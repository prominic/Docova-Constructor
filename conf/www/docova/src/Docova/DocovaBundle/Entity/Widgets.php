<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Widgets
 *
 * @ORM\Table(name="tb_widgets")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\WidgetsRepository")
 */
class Widgets
{
	/**
	 * @ORM\Column(name="id", type="guid")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="UUID")
	 */
	protected $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="Widget_Name", type="string", length=100)
	 */
	protected $widgetName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="Subform_Name", type="string", length=255)
	 */
	protected $subformName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="Subform_Alias", type="string", length=255, nullable=true)
	 */
	protected $subformAlias;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="Description", type="string", length=255, nullable=true)
	 */
	protected $description;

	/**
	 * @var boolean
	 * 
	 * @ORM\Column(name="Is_Custom", type="boolean", options={"default":true})
	 */
	protected $isCustom = true;

	/**
	 * @var boolean
	 * 
	 * @ORM\Column(name="Inactive", type="boolean", options={"default":false})
	 */
	protected $inactive = false;

	/**
	 * @var \DateTime
	 * 
	 * @ORM\Column(name="Date_Modified", type="datetime", nullable=true)
	 */
	protected $dateModified;

	/**
	 * @ORM\ManyToOne(targetEntity="UserAccounts")
	 * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id", nullable=true)
	 */
	protected $modifiedBy;

	/**
	 * @var ArrayCollection
	 * 
	 * @ORM\OneToMany(targetEntity="PanelWidgets", mappedBy="widget")
	 */
	protected $panelWidgets;


	/**
	 * Get id
	 *
	 * @return string 
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set widgetName
	 *
	 * @param string $widgetName
	 * @return Widgets
	 */
	public function setWidgetName($widgetName)
	{
		$this->widgetName = $widgetName;
	
		return $this;
	}

	/**
	 * Get widgetName
	 *
	 * @return string 
	 */
	public function getWidgetName()
	{
		return $this->widgetName;
	}

	/**
	 * Set subformName
	 *
	 * @param string $subformName
	 * @return Widgets
	 */
	public function setSubformName($subformName)
	{
		$this->subformName = $subformName;
	
		return $this;
	}

	/**
	 * Get subformName
	 *
	 * @return string 
	 */
	public function getSubformName()
	{
		return $this->subformName;
	}

	/**
	 * Set subformAlias
	 *
	 * @param string $subformAlias
	 * @return Widgets
	 */
	public function setSubformAlias($subformAlias)
	{
		$this->subformAlias = $subformAlias;
	
		return $this;
	}

	/**
	 * Get subformAlias
	 *
	 * @return string 
	 */
	public function getSubformAlias()
	{
		return $this->subformAlias;
	}

	/**
	 * Set description
	 *
	 * @param string $description
	 * @return Widgets
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	
		return $this;
	}

	/**
	 * Get description
	 *
	 * @return string 
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set isCustom
	 *
	 * @param boolean $isCustom
	 * @return Widgets
	 */
	public function setIsCustom($isCustom)
	{
		$this->isCustom = $isCustom;
	
		return $this;
	}
	
	/**
	 * Get isCustom
	 *
	 * @return boolean
	 */
	public function getIsCustom()
	{
		return $this->isCustom;
	}

	/**
	 * Set inactive
	 *
	 * @param boolean $inactive
	 * @return Widgets
	 */
	public function setInactive($inactive)
	{
		$this->inactive = $inactive;
	
		return $this;
	}
	
	/**
	 * Get inactive
	 *
	 * @return boolean
	 */
	public function getInactive()
	{
		return $this->inactive;
	}

	/**
	 * Get panelWidgets
	 * 
	 * @return ArrayCollection
	 */
	public function getPanelWidgets()
	{
		return $this->panelWidgets;
	}

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     *
     * @return Widgets
     */
    public function setDateModified($dateModified)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified
     *
     * @return \DateTime
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Set modifiedBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $modifiedBy
     *
     * @return Widgets
     */
    public function setModifiedBy(\Docova\DocovaBundle\Entity\UserAccounts $modifiedBy = null)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }
}
