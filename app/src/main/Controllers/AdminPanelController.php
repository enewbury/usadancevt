<?php
/**
 * Created by Eric Newbury.
 * Date: 4/22/16
 */

namespace EricNewbury\DanceVT\Controllers;


use EricNewbury\DanceVT\Constants\Association;
use EricNewbury\DanceVT\Constants\PermissionStatus;
use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\Error404Exception;
use EricNewbury\DanceVT\Models\Persistence\Category;
use EricNewbury\DanceVT\Models\Persistence\Event;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use EricNewbury\DanceVT\Services\AdminTool;
use EricNewbury\DanceVT\Services\AssociationsTool;
use EricNewbury\DanceVT\Services\EventTool;
use EricNewbury\DanceVT\Services\FilteringService;
use EricNewbury\DanceVT\Services\InstructorTool;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Services\OrganizationTool;
use EricNewbury\DanceVT\Services\PersistenceService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Views\Twig;

class AdminPanelController
{
    /** @var  Twig $view */
    private $view;

    /** @var  RouterInterface $router */
    private $router;
    
    /** @var  PersistenceService $persistenceService */
    private $persistenceService;
    
    /** @var  FilteringService $filteringService */
    private $filteringService;
    
    /** @var  AdminTool $adminTool */
    private $adminTool;
    
    /** @var AssociationsTool $associationsTool */
    private $associationsTool;
    
    /** @var  OrganizationTool $organizationTool */
    private $organizationTool;
    
    /** @var  InstructorTool $instructorTool */
    private $instructorTool;

    /** @var  EventTool $eventTool */
    private $eventTool;

    /** @var  \HTMLPurifier */
    private $htmlPurifier;
    
    /** @var  MailService */
    private $mailService;

    /**
     * AdminPanelController constructor.
     * @param Twig $view
     * @param RouterInterface $router
     * @param PersistenceService $persistenceService
     * @param FilteringService $filteringService
     * @param AdminTool $adminTool
     * @param AssociationsTool $associationsTool
     * @param OrganizationTool $organizationTool
     * @param InstructorTool $instructorTool
     * @param EventTool $eventTool
     * @param \HTMLPurifier $htmlPurifier
     * @param MailService $mailService
     */
    public function __construct(Twig $view, RouterInterface $router, PersistenceService $persistenceService, FilteringService $filteringService, AdminTool $adminTool, AssociationsTool $associationsTool, OrganizationTool $organizationTool, InstructorTool $instructorTool, EventTool $eventTool, \HTMLPurifier $htmlPurifier, MailService $mailService)
    {
        $this->view = $view;
        $this->router = $router;
        $this->persistenceService = $persistenceService;
        $this->filteringService = $filteringService;
        $this->adminTool = $adminTool;
        $this->associationsTool = $associationsTool;
        $this->organizationTool = $organizationTool;
        $this->instructorTool = $instructorTool;
        $this->eventTool = $eventTool;
        $this->htmlPurifier = $htmlPurifier;
        $this->mailService = $mailService;
    }


    // ACCOUNTS
    public function root(ServerRequestInterface $httpReq, Response $httpRes, $args){
        return $httpRes->withRedirect($this->router->pathFor('adminPanelGlobals'));
    }
    
    public function accounts(Request $httpReq, ResponseInterface $httpRes, $args){
        $res = null;
        if($httpReq->isPost()) {
            $res = $this->adminTool->createAccountViaAdmin($_POST['first'], $_POST['last'], $_POST['email'], $this->htmlPurifier->purify($_POST['message']));
        }
    
        $users = $this->persistenceService->getAllUsers();
        $data = ['users'=>$users];
        if($httpReq->isPost()){
            $data = array_merge($data,$res->toArray());
        }
    
        return $this->view->render($httpRes, '/manage/admin/admin_accounts.twig', $data);
    }
    
    //ORGANIZATIONS
    public function loadOrganizations(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        list($templateData, $filters) = $this->filteringService->processFilters($_GET);
        $templateData['organizations'] = $this->persistenceService->getOrganizationsByFilters(false, $filters['searchQuery'], $filters['organizations'], $filters['categories'], $filters['counties']);
        $templateData['instructors'] = $this->persistenceService->getActiveInstructors();
        $templateData['categories'] = $this->persistenceService->getCategories();
        $templateData['counties'] = $this->persistenceService->getAllCounties(Organization::class);

        return $this->view->render($httpRes, '/manage/admin/admin_organizations.twig', $templateData);
    }
    
