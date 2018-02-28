<?php
/**
 * Created by Eric Newbury.
 * Date: 7/9/16
 */

namespace EricNewbury\DanceVT\Services;


use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Exceptions\FastLoginException;
use EricNewbury\DanceVT\Models\Exceptions\IpBlockedForeverException;
use EricNewbury\DanceVT\Models\Exceptions\IpBlockedTemporarilyException;
use EricNewbury\DanceVT\Models\Exceptions\TooManyLoginAttempsException;
use EricNewbury\DanceVT\Models\Persistence\BlockedIp;
use EricNewbury\DanceVT\Models\Persistence\LoginAttempt;
use EricNewbury\DanceVT\Models\Persistence\User;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Util\Validator;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class UserAccountServiceTest extends TestCase
{
    /** @var  PersistenceService */
    private $persistenceService;
    
    /** @var  MailService */
    private $mailService;
    
    /** @var  Validator */
    private $validator;
    
    /** @var  UserAccountServiceExt */
    private $accountService;

    /** @var  AdminTool */
    private $adminTool;

    /** @var  AssociationsTool */
    private $associationsTool;

    /** @var  OrganizationTool */
    private $organizationTool;

    /** @var  InstructorTool */
    private $instructorTool;

    protected function setUp(){
        $this->validator = $this->createMock(Validator::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->persistenceService = $this->createMock(PersistenceService::class);
        $this->adminTool = $this->createMock(AdminTool::class);
        $this->associationsTool = $this->createMock(AssociationsTool::class);
        $this->organizationTool = $this->createMock(OrganizationTool::class);
        $this->instructorTool = $this->createMock(InstructorTool::class);

        
        /** @var Logger $logger*/
        $logger =  $this->createMock(Logger::class);
        $this->accountService = new UserAccountServiceExt($logger, $this->persistenceService, $this->validator, $this->mailService, $this->adminTool, $this->associationsTool, $this->instructorTool, $this->organizationTool);
    }
    
    public function testFailsOnTooQuick(){
        
        //returns one login attempt but it was only a second ago
        $this->persistenceService->method('getLoginFailedAttempts')->willReturn([new LoginAttempt('1', new \DateTime('-1 second'))]);
        $this->expectException(FastLoginException::class);
        $this->accountService->callCheckBruteForce('enewbury@uvm.edu', '1');
    }
    
    public function testFailsOnIndefinitelyBlockedIp(){
        $this->persistenceService->method('getBlockedIp')->willReturn(new BlockedIp('1', null, 4));
        $this->expectException(IpBlockedForeverException::class);
        $this->accountService->callCheckBruteForce('enewbury@uvm.edu', '1');
    }

    public function testFailsWhenIpTemporarilyBlocked(){
        $this->persistenceService->method('getBlockedIp')->willReturn(new BlockedIp('1', new \DateTime('+1 minute'), 4));
        $this->expectException(IpBlockedTemporarilyException::class);
        $this->accountService->callCheckBruteForce('enewbury@uvm.edu', '1');
    }

    public function testFailsWhenTooManyLoginAttemptsAndBlockIp(){
        $this->persistenceService->method('getLoginFailedAttempts')->willReturn([new LoginAttempt(), new LoginAttempt(), new LoginAttempt(), new LoginAttempt()]);
        
        //check that its 5 minutes for first time blocked
        $this->persistenceService->method('saveBlockedIp')->will($this->returnCallback(function($blockedIp){
            /** @var BlockedIp $blockedIp */
            \PHPUnit_Framework_Assert::assertEquals($blockedIp->getOffenseCount(), 1);
            \PHPUnit_Framework_Assert::assertEquals($blockedIp->getBlockedUntil(), new \DateTime('+5 minutes'));
        }));
        
        $this->expectException(TooManyLoginAttempsException::class);
        $this->accountService->callCheckBruteForce('enewbury@uvm.edu', '1');

        
        //check that its 25 minutes for second time blocked
        $this->persistenceService->method('getBlockedIp')->willReturn(new BlockedIp('1', new \DateTime('-1 minute'), 1));
        $this->persistenceService->method('saveBlockedIp')->will($this->returnCallback(function($blockedIp){
            /** @var BlockedIp $blockedIp */
            \PHPUnit_Framework_Assert::assertEquals($blockedIp->getOffenseCount(), 2);
            \PHPUnit_Framework_Assert::assertEquals($blockedIp->getBlockedUntil(), new \DateTime('+25 minutes'));
        }));

        $this->expectException(TooManyLoginAttempsException::class);
        $this->accountService->callCheckBruteForce('enewbury@uvm.edu', '1');
    }


}
class UserAccountServiceExt extends UserAccountService{
    public function callCheckBruteForce($email, $ip){
        $this->checkBruteForceAttack($email, $ip);
    }
}