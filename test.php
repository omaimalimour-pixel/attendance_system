<?php

require 'vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

$zk = new ZKTeco('192.168.100.201', 4370);

if($zk->connect()){
    echo "Connexion réussie";
}else{
    echo "Connexion échouée";
}