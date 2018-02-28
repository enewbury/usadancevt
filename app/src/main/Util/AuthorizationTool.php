<?php
/**
 * Created by enewbury.
 * Date: 4/21/16
 */

namespace EricNewbury\DanceVT\Util;


use EricNewbury\DanceVT\Models\Persistence\Event;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Services\PersistenceService;

class AuthorizationTool
{
    /** @var  PersistenceService $persistenceService */
    private $persistenceService;

    /**
     * AuthorizationTool constructor.
     * @param PersistenceService $persistenceService
     */
    public function __construct(PersistenceService $persistenceService)
    {
        $this->persistenceService = $persistenceService;
    }


    public function invalidAccountPermissionsMessaage()
    {
        return 'Invalid account permissions to perform this action';
    }

    public function isLoggedIn(){
        if(!isSet($_SESSION['userId'])){
            return false;
        }
        return true;
    }
    public function mustBeLoggedInMessage(){
        return 'You must be logged in to complete this action.';
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isAdmin($user){
        return (isSet($user) && $user->isApprovedSiteAdmin());
    }

    public function mustBeAdminMessage(){
        return 'You must be and admin to complete this action.';
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isOrganizationAdmin($user){
        return (isSet($user) && $user->isActiveOrganizationAdmin());
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isInstructorAdmin($user){
        return (isSet($user) && $user->isActiveInstructorAdmin());
    }

    /**
     * @param User $user
     * @param int $orgId
     * @return bool
     */
    public function isValidOrganizationAdmin($user, $orgId)
    {
        $organization = $this->persistenceService->getOrganizationRef($orgId);
        $permission = $this->persistenceService->getUserOrganizationAssociation($user, $organization);

        return ($permission !== null && $permission->isApproved());
    }


    public function isActiveOrPendingOrganizationAdmin($user, $orgId)
    {
        $organization = $this->persistenceService->getOrganizationRef($orgId);
        $permission = $this->persistenceService->getUserOrganizationAssociation($user, $organization);

        return ($permission !== null);
    }

    public function isValidInstructorAdmin($user, $instructorId)
    {
        $instructor = $this->persistenceService->getInstructorRef($instructorId);
        $permission = $this->persistenceService->getUserInstructorAssociation($user, $instructor);

        return($permission !== null && $permission->isApproved());
    
    }

    public function isActiveOrPendingInstructorAdmin($user, $instructorId)
    {

        $instructor = $this->persistenceService->getInstructor($instructorId);
        $permission = $this->persistenceService->getUserInstructorAssociation($user, $instructor);

        return ($permission !== null);

    }

    /**
     * @param User $user
     * @param int $orgId
     * @return bool
     */
    public function isAdminOrValidOrganizationAdmin($user, $orgId)
    {
        if ($user && $user->isApprovedSiteAdmin()) {
            return true;
        }
        /** @var Organization $organization */
        $organization = $this->persistenceService->getReference(Organization::class, $orgId);
        $association = $this->persistenceService->getUserOrganizationAssociation($user, $organization);
        if ($association !== null && $association->isApproved()) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param int $instructorId
     * @return bool
     */
    public function isAdminOrValidInstructorAdmin($user, $instructorId)
    {
        if ($user && $user->isApprovedSiteAdmin()) {
            return true;
        }

        /** @var Instructor $instructor */
        $instructor = $this->persistenceService->getReference(Instructor::class, $instructorId);
        $association = $this->persistenceService->getUserInstructorAssociation($user, $instructor);
        if ($association !== null && $association->isApproved()) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param int $eventId
     * @param bool $new
     * @return bool
     */
    public function isAdminOrValidEventAdmin($user, $eventId, $new=false)
    {
        if ($user && $user->isApprovedSiteAdmin()) {
            return true;
        }
        if($eventId === 'new' || $new == true) return true;
        /** @var Event $event */
        $event = $this->persistenceService->getEvent($eventId);
        if ($event === null){
            return false;
        }
        $valid = false;
        foreach($event->getApprovedInstructors() as $approvedInstructor){
            if($valid) break;
            $valid = $this->isValidInstructorAdmin($user, $approvedInstructor->getId());
        }
        foreach($event->getApprovedOrganizations() as $approvedOrganization){
            if($valid) break;
            $valid = $this->isValidOrganizationAdmin($user, $approvedOrganization->getId());
        }
        if ($valid) {
            return true;
        }
        return false;
    }

    public function isValidInstructorForEvent($instructorId, $eventId)
    {
        if($eventId === 'new') return true;
        $event = $this->persistenceService->getEvent($eventId);
        if ($event === null){
            return false;
        }
        $valid = false;
        foreach($event->getApprovedInstructors() as $approvedInstructor){
            $valid = ($approvedInstructor->getId() == $instructorId);
            if($valid) break;
        }
        if($valid) return true;
        return false;
    }
    public function isValidOrganizationForEvent($organizationId, $eventId)
    {
        if($eventId === 'new') return true;
        $event = $this->persistenceService->getEvent($eventId);
        if ($event === null) return false;
        $valid = false;
        foreach($event->getApprovedOrganizations() as $approvedOrganization){
            $valid = ($approvedOrganization->getId() == $organizationId);
            if($valid) break;
        }
        if($valid) return true;
        return false;
    }

    public function mustBeOrganizationAdminMessage()
    {
        return "You must be an organization admin to continue.";
    }

    public function mustBeInstructorAdminMessage()
    {
        return "You must be an instructor admin to continue.";
    }

  


}