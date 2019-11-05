<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * DocWorkflowStepActions
 *
 * @ORM\Table(name="tb_docworkflow_step_actions")
 * @ORM\Entity
 */
class DocWorkflowStepActions
{
	const TYPE_1 = 'ACTIVATE';
	const TYPE_2 = 'COMPLETE';
	const TYPE_3 = 'DECLINE';
	const TYPE_4 = 'PAUSE';
	const TYPE_5 = 'CANCEL';
	const TYPE_6 = 'DELAY';
	const TYPE_7 = 'DELAYESCL';
	
	/**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="Action_Type", type="smallint")
     */
    protected $Action_Type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Send_Message", type="boolean")
     */
    protected $Send_Message = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="To_Options", type="smallint", nullable=true)
     */
    protected $To_Options;

    /**
     * @var integer
     *
     * @ORM\Column(name="Due_Days", type="smallint", nullable=true)
     */
    protected $Due_Days;
    
    /**
     * @var integer
     * 
     * @ORM\Column(name="Decline_Action", type="smallint", nullable=true)
     */
    protected $Decline_Action;

    /**
     * @var string
     * 
     * @ORM\Column(name="Back_Track_Step", type="string", length=255, nullable=true)
     */
    protected $Back_Track_Step;

    /**
     * @ORM\ManyToOne(targetEntity="DocumentWorkflowSteps", inversedBy="Actions")
     * @ORM\JoinColumn(name="Step_Id", referencedColumnName="id", nullable=false)
     */
    protected $Step;

    /**
     * @ORM\ManyToOne(targetEntity="SystemMessages")
     * @ORM\JoinColumn(name="Message_Id", referencedColumnName="id", nullable=true)
     */
    protected $Message;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_docstep_action_senders",
     * 		joinColumns={@ORM\JoinColumn(name="Message_Id", referencedColumnName="id", onDelete="CASCADE")},
     * 		inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id", onDelete="CASCADE")})
     */
    protected $Send_To;

    /**
     * @var array
     *
     * @ORM\Column(name="Sender_Groups", type="text", nullable=true)
     */
    protected $Sender_Groups;


    public function __construct()
    {
    	$this->Send_To = new ArrayCollection();
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
     * Set Action_Type
     *
     * @param integer $actionType
     * @return WorkflowStepActions
     */
    public function setActionType($actionType)
    {
        $this->Action_Type = $actionType;
    
        return $this;
    }

    /**
     * Get Action_Type
     *
     * @return integer 
     */
    public function getActionType($return_string = false)
    {
    	if ($return_string === false)
    	{
        	return $this->Action_Type;
    	}
    	else {
    		eval('$type = $this::TYPE_'.$this->Action_Type.';');
    		return $type;
    	}
    }

    /**
     * Set Send_Message
     *
     * @param boolean $sendMessage
     * @return WorkflowStepActions
     */
    public function setSendMessage($sendMessage)
    {
        $this->Send_Message = $sendMessage;
    
        return $this;
    }

    /**
     * Get Send_Message
     *
     * @return boolean 
     */
    public function getSendMessage()
    {
        return $this->Send_Message;
    }

    /**
     * Set To_Options
     *
     * @param integer $toOptions
     * @return WorkflowStepActions
     */
    public function setToOptions($toOptions)
    {
        $this->To_Options = $toOptions;
    
        return $this;
    }

    /**
     * Get To_Options
     *
     * @return integer 
     */
    public function getToOptions()
    {
        return $this->To_Options;
    }

    /**
     * Set Due_Days
     *
     * @param integer $dueDays
     * @return WorkflowStepActions
     */
    public function setDueDays($dueDays)
    {
        $this->Due_Days = $dueDays;
    
        return $this;
    }

    /**
     * Get Due_Days
     *
     * @return integer 
     */
    public function getDueDays()
    {
        return $this->Due_Days;
    }

    /**
     * Set Decline_Action
     * 
     * @param integer $declineAction
     * @return WorkflowStepActions
     */
    public function setDeclineAction($declineAction)
    {
    	$this->Decline_Action = $declineAction;
    	
    	return $this;
    }

    /**
     * Get Decline_Action
     * 
     * @return integer
     */
    public function getDeclineAction()
    {
    	return $this->Decline_Action;
    }

    /**
     * Set Back_Track_Step
     *
     * @param null|string $step
     * @return DocWorkflowStepActions
     */
    public function setBackTrackStep($step = null)
    {
    	$this->Back_Track_Step = $step;
    	
    	return $this;
    }
    
    /**
     * Get Back_Track_Step
     *
     * @return null|string
     */
    public function getBackTrackStep()
    {
    	return $this->Back_Track_Step;
    }

    /**
     * Set Step
     * 
     * @param \Docova\DocovaBundle\Entity\DocumentWorkflowSteps $step
     */
    public function setStep(\Docova\DocovaBundle\Entity\DocumentWorkflowSteps $step)
    {
    	$this->Step = $step;
    }

    /**
     * Get Step
     * 
     * @return \Docova\DocovaBundle\Entity\DocumentWorkflowSteps
     */
    public function getStep()
    {
    	return $this->Step;
    }

    /**
     * Set Message
     * 
     * @param \Docova\DocovaBundle\Entity\SystemMessages $message
     */
    public function setMessage(\Docova\DocovaBundle\Entity\SystemMessages $message = null)
    {
    	$this->Message = $message;
    }

    /**
     * Get Message
     * 
     * @return \Docova\DocovaBundle\Entity\SystemMessages
     */
    public function getMessage()
    {
    	return $this->Message;
    }

    /**
     * Add Send_To
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function addSendTo(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->Send_To[] = $user;
    }

    /**
     * Get Send_To
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSendTo()
    {
    	return $this->Send_To;
    }

    /**
     * Remove Send_To
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function removeSendTo(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->Send_To->remove($user);
    }

    /**
     * Set Sender_Groups
     *
     * @param array|string $senderGroups
     * @return DocWorkflowStepActions
     */
    public function setSenderGroups($senderGroups)
    {
    	if (!empty($senderGroups)) 
    	{
    		if (is_array($senderGroups)) 
    		{
    			$this->Sender_Groups = implode(',', $senderGroups);
    		}
    		else {
    			$this->Sender_Groups = $senderGroups;
    		}
    	}
    	else {
    		$this->Sender_Groups = null;
    	}
    
        return $this;
    }

    /**
     * Get Sender_Groups
     *
     * @return array 
     */
    public function getSenderGroups()
    {
    	if (!empty($this->Sender_Groups)) 
    	{
    		return explode(',', $this->Sender_Groups);
    	}
    	
        return array();
    }
}
