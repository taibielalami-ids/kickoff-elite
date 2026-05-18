<?php

class TeamAd extends Model
{
    private const POSITION_SLOTS = [
        'gk',
        'lb',
        'lcb',
        'rcb',
        'rb',
        'lcm',
        'rcm',
        'cam',
        'lw',
        'rw',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensurePositionsTable();
    }

    public function listActive(int $viewerUserId, string $city = ''): array
    {
        $city = trim($city);
        if ($city !== '') {
            return $this->all(
                'SELECT
                    ta.*,
                    p.name AS pitch_name,
                    p.city,
                    p.address,
                    u.username AS creator_username,
                    (SELECT COUNT(*) FROM team_ad_members tam WHERE tam.ad_id = ta.id AND tam.status = "joined") AS joined_count,
                    (SELECT COUNT(*) FROM team_ad_members tam2 WHERE tam2.ad_id = ta.id AND tam2.user_id = ? AND tam2.status = "joined") AS joined_by_me
                 FROM team_ads ta
                 INNER JOIN pitches p ON p.id = ta.pitch_id
                 INNER JOIN users u ON u.id = ta.creator_user_id
                 WHERE ta.status IN ("open", "full")
                   AND ta.match_start >= NOW()
                   AND p.city = ?
                 ORDER BY ta.match_start ASC, ta.id DESC',
                'is',
                [$viewerUserId, $city]
            );
        }

        return $this->all(
            'SELECT
                ta.*,
                p.name AS pitch_name,
                p.city,
                p.address,
                u.username AS creator_username,
                (SELECT COUNT(*) FROM team_ad_members tam WHERE tam.ad_id = ta.id AND tam.status = "joined") AS joined_count,
                (SELECT COUNT(*) FROM team_ad_members tam2 WHERE tam2.ad_id = ta.id AND tam2.user_id = ? AND tam2.status = "joined") AS joined_by_me
             FROM team_ads ta
             INNER JOIN pitches p ON p.id = ta.pitch_id
             INNER JOIN users u ON u.id = ta.creator_user_id
             WHERE ta.status IN ("open", "full")
               AND ta.match_start >= NOW()
             ORDER BY ta.match_start ASC, ta.id DESC',
            'i',
            [$viewerUserId]
        );
    }

