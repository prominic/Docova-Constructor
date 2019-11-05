<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Docova\DocovaBundle\Entity\GlobalSettings;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserProfile;
use Docova\DocovaBundle\Security\User\adLDAP;

class UtilityHelperTeitr extends Controller {
	
	protected $user;
	protected $global_settings;
	protected $container;
	protected $_params = array();
	
	public function __construct($global_sett, $containerr)
	{
		$this->global_settings=$global_sett;
		$this->container=$containerr;
		$this->_params['ldap_adkey'] = $this->container->getParameter('ldap_adkey');
	}
	
	/**
	 * Find a user info in Database, if does not exist a user object can be created according to its info through LDAP server
	 *
	 * @param string $username
	 * @param boolean $create
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param boolean $search_userid
	 * @param boolean $search_ad
	 * @param boolean $find_inactive
	 * @param boolean $search_common
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	public function findUserAndCreate($username, $create = true, $em, $search_userid = false, $search_ad = true, $find_inactive = false, $search_common = true)
	{
		$em = $this->getDoctrine()->getManager();

		//-- search against the base user name value
		if ($search_userid === true) {
			//-- search active and inactive entries
			if ($find_inactive === false){
			    $user_obj = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username'=>$username));
			//-- search inactive entries
			}else{ 
			    $user_obj = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username'=>$username, 'Trash' => true));
			}
		//-- search against abbreviated name
		}else {
		    	$tmpAbbName=$this->getUserAbbreviatedName($username);
			//-- search active and inactive entries
			if ($find_inactive === false){
				$user_obj = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated'=>$tmpAbbName));
			//-- search inactive entries
			}else{ 
			    $user_obj = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated'=>$tmpAbbName, 'Trash' => true));
			}
		}

		//-- search by common name
		if($search_userid === false && $search_common === true && empty($user_obj)){
		    	$tmpAbbName=$this->getUserAbbreviatedName($username);
			//-- check to see if we have been given a common name (if it contains a slash it is hierarchical)
		    	$nameparts = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $tmpAbbName);
			if(!(is_array($nameparts) && count($nameparts) > 1)){
				$result = null;
				//-- search active and inactive entries
				if($find_inactive === false){
					$query = $em->getRepository("DocovaBundle:UserAccounts")->createQueryBuilder('u');
					$result = $query->where($query->expr()->like('u.userNameDnAbbreviated', $query->expr()->literal($this->escapeValue($tmpAbbName, "SQL_LIKE_SETPARAM").'/%')))
						->getQuery()
						->getResult();
				//-- search inactive entries
				}else{
					$query = $em->getRepository("DocovaBundle:UserAccounts")->createQueryBuilder('u');
					$result = $query->where($query->expr()->like('u.userNameDnAbbreviated', $query->expr()->literal($this->escapeValue($tmpAbbName, "SQL_LIKE_SETPARAM").'/%')))
						->andWhere('u.Trash = :trash')					
						->setParameter('trash', true)					
						->getQuery()
						->getResult();				
				}
				if(!empty($result)){
					//-- check if only a single result found
					if(count($result) == 1){
						$user_obj = $result[0];
					}else{
						//-- previously we were stopping if we got more than one result since we don't know which one to return
						//return false;
						//now we return the first found
						$user_obj = $result[0];
					}
				}
			}
		}
		

		if ($search_ad === true && empty($user_obj) && $this->global_settings->getLDAPAuthentication())
		{
			$ldap_obj = new adLDAP(array(
					'domain_controllers' => $this->global_settings->getLDAPDirectory(), 
					'domain_port' => $this->global_settings->getLDAPPort(), 
					'base_dn' => $this->global_settings->getLdapBaseDn(), 
					'ad_username'=>$this->container->getParameter('ldap_username'), 
					'ad_password'=>$this->container->getParameter('ldap_password')
			));
			
			$arrUserName = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $username);
			$searchTxt=$arrUserName[0];
			$searchTxt = str_replace('\\', '', $searchTxt);
			
			$filter = "( &(objectclass=person)(|(samaccountname=".$searchTxt.")(uid=".$searchTxt.")(userPrincipalName=".$searchTxt.")(cn=".$searchTxt.") ) )";

			$info = $ldap_obj->search_user($filter);
			if (empty($user_obj) && !empty($info['count']))
			{
				if ($create === true && $this->global_settings->getNumberOfUsers() > 0)
				{
					$user_count = $em->getRepository('DocovaBundle:UserAccounts')->createQueryBuilder('U')
						->select('COUNT(U.id)')
						->where('U.Trash = false')
						->getQuery()
						->getSingleScalarResult();
				
					if ($this->global_settings->getNumberOfUsers() <= $user_count)
					{
						$create = false;
					}
				}
				
				
				$userUnid = '';
				if(!empty($this->_params['ldap_adkey'])	&& !empty($info[0][strtolower($this->_params['ldap_adkey'])][0])){
				    $userUnid = $info[0][strtolower($this->_params['ldap_adkey'])][0];
				}elseif(!empty($info[0]['samaccountname'][0])){
				    $userUnid = $info[0]['samaccountname'][0];
				}elseif(!empty($info[0]['uid'][0])){
				    $userUnid = $info[0]['uid'][0];
				}elseif(!empty($info[0]['userprincipalname'][0])){
				    $userUnid = $info[0]['userprincipalname'][0];
				}elseif(!empty($info[0]['mail'][0])){
				    $userUnid = $info[0]['mail'][0];  //TODO - review if this should be the case.  Mail might not be unique attribute
				}
				
				$mail = '';
				if(!empty($info[0]['mail'][0])){
				    $mail = $info[0]['mail'][0];
				}elseif(!empty($info[0]['userprincipalname'][0])){
				    $mail = $info[0]['userprincipalname'][0];
				}else{
				    $mail = $userUnid.'@unknown.unknown';
				}
				
				
				$ldap_dn_name=$info[0]['dn'];
				$tmpAbbName=$this->getUserAbbreviatedName($ldap_dn_name);
				
				
				if ($create === true) 
				{
					
					$user_obj = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username'=>$userUnid, 'userNameDnAbbreviated' => $tmpAbbName)); // duplicate checking
					if (!empty($user_obj)) { return $user_obj; }
					$user_obj = null;
					
					$user_obj = new UserAccounts();
					$user_obj->setUserMail($mail);
					$user_obj->setUsername($userUnid);
		
					//------------- add ldap dn and abbreviated name to user account     ----------
					$user_obj->setUserNameDn($ldap_dn_name); // raw dn format $users_list[$x]['dn']
					$user_obj->setUserNameDnAbbreviated($tmpAbbName); // abbreviate format
					//-----------------------------------------------------------------------------
		
					$default_role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role'=>'ROLE_USER'));
					$user_obj->addRoles($default_role);
					$em->persist($user_obj);
		
					$user_profile = new UserProfile();
					$user_profile->setFirstName((!empty($info[0]['givenname'][0])) ? $info[0]['givenname'][0] : $info[0]['cn'][0]);
					$user_profile->setLastName((!empty($info[0]['sn'][0])) ? $info[0]['sn'][0] : $info[0]['cn'][0]);
					$user_profile->setAccountType(false);
					$user_profile->setDisplayName((!empty($info[0]['displayname'][0])) ? $info[0]['displayname'][0] : $info[0]['cn'][0]);
					$user_profile->setUser($user_obj);
					if (!empty($mail))
					{
						if (false !== stripos($mail, '@gmail')) {
							$mailServerUrl = 'imap.gmail.com:993/imap/ssl';
						}
						elseif (false !== stripos($mail, '@yahoo') || stripos($mail, '@ymail')) {
							$mailServerUrl = 'imap.mail.yahoo.com:993/imap/ssl';
						}
						elseif (false !== stripos($mail, '@hotmail') || stripos($mail, '@live')) {
							$mailServerUrl = 'imap-mail.outlook.com:993/imap/ssl';
						}
						else {
							$mailServerUrl = $this->global_settings->getNotesMailServer() ? $this->global_settings->getNotesMailServer() : 'MAIL SERVER SHOULD BE SET MANUALLY.';
						}
					}
					else {
						$mailServerUrl = $this->global_settings->getNotesMailServer() ? $this->global_settings->getNotesMailServer() : 'MAIL SERVER SHOULD BE SET MANUALLY.';
					}
					$user_profile->setMailServerURL($mailServerUrl);
					$em->persist($user_profile);
					$em->flush();

				}
				else {
					$user_obj = array(
						'mail' => $mail,
						'username_dn_abbreviated' => $tmpAbbName,
						'username_dn' => $ldap_dn_name,
						'display_name' => (!empty($info[0]['displayname'][0]) ? $info[0]['displayname'][0] : $info[0]['cn'][0]),
						'uid' => $userUnid
					);
				}
				$em = $ldap_obj = $user_profile = null;
			}
		}
	
		if (!empty($user_obj)) {
			return $user_obj;
		}
	
		return false;
	}
	
	/**
	* @param: string $userDnName e.g CN=DV Punia,O=DLI
	* @return: string abbreviated name e.g DV Punia/DLI
	*/
	public function getUserAbbreviatedName($userDnName)
	{
		try {
			$strAbbDnName="";
			$arrAbbName = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $userDnName);
			if(count($arrAbbName) < 2){
			    $arrAbbName = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $userDnName);
			}
				
