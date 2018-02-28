<?php
/**
 * Created by enewbury.
 * Date: 12/7/15
 */

namespace EricNewbury\DanceVT\Models\Persistence;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;


/** @Entity */
class Instructor implements Profile, \JsonSerializable
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Category")
     * @var Category
     */
    protected $category;

    /**
     * @OneToMany(targetEntity="InstructorTeachesForOrganization", mappedBy="instructor")
     * @var InstructorTeachesForOrganization[] $organizationAssociations
     */
    protected $organizationAssociations;

    /**
     * @OneToMany(targetEntity="InstructorTeachesEvent", mappedBy="instructor")
     * @var InstructorTeachesEvent[] $organizationAssociations
     */
    protected $eventAssociations;

    /**
     * @OneToMany(targetEntity="UserManagesInstructor", mappedBy="instructor")
     * @var UserManagesInstructor[] $userAssociations
     */
    protected $userAssociations;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $active;

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
    protected $coverPhoto;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $description;

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
     * @Column(type="string")
     * @var string
     */
    protected $email;

    /**
     * @Column(type="string")
     * @var string $phone
     */
    protected $phone;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $website;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $facebook;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $twitter;

    public function __construct()
    {
        $this->active = 0;
        $this->userAssociations = new ArrayCollection();
        $this->organizationAssociations = new ArrayCollection();
        $this->eventAssociations = new ArrayCollection();
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
     * @return InstructorTeachesForOrganization[]
     */
    public function getOrganizationAssociations()
    {
        return $this->organizationAssociations;
    }

    /**
     * @param InstructorTeachesForOrganization $organizationAssociation
     */
    public function addOrganizationAssociation($organizationAssociation)
    {
        $this->organizationAssociations = $organizationAssociation;
    }

    /**
     * @return InstructorTeachesEvent[]
     */
    public function getEventAssociations()
    {
        return $this->eventAssociations;
    }

    /**
     * @param InstructorTeachesEvent $eventAssociation
     */
    public function addEventAssociation($eventAssociation)
    {
        $this->eventAssociations[] = $eventAssociation;
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
     * @return UserManagesInstructor[]
     */
    public function getUserAssociations()
    {
        return $this->userAssociations;
    }

    /**
     * @param UserManagesInstructor $userAssociation
     */
    public function addUserAssociation($userAssociation)
    {
        $this->userAssociations = $userAssociation;
    }

    /**
     * @return User[]
     */
    public function getManagingUsers(){
        $users = [];
        foreach($this->userAssociations as $userAssociation){
            $users[] = $userAssociation->getUser();
        }
        return $users;
    }

    /**
     * @return User[]
     */
    public function getApprovedManagingUsers(){
        $users = [];
        foreach($this->userAssociations as $userAssociation){
            if($userAssociation->isApproved()) {
                $users[] = $userAssociation->getUser();
            }
        }
        return $users;
    }

    /**
     * @return User[]
     */
    public function getPendingManagingUsers(){
        $users = [];
        foreach($this->userAssociations as $userAssociation){
            if(!$userAssociation->isApproved()) {
                $users[] = $userAssociation->getUser();
            }
        }
        return $users;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return ($this->active === 1) ? true : false;
    }

    /**
     * @return bool
     */
    public function activeIsSet(){
        return ($this->active !== null);
    }

    /**
     * @param int $active
     */
    public function setActive($active)
    {
        $this->active = ($active === true) ? 1 : 0;
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
    public function getCoverPhoto()
    {
        return $this->coverPhoto;
    }

    /**
     * @param string $coverPhoto
     */
    public function setCoverPhoto($coverPhoto)
    {
        $this->coverPhoto = $coverPhoto;
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

    public function getBlurb(){
        return substr(nl2br(strip_tags($this->description)), 0, 500);
    }
    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
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
    public function getTwitter()
    {
        return $this->twitter;
    }

    /**
     * @param string $twitter
     */
    public function setTwitter($twitter)
    {
        $this->twitter = $twitter;
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
            'imageLink'=>$this->imageLink,
            'thumbLink'=>$this->thumbLink,
            'coverPhoto'=>$this->coverPhoto,
            'location'=>$this->location,
            'coordinates'=>$this->coordinates,
            'county'=>$this->county,
            'facebook'=>$this->facebook,
            'twitter'=>$this->twitter,
            'description'=>$this->description,
            'email'=>$this->email,
            'phone'=>$this->phone,
            'website'=>$this->website,
            'active'=>$this->active,
        ];
    }

}