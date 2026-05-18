<?php

class Pitch extends Model
{
    public function cityOptions(): array
    {
        $rows = $this->all(
            'SELECT DISTINCT city
             FROM pitches
             WHERE is_active = 1
               AND city IS NOT NULL
               AND city <> ""
             ORDER BY city ASC'
        );
        return array_values(array_filter(array_map(
            static fn(array $row): string => trim((string) ($row['city'] ?? '')),
            $rows
        )));
    }

    public function findById(int $pitchId): ?array
    {
        return $this->one(
            'SELECT
                p.*,
                u.username AS owner_name,
                (SELECT photo_url FROM pitch_photos ph WHERE ph.pitch_id = p.id ORDER BY ph.id ASC LIMIT 1) AS cover_photo
             FROM pitches p
             INNER JOIN users u ON u.id = p.owner_id
             WHERE p.id = ?
               AND p.is_active = 1
             LIMIT 1',
            'i',
            [$pitchId]
        );
    }

    public function bookedRangesForDay(int $pitchId, string $day): array
    {
        [$startAt, $endAt] = $this->dayBounds($day);
        return $this->all(
            'SELECT slot_start, slot_end, status
             FROM bookings
             WHERE pitch_id = ?
               AND status IN ("pending","waiting_payment","reserved","completed")
               AND slot_start < ?
               AND slot_end > ?
             ORDER BY slot_start ASC',
            'iss',
            [$pitchId, $endAt, $startAt]
        );
    }

    public function blockedRangesForDay(int $pitchId, string $day): array
    {
        [$startAt, $endAt] = $this->dayBounds($day);
        return $this->all(
            'SELECT start_at, end_at, reason
             FROM pitch_blocked_slots
             WHERE pitch_id = ?
               AND start_at < ?
               AND end_at > ?
             ORDER BY start_at ASC',
            'iss',
            [$pitchId, $endAt, $startAt]
        );
    }

    public function search(array $filters): array
    {
        $sql = 'SELECT
                    p.*,
                    u.username AS owner_name,
                    (SELECT photo_url FROM pitch_photos ph WHERE ph.pitch_id = p.id ORDER BY ph.id ASC LIMIT 1) AS cover_photo';

        $types = '';
        $params = [];
        $hasCoordinates = $this->hasValue($filters['user_lat'] ?? null) && $this->hasValue($filters['user_lng'] ?? null);
        if ($hasCoordinates) {
            $sql .= ',
                (6371 * ACOS(
                    COS(RADIANS(?)) * COS(RADIANS(p.lat)) *
                    COS(RADIANS(p.lng) - RADIANS(?)) +
                    SIN(RADIANS(?)) * SIN(RADIANS(p.lat))
                )) AS distance_km';

            $types .= 'ddd';
            $params[] = (float) $filters['user_lat'];
            $params[] = (float) $filters['user_lng'];
            $params[] = (float) $filters['user_lat'];
        } else {
            $sql .= ', NULL AS distance_km';
        }

        $sql .= '
                FROM pitches p
                INNER JOIN users u ON u.id = p.owner_id
                WHERE p.is_active = 1';

        if ($this->hasValue($filters['location'] ?? null)) {
            $location = '%' . trim((string) $filters['location']) . '%';
            $sql .= ' AND (p.city LIKE ? OR p.address LIKE ? OR p.name LIKE ?)';
            $types .= 'sss';
            $params[] = $location;
            $params[] = $location;
            $params[] = $location;
        }

        $sql .= ' ORDER BY p.id DESC';
        $rows = $this->all($sql, $types, $params);

        if ($hasCoordinates && $this->hasValue($filters['max_distance'] ?? null) && is_numeric((string) $filters['max_distance'])) {
            $maxDistance = (float) $filters['max_distance'];
            $rows = array_values(array_filter($rows, static function (array $row) use ($maxDistance): bool {
                if (!isset($row['distance_km'])) {
                    return false;
                }
                return (float) $row['distance_km'] <= $maxDistance;
            }));
        }

        return $rows;
    }

    public function isSlotAvailableForPitch(array $pitch, string $slotStart, string $slotEnd): bool
    {
        if (($pitch['status'] ?? 'available') !== 'available') {
            return false;
        }

        $slotStartTs = strtotime($slotStart);
        $slotEndTs = strtotime($slotEnd);
        if ($slotStartTs === false || $slotEndTs === false || $slotStartTs >= $slotEndTs) {
            return false;
        }

        $slotStartTime = date('H:i:s', $slotStartTs);
        $slotEndTime = date('H:i:s', $slotEndTs);
        if ($slotStartTime < (string) $pitch['open_time'] || $slotEndTime > (string) $pitch['close_time']) {
            return false;
        }

        if ($this->hasBlockedSlot((int) $pitch['id'], $slotStart, $slotEnd)) {
            return false;
        }

        if ($this->hasReservedBooking((int) $pitch['id'], $slotStart, $slotEnd)) {
            return false;
        }

        return true;
    }

