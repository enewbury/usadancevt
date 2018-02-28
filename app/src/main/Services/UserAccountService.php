<?php
/**
 * Created by enewbury.
 * Date: 10/30/15
 */

namespace EricNewbury\DanceVT\Services;


use DateTime;
use EricNewbury\DanceVT\Constants\Association;
use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\FastLoginException;
use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;
use EricNewbury\DanceVT\Models\Exceptions\IpBlockedForeverException;
use EricNewbury\DanceVT\Models\Exceptions\IpBlockedTemporarilyException;
use EricNewbury\DanceVT\Models\Exceptions\TooManyLoginAttempsException;
use EricNewbury\DanceVT\Models\Exceptions\UserExistsException;
use EricNewbury\DanceVT\Models\Persistence\BlockedIp;
use EricNewbury\DanceVT\Models\Persistence\LoginAttempt;
use EricNewbury\DanceVT\Models\Persistence\Token;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Util\DateTool;
use EricNewbury\DanceVT\Util\Validator;
use Monolog\Logger;
use Respect\Validation\Rules\Date;


class UserAccountService
{
    /** @var  Logger logger */
    private $logger;

    /** @var  PersistenceService persistenceService */
    private $persistenceService;

    /** @var Validator validator */
    private $validator;

    /** @var MailService mailService */
    private $mailService;

    /** @var  AdminTool $adminService */
    private $adminService;

    /** @var  AssociationsTool $associationsTool */
    private $associationsTool;

    /** @var  InstructorTool $instructorTool */
    private $instructorTool;

    /** @var  OrganizationTool $organizationTool */
    private $organizationTool;

    /**
     * UserAccountService constructor.
     * @param Logger $logger
     * @param PersistenceService $persistenceService
     * @param Validator $validator
     * @param MailService $mailService
     * @param AdminTool|AssociationsTool $adminService
     * @param AssociationsTool $associationsTool
     * @param InstructorTool $instructorTool
     * @param OrganizationTool $organizationTool
     */
    public function __construct(Logger $logger, PersistenceService $persistenceService, Validator $validator, MailService $mailService, AdminTool $adminService, AssociationsTool $associationsTool, InstructorTool $instructorTool, OrganizationTool $organizationTool)
    {
        $this->logger = $logger;
        $this->persistenceService = $persistenceService;
        $this->validator = $validator;
        $this->mailService = $mailService;
        $this->adminService = $adminService;
        $this->associationsTool = $associationsTool;
        $this->instructorTool = $instructorTool;
        $this->organizationTool = $organizationTool;
    }



    // -----------------------------------------------------------------------
    // AUTHENTICATION
    //-----------------------------------------------------------------------

    /**
     * @param string $email
     * @param string $password
     * @param $ip
     * @param string $userAgent
     * @param string $resendLink
     * @return BaseResponse
     */
    public function login($email, $password, $ip, $userAgent, $resendLink){

        $loginAttempt = new LoginAttempt();
        $loginAttempt->setEmail($email);
        $loginAttempt->setDate(new DateTime());
        $loginAttempt->setIpAddress($ip);
        $loginAttempt->setUserAgent($userAgent);
        try {

            //check bruteforce attacks
            $this->checkBruteForceAttack($email, $ip);
            
            //validate email is an email
            $this->validator->validateEmail($email);

            //lookup user from db
            $user = $this->persistenceService->getUserByEmail($email);

            if ($user === null || !password_verify($password, $user->getPasswordHash())) {
                throw new ClientErrorException('Invalid credentials');
            } elseif (!$user->isActive()) {
                throw new UserExistsException("Account inactive");
            }

            //good to go.  reset session token to prevent session fixation
            session_regenerate_id();
            //login
            $_SESSION['userId'] = $user->getId();
            $_SESSION['email'] = $user->getEmail();

            $loginAttempt->setOutcome("SUCCESS");
            $this->persistenceService->createLoginAttempt($loginAttempt);

            return new SuccessResponse();
        }
        catch(UserExistsException $e){
            $res = BaseResponse::generateClientErrorResponse($e);
            $dataArray = $res->getData();
            $dataArray['email'] = $email;
            $dataArray['linkText'] = 'Resend Verification Email';
            $dataArray['linkHref'] = $resendLink.'?email='.$email;
            $res->setData($dataArray);
            return $res;
        }
        catch(ClientErrorException $e){
            $loginAttempt->setOutcome("FAIL");
            $this->persistenceService->createLoginAttempt($loginAttempt);
            $res = new BaseResponse();
            $res->setStatus(BaseResponse::FAIL)->setData([
                'message'=>$e->getMessage(),
                'messages'=>$e->getMessages(),
                'email'=>$email
            ]);
            return $res;
        }

    }
    
