<?php
include "db.php";

// Récupérer tous les employés
$sql = "SELECT * FROM employees";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pointage</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Pointage des employés</h2>

<form action="insert.php" method="POST"> <!--quand on clique sur envoyer , les donnees sont envoyees au fichier insert.php -->

    <label>Employé :</label><br>

    <select name="user_id" required> <!-- user_id : le nom de la donnée qui sera envoyée à insert.php-->

        <option value="">-- Choisir un employé --</option>

        <?php while($row = mysqli_fetch_assoc($result)){ ?>

            <option value="<?= $row['user_id']; ?>">  <!-- value : la valeur envoyer si l'utilisateur choisit ce employe -->
                <?= $row['first_name']; ?> <?= $row['last_name']; ?>
            </option>

        <?php } ?> <!-- Ferme un bloc PHP ouvert avec une accolade {. -->

    </select>

    <br><br>

    <label>Type de pointage :</label><br>

    <select name="type">

        <option value="IN">Entrée (IN)</option>

        <option value="OUT">Sortie (OUT)</option>

    </select>

    <br><br>

    <button type="submit">
        Pointer
    </button>

</form>

</body>
</html>