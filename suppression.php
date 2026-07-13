<?php

require "vendor/autoload.php";

use Rats\Zkteco\Lib\ZKTeco;

$zk = new ZKTeco("192.168.100.201", 4370);

$zk->connect();

$zk->connect();

$zk->disableDevice();

$result = $zk->removeUser(9);

$users = $zk->getUser();

$zk->enableDevice();
$zk->disconnect();

echo "<pre>";
print_r($users);
echo "</pre>";

var_dump($result);
var_dump(method_exists($zk, 'removeUser'));
var_dump(method_exists($zk, 'deleteUser'));