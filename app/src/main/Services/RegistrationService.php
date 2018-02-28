<?php
/**
 * Created by enewbury.
 * Date: 10/25/15
 */

namespace EricNewbury\DanceVT\Services;


use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\ClientValidationErrorException;
use EricNewbury\DanceVT\Models\Exceptions\InternalErrorException;
use EricNewbury\DanceVT\Models\Exceptions\UserExistsException;
use EricNewbury\DanceVT\Models\Persistence\Token;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Util\Validator;
use Monolog\Logger;
use Exception;
use Respect\Validation\Exceptions\NestedValidationException;

class RegistrationService
{
    /** @var  Logger logger */
    private $logger;
    
    /** @var  PersistenceService persistenceService */
    private $persistenceService;
    
    /** @var Validator validator */
    private $validator;

    /** @var MailService mailService */
    private $mailService;

    /**
     * RegistrationService constructor.
     * @param Logger $logger
     * @param PersistenceService $persistenceService
     * @param Validator $validator
     * @param MailService $mailService
     */
    public function __construct(Logger $logger, PersistenceService $persistenceService, Validator $validator, MailService $mailService)
    {
        $this->logger = $logger;
        $this->persistenceService = $persistenceService;
        $this->validator = $validator;
        $this->mailService = $mailService;
    }

    /**
     * Creates and persists a new account with email and password.
     * @param string $first
     * @param string $last
     * @param string $email unvalidated email entered by the user.
     * @param string $password unescaped password entered by the user
     * @param string $confirm unescaped password confirmation entered by the user
     * @return BaseResponse returns a response object for an ajax request
     */
    public function createAccount($first, $last, $email, $password, $confirm, $resendLink){

        try {
            //do validation
            $this->validator->validateEmail($email);
            $this->validator->validateNames($first, $last);
            $this->validator->validatePassword($password);

            //check if account already exists
            $user = $this->persistenceService->getUserByEmail($email);

            if ($user != null) {
                throw new UserExistsException('User already exists.');
            }

            //check if password mismatch
            if ($password !== $confirm) {
                throw new ClientErrorException('Passwords mismatched');
            }

            //hash and salt password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash == false) {
                $this->logger->error("couldn't hash password");
                throw new InternalErrorException("Couldn't create your account", "Password Hash Failed");
            }

            //persist
            $user = $this->persistenceService->createAccount($first, $last, $email, $passwordHash);

            //create verification token
            $verificationToken = $this->generateVerificationToken($user);

            //send email with verification
            try {
                $this->mailService->sendVerificationEmail($email, $user->getId(), $verificationToken);
            } catch (Exception $e) {
                $this->logger->error("Couldn't send verification email", array('exception' => $e));
                throw new InternalErrorException("Couldn't send verification email. Try again later", "email failed");
            }

            //all is well, return success with no data
            return new SuccessResponse();
        }
        catch(UserExistsException $e){
            $res = BaseResponse::generateClientErrorResponse($e);
            $dataArray = $res->getData();
            $dataArray['email'] = $email;
            $dataArray['first'] = $first;
            $dataArray['last'] = $last;
            $dataArray['linkText'] = 'Resend Verification Email';
            $dataArray['linkHref'] = $resendLink.'?email='.$email;
            $res->setData($dataArray);
            return $res;
        }
        catch(ClientErrorException $e){
            $res = BaseResponse::generateClientErrorResponse($e);
            $dataArray = $res->getData();
            $dataArray['email'] = $email;
            $dataArray['first'] = $first;
            $dataArray['last'] = $last;
            $res->setData($dataArray);
            return $res;
        }
        catch(InternalErrorException $e){
            $res =  BaseResponse::generateInternalErrorResponse($e);
            $dataArray = $res->getData();
            $dataArray['email'] = $email;
            $dataArray['first'] = $first;
            $dataArray['last'] = $last;
            $res->setData($dataArray);
            return $res;
        }

    }

    /**
     * Creates a verification for an account, persists it, and sends an email to user
     * @param User $user user for account needing verification
     * @return string
     * @internal param string $email
     */
    private function generateVerificationToken($user){
        //8 hours from now.
        $expireDate = new \DateTime();
        $expireDate = $expireDate->add(new \DateInterval("PT8H"));

        //create new verification
        $token = md5(uniqid());

        $this->persistenceService->createVerificationToken($user, $token, $expireDate);

        return $token;
    }



    /**
     * Attempts to validate account against given token on success sets session to verified.
     * @param int $userId
     * @param string $tokenVal
     * @return BaseResponse verified successfully or not.
     */
    public function verifyAccount($userId, $tokenVal){
        try {
            //lookup the token
            $verificationToken = $this->persistenceService->getValidToken($userId, $tokenVal, Token::VERIFICATION);


            if($verificationToken == null){
                throw new ClientErrorException('There was an error verifying your account.  Your token may have expired.  If you attempt to log in, you will be asked if you want to generate a new verification email');
            }

            //lookup user
            $user = $this->persistenceService->getUser($userId);

            //set to active
            $user->setActive(true);

            //expire token
            $now = new \DateTime();
            $verificationToken->setExpireDate($now);

            $this->persistenceService->persistChanges();

            //generate response
            $response = new BaseResponse();
            $response->setStatus(BaseResponse::SUCCESS);
            return $response;

        }
        catch(ClientErrorException $e){
            return BaseResponse::generateClientErrorResponse($e);
        }
    }

    public function resendVerification($email)
    {
        try {
            //get user
            $user = $this->persistenceService->getUserByEmail($email);
            if($user == null){
                throw new ClientErrorException("User not found");
            }

            //invalidate old tokens
            $this->invalidateUserTokens($user);

            //create verification token
            $verificationToken = $this->generateVerificationToken($user);

            //send email with verification
            try {
                $this->mailService->sendVerificationEmail($email, $user->getId(), $verificationToken);
            } catch (Exception $e) {
                $this->logger->error("Couldn't send verification email");
                throw new InternalErrorException("Couldn't send verification email. Try again later", "email failed");
            }

            //all is well, return success with no data
            return new SuccessResponse();
        }
        catch(ClientErrorException $e){
            return BaseResponse::generateClientErrorResponse($e);
        }
    }

    private function invalidateUserTokens($user)
    {
        $tokens = $this->persistenceService->getValidTokensForUser($user, Token::VERIFICATION);
        foreach ($tokens as $token){
            /* @var $token Token */
            $token->setExpireDate(new \DateTime());
        }

        $this->persistenceService->persistChanges();
    }

    public function createNewSubscriber($name, $email)
    {
        //do validation
        try {
            $this->validator->validateEmail($email);
            $this->persistenceService->createNewSubscriber($name, $email);
            $this->mailService->notifyNewSubscriber($name, $email);
            return new SuccessResponse();
        }
        catch(ClientValidationErrorException $e){
            return new FailResponse($e->getMessages()[0]);
        }
    }

    public function unsubscribe($email)
    {
        $this->persistenceService->unsubscribe($email);
    }


}