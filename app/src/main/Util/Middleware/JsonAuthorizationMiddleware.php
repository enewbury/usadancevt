<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Util\Middleware;


use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Util\AuthorizationTool;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;
use Slim\Views\Twig;

class JsonAuthorizationMiddleware implements AuthorizationMiddleware
{

    /** @var  AuthorizationTool $authorizationTool */
    private $authorizationTool;

    /** @var  Twig $view */
    private $view;

    /**
     * JsonAuthorizationMiddleware constructor.
     * @param AuthorizationTool $authorizationTool
     * @param Twig $view
     */
    public function __construct(AuthorizationTool $authorizationTool, Twig $view)
    {
        $this->authorizationTool = $authorizationTool;
        $this->view = $view;
    }


    private function generateFailResponse(Response $httpRes, $msg){
        $res = new FailResponse($msg);
        return $httpRes->withJson($res->toArray());
    }

    public function assertLoggedIn(ServerRequestInterface $httpReq, Response $httpRes, $next)
    {
        if($this->authorizationTool->isLoggedIn()){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->generateFailResponse($httpRes, $this->authorizationTool->mustBeLoggedInMessage());
        }
    }

    public function assertAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isAdmin($user)){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->generateFailResponse($httpRes, $this->authorizationTool->mustBeAdminMessage());
        }
    }

    public function assertAdminOrValidOrganizationAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isAdminOrValidOrganizationAdmin($user, $_POST['organizationId'])){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->generateFailResponse($httpRes, $this->authorizationTool->invalidAccountPermissionsMessaage());
        }
    }

    public function assertAdminOrValidInstructorAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isAdminOrValidInstructorAdmin($user, $_POST['instructorId'])){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->generateFailResponse($httpRes, $this->authorizationTool->invalidAccountPermissionsMessaage());
        }
    }

    public function assertAdminOrValidEventAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isAdminOrValidEventAdmin($user, $_POST['eventId'])){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->generateFailResponse($httpRes, $this->authorizationTool->invalidAccountPermissionsMessaage());
        }
    }

    public function assertValidOrganizationAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isValidOrganizationAdmin($user, $_POST['organizationId'])){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->generateFailResponse($httpRes, $this->authorizationTool->invalidAccountPermissionsMessaage());
        }
    }

    public function assertValidInstructorAdmin(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isValidInstructorAdmin($user, $_POST['instructorId'])){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->generateFailResponse($httpRes, $this->authorizationTool->invalidAccountPermissionsMessaage());
        }
    }

    public function assertOrganizationAdminForOrganization(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isActiveOrPendingOrganizationAdmin($user, $_POST['organizationId'])){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->generateFailResponse($httpRes, $this->authorizationTool->invalidAccountPermissionsMessaage());
        }
    }

    public function assertInstructorAdminForInstructor(ServerRequestInterface $httpReq, Response $httpRes, $next){
        $user = ($this->view->offsetExists('user')) ? $this->view['user'] : null;
        if($this->authorizationTool->isActiveOrPendingInstructorAdmin($user, $_POST['instructorId'])){
            return $next($httpReq, $httpRes);
        }
        else{
            return $this->generateFailResponse($httpRes, $this->authorizationTool->invalidAccountPermissionsMessaage());
        }
    }
}