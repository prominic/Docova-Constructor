<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * AppForms
 *
 * @ORM\Table(name="tb_app_forms")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AppFormsRepository")
 */
class AppForms
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Form_Name", type="string", length=200)
     */
    protected $formName;

    /**
     * @var string
     *
     * @ORM\Column(name="Form_Alias", type="string", length=255, nullable=true)
     */
    protected $formAlias;

    /**
     * @var string
     *
     * @ORM\Column(name="CSS_Filename", type="string", length=255, nullable=true)
     */
    protected $cSSFilename;

    /**
     * @var string
     *
     * @ORM\Column(name="JS_Filename", type="string", length=255, nullable=true)
     */
    protected $jSFilename;

    /**
     * @var boolean
     *
     * @ORM\Column(name="PDU", type="boolean", options={"comment"="Prohibit Design Update"})
     */
    protected $pDU = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Trash", type="boolean", options={"default":false})
     */
    protected $trash = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime", nullable=true)
     */
    protected $dateCreated;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Modified", type="datetime", nullable=false)
     */
    protected $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id", nullable=true)
     */
    protected $modifiedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries", inversedBy="forms")
     * @ORM\JoinColumn(name="App_Id", referencedColumnName="id", nullable=false)
     */
    protected $application;

    /**
     * @ORM\OneToOne(targetEntity="AppFormProperties", mappedBy="appForm")
     */
    protected $formProperties;

    /**
     * @ORM\OneToOne(targetEntity="AppFormAttProperties", mappedBy="appForm")
     */
    protected $attachmentProp;

    /**
     * @ORM\OneToMany(targetEntity="DesignElements", mappedBy="form")
     */
    protected $elements;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Workflow")
     * @ORM\JoinTable(name="tb_app_form_workflows",
     *      joinColumns={@ORM\JoinColumn(name="Form_Id", referencedColumnName="id", nullable=false)},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Workflow_Id", referencedColumnName="id", nullable=false)})
     */
    protected $formWorkflows;


    public function __construct()
    {
    	$this->formWorkflows = new ArrayCollection();
    }
    

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set formName
     *
     * @param string $formName
     * @return AppForms
     */
    public function setFormName($formName)
    {
        $this->formName = $formName;

        return $this;
    }

    /**
     * Get formName
     *
     * @return string 
     */
    public function getFormName()
    {
        return $this->formName;
    }

    /**
     * Set formAlias
     *
     * @param string $formAlias
     * @return AppForms
     */
    public function setFormAlias($formAlias = null)
    {
        $this->formAlias = $formAlias;

        return $this;
    }

    /**
     * Get formAlias
     *
     * @return string 
     */
    public function getFormAlias()
    {
        return $this->formAlias;
    }

    /**
     * Set cSSFilename
     *
     * @param string $cSSFilename
     * @return AppForms
     */
    public function setCSSFilename($cSSFilename = null)
    {
        $this->cSSFilename = $cSSFilename;

        return $this;
    }

    /**
     * Get cSSFilename
     *
     * @return string 
     */
    public function getCSSFilename()
    {
        return $this->cSSFilename;
    }

    /**
     * Set jSFilename
     *
     * @param string $jSFilename
     * @return AppForms
     */
    public function setJSFilename($jSFilename = null)
    {
        $this->jSFilename = $jSFilename;

        return $this;
    }

    /**
     * Get jSFilename
     *
     * @return string 
     */
    public function getJSFilename()
    {
        return $this->jSFilename;
    }

    /**
     * Set pDU
     *
     * @param boolean $pDU
     * @return AppForms
     */
    public function setPDU($pDU)
    {
        $this->pDU = $pDU;

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
     * Set trash
     *
     * @param boolean $trash
     * @return AppForms
     */
    public function setTrash($trash)
    {
    	$this->trash = $trash;
    
    	return $this;
    }
    
    /**
     * Get trash
     *
     * @return boolean
     */
    public function getTrash()
    {
    	return $this->trash;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return AppForms
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return AppForms
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
     * Set createdBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $createdBy
     * @return AppForms
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $modifiedBy
     * @return AppForms
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

    /**
     * Set application
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $application
     * @return AppForms
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

    /**
     * Get formProperties
     * 
     *  @return \Docova\DocovaBundle\Entity\AppFormProperties
     */
    public function getFormProperties()
    {
    	return $this->formProperties;
    }

    /**
     * Set formProperties
     *
     * @param \Docova\DocovaBundle\Entity\AppFormProperties $formProperties
     * @return AppForms
     */
    public function setFormProperties(\Docova\DocovaBundle\Entity\AppFormProperties $formProperties = null)
    {
    	$this->formProperties = $formProperties;
    
    	return $this;
    }

    /**
     * Get attachmentProp
     * 
     * @return \Docova\DocovaBundle\Entity\AppFormAttProperties
     */
    public function getAttachmentProp()
    {
    	return $this->attachmentProp;
    }

    /**
     * Get elements
     * 
     * @return ArrayCollection
     */
    public function getElements()
    {
    	return $this->elements;
    }

    /**
     * Add elements
     *
     * @param \Docova\DocovaBundle\Entity\DesignElements $elements
     * @return AppForms
     */
    public function addElement(\Docova\DocovaBundle\Entity\DesignElements $elements)
    {
        $this->elements[] = $elements;

        return $this;
    }

    /**
     * Remove elements
     *
     * @param \Docova\DocovaBundle\Entity\DesignElements $elements
     */
    public function removeElement(\Docova\DocovaBundle\Entity\DesignElements $elements)
    {
        $this->elements->removeElement($elements);
    }

    /**
     * Add formWorkflows
     *
     * @param \Docova\DocovaBundle\Entity\Workflow $formWorkflows
     * @return AppForms
     */
    public function addFormWorkflow(\Docova\DocovaBundle\Entity\Workflow $formWorkflows)
    {
        $this->formWorkflows[] = $formWorkflows;

        return $this;
    }

    /**
     * Remove formWorkflows
     *
     * @param \Docova\DocovaBundle\Entity\Workflow $formWorkflows
     */
    public function removeFormWorkflow(\Docova\DocovaBundle\Entity\Workflow $formWorkflows)
    {
        $this->formWorkflows->removeElement($formWorkflows);
    }

    /**
     * Get formWorkflows
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFormWorkflows()
    {
        return $this->formWorkflows;
    }
}
