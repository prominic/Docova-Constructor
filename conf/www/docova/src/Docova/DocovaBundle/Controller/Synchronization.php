<?php

namespace Docova\DocovaBundle\Controller;

use \Docova\DocovaBundle\Entity\Folders;
use \Docova\DocovaBundle\Security\User\CustomACL;

/**
 * @author javad rahimi
 *        
 */
class Synchronization 
{
	protected $user;
	protected $container;
	protected $security_token;
	protected $security_checker;
	static private $synced_folders = array();

	public function __construct($container, $user = null)
	{
		$this->container = $container;
		$this->security_token = $container->get('security.token_storage');
		$this->security_checker = $container->get('security.authorization_checker');
		if (empty($user)) 
		{
			$user = $this->security_token->getToken()->getUser();
		}
		$this->user = $user;
	}

	/**
	 * Get all synced folders/subfolders for current user in a library categorised array
	 * 
	 * @return mixed
	 */
	public function getSyncedFolders()
	{
		$user = $this->security_token->getToken()->getUser();
		$em = $this->container->get('doctrine')->getManager();
		$folders = $em->getRepository('DocovaBundle:Folders')->getSyncedFoldres($user->getId());
		return $this->buildArray($folders);
	}
	
	/**
	 * Generate proper XML for the synced folders/subfolders array
	 * 
	 * @param array $categorized_folders
	 * @return \DOMDocument
	 */
	public function produceSyncXML($categorized_folders)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml->appendChild($xml->createElement('Results'));
		foreach ($categorized_folders as $library)
		{
			$node = $xml->createElement('library');
			$attr = $xml->createAttribute('name');
			$attr->value = $library['name'];
			$node->appendChild($attr);
			$attr = $xml->createAttribute('Y');
			$attr->value = date('Y', strtotime($library['modifiedDate']));
			$node->appendChild($attr);
			$attr = $xml->createAttribute('M');
			$attr->value = date('m', strtotime($library['modifiedDate']));
			$node->appendChild($attr);
			$attr = $xml->createAttribute('D');
			$attr->value = date('d', strtotime($library['modifiedDate']));
			$node->appendChild($attr);
			$attr = $xml->createAttribute('H');
			$attr->value = date('H', strtotime($library['modifiedDate']));
			$node->appendChild($attr);
			$attr = $xml->createAttribute('MN');
			$attr->value = date('i', strtotime($library['modifiedDate']));
			$node->appendChild($attr);
			$attr = $xml->createAttribute('S');
			$attr->value = date('s', strtotime($library['modifiedDate']));
			$node->appendChild($attr);
			$attr = $xml->createAttribute('path');
			$attr->value = substr($this->container->get('router')->generate('docova_homepage'), 0, -1);
			$node->appendChild($attr);
			$attr = $xml->createAttribute('unid');
			$attr->value = $library['unid'];
			$node->appendChild($attr);
			$attr = $xml->createAttribute('docKey');
			$attr->value = $library['unid'];
			$node->appendChild($attr);
			$attr = $xml->createAttribute('access');
			$attr->value = 0;
			$node->appendChild($attr);
			$root->appendChild($node);

			foreach ($library['folders'] as $folder) 
			{
				$folder_xml = $this->generateFoldersXML($folder, $folder->getSyncSubfolders());
				if ($folder->getParentFolder() && in_array($folder->getParentFolder()->getId(), self::$synced_folders)) 
				{
					foreach ($xml->getElementsByTagName('folder') as $fnode) {
						if ($fnode->getElementsByTagName('name')->item(0)->nodeValue === $folder->getParentFolder()->getFolderName()) 
						{
							$fnode->appendChild($xml->importNode($folder_xml->getElementsByTagName('folder')->item(0), true));
							break;
						}
					}
				}
				else {
					$node->appendChild($xml->importNode($folder_xml->getElementsByTagName('folder')->item(0), true));
				}
			}
		}
		