    private function hasBlockedSlot(int $pitchId, string $slotStart, string $slotEnd): bool
    {
        return $this->one(
            'SELECT id
             FROM pitch_blocked_slots
             WHERE pitch_id = ?
               AND start_at < ?
               AND end_at > ?
             LIMIT 1',
            'iss',
            [$pitchId, $slotEnd, $slotStart]
        ) !== null;
    }

    private function hasReservedBooking(int $pitchId, string $slotStart, string $slotEnd): bool
    {
        return $this->one(
            'SELECT id
             FROM bookings
             WHERE pitch_id = ?
               AND status IN ("pending","waiting_payment","reserved","completed")
               AND slot_start < ?
               AND slot_end > ?
             LIMIT 1',
            'iss',
            [$pitchId, $slotEnd, $slotStart]
        ) !== null;
    }

    private function hasValue(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    public function adminPitches(): array
    {
        return $this->all(
            'SELECT
                p.*,
                u.username AS owner_name,
                (SELECT photo_url FROM pitch_photos ph WHERE ph.pitch_id = p.id ORDER BY ph.id ASC LIMIT 1) AS cover_photo,
                (SELECT COUNT(*) FROM pitch_photos ph2 WHERE ph2.pitch_id = p.id) AS photos_count,
                (SELECT COUNT(*) FROM pitch_blocked_slots bs WHERE bs.pitch_id = p.id) AS blocks_count
             FROM pitches p
             INNER JOIN users u ON u.id = p.owner_id
             ORDER BY p.id DESC'
        );
    }

    public function findForAdmin(int $pitchId): ?array
    {
        return $this->one(
            'SELECT p.*, u.username AS owner_name
             FROM pitches p
             INNER JOIN users u ON u.id = p.owner_id
             WHERE p.id = ?
             LIMIT 1',
            'i',
            [$pitchId]
        );
    }

    private function dayBounds(string $day): array
    {
        $startAt = $day . ' 00:00:00';
        $endAt = date('Y-m-d H:i:s', strtotime($startAt . ' +1 day'));
        return [$startAt, $endAt];
    }

    public function create(array $data): int
    {
        return $this->insert(
            'INSERT INTO pitches
             (owner_id, name, city, address, lat, lng, open_time, close_time, price_per_player, team_size, status, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())',
            'isssddssdis',
            [
                $data['owner_id'],
                $data['name'],
                $data['city'],
                $data['address'],
                $data['lat'],
                $data['lng'],
                $data['open_time'],
                $data['close_time'],
                $data['price_per_player'],
                $data['team_size'],
                $data['status'],
            ]
        );
    }

    public function update(int $pitchId, array $data): void
    {
        $this->run(
            'UPDATE pitches
             SET name = ?, city = ?, address = ?, lat = ?, lng = ?, open_time = ?, close_time = ?, price_per_player = ?, status = ?, is_active = ?
             WHERE id = ?',
            'sssddssdsii',
            [
                $data['name'],
                $data['city'],
                $data['address'],
                $data['lat'],
                $data['lng'],
                $data['open_time'],
                $data['close_time'],
                $data['price_per_player'],
                $data['status'],
                $data['is_active'],
                $pitchId,
            ]
        );
    }

    public function photosByPitch(int $pitchId): array
    {
        return $this->all('SELECT * FROM pitch_photos WHERE pitch_id = ? ORDER BY id DESC', 'i', [$pitchId]);
    }

    public function addPhoto(int $pitchId, string $photoUrl): void
    {
        $this->run('INSERT INTO pitch_photos (pitch_id, photo_url) VALUES (?, ?)', 'is', [$pitchId, $photoUrl]);
    }

    public function deletePhoto(int $photoId, int $pitchId): void
    {
        $this->run('DELETE FROM pitch_photos WHERE id = ? AND pitch_id = ?', 'ii', [$photoId, $pitchId]);
    }

    public function blockedSlotsByPitch(int $pitchId): array
    {
        return $this->all('SELECT * FROM pitch_blocked_slots WHERE pitch_id = ? ORDER BY start_at DESC', 'i', [$pitchId]);
    }

    public function addBlockedSlot(int $pitchId, string $startAt, string $endAt, string $reason): void
    {
        $this->run(
            'INSERT INTO pitch_blocked_slots (pitch_id, start_at, end_at, reason, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            'isss',
            [$pitchId, $startAt, $endAt, $reason]
        );
    }

    public function deleteBlockedSlot(int $slotId, int $pitchId): void
    {
        $this->run('DELETE FROM pitch_blocked_slots WHERE id = ? AND pitch_id = ?', 'ii', [$slotId, $pitchId]);
    }
}

