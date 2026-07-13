<?php
include "db.php";

$sql = "SELECT * FROM employees";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Liste des employés</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Liste des employés</h2>

<table>

<tr>  <!--table row-->
    <th>ID</th> <!--table header-->
    <th>User ID</th>
    <th>Prénom</th>
    <th>Nom</th>
    <th>Département</th>
    <th>Poste</th>
    <th>Actions</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>
    <td><?= $row['id']; ?></td> <!--table data-->
    <td><?= $row['user_id']; ?></td>
    <td><?= $row['first_name']; ?></td>
    <td><?= $row['last_name']; ?></td>
    <td><?= $row['department']; ?></td>
    <td><?= $row['position']; ?></td>

    <td>
        <a href="edit_employee.php?id=<?= $row['id']; ?>">Modifier</a> |
        <a href="delete_employee.php?id=<?= $row['id']; ?>" onclick="return confirm('Supprimer cet employé ?');">
            Supprimer
        </a>
    </td>
</tr>

<?php } ?>

</table>

</body>
</html>