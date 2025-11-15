<?php
declare(strict_types=1);

$user = current_user();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="/dashboard.php">Onsite Lite</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                <?php if (is_admin()): ?>
                    <li class="nav-item me-lg-3">
                        <div class="d-flex align-items-center">
                            <label for="clientSwitcher" class="text-white me-2 mb-0 small">Workspace</label>
                            <select id="clientSwitcher" class="form-select form-select-sm" style="min-width: 200px;"
                                    data-active-client="<?= esc((string)(current_client_id() ?? '')) ?>">
                                <option value="">All Clients</option>
                            </select>
                        </div>
                    </li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="/projects.php">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="/tasks.php">Tasks</a></li>
                <li class="nav-item"><a class="nav-link" href="/boq.php">BOQ</a></li>
                <li class="nav-item"><a class="nav-link" href="/dpr.php">DPR</a></li>
                <li class="nav-item"><a class="nav-link" href="/materials.php">Material</a></li>
                <li class="nav-item"><a class="nav-link" href="/purchase_orders.php">Purchase Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="/stock.php">Stock</a></li>
                <li class="nav-item"><a class="nav-link" href="/attendance.php">Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="/workforce.php">Workforce</a></li>
                <li class="nav-item"><a class="nav-link" href="/equipment.php">Equipment</a></li>
                <li class="nav-item"><a class="nav-link" href="/vendors.php">Vendors</a></li>
                <li class="nav-item"><a class="nav-link" href="/financial.php">Financials</a></li>
                <li class="nav-item"><a class="nav-link" href="/reports.php">Reports</a></li>
                <?php if (is_admin()): ?>
                    <li class="nav-item"><a class="nav-link" href="/clients.php">Clients</a></li>
                    <li class="nav-item"><a class="nav-link" href="/users.php">Users</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text text-white">Hello, <?= esc($user['full_name']) ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
