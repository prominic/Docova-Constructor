<?php
namespace Docova\DocovaBundle\Controller;

use Docova\DocovaBundle\Security\User\CustomACL;

use Docova\DocovaBundle\Entity\DocumentsLog;

use Docova\DocovaBundle\Entity\FoldersLog;

class Miscellaneous
{
	const ACTION_CREATE	= 2;
	const ACTION_UPDATE	= 4;
	const ACTION_DELETE	= 8;
	const ACTION_LOCK	= 1;
	const ACTION_UNLOCK	= 3;
	const ACTION_WORKFLOW = 5;
	const ACTION_ARCHIVE = 6;
	
	const FOLDER_CREATE = 'Created new folder.';
	const FOLDER_UPDATE = 'Updated folder properties.';
	const FOLDER_DELETE = 'Moved folder to Recycle Bin.';

	const DOCUMENT_CREATE = 'Created new document.';
	const DOCUMENT_UPDATE = 'Saved and unlocked document.';
	const DOCUMENT_DELETE = 'Moved document to Recycle Bin.';
	const DOCUMENT_LOCK	  = 'Locked document for editing.';
	const DOCUMENT_UNLOCK = 'Unlocked document.';
	const DOCUMENT_ARCHIVE = 'Archived document.';
	
	protected $security_checker;
	protected $security_token;
	protected $container;

	public function __construct($container)
	{
		$this->container = $container;
		$this->security_checker = $container->get('security.authorization_checker');
		$this->security_token = $container->get('security.token_storage');
	}
	
	/**
	 * @param \Docova\DocovaBundle\Entity\Libraries $library
	 * @return array
	 */
	public function getDBAccessLevel($library)
	{
		if ($library->getTrash() === true || false === $this->security_checker->isGranted('VIEW', $library)) 
		{
			$output = array('dbaccess' => 0, 'docrole' => 'No Access');
			return $output;
		}

		$output = array('dbaccess' => 2, 'docrole' => 'Reader');
//		if ($this->security_checker->isGranted('CREATE', $library)) {
			if ($this->security_checker->isGranted('ROLE_ADMIN') || $this->security_checker->isGranted('MASTER', $library)) {
				$output['dbaccess'] = 6;
				$output['docrole'] = 'Manager';
			}
			elseif ($this->security_checker->isGranted('ROLE_USER')) {
				$output['dbaccess'] = 3;
				$output['docrole'] = 'Author';
			}
//		}
		
		return $output;
	}

	/**
	 * Get the Access Level options for current user
	 *
	 * @param \Docova\DocovaBundle\Entity\Folders|null $folder
	 * @return array
	 */
	public function getAccessLevel($folder = null)
	{
		$output = array(
			'dbaccess' => 2,
			'docacess' => 0,
			'ccdocument' => 0,
			'cddocument' => 0,
			'ccrevision' => 0,
			'docrole' => 'No Access' 
		);

		if (!empty($folder))
		{
			$library_ac = $this->getDBAccessLevel($folder->getLibrary());
			$output['dbaccess'] = $library_ac['dbaccess'];
			$output['docrole'] = $library_ac['docrole'];
			unset($library_ac);
		}
		else {
			if ($this->security_checker->isGranted('ROLE_ADMIN')) {
				$output['dbaccess'] = 6;
				$output['docrole'] = 'Manager';
			}
			elseif ($this->security_checker->isGranted('ROLE_USER')) {
				$output['dbaccess'] = 3;
				$output['docrole'] = 'Author';
			}
		}

		if ($output['dbaccess'] == 6) {
			$output['ccdocument'] = 1;
		}
		elseif ($output['dbaccess'] > 2) {
			if (!empty($folder) && true === $this->canCreateDocument($folder)) {
				$output['ccdocument'] = 1;
			}
			elseif (empty($folder)) {
				$output['ccdocument'] = 1;
			}
		}
			
		if ($output['dbaccess'] == 6 || $output['dbaccess'] <= 2) {
			$output['docacess'] = $output['dbaccess'];
		}
		else {
			if (!empty($folder) && true === $this->isFolderManagers($folder)) {
				$output['cddocument'] = 1;
				$output['docacess'] = 6;
				$output['docrole'] = 'Manager';
			}
			elseif (!empty($folder) && true === $this->security_checker->isGranted('MASTER', $folder)) {
				$output['ccdocument'] = 1;
				$output['cddocument'] = 1;
				$output['docacess'] = 3;
				$output['docrole'] = 'Author';
			}
			elseif (!empty($folder) && true === $this->canCreateDocument($folder)) {
				$output['docacess'] = 3;
				$output['docrole'] = 'Author';
			}
			elseif (!empty($folder) && true === $this->isFolderVisible($folder)) {
				$output['docacess'] = 2;
				$output['docrole'] = 'Reader';
			}
			elseif (empty($folder)) {
				$output['docacess'] = 3;
				$output['docrole'] = 'Author';
			}
		}
			
		if (!empty($folder) && $output['cddocument'] !== 1 && true === $this->canDeleteDocument($folder))
		{
			$output['cddocument'] = 1;
		}
		elseif (empty($folder) && $output['dbaccess'] > 3) {
			$output['cddocument'] = 1;
		}
			
		if ($output['ccdocument'] && !empty($folder) && $folder->getEnableACR()) {
			$output['ccrevision'] = 1;
		}

		return $output;
	}
	
