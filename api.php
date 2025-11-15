<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

$user = require_auth();
$modules = include __DIR__ . '/includes/modules.php';

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'list':
        handle_list($modules);
        break;
    case 'save':
        handle_save($modules);
        break;
    case 'delete':
        handle_delete($modules);
        break;
    case 'relations':
        handle_relations($modules);
        break;
    case 'activity':
        handle_activity();
        break;
    case 'setWorkspace':
        handle_set_workspace();
        break;
    case 'financialSummary':
        handle_financial_summary();
        break;
    case 'export':
        handle_export($modules);
        break;
    default:
        json_response(['error' => 'Unsupported action.'], 400);
}

function module_or_fail(array $modules, string $moduleKey): array
{
    if (!isset($modules[$moduleKey])) {
        json_response(['error' => 'Invalid module.'], 404);
    }
    return $modules[$moduleKey];
}

function ensure_tenant_scope(array $module): ?int
{
    $clientId = current_client_id();
    if ($module['tenant_scoped'] && $clientId === null && !is_admin()) {
        json_response(['error' => 'Tenant not assigned.'], 403);
    }
    if ($module['tenant_scoped'] && $clientId === null && is_admin()) {
        json_response(['error' => 'Select a client workspace first.'], 409);
    }
    return $module['tenant_scoped'] ? $clientId : null;
}

function filter_clause(array $module, ?int $clientId): array
{
    if (!$module['tenant_scoped']) {
        return ['', []];
    }

    if ($clientId === null && is_admin()) {
        return ['', []];
    }

    return [' WHERE client_id = :client_id', ['client_id' => $clientId]];
}

function handle_list(array $modules): void
{
    $moduleKey = $_GET['module'] ?? '';
    $module = module_or_fail($modules, $moduleKey);
    assert_module_access($moduleKey, $module);

    $clientId = ensure_tenant_scope($module);

    [$where, $params] = filter_clause($module, $clientId);
    $columns = array_merge(['id'], array_keys($module['fields']), ['created_at']);

    $sql = sprintf(
        'SELECT %s FROM %s%s ORDER BY created_at DESC',
        implode(', ', array_map(static fn(string $col) => $col === 'id' ? $col : "`$col`", $columns)),
        $module['table'],
        $where
    );

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    json_response(['records' => $records]);
}

