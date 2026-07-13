<?php
include "db.php";

$where = "WHERE 1=1";

if (!empty($_GET['date'])) { /*empty: une fonction qui revois un BOOLEEN*/
    $date = $_GET['date'];
    $where .= " AND attendance.date='$date'";
}

if (!empty($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $where .= " AND attendance.user_id='$user_id'";
}

if (!empty($_GET['type'])) {
    $type = $_GET['type'];
    $where .= " AND attendance.type='$type'";
}

$sql = "SELECT attendance.id,
               employees.first_name,
               employees.last_name,
               attendance.user_id,
               attendance.date,
               attendance.time,
               attendance.type
        FROM attendance
        INNER JOIN employees
        ON attendance.user_id = employees.user_id
        $where
        ORDER BY attendance.date DESC, attendance.time DESC";

echo "<h3>Requête SQL :</h3>";
echo "<pre>$sql</pre>";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Erreur SQL : " . mysqli_error($conn)); /* afficher ce message Erreur SQL puis arreter immediatement */
}

echo "<h3>Nombre de résultats : " . mysqli_num_rows($result) . "</h3>";

$employees = mysqli_query($conn, "SELECT * FROM employees");
?>

<h2>Liste des pointages</h2>

<form method="GET">

    <label>Employé :</label>
    <select name="user_id">
        <option value="">Tous</option> <!-- Le name est le nom de la donnée qui sera envoyée lorsque le formulaire est soumis-->

        <?php while($emp = mysqli_fetch_assoc($employees)){ ?>
            <option value="<?= $emp['user_id']; ?>">
                <?= $emp['first_name']." ".$emp['last_name']; ?>
            </option>
        <?php } ?>

    </select>

    <label>Date :</label>
    <input type="date" name="date">

    <label>Type :</label>

    <select name="type">
        <option value="">Tous</option>
        <option value="IN">IN</option>
        <option value="OUT">OUT</option>
    </select>

    <button type="submit">Rechercher</button>

</form>

<br>

<table border="1">
    <tr>
        <th>Nom</th>
        <th>User ID</th>
        <th>Date</th>
        <th>Heure</th>
        <th>Type</th>
    </tr>

    <?php while($row = mysqli_fetch_assoc($result)){ ?>
    <tr>
        <td><?= $row['first_name']." ".$row['last_name']; ?></td>
        <td><?= $row['user_id']; ?></td>
        <td><?= $row['date']; ?></td>
        <td><?= $row['time']; ?></td>
        <td><?= $row['type']; ?></td>
    </tr>
    <?php } ?>

</table>