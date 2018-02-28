<?php
/**
 * Created by enewbury.
 * Date: 2/24/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

/** @Entity */
class OrganizationHostsEvent
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Organization", inversedBy="eventAssociations")
     * @var Organization $organization
     */
    protected $organization;

    /**
     * @ManyToOne(targetEntity="Event", inversedBy="organizationAssociations")
     * @var Event event
     */
    protected $event;

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