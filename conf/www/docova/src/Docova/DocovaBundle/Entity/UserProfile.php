<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserProfile
 *
 * @ORM\Table(name="tb_user_profile")
 * @ORM\Entity
 */
class UserProfile
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
     * @ORM\Column(name="First_Name", type="string", length=50)
     */
    protected $First_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Last_Name", type="string", length=50)
     */
    protected $Last_Name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Account_Type", type="boolean")
     */
    protected $Account_Type = false;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Display_Name", type="string", length=255)
     */
    protected $Display_Name;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Recent_Edit_Count", type="smallint", options={"default":10})
     */
    protected $Recent_Edit_Count = 10;

    /**
     * @var integer
     *
     * @ORM\Column(name="Recent_Used_App_Count", type="smallint", options={"default":5})
     */
    protected $Recent_Used_App_Count = 5;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Load_Library_Folders", type="boolean")
     */
    protected $Load_Library_Folders = true;

    /**
     * @var string
     *
     * @ORM\Column(name="User_Mail_System", type="string", length=1)
     */
    protected $User_Mail_System = 'N';

    /**
     * @var string
     * 
     * @ORM\Column(name="Mail_Server_Url", type="string", length=100)
     */
    protected $Mail_Server_URL;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Enable_Debug_Mode", type="boolean")
     */
    protected $Enable_Debug_Mode = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Outlook_Msgs_To_Show", type="integer", nullable=true)
     */
    protected $Outlook_Msgs_To_Show;

    /**
     * @var string
     *
     * @ORM\Column(name="Exclude_Outlook_Folders", type="string", length=255, nullable=true)
     */
    protected $Exclude_Outlook_Folders;

    /**
     * @var string
     *
     * @ORM\Column(name="Exclude_Outlook_Inbox_Level", type="string", length=255, nullable=true)
     */
    protected $Exclude_Outlook_Inbox_Level;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Last_Modified_Date", type="datetime", nullable=true)
     */
    protected $Last_Modified_Date;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Notify_User", type="boolean")
     */
    protected $Notify_User = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Title", type="string", length=255, nullable=true)
     */
    protected $Title;

    /**
     * @var string
     * 
     * @ORM\Column(name="Department", type="string", length=255, nullable=true)
     */
    protected $Department;

    /**
     * @var string
     * 
     * @ORM\Column(name="Office_No", type="string", length=15, nullable=true)
     */
    protected $Office_No;

    /**
     * @var string
     * 
     * @ORM\Column(name="Mobile_No", type="string", length=15, nullable=true)
     */
    protected $Mobile_No;

    /**
     * @var string
     * 
     * @ORM\Column(name="Expertise", type="text", nullable=true)
     */
    protected $Expertise;

    /**
     * @var string
     * 
     * @ORM\Column(name="Theme", type="string", length=75, options={"default" : "redmond"})
     */
    protected $Theme = 'redmond';

    /**
     * @var string
     * 
     * @ORM\Column(name="Workspace_Theme", type="string", length=1, options={"default" : "S"})
     */
    protected $workspaceTheme = 'S';

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Can_Create_App", type="boolean", options={"default" : false})
     */
    protected $Can_Create_App = false;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="Number_Format", type="string", length=7, nullable=true)
     */
    protected $Number_Format;

    /**
     * @var string
     * 
     * @ORM\Column(name="Default_Show_Option", type="string", length=1, nullable=true)
     */
    protected $Default_Show_Option;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="RedirectToMobile", type="boolean", options={"default" : false})
     */
    protected $Redirect_To_Mobile = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Hide_Logout", type="boolean")
     */
    protected $Hide_Logout = false;

    /**
     * @ORM\ManyToOne(targetEntity="ViewColumns")
     * @ORM\JoinColumn(name="Fltr_Field1", referencedColumnName="id", nullable=true)
     */
    protected $Fltr_Field1;

    /**
     * @var string
     * 
     * @ORM\Column(name="Fltr_Field_Val1", type="string", length=255, nullable=true)
     */
    protected $Fltr_Field_Val1;

    /**
     * @ORM\ManyToOne(targetEntity="ViewColumns")
     * @ORM\JoinColumn(name="Fltr_Field2", referencedColumnName="id", nullable=true)
     */
    protected $Fltr_Field2;

    /**
     * @var string
     *
     * @ORM\Column(name="Fltr_Field_Val2", type="string", length=255, nullable=true)
     */
    protected $Fltr_Field_Val2;
    
    /**
     * @ORM\ManyToOne(targetEntity="ViewColumns")
     * @ORM\JoinColumn(name="Fltr_Field3", referencedColumnName="id", nullable=true)
     */
    protected $Fltr_Field3;

    /**
     * @var string
     *
     * @ORM\Column(name="Fltr_Field_Val3", type="string", length=255, nullable=true)
     */
    protected $Fltr_Field_Val3;

    /**
     * @ORM\ManyToOne(targetEntity="ViewColumns")
     * @ORM\JoinColumn(name="Fltr_Field4", referencedColumnName="id", nullable=true)
     */
    protected $Fltr_Field4;

    /**
     * @var string
     *
     * @ORM\Column(name="Fltr_Field_Val4", type="string", length=255, nullable=true)
     */
    protected $Fltr_Field_Val4;

    /**
     * @var string
     * 
     * @ORM\Column(name="Language", type="string", length=3, options={"default":"en"})
     */
    protected $Language = 'en';

    /**
     * @var string
     *
     * @ORM\Column(name="TimeZone", type="string", length=50, nullable=true, options={"default":""})
     */
    protected $TimeZone = '';
    
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Manager", referencedColumnName="id", nullable=true)
     */
    protected $Manager;

    /**
     * @ORM\OneToOne(targetEntity="UserAccounts", inversedBy="User_Profile")
     * @ORM\JoinColumn(name="User_Id", referencedColumnName="id", nullable=false)
     */
    protected $User;


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
     * Set First_Name
     *
     * @param string $firstName
     * @return UserProfile
     */
    public function setFirstName($firstName)
    {
        $this->First_Name = $firstName;
    
        return $this;
    }

    /**
     * Get First_Name
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->First_Name;
    }

    /**
     * Set Last_Name
     *
     * @param string $lastName
     * @return UserProfile
     */
    public function setLastName($lastName)
    {
        $this->Last_Name = $lastName;
    
        return $this;
    }

    /**
     * Get Last_Name
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->Last_Name;
    }

    /**
     * Set Load_Library_Folders
     *
     * @param boolean $loadFoldersStartup
     * @return UserProfile
     */
    public function setLoadLibraryFolders($Load_Library_Folders)
    {
        $this->Load_Library_Folders = $Load_Library_Folders;
    
        return $this;
    }

    /**
     * Get Load_Library_Folders
     *
     * @return boolean 
     */
    public function getLoadLibraryFolders()
    {
        return $this->Load_Library_Folders;
    }

    /**
     * Set User_Mail_System
     *
     * @param string $mailSystem
     * @return UserProfile
     */
    public function setUserMailSystem($mailSystem)
    {
        $this->User_Mail_System = $mailSystem;
    
        return $this;
    }

    /**
     * Get User_Mail_System
     *
     * @return string 
     */
    public function getUserMailSystem()
    {
        return $this->User_Mail_System;
    }

    /**
     * Set Mail_Server_URL
     *
     * @param string $mailServerUrl
     * @return UserProfile
     */
    public function setMailServerURL($mailServerUrl)
    {
    	$this->Mail_Server_URL = $mailServerUrl;
    
    	return $this;
    }
    
    /**
     * Get Mail_Server_URL
     *
     * @return string
     */
    public function getMailServerURL()
    {
    	return $this->Mail_Server_URL;
    }

    /**
     * Set Outlook_Msgs_To_Show
     *
     * @param integer $folderMessages
     * @return UserProfile
     */
    public function setOutlookMsgsToShow($folderMessages)
    {
        $this->Outlook_Msgs_To_Show = $folderMessages;
    
        return $this;
    }

    /**
     * Get Outlook_Msgs_To_Show
     *
     * @return integer 
     */
    public function getOutlookMsgsToShow()
    {
        return $this->Outlook_Msgs_To_Show;
    }

    /**
     * Set Exclude_Outlook_Folders
     *
     * @param string $rootFolders
     * @return UserProfile
     */
    public function setExcludeOutlookFolders($rootFolders)
    {
        $this->Exclude_Outlook_Folders = $rootFolders;
    
        return $this;
    }

    /**
     * Get Exclude_Outlook_Folders
     *
     * @return string 
     */
    public function getExcludeOutlookFolders()
    {
        return $this->Exclude_Outlook_Folders;
    }

    /**
     * Set Exclude_Outlook_Inbox_Level
     *
     * @param string $inboxFoldersLevel
     * @return UserProfile
     */
    public function setExcludeOutlookInboxLevel($inboxFoldersLevel)
    {
        $this->Exclude_Outlook_Inbox_Level = $inboxFoldersLevel;
    
        return $this;
    }

    /**
     * Get Exclude_Outlook_Inbox_Level
     *
     * @return string 
     */
    public function getExcludeOutlookInboxLevel()
    {
        return $this->Exclude_Outlook_Inbox_Level;
    }

    /**
     * Set Last_Modified_Date
     *
     * @param \DateTime $modified_Date
     * @return UserAccounts
     */
    public function setLastModifiedDate($modified_Date)
    {
    	$this->Last_Modified_Date = $modified_Date;
    	 
    	return $this;
    }
    
    /**
     * Get Last_Modified_Date
     *
     * @return \DateTime
     */
    public function getLastModifiedDate()
    {
    	return $this->Last_Modified_Date;
    }

    /**
     * Set User
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function setUser(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->User = $user;
    }

    /**
     * Get User
     * 
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getUser()
    {
    	return $this->User;
    }

    /**
     * Set Account_Type
     *
     * @param boolean $account_Type
     * @return UserAccounts
     */
    public function setAccountType($account_Type)
    {
    	$this->Account_Type = $account_Type;
    	 
    	return $this;
    }
    
    /**
     * Get Account_Type
     *
     * @return boolean
     */
    public function getAccountType()
    {
    	return $this->Account_Type;
    }
    
    /**
     * Set Display_Name
     *
     * @param string $displayName
     * @return UserAccounts
     */
    public function setDisplayName($displayName)
    {
    	$this->Display_Name = $displayName;
    
    	return $this;
    }
    
    /**
     * Get Display_Name
     *
     * @return string
     */
    public function getDisplayName()
    {
    	return $this->Display_Name;
    }

	/**
	 * Get Recent_Edit_Count
	 * 
	 * @return integer
	 */
	public function getRecentEditCount() 
	{
		return $this->Recent_Edit_Count;
	}
	
	/**
	 * Set Recent_Edit_Count
	 * 
	 * @param integer $Recent_Edit_Count
	 * @return UserProfile
	 */
	public function setRecentEditCount($Recent_Edit_Count) 
	{
		$this->Recent_Edit_Count = $Recent_Edit_Count;
		return $this;
	}

	/**
	 * Get Recent_Used_App_Count
	 *
	 * @return integer
	 */
	public function getRecentUsedAppCount()
	{
		return $this->Recent_Used_App_Count;
	}
	
	/**
	 * Set Recent_Used_App_Count
	 *
	 * @param integer $Recent_Used_App_Count
	 * @return UserProfile
	 */
	public function setRecentUsedAppCount($Recent_Used_App_Count)
	{
		$this->Recent_Used_App_Count = $Recent_Used_App_Count;
		return $this;
	}

	/**
	 * Get Enable_Debug_Mode
	 * 
	 * @return boolean
	 */
	public function getEnableDebugMode() 
	{
		return $this->Enable_Debug_Mode;
	}
	
	/**
	 * Set Enable_Debug_Mode
	 * 
	 * @param boolean $Enable_Debug_Mode
	 * @return UserProfile
	 */
	public function setEnableDebugMode($Enable_Debug_Mode) 
	{
		$this->Enable_Debug_Mode = $Enable_Debug_Mode;
		return $this;
	}
	
	/**
	 * Get Notify_User
	 * 
	 * @return boolean
	 */
	public function getNotifyUser() 
	{
		return $this->Notify_User;
	}
	
	/**
	 * Set Notify_User
	 * 
	 * @param boolean $Notify_User
	 * @return UserProfile
	 */
	public function setNotifyUser($Notify_User) 
	{
		$this->Notify_User = $Notify_User;
		return $this;
	}
	
	/**
	 * Get Title
	 * 
	 * @return string
	 */
	public function getTitle() 
	{
		return $this->Title;
	}
	
	/**
	 * Set Title
	 * 
	 * @param string $Title
	 * @return UserProfile
	 */
	public function setTitle($Title) 
	{
		$this->Title = $Title;
		return $this;
	}
	
	/**
	 * Get Department
	 * 
	 * @return string
	 */
	public function getDepartment() 
	{
		return $this->Department;
	}
	
	/**
	 * Set Department
	 * 
	 * @param string $Department
	 * @return UserProfile
	 */
	public function setDepartment($Department) 
	{
		$this->Department = $Department;
		return $this;
	}
	
	/**
	 * Get Office_No
	 * 
	 * @return string
	 */
	public function getOfficeNo() 
	{
		return $this->Office_No;
	}
	
	/**
	 * Set Office_No
	 * 
	 * @param string $Office_No
	 * @return UserProfile
	 */
	public function setOfficeNo($Office_No) 
	{
		$this->Office_No = $Office_No;
		return $this;
	}
	
	/**
	 * Get Mobile_No
	 * 
	 * @return string
	 */
	public function getMobileNo() 
	{
		return $this->Mobile_No;
	}
	
	/**
	 * Set Mobile_No
	 * 
	 * @param string $Mobile_No
	 * @return UserProfile
	 */
	public function setMobileNo($Mobile_No) 
	{
		$this->Mobile_No = $Mobile_No;
		return $this;
	}
	
	/**
	 * Get Expertise
	 * 
	 * @return string
	 */
	public function getExpertise() 
	{
		return $this->Expertise;
	}
	
	/**
	 * Set Expertise
	 * 
	 * @param string $Expertise
	 * @return UserProfile
	 */
	public function setExpertise($Expertise) 
	{
		$this->Expertise = $Expertise;
		return $this;
	}

	/**
	 * Get Theme
	 *
	 * @return string
	 */
	public function getTheme()
	{
		return $this->Theme;
	}
	
	/**
	 * Set Theme
	 *
	 * @param string $Theme
	 * @return UserProfile
	 */
	public function setTheme($Theme)
	{
		$this->Theme = $Theme;
		return $this;
	}
	
	/**
	 * Get workspaceTheme
	 * 
	 * @return string
	 */
	public function getWorkspaceTheme()
	{
		return $this->workspaceTheme;
	}

	/**
	 * Set workspaceTheme
	 * 
	 * @param string $workspaceTheme
	 * @return UserProfile
	 */
	public function setWorkspaceTheme($workspaceTheme)
	{
		$this->workspaceTheme = $workspaceTheme;
		
		return $this;
	}

	/**
	 * Set Can_Create_App
	 * 
	 * @param boolean $can_create_app
	 * @return \Docova\DocovaBundle\Entity\UserProfile
	 */
	public function setCanCreateApp($can_create_app)
	{
		$this->Can_Create_App = $can_create_app;
		
		return $this;
	}

	/**
	 * Get Can_Create_App
	 * 
	 * @return boolean
	 */
	public function getCanCreateApp()
	{
		return $this->Can_Create_App;
	}
	
	/**
	 * Get Hide_Logout
	 * 
	 * @return boolean
	 */
	public function getHideLogout() 
	{
		return $this->Hide_Logout;
	}
	
	/**
	 * Set Hide_Logout
	 * 
	 * @param boolean $Hide_Logout
	 * @return UserProfile
	 */
	public function setHideLogout($Hide_Logout) 
	{
		$this->Hide_Logout = $Hide_Logout;
		return $this;
	}

	/**
	 * Set Fltr_Field1
	 * 
	 * @param \Docova\DocovaBundle\Entity\ViewColumns $field
	 */
	public function setFltrField1(\Docova\DocovaBundle\Entity\ViewColumns $field = null)
	{
		$this->Fltr_Field1 = $field;
	}

	/**
	 * Get Fltr_Field1
	 * 
	 * @return \Docova\DocovaBundle\Entity\ViewColumns
	 */
	public function getFltrField1()
	{
		return $this->Fltr_Field1;
	}

	/**
	 * Set Fltr_Field_Val1
	 * 
	 * @param string $Fltr_Field_Val1
	 * @return UserProfile
	 */
	public function setFltrFieldVal1($Fltr_Field_Val1 = null)
	{
		$this->Fltr_Field_Val1 = $Fltr_Field_Val1;
		
		return $this;
	}

	/**
	 * Get Fltr_Field_Val1
	 * 
	 * @return string
	 */
	public function getFltrFieldVal1()
	{
		return $this->Fltr_Field_Val1;
	}

	/**
	 * Set Fltr_Field2
	 *
	 * @param \Docova\DocovaBundle\Entity\ViewColumns $field
	 */
	public function setFltrField2(\Docova\DocovaBundle\Entity\ViewColumns $field = null)
	{
		$this->Fltr_Field2 = $field;
	}
	
	/**
	 * Get Fltr_Field2
	 *
	 * @return \Docova\DocovaBundle\Entity\ViewColumns
	 */
	public function getFltrField2()
	{
		return $this->Fltr_Field2;
	}

	/**
	 * Set Fltr_Field_Val2
	 *
	 * @param string $Fltr_Field_Val2
	 * @return UserProfile
	 */
	public function setFltrFieldVal2($Fltr_Field_Val2 = null)
	{
		$this->Fltr_Field_Val2 = $Fltr_Field_Val2;
	
		return $this;
	}
	
	/**
	 * Get Fltr_Field_Val2
	 *
	 * @return string
	 */
	public function getFltrFieldVal2()
	{
		return $this->Fltr_Field_Val2;
	}

	/**
	 * Set Fltr_Field3
	 *
	 * @param \Docova\DocovaBundle\Entity\ViewColumns $field
	 */
	public function setFltrField3(\Docova\DocovaBundle\Entity\ViewColumns $field = null)
	{
		$this->Fltr_Field3 = $field;
	}
	
	/**
	 * Get Fltr_Field3
	 *
	 * @return \Docova\DocovaBundle\Entity\ViewColumns
	 */
	public function getFltrField3()
	{
		return $this->Fltr_Field3;
	}

	/**
	 * Set Fltr_Field_Val3
	 *
	 * @param string $Fltr_Field_Val3
	 * @return UserProfile
	 */
	public function setFltrFieldVal3($Fltr_Field_Val3 = null)
	{
		$this->Fltr_Field_Val3 = $Fltr_Field_Val3;
	
		return $this;
	}
	
	/**
	 * Get Fltr_Field_Val3
	 *
	 * @return string
	 */
	public function getFltrFieldVal3()
	{
		return $this->Fltr_Field_Val3;
	}

	/**
	 * Set Fltr_Field4
	 *
	 * @param \Docova\DocovaBundle\Entity\ViewColumns $field
	 */
	public function setFltrField4(\Docova\DocovaBundle\Entity\ViewColumns $field = null)
	{
		$this->Fltr_Field4 = $field;
	}
	
	/**
	 * Get Fltr_Field4
	 *
	 * @return \Docova\DocovaBundle\Entity\ViewColumns
	 */
	public function getFltrField4()
	{
		return $this->Fltr_Field4;
	}

	/**
	 * Set Fltr_Field_Val4
	 *
	 * @param string $Fltr_Field_Val4
	 * @return UserProfile
	 */
	public function setFltrFieldVal4($Fltr_Field_Val4 = null)
	{
		$this->Fltr_Field_Val4 = $Fltr_Field_Val4;
	
		return $this;
	}
	
	/**
	 * Get Fltr_Field_Val4
	 *
	 * @return string
	 */
	public function getFltrFieldVal4()
	{
		return $this->Fltr_Field_Val4;
	}

	/**
	 * Get Language
	 * 
	 * @return string
	 */
	public function getLanguage() 
	{
		return $this->Language;
	}
	
	/**
	 * Set Language
	 * 
	 * @param string $Language
	 * @return UserProfile
	 */
	public function setLanguage($Language) 
	{
		$this->Language = $Language;
		return $this;
	}
	
	/**
	 * Get TimeZone
	 *
	 * @return string
	 */
	public function getTimeZone()
	{
	    return $this->TimeZone;
	}
	
	/**
	 * Set TimeZone
	 *
	 * @param string $TimeZone
	 * @return UserProfile
	 */
	public function setTimeZone($TimeZone)
	{
	    $this->TimeZone = $TimeZone;
	    return $this;
	}
	
	/**
	 * Get Manager
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserAccounts
	 */
	public function getManager() 
	{
		return $this->Manager;
	}
	
	/**
	 * Set Manager
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $Manager        	
	 */
	public function setManager(\Docova\DocovaBundle\Entity\UserAccounts $Manager = null) 
	{
		$this->Manager = $Manager;
	}

    /**
     * Set Number_Format
     *
     * @param string $numberFormat
     * @return UserProfile
     */
    public function setNumberFormat($numberFormat = null)
    {
        $this->Number_Format = $numberFormat;

        return $this;
    }

    /**
     * Get Number_Format
     *
     * @return string 
     */
    public function getNumberFormat()
    {
        return $this->Number_Format;
    }

    /**
     * Set Default_Show_Option
     *
     * @param string $defaultShowOption
     * @return UserProfile
     */
    public function setDefaultShowOption($defaultShowOption)
    {
        $this->Default_Show_Option = $defaultShowOption;

        return $this;
    }

    /**
     * Get Default_Show_Option
     *
     * @return string 
     */
    public function getDefaultShowOption()
    {
        return $this->Default_Show_Option;
    }

    /**
     * Set Redirect_To_Mobile
     *
     * @param boolean $redirectToMobile
     * @return UserProfile
     */
    public function setRedirectToMobile($redirectToMobile)
    {
        $this->Redirect_To_Mobile = $redirectToMobile;

        return $this;
    }

    /**
     * Get Redirect_To_Mobile
     *
     * @return boolean 
     */
    public function getRedirectToMobile()
    {
        return $this->Redirect_To_Mobile;
    }
}
