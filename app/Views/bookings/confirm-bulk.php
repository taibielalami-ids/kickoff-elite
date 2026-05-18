<div class="row justify-content-center">
    <div class="col-lg-10">
        <section class="glass-panel p-4 p-md-5">
            <h1 class="h3 mb-2">Confirm Selected Slots</h1>
            <p class="text-light-emphasis mb-4">
                Your selected slots are locked for a short time. Confirm now to finalize all bookings.
            </p>

            <?php if (!empty($fromPitchId)): ?>
                <div class="mb-3">
                    <a href="<?= route_path('/pitches/profile?id=' . (int) $fromPitchId . '&day=' . urlencode((string) $fromDay)) ?>" class="btn btn-sm btn-outline-light">
                        Back to Pitch Profile
                    </a>
                </div>
            <?php endif; ?>

            <div class="table-responsive mb-4">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                    <tr>
                        <th>Pitch</th>
                        <th>Slot Start</th>
                        <th>Slot End</th>
                        <th>Lock Expires</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($locks as $lock): ?>
                        <tr>
                            <td><?= e($lock['pitch_name']) ?> <span class="small text-light-emphasis">(<?= e($lock['city']) ?>)</span></td>
                            <td><?= e($lock['slot_start']) ?></td>
                            <td><?= e($lock['slot_end']) ?></td>
                            <td><?= e($lock['expires_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form action="<?= route_path('/bookings/confirm-bulk') ?>" method="post" class="row g-3">
                <?= Csrf::inputField() ?>
                <div class="col-md-6">
                    <label class="form-label">Payment Method For All Slots</label>
                    <select name="payment_mode" class="form-select">
                        <option value="wallet">Wallet (500 DH per slot)</option>
                        <option value="tickets">10 Tickets per slot</option>
                    </select>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                    <button class="btn btn-warning btn-lg px-4">Confirm All Slots</button>
                    <a href="<?= route_path('/bookings') ?>" class="btn btn-outline-light btn-lg px-4">My Bookings</a>
                </div>
            </form>
        </section>
    </div>
</div>