    protected function checkBruteForceAttack($email, $ip){
        //get recent login attempts
        $attempts = $this->persistenceService->getLoginFailedAttempts($email, $ip, new DateTime('-5 minutes'));
        $blocked = $this->persistenceService->getBlockedIp($ip);
        
        //too fast
        if (!empty($attempts) && $attempts[0]->getDate() > new DateTime('-2 second')){
            throw new FastLoginException('You tried to log in less than a second ago!  You sneaky bot you.');
        }
        //good to go
        else if(count($attempts) < 4 && ($blocked == null || ($blocked->getBlockedUntil() != null && $blocked->getBlockedUntil() <  new DateTime()))) {
            $this->persistenceService->deleteBlockedIp($ip);
            return;
        }
        //blocked indefinitely
        elseif($blocked != null && $blocked->getBlockedUntil() == null) throw new IpBlockedForeverException('You have been very persistent about trying to log in, so we blocked you...FOREVER!!');
        //blocked right now
        elseif($blocked != null && $blocked->getBlockedUntil() != null && $blocked->getBlockedUntil() > new DateTime()) throw new IpBlockedTemporarilyException('You tried to log in a lot in the last couple minutes to you are blocked for the next '. DateTool::getDateDiffReadable($blocked->getBlockedUntil(), new DateTime()));

        //too many in last 5 minutes
        elseif(count($attempts) >= 4){
            if($blocked == null){
                $blocked = new BlockedIp();
                $blocked->setIp($ip);
                $blocked->setOffenseCount(0);
            }
            //increment offense count and use it to calculate the exponent of 5 minutes delay
            $blocked->setOffenseCount($blocked->getOffenseCount() + 1);
            $blockedMinutes = pow(5, $blocked->getOffenseCount());
            $blocked->setBlockedUntil(new DateTime('+ '.$blockedMinutes. ' minutes'));

            //blocks forever after 12 login attempts over 2.08 hours
            if ($blocked->getOffenseCount() > 3)$blocked->setBlockedUntil(null);
            $this->persistenceService->saveBlockedIp($blocked);
            $message = ($blocked->getBlockedUntil() == null) ? '... FOREVER!' : 'for the next '.DateTool::getDateDiffReadable($blocked->getBlockedUntil(), new DateTime());
            throw new TooManyLoginAttempsException('You\'ve had 4 failed login attempts in the last 5 minutes. You seem to be a bot so we blocked you '.$message);
        }
        else{
            $this->logger->error('did something wrong with login attempts logic');
            throw new InternalErrorException('Did something wrong with login around login attempts');
        }
    }

    public function logout(){
        //overwrite variables
        $_SESSION = array();

        //unset cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        //clear all data
        session_destroy();
    }


    // -----------------------------------------------------------------------
    // PASSWORD RESET
    //-----------------------------------------------------------------------

