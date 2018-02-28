<?php
/**
 * Created by enewbury.
 * Date: 10/25/15
 */

namespace EricNewbury\DanceVT\Services;



use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use EricNewbury\DanceVT\Constants\Association;
use EricNewbury\DanceVT\Constants\PermissionStatus;
use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Persistence\BlockedIp;
use EricNewbury\DanceVT\Models\Persistence\Category;
use EricNewbury\DanceVT\Models\Persistence\Event;
use EricNewbury\DanceVT\Models\Persistence\Form;
use EricNewbury\DanceVT\Models\Persistence\GlobalPageComponent;
use EricNewbury\DanceVT\Models\Persistence\InstructorTeachesEvent;
use EricNewbury\DanceVT\Models\Persistence\InstructorTeachesForOrganization;
use EricNewbury\DanceVT\Models\Persistence\LoginAttempt;
use EricNewbury\DanceVT\Models\Persistence\NavItem;
use EricNewbury\DanceVT\Models\Persistence\Newsletter;
use EricNewbury\DanceVT\Models\Persistence\OrganizationHostsEvent;
use EricNewbury\DanceVT\Models\Persistence\Page;
use EricNewbury\DanceVT\Models\Persistence\PageComponent;
use EricNewbury\DanceVT\Models\Persistence\PermissionRequest;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Models\Persistence\Profile;
use EricNewbury\DanceVT\Models\Persistence\Subscriber;
use EricNewbury\DanceVT\Models\Persistence\Template;
use EricNewbury\DanceVT\Models\Persistence\TemplateComponent;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Models\Persistence\Token;
use EricNewbury\DanceVT\Models\Persistence\UserManagesInstructor;
use EricNewbury\DanceVT\Models\Persistence\UserManagesOrganization;
use Respect\Validation\Rules\Even;
use Respect\Validation\Rules\In;

class PersistenceService
{

    /** @var  EntityManager */
    private $db;
    private $logger;

