<?php
namespace Docova\DocovaBundle\Extensions;
use Docova\DocovaBundle\Security\User\CustomACL;

class SessionHelpers 
{	
	public static function getCurrentUser($sessionId,$environment,$em)
	{
		$userId = self::getCurrentUserId($sessionId, $environment);		
		return self::getUser($em,$userId);
	}

	public static function getCurrentUserFromSession($sessionData,$em)
	{
		$userId = self::getCurrentUserIdFromSession($sessionData);
		return self::getUser($em,$userId);
	}

	public static function getDocovaPath()
	{
		$path = \str_replace('\\', '/', realpath(__DIR__.'/../'.'/../'.'/../'.'/../'))."/";
		return $path;
	}

	public static function getSubscribedLibraries($em,$container,$user)
	{
		$count=0;
		$libraries = self::getLibraries($em);
		$subscribedLibraries = array();
	
		$customAcl = new CustomACL($container);
		//$userContext = $em->getReference('DocovaBundle:UserAccounts', $user->getId());
	
		foreach($libraries as $library){
			//$libraryContext = $em->getReference('DocovaBundle:UserAccounts', $library->getId()['id']);
			//check to see if the library is subsribed
			if (true === $customAcl->isUserGranted($library, $user, 'delete'))
			{
				$subscribedLibraries[$count]=$library;
				$count++;
			}
		}
	
		return $subscribedLibraries;
	}
	
	public static function getUserSessions($em,$echoDetails=false,$format="array")
	{
		$devSessions = self::getSessionStoragePath("dev");
		$prodSessions = self::getSessionStoragePath("prod");

		if ($echoDetails){
			//echo "Dev Path: ".$devSessions.PHP_EOL;
			//echo "Prod Path: ".$prodSessions.PHP_EOL;			
		}
		$userSessions = array();
		foreach(new \DirectoryIterator($devSessions) as $sessionFile){
			if ($sessionFile){
				if ($sessionFile->isFile()) {
					$sessionFileName = $sessionFile->getFilename();
					$sessionFilePath =($devSessions.$sessionFileName);
					self::processSessionFile($em,$sessionFilePath,$userSessions,$echoDetails);
				}
			}
		}

		foreach(new \DirectoryIterator($prodSessions) as $sessionFile){
			if ($sessionFile){
				if ($sessionFile->isFile()) {					
					$sessionFileName = $sessionFile->getFilename();
					$sessionFilePath =($prodSessions.$sessionFileName);
					self::processSessionFile($em,$sessionFilePath,$userSessions,$echoDetails);
				}
			}
		}
	
		if ($format=="array")
			return $userSessions;
		else if ($format=="xml") {
			$data_xml = '';
			if (!empty($userSessions)) {
				foreach ($userSessions as $userSession)
				{					
					//echo "User: ".$userSession->getUserName()." Created: ". $userSession->getCreated()->format("Y/m/d H:i:s")." Modified: ". $userSession->getModified()->format("Y/m/d H:i:s")." Environment: ".$userSession->getEnvironment().PHP_EOL;
					
					$data_xml .= '<document>';
					$data_xml .= '<dockey>'.$userSession->getUserId().'</dockey>';
					$data_xml .= '<docid>'.$userSession->getUserId().'</docid>';
					$data_xml .= '<rectype>doc</rectype>';
					// view specific fields
					$data_xml .= '<user><![CDATA['. $userSession->getUserName().']]></user>';
					$data_xml .= '<env><![CDATA['. $userSession->getEnvironment().']]></env>';
					$access_date = $userSession->getCreated();
					$date = !empty($access_date) ? $access_date->format("m/d/Y h:i:s A") : '';
					$val = !empty($access_date) ? $access_date->getTimeStamp() : '';
					$y = !empty($access_date) ? $access_date->format('Y') : '';
					$m = !empty($access_date) ? $access_date->format('m') : '';
					$d = !empty($access_date) ? $access_date->format('d') : '';
					$w = !empty($access_date) ? $access_date->format('w') : '';
					$h = !empty($access_date) ? $access_date->format('H') : '';
					$mn = !empty($access_date) ? $access_date->format('i') : '';
					$s = !empty($access_date) ? $access_date->format('s') : '';
					$data_xml .= "<created val='$val' Y='$y' M='$m' D='$d' W='$w' H='$h' MN='$mn' S='$s'><![CDATA[$date]]></created>";
					
					
					$access_date = $userSession->getModified();
					$date = !empty($access_date) ? $access_date->format("m/d/Y h:i:s A") : '';
					$val = !empty($access_date) ? $access_date->getTimeStamp() : '';
					$y = !empty($access_date) ? $access_date->format('Y') : '';
					$m = !empty($access_date) ? $access_date->format('m') : '';
					$d = !empty($access_date) ? $access_date->format('d') : '';
					$w = !empty($access_date) ? $access_date->format('w') : '';
					$h = !empty($access_date) ? $access_date->format('H') : '';
					$mn = !empty($access_date) ? $access_date->format('i') : '';
					$s = !empty($access_date) ? $access_date->format('s') : '';
					$data_xml .= "<lastaccess val='$val' Y='$y' M='$m' D='$d' W='$w' H='$h' MN='$mn' S='$s'><![CDATA[$date]]></lastaccess>";
					
					$data_xml .= '<ipaddress>'.$userSession->getIpAddress()."</ipaddress>";
					$data_xml .= '<statno />';
					$data_xml .= '<wfstarted />';
					$data_xml .= '<delflag />';
					$data_xml .= '</document>';
					
				}
			}
			
			return $data_xml;
		}
	}

