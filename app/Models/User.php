<?php

class User extends Model
{
    public function findByUsername(string $username): ?array
    {
        $sql = 'SELECT * FROM users WHERE username = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = 'SELECT * FROM users WHERE email = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM users WHERE id = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO users (username, email, password_hash, date_of_birth, city, role, email_verified, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssssss',
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['date_of_birth'],
            $data['city'],
            $data['role']
        );
        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();

        $this->ensureWalletForUser($id);
        return $id;
    }

    public function savePlayingRoles(int $userId, array $roles): void
    {
        $allowed = ['goalkeeper', 'defender', 'midfielder', 'attacker'];
        $clean = [];
        foreach ($roles as $role) {
            $value = trim((string) $role);
            if (in_array($value, $allowed, true) && !in_array($value, $clean, true)) {
                $clean[] = $value;
            }
        }

        $sqlDelete = 'DELETE FROM user_playing_roles WHERE user_id = ?';
        $stmtDelete = $this->db->prepare($sqlDelete);
        $stmtDelete->bind_param('i', $userId);
        $stmtDelete->execute();
        $stmtDelete->close();

        if (empty($clean)) {
            return;
        }

        $sqlInsert = 'INSERT INTO user_playing_roles (user_id, role_name) VALUES (?, ?)';
        $stmtInsert = $this->db->prepare($sqlInsert);
        foreach ($clean as $roleName) {
            $stmtInsert->bind_param('is', $userId, $roleName);
            $stmtInsert->execute();
        }
        $stmtInsert->close();
    }

    public function getPlayingRoles(int $userId): array
    {
        $sql = 'SELECT role_name FROM user_playing_roles WHERE user_id = ? ORDER BY id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_map(static fn(array $row): string => (string) $row['role_name'], $rows);
    }

    public function markEmailVerified(int $userId): void
    {
        $sql = 'UPDATE users SET email_verified = 1 WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function saveAuthCode(int $userId, string $code, string $type, int $minutes): void
    {
        $codeHash = hash('sha256', $code);
        $sql = 'INSERT INTO auth_codes (user_id, code_hash, code_type, expires_at, is_used, created_at)
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), 0, NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issi', $userId, $codeHash, $type, $minutes);
        $stmt->execute();
        $stmt->close();
    }

    public function verifyAuthCode(int $userId, string $code, string $type): ?array
    {
        $codeHash = hash('sha256', $code);
        $sql = 'SELECT * FROM auth_codes
                WHERE user_id = ? AND code_hash = ? AND code_type = ? AND is_used = 0 AND expires_at >= NOW()
                ORDER BY id DESC
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $userId, $codeHash, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function markCodeUsed(int $codeId): void
    {
        $sql = 'UPDATE auth_codes SET is_used = 1 WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $codeId);
        $stmt->execute();
        $stmt->close();
    }

    public function ensureWalletForUser(int $userId): void
    {
        $sql = 'INSERT INTO wallets (user_id, balance, ticket_balance) VALUES (?, 0.00, 0)
                ON DUPLICATE KEY UPDATE user_id = user_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function adminListUsers(int $limit = 200): array
    {
        $sql = 'SELECT id, username, email, city, role, status, email_verified, created_at
                FROM users
                ORDER BY id DESC
                LIMIT ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}