    public function sendForgotPasswordToken($email){
        try {
            //get user
            $user = $this->persistenceService->getUserByEmail($email);
            if ($user === null) {
                throw new ClientErrorException('No account is associated with this email');
            }

            //generate password token
            $code = password_hash(uniqid(), PASSWORD_DEFAULT);
            $this->persistenceService->savePasswordCode($user, $code);

            //send email
            try {
                $this->mailService->sendPasswordResetEmail($user, urlencode($code));
            } catch (\phpmailerException $e) {
                $this->logger->error("Couldn't send password reset email");
                throw new InternalErrorException("emailFailure", "Couldn't send password reset email. Try again later", "email failed");
            }

            $res = new BaseResponse();
            $res->setStatus(BaseResponse::SUCCESS);
            return $res;
        }
        catch(ClientErrorException $e){
            return BaseResponse::generateClientErrorResponse($e);
        }
        catch(InternalErrorException $e){
            return BaseResponse::generateInternalErrorResponse($e);
        }
    }

    public function verifyPasswordToken($userId, $token){

        try {
            $user = $this->persistenceService->getUser($userId);
            if ($user === null) {
                throw new ClientErrorException("Invalid password reset token");
            }

            $token = $this->persistenceService->getValidToken($userId, $token, Token::PASS_RESET);
            if($token ===null) {
                throw new ClientErrorException("Invalid password reset token");
            }

            $res = new BaseResponse();
            $res->setStatus(BaseResponse::SUCCESS);
            return $res;
        }
        catch(ClientErrorException $e){
            return BaseResponse::generateClientErrorResponse($e);
        }
    }

    public function verifyPasswordTokenAndChangePassword($userId, $token, $password, $confirm){
        $response = $this->verifyPasswordToken($userId, $token);
        try {
            if ($response->getStatus() !== BaseResponse::SUCCESS) {
                //invalid token
                throw new ClientErrorException('Invalid password reset token.');
            }

            $this->validator->validatePassword($password);
            
            $user = $this->persistenceService->getUser($userId);
            if ($password !== $confirm) {
                //password mismatch
                throw new ClientErrorException('Passwords mismatched');
            }

            $user->setPasswordHash(password_hash($password, PASSWORD_DEFAULT));

            $tokens = $this->persistenceService->getValidTokensForUser($user, Token::PASS_RESET);
            $this->invalidateTokens($tokens);

            $this->persistenceService->persistChanges();

            $response = new BaseResponse();
            $response->setStatus(BaseResponse::SUCCESS);
            return $response;

        }
        catch(ClientErrorException $e){
            return BaseResponse::generateClientErrorResponse($e);
        }
    }

    /**
     * @param Token[] $tokens
     */
    private function invalidateTokens($tokens){
        foreach($tokens as $token){
            $token->setExpireDate(new \DateTime());
        }
    }


    // -----------------------------------------------------------------------
    // ACCOUNT UPDATES
    //-----------------------------------------------------------------------
    public function deleteUser($id)
    {
        $this->persistenceService->deleteUser($id);
        return new SuccessResponse('Account deleted.');
    }

    public function deactivateAccount($accountId){
        if($accountId != null) {
            $this->logout();
            $this->persistenceService->deactivateAccount($accountId);
        }
    }

    /**
     * @param string $first
     * @param string $last
     * @param User $user
     * @return BaseResponse
     */
    public function updateName($first, $last, $user){
        try {
            $this->validator->validateNames($first, $last);

            $user->setFirstName($first);
            $user->setLastName($last);
            $this->persistenceService->persistChanges();

            $response = new BaseResponse();
            $response->setStatus(BaseResponse::SUCCESS);
            $response->setData(['message'=>'Updated Your Name']);
            return $response;
        }
        catch(ClientErrorException $e){
            return BaseResponse::generateClientErrorResponse($e);
        }
    }

    /**
     * @param string $email
     * @param User $user
     * @return BaseResponse
     */
    public function updateEmail($email, $user){
        try {
            $this->validator->validateEmail($email);

            $user->setEmail($email);
            $this->persistenceService->persistChanges();

            $response = new BaseResponse();
            $response->setStatus(BaseResponse::SUCCESS);
            $response->setData(['message'=>'Updated Your Email']);
            return $response;
        }
        catch(ClientErrorException $e){
            return BaseResponse::generateClientErrorResponse($e);
        }
    }

