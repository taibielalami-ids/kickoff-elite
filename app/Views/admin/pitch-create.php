<div class="row justify-content-center">
    <div class="col-xl-9">
        <section class="glass-panel p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Create New Pitch</h1>
                <a href="<?= route_path('/admin/pitches') ?>" class="btn btn-sm btn-outline-light">Back</a>
            </div>

            <form action="<?= route_path('/admin/pitches/create') ?>" method="post" class="row g-3">
                <?= Csrf::inputField() ?>

                <div class="col-md-6">
                    <label class="form-label">Pitch Name</label>
                    <input type="text" name="name" class="form-control" value="<?= old('name') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?= old('city', 'Sale') ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= old('address') ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="lat" class="form-control" value="<?= old('lat') ?>" placeholder="34.0479000" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="lng" class="form-control" value="<?= old('lng') ?>" placeholder="-6.7984600" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Open Time</label>
                    <input type="time" name="open_time" class="form-control" value="<?= old('open_time', '08:00') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Close Time</label>
                    <input type="time" name="close_time" class="form-control" value="<?= old('close_time', '23:00') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Price / Player (DH)</label>
                    <input type="number" step="0.01" name="price_per_player" class="form-control" value="<?= old('price_per_player', '50') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php $statuses = ['available', 'reserved', 'maintenance', 'lights_off', 'weather_closed']; ?>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= old('status', 'available') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 d-grid mt-4">
                    <button class="btn btn-warning btn-lg">Create Pitch</button>
                </div>
            </form>
        </section>
    </div>
</div>

