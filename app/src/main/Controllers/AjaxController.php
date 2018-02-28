<?php
/**
 * Created by Eric Newbury.
 * Date: 4/22/16
 */

namespace EricNewbury\DanceVT\Controllers;


use EricNewbury\DanceVT\Constants\Association;
use EricNewbury\DanceVT\Constants\PermissionStatus;
use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;
use EricNewbury\DanceVT\Models\Persistence\Profile;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use EricNewbury\DanceVT\Services\AssociationsTool;
use EricNewbury\DanceVT\Services\EventTool;
use EricNewbury\DanceVT\Services\FilteringService;
use EricNewbury\DanceVT\Services\InstructorTool;
use EricNewbury\DanceVT\Services\OrganizationTool;
use EricNewbury\DanceVT\Services\PersistenceService;
use EricNewbury\DanceVT\Services\UserAccountService;
use EricNewbury\DanceVT\Util\Uploader;
use EricNewbury\DanceVT\Util\UrlTool;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;
use Slim\Views\Twig;

class AjaxController
{

    /** @var  Twig $view */
    private $view;

    /** @var  PersistenceService $persistenceService */
    private $persistenceService;

    /** @var  AssociationsTool $associationsTool */
    private $associationsTool;

    /** @var  UserAccountService $userAccountService */
    private $userAccountService;
    
    /** @var  InstructorTool */
    private $instructorTool;

    /** @var  OrganizationTool */
    private $organizationTool;

    /** @var  EventTool */
    private $eventTool;
    
    /** @var  FilteringService */
    private $filteringService;

    /**
     * AjaxController constructor.
     * @param Twig $view
     * @param PersistenceService $persistenceService
     * @param AssociationsTool $associationsTool
     * @param UserAccountService $userAccountService
     * @param InstructorTool $instructorTool
     * @param OrganizationTool $organizationTool
     * @param EventTool $eventTool
     * @param FilteringService $filteringService
     */
    public function __construct(Twig $view, PersistenceService $persistenceService, AssociationsTool $associationsTool, UserAccountService $userAccountService, InstructorTool $instructorTool, OrganizationTool $organizationTool, EventTool $eventTool, FilteringService $filteringService)
    {
        $this->view = $view;
        $this->persistenceService = $persistenceService;
        $this->associationsTool = $associationsTool;
        $this->userAccountService = $userAccountService;
        $this->instructorTool = $instructorTool;
        $this->organizationTool = $organizationTool;
        $this->eventTool = $eventTool;
        $this->filteringService = $filteringService;
    }

    // -----------------------------------------------------------------------
    // UPDATE ACCOUNT
    //------------------------------------------------------------------------
    public function updateAccount(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        if(isSet($_POST['first'])){
            $res = $this->userAccountService->updateName($_POST['first'], $_POST['last'], $user);
        }
        elseif(isSet($_POST['email'])){
            $res = $this->userAccountService->updateEmail($_POST['email'], $user);
        }
        elseif(isSet($_POST['old'])){
            $res = $this->userAccountService->updatePassword($_POST['old'], $_POST['new'], $_POST['confirm'], $user);
        }
        else{
            $res = [];
        }

        $data=['user'=>$user];
        $data = array_merge($data, $res->toArray());

        return $httpRes->withJson($data);
    }

    /**
     * Gets the info box that shows associations between entities
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getAssociations(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $data = $this->associationsTool->getAssociations($_POST['association'], $_POST['id'], $this->view['user']);
        return $this->view->render($httpRes, 'manage/admin/permission_popup.twig', $data);
    }
    
    /**
     * Set admin status and attend to any permission requests
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param $args
     * @return Response
     */
    public function updateAdminStatus(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->persistenceService->getUser($_POST['userId']);
        $user->setSiteAdminPermission($_POST['status']);
        $this->persistenceService->persistChanges();

        //complete any open requests
        $status = $_POST['status'];
        if($status !== PermissionStatus::PENDING) {
            if($status == PermissionStatus::OFF){$status = PermissionStatus::DENIED; }
            $this->persistenceService->completePermission(Association::ADMIN, $status, $_POST['userId']);
        }

        $res = new SuccessResponse('Updated site admin permissions');
        return $httpRes->withJson($res->toArray());
    }

