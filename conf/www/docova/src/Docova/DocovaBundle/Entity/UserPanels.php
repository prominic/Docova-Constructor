<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * UserPanels
 *
 * @ORM\Table(name="tb_user_panels")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\UserPanelsRepository")
 */
class UserPanels
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
     * @ORM\Column(name="Title", type="string", length=100)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="Layout_Name", type="string", length=100, options={"default" : "L4-1"})
     */
    protected $layoutName = 'L4-1';

    /**
     * @var integer
     * 
     * @ORM\Column(name="Layout_Order", type="smallint")
     */
    protected $layoutOrder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Layout_Default", type="boolean")
     */
    protected $layoutDefault = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Share_Type", type="string", length=7, options={"default" : "Private"})
     */
    protected $shareType = 'Private';

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Panel_Tab", type="boolean", options={"default" : true})
     */
    protected $panelTab = true;

    /**
     * @var string
     *
     * @ORM\Column(name="Share_With", type="string", length=10, nullable=true)
     */
    protected $shareWith;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Panel_List", type="boolean", options={"default" : false})
     */
    protected $panelList = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Is_Workspace", type="boolean", options={"default" : false})
     */
    protected $isWorkspace = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean", options={"default" : false})
     */
    protected $trash = false;

    /**
     * @ORM\ManyToOne(targetEntity="UserPanels")
     * @ORM\JoinColumn(name="Parent_Panel", referencedColumnName="id", nullable=true)
     */
    protected $parentPanel;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Owner", referencedColumnName="id", nullable=false)
     */
    protected $creator;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="User_Id", referencedColumnName="id", nullable=true)
     */
    protected $assignedUser;

    /**
	 * @var ArrayCollection
	 *
	 * @ORM\ManyToMany(targetEntity="UserAccounts")
	 * @ORM\JoinTable(name="tb_userpanels_additional_editors",
	 *      joinColumns={@ORM\JoinColumn(name="User_Panel_Id", referencedColumnName="id", onDelete="CASCADE")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id", onDelete="CASCADE")})
     */
    protected $additionalEditors;

    /**
	 * @var ArrayCollection
	 *
     * @ORM\OneToMany(targetEntity="PanelWidgets", mappedBy="panel")
     */
    protected $panelWidgets;


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
     * Set title
     *
     * @param string $title
     * @return UserPanels
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
     * Set description
     *
     * @param string $description
     * @return UserPanels
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
     * Set layoutName
     *
     * @param string $layoutName
     * @return UserPanels
     */
    public function setLayoutName($layoutName)
    {
        $this->layoutName = $layoutName;
    
        return $this;
    }

    /**
     * Get layoutName
     *
     * @return string 
     */
    public function getLayoutName()
    {
        return $this->layoutName;
    }

    /**
     * Set layoutOrder
     * 
     * @param integer $layoutOrder
     * @return UserPanels
     */
    public function setLayoutOrder($layoutOrder) 
    {
    	$this->layoutOrder = $layoutOrder;
    	
    	return $this;
    }

	/**
	 * Get layoutOrder
	 * 
	 * @return integer
	 */
	public function getLayoutOrder() 
	{
		return $this->layoutOrder;
	}

    /**
     * Set layoutDefault
     *
     * @param boolean $layoutDefault
     * @return UserPanels
     */
    public function setLayoutDefault($layoutDefault)
    {
        $this->layoutDefault = $layoutDefault;
    
        return $this;
    }

    /**
     * Get layoutDefault
     *
     * @return boolean 
     */
    public function getLayoutDefault()
    {
        return $this->layoutDefault;
    }

    /**
     * Set shareType
     *
     * @param string $shareType
     * @return UserPanels
     */
    public function setShareType($shareType)
    {
        $this->shareType = $shareType;
    
        return $this;
    }

    /**
     * Get shareType
     *
     * @return string 
     */
    public function getShareType()
    {
        return $this->shareType;
    }

    /**
     * Set panelTab
     * 
     * @param boolean $panelTab
     * @return UserPanels
     */
    public function setPanelTab($panelTab) 
    {
    	$this->panelTab = $panelTab;
    	
    	return $this;
    }

	/**
	 * Get panelTab
	 * 
	 * @return boolean
	 */
	public function getPanelTab() 
	{
		return $this->panelTab;
	}
	
    /**
     * Set shareWith
     *
     * @param string $shareWith
     * @return UserPanels
     */
    public function setShareWith($shareWith = null)
    {
        $this->shareWith = $shareWith;
    
        return $this;
    }

    /**
     * Get shareWith
     *
     * @return string 
     */
    public function getShareWith()
    {
        return $this->shareWith;
    }

	/**
	 * Set panelList
	 * 
	 * @param boolean $panelList
	 * @return UserPanels
	 */
	public function setPanelList($panelList) 
	{
		$this->panelList = $panelList;
		
		return $this;
	}

	/**
	 * Get panelList
	 * 
	 * @return boolean
	 */
	public function getPanelList() 
	{
		return $this->panelList;
	}

	/**
	 * Set isWorkspace
	 *
	 * @param boolean $isWorkspace
	 * @return UserPanels
	 */
	public function setIsWorkspacet($isWorkspace)
	{
		$this->isWorkspace = $isWorkspace;
	
		return $this;
	}
	
	/**
	 * Get isWorkspace
	 *
	 * @return boolean
	 */
	public function getIsWorkspace()
	{
		return $this->isWorkspace;
	}

	/**
	 * Set tarsh
	 *
	 * @param boolean $trash
	 * @return UserPanels
	 */
	public function setTrash($trash)
	{
		$this->trash = $trash;
	
		return $this;
	}
	
	/**
	 * Get trash
	 *
	 * @return boolean
	 */
	public function getTrash()
	{
		return $this->trash;
	}

	/**
	 * Set parentPanel
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserPanels $parentPanel
	 * @return UserPanels
	 */
	public function setParentPanel(\Docova\DocovaBundle\Entity\UserPanels $parentPanel) 
	{
		$this->parentPanel = $parentPanel;
		
		return $this;
	}

	/**
	 * Get parentPanel
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserPanels
	 */
	public function getParentPanel() 
	{
		return $this->parentPanel;
	}

	/**
	 * Set creator
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $creator
	 * @return UserPanels
	 */
	public function setCreator(\Docova\DocovaBundle\Entity\UserAccounts $creator) 
	{
		$this->creator = $creator;
		
		return $this;
	}

	/**
	 * Get creator
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserAccounts
	 */
	public function getCreator() 
	{
		return $this->creator;
	}

	/**
	 * Set assignedUser
	 *
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $assignedUser
	 * @return UserPanels
	 */
	public function setAssignedUser(\Docova\DocovaBundle\Entity\UserAccounts $assignedUser = null)
	{
		$this->assignedUser = $assignedUser;
	
		return $this;
	}
	
	/**
	 * Get assignedUser
	 *
	 * @return \Docova\DocovaBundle\Entity\UserAccounts
	 */
	public function getAssignedUser()
	{
		return $this->assignedUser;
	}

	/**
	 * Add additionalEditors
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $additionalEditors
	 */
	public function addAdditionalEditors(\Docova\DocovaBundle\Entity\UserAccounts $additionalEditors) 
	{
		$this->additionalEditors[] = $additionalEditors;
	}

	/**
	 * Get additionalEditors
	 * 
	 * @return ArrayCollection
	 */
	public function getAdditionalEditors() 
	{
		return $this->additionalEditors;
	}
	
	/**
	 * Get panelWidgets
	 * 
	 * @return ArrayCollection
	 */
	public function getPanelWidgets()
	{
		return $this->panelWidgets;
	}
}
