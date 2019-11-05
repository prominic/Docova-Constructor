<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Documents
 *
 * @ORM\Table(name="tb_folders_documents")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DocumentsRepository")
 */
class Documents
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Doc_Title", type="string", length=255, nullable=true)
     */
    protected $Doc_Title;

    /**
     * @var string
     *
     * @ORM\Column(name="Doc_Version", type="string", length=12, nullable=true)
     */
    protected $Doc_Version;
    
    /**
     * @var integer
     * 
     * @ORM\Column(name="Revision", type="smallint", nullable=true)
     */
    protected $Revision;

    /**
     * @ORM\ManyToOne(targetEntity="Documents")
     * @ORM\JoinColumn(name="Parent_Document", referencedColumnName="id", nullable=true)
     */
    protected $Parent_Document;

    /**
     * @var string
     * 
     * @ORM\Column(name="Doc_Status", type="string", length=50, nullable=true)
     */
    protected $Doc_Status;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Status_No", type="smallint", options={"default": 0})
     */
    protected $Status_No = 0;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Doc_Owner", referencedColumnName="id", nullable=false)
     */
    protected $Owner;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Doc_Author", referencedColumnName="id")
     */
    protected $Author;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="Description", type="string", nullable=true)
     */
    protected $Description;

    /**
     * @var string
     * 
     * @ORM\Column(name="Keywords", type="string", nullable=true)
     */
    protected $Keywords;

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
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id", nullable=true)
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
     * @ORM\Column(name="Locked", type="boolean")
     */
    protected $Locked = false;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Lock_Editor", referencedColumnName="id", nullable=true)
     */
    protected $Lock_Editor;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Released_By", referencedColumnName="id", nullable=true)
     */
    protected $Released_By;
    
    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Released_Date", type="datetime", nullable=true)
     */
    protected $Released_Date;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Last_Review_Date", type="datetime", nullable=true)
     */
    protected $Last_Review_Date;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Next_Review_Date", type="datetime", nullable=true)
     */
    protected $Next_Review_Date;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Has_Pending_Review", type="boolean", options={"default": false})
     */
    protected $Has_Pending_Review = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Review_Type", type="string", length=1, options={"default" : "P"})
     */
    protected $Review_Type = 'P';

    /**
     * @var integer
     * 
     * @ORM\Column(name="Review_Period", type="integer", nullable=true)
     */
    protected $Review_Period;

    /**
     * @var string
     * 
     * @ORM\Column(name="Review_Period_Option", type="string", length=1, nullable=true)
     */
    protected $Review_Period_Option;

    /**
     * @var string
     * 
     * @ORM\Column(name="Review_Date_Select", type="string", length=50, nullable=true)
     */
    protected $Review_Date_Select;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Review_Start_Month", type="smallint", nullable=true)
     */
    protected $Review_Start_Month;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Review_Start_Day", type="smallint", nullable=true)
     */
    protected $Review_Start_Day;
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Author_Review", type="boolean", options={"default": false})
     */
    protected $Author_Review = false;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_document_reviewers",
     *      joinColumns={@ORM\JoinColumn(name="Document_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id")})
     */
    protected $Reviewers;

    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="ReviewItems", mappedBy="Document")
     */
    protected $reviewItems;

    /**
     * @var string
     *
     * @ORM\Column(name="Archive_Type", type="string", length=1, options={"default" : "P"})
     */
    protected $Archive_Type = 'P';

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Custom_Archive_Date", type="datetime", nullable=true)
     */
    protected $Custom_Archive_Date;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean")
     */
    protected $Trash = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Archived", type="boolean")
     */
    protected $Archived = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Indexed", type="boolean")
     */
    protected $Indexed = false;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Index_Date", type="datetime", nullable=true)
     */
    protected $Index_Date;

    /**
     * @var string
     * 
     * @ORM\Column(name="Profile_Name", type="string", length=255, nullable=true)
     */
    protected $profileName;

    /**
     * @var string
     * 
     * @ORM\Column(name="Profile_Key", type="string", length=255, nullable=true)
     */
    protected $profileKey;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="DesignElements", mappedBy="profileDocument")
     */
    protected $profileFields;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Archived", type="datetime", nullable=true)
     */
    protected $Date_Archived;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Status_No_Archived", type="integer", nullable=true)
     */
    protected $Status_No_Archived;

    /**
     * @var string
     * 
     * @ORM\Column(name="Previous_Status", type="string", length=50, nullable=true)
     */
    protected $Previous_Status;

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
     * @ORM\ManyToOne(targetEntity="Folders", inversedBy="documents")
     * @ORM\JoinColumn(name="Folder_Id", referencedColumnName="id", nullable=true)
     */
    protected $folder;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="App_Id", referencedColumnName="id", nullable=true)
     */
    protected $application;
    
    /**
     * @ORM\ManyToOne(targetEntity="DocumentTypes")
     * @ORM\JoinColumn(name="Doc_Type_Id", referencedColumnName="id", nullable=true)
     */
    protected $DocType;

    /**
     * @ORM\ManyToOne(targetEntity="AppForms")
     * @ORM\JoinColumn(name="App_Form_Id", referencedColumnName="id", nullable=true)
     */
    protected $appForm;

    /**
     * @ORM\OneToMany(targetEntity="RelatedDocuments", mappedBy="Parent_Doc")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $childDocuments;
    
    /**
     * @ORM\OneToMany(targetEntity="DocumentWorkflowSteps", mappedBy="Document")
     * @ORM\OrderBy({"Position" = "ASC"})
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $DocSteps;
    
    /**
     * @ORM\OneToMany(targetEntity="DocumentComments", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Comments;
    
    /**
     * @ORM\OneToMany(targetEntity="AttachmentsDetails", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Attachments;
    
    /**
     * @ORM\OneToMany(targetEntity="Bookmarks", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Bookmarks;
    
    /**
     * @ORM\OneToMany(targetEntity="FormTextValues", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Text_Values;

    /**
     * @ORM\OneToMany(targetEntity="FormDateTimeValues", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Date_Values;

    /**
     * @ORM\OneToMany(targetEntity="FormNumericValues", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Numeric_Values;
    
    /**
     * @ORM\OneToMany(targetEntity="FormNameValues", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Name_Values;

    /**
     * @ORM\OneToMany(targetEntity="FormGroupValues", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Group_Values;
    
    /**
     * @ORM\OneToMany(targetEntity="DocumentActivities", mappedBy="document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Activities;

    /**
     * @ORM\OneToMany(targetEntity="DocumentsLog", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Logs;

    /**
     * @ORM\OneToMany(targetEntity="DocumentsWatchlist", mappedBy="Document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $Favorites;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="DiscussionTopic", mappedBy="parentDocument")
     */
    protected $discussion;

    /**
     * @ORM\OneToMany(targetEntity="AppDocComments", mappedBy="document")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $AppDocComments;


    public function __construct()
    {
    	$this->Date_Created = new \DateTime();
    }


	/**
	 * Set id
	 * 
	 * @param string $id
	 * @return Documents
	 */
	public function setId($id)
	{
		$this->id = $id;
		
		return $this;
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
     * Set folder
     *
     * @param \Docova\DocovaBundle\Entity\Folders $folder
     */
    public function setFolder(\Docova\DocovaBundle\Entity\Folders $folder)
    {
    	$this->folder = $folder;
    }
    
    /**
     * Get folder
     *
     * @return \Docova\DocovaBundle\Entity\Folders
     */
    public function getFolder()
    {
    	return $this->folder;
    }

    /**
     * Set application
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $application
     */
    public function setApplication(\Docova\DocovaBundle\Entity\Libraries $application)
    {
    	$this->application = $application;
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
     * Set DocType
     * 
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $DocType
     */
    public function setDocType(\Docova\DocovaBundle\Entity\DocumentTypes $DocType)
    {
    	$this->DocType = $DocType;
    }
    
    /**
     * Get DocType
     * 
     * @param \Docova\DocovaBundle\Entity\DocumentTypes
     */
    public function getDocType()
    {
    	return $this->DocType;
    }
    
    /**
     * Set Doc_Title
     *
     * @param string $docTitle
     * @return Documents
     */
    public function setDocTitle($docTitle)
    {
        $this->Doc_Title = $docTitle;
    
        return $this;
    }

    /**
     * Get Doc_Title
     *
     * @return string 
     */
    public function getDocTitle()
    {
        return $this->Doc_Title;
    }

    /**
     * Set Doc_Version
     *
     * @param string $docVersion
     * @return Documents
     */
    public function setDocVersion($docVersion)
    {
        $this->Doc_Version = $docVersion;
    
        return $this;
    }

    /**
     * Get Doc_Version
     *
     * @return string 
     */
    public function getDocVersion()
    {
        return $this->Doc_Version;
    }

    /**
     * Set Revision
     * 
     * @param integer $revision
     * @return Documents
     */
    public function setRevision($revision)
    {
    	$this->Revision = $revision;
    	
    	return $this;
    }

    /**
     * Get Revision
     * 
     * @return integer
     */
    public function getRevision()
    {
    	return $this->Revision;
    }

    /**
     * Set Parent_Document
     * 
     * @param Documents $document
     */
    public function setParentDocument(Documents $document = null)
    {
    	$this->Parent_Document = $document;
    }

    /**
     * Get Parent_Document
     * 
     * @return Documents
     */
    public function getParentDocument()
    {
    	return $this->Parent_Document;
    }

    /**
     * Set Doc_Status
     * 
     * @param string $docStatus
     * @return Documents
     */
    public function setDocStatus($docStatus)
    {
    	$this->Doc_Status = $docStatus;
    	
    	return $this;
    }
    
    /**
     * Get Doc_Status
     * 
     * @return string
     */
    public function getDocStatus()
    {
    	return $this->Doc_Status;
    }

    /**
     * Set Status_No
     * 
     * @param integer $Status_No
     * @return Documents
     */
    public function setStatusNo($Status_No) 
    {
    	$this->Status_No = $Status_No;
    	
    	return $this;
    }

	/**
	 * Get Status_No
	 * 
	 * @return integer
	 */
	public function getStatusNo() 
	{
		return $this->Status_No;
	}
	
    /**
     * Set Owner
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $Owner
     */
    public function setOwner(\Docova\DocovaBundle\Entity\UserAccounts $Owner)
    {
    	$this->Owner = $Owner;
    }
    
    /**
     * Get Owner
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts $Owner
     */
    public function getOwner()
    {
    	return $this->Owner;
    }

    /**
     * Set Author
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $Author
     */
    public function setAuthor(\Docova\DocovaBundle\Entity\UserAccounts $Author)
    {
    	$this->Author = $Author;
    }
    
    /**
     * Get Author
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts $Author
     */
    public function getAuthor()
    {
    	return $this->Author;
    }
    
    /**
     * Set Description
     * 
     * @param string $Description
     * @return Documents
     */
    public function setDescription($Description = null)
    {
    	$this->Description = $Description;
    	
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
     * Set Keywords
     * 
     * @param string $Keywords
     * @return Documents
     */
    public function setKeywords($Keywords = null)
    {
    	$this->Keywords = $Keywords;
    	
    	return $this;
    }
    
    /**
     * Get Keywords
     * 
     *  @return string
     */
    public function getKeywords()
    {
    	return $this->Keywords;
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
     * @return Documents
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
    	$this->Modifier = $Modifier;
    }
    
    /**
     * Get Modifier
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts $Modifier
     */
    public function getModifier()
    {
    	return $this->Modifier;
    }
    
    /**
     * Set Date_Modified
     *
     * @param \DateTime $dateModified
     * @return Documents
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
     * Set Locked
     * 
     * @param boolean $locked
     * @return Documents
     */
    public function setLocked($locked)
    {
    	$this->Locked = $locked;
    	
    	return $this;
    }
    
    /**
     * Get Locked
     * 
     * @return boolean
     */
    public function getLocked()
    {
    	return $this->Locked;
    }
    
    /**
     * Set Lock_Editor
     * 
     * @param null|\Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function setLockEditor(\Docova\DocovaBundle\Entity\UserAccounts $user = null)
    {
    	
    	$this->Lock_Editor = $user;
    }
    
    /**
     * Get Lock_Editor
     * 
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getLockEditor()
    {
    	return $this->Lock_Editor;
    }

    /**
     * Set Released_By
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $Released_By
     * @return Documents
     */
    public function setReleasedBy(\Docova\DocovaBundle\Entity\UserAccounts $Released_By = null) 
    {
    	$this->Released_By = $Released_By;
    	
    	return $this;
    }

	/**
	 * Get Released_By
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserAccounts
	 */
	public function getReleasedBy() 
	{
		return $this->Released_By;
	}

	/**
	 * Set Released_Date
	 * 
	 * @param \DateTime $Released_Date
	 * @return Documents
	 */
	public function setReleasedDate($Released_Date = null) 
	{
		$this->Released_Date = $Released_Date;
		
		return $this;
	}

	/**
	 * Get Released_Date
	 * 
	 * @return \DateTime
	 */
	public function getReleasedDate() 
	{
		return $this->Released_Date;
	}

	/**
	 * Set Last_Review_Date
	 * 
	 * @param \DateTime $Last_Review_Date
	 * @return Documents
	 */
	public function setLastReviewDate($Last_Review_Date) 
	{
		$this->Last_Review_Date = $Last_Review_Date;
		
		return $this;
	}

	/**
	 * Get Last_Review_Date
	 * 
	 * @return \DateTime
	 */
	public function getLastReviewDate() 
	{
		return $this->Last_Review_Date;
	}

	/**
	 * Set Next_Review_Date
	 * 
	 * @param \DateTime $Next_Review_Date
	 * @return Documents
	 */
	public function setNextReviewDate($Next_Review_Date) 
	{
		$this->Next_Review_Date = $Next_Review_Date;
		
		return $this;
	}

	/**
	 * Get Next_Review_Date
	 * 
	 * @return \DateTime
	 */
	public function getNextReviewDate() 
	{
		return $this->Next_Review_Date;
	}

	/**
	 * Set Has_Pending_Review
	 * 
	 * @param boolean $Has_Pending_Review
	 * @return Documents
	 */
	public function setHasPendingReview($Has_Pending_Review) 
	{
		$this->Has_Pending_Review = $Has_Pending_Review;
		
		return $this;
	}

	/**
	 * Get Has_Pending_Review
	 * 
	 * @return boolean
	 */
	public function getHasPendingReview() 
	{
		return $this->Has_Pending_Review;
	}

	/**
	 * Set Review_Type
	 * 
	 * @param string $Review_Type
	 * @return Documents
	 */
	public function setReviewType($Review_Type) 
	{
		$this->Review_Type = $Review_Type;
		
		return $this;
	}

	/**
	 * Get Review_Type
	 * 
	 * @return string
	 */
	public function getReviewType() 
	{
		return $this->Review_Type;
	}

	/**
	 * Set Review_Period
	 * 
	 * @param integer|null $Review_Period
	 * @return Documents
	 */
	public function setReviewPeriod($Review_Period = null) 
	{
		$this->Review_Period = $Review_Period;
		
		return $this;
	}

	/**
	 * Get Review_Period
	 * 
	 * @return integer
	 */
	public function getReviewPeriod() 
	{
		return $this->Review_Period;
	}

	/**
	 * Set Review_Period_Option
	 * 
	 * @param string|null $Review_Period_Option
	 * @return Documents
	 */
	public function setReviewPeriodOption($Review_Period_Option = null) 
	{
		$this->Review_Period_Option = $Review_Period_Option;
		
		return $this;
	}

	/**
	 * Get Review_Period_Option
	 * 
	 * @return string
	 */
	public function getReviewPeriodOption() 
	{
		return $this->Review_Period_Option;
	}

	/**
	 * Set Review_Date_Select
	 * 
	 * @param string|null $Review_Date_Select
	 * @return Documents
	 */
	public function setReviewDateSelect($Review_Date_Select = null) 
	{
		$this->Review_Date_Select = $Review_Date_Select;
		
		return $this;
	}

	/**
	 * Get Review_Date_Select
	 * 
	 * @return string
	 */
	public function getReviewDateSelect() 
	{
		return $this->Review_Date_Select;
	}

	/**
	 * Set Review_Start_Month
	 * 
	 * @param integer|null $Review_Start_Month
	 * @return Documents
	 */
	public function setReviewStartMonth($Review_Start_Month = null) 
	{
		$this->Review_Start_Month = $Review_Start_Month;
		
		return $this;
	}

	/**
	 * Get Review_Start_Month
	 * 
	 * @return integer
	 */
	public function getReviewStartMonth() 
	{
		return $this->Review_Start_Month;
	}

	/**
	 * Set Review_Start_Day
	 * 
	 * @param integer|null $Review_Start_Day
	 * @return Documents
	 */
	public function setReviewStartDay($Review_Start_Day = null) 
	{
		$this->Review_Start_Day = $Review_Start_Day;
		
		return $this;
	}

	/**
	 * Get Review_Start_Day
	 * 
	 * @return integer
	 */
	public function getReviewStartDay() 
	{
		return $this->Review_Start_Day;
	}

	/**
	 * Set Author_Review
	 * 
	 * @param boolean $Author_Review
	 * @return Documents
	 */
	public function setAuthorReview($Author_Review) 
	{
		$this->Author_Review = $Author_Review;
		
		return $this;
	}

	/**
	 * Get Author_Review
	 * 
	 * @return boolean
	 */
	public function getAuthorReview() 
	{
		return $this->Author_Review;
	}	

	/**
	 * Add Reviewers
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $Reviewers
	 */
	public function addReviewers(\Docova\DocovaBundle\Entity\UserAccounts $Reviewers) 
	{
		$this->Reviewers[] = $Reviewers;
	}
	
	/**
	 * Remove all assigned reviewers to particular document 
	 */
	public function clearReviewers()
	{
		$this->Reviewers->clear();
	}
	
	/**
	 * Get Reviewers
	 * 
	 * @return ArrayCollection
	 */
	public function getReviewers() 
	{
		return $this->Reviewers;
	}
	
	/**
	 * Get reviewItems
	 * 
	 * @return ArrayCollection
	 */
	public function getReviewItems()
	{
		return $this->reviewItems;
	}

	/**
	 * Set Archive_Type
	 * 
	 * @param string $Archive_Type
	 * @return Documents
	 */
	public function setArchiveType($Archive_Type) 
	{
		$this->Archive_Type = $Archive_Type;
		
		return $this;
	}

	/**
	 * Get Archive_Type
	 * 
	 * @return string
	 */
	public function getArchiveType() 
	{
		return $this->Archive_Type;
	}

	/**
	 * Set Custom_Archive_Date
	 * 
	 * @param \DateTime $Custom_Archive_Date
	 * @return Documents
	 */
	public function setCustomArchiveDate($Custom_Archive_Date = null) 
	{
		$this->Custom_Archive_Date = $Custom_Archive_Date;
		
		return $this;
	}

	/**
	 * Get Custom_Archive_Date
	 * 
	 * @return \DateTime
	 */
	public function getCustomArchiveDate() 
	{
		return $this->Custom_Archive_Date;
	}

    /**
     * Set Trash
     *
     * @param boolean $trash
     * @return Documents
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
     * Set Archived
     * 
     * @param boolean $Archived
     * @return Documents
     */
    public function setArchived($Archived) 
    {
    	$this->Archived = $Archived;
    	
    	return $this;
    }

	/**
	 * Get Archived
	 * 
	 * @return boolean
	 */
	public function getArchived() 
	{
		return $this->Archived;
	}

	/**
	 * Set Indexed
	 * 
	 * @param boolean $indexed
	 * @return Documents
	 */
	public function setIndexed($indexed)
	{
		$this->Indexed = $indexed;

		return $this;
	}

	/**
	 * Get Indexed
	 * 
	 * @return boolean
	 */
	public function getIndexed()
	{
		return $this->Indexed;
	}

	/**
	 * Set Index_Date
	 * @param \DateTime $indexDate
	 * @return Documents
	 */
	public function setIndexDate($indexDate)
	{
		$this->Index_Date = $indexDate;
		
		return $this;
	}

	/**
	 * Get Index_Date
	 * 
	 * @return \DateTime
	 */
	public function getIndexDate()
	{
		return $this->Index_Date;
	}

	/**
	 * Set profileName
	 * 
	 * @param string $profileName
	 * @return Documents
	 */
	public function setProfileName($profileName)
	{
		$this->profileName = $profileName;
		
		return $this;
	}
	
	/**
	 * Get profileName
	 * 
	 * @return string
	 */
	public function getProfileName()
	{
		return $this->profileName;
	}
	
	/**
	 * Set profileKey
	 * 
	 * @param string $profileKey
	 * @return Documents
	 */
	public function setProfileKey($profileKey)
	{
		$this->profileKey = $profileKey;
		
		return $this;
	}
	
	/**
	 * Get profileKey
	 * 
	 * @return string
	 */
	public function getProfileKey()
	{
		return $this->profileKey;
	}
	
	/**
	 * Add profileFields
	 * 
	 * @param \Docova\DocovaBundle\Entity\DesignElements $profileField
	 */
	public function addProfileFields(\Docova\DocovaBundle\Entity\DesignElements $profileField)
	{
		$this->profileFields->add($profileField);
	}
	
	/**
	 * Get profileFields
	 * 
	 * @return ArrayCollection
	 */
	public function getProfileFields()
	{
		return $this->profileFields;
	}
	
	/**
	 * Remove profileFields
	 * 
	 * @param \Docova\DocovaBundle\Entity\DesignElements $profileField
	 */
	public function removeProfileFields(\Docova\DocovaBundle\Entity\DesignElements $profileField)
	{
		$this->profileFields->removeElement($profileField);
	}

	/**
	 * Set Date_Archived
	 * 
	 * @param \DateTime $Date_Archived
	 * @return Documents
	 */
	public function setDateArchived($Date_Archived) 
	{
		$this->Date_Archived = $Date_Archived;
		
		return $this;
	}

	/**
	 * Get Date_Archived
	 * 
	 * @return \DateTime
	 */
	public function getDateArchived() 
	{
		return $this->Date_Archived;
	}

	/**
	 * Set Status_No_Archived
	 * 
	 * @param integer|null $Status_No_Archived
	 * @return Documents
	 */
	public function setStatusNoArchived($Status_No_Archived = null) 
	{
		$this->Status_No_Archived = $Status_No_Archived;
		
		return $this;
	}

	/**
	 * Get Status_No_Archived
	 * 
	 * @return integer
	 */
	public function getStatusNoArchived() 
	{
		return $this->Status_No_Archived;
	}

	/**
	 * Set Previous_Status
	 * 
	 * @param string|null $Previous_Status
	 * @return Documents
	 */
	public function setPreviousStatus($Previous_Status = null) 
	{
		$this->Previous_Status = $Previous_Status;
		
		return $this;
	}

	/**
	 * Get Previous_Status
	 * 
	 * @return string
	 */
	public function getPreviousStatus() 
	{
		return $this->Previous_Status;
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
     * @return Documents
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
     * Set appForm
     * 
     * @param \Docova\DocovaBundle\Entity\AppForms $appForm
     */
    public function setAppForm(\Docova\DocovaBundle\Entity\AppForms $appForm)
    {
    	$this->appForm = $appForm;
    }

    /**
     * Get appForm
     * 
     * @return \Docova\DocovaBundle\Entity\AppForms
     */
    public function getAppForm()
    {
    	return $this->appForm;
    }

    /**
     * Get DocSteps
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDocSteps()
    {
    	return $this->DocSteps;
    }
    
    /**
     * Get childDocuments
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getChildDocuments()
    {
    	return $this->childDocuments;
    }

    /**
     * Get Comments
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getComments()
    {
    	return $this->Comments;
    }

    /**
     * Get Comments
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getAppDocComments()
    {
        return $this->AppDocComments;
    }

    /**
     * Get Attachments
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getAttachments()
    {
    	return $this->Attachments;
    }


    /**
     * Get Attachments by field name
     *
     */
    public function getAttachmentsByFieldName($fieldname)
    {
        $attachments = $this->Attachments;
        $toret = array();
        if (is_null($attachments))
			return $toret;
        foreach ($attachments as $attach) {
            if ($attach->getField()->getFieldName() == strtolower($fieldname))
               array_push($toret, $attach);
        }
        return $toret;
    }


    /**
     * Get Bookmarks
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getBookmarks()
    {
    	return $this->Bookmarks;
    }

    /**
     * Get Text_Values
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getTextValues()
    {
    	return $this->Text_Values;
    }

    /**
     * Get Date_Values
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDateValues()
    {
    	return $this->Date_Values;
    }

    /**
     * Get Numeric_Values
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getNumericValues()
    {
    	return $this->Numeric_Values;
    }

    /**
     * Get Name_Values
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getNameValues()
    {
    	return $this->Name_Values;
    }
    
    /**
     * Get Group_Values
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getGroupValues()
    {
    	return $this->Group_Values;
    }

    /**
     * Get Activities
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getActivities()
    {
    	return $this->Activities;
    }

    /**
     * Get Logs
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getLogs()
    {
    	return $this->Logs;
    }

    /**
     * Get Favorites
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getFavorites()
    {
    	return $this->Favorites;
    }
    
    /**
     * Get discussion
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDiscussion()
    {
    	return $this->discussion;
    }
}
