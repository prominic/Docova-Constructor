<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentTypes
 *
 * @ORM\Table(name="tb_document_types")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DocumentTypesRepository")
 */
class DocumentTypes
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
     * @ORM\Column(name="Trash", type="boolean", options={"default" : false})
     */
    protected $Trash = false;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Status", type="boolean")
     */
    protected $Status;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Key_Type", type="smallint", options={"default": 1})
     */
    protected $Key_Type = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="Doc_Name", type="string", length=255)
     */
    protected $Doc_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="text", nullable=true)
     */
    protected $Description;

    /**
     * @var string
     * 
     * @ORM\Column(name="Doc_Icon", type="string", length=50, options={"default": "icn16-stddoc.gif"})
     */
    protected $Doc_Icon = 'icn16-stddoc.gif';

    /**
     * @var string
     * 
     * @ORM\Column(name="Paper_Color", type="string", length=6, nullable=true)
     */
    protected $Paper_Color;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Disable_Activities", type="boolean")
     */
    protected $Disable_Activities = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Hide_Subject", type="boolean")
     */
    protected $Hide_Subject;

    /**
     * @var string
     *
     * @ORM\Column(name="Subject_Label", type="string", length=100)
     */
    protected $Subject_Label;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Subject_Formula", type="text", nullable=true)
     */
    protected $Custom_Subject_Formula;

    /**
     * @var string
     *
     * @ORM\Column(name="Translate_Subject_To", type="text", nullable=true)
     */
    protected $Translate_Subject_To;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="More_Section_Label", type="string", length=255, options={"default":"More"})
     */
    protected $More_Section_Label = 'More';

    /**
     * @var boolean
     *
     * @ORM\Column(name="Hide_On_Reading", type="boolean")
     */
    protected $Hide_On_Reading = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Hide_On_Editing", type="boolean")
     */
    protected $Hide_On_Editing = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Hide_On_Custom", type="boolean")
     */
    protected $Hide_On_Custom = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Header_Hide_When", type="text", nullable=true)
     */
    protected $Custom_Header_Hide_When;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Change_Doc_Author", type="string", length=255, nullable=true)
     */
    protected $Custom_Change_Doc_Author;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Hide_Description", type="string", length=255, nullable=true)
     */
    protected $Custom_Hide_Description;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Hide_Keywords", type="string", length=255, nullable=true)
     */
    protected $Custom_Hide_Keywords;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Hide_Review_Cycle", type="string", length=255, nullable=true)
     */
    protected $Custom_Hide_Review_Cycle;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Hide_Archiving", type="string", length=255, nullable=true)
     */
    protected $Custom_Hide_Archiving;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Change_Owner", type="string", length=255, nullable=true)
     */
    protected $Custom_Change_Owner;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Change_Add_Editors", type="string", length=255, nullable=true)
     */
    protected $Custom_Change_Add_Editors;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Change_Read_Access", type="string", length=255, nullable=true)
     */
    protected $Custom_Change_Read_Access;

    /**
     * @var string
     *
     * @ORM\Column(name="Top_Banner", type="string", length=255, nullable=true)
     */
    protected $Top_Banner;

    /**
     * @var string
     *
     * @ORM\Column(name="Bottom_Banner", type="string", length=255, nullable=true)
     */
    protected $Bottom_Banner;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Section_Style", type="boolean", options={"default":true})
     */
    protected $Section_Style = true;

    /**
     * @var string
     *
     * @ORM\Column(name="Section_Label", type="string", length=255, nullable=true)
     */
    protected $Section_Label;

    /**
     * @var string
     *
     * @ORM\Column(name="Background_Color", type="string", length=6, options={"default": "DFDFDF"})
     */
    protected $Background_Color;

    /**
     * @var string
     * 
     * @ORM\Column(name="Additional_CSS", type="text", nullable=true)
     */
    protected $Additional_CSS;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Enable_For_DLE", type="boolean")
     */
    protected $Enable_For_DLE = true;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Hide_Docs_From_DOE", type="boolean")
     */
    protected $Hide_Docs_From_Doe = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Hide_Docs_From_Mobile", type="boolean")
     */
    protected $Hide_Docs_From_Mobile = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Enable_Mail_Acquire", type="boolean")
     */
    protected $Enable_Mail_Acquire = true;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Enable_Discussion", type="boolean")
     */
    protected $Enable_Discussion = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Prompt_For_Metadata", type="boolean")
     */
    protected $Prompt_For_Metadata = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Allow_Forwarding", type="boolean", options={"default":true})
     */
    protected $Allow_Forwarding = true;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Forward_Save", type="smallint", nullable=true)
     */
    protected $Forward_Save;

    /**
     * @var array
     *
     * @ORM\Column(name="Content_Sections", type="string", length=255)
     */
    protected $Content_Sections = 'N,N,N,N,N,N,N,N';

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Enable_Lifecycle", type="boolean")
     */
    protected $Enable_Lifecycle = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Initial_Status", type="string", length=255, nullable=true)
     */
    protected $Initial_Status;

    /**
     * @var string
     * 
     * @ORM\Column(name="Final_Status", type="string", length=255)
     */
    protected $Final_Status = 'Released';

    /**
     * @var string
     * 
     * @ORM\Column(name="Superseded_Status", type="string", length=255, nullable=true)
     */
    protected $Superseded_Status;

    /**
     * @var string
     * 
     * @ORM\Column(name="Discarded_Status", type="string", length=255, nullable=true)
     */
    protected $Discarded_Status;

    /**
     * @var string
     * 
     * @ORM\Column(name="Archived_Status", type="string", length=255)
     */
    protected $Archived_Status = 'Archived';

    /**
     * @var string
     * 
     * @ORM\Column(name="Deleted_Status", type="string", length=255)
     */
    protected $Deleted_Status = 'Deleted';

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Enable_Versions", type="boolean")
     */
    protected $Enable_Versions = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Restrict_Live_Drafts", type="boolean")
     */
    protected $Restrict_Live_Drafts = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Show_Headers", type="boolean")
     */
    protected $Enable_Workflow = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Strict_Versioning", type="boolean")
     */
    protected $Strict_Versioning = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Allow_Retract", type="boolean")
     */
    protected $Allow_Retract = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Restrict_Drafts", type="boolean")
     */
    protected $Restrict_Drafts = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Update_Bookmarks", type="boolean")
     */
    protected $Update_Bookmarks = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Hide_Workflow", type="boolean")
     */
    protected $Hide_Workflow = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Disable_Delete_In_Workflow", type="boolean")
     */
    protected $Disable_Delete_In_Workflow = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Start_Button_Label", type="text", nullable=true)
     */
    protected $Custom_Start_Button_Label;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Release_Button_Label", type="text", nullable=true)
     */
    protected $Custom_Release_Button_Label;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_JS_WF_Start", type="text", nullable=true)
     */
    protected $Custom_JS_WF_Start;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_JS_WF_Complete", type="text", nullable=true)
     */
    protected $Custom_JS_WF_Complete;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_JS_WF_Approve", type="text", nullable=true)
     */
    protected $Custom_JS_WF_Approve;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_JS_WF_Deny", type="text", nullable=true)
     */
    protected $Custom_JS_WF_Deny;

    /**
     * @var string
     * 
     * @ORM\Column(name="Hide_Buttons", type="string", length=5, nullable=true)
     */
    protected $Hide_Buttons;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Buttons_Hide_When", type="text", nullable=true)
     */
    protected $Custom_Buttons_Hide_When;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Review_Button_Label", type="string", length=255, nullable=true)
     */
    protected $Custom_Review_Button_Label;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_HTML_Head", type="text", nullable=true)
     */
    protected $Custom_HTML_Head;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_WQO_Agent", type="text", nullable=true)
     */
    protected $Custom_WQO_Agent;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Edit_Button_Label", type="string", length=255, nullable=true)
     */
    protected $Custom_Edit_Button_Label;

    /**
     * @var string
     * 
     * @ORM\Column(name="Edit_Document_CustomJS", type="text", nullable=true)
     */
    protected $EditDocument_CustomJS;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Edit_Button_Hide_When", type="text", nullable=true)
     */
    protected $Custom_Edit_Button_Hide_When;

    /**
     * @var string
     * 
     * @ORM\Column(name="Cursor_Focus_Field", type="string", length=255, nullable=true)
     */
    protected $Cursor_Focus_Field = 'Subject';

    /**
     * @var string
     * 
     * @ORM\Column(name="OnLoad_CustomJS", type="text", nullable=true)
     */
    protected $OnLoad_CustomJS;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="OnUnLoad_CustomJS", type="text", nullable=true)
     */
    protected $OnUnload_CustomJS;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Restrict_Printing", type="text", nullable=true)
     */
    protected $Custom_Restrict_Printing;

    /**
     * @var string
     *
     * @ORM\Column(name="Print_CustomJS", type="text", nullable=true)
     */
    protected $Print_CustomJS;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Save_Button_Label", type="string", length=255, nullable=true)
     */
    protected $Custom_Save_Button_Label;

    /**
     * @var string
     * 
     * @ORM\Column(name="Save_Close_CustomJS", type="text", nullable=true)
     */
    protected $SaveClose_CustomJS;

    /**
     * @var string
     * 
     * @ORM\Column(name="Save_Button_Hide_When", type="text", nullable=true)
     */
    protected $Save_Button_Hide_When;

    /**
     * @var string
     * 
     * @ORM\Column(name="Before_Release_CustomJS", type="text", nullable=true)
     */
    protected $BeforeRelease_CustomJS;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="After_Release_CustomJS", type="text", nullable=true)
     */
    protected $AfterRelease_CustomJS;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Validate_Choice", type="smallint")
     */
    protected $Validate_Choice = 0;

    /**
     * @var string
     * 
     * @ORM\Column(name="Validate_Field_Names", type="string", length=255, nullable=true)
     */
    protected $Validate_Field_Names;

    /**
     * @var string
     * 
     * @ORM\Column(name="Validate_Field_Types", type="string", length=255, nullable=true)
     */
    protected $Validate_Field_Types;

    /**
     * @var string
     * 
     * @ORM\Column(name="Validate_Field_Labels", type="string", length=255, nullable=true)
     */
    protected $Validate_Field_Labels;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_Validate_JS", type="text", nullable=true)
     */
    protected $Custom_Validate_JS;

    /**
     * @var string
     * 
     * @ORM\Column(name="Custom_WQS_Agent", type="text", nullable=true)
     */
    protected $Custom_WQS_Agent;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Update_Full_Text_Index", type="boolean")
     */
    protected $Update_Full_Text_Index = false;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id")
     */
    protected $Creator;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $Date_Created;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id", nullable=true)
     */
    protected $Modifier;
    
    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Modified", type="datetime", nullable=true)
     */
    protected $Date_Modified;

    /**
     * @var string
     * 
     * @ORM\Column(name="Filter_Attachment_Script", type="string", length=255, nullable=true)
     */
    protected $filterAttachmentScript;
    
    /**
     * @ORM\OneToMany(targetEntity="DocTypeSubforms", mappedBy="DocType")
     * @ORM\OrderBy({"Subform_Order" = "ASC"})
     * @var ArrayCollection
     */
    protected $DocTypeSubform;
    
    /**
     * @ORM\ManyToMany(targetEntity="Libraries", mappedBy="ApplicableDocType")
     * @var ArrayCollection
     */
    protected $ApplicableLibrary;

    /**
     * @ORM\ManyToMany(targetEntity="Folders", mappedBy="ApplicableDocType")
     * @var ArrayCollection
     */
    protected $ApplicableFolder;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="Workflow", inversedBy="DocType")
     * @ORM\JoinTable(name="tb_doc_type_workflows",
     *      joinColumns={@ORM\JoinColumn(name="Doc_Type_Id", referencedColumnName="id", nullable=false)},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Workflow_Id", referencedColumnName="id", nullable=false)})
     */
    protected $DocTypeWorkflow;

    
    /**
     * Initialize DocTypeSubform
     */
    public function __construct()
    {
    	$this->DocTypeWorkflow = new ArrayCollection();
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
     * Set Doc_Name
     *
     * @param string $docName
     * @return DocumentTypes
     */
    public function setDocName($docName)
    {
        $this->Doc_Name = $docName;
    
        return $this;
    }

    /**
     * Get Doc_Name
     *
     * @return string 
     */
    public function getDocName()
    {
        return $this->Doc_Name;
    }

    /**
     * Set Background_Color
     *
     * @param string $backgroundColor
     * @return DocumentTypes
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->Background_Color = $backgroundColor;
    
        return $this;
    }

    /**
     * Get Background_Color
     *
     * @return string 
     */
    public function getBackgroundColor()
    {
        return $this->Background_Color;
    }

    /**
     * Set Subject_Label
     *
     * @param string|null $subjectLabel
     * @return DocumentTypes
     */
    public function setSubjectLabel($subjectLabel)
    {
        $this->Subject_Label = $subjectLabel;
    
        return $this;
    }

    /**
     * Get Subject_Label
     *
     * @return string 
     */
    public function getSubjectLabel()
    {
        return $this->Subject_Label;
    }

    /**
     * Set Top_Banner
     *
     * @param string $topBanner
     * @return DocumentTypes
     */
    public function setTopBanner($topBanner = null)
    {
        $this->Top_Banner = $topBanner;
    
        return $this;
    }

    /**
     * Get Top_Banner
     *
     * @return string 
     */
    public function getTopBanner()
    {
        return $this->Top_Banner;
    }

    /**
     * Set Section_Label
     *
     * @param string|null $sectionLabel
     * @return DocumentTypes
     */
    public function setSectionLabel($sectionLabel = null)
    {
        $this->Section_Label = $sectionLabel;
    
        return $this;
    }

    /**
     * Get Section_Label
     *
     * @return string 
     */
    public function getSectionLabel()
    {
        return $this->Section_Label;
    }

    /**
     * Set Section_Style
     *
     * @param boolean $sectionStyle
     * @return DocumentTypes
     */
    public function setSectionStyle($sectionStyle)
    {
        $this->Section_Style = $sectionStyle;
    
        return $this;
    }

    /**
     * Get Section_Style
     *
     * @return boolean 
     */
    public function getSectionStyle()
    {
        return $this->Section_Style;
    }

    /**
     * Set Description
     *
     * @param string $description
     * @return DocumentTypes
     */
    public function setDescription($description = null)
    {
        $this->Description = $description;
    
        return $this;
    }

    /**
     * Get Description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * Set Hide_Subject
     * 
     * @param boolean $hide
     * @return DocumentTypes
     */
    public function setHideSubject($hide)
    {
    	$this->Hide_Subject = $hide;
    	
    	return $this;
    }

    /**
     * Get Hide_Subject
     * 
     * @return boolean
     */
    public function getHideSubject()
    {
    	return $this->Hide_Subject;
    }

    /**
     * Set Hide_On_Reading
     * 
     * @param boolean $hide
     * @return DocumentTypes
     */
    public function setHideOnReading($hide)
    {
    	$this->Hide_On_Reading = $hide;
    	
    	return $this;
    }

    /**
     * Get Hide_On_Reading
     * 
     * @return boolean
     */
    public function getHideOnReading()
    {
    	return $this->Hide_On_Reading;
    }

    /**
     * Set Hide_On_Editing
     * 
     * @param boolean $hide
     * @return DocumentTypes
     */
    public function setHideOnEditing($hide)
    {
    	$this->Hide_On_Editing = $hide;
    	
    	return $this;
    }

    /**
     * Get Hide_On_Editing
     * 
     * @return boolean
     */
    public function getHideOnEditing()
    {
    	return $this->Hide_On_Editing;
    }

    /**
     * Set Translate_Subject_To
     * 
     * @param string|null $field_name
     * @return DocumentTypes
     */
    public function setTranslateSubjectTo($field_name = null)
    {
    	$this->Translate_Subject_To = $field_name;
    	
    	return $this;
    }

    /**
     * Get Translate_Subject_To
     * 
     * @return string
     */
    public function getTranslateSubjectTo()
    {
    	return $this->Translate_Subject_To;
    }

    /**
     * Set OnLoad_CustomJS
     * 
     * @param string $custom_js
     * @return DocumentTypes
     */
    public function setOnLoadCustomJS($custom_js = null)
    {
    	$this->OnLoad_CustomJS = $custom_js;
    	
    	return $this;
    }

    /**
     * Get OnLoad_CustomJS
     * 
     * @return string
     */
    public function getOnLoadCustomJS()
    {
    	return $this->OnLoad_CustomJS;
    }

    /**
     * Set OnUnLoad_CustomJS
     * 
     * @param string $custom_js
     * @return DocumentTypes
     */
    public function setOnUnLoadCustomJS($custom_js = null)
    {
    	$this->OnUnload_CustomJS = $custom_js;
    	 
    	return $this;
    }

    /**
     * Get OnUnLoad_CustomJS
     * 
     * @return string
     */
    public function getOnUnLoadCustomJS()
    {
    	return $this->OnUnload_CustomJS;
    }

    /**
     * Set EditDocument_CustomJS
     * 
     * @param string $custom_js
     * @return DocumentTypes
     */
    public function setEditDocumentCustomJS($custom_js = null)
    {
    	$this->EditDocument_CustomJS = $custom_js;
    	 
    	return $this;
    }

    /**
     * Get EditDocument_CustomJS
     * 
     * @return string
     */
    public function getEditDocumentCustomJS()
    {
    	return $this->EditDocument_CustomJS;
    }

    /**
     * Set SaveClose_CustomJS
     * 
     * @param string $custom_js
     * @return DocumentTypes
     */
    public function setSaveCloseCustomJS($custom_js = null)
    {
    	$this->SaveClose_CustomJS = $custom_js;
    	 
    	return $this;
    }

    /**
     * Get SaveClose_CustomJS
     * 
     * @return string
     */
    public function getSaveCloseCustomJS()
    {
    	return $this->SaveClose_CustomJS;
    }

    /**
     * Set BeforeRelease_CustomJS
     * 
     * @param string $custom_js
     * @return DocumentTypes
     */
    public function setBeforeReleaseCustomJS($custom_js = null)
    {
    	$this->BeforeRelease_CustomJS = $custom_js;
    	 
    	return $this;
    }

    /**
     * Get BeforeRelease_CustomJS
     * 
     * @return string
     */
    public function getBeforeReleaseCustomJS()
    {
    	return $this->BeforeRelease_CustomJS;
    }

    /**
     * Set AfterRelease_CustomJS
     * 
     * @param string $custom_js
     * @return DocumentTypes
     */
    public function setAfterReleaseCustomJS($custom_js = null)
    {
    	$this->AfterRelease_CustomJS = $custom_js;
    	 
    	return $this;
    }

    /**
     * Get AfterRelease_CustomJS
     * 
     * @return string
     */
    public function getAfterReleaseCustomJS()
    {
    	return $this->AfterRelease_CustomJS;
    }

    /**
     * Set Print_CustomJS
     * 
     * @param string $custom_js
     * @return DocumentTypes
     */
    public function setPrintCustomJS($custom_js = null)
    {
    	$this->Print_CustomJS = $custom_js;
    	 
    	return $this;
    }

    /**
     * Get Print_CustomJS
     * 
     * @return string
     */
    public function getPrintCustomJS()
    {
    	return $this->Print_CustomJS;
    }
    
    /**
     * Set Creator
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $Creator
     */
    public function setCreator(\Docova\DocovaBundle\Entity\UserAccounts $Creator)
    {
        $this->Creator = $Creator;
    }

    /**
     * Get Creator
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts $Creator 
     */
    public function getCreator()
    {
        return $this->Creator;
    }

    /**
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return DocumentTypes
     */
    public function setDateCreated($dateCreated)
    {
        $this->Date_Created = $dateCreated;
    
        return $this;
    }

    /**
     * Get Date_Created
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->Date_Created;
    }

    /**
     * Set Modifier
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $modifier
     * @return DocumentTypes
     */
    public function setModifier(\Docova\DocovaBundle\Entity\UserAccounts $modifier)
    {
    	$this->Modifier = $modifier;
    	
    	return $this;
    }

    /**
     * Get Modifier
     * 
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getModifier()
    {
    	return $this->Modifier;
    }

    /**
     * Set Date_Modified
     * 
     * @param \DateTime $date_modified
     * @return DocumentTypes
     */
    public function setDateModified($date_modified)
    {
    	$this->Date_Modified = $date_modified;
    	
    	return $this;
    }

    /**
     * Get Date_Modified
     * 
     * @return \DateTime
     */
    public function getDateModified()
    {
    	return $this->Date_Modified;
    }

    /**
     * Get DocTypeSubform
     * 
     * @return ArrayCollection
     */
    public function getDocTypeSubform()
    {
    	return $this->DocTypeSubform;
    }

    /**
     * Remove DocTypeSubform
     * 
     * @param \Docova\DocovaBundle\Entity\DocTypeSubforms $subform
     */
    public function removeDocTypeSubform(\Docova\DocovaBundle\Entity\DocTypeSubforms $subform)
    {
    	$this->DocTypeSubform->removeElement($subform);
    }

    /**
     * Add DocTypeWorkflow
     * 
     * @param \Docova\DocovaBundle\Entity\Workflow $workflow
     */
    public function addDocTypeWorkflow(\Docova\DocovaBundle\Entity\Workflow $workflow)
    {
    	$this->DocTypeWorkflow[] = $workflow;
    }

    /**
     * Get DocTypeWorkflow
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDocTypeWorkflow()
    {
    	return $this->DocTypeWorkflow;
    }

    /**
     * Remove DocTypeWorkflow
     * 
     * @param \Docova\DocovaBundle\Entity\Workflow $workflow
     */
    public function removeDocTypeWorkflow(\Docova\DocovaBundle\Entity\Workflow $workflow)
    {
    	$this->DocTypeWorkflow->removeElement($workflow);
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
     * Set Trash
     *
     * @param boolean $Trash
     * @return DocumentTypes
     */
    public function setTrash($Trash)
    {
    	$this->Trash = $Trash;
    
    	return $this;
    }

	/**
	 * Get Status
	 * 
	 * @return boolean
	 */
	public function getStatus() 
	{
		return $this->Status;
	}
	
	/**
	 * Set Status
	 * 
	 * @param boolean $Status
	 * @return DocumentTypes
	 */
	public function setStatus($Status) 
	{
		$this->Status = $Status;
		
		return $this;
	}
	
	/**
	 * Get Key_Type
	 * 
	 * @return integer
	 */
	public function getKeyType() 
	{
		return $this->Key_Type;
	}
	
	/**
	 * Set Key_Type
	 * 
	 * @param integer $Key_Type
	 * @return DocumentTypes
	 */
	public function setKeyType($Key_Type) 
	{
		$this->Key_Type = $Key_Type;
		
		return $this;
	}
	
	/**
	 * Get Doc_Icon
	 * 
	 * @return string
	 */
	public function getDocIcon() 
	{
		return $this->Doc_Icon;
	}
	
	/**
	 * Set Doc_Icon
	 * 
	 * @param string $Doc_Icon
	 * @return DocumentTypes
	 */
	public function setDocIcon($Doc_Icon) 
	{
		$this->Doc_Icon = $Doc_Icon;
		
		return $this;
	}
	
	/**
	 * Get Paper_Color
	 * 
	 * @return string
	 */
	public function getPaperColor() 
	{
		return $this->Paper_Color;
	}
	
	/**
	 * Set Paper_Color
	 * 
	 * @param string $Paper_Color
	 * @return DocumentTypes        	
	 */
	public function setPaperColor($Paper_Color = null) 
	{
		$this->Paper_Color = $Paper_Color;

		return $this;
	}
	
	/**
	 * Get Disable_Activities
	 * 
	 * @return boolean
	 */
	public function getDisableActivities() 
	{
		return $this->Disable_Activities;
	}
	
	/**
	 * Set Disable_Activities
	 * 
	 * @param boolean $Disable_Activities
	 * @return DocumentTypes        	
	 */
	public function setDisableActivities($Disable_Activities) 
	{
		$this->Disable_Activities = $Disable_Activities;
		
		return $this;
	}
	
	/**
	 * Get More_Section_Label
	 * 
	 * @return string
	 */
	public function getMoreSectionLabel() 
	{
		return $this->More_Section_Label;
	}
	
	/**
	 * Set More_Section_Label
	 * 
	 * @param string $More_Section_Label
	 * @return DocumentTypes
	 */
	public function setMoreSectionLabel($More_Section_Label) 
	{
		$this->More_Section_Label = $More_Section_Label;
		
		return $this;
	}
	
	/**
	 * Get Hide_On_Custom
	 * 
	 * @return boolean
	 */
	public function getHideOnCustom() 
	{
		return $this->Hide_On_Custom;
	}
	
	/**
	 * Set Hide_On_Custom
	 * 
	 * @param boolean $Hide_On_Custom
	 * @return DocumentTypes        	
	 */
	public function setHideOnCustom($Hide_On_Custom) 
	{
		$this->Hide_On_Custom = $Hide_On_Custom;
		
		return $this;
	}
	
	/**
	 * Get Bottom_Banner
	 * 
	 * @return string
	 */
	public function getBottomBanner() 
	{
		return $this->Bottom_Banner;
	}
	
	/**
	 * Set Bottom_Banner
	 * 
	 * @param string $Bottom_Banner
	 * @return DocumentTypes
	 */
	public function setBottomBanner($Bottom_Banner = null) 
	{
		$this->Bottom_Banner = $Bottom_Banner;
		
		return $this;
	}
	
	/**
	 * Get Additional_CSS
	 * 
	 * @return string
	 */
	public function getAdditionalCss() 
	{
		return $this->Additional_CSS;
	}
	
	/**
	 * Set Additional_CSS
	 * 
	 * @param string $Additional_CSS
	 * @return DocumentTypes        	
	 */
	public function setAdditionalCss($Additional_CSS = null) 
	{
		$this->Additional_CSS = $Additional_CSS;
		
		return $this;
	}
	
	/**
	 * Get Enable_For_DLE
	 * 
	 * @return boolean
	 */
	public function getEnableForDle() 
	{
		return $this->Enable_For_DLE;
	}
	
	/**
	 * Set Enable_For_DLE
	 * 
	 * @param boolean $Enable_For_DLE    
	 * @return DocumentTypes    	
	 */
	public function setEnableForDle($Enable_For_DLE) 
	{
		$this->Enable_For_DLE = $Enable_For_DLE;
		
		return $this;
	}
	
	/**
	 * Get Hide_Docs_From_Doe
	 * 
	 * @return boolean
	 */
	public function getHideDocsFromDoe()
	{
		return $this->Hide_Docs_From_Doe;
	}
	
	/**
	 * Set Hide_Docs_From_Doe
	 * 
	 * @param boolean $hideDocsFromDoe
	 * @return DocumentTypes
	 */
	public function setHideDocsFromDoe($hideDocsFromDoe)
	{
		$this->Hide_Docs_From_Doe = $hideDocsFromDoe;
		
		return $this;
	}
	
	/**
	 * Get Hide_Docs_From_Mobile
	 * 
	 * @return boolean
	 */
	public function getHideDocsFromMobile()
	{
		return $this->Hide_Docs_From_Mobile;
	}
	
	/**
	 * Set Hide_Docs_From_Mobile
	 * 
	 * @param boolean $hideDocsFromMobile
	 * @return DocumentTypes
	 */
	public function setHideDocsFromMobile($hideDocsFromMobile)
	{
		$this->Hide_Docs_From_Mobile = $hideDocsFromMobile;
		
		return $this;
	}
	
	/**
	 * Get Enable_Mail_Acquire
	 * 
	 * @return boolean
	 */
	public function getEnableMailAcquire() 
	{
		return $this->Enable_Mail_Acquire;
	}
	
	/**
	 * Set Enable_Mail_Acquire
	 * 
	 * @param boolean $Enable_Mail_Acquire
	 * @return DocumentTypes        	
	 */
	public function setEnableMailAcquire($Enable_Mail_Acquire) 
	{
		$this->Enable_Mail_Acquire = $Enable_Mail_Acquire;
		
		return $this;
	}
	
	/**
	 * Get Enable_Discussion
	 * 
	 * @return boolean
	 */
	public function getEnableDiscussion() 
	{
		return $this->Enable_Discussion;
	}
	
	/**
	 * Set Enable_Discussion
	 * 
	 * @param boolean $Enable_Discussion
	 * @return DocumentTypes        	
	 */
	public function setEnableDiscussion($Enable_Discussion) 
	{
		$this->Enable_Discussion = $Enable_Discussion;
		
		return $this;
	}
	
	/**
	 * Get Prompt_For_Metadata
	 * 
	 * @return boolean
	 */
	public function getPromptForMetadata()
	{
		return $this->Prompt_For_Metadata;
	}
	
	/**
	 * Set Prompt_For_Metadata
	 * 
	 * @param boolean $promptForMetadata
	 * @return DocumentTypes
	 */
	public function setPromptForMetadata($promptForMetadata)
	{
		$this->Prompt_For_Metadata = $promptForMetadata;
		
		return $this;
	}
	
	/**
	 * Get Custom_HTML_Head
	 * 
	 * @return string
	 */
	public function getCustomHtmlHead() 
	{
		return $this->Custom_HTML_Head;
	}
	
	/**
	 * Set Custom_HTML_Head
	 * 
	 * @param string $Custom_HTML_Head
	 * @return DocumentTypes        	
	 */
	public function setCustomHtmlHead($Custom_HTML_Head = null) 
	{
		$this->Custom_HTML_Head = $Custom_HTML_Head;
		
		return $this;
	}
	
	/**
	 * Get Cursor_Focus_Field
	 * 
	 * @return string
	 */
	public function getCursorFocusField() 
	{
		return $this->Cursor_Focus_Field;
	}
	
	/**
	 * Set Cursor_Focus_Field
	 * 
	 * @param string $Cursor_Focus_Field
	 * @return DocumentTypes        	
	 */
	public function setCursorFocusField($Cursor_Focus_Field = null) 
	{
		$this->Cursor_Focus_Field = $Cursor_Focus_Field;
		
		return $this;
	}
	
	/**
	 * Get Validate_Choice
	 * 
	 * @return integer
	 */
	public function getValidateChoice() 
	{
		return $this->Validate_Choice;
	}
	
	/**
	 * Set Validate_Choice
	 * 
	 * @param integer $Validate_Choice
	 * @return DocumentTypes        	
	 */
	public function setValidateChoice($Validate_Choice) 
	{
		$this->Validate_Choice = $Validate_Choice;
		
		return $this;
	}
	
	/**
	 * Get Validate_Field_Names
	 * 
	 * @return string
	 */
	public function getValidateFieldNames() 
	{
		return $this->Validate_Field_Names;
	}
	
	/**
	 * Set Validate_Field_Names
	 * 
	 * @param string $Validate_Field_Names
	 * @return DocumentTypes        	
	 */
	public function setValidateFieldNames($Validate_Field_Names = null) 
	{
		$this->Validate_Field_Names = $Validate_Field_Names;
		
		return $this;
	}
	
	/**
	 * Get Validate_Field_Labels
	 * 
	 * @return string
	 */
	public function getValidateFieldLabels() 
	{
		return $this->Validate_Field_Labels;
	}
	
	/**
	 * Set Validate_Field_Labels
	 * 
	 * @param string $Validate_Field_Labels
	 * @return DocumentTypes        	
	 */
	public function setValidateFieldLabels($Validate_Field_Labels = null) 
	{
		$this->Validate_Field_Labels = $Validate_Field_Labels;
		
		return $this;
	}
	
	/**
	 * Get Custom_Validate_JS
	 * 
	 * @return string
	 */
	public function getCustomValidateJs() 
	{
		return $this->Custom_Validate_JS;
	}
	
	/**
	 * Set Custom_Validate_JS
	 * 
	 * @param string $Custom_Validate_JS
	 * @return DocumentTypes        	
	 */
	public function setCustomValidateJs($Custom_Validate_JS = null) 
	{
		$this->Custom_Validate_JS = $Custom_Validate_JS;
		
		return $this;
	}
	
	/**
	 * Get Update_Full_Text_Index
	 * 
	 * @return boolean
	 */
	public function getUpdateFullTextIndex() 
	{
		return $this->Update_Full_Text_Index;
	}
	
	/**
	 * Set Update_Full_Text_Index
	 * 
	 * @param boolean $Update_Full_Text_Index
	 * @return DocumentTypes        	
	 */
	public function setUpdateFullTextIndex($Update_Full_Text_Index) 
	{
		$this->Update_Full_Text_Index = $Update_Full_Text_Index;

		return $this;
	}
	
	/**
	 * Get Custom_Subject_Formula
	 * 
	 * @return string
	 */
	public function getCustomSubjectFormula() 
	{
		return $this->Custom_Subject_Formula;
	}
	
	/**
	 * Set Custom_Subject_Formula
	 * 
	 * @param string $Custom_Subject_Formula
	 * @return DocumentTypes
	 */
	public function setCustomSubjectFormula($Custom_Subject_Formula = null) 
	{
		$this->Custom_Subject_Formula = $Custom_Subject_Formula;
		return $this;
	}
	
	/**
	 * Get Custom_Header_Hide_When
	 * 
	 * @return string
	 */
	public function getCustomHeaderHideWhen() 
	{
		return $this->Custom_Header_Hide_When;
	}
	
	/**
	 * Set Custom_Header_Hide_When
	 * 
	 * @param string $Custom_Header_Hide_When
	 * @return DocumentTypes
	 */
	public function setCustomHeaderHideWhen($Custom_Header_Hide_When = null) 
	{
		$this->Custom_Header_Hide_When = $Custom_Header_Hide_When;
		return $this;
	}
	
	/**
	 * Get Custom_Change_Doc_Author
	 * 
	 * @param boolean $process
	 * @return string|boolean
	 */
	public function getCustomChangeDocAuthor($process = false) 
	{
		if ($process === true) 
		{
			if (empty($this->Custom_Change_Doc_Author)) {
				return true;
			}
			else {
				//@TODO: Complete the process of the php scripts used in this field
			}
		}
		else {
			return $this->Custom_Change_Doc_Author;
		}
	}
	
	/**
	 * Set Custom_Change_Doc_Author
	 * 
	 * @param string $Custom_Change_Doc_Author
	 * @return DocumentTypes
	 */
	public function setCustomChangeDocAuthor($Custom_Change_Doc_Author = null) 
	{
		$this->Custom_Change_Doc_Author = $Custom_Change_Doc_Author;
		return $this;
	}
	
	/**
	 * Get Custom_Hide_Description
	 * 
	 * @return string
	 */
	public function getCustomHideDescription($process = false) 
	{
		if ($process === true) 
		{
			if (empty($this->Custom_Hide_Description)) {
				return true;
			}
			else {
				//@TODO: Complete the process of the php scripts used in this field
			}
		}
		else {
			return $this->Custom_Hide_Description;
		}
	}
	
	/**
	 * Set Custom_Hide_Description
	 * 
	 * @param string $Custom_Hide_Description
	 * @return DocumentTypes
	 */
	public function setCustomHideDescription($Custom_Hide_Description = null) 
	{
		$this->Custom_Hide_Description = $Custom_Hide_Description;
		return $this;
	}
	
	/**
	 * Get Custom_Hide_Keywords
	 * 
	 * @param boolean $process
	 * @return string|null
	 */
	public function getCustomHideKeywords($process = false) 
	{
		if ($process === true) 
		{
			if (empty($this->Custom_Hide_Keywords)) {
				return true;
			}
			else {
				//@TODO: Complete the process of the php scripts used in this field
			}
		}
		else {
			return $this->Custom_Hide_Keywords;
		}
	}
	
	/**
	 * Set Custom_Hide_Keywords
	 * 
	 * @param string $Custom_Hide_Keywords
	 * @return DocumentTypes
	 */
	public function setCustomHideKeywords($Custom_Hide_Keywords = null) 
	{
		$this->Custom_Hide_Keywords = $Custom_Hide_Keywords;
		return $this;
	}
	
	/**
	 * Get Custom_Hide_Review_Cycle
	 * 
	 * @param boolean $process
	 * @return string|boolean
	 */
	public function getCustomHideReviewCycle($process = false) 
	{
		if ($process === true) 
		{
			if (empty($this->Custom_Hide_Review_Cycle)) {
				return true;
			}
			else {
				//@TODO: Complete the process of the php scripts used in this field
			}
		}
		else {
			return $this->Custom_Hide_Review_Cycle;
		}
	}
	
	/**
	 * Set Custom_Hide_Review_Cycle
	 * 
	 * @param string $Custom_Hide_Review_Cycle
	 * @return DocumentTypes
	 */
	public function setCustomHideReviewCycle($Custom_Hide_Review_Cycle = null) 
	{
		$this->Custom_Hide_Review_Cycle = $Custom_Hide_Review_Cycle;
		return $this;
	}
	
	/**
	 * Get Custom_Hide_Archiving
	 * 
	 * @param boolean $process
	 * @return string|boolean
	 */
	public function getCustomHideArchiving($process = false) 
	{
		if ($process === true) 
		{
			if (empty($this->Custom_Hide_Archiving)) {
				return true;
			}
			else {
				//@TODO: Complete the process of the php scripts used in this field
			}
		}
		else {
			return $this->Custom_Hide_Archiving;
		}
	}
	
	/**
	 * Set Custom_Hide_Archiving
	 * 
	 * @param string $Custom_Hide_Archiving
	 * @return DocumentTypes
	 */
	public function setCustomHideArchiving($Custom_Hide_Archiving = null) 
	{
		$this->Custom_Hide_Archiving = $Custom_Hide_Archiving;
		return $this;
	}
	
	/**
	 * Get Custom_Change_Owner
	 * 
	 * @param boolean $process
	 * @return string|boolean
	 */
	public function getCustomChangeOwner($process = false) 
	{
		if ($process === true) 
		{
			if (empty($this->Custom_Change_Owner)) {
				return true;
			}
			else {
				//@TODO: Complete the process of the php scripts used in this field
			}
		}
		else {
			return $this->Custom_Change_Owner;
		}
	}
	
	/**
	 * Set Custom_Change_Owner
	 * 
	 * @param string $Custom_Change_Owner
	 * @return DocumentTypes
	 */
	public function setCustomChangeOwner($Custom_Change_Owner = null) 
	{
		$this->Custom_Change_Owner = $Custom_Change_Owner;
		return $this;
	}
	
	/**
	 * Get Custom_Change_Add_Editors
	 * 
	 * @param boolean $process
	 * @return string|boolean
	 */
	public function getCustomChangeAddEditors($process = false) 
	{
		if ($process === true) 
		{
			if (empty($this->Custom_Change_Add_Editors)) {
				return true;
			}
			else {
				//@TODO: Complete the process of the php scripts used in this field
			}
		}
		else {
			return $this->Custom_Change_Add_Editors;
		}
	}
	
	/**
	 * Set Custom_Change_Add_Editors
	 * 
	 * @param string $Custom_Change_Add_Editors
	 * @return DocumentTypes
	 */
	public function setCustomChangeAddEditors($Custom_Change_Add_Editors = null) 
	{
		$this->Custom_Change_Add_Editors = $Custom_Change_Add_Editors;
		return $this;
	}
	
	/**
	 * Get Enable_Lifecycle
	 * 
	 * @return boolean
	 */
	public function getEnableLifecycle() 
	{
		return $this->Enable_Lifecycle;
	}
	
	/**
	 * Set Enable_Lifecycle
	 * 
	 * @param boolean $Enable_Lifecycle
	 * @return DocumentTypes
	 */
	public function setEnableLifecycle($Enable_Lifecycle) 
	{
		$this->Enable_Lifecycle = $Enable_Lifecycle;
		return $this;
	}
	
	/**
	 * Get Initial_Status
	 * 
	 * @return string
	 */
	public function getInitialStatus() 
	{
		return $this->Initial_Status;
	}
	
	/**
	 * Set Initial_Status
	 * 
	 * @param string $Initial_Status
	 * @return DocumentTypes
	 */
	public function setInitialStatus($Initial_Status = null) 
	{
		$this->Initial_Status = $Initial_Status;
		return $this;
	}
	
	/**
	 * Get Final_Status
	 * 
	 * @return string
	 */
	public function getFinalStatus() 
	{
		return $this->Final_Status;
	}
	
	/**
	 * Set Final_Status
	 * 
	 * @param string $Final_Status
	 * @return DocumentTypes
	 */
	public function setFinalStatus($Final_Status) 
	{
		$this->Final_Status = $Final_Status;
		return $this;
	}
	
	/**
	 * Get Superseded_Status
	 * 
	 * @return string
	 */
	public function getSupersededStatus() 
	{
		return $this->Superseded_Status;
	}
	
	/**
	 * Set Superseded_Status
	 * 
	 * @param $Superseded_Status
	 * @return DocumentTypes
	 */
	public function setSupersededStatus($Superseded_Status = null) 
	{
		$this->Superseded_Status = $Superseded_Status;
		return $this;
	}
	
	/**
	 * Get Discarded_Status
	 * 
	 * @return string
	 */
	public function getDiscardedStatus() 
	{
		return $this->Discarded_Status;
	}
	
	/**
	 * Set Discarded_Status
	 * 
	 * @param string $Discarded_Status
	 * @return DocumentTypes
	 */
	public function setDiscardedStatus($Discarded_Status = null) 
	{
		$this->Discarded_Status = $Discarded_Status;
		return $this;
	}
	
	/**
	 * Get Archived_Status
	 * 
	 * @return string
	 */
	public function getArchivedStatus() 
	{
		return $this->Archived_Status;
	}
	
	/**
	 * Set Archived_Status
	 * 
	 * @param string $Archived_Status
	 * @return DocumentTypes
	 */
	public function setArchivedStatus($Archived_Status) 
	{
		$this->Archived_Status = $Archived_Status;
		return $this;
	}
	
	/**
	 * Get Deleted_Status
	 * 
	 * @return string
	 */
	public function getDeletedStatus() 
	{
		return $this->Deleted_Status;
	}
	
	/**
	 * Set Deleted_Status
	 * 
	 * @param string $Deleted_Status
	 * @return DocumentTypes
	 */
	public function setDeletedStatus($Deleted_Status) 
	{
		$this->Deleted_Status = $Deleted_Status;
		return $this;
	}
	
	/**
	 * Get Enable_Versions
	 * 
	 * @return boolean
	 */
	public function getEnableVersions() 
	{
		return $this->Enable_Versions;
	}
	
	/**
	 * Set Enable_Versions
	 * 
	 * @param boolean $Enable_Versions
	 * @return DocumentTypes
	 */
	public function setEnableVersions($Enable_Versions) 
	{
		$this->Enable_Versions = $Enable_Versions;
		return $this;
	}
	
	/**
	 * Get Restrict_Live_Drafts
	 * 
	 * @return boolean
	 */
	public function getRestrictLiveDrafts() 
	{
		return $this->Restrict_Live_Drafts;
	}
	
	/**
	 * Set Restrict_Live_Drafts
	 * 
	 * @param boolean $Restrict_Live_Drafts
	 * @return DocumentTypes
	 */
	public function setRestrictLiveDrafts($Restrict_Live_Drafts) 
	{
		$this->Restrict_Live_Drafts = $Restrict_Live_Drafts;
		return $this;
	}
	
	/**
	 * Get Enable_Workflow
	 * 
	 * @return boolean
	 */
	public function getEnableWorkflow() 
	{
		return $this->Enable_Workflow;
	}
	
	/**
	 * Set Enable_Workflow
	 * 
	 * @param boolean $Enable_Workflow
	 * @return DocumentTypes
	 */
	public function setEnableWorkflow($Enable_Workflow) 
	{
		$this->Enable_Workflow = $Enable_Workflow;
		return $this;
	}
	
	/**
	 * Get Strict_Versioning
	 * 
	 * @return boolean
	 */
	public function getStrictVersioning() 
	{
		return $this->Strict_Versioning;
	}
	
	/**
	 * Set Strict_Versioning
	 * 
	 * @param boolean $Strict_Versioning
	 * @return DocumentTypes
	 */
	public function setStrictVersioning($Strict_Versioning) 
	{
		$this->Strict_Versioning = $Strict_Versioning;
		return $this;
	}
	
	/**
	 * Get Allow_Retract
	 * 
	 * @return boolean
	 */
	public function getAllowRetract() 
	{
		return $this->Allow_Retract;
	}
	
	/**
	 * Set Allow_Retract
	 * 
	 * @param boolean $Allow_Retract
	 * @return DocumentTypes
	 */
	public function setAllowRetract($Allow_Retract) 
	{
		$this->Allow_Retract = $Allow_Retract;
		return $this;
	}
	
	/**
	 * Get Restrict_Drafts
	 * 
	 * @return boolean
	 */
	public function getRestrictDrafts() 
	{
		return $this->Restrict_Drafts;
	}
	
	/**
	 * Set Restrict_Drafts
	 * 
	 * @param boolean $Restrict_Drafts
	 * @return DocumentTypes
	 */
	public function setRestrictDrafts($Restrict_Drafts) 
	{
		$this->Restrict_Drafts = $Restrict_Drafts;
		return $this;
	}
	
	/**
	 * Get Update_Bookmarks
	 * 
	 * @return boolean
	 */
	public function getUpdateBookmarks() 
	{
		return $this->Update_Bookmarks;
	}
	
	/**
	 * Set Update_Bookmarks
	 * 
	 * @param boolean $Update_Bookmarks
	 * @return DocumentTypes
	 */
	public function setUpdateBookmarks($Update_Bookmarks) 
	{
		$this->Update_Bookmarks = $Update_Bookmarks;
		return $this;
	}
	
	/**
	 * Get Hide_Workflow
	 * 
	 * @return boolean
	 */
	public function getHideWorkflow() 
	{
		return $this->Hide_Workflow;
	}
	
	/**
	 * Set Hide_Workflow
	 * 
	 * @param boolean $Hide_Workflow
	 * @return DocumentTypes
	 */
	public function setHideWorkflow($Hide_Workflow) 
	{
		$this->Hide_Workflow = $Hide_Workflow;
		return $this;
	}
	
	/**
	 * Get Disable_Delete_In_Workflow
	 * 
	 * @return boolean
	 */
	public function getDisableDeleteInWorkflow() 
	{
		return $this->Disable_Delete_In_Workflow;
	}
	
	/**
	 * Set Disable_Delete_In_Workflow
	 * 
	 * @param boolean $Disable_Delete_In_Workflow
	 */
	public function setDisableDeleteInWorkflow($Disable_Delete_In_Workflow) 
	{
		$this->Disable_Delete_In_Workflow = $Disable_Delete_In_Workflow;
		return $this;
	}
	
	/**
	 * Get Custom_Start_Button_Label
	 * 
	 * @return string
	 */
	public function getCustomStartButtonLabel() 
	{
		return $this->Custom_Start_Button_Label;
	}
	
	/**
	 * Set Custom_Start_Button_Label
	 * 
	 * @param string $Custom_Start_Button_Label
	 * @return DocumentTypes
	 */
	public function setCustomStartButtonLabel($Custom_Start_Button_Label = null) 
	{
		$this->Custom_Start_Button_Label = $Custom_Start_Button_Label;
		return $this;
	}
	
	/**
	 * Get Custom_Release_Button_Label
	 * 
	 * @return string
	 */
	public function getCustomReleaseButtonLabel() 
	{
		return $this->Custom_Release_Button_Label;
	}
	
	/**
	 * Set Custom_Release_Button_Label
	 * 
	 * @param string $Custom_Release_Button_Label
	 * @return DocumentTypes
	 */
	public function setCustomReleaseButtonLabel($Custom_Release_Button_Label = null) 
	{
		$this->Custom_Release_Button_Label = $Custom_Release_Button_Label;
		return $this;
	}
	
	/**
	 * Get Hide_Buttons
	 * 
	 * @return string
	 */
	public function getHideButtons() 
	{
		return $this->Hide_Buttons;
	}
	
	/**
	 * Set Hide_Buttons
	 * 
	 * @param string $Hide_Buttons
	 * @return DocumentTypes
	 */
	public function setHideButtons($Hide_Buttons = null) 
	{
		$this->Hide_Buttons = $Hide_Buttons;
		return $this;
	}
	
	/**
	 * Get Custom_Buttons_Hide_When
	 * 
	 * @return string
	 */
	public function getCustomButtonsHideWhen() 
	{
		return $this->Custom_Buttons_Hide_When;
	}
	
	/**
	 * Set Custom_Buttons_Hide_When
	 * 
	 * @param string $Custom_Buttons_Hide_When
	 */
	public function setCustomButtonsHideWhen($Custom_Buttons_Hide_When = null) 
	{
		$this->Custom_Buttons_Hide_When = $Custom_Buttons_Hide_When;
		return $this;
	}
	
	/**
	 * Get Custom_Review_Button_Label
	 * 
	 * @return string
	 */
	public function getCustomReviewButtonLabel() 
	{
		return $this->Custom_Review_Button_Label;
	}
	
	/**
	 * Set Custom_Review_Button_Label
	 * 
	 * @param string $Custom_Review_Button_Label
	 * @return DocumentTypes
	 */
	public function setCustomReviewButtonLabel($Custom_Review_Button_Label = null) 
	{
		$this->Custom_Review_Button_Label = $Custom_Review_Button_Label;
		return $this;
	}
	
	/**
	 * Get Custom_WQO_Agent
	 * 
	 * @return string
	 */
	public function getCustomWqoAgent() {
		return $this->Custom_WQO_Agent;
	}
	
	/**
	 * Set Custom_WQO_Agent
	 * 
	 * @param string $Custom_WQO_Agent
	 * @return DocumentTypes
	 */
	public function setCustomWqoAgent($Custom_WQO_Agent = null) 
	{
		$this->Custom_WQO_Agent = $Custom_WQO_Agent;
		return $this;
	}
	
	/**
	 * Get Custom_Edit_Button_Label
	 * 
	 * @return string
	 */
	public function getCustomEditButtonLabel() 
	{
		return $this->Custom_Edit_Button_Label;
	}
	
	/**
	 * Set Custom_Edit_Button_Label
	 * 
	 * @param string $Custom_Edit_Button_Label
	 * @return DocumentTypes
	 */
	public function setCustomEditButtonLabel($Custom_Edit_Button_Label = null) 
	{
		$this->Custom_Edit_Button_Label = $Custom_Edit_Button_Label;
		return $this;
	}
	
	/**
	 * Get Custom_Edit_Button_Hide_When
	 * 
	 * @return string
	 */
	public function getCustomEditButtonHideWhen() 
	{
		return $this->Custom_Edit_Button_Hide_When;
	}
	
	/**
	 * Set Custom_Edit_Button_Hide_When
	 * 
	 * @param string $Custom_Edit_Button_Hide_When
	 * @return DocumentTypes
	 */
	public function setCustomEditButtonHideWhen($Custom_Edit_Button_Hide_When = null) 
	{
		$this->Custom_Edit_Button_Hide_When = $Custom_Edit_Button_Hide_When;
		return $this;
	}
	
	/**
	 * Get Custom_Restrict_Printing
	 * 
	 * @return string
	 */
	public function getCustomRestrictPrinting() 
	{
		return $this->Custom_Restrict_Printing;
	}
	
	/**
	 * Set Custom_Restrict_Printing
	 * 
	 * @param string $Custom_Restrict_Printing
	 * @return DocumentTypes
	 */
	public function setCustomRestrictPrinting($Custom_Restrict_Printing) 
	{
		$this->Custom_Restrict_Printing = $Custom_Restrict_Printing;
		return $this;
	}
	
	/**
	 * Get Custom_Save_Button_Label
	 * 
	 * @return string
	 */
	public function getCustomSaveButtonLabel() 
	{
		return $this->Custom_Save_Button_Label;
	}
	
	/**
	 * Set Custom_Save_Button_Label
	 * 
	 * @param string $Custom_Save_Button_Label
	 * @return DocumentTypes
	 */
	public function setCustomSaveButtonLabel($Custom_Save_Button_Label = null) 
	{
		$this->Custom_Save_Button_Label = $Custom_Save_Button_Label;
		return $this;
	}
	
	/**
	 * Get Save_Button_Hide_When
	 * 
	 * @return string
	 */
	public function getSaveButtonHideWhen() 
	{
		return $this->Save_Button_Hide_When;
	}
	
	/**
	 * Set Save_Button_Hide_When
	 * 
	 * @param string $Save_Button_Hide_When
	 * @return DocumentTypes
	 */
	public function setSaveButtonHideWhen($Save_Button_Hide_When = null) 
	{
		$this->Save_Button_Hide_When = $Save_Button_Hide_When;
		return $this;
	}
	
	/**
	 * Get Validate_Field_Types
	 * 
	 * @return string
	 */
	public function getValidateFieldTypes() 
	{
		return $this->Validate_Field_Types;
	}
	
	/**
	 * Set Validate_Field_Types
	 * 
	 * @param string $Validate_Field_Types
	 * @return DocumentTypes
	 */
	public function setValidateFieldTypes($Validate_Field_Types = null) 
	{
		$this->Validate_Field_Types = $Validate_Field_Types;
		return $this;
	}
	
	/**
	 * Get Custom_WQS_Agent
	 * 
	 * @return string
	 */
	public function getCustomWqsAgent() 
	{
		return $this->Custom_WQS_Agent;
	}
	
	/**
	 * Set Custom_WQS_Agent
	 * 
	 * @param string $Custom_WQS_Agent
	 * @return DocumentTypes
	 */
	public function setCustomWqsAgent($Custom_WQS_Agent = null) 
	{
		$this->Custom_WQS_Agent = $Custom_WQS_Agent;
		return $this;
	}

	/**
	 * Get Custom_Change_Read_Access
	 * 
	 * @param boolean $process
	 * @return string|boolean
	 */
	public function getCustomChangeReadAccess($process = false) 
	{
		if ($process === true) 
		{
			if (empty($this->Custom_Change_Read_Access)) {
				return true;
			}
			else {
				//@TODO: Complete the process of the php scripts used in this field
			}
		}
		else {
			return $this->Custom_Change_Read_Access;
		}
	}

	/**
	 * Set Custom_Change_Read_Access
	 * 
	 * @param string $Custom_Change_Read_Access
	 * @return DocumentTypes
	 */
	public function setCustomChangeReadAccess($Custom_Change_Read_Access = null) 
	{
		$this->Custom_Change_Read_Access = $Custom_Change_Read_Access;
		return $this;
	}
	
	/**
	 * Get Forward_Save
	 * 
	 * @return boolean
	 */
	public function getAllowForwarding() 
	{
		return $this->Allow_Forwarding;
	}
	
	/**
	 * Set Forward_Save
	 * 
	 * @param boolean $Allow_Forwarding
	 * @return DocumentTypes
	 */
	public function setAllowForwarding($Allow_Forwarding) 
	{
		$this->Allow_Forwarding = $Allow_Forwarding;
		return $this;
	}
	
	/**
	 * Get Forward_Save
	 * 
	 * @return integer
	 */
	public function getForwardSave() 
	{
		return $this->Forward_Save;
	}
	
	/**
	 * Set Forward_Save
	 * 
	 * @param integer $Forward_Save
	 * @return DocumentTypes
	 */
	public function setForwardSave($Forward_Save = null) 
	{
		$this->Forward_Save = $Forward_Save;
		return $this;
	}
	
	/**
	 * Get Content_Sections
	 * 
	 * @return array
	 */
	public function getContentSections() 
	{
		return explode(',', $this->Content_Sections);
	}
	
	/**
	 * Set Content_Sections
	 * 
	 * @param array|string $Content_Sections
	 * @return DocumentTypes        	
	 */
	public function setContentSections($Content_Sections) 
	{
		if (is_array($Content_Sections)) 
		{
			$this->Content_Sections = implode(',', $Content_Sections);
		}
		else {
			$this->Content_Sections = $Content_Sections;
		}
		return $this;
	}

    /**
     * Set Custom_JS_WF_Start
     *
     * @param string $customJSWFStart
     * @return DocumentTypes
     */
    public function setCustomJSWFStart($customJSWFStart = null)
    {
        $this->Custom_JS_WF_Start = $customJSWFStart;
    
        return $this;
    }

    /**
     * Get Custom_JS_WF_Start
     *
     * @return string 
     */
    public function getCustomJSWFStart()
    {
        return $this->Custom_JS_WF_Start;
    }

    /**
     * Set Custom_JS_WF_Complete
     *
     * @param string $customJSWFComplete
     * @return DocumentTypes
     */
    public function setCustomJSWFComplete($customJSWFComplete = null)
    {
        $this->Custom_JS_WF_Complete = $customJSWFComplete;
    
        return $this;
    }

    /**
     * Get Custom_JS_WF_Complete
     *
     * @return string 
     */
    public function getCustomJSWFComplete()
    {
        return $this->Custom_JS_WF_Complete;
    }

    /**
     * Set Custom_JS_WF_Approve
     *
     * @param string $customJSWFApprove
     * @return DocumentTypes
     */
    public function setCustomJSWFApprove($customJSWFApprove = null)
    {
        $this->Custom_JS_WF_Approve = $customJSWFApprove;
    
        return $this;
    }

    /**
     * Get Custom_JS_WF_Approve
     *
     * @return string 
     */
    public function getCustomJSWFApprove()
    {
        return $this->Custom_JS_WF_Approve;
    }

    /**
     * Set Custom_JS_WF_Deny
     *
     * @param string $customJSWFDeny
     * @return DocumentTypes
     */
    public function setCustomJSWFDeny($customJSWFDeny = null)
    {
        $this->Custom_JS_WF_Deny = $customJSWFDeny;
    
        return $this;
    }

    /**
     * Get Custom_JS_WF_Deny
     *
     * @return string 
     */
    public function getCustomJSWFDeny()
    {
        return $this->Custom_JS_WF_Deny;
    }

    /**
     * Set filterAttachmentScript
     * 
     * @param string $attachmentScript
     * @return DocumentTypes
     */
    public function setFilterAttachmentScript($attachmentScript = null)
    {
    	$this->filterAttachmentScript = $attachmentScript;
    	
    	return $this;
    }

    /**
     * Get filterAttachmentScript
     * 
     * @return string
     */
    public function getFilterAttachmentScript()
    {
    	return $this->filterAttachmentScript;
    }

    /**
     * Add DocTypeSubform
     *
     * @param \Docova\DocovaBundle\Entity\DocTypeSubforms $docTypeSubform
     * @return DocumentTypes
     */
    public function addDocTypeSubform(\Docova\DocovaBundle\Entity\DocTypeSubforms $docTypeSubform)
    {
        $this->DocTypeSubform[] = $docTypeSubform;
    
        return $this;
    }

    /**
     * Add ApplicableLibrary
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $applicableLibrary
     * @return DocumentTypes
     */
    public function addApplicableLibrary(\Docova\DocovaBundle\Entity\Libraries $applicableLibrary)
    {
        $this->ApplicableLibrary[] = $applicableLibrary;
    
        return $this;
    }

    /**
     * Remove ApplicableLibrary
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $applicableLibrary
     */
    public function removeApplicableLibrary(\Docova\DocovaBundle\Entity\Libraries $applicableLibrary)
    {
        $this->ApplicableLibrary->removeElement($applicableLibrary);
    }

    /**
     * Get ApplicableLibrary
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getApplicableLibrary()
    {
        return $this->ApplicableLibrary;
    }

    /**
     * Add ApplicableFolder
     *
     * @param \Docova\DocovaBundle\Entity\Folders $applicableFolder
     * @return DocumentTypes
     */
    public function addApplicableFolder(\Docova\DocovaBundle\Entity\Folders $applicableFolder)
    {
        $this->ApplicableFolder[] = $applicableFolder;
    
        return $this;
    }

    /**
     * Remove ApplicableFolder
     *
     * @param \Docova\DocovaBundle\Entity\Folders $applicableFolder
     */
    public function removeApplicableFolder(\Docova\DocovaBundle\Entity\Folders $applicableFolder)
    {
        $this->ApplicableFolder->removeElement($applicableFolder);
    }

    /**
     * Get ApplicableFolder
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getApplicableFolder()
    {
        return $this->ApplicableFolder;
    }
    
    /**
     * Get flags
     * 
     * @return number
     */
    public function getFlags()
    {
    	$sum = 0;
    	if ($this->Enable_Versions) {
    		$sum += 8;
    	}
    	if ($this->Strict_Versioning) {
    		$sum += 16;
    	}
    	if ($this->Disable_Delete_In_Workflow) {
    		$sum += 64;
    	}
    	if ($this->Allow_Forwarding) {
    		$sum += 128;
    	}
    	if ($this->Forward_Save == 1) {
    		$sum += 256;
    	}
    	elseif ($this->Forward_Save == 2) {
    		$sum += 512;
    	}
    	
    	return $sum;
    }
}