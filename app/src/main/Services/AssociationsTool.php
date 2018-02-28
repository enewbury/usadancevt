<?php
/**
 * Created by enewbury.
 * Date: 12/22/15
 */

namespace EricNewbury\DanceVT\Services;



use EricNewbury\DanceVT\Constants\PermissionStatus;
use EricNewbury\DanceVT\Constants\Association;
use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;
use EricNewbury\DanceVT\Models\Persistence\Event;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Models\Request\AssociationsUpdateRequest;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Models\Response\ErrorResponse;
use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Util\AuthorizationTool;

class AssociationsTool
{
    
    /** @var  PersistenceService $persistenceService */
    private $persistenceService;

    /** @var  MailService $mailService */
    private $mailService;
    
    /** @var AuthorizationTool $authorizationTool */
    private $authorizationTool;

    /**
     * AssociationsTool constructor.
     * @param PersistenceService $persistenceService
     * @param MailService $mailService
     * @param AuthorizationTool $authorizationTool
     */
    public function __construct(PersistenceService $persistenceService, MailService $mailService, AuthorizationTool $authorizationTool)
    {
        $this->persistenceService = $persistenceService;
        $this->mailService = $mailService;
        $this->authorizationTool = $authorizationTool;
    }


    /**
     * @param string $association
     * @param int $id
     * @param User $user
     * @return array
     */
    public function getAssociations($association, $id, $user)
    {
        $data = ['association'=>$association];
        if($association == Association::ADMIN){
            $user = $this->persistenceService->getUser($id);
            $data['user']=$user;
        }
        else if($association == Association::USER_MANAGES_ORGANIZATION){
            $user = $this->persistenceService->getUser($id);
            $data['user']=$user;
            $data['optionItems'] = $this->persistenceService->getOrganizationsExcludingSet($user->getManagedOrganizations());

        }
        else if ($association == Association::USER_MANAGES_INSTRUCTOR){
            $user = $this->persistenceService->getUser($id);
            $data['user']=$user;
            $data['optionItems'] = $this->persistenceService->getInstructorsExcludingSet($user->getManagedInstructors());
        }
        else if($association == Association::INSTRUCTOR_TEACHES_FOR_ORGANIZATION){
            if(empty($id)){
                $data['optionItems']=$this->persistenceService->getAllOrganizations();
            }
            else{
                $instructor = $this->persistenceService->getInstructor($id);
                $data = $this->getCategorizedOrganizations($instructor->getOrganizations(), $user);
                $data['instructor']=$instructor;
            }
        }
        else if($association == Association::ORGANIZATION_HAS_INSTRUCTOR){
            if(empty($id)){
                $data['optionItems']=$this->persistenceService->getAllInstructors();
            }
            else {
                $organization = $this->persistenceService->getOrganization($id);
                $data = $this->getCategorizedInstructors($organization->getInstructors(), $user);
                $data['organization'] = $organization;
            }
        }
        else if($association == Association::INSTRUCTOR_TEACHES_EVENT) {
            if(empty($id)){
                $data = $this->getCategorizedInstructors([], $user);
            }
            else {
                $event = $this->persistenceService->getEvent($id);
                $data = $this->getCategorizedInstructors($event->getInstructors(), $user);
                $data['event'] = $event;
            }
        }
        else if($association == Association::ORGANIZATION_HOSTS_EVENT){
            if(empty($id)){
                $data = $this->getCategorizedOrganizations([], $user);
            }
            else {
                $event = $this->persistenceService->getEvent($id);
                $data = $this->getCategorizedOrganizations($event->getOrganizations(), $user);
                $data['event'] = $event;
            }
        }

        return $data;
    }


    /**
     * @param array $request
     * @param User $user
     * @param bool $new
     * @return BaseResponse
     * @throws ClientErrorException
     */
    public function updateAssociations($request, $user, $new = false)
    {
        $data = new AssociationsUpdateRequest($request);

        if($data->association == Association::USER_MANAGES_ORGANIZATION && $user->isApprovedSiteAdmin()){
            $this->updateUserOrganization($data, true);
        }
        else if ($data->association == Association::USER_MANAGES_INSTRUCTOR && $user->isApprovedSiteAdmin()){
            $this->updateUserInstructor($data, true);
        }
        else if ($data->association == Association::INSTRUCTOR_TEACHES_FOR_ORGANIZATION){
            $this->updateInstructorOrganization($data,  $user);
        }
        else if ($data->association == Association::ORGANIZATION_HAS_INSTRUCTOR){
            $this->updateOrganizationInstructor($data, $user);
        }
        else if ($data->association == Association::INSTRUCTOR_TEACHES_EVENT){
            $this->updateEventInstructor($data, $user, $new);
        }
        else if ($data->association == Association::ORGANIZATION_HOSTS_EVENT){
            $this->updateEventOrganization($data, $user, $new);
        }
        else{
            throw new ClientErrorException("Invalid association action.");
        }
        
    }


