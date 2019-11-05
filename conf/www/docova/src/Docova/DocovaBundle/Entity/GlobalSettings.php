<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;

/**
 * GlobalSettings
 *
 * @ORM\Table(name="tb_global_settings")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\GlobalSettingsRepository")
 */
class GlobalSettings
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
     * @ORM\Column(name="System_Key", type="string", length=32, nullable=true)
     */
    protected $systemKey;

    /**
     * @var string
     *
     * @ORM\Column(name="Application_Title", type="string", length=255)
     */
    protected $applicationTitle;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Startup_Option", type="boolean")
     */
    protected $startupOption = true;

    /**
     * @var array
     *
     * @ORM\Column(name="Chrome_Options", type="string", nullable=true)
     */
    protected $chromeOptions;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="User_Display_Default", type="boolean")
     */
    protected $userDisplayDefault;

    /**
     * @var string
     * 
     * @ORM\Column(name="Default_DateFormat", type="string", length=10, options={"default" : "MM/DD/YYYY"})
     */
    protected $defaultDateFormat = 'MM/DD/YYYY';

    /**
     * @var boolean
     *
     * @ORM\Column(name="Latest_Release_Links", type="boolean")
     */
    protected $latestReleaseLinks = false;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="UserAccounts")
     * @ORM\JoinTable(name="tb_global_default_principal",
     *      joinColumns={@ORM\JoinColumn(name="Setting_Id", referencedColumnName="id", nullable=false)},
     *      inverseJoinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id", nullable=false)})
     */
    protected $defaultPrincipal;

    /**
     * @var array
     * 
     * @ORM\Column(name="Principal_Groups", type="text", nullable=true)
     */
    protected $principalGroups;

    /**
     * @var integer
     *
     * @ORM\Column(name="Error_Log_Level", type="smallint")
     */
    protected $errorLogLevel = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="Error_Log_Email", type="smallint")
     */
    protected $errorLogEmail = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="Log_Retention", type="integer")
     */
    protected $logRetention = 90;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="User_Profile_Creation", type="boolean", options={"default" : false})
     */
    protected $userProfileCreation = false;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Prompt_To_Close", type="boolean", options={"default" : true})
     */
    protected $promptToClose = true;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Default_Folder_Pane_Width", type="integer", nullable=true)
     */
    protected $defaultFolderPaneWidth;

    /**
     * @var string
     *
     * @ORM\Column(name="Redirect_To", type="string", length=255, nullable=true)
     */
    protected $redirectTo;

    /**
     * @var string
     * 
     * @ORM\Column(name="Company_Name", type="string", length=255, nullable=true)
     */
    protected $companyName;

    /**
     * @var string
     * 
     * @ORM\Column(name="License_Code", type="string", length=50, nullable=true)
     */
    protected $licenseCode;
    
    /**
     * @var integer
     * 
     * @ORM\Column(name="Number_Of_Users", type="smallint", options={"default" : "0"})
     */
    protected $numberOfUsers;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Expiry_Date", type="datetime", nullable=true)
     */
    protected $expiryDate;

    /**
     * @var string
     *
     * @ORM\Column(name="Licensed_Features", type="string", length=255, nullable=true)
     */
    protected $licensedFeatures;

    /**
     * @var string
     *
     * @ORM\Column(name="Thing_Factory_Key", type="string", length=50, nullable=true)
     */
    protected $thingFactoryKey;

    /**
     * @var string
     *
     * @ORM\Column(name="Uploader_License_Key", type="string", length=50, nullable=true)
     */
    protected $uploaderLicenseKey;

    /**
     * @var string
     *
     * @ORM\Column(name="Folder_License_Key", type="string", length=50, nullable=true)
     */
    protected $folderLicenseKey;

    /**
     * @var string
     *
     * @ORM\Column(name="Local_Host_Code", type="string", length=50, nullable=true)
     */
    protected $localHostCode;

    /**
     * @var string
     * 
     * @ORM\Column(name="Notes_Mail_Server", type="string", length=100, nullable=true)
     */
    protected $notesMailServer;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Central_Locking", type="boolean")
     */
    protected $centralLocking;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Record_Management", type="boolean")
     */
    protected $recordManagement;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Duplicate_Email_Check", type="boolean")
     */
    protected $duplicateEmailCheck;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Local_Delete", type="boolean")
     */
    protected $localDelete;

    /**
     * @var string
     * 
     * @ORM\Column(name="Local_Delete_Exclude", type="string", length=255, nullable=true)
     */
    protected $localDeleteExclude;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Enable_Elastica", type="boolean")
     */
    protected $enableElastica = true;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Enable_Domain_Search", type="boolean")
     */
    protected $enableDomainSearch = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Launch_Locally", type="string", length=255)
     */
    protected $launchLocally = 'doc,docx,docm,xls,xlsx,xlsm,ppt,pptx,pptm,csv,tif';

    /**
     * @var boolean
     * 
     * @ORM\Column(name="SSL_Enabled", type="boolean", options={"default": false})
     */
    protected $sslEnabled = false;

    
    /**
     * @var integer
     *
     * @ORM\Column(name="HTTP_Port", type="integer", nullable=true)
     */
    protected $httpPort = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Running_Server", type="string", length=100, nullable=true)
     */
    protected $runningServer;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="Deploy_Server_Path", type="text", nullable=true)
     */
    protected $deployServerPath;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Cleanup_Time", type="time")
     */
    protected $cleanupTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Archive_Time", type="time")
     */
    protected $archiveTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Notification_Time", type="time")
     */
    protected $notificationTime;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="Limit_Folder_Path_Length", type="boolean", options={"default" : false})
     */
    protected $limitFolderPathLength = false;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Folder_Path_Length", type="integer", nullable=true)
     */
    protected $folderPathLength;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="LDAP_Authentication", type="boolean")
     */
    protected $LDAP_Authentication = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="LDAP_Directory_Name", type="string", length=255, options={"default" : "LDAP/AD"})
     */
    protected $LDAP_Directory_Name = 'LDAP/AD';

    /**
     * @var string
     * 
     * @ORM\Column(name="LDAP_Directory", type="string", length=255, nullable=true)
     */
    protected $LDAP_Directory;

    /**
     * @var integer
     *
     * @ORM\Column(name="LDAP_Port", type="smallint", options={"default": 389})
     */
    protected $LDAP_Port = 389;
    
    /**
     * @var string
     *
     * @ORM\Column(name="ldap_base_dn", type="string", length=255, nullable=true)
     */
    protected $ldapBaseDn;

    /**
     * @var string
     *
     * @ORM\Column(name="docova_base_dn", type="string", length=255, nullable=true)
     */
    protected $docovaBaseDn;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="SSO_Authentication", type="boolean", options={"default" : false})
     */
    protected $Sso_Authentication = false;
    
    /**
     * @var boolean
     * 
     * @ORM\Column(name="DB_Authentication", type="boolean")
     */
    protected $DB_Authentication = true;


    public function __construct()
    {
//    	$this->runningServer = $_SERVER['SERVER_NAME'];
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
     * Set applicationTitle
     *
     * @param string $applicationTitle
     * @return GlobalSettings
     */
    public function setApplicationTitle($applicationTitle)
    {
        $this->applicationTitle = $applicationTitle;
    
        return $this;
    }

    /**
     * Get applicationTitle
     *
     * @return string 
     */
    public function getApplicationTitle()
    {
        return $this->applicationTitle;
    }

    /**
     * Set startupOption
     *
     * @param boolean $startupOption
     * @return GlobalSettings
     */
    public function setStartupOption($startupOption)
    {
        $this->startupOption = $startupOption;
    
        return $this;
    }

    /**
     * Get startupOption
     *
     * @return boolean 
     */
    public function getStartupOption()
    {
        return $this->startupOption;
    }

    /**
     * Set chromeOptions
     *
     * @param array|string $chromeOptions
     * @return GlobalSettings
     */
    public function setChromeOptions($chromeOptions)
    {
    	if (empty($chromeOptions)) 
    	{
    		$this->chromeOptions = null;
    		return $this;
    	}
    	if (is_array($chromeOptions)) {
    		$this->chromeOptions = implode(',', $chromeOptions);
    	}
    	else {
    		$this->chromeOptions = $chromeOptions;
    	}
    
        return $this;
    }

    /**
     * Get chromeOptions
     *
     * @return array 
     */
    public function getChromeOptions()
    {
    	if (!empty($this->chromeOptions)) 
    	{
    		return explode(',', $this->chromeOptions);
    	}
    	
        return array();
    }

    /**
     * Set userDisplayDefault
     *
     * @param boolean $userDisplayDefault
     * @return GlobalSettings
     */
    public function setUserDisplayDefault($userDisplayDefault)
    {
    	$this->userDisplayDefault = $userDisplayDefault;
    
    	return $this;
    }
    
    /**
     * Get userDisplayDefault
     *
     * @return boolean
     */
    public function getUserDisplayDefault()
    {
    	return $this->userDisplayDefault;
    }

    /**
     * Add defaultPrincipal
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $defaultPrincipal
     */
    public function addDefaultPrincipal(\Docova\DocovaBundle\Entity\UserAccounts $defaultPrincipal)
    {
    	$this->defaultPrincipal[] = $defaultPrincipal;
    }

    /**
     * Get defaultPrincipal
     * 
     * @return ArrayCollection
     */
    public function getDefaultPrincipal()
    {
    	return $this->defaultPrincipal;
    }

    /**
     * Remove defaultPrincipal
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $defaultPrincipal
     */
    public function removeDefaultPrincipal(\Docova\DocovaBundle\Entity\UserAccounts $defaultPrincipal)
    {
    	$this->defaultPrincipal->removeElement($defaultPrincipal);
    }

    /**
     * Set principalGroups
     *
     * @param array|string $principalGroups
     * @return GlobalSettings
     */
    public function setPrincipalGroups($principalGroups)
    {
    	if (!empty($principalGroups))
    	{
    		if (is_array($principalGroups))
    		{
    			$this->principalGroups = implode(',', $principalGroups);
    		}
    		else {
    			$this->principalGroups = $principalGroups;
    		}
    	}
    	else {
    		$this->principalGroups = null;
    	}
    
    	return $this;
    }
    
    /**
     * Get principalGroups
     *
     * @return array
     */
    public function getPrincipalGroups()
    {
    	if (!empty($this->principalGroups))
    	{
    		return explode(',', $this->principalGroups);
    	}
    	 
    	return array();
    }

    /**
     * Set errorLogLevel
     *
     * @param integer $errorLogLevel
     * @return GlobalSettings
     */
    public function setErrorLogLevel($errorLogLevel)
    {
        $this->errorLogLevel = $errorLogLevel;
    
        return $this;
    }

    /**
     * Get errorLogLevel
     *
     * @return integer 
     */
    public function getErrorLogLevel()
    {
        return $this->errorLogLevel;
    }

    /**
     * Set errorLogEmail
     *
     * @param integer $errorLogEmail
     * @return GlobalSettings
     */
    public function setErrorLogEmail($errorLogEmail)
    {
        $this->errorLogEmail = $errorLogEmail;
    
        return $this;
    }

    /**
     * Get errorLogEmail
     *
     * @return integer 
     */
    public function getErrorLogEmail()
    {
        return $this->errorLogEmail;
    }

    /**
     * Set logRetention
     *
     * @param integer $logRetention
     * @return GlobalSettings
     */
    public function setLogRetention($logRetention)
    {
        $this->logRetention = $logRetention;
    
        return $this;
    }

    /**
     * Get logRetention
     *
     * @return integer 
     */
    public function getLogRetention()
    {
        return $this->logRetention;
    }
    
    /**
     * Set userProfileCreation
     * 
     * @param boolean $userProfileCreation
     * @return GlobalSettings
     */
    public function setUserProfileCreation($userProfileCreation)
    {
    	$this->userProfileCreation = $userProfileCreation;
    	
    	return $this;
    }
    
    /**
     * Get userProfileCreation
     * 
     * @return boolean
     */
    public function getUserProfileCreation()
    {
    	return $this->userProfileCreation;
    }

    /**
     * Set redirectTo
     *
     * @param null|string $redirectTo
     * @return GlobalSettings
     */
    public function setRedirectTo($redirectTo = null)
    {
        $this->redirectTo = $redirectTo;
    
        return $this;
    }

    /**
     * Get redirectTo
     *
     * @return string 
     */
    public function getRedirectTo()
    {
        return $this->redirectTo;
    }

    /**
     * Set thingFactoryKey
     *
     * @param null|string $thingFactoryKey
     * @return GlobalSettings
     */
    public function setThingFactoryKey($thingFactoryKey = null)
    {
        $this->thingFactoryKey = $thingFactoryKey;
    
        return $this;
    }

    /**
     * Get thingFactoryKey
     *
     * @return string 
     */
    public function getThingFactoryKey()
    {
        return $this->thingFactoryKey;
    }

    /**
     * Set uploaderLicenseKey
     *
     * @param null|string $uploaderLicenseKey
     * @return GlobalSettings
     */
    public function setUploaderLicenseKey($uploaderLicenseKey = null)
    {
        $this->uploaderLicenseKey = $uploaderLicenseKey;
    
        return $this;
    }

    /**
     * Get uploaderLicenseKey
     *
     * @return string 
     */
    public function getUploaderLicenseKey()
    {
        return $this->uploaderLicenseKey;
    }

    /**
     * Set folderLicenseKey
     *
     * @param null|string $folderLicenseKey
     * @return GlobalSettings
     */
    public function setFolderLicenseKey($folderLicenseKey = null)
    {
        $this->folderLicenseKey = $folderLicenseKey;
    
        return $this;
    }

    /**
     * Get folderLicenseKey
     *
     * @return string 
     */
    public function getFolderLicenseKey()
    {
        return $this->folderLicenseKey;
    }

    /**
     * Set localHostCode
     *
     * @param null|string $localHostCode
     * @return GlobalSettings
     */
    public function setLocalHostCode($localHostCode = null)
    {
        $this->localHostCode = $localHostCode;
    
        return $this;
    }

    /**
     * Get localHostCode
     *
     * @return string 
     */
    public function getLocalHostCode()
    {
        return $this->localHostCode;
    }

    /**
     * Set notesMailServer
     * 
     * @param string $notesMailServer
     * @return GlobalSettings
     */
    public function setNotesMailServer($notesMailServer = null)
    {
    	$this->notesMailServer = $notesMailServer;
    	
    	return $this;
    }

    /**
     * Get notesMailServer
     * 
     * @return string
     */
    public function getNotesMailServer()
    {
    	return $this->notesMailServer;
    }

    /**
     * Set centralLocking
     *
     * @param boolean $centralLocking
     * @return GlobalSettings
     */
    public function setCentralLocking($centralLocking)
    {
    	$this->centralLocking = $centralLocking;
    	 
    	return $this;
    }
    
    /**
     * Get centralLocking
     *
     * @return boolean
     */
    public function getCentralLocking()
    {
    	return $this->centralLocking;
    }

    /**
     * Set recordManagement
     * 
     * @param boolean $recordManagement
     * @return GlobalSettings
     */
    public function setRecordManagement($recordManagement)
    {
    	$this->recordManagement = $recordManagement;
    	
    	return $this;
    }

    /**
     * Get recordManagement
     * 
     * @return boolean
     */
    public function getRecordManagement()
    {
    	return $this->recordManagement;
    }

    /**
     * Set duplicateEmailCheck
     *
     * @param boolean $duplicateEmailCheck
     * @return GlobalSettings
     */
    public function setDuplicateEmailCheck($duplicateEmailCheck)
    {
    	$this->duplicateEmailCheck = $duplicateEmailCheck;
    	 
    	return $this;
    }
    
    /**
     * Get duplicateEmailCheck
     *
     * @return boolean
     */
    public function getDuplicateEmailCheck()
    {
    	return $this->duplicateEmailCheck;
    }

    /**
     * Set localDelete
     *
     * @param boolean $localDelete
     * @return GlobalSettings
     */
    public function setLocalDelete($localDelete)
    {
    	$this->localDelete = $localDelete;
    
    	return $this;
    }

    /**
     * Get localDelete
     *
     * @return boolean
     */
    public function getLocalDelete()
    {
    	return $this->localDelete;
    }

    /**
     * Set localDeleteExclude
     *
     * @param string $localDeleteExclude
     * @return GlobalSettings
     */
    public function setLocalDeleteExclude($localDeleteExclude = null)
    {
    	$this->localDeleteExclude = $localDeleteExclude;
    
    	return $this;
    }
    
    /**
     * Get localDelete
     *
     * @return string
     */
    public function getLocalDeleteExclude()
    {
    	return $this->localDeleteExclude;
    }

    /**
     * Set enableElastica
     *
     * @param boolean $enableElastica
     * @return GlobalSettings
     */
    public function setEnableElastica($enableElastica)
    {
    	$this->enableElastica = $enableElastica;
    	 
    	return $this;
    }
    
    /**
     * Get enableElastica
     *
     * @return boolean
     */
    public function getEnableElastica()
    {
    	return $this->enableElastica;
    }

    /**
     * Set enableDomainSearch
     * 
     * @param boolean $enableDomainSearch
     * @return GlobalSettings
     */
    public function setEnableDomainSearch($enableDomainSearch)
    {
    	$this->enableDomainSearch = $enableDomainSearch;
    	
    	return $this;
    }
    
    /**
     * Get enableDomainSearch
     * 
     * @return boolean
     */
    public function getEnableDomainSearch()
    {
    	return $this->enableDomainSearch;
    }

    /**
     * Set launchLocally
     *
     * @param string $launchLocally
     * @return GlobalSettings
     */
    public function setLaunchLocally($launchLocally)
    {
        $this->launchLocally = $launchLocally;
    
        return $this;
    }

    /**
     * Get launchLocally
     *
     * @return string 
     */
    public function getLaunchLocally()
    {
        return $this->launchLocally;
    }

    /**
     * Set sslEnabled
     * 
     * @param boolean $sslEnabled
     * @return GlobalSettings
     */
    public function setSslEnabled($sslEnabled)
    {
    	$this->sslEnabled = $sslEnabled;
    	
    	return $this;
    }

    /**
     * Get sslEnabled
     * 
     * @return boolean
     */
    public function getSslEnabled()
    {
    	return $this->sslEnabled;
    }

    /**
     * Set httpPort
     *
     * @param integer $httpPort
     * @return GlobalSettings
     */
    public function setHttpPort($portnumber)
    {
        $this->httpPort = $portnumber;
        
        return $this;
    }
    
    /**
     * Get httpPort
     *
     * @return integer
     */
    public function getHttpPort()
    {
        return $this->httpPort;
    }
    
    
    /**
     * Set runningServer
     *
     * @param string $runningServer
     * @return GlobalSettings
     */
    public function setRunningServer($runningServer)
    {
        $this->runningServer = $runningServer;
    
        return $this;
    }

    /**
     * Get runningServer
     *
     * @return string 
     */
    public function getRunningServer()
    {
        return $this->runningServer;
    }

    /**
     * Set deployServerPath
     * 
     * @param string|array $path
     * @return GlobalSettings
     */
    public function setDeployServerPath($path = null)
    {
    	if (is_array($path)) {
    		$path = implode(';', $path);
    	}
    	$this->deployServerPath = $path;
    	
    	return $this;
    }
    
    /**
     * Get deployServerPath
     * 
     * @param string $type
     * @return string|array
     */
    public function getDeployServerPath($type = '')
    {
    	if ($type == 'string' || $type == 's') {
    		return $this->deployServerPath;
    	}
    	
    	if (!empty($this->deployServerPath))
    	{
	    	$servers = explode(';', $this->deployServerPath);
	    	return $servers;
    	}
    	else {
    		return null;
    	}
    }

    /**
     * Set cleanupTime
     *
     * @param \DateTime $cleanupTime
     * @return GlobalSettings
     */
    public function setCleanupTime($cleanupTime)
    {
        $this->cleanupTime = $cleanupTime;
    
        return $this;
    }

    /**
     * Get cleanupTime
     *
     * @return \DateTime 
     */
    public function getCleanupTime()
    {
        return $this->cleanupTime;
    }

    /**
     * Set archiveTime
     *
     * @param \DateTime $archiveTime
     * @return GlobalSettings
     */
    public function setArchiveTime($archiveTime)
    {
        $this->archiveTime = $archiveTime;
    
        return $this;
    }

    /**
     * Get archiveTime
     *
     * @return \DateTime 
     */
    public function getArchiveTime()
    {
        return $this->archiveTime;
    }

    /**
     * Set notificationTime
     *
     * @param \DateTime $notificationTime
     * @return GlobalSettings
     */
    public function setNotificationTime($notificationTime)
    {
        $this->notificationTime = $notificationTime;
    
        return $this;
    }

    /**
     * Get notificationTime
     *
     * @return \DateTime 
     */
    public function getNotificationTime()
    {
        return $this->notificationTime;
    }
    
    /**
     * Set LDAP_Authentication
     * 
     * @param boolean $LDAP_Authentication
     * @return GlobalSettings
     */
    public function setLDAPAuthentication($LDAP_Authentication)
    {
    	$this->LDAP_Authentication = $LDAP_Authentication;
    	
    	return $this;
    }

    /**
     * Get LDAP_Authentication
     * 
     * @return boolean
     */
    public function getLDAPAuthentication()
    {
    	return $this->LDAP_Authentication;
    }

    /**
     * Set LDAP_Directory_Name
     *
     * @param string $ldapDirectoryName
     * @return GlobalSettings
     */
    public function setLdapDirectoryName($ldapDirectoryName)
    {
    	$this->LDAP_Directory_Name = $ldapDirectoryName;
    	 
    	return $this;
    }
    
    /**
     * Get LDAP_Directory_Name
     *
     * @return string
     */
    public function getLdapDirectoryName()
    {
    	return $this->LDAP_Directory_Name;
    }

    /**
     * Set LDAP_Directory
     * 
     * @param string $LDAP_Directory
     * @return GlobalSettings
     */
    public function setLDAPDirectory($LDAP_Directory)
    {
    	$this->LDAP_Directory = $LDAP_Directory;
    	
    	return $this;
    }

    /**
     * Get LDAP_Directory
     * 
     * @return string
     */
    public function getLDAPDirectory()
    {
    	return $this->LDAP_Directory;
    }

    /**
     * Set LDAP_Port
     * 
     * @param integer $port
     * @return GlobalSettings
     */
    public function setLDAPPort($port)
    {
    	$this->LDAP_Port = $port;
    	
    	return $this;
    }

    /**
     * Get LDAP_Port
     * 
     *  @return integer
     */
    public function getLDAPPort()
    {
    	return $this->LDAP_Port;
    }

    /**
     * Set DB_Authentication
     * 
     * @param boolean $DB_Authentication
     * @return GlobalSettings
     */
    public function setDBAuthentication($DB_Authentication)
    {
    	$this->DB_Authentication = $DB_Authentication;
    	
    	return $this;
    }

    /**
     * Get DB_Authentication
     * 
     * @return boolean
     */
    public function getDBAuthentication()
    {
    	return $this->DB_Authentication;
    }

    /**
     * Set Sso_Authentication
     * 
     * @param boolean $sso
     * @return GlobalSettings
     */
    public function setSsoAuthentication($sso)
    {
    	$this->Sso_Authentication = $sso;
    	
    	return $this;
    }

    /**
     * Get Sso_Authentication
     * 
     * @return boolean
     */
    public function getSsoAuthentication()
    {
    	return $this->Sso_Authentication;
    }
    
    /**
     * Get ldapBaseDn
     * 
     * @return string
     */
    public function getLdapBaseDn() {
		return $this->ldapBaseDn;
	}
	/**
	 * Set ldapBaseDn
	 * 
	 * @param string $ldapBaseDn
	 * @return GlobalSettings
	 */
	public function setLdapBaseDn( $ldapBaseDn) {
		$this->ldapBaseDn = $ldapBaseDn;
		return $this;
	}

	/**
	 * Get docovaBaseDn
	 * 
	 * @return string
	 */
	public function getDocovaBaseDn() {
		return $this->docovaBaseDn;
	}
	/**
	 * Set docovaBaseDn
	 * 
	 * @param string $docovaBaseDn
	 * @return GlobalSettings
	 */
	public function setDocovaBaseDn( $docovaBaseDn) {
		$this->docovaBaseDn = $docovaBaseDn;
		return $this;
	}

    /**
     * Set systemKey
     *
     * @param string $systemKey
     * @return GlobalSettings
     */
    public function setSystemKey($systemKey)
    {
        $this->systemKey = $systemKey;
    
        return $this;
    }

    /**
     * Get systemKey
     *
     * @return string 
     */
    public function getSystemKey()
    {
        return $this->systemKey;
    }

    /**
     * Set defaultDateFormat
     *
     * @param string $defaultDateFormat
     * @return GlobalSettings
     */
    public function setDefaultDateFormat($defaultDateFormat)
    {
        $this->defaultDateFormat = $defaultDateFormat;
    
        return $this;
    }

    /**
     * Get defaultDateFormat
     *
     * @return string 
     */
    public function getDefaultDateFormat()
    {
        return $this->defaultDateFormat;
    }

    /**
     * Set latestReleaseLinks
     *
     * @param boolean $latestReleaseLinks
     * @return GlobalSettings
     */
    public function setLatestReleaseLinks($latestReleaseLinks)
    {
    	$this->latestReleaseLinks = $latestReleaseLinks;
    
    	return $this;
    }
    
    /**
     * Get latestReleaseLinks
     *
     * @return boolean
     */
    public function getLatestReleaseLinks()
    {
    	return $this->latestReleaseLinks;
    }

    /**
     * Set companyName
     *
     * @param string $companyName
     * @return GlobalSettings
     */
    public function setCompanyName($companyName = null)
    {
        $this->companyName = $companyName;
    
        return $this;
    }

    /**
     * Get companyName
     *
     * @return string 
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * Set licenseCode
     *
     * @param string $licenseCode
     * @return GlobalSettings
     */
    public function setLicenseCode($licenseCode = null)
    {
        $this->licenseCode = $licenseCode;
    
        return $this;
    }

    /**
     * Get licenseCode
     *
     * @return string 
     */
    public function getLicenseCode()
    {
        return $this->licenseCode;
    }

    /**
     * Set numberOfUsers
     *
     * @param integer $numberOfUsers
     * @return GlobalSettings
     */
    public function setNumberOfUsers($numberOfUsers)
    {
        $this->numberOfUsers = $numberOfUsers;
    
        return $this;
    }

    /**
     * Get numberOfUsers
     *
     * @return integer 
     */
    public function getNumberOfUsers()
    {
        return $this->numberOfUsers;
    }

    /**
     * Set expiryDate
     *
     * @param \DateTime $expiryDate
     * @return GlobalSettings
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;
    
        return $this;
    }

    /**
     * Get expiryDate
     *
     * @return \DateTime 
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Set licensedFeatures
     *
     * @param string $licensedFeatures
     * @return GlobalSettings
     */
    public function setLicensedFeatures($licensedFeatures = null)
    {
        $this->licensedFeatures = $licensedFeatures;
    
        return $this;
    }

    /**
     * Get licensedFeatures
     *
     * @return string
     */
    public function getLicensedFeatures()
    {
        return $this->licensedFeatures;
    }

    /**
     * Set promptToClose
     *
     * @param boolean $promptToClose
     * @return GlobalSettings
     */
    public function setPromptToClose($promptToClose)
    {
        $this->promptToClose = $promptToClose;

        return $this;
    }

    /**
     * Get promptToClose
     *
     * @return boolean 
     */
    public function getPromptToClose()
    {
        return $this->promptToClose;
    }

    /**
     * Set defaultFolderPaneWidth
     *
     * @param integer $defaultFolderPaneWidth
     * @return GlobalSettings
     */
    public function setDefaultFolderPaneWidth($defaultFolderPaneWidth = null)
    {
        $this->defaultFolderPaneWidth = $defaultFolderPaneWidth;

        return $this;
    }

    /**
     * Get defaultFolderPaneWidth
     *
     * @return integer 
     */
    public function getDefaultFolderPaneWidth()
    {
        return $this->defaultFolderPaneWidth;
    }

    /**
     * Set limitFolderPathLength
     *
     * @param boolean $limitFolderPathLength
     * @return GlobalSettings
     */
    public function setLimitFolderPathLength($limitFolderPathLength)
    {
        $this->limitFolderPathLength = $limitFolderPathLength;

        return $this;
    }

    /**
     * Get limitFolderPathLength
     *
     * @return boolean 
     */
    public function getLimitFolderPathLength()
    {
        return $this->limitFolderPathLength;
    }

    /**
     * Set folderPathLength
     *
     * @param integer $folderPathLength
     * @return GlobalSettings
     */
    public function setFolderPathLength($folderPathLength)
    {
        $this->folderPathLength = $folderPathLength;

        return $this;
    }

    /**
     * Get folderPathLength
     *
     * @return integer 
     */
    public function getFolderPathLength()
    {
        return $this->folderPathLength;
    }
}
