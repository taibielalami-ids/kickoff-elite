<div class="row g-4">
    <div class="col-lg-5">
        <section class="glass-panel p-4">
            <h1 class="h4 mb-3">Booking Code Verification</h1>
            <p class="small text-light-emphasis">Enter the unique booking code shown by the player.</p>

            <form method="post" action="<?= route_path('/admin/verify-code') ?>" class="row g-3">
                <?= Csrf::inputField() ?>
                <div class="col-12">
                    <label class="form-label">Booking Code</label>
                    <input type="text" name="code" class="form-control text-uppercase" placeholder="EX: KF9A72" required>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-warning">Check Code</button>
                </div>
            </form>
        </section>
    </div>

    <div class="col-lg-7">
        <section class="glass-panel p-4 h-100">
            <h2 class="h5 mb-3">Verification Result</h2>
            <?php if (empty($booking)): ?>
                <p class="mb-0 text-light-emphasis">No code checked yet.</p>
            <?php else: ?>
                <dl class="row small mb-4">
                    <dt class="col-sm-4">Pitch</dt>
                    <dd class="col-sm-8"><?= e($booking['pitch_name']) ?></dd>

                    <dt class="col-sm-4">Captain</dt>
                    <dd class="col-sm-8"><?= e($booking['captain_name']) ?></dd>

                    <dt class="col-sm-4">Kickoff</dt>
                    <dd class="col-sm-8"><?= e($booking['slot_start']) ?></dd>

                    <dt class="col-sm-4">End Time</dt>
                    <dd class="col-sm-8"><?= e($booking['slot_end']) ?></dd>

                    <dt class="col-sm-4">Booking Status</dt>
                    <dd class="col-sm-8"><span class="badge text-bg-dark"><?= e($booking['booking_status']) ?></span></dd>

                    <dt class="col-sm-4">Code Status</dt>
                    <dd class="col-sm-8"><span class="badge <?= ($booking['code_status'] === 'active') ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($booking['code_status']) ?></span></dd>
                </dl>

                <?php if (($booking['code_status'] ?? '') === 'active'): ?>
                    <form method="post" action="<?= route_path('/admin/verify-code') ?>">
                        <?= Csrf::inputField() ?>
                        <input type="hidden" name="code" value="<?= e($booking['code']) ?>">
                        <input type="hidden" name="action" value="confirm">
                        <button class="btn btn-outline-light">Confirm Check-In</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

