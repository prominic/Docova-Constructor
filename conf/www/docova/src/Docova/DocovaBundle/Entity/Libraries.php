<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Libraries
 *
 * @ORM\Table(name="tb_libraries")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\LibrariesRepository")
 */
class Libraries
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Public_Access_Enabled", type="boolean", nullable=true )
     */
    protected $Public_Access_Enabled;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Public_Access_Expiration", type="smallint", nullable=true )
     */
    protected $Public_Access_Expiration;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Load_Docs_As_Folders", type="boolean", nullable=true)
     */
    protected $LoadDocsAsFolders;

    /**
     * @var string
     *
     * @ORM\Column(name="Library_Title", type="string", length=150)
     */
    protected $Library_Title;

    /**
     * @var string
     *
     * @ORM\Column(name="Host_Name", type="string", length=255)
     */
    protected $Host_Name;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Is_App", type="boolean")
     */
    protected $isApp;

    /**
     * @var string
     * 
     * @ORM\Column(name="App_Icon", type="string", length=150, nullable=true)
     */
    protected $appIcon;

    /**
     * @var string
     * 
     * @ORM\Column(name="App_Icon_Color", type="string", length=50, nullable=true)
     */
    protected $appIconColor;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="App_Inherit", type="boolean", options={"default" : false})
     */
    protected $appInherit = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Inherit_Design_From", type="string", length=50, nullable=true)
     */
    protected $inheritDesignFrom;


    /**
     * @var boolean
     * 
     * @ORM\Column(name="Status", type="boolean")
     */
    protected $Status = true;

    /**
     * @var string
     * @ORM\Column(name="Source_Template", type="string", length=50, nullable=true)
     */
    protected $Source_Template;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Is_Template", type="boolean", options={"default":false})
     */
    protected $Is_Template = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Recycle_Retention", type="smallint", nullable=true)
     */
    protected $Recycle_Retention;

    /**
     * @var integer
     *
     * @ORM\Column(name="Archive_Retention", type="smallint", nullable=true)
     */
    protected $Archive_Retention;

    /**
     * @var integer
     *
     * @ORM\Column(name="DocLog_Retention", type="smallint", nullable=true)
     */
    protected $DocLog_Retention;

    /**
     * @var integer
     *
     * @ORM\Column(name="Event_Log_Retention", type="smallint", nullable=true)
     */
    protected $Event_Log_Retention;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255, nullable=true)
     */
    protected $Description;

    /**
     * @var string
     *
     * @ORM\Column(name="Community", type="string", length=255, nullable=true)
     */
    protected $Community;

    /**
     * @var string
     * 
     * @ORM\Column(name="Realm", type="string", length=255, nullable=true)
     */
    protected $Realm;

    /**
     * @var string
     *
     * @ORM\Column(name="Compare_File_Source", type="string", length=255, nullable=true)
     */
    protected $Compare_File_Source;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="PDF_Creator_Required", type="boolean")
     */
    protected $PDF_Creator_Required = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Member_Assignment", type="boolean", nullable=true)
     */
    protected $Member_Assignment;

    /**
     * @var string
     * 
     * @ORM\Column(name="Library_Domain_Org_Unit", type="string", length=255, nullable=true)
     */
    protected $Library_Domain_Org_Unit;

    /**
     * @var string
     * 
     * @ORM\Column(name="Comments", type="text", nullable=true)
     */
    protected $Comments;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $Date_Created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Updated", type="datetime", nullable=true)
     */
    protected $Date_Updated;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Trash", type="boolean", options={"default": false})
     */
    protected $Trash = false;

    /**
     * @ORM\ManyToOne(targetEntity="SystemPerspectives")
     * @ORM\JoinColumn(name="Default_Perspective", referencedColumnName="id", nullable=true)
     */
    protected $Default_Perspective;

    /**
     * @ORM\OneToMany(targetEntity="Folders",mappedBy="Library")
     * @var ArrayCollection
     */
    protected $folders;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="DocumentTypes", inversedBy="ApplicableLibrary")
     * @ORM\JoinTable(name="tb_library_doctypes",
     *      joinColumns={@ORM\JoinColumn(name="Library_Id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Doc_Type_Id", referencedColumnName="id", onDelete="CASCADE")})
     * @ORM\OrderBy({"Doc_Name"="ASC"})
     */
    protected $ApplicableDocType;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="SavedSearches", mappedBy="libraries")
     */
    protected $savedSearches;

    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppLayout", mappedBy="application")
     */
    protected $layouts;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppForms", mappedBy="application")
     */
    protected $forms;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppViews", mappedBy="application")
     */
    protected $views;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppPages", mappedBy="application")
     */
    protected $pages;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Subforms", mappedBy="application")
     */
    protected $subforms;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppOutlines", mappedBy="application")
     */
    protected $outlines;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppJavaScripts", mappedBy="application")
     */
    protected $jScripts;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppAgents", mappedBy="application")
     */
    protected $agents;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppPhpScripts", mappedBy="application")
     */
    protected $phpScripts;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppFiles", mappedBy="application")
     */
    protected $files;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppCss", mappedBy="application")
     */
    protected $csses;

    
    /**
     * @var string
     *
     * @ORM\Column(name="Mobile_Launch_Type", type="string", length=50, nullable=true)
     */
    protected $Mobile_Launch_Type;

    /**
     * @var string
     *
     * @ORM\Column(name="Mobile_Launch_Id", type="string", length=150, nullable=true)
     */
    protected $Mobile_Launch_Id;
    
    
    public function __construct()
    {
    	$this->Date_Created = new \DateTime();
    	$this->folders = new ArrayCollection();
    	$this->ApplicableDocType = new ArrayCollection();
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
     * Set Library_Title
     *
     * @param string $libraryTitle
     * @return Libraries
     */
    public function setLibraryTitle($libraryTitle)
    {
        $this->Library_Title = $libraryTitle;
    
        return $this;
    }

    /**
     * Get Library_Title
     *
     * @return string 
     */
    public function getLibraryTitle()
    {
        return $this->Library_Title;
    }

    /**
     * Set Host_Name
     *
     * @param string $hostName
     * @return Libraries
     */
    public function setHostName($hostName)
    {
        $this->Host_Name = $hostName;
    
        return $this;
    }

    /**
     * Get Host_Name
     *
     * @return string 
     */
    public function getHostName()
    {
        return $this->Host_Name;
    }

    /**
     * Set Status
     * 
     * @param boolean $status
     * @return Libraries
     */
    public function setStatus($status)
    {
    	$this->Status = $status;

    	return $this;
    }

    /**
     * Get Status
     * 
     * @return boolean
     */
    public function getStatus()
    {
    	return $this->Status;
    }

    /**
     * Set Source_Template
     * 
     * @param string $sourceTemplate
     * @return Libraries
     */
    public function setSourceTemplate($sourceTemplate = null)
    {
    	$this->Source_Template = $sourceTemplate;
    	
    	return $this;
    }

	/**
	 * Get Source_Template
	 * 
	 * @return string 
	 */
	public function getSourceTemplate()
    {
    	return $this->Source_Template;
	}

	/**
	 * Set Is_Template
	 * 
	 * @param boolean $isTemplate
	 * @return Libraries
	 */
	public function setIsTemplate($isTemplate)
	{
		$this->Is_Template = $isTemplate;
		
		return $this;
	}

	/**
	 * Get Is_Template
	 * 
	 * @return boolean
	 */
	public function getIsTemplate()
	{
		return $this->Is_Template;
	}

    /**
     * Set Recycle_Retention
     *
     * @param integer $recycleRetention
     * @return Libraries
     */
    public function setRecycleRetention($recycleRetention)
    {
        $this->Recycle_Retention = $recycleRetention;
    
        return $this;
    }

    /**
     * Get Recycle_Retention
     *
     * @return integer 
     */
    public function getRecycleRetention()
    {
        return $this->Recycle_Retention;
    }

    /**
     * Set Archive_Retention
     *
     * @param integer $archiveRetention
     * @return Libraries
     */
    public function setArchiveRetention($archiveRetention)
    {
        $this->Archive_Retention = $archiveRetention;
    
        return $this;
    }

    /**
     * Get Archive_Retention
     *
     * @return integer 
     */
    public function getArchiveRetention()
    {
        return $this->Archive_Retention;
    }

    /**
     * Set DocLog_Retention
     *
     * @param integer $docLogRetention
     * @return Libraries
     */
    public function setDocLogRetention($docLogRetention)
    {
        $this->DocLog_Retention = $docLogRetention;
    
        return $this;
    }

    /**
     * Get DocLog_Retention
     *
     * @return integer 
     */
    public function getDocLogRetention()
    {
        return $this->DocLog_Retention;
    }

    /**
     * Set Event_Log_Retention
     *
     * @param integer $eventLogRetention
     * @return Libraries
     */
    public function setEventLogRetention($eventLogRetention)
    {
        $this->Event_Log_Retention = $eventLogRetention;
    
        return $this;
    }

    /**
     * Get Event_Log_Retention
     *
     * @return integer 
     */
    public function getEventLogRetention()
    {
        return $this->Event_Log_Retention;
    }

    /**
     * Set Description
     *
     * @param string $description
     * @return Libraries
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
     * Set Community
     *
     * @param string $community
     * @return Libraries
     */
    public function setCommunity($community)
    {
        $this->Community = $community;
    
        return $this;
    }

    /**
     * Get Community
     *
     * @return string 
     */
    public function getCommunity()
    {
        return $this->Community;
    }

    /**
     * Set Realm
     *
     * @param string $realm
     * @return Libraries
     */
    public function setRealm($realm)
    {
    	$this->Realm = $realm;
    
    	return $this;
    }
    
    /**
     * Get Realm
     *
     * @return string
     */
    public function getRealm()
    {
    	return $this->Realm;
    }

    /**
     * Set Compare_File_Source
     *
     * @param string $compareFileSource
     * @return Libraries
     */
    public function setCompareFileSource($compareFileSource)
    {
        $this->Compare_File_Source = $compareFileSource;
    
        return $this;
    }

    /**
     * Get Compare_File_Source
     *
     * @return string 
     */
    public function getCompareFileSource()
    {
        return $this->Compare_File_Source;
    }

    /**
     * Set PDF_Creator_Required
     *
     * @param boolean $PDFCreatorRequired
     * @return Libraries
     */
    public function setPDFCreatorRequired($pdfCreatorRequired)
    {
    	$this->PDF_Creator_Required = $pdfCreatorRequired;
    
    	return $this;
    }
    
    /**
     * Get PDF_Creator_Required
     *
     * @return boolean
     */
    public function getPDFCreatorRequired()
    {
    	return $this->PDF_Creator_Required;
    }

    /**
     * Set Comments
     * 
     * @param string $comments
     * @return Libraries
     */
    public function setComments($comments)
    {
    	$this->Comments = $comments;
    	
    	return $this;
    }

    /**
     * Get Comments
     * 
     * @return string
     */
    public function getComments()
    {
    	return $this->Comments;
    }
    
    /**
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return Libraries
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
     * Set Date_Updated
     *
     * @param \DateTime $dateUpdated
     * @return Libraries
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->Date_Updated = $dateUpdated;
    
        return $this;
    }

    /**
     * Get Date_Updated
     *
     * @return \DateTime 
     */
    public function getDateUpdated()
    {
        return $this->Date_Updated;
    }

    /**
     * Set Trash
     *
     * @param boolean $trash
     * @return Libraries
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
     * Set Default_Perspective
     * 
     * @param \Docova\DocovaBundle\Entity\SystemPerspectives $systemPerspective
     */
    public function setDefaultPerspective(\Docova\DocovaBundle\Entity\SystemPerspectives $systemPerspective = null)
    {
    	$this->Default_Perspective = $systemPerspective;
    }

    /**
     * Get Default_Perspective
     * 
     * @return \Docova\DocovaBundle\Entity\SystemPerspectives
     */
    public function getDefaultPerspective()
    {
    	return $this->Default_Perspective;
    }

    public function getPublicAccessEnabled() {
    	return $this->Public_Access_Enabled;
    }
    public function setPublicAccessEnabled($Public_Access_Enabled) {
    	$this->Public_Access_Enabled = $Public_Access_Enabled;
    	return $this;
    }
    public function getPublicAccessExpiration() {
    	return $this->Public_Access_Expiration;
    }
    public function setPublicAccessExpiration($Public_Access_Expiration) {
    	$this->Public_Access_Expiration = $Public_Access_Expiration;
    	return $this;
    }
    
    /**
     * Add folders
     *
     * @param \Docova\DocovaBundle\Entity\Folders $folders
     */
    public function addFolders(\Docova\DocovaBundle\Entity\Folders $folders)
    {
        $this->folders[] = $folders;
    }

    /**
     * Get folders
     *
     * @return \Doctrine\Common\Collections\Collection $folders
     */
    public function getFolders()
    {
        return $this->folders;
    }
    
    /**
     * Add ApplicableDocType
     * 
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $docTypes
     */
    public function addApplicableDocType(\Docova\DocovaBundle\Entity\DocumentTypes $docTypes)
    {
    	$this->ApplicableDocType[] = $docTypes;
    }

    /**
     * Remove ApplicableDocType
     * 
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $docTypes
     */
    public function removeApplicableDocType(\Docova\DocovaBundle\Entity\DocumentTypes $docTypes)
    {
    	$this->ApplicableDocType->removeElement($docTypes);
    }
    
    /**
     * Get ApplicableDocType
     * 
     * @return ArrayCollection
     */
    public function getApplicableDocType()
    {
    	return $this->ApplicableDocType;
    }

    /**
     * Get savedSearches
     * 
     * @return ArrayCollection
     */
    public function getSavedSearches()
    {
    	return $this->savedSearches;
    }

	

    /**
     * Set LoadDocsAsFolders
     *
     * @param boolean $loadDocsAsFolders
     * @return Libraries
     */
    public function setLoadDocsAsFolders($loadDocsAsFolders)
    {
        $this->LoadDocsAsFolders = $loadDocsAsFolders;
    
        return $this;
    }

    /**
     * Get LoadDocsAsFolders
     *
     * @return boolean 
     */
    public function getLoadDocsAsFolders()
    {
        return $this->LoadDocsAsFolders;
    }

    /**
     * Set Member_Assignment
     *
     * @param boolean $memberAssignment
     * @return Libraries
     */
    public function setMemberAssignment($memberAssignment)
    {
        $this->Member_Assignment = $memberAssignment;
    
        return $this;
    }

    /**
     * Get Member_Assignment
     *
     * @return boolean 
     */
    public function getMemberAssignment()
    {
        return $this->Member_Assignment;
    }

    /**
     * Set Library_Domain_Org_Unit
     *
     * @param string $libraryDomainOrgUnit
     * @return Libraries
     */
    public function setLibraryDomainOrgUnit($libraryDomainOrgUnit = null)
    {
        $this->Library_Domain_Org_Unit = $libraryDomainOrgUnit;
    
        return $this;
    }

    /**
     * Get Library_Domain_Org_Unit
     *
     * @return string 
     */
    public function getLibraryDomainOrgUnit()
    {
        return $this->Library_Domain_Org_Unit;
    }

    /**
     * Set isApp
     *
     * @param boolean $isApp
     * @return Libraries
     */
    public function setIsApp($isApp)
    {
        $this->isApp = $isApp;

        return $this;
    }

    /**
     * Get isApp
     *
     * @return boolean 
     */
    public function getIsApp()
    {
        return $this->isApp;
    }

    /**
     * Set appIcon
     *
     * @param string $appIcon
     * @return Libraries
     */
    public function setAppIcon($appIcon)
    {
        $this->appIcon = $appIcon;

        return $this;
    }

    /**
     * Get appIcon
     *
     * @return string 
     */
    public function getAppIcon()
    {
        return $this->appIcon;
    }

    /**
     * Set appIconColor
     *
     * @param string $appIconColor
     * @return Libraries
     */
    public function setAppIconColor($appIconColor)
    {
        $this->appIconColor = $appIconColor;

        return $this;
    }

    /**
     * Get appIconColor
     *
     * @return string 
     */
    public function getAppIconColor()
    {
        return $this->appIconColor;
    }

    /**
     * Set appInherit
     *
     * @param string $appInherit
     * @return Libraries
     */
    public function setAppInherit($appInherit)
    {
        $this->appInherit = $appInherit;

        return $this;
    }

    /**
     * Get appInherit
     *
     * @return string 
     */
    public function getAppInherit()
    {
        return $this->appInherit;
    }

    /**
     * Set inheritDesignFrom
     * 
     * @param string $application
     * @return Libraries
     */
    public function setInheritDesignFrom($application = null)
    {
    	$this->inheritDesignFrom = $application;
    	
    	return $this;
    }

    /**
     * Get inheritDesignFrom
     * 
     * @return string
     */
    public function getInheritDesignFrom()
    {
    	return $this->inheritDesignFrom;
    }
    

    /**
     * Add layouts
     * 
     * @param \Docova\DocovaBundle\Entity\AppLayout $appLayout
     */
    public function addLayouts(\Docova\DocovaBundle\Entity\AppLayout $appLayout)
    {
    	$this->layouts[] = $appLayout;
    }

    /**
     * Get layouts
     * 
     * @return ArrayCollection
     */
    public function getLayouts()
    {
    	return $this->layouts;
    }
    
    /**
     * Get forms
     * 
     * @return ArrayCollection
     */
    public function getForms()
    {
    	return $this->forms;
    }
    
    /**
     * Get views
     * 
     * @return ArrayCollection
     */
    public function getViews()
    {
    	return $this->views;
    }
    
    /**
     * Get pages
     * 
     * @return ArrayCollection
     */
    public function getPages()
    {
    	return $this->pages;
    }
    
    /**
     * Get subforms
     * 
     * @return ArrayCollection
     */
    public function getSubforms()
    {
    	return $this->subforms;
    }
    
    /**
     * Get outlines
     * 
     * @return ArrayCollection
     */
    public function getOutlines()
    {
    	return $this->outlines;
    }
    
    /**
     * Get jScripts
     * 
     * @return ArrayCollection
     */
    public function getjScripts()
    {
    	return $this->jScripts;
    }
    
    /**
     * Get agents
     * 
     * @return ArrayCollection
     */
    public function getAgents()
    {
    	return $this->agents;
    }
    
    /**
     * Get phpScripts
     * 
     * @return ArrayCollection
     */
    public function getPhpScripts()
    {
    	return $this->phpScripts;
    }
    
    /**
     * Get files
     * 
     * @return ArrayCollection
     */
    public function getFiles()
    {
    	return $this->files;
    }
    
    /**
     * Get csses
     * 
     * @return ArrayCollection
     */
    public function getCsses()
    {
    	return $this->csses;
    }
    
    
    /**
     * Set MobileLaunchType
     *
     * @param string $mobileLaunchType
     */
    public function setMobileLaunchType($mobileLaunchType)
    {
        $this->Mobile_Launch_Type = $mobileLaunchType;        
    }
    
    /**
     * Get MobileLaunchType
     *
     * @return string
     */
    public function getMobileLaunchType()
    {
        return $this->Mobile_Launch_Type;
    }
 
    /**
     * Set MobileLaunchId
     *
     * @param string $mobileLaunchId
     */
    public function setMobileLaunchId($mobileLaunchId)
    {
        $this->Mobile_Launch_Id = $mobileLaunchId;
    } 
    
    /**
     * Get MobileLaunchId
     *
     * @return string
     */
    public function getMobileLaunchId()
    {
        return $this->Mobile_Launch_Id;
    }
}
