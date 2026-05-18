<div class="row justify-content-center">
    <div class="col-lg-8">
        <section class="glass-panel p-4 p-md-5">
            <h1 class="h3 mb-3">Confirm Booking</h1>
            <p class="text-light-emphasis mb-4">
                This slot is locked for you for a short time. Confirm before it expires.
            </p>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="glass-panel p-3">
                        <h2 class="h6 text-uppercase text-warning mb-2">Pitch</h2>
                        <p class="mb-1"><strong><?= e($lock['pitch_name']) ?></strong></p>
                        <p class="small mb-0 text-light-emphasis"><?= e($lock['city']) ?> - <?= e($lock['address']) ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-panel p-3">
                        <h2 class="h6 text-uppercase text-warning mb-2">Slot</h2>
                        <p class="mb-1"><strong><?= e($lock['slot_start']) ?></strong></p>
                        <p class="small mb-0 text-light-emphasis">to <?= e($lock['slot_end']) ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-panel p-3">
                        <h2 class="h6 text-uppercase text-warning mb-2">Price</h2>
                        <p class="mb-0"><strong>500 DH total</strong> (50 DH x 10 players)</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-panel p-3">
                        <h2 class="h6 text-uppercase text-warning mb-2">Lock Expires</h2>
                        <p class="mb-0"><strong id="lockExpiresAt"><?= e($lock['expires_at']) ?></strong></p>
                    </div>
                </div>
            </div>

            <form action="<?= route_path('/bookings/confirm') ?>" method="post" class="row g-3">
                <?= Csrf::inputField() ?>
                <input type="hidden" name="lock_token" value="<?= e($lock['lock_token']) ?>">

                <div class="col-md-6">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_mode" class="form-select">
                        <option value="wallet">Wallet (500 DH)</option>
                        <option value="tickets">10 Tickets</option>
                    </select>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                    <button class="btn btn-warning btn-lg px-4">Confirm Booking</button>
                    <a href="<?= route_path('/pitches') ?>" class="btn btn-outline-light btn-lg px-4">Back to Search</a>
                </div>
            </form>
        </section>
    </div>
</div>