    public function loadOrganizationProfile(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        $organization = $this->persistenceService->getOrganization($args['id']);
        if($organization == null && $args['id'] != 'new') throw new Error404Exception;
        return $this->view->render($httpRes, '/manage/admin/admin_organization_profile.twig', ['organization'=>$organization, 'categories'=>$this->persistenceService->getCategories()]);
    }
    public function updateOrganization(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $id = $args['id'];

        if($id !== 'new'){
            $organization = $this->persistenceService->getOrganization($id);
            $orgNotFound = ($organization === null);
            $res = $this->organizationTool->updateOrganization($_POST, $user, $organization);
            $data = $res->toArray();
            $data['organization'] = $organization;
            $data['categories'] = $this->persistenceService->getCategories();
            return $this->view->render($httpRes, '/manage/admin/admin_organization_profile.twig', $data);
        }
        if($id === 'new' || isSet($orgNotFound)){
            $res = $this->organizationTool->updateOrganization($_POST, $user);
            return $httpRes->withRedirect($this->router->pathFor('adminPanelOrganization', ['id'=>$res->getData()['orgId']]));
        }
    }
    
    //INSTRUCTORS
    public function loadInstructors(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        list($templateData, $filters) = $this->filteringService->processFilters($_GET);
        $templateData['instructors'] = $this->persistenceService->getInstructorsByFilters(false, $filters['searchQuery'], $filters['organizations'], $filters['categories'], $filters['counties']);
        $templateData['organizations'] = $this->persistenceService->getActiveOrganizations();
        $templateData['categories'] = $this->persistenceService->getCategories();
        $templateData['counties'] = $this->persistenceService->getAllCounties(Instructor::class);
        
        return $this->view->render($httpRes, '/manage/admin/admin_instructors.twig', $templateData);
    }
    
    public function loadInstructorProfile(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        $instructor = $this->persistenceService->getInstructor($args['id']);
        if($instructor == null && $args['id'] != 'new') throw new Error404Exception;
        return $this->view->render($httpRes, '/manage/admin/admin_instructor_profile.twig', ['instructor'=>$instructor, 'categories'=>$this->persistenceService->getCategories()]);
    }
    public function updateInstructorProfile(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $id = $args['id'];

        if($id !== 'new'){
            $instructor = $this->persistenceService->getInstructor($id);
            $notFound = ($instructor === null);
            $res = $this->instructorTool->updateInstructor($_POST, $user, $instructor);
            $data = $res->toArray();
            $data['instructor'] = $instructor;
            $data['categories'] = $this->persistenceService->getCategories();
            return $this->view->render($httpRes, '/manage/admin/admin_instructor_profile.twig', $data);

        }
        if($id == 'new' || isSet($notFound)){
            $res = $this->instructorTool->updateInstructor($_POST, $user);
            return $httpRes->withRedirect($this->router->pathFor('adminPanelInstructor', ['id'=>$res->getData()['instId']]));
        }
    }
    
    //EVENTS
    public function loadEvents(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        //get filters
        list($templateData, $filters) = $this->filteringService->processFilters($_GET);
       
        $templateData['events'] =  $this->eventTool->loadEvents($filters);
        $templateData['instructors'] = $this->persistenceService->getAllInstructors();
        $templateData['organizations'] = $this->persistenceService->getAllOrganizations();
        $templateData['categories'] = $this->persistenceService->getCategories();
        $templateData['counties'] = $this->persistenceService->getAllCounties(Event::class);
        $templateData = array_merge($templateData, $this->filteringService->generateQueryString($httpReq, $templateData['start'], $templateData['end']));
        $templateData['manageMode'] = true;
        $this->view->render($httpRes, '/manage/admin/admin_events.twig', $templateData);
    }
    
    public function loadEvent(ServerRequestInterface $httpReq, ResponseInterface $httpRes, $args){
        $instanceDate = (isSet($args['date'])) ? $args['date'] : null;
        $event = $this->eventTool->loadEvent($args['eventId'], $instanceDate);
        if($event == null && $args['eventId'] != 'new') throw new Error404Exception;
        $categories = $this->persistenceService->getCategories();
        return $this->view->render($httpRes, '/manage/admin/admin_event_profile.twig', ['event'=>$event, 'categories'=>$categories]);
    }
    
    public function updateEvent(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $id = $args['eventId'];
        
        $event = $this->persistenceService->getEvent($id);
        $res = $this->eventTool->processEvent($_POST, $user, $event);
        $new = $res->getData()['new'];
        
        if(!$new || !$res->isSuccessful()){
            $data = $res->toArray();
            $data['event'] = $event;
            $data['categories'] = $this->persistenceService->getCategories();
            return $this->view->render($httpRes, '/manage/admin/admin_event_profile.twig', $data);

        }
        else{
            $data['eventId'] = $res->getData()['eventId'];
            if ($res->getData()['date']){
                $data['date']=$res->getData()['date'];
            }
            return $httpRes->withRedirect($this->router->pathFor('adminPanelEvent', $data));
        }

    }
    
    //PAGES
    public function loadPages(Request $httpReq, Response $httpRes){
        $data['templates'] = $this->persistenceService->getAllUnlockedTemplates();
        $data['pages'] = $this->persistenceService->getAllPages();
        return $this->view->render($httpRes, '/manage/admin/admin_pages.twig', $data);
    }

