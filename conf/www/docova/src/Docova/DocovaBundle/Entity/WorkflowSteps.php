<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * WorkflowSteps
 *
 * @ORM\Table(name="tb_workflow_steps")
 * @ORM\Entity
 */
class WorkflowSteps
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
     * @ORM\Column(name="Step_Name", type="string", length=255)
     */
    protected $Step_Name;

    /**
     * @var integer
     *
     * @ORM\Column(name="Step_Type", type="smallint")
     */
    protected $Step_Type;

    /**
     * @var string
     *
     * @ORM\Column(name="Doc_Status", type="string", length=100, nullable=true)
     */
    protected $Doc_Status;

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
     * @ORM\Column(name="Complete_By", type="smallint", nullable=true)
     */
    protected $Complete_By;

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
     * @var array
     * 
     * @ORM\Column(name="Participant_Groups", type="text", nullable=true)
     */
    protected $Other_Participant_Groups;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean")
     */
    protected $Trash = false;

    /**
     * @var string
     *
     * @ORM\Column(name="RuntimeJSON", type="text", nullable=true)
     */
    protected $RuntimeJSON;

    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_workflowsteps_users",
     *      joinColumns={@ORM\JoinColumn(name="Step_Id", referencedColumnName="id", nullable=true, onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id", nullable=true, onDelete="CASCADE")})
     */
    protected $Other_Participant;
    
    /**
     * @ORM\ManyToOne(targetEntity="Workflow", inversedBy="Steps")
     * @ORM\JoinColumn(name="Workflow_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $Workflow;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="WorkflowStepActions", mappedBy="Step")
     */
    protected $Actions;

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
     * Set Step_Name
     *
     * @param string $stepName
     * @return WorkflowSteps
     */
    public function setStepName($stepName)
    {
        $this->Step_Name = $stepName;
    
        return $this;
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
     * Get Step_Name
     *
     * @return string 
     */
    public function getStepName()
    {
        return $this->Step_Name;
    }

    /**
     * Set Step_Type
     *
     * @param integer $stepType
     * @return WorkflowSteps
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
     * @return integer | string
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
     * Set Doc_Status
     *
     * @param string $docStatus
     * @return WorkflowSteps
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
     * Set Position
     *
     * @param integer $position
     * @return WorkflowSteps
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
     * @return WorkflowSteps
     */
    public function setDistribution($distribution)
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
     * @return WorkflowSteps
     */
    public function setParticipants($participants)
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
     * Set Complete_By
     *
     * @param integer $completeBy
     * @return WorkflowSteps
     */
    public function setCompleteBy($completeBy)
    {
        $this->Complete_By = $completeBy;
    
        return $this;
    }

    /**
     * Get Complete_By
     *
     * @return integer 
     */
    public function getCompleteBy()
    {
        return $this->Complete_By;
    }

    /**
     * Set Optional_Comments
     *
     * @param boolean $optionalComments
     * @return WorkflowSteps
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
     * @return WorkflowSteps
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
     * @param boolean $visibility
     * @return WorkflowSteps
     */
    public function setHideReading($visibility)
    {
        $this->Hide_Reading = $visibility;
    
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
     * @param boolean $visibility
     * @return WorkflowSteps
     */
    public function setHideEditing($visibility)
    {
    	$this->Hide_Editing = $visibility;
    
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
     * @param boolean $visibility
     * @return WorkflowSteps
     */
    public function setHideCustom($visibility)
    {
    	$this->Hide_Custom = $visibility;
    
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
     * Set Trash
     *
     * @param boolean $trash
     * @return WorkflowSteps
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
     * Set Workflow
     * 
     * @param \Docova\DocovaBundle\Entity\Workflow $workflow
     */
    public function setWorkflow(\Docova\DocovaBundle\Entity\Workflow $workflow)
    {
    	$this->Workflow = $workflow;
    }

    /**
     * Get Workflow
     * 
     * @return \Docova\DocovaBundle\Entity\Workflow
     */
    public function getWorkflow()
    {
    	return $this->Workflow;
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
     * Set Custom_Review_Button_Label
     *
     * @param string $customReviewButtonLabel
     * @return WorkflowSteps
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
     * @return WorkflowSteps
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
     * @return WorkflowSteps
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
     * @return WorkflowSteps
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
     * Set Other_Participant_Groups
     *
     * @param array|string $participantGroups
     * @return WorkflowSteps
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

    /**
     * Add Actions
     *
     * @param \Docova\DocovaBundle\Entity\WorkflowStepActions $actions
     * @return WorkflowSteps
     */
    public function addAction(\Docova\DocovaBundle\Entity\WorkflowStepActions $actions)
    {
        $this->Actions[] = $actions;
    
        return $this;
    }

    /**
     * Remove Actions
     *
     * @param \Docova\DocovaBundle\Entity\WorkflowStepActions $actions
     */
    public function removeAction(\Docova\DocovaBundle\Entity\WorkflowStepActions $actions)
    {
        $this->Actions->removeElement($actions);
    }
}