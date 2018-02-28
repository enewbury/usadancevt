<?php
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;

// replace with file to your own project bootstrap
require_once './vendor/autoload.php';

// replace with mechanism to retrieve EntityManager in your app
$settings = require './app/settings.php';
$settings = $settings['settings'];
$isDevMode = ($settings["mode"] == "development") ? true : false;
$settings['doctrine']['user'] = "root";
$settings['doctrine']['password'] = "root";
$settings['doctrine']['dbname'] = "dancevt";

$config = Setup::createAnnotationMetadataConfiguration($settings['doctrine']['model_paths'], $isDevMode, $settings['cache.dir']);
$config->setNamingStrategy(new UnderscoreNamingStrategy());

$entityManager = EntityManager::create($settings['doctrine'], $config);

return ConsoleRunner::createHelperSet($entityManager);