<?php

declare(strict_types=1);

$user = Auth::requireLogin();
Auth::requireRole($user, ['manager', 'admin']);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h3 class="mb-1 fw-bold">Analytics</h3>
        <div class="text-muted">Operational visibility for request volume, status flow, priority pressure, and SLA health</div>
    </div>

    <a class="btn btn-outline-primary" href="/index.php?page=requests">Open queue</a>
</div>

<div class="row g-3 mb-3" id="analyticsTopCards">
    <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="placeholder-glow">
                        <span class="placeholder col-6"></span>
                        <div class="mt-3"><span class="placeholder col-4"></span></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endfor; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="fw-semibold mb-3">Status distribution</h5>
                <div id="statusChart" class="vstack gap-2"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="fw-semibold mb-3">Priority mix</h5>
                <div id="priorityChart" class="vstack gap-2"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="fw-semibold mb-3">Type distribution</h5>
                <div id="typeChart" class="vstack gap-2"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="fw-semibold mb-3">SLA health</h5>
                <div id="slaChart" class="vstack gap-2"></div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h5 class="fw-semibold mb-0">Oldest active requests</h5>
                    <span class="small text-muted">Top 10 aging items in your current scope</span>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted small">
                                <th>ID</th>
                                <th>Title</th>
                                <th>Requester</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Age</th>
                                <th>Due date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="agingTable">
                            <tr><td colspan="8" class="text-muted py-4">Loading analytics…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/analytics.js"></script>