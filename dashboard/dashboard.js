// ==============================
// SIDEBAR TOGGLE
// ==============================

const sidebar = document.getElementById("sidebar");
const main = document.getElementById("main");
const toggleBtn = document.getElementById("toggleSidebar");
const backdrop = document.getElementById("backdrop");

if (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
        if (window.innerWidth < 992) {
            sidebar.classList.toggle("mobile-open");
            backdrop.classList.toggle("show");
        } else {
            sidebar.classList.toggle("collapsed");
            main.classList.toggle("expanded");
        }
    });
}

if (backdrop) {
    backdrop.addEventListener("click", function () {
        sidebar.classList.remove("mobile-open");
        backdrop.classList.remove("show");
    });
}

// ==============================
// WEEKLY ATTENDANCE CHART
// ==============================

const monthlyCanvas = document.getElementById("monthlyChart");

if (monthlyCanvas) {
    const labels = window.weeklyLabels || ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
    const data = window.weeklyData || [0,0,0,0,0,0,0];

    new Chart(monthlyCanvas, {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                label: "Attendance Rate (%)",
                data: data,
                borderColor: "#22C55E",
                backgroundColor: "rgba(34,197,94,0.08)",
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: "#22C55E",
                pointBorderColor: "#fff",
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#0F172A",
                    titleFont: { size: 12, weight: "600" },
                    bodyFont: { size: 13 },
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(ctx) {
                            return ctx.parsed.y + "% attendance";
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: "#F1F5F9" },
                    ticks: {
                        callback: function(v) { return v + "%"; },
                        font: { size: 11, weight: "500" },
                        color: "#94A3B8"
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11, weight: "600" },
                        color: "#64748B"
                    }
                }
            }
        }
    });
}

// ==============================
// TODAY'S PERCENTAGE DOUGHNUT
// ==============================

const pctCanvas = document.getElementById("pctChart");

if (pctCanvas) {
    const rate = window.attendanceRate || 0;
    const absent = 100 - rate;

    new Chart(pctCanvas, {
        type: "doughnut",
        data: {
            labels: ["Present", "Absent"],
            datasets: [{
                data: [rate, absent],
                backgroundColor: ["#2563EB", "#EEF2F7"],
                borderWidth: 0,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "75%",
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#0F172A",
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(ctx) {
                            return ctx.label + ": " + ctx.parsed + "%";
                        }
                    }
                }
            }
        }
    });
}

// ==============================
// CONFIRM DELETE ACTIONS
// ==============================

document.querySelectorAll("[data-confirm]").forEach(function(el) {
    el.addEventListener("click", function(e) {
        if (!confirm(el.dataset.confirm)) {
            e.preventDefault();
        }
    });
});
