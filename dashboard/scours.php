<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db.php';

/* ======================================================
   Date de démonstration
   ====================================================== */

$selectedDate = '2026-07-09'; // Mets ici la date de tes pointages

/* ======================================================
   Total des employés
   ====================================================== */

$sqlEmployees = "SELECT COUNT(*) AS total FROM employees";
$resultEmployees = mysqli_query($conn, $sqlEmployees);

$rowEmployees = mysqli_fetch_assoc($resultEmployees);
$totalEmployees = $rowEmployees['total'];

/* ======================================================
   Employés présents
   ====================================================== */

$sqlPresent = "SELECT COUNT(DISTINCT user_id) AS total
               FROM attendance
               WHERE date = '$selectedDate'
               AND type = 'IN'";

$resultPresent = mysqli_query($conn, $sqlPresent);

$rowPresent = mysqli_fetch_assoc($resultPresent);

$totalPresent = $rowPresent['total'];

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard</title>

<link rel="stylesheet" href="dashboard.css">

</head>

<body>

<div class="container">

    <aside class="sidebar">

        <h2>ZKTeco</h2>

        <ul>

            <li class="active">Dashboard</li>
            <li>Employés</li>
            <li>Pointages</li>
            <li>Utilisateurs</li>

        </ul>

    </aside>

    <main>

        <header class="topbar">

            <h1>Attendance Dashboard</h1>

            <span>

                Date analysée :
                <strong><?php echo $selectedDate; ?></strong>

            </span>

        </header>

        <section class="cards">

            <div class="card">

                <h3>Total Employés</h3>

                <p>

                    <?php echo $totalEmployees; ?>

                </p>

            </div>

            <div class="card">

                <h3>Présents</h3>

                <p>

                    <?php echo $totalPresent; ?>

                </p>

            </div>

        </section>

    </main>

</div>

</body>

</html>