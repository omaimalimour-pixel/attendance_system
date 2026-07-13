// ==============================
// Lucide Icons
// ==============================

lucide.createIcons();

// ==============================
// WEEKLY ATTENDANCE
// ==============================

const monthlyCanvas = document.getElementById("monthlyChart");

if (monthlyCanvas) {

    new Chart(monthlyCanvas, {

        type: "line",

        data: {

            labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],

            datasets: [{

                label: "Attendance Rate (%)",

                data: [85, 90, 88, 95, 100, 80, 92], // Valeurs temporaires

                borderWidth: 3,

                tension: 0.4,

                fill: true

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    display: false

                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    max: 100

                }

            }

        }

    });

}

// ==============================
// TODAY PERCENTAGE
// ==============================

const pctCanvas = document.getElementById("pctChart");

if (pctCanvas) {

    new Chart(pctCanvas, {

        type: "doughnut",

        data: {

            labels: ["Present", "Absent"],

            datasets: [{

                data: [100, 0], // Valeurs temporaires

                borderWidth: 0

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            cutout: "70%",

            plugins: {

                legend: {

                    display: false

                }

            }

        }

    });

}