function handle_save(array $modules): void
{
    $moduleKey = $_POST['module'] ?? '';
    $module = module_or_fail($modules, $moduleKey);
    assert_module_access($moduleKey, $module);

    $clientId = ensure_tenant_scope($module);

    $id = request_int('id', $_POST);
    $fields = $module['fields'];
    $data = [];
    $filePaths = [];

    foreach ($fields as $field => $meta) {
        if ($meta['type'] === 'file') {
            if (!empty($_FILES[$field]['tmp_name'])) {
                $filePaths[$field] = compress_and_store_photo($_FILES[$field]);
            } elseif ($id !== null) {
                $existingKey = 'existing_' . $field;
                if (!empty($_POST[$existingKey])) {
                    $data[$field] = $_POST[$existingKey];
                }
            }
            continue;
        }

        $value = $_POST[$field] ?? null;
        if ($meta['required'] && ($value === null || $value === '')) {
            json_response(['error' => sprintf('%s is required.', $meta['label'])], 422);
        }

        if ($meta['type'] === 'number') {
            $data[$field] = $value !== null && $value !== '' ? (float)$value : null;
        } elseif ($meta['type'] === 'date') {
            $data[$field] = $value !== null && $value !== '' ? $value : null;
        } elseif ($meta['type'] === 'relation') {
            $data[$field] = $value !== null && $value !== '' ? (int)$value : null;
        } elseif ($meta['type'] === 'password') {
            $data[$field] = $value;
        } else {
            $data[$field] = $value !== null ? trim((string)$value) : null;
        }
    }

    $generatedPassword = null;
    if ($moduleKey === 'users') {
        $generatedPassword = null;
        if ($data['password'] === '' || $data['password'] === null) {
            if ($id === null) {
                $generatedPassword = bin2hex(random_bytes(4));
                $data['password'] = $generatedPassword;
            } else {
                unset($data['password']);
            }
        }
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => PASSWORD_COST]);
            unset($data['password']);
        }
    }

    foreach ($filePaths as $field => $path) {
        $data[$field] = $path;
    }

    $pdo = db();
    if ($id) {
        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            $sets[] = "`$column` = :$column";
            $params[$column] = $value;
        }
        if ($module['tenant_scoped']) {
            $params['client_cond'] = $clientId;
            $where = 'WHERE id = :id AND client_id = :client_cond';
        } else {
            $where = 'WHERE id = :id';
        }
        $params['id'] = $id;
        $sql = sprintf('UPDATE %s SET %s %s', $module['table'], implode(', ', $sets), $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        log_activity($module['tenant_scoped'] ? $clientId : null, $moduleKey, sprintf('Updated record %d', $id), current_user()['id']);
        json_response(['status' => 'updated', 'generated_password' => $generatedPassword]);
    }

    if ($module['tenant_scoped']) {
        $data['client_id'] = $clientId;
    }

    $columns = array_keys($data);
    $placeholders = array_map(static fn(string $col) => ':' . $col, $columns);

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $module['table'],
        implode(', ', array_map(static fn(string $col) => "`$col`", $columns)),
        implode(', ', $placeholders)
    );
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $insertId = (int)$pdo->lastInsertId();

    log_activity($module['tenant_scoped'] ? $clientId : null, $moduleKey, sprintf('Created record %d', $insertId), current_user()['id']);
    json_response(['status' => 'created', 'id' => $insertId, 'generated_password' => $generatedPassword]);
}

function handle_delete(array $modules): void
{
    $moduleKey = $_POST['module'] ?? '';
    $module = module_or_fail($modules, $moduleKey);
    assert_module_access($moduleKey, $module);

    $clientId = ensure_tenant_scope($module);
    $id = request_int('id', $_POST);

    if ($id === null) {
        json_response(['error' => 'Missing record id.'], 422);
    }

    $params = ['id' => $id];
    if ($module['tenant_scoped'] && $clientId !== null) {
        $params['client_id'] = $clientId;
        $where = 'WHERE id = :id AND client_id = :client_id';
    } else {
        $where = 'WHERE id = :id';
    }

    $sql = sprintf('DELETE FROM %s %s', $module['table'], $where);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    log_activity($module['tenant_scoped'] ? $clientId : null, $moduleKey, sprintf('Deleted record %d', $id), current_user()['id']);
    json_response(['status' => 'deleted']);
}

function handle_relations(array $modules): void
{
    $sourceKey = $_GET['source'] ?? '';
    if (!isset($modules[$sourceKey])) {
        json_response(['options' => []]);
    }
    $module = $modules[$sourceKey];

    $clientId = ensure_tenant_scope_for_relation($module);
    [$where, $params] = filter_clause($module, $clientId);

    $labelColumn = guess_label_column($module['table']);

    $sql = sprintf('SELECT id, %s AS label FROM %s%s ORDER BY label ASC', $labelColumn, $module['table'], $where);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $options = $stmt->fetchAll();

    json_response(['options' => $options]);
}

function ensure_tenant_scope_for_relation(array $module): ?int
{
    $clientId = current_client_id();
    if ($module['tenant_scoped']) {
        if ($clientId === null && !is_admin()) {
            json_response(['options' => []]);
        }
        return $clientId;
    }
    return null;
}

function guess_label_column(string $table): string
{
    $pdo = db();
    static $cached = [];
    if (isset($cached[$table])) {
        return $cached[$table];
    }
    $columns = $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
    $preferred = ['name', 'title', 'description', 'item_code'];
    foreach ($preferred as $col) {
        if (in_array($col, $columns, true)) {
            return $cached[$table] = $col;
        }
    }
    return $cached[$table] = $columns[1] ?? 'id';
}

