<?php
/**
 * Created by enewbury.
 * Date: 10/25/15
 */

namespace EricNewbury\DanceVT\Models\Persistence;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use EricNewbury\DanceVT\Constants\PermissionStatus;


/** @Entity */
class User
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
    protected $firstName;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $lastName;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $email;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $requestedEmail;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $passwordHash;

    /**
     * @Column(type="datetime")
     * @var Datetime
     */
    protected $createdAt;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $active;

    /**
     * @Column(type="string")
     * @var string $siteAdminPermission
     */
    protected $siteAdminPermission = 'OFF';

    /**
     * @Column(type="string")
     * @var string $instructorAdminPermission
     */
    protected $instructorAdminPermission = 'OFF';

    /**
     * @Column(type="string")
     * @var string $organizationAdminPermission
     */
    protected $organizationAdminPermission = 'OFF';

    /**
     * @OneToMany(targetEntity="Token", mappedBy="user")
     * @var Token[]
     */
    protected $tokens;

    /**
     * @OneToMany(targetEntity="UserManagesInstructor", mappedBy="user")
     * @var UserManagesInstructor[]
     */
    protected $managedInstructorAssociations;

    /**
     * @OneToMany(targetEntity="UserManagesOrganization", mappedBy="user")
     * @var UserManagesOrganization[]
     */
    protected $managedOrganizationAssociations;


    public function __construct()
    {
        $this->managedInstructorAssociations = new ArrayCollection();
        $this->managedOrganizationAssociations = new ArrayCollection();
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
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
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
    public function getRequestedEmail()
    {
        return $this->requestedEmail;
    }

    /**
     * @param string $requestedEmail
     */
    public function setRequestedEmail($requestedEmail)
    {
        $this->requestedEmail = $requestedEmail;
    }

    /**
     * @return string
     */
    public function getPasswordHash()
    {
        return $this->passwordHash;
    }

    /**
     * @param string $passwordHash
     */
    public function setPasswordHash($passwordHash)
    {
        $this->passwordHash = $passwordHash;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
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
     * @return string
     */
    public function getSiteAdminPermission()
    {
        return $this->siteAdminPermission;
    }

    /**
     * @param string $siteAdminPermission
     */
    public function setSiteAdminPermission($siteAdminPermission)
    {
        $this->siteAdminPermission = $siteAdminPermission;
    }

    /**
     * @return string
     */
    public function getInstructorAdminPermission()
    {
        return $this->instructorAdminPermission;
    }

    /**
     * @param string $instructorAdminPermission
     */
    public function setInstructorAdminPermission($instructorAdminPermission)
    {
        $this->instructorAdminPermission = $instructorAdminPermission;
    }

    /**
     * @return string
     */
    public function getOrganizationAdminPermission()
    {
        return $this->organizationAdminPermission;
    }

    /**
     * @param string $organizationAdminPermission
     */
    public function setOrganizationAdminPermission($organizationAdminPermission)
    {
        $this->organizationAdminPermission = $organizationAdminPermission;
    }

    /**
     * @return bool
     */
    public function isApprovedSiteAdmin(){
        return ($this->siteAdminPermission === PermissionStatus::APPROVED);
    }

    /**
     * @return bool
     */
    public function isPendingSiteAdmin(){
        return ($this->siteAdminPermission === PermissionStatus::PENDING);
    }

    /**
     * @return bool
     */
    public function isActiveInstructorAdmin(){
        return ($this->instructorAdminPermission === PermissionStatus::ACTIVE);
    }

    /**
     * @return bool
     */
    public function isActiveOrganizationAdmin(){
        return ($this->organizationAdminPermission === PermissionStatus::ACTIVE);
    }

    /**
     * @return Token[]
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param Token $token
     */
    public function addToken($token)
    {
        $this->tokens[] = $token;
    }

    /**
     * @return UserManagesInstructor[]
     */
    public function getManagedInstructorAssociations()
    {
        return $this->managedInstructorAssociations;
    }

    /**
     * @param UserManagesInstructor $managedInstructorAssociation
     */
    public function addManagedInstructorAssociation($managedInstructorAssociation)
    {
        $this->managedInstructorAssociations = $managedInstructorAssociation;
    }

    /**
     * @return Instructor[]
     */
    public function getManagedInstructors(){
        $instructors = [];
        foreach($this->managedInstructorAssociations as $instructorAssociation){
            $instructors[] = $instructorAssociation->getInstructor();
        }
        return $instructors;
    }

    /**
     * @return Instructor[]
     */
    public function getApprovedManagedInstructors(){
        $instructors = [];
        foreach($this->managedInstructorAssociations as $instructorAssociation){
            if($instructorAssociation->isApproved()) {
                $instructors[] = $instructorAssociation->getInstructor();
            }
        }
        return $instructors;
    }

    /**
     * @return Instructor[]
     */
    public function getPendingManagedInstructors(){
        $instructors = [];
        foreach($this->managedInstructorAssociations as $instructorAssociation){
            if(!$instructorAssociation->isApproved()) {
                $instructors[] = $instructorAssociation->getInstructor();
            }
        }
        return $instructors;
    }

    /**
     * @return UserManagesOrganization[]
     */
    public function getManagedOrganizationAssociations()
    {
        return $this->managedOrganizationAssociations;
    }

    /**
     * @param UserManagesOrganization $managedOrganizationAssociation
     */
    public function addManagedOrganizationAssociation($managedOrganizationAssociation)
    {
        $this->managedOrganizationAssociations = $managedOrganizationAssociation;
    }

    /**
     * @return Organization[]
     */
    public function getManagedOrganizations(){
        $orgs = [];
        foreach($this->managedOrganizationAssociations as $organizationAssociation){
            $orgs[] = $organizationAssociation->getOrganization();
        }
        return $orgs;
    }

    /**
     * @return Organization[]
     */
    public function getApprovedManagedOrganizations(){
        $orgs = [];
        foreach($this->managedOrganizationAssociations as $organizationAssociation){
            if($organizationAssociation->isApproved()) {
                $orgs[] = $organizationAssociation->getOrganization();
            }
        }
        return $orgs;
    }

    /**
     * @return Organization[]
     */
    public function getPendingManagedOrganizations(){
        $orgs = [];
        foreach($this->managedOrganizationAssociations as $organizationAssociation){
            if(!$organizationAssociation->isApproved()) {
                $orgs[] = $organizationAssociation->getOrganization();
            }
        }
        return $orgs;
    }

}