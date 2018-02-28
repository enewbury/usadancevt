<?php
/**
 * Created by enewbury.
 * Date: 1/5/16
 */

namespace EricNewbury\DanceVT\Constants;

use EricNewbury\DanceVT\Models\Persistence\PermissionRequest;
use EricNewbury\DanceVT\Models\Persistence\User;

class Association
{
    const ADMIN = "ADMIN";
    const USER_MANAGES_ORGANIZATION = "USER_MANAGES_ORGANIZATION";
    const USER_MANAGES_INSTRUCTOR = "USER_MANAGES_INSTRUCTOR";
    const INSTRUCTOR_TEACHES_FOR_ORGANIZATION = "INSTRUCTOR_TEACHES_FOR_ORGANIZATION";
    const ORGANIZATION_HAS_INSTRUCTOR = "ORGANIZATION_HAS_INSTRUCTOR";
    const INSTRUCTOR_TEACHES_EVENT = "INSTRUCTOR_TEACHES_EVENT";
    const ORGANIZATION_HOSTS_EVENT = "ORGANIZATION_HOSTS_EVENT";


    /**
     * @param PermissionRequest $request
     * @return string
     */
    public static function getMessage($request){
        /** @var User $user */
        $user = $request->getUser();
        switch($request->getRequest()){
            case self::ADMIN:
                return "User \"" . $user->getFirstName() . " " . $user->getLastName() . "\" has requested Admin Permissions";
            case self::USER_MANAGES_ORGANIZATION:
                return "User \"" . $user->getFirstName() . " " . $user->getLastName() . "\" has requested to manage organization \"" . $request->getOrganization()->getName() . "\"";
            case self::USER_MANAGES_INSTRUCTOR:
                return "User \"" . $user->getFirstName() . " " . $user->getLastName() . "\" has requested to manage instructor \"" . $request->getInstructor()->getName() . "\"";
            case self::INSTRUCTOR_TEACHES_FOR_ORGANIZATION:
            case self::ORGANIZATION_HAS_INSTRUCTOR:
                return "User \"" . $user->getFirstName() . " " . $user->getLastName() . "\" has requested to make instructor \"" . $request->getInstructor()->getName() . "\" a teacher for organization \"" . $request->getOrganization()->getName() . "\"";
            case self::INSTRUCTOR_TEACHES_EVENT:
                $repeating = ($request->getEvent()->isRepeating()) ? ' from '.$request->getEvent()->getStartDatetime()->format('m/d/y') : '';
                return "User \"". $user->getFirstName() . " " . $user->getLastName() . "\" has requested to make " . $request->getInstructor()->getName() . " an instructor of event \"" . $request->getEvent()->getName() . "\"".$repeating;
            case self::ORGANIZATION_HOSTS_EVENT:
                $repeating = ($request->getEvent()->isRepeating()) ? ' from '.$request->getEvent()->getStartDatetime()->format('m/d/y') : '';
                return "User \"". $user->getFirstName() . " " . $user->getLastName() . "\" has requested to make " . $request->getOrganization()->getName() . " a host for event \"" . $request->getEvent()->getName() . "\"".$repeating;

        }
    }

    /**
     * @param PermissionRequest $request
     * @return string
     */
    public static function getApprovalMessage($request)
    {
        switch ($request->getRequest()) {
            case self::ADMIN:
                return "Your site admin account has been approved.";
            case self::USER_MANAGES_ORGANIZATION:
                return "You have been approved to manage organization \"" . $request->getOrganization()->getName() . "\"";
            case self::USER_MANAGES_INSTRUCTOR:
                return "You have been approved to manage instructor \"" . $request->getInstructor()->getName() . "\"";
            case self::INSTRUCTOR_TEACHES_FOR_ORGANIZATION:
            case self::ORGANIZATION_HAS_INSTRUCTOR:
                return "\"" . $request->getInstructor()->getName() . "\" has been approved as a teacher for organization \"" . $request->getOrganization()->getName() . "\"";
            case self::INSTRUCTOR_TEACHES_EVENT:
                $repeating = ($request->getEvent()->isRepeating()) ? ' from ' . $request->getEvent()->getStartDatetime()->format('m/d/y') : '';
                return "\"" . $request->getInstructor()->getName() . "\" has been approved to be an instructor of event \"" . $request->getEvent()->getName() . "\"" . $repeating;
            case self::ORGANIZATION_HOSTS_EVENT:
                $repeating = ($request->getEvent()->isRepeating()) ? ' from ' . $request->getEvent()->getStartDatetime()->format('m/d/y') : '';
                return "\"" . $request->getOrganization()->getName() . "\" has been approved as a host for event \"" . $request->getEvent()->getName() . "\"" . $repeating;

        }
    }
}