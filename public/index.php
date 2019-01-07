<?php

use Aura\Di\ContainerBuilder;
use GuzzleHttp\Psr7\ServerRequest;
use Narrowspark\HttpEmitter\SapiEmitter;

require '../vendor/autoload.php';

// todo cache container for production

$config = [
    'Nessie\Core\Config\Config',
];

$relay = (new ContainerBuilder())->newConfiguredInstance($config)->get('nessie/project:relay');

$response = $relay->handle(ServerRequest::fromGlobals());

(new SapiEmitter())->emit($response);