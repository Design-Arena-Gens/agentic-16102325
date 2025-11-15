<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

require_auth();
$modules = include __DIR__ . '/includes/modules.php';

// Limit modules exposed in reports to tenant-scoped operational ones.
$reportableModules = array_filter($modules, static function (array $module, string $key): bool {
    if ($key === 'clients' || $key === 'users') {
        return false;
    }
    return true;
}, ARRAY_FILTER_USE_BOTH);

include __DIR__ . '/includes/header.php';
?>
<div class="card border-0">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
            <h2 class="h5 mb-3 mb-lg-0">Reports</h2>
            <div class="d-flex gap-2">
                <select id="reportModule" class="form-select">
                    <?php foreach ($reportableModules as $key => $module): ?>
                        <option value="<?= esc($key) ?>"><?= esc(ucwords(str_replace('_', ' ', $key))) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary report-export" data-format="csv">Export CSV</button>
                <button class="btn btn-outline-primary report-export" data-format="pdf">Export PDF</button>
            </div>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-hover align-middle" id="reportTable">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
