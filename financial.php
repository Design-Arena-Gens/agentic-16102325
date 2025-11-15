<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

require_auth();

include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-0">
            <div class="card-body">
                <h2 class="h6 text-muted">Budget Total</h2>
                <p class="display-6 fw-semibold mb-0" id="budgetTotal">-</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0">
            <div class="card-body">
                <h2 class="h6 text-muted">Actual Spend</h2>
                <p class="display-6 fw-semibold mb-0" id="actualTotal">-</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0">
            <div class="card-body">
                <h2 class="h6 text-muted">Variance</h2>
                <p class="display-6 fw-semibold mb-0" id="varianceTotal">-</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 mt-4">
    <div class="card-body">
        <h2 class="h5">Project Budgets</h2>
        <div class="table-responsive mt-3">
            <table class="table table-striped align-middle" id="financialTable">
                <thead>
                <tr>
                    <th>Project</th>
                    <th>Budget</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
