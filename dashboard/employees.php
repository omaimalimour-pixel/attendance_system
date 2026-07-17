<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

include "../db.php";

$search="";

if(isset($_GET['search'])){
    $search=mysqli_real_escape_string($conn,$_GET['search']);
}

$sql="SELECT * FROM employees";

if($search!=""){
    $sql.=" WHERE
        user_id LIKE '%$search%' OR
        first_name LIKE '%$search%' OR
        last_name LIKE '%$search%' OR
        department LIKE '%$search%' OR
        position LIKE '%$search%'";
}

$sql.=" ORDER BY id DESC";

$result=mysqli_query($conn,$sql);

$total=mysqli_num_rows(mysqli_query($conn,"SELECT * FROM employees"));

$departments=mysqli_num_rows(mysqli_query($conn,"
SELECT DISTINCT department
FROM employees
"));

$newThisMonth=mysqli_num_rows(mysqli_query($conn,"
SELECT *
FROM employees
WHERE MONTH(created_at)=MONTH(CURDATE())
AND YEAR(created_at)=YEAR(CURDATE())
"));
?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Employees</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{

background:#eef2f7;

color:#374151;

}

.container{

width:96%;

margin:35px auto;

}

.card{

background:#fff;

border-radius:18px;

overflow:hidden;

box-shadow:0 10px 35px rgba(0,0,0,.08);

}

.page-header{

display:flex;

justify-content:space-between;

align-items:center;

padding:30px;

border-bottom:1px solid #eee;

}

.page-header h1{

font-size:32px;

color:#111827;

}

.page-header p{

margin-top:6px;

color:#6b7280;

}

.header-buttons{

display:flex;

gap:12px;

}

.btn{

padding:12px 20px;

border:none;

border-radius:10px;

cursor:pointer;

background:#2563eb;

color:white;

text-decoration:none;

font-weight:600;

transition:.3s;

}

.btn:hover{

transform:translateY(-2px);

}

.btn-success{

background:#10b981;

}

.btn-warning{

background:#f59e0b;

}

.reset{

background:#6b7280;

}
.stats{

display:grid;

grid-template-columns:repeat(3,1fr);

gap:20px;

padding:30px;

}

.stat-card{

background:#f8fafc;

border-radius:15px;

padding:22px;

display:flex;

align-items:center;

gap:18px;

transition:.3s;

border:1px solid #e5e7eb;

}

.stat-card:hover{

transform:translateY(-5px);

box-shadow:0 10px 25px rgba(0,0,0,.08);

}

.icon{

width:65px;

height:65px;

border-radius:50%;

display:flex;

justify-content:center;

align-items:center;

font-size:30px;

}

.blue{

background:#dbeafe;

}

.green{

background:#dcfce7;

}

.orange{

background:#fef3c7;

}

.search-box{

display:flex;

gap:12px;

padding:30px;

}

.search-box input{

flex:1;

padding:13px 15px;

border:1px solid #d1d5db;

border-radius:10px;

outline:none;

font-size:15px;

}

.search-box input:focus{

border-color:#2563eb;

box-shadow:0 0 0 3px rgba(37,99,235,.15);

}

.table-container{

padding:0 30px 30px;

overflow:auto;

}

table{

width:100%;

border-collapse:collapse;

background:white;

}

thead{

background:#2563eb;

color:white;

}

th{

padding:16px;

text-align:left;

font-size:14px;

}

td{

padding:16px;

border-bottom:1px solid #ececec;

vertical-align:middle;

}

tbody tr{

transition:.25s;

}

tbody tr:hover{

background:#f8fbff;

}
.employee-info{

display:flex;

align-items:center;

gap:12px;

}

.avatar{

width:45px;

height:45px;

border-radius:50%;

background:#2563eb;

color:white;

display:flex;

justify-content:center;

align-items:center;

font-weight:bold;

font-size:18px;

}

.employee-name{

font-weight:600;

color:#111827;

}

.employee-id{

font-size:13px;

color:#6b7280;

margin-top:3px;

}

.badge{

display:inline-block;

padding:6px 14px;

border-radius:30px;

font-size:13px;

font-weight:600;

}

.badge-blue{

background:#dbeafe;

color:#2563eb;

}

.badge-green{

background:#dcfce7;

color:#15803d;

}

.action{

width:36px;

height:36px;

display:inline-flex;

justify-content:center;

align-items:center;

border-radius:8px;

text-decoration:none;

margin-right:5px;

color:white;

transition:.3s;

}

.edit{

background:#10b981;

}

.delete{

background:#ef4444;

}

.action:hover{

transform:scale(1.08);

}

.empty{

text-align:center;

padding:35px;

color:#9ca3af;

font-size:15px;

}

@media(max-width:1000px){

.page-header{

flex-direction:column;

align-items:flex-start;

gap:20px;

}

.header-buttons{

flex-wrap:wrap;

}

.stats{

grid-template-columns:1fr;

}

.search-box{

flex-direction:column;

}

table{

min-width:900px;

}

}

</style>

</head>

<body>

<div class="container">

<div class="card">

<div class="page-header">

<div>

<h1>👥 Employees</h1>

<p>Manage all employees from one place.</p>

</div>

<div class="header-buttons">

<a href="add_employee.php" class="btn">

➕ Add Employee

</a>

<button class="btn btn-success">

📥 Import

</button>

<button class="btn btn-warning">

📤 Export

</button>

</div>

</div>

<div class="stats">

<div class="stat-card">

<div class="icon blue">👥</div>

<div>

<h2><?= $total ?></h2>

<p>Total Employees</p>

</div>

</div>

<div class="stat-card">

<div class="icon green">🏢</div>

<div>

<h2><?= $departments ?></h2>

<p>Departments</p>

</div>

</div>

<div class="stat-card">

<div class="icon orange">⭐</div>

<div>

<h2><?= $newThisMonth ?></h2>

<p>New This Month</p>

</div>

</div>

</div>
<form method="GET" class="search-box">

    <input
        type="text"
        name="search"
        placeholder="🔍 Search employee..."
        value="<?= htmlspecialchars($search); ?>">

    <button type="submit" class="btn">
        Search
    </button>

    <a href="employees.php" class="btn reset">
        Reset
    </a>

</form>

<div class="table-container">

<table>

<thead>

<tr>

<th>ID</th>
<th>User ID</th>
<th>Employee</th>
<th>Department</th>
<th>Position</th>
<th>Created</th>
<th>Status</th>
<th>Actions</th>

</tr>

</thead>

<tbody>

<?php if(mysqli_num_rows($result)>0){ ?>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?= $row['id']; ?></td>

<td><?= $row['user_id']; ?></td>

<td>

<div class="employee-info">

<div class="avatar">

<?= strtoupper(substr($row['first_name'],0,1)); ?>

</div>

<div>

<div class="employee-name">

<?= htmlspecialchars($row['first_name'] ?? ''); ?>

<?= htmlspecialchars($row['last_name'] ?? ''); ?>

</div>

<div class="employee-id">

User ID : <?= $row['user_id']; ?>

</div>

</div>

</div>

</td>

<td>

<span class="badge badge-blue">

<?= htmlspecialchars($row['department'] ?? '---'); ?>

</span>

</td>

<td>

<?= htmlspecialchars($row['position'] ?? '---'); ?>
</td>

<td>

<?= date("d/m/Y",strtotime($row['created_at'])); ?>

</td>

<td>

<span class="badge badge-green">

Active

</span>

</td>

<td>

<a
href="edit_employee.php?id=<?= $row['id']; ?>"
class="action edit"
title="Edit">

✏️

</a>

<a
href="delete_employee.php?id=<?= $row['id']; ?>"
class="action delete"
title="Delete"
onclick="return confirm('Delete this employee ?')">

🗑️

</a>

</td>

</tr>

<?php } ?>

<?php } else { ?>

<tr>

<td colspan="8" class="empty">

No employee found.

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</body>

</html>