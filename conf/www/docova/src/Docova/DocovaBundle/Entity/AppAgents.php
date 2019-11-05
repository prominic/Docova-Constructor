<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppAgents
 *
 * @ORM\Table(name="tb_app_agents")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AppAgentsRepository")
 */
class AppAgents
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
     * @var string
     *
     * @ORM\Column(name="Agent_Name", type="string", length=255)
     */
    protected $agentName;

    /**
     * @var string
     *
     * @ORM\Column(name="Agent_Alias", type="string", length=255, nullable=true)
     */
    protected $agentAlias;

    /**
     * @var boolean
     *
     * @ORM\Column(name="PDU", type="boolean")
     */
    protected $pDU = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Agent_Type", type="smallint", nullable=true)
     */
    protected $agentType;
    /**
     * @var string
     * 
     * @ORM\Column(name="Agent_Schedule", type="string", length=1)
     */
    protected $agentSchedule;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Start_Day_Of_Month", type="smallint", nullable=true)
     */
    protected $startDayOfMonth;

    /**
     * @var string
     * 
     * @ORM\Column(name="Start_Week_Day", type="string", length=15, nullable=true)
     */
    protected $startWeekDay;
    
    /**
     * @var integer
     * 
     * @ORM\Column(name="Start_Hour", type="smallint", nullable=true)
     */
    protected $startHour;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Start_Minutes", type="smallint", nullable=true)
     */
    protected $startMinutes;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Start_Hour_AM_PM", type="smallint", nullable=true)
     */
    protected $startHourAmPm;
    
    /**
     * @var integer
     * 
     * @ORM\Column(name="Interval_Hours", type="smallint", nullable=true)
     */
    protected $intervalHours;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Interval_Minutes", type="smallint", nullable=true)
     */
    protected $intervalMinutes;

    /**
     * @var string
     * 
     * @ORM\Column(name="Run_As", type="string", length=1, nullable=true)
     */
    protected $runAgentAs;
    
    /**
     * @var integer
     * 
     * @ORM\Column(name="Runtime_Security_Level", type="smallint", nullable=true)
     */
    protected $runtimeSecurityLevel;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Modified", type="datetime", nullable=true)
     */
    protected $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id", nullable=true)
     */
    protected $modifiedBy;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Last_Execution", type="datetime", nullable=true)
     */
    protected $lastExecution;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries", inversedBy="agents")
     * @ORM\JoinColumn(name="App_Id", referencedColumnName="id")
     */
    protected $application;
    

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
     * Set agentName
     *
     * @param string $agentName
     * @return AppAgents
     */
    public function setAgentName($agentName)
    {
        $this->agentName = $agentName;

        return $this;
    }

    /**
     * Get agentName
     *
     * @return string 
     */
    public function getAgentName()
    {
        return $this->agentName;
    }

    /**
     * Set agentAlias
     *
     * @param string $agentAlias
     * @return AppAgents
     */
    public function setAgentAlias($agentAlias)
    {
        $this->agentAlias = $agentAlias;

        return $this;
    }

    /**
     * Get agentAlias
     *
     * @return string 
     */
    public function getAgentAlias()
    {
        return $this->agentAlias;
    }

    /**
     * Set pDU
     *
     * @param boolean $pDU
     * @return AppAgents
     */
    public function setPDU($pDU)
    {
        $this->pDU = $pDU;

        return $this;
    }

    /**
     * Get pDU
     *
     * @return boolean 
     */
    public function getPDU()
    {
        return $this->pDU;
    }

    /**
     * Set agentType
     *
     * @param integer $agentType
     * @return AppAgents
     */
    public function setAgentType($agentType)
    {
        $this->agentType = $agentType;

        return $this;
    }

    /**
     * Get agentType
     *
     * @return integer 
     */
    public function getAgentType()
    {
        return $this->agentType;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return AppAgents
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
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return AppAgents
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
     * Set createdBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $createdBy
     * @return AppAgents
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $createdBy = null)
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
     * Set modifiedBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $modifiedBy
     * @return AppAgents
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
     * Set lastExecution
     *
     * @param \DateTime $lastExecution
     * @return AppAgents
     */
    public function setLastExecution($lastExecution = null)
    {
    	$this->lastExecution = $lastExecution;
    
    	return $this;
    }
    
    /**
     * Get lastExecution
     *
     * @return \DateTime
     */
    public function getLastExecution()
    {
    	return $this->lastExecution;
    }

    /**
     * Set application
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $application
     * @return AppAgents
     */
    public function setApplication(\Docova\DocovaBundle\Entity\Libraries $application = null)
    {
        $this->application = $application;

        return $this;
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
     * Set agentSchedule
     *
     * @param string $agentSchedule
     * @return AppAgents
     */
    public function setAgentSchedule($agentSchedule)
    {
        $this->agentSchedule = $agentSchedule;

        return $this;
    }

    /**
     * Get agentSchedule
     *
     * @return string 
     */
    public function getAgentSchedule()
    {
        return $this->agentSchedule;
    }

    /**
     * Set startDayOfMonth
     *
     * @param integer $startDayOfMonth
     * @return AppAgents
     */
    public function setStartDayOfMonth($startDayOfMonth)
    {
        $this->startDayOfMonth = $startDayOfMonth;

        return $this;
    }

    /**
     * Get startDayOfMonth
     *
     * @return integer 
     */
    public function getStartDayOfMonth()
    {
        return $this->startDayOfMonth;
    }

    /**
     * Set startWeekDay
     *
     * @param string $startWeekDay
     * @return AppAgents
     */
    public function setStartWeekDay($startWeekDay)
    {
        $this->startWeekDay = $startWeekDay;

        return $this;
    }

    /**
     * Get startWeekDay
     *
     * @return string 
     */
    public function getStartWeekDay()
    {
        return $this->startWeekDay;
    }

    /**
     * Set startHour
     *
     * @param integer $startHour
     * @return AppAgents
     */
    public function setStartHour($startHour)
    {
        $this->startHour = $startHour;

        return $this;
    }

    /**
     * Get startHour
     *
     * @return integer 
     */
    public function getStartHour()
    {
        return $this->startHour;
    }

    /**
     * Set startMinutes
     *
     * @param integer $startMinutes
     * @return AppAgents
     */
    public function setStartMinutes($startMinutes)
    {
        $this->startMinutes = $startMinutes;

        return $this;
    }

    /**
     * Get startMinutes
     *
     * @return integer 
     */
    public function getStartMinutes()
    {
        return $this->startMinutes;
    }

    /**
     * Set startHourAmPm
     *
     * @param integer $startHourAmPm
     * @return AppAgents
     */
    public function setStartHourAmPm($startHourAmPm)
    {
        $this->startHourAmPm = $startHourAmPm;

        return $this;
    }

    /**
     * Get startHourAmPm
     *
     * @return integer 
     */
    public function getStartHourAmPm()
    {
        return $this->startHourAmPm;
    }

    /**
     * Set intervalHours
     *
     * @param integer $intervalHours
     * @return AppAgents
     */
    public function setIntervalHours($intervalHours)
    {
        $this->intervalHours = $intervalHours;

        return $this;
    }

    /**
     * Get intervalHours
     *
     * @return integer 
     */
    public function getIntervalHours()
    {
        return $this->intervalHours;
    }

    /**
     * Set intervalMinutes
     *
     * @param integer $intervalMinutes
     * @return AppAgents
     */
    public function setIntervalMinutes($intervalMinutes)
    {
        $this->intervalMinutes = $intervalMinutes;

        return $this;
    }

    /**
     * Get intervalMinutes
     *
     * @return integer 
     */
    public function getIntervalMinutes()
    {
        return $this->intervalMinutes;
    }

    /**
     * Set runAgentAs
     *
     * @param string $runAgentAs
     * @return AppAgents
     */
    public function setRunAgentAs($runAgentAs)
    {
        $this->runAgentAs = $runAgentAs;

        return $this;
    }

    /**
     * Get runAgentAs
     *
     * @return string 
     */
    public function getRunAgentAs()
    {
        return $this->runAgentAs;
    }

    /**
     * Set runtimeSecurityLevel
     *
     * @param integer $runtimeSecurityLevel
     * @return AppAgents
     */
    public function setRuntimeSecurityLevel($runtimeSecurityLevel)
    {
        $this->runtimeSecurityLevel = $runtimeSecurityLevel;

        return $this;
    }

    /**
     * Get runtimeSecurityLevel
     *
     * @return integer 
     */
    public function getRuntimeSecurityLevel()
    {
        return $this->runtimeSecurityLevel;
    }
}
