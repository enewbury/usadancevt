<?php
/**
 * Created by Eric Newbury.
 * Date: 7/9/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use EricNewbury\DanceVT\Util\DateTool;

/** @Entity */
class BlockedIp
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
    protected $ip;

    /**
     * @Column(type="datetime")
     * @var \DateTime $blockedUntil
     */
    protected $blockedUntil;

    /**
     * @Column(type="integer")
     * @var int $offenseCount
     */
    protected $offenseCount;
    
    /**
     * BlockedIp constructor.
     * @param string $ip
     * @param \DateTime $blockedUntil
     * @param int $offenseCount
     */
    public function __construct($ip = null, \DateTime $blockedUntil = null, $offenseCount = 0)
    {
        $this->ip = $ip;
        $this->blockedUntil = $blockedUntil;
        $this->offenseCount = $offenseCount;
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
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return \DateTime
     */
    public function getBlockedUntil()
    {
        return $this->blockedUntil;
    }

    /**
     * @param \DateTime $blockedUntil
     */
    public function setBlockedUntil($blockedUntil)
    {
        $this->blockedUntil = $blockedUntil;
    }

    /**
     * @return int
     */
    public function getOffenseCount()
    {
        return $this->offenseCount;
    }

    /**
     * @param int $offenseCount
     */
    public function setOffenseCount($offenseCount)
    {
        $this->offenseCount = $offenseCount;
    }

    

}