    /**
     * Only called by admins
     * @param AssociationsUpdateRequest $data
     * @param bool $approvalForNew
     */
    public function updateUserOrganization($data, $approvalForNew)
    {
        if (isSet($data->active)) {
            $this->persistenceService->updateOrganizationAdminPermission($this->persistenceService->getUser($data->id), $data->active);
        }

        foreach ($data->organizations as $org) {
            //remove association
            if (isSet($org->action) and $org->action == 'remove') {
                $this->persistenceService->removeUserOrganizationAssociation($data->id, $org->id);
                //remove requests
                $this->persistenceService->deletePermissionRequest(Association::USER_MANAGES_ORGANIZATION, $data->id, $org->id);
            } //add or update approval
            else {
                //add
                if (isSet($org->action) and $org->action == 'add') {
                    $this->persistenceService->newUserManagesOrganization($data->id, $org->id, $approvalForNew);
                }
                //update approval
                if (isSet($org->approved)) {
                    $this->persistenceService->updateUserOrganizationApproval($data->id, $org->id, $org->approved);
                    //complete any open requests
                    $completed = ($org->approved) ? PermissionStatus::APPROVED : PermissionStatus::DENIED;
                    $this->persistenceService->completePermission(Association::USER_MANAGES_ORGANIZATION, $completed, $data->id, $org->id);
                }
            }
        }
    }

    /**
     * Only called by admins
     * @param AssociationsUpdateRequest $data
     * @param bool $approvalForNew
     */
    public function updateUserInstructor($data, $approvalForNew)
    {
        if (isSet($data->active)) {
            $this->persistenceService->updateInstructorAdminPermission($this->persistenceService->getUser($data->id), $data->active);
        }

        foreach ($data->instructors as $instructor) {
            //remove association
            if (isSet($instructor->action) and $instructor->action == 'remove') {
                $this->persistenceService->removeUserInstructorAssociation($data->id, $instructor->id);
                $this->persistenceService->deletePermissionRequest(Association::USER_MANAGES_INSTRUCTOR, $data->id, null, $instructor->id);
            } //add or update approval
            else {
                //add
                if (isSet($instructor->action) and $instructor->action == 'add') {
                    $this->persistenceService->newUserManagesInstructor($data->id, $instructor->id, $approvalForNew);
                }
                //update approval
                if (isSet($instructor->approved)) {
                    $this->persistenceService->updateUserInstructorApproval($data->id, $instructor->id, $instructor->approved);
                    //complete any open requests
                    $completed = ($instructor->approved) ? PermissionStatus::APPROVED : PermissionStatus::DENIED;
                    $this->persistenceService->completePermission(Association::USER_MANAGES_INSTRUCTOR, $completed, $data->id, null, $instructor->id);
                }
            }
        }
    }

    /**
     * @param AssociationsUpdateRequest $data
     * @param User $user
     */
    public function updateInstructorOrganization($data, $user)
    {

        foreach ($data->organizations as $organization) {
            //remove association
            if (isSet($organization->action) and $organization->action == 'remove') {
                $this->persistenceService->removeInstructorOrganizationAssociation($data->id, $organization->id);
                $this->persistenceService->deletePermissionRequest(Association::INSTRUCTOR_TEACHES_FOR_ORGANIZATION, null, $organization->id, $data->id);
            } //add or update approval
            else {
                //add
                if (isSet($organization->action) and $organization->action == 'add') {
                    if($user->isApprovedSiteAdmin() || ($this->authorizationTool->isValidInstructorAdmin($user, $data->id) && $this->authorizationTool->isValidOrganizationAdmin($user, $organization->id))){
                        $this->persistenceService->newInstructorOrganizationAssociation($data->id, $organization->id, $organization->approved);
                    }
                    else if($this->authorizationTool->isValidInstructorAdmin($user, $data->id)){
                        $this->requestInstructorTeachesForOrganizations($user, $data->id, $organization->id);
                    }
                    else{
                        //no op, user doesn't have the right permissions
                    }

                }
                //update approval (admins only)
                else if (isSet($organization->approved)  && $user->isApprovedSiteAdmin()) {
                    $this->persistenceService->updateInstructorOrganizationAssociationStatus($data->id, $organization->id, $organization->approved, $user);
                    //complete any open requests
                    $completed = ($organization->approved) ? PermissionStatus::APPROVED : PermissionStatus::DENIED;
                    $this->persistenceService->completePermission(Association::INSTRUCTOR_TEACHES_FOR_ORGANIZATION, $completed, null, $organization->id, $data->id);
                }
            }
        }
    }

