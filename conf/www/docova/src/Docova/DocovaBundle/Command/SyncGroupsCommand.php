<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Security\User\adLDAP;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserProfile;
use Docova\DocovaBundle\Entity\EventLogs;
use Doctrine\ORM\Query;

/**
 * Synchronize existing group members with AD/LDAP
 * @author javad rahimi
 *        
 */
class SyncGroupsCommand extends ContainerAwareCommand 
{
	protected static $amount = 0;
	protected $_em;
	protected $ldap = null;
	protected $global_settings;
	protected $_params = array();
	
	protected function configure()
	{
		$this
			->setName('docova:syncgroups')
			->addArgument('groupid')
			->setDescription('Synchronize group members with AD/LDAP');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->_em = $this->getContainer()->get('doctrine')->getManager();
		$this->_params['ldap_adkey'] = $this->getContainer()->getParameter('ldap_adkey');
		
		$groupid = $input->getArgument('groupid');
	    	$group = (!empty($groupid) ? $this->_em->getRepository('DocovaBundle:UserRoles')->find($groupid) : null);
		if (!empty($group)) 
		{
			$this->global_settings = $this->_em->getRepository('DocovaBundle:GlobalSettings')->createQueryBuilder('GS')
				->select(array('GS.LDAP_Directory', 'GS.LDAP_Port', 'GS.ldapBaseDn', 'GS.runningServer', 'GS.notesMailServer'))
				->setMaxResults(1)
				->getQuery()
				->getSingleResult(Query::HYDRATE_ARRAY);
			
			$this->_em->getConnection()->beginTransaction();
			try {
				$this->ldap = new adLDAP(array(
					'domain_controllers' => $this->global_settings['LDAP_Directory'],
					'domain_port' => $this->global_settings['LDAP_Port'],
					'base_dn' => $this->global_settings['ldapBaseDn'],
					'ad_username' => $this->getContainer()->getParameter('ldap_username'),
					'ad_password' => $this->getContainer()->getParameter('ldap_password')
				));
				
				if ($group->getRoleUsers()->count() > 0) {
					foreach($group->getRoleUsers() as $user) {
						$group->removeRoleUsers($user);
					}
					$this->_em->flush();
				}
				$this->importGroupMembers($group, $this->global_settings['ldapBaseDn']);
				$this->_em->getConnection()->commit();
				$this->ldap = null;
			}
			catch (\Exception $e) {
				$this->_em->getConnection()->rollback();
				$output->writeln('ERROR: '. $e->getMessage() . ' line ' . $e->getLine() . ' File' . $e->getFile());
				return false;
			}
			
			$event = new EventLogs();
			$event->setAgentName('Groups Synchronization');
			$event->setEventDate(new \DateTime());
			$event->setServer($this->global_settings['runningServer']);
			$event->setDetails('AD/LDAP group synchronization has completed. (Synced "'.self::$amount.'" user(s) for group '. $group->getDisplayName() .')');
			$this->_em->persist($event);
			$this->_em->flush();
			$output->writeln('AD/LDAP group synchronization has completed. (Synced "'.self::$amount.'" user(s) for group '. $group->getDisplayName() .')');
			self::$amount = 0;
			$event = $group = null;
		}


	}
	
