<?php

class BackupCall extends Model
{
    public function listOpenForUser(int $viewerUserId, string $city = ''): array
    {
        $city = trim($city);
        if ($city !== '') {
            return $this->all(
                'SELECT
                    bc.*,
                    p.name AS pitch_name,
                    p.city,
                    p.address,
                    u.username AS requester_username,
                    (SELECT COUNT(*) FROM backup_call_responses bcr WHERE bcr.backup_call_id = bc.id) AS responses_count,
                    (SELECT COUNT(*) FROM backup_call_responses bcr2 WHERE bcr2.backup_call_id = bc.id AND bcr2.user_id = ?) AS responded_by_me
                 FROM backup_calls bc
                 INNER JOIN pitches p ON p.id = bc.pitch_id
                 INNER JOIN users u ON u.id = bc.requester_user_id
                 WHERE bc.status = "open"
                   AND bc.expires_at > NOW()
                   AND p.city = ?
                 ORDER BY bc.match_start ASC, bc.id DESC',
                'is',
                [$viewerUserId, $city]
            );
        }

        return $this->all(
            'SELECT
                bc.*,
                p.name AS pitch_name,
                p.city,
                p.address,
                u.username AS requester_username,
                (SELECT COUNT(*) FROM backup_call_responses bcr WHERE bcr.backup_call_id = bc.id) AS responses_count,
                (SELECT COUNT(*) FROM backup_call_responses bcr2 WHERE bcr2.backup_call_id = bc.id AND bcr2.user_id = ?) AS responded_by_me
             FROM backup_calls bc
             INNER JOIN pitches p ON p.id = bc.pitch_id
             INNER JOIN users u ON u.id = bc.requester_user_id
             WHERE bc.status = "open"
               AND bc.expires_at > NOW()
             ORDER BY bc.match_start ASC, bc.id DESC',
            'i',
            [$viewerUserId]
        );
    }

    public function listMyEligibleBookings(int $userId): array
    {
        return $this->all(
            'SELECT
                b.id,
                b.slot_start,
                b.slot_end,
                p.name AS pitch_name,
                p.city,
                p.address
             FROM bookings b
             INNER JOIN pitches p ON p.id = b.pitch_id
             WHERE b.creator_user_id = ?
               AND b.status IN ("reserved", "waiting_payment", "pending", "waiting_players")
               AND b.slot_start >= NOW()
             ORDER BY b.slot_start ASC',
            'i',
            [$userId]
        );
    }

    public function createFromBooking(
        int $requesterUserId,
        int $bookingId,
        string $neededRole,
        int $isFree,
        float $rewardAmount,
        string $message,
        int $expiresMinutes
    ): array {
        $this->db->begin_transaction();
        try {
            $booking = $this->one(
                'SELECT b.id, b.pitch_id, b.slot_start, b.slot_end
                 FROM bookings b
                 WHERE b.id = ? AND b.creator_user_id = ? AND b.status IN ("reserved", "waiting_payment", "pending", "waiting_players")
                 LIMIT 1
                 FOR UPDATE',
                'ii',
                [$bookingId, $requesterUserId]
            );
            if (!$booking) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Booking not found or not eligible for backup call.'];
            }

            if (strtotime((string) $booking['slot_start']) <= time()) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Match already started.'];
            }

            if ($isFree === 1) {
                $rewardAmount = 0.00;
            }
            if ($rewardAmount < 0) {
                $rewardAmount = 0.00;
            }
            if ($expiresMinutes < 10) {
                $expiresMinutes = 10;
            }
            if ($expiresMinutes > 360) {
                $expiresMinutes = 360;
            }

            $this->run(
                'INSERT INTO backup_calls
                 (booking_id, requester_user_id, pitch_id, match_start, needed_role, is_free, reward_amount, message, status, expires_at, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, "open", DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())',
                'iiissidsi',
                [
                    $bookingId,
                    $requesterUserId,
                    (int) $booking['pitch_id'],
                    (string) $booking['slot_start'],
                    $neededRole,
                    $isFree,
                    $rewardAmount,
                    $message,
                    $expiresMinutes,
                ]
            );

            $this->db->commit();
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not create backup call: ' . $e->getMessage()];
        }
    }

    public function respond(int $callId, int $userId, string $message): array
    {
        $this->db->begin_transaction();
        try {
            $call = $this->one(
                'SELECT * FROM backup_calls WHERE id = ? AND status = "open" AND expires_at > NOW() LIMIT 1 FOR UPDATE',
                'i',
                [$callId]
            );
            if (!$call) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Backup call is no longer open.'];
            }

            if ((int) $call['requester_user_id'] === $userId) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'You cannot respond to your own backup call.'];
            }

            if ($this->exists('SELECT id FROM backup_call_responses WHERE backup_call_id = ? AND user_id = ? LIMIT 1', 'ii', [$callId, $userId])) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'You already responded to this backup call.'];
            }

            $this->run(
                'INSERT INTO backup_call_responses (backup_call_id, user_id, status, message, created_at)
                 VALUES (?, ?, "pending", ?, NOW())',
                'iis',
                [$callId, $userId, $message]
            );

            $this->db->commit();
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not send response: ' . $e->getMessage()];
        }
    }

    public function selectResponder(int $callId, int $requesterUserId, int $responseId): array
    {
        $this->db->begin_transaction();
        try {
            $call = $this->one(
                'SELECT * FROM backup_calls WHERE id = ? AND requester_user_id = ? AND status = "open" LIMIT 1 FOR UPDATE',
                'ii',
                [$callId, $requesterUserId]
            );
            if (!$call) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Backup call not found.'];
            }

            $response = $this->one(
                'SELECT * FROM backup_call_responses WHERE id = ? AND backup_call_id = ? LIMIT 1 FOR UPDATE',
                'ii',
                [$responseId, $callId]
            );
            if (!$response) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Response not found.'];
            }

            $this->run('UPDATE backup_call_responses SET status = "selected" WHERE id = ?', 'i', [$responseId]);
            $this->run(
                'UPDATE backup_call_responses SET status = "rejected" WHERE backup_call_id = ? AND id <> ? AND status = "pending"',
                'ii',
                [$callId, $responseId]
            );
            $this->run('UPDATE backup_calls SET status = "filled", selected_user_id = ? WHERE id = ?', 'ii', [(int) $response['user_id'], $callId]);

            $this->db->commit();
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not select responder: ' . $e->getMessage()];
        }
    }

    public function closeByOwner(int $callId, int $requesterUserId): array
    {
        $this->run('UPDATE backup_calls SET status = "closed" WHERE id = ? AND requester_user_id = ?', 'ii', [$callId, $requesterUserId]);
        return ['ok' => true, 'error' => ''];
    }
}
