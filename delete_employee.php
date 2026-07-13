<?php
include "db.php";

if(isset($_GET['id'])){

    $id = $_GET['id'];

    // récupérer le user_id
    $result = mysqli_query($conn,"SELECT user_id FROM employees WHERE id='$id'");
    $employee = mysqli_fetch_assoc($result);

    $user_id = $employee['user_id'];

    // supprimer les pointages
    mysqli_query($conn,"DELETE FROM attendance WHERE user_id='$user_id'");

    // supprimer l'employé
    mysqli_query($conn,"DELETE FROM employees WHERE id='$id'");

    header("Location: employees.php");
    exit;
}
?>