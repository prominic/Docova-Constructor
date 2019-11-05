<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Docova\DocovaBundle\Entity\SystemPerspectives;

//use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Entity\Folders;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
//use Docova\DocovaBundle\Entity\UserProfile;
use Docova\DocovaBundle\Entity\FacetMaps;
//use Docova\DocovaBundle\Extensions\ExternalViews;

class FolderController extends Controller
{
	protected $user;
	protected $global_settings;

	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
		
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
	}
	
	public function getFoldersDoeAction(Request $request)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		
		$libid = $request->query->get('LibraryId');
		$lazy_load = $request->query->get('LazyLoad');
		if (!empty($libid)) {
			$em = $this->getDoctrine()->getManager();
			$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $libid, 'Trash' => false));
			if (empty($library))
			{
				throw $this->createNotFoundException('Unspecified Library source ID = '.$libid);
			}
		
			$security_check = new Miscellaneous($this->container);
			if (false === $security_check->isLibrarySubscribed($library))
			{
				throw new AccessDeniedException();
			}
			unset($security_check);
				
			$this->initialize();
			$isAdmin = ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('MASTER', $library));
		
			if (!empty($lazy_load) && $lazy_load == 'true')
			{
				$folder_id = $request->query->get('FolderId');
				if (empty($folder_id))
				{
					$folders = $em->getRepository('DocovaBundle:Folders')->getSubfolders($libid, $this->user, null, $isAdmin);
				}
				else {
					$folders = $em->getRepository('DocovaBundle:Folders')->getSubfolders($libid, $this->user, $folder_id, $isAdmin);
				}
			}
			else {
				$folders = $em->getRepository('DocovaBundle:Folders')->getAllFolders($libid, $this->user, $isAdmin);
			}
			$em->clear();
		
			$foldersXML = new \DOMDocument('1.0', 'UTF-8');
			$root = $foldersXML->appendChild($foldersXML->createElement('viewentries'));
			if (!empty($folders) && is_array($folders))
			{
				$sortedFolders = $this->sortAlphabetically($folders);
				foreach ($sortedFolders AS $folder){
					//if (true === $security_check->isFolderVisible($folder, true, $library)) {
					if (!empty($folder)) {
						$entry = $foldersXML->createElement('viewentry');
						$attr = $foldersXML->createAttribute('position');
						$attr->value = $folder['Position'];
						$entry->appendChild($attr);
						$attr = $foldersXML->createAttribute('unid');
						$attr->value = $folder['id'];
						$entry->appendChild($attr);
						$newnode = $foldersXML->createElement('entrydata');
						$attr = $foldersXML->createAttribute('columnnumber');
						$attr->value = 0;
						$newnode->appendChild($attr);
						$attr = $foldersXML->createAttribute('name');
						$attr->value = 'FolderName';
						$newnode->appendChild($attr);
						$data = $foldersXML->createElement('text', htmlentities($folder['Folder_Name']));
						$newnode->appendChild($data);
						$entry->appendChild($newnode);
						$newnode = $foldersXML->createElement('entrydata');
						$attr = $foldersXML->createAttribute('columnnumber');
						$attr->value = 1;
						$newnode->appendChild($attr);
						$attr = $foldersXML->createAttribute('name');
						$attr->value = 'FolderID';
						$newnode->appendChild($attr);
						$data = $foldersXML->createElement('text', $folder['id']);
						$newnode->appendChild($data);
						$entry->appendChild($newnode);
						$newnode = $foldersXML->createElement('entrydata');
						$attr = $foldersXML->createAttribute('columnnumber');
						$attr->value = 2;
						$newnode->appendChild($attr);
						$attr = $foldersXML->createAttribute('name');
						$attr->value = 'FolderIcon';
						$newnode->appendChild($attr);
						$data = $foldersXML->createElement('text', $folder['Icon_Normal'].'-'.$folder['Icon_Selected']);
						$newnode->appendChild($data);
						$entry->appendChild($newnode);
						$root->appendChild($entry);
					}
					unset($folder);
				}
			}
				
			if (empty($lazy_load) || $lazy_load != 'true')
			{
				$entry = $foldersXML->createElement('viewentry');
				$attr = $foldersXML->createAttribute('position');
				$attr->value = 1;
				$entry->appendChild($attr);
				$attr = $foldersXML->createAttribute('unid');
				$attr->value = "RCBIN".$libid;
				$entry->appendChild($attr);
				$newnode = $foldersXML->createElement('entrydata');
				$attr = $foldersXML->createAttribute('columnnumber');
				$attr->value = 0;
				$newnode->appendChild($attr);
				$attr = $foldersXML->createAttribute('name');
				$attr->value = 'FolderName';
				$newnode->appendChild($attr);
				$data = $foldersXML->createElement('text', 'Recycle Bin');
				$newnode->appendChild($data);
				$entry->appendChild($newnode);
				$newnode = $foldersXML->createElement('entrydata');
				$attr = $foldersXML->createAttribute('columnnumber');
				$attr->value = 1;
				$newnode->appendChild($attr);
				$attr = $foldersXML->createAttribute('name');
				$attr->value = 'FolderID';
				$newnode->appendChild($attr);
				$data = $foldersXML->createElement('text', "RCBIN".$libid);
				$newnode->appendChild($data);
				$entry->appendChild($newnode);
				$newnode = $foldersXML->createElement('entrydata');
				$attr = $foldersXML->createAttribute('columnnumber');
				$attr->value = 2;
				$newnode->appendChild($attr);
				$attr = $foldersXML->createAttribute('name');
				$attr->value = 'FolderIcon';
				$newnode->appendChild($attr);
				$data = $foldersXML->createElement('text', 'RecycleFull-RecycleFull');
				$newnode->appendChild($data);
				$entry->appendChild($newnode);
				$root->appendChild($entry);
			}
		}
		else {
			throw $this->createNotFoundException('Library Id is missed');
		}
		
		$response->setContent($foldersXML->saveXML());
		return $response;
	}

	public function GetFoldersAction(Request $request)
	{
		$response = new Response();
		$libid = $request->query->get('LibraryId');
		if (!empty($libid)) {
			$em = $this->getDoctrine()->getManager();
			$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $libid, 'Trash' => false));
			if (empty($library))
			{
				throw $this->createNotFoundException('Unspecified Library source ID = '.$libid);
			}

			$security_check = new Miscellaneous($this->container);
			if (false === $security_check->isLibrarySubscribed($library))
			{
				throw new AccessDeniedException();
			}
			$security_check = null;
			$folder_id = null;
			$this->initialize();
			$lazyLoad = $request->query->get('LazyLoad') == 'true' ? true : false;
			$isAdmin = ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('MASTER', $library));
			if (!empty($lazyLoad))
			{
				$folder_id = $request->query->get('FolderId');
				if (empty($folder_id))
				{
					$folders = $em->getRepository('DocovaBundle:Folders')->getSubfolders($libid, $this->user, null, $isAdmin);
				}
				else {
					$folders = $em->getRepository('DocovaBundle:Folders')->getSubfolders($libid, $this->user, $folder_id, $isAdmin);
				}
			}
			else {
				$folders = $em->getRepository('DocovaBundle:Folders')->getAllFolders($libid, $this->user, $isAdmin);
			}
			$em->clear();
			
			if (empty($lazyLoad))
			{
				$output = array();
				if (!empty($folders) && is_array($folders)) 
				{
					$sortedFolders = $this->sortAlphabetically($folders);
					foreach ($sortedFolders as $folder) {
						if (!empty($folder['Icon_Normal'])) {
							$folder['Icon_Normal'] = $this->get('assets.packages')->getUrl('bundles/docova/images/').$folder['Icon_Normal'];
						}
						$output[] = array(
							'id' => $folder['id'],
							'parent' => !empty($folder['Parent_Id']) ? $folder['Parent_Id'] : '',
							'text' => $folder['Folder_Name'],
							'icon' => $folder['Icon_Normal'],
							'state' => array('opened' => false, 'disabled' => false, 'selected' => false),
							'li_attr' => array(),
							'a_attr' => array(),
							'data' => array(
								'FolderID' => $folder['id'],
								'FolderOpenUrl' => '',
								'sortorder' => '',
								'Unid' => $folder['id']
							)
						);
					}
				}
	
				$output[] = array(
					'id' => "RCBIN".$libid,
					'parent' => '',
					'text' => 'Recycle Bin',
					'icon' => 'docova-default docova-trash',
					'state' => array('opened' => false, 'disabled' => false, 'selected' => false),
					'li_attr' => array(),
					'a_attr' => array(),
					'data' => array(
						'FolderID' => "RCBIN".$libid,
						'FolderOpenUrl' => '',
						'sortorder' => '',
						'Unid' => "RCBIN".$libid
					)
				);
				$response->headers->set('Content-Type', 'application/json');
				$response->setContent(json_encode($output));
				return $response;
			}
			else {
				$foldersXML = '<?xml version="1.0" encoding="UTF-8" ?><viewentries>';
				if (!empty($folders) && is_array($folders))
				{
					$sortedFolders = $this->sortAlphabetically($folders, $folder_id);
					foreach ($sortedFolders AS $folder){
						if (!empty($folder)) {
							$foldersXML .= '<viewentry position="'.$folder['Position'].'" unid="'.$folder['id'].'">';
							$foldersXML .= '<entrydata columnnumber="0" name="FolderName"><text>'.htmlentities($folder['Folder_Name']).'</text></entrydata>';
							$foldersXML .= '<entrydata columnnumber="1" name="FolderID"><text>'.$folder['id'].'</text></entrydata>';
							$foldersXML .= '<entrydata columnnumber="2" name="FolderIcon"><text>'.$folder['Icon_Normal'].'-'.$folder['Icon_Selected'].'</text></entrydata>';
							$foldersXML .= '</viewentry>';
						}
						$folder = null;
					}
				}
				$foldersXML .= '</viewentries>';
			}
			$response->headers->set('Content-Type', 'text/xml');
			$response->setContent($foldersXML);
			return $response;
		}
		else {
			throw $this->createNotFoundException('Library Id is missed');
		}
	}
	
	public function getPartialFoldersAction(Request $request)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$folder = trim($request->query->get('RestrictToCategory')) ? trim($request->query->get('RestrictToCategory')) : null;
		$library = trim($request->query->get('RootLib')) ? trim($request->query->get('RootLib')) : null;
		if (empty($folder) && empty($library)) 
		{
			throw $this->createNotFoundException('Missed parent folder ID or library ID.');
		}
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		if (!empty($library)) 
		{
			$isAdmin = ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('MASTER', $library));
			$folders = $em->getRepository('DocovaBundle:Folders')->getSubfolders($library, $this->user, null, $isAdmin);
		}
		else {
			$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder, 'Del' => false, 'Inactive' => 0));
			$isAdmin = ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('MASTER', $folder->getLibrary()));
			if (!empty($folder)) 
			{
				$library = $folder->getLibrary()->getId();
				$folders = $em->getRepository('DocovaBundle:Folders')->getSubfolders($library, $this->user, $folder->getId(), $isAdmin);
				$library = $folder = null;
			}
		}
		$output = array();
		if (!empty($folders)) 
		{
			foreach ($folders as $folder)
			{
				$output[] = array(
					'id' => $folder['id'],
					'parent' => !empty($folder['Parent_Id']) ? $folder['Parent_Id'] : '',
					'text' => $folder['Folder_Name'],
					'icon' => $folder['Icon_Normal'].' '.$folder['Icon_Selected'],
					'state' => array('opened' => false, 'disabled' => false, 'selected' => false),
					'li_attr' => array(),
					'a_attr' => array(),
					'data' => array(
						'FolderID' => $folder['id'],
						'FolderOpenUrl' => '',
						'sortorder' => '',
						'Unid' => $folder['id']
					)
				);
			}
		}
		if (!empty($library)) 
		{
			$output[] = array(
				'id' => "RCBIN".$library,
				'parent' => '',
				'text' => 'Recycle Bin',
				'icon' => 'docova-default docova-trash',
				'state' => array('opened' => false, 'disabled' => false, 'selected' => false),
				'li_attr' => array(),
				'a_attr' => array(),
				'data' => array(
					'FolderID' => "RCBIN".$library,
					'FolderOpenUrl' => '',
					'sortorder' => '',
					'Unid' => "RCBIN".$library
				)
			);
		}
		$response->setContent(json_encode($output));
		return $response;
	}

	public function allByDocKeyAction(Request $request, $key)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$savedSearches = null;
		if (substr($key, 0, 5) == "RCBIN") 
		{
			$lib_id = $request->query->get('lib');
			if(empty($lib_id)){
				$lib_id = substr($key, 5);
			}
			$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $lib_id, 'Trash' => false));
			if (empty($library)) 
			{
				throw $this->createNotFoundException('Unspecified Library ID = '.$lib_id);
			}

			$savedSearches = $em->getRepository('DocovaBundle:SavedSearches')->getUserSavedSearchesInLibraries($this->user->getId(), array($lib_id));
			$perspective = $em->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Default_For' => 'Recycle Bin', 'Is_System' => true));
			$valid_perspectives = $em->getRepository('DocovaBundle:SystemPerspectives')->getValidPerspectivesFor('Recycle Bin');
			$folderDetails = null;
		}
		else {
			$folderDetails = $em->find('DocovaBundle:Folders', $key);
			
			if (empty($folderDetails))
			{
				throw $this->createNotFoundException('Unspecified source folder ID = '. $key);
			}
			
			$savedSearches = $em->getRepository('DocovaBundle:SavedSearches')->getUserSavedSearchesInLibraries($this->user->getId(), array($folderDetails->getLibrary()->getId()));

			if ($folderDetails->getDefaultPerspective() && (!$folderDetails->getLibrary()->getDefaultPerspective() || ($folderDetails->getLibrary()->getDefaultPerspective() && $folderDetails->getDefaultPerspective()->getPerspectiveName() != 'System Default'))) 
			{
				$perspective = $folderDetails->getDefaultPerspective();
			}
			elseif ($folderDetails->getLibrary()->getDefaultPerspective())
			{
				$perspective = $folderDetails->getLibrary()->getDefaultPerspective();
			}
			
			$valid_perspectives = $em->getRepository('DocovaBundle:SystemPerspectives')->getValidPerspectivesFor('Folder', $folderDetails);
			if (empty($valid_perspectives))
			{
				throw $this->createNotFoundException('No default perspective is defined in DB.');
			}
			$library = $folderDetails->getLibrary();
		}
	
		$date_modified = $perspective->getDateModified();
		$date_modified = (!empty($date_modified)) ? $date_modified->format('Y/m/d H:i:s') : '';
		$modifier = $perspective->getModifier();
		$modifier = (!empty($modifier)) ? $modifier->getUserNameDnAbbreviated() : '';
		$xml_perspective = '<viewperspective><type>';
		$xml_perspective .= ($perspective->getIsSystem() === true) ? 'system' : 'custom';
		$xml_perspective .= '</type><id>';
		$xml_perspective .= ($perspective->getIsSystem() === true) ? 'system_'.$perspective->getId() : 'custom_'.$perspective->getId();
		$xml_perspective .= '</id><Unid>'.$perspective->getId().'</Unid>';
		$xml_perspective .= '<name><![CDATA['.$perspective->getPerspectiveName().']]></name>';
		$xml_perspective .= '<description><![CDATA['.$perspective->getDescription().']]></description>';
		$xml_perspective .= '<createdby><![CDATA['.$perspective->getCreator()->getUserNameDnAbbreviated().']]></createdby>';
		$xml_perspective .= '<createddate>'.$perspective->getDateCreated()->format('m/d/Y H:i:s').'</createddate>';
		$xml_perspective .= '<modifiedby><![CDATA['.$modifier.']]></modifiedby>';
		$xml_perspective .= '<modifieddate>'.$date_modified.'</modifieddate>';
		if ($perspective->getIsSystem() !== true)
		{
			$xml_perspective .= '<autocollapse>'.(($perspective->getCollapseFirst() === true) ? '1' : '0').'</autocollapse>';
			$xml_perspective .= '<libscope>'.(($perspective->getAvailableForFolders() === true) ? 'L' : '').'</libscope>';
			$xml_perspective .= '<libdefault>'.((!empty($folderDetails) && $folderDetails->getLibrary()->getDefaultPerspective() && $folderDetails->getLibrary()->getDefaultPerspective()->getId() === $perspective->getId()) ? 'D' : '').'</libdefault>';
		}
		$xml_perspective .= $perspective->getXmlStructure();
		$xml_perspective .= '</viewperspective>';
		
		$security_check = new Miscellaneous($this->container);
		$access_levels = $security_check->getAccessLevel($folderDetails);
		$load_doc_id = $request->query->get('loaddoc');
		$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
		
		if (substr($key, 0, 5) != "RCBIN")
			$dataView = $em->getRepository("DocovaBundle:DataViews")->getDataView($key);
		return $this->render('DocovaBundle:Default:FolderDocuments.html.twig', array(
				'user' => $this->user,
				'folder' => $folderDetails,
				'library' => $library,
				'settings' => $this->global_settings,
				'user_access' => $access_levels,
				'load_doc_id' => $load_doc_id,
				'savedSearches' => $savedSearches,
				'date_format' => $defaultDateFormat,				
				'valid_perspectives' => $valid_perspectives,
				'system_perspective' => $xml_perspective,
				'dataView' => !empty($dataView) ? $dataView->getId() : ''
		));
	}

	public function getwFolderContentDoeAction(Request $request, $key)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$savedSearches = null;
		if (substr($key, 0, 5) == "RCBIN")
		{
			$lib_id = $request->query->get('lib');
			$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $lib_id, 'Trash' => false));
			if (empty($library))
			{
				throw $this->createNotFoundException('Unspecified Library ID = '.$lib_id);
			}
	
			$savedSearches = $em->getRepository('DocovaBundle:SavedSearches')->getUserSavedSearchesInLibraries($this->user->getId(), array($lib_id));
			$perspective = $em->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Default_For' => 'Recycle Bin', 'Is_System' => true));
			$valid_perspectives = $em->getRepository('DocovaBundle:SystemPerspectives')->getValidPerspectivesFor('Recycle Bin');
			$folderDetails = null;
		}
		else {
			$folderDetails = $em->find('DocovaBundle:Folders', $key);
				
			if (empty($folderDetails))
			{
				throw $this->createNotFoundException('Unspecified source folder ID = '. $key);
			}
				
			$savedSearches = $em->getRepository('DocovaBundle:SavedSearches')->getUserSavedSearchesInLibraries($this->user->getId(), array($folderDetails->getLibrary()->getId()));
	
			if ($folderDetails->getLibrary()->getDefaultPerspective())
			{
				$perspective = $folderDetails->getLibrary()->getDefaultPerspective();
			}
			else {
				$perspective = $folderDetails->getDefaultPerspective();
			}
				
			$valid_perspectives = $em->getRepository('DocovaBundle:SystemPerspectives')->getValidPerspectivesFor('Folder', $folderDetails);
			if (empty($valid_perspectives))
			{
				throw $this->createNotFoundException('No default perspective is defined in DB.');
			}
			$library = $folderDetails->getLibrary();
		}
	
		$date_modified = $perspective->getDateModified();
		$date_modified = (!empty($date_modified)) ? $date_modified->format('Y/m/d H:i:s') : '';
		$modifier = $perspective->getModifier();
		$modifier = (!empty($modifier)) ? $modifier->getUserNameDnAbbreviated() : '';
		$xml_perspective = '<viewperspective><type>';
		$xml_perspective .= ($perspective->getIsSystem() === true) ? 'system' : 'custom';
		$xml_perspective .= '</type><id>';
		$xml_perspective .= ($perspective->getIsSystem() === true) ? 'system_'.$perspective->getId() : 'custom_'.$perspective->getId();
		$xml_perspective .= '</id><Unid>'.$perspective->getId().'</Unid>';
		$xml_perspective .= '<name><![CDATA['.$perspective->getPerspectiveName().']]></name>';
		$xml_perspective .= '<description><![CDATA['.$perspective->getDescription().']]></description>';
		$xml_perspective .= '<createdby><![CDATA['.$perspective->getCreator()->getUserNameDnAbbreviated().']]></createdby>';
		$xml_perspective .= '<createddate>'.$perspective->getDateCreated()->format('m/d/Y H:i:s').'</createddate>';
		$xml_perspective .= '<modifiedby><![CDATA['.$modifier.']]></modifiedby>';
		$xml_perspective .= '<modifieddate>'.$date_modified.'</modifieddate>';
		if ($perspective->getIsSystem() !== true)
		{
			$xml_perspective .= '<autocollapse>'.(($perspective->getCollapseFirst() === true) ? '1' : '0').'</autocollapse>';
			$xml_perspective .= '<libscope>'.(($perspective->getAvailableForFolders() === true) ? 'L' : '').'</libscope>';
			$xml_perspective .= '<libdefault>'.((!empty($folderDetails) && $folderDetails->getLibrary()->getDefaultPerspective() && $folderDetails->getLibrary()->getDefaultPerspective()->getId() === $perspective->getId()) ? 'D' : '').'</libdefault>';
		}
		$xml_perspective .= $perspective->getXmlStructure();
		$xml_perspective .= '</viewperspective>';
	
		$security_check = new Miscellaneous($this->container);
		$access_levels = $security_check->getAccessLevel($folderDetails);
		$load_doc_id = $request->query->get('loaddoc');
		$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
		return $this->render('DocovaBundle:Default:wfolder2.html.twig', array(
				'user' => $this->user,
				'folder' => $folderDetails,
				'library' => $library,
				'user_access' => $access_levels,
				'load_doc_id' => $load_doc_id,
				'savedSearches' => $savedSearches,
				'date_format' => $defaultDateFormat,
				'valid_perspectives' => $valid_perspectives,
				'system_perspective' => $xml_perspective
		));
	}

	public function docsByFolderAction(Request $request)
	{
		$folder_id = $request->query->get('restricttocategory');
		$nodes = $request->query->get('nodes');
		$isMobile = trim($request->query->get('mobile'));
		$start = (!empty($isMobile)) ? 1 : (int)$request->query->get('start');
		$count = (!empty($isMobile)) ? 1000 : (int)$request->query->get('count');
		$nodes = (!empty($isMobile)) ? 'F8,F9,F1,F12,F4,F7' : $nodes;
		if (empty($nodes)) {
			throw $this->createNotFoundException('Undefined perspective xmlNodes are selected!');
		}
		$nodes = explode(',', $nodes);
		if (!in_array('statno', $nodes)) {
			$nodes[] = 'statno';
		}
		if (!in_array('verflag', $nodes)) {
			$nodes[] = 'verflag';
		}
		if (!in_array('versflag', $nodes)) {
			$nodes[] = 'versflag';
		}
		if (!in_array('flags', $nodes)) {
			$nodes[] = 'flags';
		}
//		$security_check = new Miscellaneous($this->container);
		if (!empty($folder_id)) 
		{
			$include_subfolders = $current_versions = $pending_release = false;
			if (false !== strpos($folder_id, 'ST')) 
			{
				$folder_id = str_replace('ST', '', $folder_id);
				$include_subfolders = true;
			}
			if (false !== strpos($folder_id, 'REL')) {
				$folder_id = str_replace('REL', '', $folder_id);
				$current_versions = true;
			}
			elseif (false !== strpos($folder_id, 'NEW')) {
				$folder_id = str_replace('NEW', '', $folder_id);
				$pending_release = true;
			}
			$this->initialize();
//			$folders = array($folder_id);
			$em = $this->getDoctrine()->getManager();

			$folder = $em->getRepository('DocovaBundle:Folders')->getOneFolderAndLibrary(array('id' => $folder_id, 'Del' => false, 'Inactive' => 0));
			$isAdmin = $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('MASTER', $folder->getLibrary()) ? true : false;
			$showDisplayName = $this->global_settings->getUserDisplayDefault() ? true : false;
			
			
			//*********External View Processing Extension**********
			//Process any matching external data extensions
			//if an extension is found return the results and do not continue
			//$extProcessed = ExternalViews::getData($this, $this->container->getParameter("document_root"), $folder_id, $start, $count, null);
			//if ($extProcessed!=null)
			//	return $extProcessed;
			//********************************************
			
			
			$documents = $em->getRepository('DocovaBundle:Documents')->getAllFolderDocuments($folder, $start, $count, $this->user, $isAdmin, $include_subfolders, $current_versions, $pending_release, $showDisplayName);
			$xml_doc = '<?xml version="1.0" encoding="UTF-8" ?><documents>';
			if (!empty($documents) && !empty($documents[0]))
			{
				$twig = $this->container->get('twig');
				$xml_fields = $em->getRepository('DocovaBundle:ViewColumns')->getValidColumns($folder->getLibrary(), $nodes);
				$len = count($documents);
				for ($x = 0; $x < $len; $x++)
				{
					$is_bookmark = false;
					if (!empty($documents[$x]['Target_Folder'])) {
						$is_bookmark = true;
					}
					$xml_doc .= "<document><dockey>DK{$documents[$x]['id']}</dockey><docid>{$documents[$x]['id']}</docid><rectype>doc</rectype><typekey>{$documents[$x]['id']}</typekey>";
					if (key_exists('attachments', $nodes)) 
					{
						$attachments = $em->getRepository('DocovaBundle:AttachmentsDetails')->getDocumentAttachmentsArray($documents[$x]['id']);
						if (!empty($attachments)) 
						{
							$alen = count($attachments);
							for ($c = 0; $c < $alen; $c++)
							{
								$date = !empty($attachments[$c]['File_Date']) ? $attachments[$c]['File_Date'] : null;
								$y = (!empty($date)) ? $date->format('Y') : $date;
								$m = (!empty($date)) ? $date->format('m') : $date;
								$d = (!empty($date)) ? $date->format('d') : $date;
								$w = (!empty($date)) ? $date->format('w') : $date;
								$h = (!empty($date)) ? $date->format('H') : $date;
								$mn = (!empty($date)) ? $date->format('i') : $date;
								$s = (!empty($date)) ? $date->format('s') : $date;
								$val = (!empty($date)) ? strtotime($date->format('Y-m-d H:i:s')) : $date;
								$od = (!empty($date)) ? $date->format('m/d/Y') : '';
								$xml_doc .= "<FILES Y='$y' M='$m' D='$d' W='$w' H='$h' MN='$mn' S='$s' val='$val' origDate='$od'>";
								$y = $m = $d = $w = $h = $mn = $s = $val = $od = null;
								
								$xml_doc .= "<src><![CDATA[{$this->get('router')->generate('docova_opendocfile', array('file_name' => $attachments[$c]['File_Name']))}?OpenElement&doc_id={$documents[$x]['id']}]]></src>";
								$xml_doc .= "<name><![CDATA[{$attachments[$c]['File_Name']}]]></name>";
								$xml_doc .= "<author><![CDATA[{$attachments[$c]['Author']['userNameDnAbbreviated']}]]></author></FILES>";
							}
							$attachments = $alen = $c = null;
						}
					}

					$output_nodes = array();
					foreach ($xml_fields as $column)
					{
						if (key_exists($column['Field_Name'], $documents[$x]))
						{
							$output_nodes[] = array(
								'value' => $documents[$x][$column['Field_Name']],
								'type' => $column['Data_Type'],
								'node' => $column['XML_Name']
							);
						}
						elseif ($column['Field_Name'] == 'Bookmarks') {
							$output_nodes[] = array(
								'value' => $is_bookmark ? $this->get('assets.packages')->getUrl('bundles/docova/images/').'icn10-docbookmark.png' : '',
								'type' => $column['Data_Type'],
								'node' => $column['XML_Name']
							);
						}
						elseif (strpos($column['Field_Name'], '{{') !== false || strpos($column['Field_Name'], '{%') !== false)
						{
							$template = $twig->createTemplate('{{ f_SetUser(user) }}{{ f_SetApplication(library) }}{{ f_SetDocument(document[\'id\'], library) }}{% docovascript "output:string" %} '.$column['Field_Name'].'{% enddocovascript %}');
							$value = $template->render(array(
								'user' => $this->user,
								'document' => $documents[$x],
								'library' => $folder->getLibrary()
							));
							$output_nodes[] = array(
								'value' => trim($value),
								'type' => $column['Data_Type'],
								'node' => $column['XML_Name']
							);
						}
					}
					
					if (!empty($output_nodes))
					{
						$olen = count($output_nodes);
						for ($c = 0; $c < $olen; $c++)
						{
							if ($output_nodes[$c]['type'] == 2) 
							{
								$value = explode(';', $output_nodes[$c]['value']);
								if (count($value) > 1) {
									$xml_doc .= "<{$output_nodes[$c]['node']}><![CDATA[{$output_nodes[$c]['value']}]]></{$output_nodes[$c]['node']}>";
								}
								else {
									$date = null;
									if (!empty($value[0]))
									{
										$date = new \DateTime();
										if (is_string($value[0])) {
											$value[0] = str_replace('/', '-', $value[0]);
											$value[0] = strtotime($value[0]);
											$date->setTimestamp($value[0]);
										}
										elseif ($value[0] instanceof \DateTime) {
											$date = $value[0];
										}
										else {
											$date = null;
										}
									}
									$date_value = !empty($date) ? $date->format('m/d/Y') : $date;
									$y = (!empty($date)) ? $date->format('Y') : $date;
									$m = (!empty($date)) ? $date->format('m') : $date;
									$d = (!empty($date)) ? $date->format('d') : $date;
									$w = (!empty($date)) ? $date->format('w') : $date;
									$h = (!empty($date)) ? $date->format('H') : $date;
									$mn = (!empty($date)) ? $date->format('i') : $date;
									$s = (!empty($date)) ? $date->format('s') : $date;
									$val = !empty($date) ? strtotime($date->format('Y/m/d H:i:s')) : '';
									$xml_doc .= "<{$output_nodes[$c]['node']} Y='$y' M='$m' D='$d' W='$w' H='$h' MN='$mn' S='$s' val='$val'><![CDATA[$date_value]]></{$output_nodes[$c]['node']}>";
									$y = $m = $d = $w = $h = $mn = $s = $val = $date_value = null;
								}
							}
							elseif ($output_nodes[$c]['type'] == 4) {
								if ($output_nodes[$c]['node'] == 'bmk') {
									if ($output_nodes[$c]['value']) {
										$xml_doc .= "<{$output_nodes[$c]['node']}><img width='10' height='10' src='{$output_nodes[$c]['value']}' alt='bookmark' ></img></{$output_nodes[$c]['node']}>";
									}
									else {
										$xml_doc .= "<{$output_nodes[$c]['node']}></{$output_nodes[$c]['node']}>";
									}
								}
								else {
									$xml_doc .= "<{$output_nodes[$c]['node']}>{$output_nodes[$c]['value']}</{$output_nodes[$c]['node']}>";
								}
							}
							else {
								$xml_doc .= "<{$output_nodes[$c]['node']}><![CDATA[{$output_nodes[$c]['value']}]]></{$output_nodes[$c]['node']}>";
							}
						}
					}
					
					$xml_doc .= '</document>';
				}
			}
			$xml_doc .= '</documents>';
		}
		else {
			throw $this->createNotFoundException('Folder Id is missed.');
		}
		
		$response = new Response($xml_doc);
		$response->headers->set('Content-Type', 'text/xml');
		$xml_doc = null;
		
		return $response;
	}
	
	public function getRecycleBinDataViewAction(Request $request)
	{
		$library_id = $request->query->get('LibraryID');
		if (empty($library_id)) 
		{
			throw $this->createNotFoundException('LibraryID for recycle bin is missed.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library_id, 'Trash' => false));
		if (empty($library)) 
		{
			throw $this->createNotFoundException('Unspecified source Library ID = '. $library_id);
		}
		
		$security_check = new Miscellaneous($this->container);
		if (false === $security_check->isLibrarySubscribed($library)) 
		{
			throw new AccessDeniedException();
		}
		
		$nodes = 'F30,F8,F9,F32,F31';
		$nodes = explode(',', $nodes);
		$columns = $em->getRepository('DocovaBundle:ViewColumns')->getValidColumns($library->getId(), $nodes);
		$documents = $em->getRepository('DocovaBundle:Documents')->getAllDeletedDocsInLibrary($library->getId());
		$folders = $em->getRepository('DocovaBundle:Folders')->getDeletedFolders($library->getId());
		$xml_result = '<?xml version="1.0" encoding="UTF-8" ?><documents>';
		$this->generateXMLViewData($nodes, $columns, $documents, $library_id, $xml_result);
		$this->generateXMLViewData($nodes, $columns, $folders, $library_id, $xml_result);
		$documents = $folders = $columns = null;
		$xml_result .= '</documents>';
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml_result);
		return $response;
	}
	
	public function getDocumentsByFolderCountAction(Request $request)
	{
		$count = 0;
		$folder = $request->query->get('restricttocategory');
		
		
		//*********External View Processing Extension**********
		//Process any matching external data extensions
		//If found then return the results and do not continue
/* TEMPORARLY COMMENTED - the ExternalViews class doesn't follow PHP 7 rules */
//		$extProcessed = ExternalViews::getCount($this, $this->container->getParameter("document_root"),$folder);
//		if ($extProcessed!=null)
//			return $extProcessed;
		//********************************************
		
		
		$em = $this->getDoctrine()->getManager();
		if (!empty($folder)) 
		{
			$include_subfolders = $current_versions = $pending_release = false;
			if (false !== strpos($folder, 'ST')) 
			{
				$folder = str_replace('ST', '', $folder);
				$include_subfolders = true;
			}
			if (false !== strpos($folder, 'REL')) {
				$folder = str_replace('REL', '', $folder);
				$current_versions = true;
			}
			elseif (false !== strpos($folder, 'NEW')) {
				$folder = str_replace('NEW', '', $folder);
				$pending_release = true;
			}
			$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder, 'Del' => false, 'Inactive' => 0));
			$isAdmin = $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('MASTER', $folder->getLibrary()) ? true : false;
			$this->initialize();
			$documents = $em->getRepository('DocovaBundle:Documents')->getAllFolderDocuments($folder, null, null, $this->user, $isAdmin, $include_subfolders, $current_versions, $pending_release);
			if (!empty($documents)) 
			{
				$count = $documents[0]['Amount'];
			}
		}
		
		$result = '<?xml version="1.0" encoding="UTF-8"?>
