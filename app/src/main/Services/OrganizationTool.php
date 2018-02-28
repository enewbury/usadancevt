<?php
/**
 * Created by enewbury.
 * Date: 12/31/15
 */

namespace EricNewbury\DanceVT\Services;


use EricNewbury\DanceVT\Constants\Association;
use EricNewbury\DanceVT\Constants\PermissionAction;
use EricNewbury\DanceVT\Constants\PermissionStatus;
use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;
use EricNewbury\DanceVT\Models\Persistence\Category;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Persistence\InstructorTeachesForOrganization;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Models\Persistence\OrganizationHostsEvent;
use EricNewbury\DanceVT\Models\Persistence\PermissionRequest;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Models\Persistence\UserManagesOrganization;
use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Util\Validator;

class OrganizationTool
{

    /** @var  PersistenceService $persistenceService */
    private $persistenceService;

    /** @var  AssociationsTool $associationsTool */
    private $associationsTool;

    /** @var  MailService $mailService */
    private $mailService;

    /** @var  Validator $validator */
    private $validator;

    /** @var  \HTMLPurifier $htmlPurifier */
    private $htmlPurifier;

    /**
     * OrganizationTool constructor.
     * @param PersistenceService $persistenceService
     * @param AssociationsTool $associationsTool
     * @param MailService $mailService
     * @param Validator $validator
     * @param \HTMLPurifier $htmlPurifier
     */
    public function __construct(PersistenceService $persistenceService, AssociationsTool $associationsTool, MailService $mailService, Validator $validator, \HTMLPurifier $htmlPurifier)
    {
        $this->persistenceService = $persistenceService;
        $this->associationsTool = $associationsTool;
        $this->mailService = $mailService;
        $this->validator = $validator;
        $this->htmlPurifier = $htmlPurifier;
    }
    

    public function updateActivation($id, $isOn){
        $res = new BaseResponse();
        $organization = $this->persistenceService->getOrganization($id);
        if ($isOn){
            $organization->setActive(true);
            $res->setData(['message'=>'Organization activated.']);
        }
        else{
            $organization->setActive(false);
            $res->setData(['message'=>'Organization deactivated.']);
        }
        $this->persistenceService->persistChanges();

        $res->setStatus(BaseResponse::SUCCESS);
        return $res;
    }

    public function deleteOrganization($organizationId)
    {
        $this->persistenceService->deleteOrganization($organizationId);
        return new SuccessResponse('Organization deleted.');
    }

    /**
     * @param array $data
     * @param User $user
     * @param Organization $organization
     * @return BaseResponse
     */
    public function updateOrganization($data, $user, $organization = null){
        $new = false;
        if($organization == null){
            $new = true;
            $organization = new Organization();
            $this->persistenceService->persistItem($organization);
            $this->persistenceService->persistChanges();
        }

        //replace blank with null
        foreach($data as $key => $value){
            if($value === ""){
                $data[$key] = null;
            }
        }

        //remove phone formatting
        if($data['phone'] !=null) {
            $data['phone'] = preg_replace("/[^0-9]/", "", $data['phone']);
        }

        $organization->setActive((isSet($data['active']) && $data['active'] == 'on'));
        
        /** @var Category $category */
        $category = $this->persistenceService->getCategory($data['categoryId']);
        $organization->setCategory($category);
        $organization->setName($data['name']);

        $organization->setImageLink($data['imageLink']);
        $organization->setThumbLink($data['thumbLink']);
        $organization->setCoverPhoto($data['coverPhoto']);
        $organization->setLocation($data['location']);
        $organization->setCoordinates($data['coordinates']);
        $organization->setCounty($data['county']);
        $organization->setDescription($this->htmlPurifier->purify($data['description']));
        $organization->setEmail($data['email']);
        $organization->setPhone($data['phone']);

        $website = ($data['website'] === null || substr($data['website'], 0, 4 ) === "http") ? $data['website'] : 'http://'.$data['website'];
        $facebook = ($data['facebook'] === null || substr($data['facebook'], 0, 4) === "http" ) ? $data['facebook'] : 'http://'.$data['facebook'];
        $twitter = ($data['twitter'] === null || substr($data['twitter'], 0, 4) === "http" ) ? $data['twitter'] : 'http://'.$data['twitter'];
        $organization->setWebsite($website);
        $organization->setFacebook($facebook);
        $organization->setTwitter($twitter);



        try{
            //update associations
            $request =json_decode($data['request'], true);
            $request['id'] = $organization->getId();
            $this->associationsTool->updateAssociations($request, $user);
            //validate fields
            $this->validator->validateOrganization($organization);
            $this->persistenceService->persistChanges();

            $res = new SuccessResponse();
            $res->setData(['message'=>'Updated Successfully', 'orgId'=>$organization->getId()]);
            return $res;
        }
        catch(ClientErrorException $e){
            if($new){
                $this->persistenceService->deleteEntity(Organization::class, $organization->getId());
            }
            return BaseResponse::generateClientErrorResponse($e);
        }
        catch(InternalErrorException $e){
            if($new){
                $this->persistenceService->deleteEntity(Organization::class, $organization->getId());
            }
            return BaseResponse::generateInternalErrorResponse($e);
        }

    }

