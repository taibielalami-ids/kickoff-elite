<section class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Manage Pitch: <?= e($pitch['name']) ?></h1>
        <p class="text-light-emphasis mb-0"><?= e($pitch['city']) ?> - <?= e($pitch['address']) ?></p>
    </div>
    <a href="<?= route_path('/admin/pitches') ?>" class="btn btn-sm btn-outline-light">Back to My Pitches</a>
</section>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="glass-panel p-4 mb-4">
            <h2 class="h5 mb-3">Pitch Details</h2>
            <form action="<?= route_path('/admin/pitches/update') ?>" method="post" class="row g-3">
                <?= Csrf::inputField() ?>
                <input type="hidden" name="pitch_id" value="<?= (int) $pitch['id'] ?>">

                <div class="col-md-6">
                    <label class="form-label">Pitch Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($pitch['name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?= e($pitch['city']) ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= e($pitch['address']) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="lat" class="form-control" value="<?= e((string) $pitch['lat']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="lng" class="form-control" value="<?= e((string) $pitch['lng']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Open Time</label>
                    <input type="time" name="open_time" class="form-control" value="<?= e(substr((string) $pitch['open_time'], 0, 5)) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Close Time</label>
                    <input type="time" name="close_time" class="form-control" value="<?= e(substr((string) $pitch['close_time'], 0, 5)) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Price / Player (DH)</label>
                    <input type="number" step="0.01" name="price_per_player" class="form-control" value="<?= e((string) $pitch['price_per_player']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <?php $statuses = ['available', 'reserved', 'maintenance', 'lights_off', 'weather_closed']; ?>
                    <select name="status" class="form-select">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= $pitch['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= ((int) $pitch['is_active'] === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Pitch Active</label>
                    </div>
                </div>

                <div class="col-12 d-grid">
                    <button class="btn btn-warning">Save Changes</button>
                </div>
            </form>
        </section>

        <section class="glass-panel p-4">
            <h2 class="h5 mb-3">Blocked Slots</h2>
            <form action="<?= route_path('/admin/pitches/blocks/add') ?>" method="post" class="row g-3 mb-4">
                <?= Csrf::inputField() ?>
                <input type="hidden" name="pitch_id" value="<?= (int) $pitch['id'] ?>">
                <div class="col-md-4">
                    <label class="form-label">Start</label>
                    <input type="datetime-local" name="start_at" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">End</label>
                    <input type="datetime-local" name="end_at" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" class="form-control" placeholder="maintenance / event">
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-outline-light">Add Blocked Slot</button>
                </div>
            </form>

            <?php if (empty($blockedSlots)): ?>
                <p class="small text-light-emphasis mb-0">No blocked slots yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-striped align-middle">
                        <thead>
                        <tr>
                            <th>Start</th>
                            <th>End</th>
                            <th>Reason</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($blockedSlots as $slot): ?>
                            <tr>
                                <td><?= e($slot['start_at']) ?></td>
                                <td><?= e($slot['end_at']) ?></td>
                                <td><?= e($slot['reason'] ?: '-') ?></td>
                                <td class="text-end">
                                    <form action="<?= route_path('/admin/pitches/blocks/delete') ?>" method="post" class="d-inline">
                                        <?= Csrf::inputField() ?>
                                        <input type="hidden" name="pitch_id" value="<?= (int) $pitch['id'] ?>">
                                        <input type="hidden" name="slot_id" value="<?= (int) $slot['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="glass-panel p-4">
            <h2 class="h5 mb-3">Pitch Photos</h2>
            <form action="<?= route_path('/admin/pitches/photos/add') ?>" method="post" enctype="multipart/form-data" class="row g-2 mb-4">
                <?= Csrf::inputField() ?>
                <input type="hidden" name="pitch_id" value="<?= (int) $pitch['id'] ?>">
                <div class="col-12">
                    <label class="form-label">Upload Photo</label>
                    <input type="file" name="photo_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" required>
                    <p class="small text-light-emphasis mt-1 mb-0">Allowed: JPG, PNG, WEBP, GIF - max 5 MB.</p>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-outline-light">Add Photo</button>
                </div>
            </form>

            <?php if (empty($photos)): ?>
                <p class="small text-light-emphasis mb-0">No photos added yet.</p>
            <?php else: ?>
                <div class="owner-photo-grid">
                    <?php foreach ($photos as $photo): ?>
                        <div class="owner-photo-item">
                            <img src="<?= e($photo['photo_url']) ?>" alt="Pitch photo">
                            <form action="<?= route_path('/admin/pitches/photos/delete') ?>" method="post" class="mt-2">
                                <?= Csrf::inputField() ?>
                                <input type="hidden" name="pitch_id" value="<?= (int) $pitch['id'] ?>">
                                <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger w-100">Remove</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

