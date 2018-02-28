<?php
/**
 * Created by enewbury.
 * Date: 12/7/15
 */

namespace EricNewbury\DanceVT\Models\Persistence;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
class LoginAttempt
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
    protected $email;

    /**
     * @Column(type="datetime")
     * @var DateTime email
     */
    protected $date;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $ipAddress;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $userAgent;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $outcome;

    /**
     * LoginAttempt constructor.
     * @param string $ipAddress
     * @param DateTime $date
     */
    public function __construct($ipAddress = null, DateTime $date = null)
    {
        $this->ipAddress = $ipAddress;
        $this->date = $date;
    }


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
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @return string
     */
    public function getOutcome()
    {
        return $this->outcome;
    }

    /**
     * @param string $outcome
     */
    public function setOutcome($outcome)
    {
        $this->outcome = $outcome;
    }

}