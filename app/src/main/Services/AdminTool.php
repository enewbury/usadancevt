<?php
/**
 * Created by Eric Newbury.
 * Date: 4/22/16
 */

namespace EricNewbury\DanceVT\Services;


use EricNewbury\DanceVT\Constants\PermissionAction;
use EricNewbury\DanceVT\Constants\PermissionStatus;
use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\UserExistsException;
use EricNewbury\DanceVT\Models\Persistence\PermissionRequest;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Util\Validator;
use Monolog\Logger;

class AdminTool
{
    /** @var  PersistenceService $persistenceService */
    private $persistenceService;

    /** @var  MailService */
    private $mailService;

    /** @var  Validator $validator */
    private $validator;

    /** @var  Logger $logger */
    private $logger;

    /**
     * AdminTool constructor.
     * @param PersistenceService $persistenceService
     * @param MailService $mailService
     * @param Validator $validator
     * @param Logger $logger
     */
    public function __construct(PersistenceService $persistenceService, MailService $mailService, Validator $validator, Logger $logger)
    {
        $this->persistenceService = $persistenceService;
        $this->mailService = $mailService;
        $this->validator = $validator;
        $this->logger = $logger;
    }


    /**
     * @param User $requestingUser
     * @param string $permissionAction
     * @param PermissionRequest $request
     * @return BaseResponse
     */
    public function respondToAdminRequest($requestingUser, $permissionAction, $request)
    {
        //check approved by an admin
        if (!$requestingUser || !$requestingUser->isApprovedSiteAdmin()) {
            return new FailResponse('invalid privilege to respond to this request');
        }

        //get user in question
        $requestedUser = $request->getUser();

        //change site admin permission & complete permissionRequest
        if($permissionAction === PermissionAction::APPROVE){
            $requestedUser->setSiteAdminPermission(PermissionStatus::APPROVED);
            $request->setCompleted(PermissionStatus::APPROVED);
            $res = new SuccessResponse('Approved');
        }
        else{
            $requestedUser->setSiteAdminPermission(PermissionStatus::OFF);
            $request->setCompleted(PermissionStatus::DENIED);
            $res = new SuccessResponse('Denied');
        }

        $this->persistenceService->persistChanges();
        return $res;
    }

    public function createAccountViaAdmin($first, $last, $email, $message){

        //do validation
        $this->validator->validateEmail($email);
        $this->validator->validateNames($first, $last);

        //check if account already exists
        $user = $this->persistenceService->getUserByEmail($email);
        try {
            if ($user != null) {
                throw new UserExistsException('User already exists.');
            }

            //generate password
            $password = password_hash(uniqid(), PASSWORD_DEFAULT);

            //persist
            $user = $this->persistenceService->createAccount($first, $last, $email, $password, true);

            //generate password token
            $code = password_hash(uniqid(), PASSWORD_DEFAULT);
            $this->persistenceService->savePasswordCode($user, $code, true);

            //send email
            $this->mailService->sendAccountCreatedMessage($user, urlencode($code), $message);
        } catch(ClientErrorException $e){
            $res = BaseResponse::generateClientErrorResponse($e);
            return $res;
        } catch (\phpmailerException $e) {
            $this->logger->error("Couldn't send password reset email for admin-created account");
            return new FailResponse("Couldn't send password reset email. Try again later");

        }

        return new SuccessResponse('Account created.  Notification email sent.');

    }
}