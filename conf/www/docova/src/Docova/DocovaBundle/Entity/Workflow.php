<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Workflow
 *
 * @ORM\Table(name="tb_workflow")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\WorkflowRepository")
 */
class Workflow
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
     * @ORM\Column(name="Workflow_Name", type="string", length=255)
     */
    protected $Workflow_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255, nullable=true)
     */
    protected $Description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Author_Customization", type="boolean")
     */
    protected $Author_Customization = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Bypass_Rleasing", type="boolean")
     */
    protected $Bypass_Rleasing = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Use_Default_Doc", type="boolean")
     */
    protected $Use_Default_Doc = false ;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Modified", type="datetime", nullable=true)
     */
    protected $Date_Modified;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean")
     */
    protected $Trash = false;
    
    /**
     * @ORM\OneToMany(targetEntity="WorkflowSteps", mappedBy="Workflow")
     * @ORM\OrderBy({"Position" = "ASC"})
     * @var ArrayCollection
     */
    protected $Steps;
    
    /**
     * @ORM\ManyToMany(targetEntity="DocumentTypes", mappedBy="DocTypeWorkflow")
     * @var ArrayCollection
     */
    protected $DocType;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="App_Id", referencedColumnName="id", nullable=true)
     */
    protected $application;
    

    public function __construct()
    {
    	$this->Steps = new ArrayCollection();
    }
    
    
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
     * Set Workflow_Name
     *
     * @param string $workflowName
     * @return Workflow
     */
    public function setWorkflowName($workflowName)
    {
        $this->Workflow_Name = $workflowName;
    
        return $this;
    }

    /**
     * Get Workflow_Name
     *
     * @return string 
     */
    public function getWorkflowName()
    {
        return $this->Workflow_Name;
    }

    /**
     * Set Description
     *
     * @param string $description
     * @return Workflow
     */
    public function setDescription($description = null)
    {
        $this->Description = $description;
    
        return $this;
    }

    /**
     * Get Description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * Set Author_Customization
     *
     * @param boolean $authorCustomization
     * @return Workflow
     */
    public function setAuthorCustomization($authorCustomization)
    {
        $this->Author_Customization = $authorCustomization;
    
        return $this;
    }

    /**
     * Get Author_Customization
     *
     * @return boolean 
     */
    public function getAuthorCustomization()
    {
        return $this->Author_Customization;
    }

    /**
     * Set Bypass_Rleasing
     *
     * @param boolean $bypassRleasing
     * @return Workflow
     */
    public function setBypassRleasing($bypassRleasing)
    {
        $this->Bypass_Rleasing = $bypassRleasing;
    
        return $this;
    }

    /**
     * Get Bypass_Rleasing
     *
     * @return boolean 
     */
    public function getBypassRleasing()
    {
        return $this->Bypass_Rleasing;
    }

    /**
     * Set Use_Default_Doc
     *
     * @param boolean $useDefaultDoc
     * @return Workflow
     */
    public function setUseDefaultDoc($useDefaultDoc)
    {
        $this->Use_Default_Doc = $useDefaultDoc;
    
        return $this;
    }

    /**
     * Get Use_Default_Doc
     *
     * @return boolean 
     */
    public function getUseDefaultDoc()
    {
        return $this->Use_Default_Doc;
    }

    /**
     * Set Date_Modified
     * 
     * @param \DateTime $dateModified
     * @return Workflow
     */
    public function setDateModified($dateModified)
    {
    	$this->Date_Modified = $dateModified;
    	
    	return $this;
    }

    /**
     * Get Date_Modified
     * @return \DateTime
     */
    public function getDateModified()
    {
    	return $this->Date_Modified;
    }
    /**
     * Set Trash
     *
     * @param boolean $trash
     * @return Workflow
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
     * Set Steps
     * 
     * @param \Docova\DocovaBundle\Entity\WorkflowSteps $steps
     */
    public  function addSteps(\Docova\DocovaBundle\Entity\WorkflowSteps $steps)
    {
    	$this->Steps[] = $steps;
    }

    /**
     * Get Steps
     * 
     * @return ArrayCollection
     */
    public function getSteps()
    {
    	return $this->Steps;
    }

    /**
     * Get Doctype
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDoctype()
    {
    	return $this->DocType;
    }

    /**
     * Set application
     * 
     * @param \Docova\DocovaBundle\Entity\Libraries $app
     * @return Workflow
     */
    public function setApplication(\Docova\DocovaBundle\Entity\Libraries $app = null)
    {
    	$this->application = $app;
    	
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
