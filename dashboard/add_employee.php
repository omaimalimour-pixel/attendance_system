<?php
include "../db.php";

$message = "";

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $user_id = mysqli_real_escape_string($conn,$_POST['user_id']);
    $first_name = mysqli_real_escape_string($conn,$_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn,$_POST['last_name']);
    $department = mysqli_real_escape_string($conn,$_POST['department']);
    $position = mysqli_real_escape_string($conn,$_POST['position']);

    $check = mysqli_query($conn,"SELECT * FROM employees WHERE user_id='$user_id'");

    if(mysqli_num_rows($check)>0){

        $message = "<div class='error'>Ce User ID existe déjà.</div>";

    }else{

        $sql="INSERT INTO employees(user_id,first_name,last_name,department,position)
              VALUES('$user_id','$first_name','$last_name','$department','$position')";

        if(mysqli_query($conn,$sql)){

            header("Location: employees.php");
            exit();

        }else{

            $message="<div class='error'>".mysqli_error($conn)."</div>";

        }

    }

}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<title>Add Employee</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Segoe UI,sans-serif;
}

body{
background:#f5f7fb;
}

.container{

width:500px;
margin:40px auto;

}

.card{

background:white;
padding:30px;
border-radius:15px;
box-shadow:0 8px 20px rgba(0,0,0,.08);

}

h2{

margin-bottom:25px;
color:#1e293b;

}

input{

width:100%;
padding:12px;
margin-bottom:15px;
border:1px solid #ddd;
border-radius:8px;
font-size:15px;

}

button{

width:100%;
padding:14px;
border:none;
background:#2563eb;
color:white;
font-size:16px;
border-radius:8px;
cursor:pointer;

}

button:hover{

background:#1d4ed8;

}

.back{

display:block;
margin-top:15px;
text-align:center;
text-decoration:none;
color:#2563eb;

}

.error{

background:#ffe4e4;
color:#b91c1c;
padding:10px;
margin-bottom:15px;
border-radius:8px;

}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h2>Add Employee</h2>

<?= $message; ?>

<form method="POST">

<input type="number" name="user_id" placeholder="User ID" required>

<input type="text" name="first_name" placeholder="First Name" required>

<input type="text" name="last_name" placeholder="Last Name" required>

<input type="text" name="department" placeholder="Department" required>

<input type="text" name="position" placeholder="Position" required>

<button type="submit">
Add Employee
</button>

</form>

<a href="employees.php" class="back">
← Back to Employees
</a>

</div>

</div>

</body>

</html>