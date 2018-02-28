<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Controllers;


use EricNewbury\DanceVT\Services\PersistenceService;
use EricNewbury\DanceVT\Services\UserAccountService;
use EricNewbury\DanceVT\Util\Uploader;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Router;
use Slim\Views\Twig;

class UserAccountController
{

    /** @var Twig view */
    private $view;

    /** @var  RouterInterface router */
    private $router;


    /** @var  UserAccountService $userAccountService */
    private $userAccountService;

    /** @var  PersistenceService $persistenceService */
    private $persistenceService;

    /**
     * UserAccountController constructor.
     * @param Twig $view
     * @param RouterInterface|Router $router
     * @param UserAccountService $userAccountService
     * @param PersistenceService $persistenceService
     */
    public function __construct(Twig $view, RouterInterface $router, UserAccountService $userAccountService, PersistenceService $persistenceService)
    {
        $this->view = $view;
        $this->router = $router;
        $this->userAccountService = $userAccountService;
        $this->persistenceService = $persistenceService;
    }

 

    public function deleteAccount(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        $this->userAccountService->deleteUser($_SESSION['userId']);
        $this->userAccountService->logout();
        unset($this->view['user']);
        return $this->view->render($httpRes, 'notice.twig', ['title'=>'Account Deleted', 'notice'=>'Your account has been successfully deleted']);
    }

    // -----------------------------------------------------------------------
    // AUTHENTICATION
    //-----------------------------------------------------------------------
    public function loadLogin(ServerRequestInterface $httpReq, Response $httpRes) {
        //check if already logged in
        if(isSet($_SESSION['userId'])){
            //go back to previous page if on site
            $referrer = (isSet($_SESSION['redirectPath'])) ? $_SESSION['redirectPath'] : '/';
            return $httpRes->withRedirect($referrer);
        }

        return $this->view->render($httpRes, 'login.twig');
    }

    public function login(Request $httpReq, Response $httpRes) {
        $ip = $httpReq->getAttribute('ip_address');
        $userAgent = $httpReq->getHeader('HTTP_USER_AGENT')[0];
        $response = $this->userAccountService->login($_POST['email'], $_POST['password'], $ip, $userAgent, $this->router->pathFor('resendVerification'));

        //success
        if($response->isSuccessful()){
            //go back to previous page if on site
            $referrer = (isSet($_SESSION['redirectPath'])) ? $_SESSION['redirectPath'] : '/';
            return $httpRes->withRedirect($referrer);
        }
        //fail
        else {
            return $this->view->render($httpRes,'login.twig', $response->toArray());
        }
    }

    public function logout(ServerRequestInterface $httpReq, Response $httpRes){
        $this->userAccountService->logout();
        return $httpRes->withRedirect('/');
    }

    // -----------------------------------------------------------------------
    // PASSWORD RESET
    //-----------------------------------------------------------------------

    public function loadForgotPassword(ServerRequestInterface $httpReq, Response $httpRes){
        return $this->view->render($httpRes, "forgot_password.twig");
    }

    public function forgotPassword(ServerRequestInterface $httpReq, Response $httpRes){
        $res = $this->userAccountService->sendForgotPasswordToken($_POST['email']);
        return $this->view->render($httpRes, 'forgot_password.twig',$res->toArray());
    }

    public function loadResetPassword(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $res = $this->userAccountService->verifyPasswordToken($args['userId'], $_GET['token']);
        return $this->view->render($httpRes, 'password_reset.twig', $res->toArray());
    }

    public function resetPassword(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $res = $this->userAccountService->verifyPasswordTokenAndChangePassword($args['userId'], $_GET['token'], $_POST["password"], $_POST["confirm"]);
        if($res->isSuccessful()){
            return $this->view->render($httpRes, 'notice.twig',[
                'title'=>'Password Reset Successful',
                'notice'=>'Successfully reset your password.',
                'linkText'=>'Login',
                'linkHref'=>$this->router->pathFor('login')
            ]);
        }
        else{
            return $this->view->render($httpRes, 'password_reset.twig', $res->toArray());
        }
    }

    // -----------------------------------------------------------------------
    // CREATE REQUESTS
    //-----------------------------------------------------------------------

    public function requestSiteAdminPermission(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $user = $this->view['user'];
        $this->userAccountService->requestSiteAdminPermission($user);
        $this->view['user'] = $user;
        $this->view->render($httpRes, 'notice.twig',[
            'title'=>'Request Sent',
            'notice'=>'Request Sent.  Your account will be activated once an administrator approves the request.'
        ]);
    }
    
    public function deactivateSiteAdminPermission(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $this->persistenceService->deactivateSiteAdminPermission($user);
        $this->view['user'] = $user;
        $this->view->render($httpRes, 'notice.twig',[
            'title'=>'Deactivated',
            'notice'=>'Your Site Admin Permissions have been deactivated'
        ]);
    }
    
    public function activateInstructorAdminAccount(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $this->persistenceService->activateInstructorAdminPermission($user);
        $this->view['user'] = $user;
        $this->view->render($httpRes, 'notice.twig',[
            'title'=>'Account Activated',
            'notice'=>'Instructor Admin permissions activated'
        ]);
    }
    
    public function deactivateInstructorAdminAccount(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $this->persistenceService->deactivateInstructorAdminPermission($user);
        $this->view['user'] = $user;
        $this->view->render($httpRes, 'notice.twig',[
            'title'=>'Account Deactivated',
            'notice'=>'Instructor Admin permissions deactivated'
        ]);
    }
    
    public function activateOrganizationAdminAccount(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $this->persistenceService->activateOrganizationAdminPermission($user);
        $this->view['user'] = $user;
        $this->view->render($httpRes, 'notice.twig',[
            'title'=>'Account Activated',
            'notice'=>'Organization Admin permissions activated'
        ]);
    }
    
    public function deactivateOrganizationAdminAccount(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $this->persistenceService->deactivateOrganizationAdminPermission($user);
        $this->view['user'] = $user;
        $this->view->render($httpRes, 'notice.twig',[
            'title'=>'Account Deactivated',
            'notice'=>'Organization Admin permissions deactivated'
        ]);
    }

    
   

    public function getRequests(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $requests = $this->userAccountService->getRequestsForUser($user);
        $this->view->render($httpRes, 'components/requests.twig', ['requests'=>$requests]);
    }



}