<?php
/**
 * Created by enewbury.
 * Date: 1/3/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;

/** @Entity(repositoryClass="EricNewbury\DanceVT\Models\Repository\PermissionRequestRepository") */
class PermissionRequest
{

    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $request;

    /**
     * @ManyToOne(targetEntity="User")
     * @var User $user
     */
    protected $user;

    /**
     * @ManyToOne(targetEntity="Organization")
     * @var Organization $organization
     */
    protected $organization;

    /**
     * @ManyToOne(targetEntity="Instructor")
     * @var Instructor $instructor
     */
    protected $instructor;

    /**
     * @ManyToOne(targetEntity="Event")
     * @var Event $event
     */
    protected $event;

    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    protected $requestDate;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $completed;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return Instructor
     */
    public function getInstructor()
    {
        return $this->instructor;
    }

    /**
     * @param Instructor $instructor
     */
    public function setInstructor($instructor)
    {
        $this->instructor = $instructor;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param Event $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * @return \DateTime
     */
    public function getRequestDate()
    {
        return $this->requestDate;
    }

    /**
     * @param \DateTime $requestDate
     */
    public function setRequestDate($requestDate)
    {
        $this->requestDate = $requestDate;
    }

    /**
     * @return string
     */
    public function getCompleted()
    {
        return $this->completed;
    }/**
     * @param string $completed
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
    }

}