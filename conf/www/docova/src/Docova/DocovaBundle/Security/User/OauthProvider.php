<?php

namespace Docova\DocovaBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Docova\DocovaBundle\Entity\UserAccounts;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Docova\DocovaBundle\Entity\UserProfile;
//use Docova\DocovaBundle\Extensions\Oauthorization;
//use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class to process SSO authentication (currently Azure) using Magium plug-in 
 * @author javad_rahimi
 */
class OauthProvider implements UserProviderInterface
{
	private $_em;
	private $_user;
	private $_params = array();
	
	public function __construct(EntityManager $em, $params)
	{
		$this->_em = $em;
		$this->_params = $params;
	}
	
	public function supportsClass($class)
	{
		return $class === 'Docova\DocovaBundle\Entity\UserAccounts' || is_subclass_of($class, 'Docova\DocovaBundle\Entity\UserAccounts');
	}
	
	public function refreshUser(UserInterface $user)
	{
		if (!$user instanceof UserAccounts)
		{
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
		}
		$username = $user->getUsername();
		return $this->loadUserByUsername($username);
	}
	
	public function loadUserByUsername($username)
	{
		$em = $this->_em->create($this->_em->getConnection(), $this->_em->getConfiguration());
		$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$global_settings = $global_settings[0];
		
		if (!$global_settings->getSsoAuthentication())
		{
			throw new UsernameNotFoundException('SSO is not enabled on this instance.');
		}
		
		$session = new Session();
		$session->set('directory', $global_settings->getLDAPDirectory());
		$session->set('port', $global_settings->getLDAPDirectory());
		$session->set('base_dn', $global_settings->getLDAPDirectory());
		$session->save();
		
		$ldap_obj = new adLDAP(array(
			'domain_controllers' => $global_settings->getLDAPDirectory(),
			'domain_port' => $global_settings->getLDAPPort(),
			'ad_username'=>$this->_params['ldap_username'],
			'ad_password'=>$this->_params['ldap_password']
		));
		
		$arrUserName = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $username);
		$searchTxt=$arrUserName[0];
		$searchTxt = str_replace('\\', '', $searchTxt);
		
		$filter = "( &(objectclass=person)(|(samaccountname=".$searchTxt.")(uid=".$searchTxt.")(userPrincipalName=".$searchTxt.")(cn=".$searchTxt.")(mail=".$searchTxt.") ) )";
		$info = $ldap_obj->search_user($filter);

		if (!empty($info) && !empty($info['count']))
		{
			$ldap_dn_name=$info[0]['dn'];
			$tmpAbbName = $this->getUserAbbreviatedName($ldap_dn_name);
			
			$specificlookup = false;
			
			$userUnid = '';
			if(!empty($this->_params['ldap_adkey'])	&& !empty($info[0][strtolower($this->_params['ldap_adkey'])][0])){
			    $userUnid = $info[0][strtolower($this->_params['ldap_adkey'])][0];
			    $specificlookup = true;
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
			
			if ($specificlookup)
			{
			    $query = $em->getRepository('DocovaBundle:UserAccounts')->createQueryBuilder('U')
				    ->where('U.username = :userkey')
				    ->setParameter('userkey', $userUnid)
				    ->getQuery();
			}else {
    				$query = $em->getRepository('DocovaBundle:UserAccounts')->createQueryBuilder('U')
    					->where('U.username = :userid')
    					->andWhere('U.User_Mail = :email OR U.userNameDnAbbreviated = :userNameDnAbbreviated')
    					->setParameter('userid', $userUnid)
    					->setParameter('email', $mail)
    					->setParameter('userNameDnAbbreviated', $tmpAbbName)
    					->getQuery();
			}
			
			$user_obj = $query->getResult();
			if (empty($user_obj))
			{
				if (!$global_settings->getUserProfileCreation())
				{
					throw new UsernameNotFoundException('Please, contact your Administrator or IT member to generate profile for you.');
				}
				
				if ($global_settings->getNumberOfUsers() > 0)
				{
					$user_count = $em->getRepository('DocovaBundle:UserAccounts')->createQueryBuilder('U')
						->select('COUNT(U.id)')
						->where('U.Trash = false')
						->andWhere('U.username != :u1')
						->andWhere('U.username != :u2')
						->setParameter('u1', 'DOCOVA SE')
						->setParameter('u2', 'DOCOVA Administrator')
						->getQuery()
						->getSingleScalarResult();
						
					if ($global_settings->getNumberOfUsers() <= $user_count)
					{
						$routing = new Router();
						$redirect = new RedirectResponse($routing->generate('docova_licenseguide'));
						return $redirect;
					}
				}
	
				$user_obj = new UserAccounts();
				$user_obj->setUsername($userUnid);
				$user_obj->setUserMail($mail);
				$user_obj->setUserNameDn($ldap_dn_name);
				$user_obj->setUserNameDnAbbreviated($tmpAbbName);
				
				$default_role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_USER'));
				$user_obj->addRoles($default_role);
				
				$em->persist($user_obj);
				$em->flush();
				$user_profile = new UserProfile();
				$user_profile->setDisplayName((!empty($info[0]['displayname'][0])) ? $info[0]['displayname'][0] : $info[0]['cn'][0]);
				$user_profile->setFirstName((!empty($info[0]['givenname'][0])) ? $info[0]['givenname'][0] : $info[0]['cn'][0]);
				$user_profile->setLastName((!empty($info[0]['sn'][0])) ? $info[0]['sn'][0] : $info[0]['cn'][0]);
				$user_profile->setMailServerURL($global_settings->getNotesMailServer() ? $global_settings->getNotesMailServer() : 'MAIL SERVER SHOULD BE SET MANUALLY.');
				$user_profile->setUser($user_obj);
				$user_profile->setLastModifiedDate(new \DateTime());
				
				$em->persist($user_profile);
				$em->flush();
				return $user_obj;
			}
			elseif (count($user_obj) > 1)
			{
				throw new NoResultException();
			}
			elseif ($user_obj[0]->getTrash() === true)
			{
				throw new NoResultException();
			}
			else {
				if (!$user_obj[0]->getUserNameDn()) {
					$user_obj[0]->setUserNameDn($ldap_dn_name);
					$user_obj[0]->setUserNameDnAbbreviated($tmpAbbName);
					$em->flush();
				}
			}

			return $user_obj[0];
		}
		else {
			throw new UsernameNotFoundException('No match username was found. Check the username, please.');
		}
	}


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
	        var_dump("OauthProvider::getUserAbbreviatedName() exception".$e->getMessage());
	    }
	    
	    
	    return $strAbbDnName;
	}
}