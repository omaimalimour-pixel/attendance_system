<?php

include "db.php";
require "vendor/autoload.php";

use Rats\Zkteco\Lib\ZKTeco;

// Connexion à la machine
$zk = new ZKTeco("192.168.100.201", 4370);

if (!$zk->connect()) {
    die("Connexion à la machine impossible !");
}

echo "<h2>Synchronisation des pointages</h2>";

// Date d'aujourd'hui
$today = date("Y-m-d");

// Récupérer les pointages
$attendances = $zk->getAttendance();

echo "Nombre total de pointages récupérés : " . count($attendances) . "<br><br>";

foreach ($attendances as $a) {

    // Vérifier que le timestamp existe
    if (!isset($a['timestamp'])) {
        continue;
    }

    $datetime = strtotime($a['timestamp']);

    $date = date("Y-m-d", $datetime);
    $time = date("H:i:s", $datetime);

    // Ignorer uniquement les pointages avant aujourd'hui
    if ($date < $today) {
        continue;
    }

    $user_id = $a['id'];

    // Type IN / OUT
    $type = ($a['type'] == 0) ? "IN" : "OUT";

    $device_id = "ZKTeco";

    // Stocker les données originales
    $data = mysqli_real_escape_string($conn, json_encode($a));

    // Vérifier si le pointage existe déjà
    $check = mysqli_query($conn, "
        SELECT id
        FROM attendance
        WHERE user_id='$user_id'
        AND date='$date'
        AND time='$time'
        AND type='$type'
    ");

    if (!$check) {
        echo "Erreur SELECT : " . mysqli_error($conn) . "<br>";
        continue;
    }

    // Si déjà importé
    if (mysqli_num_rows($check) > 0) {
        echo "Déjà importé : User $user_id - $date $time<br>";
        continue;
    }

    // Insertion dans attendance
    $sql = "
        INSERT INTO attendance
        (user_id, date, time, type, device_id, data)
        VALUES
        (
            '$user_id',
            '$date',
            '$time',
            '$type',
            '$device_id',
            '$data'
        )
    ";

    if (mysqli_query($conn, $sql)) {

        echo "✅ Pointage importé : User $user_id | $date | $time | $type <br>";

    } else {

        echo "❌ Erreur INSERT : " . mysqli_error($conn) . "<br>";

    }
}

$zk->disconnect();

echo "<br><h3>Synchronisation terminée.</h3>";

?>