	public static function showUserSessions($em)
	{
		$userSessions = self::getUserSessions($em,true,"array");
		if (empty($userSessions))
			echo PHP_EOL."0 user sessions were identified.".PHP_EOL;
		else
			echo PHP_EOL.count($userSessions) . " user sessions were identified.".PHP_EOL;
		
	}

	private static function getSessionDataFromFile($sessionFile,$environment="prod")
	{
		$sessionData = null;
		if (empty($sessionFile))
			return null;
	
		//Read the session file from the disk
		$handle = fopen($sessionFile, 'r+');
		$sData = fread($handle, filesize($sessionFile));
		return self::unserialize_php($sData);
	}
	
	private static function getSessionStoragePath($environment="prod")
	{
		if ($environment=="prod")
			return self::getDocovaPath().'app/cache/prod/sessions/';
		else
			return self::getDocovaPath().'app/cache/dev/sessions/';
	}
	
	private static function unserialize_php($session_data) 
	{
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($session_data)) {
			if (!strstr(substr($session_data, $offset), "|")) {
				throw new \Exception("invalid data, remaining: " . substr($session_data, $offset));
			}
			$pos = strpos($session_data, "|", $offset);
			$num = $pos - $offset;
			$varname = substr($session_data, $offset, $num);
			$offset += $num + 1;
			$data = unserialize(substr($session_data, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}

	private static function processSessionFile($em,$sessionFilePath,&$userSessions,$echoDetails=false)
	{
		if (empty($sessionFilePath))
			return;
		//echo "Processing session file: ".$sessionFilePath.PHP_EOL;
	
		$sessionData = self::getSessionDataFromFile($sessionFilePath);
		if (empty($sessionData)){
			//echo "Skipping invalid session file".PHP_EOL;
			return;
		}
		
		$environment = \strpos($sessionFilePath, "/dev/")=== false ? "prod" : "dev";
	
		$sessionUser = self::getCurrentUserFromSession($sessionData,$em);
		if (!empty($sessionUser)){
			$sessionCreated =  new \DateTime();
			$sessionCreated->setTimestamp(filectime($sessionFilePath));
			$sessionModified = new \DateTime();
			$sessionModified->setTimestamp(filemtime($sessionFilePath));
			
			$sessionIpAddress = \str_replace(array("s:??:","\""),"",self::getSessionAttributeFromSession($sessionData, "IP_Address"));
			$userSession = new UserSession($sessionUser,$sessionCreated,$sessionModified,$sessionIpAddress,$environment);
			$userSessions[]=$userSession;
			if ($echoDetails)
				echo "User: ".$userSession->getUserName()." Created: ". $userSession->getCreated()->format("Y/m/d H:i:s")." Modified: ". $userSession->getModified()->format("Y/m/d H:i:s")." Environment: ".$userSession->getEnvironment()." Id Address: ".$sessionIpAddress.PHP_EOL;
			
		}
	}

	private static function getSessionData($sessionId,$environment="prod")
	{		
		$sessionData = null;
		if (empty($sessionId))
			return null;
		
		//Read the session file from the disk
		$sessionFile = self::getSessionStoragePath($environment).'sess_'.$sessionId;
		$sessionData = file_get_contents($sessionFile);
		return self::unserialize_php($sessionData);
	}
	
	private static function getSessionAttribute($sessionId,$environment,$attribute)
	{		
		$sessionAttributes = self::getSessionAttributes($sessionId, $environment);		
		foreach ($sessionAttributes as $key => $value){			
			if ($key==$attribute){
				return $value;
			}
		}
		return null;
	}

	private static function getSessionAttributeFromSession($sessionData,$attribute)
	{
		$sessionAttributes = self::getSessionAttributesFromSession($sessionData);
		foreach ($sessionAttributes as $key => $value){
			if ($key==$attribute){
				return $value;
			}
		}
		return null;
	}

	private static function getSessionAttributes($sessionId,$environment)
	{
		$sessionData = self::getSessionData($sessionId,$environment);
		return $sessionData["_sf2_attributes"];	
	}	

	private static function getSessionAttributesFromSession($sessionData)
	{	
		return $sessionData["_sf2_attributes"];
	}

	private static function processSecurityAttribute($securityAttribute)
	{
		$objects = explode(";",$securityAttribute);
		$accountFound=false;
		foreach ($objects as $oValue){
			$isAccount = !(strpos($oValue,"Docova\DocovaBundle\Entity\UserAccounts")===false);
			if ($isAccount){
				//The next GUID object will be the user GUID
				//Flag the account as being found and continue processing the next loop
				$accountFound=true;
				continue;
			}
			if ($accountFound){
				$isGUID = !(strpos($oValue,"s:36:")===false);
				if ($isGUID){
					//return the user GUID from the session
					return \str_replace(array("s:36:","\""),"",$oValue);
				}
			}
		}
		return null;
	}

	private static function getCurrentUserId($sessionId,$environment)
	{
		$securityAttribute = self::getSessionAttribute($sessionId, $environment, "_security_my_docova");
		return self::processSecurityAttribute($securityAttribute);
	}

	private static function getCurrentUserIdFromSession($sessionData)
	{
		$securityAttribute = self::getSessionAttributeFromSession($sessionData, "_security_my_docova");
		return self::processSecurityAttribute($securityAttribute);
	}

	private static function getUser($em,$userId)
	{
		$query = $em->getRepository('DocovaBundle:UserAccounts')->createQueryBuilder("u");
	
		//Return a users email address given the user name, dn, or abbreviated dn
		$userAccounts = $query->where('u.id= :user')		
		->setParameter('user',$userId)
		->getQuery()->getResult();
	
		//ensure that an account is found
		if (empty($userAccounts) || empty($userAccounts[0]) )
			return null;
		else
			return $userAccounts[0];
	}

	private static function getLibraries($em)
	{
		$qbLib = $em->getRepository("DocovaBundle:Libraries")->createQueryBuilder("l");
		$qbLib->select("l")
		->where("l.Trash='false' AND l.Status='true'")
		->orderBy("l.Library_Title","ASC");
		return $qbLib->getQuery()->getResult();
	}
}
?>