<?php
/**
 * Created by Eric Newbury.
 * Date: 5/6/16
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
class Page
{

    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Template", inversedBy="components")
     * @var Template
     */
    protected $template;

    /**
     * @Column(type="string")
     * @var string $name
     */
    protected $name;

    /**
     * @Column(type="string")
     * @var string $url
     */
    protected $url;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $active;
    

    /**
     * @OneToMany(targetEntity="PageComponent", mappedBy="page")
     * @var PageComponent[] $components
     */
    protected $components;

    /**
     * @OneToMany(targetEntity="NavItem", mappedBy="page")
     * @var NavItem[] $navItems
     */
    protected $navItems;

    public function __construct()
    {
        $this->navItems = new ArrayCollection();
        $this->active = 0;
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
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param Template $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
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
     * @return PageComponent[]
     */
    public function getComponents()
    {
        $components = $this->components->toArray();
        usort($components, function (PageComponent $a, PageComponent $b) {
            if($a == null || $b == null) return 0;
            return strcmp($a->getTemplateComponent()->getOrder(), $b->getTemplateComponent()->getOrder());
        });

        return $this->components;
    }

    /**
     * @param PageComponent $component
     */
    public function addComponent($component)
    {
        $this->components[] = $component;
    }

    public function getComponent($templateComponent){
        foreach($this->components as $component){
            if($component->getTemplateComponent()->getId() == $templateComponent->getId()){
                return $component;
            }
        }
    }

    /**
     * @return NavItem[]
     */
    public function getNavItems()
    {
        return $this->navItems;
    }

    /**
     * @param NavItem[] $navItems
     */
    public function setNavItems($navItems)
    {
        $this->navItems = $navItems;
    }

    
}