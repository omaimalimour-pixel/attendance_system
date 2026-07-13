<?php
include "db.php";

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $department = $_POST['department'];
    $position = $_POST['position'];

    $sql = "INSERT INTO employees(user_id, first_name, last_name, department, position)
            VALUES('$user_id','$first_name','$last_name','$department','$position')";

    if(mysqli_query($conn,$sql)){
        echo "Employé ajouté avec succès.";
    }else{
        echo "Erreur : ".mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajouter un employé</title>
</head>
<body>

<h2>Ajouter un employé</h2>

<form method="POST">

    <input type="number" name="user_id" placeholder="User ID" required><br><br>

    <input type="text" name="first_name" placeholder="Prénom" required><br><br>

    <input type="text" name="last_name" placeholder="Nom" required><br><br>

    <input type="text" name="department" placeholder="Département" required><br><br>

    <input type="text" name="position" placeholder="Poste" required><br><br>

    <button type="submit">Ajouter</button>

</form>

</body>
</html>