<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Folders
 *
 * @ORM\Table(name="tb_library_folders", indexes={@ORM\Index(name="position_idx", columns={"Position"})})
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\FoldersRepository")
 */
class Folders
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Folder_Name", type="string", length=255)
     */
    protected $Folder_Name;

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
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $Creator;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Updated_By", referencedColumnName="id", nullable=true)
     */
    protected $Updator;

   	/**
   	 * @var string
   	 * 
     * @ORM\Column(name="Position", type="string", length=255, nullable=true)
     */
    protected $Position;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255, nullable=true)
     */
    protected $Description;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="Icon_Normal", type="string", length=50, nullable=true)
     */
    protected $Icon_Normal;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="Icon_Selected", type="string", length=50, nullable=true)
     */
    protected $Icon_Selected;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="Default_DocType", type="string", length=255, nullable=true)
     */
    protected $Default_DocType;
    
    /**
     * @var integer
     * 
     * @ORM\Column(name="Paging_Count", type="smallint", nullable=true)
     */
    protected $Paging_count;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Disable_ACF", type="boolean", options={"comment"="Disable Author to Create Folder"}, nullable=false)
     */
    protected $Disable_ACF = false;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Enable_ACR", type="boolean", options={"comment"="Enable Author to Create Revision"}, nullable=false)
     */
    protected $Enable_ACR = false;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Private_Draft", type="boolean", options={"comment"="Set Draft documents private"}, nullable=false)
     */
    protected $Private_Draft = false;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Set_AAE", type="boolean", options={"comment"="Set default new document to include Authors as additional editor"}, nullable=false)
     */
    protected $Set_AAE = false;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Restrict_RtA", type="boolean", options={"comment"="Restrict readers to authors list"}, nullable=false)
     */
    protected $Restrict_RtA = false;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Set_DVA", type="boolean", options={"comment"="Readers can see drafts"}, nullable=false)
     */
    protected $Set_DVA = false;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Disable_CCP", type="boolean", options={"comment"="Disable Cut/Copy/Paste"}, nullable=false)
     */
    protected $Disable_CCP = false;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Disable_TCB", type="boolean", options={"comment"="Disable tools / Create Bookmark"}, nullable=false)
     */
    protected $Disable_TCB = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Filtering", type="boolean")
     */
    protected $Filtering = false;

    /**
     * @ORM\ManyToOne(targetEntity="ViewColumns")
     * @ORM\JoinColumn(name="Fltr_Field1", referencedColumnName="id", nullable=true)
     */
    protected $Fltr_Field1;

    /**
     * @ORM\ManyToOne(targetEntity="ViewColumns")
     * @ORM\JoinColumn(name="Fltr_Field2", referencedColumnName="id", nullable=true)
     */
    protected $Fltr_Field2;

    /**
     * @ORM\ManyToOne(targetEntity="ViewColumns")
     * @ORM\JoinColumn(name="Fltr_Field3", referencedColumnName="id", nullable=true)
     */
    protected $Fltr_Field3;

    /**
     * @ORM\ManyToOne(targetEntity="ViewColumns")
     * @ORM\JoinColumn(name="Fltr_Field4", referencedColumnName="id", nullable=true)
     */
    protected $Fltr_Field4;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Sync", type="boolean", options={"default": false})
     */
    protected $Synched = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Sync_Subfolders", type="boolean", options={"default": false})
     */
    protected $Sync_Subfolders = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Synced_From_Parent", type="boolean", options={"default": false})
     */
    protected $Synced_From_Parent = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Del", type="boolean")
     */
    protected $Del = false;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Sub_Trash", type="smallint", options={"default": 0})
     */
    protected $Inactive = 0;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Deleted", type="datetime", nullable=true)
     */
    protected $Date_Deleted;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Deleted_By", referencedColumnName="id", nullable=true)
     */
    protected $Deleted_By;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries", inversedBy="folders")
     * @ORM\JoinColumn(name="Library_Id", referencedColumnName="id", nullable=false)
     */
    protected $Library;
    
    /**
     * @ORM\ManyToOne(targetEntity="Folders", inversedBy="children")
     * @ORM\JoinColumn(name="Parent_Id", referencedColumnName="id", nullable=true)
     */
    protected $parentfolder;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Folders", mappedBy="parentfolder")
     */
    protected $children;

    /**
     * @ORM\OneToMany(targetEntity="Documents",mappedBy="folder")
     * @var ArrayCollection
     */
    protected $documents;
    
    /**
     * @ORM\ManyToOne(targetEntity="SystemPerspectives")
     * @ORM\JoinColumn(name="Default_Perspective", referencedColumnName="id", nullable=false)
     */
    protected $Default_Perspective;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="DocumentTypes", inversedBy="ApplicableFolder")
     * @ORM\JoinTable(name="tb_folder_doctypes",
     *      joinColumns={@ORM\JoinColumn(name="Folder_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Doc_Type_Id", referencedColumnName="id")})
     * @ORM\OrderBy({"Doc_Name"="ASC"})
     */
    protected $ApplicableDocType;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Bookmarks", mappedBy="Target_Folder")
     */
    protected $Bookmarks;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="FoldersLog", mappedBy="Folder")
     */
    protected $Logs;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="FoldersWatchlist", mappedBy="Folders")
     */
    protected $Favorites;
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_sync_users",
     *      joinColumns={@ORM\JoinColumn(name="Folder_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id")})
     */
    protected $SynchUsers;
    

    public function __construct()
    {
    	$this->Date_Created = new \DateTime();
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
     * Set Folder_Name
     *
     * @param string $folderName
     * @return Folders
     */
    public function setFolderName($folderName)
    {
        $this->Folder_Name = $folderName;
    
        return $this;
    }

    /**
     * Get Folder_Name
     *
     * @return string 
     */
    public function getFolderName()
    {
        return $this->Folder_Name;
    }

    /**
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return Folders
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
     * @return Folders
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
     * Set Updator
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $Updator
     */
    public function setUpdator(\Docova\DocovaBundle\Entity\UserAccounts $Updator)
    {
    	$this->Updator = $Updator;
    }
    
    /**
     * Get Updator
     * 
     * @return \Docova\DocovaBundle\Entity\UserAccounts $Updator
     */
    public function getUpdator()
    {
    	return $this->Updator;
    }
    
	/**
     * Set Position
     * 
     * @return Folders
     */
    public function setPosition($entity_manager = null)
    {
    	if (!empty($entity_manager)) 
    	{
	    	if ($this->parentfolder) 
	    	{
	    		$parent_pos = $this->parentfolder->getPosition();	    		
	    		$query = $entity_manager->createQuery("SELECT MAX(StrToInteger(StrReplace(f.Position,'".$parent_pos.".','')))+1 FROM DocovaBundle:Folders f WHERE f.parentfolder=:parent_obj AND f.Library = :lib_obj")
	    			->setParameter('lib_obj', $this->Library)
	    			->setParameter('parent_obj', $this->parentfolder->getId());

	    		$result = $query->getSingleScalarResult();	   
				if (empty($result)) 
				{
					$result = '1';
				}
	    		$this->Position = "$parent_pos.$result";
	    	}
	    	else {
	    		$query = $entity_manager->createQuery('SELECT MAX(StrToInteger(f.Position))+1 FROM DocovaBundle:Folders f WHERE f.parentfolder IS NULL AND f.Library = :lib_obj')
	    			->setParameter('lib_obj', $this->Library);

	    		$result = $query->getSingleScalarResult();
	    		if (empty($result)) 
	    		{
	    			$result = '1';
	    		}
	    		$this->Position = strval($result);
	    	}
    	}
    	else {
    		$this->Position = null;
    	}
    	
    	return $this;
    }

    /**
     * Get Position
     * 
     * @return string
     */
    public function getPosition()
    {
    	return $this->Position;
    }
    
    /**
     * Set Description
     *
     * @param string $description
     * @return Folders
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
     * Set Icon_Normal
     * 
     * @param integer|null $icon
     * @return Folders
     */
    public function setIconNormal($icon)
    {
    	$this->Icon_Normal = $icon;
    	
    	return $this;
    }
    
    /**
     * Get Icon_Normal
     * 
     * @return integer
     */
    public function getIconNormal()
    {
    	return $this->Icon_Normal;
    }

    /**
     * Set Icon_Selected
     * 
     * @param integer|null $icon
     * @return Folders
     */
    public function setIconSelected($icon)
    {
    	$this->Icon_Selected = $icon;
    	
    	return $this;
    }

    /**
     * Get Icon_Selected
     * 
     * @return integer
     */
    public function getIconSelected()
    {
    	return $this->Icon_Selected;
    }

    /**
     * Set Default_DocType
     * 
     * @param null|string $doc_type
     * @return Folders
     */
    public function setDefaultDocType($doc_type = null)
    {
    	$this->Default_DocType = $doc_type;
    	
    	return $this;
    }

    /**
     * Get Default_DocType
     * 
     * @return null|string
     */
    public function getDefaultDocType()
    {
    	return $this->Default_DocType;
    }
    
    /**
     * Set Paging_Count
     * 
     * @param null|integer $count
     * @return Folders
     */
    public function setPagingCount($count = null)
    {
    	if (empty($count)) {
    		$this->Paging_count = null;
    	}
    	else {
    		$this->Paging_count = $count;
    	}
    }
    
    /**
     * Get Paging_Count
     * 
     * @return null|integer
     */
    public function getPagingCount()
    {
    	return $this->Paging_count;
    }

    /**
     * Set Disable_ACF
     * 
     * @param boolean $disabled
     * @return Folders
     */
    public function setDisableACF($disabled)
    {
    	$this->Disable_ACF = $disabled;
    	
    	return $this;
    }

    /**
     * Get Disable_ACF
     * 
     * @return boolean
     */
    public function getDisableACF()
    {
    	return $this->Disable_ACF;
    }

    /**
     * Set Enable_ACR
     * 
     * @param boolean $disabled
     * @return Folders
     */
    public function setEnableACR($disabled)
    {
    	$this->Enable_ACR = $disabled;
    	 
    	return $this;
    }

    /**
     * Get Enable_ACR
     * 
     * @return boolean
     */
    public function getEnableACR()
    {
    	return $this->Enable_ACR;
    }

    /**
     * Set Private_Draft
     * 
     * @param boolean $disabled
     * @return Folders
     */
    public function setPrivateDraft($disabled)
    {
    	$this->Private_Draft = $disabled;
    	 
    	return $this;
    }

    /**
     * Get Private_Draft
     * 
     * @return boolean
     */
    public function getPrivateDraft()
    {
    	return $this->Private_Draft;
    }

    /**
     * Set Set_AAE
     * 
     * @param boolean $disabled
     * @return Folders
     */
    public function setSetAAE($disabled)
    {
    	$this->Set_AAE = $disabled;
    	 
    	return $this;
    }

    /**
     * Get Set_AAE
     * 
     * @return boolean
     */
    public function getSetAAE()
    {
    	return $this->Set_AAE;
    }

    /**
     * Set Restrict_RtA
     * 
     * @param boolean $disabled
     * @return Folders
     */
    public function setRestrictRtA($disabled)
    {
    	$this->Restrict_RtA = $disabled;
    	 
    	return $this;
    }

    /**
     * Get Restrict_RtA
     * 
     * @return boolean
     */
    public function getRestrictRtA()
    {
    	return $this->Restrict_RtA;
    }

    /**
     * Set Set_DVA
     * 
     * @param boolean $disabled
     * @return Folders
     */
    public function setSetDVA($disabled)
    {
    	$this->Set_DVA = $disabled;
    	 
    	return $this;
    }

    /**
     * Get Set_DVA
     * 
     * @return boolean
     */
    public function getSetDVA()
    {
    	return $this->Set_DVA;
    }

    /**
     * Set Disable_CCP
     * 
     * @param boolean $disabled
     * @return Folders
     */
    public function setDisableCCP($disabled)
    {
    	$this->Disable_CCP = $disabled;
    	 
    	return $this;
    }

    /**
     * Get Disable_CCP
     * 
     * @return boolean
     */
    public function getDisableCCP()
    {
    	return $this->Disable_CCP;
    }

    /**
     * Set Disable_TCB
     * 
     * @param boolean $disabled
     * @return Folders
     */
    public function setDisableTCB($disabled)
    {
    	$this->Disable_TCB = $disabled;
    	 
    	return $this;
    }

    /**
     * Get Disable_TCB
     * 
     * @return boolean
     */
    public function getDisableTCB()
    {
    	return $this->Disable_TCB;
    }

    /**
     * Set Filtering
     * 
     * @param boolean $filtering
     * @return Folders
     */
    public function setFiltering($filtering)
    {
    	$this->Filtering = $filtering;
    	
    	return $this;
    }

    /**
     * Get Filtering
     * 
     * @return boolean
     */
    public function getFiltering()
    {
    	return $this->Filtering;
    }

    /**
     * Set Fltr_Field1
     * 
     * @param \Docova\DocovaBundle\Entity\ViewColumns $field
     * @return Folders
     */
    public function setFltrField1(\Docova\DocovaBundle\Entity\ViewColumns $field = null)
    {
    	$this->Fltr_Field1 = $field;
    	
    	return $this;
    }

    /**
     * Get Fltr_Field1
     * 
     * @return \Docova\DocovaBundle\Entity\ViewColumns
     */
    public function getFltrField1()
    {
    	return $this->Fltr_Field1;
    }

    /**
     * Set Fltr_Field2
     *
     * @param \Docova\DocovaBundle\Entity\ViewColumns $field
     * @return Folders
     */
    public function setFltrField2(\Docova\DocovaBundle\Entity\ViewColumns $field = null)
    {
    	$this->Fltr_Field2 = $field;
    	 
    	return $this;
    }
    
    /**
     * Get Fltr_Field2
     *
     * @return \Docova\DocovaBundle\Entity\ViewColumns
     */
    public function getFltrField2()
    {
    	return $this->Fltr_Field2;
    }

    /**
     * Set Fltr_Field3
     *
     * @param \Docova\DocovaBundle\Entity\ViewColumns $field
     * @return Folders
     */
    public function setFltrField3(\Docova\DocovaBundle\Entity\ViewColumns $field = null)
    {
    	$this->Fltr_Field3 = $field;
    	 
    	return $this;
    }
    
    /**
     * Get Fltr_Field3
     *
     * @return \Docova\DocovaBundle\Entity\ViewColumns
     */
    public function getFltrField3()
    {
    	return $this->Fltr_Field3;
    }

    /**
     * Set Fltr_Field4
     *
     * @param \Docova\DocovaBundle\Entity\ViewColumns $field
     * @return Folders
     */
    public function setFltrField4(\Docova\DocovaBundle\Entity\ViewColumns $field = null)
    {
    	$this->Fltr_Field4 = $field;
    	 
    	return $this;
    }
    
    /**
     * Get Fltr_Field4
     *
     * @return \Docova\DocovaBundle\Entity\ViewColumns
     */
    public function getFltrField4()
    {
    	return $this->Fltr_Field4;
    }

    /**
     * Set Synched
     *
     * @param boolean $synched
     * @return Folders
     */
    public function setSynched($synched)
    {
    	$this->Synched = $synched;
    
    	return $this;
    }
    
    /**
     * Get Synched
     *
     * @return boolean
     */
    public function getSynched()
    {
    	return $this->Synched;
    }
    
    /**
     * Set Sync_Subfolders
     *
     * @param boolean $syncSubfolders
     * @return Folders
     */
    public function setSyncSubfolders($syncSubfolders)
    {
    	$this->Sync_Subfolders = $syncSubfolders;
    
    	return $this;
    }
    
    /**
     * Get Sync_Subfolders
     *
     * @return boolean
     */
    public function getSyncSubfolders()
    {
    	return $this->Sync_Subfolders;
    }

    /**
     * Set Synced_From_Parent
     *
     * @param boolean $syncedFromParent
     * @return Folders
     */
    public function setSyncedFromParent($syncedFromParent)
    {
    	$this->Synced_From_Parent = $syncedFromParent;
    
    	return $this;
    }
    
    /**
     * Get Synced_From_Parent
     *
     * @return boolean
     */
    public function getSyncedFromParent()
    {
    	return $this->Synced_From_Parent;
    }

    /**
     * Set Del
     *
     * @param boolean $del
     * @return Folders
     */
    public function setDel($del)
    {
        $this->Del = $del;
    
        return $this;
    }

    /**
     * Get Del
     *
     * @return boolean 
     */
    public function getDel()
    {
        return $this->Del;
    }

    /**
     * Set Inactive
     *
     * @param integer $inactive
     * @return Folders
     */
    public function setInactive($inactive)
    {
    	$this->Inactive = $inactive;
    
    	return $this;
    }
    
    /**
     * Get Inactive
     *
     * @return integer
     */
    public function getInactive()
    {
    	return $this->Inactive;
    }

    /**
     * Set Date_Deleted
     *
     * @param \DateTime|null $date
     * @return Documents
     */
    public function setDateDeleted($date = null)
    {
    	$this->Date_Deleted = $date;
    	 
    	return $this;
    }
    
    /**
     * Get Date_Deleted
     *
     * @return \DateTime
     */
    public function getDateDeleted()
    {
    	return $this->Date_Deleted;
    }

    /**
     * Set Deleted_By
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $deletedBy
     * @return Folders
     */
    public function setDeletedBy(\Docova\DocovaBundle\Entity\UserAccounts $deletedBy = null)
    {
    	$this->Deleted_By = $deletedBy;
    
    	return $this;
    }
    
    /**
     * Get Deleted_By
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts|null
     */
    public function getDeletedBy()
    {
    	return $this->Deleted_By;
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
     * Get library
     *
     * @return \Docova\DocovaBundle\Entity\Libraries $library
     */
    public function getLibrary()
    {
        return $this->Library;
    }

    /**
     * Add documents
     *
     * @param \Docova\DocovaBundle\Entity\Documents $documents
     */
    public function addDocuments(\Docova\DocovaBundle\Entity\Documents $documents)
    {
        $this->documents[] = $documents;
    }

    /**
     * Get documents
     *
     * @return \Doctrine\Common\Collections\Collection $documents
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Set parentfolder
     *
     * @param \Docova\DocovaBundle\Entity\Folders $parentfolder
     */
    public function setParentfolder(\Docova\DocovaBundle\Entity\Folders $parentfolder = null)
    {
        $this->parentfolder = $parentfolder;
    }

    /**
     * Get parentfolder
     *
     * @return \Docova\DocovaBundle\Entity\Folders $parentfolder
     */
    public function getParentfolder()
    {
        return $this->parentfolder;
    }
    
    /**
     * Get children
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getChildren()
    {
    	return $this->children;
    }
    
    /**
     * Set Default_Perspective
     * 
     * @param \Docova\DocovaBundle\Entity\SystemPerspectives $perspective
     */
    public function setDefaultPerspective(\Docova\DocovaBundle\Entity\SystemPerspectives $perspective)
    {
    	$this->Default_Perspective = $perspective;
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

    /**
     * Add ApplicableDocType
     *
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $docTypes
     */
    public function addApplicableDocType(\Docova\DocovaBundle\Entity\DocumentTypes $doc_types)
    {
    	$this->ApplicableDocType[] = $doc_types;
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
     * Remove ApplicableDocType
     * 
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $doc_type
     */
    public function removeApplicableDocType(\Docova\DocovaBundle\Entity\DocumentTypes $doc_type)
    {
    	$this->ApplicableDocType->removeElement($doc_type);
    }
    
    /**
     * Clear all Applicable DocTypes 
     */
    public function clearApplicableDocTypes()
    {
    	$this->ApplicableDocType->clear();
    }
    
    /**
     * Get Bookmarks 
     * 
     * @return ArrayCollection
     */
    public function getBookmarks()
    {
    	return $this->Bookmarks;
    }

    /**
     * Get Logs
     * 
     * @return ArrayCollection
     */
    public function getLogs()
    {
    	return $this->Logs;
    }

    /**
     * Get Favorites
     * 
     * @return ArrayCollection
     */
    public function getFavorites()
    {
    	return $this->Favorites;
    }

    /**
     * Add SynchUsers
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function addSynchUsers(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->SynchUsers[] = $user;
    }

    /**
     * Get SynchUsers
     * 
     * @return ArrayCollection
     */
    public function getSynchUsers()
    {
    	return $this->SynchUsers;
    }

    /**
     * Remove SynchUsers
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function removeSynchUsers(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->SynchUsers->removeElement($user);
    }

    /**
     * Remove all SynchUsers 
     */
    public function removeAllSynchUsers()
    {
    	$this->SynchUsers->clear();
    }

    /**
     * Get a folder path
     * 
     * @return string
     */
    public function getFolderPath()
    {
    	if (!$this->getParentfolder())
    	{
    		return $this->getFolderName();
    	}
    	
    	$path = $this->getParentfolder()->getFolderPath().'\\'.$this->getFolderName();
    	return $path;
    }
}