			//create abbreviated name
			foreach ($arrAbbName as $value){
				if(trim($value) != ""){
					$namepart = explode("=", $value);
					$strAbbDnName .= (count($namepart) > 1 ? trim($namepart[1]) : trim($namepart[0]))."/";
				}
			}
			//remove last "/"
			$strAbbDnName=rtrim($strAbbDnName,"/");
				
		} catch (\Exception $e) {
			//$this->log->error("Bad name found in Ldap (user_dn): ". $user_dn[0]);
			var_dump("UtilityHelper::getUserAbbreviatedName() exception".$e->getMessage());
		}
	
	
		return $strAbbDnName;
	}
	
	
	/**
	 * escapeValue
	 * @param: $valuetoescape string
	 * @param: $escapetype string - "SQL" or "SQL_LIKE" or "SQL_LIKE_SETPARAM" 
	 * @return: string 
	 */
	public function escapeValue($valuetoescape, $escapetype = ""){
	    $output = $valuetoescape;
	    
	    if($escapetype == "SQL"){
	        $output = str_replace("\\", "\\\\", $output); //-- escape a single back slash into two
	    }else if($escapetype == "SQL_LIKE"){
	        $output = str_replace("\\", "\\\\\\\\", $output); //-- escape a single back slash into four
	    }else if($escapetype == "SQL_LIKE_SETPARAM"){
	        $output = str_replace("\\", "\\\\", $output); //-- escape a single back slash into four
	    }
	    
	    return $output;
	}

}