    /**
     * @param AssociationsUpdateRequest $data
     * @param User $user
     */
    public function updateOrganizationInstructor($data, $user)
    {

        foreach ($data->instructors as $instructor) {
            //remove association
            if (isSet($instructor->action) and $instructor->action == 'remove') {
                $this->persistenceService->removeInstructorOrganizationAssociation($instructor->id, $data->id);
                $this->persistenceService->deletePermissionRequest(Association::ORGANIZATION_HAS_INSTRUCTOR, null, $data->id, $instructor->id);
            } //add or update approval
            else {
                //add
                if (isSet($instructor->action) and $instructor->action == 'add') {
                    //sets up emails if it is a request not a admin action
                    if($user->isApprovedSiteAdmin()  || ($this->authorizationTool->isValidOrganizationAdmin($user, $data->id) && $this->authorizationTool->isValidInstructorAdmin($user, $instructor->id))){
                        $this->persistenceService->newInstructorOrganizationAssociation($instructor->id, $data->id, $instructor->approved);
                    }
                    else if($this->authorizationTool->isValidOrganizationAdmin($user, $data->id)){
                        $this->requestOrganizationHasInstructor($user, $data->id, $instructor->id);
                    }
                    else{
                        //no op user doesn't have right permissions
                    }

                }
                //update approval
                if (isSet($instructor->approved)  && $user->isApprovedSiteAdmin()) {
                    $this->persistenceService->updateInstructorOrganizationAssociationStatus($instructor->id, $data->id, $instructor->approved, $user);
                    //complete any open requests
                    $completed = ($instructor->approved) ? PermissionStatus::APPROVED : PermissionStatus::DENIED;
                    $this->persistenceService->completePermission(Association::ORGANIZATION_HAS_INSTRUCTOR, $completed, null, $data->id, $instructor->id);
                }
            }
        }
    }

    /**
     * @param AssociationsUpdateRequest $data
     * @param User $user
     * @param bool $newEvent
     */
    private function updateEventInstructor($data, $user, $newEvent=false)
    {
        foreach ($data->instructors as $instructor) {
            //remove association
            if (isSet($instructor->action) and $instructor->action == 'remove') {
                $this->persistenceService->removeEventInstructorAssociation($data->id, $instructor->id);
                $this->persistenceService->deletePermissionRequest(Association::INSTRUCTOR_TEACHES_EVENT, null, null, $instructor->id, $data->id);
            } //add or update approval
            else {
                //add
                if (isSet($instructor->action) and $instructor->action == 'add') {
                    //sets up emails if it is a request not a admin action
                    if($user->isApprovedSiteAdmin() || ($this->authorizationTool->isValidInstructorAdmin($user, $instructor->id) && $this->authorizationTool->isAdminOrValidEventAdmin($user, $data->id, $newEvent))){
                        $this->persistenceService->newEventInstructorAssociation($data->id, $instructor->id, $instructor->approved);
                    }
                    else{
                        $this->requestInstructorTeachesEvent($user, $data->id, $instructor->id);
                    }

                }
                //update approval
                if (isSet($instructor->approved)  && $user->isApprovedSiteAdmin()) {
                    $this->persistenceService->updateEventInstructorAssociationStatus($data->id, $instructor->id, $instructor->approved, $user);
                    //complete any open requests
                    $completed = ($instructor->approved) ? PermissionStatus::APPROVED : PermissionStatus::DENIED;
                    $this->persistenceService->completePermission(Association::INSTRUCTOR_TEACHES_EVENT, $completed, null, null, $instructor->id, $data->id);
                }
            }
        }
    }

