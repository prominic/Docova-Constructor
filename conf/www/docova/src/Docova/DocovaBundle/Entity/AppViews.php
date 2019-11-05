<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppViews
 *
 * @ORM\Table(name="tb_app_views")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AppViewsRepository")
 */
class AppViews
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
     * @ORM\Column(name="View_Name", type="string", length=255)
     */
    protected $viewName;

    /**
     * @var string
     *
     * @ORM\Column(name="View_Alias", type="string", length=255, nullable=true)
     */
    protected $viewAlias;

    /**
     * @var string
     * 
     * @ORM\Column(name="Selection_Type", type="string", length=1, options={"default":"F"})
     */
    protected $selectionType = 'F';

    /**
     * @var string
     *
     * @ORM\Column(name="View_Query", type="text", nullable=false)
     */
    protected $viewQuery;

    /**
     * @var string
     * 
     * @ORM\Column(name="Converted_Query", type="text", nullable=true)
     */
    protected $convertedQuery;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Emulate_Folder", type="boolean", options={"default":false})
     */
    protected $emulateFolder = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Private_On_First_Use", type="boolean", options={"default":false})
     */
    protected $privateOnFirstUse = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Resp_Hierarchy", type="boolean")
     */
    protected $respHierarchy = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Resp_Colspan", type="smallint", nullable=true)
     */
    protected $respColspan;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Enable_Paging", type="boolean")
     */
    protected $enablePaging = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Max_Doc_Count", type="integer", nullable=true)
     */
    protected $maxDocCount;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Open_In_Edit", type="boolean")
     */
    protected $openInEdit = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Open_In_Dialog", type="boolean")
     */
    protected $openInDialog = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Show_Selection", type="boolean")
     */
    protected $showSelection = false;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="View_Type", type="string", length=15, options={"default":"Standard"})
     */
    protected $viewType = 'Standard';

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Auto_Collapse", type="boolean", options={"default":false})
     */
    protected $autoCollapse;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="View_Fulltext_Search", type="boolean", options={"default":false})
     */
    protected $viewFtSearch;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="PDU", type="boolean", options={"comment"="Prohibit Design Update"})
     */
    protected $pDU = false;

    /**
     * @var string
     *
     * @ORM\Column(name="View_JavaScript", type="text", nullable=true)
     */
    protected $viewJavaScript;

    /**
     * @var string
     *
     * @ORM\Column(name="View_Perspective", type="text", nullable=true)
     */
    protected $viewPerspective;

    /**
     * @var string
     * 
     * @ORM\Column(name="Weekends", type="boolean", options={"default":false})
     */
    protected $weekends = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="First_Day", type="boolean", options={"default":true})
     */
    protected $firstDay = true;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Calendar_Style", type="boolean", options={"default":false})
     */
    protected $style = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Day_Click", type="string", length=255, nullable=true)
     */
    protected $dayClick;

    /**
     * @var string
     * 
     * @ORM\Column(name="Event_Click", type="string", length=255, nullable=true)
     */
    protected $eventClick;

    /**
     * @var string
     * 
     * @ORM\Column(name="Event_Color", type="string", length=8, nullable=true)
     */
    protected $eventColor;

    /**
     * @var string
     * 
     * @ORM\Column(name="Day_Double_Click", type="string", length=255, nullable=true)
     */
    protected $dayDblClick;

    /**
     * @var string
     * 
     * @ORM\Column(name="Event_Double_Click", type="string", length=255, nullable=true)
     */
    protected $eventDblClick;

    /**
     * @var string
     * 
     * @ORM\Column(name="Event_Text_Color", type="string", length=8, nullable=true)
     */
    protected $eventTextColor;

    /**
     * @var string
     * 
     * @ORM\Column(name="Gantt_Default_Form", type="string", length=36, nullable=true)
     */
    protected $ganttDefaultForm;

    /**
     * @var string
     * 
     * @ORM\Column(name="Gantt_Resource_Type", type="string", length=1, nullable=true)
     */
    protected $ganttResourceType;

    /**
     * @var string
     * 
     * @ORM\Column(name="Gantt_Resource_Options", type="text", nullable=true)
     */
    protected $ganttResourceOptions;

    /**
     * @var string
     * 
     * @ORM\Column(name="Gantt_Resource_Formula", type="string", length=1024, nullable=true)
     */
    protected $ganttResourceFormula;

    /**
     * @var string
     * 
     * @ORM\Column(name="Gantt_Translated_Resource_Formula", type="text", nullable=true)
     */
    protected $ganttTranslatedFormula;

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
     * @ORM\ManyToOne(targetEntity="Libraries", inversedBy="views")
     * @ORM\JoinColumn(name="App_Id", referencedColumnName="id", nullable=true)
     */
    protected $application;
    /**
     * @ORM\ManyToOne(targetEntity="AppFormProperties", inversedBy="dataTableViews")
     * @ORM\JoinColumn(name="DataTable_FormProperties_Id", referencedColumnName="id", nullable=true)
     */
    protected $datatableformproperties;


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
     * Set viewName
     *
     * @param string $viewName
     * @return AppViews
     */
    public function setViewName($viewName)
    {
        $this->viewName = $viewName;

        return $this;
    }

    /**
     * Get viewName
     *
     * @return string 
     */
    public function getViewName()
    {
        return $this->viewName;
    }

    /**
     * Set viewAlias
     *
     * @param string $viewAlias
     * @return AppViews
     */
    public function setViewAlias($viewAlias)
    {
        $this->viewAlias = $viewAlias;

        return $this;
    }

    /**
     * Get viewAlias
     *
     * @return string 
     */
    public function getViewAlias()
    {
        return $this->viewAlias;
    }

    /**
     * Set datatableformproperties
     *
     * @param \Docova\DocovaBundle\Entity\AppFormProperties $formproperties
     */
    public function setDataTableFormProperties(\Docova\DocovaBundle\Entity\AppFormProperties $formproperties)
    {
        $this->datatableformproperties = $formproperties;
    }
    
    /**
     * Get datatableformproperties
     *
     * @return \Docova\DocovaBundle\Entity\AppFormProperties
     */
    public function getDataTableFormProperties()
    {
        return $this->datatableformproperties;
    }

    /**
     * Set selectionType
     *
     * @param string $selectionType
     * @return AppViews
     */
    public function setSelectionType($selectionType)
    {
    	$this->selectionType = $selectionType;
    
    	return $this;
    }
    
    /**
     * Get selectionType
     *
     * @return string
     */
    public function getSelectionType()
    {
    	return $this->selectionType;
    }
    
    /**
     * Set viewQuery
     *
     * @param string $viewQuery
     * @return AppViews
     */
    public function setViewQuery($viewQuery)
    {
        $this->viewQuery = (is_null($viewQuery) ? '' : $viewQuery);

        return $this;
    }

    /**
     * Get viewQuery
     *
     * @return string 
     */
    public function getViewQuery()
    {
        return $this->viewQuery;
    }

    /**
     * Set respHierarchy
     *
     * @param boolean $respHierarchy
     * @return AppViews
     */
    public function setRespHierarchy($respHierarchy)
    {
        $this->respHierarchy = $respHierarchy;

        return $this;
    }

    /**
     * Get respHierarchy
     *
     * @return boolean 
     */
    public function getRespHierarchy()
    {
        return $this->respHierarchy;
    }

    /**
     * Set respColspan
     *
     * @param integer $respColspan
     * @return AppViews
     */
    public function setRespColspan($respColspan)
    {
        $this->respColspan = $respColspan;

        return $this;
    }

    /**
     * Get respColspan
     *
     * @return integer 
     */
    public function getRespColspan()
    {
        return $this->respColspan;
    }

    /**
     * Set maxDocCount
     *
     * @param integer $maxDocCount
     * @return AppViews
     */
    public function setMaxDocCount($maxDocCount)
    {
        $this->maxDocCount = $maxDocCount;

        return $this;
    }

    /**
     * Get maxDocCount
     *
     * @return integer 
     */
    public function getMaxDocCount()
    {
        return $this->maxDocCount;
    }

    /**
     * Set viewJavaScript
     *
     * @param string $viewJavaScript
     * @return AppViews
     */
    public function setViewJavaScript($viewJavaScript)
    {
        $this->viewJavaScript = $viewJavaScript;

        return $this;
    }

    /**
     * Get viewJavaScript
     *
     * @return string 
     */
    public function getViewJavaScript($b64 = false)
    {
    	if ($b64 === false) {
        	return $this->viewJavaScript;
    	}
    	else {
    		return !empty($this->viewJavaScript) ? base64_encode($this->viewJavaScript) : null;
    	}
    }

    /**
     * Set viewPerspective
     *
     * @param string $viewPerspective
     * @return AppViews
     */
    public function setViewPerspective($viewPerspective)
    {
        $this->viewPerspective = $viewPerspective;

        return $this;
    }

    /**
     * Get viewPerspective
     *
     * @return string 
     */
    public function getViewPerspective()
    {
        return $this->viewPerspective;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return AppViews
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
     * @return AppViews
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
     * @return AppViews
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
     * @return AppViews
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
     * Set application
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $application
     * @return AppViews
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
     * Set pDU
     *
     * @param boolean $pDU
     * @return AppViews
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
     * Set enablePaging
     *
     * @param boolean $enablePaging
     * @return AppViews
     */
    public function setEnablePaging($enablePaging)
    {
        $this->enablePaging = $enablePaging;

        return $this;
    }

    /**
     * Get enablePaging
     *
     * @return boolean 
     */
    public function getEnablePaging()
    {
        return $this->enablePaging;
    }

    /**
     * Set openInEdit
     *
     * @param boolean $openInEdit
     * @return AppViews
     */
    public function setOpenInEdit($openInEdit)
    {
        $this->openInEdit = $openInEdit;

        return $this;
    }

    /**
     * Get openInEdit
     *
     * @return boolean 
     */
    public function getOpenInEdit()
    {
        return $this->openInEdit;
    }
    
    /**
     * Set openInDialog
     * 
     * @param boolean $openInDialog
     * @return AppViews
     */
    public function setOpenInDialog($openInDialog)
    {
    	$this->openInDialog = $openInDialog;
    	
    	return $this;
    }
    
    /**
     * Get openInDialog
     * 
     * @return boolean
     */
    public function getOpenInDialog()
    {
    	return $this->openInDialog;
    }

    /**
     * Set showSelection
     *
     * @param boolean $showSelection
     * @return AppViews
     */
    public function setShowSelection($showSelection)
    {
    	$this->showSelection = $showSelection;
    
    	return $this;
    }
    
    /**
     * Get showSelection
     *
     * @return boolean
     */
    public function getShowSelection()
    {
    	return $this->showSelection;
    }

    /**
     * Set viewType
     * 
     * @param string $type
     * @return AppViews
     */
    public function setViewType($type)
    {
    	$this->viewType = $type;
    	
    	return $this;
    }

    /**
     * Get viewType
     * 
     * @return string
     */
    public function getViewType()
    {
    	return $this->viewType;
    }
    
    /**
     * Set autoCollapse
     * 
     * @param boolean $autoCollapse
     * @return AppViews
     */
    public function setAutoCollapse($autoCollapse)
    {
    	$this->autoCollapse = $autoCollapse;
    	
    	return $this;
    }
    
    /**
     * Get autoCollapse
     * 
     * @return boolean
     */
    public function getAutoCollapse()
    {
    	return $this->autoCollapse;
    }
    
    /**
     * Set viewFtSearch
     * 
     * @param boolean $viewFtSearch
     * @return AppViews
     */
    public function setViewFtSearch($viewFtSearch)
    {
    	$this->viewFtSearch = $viewFtSearch;
    	
    	return $this;
    }

    /**
     * Get viewFtSearch
     * 
     * @return boolean
     */
    public function getViewFtSearch()
    {
    	return $this->viewFtSearch;
    }

    /**
     * Set convertedQuery
     *
     * @param string $convertedQuery
     * @return AppViews
     */
    public function setConvertedQuery($convertedQuery = null)
    {
        $this->convertedQuery = $convertedQuery;

        return $this;
    }

    /**
     * Get convertedQuery
     *
     * @return string 
     */
    public function getConvertedQuery()
    {
        return $this->convertedQuery;
    }

    /**
     * Set emulateFolder
     *
     * @param boolean $emulateFolder
     * @return AppViews
     */
    public function setEmulateFolder($emulateFolder)
    {
        $this->emulateFolder = $emulateFolder;

        return $this;
    }

    /**
     * Get emulateFolder
     *
     * @return boolean 
     */
    public function getEmulateFolder()
    {
        return $this->emulateFolder;
    }
    
    /**
     * Set privateOnFirstUse
     * 
     * @param boolean $privateOnFirstUse
     * @return AppViews
     */
    public function setPrivateOnFirstUse($privateOnFirstUse)
    {
    	$this->privateOnFirstUse = $privateOnFirstUse;
    	
    	return $this;
    }
    
    /**
     * Get privateOnFirstUse
     * 
     * @return boolean
     */
    public function getPrivateOnFirstUse()
    {
    	return $this->privateOnFirstUse;
    }

    /**
     * Set weekends
     *
     * @param boolean $weekends
     * @return AppViews
     */
    public function setWeekends($weekends)
    {
        $this->weekends = $weekends;

        return $this;
    }

    /**
     * Get weekends
     *
     * @return boolean 
     */
    public function getWeekends()
    {
        return $this->weekends;
    }

    /**
     * Set firstDay
     *
     * @param integer $firstDay
     * @return AppViews
     */
    public function setFirstDay($firstDay)
    {
        $this->firstDay = $firstDay;

        return $this;
    }

    /**
     * Get firstDay
     *
     * @return integer 
     */
    public function getFirstDay()
    {
        return $this->firstDay;
    }

    /**
     * Set style
     *
     * @param boolean $style
     * @return AppViews
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Get style
     *
     * @return boolean 
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set dayClick
     *
     * @param string $dayClick
     * @return AppViews
     */
    public function setDayClick($dayClick)
    {
        $this->dayClick = $dayClick;

        return $this;
    }

    /**
     * Get dayClick
     *
     * @return string 
     */
    public function getDayClick()
    {
        return $this->dayClick;
    }

    /**
     * Set eventClick
     *
     * @param string $eventClick
     * @return AppViews
     */
    public function setEventClick($eventClick)
    {
        $this->eventClick = $eventClick;

        return $this;
    }

    /**
     * Get eventClick
     *
     * @return string 
     */
    public function getEventClick()
    {
        return $this->eventClick;
    }

    /**
     * Set eventColor
     *
     * @param string $eventColor
     * @return AppViews
     */
    public function setEventColor($eventColor)
    {
        $this->eventColor = $eventColor;

        return $this;
    }

    /**
     * Get eventColor
     *
     * @return string 
     */
    public function getEventColor()
    {
        return $this->eventColor;
    }

    /**
     * Set dayDblClick
     *
     * @param string $dayDblClick
     * @return AppViews
     */
    public function setDayDblClick($dayDblClick)
    {
        $this->dayDblClick = $dayDblClick;

        return $this;
    }

    /**
     * Get dayDblClick
     *
     * @return string 
     */
    public function getDayDblClick()
    {
        return $this->dayDblClick;
    }

    /**
     * Set eventDblClick
     *
     * @param string $eventDblClick
     * @return AppViews
     */
    public function setEventDblClick($eventDblClick)
    {
        $this->eventDblClick = $eventDblClick;

        return $this;
    }

    /**
     * Get eventDblClick
     *
     * @return string 
     */
    public function getEventDblClick()
    {
        return $this->eventDblClick;
    }

    /**
     * Set eventTextColor
     *
     * @param string $eventTextColor
     * @return AppViews
     */
    public function setEventTextColor($eventTextColor)
    {
        $this->eventTextColor = $eventTextColor;

        return $this;
    }

    /**
     * Get eventTextColor
     *
     * @return string 
     */
    public function getEventTextColor()
    {
        return $this->eventTextColor;
    }

    /**
     * Set ganttDefaultForm
     *
     * @param string $ganttDefaultForm
     * @return AppViews
     */
    public function setGanttDefaultForm($ganttDefaultForm)
    {
        $this->ganttDefaultForm = $ganttDefaultForm;

        return $this;
    }

    /**
     * Get ganttDefaultForm
     *
     * @return string 
     */
    public function getGanttDefaultForm()
    {
        return $this->ganttDefaultForm;
    }

    /**
     * Set ganttResourceType
     *
     * @param string $ganttResourceType
     * @return AppViews
     */
    public function setGanttResourceType($ganttResourceType)
    {
        $this->ganttResourceType = $ganttResourceType;

        return $this;
    }

    /**
     * Get ganttResourceType
     *
     * @return string 
     */
    public function getGanttResourceType()
    {
        return $this->ganttResourceType;
    }

    /**
     * Set ganttResourceOptions
     *
     * @param string $ganttResourceOptions
     * @return AppViews
     */
    public function setGanttResourceOptions($ganttResourceOptions)
    {
        $this->ganttResourceOptions = $ganttResourceOptions;

        return $this;
    }

    /**
     * Get ganttResourceOptions
     *
     * @return string 
     */
    public function getGanttResourceOptions()
    {
        return $this->ganttResourceOptions;
    }

    /**
     * Set ganttResourceFormula
     *
     * @param string $ganttResourceFormula
     * @return AppViews
     */
    public function setGanttResourceFormula($ganttResourceFormula)
    {
        $this->ganttResourceFormula = $ganttResourceFormula;

        return $this;
    }

    /**
     * Get ganttResourceFormula
     *
     * @return string 
     */
    public function getGanttResourceFormula()
    {
        return $this->ganttResourceFormula;
    }
    
    /**
     * Set ganttTranslatedFormula
     * 
     * @param unknown $translatedFormula
     * @return \Docova\DocovaBundle\Entity\AppViews
     */
    public function setGanttTranslatedFormula($translatedFormula = null)
    {
    	$this->ganttTranslatedFormula = $translatedFormula;
    	
    	return $this;
    }
    
    /**
     * Get ganttTranslatedFormula
     * 
     * @return string
     */
    public function getGanttTranslatedFormula()
    {
    	return $this->ganttTranslatedFormula;
    }
}
