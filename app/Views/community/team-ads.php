<?php
$membersByAd = $membersByAd ?? [];
$positionSlots = $positionSlots ?? ['gk', 'lb', 'lcb', 'rcb', 'rb', 'lcm', 'rcm', 'cam', 'lw', 'rw'];
$slotMeta = [
    'gk' => ['label' => 'GK', 'x' => 4, 'y' => 50, 'side' => 'blue'],
    'lb' => ['label' => 'Top', 'x' => 27, 'y' => 18, 'side' => 'blue'],
    'lcb' => ['label' => 'Mid', 'x' => 33, 'y' => 36, 'side' => 'blue'],
    'rcb' => ['label' => 'Mid', 'x' => 33, 'y' => 55, 'side' => 'blue'],
    'rb' => ['label' => 'Bot', 'x' => 27, 'y' => 82, 'side' => 'blue'],
    'lcm' => ['label' => 'GK', 'x' => 96, 'y' => 50, 'side' => 'red'],
    'rcm' => ['label' => 'Top', 'x' => 73, 'y' => 18, 'side' => 'red'],
    'cam' => ['label' => 'Mid', 'x' => 67, 'y' => 36, 'side' => 'red'],
    'lw' => ['label' => 'Mid', 'x' => 67, 'y' => 55, 'side' => 'red'],
    'rw' => ['label' => 'Bot', 'x' => 73, 'y' => 82, 'side' => 'red'],
];
$viewerName = (string) (Auth::user()['username'] ?? 'You');
$viewerAvatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($viewerName) . '&background=1b5bd8&color=ffffff&bold=true&size=84';
?>

<section class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Team Ads</h1>
        <p class="text-light-emphasis mb-0">Join a team and pick your position directly on the field.</p>
    </div>
</section>

