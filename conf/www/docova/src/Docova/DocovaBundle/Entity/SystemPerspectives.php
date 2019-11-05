<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SystemPerspectives
 *
 * @ORM\Table(name="tb_system_perspectives")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\SystemPerspectivesRepository")
 */
class SystemPerspectives
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
     * @ORM\Column(name="Perspective_Name", type="string", length=255)
     */
    protected $Perspective_Name;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Is_System", type="boolean", nullable=false)
     */
    protected $Is_System = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255)
     */
    protected $Description;

    /**
     * @ORM\ManyToOne(targetEntity="FacetMaps")
     * @ORM\JoinColumn(name="Facet_Map", referencedColumnName="id", nullable=false)
     */
    protected $Facet_Map;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="Visibility", type="string", length=255, nullable=true)
     */
    protected $Visibility;

    /**
     * @var string
     *
     * @ORM\Column(name="Default_For", type="string", length=50, nullable=true)
     */
    protected $Default_For;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Collapse_First", type="boolean")
     */
    protected $Collapse_First = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Available_For_Folders", type="boolean")
     */
    protected $Available_For_Folders = false;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="Library_Id", referencedColumnName="id", nullable=true)
     */
    protected $Library;

    /**
     * @ORM\ManyToOne(targetEntity="Folders")
     * @ORM\JoinColumn(name="Built_In_Folder", referencedColumnName="id", nullable=true)
     */
    protected $Built_In_Folder;

    /**
     * @var string
     *
     * @ORM\Column(name="xml_Structure", type="text")
     */
    protected $xml_Structure;

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
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id")
     */
    protected $Modifier;
    
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
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Perspective_Name
     *
     * @param string $perspectiveName
     * @return SystemPerspectives
     */
    public function setPerspectiveName($perspectiveName)
    {
        $this->Perspective_Name = $perspectiveName;
    
        return $this;
    }

    /**
     * Get Perspective_Name
     *
     * @return string 
     */
    public function getPerspectiveName()
    {
        return $this->Perspective_Name;
    }

    /**
     * Set Is_System
     * 
     * @param boolean $isSystem
     * @return SystemPerspectives
     */
    public function setIsSystem($isSystem)
    {
    	$this->Is_System = $isSystem;
    	
    	return $this;
    }

    /**
     * Get Is_System
     * 
     * @return boolean
     */
    public function getIsSystem()
    {
    	return $this->Is_System;
    }

    /**
     * Set Description
     *
     * @param string $description
     * @return SystemPerspectives
     */
    public function setDescription($description)
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
     * Set Facet_Map
     *
     * @param \Docova\DocovaBundle\Entity\FacetMaps $facetMap
     * @return SystemPerspectives
     */
    public function setFacetMap(\Docova\DocovaBundle\Entity\FacetMaps $facetMap)
    {
        $this->Facet_Map = $facetMap;
    
        return $this;
    }

    /**
     * Get Facet_Map
     *
     * @return \Docova\DocovaBundle\Entity\FacetMaps
     */
    public function getFacetMap()
    {
        return $this->Facet_Map;
    }

    /**
     * Set Visibility
     * 
     * @param string $visibility
     * @return SystemPerspectives
     */
    public function setVisibility($visibility)
    {
    	$this->Visibility = $visibility;
    	
    	return $this;
    }

    /**
     * Get Visibility
     * 
     * @return string
     */
    public function getVisibility()
    {
    	return $this->Visibility;
    }

    /**
     * Set Default_For
     *
     * @param string $defaultFor
     * @return SystemPerspectives
     */
    public function setDefaultFor($defaultFor)
    {
        $this->Default_For = $defaultFor;
    
        return $this;
    }

    /**
     * Get Default_For
     *
     * @return string 
     */
    public function getDefaultFor()
    {
        return $this->Default_For;
    }

    /**
     * Set Collapse_First
     * 
     * @param boolean $collapseFirst
     * @return SystemPerspectives
     */
    public function setCollapseFirst($collapseFirst)
    {
    	$this->Collapse_First = $collapseFirst;
    	
    	return $this;
    }

    /**
     * Get Collapse_First
     * 
     * @return boolean
     */
    public function getCollapseFirst()
    {
    	return $this->Collapse_First;
    }

    /**
     * Set Available_For_Folders
     *
     * @param boolean $isAvailable
     * @return SystemPerspectives
     */
    public function setAvailableForFolders($isAvailable)
    {
    	$this->Available_For_Folders = $isAvailable;
    	 
    	return $this;
    }
    
    /**
     * Get Available_For_Folders
     *
     * @return boolean
     */
    public function getAvailableForFolders()
    {
    	return $this->Available_For_Folders;
    }

    /**
     * Set Library
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $library
     */
    public function setLibrary(\Docova\DocovaBundle\Entity\Libraries $library)
    {
    	$this->Library = $library;
    }
    
    /**
     * Get Library
     *
     * @return \Docova\DocovaBundle\Entity\Libraries
     */
    public function getLibrary()
    {
    	return $this->Library;
    }

    /**
     * Set Built_In_Folder
     * 
     * @param \Docova\DocovaBundle\Entity\Folders|null $folder
     */
    public function setBuiltInFolder(\Docova\DocovaBundle\Entity\Folders $folder = null)
    {
    	$this->Built_In_Folder = $folder;
    }

    /**
     * Get Built_In_Folder
     * 
     * @return \Docova\DocovaBundle\Entity\Folders
     */
    public function getBuiltInFolder()
    {
    	return $this->Built_In_Folder;
    }

    /**
     * Set xml_Structure
     *
     * @param string $xmlStructure
     * @return SystemPerspectives
     */
    public function setXmlStructure($xmlStructure)
    {
        $this->xml_Structure = $xmlStructure;
    
        return $this;
    }

    /**
     * Get xml_Structure
     *
     * @return string 
     */
    public function getXmlStructure()
    {
        return $this->xml_Structure;
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
     * @param \Docova\DocovaBundle\Entity\UserAccounts $Creator
     */
    public function getCreator()
    {
    	return $this->Creator;
    }
    
    /**
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return SystemPerspectives
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
     * Set Modifier
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $Modifier
     */
    public function setModifier(\Docova\DocovaBundle\Entity\UserAccounts $Modifier)
    {
    	$this->Modifier = Modifier;
    }
    
    /**
     * Get Modifier
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $Modifier
     */
    public function getModifier()
    {
    	return $this->Modifier;
    }
    
    /**
     * Set Date_Modified
     *
     * @param \DateTime $dateModified
     * @return SystemPerspectives
     */
    public function setDateModified($dateModified)
    {
        $this->Date_Modified = $dateModified;
    
        return $this;
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
     * Set Trash
     *
     * @param boolean $trash
     * @return SystemPerspectives
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
}
