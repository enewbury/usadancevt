<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Controllers;


use EricNewbury\DanceVT\Models\Exceptions\ClientValidationErrorException;
use EricNewbury\DanceVT\Models\Exceptions\Error404Exception;
use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;
use EricNewbury\DanceVT\Models\Persistence\Event;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Models\Persistence\Template;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Services\EventTool;
use EricNewbury\DanceVT\Services\FilteringService;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Services\PersistenceService;
use EricNewbury\DanceVT\Util\DateTool;
use EricNewbury\DanceVT\Util\UrlTool;
use EricNewbury\DanceVT\Util\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Rules\Even;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

class WebsiteController
{

    /** @var Twig */
    private $view;
    
    /** @var  PersistenceService */
    private $persistenceService;
    
    /** @var  EventTool */
    private $eventTool;
    
    /** @var  FilteringService */
    private $filteringService;
    
    /** @var  Validator */
    private $validator;
    
    /** @var  MailService */
    private $mailService;

    /**
     * WebsiteController constructor.
     * @param Twig $view
     * @param PersistenceService $persistenceService
     * @param EventTool $eventTool
     * @param FilteringService $filteringService
     * @param Validator $validator
     * @param MailService $mailService
     */
    public function __construct(Twig $view, PersistenceService $persistenceService, EventTool $eventTool, FilteringService $filteringService, Validator $validator, MailService $mailService)
    {
        $this->view = $view;
        $this->persistenceService = $persistenceService;
        $this->eventTool = $eventTool;
        $this->filteringService = $filteringService;
        $this->validator = $validator;
        $this->mailService = $mailService;
    }


    /**
     * @param ServerRequestInterface $req
     * @param ResponseInterface $res
     * @param array $args
     * @param array $data
     * @return ResponseInterface
     * @throws Error404Exception
     */
    public function loadPage($req, $res, $args, $data = []){
        $pageUrl = (isSet($args['slug'])) ? $args['slug'] : '';
        //look for page
        $page = $this->persistenceService->getPageByUrl('/'.$pageUrl);
        //if page not found, or page isn't active and you're not an admin
        if ($page == null || (!$page->isActive() && (!$this->view->offsetExists('user') || !$this->view['user']->isSiteAdmin()))){
            throw new Error404Exception;
        }
        
        //set all component slugs to template variables
        foreach($page->getComponents() as $component){
            if($component->getTemplateComponent()->getType() == 'form'){
                $data[$component->getTemplateComponent()->getSlug()] = $this->persistenceService->getForm($component->getValue());
            }
            else{
                $data[$component->getTemplateComponent()->getSlug()] = $component->getValue();
            }
        }


        $data['pageTitle'] = $page->getName();
        $data['currentUrl'] = $page->getUrl();
        $data = $this->loadReadOnlyTemplateData($page->getTemplate(), $data, $args, $req);
        return $this->view->render($res, $page->getTemplate()->getTemplateLink(), $data);
    }

    public function loadEvents($req, $res, $args){
        $args['slug'] = 'events';
        return $this->loadPage($req, $res, $args);
    }
    public function loadEvent($req, $res, $args){
        $args['slug'] = 'events/{id}';
        return $this->loadPage($req, $res, $args);
    }

    public function loadInstructors($req, $res, $args){
        $args['slug'] = 'instructors';
        return $this->loadPage($req, $res, $args);
    }
    public function loadInstructor($req, $res, $args){
        $args['slug'] = 'instructors/{id}';
        return $this->loadPage($req, $res, $args);
    }
    public function loadOrganizations($req, $res, $args){
        $args['slug'] = 'organizations';
        return $this->loadPage($req, $res, $args);
    }
    public function loadOrganization($req, $res, $args){
        $args['slug'] = 'organizations/{id}';
        return $this->loadPage($req, $res, $args);
    }
    public function loadNewsletter($req, Response $res, $args){
        $newsletter = $this->persistenceService->getNewsletter($args['newsletterId']);
        if($newsletter == null) throw new Error404Exception;
        return $res->write($newsletter->getContent());
    }
    
