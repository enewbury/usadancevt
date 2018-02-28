<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Util\Middleware;


use EricNewbury\DanceVT\Models\Exceptions\Error404Exception;
use EricNewbury\DanceVT\Util\AuthorizationTool;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Router;
use Slim\Views\Twig;

class HtmlAuthorizationMiddleware implements AuthorizationMiddleware
{
    /** @var  AuthorizationTool $authorizationTool */
    private $authorizationTool;

    /** @var  Twig $view */
    private $view;

    /** @var  RouterInterface $router */
    private $router;

    /**
     * HtmlAuthorizationMiddleware constructor.
     * @param AuthorizationTool $authorizationTool
     * @param Twig $view
     */
    public function __construct(AuthorizationTool $authorizationTool, Twig $view, RouterInterface $router)
    {
        $this->authorizationTool = $authorizationTool;
        $this->view = $view;
        $this->router = $router;
    }


    private function renderErrorPage(ResponseInterface $httpRes, $message){
        $httpRes = $httpRes->withStatus(401);
        return $this->view->render($httpRes, 'notice.twig', [
            'title'=>'Permission Denied',
            'notice'=>$message,
            'linkText'=>'Login',
            'linkHref'=>$this->router->pathFor('login')
        ]);
    }

    /**
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param callable $next
     * @return ResponseInterface
     */
    public function assertLoggedIn(ServerRequestInterface $httpReq, Response $httpRes, $next){
        if($this->authorizationTool->isLoggedIn()){
            return $next($httpReq, $httpRes);
        }
        else{
            return $httpRes->withRedirect($this->router->pathFor('login'));
        }
    }

    public function assertAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isAdmin($user)){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->view->render($httpRes->withStatus(401), 'notice.twig', [
                'title'=>'Permission Denied',
                'notice'=>$this->authorizationTool->mustBeAdminMessage()
            ]);
        }
    }

    public function assertOrganizationAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isOrganizationAdmin($user)){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->view->render($httpRes->withStatus(401), 'notice.twig', [
                'title'=>'Permission Denied',
                'notice'=>$this->authorizationTool->mustBeOrganizationAdminMessage()
            ]);
        }
    }

    public function assertInstructorAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isInstructorAdmin($user)){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->view->render($httpRes->withStatus(401), 'notice.twig', [
                'title'=>'Permission Denied',
                'notice'=>$this->authorizationTool->mustBeInstructorAdminMessage()
            ]);
        }
    }

    public function assertValidOrganizationAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next)
    {
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        $organizationId = $httpReq->getAttribute('routeInfo')[2]['id'];
        if ($this->authorizationTool->isValidOrganizationAdmin($user, $organizationId)) {
            return $next($httpReq, $httpRes);
        } else {
            return $this->view->render($httpRes->withStatus(401), 'notice.twig', [
                'title' => 'Permission Denied',
                'notice' => $this->authorizationTool->invalidAccountPermissionsMessaage()
            ]);
        }
    }
    public function assertValidInstructorAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next)
    {
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        $instructorId = $httpReq->getAttribute('routeInfo')[2]['id'];
        if ($this->authorizationTool->isValidInstructorAdmin($user, $instructorId)) {
            return $next($httpReq, $httpRes);
        } else {
            return $this->view->render($httpRes->withStatus(401), 'notice.twig', [
                'title' => 'Permission Denied',
                'notice' => $this->authorizationTool->invalidAccountPermissionsMessaage()
            ]);
        }
    }
    
    public function assertValidInstructorForEvent(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $instructorId = $httpReq->getAttribute('routeInfo')[2]['id'];
        $eventId = $httpReq->getAttribute('routeInfo')[2]['eventId'];
        
        if($this->authorizationTool->isValidInstructorForEvent($instructorId, $eventId)){
            return $next($httpReq, $httpRes);
        }
        else{
            throw new Error404Exception;
        }
    }
    public function assertValidOrganizationForEvent(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $organizationId = $httpReq->getAttribute('routeInfo')[2]['id'];
        $eventId = $httpReq->getAttribute('routeInfo')[2]['eventId'];

        if($this->authorizationTool->isValidOrganizationForEvent($organizationId, $eventId)){
            return $next($httpReq, $httpRes);
        }
        else{
            throw new Error404Exception;
        }
    }
}