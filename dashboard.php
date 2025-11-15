<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

$user = require_auth();
$pdo = db();

$clientId = current_client_id();

// Summary counts scoped to tenant.
$counts = [
    'projects' => 0,
    'tasks' => 0,
    'vendors' => 0,
    'pending_material_requests' => 0,
];

if ($clientId !== null) {
    $queries = [
        'projects' => 'SELECT COUNT(*) FROM projects WHERE client_id = :client_id',
        'tasks' => 'SELECT COUNT(*) FROM tasks WHERE client_id = :client_id',
        'vendors' => 'SELECT COUNT(*) FROM vendors WHERE client_id = :client_id',
        'pending_material_requests' => 'SELECT COUNT(*) FROM material_requests WHERE client_id = :client_id AND status = "Pending"',
    ];
} else {
    $queries = [
        'projects' => 'SELECT COUNT(*) FROM projects',
        'tasks' => 'SELECT COUNT(*) FROM tasks',
        'vendors' => 'SELECT COUNT(*) FROM vendors',
        'pending_material_requests' => 'SELECT COUNT(*) FROM material_requests WHERE status = "Pending"',
    ];
}

foreach ($queries as $key => $sql) {
    $stmt = $pdo->prepare($sql);
    if ($clientId !== null) {
        $stmt->execute(['client_id' => $clientId]);
    } else {
        $stmt->execute();
    }
    $counts[$key] = (int)$stmt->fetchColumn();
}

include __DIR__ . '/includes/header.php';
?>
<div class="container-fluid">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Projects</h2>
                    <p class="display-6 fw-semibold mb-0"><?= esc($counts['projects']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Tasks</h2>
                    <p class="display-6 fw-semibold mb-0"><?= esc($counts['tasks']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Vendors</h2>
                    <p class="display-6 fw-semibold mb-0"><?= esc($counts['vendors']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Pending Materials</h2>
                    <p class="display-6 fw-semibold mb-0"><?= esc($counts['pending_material_requests']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0">
        <div class="card-body">
            <h2 class="h5">Recent Activity</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th scope="col">Module</th>
                        <th scope="col">Description</th>
                        <th scope="col">Date</th>
                    </tr>
                    </thead>
                    <tbody id="activityFeed">
                    <tr><td colspan="3">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
