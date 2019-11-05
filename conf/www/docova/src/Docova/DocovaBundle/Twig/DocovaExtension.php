<?php

namespace Docova\DocovaBundle\Twig;

use Docova\DocovaBundle\ObjectModel\ObjectModel;
use Docova\DocovaBundle\ObjectModel\Docova;

require_once(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Command'.DIRECTORY_SEPARATOR.'LSHelperFunctions.php');

/**
 * DOCOVA API transferred $$ functions to twig scripts
 * @author javad rahimi
 *        
 */
class DocovaExtension extends \Twig_Extension 
{
	protected $docova;
	protected $buffer;
	protected $formname;
	protected $isViewScripting;
	protected $newdocunid;
	protected $isprofile;
	protected $ismobile;
	protected $dateFormat;
	
	public function __construct(ObjectModel $docovaobj, $isViewScripting = false)
	{
		$this->docova = $docovaobj;
		$this->buffer = array();
		$this->isViewScripting = $isViewScripting;
		$this->isrefresh = false;
		$this->ismobile = false;
		$format = $docovaobj->getGlobalSettings()->getDefaultDateFormat();
		$format = str_replace(array('YYYY', 'MM', 'DD'), array('Y', 'm', 'd'), $format);
		$this->dateFormat = $format;
	}

	 public function getNodeVisitors()
    {
        return array(
            new DocovaNodeVisitor(),
        );
    }

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('f_SetApplication', array($this, 'f_SetApplication')),
			new \Twig_SimpleFunction('f_SetDocument', array($this, 'f_SetDocument')),
		    new \Twig_SimpleFunction('f_SetIsMobile', array($this, 'f_SetIsMobile')),
			new \Twig_SimpleFunction('f_SetIsRefresh', array($this, 'f_SetIsRefresh')),		    
			new \Twig_SimpleFunction('f_SetUser', array($this, 'f_SetUser')),
			new \Twig_SimpleFunction('f_SetForm', array($this, 'f_SetForm')),
			new \Twig_SimpleFunction('f_SetNewDocUNID', array($this, 'f_SetNewDocUNID')),
			new \Twig_SimpleFunction('f_Abs', array($this, 'f_Abs')),
			new \Twig_SimpleFunction('f_Abstract', array($this, 'f_Abstract'), array('needs_context' => true)),				
			new \Twig_SimpleFunction('f_ACos', array($this, 'f_ACos')),
			new \Twig_SimpleFunction('f_Accessed', array($this, 'f_Accessed'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_Adjust', array($this, 'f_Adjust')),
			new \Twig_SimpleFunction('f_AddToFolder', array($this, 'f_AddToFolder')),
			new \Twig_SimpleFunction('f_All', array($this, 'f_All')),
			new \Twig_SimpleFunction('f_ArrayAppend', array($this, 'f_ArrayAppend')),
			new \Twig_SimpleFunction('f_ArrayConcat', array($this, 'f_ArrayConcat')),
			new \Twig_SimpleFunction('f_ArrayGetIndex', array($this, 'f_ArrayGetIndex')),
			new \Twig_SimpleFunction('f_ArrayRemoveItem', array($this, 'f_ArrayRemoveItem')),
			new \Twig_SimpleFunction('f_ArrayUnique', array($this, 'f_ArrayUnique')),
			new \Twig_SimpleFunction('f_Ascii', array($this, 'f_Ascii')),
			new \Twig_SimpleFunction('f_ASin', array($this, 'f_ASin')),
			new \Twig_SimpleFunction('f_ATan', array($this, 'f_ATan')),
			new \Twig_SimpleFunction('f_ATan2', array($this, 'f_ATan2')),
			new \Twig_SimpleFunction('f_Author', array($this, 'f_Author')),
			new \Twig_SimpleFunction('f_AttachmentLengths', array($this, 'f_AttachmentLengths'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_AttachmentNames', array($this, 'f_AttachmentNames'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_AttachmentModifiedTimes', array($this, 'f_AttachmentModifiedTimes'), array('needs_context' => true)),				
			new \Twig_SimpleFunction('f_Attachments', array($this, 'f_Attachments'), array('needs_context' => true)),				
			new \Twig_SimpleFunction('f_Begins', array($this, 'f_Begins')),
			new \Twig_SimpleFunction('f_Char', array($this, 'f_Char')),
			new \Twig_SimpleFunction('f_ClientType', array($this, 'f_ClientType')),
			new \Twig_SimpleFunction('f_Command', array($this, 'f_Command')),
			new \Twig_SimpleFunction('f_Compare', array($this, 'f_Compare')),
			new \Twig_SimpleFunction('f_Contains', array($this, 'f_Contains')),
			new \Twig_SimpleFunction('f_Cos', array($this, 'f_Cos')),
			new \Twig_SimpleFunction('f_Count', array($this, 'f_Count')),
			new \Twig_SimpleFunction('f_Created', array($this, 'f_Created'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_Date', array($this, 'f_Date')),
			new \Twig_SimpleFunction('f_Day', array($this, 'f_Day')),
			new \Twig_SimpleFunction('f_DbColumn', array($this, 'f_DbColumn')),
			new \Twig_SimpleFunction('f_DbTitle', array($this, 'f_DbTitle')),
			new \Twig_SimpleFunction('f_DbLookup', array($this, 'f_DbLookup')),
			new \Twig_SimpleFunction('f_DbName', array($this, 'f_DbName')),
			new \Twig_SimpleFunction('f_DbCalculate', array($this, 'f_DbCalculate')),
			new \Twig_SimpleFunction('f_DeleteField', array($this, 'f_DeleteField')),
			new \Twig_SimpleFunction('f_DocDescendants', array($this, 'f_DocDescendants')),
			new \Twig_SimpleFunction('f_DocLength', array($this, 'f_DocLength')),
			new \Twig_SimpleFunction('f_DocNumber', array($this, 'f_DocNumber')),
			new \Twig_SimpleFunction('f_DocOmittedLength', array($this, 'f_DocOmittedLength')),
			new \Twig_SimpleFunction('f_DocumentUniqueID', array($this, 'f_DocumentUniqueID'),  array('needs_context' => true)),
			new \Twig_SimpleFunction('f_Elements', array($this, 'f_Elements')),
			new \Twig_SimpleFunction('f_Ends', array($this, 'f_Ends')),
			new \Twig_SimpleFunction('f_Environment', array($this, 'f_Environment')),
			new \Twig_SimpleFunction('f_Error', array($this, 'f_Error')),
			new \Twig_SimpleFunction('f_Exp', array($this, 'f_Exp')),
			new \Twig_SimpleFunction('f_Explode', array($this, 'f_Explode')),
			new \Twig_SimpleFunction('f_Failure', array($this, 'f_Failure')),
			new \Twig_SimpleFunction('f_False', array($this, 'f_False')),
			new \Twig_SimpleFunction('f_FirstPendingFor', array($this, 'f_FirstPendingFor')),
			new \Twig_SimpleFunction('f_Format', array($this, 'f_Format')),
			new \Twig_SimpleFunction('f_FormatDate', array($this, 'f_FormatDate')),
			new \Twig_SimpleFunction('f_GetDocField', array($this, 'f_GetDocField'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_GetField', array($this, 'f_GetField'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_GetFieldString', array($this, 'f_GetFieldString'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_GetProfileField', array($this, 'f_GetProfileField')),
			new \Twig_SimpleFunction('f_Hour', array($this, 'f_Hour')),
			new \Twig_SimpleFunction('f_Implode', array($this, 'f_Implode')),
			new \Twig_SimpleFunction('f_InStr', array($this, 'f_InStr')),
			new \Twig_SimpleFunction('f_Integer', array($this, 'f_Integer')),
			new \Twig_SimpleFunction('f_IsDate', array($this, 'f_IsDate')),
			new \Twig_SimpleFunction('f_IsDocTruncated', array($this, 'f_IsDocTruncated')),
			new \Twig_SimpleFunction('f_IsEmpty', array($this, 'f_IsEmpty')),
			new \Twig_SimpleFunction('f_IsError', array($this, 'f_IsError')),
			new \Twig_SimpleFunction('f_IsMember', array($this, 'f_IsMember')),
			new \Twig_SimpleFunction('f_IsDocBeingEdited', array($this, 'f_IsDocBeingEdited')),
			new \Twig_SimpleFunction('f_IsDocBeingLoaded', array($this, 'f_IsDocBeingLoaded')),
			new \Twig_SimpleFunction('f_IsDocBeingSaved', array($this, 'f_IsDocBeingSaved')),		
		    new \Twig_SimpleFunction('f_IsMobile', array($this, 'f_IsMobile')),
			new \Twig_SimpleFunction('f_IsNewDoc', array($this, 'f_IsNewDoc')),
			new \Twig_SimpleFunction('f_IsNotMember', array($this, 'f_IsNotMember')),
			new \Twig_SimpleFunction('f_IsNull', array($this, 'f_IsNull')),
			new \Twig_SimpleFunction('f_IsNumber', array($this, 'f_IsNumber')),
			new \Twig_SimpleFunction('f_IsNumeric', array($this, 'f_IsNumeric')),
			new \Twig_SimpleFunction('f_IsResponseDoc', array($this, 'f_IsResponseDoc'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_IsText', array($this, 'f_IsText')),
			new \Twig_SimpleFunction('f_IsTime', array($this, 'f_IsTime')),
		    new \Twig_SimpleFunction('f_IsUnavailable', array($this, 'f_IsUnavailable'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_IsAvailable', array($this, 'f_IsAvailable'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_Keywords', array($this, 'f_Keywords')),
			new \Twig_SimpleFunction('f_LanguagePreference', array($this, 'f_LanguagePreference')),
			new \Twig_SimpleFunction('f_LBound', array($this, 'f_LBound')),
			new \Twig_SimpleFunction('f_Left', array($this, 'f_Left')),
			new \Twig_SimpleFunction('f_LeftBack', array($this, 'f_LeftBack')),
			new \Twig_SimpleFunction('f_Length', array($this, 'f_Length')),
			new \Twig_SimpleFunction('f_Ln', array($this, 'f_Ln')),
			new \Twig_SimpleFunction('f_LocationGetInfo', array($this, 'f_LocationGetInfo')),			
			new \Twig_SimpleFunction('f_Log', array($this, 'f_Log')),
			new \Twig_SimpleFunction('f_LowerCase', array($this, 'f_LowerCase')),
		    new \Twig_SimpleFunction('f_Max', array($this, 'f_Max')),
			new \Twig_SimpleFunction('f_MailSend', array($this, 'f_MailSend')),
			new \Twig_SimpleFunction('f_Member', array($this, 'f_Member')),
			new \Twig_SimpleFunction('f_Middle', array($this, 'f_Middle')),				
			new \Twig_SimpleFunction('f_Minute', array($this, 'f_Minute')),
			new \Twig_SimpleFunction('f_Modified', array($this, 'f_Modified'), array('needs_context' => true)),
			new \Twig_SimpleFunction('f_Month', array($this, 'f_Month')),
			new \Twig_SimpleFunction('f_Name', array($this, 'f_Name')),
			new \Twig_SimpleFunction('f_NewLine', array($this, 'f_NewLine')),
			new \Twig_SimpleFunction('f_No', array($this, 'f_No')),
			new \Twig_SimpleFunction('f_Nothing', array($this, 'f_Nothing')),
			new \Twig_SimpleFunction('f_Now', array($this, 'f_Now')),
			new \Twig_SimpleFunction('f_Pi', array($this, 'f_Pi')),
			new \Twig_SimpleFunction('f_PostedCommand', array($this, 'f_PostedCommand')),
			new \Twig_SimpleFunction('f_Power', array($this, 'f_Power')),
			new \Twig_SimpleFunction('f_Prompt', array($this, 'f_Prompt')),		    
			new \Twig_SimpleFunction('f_ProperCase', array($this, 'f_ProperCase')),
			new \Twig_SimpleFunction('f_Random', array($this, 'f_Random')),
			new \Twig_SimpleFunction('f_Repeat', array($this, 'f_Repeat')),
			new \Twig_SimpleFunction('f_Replace', array($this, 'f_Replace')),
			new \Twig_SimpleFunction('f_ReplaceSubstring', array($this, 'f_ReplaceSubstring')),
			new \Twig_SimpleFunction('f_Return', array($this, 'f_Return')),
			new \Twig_SimpleFunction('f_Right', array($this, 'f_Right')),
			new \Twig_SimpleFunction('f_RightBack', array($this, 'f_RightBack')),
			new \Twig_SimpleFunction('f_Round', array($this, 'f_Round')),
			new \Twig_SimpleFunction('f_Second', array($this, 'f_Second')),
			new \Twig_SimpleFunction('f_Select', array($this, 'f_Select')),
			new \Twig_SimpleFunction('f_ServerName', array($this, 'f_ServerName')),
			new \Twig_SimpleFunction('f_SetDocField', array($this, 'f_SetDocField')),
			new \Twig_SimpleFunction('f_SetEnvironment', array($this, 'f_SetEnvironment')),
			new \Twig_SimpleFunction('f_Sum', array($this, 'f_Sum')),		
			new \Twig_SimpleFunction('f_SetField', array($this, 'f_SetField')),
			new \Twig_SimpleFunction('f_SetProfileField', array($this, 'f_SetProfileField')),
			new \Twig_SimpleFunction('f_Sign', array($this, 'f_Sign')),
			new \Twig_SimpleFunction('f_Sin', array($this, 'f_Sin')),
			new \Twig_SimpleFunction('f_Sort', array($this, 'f_Sort')),
			new \Twig_SimpleFunction('f_Sqrt', array($this, 'f_Sqrt')),
			new \Twig_SimpleFunction('f_Subset', array($this, 'f_Subset')),
			new \Twig_SimpleFunction('f_Success', array($this, 'f_Success')),
			new \Twig_SimpleFunction('f_Tan', array($this, 'f_Tan')),
			new \Twig_SimpleFunction('f_Text', array($this, 'f_Text')),
			new \Twig_SimpleFunction('f_TextToNumber', array($this, 'f_TextToNumber')),
			new \Twig_SimpleFunction('f_TextToTime', array($this, 'f_TextToTime')),
			new \Twig_SimpleFunction('f_Time', array($this, 'f_Time')),
			new \Twig_SimpleFunction('f_Today', array($this, 'f_Today')),
			new \Twig_SimpleFunction('f_Tomorrow', array($this, 'f_Tomorrow')),
			new \Twig_SimpleFunction('f_ToNumber', array($this, 'f_ToNumber')),
			new \Twig_SimpleFunction('f_Trim', array($this, 'f_Trim')),
			new \Twig_SimpleFunction('f_True', array($this, 'f_True')),
			new \Twig_SimpleFunction('f_Trunc', array($this, 'f_Trunc')),				
			new \Twig_SimpleFunction('f_UBound', array($this, 'f_UBound')),
			new \Twig_SimpleFunction('f_Unavailable', array($this, 'f_Unavailable')),
			new \Twig_SimpleFunction('f_Unique', array($this, 'f_Unique')),
			new \Twig_SimpleFunction('f_UpperCase', array($this, 'f_UpperCase')),
			new \Twig_SimpleFunction('f_URLDecode', array($this, 'f_URLDecode')),
			new \Twig_SimpleFunction('f_URLEncode', array($this, 'f_URLEncode')),
			new \Twig_SimpleFunction('f_UserAccess', array($this, 'f_UserAccess')),		    
			new \Twig_SimpleFunction('f_UserName', array($this, 'f_UserName')),
			new \Twig_SimpleFunction('f_UserNameLanguage', array($this, 'f_UserNameLanguage')),		    
			new \Twig_SimpleFunction('f_UserNamesList', array($this, 'f_UserNamesList')),					    
			new \Twig_SimpleFunction('f_UserRoles', array($this, 'f_UserRoles')),
			new \Twig_SimpleFunction('f_V3UserName', array($this, 'f_V3UserName')),
			new \Twig_SimpleFunction('f_V4UserAccess', array($this, 'f_V4UserAccess')),		    
			new \Twig_SimpleFunction('f_Version', array($this, 'f_Version')),
			new \Twig_SimpleFunction('f_WebDbName', array($this, 'f_WebDbName')),
			new \Twig_SimpleFunction('f_Weekday', array($this, 'f_Weekday')),
			new \Twig_SimpleFunction('f_Word', array($this, 'f_Word')),
			new \Twig_SimpleFunction('f_Year', array($this, 'f_Year')),
			new \Twig_SimpleFunction('f_Yes', array($this, 'f_Yes')),
			new \Twig_SimpleFunction('f_Yesterday', array($this, 'f_Yesterday')),

			//custom function
			
			new \Twig_SimpleFunction('f_ClearBuffer', array($this, 'f_ClearBuffer')),
			new \Twig_SimpleFunction('f_GetSubformFileName', array($this, 'f_GetSubformFileName')),
			new \Twig_SimpleFunction('f_SetViewScripting', array($this, 'f_SetViewScripting')),
			new \Twig_SimpleFunction('f_AddComputedToBuffer', array($this, 'f_AddComputedToBuffer')),
			
			//library functions
			
			new \Twig_SimpleFunction('f_GetFlag', array($this, 'f_GetFlag')),
			new \Twig_SimpleFunction('f_FolderName', array($this, 'f_FolderName')),
			new \Twig_SimpleFunction('f_FolderPath', array($this, 'f_FolderPath')),
			new \Twig_SimpleFunction('f_FetchUser', array($this, 'f_FetchUser')),
			new \Twig_SimpleFunction('f_IsWorkflowStarted', array($this, 'f_IsWorkflowStarted'))
		);
	}
	
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('dateformat', array($this, 'dateFormat')),
			new \Twig_SimpleFilter('serialize', array($this, 'serializeValue')),
			new \Twig_SimpleFilter('unserialize', array($this, 'unserializeValue')),
			new \Twig_SimpleFilter('unescape', array($this, 'unescape'))
		);
	}

	public function getName() 
	{
		return 'docova_extension';
	}
	
	/**
	 * Set global application object
	 * 
	 * @param string|\Docova\DocovaBundle\Entity\Libraries $application
	 */
	public function f_SetApplication($application)
	{
		$this->docova->setApplication($application);
	}

	public function f_SetViewScripting(){
		$this->isViewScripting = true;
	}

	
	/**
	 * Record whether we are in the mobile phone interface
	 * Called from mobile phone interface twig elements
	 * 
	 * @param bool
	 */
	public function f_SetIsMobile($value)
	{
	    $this->ismobile = $value;
	}
	
	
	/**
	 * Record the temp id that we produce for a new document
	 * 
	 * @param string
	 */
	public function f_SetNewDocUNID($unidval)
	{
		$this->newdocunid = $unidval;
	}

	/**
	 * Record that we are in refresh document mode ( called from  $$viewrefreshfields)
	 * Used in getField to return values from the document being submitted to the refresh
	 * 
	 * @param bool
	 */
	public function f_SetIsRefresh($value)
	{
		$this->isrefresh = $value;
	}

	public function f_ClearBuffer(){
		 $this->buffer = array();
	}
	
	/**
	 * Set global document object
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents|string $document
	 * @param \Docova\DocovaBundle\Entity\Libraries $library
	 */
	public function f_SetDocument($document, $library = null)
	{
		$this->docova->setDocument($document, $library);
		$this->buffer = array();
	}

	public function f_SetForm($formname)
	{
		$this->formname = $formname;
	}
	
	/**
	 * Set global user object
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 */
	public function f_SetUser($user)
	{
		$this->docova->setUser($user);
	}
	
	/**
	 * Abs twig script
	 * 
	 * @param mixed $argument_list
	 * @return mixed
	 */
	public function f_Abs($argument_list)
	{
		if (is_array($argument_list)) {
			foreach ($argument_list as $index => $value)
			{
				$argument_list[$index] = abs($value);
			}
		}
		else {
			$argument_list = abs($argument_list);
		}
		
		return $argument_list;
	}
	
	
	/**
	 * Abstract twig script
	 *
	 * @param object $context
	 * @param mixed $keywords
	 * @param number $size
	 * @param string $begintext
	 * @param string/string array $bodyfields
	 * @return string
	 */
	public function f_Abstract($context, $keywords = '', $size = 64994, $begintext = '', $bodyfields = '')
	{
		$result = '';
		
		$tempfields = (is_array($bodyfields) ? $bodyfields : [$bodyfields]);
		
		$flags = (is_array($keywords) ? join(",", $keywords) : $keywords);
		$textonly = (strpos($flags, "TEXTONLY") !== false); 
		$trimspaces = !(strpos($flags, "NOTRIMWHITE") !== false);
		
		$result = $begintext;
		
        for($i=0; $i<count($tempfields); $i++){
        	if(trim($tempfields[$i]) != "" ){
        		$fval = $this->f_GetField($context, $tempfields[$i]);
        		if($fval !== null){
        			$tempval = (is_array($fval) ? join(" ", $fval) : $fval);
        			$tempval = preg_replace('/\s+/', ' ', $tempval);
        			if($trimspaces){
        				$tempval = trim($tempval);
        			}
        			$result = $result . (strlen($result) > 0 ? " " : "") . $tempval;

        			if(strlen($result) > $size){
        				$result = substr($result, $size);
        				break;
        			}
        		}

        	}        		
        }

	
		return $result;
	}
	
	
	/**
	 * Acos twig script
	 * 
	 * @param mixed $argument
	 * @return mixed
	 */
	public function f_ACos($argument)
	{
		if (is_array($argument)) {
			if (empty($argument)) return array();
			foreach ($argument as $index => $value)
			{
				$value = floatval($value);
				$argument[$index] = acos($value) ? acos($value) : '';
			}
		}
		else {
			if (trim($argument) === '') { return ''; }
			$argument = acos($argument) ? acos($argument) : '';
		}
		return $argument;
	}
	
	/**
	 * Indicates the time and date when the document was last accessed
	 * (Since this function cannot be supported in DOCOVA we return @Modified value)
	 * 
	 * @return DateTime
	 */
	public function f_Accessed($context)
	{
		return $this->f_Modified($context);
	}
	
	/**
	 * Adjust date twig script
	 * 
	 * @param mixed $date
	 * @param number $year
	 * @param number $month
	 * @param number $day
	 * @param number $hours
	 * @param number $min
	 * @param number $sec
	 * @return string|array
	 */
	public function f_Adjust($date, $year = 0, $month = 0, $day = 0, $hours = 0, $min = 0, $sec = 0)
	{
		if (empty($date)) { return is_array($date) ? array() : ''; }
		$year = is_array($year) ? $year[0] : $year;
		$month = is_array($month) ? $month[0] : $month;
		$day = is_array($day) ? $day[0] : $day;
		$hours = is_array($hours) ? $hours[0] : $hours;
		$min = is_array($min) ? $min[0] : $min;
		$sec = is_array($sec) ? $sec[0] : $sec;
		
		$timezone = '';
		if(!$this->isViewScripting){
		    global $docova;
		    if (empty($docova) || !($docova instanceof Docova)) {
		        $docova = $this->docova->getDocovaObject();
		    }
		    $timezone = _GetUserTimeZone();
		}
		
		$result = null;
		
		if (is_array($date)) {
			$result = array();
			foreach ($date as $value) {
				$t = is_object($value) ? clone $value : $value;
				if (is_string($t)) {
					try {
						if(!empty($timezone)){
						    $t = new \DateTime($t, new \DateTimeZone($timezone));
						}else{
						    $t = new \DateTime($t);
						}
					} catch (\Exception $e) {
						array_push($result, '');
						continue;
					}
				}
				
				array_push($result, $this->adjustDate($t, is_string($value), $year, $month, $day, $hours, $min, $sec));
			}
		}
		else {
			$result = '';
			$t = is_object($date) ? clone $date : $date;				
			if (is_string($t)) {
				try {
				    if(!empty($timezone)){
				        $t = new \DateTime($t, new \DateTimeZone($timezone));
				    }else{
				        $t = new \DateTime($t);
				    }
				} catch (\Exception $e) {
					return $result;
				}
			}
			
			$result = $this->adjustDate($t, is_string($date), $year, $month, $day, $hours, $min, $sec);
		}
		
		return $result;
	}
	
	/**
	 * Adds current document to one folder while removing it from another.
	 * 
	 * @param string $folderadd
	 * @param string $folderremove
	 */
	public function f_AddToFolder($folderadd, $folderremove)
	{
		//@todo: DOCOVADomino - needs to be implemented
	}
	
	/**
	 * Mimicked \@All in Domino
	 * 
	 * @return boolean
	 */
	public function f_All()
	{
		return true;
	}
	
	/**
	 * Merge two arrays
	 * 
	 * @param mixed $array1
	 * @param mixed $array2
	 * @return array
	 */
	public function f_ArrayAppend($array1, $array2)
	{
		$array1 = is_array($array1) ? $array1 : array($array1);
		$array2 = is_array($array2) ? $array2 : array($array2);
		
		$return_array = array_merge($array1, $array2);
		return $return_array;
	}
	
	/**
	 * Merge array elements of an array in one dimensional array
	 * 
	 * @param mixed $argument
	 * @return array
	 */
	public function f_ArrayConcat($argument)
	{
		$output = array();
		if (empty($argument)) return $output;
		
		$tmp_array = is_array($argument) ? $argument : array($argument);
		for ($x = 0; $x < count($tmp_array); $x++)
		{
			if (is_array($tmp_array[$x])) {
				$output = array_merge($output, $tmp_array[$x]);
			}
			else {
				$output[] = $tmp_array[$x];
			}
		}
		return $output;
	}
	
	/**
	 * Searches an array of strings for the value given. If the value is found within the array,
	 * the array index of that value is returned.
	 * 
	 * @param array $haystack
	 * @param string $needle
	 * @param integer $compmethod
	 * @return NULL|integer
	 */
	public function f_ArrayGetIndex($haystack, $needle, $compmethod)
	{
		return _ArrayGetIndex($haystack, $needle, $compmethod);
	}
	
	
	/**
	 * Given an array returns the array minus the specified
	 * array element, either based on numeric index or matching string value.
	 *
	 * @param array $sourcearray
	 * @param mixed $element
	 * @return NULL|array
	 */
	public function f_ArrayRemoveItem($sourcearray, $element)
	{
		return _ArrayRemoveItem($sourcearray, $element);
	}
		
	
	
	/**
	 * Removes duplicate elements from an Array
	 * 
	 * @param array $source_array
	 * @param integer $compmethod
	 * @return NULL|array
	 */
	public function f_ArrayUnique($source_array, $compmethod = 0)
	{
		return _ArrayUnique ($source_array, $compmethod);
	}
	
	/**
	 * Get ascii twig script
	 * 
	 * @param mixed $argument
	 * @param string|array $flags
	 * @return mixed
	 */
	public function f_Ascii($argument, $flags = false)
	{
		return _ascii($argument, $flags);
	}
	
	/**
	 * ASin twig script
	 * 
	 * @param mixed $argument
	 * @return float[]
	 */
	public function f_ASin($argument)
	{
		return _ASin($argument);
	}
	
	/**
	 * ATan twig script
	 * 
	 * @param mixed $argument
	 * @return float[]
	 */
	public function f_ATan($argument)
	{
		return _ATan($argument);
	}

	/**
	 * ATan2 twig script
	 *
	 * @param mixed $argument
	 * @return mixed
	 */
	public function f_ATan2($argument1, $argument2)
	{
		return _ATan2($argument1, $argument2);
	}
	
	/**
	 * Returns a number/number list containing the length of each attachment to the current document
	 * 
	 * @param object $context	  
	 * @return number[]
	 */
	public function f_AttachmentLengths($context)
	{		
		$output = [];
		
		if ( $this->isViewScripting){
			$tmpbuffer = null;
			if ( isset($context['docvalues']) and is_array($context['docvalues'])){
				$tmpbuffer = $context['docvalues'];
			}
		
			if ($tmpbuffer != null && isset($tmpbuffer['__attachments'])){
				$attachments = $tmpbuffer['__attachments'];
				if (!empty($attachments)) {
					foreach ($attachments as $att) {
						$output[] = (!empty($att['fileSize']) ? intval($att['fileSize']) : 0);
					}
				}
			}
		}else{
			$attachments = $this->docova->getAttachments(1);
			if (!empty($attachments)) {
				foreach ($attachments as $att) {
					$output[] = (!empty($att->fileSize) ? intval($att->fileSize) : 0);
				}
			}
		}
		
		
		return (empty($output) ? 0 : (count($output) === 1 ? $output[0] : $output));		
		
	}
	
	/**
	 * Returns the system file names of any files attached to a document.
	 * 
	 * @param object $context	 
	 * @return string[]
	 */
	public function f_AttachmentNames($context)
	{
		$output = [];
		
		if ( $this->isViewScripting){
			$tmpbuffer = null;
			if ( isset($context['docvalues']) and is_array($context['docvalues'])){
				$tmpbuffer = $context['docvalues'];
			}
		
			if ($tmpbuffer != null && isset($tmpbuffer['__attachments'])){
				$attachments = $tmpbuffer['__attachments'];
				if (!empty($attachments)) {
					foreach ($attachments as $att) {
						$output[] = $att['fileName'];
					}
				}				
			}
		}else{
			$attachments = $this->docova->getAttachments(1);
			if (!empty($attachments)) {
				foreach ($attachments as $att) {
					$output[] = $att->fileName;
				}				
			}				
		}
		
		
		return (empty($output) ? '' : (count($output) === 1 ? $output[0] : $output));
		
	}
	
	
	/**
	 * Returns a date/date list containing the date/time of each attachment to the current document
	 *
	 * @param object $context
	 * @return \DateTime[]
	 */
	public function f_AttachmentModifiedTimes($context)
	{
		$output = [];
	
		if ( $this->isViewScripting){
			$tmpbuffer = null;
			if ( isset($context['docvalues']) and is_array($context['docvalues'])){
				$tmpbuffer = $context['docvalues'];
			}
	
			if ($tmpbuffer != null && isset($tmpbuffer['__attachments'])){
				$attachments = $tmpbuffer['__attachments'];
				if (!empty($attachments)) {
					foreach ($attachments as $att) {
						$output[] = (!empty($att['fileDate']) ? $att['fileDate'] : '');
					}
				}
			}
		}else{
			$attachments = $this->docova->getAttachments(1);
			if (!empty($attachments)) {
				foreach ($attachments as $att) {
					$output[] = (!empty($att->fileDate) ? $att->fileDate : '');
				}
			}
		}
	
	
		return (empty($output) ? '' : (count($output) === 1 ? $output[0] : $output));
	
	}	
	

	/**
	 * Get attachments count
	 * 
	 * @param object $context	  
	 * @return number
	 */
	public function f_Attachments($context)
	{
		$count = 0;
		
		if ( $this->isViewScripting){
			$tmpbuffer = null;
			if ( isset($context['docvalues']) and is_array($context['docvalues'])){
				$tmpbuffer = $context['docvalues'];		
			}
		
			if ($tmpbuffer != null && isset($tmpbuffer['__attachments'])){
				$count = count($tmpbuffer['__attachments']);
			}
		}else{
			$count = $this->docova->getAttachments(0);			
		}
		
		return $count;
	}
	
	public function f_Author()
	{
		//@todo: return list of current document authors (abbreviated usernames)
	}

	/**
	 * Compare if string(s) begins with something
	 * 
	 * @param string[] $content_string
	 * @param string[] $search_string
	 * @return boolean
	 */
	public function f_Begins($content_string, $search_string)
	{
		if (!isset($content_string) || !isset($search_string)) { return false; }
		$content_string = is_array($content_string) ? $content_string : array($content_string);
		$search_string = is_array($search_string) ? $search_string : array($search_string);
		
		foreach ($content_string as $c)
		{
			foreach ($search_string as $s)
			{
				if (0 === strpos($c, $s)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Char twig script (Ascii to character)
	 * 
	 * @param string[] $argument
	 * @return string[]
	 */
	public function f_Char($argument)
	{
		return _Char($argument);
	}
	
	public function f_ClientType()
	{
		return 'Notes';
	}

	/*
	 * I BELEIVE THIS METHOD IS FOR CLIENT SIDE.
	 */
	public function f_Command($action, $param1 = null, $param2 = null, $param3 = null, $param4 = null, $param5 = null)
	{
	}
	
	/**
	 * Compare two string lists
	 * 
	 * @param string|array $list1
	 * @param string|array $list2
	 * @param string $flags
	 * @return number[]
	 */
	public function f_Compare($list1, $list2, $flags = null)
	{
		$output = array();
		if (!isset($list1) || !isset($list2)) {
			return $output;
		}
		
		$case_sensitive = $accent_sensitive = $pitch_sensitive = true;
		if (!empty($flags)) 
		{
			$case_sensitive = (false !== strpos($flags, 'CASEINSENSITIVE')) ? false : true;
			$accent_sensitive = (false !== strpos($flags, 'ACCENTINSENSITIVE')) ? false : true;
			$pitch_sensitive = (false !== strpos($flags, 'PITCHINSENSITIVE')) ? false : true;
		}
		
		$list1 = is_array($list1) ? $list1 : array($list1);
		$list2 = is_array($list2) ? $list2 : array($list2);
		$count = max(array(count($list1), count($list2)));
		
		for ($x = 0; $x < $count; $x ++) 
		{
			$val1 = isset($list1[$x]) ? $list1[$x] : '';
			$val2 = isset($list2[$x]) ? $list2[$x] : '';
			
			if ($accent_sensitive === true || $pitch_sensitive === true) 
			{
				$val1 = $this->f_Ascii($val1);
				$val2 = $this->f_Ascii($val2);
			}
			
			if ($case_sensitive === true) {
				$output[$x] = strcasecmp($val1, $val2);
			}
			else {
				$output[$x] = strcmp($val1, $val2);
			}
		}
		return $output;
	}
	
	/**
	 * Search needle(s) in array
	 * 
	 * @param string|array $haystack
	 * @param string|array $needle
	 * @return boolean
	 */
	public function f_Contains($haystack, $needle)
	{
		if (!isset($haystack) || !isset($needle)) { return false; }

		if ( is_string($haystack) && is_string($needle)){
			if (strstr($haystack, $needle))
				return true;
			else
				return false;
		}
		
		$haystack = is_array($haystack) ? $haystack : array($haystack);
		$needle = is_array($needle) ? $needle : array($needle);
		
		foreach ($needle as $search) {
			if ($search == '') {
				return true;
			}

			foreach ($haystack as $string) {
				if (false !== strstr($string, $search)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Get cos
	 * 
	 * @param array|string $argument
	 * @return number[]
	 */
	public function f_Cos($argument)
	{
		return _Cos($argument);
	}
	
	/**
	 * Get array count
	 * 
	 * @param mixed $argument
	 * @return number
	 */
	public function f_Count($argument)
	{
		if (!is_array($argument)) return 1;
		return count($argument);
	}
	
	/**
	 * Generate a datetime object
	 * 
	 * @param array|string $datetimeoryear
	 * @param string $month
	 * @param string $day
	 * @param string $hour
	 * @param string $minute
	 * @param string $sec
	 * @return \DateTime[]
	 */
	public function f_Date($datetimeoryear, $month = null, $day = null, $hour = null, $minute = null, $sec = null)
	{
		if (empty($datetimeoryear)) return '';
		
		$timezone = '';
		if(!$this->isViewScripting){
		    global $docova;
		    if (empty($docova) || !($docova instanceof Docova)) {
		        $docova = $this->docova->getDocovaObject();
		    }
		    $timezone = _GetUserTimeZone();
		}
		
		$output = array();
		if (isset($hour) && isset($minute) && isset($sec)) 
		{
			$format = 'm-d-Y H:i:s';
			$textdate = "{$month}-{$day}-{$datetimeoryear} {$hour}:".(strlen((string)$minute)<2 ? '0' : '')."{$minute}:".(strlen((string)$sec)<2 ? '0' : '')."{$sec}";
			if(!empty($timezone)){
			    $tempdate = \DateTime::createFromFormat($format, $textdate, new \DateTimeZone($timezone));
			}else{
			    $tempdate = \DateTime::createFromFormat($format, $textdate);
			}
			$output[] = ($tempdate === false ? '' : $tempdate);
		}
		elseif (isset($month) && isset($day)) {
			$format = 'm-d-Y';
			if(!empty($timezone)){
			    $date = \DateTime::createFromFormat($format, "$month-$day-$datetimeoryear", new \DateTimeZone($timezone));
			}else{
			    $date = \DateTime::createFromFormat($format, "$month-$day-$datetimeoryear");
			}
			$output[] = $date;
		}
		else {
			$datetimeoryear = is_array($datetimeoryear) ? $datetimeoryear : array($datetimeoryear);
			foreach ($datetimeoryear as $value)
			{
				if ($value instanceof \DateTime) 
				{
					$value->setTime(0, 0, 0);
					$output[] = $value;
				}
				elseif (is_string($value)) {
					try {
					    if(!empty($timezone)){
					        $date = new \DateTime($value, new \DateTimeZone($timezone));
					    }else{
					        $date = new \DateTime($value);
					    }
					    
						$date->setTime(0, 0, 0);
						$output[] = $date;
					} 
					catch (\Exception $e) {
						$output[] = '';
					}
				}
				else {
					$output[] = '';
				}
			}
		}
		return is_array($datetimeoryear) ? $output : $output[0];
	}
	
	/**
	 * Get day of a month
	 * 
	 * @param \DateTime|array $argument
	 * @return string|NULL[]
	 */
	public function f_Day($argument)
	{
		if (empty($argument)) return '';
		$output = array();
		$dates = is_array($argument) ? $argument : array($argument);
		foreach ($dates as $value) {
			if ($value instanceof \DateTime) {
				$output[] = intval($value->format('j'));
			}
		}
		return is_array($argument) ? $output : $output[0];
	}
	
	/**
	 * Returns the values of a view column
	 * 
	 * @param string $notused
	 * @param string $serverdb
	 * @param string $viewname
	 * @param integer $column
	 * @return array
	 */
	public function f_DbColumn($notused = null, $serverdb, $viewname, $column)
	{
		$serverdb = is_array($serverdb) && !empty($serverdb) ? $serverdb[0] : '';
		return $this->docova->dbColumn($viewname, $column);
	}
	
	/**
	 * Returns view column or field values that correspond to matched keys in a sorted view column.
	 * 
	 * @param string $notused
	 * @param string $serverdb
	 * @param string $viewname
	 * @param string $key
	 * @param integer|string $columnorfield
	 * @param string
	 * @return array
	 */
	public function f_DbLookup($notused = null, $serverdb, $viewname, $key, $columnorfield, $keywords = null)
	{
	    $oldapp = $this->docova->getCurrentApplication();
	    if(empty($oldapp)){
	        $session = $this->docova->getDocovaSessionObject();
	        $oldapp = $session->getCurrentDatabase();
	        unset($session);
	    }
	    
		$serverdb = is_array($serverdb) ? $serverdb[1] : $serverdb;
		if(!empty($serverdb)){
		    $this->docova->setApplication($serverdb);
		}
		$result = $this->docova->dbLookup($viewname, $key, $columnorfield, $keywords);
		if(!empty($oldapp)){
    		$this->docova->setApplication($oldapp);
		}
		if (!empty($result))
		{
			$result = explode(';', $result);
			return $result;
		}
		else {
			return array();
		}
	}
	
	/**
	 * Get server and db path
	 * 
	 * @return array
	 */
	public function f_DbName()
	{
		//@NOTE: if this caused to issue the following should be commented out
		$application = $this->docova->getCurrentApplication();
		if (empty($application)) {
			return ["", ""];
		}

		return array($application->getServer(), $application->appID);
	}

	/**
	 * Returns computed action on a view column values that correspond to matched where clause.
	 * 
	 * @param string $serverdb
	 * @param string $viewname
	 * @param string $action
	 * @param string|integer $columnorfield
	 * @param array $criteria
	 * @return number
	 */
	public function f_DbCalculate($serverdb, $viewname, $action, $columnorfield, $criteria = [])
	{
		$oldapp = $this->docova->getCurrentApplication();
		if(empty($oldapp)){
			$session = $this->docova->getDocovaSessionObject();
			$oldapp = $session->getCurrentDatabase();
			unset($session);
		}
		 
		$serverdb = is_array($serverdb) ? $serverdb[1] : $serverdb;
		if(!empty($serverdb)){
			$this->docova->setApplication($serverdb);
		}
		
		$result = $this->docova->dbCalculate($viewname, $action, $columnorfield, $criteria);
		if(!empty($oldapp)){
    		$this->docova->setApplication($oldapp);
		}
		
		if (!empty($result)) {
			return $result;
		}
		
		return 0;
	}

	/**
	 * Gets the current apps title
	 * 
	 * @return string
	 */
	public function f_DbTitle()
	{
		$application = $this->docova->getCurrentApplication();
		if (empty($application)) {
			$session = $this->docova->getDocovaSessionObject();
			$application = $session->getCurrentDatabase();
			if (empty($application))
				return '';
		}
		return $application->getTitle();
	}
	
	
	/**
	 * Returns a null value
	 *
	 * @return string
	 */
	public function f_DeleteField()
	{
        	return "";
	}
	
	
	/**
	 * In a column or window title formula, returns the number of descendant documents or subcategories belonging to the current document or category
	 * 
	 * @param string $default_str
	 * @param string $zeor_str
	 * @param string $one_str
	 * @return string
	 */
	public function f_DocDescendants($default_str = '', $zeor_str = '', $one_str = '')
	{
		//@todo: if we couldn't handle this on client side, this needs to compute the actual descendants of current document in back-end
		return '';
	}
	
	/**
	 * Returns the approximate size of a document
	 * 
	 * @return number
	 */
	public function f_DocLength()
	{
		//@todo: since it might be expensive to calculate document size live, in future we may need to add a field to documnet table indicates the size
		// for now we just return 0;
		return 0;
	}
	
	public function f_DocNumber()
	{
		//@todo: to be done in future!
		return '';
	}
	
	/**
	 * Returns the approximate number of bytes a truncated document (always 0 in SE)
	 * 
	 * @return number
	 */
	public function f_DocOmittedLength()
	{
		return 0;
	}
	
	/**
	 * Get current document unique ID
	 * 
	 * @return string
	 */
	public function f_DocumentUniqueID($context=null)
	{
	    if ($this->f_IsNewDoc()){
	        return $this->newdocunid ;
	    }else{ 	        
		if ( $this->isViewScripting){
			$tmpbuffer = null;
	            if ( isset($context) and isset($context['docvalues']) and is_array($context['docvalues'])){
				$tmpbuffer = $context['docvalues'];
				
			}

			if ($tmpbuffer != null && isset($tmpbuffer['__id'])){
				$docval = $tmpbuffer['__id'];
				return $docval;
			}
			return "";
		}

	        return $this->docova->getDocumentUniqueId();
	    }
	}
	
	/**
	 * Get count of array elements
	 * 
	 * @param array|string $argument
	 * @return number
	 */
	public function f_Elements($argument)
	{
		if (empty($argument) ) return 0;
		if ( !is_array($argument) ) return 1;
		$len = count($argument);
		return $len;
	}
	
	/**
	 * Compare if string(s) ends with something
	 * 
	 * @param string[] $content_string
	 * @param string[] $search_string
	 * @return boolean
	 */
	public function f_Ends($content_string, $search_string)
	{
		if (!isset($content_string) || !isset($search_string)) { return false; }
		$content_string = is_array($content_string) ? $content_string : array($content_string);
		$search_string = is_array($search_string) ? $search_string : array($search_string);
		
		foreach ($content_string as $c)
		{
			foreach ($search_string as $s)
			{
				$strlen = strlen($c);
				$ndlen = strlen($s);
				if ($strlen < $ndlen) continue;
				if (substr_compare($c, $s, $strlen - $ndlen, $ndlen) === 0) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Sets or returns an environment variable stored in a formula.
	 * 
	 * @param string $varname
	 * @param mixed $value
	 * @return mixed
	 */
	public function f_Environment($varname, $value = null)
	{
		if (empty($varname))
			return '';
		
		$session = $this->docova->getDocovaSessionObject();
		if ($value === null)
			return $session->getEnvironmentVar($varname);
		else 
			$session->setEnvironmentVar($varname, $value);

		$session = null;
		return;
	}
	
	public function f_Error()
	{
		return 'ERROR: Oop! There seems to be some error!';
	}
	
	/**
	 * Calculate the exponent of E
	 * 
	 * @param mixed $argument
	 * @return number
	 */
	public function f_Exp($argument)
	{
		if (is_array($argument))
		{
			for ($x = 0; $x < count($argument); $x++)
			{
				$value = intval($argument[$x]);
				$argument[$x] = exp($value); 
			}
		}
		else {
			$argument = intval($argument);
			$argument = exp($argument);
		}
		return $argument;
	}
	
	/**
	 * Explode string(s) to array
	 * 
	 * @param string|array $content
	 * @param array $separators
	 * @param boolean $includeempties
	 * @param boolean $breakonnewline
	 * @return array
	 */
	public function f_Explode($content, $separators = array(',', ';'), $includeempties = false, $breakonnewline = true)
	{
		$output = array();
		if (!isset($content)) return $output;
		
		$content = is_array($content) ? $content : array($content);
		$separators = empty($separators) ? array(',', ';') :str_split( $separators);
		
		if ($breakonnewline === true) {
			$separators[] = '\n';
		}

		//for($i=0; $i<count($separators); $i++){
		//	$separators[$i] = preg_replace('/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/', '\\$&', $separators[$i]);
		//}
		$separators = implode('', $separators);
		
		foreach ($content as $string)
		{
			$res = preg_split("/[$separators]/", $string);
			for ($x = 0; $x < count($res); $x++) {
				if ($res[$x] != '' || $includeempties === true) {
					$output[] = $res[$x];
				}
			}
		}
		return $output;
	}
	
	/**
	 * Print failur message
	 * 
	 * @param string $message
	 * @return string
	 */
	public function f_Failure($message)
	{
		return $message;
	}
	
	/**
	 * Return false
	 * 
	 * @return boolean
	 */
	public function f_False()
	{
		return false;
	}
	
	/**
	 * Formats a number, a date/time, or a string according to a supplied format.
	 * 
	 * @param mixed $expr
	 * @param string $format
	 * @return string
	 */
	public function f_Format($expr, $format = '')
	{
	    global $docova;
	    if (empty($docova) || !($docova instanceof Docova)) {
	        $docova = $this->docova->getDocovaObject();
	    }
	    
		return _Format($expr, $format);
	}
	
	/**
	 * Formats a DateTime object to the supplied format
	 * 
	 * @param \DateTime $date
	 * @param string $format
	 * @return string
	 */
	public function f_FormatDate($date, $format)
	{
	    global $docova;
	    if (empty($docova) || !($docova instanceof Docova)) {
	        $docova = $this->docova->getDocovaObject();
	    }
	    
		return _FormatDate($date, $format);
	}
	
	/**
	 * Get document field value
	 * 
	 * @param string $docunid
	 * @param string $fieldname
	 * @return null|string
	 */
	public function f_GetDocField($context = null, $docunid, $fieldname)
	{
		$result = null;
		if (empty($docunid)){
			return $result;
		}


		$document = null;
		if ( $this->f_DocumentUniqueID($context) == $docunid && !$this->isViewScripting){

			$document = $this->docova->getDocument();
		}else{
		    $application = $this->docova->getCurrentApplication();		    
		    if (empty($application)) {
		        $session = $this->docova->getDocovaSessionObject();
		        $application = $session->getCurrentDatabase();
		        unset($session);
		    }
		    if (!empty($application)){
			$document = $application->getDocument($docunid);
		    }
		}

		$application = null;
		if (!empty($document))
		{
			$tempvar = $document->getField($fieldname);
			return $tempvar;
		}
		return $result;
	}

	public function f_GetFieldString($context, $fieldname, $mvsep=",", $type = "", $iseditable = false, $nameformat = null){

		$fval = $this->f_GetField($context, $fieldname);

		//note: no filter on $iseditable and raw filter on read mode

		//if new line in delimiters then use that

		if ( $mvsep == "newline"  ){
			$mvsep = !$iseditable ? "<br>" : "\r\n";
		}else if ( $mvsep == "blankline"){
			$mvsep = !$iseditable ? "<br><br>" : "\r\n\n";
		}else if ( $mvsep == "space"){
			$mvsep = !$iseditable ? "&nbsp;" : " ";
		}else if  ( $mvsep == "semicolon"){
			$mvsep = ";";
		}else if ( $mvsep == "comma"){
			$mvsep = ",";
		}
		
		if ( $type == "names"){
			if ( is_array($fval) ){
				if ($iseditable === false) {
					if (empty($nameformat)) {
						$retval = implode($mvsep, $this->f_Name('[ABBREVIATE]', $fval));
					}
					else {
						if ($nameformat == 'DN') {
							$retval = implode($mvsep, $fval);
						}
						elseif ($nameformat == 'C') {
							$retval = implode($mvsep, $this->f_Name('[CN]', $fval));
						}
						else {
							$retval = implode($mvsep, $this->f_Name('[ABBREVIATE]', $fval));
						}
					}
				}
				else {
					$retval = '';
					if ($nameformat == 'DN') {
						$names = $fval;
					}
					elseif ($nameformat == 'C') {
						$names = $this->f_Name('[CN]', $fval);
					}
					else {
						$names = $this->f_Name('[ABBREVIATE]', $fval);
					}
					if (!empty($names[0])) {
						foreach ($names as $n) {
							$retval .= '<span>'.$n.'<i class="far fa-times removename"></i></span>';
						}
					}
				}
				return $retval;
			}else{
				if ($nameformat == 'DN') {
					$retval = $fval;
				}
				elseif ($nameformat == 'C') {
					$retval = $this->f_Name('[CN]', $fval);
				}
				else {
					$retval = $this->f_Name('[ABBREVIATE]', $fval);
				}
				return $retval;
			}
		}elseif ($type == 'date' || $type == 'datetime' || $type == 'time') {
		    if($type == "date"){
    			$fmt  = $this->dateFormat;
		    }elseif($type == "datetime"){
		        $fmt = $this->dateFormat." h:i A";
		    }elseif($type == "time"){
		        $fmt = "h:i A";
		    }else{
		        return '';
		    }
			if (is_array($fval)) {
				$output = '';
				foreach ($fval as $v) {
					if ($v instanceof \DateTime) {
						$output .= $v->format($fmt).$mvsep;
					}
					else {
						$output .= $v.$mvsep;
					}
				}
				if (!empty($mvsep)) {
					$output = substr_replace($output, '', -1);
				}
				return $output;
			}
			elseif ($fval instanceof \DateTime) {
				return $fval->format($fmt);
			}
			else {
				return $fval;
			}
		}

		if ( is_string($fval)) return $fval;

		if ( is_numeric($fval)) return (string)$fval;

		if ( is_array($fval) ){
			return implode($mvsep, $fval);
		}

		return $fval;

	}
	
	/**
	 * Get current document field value
	 * 
	 * @param string $fieldname
	 * @return null|string
	 */
	public function f_GetField( $context, $fieldname)
	{
		$docval = null;
		if (is_null($fieldname) || empty($fieldname))
			return "";

		$fieldname = strtolower($fieldname);

		if ($fieldname == "form")
			return $this->f_GetFormName($context);

		//check in the buffer first
		//a field will get into the buffer tthroug f_AddcoputedtoBuffer which will add
		//the currntly calcuated value to the buffer so that fields subsequent to this will be able to 
		//access the newwly comuted value.
		if (isset($this->buffer[$fieldname])){
			$docval = $this->buffer[$fieldname];
			return $docval;
		}

		$tmpbuffer = null;
		if ( isset($context['docvalues']) && is_array($context['docvalues'])){
			$tmpbuffer = $context['docvalues'];		
		}
		
		if ($tmpbuffer != null && isset($tmpbuffer[$fieldname])){
			$docval = $tmpbuffer[$fieldname];
			return $docval;
		}

		//if new doc and we have a parent, then get the value from the parent so that we can inherit matching fields from the parent

		if ( $this->f_IsNewDoc() && isset($context['parentdocument']) && is_array($context['parentdocument']) && is_array($context['parentdocument'][0])&& isset($context['parentdocument'][0][$fieldname]) ){
			$docval = $context['parentdocument'][0][$fieldname];
		}

		/*NOTE:: all default formula values and computed formula values are stored in the buffer through f_AddComputedToBuffer 
	 	 if the doc is new then we check this buffer for the value.  
	 	 this is to allow values that have already been computed to this point to be available */

		//check in the buffer first
		if ( isset($this->buffer[$fieldname]))
			$docval = $this->buffer[$fieldname];

		//if we are in viewscripting then we already have all the fields an there is no need to look into the backend doc to get the value
		
		//if we don't have a value yet..get it from the backend
		if ( is_null($docval) && !$this->isViewScripting ){
			if (!(isset($context['docvalues']) && is_array($context['docvalues']) && isset($context['docvalues_gotall']) && $context['docvalues_gotall'] == TRUE)){
				if ($this->f_IsNewDoc()) {
					switch ($fieldname) {
						case '__status':
							$properties = $this->docova->getFormProperties($this->formname);
							if ($properties && $properties->getEnableLifeCycle()) {
								$docval = $properties->getInitialStatus();
							}
							break;
						case '__statusno':
							$properties = $this->docova->getFormProperties($this->formname);
							if ($properties && $properties->getEnableLifeCycle()) {
								$docval = 0;
							}
							else {
								$docval = 1;
							}
							break;
						case '__version':
							$properties = $this->docova->getFormProperties($this->formname);
							if ($properties && $properties->getEnableLifeCycle() && $properties->getEnableVersions()) {
								$docval = '0.0.0';
							}
							break;
						default:
							$docval = $this->f_GetDocField($context, $this->f_DocumentUniqueID($context), $fieldname);
							break;
					}
				}
				else {
					$docval = $this->f_GetDocField($context, $this->f_DocumentUniqueID($context), $fieldname);
				}
			}
		}

		if ( is_null($docval) )
			$docval = "";

		if(is_array($docval) && count($docval) == 1){
			$docval = $docval[0];
		}
		return $docval;
	}
	
	/**
	 * Get field value base on document profile
	 * 
	 * @param string $profilename
	 * @param string $fieldname
	 * @param string $key
	 * @return array|boolean
	 */
	public function f_GetProfileField($profilename, $fieldname, $key = null)
	{
		if (empty($profilename) || empty($fieldname)) return false;
		$session = $this->docova->getDocovaSessionObject();
		$application = $session->getCurrentDatabase();
		if (!empty($application))
		{
			return $application->getProfileField($profilename, $fieldname, $key);
		}
		else {
			$application = $this->docova->getCurrentApplication();
			if (!empty($application))
				return $application->getProfileField($profilename, $fieldname, $key);
		}
		return "";
		//return $this->docova->getProfileField($profilename, $fieldname, $key);
	}
	
	/**
	 * Get hour value from datetime object(s)
	 * @param \DateTime[] $argument
	 * @return number[]
	 */
	public function f_Hour($argument)
	{
		if (empty($argument)) return '';
		$output = array();
		$dates = is_array($argument) ? $argument : array($argument);
		foreach ($dates as $value) {
			if ($value instanceof \DateTime) {
				$output[] = intval($value->format('H'));
			}
			else {
				$output[] = -1;
			}
		}
		return is_array($argument) ? $output : $output[0];
	}
	
	/**
	 * Implode array elements
	 * 
	 * @param array|string $list
	 * @param string $glue
	 * @return string
	 */
	public function f_Implode($list, $glue = ' ')
	{
		return _Implode($list, $glue);
	}
	
	/**
	 * Returns the position of the character that begins the first occurrence of one string within another string.
	 * 
	 * @param string|number $param
	 * @param string $string
	 * @param string $last_string
	 * @param number $comptype
	 * @return number
	 */
	public function f_InStr($param, $string, $last_string = null, $comptype = 0)
	{
		return _instr($param, $string, $last_string, $comptype);
	}
	
	/**
	 * Truncates a number to an integer.
	 * 
	 * @param mixed $argument
	 * @return integer[]|null
	 */
	public function f_Integer($argument)
	{
		return _Int($argument);
	}
	
	/**
	 * Tests the value of an expression to determine whether it is a date/time value.
	 * 
	 * @param mixed $argument
	 * @return boolean
	 */
	public function f_IsDate($argument)
	{
		return _IsDate($argument);
	}
	
	/**
	 * Indecated if a document is truncated (always return false)
	 * 
	 * @return boolean
	 */
	public function f_IsDocTruncated()
	{
		return false;
	}
	
	/**
	 * Tests the value of an expression to determine whether it is EMPTY.
	 * 
	 * @param mixed $argument
	 * @return boolean
	 */
	public function f_IsEmpty($argument)
	{
		return _IsEmpty($argument);
	}
	
	/**
	 * Is error
	 * 
	 * @param string $value
	 * @return boolean
	 */
	public function f_IsError($value)
	{
		return $value === '@ERROR';
	}
	
	/**
	 * Indicates if a list contains a string or strings.
	 * 
	 * @param string[] $value
	 * @param array $list
	 * @return boolean
	 */
	public function f_IsMember($value, $list)
	{
		if (!isset($value) || !isset($list))
			return false;
		
		$list = is_array($list) ? $list : array($list);
		if (is_array($value))
		{
			foreach ($value as $v)
			{
				if (!in_array($v, $list))
				{
					return false;
				}
			}
			return true;
		}
		else {
			return in_array($value, $list);
		}
		return false;
	}

	/**
	 * Is current document being edited.
	 * 
	 * @return boolean
	 */
	public function f_IsDocBeingEdited()
	{
		$mode = $this->docova->getCurrentMode();
		if ($mode == 'edit')
			return true;
		
		return false;
	}
	
	/**
	 * Is current document beign loaded.
	 * 
	 * @return boolean
	 */
	public function f_IsDocBeingLoaded()
	{
		//@todo: for future
		return false;
	}
	
	
	/**
	 * Is current document being saved.
	 *
	 * @return boolean
	 */
	public function f_IsDocBeingSaved()
	{
		//@todo: for future
		return $true;
	}	
	
	
	/**
	 * Are we running in the phone mobile client
	 *
	 * @return boolean
	 */
	public function f_IsMobile()
	{
	    return $this->ismobile;
	}
	
	
	/**
	 * Is current document a new document
	 * 
	 * @return boolean
	 */
	public function f_IsNewDoc()
	{
		if ( $this->isViewScripting){
			return false;
		}
		return $this->docova->isNew();
	}	
	
	
	public function f_GetFormName($context = null){

		if ( $this->isViewScripting){
			$tmpbuffer = null;
			if ( isset($context['docvalues']) and is_array($context['docvalues'])){
				$tmpbuffer = $context['docvalues'];
				
			}

			if ($tmpbuffer != null && isset($tmpbuffer['__form'])){
				$docval = $tmpbuffer['__form'];
				return $docval;
			}
			return "";
		}

		if ( $this->f_IsNewDoc()){
			return $this->formname;
		}else{
			return $this->docova->getFormName();
		}
	}
	
	/**
	 * Indicates if a list does not contain a string or strings.
	 * 
	 * @param string[] $value
	 * @param array $list
	 * @return boolean
	 */
	public function f_IsNotMember($value, $list)
	{
		if (!isset($value) || !isset($list))
			return true;
		
		$list = is_array($list) ? $list : array($list);
		if (is_array($value))
		{
			foreach ($value as $v)
			{
				if (in_array($v, $list))
					return false;
			}
			return true;
		}
		else {
			return !in_array($value, $list);
		}
		return true;
	}
	
	/**
	 * Is value null
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function f_IsNull($value)
	{
		return _IsNull($value);
	}
	
	/**
	 * Is value a number
	 * 
	 * @param mixed $argument
	 * @return boolean
	 */
	public function f_IsNumber($argument)
	{
		if (is_array($argument)) {
			foreach ($argument as $value) {
				if (!(is_numeric($value) && !is_string($value))) {
					return false;
				}
			}
		}
		else {
		    return (is_numeric($argument) && !is_string($argument));
		}
		return true;
	}
	
	/**
	 * Tests the value of an expression to determine whether it is numeric, or can be converted to a numeric value.
	 * 
	 * @param mixed $argument
	 * @return boolean
	 */
	public function f_IsNumeric($argument)
	{
		if (is_array($argument))
		{
			foreach ($argument as $value) {
				if (!is_numeric($value)) {
					return false;
				}
			}
		}
		else {
			return is_numeric($argument);
		}
		return true;
	}
	
	/**
	 * Indicates whether a document is a response to another document. 
	 * 
	 * @return boolean
	 */
	public function f_IsResponseDoc($context)
	{
		if ( $this->f_IsNewDoc() && isset($context['parentdocument'])) {
			return true;
		}
		elseif (isset($context['docvalues']) && isset($context['docvalues']['__parentdoc'])){
		    return true;
		//}
		//elseif ($this->docova->isResponse()) {
		//	return true;
		}
		
		return false;
	}
	
	/**
	 * Is value a text(string)
	 * 
	 * @param mixed $argument
	 * @return boolean
	 */
	public function f_IsText($argument)
	{
		if (is_array($argument)) {
			foreach ($argument as $value) {
				if (!is_string($value)) {
					return false;
				}
			}
		}
		else {
			return is_string($argument);
		}
		return true;
	}
	
	/**
	 * Is value a datetime object
	 * 
	 * @param mixed $argument
	 * @return boolean
	 */
	public function f_IsTime($argument)
	{
		if (empty($argument)) return false;
		if (is_array($argument)) {
			foreach ($argument as $value) {
				if (!($value instanceof \DateTime)) {
					return false;
				}
			}
		}
		else {
			return $argument instanceof \DateTime;
		}
		return true;
	}
	
	/**
	 * Is fieldname unavailable in current form
	 * 
	 * @param string $fieldname
	 * @return boolean
	 */
	public function f_IsUnavailable($context=null, $fieldname)
	{
	    return !$this->f_IsAvailable($context, $fieldname);
	}

	/* 
	 * Given two text lists, returns only those items from the second list that are found in the first list.
	 * @param string $fieldname
	 * @return boolean
	 */
	public function f_Keywords($list1, $list2, $separator)
	{
		//TODO ..add all bells and whistles of the notes version of this function
		$output = array();
		$arr1 =  is_array($list1) ? $list1 : explode($separator, $list1);
		$arr2 =  is_array($list2) ? $list2 : explode($separator, $list2);

		return array_intersect($arr1, $arr2);
	}

	/** 
	 * @param string $fieldname
	 * @return boolean
	 */
	public function f_IsAvailable($context = null, $fieldname)
	{
		if ( is_null($fieldname)) return false;

		$fieldname = strtolower($fieldname);
		if ($this->isViewScripting){
			$tmpbuffer = null;
			if ( isset($context['docvalues']) and is_array($context['docvalues'])){
				$tmpbuffer = $context['docvalues'];
			}
		
			if ($tmpbuffer != null && array_key_exists($fieldname, $tmpbuffer)){
				return true;
			}
			return false;
		}
		
		//for new docs, we check the buffer
		if ( isset($this->buffer[$fieldname])){
			return true;
		}

		if ($this->f_IsNewDoc()) return false;
	    $application = $this->docova->getCurrentApplication();
	    if (empty($application)) {
			$session = $this->docova->getDocovaSessionObject();
			$application = $session->getCurrentDatabase();
			unset($session);
			if (empty($application)){ return false; }
		}
		if(!empty($application)){
			$document = $application->getDocument($this->docova->getDocumentUniqueId());
			if(!empty($document)){
				return $document->hasItem($fieldname);
			}
		}
		return false;
	}


	/**
	 * Returns the time-date when the document was created
	 * 
	 * 
	 * @return string
	 */
	public function f_Created($context)
	{
		if ($this->isViewScripting){
			$tmpbuffer = null;
			$fieldname = "__created";
			if ( isset($context['docvalues']) and is_array($context['docvalues'])){
				$tmpbuffer = $context['docvalues'];
				
			}

			if ($tmpbuffer != null && isset($tmpbuffer[$fieldname])){
				$docval = $tmpbuffer[$fieldname];
				return $docval;
			}
			return "";
		}
		$session = $this->docova->getDocovaSessionObject();
		$application = $this->docova->getCurrentApplication();
		unset($session);
		if ($this->f_IsNewDoc()){
			return $this->f_Now();
		}

		$document = $application->getDocument($this->docova->getDocumentUniqueId());
		if ( !empty($document) )
			return $document->getCreated();
		else
			return $this->f_Now();
		//return !$this->docova->fieldExists($fieldname);
	}
	
	/**
	 * Returns the language preference for a user
	 * @param string $key
	 * @return string
	 */
	public function f_LanguagePreference($key=null)
	{
	    $result = "";
	    
	    $current_user = $this->docova->getDocovaSessionObject()->getCurrentUser();
	    if (!empty($current_user)){	        
	        $result = $current_user->getUserProfile()->getLanguage();
	    }
	    
	    return $result;
	}
	
	/**
	 * Get first pending workflow step assignee name
	 * 
	 * @return string
	 */
	public function f_FirstPendingFor()
	{
		$assignee = '';
		if ($this->f_IsNewDoc()) {
			return $assignee;
		}
		
		$assignee = $this->docova->getFirstPendingFor();
		return $assignee;
	}
	
	/**
	 * Returns the lower bound for one dimension of an array.
	 * 
	 * @param array $array
	 * @return boolean|number
	 */
	public function f_LBound($array)
	{
		return _LBound($array);
	}
	
	/**
	 * Returns leftmost found characters of the string
	 * 
	 * @param string[] $string_search
	 * @param string|number $selection
	 * @param integer $compmethod
	 * @return string[]
	 */
	public function f_Left($string_search, $selection, $compmethod = 0)
	{
		return _Left($string_search, $selection, $compmethod = 0);
	}
	
	/**
	 * Searches a string from right to left and returns a substring.
	 * 
	 * @param string[] $search_string
	 * @param string|number $selection
	 * @return string[]
	 */
	public function f_LeftBack($search_string, $selection)
	{
	    if (!isset($search_string) || !isset($selection)){
			return '';
	    }
	    if (is_numeric($selection)) {
	        $selection = intval($selection);
	    }
		
		$search_string = is_array($search_string) ? $search_string : array($search_string);
		$output = array();
		foreach ($search_string as $str)
		{
			if (is_numeric($selection)) {
			    if($selection == 0){
			        $output[] = $str;
			    }else if($selection < 0){
			        $output = ''; 
			    }else{
				$output[] = substr($str, 0, -($selection));
			    }
			}
			else {
				$value = strstr($str, $selection, true);
				$output[] = $value !== false ? $value : '';
			}
		}
		
		return is_array($search_string) ? $output : $output[0];
	}
	
	/**
	 * Get the length of string(s)
	 * 
	 * @param string|array $values
	 * @return number|number|number[]
	 */
	public function f_Length($values)
	{
		if (!isset($values)) return 0;
		$output = array();
		if (is_array($values)) 
		{
			foreach ($values as $v) {
				$output[] = is_string($v) ? strlen($v) : 0;
			}
		}
		elseif (is_string($values)) {
			$output[] = strlen($values); 
		}
		else {
			$output[] = 0;
		}
		return is_array($values) ? $output : $output[0];
	}
	
	/**
	 * Get logarithm of argument
	 * 
	 * @param mixed $argument
	 * @return string|number
	 */
	public function f_Ln($argument)
	{
		if (!isset($argument)) return '';
		if (is_array($argument)) {
			for ($x = 0; $x < count($argument); $x++) {
				$argument[$x] = log($argument[$x]);
			}
		}
		else {
			$argument = log($argument);
		}
		return $argument;
	}

	/**
	 * Return empty string for use with migrated function code
	 * 
	 * @param string $argument
	 * @return string
	 */
	public function f_LocationGetInfo($argument)
	{
		return '';
	}	
	
	/**
	 * Returns the common logarithm (base 10) of any number greater than zero.
	 * 
	 * @param number[] $numbers
	 * @return NULL|float[]
	 */
	public function f_Log($numbers)
	{
		if (empty($numbers))
			return null;
		
		$inputs = is_array($numbers) ? $numbers : array($numbers);
		for ($x = 0; $x < count($inputs); $x++)
		{
			$inputs[$x] = log10($inputs[$x]);
		}
		return is_array($numbers) ? $inputs : $inputs[0];
	}
	
	/**
	 * Change all characters of string(s) to lower case
	 * 
	 * @param mixed $argument
	 * @return string|string[]
	 */
	public function f_LowerCase($argument)
	{
		return _LCase($argument);
	}
	

	/**
	 * Given two values returns the maximum value.
	 * Given two arrays returns an array of the maximum values from each list compared pair wise.
	 * Given an array returns the maximum value in the array.
	 * @param number[] or number
	 * @param number[] or number
	 * @return number[] or number
	 */
	public function f_Max($param1, $param2=null)
	{
	    $result = null;
	    
	    if((!isset($param1) || is_null($param1)) && (!isset($param2) || is_null($param2))){
	        //do nothing
	    }else if(!isset($param2) || is_null($param2)){
	        if(!is_array($param1)){
	            $result = $param1;
	        }else if(count($param1) < 2){
	            $result = $param1[0];
	        }else{
	            $tempres = $param1[0];
	            for($i=1; $i<count($param1); $i++){
	                if($tempres < $param1[$i]){
	                    $tempres = $param1[$i];
	                }
	            }
	            $result = $tempres;
	        }
	    }else{
	        $tempres = [];
	        
	        $temparray1 = (is_array($param1) ? $param1 : [$param1]);
	        $temparray2 = (is_array($param2) ? $param2 : [$param2]);
	        
	        $a1len = count($temparray1);
	        $a2len = count($temparray2);
	        $maxlen = max($a1len, $a2len);
	        $a1i = 0;
	        $a2i = 0;
	        
	        for($i=0; $i<$maxlen; $i++){
	            $a1i = ($i>=$a1len ? $a1i : $i);
	            $a2i = ($i>=$a2len ? $a2i : $i);
	            
	            if($temparray1[$a1i] >= $temparray2[$a2i]){
	                array_push($tempres, $temparray1[$a1i]);
	            }else if($temparray1[$a1i] < $temparray2[$a2i]){
	                array_push($tempres, $temparray2[$a2i]);
	            }
	        }
	        
	        $result = $tempres;
	    }
	    
	    return (is_array($result) && count($result) == 1 ? $result[0] : $result);
	}
	
	
	/**
	 * Send mail base on current document
	 * 
	 * @param string[] $sendTo
	 * @param string[] $copyTo
	 * @param string $blindCopyTo
	 * @param string $subject
	 * @param string[] $remark
	 * @param string $bodyFields
	 * @param array $flags
	 * @return boolean
	 */
	public function f_MailSend($sendTo, $copyTo, $blindCopyTo, $subject, $remark, $bodyFields, $flags = array())
	{
		$result = $include_link = false;
		if (!empty($flags))
		{
			$flags = is_array($flags) ? implode(':', $flags) : strval($flags);
			$include_link = false !== stripos($flags, '[INCLUDEDOCLINK]') ? true : false;
		}
		
		$document = $this->docova->getDocumentUniqueId();
		if (!empty($document))
		{
			$result = $this->docova->sendMailTo($sendTo, $copyTo, $blindCopyTo, $subject, $remark, $bodyFields, $include_link);
		}
		return $result;
	}
	
	/**
	 * Given a value, finds its position in a text list.
	 * 
	 * @param string $value
	 * @param string[] $string_list
	 * @return number
	 */
	public function f_Member($value, $string_list)
	{
		$pos = null;
		if(is_array($string_list)){
			$pos = $this->f_ArrayGetIndex($string_list, $value, 0);
		}else if($value == $string_list){
			$pos = 0;
		}
		return ($pos !== null) ? $pos + 1 : 0;
	}
	
	/**
	 * Returns a substring or array of substrings.
	 *
	 * @param string[] $search_string
	 * @param string|number $startoroffset
	 * @param string|number $endorchars
	 * @return string[]
	 */
	public function f_Middle($search_string, $startoroffset, $endorchars)
	{
		if (!isset($search_string) || !isset($startoroffset) || !isset($endorchars)){
			return '';
		}
	
		$search_string = is_array($search_string) ? $search_string : array($search_string);
		$output = array();
		foreach ($search_string as $str)
		{
			$selection = $str;
			if (is_numeric($startoroffset)) {
				$selection = substr($selection, intval($startoroffset));
			}
			else {
				$pos = strpos($selection, $startoroffset);
				if($pos !== false){
					$selection = substr($selection, $pos + strlen($startoroffset));					
				}
			}
			
			if (is_numeric($endorchars)) {
				$selection = substr($selection, intval($endorchars));
			}
			else {
				$pos = strpos($selection, $endorchars);
				if($pos !== false){
					$selection = substr($selection, 0, $pos);
				}
			}
			
			$output[] = $selection;
		}
	
		return is_array($search_string) ? $output : $output[0];
	}	
	
	/**
	 * Get minute from datetime object(s)
	 * 
	 * @param \DateTime[] $argument
	 * @return number
	 */
	public function f_Minute($argument)
	{
		if (empty($argument)) return '';
		$output = array();
		$dates = is_array($argument) ? $argument : array($argument);
		foreach ($dates as $value) {
			if ($value instanceof \DateTime) {
				$output[] = intval($value->format('i'));
			}
			else {
				$output[] = -1;
			}
		}
		return is_array($argument) ? $output : $output[0];
	}
	
	/**
	 * Get month from datetime object(s)
	 * 
	 * @param \DateTime[] $argument
	 * @return number
	 */
	public function f_Month($argument)
	{
		if (empty($argument)) return '';
		$output = array();
		$dates = is_array($argument) ? $argument : array($argument);
		foreach ($dates as $value) {
			if ($value instanceof \DateTime) {
				$output[] = intval($value->format('n'));
			}
			else {
				$output[] = -1;
			}
		}
		return is_array($argument) ? $output : $output[0];
	}
	
	/**
	 * Returns a datetime value indicating when the document was last edited and saved
	 * 
	 * @return \DateTime
	 */
	public function f_Modified($context)
	{
		if ($this->isViewScripting)
		{
			$tmpbuffer = null;
			$fieldname = "__modified";
			if ( isset($context['docvalues']) and is_array($context['docvalues']))
			{
				$tmpbuffer = $context['docvalues'];
			}

			if ($tmpbuffer != null && isset($tmpbuffer[$fieldname]))
			{
				$docval = $tmpbuffer[$fieldname];
				return $docval;
			}
			return "";
		}

		if ($this->f_IsNewDoc())
		{
			return $this->f_Now();
		}
		
		$date = $this->docova->getDocument()->getEntity()->getDateModified();
		if (empty($date))
		{
			$date = $this->docova->getDocument()->getEntity()->getDateCreated();
		}
		
		return $date;
	}
	
	/**
	 * Manipulate name formats
	 * 
	 * @param string $action
	 * @param string[] $name
	 * @return string[]
	 */
	public function f_Name($action, $name)
	{
		if (empty($name) || empty($action)) return $name;
		$tmpname = is_array($name) ? $name : array($name);
		$action = strtoupper($action);
		for ($x = 0; $x < count($tmpname); $x++)
		{
			if ($action == '[A]'){
				$tmpname[$x] = '';// not sure how this would work for different ADs?
			}
			elseif ($action == '[ABBREVIATE]') {
				if (false !== stripos($tmpname[$x], 'CN=')){
				    $nameparts = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $tmpname[$x]);
				    if(!(is_array($nameparts) && count($nameparts) > 1)){
				        $nameparts = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $tmpname[$x]);
				    }
					$stempname = "";
					for($j=0; $j< count($nameparts); $j++){
						if($nameparts[$j] != ""){
							$namepart = explode("=", $nameparts[$j]);
							if($stempname != ""){
								$stempname .= "/";
							}
							$stempname .= (count($namepart) > 1 ? $namepart[1] : $namepart[0]);
						}
					}
					$tmpname[$x] = $stempname;
				}
			}
			elseif ($action == '[ADDRESS821]') {
				if (!preg_match('/^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*$/', $tmpname[$x]))
					$tmpname[$x] = '';
			}
			elseif ($action == '[CN]') {
			    $tmpvar = $tmpname[$x];
			    $pos = stripos($tmpvar, 'CN=');
				if (false !== $pos)
				{
				    $tmpvar = substr($tmpvar, $pos + 3);
				}
				$delim ='\/';
				$nameparts = preg_split('~\\\\.(*SKIP)(*FAIL)|'.$delim.'~s', $tmpvar);
				if(count($nameparts) < 2){
				    $delim = ',';
				    $nameparts = preg_split('~\\\\.(*SKIP)(*FAIL)|'.$delim.'~s', $tmpvar);
				}
				$tmpname[$x] = $nameparts[0];
			}
			elseif ($action == '[CANONICALIZE]') {
			    if (false === stripos($tmpname[$x], 'CN=')){
			        $delim ='\/';
			        $nameparts = preg_split('~\\\\.(*SKIP)(*FAIL)|'.$delim.'~s', $tmpname[$x]);
			        if(count($nameparts) < 2){
			            $delim =',';
			            $nameparts = preg_split('~\\\\.(*SKIP)(*FAIL)|'.$delim.'~s', $tmpname[$x]);			            
			        }
			        $tempname = "";
			        for($j=0; $j<count($nameparts); $j++){
			            if($tempname !== ""){
			                $tempname .= "\/";
			            }
			            if($j==0){
			                $tempname .= "CN=";
			            }else if($j==(count($nameparts)-1)){
			                $tempname .= "O=";
			            }else{
			                $tempname .= "OU=";
						}
			            $tempname .= $nameparts[$j];
					}
			        $tmpname[$x] = $tempname;
				}
			}
			elseif ($action == '[C]') {
				if (false !== strpos($tmpname[$x], 'CN=')) {
					$tmp = strripos($tmpname[$x], '/C=');
					if (false !== $tmp) {
						$tmp = substr($tmpname[$x], $tmp + 3);
					}
					else{ 
						$tmp = '';
					}
					$tmpname[$x] = $tmp;
				}
			}
			elseif ($action == '[G]') {
				if (false !== strpos($tmpname[$x], '/'))
				{
					$tmpname[$x] = strstr($tmpname[$x], '/', true);
				}
				if (false !== strpos($tmpname[$x], ' '))
				{
					$tmpname[$x] = strstr($tmpname[$x], ' ', true);
				}
			}
			elseif ($action == '[HIERARCHYONLY]') {
				if (false !== stripos($tmpname[$x], 'CN=') && false !== strpos($tmpname[$x], '/')) {
					$tmpname[$x] = substr(strstr($tmpname[$x], '/'), 1);
				}
				else 
					$tmpname[$x] = '';
			}
			elseif ($action == '[O]') {
				if (false !== stripos($tmpname[$x], 'CN=') && false !== stripos($tmpname[$x], '/O=')) {
					$tmp = substr(strstr($tmpname[$x], '/O='), 3);
					$tmp = false !== strpos($tmp, '/') ? strstr($tmp, '/', true) : $tmp;
					$tmpname[$x] = $tmp;
				}
				elseif (false !== strpos($tmpname[$x], '/')) {
					$tmp = explode('/', $tmpname[$x]);
					$tmpname[$x] = !empty($tmp[1]) ? $tmp[1] : '';
				}
				else 
					$tmpname[$x] = '';
			}
			elseif ($action == '[OU1]') {
				if (false !== stripos($tmpname[$x], 'CN=')) {
					if (false !== stripos($tmpname[$x], 'OU=')) {
						$tmp = substr(stristr($tmpname[$x], 'OU='), 3);
						$tmp = false !== strpos($tmp, '/') ? strstr($tmp, '/', true) : $tmp;
						$tmpname[$x] = $tmp;
					}
					else {
						$tmpname[$x] = '';
					}
				}
				elseif (false !== strpos($tmpname[$x], '/')) {
					$tmp = explode('/', $tmpname[$x]);
					$tmpname[$x] = !empty($tmp[2]) ? $tmp[2] : '';
				}
				else {
					$tmpname[$x] = '';
				}
			}
			elseif ($action == '[OU2]') {
				if (false !== stripos($tmpname[$x], 'CN=')) {
					if (false !== stripos($tmpname, 'OU=')) {
						$tmp = substr(stristr($tmpname[$x], 'OU='), 3);
						$ou_list = preg_split('/OU\=/i', $tmp);
						if (!empty($ou_list[1])) {
							$tmp = false !== strpos($ou_list[1], '/') ? strstr($ou_list[1], '/', true) : $ou_list[1];
							$tmpname[$x] = $tmp;
						}
						else {
							$tmpname[$x] = '';
						}
					}
					else {
						$tmpname[$x] = '';
					}
				}
				elseif (false !== strpos($tmpname[$x], '/')) {
					$tmp = explode('/', $tmpname[$x]);
					$tmpname[$x] = !empty($tmp[3]) ? $tmp[3] : '';
				}
				else {
					$tmpname[$x] = '';
				}
			}
			elseif ($action == '[OU3]') {
				if (false !== stripos($tmpname[$x], 'CN=')) {
					if (false !== stripos($tmpname, 'OU=')) {
						$tmp = substr(stristr($tmpname[$x], 'OU='), 3);
						$ou_list = preg_split('/OU\=/i', $tmp);
						if (!empty($ou_list[2])) {
							$tmp = false !== strpos($ou_list[2], '/') ? strstr($ou_list[2], '/', true) : $ou_list[2];
							$tmpname[$x] = $tmp;
						}
						else {
							$tmpname[$x] = '';
						}
					}
					else {
						$tmpname[$x] = '';
					}
				}
				elseif (false !== strpos($tmpname[$x], '/')) {
					$tmp = explode('/', $tmpname[$x]);
					$tmpname[$x] = !empty($tmp[4]) ? $tmp[4] : '';
				}
				else {
					$tmpname[$x] = '';
				}
			}
			elseif ($action == '[OU4]') {
				if (false !== stripos($tmpname[$x], 'CN=')) {
					if (false !== stripos($tmpname, 'OU=')) {
						$tmp = substr(stristr($tmpname[$x], 'OU='), 3);
						$ou_list = preg_split('/OU\=/i', $tmp);
						if (!empty($ou_list[3])) {
							$tmp = false !== strpos($ou_list[3], '/') ? strstr($ou_list[3], '/', true) : $ou_list[3];
							$tmpname[$x] = $tmp;
						}
						else {
							$tmpname[$x] = '';
						}
					}
					else {
						$tmpname[$x] = '';
					}
				}
				elseif (false !== strpos($tmpname[$x], '/')) {
					$tmp = explode('/', $tmpname[$x]);
					$tmpname[$x] = !empty($tmp[5]) ? $tmp[5] : '';
				}
				else {
					$tmpname[$x] = '';
				}
			}
			elseif ($action == '[S]') {
				if (false !== strpos($tmpname[$x], '/'))
				{
					$tmpname[$x] = strstr($tmpname[$x], '/', true);
				}
				if (false !== strpos($tmpname[$x], ' '))
				{
					$tmpname[$x] = trim(strstr($tmpname[$x], ' '));
				}
			}
		}
		
		return is_array($name) ? $tmpname : $tmpname[0];
	}
	
	/**
	 * Generate new line
	 * 
	 * @return string
	 */
	public function f_NewLine()
	{
		return "\n\r";
	}
	
	/**
	 * Return false
	 * 
	 * @return boolean
	 */
	public function f_No()
	{
		return false;
	}
	
	/**
	 * Get null
	 * 
	 * @return NULL
	 */
	public function f_Nothing()
	{
		return null;
	}
	
	/**
	 * Datetime object base on current time
	 * 
	 * @return \DateTime
	 */
	public function f_Now()
	{
	    $date = new \DateTime();
	    if(!$this->isViewScripting){
	        global $docova;
	        if (empty($docova) || !($docova instanceof Docova)) {
	            $docova = $this->docova->getDocovaObject();
	        }	        
	        $timezone = _GetUserTimeZone();
	        if(!empty($timezone)){
	            $date->setTimeZone(new \DateTimeZone($timezone));
	        }
	    }
		return $date;
	}
	
	/**
	 * Get Pi number
	 * 
	 * @return number
	 */
	public function f_Pi()
	{
		return pi();
	}
	
	/**
	 * Executes a PHP/Twig command.
	 * 
	 * @param string $action
	 * @param string $param1
	 * @param string $param2
	 * @param string $param3
	 * @param string $param4
	 * @param string $param5
	 */
	public function f_PostedCommand($action, $param1, $param2, $param3, $param4, $param5)
	{
		return $this->f_Command($action, $param1, $param2, $param3, $param4, $param5);
	}
	
	/**
	 * Exponential exp
	 * 
	 * @param mixed $base
	 * @param string $exponent
	 * @return number[]
	 */
	public function f_Power($base, $exponent)
	{
		if (!is_string($exponent) && !is_numeric($exponent)) return false;
		if (is_array($base)) 
		{
			$output = array();
			for ($x = 0; $x < count($base); $x++) {
				$output[] = pow($base[$x], $exponent);
			}
			return $output;
		}
		else {
			return pow($base, $exponent);
		}
	}
	
	/**
	 * Stub function for use in migrated code
	 *
	 * @return string
	 */
	public function f_Prompt()
	{
        	return "";
	}		
	
	/**
	 * Upercase first characters of each words in string(s)
	 * 
	 * @param string[] $argument
	 * @return string[]
	 */
	public function f_ProperCase($argument)
	{
		if (empty($argument)) return $argument;
		$strings = is_array($argument) ? $argument : array($argument);
		$output = array();
		for ($x = 0; $x < count($strings); $x++)
		{
			$output[] = ucwords(strtolower($strings[$x]));
		}
		return is_array($argument) ? $output : $output[0];
	}
	
	/**
	 * Get random number
	 * 
	 * @return number
	 */
	public function f_Random()
	{
		return mt_rand();
	}
	
	/**
	 * Repeat string(s) multiple times
	 * 
	 * @param string[] $argument
	 * @param number $count
	 * @param number $maxchars
	 * @return string[]
	 */
	public function f_Repeat($argument, $count, $maxchars = null)
	{
		if (!isset($argument) || empty($count)) return $argument;
		$strings = is_array($argument) ? $argument : array($argument);
		$output = array();
		for ($x = 0; $x < count($strings); $x++)
		{
			$tmp = str_repeat($strings[$x], $count);
			if (!empty($maxchars)) {
				$tmp = substr($tmp, 0, $maxchars);
			}
			$output[] = $tmp;
			$tmp = null;
		}
		return is_array($argument) ? $output : $output[0];
	}
	
	/**
	 * Search and replace array of string
	 * 
	 * @param string[] $source_list
	 * @param string[] $from_list
	 * @param string[] $to_list
	 * @return string[]
	 */
	public function f_Replace($source_list, $from_list, $to_list)
	{
		if (!isset($source_list) || !isset($from_list) || !isset($to_list)) return $source_list;
		$source_list = is_array($source_list) ? $source_list : array($source_list);
		$from_list = is_array($from_list) ? $from_list : array($from_list);
		$to_list = is_array($to_list) ? $to_list : array($to_list);
		for ($x = 0; $x < count($source_list); $x++)
		{
			$pos = array_search($source_list[$x],$from_list);
			if ($pos !== false && count($to_list) > $pos) {				
				$source_list[$x] = $to_list[$pos];
			}else if ($pos !== false && count($to_list) <= $pos) {	
			    $source_list[$x] = '';
			}
		}
		return $source_list;
	}
	
	/**
	 * Search replace specific words/phrases in string
	 * 
	 * @param string[] $source_list
	 * @param string[] $from_list
	 * @param string[] $to_list
	 * @return string[]
	 */
	public function f_ReplaceSubstring($source_list, $from_list, $to_list = "")
	{
		if (!isset($source_list) || !isset($from_list) ) return $source_list;
		$source_list = is_array($source_list) ? $source_list : array($source_list);
		$from_list = is_array($from_list) ? $from_list : array($from_list);
		$to_list = is_array($to_list) ? $to_list : array($to_list);

		$source_list = str_replace($from_list, $to_list, $source_list);
/* 		for ($x = 0; $x < count($source_list); $x++)
		{
			$source_list[$x] = str_replace($from_list, $to_list, $source_list);
		}
 */		return $source_list;
	}

	
	
	/**
	 * Extracts a specified number of the rightmost characters in a string.
	 * 
	 * @param string[] $search_string
	 * @param string|number $selection
	 * @param number $compmethod
	 * @return string[]
	 */
	public function f_Right($search_string, $selection, $compmethod = 0)
	{
		return _Right($search_string, $selection, $compmethod);
	}
	
	/**
	 * Returns the rightmost characters in a string.
	 * 
	 * @param string[] $search_string
	 * @param string|number $selection
	 * @return string[]
	 */
	public function f_RightBack($search_string, $selection)
	{
	    if (!isset($search_string) || !isset($selection)){
			return '';
	    }
	    if (is_numeric($selection)) {
	        $selection = intval($selection);
	    }
		$search_string = is_array($search_string) ? $search_string : array($search_string);
		$output = array();
		foreach ($search_string as $str)
		{
			if (is_numeric($selection)) {
			    if($selection == 0){
			        $output[] = $str;
			    }else if($selection < 0){
			        $output[] = '';
			    }else{
    				$output[] = substr($str, $selection);
			    }
			}
			else {
				$pos = strrpos($str, $selection);
				$output[] = $pos !== false ? trim(substr($str, $pos), $selection) : '';
			}
		}
	
		return is_array($search_string) ? $output : $output[0];
	}
	
	/**
	 * Round down/up the value base on the factor
	 * 
	 * @param float[] $argument
	 * @param float $factor
	 * @return float[]
	 */
	public function f_Round($argument, $factor = 1)
	{
		if (empty($argument)) return $argument;
		$factor = empty($factor) ? 1 : $factor;
		$digits = 0;
		if(floor($factor) !== $factor){
			$tmparray = explode(".", (string)$factor);
			if(!empty($tmparray) && count($tmparray) > 1){
				$digits = strlen($tmparray[1]);
			}
		}

		$values = is_array($argument) ? $argument : array($argument);
		for ($x = 0; $x < count($values); $x++)
		{
			$values[$x] = $this->f_Trunc(round($values[$x] / $factor) * $factor, $digits);						
		}
		return is_array($argument) ? $values : $values[0];
	}


	/**
	 *Returns the value that appears in the number position. If the number is greater than the number of values, @Select returns the last value in the list. 
	 *If the value in the number position is a list, returns the entire list contained within the value.
	 * 
	 * Parameters
	 * Number. The position of the value you want to retrieve.
	 * values 
	 * Any number of values, separated by semicolons. A value may be a number, text, time-date, or a number list, text list, or time-date list.

	 */
	public function f_Select($number, $values)
	{
		$retval = "";
		$list = !is_array($values) ? array($values) : $values;
		$number = intval($number);
		if ( $number > count($list) )
			$retval =  $list[count($list)];
		else{
			if ( isset($list[$number-1]))
				$retval =  $list[$number-1];
		}

		if ( strstr ( $retval , ":") != false ){
			return explode(":", $retval);
		}

		return $retval;
	}
	
	/**
	 * Get seconds of datetime object(s)
	 * 
	 * @param \DateTime[] $argument
	 * @return number[]
	 */
	public function f_Second($argument)
	{
		if (empty($argument)) return '';
		$output = array();
		$dates = is_array($argument) ? $argument : array($argument);
		foreach ($dates as $value) {
			if ($value instanceof \DateTime) {
				$output[] = intval($value->format('s'));
			}
			else {
				$output[] = -1;
			}
		}
		return is_array($argument) ? $output : $output[0];
	}
	
	
	/**
	 * Returns the name of the current server
	 *
	 * @param string $value
	 */
	public function f_ServerName()
	{
	   $result = "";
	   
	   $result = $this->docova->getGlobalSettings()->getRunningServer();
	   
	   return $result;
	}
	
	
	
	/**
	 * Set document field value
	 * 
	 * @param string $document
	 * @param string $field
	 * @param string $value
	 */
	public function f_SetDocField($document = null, $field, $value)
	{
		if (empty($document) && !$this->f_IsNewDoc()) {
			$this->docova->setFieldValue($field, $value);
		}elseif (empty($document) && $this->f_IsNewDoc()) {
		    $this->f_AddComputedToBuffer($field, $value);
		}elseif (!empty($document))
		{
			$current_doc = $this->docova->getDocumentUniqueId();
			$tmp_doc = $this->docova;
			$tmp_doc->setDocument($document);
			$tmp_doc->setFieldValue($field, $value);
			$tmp_doc = null;
			$this->docova->setDocument($current_doc);
		}
	}
	
	/**
	 * Sets an environment variable stored in the user's session.
	 * 
	 * @param string $varname
	 * @param mixed $value
	 * @return boolean
	 */
	public function f_SetEnvironment($varname, $value)
	{
		if (empty($varname) || empty($value))
			return false;
		
		$session = $this->docova->getDocovaSessionObject();
		$session->setEnvironmentVar($varname, $value);
		return true;
	}
	
	/**
	 * Adds a set of numbers or number lists
	 * 
	 * @param number[] $argument
	 * @return number
	 */
	public function f_Sum($argument)
	{
		if (empty($argument))
			return 0;
		
		$sum = 0;
		foreach ($argument as $value) {
			if (is_array($value)) {
				$sum += array_sum($value);
			}
			else {
				$sum += $value;
			}
		}
		return $sum;
	}

	/**
	 * Truncate a number to the specified number of decimal places
	 *
	 * @param float[] $argument
	 * @param integer $digits
	 * @return float[]
	 */
	public function f_Trunc($argument, $digits = 0)
	{
		if (empty($argument)) return $argument;
		$digits = empty($digits) ? 0 : $digits;
	
		$values = is_array($argument) ? $argument : array($argument);
		for ($x = 0; $x < count($values); $x++)
		{
			
			$numS = (string)$values[$x];
			$decPos = strpos($numS, ".");
			$substrLength = $decPos == false ? strlen($numS) : 1 + $decPos + $digits;
			$trimmedresult = substr($numS, 0, $substrLength);
			$finalresult = !is_numeric($trimmedresult) ? 0 : $trimmedresult;
			
			$values[$x] = floatval($finalresult);
		}
		return is_array($argument) ? $values : $values[0];
	}	
	
	
	/**
	 * Returns a text list containing Username, Hierarchy Name, Abbreviated name, Display name, User roles and groups
	 *  
	 * @return array 
	 */
	public function f_UserNamesList()
	{
		$output = array();
		$current_uesr = $this->docova->getDocovaSessionObject()->getCurrentUser();
		if (empty($current_uesr))
			return $output;
		
		$output[] = $current_uesr->getUsername();
		$output[] = $current_uesr->getUserNameDn();
		$output[] = $current_uesr->getUserNameDnAbbreviated();
		$output[] = $current_uesr->getUserProfile()->getDisplayName();
		$groups = $current_uesr->getUserRoles();
		foreach ($groups as $g)
		{
			$output[] = $g->getDisplayName();
		}
		
		$output = array_unique($output);
		return $output;
	}
	
	/**
	 * Set current document field value
	 * 
	 * @param string $field
	 * @param string $value
	 */
	public function f_SetField($field, $value)
	{
		$this->f_SetDocField(null, $field, $value);
	}
	
	/**
	 * Set profile document field value
	 * 
	 * @param string $profilename
	 * @param string $fieldname
	 * @param string $value
	 * @param mixed $key
	 * @return boolean
	 */
	public function f_SetProfileField($profilename, $fieldname, $value, $key = null)
	{
		if (empty($profilename) || empty($fieldname) || empty($value)) return false;
		if (is_numeric($value)) {
			$value = floatval($value);
		}
		else {
			try {
				$v = new \DateTime($value);
				if (!empty($v)) {
					$value = $v;
				}
			}
			catch (\Exception $e) { }
		}
		$application = $this->docova->getCurrentApplication();
		$application->setProfileFields($profilename, array($fieldname => $value), $key);
//		$this->docova->setProfileField($profilename, $fieldname, $value, $key);
	}
	
	/**
	 * Is positive or negative or zero
	 * 
	 * @param mixed $argument
	 * @return number[]
	 */
	public function f_Sign($argument)
	{
		if (empty($argument)) return 0;
		if (is_array($argument)) 
		{
			for ($x = 0; $x < count($argument); $x++) {
				$value = floatval($argument[$x]);
				$argument[$x] = $value > 0 ? 1 : ($value < 0 ? -1 : 0);
			}
		}
		else {
			$value = floatval($argument);
			$argument = $value > 0 ? 1 : ($value < 0 ? -1 : 0);
		}
		return $argument;
	}
	
	/**
	 * Get sin for argument(s)
	 * 
	 * @param mixed $argument
	 * @return mixed
	 */
	public function f_Sin($argument)
	{
		if (is_array($argument)) {
			if (empty($argument)) { return array();	}
			for ($x = 0; $x < count($argument); $x++) {
				$value = floatval($argument[$x]);
				$argument[$x] = sin($value) ? sin($value) : '';
			}
		}
		else {
			if (trim($argument) === '') { return ''; }
			$argument = floatval($argument);
			$argument = sin($argument) ? sin($argument) : '';
		}
		return $argument;
	}
	
	/**
	 * Sorts a list.
	 * 
	 * @param array $list
	 * @param string $order
	 * @param string $customsort
	 * @return mixed
	 */
	public function f_Sort($list, $order=null, $customsort = null)
	{
		if (empty($list))
			return "";
		
		$inputs = is_array($list) ? $list : array($list);
		$order = empty($order) ? '[ASCENDING]:[CASESENSITIVE]:[ACCENTSENSITIVE]:[PITCHSENSITIVE]': $order;
		$insensitive = false !== stripos($order, '[CASEINSENSITIVE]') ? true : false;

		if (false !== stripos($order, '[ASCENDING]'))
			usort($inputs, function($a, $b) use ($insensitive) {
				return $this->sortDataAsc($a, $b, $insensitive);
				
			});
		elseif (false !== stripos($order, '[DESCENDING]'))
			usort($inputs, function($a, $b) use ($insensitive) {
				return $this->sortDataDesc($a, $b, $insensitive);
			});
		
		return $inputs;
	}
	
	/**
	 * Get square root
	 * 
	 * @param mixed $argument
	 * @return number[]
	 */
	public function f_Sqrt($argument)
	{
		if (empty($argument)) return $argument;
		if (is_array($argument))
		{
			for ($x = 0; $x < count($argument); $x++) {
				$value = floatval($argument[$x]);
				$argument[$x] = sqrt($value);
			}
		}
		else {
			$value = floatval($argument);
			$argument = sqrt($value);
		}
		return $argument;
	}
	
	/**
	 * Get a subset list from a list
	 * 
	 * @param array $item_list
	 * @param number $item_number
	 * @return array|string
	 */
	public function f_Subset($item_list, $item_number)
	{
		if (empty($item_number)) return 'ERROR: The second argument to f_Subset must not be zero';
		if (!is_array($item_list)) return $item_list;
		$c = count($item_list);
		$item_number = $c < abs($item_number) ? $c*($item_number/abs($item_number)) : $item_number;
		$offset = $item_number > 0 ? 0 : $c + $item_number;
		$length = $item_number > 0 ? $item_number : null;
		$retarray = array_slice($item_list, $offset, $length);
		if ( count($retarray) == 1 ){
			return $retarray[0];
		}else{
			return $retarray;
		}
	}
	
	/**
	 * Return true
	 * 
	 * @return boolean
	 */
	public function f_Success()
	{
		return true;
	}
	
	/**
	 * Get tangent
	 * 
	 * @param mixed $argument
	 * @return number[]
	 */
	public function f_Tan($argument)
	{
		if (is_array($argument)) {
			if (empty($argument)) { return array();	}
			for ($x = 0; $x < count($argument); $x++) {
				$value = floatval($argument[$x]);
				$argument[$x] = tan($value) ? tan($value) : '';
			}
		}
		else {
			if (trim($argument) === '') { return ''; }
			$argument = floatval($argument);
			$argument = tan($argument) ? tan($argument) : '';
		}
		return $argument;
	}
	
	/**
	 * Convert value(s) to string
	 * 
	 * @param mixed $argument
	 * @param string $formatstring
	 * @return string[]
	 */
	public function f_Text($argument, $formatstring = null)
	{
	    global $docova;
	    if (empty($docova) || !($docova instanceof Docova)) {
	        $docova = $this->docova->getDocovaObject();
	    }
		return _Text($argument, $formatstring);
	}

	/**
	 * Convert text to date time object
	 * 
	 * @param string|[] $argument
	 * @return string|\DateTime
	 */
	public function f_TextToTime($argument)
	{
	    if (empty($argument)) return '';
	    
	    $timezone = '';
	    if(!$this->isViewScripting){
	        global $docova;
	        if (empty($docova) || !($docova instanceof Docova)) {
	            $docova = $this->docova->getDocovaObject();
	        }
	        $timezone = _GetUserTimeZone();
	    }
	    
	    if (is_array($argument))
	    {
	        for ($x = 0; $x < count($argument); $x++) {
	            try {
	                if(!empty($timezone)){
	                    $dt = new \DateTime($argument[$x], new \DateTimeZone($timezone));
	                }else{
	                    $dt = new \DateTime($argument[$x]);
	                }
	            }
	            catch (\Exception $e) {
	                if (false !== strpos($argument[$x], '/')) {
	                    $argument[$x] = str_replace('/', '-', $argument[$x]);
	                    try {
	                        if(!empty($timezone)){
	                            $dt = new \DateTime($argument[$x], new \DateTimeZone($timezone));
	                        }else{
	                            $dt = new \DateTime($argument[$x]);
	                        }
	                    }
	                    catch (\Exception $e) {
	                        $dt = '';
	                    }
	                }
	            }
	            $argument[$x] = $dt;
	        }
	    }
	    else {
	        try {
	            if(!empty($timezone)){
	                $dt = new \DateTime($argument, new \DateTimeZone($timezone));
	            }else{
	                $dt = new \DateTime($argument);
	            }
	        }
	        catch (\Exception $e) {
	            if (false !== strpos($argument, '/')) {
	                $argument = str_replace('/', '-', $argument);
	                try {
	                    if(!empty($timezone)){
	                        $dt = new \DateTime($argument, new \DateTimeZone($timezone));
	                    }else{
	                        $dt = new \DateTime($argument);
	                    }
	                }
	                catch (\Exception $e) {
	                    $dt = '';
	                }
	            }
	        }
	        $argument = $dt;
	    }
	    
	    return $argument;
	}
	
	/**
	 * Convert text values to numbers
	 * 
	 * @param string[] $argument
	 * @return number[]
	 */
	public function f_TextToNumber($argument)
	{
		if (empty($argument)) return 0;
		if (is_array($argument)) 
		{
			for ($x = 0; $x < count($argument); $x++) {
				if (is_string($argument[$x])) {
					settype($argument[$x], "float");
				}
				else {
					$argument[$x] = $this->f_Error();
				}
			}
		}
		else {
			if (is_string($argument)) {
				settype($argument, "float");
			}
			else {
				$argument = $this->f_Error();
			}
		}
		return $argument;
	}
	
	/**
	 * Generate a truncated datetime object or time string
	 *
	 * @param array|string $datetimeorhour
	 * @param string $monthorminute
	 * @param string $dayorsecond
	 * @param string $hour
	 * @param string $minute
	 * @param string $sec
	 * @return \DateTime[]|string
	 */
	function f_Time($timedateoryearorhour, $monthorminute = null, $dayorsecond = null, $hour = null, $minute = null, $sec = null) {
	    if(empty($timedateoryearorhour)) {return '';}
	    
	    $timezone = '';
	    if(!$this->isViewScripting){
	        global $docova;
	        if (empty($docova) || !($docova instanceof Docova)) {
	            $docova = $this->docova->getDocovaObject();
	        }
	        $timezone = _GetUserTimeZone();
	    }
	    
	    $isarray = false;
	    $output = array();
	    if(!is_null($hour) && !is_null($minute) && !is_null($sec))
	    {
	        $format = 'm-d-Y H:i:s';
	        $textdate = "{$monthorminute}-{$dayorsecond}-{$timedateoryearorhour} {$hour}:".(strlen((string)$minute)<2 ? '0' : '')."{$minute}:".(strlen((string)$sec)<2 ? '0' : '')."{$sec}";
	        if(!empty($timezone)){
	            $tempdate = \DateTime::createFromFormat($format, $textdate, new \DateTimeZone($timezone));
	        }else{
	            $tempdate = \DateTime::createFromFormat($format, $textdate);
	        }
	        $output[] = ($tempdate === false ? '' : $tempdate);	               
	    }elseif(!is_null($monthorminute) && !is_null($dayorsecond)){
	        $format = 'm-d-Y H:i:s';
	        $textdate = "1-1-1 {$timedateoryearorhour}:".(strlen((string)$monthorminute)<2 ? '0' : '')."{$monthorminute}:".(strlen((string)$dayorsecond)<2 ? '0' : '')."{$dayorsecond}";
	        $tempdate = \DateTime::createFromFormat($format, $textdate);
	        $output[] = ($tempdate === false ? '' :  $tempdate->format("H:i:s"));
	    }else{
	        $isarray = is_array($timedateoryearorhour);
	        $templist = ($isarray ? $timedateoryearorhour : array($timedateoryearorhour));
	        foreach($templist as $value){
	            if($value instanceof \DateTime)
	            {
	                $value->setDate(1, 1, 1);
	                $value = $value->format("H:i:s");                
	                $output[] = $value;
	            }
	            else if(is_string($value)){
	                try {
	                    $date = new \DateTime($value);
	                    if($date === false){
	                        $date = '';
	                    }else{
    	                    $date->setDate(1, 1, 1);
	                       $date = $date->format("H:i:s");
	                    }
	                    $output[] = $date;
	                }
	                catch (\Exception $e) {
	                    $output[] = '';
	                }
	                
	            }
	            else{
	                $output[] = '';
	            }
	            
	        }
	    }
	    
	    return ($isarray ? $output : $output[0]);
	    
	}
	
	
	/**
	 * Today datetime object
	 * 
	 * @return \DateTime
	 */
	public function f_Today()
	{
		return $this->f_Date($this->f_Now());
	}
	
	/**
	 * Tomorrow datetime object
	 * 
	 * @return \DateTime
	 */
	public function f_Tomorrow()
	{
		$date = $this->f_Today();
		$date->modify('+1 day');
		return $date;
	}
	
	/**
	 * Convert values to numbers
	 * 
	 * @param mixed $argument
	 * @return number[]
	 */
	public function f_ToNumber($argument)
	{
		if (empty($argument)) return 0;
		if (is_array($argument)) 
		{
			for ($x = 0; $x < count($argument); $x++) {
				if (!is_numeric($argument[$x])) {
					$argument[$x] = 'The value cannot be converted to a Number.';
				}
				else {
					$argument[$x] = intval($argument[$x]);
				}
			}
		}
		else {
			if (!is_numeric($argument)) {
				$argument = 'The value cannot be converted to a Number.';
			}
			else {
				$argument = intval($argument);
			}
		}
		return $argument;
	}
	
	/**
	 * Trim spaces in string(s)
	 * 
	 * @param string[] $argument
	 * @return string[]
	 */
	public function f_Trim($argument)
	{
		return _Trim($argument);
	}
	
	/**
	 * Return true
	 * 
	 * @return boolean
	 */
	public function f_True()
	{
		return true;
	}
	
	/**
	 * Returns the upper bound for one dimension of an array.
	 * 
	 * @param mixed[] $array
	 * @return boolean|mixed
	 */
	public function f_UBound($array)
	{
		return $this->docova->_UBound($array);
	}
	
	/**
	 * Retrun null
	 * 
	 * @return NULL
	 */
	public function f_Unavailable()
	{
		return null;
	}
	
	public function f_Unique($array = null)
	{
		if (is_null($array))
		{
		    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4).'-'.substr(md5(uniqid(mt_rand(), true)), 0, 4).'-'.substr(md5(uniqid(mt_rand(), true)), 0, 4));
		}
		else {
			if ( ! is_array($array))
				$array = [$array];
			return $this->f_ArrayUnique($array, 0);
		}
	}
	
	/**
	 * Change all string(s) characters to upercase
	 * 
	 * @param string[] $argument
	 * @return string|string[]
	 */
	public function f_UpperCase($argument)
	{
		/*if (!is_array($argument) && !is_string($argument)) return '';
		if (empty($argument)) return $argument;
		if (is_array($argument))
		{
			for ($x = 0; $x < count($argument); $x++) {
				$argument[$x] = strtoupper($argument[$x]);
			}
		}
		else {
			$argument = strtoupper($argument);
		}
		return $argument;*/
		return _UpperCase($argument);
	}
	
	/**
	 * Decodes a URL string into regular text.
	 * 
	 * @param string $type
	 * @param string[] $string_value
	 * @return string[]
	 */
	public function f_URLDecode($type, $string_value)
	{
		if (empty($string_value))
			return '';
		
		$result = is_array($string_value) ? $string_value : array($string_value);
		for ($x = 0; $x < count($result); $x++)
		{
			$result[$x] = urldecode($result[$x]);
		}
		return $result;
	}
	
	/**
	 * Encodes a string into a URL-safe format.
	 * 
	 * @param string $format
	 * @param string[] $string_value
	 * @return string[]
	 */
	public function f_URLEncode($format, $string_value)
	{
		if (empty($string_value))
			return '';
		
		$result = is_array($string_value) ? $string_value : array($string_value);
		for ($x = 0; $x < count($result); $x++)
		{
			$result = urlencode($result[$x]);
		}
		return $result;
	}
	
	
	/**
	 * Returns access level of the current user to a specified database
	 *
	 * @param string[] $appinfo
	 * @param string $accesstype
	 * @return mixed
	 */
	public function f_UserAccess($appinfo, $accesstype = null)
	{	    
	    $output = array();	    
	    
	    $curr_app = $this->docova->getCurrentApplication();
	    $target_app = $curr_app;
	    
	    if(!empty($appinfo) && is_array($appinfo) && count($appinfo) > 1){	        
	        if($curr_app->appID != $appinfo[1]){
	            $target_app = $this->docova->getDocovaSessionObject()->getDatabase($appinfo[0], $appinfo[1], false);
	            if(empty($target_app->appID)){
	                $target_app = null;
	            }
	        }
	    }

	    $accesslevel = 0;
	    $createdocuments = 0;
	    $deletedocuments = 0;
	    $createpersonalagents = 0;
	    $createpersonalfoldersandviews = 0;
	    $createagents = 0;
	    $createsharedfoldersandviews = 0;
	    $readpublicdocuments = 0;
	    $writepublicdocuments = 0;
	    $replicateorcopydocuments = 1;
	    
	    if(!empty($target_app)){
	        $appacl = $target_app->getAcl();
	        
	        if($appacl->isSuperAdmin() || $appacl->isManager()){
	            $accesslevel = 6;
	        }else if($appacl->isDesigner()){
	            $accesslevel = 5;
	        }else if($appacl->isEditor()){
	            $accesslevel = 4;
	        }else if($appacl->isAuthor()){
	            $accesslevel = 3;
	        }else if($appacl->isReader()){
	            $accesslevel = 2;
	        }
	        
	        if($accesslevel > 2 && $appacl->canCreateDocument()){
	            $createdocuments = 1;
	        }
	        
	        if($accesslevel > 2 && $appacl->canDeleteDocument()){
	            $deletedocuments = 1;
	        }
	        
	        if($accesslevel > 4){
	            $createpersonalagents = 1;
	            $createagents = 1;
	            $createsharedfoldersandviews = 1;	            
	        }
	        
	        if($accesslevel > 1){
	            $createpersonalfoldersandviews = 1;	  
	            $replicateorcopydocuments = 1;
	            $readpublicdocuments = 1;
	        }	        
	    }
	    
	    
	    if (empty($accesstype)){
	       $output = [$accesslevel, $createdocuments, $deletedocuments, $createpersonalagents, $createpersonalfoldersandviews, $createagents, $createsharedfoldersandviews, $readpublicdocuments, $writepublicdocuments];    
	    }else{
	        switch (strtoupper($accesstype)){
	            case "ACCESSLEVEL":
	            case "[ACCESSLEVEL]":
	              $output = $accesslevel;
	              break;
	            case "CREATEDOCUMENTS":
	            case "[CREATEDOCUMENTS]":
	                $output = $createdocuments;
	                break;
	            case "DELETEDOCUMENTS":
	            case "[DELETEDOCUMENTS]":
	                $output = $deletedocuments;
	                break;
	            case "CREATEPERSONALAGENTS":
	            case "[CREATEPERSONALAGENTS]":
	                $output = $createpersonalagents;
	                break;
	            case "CREATEPERSONALFOLDERSANDVIEWS":
	            case "[CREATEPERSONALFOLDERSANDVIEWS]":
	                $output = $createpersonalfoldersandviews;
	                break;
	            case "CREATELOTUSSCRIPTJAVAAGENTS":
	            case "[CREATELOTUSSCRIPTJAVAAGENTS]":
	                $output = $createagents;
	                break;
	            case "CREATESHAREDFOLDERSANDVIEWS":
	            case "[CREATESHAREDFOLDERSANDVIEWS]":
	                $output = $createsharedfoldersandviews;
	                break;
	            case "READPUBLICDOCUMENTS":
	            case "[READPUBLICDOCUMENTS]":
	                $output = $readpublicdocuments;
	                break;
	            case "WRITEPUBLICDOCUMENTS":
	            case "[WRITEPUBLICDOCUMENTS]":
	                $output = $writepublicdocuments;
	                break;
	            case "REPLICATEORCOPYDOCUMENTS":
	            case "[REPLICATEORCOPYDOCUMENTS]":
	                $output = $replicateorcopydocuments;
	                break;
	            default:
	                $output = 0;
	        }
	    }
	    
	    return $output;
	}
	
	
	/**
	 * Get username dn
	 * 
	 * @return string
	 */
	public function f_UserName()
	{
		$current_user = $this->docova->getDocovaSessionObject()->getCurrentUser();
		if (!empty($current_user))
		{
			return $current_user->getUserNameDn();
		}
		return '';
	}
	
	/**
	 * Get language corresponding to user's name
	 * Stub function used for accomodating migrated code
	 * @return string
	 */
	public function f_UserNameLanguage()
	{
	    return f_LanguagePreference();
	}
	
	/**
	 * Returns a list of roles that the current user has.
	 * 
	 * @return array
	 */
	public function f_UserRoles()
	{
		$current_user = $this->docova->getDocovaSessionObject()->getCurrentUser();
		//$rolesarr= $this->docova->getDocovaSessionObject()->getUserRoles();
	
		
		if (($this->docova->getCurrentApplication())) {
			$rolesarr = $this->docova->getAclRoles($current_user);
		}
		else {
			$rolesarr = $current_user->getRolesDisplay();
		}
		$index = 0;
		foreach ( $rolesarr as $role){
			$rolesarr[$index] = "[".$role."]";
			$index++;
		}
		return $rolesarr;
	}
	
	/**
	 * Get username dn abbreviated
	 * 
	 * @return string
	 */
	public function f_V3UserName()
	{
		$current_user = $this->docova->getDocovaSessionObject()->getCurrentUser();
		if (!empty($current_user))
		{
			return $current_user->getUserNameDnAbbreviated();
		}
		return '';
	}
	
	/**
	 * Returns access level of the current user to a specified database
	 *
	 * @param string[] $appinfo
	 * @return string[]
	 */
	public function f_V4UserAccess($appinfo)
	{
	    $output = array();
	    
	    $current_user = $this->docova->getDocovaSessionObject()->getCurrentUser();
	    $curr_app = $this->docova->getCurrentApplication();
	    $target_app = $curr_app;
	    
	    if(!empty($appinfo) && is_array($appinfo) && count($appinfo) > 1){
	        if($curr_app->appID != $appinfo[1]){
	            $target_app = $this->docova->getDocovaSessionObject()->getDatabase($appinfo[0], $appinfo[1], false);
	            if(empty($taret_app->appID)){
	                $target_app = null;
	            }
	        }
	    }
	    
	    $accesslevel = 0;
	    $createdocuments = 0;
	    $deletedocuments = 0;
	    
	    if(!empty($target_app)){
	        $appacl = $target_app->getAcl();
	        
	        if($appacl->isSuperAdmin() || $appacl->isManager()){
	            $accesslevel = 6;
	        }else if($appacl->isDesigner()){
	            $accesslevel = 5;
	        }else if($appacl->isEditor()){
	            $accesslevel = 4;
	        }else if($appacl->isAuthor()){
	            $accesslevel = 3;
	        }else if($appacl->isReader()){
	            $accesslevel = 2;
	        }
	        
	        if($accesslevel > 2 && $appacl->canCreateDocument()){
	            $createdocuments = 1;
	        }
	        
	        if($accesslevel > 2 && $appacl->canDeleteDocument()){
	            $deletedocuments = 1;
	        }
	    }
	    
	    
        $output = [$accesslevel, $createdocuments, $deletedocuments];	   
	    
	    return $output;
	}
	
	
	/**
	 * Returns DOCOVA version (not really useful but need to define it to avoid any error in migrated apps)
	 * 
	 * @return number (always 500 which indicates 5.0.0)
	 */
	public function f_Version()
	{
		return 500;
	}

	
	/**
	 * Get server and db path
	 *
	 * @return array
	 */
	public function f_WebDbName()
	{
	    return f_DbName();
	}
	
	
	public function f_Word($argument)
	{
		//TODO Implement this function
		return $argument;
	}
	
	/**
	 * Get week day number
	 * 
	 * @param \DateTime[] $argument
	 * @return number[]
	 */
	public function f_Weekday($argument)
	{
		if (empty($argument)) return '';
		$output = array();
		$dates = is_array($argument) ? $argument : array($argument);
		foreach ($dates as $value) {
			if ($value instanceof \DateTime) {
				$output[] = intval($value->format('w')) + 1;
			}
			else {
				$output[] = -1;
			}
		}
		return is_array($argument) ? $output : $output[0];
	}
	
	/**
	 * Get year from DateTime object(s)
	 * 
	 * @param \DateTime[] $argument
	 * @return number[]
	 */
	public function f_Year($argument)
	{
		if (empty($argument)) return '';
		$output = array();
		$dates = is_array($argument) ? $argument : array($argument);
		foreach ($dates as $value) {
			if ($value instanceof \DateTime) {
				$output[] = intval($value->format('Y'));
			}
			else {
				$output[] = -1;
			}
		}
		return is_array($argument) ? $output : $output[0];
	}
	
	/**
	 * Return true
	 * 
	 * @return boolean
	 */
	public function f_Yes()
	{
		return true;
	}
	
	/**
	 * Get yesterday datetime object
	 * 
	 * @return \DateTime
	 */
	public function f_Yesterday()
	{
		$date = $this->f_Today();
		$date->modify('-1 day');
		return $date;
	}

	public function f_GetSubformFileName($name){
		$application = $this->docova->getCurrentApplication();
		return $application->getSubformFileName($name);
	}

	public function f_AddComputedToBuffer($fieldname, $fieldvalue){

		//only put the value into the buffer, if its a new doc.  if not the value should be got from the backend document
		//the f_getField checks the buffer first for the value before going to the backend docs.
		//for all new docs, the computed values will end up the buffer such that if any subsequest scripts used that field, 
		//they would get the value that was calculated by the code earlier.
		//for docs that are getting refreshed, we don't want to record the value that was computed, because we want the 
		//value to come from the array that was poseted by the browser ( the current values )
		
		//if ( $this->f_IsNewDoc() and  !$this->isrefresh ) {
			$fieldname = strtolower($fieldname);
			$this->buffer[$fieldname] = $fieldvalue;
	//	}


		return "";
	}
	
	/**
	 * Get document type flag value
	 * 
	 * @param string $doctype
	 * @return string
	 */
	public function f_GetFlag($doctype)
	{
		if (empty($doctype))
			return '';
		
		$flag = $this->docova->getDocTypeFlag($doctype);
		return is_null($flag) ? '' : $flag;
	}
	
	/**
	 * Get folder name
	 * 
	 * @param string $folder
	 * @return string
	 */
	public function f_FolderName($folder)
	{
		if (empty($folder))
			return '';
		
		return $this->docova->getFolderInfo($folder, 'N');
	}
	
	/**
	 * Get folder path
	 * 
	 * @param string $folder
	 * @return string
	 */
	public function f_FolderPath($folder)
	{
		if (empty($folder))
			return '';
		
		return $this->docova->getFolderInfo($folder, 'P');
	}
	
	/**
	 * Get user info (DN) by user id/username
	 * 
	 * @param string $user
	 * @return string
	 */
	public function f_FetchUser($user)
	{
		if (empty($user))
			return '';
		
		return $this->docova->fetchUserDn($user);
	}
	
	/**
	 * Check if document workflow has been started
	 * 
	 * @return boolean
	 */
	public function f_IsWorkflowStarted()
	{
		if ($this->f_IsNewDoc())
		{
			var_dump('Oops!');
			return false;
		}
		
		return $this->docova->isWorkflowStarted();
	}
	
	/**
	 * Filter to print a datetime string to specific format
	 * 
	 * @param string|\DateTime $string
	 * @param string $format
	 * @return string
	 */
	public function dateFormat($date_value, $format = 'm/d/Y')
	{
		$datetime = '';
		if (empty($date_value))
			return $datetime;
		
		if ($date_value instanceof \DateTime)
			return $date_value->format($format);
		
		try {
			$datetime = new \DateTime($date_value);
			if ($datetime) {
				return $datetime->format($format);
			}
		}
		catch (\Exception $e) {
			if (false !== strpos($date_value, '-'))
			{
				$date_value = str_replace('-', '/', $date_value);
				try {
					$datetime = new \DateTime($date_value);
					return $datetime->format($format);
				}
				catch (\Exception $e) {
					$datetime = '';
				}
			}
			elseif (false !== strpos($date_value, '/'))
			{
				$date_value = str_replace('/', '-', $date_value);
				try {
					$datetime = new \DateTime($date_value);
					return $datetime->format($format);
				}
				catch (\Exception $e) {
					$datetime = '';
				}
			}
			$datetime = '';
		}
		return $datetime;
	}
	
	/**
	 * Filter to serialize object value
	 * 
	 * @param mixed $value
	 * @return mixed
	 */
	public function serializeValue($value)
	{
		if (!is_array($value))
		{
			if (is_object($value))
				return serialize($value);
			else 
				return $value;
		}
		else {
			foreach ($value as $key => $v) {
				if (is_object($v))
					$value[$key] = serialize($v);
			}
			return $value;
		}
	}

	/**
	 * Filter to unserialize object value
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function unserializeValue($value)
	{
		if (false !== @unserialize($value))
			return unserialize($value);
		else
			return $value;
	}

	/**
	 * Convert all HTML entities to their applicable characters
	 * 
	 * @param string $value
	 * @return string
	 */
	public function unescape($value)
	{
		return html_entity_decode($value);
	}
	
	private function adjustDate($datetime, $isString = false, $year, $month, $day, $hours, $min, $sec)
	{
		if (!($datetime instanceof \DateTime)) { return null; }
		if (!empty($year)) {
			$datetime->modify("$year year");
		}
		if (!empty($month)) {
			$datetime->modify("$month month");
		}
		if (!empty($day)) {
			$datetime->modify("$day day");
		}
		if (!empty($hours)) {
			$datetime->modify("$hours hour");
		}
		if (!empty($min)) {
			$datetime->modify("$min minute");
		}
		if (!empty($sec)) {
			$datetime->modify("$sec second");
		}
		
		return $isString === false ? $datetime : $datetime->format('m/d/Y H:i:s');
	}
	
	private function sortDataAsc($entryA, $entryB, $insensitive = false)
	{
		if (gettype($entryA) != gettype($entryB))
			throw new \Exception('Entries data types do not match!');
		
		$elementA = $entryA;
		$elementB = $entryB;

		if ($entryA instanceof \DateTime){
			$elementA = new \DateTime($entryA);
		}
		
		if ($entryB instanceof \DateTime){
			$elementB = new \DateTime($entryB);
		}
		

		
		if (is_string($elementA))
		{
			if ($insensitive === false)
				return strcmp($entryA, $entryB);
			else 
				return strcasecmp($entryA, $entryB);
		}
		else {
			if ($elementA == $elementB)
				return 0;
			
			return $elementA < $elementB ? -1 : 1;
		}
	}
	
	private function sortDataDesc($entryA, $entryB, $insensitive = false)
	{
		if (gettype($entryA) != gettype($entryB))
			throw new \Exception('Entries data types do not match!');
		
		$elementA = $entryA;
		$elementB = $entryB;
		
		if ($entryA instanceof \DateTime){
			$elementA = new \DateTime($entryA);
		}
		
		if ($entryB instanceof \DateTime){
			$elementB = new \DateTime($entryB);
		}
		
		
		
		if (is_string($elementA))
		{
			if ($insensitive === false)
				return (-1 * (strcmp($entryA, $entryB)));
			else 
				return (-1 * (strcasecmp($entryA, $entryB)));
		}
		if ($elementA == $elementB)
			return 0;
		
		return $elementA < $elementB ? 1 : -1;
	}
	
}