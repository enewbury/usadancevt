<?php
/**
 * Created by Eric Newbury.
 * Date: 5/6/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

/** @Entity */
class NavItem
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Page", inversedBy="navItems")
     * @var Page $page
     */
    protected $page;

    /**
     * @Column(type="integer")
     * @var int $pageOrder
     */
    protected $pageOrder;

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
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param Page $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @return int
     */
    public function getPageOrder()
    {
        return $this->pageOrder;
    }

    /**
     * @param int $pageOrder
     */
    public function setPageOrder($pageOrder)
    {
        $this->pageOrder = $pageOrder;
    }

    


}