    /**
     * @param string $old
     * @param string $new
     * @param string $confirm
     * @param User $user
     * @return BaseResponse
     * @throws InternalErrorException
     */
    public function updatePassword($old, $new, $confirm, $user){
        try {
            if ($user === null || !password_verify($old, $user->getPasswordHash())) {
                throw new ClientErrorException('Invalid credentials');
            }

            //check if password mismatch
            if ($new !== $confirm) {
                throw new ClientErrorException('Passwords mismatched');
            }

            //hash and salt password
            $passwordHash = password_hash($new, PASSWORD_DEFAULT);
            if ($passwordHash == false) {
                $this->logger->error("couldn't hash password");
                throw new InternalErrorException("hashFailure","Couldn't change your password", "password hash failed");
            }

            $user->setPasswordHash($passwordHash);
            $this->persistenceService->persistChanges();

            $this->mailService->passwordUpdated($user);

            return new SuccessResponse('Password updated.');
        }
        catch(ClientErrorException $e){
            return BaseResponse::generateClientErrorResponse($e);
        }
    }

    public function updateAccountActivation($id, $active){
        $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
        $res = new BaseResponse();
        if($active == true){
            $this->persistenceService->activateUser($id);
            $res->setData(['message'=>'User Activated']);
        }
        else{
            $this->persistenceService->deactivateAccount($id);
            $res->setData(['message'=>'User Deactivated']);
        }
        $res->setStatus(BaseResponse::SUCCESS);
        return $res;

    }



    // -----------------------------------------------------------------------
    // CREATE REQUESTS
    //-----------------------------------------------------------------------
    /**
     * @param User$user
     */
    public function requestSiteAdminPermission($user)
    {
        //add admin account inactive
        $this->persistenceService->requestSiteAdminPermission($user);
        $request = $this->persistenceService->createPermissionRequest(Association::ADMIN, $user);

        //send notification email
        $this->mailService->sendSiteAdminRequestEmail($request);

    }

    public function requestUserManagesOrganizationPermission($user, $organizationId, $newOrganizationName){
        $res = new BaseResponse();

        if($organizationId == -1){
            $res->setStatus(BaseResponse::FAIL);
            $res->setData(['message'=>'You must select an organization or request a new one.']);
        }
        else {
            //add organization association
            $organization = null;
            try {
                if ($organizationId == "new") {
                    $existing = $this->persistenceService->getOrganizationByName($newOrganizationName);
                    if ($existing != null) {
                        throw new ClientErrorException('Organization already requested');
                    }
                    $organization = $this->persistenceService->createInactiveOrganizationByName($newOrganizationName);
                    $this->persistenceService->createPendingUserOrganizationAssociation($user, $organization);
                } else {
                    $organization = $this->persistenceService->getOrganization($organizationId);
                    $existing = $this->persistenceService->getUserOrganizationAssociation($user, $organization);
                    if ($existing != null) {
                        throw new ClientErrorException('Organization already requested');
                    }
                    $this->persistenceService->createPendingUserOrganizationAssociation($user, $organization);
                }

                //save request
                $request = $this->persistenceService->createPermissionRequest(Association::USER_MANAGES_ORGANIZATION, $user, $organization);

                //send notification email
                $this->mailService->sendNotificationOfOrganizationManagementRequest($user, $organization, $request);

                $res->setStatus(BaseResponse::SUCCESS);
                $res->setData(['message' => 'Request Sent']);
            }
            catch(ClientErrorException $e){
                $res = BaseResponse::generateClientErrorResponse($e);
            }
        }

        return $res;
    }


