<?php
/**
 * Created by Eric Newbury.
 * Date: 5/6/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

/** @Entity */
class Template
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
     * @var string $templateLink
     */
    protected $templateLink;

    /**
     * @Column(type="string")
     * @var string $imageLink
     */
    protected $imageLink;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $locked;

    /**
     * @OneToMany(targetEntity="TemplateComponent", mappedBy="template")
     * @var TemplateComponent[] $components
     */
    protected $components;

    /**
     * Template constructor.
     */
    public function __construct()
    {
        $this->locked = 0;
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
    public function getTemplateLink()
    {
        return $this->templateLink;
    }

    /**
     * @param string $templateLink
     */
    public function setTemplateLink($templateLink)
    {
        $this->templateLink = $templateLink;
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
     * @return bool
     */
    public function isLocked()
    {
        return ($this->locked === 1);
    }

    /**
     * @param bool $locked
     */
    public function setLocked($locked)
    {
        $this->locked = ($locked === true) ? 1 : 0;
    }

    /**
     * @return mixed
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * @param mixed $components
     */
    public function setComponents($components)
    {
        $this->components = $components;
    }
    
    
    
}