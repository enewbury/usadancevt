<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

//get logged in user

/** @var CustomContainer $c */
use EricNewbury\DanceVT\Util\Middleware\BaseMiddleware;
use EricNewbury\DanceVT\Util\Middleware\TrailingSlash;
use RKA\Middleware\IpAddress;

$c = $app->getContainer();

// SET LOGGED IN USER
$app->add(BaseMiddleware::class.':setCurrentUser');

//SET NAV COOKIE
$app->add(BaseMiddleware::class.':setNavCookie');

//Can now throw not found exceptions everywhere
$app->add(BaseMiddleware::class.':catchNotFoundExceptions');

$app->add(BaseMiddleware::class.':loadGlobalTemplateVariables');

$app->add(new IpAddress(true));

$app->add(new TrailingSlash()); // true adds the trailing slash (false removes it)