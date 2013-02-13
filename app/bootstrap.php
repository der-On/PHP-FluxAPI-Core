<?php
$loader = require __DIR__ . '/../vendor/autoload.php';

// create application
$app = new Silex\Application();

$fluxApi = new FluxAPI\Core();

var_dump($fluxApi->getPlugins());

$project = new Plugins\Core\Model\Project();
var_dump($project);

$app->match('/',function() {
    return 'Welcome to the PHP-FluxAPI';
});