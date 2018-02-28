<?php
/**
 * Created by Eric Newbury.
 * Date: 4/22/16
 */

namespace EricNewbury\DanceVT\Util\Middleware;


use Slim\Http\Request;
use Slim\Http\Response;

class TrailingSlash
{

    public function __invoke(Request $request, Response $response, callable $next) {
        $uri = $request->getUri();
        $path = $uri->getPath();
        if ($path != '/' && substr($path, -1) == '/') {
            // permanently redirect paths with a trailing slash
            // to their non-trailing counterpart
            $uri = $uri->withPath(substr($path, 0, -1));
            return $response->withRedirect((string)$uri, 301);
        }

        return $next($request, $response);
    }
}