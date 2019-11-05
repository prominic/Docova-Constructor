<?php

namespace Docova\DocovaBundle\Controller;

use Docova\DocovaBundle\Security\User\adLDAP;
//use Docova\DocovaBundle\Security\User\CustomACL;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
//use Docova\DocovaBundle\Entity\Folders;
//use Docova\DocovaBundle\Entity\Libraries;


class UserController extends Controller
{
	protected $user;
	protected $global_settings;
	protected $log; 
	

	private function initialize()
	{
 		$this->log = $this->get('logger');
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();

		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
	}
	
	
	public function getLibraryAccessAction(Request $request)
	{
		$library_id = $request->query->get('LibraryId');
		
		if (empty($library_id)) {
			throw $this->createNotFoundException('Library ID is missed.');
		}
		
		$res = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library_id, 'Trash' => false));
		if (empty($res)) {
			throw $this->createNotFoundException('Could not find library with ID = '.$library_id);
		}
		
		$library_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $library_xml->appendChild($library_xml->createElement('document'));
		$access_control = new Miscellaneous($this->container);
		$db_access_level = $access_control->getDBAccessLevel($res);
		$root->appendChild($library_xml->createElement('DbAccessLevel', $db_access_level['dbaccess']));
		$root->appendChild($library_xml->createElement('DocAccessRole', $db_access_level['docrole']));
		unset($access_control);
		$response = new Response($library_xml->saveXML());
		$response->headers->set('Content-Type', 'text/xml');
		
		return $response;
	}

	public function getUserLibrariesAction(Request $request)
	{
		$this->initialize();
		$server = $request->getHttpHost();
		$liblist = trim($request->query->get('LibList'));
		$sort = $request->query->get('sort') == true ? true : false;
		
		//$xml_str = $this->getLibrariesXML($server, true, $sort, $liblist);
		$em = $this->getDoctrine()->getManager();
		if ($sort === false) {
			$libraries = $em->getRepository('DocovaBundle:Libraries')->getUserLibraries($this->user, true, array('Community' => 'ASC', 'Realm' => 'ASC', 'Library_Title' => 'ASC'));
		}
		else {
			$libraries = $em->getRepository('DocovaBundle:Libraries')->getUserLibraries($this->user, true, array('Library_Title' => 'ASC'));
		}

		$xml_obj = new \DOMDocument("1.0", "UTF-8");
		$root = $xml_obj->appendChild($xml_obj->createElement('Libraries'));
		foreach ($libraries as $library) {
			if (!empty($liblist)) {
				if (false === stripos($liblist, $library['id'])) {
					continue;
				}
			}
//			$lib_obj = $em->getReference('DocovaBundle:Libraries', $library['id']);
//			if ($securityContext->isGranted('VIEW', $lib_obj) === true && $customACL_obj->isUserGranted($lib_obj, $this->user, 'delete') === true) {
				$lib_child = $root->appendChild($xml_obj->createElement('Library'));
				$CData = $xml_obj->createCDATASection($server);
				$child = $xml_obj->createElement('Server');
				$child->appendChild($CData);
				$lib_child->appendChild($child);
				$lib_child->appendChild($xml_obj->createElement('NsfName', substr($this->generateUrl('docova_homepage'), 0, -1)));
		
				$CData = $xml_obj->createCDATASection($library['Library_Title']);
				$child = $xml_obj->createElement('Title');
				$child->appendChild($CData);
				$lib_child->appendChild($child);
		
				$CData = $xml_obj->createCDATASection($library['Description']);
				$child = $xml_obj->createElement('Description');
				$child->appendChild($CData);
				$lib_child->appendChild($child);

				$lib_child->appendChild($xml_obj->createElement('DocKey', $library['id']));
				$lib_child->appendChild($xml_obj->createElement('Unid', $library['id']));
				
				$CData = $xml_obj->createCDATASection($library['Community']);
				$child = $xml_obj->createElement('Community');
				$child->appendChild($CData);
				$lib_child->appendChild($child);

				$CData = $xml_obj->createCDATASection($library['Realm']);
				$child = $xml_obj->createElement('Realm');
				$child->appendChild($CData);
				$lib_child->appendChild($child);

				$lib_child->appendChild($xml_obj->createElement('LoadDocsAsFolders', (int)$library['Load_Docs_As_Folders']));
				
				$lib_child->appendChild($xml_obj->createElement('Selected', 0));
				$lib_child->appendChild($xml_obj->createElement('Modified', 0));
//			}
		}
				
		$response = new Response($xml_obj->saveXML());
		$response->headers->set('Content-Type', 'text/xml');
		return $response;
	}	 

	public function folderAccessAction(Request $request)
	{
		$this->initialize();
		$folder_id = $request->query->get('ParentUNID');
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$security_check = new Miscellaneous($this->container);
		$isrecycle = 0;
		if (!empty($folder_id)) {
			$em = $this->getDoctrine()->getManager();
			if (substr($folder_id, 0, 5) != "RCBIN") {
				$folder = $em->find('DocovaBundle:Folders', $folder_id);
					
				if (empty($folder)) {
					throw $this->createNotFoundException('No folder is found for ID = '.$folder_id);
				}
			}
			else {
				$isrecycle = 1;
				$folder = null;
			}
			$access_level = $security_check->getAccessLevel($folder);

			$folder_xml = new \DOMDocument('1.0', 'UTF-8');
			$root = $folder_xml->appendChild($folder_xml->createElement('document'));
			$root->appendChild($folder_xml->createElement('DbAccessLevel', $access_level['dbaccess']));
			$root->appendChild($folder_xml->createElement('DocAccessLevel', $access_level['docacess']));
			$root->appendChild($folder_xml->createElement('IsRecycleBin', $isrecycle));
			$root->appendChild($folder_xml->createElement('CanCreateDocuments', $access_level['ccdocument']));
			$root->appendChild($folder_xml->createElement('CanCreateRevisions', $access_level['ccrevision']));
			$root->appendChild($folder_xml->createElement('CanDeleteDocuments', $access_level['cddocument']));
			$root->appendChild($folder_xml->createElement('CanSoftDeleteDocuments', $access_level['ccdocument']));
			$root->appendChild($folder_xml->createElement('AuthorsCanNotCreateFolders', ((!empty($folder) && $folder->getDisableACF() === true) ? 1 : '')));
			$root->appendChild($folder_xml->createElement('DocAccessRole', $access_level['docrole']));
			$response->setContent($folder_xml->saveXML());
		}
		else {
			throw $this->createNotFoundException('Parent Folder Id is missed.');
		}

	  	return $response;
	}
	
	public function popupSubscriptionsAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$subscribed_libs = $available_libs = array();
		$libraries = $em->getRepository('DocovaBundle:Libraries')->getUserLibraries($this->user, true, array('Community' => 'ASC', 'Realm' => 'ASC', 'Library_Title' => 'ASC'));
		if (!empty($libraries))
		{
			foreach ($libraries as $library) {
				if ($library['Community']) {
					if (!array_key_exists('community', $subscribed_libs))
						$subscribed_libs['community'] = array();
					if (!array_key_exists($library['Community'], $subscribed_libs['community']))
						$subscribed_libs['community'][$library['Community']] = array();
					if ($library['Realm']) {
						if (!array_key_exists('realm', $subscribed_libs['community'][$library['Community']]))
							$subscribed_libs['community'][$library['Community']]['realm'] = array();
						if (!array_key_exists($library['Realm'], $subscribed_libs['community'][$library['Community']]['realm']))
							$subscribed_libs['community'][$library['Community']]['realm'][$library['Realm']] = array();
						
						$subscribed_libs['community'][$library['Community']]['realm'][$library['Realm']][] = $library;
					}
					else {
						$subscribed_libs['community'][$library['Community']][] = $library;
					}
				}
				else {
					$subscribed_libs[] = $library;
				}
			}
		}
		$libraries = null;
		$libraries = $em->getRepository('DocovaBundle:Libraries')->getUserLibraries($this->user, false, array('Community' => 'ASC', 'Realm' => 'ASC', 'Library_Title' => 'ASC'));
		if (!empty($libraries))
		{
			foreach ($libraries as $library) {
				if ($library['Community']) {
					if (!array_key_exists('community', $available_libs))
						$available_libs['community'] = array();
					if (!array_key_exists($library['Community'], $available_libs['community']))
						$available_libs['community'][$library['Community']] = array();
					if ($library['Realm']) {
						if (!array_key_exists('realm', $available_libs['community'][$library['Community']]))
							$available_libs['community'][$library['Community']]['realm'] = array();
						if (!array_key_exists($library['Realm'], $available_libs['community'][$library['Community']]['realm']))
							$available_libs['community'][$library['Community']]['realm'][$library['Realm']] = array();
				
						$available_libs['community'][$library['Community']]['realm'][$library['Realm']][] = $library;
					}
					else {
						$available_libs['community'][$library['Community']][] = $library;
					}
				}
				else {
					$available_libs[] = $library;
				}
			}
		}
		return $this->render('DocovaBundle:Default:dlgSubscriptions.html.twig', array(
			'subscribed_libs' => $subscribed_libs,
			'available_libs' => $available_libs,
			'user' => $this->user
		));
	}
	
	public function getAvailableLibrariesAction(Request $request)
	{
		$type = $request->query->get('for');
		if (!empty($type) && $type === 'libgroup') {
			return $this->getLibrariesXml();
		}
		return $this->getLibrariesHtml('available', false);
	}
	
	public function getLibrarySelectedXSLAction()
	{
		$xsl_str = <<<XSL
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/TR/WD-xsl">
	<xsl:template match="/">
		<xsl:apply-templates/>
	</xsl:template>
	<xsl:template match="Libraries">
		<xsl:apply-templates select="Library"/>
	</xsl:template>
	<xsl:template match="Library[Modified='1' and Selected='-1']">
		<DocKey>
			<xsl:value-of select="DocKey"/>
		</DocKey>
	</xsl:template>
</xsl:stylesheet>
XSL;
		$response = new Response($xsl_str);
		$response->headers->set('Content-Type', 'text/xml');
		
		return $response;
	}
	
	public function getUserSubscriptionsAction()
	{
		return $this->getLibrariesHtml('subscribed', true);
	}

	/**
	 * This is used by Dialog Picker on load for dialog
	 */
	public function getUsernamesListAction(Request $request)
	{
		$this->initialize ();
		$count = 0;
		$em = $this->getDoctrine()->getManager();
		if ($request->isMethod('POST'))
		{
			$output = array();
			$directory = $request->request->get('directory');
			$searchfor = urldecode($request->request->get('searchfor'));
			$page = $request->request->get('page');
			if (!empty($directory) && is_numeric($directory) && empty($page))
			{
				if ($directory == '1')
				{
					if (!empty($searchfor))
					{
						$users = $em->getRepository('DocovaBundle:UserAccounts')->searchUsernameAbbreviated($searchfor, true, 25);
						$groups = $em->getRepository('DocovaBundle:UserRoles')->searchForGroup($searchfor);
					}
					else {
						$users = $em->getRepository('DocovaBundle:UserAccounts')->findBy(array('Trash' => false), array('userNameDnAbbreviated' => 'ASC'), 25);
						$groups = $em->getRepository('DocovaBundle:UserRoles')->getAllValidGroups();
					}
					if (!empty($users))
					{
						$output['pcount'] = count($users);
						foreach ($users as $user)
						{
							$output['users'][] = array(
								'UserNameDnAbbreviated' => $user->getUserNameDnAbbreviated(),
								'DisplayName' => $user->getUserProfile()->getDisplayName(),
								'UserType' => $user->getUserProfile()->getAccountType()
							);
						}
					}
					if (!empty($groups))
					{
						$output['gcount'] = count($groups);
						foreach ($groups as $gp)
						{
							$output['groups'][] = array(
								'GroupName' => (!$gp->getGroupType() ? $gp->getDisplayName().'/DOCOVA' : $gp->getDisplayName()),
								'GroupType' => $gp->getGroupType()
							);
						}
					}
				}
				else {
					if (!empty($searchfor))
					{
						$ldap_obj = new adLDAP(array(
							'domain_controllers' => $this->global_settings->getLDAPDirectory(),
							'domain_port' => $this->global_settings->getLDAPPort(),
							'base_dn' => $this->global_settings->getLdapBaseDn(),
							'ad_username' => $this->container->getParameter('ldap_username'),
							'ad_password' => $this->container->getParameter('ldap_password')
						));
	
						$searchfor = str_replace('\\', '', $searchfor);
						
						$filter = "(&(objectclass=person)(|(givenname=*" . $searchfor . "*)(sn=*" . $searchfor . "*)(cn=".$searchfor."*)))";
						$users_list = $ldap_obj->search_user($filter);
						if (!empty($users_list))
						{
							$count = 0;
							for($x = 0; $x < count($users_list); $x++)
							{
								if (!empty($users_list [$x] ))
								{
									if (empty($users_list[$x]['uid']) && empty($users_list[$x]['samaccountname'])) {
										continue;
									}

									$user_dn = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $users_list[$x]['dn']);
									$username = explode('=', $user_dn[0]);
									$strRaw_dn = $users_list[$x]['dn'];
									$strAbbDnName = $this->getUserAbbreviatedName($strRaw_dn);
									
									$count++;
									$output['users'][] = array(
										'UserNameDnAbbreviated' => $strAbbDnName,
										'DisplayName' => $username[1],
										'UserType' => false
									);
								}
							}
							$output['count'] = $count;
						}
					}
				}
			}
			elseif (!empty($page) && !is_numeric($directory)) {
				if ($directory == 'users')
				{
					$users = $em->getRepository('DocovaBundle:UserAccounts')->findBy(array('Trash' => false), array('userNameDnAbbreviated' => 'ASC'), 25, ($page - 1) * 25);
					if (!empty($users))
					{
						$output['count'] = count($users);
						foreach ($users as $user)
						{
							$output['users'][] = array(
								'UserNameDnAbbreviated' => $user->getUserNameDnAbbreviated(),
								'DisplayName' => $user->getUserProfile()->getDisplayName(),
								'UserType' => $user->getUserProfile()->getAccountType()
							);
						}
					}
				}
				else {
					$groups = $em->getRepository('DocovaBundle:UserRoles')->getAllValidGroups(25, ($page - 1) * 25);
					if (!empty($groups)) {
						$output['count'] = count($groups);
						foreach ($groups as $gp) {
							$output['groups'][] = array(
								'GroupName' => (!$gp->getGroupType() ? $gp->getDisplayName().'/DOCOVA' : $gp->getDisplayName()),
								'GroupType' => $gp->getGroupType()
							);
						}
					}
				}
			}

			$response = new Response();
			$response->headers->set('Content-Type', 'application/json');
			$response->setContent(json_encode($output));
			return $response;
		}
		$users = $em->getRepository('DocovaBundle:UserAccounts')->findBy(array('Trash' => false), array('userNameDnAbbreviated' => 'ASC'), 25);
		$groups = $em->getRepository('DocovaBundle:UserRoles')->getAllValidGroups();
		$total = $em->getRepository('DocovaBundle:UserAccounts')->getCountBy(array('Trash' => false));
	
		return $this->render ( 'DocovaBundle:Default:dlgNameLookup.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'isLdapEnabled' => $this->global_settings->getLDAPAuthentication() && $this->global_settings->getLDAPDirectory() ? true : false,
			'users_list' => $users,
			'groups' => $groups,
			'total_count' => $total
		));
	}
	
	public function getUsernamesListJSONAction(Request $request)
	{
		$this->initialize ();
		$em = $this->getDoctrine()->getManager();
		$output = array();
		$hasldap = $this->global_settings->getLDAPAuthentication();

		if ($request->isMethod('POST'))
		{
			$searchfor = urldecode($request->request->get('searchfor'));
			if (!empty($searchfor))
			{
				$users = $em->getRepository('DocovaBundle:UserAccounts')->searchUser($searchfor);
				$groups = $em->getRepository('DocovaBundle:UserRoles')->searchForGroup($searchfor);
				if (!empty($groups))
				{
					foreach ($groups as $gp)
					{
						$users[] = array(
							'userNameDnAbbreviated' => (!$gp->getGroupType() ? $gp->getDisplayName().'/DOCOVA' : $gp->getDisplayName()),
							'Display_Name' => (!$gp->getGroupType() ? $gp->getDisplayName().'/DOCOVA' : $gp->getDisplayName()),
							'type' => 'group'
						);
					}
				}

				if ( $hasldap == "1")
				{
					$ldap_obj = new adLDAP(array(
							'domain_controllers' => $this->global_settings->getLDAPDirectory(),
							'domain_port' => $this->global_settings->getLDAPPort(),
							'base_dn' => $this->global_settings->getLdapBaseDn(),
							'ad_username' => $this->container->getParameter('ldap_username'),
							'ad_password' => $this->container->getParameter('ldap_password')
					));
		
					$searchfor = str_replace('\\', '', $searchfor);
					$filter = "(&(objectclass=person)(|(givenname=*" . $searchfor . "*)(sn=*" . $searchfor . "*)(cn=".$searchfor."*)))";
					$users_list = $ldap_obj->search_user($filter);
					if (!empty($users_list))
					{
						for($x = 0; $x < count($users_list); $x++)
						{
							if (!empty($users_list [$x] ))
							{
								if (empty($users_list[$x]['uid']) && empty($users_list[$x]['samaccountname'])) {
									continue;
								}
		
							$user_dn = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $users_list[$x]['dn']);							
								$username = explode('=', $user_dn[0]);
								$strRaw_dn = $users_list[$x]['dn'];
								$strAbbDnName = $this->getUserAbbreviatedName($strRaw_dn);
								
								$users[] = array(
									'userNameDnAbbreviated' => $strAbbDnName,
									'Display_Name' => $username[1],
									'type' => 'person'
								);
							}
						}
					}
				}
			}
			
			if (!empty($users[0]))
			{
				foreach ($users as $key => $value) {
					if (true === $this->arrayKeyContain($users, 'userNameDnAbbreviated', $value['userNameDnAbbreviated'], $key)) {
						$users[$key] = null;
					}
				}
				$output['users'] = array_values(array_filter($users));
			}
		}

		$output['count'] = count($output['users']);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($output));
		return $response;
	}

	public function getSubscribedLibrariesAction($output)
	{
		$this->initialize();
		$response = new Response();
		$em = $this->getDoctrine()->getManager();
		$libraries = $em->getRepository('DocovaBundle:Libraries')->getUserLibraries($this->user, true, array('Library_Title' => 'ASC'));
		$content = $output === 'xml' ? '<Libraries>' : '';
		$nsf = $this->generateUrl('docova_homepage');
		foreach ($libraries as $library) {
			if ($output === 'xml') {
				$content .= '<Library><Server></Server>';
				$content .= '<NsfName>'.$nsf.'</NsfName>';
				$content .= "<Title><![CDATA[{$library['Library_Title']}]]></Title>";
				$content .= "<Description><![CDATA[{$library['Description']}]]></Description>";
				$content .= "<DocKey>{$library['id']}</DocKey>";
				$content .= "<Unid>{$library['id']}</Unid><Selected>0</Selected><Modified>0</Modified>";
				$content .= '<DocAuthors><![CDATA[[Administration], ]]></DocAuthors></Library>';
			}
			else {
				$content .= '<option value="'.$library['Library_Title'].'">'.$library['Library_Title'].'</option>';
			}
		}
		unset($libraries, $library);
		$content .= $output === 'xml' ? '</Libraries>' : '';
	
		$response->setContent($content);
		return $response;
	}
	
	
	/**
	 * Create XML of the libraries according to being subscribed or not
	 * 
	 * @param string $type
	 * @param boolean $subscribed
	 * @return Response
	 */
	private function getLibrariesHtml($type, $subscribed = true)
	{
		$result_libs = array();
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$libraries = $em->getRepository('DocovaBundle:Libraries')->getUserLibraries($this->user, $subscribed, array('Community' => 'ASC', 'Realm' => 'ASC', 'Library_Title' => 'ASC'));
		foreach ($libraries as $library) {
			if ($library['Community']) {
				if (!array_key_exists('community', $result_libs))
					$result_libs['community'] = array();
				if (!array_key_exists($library['Community'], $result_libs['community']))
					$result_libs['community'][$library['Community']] = array();
				if ($library['Realm']) {
					if (!array_key_exists('realm', $result_libs['community'][$library['Community']]))
						$result_libs['community'][$library['Community']]['realm'] = array();
					if (!array_key_exists($library['Realm'], $result_libs['community'][$library['Community']]['realm']))
						$result_libs['community'][$library['Community']]['realm'][$library['Realm']] = array();
	
					$result_libs['community'][$library['Community']]['realm'][$library['Realm']][] = $library;
				}
				else {
					$result_libs['community'][$library['Community']][] = $library;
				}
			}
			else {
				$result_libs[] = $library;
			}
		}
		
		return $this->render('DocovaBundle:Default:communityRealmLibraries.html.twig', array(
				'libraries' => $result_libs,
				'type' => $type
		));
	}
	
	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	private function getLibrariesXml()
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$xml = '<?xml version="1.0" encoding="UTF-8" ?><Libraries>';
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$libraries = $em->getRepository('DocovaBundle:Libraries')->getAllVisibleLibraries($this->user);
		if (!empty($libraries))
		{
			foreach ($libraries as $library) {
				$xml .= '<Library><Title><![CDATA['.$library['Library_Title'].']]></Title>';
				$xml .= '<Server></Server><NsfName></NsfName><Description><![CDATA['.$library['Description'].']]></Description>';
				$xml .= '<DocKey>'.$library['id'].'</DocKey><Unid>'.$library['id'].'</Unid>';
				$xml .= '<Selected>0</Selected><Modified>0</Modified><DocAuthors><![CDATA[Administration]]></DocAuthors>';
				$xml .= '</Library>';
			}
		}
		$xml .= '</Libraries>';
		$response->setContent($xml);
		return $response;
	}
	
	/**
	 * Check if element value exists in an array base on a key
	 * 
	 * @param array $array
	 * @param string $key
	 * @param string $value
	 * @param integer $not
	 * @return boolean
	 */
	private function arrayKeyContain($array, $key, $value, $not)
	{
		foreach ($array as $index => $val) 
		{
			if ($index != $not && $val[$key] === $value) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Convert user name to abbreviated format
	 *
	 * @param string $userDnName
	 * @return string
	 */	
	private function getUserAbbreviatedName($userDnName)
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
	        var_dump("UserController::getUserAbbreviatedName() exception".$e->getMessage());
	    }
	    
	    
	    return $strAbbDnName;
	}
	
	
}