    public function processPost($req, $res, $args){
        $data = [];
        $formId = $_POST['formId'];
        $data = $this->processForm($formId);

        return $this->loadPage($req, $res, $args, $data);
    }

    /**
     * @param Template $template
     * @param $baseData
     * @param $args
     * @param Request $httpReq
     * @return mixed
     * @internal param $data
     */
    private function loadReadOnlyTemplateData($template, $baseData, $args, $httpReq)
    {
        switch($template->getName()){
            case 'Home':
                return array_merge($baseData, $this->loadHomeTemplateData());
                break;
            case 'Events':
                $data = $this->loadEventsTemplateData($args, $httpReq);
                return array_merge($baseData, $data);
                break;
            case 'Event':
                $data = $this->loadEventTemplateData($args, $httpReq);
                return array_merge($baseData, $data);
            case 'Instructor':
            $data = $this->loadInstructorTemplateData($args, $httpReq);
            return array_merge($baseData, $data);
            case 'Instructors':
                $data = $this->loadInstructorsTemplateData($args, $httpReq);
                return array_merge($baseData, $data);
            case 'Organization':
                $data = $this->loadOrganizationTemplateData($args, $httpReq);
                return array_merge($baseData, $data);
            case 'Organizations':
                $data = $this->loadOrganizationsTemplateData($args, $httpReq);
                return array_merge($baseData, $data);
            default:
                return $baseData;
        }
    }

    private function loadHomeTemplateData()
    {
        list($templateData, $filters) = $this->filteringService->processFilters([]);
        $filters['end'] = null;
        $events =  $this->eventTool->loadEvents($filters, 4, true);
        $data['DateTool'] = $templateData['DateTool'];
        $data['featuredDance'] = $this->eventTool->getNextSignatureEvent(true);
        $data['upcomingEvents'] = $events;
        return $data;
    }

    /**
     * @param $args
     * @param Request $httpReq
     * @return array
     */
    private function loadEventsTemplateData($args, $httpReq)
    {
        list($data, $filters) = $this->filteringService->processFilters($_GET);

        $data['events'] =  $this->eventTool->loadEvents($filters, null, true);
        $data['instructors'] = $this->persistenceService->getActiveInstructors();
        $data['organizations'] = $this->persistenceService->getActiveOrganizations();
        $data['categories'] = $this->persistenceService->getCategories();
        $data['counties'] = $this->persistenceService->getAllCounties(Event::class);
        $data = array_merge($data, $this->filteringService->generateQueryString($httpReq, $data['start'], $data['end']));

        $data['jsonEvents'] = addslashes(json_encode($data['events']));
        return $data;
    }

    /**
     * @param $args
     * @param Request $httpReq
     * @return mixed
     * @throws Error404Exception
     */
    private function loadEventTemplateData($args, $httpReq){
        if(empty($args['date'])){
            $args['date'] = null;
        }
        $i = $args['date'];
        $event = $this->eventTool->loadEvent($args['eventId'], $i, true);
        if($event == null) throw new Error404Exception;
        $data['event'] = $event;
        $data['DateTool'] = new DateTool();
        list(,$filters) = $this->filteringService->processFilters([], true);
        $filters['instructors'] = $event->getApprovedInstructors();
        $data['instructorEvents'] = (!empty($filters['instructors'])) ? $this->eventTool->loadEvents($filters, 3, true) : null;
        $filters['instructors']=null; $filters['organizations'] = $event->getApprovedOrganizations();
        $data['organizationEvents'] = (!empty($filters['organizations'])) ? $this->eventTool->loadEvents($filters, 3, true) : null;
        //remove self from event lists
        if($data['instructorEvents'] != null) {
            array_shift($data['instructorEvents']);
        }
        if($data['organizationEvents'] != null){
            array_shift($data['organizationEvents']);
        }
        return $data;
    }

