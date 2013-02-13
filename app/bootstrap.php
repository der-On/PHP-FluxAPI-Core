<?php
$loader = require __DIR__ . '/../vendor/autoload.php';

// create application
$app = new Silex\Application();

$fluxApi = new FluxAPI\Core();

$project = $fluxApi->api->loadProject(1);
$fluxApi->api->saveProject($project);
$fluxApi->api->deleteProject(1);
$fluxApi->api->updateProject(1);

$app->match('/',function() {
    return 'Welcome to the PHP-FluxAPI';
});