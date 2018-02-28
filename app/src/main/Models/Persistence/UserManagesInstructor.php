<?php
/**
 * Created by enewbury.
 * Date: 1/2/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

/** @Entity */
class UserManagesInstructor
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="managedInstructorAssociations")
     * @var User
     */
    protected $user;

    /**
     * @ManyToOne(targetEntity="Instructor", inversedBy="userAssociations")
     * @var Instructor
     */
    protected $instructor;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $approved;

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
     * @return bool
     */
    public function isApproved()
    {
        return ($this->approved === 1) ? true : false;
    }

    /**
     * @param int $approved
     */
    public function setApproved($approved)
    {
        $this->approved = ($approved === true) ? 1 : 0;
    }


}