    /**
     * @param AssociationsUpdateRequest $data
     * @param User $user
     * @param bool $newEvent
     */
    private function updateEventOrganization($data, $user, $newEvent = false)
    {
        foreach ($data->organizations as $organization) {
            //remove association
            if (isSet($organization->action) and $organization->action == 'remove') {
                $this->persistenceService->removeEventOrganizationAssociation($data->id, $organization->id);
                $this->persistenceService->deletePermissionRequest(Association::ORGANIZATION_HOSTS_EVENT, null, $organization->id, null, $data->id);
            } //add or update approval
            else {
                //add
                if (isSet($organization->action) and $organization->action == 'add') {
                    //sets up emails if it is a request not a admin action
                    if($user->isApprovedSiteAdmin() || ($this->authorizationTool->isValidOrganizationAdmin($user, $organization->id) && $this->authorizationTool->isAdminOrValidEventAdmin($user, $data->id, $newEvent))){
                        $this->persistenceService->newEventOrganizationAssociation($data->id, $organization->id, $organization->approved);
                    }
                    else{
                        $this->requestOrganizationHostsEvent($user, $data->id, $organization->id);
                    }

                }
                //update approval only admin have this privaledge
                if (isSet($organization->approved) && $user->isApprovedSiteAdmin()) {
                    $this->persistenceService->updateEventOrganizationAssociationStatus($data->id, $organization->id, $organization->approved, $user);
                    //complete any open requests
                    $completed = ($organization->approved) ? PermissionStatus::APPROVED : PermissionStatus::DENIED;
                    $this->persistenceService->completePermission(Association::ORGANIZATION_HOSTS_EVENT, $completed, null, $organization->id, null, $data->id);
                }
            }
        }
    }

    private function requestInstructorTeachesForOrganizations($user, $instructorId, $organizationId)
    {

        if($organizationId == -1){
            return new FailResponse('You must select an organization or request a new one.');
        }
        else {
            //add instructor association
            try {
                $organization = $this->persistenceService->getOrganization($organizationId);
                $instructor = $this->persistenceService->getInstructorRef($instructorId);
                $existing = $this->persistenceService->getInstructorOrganizationAssociation($instructor, $organization);
                if ($existing != null) {
                    throw new ClientErrorException('Organization already requested');
                }
                $this->persistenceService->createPendingInstructorOrganizationAssociation($instructor, $organization);

                //save request
                $request = $this->persistenceService->createPermissionRequest(Association::INSTRUCTOR_TEACHES_FOR_ORGANIZATION, $user, $organization, $instructor);

                //send notification email
                $this->mailService->sendNotificationOfInstructorTeachesForOrganizationRequest($user, $request);

                return new SuccessResponse('Request sent.');
            }
            catch(ClientErrorException $e){
                return BaseResponse::generateClientErrorResponse($e);
            }
        }
    }

    private function requestOrganizationHasInstructor($user, $organizationId, $instructorId)
    {

        if($instructorId == -1){
            return new FailResponse('You must select an instructor or request a new one.');
        }
        else {
            //add instructor association
            try {
                $instructor = $this->persistenceService->getInstructor($instructorId);
                $organization = $this->persistenceService->getOrganizationRef($organizationId);
                $existing = $this->persistenceService->getInstructorOrganizationAssociation($instructor, $organization);
                if ($existing != null) {
                    throw new ClientErrorException('Instructor already requested');
                }
                $this->persistenceService->createPendingInstructorOrganizationAssociation($instructor, $organization);


                //save request
                $request = $this->persistenceService->createPermissionRequest(Association::ORGANIZATION_HAS_INSTRUCTOR, $user, $organization, $instructor);

                //send notification email
                $this->mailService->sendNotificationOfOrganizationHasInstructorRequest($user, $request);

                return new SuccessResponse('Request sent.');
            }
            catch(ClientErrorException $e){
                return BaseResponse::generateClientErrorResponse($e);
            }
        }

    }