    public function createPage(Request $httpReq, Response $httpRes){
        $newPageName = $_POST['newpage'];
        $templateId = $_POST['templateId'];
        try {
            $this->persistenceService->createNewPage($newPageName, $templateId);
            $res = new SuccessResponse('Created New Page.');
            $data = $res->toArray();
        }
        catch(ClientErrorException $e){
            $data = BaseResponse::generateClientErrorResponse($e)->toArray();
        }
        $data['templates'] = $this->persistenceService->getAllUnlockedTemplates();
        $data['pages'] = $this->persistenceService->getAllPages();
        return $this->view->render($httpRes, '/manage/admin/admin_pages.twig', $data);
    }

    public function loadPage(Request $httpReq, Response $httpRes, $args){
        $page = $this->persistenceService->getPage($args['pageId']);
        if($page == null) throw new Error404Exception;
        $forms = $this->persistenceService->getForms();
        return $this->view->render($httpRes, '/manage/admin/admin_page.twig', ['page'=>$page, 'forms'=>$forms]);
    }

    public function updatePage(Request $httpReq, Response $httpRes, $args){
        $data = [];
        $pageName = $_POST['pageName'];
        unset($_POST['pageName']);
        $pageUrl = (isSet($_POST['pageUrl'])) ? (substr($_POST['pageUrl'], 0, 1 ) !== "/" && substr($_POST['pageUrl'], 0, 4 ) !== "http") ? strtolower('/'.$_POST['pageUrl']) : $_POST['pageUrl'] : null;
        unset($_POST['pageUrl']);
        try {
            foreach ($_POST as $componentSlug => $componentValue) {
                $this->persistenceService->updatePageComponent($componentSlug, $this->htmlPurifier->purify($componentValue), $args['pageId']);
            }
            $page = $this->persistenceService->getPage($args['pageId']);
            $page->setName($pageName);
            if(!$page->getTemplate()->isLocked()){
                $page->setUrl($pageUrl);
            }
            $this->persistenceService->persistChanges();
            $res = new SuccessResponse('Updated Successfully');
            $data = $res->toArray();
            $data['page'] = $page;
            $data['forms'] = $this->persistenceService->getForms();
        }
        catch(ClientErrorException $e){
            $res = BaseResponse::generateClientErrorResponse($e);
            $data = $res->toArray();
            $data['page'] = $this->persistenceService->getPage($args['pageId']);
            $data['forms'] = $this->persistenceService->getForms();
        }
        return $this->view->render($httpRes, '/manage/admin/admin_page.twig', $data);
    }

    public function loadGlobalSettingsPage(Request $httpReq, Response $httpRes, $args){
        $globalComponents = $this->persistenceService->getGlobalData();
        $pages = $this->persistenceService->getAllActivePages();
        return $this->view->render($httpRes, '/manage/admin/admin_globals.twig', ['components'=>$globalComponents, 'pages'=>$pages]);
    }

    public function updateGlobals(Request $httpReq, Response $httpRes, $args){

        try{
            $navItems = $_POST['navItems'];
            unset($_POST['navItems']);
            $this->persistenceService->resetNavItems($navItems);
            
            foreach ($_POST as $componentSlug => $componentValue) {
                $this->persistenceService->updateGlobalComponent($componentSlug, $this->htmlPurifier->purify($componentValue));
            }
            $res = new SuccessResponse('Updated Successfully');
            $data = $res->toArray();
        }
        catch(ClientErrorException $e){
            $res = BaseResponse::generateClientErrorResponse($e);
            $data = $res->toArray();
        }
        $data['pages'] = $this->persistenceService->getAllActivePages();
        $data['components'] =  $this->persistenceService->getGlobalData();
        return $this->view->render($httpRes, '/manage/admin/admin_globals.twig', $data);
    }
    
    public function loadEmailToolPage(Request $httpReq, Response $httpRes, $data){
        $data['jsonInstructors'] = json_encode($this->persistenceService->getActiveInstructors());
        $data['jsonOrganizations'] = json_encode($this->persistenceService->getActiveOrganizations());
        $data['jsonCategories'] = json_encode($this->persistenceService->getCategories());
        $data['jsonCounties'] = json_encode($this->persistenceService->getAllCounties(Event::class));
        return $this->view->render($httpRes, '/manage/admin/admin_email_tool.twig', $data);
    }

    public function sendNewsletter(Request $httpReq, Response $httpRes){
        $sections = json_decode($_POST['sections']);
        $subject = $_POST['subject'];

        try {
            //purify html input
            foreach ($sections as $section) {
                if ($section->type == 'html') {
                    $section->content = $this->htmlPurifier->purify($section->content);
                }
            }
            $this->mailService->sendNewsletter($subject, $sections);
            $res = new SuccessResponse('Email Sent');
            return $this->loadEmailToolPage($httpReq, $httpRes, $res->toArray());
        }
        catch(\Exception $e){
            $res = new FailResponse('There was an error sending the email');
            return $this->loadEmailToolPage($httpReq, $httpRes, $res->toArray());
        }
    }
}