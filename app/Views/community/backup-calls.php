<section class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Backup Calls</h1>
        <p class="text-light-emphasis mb-0">Call a replacement player quickly when someone is missing.</p>
    </div>
</section>

<section class="glass-panel p-4 mb-4">
    <h2 class="h5 mb-3">Create Backup Call</h2>
    <?php if (empty($myBookings)): ?>
        <div class="alert alert-info mb-3">
            You have no upcoming eligible bookings yet. Create a booking first, then come back here to publish a backup call.
            <a href="<?= route_path('/pitches') ?>" class="alert-link">Book from Pitches</a>.
        </div>
    <?php endif; ?>
    <form method="post" action="<?= route_path('/backup-calls/create') ?>" class="row g-2">
        <?= Csrf::inputField() ?>
        <div class="col-md-4">
            <label class="form-label small">From Your Booking</label>
            <select class="form-select" name="booking_id" required>
                <option value="">Choose booking</option>
                <?php foreach ($myBookings as $booking): ?>
                    <option value="<?= (int) $booking['id'] ?>">
                        <?= e($booking['pitch_name']) ?> - <?= e((string) $booking['slot_start']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Needed Role</label>
            <select class="form-select" name="needed_role">
                <option value="any" selected>Any</option>
                <option value="goalkeeper">Goalkeeper</option>
                <option value="defender">Defender</option>
                <option value="midfielder">Midfielder</option>
                <option value="attacker">Attacker</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Expires (min)</label>
            <input type="number" class="form-control" name="expires_minutes" min="10" max="360" value="90">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Reward (DH)</label>
            <input type="number" class="form-control" name="reward_amount" min="0" step="10" value="0">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_free" id="isFreeCall" value="1">
                <label class="form-check-label" for="isFreeCall">Free Spot</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label small">Message</label>
            <input type="text" class="form-control" name="message" maxlength="255" placeholder="Example: Need one defender, match starts soon.">
        </div>
        <div class="col-12 d-grid d-md-flex justify-content-md-end">
            <button class="btn btn-warning rounded-pill px-4" <?= empty($myBookings) ? 'disabled' : '' ?>>Publish Backup Call</button>
        </div>
    </form>
</section>

<section class="glass-panel p-4 mb-4">
    <form method="get" action="<?= route_path('/backup-calls') ?>" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small">City filter</label>
            <select class="form-select" name="city">
                <option value="">All cities</option>
                <?php foreach ($cityOptions as $city): ?>
                    <option value="<?= e($city) ?>" <?= $selectedCity === $city ? 'selected' : '' ?>><?= e($city) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-light">Filter</button>
        </div>
    </form>
</section>

<section class="row g-3">
    <?php if (empty($calls)): ?>
        <div class="col-12">
            <div class="glass-panel p-4">
                <p class="mb-0">No open backup calls right now.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($calls as $call): ?>
            <?php
            $isOwner = (int) $call['requester_user_id'] === (int) $viewerId;
            $respondedByMe = (int) ($call['responded_by_me'] ?? 0) > 0;
            ?>
            <div class="col-md-6 col-xl-4">
                <article class="glass-panel p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h5 mb-0"><?= e($call['pitch_name']) ?></h2>
                        <span class="badge text-bg-danger">backup</span>
                    </div>
                    <p class="small text-light-emphasis mb-1"><?= e($call['city']) ?> - <?= e($call['address']) ?></p>
                    <p class="small mb-1">By: <strong><?= e($call['requester_username']) ?></strong></p>
                    <p class="small mb-1">Match: <strong><?= e((string) $call['match_start']) ?></strong></p>
                    <p class="small mb-1">Role: <strong><?= e((string) $call['needed_role']) ?></strong></p>
                    <p class="small mb-1">
                        Reward:
                        <?php if ((int) $call['is_free'] === 1): ?>
                            <strong>Free spot</strong>
                        <?php else: ?>
                            <strong><?= e(number_format((float) $call['reward_amount'], 0)) ?> DH</strong>
                        <?php endif; ?>
                    </p>
                    <p class="small mb-1">Expires: <strong><?= e((string) $call['expires_at']) ?></strong></p>
                    <p class="small mb-2">Responses: <strong><?= e((string) ((int) ($call['responses_count'] ?? 0))) ?></strong></p>
                    <?php if (trim((string) ($call['message'] ?? '')) !== ''): ?>
                        <p class="small mb-3"><?= e((string) $call['message']) ?></p>
                    <?php endif; ?>

                    <?php if ($isOwner): ?>
                        <form method="post" action="<?= route_path('/backup-calls/close') ?>">
                            <?= Csrf::inputField() ?>
                            <input type="hidden" name="call_id" value="<?= (int) $call['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Close Call</button>
                        </form>
                    <?php elseif (!$respondedByMe): ?>
                        <form method="post" action="<?= route_path('/backup-calls/respond') ?>" class="row g-2">
                            <?= Csrf::inputField() ?>
                            <input type="hidden" name="call_id" value="<?= (int) $call['id'] ?>">
                            <div class="col-8">
                                <input type="text" class="form-control form-control-sm" name="message" maxlength="140" placeholder="I can join in 15 min">
                            </div>
                            <div class="col-4 d-grid">
                                <button class="btn btn-sm btn-warning">Respond</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">You responded</span>
                    <?php endif; ?>
                </article>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
