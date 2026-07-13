<?php
include "db.php";

$id = $_GET['id'];

$sql = "SELECT * FROM employees WHERE id='$id'";
$result = mysqli_query($conn, $sql);

$employee = mysqli_fetch_assoc($result);

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $department = $_POST['department'];
    $position = $_POST['position'];

    $sql = "UPDATE employees
            SET user_id='$user_id',
                first_name='$first_name',
                last_name='$last_name',
                department='$department',
                position='$position'
            WHERE id='$id'";

    if(mysqli_query($conn,$sql)){
        header("Location: employees.php");
        exit;
    }else{
        echo mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modifier un employé</title>
</head>
<body>

<h2>Modifier un employé</h2>

<form method="POST">

<input type="number" name="user_id" value="<?= $employee['user_id']; ?>" required>


<input type="text" name="first_name" value="<?= $employee['first_name']; ?>" required>

<input type="text" name="last_name" value="<?= $employee['last_name']; ?>" required>


<input type="text" name="department" value="<?= $employee['department']; ?>" required>


<input type="text" name="position" value="<?= $employee['position']; ?>" required>


<button type="submit">Mettre à jour</button>

</form>

</body>
</html>