function handle_activity(): void
{
    $clientId = current_client_id();
    $sql = 'SELECT module, description, created_at FROM activity_log';
    $params = [];
    if ($clientId !== null) {
        $sql .= ' WHERE client_id = :client_id';
        $params['client_id'] = $clientId;
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 10';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    json_response(['records' => $records]);
}

function handle_set_workspace(): void
{
    if (!is_admin()) {
        json_response(['error' => 'Access denied.'], 403);
    }
    $clientId = request_int('client_id', $_POST);
    set_active_client($clientId);
    json_response(['status' => 'ok']);
}

function handle_financial_summary(): void
{
    $clientId = current_client_id();
    $pdo = db();

    $params = [];
    $budgetSql = 'SELECT COALESCE(SUM(budget),0) FROM projects';
    $actualSql = 'SELECT COALESCE(SUM(total_amount),0) FROM purchase_orders';
    if ($clientId !== null) {
        $budgetSql .= ' WHERE client_id = :client_id';
        $actualSql .= ' WHERE client_id = :client_id';
        $params['client_id'] = $clientId;
    }

    $budgetStmt = $pdo->prepare($budgetSql);
    $budgetStmt->execute($params);
    $budgetTotal = (float)$budgetStmt->fetchColumn();

    $actualStmt = $pdo->prepare($actualSql);
    $actualStmt->execute($params);
    $actualTotal = (float)$actualStmt->fetchColumn();

    $projectsSql = 'SELECT name, budget FROM projects';
    if ($clientId !== null) {
        $projectsSql .= ' WHERE client_id = :client_id';
    }
    $projectsStmt = $pdo->prepare($projectsSql);
    $projectsStmt->execute($params);
    $projectBudgets = $projectsStmt->fetchAll();

    json_response([
        'budget_total' => $budgetTotal,
        'actual_total' => $actualTotal,
        'projects' => $projectBudgets,
    ]);
}

function handle_export(array $modules): void
{
    $moduleKey = $_GET['module'] ?? '';
    $format = $_GET['format'] ?? 'csv';
    $module = module_or_fail($modules, $moduleKey);
    assert_module_access($moduleKey, $module);

    $clientId = ensure_tenant_scope($module);
    [$where, $params] = filter_clause($module, $clientId);

    $columns = array_merge(['id'], array_keys($module['fields']), ['created_at']);

    $sql = sprintf(
        'SELECT %s FROM %s%s ORDER BY created_at DESC',
        implode(', ', array_map(static fn(string $col) => $col === 'id' ? $col : "`$col`", $columns)),
        $module['table'],
        $where
    );
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    if ($format === 'csv') {
        export_csv($moduleKey, $columns, $records);
        return;
    }

    if ($format === 'pdf') {
        export_pdf($moduleKey, $columns, $records);
        return;
    }

    json_response(['error' => 'Unsupported export format.'], 400);
}

function export_csv(string $moduleKey, array $columns, array $records): void
{
    header('Content-Type: text/csv');
    header(sprintf('Content-Disposition: attachment; filename="%s.csv"', $moduleKey));

    $output = fopen('php://output', 'w');
    fputcsv($output, $columns);
    foreach ($records as $row) {
        fputcsv($output, array_map(static fn($value) => is_scalar($value) ? $value : json_encode($value), $row));
    }
    fclose($output);
    exit();
}

function export_pdf(string $moduleKey, array $columns, array $records): void
{
    require_once __DIR__ . '/includes/simple_pdf.php';
    $title = ucwords(str_replace('_', ' ', $moduleKey)) . ' Report';
    $pdfContent = generate_simple_pdf($title, $columns, $records);
    header('Content-Type: application/pdf');
    header(sprintf('Content-Disposition: attachment; filename="%s.pdf"', $moduleKey));
    echo $pdfContent;
    exit();
}
