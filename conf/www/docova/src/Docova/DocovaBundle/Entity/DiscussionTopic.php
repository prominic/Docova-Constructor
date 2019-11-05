<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * DiscussionTopic
 *
 * @ORM\Table(name="tb_discussion_topic")
 * @ORM\Entity
 */
class DiscussionTopic
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
     * @ORM\Column(name="Subject", type="string", length=255)
     */
    protected $subject;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @var string
     *
     * @ORM\Column(name="Body", type="text", nullable=true)
     */
    protected $body;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="discussion")
     * @ORM\JoinColumn(name="Parent_Document", referencedColumnName="id", nullable=false)
     */
    protected $parentDocument;

    /**
     * @ORM\ManyToOne(targetEntity="DiscussionTopic", inversedBy="childTopics")
     * @ORM\JoinColumn(name="Parent_Thread", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $parentDiscussion;

    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="DiscussionTopic", mappedBy="parentDiscussion")
     */
    protected $childTopics;

    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="DiscussionAttachments", mappedBy="discussion")
     */
    protected $attachments;


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
     * Set subject
     *
     * @param string $subject
     * @return DiscussionTopic
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
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return DiscussionTopic
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
     * Set body
     *
     * @param string $body
     * @return DiscussionTopic
     */
    public function setBody($body)
    {
        $this->body = $body;
    
        return $this;
    }

    /**
     * Get body
     *
     * @return string 
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set createdBy
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $createdBy
     * @return DiscussionTopic
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $createdBy) 
    {
    	$this->createdBy = $createdBy;
    	
    	return $this;
    }

	/**
	 * Get createBy
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserAccounts
	 */
	public function getCreatedBy() 
	{
		return $this->createdBy;
	}

	/**
	 * Set parentDocument
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $parentDocument
	 */
	public function setParentDocument(\Docova\DocovaBundle\Entity\Documents $parentDocument) 
	{
		$this->parentDocument = $parentDocument;
	}

	/**
	 * Get parentDocument
	 * 
	 * @return \Docova\DocovaBundle\Entity\Documents
	 */
	public function getParentDocument() 
	{
		return $this->parentDocument;
	}

	/**
	 * Set parentDiscussion
	 * 
	 * @param \Docova\DocovaBundle\Entity\DiscussionTopic $parentDiscussion
	 */
	public function setParentDiscussion(\Docova\DocovaBundle\Entity\DiscussionTopic $parentDiscussion) 
	{
		$this->parentDiscussion = $parentDiscussion;
	}

	/**
	 * Get parentDiscussion
	 * 
	 * @return \Docova\DocovaBundle\Entity\DiscussionTopic
	 */
	public function getParentDiscussion() 
	{
		return $this->parentDiscussion;
	}
	
	/**
	 * Get indent for a discussion thread
	 * 
	 * @return number
	 */
	public function getIndent()
	{
		while ($this->getParentDiscussion()) {
			return $this->getParentDiscussion()->getIndent() + 1;
		}
		return 0;
	}
	
	/**
	 * Get attachments
	 * 
	 * @return ArrayCollection
	 */
	public function getAttachments()
	{
		return $this->attachments;
	}
	
	/**
	 * Get childTopics
	 * 
	 * @return ArrayCollection
	 */
	public function getChildTopics()
	{
		return $this->childTopics;
	}
}
