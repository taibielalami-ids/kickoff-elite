<?php

class Wallet extends Model
{
    private float $ticketPriceDh = 50.0;

    public function ensureForUser(int $userId): void
    {
        $sql = 'INSERT INTO wallets (user_id, balance, ticket_balance) VALUES (?, 0.00, 0)
                ON DUPLICATE KEY UPDATE user_id = user_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function getByUser(int $userId): ?array
    {
        $this->ensureForUser($userId);
        $sql = 'SELECT * FROM wallets WHERE user_id = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function withdraw(int $userId, float $amount): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Withdraw amount must be positive.'];
        }

        $this->db->begin_transaction();
        try {
            $wallet = $this->getForUpdate($userId);
            if (!$wallet) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Wallet not found.'];
            }

            if ((float) $wallet['balance'] < $amount) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Insufficient wallet balance.'];
            }

            $sql = 'UPDATE wallets SET balance = balance - ? WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('di', $amount, $wallet['id']);
            $stmt->execute();
            $stmt->close();

            $this->insertTransaction((int) $wallet['id'], $userId, 'withdrawal', $amount, 0, 'Wallet withdrawal');
            $this->db->commit();
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Withdraw failed: ' . $e->getMessage()];
        }
    }

    public function topUp(int $userId, float $amount): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Top-up amount must be positive.'];
        }

        $this->db->begin_transaction();
        try {
            $wallet = $this->getForUpdate($userId);
            if (!$wallet) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Wallet not found.'];
            }

            $sql = 'UPDATE wallets SET balance = balance + ? WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('di', $amount, $wallet['id']);
            $stmt->execute();
            $stmt->close();

            $this->insertTransaction((int) $wallet['id'], $userId, 'topup', $amount, 0, 'Wallet top-up');
            $this->db->commit();
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Top-up failed: ' . $e->getMessage()];
        }
    }

    public function buyTickets(int $userId, int $ticketCount): array
    {
        if ($ticketCount <= 0) {
            return ['ok' => false, 'error' => 'Ticket count must be at least 1.'];
        }

        $totalCost = $ticketCount * $this->ticketPriceDh;
        $this->db->begin_transaction();
        try {
            $wallet = $this->getForUpdate($userId);
            if (!$wallet) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Wallet not found.'];
            }

            if ((float) $wallet['balance'] < $totalCost) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Insufficient wallet balance for ticket purchase.'];
            }

            $sql = 'UPDATE wallets SET balance = balance - ?, ticket_balance = ticket_balance + ? WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('dii', $totalCost, $ticketCount, $wallet['id']);
            $stmt->execute();
            $stmt->close();

            $this->insertTransaction((int) $wallet['id'], $userId, 'ticket_purchase', $totalCost, $ticketCount, 'Converted balance to tickets');
            $this->db->commit();
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Ticket purchase failed: ' . $e->getMessage()];
        }
    }

    public function recentTransactions(int $userId, int $limit = 12): array
    {
        $limit = max(1, min(100, $limit));
        $wallet = $this->getByUser($userId);
        if (!$wallet) {
            return [];
        }

        $sql = 'SELECT tx_type, amount, tickets_change, reference_text, created_at
                FROM wallet_transactions
                WHERE wallet_id = ?
                ORDER BY id DESC
                LIMIT ?';
        $stmt = $this->db->prepare($sql);
        $walletId = (int) $wallet['id'];
        $stmt->bind_param('ii', $walletId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    private function getForUpdate(int $userId): ?array
    {
        $this->ensureForUser($userId);
        $sql = 'SELECT * FROM wallets WHERE user_id = ? LIMIT 1 FOR UPDATE';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    private function insertTransaction(int $walletId, int $userId, string $type, float $amount, int $ticketsChange, string $reference): void
    {
        $sql = 'INSERT INTO wallet_transactions
                (wallet_id, user_id, tx_type, amount, tickets_change, reference_text, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iisdis', $walletId, $userId, $type, $amount, $ticketsChange, $reference);
        $stmt->execute();
        $stmt->close();
    }
}

