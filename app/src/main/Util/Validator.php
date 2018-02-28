<?php
/**
 * Created by enewbury.
 * Date: 12/2/15
 */

namespace EricNewbury\DanceVT\Util;


use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\ClientValidationErrorException;
use EricNewbury\DanceVT\Models\Persistence\Event;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class Validator
{

    /**
     * @param $email
     * @throws ClientValidationErrorException
     */
    public function validateEmail($email){
        try {
            v::email()->setName('Email')->assert($email);
        }
        catch(NestedValidationException $e){
            throw new ClientValidationErrorException(null, $e->getMessages());
        }
    }

    public function validatePassword($password)
    {

            if(!v::length(8)->regex('/.*\d|[@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?].*/')->validate($password)) {
                throw new ClientValidationErrorException('Password must be at least 8 characters, and include a number or special character.');
            }

    }

    /**
     * @param $first
     * @param $last
     * @throws ClientValidationErrorException
     */
    public function validateNames($first, $last){
        try {
            v::notBlank()->setName('First Name')->assert($first);
            v::notBlank()->setName('Last Name')->assert($last);
        }
        catch(NestedValidationException $e){
            throw new ClientValidationErrorException(null, $e->getMessages());
        }
    }

    /**
     * @param Organization $organization
     * @throws ClientValidationErrorException
     */
    public function validateOrganization($organization){
        try{
            v::notEmpty()->setName('Name')->assert($organization->getName());
            v::optional(v::email())->setName('Email')->assert($organization->getEmail());
            v::optional(v::phone())->setName('Phone')->assert($organization->getPhone());
            v::optional(v::url())->setName('Website')->assert($organization->getWebsite());
            v::optional(v::url())->setName('Facebook')->assert($organization->getFacebook());
            v::optional(v::url())->setName('Twitter')->assert($organization->getTwitter());
        }
        catch(NestedValidationException $e){
            throw new ClientValidationErrorException(null, $e->getMessages());
        }
    }

    /**
     * @param Instructor $instructor
     * @throws ClientValidationErrorException
     */
    public function validateInstructor($instructor)
    {
        try{
            v::notEmpty()->setName('Name')->assert($instructor->getName());
            v::optional(v::email())->setName('Email')->assert($instructor->getEmail());
            v::optional(v::phone())->setName('Phone')->assert($instructor->getPhone());
            v::optional(v::url())->setName('Website')->assert($instructor->getWebsite());
            v::optional(v::url())->setName('Facebook')->assert($instructor->getFacebook());
            v::optional(v::url())->setName('Twitter')->assert($instructor->getTwitter());
        }
        catch(NestedValidationException $e){
            throw new ClientValidationErrorException(null, $e->getMessages());
        }
    }

    /**
     * @param $startDate
     * @param $startTime
     * @param bool $optional
     * @throws ClientValidationErrorException
     */
    public function validateDateTime($startDate, $startTime, $optional)
    {
        try{
            if($optional){
                v::optional(v::date())->setName('Date')->assert($startDate);
            }
            else{
                v::date()->setName('Date')->assert($startDate);
            }
            v::optional(v::date())->setName('Time')->assert($startTime);
        }
        catch(NestedValidationException $e){
            throw new ClientValidationErrorException(null, $e->getMessages());
        }
    }

    /**
     * @param Event $event
     * @throws ClientValidationErrorException
     */
    public function validateEvent($event)
    {
        try{
            v::notEmpty()->setName('Name')->assert($event->getName());
            v::date()->setName('Start Date')->assert($event->getStartDatetime());
            v::optional(v::date())->setName('End Date')->assert($event->getEndDatetime());
            v::optional(v::date())->setName('Repeat Until')->assert($event->getRepeatUntil());
            
            if($event->getEndDatetime() != null && $event->getStartDatetime() > $event->getEndDatetime()){
                throw new ClientValidationErrorException('End date must be after start date');
            }

            if($event->getRepeatDays() != null){
                foreach(explode(',', $event->getRepeatDays()) as $day){
                    v::intVal()->setName('Repeat Day')->assert($day);
                }
            }
        }
        catch(NestedValidationException $e){
            throw new ClientValidationErrorException(null, $e->getMessages());
        }
    }

    public function validateContactForm($name, $email, $subject, $message)
    {
        try{
            v::notEmpty()->setName('Name')->assert($name);
            v::email()->setName('Email')->assert($email);
            v::notEmpty()->setName('Subject')->assert($subject);
            v::notEmpty()->setName('Message')->assert($message);
        }
        catch(NestedValidationException $e){
            throw new ClientValidationErrorException(null, $e->getMessages());
        }
    }

    public static function validateImage($fileKey){
        try {
            v::size(null,'5MB')->image()->setName('File')->assert($_FILES[$fileKey]['tmp_name']);
        }
        catch(NestedValidationException $e){
            throw new ClientValidationErrorException($e->getMessages()[0]);
        }
    }



}