<?php
/**
 * Created by enewbury.
 * Date: 10/27/15
 */

namespace EricNewbury\DanceVT\Models\Persistence;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * @Entity
 */
class Token
{

    const VERIFICATION = "VERIFICATION";
    const PASS_RESET = "PASS_RESET";

    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="tokens")
     * @var User user
     */
    protected $user;

    /**
     * @Column(type="string")
     * @var string type
     */
    protected $type;

    /**
     * @Column(type="string")
     * @var string token
     */
    protected $token;

    /**
     * @Column(type="datetime")
     * @var DateTime expireDate
     */
    protected $expireDate;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $user->addToken($this);
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return DateTime
     */
    public function getExpireDate()
    {
        return $this->expireDate;
    }

    /**
     * @param DateTime $expireDate
     */
    public function setExpireDate($expireDate)
    {
        $this->expireDate = $expireDate;
    }


}