    /**
     * Takes AssociationsUpdateRequest as json and applies it to applicable entities
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param $args
     * @return Response
     */
    public function updateAssociations(ServerRequestInterface $httpReq, Response $httpRes, $args) {
        try{
            $this->associationsTool->updateAssociations(json_decode($httpReq->getBody(), true), $this->view['user']);
            $res = new SuccessResponse('Updated permissions successfully.');
            return $httpRes->withJson($res->toArray());
        }
        catch(ClientErrorException $e){
            $res = BaseResponse::generateClientErrorResponse($e);
            return $httpRes->withJson($res->toArray());
        }
        catch(InternalErrorException $e){
            $res = BaseResponse::generateInternalErrorResponse($e);
            return $httpRes->withJson($res->toArray());
        }
        
    }

    /**
     * Respond to clicking the approve or deny button in the permissions request drop-down
     * Takes into account user permission to do the approval
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param $args
     * @return Response
     */
    public function respondToRequest(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $res = $this->userAccountService->respondToRequest($user, $_POST['permissionAction'], $_POST['requestId']);
        return $httpRes->withJson($res->toArray());
    }

    /**
     * Activates or deactivates users from the admin panel switch
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param $args
     * @return Response
     */
    public function setUserActivation(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $res = $this->userAccountService->updateAccountActivation($_POST['id'], $_POST['active']);
        return $httpRes->withJson($res->toArray());
    }

    /**
     * Response to clicking the delete button on admin panel
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param $args
     * @return Response
     */
    public function deleteUser(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $res = $this->userAccountService->deleteUser($_POST['id']);
        return $httpRes->withJson($res->toArray());
    }

    /**
     * Upload an image to the uploads folder.  You only have to be logged in to do this.
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param $args
     * @return Response
     */
    public function uploadImage(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $res = Uploader::uploadImage('file');
        return $httpRes->withJson($res->toArray());
    }

    /**
     * Get list of options for an association separating by items the user already manages, and others
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param $args
     * @return Response
     */
    public function getSelectionListForAssociation(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $associations = $this->associationsTool->getAssociations($_POST['association'], $_POST['id'], $this->view['user']);

        //simplify data
        $data=['optionItems'=>[]];
        foreach($associations['optionItems'] as $item){
            /** @var Profile $item */
            $data['optionItems'][]=array('id'=>$item->getId(), 'name'=>$item->getName(), 'link'=>$item->getImageLink());
        }
        if(isSet($associations['others'])){
            foreach($associations['others'] as $other){
                /** @var Profile $other */
                $data['others'][]=array('id'=>$other->getId(), 'name'=>$other->getName(), 'link'=>$other->getImageLink());
            }
        }

        $res = new SuccessResponse();
        $res->setData($data);
        return $httpRes->withJson($res->toArray());
    }

    /**
     * Removes access of instructor from organization. Works from both panels. Only lets you remove access to things you own.
     * @param ServerRequestInterface $httpReq
     * @param Response $httpRes
     * @param $args
     * @return Response
     */
    public function removeInstructorOrganizationAccess(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];

