<?php

include "db.php";
require "vendor/autoload.php";

use Rats\Zkteco\Lib\ZKTeco;

$zk = new ZKTeco("192.168.100.201", 4370);

if (!$zk->connect()) {
    die("Connexion impossible !");
}

echo "<h2>Synchronisation des pointages</h2>";

$today = date("Y-m-d");

$attendances = $zk->getAttendance();

echo "Nombre total de pointages récupérés : " . count($attendances) . "<br><br>";

foreach ($attendances as $a) {

    if (!isset($a['timestamp'])) {
        continue;
    }

    $datetime = strtotime($a['timestamp']);
    $date = date("Y-m-d", $datetime);
    $time = date("H:i:s", $datetime);

    if ($date < $today) {
        continue;
    }

    // Employee ID envoyé par la machine
    $user_id = (int)$a['id'];

    // Vérifier que l'employé existe
    $emp = mysqli_query($conn, "SELECT id FROM employees WHERE user_id = $user_id");

    if (mysqli_num_rows($emp) == 0) {
        echo "Employé $user_id introuvable<br>";
        continue;
    }

    $type = ($a['type'] == 0) ? "IN" : "OUT";
    $device_id = "ZKTeco";
    $data = mysqli_real_escape_string($conn, json_encode($a));

    // Vérifier si le pointage existe déjà
    $check = mysqli_query($conn, "
        SELECT id
        FROM attendance
        WHERE user_id = $user_id
        AND date = '$date'
        AND time = '$time'
        AND type = '$type'
    ");

    if (mysqli_num_rows($check) > 0) {
        continue;
    }

    // Insertion
    mysqli_query($conn, "
        INSERT INTO attendance
        (user_id, date, time, type, device_id, data)
        VALUES
        ($user_id, '$date', '$time', '$type', '$device_id', '$data')
    ");

    echo "✅ Pointage importé : $user_id - $date $time<br>";
}

$zk->disconnect();

echo "<h3>Synchronisation terminée.</h3>";