<section class="login-shell">
    <div class="login-panel">
        <div class="login-left d-none d-lg-flex">
            <div class="login-left-top">
                <span class="login-brand-badge">KickOff Elite</span>
                <h2>Welcome back to your 5v5 booking platform.</h2>
                <p>Login quickly, verify with your 6-digit code, and continue to dashboard, bookings, team ads, and backup calls.</p>
            </div>

            <div id="loginScene" class="login-scene" aria-hidden="true">
                <div id="loginCharacterPurple" class="char char-purple" data-login-character>
                    <div class="char-eyes">
                        <span class="eye"><span class="pupil"></span></span>
                        <span class="eye"><span class="pupil"></span></span>
                    </div>
                </div>

                <div id="loginCharacterBlack" class="char char-black" data-login-character>
                    <div class="char-eyes">
                        <span class="eye"><span class="pupil"></span></span>
                        <span class="eye"><span class="pupil"></span></span>
                    </div>
                </div>

                <div id="loginCharacterOrange" class="char char-orange" data-login-character>
                    <div class="char-eyes">
                        <span class="dot-eye"></span>
                        <span class="dot-eye"></span>
                    </div>
                </div>

                <div id="loginCharacterYellow" class="char char-yellow" data-login-character>
                    <div class="char-eyes">
                        <span class="dot-eye"></span>
                        <span class="dot-eye"></span>
                    </div>
                    <span class="char-mouth"></span>
                </div>
            </div>

        </div>

        <div class="login-right">
            <div class="glass-panel login-form-wrap p-4 p-md-5">
                <h1 class="h3 mb-2">Login</h1>
                <p class="text-light-emphasis mb-4 small">Enter your account details to continue.</p>

                <form action="<?= route_path('/auth/login') ?>" method="post" class="row g-3">
                    <?= Csrf::inputField() ?>
                    <div class="col-12">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control login-input" value="<?= old('username') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Password</label>
                        <div class="position-relative">
                            <input id="loginPassword" type="password" name="password" class="form-control login-input pe-5" required>
                            <button id="loginTogglePassword" class="btn btn-sm btn-link login-password-toggle" type="button">Show</button>
                        </div>
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn-warning btn-lg">Continue</button>
                    </div>
                </form>
                <div class="d-flex justify-content-end mt-3 small">
                    <span>No account yet? <a class="link-light" href="<?= route_path('/auth/register') ?>">Register</a></span>
                </div>
            </div>
        </div>
    </div>
</section>
