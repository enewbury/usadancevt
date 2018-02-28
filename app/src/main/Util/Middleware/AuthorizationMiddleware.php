<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Util\Middleware;


use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;

interface AuthorizationMiddleware
{

    function assertLoggedIn(ServerRequestInterface $httpReq, Response $httpRes, $next);
    function assertAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next);
}