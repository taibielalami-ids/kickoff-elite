<?php

class Booking extends Model
{
    private float $fullBookingAmount = 500.00;
    private int $fullBookingTickets = 10;
    private BookingFinance $finance;

    public function __construct()
    {
        parent::__construct();
        $this->finance = new BookingFinance($this->db, $this->fullBookingAmount, $this->fullBookingTickets);
    }

    public function createLock(int $userId, int $pitchId, string $slotStart, string $slotEnd): array
    {
        $this->db->begin_transaction();
        try {
            $this->cleanupExpiredLocks();

            $pitch = $this->getPitchForUpdate($pitchId);
            if (!$pitch) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Pitch not found.'];
            }

            if ((int) $pitch['is_active'] !== 1 || ($pitch['status'] ?? 'available') !== 'available') {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Pitch is not available right now.'];
            }

            if (!$this->isWithinPitchHours($pitch, $slotStart, $slotEnd)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Selected slot is outside pitch opening hours.'];
            }

            if ($this->hasBlockedSlot($pitchId, $slotStart, $slotEnd)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'This slot is blocked by the admin.'];
            }

            if ($this->hasActivePitchBooking($pitchId, $slotStart, $slotEnd)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'This slot is already reserved by another team.'];
            }

            if ($this->hasActivePitchLock($pitchId, $slotStart, $slotEnd)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'This slot is currently locked by another user. Try again in a moment.'];
            }

            if ($this->hasUserOverlapBooking($userId, $slotStart, $slotEnd)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'You already have another booking at this date and hour.'];
            }

            $token = bin2hex(random_bytes(16));
            $this->run(
                'INSERT INTO booking_locks
                 (pitch_id, user_id, slot_start, slot_end, lock_token, expires_at, status, created_at)
                 VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE), "active", NOW())',
                'iisss',
                [$pitchId, $userId, $slotStart, $slotEnd, $token]
            );

            $this->db->commit();
            return ['ok' => true, 'error' => '', 'token' => $token];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not lock slot: ' . $e->getMessage()];
        }
    }

    public function findActiveLockByToken(string $token, int $userId): ?array
    {
        return $this->one(
            'SELECT
                bl.*,
                p.name AS pitch_name,
                p.city,
                p.address,
                p.price_per_player,
                p.team_size
             FROM booking_locks bl
             INNER JOIN pitches p ON p.id = bl.pitch_id
             WHERE bl.lock_token = ?
               AND bl.user_id = ?
               AND bl.status = "active"
               AND bl.expires_at > NOW()
             LIMIT 1',
            'si',
            [$token, $userId]
        );
    }

    public function confirmFromLock(string $token, int $userId, string $paymentMode): array
    {
        $paymentMode = in_array($paymentMode, ['wallet', 'tickets'], true) ? $paymentMode : 'wallet';

        $this->db->begin_transaction();
        try {
            $this->cleanupExpiredLocks();

            $lock = $this->findLockForUpdate($token, $userId);
            if (!$lock) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Lock expired or not found. Please try booking again.'];
            }

            $pitchId = (int) $lock['pitch_id'];
            $slotStart = (string) $lock['slot_start'];
            $slotEnd = (string) $lock['slot_end'];

            if ($this->hasBlockedSlot($pitchId, $slotStart, $slotEnd)) {
                $this->markLockExpired((int) $lock['id']);
                $this->db->commit();
                return ['ok' => false, 'error' => 'Slot became blocked. Please choose another slot.'];
            }

            if ($this->hasActivePitchBooking($pitchId, $slotStart, $slotEnd)) {
                $this->markLockExpired((int) $lock['id']);
                $this->db->commit();
                return ['ok' => false, 'error' => 'Slot is no longer available.'];
            }

            if ($this->hasUserOverlapBooking($userId, $slotStart, $slotEnd)) {
                $this->markLockExpired((int) $lock['id']);
                $this->db->commit();
                return ['ok' => false, 'error' => 'You already have another booking in this timeslot.'];
            }

            $bookingStatus = 'reserved';
            $storedPaymentMode = $paymentMode;

            $charge = $this->finance->chargeDirectBooking($userId, $paymentMode);
            if (!($charge['ok'] ?? false)) {
                $this->markLockExpired((int) $lock['id']);
                $this->db->commit();
                return ['ok' => false, 'error' => (string) ($charge['error'] ?? 'Payment failed.')];
            }

            $paidAmount = (float) ($charge['paid_amount'] ?? 0.00);
            $paidTickets = (int) ($charge['paid_tickets'] ?? 0);

            $bookingId = $this->insert(
                'INSERT INTO bookings
                 (pitch_id, creator_user_id, slot_start, slot_end, status, total_price, payment_mode, paid_amount, paid_tickets, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                'iisssdsdi',
                [
                    $pitchId,
                    $userId,
                    $slotStart,
                    $slotEnd,
                    $bookingStatus,
                    $this->fullBookingAmount,
                    $storedPaymentMode,
                    $paidAmount,
                    $paidTickets,
                ]
            );

            $ownerCredit = $this->finance->creditOwnerForPaidBooking(
                $bookingId,
                $pitchId,
                $storedPaymentMode,
                $paidAmount,
                $paidTickets
            );
            if (!($ownerCredit['ok'] ?? false)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => (string) ($ownerCredit['error'] ?? 'Could not credit admin wallet.')];
            }

            $bookingCode = $this->generateUniqueBookingCode();
            $this->run(
                'INSERT INTO booking_codes (booking_id, code, status, created_at)
                 VALUES (?, ?, "active", NOW())',
                'is',
                [$bookingId, $bookingCode]
            );

            $this->markLockConsumed((int) $lock['id']);

            $this->db->commit();
            return [
                'ok' => true,
                'error' => '',
                'booking_id' => $bookingId,
                'booking_code' => $bookingCode,
                'status' => $bookingStatus,
                'payment_mode' => $storedPaymentMode,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not confirm booking: ' . $e->getMessage()];
        }
    }

    public function myBookings(int $userId): array
    {
        return $this->all(
            'SELECT
                b.*,
                p.name AS pitch_name,
                p.city,
                p.address,
                bc.code AS booking_code,
                bc.status AS code_status,
                b.payment_mode,
                b.paid_amount,
                b.paid_tickets,
                b.is_refunded,
                b.refunded_amount,
                b.refunded_tickets,
                b.cancelled_at
             FROM bookings b
             INNER JOIN pitches p ON p.id = b.pitch_id
             LEFT JOIN booking_codes bc ON bc.booking_id = b.id
             WHERE b.creator_user_id = ?
             ORDER BY b.slot_start DESC, b.id DESC',
            'i',
            [$userId]
        );
    }

    public function findByCodeForAdmin(string $code): ?array
    {
        return $this->one(
            'SELECT
                bc.id AS booking_code_id,
                bc.code,
                bc.status AS code_status,
                bc.used_at,
                b.id AS booking_id,
                b.status AS booking_status,
                b.slot_start,
                b.slot_end,
                b.total_price,
                p.name AS pitch_name,
                p.owner_id,
                u.username AS captain_name
             FROM booking_codes bc
             INNER JOIN bookings b ON b.id = bc.booking_id
             INNER JOIN pitches p ON p.id = b.pitch_id
             INNER JOIN users u ON u.id = b.creator_user_id
             WHERE bc.code = ?
             LIMIT 1',
            's',
            [$code]
        );
    }

    public function markCodeUsed(int $bookingCodeId): void
    {
        $this->run(
            'UPDATE booking_codes SET status = "used", used_at = NOW() WHERE id = ? AND status = "active"',
            'i',
            [$bookingCodeId]
        );
    }

    public function cancelByUser(int $bookingId, int $userId): array
    {
        $this->db->begin_transaction();
        try {
            $booking = $this->one(
                'SELECT b.*, bc.id AS booking_code_id
                 FROM bookings b
                 LEFT JOIN booking_codes bc ON bc.booking_id = b.id
                 WHERE b.id = ? AND b.creator_user_id = ?
                 LIMIT 1
                 FOR UPDATE',
                'ii',
                [$bookingId, $userId]
            );
            if (!$booking) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Booking not found.'];
            }

            $status = (string) ($booking['status'] ?? '');
            if (in_array($status, ['cancelled', 'completed'], true)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Booking cannot be cancelled in its current status.'];
            }

            $slotStartTs = strtotime((string) ($booking['slot_start'] ?? ''));
            $deadlineTs = strtotime('+48 hours');
            if ($slotStartTs === false || $slotStartTs <= $deadlineTs) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Cancellation is only allowed more than 48 hours before the booking time.'];
            }

            $refundResult = $this->finance->refundIfEligible($booking, $userId);
            if (!($refundResult['ok'] ?? false)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => (string) ($refundResult['error'] ?? 'Refund failed.')];
            }

            $isRefunded = (int) ($refundResult['is_refunded'] ?? 0);
            $refundedAmount = (float) ($refundResult['refunded_amount'] ?? 0.00);
            $refundedTickets = (int) ($refundResult['refunded_tickets'] ?? 0);
            $this->run(
                'UPDATE bookings
                 SET status = "cancelled",
                     is_refunded = ?,
                     refunded_amount = ?,
                     refunded_tickets = ?,
                     cancelled_at = NOW()
                 WHERE id = ?',
                'idii',
                [$isRefunded, $refundedAmount, $refundedTickets, $bookingId]
            );

            if (!empty($booking['booking_code_id'])) {
                $this->markCodeCancelled((int) $booking['booking_code_id']);
            }

            $this->db->commit();
            return [
                'ok' => true,
                'error' => '',
                'is_refunded' => $isRefunded === 1,
                'refunded_amount' => $refundedAmount,
                'refunded_tickets' => $refundedTickets,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not cancel booking: ' . $e->getMessage()];
        }
    }

    public function payWaitingBooking(int $bookingId, int $userId, string $paymentMode): array
    {
        $paymentMode = in_array($paymentMode, ['wallet', 'tickets'], true) ? $paymentMode : 'wallet';

        $this->db->begin_transaction();
        try {
            $booking = $this->one(
                'SELECT *
                 FROM bookings
                 WHERE id = ? AND creator_user_id = ?
                 LIMIT 1
                 FOR UPDATE',
                'ii',
                [$bookingId, $userId]
            );
            if (!$booking) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Booking not found.'];
            }

            if ((string) ($booking['status'] ?? '') !== 'waiting_payment') {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Only waiting_payment bookings can be paid.'];
            }

            $slotEndTs = strtotime((string) ($booking['slot_end'] ?? ''));
            if ($slotEndTs !== false && $slotEndTs <= time()) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'This booking has already ended and can no longer be paid.'];
            }

            $charge = $this->finance->chargeDirectBooking($userId, $paymentMode);
            if (!($charge['ok'] ?? false)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => (string) ($charge['error'] ?? 'Payment failed.')];
            }

            $paidAmount = (float) ($charge['paid_amount'] ?? 0.00);
            $paidTickets = (int) ($charge['paid_tickets'] ?? 0);

            $ownerCredit = $this->finance->creditOwnerForPaidBooking(
                $bookingId,
                (int) ($booking['pitch_id'] ?? 0),
                $paymentMode,
                $paidAmount,
                $paidTickets
            );
            if (!($ownerCredit['ok'] ?? false)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => (string) ($ownerCredit['error'] ?? 'Could not credit admin wallet.')];
            }

            $this->run(
                'UPDATE bookings
                 SET status = "reserved",
                     payment_mode = ?,
                     paid_amount = ?,
                     paid_tickets = ?
                 WHERE id = ?',
                'sdii',
                [$paymentMode, $paidAmount, $paidTickets, $bookingId]
            );

            $this->db->commit();
            return [
                'ok' => true,
                'error' => '',
                'paid_amount' => $paidAmount,
                'paid_tickets' => $paidTickets,
                'payment_mode' => $paymentMode,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not pay booking: ' . $e->getMessage()];
        }
    }

    private function cleanupExpiredLocks(): void
    {
        $this->db->query('UPDATE booking_locks SET status = "expired" WHERE status = "active" AND expires_at <= NOW()');
    }

    private function getPitchForUpdate(int $pitchId): ?array
    {
        return $this->one('SELECT * FROM pitches WHERE id = ? LIMIT 1 FOR UPDATE', 'i', [$pitchId]);
    }

    private function findLockForUpdate(string $token, int $userId): ?array
    {
        return $this->one(
            'SELECT *
             FROM booking_locks
             WHERE lock_token = ?
               AND user_id = ?
               AND status = "active"
               AND expires_at > NOW()
             LIMIT 1
             FOR UPDATE',
            'si',
            [$token, $userId]
        );
    }

    private function markLockConsumed(int $lockId): void
    {
        $this->run('UPDATE booking_locks SET status = "consumed" WHERE id = ?', 'i', [$lockId]);
    }

    private function markLockExpired(int $lockId): void
    {
        $this->run('UPDATE booking_locks SET status = "expired" WHERE id = ?', 'i', [$lockId]);
    }

    private function isWithinPitchHours(array $pitch, string $slotStart, string $slotEnd): bool
    {
        $startTime = date('H:i:s', strtotime($slotStart));
        $endTime = date('H:i:s', strtotime($slotEnd));
        return $startTime >= (string) $pitch['open_time'] && $endTime <= (string) $pitch['close_time'];
    }

    private function hasBlockedSlot(int $pitchId, string $slotStart, string $slotEnd): bool
    {
        return $this->exists(
            'SELECT id
             FROM pitch_blocked_slots
             WHERE pitch_id = ?
               AND start_at < ?
               AND end_at > ?
             LIMIT 1',
            'iss',
            [$pitchId, $slotEnd, $slotStart]
        );
    }

    private function hasActivePitchBooking(int $pitchId, string $slotStart, string $slotEnd): bool
    {
        return $this->exists(
            'SELECT id
             FROM bookings
             WHERE pitch_id = ?
               AND status IN ("pending","waiting_payment","reserved","completed")
               AND slot_start < ?
               AND slot_end > ?
             LIMIT 1',
            'iss',
            [$pitchId, $slotEnd, $slotStart]
        );
    }

    private function hasUserOverlapBooking(int $userId, string $slotStart, string $slotEnd): bool
    {
        return $this->exists(
            'SELECT id
             FROM bookings
             WHERE creator_user_id = ?
               AND status IN ("pending","waiting_payment","reserved","completed")
               AND slot_start < ?
               AND slot_end > ?
             LIMIT 1',
            'iss',
            [$userId, $slotEnd, $slotStart]
        );
    }

    private function hasActivePitchLock(int $pitchId, string $slotStart, string $slotEnd): bool
    {
        return $this->exists(
            'SELECT id
             FROM booking_locks
             WHERE pitch_id = ?
               AND status = "active"
               AND expires_at > NOW()
               AND slot_start < ?
               AND slot_end > ?
             LIMIT 1',
            'iss',
            [$pitchId, $slotEnd, $slotStart]
        );
    }

    private function generateUniqueBookingCode(): string
    {
        $attempts = 0;
        do {
            $attempts++;
            $code = strtoupper(bin2hex(random_bytes(3)));
            if (!$this->exists('SELECT id FROM booking_codes WHERE code = ? LIMIT 1', 's', [$code])) {
                return $code;
            }
        } while ($attempts < 8);

        return strtoupper(bin2hex(random_bytes(4)));
    }

    private function markCodeCancelled(int $bookingCodeId): void
    {
        $this->run('UPDATE booking_codes SET status = "cancelled" WHERE id = ? AND status = "active"', 'i', [$bookingCodeId]);
    }
}
