<?php

namespace Fernandod1\RabbitmqRpc;

require_once '../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable('../');
$dotenv->safeLoad();

$credentials = [
    'username' => $_ENV["TRIED_USERNAME"],
    'password' => $_ENV["TRIED_PASSWORD"]
];

$sender = new RpcSender();
$response = $sender->execute($credentials);

$json = json_decode($response);
($json->status) ? print("Auth success") : print("Auth fail");