    public function __construct($db, $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * @param $email
     * @return null|User
     */
    public function getUserByEmail($email)
    {
        return $this->db->getRepository(User::class)->findOneBy(array('email'=>$email));
    }

    /**
     * @param string $first
     * @param string $last
     * @param $email
     * @param $passwordHash
     * @param bool|int $active
     * @return User inserted account
     */
    public function createAccount($first, $last, $email, $passwordHash, $active = false)
    {
        $user = new User();
        $user->setFirstName($first);
        $user->setLastName($last);
        $user->setEmail($email);
        $user->setPasswordHash($passwordHash);
        $user->setCreatedAt(new \DateTime());
        $user->setActive($active);

        $this->db->persist($user);
        $this->db->flush();
        return $user;
    }



    public function createVerificationToken($user, $tokenVal, $expireDate)
    {
        self::createToken($user, $tokenVal, $expireDate, Token::VERIFICATION);
    }

    public function createToken($user, $tokenVal, $expireDate, $type){
        
        $token = new Token();

        $token->setUser($user);
        $token->setToken($tokenVal);
        $token->setType($type);
        $token->setExpireDate($expireDate);

        $this->db->persist($token);
        $this->db->flush();
    }

    public function createLoginAttempt($loginAttempt){
        
        $this->db->persist($loginAttempt);
        $this->db->flush();
    }


    /**
     * @param $userId
     * @param $tokenVal
     * @param string $type
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getValidToken($userId, $tokenVal, $type)
    {
        
        $query =  $this->db->createQueryBuilder()->select('t')
            ->from(Token::class, 't')
            ->where('IDENTITY(t.user) = ?1')
                ->andWhere('t.token = ?2')
                ->andWhere('t.expireDate > ?3')
                ->andWhere('t.type = ?4')
            ->setParameters(['1'=>$userId, '2'=>$tokenVal, '3'=>new \DateTime(), '4'=>$type])
            ->getQuery();
        try {
            $token = $query->getSingleResult();
            return $token;
        }
        catch(\Exception $e){
            return null;
        }
    }

    /**
     * @param $userId
     * @return null|User
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getUser($userId)
    {
        
        return $this->db->find(User::class, $userId);

    }

    public function persistChanges()
    {
        
        $this->db->flush();
    }

    /**
     * @param User $user
     * @param string $type
     * @return Token[]
     */
    public function getValidTokensForUser($user, $type)
    {
        
        return $this->db->createQueryBuilder()->select('t')
            ->from(Token::class, 't')
            ->where('IDENTITY(t.user) = ?1')
            ->andWhere('t.expireDate > ?2')
            ->andWhere('t.type = ?3')
            ->setParameters(['1'=>$user->getId(), '2'=>new \DateTime(), '3'=>$type])
            ->getQuery()->getResult();
    }

    public function savePasswordCode($user, $code, $longExpireTime=false)
    {
        $token = new Token();
        $token->setType(Token::PASS_RESET);
        $token->setUser($user);
        $token->setToken($code);

        $expireDate = new \DateTime();
        if($longExpireTime){
            $expireDate->add(new \DateInterval("P1M"));
        }
        else {
            $expireDate->add(new \DateInterval("PT8H"));
        }
        $token->setExpireDate($expireDate);

        
        $this->db->persist($token);
        $this->db->flush();
    }

    public function deactivateAccount($id)
    {
        $this->db->createQueryBuilder()->update(User::class, 'u')->set('u.active', 0)->where('u.id = ?1')->setParameter(1, $id)->getQuery()->execute();
    }

    /**
     * @param User $user
     */
    public function requestSiteAdminPermission($user)
    {
        
        $user->setSiteAdminPermission(PermissionStatus::PENDING);
        $this->db->flush();
    }

    /**
     * @return User[]
     */
    public function getAdmins()
    {
        
        $users = $this->db->getRepository(User::class)->findBy(['active' => 1, 'siteAdminPermission' => PermissionStatus::APPROVED]);

        return $users;
    }

    public function createPendingUserOrganizationAssociation($user, $organization)
    {
        
        $permission = new UserManagesOrganization();
        $permission->setUser($user);
        $permission->setOrganization($organization);
        $permission->setApproved(false);
        $this->db->persist($permission);
        $this->db->flush();
    }

    /**
     * @param $id
     * @param bool $activeOnly
     * @return Organization|null
     */
    public function getOrganization($id, $activeOnly = false){
        
        $org = $this->db->find(Organization::class, $id);
        if($activeOnly && $org != null && !$org->isActive()) $org = null;
        return $org;
    }

    /**
     * @param $name
     * @return null|Organization
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getOrganizationByName($name){

        return $this->db->getRepository(Organization::class)->findOneBy(['name'=>$name]);
    }

    /**
     * @param string $newOrganizationName
     * @return Organization
     */
    public function createInactiveOrganizationByName($newOrganizationName)
    {
        
        $organization = new Organization();
        $organization->setName($newOrganizationName);
        $organization->setActive(false);
        $this->db->persist($organization);
        $this->db->flush();
        return $organization;
    }

    public function getActiveOrganizations()
    {
        
        return $this->db->getRepository(Organization::class)->findBy(array('active'=>1));
    }

    /**
     * @param User $user
     * @param Instructor $instructor
     * @return Instructor
     */
    public function createPendingUserInstructorAssociation($user, $instructor)
    {
        

        $permission = new UserManagesInstructor();
        $permission->setUser($user);
        $permission->setInstructor($instructor);
        $permission->setApproved(false);

        $this->db->persist($permission);
        $this->db->flush();
        return $permission;

    }

    /**
     * @return Instructor[]
     */
    public function getInactiveInstructors()
    {
        
        return $this->db->getRepository(Instructor::class)->findBy(array('active'=>0));
    }

    /**
     * @param User $user
     * @param Organization $organization
     * @return null | UserManagesOrganization
     */
    public function getUserOrganizationAssociation($user, $organization)
    {
        if($user === null || $organization === null){
            return null;
        }
        return $this->db->getRepository(UserManagesOrganization::class)->findOneBy(array(
            'user'=>$user,
            'organization'=>$organization
        ));
    }

    /**
     * @param User $user
     * @param Instructor $instructor
     * @return UserManagesInstructor
     */
    public function getUserInstructorAssociation($user, $instructor)
    {
        if($user === null || $instructor === null){
            return null;
        }
        return $this->db->getRepository(UserManagesInstructor::class)->findOneBy(array(
            'user'=>$user,
            'instructor'=>$instructor
        ));
    }


    /**
     * @return Instructor[]
     */
    public function getActiveInstructors()
    {
        
        return $this->db->getRepository(Instructor::class)->findBy(array('active'=>1));
    }

    public function getAllUsers()
    {
        
        return $this->db->getRepository(User::class)->findBy([],['lastName'=>'ASC','firstName'=>'ASC']);
    }

    /**
     * @return Organization[]
     */
    public function getAllOrganizations()
    {
        
        return $this->db->getRepository(Organization::class)->findBy([],['name'=>'ASC']);
    }

    public function getAllInstructors()
    {
        
        return $this->db->getRepository(Instructor::class)->findBy([], ['name'=>'ASC']);
    }

    public function activateUser($id)
    {
        
        $this->db->createQueryBuilder()->update(User::class, 'u')->set('u.active',1)->where('u.id = ?1')->setParameter(1, $id)->getQuery()->execute();
    }

    public function deleteUser($id)
    {
        $user = $this->getUser($id);
        foreach($user->getManagedInstructorAssociations() as $association){
            $this->db->remove($association);
        }
        foreach($user->getManagedOrganizationAssociations() as $association){
            $this->db->remove($association);
        }
        $this->db->remove($user);
        $this->db->flush();
    }

    public function deleteOrganization($organizationId)
    {
        $organization = $this->getOrganization($organizationId);
        foreach($organization->getUserAssociations() as $association){
            $this->db->remove($association);
        }
        foreach($organization->getInstructorAssociations() as $association){
            $this->db->remove($association);
        }
        foreach($organization->getEventAssociations() as $association){
            $this->db->remove($association);
        }
        $this->db->remove($organization);
        $this->db->flush();
    }

    public function createPermissionRequest($permissionType, $user, $organization = null, $instructor = null, $event = null, $flush = true)
    {
        
        $request = new PermissionRequest();
        $request->setRequest($permissionType);
        $request->setUser($user);
        $request->setOrganization($organization);
        $request->setInstructor($instructor);
        $request->setEvent($event);
        $request->setRequestDate(new \DateTime());
        $this->db->persist($request);
        if($flush){
            $this->db->flush();
        }
        return $request;
    }

    /**
     * @param User $user
     */
    public function deactivateSiteAdminPermission($user)
    {
        $user->setSiteAdminPermission(PermissionStatus::OFF);
        $this->db->flush();
    }

    /**
     * @param User $user
     */
    public function activateInstructorAdminPermission($user)
    {
        $user->setInstructorAdminPermission(PermissionStatus::ACTIVE);
        $this->db->flush();
    }

    /**
     * @param User $user
     */
    public function deactivateInstructorAdminPermission($user)
    {
        $user->setInstructorAdminPermission(PermissionStatus::OFF);
        $this->db->flush();
    }

    /**
     * @param User $user
     */
    public function activateOrganizationAdminPermission($user)
    {
        $user->setOrganizationAdminPermission(PermissionStatus::ACTIVE);
        $this->db->flush();
    }

    /**
     * @param User $user
     */
    public function deactivateOrganizationAdminPermission($user)
    {
        $user->setOrganizationAdminPermission(PermissionStatus::OFF);
        $this->db->flush();
    }

    /**
     * @param User $user
     * @param Organization $organization
     * @return \EricNewbury\DanceVT\Models\Persistence\User[]
     */
    public function getSiteAdminsAndOrganizationAdmins($user, $organization)
    {
        
        $qb = $this->db->createQueryBuilder();

        return $qb->select('u')->from(User::class, 'u')
        ->join(UserManagesOrganization::class, 'm')
        ->where('u.active = 1')
        ->andWhere('u.id != ' . $user->getId())
        ->andWhere($qb->expr()->orX(
            $qb->expr()->eq('u.siteAdminPermission', '?0'),
            $qb->expr()->andX(
                $qb->expr()->eq('u.organizationAdminPermission', '?1'),
                $qb->expr()->eq('IDENTITY(m.organization)', '?2'),
                $qb->expr()->eq('m.approved', 1)
            )
        ))
        ->setParameters(array(PermissionStatus::APPROVED, PermissionStatus::ACTIVE, $organization->getId()))
        ->getQuery()->getResult();
    }

    /**
     * @param Organization[] $currentlyManagedOrganizations
     * @return Organization[]
     */
    public function getOrganizationsExcludingSet($currentlyManagedOrganizations)
    {
        
        $qb = $this->db->createQueryBuilder();

        $qb = $qb->select('o')->from(Organization::class, 'o');
        if(count($currentlyManagedOrganizations) > 0){
            $qb = $qb->where($qb->expr()->notIn('o.id', ':exceptions'))->orderBy('o.name', 'ASC')->setParameter('exceptions', array_map(function($org){return $org->getId();}, $currentlyManagedOrganizations));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Instructor[] $currentlyManagedInstructors
     * @return Instructor[]
     */
    public function getInstructorsExcludingSet($currentlyManagedInstructors)
    {
        
        $qb = $this->db->createQueryBuilder();

        $qb = $qb->select('i')->from(Instructor::class, 'i');
        if(count($currentlyManagedInstructors) > 0){
            $qb = $qb->where($qb->expr()->notIn('i.id', ':current'))->orderBy('i.name', 'ASC')->setParameter('current', array_map(function($ins){return $ins->getId();}, $currentlyManagedInstructors));
        }

        return $qb->getQuery()->getResult();
    }

    public function removeUserOrganizationAssociation($userId, $organizationId)
    {
        
        $this->db->createQueryBuilder()->delete(UserManagesOrganization::class, 'm')->where('IDENTITY(m.user) = ?0')->andWhere('IDENTITY(m.organization) = ?1')->setParameters([$userId, $organizationId])->getQuery()->execute();
    }

    public function newUserManagesOrganization($userId, $organizationId, $approved)
    {
        
        $userRef = $this->db->getReference(User::class, $userId);
        $orgRef = $this->db->getReference(Organization::class, $organizationId);

        $association = new UserManagesOrganization();
        $association->setUser($userRef);
        $association->setOrganization($orgRef);
        $association->setApproved($approved);
        $this->db->persist($association);
        $this->db->flush();
    }

    public function updateUserOrganizationApproval($userId, $organizationId, $approved)
    {
        
        $userRef = $this->db->getReference(User::class, $userId);
        $orgRef = $this->db->getReference(Organization::class, $organizationId);

        $association = $this->db->getRepository(UserManagesOrganization::class)->findOneBy(['user'=>$userRef, 'organization'=>$orgRef]);
        $association->setApproved($approved);
        $this->db->flush();
    }

    /**
     * @param User $user
     * @param int $active
     */
    public function updateOrganizationAdminPermission($user, $active)
    {
        
        $status = ($active === true) ? PermissionStatus::ACTIVE : PermissionStatus::OFF;
        $user->setOrganizationAdminPermission($status);
        $this->db->flush();

    }

    /**
     * @param User $user
     * @param bool $active
     */
    public function updateInstructorAdminPermission($user, $active)
    {
        
        $status = ($active === true) ? PermissionStatus::ACTIVE : PermissionStatus::OFF;
        $user->setInstructorAdminPermission($status);
        $this->db->flush();
    }

    public function removeUserInstructorAssociation($userId, $instructorId)
    {
        
        $this->db->createQueryBuilder()->delete(UserManagesInstructor::class, 'm')->where('IDENTITY(m.user) = ?0')->andWhere('IDENTITY(m.instructor) = ?1')->setParameters([$userId, $instructorId])->getQuery()->execute();
    }

    public function newUserManagesInstructor($userId, $instructorId, $approved)
    {
        
        $userRef = $this->db->getReference(User::class, $userId);
        $instRef = $this->db->getReference(Instructor::class, $instructorId);

        $association = new UserManagesInstructor();
        $association->setUser($userRef);
        $association->setInstructor($instRef);
        $association->setApproved($approved);
        $this->db->persist($association);
        $this->db->flush();
    }

    public function updateUserInstructorApproval($userId, $instructorId, $approved)
    {
        
        $userRef = $this->db->getReference(User::class, $userId);
        $instRef = $this->db->getReference(Instructor::class, $instructorId);

        $association = $this->db->getRepository(UserManagesInstructor::class)->findOneBy(['user'=>$userRef, 'instructor'=>$instRef]);
        $association->setApproved($approved);
        $this->db->flush();
    }

    public function persistItem($item)
    {
        $this->db->persist($item);
    }

    /**
     * @param $id
     * @param bool $activeOnly
     * @return Instructor|null
     */
    public function getInstructor($id, $activeOnly = false)
    {
        $instructor = $this->db->find(Instructor::class, $id);
        if ($activeOnly && $instructor != null && !$instructor->isActive()) $instructor = null;
        return $instructor;
    }

    /**
     * @param string $name
     * @return null|Instructor
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getInstructorByName($name)
    {

        return $this->db->getRepository(Instructor::class)->findOneBy(['name'=>$name]);
    }
    

    public function createInactiveInstructorByName($newInstructor)
    {
        
        $instructor = new Instructor();
        $instructor->setName($newInstructor);
        $instructor->setActive(false);
        $this->db->persist($instructor);
        $this->db->flush();
        return $instructor;
    }

    /**
     * @param User $user
     * @param Instructor $instructor
     * @return User[]
     */
    public function getSiteAdminsAndInstructorAdmins($user, $instructor)
    {
        
        $qb = $this->db->createQueryBuilder();

        return $qb->select('u')->from(User::class, 'u')
            ->join(UserManagesInstructor::class, 'm')
            ->where('u.active = 1')
            ->andWhere('u.id != ' . $user->getId())
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('u.siteAdminPermission', '?0'),
                $qb->expr()->andX(
                    $qb->expr()->eq('u.instructorAdminPermission', '?1'),
                    $qb->expr()->eq('IDENTITY(m.instructor)', '?2'),
                    $qb->expr()->eq('m.approved', 1)
                )
            ))
            ->setParameters(array(PermissionStatus::APPROVED, PermissionStatus::ACTIVE, $instructor->getId()))
            ->getQuery()->getResult();
    }

    public function deleteInstructor($instructorId)
    {
        $instructor = $this->getInstructor($instructorId);
        foreach($instructor->getUserAssociations() as $association){
            $this->db->remove($association);
        }
        foreach($instructor->getOrganizationAssociations() as $association){
            $this->db->remove($association);
        }
        foreach($instructor->getEventAssociations() as $association){
            $this->db->remove($association);
        }
        $this->db->remove($instructor);
        $this->db->flush();
    }
    public function deleteEntity($entity, $entityId)
    {
        
        $this->db->createQueryBuilder()->delete($entity, 'e')->where('e.id = ?1')->setParameter(1, $entityId)->getQuery()->execute();
    }

    public function removeInstructorOrganizationAssociation($instructorId, $organizationId)
    {

        $this->db->createQueryBuilder()->delete(InstructorTeachesForOrganization::class, 't')
            ->where('IDENTITY(t.instructor) = ?0')->andWhere('IDENTITY(t.organization) = ?1')->setParameters([$instructorId, $organizationId])->getQuery()->execute();

    }

    public function deleteEvent($eventId)
    {
        $event = $this->getEvent($eventId);
        foreach($event->getInstructorAssociations() as $association){
            $this->db->remove($association);
        }
        foreach($event->getOrganizationAssociations() as $association){
            $this->db->remove($association);
        }
        $this->db->remove($event);
        $this->db->flush();
    }

    public function removeEventInstructorAssociation($eventId, $instructorId)
    {
        
        $this->db->createQueryBuilder()->delete(InstructorTeachesEvent::class, 't')
            ->where('IDENTITY(t.event) = ?0')->andWhere('IDENTITY(t.instructor) = ?1')->setParameters([$eventId, $instructorId])->getQuery()->execute();

    }

    public function removeEventOrganizationAssociation($eventId, $organizationId)
    {

        $this->db->createQueryBuilder()->delete(OrganizationHostsEvent::class, 'u')
            ->where('IDENTITY(u.event) = ?0')->andWhere('IDENTITY(u.organization) = ?1')->setParameters([$eventId, $organizationId])->getQuery()->execute();

    }

    public function newInstructorOrganizationAssociation($instructorId, $organizationId, $approved)
    {
        $instRef = $this->db->getReference(Instructor::class, $instructorId);
        $orgRef = $this->db->getReference(Organization::class,$organizationId);
        $association = new InstructorTeachesForOrganization();
        $association->setInstructor($instRef);
        $association->setOrganization($orgRef);
        $association->setApproved($approved);

        $this->db->persist($association);
        $this->db->flush();
    }

    public function newEventInstructorAssociation($eventId, $instructorId, $approved)
    {
        $instRef = $this->db->getReference(Instructor::class, $instructorId);
        $eventRef = $this->db->getReference(Event::class,$eventId);
        $association = new InstructorTeachesEvent();
        $association->setEvent($eventRef);
        $association->setInstructor($instRef);
        $association->setApproved($approved);
        
        $this->db->persist($association);
        $this->db->flush();
    }

    public function newEventOrganizationAssociation($eventId, $organizationId, $approved)
    {
        $eventRef = $this->db->getReference(Event::class, $eventId);
        $orgRef = $this->db->getReference(Organization::class,$organizationId);
        $association = new OrganizationHostsEvent();
        $association->setEvent($eventRef);
        $association->setOrganization($orgRef);
        $association->setApproved($approved);

        $this->db->persist($association);
        $this->db->flush();
    }
    
    public function updateInstructorOrganizationAssociationStatus($instructorId, $organizationId, $isApproved, $user)
    {
        
        $instRef = $this->db->getReference(Instructor::class, $instructorId);
        $orgRef = $this->db->getReference(Organization::class, $organizationId);

        $association = $this->db->getRepository(InstructorTeachesForOrganization::class)->findOneBy(['instructor'=>$instRef, 'organization'=>$orgRef]);

        //updatable only by admin
        if($user->isApprovedSiteAdmin()){
            $association->setApproved($isApproved);
        }

        $this->db->flush();
    }

    public function updateEventInstructorAssociationStatus($eventId, $instructorId, $isApproved, $user)
    {

        $instRef = $this->db->getReference(Instructor::class, $instructorId);
        $eventRef= $this->db->getReference(Event::class, $eventId);

        $association = $this->db->getRepository(InstructorTeachesEvent::class)->findOneBy(['instructor'=>$instRef, 'event'=>$eventRef]);

        //updatable only by admin
        if($user->isApprovedSiteAdmin()){
            $association->setApproved($isApproved);
        }

        $this->db->flush();
    }

    public function updateEventOrganizationAssociationStatus($eventId, $organizationId, $isApproved, $user)
    {

        $orgRef = $this->db->getReference(Organization::class, $organizationId);
        $eventRef= $this->db->getReference(Event::class, $eventId);

        $association = $this->db->getRepository(OrganizationHostsEvent::class)->findOneBy(['organization'=>$orgRef, 'event'=>$eventRef]);

        //updatable only by admin
        if($user->isApprovedSiteAdmin()){
            $association->setApproved($isApproved);
        }

        $this->db->flush();
    }

    /**
     * @param $id
     * @return Organization|bool|\Doctrine\Common\Proxy\Proxy|null
     * @throws \Doctrine\ORM\ORMException
     */
    public function getOrganizationRef($id)
    {
        return $this->db->getReference(Organization::class,$id);
    }

    /**
     * @param $instructor
     * @param $organizationRef
     * @return null|InstructorTeachesForOrganization
     */
    public function getInstructorOrganizationAssociation($instructor, $organizationRef)
    {
        
        return $this->db->getRepository(InstructorTeachesForOrganization::class)->findOneBy(['instructor'=>$instructor, 'organization'=>$organizationRef]);
    }

    /**
     * @param $eventRef
     * @param $instructorRef
     * @return InstructorTeachesForOrganization|null
     */
    public function getEventInstructorAssociation($eventRef, $instructorRef)
    {

        return $this->db->getRepository(InstructorTeachesEvent::class)->findOneBy(['event'=>$eventRef, 'instructor'=>$instructorRef]);
    }

    /**
     * @param $eventRef
     * @param $organizationRef
     * @return InstructorTeachesForOrganization|null
     */
    public function getEventOrganizationAssociation($eventRef, $organizationRef)
    {
        return $this->db->getRepository(OrganizationHostsEvent::class)->findOneBy(['event'=>$eventRef, 'organization'=>$organizationRef]);
    }
    
    public function createPendingInstructorOrganizationAssociation($instructor, $organization)
    {
        $association = new InstructorTeachesForOrganization();
        $association->setInstructor($instructor);
        $association->setOrganization($organization);
        $association->setApproved(false);
        $this->db->persist($association);
        $this->db->flush();
    }

    public function createPendingEventInstructorAssociation($instructor, $event)
    {
        $association = new InstructorTeachesEvent();
        $association->setInstructor($instructor);
        $association->setEvent($event);
        $association->setApproved(false);
        $this->db->persist($association);
        $this->db->flush();
    }

    public function createPendingEventOrganizationAssociation($organization, $event)
    {
        $association = new OrganizationHostsEvent();
        $association->setOrganization($organization);
        $association->setEvent($event);
        $association->setApproved(false);
        $this->db->persist($association);
        $this->db->flush();
    }
    
    public function getInstructorRef($id)
    {
        return $this->db->getReference(Instructor::class, $id);
    }

    public function removeInstructorOrganizationRequest($instRef, $orgRef)
    {
        
        $qb = $this->db->createQueryBuilder();
        $qb->delete(PermissionRequest::class, 'r')->where('r.instructor = ?0')->andWhere('r.organization = ?1')->setParameters([$instRef, $orgRef])->getQuery()->execute();
    }

    public function removeUserOrganizationRequest($user, $organization)
    {
        
        $qb = $this->db->createQueryBuilder();
        $qb->delete(PermissionRequest::class, 'r')->where('r.user = ?0')->andWhere('r.organization = ?1')->setParameters([$user, $organization])->getQuery()->execute();
    }

    public function removeUserInstructorRequest($user, $instructor)
    {
        
        $qb = $this->db->createQueryBuilder();
        $qb->delete(PermissionRequest::class, 'r')->where('r.user = ?0')->andWhere('r.instructor = ?1')->setParameters([$user, $instructor])->getQuery()->execute();
    }

    /**
     * @return PermissionRequest[]
     */
    public function getAllPendingAndRecentRequests()
    {
        
        return $this->db->getRepository(PermissionRequest::class)->getIncomplete();
    }

    /**
     * @param User $user
     * @return PermissionRequest[]
     */
    public function getPermissionRequestsForInstructorOrOrganizationAdmin($user)
    {
        
        $qb = $this->db->createQueryBuilder();
        $qb->select('r')->from(PermissionRequest::class,'r');
        $qb->where($qb->expr()->isNull('r.completed'));

        $ors = $qb->expr()->orX();
        if($user->isActiveInstructorAdmin()){
            $instructorIds = self::getAssociatedInstructorIds($user);

            //a user has requested to be an admin of one of your managed instructors
            $ors->add(
                $qb->expr()->andX($qb->expr()->eq('r.request', ":p1"), $qb->expr()->in('IDENTITY(r.instructor)', ':instructorIds'))
            );

            //an organization has requested to have one of your managed instructors
            $ors->add(
                $qb->expr()->andX($qb->expr()->eq('r.request', ":p2"), $qb->expr()->in('IDENTITY(r.instructor)', ':instructorIds'))
            );

            //an event wants to name you as an instructor
            $ors->add(
                $qb->expr()->andX($qb->expr()->eq('r.request', ":p3"), $qb->expr()->in('IDENTITY(r.instructor)', ':instructorIds'))
            );

            $qb->setParameter('instructorIds', $instructorIds);
            $qb->setParameter('p1', Association::USER_MANAGES_INSTRUCTOR);
            $qb->setParameter('p2', Association::ORGANIZATION_HAS_INSTRUCTOR);
            $qb->setParameter('p3', Association::INSTRUCTOR_TEACHES_EVENT);

        }

        if($user->isActiveOrganizationAdmin()){
            $organizationIds = self::getAssociatedOrganizationIds($user);

            $ors->add(
                $qb->expr()->andX($qb->expr()->eq('r.request', ":p4"), $qb->expr()->in('IDENTITY(r.organization)', ':organizationIds'))
            );

            //an organization has requested to have one of your managed instructors
            $ors->add(
                $qb->expr()->andX($qb->expr()->eq('r.request', ":p5"), $qb->expr()->in('IDENTITY(r.organization)', ':organizationIds'))
            );

            //an event wants to name you as a host
            $ors->add(
                $qb->expr()->andX($qb->expr()->eq('r.request', ":p6"), $qb->expr()->in('IDENTITY(r.organization)', ':organizationIds'))
            );

            $qb->setParameter('organizationIds', $organizationIds);
            $qb->setParameter('p4', Association::USER_MANAGES_ORGANIZATION);
            $qb->setParameter('p5', Association::INSTRUCTOR_TEACHES_FOR_ORGANIZATION);
            $qb->setParameter('p6', Association::ORGANIZATION_HOSTS_EVENT);

        }
        if($ors->count() > 0){
            $qb->andWhere($ors);
        }
        return $qb->getQuery()->getResult();
    }

    /**
 * @param User $user
 * @return int[]
 */
    private static function getAssociatedInstructorIds($user){
        //get instructor in clause
        $instructorIds = [];
        foreach($user->getApprovedManagedInstructors() as $instructor){
            $instructorIds[] = $instructor->getId();
        }

        return $instructorIds;
    }

    /**
     * @param User $user
     * @return int[]
     */
    private static function getAssociatedOrganizationIds($user){
        //get organization in clause
        $organizationIds = [];
        foreach($user->getApprovedManagedOrganizations() as $organization){
            $organizationIds[] = $organization->getId();
        }
        return $organizationIds;
    }

    /**
     * @param int $requestId
     * @return null|PermissionRequest
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getRequest($requestId)
    {
        
        return $this->db->find(PermissionRequest::class, $requestId);
    }

    /**
     * @param $instructor
     * @param $event
     * @return null|InstructorTeachesEvent
     */
    public function getInstructorEventAssociation($instructor, $event)
    {
        
        return $this->db->getRepository(InstructorTeachesEvent::class)->findOneBy(['instructor'=>$instructor, 'event'=>$event]);

    }

    /**
     * @param Organization $organization
     * @param Event $event
     * @return null|OrganizationHostsEvent
     */
    public function getOrganizationEventAssociation($organization, $event)
    {
        return $this->db->getRepository(OrganizationHostsEvent::class)->findOneBy(['organization'=>$organization, 'event'=>$event]);

    }

    public function completePermission($requestType, $status, $userId=null, $organizationId=null, $instructorId=null, $eventId=null)
    {
        $params = ['type'=>$requestType, 'status'=>$status];
        
        $qb = $this->db->createQueryBuilder()->update(PermissionRequest::class, 'r')->set('r.completed', ":status");

        $qb->where($qb->expr()->eq('r.request', ':type'));
        if($userId !=null){
            $qb = $qb->andWhere($qb->expr()->eq('IDENTITY(r.user)', ':userId'));
            $params['userId']=$userId;
        }
        if($organizationId !=null){
            $qb->andWhere($qb->expr()->eq('IDENTITY(r.organization)', ':organizationId'));
            $params['organizationId'] = $organizationId;
        }
        if($instructorId !=null){
            $qb->andWhere($qb->expr()->eq('IDENTITY(r.instructor)', ':instructorId'));
            $params['instructorId'] = $instructorId;
        }
        if($eventId !=null){
            $qb->andWhere($qb->expr()->eq('IDENTITY(r.event)', ':eventId'));
            $params['eventId'] = $eventId;
        }
        $qb->setParameters($params)->getQuery()->execute();
    }

    public function deletePermissionRequest($requestType, $userId=null, $organizationId=null, $instructorId=null, $eventId=null)
    {
        $params = ['type'=>$requestType];
        
        $qb = $this->db->createQueryBuilder()->delete(PermissionRequest::class, 'r');

        $qb->where($qb->expr()->eq('r.request', ':type'));
        if($userId !=null){
            $qb = $qb->andWhere($qb->expr()->eq('IDENTITY(r.user)', ':userId'));
            $params['userId']=$userId;
        }
        if($organizationId !=null){
            $qb->andWhere($qb->expr()->eq('IDENTITY(r.organization)', ':organizationId'));
            $params['organizationId'] = $organizationId;
        }
        if($instructorId !=null){
            $qb->andWhere($qb->expr()->eq('IDENTITY(r.instructor)', ':instructorId'));
            $params['instructorId'] = $instructorId;
        }
        if($eventId !=null){
            $qb->andWhere($qb->expr()->eq('IDENTITY(r.event)', ':eventId'));
            $params['eventId'] = $eventId;
        }
        $qb->setParameters($params)->getQuery()->execute();
    }


    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param $search
     * @param $organizations
     * @param $instructors
     * @param $categories
     * @param $counties
     * @param $activeOnly
     * @return \EricNewbury\DanceVT\Models\Persistence\Event[]
     */
    public function getEvents(DateTime $start, DateTime $end, $search, $organizations, $instructors, $categories, $counties, $activeOnly){
        
        $qb = $this->db->createQueryBuilder();
        $ex = $qb->expr();
        /** @var QueryBuilder $step */
        $step = $qb->select('e')->from(Event::class, 'e');
        $step = $step->leftJoin('e.organizationAssociations', 'o')->leftJoin('e.instructorAssociations', 'i');

        //get events in range
        $step = $step->where($ex->orX(
            //repeating
            $ex->andX(
                $ex->eq('e.repeating', 1),
                $ex->lte('e.startDatetime', ':endRange'),
                $ex->orX($ex->isNull('e.repeatUntil'), $ex->gt('e.repeatUntil', ':startRange'))
            ),
            //non repeating
            $ex->andX(
                $ex->eq('e.repeating', 0),
                $ex->between('e.startDatetime', ':startRange', ':endRange')
            )
        ));
        $step = $step->setParameter('startRange', $start);
        $step = $step->setParameter('endRange', $end);

        if($search){
            $step->andWhere($ex->like('e.name', ':searchQuery'));
            $step = $step->setParameter('searchQuery', '%'.$search.'%');
        }

        //Set if only selecting active events
        if($activeOnly){
            $step = $step->andWhere($ex->eq('e.active', 1));
        }

        if(count($organizations)){
            $step = $step->andWhere($ex->in('o.organization', ':orgs'))->andWhere($ex->eq('o.approved', 1));
            $step = $step->setParameter('orgs', $organizations);
        }
        if(count($instructors)){
            $step = $step->andWhere($ex->in('i.instructor', ':instructors'))->andWhere($ex->eq('i.approved', 1));
            $step = $step->setParameter('instructors', $instructors);
        }
        if(count($categories)){
            $step = $step->andWhere($ex->in('e.category',':categories'));
            $step = $step->setParameter('categories', $categories);
        }
        if(count($counties)){
            $step = $step->andWhere($ex->in('e.county',':counties'));
            $step = $step->setParameter('counties', $counties);
        }

        $step = $step->addOrderBy('e.name','ASC');
        return $step->getQuery()->getResult();

    }
    
    /**
     * @param $id
     * @return null|Event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getEvent($id)
    {
        
        return $this->db->find(Event::class, $id);
    }

    public function getCategories()
    {
        return $this->db->getRepository(Category::class)->findAll();
    }

    public function getReference($class, $id){
        return $this->db->getReference($class, $id);
    }
    public function refresh($obj){
        $this->db->refresh($obj);
    }

    /**
     * @param $pageUrl
     * @return null|Page
     */
    public function getPageByUrl($pageUrl)
    {
        return $this->db->getRepository(Page::class)->findOneBy(['url'=>$pageUrl]);
    }

    /**
     * @return NavItem[]
     */
    public function getNavLinks()
    {
        return $this->db->getRepository(NavItem::class)->findAll();
    }

    /**
     * @return GlobalPageComponent[]
     */
    public function getGlobalData()
    {
        return $this->db->getRepository(GlobalPageComponent::class)->findBy(array(), array('order' => 'ASC'));
    }

    /**
     * @return Template[]
     */
    public function getAllUnlockedTemplates()
    {
        return $this->db->getRepository(Template::class)->findBy(['locked'=>0]);
    }

    /**
     * @return Page[]
     */
    public function getAllPages()
    {
        return $this->db->getRepository(Page::class)->findAll();
    }

    /**
     * @return Page[]
     */
    public function getAllActivePages()
    {
        $qb = $this->db->createQueryBuilder();
        return $qb->select('p')->from(Page::class, 'p')
            ->leftJoin('p.navItems', 'n')->where('p.active = 1')->orderBy('n.pageOrder','ASC')->getQuery()->getResult();
    }

    /**
     * @param string $newPageName
     * @param int $templateId
     * @return Page
     * @throws ClientErrorException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function createNewPage($newPageName, $templateId)
    {
        $page = new Page();
        $page->setName($newPageName);
        /** @var Template $template */
        $template = $this->db->find(Template::class, $templateId);
        if ($template->isLocked()){
            throw new ClientErrorException('You cannot create more instances of locked templates');
        }
        $page->setTemplate($template);
        $this->db->persist($page);
        $this->db->flush();
        foreach($template->getComponents() as $templateComponent){
            $pageComponent = new PageComponent();
            $pageComponent->setTemplateComponent($templateComponent);
            $pageComponent->setPage($page);
            $this->db->persist($pageComponent);
            $page->addComponent($pageComponent);
        }
        $this->db->flush();
        return $page;
    }

