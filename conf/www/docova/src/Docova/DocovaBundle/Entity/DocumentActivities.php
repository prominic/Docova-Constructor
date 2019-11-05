<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * DocumentActivities
 *
 * @ORM\Table(name="tb_document_activities")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DocumentActivitiesRepository")
 */
class DocumentActivities
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
     * @var boolean
     *
     * @ORM\Column(name="Ack_Required", type="boolean")
     */
    protected $ackRequired;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Request_Response", type="boolean")
     */
    protected $requestResponse;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Send_Email_Notification", type="boolean", options={"default": false})
     */
    protected $sendEmailNotification = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Subject", type="string", length=255)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="Message", type="text")
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(name="Response", type="text", nullable=true)
     */
    protected $response;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Acknowledged", type="boolean", options={"default" : false})
     */
    protected $acknowledged = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Is_Complete", type="boolean", options={"default" : false})
     */
    protected $isComplete = false;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Status_Date", type="datetime")
     */
    protected $statusDate;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="Activities")
     * @ORM\JoinColumn(name="Document_Id", referencedColumnName="id", nullable=false)
     */
    protected $document;

    /**
     * @var ArrayCollection
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Assignee", referencedColumnName="id", nullable=false)
     */
    protected $assignee;


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
     * Set activityAction
     *
     * @param string $activityAction
     * @return DocumentActivities
     */
    public function setActivityAction($activityAction)
    {
    	$this->activityAction = $activityAction;
    
    	return $this;
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
     * Set ackRequired
     *
     * @param boolean $ackRequired
     * @return DocumentActivities
     */
    public function setAckRequired($ackRequired)
    {
        $this->ackRequired = $ackRequired;
    
        return $this;
    }

    /**
     * Get ackRequired
     *
     * @return boolean 
     */
    public function getAckRequired()
    {
        return $this->ackRequired;
    }

    /**
     * Set requestResponse
     *
     * @param boolean $requestResponse
     * @return DocumentActivities
     */
    public function setRequestResponse($requestResponse)
    {
        $this->requestResponse = $requestResponse;
    
        return $this;
    }

    /**
     * Get requestResponse
     *
     * @return boolean 
     */
    public function getRequestResponse()
    {
        return $this->requestResponse;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return DocumentActivities
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    
        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return DocumentActivities
     */
    public function setMessage($message)
    {
        $this->message = $message;
    
        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set response
     *
     * @param string $response
     * @return DocumentActivities
     */
    public function setResponse($response)
    {
        $this->response = $response;
    
        return $this;
    }

    /**
     * Get response
     *
     * @return string 
     */
    public function getResponse()
    {
        return $this->response;
    }

	/**
	 * Set acknowledged
	 * 
	 * @param $acknowledged
	 * @return DocumentActivities
	 */
	public function setAcknowledged($acknowledged) 
	{
		$this->acknowledged = $acknowledged;
		
		return $this;
	}

	/**
	 * Get acknowledged
	 * 
	 * @return boolean
	 */
	public function getAcknowledged() 
	{
		return $this->acknowledged;
	}

    /**
     * Set isComplete
     *
     * @param boolean $isComplete
     * @return DocumentActivities
     */
    public function setIsComplete($isComplete)
    {
        $this->isComplete = $isComplete;
    
        return $this;
    }

    /**
     * Get isComplete
     *
     * @return boolean 
     */
    public function getIsComplete()
    {
        return $this->isComplete;
    }

    /**
     * Set createdBy
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $createdBy
     * @return DocumentActivities
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $createdBy) 
    {
    	$this->createdBy = $createdBy;
    	
    	return $this;
    }

	/**
	 * Get createdBy
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserAccounts
	 */
	public function getCreatedBy() 
	{
		return $this->createdBy;
	}
	
    /**
     * Set Document
     * 
     * @param \Docova\DocovaBundle\Entity\Documents $document
     */
    public function setDocument(\Docova\DocovaBundle\Entity\Documents $document)
    {
    	$this->document = $document;
    }

    /**
     * Get Document
     * 
     * @return \Docova\DocovaBundle\Entity\Documents
     */
    public function getDocument()
    {
    	return $this->document;
    }

    /**
     * Set assignee
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function setAssignee(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->assignee = $user;
    }
    
    /**
     * Get assignee
     * 
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getAssignee()
    {
    	return $this->assignee;
    }
    
    /**
     * Set statusDate
     *
     * @param \DateTime $statusDate
     * @return DocumentActivities
     */
    public function setStatusDate($statusDate)
    {
        $this->statusDate = $statusDate;
    
        return $this;
    }

    /**
     * Get statusDate
     *
     * @return \DateTime 
     */
    public function getStatusDate()
    {
        return $this->statusDate;
    }

    /**
     * Set sendEmailNotification
     *
     * @param boolean $sendEmailNotification
     * @return DocumentActivities
     */
    public function setSendEmailNotification($sendEmailNotification)
    {
        $this->sendEmailNotification = $sendEmailNotification;
    
        return $this;
    }

    /**
     * Get sendEmailNotification
     *
     * @return boolean 
     */
    public function getSendEmailNotification()
    {
        return $this->sendEmailNotification;
    }
}