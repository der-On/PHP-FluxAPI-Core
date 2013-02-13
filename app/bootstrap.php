<?php
$loader = require __DIR__ . '/../vendor/autoload.php';

// create application
$app = new Silex\Application();

$app->match('/',function() {
    return 'Welcome to the PHP-FluxAPI';
});