	/**
	 * Get access level details base on the document
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @return array
	 */
	public function getAccessLevelForDocument($document)
	{
		$output = array(
			'dbaccess' => 2,
			'docacess' => 0,
			'ccdocument' => 0,
			'cddocument' => 0,
			'docrole' => 'Reader'
		);

		$library_ac = $this->getDBAccessLevel($document->getFolder()->getLibrary());
		$output['dbaccess'] = $library_ac['dbaccess'];
		$output['docrole'] = $library_ac['docrole'];
		unset($library_ac);
		if ($output['dbaccess'] == 6) {
			$output['ccdocument'] = 1;
		}
		elseif ($output['dbaccess'] > 2) {
			if (true === $this->canCreateDocument($document->getFolder())) {
				$output['ccdocument'] = 1;
			}
		}
			
		if ($output['dbaccess'] == 6 || $output['dbaccess'] <= 2) {
			$output['docacess'] = $output['dbaccess'];
		}
		else {
			if (true === $this->isFolderManagers($document->getFolder())) {
				$output['docacess'] = 6;
				$output['docrole'] = 'Manager';
			}
			elseif (true === $this->canEditDocument($document)) {
				$output['docacess'] = 3;
				$output['docrole'] = 'Author';
			}
			elseif (true === $this->canReadDocument($document)) {
				$output['docacess'] = 2;
				$output['docrole'] = 'Reader';
			}
		}
			
		if (true === $this->canSoftDeleteDocument($document))
		{
			$output['cddocument'] = 1;
		}
		
		return $output;
	}

	/**
	 * Check if the library is subscribed
	 * 
	 * @param \Docova\DocovaBundle\Entity\Libraries $library
	 * @return boolean
	 */
	public function isLibrarySubscribed($library)
	{
		if ($library->getTrash()) 
		{
			return false;
		}
		$customAcl = new CustomACL($this->container);
		$user = $this->container->get('doctrine')->getManager()->getReference('DocovaBundle:UserAccounts', $this->security_token->getToken()->getUser()->getId());
		if (false === $customAcl->isUserGranted($library, $user, 'delete'))
		{
			return false;
		}
		
		return true;
	}

