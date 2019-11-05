<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentWorkflowSteps
 *
 * @ORM\Table(name="tb_document_workflow_steps")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DocumentWorkflowStepsRepository")
 */
class DocumentWorkflowSteps
{
	const TYPE_1 = 'Start';
	const TYPE_2 = 'Review';
	const TYPE_3 = 'Approve';
	const TYPE_4 = 'End';
    const TYPE_5 = 'Stop';
	/**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Status", type="string", length=50)
     */
    protected $Status;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Started", type="datetime", nullable=true)
     */
    protected $Date_Started;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Completed", type="datetime", nullable=true)
     */
    protected $Date_Completed;
    
    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="DocSteps")
     * @ORM\JoinColumn(name="Document_Id", referencedColumnName="id", nullable=true)
     */
    protected $Document;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="WorkflowAssignee", mappedBy="DocWorkflowStep")
     */
    protected $assignee;

    /**
     * @var array
     * 
     * @ORM\Column(name="Assignee_Group", type="text", nullable=true)
     */
    protected $Assignee_Group;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="WorkflowCompletedBy", mappedBy="DocWorkflowStep")
     */
    protected $completedBy;

    /**
     * @var string
     * 
     * @ORM\Column(name="Workflow_Name", type="string", length=255)
     */
    protected $Workflow_Name;

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
     * @var string
     *
     * @ORM\Column(name="Step_Name", type="string", length=255)
     */
    protected $Step_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Doc_Status", type="string", length=100, nullable=true)
     */
    protected $Doc_Status;

    /**
     * @var integer
     *
     * @ORM\Column(name="Step_Type", type="smallint")
     */
    protected $Step_Type;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="Position", type="smallint", nullable=true)
     */
    protected $Position;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="Distribution", type="smallint", nullable=true)
     */
    protected $Distribution;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="Participants", type="smallint", nullable=true)
     */
    protected $Participants;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="Complete_On", type="smallint", nullable=true)
     */
    protected $Complete_On;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Optional_Comments", type="boolean")
     */
    protected $Optional_Comments = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Approver_Edit", type="boolean")
     */
    protected $Approver_Edit = false;


    /**
     * @var boolean
     *
     * @ORM\Column(name="IsCurrentStep", type="boolean",  nullable=true)
     */
    protected $IsCurrentStep = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Hide_Reading", type="boolean")
     */
    protected $Hide_Reading = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Hide_Editing", type="boolean")
     */
    protected $Hide_Editing = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Hide_Custom", type="boolean")
     */
    protected $Hide_Custom = false;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Custom_Review_Button_Label", type="string", length=255, nullable=true)
     */
    protected $Custom_Review_Button_Label;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Custom_Approve_Button_Label", type="string", length=255, nullable=true)
     */
    protected $Custom_Approve_Button_Label;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Custom_Decline_Button_Label", type="string", length=255, nullable=true)
     */
    protected $Custom_Decline_Button_Label;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Custom_Release_Button_Label", type="string", length=255, nullable=true)
     */
    protected $Custom_Release_Button_Label;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DocWorkflowStepActions", mappedBy="Step", cascade={"remove"})
     */
    protected $Actions;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_docworkflowsteps_users",
     *      joinColumns={@ORM\JoinColumn(name="Step_Id", referencedColumnName="id", nullable=true, onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id", nullable=true, onDelete="CASCADE")})
     */
    protected $Other_Participant;

    /**
     * @var array
     * 
     * @ORM\Column(name="Participant_Groups", type="text", nullable=true)
     */
    protected $Other_Participant_Groups;


    /**
     * @var string
     *
     * @ORM\Column(name="RuntimeJSON", type="text", nullable=true)
     */
    protected $RuntimeJSON;

    /**
     * @var string
     * 
     * @ORM\Column(name="wfStepTokens", type="string", length=255, nullable=true)
     */
    protected $Participant_Tokens;

     /**
     * @var string
     * 
     * @ORM\Column(name="wfParticipantFormula", type="text",  nullable=true)
     */
    protected $Participant_Formula;

    

    public function __construct()
    {
    	$this->Other_Participant = new ArrayCollection();
    	$this->Actions = new ArrayCollection();
    }

    public function getParticipantTokens()
    {
        return $this->Participant_Tokens;
    }

    public function setParticipantTokens($tokenlist)
    {
        $this->Participant_Tokens = $tokenlist;
    }

     public function getParticipantFormula()
    {
        return $this->Participant_Formula;
    }

    public function setParticipantFormula($formula)
    {
        $this->Participant_Formula = $formula;
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
     * Get RuntimeJSON
     *
     * @return string 
     */
    public function getRuntimeJSON()
    {
        return $this->RuntimeJSON;
    }

    /**
     * Set RuntimeJSON
     *
     * @return string 
     */
    public function setRuntimeJSON($json)
    {
       $this->RuntimeJSON = $json;
    }

    /**
     * Get id
     *
     * @return string 
     */
    public function getIsCurrentStep()
    {
        return $this->IsCurrentStep;
    }

    /**
     * Get id
     *
     * @return string 
     */
    public function setIsCurrentStep($boolval)
    {
        $this->IsCurrentStep = $boolval;
    }

    /**
     * Set Status
     *
     * @param string $status
     * @return DocumentWorkflowSteps
     */
    public function setStatus($status)
    {
        $this->Status = $status;
    
        return $this;
    }

    /**
     * Get Status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->Status;
    }
	
    /**
     * Set Date_Started
     *
     * @param \DateTime $date_started
     * @return DocumentWorkflowSteps
     */
    public function setDateStarted($date_started)
    {
    	$this->Date_Started = $date_started;
    	 
    	return $this;
    }
    
    /**
     * Get Date_Started
     *
     * @return \DateTime
     */
    public function getDateStarted()
    {
    	return $this->Date_Started;
    }

    /**
     * Set Date_Completed
     * 
     * @param \DateTime $date_completed
     * @return DocumentWorkflowSteps
     */
    public function setDateCompleted($date_completed)
    {
    	$this->Date_Completed = $date_completed;
    	
    	return $this;
    }

    /**
     * Get Date_Completed
     * 
     * @return \DateTime
     */
    public function getDateCompleted()
    {
    	return $this->Date_Completed;
    }

    /**
     * Set Document
     * 
     * @param \Docova\DocovaBundle\Entity\Documents $document
     */
    public function setDocument(\Docova\DocovaBundle\Entity\Documents $document)
    {
    	$this->Document = $document;
    }

    /**
     * Get Document
     * 
     * @return \Docova\DocovaBundle\Entity\Documents
     */
    public function getDocument()
    {
    	return $this->Document;
    }

    /**
     * Get assignees
     * 
     * @return ArrayCollection
     */
    public function getAssignee()
    {
    	return $this->assignee;
    }

    /**
     * Set Assignee_Group
     *
     * @param array|string $assigneeGroup
     * @return DocumentWorkflowSteps
     */
    public function setAssigneeGroup($assigneeGroup)
    {
    	if (!empty($assigneeGroup))
    	{
    		if (is_array($assigneeGroup))
    		{
    			$this->Assignee_Group = implode(',', $assigneeGroup);
    		}
    		else {
    			$this->Assignee_Group = $assigneeGroup;
    		}
    	}
    	else {
    		$this->Assignee_Group = null;
    	}
    
    	return $this;
    }
    
    /**
     * Get Assignee_Group
     *
     * @return array
     */
    public function getAssigneeGroup()
    {
    	if (!empty($this->Assignee_Group))
    	{
    		return explode(',', $this->Assignee_Group);
    	}
    
    	return array();
    }

    /**
     * Get completedBy
     * 
     * @return ArrayCollection
     */
    public function getCompletedBy()
    {
    	return $this->completedBy;
    }

    /**
     * Set Workflow_Name
     *
     * @param string $workflowName
     * @return DocumentWorkflowSteps
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
     * Set Author_Customization
     *
     * @param boolean $authorCustomization
     * @return DocumentWorkflowSteps
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
     * @return DocumentWorkflowSteps
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
     * Set Step_Name
     *
     * @param string $stepName
     * @return DocumentWorkflowSteps
     */
    public function setStepName($stepName)
    {
        $this->Step_Name = $stepName;
    
        return $this;
    }

    /**
     * Get Step_Name
     *
     * @return string 
     */
    public function getStepName()
    {
        return $this->Step_Name;
    }

    /**
     * Set Doc_Status
     *
     * @param string $docStatus
     * @return DocumentWorkflowSteps
     */
    public function setDocStatus($docStatus = null)
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
     * Set Step_Type
     *
     * @param integer $stepType
     * @return DocumentWorkflowSteps
     */
    public function setStepType($stepType)
    {
        $this->Step_Type = $stepType;
    
        return $this;
    }

    /**
     * Get Step_Type
     *
     * @param boolean $to_string
     * @return integer|string 
     */
    public function getStepType($to_string = false)
    {
    	if ($to_string === false)
    	{
        	return $this->Step_Type;
    	}
    	else 
    	{
    		eval("\$step_type = self::TYPE_".$this->Step_Type.';');
    		return $step_type;
    	}
    }

    /**
     * Set Position
     *
     * @param integer $position
     * @return DocumentWorkflowSteps
     */
    public function setPosition($position)
    {
        $this->Position = $position;
    
        return $this;
    }

    /**
     * Get Position
     *
     * @return integer 
     */
    public function getPosition()
    {
        return $this->Position;
    }

    /**
     * Set Distribution
     *
     * @param integer $distribution
     * @return DocumentWorkflowSteps
     */
    public function setDistribution($distribution = null)
    {
        $this->Distribution = $distribution;
    
        return $this;
    }

    /**
     * Get Distribution
     *
     * @return integer 
     */
    public function getDistribution()
    {
        return $this->Distribution;
    }

    /**
     * Set Participants
     *
     * @param integer $participants
     * @return DocumentWorkflowSteps
     */
    public function setParticipants($participants = null)
    {
        $this->Participants = $participants;
    
        return $this;
    }

    /**
     * Get Participants
     *
     * @return integer 
     */
    public function getParticipants()
    {
        return $this->Participants;
    }

    /**
     * Set Complete_On
     *
     * @param integer $completeOn
     * @return DocumentWorkflowSteps
     */
    public function setCompleteOn($completeOn = null)
    {
        $this->Complete_On = $completeOn;
    
        return $this;
    }

    /**
     * Get Complete_On
     *
     * @return integer 
     */
    public function getCompleteOn()
    {
        return $this->Complete_On;
    }

    /**
     * Set Optional_Comments
     *
     * @param boolean $optionalComments
     * @return DocumentWorkflowSteps
     */
    public function setOptionalComments($optionalComments)
    {
        $this->Optional_Comments = $optionalComments;
    
        return $this;
    }

    /**
     * Get Optional_Comments
     *
     * @return boolean 
     */
    public function getOptionalComments()
    {
        return $this->Optional_Comments;
    }

    /**
     * Set Approver_Edit
     *
     * @param boolean $approverEdit
     * @return DocumentWorkflowSteps
     */
    public function setApproverEdit($approverEdit)
    {
        $this->Approver_Edit = $approverEdit;
    
        return $this;
    }

    /**
     * Get Approver_Edit
     *
     * @return boolean 
     */
    public function getApproverEdit()
    {
        return $this->Approver_Edit;
    }

    /**
     * Set Hide_Reading
     *
     * @param boolean $hideReading
     * @return DocumentWorkflowSteps
     */
    public function setHideReading($hideReading)
    {
        $this->Hide_Reading = $hideReading;
    
        return $this;
    }

    /**
     * Get Hide_Reading
     *
     * @return boolean 
     */
    public function getHideReading()
    {
        return $this->Hide_Reading;
    }

    /**
     * Set Hide_Editing
     *
     * @param boolean $hideEditing
     * @return DocumentWorkflowSteps
     */
    public function setHideEditing($hideEditing)
    {
        $this->Hide_Editing = $hideEditing;
    
        return $this;
    }

    /**
     * Get Hide_Editing
     *
     * @return boolean 
     */
    public function getHideEditing()
    {
        return $this->Hide_Editing;
    }

    /**
     * Set Hide_Custom
     *
     * @param boolean $hideCustom
     * @return DocumentWorkflowSteps
     */
    public function setHideCustom($hideCustom)
    {
        $this->Hide_Custom = $hideCustom;
    
        return $this;
    }

    /**
     * Get Hide_Custom
     *
     * @return boolean 
     */
    public function getHideCustom()
    {
        return $this->Hide_Custom;
    }

    /**
     * Set Custom_Review_Button_Label
     *
     * @param string $customReviewButtonLabel
     * @return DocumentWorkflowSteps
     */
    public function setCustomReviewButtonLabel($customReviewButtonLabel = null)
    {
        $this->Custom_Review_Button_Label = $customReviewButtonLabel;
    
        return $this;
    }

    /**
     * Get Custom_Review_Button_Label
     *
     * @return string 
     */
    public function getCustomReviewButtonLabel()
    {
        return $this->Custom_Review_Button_Label;
    }

    /**
     * Set Custom_Approve_Button_Label
     *
     * @param string $customApproveButtonLabel
     * @return DocumentWorkflowSteps
     */
    public function setCustomApproveButtonLabel($customApproveButtonLabel = null)
    {
        $this->Custom_Approve_Button_Label = $customApproveButtonLabel;
    
        return $this;
    }

    /**
     * Get Custom_Approve_Button_Label
     *
     * @return string 
     */
    public function getCustomApproveButtonLabel()
    {
        return $this->Custom_Approve_Button_Label;
    }

    /**
     * Set Custom_Decline_Button_Label
     *
     * @param string $customDeclineButtonLabel
     * @return DocumentWorkflowSteps
     */
    public function setCustomDeclineButtonLabel($customDeclineButtonLabel = null)
    {
        $this->Custom_Decline_Button_Label = $customDeclineButtonLabel;
    
        return $this;
    }

    /**
     * Get Custom_Decline_Button_Label
     *
     * @return string 
     */
    public function getCustomDeclineButtonLabel()
    {
        return $this->Custom_Decline_Button_Label;
    }

    /**
     * Set Custom_Release_Button_Label
     *
     * @param string $customReleaseButtonLabel
     * @return DocumentWorkflowSteps
     */
    public function setCustomReleaseButtonLabel($customReleaseButtonLabel = null)
    {
        $this->Custom_Release_Button_Label = $customReleaseButtonLabel;
    
        return $this;
    }

    /**
     * Get Custom_Release_Button_Label
     *
     * @return string 
     */
    public function getCustomReleaseButtonLabel()
    {
        return $this->Custom_Release_Button_Label;
    }

    /**
     * Get Actions
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getActions()
    {
    	return $this->Actions;
    }

    /**
     * Add Actions
     *
     * @param \Docova\DocovaBundle\Entity\DocWorkflowStepActions $actions
     * @return WorkflowSteps
     */
    public function addAction(\Docova\DocovaBundle\Entity\DocWorkflowStepActions $actions)
    {
    	$this->Actions[] = $actions;
    
    	return $this;
    }
    
    /**
     * Remove Actions
     *
     * @param \Docova\DocovaBundle\Entity\DocWorkflowStepActions $actions
     */
    public function removeAction(\Docova\DocovaBundle\Entity\DocWorkflowStepActions $actions)
    {
    	$this->Actions->removeElement($actions);
    }

    /**
     * Add Other_Participant
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function addOtherParticipant(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->Other_Participant[] = $user;
    }
    
    /**
     * Get Other_Participant
     *
     * @return ArrayCollection
     */
    public function getOtherParticipant()
    {
    	return $this->Other_Participant;
    }
    
    /**
     * Remove Other_Participant
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function removeOtherParticipant(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->Other_Participant->removeElement($user);
    }

    /**
     * Set Other_Participant_Groups
     *
     * @param array|string $participantGroups
     * @return DocumentWorkflowSteps
     */
    public function setOtherParticipantGroups($participantGroups)
    {
    	if (!empty($participantGroups))
    	{
    		if (is_array($participantGroups))
    		{
    			$this->Other_Participant_Groups = implode(',', $participantGroups);
    		}
    		else {
    			$this->Other_Participant_Groups = $participantGroups;
    		}
    	}
    	else {
    		$this->Other_Participant_Groups = null;
    	}
    
    	return $this;
    }
    
    /**
     * Get Other_Participant_Groups
     *
     * @return array
     */
    public function getOtherParticipantGroups()
    {
    	if (!empty($this->Other_Participant_Groups))
    	{
    		return explode(',', $this->Other_Participant_Groups);
    	}
    
    	return array();
    }
}