<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentsLog
 *
 * @ORM\Table(name="tb_documents_log")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DocumentsLogRepository")
 */
class DocumentsLog
{
	const ACTION_2	= 'CREATE';
	const ACTION_4	= 'UPDATE';
	const ACTION_8	= 'DELETE';
	const ACTION_1	= 'UPDATE';
	const ACTION_3	= 'UPDATE';
	const ACTION_5	= 'WORKFLOW';
	const ACTION_6	= 'ARCHIVE';
	
	/**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="Log_Action", type="smallint")
     */
    protected $Log_Action;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Log_Status", type="boolean")
     */
    protected $Log_Status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Log_Date", type="datetime")
     */
    protected $Log_Date;

    /**
     * @var string
     *
     * @ORM\Column(name="Log_Details", type="text")
     */
    protected $Log_Details;
    
    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="Logs")
     * @ORM\JoinColumn(name="Document_Id", referencedColumnName="id", nullable=false)
     */
    protected $Document;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Log_Author", referencedColumnName="id", nullable=true)
     */
    protected $Log_Author;


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
     * Set Log_Action
     *
     * @param integer $logAction
     * @return DocumentsLog
     */
    public function setLogAction($logAction)
    {
        $this->Log_Action = $logAction;
    
        return $this;
    }

    /**
     * Get Log_Action
     *
     * @return string
     */
    public function getLogAction()
    {
        eval("\$action = self::ACTION_".$this->Log_Action.';');
        return $action;
    }

    /**
     * Set Log_Status
     *
     * @param boolean $logStatus
     * @return DocumentsLog
     */
    public function setLogStatus($logStatus)
    {
        $this->Log_Status = $logStatus;
    
        return $this;
    }

    /**
     * Get Log_Status
     *
     * @return boolean 
     */
    public function getLogStatus()
    {
        return $this->Log_Status;
    }

    /**
     * Set Log_Date
     *
     * @param \DateTime $logDate
     * @return DocumentsLog
     */
    public function setLogDate($logDate)
    {
        $this->Log_Date = $logDate;
    
        return $this;
    }

    /**
     * Get Log_Date
     *
     * @return \DateTime 
     */
    public function getLogDate()
    {
        return $this->Log_Date;
    }

    /**
     * Set Log_Details
     *
     * @param string $logDetails
     * @return DocumentsLog
     */
    public function setLogDetails($logDetails)
    {
        $this->Log_Details = $logDetails;
    
        return $this;
    }

    /**
     * Get Log_Details
     *
     * @return string 
     */
    public function getLogDetails()
    {
        return $this->Log_Details;
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
     * Set Log_Author
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts|null $user
     */
    public function setLogAuthor(\Docova\DocovaBundle\Entity\UserAccounts $user = null)
    {
    	$this->Log_Author = $user;
    }

    /**
     * Get Log_Author
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getLogAuthor()
    {
    	return $this->Log_Author;
    }
}
