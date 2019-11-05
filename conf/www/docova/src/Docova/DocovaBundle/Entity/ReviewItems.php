<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ReviewItems
 *
 * @ORM\Table(name="tb_review_items")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\ReviewItemsRepository")
 */
class ReviewItems
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="reviewItems")
     * @ORM\JoinColumn(name="Document_Id", referencedColumnName="id", nullable=false)
     */
    protected $Document;

    /**
     * @var string
     *
     * @ORM\Column(name="Title", type="string", length=255)
     */
    protected $title;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Start_Date", type="datetime")
     */
    protected $startDate;

    /**
     * @var string
     *
     * @ORM\Column(name="Status", type="string", length=100)
     */
    protected $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Due_Date", type="datetime")
     */
    protected $dueDate;

    /**
     * @ORM\ManyToOne(targetEntity="ReviewPolicies")
     * @ORM\JoinColumn(name="Policy_Id", referencedColumnName="id", nullable=true)
     */
    protected $reviewPolicy;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_pending_reviewers",
     *      joinColumns={@ORM\JoinColumn(name="Review_Item_Id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id", onDelete="CASCADE")})
     */
    protected $pendingReviewers;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_completed_reviewers",
     *      joinColumns={@ORM\JoinColumn(name="Review_Item_Id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id", onDelete="CASCADE")})
     */
    protected $completedReviewers;


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
     * Set Document
     * 
     * @param \Docova\DocovaBundle\Entity\Documents $Document
     * @return ReviewItems
     */
    public function setDocument(\Docova\DocovaBundle\Entity\Documents $Document) 
    {
    	$this->Document = $Document;
    	
    	return $this;
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
     * Set title
     *
     * @param string $title
     * @return ReviewItems
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return ReviewItems
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    
        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return ReviewItems
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set dueDate
     *
     * @param \DateTime $dueDate
     * @return ReviewItems
     */
    public function setDueDate($dueDate)
    {
        $this->dueDate = $dueDate;
    
        return $this;
    }

    /**
     * Get dueDate
     *
     * @return \DateTime 
     */
    public function getDueDate()
    {
        return $this->dueDate;
    }

    /**
     * Set reviewPolicy
     * 
     * @param \Docova\DocovaBundle\Entity\ReviewPolicies $reviewPolicy
     * @return ReviewItems
     */
    public function setReviewPolicy(\Docova\DocovaBundle\Entity\ReviewPolicies $reviewPolicy) 
    {
    	$this->reviewPolicy = $reviewPolicy;
    	
    	return $this;
    }

	/**
	 * Get reviewPolicy
	 * 
	 * @return \Docova\DocovaBundle\Entity\ReviewPolicies
	 */
	public function getReviewPolicy() 
	{
		return $this->reviewPolicy;
	}

	/**
	 * Add pendingReviewers
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $pendingReviewers
	 */
	public function addPendingReviewers(\Docova\DocovaBundle\Entity\UserAccounts $pendingReviewers) 
	{
		$this->pendingReviewers[] = $pendingReviewers;
	}
	
	/**
	 * Remove pendingReviewers
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $pendingReviewers
	 */
	public function removePendingReviewers(\Docova\DocovaBundle\Entity\UserAccounts $pendingReviewers)
	{
		$this->pendingReviewers->removeElement($pendingReviewers);
	}

	/**
	 * Get pendingReviewers
	 * 
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getPendingReviewers() 
	{
		return $this->pendingReviewers;
	}

	/**
	 * Set completedReviewers
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $completedReviewers
	 */
	public function addCompletedReviewers(\Docova\DocovaBundle\Entity\UserAccounts $completedReviewers) 
	{
		$this->completedReviewers[] = $completedReviewers;
	}
	
	/**
	 * Remove completedReviewers
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $completedReviewers
	 */
	public function removeCompletedReviewers(\Docova\DocovaBundle\Entity\UserAccounts $completedReviewers)
	{
		$this->completedReviewers->removeElement($completedReviewers);
	}

	/**
	 * Get completedReviewers
	 * 
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getCompletedReviewers() 
	{
		return $this->completedReviewers;
	}
}
