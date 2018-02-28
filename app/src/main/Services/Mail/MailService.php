<?php
/**
 * Created by enewbury.
 * Date: 10/25/15
 */

namespace EricNewbury\DanceVT\Services\Mail;


use EricNewbury\DanceVT\Constants\Association;
use EricNewbury\DanceVT\Models\Persistence\Newsletter;
use EricNewbury\DanceVT\Models\Persistence\Subscriber;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Models\Persistence\PermissionRequest;
use EricNewbury\DanceVT\Services\PersistenceService;
use EricNewbury\DanceVT\Util\UrlTool;
use PHPMailer\PHPMailer\Exception;

class MailService extends MailServiceConfig
{
    /** @var  \Twig_Environment $twig */
    private $twig;

    /** @var  PersistenceService $persistenceService */
    private $persistenceService;


    /**
     * MailService constructor.
     * @param \Twig_Environment $twig
     * @param PersistenceService $persistenceService
     * @param $settings
     */
    public function __construct(\Twig_Environment $twig, PersistenceService $persistenceService, $settings)
    {
        $this->twig = $twig;
        $this->persistenceService = $persistenceService;
        $this->settings = $settings;
    }


    /**
     * @param $template
     * @param array $data
     * @return string
     */
    public function generateTemplate($template, $data){
        $domain = (!empty($_SERVER['HTTPS'])) ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];
        $data['domain'] = $domain;
        return $this->twig->render("email_templates/rendered/".$template, $data);
    }

    /**
     * @param $email
     * @param $userId
     * @param $verificationCode
     * @throws Exception
     */
    public function sendVerificationEmail($email, $userId, $verificationCode){
        $domain = (!empty($_SERVER['HTTPS'])) ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];

        //load verification email template
        $data = array('userId'=>$userId, 'verificationCode'=>$verificationCode);

        $template = self::generateTemplate('account_verification.twig', $data);

        $mail = $this->configureMailer();
        $mail->addAddress($email);

        $mail->Subject = 'Verify Your USA Dance Vermont Account';
        $mail->Body    = $template;
        $mail->AltBody = 'Thank you for registering.\\nOpen '.$domain.'/account/verify/'.$userId.'?token='.$verificationCode.'  to verify your account';
        $mail->send();
    }

    /**
     * @param User $user
     * @param string $code
     * @throws \Exception
     * @throws Exception
     */
    public function sendPasswordResetEmail($user, $code){
        //load reset email template
        $templateUrl = 'password_reset.twig';
        $data = array('userId'=>$user->getId(), 'code'=>$code);
        $template = self::generateTemplate($templateUrl, $data);

        $mail = $this->configureMailer();
        $mail->addAddress($user->getEmail());

        $mail->Subject = 'Password Reset for USADanceVT';
        $mail->Body    = $template;
        $mail->AltBody = '\\nOpen http://www.dancevt.org/account/reset-password/'.$user->getId().'/'. $code. ' to reset your password.';

        $mail->send();
    }

    /**
     * @param $requestType
     * @param User $requestUser
     * @param null $organizationName
     * @throws Exception
     */
    public function sendPermissionRequestEmail($requestType, $requestUser, $organizationName = null){
        $adminUsers = $this->persistenceService->getAdmins();
        $templateUrl = 'account_request.twig';
        $data = ['requestType' => $requestType, 'user'=>$requestUser, 'organizationName'=>$organizationName];
        $template = self::generateTemplate($templateUrl, $data);
        $mail = $this->configureMailer();

        $mail->Subject = $requestType . ' Account Request';
        $mail->Body = $template;
        $mail->AltBody = "Log in to your admin account confirm or delete this " . $requestType . ' Account request';

        $mail->addAddress($this->settings['username']);
        foreach($adminUsers as $admin){
            $mail->addBCC($admin->getEmail());
        }

        $mail->send();
    }

    /**
     * @param User $user
     * @param string $code
     * @param string $message
     * @throws Exception
     */
    public function sendAccountCreatedMessage($user, $code, $message){
        //load account created email template
        $templateUrl = 'admin_created_account.twig';
        $data = array('userId'=>$user->getId(), 'firstName'=>$user->getFirstName(), 'code'=>$code, 'message'=>$message);
        $template = self::generateTemplate($templateUrl, $data);

        $mail = $this->configureMailer();
        $mail->addAddress($user->getEmail());

        $mail->Subject = 'Your account is waiting for you';
        $mail->Body    = $template;
        $mail->AltBody = '\\nOpen http://www.dancevt.org/password-reset/'.$user->getId().'/'. $code. ' to access your account.';

        $mail->send();
    }

    /**
     * @param User $user
     */
    public function passwordUpdated($user){
        $msg = 'Your password has been reset.  If this wasn\'t you, reset your password again immediately';
        $template = self::generateTemplate('message.twig', ['user'=>$user, 'message'=>$msg]);

        $mail = $this->configureMailer();
        $mail->addAddress($user->getEmail());

        $mail->Subject = 'Password Reset';
        $mail->Body    = $template;
        $mail->AltBody = $msg;

        $mail->send();
    }

    public function sendSiteAdminRequestEmail($request){
        $recipients = $this->persistenceService->getAdmins();
        $subject = 'Site Admin Permissions Request';
        $this->sendPermissionRequestToRecipients($request, $recipients, $subject);
    }

    public function sendNotificationOfOrganizationManagementRequest($user, $organization, $request){
        $recipients = $this->persistenceService->getSiteAdminsAndOrganizationAdmins($user, $organization);
        $subject = 'Organization Admin Permission Request';
        $this->sendPermissionRequestToRecipients($request, $recipients, $subject);
    }

    /**
     * @param $user
     * @param PermissionRequest $request
     * @throws Exception
     */
    public function sendNotificationOfInstructorManagementRequest($user, $request){
        $recipients = $this->persistenceService->getSiteAdminsAndInstructorAdmins($user, $request->getInstructor());
        $subject = 'Instructor Admin Permission Request';
        $this->sendPermissionRequestToRecipients($request, $recipients, $subject);
    }

    /**
     * @param User $user
     * @param PermissionRequest $request
     */
    public function sendNotificationOfOrganizationHasInstructorRequest($user, $request){
        $recipients = $this->persistenceService->getSiteAdminsAndInstructorAdmins($user, $request->getInstructor());
        $subject = 'Instructor Association Request';
        $this->sendPermissionRequestToRecipients($request, $recipients, $subject);
    }

    /**
     * @param User $user
     * @param PermissionRequest $request
     * @throws Exception
     */
    public function sendNotificationOfInstructorTeachesForOrganizationRequest($user, $request){
        $recipients = $this->persistenceService->getSiteAdminsAndOrganizationAdmins($user, $request->getOrganization());
        $subject = 'Organization Association Request';
        $this->sendPermissionRequestToRecipients($request, $recipients, $subject);
    }

    /**
     * @param User $user
     * @param PermissionRequest $request
     * @throws Exception
     */
    public function sendNotificationOfInstructorTeachesEventRequest($user, $request){
        $recipients = $this->persistenceService->getSiteAdminsAndInstructorAdmins($user, $request->getInstructor());
        $subject = 'Instructor Association Request';
        $this->sendPermissionRequestToRecipients($request, $recipients, $subject);
    }

    /**
     * @param User $user
     * @param PermissionRequest $request
     * @throws Exception
     */
    public function sendNotificationOfOrganizationHostsEventRequest($user, $request){
        $recipients = $this->persistenceService->getSiteAdminsAndOrganizationAdmins($user, $request->getOrganization());
        $subject = 'Organization Association Request';
        $this->sendPermissionRequestToRecipients($request, $recipients, $subject);
    }

    /**
     * @param $request
     * @param User[] $recipients
     * @param $subject
     * @throws Exception
     */
    private function sendPermissionRequestToRecipients($request, $recipients, $subject){
        $templateUrl = 'permission_request.twig';
        $message = Association::getMessage($request);
        $data = ['message' => $message];
        $template = self::generateTemplate($templateUrl, $data);
        $mail = $this->configureMailer();

        $mail->Subject = $subject;
        $mail->Body = $template;
        $mail->AltBody = $message;

        $mail->addAddress($this->settings['username']);
        foreach ($recipients as $recipient) {
            $mail->addBCC($recipient->getEmail());
        }

        $mail->send();
    }

    /**
     * @param PermissionRequest $request
     * @throws Exception
     */
    public function sendApprovalEmail($request){
        $recipient = $request->getUser();
        $data = ['user'=>$recipient, 'message'=>Association::getApprovalMessage($request)];

        $templateUrl = 'message.twig';
        $template = self::generateTemplate($templateUrl, $data);
        $mail = $this->configureMailer();

        $mail->Subject = "Request Approved";
        $mail->Body = $template;
        $mail->AltBody = $data['message'];

        $mail->addAddress($recipient->getEmail());
     
        $mail->send();
    }

    public function sendContactMessage($name, $email, $subject, $message){
        $mail = $this->configureMailer();
        $mail->isHTML(false);
        $message = "Message From ".$name."<".$email.">\r\n".$message;
        $mail->setFrom($email, $name);
        $mail->addReplyTo($email, $name);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->addAddress($this->settings['username']);
        $mail->send();
    }

    public function sendNewsletter($subject, $sections){
        $templateUrl = 'newsletter.twig';
        $data = ['sections' => $sections];
        $newsletter = new Newsletter();
        $this->persistenceService->saveNewsletter($newsletter);
        $data['newsletterId']=$newsletter->getId();
        $template = self::generateTemplate($templateUrl, $data);
        $newsletter->setContent($template);
        $this->persistenceService->saveNewsletter($newsletter);
        
        $mail = $this->configureMailer();

        $mail->Subject = $subject;
        $mail->Body = $template;
        $mail->AltBody = "View in Browser ". UrlTool::myDomain().'/newsletter/'.$newsletter->getId();

        $mail->addAddress($this->settings['username']);
        $subscribers = $this->persistenceService->getSubscribers();
        $subscriberPartitions = array_chunk($subscribers, 100);

        foreach($subscriberPartitions as $subscriberGroup) {
            $mail->clearBCCs();
            foreach ($subscriberGroup as $subscriber) {
                /** @var $subscriber Subscriber */
                $mail->addBCC($subscriber->getEmail(), $subscriber->getName());
            }

            $mail->send();
        }
    }

    public function notifyNewSubscriber($name, $email){
        $mail = $this->configureMailer();
        $mail->isHTML(false);
        $mail->Subject = 'New Stepping Out Subscriber';
        $mail->Body = $name .  ' at ' . $email . ' signed up for stepping out.';

        $mail->addAddress($this->settings['username']);
        $mail->send();
    }

}