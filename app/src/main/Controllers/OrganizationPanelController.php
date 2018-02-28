<?php
/**
 * Created by Eric Newbury.
 * Date: 4/22/16
 */

namespace EricNewbury\DanceVT\Controllers;


use EricNewbury\DanceVT\Models\Persistence\Category;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Services\EventTool;
use EricNewbury\DanceVT\Services\FilteringService;
use EricNewbury\DanceVT\Services\OrganizationTool;
use EricNewbury\DanceVT\Services\PersistenceService;
use EricNewbury\DanceVT\Services\UserAccountService;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Views\Twig;

class OrganizationPanelController
{
    /** @var  Twig $view */
    private $view;
    
    /** @var  RouterInterface $router */
    private $router;
    
    /** @var  PersistenceService $persistenceService */
    private $persistenceService;
    
    /** @var  UserAccountService $userAccountService */
    private $userAccountService;

    /** @var  OrganizationTool $organizationTool */
    private $organizationTool;
    
    /** @var  EventTool $eventTool */
    private $eventTool;

    /** @var  FilteringService */
    private $filteringService;

    /**
     * OrganizationPanelController constructor.
     * @param Twig $view
     * @param RouterInterface $router
     * @param PersistenceService $persistenceService
     * @param UserAccountService $userAccountService
     * @param OrganizationTool $organizationTool
     * @param EventTool $eventTool
     * @param FilteringService $filteringService
     */
    public function __construct(Twig $view, RouterInterface $router, PersistenceService $persistenceService, UserAccountService $userAccountService, OrganizationTool $organizationTool, EventTool $eventTool, FilteringService $filteringService)
    {
        $this->view = $view;
        $this->router = $router;
        $this->persistenceService = $persistenceService;
        $this->userAccountService = $userAccountService;
        $this->organizationTool = $organizationTool;
        $this->eventTool = $eventTool;
        $this->filteringService = $filteringService;
    }

    public function redirectToAll(ServerRequestInterface $httpReq, Response $httpRes, $args){
        return $httpRes->withRedirect($this->router->pathFor('organizationPanelHome'));
    }
    
    //organization list
    public function loadAllForUser(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        return $this->view->render($httpRes, 'manage/organization/organization_panel.twig', [
            'pendingOrganizations'=>$user->getPendingManagedOrganizations(),
            'approvedOrganizations'=>$user->getApprovedManagedOrganizations(),
            'allOrganizations'=>$this->persistenceService->getOrganizationsExcludingSet($user->getManagedOrganizations())
        ]);
    }

    //request new organization
    public function requestNewOrganizationForUser(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $res = $this->userAccountService->requestUserManagesOrganizationPermission($user, $_POST['organizationId'], $_POST['newOrganization']);
        $data = $res->toArray();
        $data['pendingOrganizations']=$user->getPendingManagedOrganizations();
        $data['approvedOrganizations']=$user->getApprovedManagedOrganizations();
        $data['allOrganizations']=$this->persistenceService->getOrganizationsExcludingSet($user->getManagedOrganizations());
    
        return $this->view->render($httpRes, 'manage/organization/organization_panel.twig',$data);
    }

    
    //show org profile
    public function loadProfile(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $organization = $this->persistenceService->getOrganization($args['id']);
        return $this->view->render($httpRes, 'manage/organization/organization_panel_profile.twig', ['organization' => $organization, 'categories'=>$this->persistenceService->getCategories()]);
    
    }
    
    //update org profile
    public function updateProfile(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $organization = $this->persistenceService->getOrganization($args['id']);
        $res = $this->organizationTool->updateOrganization($_POST, $this->view['user'], $organization);
        $data = $res->toArray();
        $data['organization'] = $organization;
        $data['categories'] = $this->persistenceService->getCategories();
        return $this->view->render($httpRes, 'manage/organization/organization_panel_profile.twig', $data);
    
    }

    public function loadEvents(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $organization = $this->persistenceService->getOrganization($args['id']);
        $_GET['organization'] = $organization->getId();
        list($data, $filters) = $this->filteringService->processFilters($_GET);
        
        $data['events'] =  $this->eventTool->loadEvents($filters);
        $data['organization'] = $organization;
        $data['instructors'] = $this->persistenceService->getAllInstructors();
        $data['categories'] = $this->persistenceService->getCategories();
        $data['manageMode'] = true;
        $data = array_merge($data, $this->filteringService->generateQueryString($httpReq, $data['start'], $data['end']));
        
        $this->view->render($httpRes, 'manage/organization/organization_panel_events.twig', $data);
    }

    public function loadEvent(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $instanceDate = (isSet($args['date'])) ? $args['date'] : null;
        $event = $this->eventTool->loadEvent($args['eventId'], $instanceDate);
        $organization = $this->persistenceService->getOrganization($args['id']);
        $categories = $this->persistenceService->getCategories();
        return $this->view->render($httpRes, 'manage/organization/organization_panel_event_profile.twig', ['organization'=>$organization, 'event'=>$event, 'categories'=>$categories]);
    }

    public function updateEvent(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $id = $args['id'];
        $data['organization'] = $this->persistenceService->getOrganization($id);
        
        $event = $this->persistenceService->getEvent($args['eventId']);
        $res = $this->eventTool->processEvent($_POST, $user, $event);
        $data = array_merge($data, $res->toArray());
        $new = $res->getData()['new'];

        if(!$new || !$res->isSuccessful()){

            $data['event'] = $event;
            $data['categories'] = $this->persistenceService->getCategories();
            return $this->view->render($httpRes, 'manage/organization/organization_panel_event_profile.twig', $data);

        }
        else{
            $data['id'] = $id;
            $data['eventId'] = $res->getData()['eventId'];
            if ($res->getData()['date']){
                $data['date']=$res->getData()['date'];
            }
            return $httpRes->withRedirect($this->router->pathFor('organizationPanelEvent', $data));
        }

    }

}