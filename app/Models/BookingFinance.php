<?php

class BookingFinance
{
    private mysqli $db;
    private float $fullBookingAmount;
    private int $fullBookingTickets;

    public function __construct(mysqli $db, float $fullBookingAmount, int $fullBookingTickets)
    {
        $this->db = $db;
        $this->fullBookingAmount = $fullBookingAmount;
        $this->fullBookingTickets = $fullBookingTickets;
    }

    public function chargeDirectBooking(int $userId, string $paymentMode): array
    {
        $wallet = $this->getWalletForUserForUpdate($userId);
        if (!$wallet) {
            return ['ok' => false, 'error' => 'Wallet not found.'];
        }

        $walletId = (int) $wallet['id'];
        if ($paymentMode === 'wallet') {
            if ((float) $wallet['balance'] < $this->fullBookingAmount) {
                return ['ok' => false, 'error' => 'Insufficient wallet balance for direct booking.'];
            }

            $this->run('UPDATE wallets SET balance = balance - ? WHERE id = ?', 'di', [$this->fullBookingAmount, $walletId]);
            $this->insertWalletTx($walletId, $userId, 'booking_pay_wallet', $this->fullBookingAmount, 0, 'Direct booking payment');
            return ['ok' => true, 'error' => '', 'paid_amount' => $this->fullBookingAmount, 'paid_tickets' => 0];
        }

        if ((int) $wallet['ticket_balance'] < $this->fullBookingTickets) {
            return ['ok' => false, 'error' => 'Insufficient ticket balance for direct booking.'];
        }

        $this->run('UPDATE wallets SET ticket_balance = ticket_balance - ? WHERE id = ?', 'ii', [$this->fullBookingTickets, $walletId]);
        $this->insertWalletTx($walletId, $userId, 'booking_pay_tickets', 0.00, -$this->fullBookingTickets, 'Direct booking payment (tickets)');
        return ['ok' => true, 'error' => '', 'paid_amount' => 0.00, 'paid_tickets' => $this->fullBookingTickets];
    }

    public function refundIfEligible(array $booking, int $userId): array
    {
        if ((int) ($booking['is_refunded'] ?? 0) === 1) {
            return [
                'ok' => true,
                'is_refunded' => 1,
                'refunded_amount' => (float) ($booking['refunded_amount'] ?? 0),
                'refunded_tickets' => (int) ($booking['refunded_tickets'] ?? 0),
            ];
        }

        $paymentMode = (string) ($booking['payment_mode'] ?? 'none');
        if ($paymentMode === 'none') {
            return ['ok' => true, 'is_refunded' => 0, 'refunded_amount' => 0.00, 'refunded_tickets' => 0];
        }

        $wallet = $this->getWalletForUserForUpdate($userId);
        if (!$wallet) {
            return ['ok' => false, 'error' => 'Wallet not found for refund.'];
        }

        $walletId = (int) $wallet['id'];
        if ($paymentMode === 'wallet') {
            $refundAmount = (float) ($booking['paid_amount'] ?? 0.00);
            if ($refundAmount <= 0) {
                return ['ok' => true, 'is_refunded' => 0, 'refunded_amount' => 0.00, 'refunded_tickets' => 0];
            }

            $this->run('UPDATE wallets SET balance = balance + ? WHERE id = ?', 'di', [$refundAmount, $walletId]);
            $this->insertWalletTx($walletId, $userId, 'booking_refund_wallet', $refundAmount, 0, 'Booking cancellation refund');
            $ownerReverse = $this->reverseOwnerCreditForRefund((int) ($booking['id'] ?? 0), (int) ($booking['pitch_id'] ?? 0), $refundAmount);
            if (!($ownerReverse['ok'] ?? false)) {
                return ['ok' => false, 'error' => (string) ($ownerReverse['error'] ?? 'Could not reverse owner credit.')];
            }
            return ['ok' => true, 'is_refunded' => 1, 'refunded_amount' => $refundAmount, 'refunded_tickets' => 0];
        }

        if ($paymentMode === 'tickets') {
            $refundTickets = (int) ($booking['paid_tickets'] ?? 0);
            if ($refundTickets <= 0) {
                return ['ok' => true, 'is_refunded' => 0, 'refunded_amount' => 0.00, 'refunded_tickets' => 0];
            }

            $this->run('UPDATE wallets SET ticket_balance = ticket_balance + ? WHERE id = ?', 'ii', [$refundTickets, $walletId]);
            $this->insertWalletTx($walletId, $userId, 'booking_refund_tickets', 0.00, $refundTickets, 'Booking cancellation refund (tickets)');

            $ownerReverse = $this->reverseOwnerCreditForRefund(
                (int) ($booking['id'] ?? 0),
                (int) ($booking['pitch_id'] ?? 0),
                $refundTickets * 50.0
            );
            if (!($ownerReverse['ok'] ?? false)) {
                return ['ok' => false, 'error' => (string) ($ownerReverse['error'] ?? 'Could not reverse owner credit.')];
            }
            return ['ok' => true, 'is_refunded' => 1, 'refunded_amount' => 0.00, 'refunded_tickets' => $refundTickets];
        }

        return ['ok' => true, 'is_refunded' => 0, 'refunded_amount' => 0.00, 'refunded_tickets' => 0];
    }

