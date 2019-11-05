<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Entity\UserRoles;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserProfile;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Library services and actions are handled here
 * @author javad rahimi
 *        
 */
class LibraryController extends Controller 
{
	protected $user;
	protected $global_settings;
	protected $UPLOAD_FILE_PATH;
	protected $root_path;
	
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
	
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];

		$this->root_path = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root') : $_SERVER['DOCUMENT_ROOT']; 
		$this->UPLOAD_FILE_PATH = $this->root_path.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
	}
	
	public function getDocTypeInfoAction(Request $request)
	{
		$post_req = $request->getContent();
		if (empty($post_req)) {
			throw $this->createNotFoundException('No POST request is submitted.');
		}
		$post_req = urldecode($post_req);
		$this->initialize();
		$xml_result = '<?xml version="1.0" encoding="UTF-8" ?>';
		$dom = new \DOMDocument();
		$dom->loadXML($post_req);
		$action = $dom->getElementsByTagName('Action')->item(0)->nodeValue;
		if (method_exists($this, 'libservices'. $action)) 
		{
			$xml_result = call_user_func(array($this, 'libservices'.$action), $dom);
		}
		$dom = $action = $post_req = null;
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml_result);
		return $response;
	}

	public function getLibraryMembersAction(Request $request)
	{
		$response = new Response();
		$xml = '<?xml version="1.0" encoding="UTF-8"?><documents>';
		$library = $request->query->get('RestrictToCategory');
		$em = $this->getDoctrine()->getManager();
		$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library, 'Trash' => false));
		if (!empty($library) && $library->getMemberAssignment())
		{
			$acl = new CustomACL($this->container);
			$members = $acl->getObjectACEUsers($library, 'view');
			$gmembers = $acl->getObjectACEGroups($library, 'view');
			if (!empty($members))
			{
				foreach ($members as $m) {
					if (false !== $user = $this->findUserAndCreate($m->getUsername(), false, true, false))
					{
						$xml .= "<document><docid>{$user->getId()}</docid>";
						$xml .= "<LibKey>{$library->getId()}</LibKey>";
						$xml .= "<MemberName>{$user->getUserNameDnAbbreviated()}</MemberName>";
						$xml .= '<MemberType>Person</MemberType>';
						$access = 'User';
						if ($acl->isRoleGranted($library, 'ROLE_LIBADMIN'.$library->getId(), 'master')) {
							$access = $user->hasRole('ROLE_LIBADMIN'.$library->getId()) ? 'Library Administrator' : 'User';
						}
						$xml .= "<MemberAccess>{$access}</MemberAccess>";
						$xml .= '<MaintainedBy></MaintainedBy></document>';
					}
				}
			}
			if (!empty($gmembers)) 
			{
				foreach ($gmembers as $gm) {
					if ($gm->getRole() != 'ROLE_ADMIN' && false != $group = $this->retrieveGroupByRole($gm->getRole())) 
					{
						$xml .= "<document><docid>{$group->getId()}</docid>";
						$xml .= "<LibKey>{$library->getId()}</LibKey>";
						$suffix = $group->getGroupType() === false && $group->getGroupName() ? '/DOCOVA' : '';
						$xml .= "<MemberName>{$group->getDisplayName()}$suffix</MemberName>";
						$xml .= '<MemberType>Group</MemberType>';
						$access = 'User';
						if ($acl->isRoleGranted($library, $group->getRole(), 'master')) {
							$access = 'Library Administrator';
						}
						$xml .= "<MemberAccess>{$access}</MemberAccess>";
						$xml .= '<MaintainedBy></MaintainedBy></document>';
					}
				}
			}
		}
		$xml.= '</documents>';
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml);
		return $response;
	}
	
	public function getLibraryActivityAction(Request $request)
	{
		$path = $request->query->get('path');
		$path = explode(',', $path);
		$library = $path[0];
		$type = end($path);
		$output = '<html><head></head><body text="#000000">No recent activity was found for this library.</body></html>';
		$em = $this->getDoctrine()->getManager();
		$documents = $em->getRepository('DocovaBundle:Documents')->getUpdatedDocuments($library, $type);
		if (!empty($documents)) {
			$securityContext = $this->container->get('security.authorization_checker');
			$lib_object = $em->getReference('DocovaBundle:Libraries', $library);
			if (!$securityContext->isGranted('ROLE_ADMIN') && !$securityContext->isGranted('MASTER', $lib_object)) {
				$security = new Miscellaneous($this->container);
				foreach ($documents as $index => $doc) {
					if (!$security->canReadDocument($doc, true)) {
						$documents[$index] = null;
						unset($documents[$index]);
					}
				}
				$security = $doc = null;
			}
			$securityContext = $lib_object = null;
			return $this->render('DocovaBundle:Default:wLibActivityContent.html.twig', array(
				'documents' => $documents,
				'library' => $library
			));
		}
		$response = new Response();
		$response->setContent($output);
		return $response;
	}

	/**
	 * Get document type info
	 * @param \DOMDocument $post
	 * @return string;
	 */
	private function libservicesGETTYPEINFO($post)
	{
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		$doc_id = $post->getElementsByTagName('TypeKey')->item(0)->nodeValue;
		$result = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $doc_id, 'Trash' => false));
		if (empty($result)) {
			throw $this->createNotFoundException('Document Type could not be found.');
		}
		
		$xml .= '<Results><Result ID="Status">OK</Result>';
		$xml .= '<Result ID="Ret1"><![CDATA['.$result->getDocName().']]></Result>';
		$xml .= '<Result ID="Ret2"><![CDATA['.$result->getDescription().']]></Result>';
		$xml .= '<Result></Result></Results>';
		return $xml;
	}
	
	/**
	 * Library admin/membership services
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesADDLIBRARYMEMBERS($post)
	{
		$created = false;
		$em = $this->getDoctrine()->getManager();
		$library = $post->getElementsByTagName('LibKey')->item(0)->nodeValue;
		$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library, 'Trash' => false));
		$securityContext = $this->container->get('security.authorization_checker');
		if (!$securityContext->isGranted('MASTER', $library)) {
			throw new AccessDeniedException();
		}
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		if (!empty($library) && !empty($post->getElementsByTagName('Member')->length))
		{
			try {
				$lib_admins = array();
				$customACL_obj = new CustomACL($this->container);
				$customACL_obj->removeMaskACEs($library, 'view');
				if ($customACL_obj->isRoleGranted($library, 'ROLE_USER', 'view')) {
					$customACL_obj->removeUserACE($library, 'ROLE_USER', 'view', true);
				}
				//@TODO: remove subscribed users are not in the members list either individual or groups
				//$subscribers = $customACL_obj->getObjectACEUsers($library, 'delete');
				if (!$customACL_obj->isRoleGranted($library, 'ROLE_ADMIN', 'view')) {
					$customACL_obj->insertObjectAce($library, 'ROLE_ADMIN', 'view');
				}
				if (!$library->getMemberAssignment())
				{
					$library->setMemberAssignment(true);
					$em->flush();
				}
				if (!empty($post->getElementsByTagName('MemberName')->length))
				{
					for ($x = 0; $x < $post->getElementsByTagName('MemberName')->length; $x++) {
						$member = $this->findUserAndCreate($post->getElementsByTagName('MemberName')->item($x)->nodeValue, false, false, false);
						if (!empty($member))
						{
							$customACL_obj->insertObjectAce($library, $member, 'view', false);
							if ($post->getElementsByTagName('MemberAccess')->item($x)->nodeValue == 'Library Administrator') {
								$lib_admins[] = $member;
							}
						}
						elseif (false != $group = $this->retrieveGroupByRole($post->getElementsByTagName('MemberName')->item($x)->nodeValue, true)) {
							$customACL_obj->insertObjectAce($library, $group->getRole(), 'view');
							if ($post->getElementsByTagName('MemberAccess')->item($x)->nodeValue == 'Library Administrator') {
								$lib_admins[] = $group;
							}
						}
					}
				}
				elseif (!empty($post->getElementsByTagName('Register')->item(0)->nodeValue))
				{
					if (!empty($post->getElementsByTagName('MemberFirstName')->length))
					{
						for ($x = 0; $x < $post->getElementsByTagName('MemberFirstName')->length; $x++)
						{
							$member = $this->createUserProfile($post, $x);
							if (!empty($member)) 
							{
								$customACL_obj->insertObjectAce($library, $member, 'view', false);
							}
							if ($post->getElementsByTagName('MemberAccess')->item($x)->nodeValue == 'Library Administrator') {
								$lib_admins[] = $member;
							}
						}
					}
					elseif (!empty($post->getElementsByTagName('GroupName')->item(0)->nodeValue) && !empty($post->getElementsByTagName('GroupMembers')->item(0)->nodeValue))
					{
						$gname = $post->getElementsByTagName('GroupName')->item(0)->nodeValue;
						$members = explode(':', $post->getElementsByTagName('GroupMembers')->item(0)->nodeValue);
						$group = new UserRoles();
						$group->setDisplayName($gname);
						$group->setGroupName($gname);
						$group->setRole('ROLE_'.strtoupper(str_replace(' ', '', $gname)));
						$group->setGroupType(false);
						$em->persist($group);
						foreach ($members as $username) {
							if (false !== $user = $this->findUserAndCreate($username, false)) 
							{
								if ($user instanceof \Docova\DocovaBundle\Entity\UserAccounts) {
									$group->addRoleUsers($user);
								}
							}
						}
						$em->flush();
						$gname = $members = $user = $username = null;
						$customACL_obj->insertObjectAce($library, $group->getRole(), 'view');
						if ($post->getElementsByTagName('MemberAccess')->item(0)->nodeValue == 'Library Administrator') {
							$lib_admins[] = $group;
						}
					}
				}
				if (!empty($lib_admins))
				{
					$is_new = false;
					$lib_admin_role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_LIBADMIN'.$library->getId()));
					if (empty($lib_admin_role))
					{
						$is_new = true;
						$lib_admin_role = new UserRoles();
						$lib_admin_role->setDisplayName($library->getLibraryTitle() . ' Administrators');
						$lib_admin_role->setRole('ROLE_LIBADMIN' . $library->getId());
						$em->persist($lib_admin_role);
					}
					foreach ($lib_admins as $admin) {
						if ($admin instanceof \Docova\DocovaBundle\Entity\UserAccounts) {
							if ($is_new === true)
								$lib_admin_role->addRoleUsers($admin);
							else {
								$found = false;
								foreach ($lib_admin_role->getRoleUsers() as $user) {
									if ($user->getId() == $admin->getId()) {
										$found = true;
										break;
									}
								}
								if ($found === false) {
									$lib_admin_role->addRoleUsers($admin);
								}
							}
						}
						elseif ($admin instanceof \Docova\DocovaBundle\Entity\UserRoles) {
							$customACL_obj->insertObjectAce($library, $admin->getRole(), 'master');
						}
					}
					$em->flush();
					if (!$customACL_obj->isRoleGranted($library, $lib_admin_role->getRole(), 'master')) {
						$customACL_obj->insertObjectAce($library, $lib_admin_role->getRole(), 'master');
					}
				}
				$created = true;
			}
			catch (\Exception $e) {
				$created = $e->getMessage();
			}
		}
		if ($created === true)
		{
			$xml .= '<Results><Result ID="Status">OK</Result></Results>';
		}
		else {
			$xml .= '<Results><Result ID="Status">FAILED</Result>';
			$xml .= '<Result ID="ErrMsg">'.($created === false ? 'No member has beend added!' : $created).'</Result>';
			$xml .= '</Results>';
		}
		return $xml;
	}

	/**
	 * Remove library member(s) service
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesREMOVELIBRARYMEMBERS($post)
	{
		$removed = false;
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		if (!empty($post->getElementsByTagName('MemberName')->length))
		{
			$em = $this->getDoctrine()->getManager();
			$library = $post->getElementsByTagName('LibKey')->item(0)->nodeValue;
			$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library, 'Trash' => false));
			$securityContext = $this->container->get('security.authorization_checker');
			if (!$securityContext->isGranted('MASTER', $library)) {
				throw new AccessDeniedException();
			}
			$customAcl = new CustomACL($this->container);
			foreach ($post->getElementsByTagName('MemberName') as $item) {
				if (false !== $user = $this->findUserAndCreate($item->nodeValue, false, false, false))
				{
					if ($user->hasRole('ROLE_LIBADMIN'.$library->getId())) {
						$userRole = $this->retrieveGroupByRole('ROLE_LIBADMIN'.$library->getId());
						$user->removeUserRoles($userRole);
						$em->flush();
						$userRole = null;
					}
					$customAcl->removeUserACE($library, $user, 'view');
					$removed = true;
				}
				elseif (false != $group = $this->retrieveGroupByRole($item->nodeValue, true)) {
					$customAcl->removeUserACE($library, $group->getRole(), 'view', true);
					$customAcl->removeUserACE($library, $group->getRole(), 'master', true);
					$removed = true;
				}
			}
			
			if ($removed === true) 
			{
				$individuals = $customAcl->getObjectACEUsers($library, 'view');
				$groups = $customAcl->getObjectACEGroups($library, 'view');
				if ((empty($individuals) || !$individuals->count()) && (empty($groups) || !$groups->count() || ($groups->count() == 1 && $groups[0]->getRole() == 'ROLE_ADMIN'))) {
					$customAcl->removeUserACE($library, 'ROLE_ADMIN', 'view', true);
					$customAcl->removeUserACE($library, 'ROLE_LIBADMIN'.$library->getId(), 'master', true);
					$customAcl->insertObjectAce($library, 'ROLE_USER', 'view');
				}
			}
		}
		
		if ($removed === true) 
		{
			$xml .= '<Results><Result ID="Status">OK</Result></Results>';
		}
		return $xml;
	}
	
	/**
	 * Update library member(s) access
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesUPDATELIBRARYMEMBERS($post)
	{
		$updated = false;
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		if (!empty($post->getElementsByTagName('MemberName')->length)) 
		{
			$em = $this->getDoctrine()->getManager();
			$library = $post->getElementsByTagName('LibKey')->item(0)->nodeValue;
			$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library, 'Trash' => false));
			if (!empty($library)) 
			{
				$securityContext = $this->container->get('security.authorization_checker');
				if (!$securityContext->isGranted('MASTER', $library)) {
					throw new AccessDeniedException();
				}
				$customAcl = new CustomACL($this->container);
				for ($x = 0; $x < $post->getElementsByTagName('MemberName')->length; $x++)
				{
					$access = $post->getElementsByTagName('MemberAccess')->item($x)->nodeValue;
					if (false !== $user = $this->findUserAndCreate($post->getElementsByTagName('MemberName')->item($x)->nodeValue, false, false, false)) 
					{
						$role = $this->retrieveGroupByRole('ROLE_LIBADMIN' . $library->getId());
						if ($user->hasRole('ROLE_LIBADMIN' . $library->getId()) && $access == 'User') {
							$user->removeUserRoles($role);
						}
						elseif (!$user->hasRole('ROLE_LIBADMIN' . $library->getId()) && $access == 'Library Administrator') {
							if (empty($role)) 
							{
								$role = new UserRoles();
								$role->setDisplayName($library->getLibraryTitle() . ' Administrators');
								$role->setRole('ROLE_LIBADMIN' . $library->getId());
								$em->persist($role);
								$customAcl->insertObjectAce($library, $role->getRole(), 'master');
							}
							$role->addRoleUsers($user);
						}
						$em->flush();
						$em->refresh($role);
						if (!$role->getRoleUsers()->count()) {
							$customAcl->removeUserACE($library, $role->getRole(), 'master', true);
						}
						$updated = true;
						$role = $user = null;
					}
					elseif (false !== $group = $this->retrieveGroupByRole($post->getElementsByTagName('MemberName')->item($x)->nodeValue, true))
					{
						if ($customAcl->isRoleGranted($library, $group->getRole(), 'master') && $access == 'User') {
							$customAcl->removeUserACE($library, $group->getRole(), 'master', true);
							$updated = true;
						}
						elseif (!$customAcl->isRoleGranted($library, $group->getRole(), 'master') && $access == 'Library Administrator') {
							$customAcl->insertObjectAce($library, $group->getRole(), 'master');
							$updated = true;
						}
					}
				}
				if ($updated === true) 
				{
					$xml .= '<Results><Result ID="Status">OK</Result></Results>';
				}
			}
		}
		return $xml;
	}

	/**
	 * Add subscription service
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesSUBADD($post)
	{
		return $this->commonSubscriptionServices($post, 'DocKey');
	}
	
	/**
	 * Add subscribers service
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesADDSUBSCRIBERS($post)
	{
		return $this->commonSubscriptionServices($post, 'LibKey');
	}
	
	/**
	 * Common adding subscribers service
	 * @param \DOMDocument $post
	 * @param string $libKey
	 * @return string
	 */
	private function commonSubscriptionServices($post, $libKey)
	{
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>'; 
		if (!empty($post->getElementsByTagName($libKey)->length))
		{
			$em = $this->getDoctrine()->getManager();
			$res = false;
			foreach ($post->getElementsByTagName($libKey) as $lib_id)
			{
				$lib_id = trim($lib_id->nodeValue);
				$result = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $lib_id, 'Trash' => false));
				if (!empty($result)) {
					$customACL_obj = new CustomACL($this->container);
					if ($libKey === 'LibKey')
					{
						if (!empty($post->getElementsByTagName('SubscriberName')->length))
						{
							foreach ($post->getElementsByTagName('SubscriberName') as $item)
							{
								if (false !== $user = $this->findUserAndCreate($item->nodeValue))
								{
									if (true === $customACL_obj->insertObjectAce($result, $user, 'delete', false)) {
										$res = true;
									}
								}
							}
						}
					}
					else {
						$res = $customACL_obj->insertObjectAce($result, $this->user, 'delete', false);
					}
				}
				$customACL_obj = $user = $result = null;
			}
			if ($res === true) {
				$xml .= '<Results><Result ID="Status">OK</Result></Results>';
			}
		}
		return $xml;
	}
	
	/**
	 * Remove subscriber service 
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesSUBREMOVE($post)
	{
		$removed = false;
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		if (!empty($post->getElementsByTagName('DocKey')->length))
		{
			$customACL_obj = new CustomACL($this->container);
			foreach ($post->getElementsByTagName('DocKey') as $item)
			{
				$lib_id = trim($item->nodeValue);
				$result = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $lib_id));
				if (!empty($result))
				{
					$res = $customACL_obj->removeUserACE($result, $this->user, 'delete');
					if ($res === true) {
						$removed = true;
					}
				}
			}
		}
		if (!empty($removed))
		{
			$xml .= '<Results><Result ID="Status">OK</Result></Results>';
		}
		else {
			$xml .= '<Results><Result ID="Status">FAILED</Result></Results>';
		}
		return $xml;
	}
	
	/**
	 * Paste folder and its documents service
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesPASTEFOLDER($post)
	{
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		$clip_action	= $post->getElementsByTagName('clipaction')->item(0)->nodeValue;
		$src_folder_id	= $post->getElementsByTagName('srcfolderunid')->item(0)->nodeValue;
		$folderUNID = $post->getElementsByTagName('targetfolderunid');
		if (!empty($folderUNID))
			$target_folder_id	= $post->getElementsByTagName('targetfolderunid')->item(0)->nodeValue;
		$target_library_id	= $post->getElementsByTagName('targetlibkey')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		
		if (substr($target_folder_id, 0, 5) == "RCBIN") {
			throw $this->createNotFoundException('Items cannot be pasted to the Recycle Bin. Please use delete.');
		}
		if (empty($clip_action)) {
			throw $this->createNotFoundException('Unspecified clipboard action.');
		}
		
		if ($clip_action === 'CUTFOLDER') {
			$src_folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $src_folder_id, 'Del' => false, 'Inactive' => 0));
		}
		else {
			$src_folder = $em->getRepository('DocovaBundle:Folders')->getOneBy(array('id' => $src_folder_id, 'Del' => false, 'Inactive' => 0));
		}
		if (!empty($target_folder_id) && $target_folder_id != $target_library_id ){
			$target_folder = $em->getRepository('DocovaBundle:Folders')->getOneBy(array('id' => $target_folder_id, 'Del' => false, 'Inactive' => 0));
		}
		else{
			//by default if no target folder is provided then presume that the user intends to paste into the root
			$target_folder_id = $target_library_id;
		}
			
		if (empty($src_folder)) {
			throw $this->createNotFoundException('Could not access source folder with ID = '. $src_folder_id);
		}
		if (empty($target_folder) && $target_folder_id != $target_library_id) {
			throw $this->createNotFoundException('Could not access target folder with ID = '. $target_folder_id);
		}
			
		if ($clip_action === 'CUTFOLDER') {
			if ($target_folder_id != $target_library_id && true === $em->getRepository('DocovaBundle:Folders')->folderExistsInDescendants($src_folder->getPosition() . '.', $target_folder[0]['id'], $src_folder->getLibrary()->getId())) {
				return $xml; //@TODO change the false to an xml data with error message
			}
		
			if (empty($target_folder) && true === $em->getRepository('DocovaBundle:Folders')->folderByIdExists(null, $src_folder->getId(), $target_library_id))
			{
				return $xml;
			}
			elseif (!empty($target_folder) && true === $em->getRepository('DocovaBundle:Folders')->folderExists($target_folder[0]['id'], $src_folder->getFolderName())) {
				$src_folder->setFolderName('Copy of '. $src_folder->getFolderName());
			}
		
			if ($target_folder_id == $target_library_id) {
				$src_folder->setParentfolder(null);
			}
			else {
				$src_folder->setParentfolder($em->getReference('DocovaBundle:Folders', $target_folder[0]['id']));
			}
		
			$src_folder->setUpdator($this->user);
			$src_folder->setDateUpdated(new \DateTime());
			if (!empty($target_folder)) {
				$src_folder->setLibrary($em->getReference('DocovaBundle:Libraries', $target_folder['Library']));
			}
			else {
				$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $target_library_id, 'Trash' => false),array('Library_Title' => 'ASC'));
				$src_folder->setLibrary($library);
			}
			$em->flush();
			$newfolder = $src_folder->getId();
		
			$children = $em->getRepository('DocovaBundle:Folders')->getDescendants($src_folder->getPosition(), $target_library_id, $src_folder->getId(), true);
		
			//$this->createSubfoldersLog($children, 'CREATE', 'Moved Folder.');
			$this->updateSubfolderPosition($children, $src_folder->getId(), $target_library_id);
			$this->reindexFolderDocuments($src_folder, $target_library_id);
			//$this->updateSubfoldersACL();
		
			// ---------------------------------
			//     NOW FOLDER ACCESS CONTROL
			// ---------------------------------
			if (!empty($target_folder)) {
		
				$customACL_obj = new CustomACL($this->container);
		
//				if (!empty($children)) {
//					$this->overwriteSubfolderOwners($em, array($src_folder), $target_folder, $this->user);
//				}
				$readers = $customACL_obj->getObjectACEUsers($em->getReference('DocovaBundle:Folders', $target_folder[0]['id']), 'view');
				$reader_groups = $customACL_obj->getObjectACEGroups($em->getReference('DocovaBundle:Folders', $target_folder[0]['id']), 'view');
				$parent_folders = $em->getRepository('DocovaBundle:Folders')->getAncestors($target_folder[0]['Position'], $target_folder[0]['parentfolder']['id'], null, true);
				if (!empty($parent_folders))
				{
					foreach ($parent_folders as $pfolder)
					{
						$temp = $customACL_obj->getObjectACEUsers($em->getReference('DocovaBundle:Folders', $pfolder['id']), 'view');
						if ($temp->count() > 0) {
							if ($readers->count() > 0)
							{
								foreach ($temp as $parent_element)
								{
									if (false === $this->objectArrayContains($readers, $parent_element)) {
										$readers->add($parent_element);
									}
								}
							}
							else {
								$readers = $temp;
							}
						}
						$temp = $customACL_obj->getObjectACEGroups($em->getReference('DocovaBundle:Folders', $pfolder['id']), 'view');
						if ($temp->count() > 0) {
							if ($reader_groups->count() > 0)
							{
								foreach ($temp as $parent_element)
								{
									if (false === $this->objectArrayContains($reader_groups, $parent_element)) {
										$reader_groups->add($parent_element);
									}
								}
							}
							else {
								$reader_groups = $temp;
							}
						}
					}
				}
					
				if ($readers->count() > 0 || $reader_groups->count() > 0) {
					$parent_authors = $customACL_obj->getObjectACEUsers($em->getReference('DocovaBundle:Folders', $target_folder[0]['id']), 'create');
					$pauthor_groups = $customACL_obj->getObjectACEGroups($em->getReference('DocovaBundle:Folders', $target_folder[0]['id']), 'create');
					$current_owners = $customACL_obj->getObjectACEUsers($src_folder, 'owner');
					$current_masters = $customACL_obj->getObjectACEUsers($src_folder, 'master');
					$cowner_groups = $customACL_obj->getObjectACEGroups($src_folder, 'owner');
					$cmaster_groups = $customACL_obj->getObjectACEGroups($src_folder, 'master');
					if ($current_masters->count() > 0)
					{
						for ($x = 0; $x < $current_masters->count(); $x++)
						{
							$current_owners->add($current_masters[$x]);
						}
					}
					$current_masters = null;
					if ($cmaster_groups->count() > 0)
					{
						for ($x = 0; $x < $cmaster_groups->count(); $x++)
						{
							$cowner_groups->add($cmaster_groups[$x]);
						}
					}
					$cmaster_groups = null;

					$customACL_obj->removeUserACE($src_folder, 'ROLE_USER', 'create', true);
					$customACL_obj->removeMaskACEs($src_folder, 'view');

					foreach ($current_owners as $owner)
					{
						if (false === $this->objectArrayContains($readers, $owner, true))
						{
							if ($parent_authors->count() > 0)
							{
								if (false === $this->objectArrayContains($parent_authors, $owner, true))
								{
									if (false !== $owner = $this->findUserAndCreate($owner->getUsername(), false, true)) {
										$customACL_obj->removeUserACE($src_folder, $owner, array('owner', 'master'));
									}
								}
							}
							else
							{
								if (false !== $owner = $this->findUserAndCreate($owner->getUsername(), false, true)) {
									$customACL_obj->removeUserACE($src_folder, $owner, array('owner', 'master'));
								}
							}
						}
					}
					$parent_authors = null;

					if ($cowner_groups->count())
					{
						foreach ($cowner_groups as $owner)
						{
							if (false === $this->objectArrayContains($reader_groups, $owner))
							{
								if ($pauthor_groups->count() > 0)
								{
									if (false === $this->objectArrayContains($pauthor_groups, $owner))
									{
										$customACL_obj->removeUserACE($src_folder, $owner->getRole(), array('owner', 'master'), true);
									}
								}
								else
								{
									$customACL_obj->removeUserACE($src_folder, $owner->getRole(), array('owner', 'master'), true);
								}
							}
						}
					}
					$pauthor_groups = null;
					$authors = $customACL_obj->getObjectACEUsers($src_folder, 'create');
					$author_groups = $customACL_obj->getObjectACEGroups($src_folder, 'create');
					if ($authors->count() > 0) {
						for ($x = 0; $x < $authors->count(); $x++) {
							if (false === $this->objectArrayContains($readers, $authors[$x], true)) {
								$user = $this->findUserAndCreate($authors[$x]->getUsername(), false, true);
								if (!empty($user)) {
									$customACL_obj->removeUserACE($src_folder, $user, 'create');
								}
							}
						}
					}
					$authors = null;
					if ($author_groups->count() > 0) {
						for ($x = 0; $x < $author_groups->count(); $x++) {
							if (false === $this->objectArrayContains($reader_groups, $author_groups[$x])) {
								$customACL_obj->removeUserACE($src_folder, $author_groups[$x]->getRole(), 'create', true);
							}
						}
					}
					$author_groups = null;
					if ($readers->count())
					{
						for ($x = 0; $x < $readers->count(); $x++)
						{
							if (!empty($readers[$x])) 
							{
								if (false !== $user_obj = $this->findUserAndCreate($readers[$x]->getUsername(), false, true)) 
								{
									if (false === $customACL_obj->isUserGranted($src_folder, $user_obj, 'view')) {
										$customACL_obj->insertObjectAce($src_folder, $user_obj, 'view', false);
									}
								}
							}
						}
					}
		
					if ($reader_groups->count())
					{
						for ($x = 0; $x < $reader_groups->count(); $x++) 
						{
							if (!empty($reader_groups[$x]))
							{
								if (false === $customACL_obj->isRoleGranted($src_folder, $reader_groups[$x]->getRole(), 'view')) {
									$customACL_obj->insertObjectAce($src_folder, $reader_groups[$x]->getRole(), 'view');
								}
							}
						}
					}
					$customACL_obj = $user_obj = $src_folder = $target_folder = null;

					if (!empty($children)) {
						$this->addReadersToChildren($children, $readers, $reader_groups, $newfolder);
					}
					$em->clear();
					$em = null;
				}
			}
		}
		elseif ($clip_action === 'COPYFOLDER')
		{
			if ($target_folder_id != $target_library_id && true === $em->getRepository('DocovaBundle:Folders')->folderExistsInDescendants($src_folder[0]['Position'] . '.', $target_folder[0]['id'], $src_folder['Library'])) {
				return $xml; //@TODO change the false to an xml data with error message
			}
			$children = $em->getRepository('DocovaBundle:Folders')->getDescendants($src_folder[0]['Position'], $src_folder['Library'], $src_folder[0]['id'], true);
			if (!empty($target_folder)) {
				$exist = $em->getRepository('DocovaBundle:Folders')->folderExists($target_folder[0]['id'], $src_folder[0]['Folder_Name']);
				$newfolder = $this->pasteCopiedFolders($children, $src_folder[0]['id'], $target_folder['Library'], $target_folder[0]['id'], $exist);
			}
			else {
				$exist = $em->getRepository('DocovaBundle:Folders')->folderExists(null, $src_folder[0]['Folder_Name'], $target_library_id);
				//$target_library = $em->find('DocovaBundle:Libraries', $target_library_id);
				$newfolder = $this->pasteCopiedFolders($children, $src_folder[0]['id'], $target_library_id, null, $exist);
			}
			$children = $src_folder = $target_folder = $target_library = null;
		}

		$xml .= '<Results><Result ID="Status">OK</Result>';
		$xml .= '<Result ID="Ret1">'.$newfolder.'</Result>';
		$xml .= '<Result ID="Ret2">'.$newfolder.'</Result>';
		$xml .= '</Results>';
		return $xml;
	}
	
	/**
	 * Get library name service
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesGETNSFNAMEBYLIBRARYKEY($post)
	{
		$lib_id = trim($post->getElementsByTagName('LibraryKey')->item(0)->nodeValue);
		if (empty($lib_id))
		{
			throw $this->createNotFoundException('Library ID is missed.');
		}
		
		$library = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $lib_id, 'Trash' => false));
		if (empty($library)) {
			throw $this->createNotFoundException('Unspecified source Library with ID = '.$lib_id);
		}
		
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		$xml .= '<Results><Result ID="Status">OK</Result>';
		$xml .= '<Result ID="Ret1">'.$library->getLibraryTitle().'</Result>';
		$xml .= '</Results>';
		return $xml;
	}
	
	/**
	 * Get library/app info
	 * 
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesGETLIBRARYINFO($post)
	{
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		$em = $this->getDoctrine()->getManager();
		$libid = $post->getElementsByTagName('LibraryKey')->item(0)->nodeValue;
		$libname = $post->getElementsByTagName('LibraryName')->item(0)->nodeValue;
		$libpath = !empty($post->getElementsByTagName('LibraryPath')->length) ? $post->getElementsByTagName('LibraryPath')->item(0)->nodeValue : null;
		$name = $libname ? $libname : $libpath;

		if ( !empty($libid)){
			$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $libid, 'Trash' => false));
		}else{
			$library = $em->getRepository('DocovaBundle:Libraries')->getByName($name);
		}
		
		if (empty($library)) {
			$library = $em->getRepository('DocovaBundle:LibraryGroups')->find($libid);
			if (empty($library)) 
			{
				$xml .= '<Results><Result ID="Status">FAILED</Result>';
				$xml .= '<Result ID="ErrMsg"><![CDATA[Unspecified Library/Application source ID]]></Result>';
				$xml .= '</Results>';
				return $xml;
			}
			$libraries = array();
			foreach ($library->getLibraries() as $lib) {
				$libraries[] = $lib->getId();
			}
			$xml .= '<Results><Result ID="Status">OK</Result>';
			$xml .= "<Result ID=\"Ret1\"><LibraryInfo><LibraryKey>{$library->getId()}</LibraryKey>";
			$xml .= "<LibraryName><![CDATA[{$library->getGroupTitle()}]]></LibraryName>";
			$xml .= "<LibraryPath><![CDATA[{$this->generateUrl('docova_homepage')}]]></LibraryPath>";
			$xml .= "<InheritDesignFrom></InheritDesignFrom>";
			$xml .= "<AppIcon><![CDATA[{$library->getGroupIcon()}]]></AppIcon>";
			$xml .= "<AppIconColor><![CDATA[{$library->getGroupIconColor()}]]></AppIconColor>";
			$xml .= '<IsApp>0</IsApp></LibraryInfo><AppType>LG</AppType>';
			$xml .= '<LibraryList>'.implode(',', $libraries).'</LibraryList>';
			$xml .= '</Result></Results>';
			return $xml;
		}
		
		$xml .= '<Results><Result ID="Status">OK</Result>';
		if (!empty($post->getElementsByTagName('InfoType')->item(0)->nodeValue) && $post->getElementsByTagName('InfoType')->item(0)->nodeValue == 'InheritDesignFrom')
		{
			if ($library->getInheritDesignFrom()) {
				$library = $em->getRepository('DocovaBundle:Libraries')->find($library->getInheritDesignFrom());
				$xml .= "<Result ID=\"Ret1\"><![CDATA[{$library->getLibraryTitle()}]]></Result>";
			}
			$xml .= '</Results>';
			return $xml;
		}
		$xml .= "<Result ID=\"Ret1\"><LibraryInfo><LibraryKey>{$library->getId()}</LibraryKey>";
		$xml .= "<LibraryName><![CDATA[{$library->getLibraryTitle()}]]></LibraryName>";
		$xml .= "<LibraryPath><![CDATA[{$this->generateUrl('docova_homepage')}]]></LibraryPath>";
		$xml .= "<InheritDesignFrom>{$library->getInheritDesignFrom()}</InheritDesignFrom>";
		$xml .= "<AppIcon><![CDATA[{$library->getAppIcon()}]]></AppIcon>";
		$xml .= "<AppIconColor><![CDATA[{$library->getAppIconColor()}]]></AppIconColor>";
		$xml .= '<AppType>'.($library->getIsApp() ? 'A' : 'L').'</AppType>';
		$xml .= '<IsApp>'.($library->getIsApp() ? 1 : '').'</IsApp></LibraryInfo>';
		$xml .= '</Result></Results>';
		return $xml;
	}
	
	/**
	 * Remove related document or email
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesREMOVERELATED($post)
	{
		$src_type = $post->getElementsByTagName('SrcType')->item(0)->nodeValue;
		$parent_doc_id	= $post->getElementsByTagName('ParentDocID')->item(0)->nodeValue;
		if (empty($parent_doc_id))
		{
			throw $this->createNotFoundException('Parent Document ID is missed.');
		}
			
		$em = $this->getDoctrine()->getManager();
		$parent_document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $parent_doc_id, 'Archived' => false));
		
		if (empty($parent_document))
		{
			throw $this->createNotFoundException('Unspecified source document for ID = '.$parent_doc_id);
		}
			
		if (count($post->getElementsByTagName('Unid')) < 1)
		{
			throw $this->createNotFoundException("Related $src_type ID is missed.");
		}
		
		$log_obj = new Miscellaneous($this->container);
		foreach ($post->getElementsByTagName('Unid') as $related_doc)
		{
			if ($src_type == 'Email')
			{
				$email = $em->getRepository('DocovaBundle:RelatedEmails')->findOneBy(array('document' => $parent_document->getId(), 'id' => $related_doc->nodeValue));
				if (!empty($email))
				{
					$em->remove($email);
					$em->flush();
					@unlink($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'mails'.DIRECTORY_SEPARATOR.$related_doc->nodeValue);
		
					$log_obj->createDocumentLog($em, 'UPDATE', $parent_document, 'Removed \''.$email->getSubject().'\' email message from Related Correspondence.');
				}
			}
			elseif ($src_type == 'Related')
			{
				$link = $em->getRepository('DocovaBundle:RelatedLinks')->findOneBy(array('document' => $parent_document->getId(), 'id' => $related_doc->nodeValue));
				if (!empty($link))
				{
					$em->remove($link);
					$em->flush();
		
					$log_obj->createDocumentLog($em, 'UPDATE', $parent_document, 'Removed \''.$link->getDescription().'\' link from Document Relationships section.');
				}
			}
			else {
				$related_record = $em->getRepository('DocovaBundle:RelatedDocuments')->findOneBy(array('Parent_Doc' => $parent_document, 'Related_Doc' => $related_doc->nodeValue));
				if (!empty($related_record))
				{
					$em->remove($related_record);
					$em->flush();
		
					$log_obj->createDocumentLog($em, 'UPDATE', $parent_document, 'Removed \''.$related_record->getRelatedDoc()->getDocTitle().'\' link from Document Relationships section.');
				}
				unset($related_record);
			}
		}
		
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		$xml .= '<Results><Result ID="Status">OK</Result></Results>';
		return $xml;
	}
	
	/**
	 * Archive document(s) service
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesARCHIVESELECTED($post)
	{
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		if (!empty($post->getElementsByTagName('Unid')->item(0)->nodeValue))
		{
			$archived = false;
			$em = $this->getDoctrine()->getManager();
			foreach ($post->getElementsByTagName('Unid') as $item) {
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $item->nodeValue, 'Trash' => false));
				if (!empty($document))
				{
					$document->setArchived(true);
					$document->setDateArchived(new \DateTime());
					$document->setStatusNoArchived($document->getStatusNo());
					$document->setPreviousStatus($document->getDocStatus());
					$document->setStatusNo(6);
					$document->setDocStatus('Archived');
					if ($document->getBookmarks()->count() > 0) {
						foreach ($document->getBookmarks() as $bmk) {
							$em->remove($bmk);
						}
					}
					$em->flush();
					$log_obj = new Miscellaneous($this->container);
					$log_obj->createDocumentLog($em, 'ARCHIVE', $document);
					$log_obj = null;
					$archived = true;
				}
			}
		
			if ($archived === true)
			{
				$xml .= '<Results><Result ID="Status">OK</Result></Results>';
			}
		}
		return $xml;
	}
	
	/**
	 * Restore archived document(s) service
	 * @param \DOMDocument $post
	 * @return string
	 */
	private function libservicesRESTOREARCHIVED($post)
	{
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		if (!empty($post->getElementsByTagName('Unid')->item(0)->nodeValue))
		{
			$restored = false;
			$em = $this->getDoctrine()->getManager();
			foreach ($post->getElementsByTagName('Unid') as $item) {
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $item->nodeValue, 'Archived' => true));
				if (!empty($document))
				{
					$document->setArchived(false);
					$document->setStatusNo($document->getStatusNoArchived());
					$document->setDocStatus($document->getPreviousStatus());
					$document->setStatusNoArchived(null);
					$document->setPreviousStatus(null);
					$em->flush();
		
					$log_obj = new Miscellaneous($this->container);
					$log_obj->createDocumentLog($em, 'ARCHIVE', $document, 'Restored document from archive.');
					$log_obj = null;
					$restored = true;
				}
			}
				
			if ($restored === true)
			{
				$xml .= '<Results><Result ID="Status">OK</Result></Results>';
			}
		}
		return $xml;
	}

	/**
	 * Update position of all descendants according to new parent folder position
	 *
	 * @param array $subfolders
	 * @param string $targetFolder
	 * @param string $library
	 */
	private function updateSubfolderPosition($subfolders, $targetFolder, $library)
	{
		if (!empty($subfolders) && is_array($subfolders))
		{
			$conn = $this->getDoctrine()->getConnection();
			$driver = $conn->getDriver()->getName();
			$conn->beginTransaction();
			try {
				if ($driver == 'pdo_mysql')
				{
					foreach ($subfolders as $folder)
					{
						if ($folder['id'] == $targetFolder) {
							if (!empty($folder['Parent'])) {
								$lastPos = $conn->fetchArray("SELECT SUBSTRING_INDEX(MAX(`Position`), '.', -1) FROM `tb_library_folders` WHERE `Parent_Id` = ? AND `id` <> ?", array($folder['Parent'], $folder['id']));
								$new_pos = !empty($lastPos[0]) ? ((int)$lastPos[0] + 1) : 1;
								$position = "SELECT (CONCAT((SELECT `position` FROM `tb_library_folders` WHERE `id` = '{$folder['Parent']}'), '.', '$new_pos'))";
							}
							else {
								$position = "SELECT MAX(`Position`) + 1 FROM `tb_library_folders` WHERE `Parent_Id` IS NULL AND `Library_Id` = '$library' AND `id` <> '{$folder['id']}'";
							}
						}
						else {
							$subquery = "SELECT SUBSTRING_INDEX(`Position`, '.', -1) FROM `tb_library_folders` WHERE `id` = '{$folder['id']}'";
							$position = "SELECT (CONCAT((SELECT `position` FROM `tb_library_folders` WHERE `id` = '{$folder['Parent']}'), '.', ($subquery)))";
						}
	
						$position = $conn->fetchArray($position);
						$position = $position[0];
						$query = "UPDATE `tb_library_folders` SET `Position` = ? WHERE `id` = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $position);
						$stmt->bindValue(2, $folder['id']);
						$stmt->execute();
	
						$query = "INSERT INTO `tb_folders_log` (`id`, `Log_Action`, `Log_Status`, `Log_Date`, `Log_Details`, `Folder_Id`, `Log_Author`)
								  SELECT (SELECT UUID()), 2, 1, NOW(), 'Moved Folder.', ?, ?";
						$stmt  = $conn->prepare($query);
						$stmt->bindValue(1, $folder['id']);
						$stmt->bindValue(2, $this->user->getId());
						$stmt->execute();
					}
				}
				elseif ($driver == 'pdo_sqlsrv')
				{
					foreach ($subfolders as $folder)
					{
						if ($folder['id'] == $targetFolder) {
							if (!empty($folder['Parent'])) {
								$lastPosQuery="SELECT MAX(FolderPosition) FROM ( ";
								$lastPosQuery.="SELECT TRY_CONVERT(integer,REPLACE(position,(SELECT Position from tb_library_folders where id=folders.parent_id)+'.','')) AS FolderPosition ";
								$lastPosQuery.="FROM [tb_library_folders] folders ";
								$lastPosQuery.="WHERE [Parent_Id] = ? AND [id] <> ? ";
								$lastPosQuery.=") T";
								$lastPos = $conn->fetchArray($lastPosQuery, array($folder['Parent'], $folder['id']));
	
								//$lastPos = $conn->fetchArray("SELECT REVERSE(LEFT(REVERSE(MAX([Position])), CHARINDEX('.', REVERSE(MAX([Position]))) - 1)) FROM [tb_library_folders] WHERE [Parent_Id] = ? AND [id] <> ?", array($folder['Parent'], $folder['id']));
								$new_pos = !empty($lastPos[0]) ? ((int)$lastPos[0] + 1) : 1;
								$position = "SELECT ((SELECT [Position] FROM [tb_library_folders] WHERE [id] = '{$folder['Parent']}') + '.' + '$new_pos')";
							}
							else {
								$position = "SELECT MAX(TRY_CONVERT(integer,[Position])) + 1 FROM [tb_library_folders] WHERE [Parent_Id] IS NULL AND [Library_Id] = '$library' AND [id] <> '{$folder['id']}'";
							}
						}
						else {
							$subquery = "SELECT CASE CHARINDEX('.', REVERSE([Position])) WHEN 0 THEN REVERSE([Position]) ELSE REVERSE(LEFT(REVERSE([Position]), CHARINDEX('.', REVERSE([Position])) - 1)) END FROM [tb_library_folders] WHERE [id] = '{$folder['id']}'";
							//$subquery = "SELECT REVERSE(LEFT(REVERSE([Position]), CHARINDEX('.', REVERSE([Position])) - 1)) FROM [tb_library_folders] WHERE [id] = '{$folder['id']}'";
							if (!empty($folder['Parent']))
								$position = "SELECT (SELECT [position] FROM [tb_library_folders] WHERE [id] = '{$folder['Parent']}') + '.' + ($subquery)";
							else
								$position = "SELECT (SELECT [position] FROM [tb_library_folders] WHERE [id] = '{$folder['id']}') + '.' + ($subquery)";
						}
							
						$position = $conn->fetchArray($position);
						$position = $position[0];
						$query = "UPDATE [tb_library_folders] SET [Position] = ? WHERE [id] = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $position);
						$stmt->bindValue(2, $folder['id']);
						$stmt->execute();
							
						$query = "INSERT INTO [tb_folders_log] ([id], [Log_Action], [Log_Status], [Log_Date], [Log_Details], [Folder_Id], [Log_Author])
								  SELECT NEWID(), 2, 1, GETDATE(), 'Moved Folder.', ?, ?";
						$stmt  = $conn->prepare($query);
						$stmt->bindValue(1, $folder['id']);
						$stmt->bindValue(2, $this->user->getId());
						$stmt->execute();
					}
				}
				$conn->commit();
			}
			catch (\Exception $e) {
				var_export($e->getMessage());
				$conn->rollback();
			}
				
			/*
			$em = $this->getDoctrine()->getManager();
			foreach ($subfolders as $folder)
			{
				$folder = $em->getReference('DocovaBundle:Folders', $folder['id']);
				$folder->setPosition($em);
				$em->flush();
				unset($folder);
			}
			$em->clear('DocovaBundle:Folders');
			*/
		}
	}

	/**
	 * Prepare to force to re-index all documents of a folder in a library
	 *
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @param string $library
	 * @return void
	 */
	private function reindexFolderDocuments($folder, $library)
	{
		$conn = $this->getDoctrine()->getConnection();
		$driver = $conn->getDriver()->getName();
		$conn->beginTransaction();
		try {
			$query = "UPDATE tb_folders_documents SET Indexed = 0 WHERE Trash = 0 AND Folder_Id IN (SELECT DISTINCT(id) FROM tb_library_folders AS F WHERE (F.Position LIKE '{$folder->getPosition()}.%' OR F.id = ?) AND F.Library_Id = ?)";
			$conn->executeUpdate($query, array($folder->getId(), $library));
			$conn->commit();
		}
		catch (\Exception $e) {
			var_export($e->getMessage());
			$conn->rollback();
		}
	}

	/**
	 * Add Readers to all descendants (remove ROLE_USER for their readers and authors);
	 *
	 * @param object $folders
	 * @param \Doctrine\Common\Collections\ArrayCollection $readers
	 * @param \Doctrine\Common\Collections\ArrayCollection $reader_groups
	 * @param string $target_folder
	 */
	private function addReadersToChildren($folders, $readers, $reader_groups, $target_folder)
	{
		$customACL_obj = new CustomACL($this->container);
		$em = $this->getDoctrine()->getManager();
		foreach ($folders as $fld)
		{
			if ($fld['id'] !== $target_folder)
			{
				$folder = $em->getReference('DocovaBundle:Folders', $fld['id']);
				$customACL_obj->removeUserACE($folder, 'ROLE_USER', 'create', true);
				$customACL_obj->removeMaskACEs($folder, 'view');
	
				if ($readers->count())
				{
					for ($x = 0; $x < $readers->count(); $x++) {
							
						if (!empty($readers[$x])) {
								
							if (false !== $user_obj = $this->findUserAndCreate($readers[$x]->getUsername(), false, true)) {
	
								if (false === $customACL_obj->isUserGranted($folder, $user_obj, 'view')) {
									$customACL_obj->insertObjectAce($folder, $user_obj, 'view', false);
								}
							}
						}
					}
				}
	
				if ($reader_groups->count())
				{
					for ($x = 0; $x < $reader_groups->count(); $x++) {
							
						if (!empty($reader_groups[$x]))
						{
							if (false === $customACL_obj->isRoleGranted($folder, $reader_groups[$x]->getRole(), 'view')) {
								$customACL_obj->insertObjectAce($folder, $reader_groups[$x]->getRole(), 'view');
							}
						}
					}
				}
	
				$authors = $customACL_obj->getObjectACEUsers($folder, 'create');
				$author_groups = $customACL_obj->getObjectACEGroups($folder, 'create');
				if ($authors->count() > 0) {
					for ($x = 0; $x < $authors->count(); $x++) {
	
						if (false === $this->objectArrayContains($readers, $authors[$x], true)) {
	
							$user = $this->findUserAndCreate($authors[$x]->getUsername(), false, true);
							if (!empty($user)) {
								$customACL_obj->removeUserACE($folder, $user, 'create');
							}
						}
					}
				}
				if ($author_groups->count() > 0) {
					for ($x = 0; $x < $author_groups->count(); $x++) {
	
						if (false === $this->objectArrayContains($reader_groups, $author_groups[$x]))
						{
							$customACL_obj->removeUserACE($folder, $author_groups[$x]->getRole(), 'create', true);
						}
					}
				}
	
				$current_owners	= $customACL_obj->getObjectACEUsers($folder, 'owner');
				$current_masters= $customACL_obj->getObjectACEUsers($folder, 'master');
				$parent_authors = $customACL_obj->getObjectACEUsers($em->getReference('DocovaBundle:Folders', $fld['Parent']), 'create');
				$cowner_groups	= $customACL_obj->getObjectACEGroups($folder, 'owner');
				$cmaster_groups	= $customACL_obj->getObjectACEGroups($folder, 'master');
				$pauthor_groups = $customACL_obj->getObjectACEGroups($em->getReference('DocovaBundle:Folders', $fld['Parent']), 'create');
				if ($current_masters->count() > 0)
				{
					foreach ($current_masters as $master)
					{
						$current_owners->add($master);
					}
				}
				$current_masters = null;
				if ($cmaster_groups->count() > 0)
				{
					foreach ($cmaster_groups as $master)
					{
						$cowner_groups->add($master);
					}
				}
				$cmaster_groups = $master = null;
				if ($current_owners->count())
				{
					foreach ($current_owners as $owner)
					{
						if (false === $this->objectArrayContains($readers, $owner, true))
						{
							if ($parent_authors->count() > 0)
							{
								if (false === $this->objectArrayContains($parent_authors, $owner, true))
								{
									if (false !== $owner = $this->findUserAndCreate($owner->getUsername(), false, true)) {
										$customACL_obj->removeUserACE($folder, $owner, array('owner', 'master'));
									}
								}
							}
							else
							{
								if (false !== $owner = $this->findUserAndCreate($owner->getUsername(), false, true)) {
									$customACL_obj->removeUserACE($folder, $owner, array('owner', 'master'));
								}
							}
						}
					}
				}
				if ($cowner_groups->count())
				{
					foreach ($cowner_groups as $owner)
					{
						if (false === $this->objectArrayContains($reader_groups, $owner, true))
						{
							if ($pauthor_groups->count() > 0)
							{
								if (false === $this->objectArrayContains($pauthor_groups, $owner))
								{
									$customACL_obj->removeUserACE($folder, $owner->getRole(), array('owner', 'master'), true);
								}
							}
							else
							{
								$customACL_obj->removeUserACE($folder, $owner->getRole(), array('owner', 'master'), true);
							}
						}
					}
				}
				$folder = null;
			}
		}
	}

	/**
	 * Paste (duplicate) copied folders to the target folder
	 *
	 * @param array $subfolders
	 * @param array $source_folder
	 * @param \Docova\DocovaBundle\Entity\Libraries $target_library
	 * @param string $parent_folder [optional()]
	 * @param boolean $folder_exists [optional()]
	 * @return string|void
	 */
	private function pasteCopiedFolders($subfolders, $source_folder, $target_library, $parent_folder = null, $folder_exists = false)
	{
		if (is_array($subfolders))
		{
			$pasted_folders = array();
			$conn = $this->getDoctrine()->getConnection();
			$conn->beginTransaction();
			try {
				foreach ($subfolders as $folder)
				{
					if ($folder['id'] == $source_folder) {
						$new_folder = $this->createCopy($conn, $source_folder, $target_library, $parent_folder, $folder_exists);
						$pasted_folders[] = array(
							'source_id' => $source_folder,
							'new_id' => $new_folder
						);
					}
					elseif (false != $parent_folder = $this->searchInPastedFolders($pasted_folders, $folder['Parent']))
					{
//						$tfolder = $em->getRepository('DocovaBundle:Folders')->getOneBy(array('id' => $folder['id'], 'Del' => false));
//						if (!empty($tfolder)) {
						$new_folder = $this->createCopy($conn, $folder['id'], $target_library, $parent_folder);
						$pasted_folders[] = array(
							'source_id' => $folder['id'],
							'new_id' => $new_folder
						);
//						}
//						unset($tfolder);
					}
				}
				$conn->commit();
			}
			catch (\Exception $e) {
				$conn->rollback();
			}
//			$em->clear();
			$em = $source_folder = null;
			return $pasted_folders[0]['new_id'];
//			}
		}
	}

	/**
	 * Search for a foldre ID in pasted folders
	 *
	 * @param array $pasted_folders
	 * @param string $needle
	 * @return \Docova\DocovaBundle\Entity\Folders|boolean
	 */
	private function searchInPastedFolders($pasted_folders, $needle)
	{
		$new_folder = false;
		if (!empty($pasted_folders) && is_array($pasted_folders))
		{
			foreach ($pasted_folders as $pf) {
				if ($pf['source_id'] === $needle) {
					$new_folder = $pf['new_id'];
					break;
				}
			}
		}
		return $new_folder;
	}
	
	/**
	 * Create a copy of the folder in target folder
	 *
	 * @param array $folder
	 * @param \Docova\DocovaBundle\Entity\Libraries $target_library
	 * @param string $parent_folder [optional()]
	 * @param boolean $folder_exists [optional(false)]
	 * @param object $user [optional(false)]
	 * @return string
	 */
	public function createCopy($conn, $folder, $target_library, $parent_folder = null, $folder_exists = false, $user = null, $path = null)
	{
		if (empty($user))
		{
			$user = $this->user;
		}
		if (empty($path))
		{
			$path = $this->UPLOAD_FILE_PATH;
		}
		$driver = $conn->getDriver()->getName();
		if ($driver == 'pdo_mysql')
		{
			if (!empty($parent_folder)) {
				$lastPos = $conn->fetchArray("SELECT MAX(SUBSTRING_INDEX(`Position`, '.', -1)) FROM `tb_library_folders` WHERE `Parent_Id` = ?", array($parent_folder));
				$new_pos = !empty($lastPos[0]) ? ((int)$lastPos[0] + 1) : 1;
				$position = "CONCAT((SELECT `position` FROM `tb_library_folders` WHERE `id` = '$parent_folder'), '.', '$new_pos')";
			}
			else {
				$position = "(SELECT MAX(`Position`) + 1 FROM `tb_library_folders` WHERE `Parent_Id` IS NULL AND `Library_Id` = '$target_library')";
			}
			$pf_query = empty($parent_folder) ? 'NULL' : "'$parent_folder'";
			$folder_name = $folder_exists === true ? "CONCAT('Copy of ', `Folder_Name`)" : '`Folder_Name`';
			$conn->beginTransaction('mypoint');
			try {
				$uuid = $conn->fetchArray('SELECT UUID()');
				$uuid = $uuid[0];
				$query = "INSERT INTO `tb_library_folders` (`id`, `Date_Created`, `Created_By`, `Library_Id`, `Parent_Id`, `Position`, `Folder_Name`, `Description`, `Icon_Normal`, `Icon_Selected`, `Default_DocType`, `Paging_Count`, `Sync`, `Sync_Subfolders`, `Default_Perspective`, `Disable_ACF`, `Enable_ACR`, `Private_Draft`, `Set_AAE`, `Restrict_RtA`, `Set_DVA`, `Disable_CCP`, `Disable_TCB`, `Filtering`, `Del`)
							SELECT '$uuid', NOW(), '{$user->getId()}', '$target_library', $pf_query, $position, $folder_name, `Description`, `Icon_Normal`, `Icon_Selected`, `Default_DocType`, `Paging_Count`, `Sync`, `Sync_Subfolders`, `Default_Perspective`, `Disable_ACF`, `Enable_ACR`, `Private_Draft`, `Set_AAE`, `Restrict_RtA`, `Set_DVA`, `Disable_CCP`, `Disable_TCB`, `Filtering`, `Del`
							FROM `tb_library_folders`
							WHERE `id` = '$folder' AND `Del` = 0";
				$stmt = $conn->prepare($query);
				$stmt->execute();
	
				$query = "INSERT INTO `tb_folders_log` (`id`, `Log_Action`, `Log_Status`, `Log_Date`, `Log_Details`, `Folder_Id`, `Log_Author`)
						 SELECT (SELECT UUID()), 2, 1, NOW(), 'Created new folder.', ?, ?";
				$stmt  = $conn->prepare($query);
				$stmt->bindValue(1, $uuid);
				$stmt->bindValue(2, $user->getId());
				$stmt->execute();
	
				if (!empty($parent_folder))
				{
					$query = "INSERT INTO `tb_folder_doctypes` (`Folder_Id`, `Doc_Type_Id`) SELECT '$uuid', `Doc_Type_Id` FROM `tb_folder_doctypes` WHERE `Folder_Id` = '$parent_folder'";
					$stmt  = $conn->prepare($query);
					$stmt->execute();
						
					$query = "INSERT INTO `tb_sync_users` (`Folder_Id`, `User_Id`) SELECT '$uuid', `User_Id` FROM `tb_sync_users` WHERE `Folder_Id` = '$parent_folder'";
					$stmt  = $conn->prepare($query);
					$stmt->execute();
				}
				else {
					$query = "INSERT INTO `tb_sync_users` (`Folder_Id`, `User_Id`) SELECT '$uuid', `User_Id` FROM `tb_sync_users` WHERE `Folder_Id` = '$folder'";
					$stmt  = $conn->prepare($query);
					$stmt->execute();
				}
	
				/************ APLY ACL *************/
				$query = "INSERT INTO `acl_object_identities` (`object_identifier`, `entries_inheriting`, `class_id`) SELECT '$uuid', 1, `id` FROM `acl_classes` WHERE `class_type` = ?";
				$stmt  = $conn->prepare($query);
				$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Folders');
				$stmt->execute();
				$acl_id = $conn->lastInsertId('id');
				$query = "INSERT INTO `acl_object_identity_ancestors` (`object_identity_id`, `ancestor_id`) VALUES ('$acl_id', '$acl_id')";
				$stmt  = $conn->prepare($query);
				$stmt->execute();
				$query = "INSERT INTO `acl_entries` (`object_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`, `class_id`, `security_identity_id`)
							SELECT '$acl_id', 0, 8, 1, 'all', 0, 0,
							(SELECT `id` FROM `acl_classes` WHERE `class_type` = ?),
							(SELECT `id` FROM `acl_security_identities` WHERE `identifier` = 'ROLE_USER')";
				$stmt = $conn->prepare($query);
				$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Folders');
				$stmt->execute();
				$query = "INSERT INTO `acl_entries` (`object_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`, `class_id`, `security_identity_id`)
							SELECT '$acl_id', 0, 128, 1, 'all', 0, 0,
							(SELECT `id` FROM `acl_classes` WHERE `class_type` = ?),
							(SELECT `id` FROM `acl_security_identities` WHERE `identifier` = ?)";
				$stmt = $conn->prepare($query);
				$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Folders');
				$stmt->bindValue(2, 'Docova\DocovaBundle\Entity\UserAccounts-'.$user->getUsername());
				$stmt->execute();
				if (!empty($parent_folder))
				{
					$query = "SELECT DISTINCT(`ACE`.`security_identity_id`) FROM `acl_entries` AS `ACE` INNER JOIN `acl_object_identities` AS `ACI` ON (`ACE`.`object_identity_id` = `ACI`.`id`) INNER JOIN `acl_security_identities` AS `ACS` ON (`ACE`.`security_identity_id` = `ACS`.`id`) WHERE `mask` = 1 AND `ACI`.`object_identifier` = '$parent_folder' AND `ACS`.`identifier` <> 'ROLE_USER'";
					$parent_readers = $conn->fetchArray($query);
					if (count($parent_readers) > 0 && !empty($parent_readers[0]))
					{
						$query = "INSERT INTO `acl_entries` (`object_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`, `class_id`, `security_identity_id`)
									SELECT '$acl_id', 0, `ACE`.`mask`, `ACE`.`granting`, `ACE`.`granting_strategy`, `ACE`.`audit_success`, `ACE`.`audit_failure`, `ACE`.`class_id`, `ACE`.`security_identity_id`
									FROM `acl_entries` AS `ACE` INNER JOIN `acl_object_identities` AS `ACI` ON (`ACE`.`object_identity_id` = `ACI`.`id`) WHERE `ACI`.`object_identifier` = '$parent_folder' AND `ACE`.`mask` = 1";
						$stmt = $conn->prepare($query);
						$stmt->execute();

						$readers = implode(',', $parent_readers);
						$query = "INSERT INTO `acl_entries` (`object_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`, `class_id`, `security_identity_id`)
									SELECT '$acl_id', 0, `ACE`.`mask`, `ACE`.`granting`, `ACE`.`granting_strategy`, `ACE`.`audit_success`, `ACE`.`audit_failure`, `ACE`.`class_id`, `ACE`.`security_identity_id`
									FROM `acl_entries` AS `ACE` INNER JOIN `acl_object_identities` AS `ACI` ON (`ACE`.`object_identity_id` = `ACI`.`id`) WHERE `ACI`.`object_identifier` = '$folder' AND `ACE`.`mask` IN (2, 4, 64, 128) AND `ACE`.`security_identity_id` IN ($readers)";
						$stmt = $conn->prepare($query);
						$stmt->execute();
					}
					else {
						$query = "INSERT INTO `acl_entries` (`object_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`, `class_id`, `security_identity_id`)
									SELECT '$acl_id', 0, `ACE`.`mask`, `ACE`.`granting`, `ACE`.`granting_strategy`, `ACE`.`audit_success`, `ACE`.`audit_failure`, `ACE`.`class_id`, `ACE`.`security_identity_id`
									FROM `acl_entries` AS `ACE` INNER JOIN `acl_object_identities` AS `ACI` ON (`ACE`.`object_identity_id` = `ACI`.`id`) WHERE `ACI`.`object_identifier` = '$folder' AND `ACE`.`mask` IN (1, 2, 4, 64, 128)";
						$stmt = $conn->prepare($query);
						$stmt->execute();
					}
				}
				else {
					$query = "INSERT INTO `acl_entries` (`object_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`, `class_id`, `security_identity_id`)
								SELECT '$acl_id', 0, `ACE`.`mask`, `ACE`.`granting`, `ACE`.`granting_strategy`, `ACE`.`audit_success`, `ACE`.`audit_failure`, `ACE`.`class_id`, `ACE`.`security_identity_id`
								FROM `acl_entries` AS `ACE` INNER JOIN `acl_object_identities` AS `ACI` ON (`ACE`.`object_identity_id` = `ACI`.`id`) WHERE `ACI`.`object_identifier` = '$folder' AND `ACE`.`mask` IN (1, 2, 4, 64, 128)";
					$stmt = $conn->prepare($query);
					$stmt->execute();
				}

				$conn->query('SET @ordering = -1;');
				$query = "UPDATE `acl_entries` SET `ace_order` = (@ordering := @ordering + 1) WHERE `object_identity_id` = '$acl_id' ORDER BY `id` DESC";
				$stmt = $conn->prepare($query);
				$stmt->execute();

				$folder_documents = $conn->fetchAll("SELECT `d`.`id`, `d`.`Doc_Version`, `dt`.`Enable_Lifecycle`, `dt`.`Initial_Status`, `dt`.`Final_Status` FROM `tb_folders_documents` AS `d` INNER JOIN `tb_document_types` AS `dt` ON (`d`.`Doc_Type_Id` = `dt`.`id`) WHERE `d`.`Folder_Id` = '$folder' AND `d`.`Trash` = 0 AND `d`.`Archived` = 0");
				if (!empty($folder_documents) && !empty($folder_documents[0]['id']))
				{
					foreach ($folder_documents as $document)
					{
						$duuid = $conn->fetchArray('SELECT UUID()');
						$duuid = $duuid[0];
						$version = !empty($document['Doc_Version']) ? "'0.0'" : 'NULL';
						$revision = !empty($document['Doc_Version']) ? 0 : 'NULL';
						$status = $document['Enable_Lifecycle'] == 1 ? $document['Initial_Status'] : $document['Final_Status'];
						$status_no = $document['Enable_Lifecycle'] == 1 ? 0 : 1;
						$query = "INSERT INTO `tb_folders_documents` (`id`, `Folder_Id`, `Doc_Version`, `Revision`, `Doc_Status`, `Status_No`, `Date_Created`, `Created_By`, `Locked`, `Trash`, `Archived`, `Doc_Owner`, `Doc_Title`, `Description`, `Keywords`, `Doc_Type_Id`, `Indexed`)
									SELECT '$duuid', '$uuid', $version, $revision, '$status', '$status_no', NOW(), ?, 0, 0, 0, ?, `Doc_Title`, `Description`, `Keywords`, `Doc_Type_Id`, 0
									FROM `tb_folders_documents` WHERE `id` = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $user->getId());
						$stmt->bindValue(2, $user->getId());
						$stmt->bindValue(3, $document['id']);
						$stmt->execute();
	
						$query = "INSERT INTO `tb_form_datetime_values` (`id`, `Trash`, `Doc_Id`, `Field_Value`, `Field_Id`)
								 SELECT UUID(), 0, ?, `Field_Value`, `Field_Id` FROM `tb_form_datetime_values` WHERE `Doc_Id` = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "INSERT INTO `tb_form_group_values` (`id`, `Trash`, `Doc_Id`, `Field_Value`, `Field_Id`)
								 SELECT UUID(), 0, ?, `Field_Value`, `Field_Id` FROM `tb_form_group_values` WHERE `Doc_Id` = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "INSERT INTO `tb_form_name_values` (`id`, `Trash`, `Doc_Id`, `Field_Value`, `Field_Id`)
								 SELECT UUID(), 0, ?, `Field_Value`, `Field_Id` FROM `tb_form_name_values` WHERE `Doc_Id` = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "INSERT INTO `tb_form_numeric_values` (`id`, `Trash`, `Doc_Id`, `Field_Value`, `Field_Id`)
								 SELECT UUID(), 0, ?, `Field_Value`, `Field_Id` FROM `tb_form_numeric_values` WHERE `Doc_Id` = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "INSERT INTO `tb_form_text_values` (`id`, `Trash`, `Doc_Id`, `Summary_Value`, `Field_Value`, `Field_Id`)
								 SELECT UUID(), 0, ?, `Summary_Value`, `Field_Value`, `Field_Id` FROM `tb_form_text_values` WHERE `Doc_Id` = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "SELECT * FROM `tb_attachments_details` WHERE `Doc_Id` = ?";
						$attachments = $conn->fetchAll($query, array($document['id']));
						if (!empty($attachments) && !empty($attachments[0]['id']))
						{
							$query = 'INSERT INTO `tb_attachments_details` (`id`, `Doc_Id`, `Author_Id`, `File_Name`, `File_Date`, `File_Size`, `File_Mime_Type`, `Field_Id`) VALUES ';
							$values = '';
							foreach ($attachments as $att)
							{
								if (!file_exists($path.DIRECTORY_SEPARATOR.$duuid.DIRECTORY_SEPARATOR.md5($att['File_Name'])))
								{
									if (!is_dir($path.DIRECTORY_SEPARATOR.$duuid.DIRECTORY_SEPARATOR)) {
										mkdir($path.DIRECTORY_SEPARATOR.$duuid.DIRECTORY_SEPARATOR);
									}
	
									//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$entity->getId(), '0777');
									copy($path.DIRECTORY_SEPARATOR.$document['id'].DIRECTORY_SEPARATOR.md5($att['File_Name']), $path.DIRECTORY_SEPARATOR.$duuid.DIRECTORY_SEPARATOR.md5($att['File_Name']));
									//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$entity->getId(), '0666');
	
									$values .= "(UUID(), '$duuid', '{$user->getId()}', '{$att['File_Name']}', '{$att['File_Date']}', '{$att['File_Size']}', '{$att['File_Mime_Type']}', '{$att['Field_Id']}'),";
								}
							}
							if (!empty($values))
							{
								$values = substr_replace($values, '', -1);
								$query .= $values;
								$stmt = $conn->prepare($query);
								$stmt->execute();
							}
						}
	
						$query = "INSERT INTO `acl_object_identities` (`object_identifier`, `entries_inheriting`, `class_id`) SELECT '$duuid', 1, `id` FROM `acl_classes` WHERE `class_type` = ?";
						$stmt  = $conn->prepare($query);
						$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Documents');
						$stmt->execute();
						$acl_id = $conn->lastInsertId('id');
						$query = "INSERT INTO `acl_object_identity_ancestors` (`object_identity_id`, `ancestor_id`) VALUES ('$acl_id', '$acl_id')";
						$stmt  = $conn->prepare($query);
						$stmt->execute();
						$query = "INSERT INTO `acl_entries` (`object_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`, `class_id`, `security_identity_id`)
									SELECT '$acl_id', 0, 128, 1, 'all', 0, 0,
									(SELECT `id` FROM `acl_classes` WHERE `class_type` = ?),
									(SELECT `id` FROM `acl_security_identities` WHERE `identifier` = ?)";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Documents');
						$stmt->bindValue(2, 'Docova\DocovaBundle\Entity\UserAccounts-' . $user->getUsername());
						$stmt->execute();
	
						$query = "INSERT INTO `acl_entries` (`object_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`, `class_id`, `security_identity_id`)
									SELECT '$acl_id', 0, `ACE`.`mask`, `ACE`.`granting`, `ACE`.`granting_strategy`, `ACE`.`audit_success`, `ACE`.`audit_failure`, `ACE`.`class_id`, `ACE`.`security_identity_id`
									FROM `acl_entries` AS `ACE` INNER JOIN `acl_object_identities` AS `ACI` ON (`ACE`.`object_identity_id` = `ACI`.`id`) WHERE `ACI`.`object_identifier` = '{$document['id']}' AND (`ACE`.`mask` = 4 OR `ACE`.`mask` = 1)";
						$stmt = $conn->prepare($query);
						$stmt->execute();

						$conn->query('SET @ordering = 0;');
						$query = "UPDATE `acl_entries` SET `ace_order` = (@ordering := @ordering + 1) WHERE `object_identity_id` = '$acl_id'";
						$stmt = $conn->prepare($query);
						$stmt->execute();
					}
				}
				$conn->commit('mypoint');
			}
			catch (\Exception $e) {
				$logger = $this->get('logger');
				$logger->error($e->getMessage());
				$uuid = null;
				$conn->rollback('mypoint');
			}
		}
		elseif ($driver == 'pdo_sqlsrv') {
			if (!empty($parent_folder)) {
				//$lastPos = $conn->fetchArray("SELECT CASE CHARINDEX('.', REVERSE(MAX([Position]))) WHEN 0 THEN REVERSE(MAX([Position])) ELSE REVERSE(LEFT(REVERSE(MAX([Position])), CHARINDEX('.', REVERSE(MAX([Position]))) - 1)) END FROM tb_library_folders WHERE Parent_Id = ?", array($parent_folder));
				//CASE CHARINDEX('.', REVERSE(MAX([Position]))) WHEN 0 THEN REVERSE(MAX([Position])) ELSE
				$lastPosQuery="SELECT MAX(FolderPosition) FROM ( ";
				$lastPosQuery.="SELECT TRY_CONVERT(integer,REPLACE(position,(SELECT Position from tb_library_folders where id=folders.parent_id)+'.','')) AS FolderPosition ";
				$lastPosQuery.="FROM [tb_library_folders] folders ";
				$lastPosQuery.="WHERE [Parent_Id] = ?";
				$lastPosQuery.=") T";
	
				$lastPos = $conn->fetchArray($lastPosQuery, array($parent_folder));
				//$lastPos = $conn->fetchArray("SELECT REVERSE(LEFT(REVERSE(MAX([Position])), CHARINDEX('.', REVERSE(MAX([Position]))) - 1)) FROM tb_library_folders WHERE Parent_Id = ?", array($parent_folder));
				$new_pos = !empty($lastPos[0]) ? ((int)$lastPos[0] + 1) : 1;
				$position = "((SELECT [Position] FROM tb_library_folders WHERE id = '$parent_folder')+'.".$new_pos."')";
			}
			else {
				//$position = "(SELECT MAX([Position]) + 1 FROM tb_library_folders WHERE Parent_Id IS NULL AND Library_Id = '$target_library')";
				$position = "(SELECT MAX(TRY_CONVERT(integer,[Position])) + 1 FROM tb_library_folders WHERE Parent_Id IS NULL AND Library_Id = '$target_library')";
			}
			$pf_query = empty($parent_folder) ? 'NULL' : "'$parent_folder'";
			$folder_name = $folder_exists === true ? "('Copy of ' + Folder_Name)" : 'Folder_Name';
			$uuid = $conn->fetchArray('SELECT NEWID()');
			$uuid = $uuid[0];

			$sourceFolderSelect="SELECT '$uuid', GETDATE(), '{$user->getId()}', '$target_library', $pf_query, $position, $folder_name, [Description], [Icon_Normal], [Icon_Selected], [Default_DocType], [Paging_Count], [Sync], [Sync_Subfolders], [Default_Perspective], [Disable_ACF], [Enable_ACR], [Private_Draft], [Set_AAE], [Restrict_RtA], [Set_DVA], [Disable_CCP], [Disable_TCB], [Filtering], [Del]
								FROM [tb_library_folders]
								WHERE [id] = '$folder' AND [Del] = 0";

			$sourceFolder = $conn->fetchArray($sourceFolderSelect);
			if (empty($sourceFolder) || empty($sourceFolder[0])){
				return;
			}
		
			$conn->beginTransaction('mypoint');
			try {
				$query = "INSERT INTO tb_library_folders ([id], [Date_Created], [Created_By], [Library_Id], [Parent_Id], [Position], [Folder_Name], [Description], [Icon_Normal], [Icon_Selected], [Default_DocType], [Paging_Count], [Sync], [Sync_Subfolders], [Default_Perspective], [Disable_ACF], [Enable_ACR], [Private_Draft], [Set_AAE], [Restrict_RtA], [Set_DVA], [Disable_CCP], [Disable_TCB], [Filtering], [Del])
							SELECT '$uuid', GETDATE(), '{$user->getId()}', '$target_library', $pf_query, $position, $folder_name, [Description], [Icon_Normal], [Icon_Selected], [Default_DocType], [Paging_Count], [Sync], [Sync_Subfolders], [Default_Perspective], [Disable_ACF], [Enable_ACR], [Private_Draft], [Set_AAE], [Restrict_RtA], [Set_DVA], [Disable_CCP], [Disable_TCB], [Filtering], [Del]
							FROM [tb_library_folders]
							WHERE [id] = '$folder' AND [Del] = 0";
				$stmt	= $conn->prepare($query);
				$stmt->execute();

				$query = "INSERT INTO [tb_folders_log] ([id], [Log_Action], [Log_Status], [Log_Date], [Log_Details], [Folder_Id], [Log_Author])
						  SELECT NEWID(), 2, 1, GETDATE(), 'Created new folder.', ?, ?";
				$stmt  = $conn->prepare($query);
				$stmt->bindValue(1, $uuid);
				$stmt->bindValue(2, $user->getId());
				$stmt->execute();
	
				if (!empty($parent_folder))
				{
					$query = "INSERT INTO [tb_folder_doctypes] ([Folder_Id], [Doc_Type_Id]) SELECT '$uuid', [Doc_Type_Id] FROM [tb_folder_doctypes] WHERE [Folder_Id] = '$parent_folder'";
					$stmt  = $conn->prepare($query);
					$stmt->execute();
	
					$query = "INSERT INTO [tb_sync_users] ([Folder_Id], [User_Id]) SELECT '$uuid', [User_Id] FROM [tb_sync_users] WHERE [Folder_Id] = '$parent_folder'";
					$stmt  = $conn->prepare($query);
					$stmt->execute();
				}
				else {
					$query = "INSERT INTO [tb_sync_users] ([Folder_Id], [User_Id]) SELECT '$uuid', [User_Id] FROM [tb_sync_users] WHERE [Folder_Id] = '$folder'";
					$stmt  = $conn->prepare($query);
					$stmt->execute();
				}
	
	 			/************ APPLY ACL *************/
				$query = "INSERT INTO [acl_object_identities] ([object_identifier], [entries_inheriting], [class_id]) SELECT '$uuid', 1, [id] FROM [acl_classes] WHERE [class_type] = ?";
				$stmt  = $conn->prepare($query);
				$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Folders');
				$stmt->execute();
				$acl_id = $conn->lastInsertId();
				$query = "INSERT INTO [acl_object_identity_ancestors] ([object_identity_id], [ancestor_id]) VALUES ('$acl_id', '$acl_id')";
				$stmt  = $conn->prepare($query);
				$stmt->execute();
				$query = "INSERT INTO [acl_entries] ([object_identity_id], [ace_order], [mask], [granting], [granting_strategy], [audit_success], [audit_failure], [class_id], [security_identity_id])
							SELECT '$acl_id', 0, 8, 1, 'all', 0, 0,
							(SELECT [id] FROM [acl_classes] WHERE [class_type] = ?),
							(SELECT [id] FROM [acl_security_identities] WHERE [identifier] = 'ROLE_USER')";
				$stmt = $conn->prepare($query);
				$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Folders');
				$stmt->execute();
				$query = "INSERT INTO [acl_entries] ([object_identity_id], [ace_order], [mask], [granting], [granting_strategy], [audit_success], [audit_failure], [class_id], [security_identity_id])
							SELECT '$acl_id', 0, 128, 1, 'all', 0, 0,
							(SELECT [id] FROM [acl_classes] WHERE [class_type] = ?),
	 						(SELECT [id] FROM [acl_security_identities] WHERE [identifier] = ?)";
		 		$stmt = $conn->prepare($query);
		 		$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Folders');
		 		$stmt->bindValue(2, 'Docova\DocovaBundle\Entity\UserAccounts-'.$user->getUsername());
				$stmt->execute();
				if (!empty($parent_folder))
				{
					$query = "SELECT DISTINCT([ACE].[security_identity_id]) FROM [acl_entries] AS [ACE] INNER JOIN [acl_object_identities] AS [ACI] ON ([ACE].[object_identity_id] = [ACI].[id]) INNER JOIN [acl_security_identities] AS [ACS] ON ([ACE].[security_identity_id] = [ACS].[id]) WHERE [mask] = 1 AND [ACI].[object_identifier] = '$parent_folder' AND [ACS].[identifier] <> 'ROLE_USER'";
					$parent_readers = $conn->fetchArray($query);
					if (count($parent_readers) > 0 && !empty($parent_readers[0]))
					{
						$query = "INSERT INTO [acl_entries] ([object_identity_id], [ace_order], [mask], [granting], [granting_strategy], [audit_success], [audit_failure], [class_id], [security_identity_id])
									 SELECT '$acl_id', 0, [ACE].[mask], [ACE].[granting], [ACE].[granting_strategy], [ACE].[audit_success], [ACE].[audit_failure], [ACE].[class_id], [ACE].[security_identity_id]
									 FROM [acl_entries] AS [ACE] INNER JOIN [acl_object_identities] AS [ACI] ON ([ACE].[object_identity_id] = [ACI].[id]) WHERE [ACI].[object_identifier] = '$parent_folder' AND [ACE].[mask] = 1";
						$stmt = $conn->prepare($query);
						$stmt->execute();
	
						$readers = implode(',', $parent_readers);
						$query = "INSERT INTO [acl_entries] ([object_identity_id], [ace_order], [mask], [granting], [granting_strategy], [audit_success], [audit_failure], [class_id], [security_identity_id])
						SELECT '$acl_id', 0, [ACE].[mask], [ACE].[granting], [ACE].[granting_strategy], [ACE].[audit_success], [ACE].[audit_failure], [ACE].[class_id], [ACE].[security_identity_id]
						FROM [acl_entries] AS [ACE] INNER JOIN [acl_object_identities] AS [ACI] ON ([ACE].[object_identity_id] = [ACI].[id]) WHERE [ACI].[object_identifier] = '$folder' AND [ACE].[mask] IN (2, 4, 64, 128) AND [ACE].[security_identity_id] IN ($readers)";
						$stmt = $conn->prepare($query);
	 					$stmt->execute();
					}
	 				else {
				 		$query = "INSERT INTO [acl_entries] ([object_identity_id], [ace_order], [mask], [granting], [granting_strategy], [audit_success], [audit_failure], [class_id], [security_identity_id])
							 		SELECT '$acl_id', 0, [ACE].[mask], [ACE].[granting], [ACE].[granting_strategy], [ACE].[audit_success], [ACE].[audit_failure], [ACE].[class_id], [ACE].[security_identity_id]
							 		FROM [acl_entries] AS [ACE] INNER JOIN [acl_object_identities] AS [ACI] ON ([ACE].[object_identity_id] = [ACI].[id]) WHERE [ACI].[object_identifier] = '$folder' AND [ACE].[mask] IN (1, 2, 4, 64, 128)";
				 		$stmt = $conn->prepare($query);
				 		$stmt->execute();
					}
				}
				else {
					$query = "INSERT INTO [acl_entries] ([object_identity_id], [ace_order], [mask], [granting], [granting_strategy], [audit_success], [audit_failure], [class_id], [security_identity_id])
								SELECT '$acl_id', 0, [ACE].[mask], [ACE].[granting], [ACE].[granting_strategy], [ACE].[audit_success], [ACE].[audit_failure], [ACE].[class_id], [ACE].[security_identity_id]
								FROM [acl_entries] AS [ACE] INNER JOIN [acl_object_identities] AS [ACI] ON ([ACE].[object_identity_id] = [ACI].[id]) WHERE [ACI].[object_identifier] = '$folder' AND [ACE].[mask] IN (1, 2, 4, 64, 128)";
					$stmt = $conn->prepare($query);
					$stmt->execute();
				}
	
//				$conn->query('DECLARE @ordering int = -1;');
// 				$query = "UPDATE [acl_entries] SET [ace_order] = (@ordering := @ordering + 1) WHERE [object_identity_id] = '$acl_id' ORDER BY [id] DESC";
				$query = "UPDATE [ocl] SET [ace_order]=[Row] FROM (SELECT ROW_NUMBER() OVER(ORDER BY [id] DESC) - 1 AS [Row], [ace_order] FROM [acl_entries] WHERE [object_identity_id] = '$acl_id') AS [ocl]";
				$stmt = $conn->prepare($query);
				$stmt->execute();
	
				$folder_documents = $conn->fetchAll("SELECT [d].[id], [d].[Doc_Version], [dt].[Enable_Lifecycle], [dt].[Initial_Status], [dt].[Final_Status] FROM [tb_folders_documents] AS [d] INNER JOIN [tb_document_types] AS [dt] ON ([d].[Doc_Type_Id] = [dt].[id]) WHERE [d].[Folder_Id] = '$folder' AND [d].[Trash] = 0 AND [d].[Archived] = 0");
				if (!empty($folder_documents) && !empty($folder_documents[0]['id']))
				{
					foreach ($folder_documents as $document)
					{
						$duuid = $conn->fetchArray('SELECT NEWID()');
						$duuid = $duuid[0];
						$version = !empty($document['Doc_Version']) ? "'0.0'" : 'NULL';
						$revision = !empty($document['Doc_Version']) ? 0 : 'NULL';
						$status = $document['Enable_Lifecycle'] == 1 ? $document['Initial_Status'] : $document['Final_Status'];
						$status_no = $document['Enable_Lifecycle'] == 1 ? 0 : 1;
						$query = "INSERT INTO [tb_folders_documents] ([id], [Folder_Id], [Doc_Version], [Revision], [Doc_Status], [Status_No], [Date_Created], [Created_By], [Locked], [Trash], [Archived], [Doc_Owner], [Doc_Title], [Description], [Keywords], [Doc_Type_Id], [Indexed])
						SELECT '$duuid', '$uuid', $version, $revision, '$status', '$status_no', GETDATE(), ?, 0, 0, 0, ?, [Doc_Title], [Description], [Keywords], [Doc_Type_Id], 0
						FROM [tb_folders_documents] WHERE [id] = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $user->getId());
						$stmt->bindValue(2, $user->getId());
						$stmt->bindValue(3, $document['id']);
						$stmt->execute();
						$query = "INSERT INTO [tb_form_datetime_values] ([id], [Trash], [Doc_Id], [Field_Value], [Field_Id])
						SELECT NEWID(), 0, ?, [Field_Value], [Field_Id] FROM [tb_form_datetime_values] WHERE [Doc_Id] = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "INSERT INTO [tb_form_group_values] ([id], [Trash], [Doc_Id], [Field_Value], [Field_Id])
						SELECT NEWID(), 0, ?, [Field_Value], [Field_Id] FROM [tb_form_group_values] WHERE [Doc_Id] = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "INSERT INTO [tb_form_name_values] ([id], [Trash], [Doc_Id], [Field_Value], [Field_Id])
						SELECT NEWID(), 0, ?, [Field_Value], [Field_Id] FROM [tb_form_name_values] WHERE [Doc_Id] = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "INSERT INTO [tb_form_numeric_values] ([id], [Trash], [Doc_Id], [Field_Value], [Field_Id])
						SELECT NEWID(), 0, ?, [Field_Value], [Field_Id] FROM [tb_form_numeric_values] WHERE [Doc_Id] = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "INSERT INTO [tb_form_text_values] ([id], [Trash], [Doc_Id], [Summary_Value], [Field_Value], [Field_Id])
						SELECT NEWID(), 0, ?, [Summary_Value], [Field_Value], [Field_Id] FROM [tb_form_text_values] WHERE [Doc_Id] = ?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $duuid);
						$stmt->bindValue(2, $document['id']);
						$stmt->execute();
						$query = "SELECT * FROM [tb_attachments_details] WHERE [Doc_Id] = ?";
						$attachments = $conn->fetchAll($query, array($document['id']));
						if (!empty($attachments) && !empty($attachments[0]['id']))
						{
							$query = 'INSERT INTO [tb_attachments_details] ([id], [Doc_Id], [Author_Id], [File_Name], [File_Date], [File_Size], [File_Mime_Type], [Field_Id]) VALUES ';
							$values = '';
							foreach ($attachments as $att)
							{
								if (!file_exists($path.DIRECTORY_SEPARATOR.$duuid.DIRECTORY_SEPARATOR.md5($att['File_Name']))
									&& file_exists($path.DIRECTORY_SEPARATOR.$document['id'].DIRECTORY_SEPARATOR.md5($att['File_Name'])))
								{
									if (!is_dir($path.DIRECTORY_SEPARATOR.$duuid.DIRECTORY_SEPARATOR)) {
										mkdir($path.DIRECTORY_SEPARATOR.$duuid.DIRECTORY_SEPARATOR);
									}
	
									//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$entity->getId(), '0777');
									if (is_dir($path.DIRECTORY_SEPARATOR.$duuid.DIRECTORY_SEPARATOR)) {
										copy($path.DIRECTORY_SEPARATOR.$document['id'].DIRECTORY_SEPARATOR.md5($att['File_Name']), $path.DIRECTORY_SEPARATOR.$duuid.DIRECTORY_SEPARATOR.md5($att['File_Name']));
									}

									//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$entity->getId(), '0666');
									//single quotes will invalidate the SQL, replace with two single quotes
									$fileNameProcessed = str_replace("'","''", $att['File_Name']);
									$values .= "(NEWID(), '$duuid', '{$user->getId()}', '{$fileNameProcessed}', '{$att['File_Date']}', '{$att['File_Size']}', '{$att['File_Mime_Type']}', '{$att['Field_Id']}'),";
									//$values .= "(NEWID(), '$duuid', '{$this->user->getId()}', '{$att['File_Name']}', '{$att['File_Date']}', '{$att['File_Size']}', '{$att['File_Mime_Type']}', '{$att['Field_Id']}'),";
								}
							}
							if (!empty($values))
							{
								$values = substr_replace($values, '', -1);
								$query .= $values;
								$stmt = $conn->prepare($query);
								$stmt->execute();
							}
						}

						$query = "INSERT INTO [acl_object_identities] ([object_identifier], [entries_inheriting], [class_id]) SELECT '$duuid', 1, [id] FROM [acl_classes] WHERE [class_type] = ?";
						$stmt  = $conn->prepare($query);
						$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Documents');
						$stmt->execute();
						$acl_id = $conn->lastInsertId();
						$query = "INSERT INTO [acl_object_identity_ancestors] ([object_identity_id], [ancestor_id]) VALUES ('$acl_id', '$acl_id')";
						$stmt  = $conn->prepare($query);
						$stmt->execute();
						$query = "INSERT INTO [acl_entries] ([object_identity_id], [ace_order], [mask], [granting], [granting_strategy], [audit_success], [audit_failure], [class_id], [security_identity_id])
									SELECT '$acl_id', 0, 128, 1, 'all', 0, 0,
									(SELECT [id] FROM [acl_classes] WHERE [class_type] = ?),
									(SELECT [id] FROM [acl_security_identities] WHERE [identifier] = ?)";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, 'Docova\DocovaBundle\Entity\Documents');
						$stmt->bindValue(2, 'Docova\DocovaBundle\Entity\UserAccounts-' . $user->getUsername());
						$stmt->execute();
						$query = "INSERT INTO [acl_entries] ([object_identity_id], [ace_order], [mask], [granting], [granting_strategy], [audit_success], [audit_failure], [class_id], [security_identity_id])
									SELECT '$acl_id', 0, [ACE].[mask], [ACE].[granting], [ACE].[granting_strategy], [ACE].[audit_success], [ACE].[audit_failure], [ACE].[class_id], [ACE].[security_identity_id]
									FROM [acl_entries] AS [ACE] INNER JOIN [acl_object_identities] AS [ACI] ON ([ACE].[object_identity_id] = [ACI].[id]) WHERE [ACI].[object_identifier] = '{$document['id']}' AND ([ACE].[mask] = 4 OR [ACE].[mask] = 1)";
						$stmt = $conn->prepare($query);
						$stmt->execute();
	
// 						$conn->query('DECLARE @ordering int = 0;');
// 						$query = "UPDATE [acl_entries] SET [ace_order] = (@ordering := @ordering + 1) WHERE [object_identity_id] = '$acl_id'";
						$query = "UPDATE [ocl] SET [ace_order]=[Row] FROM (SELECT ROW_NUMBER() OVER(ORDER BY [id] DESC) - 1 AS [Row], [ace_order] FROM [acl_entries] WHERE [object_identity_id] = '$acl_id') AS [ocl]";
						$stmt = $conn->prepare($query);
						$stmt->execute();
					}
	 			}

				$conn->commit('mypoint');
			}
	 		catch (\Exception $e) {
				$logger = $this->get('logger');
				$logger->error($e->getMessage());
				$uuid = null;
				$conn->rollback('mypoint');
			}
		}
		return $uuid;
	}
	
	/**
	 * Create user profile and return user object
	 * @param \DOMDocument $post
	 * @param integer $index
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function createUserProfile($post, $index)
	{
		$first_name = $post->getElementsByTagName('MemberFirstName')->item($index)->nodeValue;
		$last_name = $post->getElementsByTagName('MemberLastName')->item($index)->nodeValue;
		$username = $post->getElementsByTagName('MemberUsername')->item($index)->nodeValue;
		$email = $post->getElementsByTagName('MemberEmail')->item($index)->nodeValue;
		$pass = $post->getElementsByTagName('MemberPassword')->item($index)->nodeValue;
		if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($pass)) 
		{
			if (empty($username)) 
			{
				$username = $first_name[0] . $last_name;
			}
			$em = $this->getDoctrine()->getManager();
			$exist = $em->getRepository('DocovaBundle:UserAccounts')->isDuplicatedUsername($username);
			if (!empty($exist)) 
			{
				$username = $em->getRepository('DocovaBundle:UserAccounts')->getNonDuplicateUsername($username);
				if ($username === false) 
				{
					throw $this->createNotFoundException('Sorry! Cannot generate user profile due to duplicated usernames.');
				}
			}
			
			$user_role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_USER'));
			$user = new UserAccounts();
			$user->setUserMail($email);
			$user->setPassword(md5(md5(md5($pass))));
			$user->setUsername($username);
			$docova_certifier_name = $this->global_settings->getDocovaBaseDn() ? $this->global_settings->getDocovaBaseDn() : "/DOCOVA";
			if(substr($docova_certifier_name, 0, 1) == "/" || substr($docova_certifier_name, 0, 1) == "\\"){
			    $docova_certifier_name = substr($docova_certifier_name, 1);
			}
			$unameAbbr = trim($first_name." ".$last_name).'/'.$docova_certifier_name;
			$unameCanon = 'CN='.trim($first_name." ".$last_name).",O=".$docova_certifier_name;
			$user->setUserNameDnAbbreviated($unameAbbr);
			$user->setUserNameDn($unameCanon);
			$user->addRoles($user_role);
			$em->persist($user);
			$profile = new UserProfile();
			
			$profile->setUser($user);
			$profile->setFirstName($first_name);
			$profile->setLastName($last_name);
			$profile->setDisplayName("$first_name $last_name");
			$profile->setAccountType(true);
			$profile->setMailServerURL('UNKNOWN');
			$profile->setUserMailSystem('O');
			$profile->setLanguage('en');
			$em->persist($profile);
			$em->flush();
			$user_role = $profile = $em = null;
			
			return $user;
		}
		
		return false;
	}

	/**
	 * Check if an object exists in an ArrayCollection; by default it's not restricted search.
	 * If the $check_details is TRUE then it check objects details with restriction
	 *
	 * @param \Doctrine\Common\Collections\ArrayCollection $target_array
	 * @param object $needle_object
	 * @param boolean $check_details
	 * @return boolean
	 */
	private function objectArrayContains($target_array, $needle_object, $check_details = false)
	{
		if (empty($target_array) || !$target_array->count())
		{
			return false;
		}
	
		foreach ($target_array as $element)
		{
			if ($check_details === false)
			{
				if ($element == $needle_object) {
					return true;
				}
			}
			else
			{
				if ($element == $needle_object && $element->getUsername() === $needle_object->getUsername())
				{
					return true;
				}
			}
			return false;
		}
	}

	/**
	 * Find a user info in Database, if does not exist a user object can be created according to its info through LDAP server
	 *  
	 * @param string $username
	 * @param boolean $create
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUserAndCreate($username, $create = true, $search_userid = false, $search_ad = true)
	{
		if (empty($this->user)) {
			$this->initialize();
		}
		$em = $this->getDoctrine()->getManager();
		$utilHelper= new UtilityHelperTeitr($this->global_settings,$this->container);
		return $utilHelper->findUserAndCreate($username, $create, $em, $search_userid, $search_ad);
	}
	
	/**
	 * Get the role(group) object base on role name
	 * 
	 * @param string $rolename
	 * @param boolean $by_groupname
	 * @return \Docova\DocovaBundle\Entity\UserRoles|boolean
	 */
	private function retrieveGroupByRole($rolename, $by_groupname = false)
	{
		$em = $this->getDoctrine()->getManager();
		if ($by_groupname === true) 
		{
			$type = false === strpos($rolename, '/DOCOVA') ? true : false;
			$name = str_replace('/DOCOVA', '', $rolename);
			$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $name, 'groupType' => $type));
		}
		else {
			$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $rolename));
		}
		if (!empty($group)) 
		{
			return $group;
		}
		
		return false;
	}
}