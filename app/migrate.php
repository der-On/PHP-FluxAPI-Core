<?php
$loader = require __DIR__ . '/../vendor/autoload.php';

// create application
$app = new Silex\Application();

$config = json_decode(file_get_contents('../config/development.json'),TRUE);

if ($config['debug'] == TRUE) {
    $app['debug'] = TRUE;
}

$fluxApi = new FluxAPI\Api($app,$config);

$fluxApi->migrate();