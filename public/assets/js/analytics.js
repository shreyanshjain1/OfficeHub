(function () {
  const topCards = document.getElementById('analyticsTopCards');
  const statusChart = document.getElementById('statusChart');
  const priorityChart = document.getElementById('priorityChart');
  const typeChart = document.getElementById('typeChart');
  const slaChart = document.getElementById('slaChart');
  const agingTable = document.getElementById('agingTable');

  if (!topCards || !statusChart || !priorityChart || !typeChart || !slaChart || !agingTable) {
    return;
  }

  function esc(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function renderBarList(target, data) {
    const entries = Object.entries(data || {});
    if (!entries.length) {
      target.innerHTML = '<div class="text-muted">No data available.</div>';
      return;
    }

    const max = Math.max(...entries.map(([, value]) => Number(value)));

    target.innerHTML = entries.map(([label, value]) => {
      const percent = max > 0 ? Math.max(6, Math.round((Number(value) / max) * 100)) : 0;

      return `
        <div>
          <div class="d-flex justify-content-between small mb-1">
            <span>${esc(label)}</span>
            <span class="text-muted">${esc(value)}</span>
          </div>
          <div class="progress" style="height:10px;">
            <div class="progress-bar" style="width:${percent}%"></div>
          </div>
        </div>
      `;
    }).join('');
  }

  function renderSummary(summary) {
    topCards.innerHTML = `
      <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm"><div class="card-body">
          <div class="small text-muted text-uppercase fw-semibold mb-2">Visible requests</div>
          <div class="display-6 fw-bold">${esc(summary.total)}</div>
          <div class="small text-muted">Requests in current access scope</div>
        </div></div>
      </div>
      <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm"><div class="card-body">
          <div class="small text-muted text-uppercase fw-semibold mb-2">Active queue</div>
          <div class="display-6 fw-bold">${esc(summary.active)}</div>
          <div class="small text-muted">Open, review, approved, in progress</div>
        </div></div>
      </div>
      <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm"><div class="card-body">
          <div class="small text-muted text-uppercase fw-semibold mb-2">Urgent / high</div>
          <div class="display-6 fw-bold">${esc(summary.urgent_high)}</div>
          <div class="small text-muted">Current priority pressure</div>
        </div></div>
      </div>
      <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm"><div class="card-body">
          <div class="small text-muted text-uppercase fw-semibold mb-2">At risk / breached</div>
          <div class="display-6 fw-bold">${esc(summary.at_risk_breached)}</div>
          <div class="small text-muted">Tickets needing escalation</div>
        </div></div>
      </div>
    `;
  }

  function renderAging(rows) {
    if (!rows.length) {
      agingTable.innerHTML = '<tr><td colspan="8" class="text-muted py-4">No aging requests found.</td></tr>';
      return;
    }

    agingTable.innerHTML = rows.map((row) => `
      <tr>
        <td class="fw-semibold">#${esc(row.id)}</td>
        <td>
          <div class="fw-semibold">${esc(row.title)}</div>
          <div class="small text-muted">${esc(row.department_name || '')}</div>
        </td>
        <td>${esc(row.requester_name || '—')}</td>
        <td><span class="badge ${esc(row.status_badge_class)}">${esc(row.status)}</span></td>
        <td><span class="badge ${esc(row.priority_badge_class)}">${esc(row.priority)}</span></td>
        <td>
          <div>${esc(row.age_label)}</div>
          <div class="small text-muted"><span class="badge ${esc(row.sla_badge_class)}">${esc(row.sla_label)}</span></div>
        </td>
        <td>${esc(row.due_date_label || '—')}</td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="/index.php?page=request_view&id=${encodeURIComponent(row.id)}">Open</a>
        </td>
      </tr>
    `).join('');
  }

  async function load() {
    try {
      const res = await fetch('/actions/api/analytics_overview.php', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      const data = await res.json();

      renderSummary(data.summary || {});
      renderBarList(statusChart, data.status_counts || {});
      renderBarList(priorityChart, data.priority_counts || {});
      renderBarList(typeChart, data.type_counts || {});
      renderBarList(slaChart, data.sla_counts || {});
      renderAging(Array.isArray(data.aging) ? data.aging : []);
    } catch (error) {
      topCards.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">Failed to load analytics.</div></div>';
      statusChart.innerHTML = '<div class="text-danger">Failed to load.</div>';
      priorityChart.innerHTML = '<div class="text-danger">Failed to load.</div>';
      typeChart.innerHTML = '<div class="text-danger">Failed to load.</div>';
      slaChart.innerHTML = '<div class="text-danger">Failed to load.</div>';
      agingTable.innerHTML = '<tr><td colspan="8" class="text-danger py-4">Failed to load analytics.</td></tr>';
    }
  }

  load();
})();