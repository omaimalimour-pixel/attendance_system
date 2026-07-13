<?php

include "db.php";
require "vendor/autoload.php";

use Rats\Zkteco\Lib\ZKTeco;


// Connexion ZKTeco
$zk = new ZKTeco("192.168.100.201", 4370);


if (!$zk->connect()) {
    die("Connexion à la machine impossible !");
}


echo "<h2>Liste des utilisateurs ZKTeco</h2>";


// Récupérer les utilisateurs
$users = $zk->getUser();


// Afficher tous les utilisateurs de la machine
echo "<h3>Tous les utilisateurs enregistrés dans la machine :</h3>";

echo "<table border='1' cellpadding='5'>";
echo "<tr>
        <th>User ID</th>
        <th>Nom</th>
        <th>Privilege</th>
      </tr>";


foreach ($users as $u) {

    echo "<tr>";
    echo "<td>".$u['userid']."</td>";
    echo "<td>".$u['name']."</td>";
    echo "<td>".$u['role']."</td>";
    echo "</tr>";

}


echo "</table><br><br>";





echo "<h2>Synchronisation des nouveaux utilisateurs</h2>";


// Récupérer les pointages
$attendances = $zk->getAttendance();


echo "Nombre total de pointages récupérés : "
     .count($attendances)."<br><br>";




// Date limite (à partir d'hier)
$limit_date = date("Y-m-d", strtotime("-1 day"));



// Tableau des utilisateurs qui ont pointé
$users_pointed = [];




// Chercher les utilisateurs qui ont pointé depuis hier
foreach ($attendances as $a) {


    if (!isset($a['timestamp'])) {
        continue;
    }


    $datetime = strtotime($a['timestamp']);


    // Date du pointage
    $date = date("Y-m-d", $datetime);



    // Ignorer les anciens pointages
    if ($date < $limit_date) {
        continue;
    }



    // Ajouter l'utilisateur
    $users_pointed[] = $a['id'];

}



// Supprimer les doublons
$users_pointed = array_unique($users_pointed);





echo "<h3>Utilisateurs ayant pointé depuis hier :</h3>";



foreach ($users as $u) {


    $user_id = $u['userid'];



    // seulement ceux qui ont pointé
    if (!in_array($user_id, $users_pointed)) {
        continue;
    }



    $name = $u['name'];



    echo "Utilisateur trouvé : "
        .$user_id." - ".$name."<br>";





    // Vérifier existence dans employees
    $check = mysqli_query($conn,
        "SELECT id 
         FROM employees 
         WHERE user_id='$user_id'"
    );



    if (!$check) {

        echo "Erreur SELECT : "
        .mysqli_error($conn)."<br>";

        continue;

    }




    // Existe déjà
    if (mysqli_num_rows($check) > 0) {


        echo "⏩ Déjà dans la base : "
        .$user_id." - ".$name."<br><br>";


    }

    else {



        // Nouveau utilisateur
        $sql = "

        INSERT INTO employees
        (user_id,name)

        VALUES

        ('$user_id','$name')

        ";




        if(mysqli_query($conn,$sql)){


            echo "✅ Nouveau utilisateur ajouté : "
            .$user_id." - ".$name."<br><br>";


        }
        else{


            echo "❌ Erreur INSERT : "
            .mysqli_error($conn)."<br>";

        }


    }



}



$zk->disconnect();


echo "<br><h3>Synchronisation terminée.</h3>";

?>