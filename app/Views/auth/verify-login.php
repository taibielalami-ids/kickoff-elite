<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="glass-panel p-4 p-md-5">
            <h1 class="h4 mb-3">Two-Step Verification</h1>
            <p class="small text-light-emphasis mb-4">Enter your 6-digit login code to finish sign-in.</p>
            <form action="<?= route_path('/auth/verify-login') ?>" method="post" class="row g-3">
                <?= Csrf::inputField() ?>
                <div class="col-12">
                    <label class="form-label">Login Code</label>
                    <input type="text" name="code" class="form-control text-uppercase" maxlength="6" required>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-warning">Verify & Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

