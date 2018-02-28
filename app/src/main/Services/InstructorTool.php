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
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\InstructorTeachesEvent;
use EricNewbury\DanceVT\Models\Persistence\InstructorTeachesForOrganization;
use EricNewbury\DanceVT\Models\Persistence\PermissionRequest;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Models\Persistence\UserManagesInstructor;
use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Util\Validator;

class InstructorTool
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
     * InstructorTool constructor.
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
        $instructor = $this->persistenceService->getInstructor($id);
        if ($isOn){
            $instructor->setActive(true);
            $res->setData(['message'=>'Instructor Activated']);
        }
        else{
            $instructor->setActive(false);
            $res->setData(['message'=>'Instructor Deactivated']);
        }
        $this->persistenceService->persistChanges();

        $res->setStatus(BaseResponse::SUCCESS);
        return $res;
    }

    public function deleteInstructor($instructorId)
    {
        $this->persistenceService->deleteInstructor($instructorId);
        return new SuccessResponse('Instructor deleted.');
    }

    /**
     * @param array $data
     * @param User $user
     * @param Instructor $instructor
     * @return BaseResponse
     */
    public function updateInstructor($data, $user, $instructor = null){
        $new = false;
        if($instructor == null){
            $new = true;
            $instructor = new Instructor();
            $this->persistenceService->persistItem($instructor);
            $this->persistenceService->persistChanges();
        }

        //remove phone formatting
        if($data['phone'] !=null) {
            $data['phone'] = preg_replace("/[^0-9]/", "", $data['phone']);
        }

        //replace blank with null
        foreach($data as $key => $value){
            if($value === ""){
                $data[$key] = null;
            }
        }
        $instructor->setActive((isSet($data['active']) && $data['active'] == 'on'));
        /** @var Category $category */
        $category = $this->persistenceService->getCategory($data['categoryId']);
        $instructor->setCategory($category);
        $instructor->setName($data['name']);

        $instructor->setImageLink($data['imageLink']);
        $instructor->setThumbLink($data['thumbLink']);
        $instructor->setCoverPhoto($data['coverPhoto']);
        $instructor->setDescription($this->htmlPurifier->purify($data['description']));
        $instructor->setLocation($data['location']);
        $instructor->setCoordinates($data['coordinates']);
        $instructor->setCounty($data['county']);
        $instructor->setEmail($data['email']);
        $instructor->setPhone($data['phone']);
        $website = ($data['website'] === null || substr($data['website'], 0, 4) === "http" ) ? $data['website'] : 'http://'.$data['website'];
        $facebook = ($data['facebook'] === null || substr($data['facebook'], 0, 4) === "http" ) ? $data['facebook'] : 'http://'.$data['facebook'];
        $twitter = ($data['twitter'] === null || substr($data['twitter'], 0, 4) === "http" ) ? $data['twitter'] : 'http://'.$data['twitter'];
        $instructor->setWebsite($website);
        $instructor->setFacebook($facebook);
        $instructor->setTwitter($twitter);
        if(!$instructor->activeIsSet()){
            $instructor->setActive(false);
        }

        try{
            $request = json_decode($data['request'], true);
            $request['id'] = $instructor->getId();
            $this->associationsTool->updateAssociations($request, $user);
            $this->validator->validateInstructor($instructor);
            $this->persistenceService->persistChanges();

            $res = new BaseResponse();
            $res->setStatus(BaseResponse::SUCCESS)->setData(['message'=>'Updated Successfully', 'instId'=>$instructor->getId()]);
            return $res;
        }
        catch(ClientErrorException $e){
            if($new){
                $this->persistenceService->deleteEntity(Instructor::class, $instructor->getId());
            }
            return BaseResponse::generateClientErrorResponse($e);
        }
        catch(InternalErrorException $e){
            if($new){
                $this->persistenceService->deleteEntity(Instructor::class, $instructor->getId());
            }
            return BaseResponse::generateInternalErrorResponse($e);
        }

    }


    /**
     * @param User $user
     * @param int $instructorId
     * @param int $organizationId
     * @return BaseResponse
     */
    public function removeInstructorOrganizationAssociation($user, $instructorId, $organizationId)
    {
        $instRef = $this->persistenceService->getInstructorRef($instructorId);
        $orgRef = $this->persistenceService->getOrganizationRef($organizationId);

        if (
            ($user->isActiveInstructorAdmin() || $user->isActiveOrganizationAdmin()) &&
            ($this->persistenceService->getUserOrganizationAssociation($user, $orgRef) !== null || $this->persistenceService->getUserInstructorAssociation($user, $instRef) !== null)
        ) {
            $this->persistenceService->removeInstructorOrganizationAssociation($instructorId, $organizationId);
            $this->persistenceService->removeInstructorOrganizationRequest($instRef, $orgRef);

            return new SuccessResponse('Successfully removed access.');

        } else {
            return new FailResponse('You do not have access to remove access');
        }
    }

    /**
     * @param User $user
     * @param Instructor $instructor
     * @return BaseResponse
     */
    public function removeAccess($user, $instructor)
    {
        $this->persistenceService->removeUserInstructorAssociation($user->getId(), $instructor->getId());
        $this->persistenceService->removeUserInstructorRequest($user, $instructor);

        return new SuccessResponse('Access removed');
    }

    /**
     * @param User $requestingUser
     * @param string $permissionAction
     * @param PermissionRequest $request
     * @return BaseResponse
     */
    public function respondToUserInstructorRequest($requestingUser, $permissionAction, $request)
    {

        //check is admin or a valid instructor admin
        if (
            !($requestingUser && $requestingUser->isApprovedSiteAdmin()) &&
            !$this->isAdminForInstructor($requestingUser, $request->getInstructor())
        ) {
            return new FailResponse('Invalid privilege to respond to this request');
        }

        //get user and instructor in question
        $requestedUser = $request->getUser();
        $instructor = $request->getInstructor();
        $association = $this->persistenceService->getUserInstructorAssociation($requestedUser, $instructor);

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
            $this->persistenceService->deleteEntity(UserManagesInstructor::class, $association->getId());
            $request->setCompleted(PermissionStatus::DENIED);
            $res = new SuccessResponse('Denied');
        }

        $this->persistenceService->persistChanges();
        return $res;
    }

    public function isAdminForInstructor($user, $instructor){
        $permission = $this->persistenceService->getUserInstructorAssociation($user, $instructor);
        return ($permission !==null && $permission->isApproved());
    }

    /**
     * @param User $requestingUser
     * @param string $permissionAction
     * @param PermissionRequest $request
     * @return BaseResponse
     */
    public function respondToInstructorAssociationRequest($requestingUser, $permissionAction, $request)
    {
        //check is admin or a valid instructor admin
        if (
            !($requestingUser && $requestingUser->isApprovedSiteAdmin()) &&
            !$this->isAdminForInstructor($requestingUser, $request->getInstructor())
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
    public function respondToTeachingEventRequest($requestingUser, $permissionAction, $request)
    {
        //check is admin or a valid instructor admin
        if (
            !($requestingUser && $requestingUser->isApprovedSiteAdmin()) &&
            !$this->isAdminForInstructor($requestingUser, $request->getInstructor())
        ) {
            return new FailResponse('Invalid privilege to respond to this request');
        }

        //get user and organization in question
        $instructor = $request->getInstructor();
        $event = $request->getEvent();
        $association = $this->persistenceService->getInstructorEventAssociation($instructor, $event);

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
            $this->persistenceService->deleteEntity(InstructorTeachesEvent::class, $association->getId());
            $request->setCompleted(PermissionStatus::DENIED);
            $res = new SuccessResponse('Denied');
        }

        $this->persistenceService->persistChanges();
        return $res;
    }



}