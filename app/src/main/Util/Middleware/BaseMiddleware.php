<?php
namespace EricNewbury\DanceVT\Util\Middleware;

use EricNewbury\DanceVT\Models\Exceptions\Error404Exception;
use EricNewbury\DanceVT\Services\PersistenceService;
use EricNewbury\DanceVT\Services\UserAccountService;
use EricNewbury\DanceVT\Util\UrlTool;
use Slim\Exception\NotFoundException;
use Slim\Views\Twig;

/**
 * Created by enewbury.
 * Date: 4/21/16
 */
class BaseMiddleware{

    /** @var PersistenceService $persistenceService */
    private $persistenceService;

    /** @var  Twig $view */
    private $view;

    /** @var callable $notFoundHandler  */
    private $notFoundHandler;

    /** @var  UserAccountService $userAccountService */
    private $userAccountService;

    /**
     * BaseMiddleware constructor.
     * @param PersistenceService $persistenceService
     * @param Twig $view
     * @param callable $notFoundHandler
     * @param UserAccountService $userAccountService
     */
    public function __construct(PersistenceService $persistenceService, Twig $view, callable $notFoundHandler, UserAccountService $userAccountService)
    {
        $this->persistenceService = $persistenceService;
        $this->view = $view;
        $this->notFoundHandler = $notFoundHandler;
        $this->userAccountService = $userAccountService;
    }


    public function setCurrentUser($req, $res, $next){
        if(isSet($_SESSION['userId'])) {

            //get user
            $user = $this->persistenceService->getUser($_SESSION['userId']);

            if(isSet($user)){
                $this->view['user']=$user;

                //get requests for user
                $requests = $this->userAccountService->getRequestsForUser($user);
                $this->view['requests']=$requests;
            }

        }

        return $next($req, $res);
    }

    public function setNavCookie($req, $res, $next){
        if (isSet($_COOKIE['accountNavClosed']) && $_COOKIE['accountNavClosed'] === 'true') {
            $this->view['accountPanelClass'] = 'closed';
        }
        return $next($req, $res);
    }

    public function catchNotFoundExceptions($request, $response, $next) {
        try {
            $response = $next($request, $response);
        }
        catch(Error404Exception $e) {
            $notFoundHandler = $this->notFoundHandler;
            return $notFoundHandler($request->withAttribute('message', $e->getMessage()), $response);
        }
        return $response;
    }
    
    public function loadGlobalTemplateVariables($req, $res, $next){
        //get nav list
        $navItems = $this->persistenceService->getNavLinks();
        $data = [];
        foreach($navItems as $item){
            if ($item->getPage()->isActive()) {
                $data[] = [
                    'linkText' => $item->getPage()->getName(),
                    'linkHref' => $item->getPage()->getUrl()
                ];
            }
        }
        $this->view['navItems'] = $data;

        //get other global data
        $globalData = $this->persistenceService->getGlobalData();
        foreach($globalData as $component){
            $this->view[UrlTool::camelCase($component->getSlug())] = $component->getValue();
        }
        
        $this->view['siteDomain'] =  (isSet($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

        return $next($req, $res);
    }
}