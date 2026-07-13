<?php
include "db.php";

// Vérifier que le formulaire est envoyé
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Récupérer les données du formulaire
    $user_id = $_POST['user_id'];
    $type = $_POST['type'];

    // Date et heure actuelles
    $date = date("Y-m-d");
    $time = date("H:i:s");

    // Requête d'insertion
    $sql = "INSERT INTO attendance (user_id, date, time, type)
            VALUES ('$user_id', '$date', '$time', '$type')";

    if (mysqli_query($conn, $sql)) {

        // Retour vers la page de pointage
        header("Location: attendance.php?success=1");
        exit;

    } else {

        echo "Erreur : " . mysqli_error($conn);

    }

} else {

    header("Location: attendance.php");
    exit;

}
?>