<section class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e($pitch['name']) ?></h1>
        <p class="text-light-emphasis mb-0"><?= e($pitch['city']) ?> - <?= e($pitch['address']) ?></p>
    </div>
    <a href="<?= route_path('/pitches') ?>" class="btn btn-outline-light rounded-pill px-4">Back to Pitches</a>
</section>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="glass-panel p-3 p-md-4 mb-4">
            <h2 class="h5 mb-3">Pitch Location</h2>
            <div id="pitchProfileMap"></div>
        </section>

        <section class="glass-panel p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">Reservation Calendar</h2>
                <span class="badge text-bg-warning px-3 py-2">50 DH / player - 500 DH full slot</span>
            </div>

            <form method="get" action="<?= route_path('/pitches/profile') ?>" class="row g-2 mb-3">
                <input type="hidden" name="id" value="<?= (int) $pitch['id'] ?>">
                <div class="col-md-5">
                    <label class="form-label">Pick Day</label>
                    <input type="date" name="day" class="form-control" value="<?= e($selectedDay) ?>">
                </div>
                <div class="col-md-3 d-grid align-items-end">
                    <button class="btn btn-outline-light">Load Schedule</button>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($quickDays as $quickDay): ?>
                    <a href="<?= route_path('/pitches/profile?id=' . (int) $pitch['id'] . '&day=' . urlencode($quickDay['value'])) ?>"
                       class="btn btn-sm <?= $quickDay['is_selected'] ? 'btn-warning' : 'btn-outline-light' ?>">
                        <?= e($quickDay['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($dailySlots)): ?>
                <p class="mb-0 text-light-emphasis">No one-hour slots available for this day.</p>
            <?php else: ?>
                <form method="post" action="<?= route_path('/bookings/lock-bulk') ?>">
                    <?= Csrf::inputField() ?>
                    <input type="hidden" name="pitch_id" value="<?= (int) $pitch['id'] ?>">
                    <input type="hidden" name="day" value="<?= e($selectedDay) ?>">

                    <div class="table-responsive">
                        <table class="table table-dark table-striped align-middle">
                            <thead>
                            <tr>
                                <th style="width: 90px;">Pick</th>
                                <th>Hour</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($dailySlots as $slot): ?>
                                <?php
                                $status = $slot['status'];
                                $badgeClass = 'text-bg-success';
                                if ($status === 'reserved') {
                                    $badgeClass = 'text-bg-danger';
                                } elseif ($status === 'blocked') {
                                    $badgeClass = 'text-bg-secondary';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($status === 'available'): ?>
                                            <input class="form-check-input" type="checkbox" name="hours[]" value="<?= e($slot['hour']) ?>">
                                        <?php else: ?>
                                            <span class="text-light-emphasis">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= e($slot['from']) ?> - <?= e($slot['to']) ?></strong></td>
                                    <td><span class="badge <?= e($badgeClass) ?>"><?= e($status) ?></span></td>
                                    <td class="small text-light-emphasis">
                                        <?php if ($status === 'available'): ?>
                                            Open for reservation
                                        <?php elseif ($status === 'reserved'): ?>
                                            Already reserved (<?= e($slot['reason']) ?>)
                                        <?php else: ?>
                                            Blocked by owner<?= $slot['reason'] !== '' ? ': ' . e($slot['reason']) : '' ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button class="btn btn-warning">Reserve Selected Slot(s)</button>
                        <span class="small text-light-emphasis align-self-center">Selected slots are locked first, then you confirm payment.</span>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="glass-panel p-4 mb-4">
            <h2 class="h5 mb-3">Pitch Details</h2>
            <p class="mb-2"><strong>Owner:</strong> <?= e($pitch['owner_name']) ?></p>
            <p class="mb-2"><strong>Open Hours:</strong> <?= e(substr((string) $pitch['open_time'], 0, 5)) ?> - <?= e(substr((string) $pitch['close_time'], 0, 5)) ?></p>
            <p class="mb-0"><strong>Format:</strong> 5v5 (10 players total)</p>
        </section>

        <section class="glass-panel p-4">
            <h2 class="h5 mb-3">Pitch Photos</h2>
            <?php if (empty($photos)): ?>
                <p class="small text-light-emphasis mb-0">No photos available.</p>
            <?php else: ?>
                <div class="owner-photo-grid">
                    <?php foreach (array_slice($photos, 0, 8) as $photo): ?>
                        <div class="owner-photo-item">
                            <img src="<?= e($photo['photo_url']) ?>" alt="<?= e($pitch['name']) ?> photo">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php if (trim($mapboxToken) !== ''): ?>
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.js"></script>
<?php else: ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
<?php endif; ?>

<script>
(() => {
    const mapData = <?= $mapDataJson ?: '[]' ?>;
    if (!Array.isArray(mapData) || mapData.length === 0) {
        return;
    }

    const pitch = mapData[0];
    const center = [pitch.lng, pitch.lat];
    const token = <?= json_encode($mapboxToken) ?>;

    if (token && typeof mapboxgl !== 'undefined') {
        mapboxgl.accessToken = token;
        const map = new mapboxgl.Map({
            container: 'pitchProfileMap',
            style: 'mapbox://styles/mapbox/standard',
            center: center,
            zoom: 15
        });
        map.addControl(new mapboxgl.NavigationControl(), 'top-right');
        new mapboxgl.Marker({ color: '#f7c90b' })
            .setLngLat(center)
            .setPopup(new mapboxgl.Popup({ offset: 20 }).setHTML(
                `<strong>${pitch.name}</strong><br>${pitch.city}<br>${pitch.address}`
            ))
            .addTo(map);
        return;
    }

    if (typeof L !== 'undefined') {
        const map = L.map('pitchProfileMap').setView([pitch.lat, pitch.lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        L.marker([pitch.lat, pitch.lng]).addTo(map).bindPopup(
            `<strong>${pitch.name}</strong><br>${pitch.city}<br>${pitch.address}`
        );
    }
})();
</script>
