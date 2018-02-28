<?php
/**
 * Created by enewbury.
 * Date: 2/3/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

/** @Entity */
class Event implements Profile, \JsonSerializable
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @OneToMany(targetEntity="InstructorTeachesEvent", mappedBy="event")
     * @var InstructorTeachesEvent[] $instructorAssociations
     */
    protected $instructorAssociations;

    /**
     * @OneToMany(targetEntity="OrganizationHostsEvent", mappedBy="event")
     * @var OrganizationHostsEvent[] $organizationAssociations
     */
    protected $organizationAssociations;

    /**
     * @OneToMany(targetEntity="EventException", mappedBy="event")
     * @var EventException[] $exceptions
     */
    protected $exceptions;

    /**
     * @ManyToOne(targetEntity="Category")
     * @var Category
     */
    protected $category;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $imageLink;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $thumbLink;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $location;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $coordinates;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $county;

    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    protected $startDatetime;

    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    protected $endDatetime;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $allDay;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $facebook;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $description;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $blurb;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $repeating;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $repeatDays;

    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    protected $repeatUntil;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $active;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $signatureEvent;

    public function __construct()
    {
        $this->allDay = 0;
        $this->repeating = 0;
        $this->active = 0;
        $this->signatureEvent = 0;
        $this->organizationAssociations = new ArrayCollection();
        $this->instructorAssociations = new ArrayCollection();
        $this->exceptions = new ArrayCollection();
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
     * @return OrganizationHostsEvent[]
     */
    public function getOrganizationAssociations()
    {
        return $this->organizationAssociations;
    }

    /**
     * @param OrganizationHostsEvent $organizationAssociation
     */
    public function addOrganizationAssociation($organizationAssociation)
    {
        $this->organizationAssociations = $organizationAssociation;
    }

    /**
     * @return Organization[]
     */
    public function getOrganizations(){
        $organizations = [];
        foreach($this->organizationAssociations as $organizationAssociation){
            $organizations[] = $organizationAssociation->getOrganization();
        }
        return $organizations;
    }

    /**
     * @param bool $activeOnly
     * @return Organization[]
     */
    public function getApprovedOrganizations($activeOnly = false){
        $organizations = [];
        foreach($this->organizationAssociations as $organizationAssociation){
            if ($organizationAssociation->isApproved() && (!$activeOnly || $organizationAssociation->getOrganization()->isActive())){
                $organizations[] = $organizationAssociation->getOrganization();
            }
        }
        return $organizations;
    }

    /**
     * @return Organization[]
     */
    public function getPendingOrganizations(){
        $organizations = [];
        foreach($this->organizationAssociations as $organizationAssociation){
            if (!$organizationAssociation->isApproved()){
                $organizations[] = $organizationAssociation->getOrganization();
            }
        }
        return $organizations;
    }

    /**
     * @return InstructorTeachesEvent[]
     */
    public function getInstructorAssociations()
    {
        return $this->instructorAssociations;
    }

    /**
     * @param InstructorTeachesEvent $instructorEventAssociation
     */
    public function addInstructorAssociations($instructorEventAssociation)
    {
        $this->instructorAssociations[] = $instructorEventAssociation;
    }

    /**
     * @return Instructor[]
     */
    public function getInstructors(){
        $instructors = [];
        foreach($this->instructorAssociations as $instructorAssociation){
            $instructors[] = $instructorAssociation->getInstructor();
        }
        return $instructors;
    }

    /**
     * @param bool $activeOnly
     * @return Instructor[]
     */
    public function getApprovedInstructors($activeOnly = false){
        $instructors = [];
        foreach($this->instructorAssociations as $instructorAssociation){
            if ($instructorAssociation->isApproved() && (!$activeOnly || $instructorAssociation->getInstructor()->isActive())){
                $instructors[] = $instructorAssociation->getInstructor();
            }
        }
        return $instructors;
    }

    /**
     * @return Instructor[]
     */
    public function getPendingInstructors(){
        $instructors = [];
        foreach($this->instructorAssociations as $instructorAssociation){
            if (!$instructorAssociation->isApproved()){
                $instructors[] = $instructorAssociation->getInstructor();
            }
        }
        return $instructors;
    }

    /**
     * @return EventException[]
     */
    public function getExceptionsMap()
    {
        $map = [];
        foreach($this->exceptions as $exception){
            $map[$exception->getDatetime()->getTimestamp()] = $exception;
        }
        return $map;
    }

    /**
     * @param EventException $exception
     */
    public function addException($exception)
    {
        $this->exceptions[] = $exception;
    }
    

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getImageLink()
    {
        return $this->imageLink;
    }

    /**
     * @param string $imageLink
     */
    public function setImageLink($imageLink)
    {
        $this->imageLink = $imageLink;
    }

    /**
     * @return string
     */
    public function getThumbLink()
    {
        return $this->thumbLink;
    }

    /**
     * @param string $thumbLink
     */
    public function setThumbLink($thumbLink)
    {
        $this->thumbLink = $thumbLink;
    }

    
    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * @param string $coordinates
     */
    public function setCoordinates($coordinates)
    {
        $this->coordinates = $coordinates;
    }

    /**
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * @param string $county
     */
    public function setCounty($county)
    {
        $this->county = $county;
    }
    
    /**
     * @return \DateTime
     */
    public function getStartDatetime()
    {
        return $this->startDatetime;
    }

    /**
     * @param \DateTime $startDatetime
     */
    public function setStartDatetime($startDatetime)
    {
        $this->startDatetime = $startDatetime;
    }

    /**
     * @return \DateTime
     */
    public function getEndDatetime()
    {
        return $this->endDatetime;
    }

    /**
     * @param \DateTime $endDatetime
     */
    public function setEndDatetime($endDatetime)
    {
        $this->endDatetime = $endDatetime;
    }

    /**
     * @return bool
     */
    public function isAllDay()
    {
        return ($this->allDay === 1);
    }

    /**
     * @param bool $allDay
     */
    public function setAllDay($allDay)
    {
        $this->allDay = ($allDay === true) ? 1 : 0;
    }

    /**
     * @return string
     */
    public function getFacebook()
    {
        return $this->facebook;
    }

    /**
     * @param string $facebook
     */
    public function setFacebook($facebook)
    {
        $this->facebook = $facebook;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getBlurb()
    {
        $blurb =  ($this->blurb === null) ? substr(preg_replace('(\r?\n)'," ", strip_tags(preg_replace('(<br ?/?>)', " ", $this->description))), 0, 500) : $this->blurb;
        return $blurb;
    }

    /**
     * @param string $blurb
     */
    public function setBlurb($blurb)
    {
        $this->blurb = $blurb;
    }

    /**
     * @return bool
     */
    public function isRepeating()
    {
        return ($this->repeating === 1);
    }

    /**
     * @param bool $repeating
     */
    public function setRepeating($repeating)
    {
        $this->repeating = ($repeating === true) ? 1 : 0;
    }

    /**
     * @return string
     */
    public function getRepeatDays()
    {
        return $this->repeatDays;
    }

    /**
     * @param string $repeatDays
     */
    public function setRepeatDays($repeatDays)
    {
        $this->repeatDays = $repeatDays;
    }

    public function getFullRepeatDays(){
        $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        $string = ''; $first = true;
        foreach(explode(',',$this->repeatDays) as $dayInt){
            (!$first) ? $string .=', ' : $first = false;
            $string.=$days[$dayInt];
        }
        return $string;
    }

    /**
     * @return \DateTime
     */
    public function getRepeatUntil()
    {
        return $this->repeatUntil;
    }

    /**
     * @param \DateTime $repeatUntil
     */
    public function setRepeatUntil($repeatUntil)
    {
        $this->repeatUntil = $repeatUntil;
    }


    /**
     * @return bool
     */
    public function activeIsSet(){
        return ($this->active !== null);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return ($this->active === 1);
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = ($active === true) ? 1 : 0;
    }

    /**
     * @return bool
     */
    public function isSignatureEvent()
    {
        return ($this->signatureEvent === 1);
    }

    /**
     * @param bool $signatureEvent
     */
    public function setSignatureEvent($signatureEvent)
    {
        $this->signatureEvent = ($signatureEvent === true) ? 1 : 0;
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'thumbLink'=>$this->thumbLink,
            'imageLink'=>$this->imageLink,
            'location'=>$this->location,
            'coordinates'=>$this->coordinates,
            'county'=>$this->county,
            'startDatetime'=>($this->startDatetime) ? $this->startDatetime->getTimestamp() : null,
            'endDatetime'=>($this->endDatetime) ? $this->endDatetime->getTimestamp(): null,
            'allDay'=>$this->allDay,
            'facebook'=>$this->facebook,
            'description'=>$this->description,
            'blurb'=>$this->blurb,
            'repeating'=>$this->repeating,
            'repeatDays'=>$this->repeatDays,
            'repeatUntil'=>($this->repeatUntil) ? $this->repeatUntil->getTimestamp() : null,
            'active'=>$this->active,
            'signatureEvent'=>$this->signatureEvent
        ];
    }

}