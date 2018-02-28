<?php
/**
 * Created by Eric Newbury.
 * Date: 4/22/16
 */

namespace EricNewbury\DanceVT\Controllers;


use Doctrine\Tests\Common\Cache\CacheTest;
use EricNewbury\DanceVT\Models\Exceptions\Error404Exception;
use EricNewbury\DanceVT\Models\Persistence\Category;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Services\EventTool;
use EricNewbury\DanceVT\Services\FilteringService;
use EricNewbury\DanceVT\Services\InstructorTool;
use EricNewbury\DanceVT\Services\PersistenceService;
use EricNewbury\DanceVT\Services\UserAccountService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Views\Twig;

class InstructorPanelController
{
    /** @var  Twig $view */
    private $view;
    
    /** @var  RouterInterface $router */
    private $router;
    
    /** @var  PersistenceService $persistenceService */
    private $persistenceService;
    
    /** @var  UserAccountService $userAccountService */
    private $userAccountService;

    /** @var  InstructorTool $instructorTool */
    private $instructorTool;
    
    /** @var  EventTool $eventTool */
    private $eventTool;
    
    /** @var  FilteringService */
    private $filteringService;

    /**
     * InstructorPanelController constructor.
     * @param Twig $view
     * @param RouterInterface $router
     * @param PersistenceService $persistenceService
     * @param UserAccountService $userAccountService
     * @param InstructorTool $instructorTool
     * @param EventTool $eventTool
     * @param FilteringService $filteringService
     */
    public function __construct(Twig $view, RouterInterface $router, PersistenceService $persistenceService, UserAccountService $userAccountService, InstructorTool $instructorTool, EventTool $eventTool, FilteringService $filteringService)
    {
        $this->view = $view;
        $this->router = $router;
        $this->persistenceService = $persistenceService;
        $this->userAccountService = $userAccountService;
        $this->instructorTool = $instructorTool;
        $this->eventTool = $eventTool;
        $this->filteringService = $filteringService;
    }


    //INSTRUCTOR
    public function redirectToAll(ServerRequestInterface $httpReq, Response $httpRes){
        return $httpRes->withRedirect($this->router->pathFor('instructorPanelHome'));
    }

    public function loadAllForUser(ServerRequestInterface $httpReq, ResponseInterface $httpRes){
        $user = $this->view['user'];
        return $this->view->render($httpRes, 'manage/instructor/instructor_panel.twig', [
            'pendingInstructors'=>$user->getPendingManagedInstructors(),
            'approvedInstructors'=>$user->getApprovedManagedInstructors(),
            'allInstructors'=>$this->persistenceService->getInstructorsExcludingSet($user->getManagedInstructors())
        ]);
    }
    
    //request new instructor
    public function requestNewInstructorForUser(ServerRequestInterface $httpReq, ResponseInterface $httpRes){
        $user = $this->view['user'];
        $res = $this->userAccountService->requestUserManagesInstructorPermission($user, $_POST['instructorId'], $_POST['newInstructor']);
        $data = $res->toArray();
    
        $data['pendingInstructors']=$user->getPendingManagedInstructors();
        $data['approvedInstructors']=$user->getApprovedManagedInstructors();
        $data['allInstructors']=$this->persistenceService->getInstructorsExcludingSet($user->getManagedInstructors());
    
        return $this->view->render($httpRes, 'manage/instructor/instructor_panel.twig',$data);
    }
    
    public function loadProfile(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args) {
        $instructor = $this->persistenceService->getInstructor($args['id']);
        return $this->view->render($httpRes, 'manage/instructor/instructor_panel_profile.twig', ['instructor'=>$instructor, 'categories'=>$this->persistenceService->getCategories()]);
    
    }
    
    public function updateProfile(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args) {
        $instructor = $this->persistenceService->getInstructor($args['id']);
        $res = $this->instructorTool->updateInstructor($_POST, $this->view['user'], $instructor);
        $data = $res->toArray();
        $data['instructor'] = $instructor;
        $data['categories'] = $this->persistenceService->getCategories();
        return $this->view->render($httpRes, 'manage/instructor/instructor_panel_profile.twig', $data);
    }

    public function loadEvents(Request $httpReq, ResponseInterface $httpRes, $args){
      
        $instructor = $this->persistenceService->getInstructor($args['id']);
        $_GET['instructor'] = $instructor->getId();
        list($data, $filters) = $this->filteringService->processFilters($_GET);
        
        $data['events'] =  $this->eventTool->loadEvents($filters);
        $data['instructor'] = $instructor;
        $data['organizations'] = $this->persistenceService->getAllOrganizations();
        $data['categories'] = $this->persistenceService->getCategories();
        $data['manageMode'] = true;
        $data = array_merge($data, $this->filteringService->generateQueryString($httpReq, $data['start'], $data['end']));
        $this->view->render($httpRes, 'manage/instructor/instructor_panel_events.twig', $data);
    }

    public function loadEvent(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        $instanceDate = (isSet($args['date'])) ? $args['date'] : null;
        $event = $this->eventTool->loadEvent($args['eventId'], $instanceDate);
        $instructor = $this->persistenceService->getInstructor($args['id']);
        $categories = $this->persistenceService->getCategories();
        return $this->view->render($httpRes, 'manage/instructor/instructor_panel_event_profile.twig', ['instructor'=>$instructor, 'event'=>$event, 'categories'=>$categories]);
    }

    public function updateEvent(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $id = $args['id'];
        $data['instructor'] = $this->persistenceService->getInstructor($id);
        
        $event = $this->persistenceService->getEvent($args['eventId']);
        $res = $this->eventTool->processEvent($_POST, $user, $event);
        $data = array_merge($data, $res->toArray());
        $new = $res->getData()['new'];

        if(!$new || !$res->isSuccessful()){
            $data['event'] = $event;
            $data['categories'] = $this->persistenceService->getCategories();
            return $this->view->render($httpRes, 'manage/instructor/instructor_panel_event_profile.twig', $data);

        }
        else{
            $data['id'] = $id;
            $data['eventId'] = $res->getData()['eventId'];
            if ($res->getData()['date']){
                $data['date']=$res->getData()['date'];
            }
            return $httpRes->withRedirect($this->router->pathFor('instructorPanelEvent', $data));
        }

    }

}