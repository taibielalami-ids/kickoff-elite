<section class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Admin Dashboard</h1>
        <p class="text-light-emphasis mb-0">Read-only users list.</p>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <article class="glass-panel p-4 h-100">
            <h2 class="h6 text-uppercase text-warning mb-2">Users</h2>
            <p class="display-6 mb-0"><?= e((string) ($summary['users_count'] ?? 0)) ?></p>
        </article>
    </div>
</section>

<section class="mt-4">
    <article class="glass-panel p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">All Users</h2>
            <span class="small text-light-emphasis"><?= count($users ?? []) ?> user(s)</span>
        </div>

        <?php if (empty($users ?? [])): ?>
            <p class="mb-0 text-light-emphasis">No users found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>City</th>
                        <th>Created</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= (int) $user['id'] ?></td>
                            <td><?= e((string) $user['username']) ?></td>
                            <td><?= e((string) $user['email']) ?></td>
                            <td><span class="badge text-bg-dark"><?= e((string) $user['role']) ?></span></td>
                            <td>
                                <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                    <span class="badge text-bg-success">active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">blocked</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) $user['city']) ?></td>
                            <td><?= e((string) $user['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>
