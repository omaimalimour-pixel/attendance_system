/* ============================================
   CHRONOX DASHBOARD — INTERACTIONS
   ============================================ */

// ===== SIDEBAR TOGGLE =====
const sidebar = document.getElementById('sidebar');
const main = document.getElementById('main');
const toggle = document.getElementById('toggleSidebar');
const backdrop = document.getElementById('backdrop');

if (toggle) {
    toggle.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            sidebar.classList.toggle('mobile-open');
            backdrop.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        }
    });
}
if (backdrop) {
    backdrop.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        backdrop.classList.remove('show');
    });
}

// ===== CHART.JS DARK DEFAULTS =====
if (typeof Chart !== 'undefined') {
    Chart.defaults.color = '#9AA2C0';
    Chart.defaults.borderColor = 'rgba(255,255,255,.06)';
    Chart.defaults.plugins.tooltip.backgroundColor = '#1A1D2E';
    Chart.defaults.plugins.tooltip.titleColor = '#EDF0FA';
    Chart.defaults.plugins.tooltip.bodyColor = '#9AA2C0';
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(255,255,255,.12)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.padding = 12;
}

// ===== WEEKLY CHART =====
const weeklyEl = document.getElementById('weeklyChart');
if (weeklyEl) {
    const labels = window.weeklyLabels || ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    const data = window.weeklyData || [0,0,0,0,0,0,0];
    new Chart(weeklyEl, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Attendance %',
                data,
                borderColor: '#818CF8',
                backgroundColor: 'rgba(129,140,248,.08)',
                borderWidth: 2.5,
                tension: .4,
                fill: true,
                pointBackgroundColor: '#818CF8',
                pointBorderColor: '#0A0C18',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,.04)' }, ticks: { callback: v => v+'%' } },
                x: { grid: { display: false } }
            }
        }
    });
}

// ===== DOUGHNUT CHART =====
const pctEl = document.getElementById('pctChart');
if (pctEl) {
    const rate = window.attendanceRate || 0;
    new Chart(pctEl, {
        type: 'doughnut',
        data: {
            labels: ['Present','Absent'],
            datasets: [{ data: [rate, 100-rate], backgroundColor: ['#6366F1','rgba(255,255,255,.06)'], borderWidth: 0, borderRadius: 6 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '76%', plugins: { legend: { display: false } } }
    });
}

// ===== CONFIRM DELETE =====
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
});
