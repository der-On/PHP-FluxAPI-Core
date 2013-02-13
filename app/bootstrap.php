<?php
$loader = require __DIR__ . '/../vendor/autoload.php';

// create application
$app = new Silex\Application();

$fluxApi = new FluxAPI\Api($app);

$app->match('/',function() {
    return 'Welcome to the PHP-FluxAPI';
});