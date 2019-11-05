<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ReviewPolicies
 *
 * @ORM\Table(name="tb_review_policies")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\ReviewPoliciesRepository")
 */
class ReviewPolicies
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
     * @ORM\Column(name="Policy_Name", type="string", length=255)
     */
    protected $policyName;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Policy_Status", type="boolean", options={"default":true})
     */
    protected $policyStatus = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="Policy_Priority", type="smallint", options={"default":2})
     */
    protected $policyPriority = 2;

    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="Libraries")
     * @ORM\JoinTable(name="tb_review_policies_libraries",
     *      joinColumns={@ORM\JoinColumn(name="Review_Policy_Id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Library_Id", referencedColumnName="id", onDelete="CASCADE")})
     */
    protected $libraries;

    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="DocumentTypes")
     * @ORM\JoinTable(name="tb_review_policies_document_types",
     *      joinColumns={@ORM\JoinColumn(name="Review_Policy_Id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Doc_Type_Id", referencedColumnName="id", onDelete="CASCADE")})
     */
    protected $documentTypes;

    /**
     * @var integer
     *
     * @ORM\Column(name="Document_Status", type="smallint", options={"default":1})
     */
    protected $documentStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="Review_Custom_Formula", type="text", nullable=true)
     */
    protected $reviewCustomFormula;

    /**
     * @var string
     *
     * @ORM\Column(name="Review_Period_Option", type="string", length=1, options={"default":"Y"})
     */
    protected $reviewPeriodOption = 'Y';

    /**
     * @var integer
     *
     * @ORM\Column(name="Review_Period", type="smallint", options={"default":1})
     */
    protected $reviewPeriod = 1;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Review_Period", type="string", length=255, nullable=true)
     */
    protected $customReviewPeriod;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Review_Period_Option", type="string", length=255, nullable=true)
     */
    protected $customReviewPeriodOption;

    /**
     * @var string
     *
     * @ORM\Column(name="Review_Date_Select", type="string", length=50, options={"default":"LastReviewDate"})
     */
    protected $reviewDateSelect = 'LastReviewDate';

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Review_Date_Select", type="string", length=255, nullable=true)
     */
    protected $customReviewDateSelect;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Review_Start_Day_Option", type="boolean")
     */
    protected $reviewStartDayOption;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Review_Start_Month", type="smallint", nullable=true)
     */
    protected $reviewStartMonth;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Review_Start_Day", type="smallint", nullable=true)
     */
    protected $reviewStartDay;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Author_Review", type="boolean")
     */
    protected $authorReview;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Additional_Editor_Review", type="boolean")
     */
    protected $additionalEditorReview;

    /**
     * @var string
     *
     * @ORM\Column(name="Reviewers_Field", type="string", length=255, nullable=true)
     */
    protected $reviewersField;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_reviewer_users",
     *      joinColumns={@ORM\JoinColumn(name="Review_Policy_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id")})
     */
    protected $reviewers;

    /**
     * @var array
     * 
     * @ORM\Column(name="Reviewer_Groups", type="text", nullable=true)
     */
    protected $reviewerGroups;

    /**
     * @ORM\ManyToOne(targetEntity="SystemMessages")
     * @ORM\JoinColumn(name="Activate_Message", referencedColumnName="id", nullable=true)
     */
    protected $enableActivateMsg;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Activate_Notify_Participants", type="boolean")
     */
    protected $activateNotifyParticipants;

    /**
     * @var string
     *
     * @ORM\Column(name="Activate_Notify_Field", type="string", length=255, nullable=true)
     */
    protected $activateNotifyField;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_review_activate_notify_list",
     *      joinColumns={@ORM\JoinColumn(name="Review_Policy_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id")})
     */
    protected $activateNotifyList;

    /**
     * @var array
     * 
     * @ORM\Column(name="Activate_Notify_Groups", type="text", nullable=true)
     */
    protected $activateNotifyGroups;

    /**
     * @var integer
     *
     * @ORM\Column(name="Review_Advance", type="smallint", options={"default":1})
     */
    protected $reviewAdvance = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="Review_Grace_Period", type="smallint", options={"default":30})
     */
    protected $reviewGracePeriod = 30;

    /**
     * @var integer
     *
     * @ORM\Column(name="Delay_Complete_Threshold", type="smallint", options={"default":3})
     */
    protected $delayCompleteThreshold = 3;

    /**
     * @ORM\ManyToOne(targetEntity="SystemMessages")
     * @ORM\JoinColumn(name="Delay_Msg", referencedColumnName="id", nullable=true)
     */
    protected $enableDelayMsg;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Delay_Notify_Participants", type="boolean")
     */
    protected $delayNotifyParticipants;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_review_delay_notify_list",
     *      joinColumns={@ORM\JoinColumn(name="Review_Policy_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id")})
     */
    protected $delayNotifyList;

    /**
     * @var array
     * 
     * @ORM\Column(name="Delay_Notify_Groups", type="text", nullable=true)
     */
    protected $delayNotifyGroups;

    /**
     * @var integer
     *
     * @ORM\Column(name="Delay_Escl_Threshold", type="smallint", options={"default":3})
     */
    protected $delayEsclThreshold = 3;

    /**
     * @ORM\ManyToOne(targetEntity="SystemMessages")
     * @ORM\JoinColumn(name="Delay_Escl_Msg", referencedColumnName="id", nullable=true)
     */
    protected $enableDelayEsclMsg;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Delay_Escl_Notify_Participant", type="boolean")
     */
    protected $delayEsclNotifyParticipant;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_review_delay_escl_notify_list",
     *      joinColumns={@ORM\JoinColumn(name="Review_Policy_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id")})
     */
    protected $delayEsclNotifyList;

    /**
     * @var array
     * 
     * @ORM\Column(name="Delay_Escl_Notify_Groups", type="text", nullable=true)
     */
    protected $delayEsclNotifyGroups;

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
     * Set policyName
     *
     * @param string $policyName
     * @return ReviewPolicies
     */
    public function setPolicyName($policyName)
    {
        $this->policyName = $policyName;
    
        return $this;
    }

    /**
     * Get policyName
     *
     * @return string 
     */
    public function getPolicyName()
    {
        return $this->policyName;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ReviewPolicies
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set policyStatus
     *
     * @param boolean $policyStatus
     * @return ReviewPolicies
     */
    public function setPolicyStatus($policyStatus)
    {
        $this->policyStatus = $policyStatus;
    
        return $this;
    }

    /**
     * Get policyStatus
     *
     * @return boolean 
     */
    public function getPolicyStatus()
    {
        return $this->policyStatus;
    }

    /**
     * Set policyPriority
     *
     * @param integer $policyPriority
     * @return ReviewPolicies
     */
    public function setPolicyPriority($policyPriority)
    {
        $this->policyPriority = $policyPriority;
    
        return $this;
    }

    /**
     * Get policyPriority
     *
     * @return integer 
     */
    public function getPolicyPriority()
    {
        return $this->policyPriority;
    }

    /**
     * Add libraries
     * 
     * @param \Docova\DocovaBundle\Entity\Libraries $library
     */
    public function addLibraries(\Docova\DocovaBundle\Entity\Libraries $library)
    {
    	$this->libraries[] = $library;
    }

    /**
     * Get libraries
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getLibraries()
    {
    	return $this->libraries;
    }

    /**
     * Remove libraries
     * 
     * @param \Docova\DocovaBundle\Entity\Libraries $library
     */
    public function removeLibraries(\Docova\DocovaBundle\Entity\Libraries $library)
    {
    	$this->libraries->removeElement($library);
    }
    
    /**
     * Remove all selected libraries 
     */
    public function clearAllLibraries()
    {
    	$this->libraries->clear();
    }

    /**
     * Add documentTypes
     *
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $doctype
     */
    public function addDocumentTypes(\Docova\DocovaBundle\Entity\DocumentTypes $doctype)
    {
    	$this->documentTypes[] = $doctype;
    }
    
    /**
     * Get documentTypes
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDocumentTypes()
    {
    	return $this->documentTypes;
    }
    
    /**
     * Remove documentTypes
     *
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $doctype
     */
    public function removeDocumentTypes(\Docova\DocovaBundle\Entity\DocumentTypes $doctype)
    {
    	$this->documentTypes->removeElement($doctype);
    }
    
    /**
     * Remove all selected DocumentTypes 
     */
    public function clearDocumentTypes()
    {
    	$this->documentTypes->clear();
    }

    /**
     * Set documentStatus
     *
     * @param integer $documentStatus
     * @return ReviewPolicies
     */
    public function setDocumentStatus($documentStatus)
    {
        $this->documentStatus = $documentStatus;
    
        return $this;
    }

    /**
     * Get documentStatus
     *
     * @return integer 
     */
    public function getDocumentStatus()
    {
        return $this->documentStatus;
    }

    /**
     * Set reviewCustomFormula
     *
     * @param string $reviewCustomFormula
     * @return ReviewPolicies
     */
    public function setReviewCustomFormula($reviewCustomFormula = null)
    {
        $this->reviewCustomFormula = $reviewCustomFormula;
    
        return $this;
    }

    /**
     * Get reviewCustomFormula
     *
     * @return string 
     */
    public function getReviewCustomFormula()
    {
        return $this->reviewCustomFormula;
    }

    /**
     * Set reviewPeriodOption
     *
     * @param string $reviewPeriodOption
     * @return ReviewPolicies
     */
    public function setReviewPeriodOption($reviewPeriodOption)
    {
        $this->reviewPeriodOption = $reviewPeriodOption;
    
        return $this;
    }

    /**
     * Get reviewPeriodOption
     *
     * @return string 
     */
    public function getReviewPeriodOption()
    {
        return $this->reviewPeriodOption;
    }

    /**
     * Set reviewPeriod
     *
     * @param integer $reviewPeriod
     * @return ReviewPolicies
     */
    public function setReviewPeriod($reviewPeriod)
    {
        $this->reviewPeriod = $reviewPeriod;
    
        return $this;
    }

    /**
     * Get reviewPeriod
     *
     * @return integer 
     */
    public function getReviewPeriod()
    {
        return $this->reviewPeriod;
    }

    /**
     * Set reviewDateSelect
     *
     * @param string $reviewDateSelect
     * @return ReviewPolicies
     */
    public function setReviewDateSelect($reviewDateSelect)
    {
        $this->reviewDateSelect = $reviewDateSelect;
    
        return $this;
    }

    /**
     * Get reviewDateSelect
     *
     * @return string 
     */
    public function getReviewDateSelect()
    {
        return $this->reviewDateSelect;
    }

    /**
     * Set reviewStartDayOption
     *
     * @param boolean $reviewStartDayOption
     * @return ReviewPolicies
     */
    public function setReviewStartDayOption($reviewStartDayOption)
    {
        $this->reviewStartDayOption = $reviewStartDayOption;
    
        return $this;
    }

    /**
     * Get reviewStartDayOption
     *
     * @return boolean 
     */
    public function getReviewStartDayOption()
    {
        return $this->reviewStartDayOption;
    }

    /**
     * Set reviewStartMonth
     *
     * @param integer $reviewStartMonth
     * @return ReviewPolicies
     */
    public function setReviewStartMonth($reviewStartMonth = null)
    {
        $this->reviewStartMonth = $reviewStartMonth;
    
        return $this;
    }

    /**
     * Get reviewStartMonth
     *
     * @return integer 
     */
    public function getReviewStartMonth()
    {
        return $this->reviewStartMonth;
    }

    /**
     * Set reviewStartDay
     *
     * @param integer $reviewStartDay
     * @return ReviewPolicies
     */
    public function setReviewStartDay($reviewStartDay = null)
    {
        $this->reviewStartDay = $reviewStartDay;
    
        return $this;
    }

    /**
     * Get reviewStartDay
     *
     * @return integer 
     */
    public function getReviewStartDay()
    {
        return $this->reviewStartDay;
    }

    /**
     * Set authorReview
     *
     * @param boolean $authorReview
     * @return ReviewPolicies
     */
    public function setAuthorReview($authorReview)
    {
        $this->authorReview = $authorReview;
    
        return $this;
    }

    /**
     * Get authorReview
     *
     * @return boolean 
     */
    public function getAuthorReview()
    {
        return $this->authorReview;
    }

    /**
     * Set additionalEditorReview
     *
     * @param boolean $additionalEditorReview
     * @return ReviewPolicies
     */
    public function setAdditionalEditorReview($additionalEditorReview)
    {
        $this->additionalEditorReview = $additionalEditorReview;
    
        return $this;
    }

    /**
     * Get additionalEditorReview
     *
     * @return boolean 
     */
    public function getAdditionalEditorReview()
    {
        return $this->additionalEditorReview;
    }

    /**
     * Set reviewersField
     *
     * @param string $reviewersField
     * @return ReviewPolicies
     */
    public function setReviewersField($reviewersField)
    {
        $this->reviewersField = $reviewersField;
    
        return $this;
    }

    /**
     * Get reviewersField
     *
     * @return string 
     */
    public function getReviewersField()
    {
        return $this->reviewersField;
    }

    /**
     * Set reviewers
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $reviewer
     */
    public function addReviewers(\Docova\DocovaBundle\Entity\UserAccounts $reviewer)
    {
    	$this->reviewers[] = $reviewer;
    }

    /**
     * Get reviewers
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getReviewers()
    {
    	return $this->reviewers;
    }

    /**
     * Remove reviewers
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $reviewer
     */
    public function removeReviewers(\Docova\DocovaBundle\Entity\UserAccounts $reviewer)
    {
    	$this->reviewers->removeElement($reviewer);
    }
    
    /**
     * Remove all selected reviewers 
     */
    public function clearReviewers()
    {
    	$this->reviewers->clear();
    }

    /**
     * Set reviewerGroups
     *
     * @param array|string $reviewerGroups
     * @return ReviewPolicies
     */
    public function setReviewerGroups($reviewerGroups)
    {
    	if (!empty($reviewerGroups))
    	{
    		if (is_array($reviewerGroups))
    		{
    			$this->reviewerGroups = implode(',', $reviewerGroups);
    		}
    		else {
    			$this->reviewerGroups = $reviewerGroups;
    		}
    	}
    	else {
    		$this->reviewerGroups = null;
    	}
    
    	return $this;
    }
    
    /**
     * Get reviewerGroups
     *
     * @return array
     */
    public function getReviewerGroups()
    {
    	if (!empty($this->reviewerGroups))
    	{
    		return explode(',', $this->reviewerGroups);
    	}
    	 
    	return array();
    }

    /**
     * Set enableActivateMsg
     *
     * @param null|\Docova\DocovaBundle\Entity\SystemMessages $enableActivateMsg
     * @return ReviewPolicies
     */
    public function setEnableActivateMsg(\Docova\DocovaBundle\Entity\SystemMessages $enableActivateMsg = null)
    {
        $this->enableActivateMsg = $enableActivateMsg;
    
        return $this;
    }

    /**
     * Get enableActivateMsg
     *
     * @return \Docova\DocovaBundle\Entity\SystemMessages
     */
    public function getEnableActivateMsg()
    {
        return $this->enableActivateMsg;
    }

    /**
     * Set activateNotifyParticipants
     *
     * @param boolean $activateNotifyParticipants
     * @return ReviewPolicies
     */
    public function setActivateNotifyParticipants($activateNotifyParticipants)
    {
        $this->activateNotifyParticipants = $activateNotifyParticipants;
    
        return $this;
    }

    /**
     * Get activateNotifyParticipants
     *
     * @return boolean 
     */
    public function getActivateNotifyParticipants()
    {
        return $this->activateNotifyParticipants;
    }

    /**
     * Set activateNotifyField
     *
     * @param string $activateNotifyField
     * @return ReviewPolicies
     */
    public function setActivateNotifyField($activateNotifyField)
    {
        $this->activateNotifyField = $activateNotifyField;
    
        return $this;
    }

    /**
     * Get activateNotifyField
     *
     * @return string 
     */
    public function getActivateNotifyField()
    {
        return $this->activateNotifyField;
    }

    /**
     * Add activateNotifyList
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $activate_notify_user
     */
    public function addActivateNotifyList(\Docova\DocovaBundle\Entity\UserAccounts $activate_notify_user)
    {
    	$this->activateNotifyList[] = $activate_notify_user;
    }

    /**
     * Get activateNotifyList
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getActivateNotifyList()
    {
    	return $this->activateNotifyList;
    }

    /**
     * Remove activateNotifyList
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $activate_notify_user
     */
    public function removeActivateNotifyList(\Docova\DocovaBundle\Entity\UserAccounts $activate_notify_user)
    {
    	$this->activateNotifyList->removeElement($activate_notify_user);
    }
    
    /**
     * Remove all ActivateNotifyList 
     */
    public function clearActivateNotifyList()
    {
    	$this->activateNotifyList->clear();
    }

    /**
     * Set activateNotifyGroups
     *
     * @param array|string $activateNotifyGroups
     * @return ReviewPolicies
     */
    public function setActivateNotifyGroups($activateNotifyGroups)
    {
    	if (!empty($activateNotifyGroups))
    	{
    		if (is_array($activateNotifyGroups))
    		{
    			$this->activateNotifyGroups = implode(',', $activateNotifyGroups);
    		}
    		else {
    			$this->activateNotifyGroups = $activateNotifyGroups;
    		}
    	}
    	else {
    		$this->activateNotifyGroups = null;
    	}
    
    	return $this;
    }
    
    /**
     * Get activateNotifyGroups
     *
     * @return array
     */
    public function getActivateNotifyGroups()
    {
    	if (!empty($this->activateNotifyGroups))
    	{
    		return explode(',', $this->activateNotifyGroups);
    	}
    	 
    	return array();
    }

    /**
     * Set reviewAdvance
     *
     * @param integer $reviewAdvance
     * @return ReviewPolicies
     */
    public function setReviewAdvance($reviewAdvance)
    {
        $this->reviewAdvance = $reviewAdvance;
    
        return $this;
    }

    /**
     * Get reviewAdvance
     *
     * @return integer 
     */
    public function getReviewAdvance()
    {
        return $this->reviewAdvance;
    }

    /**
     * Set reviewGracePeriod
     *
     * @param integer $reviewGracePeriod
     * @return ReviewPolicies
     */
    public function setReviewGracePeriod($reviewGracePeriod)
    {
        $this->reviewGracePeriod = $reviewGracePeriod;
    
        return $this;
    }

    /**
     * Get reviewGracePeriod
     *
     * @return integer 
     */
    public function getReviewGracePeriod()
    {
        return $this->reviewGracePeriod;
    }

    /**
     * Set delayCompleteThreshold
     *
     * @param integer $delayCompleteThreshold
     * @return ReviewPolicies
     */
    public function setDelayCompleteThreshold($delayCompleteThreshold)
    {
        $this->delayCompleteThreshold = $delayCompleteThreshold;
    
        return $this;
    }

    /**
     * Get delayCompleteThreshold
     *
     * @return integer 
     */
    public function getDelayCompleteThreshold()
    {
        return $this->delayCompleteThreshold;
    }

    /**
     * Set enableDelayMsg
     *
     * @param null|\Docova\DocovaBundle\Entity\SystemMessages $enableDelayMsg
     * @return ReviewPolicies
     */
    public function setEnableDelayMsg(\Docova\DocovaBundle\Entity\SystemMessages $enableDelayMsg = null)
    {
        $this->enableDelayMsg = $enableDelayMsg;
    
        return $this;
    }

    /**
     * Get enableDelayMsg
     *
     * @return \Docova\DocovaBundle\Entity\SystemMessages 
     */
    public function getEnableDelayMsg()
    {
        return $this->enableDelayMsg;
    }

    /**
     * Set delayNotifyParticipants
     *
     * @param boolean $delayNotifyParticipants
     * @return ReviewPolicies
     */
    public function setDelayNotifyParticipants($delayNotifyParticipants)
    {
        $this->delayNotifyParticipants = $delayNotifyParticipants;
    
        return $this;
    }

    /**
     * Get delayNotifyParticipants
     *
     * @return boolean 
     */
    public function getDelayNotifyParticipants()
    {
        return $this->delayNotifyParticipants;
    }

    /**
     * Add delayNotifyList
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $delay_notify_user
     */
    public function addDelayNotifyList(\Docova\DocovaBundle\Entity\UserAccounts $delay_notify_user)
    {
    	$this->delayNotifyList[] = $delay_notify_user;
    }

    /**
     * Get delayNotifyList
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDelayNotifyList()
    {
    	return $this->delayNotifyList;
    }

    /**
     * Remove delayNotifyList
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $delay_notify_user
     */
    public function removeDelayNotifyList(\Docova\DocovaBundle\Entity\UserAccounts $delay_notify_user)
    {
    	$this->delayNotifyList->removeElement($delay_notify_user);
    }
    
    /**
     * Remove all DelayNotifyList 
     */
    public function clearDelayNotifyList()
    {
    	$this->delayNotifyList->clear();
    }

    /**
     * Set delayNotifyGroups
     *
     * @param array|string $delayNotifyGroups
     * @return ReviewPolicies
     */
    public function setDelayNotifyGroups($delayNotifyGroups)
    {
    	if (!empty($delayNotifyGroups))
    	{
    		if (is_array($delayNotifyGroups))
    		{
    			$this->delayNotifyGroups = implode(',', $delayNotifyGroups);
    		}
    		else {
    			$this->delayNotifyGroups = $delayNotifyGroups;
    		}
    	}
    	else {
    		$this->delayNotifyGroups = null;
    	}
    
    	return $this;
    }
    
    /**
     * Get delayNotifyGroups
     *
     * @return array
     */
    public function getDelayNotifyGroups()
    {
    	if (!empty($this->delayNotifyGroups))
    	{
    		return explode(',', $this->delayNotifyGroups);
    	}
    	 
    	return array();
    }

    /**
     * Set delayEsclThreshold
     *
     * @param integer $delayEsclThreshold
     * @return ReviewPolicies
     */
    public function setDelayEsclThreshold($delayEsclThreshold)
    {
        $this->delayEsclThreshold = $delayEsclThreshold;
    
        return $this;
    }

    /**
     * Get delayEsclThreshold
     *
     * @return integer 
     */
    public function getDelayEsclThreshold()
    {
        return $this->delayEsclThreshold;
    }

    /**
     * Set enableDelayEsclMsg
     *
     * @param \Docova\DocovaBundle\Entity\SystemMessages $enableDelayEsclMsg
     * @return ReviewPolicies
     */
    public function setEnableDelayEsclMsg(\Docova\DocovaBundle\Entity\SystemMessages $enableDelayEsclMsg)
    {
        $this->enableDelayEsclMsg = $enableDelayEsclMsg;
    
        return $this;
    }

    /**
     * Get enableDelayEsclMsg
     *
     * @return \Docova\DocovaBundle\Entity\SystemMessages 
     */
    public function getEnableDelayEsclMsg()
    {
        return $this->enableDelayEsclMsg;
    }

    /**
     * Set delayEsclNotifyParticipant
     *
     * @param boolean $delayEsclNotifyParticipant
     * @return ReviewPolicies
     */
    public function setDelayEsclNotifyParticipant($delayEsclNotifyParticipant)
    {
        $this->delayEsclNotifyParticipant = $delayEsclNotifyParticipant;
    
        return $this;
    }

    /**
     * Get delayEsclNotifyParticipant
     *
     * @return boolean 
     */
    public function getDelayEsclNotifyParticipant()
    {
        return $this->delayEsclNotifyParticipant;
    }

    /**
     * Add delayEsclNotifyList
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $delay_escl_user
     */
    public function addDelayEsclNotifyList(\Docova\DocovaBundle\Entity\UserAccounts $delay_escl_user)
    {
    	$this->delayEsclNotifyList[] = $delay_escl_user;
    }

    /**
     * Get delayEsclNotifyList
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDelayEsclNotifyList()
    {
    	return $this->delayEsclNotifyList;
    }

    /**
     * Remove delayEsclNotifyList
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $delay_escl_user
     */
    public function removeDelayEsclNotifyList(\Docova\DocovaBundle\Entity\UserAccounts $delay_escl_user)
    {
    	$this->delayEsclNotifyList->removeElement($delay_escl_user);
    }
    
    /**
     * Remove all DelayEsclNotifyList 
     */
    public function clearDelayEsclNotifyList()
    {
    	$this->delayEsclNotifyList->clear();
    }

    /**
     * Set delayEsclNotifyGroups
     *
     * @param array|string $delayEsclNotifyGroups
     * @return ReviewPolicies
     */
    public function setDelayEsclNotifyGroups($delayEsclNotifyGroups)
    {
    	if (!empty($delayEsclNotifyGroups))
    	{
    		if (is_array($delayEsclNotifyGroups))
    		{
    			$this->delayEsclNotifyGroups = implode(',', $delayEsclNotifyGroups);
    		}
    		else {
    			$this->delayEsclNotifyGroups = $delayEsclNotifyGroups;
    		}
    	}
    	else {
    		$this->delayEsclNotifyGroups = null;
    	}
    
    	return $this;
    }
    
    /**
     * Get delayEsclNotifyGroups
     *
     * @return array
     */
    public function getDelayEsclNotifyGroups()
    {
    	if (!empty($this->delayEsclNotifyGroups))
    	{
    		return explode(',', $this->delayEsclNotifyGroups);
    	}
    	 
    	return array();
    }

    /**
	 * Get customReviewPeriod
	 * 
	 * @return string
	 */
	public function getCustomReviewPeriod() 
	{
		return $this->customReviewPeriod;
	}
	
	/**
	 * Set customReviewPeriod
	 * 
	 * @param string $customReviewPeriod
	 * @return ReviewPolicies        	
	 */
	public function setCustomReviewPeriod($customReviewPeriod) 
	{
		$this->customReviewPeriod = $customReviewPeriod;

		return $this;
	}
	
	/**
	 * Get customReviewPeriodOption
	 * 
	 * @return string
	 */
	public function getCustomReviewPeriodOption() 
	{
		return $this->customReviewPeriodOption;
	}
	
	/**
	 * Set customReviewPeriodOption
	 * 
	 * @param string $customReviewPeriodOption
	 * @return ReviewPolicies
	 */
	public function setCustomReviewPeriodOption($customReviewPeriodOption) 
	{
		$this->customReviewPeriodOption = $customReviewPeriodOption;
		
		return $this;
	}
	
	/**
	 * Get customReviewDateSelect
	 * 
	 * @return string
	 */
	public function getCustomReviewDateSelect() 
	{
		return $this->customReviewDateSelect;
	}
	
	/**
	 * Set customReviewDateSelect
	 * 
	 * @param string $customReviewDateSelect
	 * @return ReviewPolicies
	 */
	public function setCustomReviewDateSelect($customReviewDateSelect) 
	{
		$this->customReviewDateSelect = $customReviewDateSelect;
		
		return $this;
	}
	
}
