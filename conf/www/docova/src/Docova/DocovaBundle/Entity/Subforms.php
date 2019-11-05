<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Subforms
 *
 * @ORM\Table(name="tb_subforms")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\SubformsRepository")
 */
class Subforms
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
     * @ORM\Column(name="Form_Name", type="string", length=255)
     */
    protected $Form_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Form_File_Name", type="string", length=255)
     */
    protected $Form_File_Name;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $Creator;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $Date_Created;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id", nullable=false)
     */
    protected $Modified_By;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Modified", type="datetime", nullable=true)
     */
    protected $Date_Modified;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean", nullable=false)
     */
    protected $Trash = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Is_Custom", type="boolean")
     */
    protected $Is_Custom = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="PDU", type="boolean", options={"comment"="Prohibit Design Update"})
     */
    protected $pDU = false;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries", inversedBy="subforms")
     * @ORM\JoinColumn(name="App_Id", referencedColumnName="id", nullable=true)
     */
    protected $application;

    /**
     * @ORM\OneToMany(targetEntity="DocTypeSubforms", mappedBy="Subform")
     * @var ArrayCollection
     */
    protected $DocType;
    
    /**
     * @ORM\OneToMany(targetEntity="DesignElements", mappedBy="Subform")
     * @var ArrayCollection
     */
    protected $SubformFields;
    
    /**
     * @ORM\OneToMany(targetEntity="SubformActionButtons", mappedBy="Subform")
     * @var ArrayCollection
     */
    protected $Action_Buttons;


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
     * Set Form_Name
     *
     * @param string $formName
     * @return Subforms
     */
    public function setFormName($formName)
    {
        $this->Form_Name = $formName;
    
        return $this;
    }

    /**
     * Get Form_Name
     *
     * @return string 
     */
    public function getFormName()
    {
        return $this->Form_Name;
    }

    /**
     * Set Form_File_Name
     *
     * @param string $formFileName
     * @return Subforms
     */
    public function setFormFileName($formFileName)
    {
    	$this->Form_File_Name = $formFileName;
    
    	return $this;
    }
    
    /**
     * Get Form_File_Name
     *
     * @return string
     */
    public function getFormFileName()
    {
    	return $this->Form_File_Name;
    }

    /**
     * Set Creator
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $Creator
     */
    public function setCreator(\Docova\DocovaBundle\Entity\UserAccounts $Creator)
    {
    	$this->Creator = $Creator;
    }
    
    /**
     * Get Creator
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts $Creator
     */
    public function getCreator()
    {
    	return $this->Creator;
    }
        
    /**
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return Subforms
     */
    public function setDateCreated($dateCreated)
    {
        $this->Date_Created = $dateCreated;
    
        return $this;
    }

    /**
     * Get Date_Created
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->Date_Created;
    }
	
	/**
	 * Get Modified_By
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserAccounts
	 */
	public function getModifiedBy() 
	{
		return $this->Modified_By;
	}
	
	/**
	 * Set Modified_By
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $Modified_By
	 */
	public function setModifiedBy(\Docova\DocovaBundle\Entity\UserAccounts $Modified_By) 
	{
		$this->Modified_By = $Modified_By;
	}
	
	/**
	 * Get Date_Modified
	 * 
	 * @return \DateTime
	 */
	public function getDateModified() 
	{
		return $this->Date_Modified;
	}
	
	/**
	 * Set Date_Modified
	 * 
	 * @param \DateTime $Date_Modified
	 * @return Subforms        	
	 */
	public function setDateModified($Date_Modified) 
	{
		$this->Date_Modified = $Date_Modified;
		
		return $this;
	}
	
    /**
     * Set Trash
     *
     * @param boolean $trash
     * @return Subforms
     */
    public function setTrash($trash)
    {
        $this->Trash = $trash;
    
        return $this;
    }

    /**
     * Get Trash
     *
     * @return boolean 
     */
    public function getTrash()
    {
        return $this->Trash;
    }
    
    /**
     * Add DocType
     * 
     * @param Docova\DocovaBundle\Entity\DocumentTypes $doctype
     */
/*    public function addDocType(\Docova\DocovaBundle\Entity\DocumentTypes $doctype)
    {
    	$this->DocType[] = $doctype;
    }
*/    
    /**
     * Get DocType
     * 
     * @return ArrayCollection
     */
    public function getDocType()
    {
    	return $this->DocType;
    }
    
    /**
     * Add SubformFields
     * 
     * @param \Docova\DocovaBundle\Entity\DesignElements $subformFields
     */
    public function addSubformFields(\Docova\DocovaBundle\Entity\DesignElements $subformFields)
    {
    	$this->SubformFields[] = $subformFields;
    }
    
    /**
     * Get SubformFields
     * 
     * @return ArrayCollection
     */
    public function getSubformFields()
    {
    	return $this->SubformFields;
    }

    /**
     * Add Action_Buttons
     * 
     * @param \Docova\DocovaBundle\Entity\SubformActionButtons $action_button
     */
    public function addActionButtons(\Docova\DocovaBundle\Entity\SubformActionButtons $action_button)
    {
    	$this->Action_Buttons[] = $action_button;
    }

    /**
     * Get Action_Buttons
     * 
     * @return ArrayCollection
     */
    public function getActionButtons()
    {
    	return $this->Action_Buttons;
    }
	
	/**
	 * Get Is_Custom
	 * 
	 * @return boolean
	 */
	public function getIsCustom() 
	{
		return $this->Is_Custom;
	}
	
	/**
	 * Set Is_Custom
	 * 
	 * @param boolean $Is_Custom
	 * @return Subforms
	 */
	public function setIsCustom($Is_Custom) 
	{
		$this->Is_Custom = $Is_Custom;
		return $this;
	}

	/**
	 * Get pDU
	 * 
	 * @return boolean
	 */
	public function getPDU() 
	{
		return $this->pDU;
	}

	/**
	 * Set pDU
	 * 
	 * @param boolean $pDU
	 * @return Subforms
	 */
	public function setPDU($pDU) 
	{
		$this->pDU = $pDU;
		return $this;
	}

	/**
	 * Set application
	 *
	 * @param \Docova\DocovaBundle\Entity\Libraries $application
	 * @return Subforms
	 */
	public function setApplication(\Docova\DocovaBundle\Entity\Libraries $application)
	{
		$this->application = $application;
	
		return $this;
	}
	
	/**
	 * Get application
	 *
	 * @return \Docova\DocovaBundle\Entity\Libraries
	 */
	public function getApplication()
	{
		return $this->application;
	}
}