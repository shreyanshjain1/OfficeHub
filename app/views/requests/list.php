<?php

declare(strict_types=1);

$user = Auth::requireLogin();

$tab = $_GET['tab'] ?? 'all';
if (!is_string($tab)) {
    $tab = 'all';
}
$tab = in_array($tab, ['all', 'needs_approval', 'my_assigned'], true) ? $tab : 'all';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h3 class="mb-1 fw-bold">Requests</h3>
        <div class="text-muted">Filter, triage, export, and monitor the full request queue</div>
    </div>

    <div class="d-flex gap-2">
        <button id="btnExportCsv" type="button" class="btn btn-outline-secondary">Export CSV</button>
        <a class="btn btn-primary" href="/index.php?page=request_new">+ New request</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted text-uppercase fw-semibold mb-2">Visible requests</div>
                <div id="metricVisible" class="display-6 fw-bold">—</div>
                <div class="small text-muted">Total records in current scope</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted text-uppercase fw-semibold mb-2">Open work</div>
                <div id="metricOpen" class="display-6 fw-bold">—</div>
                <div class="small text-muted">Open, review, approved, in progress</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted text-uppercase fw-semibold mb-2">Urgent / high</div>
                <div id="metricPriority" class="display-6 fw-bold">—</div>
                <div class="small text-muted">Priority pressure inside filtered queue</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted text-uppercase fw-semibold mb-2">At risk / breached</div>
                <div id="metricSla" class="display-6 fw-bold">—</div>
                <div class="small text-muted">Aging tickets that need action</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-3">
                <label class="form-label">Search</label>
                <input id="fSearch" class="form-control" placeholder="Title, description, requester…">
            </div>

            <div class="col-6 col-lg-2">
                <label class="form-label">Status</label>
                <select id="fStatus" class="form-select">
                    <option value="">All</option>
                    <option>OPEN</option>
                    <option>IN_REVIEW</option>
                    <option>APPROVED</option>
                    <option>REJECTED</option>
                    <option>IN_PROGRESS</option>
                    <option>DONE</option>
                    <option>CLOSED</option>
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <label class="form-label">Type</label>
                <select id="fType" class="form-select">
                    <option value="">All</option>
                    <option>IT</option>
                    <option>SUPPLIES</option>
                    <option>OFFICE</option>
                    <option>OTHER</option>
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <label class="form-label">Priority</label>
                <select id="fPriority" class="form-select">
                    <option value="">All</option>
                    <option>LOW</option>
                    <option>MEDIUM</option>
                    <option>HIGH</option>
                    <option>URGENT</option>
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <label class="form-label">Sort</label>
                <select id="fSort" class="form-select">
                    <option value="newest">Newest first</option>
                    <option value="oldest">Oldest first</option>
                    <option value="priority_desc">Priority high → low</option>
                    <option value="priority_asc">Priority low → high</option>
                    <option value="title_asc">Title A → Z</option>
                    <option value="title_desc">Title Z → A</option>
                    <option value="due_asc">Due date soonest</option>
                    <option value="due_desc">Due date latest</option>
                </select>
            </div>

            <div class="col-12 col-lg-1 d-grid">
                <button id="btnApply" class="btn btn-outline-primary" type="button">Apply</button>
            </div>
        </div>

        <hr class="my-3">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <ul class="nav nav-pills gap-2">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="/index.php?page=requests&tab=all">All</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'my_assigned' ? 'active' : '' ?>" href="/index.php?page=requests&tab=my_assigned">My assigned</a>
                </li>
                <?php if ($user['role'] !== 'employee'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $tab === 'needs_approval' ? 'active' : '' ?>" href="/index.php?page=requests&tab=needs_approval">Needs approval</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div id="queueSummary" class="small text-muted">Loading queue…</div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>ID</th>
                        <th>Title</th>
                        <th class="d-none d-md-table-cell">Requester</th>
                        <th>Status</th>
                        <th class="d-none d-lg-table-cell">Type</th>
                        <th>Priority</th>
                        <th class="d-none d-lg-table-cell">SLA</th>
                        <th class="d-none d-lg-table-cell">Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="reqTbody">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <tr class="placeholder-glow">
                            <td><span class="placeholder col-6"></span></td>
                            <td><span class="placeholder col-10"></span></td>
                            <td class="d-none d-md-table-cell"><span class="placeholder col-8"></span></td>
                            <td><span class="placeholder col-6"></span></td>
                            <td class="d-none d-lg-table-cell"><span class="placeholder col-6"></span></td>
                            <td><span class="placeholder col-6"></span></td>
                            <td class="d-none d-lg-table-cell"><span class="placeholder col-6"></span></td>
                            <td class="d-none d-lg-table-cell"><span class="placeholder col-8"></span></td>
                            <td><span class="placeholder col-6"></span></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div id="emptyStateWrap" class="d-none">
            <?php
            $actionHtml = '<a class="btn btn-primary" href="/index.php?page=request_new">Create your first request</a>';
            $title = 'No requests found';
            $subtitle = 'Try adjusting filters or create a new request.';
            require __DIR__ . '/../partials/empty_state.php';
            ?>
        </div>
    </div>
</div>

<script>
window.__REQUESTS_TAB__ = <?= json_encode($tab, JSON_UNESCAPED_SLASHES) ?>;

(function () {
    const tbody = document.getElementById('reqTbody');
    const emptyWrap = document.getElementById('emptyStateWrap');
    const queueSummary = document.getElementById('queueSummary');
    const btnApply = document.getElementById('btnApply');
    const btnExportCsv = document.getElementById('btnExportCsv');

    const metricVisible = document.getElementById('metricVisible');
    const metricOpen = document.getElementById('metricOpen');
    const metricPriority = document.getElementById('metricPriority');
    const metricSla = document.getElementById('metricSla');

    const fields = {
        q: document.getElementById('fSearch'),
        status: document.getElementById('fStatus'),
        type: document.getElementById('fType'),
        priority: document.getElementById('fPriority'),
        sort: document.getElementById('fSort')
    };

    let lastRows = [];

    function esc(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function buildQuery() {
        const params = new URLSearchParams();
        params.set('tab', window.__REQUESTS_TAB__ || 'all');

        if (fields.q.value.trim() !== '') params.set('q', fields.q.value.trim());
        if (fields.status.value !== '') params.set('status', fields.status.value);
        if (fields.type.value !== '') params.set('type', fields.type.value);
        if (fields.priority.value !== '') params.set('priority', fields.priority.value);
        if (fields.sort.value !== '') params.set('sort', fields.sort.value);

        return params.toString();
    }

    function renderMetrics(rows) {
        const openCount = rows.filter(r => ['OPEN', 'IN_REVIEW', 'APPROVED', 'IN_PROGRESS'].includes(r.status)).length;
        const pressureCount = rows.filter(r => ['HIGH', 'URGENT'].includes(r.priority)).length;
        const slaCount = rows.filter(r => ['at_risk', 'breached'].includes(r.sla_bucket)).length;

        metricVisible.textContent = rows.length;
        metricOpen.textContent = openCount;
        metricPriority.textContent = pressureCount;
        metricSla.textContent = slaCount;

        queueSummary.textContent = `${rows.length} request(s) loaded • ${openCount} active • ${slaCount} at risk / breached`;
    }

    function renderTable(rows) {
        lastRows = rows;
        renderMetrics(rows);

        if (!rows.length) {
            tbody.innerHTML = '';
            emptyWrap.classList.remove('d-none');
            return;
        }

        emptyWrap.classList.add('d-none');

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td class="fw-semibold">#${esc(row.id)}</td>
                <td>
                    <div class="fw-semibold">${esc(row.title)}</div>
                    <div class="small text-muted text-truncate" style="max-width: 360px;">${esc(row.description_preview || '')}</div>
                </td>
                <td class="d-none d-md-table-cell">
                    <div>${esc(row.requester_name || '—')}</div>
                    <div class="small text-muted">${esc(row.department_name || 'No department')}</div>
                </td>
                <td><span class="badge ${esc(row.status_badge_class)}">${esc(row.status)}</span></td>
                <td class="d-none d-lg-table-cell"><span class="badge border ${esc(row.type_badge_class)}">${esc(row.type)}</span></td>
                <td><span class="badge ${esc(row.priority_badge_class)}">${esc(row.priority)}</span></td>
                <td class="d-none d-lg-table-cell"><span class="badge ${esc(row.sla_badge_class)}">${esc(row.sla_label)}</span></td>
                <td class="d-none d-lg-table-cell">
                    <div>${esc(row.created_at_label)}</div>
                    <div class="small text-muted">${esc(row.age_days_label)}</div>
                </td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="/index.php?page=request_view&id=${encodeURIComponent(row.id)}">Open</a>
                </td>
            </tr>
        `).join('');
    }

    function exportCsv() {
        if (!lastRows.length) {
            return;
        }

        const headers = [
            'ID', 'Title', 'Requester', 'Department', 'Status',
            'Type', 'Priority', 'SLA', 'Created At', 'Due Date'
        ];

        const lines = [headers.join(',')];

        for (const row of lastRows) {
            const vals = [
                row.id,
                row.title,
                row.requester_name || '',
                row.department_name || '',
                row.status,
                row.type,
                row.priority,
                row.sla_label,
                row.created_at_label,
                row.due_date_label || ''
            ].map(v => `"${String(v ?? '').replaceAll('"', '""')}"`);

            lines.push(vals.join(','));
        }

        const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'officehub-requests.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    async function loadRequests() {
        tbody.innerHTML = `
            <tr><td colspan="9" class="text-muted py-4">Loading requests…</td></tr>
        `;

        try {
            const res = await fetch('/actions/api/requests_list.php?' + buildQuery(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await res.json();
            renderTable(Array.isArray(data.rows) ? data.rows : []);
        } catch (error) {
            tbody.innerHTML = `
                <tr><td colspan="9" class="text-danger py-4">Failed to load requests.</td></tr>
            `;
            queueSummary.textContent = 'Unable to load queue';
            metricVisible.textContent = '—';
            metricOpen.textContent = '—';
            metricPriority.textContent = '—';
            metricSla.textContent = '—';
        }
    }

    btnApply.addEventListener('click', loadRequests);
    btnExportCsv.addEventListener('click', exportCsv);

    fields.q.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadRequests();
        }
    });

    loadRequests();
})();
</script>