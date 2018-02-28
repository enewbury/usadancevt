<?php
define('ROOT_URL', __DIR__);
$secrets = require 'secrets.php';

$devSettings =  [
    'settings' => [
        // Slim Settings
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => true,
        'mode'=>'development',
        
        'cache.dir'=>__DIR__ . '/cache',

        // View settings
        'view' => [
            'template_path' => __DIR__ . '/views/templates',
            'twig' => [
                'cache' => __DIR__ . '/cache/twig',
                'debug' => true,
                'auto_reload' => true,
            ],
        ],

        // monolog settings
        'logger' => [
            'name' => 'usaDanceVt',
            'level'=>\Monolog\Logger::DEBUG,
            'path' => __DIR__ . '/log/dancevt.log',
        ],

        //doctrine settings
        'doctrine' => [
            'driver' => 'pdo_mysql',
            'user' => $secrets['dev']['db']['user'],
            'password' => $secrets['dev']['db']['user'],
            'dbname' => $secrets['dev']['db']['dbname'],
            'host' => $secrets['dev']['db']['host'],
            'port'=> 8889,
            'model_paths' => [
                __DIR__.'/src/main/Models/Persistence'
            ]
        ],

        'mail' => [
            'host' => 'smtp.gmail.com',
            'username' => $secrets['dev']['mail']['username'],
            'password' =>  $secrets['dev']['mail']['password'],
            'name' =>  $secrets['dev']['mail']['name']
        ]
    ],
];

$productionSettings = [
    'settings' => [
        // Slim Settings
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => false,
        'routerCacheFile'=>__DIR__ . '/cache/router_cache.php',

        'mode'=>'production',

        'cache.dir'=>__DIR__ . '/cache',

        // View settings
        'view' => [
            'template_path' => __DIR__ . '/views/templates',
            'twig' => [
                'cache' => __DIR__ . '/cache/twig',
                'debug' => false,
                'auto_reload' => false,
            ],
        ],

        // monolog settings
        'logger' => [
            'name' => 'usaDanceVt',
            'level'=>\Monolog\Logger::INFO,
            'path' => __DIR__ . '/log/dancevt.log',
        ],

        //doctrine settings
        'doctrine' => [
            'driver' => 'pdo_mysql',
            'user' =>  $secrets['prod']['db']['user'],
            'password' => $secrets['prod']['db']['password'],
            'dbname' => $secrets['prod']['db']['dbname'],
            'host' => $secrets['prod']['db']['host'],
            'model_paths' => [
                __DIR__.'/src/main/Models/Persistence'
            ]
        ],

        'mail' => [
            'host' => 'smtp.gmail.com',
            'username' => $secrets['prod']['mail']['username'],
            'password' => $secrets['prod']['mail']['password'],
            'name' => $secrets['prod']['mail']['name']
        ]
    ],
];
return(isSet($_SERVER['PHP_ENV']) && $_SERVER['PHP_ENV'] === 'dev') ? $devSettings : $productionSettings;

