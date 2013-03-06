<?php
$options = getopt('he::',array('helpenv::'));

print "FluxAPI command line tools.\n";

if (isset($options['h'])) {
    help();
} else {
    print "Type -h for a list of available commands and options.\n";
}

$loader = require __DIR__ . '/../vendor/autoload.php';

// create application
$app = new Silex\Application();

$config = json_decode(file_get_contents('../config/development.json'),TRUE);

if ($config['debug'] == TRUE) {
    $app['debug'] = TRUE;
}

$fluxApi = new FluxAPI\Api($app,$config);

function help($command = NULL)
{
    print "\n";
    if (empty($command)) {
        print "Options:\n\n";
        print "-h\t\t\tShow this help\n";
        print "-e --env [environment]\tSet the environment. Default is 'production'.\n";

        print "\nCommands:\n\n";
    }
    print "\n";
}