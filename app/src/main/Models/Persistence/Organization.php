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
class Organization implements Profile, \JsonSerializable
{
    
    const USA_DANCE = 1;
    
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
     * @OneToMany(targetEntity="UserManagesOrganization", mappedBy="organization")
     * @var UserManagesOrganization[]
     */
    protected $userAssociations;

    /**
     * @OneToMany(targetEntity="InstructorTeachesForOrganization", mappedBy="organization")
     * @var InstructorTeachesForOrganization[] $instructorAssociations
     */
    protected $instructorAssociations;

    /**
     * @OneToMany(targetEntity="OrganizationHostsEvent", mappedBy="organization")
     * @var OrganizationHostsEvent[] $eventAssociations
     */
    protected $eventAssociations;

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
    protected $description;

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

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $active;

    public function __construct()
    {
        $this->active = 0;
        $this->userAssociations = new ArrayCollection();
        $this->instructorAssociations = new ArrayCollection();
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
     * @return UserManagesOrganization[]
     */
    public function getUserAssociations()
    {
        return $this->userAssociations;
    }

    /**
     * @param UserManagesOrganization $userAssociation
     */
    public function addOrganizationAdminOrganizationAssociation($userAssociation)
    {
        $this->userAssociations[] = $userAssociation;
    }

    /**
     * @return Organization[]
     */
    public function getManagingUsers(){
        $organizations = [];
        foreach($this->userAssociations as $userAssociation){
            $organizations[] = $userAssociation->getUser();
        }
        return $organizations;
    }

    /**
     * @return Organization[]
     */
    public function getApprovedManagingUsers(){
        $organizations = [];
        foreach($this->userAssociations as $userAssociation){
            if ($userAssociation->isApproved()){
                $organizations[] = $userAssociation->getUser();
            }
        }
        return $organizations;
    }

    /**
     * @return Organization[]
     */
    public function getPendingManagingUsers(){
        $organizations = [];
        foreach($this->userAssociations as $userAssociation){
            if (!$userAssociation->isApproved()){
                $organizations[] = $userAssociation->getUser();
            }
        }
        return $organizations;
    }

    /**
     * @return InstructorTeachesForOrganization[]
     */
    public function getInstructorAssociations()
    {
        return $this->instructorAssociations;
    }

    /**
     * @param InstructorTeachesForOrganization $instructorOrganizationAssociation
     */
    public function addInstructorOrganizationAssociations($instructorOrganizationAssociation)
    {
        $this->instructorAssociations[] = $instructorOrganizationAssociation;
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
     * @return OrganizationHostsEvent[]
     */
    public function getEventAssociations()
    {
        return $this->eventAssociations;
    }

    /**
     * @param OrganizationHostsEvent $eventAssociation
     */
    public function addEventAssociation($eventAssociation)
    {
        $this->eventAssociations[] = $eventAssociation;
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
     * @return int
     */
    public function isActive()
    {
        return ($this->active === 1) ? true : false;
    }

    /**
     * @param int $active
     */
    public function setActive($active)
    {
        $this->active = ($active === true) ? 1 : 0;
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