<section class="glass-panel p-4 mb-4">
    <h2 class="h5 mb-3">Create Team Ad</h2>
    <form method="post" action="<?= route_path('/team-ads/create') ?>" class="row g-2" data-team-create-form>
        <?= Csrf::inputField() ?>
        <div class="col-md-3">
            <label class="form-label small">Pitch</label>
            <select class="form-select" name="pitch_id" required>
                <option value="">Choose pitch</option>
                <?php foreach ($pitches as $pitch): ?>
                    <option value="<?= (int) $pitch['id'] ?>"><?= e($pitch['name']) ?> - <?= e($pitch['city']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Day</label>
            <input type="date" name="day" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Hour</label>
            <input type="time" name="hour" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Team Size</label>
            <input type="number" name="team_size_target" class="form-control" min="2" max="10" value="10" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Your Role</label>
            <select class="form-select" name="role_name">
                <option value="goalkeeper">Goalkeeper</option>
                <option value="defender">Defender</option>
                <option value="midfielder" selected>Midfielder</option>
                <option value="attacker">Attacker</option>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label small">Notes</label>
            <input type="text" name="notes" class="form-control" maxlength="255" placeholder="Example: Need defenders, match starts on time.">
        </div>
        <div class="col-12 order-first">
            <input type="hidden" name="position_slot" value="" data-selected-slot-input>
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <label class="form-label small mb-0">Pick your position on the field</label>
                <span class="small text-light-emphasis" data-selected-slot-label>No slot selected (auto assign)</span>
            </div>
            <div class="team-field team-field-create mb-2"
                 data-position-picker
                 data-viewer-avatar="<?= e($viewerAvatarUrl) ?>"
                 data-viewer-name="<?= e($viewerName) ?>">
                <div class="team-field-half-line"></div>
                <div class="team-field-line team-field-center-circle"></div>
                <div class="team-field-center-dot"></div>
                <div class="team-field-line team-field-left-box"></div>
                <div class="team-field-line team-field-right-box"></div>
                <div class="team-field-line team-field-left-small-box"></div>
                <div class="team-field-line team-field-right-small-box"></div>
                <div class="team-field-goal team-field-left-goal"></div>
                <div class="team-field-goal team-field-right-goal"></div>
                <div class="team-field-penalty-dot team-field-left-penalty"></div>
                <div class="team-field-penalty-dot team-field-right-penalty"></div>
                <?php foreach ($positionSlots as $slotKey): ?>
                    <?php $meta = $slotMeta[$slotKey] ?? null; ?>
                    <?php if (!$meta): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <div class="field-slot field-side-<?= e((string) ($meta['side'] ?? 'blue')) ?> is-open is-selectable"
                         style="left: <?= (float) $meta['x'] ?>%; top: <?= (float) $meta['y'] ?>%;"
                         data-slot-key="<?= e($slotKey) ?>">
                        <button type="button" class="field-plus-btn" aria-label="Select <?= e($meta['label']) ?>">+</button>
                        <span class="field-label"><?= e($meta['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-12 d-grid d-md-flex justify-content-md-end">
            <button class="btn btn-warning rounded-pill px-4">Publish Team Ad</button>
        </div>
    </form>
</section>

<section class="glass-panel p-4 mb-4">
    <form method="get" action="<?= route_path('/team-ads') ?>" class="row g-2 align-items-end">
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
    <?php if (empty($ads)): ?>
        <div class="col-12">
            <div class="glass-panel p-4">
                <p class="mb-0">No active team ads right now.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($ads as $ad): ?>
            <?php
            $adId = (int) ($ad['id'] ?? 0);
            $joinedCount = (int) ($ad['joined_count'] ?? 0);
            $teamTarget = (int) ($ad['team_size_target'] ?? 10);
            $spotsLeft = max(0, $teamTarget - $joinedCount);
            $isCreator = (int) ($ad['creator_user_id'] ?? 0) === (int) (Auth::id() ?? 0);
            $joinedByMe = (int) ($ad['joined_by_me'] ?? 0) > 0;
            $canSelectSlot = !$isCreator && !$joinedByMe && $spotsLeft > 0 && (string) $ad['status'] === 'open';
            $members = $membersByAd[$adId] ?? [];
            $occupiedBySlot = [];
            foreach ($members as $member) {
                $slotKey = strtolower(trim((string) ($member['slot_key'] ?? '')));
                if ($slotKey !== '') {
                    $occupiedBySlot[$slotKey] = $member;
                }
            }
            ?>
            <div class="col-12">
                <article class="glass-panel p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h5 mb-0"><?= e($ad['pitch_name']) ?></h2>
                        <span class="badge text-bg-dark"><?= e((string) $ad['status']) ?></span>
                    </div>
                    <p class="small text-light-emphasis mb-1"><?= e($ad['city']) ?> - <?= e($ad['address']) ?></p>
                    <p class="small mb-1">By: <strong><?= e($ad['creator_username']) ?></strong></p>
                    <p class="small mb-1">Start: <strong><?= e((string) $ad['match_start']) ?></strong></p>
                    <p class="small mb-3">Players: <strong><?= $joinedCount ?>/<?= $teamTarget ?></strong> (<?= $spotsLeft ?> left)</p>
                    <?php if (trim((string) ($ad['notes'] ?? '')) !== ''): ?>
                        <p class="small mb-3"><?= e((string) $ad['notes']) ?></p>
                    <?php endif; ?>

                    <div class="team-field mb-3"
                         data-team-field
                         data-position-picker
                         data-ad-id="<?= $adId ?>"
                         data-viewer-avatar="<?= e($viewerAvatarUrl) ?>"
                         data-viewer-name="<?= e($viewerName) ?>">
                        <div class="team-field-half-line"></div>
                        <div class="team-field-line team-field-center-circle"></div>
                        <div class="team-field-center-dot"></div>
                        <div class="team-field-line team-field-left-box"></div>
                        <div class="team-field-line team-field-right-box"></div>
                        <div class="team-field-line team-field-left-small-box"></div>
                        <div class="team-field-line team-field-right-small-box"></div>
                        <div class="team-field-goal team-field-left-goal"></div>
                        <div class="team-field-goal team-field-right-goal"></div>
                        <div class="team-field-penalty-dot team-field-left-penalty"></div>
                        <div class="team-field-penalty-dot team-field-right-penalty"></div>
                        <?php foreach ($positionSlots as $slotKey): ?>
                            <?php $meta = $slotMeta[$slotKey] ?? null; ?>
                            <?php if (!$meta): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php $player = $occupiedBySlot[$slotKey] ?? null; ?>
                            <?php $avatarName = (string) ($player['username'] ?? 'Player'); ?>
                            <?php $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($avatarName) . '&background=1f5f3f&color=ffffff&bold=true&size=84'; ?>
                            <div class="field-slot field-side-<?= e((string) ($meta['side'] ?? 'blue')) ?> <?= $player ? 'is-filled' : 'is-open' ?> <?= (!$player && $canSelectSlot) ? 'is-selectable' : '' ?>"
                                 style="left: <?= (float) $meta['x'] ?>%; top: <?= (float) $meta['y'] ?>%;"
                                 data-slot-key="<?= e($slotKey) ?>">
                                <?php if ($player): ?>
                                    <img src="<?= e($avatarUrl) ?>" alt="<?= e($avatarName) ?>" class="field-avatar">
                                    <span class="field-label"><?= e($meta['label']) ?></span>
                                    <span class="field-name"><?= e($avatarName) ?></span>
                                <?php else: ?>
                                    <button type="button" class="field-plus-btn" aria-label="Select <?= e($meta['label']) ?>">+</button>
                                    <span class="field-label"><?= e($meta['label']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="small text-light-emphasis mb-3">
                        Click an open slot before joining to lock your position.
                    </div>

                    <?php if ($isCreator): ?>
                        <form method="post" action="<?= route_path('/team-ads/close') ?>">
                            <?= Csrf::inputField() ?>
                            <input type="hidden" name="ad_id" value="<?= $adId ?>">
                            <button class="btn btn-sm btn-outline-danger">Close Ad</button>
                        </form>
                    <?php elseif ($joinedByMe): ?>
                        <form method="post" action="<?= route_path('/team-ads/leave') ?>">
                            <?= Csrf::inputField() ?>
                            <input type="hidden" name="ad_id" value="<?= $adId ?>">
                            <button class="btn btn-sm btn-outline-light">Leave Ad</button>
                        </form>
                    <?php elseif ($spotsLeft > 0 && (string) $ad['status'] === 'open'): ?>
                        <form method="post" action="<?= route_path('/team-ads/join') ?>" class="row g-2 team-join-form" data-team-join-form>
                            <?= Csrf::inputField() ?>
                            <input type="hidden" name="ad_id" value="<?= $adId ?>">
                            <input type="hidden" name="position_slot" value="" data-selected-slot-input>
                            <div class="col-md-6">
                                <select class="form-select form-select-sm" name="role_name">
                                    <option value="goalkeeper">Goalkeeper</option>
                                    <option value="defender">Defender</option>
                                    <option value="midfielder" selected>Midfielder</option>
                                    <option value="attacker">Attacker</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-light-emphasis pt-1" data-selected-slot-label>No slot selected (auto assign)</div>
                            </div>
                            <div class="col-12 d-grid d-md-flex justify-content-md-end">
                                <button class="btn btn-sm btn-warning">Join</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </article>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