    public function requestUserManagesInstructorPermission($user, $instructorId, $newInstructorName)
    {
        $res = new BaseResponse();

        if($instructorId == -1){
            $res->setStatus(BaseResponse::FAIL);
            $res->setData(['message'=>'You must select an instructor or request a new one.']);
        }
        else {
            //add instructor association
            $instructor = null;
            try {
                if ($instructorId == "new") {
                    $existing = $this->persistenceService->getInstructorByName($newInstructorName);
                    if ($existing != null) {
                        throw new ClientErrorException('Instructor of that name already requested.');
                    }
                    $instructor = $this->persistenceService->createInactiveInstructorByName($newInstructorName);
                    $this->persistenceService->createPendingUserInstructorAssociation($user, $instructor);
                } else {
                    $instructor = $this->persistenceService->getInstructor($instructorId);
                    $existing = $this->persistenceService->getUserInstructorAssociation($user, $instructor);
                    if ($existing != null) {
                        throw new ClientErrorException('Instructor already requested');
                    }
                    $this->persistenceService->createPendingUserInstructorAssociation($user, $instructor);
                }

                //save request
                $request = $this->persistenceService->createPermissionRequest(Association::USER_MANAGES_INSTRUCTOR, $user, null, $instructor);

                //send notification email
                $this->mailService->sendNotificationOfInstructorManagementRequest($user, $request);

                $res->setStatus(BaseResponse::SUCCESS);
                $res->setData(['message' => 'Request Sent']);
            }
            catch(ClientErrorException $e){
                $res = BaseResponse::generateClientErrorResponse($e);
            }
        }

        return $res;
    }


    // -----------------------------------------------------------------------
    // RESPOND TO REQUESTS
    //-----------------------------------------------------------------------
    /**
     * @param User $user
     * @return array
     */
    public function getRequestsForUser($user)
    {
        $requests = array();
        $permissionRequests=array();
        if($user->isApprovedSiteAdmin()){
            $permissionRequests = $this->persistenceService->getAllPendingAndRecentRequests();
        }
        else if($user->isActiveInstructorAdmin() || $user->isActiveOrganizationAdmin()){
            $permissionRequests = $this->persistenceService->getPermissionRequestsForInstructorOrOrganizationAdmin($user);
        }

        foreach($permissionRequests as $permissionRequest){
            $request['id']=$permissionRequest->getId();
            $request['completed']=$permissionRequest->getCompleted();
            $request['date']=$permissionRequest->getRequestDate();
            $request['message']=Association::getMessage($permissionRequest);
            $requests[]=$request;
        }

        return $requests;
    }

    public function respondToRequest($user, $permissionAction, $requestId)
    {
        $request = $this->persistenceService->getRequest($requestId);

        if($request->getCompleted() != NULL){
            $res = new SuccessResponse();
            $res->setData(
                ['message'=>'This request has already been responded to',
                'action'=>$request->getCompleted()
                ]);
            return $res;
        }

        $res = new SuccessResponse();
        switch($request->getRequest()){
            case Association::ADMIN:
                $res = $this->adminService->respondToAdminRequest($user, $permissionAction, $request);
                break;
            case Association::USER_MANAGES_INSTRUCTOR:
                $res = $this->instructorTool->respondToUserInstructorRequest($user, $permissionAction, $request);
                break;
            case Association::USER_MANAGES_ORGANIZATION:
                $res = $this->organizationTool->respondToUserOrganizationRequest($user, $permissionAction, $request);
                break;
            case Association::INSTRUCTOR_TEACHES_FOR_ORGANIZATION:
                $res = $this->organizationTool->respondToTeachingForOrganizationRequest($user, $permissionAction, $request);
                break;
            case Association::ORGANIZATION_HAS_INSTRUCTOR:
                $res = $this->instructorTool->respondToInstructorAssociationRequest($user, $permissionAction, $request);
                break;
            case Association::INSTRUCTOR_TEACHES_EVENT:
                $res = $this->instructorTool->respondToTeachingEventRequest($user, $permissionAction, $request);
                break;
            case Association::ORGANIZATION_HOSTS_EVENT:
                $res = $this->organizationTool->respondToHostingEventRequest($user, $permissionAction, $request);
                break;

        }

        if($res->isSuccessful() && $res->getData()['message'] === "Approved"){
            $this->mailService->sendApprovalEmail($request);
        }

        return $res;
    }


}