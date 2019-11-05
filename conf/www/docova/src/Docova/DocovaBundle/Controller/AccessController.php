<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Entity\UserRoles;
use Docova\DocovaBundle\Entity\AppAcl;
use Docova\DocovaBundle\Extensions\MiscFunctions;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Controller class to handle access services
 * @author javad_rahimi
 */
class AccessController extends Controller 
{
	private $user;
	private $_docova;
	private $global_settings;
	
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
		
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
		
		$this->_docova = new Docova($this->container);
	}

	public function getAccessServiceAction(Request $request)
	{
		$post_req = $request->getContent();
		if (empty($post_req)) {
			throw $this->createNotFoundException('No POST data was submitted.');
		}
	
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$post_xml = new \DOMDocument();
		$post_xml->loadXML($post_req);
		$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
		if (empty($action)) {
			throw $this->createNotFoundException('No ACTION was found in POST request');
		}
	
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$this->initialize();
	
		if (method_exists($this, 'getAccess'.$action))
		{
			$result_xml = call_user_func(array($this, 'getAccess'.$action), $post_xml);
		}
	
		return $response->setContent($result_xml->saveXML());
	}
	
	/**
	 * Return current user's access level for the selected document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function getAccessQUERYACCESS($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$accessl_level = 0;
		
		try {
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document));
			if (!empty($document)) {
				$security_context = $this->container->get('security.authorization_checker');
				if ($security_context->isGranted('ROLE_ADMIN') || $security_context->isGranted('MASTER', $document->getFolder()->getLibrary())) {
					$accessl_level = 7;
				}
				else {
					$custom_acl = new CustomACL($this->container);
					$editors = $custom_acl->getObjectACEUsers($document, 'edit');
					$feditors = $custom_acl->getObjectACEUsers($document->getFolder(), 'create');
					if (!empty($feditors)) {
						foreach ($feditors as $fe) {
							if (!$editors->contains($fe)) {
								$editors->add($fe);
							}
						}
					}
					$feditors = $fe = null;
					
					$managers = $custom_acl->getObjectACEUsers($document->getFolder(), 'owner');
					$emanagers = $custom_acl->getObjectACEUsers($document->getFolder(), 'master');
					if (!empty($emanagers)) {
						foreach ($emanagers as $m) {
							if (!$managers->contains($m)) {
								$managers->add($m);
							}
						}
					}
					$emanagers = $m = null;
					
					if (!empty($managers)) {
						foreach ($managers as $m) {
							if (false !== ($user = $this->findUser($m->getUsername())) && $user->getUserNameDnAbbreviated() == $this->user->getUserNameDnAbbreviated()) {
								$accessl_level = 6;
								break;
							}
						}
						$m = $managers = $user = null;
					}
					
					if (empty($accessl_level) && !empty($editors)) {
						foreach ($editors as $e) {
							if (false !== ($user = $this->findUser($e->getUsername())) && $user->getUserNameDnAbbreviated() == $this->user->getUserNameDnAbbreviated()) {
								$accessl_level = 3;
								break;
							}
						}
						$e = $editors = $user = null;
					}
					$custom_acl = $security_context = null;
									
					if (empty($accessl_level)) {
						$security_context = new Miscellaneous($this->container);
						if (true === $security_context->canReadDocument($document, true)) {
							$accessl_level = 2;
						}
					}
				}
				
				$security_context = $document = $em = null;
			}
			else {
				$accessl_level = 99;
			}
		}
		catch(\Exception $e) {
			$accessl_level = 0;
		}
		
		$node = $result_xml->createElement('Result', 'OK');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Ret1';
		$node = $result_xml->createElement('Result', $accessl_level);
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Get app ACL list
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function getAccessGETACLLIST($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$app = $post_xml->getElementsByTagName('Application')->item(0)->nodeValue;
		$acl_list = '';
		$complete = false;
		try {
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application source ID.');
			}
			
			$list = '-Default-|-Default-~0:';
			$role_list = array();
			
			$displayfullname = $this->global_settings->getUserDisplayDefault();
			
			$aclentries = $em->getRepository('DocovaBundle:AppAcl')->findBy(array('application' => $app->getId()));
			if(!empty($aclentries) && count($aclentries) > 0){
			    foreach ($aclentries as $aclentry) {
			        if(!is_null($user = $aclentry->getUserObject())){
		                $list .= ($displayfullname ? $user->getUserNameDnAbbreviated() : $user->getUserProfile()->getDisplayName()).'|'.$user->getUserNameDnAbbreviated().'~1:';
			        }else if(!is_null($group = $aclentry->getGroupObject())){
			            if (!$group->getApplication() && $group->getRole() != 'ROLE_USER' && $group->getRole() != 'ROLE_ADMIN') {
			                $list .= $group->getDisplayName().'|'.$group->getRole().'~4:';
			            }
			        }
			    }
			}
			
			$app_roles = $em->getRepository('DocovaBundle:UserRoles')->findBy(array('application' => $app->getId()));
			if (!empty($app_roles))
			{
				foreach ($app_roles as $role)
				{
					$role_list[] = $role->getDisplayName().'|'.$role->getRole();
				}
			}
			$acl_list = substr_replace($list, '', -1);
			if (!empty($role_list)) {
				$acl_list .= ';' . implode(':', $role_list);
			}
			$complete = true;
		}
		catch (\Exception $e) {
			$complete = false;
			$acl_list = $e->getMessage();
		}
	
		if ($complete === true) {
			$node = $result_xml->createElement('Result', 'OK');
			$attrib	= $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$node->appendChild($attrib);
			$root->appendChild($node);
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'Ret1';
			$node = $result_xml->createElement('Result', $acl_list);
			$node->appendChild($attrib);
			$root->appendChild($node);
		}
		else {
			$node = $result_xml->createElement('Result', 'FAILED');
			$attrib	= $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$node->appendChild($attrib);
			$root->appendChild($node);
		}
		
		return $result_xml;
	}
	
	/**
	 * Get ACL properties and details base on selected entry
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function getAccessGETACLENTRYPROPERTIES($post_xml)
	{
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$xml = '<Results>';
		if (!empty($post_xml->getElementsByTagName('AppID')->item(0)->nodeValue) && !empty($post_xml->getElementsByTagName('EntryName')->item(0)->nodeValue))
		{
			$acl_arr = $acl_roles = array();
			$app = $post_xml->getElementsByTagName('AppID')->item(0)->nodeValue;
			$entry = $post_xml->getElementsByTagName('EntryName')->item(0)->nodeValue;
			$type = $post_xml->getElementsByTagName('EntryType')->item(0)->nodeValue;
			if ($entry == '-Default-') {
				$entry = 'ROLE_USER';
				$type = 'group';
			}
			else {
				$type = empty($type) ? 'person' : $type;
			}
			$em = $this->getDoctrine()->getManager();
			$acl = new CustomACL($this->container);
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($app))
			{
				$xml .= '<Result ID="Status">FAILED</Result><Result ID="ErrMsg"><![CDATA[Unspecified application source ID.]]></Result></Results>';
				$result_xml->loadXML($xml);
				return $result_xml;
			}
			
			if ($type == 'person')
			{
				$acl_arr[] = 1; //indicates person type
				
				$access_level = 0;
				$user = $this->findUser($entry, true, false, true);
				if (!empty($user))
				{
					$masks = $acl->getUserMasks($app, $user);
					if (!empty($masks[0]))
					{
						switch ($masks[0])
						{
							case 'owner':
								$access_level = 6;
								break;
							case 'master':
								$access_level = 5;
								break;
							case 'operator':
								$access_level = 4;
								break;
							case 'edit':
								$access_level = 3;
								break;
							case 'view':
								$access_level = 2;
								break;
							default:
								$access_level = 0;
								break;
						}
					}
										
					$app_acl = $em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $app->getId(), 'userObject' => $user->getId()));
					if(!empty($app_acl && $app_acl->getNoAccess())){
					    $access_level = 0;  //override access if set in acl as no access					    
					}
					$acl_arr[] = $access_level;  //set access level
					
					//set can create flag
				    	$acl_arr[] = ((!empty($app_acl) && $app_acl->getCreateDocument()) ? 'True' : 'False');
					
					//set can delete flag
				   	 $acl_arr[] = ((!empty($app_acl) && $app_acl->getDeleteDocument()) ? 'True' : 'False');
					
					$app_groups = $em->getRepository('DocovaBundle:UserRoles')->getAppUserGroups($app->getId(), $user->getId());
					if (!empty($app_groups))
					{
						foreach ($app_groups as $g) {
							$acl_roles[] = $g['displayName'].'|'.$g['role'];
						}
					}
				}
			}
			else {
				$acl_arr[] = 2;  //indicates group type
				
				$access_level = 0;
				$group = $this->findGroup($entry);
				if (!empty($group))
				{
					$masks = $acl->getGroupMasks($app, $group->getRole());
					if (!empty($masks[0]))
					{
						switch ($masks[0])
						{
							case 'owner':
								$access_level = 6;
								break;
							case 'master':
							    $access_level = 5;
								break;
							case 'operator':
							    $access_level = 4;
								break;
							case 'edit':
							    $access_level = 3;
								break;
							case 'view':
							    $access_level = 2;
								break;
							default:
							    $access_level = 0;
								break;
						}
					}
					
					$app_acl = $em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $app->getId(), 'groupObject' => $group->getId()));
					if(!empty($app_acl && $app_acl->getNoAccess())){
					    $access_level = 0;  //override access if set in acl as no access
					}
					$acl_arr[] = $access_level;  //set access level
					
					//set can create flag
					$acl_arr[] = ((!empty($app_acl) && $app_acl->getCreateDocument()) ? 'True' : 'False');
					
					//set can delete flag
					$acl_arr[] = ((!empty($app_acl) && $app_acl->getDeleteDocument()) ? 'True' : 'False');

					if ($group->getRole() !== 'ROLE_USER' && $group->getRole() !== 'ROLE_ADMIN')
					{
						$app_roles = $em->getRepository('DocovaBundle:UserRoles')->getAppRoles($app->getId(), $group->getGroupName());
					}
					else {
						$app_roles = $em->getRepository('DocovaBundle:UserRoles')->getAppRoles($app->getId(), $group->getDisplayName());
					}
					if (!empty($app_roles))
					{
						foreach ($app_roles as $g) {
							$acl_roles[] = $g['displayName'].'|'.$g['role'];
						}
					}
				}
			}
			if (!empty($acl_roles)) {
				$acl_roles = implode(',', $acl_roles);
				$acl_arr[] = $acl_roles;
			}
		}
		if (!empty($acl_arr))
		{
			$xml .= '<Result ID="Status">OK</Result><Result ID="Ret1">'.implode(';', $acl_arr).'</Result>';
		}
		$xml .= '</Results>';
		
		$result_xml->loadXML($xml);
		return $result_xml;
	}
	
	/**
	 * Add application ACL role
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function getAccessADDACLROLE($post_xml)
	{
		$result = new \DOMDocument('1.0', 'UTF-8');
		$xml = '<Results>';
		$app = $post_xml->getElementsByTagName('Application')->item(0)->nodeValue;
		$role = $post_xml->getElementsByTagName('RoleName')->item(0)->nodeValue;
		try {
			if (empty($role))
				throw new \Exception('Role name is missed');
			
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($app)) 
				throw new \Exception('Unspecified application source ID');
			
			$docovaAcl = $this->_docova->DocovaAcl($app);
			if (!$docovaAcl->isManager())
				throw new \Exception('Access denied.');
			$docovaAcl = null;
			
			$app_role = new UserRoles();
			$app_role->setApplication($app);
			$app_role->setDisplayName($role);
			$miscfunctions = new MiscFunctions();
			$app_role->setRole('ROLE_APP'.$miscfunctions->generateGuid("UPPERCASE"));
			$em->persist($app_role);
			$em->flush();
			
			$xml .= '<Result ID="Status">OK</Result>';
			$xml .= '<Result ID="Ret1"><![CDATA['.$app_role->getId().']]></Result>';
		}
		catch (\Exception $e) {
			$xml .= '<Result ID="Status">FAILED</Result>';
			$xml .= '<Result ID="ErrMsg"><![CDATA['.$e->getMessage().']]></Result>';
		}
		$xml .= '</Results>';

		$result->loadXML($xml);
		return $result;
	}
	
	/**
	 * Set app ACL properties for an entry
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function getAccessSETACLENTRYPROPERTIES($post_xml)
	{
		$result = new \DOMDocument('1.0', 'UTF-8');
		$xml = '<Results>';
		$app = $post_xml->getElementsByTagName('Application')->item(0)->nodeValue;
		$entry = $post_xml->getElementsByTagName('EntryName')->item(0)->nodeValue;
		$type = $post_xml->getElementsByTagName('UserType')->item(0)->nodeValue;
		$access = $post_xml->getElementsByTagName('AccessType')->item(0)->nodeValue;
		$options = array();
		$options['createDocument'] = false !== stripos($post_xml->getElementsByTagName('AccessOptions')->item(0)->nodeValue, 'Create documents') ? true : false;
		$options['deleteDocuments'] = false !== stripos($post_xml->getElementsByTagName('AccessOptions')->item(0)->nodeValue, 'Delete documents') ? true :false;
		$roles = explode(';', $post_xml->getElementsByTagName('RolesList')->item(0)->nodeValue);
		if ($entry == '-Default-') {
			$entry = 'ROLE_USER';
			$type = 2;
		}
		else {
			$type = empty($type) ? 1 : $type;
		}
		switch ($access)
		{
			case 0:
				$access = null;
				break;
			case 2:
				$access = 'view';
				break;
			case 3:
				$access = 'edit';
				break;
			case 4:
				$access = 'operator';
				break;
			case 5:
				$access = 'master';
				break;
			case 6:
				$access = 'owner';
				break;
		}
		$em = $this->getDoctrine()->getManager();
		try {
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application source ID.');
			}
			$docovaAcl = $this->_docova->DocovaAcl($app);
			if (!$docovaAcl->isManager())
				throw new \Exception('Access denied.');
			$docovaAcl = null;

			if ($type == 1)
			{
				$user = $this->findUser($entry, true, false, true);
				if (empty($user))
					throw new \Exception('Selected person cannot be found.');
				$acl = new CustomACL($this->container);
				$acl->removeUserACE($app, $user);
				if (!empty($access)) {
					$acl->insertObjectAce($app, $user, $access, false);
				}
				elseif ($acl->isUserGranted($app, $user, 'owner')) {
					$acl->removeUserACE($app, $user, ['owner']);
				}
				
				$isNew = false;
				$acl_property = $em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $app->getId(), 'userObject' => $user->getId()));
				if (empty($acl_property)) {
					$acl_property = new AppAcl();
					$acl_property->setApplication($app);
					$acl_property->setUserObject($user);
					$isNew = true;
				}
				$acl_property->setNoAccess(empty($access));
				$acl_property->setCreateDocument($options['createDocument']);
				$acl_property->setDeleteDocument($options['deleteDocuments']);
				$acl_property->setGroupObject(null);
				if ($isNew === true)
					$em->persist($acl_property);
				$em->flush();
				
				$app_roles = $em->getRepository('DocovaBundle:UserRoles')->getAppUserGroups($app->getId(), $user->getId(), true);
				if (!empty($app_roles) && count($app_roles))
				{
					foreach ($app_roles as $ar) {
						$ar->removeRoleUsers($user);
					}
					$em->flush();
				}
				
				if (!empty($roles))
				{
					$added = false;
					foreach ($roles as $r) {
						$role = $this->findGroup($r);
						if (!empty($role)) {
							$role->addRoleUsers($user);
							$added = true;
						}
					}
					if ($added === true)
						$em->flush();
				}
				
				$xml .= '<Result ID="Status">OK</Result>';
				$xml .= '<Result ID="Ret1">SUCCESS</Result>';
			}
			else {
				$entry = false !== strpos($entry, '/') ? strstr($entry, '/', true) : $entry;
				$group = $this->findGroup($entry);
				if (empty($group))
					throw new \Exception('Selected group cannot be found.');
				
				$acl = new CustomACL($this->container);
				$acl->removeUserACE($app, $group->getRole(), null, true);
				if (!empty($access)) {
					$acl->insertObjectAce($app, $group->getRole(), $access);
				}
				elseif ($acl->isRoleGranted($app, $group->getRole(), 'owner')) {
					$acl->removeUserACE($app, $group->getRole(), 'owner', true);
				}
				
				$isNew = false;
				$acl_property = $em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $app->getId(), 'groupObject' => $group->getId()));
				if (empty($acl_property)) {
					$acl_property = new AppAcl();
					$acl_property->setApplication($app);
					$acl_property->setGroupObject($group);
					$isNew = true;
				}
				$acl_property->setNoAccess(empty($access));
				$acl_property->setCreateDocument($options['createDocument']);
				$acl_property->setDeleteDocument($options['deleteDocuments']);
				$acl_property->setUserObject(null);
				if ($isNew === true)
					$em->persist($acl_property);
				$em->flush();

//				if ($group->getRole() !== 'ROLE_USER' && $group->getRole() !== 'ROLE_ADMIN')
//				{
					$app_roles = $em->getRepository('DocovaBundle:UserRoles')->getAppRoles($app->getId(), null, true);
					if (!empty($app_roles) && count($app_roles))
					{
						foreach ($app_roles as $ar) {
							$nested_groups = $ar->getNestedGroups();
							if (!empty($nested_groups) && (in_array($group->getGroupName(), $nested_groups) || in_array($group->getDisplayName(), $nested_groups)))
							{
								if ($group->getRole() !== 'ROLE_USER' && $group->getRole() !== 'ROLE_ADMIN' && $group->getRoleUsers()->count() > 0)
								{
									//remove all the members of this group from the role members
									foreach ($group->getRoleUsers() as $ru)
										$ar->removeRoleUsers($ru);
								}
								if ($group->getRole() !== 'ROLE_USER' && $group->getRole() !== 'ROLE_ADMIN')
								{
									unset($nested_groups[array_search($group->getGroupName(), $nested_groups)]);
									$nested_groups = array_values($nested_groups);
									$ar->setNestedGroups($nested_groups);
								}
								else {
									unset($nested_groups[array_search($group->getDisplayName(), $nested_groups)]);
									$nested_groups = array_values($nested_groups);
									$ar->setNestedGroups($nested_groups);
								}
							}
						}
						$em->flush();
					}
				
					if (!empty($roles))
					{
						$members = $group->getRoleUsers();
						$added = false;
						foreach ($roles as $r) {
							$role = $this->findGroup($r);
							if (!empty($role)) {
								$nested_groups = $role->getNestedGroups();
								if (empty($nested_groups) || !(in_array($group->getGroupName(), $nested_groups) || in_array($group->getDisplayName(), $nested_groups)))
								{
									if ($group->getRole() !== 'ROLE_USER' && $group->getRole() !== 'ROLE_ADMIN')
										$nested_groups[] = $group->getGroupName();
									else 
										$nested_groups[] = $group->getDisplayName();
									$role->setNestedGroups($nested_groups);
									$added = true;
								}
								
								if ($group->getRole() !== 'ROLE_USER' && $group->getRole() !== 'ROLE_ADMIN' && $members->count() > 0)
								{
									foreach ($members as $user) {
										if (!$role->getRoleUsers()->contains($user)) {
											$role->addRoleUsers($user);
											$added = true;
										}
									}
								}
							}
						}
						if ($added === true)
							$em->flush();
					}
//				}
				$xml .= '<Result ID="Status">OK</Result>';
				$xml .= '<Result ID="Ret1">SUCCESS</Result>';
			}
		}
		catch (\Exception $e) {
			$xml = '<Results><Result ID="Status">FAILED</Result>';
			$xml .= '<Result ID="ErrMsg"><![CDATA['.$e->getMessage().' on line '.$e->getLine().' in file '.$e->getFile().' Trace: '. $e->getTraceAsString() .']]></Result>';
		}
		$xml .= '</Results>';
		$result->loadXML($xml);
		return $result;
	}
	
	/**
	 * Add new app ACL entry
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function getAccessADDACLENTRY($post_xml)
	{
		$entry = $post_xml->getElementsByTagName('EntryName')->item(0)->nodeValue;
		if ($entry == '-Default-' || $entry == 'ROLE_USER' || $entry == 'ROLE_ADMIN')
		{
			$result = new \DOMDocument('1.0', 'UTF-8');
			$result->loadXML('<Results><Result ID="Status">FAILED</Result><Result ID="ErrMsg"><![CDATA[Invalid entry]]></Result></Results>');
			return $result;
		}
		
		$error = '';
		$em = $this->getDoctrine()->getManager();
		$app = $post_xml->getElementsByTagName('Application')->item(0)->nodeValue;
		$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		if(empty($app))
			$error = 'Unspecified application source ID.';
		else {
			$docovaAcl = $this->_docova->DocovaAcl($app);
			if (!$docovaAcl->isManager())
				$error = 'Access denied.';
			$docovaAcl = null;
		}
		if (!empty($error))
		{
			$result = new \DOMDocument('1.0', 'UTF-8');
			$result->loadXML('<Results><Result ID="Status">FAILED</Result><Result ID="ErrMsg"><![CDATA['.$error.']]></Result></Results>');
			return $result;
		}
		$app = $em = $error = null;
		$entry = $post_xml->getElementsByTagName('EntryName')->item(0)->nodeValue;
		$type = $post_xml->getElementsByTagName('UserType')->item(0)->nodeValue;

		if (!empty($entry) && $type == 1 && $entry != '-Default-' && $entry != 'ROLE_USER')
		{
			//we call find user to create the profile if it doesn't exist
			$this->findUser($entry, true, true, true);
		}
		
		$result = $this->getAccessSETACLENTRYPROPERTIES($post_xml);
		return $result;
	}
	
	/**
	 * Remove app ACL role
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function getAccessREMOVEACLROLE($post_xml)
	{
		$result = new \DOMDocument('1.0', 'UTF-8');
		$role = $post_xml->getElementsByTagName('RoleName')->item(0)->nodeValue;
		$app = $post_xml->getElementsByTagName('Application')->item(0)->nodeValue;
		$xml = '<Results>';
		try {
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($app))
				throw new \Exception('Unspecified application source ID.');

			$docovaAcl = $this->_docova->DocovaAcl($app);
			if (!$docovaAcl->isManager())
				throw new \Exception('Access denied.');
			$docovaAcl = null;

			$role = $this->findGroup($role);
			if (empty($role) || !$role->getApplication() || $role->getApplication()->getId() != $app->getId())
				throw new \Exception('Selected role cannot be found.');
			
			$members = $role->getRoleUsers();
			if (!empty($members) && $members->count() > 0)
			{
				foreach ($members as $user) {
					$role->removeRoleUsers($user);
				}
			}
			$em->remove($role);
			$em->flush();
			
			$xml .= '<Result ID="Status">OK</Result>';
			$xml .= '<Result ID="Ret1">SUCCESS</Result>';
			$members = $em = $role = null;
		}
		catch (\Exception $e) {
			$xml = '<Results><Result ID="Status">FAILED</Result>';
			$xml .= '<Result ID="ErrMsg"><![CDATA['.$e->getMessage().']]></Result>';
		}
		$xml .= '</Results>';
		$result->loadXML($xml);
		return $result;
	}
	
	/**
	 * Remove app ACL entry
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function getAccessREMOVEACLENTRY($post_xml)
	{
		$result = new \DOMDocument('1.0', 'UTF-8');
		$xml = '<Results>';
		$entry = $post_xml->getElementsByTagName('EntryName')->item(0)->nodeValue;
		$app = $post_xml->getElementsByTagName('Application')->item(0)->nodeValue;
		$type = $post_xml->getElementsByTagName('EntryType')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		try {
			if (empty($entry) || $entry == '-Default-' || $entry == 'ROLE_USER' || $entry == 'ROLE_ADMIN')
				throw new \Exception('Invalid entry.');
			
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($app))
				throw new \Exception('Unspecified application source ID');

			$docovaAcl = $this->_docova->DocovaAcl($app);
			if (!$docovaAcl->isManager())
				throw new \Exception('Access denied.');
			$docovaAcl = null;

			if ($type == 1) {
				$user = $this->findUser($entry, true, false, true);
				if (empty($user))
					throw new \Exception('Selected person cannot be found.');
				$acl = new CustomACL($this->container);
				$acl->removeUserACE($app, $user);
				$acl_property = $em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $app->getId(), 'userObject' => $user->getId()));
				if (!empty($acl_property))
				{
					$em->remove($acl_property);
					$em->flush();
				}

				$app_roles = $em->getRepository('DocovaBundle:UserRoles')->getAppUserGroups($app->getId(), $user->getId(), true);
				if (!empty($app_roles) && count($app_roles))
				{
					foreach ($app_roles as $ar) {
						$ar->removeRoleUsers($user);
					}
					$em->flush();
				}
				
				$xml .= '<Result ID="Status">OK</Result>';
				$xml .= '<Result ID="Ret1">SUCCESS</Result>';
			}
			else {
				$group = $this->findGroup($entry);
				if (empty($group))
					throw new \Exception('Selected group cannot be found.');
				
				$acl = new CustomACL($this->container);
				$acl->removeUserACE($app, $group->getRole(), null, true);
				$acl_property = $em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $app->getId(), 'groupObject' => $group->getId()));
				if (!empty($acl_property))
				{
					$em->remove($acl_property);
					$em->flush();
				}

				$app_roles = $em->getRepository('DocovaBundle:UserRoles')->getAppRoles($app->getId(), null, true);
				if (!empty($app_roles) && count($app_roles))
				{
					foreach ($app_roles as $ar) {
						$nested_groups = $ar->getNestedGroups();
						if (!empty($nested_groups) && in_array($group->getGroupName(), $nested_groups))
						{
							if ($group->getRoleUsers()->count() > 0)
							{
								foreach ($group->getRoleUsers() as $ru)
									$ar->removeRoleUsers($ru);
							}
							unset($nested_groups[array_search($group->getGroupName(), $nested_groups)]);
							$nested_groups = array_values($nested_groups);
							$ar->setNestedGroups($nested_groups);
						}
					}
					$em->flush();
				}
				
				$xml .= '<Result ID="Status">OK</Result>';
				$xml .= '<Result ID="Ret1">SUCCESS</Result>';
			}
		}
		catch (\Exception $e) {
			$xml = '<Results><Result ID="Status">FAILED</Result>';
			$xml .= '<Result ID="ErrMsg"><![CDATA['.$e->getMessage().']]></Result>';
		}
		$xml .= '</Results>';
		$result->loadXML($xml);
		return $result;
	}
	
	/**
	 * Find user object base on username
	 * 
	 * @param string $username
	 * @param boolean $abbreviated_name
	 * @param boolean $create
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUser($username, $abbreviated_name = false, $create = false, $includetrash = false)
	{
		$em = $this->getDoctrine()->getManager();
		
		$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $username, 'Trash' => false));
		if (!empty($user))
		{
			return $user;
		}
		
		if ($abbreviated_name !== false)
		{
			$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $username, 'Trash' => false));
			if (!empty($user))
			{
				return $user;
			}
		}
		
		if($includetrash){
    		$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $username, 'Trash' => true));
    		if (!empty($user))
			{
				return $user;
			}
			
			if ($abbreviated_name !== false)
			{
				$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $username, 'Trash' => true));
				if (!empty($user))
				{
					return $user;
				}
			}
		}

		if ($create === true) {
			$helper = new UtilityHelperTeitr($this->global_settings, $this->container);
			$user = $helper->findUserAndCreate($username, true, $em, false, true);
			return $user;
		}
		
		return false;
	}

	/**
	 * Find group/role in DB
	 *
	 * @param string $role
	 * @return \Docova\DocovaBundle\Entity\UserRoles|boolean
	 */
	private function findGroup($role)
	{
		$em = $this->getDoctrine()->getManager();
		$group = $em->getRepository('DocovaBundle:UserRoles')->findByNameOrRole($role);
		if (!empty($group))
		{
			return $group;
		}
	
		return false;
	}

}