/* ChronoX Dashboard JS */

// ── Sidebar toggle (both mobile and desktop) ──────────────────────
const sb = document.getElementById('sb') || document.getElementById('sidebar');
const mn = document.getElementById('main');
const tg = document.getElementById('tog') || document.getElementById('toggleSidebar');
const bk = document.getElementById('bk') || document.getElementById('backdrop');

if (tg && sb) {
    tg.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            // Mobile: slide in/out
            sb.classList.toggle('mobile-open');
            if (bk) bk.classList.toggle('show');
        } else {
            // Desktop: collapse/expand sidebar
            sb.classList.toggle('sb-collapsed');
            if (mn) mn.classList.toggle('sb-expanded');
        }
    });
}
if (bk) {
    bk.addEventListener('click', () => {
        sb && sb.classList.remove('mobile-open');
        bk.classList.remove('show');
    });
}

// ── Search bar: filter table rows live ───────────────────────────
const searchInput = document.querySelector('.hdr-search input');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        // Try to filter the main data table
        document.querySelectorAll('table.data tbody tr, table.dt tbody tr').forEach(row => {
            if (!q) { row.style.display = ''; return; }
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
    // Clear on Escape
    searchInput.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        }
    });
}

// ── Chart.js dark defaults ────────────────────────────────────────
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Inter',system-ui,sans-serif";
    Chart.defaults.color = '#B0B8D0';
    Chart.defaults.borderColor = 'rgba(255,255,255,.06)';
    Chart.defaults.plugins.tooltip.backgroundColor = '#0A0C18';
    Chart.defaults.plugins.tooltip.titleColor = '#F0F2FA';
    Chart.defaults.plugins.tooltip.bodyColor = '#B0B8D0';
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(255,255,255,.14)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.padding = 12;
}

// ── Weekly attendance chart ───────────────────────────────────────
const wE = document.getElementById('wChart') || document.getElementById('weeklyChart');
if (wE) {
    const ctx = wE.getContext('2d');
    const g = ctx.createLinearGradient(0, 0, 0, 260);
    g.addColorStop(0, 'rgba(129,140,248,.28)');
    g.addColorStop(1, 'rgba(129,140,248,0)');
    new Chart(wE, {
        type: 'line',
        data: {
            labels: window.wL || window.weeklyLabels || [],
            datasets: [{
                data: window.wD || window.weeklyData || [],
                borderColor: '#818CF8', backgroundColor: g,
                borderWidth: 2.5, tension: .42, fill: true,
                pointBackgroundColor: '#818CF8', pointBorderColor: '#05060D',
                pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, border: { display: false }, grid: { color: 'rgba(255,255,255,.04)' }, ticks: { callback: v => v + '%', font: { size: 12 }, padding: 8 } },
                x: { border: { display: false }, grid: { display: false }, ticks: { font: { size: 12 }, padding: 6 } }
            }
        }
    });
}

// ── Doughnut ─────────────────────────────────────────────────────
const pE = document.getElementById('pChart') || document.getElementById('pctChart');
if (pE) {
    const r = window.aR || window.attendanceRate || 0;
    new Chart(pE, {
        type: 'doughnut',
        data: { datasets: [{ data: [r, 100 - r], backgroundColor: ['#818CF8', 'rgba(255,255,255,.07)'], borderWidth: 0, borderRadius: 4, spacing: 2 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '76%', plugins: { legend: { display: false }, tooltip: { enabled: false } } }
    });
}

// ── Confirm on delete ─────────────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', ev => { if (!confirm(el.dataset.confirm)) ev.preventDefault(); });
});
