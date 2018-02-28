<?php
/**
 * Created by Eric Newbury.
 * Date: 5/6/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
class GlobalPageComponent
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @Column(type="string")
     * @var string $name
     */
    protected $name;

    /**
     * @Column(type="string")
     * @var string $slug
     */
    protected $slug;

    /**
     * @Column(type="string")
     * @var string $value
     */
    protected $value;

    /**
     * @Column(type="string")
     * @var string $type
     */
    protected $type;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $order;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $span;

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
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
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
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return int
     */
    public function getSpan()
    {
        return $this->span;
    }

    /**
     * @param int $span
     */
    public function setSpan($span)
    {
        $this->span = $span;
    }
    
    
}