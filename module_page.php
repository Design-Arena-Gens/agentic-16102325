<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

$user = require_auth();
$moduleKey = $_GET['module'] ?? '';
$modules = include __DIR__ . '/includes/modules.php';

if (!isset($modules[$moduleKey])) {
    http_response_code(404);
    echo 'Module not found';
    exit();
}

$module = $modules[$moduleKey];

if (!in_array($user['role'], $module['roles'], true)) {
    http_response_code(403);
    echo 'Access denied';
    exit();
}

$pageTitle = ucwords(str_replace('_', ' ', $moduleKey));
$displayFields = array_filter(
    $module['fields'],
    static fn(array $meta) => $meta['type'] !== 'password'
);

include __DIR__ . '/includes/header.php';
?>
<div class="row">
    <div class="col-12 col-lg-4">
        <div class="card border-0 mb-4">
            <div class="card-body">
                <div class="module-title">
                    <h2 class="h5 mb-0"><?= esc($pageTitle) ?></h2>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="resetFormBtn">Reset</button>
                    </div>
                </div>
                <form id="moduleForm" class="mt-3" enctype="multipart/form-data" data-module="<?= esc($moduleKey) ?>">
                    <input type="hidden" name="id" id="recordId">
                    <?php foreach ($module['fields'] as $field => $meta): ?>
                        <div class="mb-3">
                            <label class="form-label" for="<?= esc($field) ?>"><?= esc($meta['label']) ?></label>
                            <?php if ($meta['type'] === 'textarea'): ?>
                                <textarea class="form-control" name="<?= esc($field) ?>" id="<?= esc($field) ?>" <?= $meta['required'] ? 'required' : '' ?>></textarea>
                            <?php elseif ($meta['type'] === 'select'): ?>
                                <select class="form-select" name="<?= esc($field) ?>" id="<?= esc($field) ?>" <?= $meta['required'] ? 'required' : '' ?>>
                                    <option value="">Select</option>
                                    <?php foreach ($meta['options'] ?? [] as $option): ?>
                                        <option value="<?= esc($option) ?>"><?= esc($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($meta['type'] === 'relation'): ?>
                                <select class="form-select relation-field" data-source="<?= esc($meta['source']) ?>" name="<?= esc($field) ?>" id="<?= esc($field) ?>" <?= $meta['required'] ? 'required' : '' ?>>
                                    <option value="">Select</option>
                                </select>
                            <?php elseif ($meta['type'] === 'file'): ?>
                                <input class="form-control" type="file" name="<?= esc($field) ?>" id="<?= esc($field) ?>" <?= $meta['required'] ? 'required' : '' ?>>
                                <input type="hidden" name="existing_<?= esc($field) ?>" id="existing_<?= esc($field) ?>">
                            <?php else: ?>
                                <input class="form-control" type="<?= esc($meta['type']) ?>" name="<?= esc($field) ?>" id="<?= esc($field) ?>" <?= $meta['required'] ? 'required' : '' ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Records</h2>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary export-btn" data-format="csv">Export CSV</button>
                        <button class="btn btn-sm btn-outline-primary export-btn" data-format="pdf">Export PDF</button>
                    </div>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle" id="moduleTable"
                           data-module="<?= esc($moduleKey) ?>"
                           data-columns="<?= esc(implode(',', array_keys($displayFields))) ?>"
                           data-file-fields="<?= esc(implode(',', array_keys(array_filter($displayFields, fn($meta) => $meta['type'] === 'file')))) ?>">
                        <thead>
                        <tr>
                            <th>Actions</th>
                            <?php foreach ($displayFields as $field => $meta): ?>
                                <th><?= esc($meta['label']) ?></th>
                            <?php endforeach; ?>
                            <th>Created</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
