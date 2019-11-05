<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppFormProperties
 *
 * @ORM\Table(name="tb_app_form_properties")
 * @ORM\Entity
 */
class AppFormProperties
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
     * @var boolean
     *
     * @ORM\Column(name="Enable_Life_Cycle", type="boolean", options={"default" : false})
     */
    protected $enableLifeCycle = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Initial_Status", type="string", length=255, nullable=true)
     */
    protected $initialStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="Final_Status", type="string", length=255)
     */
    protected $finalStatus;

    /**
     * @var string
     * 
     * @ORM\Column(name="Superseded_Status", type="string", length=255, nullable=true)
     */
    protected $supersededStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="Discarded_Status", type="string", length=255, nullable=true)
     */
    protected $discardedStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="Archived_Status", type="string", length=255)
     */
    protected $archivedStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="Deleted_Status", type="string", length=255)
     */
    protected $deletedStatus;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Enable_Versions", type="boolean", options={"default" : false})
     */
    protected $enableVersions = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Restrict_Live_Drafts", type="boolean", options={"default" : false})
     */
    protected $restrictLiveDrafts = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Strict_Versioning", type="boolean", options={"default":false})
     */
    protected $strictVersioning = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Allow_Retract", type="boolean", options={"default":false})
     */
    protected $allowRetract = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Restrict_Drafts", type="boolean", options={"default":false})
     */
    protected $restrictDrafts = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Update_Bookmarks", type="boolean", options={"default":false})
     */
    protected $updateBookmarks = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Attachment_Options", type="smallint", nullable=true)
     */
    protected $attachmentOptions;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Enable_Workflow", type="boolean", options={"default" : false})
     */
    protected $enableWorkflow = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Workflow_Style", type="string", length=1, nullable=true)
     */
    protected $workflowStyle;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Disable_Delete_Workflow", type="boolean", options={"default" : false})
     */
    protected $disableDeleteWorkflow = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Custom_Start_Button_Label", type="text", nullable=true)
     */
    protected $customStartButtonLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="Custom_JS_WF_Complete", type="text", nullable=true)
     */
    protected $Custom_JS_WF_Complete;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Custom_JS_WF_Approve", type="text", nullable=true)
     */
    protected $Custom_JS_WF_Approve;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Custom_JS_WF_Deny", type="text", nullable=true)
     */
    protected $Custom_JS_WF_Deny;

    /**
     * @var string
     *
     * @ORM\Column(name="Custom_JS_WF_Start", type="text", nullable=true)
     */
    protected $Custom_JS_WF_Start;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Custom_Release_Button_Label", type="text", nullable=true)
     */
    protected $customReleaseButtonLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="Custom_JS_Before_Release", type="text", nullable=true)
     */
    protected $Custom_JS_Before_Release;

    /**
     * @var string
     *
     * @ORM\Column(name="Custom_JS_After_Release", type="text", nullable=true)
     */
    protected $Custom_JS_After_Release;
    

    /**
     * @var string
     *
     * @ORM\Column(name="Custom_Review_Button_Label", type="string", length=255, nullable=true)
     */
    protected $customReviewButtonLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="Custom_WQO_Agent", type="text", nullable=true)
     */
    protected $Custom_WQO_Agent;
    /**
     * @var string
     *
     * @ORM\Column(name="Custom_WQS_Agent", type="text", nullable=true)
     */
    protected $Custom_WQS_Agent;
    /**
     * @var string
     *
     * @ORM\Column(name="Hide_Buttons", type="string", length=5, nullable=true)
     */
    protected $hideButtons;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Hide_Buttons_When", type="text", nullable=true)
     */
    protected $customHideButtonsWhen;

    /**
     * @ORM\OneToMany(targetEntity="AppViews",mappedBy="datatableformproperties")
     * @var ArrayCollection
     */
    protected $dataTableViews;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Attachment_Section", type="boolean", options={"default":false})
     */
    protected $attachmentSection = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Email_Section", type="boolean", options={"default":false})
     */
    protected $emailSection = false;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Special_Editor_Sectoin", type="smallint", nullable=true)
     */
    protected $specialEditorSectoin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Modified", type="datetime")
     */
    protected $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id")
     */
    protected $modifiedBy;

    /**
     * @ORM\OneToOne(targetEntity="AppForms", inversedBy="formProperties")
     * @ORM\JoinColumn(name="App_Form_Id", referencedColumnName="id")
     */
    protected $appForm;


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
     * Set enableLifeCycle
     *
     * @param boolean $enableLifeCycle
     * @return AppFormProperties
     */
    public function setEnableLifeCycle($enableLifeCycle)
    {
        $this->enableLifeCycle = $enableLifeCycle;

        return $this;
    }

    /**
     * Get enableLifeCycle
     *
     * @return boolean 
     */
    public function getEnableLifeCycle()
    {
        return $this->enableLifeCycle;
    }


    /**
     * Add data table view
     *
     * @param \Docova\DocovaBundle\Entity\AppViews $view
     */
    public function addDataTableViews(\Docova\DocovaBundle\Entity\AppViews $view)
    {
        $this->dataTableViews[] = $view;
    }

    /**
     * Get views
     *
     * @return \Doctrine\Common\Collections\Collection $AppViews
     */
    public function getDataTableViews()
    {
        return $this->dataTableViews;
    }


    /**
     * Set initialStatus
     *
     * @param string $initialStatus
     * @return AppFormProperties
     */
    public function setInitialStatus($initialStatus)
    {
        $this->initialStatus = $initialStatus;

        return $this;
    }

    /**
     * Get initialStatus
     *
     * @return string 
     */
    public function getInitialStatus()
    {
        return $this->initialStatus;
    }

    /**
     * Set finalStatus
     *
     * @param string $finalStatus
     * @return AppFormProperties
     */
    public function setFinalStatus($finalStatus)
    {
        $this->finalStatus = $finalStatus;

        return $this;
    }

    /**
     * Get finalStatus
     *
     * @return string 
     */
    public function getFinalStatus()
    {
        return $this->finalStatus;
    }

    /**
     * Set supersededStatus
     *
     * @param string $supersededStatus
     * @return AppFormProperties
     */
    public function setSupersededStatus($supersededStatus)
    {
        $this->supersededStatus = $supersededStatus;

        return $this;
    }

    /**
     * Get supersededStatus
     *
     * @return string 
     */
    public function getSupersededStatus()
    {
        return $this->supersededStatus;
    }

    /**
     * Set discardedStatus
     *
     * @param string $discardedStatus
     * @return AppFormProperties
     */
    public function setDiscardedStatus($discardedStatus)
    {
        $this->discardedStatus = $discardedStatus;

        return $this;
    }

    /**
     * Get discardedStatus
     *
     * @return string 
     */
    public function getDiscardedStatus()
    {
        return $this->discardedStatus;
    }

    /**
     * Set archivedStatus
     *
     * @param string $archivedStatus
     * @return AppFormProperties
     */
    public function setArchivedStatus($archivedStatus)
    {
        $this->archivedStatus = $archivedStatus;

        return $this;
    }

    /**
     * Get archivedStatus
     *
     * @return string 
     */
    public function getArchivedStatus()
    {
        return $this->archivedStatus;
    }

    /**
     * Set deletedStatus
     *
     * @param string $deletedStatus
     * @return AppFormProperties
     */
    public function setDeletedStatus($deletedStatus)
    {
        $this->deletedStatus = $deletedStatus;

        return $this;
    }

    /**
     * Get deletedStatus
     *
     * @return string 
     */
    public function getDeletedStatus()
    {
        return $this->deletedStatus;
    }

    /**
     * Set enableVersions
     *
     * @param boolean $enableVersions
     * @return AppFormProperties
     */
    public function setEnableVersions($enableVersions)
    {
        $this->enableVersions = $enableVersions;

        return $this;
    }

    /**
     * Get enableVersions
     *
     * @return boolean 
     */
    public function getEnableVersions()
    {
        return $this->enableVersions;
    }

    /**
     * Set restrictLiveDrafts
     *
     * @param boolean $restrictLiveDrafts
     * @return AppFormProperties
     */
    public function setRestrictLiveDrafts($restrictLiveDrafts)
    {
        $this->restrictLiveDrafts = $restrictLiveDrafts;

        return $this;
    }

    /**
     * Get restrictLiveDrafts
     *
     * @return boolean 
     */
    public function getRestrictLiveDrafts()
    {
        return $this->restrictLiveDrafts;
    }

    /**
     * Set strictVersioning
     *
     * @param boolean $strictVersioning
     * @return AppFormProperties
     */
    public function setStrictVersioning($strictVersioning)
    {
        $this->strictVersioning = $strictVersioning;

        return $this;
    }

    /**
     * Get strictVersioning
     *
     * @return boolean 
     */
    public function getStrictVersioning()
    {
        return $this->strictVersioning;
    }

    /**
     * Set allowRetract
     *
     * @param boolean $allowRetract
     * @return AppFormProperties
     */
    public function setAllowRetract($allowRetract)
    {
        $this->allowRetract = $allowRetract;

        return $this;
    }

    /**
     * Get allowRetract
     *
     * @return boolean 
     */
    public function getAllowRetract()
    {
        return $this->allowRetract;
    }

    /**
     * Set restrictDrafts
     *
     * @param boolean $restrictDrafts
     * @return AppFormProperties
     */
    public function setRestrictDrafts($restrictDrafts)
    {
        $this->restrictDrafts = $restrictDrafts;

        return $this;
    }

    /**
     * Get restrictDrafts
     *
     * @return boolean 
     */
    public function getRestrictDrafts()
    {
        return $this->restrictDrafts;
    }

    /**
     * Set updateBookmarks
     *
     * @param boolean $updateBookmarks
     * @return AppFormProperties
     */
    public function setUpdateBookmarks($updateBookmarks)
    {
        $this->updateBookmarks = $updateBookmarks;

        return $this;
    }

    /**
     * Get updateBookmarks
     *
     * @return boolean 
     */
    public function getUpdateBookmarks()
    {
        return $this->updateBookmarks;
    }

    /**
     * Set attachmentOptions
     *
     * @param integer $attachmentOptions
     * @return AppFormProperties
     */
    public function setAttachmentOptions($attachmentOptions)
    {
        $this->attachmentOptions = $attachmentOptions;

        return $this;
    }

    /**
     * Get attachmentOptions
     *
     * @return integer 
     */
    public function getAttachmentOptions()
    {
        return $this->attachmentOptions;
    }

    /**
     * Set enableWorkflow
     *
     * @param boolean $enableWorkflow
     * @return AppFormProperties
     */
    public function setEnableWorkflow($enableWorkflow)
    {
        $this->enableWorkflow = $enableWorkflow;

        return $this;
    }

    /**
     * Get enableWorkflow
     *
     * @return boolean 
     */
    public function getEnableWorkflow()
    {
        return $this->enableWorkflow;
    }

    /**
     * Set workflowStyle
     * 
     * @param string $workflowStyle
     * @return AppFormProperties
     */
    public function setWorkflowStyle($workflowStyle = null)
    {
    	$this->workflowStyle = $workflowStyle;
    	
    	return $this;
    }

    /**
     * Get workflowStyle
     * 
     * @return string
     */
    public function getWorkflowStyle()
    {
    	return $this->workflowStyle;
    }

    /**
     * Set disableDeleteWorkflow
     *
     * @param boolean $disableDeleteWorkflow
     * @return AppFormProperties
     */
    public function setDisableDeleteWorkflow($disableDeleteWorkflow)
    {
        $this->disableDeleteWorkflow = $disableDeleteWorkflow;

        return $this;
    }

    /**
     * Get disableDeleteWorkflow
     *
     * @return boolean 
     */
    public function getDisableDeleteWorkflow()
    {
        return $this->disableDeleteWorkflow;
    }

    /**
     * Set customStartButtonLabel
     *
     * @param string $customStartButtonLabel
     * @return AppFormProperties
     */
    public function setCustomStartButtonLabel($customStartButtonLabel)
    {
        $this->customStartButtonLabel = $customStartButtonLabel;

        return $this;
    }

    /**
     * Get customStartButtonLabel
     *
     * @return string 
     */
    public function getCustomStartButtonLabel()
    {
        return $this->customStartButtonLabel;
    }

    /**
     * Set customReleaseButtonLabel
     *
     * @param string $customReleaseButtonLabel
     * @return AppFormProperties
     */
    public function setCustomReleaseButtonLabel($customReleaseButtonLabel)
    {
        $this->customReleaseButtonLabel = $customReleaseButtonLabel;

        return $this;
    }

    /**
     * Get customReleaseButtonLabel
     *
     * @return string 
     */
    public function getCustomReleaseButtonLabel()
    {
        return $this->customReleaseButtonLabel;
    }

    /**
     * Set customReviewButtonLabel
     *
     * @param string $customReviewButtonLabel
     * @return AppFormProperties
     */
    public function setCustomReviewButtonLabel($customReviewButtonLabel)
    {
        $this->customReviewButtonLabel = $customReviewButtonLabel;

        return $this;
    }

    /**
     * Get customReviewButtonLabel
     *
     * @return string 
     */
    public function getCustomReviewButtonLabel()
    {
        return $this->customReviewButtonLabel;
    }

    /**
     * Set hideButtons
     *
     * @param string $hideButtons
     * @return AppFormProperties
     */
    public function setHideButtons($hideButtons)
    {
        $this->hideButtons = $hideButtons;

        return $this;
    }

    /**
     * Get hideButtons
     *
     * @return string 
     */
    public function getHideButtons()
    {
        return $this->hideButtons;
    }

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return AppFormProperties
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
     * @return AppFormProperties
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
     * Set appForm
     *
     * @param \Docova\DocovaBundle\Entity\AppForms $appForm
     * @return AppFormProperties
     */
    public function setAppForm(\Docova\DocovaBundle\Entity\AppForms $appForm = null)
    {
        $this->appForm = $appForm;

        return $this;
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
     * Set customHideButtonsWhen
     *
     * @param string $customHideButtonsWhen
     * @return AppFormProperties
     */
    public function setCustomHideButtonsWhen($customHideButtonsWhen)
    {
        $this->customHideButtonsWhen = $customHideButtonsWhen;

        return $this;
    }

    /**
     * Get customHideButtonsWhen
     *
     * @return string 
     */
    public function getCustomHideButtonsWhen()
    {
        return $this->customHideButtonsWhen;
    }

    /**
     * Set attachmentSection
     *
     * @param boolean $attachmentSection
     * @return AppFormProperties
     */
    public function setAttachmentSection($attachmentSection)
    {
        $this->attachmentSection = $attachmentSection;

        return $this;
    }

    /**
     * Get attachmentSection
     *
     * @return boolean 
     */
    public function getAttachmentSection()
    {
    	//TODO FIX THIS Later
        return true;
       // return $this->attachmentSection;
    }

    /**
     * Set emailSection
     *
     * @param boolean $emailSection
     * @return AppFormProperties
     */
    public function setEmailSection($emailSection)
    {
        $this->emailSection = $emailSection;

        return $this;
    }

    /**
     * Get emailSection
     *
     * @return boolean 
     */
    public function getEmailSection()
    {
        return $this->emailSection;
    }

    /**
     * Set specialEditorSectoin
     *
     * @param integer $specialEditorSectoin
     * @return AppFormProperties
     */
    public function setSpecialEditorSectoin($specialEditorSectoin = null)
    {
        $this->specialEditorSectoin = $specialEditorSectoin;

        return $this;
    }

    /**
     * Get specialEditorSectoin
     *
     * @return integer 
     */
    public function getSpecialEditorSectoin()
    {
        return $this->specialEditorSectoin;
    }

    /**
     * Set Custom_JS_WF_Complete
     *
     * @param string $customJSWFComplete
     * @return AppFormProperties
     */
    public function setCustomJSWFComplete($customJSWFComplete)
    {
        $this->Custom_JS_WF_Complete = $customJSWFComplete;

        return $this;
    }

    /**
     * Get Custom_JS_WF_Complete
     *
     * @return string 
     */
    public function getCustomJSWFComplete()
    {
        return $this->Custom_JS_WF_Complete;
    }

    /**
     * Set Custom_JS_WF_Approve
     *
     * @param string $customJSWFApprove
     * @return AppFormProperties
     */
    public function setCustomJSWFApprove($customJSWFApprove)
    {
        $this->Custom_JS_WF_Approve = $customJSWFApprove;

        return $this;
    }

    /**
     * Get Custom_JS_WF_Approve
     *
     * @return string 
     */
    public function getCustomJSWFApprove()
    {
        return $this->Custom_JS_WF_Approve;
    }

    /**
     * Set Custom_JS_WF_Deny
     *
     * @param string $customJSWFDeny
     * @return AppFormProperties
     */
    public function setCustomJSWFDeny($customJSWFDeny)
    {
        $this->Custom_JS_WF_Deny = $customJSWFDeny;

        return $this;
    }

    /**
     * Get Custom_JS_WF_Deny
     *
     * @return string 
     */
    public function getCustomJSWFDeny()
    {
        return $this->Custom_JS_WF_Deny;
    }

    /**
     * Set Custom_JS_WF_Start
     *
     * @param string $customJSWFStart
     * @return AppFormProperties
     */
    public function setCustomJSWFStart($customJSWFStart)
    {
        $this->Custom_JS_WF_Start = $customJSWFStart;

        return $this;
    }

    /**
     * Get Custom_JS_WF_Start
     *
     * @return string 
     */
    public function getCustomJSWFStart()
    {
        return $this->Custom_JS_WF_Start;
    }
    
    
    /**
     * Set Custom_JS_Before_Release
     *
     * @param string $customJSBeforeRelease
     * @return AppFormProperties
     */
    public function setCustomJSBeforeRelease($customJSBeforeRelease)
    {
        $this->Custom_JS_Before_Release = $customJSBeforeRelease;
        
        return $this;
    }
    
    /**
     * Get Custom_JS_Before_Release
     *
     * @return string
     */
    public function getCustomJSBeforeRelease()
    {
        return $this->Custom_JS_Before_Release;
    }
    
    /**
     * Set Custom_JS_After_Release
     *
     * @param string $customJSAfterRelease
     * @return AppFormProperties
     */
    public function setCustomJSAfterRelease($customJSAfterRelease)
    {
        $this->Custom_JS_After_Release = $customJSAfterRelease;
        
        return $this;
    }
    
    /**
     * Get Custom_JS_After_Release
     *
     * @return string
     */
    public function getCustomJSAfterRelease()
    {
        return $this->Custom_JS_After_Release;
    }
    
    /**
     * Get Custom_WQO_Agent
     *
     * @return string
     */
    public function getCustomWqoAgent() {
        return $this->Custom_WQO_Agent;
    }
    
    /**
     * Set Custom_WQO_Agent
     *
     * @param string $Custom_WQO_Agent
     * @return DocumentTypes
     */
    public function setCustomWqoAgent($Custom_WQO_Agent = null)
    {
        $this->Custom_WQO_Agent = $Custom_WQO_Agent;
        return $this;
    }
    
    
    /**
     * Get Custom_WQS_Agent
     *
     * @return string
     */
    public function getCustomWqsAgent()
    {
        return $this->Custom_WQS_Agent;
    }
    
    /**
     * Set Custom_WQS_Agent
     *
     * @param string $Custom_WQS_Agent
     * @return DocumentTypes
     */
    public function setCustomWqsAgent($Custom_WQS_Agent = null)
    {
        $this->Custom_WQS_Agent = $Custom_WQS_Agent;
        return $this;
    }
    
}
