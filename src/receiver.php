<?php

namespace Fernandod1\RabbitmqRpc;

require_once '../vendor/autoload.php';


$receiver = new RpcReceiver();
$receiver->listen();