    public function membersByAdIds(array $adIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $adIds)));
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
        if (empty($ids)) {
            return [];
        }

        $this->backfillMemberPositions($ids);

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $rows = $this->all(
            'SELECT
                tam.ad_id,
                tam.user_id,
                tam.role_name,
                tam.created_at,
                u.username,
                COALESCE(tap.slot_key, "") AS slot_key
             FROM team_ad_members tam
             INNER JOIN users u ON u.id = tam.user_id
             LEFT JOIN team_ad_positions tap ON tap.ad_id = tam.ad_id AND tap.user_id = tam.user_id
             WHERE tam.status = "joined"
               AND tam.ad_id IN (' . $placeholders . ')
             ORDER BY tam.ad_id ASC, tam.id ASC',
            $types,
            $ids
        );

        $grouped = [];
        foreach ($rows as $row) {
            $adId = (int) ($row['ad_id'] ?? 0);
            if ($adId <= 0) {
                continue;
            }
            if (!isset($grouped[$adId])) {
                $grouped[$adId] = [];
            }
            $grouped[$adId][] = $row;
        }

        return $grouped;
    }

    public function positionSlots(): array
    {
        return self::POSITION_SLOTS;
    }

    public function createAd(
        int $creatorUserId,
        int $pitchId,
        string $matchStart,
        int $teamSizeTarget,
        string $notes,
        string $creatorRole,
        string $creatorSlot = ''
    ): array {
        if ($teamSizeTarget < 2 || $teamSizeTarget > 10) {
            return ['ok' => false, 'error' => 'Team size must be between 2 and 10.'];
        }

        $pitch = $this->one('SELECT id FROM pitches WHERE id = ? AND is_active = 1 LIMIT 1', 'i', [$pitchId]);
        if (!$pitch) {
            return ['ok' => false, 'error' => 'Selected pitch was not found.'];
        }

        $matchStartTs = strtotime($matchStart);
        if ($matchStartTs === false || $matchStartTs <= time()) {
            return ['ok' => false, 'error' => 'Match time must be in the future.'];
        }

        $this->db->begin_transaction();
        try {
            $matchEnd = date('Y-m-d H:i:s', strtotime('+1 hour', $matchStartTs));
            $adId = $this->insert(
                'INSERT INTO team_ads
                 (creator_user_id, pitch_id, match_start, match_end, team_size_target, status, notes, created_at)
                 VALUES (?, ?, ?, ?, ?, "open", ?, NOW())',
                'iissis',
                [$creatorUserId, $pitchId, date('Y-m-d H:i:s', $matchStartTs), $matchEnd, $teamSizeTarget, $notes]
            );

            $this->run(
                'INSERT INTO team_ad_members (ad_id, user_id, role_name, status, created_at)
                 VALUES (?, ?, ?, "joined", NOW())',
                'iis',
                [$adId, $creatorUserId, $creatorRole]
            );

            $slotResult = $this->assignSlot($adId, $creatorUserId, $creatorSlot);
            if (!($slotResult['ok'] ?? false)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => (string) ($slotResult['error'] ?? 'Could not assign creator position.')];
            }

            $this->syncStatus($adId);
            $this->db->commit();
            return ['ok' => true, 'error' => '', 'ad_id' => $adId];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not create ad: ' . $e->getMessage()];
        }
    }

    public function joinAd(int $adId, int $userId, string $roleName, string $slotKey = ''): array
    {
        $this->db->begin_transaction();
        try {
            $ad = $this->one(
                'SELECT * FROM team_ads WHERE id = ? AND status IN ("open", "full") AND match_start >= NOW() LIMIT 1 FOR UPDATE',
                'i',
                [$adId]
            );
            if (!$ad) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Ad is no longer available.'];
            }

            if ($this->exists('SELECT id FROM team_ad_members WHERE ad_id = ? AND user_id = ? AND status = "joined" LIMIT 1', 'ii', [$adId, $userId])) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'You already joined this ad.'];
            }

            $joinedCount = (int) ($this->one(
                'SELECT COUNT(*) AS c FROM team_ad_members WHERE ad_id = ? AND status = "joined"',
                'i',
                [$adId]
            )['c'] ?? 0);
            if ($joinedCount >= (int) $ad['team_size_target']) {
                $this->run('UPDATE team_ads SET status = "full" WHERE id = ?', 'i', [$adId]);
                $this->db->commit();
                return ['ok' => false, 'error' => 'This ad is already full.'];
            }

            $this->run(
                'INSERT INTO team_ad_members (ad_id, user_id, role_name, status, created_at)
                 VALUES (?, ?, ?, "joined", NOW())',
                'iis',
                [$adId, $userId, $roleName]
            );

            $slotResult = $this->assignSlot($adId, $userId, $slotKey);
            if (!($slotResult['ok'] ?? false)) {
                $this->db->rollback();
                return ['ok' => false, 'error' => (string) ($slotResult['error'] ?? 'Could not assign player position.')];
            }

            $this->syncStatus($adId);
            $this->db->commit();
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not join ad: ' . $e->getMessage()];
        }
    }

    public function leaveAd(int $adId, int $userId): array
    {
        $this->db->begin_transaction();
        try {
            $ad = $this->one('SELECT * FROM team_ads WHERE id = ? LIMIT 1 FOR UPDATE', 'i', [$adId]);
            if (!$ad) {
                $this->db->rollback();
                return ['ok' => false, 'error' => 'Ad not found.'];
            }

            if ((int) $ad['creator_user_id'] === $userId) {
                $this->run('UPDATE team_ads SET status = "closed" WHERE id = ?', 'i', [$adId]);
                $this->run('DELETE FROM team_ad_positions WHERE ad_id = ?', 'i', [$adId]);
                $this->db->commit();
                return ['ok' => true, 'error' => ''];
            }

            $this->run('DELETE FROM team_ad_members WHERE ad_id = ? AND user_id = ?', 'ii', [$adId, $userId]);
            $this->run('DELETE FROM team_ad_positions WHERE ad_id = ? AND user_id = ?', 'ii', [$adId, $userId]);
            $this->syncStatus($adId);
            $this->db->commit();
            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Could not leave ad: ' . $e->getMessage()];
        }
    }

    public function closeAd(int $adId, int $creatorUserId): array
    {
        $this->run(
            'UPDATE team_ads SET status = "closed" WHERE id = ? AND creator_user_id = ?',
            'ii',
            [$adId, $creatorUserId]
        );
        return ['ok' => true, 'error' => ''];
    }

    public function cityOptions(): array
    {
        $rows = $this->all(
            'SELECT DISTINCT city
             FROM pitches
             WHERE is_active = 1 AND city IS NOT NULL AND city <> ""
             ORDER BY city ASC'
        );
        return array_values(array_filter(array_map(
            static fn(array $row): string => trim((string) ($row['city'] ?? '')),
            $rows
        )));
    }

    private function syncStatus(int $adId): void
    {
        $ad = $this->one('SELECT id, status, team_size_target, match_start FROM team_ads WHERE id = ? LIMIT 1', 'i', [$adId]);
        if (!$ad) {
            return;
        }
        if ((string) $ad['status'] === 'closed') {
            return;
        }

        $joinedCount = (int) ($this->one(
            'SELECT COUNT(*) AS c FROM team_ad_members WHERE ad_id = ? AND status = "joined"',
            'i',
            [$adId]
        )['c'] ?? 0);

        if (strtotime((string) $ad['match_start']) < time()) {
            $this->run('UPDATE team_ads SET status = "closed" WHERE id = ?', 'i', [$adId]);
            return;
        }

        if ($joinedCount >= (int) $ad['team_size_target']) {
            $this->run('UPDATE team_ads SET status = "full" WHERE id = ?', 'i', [$adId]);
            return;
        }

        $this->run('UPDATE team_ads SET status = "open" WHERE id = ?', 'i', [$adId]);
    }

    private function normalizeSlotKey(string $slotKey): string
    {
        $slot = strtolower(trim($slotKey));
        return in_array($slot, self::POSITION_SLOTS, true) ? $slot : '';
    }

    private function firstAvailableSlot(int $adId): string
    {
        $rows = $this->all('SELECT slot_key FROM team_ad_positions WHERE ad_id = ?', 'i', [$adId]);
        $occupied = [];
        foreach ($rows as $row) {
            $key = strtolower(trim((string) ($row['slot_key'] ?? '')));
            if ($key !== '') {
                $occupied[$key] = true;
            }
        }

        foreach (self::POSITION_SLOTS as $slot) {
            if (!isset($occupied[$slot])) {
                return $slot;
            }
        }

        return '';
    }

    private function assignSlot(int $adId, int $userId, string $preferredSlot): array
    {
        $slot = $this->normalizeSlotKey($preferredSlot);
        if ($slot === '') {
            $slot = $this->firstAvailableSlot($adId);
        }
        if ($slot === '') {
            return ['ok' => false, 'error' => 'No field positions available for this ad.'];
        }

        if ($this->exists('SELECT id FROM team_ad_positions WHERE ad_id = ? AND slot_key = ? LIMIT 1', 'is', [$adId, $slot])) {
            return ['ok' => false, 'error' => 'Selected field position is already taken.'];
        }

        $this->run(
            'INSERT INTO team_ad_positions (ad_id, user_id, slot_key, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE slot_key = VALUES(slot_key), created_at = NOW()',
            'iis',
            [$adId, $userId, $slot]
        );

        return ['ok' => true, 'error' => ''];
    }

    private function ensurePositionsTable(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS team_ad_positions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ad_id INT NOT NULL,
            user_id INT NOT NULL,
            slot_key VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_team_ad_position_slot (ad_id, slot_key),
            UNIQUE KEY uq_team_ad_position_user (ad_id, user_id),
            FOREIGN KEY (ad_id) REFERENCES team_ads(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )';
        $this->db->query($sql);
    }

    private function backfillMemberPositions(array $adIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $adIds)));
        if (empty($ids)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $rows = $this->all(
            'SELECT tam.ad_id, tam.user_id
             FROM team_ad_members tam
             LEFT JOIN team_ad_positions tap ON tap.ad_id = tam.ad_id AND tap.user_id = tam.user_id
             WHERE tam.status = "joined"
               AND tap.id IS NULL
               AND tam.ad_id IN (' . $placeholders . ')
             ORDER BY tam.ad_id ASC, tam.id ASC',
            $types,
            $ids
        );

        foreach ($rows as $row) {
            $adId = (int) ($row['ad_id'] ?? 0);
            $userId = (int) ($row['user_id'] ?? 0);
            if ($adId <= 0 || $userId <= 0) {
                continue;
            }
            $this->assignSlot($adId, $userId, '');
        }
    }
}