        $res = $this->instructorTool->removeInstructorOrganizationAssociation($user, $_POST['instructorId'], $_POST['organizationId']);
        return $httpRes->withJson($res->toArray());
    }

    public function updateOrganizationActivation(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $res = $this->organizationTool->updateActivation($_POST['organizationId'], filter_var($_POST['isOn'], FILTER_VALIDATE_BOOLEAN));
        return $httpRes->withJson($res->toArray());
    }

    public function deleteOrganization(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $res = $this->organizationTool->deleteOrganization($_POST['organizationId']);
        return $httpRes->withJson($res->toArray());
    }

    public function updateInstructorActivation(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $res = $this->instructorTool->updateActivation($_POST['instructorId'], filter_var($_POST['isOn'], FILTER_VALIDATE_BOOLEAN));
        return $httpRes->withJson($res->toArray());
    }

    public function deleteInstructor(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $res = $this->instructorTool->deleteInstructor($_POST['instructorId']);
        return $httpRes->withJson($res->toArray());
    }

    public function updateEventActivation(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $res = $this->eventTool->updateActivation(
            $user,
            $_POST['eventId'], 
            filter_var($_POST['isOn'], FILTER_VALIDATE_BOOLEAN),
            (isSet($_POST['repeatSelection'])) ? $_POST['repeatSelection'] : null, 
            (isSet($_POST['instanceDate'])) ? $_POST['instanceDate'] : null
        );
        return $httpRes->withJson($res->toArray());
    }

    public function deleteEvent(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $user = $this->view['user'];
        $res = $this->eventTool->deleteEvent(
            $user,
            $_POST['eventId'],
            (isSet($_POST['repeatSelection'])) ? $_POST['repeatSelection'] : null,
            (isSet($_POST['instanceDate'])) ? $_POST['instanceDate'] : null
        );
        return $httpRes->withJson($res->toArray());
    }

    public function updatePageActivation(ServerRequestInterface $httpReq, Response $httpRes, $args){
        try{
            $isOn = filter_var($_POST['isOn'], FILTER_VALIDATE_BOOLEAN);
            $this->persistenceService->updatePageActivation($_POST['pageId'], $isOn);
            $res = new SuccessResponse(($isOn) ? 'Activated' : "Deactivated");
        }
        catch(ClientErrorException $e){
            $res = BaseResponse::generateClientErrorResponse($e);
        }
        return $httpRes->withJson($res->toArray());
    }

    public function deletePage(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $page = $this->persistenceService->getPage($_POST['pageId']);
        if($page != null && $page->isLocked()) return new FailResponse("You cannot delete a locked page. Deactivate instead");
        $this->persistenceService->deletePage($_POST['pageId']);
        $res = new SuccessResponse('Deleted Page');
        return $httpRes->withJson($res->toArray());
    }

    public function getEmailProfiles(ServerRequestInterface $httpReq, Response $httpRes, $args){
        if(empty($_POST['ids'])){
            return $httpRes;
        }
        if($_POST['type'] == 'instructorProfiles'){
            return $this->view->render($httpRes, 'email_templates/rendered/profiles.twig', ['profiles'=>$this->persistenceService->getInstructorsByIds($_POST['ids']), 'domain'=>UrlTool::myDomain(), 'type'=>'instructor']);
        }
        else if ($_POST['type'] == 'organizationProfiles'){
            return $this->view->render($httpRes, 'email_templates/rendered/profiles.twig', ['profiles'=>$this->persistenceService->getOrganizationsByIds($_POST['ids']), 'domain'=>UrlTool::myDomain(), 'type'=>'organization']);
        }
        else{
            return $httpRes->withStatus(400);
        }
    }
    

    public function removeOrganizationAccessFromUser(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $organization = $this->persistenceService->getOrganization($_POST['organizationId']);
        $res = $this->organizationTool->removeAccess($this->view['user'], $organization);
        return $httpRes->withJson($res->toArray());
    }

    public function removeInstructorAccessFromUser(ServerRequestInterface $httpReq, Response $httpRes, $args){
        $instructor = $this->persistenceService->getInstructor($_POST['instructorId']);
        $res = $this->instructorTool->removeAccess($this->view['user'], $instructor);
        return $httpRes->withJson($res->toArray());
    }

    public function loadEventListHtml(ServerRequestInterface $httpReq, Response $httpRes, $args){
        list($data, $filters) = $this->filteringService->processFilters($_POST, true);
        $data['events'] = $this->eventTool->loadEvents($filters, null, true);
        $data['domain'] = UrlTool::myDomain();
        return $this->view->render($httpRes, 'email_templates/rendered/email_event_list.twig', $data);
    }
}