	/**
	 * Import group members to DB
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserRoles $group
	 * @param string $baseDn
	 */
	private function importGroupMembers($group, $baseDn = null)
	{
		$imported = false;
		$groupname = $group->getGroupName();// .(!empty($baseDn) ? '/' . str_replace(array(', ', ','), '/', ldap_dn2ufn($baseDn)) : '');
		echo $groupname."\n";
		if (!in_array($groupname, array('Domino.Doc Users', 'Domino.Doc Administrators for CSBP DevTest', 'FSA', 'Domino.Doc Address Book Editors', 'Domino.Doc Users for CSBP DevTest', 'Domino.Doc Address Book Editors for CSBP DevTest')))
		{
			try {
				$members = $this->ldap->group_members($groupname, true);
			}
			catch(\Exception $e) {
				$members = null;
			}
		}
		
		if (!empty($members) && count($members) > 0)
		{
			foreach ($members as $username) {
				if (strpos($username, '=') === false && (false !== $user = $this->findUser($username, true)))
				{
					$user->addRoles($group);
					$imported = true;
					self::$amount++;
				}
				unset($user);
			}

			$subgroups = $this->ldap->groups_in_group($group->getGroupName(), true);// . (!empty($baseDn) ? '/' . str_replace(array(', ', ','), '/', ldap_dn2ufn($baseDn)) : ''));
			if (!empty($subgroups)) 
			{
				$nested_groups = array();
				foreach ($subgroups as $g) {
					$d = substr(stristr($g, 'CN='), 3);
					$d = strstr($d, ',', true);
//					$d = substr_replace($d, '', -1);
					$nested_groups[] = $d;
				}
				$group->setNestedGroups($nested_groups);
			}
			$this->_em->flush();
		}
	}
	
	/**
	 * Search for a user in DB if not found will search in AD/LDAP and create user profile
	 * 
	 * @param string $username
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUser($username, $byUsername = false)
	{
		try {
			if ($byUsername === true) 
			{
				$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $username));
			}
			else {
				$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $username));
			}
			if (!empty($user)) 
			{
				return $user;
			}
			
			$arrUserName = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $username); 
			$searchTxt=$arrUserName[0];
			$searchTxt = str_replace('\\', '', $searchTxt);
			
			$filter = "( &(objectclass=person)(|(samaccountname=".$searchTxt.")(uid=".$searchTxt.")(userPrincipalName=".$searchTxt.")(cn=".$searchTxt.") ) )";
				
			$info = $this->ldap->search_user($filter);
			if (!empty($info['count']))
			{
			    
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
			    
			    //double check that a match doesnt already exist as the returned data from ldap may now match an existing user
			    $user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $userUnid));
			    if (!empty($user))
			    {
			        return $user;
			    }
			    
				$user = new UserAccounts();
				$user->setUsername($userUnid);				
				$user->setUserMail($mail);
	
				//------------- add ldap dn and abbreviated name to user account     ----------
				$ldap_dn_name = $info[0]['dn'];
				$user->setUserNameDn($ldap_dn_name); // raw dn format $users_list[$x]['dn']
				$tmpAbbName = $this->getUserAbbreviatedName($ldap_dn_name);
				$user->setUserNameDnAbbreviated($tmpAbbName); // abbreviate format
				//-----------------------------------------------------------------------------
	
				$default_role = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role'=>'ROLE_USER'));
				$user->addRoles($default_role);
				$this->_em->persist($user);
				unset($default_role);
	
				$profile = new UserProfile();
				$profile->setFirstName((!empty($info[0]['givenname'][0])) ? $info[0]['givenname'][0] : $info[0]['cn'][0]);
				$profile->setLastName((!empty($info[0]['sn'][0])) ? $info[0]['sn'][0] : $info[0]['cn'][0]);
				$profile->setAccountType(false);
				$profile->setDisplayName((!empty($info[0]['displayname'][0])) ? $info[0]['displayname'][0] : $info[0]['cn'][0]);
				$profile->setUser($user);
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
						$mailServerUrl = $this->global_settings['notesMailServer'] ? $this->global_settings['notesMailServer'] : 'MAIL SERVER SHOULD BE SET MANUALLY.';
					}
				}
				else {
					$mailServerUrl = $this->global_settings['notesMailServer'] ? $this->global_settings['notesMailServer'] : 'MAIL SERVER SHOULD BE SET MANUALLY.';
				}
				$profile->setMailServerURL($mailServerUrl);
				$this->_em->persist($profile);
				$this->_em->flush();
				unset($profile);
				return $user;
			}
			return false;
		}
		catch (\Exception $e) {
			unset($user, $username);
		}
		
		return false;
	}
	
	/**
	 * @param: dn name e.g CN=DV Punia,O=DLI
	 * @return: abbreviated name e.g DV Punia/DLI
	 */
	private function getUserAbbreviatedName($userDnName){
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
	    }
	    
	    
	    return $strAbbDnName;
	}
}