<section class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">My Bookings</h1>
        <p class="text-light-emphasis mb-0">Track reservation status and booking codes for check-in.</p>
    </div>
    <a href="<?= route_path('/pitches') ?>" class="btn btn-warning rounded-pill px-4">Book New Slot</a>
</section>

<section class="row g-3">
    <?php if (empty($bookings)): ?>
        <div class="col-12">
            <div class="glass-panel p-4">
                <p class="mb-0">No bookings yet. Choose a day/hour on the pitches page and reserve your slot.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($bookings as $booking): ?>
            <div class="col-md-6 col-xl-4">
                <article class="glass-panel p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h5 mb-0"><?= e($booking['pitch_name']) ?></h2>
                        <?php
                        $status = (string) $booking['status'];
                        $badgeClass = 'text-bg-secondary';
                        if ($status === 'reserved' || $status === 'completed') {
                            $badgeClass = 'text-bg-success';
                        } elseif ($status === 'waiting_payment' || $status === 'pending') {
                            $badgeClass = 'text-bg-warning';
                        } elseif ($status === 'cancelled' || $status === 'rejected' || $status === 'expired') {
                            $badgeClass = 'text-bg-danger';
                        }
                        ?>
                        <span class="badge <?= e($badgeClass) ?>"><?= e($status) ?></span>
                    </div>
                    <p class="small text-light-emphasis mb-1"><?= e($booking['city']) ?> - <?= e($booking['address']) ?></p>
                    <p class="small mb-1">Start: <strong><?= e($booking['slot_start']) ?></strong></p>
                    <p class="small mb-1">End: <strong><?= e($booking['slot_end']) ?></strong></p>
                    <p class="small mb-1">Total: <strong><?= e(number_format((float) $booking['total_price'], 0)) ?> DH</strong></p>
                    <p class="small mb-1">
                        Payment:
                        <strong><?= e((string) ($booking['payment_mode'] ?? 'none')) ?></strong>
                        <?php if ((int) ($booking['paid_amount'] ?? 0) > 0): ?>
                            (<?= e(number_format((float) $booking['paid_amount'], 0)) ?> DH)
                        <?php elseif ((int) ($booking['paid_tickets'] ?? 0) > 0): ?>
                            (<?= e((string) ((int) $booking['paid_tickets'])) ?> tickets)
                        <?php endif; ?>
                    </p>
                    <?php if ((int) ($booking['is_refunded'] ?? 0) === 1): ?>
                        <p class="small mb-1 text-success">
                            Refund sent:
                            <?php if ((float) ($booking['refunded_amount'] ?? 0) > 0): ?>
                                <?= e(number_format((float) $booking['refunded_amount'], 0)) ?> DH
                            <?php else: ?>
                                <?= e((string) ((int) ($booking['refunded_tickets'] ?? 0))) ?> tickets
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <p class="small mb-0">
                        Booking Code:
                        <span class="badge text-bg-dark fs-6"><?= e((string) ($booking['booking_code'] ?? '-')) ?></span>
                    </p>

                    <?php
                    $canCancelStatus = in_array($status, ['pending', 'waiting_payment', 'reserved'], true);
                    $startTs = strtotime((string) $booking['slot_start']);
                    $endTs = strtotime((string) $booking['slot_end']);
                    $canCancelTime = $startTs !== false && $startTs > strtotime('+48 hours');
                    $canPayNow = $status === 'waiting_payment' && $endTs !== false && $endTs > time();
                    ?>
                    <?php if ($canPayNow): ?>
                        <form method="post" action="<?= route_path('/bookings/pay') ?>" class="mt-3">
                            <?= Csrf::inputField() ?>
                            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                            <div class="row g-2">
                                <div class="col-7">
                                    <select name="payment_mode" class="form-select form-select-sm">
                                        <option value="wallet">Pay with Wallet (500 DH)</option>
                                        <option value="tickets">Pay with 10 Tickets</option>
                                    </select>
                                </div>
                                <div class="col-5 d-grid">
                                    <button class="btn btn-sm btn-warning">Pay Now</button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if ($canCancelStatus && $canCancelTime): ?>
                        <form method="post" action="<?= route_path('/bookings/cancel') ?>" class="mt-3">
                            <?= Csrf::inputField() ?>
                            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Cancel Booking (48h+ only)</button>
                        </form>
                    <?php elseif ($canCancelStatus): ?>
                        <p class="small text-warning mt-3 mb-0">Cancellation and refund are closed (less than 48h).</p>
                    <?php endif; ?>
                </article>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