		return $xml;
	}
	
	/**
	 * Simple file sharing services
	 * 
	 * @param string $content
	 * @param string $string
	 * @return \DOMDocument|string
	 */
	public function fileShareServices($content, $string = false)
	{
		$request = new \DOMDocument('1.0', 'UTF-8');
		$request->loadXML($content);
		$action = $request->getElementsByTagName('Action')->item(0)->nodeValue;
		if (method_exists($this, 'service'.$action)) 
		{
			$result = call_user_func(array($this, 'service'.$action), $request, $string);
		}
		
		return $result;
	}
	
	/**
	 * Categorize the synced folders by library in an array structure
	 * 
	 * @param array $folders
	 * @return array
	 */
	private function buildArray($folders)
	{
		$output = array();
		if (!empty($folders))
		{
			$security = new Miscellaneous($this->container);
			foreach ($folders as $fld)
			{
				if ($security->isLibrarySubscribed($fld->getLibrary()) && !array_key_exists($fld->getLibrary()->getLibraryTitle(), $output)); 
				{
					$output[$fld->getLibrary()->getLibraryTitle()] = array(
						'name' => $fld->getLibrary()->getLibraryTitle(),
						'modifiedDate' => ($fld->getLibrary()->getDateUpdated() ? $fld->getLibrary()->getDateUpdated()->format('m/d/Y H:i:s') : $fld->getLibrary()->getDateCreated()->format('m/d/Y H:i:s')),
						'unid' => $fld->getLibrary()->getId(),
						'folders' => array()
					);
				}
			}
			
			foreach ($folders as $fld) 
			{
				if (array_key_exists($fld->getLibrary()->getLibraryTitle(), $output))
				{
					$output[$fld->getLibrary()->getLibraryTitle()]['folders'][] = $fld;
				}
			}
		}

		return $output;
	}
	
	/**
	 * Generate proper XML for synced folders/subfolders
	 * 
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @param boolean $sync_subfolders
	 * @return \DOMDocument
	 */
	private function generateFoldersXML($folder, $sync_subfolders)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$access_level = $this->getUserAccessDetails($folder);
		$thmb = '0;;';
		$em = $this->container->get('doctrine')->getManager();
		$default_doctype = $folder->getDefaultDocType();
		if ($default_doctype && $default_doctype != -1) 
		{
			$properties = $em->getRepository('DocovaBundle:DocumentTypes')->containsSubform($default_doctype, 'Attachments');
			if (!empty($properties)) 
			{
				$temp = new \DOMDocument('1.0', 'UTF-8');
				$temp->loadXML($properties);
				$thmb = $temp->getElementsByTagName('GenerateThumbnails')->item(0)->nodeValue . ';';
				$thmb .= $temp->getElementsByTagName('ThumbnailWidth')->item(0)->nodeValue . ';';
				$thmb .= $temp->getElementsByTagName('ThumbnailHeight')->item(0)->nodeValue;
				unset($properties, $temp);
			}
		}
		else {
			$default_doctype = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => 'File Document', 'Trash' => false));
			$default_doctype = $default_doctype->getId();
		}
		$attributes = array(
			'access' => $access_level['docaccess'],
			'canCreateDocs' => $access_level['cancreate'],
			'defdoctype' => $default_doctype,
			'thmb' => $thmb
		);
		
		if ($folder->getSynched() === true && $folder->getSyncedFromParent() === false && $parent_folder = $folder->getParentfolder() && !in_array($folder->getParentfolder()->getId(), self::$synced_folders))
		{
			$root = $this->generateAncestorsXML($xml, $parent_folder);
			$synced_folder = $this->createFolderNode($xml, $folder, $attributes);
			$root->appendChild($synced_folder);
		}
		else {
			$synced_folder = $this->createFolderNode($xml, $folder, $attributes);
			$xml->appendChild($synced_folder);
			self::$synced_folders[] = $folder->getId();
		}
		
		$documents = $em->getRepository('DocovaBundle:AttachmentsDetails')->getFolderAttachments($folder->getId());
		if (!empty($documents)) 
		{
			$security = new Miscellaneous($this->container);
			foreach ($documents as $file)
			{
				$file instanceof \Docova\DocovaBundle\Entity\AttachmentsDetails;
				if (true === $security->canReadDocument($file->getDocument(), true)); 
				{
					$node = $xml->createElement('file');
					$newnode = $xml->createElement('unid', $file->getDocument()->getId());
					$node->appendChild($newnode);
					$cdata = $xml->createCDATASection($file->getFileName());
					$newnode = $xml->createElement('name');
					$newnode->appendChild($cdata);
					$node->appendChild($newnode);
					$cdata = $xml->createCDATASection($file->getFileDate()->format('m/d/Y H:i:s A'));
					$newnode = $xml->createElement('date');
					$newnode->appendChild($cdata);
					$attr = $xml->createAttribute('Y');
					$attr->value = $file->getFileDate()->format('Y');
					$newnode->appendChild($attr);
					$attr = $xml->createAttribute('M');
					$attr->value = $file->getFileDate()->format('m');
					$newnode->appendChild($attr);
					$attr = $xml->createAttribute('D');
					$attr->value = $file->getFileDate()->format('d');
					$newnode->appendChild($attr);
					$attr = $xml->createAttribute('H');
					$attr->value = $file->getFileDate()->format('H');
					$newnode->appendChild($attr);
					$attr = $xml->createAttribute('MN');
					$attr->value = $file->getFileDate()->format('i');
					$newnode->appendChild($attr);
					$attr = $xml->createAttribute('S');
					$attr->value = $file->getFileDate()->format('s');
					$newnode->appendChild($attr);
					$node->appendChild($newnode);
					$newnode = $xml->createElement('workflow', ($file->getDocument()->getDocSteps()->count() > 0 ? 1 : 0));
					$node->appendChild($newnode);
					$newnode = $xml->createElement('size', $file->getFileSize());
					$node->appendChild($newnode);
					$docaccess = $security->canEditDocument($file->getDocument(), true) ? ($access_level['docaccess'] > 0 ? $access_level['docaccess'] : 2) : 0;
					$newnode = $xml->createElement('access', $docaccess);
					$node->appendChild($newnode);
					$synced_folder->appendChild($node);
					unset($node, $attr, $cdata, $newnode);
				}
			}
		}
			
		if ($sync_subfolders === true && ($children = $folder->getChildren()) && $children->count() > 0) 
		{
			foreach ($children as $subfolder) 
			{
				if ($subfolder->getSyncedFromParent()) 
				{
					$subxml = $this->generateFoldersXML($subfolder, $sync_subfolders);
					$synced_folder->appendChild($xml->importNode($subxml->getElementsByTagName('folder')->item(0), true));
				}
			}
		}
		return $xml;
	}
	
	/**
	 * Generate proper XML for the ancestor folders
	 * 
	 * @param \DOMDocument $xml
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @return \DOMElement
	 */
	private function generateAncestorsXML(&$xml, $folder)
	{
		if ($folder->getParentFolder()) 
		{
			$append_to = $this->generateAncestorsXML($xml, $folder->getParentfolder());
		}
		
		$attributes = array(
			'access' => 0,
			'canCreateDocs' => 0,
			'defdoctype' => null,
			'thmb' => '0;;'
		);
		$root = $this->createFolderNode($xml, $folder, $attributes);
		if (!empty($append_to)) 
		{
			$append_to->appendChild($root);
		}
		else {
			$xml->appendChild($root);
		}
		return $root;
	}
	
	/**
	 * Create XML node for the folder
	 * 
	 * @param \DOMDocument $xml
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @param array $attributes
	 * @return \DOMElement
	 */
	private function createFolderNode(&$xml, $folder, $attributes)
	{
		$root = $xml->createElement('folder');
		$date = $folder->getDateUpdated() ? $folder->getDateUpdated() : $folder->getDateCreated();
		$attr = $xml->createAttribute('Y');
		$attr->value = $date->format('Y');
		$root->appendChild($attr);
		$attr = $xml->createAttribute('M');
		$attr->value = $date->format('m');
		$root->appendChild($attr);
		$attr = $xml->createAttribute('D');
		$attr->value = $date->format('d');
		$root->appendChild($attr);
		$attr = $xml->createAttribute('H');
		$attr->value = $date->format('h');
		$root->appendChild($attr);
		$attr = $xml->createAttribute('MN');
		$attr->value = $date->format('i');
		$root->appendChild($attr);
		$attr = $xml->createAttribute('S');
		$attr->value = $date->format('s');
		$root->appendChild($attr);
		$attr = $xml->createAttribute('unid');
		$attr->value = $folder->getId();
		$root->appendChild($attr);
		$attr = $xml->createAttribute('access');
		$attr->value = $attributes['access'];
		$root->appendChild($attr);
		$attr = $xml->createAttribute('canCreateDocs');
		$attr->value = $attributes['canCreateDocs'];
		$root->appendChild($attr);
		$attr = $xml->createAttribute('defdoctype');
		$attr->value = $attributes['defdoctype'] ? $attributes['defdoctype'] : '';
		$root->appendChild($attr);
		$attr = $xml->createAttribute('thmb');
		$attr->value = $attributes['thmb'];
		$root->appendChild($attr);
		$newnode = $xml->createElement('name');
		$cdata = $xml->createCDATASection($folder->getFolderName());
		$newnode->appendChild($cdata);
		$root->appendChild($newnode);
		return $root;
	}

	/**
	 * Get current user access level details
	 * 
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @return array
	 */
	private function getUserAccessDetails($folder)
	{
		$access_levels = array('dbaccess' => 0, 'docaccess' => 0);
		$security_check = new Miscellaneous($this->container);
		if ($this->security_checker->isGranted('ROLE_ADMIN') || $this->security_checker->isGranted('MASTER', $folder->getLibrary())) {
			$access_levels['dbaccess'] = 6;
		}
		elseif (false === $security_check->canCreateDocument($folder)) {
			$access_levels['dbaccess'] = 2;
		}
		elseif ($this->security_checker->isGranted('ROLE_USER')) {
			$access_levels['dbaccess'] = 3;
		}
		
		if (true === $security_check->isFolderManagers($folder) || $access_levels['dbaccess'] === 6) {
			$access_levels['docaccess'] = 6;
			$access_levels['docrole'] = 'Manager';
		}
		
		if (empty($access_levels['docaccess'])) {
			if (true === $security_check->canCreateDocument($folder)) {
				$access_levels['docaccess'] = 3;
				$access_levels['docrole'] = 'Author';
			}
			else {
				$access_levels['docaccess'] = 2;
				$access_levels['docrole'] = 'Reader';
			}
		}
			
		$access_levels['cancreate'] = $security_check->canCreateDocument($folder);
		$access_levels['candelete'] = $security_check->canDeleteDocument($folder);
		
		return $access_levels;
	}
	
	/**
	 * Get document lock status
	 * 
	 * @param \DOMDocument $request
	 * @param boolean $string
	 * @return \DOMDocument|string
	 */
	private function serviceLockStatus($request, $string)
	{
		$em = $this->container->get('doctrine')->getManager();
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml->appendChild($xml->createElement('Results'));
		$document = trim($request->getElementsByTagName('Unid')->item(0)->nodeValue);
		$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Archived' => false));
		if (!empty($document)) 
		{
			$newnode = $xml->createElement('IsDocLocked', $document->getLocked() ? '1' : '0');
		}
		else {
			$newnode = $xml->createElement('Result', 'FAILED');
			$attr = $xml->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
		}
		$root->appendChild($newnode);
		if ($string === true) 
		{
			return $xml->saveXML();
		}
		return $xml;
	}
	
	/**
	 * Rename folder service through simple file sharing
	 * 
	 * @param \DOMDocument $request
	 * @param boolean $string
	 * @return string|\DOMDocument
	 */
	private function serviceRenameFolder($request, $string)
	{
		$em = $this->container->get('doctrine')->getManager();
		$folder	= $request->getElementsByTagName('Unid')->item(0)->nodeValue;
		$folder_name = $request->getElementsByTagName('FolderName')->item(0)->nodeValue;
		
		try {
			if (!empty($folder_name))
			{
				$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder, 'Del' => false, 'Inactive' => 0));
				$previous_name = $folder->getFolderName();
				$security_check = new Miscellaneous($this->container);
				$granted = $this->security_checker->isGranted('ROLE_ADMIN') ? true : $security_check->isFolderManagers($folder);
				if ($granted === true) {
					$folder->setFolderName($folder_name);
					$em->flush();
					$security_check->createFolderLog($em, 'UPDATE', $folder, "Renamed folder from: $previous_name to: $folder_name through Simple File Sharing.");
				}
				unset($security_check);
				$xml = $this->generateOKResultXML();
			}
			else {
				throw new \Exception('Folder name cannot be empty.');
			}
		}
		catch (\Exception $e) {
			$xml = $this->generatFailedXML($e->getMessage());
		}

		if ($string === true) 
		{
			return $xml->saveXML();
		}
		return $xml;
	}
	
	/**
	 * Create folder service
	 * 
	 * @param \DOMDocument $request
	 * @param boolean $string
	 * @return string|\DOMDocument
	 */
	private function serviceCreateFolder($request, $string)
	{
		$em = $this->container->get('doctrine')->getManager();
		$parent_folder	= $request->getElementsByTagName('ParentUnid')->item(0)->nodeValue;
		$new_folder	= $request->getElementsByTagName('name')->item(0)->nodeValue;
		
		try {
			if (empty($new_folder)) 
			{
				throw new \Exception('Folder name cannot be empty');
			}
			
			$parent_folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $parent_folder, 'Del' => false, 'Inactive' => 0));
			if (empty($parent_folder)) 
			{
				throw new \Exception('Unspecified source parent folder.');
			}
			
			if (true === $em->getRepository('DocovaBundle:Folders')->folderExists($parent_folder->getId(), $new_folder)) 
			{
				throw new \Exception('Another folder with this name already exists.');
			}
			
			$security_check = new Miscellaneous($this->container);
			if ($security_check->canCreateFolder($parent_folder)) 
			{
				$folder = new Folders();
				$folder->setParentfolder($parent_folder);
				$folder->setLibrary($parent_folder->getLibrary());
				$folder->setFolderName($new_folder);
				$folder->setDateCreated(new \DateTime());
				$folder->setCreator($this->user);
				$folder->setPosition($em);
				$folder->setDefaultDocType('-1');
				$perspective = $em->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'System Default', 'Is_System' => true));
				$folder->setDefaultPerspective($perspective);
				if ($parent_folder->getSyncedFromParent() || ($parent_folder->getSynched() && $parent_folder->getSyncSubfolders())) 
				{
					$folder->setSyncedFromParent(true);
					foreach ($parent_folder->getSynchUsers() as $su)
					{
						$folder->addSynchUsers($su);
					}
				}
				else {
					$folder->setSynched(true);
					$folder->addSynchUsers($this->user);
				}
				if ($parent_folder->getApplicableDocType()->count() > 0)
				{
					foreach ($parent_folder->getApplicableDocType() as $dt) {
						$folder->addApplicableDocType($dt);
					}
				}
				$em->persist($folder);
				$em->flush();
				
				$customACL_obj = new CustomACL($this->container);
				$res = $customACL_obj->insertObjectAce($folder, 'ROLE_USER', array('create', 'delete'));
				$res = $customACL_obj->insertObjectAce($folder, $this->user, 'owner', false);
				$security_check->createFolderLog($em, 'CREATE', $folder, $this->user->getUserNameDnAbbreviated() . ' Created folder ' . $new_folder . ' through Simple File Sharing.');
				$parent_readers = $customACL_obj->getObjectACEUsers($parent_folder, 'view');
				if ($parent_readers->count() > 0) 
				{
					foreach ($parent_readers as $username) {
						if ($user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $username, 'Trash' => false))) 
						{
							$customACL_obj->insertObjectAce($folder, $user, 'view', false);
						}
					}
					unset($user);
				}
				else {
					$customACL_obj->insertObjectAce($folder, 'ROLE_USER', array('view'));
				}
				$parent_managers = $customACL_obj->getObjectACEUsers($parent_folder, 'master');
				if ($parent_managers->count() > 0) 
				{
					foreach ($parent_managers as $username) {
						if ($user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $username, 'Trash' => false))) 
						{
							$customACL_obj->insertObjectAce($folder, $user, 'master', false);
						}
					}
					unset($user);
				}
				
				$xml = $this->generateOKResultXML(array('Unid' => $folder->getId()));
			}
			else {
				throw new \Exception('Insufficient access to create folder.');
			}
		}
		catch (\Exception $e) {
			//var_dump($e);
			$xml = $this->generatFailedXML($e->getMessage());
		}
		
		if ($string === true) 
		{
			return $xml->saveXML();
		}
		return $xml;
	}
	
	/**
	 * Generate OK status XML
	 * 
	 * @param array $return_nodes
	 * @return \DOMDocument
	 */
	private function generateOKResultXML($return_nodes = array())
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml->appendChild($xml->createElement('Results'));
		$newnode = $xml->createElement('Result', 'OK');
		$attr = $xml->createAttribute('ID');
		$attr->value = 'Status';
		$newnode->appendChild($attr);
		$root->appendChild($newnode);
		if (!empty($return_nodes)) 
		{
			foreach ($return_nodes as $key => $value)
			{
				$newnode = $xml->createElement($key);
				$cdata = $xml->createCDATASection($value);
				$newnode->appendChild($cdata);
				$root->appendChild($newnode);
			}
		}
		
		return $xml;
	}
	
	/**
	 * Generate pre-canned FAILED status XML
	 * 
	 * @param string $message [optional()]
	 * @return \DOMDocument
	 */
	private function generatFailedXML($message = null)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml->appendChild($xml->createElement('Results'));
		$newnode = $xml->createElement('Result', 'FAILED');
		$attr = $xml->createAttribute('ID');
		$attr->value = 'Status';
		$newnode->appendChild($attr);
		$root->appendChild($newnode);
		if (!empty($message))
		{
			$newnode = $xml->createElement('Result');
			$attr = $xml->createAttribute('ID');
			$attr->value = 'ErrMsg';
			$newnode->appendChild($attr);
			$cdata = $xml->createCDATASection($message);
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
		}
		
		return $xml;
	}
}