	/**
	 * Check if the user can see the folder
	 * 
	 * @param object|array $folder
	 * @param boolean $check_parents
	 * @return boolean
	 */
	public function isFolderVisible($folder, $check_parents = true)
	{
		if (!empty($folder)) {
			if ($folder instanceof \Docova\DocovaBundle\Entity\Folders) 
			{
				$library = $folder->getLibrary();
				if ($library->getTrash() === true) 
				{
					return false;
				}
				$position = $folder->getPosition();
				$parentFolder = $folder->getParentfolder();
			}
			else {
				$em = $this->container->get('doctrine')->getManager();
				$library = $em->getReference('DocovaBundle:Libraries', $folder['Library']['id']);
				$position = $folder['Position'];
				if (!empty($folder['parentfolder']['id'])) {
					$parentFolder = $em->getReference('DocovaBundle:Folders', $folder['parentfolder']['id']);
				}
				else {
					$parentFolder = null;
				}
				$folder = $em->getReference('DocovaBundle:Folders', $folder['id']);
			}

			if (false === $this->isLibrarySubscribed($library))
			{
				return false;
			}

			if (true === $this->security_checker->isGranted('ROLE_ADMIN') || true === $this->security_checker->isGranted('MASTER', $library))
			{
				return true;
			}

			if ($check_parents === true) 
			{
				$ancestors = $this->container->get('doctrine')->getManager()->getRepository('DocovaBundle:Folders')->getAncestors($position, $library->getId());
				if (!empty($ancestors)) {
					$res = $this->isParentFolderVisible($ancestors, $folder->getId());
					if ($res === false && (empty($parentFolder) || false === $this->security_checker->isGranted('CREATE', $parentFolder))) {
						return false;
					}
				}
			}
			
			if (true === $this->security_checker->isGranted('MASTER', $folder) || true === $this->security_checker->isGranted('OWNER', $folder) || true === $this->security_checker->isGranted('VIEW', $folder))
			{
				return true;
			}
			elseif (true === $this->security_checker->isGranted('CREATE', $folder)) 
			{
				return true;
			}
			else
			{
				if (empty($ancestors)) 
				{
					$ancestors = $this->container->get('doctrine')->getManager()->getRepository('DocovaBundle:Folders')->getAncestors($position, $library->getId());
				}
				if (true === $this->isFolderManagers($ancestors)) 
				{
					return true;
				}
			}
			unset($ancestors);
		}
		return false;
	}

	/**
	 * Check if the user can create a document in a folder
	 * 
	 * @param object $folder
	 * @return boolean
	 */
	public function canCreateDocument($folder)
	{
		if ($this->security_checker->isGranted('ROLE_ADMIN') || $this->security_checker->isGranted('MASTER', $folder->getLibrary())) 
		{
			return true;
		}

		if (false === $this->isLibrarySubscribed($folder->getLibrary())) 
		{
			return false;
		}

		if (true === $this->isFolderManagers($folder)) 
		{
			return true;
		}
		
		if (true === $this->security_checker->isGranted('MASTER', $folder)) 
		{
			return true;
		}

		if (true === $this->security_checker->isGranted('CREATE', $folder)) 
		{
			$customAcl = new CustomACL($this->container);
			$author = $customAcl->isRoleGranted($folder, 'ROLE_USER', 'create');
			if (empty($author)) {
				return true;
			}
			$readers = $customAcl->getObjectACEUsers($folder, 'view');
			if ($readers->count() > 0) 
			{
				return false;
			}
			$readers = $customAcl->getObjectACEGroups($folder, 'view');
			if ($readers->count() > 0) 
			{
				return false;
			}
			unset($customAcl, $readers, $rd, $role);
			return true;
		}

		return false;
	}

