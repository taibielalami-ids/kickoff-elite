<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="glass-panel p-4 p-md-5">
            <h1 class="h4 mb-3">Verify Email</h1>
            <p class="small text-light-emphasis mb-4">Enter the 6-digit code sent to your email.</p>
            <form action="<?= route_path('/auth/verify-email') ?>" method="post" class="row g-3">
                <?= Csrf::inputField() ?>
                <input type="hidden" name="user_id" value="<?= (int) ($userId ?? 0) ?>">
                <div class="col-12">
                    <label class="form-label">Verification Code</label>
                    <input type="text" name="code" class="form-control text-uppercase" maxlength="6" required>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-warning">Verify Email</button>
                </div>
            </form>
            <form action="<?= route_path('/auth/verify-email/resend') ?>" method="post" class="mt-3">
                <?= Csrf::inputField() ?>
                <input type="hidden" name="user_id" value="<?= (int) ($userId ?? 0) ?>">
                <div class="mb-2">
                    <label class="form-label small">Optional email (if user id is missing)</label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com">
                </div>
                <button class="btn btn-outline-light w-100">Resend Code</button>
            </form>
        </div>
    </div>
</div>
