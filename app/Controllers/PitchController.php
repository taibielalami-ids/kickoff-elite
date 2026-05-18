<?php

class PitchController extends Controller
{
    private Pitch $pitches;

    public function __construct()
    {
        $this->pitches = new Pitch();
    }

    public function index(): void
    {
        $this->requireAuth();

        $filters = [
            'day' => trim($_GET['day'] ?? ''),
            'hour' => trim($_GET['hour'] ?? ''),
            'location' => trim($_GET['location'] ?? ''),
            'max_distance' => trim($_GET['max_distance'] ?? ''),
            'availability' => trim($_GET['availability'] ?? 'all'),
            'user_lat' => trim($_GET['user_lat'] ?? ''),
            'user_lng' => trim($_GET['user_lng'] ?? ''),
        ];

        if (!in_array($filters['availability'], ['all', 'available', 'reserved'], true)) {
            $filters['availability'] = 'all';
        }

        $slotStart = null;
        $slotEnd = null;
        if ($this->hasDateAndHour($filters['day'], $filters['hour'])) {
            $slotStart = date('Y-m-d H:i:s', strtotime($filters['day'] . ' ' . $filters['hour']));
            $slotEnd = date('Y-m-d H:i:s', strtotime($slotStart . ' +1 hour'));
        }

        $pitches = $this->pitches->search($filters);
        $enhanced = [];
        foreach ($pitches as $pitch) {
            $isAvailable = true;
            if ($slotStart !== null && $slotEnd !== null) {
                $isAvailable = $this->pitches->isSlotAvailableForPitch($pitch, $slotStart, $slotEnd);
            } else {
                $isAvailable = ($pitch['status'] ?? 'available') === 'available';
            }

            $pitch['slot_state'] = $isAvailable ? 'available' : 'reserved';
            $pitch['distance_label'] = isset($pitch['distance_km']) && $pitch['distance_km'] !== null
                ? number_format((float) $pitch['distance_km'], 1) . ' km'
                : 'N/A';

            if ($filters['availability'] === 'available' && !$isAvailable) {
                continue;
            }
            if ($filters['availability'] === 'reserved' && $isAvailable) {
                continue;
            }

            $enhanced[] = $pitch;
        }

        $mapData = array_map(static function (array $pitch): array {
            return [
                'id' => (int) $pitch['id'],
                'name' => $pitch['name'],
                'city' => $pitch['city'],
                'address' => $pitch['address'],
                'lat' => (float) $pitch['lat'],
                'lng' => (float) $pitch['lng'],
                'price' => (float) $pitch['price_per_player'],
                'status' => $pitch['slot_state'] ?? $pitch['status'],
                'distance_label' => $pitch['distance_label'] ?? 'N/A',
            ];
        }, $enhanced);

        $cityOptions = $this->pitches->cityOptions();

        $this->view('pitches/index', [
            'pitches' => $enhanced,
            'cityOptions' => $cityOptions,
            'filters' => $filters,
            'slotStart' => $slotStart,
            'filtersQuery' => http_build_query($filters),
            'mapDataJson' => json_encode($mapData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'mapboxToken' => (string) config('app.mapbox_token', ''),
        ]);
    }

    public function profile(): void
    {
        $this->requireAuth();

        $pitchId = (int) ($_GET['id'] ?? 0);
        if ($pitchId <= 0) {
            $this->redirectWith('/pitches', 'danger', 'Invalid pitch profile.');
        }

        $pitch = $this->pitches->findById($pitchId);
        if (!$pitch) {
            $this->redirectWith('/pitches', 'danger', 'Pitch not found or inactive.');
        }

        $day = trim($_GET['day'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) !== 1) {
            $day = date('Y-m-d');
        }

        $bookedRanges = $this->pitches->bookedRangesForDay($pitchId, $day);
        $blockedRanges = $this->pitches->blockedRangesForDay($pitchId, $day);
        $dailySlots = $this->buildDailySlots($pitch, $day, $bookedRanges, $blockedRanges);
        $photos = $this->pitches->photosByPitch($pitchId);

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($day . ' +' . $i . ' day'));
            $days[] = [
                'value' => $date,
                'label' => date('D d M', strtotime($date)),
                'is_selected' => $date === $day,
            ];
        }

        $this->view('pitches/profile', [
            'pitch' => $pitch,
            'photos' => $photos,
            'selectedDay' => $day,
            'quickDays' => $days,
            'dailySlots' => $dailySlots,
            'mapDataJson' => json_encode([
                [
                    'id' => (int) $pitch['id'],
                    'name' => $pitch['name'],
                    'city' => $pitch['city'],
                    'address' => $pitch['address'],
                    'lat' => (float) $pitch['lat'],
                    'lng' => (float) $pitch['lng'],
                    'status' => $pitch['status'],
                    'price' => (float) $pitch['price_per_player'],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'mapboxToken' => (string) config('app.mapbox_token', ''),
        ]);
    }

    private function buildDailySlots(array $pitch, string $day, array $bookedRanges, array $blockedRanges): array
    {
        $slots = [];
        $openTs = strtotime($day . ' ' . (string) $pitch['open_time']);
        $closeTs = strtotime($day . ' ' . (string) $pitch['close_time']);
        if ($openTs === false || $closeTs === false || $closeTs <= $openTs) {
            return $slots;
        }

        for ($cursor = $openTs; $cursor < $closeTs; $cursor += 3600) {
            $slotStartTs = $cursor;
            $slotEndTs = $cursor + 3600;
            if ($slotEndTs > $closeTs) {
                continue;
            }

            $slotStart = date('Y-m-d H:i:s', $slotStartTs);
            $slotEnd = date('Y-m-d H:i:s', $slotEndTs);
            $status = 'available';
            $reason = '';

            foreach ($blockedRanges as $blocked) {
                $blockedStart = strtotime((string) ($blocked['start_at'] ?? ''));
                $blockedEnd = strtotime((string) ($blocked['end_at'] ?? ''));
                if ($blockedStart === false || $blockedEnd === false) {
                    continue;
                }
                if ($slotStartTs < $blockedEnd && $slotEndTs > $blockedStart) {
                    $status = 'blocked';
                    $reason = trim((string) ($blocked['reason'] ?? ''));
                    break;
                }
            }

            if ($status === 'available') {
                foreach ($bookedRanges as $booked) {
                    $bookedStart = strtotime((string) ($booked['slot_start'] ?? ''));
                    $bookedEnd = strtotime((string) ($booked['slot_end'] ?? ''));
                    if ($bookedStart === false || $bookedEnd === false) {
                        continue;
                    }
                    if ($slotStartTs < $bookedEnd && $slotEndTs > $bookedStart) {
                        $status = 'reserved';
                        $reason = (string) ($booked['status'] ?? 'reserved');
                        break;
                    }
                }
            }

            $slots[] = [
                'hour' => date('H:i', $slotStartTs),
                'from' => date('H:i', $slotStartTs),
                'to' => date('H:i', $slotEndTs),
                'slot_start' => $slotStart,
                'slot_end' => $slotEnd,
                'status' => $status,
                'reason' => $reason,
            ];
        }

        return $slots;
    }

    private function hasDateAndHour(string $day, string $hour): bool
    {
        if ($day === '' || $hour === '') {
            return false;
        }

        $dayOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) === 1;
        $hourOk = preg_match('/^\d{2}:\d{2}$/', $hour) === 1;
        return $dayOk && $hourOk;
    }
}
