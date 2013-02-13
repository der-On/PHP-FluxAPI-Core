<?php
$loader = require __DIR__ . '/../vendor/autoload.php';

// create application
$app = new Silex\Application();

$fluxApi = new FluxAPI\Api($app);

$project = $fluxApi->loadProject(1);
$fluxApi->saveProject($project);
$fluxApi->deleteProject(1);
$fluxApi->updateProject(1);

$app->match('/',function() {
    return 'Welcome to the PHP-FluxAPI';
});