    /**
     * @param int $pageId
     * @return null|Page
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getPage($pageId)
    {
        return $this->db->find(Page::class, $pageId);
    }

    public function updatePageComponent($componentSlug, $componentValue, $pageId)
    {
        /** @var Page $page */
        $page = $this->db->find(Page::class, $pageId);
        /** @var TemplateComponent $templateComponent */
        $templateComponent = $this->db->getRepository(TemplateComponent::class)->findOneBy(['template'=>$page->getTemplate(), 'slug'=>$componentSlug]);
        if($templateComponent == null){
            throw new ClientErrorException('Couldn\'t find a template component of name "'.$componentSlug.'"');
        }
        $pageComponent = $this->db->getRepository(PageComponent::class)->findOneBy(['templateComponent'=>$templateComponent]);
        if($pageComponent == null) {
            throw new ClientErrorException('Couldn\'t find a page component for template component of name "'.$componentSlug.'"');
        }
        $pageComponent->setValue($componentValue);
        $this->db->flush();
    }

    /**
     * @param $activeOnly
     * @return Event|null
     */
    public function getNextSignatureEvent($activeOnly)
    {

        $qb = $this->db->createQueryBuilder();
        $validDayArray = [];
        $validDays = $qb->expr()->orX();
        $now = new DateTime();
        $validDay = new DateTime();
        $nextWeek = new DateTime('next sunday');
        while($validDay <= $nextWeek){
            $validDayArray['day'.$validDay->format('w')] = '%'.$validDay->format('w').'%';
            $validDays->add($qb->expr()->like('e.repeatDays', ':day'. $validDay->format('w')));
            $validDay->add(new \DateInterval('P1D'));
        }
        $qb = $qb->select('e')->from(Event::class, 'e')
            //is signature event
            ->where($qb->expr()->eq('e.signatureEvent', 1));
            //and is active
            if($activeOnly){
                $qb->andWhere($qb->expr()->eq('e.active', 1));
            }
            //repeating or non repeating
            $qb->andWhere(
                $qb->expr()->orX(
                    //non repeating and starts after now or right now
                    $qb->expr()->andX($qb->expr()->eq('e.repeating', 0), $qb->expr()->gte('e.startDatetime', ':now')),
                    //repeating and either repeats until next sunday or longer, or repeatsUntil now-next sunday and repeat days is a weekday between now and sunday
                    $qb->expr()->andX(
                        $qb->expr()->eq('e.repeating', 1),
                        $qb->expr()->orX(
                            $qb->expr()->isNull('e.repeatUntil'),
                            $qb->expr()->gte('e.repeatUntil', ':nextWeek'),
                            $qb->expr()->andX(
                                $qb->expr()->gte('e.repeatUntil', ':now'),
                                $validDays
                            )
                        )
                    )
                )
            )
            ->orderBy('e.startDatetime')->setParameters(array_merge($validDayArray, ['now'=>$now, 'nextWeek'=>$nextWeek]));

        return $qb->getQuery()->getResult();
    }

    public function updateGlobalComponent($componentSlug, $componentValue)
    {
        $pageComponent = $this->db->getRepository(GlobalPageComponent::class)->findOneBy(['slug'=>$componentSlug]);
        if($pageComponent == null) {
            throw new ClientErrorException('Couldn\'t find a component of name "'.$componentSlug.'"');
        }
        $pageComponent->setValue($componentValue);
        $this->db->flush();
    }

    public function resetNavItems($navItems)
    {
        $qb = $this->db->createQueryBuilder();
        //delete all old
        $qb->delete()->from(NavItem::class, 'n')->getQuery()->execute();
        foreach($navItems as $order=>$navItem){
            $page = $this->getPage($navItem);
            if ($page == null){
                throw new ClientErrorException('Couldn\'t find pages specified for nav items.');
            }
            $navItem = new NavItem();
            $navItem->setPage($page);
            $navItem->setPageOrder($order);
            $this->db->persist($navItem);
        }
        $this->db->flush();
    }
    /**
     * @param Event $event
     * @param Event $newEvent
     */
    public function duplicateEventAssociations($user, $event, $newEvent){
        foreach($event->getInstructorAssociations() as $association){
            $newAssociation = clone $association;
            $newAssociation->setEvent($newEvent);
            $this->db->persist($newAssociation);
            if(!$newAssociation->isApproved()){
                $this->createPermissionRequest(Association::INSTRUCTOR_TEACHES_EVENT, $user, null, $newAssociation->getInstructor(), $newEvent, false);
            }
        }

        foreach($event->getOrganizationAssociations() as $association){
            $newAssociation = clone $association;
            $newAssociation->setEvent($newEvent);
            $this->db->persist($newAssociation);
            if(!$newAssociation->isApproved()){
                $this->createPermissionRequest(Association::ORGANIZATION_HOSTS_EVENT, $user, $newAssociation->getOrganization(), null, $newEvent, false);
            }
        }
        
        $this->db->flush();
    }

    /**
     * @param Event $event
     */
    public function removeAllEventAssociations($event)
    {
        foreach($event->getInstructorAssociations() as $association){
            $this->db->remove($association);
        }
        foreach($event->getOrganizationAssociations() as $association){
            $this->db->remove($association);
        }
        $this->db->flush();
    }

    /**
     * @param int $pageId
     * @param bool $isOn
     * @throws ClientErrorException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function updatePageActivation($pageId, $isOn)
    {
        /** @var Page $page */
        $page = $this->db->find(Page::class, $pageId);
        if($page == null){
            throw new ClientErrorException('Page not found');
        }
        $page->setActive($isOn);
        $this->db->flush();
    }

    public function deletePage($pageId)
    {
        $this->db->createQueryBuilder()->delete()->from(PageComponent::class, 'c')->where('IDENTITY(c.page) = :pageId')->setParameter('pageId', $pageId)->getQuery()->execute();
        $this->db->createQueryBuilder()->delete()->from(Page::class, 'p')->where('p.id = :id')->setParameter('id', $pageId)->getQuery()->execute();
    }

    public function getForms()
    {
        return $this->db->getRepository(Form::class)->findAll();
    }

    /**
     * @param int $formId
     * @return null|Form
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getForm($formId)
    {
        return $this->db->find(Form::class, $formId);
    }

    public function getCategory($categoryId)
    {
        return $this->db->find(Category::class, $categoryId);
    }

    public function getInstructorsByFilters($activeOnly, $searchQuery = null, $organizations = null, $categories = null, $counties = null)
    {
        $qb = $this->db->createQueryBuilder();
        $ex = $qb->expr();
        /** @var QueryBuilder $step */
        $step = $qb->select('i')->from(Instructor::class, 'i');
        $step = $step->leftJoin('i.organizationAssociations', 'o');

        if($activeOnly){
            $step = $step->where('i.active = 1');
        }
        if(isSet($searchQuery)){
            $step = $step->andWhere($ex->like('i.name', ':searchQuery'));
            $step = $step->setParameter('searchQuery', '%'.$searchQuery.'%');
        }

        if(count($organizations)){
            $step = $step->andWhere($ex->in('o.organization', ':orgs'))->andWhere($ex->eq('o.approved', 1));
            $step = $step->setParameter('orgs', $organizations);
        }

        if(count($categories)){
            $step = $step->andWhere($ex->in('i.category',':categories'));
            $step = $step->setParameter('categories', $categories);
        }
        if(count($counties)){
            $step = $step->andWhere($ex->in('i.county',':counties'));
            $step = $step->setParameter('counties', $counties);
        }

        return $step->orderBy('i.name', 'ASC')->getQuery()->getResult();
    }

    public function getOrganizationsByFilters($activeOnly, $searchQuery, $instructors, $categories, $counties = null)
    {
        $qb = $this->db->createQueryBuilder();
        $ex = $qb->expr();
        /** @var QueryBuilder $step */
        $step = $qb->select('o')->from(Organization::class, 'o');
        $step = $step->leftJoin('o.instructorAssociations', 'i');

        if($activeOnly){
            $step = $step->where('o.active = 1');
        }
        if(isSet($searchQuery)){
            $step = $step->andWhere($ex->like('o.name', ':searchQuery'));
            $step = $step->setParameter('searchQuery', '%'.$searchQuery.'%');
        }

        if(count($instructors)){
            $step = $step->andWhere($ex->in('i.instructor', ':instructors'))->andWhere($ex->eq('i.approved', 1));
            $step = $step->setParameter('instructors', $instructors);
        }

        if(count($categories)){
            $step = $step->andWhere($ex->in('o.category',':categories'));
            $step = $step->setParameter('categories', $categories);
        }
        if(count($counties)){
            $step = $step->andWhere($ex->in('o.county',':counties'));
            $step = $step->setParameter('counties', $counties);
        }

        return $step->orderBy('o.name', 'ASC')->getQuery()->getResult();
    }

    public function getAllCounties($entity)
    {
        return array_column($this->db->createQueryBuilder()->select('t.county')->from($entity, 't')->where('t.county IS NOT NULL')->andWhere('t.active = 1')->groupBy('t.county')->getQuery()->getResult(), 'county');
    }

    public function getInstructorsByIds($ids)
    {
        return $this->db->createQueryBuilder()->select('i')->from(Instructor::class, 'i')->where($this->db->getExpressionBuilder()->in('i.id', ':ids'))->setParameter('ids', $ids)->getQuery()->getResult();
    }
    public function getOrganizationsByIds($ids)
    {
        return $this->db->createQueryBuilder()->select('o')->from(Organization::class, 'o')->where($this->db->getExpressionBuilder()->in('o.id', ':ids'))->setParameter('ids', $ids)->getQuery()->getResult();
    }

    /**
     * @return Subscriber[]
     */
    public function getSubscribers()
    {
        return $this->db->getRepository(Subscriber::class)->findAll();
    }

    public function createNewSubscriber($name, $email)
    {
        $subscriber = new Subscriber();
        $subscriber->setName($name);
        $subscriber->setEmail($email);
        $this->db->persist($subscriber);
        $this->db->flush();
    }

    public function unsubscribe($email)
    {
        $this->db->createQueryBuilder()->delete()->from(Subscriber::class, 's')->where('s.email = :email')->setParameter('email', $email)->getQuery()->execute();
    }

    /**
     * @param Newsletter $newsletter
     */
    public function saveNewsletter($newsletter)
    {
        $this->db->persist($newsletter);
        $this->db->flush();
    }

    public function getNewsletter($newsletterId)
    {
        return $this->db->find(Newsletter::class, $newsletterId);
    }

    /**
     * @param String $email
     * @param String $ip
     * @param DateTime $timeAgo
     * @return LoginAttempt[]
     */
    public function getLoginFailedAttempts($email, $ip, $timeAgo)
    {
        return $this->db->createQueryBuilder()->select('a')->from(LoginAttempt::class, 'a')
            ->where('a.email = :email')
            ->andWhere('a.ipAddress = :ip')
            ->andWhere('a.date > :date')
            ->andWhere('a.outcome = :outcome')
            ->orderBy('a.date', 'DESC')
            ->setParameters(['email'=>$email, 'ip'=>$ip, 'date'=>$timeAgo, 'outcome'=>"FAIL"])->getQuery()->getResult();
    }

    /**
     * @param String $ip
     * @return null|BlockedIp
     */
    public function getBlockedIp($ip)
    {
        return $this->db->getRepository(BlockedIp::class)->findOneBy(['ip'=>$ip]);
    }

    public function saveBlockedIp($blocked)
    {
        $this->db->persist($blocked);
        $this->db->flush();
    }

    public function deleteBlockedIp($ip)
    {
        $this->db->createQueryBuilder()->delete()->from(BlockedIp::class, 'b')->where('b.ip = ?0')->setParameters([$ip])->getQuery()->execute();
    }


}