    private function requestInstructorTeachesEvent($user, $eventId, $instructorId)
    {

        if($instructorId == -1){
            return new FailResponse('You must select an instructor or request a new one.');
        }
        else {
            //add instructor association
            try {
                $instructor = $this->persistenceService->getInstructor($instructorId);
                $eventRef = $this->persistenceService->getReference(Event::class, $eventId);
                $existing = $this->persistenceService->getEventInstructorAssociation($eventRef, $instructor);
                if ($existing != null) {
                    throw new ClientErrorException('Instructor already requested');
                }
                $this->persistenceService->createPendingEventInstructorAssociation($instructor, $eventRef);

                //save request
                $request = $this->persistenceService->createPermissionRequest(Association::INSTRUCTOR_TEACHES_EVENT, $user, null, $instructor, $eventRef);

                //send notification email
                $this->mailService->sendNotificationOfInstructorTeachesEventRequest($user, $request);

                return new SuccessResponse('Request sent.');
            }
            catch(ClientErrorException $e){
                return BaseResponse::generateClientErrorResponse($e);
            }
        }
    }
    private function requestOrganizationHostsEvent($user, $eventId, $organizationId)
    {

        if($organizationId == -1){
            return new FailResponse('You must select an organization or request a new one.');
        }
        else {
            //add instructor association
            try {
                $organizationRef = $this->persistenceService->getReference(Organization::class, $organizationId);
                $eventRef = $this->persistenceService->getReference(Event::class, $eventId);
                $existing = $this->persistenceService->getEventOrganizationAssociation($eventRef, $organizationRef);
                if ($existing != null) {
                    throw new ClientErrorException('Organization already requested');
                }
                //persist association
                $this->persistenceService->createPendingEventOrganizationAssociation($organizationRef, $eventRef);

                //create a request for approval
                $request = $this->persistenceService->createPermissionRequest(Association::ORGANIZATION_HOSTS_EVENT, $user, $organizationRef, null, $eventRef);

                //send notification email
                $this->mailService->sendNotificationOfOrganizationHostsEventRequest($user, $request);

                return new SuccessResponse('Request sent.');
            }
            catch(ClientErrorException $e){
                return BaseResponse::generateClientErrorResponse($e);
            }
        }
    }


    /**
     * @param Instructor[] $currentlyAssociated
     * @param User $user
     * @return array
     */
    private function getCategorizedInstructors($currentlyAssociated, $user)
    {
        $data=[];
        if($user->isApprovedSiteAdmin()){
            $instructors = $this->persistenceService->getInstructorsExcludingSet($currentlyAssociated);
            $data['optionItems'] = $instructors;
        }
        else{
            $data['optionItems']=[];
            $data['others']=[];

            $eligibleInstructors = $this->persistenceService->getInstructorsExcludingSet($currentlyAssociated);
            $instructorsManagedByUser = $user->getApprovedManagedInstructors();

            foreach($eligibleInstructors as $eligibleInstructor){
                $found = false;
                //check if in managed instructors
                foreach($instructorsManagedByUser as $key=>$managedInstructor){
                    if($eligibleInstructor->getId() === $managedInstructor->getId()){
                        $data['optionItems'][] = $eligibleInstructor;
                        $found = true;

                        //don't need to check this one again for future searches.
                        unset($instructorsManagedByUser[$key]);
                        break;
                    }
                }

                //if not in managed users ad to others
                if(!$found){
                    $data['others'][] = $eligibleInstructor;
                }
            }

        }
        return $data;
    }

    /**
     * @param $currentlyAssociated
     * @param User $user
     * @return array
     */
    private function getCategorizedOrganizations($currentlyAssociated,$user)
    {
        $data=[];
        if($user->isApprovedSiteAdmin()){
            $organizations = $this->persistenceService->getOrganizationsExcludingSet($currentlyAssociated);
            $data['optionItems'] = $organizations;
        }
        else{
            $data['optionItems']=[];
            $data['others']=[];

            $eligibleOrgs = $this->persistenceService->getOrganizationsExcludingSet($currentlyAssociated);
            $orgsManagedByUser = $user->getApprovedManagedOrganizations();

            foreach($eligibleOrgs as $eligibleOrg){
                $found = false;
                //check if in managed orgs
                foreach($orgsManagedByUser as $key=>$managedOrg){
                    if($eligibleOrg->getId() === $managedOrg->getId()){
                        $data['optionItems'][] = $eligibleOrg;
                        $found = true;

                        //don't need to check this one again for future searches.
                        unset($orgsManagedByUser[$key]);
                        break;
                    }
                }

                //if not in managed users ad to others
                if(!$found){
                    $data['others'][] = $eligibleOrg;
                }
            }

        }
        return $data;
    }


}