<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;

/**
 * SystemMessages
 *
 * @ORM\Table(name="tb_system_messages")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\SystemMessagesRepository")
 */
class SystemMessages
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
     * @ORM\Column(name="Message_Name", type="string", length=255)
     */
    protected $Message_Name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Link_Type", type="boolean")
     */
    protected $Link_Type;

    /**
     * @var string
     *
     * @ORM\Column(name="Subject_Line", type="string", length=255)
     */
    protected $subjectLine;

    /**
     * @var string
     *
     * @ORM\Column(name="Message_Content", type="text")
     */
    protected $messageContent;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Systemic", type="boolean")
     */
    protected $Systemic = false;

    /**
     * @var array
     * 
     * @ORM\Column(name="Sender_Groups", type="text", nullable=true)
     */
    protected $Sender_Groups;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean")
     */
    protected $Trash = false;
    
    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_system_messages_senders",
     *      joinColumns={@ORM\JoinColumn(name="Message_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id")})
     */
    protected $Senders;
    

    public function __construct()
    {
    	$this->Senders = new ArrayCollection();
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
     * Set Message_Name
     *
     * @param string $messageName
     * @return SystemMessages
     */
    public function setMessageName($messageName)
    {
        $this->Message_Name = $messageName;
    
        return $this;
    }

    /**
     * Get Message_Name
     *
     * @return string 
     */
    public function getMessageName()
    {
        return $this->Message_Name;
    }

    /**
     * Set Link_Type
     *
     * @param boolean $linkType
     * @return SystemMessages
     */
    public function setLinkType($linkType)
    {
        $this->Link_Type = $linkType;
    
        return $this;
    }

    /**
     * Get Link_Type
     *
     * @return boolean 
     */
    public function getLinkType()
    {
        return $this->Link_Type;
    }

    /**
     * Set subjectLine
     *
     * @param string $subjectLine
     * @return SystemMessages
     */
    public function setSubjectLine($subjectLine)
    {
        $this->subjectLine = $subjectLine;
    
        return $this;
    }

    /**
     * Get subjectLine
     *
     * @return string 
     */
    public function getSubjectLine()
    {
        return $this->subjectLine;
    }

    /**
     * Set messageContent
     *
     * @param string $messageContent
     * @return SystemMessages
     */
    public function setMessageContent($messageContent)
    {
        $this->messageContent = $messageContent;
    
        return $this;
    }

    /**
     * Get messageContent
     *
     * @return string 
     */
    public function getMessageContent()
    {
        return $this->messageContent;
    }

    /**
     * Set Systemic
     * 
     * @param boolean $is_systemic
     * @return SystemMessages
     */
    public function setSystemic($is_systemic)
    {
    	$this->Systemic = $is_systemic;
    	
    	return $this;
    }

    /**
     * Get Systemic
     * 
     * @return boolean
     */
    public function getSystemic()
    {
    	return $this->Systemic;
    }

    /**
     * Set Trash
     *
     * @param boolean $trash
     * @return SystemMessages
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
     * Add Senders
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $sender
     */
    public function addSenders(\Docova\DocovaBundle\Entity\UserAccounts $sender)
    {
    	$this->Senders[] = $sender;
    }

    /**
     * Get Senders
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSenders()
    {
    	return $this->Senders;
    }

    /**
     * Remove Senders
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $sender
     */
    public function removeSenders(\Docova\DocovaBundle\Entity\UserAccounts $sender)
    {
    	$this->Senders->removeElement($sender);
    }

    /**
     * Set Sender_Groups
     *
     * @param array|string $senderGroups
     * @return SystemMessages
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