	/**
	 * Check if the user can see/read the document (either itself or parent folder managers)
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $folder_master_required
	 * @param boolean $check_workflow
	 * @return boolean
	 */
	public function canReadDocument($document, $folder_master_required = false, $by_pass_subscription = false)
	{
		$folder = $document->getFolder();
		if ($by_pass_subscription === false && $this->isLibrarySubscribed($folder->getLibrary()) === false) 
		{
			return false;
		}
		
		if (true === $this->security_checker->isGranted('ROLE_ADMIN') || true === $this->security_checker->isGranted('MASTER', $folder->getLibrary())) 
		{
			return true;
		}
		
		$ancestors = $this->container->get('doctrine')->getManager()->getRepository('DocovaBundle:Folders')->getAncestors($folder->getPosition(), $folder->getLibrary()->getId());
		if (!empty($ancestors))
		{
			if (false === $this->isParentFolderVisible($ancestors, $folder->getId())) 
			{
				return false;
			}
		}
		unset($ancestors);
		
		if (true === $this->security_checker->isGranted('MASTER', $folder)) 
		{
			return true;
		}

		if ($folder_master_required === true)
		{
			if (true === $this->isFolderManagers($folder)) {
				return true;
			}
		}
		
		if ($this->security_checker->isGranted('VIEW', $document)) 
		{
			$custom_acl = new CustomACL($this->container);
			$current_user = $this->security_token->getToken()->getUser();
			
			if ($folder->getPrivateDraft() === true && $document->getDocStatus() !== 'Released')
			{
				$visible = false;
				if ($current_user->getId() === $document->getOwner()->getId())
				{
					$visible = true;
				}

				//if (false === $custom_acl->isRoleGranted($document, 'ROLE_USER', 'view') && true === $custom_acl->isUserGranted($document, $current_user, 'view'))
				if (false === $custom_acl->isRoleGranted($document, 'ROLE_USER', 'view') && true === $this->security_checker->isGranted('VIEW', $document))
				{
					$visible = true;
				}

				if ($document->getDocSteps()->count() > 0)
				{
					$steps = $document->getDocSteps();
					foreach ($steps as $step)
					{
						if ($step->getStatus() === 'Pending' && $step->getDateStarted() && true === $this->searchUserInCollection($step->getAssignee(), $current_user)) {
							$visible = true;
							break;
						}
					}
				}
				
				if ($visible === false) {
					return false;
				}
			}
			
			if (true === $this->security_checker->isGranted('CREATE', $folder))
			{
				return true;
			}
			
			if (true === $this->security_checker->isGranted('VIEW', $folder) && $folder->getSetDVA() === false)
			{
				$visible = false;
				if (empty($steps) && $document->getDocSteps()->count() > 0) 
				{
					$steps = $document->getDocSteps();
					foreach ($steps as $step)
					{
						if ($step->getStatus() === 'Pending' && $step->getDateStarted() && true === $this->searchUserInCollection($step->getAssignee(), $current_user)) {
							$visible = true;
							break;
						}
					}
				}
				
				if ($visible === true) { return true; }
				if ($document->getStatusNo() != 1 && $document->getStatusNo() !== 6) {
					return false;
				}
			}
			unset($custom_acl);
			
			return true;
		}
		
		return false;
	}

	/**
	 * Check if the user can edit the document (either itself or according to parent folder managers)
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $folder_master_required
	 * @return boolean
	 */
	public function canEditDocument($document, $folder_master_required = false)
	{
		if ($this->isLibrarySubscribed($document->getFolder()->getLibrary()) === false) 
		{
			return false;
		}

		if ($document->getDocType()->getEnableVersions() && $document->getDocType()->getStrictVersioning() && $document->getStatusNo() != 0)
		{
			return false;
		}

		if (true === $this->security_checker->isGranted('ROLE_ADMIN') || true === $this->security_checker->isGranted('MASTER', $document->getFolder()->getLibrary())) 
		{
			return true;
		}
		
		$ancestors = $this->container->get('doctrine')->getManager()->getRepository('DocovaBundle:Folders')->getAncestors($document->getFolder()->getPosition(), $document->getFolder()->getLibrary()->getId());
		if (!empty($ancestors)) 
		{
			if (false === $this->isParentFolderVisible($ancestors, $document->getFolder()->getId()))
			{
				return false;
			}
		}
		unset($ancestors);
		
		if (true === $this->security_checker->isGranted('MASTER', $document->getFolder())) 
		{
			return true;
		}
		
		if ($folder_master_required === true)
		{
			if (true === $this->isFolderManagers($document->getFolder())) {
				return true;
			}
		}
	
		if ($this->security_checker->isGranted('EDIT', $document))
		{
			return true;
		}

		if ($document->getDocType()->getEnableWorkflow())
		{
			$active_step = $this->container->get('doctrine')->getManager()->getRepository('DocovaBundle:DocumentWorkflowSteps')->getFirstPendingStep($document->getId());
			if (!empty($active_step) && ($active_step[0]->getStepType() == 2 || $active_step[0]->getStepType() == 4 || $active_step[0]->getApproverEdit()))
			{
				$current_user = $this->security_token->getToken()->getUser();
				foreach ($active_step as $step)
				{
					if (true === $this->searchUserInCollection($step->getAssignee(), $current_user))
					{
						return true;
					}
				}
			}
		}
		
		return false;
	}

