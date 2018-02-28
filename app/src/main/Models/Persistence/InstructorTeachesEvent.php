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
class InstructorTeachesEvent
{

    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Instructor", inversedBy="eventAssociations")
     * @var Instructor instructor
     */
    protected $instructor;

    /**
     * @ManyToOne(targetEntity="Event", inversedBy="instructorAssociations")
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