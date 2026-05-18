<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="glass-panel p-4 p-md-5">
            <h1 class="h3 mb-4">Create your account</h1>
            <form action="<?= route_path('/auth/register') ?>" method="post" class="row g-3">
                <?= Csrf::inputField() ?>

                <div class="col-md-6">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= old('username') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= old('date_of_birth') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?= old('city') ?>" required>
                </div>
                <?php $oldPlayingRoles = $_SESSION['old']['playing_roles'] ?? []; ?>
                <div class="col-12">
                    <label class="form-label">Preferred Playing Role</label>
                    <div class="glass-panel p-3 role-orbit-wrap">
                        <div id="roleOrbit" class="role-orbit" aria-label="Select preferred playing roles">
                            <div class="role-orbit-core">
                                <span>Roles</span>
                            </div>

                            <button type="button" class="role-node role-node-top <?= in_array('goalkeeper', $oldPlayingRoles, true) ? 'is-selected' : '' ?>" data-role-node="goalkeeper" aria-pressed="<?= in_array('goalkeeper', $oldPlayingRoles, true) ? 'true' : 'false' ?>">
                                Goalkeeper
                            </button>
                            <button type="button" class="role-node role-node-right <?= in_array('attacker', $oldPlayingRoles, true) ? 'is-selected' : '' ?>" data-role-node="attacker" aria-pressed="<?= in_array('attacker', $oldPlayingRoles, true) ? 'true' : 'false' ?>">
                                Attacker
                            </button>
                            <button type="button" class="role-node role-node-bottom <?= in_array('midfielder', $oldPlayingRoles, true) ? 'is-selected' : '' ?>" data-role-node="midfielder" aria-pressed="<?= in_array('midfielder', $oldPlayingRoles, true) ? 'true' : 'false' ?>">
                                Midfielder
                            </button>
                            <button type="button" class="role-node role-node-left <?= in_array('defender', $oldPlayingRoles, true) ? 'is-selected' : '' ?>" data-role-node="defender" aria-pressed="<?= in_array('defender', $oldPlayingRoles, true) ? 'true' : 'false' ?>">
                                Defender
                            </button>
                        </div>

                        <div class="role-checkboxes-hidden">
                            <input id="roleCheckboxGoalkeeper" class="role-checkbox-input" type="checkbox" name="playing_roles[]" value="goalkeeper" <?= in_array('goalkeeper', $oldPlayingRoles, true) ? 'checked' : '' ?>>
                            <input id="roleCheckboxDefender" class="role-checkbox-input" type="checkbox" name="playing_roles[]" value="defender" <?= in_array('defender', $oldPlayingRoles, true) ? 'checked' : '' ?>>
                            <input id="roleCheckboxMidfielder" class="role-checkbox-input" type="checkbox" name="playing_roles[]" value="midfielder" <?= in_array('midfielder', $oldPlayingRoles, true) ? 'checked' : '' ?>>
                            <input id="roleCheckboxAttacker" class="role-checkbox-input" type="checkbox" name="playing_roles[]" value="attacker" <?= in_array('attacker', $oldPlayingRoles, true) ? 'checked' : '' ?>>
                        </div>

                        <noscript>
                            <div class="d-flex flex-wrap gap-3 mt-3">
                                <label class="form-check-label">
                                    <input class="form-check-input me-1" type="checkbox" name="playing_roles[]" value="goalkeeper">
                                    Goalkeeper
                                </label>
                                <label class="form-check-label">
                                    <input class="form-check-input me-1" type="checkbox" name="playing_roles[]" value="defender">
                                    Defender
                                </label>
                                <label class="form-check-label">
                                    <input class="form-check-input me-1" type="checkbox" name="playing_roles[]" value="midfielder">
                                    Midfielder
                                </label>
                                <label class="form-check-label">
                                    <input class="form-check-input me-1" type="checkbox" name="playing_roles[]" value="attacker">
                                    Attacker
                                </label>
                            </div>
                        </noscript>
                    </div>
                </div>

                <div class="col-12 d-grid">
                    <button class="btn btn-warning btn-lg">Create Account</button>
                </div>
            </form>
            <p class="small mt-3 mb-0">Already have an account? <a class="link-light" href="<?= route_path('/auth/login') ?>">Login</a></p>
        </div>
    </div>
</div>