    public function creditOwnerForPaidBooking(int $bookingId, int $pitchId, string $paymentMode, float $paidAmount, int $paidTickets): array
    {
        $ownerId = $this->getPitchOwnerId($pitchId);
        if ($ownerId <= 0) {
            return ['ok' => false, 'error' => 'Admin account not found.'];
        }

        $creditAmount = 0.0;
        if ($paymentMode === 'wallet') {
            $creditAmount = $paidAmount;
        } elseif ($paymentMode === 'tickets') {
            $creditAmount = $paidTickets * 50.0;
        }
        if ($creditAmount <= 0) {
            return ['ok' => true, 'error' => ''];
        }

        $ownerWallet = $this->getWalletForUserForUpdate($ownerId);
        if (!$ownerWallet) {
            return ['ok' => false, 'error' => 'Pitch owner wallet not found.'];
        }

        $ownerWalletId = (int) $ownerWallet['id'];
        $this->run('UPDATE wallets SET balance = balance + ? WHERE id = ?', 'di', [$creditAmount, $ownerWalletId]);
        $this->insertWalletTx(
            $ownerWalletId,
            $ownerId,
            'booking_owner_credit',
            $creditAmount,
            0,
            'Booking #' . $bookingId . ' owner credit (' . $paymentMode . ')'
        );
        return ['ok' => true, 'error' => ''];
    }

    private function getWalletForUserForUpdate(int $userId): ?array
    {
        $this->run(
            'INSERT INTO wallets (user_id, balance, ticket_balance) VALUES (?, 0.00, 0)
             ON DUPLICATE KEY UPDATE user_id = user_id',
            'i',
            [$userId]
        );
        return $this->one('SELECT * FROM wallets WHERE user_id = ? LIMIT 1 FOR UPDATE', 'i', [$userId]);
    }

    private function insertWalletTx(int $walletId, int $userId, string $type, float $amount, int $ticketsChange, string $reference): void
    {
        $this->run(
            'INSERT INTO wallet_transactions
             (wallet_id, user_id, tx_type, amount, tickets_change, reference_text, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            'iisdis',
            [$walletId, $userId, $type, $amount, $ticketsChange, $reference]
        );
    }

    private function reverseOwnerCreditForRefund(int $bookingId, int $pitchId, float $amount): array
    {
        if ($amount <= 0) {
            return ['ok' => true, 'error' => ''];
        }

        $ownerId = $this->getPitchOwnerId($pitchId);
        if ($ownerId <= 0) {
            return ['ok' => false, 'error' => 'Admin account not found for refund reversal.'];
        }

        $ownerWallet = $this->getWalletForUserForUpdate($ownerId);
        if (!$ownerWallet) {
            return ['ok' => false, 'error' => 'Pitch owner wallet not found for refund reversal.'];
        }

        $ownerWalletId = (int) $ownerWallet['id'];
        $this->run('UPDATE wallets SET balance = balance - ? WHERE id = ?', 'di', [$amount, $ownerWalletId]);
        $this->insertWalletTx(
            $ownerWalletId,
            $ownerId,
            'booking_owner_refund_reverse',
            $amount,
            0,
            'Booking #' . $bookingId . ' owner refund reverse'
        );
        return ['ok' => true, 'error' => ''];
    }

    private function getPitchOwnerId(int $pitchId): int
    {
        if ($pitchId <= 0) {
            return 0;
        }
        $row = $this->one('SELECT owner_id FROM pitches WHERE id = ? LIMIT 1', 'i', [$pitchId]);
        return (int) ($row['owner_id'] ?? 0);
    }

    private function one(string $sql, string $types = '', array $params = []): ?array
    {
        $stmt = $this->stmt($sql, $types, $params);
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    private function run(string $sql, string $types = '', array $params = []): void
    {
        $stmt = $this->stmt($sql, $types, $params);
        $stmt->close();
    }

    private function stmt(string $sql, string $types = '', array $params = []): mysqli_stmt
    {
        $stmt = $this->db->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }
}
