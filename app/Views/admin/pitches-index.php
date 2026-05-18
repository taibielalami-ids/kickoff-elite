<section class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Pitch Management Panel</h1>
        <p class="text-light-emphasis mb-0">Manage your venues, photos, and blocked schedules.</p>
    </div>
    <a class="btn btn-warning rounded-pill px-4" href="<?= route_path('/admin/pitches/create') ?>">Add New Pitch</a>
</section>

<section class="row g-3">
    <?php if (empty($pitches)): ?>
        <div class="col-12">
            <div class="glass-panel p-4">
                <p class="mb-0">No pitches yet. Start by creating your first pitch listing.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($pitches as $pitch): ?>
            <div class="col-md-6 col-xl-4">
                <article class="glass-panel p-3 h-100">
                    <?php if (!empty($pitch['cover_photo'])): ?>
                        <img src="<?= e($pitch['cover_photo']) ?>" alt="<?= e($pitch['name']) ?>" class="img-fluid rounded mb-3 owner-pitch-cover">
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0"><?= e($pitch['name']) ?></h2>
                        <span class="badge text-bg-success"><?= e($pitch['status']) ?></span>
                    </div>
                    <p class="small text-light-emphasis mb-2 mt-2"><?= e($pitch['city']) ?> - <?= e($pitch['address']) ?></p>
                    <p class="small mb-1">Hours: <?= e(substr($pitch['open_time'], 0, 5)) ?> - <?= e(substr($pitch['close_time'], 0, 5)) ?></p>
                    <p class="small mb-3">
                        Photos: <?= (int) $pitch['photos_count'] ?> - Blocked Slots: <?= (int) $pitch['blocks_count'] ?>
                    </p>

                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="text-warning"><?= e(number_format((float) $pitch['price_per_player'], 0)) ?> DH / player</strong>
                        <a class="btn btn-sm btn-outline-light" href="<?= route_path('/admin/pitches/manage?id=' . (int) $pitch['id']) ?>">Manage</a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

