<?php

require 'vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

$zk = new ZKTeco('192.168.100.201', 4370);

$zk->connect();

$attendance = $zk->getAttendance();

echo "<pre>";
print_r($attendance);