<Results><Result ID="Status">OK</Result><Result ID="Ret1">'.$count.'</Result></Results>';
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($result);
		return $response;
	}
	
	public function folderServicesAction(Request $request)
	{
		$postreq = $request->getContent();
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		if (!empty($postreq)) {
			$this->initialize();
			$granted = $valid_action = true;
			$security_context = $this->container->get('security.authorization_checker');
			$security_check = new Miscellaneous($this->container);
			$dom = new \DOMDocument("1.0", "UTF-8");
			$postreq = urldecode($postreq);
			$dom->loadXML($postreq);
			$action = $dom->getElementsByTagName('Action')->item(0)->nodeValue;
				
			if (strtoupper($action) == 'NEW') {
			    $tempnode = $dom->getElementsByTagName('FolderID');
				$folder_id	= (!empty($tempnode) && $tempnode->length ? $tempnode->item(0)->nodeValue : "");
				$tempnode = $dom->getElementsByTagName('DocKey');
				$doc_key	= (!empty($tempnode) && $tempnode->length ? $tempnode->item(0)->nodeValue : "");
				$tempnode = $dom->getElementsByTagName('LibraryId');
				$library_id	= (!empty($tempnode) && $tempnode->length ? $tempnode->item(0)->nodeValue : "");
				$tempnode = $dom->getElementsByTagName('Name');				
				$new_folder	= (!empty($tempnode) && $tempnode->length ? $tempnode->item(0)->nodeValue : "");
				
				if(!empty($new_folder)){
    				$em = $this->getDoctrine()->getManager();
				
	       			if(empty($folder_id) && empty($library_id)){
			     	    //-- if request is coming from DOE				    
				       $tempnode = $dom->getElementsByTagName('Unid');
				        $folder_id	= (!empty($tempnode) && $tempnode->length ? $tempnode->item(0)->nodeValue : "");
				        $doc_key = $folder_id;
				    
    				    $folder_obj		= $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id));
	       			    if(!empty($folder_obj)){
    		      		    $library_obj    = $folder_obj->getLibrary();
    				        if($library_obj->getTrash()){
    				            unset($library_obj);
    				            unset($folder_obj);
    				        }
				        }
    				}else if((!empty($library_id) && !empty($folder_id))){
	       			    $library_obj	= $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library_id, 'Trash' => false));
			     	    $folder_obj		= $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id, 'Library' => $library_obj));				    
				    }
				}
				
				if (!empty($library_obj) && !empty($new_folder)) {
					$active_user = $this->user;
					$entity = new Folders();
						
					if ($library_id === $folder_id && empty($doc_key)) {
						if ($security_context->isGranted('CREATE', $library_obj) === false) {
							$granted = false;
						}
					}
					elseif (empty($folder_obj)) {
						throw $this->createNotFoundException('No data is submitted.');
					}
					else {
						if (!$security_context->isGranted('ROLE_ADMIN') && $security_context->isGranted('VIEW', $folder_obj) === false && $security_check->isFolderManagers($folder_obj) === false && $security_context->isGranted('CREATE', $folder_obj) === false) {
							$granted = false;
						}
						else {
							$entity->setParentfolder($folder_obj);
						}
					}
						
					if ($granted === true) {
						if (!empty($folder_obj) && !$security_context->isGranted('ROLE_ADMIN') && !$security_context->isGranted('MASTER', $library_obj) && $folder_obj->getDisableACF() === true)
						{
							if ($security_context->isGranted('CREATE', $folder_obj) === true) {
								$granted = false;
								unset($entity);
							}
						}
					}
						
					if ($granted === true) {
						$entity->setLibrary($library_obj);
						$entity->setFolderName($new_folder);
						$entity->setDateCreated(new \DateTime());
						$entity->setCreator($active_user);
						$entity->setPosition($em);
						$entity->setDefaultDocType('-1');
						$perspective = $em->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'System Default', 'Is_System' => true));
						$entity->setDefaultPerspective($perspective);
	
						$em->persist($entity);
												
						if ($entity->getParentfolder()) 
						{
							$parent_folder = $entity->getParentfolder();
							$entity->setDefaultDocType($parent_folder->getDefaultDocType());
							if ($parent_folder->getApplicableDocType()->count() > 0) 
							{
								foreach ($parent_folder->getApplicableDocType() as $dt) {
									$entity->addApplicableDocType($dt);
								}
							}
							unset($dt);
						}
						$em->flush();
							
						$customeACL_obj = new CustomACL($this->container);
						$customeACL_obj->insertObjectAce($entity, 'ROLE_USER', array('view', 'delete'));
						
						if (!empty($folder_obj)) 
						{
							$parent_authors = $customeACL_obj->getObjectACEUsers($folder_obj, 'create');
							$parent_gauthors = $customeACL_obj->getObjectACEGroups($folder_obj, 'create');
							$parent_editors = $customeACL_obj->getObjectACEUsers($folder_obj, 'master');
							$parent_geditors = $customeACL_obj->getObjectACEGroups($folder_obj, 'master');
							$parent_readers = $customeACL_obj->getObjectACEUsers($folder_obj, 'view');
							$parent_greaders = $customeACL_obj->getObjectACEGroups($folder_obj, 'view');
						}
						if ((empty($parent_authors) || $parent_authors->count() == 0) && (empty($parent_gauthors) || $parent_gauthors->count() == 0)) 
						{
							$customeACL_obj->insertObjectAce($entity, 'ROLE_USER', 'create');
						}
						else {
							if (!empty($parent_authors) && $parent_authors->count() > 0) 
							{
								foreach ($parent_authors as $pauthor) {
									if (false !== $user_obj = $this->findUserAndCreate($pauthor->getUsername(), false, true)) 
									{
										$customeACL_obj->insertObjectAce($entity, $user_obj, 'create', false);
									}
								}
							}
							if (!empty($parent_gauthors) && $parent_gauthors->count() > 0) 
							{
								foreach ($parent_gauthors as $group) {
									$customeACL_obj->insertObjectAce($entity, $group->getRole(), 'create');
								}
							}
						}
						if (!empty($parent_editors) && $parent_editors->count()) 
						{
							foreach ($parent_editors as $peditor) {
								if (false !== $user_obj = $this->findUserAndCreate($peditor->getUsername(), false, true)) 
								{
									$customeACL_obj->insertObjectAce($entity, $user_obj, 'master', false);
								}
							}
						}
						if (!empty($parent_geditors) && $parent_geditors->count()) 
						{
							foreach ($parent_geditors as $group) {
								$customeACL_obj->insertObjectAce($entity, $group->getRole(), 'master');
							}
						}
						if ((!empty($parent_readers) && $parent_readers->count()) || (!empty($parent_greaders) && !empty($parent_greaders)))
						{
							$customeACL_obj->removeUserACE($entity, 'ROLE_USER', 'view', true);
							if (!empty($parent_readers) && $parent_readers->count())
							{
								foreach ($parent_readers as $preader) {
									if (false !== $user_obj = $this->findUserAndCreate($preader->getUsername(), false, true))
									{
										$customeACL_obj->insertObjectAce($entity, $user_obj, 'view', false);
									}
								}
							}
							
							if (!empty($parent_greaders) && $parent_greaders->count())
							{
								foreach ($parent_greaders as $group) {
									$customeACL_obj->insertObjectAce($entity, $group->getRole(), 'view');
								}
							}
						}
						$group = $user_obj = $parent_authors = $parent_editors = $parent_gauthors = $parent_geditors = $parent_readers = $parent_greaders = null;
						//@TODO: Create a log for not inserted ACL for the object
						
						$customeACL_obj->insertObjectAce($entity, $this->user, 'owner', false);
						//@TODO: Create a log for not inserted ACL for the object
