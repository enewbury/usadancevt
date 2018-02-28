<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Controllers;

use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Services\RegistrationService;
use EricNewbury\DanceVT\Util\NoticeTool;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Router;
use Slim\Views\Twig;

class RegistrationController
{
    /** @var Twig view */
    private $view;

    /** @var  Router router */
    private $router;


    /** @var  RegistrationService $registrationService */
    private $registrationService;

    public function __construct($view, $router, $registrationService)
    {
        $this->view = $view;
        $this->router = $router;
        $this->registrationService = $registrationService;
    }


    public function renderCreateAccountPage($httpReq, $httpRes){
        return $this->view->render($httpRes, 'create_account.twig');
    }

    public function createAccount(ServerRequestInterface $httpReq, ResponseInterface $httpRes){
        $response = $this->registrationService->createAccount($_POST['first'], $_POST['last'], $_POST['email'], $_POST['password'], $_POST['confirm'], $this->router->pathFor('resendVerification'));
        if($response->isSuccessful()){
            return $httpRes->withStatus(302)->withHeader('Location', $this->router->pathFor('thankYou'));
        } else{
            return $this->view->render($httpRes, 'create_account.twig', $response->toArray());
        }
    }

    public function thankYou(ServerRequestInterface $httpReq, ResponseInterface $httpRes){
        return $this->view->render($httpRes, 'notice.twig', [
            'title'=>'Thank You',
            'notice'=>'Thank you for creating an account!  You have been sent an email to verify your account.'
        ]);
    }

    //verify new account
    public function verify(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        $res = $this->registrationService->verifyAccount($args['userId'], $_GET['token']);
        $data = $res->toArray();
        $data = array_merge($data, NoticeTool::generateNotice('Verify Account','Your account has been verified.', 'Login', $this->router->pathFor('login')));
        return $this->view->render($httpRes, 'notice.twig', $data);
    }

    //resend verification email
    public function resendVerification(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        $data = null;
        if(!isSet($_GET['email'])){
            $res = new FailResponse('Email not set.');
            $data = $res->toArray();
        }
        else{
            $res = $this->registrationService->resendVerification($_GET['email']);
            $data = $res->toArray();
            $data = array_merge($data, NoticeTool::generateNotice("Verification Email Resent","Verification email resent.  Check you email to complete verification."));
        }
        return $this->view->render($httpRes, 'notice.twig', $data);
    }

    public function subscribe(ServerRequestInterface $httpReq, ResponseInterface $httpRes){
        $response = $this->registrationService->createNewSubscriber($_POST['subscriberName'], $_POST['subscriberEmail']);
        if($response->isSuccessful()){
            return $this->view->render($httpRes, 'notice.twig', NoticeTool::generateNotice('Subscription Successful', 'You have successfully subscribed to our newsletter.'));
        } else{
            return $this->view->render($httpRes, 'notice.twig', $response->toArray());
        }
    }

    public function loadUnsubscribe(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        return $this->view->render($httpRes, 'unsubscribe.twig');
    }

    public function unsubscribe(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        try{
            $this->registrationService->unsubscribe($_POST['email']);
            return $this->view->render($httpRes, 'notice.twig', NoticeTool::generateNotice('Unsubscribe Successful', 'You have successfully unsubscribed from our newsletter.'));
        }
        catch(\Exception $e){
            $res = new FailResponse('There was an error unsubscribing.  Seriously, we aren\'t just trying to keep spamming you.');
            return $this->view->render($httpRes, 'unsubscribe.twig', $res);
        }
    }

}