    /**
     * @param User $user
     * @param Organization $organization
     * @return BaseResponse
     */
    public function removeAccess($user, $organization)
    {
        $this->persistenceService->removeUserOrganizationAssociation($user->getId(), $organization->getId());
        $this->persistenceService->removeUserOrganizationRequest($user, $organization);
        return new SuccessResponse('Access removed');
    }

    /**
     * @param User $requestingUser
     * @param string $permissionAction
     * @param PermissionRequest $request
     * @return BaseResponse
     */
    public function respondToUserOrganizationRequest($requestingUser, $permissionAction, $request)
    {

        //check is admin or a valid instructor admin
        if (
            !($requestingUser && $requestingUser->isApprovedSiteAdmin()) &&
            !$this->isAdminForOrganization($requestingUser, $request->getOrganization())
        ) {
            return new FailResponse('Invalid privilege to respond to this request');
        }

        //get user and organization in question
        $requestedUser = $request->getUser();
        $organization = $request->getOrganization();
        $association = $this->persistenceService->getUserOrganizationAssociation($requestedUser, $organization);

        //check that there actually is still an association
        if($association === null){
            $this->persistenceService->deleteEntity(PermissionRequest::class, $request->getId());
            return new FailResponse('Request not found');
        }

        //change permission & complete permissionRequest
        if($permissionAction === PermissionAction::APPROVE){
            $association->setApproved(true);
            $request->setCompleted(PermissionStatus::APPROVED);
            $res = new SuccessResponse('Approved');
        }
        else{
            $this->persistenceService->deleteEntity(UserManagesOrganization::class, $association->getId());
            $request->setCompleted(PermissionStatus::DENIED);
            $res = new SuccessResponse('Denied');
        }

        $this->persistenceService->persistChanges();
        return $res;
    }

    public function isAdminForOrganization($user, $organization){
        $permission = $this->persistenceService->getUserOrganizationAssociation($user, $organization);
        return ($permission !==null && $permission->isApproved());
    }

    /**
     * @param User $requestingUser
     * @param string $permissionAction
     * @param PermissionRequest $request
     * @return BaseResponse
     */
    public function respondToTeachingForOrganizationRequest($requestingUser, $permissionAction, $request)
    {
        //check is admin or a valid instructor admin
        if (
            !($requestingUser && $requestingUser->isApprovedSiteAdmin()) &&
            !$this->isAdminForOrganization($requestingUser, $request->getOrganization())
        ) {
            return new FailResponse('Invalid privilege to respond to this request');
        }

        //get user and organization in question
        $instructor = $request->getInstructor();
        $organization = $request->getOrganization();
        $association = $this->persistenceService->getInstructorOrganizationAssociation($instructor, $organization);

        //check that there actually is still an association
        if($association === null){

            $this->persistenceService->deleteEntity(PermissionRequest::class, $request->getId());
            return new FailResponse('Request not found');
        }

        //change permission & complete permissionRequest
        if($permissionAction === PermissionAction::APPROVE){
            $association->setApproved(true);
            $request->setCompleted(PermissionStatus::APPROVED);
            $res = new SuccessResponse('Approved');
        }
        else{
            $this->persistenceService->deleteEntity(InstructorTeachesForOrganization::class, $association->getId());
            $request->setCompleted(PermissionStatus::DENIED);
            $res = new SuccessResponse('Denied');
        }

        $this->persistenceService->persistChanges();
        return $res;
    }

    /**
     * @param User $requestingUser
     * @param string $permissionAction
     * @param PermissionRequest $request
     * @return BaseResponse
     */
    public function respondToHostingEventRequest($requestingUser, $permissionAction, $request)
    {
        //check is admin or a valid instructor admin
        if (
            !($requestingUser && $requestingUser->isApprovedSiteAdmin()) &&
            !$this->isAdminForOrganization($requestingUser, $request->getInstructor())
        ) {
            return new FailResponse('Invalid privilege to respond to this request');
        }

        //get user and organization in question
        $organization = $request->getOrganization();
        $event = $request->getEvent();
        $association = $this->persistenceService->getOrganizationEventAssociation($organization, $event);

        //check that there actually is still an association
        if($association === null){
            $this->persistenceService->deleteEntity(PermissionRequest::class, $request->getId());
            return new FailResponse('Request not found');
        }

        //change permission & complete permissionRequest
        if($permissionAction === PermissionAction::APPROVE){
            $association->setApproved(true);
            $request->setCompleted(PermissionStatus::APPROVED);
            $res = new SuccessResponse('Approved');
        }
        else{
            $this->persistenceService->deleteEntity(OrganizationHostsEvent::class, $association->getId());
            $request->setCompleted(PermissionStatus::DENIED);
            $res = new SuccessResponse('Denied');
        }

        $this->persistenceService->persistChanges();
        return $res;

    }
}