/*
						$parent_folder = $entity->getParentfolder();
						if (!empty($parent_folder)) {
							$owners = $customeACL_obj->getObjectACEUsers($parent_folder, 'owner');
							
							if ($owners->count() > 0) {
								for ($x = 0; $x < $owners->count(); $x++) {
									if (false !== $owner_obj = $this->findUserAndCreate($owners[$x]->getUsername())) {
										if ($customeACL_obj->isUserGranted($entity, $owner_obj, 'owner') === false) {
											$customeACL_obj->insertObjectAce($entity, $owner_obj, 'owner', false);
										}
									}
								}
							}
							
//							$parent_folder = $parent_folder->getParentfolder();
						}
*/							
						$security_check->createFolderLog($em, 'CREATE', $entity);
					}
				}
				else {
					throw $this->createNotFoundException('No data is submitted.');
				}
			}
			elseif ($action == 'DELETE') {
				$folder_id	= $dom->getElementsByTagName('FolderID')->item(0)->nodeValue;
				$em = $this->getDoctrine()->getManager();
				$entity = $em->getRepository('DocovaBundle:Folders')->find($folder_id);
				$granted = $security_check->isFolderManagers($entity);
				$granted = ($granted !== true) ? $security_context->isGranted('MASTER', $entity) : $granted;

				if ($granted === true) {
					$entity->setDel(true);
					$entity->setDateDeleted(new \DateTime());
					$entity->setDeletedBy($this->user);
					$em->getRepository('DocovaBundle:Folders')->setChildrenInactive($entity->getPosition(), $entity->getLibrary()->getId());
					$em->flush();

					$security_check->createFolderLog($em, 'DELETE', $entity);
				}
			}
			elseif ($action == 'RENAME') {
				$folder_id	= $dom->getElementsByTagName('FolderID')->item(0)->nodeValue;
				$folder_name = $dom->getElementsByTagName('Name')->item(0)->nodeValue;
				$em = $this->getDoctrine()->getManager();
				$entity = $em->getRepository('DocovaBundle:Folders')->find($folder_id);
				$previous_name = $entity->getFolderName();
				
				if (empty($folder_name))
				{
					throw $this->createNotFoundException('Folder name cannot be empty.');
				}

				$granted = $security_context->isGranted('ROLE_ADMIN') ? true : $security_check->isFolderManagers($entity);
				$granted = ($granted !== true) ? $security_context->isGranted('MASTER', $entity) : $granted;
/*
				if ($security_context->isGranted('OWNER', $entity) === false) {
					
					$granted = false;
					$parent_folder = $entity->getParentfolder();
					
					while ($parent_folder) {
						if ($security_context->isGranted('OWNER', $parent_folder) === true) {
							$granted = true;
							break;
						}
						$parent_folder = $parent_folder->getParentfolder();
					}
				}
*/				
				if ($granted === true) {
					$entity->setFolderName($folder_name);
					$em->flush();
					
					$security_check->createFolderLog($em, 'UPDATE', $entity, "Renamed folder from: $previous_name to: $folder_name");
				}
			}
			elseif ($action == 'GETMETADATA') {
				$folder_id = $dom->getElementsByTagName('Unid')->item(0)->nodeValue;

				if (empty($folder_id)) {
					throw $this->createNotFoundException('Folder ID is missed.');
				}
				
				$em = $this->getDoctrine()->getManager();
				$folder_obj = $em->getRepository('DocovaBundle:Folders')->find($folder_id);
				
				if (empty($folder_obj)) {
					throw $this->createNotFoundException('Could not find any folder for ID = '. $folder_id);
				}
			}
			elseif ($action == 'GETSUBFOLDERS') {
				$folder = null;
				$library = $dom->getElementsByTagName('LibraryID')->item(0)->nodeValue;;
				if (!empty($dom->getElementsByTagName('FolderID')->item(0)->nodeValue)) 
				{
					$folder = $dom->getElementsByTagName('FolderID')->item(0)->nodeValue;
				}
				
				$em = $this->getDoctrine()->getManager();
				$lib_obj = $em->getReference('DocovaBundle:Libraries', $library);
				$isAdmin = $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('MASTER', $lib_obj);
				$lib_obj = null;
				$folders = $em->getRepository('DocovaBundle:Folders')->getSubfolders($library, $this->user, $folder, $isAdmin);
				$xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $xml->appendChild($xml->createElement('Results'));
				$child = $xml->createElement('Result', 'OK');
				$attr = $xml->createAttribute('ID');
				$attr->value = 'Status';
				$child->appendChild($attr);
				$root->appendChild($child);
				$child = $xml->createElement('Result');
				$attr = $xml->createAttribute('ID');
				$attr->value = 'Ret1';
				$child->appendChild($attr);
				$node = $xml->createElement('Folders');
				if (!empty($folders) && count($folders) > 0) 
				{
					foreach ($folders as $folder) 
					{
						$fnode = $xml->createElement('Folder');
						$newnode = $xml->createElement('FolderID', $folder['id']);
						$fnode->appendChild($newnode);
						$newnode = $xml->createElement('FolderName');
						$cdata = $xml->createCDATASection($folder['Folder_Name']);
						$newnode->appendChild($cdata);
						$fnode->appendChild($newnode);
						$node->appendChild($fnode);
					}
				}
				$child->appendChild($node);
				$root->appendChild($child);
				
				$response->setContent($xml->saveXML());
				return $response;
			}
			elseif ($action == 'UPDATE') {
				$folder_id		= $dom->getElementsByTagName('Unid')->item(0)->nodeValue;
				$added_owners	= $dom->getElementsByTagName('AddedManagers')->item(0)->nodeValue;
				$deleted_owners	= $dom->getElementsByTagName('DeletedManagers')->item(0)->nodeValue;
				$added_editors	= $dom->getElementsByTagName('AddedEditors')->item(0)->nodeValue;
				$deleted_editors= $dom->getElementsByTagName('DeletedEditors')->item(0)->nodeValue;
				$added_authors	= $dom->getElementsByTagName('AddedAuthors')->item(0)->nodeValue;
				$deleted_authors= $dom->getElementsByTagName('DeletedAuthors')->item(0)->nodeValue;
				$added_readers	= $dom->getElementsByTagName('AddedReaders')->item(0)->nodeValue;
				$deleted_readers= $dom->getElementsByTagName('DeletedReaders')->item(0)->nodeValue;
				$synced_with	= $dom->getElementsByTagName('SyncUsers')->item(0)->nodeValue;
				$delete_rights	= trim($dom->getElementsByTagName('DeleteRights')->item(0)->nodeValue);
				$disable_acf	= (!empty($dom->getElementsByTagName('AuthorsCanNotCreateFolders')->item(0)->nodeValue)) ? true : false;
				$enable_acr		= (!empty($dom->getElementsByTagName('AllowUnrestrictedRevisions')->item(0)->nodeValue)) ? true : false;
				$private_draft	= (!empty($dom->getElementsByTagName('KeepDraftsPrivate')->item(0)->nodeValue)) ? true : false;
				$set_aae		= (!empty($dom->getElementsByTagName('AuthorsCanEditDrafts')->item(0)->nodeValue)) ? true : false;
				$restrict_rta	= (!empty($dom->getElementsByTagName('OnlyAuthorsAreReaders')->item(0)->nodeValue)) ? true : false;
				$set_dva		= (!empty($dom->getElementsByTagName('ReadersSeeDrafts')->item(0)->nodeValue)) ? true : false;
				$disable_ccp	= (!empty($dom->getElementsByTagName('DisableCutCopyPaste')->item(0)->nodeValue)) ? true : false;
				$disable_tcb	= (!empty($dom->getElementsByTagName('DisableBookmarks')->item(0)->nodeValue)) ? true : false;
				$enable_filtring= (!empty($dom->getElementsByTagName('EnableFolderFiltering')->item(0)->nodeValue)) ? true : false;
				$synced			= (!empty($dom->getElementsByTagName('Sync')->item(0)->nodeValue)) ? true : false;
				$sync_subfolder = (!empty($dom->getElementsByTagName('Sync')->item(0)->nodeValue) && !empty($dom->getElementsByTagName('SyncSubfolders')->item(0)->nodeValue)) ? true : false;
				$em = $this->getDoctrine()->getManager();
				
				if (empty($folder_id)) {
					throw $this->createNotFoundException('Folder ID for UPDATE action is missed.');
				}
				
				$folder_obj = $em->find('DocovaBundle:Folders', $folder_id);
				if (empty($folder_obj)) {
					throw $this->createNotFoundException('No Folder is found for ID = '. $folder_id);
				}

				$granted = $security_check->isFolderManagers($folder_obj);
				if ($granted === false && ($security_context->isGranted('ROLE_ADMIN') || $security_context->isGranted('MASTER', $folder_obj->getLibrary()))) {
					$granted = true;
				}
				
				if ($granted === true) {
					$folder_doctypes = $folder_obj->getApplicableDocType();
					if ($folder_doctypes->count() > 0) {
						foreach ($folder_doctypes as $doctype) {
							$folder_obj->removeApplicableDocType($doctype);
						}
						$em->flush();
					}
					unset($folder_doctypes);
					
					if ($folder_obj->getSynchUsers()->count() > 0) 
					{
						$folder_obj->removeAllSynchUsers();
						$em->flush();
					}

					$customeACL_obj = new CustomACL($this->container);

					if (!empty($added_owners)) {
						$owners = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $added_owners);
						
						for ($x = 0; $x < count($owners); $x++) {
							if (!empty($owners[$x])) {
								
								if (false !== $user_obj = $this->findUserAndCreate($owners[$x])) {
									
									if (false === $customeACL_obj->isUserGranted($folder_obj, $user_obj, 'owner')) {
										$customeACL_obj->insertObjectAce($folder_obj, $user_obj, 'owner', false);
									}
								}
								elseif (false !== $group = $this->fetchGroup($owners[$x])) {
									$customeACL_obj->insertObjectAce($folder_obj, $group->getRole(), 'owner');
								}
							}
						}
						$user_obj = $group = $owners = $x = null;
					}

					if (!empty($added_editors)) {
						$editors = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $added_editors);
					
						for ($x = 0; $x < count($editors); $x++) {
							if (!empty($editors[$x])) {
					
								if (false !== $user_obj = $this->findUserAndCreate($editors[$x])) {
										
									if (false === $customeACL_obj->isUserGranted($folder_obj, $user_obj, 'master')) {
										$customeACL_obj->insertObjectAce($folder_obj, $user_obj, 'master', false);
									}
								}
								elseif (false !== $group = $this->fetchGroup($editors[$x])) {
									$customeACL_obj->insertObjectAce($folder_obj, $group->getRole(), 'master');
								}
							}
						}
						$user_obj = $group = $editors = $x = null;
					}

					if (!empty($deleted_owners)) {
						$owners = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $deleted_owners);
	
						for ($x = 0; $x < count($owners); $x++) {
	
							if (!empty($owners[$x])) {
	
								if (false !== $user_obj = $this->findUserAndCreate($owners[$x])) {
									if (true === $customeACL_obj->isUserGranted($folder_obj, $user_obj, 'owner')) {
										$customeACL_obj->removeUserACE($folder_obj, $user_obj, 'owner');
									}
								}
								elseif (false !== $group = $this->fetchGroup($owners[$x])) {
									$customeACL_obj->removeUserACE($folder_obj, $group->getRole(), 'owner', true);
								}
							}
						}
						$user_obj = $group = $owners = $x = null;
					}

					if (!empty($deleted_editors)) {
						$editors = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $deleted_editors);
					
						for ($x = 0; $x < count($editors); $x++) {
					
							if (!empty($editors[$x])) {
					
								if (false !== $user_obj = $this->findUserAndCreate($editors[$x])) {
									if (true === $customeACL_obj->isUserGranted($folder_obj, $user_obj, 'master')) {
										$customeACL_obj->removeUserACE($folder_obj, $user_obj, 'master');
									}
								}
								elseif (false !== $group = $this->fetchGroup($editors[$x])) {
									$customeACL_obj->removeUserACE($folder_obj, $group->getRole(), 'master', true);
								}
							}
						}
						$user_obj = $group = $editors = $x = null;
					}

					if (!empty($added_authors)) {
						$authors = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $added_authors);
						$added = false;
						
						for ($x = 0; $x < count($authors); $x++) {
	
							if (!empty($authors[$x])) {
	
								if (false !== $user_obj = $this->findUserAndCreate($authors[$x])) {
									$customeACL_obj->insertObjectAce($folder_obj, $user_obj, 'create', false);
									$added = true;
								}
								elseif (false !== $group = $this->fetchGroup($authors[$x])) {
									$customeACL_obj->insertObjectAce($folder_obj, $group->getRole(), 'create');
									$added = true;
								}
							}
						}
						if ($added === true) {
							$customeACL_obj->removeUserACE($folder_obj, 'ROLE_USER', 'create', true);
						}
					}
					
					if (!empty($deleted_authors)) {
						$authors = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $deleted_authors);
						$readers = $customeACL_obj->getObjectACEUsers($folder_obj, 'view');
						$greaders = $customeACL_obj->getObjectACEGroups($folder_obj, 'view');

						for ($x = 0; $x < count($authors); $x++) {
	
							if (!empty($authors[$x])) {
	
								if (false !== $user_obj = $this->findUserAndCreate($authors[$x])) {
									$customeACL_obj->removeUserACE($folder_obj, $user_obj, 'create');
								}
								elseif (false !== $group = $this->fetchGroup($authors[$x])) {
									$customeACL_obj->removeUserACE($folder_obj, $group->getRole(), 'create', true);
								}
								else {
									unset($authors[$x]);
								}
							}
						}
						
						$creators = $customeACL_obj->getObjectACEUsers($folder_obj, 'create');
						if (empty($added_authors) && (($readers->count() == 0 && $greaders->count() == 0) || ($readers->count() + $greaders->count()) == count(preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $deleted_readers))))
						{
							if ($creators->count() < 1) {
								$customeACL_obj->insertObjectAce($folder_obj, 'ROLE_USER', 'create');
							}
						}
					}
					
					if ($added_readers == 'Only Authors above')
					{
						$added_readers = '';
						$creators = $customeACL_obj->getObjectACEUsers($folder_obj, 'create');
						if ($creators->count() > 0)
						{
							foreach ($creators as $author) 
							{
								if (false !== $user_obj = $this->findUserAndCreate($author->getUsername(), false, true)) {
									$added_readers .= $user_obj->getUserNameDnAbbreviated().',';
								}
							}
							
							$added_readers = substr_replace($added_readers, '', -1);
						}
						
						$creators = $customeACL_obj->getObjectACEGroups($folder_obj, 'create');
						if ($creators->count() > 0) 
						{
							foreach ($creators as $author)
							{
								$added_readers .= $author->getRole().'[ROLENAME],';
							}
							$added_readers = substr_replace($added_readers, '', -1);
						}
					}

					$children = $em->getRepository('DocovaBundle:Folders')->getDescendants($folder_obj->getPosition(), $folder_obj->getLibrary()->getId(), $folder_obj->getId(), true);
					if (!empty($added_readers)) {
						$readers = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $added_readers);
						//$added = false;
							
						for ($x = 0; $x < count($readers); $x++) 
						{
							if (!empty($readers[$x])) 
							{
								if (false !== strpos($readers[$x], '[ROLENAME]'))
								{
									$readers[$x] = str_replace('[ROLENAME]', '', $readers[$x]);
								}
								elseif (false !== $user_obj = $this->findUserAndCreate($readers[$x])) {
									$readers[$x] = 'Docova\DocovaBundle\Entity\UserAccounts-' . $user_obj->getUsername();
									//$customeACL_obj->insertObjectAce($folder_obj, $user_obj, 'view', false);
									//$added = true;
								}
								elseif (false !== $group = $this->fetchGroup($readers[$x])) {
									$readers[$x] = $group->getRole();
								}
								else {
									unset($readers[$x]);
								}
							}
						}
						
						if (!empty($children) && count($children) > 0 ) 
						{
							$ancestors = $em->getRepository('DocovaBundle:Folders')->getAncestors($folder_obj->getPosition(), $folder_obj->getLibrary()->getId(), $folder_obj->getId(), true);
							$this->addReadersToChildren($children, $readers, $folder_obj->getFolderName(), $folder_obj->getId(), $ancestors);
						}
					}
					
					if ($deleted_readers == 'Only Authors above')
					{
						$deleted_readers = '';
						$creators = $customeACL_obj->getObjectACEUsers($folder_obj, 'create');
						if ($creators->count() > 0)
						{
							foreach ($creators as $author) 
							{
								if (false !== $user_obj = $this->findUserAndCreate($author->getUsername(), false, true)) {
									$deleted_readers .= $user_obj->getUserNameDnAbbreviated().',';
								}
							}
							$deleted_readers = substr_replace($deleted_readers, '', -1);
						}
						$creators = $customeACL_obj->getObjectACEGroups($folder_obj, 'create');
						if ($creators->count() > 0) 
						{
							foreach ($creators as $author) {
								$deleted_readers .= $author->getRole().'[ROLENAME],';
							}
							$deleted_readers = substr_replace($deleted_readers, '', -1);
						}
					}

					if (!empty($deleted_readers)) {
						$readers = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $deleted_readers);
	
						for ($x = 0; $x < count($readers); $x++) {
	
							if (!empty($readers[$x])) {
								if (false !== strpos($readers[$x], '[ROLENAME]')) {
									$customeACL_obj->removeUserACE($folder_obj, str_replace('[ROLENAME]', '', $readers[$x]), 'view', true);
								}
								elseif (false !== $user_obj = $this->findUserAndCreate($readers[$x])) {
									$customeACL_obj->removeUserACE($folder_obj, $user_obj, 'view');
								}
								elseif (false !== $group = $this->fetchGroup($readers[$x])) {
									$customeACL_obj->removeUserACE($folder_obj, $group->getRole(), 'view', true);
								}
							}
						}
						
						$viewers = $customeACL_obj->getObjectACEUsers($folder_obj, 'view');
						$gviewers = $customeACL_obj->getObjectACEGroups($folder_obj, 'view');
						if ($viewers->count() < 1 && $gviewers->count() < 1) {
							$customeACL_obj->insertObjectAce($folder_obj, 'ROLE_USER', 'view');
							
							$folder_authors = $customeACL_obj->getObjectACEUsers($folder_obj, 'create');
							$folder_gauthors = $customeACL_obj->getObjectACEGroups($folder_obj, 'create');
							if ($folder_authors->count() < 1 && $folder_gauthors->count() < 1 && false === $customeACL_obj->isRoleGranted($folder_obj, 'ROLE_USER', 'create')) {
								$customeACL_obj->insertObjectAce($folder_obj, 'ROLE_USER', 'create');
							}
							unset($folder_authors, $folder_gauthors);
						}
					}
					
					if ($synced) 
					{
						if (!empty($synced_with)) 
						{
							$synced_with = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $synced_with);
							foreach ($synced_with as $index => $username) 
							{
								if (false !== $user_obj = $this->findUserAndCreate($username)) {
									$synced_with[$index] = $user_obj->getId();
								}
								else {
									unset($synced_with[$index]);
								}
							}
						}

						$folder_obj->setSynched($synced);
						$folder_obj->setSyncSubfolders($sync_subfolder);
						$folder_obj->setSyncedFromParent(false);

						if (!empty($children) && count($children) > 0) 
						{
							$this->syncSubfolders($children, $synced_with, $folder_obj->getId(), $sync_subfolder);
						}
						if (empty($sync_subfolder) && !empty($children) && count($children) > 0) {
							$this->removeSubfoldersSyncStatus($children, $folder_obj->getId());
						}
					}
					else {
						$folder_obj->setSynched(false);
						$folder_obj->setSyncSubfolders(false);
						$folder_obj->setSyncedFromParent(false);
						if (!empty($children) && count($children) > 0) 
						{
							$this->removeSubfoldersSyncStatus($children, $folder_obj->getId());
						}
					}
					
					if (!empty($delete_rights)) 
					{
						$delete = false;
						$delete_rights = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $delete_rights);
						$customeACL_obj->removeMaskACEs($folder_obj, 'delete');
						for ($x = 0; $x < count($delete_rights); $x++) {
							if (!empty($delete_rights[$x])) {
								if (false !== $user_obj = $this->findUserAndCreate($delete_rights[$x])) {
									$customeACL_obj->insertObjectAce($folder_obj, $user_obj, 'delete', false);
									$delete = true;
								}
								elseif (false !== $group = $this->fetchGroup($delete_rights[$x])) {
									$customeACL_obj->insertObjectAce($folder_obj, $group->getRole(), 'delete');
									$delete = true;
								}
							}
						}
						if ($delete === true) 
						{
							$customeACL_obj->removeUserACE($folder_obj, 'ROLE_USER', 'delete', true);
						}
					}
					else {
						if (false === $customeACL_obj->isRoleGranted($folder_obj, 'ROLE_USER', 'delete')) {
							$customeACL_obj->removeMaskACEs($folder_obj, 'delete');
							$customeACL_obj->insertObjectAce($folder_obj, 'ROLE_USER', 'delete');
						}
					}
					
					$fltr_field1 = $fltr_field2 = $fltr_field3 = $fltr_field4 = $propagate_fields = null;
					if (!empty($dom->getElementsByTagName('fltrField1')->item(0)->nodeValue)) {
						$fltr_field1 = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $dom->getElementsByTagName('fltrField1')->item(0)->nodeValue));
						$fltr_field1 = (!empty($fltr_field1)) ? $fltr_field1 : null;
					}
					if (!empty($dom->getElementsByTagName('fltrField2')->item(0)->nodeValue)) {
						$fltr_field2 = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $dom->getElementsByTagName('fltrField2')->item(0)->nodeValue));
						$fltr_field2 = (!empty($fltr_field2)) ? $fltr_field2 : null;
					}
					if (!empty($dom->getElementsByTagName('fltrField3')->item(0)->nodeValue)) {
						$fltr_field3 = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $dom->getElementsByTagName('fltrField3')->item(0)->nodeValue));
						$fltr_field3 = (!empty($fltr_field3)) ? $fltr_field3 : null;
					}
					if (!empty($dom->getElementsByTagName('fltrField4')->item(0)->nodeValue)) {
						$fltr_field4 = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $dom->getElementsByTagName('fltrField4')->item(0)->nodeValue));
						$fltr_field4 = (!empty($fltr_field4)) ? $fltr_field4 : null;
					}
					if (!empty($dom->getElementsByTagName('PropagateFields')->item(0)->nodeValue)) {
						$propagate_fields = explode(';', $dom->getElementsByTagName('PropagateFields')->item(0)->nodeValue);
					}

					$folder_obj->setDisableACF($disable_acf);
					$folder_obj->setEnableACR($enable_acr);
					$folder_obj->setPrivateDraft($private_draft);
					$folder_obj->setSetAAE($set_aae);
					$folder_obj->setRestrictRTA($restrict_rta);
					$folder_obj->setSetDVA($set_dva);
					$folder_obj->setDisableCCP($disable_ccp);
					$folder_obj->setDisableTCB($disable_tcb);
					$folder_obj->setFiltering($enable_filtring);
					$folder_obj->setFltrField1($fltr_field1);
					$folder_obj->setFltrField2($fltr_field2);
					$folder_obj->setFltrField3($fltr_field3);
					$folder_obj->setFltrField4($fltr_field4);
					$folder_obj->setFolderName($dom->getElementsByTagName('FolderName')->item(0)->nodeValue);
					$folder_obj->setDescription($dom->getElementsByTagName('Description')->item(0)->nodeValue);
					$folder_obj->setIconNormal($dom->getElementsByTagName('IconNormal')->item(0)->nodeValue);
					$folder_obj->setIconSelected($dom->getElementsByTagName('IconSelected')->item(0)->nodeValue);
					
					if (trim($dom->getElementsByTagName('DefaultDocumentType')->item(0)->nodeValue) == 'None' || trim($dom->getElementsByTagName('DocumentTypeOption')->item(0)->nodeValue) == 'N') {
						$folder_obj->setDefaultDocType((trim($dom->getElementsByTagName('DocumentTypeOption')->item(0)->nodeValue) == 'N') ? null : '-1');
					}
					else {
						$folder_obj->setDefaultDocType($dom->getElementsByTagName('DefaultDocumentType')->item(0)->nodeValue);
					}
					
					if ($dom->getElementsByTagName('UseContentPaging')->item(0)->nodeValue) {
						$folder_obj->setPagingCount(($dom->getElementsByTagName('MaxDocCount')->item(0)->nodeValue) ? $dom->getElementsByTagName('MaxDocCount')->item(0)->nodeValue : 1);
					}
					else {
						$folder_obj->setPagingCount();
					}

					$applicable_doctypes = $dom->getElementsByTagName('DocumentType')->item(0)->nodeValue;
					if (!empty($applicable_doctypes)) {
						$applicable_doctypes = explode(',', $applicable_doctypes);
						$library_doctypes = $folder_obj->getLibrary()->getApplicableDocType();
						
						for ($x = 0; $x < count($applicable_doctypes); $x++) {
							$doctype = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => trim($applicable_doctypes[$x]), 'Trash' => false));
							if ($library_doctypes->count() > 0) {
								if (true === $library_doctypes->contains($doctype)) {
									$folder_obj->addApplicableDocType($doctype);
									//$em->persist($folder_obj);
								}
							}
							else {
								$folder_obj->addApplicableDocType($doctype);
								//$em->persist($folder_obj);
							}
						}
						unset($library_doctypes);
					}
					unset($applicable_doctypes);
					
					$default_perspective = $dom->getElementsByTagName('DefaultPerspective')->item(0)->nodeValue;
					$default_perspective = ($default_perspective == 'system_default_folder') ? 'System Default' : str_replace('system_', '', $default_perspective);
					$default_perspective = (preg_match('~[0-9]+~', $default_perspective) && false !== strpos($default_perspective, '-')) ? $em->find('DocovaBundle:SystemPerspectives', $default_perspective) : $em->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => $default_perspective));
					if (!empty($default_perspective)) {
						$folder_obj->setDefaultPerspective($default_perspective);
					}
					
					$em->flush();
					
					$security_check->createFolderLog($em, 'UPDATE', $folder_obj);
					
					if (!empty($propagate_fields) && count($propagate_fields) > 0 && $folder_obj->getChildren()->count() > 0) 
					{
						if (in_array('Managers', $propagate_fields) || in_array('Editors', $propagate_fields) || in_array('Authors', $propagate_fields) || in_array('Readers', $propagate_fields) || in_array('DeleteRights', $propagate_fields)) {
							$this->updateSubfolderACL($em, $folder_obj, $children, $propagate_fields);
						}
						$this->updateSubfoldeProperties($em, $folder_obj, $folder_obj->getChildren(), $propagate_fields);
					}
				}
			}
			elseif ($action === 'NEWFACET' || $action === 'UPDATEFACET') 
			{
				$fields = array();
				$facet_fields = '';
				$library = trim($dom->getElementsByTagName('library')->item(0)->nodeValue);
				$facet_name = trim($dom->getElementsByTagName('name')->item(0)->nodeValue);
				$facet_desc = trim($dom->getElementsByTagName('description')->item(0)->nodeValue);
				if (trim($dom->getElementsByTagName('field1')->item(0)->nodeValue)) {
					$fields[] = trim($dom->getElementsByTagName('field1')->item(0)->nodeValue);
				}
				if (trim($dom->getElementsByTagName('field2')->item(0)->nodeValue)) {
					$fields[] = trim($dom->getElementsByTagName('field2')->item(0)->nodeValue);
				}
				if (trim($dom->getElementsByTagName('field3')->item(0)->nodeValue)) {
					$fields[] = trim($dom->getElementsByTagName('field3')->item(0)->nodeValue);
				}
				if (trim($dom->getElementsByTagName('field4')->item(0)->nodeValue)) {
					$fields[] = trim($dom->getElementsByTagName('field4')->item(0)->nodeValue);
				}
				if (trim($dom->getElementsByTagName('field5')->item(0)->nodeValue)) {
					$fields[] = trim($dom->getElementsByTagName('field5')->item(0)->nodeValue);
				}
				$em = $this->getDoctrine()->getManager();
				$facet = trim($dom->getElementsByTagName('facetID')->item(0)->nodeValue);
				$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library, 'Status' => true, 'Trash' => false));
				if (!empty($facet_name) && !empty($fields) && !empty($library)) 
				{
					if ($action == 'NEWFACET') 
					{
						$facet_map = new FacetMaps();
					}
					else {
						$facet_map = $em->getRepository('DocovaBundle:FacetMaps')->find($facet);
						foreach ($facet_map->getFields() as $column) {
							$facet_map->remvoveFields($column);
						}
						$em->flush();
						unset($column);
					}
					$facet_map->setFacetMapName($facet_name);
					$facet_map->setDescription($facet_desc);
					$cnt = 0;
					foreach ($fields as $field) 
					{
						$column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $field));
						if (!empty($column)) {
							$facet_fields .= $field.':'.($cnt + 1).',';
							$facet_map->addFields($column);
						}
						$cnt++;
					}
					$facet_map->setFacetMapFields($facet_fields);
					$facet_map->setLibrary($library);
					if ($action == 'NEWFACET') {
						$em->persist($facet_map);
					}
					$em->flush();
				}
				else {
					$res_xml = new \DOMDocument("1.0", "UTF-8");
					$root = $res_xml->appendChild($res_xml->createElement('Results'));
					$root_child = $res_xml->createElement('Result', 'FAILED');
					$root_att = $res_xml->createAttribute('ID');
					$root_att->value = 'Status';
					$root_child->appendChild($root_att);
					$root->appendChild($root_child);
					$root_child = $res_xml->createElement('Result', 'Facet name cannot be empty. At leat one column should be selected.');
					$root_att = $res_xml->createAttribute('ID');
					$root_att->value = 'ErrMsg';
					$root_child->appendChild($root_att);
					$root->appendChild($root_child);
					$response->setContent($res_xml->saveXML());
					return $response;
				}
			}
			elseif ($action === 'NEWPERSPECTIVE' || $action === 'UPDATEPERSPECTIVE')
			{
				$folder_id = $dom->getElementsByTagName('Unid')->item(0)->nodeValue;
				//check for element to prevent exception saving perspective
				if (!empty($dom->getElementsByTagName('facetMapID')->length)){
					$facet_map = $dom->getElementsByTagName('facetMapID')->item(0)->nodeValue;
				}
				if (empty($folder_id))
				{
					throw $this->createNotFoundException('Folder ID is missed.');
				}
				
				$em = $this->getDoctrine()->getManager();
				if ($action === 'UPDATEPERSPECTIVE')
				{
					$p_id = $dom->getElementsByTagName('Unid')->item(1)->nodeValue;
					if (empty($p_id))
					{
						throw $this->createNotFoundException('Perspective ID is missed.');
					}

					$perspective = $em->find('DocovaBundle:SystemPerspectives', $p_id);
					if (empty($perspective))
					{
						throw $this->createNotFoundException('Unspecified source perspective ID = '.$p_id);
					}
				}

				$folder_obj = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id, 'Del' => false, 'Inactive' => 0));
				if (empty($folder_obj))
				{
					throw $this->createNotFoundException('Unspecified source folder ID = '.$folder_id);
				}
				
				$facet_map = (!empty($facet_map)) ? $em->find('DocovaBundle:FacetMaps', $facet_map) : $em->getRepository('DocovaBundle:FacetMaps')->getSystemDefaultFacetMap(); 
				if (empty($facet_map))
				{
					throw $this->createNotFoundException('Unspecified source Facet Map.');
				}

				if ($action === 'NEWPERSPECTIVE')
				{
					$exists = $em->getRepository('DocovaBundle:SystemPerspectives')->perspetiveExists($dom->getElementsByTagName('name')->item(0)->nodeValue, $folder_obj->getLibrary()->getId());
					if ($exists === true)
					{
						$res_xml = new \DOMDocument("1.0", "UTF-8");
						$root = $res_xml->appendChild($res_xml->createElement('Results'));
						$root_child = $res_xml->createElement('Result', 'FAILED');
						$root_att = $res_xml->createAttribute('ID');
						$root_att->value = 'Status';
						$root_child->appendChild($root_att);
						$root->appendChild($root_child);
						$root_child = $res_xml->createElement('Result', 'Another perspective with this name already exists.');
						$root_att = $res_xml->createAttribute('ID');
						$root_att->value = 'ErrMsg';
						$root_child->appendChild($root_att);
						$root->appendChild($root_child);
	
						$response->setContent($res_xml->saveXML());
						return $response;
					}
					
					$perspective = new SystemPerspectives();
					$perspective->setCreator($this->user);
					$perspective->setDateCreated(new \DateTime());
					$perspective->setIsSystem(false);
					$perspective->setVisibility('Folder');
					$perspective->setBuiltInFolder($folder_obj);
					$perspective->setLibrary($folder_obj->getLibrary());
				}

				$perspective->setDescription($dom->getElementsByTagName('description')->item(0)->nodeValue);
				$perspective->setFacetMap($facet_map);
				$perspective->setPerspectiveName($dom->getElementsByTagName('name')->item(0)->nodeValue);
				$perspective->setXmlStructure($dom->saveXML($dom->getElementsByTagName('viewsettings')->item(0)));
				
				if (!empty($dom->getElementsByTagName('autocollapse')->item(0)->nodeValue)) {
					$perspective->setCollapseFirst(true);
				}
				else {
					$perspective->setCollapseFirst(false);
				}
				if (!empty($dom->getElementsByTagName('libscope')->item(0)->nodeValue))
				{
					$perspective->setAvailableForFolders(true);
				}
				else {
					$perspective->setAvailableForFolders(false);
				}

				if ($action === 'NEWPERSPECTIVE')
				{
					$em->persist($perspective);
				}
				$em->flush();

				if (!empty($dom->getElementsByTagName('libdefault')->item(0)->nodeValue))
				{
					if ($perspective->getLibrary()) {
						$perspective->getLibrary()->setDefaultPerspective($perspective);
					}
					else {
						$folder_obj->getLibrary()->setDefaultPerspective($perspective);
					}
					$em->flush();
				}
				else {
					if ($action === 'UPDATEPERSPECTIVE' && $perspective->getLibrary())
					{
						$perspective->getLibrary()->setDefaultPerspective(null);
						$em->flush();
					}
				}

				if (!empty($dom->getElementsByTagName('setdefault')->item(0)->nodeValue))
				{
					$folder_obj->setDefaultPerspective($perspective);
					
					$em->flush();
				}
			}
			elseif ($action === 'REMOVEPERSPECTIVE')
			{
				$folder_id = $dom->getElementsByTagName('Unid')->item(0)->nodeValue;
				if (empty($folder_id))
				{
					throw $this->createNotFoundException('Folder ID is missed.');
				}
				
				$em = $this->getDoctrine()->getManager();
				$folder_obj = $em->find('DocovaBundle:Folders', $folder_id);
				if (empty($folder_obj))
				{
					throw $this->createNotFoundException('Unspecified source folder ID = '. $folder_id);
				}
				
				$perspective = $em->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Default_For' => 'Folder', 'Is_System' => true));
				if (empty($perspective))
				{
					throw $this->createNotFoundException('No system default perspective for folder is defined.');
				}

				$folder_obj->setDefaultPerspective($perspective);
				$em->flush();
			}
			elseif ($action === 'DELETEPERSPECTIVE')
			{
				$p_id = $dom->getElementsByTagName('Unid')->item(1)->nodeValue;
				if (empty($p_id))
				{
					throw $this->createNotFoundException('Perspective ID is missed.');
				}
				
				$em = $this->getDoctrine()->getManager();
				$perspective = $em->find('DocovaBundle:SystemPerspectives', $p_id);
				if (empty($perspective))
				{
					throw $this->createNotFoundException('Uspecified source perspective ID = '. $p_id);
				}
				
				$folders_list = $em->getRepository('DocovaBundle:Folders')->findBy(array('Default_Perspective' => $p_id));
				if (!empty($folders_list) && count($folders_list) > 0)
				{
					$default_perspective = $em->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Default_For' => 'Folder', 'Is_System' => true));
					if (empty($default_perspective))
					{
						throw $this->createNotFoundException('No system default perspective for folder is defined.');
					}

					foreach ($folders_list as $folder)
					{
						$folder->setDefaultPerspective($default_perspective);
						$em->flush();
					}
				}
				
				$em->remove($perspective);
				$em->flush();
			}
			elseif ($action === 'GETFOLDERINFO') {
				$info_type = $dom->getElementsByTagName('InfoType')->item(0)->nodeValue;
				$folder = $dom->getElementsByTagName('Unid')->item(0)->nodeValue;
				$folder_info = '';
				
				$em = $this->getDoctrine()->getManager();
				$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder, 'Del' => false, 'Inactive' => 0));
				
				if (!empty($folder)) {
					switch ($info_type) {
						case 'FolderName':
							$folder_info = $folder->getFolderName();
							break;
						case 'FolderPath':
							$folder_info = $folder->getFolderPath();
							break;
						case 'FolderUrl':
							$folder_info = $this->generateUrl('docova_homeframe').'?goto=' . $folder->getLibrary()->getId() . ',' . $folder->getId();
							break;
						case 'FolderTemplateType':
							$folder_info = '0';
							break;
						case 'FolderRenameAllowed':
							$folder_info = 'Yes';
							break;
					}
				}
			}
			elseif ($action === 'QUERYACCESS') {
				$folder = $dom->getElementsByTagName('DocKey')->item(0)->nodeValue;
				$em = $this->getDoctrine()->getManager();
				$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder, 'Del' => false, 'Inactive' => 0));
				if (!empty($folder)) 
				{
					if (trim($dom->getElementsByTagName('AccessType')->item(0)->nodeValue) == 'CANCREATEDOCUMENTS') {
						$access = true === $security_check->canCreateDocument($folder) ? 1 : 0;
					}
					else {
						$access = $security_check->getAccessLevel($folder);
						$access = $access['docacess'];
					}
				}
			}
			else {
				$valid_action = false;
			}
		}
		else {
			throw $this->createNotFoundException('No POST request is submitted.');
		}
		
		$res_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $res_xml->appendChild($res_xml->createElement('Results'));
		if ($valid_action === true)
		{
			if ($granted === false) {
				$root_child = $res_xml->createElement('Result', 'FAILED');
			}
			else {
				$root_child = $res_xml->createElement('Result', 'OK');
			}
			$root_att = $res_xml->createAttribute('ID');
			$root_att->value = 'Status';
			$root_child->appendChild($root_att);
			$root->appendChild($root_child);
	
			if ($granted === false) {
				$root_child = $res_xml->createElement('Result', 'Insufficient access to create folder.');
				$root_att = $res_xml->createAttribute('ID');
				$root_att->value = 'ErrMsg';
				$root_child->appendChild($root_att);
				$root->appendChild($root_child);
			}
			else {
				if ($action == 'NEW') {
					$root_child = $res_xml->createElement('Result', $entity->getId());
					$root_att = $res_xml->createAttribute('ID');
					$root_att->value = 'Unid';
					$root_child->appendChild($root_att);
				}
				elseif ($action == 'NEWFACET' || $action == 'UPDATEFACET') {
					$root_child = $res_xml->createElement('Result', $facet_map->getId());
					$root_att = $res_xml->createAttribute('ID');
					$root_att->value = 'Ret1';
					$root_child->appendChild($root_att);
				}
				elseif ($action == 'NEWPERSPECTIVE') {
					$root_child = $res_xml->createElement('Result', $perspective->getId());
					$root_att = $res_xml->createAttribute('ID');
					$root_att->value = 'Ret1';
					$root_child->appendChild($root_att);
				}
				elseif ($action == 'GETFOLDERINFO' && !empty($folder_info)) {
					$root_child = $res_xml->createElement('Result', $folder_info);
					$root_att = $res_xml->createAttribute('ID');
					$root_att->value = 'Ret1';
					$root_child->appendChild($root_att);
				}
				elseif ($action == 'DELETE') {
					$root_child = $res_xml->createElement('Result', $entity->getId());
					$root_att = $res_xml->createAttribute('ID');
					$root_att->value = 'Parent';
					$root_child->appendChild($root_att);
				}
				elseif ($action == 'GETMETADATA') {
					$root_child = $res_xml->createElement('Result');
					$root_att = $res_xml->createAttribute('ID');
					$root_att->value = 'Ret1';
					$root_child->appendChild($root_att);
					$child_value = $res_xml->createElement('Fields');
					$root_child->appendChild($child_value);
				}
				elseif ($action == 'QUERYACCESS') {
					$root_child = $res_xml->createElement('Result', empty($access) ? 0 : $access);
					$root_att = $res_xml->createAttribute('ID');
					$root_att->value = 'Ret1';
					$root_child->appendChild($root_att);
				}
				if ($action !== 'RENAME' && $action !== 'UPDATE') {
					$root->appendChild($root_child);
				}
			}
		}
		
		$response->setContent($res_xml->saveXML());
		
		return $response;
	}
	
	public function getFolderPropertiesAction(Request $request)
	{
		$this->initialize();
		$folder_id	= $request->query->get('ParentUNID');
		$mode_url	= $request->query->get('mode');
		$em = $this->getDoctrine()->getManager();
		$folder_obj = $em->getRepository('DocovaBundle:Folders')->find($folder_id);
		
		$owners = $editors = $authors = $readers = $delete_users = $restricted_names = array();
		$parent_managers = $parent_authors = $parent_readers = array();
		$perspectives = array('default' => array(), 'custom' => array());
		$is_owner = false;
		
		if (empty($folder_obj) && substr($folder_id, 0, 5) != "RCBIN") {
			throw $this->createNotFoundException('No Folder is found for ID = '. $folder_id);
		}
		
		$view_columns = $em->getRepository('DocovaBundle:ViewColumns')->findAll();
		if (substr($folder_id, 0, 5) != "RCBIN") {
			$folder_perspectives = $em->getRepository('DocovaBundle:SystemPerspectives')->getFoldersPerspectives($folder_obj->getLibrary()->getId());
			if (!empty($folder_perspectives)) 
			{
				foreach ($folder_perspectives as $fp) {
					if ($fp->getIsSystem() || $fp->getAvailableForFolders()) {
						$perspectives['default'][] = $fp;
					}
					else {
						$perspectives['custom'][] = $fp;
					}
				}
			}
			$folder_perspectives = $fp = null;
		}
		
		if ((substr($folder_id, 0, 5) != "RCBIN")) 
		{
			$applicable_doctypes = $folder_obj->getLibrary()->getApplicableDocType();
			if (empty($applicable_doctypes[0])) {
				$applicable_doctypes = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false), array('Doc_Name' => 'ASC'));
			}
			
			$folder_doctypes = $folder_obj->getApplicableDocType();
			if (empty($folder_doctypes[0])) {
				$folder_doctypes = '';
			}
		
			$parent_folder = $folder_obj;
			$customACL_obj = new CustomACL($this->container);
			$users_list = $customACL_obj->getObjectACEUsers($parent_folder, 'delete');
			if ($users_list->count() > 0) {
				foreach ($users_list as $user) {
					$delete_users[] = $user->getUsername();
				}
			}
			$groups_list = $customACL_obj->getObjectACEGroups($parent_folder, 'delete');
			if ($groups_list->count() > 0) {
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole())) {
						$delete_users[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					}
				}
			}

			$users_list = $customACL_obj->getObjectACEUsers($folder_obj, 'master');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user)
				{
					$editors[] = $user->getUsername();
				}
				$user = null;
			}
			$groups_list = $customACL_obj->getObjectACEGroups($folder_obj, 'master');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$editors[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					}
					$g = null;
				}
				$group = null;
			}

			$ancestors = $em->getRepository('DocovaBundle:Folders')->getAncestors($parent_folder->getPosition(), $parent_folder->getLibrary()->getId(), $parent_folder->getId(), true);
			foreach ($ancestors as $folder) 
			{
				$folder = $em->getReference('DocovaBundle:Folders', $folder['id']);
				if ($is_owner === false && true === $customACL_obj->isUserGranted($folder, $this->user, 'owner')) 
				{
					$is_owner = true;
				}
				$users_list = $customACL_obj->getObjectACEUsers($folder, 'owner');
				if ($users_list->count() > 0)
				{
					foreach ($users_list as $user)
					{
						if ($folder->getId() == $folder_obj->getId()) {
							$owners[] = $user->getUsername();
						}
						else {
							$parent_managers[] = $user->getUsername();
							$restricted_names[] = $user->getUsername();
						}
					}
				}
							
				$groups_list = $customACL_obj->getObjectACEGroups($folder, 'owner');
				if ($groups_list->count() > 0) 
				{
					foreach ($groups_list as $group) {
						if (false !== $g = $this->retrieveGroupByRole($group->getRole())) 
						{
							if ($folder->getId() === $folder_obj->getId()) {
								$owners[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							}
							else {
								$parent_managers[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
								$restricted_names[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							}
						}
						unset($g);
					}
				}

				$users_list = $customACL_obj->getObjectACEUsers($folder, 'create');
				if ($users_list->count() > 0)
				{
					foreach ($users_list as $user)
					{
						if ($folder->getId() == $folder_obj->getId()) {
							$authors[] = $user->getUsername();
							$restricted_names[] = $user->getUsername();
						}
						else {
							$parent_authors[] = $user->getUsername();
						}
					}
				}
				$groups_list = $customACL_obj->getObjectACEGroups($folder, 'create');
				if ($groups_list->count() > 0) 
				{
					foreach ($groups_list as $group)
					{
						if (false !== $g = $this->retrieveGroupByRole($group->getRole())) 
						{
							if ($folder->getId() == $folder_obj->getId()) {
								$authors[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
								$restricted_names[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							}
							else {
								$parent_authors[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							}
						}
					}
				}
				
				$users_list = $customACL_obj->getObjectACEUsers($folder, 'view');
				if ($users_list->count() > 0) 
				{
					foreach ($users_list as $user) {
						if ($folder->getId() != $folder_obj->getId()) {
							$parent_readers[] = $user->getUsername();
						}
						$readers[] = $user->getUsername();
						$restricted_names[] = $user->getUsername();
					}
				}
				$groups_list = $customACL_obj->getObjectACEGroups($folder, 'view');
				if ($groups_list->count() > 0) 
				{
					foreach ($groups_list as $group) {
						if (false !== $g = $this->retrieveGroupByRole($group->getRole())) 
						{
							if ($folder->getId() != $folder_obj->getId()) 
							{
								$parent_readers[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							}
							$readers[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							$restricted_names[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						}
					}
				}
			}

			$user = $group = null;
			if (!empty($parent_managers) && count($parent_managers) > 0) 
			{
				foreach ($parent_managers as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$parent_managers[$index] = $user->getUserNameDnAbbreviated();
					}
				}
				$user = null;
			}
	
			if (!empty($owners) && count($owners) > 0)
			{
				foreach ($owners as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$owners[$index] = $user->getUserNameDnAbbreviated();
					}
				}
				$user = null;
			}

			if (!empty($editors) && count($editors) > 0)
			{
				foreach ($editors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$editors[$index] = $user->getUserNameDnAbbreviated();
					}
				}
				$user = null;
			}

			if (!empty($parent_authors) && count($parent_authors) > 0)
			{
				foreach ($parent_authors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$parent_authors[$index] = $user->getUserNameDnAbbreviated();
					}
				}
				$user = null;
			}
	
			if (!empty($authors) && count($authors) > 0)
			{
				foreach ($authors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$authors[$index] = $user->getUserNameDnAbbreviated();
					}
				}
				$user = null;
			}
	
			if (!empty($parent_readers) && count($parent_readers) > 0)
			{
				foreach ($parent_readers as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$parent_readers[$index] = $user->getUserNameDnAbbreviated();
					}
				}
				$user = null;
			}
	
			if (!empty($readers) && count($readers) > 0)
			{
				foreach ($readers as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$readers[$index] = $user->getUserNameDnAbbreviated();
					}
					$user = null;
				}
			}
	
			if (!empty($delete_users) && count($delete_users) > 0)
			{
				foreach ($delete_users as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$delete_users[$index] = $user->getUserNameDnAbbreviated();
					}
				}
				$user = null;
			}
	
			if (!empty($restricted_names) && count($restricted_names) > 0)
			{
				foreach ($restricted_names as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$restricted_names[$index] = $user->getUserNameDnAbbreviated();
					}
				}
				$user = null;
			}
	
			if (!empty($parent_managers)) {
				$parent_managers = array_unique($parent_managers);
				$parent_managers = array_reverse($parent_managers);
				$parent_managers = implode(',', $parent_managers);
			}
			else {
				$parent_managers = '';
			}
	
			if (!empty($owners)) {
				$owners = array_unique($owners);
				$owners = array_reverse($owners);
				$owners = implode(',', $owners);
			}
			else {
				$owners = '';
			}

			if (!empty($editors)) {
				$editors = array_unique($editors);
				$editors = array_reverse($editors);
				$editors = implode(',', $editors);
			}
			else {
				$editors = '';
			}

			if (!empty($parent_authors)) {
				$parent_authors = array_unique($parent_authors);
				$parent_authors = array_reverse($parent_authors);
				$parent_authors = implode(',', $parent_authors);
			}
			else {
				$parent_authors = '';
			}
	
			if (!empty($authors)) {
				$authors = array_unique($authors);
				$authors = array_reverse($authors);
				$authors = implode(',', $authors);
			}
			else {
				$authors = '';
			}
	
			if (!empty($parent_readers)) {
				$parent_readers = array_unique($parent_readers);
				$parent_readers = array_reverse($parent_readers);
				$parent_readers = implode(',', $parent_readers);
			}
			else {
				$parent_readers = '';
			}
	
			if ($folder_obj->getRestrictRTA() === true)
			{
				$readers = 'Only Authors above';
			}
			else {
				if (!empty($readers)) {
					$readers = array_unique($readers);
					$readers = array_reverse($readers);
					$readers = implode(',', $readers);
				}
				else {
					$readers = '';
				}
			}
	
			if (!empty($delete_users)) {
				$delete_users = array_unique($delete_users);
				$delete_users = array_reverse($delete_users);
				$delete_users = implode(',', $delete_users);
			}
			else {
				$delete_users = '';
			}
			
			if (!empty($restricted_names)) {
				$restricted_names = array_unique($restricted_names);
				$restricted_names = implode(',',$restricted_names);
			}
			else {
				$restricted_names = '';
			}
		}
		else {
			$applicable_doctypes = $folder_doctypes = [];
			$owners = $editors = $authors = $readers = $delete_users = '';
		}
		
		$security_context = $this->container->get('security.authorization_checker');
		if (substr($folder_id, 0, 5) != "RCBIN")
			$is_owner = ($security_context->isGranted('ROLE_ADMIN') || $security_context->isGranted('MASTER', $folder_obj->getLibrary())) ? true : $is_owner;
		$security_context = null;
				
		$read_mode = ($is_owner === false || $mode_url === 'R') ? '-read' : '';

		return $this->render('DocovaBundle:Default:dlgFolderProperties'.$read_mode.'.html.twig', array(
			'folder' => $folder_obj,
			'user' => $this->user,
			'mode' => $mode_url,
			'applicable_doctypes' => $applicable_doctypes,
			'folder_doctypes' => $folder_doctypes,
			'parent_authors_list' => $parent_authors,
			'parent_readers_list' => $parent_readers,
			'authors_list' => $authors,
			'readers_list' => $readers,
			'delete_list' => $delete_users,
			'parent_managers' => $parent_managers,
			'restricted_names' => $restricted_names,
			'view_columns' => $view_columns,
			'perspectives' => $perspectives,
			'owners' => $owners,
			'editors' => $editors
		));
	}
	
	public function getFolderLogsAction($folder)
	{
		$xml_log = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_log->appendChild($xml_log->createElement('logentries'));
		$em = $this->getDoctrine()->getManager();
		$logs = $em->getRepository('DocovaBundle:FoldersLog')->findBy(array('Folder' => $folder));
		
		if (!empty($logs)) {
			$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
			$global_settings = $global_settings[0];
			$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
			unset($global_settings);
			for ($x = 0; $x < count($logs); $x++) {

				$child = $root->appendChild($xml_log->createElement('logentry'));

				$child->appendChild($xml_log->createElement('logaction', $logs[$x]->getLogAction()));
				$child->appendChild($xml_log->createElement('logactionstatus', ($logs[$x]->getLogStatus() === true) ? 'OK' : 'ERROR'));
				$child->appendChild($xml_log->createElement('logdate', $logs[$x]->getLogDate()->format($defaultDateFormat)));
				$child->appendChild($xml_log->createElement('logtime', $logs[$x]->getLogDate()->format('H:i:s')));
				$author = $child->appendChild($xml_log->createElement('logauthor'));
				$author->appendChild($xml_log->createCDATASection($logs[$x]->getLogAuthor()->getUserNameDnAbbreviated()));
				$child->appendChild($xml_log->createElement('logdetails', $logs[$x]->getLogDetails()));
			}
		}
		
		$response = new Response($xml_log->saveXML());
		$response->headers->set('Content-Type', 'text/xml');

		return $response;
	}
	
	public function getFolderInfoAction(Request $request)
	{
		$folder_id = $request->query->get('ParentUNID');
		if (empty($folder_id))
		{
			throw $this->createNotFoundException('Folder ID is missed.');
		}
		
		$folder_obj = $this->getDoctrine()->getManager()->find('DocovaBundle:Folders', $folder_id);
		if (empty($folder_obj))
		{
			throw $this->createNotFoundException('Unspecified source folder ID = '.$folder_id);
		}

		$this->initialize();
		$parent_folder = $folder_obj;
		$customACL_obj = new CustomACL($this->container);
		$owners = $authors = $readers = array();

		while ($parent_folder) 
		{
			$users_list = $customACL_obj->getObjectACEUsers($parent_folder, 'owner');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user)
				{
					if ($parent_folder == $folder_obj) {
						$owners[] = $user->getUsername();
					}
				}
			}
				
			$users_list = $customACL_obj->getObjectACEUsers($parent_folder, 'master');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user)
				{
					if ($parent_folder == $folder_obj) {
						$owners[] = $user->getUsername();
					}
				}
			}
			$groups_list = $customACL_obj->getObjectACEGroups($parent_folder, 'master');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						if ($parent_folder->getId() === $folder_obj->getId()) {
							$owners[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						}
					}
					unset($g);
				}
				$group = $g = null;
			}
				
			$users_list = $customACL_obj->getObjectACEUsers($parent_folder, 'create');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user)
				{
					if ($parent_folder == $folder_obj) {
						$authors[] = $user->getUsername();
					}
				}
			}
			$groups_list = $customACL_obj->getObjectACEGroups($parent_folder, 'create');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group)
				{
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						if ($parent_folder->getId() == $folder_obj->getId()) {
							$authors[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						}
					}
				}
			}
			$group = $g = null;
				
			$users_list = $customACL_obj->getObjectACEUsers($parent_folder, 'view');
			if ($users_list->count() > 0) 
			{
				foreach ($users_list as $user) 
				{
					$readers[] = $user->getUsername();
				}
			}
			$groups_list = $customACL_obj->getObjectACEGroups($parent_folder, 'view');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$readers[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					}
				}
			}

			$parent_folder = $parent_folder->getParentfolder();
		}
		if (!empty($owners) && count($owners) > 0)
		{
			foreach ($owners as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false)) {
					$owners[$index] = $user->getUserNameDnAbbreviated();
				}
				$user = null;
			}
		}
		
		if (!empty($authors) && count($authors) > 0)
		{
			foreach ($authors as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false)) {
					$authors[$index] = $user->getUserNameDnAbbreviated();
				}
				$user = null;
			}
		}
		
		if (!empty($readers) && count($readers) > 0)
		{
			foreach ($readers as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false)) {
					$readers[$index] = $user->getUserNameDnAbbreviated();
				}
				$user = null;
			}
		}

		if (!empty($owners)) {
			$owners = array_unique($owners);
			$owners = array_reverse($owners);
			$owners = implode(',', $owners);
		}
		else {
			$owners = '';
		}
		
		if (!empty($authors)) {
			$authors = array_unique($authors);
			$authors = array_reverse($authors);
			$authors = implode(',', $authors);
		}
		else {
			$authors = '';
		}
		
		if ($folder_obj->getRestrictRTA() === true)
		{
			$readers = 'Only Authors above';
		}
		else {
			if (!empty($readers)) {
				$readers = array_unique($readers);
				$readers = array_reverse($readers);
				$readers = implode(',', $readers);
			}
			else {
				$readers = '';
			}
		}

		return $this->render('DocovaBundle:Default:dlgFolderInfo.html.twig', array(
					'user' => $this->user,
					'folder' => $folder_obj,
					'managers' => $owners,
					'authors' => $authors,
					'readers' => $readers
				));
	}
	
	public function openFolderSelectAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgFolderSelect.html.twig', array(
					'user' => $this->user
				));
	}

	public function getFolderFilesAction(Request $request)
	{
		$this->initialize();
		$folder = $request->query->get('folderid');
		if (empty($folder))
		{
			throw $this->createNotFoundException('Folder ID is missed.');
		}
	
		$em = $this->getDoctrine()->getManager();
		$documents = $em->getRepository('DocovaBundle:Documents')->findBy(array('folder' => $folder, 'Trash' => false, 'Archived' => false));
		$folder = $em->getRepository('DocovaBundle:Folders')->find($folder);
		$xml = '<Db>';
		if (!empty($documents) && count($documents) > 0)
		{
			$access_control = new Miscellaneous($this->container);
			$security_check = $this->get('security.authorization_checker');
			$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
			foreach ($documents as $doc)
			{
				if ($access_control->canReadDocument($doc, true) || (($security_check->isGranted('ROLE_ADMIN') || $security_check->isGranted('MASTER', $folder->getLibrary())) && $access_control->isLibrarySubscribed($doc->getFolder()->getLibrary())))
				{
					$doc_authors = $this->getDocumentAuthors($doc);
					$attachments = $dates = $sizes = '';
					if ($doc->getAttachments()->count() > 0) 
					{
						foreach ($doc->getAttachments() as $att) {
							$attachments .= $att->getFileName() . ';';
							$dates .= $att->getFileDate()->format($defaultDateFormat) . ';';
							$sizes .= $att->getFileSize() . ';';
						}
						$attachments = substr_replace($attachments, '', -1);
						$dates = substr_replace($dates, '', -1);
						$sizes = substr_replace($sizes, '', -1);
					}
					
					$aco = 0;
					if (in_array('ATT', $doc->getDocType()->getContentSections())) 
					{
						if ($properties = $em->getRepository('DocovaBundle:DocumentTypes')->containsSubform($doc->getDocType()->getId(), 'Attachments')) {
							if (strpos($properties['Properties_XML'], '<EnableFileCIAO>1</EnableFileCIAO>')) {
								$aco = 1;
							}
						}
					}
						
					$xml .= '<Doc><selected />';
					$xml .= '<docauthors><![CDATA[[Administration];[LibraryAdmin];'.$doc_authors.']]></docauthors>';
					$xml .= "<unid>{$doc->getId()}</unid>";
					$xml .= "<dockey>{$doc->getId()}</dockey>";
					$xml .= "<subject><![CDATA[{$doc->getDocTitle()}]]></subject>";
					$xml .= "<name><![CDATA[$attachments]]></name>";
					$xml .= "<date>$dates</date>";
					$xml .= "<size>$sizes</size>";
					$xml .= "<aco>$aco</aco>";
					$xml .= "<DocumentTypeKey>{$doc->getDocType()->getId()}</DocumentTypeKey>";
					$xml .= "<Summary><![CDATA[ <div style=\'margin-bottom:3px;padding-left:20px\'><b>{$doc->getDocTitle()}</b></div><div style=\'padding-left:20px;\'>Author: {$doc->getAuthor()->getUserNameDnAbbreviated()}, Created: {$doc->getDateCreated()->format($defaultDateFormat)}, Modified: ".($doc->getDateModified() ? $doc->getDateModified()->format($defaultDateFormat) : '')."<br/>Status: {$doc->getDocStatus()}</div>]]></Summary>";
					$xml .= '</Doc>';
				}
			}
		}
		$xml .= '</Db>';
		return $this->render('DocovaBundle:Default:doeGetFolderFiles.html.twig', array(
			'user' => $this->user,
			'documents_xml' => $xml,
			'folder' => $folder
		));
	}
	
	public function getDOEFolderPropertiesAction(Request $request)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_result->appendChild($xml_result->createElement('document'));
		$em = $this->getDoctrine()->getManager();
		$folder = $request->query->get('ParentUNID');
		$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder, 'Del' => false, 'Inactive' => 0));
		if (!empty($folder)) 
		{
			$folder instanceof \Docova\DocovaBundle\Entity\Folders;
			$newnode = $xml_result->createElement('DocID', $folder->getId());
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('DocKey', $folder->getId());
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('FolderID', $folder->getId());
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('FolderName');
			$cdata = $xml_result->createCDATASection($folder->getFolderName());
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('Description');
			$cdata = $xml_result->createCDATASection($folder->getDescription());
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$ancestors = $folder->getParentfolder() ? str_replace('\\', ',', $folder->getParentFolder()->getFolderPath()) : '';
			$newnode = $xml_result->createElement('FolderAncestors', $ancestors);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('FolderPath');
			$cdata = $xml_result->createCDATASection($folder->getFolderPath());
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('IconNormal', $folder->getIconNormal());
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('IconSelected', $folder->getIconSelected());
			$root->appendChild($newnode);
			$owners = $editors = $authors = $readers = $doc_authors = $doc_readers = array();
			$parent_folder = $folder;
			$customAcl = new CustomACL($this->container);

			$users_list = $customAcl->getObjectACEUsers($parent_folder, 'master');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user)
				{
					$editors[] = $user->getUsername();
					$doc_authors[] = $user->getUsername();
				}
			}

			$groups_list = $customAcl->getObjectACEGroups($parent_folder, 'master');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group)
				{
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$editors[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						$doc_authors[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					}
				}
			}

			while ($parent_folder) {
				$users_list = $customAcl->getObjectACEUsers($parent_folder, 'owner');
				if ($users_list->count() > 0) 
				{
					foreach ($users_list as $user)
					{
						if ($parent_folder == $folder) {
							$owners[] = $user->getUsername();
						}
						if (!in_array($user->getUsername(), $doc_authors)) {
							$doc_authors[] = $user->getUsername();
						}
					}
				}

				$groups_list = $customAcl->getObjectACEGroups($parent_folder, 'owner');
				if ($groups_list->count() > 0)
				{
					foreach ($groups_list as $group)
					{
						if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
						{
							if ($parent_folder == $folder) {
								$owners[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							}
							$g = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							if (!in_array($g, $doc_authors)) {
								$doc_authors[] = $g;
							}
						}
					}
				}

				$users_list = $customAcl->getObjectACEUsers($parent_folder, 'create');
				if ($users_list->count() > 0)
				{
					foreach ($users_list as $user)
					{
						if ($parent_folder == $folder) {
							$authors[] = $user->getUsername();
						}
						if (!in_array($user->getUsername(), $doc_authors)) {
							$doc_authors[] = $user->getUsername();
						}
					}
				}

				$groups_list = $customAcl->getObjectACEGroups($parent_folder, 'create');
				if ($groups_list->count() > 0)
				{
					foreach ($groups_list as $group)
					{
						if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
						{
							if ($parent_folder == $folder) {
								$authors[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							}
							$g = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							if (!in_array($g, $doc_authors)) {
								$doc_authors[] = $g;
							}
						}
					}
				}

				$users_list = $customAcl->getObjectACEUsers($parent_folder, 'view');
				if ($users_list->count() > 0)
				{
					foreach ($users_list as $user)
					{
						if ($parent_folder == $folder) {
							$readers[] = $user->getUsername();
						}
						if (!in_array($user->getUsername(), $doc_readers)) {
							$doc_readers[] = $user->getUsername();
						}
					}
				}

				$groups_list = $customAcl->getObjectACEGroups($parent_folder, 'view');
				if ($groups_list->count() > 0)
				{
					foreach ($groups_list as $group)
					{
						if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
						{
							if ($parent_folder == $folder) {
								$readers[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							}
							$g = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							if (!in_array($g, $doc_readers)) {
								$doc_readers[] = $g;
							}
						}
					}
				}

				$parent_folder = $parent_folder->getParentFolder();
			}
			
			if (!empty($doc_authors) && is_array($doc_authors)) 
			{
				foreach ($doc_authors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$doc_authors[$index] = $user->getUserNameDnAbbreviated();
					}
				}
			}

			if (!empty($doc_readers) && is_array($doc_readers))
			{
				foreach ($doc_readers as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$doc_readers[$index] = $user->getUserNameDnAbbreviated();
					}
				}
			}

			if (!empty($owners) && is_array($owners))
			{
				foreach ($owners as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$owners[$index] = $user->getUserNameDnAbbreviated();
					}
				}
			}

			if (!empty($editors) && is_array($editors))
			{
				foreach ($editors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$editors[$index] = $user->getUserNameDnAbbreviated();
					}
				}
			}

			if (!empty($authors) && is_array($authors))
			{
				foreach ($authors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$authors[$index] = $user->getUserNameDnAbbreviated();
					}
				}
			}

			if (!empty($readers) && is_array($readers))
			{
				foreach ($readers as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true)) {
						$readers[$index] = $user->getUserNameDnAbbreviated();
					}
				}
			}

			$newnode = $xml_result->createElement('DocAuthors');
			$cdata = $xml_result->createCDATASection(implode(';', $doc_authors));
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('DocReaders');
			$cdata = $xml_result->createCDATASection(implode(';', $doc_readers));
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('CreatedBy');
			$cdata = $xml_result->createCDATASection($folder->getCreator()->getUserNameDnAbbreviated());
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('DocumentOwner');
			$cdata = $xml_result->createCDATASection($folder->getCreator()->getUserNameDnAbbreviated());
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('Managers');
			$cdata = $xml_result->createCDATASection(implode(';', $owners));
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('Editors');
			$cdata = $xml_result->createCDATASection(implode(';', $editors));
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('Authors');
			$cdata = $xml_result->createCDATASection(implode(';', $authors));
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('Readers');
			$cdata = $xml_result->createCDATASection(implode(';', $readers));
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			//$access_details = $this->getUserAccessDetails($folder);
			$security_check = new Miscellaneous($this->container);
			$access_details = $security_check->getAccessLevel($folder);
			unset($security_check);
			$newnode = $xml_result->createElement('DbAccessLevel', $access_details['dbaccess']);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('DocAccessLevel', $access_details['docacess']);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('DocAccessRole', $access_details['docrole']);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('IsRecycleBin', '');
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('isDeleted', '');
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('CanCreateDocuments', $access_details['ccdocument']);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('CanCreateRevisions', $access_details['ccrevision']);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('CanDeleteDocuments', $access_details['cddocument']);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('CanSoftDeleteDocuments', $access_details['ccdocument']);
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('DefaultDocumentType', $folder->getDefaultDocType());
			$root->appendChild($newnode);
			$newnode = $xml_result->createElement('DocumentType');
			if ($folder->getDefaultDocType() || $folder->getApplicableDocType()->count() > 0) {
				if ($folder->getApplicableDocType()->count() > 0) {
					$doctypes = $folder->getApplicableDocType();
				}
				elseif ($folder->getLibrary()->getApplicableDocType()->count() > 0) {
					$doctypes = $folder->getLibrary()->getApplicableDocType();
				}
				else {
					$doctypes = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false, 'Status' => true), array('Doc_Name' => 'ASC'));					
				}
				
				if (!empty($doctypes)) {
					foreach ($doctypes as $dt) {
						$dt instanceof \Docova\DocovaBundle\Entity\DocumentTypes;
						$node = $xml_result->createElement('type');
						$child = $xml_result->createElement('name');
						$cdata = $xml_result->createCDATASection($dt->getDocName());
						$child->appendChild($cdata);
						$node->appendChild($child);
						$child = $xml_result->createElement('key', $dt->getId());
						$node->appendChild($child);
						$child = $xml_result->createElement('hasUploader', in_array('ATT', $dt->getContentSections()) || in_array('ATTI', $dt->getContentSections()) ? '1' : '');
						$node->appendChild($child);
						if (in_array('ATT', $dt->getContentSections()) || in_array('ATTI', $dt->getContentSections())) {
							$properties = $dt->getDocTypeSubform();
							foreach ($properties as $p) {
								$p instanceof \Docova\DocovaBundle\Entity\DocTypeSubforms;
								if ($p->getSubform()->getFormName() === 'Attachments') 
								{
									$properties = $p->getPropertiesXML(true);
									break;
								}
							}
						}
						$max_files = !empty($properties['MaxFiles']) ? $properties['MaxFiles'] : 0;
						$generate_thumbnail = !empty($properties['Interface']['GenerateThumbnails']) ? $properties['Interface']['GenerateThumbnails'] : '';
						$thumbnail_width = !empty($properties['Interface']['ThumbnailWidth']) ? $properties['Interface']['ThumbnailWidth'] : '';
						$thumbnail_height = !empty($properties['Interface']['ThumbnailHeight']) ? $properties['Interface']['ThumbnailHeight'] : '';
						$child = $xml_result->createElement('maxfiles', $max_files);
						$node->appendChild($child);
						$child = $xml_result->createElement('DisableQuickAdd');
						$node->appendChild($child);
						$child = $xml_result->createElement('GenerateThumbnail', $generate_thumbnail);
						$node->appendChild($child);
						$child = $xml_result->createElement('ThumbnailWidth', $thumbnail_width);
						$node->appendChild($child);
						$child = $xml_result->createElement('ThumbnailHeight', $thumbnail_height);
						$node->appendChild($child);
						$child = $xml_result->createElement('description');
						$cdata = $xml_result->createCDATASection($dt->getDescription());
						$child->appendChild($cdata);
						$node->appendChild($child);
						$newnode->appendChild($node);
						unset($node, $p, $child, $cdata, $properties, $max_files, $generate_thumbnail, $thumbnail_height, $thumbnail_width);
					}
				}
			}
			$root->appendChild($newnode);
		}
		
		$response->setContent($xml_result->saveXML());
		return $response;
	}
	
	public function canCreateRootFolderAction($library)
	{
		$allowed = '';
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library, 'Trash' => false, 'Status' => true));
		if (!empty($library)) 
		{
			$security_context = $this->container->get('security.authorization_checker');
			if (true === $security_context->isGranted('CREATE', $library)) 
			{
				$allowed = $this->user->getUserNameDnAbbreviated();
			}
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/html');
		$response->setContent('<span><div id="CanCreateRootFolders">'.$allowed.'</div></span>');
		return $response;
	}
	
	public function getDocsInFolderAction(Request $request)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$documents = array();
		$folder = $request->query->get('RestrictToCategory');
