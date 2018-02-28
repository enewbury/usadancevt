<?php
namespace EricNewbury\DanceVT;

require __DIR__ . '/../vendor/autoload.php';
if (!file_exists(__DIR__ . '/../app/cache')) {
mkdir(__DIR__ . '/../app/cache',0755, true);
} 

session_start();
date_default_timezone_set('America/New_York');

// Instantiate the app
$settings = require __DIR__ . '/../app/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../app/dependencies.php';

// Register middleware
require __DIR__ . '/../app/middleware.php';

// Register routes
require __DIR__ . '/../app/routes.php';

// Run!
$app->run();


