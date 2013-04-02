#!/usr/bin/env php
<?php
// app/console

use Symfony\Component\Console\Application;

// create application
$silex_app = new Silex\Application();

$config = json_decode(file_get_contents('../config/development.json'),TRUE);

if ($config['debug'] == TRUE) {
    $app['debug'] = TRUE;
}

$cli_app = new Application();

$cli = new \FluxAPI\Cli($silex_app, $cli_app, $config);