//		$start = $request->query->get('start');
		$count = $request->query->get('count');
		$em = $this->getDoctrine()->getManager();
		$docs = $em->getRepository('DocovaBundle:Documents')->findBy(array('folder' => $folder, 'Archived' => false, 'Trash' => false));
		if (!empty($docs))
		{
			$security = new Miscellaneous($this->container);
			$x = 1;
			foreach ($docs as $document)
			{
				if (true === $security->canReadDocument($document))
				{
					if ($x > $count)
						break;
					$documents[] = array(
						'data' => array(
							'DocKey' => $document->getId(),
							'Unid' => $document->getId()
						),
						'icon' => 'docova-default docova-doc',
						'id' => $document->getId(),
						'parent' => $folder,
						'state' => array('disabled' => false, 'opened' => false, 'selected' => false),
						'text' => $document->getDocTitle()
					);
					$x++;
				}
			}
		}
		else {
			$documents = array('<h2>No documents found</h2>');
		}
		$response->setContent(json_encode($documents));
		return $response;
	}

	
	
	/**
	 * Find a user info in Database, if does not exist a user object can be created according to its info through LDAP server
	 *  
	 * @param string $username
	 * @param boolean $create
	 * @param boolean $search_userid
	 * @param boolean $search_ad
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
	 * Remove unmatched authors from subfolders
	 * 
	 * @param string $authors
	 * @param string $position
	 * @return boolean
	 */
	private function removeSubfoldersAuthor($author, $position)
	{
		if (!empty($author)) 
		{
			$conn = $this->getDoctrine()->getConnection();
			$conn->beginTransaction();
			try {
				$driver = $conn->getDriver()->getName();
				if ($driver == 'pdo_mysql') 
				{
					$query = "DELETE `ACE` FROM `acl_entries` AS `ACE` INNER JOIN `acl_security_identities` AS `SI` ON (`ACE`.`security_identity_id` = `SI`.`id`) INNER JOIN `acl_object_identities` AS `ACO` ON (`ACE`.`object_identity_id` = `ACO`.`id`) 
							  WHERE `ACE`.`mask` = 2 AND `SI`.`identifier` = ? AND `ACO`.`object_identifier` IN (
								SELECT DISTINCT(`F`.`id`) FROM `tb_library_folders` AS `F` WHERE `F`.`Position` LIKE '{$position}.%')";
				}
				elseif ($driver == 'pdo_sqlsrv')
				{
					$query = "DELETE [ACE] FROM [acl_entries] AS [ACE] INNER JOIN [acl_security_identities] AS [SI] ON ([ACE].[security_identity_id] = [SI].[id]) INNER JOIN [acl_object_identities] AS [ACO] ON ([ACE].[object_identity_id] = [ACO].[id])
							  WHERE [ACE].[mask] = 2 AND [SI].[identifier] = ? AND [ACO].[object_identifier] IN (
								SELECT DISTINCT([F].[id]) FROM [tb_library_folders] AS [F] WHERE [F].[Position] LIKE '{$position}.%')";
				}
				$stmt = $conn->prepare($query);
				$stmt->bindValue(1, $author);
				$stmt->execute();
				$conn->commit();
				return true;
			}
			catch (\Exception $e) {
//				var_export($e->getMessage());
				$conn->rollback();
				return false;
			}
		}
		return false;
	}
	
	/**
	 * Recursive function to add Readers to a folder children (remove ROLE_USER for their readers and authors);
	 * 
	 * @param array $folders
	 * @param array $readers
	 * @param string $main_folder
	 * @param string $folder_id
	 */
	private function addReadersToChildren($folders, $readers, $main_folder, $folder_id, $ancestors)
	{
		$conn = $this->getDoctrine()->getConnection();
		$conn->beginTransaction();
		$readers_query = $parent_ids = '';
		foreach ($readers as $rd) {
			if (!empty($rd)) {
				$readers_query .= $conn->quote($rd, \PDO::PARAM_STR).',';
			}
		}
		$readers_query	= !empty($readers_query) ? substr_replace($readers_query, '', -1) : '';
		$driver = $conn->getDriver()->getName();
		if (!empty($ancestors) && is_array($ancestors)) 
		{
			foreach ($ancestors as $parent) {
				$parent_ids .= "'{$parent['id']}',";
			}
			$parent_ids		= !empty($parent_ids) ? substr_replace($parent_ids, '', -1) : '';
			if ($driver == 'pdo_mysql') 
			{
				$parents_query	= "SELECT DISTINCT(`ACE`.`security_identity_id`) FROM `acl_entries` AS `ACE` INNER JOIN `acl_object_identities` AS `ACO` ON (`ACE`.`object_identity_id` = `ACO`.`id`) WHERE (`ACO`.`object_identifier` IN ($parent_ids) AND `ACE`.`mask` IN (128, 64)) OR (`ACO`.`object_identifier` = ? AND `mask` = 2)";
			}
			elseif ($driver == 'pdo_sqlsrv') {
				$parents_query	= "SELECT DISTINCT [ACE].[security_identity_id] FROM [acl_entries] AS [ACE] INNER JOIN [acl_object_identities] AS [ACO] ON ([ACE].[object_identity_id] = [ACO].[id]) WHERE ([ACO].[object_identifier] IN ($parent_ids) AND [ACE].[mask] IN (128, 64)) OR ([ACO].[object_identifier] = ? AND [mask] = 2)";
			}
		}
		if ($driver == 'pdo_mysql') 
		{
			try {
				foreach ($folders as $folder)
				{
					if ($folder['id'] != $folder_id)
					{
						$query = "DELETE FROM `acl_entries` WHERE `object_identity_id` = (SELECT `id` FROM `acl_object_identities` WHERE `object_identifier` = ?) AND ((`mask` IN (128,64,2,1) AND
										`security_identity_id` NOT IN (SELECT `T`.`security_identity_id` FROM ($parents_query) `T`) AND
										`security_identity_id` NOT IN (SELECT `id` FROM `acl_security_identities` WHERE `identifier` IN ($readers_query))) OR (
										`mask` IN (1,2) AND `security_identity_id` = (SELECT `id` FROM `acl_security_identities` WHERE `identifier` = ?)))";
							
	/*
						$query = "DELETE FROM `acl_entries` WHERE (`mask` = 2 OR `mask` = 1) AND `object_identity_id` = (SELECT `id` FROM `acl_object_identities` WHERE `object_identifier` = ?) AND `security_identity_id` =
								 (SELECT `id` FROM `acl_security_identities` WHERE `identifier` = ?)";
	*/
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $folder['id']);
						$stmt->bindValue(2, $folder['Parent']);
						$stmt->bindValue(3, 'ROLE_USER');
					}
					else {
						$query = "DELETE FROM `acl_entries` WHERE `mask` = 1 AND `object_identity_id` = (SELECT `id` FROM `acl_object_identities` WHERE `object_identifier` = ?) AND `security_identity_id` =
								 (SELECT `id` FROM `acl_security_identities` WHERE `identifier` = ?)";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $folder['id']);
						$stmt->bindValue(2, 'ROLE_USER');
					}
					$stmt->execute();
					
					$acl_class = $conn->fetchArray('SELECT `id` FROM `acl_classes` WHERE `class_type` = ? LIMIT 1', array('Docova\DocovaBundle\Entity\Folders'));
					$acl_class = $acl_class[0];
					foreach ($readers as $rd)
					{
						if (!empty($rd))
						{
							$security_id = $conn->fetchArray('SELECT `id` FROM `acl_security_identities` WHERE `identifier` = ? LIMIT 1', array($rd));
							$security_id = $security_id[0];
							if (empty($security_id)) 
							{
								$isUser = false === strpos($rd, 'Docova\DocovaBundle\Entity\UserAccounts-') ? 0 : 1;
								$query = "INSERT INTO `acl_security_identities` (`identifier`, `username`) VALUES (?, $isUser);";
								$stmt = $conn->prepare($query);
								$stmt->bindValue(1, $rd);
								$stmt->execute();
								$security_id = $conn->lastInsertId('id');
							}
							$query = "INSERT INTO `acl_entries` (`mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`, `ace_order`, `class_id`, `security_identity_id`, `object_identity_id`)
									SELECT 1, 1, 'all', 0, 0, 0, '$acl_class', '$security_id', 
										(SELECT DISTINCT(`id`) FROM `acl_object_identities` WHERE `object_identifier` = '{$folder['id']}' LIMIT 1)";
							$stmt = $conn->prepare($query);
							$stmt->execute();
							
						}
					}
	/*
					if ($folder['id'] !== $folder_id) 
					{
						if (!empty($readers_query))
						{
							$query = "DELETE FROM `acl_entries` WHERE `mask` = 128 AND `object_identity_id` = (SELECT `id` FROM `acl_object_identities` WHERE `object_identifier` = ?) AND `security_identity_id` NOT IN (
									 SELECT `id` FROM `acl_security_identities` WHERE `identifier` IN ($readers_query))";
							$stmt = $conn->prepare($query);
							$stmt->bindValue(1, $folder['id']);
							$stmt->execute();
						}
		
						$query = "DELETE FROM `acl_entries` WHERE `mask` = 64 AND `object_identity_id` = (SELECT `id` FROM `acl_object_identities` WHERE `object_identifier` = ?)";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $folder['id']);
						$stmt->execute();
					}
	*/
					$query = "SET @order = -1 ; UPDATE `acl_entries` SET `ace_order` = (@order := @order + 1) WHERE `object_identity_id` = (SELECT DISTINCT(`id`) FROM `acl_object_identities` WHERE `object_identifier` = '{$folder['id']}' LIMIT 1) ORDER BY `id` DESC";
					$conn->exec($query);
					
					$query = "INSERT INTO `tb_folders_log` (`id`, `Log_Action`, `Log_Status`, `Log_Date`, `Log_Details`, `Folder_Id`, `Log_Author`) VALUES (UUID(), 4, 1, NOW(), ?, ?, ?)";
					$stmt = $conn->prepare($query);
					$stmt->bindValue(1, 'Updated folder properties from folder '. $main_folder);
					$stmt->bindValue(2, $folder['id']);
					$stmt->bindValue(3, $this->user->getId());
					$stmt->execute();
				}
				$conn->commit();
			}
			catch (\Exception $e)
			{
				var_export($e->getMessage());
				$conn->rollback();
			}
		}
		elseif ($driver == 'pdo_sqlsrv') 
		{
			try {
				foreach ($folders as $folder)
				{
					if ($folder['id'] != $folder_id)
					{
						$query = "DELETE FROM [acl_entries] WHERE [object_identity_id] = (SELECT [id] FROM [acl_object_identities] WHERE [object_identifier] = ?) AND (([mask] IN (128,64,2,1) AND
								  [security_identity_id] NOT IN (SELECT [T].[security_identity_id] FROM ($parents_query) [T]) AND
								  [security_identity_id] NOT IN (SELECT [id] FROM [acl_security_identities] WHERE [identifier] IN ($readers_query))) OR (
								  [mask] IN (1,2) AND [security_identity_id] = (SELECT [id] FROM [acl_security_identities] WHERE [identifier] = ?)))";
							
						/*
						 $query = "DELETE FROM [acl_entries] WHERE ([mask] = 2 OR [mask] = 1) AND [object_identity_id] = (SELECT [id] FROM [acl_object_identities] WHERE [object_identifier] = ?) AND [security_identity_id] =
						  (SELECT [id] FROM [acl_security_identities] WHERE [identifier] = ?)";
						 */
						 $stmt = $conn->prepare($query);
						 $stmt->bindValue(1, $folder['id']);
						 $stmt->bindValue(2, $folder['Parent']);
						 $stmt->bindValue(3, 'ROLE_USER');
					}
					else {
						$query = "DELETE FROM [acl_entries] WHERE [mask] = 1 AND [object_identity_id] = (SELECT [id] FROM [acl_object_identities] WHERE [object_identifier] = ?) AND [security_identity_id] =
								 (SELECT [id] FROM [acl_security_identities] WHERE [identifier] = ?)";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $folder['id']);
						$stmt->bindValue(2, 'ROLE_USER');
					}
					$stmt->execute();
									
					$acl_class = $conn->fetchArray('SELECT TOP 1 [id] FROM [acl_classes] WHERE [class_type] = ?', array('Docova\DocovaBundle\Entity\Folders'));
					$acl_class = $acl_class[0];
					foreach ($readers as $rd)
					{
						if (!empty($rd))
						{
							$security_id = $conn->fetchArray('SELECT [id] FROM [acl_security_identities] WHERE [identifier] = ?', array($rd));
							$security_id = $security_id[0];
							if (empty($security_id))
							{
								$isUser = false === strpos($rd, 'Docova\DocovaBundle\Entity\UserAccounts-') ? '0' : '1';
								$query = "INSERT INTO [acl_security_identities] ([identifier], [username]) VALUES (?, $isUser);";
								$stmt = $conn->prepare($query);
								$stmt->bindValue(1, $rd);
								$stmt->execute();
								$security_id = $conn->lastInsertId();
							}
							$query = "INSERT INTO [acl_entries] ([mask], [granting], [granting_strategy], [audit_success], [audit_failure], [ace_order], [class_id], [security_identity_id], [object_identity_id])
									  SELECT 1, 1, 'all', 0, 0, 0, '$acl_class', '$security_id',
									  (SELECT TOP 1 [id] FROM (SELECT DISTINCT [id] FROM [acl_object_identities] WHERE [object_identifier] = '{$folder['id']}') [SubQuery])";
							$stmt = $conn->prepare($query);
							$stmt->execute();
											
						}
					}
//  					$conn->query('DECLARE @ordering int = -1;');
//  					$query = "UPDATE [acl_entries] SET [ace_order] = (@ordering := @ordering + 1) WHERE [object_identity_id] = (SELECT TOP 1 [id] FROM (SELECT DISTINCT([id]) FROM [acl_object_identities] WHERE [object_identifier] = '{$folder['id']}') [SubQuery]) ORDER BY [id] DESC";
					$query = "UPDATE [ocl] SET [ace_order]=[Row] FROM (SELECT ROW_NUMBER() OVER(ORDER BY [id] DESC) - 1 AS [Row], [ace_order] FROM [acl_entries] WHERE [object_identity_id] = (SELECT TOP 1 [id] FROM (SELECT DISTINCT([id]) FROM [acl_object_identities] WHERE [object_identifier] = '{$folder['id']}') [SQuery])) AS [ocl]";
					$stmt = $conn->prepare($query);
					$stmt->execute();
										
					$query = "INSERT INTO [tb_folders_log] ([id], [Log_Action], [Log_Status], [Log_Date], [Log_Details], [Folder_Id], [Log_Author]) VALUES (NEWID(), 4, 1, GETDATE(), ?, ?, ?)";
					$stmt = $conn->prepare($query);
					$stmt->bindValue(1, 'Updated folder properties from folder '. $main_folder);
					$stmt->bindValue(2, $folder['id']);
					$stmt->bindValue(3, $this->user->getId());
					$stmt->execute();
				}
				$conn->commit();
			}
			catch (\Exception $e)
			{
				var_export($e->getMessage());
				$conn->rollback();
			}
		}
	}
	
	
	/**
	 * Recursive function to retrieve omitted owners of sub folders after changing the parent view mode to ROLE_USER
	 * 
	 * @param object $customACL_obj
	 * @param object $folders
	 */
	private function addFolderCreatorsAsOwner($customACL_obj, $em, $folders, $main_folder)
	{
		if (is_array($folders)) {
			
			$security_check = new Miscellaneous($this->container);
			
			foreach ($folders as $folder) {

				if ($folder->getChildren()->count() > 0) {
					$this->addFolderCreatorsAsOwner($customACL_obj, $em, $folder->getChildren(), $main_folder);
				}

				if (false === $customACL_obj->isUserGranted($folder, $folder->getCreator(), 'owner')) {
					$customACL_obj->insertObjectAce($folder, $folder->getCreator(), 'owner', false);
				}

				$security_check->createFolderLog($em, 'UPDATE', $folder, 'Updated folder properties from folder '. $main_folder);
/*					
					if (!$customACL_obj->getObjectACEUsers($folder, 'create')) {
						$customACL_obj->insertObjectAce($folder, 'ROLE_USER', 'create');
					}
*/
			}
		}
	}
	
	/**
	 * Apply sync status to subfolders
	 * 
	 * @param array $folders
	 * @param array $users
	 * @param string $main_folder
	 * @param boolean $sync_subfolders
	 * @return boolean|mixed
	 */
	private function syncSubfolders($folders, $users, $main_folder, $sync_subfolders = false)
	{
		$conn = $this->getDoctrine()->getConnection();
		$driver = $conn->getDriver()->getName();
		$conn->beginTransaction();
		try {
			$delQ = $updQ = '';
			foreach ($folders as $folder) 
			{
				if ($folder['id'] != $main_folder && !empty($sync_subfolders)) 
				{
					$updQ .= "'{$folder['id']}',";
				}
				if ($folder['id'] === $main_folder || !empty($sync_subfolders)) 
				{
					$delQ .= "'{$folder['id']}',";
				}
			}
			
			if (!empty($sync_subfolders) && !empty($updQ)) 
			{
				$updQ = substr_replace($updQ, '', -1);
				if ($driver == 'pdo_mysql') {
					$query = "UPDATE `tb_library_folders` SET `Synced_From_Parent` = 1, `Sync_Subfolders` = 0, `Sync` = 0 WHERE `id` IN ($updQ)";
				}
				elseif ($driver == 'pdo_sqlsrv') {
					$query = "UPDATE [tb_library_folders] SET [Synced_From_Parent] = 1, [Sync_Subfolders] = 0, [Sync] = 0 WHERE [id] IN ($updQ)";
				}
				$stmt = $conn->prepare($query);
				$stmt->execute();
			}
			
			if (!empty($delQ)) 
			{
				$delQ = substr_replace($delQ, '', -1);
				if ($driver == 'pdo_mysql') {
					$query = "DELETE FROM `tb_sync_users` WHERE `Folder_Id` IN ($delQ)";
				}
				elseif ($driver == 'pdo_sqlsrv') {
					$query = "DELETE FROM [tb_sync_users] WHERE [Folder_Id] IN ($delQ)";
				}
				$stmt = $conn->prepare($query);
				$stmt->execute();
				
				$insQ = '';
				foreach ($folders as $folder)
				{
					if ($folder['id'] === $main_folder || !empty($sync_subfolders)) 
					{
						foreach ($users as $uid)
						{
							$insQ .= "('{$folder['id']}', '$uid'),";
						}
					}
				}
				if (!empty($insQ)) 
				{
					$insQ = substr_replace($insQ, '', -1);
					if ($driver == 'pdo_mysql') {
						$query = "INSERT INTO `tb_sync_users` (`Folder_Id`, `User_Id`) VALUES $insQ";
					}
					elseif ($driver == 'pdo_sqlsrv') {
						$query = "INSERT INTO [tb_sync_users] ([Folder_Id], [User_Id]) VALUES $insQ";
					}
					$stmt = $conn->prepare($query);
					$stmt->execute();
				}
			}
			$conn->commit();
/*
				if ($folder->getSynchUsers()->count() > 0) 
				{
					$folder->removeAllSynchUsers();
					$em->flush();
				}
				
				$folder->setSynched(false);
				$folder->setSyncSubfolders(false);
				$folder->setSyncedFromParent(true);
				foreach ($users as $user_obj) {
					if ($user_obj instanceof UserAccounts) 
					{
						$folder->addSynchUsers($user_obj);
					}
				}
				$em->flush();
				
				$children = $folder->getChildren();
				if ($children->count() > 0) 
				{
					$this->syncSubfolders($children, $users);
				}
			}
*/
		}
		catch (\Exception $e) {
			$conn->rollback();
		}
	}
	
	/**
	 * Remove sync status and sync users from subfolders
	 * 
	 * @param array $folders
	 * @param string $main_folder
	 */
	private function removeSubfoldersSyncStatus($folders, $main_folder)
	{
		$conn = $this->getDoctrine()->getConnection();
		$driver = $conn->getDriver()->getName();
		$conn->beginTransaction('del');
		try {
			$delQ = '';
			foreach ($folders as $folder)
			{
				if ($folder['id'] != $main_folder) 
				{
					$delQ .= $folder['id'] . ',';
				}
			}
			
			if (!empty($delQ)) 
			{
				$delQ = substr_replace($delQ, '', -1);
				if ($driver == 'pdo_mysql') {
					$query = "DELETE FROM `tb_synch_users` WHERE `Folder_Id` IN ($delQ)";
				}
				elseif ($driver == 'pdo_sqlsrv') {
					$query = "DELETE FROM [tb_synch_users] WHERE [Folder_Id] IN ($delQ)";
				}
				$stmt = $conn->prepare($query);
				$stmt->execute();
				if ($driver == 'pdo_mysql') {
					$query = "UPDATE `tb_library_folders` SET `Synced_From_Parent` = 0, `Sync_Subfolders` = 0, `Sync` = 0 WHERE `id` IN ($delQ)";
				}
				elseif ($driver == 'pdo_sqlsrv') {
					$query = "UPDATE [tb_library_folders] SET [Synced_From_Parent] = 0, [Sync_Subfolders] = 0, [Sync] = 0 WHERE [id] IN ($delQ)";
				}
				$stmt = $conn->prepare($query);
				$stmt->execute();
			}
			$conn->commit('del');
		}
		catch (\Exception $e) {
			$conn->rollback('del');
		}
/*
		foreach ($folders as $folder) {
			if ($folder->getSynchUsers()->count() > 0)
			{
				$folder->removeAllSynchUsers();
			}
				
			$folder->setSynched(false);
			$folder->setSyncSubfolders(false);
			$folder->setSyncedFromParent(false);
			
			$children = $folder->getChildren();
			if ($children->count() > 0) 
			{
				$this->removeSubfoldersSyncStatus($children);
			}
		}
		$em->flush();
*/
	}
	
	/**
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param \Docova\DocovaBundle\Entity\Folders $parent_folder
	 * @param array $children
	 * @param array $propagate_fields
	 */
	private function updateSubfolderACL($em, $parent_folder, $children, $propagate_fields)
	{
		$customAcl = new CustomACL($this->container);
					
		if (in_array('Managers', $propagate_fields)) 
		{
			if (!empty($children[0]))
			{
				$managers = $customAcl->getObjectACEUsers($parent_folder, 'owner');
				$managerGroups = $customAcl->getObjectACEGroups($parent_folder, 'owner');
				if ($managers->count() || $managerGroups->count()) 
				{
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId()) 
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeMaskACEs($object, 'owner');
							if (!empty($managers)) 
							{
								foreach ($managers as $m) {
									$m = $this->findUserAndCreate($m->getUsername(), false, true, false);
									$customAcl->insertObjectAce($object, $m, 'owner', false);
								}
								$m = null;
							}
							if (!empty($managerGroups) && $managerGroups->count())
							{
								foreach ($managerGroups as $group) {
									$customAcl->insertObjectAce($object, $group->getRole(), 'owner');
								}
								$group = null;
							}
						}
					}
					$managerGroups = $managers = $object = $m = null;
				}
				else {
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId()) 
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeMaskACEs($object, 'owner');
							$object = null;
						}
					}
				}
			}
		}

		if (in_array('Editors', $propagate_fields))
		{
			if (!empty($children[0]))
			{
				$editors = $customAcl->getObjectACEUsers($parent_folder, 'master');
				$editorsGroups = $customAcl->getObjectACEGroups($parent_folder, 'master');
				if ($editors->count() || $editorsGroups->count())
				{
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId())
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeMaskACEs($object, 'master');
							if (!empty($editors))
							{
								foreach ($editors as $e) {
									$e = $this->findUserAndCreate($e->getUsername(), false, true, false);
									$customAcl->insertObjectAce($object, $e, 'master', false);
								}
								$e = null;
							}
							if (!empty($editorsGroups) && $editorsGroups->count())
							{
								foreach ($editorsGroups as $group) {
									$customAcl->insertObjectAce($object, $group->getRole(), 'master');
								}
								$group = null;
							}
						}
					}
					$editorsGroups = $editors = $object = $e = null;
				}
				else {
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId())
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeMaskACEs($object, 'owner');
							$object = null;
						}
					}
				}
			}
		}

		if (in_array('Authors', $propagate_fields))
		{
			if (!empty($children[0]))
			{
				$authors = $customAcl->getObjectACEUsers($parent_folder, 'create');
				$authorGroups = $customAcl->getObjectACEGroups($parent_folder, 'create');
				if (!empty($authors) || (!empty($authorGroups) && $authorGroups->count()))
				{
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId())
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeMaskACEs($object, 'create');
							$customAcl->removeUserACE($object, 'ROLE_USER', 'create', true);
							if (!empty($authors))
							{
								foreach ($authors as $a) {
									$a = $this->findUserAndCreate($a->getUsername(), false, true);
									$customAcl->insertObjectAce($object, $a, 'create', false);
								}
								$a = null;
							}
							if (!empty($authorGroups) && $authorGroups->count())
							{
								foreach ($authorGroups as $group) {
									$customAcl->insertObjectAce($object, $group->getRole(), 'create');
								}
								$group = null;
							}
						}
					}
					$authorGroups = $authors = $object = null;
				}
				else {
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId())
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeAllMasks($object, 'create');
							if ($customAcl->isRoleGranted($parent_folder, 'ROLE_USER', 'create')) 
							{
								$customAcl->insertObjectAce($object, 'ROLE_USER', 'create');
							}
							$object = null;
						}
					}
				}
			}
		}

		if (in_array('Readers', $propagate_fields))
		{
			if (!empty($children[0]))
			{
				$readers = $customAcl->getObjectACEUsers($parent_folder, 'view');
				$readersGroup = $customAcl->getObjectACEGroups($parent_folder, 'view');
				if ((!empty($readers) && $readers->count()) || (!empty($readersGroup) && $readersGroup->count()))
				{
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId())
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeMaskACEs($object, 'view');
							$customAcl->removeUserACE($object, 'ROLE_USER', 'view', true);
							if (!empty($readers))
							{
								foreach ($readers as $r) {
									$r = $this->findUserAndCreate($r->getUsername(), false, true);
									$customAcl->insertObjectAce($object, $r, 'view', false);
								}
								$r = null;
							}
							if (!empty($readersGroup) && $readersGroup->count())
							{
								foreach ($readersGroup as $group) {
									$customAcl->insertObjectAce($object, $group->getRole(), 'view');
								}
								$group = null;
							}
						}
					}
					$readersGroup = $readers = $object = null;
				}
				else {
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId())
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeAllMasks($object, 'view');
							if ($customAcl->isRoleGranted($parent_folder, 'ROLE_USER', 'view')) 
							{
								$customAcl->insertObjectAce($object, 'ROLE_USER', 'view');
							}
							$object = null;
						}
					}
				}
			}
		}

		if (in_array('DeleteRights', $propagate_fields))
		{
			if (!empty($children[0]))
			{
				$deletors = $customAcl->getObjectACEUsers($parent_folder, 'delete');
				$deletorGroups = $customAcl->getObjectACEGroups($parent_folder, 'delete');
				if ((!empty($deletors) && $deletors->count()) || (!empty($deletorGroups) && $deletorGroups->count()))
				{
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId())
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeMaskACEs($object, 'delete');
							$customAcl->removeUserACE($object, 'ROLE_USER', 'delete', true);
							if (!empty($deletors))
							{
								foreach ($deletors as $d) {
									$d = $this->findUserAndCreate($d->getUsername(), false, true);
									$customAcl->insertObjectAce($object, $d, 'delete', false);
								}
								$d = null;
							}
							if (!empty($deletorGroups) && $deletorGroups->count())
							{
								foreach ($deletorGroups as $group) {
									$customAcl->insertObjectAce($object, $group->getRole(), 'delete');
								}
								$group = null;
							}
						}
					}
					$deletorGroups = $deletors = $object = null;
				}
				else {
					foreach ($children as $subfolder) {
						if ($subfolder['id'] !== $parent_folder->getId())
						{
							$object = $em->getReference('DocovaBundle:Folders', $subfolder['id']);
							$customAcl->removeAllMasks($object, 'view');
							if ($customAcl->isRoleGranted($parent_folder, 'ROLE_USER', 'delete')) 
							{
								$customAcl->insertObjectAce($object, 'ROLE_USER', 'delete');
							}
							$object = null;
						}
					}
				}
			}
		}
	}

	/**
	 * Apply changes in folder properties to sub folders
	 * 
	 * @param object $em
	 * @param \Docova\DocovaBundle\Entity\Folders $parent_folder
	 * @param array $sub_folders
	 * @param array $propagate_fields
	 */
	private function updateSubfoldeProperties($em, $parent_folder, $sub_folders, $propagate_fields)
	{
		if ((is_array($sub_folders) || (is_object($sub_folders) && $sub_folders)) && !empty($propagate_fields))
		{
			foreach ($sub_folders as $folder)
			{
				if (in_array('DocumentTypeOption', $propagate_fields) || in_array('DocumentType', $propagate_fields)) 
				{
					if ($folder->getApplicableDocType()->count() > 0) 
					{
						foreach ($folder->getApplicableDocType() as $type)
						{
							$folder->removeApplicableDocType($type);
						}
						$em->flush();
						unset($type);
					}
					
					if ($parent_folder->getApplicableDocType()->count() > 0) 
					{
						foreach ($parent_folder->getApplicableDocType() as $type) {
							$folder->addApplicableDocType($type);
						}
					}
					$folder->setDefaultDocType($parent_folder->getDefaultDocType());
				}

				if (in_array('IconNormal', $propagate_fields) || in_array('IconSelected', $propagate_fields)) 
				{
					$folder->setIconNormal($parent_folder->getIconNormal());
					$folder->setIconSelected($parent_folder->getIconSelected());
				}
				
				if (in_array('AuthorsCanNotCreateFolders', $propagate_fields)) 
				{
					$folder->setDisableACF($parent_folder->getDisableACF());
				}
				
				if (in_array('AllowUnrestrictedRevisions', $propagate_fields)) 
				{
					$folder->setEnableACR($parent_folder->getEnableACR());
				}
				
				if (in_array('KeepDraftsPrivate', $propagate_fields)) 
				{
					$folder->setPrivateDraft($parent_folder->getPrivateDraft());
				}
				
				if (in_array('AuthorsCanEditDrafts', $propagate_fields)) 
				{
					$folder->setSetAAE($parent_folder->getSetAAE());
				}
				
				if (in_array('OnlyAuthorsAreReaders', $propagate_fields)) 
				{
					$folder->setRestrictRtA($parent_folder->getRestrictRTA());
					//@TODO: same as update if this value is true, update the readers
				}
				
				if (in_array('FolderPerspectives', $propagate_fields)) 
				{
					//@TODO: custom default perspectives should be saved then apply this changes
				}
				
				if (in_array('DefaultPerspective', $propagate_fields)) 
				{
					$folder->setDefaultDocType($parent_folder->getDefaultDocType());
				}
				
				if (in_array('DisableCutCopyPaste', $propagate_fields)) 
				{
					$folder->setDisableCCP($parent_folder->getDisableCCP());
				}
				
				if (in_array('UseContentPaging', $propagate_fields))
				{
					$folder->setPagingCount($parent_folder->getPagingCount());
				}
				
				if (in_array('EnableFolderFiltering', $propagate_fields)) 
				{
					$folder->setFiltering($parent_folder->getFiltering());
				}
				
				if (in_array('fltrField1', $propagate_fields)) 
				{
					$folder->setFltrField1($parent_folder->getFltrField1());
				}
							
				if (in_array('fltrField2', $propagate_fields)) 
				{
					$folder->setFltrField2($parent_folder->getFltrField2());
				}
							
				if (in_array('fltrField3', $propagate_fields)) 
				{
					$folder->setFltrField3($parent_folder->getFltrField3());
				}
							
				if (in_array('fltrField4', $propagate_fields)) 
				{
					$folder->setFltrField4($parent_folder->getFltrField4());
				}
				
				$em->flush();
				$security_check = new Miscellaneous($this->container);
				$security_check->createFolderLog($em, 'UPDATE', $folder, 'Updated folder properties from folder '. $parent_folder->getFolderName());
				unset($security_check);
				
				if ($folder->getChildren()->count() > 0) 
				{
					$this->updateSubfoldeProperties($em, $parent_folder, $folder->getChildren(), $propagate_fields);
				}
			}
		}
	}
	
	/**
	 * Generate proper XML for listed documents/folders in view
	 * 
	 * @param array $nodes
	 * @param array $xml_fields
	 * @param array $documents
	 * @param \DOMDocument $xml_doc
	 */
	public function generateXMLViewData($nodes, $xml_fields, $documents, $library, &$xml_doc)
	{
		if (!empty($documents[0]) && count($documents) > 0) 
		{
			$len = count($documents);
			$em = $this->getDoctrine()->getManager();
			$security_check = new Miscellaneous($this->container);
			$twig = $this->container->get('twig');
			for ($x = 0; $x < $len; $x++)
			{
				$document = key_exists('Doc_Version', $documents[$x]) ? $em->getReference('DocovaBundle:Documents', $documents[$x]['id']) : $em->getReference('DocovaBundle:Folders', $documents[$x]['id']); 
				if ((key_exists('Doc_Version', $documents[$x]) && true === $security_check->canReadDocument($document, true)) || (!key_exists('Doc_Version', $documents[$x]) && true === $security_check->isFolderVisible($document)))
				{
					$document = null; unset($document);
					$xml_doc .= "<document><docKey>DK{$documents[$x]['id']}</docKey><docid>{$documents[$x]['id']}</docid><libnsf>{$this->generateUrl('docova_homepage')}</libnsf><libid>{$library}</libid>";
					if (key_exists('Doc_Version', $documents[$x])) 
					{
						$xml_doc .= '<rectype>doc</rectype>';
						if (key_exists('attachments', $nodes))
						{
							$attachments = $em->getRepository('DocovaBundle:AttachmentsDetails')->getDocumentAttachmentsArray($documents[$x]['id']);
							if (!empty($attachments))
							{
								$alen = count($attachments);
								for ($c = 0; $c < $alen; $c++)
								{
									$date = !empty($attachments[$c]['File_Date']) ? $attachments[$c]['File_Date'] : null;
									$y = (!empty($date)) ? $date->format('Y') : $date;
									$m = (!empty($date)) ? $date->format('m') : $date;
									$d = (!empty($date)) ? $date->format('d') : $date;
									$w = (!empty($date)) ? $date->format('w') : $date;
									$h = (!empty($date)) ? $date->format('H') : $date;
									$mn = (!empty($date)) ? $date->format('i') : $date;
									$s = (!empty($date)) ? $date->format('s') : $date;
									$val = (!empty($date)) ? strtotime($date->format('Y-m-d H:i:s')) : $date;
									$od = (!empty($date)) ? $date->format('m/d/Y') : '';
									$xml_doc .= "<FILES Y='$y' M='$m' D='$d' W='$w' H='$h' MN='$mn' S='$s' val='$val' origDate='$od'>";
									$y = $m = $d = $w = $h = $mn = $s = $val = $od = null;
				
									$xml_doc .= "<src><![CDATA[{$this->get('router')->generate('docova_opendocfile', array('file_name' => $attachments[$c]['File_Name']))}?OpenElement&doc_id={$documents[$x]['id']}]]></src>";
									$xml_doc .= "<name><![CDATA[{$attachments[$c]['File_Name']}]]></name>";
									$xml_doc .= "<author><![CDATA[{$attachments[$c]['Author']['userNameDnAbbreviated']}]]></author></FILES>";
									}
									$attachments = $alen = $c = null;
							}
						}
					}
					else {
						$xml_doc .= '<rectype>fld</rectype>';
					}
					
					$output_nodes = array();
					foreach ($xml_fields as $column)
					{
						if (key_exists($column['Field_Name'], $documents[$x]))
						{
							$output_nodes[] = array(
								'value' => $documents[$x][$column['Field_Name']],
								'type' => $column['Data_Type'],
								'node' => $column['XML_Name']
							);
						}
						elseif ($column['Field_Name'] == 'Bookmarks') {
							$output_nodes[] = array(
								'value' => '',
								'type' => $column['Data_Type'],
								'node' => $column['XML_Name']
							);
						}
						elseif (strpos($column['Field_Name'], '{{') !== false || strpos($column['Field_Name'], '{%') !== false)
						{
							if (key_exists('Doc_Version', $documents[$x])) {
								$template = $twig->createTemplate('{{ f_SetUser(user) }}{{ f_SetApplication(library) }}{% docovascript "output:string" %} '.$column['Field_Name'].'{% enddocovascript %}');
								$value = $template->render(array(
									'user' => $this->user,
									'document' => $documents[$x],
									'library' => $em->getReference('DocovaBundle:Libraries', $library)
								));
							}
							else {
								$template = $twig->createTemplate('{{ f_SetUser(user) }}{% docovascript "output:string" %} '.$column['Field_Name'].'{% enddocovascript %}');
								$value = $template->render(array(
									'user' => $this->user,
									'folder' => $documents[$x],
									'library' => $em->getReference('DocovaBundle:Libraries', $library)
								));
							}
							$output_nodes[] = array(
								'value' => trim($value),
								'type' => $column['Data_Type'],
								'node' => $column['XML_Name']
							);
						}
					}
					
					if (!empty($output_nodes))
					{
						$olen = count($output_nodes);
						for ($c = 0; $c < $olen; $c++)
						{
							if ($output_nodes[$c]['type'] == 2)
							{
								$date = null;
								$value = explode(';', $output_nodes[$c]['value']);
								if (count($value) > 1) {
									$xml_doc .= "<{$output_nodes[$c]['node']}><![CDATA[{$output_nodes[$c]['value']}]]></{$output_nodes[$c]['node']}>";
								}
								else {
									if (!empty($value[0]))
									{
										$date = new \DateTime();
										if (is_string($value[0])) {
											$value[0] = str_replace('/', '-', $value[0]);
											$value[0] = strtotime($value[0]);
											$date->setTimestamp($value[0]);
										}
										elseif ($value[0] instanceof \DateTime) {
											$date = $value[0];
										}
										else {
											$date = null;
										}
									}
									$date_value = !empty($date) ? $date->format('m/d/Y') : $date;
									$y = (!empty($date)) ? $date->format('Y') : $date;
									$m = (!empty($date)) ? $date->format('m') : $date;
									$d = (!empty($date)) ? $date->format('d') : $date;
									$w = (!empty($date)) ? $date->format('w') : $date;
									$h = (!empty($date)) ? $date->format('H') : $date;
									$mn = (!empty($date)) ? $date->format('i') : $date;
									$s = (!empty($date)) ? $date->format('s') : $date;
									$val = !empty($date) ? strtotime($date->format('Y/m/d H:i:s')) : '';
									$xml_doc .= "<{$output_nodes[$c]['node']} Y='$y' M='$m' D='$d' W='$w' H='$h' MN='$mn' S='$s' val='$val'><![CDATA[$date_value]]></{$output_nodes[$c]['node']}>";
									$y = $m = $d = $w = $h = $mn = $s = $val = $date_value = null;
								}
							}
							elseif ($output_nodes[$c]['type'] == 4) {
								if ($output_nodes[$c]['node'] == 'bmk') {
									if ($output_nodes[$c]['value']) {
										$xml_doc .= "<{$output_nodes[$c]['node']}><img width='10' height='10' src='{$output_nodes[$c]['value']}' alt='bookmark' ></img></{$output_nodes[$c]['node']}>";
									}
									else {
										$xml_doc .= "<{$output_nodes[$c]['node']}></{$output_nodes[$c]['node']}>";
									}
								}
								else {
									$xml_doc .= "<{$output_nodes[$c]['node']}>{$output_nodes[$c]['value']}</{$output_nodes[$c]['node']}>";
								}
							}
							else {
								$xml_doc .= "<{$output_nodes[$c]['node']}><![CDATA[{$output_nodes[$c]['value']}]]></{$output_nodes[$c]['node']}>";
							}
						}
					}

					$xml_doc .= '</document>';
				}
			}
			$security_check = null;
		}
	}
	
	public function openFolderImportDialogAction(Request $request)
	{
		$folder = $request->query->get('ParentUNID');
		$em = $this->getDoctrine()->getManager();
		$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder, 'Del' => false, 'Inactive' => false));
		$doctypes = array();
		if ($folder->getDefaultDocType())
		{
			if ($folder->getDefaultDocType() != -1 && $folder->getApplicableDocType()->count())
			{
				$doctypes = $folder->getApplicableDocType();
			}
			elseif ($folder->getDefaultDocType() == -1 && $folder->getLibrary()->getApplicableDocType()->count()) {
				$doctypes = $folder->getLibrary()->getApplicableDocType();
			}
			elseif ($folder->getDefaultDocType() == -1 || $folder->getDefaultDocType()) {
				$doctypes = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false, 'Status' => true));
			}
		}
		if (empty($folder)) 
		{
			throw $this->createNotFoundException('Unspecified folder source ID.');
		}
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgFolderImport.html.twig', array(
			'folder' => $folder,
			'user' => $this->user,
			'settings' => $this->global_settings,
			'doctypes' => $doctypes
		));
	}
	
	/**
	 * Get document all authors
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @return string
	 */
	private function getDocumentAuthors($document)
	{
		$custom_acl = new CustomACL($this->container);
		$authors = array();
		$users_list = $custom_acl->getObjectACEUsers($document->getFolder(), 'owner');
		if ($users_list->count() > 0)
		{
			foreach ($users_list as $user) {
				$authors[] = $user->getUsername();
			}
		}
		$users_list = $custom_acl->getObjectACEUsers($document->getFolder(), 'master');
		if ($users_list->count() > 0)
		{
			foreach ($users_list as $user) {
				$authors[] = $user->getUsername();
			}
		}
		
		$parent_folder = $document->getFolder();
		while ($parent_folder) {
			$users_list = $custom_acl->getObjectACEUsers($parent_folder, 'create');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user)
				{
					$authors[] = $user->getUsername();
				}
			}
				
			$parent_folder = $parent_folder->getParentFolder();
		}
		unset($users_list, $user, $parent_folder);
			
		$user_list = $custom_acl->getObjectACEUsers($document, 'edit');
		if ($user_list->count() > 0) {
			for ($x = 0; $x < $user_list->count(); $x++) {
				$authors[] = $user_list[$x]->getUsername();
			}
		}
		
		$authors = array_unique($authors);
		if (!empty($authors) && count($authors) > 0) 
		{
			foreach ($authors as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true))
				{
					$authors[$index] = $user->getUserNameDnAbbreviated();
				}
			}
		}
		
		return implode(';', $authors);
	}

	/**
	 * Fetch group if exists.
	 * If getMembers is set to true it will return all group members and if findme is set, it will search for the user group members
	 *
	 * @param string $grouname
	 * @return mixed|\Docova\DocovaBundle\Entity\UserRoles
	 */
	private function fetchGroup($grouname, $getMembers = false, $findme = null)
	{
		$em = $this->getDoctrine()->getManager();
		$type = false === strpos($grouname, '/DOCOVA') ? true : false;
		$name = str_replace('/DOCOVA', '', $grouname);
		$role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $name, 'groupType' => $type));
		if (!empty($role)) {
			if (empty($getMembers) && empty($findme))
			{
				return $role;
			}
			if ($getMembers === true && empty($findme))
			{
				return $role->getRoleUsers();
			}
			else {
				if ($role->getRoleUsers()->count() > 0) {
					foreach ($role->getRoleUsers() as $user) {
						if ($user->getUserNameDnAbbreviated() === $findme->getUserNameDnAbbreviated())
						{
							return true;
						}
					}
				}
				return false;
			}
		}
	
		return false;
	}
	
	/**
	 * Get the role(group) object base on role name
	 * 
	 * @param string $rolename
	 * @return \Docova\DocovaBundle\Entity\UserRoles|boolean
	 */
	private function retrieveGroupByRole($rolename)
	{
		$em = $this->getDoctrine()->getManager();
		$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $rolename));
		if (!empty($group)) 
		{
			return $group;
		}
		
		return false;
	}
	
	/**
	 * Sort the multidientional array of folder alphabatically and regenerates their position
	 * 
	 * @param array $folders
	 * @return array
	 */
	private function sortAlphabetically($folders, $parent = null)
	{
		$tree = array();
		$tree = $this->buildTree($folders, $parent);
		if (!empty($tree))
		{
			$count = 0;
			while ($count < count($tree))
			{
				$folders = array_values($folders);
				if (true === $this->hasChild($folders, $tree[$count])) {
					$branches = $this->buildTree($folders, $tree[$count]['id'], $tree[$count]['Position']);
					if (!empty($branches)) 
					{
						array_splice($tree, $count+1, 0, $branches);
					}
					unset($branches);
				}
				$count++;
			}
		}
		return $tree;
	}
	
	/**
	 * Build sorted tree array for each level
	 * 
	 * @param array $folders
	 * @param string $parentId
	 * @param string $level
	 * @return array
	 */
	private function buildTree(&$folders, $parentId = '', $level = '')
	{
		$output = array();
		$len = count($folders);
		$x = 0;
		while ($x < $len)
		{
			if ($folders[$x]['Parent_Id'] == $parentId) {
				if (empty($output)) {
					$output[] = $folders[$x];
					unset($folders[$x]);
				}
				else {
					$inserted = false;
					for ($k = 0; $k < count($output); $k++) {
						if (strcasecmp($folders[$x]['Folder_Name'], $output[$k]['Folder_Name']) < 0) {
							array_splice($output, $k, 0, array($folders[$x]));
							$inserted = true;
							break;
						}
					}
					unset($k);
					
					if ($inserted === false) 
					{
						$output[] = $folders[$x];
					}
					unset($folders[$x]);
				}
			}
			$x++;
		}
		unset($len, $x, $parentId, $inserted);
		
		if (!empty($output)) 
		{
			$level = !empty($level) ? "$level." : $level;
			$len = count($output);
			for ($x = 0; $x < $len; $x++) {
				$output[$x]['Position'] = $level . ($x + 1);
			}
		}
		unset($level, $x, $len);
		return $output;
	}
	
	/**
	 * Check if a node has any child
	 * 
	 * @param array $folders
	 * @param array $node
	 * @return boolean
	 */
	private function hasChild($folders, $node)
	{
		$len = count($folders);
		for ($x = 0; $x < $len; $x++)
		{
			if ($folders[$x]['Parent_Id'] == $node['id']) {
				return true;
			}
		}
		unset($len, $x, $folders, $node);
		return false;
	}
}
