<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activities
 *
 * @ORM\Table(name="tb_activities")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\ActivitiesRepository")
 */
class Activities
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
	 * @ORM\Column(name="Activity_Action", type="string", length=255)
	 */
	protected $activityAction;
	
	/**
	 * @var integer
	 *
	 * @ORM\Column(name="Activity_Obligation", type="smallint", options={"default":0})
	 */
	protected $activityObligation = 0;

	/**
	 * @var boolean
	 * 
	 * @ORM\Column(name="Activity_SendMessage", type="boolean", options={"default": false})
	 */
	protected $activitySendMessage = false;

	/**
	 * @var string
	 * 
	 * @ORM\Column(name="Activity_Subject", type="string", length=255, nullable=true)
	 */
	protected $activitySubject;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="Activity_Instruction", type="string", length=255, nullable=true)
	 */
	protected $activityInstruction;


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
	 * Get activityAction
	 * 
	 * @return string
	 */
	public function getActivityAction() 
	{
		return $this->activityAction;
	}
	
	/**
	 * Set activityAction
	 * 
	 * @param string $activityAction
	 * @return Activities        	
	 */
	public function setActivityAction( $activityAction) 
	{
		$this->activityAction = $activityAction;
		
		return $this;
	}
	
	/**
	 * Get activityObligation
	 * 
	 * @return integer
	 */
	public function getActivityObligation() 
	{
		return $this->activityObligation;
	}
	
	/**
	 * Set activityObligation
	 * 
	 * @param integer $activityObligation
	 * @return Activities
	 */
	public function setActivityObligation($activityObligation) 
	{
		$this->activityObligation = $activityObligation;
		
		return $this;
	}
	
	/**
	 * Get activityInstruction
	 * 
	 * @return string
	 */
	public function getActivityInstruction() 
	{
		return $this->activityInstruction;
	}
	
	/**
	 * Set activityInstruction
	 * 
	 * @param string $activityInstruction
	 * @return Activities   	
	 */
	public function setActivityInstruction($activityInstruction) 
	{
		$this->activityInstruction = $activityInstruction;
		
		return $this;
	}

    /**
     * Set activitySendMessage
     *
     * @param boolean $activitySendMessage
     * @return Activities
     */
    public function setActivitySendMessage($activitySendMessage)
    {
        $this->activitySendMessage = $activitySendMessage;
    
        return $this;
    }

    /**
     * Get activitySendMessage
     *
     * @return boolean 
     */
    public function getActivitySendMessage()
    {
        return $this->activitySendMessage;
    }

    /**
     * Set activitySubject
     *
     * @param string $activitySubject
     * @return Activities
     */
    public function setActivitySubject($activitySubject)
    {
        $this->activitySubject = $activitySubject;
    
        return $this;
    }

    /**
     * Get activitySubject
     *
     * @return string 
     */
    public function getActivitySubject()
    {
        return $this->activitySubject;
    }
}