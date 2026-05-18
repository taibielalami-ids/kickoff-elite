<section class="glass-panel p-4 p-md-5 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="h3 mb-1">Welcome, <?= e($user['username']) ?></h1>
            <p class="mb-0 text-light-emphasis">Role: <span class="badge text-bg-dark"><?= e($user['role']) ?></span></p>
        </div>
        <?php if (($user['role'] ?? '') !== 'admin'): ?>
            <a href="<?= route_path('/pitches') ?>" class="btn btn-warning rounded-pill px-4">Browse Pitches</a>
        <?php else: ?>
            <a href="<?= route_path('/admin/verify-code') ?>" class="btn btn-warning rounded-pill px-4">Verify Booking Code</a>
        <?php endif; ?>
    </div>
</section>

<section id="wallet" class="glass-panel p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 mb-0">My Wallet</h2>
        <span class="badge text-bg-warning text-dark">1 Ticket = 50 DH</span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100">
                <p class="small text-light-emphasis mb-1">Balance</p>
                <p class="h4 mb-0"><?= e(number_format((float) ($wallet['balance'] ?? 0), 2)) ?> DH</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100">
                <p class="small text-light-emphasis mb-1">Tickets</p>
                <p class="h4 mb-0"><?= e((string) ((int) ($wallet['ticket_balance'] ?? 0))) ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100">
                <p class="small text-light-emphasis mb-2">Quick Buy</p>
                <form method="post" action="<?= route_path('/dashboard/buy-tickets') ?>" class="row g-2">
                    <?= Csrf::inputField() ?>
                    <div class="col-7">
                        <input type="number" name="ticket_count" min="1" step="1" class="form-control form-control-sm" placeholder="Tickets">
                    </div>
                    <div class="col-5 d-grid">
                        <button class="btn btn-sm btn-warning" type="submit">Buy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="border rounded-3 p-3 h-100">
                <h3 class="h6 mb-3">Top Up Balance</h3>
                <form method="post" action="<?= route_path('/dashboard/topup') ?>" class="row g-2">
                    <?= Csrf::inputField() ?>
                    <div class="col-7">
                        <input type="number" name="amount" min="10" step="10" class="form-control form-control-sm" placeholder="Amount DH">
                    </div>
                    <div class="col-5 d-grid">
                        <button class="btn btn-sm btn-success" type="submit">Top Up</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="border rounded-3 p-3 h-100">
                <h3 class="h6 mb-3">Withdraw Balance</h3>
                <form method="post" action="<?= route_path('/dashboard/withdraw') ?>" class="row g-2">
                    <?= Csrf::inputField() ?>
                    <div class="col-7">
                        <input type="number" name="amount" min="1" step="1" class="form-control form-control-sm" placeholder="Amount DH">
                    </div>
                    <div class="col-5 d-grid">
                        <button class="btn btn-sm btn-outline-light" type="submit">Withdraw</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h3 class="h6 mb-3">Recent Wallet Activity</h3>
        <?php if (empty($walletTx ?? [])): ?>
            <p class="mb-0 text-light-emphasis">No wallet transactions yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Tickets</th>
                        <th>Reference</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($walletTx as $tx): ?>
                        <tr>
                            <td><?= e((string) $tx['tx_type']) ?></td>
                            <td><?= e(number_format((float) $tx['amount'], 2)) ?> DH</td>
                            <td><?= e((string) ((int) $tx['tickets_change'])) ?></td>
                            <td><?= e((string) ($tx['reference_text'] ?? '-')) ?></td>
                            <td><?= e((string) ($tx['created_at'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (($user['role'] ?? '') === 'admin'): ?>
<section class="glass-panel p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 mb-0">My Pitches</h2>
        <span class="small text-light-emphasis"><?= count($myPitches ?? []) ?> pitch(es)</span>
    </div>

    <?php if (empty($myPitches ?? [])): ?>
        <p class="mb-2 text-light-emphasis">No pitches created yet.</p>
        <a href="<?= route_path('/admin/pitches/create') ?>" class="btn btn-sm btn-outline-light">Create First Pitch</a>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle mb-0">
                <thead>
                <tr>
                    <th>Pitch</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Active</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($myPitches, 0, 20) as $pitch): ?>
                    <tr>
                        <td>
                            <strong><?= e($pitch['name']) ?></strong><br>
                            <span class="small text-light-emphasis"><?= e((string) ($pitch['address'] ?? '')) ?></span>
                        </td>
                        <td><?= e($pitch['city']) ?></td>
                        <td><span class="badge text-bg-dark"><?= e((string) ($pitch['status'] ?? 'available')) ?></span></td>
                        <td>
                            <?php if ((int) ($pitch['is_active'] ?? 0) === 1): ?>
                                <span class="badge text-bg-success">active</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= route_path('/admin/pitches/manage?id=' . (int) $pitch['id']) ?>" class="btn btn-sm btn-outline-light">Manage</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="glass-panel p-4 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 mb-0">My Bookings</h2>
    </div>

    <?php if (empty($bookings ?? [])): ?>
        <p class="mb-0 text-light-emphasis">No bookings yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead>
                <tr>
                    <th>Pitch</th>
                    <th>Slot</th>
                    <th>Status</th>
                    <th>Code</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($bookings, 0, 10) as $booking): ?>
                    <tr>
                        <td>
                            <strong><?= e($booking['pitch_name']) ?></strong><br>
                            <span class="small text-light-emphasis"><?= e($booking['city']) ?></span>
                        </td>
                        <td>
                            <span><?= e($booking['slot_start']) ?></span><br>
                            <span class="small text-light-emphasis">to <?= e($booking['slot_end']) ?></span>
                        </td>
                        <td><span class="badge text-bg-info"><?= e($booking['status']) ?></span></td>
                        <td><?= e((string) ($booking['booking_code'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