    /**
     * @param $args
     * @param Request $httpReq
     * @return mixed
     * @throws Error404Exception
     */
    private function loadInstructorsTemplateData($args, $httpReq){
        list($templateData, $filters) = $this->filteringService->processFilters($_GET);
        $instructors = $this->persistenceService->getInstructorsByFilters(true, $filters['searchQuery'], $filters['organizations'], $filters['categories'], $filters['counties']);
        $templateData['organizations'] = $this->persistenceService->getActiveOrganizations();
        $templateData['categories'] = $this->persistenceService->getCategories();
        $templateData['counties'] = $this->persistenceService->getAllCounties(Instructor::class);
        $templateData['instructors'] = $instructors;
        return $templateData;
    }
    
    /**
     * @param $args
     * @param Request $httpReq
     * @return mixed
     * @throws Error404Exception
     */
    private function loadInstructorTemplateData($args, $httpReq){
        $instructor = $this->persistenceService->getInstructor($args['instructorId'], true);
        if($instructor == null) throw new Error404Exception;
        $data['instructor'] = $instructor;
        return $data;
    }

    /**
     * @param $args
     * @param Request $httpReq
     * @return mixed
     * @throws Error404Exception
     */
    private function loadOrganizationsTemplateData($args, $httpReq){
        list($templateData, $filters) = $this->filteringService->processFilters($_GET);
        $organizations = $this->persistenceService->getOrganizationsByFilters(true, $filters['searchQuery'], $filters['instructors'], $filters['categories'], $filters['counties']);
        $templateData['instructors'] = $this->persistenceService->getActiveInstructors();
        $templateData['categories'] = $this->persistenceService->getCategories();
        $templateData['counties'] = $this->persistenceService->getAllCounties(Organization::class);
        $templateData['organizations'] = $organizations;
        return $templateData;
    }

    /**
     * @param $args
     * @param Request $httpReq
     * @return mixed
     * @throws Error404Exception
     */
    private function loadOrganizationTemplateData($args, $httpReq){
        $organization = $this->persistenceService->getOrganization($args['organizationId'], true);
        if($organization == null) throw new Error404Exception;
        $data['organization'] = $organization;
        return $data;
    }

    private function processForm($formId)
    {
        $form = $this->persistenceService->getForm($formId);
        if($form == null){return [];}
        elseif($form->getSlug() == 'contact'){
            $data[$form->getSlug().'Response']=$this->processContactForm($_POST['name'], $_POST['email'], $_POST['subject'], $_POST['message']);
            if($data[$form->getSlug().'Response']['status']!=BaseResponse::SUCCESS){
                foreach($form->getInputs() as $input){
                    if(isSet($_POST[$input->getSlug()])){
                        $data['submitted'.$input->getSlug()] = $_POST[$input->getSlug()];
                    }
                }
            }
            return $data;
        }
    }

    private function processContactForm($name, $email, $subject, $message)
    {
        //validate inputs
        try {
            $googleRequest = ['secret'=>'6LdR5h8TAAAAAIILdbJxa65roUuVZ3lsrau9I0Co', 'response'=>(isSet($_POST['g-recaptcha-response'])) ? $_POST['g-recaptcha-response'] : ''];
            $response = UrlTool::postToUrl('https://www.google.com/recaptcha/api/siteverify', $googleRequest);
            if($response->success != true){
                throw new ClientValidationErrorException(null, ['reCaptcha must be solved.']);
            }

            $this->validator->validateContactForm($name, $email, $subject, $message);
            $this->mailService->sendContactMessage($name, $email, $subject, $message);
            return ['status'=>BaseResponse::SUCCESS, 'message'=>'Email Sent.'];
        }
        catch(ClientValidationErrorException $e){
            return ['status'=>BaseResponse::FAIL, 'message'=>$e->getMessages()[0]];
        }
        catch(InternalErrorException $e){
            return ['status'=>BaseResponse::FAIL, 'message'=>$e->getMessage()];
        }
    }


}