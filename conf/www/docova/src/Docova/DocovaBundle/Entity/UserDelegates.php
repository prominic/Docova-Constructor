<?php
namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * UserDelegates
 *
 * @ORM\Table(name="tb_user_delegates")
 * @ORM\Entity
 */
class UserDelegates
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
     *
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Owner_Id", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Start_Date", type="datetime")
     */
    protected $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="End_Date", type="datetime")
     */
    protected $endDate;

    /**
     * @var string
     * 
     * @ORM\Column(name="Applicable_Workflows", type="string", nullable=true)
     */
    protected $applicableWorkflows;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Notifications", type="boolean", options={"default":false})
     */
    protected $notifications = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Review_Policies", type="boolean", options={"default":false})
     */
    protected $reviewPolicies = false;

    /**
     * @ORM\ManytoOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Delegated_User", referencedColumnName="id", nullable=false)
     */
    protected $delegatedUser;

    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="Libraries")
     * @ORM\JoinTable(name="tb_delegate_libraries")
     *      joinColumns={@ORM\JoinColumn(name="Delegate_Id", referencedColumnName="id", onDelete"CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Library_Id", referencedColumnName="id", onDelete="CASCADE")}
     */
    protected $applicableLibraries;

    public function __construct()
    {
    	$this->applicableLibraries = new ArrayCollection();
    }

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
     * Set owner
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $owner
     * @return UserDelegates
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return UserDelegates
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
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return UserDelegates
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set applicableWorkflows
     *
     * @param string $workflows
     * @return UserDelegates
     */
    public function setApplicableWorkflows($workflows = null)
    {
    	$this->applicableWorkflows = $workflows;
    
    	return $this;
    }
    
    /**
     * Get applicableWorkflows
     *
     * @return boolean
     */
    public function getApplicableWorkflows()
    {
    	return $this->applicableWorkflows;
    }

    /**
     * Set notifications
     *
     * @param boolean $notifications
     * @return UserDelegates
     */
    public function setNotifications($notifications)
    {
        $this->notifications = $notifications;

        return $this;
    }

    /**
     * Get notifications
     *
     * @return boolean 
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * Set reviewPolicies
     *
     * @param boolean $reviewPolicies
     * @return UserDelegates
     */
    public function setReviewPolicies($reviewPolicies)
    {
        $this->reviewPolicies = $reviewPolicies;

        return $this;
    }

    /**
     * Get reviewPolicies
     *
     * @return boolean 
     */
    public function getReviewPolicies()
    {
        return $this->reviewPolicies;
    }

    /**
     * Set delegatedUser
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function setDelegatedUser(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->delegatedUser = $user;
    }

    /**
     * Get delegatedUser
     * 
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getDelegatedUser()
    {
    	return $this->delegatedUser;
    }

    /**
     * Add applicableLibraries
     * 
     * @param \Docova\DocovaBundle\Entity\Libraries $library
     */
    public function addApplicableLibraries(\Docova\DocovaBundle\Entity\Libraries $library)
    {
    	$this->applicableLibraries[] = $library;
    }

    /**
     * Remove applicableLibraries
     * 
     * @param \Docova\DocovaBundle\Entity\Libraries $library
     */
    public function removeApplicableLibraries(\Docova\DocovaBundle\Entity\Libraries $library)
    {
    	$this->applicableLibraries->remove($library);
    }

    /**
     * Get applicableLibraries
     * 
     *  @return ArrayCollection
     */
    public function getApplicableLibraries()
    {
    	return $this->applicableLibraries;
    }
}
