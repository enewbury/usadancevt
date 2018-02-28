<?php
/**
 * Created by enewbury.
 * Date: 10/27/15
 */

namespace EricNewbury\DanceVT\Services\Mail;



use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

abstract class MailServiceConfig
{
    /** @var  array $settings */
    protected $settings;


    /**
     * @return PHPMailer
     * @throws Exception
     */
    protected function configureMailer(){
        $mail = new PHPMailer;

        $mail->isSMTP();                                        // Set mailer to use SMTP
        $mail->Host = $this->settings['host'];                  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                                 // Enable SMTP authentication
        $mail->Username = $this->settings['username'];          // SMTP username
        $mail->Password = $this->settings['password'];          // SMTP password
        $mail->SMTPSecure = 'tls';                              // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                      // TCP port to connect to

        $mail->setFrom($this->settings['username'], $this->settings['name']);
        $mail->isHTML(true);                                    // Set email format to HTML

        return $mail;
    }
}