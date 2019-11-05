<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrashedLogs
 *
 * @ORM\Table(name="tb_trashed_logs")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\TrashedLogsRepository")
 */
class TrashedLogs
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
     * @ORM\Column(name="Log_Type", type="boolean")
     */
    protected $logType;

    /**
     * @var string
     *
     * @ORM\Column(name="Owner_Title", type="string", length=255)
     */
    protected $ownerTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="Parent_Folder", type="string", length=255, nullable=true)
     */
    protected $parentFolder;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="Parent_Library", referencedColumnName="id", nullable=false)
     */
    protected $parentLibrary;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @var string
     *
     * @ORM\Column(name="Log_Details", type="text")
     */
    protected $logDetails;


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
     * Set logType
     *
     * @param boolean $logType
     * @return TrashedLogs
     */
    public function setLogType($logType)
    {
        $this->logType = $logType;
    
        return $this;
    }

    /**
     * Get logType
     *
     * @return boolean 
     */
    public function getLogType()
    {
        return $this->logType;
    }

    /**
     * Set ownerTitle
     *
     * @param string $ownerTitle
     * @return TrashedLogs
     */
    public function setOwnerTitle($ownerTitle)
    {
        $this->ownerTitle = $ownerTitle;
    
        return $this;
    }

    /**
     * Get ownerTitle
     *
     * @return string 
     */
    public function getOwnerTitle()
    {
        return $this->ownerTitle;
    }

    /**
     * Set parentFolder
     *
     * @param string $parentFolder
     * @return TrashedLogs
     */
    public function setParentFolder($parentFolder)
    {
        $this->parentFolder = $parentFolder;
    
        return $this;
    }

    /**
     * Get parentFolder
     *
     * @return string 
     */
    public function getParentFolder()
    {
        return $this->parentFolder;
    }

    /**
     * Set parentFolder
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $parentFolder
     * @return TrashedLogs
     */
    public function setParentLibrary(\Docova\DocovaBundle\Entity\Libraries $parentLibrary)
    {
    	$this->parentLibrary = $parentLibrary;
    
    	return $this;
    }
    
    /**
     * Get parentFolder
     *
     * @return \Docova\DocovaBundle\Entity\Libraries
     */
    public function getParentLibrary()
    {
    	return $this->parentLibrary;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return TrashedLogs
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
    
        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set logDetails
     *
     * @param string $logDetails
     * @return TrashedLogs
     */
    public function setLogDetails($logDetails)
    {
        $this->logDetails = $logDetails;
    
        return $this;
    }

    /**
     * Get logDetails
     *
     * @return string 
     */
    public function getLogDetails()
    {
        return $this->logDetails;
    }
}
