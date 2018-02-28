<?php
/**
 * Created by Eric Newbury.
 * Date: 5/14/16
 */

namespace EricNewbury\DanceVT\Models\Persistence;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

/** @Entity */
class Form
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
     * @OneToMany(targetEntity="FormInput", mappedBy="form")
     * @var FormInput[] $inputs
     */
    protected $inputs;

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
     * @return FormInput[]
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * @param FormInput[] $inputs
     */
    public function setInputs($inputs)
    {
        $this->inputs = $inputs;
    }
    
    

}