	/**
	 * Check if the user can delete the documents in the folder
	 * 
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @return boolean
	 */
	public function canDeleteDocument($folder)
	{
		if ($this->security_checker->isGranted('ROLE_ADMIN') || true === $this->security_checker->isGranted('MASTER', $folder->getLibrary())) 
		{
			return true;
		}

		if ($this->isLibrarySubscribed($folder->getLibrary()) === false)
		{
			return false;
		}

		$ancestors = $this->container->get('doctrine')->getManager()->getRepository('DocovaBundle:Folders')->getAncestors($folder->getPosition(), $folder->getLibrary()->getId());
		if (!empty($ancestors)) 
		{
			if (false === $this->isParentFolderVisible($ancestors, $folder->getId()))
			{
				return false;
			}
		}
		unset($ancestors);

		if (false === $this->canCreateDocument($folder))
		{
			return false;
		}

		if ($this->security_checker->isGranted('DELETE', $folder))
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Check if the current user can soft delete the document
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @return boolean
	 */
	public function canSoftDeleteDocument($document)
	{
		if ($this->security_checker->isGranted('ROLE_ADMIN') || true === $this->security_checker->isGranted('MASTER', $document->getFolder()->getLibrary()))
		{
			return true;
		}
		
		if (true === $this->isFolderManagers($document->getFolder())) 
		{
			return true;
		}
		
		if (true === $this->security_checker->isGranted('MASTER', $document->getFolder())) 
		{
			return true;
		}

		if (false === $this->security_checker->isGranted('DELETE', $document->getFolder()))
		{
			return false;
		}		

		if (true === $this->canEditDocument($document, true)) 
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Check if the user is one of the parent folder managers
	 * 
	 * @param \Docova\DocovaBundle\Entity\Folders|array $parent_folder
	 * @return boolean
	 */
	public function isFolderManagers($folder)
	{
		$library = $folder instanceof  \Docova\DocovaBundle\Entity\Folders ? $folder->getLibrary() : $folder[0]->getLibrary();
		if ($this->security_checker->isGranted('ROLE_ADMIN') || true === $this->security_checker->isGranted('MASTER', $library))
		{
			return true;
		}

		if ($folder instanceof \Docova\DocovaBundle\Entity\Folders) 
		{
			if ($library->getTrash() === true) 
			{
				return false;
			}
			$ancestors = $this->container->get('doctrine')->getManager()->getRepository('DocovaBundle:Folders')->getAncestors($folder->getPosition(), $library->getId(), $folder->getId());
		}
		else {
			if ($library->getTrash() === true) 
			{
				return false;
			}
			$ancestors = $folder;
		}
		if (!empty($ancestors))
		{
			if (count($ancestors) > 0) {
				foreach ($ancestors as $pfolder) 
				{
					if ($this->security_checker->isGranted('OWNER', $pfolder))
					{
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Add audit log for the folder
	 * 
	 * @param object $em
	 * @param string $action
	 * @param object $folder
	 * @param string $details [optional()]
	 * @param boolean $status [optional()]
	 * @return void|boolean
	 */
	public function createFolderLog($em, $action, $folder, $details = '', $status = true)
	{
		if (!empty($action) && !empty($folder)) {

			$entity = new FoldersLog();
			$entity->setFolder($folder);
			$entity->setLogAuthor($this->security_token->getToken()->getUser());
			$entity->setLogDate(new \DateTime());
			$entity->setLogStatus($status);

			eval("\$val = self::ACTION_".$action.';');
			if (empty($val)) { return false; }
			$entity->setLogAction($val);
			
			if (empty($details)) {
				eval("\$details = self::FOLDER_".$action.';');
			}
			$entity->setLogDetails($details);
			
			$em->persist($entity);
			$em->flush();
		}
	}

	/**
	 * Add audit log for a document
	 * 
	 * @param object $em
	 * @param string $action
	 * @param object $document
	 * @param string $details [optional()]
	 * @param boolean $status [optional()]
	 * @return void|boolean
	 */
	public function createDocumentLog($em, $action, $document, $details = '', $status = true)
	{
		if (!empty($action) && !empty($document)) {

			$entity = new DocumentsLog();
			$entity->setDocument($document);
			if(method_exists($this->security_token->getToken(), 'getUser')) {
				$entity->setLogAuthor($this->security_token->getToken()->getUser());
			}
			else {
				$entity->setLogAuthor(null);
			}
			$entity->setLogDate(new \DateTime());
			$entity->setLogStatus($status);

			eval("\$val = self::ACTION_".$action.';');
			if (empty($val)) { return false; }
			$entity->setLogAction($val);
			
			if (empty($details)) {
				eval("\$details = self::DOCUMENT_".$action.';');
			}
			$entity->setLogDetails($details);
			
			$em->persist($entity);
			$em->flush();
		}
	}
	
	/**
	 * Check if user can create folder (either a root folder or a subfolder)
	 * 
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @param \Docova\DocovaBundle\Entity\Libraries $library
	 * @return boolean
	 */
	public function canCreateFolder($folder = null, $library = null)
	{
		if ($this->security_checker->isGranted('ROLE_ADMIN')) 
		{
			return true;
		}

		if (empty($folder) && !empty($library)) 
		{
			if ($this->security_checker->isGranted('MASTER', $library))
			{
				return true;
			}
			if (false === $this->isLibrarySubscribed($library)) 
			{
				return false;
			}
			
			if (false === $this->security_checker->isGranted('CREATE', $library)) 
			{
				return false;
			}
			
			return true;
		}
		elseif (!empty($folder) && empty($library))
		{
			if ($this->security_checker->isGranted('MASTER', $folder->getLibrary()))
			{
				return true;
			}
			if (false === $this->isLibrarySubscribed($folder->getLibrary())) 
			{
				return false;
			}
			
			if (true === $this->isFolderManagers($folder)) 
			{
				return true;
			}
			
			if (true === $this->security_checker->isGranted('MASTER', $folder)) 
			{
				return true;
			}
			
			if ($folder->getDisableACF()) 
			{
				return false;
			}
			
			if ($this->canCreateDocument($folder)) 
			{
				return true;
			}
			
			return false;
		}
		return false;
	}

	/**
	 * Check if the user can see the parent folders
	 * 
	 * @param array $ancestors
	 * @param string $folderId
	 * @return boolean
	 */
	private function isParentFolderVisible($ancestors, $folderId = null)
	{
		if (!empty($ancestors) && is_array($ancestors)) 
		{
			$folderIds = array();
			foreach ($ancestors as $pfolder) {
				if (!empty($folderId) && $pfolder->getId() != $folderId) {
					$folderIds[] = $pfolder->getId();
				}
			}
			$user = $this->security_token->getToken()->getUser();
			$grpQuery = '';
			$groups = $user->getRoles();
    		$groups = array_values(array_diff($groups, array('ROLE_USER', 'ROLE_ADMIN')));
    		$groups = !empty($groups[0]) ? "'".implode("','", $groups)."'" : null;
    		$grpQuery = !empty($groups) ? " AND SI.identifier NOT IN ($groups)" : '';
			$folderIds = implode("','", $folderIds);
			$em = $this->container->get('doctrine')->getManager();
			$conn = $em->getConnection();
/*
			$query = "SELECT COUNT(DISTINCT(AE.id)) FROM acl_entries AS AE INNER JOIN acl_object_identities AS AI ON (AE.object_identity_id = AI.id)  INNER JOIN acl_security_identities AS SI ON (AE.security_identity_id = SI.id)
						WHERE ((AI.object_identifier IN ('$folderIds') AND AE.mask IN (1,128))
							AND (AI.object_identifier = ? AND AE.mask = 2) AND SI.identifier != ? AND SI.identifier != ? $grpQuery)";
			$result = $conn->fetchArray($query, array($folderId, 'Docova\DocovaBundle\Entity\UserAccounts-' . $user->getUsername(), 'ROLE_USER'));
			if (!empty($result) && $result[0] > 0) {
				return false;
			}
			
			return true;
*/
			$query = "SELECT COUNT(DISTINCT(AE.id)) FROM acl_entries AS AE INNER JOIN acl_object_identities AS AI ON (AE.object_identity_id = AI.id) INNER JOIN acl_security_identities AS SI ON (AE.security_identity_id = SI.id)
						WHERE AI.object_identifier IN ('$folderIds'".(!empty($folderId) ? ",'$folderId'" : '').") AND AE.mask >= 128 AND SI.identifier != ? $grpQuery";
			$result = $conn->fetchArray($query, array('Docova\DocovaBundle\Entity\UserAccounts-' . $user->getUsername()));
			if (empty($result) || $result[0] == 0) {
				return true;
			}
			$granted = false;
			foreach ($ancestors as $pfolder) {
				if (!empty($folderId) && $pfolder->getId() != $folderId) {
					$id = $pfolder->getId();
				}
				elseif (empty($folderId)) {
					$id = $pfolder->getId();
				}
				else {
					continue;
				}
				$query = "SELECT COUNT(DISTINCT(AE.id)) FROM acl_entries AS AE INNER JOIN acl_object_identities AS AI ON (AE.object_identity_id = AI.id) INNER JOIN acl_security_identities AS SI ON (AE.security_identity_id = SI.id)
							WHERE (AE.mask = 1 AND AI.object_identifier = ? AND (SI.identifier = ? OR SI.identifier = ? ".(!empty($groups) ? "OR SI.identifier IN ($groups)" : '')."))";
				$result = $conn->fetchArray($query, array($id, 'ROLE_USER', 'Docova\DocovaBundle\Entity\UserAccounts-' . $user->getUsername()));
				if (empty($result) || empty($result[0])) {
					if (!empty($folderId) && $id == $folderId) {
						$query = "SELECT COUNT(AE.id) FROM acl_entries AS AE INNER JOIN acl_object_identities AS AI ON (AE.object_identity_id = AI.id) INNER JOIN acl_security_identities AS SI ON (AE.security_identity_id = SI.id)
									WHERE AE.mask = 2 AND AI.object_identifier = ? AND (SI.identifier = ? ".(!empty($groups) ? "OR SI.identifier IN ($groups)" : '').")";
						
						$result = $conn->fetchArray($query, array($folderId, 'Docova\DocovaBundle\Entity\UserAccounts-' . $user->getUsername()));
						if (!empty($result) && $result[0]) {
							return true;
						}
					}
					return false;
				}
				else {
					$granted = true;
				}
			}
			return $granted;

			foreach ($ancestors as $pfolder)
			{
				if (true === $this->security_checker->isGranted('MASTER', $pfolder)) 
				{
					return true;
				}
	
				if (false === $this->security_checker->isGranted('VIEW', $pfolder)) 
				{
					return false;
				}
			}
		}
		return true;
/*
		while ($parent_folder)
		{
			if (false === $this->security_checker->isGranted('VIEW', $parent_folder)) 
			{
				unset($parent_folder);
				return false;
			}
			if (false === $this->isFolderVisible($parent_folder, false)) 
			{
				unset($parent_folder);
				return false;
			}
			$parent_folder = $parent_folder->getParentfolder();
		}
		unset($parent_folder);
		return true;
*/
	}
	
	/**
	 * Search for a user inside a collection object
	 * 
	 * @param \Doctrine\Common\Collections\ArrayCollection $collection
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 * @param boolean $isCompletedBy [optional(false)]
	 * @return boolean
	 */
	private function searchUserInCollection($collection, $user, $isCompletedBy = false)
	{
		foreach ($collection as $record) {
			if ($isCompletedBy === false) 
			{
				if ($record->getAssignee()->getId() === $user->getId()) {
					return true;
				}
			}
			else {
				if ($record->getCompletedBy()->getId() === $user->getId()) {
					return true;
				}
			}
		}
		return false;
	}
}