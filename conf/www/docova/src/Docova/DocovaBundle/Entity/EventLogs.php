<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EventLogs
 *
 * @ORM\Table(name="tb_event_logs")
 * @ORM\Entity
 */
class EventLogs
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Agent_Name", type="string", length=100)
     */
    private $agentName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Event_Date", type="datetime")
     */
    private $eventDate;

    /**
     * @var string
     *
     * @ORM\Column(name="Server", type="string", length=100, nullable=true)
     */
    private $server;

    /**
     * @var string
     *
     * @ORM\Column(name="Details", type="text")
     */
    private $details;


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
     * Set agentName
     *
     * @param string $agentName
     * @return EventLogs
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
     * Set eventDate
     *
     * @param \DateTime $eventDate
     * @return EventLogs
     */
    public function setEventDate($eventDate)
    {
        $this->eventDate = $eventDate;
    
        return $this;
    }

    /**
     * Get eventDate
     *
     * @return \DateTime 
     */
    public function getEventDate()
    {
        return $this->eventDate;
    }

    /**
     * Set server
     *
     * @param string $server
     * @return EventLogs
     */
    public function setServer($server)
    {
        $this->server = $server;
    
        return $this;
    }

    /**
     * Get server
     *
     * @return string 
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set details
     *
     * @param string $details
     * @return EventLogs
     */
    public function setDetails($details)
    {
        $this->details = $details;
    
        return $this;
    }

    /**
     * Get details
     *
     * @return string 
     */
    public function getDetails()
    {
        return $this->details;
    }
}
