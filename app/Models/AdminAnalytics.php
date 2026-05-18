<?php

class AdminAnalytics extends Model
{
    public function adminSummary(): array
    {
        $sql = 'SELECT
                    COUNT(CASE WHEN role = "user" THEN 1 END) AS users_count
                FROM users';
        $usersRes = $this->db->query($sql);
        $users = $usersRes ? ($usersRes->fetch_assoc() ?: []) : [];

        return [
            'users_count' => (int) ($users['users_count'] ?? 0),
        ];
    }
}

