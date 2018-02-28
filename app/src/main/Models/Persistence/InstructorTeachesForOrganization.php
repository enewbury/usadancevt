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
class InstructorTeachesForOrganization
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Instructor", inversedBy="organizationAssociations")
     * @var Instructor instructor
     */
    protected $instructor;

    /**
     * @ManyToOne(targetEntity="Organization", inversedBy="instructorAssociations")
     * @var Organization organization
     */
    protected $organization;

    /**
     * @Column(type="integer")
     * @var int approved
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
     * @return bool
     */
    public function isApproved()
    {
        return ($this->approved === 1) ? true : false;
    }

    /**
     * @param bool $approved
     */
    public function setApproved($approved)
    {
        $this->approved = ($approved === true) ? 1 : 0;
    }


}