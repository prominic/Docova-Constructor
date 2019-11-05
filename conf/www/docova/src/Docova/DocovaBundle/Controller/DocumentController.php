<?php
namespace Docova\DocovaBundle\Controller;

use Docova\DocovaBundle\Entity\Bookmarks;
use Docova\DocovaBundle\Entity\DocumentsWatchlist;
use Docova\DocovaBundle\Entity\FoldersWatchlist;
use Docova\DocovaBundle\Entity\RelatedDocuments;
use Docova\DocovaBundle\Entity\DocumentComments;
use Docova\DocovaBundle\Entity\DocumentWorkflowSteps;
//use Docova\DocovaBundle\Entity\Folders;
use Docova\DocovaBundle\Security\User\CustomACL;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Docova\DocovaBundle\Entity\AttachmentsDetails;
use Docova\DocovaBundle\Entity\FormTextValues;
use Docova\DocovaBundle\Entity\FormNumericValues;
use Docova\DocovaBundle\Entity\FormDateTimeValues;
use Docova\DocovaBundle\Entity\UserAccounts;
//use Docova\DocovaBundle\Entity\UserProfile;
use Docova\DocovaBundle\Entity\Documents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\File\UploadedFile;
use Docova\DocovaBundle\Entity\DocumentActivities;
use Docova\DocovaBundle\Entity\DiscussionTopic;
use Docova\DocovaBundle\Entity\DiscussionAttachments;
use Docova\DocovaBundle\Entity\TrashedLogs;
use Docova\DocovaBundle\Entity\DocWorkflowStepActions;
use Docova\DocovaBundle\Entity\RelatedLinks;
use Docova\DocovaBundle\Entity\WorkflowAssignee;
use Docova\DocovaBundle\Entity\WorkflowCompletedBy;
use Docova\DocovaBundle\Entity\SavedSearches;
//use Docova\DocovaBundle\Entity\UserRoles;
use Docova\DocovaBundle\Extensions\ExternalViews;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Docova\DocovaBundle\Extensions\MiscFunctions;
use Docova\DocovaBundle\Entity\AppDocComments;
use Docova\DocovaBundle\Entity\FormNameValues;
use Docova\DocovaBundle\Entity\FormGroupValues;
use Docova\DocovaBundle\ObjectModel\DocovaDocument;
use Symfony\Component\Console\Input\ArrayInput;
use Docova\DocovaBundle\Extensions\CopyDesignServices;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\NullOutput;
//use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
//use PhpOffice\PhpWord\Settings;
use Docova\DocovaBundle\Entity\UserDelegates;
use Docova\DocovaBundle\ObjectModel\Docova;

class DocumentController extends Controller
{
	private $docova;
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
		
		$this->root_path = $_SERVER['DOCUMENT_ROOT'];

		$path = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root') : $_SERVER['DOCUMENT_ROOT']; 
		$this->UPLOAD_FILE_PATH = $path.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
		
		$this->docova = new Docova($this->container);
	}

	public function showDocTypeAction(Request $request, $xmobile)
	{
		$this->initialize();
		$folder_id = $request->query->get('ParentUNID');

		if (empty($folder_id)) {
			throw $this->createNotFoundException('Folder Id is missed.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$folder_obj = $em->find('DocovaBundle:Folders', $folder_id);

		if (empty($folder_obj)) {
			throw $this->createNotFoundException('No folder is found for ID = '.$folder_id);
		}

		$security_check = new Miscellaneous($this->container);
		if (false === $security_check->canCreateDocument($folder_obj)) {
			throw new AccessDeniedException();
		}
		
		if ($folder_obj->getApplicableDocType()->count() == 0)
		{
			if ($folder_obj->getLibrary()->getApplicableDocType()->count() > 0)
			{
				$doc_type_list = $folder_obj->getLibrary()->getApplicableDocType();
			}
			else {
				$doc_type_list = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true, 'Trash' => false), array('Doc_Name' => 'ASC'));
			}
		}
		else {
			$doc_type_list = $folder_obj->getApplicableDocType();
		}

		if (empty($doc_type_list)) {
			throw $this->createNotFoundException('No Document Type was found.');
		}
		
		if ($xmobile == 'xml')
		{
			$result_xml = new \DOMDocument('1.0', 'UTF-8');
			$response = new Response();
			$response->headers->set('Content-Type', 'text/xml');
			$root = $result_xml->appendChild($result_xml->createElement('documents'));
			foreach ($doc_type_list as $doctype)
			{
				$newnode = $root->appendChild($result_xml->createElement('doctype'));
				$cdata = $result_xml->createCDATASection($doctype->getDocName());
				$child = $result_xml->createElement('name');
				$child->appendChild($cdata);
				$newnode->appendChild($child);
				
				$child = $result_xml->createElement('dockey', $doctype->getId());
				$newnode->appendChild($child);
				
				$root->appendChild($newnode);
			}
			
			$response->setContent($result_xml->saveXML());
			unset($result_xml, $doctype, $doc_type_list);
			return $response;
		}
		
		return $this->render('DocovaBundle:Default:DocTypeDialog.html.twig', array(
				'user' => $this->user,
				'folder' => $folder_obj,
				'doc_types' => $doc_type_list
				));
	}
	
	public function createDocumentAction(Request $request, $mobile)
	{
	    $id = null;
	    unset($id);
		$folder_id	= $request->query->get('ParentUNID');
		$doctype_id	= $request->query->get('typekey');

		$text_content_type = $link_comments = '';
		$attachment_settings = $related_doc_settings = $templates = array();
		$this->initialize();
	
		if (empty($folder_id) || empty($doctype_id)) {
			throw $this->createNotFoundException('Folder ID or Doc Type ID is missed.');
		}
	
		$em = $this->getDoctrine()->getManager();
		$result = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $doctype_id, 'Trash' => false));
		$has_attachment = (in_array('ATT', $result->getContentSections()) || in_array('ATTI', $result->getContentSections())) ? 'true' : '';
		if (empty($result)) {
			throw $this->createNotFoundException('Document Type could not be found.');
		}
	
		$folder_obj = $em->find('DocovaBundle:Folders', $folder_id);
		if (empty($folder_obj)) {
			throw $this->createNotFoundException('Folder details could not be found.');
		}
	
		$security_check = new Miscellaneous($this->container);
		if (false === $security_check->canCreateDocument($folder_obj)) {
			throw new AccessDeniedException();
		}
	
		$access_level = $security_check->getAccessLevel($folder_obj);
		$subforms_arr = $rendered_subforms = array();
		$folder_managers = $folder_editors = $folder_authors = $folder_readers = array();
		$customAcl = new CustomACL($this->container);
		$users_list = $customAcl->getObjectACEUsers($folder_obj, 'owner');
		if ($users_list->count() > 0)
		{
			foreach ($users_list as $user) {
				$folder_managers[] = $user->getUsername();
			}
		}
		$groups_list = $customAcl->getObjectACEGroups($folder_obj, 'owner');
		if ($groups_list->count() > 0)
		{
			foreach ($groups_list as $group) {
				if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
				{
					$folder_managers[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
				}
			}
		}
		$users_list = $customAcl->getObjectACEUsers($folder_obj, 'master');
		if ($users_list->count() > 0)
		{
			foreach ($users_list as $user) {
				if (!in_array($user->getUsername(), $folder_editors)) {
					$folder_editors[] = $user->getUsername();
				}
			}
		}
		$groups_list = $customAcl->getObjectACEGroups($folder_obj, 'master');
		if ($groups_list->count() > 0)
		{
			foreach ($groups_list as $group) {
				if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
				{
					$folder_editors[] = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
				}
			}
		}
		$users_list = $customAcl->getObjectACEUsers($folder_obj, 'create');
		if ($users_list->count() > 0)
		{
			foreach ($users_list as $user)
			{
				if (!in_array($user->getUsername(), $folder_authors)) {
					$folder_authors[] = $user->getUsername();
				}
			}
		}
		$groups_list = $customAcl->getObjectACEGroups($folder_obj, 'create');
		if ($groups_list->count() > 0)
		{
			foreach ($groups_list as $group) {
				if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
				{
					$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					if (!in_array($groupname, $folder_authors))
					{
						$folder_authors[] = $groupname;
					}
				}
			}
		}

		$ancestors = $em->getRepository('DocovaBundle:Folders')->getAncestors($folder_obj->getPosition(), $folder_obj->getLibrary()->getId(), $folder_obj->getId(), true);
		foreach ($ancestors as $folder)
		{
			$folder = $em->getReference('DocovaBundle:Folders', $folder['id']);
			$users_list = $customAcl->getObjectACEUsers($folder, 'view');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user)
				{
					if (!in_array($user->getUsername(), $folder_readers)) {
						$folder_readers[] = $user->getUsername();
					}
				}
			}
			$groups_list = $customAcl->getObjectACEGroups($folder, 'view');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						if (!in_array($groupname, $folder_readers)) {
							$folder_readers[] = $groupname;
						}
					}
				}
			}
		}

		$subform_obj = $result->getDocTypeSubform();
		if (!empty($subform_obj)) {
			foreach ($subform_obj as $object) {
				$subforms_arr[$object->getSubform()->getFormFileName()]['elements'] = $object->getSubform()->getSubformFields();
				if (file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('bundles/docova/js/custom/'.$object->getSubform()->getFormFileName().'.js')) || file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('upload/js/'.$object->getSubform()->getFormFileName().'.js'))) {
					$subforms_arr[$object->getSubform()->getFormFileName()]['JSHeader'] = $object->getSubform()->getFormFileName();
				}
				else {
					$subforms_arr[$object->getSubform()->getFormFileName()]['JSHeader'] = false;
				}
				
				if ($object->getPropertiesXML()) {
					$xml_properties = new \DOMDocument();
					$xml_properties->loadXML(str_replace('&','&amp;',$object->getPropertiesXML()));
					$subforms_arr[$object->getSubform()->getFormFileName()]['Properties'] = $xml_properties;
					
					if (in_array('TXT', $result->getContentSections()) || in_array('RTXT', $result->getContentSections()) || in_array('DRTXT', $result->getContentSections())) {
						$text_content_type	= (in_array('RTXT', $result->getContentSections()) || in_array('DRTXT', $result->getContentSections())) ? 'HTML' : 'TEXT';
					}
					
					if (isset($xml_properties->getElementsByTagName('LinkComments')->item(0)->nodeValue)) {
						$link_comments = $xml_properties->getElementsByTagName('LinkComments')->item(0)->nodeValue;
					}
	
					if (isset($xml_properties->getElementsByTagName('RelatedDocOpenMode')->item(0)->nodeValue)) {
						$related_doc_settings['RelatedDocOpenMode'] = $xml_properties->getElementsByTagName('RelatedDocOpenMode')->item(0)->nodeValue;
						$related_doc_settings['EnableLinkedFiles'] = $xml_properties->getElementsByTagName('EnableLinkedFiles')->item(0)->nodeValue;
						$related_doc_settings['LaunchLinkedFiles'] = $xml_properties->getElementsByTagName('LaunchLinkedFiles')->item(0)->nodeValue;
						$related_doc_settings['EnableXLink'] = $xml_properties->getElementsByTagName('EnableXLink')->item(0)->nodeValue;
						$related_doc_settings['OMUserSelectDocTypeKey'] = $xml_properties->getElementsByTagName('OMUserSelectDocTypeKey')->item(0)->nodeValue;
						$related_doc_settings['OMLatestDocTypeKey'] = $xml_properties->getElementsByTagName('OMLatestDocTypeKey')->item(0)->nodeValue;
						$related_doc_settings['OMLinkedDocTypeKey'] = $xml_properties->getElementsByTagName('OMLinkedDocTypeKey')->item(0)->nodeValue;
					}

					if ($has_attachment === 'true' && isset($xml_properties->getElementsByTagName('HasMultiAttachmentSection')->item(0)->nodeValue)) {
						$attachment_settings['HasMultiAttachmentSection'] = $xml_properties->getElementsByTagName('HasMultiAttachmentSection')->item(0)->nodeValue;
						$attachment_settings['EnableFileCIAO'] = $xml_properties->getElementsByTagName('EnableFileCIAO')->item(0)->nodeValue;
						$attachment_settings['EnableFileViewLogging'] = $xml_properties->getElementsByTagName('EnableFileViewLogging')->item(0)->nodeValue;
						$attachment_settings['EnableFileDownloadLogging'] = $xml_properties->getElementsByTagName('EnableFileDownloadLogging')->item(0)->nodeValue;
						$attachment_settings['MaxFiles'] = $xml_properties->getElementsByTagName('MaxFiles')->item(0)->nodeValue;
						$attachment_settings['AllowedFileExtensions'] = $xml_properties->getElementsByTagName('AllowedFileExtensions')->item(0)->nodeValue;
						$attachment_settings['AttachmentReadOnly'] = $xml_properties->getElementsByTagName('AttachmentReadOnly')->item(0)->nodeValue;
						$attachment_settings['HideOnEditing'] = $xml_properties->getElementsByTagName('HideOnEditing')->item(0)->nodeValue;
						$attachment_settings['EnableLocalScan'] = $xml_properties->getElementsByTagName('EnableLocalScan')->item(0)->nodeValue;
					}

					if (isset($xml_properties->getElementsByTagName('TemplateType')->item(0)->nodeValue)) 
					{
						$templates['Template_Type'] 		= $xml_properties->getElementsByTagName('TemplateType')->item(0)->nodeValue;
						$templates['Template_Auto_Attach']	= $xml_properties->getElementsByTagName('TemplateAutoAttachment')->item(0)->nodeValue;
						$templates['Template_List']			= str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateKey')->item(0)->nodeValue);
						$templates['Template_Name_List']	= str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateName')->item(0)->nodeValue);
						$templates['Template_File_List']	= str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateFileName')->item(0)->nodeValue);
						$templates['Template_Version_list'] = str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateVersion')->item(0)->nodeValue);
					}
				}
				else {
					$subforms_arr[$object->getSubform()->getFormFileName()]['Properties'] = '';
				}
			}
			
			$is_mobile = ($mobile === 'mDocument') ? true : false;
			
			foreach ($subforms_arr as $form_name => $form_elements) {
				$more_section = '';
				if (!empty($form_elements['Properties']) && !empty($form_elements['Properties']->getElementsByTagName('DisplayInMore')->item(0)->nodeValue) && $form_elements['Properties']->getElementsByTagName('DisplayInMore')->item(0)->nodeValue == 1)
				{
					if (!empty($form_elements['Properties']->getElementsByTagName('SubFormTabName')->item(0)->nodeValue)) {
						$more_section = array('Tab_Name' => $form_elements['Properties']->getElementsByTagName('SubFormTabName')->item(0)->nodeValue);
					}
					elseif (!empty($form_elements['Properties']->getElementsByTagName('RelatedDocOpenMode')->item(0)->nodeName)) {
						$more_section = array('Tab_Name' => 'Related Documents');
					}
					else {
						$more_section = array('Tab_Name' => ' ');
					}
				}
				$rendered_subforms[] = array(
					'More_Section' => $more_section,
					'HTML' => $this->renderSubform($form_name, $form_elements['elements'], true, $result, $folder_obj, $is_mobile, $form_elements['Properties']),
					'JSHeader' => $form_elements['JSHeader']
				);
			}
		}

		$docova = $this->get('docova.objectmodel');
		$docova->setUser($this->user);
		$rendered_workflows = $doc_workflow_options = $action_buttons = array();
		$is_workflow_started = $step = $workflow_arr = false;
		$stepsxml = '';
		if ($result->getDocTypeWorkflow()->count() > 0)
		{
			$workflow_xml = $result->getDocTypeWorkflow()->count() > 1 ? '<Documents>' : '';
			foreach ($result->getDocTypeWorkflow() as $workflow)
			{
				$workflow_xml .= '<Document><wfID>'.$workflow->getId().'</wfID><wfName><![CDATA['.$workflow->getWorkflowName().']]></wfName>';
				$workflow_xml .= '<wfDescription><![CDATA['.$workflow->getDescription().']]></wfDescription>';
				$workflow_xml .= ($workflow->getBypassRleasing() == true) ? '<EnableImmediateRelease>1</EnableImmediateRelease>' : '<EnableImmediateRelease/>';
				$workflow_xml .= ($workflow->getAuthorCustomization() == true) ? '<wfCustomizeAction>1</wfCustomizeAction>' : '<wfCustomizeAction/>';
				$workflow_xml .= '</Document>';
				
				if ($result->getDocTypeWorkflow()->count() == 1) {
					$workflow_arr = array(
						'id' => $workflow->getId(),
						'Author_Customization' => $workflow->getAuthorCustomization(),
						'Bypass_Rleasing' => $workflow->getBypassRleasing(),
						'Workflow_Name' => $workflow->getWorkflowName()
					);
					$stepsxml =  $em->getRepository('DocovaBundle:Workflow')->getWorkflowStepsXML( $workflow, $this->user );
					$stepsxml = $stepsxml->saveXML();

					$steps = $workflow->getSteps();
					if ($steps->count() > 0)
					{
						$step = $steps->first();
					}
					else {
						$this->createNotFoundException('No Workflow steps were found.');
					}
				}
				
			}
			$workflow_xml .= $result->getDocTypeWorkflow()->count() > 1 ? '</Documents>' : '';
			$mobileExtension = $is_mobile === true ? '_m' : '';
			$rendered_workflows[] = $this->renderView("DocovaBundle:Subform:sfDocSection-Workflow$mobileExtension.html.twig", array(
					'workflow' => $workflow_arr,
					'workflow_xml' => $workflow_xml,
					'workflow_xml_nodes' => $stepsxml,
					'step' => $step,
					'docstep' => false,
					'doctype' => $result
			));

			$workflow_detail = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
			if (!empty($workflow_detail)) {
				$doc_workflow_options['JSHeader'] = file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('bundles/docova/js/custom/'.$workflow_detail->getFormFileName().$mobileExtension.'.js')) ? $workflow_detail->getFormFileName().$mobileExtension : false;
				$wf_buttons = $workflow_detail->getActionButtons();
				if ($wf_buttons->count() > 0)
				{
					foreach ($wf_buttons as $button)
					{
						if ($button->getActionName() === 'Start_Workflow' || $button->getActionName() === 'Workflow')
						{
							if ($button->getActionName() === 'Start_Workflow' && trim($result->getCustomStartButtonLabel())) {
								$value = trim($result->getCustomStartButtonLabel());
								if (preg_match('~^[<?].*[?>]$~', $value))
								{
									$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
									$function = create_function('$docova', $value);
									$action_buttons[$button->getActionName()]['Label'] = $function($docova);
								}
								else {
									$action_buttons[$button->getActionName()]['Label'] = $value;
								}
							}
							else {
								$action_buttons[$button->getActionName()]['Label'] = $button->getButtonLabel();
							}
							$action_buttons[$button->getActionName()]['Script']	= $button->getClickScript();
							$action_buttons[$button->getActionName()]['Primary'] = $button->getPrimaryIcon();
							$action_buttons[$button->getActionName()]['Secondary'] = $button->getSecondaryIcon();
							$action_buttons[$button->getActionName()]['Visible'] = true;
						}
					}
				}
			}
			unset($workflow_detail);
			
			$doc_workflow_options['isCreated'] = false;
			$doc_workflow_options['isCompleted'] = false;
			$doc_workflow_options['createInDraft'] = true;
		}
	
		if ($request->isMethod('POST')) {
			$paramindex = array();
			foreach($request->request->keys() as $key){
				$paramindex[strtolower($key)] = $key;
			}
			
			$req = $request->request;
			$subject_txt	= $req->get('Subject');
			$author_txt		= $req->get('OriginalAuthor');
			$owner_txt 		= $req->get('DocumentOwner');
			$desc_txt 		= trim($req->get('Description')) ? $req->get('Description') : null;
			$keywords_txt	= trim($req->get('Keywords')) ? $req->get('Keywords') : null;
			
			if (!empty($author_txt)) {
				$author_obj = $this->findUserAndCreate($author_txt);
				if (empty($author_obj)) {
					$author_obj = $this->user;
				}
			}
			else {
				$author_obj = $this->user;
			}
			
			if (!empty($owner_txt)) {
				$owner_obj = $this->findUserAndCreate($owner_txt);
				if (empty($owner_obj)) {
					$owner_obj = $this->user;
				}
			}
			else {
				$owner_obj = $this->user;
			}
			
			if(empty($id)){
			    if(!(trim($req->get('unid')))){
					$miscfunctions = new MiscFunctions();
			        $id = $miscfunctions->generateGuid();			        
			    }else{
    			    $id = trim($request->request->get('unid'));
			    }
			}
			$entity = new Documents();
			$entity->setId($id);
			$entity->setAuthor($author_obj);
			$entity->setCreator($owner_obj);
			$entity->setDateCreated(new \DateTime());
			$entity->setModifier($this->user);
			$entity->setDateModified(new \DateTime());
			$entity->setDocTitle($subject_txt);
			$entity->setDocType($result);
			$entity->setDocStatus(($result->getEnableLifecycle()) ? $result->getInitialStatus() : $result->getFinalStatus());
			$entity->setStatusNo(($result->getEnableLifecycle()) ? 0 : 1);
			$entity->setFolder($folder_obj);
			$entity->setKeywords($keywords_txt);
			$entity->setDescription($desc_txt);
			$entity->setOwner($owner_obj);
			if ($result->getEnableLifecycle() && $result->getEnableVersions()) {
				$entity->setDocVersion('0.0');
				$entity->setRevision(0);
			}
			$entity->setReviewType(trim($req->get('ReviewType')) ? $req->get('ReviewType') : 'P');
			if (trim($req->get('ReviewType')) == 'C')
			{
				$entity->setReviewPeriod((int)$req->get('ReviewPeriod'));
				$entity->setReviewPeriodOption($req->get('ReviewPeriodOption'));
				$entity->setReviewDateSelect($req->get('ReviewDateSelect'));
				$entity->setAuthorReview(trim($req->get('AuthorReview')) ? true : false);
				if (trim($req->get('ReviewStartDayOption')) && trim($req->get('ReviewStartMonth')) && trim($req->get('ReviewStartDay')))
				{
					$entity->setReviewStartMonth($req->get('ReviewStartMonth'));
					$entity->setReviewStartDay($req->get('ReviewStartDay'));
				}
				if (trim($req->get('Reviewers'))) {
				    $names = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($req->get('Reviewers')));
					for ($x = 0; $x < count($names); $x++) {
						if (false !== $reviewer = $this->findUserAndCreate($names[$x])) {
							$entity->addReviewers($reviewer);
						}
					}
				}
			}
			$entity->setArchiveType(trim($req->get('ArchiveType')) ? $req->get('ArchiveType') : 'P');
			if (trim($req->get('ArchiveType')) == 'C' && trim($req->get('CustomArchiveDate')))
			{
				$format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
				$value = date_create_from_format($format, $req->get('CustomArchiveDate'));
				$entity->setCustomArchiveDate($value);
			}

			$em->persist($entity);
			$em->flush();
			
			$security_check->createDocumentLog($em, 'CREATE', $entity);
			
			$customAcl->insertObjectAce($entity, $this->user, 'owner', false);
			$readers_added = false;
			if ($req->get('Readers')) {
				$readers = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $req->get('Readers'));
				for ($x = 0; $x < count($readers); $x++) {
					if (false !== $user_obj = $this->findUserAndCreate($readers[$x])) {
						$customAcl->insertObjectAce($entity, $user_obj, 'view', false);
						$readers_added = true;
					}
					elseif (false !== $group = $this->fetchGroupMembers($readers[$x]))
					{
						$customAcl->insertObjectAce($entity, $group->getRole(), 'view');
						$readers_added = true;
					}
				}
			}
			if($readers_added){
			    if (true === $customAcl->isRoleGranted($entity, 'ROLE_USER', 'view')) {
    			    $customAcl->removeUserACE($entity, 'ROLE_USER', 'view', true);
			    }
			}else{
			    $customAcl->insertObjectAce($entity, 'ROLE_USER', 'view', true);
			}
			
			if ($req->get('Authors')) {
				$authors = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $req->get('Authors'));
				for ($x = 0; $x < count($authors); $x++) {
					if (false !== $user_obj = $this->findUserAndCreate($authors[$x])) {
						$customAcl->insertObjectAce($entity, $user_obj, 'edit', false);
					}
					elseif (false !== $group = $this->fetchGroupMembers($authors[$x])) {
						$customAcl->insertObjectAce($entity, $group->getRole(), 'edit');
					}
				}
			}
/*			else {
				if (empty($readers)) {
					$customAcl->insertObjectAce($entity, 'ROLE_USER', 'edit');
				}
			}
*/
			if ($req->get('subforms')) {
				if (is_array($req->get('subforms'))) {
					$sub_id_arr = $req->get('subforms');
				}
				else {
					$sub_id_arr = array($req->get('subforms'));
				}
			
				for ($sf = 0; $sf < count($sub_id_arr); $sf++) {
					$sub_fields_name = $em->getRepository('DocovaBundle:DesignElements')->getSubformFields($sub_id_arr[$sf]);
					if (!empty($sub_fields_name)) {
						$matches = null;
						foreach ($sub_fields_name as $field) {
							preg_match('/^mcsf_dliuploader\d$/i', $field->getFieldName(), $matches);
							if (!empty($matches[0])) {
								if ($req->get('OFileNames')) {
									$file_dates = preg_split('/;/', $req->get('OFileDates'));
									$file_names = preg_split('/;/', $req->get('OFileNames'));
									if ($req->has('mcsf_dliuploader2')) {
										$field_filenames = preg_split('/,/', $req->get($field->getFieldName()));
									}
									else {
										$field_filenames = $file_names;
									}
									for ($i = 0; $i < count($file_names); $i++) {
										if (!in_array($file_names[$i], $field_filenames)) {
											unset($file_names[$i], $file_dates[$i]);
										}
									}
									$file_dates = array_values($file_dates);
									$file_names = array_values($file_names);
									if (!empty($file_names))
									{
										for ($i = 0; $i < count($file_names); $i++) {
											if (!empty($file_dates[$i])) {
											    $tempDateString = $file_dates[$i];
											    $format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
											    $parsed = date_parse($tempDateString);
											    $is_period = (false === stripos($tempDateString, ' am') && false === stripos($tempDateString, ' pm')) ? false : true;
											    $time = (false !== $parsed['hour'] && false !== $parsed['minute']) ? ' H:i:s' : '';
											    $time .= !empty($time) && $is_period === true ? ' A' : '';
											    $tempDate = date_create_from_format($format.$time, $tempDateString);											    
											    $file_dates[$i] = ($tempDate === false ? new \DateTime() : $tempDate);
											}
											else {
												$file_dates[$i] = new \DateTime();
											}
										}
										$file_content = $request->files;
	
										$res = $this->moveUploadedFiles($file_content, $file_names, $file_dates, $entity, $field);
										if ($res !== true) {
											throw $this->createNotFoundException('Could not upload files. - create');
										}
									}
								}
								elseif (!$req->get('OFileDates')) {
									$file_content = $request->files;
									
									if ($file_content->count()) 
									{
										$res = $this->moveUploadedFiles($file_content, array(), array(), $entity, $field);
										if ($res !== true) {
											throw $this->createNotFoundException('Could not upload files. - create');
										}
									}
								}
							}
							else {
								$separators = $summary = array();
								$fieldName = str_replace('[]', '', $field->getFieldName());
								$fieldName = (array_key_exists(strtolower($fieldName), $paramindex) ? $paramindex[strtolower($fieldName)] : $fieldName);
								
								if ($field->getMultiSeparator()) {
									$separators = implode('|', explode(' ', $field->getMultiSeparator()));
								}
								if (!empty($separators)) {
									$values = preg_split('/('.$separators.')/', $req->get($fieldName));
									for ($x = 0; $x < count($values); $x++) {
										$summary[] = substr($values[$x], 0, 450);
									}
								}
								else {
									$values = array(is_array($req->get($fieldName)) ? implode(';', $req->get($fieldName)) : $req->get($fieldName));
									$summary = array(substr($values[0], 0, 450));
								}

								$len = count($values);
								for ($x = 0; $x < $len; $x++) {
									if (!empty($values[$x]) || $values[$x] == '0')
									{
										if ($field->getFieldType() == 1) {
											$format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
											$parsed = date_parse($values[$x]);
											$is_period = (false === stripos($values[$x], ' am') && false === stripos($values[$x], ' pm')) ? false : true;
											$time = (false !== $parsed['hour'] && false !== $parsed['minute']) ? ' H:i:s' : '';
											$time .= !empty($time) && $is_period === true ? ' A' : '';
											$value = date_create_from_format($format.$time, $values[$x]);
	//										$value = $value->format('d/m/Y'.$time);
											
											$field_value = new FormDateTimeValues();
											$field_value->setFieldValue($value);
										}
										elseif ($field->getFieldType() == 3) {
											$name = $this->findUserAndCreate($values[$x], true, false);
											if (false !== $name) {
												$field_value = new FormNameValues();
												$field_value->setFieldValue($name);
											}
											else {
												$name = $this->fetchGroupMembers($values[$x]);
												if (false !== $name) {
													$field_value = new FormGroupValues();
													$field_value->setFieldValue($name);
												}
											}
										}
										elseif ($field->getFieldType() == 4) {
											$value = floatval($values[$x]);
											$field_value = new FormNumericValues();
											$field_value->setFieldValue($value);
										}
										else {
											$field_value = new FormTextValues();
											$field_value->setFieldValue($values[$x]);
											$field_value->setSummaryValue($summary[$x]);
										}
										if (!empty($field_value))
										{
											$field_value->setDocument($entity);
											$field_value->setField($field);
											$field_value->setOrder($len > 1 ? $x : null);
											$field_value->setTrash(false);
											$em->persist($field_value);
											$em->flush();
										}
									}
								}
							}
						}
					}
				}
			}
			
			if ($req->get('tmpRequestDataXml')) 
			{
				$request_data = new \DOMDocument('1.0', 'UTF-8');
				$request_data->loadXML($req->get('tmpRequestDataXml'));
				if ($request_data->getElementsByTagName('Action')->item(0)->nodeValue == 'RELEASEVERSION') 
				{
					$wfProcess = $this->get('docova.workflowprocess');
					$wfProcess->setUser($this->user);
					$wfProcess->setGs($this->global_settings);
					if (true === $wfProcess->releaseDocument($request_data, $entity)) 
					{
						$version = $request_data->getElementsByTagName('Version')->item(0)->nodeValue; 
						$security_check->createDocumentLog($em, 'UPDATE', $entity, 'Released document as version '.(!empty($version) ? $version : '0.0.0'));
					}
				}
			}
			
			if ($req->get('tmpWorkflowDataXml'))
			{
				$workflow_name = $req->get('wfWorkflowName');
				$workflow_obj = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('Workflow_Name' => $workflow_name));
				if (empty($workflow_obj))
				{
					$this->createNotFoundException('Unspecified source workflow with name = '. $workflow_name);
				}

				$wfProcess = $this->get('docova.workflowprocess');
				$wfProcess->setUser($this->user);
				$wfProcess->setGs($this->global_settings);
				$request_workflow = new \DOMDocument('1.0', 'UTF-8');
				$request_workflow->loadXML($req->get('tmpWorkflowDataXml'));
				if ($req->get('isWorkflowStarted') == 1 || $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue !== 'FINISH') {
					$wfProcess->createDocumentWorkflow($entity, $request_workflow);
				}
				
				if ($req->get('isWorkflowStarted') == 1)
				{
					$action = $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue;
					$nextnode = $request_workflow->getElementsByTagName('NextStep')->item(0)->nodeValue;
					//$steps = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findBy(array('Document' => $entity->getId()), array('Position' => 'ASC'));
					$step = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findBy(array('Document' => $entity->getId(), 'IsCurrentStep' =>true));
						
					if (!empty($steps[0]) )
					{
						
						if ($action === 'START')
						{
							$first_step = $step[0];
							//$next_order = $steps[1]->getPosition();
							$first_step->setStatus('Completed');
							$first_step->setDateCompleted(new \DateTime());
							$first_step->setIsCurrentStep(false);
							$completer = new WorkflowCompletedBy();
							$completer->setCompletedBy($this->user);
							$completer->setDocWorkflowStep($first_step);
							$completer->setGroupMember(false);
							$em->persist($completer);
							$em->flush();
							$completer = null;
							$em->refresh($first_step);
							$wfProcess->sendWfNotificationIfRequired($first_step, 2);
							$entity->setDocStatus($steps[1]->getDocStatus());
							
							$nextstep =  $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findBy(array('Document' => $entity->getId() ,'Position' => $nextnode));	
								
							if ( !empty($nextstep[0]))
							{
								$next_step = $nextstep[0];
								if ($next_step->getStatus() === 'Pending' && !$next_step->getDateStarted() && !$next_step->getDateCompleted())
								{
									$next_step->setIsCurrentStep(true);
									$next_step->setDateStarted(new \DateTime());
									$entity->setDocStatus($next_step->getDocStatus());
									$em->flush();
									$em->refresh($next_step);
									$wfProcess->sendWfNotificationIfRequired($next_step, 1);
									
								}
							}
							$em->flush();
							$em->refresh($steps[1]);
							$wfProcess->sendWfNotificationIfRequired($steps[1], 1);
							$is_workflow_started = true;
						}
						elseif ($first_step->getDocStatus())
						{
							$entity->setDocStatus($first_step->getDocStatus());
							$em->flush();
						}
					}
				}
				else {
					$action = $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue;
					if ($action === 'FINISH' && $workflow_obj->getBypassRleasing()) {
						if (true == $wfProcess->releaseDocument($request_workflow, $entity, true)) {
							$security_check->createDocumentLog($em, 'WORKFLOW', $entity, 'Completed document workflow.');
						}
					}
				}
			}
			
			if ($req->get('tmpLinkDataXml')) {
				$reldocrequests = new \DOMDocument();
				$reldocrequests->loadXML('<RequestRoot>'.$req->get('tmpLinkDataXml').'</RequestRoot>');
				
				foreach ($reldocrequests->getElementsByTagName('Request') as $requestelem)
				{
				    foreach($requestelem->getElementsByTagName('Unid') as $unidelem)
				{
    					$tmp_document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => trim($unidelem->textContent), 'Trash' => false, 'Archived' => false));
					
					if (!empty($tmp_document)) 
					{
						$relation_exists = $em->getRepository('DocovaBundle:RelatedDocuments')->findOneBy(array('Parent_Doc' => $entity, 'Related_Doc' => $tmp_document));
						if (empty($relation_exists))
						{
							$related_doc_obj = new RelatedDocuments();
							$related_doc_obj->setParentDoc($entity);
							$related_doc_obj->setRelatedDoc($tmp_document);

							$em->persist($related_doc_obj);
							$em->flush();
						}
					}
					unset($tmp_document, $related_doc_obj, $relation_exists);
				    }
				}
			}

			if (trim($result->getTranslateSubjectTo())) {
				$value = trim($result->getTranslateSubjectTo());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$docova->setDocument($entity, $folder_obj->getLibrary());
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$subject_txt = $function($docova);
				}
				else {
					$subject_txt = $value;
				}
				
				$entity->setDocTitle($subject_txt);
				$em->flush();
			}

			if ($mobile === 'mDocument') {
				return $this->redirect($this->generateUrl('docova_mobile').'#folder?libid='.$folder_obj->getLibrary()->getId().'&folderkey='.$folder_obj->getId().'&folderid='.$folder_obj->getId().'&listtitle='.$folder_obj->getFolderName());
			}
			elseif ($request->query->get('mode') == 'dle') {
				$xml_result = new \DOMDocument('1.0', 'UTF-8');
				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$newnode = $xml_result->createElement('Result', 'OK');
				$attrib = $xml_result->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
				$newnode = $xml_result->createElement('Unid', $entity->getId());
				$root->appendChild($newnode);
				$newnode = $xml_result->createElement('Url', $this->generateUrl('docova_homepage'));
				$root->appendChild($newnode);
				
				$response = new Response();
				$response->headers->set('Content-Type', 'text/xml');
				$response->setContent($xml_result->saveXML());
				return $response;
			}
			else {
				if ($req->get('tmpMode') == 'nodoe') {
					$response = new Response();
					$response->setContent('url;' . $this->generateUrl('docova_blankcontent') . '?OpenPage&docid=' . $entity->getId().'&fid='.$folder_obj->getId());
					return $response;
				}
				else {
					return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$entity->getId().'&fid='.$folder_obj->getId());
				}
			}
		}
		$miscfunctions = new MiscFunctions();
		$id = $miscfunctions->generateGuid();
		
		$formula_values = array();
		if (trim($result->getCustomSubjectFormula()))
		{
			$value = trim($result->getCustomSubjectFormula());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Subjec_Value'] = $function($docova);
			}
			else {
				$formula_values['Subjec_Value'] = $value;
			}
		}
		if (trim($result->getCustomReleaseButtonLabel()))
		{
			$value = trim($result->getCustomReleaseButtonLabel());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Release_Label'] = $function($docova);
			}
			else {
				$formula_values['Release_Label'] = $value;
			}
		}
		if (trim($result->getCustomChangeDocAuthor()))
		{
			$value = trim($result->getCustomChangeDocAuthor());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Changing_Author_Enabled'] = $function($docova);
			}
		}
		if (trim($result->getCustomHideDescription()))
		{
			$value = trim($result->getCustomHideDescription());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Description'] = $function($docova);
			}
		}
		if (trim($result->getCustomHideKeywords()))
		{
			$value = trim($result->getCustomHideKeywords());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Keywords'] = $function($docova);
			}
		}
		if (trim($result->getCustomHideReviewCycle()))
		{
			$value = trim($result->getCustomHideReviewCycle());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Review_Cycle'] = $function($docova);
			}
		}
		if (trim($result->getCustomHideArchiving()))
		{
			$value = trim($result->getCustomHideArchiving());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Archiving'] = $function($docova);
			}
		}
		if (trim($result->getCustomChangeOwner()))
		{
			$value = trim($result->getCustomChangeOwner());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Changing_Owner_Enabled'] = $function($docova);
			}
		}
		if (trim($result->getCustomChangeAddEditors()))
		{
			$value = trim($result->getCustomChangeAddEditors());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Changing_Editors_Enabled'] = $function($docova);
			}
		}
		if (trim($result->getCustomChangeReadAccess()))
		{
			$value = trim($result->getCustomChangeReadAccess());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Changing_Readers_Enabled'] = $function($docova);
			}
		}
		if (trim($result->getCustomRestrictPrinting()))
		{
			$value = trim($result->getCustomRestrictPrinting());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Restrict_Printing'] = $function($docova);
			}
		}
		if (trim($result->getSaveButtonHideWhen()))
		{
			$value = trim($result->getSaveButtonHideWhen());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Save_Close'] = $function($docova);
			}
		}
		if (trim($result->getCustomHeaderHideWhen()))
		{
			$value = trim($result->getCustomHeaderHideWhen());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Hide_Header'] = $function($docova);
			}
		}
		if (trim($result->getCustomButtonsHideWhen()))
		{
			$value = trim($result->getCustomButtonsHideWhen());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Hide_Release'] = $function($docova);
			}
		}

		if (!empty($folder_managers) && count($folder_managers) > 0) 
		{
			foreach ($folder_managers as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true)) 
				{
					$folder_managers[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}

		if (!empty($folder_editors) && count($folder_editors) > 0)
		{
			foreach ($folder_editors as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true))
				{
					$folder_editors[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}

		if (!empty($folder_authors) && count($folder_authors) > 0)
		{
			foreach ($folder_authors as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true))
				{
					$folder_authors[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}

		if (!empty($folder_readers) && count($folder_readers) > 0)
		{
			foreach ($folder_readers as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true))
				{
					$folder_readers[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}

		$is_mobile = ($mobile === 'mDocument') ? 'Mobile:mEditDocument.html.twig' : 'Default:genericDocumentTemplate.html.twig';
		return $this->render("DocovaBundle:$is_mobile", array(
				'newunid' => empty($id) ? "" : $id,
    			'unid' => empty($id) ? "" : $id,
				'user' => $this->user,
				'settings' => $this->global_settings,
				'user_access' => $access_level,
				'folder_info' => $folder_obj,
				'document_type' => $result,
				'subforms' => $rendered_subforms,
				'action_buttons' => $action_buttons,
				'workflows' => $rendered_workflows,
				'workflow_options' => $doc_workflow_options,
				'is_wfStarted' => $is_workflow_started,
				'link_comments' => $link_comments,
				'rel_doc_settings' => $related_doc_settings,
				'has_attachment' => $has_attachment,
				'attachment_settings' => $attachment_settings,
				'text_content_type' => $text_content_type,
				'incompleteEdits' => '',
				'templates' => $templates,
				'doc_readers' => '',
				'doc_editors' => '',
				'managers' => $folder_managers,
				'folder_editors' => $folder_editors,
				'folder_authors' => $folder_authors,
				'folder_readers' => $folder_readers,
				'translated_values' => $formula_values,
				'document_detail' => null,
				'document' => null
		));
	}
	
	public function readDocumentAction(Request $request, $doc_id)
	{
		//*********External View Processing Extension**********
		$dataViewId = $request->query->get('DataView');
		if (!empty($dataViewId)){
			$folder_id = $request->query->get('ParentUNID');			
			$em = $this->getDoctrine()->getManager();
			$dataView = $em->getRepository("DocovaBundle:DataViews")->find($dataViewId);
			$dataSource = !empty($dataView) ? $dataView->getDataSource($em) : null;
			
			if (!empty($dataSource)){			
					$extProcessed = ExternalViews::readDocument($this, $this->container->getParameter("document_root"),$doc_id, $folder_id, $dataView, $dataSource );
					if ($extProcessed!=null)
						return $extProcessed;
			}
		}
		//********************************************
		
		if (!empty($doc_id)) {
			$this->initialize();
			$is_mobile = $request->query->get('Mobile');
			$mobile_app_device=$request->query->get('device'); // this is coming from mobile app
			$folder_id = $request->query->get('ParentUNID');

			if ($is_mobile == 'true' ||  in_array($mobile_app_device, array("android","iOS") ) ) {
				$is_mobile=true;
			}else{
				$is_mobile=false;
			}

/*			
			if (empty($folder_id) && !$is_mobile) {
				throw $this->createNotFoundException('Folder id is missed.');
			}
*/
			
			$subforms_arr = $rendered_subforms = array();
			
			$bookmarked = false;
			$em = $this->getDoctrine()->getManager();
			if ($request->query->get('IsBookmark'))
			{
				$bmk = $em->getRepository('DocovaBundle:Bookmarks')->findOneBy(array('Document' => $doc_id, 'Target_Folder' => $folder_id));//, 'Created_By' => $this->user->getId()));
				if (!empty($bmk))
				{
					$document = $bmk->getDocument();
					$bookmarked = true;
					unset($bmk);
				}
			}
			elseif (($datasrc = $request->query->get('datadocsrc')) && $datasrc === 'A') {
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id, 'Archived' => true));
			}
			else {
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id));
			}
			if (empty($document)) {
				throw $this->createNotFoundException('Document information could not be found');
			}
/*
			if (!empty($folder_id) && $folder_id !== 'wSearchResults') {
				$folder_obj = $em->find('DocovaBundle:Folders', $folder_id);
			}
			else {
*/
			$folder_obj = $document->getFolder();
//			}

			$user = $em->getReference('DocovaBundle:UserAccounts', $this->user->getId());
			$library = $folder_obj->getLibrary();
			$customAcl = new CustomACL($this->container);
			if (false === $customAcl->isUserGranted($library, $user, 'delete'))
			{
				$security_check = $this->container->get('security.authorization_checker');;
				if (false !== $security_check->isGranted('VIEW', $library)) {
					$customAcl->insertObjectAce($library, $user, 'delete', false);
				}
			}
			$user = $security_check = null;
				
			$security_check = new Miscellaneous($this->container);
/*			$folder_manager = $security_check->isFolderManagers($folder_obj);

			while ($folder_obj) {
				if ($security_context->isGranted('MASTER', $folder_obj)) {
					$folder_manager = true;
					break;
				}
				$folder_obj = $folder_obj->getParentfolder();
			}
*/
			if ($bookmarked !== true && false === $security_check->canReadDocument($document, true)) {
				if ($document->getDocSteps()->count() > 0)
				{
					$steps = $document->getDocSteps();
					$found = false;
					foreach ($steps as $step)
					{
						if ($step->getStatus() === 'Pending' && $step->getDateStarted()) {
							if (true === $this->searchUserInCollection($step->getAssignee(), $this->user))
							{
								$found = true;
								break;
							}
						}
					}
	
					if ($found === false) {
						throw new AccessDeniedException();
					}
				}
				else {
					throw new AccessDeniedException();
				}
			}
			
			$access_level = $security_check->getAccessLevelForDocument($document);
			if ($access_level['docacess'] == 2 && $document->getStatusNo() == 1 && $document->getDocType()->getEnableVersions() && ($document->getOwner()->getId() == $this->user->getId() || $document->getCreator()->getId() == $this->user->getId())) {
				$access_level['docacess'] = 3;
			}

			$folder_managers = $folder_editors = $folder_authors = $folder_readers = $authors = $readers = $available_versions = array();
			$is_initial_version = $has_draft = $is_denied = '';
			$users_list = $customAcl->getObjectACEUsers($folder_obj, 'owner');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user) {
					$folder_managers[] = $user->getUsername();
					$authors[] = $user->getUsername();
				}
			}
			$groups_list = $customAcl->getObjectACEGroups($folder_obj, 'owner');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						$folder_managers[] = $groupname;
						$authors[] = $groupname;
					}
				}
			}
			$users_list = $customAcl->getObjectACEUsers($folder_obj, 'master');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user) {
					if (!in_array($user->getUsername(), $folder_editors)) {
						$folder_editors[] = $user->getUsername();
						$authors[] = $user->getUsername();
					}
				}
			}
			$groups_list = $customAcl->getObjectACEGroups($folder_obj, 'master');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						if (!in_array($groupname, $folder_editors)) {
							$folder_editors[] = $groupname;
							$authors[] = $groupname;
						}
					}
				}
			}

			$users_list = $customAcl->getObjectACEUsers($folder_obj, 'create');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user)
				{
					if (!in_array($user->getUsername(), $folder_authors)) {
						$folder_authors[] = $user->getUsername();
					}
					if (!in_array($user->getUsername(), $authors)) {
						$authors[] = $user->getUsername();
					}
				}
			}
			$groups_list = $customAcl->getObjectACEGroups($folder_obj, 'create');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						if (!in_array($groupname, $folder_authors)) {
							$folder_authors[] = $groupname;
						}
						if (!in_array($groupname, $authors)) {
							$authors[] = $groupname;
						}
					}
				}
			}

			$ancestors = $em->getRepository('DocovaBundle:Folders')->getAncestors($folder_obj->getPosition(), $folder_obj->getLibrary()->getId(), $folder_obj->getId(), true);
			foreach ($ancestors as $folder)
			{
				$folder = $em->getReference('DocovaBundle:Folders', $folder['id']);
				$users_list = $customAcl->getObjectACEUsers($folder, 'view');
				if ($users_list->count() > 0)
				{
					foreach ($users_list as $user)
					{
						if (!in_array($user->getUsername(), $folder_readers)) {
							$folder_readers[] = $user->getUsername();
							$readers[] = $user->getUsername();
						}
					}
				}
				$groups_list = $customAcl->getObjectACEGroups($folder, 'view');
				if ($groups_list->count() > 0)
				{
					foreach ($groups_list as $group) {
						if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
						{
							$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
							if (!in_array($groupname, $folder_readers)) {
								$folder_readers[] = $groupname;
								$readers[] = $groupname;
							}
						}
					}
				}
			}
			unset($users_list, $user, $groups_list, $group);

			$doc_editors = $doc_readers = array();
			$text_content_type = $link_comments = '';
							
			$user_list = $customAcl->getObjectACEUsers($document, 'edit');
			if ($user_list->count() > 0) {
				for ($x = 0; $x < $user_list->count(); $x++) {
					$doc_editors[] = $user_list[$x]->getUsername();
					if (!in_array($user_list[$x]->getUsername(), $authors)) {
						$authors[] = $user_list[$x]->getUsername();
					}
				}
			}
			$groups_list = $customAcl->getObjectACEGroups($document, 'edit');
			if ($groups_list->count() > 0) {
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						$doc_editors[] = $groupname;
						if (!in_array($groupname, $authors)) {
							$authors[] = $groupname;
						}
					}
				}
			}

			$user_list = $customAcl->getObjectACEUsers($document, 'view');
			if ($user_list->count() > 0) {
				for ($x = 0; $x < $user_list->count(); $x++) {
					$doc_readers[]= $user_list[$x]->getUsername();
					if (!in_array($user_list[$x]->getUsername(), $readers)) {
						$readers[] = $user_list[$x]->getUsername();
					}
				}
			}
			$groups_list = $customAcl->getObjectACEGroups($document, 'view');
			if ($groups_list->count() > 0) {
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						$doc_readers[] = $groupname;
						if (!in_array($groupname, $readers)) {
							$readers[] = $groupname;
						}
					}
				}
			}
			
			$customAcl = null;

			if (!empty($doc_editors) && count($doc_editors) > 0)
			{
				foreach ($doc_editors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true, false))
					{
						$doc_editors[$index] = $this->global_settings->getUserDisplayDefault() ? $user->getUserProfile()->getDisplayName() : $user->getUserNameDnAbbreviated();
					}
					unset($user);
				}
			}

			if (!empty($doc_readers) && count($doc_readers) > 0)
			{
				foreach ($doc_readers as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true, false))
					{
						$doc_readers[$index] = $this->global_settings->getUserDisplayDefault() ? $user->getUserProfile()->getDisplayName() : $user->getUserNameDnAbbreviated();
					}
					unset($user);
				}
			}

			$doc_editors = array_unique($doc_editors);
			$doc_readers = array_unique($doc_readers);
			$doc_editors = (!empty($doc_editors)) ? implode(',', $doc_editors) : '';
			$doc_readers = (!empty($doc_readers)) ? implode(',', $doc_readers) : '';
			$editable = $security_check->canEditDocument($document, true);
			
			$workflow_steps = $document->getDocSteps();
			$attachment_settings = $related_doc_settings = $templates = array();
			$docova = $this->get('docova.objectmodel');
			$docova->setUser($this->user);
			$docova->setDocument($document, $library);
			$library = null;
			$rendered_workflows = $doc_workflow_options = $action_buttons = $button_labels = array();
			if (!$document->getDocType()->getTrash())
			{
				if ($workflow_steps->count() > 0)
				{
					$is_workflow_completed = true;
					$workflow = array(
						'Workflow_Name' => $workflow_steps[0]->getWorkflowName(),
						'Author_Customization' => $workflow_steps[0]->getAuthorCustomization(),
						'Bypass_Rleasing' => $workflow_steps[0]->getBypassRleasing()
					);
					foreach ($workflow_steps as $step)
					{
						if ($step->getStatus() !== 'Completed' && $step->getStatus() !== 'Approved') {
							$is_workflow_completed = false;
							break;
						}
					}
					
					if ($is_workflow_completed === false) 
					{
						$is_denied = false;
						$docstep = array(
								'document_id' => $doc_id,
								'isStarted' => '',
								'isOriginator' => '',
								'isPendingParticipant' => '',
								'isApprover' => '',
								'isReviewer' => '',
								'isPublisher' => '',
								'isStartStep' => '',
								'isEndStep' => '',
								'isCompleteStep' => '',
								'isApproveStep' => '',
								'isDelegate' => '',
								'AllowInfoRequest' => '',
								'AllowUpdate' => '',
								'AllowPause' => '',
								'AllowCustomize' => '',
								'AllowBacktrack' => '',
								'AllowCancel' => ''
						);
						foreach ($workflow_steps as $index => $step)
						{
							if ($step->getStepType() === 1 && $step->getStatus() === 'Completed')
							{
								$docstep['isStarted'] = true;
							}
							if ($step->getStepType() === 1 && true === $this->searchUserInCollection($step->getAssignee(), $this->user))
							{
								$docstep['isOriginator'] = true;
							}
							$participants = $step->getOtherParticipant();
							if ($participants->count() > 0)
							{
								foreach ($participants as $puser)
								{
									if ($puser->getUserNameDnAbbreviated() === $this->user->getUserNameDnAbbreviated()) {
										$docstep['isPendingParticipant'] = true;
										break;
									}
								}
							}
							if (count($step->getOtherParticipantGroups()) > 0 && !$docstep['isPendingParticipant'])
							{
								foreach ($step->getOtherParticipantGroups() as $group) {
									if (true === $this->fetchGroupMembers($group, false, $this->user))
									{
										$docstep['isPendingParticipant'] = true;
										break;
									}
								}
							}
							if ($step->getStepType() === 3 && true === $this->searchUserInCollection($step->getAssignee(), $this->user))
							{
								$docstep['isApprover'] = true;
							}
							if ($step->getStepType() === 2 && true === $this->searchUserInCollection($step->getAssignee(), $this->user))
							{
								$docstep['isReviewer'] = true;
							}
							if ($step->getStepType() === 4 && true === $this->searchUserInCollection($step->getAssignee(), $this->user))
							{
								$docstep['isPublisher'] = true;
							}
							if ($step->getStatus() === 'Denied')
							{
								$is_denied = true;
							}
							if (empty($current_step) && $step->getStatus() !== 'Completed' && $step->getStatus() !== 'Approved')
							{
								$current_step = $step;
							}
						}

						if (!empty($current_step))
						{
							$docstep['isStartStep'] = ($current_step->getStepType() === 1) ? true : '';
							$docstep['isEndStep'] = ($current_step->getStepType() === 4) ? true : '';
							$docstep['isCompleteStep'] = ($current_step->getStepType() === 3 && $docstep['isPendingParticipant'] === true && ($current_step->getStatus() === 'Pending' || $current_step->getStatus() === 'Paused')) ? true : '';
							$docstep['isApproveStep'] = ($current_step->getStepType() === 4 && $docstep['isPendingParticipant'] === true && ($current_step->getStatus() === 'Pending' || $current_step->getStatus() === 'Paused')) ? true : '';
							$docstep['AllowInfoRequest'] = ($docstep['isOriginator'] === true || !empty($docstep['isApprover']) || !empty($docstep['isReviewer']) || $access_level['docacess'] == 6 || $document->getOwner()->getId() == $this->user->getId()) ? true : '';
							$docstep['AllowUpdate'] = ($docstep['isOriginator'] === true || $access_level['docacess'] == 6 || $document->getOwner()->getId() == $this->user->getId()) ? true : '';
							$docstep['AllowPause'] = ($current_step->getStatus() !== 'Paused' && ($docstep['isOriginator'] === true || $access_level['docacess'] == 6 || $document->getOwner()->getId() == $this->user->getId())) ? true : '';
							$docstep['AllowCustomize'] = ($workflow['Author_Customization'] == true && $current_step->getStepType() !== 4 &&  ($docstep['isOriginator'] === true || $access_level['docacess'] == 6 || $document->getOwner()->getId() == $this->user->getId())) ? true : '';
							$docstep['AllowBacktrack'] = ($docstep['isOriginator'] === true || $access_level['docacess'] == 6 || $document->getOwner()->getId() == $this->user->getId()) ? true : '';
							$docstep['AllowCancel'] = ($docstep['isOriginator'] === true || $access_level['docacess'] == 6 || $document->getOwner()->getId() == $this->user->getId()) ? true : '';
							$complete_review = $approve = $decline = $release = false;
							if (trim($current_step->getCustomReviewButtonLabel()))
							{
								$value = trim($current_step->getCustomReviewButtonLabel());
								if (preg_match('~^[<?].*[?>]$~', $value))
								{
									$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
									$function = create_function('$docova', $value);
									$complete_review = $function($docova);
								}
								else {
									$complete_review = $value;
								}
							}
							if (trim($current_step->getCustomApproveButtonLabel()))
							{
								$value = trim($current_step->getCustomApproveButtonLabel());
								if (preg_match('~^[<?].*[?>]$~', $value))
								{
									$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
									$function = create_function('$docova', $value);
									$approve = $function($docova);
								}
								else {
									$approve = $value;
								}
							}
							if (trim($current_step->getCustomDeclineButtonLabel()))
							{
								$value = trim($current_step->getCustomDeclineButtonLabel());
								if (preg_match('~^[<?].*[?>]$~', $value))
								{
									$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
									$function = create_function('$docova', $value);
									$decline = $function($docova);
								}
								else {
									$decline = $value;
								}
							}
							if (trim($current_step->getCustomReleaseButtonLabel()))
							{
								$value = trim($current_step->getCustomReleaseButtonLabel());
								if (preg_match('~^[<?].*[?>]$~', $value))
								{
									$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
									$function = create_function('$docova', $value);
									$release = $function($docova);
								}
								else {
									$release = $value;
								}
							}

							$button_labels = array(
								'Complete_Review' => $complete_review,
								'Approve' => $approve,
								'Decline' => $decline,
								'Release_Document' => $release
							);
						}
						
						if ($is_denied === true)
						{
							$docstep['isApprover'] = '';
							$docstep['isReviewer'] = '';
							$docstep['isPublisher'] = '';
						}

						$stepsxml = "";
						$wf_nodes_xmlObj = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Documents')->getWorkflowStepsXML($document, $this->user, $this->global_settings, false);
						$stepsxml = $wf_nodes_xmlObj->saveXML();
				

						$mobileExtension = $is_mobile === true ? '_m' : '';
						$rendered_workflows[] = $this->renderView("DocovaBundle:Subform:sfDocSection-Workflow$mobileExtension.html.twig", array(
							'workflow' => $workflow,
							'workflow_xml' => '',
							'workflow_xml_nodes' => $stepsxml,
							'step' => $current_step,
							'docstep' => $docstep,
							'doctype' => $document->getDocType() 
						));

						$workflow_detail = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
						if (!empty($workflow_detail)) {
							$doc_workflow_options['JSHeader'] = file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('bundles/docova/js/custom/'.$workflow_detail->getFormFileName().$mobileExtension.'.js')) ? $workflow_detail->getFormFileName().$mobileExtension : false;
							$wf_buttons = $workflow_detail->getActionButtons();
							if ($wf_buttons->count() > 0 && $bookmarked === false)
							{
								foreach ($wf_buttons as $button)
								{
									if (!empty($button_labels[$button->getActionName()]))
									{
										$action_buttons[$button->getActionName()]['Label']	= $button_labels[$button->getActionName()];
									}
									elseif ($button->getActionName() === 'Start_Workflow' && trim($document->getDocType()->getCustomStartButtonLabel()))
									{
										$value = trim($document->getDocType()->getCustomStartButtonLabel());
										if (preg_match('~^[<?].*[?>]$~', $value))
										{
											$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
											$function = create_function('$docova', $value);
											$action_buttons[$button->getActionName()]['Label'] = $function($docova);
										}
										else {
											$action_buttons[$button->getActionName()]['Label'] = $value;
										}
									}
									else {
										$action_buttons[$button->getActionName()]['Label']	= $button->getButtonLabel();
									}
									$action_buttons[$button->getActionName()]['Script']	= $button->getClickScript();
									$action_buttons[$button->getActionName()]['Primary'] = $button->getPrimaryIcon();
									$action_buttons[$button->getActionName()]['Secondary'] = $button->getSecondaryIcon();
									if ($button->getVisibleOn())
									{
										$function = create_function($button->getVisibleOn()->getFunctionArguments(), $button->getVisibleOn()->getFunctionScript());
										$res = $function($document, $this->user);
										$action_buttons[$button->getActionName()]['Visible'] = $res;
									}
									else
									{
										$action_buttons[$button->getActionName()]['Visible'] = true;
									}
								}
							}
						}
						unset($workflow_detail);
						
						$doc_workflow_options['isCreated'] = true;
						$doc_workflow_options['isCompleted'] = $is_workflow_completed;
						$doc_workflow_options['createInDraft'] = true;
					}
				}
				$subform_obj = $document->getDocType()->getDocTypeSubform();
				if (!empty($subform_obj)) {
					foreach ($subform_obj as $object) {
						$subforms_arr[$object->getSubform()->getFormFileName()]['elements'] = $object->getSubform()->getSubformFields();
						if (file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('bundles/docova/js/custom/'.$object->getSubform()->getFormFileName().'.js')) || file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('upload/js/'.$object->getSubform()->getFormFileName().'.js'))) {
							$subforms_arr[$object->getSubform()->getFormFileName()]['JSHeader'] = $object->getSubform()->getFormFileName();
						}
						else {
							$subforms_arr[$object->getSubform()->getFormFileName()]['JSHeader'] = false;
						}
						if ($object->getPropertiesXML()) {
							$xml_properties = new \DOMDocument();
							$xml_properties->loadXML(str_replace('&','&amp;',$object->getPropertiesXML()));
							$subforms_arr[$object->getSubform()->getFormFileName()]['Properties'] = $xml_properties;
				
							if (in_array('TXT', $document->getDocType()->getContentSections()) || in_array('RTXT', $document->getDocType()->getContentSections()) || in_array('DRTXT', $document->getDocType()->getContentSections())) {
								$text_content_type = (in_array('RTXT', $document->getDocType()->getContentSections()) || in_array('DRTXT', $document->getDocType()->getContentSections())) ? 'HTML' : 'TEXT';
							}
	
							if (isset($xml_properties->getElementsByTagName('LinkComments')->item(0)->nodeValue)) {
								$link_comments = $xml_properties->getElementsByTagName('LinkComments')->item(0)->nodeValue;
							}
							
							if (isset($xml_properties->getElementsByTagName('RelatedDocOpenMode')->item(0)->nodeValue)) {
								$related_doc_settings['RelatedDocOpenMode'] = $xml_properties->getElementsByTagName('RelatedDocOpenMode')->item(0)->nodeValue;
								$related_doc_settings['EnableLinkedFiles'] = $xml_properties->getElementsByTagName('EnableLinkedFiles')->item(0)->nodeValue;
								$related_doc_settings['LaunchLinkedFiles'] = $xml_properties->getElementsByTagName('LaunchLinkedFiles')->item(0)->nodeValue;
								$related_doc_settings['EnableXLink'] = $xml_properties->getElementsByTagName('EnableXLink')->item(0)->nodeValue;
								$related_doc_settings['OMUserSelectDocTypeKey'] = $xml_properties->getElementsByTagName('OMUserSelectDocTypeKey')->item(0)->nodeValue;
								$related_doc_settings['OMLatestDocTypeKey'] = $xml_properties->getElementsByTagName('OMLatestDocTypeKey')->item(0)->nodeValue;
								$related_doc_settings['OMLinkedDocTypeKey'] = $xml_properties->getElementsByTagName('OMLinkedDocTypeKey')->item(0)->nodeValue;
							}
	
							if ((in_array('ATT', $document->getDocType()->getContentSections()) || in_array('ATTI', $document->getDocType()->getContentSections())) && isset($xml_properties->getElementsByTagName('HasMultiAttachmentSection')->item(0)->nodeValue)) {
								$attachment_settings['HasMultiAttachmentSection'] = $xml_properties->getElementsByTagName('HasMultiAttachmentSection')->item(0)->nodeValue;
								$attachment_settings['EnableFileCIAO'] = $xml_properties->getElementsByTagName('EnableFileCIAO')->item(0)->nodeValue;
								$attachment_settings['EnableFileViewLogging'] = $xml_properties->getElementsByTagName('EnableFileViewLogging')->item(0)->nodeValue;
								$attachment_settings['EnableFileDownloadLogging'] = $xml_properties->getElementsByTagName('EnableFileDownloadLogging')->item(0)->nodeValue;
								$attachment_settings['MaxFiles'] = $xml_properties->getElementsByTagName('MaxFiles')->item(0)->nodeValue;
								$attachment_settings['AllowedFileExtensions'] = $xml_properties->getElementsByTagName('AllowedFileExtensions')->item(0)->nodeValue;
								$attachment_settings['AttachmentReadOnly'] = $xml_properties->getElementsByTagName('AttachmentReadOnly')->item(0)->nodeValue;
								$attachment_settings['HideOnEditing'] = $xml_properties->getElementsByTagName('HideOnEditing')->item(0)->nodeValue;
								$attachment_settings['EnableLocalScan'] = $xml_properties->getElementsByTagName('EnableLocalScan')->item(0)->nodeValue;
							}
							
							if (isset($xml_properties->getElementsByTagName('TemplateType')->item(0)->nodeValue)) 
							{
								$templates['Template_Type'] 		= $xml_properties->getElementsByTagName('TemplateType')->item(0)->nodeValue;
								$templates['Template_Auto_Attach']	= $xml_properties->getElementsByTagName('TemplateAutoAttachment')->item(0)->nodeValue;
								$templates['Template_List']			= str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateKey')->item(0)->nodeValue);
								$templates['Template_Name_List']	= str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateName')->item(0)->nodeValue);
								$templates['Template_File_List']	= str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateFileName')->item(0)->nodeValue);
								$templates['Template_Version_list'] = str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateVersion')->item(0)->nodeValue);
							}
						}
						else {
							$subforms_arr[$object->getSubform()->getFormFileName()]['Properties'] = '';
						}
					}
				
					foreach ($subforms_arr as $form_name => $form_elements) {
						$more_section = null;
						if (!empty($form_elements['Properties']) && !empty($form_elements['Properties']->getElementsByTagName('DisplayInMore')->item(0)->nodeValue) && $form_elements['Properties']->getElementsByTagName('DisplayInMore')->item(0)->nodeValue == 1)
						{
							if (!empty($form_elements['Properties']->getElementsByTagName('SubFormTabName')->item(0)->nodeValue)) {
								$more_section = array('Tab_Name' => $form_elements['Properties']->getElementsByTagName('SubFormTabName')->item(0)->nodeValue);
							}
							elseif (!empty($form_elements['Properties']->getElementsByTagName('RelatedDocOpenMode')->item(0)->nodeName)) {
								$more_section = array('Tab_Name' => 'Related Documents');
							}
						}
						$rendered_subforms[] = array(
								'More_Section' => $more_section,
								'HTML' => $this->renderSubform($form_name, $form_elements['elements'], $editable, $document->getDocType(), $folder_obj, $is_mobile, $form_elements['Properties'], $document, true),
								'JSHeader' => $form_elements['JSHeader']
						);
					}
				}
	
				if ($document->getDocType()->getEnableLifecycle() && $document->getDocType()->getEnableVersions())
				{
				    $parentdoc = $document->getParentDocument();
				    $dockey = (empty($parentdoc) ? $document->getId() : $parentdoc->getId());
				    unset($parentdoc);
					$all_versions = $em->getRepository('DocovaBundle:Documents')->getAllDocVersionsFromParent($dockey);
					if (!empty($all_versions))
					{
						foreach ($all_versions as $index => $doc)
						{
							if ($doc->getStatusNo() == 0 && $doc->getId() != $document->getId())
							{
								$has_draft = true;
								break;
							}
							unset($index, $doc);
						}

						if (count($all_versions) == 1 || $all_versions[count($all_versions) - 1]->getId() == $document->getId()) 
						{
							$is_initial_version = 'true';
							$pfv = $document->getDocVersion().'.'.$document->getRevision();
						}
						else {
							foreach ($all_versions as $index => $doc)
							{
								if ($doc->getId() == $document->getId()) {
									$pfv = $all_versions[$index + 1]->getDocVersion().'.'.$all_versions[$index + 1]->getRevision();
									break;
								}
							}
						}
	
						if (!empty($pfv))
						{
							$pfv = explode('.', $pfv);
							
							$v1 = ($pfv[0] + 1).'.0.0';
							$v2 = (empty($pfv[0]) ? '0' : $pfv[0]).'.'.($pfv[1] + 1).'.0';
							$v3 = (empty($pfv[0]) ? '0' : $pfv[0]).'.'.(empty($pfv[1]) ? '0' : $pfv[1]).'.'.($pfv[2] + 1);
							
							foreach ($all_versions as $doc) 
							{
								$v1 = ($doc->getDocVersion().'.'.$doc->getRevision() == $v1 && $v1 != $document->getDocVersion().'.'.$document->getRevision()) ? '' : $v1;
								$v2 = ($doc->getDocVersion().'.'.$doc->getRevision() == $v2 && $v2 != $document->getDocVersion().'.'.$document->getRevision()) ? '' : $v2;
								$v3 = ($doc->getDocVersion().'.'.$doc->getRevision() == $v3 && $v3 != $document->getDocVersion().'.'.$document->getRevision()) ? '' : $v3;
							}
							
							!empty($v1) ? array_push($available_versions, $v1) : '';
							!empty($v2) ? array_push($available_versions, $v2) : '';
							!empty($v3) ? array_push($available_versions, $v3) : '';
							$available_versions = array_unique($available_versions);
						}
					}
					unset($all_versions);
				}
			}
			$formula_values = array();
			if (trim($document->getDocType()->getCustomReleaseButtonLabel()))
			{
				$value = trim($document->getDocType()->getCustomReleaseButtonLabel());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$formula_values['Release_Label'] = $function($docova);
				}
				else {
					$formula_values['Release_Label'] = $value;
				}
			}
			if (trim($document->getDocType()->getCustomHideDescription()))
			{
				$value = trim($document->getDocType()->getCustomHideDescription());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$formula_values['Show_Description'] = $function($docova);
				}
			}
			if (trim($document->getDocType()->getCustomHideKeywords()))
			{
				$value = trim($document->getDocType()->getCustomHideKeywords());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$formula_values['Show_Keywords'] = $function($docova);
				}
			}
			if (trim($document->getDocType()->getCustomHideReviewCycle()))
			{
				$value = trim($document->getDocType()->getCustomHideReviewCycle());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$formula_values['Show_Review_Cycle'] = $function($docova);
				}
			}
			if (trim($document->getDocType()->getCustomHideArchiving()))
			{
				$value = trim($document->getDocType()->getCustomHideArchiving());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$formula_values['Show_Archiving'] = $function($docova);
				}
			}
			if (trim($document->getDocType()->getCustomEditButtonHideWhen()))
			{
				$value = trim($document->getDocType()->getCustomEditButtonHideWhen());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$formula_values['Hide_Edit_Button'] = $function($docova);
				}
			}
			if (trim($document->getDocType()->getCustomRestrictPrinting()))
			{
				$value = trim($document->getDocType()->getCustomRestrictPrinting());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$formula_values['Restrict_Printing'] = $function($docova);
				}
			}
			if (trim($document->getDocType()->getCustomHeaderHideWhen()))
			{
				$value = trim($document->getDocType()->getCustomHeaderHideWhen());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$formula_values['Hide_Header'] = $function($docova);
				}
			}
			if (trim($document->getDocType()->getCustomButtonsHideWhen()))
			{
				$value = trim($document->getDocType()->getCustomButtonsHideWhen());
				if (preg_match('~^[<?].*[?>]$~', $value))
				{
					$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
					$function = create_function('$docova', $value);
					$formula_values['Hide_Release'] = $function($docova);
				}
			}

			if (!empty($folder_managers) && count($folder_managers) > 0)
			{
				foreach ($folder_managers as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true, false))
					{
						$folder_managers[$index] = $user->getUserNameDnAbbreviated();
					}
					unset($user);
				}
			}

			if (!empty($folder_editors) && count($folder_editors) > 0)
			{
				foreach ($folder_editors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true, false))
					{
						$folder_editors[$index] = $user->getUserNameDnAbbreviated();
					}
					unset($user);
				}
			}

			if (!empty($folder_authors) && count($folder_authors) > 0)
			{
				foreach ($folder_authors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true, false))
					{
						$folder_authors[$index] = $user->getUserNameDnAbbreviated();
					}
					unset($user);
				}
			}

			if (!empty($folder_readers) && count($folder_readers) > 0)
			{
				foreach ($folder_readers as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true, false))
					{
						$folder_readers[$index] = $user->getUserNameDnAbbreviated();
					}
					unset($user);
				}
			}

			if (!empty($authors) && count($authors) > 0)
			{
				foreach ($authors as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true, false))
					{
						$authors[$index] = $user->getUserNameDnAbbreviated();
					}
					unset($user);
				}
			}

			if (!empty($readers) && count($readers) > 0)
			{
				foreach ($readers as $index => $username) {
					if (false !== $user = $this->findUserAndCreate($username, false, true, false))
					{
						$readers[$index] = $user->getUserNameDnAbbreviated();
					}
					unset($user);
				}
			}
			
			$reviewers = array();
			if ($document->getHasPendingReview() === true)
			{
				$reviewers = $em->getRepository('DocovaBundle:ReviewItems')->getPendingDocumentReviewers($document->getId());
			}
			
			$incompleteEdits = '';
			$incompleted_items = $em->getRepository('DocovaBundle:TempEditAttachment')->findBy(array('document' => $document->getId(), 'trackUser' => $this->user->getId()));
			if (!empty($incompleted_items))
			{
				foreach ($incompleted_items as $item) {
					$incompleteEdits .= (str_replace('\\', '\\\\', $item->getFilePath()) . $item->getFileName() . '; ');
				}
				$incompleteEdits = substr_replace($incompleteEdits, '', -2);
			}
			unset($incompleted_items, $item);

			$template_path = ($is_mobile === false) ? 'DocovaBundle:Default:genericDocumentReadMode.html.twig' : 'DocovaBundle:Mobile:mReadDocument.html.twig';
			return $this->render($template_path, array(
					'user' => $this->user,
					'settings' => $this->global_settings,
					'user_access' => $access_level,
					'document' => $document,
					'document_type' => $document->getDocType(),
					'has_draft' => $has_draft,
					'workflows' => $rendered_workflows,
					'workflow_options' => $doc_workflow_options,
					'is_initial_version' => $is_initial_version,
					'available_versions' => $available_versions,
					'action_buttons' => $action_buttons,
					'link_comments' => $link_comments,
					'rel_doc_settings' => $related_doc_settings,
					'attachment_settings' => $attachment_settings,
					'text_content_type' => $text_content_type,
					'incompleteEdits' => $incompleteEdits,
					'templates' => $templates,
					'managers' => $folder_managers,
					'authors' => $authors,
					'readers' => $readers,
					'folder_editors' => $folder_editors,
					'folder_authors' => $folder_authors,
					'folder_readers' => $folder_readers,
					'doc_editors' => $doc_editors,
					'doc_readers' => $doc_readers,
					'editable' => $editable,
					'bookmarked' => $bookmarked,
					'reviewers' => $reviewers,
					'subforms' => $rendered_subforms,
					'translated_values' => $formula_values
			));
		}
		else {
			throw $this->createNotFoundException('Document Id is missed.');
		}
	}
	
	public function documentServicesAction(Request $request)
	{
		$post_req = $request->getContent();
		if (empty($post_req)) {
			throw $this->createNotFoundException('No POST data was submitted.');
		}
		if (stripos($post_req, '%3C') !== false) {
			$post_req = urldecode($post_req);
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
	
		if (method_exists($this, 'service'.$action))
		{
			$result_xml = call_user_func(array($this, 'service'.$action), $post_xml);
		}

		return $response->setContent($result_xml->saveXML());
	}


	private function serviceGETCOMMENTS($post_xml)
	{

		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('Key')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$comments = $em->getRepository('DocovaBundle:AppDocComments')->getDocComments($document);
		$response_xml = '<?xml version="1.0" encoding="UTF-8" ?><Results>';
		
		if (!empty($comments)) 
		{
			$response_xml .= '<Result ID="Status">OK</Result><Result ID="Ret1"><Documents>';
			foreach ($comments as $comment) {
				$response_xml .= "<Document><ID>".$comment['id']."</ID>";
				$response_xml .= "<parentdockey>".$document."</parentdockey>";
				$response_xml .= "<threadIndex>".$comment['threadIndex']."</threadIndex>";
				$response_xml .= "<commentIndex>".$comment['commentIndex']."</commentIndex>";
				$response_xml .= "<avatar>".$comment['avatar']."</avatar>";
				$response_xml .= "<commentor>".$comment['userNameDnAbbreviated']."</commentor>";
				$response_xml .= "<dateCreated>".$comment['dateCreated']->format('m/d/Y H:i:s')."</dateCreated>";
				$response_xml .= "<comment><![CDATA[".$comment['comment']."]]></comment>";
				 
				$response_xml .= "</Document>";
			}
			$response_xml .= '</Documents></Result>';
		}
		$response_xml .= '</Results>';
		
		$result_xml->loadXML($response_xml);
		return $result_xml;
	}


	/**
	 * Delete activity by the assignee
	 *
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceDELETE($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$doc_id	= $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$app_id =  $post_xml->getElementsByTagName('AppID')->item(0)->nodeValue;
		$isDocComment = $post_xml->getElementsByTagName('isDocComment')->item(0)->nodeValue;
		if (!empty($doc_id) && !empty($app_id) && $isDocComment == "1")
		{
			$em = $this->getDoctrine()->getManager();
			$comment = $em->getRepository('DocovaBundle:AppDocComments')->find($doc_id);
			if (!empty($comment))
			{
				try {
					$em->remove($comment);
					$em->flush();
				}
				catch (\Exception $e) {
					//@TODO: log the error
					unset($activity, $doc_id, $activity_id, $em);
				}
			}
		}
	
		$result_str = "<Results><Result ID='Status'>OK</Result></Results>"; 
		$result_xml->loadXML($result_str);
		return $result_xml;
	
		
	}
	
	public function editDocumentAction(Request $request, $doc_id)
	{
		//*********External View Processing Extension**********
		$paramindex = array();
		if ($request->isMethod('POST')){
			foreach($request->request->keys() as $key){
				$paramindex[strtolower($key)] = $key;
			}
			$dataViewId = $request->request->get('DataView');
		}
		else{
			foreach($request->query->keys() as $key){
				$paramindex[strtolower($key)] = $key;
			}			
			$dataViewId = $request->query->get('DataView');
		}

		if (!empty($dataViewId)){	
			if ($request->isMethod('POST')) {
				
				try{
					ExternalViews::saveDocument($this, $this->container->getParameter("document_root"),$request, $doc_id, null, $dataViewId );
				}
				catch (\Exception $e){
					throw $this->createNotFoundException('Error saving document - '.$e->getMessage());
				}
			}

			try {
				$extProcessed = ExternalViews::editDocument($this, $this->container->getParameter("document_root"),$doc_id, null, $dataViewId );
				if ($extProcessed!=null){
					return $extProcessed;
				}
			} 
			catch (\Exception $e) {
				echo "ERROR: ".$e->getMessage();
			}
		}
		//********************************************
		if (empty($doc_id)) {
			throw $this->createNotFoundException('Document ID is missed.');
		}

		$this->initialize();
		$is_mobile = $request->query->get('Mobile');
		$mobile_type=$request->query->get('type');
		$mobile_app_device=$request->query->get('device');

		if ($is_mobile == 'true' ||  in_array($mobile_app_device, array("android","iOS") ) ) {
			$is_mobile=true;
		}else{
			$is_mobile=false;
		}

		$em = $this->getDoctrine()->getManager();
		$document_info = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id, 'Trash' => false, 'Archived' => false));
		if (empty($document_info)) {
			throw $this->createNotFoundException('No Document found for ID = '. $doc_id);
		}
		
		$subforms_arr = $rendered_subforms = array();

//		$folder_manager = false;
		$security_check = new Miscellaneous($this->container);
		$security_context = $this->container->get('security.authorization_checker');
		if (false === $security_check->canEditDocument($document_info, true)) {
			throw new AccessDeniedException();
			//@QUESTION: Should the assignee of each step who deos not have EDIT access to the document be able to edit or not?
			// It seems if the document is in review step just the admin, owner and reviewr can edit
		}
		elseif ($document_info->getDocType()->getEnableLifecycle() && $document_info->getDocStatus() == $document_info->getDocType()->getFinalStatus() && !$security_context->isGranted('ROLE_ADMIN') && !$security_context->isGranted('MASTER', $document_info->getFolder()->getLibrary())) {
			throw new AccessDeniedException();
		}
		
		$access_level = $security_check->getAccessLevelForDocument($document_info);

		$customAcl = new CustomACL($this->container);
		$folder_managers = $folder_editors = $folder_authors = $folder_readers = $authors = $readers = array();
		$users_list = $customAcl->getObjectACEUsers($document_info->getFolder(), 'owner');
		if ($users_list->count() > 0)
		{
			foreach ($users_list as $user) {
				$folder_managers[] = $user->getUsername();
				$authors[] = $user->getUsername();
			}
		}
		$groups_list = $customAcl->getObjectACEGroups($document_info->getFolder(), 'owner');
		if ($groups_list->count() > 0)
		{
			foreach ($groups_list as $group) {
				if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
				{
					$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					$folder_managers[] = $groupname;
					$authors[] = $groupname;
				}
			}
		}
		$users_list = $customAcl->getObjectACEUsers($document_info->getFolder(), 'master');
		if ($users_list->count() > 0)
		{
			foreach ($users_list as $user) {
				if (!in_array($user->getUsername(), $folder_editors)) {
					$folder_editors[] = $user->getUsername();
					$authors[] = $user->getUsername();
				}
			}
		}
		$groups_list = $customAcl->getObjectACEGroups($document_info->getFolder(), 'master');
		if ($groups_list->count() > 0)
		{
			foreach ($groups_list as $group) {
				if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
				{
					$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					if (!in_array($groupname, $folder_editors))
					{
						$folder_editors[] = $groupname;
						$authors[] = $groupname;
					}
				}
			}
		}
		
		$users_list = $customAcl->getObjectACEUsers($document_info->getFolder(), 'create');
		if ($users_list->count() > 0)
		{
			foreach ($users_list as $user)
			{
				if (!in_array($user->getUsername(), $folder_authors)) {
					$folder_authors[] = $user->getUsername();
				}
				if (!in_array($user->getUsername(), $authors)) {
					$authors[] = $user->getUsername();
				}
			}
		}
		$groups_list = $customAcl->getObjectACEGroups($document_info->getFolder(), 'create');
		if ($groups_list->count() > 0)
		{
			foreach ($groups_list as $group) {
				if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
				{
					$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					if (!in_array($groupname, $folder_managers))
					{
						$folder_authors[] = $groupname;
					}
					if (!in_array($groupname, $folder_managers))
					{
						$authors[] = $groupname;
					}
				}
			}
		}

		$ancestors = $em->getRepository('DocovaBundle:Folders')->getAncestors($document_info->getFolder()->getPosition(), $document_info->getFolder()->getLibrary()->getId(), $document_info->getFolder()->getId(), true);
		foreach ($ancestors as $folder)
		{
			$folder = $em->getReference('DocovaBundle:Folders', $folder['id']);
			$users_list = $customAcl->getObjectACEUsers($folder, 'view');
			if ($users_list->count() > 0)
			{
				foreach ($users_list as $user)
				{
					if (!in_array($user->getUsername(), $folder_readers)) {
						$folder_readers[] = $user->getUsername();
						$readers[] = $user->getUsername();
					}
				}
			}
			$groups_list = $customAcl->getObjectACEGroups($folder, 'view');
			if ($groups_list->count() > 0)
			{
				foreach ($groups_list as $group) {
					if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
					{
						$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
						if (!in_array($groupname, $folder_readers))
						{
							$folder_readers[] = $groupname;
							$readers[] = $groupname;
						}
					}
				}
			}
		}		
		unset($users_list, $user, $groups_list, $group);
		
		$doc_editors = $doc_readers = array();
		$text_content_type = $link_comments = '';
		$has_attachment = (in_array('ATT', $document_info->getDocType()->getContentSections()) || in_array('ATTI', $document_info->getDocType()->getContentSections())) ? 'true' : '';
		$user_list = $customAcl->getObjectACEUsers($document_info, 'edit');
		if ($user_list->count() > 0) {
			for ($x = 0; $x < $user_list->count(); $x++) {
				$doc_editors[] = $user_list[$x]->getUsername();
				if (!in_array($user_list[$x]->getUsername(), $authors)) {
					$authors[] = $user_list[$x]->getUsername();
				}
			}
		}
		$groups_list = $customAcl->getObjectACEGroups($document_info, 'edit');
		if ($groups_list->count() > 0) {
			foreach ($groups_list as $group) {
				if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
				{
					$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					$doc_editors[] = $groupname;
					if (!in_array($groupname, $authors)) {
						$authors[] = $groupname;
					}
				}
			}
		}

		$user_list = $customAcl->getObjectACEUsers($document_info, 'view');
		if ($user_list->count() > 0) {
			for ($x = 0; $x < $user_list->count(); $x++) {
				$doc_readers[]= $user_list[$x]->getUsername();
				if (!in_array($user_list[$x]->getUsername(), $readers)) {
					$readers[] = $user_list[$x]->getUsername();
				}
			}
		}
		$groups_list = $customAcl->getObjectACEGroups($document_info, 'view');
		if ($groups_list->count() > 0) {
			foreach ($groups_list as $group) {
				if (false !== $g = $this->retrieveGroupByRole($group->getRole()))
				{
					$groupname = $g->getGroupType() ? $g->getDisplayName() : $g->getDisplayName() . '/DOCOVA';
					$doc_readers[] = $groupname;
					if (!in_array($groupname, $readers)) {
						$readers[] = $groupname;
					}
				}
			}
		}

		if (!empty($doc_editors) && count($doc_editors) > 0)
		{
			foreach ($doc_editors as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false))
				{
					$doc_editors[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}
		
		if (!empty($doc_readers) && count($doc_readers) > 0)
		{
			foreach ($doc_readers as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false))
				{
					$doc_readers[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}

		$doc_editors = array_unique($doc_editors);
		$doc_readers = array_unique($doc_readers);
		$doc_editors = (!empty($doc_editors)) ? implode(',', $doc_editors) : '';
		$doc_readers = (!empty($doc_readers)) ? implode(',', $doc_readers) : '';
		
		$attachment_settings = $related_doc_settings = $templates = array();
		if (!$document_info->getDocType()->getTrash())
		{
			$subform_obj = $document_info->getDocType()->getDocTypeSubform();
			if (!empty($subform_obj)) {
				foreach ($subform_obj as $object) {
					$subforms_arr[$object->getSubform()->getFormFileName()]['elements'] = $object->getSubform()->getSubformFields();
					if (file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('bundles/docova/js/custom/'.$object->getSubform()->getFormFileName().'.js')) || file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('upload/js/'.$object->getSubform()->getFormFileName().'.js'))) {
						$subforms_arr[$object->getSubform()->getFormFileName()]['JSHeader'] = $object->getSubform()->getFormFileName();
					}
					else {
						$subforms_arr[$object->getSubform()->getFormFileName()]['JSHeader'] = false;
					}
					if ($object->getPropertiesXML()) {
						$xml_properties = new \DOMDocument();
						$xml_properties->loadXML(str_replace('&','&amp;',$object->getPropertiesXML()));
						$subforms_arr[$object->getSubform()->getFormFileName()]['Properties'] = $xml_properties;
						
						if (in_array('TXT', $document_info->getDocType()->getContentSections()) || in_array('RTXT', $document_info->getDocType()->getContentSections()) || in_array('DRTXT', $document_info->getDocType()->getContentSections())) {
							$text_content_type = (in_array('RTXT', $document_info->getDocType()->getContentSections()) || in_array('DRTXT', $document_info->getDocType()->getContentSections())) ? 'HTML' : 'TEXT';
						}
	
						if (isset($xml_properties->getElementsByTagName('LinkComments')->item(0)->nodeValue)) {
							$link_comments = $xml_properties->getElementsByTagName('LinkComments')->item(0)->nodeValue;
						}
	
						if (isset($xml_properties->getElementsByTagName('RelatedDocOpenMode')->item(0)->nodeValue)) {
							$related_doc_settings['RelatedDocOpenMode'] = $xml_properties->getElementsByTagName('RelatedDocOpenMode')->item(0)->nodeValue;
							$related_doc_settings['EnableLinkedFiles'] = $xml_properties->getElementsByTagName('EnableLinkedFiles')->item(0)->nodeValue;
							$related_doc_settings['LaunchLinkedFiles'] = $xml_properties->getElementsByTagName('LaunchLinkedFiles')->item(0)->nodeValue;
							$related_doc_settings['EnableXLink'] = $xml_properties->getElementsByTagName('EnableXLink')->item(0)->nodeValue;
							$related_doc_settings['OMUserSelectDocTypeKey'] = $xml_properties->getElementsByTagName('OMUserSelectDocTypeKey')->item(0)->nodeValue;
							$related_doc_settings['OMLatestDocTypeKey'] = $xml_properties->getElementsByTagName('OMLatestDocTypeKey')->item(0)->nodeValue;
							$related_doc_settings['OMLinkedDocTypeKey'] = $xml_properties->getElementsByTagName('OMLinkedDocTypeKey')->item(0)->nodeValue;
						}
	
						if ($has_attachment === 'true' && isset($xml_properties->getElementsByTagName('HasMultiAttachmentSection')->item(0)->nodeValue)) {
							$attachment_settings['HasMultiAttachmentSection'] = $xml_properties->getElementsByTagName('HasMultiAttachmentSection')->item(0)->nodeValue;
							$attachment_settings['EnableFileCIAO'] = $xml_properties->getElementsByTagName('EnableFileCIAO')->item(0)->nodeValue;
							$attachment_settings['EnableFileViewLogging'] = $xml_properties->getElementsByTagName('EnableFileViewLogging')->item(0)->nodeValue;
							$attachment_settings['EnableFileDownloadLogging'] = $xml_properties->getElementsByTagName('EnableFileDownloadLogging')->item(0)->nodeValue;
							$attachment_settings['MaxFiles'] = $xml_properties->getElementsByTagName('MaxFiles')->item(0)->nodeValue;
							$attachment_settings['AllowedFileExtensions'] = $xml_properties->getElementsByTagName('AllowedFileExtensions')->item(0)->nodeValue;
							$attachment_settings['AttachmentReadOnly'] = $xml_properties->getElementsByTagName('AttachmentReadOnly')->item(0)->nodeValue;
							$attachment_settings['HideOnEditing'] = $xml_properties->getElementsByTagName('HideOnEditing')->item(0)->nodeValue;
							$attachment_settings['EnableLocalScan'] = $xml_properties->getElementsByTagName('EnableLocalScan')->item(0)->nodeValue;
						}
	
						if (isset($xml_properties->getElementsByTagName('TemplateType')->item(0)->nodeValue))
						{
							$templates['Template_Type'] 		= $xml_properties->getElementsByTagName('TemplateType')->item(0)->nodeValue;
							$templates['Template_Auto_Attach']	= $xml_properties->getElementsByTagName('TemplateAutoAttachment')->item(0)->nodeValue;
							$templates['Template_List']			= str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateKey')->item(0)->nodeValue);
							$templates['Template_Name_List']	= str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateName')->item(0)->nodeValue);
							$templates['Template_File_List']	= str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateFileName')->item(0)->nodeValue);
							$templates['Template_Version_list'] = str_replace(',', ';', $xml_properties->getElementsByTagName('TemplateVersion')->item(0)->nodeValue);
						}
					}
					else {
						$subforms_arr[$object->getSubform()->getFormFileName()]['Properties'] = '';
					}
				}
					
				foreach ($subforms_arr as $form_name => $form_elements) {
					$more_section = null;
					if (!empty($form_elements['Properties']) && !empty($form_elements['Properties']->getElementsByTagName('DisplayInMore')->item(0)->nodeValue) && $form_elements['Properties']->getElementsByTagName('DisplayInMore')->item(0)->nodeValue == 1)
					{
						if (!empty($form_elements['Properties']->getElementsByTagName('SubFormTabName')->item(0)->nodeValue)) {
							$more_section = array('Tab_Name' => $form_elements['Properties']->getElementsByTagName('SubFormTabName')->item(0)->nodeValue);
						}
					}
					$rendered_subforms[] = array(
							'More_Section' => $more_section,
							'HTML' => $this->renderSubform($form_name, $form_elements['elements'], true, $document_info->getDocType(), $document_info->getFolder(), $is_mobile, $form_elements['Properties'], $document_info),
							'JSHeader' => $form_elements['JSHeader']
					);
				}
			}
			
			$is_initial_version = $has_draft = $is_denied = '';
			$doc_workflow_options = $action_buttons = $button_labels = $rendered_workflows = $available_versions = array();
			$docova = $this->get('docova.objectmodel');
			$docova->setUser($this->user);
			$docova->setDocument($document_info, $document_info->getFolder()->getLibrary());

			if ($document_info->getDocSteps()->count() > 0)
			{
				$workflow_steps = $document_info->getDocSteps();
				$workflow = array(
					'Workflow_Name' => $workflow_steps[0]->getWorkflowName(),
					'Author_Customization' => $workflow_steps[0]->getAuthorCustomization(),
					'Bypass_Rleasing' => $workflow_steps[0]->getBypassRleasing()
				);
				$docstep = array(
						'document_id' => $doc_id,
						'isStarted' => '',
						'isOriginator' => '',
						'isPendingParticipant' => '',
						'isApprover' => '',
						'isReviewer' => '',
						'isPublisher' => '',
						'isStartStep' => '',
						'isEndStep' => '',
						'isCompleteStep' => '',
						'isApproveStep' => '',
						'isDelegate' => '',
						'AllowInfoRequest' => '',
						'AllowUpdate' => '',
						'AllowPause' => '',
						'AllowCustomize' => '',
						'AllowBacktrack' => '',
						'AllowCancel' => ''
				);
				foreach ($workflow_steps as $step)
				{
					if ($step->getStepType() === 1 && $step->getStatus() === 'Completed')
					{
						$docstep['isStarted'] = true;
					}
					if ($step->getStepType() === 1 && true === $this->searchUserInCollection($step->getAssignee(), $this->user))
					{
						$docstep['isOriginator'] = true;
					}
					$participants = $step->getOtherParticipant();
					if ($participants->count() > 0)
					{
						foreach ($participants as $puser)
						{
							if ($puser->getUserNameDnAbbreviated() === $this->user->getUserNameDnAbbreviated()) {
								$docstep['isPendingParticipant'] = true;
								break;
							}
						}
					}
					if (count($step->getOtherParticipantGroups()) > 0 && !$docstep['isPendingParticipant'])
					{
						foreach ($step->getOtherParticipantGroups() as $group) {
							if (true === $this->fetchGroupMembers($group, false, $this->user))
							{
								$docstep['isPendingParticipant'] = true;
								break;
							}
						}
					}

					if ($step->getStepType() === 3 && true === $this->searchUserInCollection($step->getAssignee(), $this->user))
					{
						$docstep['isApprover'] = true;
					}
					if ($step->getStepType() === 2 && true === $this->searchUserInCollection($step->getAssignee(), $this->user))
					{
						$docstep['isReviewer'] = true;
					}
					if ($step->getStepType() === 4 && true === $this->searchUserInCollection($step->getAssignee(), $this->user))
					{
						$docstep['isPublisher'] = true;
					}
					if ($step->getStatus() === 'Denied')
					{
						$is_denied = true;
					}
					if (empty($current_step) && $step->getStatus() !== 'Completed' && $step->getStatus() !== 'Approved')
					{
						$current_step = $step;
					}
				}
				if (!empty($current_step))
				{
					$docstep['isStartStep'] = ($current_step->getStepType() === 1) ? true : '';
					$docstep['isEndStep'] = ($current_step->getStepType() === 4) ? true : '';
					$docstep['isCompleteStep'] = ($current_step->getStepType() === 3 && $docstep['isPendingParticipant'] === true && ($current_step->getStatus() === 'Pending' || $current_step->getStatus() === 'Paused')) ? true : '';
					$docstep['isApproveStep'] = ($current_step->getStepType() === 4 && $docstep['isPendingParticipant'] === true && ($current_step->getStatus() === 'Pending' || $current_step->getStatus() === 'Paused')) ? true : '';
					$docstep['AllowInfoRequest'] = ($docstep['isOriginator'] === true || !empty($docstep['isApprover']) || !empty($docstep['isReviewer'])) ? true : '';
					$docstep['AllowUpdate'] = ($docstep['isOriginator'] === true || $security_check->canEditDocument($document_info) === true) ? true : '';
					$docstep['AllowPause'] = ($current_step->getStatus() !== 'Paused' && ($docstep['isOriginator'] === true || $security_check->canEditDocument($document_info) === true)) ? true : '';
					$docstep['AllowCustomize'] = ($workflow['Author_Customization'] == true && $current_step->getStepType() !== 4 &&  ($docstep['isOriginator'] === true || $security_check->canEditDocument($document_info) === true)) ? true : '';
					$docstep['AllowBacktrack'] = ($docstep['isOriginator'] === true || $security_check->canEditDocument($document_info) === true) ? true : '';
					$docstep['AllowCancel'] = ($docstep['isOriginator'] === true || $security_check->canEditDocument($document_info) === true) ? true : '';
					$complete_review = $approve = $decline = $release = false;
					if (trim($current_step->getCustomReviewButtonLabel()))
					{
						$value = trim($current_step->getCustomReviewButtonLabel());
						if (preg_match('~^[<?].*[?>]$~', $value))
						{
							$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
							$function = create_function('$docova', $value);
							$complete_review = $function($docova);
						}
						else {
							$complete_review = $value;
						}
					}
					if (trim($current_step->getCustomApproveButtonLabel()))
					{
						$value = trim($current_step->getCustomApproveButtonLabel());
						if (preg_match('~^[<?].*[?>]$~', $value))
						{
							$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
							$function = create_function('$docova', $value);
							$approve = $function($docova);
						}
						else {
							$approve = $value;
						}
					}
					if (trim($current_step->getCustomDeclineButtonLabel()))
					{
						$value = trim($current_step->getCustomDeclineButtonLabel());
						if (preg_match('~^[<?].*[?>]$~', $value))
						{
							$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
							$function = create_function('$docova', $value);
							$decline = $function($docova);
						}
						else {
							$decline = $value;
						}
					}
					if (trim($current_step->getCustomReleaseButtonLabel()))
					{
						$value = trim($current_step->getCustomReleaseButtonLabel());
						if (preg_match('~^[<?].*[?>]$~', $value))
						{
							$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
							$function = create_function('$docova', $value);
							$release = $function($docova);
						}
						else {
							$release = $value;
						}
					}

					$button_labels = array(
						'Complete_Review' => $complete_review,
						'Approve' => $approve,
						'Decline' => $decline,
						'Release_Document' => $release
					);
				}
				
				if ($is_denied === true)
				{
					$docstep['isApprover'] = '';
					$docstep['isReviewer'] = '';
					$docstep['isPublisher'] = '';
				}

				$stepsxml = "";
				$wf_nodes_xmlObj = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Documents')->getWorkflowStepsXML($document_info, $this->user, $this->global_settings, false);
				$stepsxml = $wf_nodes_xmlObj->saveXML();
				
				$mobileExtension = $is_mobile === true ? '_m' : '';
				$rendered_workflows[] = $this->renderView("DocovaBundle:Subform:sfDocSection-Workflow$mobileExtension.html.twig", array(
						'workflow' => $workflow,
						'workflow_xml' => '',
						'workflow_xml_nodes' => $stepsxml,
						'step' => $current_step,
						'docstep' => $docstep,
						'doctype' => $document_info->getDocType()
				));

				$workflow_detail = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
				if (!empty($workflow_detail)) {
					$doc_workflow_options['JSHeader'] = file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('bundles/docova/js/custom/'.$workflow_detail->getFormFileName().$mobileExtension.'.js')) ? $workflow_detail->getFormFileName().$mobileExtension : false;
					$wf_buttons = $workflow_detail->getActionButtons();
					if ($wf_buttons->count() > 0)
					{
						foreach ($wf_buttons as $button)
						{
							if (!empty($button_labels[$button->getActionName()]))
							{
								$action_buttons[$button->getActionName()]['Label']	= $button_labels[$button->getActionName()];
							}
							elseif ($button->getActionName() === 'Start_Workflow' && trim($document_info->getDocType()->getCustomStartButtonLabel()))
							{
								$value = trim($document_info->getDocType()->getCustomStartButtonLabel());
								if (preg_match('~^[<?].*[?>]$~', $value))
								{
									$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
									$function = create_function('$docova', $value);
									$action_buttons[$button->getActionName()]['Label'] = $function($docova);
								}
								else {
									$action_buttons[$button->getActionName()]['Label'] = $value;
								}
							}
							else {
								$action_buttons[$button->getActionName()]['Label']	= $button->getButtonLabel();
							}
							$action_buttons[$button->getActionName()]['Script']	= $button->getClickScript();
							$action_buttons[$button->getActionName()]['Primary'] = $button->getPrimaryIcon();
							$action_buttons[$button->getActionName()]['Secondary'] = $button->getSecondaryIcon();
							if ($button->getVisibleOn())
							{
								$function = create_function($button->getVisibleOn()->getFunctionArguments(), $button->getVisibleOn()->getFunctionScript());
								$res = $function($document_info, $this->user);
								$action_buttons[$button->getActionName()]['Visible'] = $res;
							}
							else
							{
								$action_buttons[$button->getActionName()]['Visible'] = true;
							}
						}
					}
				}
				unset($workflow_detail);
					
				$doc_workflow_options['isCreated'] = true;
				$doc_workflow_options['isCompleted'] = false;
				$doc_workflow_options['createInDraft'] = true;
			}
			else {
				if ($document_info->getDocStatus() === $document_info->getDocType()->getInitialStatus() && $document_info->getDocType()->getDocTypeWorkflow()->count() > 0)
				{
					$workflow_arr = $step = false;
					$workflow_xml = $document_info->getDocType()->getDocTypeWorkflow()->count() > 1 ? '<Documents>' : '';
					foreach ($document_info->getDocType()->getDocTypeWorkflow() as $workflow)
					{
						$workflow_xml .= '<Document><wfID>'.$workflow->getId().'</wfID><wfName><![CDATA['.$workflow->getWorkflowName().']]></wfName>';
						$workflow_xml .= '<wfDescription><![CDATA['.$workflow->getDescription().']]></wfDescription>';
						$workflow_xml .= ($workflow->getBypassRleasing() == true) ? '<EnableImmediateRelease>1</EnableImmediateRelease>' : '<EnableImmediateRelease/>';
						$workflow_xml .= ($workflow->getAuthorCustomization() == true) ? '<wfCustomizeAction>1</wfCustomizeAction>' : '<wfCustomizeAction/>';
						$workflow_xml .= '</Document>';
					
						if ($document_info->getDocType()->getDocTypeWorkflow()->count() == 1) {
							$workflow_arr = array(
									'id' => $workflow->getId(),
									'Author_Customization' => $workflow->getAuthorCustomization(),
									'Bypass_Rleasing' => $workflow->getBypassRleasing(),
									'Workflow_Name' => $workflow->getWorkflowName()
							);
							$steps = $workflow->getSteps();
							if ($steps->count() > 0)
							{
								$step = $steps->first();
							}
							else {
								$this->createNotFoundException('No Workflow steps were found.');
							}
						}
					
					}
					$workflow_xml .= $document_info->getDocType()->getDocTypeWorkflow()->count() > 1 ? '</Documents>' : '';
					$mobileExtension = $is_mobile === true ? '_m' : '';
					$stepsxml = "";
					if ( $document_info->getDocType()->getDocTypeWorkflow()->count() == 1 )
					{
						foreach ($document_info->getDocType()->getDocTypeWorkflow() as $workflowtmp ){
							$stepsxml =  $em->getRepository('DocovaBundle:Workflow')->getWorkflowStepsXML( $workflowtmp, $this->user );
							$stepsxml = $stepsxml->saveXML();
						}
					}
					
					$rendered_workflows[] = $this->renderView("DocovaBundle:Subform:sfDocSection-Workflow$mobileExtension.html.twig", array(
							'workflow' => $workflow_arr,
							'workflow_xml' => $workflow_xml,
							'workflow_xml_nodes' => $stepsxml,
							'step' => $step,
							'docstep' => false,
							'doctype' => $document_info->getDocType()
					));
					
					$workflow_detail = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
					if (!empty($workflow_detail)) {
						$doc_workflow_options['JSHeader'] = file_exists($this->root_path.DIRECTORY_SEPARATOR.$this->get('assets.packages')->getUrl('bundles/docova/js/custom/'.$workflow_detail->getFormFileName().$mobileExtension.'.js')) ? $workflow_detail->getFormFileName().$mobileExtension : false;
						$wf_buttons = $workflow_detail->getActionButtons();
						if ($wf_buttons->count() > 0)
						{
							foreach ($wf_buttons as $button)
							{
								if ($button->getActionName() === 'Start_Workflow' || $button->getActionName() === 'Workflow')
								{
									if ($button->getActionName() === 'Start_Workflow' && trim($document_info->getDocType()->getCustomStartButtonLabel()))
									{
										$value = trim($document_info->getDocType()->getCustomStartButtonLabel());
										if (preg_match('~^[<?].*[?>]$~', $value))
										{
											$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
											$function = create_function('$docova', $value);
											$action_buttons[$button->getActionName()]['Label'] = $function($docova);
										}
										else {
											$action_buttons[$button->getActionName()]['Label'] = $value;
										}
									}
									else {
										$action_buttons[$button->getActionName()]['Label']	= $button->getButtonLabel();
									}
									$action_buttons[$button->getActionName()]['Script']	= $button->getClickScript();
									$action_buttons[$button->getActionName()]['Primary'] = $button->getPrimaryIcon();
									$action_buttons[$button->getActionName()]['Secondary'] = $button->getSecondaryIcon();
									$action_buttons[$button->getActionName()]['Visible'] = true;
								}
							}
						}
					}
					unset($workflow_detail);
						
					$doc_workflow_options['isCreated'] = false;
					$doc_workflow_options['isCompleted'] = false;
					$doc_workflow_options['createInDraft'] = true;
				}
			}
	
			if ($document_info->getDocType()->getEnableLifecycle() && $document_info->getDocType()->getEnableVersions())
			{			
				$parentdoc = $document_info->getParentDocument();
				$dockey = (empty($parentdoc) ? $document_info->getId() : $parentdoc->getId());
				unset($parentdoc);
				$all_versions = $em->getRepository('DocovaBundle:Documents')->getAllDocVersionsFromParent($dockey);
				
				
				if (!empty($all_versions))
				{
					foreach ($all_versions as $doc)
					{
						if ($doc->getStatusNo() == 0 && $doc->getId() != $document_info->getId())
						{
							$has_draft = true;
							break;
						}
					}

					if (count($all_versions) == 1 || $all_versions[count($all_versions) - 1]->getId() == $document_info->getId())
					{
						$is_initial_version = 'true';
						$pfv = $document_info->getDocVersion().'.'.$document_info->getRevision();
					}
					else {
						foreach ($all_versions as $index => $doc)
						{
							if ($doc->getId() == $document_info->getId()) {
								$pfv = $all_versions[$index + 1]->getDocVersion().'.'.$all_versions[$index + 1]->getRevision();
								break;
							}
						}
					}
						
					if (!empty($pfv))
					{
						$pfv = explode('.', $pfv);
			
						$v1 = ($pfv[0] + 1).'.0.0';
						$v2 = (empty($pfv[0]) ? '0' : $pfv[0]).'.'.($pfv[1] + 1).'.0';
						$v3 = (empty($pfv[0]) ? '0' : $pfv[0]).'.'.(empty($pfv[1]) ? '0' : $pfv[1]).'.'.($pfv[2] + 1);
			
						foreach ($all_versions as $doc)
						{
							$v1 = ($doc->getDocVersion().'.'.$doc->getRevision() == $v1 && $v1 != $document_info->getDocVersion().'.'.$document_info->getRevision()) ? '' : $v1;
							$v2 = ($doc->getDocVersion().'.'.$doc->getRevision() == $v2 && $v2 != $document_info->getDocVersion().'.'.$document_info->getRevision()) ? '' : $v2;
							$v3 = ($doc->getDocVersion().'.'.$doc->getRevision() == $v3 && $v3 != $document_info->getDocVersion().'.'.$document_info->getRevision()) ? '' : $v3;
						}
			
						!empty($v1) ? array_push($available_versions, $v1) : '';
						!empty($v2) ? array_push($available_versions, $v2) : '';
						!empty($v3) ? array_push($available_versions, $v3) : '';
						$available_versions = array_unique($available_versions);
					}
				}
				unset($all_versions);
			}

			if ($request->isMethod('POST')) {
				if ($request->query->get('mode') !== 'sfs')
				{
					$author_txt		= $request->request->get('OriginalAuthor');
					$createdate_txt = $request->request->get('OriginalDate');
					if (empty($createdate_txt)) {
						$createdate_txt = $document_info->getDateCreated();
					}
					else {
						$format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
						$createdate_txt = date_create_from_format($format, $createdate_txt);
					}
					$owner_txt 		= $request->request->get('DocumentOwner');
					$desc_txt 		= $request->request->get('Description');
					$keywords_txt	= $request->request->get('Keywords');
					$desc_txt		= ($is_mobile === true) ? $document_info->getDescription() : $desc_txt;
					$keywords_txt	= ($is_mobile === true) ? $document_info->getKeywords() : $keywords_txt;
					
					if (!empty($author_txt)) {
						$author_obj = $this->findUserAndCreate($author_txt);
						if (empty($author_obj)) {
							$author_obj = $document_info->getAuthor();
						}
					}
					else {
						$author_obj = $document_info->getAuthor();
					}
		
					if (!empty($owner_txt)) {
						$owner_obj = $this->findUserAndCreate($owner_txt);
						if (empty($owner_obj)) {
							$owner_obj = $document_info->getCreator();
						}
					}
					else {
						$owner_obj = $document_info->getOwner();
					}
		
					$modifier_obj = $this->user;
					if ($document_info->getReviewers()->count() > 0) {
						$document_info->clearReviewers();
						$em->flush();
					}
		
					$document_info->setAuthor($author_obj);
					$document_info->setCreator($owner_obj);
					$document_info->setDateCreated($createdate_txt);
					$document_info->setModifier($modifier_obj);
					$document_info->setDateModified(new \DateTime());
					/* I am not sure if we really need commented lines
					$document_info->setDocType($document_info->getDocType());
					$document_info->setDocStatus($document_info->getDocStatus());
					$document_info->setFolder($document_info->getFolder());
					*/
					$document_info->setKeywords($keywords_txt);
					$document_info->setDescription($desc_txt);
					$document_info->setOwner($owner_obj);
					$document_info->setIndexed(false);
					$document_info->setLocked(false);
					$document_info->setLockEditor();
		
					$document_info->setReviewType(trim($request->request->get('ReviewType')) ? $request->request->get('ReviewType') : 'P');
					$custom_review = trim($request->request->get('ReviewType')) == 'C' ? true : false;
					$document_info->setReviewPeriod(!empty($custom_review) ? (int)$request->request->get('ReviewPeriod') : null);
					$document_info->setReviewPeriodOption(!empty($custom_review) ? $request->request->get('ReviewPeriodOption') : null);
					$document_info->setReviewDateSelect(!empty($custom_review) ? $request->request->get('ReviewDateSelect') : null);
					$document_info->setAuthorReview(!empty($custom_review) && trim($request->request->get('AuthorReview')) ? true : false);
					if (!empty($custom_review) && trim($request->request->get('ReviewStartDayOption')) && trim($request->request->get('ReviewStartMonth')) && trim($request->request->get('ReviewStartDay')))
					{
						$document_info->setReviewStartMonth($request->request->get('ReviewStartMonth'));
						$document_info->setReviewStartDay($request->request->get('ReviewStartDay'));
					}
					if (!empty($custom_review) && trim($request->request->get('Reviewers'))) {
						$names = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($request->request->get('Reviewers')));
						for ($x = 0; $x < count($names); $x++) {
							if (false !== $reviewer = $this->findUserAndCreate($names[$x])) {
								$document_info->addReviewers($reviewer);
							}
						}
					}
					unset($custom_review);
					$document_info->setArchiveType(trim($request->request->get('ArchiveType')) ? $request->request->get('ArchiveType') : 'P');
					if (trim($request->request->get('ArchiveType')) == 'C' && trim($request->request->get('CustomArchiveDate')))
					{
						$format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
						$value = date_create_from_format($format, $request->request->get('CustomArchiveDate'));
						$document_info->setCustomArchiveDate($value);
						unset($value, $format);
					}
					else {
						$document_info->setCustomArchiveDate(null);
					}
		
					$em->flush();
					
					$security_check->createDocumentLog($em, 'UPDATE', $document_info);
					
					$reader_added = false;					
					if ($request->request->get('Readers')) {	
						$users	= preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $request->request->get('Readers'));
						$temp	= preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $doc_readers);
						$omitted = array_values(array_diff($temp, $users));
						
						if (!empty($omitted)) {
							for ($x = 0; $x < count($omitted); $x++) {
								if (!empty($omitted[$x])) {
									if (false !== $user_obj = $this->findUserAndCreate($omitted[$x])) {
										if (true === $customAcl->isUserGranted($document_info, $user_obj, 'view')) {
											$customAcl->removeUserACE($document_info, $user_obj, 'view');
										}
									}
									elseif (false !== $group = $this->fetchGroupMembers($omitted[$x])) {
										$customAcl->removeUserACE($document_info, $group->getRole(), 'view', true);
									}
								}
							}
						}
		
						for ($x = 0; $x < count($users); $x++) {
							if (false !== $user_obj = $this->findUserAndCreate($users[$x])) {
								if (false === $customAcl->isUserGranted($document_info, $user_obj, 'view')) {
									$customAcl->insertObjectAce($document_info, $user_obj, 'view', false);
								}
								$reader_added = true;
							}
							elseif (false !== $group = $this->fetchGroupMembers($users[$x])) {
								if (false === $customAcl->isRoleGranted($document_info, $group->getRole(), 'view')) {
									$customAcl->insertObjectAce($document_info, $group->getRole(), 'view');
								}
								$reader_added = true;
							}
						}
					}
					if ($reader_added === true) {
					    if (true === $customAcl->isRoleGranted($document_info, 'ROLE_USER', 'view')) {
					        $customAcl->removeUserACE($document_info, 'ROLE_USER', 'view', true);
					    }
					}else {
						$customAcl->removeMaskACEs($document_info, 'view');
						$customAcl->insertObjectAce($document_info, 'ROLE_USER', 'view', true);
					}
					
					$authors_added = false;
					if ($request->request->get('Authors')) {		
						$users	= preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $request->request->get('Authors'));
						$temp	= preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $doc_editors);
						$omitted = array_values(array_diff($temp, $users));
						
						if (!empty($omitted)) {
							for ($x = 0; $x < count($omitted); $x++) {
								if (!empty($omitted[$x])) {
									if (false !== $user_obj = $this->findUserAndCreate($omitted[$x])) {
										if (true === $customAcl->isUserGranted($document_info, $user_obj, 'edit')) {
											$customAcl->removeUserACE($document_info, $user_obj, 'edit');
										}
									}
									elseif (false !== $group = $this->fetchGroupMembers($omitted[$x])) {
										$customAcl->removeUserACE($document_info, $group->getRole(), 'edit', true);
									}
								}
							}
						}
		
						for ($x = 0; $x < count($users); $x++) {
							if (false !== $user_obj = $this->findUserAndCreate($users[$x]))
							{
								if (false === $customAcl->isUserGranted($document_info, $user_obj, 'edit')) {
									$customAcl->insertObjectAce($document_info, $user_obj, 'edit', false);
								}
							}
							elseif (false !== $group = $this->fetchGroupMembers($users[$x]))
							{
								if (false === $customAcl->isRoleGranted($document_info, $group->getRole(), 'edit')) {
									$customAcl->insertObjectAce($document_info, $group->getRole(), 'edit');
								}
							}
						}
						$authors_added = true;
					}
					
					if($authors_added === false){
					    $customAcl->removeMaskACEs($document_info, 'edit');
					}else if (true === $customAcl->isRoleGranted($document_info, 'ROLE_USER', 'edit')) {
					    $customAcl->removeUserACE($document_info, 'ROLE_USER', 'edit', true);
					}

					
					if ($request->request->get('subforms')) {
						if (is_array($request->request->get('subforms'))) {
							$sub_id_arr = $request->request->get('subforms');
						}
						else {
							$sub_id_arr = array($request->request->get('subforms'));
						}
							
						for ($sf = 0; $sf < count($sub_id_arr); $sf++) {
							$sub_fields_name = $em->getRepository('DocovaBundle:DesignElements')->getSubformFields($sub_id_arr[$sf]);
							if (!empty($sub_fields_name)) {
								foreach ($sub_fields_name as $field) {
									$fieldType = $field->getFieldType();
									switch ($fieldType)
									{
										case 0:
										case 2:
											$form_values = $em->getRepository('DocovaBundle:FormTextValues')->findBy(array('Document' => $document_info->getId(), 'Field' => $field->getId()));
											if (!empty($form_values)) {
												foreach ($form_values as $record) {
													$em->remove($record);
												}
												$em->flush();
											}
											break;											
										case 1:
											$form_values = $em->getRepository('DocovaBundle:FormDateTimeValues')->findBy(array('Document' => $document_info->getId(), 'Field' => $field->getId()));
											if (!empty($form_values)) {
												foreach ($form_values as $record) {
													$em->remove($record);
												}
												$em->flush();
											}
											break;
										case 3:
											$form_values = $em->getRepository('DocovaBundle:FormNameValues')->findBy(array('Document' => $document_info->getId(), 'Field' => $field->getId()));
											if (!empty($form_values)) {
												foreach ($form_values as $record) {
													$em->remove($record);
												}
												$em->flush();
											}
											$form_values = $em->getRepository('DocovaBundle:FormGroupValues')->findBy(array('Document' => $document_info->getId(), 'Field' => $field->getId()));
											if (!empty($form_values)) {
												foreach ($form_values as $record) {
													$em->remove($record);
												}
												$em->flush();
											}
											break;
										case 4:
											$form_values = $em->getRepository('DocovaBundle:FormNumericValues')->findBy(array('Document' => $document_info->getId(), 'Field' => $field->getId()));
											if (!empty($form_values)) {
												foreach ($form_values as $record) {
													$em->remove($record);
												}
												$em->flush();
											}
											break;
										case 5:
											break;
										default:
											$form_values = $em->getRepository('DocovaBundle:FormTextValues')->findOneBy(array('Document' => $document_info->getId(), 'Field' => $field->getId()));
											if (!empty($form_values)) {
												foreach ($form_values as $record) {
													$em->remove($record);
												}
												$em->flush();
											}
											break;
									}
									
									preg_match('/^mcsf_dliuploader\d$/i', $field->getFieldName(), $matches);
									if (!empty($matches[0])) {
										if ($request->request->get('OFileNames')) {
											$file_dates = preg_split('/;/', $request->request->get('OFileDates'));
											$file_names = preg_split('/;/', $request->request->get('OFileNames'));
											if ($request->request->has('mcsf_dliuploader2')) {
												$field_filenames = preg_split('/,/', $request->request->get($field->getFieldName()));
												if ($request->request->get('tmpRenamedFiles')) {
													$oldName = strstr($request->request->get('tmpRenamedFiles'), ',', true);
													foreach ($field_filenames as $index => $value) {
														if ($value === $oldName) {
															$field_filenames[$index] = str_replace($oldName.',', '', $request->request->get('tmpRenamedFiles'));
															break;
														}
													}
													$index = $value = null;
												}
											}
											else {
												$field_filenames = $file_names;
											}
											for ($i = 0; $i < count($file_names); $i++) {
												if (!in_array($file_names[$i], $field_filenames)) {
													unset($file_names[$i], $file_dates[$i]);
												}
											}
											$file_dates = array_values($file_dates);
											$file_names = array_values($file_names);
											if (!empty($file_names)) {
												for ($i = 0; $i < count($file_names); $i++) {
													if (!empty($file_dates[$i])) {
													    $tempDateString = $file_dates[$i];
													    $format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
													    $parsed = date_parse($tempDateString);
													    $is_period = (false === stripos($tempDateString, ' am') && false === stripos($tempDateString, ' pm')) ? false : true;
													    $time = (false !== $parsed['hour'] && false !== $parsed['minute']) ? ' H:i:s' : '';
													    $time .= !empty($time) && $is_period === true ? ' A' : '';
													    $tempDate = date_create_from_format($format.$time, $tempDateString);
													    $file_dates[$i] = ($tempDate === false ? new \DateTime() : $tempDate);
													}
													else {
														$file_dates[$i] = new \DateTime();
													}
												}
												$file_content = $request->files;
												
												$res = $this->moveUploadedFiles($file_content, $file_names, $file_dates, $document_info, $field);
												if ($res !== true) {
													throw $this->createNotFoundException('Could not upload files - standard.');
												}
											}
										}
										elseif (!$request->request->get('OFileDates') && !$request->request->get('tmpEditedFiles') && !$request->request->get('tmpRenamedFiles'))
										{
											$file_content = $request->files;
											if ($file_content->count()) 
											{
												$res = $this->moveUploadedFiles($file_content, array(), array(), $document_info, $field);
												if ($res !== true) {
													throw $this->createNotFoundException('Could not upload files - standard.');
												}
											}
										}

										if ($request->request->get('tmpEditedFiles')) {
											$file_dates = array();
											//$file_names = preg_split('/; /', $request->request->get('tmpEditedFiles'));
											$file_names = preg_split('/\*/', $request->request->get('tmpEditedFiles'));
											if (trim($request->request->get('tmpRenamedFiles')))
												$renamed_files = preg_split('/;/', $request->request->get('tmpRenamedFiles'));
											if (!empty($renamed_files)) 
											{
												foreach ($renamed_files as $key => $renamed) {
													$temp = explode(',', $renamed);
													$renamed_files[$key] = array(
														'original' => substr($temp[0], 2),
														'new' => substr($temp[1], 2)
													);
												}
												$renamed = $temp = $key = null;
											}
											$renamed_files = !empty($renamed_files[0]) ? $renamed_files : array();
											if (!$request->request->has('mcsf_dliuploader2') || ($request->request->has('mcsf_dliuploader1') && $field->getFieldName() !== 'mcsf_dliuploader2')) {
												for ($i = 0; $i < count($file_names); $i++) {
													if (!empty($file_names[$i])) {
														$file_dates[$i] = new \DateTime();
													}
													else {
														unset($file_names[$i]);
													}
												}
												$file_names = array_values($file_names);
												if (!empty($file_names)) {
													$file_content = $request->files;
			
													$res = $this->moveUploadedFiles($file_content, $file_names, $file_dates, $document_info, $field, 'EDIT', $renamed_files);
													if ($res !== true) {
														throw $this->createNotFoundException('Could not upload files.');
													}
												}
											}
										}
										if ($request->request->get('tmpDeletedFiles')) {
//											$file_names = preg_split('/; /', $request->request->get('tmpDeletedFiles'));
											$file_names = preg_split('/\*/', $request->request->get('tmpDeletedFiles'));
											$removes = array();
											for ($i = 0; $i < count($file_names); $i++) {
												if (!empty($file_names[$i])) {
		
													$file_record = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document' => $document_info->getId(), 'Field' => $field, 'File_Name' => $file_names[$i]));
		
													if (!empty($file_record) && (!$file_record->getCheckedOut() || $file_record->getCheckedOutBy()->getUserNameDnAbbreviated() === $this->user->getUserNameDnAbbreviated())) {
														$em->remove($file_record);
														$em->flush();
														
														if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document_info->getId().DIRECTORY_SEPARATOR.md5($file_names[$i]))) {
															//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document_info->getId().DIRECTORY_SEPARATOR.md5($file_names[$i]), 0777);
															@unlink($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document_info->getId().DIRECTORY_SEPARATOR.md5($file_names[$i]));
															$removes[] = $file_names[$i];
														}
													}
												}
											}
											if (!empty($removes)) {
												$security_check->createDocumentLog($em, 'UPDATE', $document_info, 'Deleted file(s): '.implode(',', $removes));
												unset($removes);
											}
										}
										
										//--check for renamed files that were not edited or deleted
										if (!empty(trim($request->request->get('tmpRenamedFiles')))) {
										    $edited_file_names = preg_split('/\*/', $request->request->get('tmpEditedFiles'));	    
										    $deleted_file_names = preg_split('/\*/', $request->request->get('tmpDeletedFiles'));
										    $renamed_files = preg_split('/;/', $request->request->get('tmpRenamedFiles'));
										    $filestorename = array();
										    
									            foreach ($renamed_files as $key => $renamed) {
									                $temp = explode(',', $renamed);
									                $renamed_files[$key] = array(
									                    'original' => substr($temp[0], 2),
									                    'new' => substr($temp[1], 2)
									                );
								                
									                $found = false;
									                if(!empty($edited_file_names)){
									                    foreach ($edited_file_names as $filepath){
									                        if (mb_substr($filepath, 0-mb_strlen($renamed_files[$key]['original'])-1) === ("\\".$renamed_files[$key]['original'])){
									                            $found = true;
									                            break;
									                        }else if ($filepath === $renamed_files[$key]['original']){
									                            $found = true;
									                            break;
									                        }
									                    }
									                }
									                if(!empty($deleted_file_names)){
									                    foreach ($deleted_file_names as $filepath){
									                        if (mb_substr($filepath, 0-mb_strlen($renamed_files[$key]['original'])-1) === ("\\".$renamed_files[$key]['original'])){
									                            $found = true;
									                            break;
									                        }else if ($filepath === $renamed_files[$key]['original']){
									                            $found = true;
									                            break;
									                        }
									                    }
									                }
									                if($found){
									                   unset($renamed_files[$key]); 
									                }

									            }
									            $renamed = $temp = $key = null;
									            $renamed_files = array_values($renamed_files);
									            if(!empty($renamed_files)){
									                $res = $this->renameFiles($document_info, $field, $renamed_files);
									                if ($res !== true) {
									                    throw $this->createNotFoundException('Could not rename files.');
									                }
									            }
										}
										
									}
									else {
										$separators = $summary = array();
										$fieldName = str_replace('[]', '', $field->getFieldName());
										$fieldName = (array_key_exists(strtolower($fieldName), $paramindex) ? $paramindex[strtolower($fieldName)] : $fieldName);
										
										if ($field->getMultiSeparator()) {
											$separators = implode('|', explode(' ', $field->getMultiSeparator()));
										}
										if (!empty($separators)) {
											$values = preg_split('/('.$separators.')/', $request->request->get($fieldName));
											for ($x = 0; $x < count($values); $x++) {
												$summary[] = substr($values[$x], 0, 450);
											}
										}
										else {											
											$values = array(is_array($request->request->get($fieldName)) ? implode(';', $request->request->get($fieldName)) : $request->request->get($fieldName));
											$summary = array(substr($values[0], 0, 450));
										}
										
										$len = count($values);
										for ($x = 0; $x < $len; $x++) {
											$field_value = null;
											if (is_null($values[$x])) {
												continue;
											}
											if ($field->getFieldType() == 1) {												
												$value = $this->getValidDateTimeValue($values[$x]);
												if(!empty($value)){
													$field_value = new FormDateTimeValues();
													$field_value->setFieldValue($value);
												}
											}
											elseif ($field->getFieldType() == 3) {
												$name = $this->findUserAndCreate($values[$x], true, false);
												if (false !== $name) {
													$field_value = new FormNameValues();
													$field_value->setFieldValue($name);
												}
												else {
													$name = $this->fetchGroupMembers($values[$x]);
													if (false !== $name) {
														$field_value = new FormGroupValues();
														$field_value->setFieldValue($name);
													}
												}
											}
											elseif ($field->getFieldType() == 4) {
												$value = floatval($values[$x]);
												$field_value = new FormNumericValues();
												$field_value->setFieldValue($value);
											}
											elseif ($field->getFieldType() != 5) {
												$field_value = new FormTextValues();
												$field_value->setFieldValue($values[$x]);
												$field_value->setSummaryValue($summary[$x]);
											}
											if (!empty($field_value))
											{
												$field_value->setDocument($document_info);
												$field_value->setField($field);
												$field_value->setOrder($len > 1 ? $x : null);
												$field_value->setTrash(false);
												$em->persist($field_value);
												$em->flush();
											}
										}
									}
								}
							}
						}
					}
		
					if (trim($request->request->get('tmpRequestDataXml')))
					{
						$request_data = new \DOMDocument('1.0', 'UTF-8');
						$request_data->loadXML($request->request->get('tmpRequestDataXml'));
						$wfProcess = $this->get('docova.workflowprocess');
						$wfProcess->setUser($this->user);
						$wfProcess->setGs($this->global_settings);
						if ($request_data->getElementsByTagName('Action')->item(0)->nodeValue == 'RELEASEVERSION')
						{
							if (true === $wfProcess->releaseDocument($request_data, $document_info))
							{
								$version = $request_data->getElementsByTagName('Version')->item(0)->nodeValue;
								$security_check->createDocumentLog($em, 'UPDATE', $document_info, 'Released document as version '.(!empty($version) ? $version : '0.0.0'));
							}
							else {
								//@TODO: log the possible error(s)
							}
						}
						elseif ($request_data->getElementsByTagName('Action')->item(0)->nodeValue == 'REVIEW')
						{
							$wfProcess->wfServiceREVIEW($request_data, $document_info);
						}
					}
					elseif ($request->request->get('tmpWorkflowDataXml'))
					{
						$workflow_name = $request->request->get('wfWorkflowName');
						$workflow_obj = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('Workflow_Name' => $workflow_name));
						if (empty($workflow_obj))
						{
							throw $this->createNotFoundException('Unspecified source workflow with name = '. $workflow_name);
						}
					
						$request_workflow = new \DOMDocument('1.0', 'UTF-8');
						$request_workflow->loadXML($request->request->get('tmpWorkflowDataXml'));
						$wfProcess = $this->get('docova.workflowprocess');
						$wfProcess->setUser($this->user);
						$wfProcess->setGs($this->global_settings);
						
						if ($document_info->getDocSteps()->count() == 0)
						{
							if ($request->request->get('wfEnableImmediateRelease') == 1 && $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue === 'FINISH') 
							{
								$wfProcess->releaseDocument($request_workflow, $document_info, true);
							}
							else {
								$wfProcess->createDocumentWorkflow($document_info, $request_workflow);
								if ($request->request->get('isWorkflowStarted') == 1)
								{
									$step = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getFirstPendingStep($document_info->getId());
									if (empty($step[0]))
									{
										//@TODO: In future this exception is not required to be thrown, just need to be logged and the process should continue
										throw $this->createNotFoundException('There is no current active step to start the workflow');
									}
									
									$step = $step[0];
									$em->refresh($step);
									$assignee = $this->searchUserInCollection($step->getAssignee(), $this->user, true);
									if (!empty($assignee['id']))
									{
										$step_assignee = $em->getReference('DocovaBundle:WorkflowAssignee', $assignee['record']);
										$em->remove($step_assignee);
										$completer = new WorkflowCompletedBy();
										$completer->setCompletedBy($this->user);
										$completer->setDocWorkflowStep($step);
										$completer->setGroupMember($assignee['gType']);
										$em->persist($completer);
										$em->flush();
										$step_assignee = $completer = null;
	
										if (!empty($request_workflow->getElementsByTagName('UserComment')->item(0)->nodeValue))
										{
											$comment_txt = trim($request_workflow->getElementsByTagName('UserComment')->item(0)->nodeValue);
											$comment_subform = $em->getRepository('DocovaBundle:DocumentTypes')->containsSubform($document_info->getDocType()->getId(), 'Advanced Comments');
											if (!empty($comment_subform))
											{
												$temp_xml = new \DOMDocument();
												$temp_xml->loadXML($comment_subform['Properties_XML']);
												if (!empty($temp_xml->getElementsByTagName('LinkComments')->item(0)->nodeValue))
												{
													$comment = new DocumentComments();
													$comment->setComment($comment_txt);
													$comment->setCommentType(2);
													$comment->setCreatedBy($this->user);
													$comment->setDateCreated(new \DateTime());
													$comment->setDocument($document_info);
													$em->persist($comment);
													$em->flush();
												}
												unset($temp_xml, $comment_subform);
											}
										}

										$completed = false;
										if ($step->getCompleteOn() === 0 && $step->getAssignee()->count() < 1)
										{
											$completed = true;
										}
										elseif ($step->getCompleteOn() > 1 && $step->getCompletedBy()->count() == ($step->getCompleteOn() - 2)) {
											$completed = true;
										}
										elseif ($step->getCompleteOn() == 1 || $step->getCompleteOn() === null) {
											$completed = true;
										}
										
										if ($completed === true) 
										{
											$step->setDateCompleted(new \DateTime());
											$step->setStatus('Completed');
											$em->flush();
											$security_check->createDocumentLog($em, 'WORKFLOW', $document_info, $step->getStepName().' - Completed workflow step.'.(!empty($comment_txt) ? ' Comments: '.$comment_txt : ''));
											$wfProcess->sendWfNotificationIfRequired($step, 2);
										}
										else {
											$security_check->createDocumentLog($em, 'WORKFLOW', $document_info, $step->getStepName().' - Completed workflow task.'.(!empty($comment_txt) ? ' Comments: '.$comment_txt : ''));
										}
									}
									unset($step);
									
									if (!empty($completed))
									{
										$em->refresh($document_info);
										$steps = $document_info->getDocSteps();
										foreach ($steps as $next_step)
										{
											if ($next_step->getStatus() === 'Pending' && !$next_step->getDateStarted() && !$next_step->getDateCompleted())
											{
												$next_step->setDateStarted(new \DateTime());
												if ($next_step->getDocStatus())
												{
													$document_info->setDocStatus($next_step->getDocStatus());
													$document_info->setStatusNo(0);
												}
												$document_info->setDateModified(new \DateTime());
												$document_info->setModifier($this->user);
												$em->flush();
												$em->refresh($next_step);
												$wfProcess->sendWfNotificationIfRequired($next_step, 1);
												break;
											}
										}
									}
								}
							}
						}
						elseif ($request->request->get('isWorkflowStarted') == 1 || $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue === 'UPDATE')
						{
							$res = $wfProcess->workflowItemProcess($request_workflow, $document_info);
							if (empty($res))
							{
								throw $this->createNotFoundException('Could not update workflow step action.');
							}
						}
						elseif ($request->request->get('wfEnableImmediateRelease') == 1 && $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue === 'FINISH') {
							// do we need to stop the process if release failed?!
							$wfProcess->releaseDocument($request_workflow, $document_info, true);
						}
					}
					if ($document_info->getDocType()->getTranslateSubjectTo()) {
						$value = trim($document_info->getDocType()->getTranslateSubjectTo());
						if (preg_match('~^[<?].*[?>]$~', $value))
						{
							$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
							$function = create_function('$docova', $value);
							$subject_txt = $function($docova);
						}
						else {
							$subject_txt = $value;
						}
					}
					else {
						$subject_txt	= $request->request->get('Subject');
					}
					$document_info->setDocTitle($subject_txt);
					$em->flush();
						
					if ($is_mobile === true)
					{
						if ($mobile_type === "mobileApp"){
							return $this->redirect($this->generateUrl('docova_mobile_app').'#folder?libid='.$document_info->getFolder()->getLibrary()->getId().'&folderkey='.$document_info->getFolder()->getId().'&folderid='.$document_info->getFolder()->getId().'&listtitle='.$document_info->getFolder()->getFolderName());
					    }
					    else if($mobile_type === "workflow"){
					    // to back to workflow action list
					    		return $this->redirect($this->generateUrl('docova_mobile_workflow_screen_redirect').'?device='.$mobile_app_device);
					    }
					    else {
					    	// mobile page
					    		return $this->redirect($this->generateUrl('docova_mobile').'#folder?libid='.$document_info->getFolder()->getLibrary()->getId().'&folderkey='.$document_info->getFolder()->getId().'&folderid='.$document_info->getFolder()->getId().'&listtitle='.$document_info->getFolder()->getFolderName());			    	
					    }
		
					}
					elseif ($request->query->get('mode') == 'dle') {
						$xml_result = new \DOMDocument('1.0', 'UTF-8');
						$root = $xml_result->appendChild($xml_result->createElement('Results'));
						$newnode = $xml_result->createElement('Result', 'OK');
						$attrib = $xml_result->createAttribute('ID');
						$attrib->value = 'Status';
						$newnode->appendChild($attrib);
						$root->appendChild($newnode);
						$newnode = $xml_result->createElement('Unid', $document_info->getId());
						$root->appendChild($newnode);
						$newnode = $xml_result->createElement('Url', $this->generateUrl('docova_homepage'));
						$root->appendChild($newnode);
						
						$response = new Response();
						$response->headers->set('Content-Type', 'text/xml');
						$response->setContent($xml_result->saveXML());
						return $response;
					}
					else {
						if ($request->get('tmpMode') == 'nodoe') {
							$response = new Response();
							$response->setContent('url;' . $this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$doc_id.'&fid='.$document_info->getFolder()->getId().'&mode='.$request->query->get('mode'));
							return $response;
						}
						else {
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$doc_id.'&fid='.$document_info->getFolder()->getId().'&mode='.$request->query->get('mode'));
						}
					}
				}
				else {
					$response = new Response();
					$response->headers->set('Content-Type', 'text/xml');
					if ($request->request->get('%%Detach')) {
						$file_name = $request->request->get('%%Detach');
						if (!empty($file_name)) {
							$file_record = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document' => $document_info->getId(), 'File_Name' => $file_name, 'Checked_Out' => false));
	
							if (!empty($file_record)) {
								$field = $file_record->getField();
								$em->remove($file_record);
								$em->flush();
								
								if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document_info->getId().DIRECTORY_SEPARATOR.md5($file_name))) {
									//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document_info->getId().DIRECTORY_SEPARATOR.md5($file_names[$i]), 0777);
									@unlink($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document_info->getId().DIRECTORY_SEPARATOR.md5($file_name));
								}
							}
						}
					}
	
					if (($request->request->get('tmpEditedFiles') || $request->request->get('OFileNames')) && $request->files->count()) {
						$file_name = array($request->request->get('tmpEditedFiles'));
						$file_date = array(new \DateTime());
						if (!in_array($request->request->get('OFileNames'), $file_name)) {
							$file_name[] = $request->request->get('OFileNames');
							$file_date[] = new \DateTime();
						}
						$file_content = $request->files;
	
						if (empty($field))
						{
							$field = $em->getRepository('DocovaBundle:DesignElements')->getUploaderField($document_info->getDocType()->getId());
						}
	
						$file = $em->getRepository('DocovaBundle:AttachmentsDetails')->containsFile($document_info->getId(), $file_name);
						if (empty($file) && !empty($field)) {
							$res = $this->moveUploadedFiles($file_content, $file_name, $file_date, $document_info, $field);
							if ($res === true) {
								$xml_result = '<?xml version="1.0" encoding="UTF-8" ?>';
								$xml_result .= '<Results>';
								$xml_result .= '<Result ID="Status">OK</Result>';
								$xml_result .= '<Unid>'.$document_info->getId().'</Unid>';
								$xml_result .= '<Url>'.$this->generateUrl('docova_opendocfile', array('file_name' => (!empty($file_name[1]) ? $file_name[1] : $file_name[0])), true) . '?doc_id=' . $document_info->getId().'</Url>';
								$xml_result .= '<date
										Y="'.$file_date[0]->format('Y').'"
										M="'.$file_date[0]->format('m').'"
										D="'.$file_date[0]->format('d').'"
										H="'.$file_date[0]->format('H').'"
										MN="'.$file_date[0]->format('i').'"
										S="'.$file_date[0]->format('s').'">
										<![CDATA['.$file_date[0]->format('d/m/Y H:i:s A').']]></date>';
								$xml_result .= '</Results>';
								$response->setContent($xml_result);
								return $response;
							}
						}
					}
					$xml_result = '<?xml version="1.0" encoding="UTF-8" ?>';
					$xml_result .= "<Results><Result ID=\"Status\">FAILED</Result></Results>";
					$response->setContent($xml_result);
					return $response;
				}
			}
			// POST ends here
		}

		$formula_values = array();
		if (trim($document_info->getDocType()->getCustomReleaseButtonLabel()))
		{
			$value = trim($document_info->getDocType()->getCustomReleaseButtonLabel());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Release_Label'] = $function($docova);
			}
			else {
				$formula_values['Release_Label'] = $value;
			}
		}
		if (trim($document_info->getDocType()->getCustomChangeDocAuthor()))
		{
			$value = trim($document_info->getDocType()->getCustomChangeDocAuthor());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Changing_Author_Enabled'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomHideDescription()))
		{
			$value = trim($document_info->getDocType()->getCustomHideDescription());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Description'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomHideKeywords()))
		{
			$value = trim($document_info->getDocType()->getCustomHideKeywords());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Keywords'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomHideReviewCycle()))
		{
			$value = trim($document_info->getDocType()->getCustomHideReviewCycle());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Review_Cycle'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomHideArchiving()))
		{
			$value = trim($document_info->getDocType()->getCustomHideArchiving());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Archiving'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomChangeOwner()))
		{
			$value = trim($document_info->getDocType()->getCustomChangeOwner());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Changing_Owner_Enabled'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomChangeAddEditors()))
		{
			$value = trim($document_info->getDocType()->getCustomChangeAddEditors());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Changing_Editors_Enabled'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomChangeReadAccess()))
		{
			$value = trim($document_info->getDocType()->getCustomChangeReadAccess());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Changing_Readers_Enabled'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomRestrictPrinting()))
		{
			$value = trim($document_info->getDocType()->getCustomRestrictPrinting());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Restrict_Printing'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getSaveButtonHideWhen()))
		{
			$value = trim($document_info->getDocType()->getSaveButtonHideWhen());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Show_Save_Close'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomHeaderHideWhen()))
		{
			$value = trim($document_info->getDocType()->getCustomHeaderHideWhen());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Hide_Header'] = $function($docova);
			}
		}
		if (trim($document_info->getDocType()->getCustomButtonsHideWhen()))
		{
			$value = trim($document_info->getDocType()->getCustomButtonsHideWhen());
			if (preg_match('~^[<?].*[?>]$~', $value))
			{
				$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
				$function = create_function('$docova', $value);
				$formula_values['Hide_Release'] = $function($docova);
			}
		}

		if (!empty($folder_managers) && count($folder_managers) > 0)
		{
			foreach ($folder_managers as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false))
				{
					$folder_managers[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}

		if (!empty($folder_editors) && count($folder_editors) > 0)
		{
			foreach ($folder_editors as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false))
				{
					$folder_editors[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}

		if (!empty($folder_authors) && count($folder_authors) > 0)
		{
			foreach ($folder_authors as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false))
				{
					$folder_authors[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}
		
		if (!empty($folder_readers) && count($folder_readers) > 0)
		{
			foreach ($folder_readers as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false))
				{
					$folder_readers[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}
		
		if (!empty($authors) && count($authors) > 0)
		{
			foreach ($authors as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false))
				{
					$authors[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}
		
		if (!empty($readers) && count($readers) > 0)
		{
			foreach ($readers as $index => $username) {
				if (false !== $user = $this->findUserAndCreate($username, false, true, false))
				{
					$readers[$index] = $user->getUserNameDnAbbreviated();
				}
				unset($user);
			}
		}

		$reviewers = array();
		if ($document_info->getHasPendingReview() === true)
		{
			$reviewers = $em->getRepository('DocovaBundle:ReviewItems')->getPendingDocumentReviewers($document->getId());
		}

		$incompleteEdits = '';
		$incompleted_items = $em->getRepository('DocovaBundle:TempEditAttachment')->findBy(array('document' => $document_info->getId(), 'trackUser' => $this->user->getId()));
		if (!empty($incompleted_items))
		{
			foreach ($incompleted_items as $item) {
				$incompleteEdits .= (str_replace('\\', '\\\\', $item->getFilePath()) . $item->getFileName() . '; ');
			}
			$incompleteEdits = substr_replace($incompleteEdits, '', -2);
		}
		unset($incompleted_items, $item);
		
		$template_path = ($is_mobile === true) ? 'Mobile:mEditDocument' : 'Default:genericDocumentTemplate';
		return $this->render('DocovaBundle:'.$template_path.'.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings,
				'user_access' => $access_level,
				'folder_info' => $document_info->getFolder(),
				'document_type' => $document_info->getDocType(),
				'action_buttons' => $action_buttons,
				'workflow_options' => $doc_workflow_options,
				'workflows' => $rendered_workflows,
				'has_draft' => $has_draft,
				'is_initial_version' => $is_initial_version,
				'available_versions' => $available_versions,
				'link_comments' => $link_comments,
				'has_attachment' => $has_attachment,
				'attachment_settings' => $attachment_settings,
				'rel_doc_settings' => $related_doc_settings,
				'text_content_type' => $text_content_type,
				'incompleteEdits' => $incompleteEdits,
				'templates' => $templates,
				'subforms' => $rendered_subforms,
				'managers' => $folder_managers,
				'authors' => $authors,
				'readers' => $readers,
				'folder_editors' => $folder_editors,
				'folder_authors' => $folder_authors,
				'folder_readers' => $folder_readers,
				'doc_editors' => $doc_editors,
				'doc_readers' => $doc_readers,
				'translated_values' => $formula_values,
				'document_detail' => $document_info,
				'document' => $document_info,
				'reviewers' => $reviewers
		));
	}

	public function openAdminHtmlResourceAction($file_name)
	{
		if (empty($file_name))
		{
			throw $this->createNotFoundException('Unspecified source File Template.');
		}
	
		$file_info = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:HtmlResources')->findOneBy(array('fileName'=>$file_name));
		if (empty($file_info))
		{
			throw $this->createNotFoundException('Unspecified source File Template with file name = '. $file_name);
		}
	
		$file_path_header = $this->generatePathHeader($file_info, 'HtmlResources');
		if (empty($file_path_header)) {
			throw $this->createNotFoundException('Could not download or find the selected file.');
		}
	
		$response = new Response(file_get_contents($file_path_header['file_path']), 200);
		$response->headers->add($file_path_header['headers']);
		return $response;
		
	}
	
	public function openFileTemplateAction($file_id, $file_name)
	{
		if (empty($file_id) || empty($file_name)) 
		{
			throw $this->createNotFoundException('Unspecified source File Template.');
		}
		
		$file_info = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:FileTemplates')->findOneBy(array('id'=>$file_id, 'File_Name'=>$file_name));
		if (empty($file_info))
		{
			throw $this->createNotFoundException('Unspecified source File Template with ID = '. $file_id);
		}
		
		$file_path_header = $this->generatePathHeader($file_info, 'Templates');
		if (empty($file_path_header)) {
			throw $this->createNotFoundException('Could not download or find the selected file.');
		}
		
		$response = new StreamedResponse();
		$response->setCallback(function() use($file_path_header) {
			$handle = fopen($file_path_header['file_path'], 'r');
			while (!feof($handle)) {
				$buffer = fread($handle, 1024);
				echo $buffer;
				flush();
			}
			fclose($handle);
		});
		$response->headers->add($file_path_header['headers']);
		$response->setStatusCode(200);
		return $response;
	}
	
	public function openDocFileAction(Request $request, $file_name)
	{
		$this->initialize();
		if ($request->query->get('topic')) 
		{
			$topic = $request->query->get('topic');
		}
		else {
			$doc_id = $request->query->get('doc_id');
		}
		if (empty($file_name) || (empty($doc_id) && empty($topic))) {
			throw $this->createNotFoundException('File Name or Document ID is missed.');
		}
		
		$security_check = new Miscellaneous($this->container);
		$em = $this->getDoctrine()->getManager();
		if (!empty($topic)) 
		{
			$doc_file_info = $em->getRepository('DocovaBundle:DiscussionAttachments')->findOneBy(array('fileName' => $file_name, 'discussion' => $topic));
			if (empty($doc_file_info)) {
				throw $this->createNotFoundException('Unspecified discussion topic source.');
				return;
			}
			$doc_obj = $doc_file_info->getDiscussion()->getParentDocument();
		}
		else {
			$doc_obj = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id));
		}

		if (empty($doc_obj)) {
			throw $this->createNotFoundException('There is no document with ID = '. $doc_id);
			return;
		}
		
		if (!$doc_obj->getApplication()) {
			if (false === $security_check->canReadDocument($doc_obj, true)) {
			    throw $this->createAccessDeniedException('You are not authorized to access this file!');
			    return;
			}
		}
		
		if (empty($doc_file_info)) 
		{
			$doc_file_info = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('File_Name' => $file_name, 'Document' => $doc_id));
			if (empty($doc_file_info)) {

				//now lets see if its there through a word merge..it may or may not be a part of uploader
				$flpath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$doc_obj->getId();
				if (file_exists($flpath.DIRECTORY_SEPARATOR.md5($file_name))) {
						$file_path_header = $this->generatePathHeaderEx($file_name, $flpath.DIRECTORY_SEPARATOR.md5($file_name));
				}else{
					throw $this->createNotFoundException('Could not find file "'.$file_name.'" for this document.');
				}
			}
		}
		
		if ( empty ($file_path_header))
			$file_path_header = $this->generatePathHeader($doc_file_info);

		if (empty($file_path_header)) {
			throw $this->createNotFoundException('Could not download or find the selected file.');
		}
		
		$response = new StreamedResponse();
		$response->setCallback(function() use($file_path_header) {
			$handle = fopen($file_path_header['file_path'], 'r');
			while (!feof($handle)) {
				$buffer = fread($handle, 1024);
				echo $buffer;
				flush();
			}
			fclose($handle);
		});
		$response->headers->add($file_path_header['headers']);
		$response->setStatusCode(200);
		return $response;
	}
	
	public function viewServicesAction(Request $request)
	{
		$post_req = $request->getContent();
		if (empty($post_req)) {
			throw $this->createNotFoundException('No post request is submitted.');
			return;
		}
		
		$this->initialize();
		$post_xml = new \DOMDocument();
		$post_xml->loadXML(urldecode($post_req));
		
		$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
		if ($action === 'PASTE') {
			$target_folder	= $post_xml->getElementsByTagName('targetfolder')->item(0)->nodeValue;
			
			if (empty($target_folder) || empty($post_xml->getElementsByTagName('Unid')->length)) {
				throw $this->createNotFoundException('Target folder or Document ID is missed.');
			}
			
			$em = $this->getDoctrine()->getManager();
			$folder_obj	= $em->find('DocovaBundle:Folders', $target_folder);
			if (empty($folder_obj)) 
			{
				throw $this->createNotFoundException('Could not find any folder for ID = '. $target_folder);
			}
			
			$security_check = new Miscellaneous($this->container);
			$done = false;
			foreach ($post_xml->getElementsByTagName('Unid') as $item) 
			{
				$document_obj = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $item->nodeValue, 'Archived' => false));
				if (!empty($document_obj))
				{
					if (true === $security_check->canCreateDocument($folder_obj))
					{
						$clip_action = $post_xml->getElementsByTagName('clipaction')->item(0)->nodeValue;
						if ($clip_action == 'copy') {
							$this->pasteFolderDocuments($folder_obj, array($document_obj));
						}
						elseif ($clip_action == 'cut') 
						{
							$document_obj->setFolder($folder_obj);
							$document_obj->setModifier($this->user);
							$document_obj->setIndexed(false);
							$document_obj->setDateModified(new \DateTime());
							
							$em->flush();
							$security_check->createDocumentLog($em, 'UPDATE', $document_obj, 'Moved Document.');
						}
						$done = true;
					}
				}
			}
			
			$response_xml = new \DOMDocument("1.0", "UTF-8");
			$root = $response_xml->appendChild($response_xml->createElement('Results'));
			if ($done === true) 
			{
				$child = $response_xml->createElement('Result', 'OK');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			else {
				$child = $response_xml->createElement('Result', 'FAILED');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
				
				$child = $response_xml->createElement('Result', 'Could not complete copying selected documents. Try again or contact admin.');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'ErrMsg';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			$response = new Response($response_xml->saveXML());
			$response->headers->set('Content-Type', 'text/xml');
			
			return $response;
		}
		if ($action === 'PASTEDOCUMENTS') {				
			$em = $this->getDoctrine()->getManager();
			if (empty($post_xml->getElementsByTagName('unid')->length)) {
				throw $this->createNotFoundException('No documents selected for pasting.');
			}
			
			$clip_action = $post_xml->getElementsByTagName('clipaction')->item(0)->nodeValue;
			if(empty($clip_action)){
				$clip_action = 'copy';
			}	
			$targetapp =  $post_xml->getElementsByTagName('targetApp')->item(0)->nodeValue;
			$srcview =  $post_xml->getElementsByTagName('srcView')->item(0)->nodeValue;
			$srcapp =  $post_xml->getElementsByTagName('srcApp')->item(0)->nodeValue;

			$source = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $srcapp, 'isApp' =>  true , 'Trash' => false));
			if (empty($source))
			{
				throw new \Exception('Source App/Library cannot be found.');
			}

			$target_app_entity = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $targetapp, 'isApp' =>  true , 'Trash' => false));
			if (empty($target_app_entity))
			{
				throw new \Exception('Target App/Library cannot be found.');
			}
				
			$security_check = new Miscellaneous($this->container);
			
			$dateformat = $this->global_settings->getDefaultDateFormat();
			$display_names = $this->global_settings->getUserDisplayDefault();
			$repository = $em->getRepository('DocovaBundle:Documents');
			$twig = $this->container->get('twig');	
			$appid = null;
			$view_handler = null;
			$views = null;
			
			$done = false;
			$installassets = false;

			if ( $srcview == "AppForms" || $srcview == "AppViews" || $srcview == "AppLayouts" || $srcview == "AppPages" ||  $srcview == "AppSubforms" || $srcview == "AppMenus"|| $srcview == "AppJS" || $srcview == "AppAgents" || $srcview == "AppScriptLibraries" || $srcview == "AppImages"|| $srcview == "AppCSS")
			{
				$copy_handler = new CopyDesignServices($this->docova, $source, $this->user, $this->get('kernel')->getRootDir());
				$copy_handler->setTargetPath($targetapp);
				$copy_handler->setTargetApp($target_app_entity);
				$installassets = true;
			}

			foreach ($post_xml->getElementsByTagName('unid') as $item)
			{
				if ( $srcview == "AppForms" )
				{
					$form_entity = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($form_entity)){
						throw new \Exception('Unable to find form with id '.$item->nodeValue);
					}

					$copy_handler->copyForm($form_entity);
				}else if ($srcview == "AppViews"){
					$view_entity = $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($view_entity)){
						throw new \Exception('Unable to find view with id '.$item->nodeValue);
					}

					$viewid = $copy_handler->copyView( $view_entity);
				}else if ($srcview == "AppLayouts"){
					$_entity = $em->getRepository('DocovaBundle:AppLayout')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($_entity)){
						throw new \Exception('Unable to find layout with id '.$item->nodeValue);
					}
				
					$copy_handler->copyLayout( $_entity);

				}else if ($srcview == "AppPages"){
					$_entity = $em->getRepository('DocovaBundle:AppPages')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($_entity)){
						throw new \Exception('Unable to find page with id '.$item->nodeValue);
					}
				
					$copy_handler->copyPage( $_entity);
				}else if ($srcview == "AppSubforms"){
					$_entity = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($_entity)){
						throw new \Exception('Unable to find subform with id '.$item->nodeValue);
					}
				
					$copy_handler->copySubform( $_entity);
				}else if ($srcview == "AppMenus"){
					$_entity = $em->getRepository('DocovaBundle:AppOutlines')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($_entity)){
						throw new \Exception('Unable to find Menu with id '.$item->nodeValue);
					}
				
					$copy_handler->copyOutline( $_entity);
				}else if ($srcview == "AppJS"){
					$_entity = $em->getRepository('DocovaBundle:AppJavaScripts')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($_entity)){
						throw new \Exception('Unable to find Javascript library with id '.$item->nodeValue);
					}
				
					$copy_handler->copyJavasScript( $_entity);
				}else if ($srcview == "AppAgents"){
					$_entity = $em->getRepository('DocovaBundle:AppAgents')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($_entity)){
						throw new \Exception('Unable to find Agent with id '.$item->nodeValue);
					}
				
					$copy_handler->copyAgent( $_entity);
				}else if ($srcview == "AppScriptLibraries"){
					$_entity = $em->getRepository('DocovaBundle:AppPhpScripts')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($_entity)){
						throw new \Exception('Unable to find Script Library with id '.$item->nodeValue);
					}
				
					$copy_handler->copyScriptLibrary( $_entity);
				}else if ($srcview == "AppImages"){
					$_entity = $em->getRepository('DocovaBundle:AppFiles')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($_entity)){
						throw new \Exception('Unable to find image with id '.$item->nodeValue);
					}
				
					$copy_handler->copyImage( $_entity);
				}else if ($srcview == "AppCSS"){
					$_entity = $em->getRepository('DocovaBundle:AppCss')->findOneBy(array('id' =>  $item->nodeValue));

					if ( empty($_entity)){
						throw new \Exception('Unable to find css with id '.$item->nodeValue);
					}
				
					$copy_handler->copyCss( $_entity);
				}else{
					$document_obj = $repository->findOneBy(array('id' => $item->nodeValue, 'Archived' => false, 'Trash' => false));
					if (!empty($document_obj))
					{
						if(empty($appid)){
							$appid = $document_obj->getApplication()->getId();
						}
						if(empty($view_handler)){
							$view_handler = new ViewManipulation($this->docova, $appid, $this->global_settings);
						}
						if(empty($views)){
							$views = $em->getRepository('DocovaBundle:AppViews')->findBy(array('application' => $appid));					
						}
						
						$newdoc = $this->copyDocument($document_obj, true);	
						
						//-- update the view indexes
						if (!empty($newdoc) && !empty($views))
						{
							$doc_values = $repository->getDocFieldValues($newdoc->getId(), $dateformat, $display_names, true);
							
							foreach ($views as $view) {
								try {
									$view_handler->indexDocument2($newdoc->getId(), $doc_values, $appid, $view->getId(), $view->getViewPerspective(), $twig, false, $view->getConvertedQuery());
								}
								catch (\Exception $e) {
								}
							}
						}					
						$done = true;
					}
				}
			}

			if ( $installassets){
				$tempapp = new Application($this->get('kernel'));
				$tempapp->setAutoExit(false);
				
				$input = new ArrayInput(array(
					'command' => 'docova:appassetsinstall',
					'appid' => $targetapp
				));
				
				$tempapp->run($input, new NullOutput());
				if ($srcview == 'AppViews') {
					$input = new ArrayInput([
						'command' => 'docova:importviewdata',
						'app' => $targetapp,
						'view' => $viewid
					]);
					$tempapp->run($input, new NullOutput());
				}
				$done = true;
			}
			
			unset($views, $view_handler, $app);
			
			$response_xml = new \DOMDocument("1.0", "UTF-8");
			$root = $response_xml->appendChild($response_xml->createElement('Results'));
			if ($done === true)
			{
				$child = $response_xml->createElement('Result', 'OK');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			else {
				$child = $response_xml->createElement('Result', 'FAILED');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
		
				$child = $response_xml->createElement('Result', 'Could not complete copying selected documents. Try again or contact admin.');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'ErrMsg';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			$response = new Response($response_xml->saveXML());
			$response->headers->set('Content-Type', 'text/xml');
				
			return $response;
		}		
		elseif ($action === 'DELETESELECTED')
		{
			if (empty($post_xml->getElementsByTagName('Unid')->length) || empty($post_xml->getElementsByTagName('Unid')->item(0)->nodeValue))
			{
				throw $this->createNotFoundException('Document ID(s) is/are missed.');
			}
			
			$deleted = 0;
			$security_check = new Miscellaneous($this->container);
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue, 'Archived' => false));
			if (true === $security_check->canSoftDeleteDocument($document))
			{
				unset($document);
				foreach ($post_xml->getElementsByTagName('Unid') as $doc_id)
				{
					$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id->nodeValue, 'Archived' => false));
					if (!empty($document))
					{
						if ($document->getFolder()->getFolderName() == html_entity_decode($post_xml->getElementsByTagName('FolderName')->item(0)->nodeValue))
						{
							$document->setTrash(true);
							$document->setDateDeleted(new \DateTime());
							$document->setDeletedBy($this->user);
							$bmk = $document->getBookmarks();
							if ($bmk->count()) {
								foreach ($bmk as $b) {
									$em->remove($b);
								}
							}
							$em->flush();
							$deleted++;
						
							$security_check->createDocumentLog($em, 'DELETE', $document);
						}
						else {
							$query = $em->getRepository('DocovaBundle:Bookmarks')->createQueryBuilder('B')
									->join('B.Target_Folder', 'F')
									->where('B.Document = :document')
									->andWhere('F.Folder_Name = :folder')
									->setParameters(array('document' => $document->getId(), 'folder' => html_entity_decode($post_xml->getElementsByTagName('FolderName')->item(0)->nodeValue)))
									->getQuery();
							$bmk = $query->getSingleResult();
							$em->remove($bmk);
							$em->flush();
							$deleted++;
						}
					}
					$document = $bmk = $b = null;
				}
			}
			
			$response_xml = new \DOMDocument("1.0", "UTF-8");
			$root = $response_xml->appendChild($response_xml->createElement('Results'));
			if ($deleted !== 0)
			{
				$child = $response_xml->createElement('Result', 'OK');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);

				$child = $response_xml->createElement('Result', $deleted+1);
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Ret1';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			else {
				$child = $response_xml->createElement('Result', 'FAILED');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
				
				$child = $response_xml->createElement('Result', 'You have insufficient access to delete one or more of the selected documents.');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'ErrMsg';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			$response = new Response($response_xml->saveXML());
			$response->headers->set('Content-Type', 'text/xml');
			return $response;
		}
		elseif ($action === 'UNDELETESELECTED')
		{
			if (empty($post_xml->getElementsByTagName('Unid')->length) || empty($post_xml->getElementsByTagName('Unid')->item(0)->nodeValue))
			{
				throw $this->createNotFoundException('Document ID(s) is/are missed.');
			}
				
			$security_check = new Miscellaneous($this->container);
			$restored = 0;
			$em = $this->getDoctrine()->getManager();
			foreach ($post_xml->getElementsByTagName('Unid') as $index => $doc_id)
			{
				if ($post_xml->getElementsByTagName('rectype')->item($index)->nodeValue == 'fld') {
					$document = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $doc_id->nodeValue, 'Del' => true));
					if (!empty($document)) 
					{
						$document->setDel(false);
						$document->setDateDeleted();
						$document->setDeletedBy();
						$em->flush();
						$em->getRepository('DocovaBundle:Folders')->resetInactiveSubfolders($document->getPosition(), $document->getLibrary()->getId());
						
						$restored++;
						$security_check->createFolderLog($em, 'DELETE', $document, 'Restored document from recycle bin.');
					}
				}
				else {
					$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id->nodeValue, 'Trash' => true, 'Archived' => false));
					if (!empty($document)) 
					{
						$document->setTrash(false);
						$document->setDateDeleted();
						$document->setDeletedBy();
						$em->flush();
						
						$restored++;
						$security_check->createDocumentLog($em, 'DELETE', $document, 'Restored document from recycle bin.');
					}
				}
				unset($document);
			}

			$response_xml = new \DOMDocument("1.0", "UTF-8");
			$root = $response_xml->appendChild($response_xml->createElement('Results'));
			if ($restored > 0)
			{
				$child = $response_xml->createElement('Result', 'OK');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
			
				$child = $response_xml->createElement('Result', $restored+1);
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Ret1';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			else {
				$child = $response_xml->createElement('Result', 'FAILED');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
			
				$child = $response_xml->createElement('Result', 'Could not restore the selected items.');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'ErrMsg';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			$response = new Response($response_xml->saveXML());
			$response->headers->set('Content-Type', 'text/xml');
			return $response;
		}
		elseif ($action === 'REMOVE')
		{
			if (!empty($post_xml->getElementsByTagName('Unid')->length)) 
			{
				$cnt = 0;
				$source_type = !empty($post_xml->getElementsByTagName('SourceType')->length) ? $post_xml->getElementsByTagName('SourceType')->item(0)->nodeValue : false;
				$em = $this->getDoctrine()->getManager();
				if ($source_type !== false && $source_type != 'customwidgets')
				{
					$app = $request->query->get('AppID');
					$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'isApp' => true, 'Trash' => false));
					if (empty($app)) {
						throw $this->createNotFoundException('Unspecified application source ID.');
					}
					
					$view = null;
					if (false !== strpos($source_type, 'Application-'))
					{
						$viewid = substr(strstr($source_type, '-'), 1);
						$view = $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('id' => $viewid, 'application' => $app->getId()));
						if (empty($view)) {
							throw $this->createNotFoundException('Unspecified application view name.');
						}
					}

					$docova = new Docova($this->container);
					$security_check = $docova->DocovaAcl($app);
					$docova = null;
					$isadmin = $security_check->isDesigner() || $security_check->isManager();
				}
				
				foreach ($post_xml->getElementsByTagName('Unid') as $item)
				{
					if ($source_type === false)
					{
						$id = trim($item->nodeValue);
						$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $id, 'Trash' => true));
						if (!empty($document) && $this->deleteDocumentPermanently($document)) {
							$cnt++;
						}
						else {
							$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $id, 'Del' => true));
							if (!empty($folder) && $this->deleteFolderPermanently($folder)) {
								$cnt++;
							}
						}
					}
					else {					
						$designPath = $this->get('kernel')->getRootDir() . '/../src/Docova/DocovaBundle/Resources/views/DesignElements/';
						switch ($source_type)
						{
							case 'AppForms':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								if (!empty($form))
								{
									$form->setTrash(true);
									$name = str_replace(array('/', '\\'), '-', $form->getFormName());
									$name = str_replace(' ', '', $name);
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.$name.'_read.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.$name.'_m_read.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.$name.'.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.$name.'_m.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.$name.'_computed.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.$name.'_default.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR . '../../../public/js/custom' . DIRECTORY_SEPARATOR . 'FORM'.DIRECTORY_SEPARATOR.$name.'.js');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'FORM'.DIRECTORY_SEPARATOR.$name.'.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'FORM'.DIRECTORY_SEPARATOR.$name.'_m.html.twig');
									
									$em->flush();
									$cnt++;
								}
								break;
							case 'AppViews':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$view = $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								$name = str_replace(' ', '', $view->getViewName());
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'toolbar'.DIRECTORY_SEPARATOR.$name.'.html.twig');
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR.$name.'.html.twig');
								$view_id = str_replace('-', '', $view->getId());
								$em->remove($view);
								$em->flush();
								$view_handler = new ViewManipulation($this->docova, $app, $this->global_settings);
								$view_handler->deleteView($view_id);
								$cnt++;
								break;
							case 'AppLayouts':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$layout = $em->getRepository('DocovaBundle:AppLayout')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								$name = str_replace(array('/', '\\'), '-', $layout->getLayoutId());
								$name = str_replace(' ', '', $name);
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$name.'.html.twig');
								$em->remove($layout);
								$em->flush();
								$cnt++;
								break;
							case 'AppPages':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$page = $em->getRepository('DocovaBundle:AppPages')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								$name = str_replace(array('/', '\\'), '-', $page->getPageName());
								$name = str_replace(' ', '', $name);
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$name.'.html.twig');
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$name.'_m.html.twig');						
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'PAGE'.DIRECTORY_SEPARATOR.$name.'.html.twig');
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'PAGE'.DIRECTORY_SEPARATOR.$name.'_m.html.twig');
								$em->remove($page);
								$em->flush();
								$cnt++;
								break;
							case 'AppSubforms':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								if (!empty($subform))
								{
									$sub_name = $subform->getFormFileName() ? $subform->getFormFileName() : $subform->getFormName();
									$sub_name = str_replace(array('/', '\\'), '-', $sub_name);
									$sub_name = str_replace(' ', '', $sub_name);
									$fields = $subform->getSubformFields();
									if (!empty($fields))
									{
										foreach ($fields as $f) {
											$em->getRepository('DocovaBundle:FormDateTimeValues')->deleteFieldRecords($f->getId());
											$em->getRepository('DocovaBundle:FormNumericValues')->deleteFieldRecords($f->getId());
											$em->getRepository('DocovaBundle:FormNameValues')->deleteFieldRecords($f->getId());
											$em->getRepository('DocovaBundle:FormGroupValues')->deleteFieldRecords($f->getId());
											$em->getRepository('DocovaBundle:FormTextValues')->deleteFieldRecords($f->getId());
											$em->getRepository('DocovaBundle:AttachmentsDetails')->deleteFieldRecords($f->getId());
											$em->remove($f);
										}
									}
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$sub_name.'_read.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$sub_name.'_m_read.html.twig');									
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$sub_name.'.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$sub_name.'_m.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$sub_name.'_computed.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$sub_name.'_default.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'SUBFORM'.DIRECTORY_SEPARATOR.$sub_name.'.html.twig');
									@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'SUBFORM'.DIRECTORY_SEPARATOR.$sub_name.'_m.html.twig');
									$em->remove($subform);
									$em->flush();
									$cnt++;
								}
								break;
							case 'AppMenus':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$outline = $em->getRepository('DocovaBundle:AppOutlines')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								$name = str_replace(array('/', '\\'), '-', $outline->getOutlineName());
								$name = str_replace(' ', '', $name);
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'outline'.DIRECTORY_SEPARATOR.$name.'.html.twig');
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'outline'.DIRECTORY_SEPARATOR.$name.'_m.html.twig');
								@unlink($designPath.$app->getId().DIRECTORY_SEPARATOR.'outline'.DIRECTORY_SEPARATOR.$name.'_twig.html.twig');
								@unlink($designPath.'../../public/css/custom/'.$app->getId().DIRECTORY_SEPARATOR.'outlines'.DIRECTORY_SEPARATOR.$name.".css");
								$em->remove($outline);
								$em->flush();
								$cnt++;
								break;
							case 'AppJS':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$js = $em->getRepository('DocovaBundle:AppJavaScripts')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								if (!empty($js))
								{
									$name = str_replace(array('/', '\\'), '-', $js->getJSName());
									$name = str_replace(' ', '', $name);
									@unlink($designPath.'../../public/js/custom/'.$app->getId().DIRECTORY_SEPARATOR.$name.".js");
									$em->remove($js);
									$em->flush();
									$cnt++;
								}
								
								break;
							case 'AppAgents':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$agent = $em->getRepository('DocovaBundle:AppAgents')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								$appdir = 'A'.str_replace('-', '', $app->getId());
								if (!empty($agent))
								{
									$name = str_replace(array('/', '\\', '-'), '', $agent->getAgentName());
									$name = str_replace(' ', '', $name);
									$rempath = $designPath.'../../../Agents/'.$appdir.DIRECTORY_SEPARATOR.$name.".php";
									@unlink($rempath);
									$rempath =$designPath.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'AGENTS'.DIRECTORY_SEPARATOR.$name.'.html.twig';
									@unlink($rempath);
									
									$em->remove($agent);
									$em->flush();
									$cnt++;
								}
								break;
							case 'AppImages':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$image = $em->getRepository('DocovaBundle:AppFiles')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								if (!empty($image))
								{
									@unlink($designPath.'../../public/images/'.$app->getId().DIRECTORY_SEPARATOR.$image->getFileName());
									$em->remove($image);
									$em->flush();
									$cnt++;
								}
								break;
							case 'AppCss':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								break;
							case 'AppScriptLibraries':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$sl = $em->getRepository('DocovaBundle:AppPhpScripts')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								$appdir = 'A'.str_replace('-', '', $app->getId());
								if (!empty($sl))
								{
									$name = str_replace(array('/', '\\'), '-', $sl->getPhpName());
									$name = str_replace(' ', '', $name);
									$rempath = $designPath.'../../../Agents/'.$appdir.DIRECTORY_SEPARATOR."ScriptLibraries".DIRECTORY_SEPARATOR.$name.".php";
									@unlink($rempath);
									$em->remove($sl);
									$em->flush();
									$cnt++;
								}
								break;

							case 'AppWorkflow':
								if(!$isadmin){
									throw new \Exception('Access Denied');
								}
								
								$workflow = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));
								if (!empty($workflow))
								{
									$em->getRepository('DocovaBundle:AppForms')->deleteAppFormWorkflows($workflow->getId());
									$steps = $workflow->getSteps();
									foreach ($steps as $stp)
									{
										$actions = $stp->getActions();
										if (!empty($actions))
										{
											foreach ($actions as $action) {
												$sendto = $action->getSendTo();
												if (!empty($sendto)) {
													foreach ($sendto as $s) {
														$em->remove($s);
													}
												}
												$em->remove($action);
											}
										}
										$participants = $stp->getOtherParticipant();
										if (!empty($participants)) 
										{
											foreach ($participants as $p) {
												$em->remove($p);
											}
										}
										$em->remove($stp);
									}
									$em->remove($workflow);
									$em->flush();
									$cnt++;
								}
								break;
							
							case 'customwidgets':
								if (!$this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
									throw new \Exception('Access Denied');
								}
								
								$widget = $em->getRepository('DocovaBundle:Widgets')->findOneBy(['id' => $item->nodeValue, 'isCustom' => true]);
								if (!empty($widget))
								{
									if ($widget->getPanelWidgets()->count()) {
										foreach ($widget->getPanelWidgets() as $upanel) {
											$em->remove($upanel);
										}
										$em->flush();
									}
									
									@unlink($designPath.'Widgets'.DIRECTORY_SEPARATOR.$widget->getSubformName().'.html.twig');
									@unlink($designPath.'..'.DIRECTORY_SEPARATOR.'Subform'.DIRECTORY_SEPARATOR.$widget->getSubformName().'.html.twig');
									$em->remove($widget);
									$em->flush();
									$cnt++;
								}
								
								break;
								
							default:// treat it as application document
								$view_handler = new ViewManipulation($this->docova, $app, $this->global_settings);
								$view_id = str_replace('-', '', $view->getId());
								if (true === $view_handler->viewExists($view_id))
								{
									
									$can_edit = false;
									$can_delete = false;
									$docova = new Docova($this->container);
									$docova_acl = $docova->DocovaAcl($app);
									
									$can_delete = $docova_acl->canDeleteDocument();
									if (!$can_delete){
										break;
									}else{			
										try {
										    $document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $item->nodeValue, 'application' => $app->getId()));

										    $docform = $document->getAppForm();
										    
										    if ($docova_acl->isEditor())
										        $can_edit = true;
										    else {
										        $can_edit = $docova_acl->isDocAuthor($document);
										    }
										    if ($can_edit === false)
										    {
										        continue;
										    }
											$this->deleteDocumentPermanently($document);
											$cnt++;
										}
										catch (\Exception $e) {
											continue;
										}
										$views = $em->getRepository('DocovaBundle:AppViews')->getAllAppViews($app);
										if (!empty($views))
										{											
											foreach ($views as $view) {
												try {
													$view_id = str_replace('-', '', $view->getId());
//													$view_handler->beginTransaction();
													$view_handler->deleteDocument($view_id, $item->nodeValue, false);
													$view_handler->dispatchChildren($view_id, $item->nodeValue);
//													$view_handler->commitTransaction();
												}
												catch (\Exception $e) {
//													$view_handler->rollbackTransaction();
												}
											}
										}

										//remove from data tables
										if ( !empty ($docform))
										{
											$dtviews =  $em->getRepository('DocovaBundle:AppViews')->getDataTableInfoByForm($app, $docform->getId());

											if ( !empty($dtviews))
											{
												foreach ( $dtviews as $dtview)
												{
													$viewid =  str_replace('-', '', $dtview["id"]);
													try {
														$view_handler->beginTransaction();
														$view_handler->deleteDocument($viewid, $item->nodeValue, false);
														$view_handler->commitTransaction();
													}
													catch (\Exception $e) {
														$view_handler->rollbackTransaction();
													}
												}
											}
										}
									}
								}
								break;
						}
					}
				}
				
				$response_xml = new \DOMDocument("1.0", "UTF-8");
				$root = $response_xml->appendChild($response_xml->createElement('Results'));
				if ($cnt > 0) 
				{
					$child = $response_xml->createElement('Result', 'OK');
					$attrib = $response_xml->createAttribute('ID');
					$attrib->value = 'Status';
					$child->appendChild($attrib);
					$root->appendChild($child);
					$child = $response_xml->createElement('Result', $cnt);
					$attrib = $response_xml->createAttribute('ID');
					$attrib->value = 'Ret1';
					$child->appendChild($attrib);
					$root->appendChild($child);
				}
				else {
					$child = $response_xml->createElement('Result', 'FAILED');
					$attrib = $response_xml->createAttribute('ID');
					$attrib->value = 'Status';
					$child->appendChild($attrib);
					$root->appendChild($child);
					
					$child = $response_xml->createElement('Result', 'Could not remove document(s). Please contact your systems administrator.');
					$attrib = $response_xml->createAttribute('ID');
					$attrib->value = 'ErrMsg';
					$child->appendChild($attrib);
					$root->appendChild($child);
				}
				$response = new Response($response_xml->saveXML());
				$response->headers->set('Content-Type', 'text/xml');

				return $response;
			}
		}
		elseif ($action === 'GETVIEWINFO')
		{
			$viewname = trim($post_xml->getElementsByTagName('ViewName')->item(0)->nodeValue);
			$app = $this->getNodeValue($post_xml, 'AppID');
			$app = $this->docova->DocovaApplication(['appID' => $app]);
			$appId = $app->appID;
			if (empty($appId)) {
				throw $this->createNotFoundException('Unspecified application source ID.');
			}

			$response = new Response();
			$response->headers->set('Content-Type', 'text/xml');


			if ($viewname == 'luDocumentComments') {
				$response_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
				$response_xml .= '<Results><Result ID=\'Status\'>OK</Result><Result ID=\'Ret1\'><ViewInfo><ViewName><![CDATA['.$viewname.']]></ViewName>';
				$response_xml .= '<ViewAlias><![CDATA[]]></ViewAlias>';
				$response_xml .= '<IsFolder>false</IsFolder><IsPrivate>false</IsPrivate>';
				
				$response_xml .= '<ViewReaders><![CDATA[]]></ViewReaders><EntryCount>0</EntryCount></ViewInfo></Result></Results>';
				$response->setContent($response_xml);
				return $response;
			}

			$view = $app->getView($viewname);

			if ( empty($view)){
				throw $this->createNotFoundException('Could not find view'.$viewname);
			}
			

			$response_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
			$response_xml .= '<Results><Result ID=\'Status\'>OK</Result><Result ID=\'Ret1\'><ViewInfo><ViewName><![CDATA['.$view->viewName.']]></ViewName>';
			$response_xml .= '<ViewAlias><![CDATA['.$view->viewAlias.']]></ViewAlias>';
			$response_xml .= '<IsFolder>'.$view->isFolder.'</IsFolder><IsPrivate>'.$view->isPrivate.'</IsPrivate>';
			$response_xml .= '<ColumnCount>'.$view->ColumnCount.'</ColumnCount><IsCategorized>'.$view->isCategorized.'</IsCategorized>';
			$response_xml .= '<ViewReaders><![CDATA[]]></ViewReaders><EntryCount>0</EntryCount></ViewInfo></Result></Results>';
			$response->setContent($response_xml);
			return $response;
		}
		elseif ($action === 'GETDOCUMENTS')
		{
			$view_name = $post_xml->getElementsByTagName('ViewName')->item(0)->nodeValue;
			$appnode = $post_xml->getElementsByTagName('AppID');
			$app = '';
			if($appnode->length > 0){
				$app = $appnode->item(0)->nodeValue;
			}
			if(empty($app)){
				$app = $request->query->get('AppID');
			}
			$response_xml = '<?xml version="1.0" encoding="UTF-8" ?><Results>';
			$em = $this->getDoctrine()->getManager();
			if (!empty($view_name))
			{
				
				if ($view_name == 'luDocumentComments') {
					$document = $post_xml->getElementsByTagName('Key')->item(0)->nodeValue;

					$comments = $em->getRepository('DocovaBundle:AppDocComments')->getDocComments($document);

					//$comments = $em->getRepository('DocovaBundle:AppDocComments')->findBy(array('document' => $document), ['threadIndex' => 'DESC', 'commentIndex' => 'ASC']);
					if (!empty($comments)) 
					{
						$response_xml .= '<Result ID="Status">OK</Result><Result ID="Ret1"><Documents>';
						foreach ($comments as $comment) {
							$response_xml .= "<Document><ID>".$comment['id']."</ID>";
							$response_xml .= "<parentdockey>".$document."</parentdockey>";
							$response_xml .= "<threadIndex>".$comment['threadIndex']."</threadIndex>";
							$response_xml .= "<commentIndex>".$comment['commentIndex']."</commentIndex>";
							$response_xml .= "<avatar>".$comment['avatar']."</avatar>";
							$response_xml .= "<commentor>".$comment['userNameDnAbbreviated']."</commentor>";
							$response_xml .= "<dateCreated>".$comment['dateCreated']->format('m/d/Y H:i:s')."</dateCreated>";
							$response_xml .= "<comment>".$comment['comment']."</comment>";
							 
							$response_xml .= "</Document>";
						}
						$response_xml .= '</Documents></Result>';
					}
				}
				else {
					$docovaapp = $this->docova->DocovaApplication(['appID' => $app]);
					$docovaview = $this->docova->DocovaView($docovaapp, $view_name);
					if (!empty($post_xml->getElementsByTagName('Key')->length)) {
						$key = $post_xml->getElementsByTagName('Key')->item(0)->nodeValue;
						$docovadoc = $docovaview->getDocumentByKey($key, true);
					}
					elseif (!empty($post_xml->getElementsByTagName('DocUnid')->length)) {
						$unid = $post_xml->getElementsByTagName('DocUnid')->item(0)->nodeValue;
						if ($unid == 'LAST') {
							$docovadoc = $docovaview->getLastDocument();
						}
						elseif ($unid == 'FIRST') {
							$docovadoc = $docovaview->getFirstDocument();
						}
						else {
							$skip = !empty($post_xml->getElementsByTagName('Skip')->length) ? $post_xml->getElementsByTagName('Skip')->item(0)->nodeValue : null;
							if ($skip == '-1') {
								$currdoc = $docovaview->getDocument($unid);
								$docovadoc = $docovaview->getPrevDocument($currdoc);
							}
							elseif ($skip == '1') {
								$currdoc = $docovaview->getDocument($unid);
								$docovadoc = $docovaview->getNextDocument($currdoc);
							}
							elseif (!is_null($skip)) {
								$skip = intval($skip);
								$docovadoc = $docovaview->getNthDocument($skip);
							}
							else {
								$docovadoc = $docovaview->getDocument($unid);
							}
						}
					}
					if ( !is_null($docovadoc)){
						$response_xml .= '<Result ID="Status">OK</Result><Result ID="Ret1"><Documents>';
						$response_xml .= "<Document><ID>".$docovadoc->getId()."</ID>";
						$response_xml .= "<Unid>".$docovadoc->getId()."</Unid></Document></Documents></Result>";
					}else{
						$response_xml .= '<Result ID="Status">ERROR</Result>';
					}


				}
			}
			$response_xml .= '</Results>';
			$response = new Response();
			$response->headers->set('Content-Type', 'text/xml');
			$response->setContent($response_xml);
			return $response;
		}
	}

	public function getDocumentLogsAction($document, $filter = "0")
	{
		$xml_log = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_log->appendChild($xml_log->createElement('logentries'));
		$em = $this->getDoctrine()->getManager();
		$logs = $em->getRepository('DocovaBundle:DocumentsLog')->findBy(array('Document' => $document), array('Log_Date' => 'ASC'));
		$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$global_settings = $global_settings[0];
		$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat()); 
	
		if (!empty($logs)) {
	
			for ($x = 0; $x < count($logs); $x++) {

				$details = $logs[$x]->getLogDetails();
				
				if ( $filter === "1")
				{
					if (  $logs[$x]->getLogAction() !== "WORKFLOW" )
						continue;
				}
	
				$child = $root->appendChild($xml_log->createElement('logentry'));
	
				$child->appendChild($xml_log->createElement('logaction', $logs[$x]->getLogAction()));
				$child->appendChild($xml_log->createElement('logactionstatus', ($logs[$x]->getLogStatus() === true) ? 'OK' : 'ERROR'));
				$child->appendChild($xml_log->createElement('logdate', $logs[$x]->getLogDate()->format($defaultDateFormat)));
				$child->appendChild($xml_log->createElement('logtime', $logs[$x]->getLogDate()->format('H:i:s')));
				$author = $child->appendChild($xml_log->createElement('logauthor'));
				$author->appendChild($xml_log->createCDATASection($logs[$x]->getLogAuthor() ? ($global_settings->getUserDisplayDefault() ? $logs[$x]->getLogAuthor()->getUserProfile()->getDisplayName() : $logs[$x]->getLogAuthor()->getUserNameDnAbbreviated()) : 'DailyLifecycleTasks'));
				$cdata = $xml_log->createCDATASection($logs[$x]->getLogDetails());
				$node = $xml_log->createElement('logdetails');
				$node->appendChild($cdata);
				$child->appendChild($node);
				$cdata = $author = $node = null;
			}
		}

		$response = new Response($xml_log->saveXML());
		$response->headers->set('Content-Type', 'text/xml');
	
		return $response;
	}
	
	public function WorkflowServicesAction(Request $request)
	{
		$this->initialize();
		$post_xml = new \DOMDocument('1.0', 'UTF-8');
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$post_xml->loadXML(urldecode($request->getContent()));

		$doc_id = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		if (empty($doc_id))
		{
			$this->createNotFoundException('Document ID is missef.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id, 'Archived' => false));
		if (empty($document))
		{
			$this->createNotFoundException('Unsufficient source Document with ID = '.$doc_id);
		}
		
		$wfProcess = $this->get('docova.workflowprocess');
		$wfProcess->setUser($this->user);
		$wfProcess->setGs($this->global_settings);
		$result_xml = $wfProcess->workflowItemProcess($post_xml, $document, true);
		if (empty($result_xml)) {
			throw $this->createNotFoundException('');
		}
		
		$response->setContent($result_xml);
		
		return $response;
	}
	
	public function workflowCommentAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgWorkflowComment.html.twig', array(
				'user' => $this->user
				));
	}
	
	public function workflowSelectStepAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgWorkflowSelectStep.html.twig', array(
				'user' => $this->user
				));
	}
	
	public function showWfSelectProcessAction()
	{
		return $this->render('DocovaBundle:Default:dlgWorkflowSelectProcess.html.twig');
	}
	
	public function popupReleaseDialogAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgWorkflowDocRelease.html.twig', array(
				'user' => $this->user
			));
	}
	
	public function popupRetractDialogAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgRetractRelease.html.twig', array(
				'user' => $this->user
			));
	}
	
	public function popupWFRequestInfoDialogAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $request->query->get('ParentUNID'), 'Trash' => false, 'Archived' => false));
		if (!empty($document)) 
		{
			return $this->render('DocovaBundle:Default:dlgWorkflowInfoRequest.html.twig', array(
				'user' => $this->user,
				'document' => $document
			));
		}
		else {
			$response = new Response('<b style="color:red">Unspecified document source ID = '.$request->query->get('ParentUNID').'</b>');
			return $response;
		}
	}
	
	public function getDocumentCommentsAction(Request $request)
	{
		$this->initialize();
		$response = new Response();
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_result->appendChild($xml_result->createElement('logentries'));
		$response->headers->set('Content-Type', 'text/xml');
		
		$params = preg_split('/DK/', $request->query->get('RestrictToCategory'), null, PREG_SPLIT_NO_EMPTY);
		if (count($params) !==  2) {
			throw $this->createNotFoundException('Document ID or Library ID is missed.');
		}
		$em = $this->getDoctrine()->getManager();
		$comments = $em->getRepository('DocovaBundle:DocumentComments')->getDocumentComments($params[1]);
		$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
		if (!empty($comments)) {
			foreach ($comments as $comment) {

				$child = $root->appendChild($xml_result->createElement('logentry'));
				
				$child->appendChild($xml_result->createElement('logaction', 'RELEASE'));
				$child->appendChild($xml_result->createElement('logactionstatus', 'OK'));
				$child->appendChild($xml_result->createElement('logdate', $comment->getDateCreated()->format($defaultDateFormat)));
				$child->appendChild($xml_result->createElement('logtime', $comment->getDateCreated()->format('H:i:s')));
				$author = $child->appendChild($xml_result->createElement('logauthor'));
				$author->appendChild($xml_result->createCDATASection($this->global_settings->getUserDisplayDefault() ? $comment->getCreatedBy()->getUserProfile()->getDisplayName() : $comment->getCreatedBy()->getUserNameDnAbbreviated()));
				$detail = $child->appendChild($xml_result->createElement('logdetails'));
				$detail->appendChild($xml_result->createCDATASection($comment->getComment()));
			}
		}
		
		$response->setContent($xml_result->saveXML());
		
		return $response;
	}
	
	public function advancedCommentDialogAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgAdvComment.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function opendlgRelatedDocSelectAction()
	{
		$this->initialize();
		
		return $this->render('DocovaBundle:Default:dlgRelatedDocSelect.html.twig', array(
					'user' => $this->user
				));
	}
	
	public function showArchivedDocumentsAction(Request $request)
	{
		$this->initialize();
		$folder_id = $request->query->get('parentUNID');
		if (empty($folder_id)) 
		{
			throw $this->createNotFoundException('Folder ID is missed');
		}
		
		$em = $this->getDoctrine()->getManager();
		$folder_obj = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id, 'Del' => false, 'Inactive' => 0));
		if (empty($folder_obj)) 
		{
			throw $this->createNotFoundException('Unspecified source for Folder ID = '.$folder_id);
		}
		
		return $this->render('DocovaBundle:Default:dlgFolderArchive.html.twig', array(
			'user' => $this->user,
			'folder' => $folder_obj
		));
	}
	
	public function getFolderArchivedDocumentAction(Request $request)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $result_xml->appendChild($result_xml->createElement('documents'));
		$folder_id = $request->query->get('RestrictToCategory');

		try {
			$em = $this->getDoctrine()->getManager();
			$folder_obj = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id, 'Del' => false, 'Inactive' => 0));
			if (empty($folder_obj)) 
			{
				throw new \Exception('Unspecified source for folder ID = '.$folder_id);
			}
			
			$documents = $em->getRepository('DocovaBundle:Documents')->findBy(array('Archived' => true, 'folder' => $folder_obj->getId()), array('Doc_Title' => 'ASC', 'Doc_Version' => 'ASC', 'Revision' => 'ASC'));
			if (empty($documents) && count($documents) == 0) {
				throw new \Exception('No documents found');
			}
			$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
			$global_settings = $global_settings[0];
			$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
			unset($global_settings);
			foreach ($documents as $document)
			{
				$doc_node = $result_xml->createElement('document');
				$newnode = $result_xml->createElement('dockey', $document->getId());
				$doc_node->appendChild($newnode);
				$newnode = $result_xml->createElement('docid', $document->getId());
				$doc_node->appendChild($newnode);
				$newnode = $result_xml->createElement('Selected');
				$doc_node->appendChild($newnode);
				$newnode = $result_xml->createElement('CreatedBy');
				$cdata = $result_xml->createCDATASection($document->getCreator()->getUserNameDnAbbreviated());
				$newnode->appendChild($cdata);
				$doc_node->appendChild($newnode);
				$newnode = $result_xml->createElement('CreatedDate', $document->getDateCreated()->format($defaultDateFormat));
				$doc_node->appendChild($newnode);
				$newnode = $result_xml->createElement('ArchivedDate', $document->getDateArchived()->format($defaultDateFormat));
				$doc_node->appendChild($newnode);
				$newnode = $result_xml->createElement('Subject');
				$cdata = $result_xml->createCDATASection($document->getDocTitle());
				$newnode->appendChild($cdata);
				$doc_node->appendChild($newnode);
				$newnode = $result_xml->createElement('DocumentTypeKey', $document->getDocType()->getId());
				$doc_node->appendChild($newnode);
				$newnode = $result_xml->createElement('DocumentType');
				$cdata = $result_xml->createCDATASection($document->getDocType()->getDocName());
				$newnode->appendChild($cdata);
				$doc_node->appendChild($newnode);
				$docversion = $document->getDocVersion() ? $document->getDocVersion() : '0.0';
				$docversion .= $document->getRevision() ? '.'.$document->getRevision() : '.0';
				$newnode = $result_xml->createElement('FullVersion', $docversion);
				$doc_node->appendChild($newnode);
				$root->appendChild($doc_node);
			}
		}
		catch (\Exception $e) {
			$result_xml = new \DOMDocument('1.0', 'UTF-8');
			$root = $result_xml->appendChild($result_xml->createElement('documents'));
			$newnode = $result_xml->createElement('h2', 'No documents found');
			$root->appendChild($newnode);
		
			$response->setContent($result_xml->saveXML());
			return $response;
		}
		
		$response->setContent($result_xml->saveXML());
		return $response;
	}
	
	public function getFolderDocumentsAction(Request $request)
	{
		$this->initialize();
		$folder_id = $request->query->get('folderid');
		if (empty($folder_id)) 
		{
			throw $this->createNotFoundException('Folder ID is missed.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$folder_obj = $em->find('DocovaBundle:Folders', $folder_id);
		if (empty($folder_obj))
		{
			throw $this->createNotFoundException('Unspecified source folder with ID = '. $folder_id);
		}
		return $this->render('DocovaBundle:Default:wFolderDocuments.html.twig', array(
				'user' => $this->user,
				'folder' =>  $folder_obj,
				'related_doc_xml' => $this->getFolderDocumentsSummaryXMLAction($request, "text")
			));
	}
		
	public function getFolderDocumentsSummaryXMLAction(Request $request, $returntype)
	{
	    $this->initialize();
	    $folder_id = $request->query->get('folderid');
	    if (empty($folder_id))
	    {
	        throw $this->createNotFoundException('Folder ID is missed.');
	    }
	    $em = $this->getDoctrine()->getManager();
	    $folder_obj = $em->find('DocovaBundle:Folders', $folder_id);
	    if (empty($folder_obj))
	    {
	        throw $this->createNotFoundException('Unspecified source folder with ID = '. $folder_id);
	    }
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_result->appendChild($xml_result->createElement('Db'));
		$documents = $em->getRepository('DocovaBundle:Documents')->getFolderDocuments($folder_id);
		$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
		if (!empty($documents))
		{
			$security_check = new Miscellaneous($this->container);
			$customACL = new CustomACL($this->container);
			foreach ($documents as $document)
			{
				if (true === $security_check->canReadDocument($document, true)) 
				{
					$manager = $customACL->getObjectACEUsers($folder_obj, 'owner');
					$inherited_managers = $customACL->getObjectACEUsers($folder_obj, 'master');
					if ($inherited_managers->count() > 0)
					{
						foreach ($inherited_managers as $im)
						{
							$manager->add($im);
						}
					}
					unset($inherited_managers, $im);
					$authors = '';
					
					foreach ($manager as $m)
					{
						if (false !== $muser = $this->findUserAndCreate($m->getUsername(), false, true)) {
							$authors .= $muser->getUserNameDnAbbreviated().';';
						}
					}
					$authors .= $document->getAuthor()->getUserNameDnAbbreviated();
					unset($manager, $m);
					
					$attachments = $em->getRepository('DocovaBundle:AttachmentsDetails')->findBy(array('Document' => $document->getId()));
					$file_names = $attach_date = $attach_size = '';
					if (!empty($attachments) && count($attachments) > 0)
					{
						foreach ($attachments as $attach)
						{
							$file_names 	.= $attach->getFileName().';';
							$attach_date	.= $attach->getFileDate()->format($defaultDateFormat).';';
							$attach_size	.= $attach->getFileSize().';';
						}
						$file_names	= substr_replace($file_names, '', -1);
						$attach_date= substr_replace($attach_date, '', -1);
						$attach_size= substr_replace($attach_size, '', -1);
					}
					unset($attachments, $attach);
					
					$summary = " <div style='margin-bottom:3px;padding-left:20px'><b>".$document->getDocTitle()."</b><div style='padding-left:20px;'>Author: ".$document->getAuthor()->getUserNameDnAbbreviated()
							.'. Created: '.$document->getDateCreated()->format($defaultDateFormat)
							.'. Modified: '.(($document->getDateModified()) ? $document->getDateModified()->format($defaultDateFormat) : '')
							.'<br />Version: '.$document->getDocVersion().',Status: '.$document->getDocStatus().'</div>';
					if (!empty($file_names)) {
	                   	$summary .= "<div style='width:20px; height:15px;float:left;line-height:15px;margin-top:4px;'><i class='fa fa-file-o'></i></div><div style='margin-top:4px;float:left;'>$file_names</div>";
					}
					
					$docs = $root->appendChild($xml_result->createElement('Doc'));
					$docs->appendChild($xml_result->createElement('selected'));
					$CData = $xml_result->createCDATASection($authors);
					$child = $xml_result->createElement('docauthors');
					$child->appendChild($CData);
					$docs->appendChild($child);
					$docs->appendChild($xml_result->createElement('unid', $document->getId()));
					$docs->appendChild($xml_result->createElement('dockey', $document->getId()));
					$CData = $xml_result->createCDATASection(htmlspecialchars($document->getDocTitle()));
					$child = $xml_result->createElement('subject');
					$child->appendChild($CData);
					$docs->appendChild($child);
					$CData = $xml_result->createCDATASection($file_names);
					$child = $xml_result->createElement('name');
					$child->appendChild($CData);
					$docs->appendChild($child);
					$docs->appendChild($xml_result->createElement('date', $attach_date));
					$docs->appendChild($xml_result->createElement('size', $attach_size));
					$docs->appendChild($xml_result->createElement('aco', ($document->getDocStatus() === 'Rleased' || $document->getDocSteps()->count() > 0) ? '0' : '1'));
					$docs->appendChild($xml_result->createElement('DocumentTypeKey', $document->getDocType()->getId()));
					$CData = $xml_result->createCDATASection($summary);
					$child = $xml_result->createElement('Summary');
					$child->appendChild($CData);
					$docs->appendChild($child);
				}
			}
		}
		
	    if(isset($returntype) && $returntype == "text"){
	        return $xml_result->saveXML();
	    }else if(isset($returntype) && $returntype == "xml"){
	        return $xml_result;
	    }else{
	        $response = new Response();
	        $response->headers->set('Content-Type', 'text/xml');
	        $response->setContent($xml_result->saveXML());
	        return $response;
	    }
	}
	
	public function showViewColumnPropertiesAction(Request $request)
	{
		$this->initialize();
		$folder_id = $request->query->get('FolderID');
		$library_id = $request->query->get('LibraryID');
		if (empty($folder_id) || empty($library_id))
		{
			throw $this->createNotFoundException('Folder ID or Libarary ID is missed.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$folder_obj = $em->find('DocovaBundle:Folders', $folder_id);
		if (empty($folder_obj) && $folder_id != -1 && substr($folder_id, 0, 5) != "RCBIN")
		{
			throw $this->createNotFoundException('Could not find Folder with ID '.$folder_id);
		}
		
		$columns = $em->getRepository('DocovaBundle:ViewColumns')->getValidColumns($library_id, array(), true);
		if (empty($columns)) 
		{
			throw $this->createNotFoundException('No column could be found, contact administrator.');
		}
				
		return $this->render('DocovaBundle:Default:dlgViewColumnProperties.html.twig', array(
				'user' => $this->user,
				'viewcolumns' => $columns
			));
	}
	
	public function savePerspectiveAction(Request $request)
	{
		$folder_id = $request->query->get('ParentUNID');
		if (empty($folder_id))
		{
			throw $this->createNotFoundException('Parent folder ID is missed.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$folder_obj = $em->find('DocovaBundle:Folders', $folder_id);
		if (empty($folder_obj))
		{
			throw $this->createNotFoundException('Unspecified source folder ID = '.$folder_id);
		}
		
		$this->initialize();
		$column_fields = $em->getRepository('DocovaBundle:ViewColumns')->findBy(array('Column_Status' => true), array('Title' => 'ASC'));
		$facet_maps = $em->getRepository('DocovaBundle:FacetMaps')->findAllValidFacetMaps($folder_obj->getLibrary()->getId());
		
		return $this->render('DocovaBundle:Default:dlgSavePerspective.html.twig', array(
				'user' => $this->user,
				'folder' => $folder_obj,
				'facet_maps' => $facet_maps,
				'column_fields' => $column_fields
			));
	}
	
	public function getFacetMapsByIDAction(Request $request)
	{
		$facet_id = $request->query->get('fid');
		if (empty($facet_id))
		{
			throw $this->createNotFoundException('Facet ID is missed');
		}
		
		$facet_map = $this->getDoctrine()->getManager()->find('DocovaBundle:FacetMaps', $facet_id);
		if (empty($facet_map))
		{
			throw $this->createNotFoundException('Unspecified source Facet Map ID = '.$facet_id);
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$xml_response = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_response->appendChild($xml_response->createElement('FACETMAP'));
		$root->appendChild($xml_response->createElement('ID', $facet_map->getId()));
		$root->appendChild($xml_response->createElement('Name', $facet_map->getFacetMapName()));
		$root->appendChild($xml_response->createElement('Description', $facet_map->getDescription()));
		$root->appendChild($xml_response->createElement('FacetFields', $facet_map->getFacetMapFields()));
		
		$response->setContent($xml_response->saveXML());
		return $response;
	}
	
	public function getFacetMapByPerspectiveAction(Request $request)
	{
		$perspective = $request->query->get('pid');
		if (empty($perspective))
		{
			throw $this->createNotFoundException('Perspective ID is missed');
		}
		
		$perspective = str_replace(array('custom_', 'system_'), '', $perspective);
		$perspective = $this->getDoctrine()->getManager()->find('DocovaBundle:SystemPerspectives', $perspective);
		if (empty($perspective)) {
			throw $this->createNotFoundException('Unspecified perspective source.');
		}
		
		$facet_map = $perspective->getFacetMap();
		unset($perspective);
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$xml_response = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_response->appendChild($xml_response->createElement('FACETMAP'));
		$root->appendChild($xml_response->createElement('ID', $facet_map->getId()));
		$root->appendChild($xml_response->createElement('Name', $facet_map->getFacetMapName()));
		$root->appendChild($xml_response->createElement('Description', $facet_map->getDescription()));
		$root->appendChild($xml_response->createElement('FacetFields', $facet_map->getFacetMapFields()));
		
		$response->setContent($xml_response->saveXML());
		return $response;
	}
	
	public function getPerspectivesXMLAction(Request $request)
	{
		$p_id = $request->query->get('pid');
		if (empty($p_id))
		{
			throw $this->createNotFoundException('Perspective ID is missed.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$perspective = $em->find('DocovaBundle:SystemPerspectives', $p_id);
		if (empty($perspective))
		{
			throw $this->createNotFoundException('Unspecified source perspective ID = '.$p_id);
		}
		
		$response = new Response();
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$response->headers->set('Content-Type', 'text/xml');
		$root = $xml_result->appendChild($xml_result->createElement('viewperspective'));
		$root->appendChild($xml_result->createElement('type', (($perspective->getIsSystem() === true) ? 'system' : 'custom')));
		$root->appendChild($xml_result->createElement('id', (($perspective->getIsSystem() === true) ? 'system_' : 'custom_').$perspective->getId()));
		$root->appendChild($xml_result->createElement('Unid', $perspective->getId()));
		$cdata = $xml_result->createCDATASection($perspective->getPerspectiveName());
		$newnode = $xml_result->createElement('name');
		$newnode->appendChild($cdata);
		$root->appendChild($newnode);
		$cdata = $xml_result->createCDATASection($perspective->getDescription());
		$newnode = $xml_result->createElement('description');
		$newnode->appendChild($cdata);
		$root->appendChild($newnode);
		$cdata = $xml_result->createCDATASection($perspective->getCreator()->getUserNameDnAbbreviated());
		$newnode = $xml_result->createElement('createdby');
		$newnode->appendChild($cdata);
		$root->appendChild($newnode);
		$root->appendChild($xml_result->createElement('createddate', $perspective->getDateCreated()->format('m/d/Y H:i:s')));
		$newnode = ($perspective->getModifier()) ? $perspective->getModifier()->getUserNameDnAbbreviated() : '';
		$cdata = $xml_result->createCDATASection($newnode);
		$newnode = $xml_result->createElement('modifiedby');
		$newnode->appendChild($cdata);
		$root->appendChild($newnode);
		$root->appendChild($xml_result->createElement('modifieddate', (($perspective->getDateModified()) ? $perspective->getDateModified()->format('m/d/Y H:i:s') : '')));
		if ($perspective->getIsSystem() !== true)
		{
			$root->appendChild($xml_result->createElement('autocollapse', (($perspective->getCollapseFirst() === true) ? '1' : '0')));
			$root->appendChild($xml_result->createElement('libscope', (($perspective->getAvailableForFolders() === true) ? 'L' : '')));
			if ($perspective->getVisibility() != 'Global Search') {
				if ($perspective->getLibrary()) {
					$lib_perspective = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('Default_Perspective' => $perspective->getLibrary()->getId()));
					$root->appendChild($xml_result->createElement('libdefault', ((!empty($lib_perspective)) ? 'D' : '')));
				}
				else {
					$root->appendChild($xml_result->createElement('libdefault', ''));
				}
			}
		}
		
		$newnode = $xml_result->createDocumentFragment();
		if ($newnode->appendXML($perspective->getXmlStructure())) 
		{
			$root->appendChild($newnode);
		}
		
		$response->setContent($xml_result->saveXML());
		return $response;
	}
	
	public function deletePerspectiveAction(Request $request)
	{
		$folder_id = $request->query->get('ParentUNID');
		if (empty($folder_id))
		{
			throw $this->createNotFoundException('Folder ID is missed.');
		}
		
		$folder_obj = $this->getDoctrine()->getManager()->find('DocovaBundle:Folders', $folder_id);
		if (empty($folder_obj))
		{
			throw $this->createNotFoundException('Unspecified source folder ID = '. $folder_id);
		}
		$this->initialize();
		
		return $this->render('DocovaBundle:Default:dlgDeletePerspective.html.twig', array(
					'folder' => $folder_obj,
					'user' => $this->user
				));
	}
	
	public function getUserDataServicesAction(Request $request)
	{
		$post_req = $request->getContent();
		if (empty($post_req))
		{
			throw $this->createNotFoundException('No data is submitted.');
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$this->initialize();
		$post_xml = new \DOMDocument('1.0', 'UTF-8');
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$post_xml->loadXML($post_req);
		$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
		
		if ($action === 'NEW')
		{
			if (empty($post_xml->getElementsByTagName('Unid')->item(0)->nodeValue))
			{
				$this->createNotFoundException('Document ID is missed.');
			}

			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue, 'Trash' => false, 'Archived' => false));
			if (empty($document))
			{
				$this->createNotFoundException('Unspecified document source');
			}
			
			$watchlist = new DocumentsWatchlist();
			$watchlist->setWatchlistType(1);
			$watchlist->setWatchlistName('Favorites');
			$watchlist->setDescription('Favorites for '.$this->user->getUserNameDnAbbreviated());
			$watchlist->setAvailability(0);
			$watchlist->setOwner($this->user);
			$watchlist->setDocument($document);
			
			$em->persist($watchlist);
			$em->flush();
			
			$root = $xml_result->appendChild($xml_result->createElement('Results'));
			$attr = $xml_result->createAttribute('ID');
			$attr->value = 'Status';
			$node = $xml_result->createElement('Result', 'OK');
			$node->appendChild($attr);
			$root->appendChild($node);
		}
		elseif ($action === 'ADDFAVORITES')
		{
			if (empty($post_xml->getElementsByTagName('FolderUNID')->item(0)->nodeValue))
			{
				$this->createNotFoundException('Folder ID is missed.');
			}
			
			$em = $this->getDoctrine()->getManager();
			$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $post_xml->getElementsByTagName('FolderUNID')->item(0)->nodeValue, 'Del' => false, 'Inactive' => 0));
			if (empty($folder))
			{
				$this->createNotFoundException('Unspecified folder source');
			}
				
			$watchlist = new FoldersWatchlist();
			$watchlist->setWatchlistType(1);
			$watchlist->setWatchlistName('Favorites');
			$watchlist->setDescription('Favorites for '.$this->user->getUserNameDnAbbreviated());
			$watchlist->setAvailability(0);
			$watchlist->setOwner($this->user);
			$watchlist->setFolders($folder);
				
			$em->persist($watchlist);
			$em->flush();
				
			$root = $xml_result->appendChild($xml_result->createElement('Results'));
			$attr = $xml_result->createAttribute('ID');
			$attr->value = 'Status';
			$node = $xml_result->createElement('Result', 'OK');
			$node->appendChild($attr);
			$root->appendChild($node);
		}
		elseif ($action === 'DELETEFAVORITES') {

			$em = $this->getDoctrine()->getManager();
			try {
				foreach ($post_xml->getElementsByTagName('Unid') as $item)
				{
					$doc_id = $item->nodeValue;
					$favorite_doc = $em->getRepository('DocovaBundle:DocumentsWatchlist')->findOneBy(array('Document' => $doc_id, 'Watchlist_Type' => 1, 'Owner' => $this->user->getId()));
					if (!empty($favorite_doc)) 
					{
						$em->remove($favorite_doc);
						$em->flush();
					}
					else {
						$favorite_folder = $em->getRepository('DocovaBundle:FoldersWatchlist')->findOneBy(array('Folders' => $doc_id, 'Watchlist_Type' => 1, 'Owner' => $this->user->getId()));
						if (!empty($favorite_folder)) 
						{
							$em->remove($favorite_folder);
							$em->flush();
						}
						else {
							throw $this->createNotFoundException('Unspecified favorited Folder/Document ID.');
						}
					}
				}
				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'OK');
				$node->appendChild($attr);
				$root->appendChild($node);
			}
			catch (\Exception $e) {
				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'FAILED');
				$node->appendChild($attr);
				$root->appendChild($node);
			}
		}
		elseif ($action === 'SIMPLESHAREXML') 
		{
			$synchronization = new Synchronization($this->container, $this->user);
			$results = $synchronization->getSyncedFolders();
			if (!empty($results)) 
			{
				$xml_result = $synchronization->produceSyncXML($results);
			}
			else {
				$xml_result->appendChild($xml_result->createElement('Results'));
			}
		}
		elseif ($action === 'ADDSAVEDSEARCH')
		{
			$em = $this->getDoctrine()->getManager();
			try {
				if (empty($post_xml->getElementsByTagName('SearchName')->item(0)->nodeValue) || empty($post_xml->getElementsByTagName('QueryFields')->item(0)->nodeValue)) 
				{
					throw new \Exception('Search Name and/or Query Field are missed.');
				}
				$savedSearch = new SavedSearches();
				$savedSearch->setGlobalSearch($post_xml->getElementsByTagName('LibraryKey')->item(0)->nodeValue == 'Global' ? true : false);
				$savedSearch->setSearchCriteria(trim($post_xml->getElementsByTagName('QueryFields')->item(0)->nodeValue));
				$savedSearch->setSearchQuery(trim($post_xml->getElementsByTagName('SearchQuery')->item(0)->nodeValue) ? trim($post_xml->getElementsByTagName('SearchQuery')->item(0)->nodeValue) : null);
				$savedSearch->setSearchName($post_xml->getElementsByTagName('SearchName')->item(0)->nodeValue);
				$savedSearch->setUserSaved($this->user);
				if ($post_xml->getElementsByTagName('LibraryKey')->item(0)->nodeValue == 'Global') 
				{
					if ($post_xml->getElementsByTagName('LibraryUnidList')->item(0)->nodeValue == 'DOMAINSEARCH') {
						$savedSearch->setIncludeUnsbscribed(true);
					}
					else {
						$libraries = explode(',', $post_xml->getElementsByTagName('LibraryUnidList')->item(0)->nodeValue);
						foreach ($libraries as $library)
						{
							$library = $em->getReference('DocovaBundle:Libraries', $library);
							$savedSearch->addLibrarie($library);
						}
					}
				}
				else {
					$library = $em->getReference('DocovaBundle:Libraries', $post_xml->getElementsByTagName('LibraryKey')->item(0)->nodeValue);
					$savedSearch->addLibrarie($library);
				}
				
				$em->persist($savedSearch);
				$em->flush();
				
				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'OK');
				$node->appendChild($attr);
				$root->appendChild($node);
			}
			catch (\Exception $e) {
				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'FAILED');
				$node->appendChild($attr);
				$root->appendChild($node);
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'ErrMsg';
				$node = $xml_result->createElement('Result', 'Operation could not be completed due to this system error: '. $e->getMessage());
				$node->appendChild($attr);
				$root->appendChild($node);
			}
		}
		elseif ($action === 'GETSAVEDSEARCH')
		{
			$root = $xml_result->appendChild($xml_result->createElement('Results'));
			$savedSearch = $post_xml->getElementsByTagName('SearchKey')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$savedSearch = $em->find('DocovaBundle:SavedSearches', $savedSearch);
			if (!empty($savedSearch)) 
			{
				$libraries = '';
				if ($savedSearch->getIncludeUnsbscribed() === true) {
					$libraries = 'DOMAINSEARCH';
				}
				else {
					if ($savedSearch->getGlobalSearch() && $savedSearch->getLibraries()->count() > 0) 
					{
						foreach ($savedSearch->getLibraries() as $library) {
							$libraries .= $library->getId(). ',';
						}
						$libraries = substr_replace($libraries, '', -1);
					}
				}
				$node = $xml_result->createElement('Result', $savedSearch->getSearchQuery().';'.$savedSearch->getSearchCriteria().";$libraries");
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node->appendChild($attr);
				$root->appendChild($node);
			}
		}
		elseif ($action === 'UPDATESAVEDSEARCH')
		{
			$em = $this->getDoctrine()->getManager();
			$search_id = trim($post_xml->getElementsByTagName('SearchKey')->item(0)->nodeValue);
			try {
				if (empty($search_id)) 
				{
					throw new \Exception('Search key is missed.');
				}
				$this->initialize();
				$savedSearch = $em->getRepository('DocovaBundle:SavedSearches')->findOneBy(array('id' => $search_id, 'userSaved' => $this->user->getId()));
				if (!empty($savedSearch)) 
				{
					$savedSearch->setSearchCriteria(trim($post_xml->getElementsByTagName('QueryFields')->item(0)->nodeValue));
					$savedSearch->setSearchQuery(trim($post_xml->getElementsByTagName('SearchQuery')->item(0)->nodeValue) ? trim($post_xml->getElementsByTagName('SearchQuery')->item(0)->nodeValue) : null);
					$savedSearch->setSearchName($post_xml->getElementsByTagName('SearchName')->item(0)->nodeValue);
					$savedSearch->setIncludeUnsbscribed(false);
					if ($post_xml->getElementsByTagName('LibraryKey')->item(0)->nodeValue == 'Global') 
					{
						foreach ($savedSearch->getLibraries() as $library) {
							$savedSearch->removeLibrarie($library);
						}
						if (!empty($post_xml->getElementsByTagName('LibraryUnidList')->item(0)->nodeValue))
						{
							if ($post_xml->getElementsByTagName('LibraryUnidList')->item(0)->nodeValue == 'DOMAINSEARCH') {
								$savedSearch->setIncludeUnsbscribed(true);
							}
							else {
								$libraries = explode(',', $post_xml->getElementsByTagName('LibraryUnidList')->item(0)->nodeValue);
								foreach ($libraries as $library)
								{
									$library = $em->getReference('DocovaBundle:Libraries', $library);
									$savedSearch->addLibrarie($library);
								}
							}
						}
					}
					$em->flush();

					$root = $xml_result->appendChild($xml_result->createElement('Results'));
					$attr = $xml_result->createAttribute('ID');
					$attr->value = 'Status';
					$node = $xml_result->createElement('Result', 'OK');
					$node->appendChild($attr);
					$root->appendChild($node);
				}
				else {
					throw new \Exception('Unspecified source search ID = '. $search_id);
				}
			}
			catch (\Exception $e)
			{
				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'FAILED');
				$node->appendChild($attr);
				$root->appendChild($node);
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'ErrMsg';
				$node = $xml_result->createElement('Result', 'Operation could not be completed due to this system error: '. $e->getMessage());
				$node->appendChild($attr);
				$root->appendChild($node);
			}
		}
		elseif ($action === 'DELETESAVEDSEARCH')
		{
			$em = $this->getDoctrine()->getManager();
			$search_id = trim($post_xml->getElementsByTagName('SearchKey')->item(0)->nodeValue);
			try {
				if (empty($search_id))
				{
					throw new \Exception('Search key is missed.');
				}
				$this->initialize();
				$savedSearch = $em->getRepository('DocovaBundle:SavedSearches')->findOneBy(array('id' => $search_id, 'userSaved' => $this->user->getId()));
				if (!empty($savedSearch)) 
				{
					foreach ($savedSearch->getLibraries() as $library) {
						$savedSearch->removeLibrarie($library);
					}
					$em->remove($savedSearch);
					$em->flush();

					$root = $xml_result->appendChild($xml_result->createElement('Results'));
					$attr = $xml_result->createAttribute('ID');
					$attr->value = 'Status';
					$node = $xml_result->createElement('Result', 'OK');
					$node->appendChild($attr);
					$root->appendChild($node);
				}
				else {
					throw new \Exception('Unspecified source search ID = '. $search_id);
				}
				
			} catch (\Exception $e) {
				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'FAILED');
				$node->appendChild($attr);
				$root->appendChild($node);
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'ErrMsg';
				$node = $xml_result->createElement('Result', 'Operation could not be completed due to this system error: '. $e->getMessage());
				$node->appendChild($attr);
				$root->appendChild($node);
			}
		}
		elseif ($action === 'ADDDELEGATE' || $action === 'UPDATEDELEGATE')
		{
			$delegateid = $action === 'UPDATEDELEGATE' && !empty($post_xml->getElementsByTagName('DocKey')->item(0)->nodeValue) ? $post_xml->getElementsByTagName('DocKey')->item(0)->nodeValue : null;
			$lib_option = $post_xml->getElementsByTagName('LibraryOption')->item(0)->nodeValue;
			$wf_option = $post_xml->getElementsByTagName('WorkflowOption')->item(0)->nodeValue;
			$start_date = $post_xml->getElementsByTagName('StartDate')->item(0)->nodeValue;
			$end_date = $post_xml->getElementsByTagName('EndDate')->item(0)->nodeValue;
			$delegate = $post_xml->getElementsByTagName('Delegate')->item(0)->nodeValue;
			$owner = $post_xml->getElementsByTagName('DelegateOwner')->item(0)->nodeValue;
			$notification = $post_xml->getElementsByTagName('Notifications')->item(0)->nodeValue;
			$review_policies = $post_xml->getElementsByTagName('ReviewPolicies')->item(0)->nodeValue;
			if ($lib_option == 'S')
			{
				$lib_unids = $post_xml->getElementsByTagName('Library')->item(0)->nodeValue;
				$lib_unids = explode(',', $lib_unids);
			}
			if ($wf_option == 'S')
			{
				$workflow_unids = $post_xml->getElementsByTagName('Workflow')->item(0)->nodeValue;
			}
			
			try {
				if (empty($start_date) || empty($end_date)) {
					throw new \Exception('Cannot set empty Start Date and/or End Date!');
				}
				$delegate = $this->findUserAndCreate($delegate);
				if (empty($delegate)) {
					throw new \Exception('Delegated user cannot be found!');
				}
				
				$owner = $this->findUserAndCreate($owner);
				if (empty($owner)) {
					throw new \Exception('Delegate Owner cannot be found!');
				}
				
				$em = $this->getDoctrine()->getManager();
				$start_date = new \DateTime($start_date);
				$end_date = new \DateTime($end_date);
				
				if ($action === 'UPDATEDELEGATE') {
					$user_delegate = $em->getRepository('DocovaBundle:UserDelegates')->find($delegateid);
					if (empty($user_delegate)) {
						throw new \Exception('Delegate entry cannot be found.');
					}
					
					if ($user_delegate->getApplicableLibraries()->count()) {
						foreach ($user_delegate->getApplicableLibraries() as $library) {
							$user_delegate->removeApplicableLibrary($library);
						}
						$em->flush();
					}
				}
				else {
					$user_delegate = new UserDelegates();
				}
				$user_delegate->setDelegatedUser($delegate);
				$user_delegate->setStartDate($start_date);
				$user_delegate->setEndDate($end_date);
				$user_delegate->setNotifications(trim($notification) == 1 ? true : false);
				$user_delegate->setReviewPolicies($review_policies == 1 ? true : false);
				$user_delegate->setOwner($owner);
				$user_delegate->setApplicableWorkflows(!empty($workflow_unids) ? $workflow_unids : null);
				if (!empty($lib_unids))
				{
					foreach ($lib_unids as $lib) {
						$lib = $em->getReference('DocovaBundle:Libraries', $lib);
						$user_delegate->addApplicableLibraries($lib);
					}
				}
				
				if ($action === 'ADDDELEGATE') {
					$em->persist($user_delegate);
				}
				$em->flush();

				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'OK');
				$node->appendChild($attr);
				$root->appendChild($node);

				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Ret1';
				$node = $xml_result->createElement('Result', $user_delegate->getId());
				$node->appendChild($attr);
				$root->appendChild($node);
			}
			catch (\Exception $e) {
				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'FAILED');
				$node->appendChild($attr);
				$root->appendChild($node);
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'ErrMsg';
				$node = $xml_result->createElement('Result', 'Operation could not be completed due to this system error: '. $e->getMessage());
				$node->appendChild($attr);
				$root->appendChild($node);
			}
		}
		elseif ($action === 'REMOVEDELEGATE')
		{
			$delegate = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			try  {
				$delegate = $em->getRepository('DocovaBundle:UserDelegates')->findOneBy(['id' => $delegate, 'owner' => $this->user->getId()]);
				if (empty($delegate)) {
					throw new \Exception('Delegated record cannot be found!');
				}
				
				if ($delegate->getApplicableLibraries()->count())
				{
					foreach ($delegate->getApplicableLibraries() as $library) {
						$delegate->removeApplicableLibraries($library);
					}
				}
				$em->remove($delegate);
				$em->flush();

				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'OK');
				$node->appendChild($attr);
				$root->appendChild($node);
			}
			catch (\Exception $e) {
				$root = $xml_result->appendChild($xml_result->createElement('Results'));
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'Status';
				$node = $xml_result->createElement('Result', 'FAILED');
				$node->appendChild($attr);
				$root->appendChild($node);
				$attr = $xml_result->createAttribute('ID');
				$attr->value = 'ErrMsg';
				$node = $xml_result->createElement('Result', 'Operation could not be completed due to this system error: '. $e->getMessage());
				$node->appendChild($attr);
				$root->appendChild($node);
			}
		}
		
		$response->setContent($xml_result->saveXML());
		return $response;
	}
	
	public function simpleFileSharingAction(Request $request)
	{
		$this->initialize();
		$request = $request->getContent();
		$sync = new Synchronization($this->container, $this->user);
		$result = $sync->fileShareServices($request, true);

		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($result);
		return $response;
	}
	
	public function getUserWatchlistsAction(Request $request)
	{
		$type = $request->query->get('listid');
		if (empty($type) && $type != '0')
		{
			throw $this->createNotFoundException('List type is missed.');
		}
		
		$this->initialize();
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$response	= new Response();
		$response->headers->set('Content-Type', 'text/xml');
		switch ($type) 
		{
			case '0':
				$xml_result = $this->getPersonalWatchlist($request->query->get('format'));
				break;
			case '1':
				$xml_result = $this->grabFavoriteList($request->query->get('format'));
				break;
			case '2':
				$xml_result = $this->getRecentEditedList($request->query->get('format'));
				break;
			case '3':
				$xml_result = $this->getPendingWorkflow($request->query->get('format'));
				break;
			case '4':
				$xml_result = $this->getMyDocuments($request->query->get('format'));
				break;
			case '5':
				$xml_result = $this->getMyToDoList($request->query->get('format'));
				break;
			case '6':
				$xml_result = $this->grabFavoriteListWidget();
				break;
			case 'S':
				$xml_result = $this->getPersonalWatchlist($request->query->get('format'));
				break;
			default:
				break;
		}
		
		$response->setContent($xml_result->saveXML());
		return $response;
	}
	
	public function getEmbeddedViewAction(Request $request)
	{
		$view = $request->query->get('view');
		if (empty($view))
		{
			throw $this->createNotFoundException('View name is missed.');
		}
		
		$this->initialize();
		$response	= new Response();
		$response->headers->set('Content-Type', 'text/xml');
		switch ($view)
		{
			case 'luCIAOLogsByUser':
				$xml_result = $this->getCIAPLogsByUser();
				break;
			case 'xmlWorkflowItemsSummary':
				$library_folder = $request->query->get('restricttocategory');
				$xml_result = $this->getWorkflowItemsSummary($library_folder);
				break;
			case 'luUserActivityIncomplete':
				$xml_result = $this->getIncompleteUserActivities();
				break;
			case 'ludocumentsbyparent':
				$document = $request->query->get('restricttocategory');
				$suffix = substr($document, -1);
				$document = substr($document, 0, -1);
				$xml_result = $this->getDocumentsByParent($document, $suffix);
				break;
			case 'xmlReviewItems.xml':
				$xml_result = $this->getPendingReviewItems();
				break;
			case 'luDelegatesByOwner':
				$xml_result = $this->getDelegatesByOwner();
			default:
				break;
		}

		if (empty($xml_result))
		{
			$xml_result = '<?xml version="1.0" encoding="UTF-8"?><documents></documents>';
		}

		$response->setContent($xml_result);
		return $response;
	}

	public function openFileImportAction(Request $request)
	{
		$folder_id = $request->query->get('ParentUNID');
		if (empty($folder_id))
		{
			throw $this->createNotFoundException('Folder ID is missed.');
		}
	
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$folder_obj = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id, 'Del' => false, 'Inactive' => 0));
		if (empty($folder_obj))
		{
			throw $this->createNotFoundException('Unspecified folder source ID = '.$folder_id);
		}

		$security_check = new Miscellaneous($this->container);
		if (false === $security_check->canCreateDocument($folder_obj)) {
			throw new AccessDeniedException();
		}
		
		$document_types = array();
		if ($folder_obj->getDefaultDocType())
		{
			if ($folder_obj->getDefaultDocType() != -1 && $folder_obj->getApplicableDocType()->count())
			{
				$document_types = $folder_obj->getApplicableDocType();
			}
			elseif ($folder_obj->getDefaultDocType() == -1 && $folder_obj->getLibrary()->getApplicableDocType()->count()) {
				$document_types = $folder_obj->getLibrary()->getApplicableDocType();
			}
			elseif ($folder_obj->getDefaultDocType() == -1 || $folder_obj->getDefaultDocType()) {
				$document_types = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false, 'Status' => true));
			}
		}
	
		if ($request->isMethod('POST'))
		{
			$subject	= $request->request->get('Subject');
			$doc_type	= $request->request->get('DocumentTypeKey');
			$doc_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $doc_type, 'Trash' => false));
			if (empty($doc_type))
			{
				throw $this->createNotFoundException('Unspecified Document Type source.');
			}
			
			if (!$request->request->get('OFileNames'))
			{
				throw $this->createNotFoundException('No file is selected to be uploaded.');
			}
			
	
			$document = new Documents();
			$miscfunctions = new MiscFunctions();
			$document->setId($miscfunctions->generateGuid());
			$document->setCreator($this->user);
			$document->setAuthor($this->user);
			$document->setModifier($this->user);
			$document->setDateCreated(new \DateTime());
			$document->setDateModified(new \DateTime());
			$document->setDocStatus(($doc_type->getEnableLifecycle()) ? $doc_type->getInitialStatus() : $doc_type->getFinalStatus());
			$document->setStatusNo(($doc_type->getEnableLifecycle()) ? 0 : 1);
			$document->setDocTitle($subject);
			$document->setDocType($doc_type);
			$document->setFolder($folder_obj);
			$document->setOwner($this->user);
			if ($doc_type->getEnableLifecycle() && $doc_type->getEnableVersions()) {
				$document->setDocVersion('0.0');
				$document->setRevision(0);
			}
				
			$em->persist($document);
			$em->flush();
				
			$customAcl = new CustomACL($this->container);
				
			$security_check->createDocumentLog($em, 'CREATE', $document);
			$customAcl->insertObjectAce($document, $this->user, 'owner', false);
			if ($folder_obj->getSetAAE()) 
			{
				$folder_authors = $customAcl->getObjectACEUsers($folder_obj, 'create');
				if ($folder_authors->count() > 0) 
				{
					foreach ($folder_authors as $author) {
						if (false !== $user = $this->findUserAndCreate($author->getUsername(), false, true)) 
						{
							$customAcl->insertObjectAce($document, $user, 'edit', false);
						}
					}
				}
			}
			$customAcl->insertObjectAce($document, 'ROLE_USER', 'view');
			
			$field = $em->getRepository('DocovaBundle:DesignElements')->getUploaderField($doc_type->getId());

			$file_dates = preg_split('/;/', $request->request->get('OFileDates'));
			$file_names = preg_split('/;/', $request->request->get('OFileNames'));
			for ($i = 0; $i < count($file_dates); $i++) {
				if (!empty($file_dates[$i])) {
				    $tempDateString = $file_dates[$i];
				    $format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
				    $parsed = date_parse($tempDateString);
				    $is_period = (false === stripos($tempDateString, ' am') && false === stripos($tempDateString, ' pm')) ? false : true;
				    $time = (false !== $parsed['hour'] && false !== $parsed['minute']) ? ' H:i:s' : '';
				    $time .= !empty($time) && $is_period === true ? ' A' : '';
				    $tempDate = date_create_from_format($format.$time, $tempDateString);
				    $file_dates[$i] = ($tempDate === false ? new \DateTime() : $tempDate);
				}
				else {
					$file_dates[$i] = new \DateTime();
				}
			}
			$file_content = $request->files;
		
			$res = $this->moveUploadedFiles($file_content, $file_names, $file_dates, $document, $field);
			if ($res !== true) {
				throw $this->createNotFoundException('Could not upload files. - open file');
			}

			unset($security_check, $customAcl);
			
			$xml_result = new \DOMDocument('1.0', 'UTF-8');
			$root = $xml_result->appendChild($xml_result->createElement('Results'));
			$attr = $xml_result->createAttribute('ID');
			$attr->value = 'Status';
			$node = $xml_result->createElement('Result', 'OK');
			$node->appendChild($attr);
			$root->appendChild($node);
			$root->appendChild($xml_result->createElement('Unid', $document->getId()));
			$root->appendChild($xml_result->createElement('Url', $this->generateUrl('docova_homepage')));
			
			$response = new Response();
			$response->headers->set('Content-Type', 'text/xml');
			$response->setContent($xml_result->saveXML());
			return $response;
		}
	
		return $this->render('DocovaBundle:Default:dlgFileImport.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings,
				'folder' => $folder_obj,
				'doc_types' => $document_types
			));
	}
	
	public function openFileExportAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		if (trim($request->query->get('folderid'))) 
		{
			$folder = $request->query->get('folderid');
			$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder, 'Del' => false));
		}
		elseif (trim($request->query->get('libraryid'))) 
		{
			$library = $request->query->get('libraryid');
			$folder = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library, 'Trash' => false));
		}
		if (empty($folder)) 
		{
			throw $this->createNotFoundException('Unspecified source '.(!empty($library) ? 'Library' : 'Folder'). '.');
		}
		
		return $this->render('DocovaBundle:Default:dlgFileExport.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'folder' => $folder
		));
	}
	
	
	public function openImportFromFileAction(Request $request)
	{
	    $folder_id = '';
	    $app_id = '';
	    $folder_obj = null;
	    $app_obj = null;	  
	    $form_doc_types = null;
	    $docovaapp = null;
	    $formname = '';
	    $formid = '';

   	    $folder_id = $request->query->get('folderid');
   	    $app_id = $request->query->get('appid');
    	    
    	if (empty($folder_id) && empty($app_id))
    	{
    	    throw $this->createNotFoundException('No folder or application id specified.');
    	}
    	    
    	$this->initialize();
    	$em = $this->getDoctrine()->getManager();
    	    
    	if(!empty($folder_id)){
    	   $folder_obj = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id, 'Del' => false, 'Inactive' => 0));
    	   if (empty($folder_obj))
    	   {
    	      throw $this->createNotFoundException('Unspecified folder source ID = '.$folder_id);
    	   }
    	}else{
   	        $app_obj = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
    	    if (empty($app_obj))
    	    {
    	       throw $this->createNotFoundException('Unspecified application id = '.$app_id);
    	    }
    	}
 	    
        //-- for library / folder environment
    	if(!empty($folder_obj)){
    	    $security_check = new Miscellaneous($this->container);
    	    if (false === $security_check->canCreateDocument($folder_obj)) {
    	        throw new AccessDeniedException();
    	    }
    	    
    	    $document_types = array();
    	    if ($folder_obj->getDefaultDocType())
    	    {
    	        if ($folder_obj->getDefaultDocType() != -1 && $folder_obj->getApplicableDocType()->count())
    	        {
    	            $document_types = $folder_obj->getApplicableDocType();
    	        }
    	        elseif ($folder_obj->getDefaultDocType() == -1 && $folder_obj->getLibrary()->getApplicableDocType()->count()) {
    	            $document_types = $folder_obj->getLibrary()->getApplicableDocType();
    	        }
    	        elseif ($folder_obj->getDefaultDocType() == -1 || $folder_obj->getDefaultDocType()) {
    	            $document_types = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false, 'Status' => true));
    	        }
    	    }
    	    foreach($document_types as $doctype){
    	        $form_doc_types[] = $doctype->getDocName()."|".$doctype->getId();
    	    }
    	    sort($form_doc_types);
	    }
	    
	    //-- for application environment
	    if(!empty($app_obj)){
	        $formlist = $em->getRepository('DocovaBundle:AppForms')->findBy(array('trash' => false, 'application' => $app_id));
	        foreach($formlist as $form){
	            $form_doc_types[] = $form->getFormName()."|".$form->getId();
	        }
	        sort($form_doc_types);
	    }
	    
	    //-- if the dialog has been submitted
    	if ($request->isMethod('POST'))
	    {
	        $doc_type	= $request->request->get('DocumentTypeKey');
	        
	        if(!empty($folder_id)){
    	        $doc_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $doc_type, 'Trash' => false));
    	        
    	        if (empty($doc_type))
    	        {
    	            throw $this->createNotFoundException('Unable to locate matching document type.');
    	        }
    	        
	        }else{
	            $doc_type = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' => $doc_type, 'application' => $app_id, 'trash' => false));
	            
	            if (empty($doc_type))
	            {
	                throw $this->createNotFoundException('Unable to locate matching form.');
	            }
	            
	            $docovaapp = $this->docova->DocovaApplication(null, $app_obj);	    
	            $formname = $doc_type->getFormName();
	            $formid = $doc_type->getId();
	        }
	 
	        $importresults = array();
	        $delemrep = $em->getRepository('DocovaBundle:DesignElements');
	 
	        $file_content = $request->files;
	        $files_list = $file_content->get('Uploader_DLI_Tools');
	        foreach ($files_list as $file) {
	            $file_name	= html_entity_decode($file->getClientOriginalName(), ENT_COMPAT, 'UTF-8');
	            $importresults[] = array(
	                'filename'=>$file_name,
	                'added'=>0,
	                'errors'=>0
	            ); 

	            $columns = array();
	            $fieldinfo = array();
	            $columncount = 0;
	            $datacolcount = 0;
	            $row = 1;
	            if (($handle = fopen($file->getPathname(), "r")) !== FALSE) {
	                while (($data = fgetcsv($handle, 0, ",", '"')) !== FALSE) {
	                    if($row == 1){
	                        $columns = $data;
	                        $columncount = count($columns);
	                        for($a=1; $a<$columncount; $a++){
	                            if(! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $columns[$a])){
	                                $xml_result = new \DOMDocument('1.0', 'UTF-8');
	                                $root = $xml_result->appendChild($xml_result->createElement('Results'));
	                                $attr = $xml_result->createAttribute('ID');
	                                $attr->value = 'Status';
	                                $node = $xml_result->createElement('Result', 'FAILED');
	                                $node->appendChild($attr);
	                                $root->appendChild($node);
	                                
	                                $cdata = $xml_result->createCDATASection("Import file missing header row containing field names, or field names contain invalid characters.");
	                                $node = $xml_result->createElement('ErrMsg');
	                                $node->appendChild($cdata);
	                                $root->appendChild($node);
	                                	                                	                                
	                                $response = new Response();
	                                $response->headers->set('Content-Type', 'text/xml');
	                                $response->setContent($xml_result->saveXML());
	                                return $response;
	                            }
	                        }
	                        $fieldinfo = $delemrep->getFormElementsBy($columns, $formid, $app_id);
	                    }else{
	                        $datacolcount = count($data);
	                        $docovadoc = $this->docova->DocovaDocument($docovaapp);	
	                        $docovadoc->setField("form", $formname); //-- set form so that it is available to look up other field values
	                        for ($c=0; $c < $datacolcount; $c++) {
	                            if($c < $columncount){
	                                $fieldname = $columns[$c];
	                                $tempdata = (string)$data[$c];
	                                $fldtype = 0;
	                                $mvseps = '';
	                                if(!empty($fieldinfo) && array_key_exists(strtolower($fieldname), $fieldinfo)){
	                                    $fldtype = $fieldinfo[strtolower($fieldname)]['fieldType'];
	                                    $mvseps = $fieldinfo[strtolower($fieldname)]['multiSeparator'];
	                                }
	                                if(!empty($mvseps)){	                                    
	                                    $tempdata = explode( chr(1), str_replace( str_split($mvseps), chr(1), $tempdata ) );
	                                }
	                                if(!is_array($tempdata)){
	                                   $tempdata = array($tempdata);
	                                }
	                                
	                                foreach($tempdata as $key => $dataelem){
	                                    switch ($fldtype)
	                                    {
	                                        case 1:  //-- Date Time
	                                            //-- look for date string in the format #yyyy-mm-dd#
	                                            if(strlen($dataelem) > 3 && substr($dataelem, 0, 1) == "#" && substr($dataelem, -1) == "#"){
	                                                $dataelem = substr($dataelem, 1, -1);
	                                            }
	                                            try {
	                                                $dataelem = new \DateTime($dataelem);
	                                            }catch (\Exception $e) {
	                                                if (false !== strpos($dataelem, '/')) {
	                                                    $dataelem = str_replace('/', '-', $dataelem);
	                                                    try {
	                                                        $dataelem = new \DateTime($dataelem);
	                                                    }catch (\Exception $e) {
	                                                        $dataelem = null;
	                                                    }
	                                                }
	                                            }
	                                            
	                                            break;
	                                        case 3: //-- Names
	                                            break;
	                                        case 4: //-- Numeric
	                                            if(is_numeric($dataelem)){
	                                                try {
    	                                                $dataelem = (float)$dataelem;
	                                                }
	                                                catch (\Exception $e) {
	                                                    $dataelem = null;	                                                    
	                                                }
	                                            }else{
	                                                $dataelem = null;
	                                            }
	                                            break;
	                                        case 5:  //-- Rich Text
	                                            break;
	                                        default:  //-- Text
	                                            break;
	                                    }
	                                    
	                                    $tempdata[$key] = $dataelem;
	                                }

	                                $docovadoc->setField($columns[$c], $tempdata);	                                
	                            }
	                        }
	                        $docovadoc->setField("form", $formname);  //--reset just in case a column value set it
	                        $saveresult = $docovadoc->save();
	                        if($saveresult){
	                            $importresults[count($importresults)-1]['added']++;	                            
	                        }else{
	                            $importresults[count($importresults)-1]['errors']++;	                            
	                        }
	                    }
	                    $row++;

	                }
	                fclose($handle);
	            }
	            
	        }
	   
	        $stats = "";
	        foreach($importresults as $impres){
	            $stats .= "Imported " . (string)$impres['added'] . " record(s) from file [" . $impres['filename'] . "] with " . (string)$impres['errors'] . " errors.\n";  
	        }
	        
	        unset($security_check, $customAcl, $delemrep);
	        
	        $xml_result = new \DOMDocument('1.0', 'UTF-8');
	        $root = $xml_result->appendChild($xml_result->createElement('Results'));
	        $attr = $xml_result->createAttribute('ID');
	        $attr->value = 'Status';
	        $node = $xml_result->createElement('Result', 'OK');
	        $node->appendChild($attr);
	        $root->appendChild($node);
	        
	        $root->appendChild($xml_result->createElement('Url', $this->generateUrl('docova_homepage')));
	        
	        $cdata = $xml_result->createCDATASection($stats);
	        $node = $xml_result->createElement('Stats');
	        $node->appendChild($cdata);
	        $root->appendChild($node);
	        	        
	        $response = new Response();
	        $response->headers->set('Content-Type', 'text/xml');
	        $response->setContent($xml_result->saveXML());
	        return $response;
	    }
	    
	    //-- render the dialog
	    return $this->render('DocovaBundle:Default:dlgImportFromFile.html.twig', array(
	        'user' => $this->user,
	        'settings' => $this->global_settings,
	        'folder' => $folder_obj,
	        'application' => $app_obj,
	        'doc_types' => $form_doc_types
	    ));
	}
	
	
	public function openSelectForCompareAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgSelectDocsForCompare.html.twig', array(
				'user' => $this->user
			));
	}
	
	public function openDocumentPropertiesAction(Request $request)
	{
		$doc_id = $request->query->get('ParentUNID');
		if (empty($doc_id))
		{
			$this->createNotFoundException('Document ID is missed.');
		}
		
		$document = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id, 'Archived' => false));
		if (empty($document))
		{
			throw $this->createNotFoundException('Unspecified document source ID = '.$doc_id);
		}

		$this->initialize();
		$doc_editors = $doc_readers = array();
		$customAcl = new CustomACL($this->container);
		$user_list = $customAcl->getObjectACEUsers($document, 'edit');
		if ($user_list->count() > 0) {
			for ($x = 0; $x < $user_list->count(); $x++) {
				if (false !== $user = $this->findUserAndCreate($user_list[$x]->getUsername(), false, true)) 
				{
					$doc_editors[] = $user->getUserNameDnAbbreviated();
				}
			}
			unset($user);
		}
			
		$user_list = $customAcl->getObjectACEUsers($document, 'view');
		if ($user_list->count() > 0) {
			for ($x = 0; $x < $user_list->count(); $x++) {
				if (false !== $user = $this->findUserAndCreate($user_list[$x]->getUsername(), false, true)) 
				{
					$doc_readers[]= $user->getUserNameDnAbbreviated();
				}
			}
			unset($user);
		}
		
		$doc_editors = (!empty($doc_editors)) ? implode(',', $doc_editors) : '';
		$doc_readers = (!empty($doc_readers)) ? implode(',', $doc_readers) : '';
		$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
		
		return $this->render('DocovaBundle:Default:dlgDocumentProperties.html.twig', array(
			'user' => $this->user,
			'document' => $document,
			'editors' => $doc_editors,
			'readers' => $doc_readers,
			'date_format' => $defaultDateFormat
		));
	}

	public function popupSelectScanTypeAction()
	{
		$this->initialize();

		return $this->render('DocovaBundle:Default:dlgSelectScanType.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function popupCustomSearchAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$doctypes = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true, 'Trash' => false), array('Doc_Name' => 'ASC'));
		$custom_fields = $em->getRepository('DocovaBundle:CustomSearchFields')->findBy(array(), array('fieldDescription' => 'ASC'));
		$custom_search_fields = $doctype_keys = '';
		$defaultDateFormat = strtolower($this->global_settings->getDefaultDateFormat());
		
		if (!empty($custom_fields)) 
		{
			foreach ($custom_fields as $csf) 
			{
				$custom_search_fields .= '<Field>';
				$custom_search_fields .= '<DocKey>'.$csf->getId().'</DocKey>';
				$custom_search_fields .= '<FieldDescription><![CDATA['.$csf->getFieldDescription().']]></FieldDescription>';
				$custom_search_fields .= '<FieldName>'.$csf->getFieldName().'</FieldName>';
				$custom_search_fields .= '<DataType>'.$csf->getDataType().'</DataType>';
				$custom_search_fields .= '<TextEntryType>'.$csf->getTextEntryType().'</TextEntryType>';
				$custom_search_fields .= '<SelectValues><![CDATA['.str_replace(array("\r\n", "\r"), ';', $csf->getSelectValues()).']]></SelectValues>';
				$custom_search_fields .= '<RelatedDocType>'.($csf->getDocumentTypes()->count() > 0 ? 'S' : 'A').'</RelatedDocType>';
				if ($csf->getDocumentTypes()->count() > 0) 
				{
					foreach ($csf->getDocumentTypes() as $dt) {
						$doctype_keys .= $dt->getId().';';
					}
					unset($dt);
					$doctype_keys = substr_replace($doctype_keys, '', -1);
				}
				$custom_search_fields .= '<DocKypeKey>'.$doctype_keys.'</DocKypeKey>';
				$custom_search_fields .= '</Field>';
			}
			unset($custom_fields, $csf);
		}
		
		return $this->render('DocovaBundle:Default:dlgCustomSearch.html.twig', array(
			'user' => $this->user,
			'document_types' => $doctypes,
			'date_format' => $defaultDateFormat,
			'custom_search_fields_xml' => $custom_search_fields
		));
	}
	
	public function popupActivityAction(Request $request)
	{
		$doc_id = $request->query->get('ParentUNID');
		if (!empty($doc_id)) 
		{
			$this->initialize();
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id, 'Trash' => false, 'Archived' => false));
			if (empty($doc_id)) 
			{
				throw $this->createNotFoundException('Unspecified Document source ID = '.$doc_id);
			}
			
			$customACL_obj = new CustomACL($this->container);
			$users_list = $customACL_obj->getObjectACEUsers($document->getFolder(), 'view');
			$readers = array();
			if ($users_list->count() > 0) 
			{
				$ancestors = $em->getRepository('DocovaBundle:Folders')->getAncestors($document->getFolder()->getPosition(), $document->getFolder()->getLibrary()->getId(), $document->getFolder()->getId(), true);
				if (!empty($ancestors)) 
				{
					foreach ($ancestors as $folder) {
						$folder = $em->getReference('DocovaBundle:Folders', $folder);
						$users_list = $customACL_obj->getObjectACEUsers($folder, 'owner');
						if ($users_list->count() > 0)
						{
							foreach ($users_list as $user)
							{
								if (false !== $user_obj = $this->findUserAndCreate($user->getUsername(), false, true)) {
									$readers[] = $user_obj->getUserNameDnAbbreviated();
								}
							}
						}
						$users_list = $customACL_obj->getObjectACEUsers($folder, 'master');
						if ($users_list->count() > 0)
						{
							foreach ($users_list as $user)
							{
								if (false !== $user_obj = $this->findUserAndCreate($user->getUsername(), false, true)) {
									$readers[] = $user_obj->getUserNameDnAbbreviated();
								}
							}
						}
						$users_list = $customACL_obj->getObjectACEUsers($folder, 'view');
						if ($users_list->count() > 0)
						{
							foreach ($users_list as $user)
							{
								if (false !== $user_obj = $this->findUserAndCreate($user->getUsername(), false, true)) {
									$readers[] = $user_obj->getUserNameDnAbbreviated();
								}
							}
						}
					}
					unset($folder, $users_list, $user_obj);
					$readers = array_unique($readers);
				}
			}
			$activities = $em->getRepository('DocovaBundle:Activities')->findAll();
			
			return $this->render('DocovaBundle:Default:dlgActivity.html.twig', array(
				'user' => $this->user,
				'document' => $document,
				'activities' => $activities,
				'parent_readers_list' => $readers
			));
		}
	}
	
	public function openActivityDialogAction($activity)
	{
		$em = $this->getDoctrine()->getManager();
		$activity = $em->getRepository('DocovaBundle:DocumentActivities')->find($activity);
		return $this->render('DocovaBundle:Default:dlgActivityDetails.html.twig', array('activity' => $activity));
	}
	
	public function editActivityAction($activity)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$activity = $em->getRepository('DocovaBundle:DocumentActivities')->findOneBy(array('id' => $activity, 'isComplete' => false));
		if (!empty($activity)) 
		{
			return $this->render('DocovaBundle:Default:dlgEditActivity.html.twig', array(
				'user' => $this->user,
				'activity' => $activity
			));
		}
		
		$response = new Response();
		$response->setContent('Activity Does not exist.');
		return $response;
	}

	public function lookupActivityAction(Request $request)
	{
		$response = new Response();
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root	= $xml_result->appendChild($xml_result->createElement('viewentries'));
		$attrib	= $xml_result->createAttribute('toplevelentries');
		$attrib->value = 1;
		$root->appendChild($attrib);
		$attrib	= $xml_result->createAttribute('timestamp');
		$attrib->value = time();
		$root->appendChild($attrib);
		
		$activity_id = $request->query->get('StartKey');
		if (!empty($activity_id))
		{
			$em = $this->getDoctrine()->getManager();
			$activity = $em->getRepository('DocovaBundle:Activities')->findOneBy(array('activityAction' => $activity_id));
			if (!empty($activity)) 
			{
				$viewentry = $xml_result->createElement('viewentry');
				$attrib	= $xml_result->createAttribute('siblings');
				$attrib->value = 1;
				$viewentry->appendChild($attrib);
				$attrib	= $xml_result->createAttribute('noteid');
				$attrib->value = $activity_id;
				$viewentry->appendChild($attrib);
				$attrib	= $xml_result->createAttribute('unid');
				$attrib->value = $activity_id;
				$viewentry->appendChild($attrib);
				$attrib	= $xml_result->createAttribute('position');
				$attrib->value = 1;
				$viewentry->appendChild($attrib);
				
				$newnode = $xml_result->createElement('entrydata');
				$textnode = $xml_result->createElement('text', $activity->getActivityAction());
				$newnode->appendChild($textnode);
				$attrib	= $xml_result->createAttribute('name');
				$attrib->value = 'ActivityAction';
				$newnode->appendChild($attrib);
				$attrib	= $xml_result->createAttribute('columnnumber');
				$attrib->value = 0;
				$newnode->appendChild($attrib);
				$viewentry->appendChild($newnode);
				
				$newnode = $xml_result->createElement('entrydata');
				$textnode = $xml_result->createElement('text', $activity->getActivityObligation());
				$newnode->appendChild($textnode);
				$attrib	= $xml_result->createAttribute('name');
				$attrib->value = 'ActivityObligation';
				$newnode->appendChild($attrib);
				$attrib	= $xml_result->createAttribute('columnnumber');
				$attrib->value = 1;
				$newnode->appendChild($attrib);
				$viewentry->appendChild($newnode);
				
				$newnode = $xml_result->createElement('entrydata');
				$textnode = $xml_result->createElement('text', $activity->getActivitySendMessage() ? '1' : '0');
				$newnode->appendChild($textnode);
				$attrib	= $xml_result->createAttribute('name');
				$attrib->value = 'ActivitySendMessage';
				$newnode->appendChild($attrib);
				$attrib	= $xml_result->createAttribute('columnnumber');
				$attrib->value = 2;
				$newnode->appendChild($attrib);
				$viewentry->appendChild($newnode);

				$newnode = $xml_result->createElement('entrydata');
				$textnode = $xml_result->createElement('text', $activity->getActivitySubject());
				$newnode->appendChild($textnode);
				$attrib	= $xml_result->createAttribute('name');
				$attrib->value = 'ActivitySubject';
				$newnode->appendChild($attrib);
				$attrib	= $xml_result->createAttribute('columnnumber');
				$attrib->value = 3;
				$newnode->appendChild($attrib);
				$viewentry->appendChild($newnode);
				
				$newnode = $xml_result->createElement('entrydata');
				$textnode = $xml_result->createElement('text', $activity->getActivityInstruction());
				$newnode->appendChild($textnode);
				$attrib	= $xml_result->createAttribute('name');
				$attrib->value = 'ActivityInstruction';
				$newnode->appendChild($attrib);
				$attrib	= $xml_result->createAttribute('columnnumber');
				$attrib->value = 4;
				$newnode->appendChild($attrib);
				$viewentry->appendChild($newnode);
				$root->appendChild($viewentry);
			}
		}
		
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml_result->saveXML());
		return $response;
	}
	
	public function getDataVersionLogAction(Request $request)
	{
		$response = new Response();
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $result_xml->appendChild($result_xml->createElement('documents'));
		$doc_id = $request->query->get('RestrictToCategory');
		if (!empty($doc_id)) 
		{
			$em = $this->getDoctrine()->getManager();
			$documents = $em->getRepository('DocovaBundle:Documents')->getAllDocVersions($doc_id);
			if (!empty($documents)) 
			{
				$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
				$global_settings = $global_settings[0];
				$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
				unset($global_settings);
				foreach ($documents as $doc) {
					$doc instanceof Documents;
					$node = $result_xml->createElement('Document');
					$cdata = $result_xml->createCDATASection($doc->getDocTitle());
					$newnode = $result_xml->createElement('Subject');
					$newnode->appendChild($cdata);
					$node->appendChild($newnode);
					$newnode = $result_xml->createElement('FullVersion', ($doc->getDocVersion()) ? $doc->getDocVersion().'.'.$doc->getRevision() : '0.0.0');
					$node->appendChild($newnode);
					$newnode = $result_xml->createElement('Status', $doc->getDocStatus());
					$node->appendChild($newnode);
					$newnode = $result_xml->createElement('ParentDocKey', $doc->getId());
					$node->appendChild($newnode);
					$newnode = $result_xml->createElement('ParentDocID', $doc->getId());
					$node->appendChild($newnode);
					$newnode = $result_xml->createElement('ReleasedBy');
					$cdata = $result_xml->createCDATASection($doc->getReleasedBy() ? $doc->getReleasedBy()->getUserProfile()->getDisplayName() : '');
					$newnode->appendChild($cdata);
					$node->appendChild($newnode);
					$value = ($doc->getReleasedDate()) ? $doc->getReleasedDate()->format($defaultDateFormat . ' h:i:s A') : '';
					$newnode = $result_xml->createElement('ReleasedDate', $value);
					$node->appendChild($newnode);
					$newnode = $result_xml->createElement('IsCurrent', ($doc->getId() == $doc_id) ? 1 : 0);
					$node->appendChild($newnode);
					$newnode = $result_xml->createElement('IsAvailable', 1);
					$node->appendChild($newnode);
					$newnode = $result_xml->createElement('Location');
					$node->appendChild($newnode);
					$root->appendChild($node);
				}
			}
		}
		
		if (empty($node)) 
		{
			$newnode = $result_xml->createElement('h2', 'No documents found');
			$root->appendChild($newnode);
		}
		
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($result_xml->saveXML());
		return $response;
	}
	
	public function discussionTopicAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		if (trim($request->query->get('Topic'))) 
		{
			$topic = $request->query->get('Topic');
			$discussion = $em->getRepository('DocovaBundle:DiscussionTopic')->find($topic);
			if (empty($discussion)) 
			{
				throw $this->createNotFoundException('Unspecified discussion topic source ID = '. $topic);
			}
			
			$security_context = $this->container->get('security.authorization_checker');
			if (!$security_context->isGranted('ROLE_ADMIN') && !$discussion->getCreatedBy()->getUserNameDnAbbreviated() == $this->user->getUserNameDnAbbreviated()) 
			{
				throw new AccessDeniedException();
			}
			
			$document = $discussion->getParentDocument();
		}
		else {
			$discussion = null;
			$document = $request->query->get('ParentUNID');//parent document ID
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Archived' => false, 'Trash' => false));
		}
		if (empty($document)) 
		{
			throw $this->createNotFoundException('Unspecified document source.');
		}
		
		//get parent discussion thread
		$parent_thread = trim($request->query->get('ParentThread')) ? trim($request->query->get('ParentThread')) : null;
		
		if ($request->isMethod('POST')) 
		{
			if (trim($request->request->get('Subject'))) 
			{
				if (empty($discussion)) 
				{
					$discussion = new DiscussionTopic();
					$discussion->setCreatedBy($this->user);
					$discussion->setDateCreated(new \DateTime());
					$discussion->setParentDocument($document);
				}
				$discussion->setSubject($request->request->get('Subject'));
				$discussion->setBody(trim($request->request->get('Body')) ? $request->request->get('Body') : null);
				if ($parent_thread) 
				{
					$parent_discussion = $em->getRepository('DocovaBundle:DiscussionTopic')->find($parent_thread);
					if (!empty($parent_discussion)) 
					{
						$discussion->setParentDiscussion($parent_discussion);
					}
					unset($parent_discussion);
				}
				if (empty($topic)) 
				{
					$em->persist($discussion);
				}
				$em->flush();
				
				if (trim($request->request->get('%%Detach'))) 
				{
					$file_name	= trim($request->request->get('%%Detach'));
					if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($file_name))) {
						@unlink($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($file_name));
					}
					
					$discussion_attachment = $em->getRepository('DocovaBundle:DiscussionAttachments')->findOneBy(array('fileName' => $file_name, 'discussion' => $discussion->getId()));
					if (!empty($discussion_attachment)) 
					{
						$em->remove($discussion_attachment);
						$em->flush();
					}
					unset($discussion_attachment);
				}
				
				if (!empty($request->files)) 
				{
					$uploaded_files = $request->files;
					$files_list = $uploaded_files->get('Uploader_DLI_Tools');
					if (!empty($files_list) && count($files_list) > 0) 
					{
						foreach ($files_list as $file)
						{
							$file_name	= html_entity_decode($file->getClientOriginalName());
							if (false === strpos($file_name, '~dthmb.bmp')) 
							{
								if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($file_name))) {
									$file_name = rand(0, 10000).$file_name;
								}
								
								$file_size = $file->getClientSize();
								$mime_type = $file->getMimeType();
								$res = $file->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), md5($file_name));
								
								if (!empty($res))
								{
									$discussion_attachment = new DiscussionAttachments();
									$discussion_attachment->setFileName($file_name);
									$discussion_attachment->setFileDate(new \DateTime());
									$discussion_attachment->setFileSize($file_size);
									$discussion_attachment->setFileMimeType($mime_type);
									$discussion_attachment->setDiscussion($discussion);
									$em->persist($discussion_attachment);
									$em->flush();
									unset($discussion_attachment);
								}
							}
						}
					}
				}
				
				return $this->redirect($this->generateUrl('docova_readdocument', array('doc_id' => $document->getId())) . '?ParentUNID=' . $document->getFolder()->getId());
			}
		}
		
		return $this->render('DocovaBundle:Default:wDiscussionTopic.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'document' => $document,
			'discussion' => $discussion,
			'parent_thread' => $parent_thread
		));
	}
	
	public function openTopicAction($topic)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$discussion = $em->getRepository('DocovaBundle:DiscussionTopic')->find($topic);
		if (empty($discussion)) 
		{
			throw $this->createNotFoundException('Unspecified discussion topic source ID = '. $topic);
		}
		
		return $this->render('DocovaBundle:Default:wDiscussionTopic-read.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'discussion' => $discussion
		));
	}
	
	public function getDiscussionThreadAction() 
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$xsl = '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:xalan="http://xml.apache.org/xslt">
	<xsl:template match="document">
		<div class="discitemcontainer">
			<xsl:attribute name="style">
				padding-left:<xsl:value-of select="indent"/>0px;
			</xsl:attribute>
			<span class="discitem" onclick="OpenTopic(this)" onmouseover="HighlightTopic(this)" onmouseout="NormalTopic(this)">
				<xsl:attribute name="id">
					<xsl:value-of select="docid" />
				</xsl:attribute>
				<img style="margin: 0px 5px 0px 0px; border:0px;" src="'.$this->get('assets.packages')->getUrl('bundles/docova/images/').'icn16-discchatbwlight.gif" align="top"/>
				<xsl:value-of select="subject"/>
   				(<xsl:value-of select="createddate"/> - <xsl:value-of select="createdby"/>)
			</span>
		</div>
	</xsl:template>
</xsl:stylesheet>';
		
		$response->setContent($xsl);
		return $response;
	}
	
	public function getDiscussionByParentAction(Request $request)
	{
		$document = $request->query->get('RestricttoCategory');
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$result_xml = '<?xml version="1.0" encoding="UTF-8"?><documents><h2>No documents found</h2></documents>';
		if (!empty($document)) 
		{
			$em = $this->getDoctrine()->getManager();
			$discussion = $em->getRepository('DocovaBundle:DiscussionTopic')->findBy(array('parentDocument' => $document, 'parentDiscussion' => NULL));
			if (!empty($discussion) && count($discussion) > 0) 
			{
				$result_xml = '<?xml version="1.0" encoding="UTF-8"?><documents>';
				$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
				$global_settings = $global_settings[0];
				$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
				unset($global_settings);
				foreach ($discussion as $d) 
				{
					$result_xml .= $this->buildChildDiscussionTree($d, $defaultDateFormat);
				}
				unset($d);
				$result_xml .= '</documents>';
			}
		}
		
		$response->setContent($result_xml);
		return $response;
	}
	
	public function showCheckInFilesDialogAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgCheckInFiles.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function getDocumentPropertiesXMLAction(Request $request)
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><document>';
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');

		$doc_id = $request->query->get('ParentUNID');
		if (!empty($doc_id))
		{
			$document = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id, 'Archived' => false));
			if (!empty($document))
			{
				$doc_editors = $doc_readers = array();
				$customAcl = new CustomACL($this->container);
				$user_list = $customAcl->getObjectACEUsers($document, 'edit');
				if ($user_list->count() > 0) {
					for ($x = 0; $x < $user_list->count(); $x++) {
						if (false !== $user = $this->findUserAndCreate($user_list[$x]->getUsername(), false, true))
						{
							$doc_editors[] = $user->getUserNameDnAbbreviated();
						}
					}
					unset($user);
				}
					
				$user_list = $customAcl->getObjectACEUsers($document, 'view');
				if ($user_list->count() > 0) {
					for ($x = 0; $x < $user_list->count(); $x++) {
						if (false !== $user = $this->findUserAndCreate($user_list[$x]->getUsername(), false, true))
						{
							$doc_readers[]= $user->getUserNameDnAbbreviated();
						}
					}
					unset($user);
				}
					
				$doc_editors = (!empty($doc_editors)) ? implode(',', $doc_editors) : '';
				$doc_readers = (!empty($doc_readers)) ? implode(',', $doc_readers) : '';
				$security_check = new Miscellaneous($this->container);
				$access_level = $security_check->getAccessLevel($document->getFolder());
				unset($security_check);
				
				$xml .= "<DocID>{$document->getId()}</DocID>
<DocKey>{$document->getId()}</DocKey>
<DocumentNumber>{$document->getId()}</DocumentNumber>
<DocumentTypeKey>{$document->getDocType()->getId()}</DocumentTypeKey>
<DocumentType><![CDATA[{$document->getDocType()->getDocName()}]]></DocumentType>
<Managers><![CDATA[managers]]></Managers>
<DocAuthors><![CDATA[[$doc_editors]]></DocAuthors>
<DocReaders><![CDATA[$doc_readers]]></DocReaders>
<Subject><![CDATA[{$document->getDocTitle()}]]></Subject>
<isEditable>1</isEditable>
<isDeleted>".($document->getTrash() ? 1 : '')."</isDeleted>
<CreatedBy><![CDATA[{$document->getCreator()->getUserNameDnAbbreviated()}]]></CreatedBy>
<CreatedDate>{$document->getDateCreated()->format('m/d/Y')}</CreatedDate>
<DocumentOwner><![CDATA[{$document->getOwner()->getUserNameDnAbbreviated()}]]></DocumentOwner>
<DbAccessLevel>{$access_level['dbaccess']}</DbAccessLevel>
<DocAccessLevel>{$access_level['docacess']}</DocAccessLevel>
<DocAccessRole>{$access_level['docrole']}</DocAccessRole>
<FolderID>{$document->getFolder()->getId()}</FolderID>
<FolderName><![CDATA[{$document->getFolder()->getFolderName()}]]></FolderName>
<FolderAncestors></FolderAncestors>
<FolderPath><![CDATA[]]></FolderPath>
<LockEditor><![CDATA[".($document->getLockEditor() ? $document->getLockEditor()->getUserNameDnAbbreviated() : '')."]]></LockEditor>
<LockDate>".($document->getLocked() ? $document->getDateModified()->format('m/d/Y') : '')."</LockDate>
<isLockEditor><![CDATA[".($document->getLockEditor() && $document->getLockEditor()->getId() == $this->user->getId() ? 1 : '')."]]></isLockEditor>
<isLocked>".($document->getLocked() ? 1 : '')."</isLocked>
<EnableFileCIAO>1</EnableFileCIAO>
<MaxFiles>0</MaxFiles>
<EnableLocalScan></EnableLocalScan>
<EnableMailAcquire></EnableMailAcquire>
<EnableDropboxAcquire></EnableDropboxAcquire>
<HasWorkflow></HasWorkflow>
<isWorkflowCreated></isWorkflowCreated>
<isWorkflowCompleted></isWorkflowCompleted>
<EnableVersions>1</EnableVersions>
<Version>{$document->getDocVersion()}</Version>
<Revision>{$document->getRevision()}</Revision>
<isCurrentVersion></isCurrentVersion>
<isNewVersion>1</isNewVersion>
<HasNewVersion></HasNewVersion>
<HasAttachmentsSection>1</HasAttachmentsSection>
<AttachmentNames><![CDATA[]]></AttachmentNames>
<AttachmentLengths></AttachmentLengths>
<AttachmentDates></AttachmentDates>
<AllowFileCIAO></AllowFileCIAO>
<DocumentTypeDetails></DocumentTypeDetails>";
			}
		}
		
		$xml .= '</document>';
		$response->setContent($xml);
		return $response;
	}
	
	public function checkInFileAndUploadAction(Request $request, $document)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$xml = '<?xml version="1.0" encoding="UTF-8"?><Results>';
		try {
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Archived' => false));
			if (!empty($document)) 
			{
				if (!empty($request->files) && $request->files->get('Uploader_DLI_Tools'))
				{
					$this->initialize();
					$filenames = $request->request->get('%%Detach');
					$res = $this->moveUploadedFiles($request->files, array($filenames), array(), $document, null, 'CHECKIN');
					if ($res === true) 
					{
						$xml .= '<Result ID="Status">OK<Result>';
						$xml .= "<Unid>{$document->getId()}</Unid>";
						$xml .= '<Url>'.$this->generateUrl('docova_homepage', array(), true).'</Url>';
						$xml .= '</Result>';
						
						$response->setContent($xml);
						return $response;
					}
				}
			}
		}
		catch (\Exception $e) {
			unset($document, $em);
			//@TODO: log the occured exception and message
		}

		$xml .= '<Result ID="Status">FAILED<Result>';
		$xml .= '<Ret1 ID="ErrMsg">'.$e->getMessage().'</Ret1>';
		$xml .= '</Result>';
		$response->setContent($xml);
		return $response;
	}
	
	public function openRelatedEmailAction($mail)
	{
		$em = $this->getDoctrine()->getManager();
		$mail = $em->find('DocovaBundle:RelatedEmails', $mail);
		$embeddedAttachments = null;
		if (!empty($mail)) 
		{
			$this->initialize();
			$mail_content = file_get_contents($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'mails'.DIRECTORY_SEPARATOR.$mail->getId());
			$embeddedAttachmentsPath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$mail->getId().DIRECTORY_SEPARATOR;
			
			try {
				$files = new \DirectoryIterator($embeddedAttachmentsPath);
				foreach ($files as $embeddedAttachment ){
					if ($embeddedAttachment->isFile()){
						$webPath = "/".$this->container->getParameter("docova_instance")."/_storage/Embedded/".$mail->getId()."/";
						$embeddedAttachments[] = array($webPath.$embeddedAttachment->getFilename(),$embeddedAttachment->getFilename() );
					}					  
				}
			} 
			catch (\Exception $e) {				
			}
			
		}
		else {
			$mail_content = '';
		}
		return $this->render('DocovaBundle:Default:openRelatedEmail.html.twig', array(
			'user' => $this->user,
			'mail' => $mail,
			'mail_content' => $mail_content,
			'mail_attachments' => $embeddedAttachments
		));
	}

	public function showRelatedLinksDialogAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgGetRelatedLink.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function popupEditAttachmentsAction()
	{
	    return $this->render('DocovaBundle:Default:dlgEditAttachments.html.twig', array(
	        'user' => $this->user
	    ));
	}
	
	public function showSelectAttachmentAction()
	{
		return $this->render('DocovaBundle:Default:dlgSelectAttachments.html.twig');
	}

	public function popupDlgDelegateAction($delegateid)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$libraries = $em->getRepository('DocovaBundle:Libraries')->findBy(array('Trash' => false, 'Status' => true), array('Library_Title' => 'ASC'));
		$workflows = $em->getRepository('DocovaBundle:Workflow')->findBy(array('Trash' => false), array('Workflow_Name' => 'ASC'));
		if (!empty($delegateid))
		{
			$delegate = $em->getRepository('DocovaBundle:UserDelegates')->find($delegateid);
		}
		return $this->render('DocovaBundle:Admin:dlgDelegate.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'libraries' => $libraries,
			'workflows' => $workflows,
			'userdelegate' => !empty($delegate) ? $delegate : null
		));
	}
	
	/**
	 * Build xml nodes for discussion thread(s) and their possible children
	 * 
	 * @param \Docova\DocovaBundle\Entity\DiscussionTopic $discussion
	 * @param string $defaultDateFormat
	 * @return string
	 */
	private function buildChildDiscussionTree($discussion, $defaultDateFormat)
	{
		$xml_result = '<document>';
		$xml_result .= "<docid>{$discussion->getId()}</docid>";
		$xml_result .= "<dockey>{$discussion->getId()}</dockey>";
		$xml_result .= "<indent>{$discussion->getIndent()}</indent>";
		$xml_result .= "<subject><![CDATA[{$discussion->getSubject()}]]></subject>";
		$xml_result .= "<createddate>{$discussion->getDateCreated()->format($defaultDateFormat)}</createddate>";
		$xml_result .= "<createdby><![CDATA[{$discussion->getCreatedBy()->getUserNameDnAbbreviated()}]]></createdby>";
		$xml_result .= '</document>';

		if ($discussion->getChildTopics()->count() > 0) 
		{
			foreach ($discussion->getChildTopics() as $child) {
				$xml_result .= $this->buildChildDiscussionTree($child, $defaultDateFormat);
			}
		}
		
		return $xml_result;
	}

	private function serviceMergeWordFilesWithJSONData($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$result_str = "<Results><Result ID='Status'>FAILED</Result></Results>"; 
		
		try{
    		$templatename = $post_xml->getElementsByTagName('templatename')->item(0)->nodeValue;
    		$docid = $post_xml->getElementsByTagName('docid')->item(0)->nodeValue;
    		$app = $this->getNodeValue($post_xml, 'AppID');
    		$filename = $post_xml->getElementsByTagName('filename')->item(0)->nodeValue;
    		$attachtodoc =  $post_xml->getElementsByTagName('attachtodoc')->item(0)->nodeValue;
    		$uploadername = $post_xml->getElementsByTagName('uploadername')->item(0)->nodeValue;
    		$jsonstr = $post_xml->getElementsByTagName('jsonstr')->item(0)->nodeValue;
    
    		if (empty($templatename)) {
    			throw $this->createNotFoundException('Unable to find template name');
    		}
    
    		if (empty($docid)) {
    			throw $this->createNotFoundException('Unable to find doc id ');
    		}
    
    		if (empty($filename)) {
    			throw $this->createNotFoundException('Unable to find filename ');
    		}
    
    		$em = $this->getDoctrine()->getManager();
    		$template = $em->getRepository('DocovaBundle:FileTemplates')->getTemplateByName($templatename);
    		if ( empty($template)){
    			throw $this->createNotFoundException('Unable to find template object with name '.$templatename);
    		}
    
    		$templatefilename = $template->getFileName();
    		$fulltemplatepath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR."Templates".DIRECTORY_SEPARATOR.md5($templatefilename);
    
    		if (! file_exists($fulltemplatepath)) {
    					
    			throw $this->createNotFoundException('Unable to find template file on the filesystem'.$templatefilename);												
    		}
    
    		$PHPWord = new PHPWord();
    
    		//$document = $PHPWord->loadTemplate('/home/vagrant/projects/First Name.docx');
    		$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($fulltemplatepath);
    		//now get all fieldvalues from docu
    		$app = $this->docova->DocovaApplication(['appID' => $app]);
    		$app->setAttachmentsBasePath($this->UPLOAD_FILE_PATH);
    
    		$appId = $app->appID;
    		if (empty($appId)) {
    			throw $this->createNotFoundException('Unspecified application source ID.');
    		}
    		
    		try{
    			$jsonObj = json_decode ( $jsonstr);
    		}catch(\Exception $e){
    			throw $this->createNotFoundException('Unable to decode json ');
    		}
    		
    		foreach ($jsonObj as $name => $value){
    			$fldname = $name;
    			try {
    				$templateProcessor->setValue($fldname, $value);
    			}catch(\Exception $e){}
    			try {
    			    $templateProcessor->replaceBookmark($fldname, $value);
    			}catch(\Exception $e){}
    		}
    
    		$finalpath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$docid;
    		if (!is_dir($finalpath)) {
    			@mkdir($finalpath);
    		}
    
    		if ( $attachtodoc == "1"){
    			$temp_file = sys_get_temp_dir().DIRECTORY_SEPARATOR.$filename;
    			$finalpath = $temp_file;
    		}else{
    			$finalpath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$docid.DIRECTORY_SEPARATOR.md5($filename);
    		}
    
    		
    		$templateProcessor->saveAs($finalpath);
    
    		if ( $attachtodoc == "1"){
    		    $doc = $app->getDocument($docid);
    			$upfield = $doc->getFirstItem($uploadername."_filenames");
    			$upfield->EmbedObject ("EMBED_ATTACHMENT", "", $temp_file);
    
    			$fldata = filesize($temp_file);
    			$doc->save(false);
    
    			//remove temp file
    			@unlink ( $temp_file);
    		}else{
    			$fldata = filesize($finalpath);
    		}
    
    		$result_str = "<Results><Result ID='Status'>OK</Result><Result ID='Ret1'><![CDATA[".$this->generateUrl('docova_opendocfile', array('file_name' => $filename), true) . '?doc_id=' . $docid."]]></Result><Result ID='Ret2'>".$fldata."</Result></Results>"; 
		}catch(\Exception $e){
		    $result_str = "<Results><Result ID='Status'>FAILED</Result><Result ID='Ret1'><![CDATA[".$e->getMessage()."]]></Result></Results>";	    
		}
		
		$result_xml->loadXML($result_str);
		return $result_xml;

	}

	private function serviceDeleteWordMergeFile($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$result_str = "<Results><Result ID='Status'>FAILED</Result></Results>"; 
		$docid = $post_xml->getElementsByTagName('docid')->item(0)->nodeValue;
		$app = $this->getNodeValue($post_xml, 'AppID');
		$filename = $post_xml->getElementsByTagName('filename')->item(0)->nodeValue;
		

		if (empty($docid)) {
			throw $this->createNotFoundException('Unable to find Docid ');
		}

		if (empty($filename)) {
			throw $this->createNotFoundException('Unable to find filename ');
		}

		$finalpath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$docid.DIRECTORY_SEPARATOR.md5($filename);
		@unlink($finalpath);

		$em = $this->getDoctrine()->getManager();
		$file_record = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document' => $docid, 'File_Name' => $filename));
		if ( !empty($file_record)){
			$em->remove($file_record);
			$em->flush();
		}
		$result_str = "<Results><Result ID='Status'>OK</Result></Results>"; 
		$result_xml->loadXML($result_str);
		return $result_xml;
	}


	private function serviceWORDFILEMERGEALL($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$result_str = "<Results><Result ID='Status'>FAILED</Result></Results>"; 
		$templatename = $post_xml->getElementsByTagName('templatename')->item(0)->nodeValue;
		$docid = $post_xml->getElementsByTagName('docid')->item(0)->nodeValue;
		$app = $this->getNodeValue($post_xml, 'AppID');
		$filename = $post_xml->getElementsByTagName('filename')->item(0)->nodeValue;
		$attachtodoc =  $post_xml->getElementsByTagName('attachtodoc')->item(0)->nodeValue;
		$uploadername = $post_xml->getElementsByTagName('uploadername')->item(0)->nodeValue;

		if (empty($templatename)) {
			throw $this->createNotFoundException('Unable to find tempalte Name');
		}

		if (empty($docid)) {
			throw $this->createNotFoundException('Unable to find Docid ');
		}

		if (empty($filename)) {
			throw $this->createNotFoundException('Unable to find filename ');
		}

		$em = $this->getDoctrine()->getManager();
		$template = $em->getRepository('DocovaBundle:FileTemplates')->getTemplateByName($templatename);
		if ( empty($template)){
			throw $this->createNotFoundException('Unable to find template object with name '.$templatename);
		}

		$templatefilename = $template->getFileName();
		$fulltemplatepath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR."Templates".DIRECTORY_SEPARATOR.md5($templatefilename);

		if (! file_exists($fulltemplatepath)) {
					
			throw $this->createNotFoundException('Unable to find template file on the filesystem'.$templatefilename);												
		}

		$PHPWord = new PHPWord();
		//$document = $PHPWord->loadTemplate('/home/vagrant/projects/First Name.docx');
		$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($fulltemplatepath);
		//now get all fieldvalues from docu
		$app = $this->docova->DocovaApplication(['appID' => $app]);
		$app->setAttachmentsBasePath($this->UPLOAD_FILE_PATH);

		$appId = $app->appID;
		if (empty($appId)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}
		$doc = $app->getDocument($docid);
		if ( empty($doc))
			throw new \Exception('Could not retrieve document.');

		$fieldarr = $doc->getFields("*", true);
		foreach ($fieldarr as $field){
			$fldname = strtolower($field->name);
			try {
				$templateProcessor->setValue($fldname, htmlentities($field->toString()));
			}catch(\Exception $e){}
		}

		$finalpath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$docid;
		if (!is_dir($finalpath)) {
			@mkdir($finalpath);
		}

		if ( $attachtodoc == "1"){
			$temp_file = sys_get_temp_dir().DIRECTORY_SEPARATOR.$filename;
			$finalpath = $temp_file;
		}else{
			$finalpath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$docid.DIRECTORY_SEPARATOR.md5($filename);
		}
		
		$templateProcessor->saveAs($finalpath);

		if ( $attachtodoc == "1"){

			$upfield = $doc->getFirstItem($uploadername."_filenames");
			$upfield->EmbedObject ("EMBED_ATTACHMENT", "", $temp_file);

		
			$doc->save(false);
			$fldata = filesize($temp_file);
			//remove temp file
			@unlink($temp_file);
		}else{
			$fldata = filesize($finalpath);
		}

		$result_str = "<Results><Result ID='Status'>OK</Result><Result ID='Ret1'><![CDATA[".$this->generateUrl('docova_opendocfile', array('file_name' => $filename), true) . '?doc_id=' . $docid."]]></Result><Result ID='Ret2'>".$fldata."</Result></Results>"; 
		$result_xml->loadXML($result_str);
		return $result_xml;

	}

	/**
	 * Execute calculation function on column values
	 * 
	 * @param \DOMDocument $post_xml
	 * @throws \Exception
	 * @return \DOMDocument
	 */
	private function serviceDBCALCULATE($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$result_str = "<Results><Result ID='Status'>FAILED</Result></Results>"; 
		$appname = $post_xml->getElementsByTagName('appname')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$appentity =  $em->getRepository('DocovaBundle:Libraries')->getByName($appname);

		//we first look by name..if not found, we look by id...this 
		if (empty($appentity))
		{
			$appentity =  $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appname, 'Trash' => false, 'isApp' => true));
			if (empty($appentity)) {
				throw $this->createNotFoundException('Unable to find source application as '.$appname);
			}
		}
		$viewname = $post_xml->getElementsByTagName('viewname')->item(0)->nodeValue;
		$columnorfield = $post_xml->getElementsByTagName('column')->item(0)->nodeValue;
		$criteria = $post_xml->getElementsByTagName('criteria')->item(0)->nodeValue;
		$action = $post_xml->getElementsByTagName('function')->item(0)->nodeValue;		
		try{
			if (empty($action)) {
				throw new \Exception('Undefind calculation function.');
			}
			if (!empty($criteria)) {
				$criteria = json_decode($criteria);
			}
			$docova = $this->get('docova.objectmodel');
			$docova->setApplication($appentity);
			$output = $docova->dbCalculate($viewname, $action, $columnorfield, $criteria);
				
			$result_str = "<Results><Result ID='Status'>OK</Result><Result ID='Ret1'><![CDATA[".$output."]]></Result></Results>"; 
		}
		catch (\Exception $e) {
			$result_str = "<Results><Result ID='Status'>FAILED</Result><Result ID='Ret1'>".$e->getMessage()."</Result></Results>";
		}

		$result_xml->loadXML($result_str);
		return $result_xml;
	}

	private function serviceDBCOLUMN($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$result_str = "<Results><Result ID='Status'>FAILED</Result></Results>"; 
		$appname = $post_xml->getElementsByTagName('appname')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$appentity =  $em->getRepository('DocovaBundle:Libraries')->getByName($appname);


		//we first look my name..if not found, we look by id...this 
		if (empty($appentity)) {

			$appentity =  $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appname, 'Trash' => false));
			if (empty($appentity)) {
				throw $this->createNotFoundException('Unable to find source application with NAME = '.$appname);
			}
		}
		$viewname = $post_xml->getElementsByTagName('viewname')->item(0)->nodeValue;

		$key = $post_xml->getElementsByTagName('key')->item(0)->nodeValue;
		$columnorfield = $post_xml->getElementsByTagName('column')->item(0)->nodeValue;
		$delimiter = $post_xml->getElementsByTagName('delimiter')->item(0)->nodeValue;
		$delimiter = empty($delimiter) ? ";" : $delimiter;
		
		try{ 
			$docova = $this->get('docova.objectmodel');
			$docova->setApplication($appentity);
			$output = $docova->dbcolumn($viewname,  $columnorfield);
			$outxml = "";
			
			foreach ( $output as $lookupres){
					if ( $outxml == "")
						$outxml = $lookupres;
					else
						$outxml .= $delimiter.$lookupres;
			}
			
			$result_str = "<Results><Result ID='Status'>OK</Result><Result ID='Ret1'><![CDATA[".$outxml."]]></Result></Results>"; 

		}catch(\Exception $e){
			$result_str = "<Results><Result ID='Status'>FAILED</Result><Result ID='Ret1'>".$e->getMessage()."</Result></Results>"; 
		}
		
		$result_xml->loadXML($result_str);
		return $result_xml;

	}


	private function serviceDBLOOKUP($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$result_str = "<Results><Result ID='Status'>FAILED</Result></Results>"; 
		$appname = $post_xml->getElementsByTagName('appname')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$appentity =  $em->getRepository('DocovaBundle:Libraries')->getByName($appname);
		//we first look my name..if not found, we look by id...this 
		if (empty($appentity)) {

			$appentity =  $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appname, 'Trash' => false));
			if (empty($appentity)) {
				throw $this->createNotFoundException('Unable to find source application with NAME = '.$appname);
			}
		}
		$viewname = $post_xml->getElementsByTagName('viewname')->item(0)->nodeValue;
		

		$key = $post_xml->getElementsByTagName('key')->item(0)->nodeValue;
		$key = base64_decode($key);
		$key = explode(chr(31), $key);

		$columnorfield = $post_xml->getElementsByTagName('columnorfield')->item(0)->nodeValue;
		$returndocid = $post_xml->getElementsByTagName('returndocid')->item(0)->nodeValue;
		$delimiter = $post_xml->getElementsByTagName('delimiter')->item(0)->nodeValue;
		$delimiter = empty($delimiter) ? ";" : $delimiter;
		$flags = ($returndocid === "1" ? "[RETURNDOCUMENTUNIQUEID]" : "");
		
		try{ 
			$docova = $this->get('docova.objectmodel');
			$docova->setApplication($appentity);
			$output = $docova->dbLookup($viewname, $key, $columnorfield, $flags);
			
			if (is_null($output) ){
				$outxml = "";
			}
			else if ( is_string($output)){
				$outxml = $output;
			}else{
				$outxml = "";
				foreach ( $output as $lookupres){
						if ( $outxml == "")
							$outxml = $lookupres;
						else
							$outxml .= $lookupres.$delimiter;
				}
			}
			$result_str = "<Results><Result ID='Status'>OK</Result><Result ID='Ret1'>".$outxml."</Result></Results>"; 

		}catch(\Exception $e){
			$result_str = "<Results><Result ID='Status'>FAILED</Result><Result ID='Ret1'>".$e->getMessage()."</Result></Results>"; 
		}
		
		$result_xml->loadXML($result_str);
		return $result_xml;

	}

	/**
	 * Get Stub information of a document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceGETSTUBINFO($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document_id = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		if (!empty($document_id)) 
		{
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document_id, 'Archived' => false));
			if (!empty($document)) 
			{
				$child_node = $result_xml->createElement('Result', 'OK');
				$child_attr = $result_xml->createAttribute('ID');
				$child_attr->value = 'Status';
				$child_node->appendChild($child_attr);
				$root->appendChild($child_node);
				
				$child_node = $result_xml->createElement('Result');
				$child_attr = $result_xml->createAttribute('ID');
				$child_attr->value = 'Ret1';
				$child_node->appendChild($child_attr);
					
				$cdata_val = $result_xml->createCDATASection(($document->getModifier()) ? $document->getModifier()->getUserNameDnAbbreviated() : '');
				$sub_child = $result_xml->createElement('LastModifiedBy');
				$sub_child->appendChild($cdata_val);
				$child_node->appendChild($sub_child);
					
				$date_modified = ($document->getDateModified()) ? $document->getDateModified()->format('Y-m-d h:i:s A') : '';
				$sub_child = $result_xml->createElement('LastModifiedDate', $date_modified);
				$child_node->appendChild($sub_child);
					
				$cdata_val = $result_xml->createCDATASection($this->get('request_stack')->getCurrentRequest()->getHost());
				$sub_child = $result_xml->createElement('LastModifiedServer');
				$sub_child->appendChild($cdata_val);
				$child_node->appendChild($sub_child);
					
				$cdata_val = $result_xml->createCDATASection(($document->getLocked() && $document->getLockEditor()) ? $document->getLockEditor()->getUserNameDnAbbreviated()  :'');
				$sub_child = $result_xml->createElement('LockEditor');
				$sub_child->appendChild($cdata_val);
				$child_node->appendChild($sub_child);
					
				$sub_child = $result_xml->createElement('LockDate', ($document->getLocked() && $document->getDateModified()) ? $document->getDateModified()->format('Y-m-d h:i:s A') : '');
				$child_node->appendChild($sub_child);
					
				$cdata_val = $result_xml->createCDATASection($this->get('request_stack')->getCurrentRequest()->getHost());
				$sub_child = $result_xml->createElement('LockServer');
				$sub_child->appendChild($cdata_val);
				$child_node->appendChild($sub_child);
					
				$sub_child = $result_xml->createElement('IsReplicated', '0');
				$child_node->appendChild($sub_child);
					
				$sub_child = $result_xml->createElement('LockStatus', ($document->getLocked() === true) ? (($document->getLockEditor() == $this->user) ? '1' : '2') : '0');
				$child_node->appendChild($sub_child);
					
				$sub_child = $result_xml->createElement('IsLocked', ($document->getLocked() === true) ? '1' : '');
				$child_node->appendChild($sub_child);
				$sub_child = $result_xml->createElement('IsLockEditor', ($document->getLocked() === true && $document->getLockEditor()->getId() == $this->user->getId()) ? '1' : '');
				$child_node->appendChild($sub_child);
					
				$root->appendChild($child_node);
				return $result_xml;
			}
		}

		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$child_node = $result_xml->createElement('Result', 'FAILED');
		$child_node->appendChild($attrib);
		$root->appendChild($child_node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$child_node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		$child_node->appendChild($attrib);
		$root->appendChild($child_node);
		
		return $result_xml;
	}
	
	/**
	 * Lock document for current user
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceLOCK($post_xml)
	{
		$document_id = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		return $this->lockingService($document_id, true);
	}

	/**
	 * Unlock document for current user
	 *
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceUNLOCK($post_xml)
	{
		$document_id = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		return $this->lockingService($document_id, false);
	}
	
	/**
	 * Lock/Unlock a document for current user
	 * 
	 * @param integer $document_id
	 * @param boolean $is_lock
	 * @return \DOMDocument
	 */
	private function lockingService($document_id, $is_lock)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$err = "";
		if (!empty($document_id)) 
		{
			$security_check = new Miscellaneous($this->container);
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document_id, 'Archived' => false));
				
			if (!empty($document)) 
			{
				try {
					if ($document->getApplication())
					{
						$docova = new Docova($this->container);
						$docova_acl = $docova->DocovaAcl($document->getApplication());
						$docova = null;
						$can_edit = false;
						if ($docova_acl->isEditor())
							$can_edit = true;
						else {
							$can_edit = $docova_acl->isDocAuthor($document);
						}
						if ($can_edit === false)
						{
							$err = "User has insufficient access to EDIT this document!";
							throw new \Exception('Access Denied');
						}
					}
					$action = ($is_lock === true) ? 'LOCK' : 'UNLOCK';
					$document->setLocked($is_lock);
					$document->setLockEditor($action === 'LOCK' ? $this->user : null);
					$em->flush();
					$security_check->createDocumentLog($em, $action, $document);
					
					$child_node = $result_xml->createElement('Result', 'OK');
					$child_attr = $result_xml->createAttribute('ID');
					$child_attr->value = 'Status';
					$child_node->appendChild($child_attr);
					$root->appendChild($child_node);
					return $result_xml;
				}
				catch (\Exception $e) {
					//@TODO: log the error 
					unset($document);
				}
			}
		}
			
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$child_node = $result_xml->createElement('Result', 'FAILED');
		$child_node->appendChild($attrib);
		$root->appendChild($child_node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$child_node = $result_xml->createElement('Result', $err );
		$child_node->appendChild($attrib);
		$root->appendChild($child_node);
		
		return $result_xml;
	}
	
	/**
	 * Add related documents
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceADDRELATEDLINKS($post_xml)
	{
		$appended = false;
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		if (!empty($post_xml->getElementsByTagName('Unid')->length) && !empty($post_xml->getElementsByTagName('Unid')->item(0)->nodeValue))
		{
			try {
				$em = $this->getDoctrine()->getManager();
				
				$library = $em->find('DocovaBundle:Libraries', $post_xml->getElementsByTagName('LibraryKey')->item(0)->nodeValue);
				$folder  = $em->find('DocovaBundle:Folders', $post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue);
				if (!empty($library) && !empty($folder))
				{
					if (empty($post_xml->getElementsByTagName('isNewDoc')->item(0)->nodeValue))
					{
						$parent_document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $post_xml->getElementsByTagName('ParentDocID')->item(0)->nodeValue, 'Archived' => false));
						if (!empty($parent_document))
						{
							$security_check = new Miscellaneous($this->container);
							if (false === $security_check->canEditDocument($parent_document, true))
							{
								throw new AccessDeniedException();
							}
						
							foreach ($post_xml->getElementsByTagName('Unid') as $document)
							{
								$tmp_document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => trim($document->nodeValue), 'Archived' => false));
									
								if (!empty($tmp_document))
								{
									$relation_exists = $em->getRepository('DocovaBundle:RelatedDocuments')->findOneBy(array('Parent_Doc' => $parent_document, 'Related_Doc' => $tmp_document));
									if (empty($relation_exists))
									{
										$related_doc_obj = new RelatedDocuments();
										$related_doc_obj->setParentDoc($parent_document);
										$related_doc_obj->setRelatedDoc($tmp_document);
											
										$em->persist($related_doc_obj);
										$em->flush();
											
										$security_check->createDocumentLog($em, 'UPDATE', $parent_document, "Added link '".$tmp_document->getDocTitle()."' to Document Relationships section.");
									}
								}
								unset($related_doc_obj, $tmp_document, $relation_exists);
							}
						
							$child_node = $result_xml->createElement('Result', 'OK');
							$child_attr	= $result_xml->createAttribute('ID');
							$child_attr->value = 'Status';
							$child_node->appendChild($child_attr);
							$root->appendChild($child_node);
							$appended = true;
						}
					}
					else
					{
						$child_node = $result_xml->createElement('Result', 'OK');
						$child_attr	= $result_xml->createAttribute('ID');
						$child_attr->value = 'Status';
						$child_node->appendChild($child_attr);
						$root->appendChild($child_node);
					
						$child_node = $result_xml->createElement('Result');
						$child_attr = $result_xml->createAttribute('ID');
						$child_attr->value = 'Ret1';
						$child_node->appendChild($child_attr);
						$data = $root->appendChild($child_node)->appendChild($result_xml->createElement('documents'));
						$appended = array();
						$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
					
						foreach ($post_xml->getElementsByTagName('Unid') as $document)
						{
							if (!in_array($document->nodeValue, $appended)) {
								$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document->nodeValue, 'Archived' => false));
								if (!empty($document)) {
									$doc = $data->appendChild($result_xml->createElement('document'));
									$doc->appendChild($result_xml->createElement('relateddata'));
									//$doc->appendChild($result_xml->createElement('LibraryKey', $post_xml->getElementsByTagName('LibraryKey')->item(0)->nodeValue));
									$doc->appendChild($result_xml->createElement('librarykey', $document->getFolder()->getLibrary()->getId()));
									//$CData = $result_xml->createCDATASection($library->getLibraryTitle());
									$CData = $result_xml->createCDATASection($document->getFolder()->getLibrary()->getLibraryTitle());
									$child = $result_xml->createElement('libraryname');
									$child->appendChild($CData);
									$doc->appendChild($child);
									//$doc->appendChild($result_xml->createElement('FolderID', $post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue));
									$doc->appendChild($result_xml->createElement('folderid', $document->getFolder()->getId()));
									//$CData = $result_xml->createCDATASection($folder->getFolderName());
									$CData = $result_xml->createCDATASection($document->getFolder()->getFolderName());
									$child = $result_xml->createElement('foldername');
									$child->appendChild($CData);
									$doc->appendChild($child);
									$doc->appendChild($result_xml->createElement('parentdocid', $document->getId()));
									$doc->appendChild($result_xml->createElement('doctypekey', ''));
									$CData = $result_xml->createCDATASection($document->getDocTitle());
									$child = $result_xml->createElement('title');
									$child->appendChild($CData);
									$doc->appendChild($child);
									$CData = $result_xml->createCDATASection($document->getAuthor()->getUserNameDnAbbreviated());
									$child = $result_xml->createElement('author');
									$child->appendChild($CData);
									$doc->appendChild($child);
									$date = $document->getDateCreated();
									$CData = $result_xml->createCDATASection($document->getDateCreated()->format($defaultDateFormat));
									$child = $result_xml->createElement('datecreated');
									$child->appendChild($CData);
									$year = $result_xml->createAttribute('Y');
									$year->value = $date->format('Y');
									$month = $result_xml->createAttribute('M');
									$month->value = $date->format('m');
									$day = $result_xml->createAttribute('D');
									$day->value = $date->format('d');
									$weekday = $result_xml->createAttribute('W');
									$weekday->value = $date->format('w');
									$hours = $result_xml->createAttribute('H');
									$hours->value = $date->format('H');
									$minutes = $result_xml->createAttribute('MN');
									$minutes->value = $date->format('i');
									$seconds = $result_xml->createAttribute('S');
									$seconds->value = $date->format('s');
									$val = $result_xml->createAttribute('val');
									$val->value = strtotime($date->format('Y-m-d H:i:s'));
									$child->appendChild($year);
									$child->appendChild($month);
									$child->appendChild($day);
									$child->appendChild($weekday);
									$child->appendChild($hours);
									$child->appendChild($minutes);
									$child->appendChild($seconds);
									$child->appendChild($val);
									$doc->appendChild($child);
									$doc->appendChild($result_xml->createElement('version', $document->getDocVersion() . '.' . $document->getRevision()));
									$CData = $result_xml->createCDATASection($document->getDocStatus());
									$child = $result_xml->createElement('status');
									$child->appendChild($CData);
									$doc->appendChild($child);
									$CData = $result_xml->createCDATASection($document->getDescription());
									$child = $result_xml->createElement('description');
									$child->appendChild($CData);
									$doc->appendChild($child);
									$doc->appendChild($result_xml->createElement('docid', $document->getId()));
//									$doc->appendChild($result_xml->createElement('typeicon', '&#157;'));
									$doc->appendChild($result_xml->createElement('rectype', 'doc'));
									$doc->appendChild($result_xml->createElement('lastModifieddate', ($document->getDateModified()) ? $document->getDateModified()->format($defaultDateFormat . ' H:i:s') : ''));
									$doc->appendChild($result_xml->createElement('active', 1));
									$doc->appendChild($result_xml->createElement('selected', 0));
									$doc->appendChild($result_xml->createElement('lvInfo'));
									$doc->appendChild($result_xml->createElement('lvparentdockey'));
									$doc->appendChild($result_xml->createElement('lvparentdocid'));
									$doc->appendChild($result_xml->createElement('archivedparentdockey', $document->getId()));
									$doc->appendChild($result_xml->createElement('docisdeleted', $document->getTrash()));
										
									$appended[] = $document->getId();
								}
							}
						}
					}
				}
			}
			catch (\Exception $e) {
				$appended = !empty($appended) ? true : false;
			}
		}

		if (empty($appended))
		{
			unset($result_xml, $root);
			$result_xml = new \DOMDocument("1.0", "UTF-8");
			$root = $result_xml->appendChild($result_xml->createElement('Results'));
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$child_node = $result_xml->createElement('Result', 'FAILED');
			$child_node->appendChild($attrib);
			$root->appendChild($child_node);
				
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'ErrMsg';
			$child_node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
			$child_node->appendChild($attrib);
			$root->appendChild($child_node);
		}
		
		return $result_xml;
	}
	
	/**
	 * Check document access
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceCHECKACCESS($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document_id = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		if (!empty($document_id))
		{
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document_id, 'Archived' => false));
			if (!empty($document)) {
				$security_check = new Miscellaneous($this->container);
					
				if (true === $security_check->canReadDocument($document, true))
				{
					$child_node = $result_xml->createElement('Result', 'OK');
					$child_attr	= $result_xml->createAttribute('ID');
					$child_attr->value = 'Status';
					$child_node->appendChild($child_attr);
					$root->appendChild($child_node);
					
					unset($security_check, $em, $document);
					return $result_xml;
				}
			}
		}

		$child_node = $result_xml->createElement('Result', 'FAILED');
		$child_attr	= $result_xml->createAttribute('ID');
		$child_attr->value = 'Status';
		$child_node->appendChild($child_attr);
		$root->appendChild($child_node);
		
		return $result_xml;
	}


	private function serviceRUNAGENT($post_xml){
		$app = $post_xml->getElementsByTagName('AppID')->item(0)->nodeValue;
		$docid = $post_xml->getElementsByTagName('NoteUNID')->item(0)->nodeValue;
		$agent = $post_xml->getElementsByTagName('Name')->item(0)->nodeValue;
		$docid = !empty($docid) ? $docid : null;
		$app = $this->docova->DocovaApplication(['appID' => $app]);
		$appId = $app->appID;
		if (empty($appId)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}

		$docova_agent = $this->docova->DocovaAgent($app, $agent);
		$output = $docova_agent->run($docid);
		$output = (is_bool($output) && $output == "false" ? "0" : $output);
		
		$ct = "";
		if(strtolower(substr($output, 0, 13)) == "content-type:"){
		    $eolp = strpos($output, PHP_EOL);
		    if($eolp !== false){
		        $tmpstr = substr($output, 13, $eolp-13);
		        $dp = strpos($tmpstr, ";");
		        if($dp !== false){
		            $tmpstr = substr($tmpstr, 0, $dp-1);
		        }
		        $ct = $tmpstr;
		        $output = substr($output, $eolp+1);
		    }
		}
		
		if($ct == ""){
		    if($this->isValidXML($output)){
		        $ct = "text/xml";
		    }else{
		        $ct = "text/html";
		    }
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', $ct);
		$response->setContent($output);
		return $response;
	}
	
	/**
	 * Create bookmark
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceCREATEBOOKMARK($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$target_library = $post_xml->getElementsByTagName('LibraryID')->item(0)->nodeValue;
		$target_folder	= $post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue;
		$document_id = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$error = null;
		if (!empty($target_library) && !empty($target_folder) && !empty($document_id))
		{
			$em = $this->getDoctrine()->getManager();
			$target_library	= $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $target_library, 'Trash' => false));
			$target_folder = $em->find('DocovaBundle:Folders', $target_folder);
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document_id, 'Archived' => false));
			if (!empty($target_library) && !empty($target_folder) && !empty($document))
			{
				try {
					$exist = $em->getRepository('DocovaBundle:Bookmarks')->findOneBy(array('Document' => $document_id, 'Target_Folder' => $target_folder->getId()));
					if (empty($exist)) 
					{
						$security_check = new Miscellaneous($this->container);
						if (false === $security_check->canCreateDocument($target_folder))
						{
							throw new AccessDeniedException();
						}
	
						$bookmark = new Bookmarks();
						$bookmark->setCreatedBy($this->user);
						$bookmark->setDateCreated(new \DateTime());
						$bookmark->setDocument($document);
						$bookmark->setTargetFolder($target_folder);
					
						$em->persist($bookmark);
						$em->flush();
					
						$attrib = $result_xml->createAttribute('ID');
						$attrib->value = 'Status';
						$node = $result_xml->createElement('Result', 'OK');
						$node->appendChild($attrib);
						$root->appendChild($node);
					
						$attrib = $result_xml->createAttribute('ID');
						$attrib->value = 'Ret1';
						$node = $result_xml->createElement('Result', $bookmark->getId());
						$node->appendChild($attrib);
						$root->appendChild($node);
					
						unset($bookmark, $target_folder, $target_library, $document, $em);
						return $result_xml;
					}
					else {
						throw new \Exception('A bookmark of the document in selected folder already exists. You cannot duplicate bookmark.');
					}
				}
				catch (\Exception $e)
				{
					$error = $e->getMessage();
					unset($bookmark, $target_folder, $target_library, $document, $em);
				}
			}
		}

		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node = $result_xml->createElement('Result', 'FAILED');
		$node->appendChild($attrib);
		$root->appendChild($node);
	
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$errMsg = !empty($error) && false !== strpos($error, 'A bookmark of document in selected') ? $error : 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.';
		$node = $result_xml->createElement('Result', $errMsg);
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Get attachments url to compare
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceGETATTACHMENTURL($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$doc_ids = $post_xml->getElementsByTagName ( 'Unids' )->item ( 0 )->nodeValue;
		if (!empty ( $doc_ids )) 
		{
			try {
				$doc_ids = explode ( ',', $doc_ids );
				$em = $this->getDoctrine ()->getManager ();
				$documents = $first_doc_files = $second_doc_files = array ();
				for($x = 0; $x < 2; $x ++) 
				{
					$documents[] = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_ids[$x], 'Trash' => false, 'Archived' => false));
					if (empty($documents[$x])) {
						throw $this->createNotFoundException ('Unspecified document source ID = ' . $doc_ids[$x]);
					}
				}
				
				$file_extensions = $post_xml->getElementsByTagName('FileExtension')->item(0)->nodeValue;
				$file_extensions = explode(',', $file_extensions);
				if (! empty ( $file_extensions )) 
				{
					for($x = 0; $x < 2; $x++) 
					{
						if ($documents[$x]->getAttachments()->count () > 0) 
						{
							foreach ($documents[$x]->getAttachments() as $attachment) 
							{
								foreach ($file_extensions as $ext) 
								{
									if (strstr(strrev($attachment->getFileName()), '.', true) == strrev($ext)) 
									{
										if ($x == 0) {
											$first_doc_files[] = $attachment->getFileName();
										} else {
											$second_doc_files[] = $attachment->getFileName();
										}
									}
								}
							}
						}
					}
				}
				
				if (!empty($first_doc_files) && !empty($second_doc_files)) 
				{
					$newnode = $result_xml->createElement ( 'Result', 'OK' );
					$attrib = $result_xml->createAttribute ( 'ID' );
					$attrib->value = 'Status';
					$newnode->appendChild ( $attrib );
					$root->appendChild ( $newnode );
					
					$newnode = $result_xml->createElement ( 'Result' );
					$attrib = $result_xml->createAttribute ( 'ID' );
					$attrib->value = 'Ret1';
					$newnode->appendChild ( $attrib );
					if (count($first_doc_files) === 1) {
						$newnode->appendChild($result_xml->createElement('URL', $this->generateUrl('docova_opendocfile', array('file_name' => $first_doc_files[0]), true) . '?doc_id=' . $documents[0]->getId()));
					} else {
						$url = '';
						foreach ($first_doc_files as $file) 
						{
							$url .= $this->generateUrl('docova_opendocfile', array('file_name' => $file), true) . '?doc_id=' . $documents[0]->getId() . '*';
						}
						$url = substr_replace ($url, '', - 1);
						$newnode->appendChild ($result_xml->createElement('URL', $url));
						unset ($url);
					}
					$newnode->appendChild($result_xml->createElement('FILENAME', implode('*', $first_doc_files)));
					$root->appendChild ($newnode);

					$newnode = $result_xml->createElement('Result');
					$attrib = $result_xml->createAttribute('ID');
					$attrib->value = 'Ret2';
					$newnode->appendChild($attrib);
					if (count($second_doc_files) === 1) 
					{
						$newnode->appendChild($result_xml->createElement('URL', $this->generateUrl('docova_opendocfile', array ('file_name' => $second_doc_files[0]), true ) . '?doc_id=' . $documents[1]->getId()));
					} else {
						$url = '';
						foreach ($second_doc_files as $file) 
						{
							$url .= $this->generateUrl('docova_opendocfile', array('file_name' => $file), true) . '?doc_id=' . $documents[1]->getId() . '*';
						}
						$url = substr_replace($url, '', - 1);
						$newnode->appendChild($result_xml->createElement('URL', $url));
						unset($url);
					}
					$newnode->appendChild($result_xml->createElement('FILENAME', implode('*', $second_doc_files)));
					$root->appendChild($newnode);
				}
				else {
					$newnode = $result_xml->createElement ( 'Result', 'FAILED' );
					$attrib = $result_xml->createAttribute ( 'ID' );
					$attrib->value = 'Status';
					$newnode->appendChild ( $attrib );
					$root->appendChild ( $newnode );
						
					$newnode = $result_xml->createElement ( 'Result', 'No documents with a doc/docx extension were found in one or both of the selected documents' );
					$attrib = $result_xml->createAttribute ( 'ID' );
					$attrib->value = 'ErrMsg';
					$newnode->appendChild ( $attrib );
					$root->appendChild( $newnode );
				}
				
				return $result_xml;
			}
			catch (\Exception $e) {
				unset($documents, $file, $file_extensions, $first_doc_files, $second_doc_files);
			}
		}

		$newnode = $result_xml->createElement ( 'Result', 'FAILED' );
		$attrib = $result_xml->createAttribute ( 'ID' );
		$attrib->value = 'Status';
		$newnode->appendChild ( $attrib );
		$root->appendChild ( $newnode );
			
		$newnode = $result_xml->createElement ( 'Result', 'No documents with a ' . implode ( '/', $file_extensions ) . ' extension were found in one or both of the selected documents' );
		$attrib = $result_xml->createAttribute ( 'ID' );
		$attrib->value = 'ErrMsg';
		$newnode->appendChild ( $attrib );
		$root->appendChild ( $newnode );
		
		return $result_xml;
	}
	
	
	/**
	 * Returns a list of attachments from a specified folder
	 * FolderID of destination folder
	 * SelectionType - one of the following;
	 *	1 - Selected documents
	 *	2 - Latest released versions
	 *	3 - All released versions (exclude drafts)
	 *	4 - All versions (including drafts)
	 * SelectedDocs - only used for SelectionType = 1 a list of document unique ids in <DocID> elements
	 * IncludeExtensions - comma separated list of file extensions to include
	 * ExcludeExtensions - comma separated list of file extensions to exclude
	 * AppendVersionInfo - 1 to return version info as part of the file name
	 * IncludeThumbnails - 1 to include thumbnail files
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceGETATTACHMENTS($post_xml)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml->appendChild($xml->createElement('Results'));
		$folder = $post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue;
		$type	= $post_xml->getElementsByTagName('SelectionType')->item(0)->nodeValue;
		$message = null;
		if ((!empty($folder) && !empty($type)) || (!empty($type) && $type == "1")) 
		{
			try {
				$em = $this->getDoctrine()->getManager();
				$include = $post_xml->getElementsByTagName('IncludeExtensions')->item(0)->nodeValue;
				$exclude = $post_xml->getElementsByTagName('ExcludeExtensions')->item(0)->nodeValue;
				$append_version = $post_xml->getElementsByTagName('AppendVersionInfo')->item(0)->nodeValue;
//				$include_thumbnail = $post_xml->getElementsByTagName('IncludeThumbnails')->item(0)->nodeValue;
				switch ($type)
				{
					case 1:
						if (!empty($post_xml->getElementsByTagName('DocID')->length)) 
						{
							$documents = array();
							foreach ($post_xml->getElementsByTagName('DocID') as $item) {
								$documents[] = trim($item->nodeValue);
							}
							$attachments = $em->getRepository('DocovaBundle:AttachmentsDetails')->exportDocAttachments($documents, $include, $exclude);
						}
						break;
					case 2:
						$attachments = $em->getRepository('DocovaBundle:AttachmentsDetails')->exportAttachmentMetaData($folder, $include, $exclude, true);
						break;
					case 3:
						$attachments = $em->getRepository('DocovaBundle:AttachmentsDetails')->exportAttachmentMetaData($folder, $include, $exclude, true, true);
						break;
					case 4:
						$attachments = $em->getRepository('DocovaBundle:AttachmentsDetails')->exportAttachmentMetaData($folder, $include, $exclude, false, true);
						break;
				}
				
				$node = $xml->createElement('Result', 'OK');
				$attr = $xml->createAttribute('ID');
				$attr->value = 'Status';
				$node->appendChild($attr);
				$root->appendChild($node);
				$node = $xml->createElement('Result');
				$attr = $xml->createAttribute('ID');
				$attr->value = 'Ret1';
				$node->appendChild($attr);
				$filesnode = $xml->createElement('Files');
				if (!empty($attachments)) 
				{
					try {
						$filepath = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root') : $_SERVER['DOCUMENT_ROOT'];
						$filepath .= DIRECTORY_SEPARATOR . $this->get('assets.packages')->getUrl('upload/scripts/');
						foreach ($attachments as $key => $file)
						{
							$doctype = $file->getDocument()->getDocType();
							if ($doctype && $doctype->getFilterAttachmentScript() && file_exists($filepath . $doctype->getFilterAttachmentScript() . '.php')) 
							{
								$className = $doctype->getFilterAttachmentScript();
								include_once $filepath . $className . '.php';
								if (class_exists($className)) {
									$filterAttachments = new $className($em);
									if (true !== $filterAttachments->isValidAttachment($file)) {
										$attachments[$key] = null;
										unset($attachments[$key]);
									}
									$filterAttachments = null;
								}
								$className = null;
							}
						}
						$attachments = array_values($attachments);
						$filepath = null;
					}
					catch (\Exception $e) {
						$logger = $this->container->get('logger');
						$logger->error('filterAttachment.ERROR: Uncaught PHP exception '.$e->getFile(). ': "' .$e->getMessage(). '" on line ' . $e->getLine() . "\n\r{" . $e->getTraceAsString() . "}\n");
						$logger = null;
					}
					
					foreach ($attachments as $file) {
						$file_node = $xml->createElement('File');
						$newnode = $xml->createElement('FileName');
						$temp_filename = $file->getFileName();
						if (!empty($append_version)) 
						{
							$temp_filename = '(v_'.$file->getDocument()->getDocVersion().'.'.$file->getDocument()->getRevision().') '.$temp_filename;
						}
/*
						if (!empty($include_thumbnail)) 
						{
							$temp_filename .= '~dthmb.bmp';
						}
*/
						$cdata = $xml->createCDATASection($temp_filename);
						$newnode->appendChild($cdata);
						$file_node->appendChild($newnode);
						$newnode = $xml->createElement('URL', $this->generateUrl('docova_opendocfile', array('file_name' => $file->getFileName()), true).'?doc_id=' . $file->getDocument()->getId());
						$file_node->appendChild($newnode);
						$filesnode->appendChild($file_node);
					}
				}
				unset($attachments, $file, $cdata, $attr, $file_node, $newnode);
				$node->appendChild($filesnode);
				$root->appendChild($node);
					
				return $xml;
			}
			catch (\Exception $e) {
				$message = 'Operation was not complete, due to: '. $e->getMessage();
			}
		}
		else {
			$message = 'Source folder and/or export type selection is missed.';
		}

		$newnode = $xml->createElement('Result', 'FAILED');
		$attr = $xml->createAttribute('ID');
		$attr->value = 'Status';
		$newnode->appendChild($attr);
		$root->appendChild($newnode);
			
		$newnode = $xml->createElement('Result', $message);
		$attr = $xml->createAttribute('ID');
		$attr->value = 'ErrMsg';
		$newnode->appendChild($attr);
		$root->appendChild($newnode);
		
		return $xml;
	}
	
	/**
	 * Get URL of a document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceGETURL($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document_id = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$type = $post_xml->getElementsByTagName('Type')->item(0)->nodeValue;
		//@TODO: double check this works for type = BOOKMARKPARENT
		if (!empty($document_id))
		{
			if ($type === 'DLENEW' || $type === 'QUICKADD') {
				$document = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $document_id, 'Del' => false, 'Inactive' => 0));
				$doctype = trim($post_xml->getElementsByTagName('TypeKey')->item(0)->nodeValue);
			}
			else {
				$document = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document_id, 'Trash' => false, 'Archived' => false));
			}
			if (!empty($document))
			{
				$isAppDoc = $document->getApplication() ? true : false;
				if ($isAppDoc === true)
				{
					if (!$document->getProfileName())
					{
						$newnode = $result_xml->createElement('Result', 'OK');
						$attrib	 = $result_xml->createAttribute('ID');
						$attrib->value = 'Status';
						$newnode->appendChild($attrib);
						$root->appendChild($newnode);
						
						$newnode = $result_xml->createElement('Result');
						$attrib	 = $result_xml->createAttribute('ID');
						$attrib->value = 'Ret1';
						$newnode->appendChild($attrib);
						$CData = $result_xml->createCDATASection($this->generateUrl('docova_readappdocument', array('docid' => $document_id), true).'?OpenDocument&mode=window&ParentUNID='.$document->getApplication()->getId());
						$newnode->appendChild($CData);
						$root->appendChild($newnode);
							
						$document = $document_id = null;
						return $result_xml;
					}
				}
				else 
				{
					$security_check = new Miscellaneous($this->container);
					if ((($type === 'DLENEW' || $type === 'QUICKADD') && true === $security_check->canCreateDocument($document) && !empty($doctype)) || ($type == 'DLEEDIT' && true === $security_check->canEditDocument($document, true)) || ($type !== 'DLENEW' && $type !== 'QUICKADD' && $type !== 'DLEEDIT' && true === $security_check->canReadDocument($document, true)))
					{
						$newnode = $result_xml->createElement('Result', 'OK');
						$attrib	 = $result_xml->createAttribute('ID');
						$attrib->value = 'Status';
						$newnode->appendChild($attrib);
						$root->appendChild($newnode);
					
						$newnode = $result_xml->createElement('Result');
						$attrib	 = $result_xml->createAttribute('ID');
						$attrib->value = 'Ret1';
						$newnode->appendChild($attrib);
						if ($type === 'DLENEW') 
						{
							$CData = $result_xml->createCDATASection($this->generateUrl('docova_documentpage', array(), true) . '?OpenForm&ParentUNID='.$document->getId().'&typekey='.$doctype . '&mode=dle');
						}
						elseif ($type === 'DLEEDIT') 
						{
							$CData = $result_xml->createCDATASection($this->generateUrl('docova_editdocument', array('doc_id' => $document->getId()), true) . '?EditDocument&ParentUNID='.$document->getId() . '&mode=dle');
						}
						elseif ($type === 'QUICKADD') 
						{
							$CData = $result_xml->createCDATASection($this->generateUrl('docova_fileimport', array(), true) . '?OpenForm&ParentUNID='.$document->getId() . '&mode=dle');
						}
						else {
							$CData = $result_xml->createCDATASection($this->generateUrl('docova_readdocument', array('doc_id' => $document_id), true).'?OpenDocument&ParentUNID='.$document->getFolder()->getId());
						}
						$newnode->appendChild($CData);
						$root->appendChild($newnode);
						
						unset($document, $document_id, $security_check);
						return $result_xml;
					}
				}
			}
		}

		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node = $result_xml->createElement('Result', 'FAILED');
		$node->appendChild($attrib);
		$root->appendChild($node);
	
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Change doctype of a document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceCHANGEDOCTYPE($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$target_doctype = $post_xml->getElementsByTagName('TypeKey')->item(0)->nodeValue;
		if (!empty($target_doctype) && !empty($post_xml->getElementsByTagName('Unid')->length))
		{
			$changed = false;
			try {
				foreach ($post_xml->getElementsByTagName('Unid') as $doc) {
					$document_id = $doc->nodeValue;
					$em = $this->getDoctrine()->getManager();
					$target_doctype = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $target_doctype, 'Trash' => false));
					$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document_id, 'Trash' => false, 'Archived' => false));
					if (!empty($target_doctype) && !empty($document))
					{
//						$security_obj = new Miscellaneous($this->container);
//						if (true === $security_obj->canEditDocument($document, true))
//						{
							$document->setDocType($target_doctype);
							$document->setDocStatus(($target_doctype->getEnableLifecycle()) ? $target_doctype->getInitialStatus() : $target_doctype->getFinalStatus());
							$document->setStatusNo(($target_doctype->getEnableLifecycle()) ? 0 : 1);
							$em->flush();
							$changed = true;
//						}
//						unset($security_obj);
					}
				}
			}
			catch (\Exception $e) {
				$changed = false;
				//@TODO: log the error
				unset($em, $target_doctype, $document_id);
			}
			
			if ($changed === true) {

				$newnode = $result_xml->createElement('Result', 'OK');
				$attrib	 = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
				return $result_xml;
			}
		}

		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node = $result_xml->createElement('Result', 'FAILED');
		$node->appendChild($attrib);
		$root->appendChild($node);
	
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		$node->appendChild($attrib);
		$root->appendChild($node);

		return $result_xml;
	}
	
	/**
	 * Import email(s)
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceIMPORTEMAIL($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$folder_id = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		if (!empty($folder_id))
		{
			$em = $this->getDoctrine()->getManager();
			$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id, 'Del' => false, 'Inactive' => 0));
			if (!empty($folder))
			{
				$newnode = $result_xml->createElement('Result', 'OK');
				$attrib	 = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
				
				return $result_xml;
			}
		}

		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node = $result_xml->createElement('Result', 'FAILED');
		$node->appendChild($attrib);
		$root->appendChild($node);
	
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		$node->appendChild($attrib);
		$root->appendChild($node);

		return $result_xml;
	}
	
	/**
	 * Create new activity in a document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceCREATEACTIVITY($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$doc_id	= $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$folder_id = $post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue;
		if (!empty($doc_id))
		{
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id, 'Trash' => false, 'Archived' => false));
			if (!empty($document) && $document->getFolder()->getId() == $folder_id)
			{
				$send_to = array();
				$assigned_creator = false;
				if (!empty($post_xml->getElementsByTagName('sendto')->item(0)->nodeValue)) 
				{
					$send_to = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $post_xml->getElementsByTagName('sendto')->item(0)->nodeValue);
				}
				if (!empty($post_xml->getElementsByTagName('activitydocumentowner')->item(0)->nodeValue)) 
				{
					$send_to[] = $post_xml->getElementsByTagName('activitydocumentowner')->item(0)->nodeValue;
				}
				if (!empty($send_to))
				{
					$participants = array();
					for ($x = 0; $x < count($send_to); $x++) 
					{
						if (!empty($send_to[$x]) && false !== $user = $this->findUserAndCreate(trim($send_to[$x]))) {
							$participants[] = $user;
							unset($user);
						}
						elseif (!empty($send_to[$x]) && false !== $members = $this->fetchGroupMembers(trim($send_to[$x]), true)) {
							foreach ($members as $user) {
								$participants[] = $user;
							}
							unset($user);
						}
					}
					
					$acknowledge = $reqresponse = false;
					$obligation = trim($post_xml->getElementsByTagName('activityobligation')->item(0)->nodeValue);
					$subject = trim($post_xml->getElementsByTagName('subject')->item(0)->nodeValue);
					$subject = !empty($subject) ? $subject : trim($post_xml->getElementsByTagName('activitytype')->item(0)->nodeValue);
					if ($obligation == 1) {
						$acknowledge = true;
					}
					elseif ($obligation == 2) {
						$reqresponse = true;
					}
					$messaging = trim($post_xml->getElementsByTagName('activitysendmessage')->item(0)->nodeValue) ? true : false;
					unset($send_to);
					try {
						$created = false;
						foreach ($participants as $user)
						{
							if (!empty($user)) 
							{
								$document_activity = new DocumentActivities();
								$document_activity->setActivityAction($post_xml->getElementsByTagName('activitytype')->item(0)->nodeValue);
								$document_activity->setAckRequired($acknowledge);
								$document_activity->setRequestResponse($reqresponse);
								$document_activity->setSubject($post_xml->getElementsByTagName('subject')->item(0)->nodeValue);
								$document_activity->setMessage($post_xml->getElementsByTagName('body')->item(0)->nodeValue);
								$document_activity->setCreatedBy($this->user);
								$document_activity->setStatusDate(new \DateTime());
								$document_activity->setDocument($document);
								$document_activity->setAssignee($user);
								$document_activity->setSendEmailNotification($messaging);
								$em->persist($document_activity);
								$em->flush();
								$created = true;
								if ($user->getId() === $this->user->getId()) {
									$assigned_creator = true;
								}
								unset($document_activity, $user);
							}
						}
						
						if ($created === true) 
						{
							if ($messaging === true) 
							{
								$content = <<<BODY
A new {$post_xml->getElementsByTagName('activitytype')->item(0)->nodeValue}  Activity has been created and you have been named as a recipient/assignee.

  [docLink]
BODY;
								$recipients = array();
								foreach ($participants as $user) {
									$recipients[] = $user->getUserMail();
								}
								unset($user);
								$message = array(
									'subject' => $subject ,
									'content' => MailController::parseTokens($this->container, $content, true, $document)
								);
								MailController::parseMessageAndSend($message, $this->container, $recipients);
							}

							$newnode = $result_xml->createElement('Result', 'OK');
							$attrib	 = $result_xml->createAttribute('ID');
							$attrib->value = 'Status';
							$newnode->appendChild($attrib);
							$root->appendChild($newnode);
							if ($assigned_creator === true) 
							{
								$newnode = $result_xml->createElement('Ret1', '1');
								$attrib	 = $result_xml->createAttribute('ID');
								$attrib->value = 'AssignedCreator';
								$newnode->appendChild($attrib);
								$root->appendChild($newnode);
							}
							
							return $result_xml;
						}
					}
					catch (\Exception $e) {
						unset($doc_id, $document, $folder_id, $em);
					}
				}
			}
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
	
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		$node->appendChild($attrib);
		$root->appendChild($node);

		return $result_xml;
	}
	
	/**
	 * Update activity to complete it by the assignee
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceUPDATEACTIVITY($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$doc_id	= $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$activity_id = $post_xml->getElementsByTagName('activityDocID')->item(0)->nodeValue;
		if (!empty($doc_id) && !empty($activity_id))
		{
			$em = $this->getDoctrine()->getManager();
			$activity = $em->getRepository('DocovaBundle:DocumentActivities')->findOneBy(array('id' => $activity_id, 'document' => $doc_id));
			if (!empty($activity))
			{
				try {
					if (!empty($post_xml->getElementsByTagName('activityacknowledged')->item(0)->nodeValue))
					{
						$activity->setAcknowledged(true);
						$activity->setIsComplete(true);
						$activity->setStatusDate(new \DateTime());
					}
					elseif (!empty($post_xml->getElementsByTagName('activityresponse')->item(0)->nodeValue))
					{
						$activity->setIsComplete(true);
						$activity->setStatusDate(new \DateTime());
						$activity->setResponse($post_xml->getElementsByTagName('activityresponse')->item(0)->nodeValue);
					}
					else {
						$activity->setIsComplete(true);
						$activity->setStatusDate(new \DateTime());
					}
						
					$em->flush();
					
					if ($activity->getSendEmailNotification()) 
					{
						$content = <<<BODY
This {$activity->getActivityAction()} Activity that you created has been updated.
						
  [docLink]
BODY;
						$recipients = array();
						$document = $activity->getDocument();
						$allActivities = $em->getRepository('DocovaBundle:DocumentActivities')->getAllSameActivities($document->getId(), $activity->getActivityAction());
						foreach ($allActivities as $act) {
							$recipients[] = $act->getAssignee()->getUserMail();
						}
						$message = array(
							'subject' => $activity->getSubject() ,
							'content' => MailController::parseTokens($this->container, $content, true, $document)
						);
						unset($document, $allActivities, $act);
						MailController::parseMessageAndSend($message, $this->container, $recipients);
					}
					$newnode = $result_xml->createElement('Result', 'OK');
					$attrib	 = $result_xml->createAttribute('ID');
					$attrib->value = 'Status';
					$newnode->appendChild($attrib);
					$root->appendChild($newnode);
					
					return $result_xml;
				}
				catch (\Exception $e) {
					//var_dump($e->getMessage()); @TODO: change it with logging error
					unset($activity, $doc_id, $activity_id, $em);
				}
			}
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}

	/**
	 * Delete activity by the assignee
	 *
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceDELETEACTIVITY($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$doc_id	= $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$activity_id = $post_xml->getElementsByTagName('activityDocID')->item(0)->nodeValue;
		if (!empty($doc_id) && !empty($activity_id))
		{
			$em = $this->getDoctrine()->getManager();
			$activity = $em->getRepository('DocovaBundle:DocumentActivities')->findOneBy(array('id' => $activity_id, 'document' => $doc_id));
			if (!empty($activity))
			{
				try {
					$activity->setIsComplete(true);
					$activity->setStatusDate(new \DateTime());
	
					$em->flush();
					$newnode = $result_xml->createElement('Result', 'OK');
					$attrib	 = $result_xml->createAttribute('ID');
					$attrib->value = 'Status';
					$newnode->appendChild($attrib);
					$root->appendChild($newnode);
						
					return $result_xml;
				}
				catch (\Exception $e) {
					//@TODO: log the error
					unset($activity, $doc_id, $activity_id, $em);
				}
			}
		}
	
		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
	
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		$node->appendChild($attrib);
		$root->appendChild($node);
	
		return $result_xml;
	}

	/**
	 * Create new version of a document (minor, major or new version)
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceNEWVERSION($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document_id	= $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$version_type	= $post_xml->getElementsByTagName('VersionType')->item(0)->nodeValue;
		$del_files		= $post_xml->getElementsByTagName('Delfile')->item(0)->nodeValue;
		$del_files		= !empty($del_files) ? explode('*', $del_files) : [];
		if (!empty($document_id) && !empty($version_type)) 
		{
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document_id, 'Trash' => false, 'Archived' => false));
			$parent_id = (!empty($document) && $document->getParentDocument()) ? $document->getParentDocument()->getId() : $document_id;
			$latest_version = $em->getRepository('DocovaBundle:Documents')->getLatestVersion($parent_id);
			if (!empty($document) && !empty($latest_version)) 
			{
				try {
					$cloned_doc = $this->pasteFolderDocuments($document->getFolder(), array($document), true, true, true, $del_files);
					$properties = $document->getApplication() ? $document->getAppForm()->getFormProperties() : $document->getDocType();
					if (!empty($cloned_doc) && count($cloned_doc) > 0) 
					{
						$cloned_doc = $cloned_doc[0];
						if ($document->getApplication()) {
							$cloned_doc->setDocStatus($properties->getInitialStatus());
						}
						else {
							$cloned_doc->setDocStatus($properties->getInitialStatus());
						}
						$cloned_doc->setStatusNo(0);
						if ($document->getParentDocument()) {
							$cloned_doc->setParentDocument($document->getParentDocument());
						}
						else {
							$cloned_doc->setParentDocument($document);
						}
						switch ($version_type) 
						{
							case 'MINOR':
								$version = explode('.', $latest_version->getDocVersion());
								$version[0] = empty($version[0]) ? '0' : $version[0];
								$version[1] = strval(intval($version[1]) + 1);
								$cloned_doc->setDocVersion(implode('.', $version));
								$cloned_doc->setRevision(0);
							break;
							case 'MAJOR':
								$version = explode('.', $latest_version->getDocVersion());
								$version[0] = strval(intval($version[0]) + 1);
								$version[1] = '0';
								$cloned_doc->setDocVersion(implode('.', $version));
								$cloned_doc->setRevision(0);
							break;
							default:
								$cloned_doc->setDocVersion($latest_version->getDocVersion());
								$cloned_doc->setRevision($latest_version->getRevision() + 1);
							break;
						}
						$em->flush();
						$log_obj = new Miscellaneous($this->container);
						$log_obj->createDocumentLog($em, 'CREATE', $cloned_doc, 'Create as version '.$cloned_doc->getDocVersion().'.'.$cloned_doc->getRevision().'.');
						$log_obj->createDocumentLog($em, 'UPDATE', $cloned_doc, 'Locked document for editing.');
						
						if ($properties->getStrictVersioning() || $properties->getRestrictDrafts()) 
						{
							$previous_versions = $em->getRepository('DocovaBundle:Documents')->getPreviousVersions($cloned_doc->getId(), $cloned_doc->getParentDocument()->getId());
							if (!empty($previous_versions) && count($previous_versions) > 0) 
							{
								$discarded_status = $properties->getDiscardedStatus();
								foreach ($previous_versions as $doc)
								{
									if ($doc->getStatusNo() == 0) 
									{
										$doc->setDocStatus($discarded_status);
										$doc->setStatusNo(5);
										if ($doc->getDocSteps()->count() > 0) 
										{
											foreach ($doc->getDocSteps() as $doc_step) {
												$em->remove($doc_step);
											}
										}
										$em->flush();
										$log_obj->createDocumentLog($em, 'UPDATE', $doc, 'Superseded by version '.$cloned_doc->getDocVersion().'.'.$cloned_doc->getRevision());
									}
								}
								$previous_versions = $log_obj = $discarded_status = $doc_step = null;
							}
						}
						
						if ($properties->getEnableVersions() && $properties->getUpdateBookmarks()) 
						{
							$bookmarks = $em->getRepository('DocovaBundle:Bookmarks')->findBy(array('Document' => $document->getId()));
							if (!empty($bookmarks)) 
							{
								foreach ($bookmarks as $b) {
									$b->setDocument($cloned_doc);
								}
								$em->flush();
							}
						}
						$bookmarks = $b = null;

						if (!$document->getApplication() && in_array('RDOC', $properties->getContentSections()))
						{
							$tmp_xml = $em->getRepository('DocovaBundle:DocumentTypes')->containsSubform($document->getDocType()->getId(), 'Related Documents');
							if (!empty($tmp_xml['Properties_XML']))
							{
								$properties = new \DOMDocument();
								$properties->loadXML($tmp_xml['Properties_XML']);
								$rtype = $properties->getElementsByTagName('RelatedDocType')->item(0)->nodeValue;
								if ($rtype == 'A' || ($rtype == 'S' && in_array($document->getDocType()->getId(), explode(',', $properties->getElementsByTagName('DocTypeKey')->item(0)->nodeValue))))
								{
									$related_docs = $em->getRepository('DocovaBundle:RelatedDocuments')->findBy(array('Parent_Doc' => $document->getId(), 'Trash' => false));
									if (!empty($related_docs) && count($related_docs))
									{
										foreach ($related_docs as $doc)
										{
											$cloned_reldoc = clone $doc;
											$cloned_reldoc->setParentDoc($cloned_doc);
											$em->persist($cloned_reldoc);
										}
										$em->flush();
									}
								}
								$properties = $rtype = $related_docs = $cloned_reldoc = $doc = null;
							}
						}
						if ($document->getApplication())
						{
							$appid = $document->getApplication()->getId();
							$views = $em->getRepository('DocovaBundle:AppViews')->findBy(array('application' => $appid));
							$view_handler = new ViewManipulation($this->docova, $appid, $this->global_settings);
							if (!empty($views))
							{
								$dateformat = $this->global_settings->getDefaultDateFormat();
								$display_names = $this->global_settings->getUserDisplayDefault();
								$repository = $em->getRepository('DocovaBundle:Documents');
								$twig = $this->container->get('twig');
								$doc_values = $repository->getDocFieldValues($cloned_doc->getId(), $dateformat, $display_names, true);
							
								foreach ($views as $v)
								{
									try {
										$view_handler->indexDocument2($cloned_doc->getId(), $doc_values, $appid, $v->getId(), $v->getViewPerspective(), $twig, false, $v->getConvertedQuery());
									}
									catch (\Exception $e) {
									}
								}
							}
						}
						
						$document = null;

						$newnode = $result_xml->createElement('Result', 'OK');
						$attrib	 = $result_xml->createAttribute('ID');
						$attrib->value = 'Status';
						$newnode->appendChild($attrib);
						$root->appendChild($newnode);
						
						$newnode = $result_xml->createElement('Result', $cloned_doc->getId());
						$attrib	 = $result_xml->createAttribute('ID');
						$attrib->value = 'Ret1';
						$newnode->appendChild($attrib);
						$root->appendChild($newnode);

						$newnode = $result_xml->createElement('Result', $cloned_doc->getDocVersion().'.'.$cloned_doc->getRevision());
						$attrib	 = $result_xml->createAttribute('ID');
						$attrib->value = 'Ret2';
						$newnode->appendChild($attrib);
						$root->appendChild($newnode);

						return $result_xml;
					}
				}
				catch (\Exception $e) {
					//@TODO: log the error(s)
					var_export($e->getMessage());
					unset($document, $latest_version, $document_id, $version_type);
				}
			}
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		//$node = $result_xml->createElement('Result', $e->getMessage());
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Check if current user has edit access on the selected document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceMAYUSEREDIT($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$security_context = $this->container->get('security.authorization_checker');
		$document = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Trash' => false, 'Archived' => false));
		$editable = '';
		
		if ($document->getApplication()) {
			$docova = new Docova($this->container);
			$docovaAcl = $docova->DocovaAcl($document->getApplication());
			$docova = null;

			if ($docovaAcl->isSuperAdmin()) {
				$editable = 1;
			}
			elseif ($docovaAcl->isManager() || $docovaAcl->isDesigner() || $docovaAcl->isEditor()) {
				$editable = 1;
			}
			else {
				$editable = $docovaAcl->isDocAuthor($document);
			}
		}
		else {
			if ($security_context->isGranted('ROLE_ADMIN') || $security_context->isGranted('MASTER', $document->getFolder()->getLibrary())) {
				$editable = 1;
				unset($security_context);
			}
			elseif (!empty($document)) 
			{
				try {
					if (!empty($document)) {
						$security_check = new Miscellaneous($this->container);
						if (true === $security_check->canEditDocument($document, true)) 
						{
							if (!($document->getDocType()->getEnableLifecycle() && $document->getStatusNo() == 1)) {
								$editable = 1;
							}
						}
					}
				}
				catch (\Exception $e) {
					//@TODO: log the error(s)
					unset($document);
				}
			}
		}

		$node = $result_xml->createElement('Result', 'OK');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Ret1';
		$node = $result_xml->createElement('Result', $editable);
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Create a comment for Log Comment Section
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceLOGCOMMENT($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$comment_text = $post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Trash' => false, 'Archived' => false));
		
		if (!empty($document) && !empty($comment_text))
		{
			try {
				$ctype = trim($post_xml->getElementsByTagName('CommentType')->item(0)->nodeValue) == 'LC' ? 2 : 1;
				$comment = new DocumentComments();
				$comment->setCreatedBy($this->user);
				$comment->setDateCreated(new \DateTime());
				$comment->setDocument($document);
				$comment->setComment($comment_text);
				$comment->setCommentType($ctype);
				$em->persist($comment);
				$em->flush();

				$newnode = $result_xml->createElement('Result', 'OK');
				$attrib	 = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);

				return $result_xml;
			}
			catch (\Exception $e) {
				//@TODO: log the occured issue
				unset($document);
			}
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		//$node = $result_xml->createElement('Result', $e->getMessage());
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Create a comment for Log Comment Section
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceREMOVECOMMENT($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$comment = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		
		try {
			$em = $this->getDoctrine()->getManager();
			$comment = $em->getRepository('DocovaBundle:DocumentComments')->find($comment);
			if (!empty($comment)) 
			{
				$em->remove($comment);
				$em->flush();

				$newnode = $result_xml->createElement('Result', 'OK');
				$attrib	 = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);

				return $result_xml;
			}
		}
		catch (\Exception $e) {
			//@TODO: log the exception
			unset($comment);
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Could not remove comment document(s). Please contact your systems administrator.');
		//$node = $result_xml->createElement('Result', $e->getMessage());
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Check out file(s) in the document and create log
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceCHECKOUT($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		try {
			$em = $this->getDoctrine()->getManager();
			$filenames = '';
			foreach ($post_xml->getElementsByTagName('file') as $item) {
				$attachment = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document' => $document, 'File_Name' => $item->getElementsByTagName('filename')->item(0)->nodeValue, 'Checked_Out' => false));
				if (!empty($attachment)) 
				{
					$attachment->setCheckedOut(true);
					$attachment->setCheckedOutPath($item->getElementsByTagName('path')->item(0)->nodeValue);
					$attachment->setDateCheckedOut(new \DateTime());
					$attachment->setCheckedOutBy($this->user);
					$filenames .= $attachment->getFileName();
				}
			}

			if (!empty($filenames)) {
				$log_obj = new Miscellaneous($this->container);
				$log_obj->createDocumentLog($em, 'UPDATE', $attachment->getDocument(), 'Checked out file(s): '.$filenames);
				unset($log_obj);
			}
			$em->flush();

			$newnode = $result_xml->createElement('Result', 'OK');
			$attrib	 = $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);

			return $result_xml;
		}	
		catch (\Exception $e) {
			//@TODO: log the possible exception and message.
			unset($attachment);
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		//$node = $result_xml->createElement('Result', 'Could not remove comment document(s). Please contact your systems administrator.');
		$node = $result_xml->createElement('Result', $e->getMessage());
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Check In file(s) for the selected document and create log
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceCHECKIN($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		try {
			$em = $this->getDoctrine()->getManager();
			$filenames = '';
			foreach ($post_xml->getElementsByTagName('file') as $item) {
				$attachment = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document' => $document, 'File_Name' => $item->getElementsByTagName('filename')->item(0)->nodeValue, 'Checked_Out' => true, 'Checked_Out_By' => $this->user->getId()));
				if (!empty($attachment)) 
				{
					$attachment->setCheckedOut(false);
					$attachment->setCheckedOutPath(null);
					$attachment->setDateCheckedOut(null);
					$attachment->setCheckedOutBy(null);
					$attachment->setFileDate(new \DateTime());
					$filenames .= $attachment->getFileName();
				}
			}

			if (!empty($filenames)) {
				$log_obj = new Miscellaneous($this->container);
				$log_obj->createDocumentLog($em, 'UPDATE', $attachment->getDocument(), 'Checked in file(s): '.$filenames);
				unset($log_obj);
			}
			$em->flush();

			$newnode = $result_xml->createElement('Result', 'OK');
			$attrib	 = $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);

			return $result_xml;
		}
		catch (\Exception $e) {
			//@TODO: log the possible exception and message.
			unset($attachment);
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		//$node = $result_xml->createElement('Result', 'Could not remove comment document(s). Please contact your systems administrator.');
		$node = $result_xml->createElement('Result', $e->getMessage());
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}

	

	private function serviceNEWDOC($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$formname = $this->getNodeValue($post_xml, 'FormName');


		//if formname is DocComment then we create that
		if ( $formname == 'd_DocComment'){
			return $this->createCommentDoc ( $post_xml);
		}


		$app = $this->getNodeValue($post_xml, 'AppID');
		$parentDoc = $this->getNodeValue($post_xml, 'ParentUNID');
		
		$app = $this->docova->DocovaApplication(['appID' => $app]);
		$appId = $app->appID;
		$fields = $post_xml->getElementsByTagName('Fields')->item(0)->childNodes;
		if (empty($appId)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}
		try {
			$modified = false;
			$miscfunctions = new MiscFunctions();
			$unid = $miscfunctions->generateGuid(); 
			$doc = $app->createDocument(array('formname'=> $formname, 'unid'=>$unid));

			if (!empty($parentDoc)) {
				$parentDoc = $app->getDocument($parentDoc);
				//Javad >> for now if parent doc not found it doesn't stop the code until in future we make a decision
				if (!empty($parentDoc)) {
					$doc->makeResponse($parentDoc);
					$modified = true;
				}
			}
			
			if ($fields->length > 0) 
			{
				$doc->syncFieldsFromXML($fields);
				$modified = true;
				/*foreach ($fields as $item) 
				{
					$fldname = $item->tagName;
					$fldtype = $item->getAttribute('dt');
					$fldspecialtype = $item->getAttribute('specialType');
					$fldval =$this->getConvertedValue($item);


					$type = 0;
					if ($fldtype == "date")
						$type = 1;
					elseif ($fldtype == "number")
						$type = 4;
					elseif ( $fldtype == "names")
						$type = 3;

					$doc->setField($fldname, $fldval, $type);
				}*/
			}
			
			if ($modified === true) {
				$res = $doc->save(true);
			}
			
			if ( $res )
				$result_str = "<Results><Result ID='Status'>OK</Result><Result ID='Ret1'>".$doc->getId()."</Result></Results>"; 
			else
				throw new \Exception('No document was created!');
			
		}catch (\Exception $e) {
			$msg = $e->getMessage();
			$result_str = "<Results><Result ID='Status'>FAILED</Result><Result ID='ErrMsg'>".$msg."</Result></Results>";
		}
		$result_xml->loadXML($result_str);
		return $result_xml;
	}



	/**
	 * Sets document field(s) value
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */

	private function serviceSETDOCFIELDS($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$unid_val = $this->getNodeValue($post_xml, 'Unid');
		$app = $this->getNodeValue($post_xml, 'AppID');
		$formname = $this->getNodeValue($post_xml, 'FormName');
		$parentDoc = $this->getNodeValue($post_xml, 'ParentUNID');
		if(strpos($unid_val, ":")) 
			$sep = ":";
		elseif(strpos($unid_val, ",")) 
			$sep = ",";
		else
			$sep = " ";

		$app = $this->docova->DocovaApplication(['appID' => $app]);
		$appId = $app->appID;
		$fields = $post_xml->getElementsByTagName('Fields')->item(0)->childNodes;

		$fieldsarray = array();
		for ( $j =0; $j < $fields->length; $j++){
			$fieldsarray [] = $fields->item($j);
		}
		if (empty($appId)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}

		$saveerrors = 0;
		$unidarray = explode($sep, $unid_val);
		foreach( $unidarray as $unid)
		{
			try {
				    $modified = false;
					$doc = $app->getDocument($unid);
					if ( empty($doc)){
						throw new \Exception('Could not retrieve document.');
					}
					if ( !empty($formname)){
						$doc->setField("form", $formname );
					}
					if (!empty($parentDoc)) {
						$parentDoc = $app->getDocument($parentDoc);
						//Javad >> for now if parent doc not found it doesn't stop the code until in future we make a decision
						if (!empty($parentDoc)) {
							$doc->makeResponse($parentDoc);
							$modified = true;
						}
					}

					if ($fields->length > 0) 
					{
						$doc->syncFieldsFromXML($fieldsarray);
						$modified = true;
					}
					
					if ($modified === true) {
					    if(!$doc->save(true)){
					        $saveerrors ++;
					        $msg = "Unable to save field changes.";					        
					}
					}else{
						$saveerrors ++;
						$msg = "No changes found to save.";
					}
					
				}catch (\Exception $e) {
					$saveerrors ++;
					$msg = $e->getMessage();
				}
		}
		if($saveerrors > 0){
			$result_str = "<Results><Result ID='Status'>FAILED</Result><Result ID='ErrMsg'>".(string)$saveerrors." documents were not updated. The last error message recorded was: $msg</Result></Results>";
		}else{
			$result_str = "<Results><Result ID='Status'>OK</Result><Result ID='Ret1'>OK</Result></Results>";			
		}
		$result_xml->loadXML($result_str);
		return $result_xml;
	}

	
	/**
	 * Get document field(s) value
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */

	private function serviceGETDOCFIELDS($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		
		$date_format = $this->global_settings->getDefaultDateFormat();
		$date_format = str_ireplace(array('MM', 'DD', 'YYYY'), array('m','d','Y'), $date_format).' H:i:s';
		$appId = $this->getNodeValue($post_xml, 'AppID');
		$unid = $this->getNodeValue($post_xml, 'Unid');
		$fields = $this->getNodeValue($post_xml, 'Fields');
		$subaction = $this->getNodeValue($post_xml, 'SubAction');

		$app = $this->docova->DocovaApplication(['appID' => $appId]);
		if (empty($app)) {
			throw $this->createNotFoundException('Application not found with specified ID.');
		}

		try {
			$output = '';
			$doc = $app->getDocument($unid);
			if ( empty($doc)){
				$em = $this->getDoctrine()->getManager();
				//see if its comments doc
				$comment = $em->getRepository('DocovaBundle:AppDocComments')->find($unid);

				if ( !empty ($comment) ){
					//retrieve values for comments
					return $this->getCommentsFields($post_xml);
				}else{

					throw new \Exception('Could not retrieve document.');
				}
			}

			if($subaction == "NAMESONLY"){
				$fieldarr = $doc->getFields(explode(",", $fields), true);
				foreach ($fieldarr as $fieldname){
					$output = empty($output) ? $fieldname : ",".$fieldname;
				}
			}else{	
				$output = "<Fields>";
				$fieldarr = $doc->getFields(explode(",", $fields), true);
				foreach ($fieldarr as $field){
					$output .= $field->toXML($date_format);
				}
				$output .= "</Fields>";
			}

			$result_str = "<Results><Result ID='Status'>OK</Result><Result ID='Ret1'>" .$output. "</Result></Results>"; 
			
		}catch (\Exception $e) {
			$msg = $e->getMessage();
			$result_str = "<Results><Result ID='Status'>FAILED</Result><Result ID='ErrMsg'>".$msg."</Result></Results>";
		}
		$result_xml->loadXML($result_str);
		return $result_xml;
	}

	private function serviceGETFORMFIELDS($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$app = $this->getNodeValue($post_xml, 'AppID');
		$form = $this->getNodeValue($post_xml, 'FormName');
		$output = $result_str = '';
		
		$app = $this->docova->DocovaApplication(['appID' => $app]);
		$appId = $app->appID;
		if (empty($appId)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}
		
		try {
			$em = $this->getDoctrine()->getManager();
			$form = $em->getRepository('DocovaBundle:AppForms')->findByNameAlias($form, $app->appID);
			if (empty($form)) {
				throw new \Exception('Invalid form name.');
			}
			
			$fields = $em->getRepository('DocovaBundle:DesignElements')->getFormFields($form->getId(), $app->appID, 'array');
			if (!empty($fields)) {
				foreach ($fields as $field) {
					$output .= $field['fieldName'].',';
				}
				$output = substr_replace($output, '', -1);
			}
			$result_str = "<Results><Result ID='Status'>OK</Result><Result ID='Ret1'>" .$output. "</Result></Results>"; 
		}catch (\Exception $e) {
			$msg = $e->getMessage();
			$result_str = "<Results><Result ID='Status'>FAILED</Result><Result ID='ErrMsg'>".$msg."</Result></Results>";
		}
		$result_xml->loadXML($result_str);
		return $result_xml;
	}
	
	/**
	 * Get document field(s) value
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function getCommentsFields($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$fields = $post_xml->getElementsByTagName('Fields')->item(0)->nodeValue;
		$msg = 'Unable to find document field.';

		$em = $this->getDoctrine()->getManager();
		$fldvalues = $em->getRepository('DocovaBundle:AppDocComments')->getCommentFieldValues($document);


		try {
			if (empty($fields)) 
			{
				throw new \Exception('Could not retrieve document fields.');
			}
			$fields = explode(',', strtolower($fields));
			
			if (count(array_intersect(array('parentdockey','threadindex','commentindex','avataricon','commentor','datecreated','comment', 'dockey', 'form'), $fields)) > 0)
			{
				//$comment = $em->getRepository('DocovaBundle:AppDocComments')->find($document);
				$node = $result_xml->createElement('Result', 'OK');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$node->appendChild($attrib);
				$root->appendChild($node);
				$node = $result_xml->createElement('Result');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Ret1';
				$node->appendChild($attrib);
				$fieldnode = $result_xml->createElement('Fields');
				foreach ($fields as $fieldname)
				{
					switch (trim($fieldname))
					{
						case 'parentdockey':
							$value = $fldvalues['id'];
							$dt = 'text';
							break;
						case 'threadindex':
							$value =  $fldvalues['threadIndex'];
							$dt = 'number';
							break;
						case 'commentindex':
							$value =  $fldvalues['commentIndex'];
							$dt = 'number';
							break;
						case 'avataricon':
							$value =  $fldvalues['avatar'];
							$dt = 'text';
							break;
						case 'commentor':
							$value = $fldvalues['userNameDnAbbreviated'];
							$dt = 'text';
							break;
						case 'datecreated':
							$value = $fldvalues['dateCreated']->format('m/d/Y h:i:s A');
							$dt = 'date';
							break;
						case 'comment':
							$value = $fldvalues['comment'];
							$dt = 'text';
							break;
						case 'dockey':
							$value = $document;
							$dt = 'text';
							break;
						case 'form':
							$value = "d_DocComment";
							$dt = 'text';
							break;
						default:
							$value = '';
							$dt = 'text';
							break;
					}
					$newnode = $result_xml->createElement(trim($fieldname));
					$attrib = $result_xml->createAttribute('dt');
					$attrib->value = $dt;
					$cdata = $result_xml->createCDATASection($value);
					$newnode->appendChild($cdata);
					$newnode->appendChild($attrib);
					$fieldnode->appendChild($newnode);
				}
				$node->appendChild($fieldnode);
				$root->appendChild($node);
				return $result_xml;
			}
			else 
			{
				$field_values = $em->getRepository('DocovaBundle:FormTextValues')->getDocumentFieldsValue($document, $fields);
				if (!empty($field_values)) 
				{
					$node = $result_xml->createElement('Result', 'OK');
					$attrib = $result_xml->createAttribute('ID');
					$attrib->value = 'Status';
					$node->appendChild($attrib);
					$root->appendChild($node);
					$node = $result_xml->createElement('Result');
					$attrib = $result_xml->createAttribute('ID');
					$attrib->value = 'Ret1';
					$node->appendChild($attrib);
					foreach ($field_values as $value)
					{
						$newnode = $result_xml->createElement($value['Field_Name']);
						$attrib = $result_xml->createAttribute('dt');
						switch ($value['Field_Type']) 
						{
							case 0:
							case 2:
								$attrib->value = 'text';
								break;
							case 1:
								$attrib->value = 'date';
								break;
							case 3:
								$attrib->value = 'names';
								break;
						}
						if ($value['Field_Type'] == 2) 
						{
							$fvalue = $em->getRepository('DocovaBundle:ListFieldOptions')->findOneBy(array('Field' => $value['id'], 'Opt_Value' => $value['Field_Value']));
							if (!empty($fvalue)) 
							{
								$fvalue = $fvalue->getDisplayOption();
							}
							else {
								$fvalue = '';
							}
						}
						else {
							$fvalue = $value['Field_Value'];
						}
						$cdata = $result_xml->createCDATASection($fvalue);
						$newnode->appendChild($cdata);
						$newnode->appendChild($attrib);
						$node->appendChild($newnode);
					}
					$root->appendChild($node);
					
					return $result_xml;
				}
			}
		}
		catch (\Exception $e) {
			$msg = $e->getMessage();
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		//$node = $result_xml->createElement('Result', 'Could not remove comment document(s). Please contact your systems administrator.');
		$node = $result_xml->createElement('Result', $msg);
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Get a field value base on selected criteria
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceGETFIELDVALUES($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$library = !empty($post_xml->getElementsByTagName('Library')->length) && trim($post_xml->getElementsByTagName('Library')->item(0)->nodeValue) ? trim($post_xml->getElementsByTagName('Library')->item(0)->nodeValue) : null;
		$folder	 = !empty($post_xml->getElementsByTagName('FolderID')->length) && trim($post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue) ? trim($post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue) : null;
		$document= !empty($post_xml->getElementsByTagName('Unid')->length) && trim($post_xml->getElementsByTagName('Unid')->item(0)->nodeValue) ? trim($post_xml->getElementsByTagName('Unid')->item(0)->nodeValue) : null;
		$field	 = trim($post_xml->getElementsByTagName('ReturnField')->item(0)->nodeValue);
		$field	 = explode(',', $field);
		$criteria= $returned_values = array();
		
		if ($post_xml->getElementsByTagName('Field')->length > 0) 
		{
			foreach ($post_xml->getElementsByTagName('Field') as $item) 
			{
				$criteria[$item->getAttribute('name')] = $item->nodeValue;
			}
		}
		$em = $this->getDoctrine()->getManager();
		try {
			if (empty($criteria) || empty($field)) 
			{
				throw new \Exception('No criteria and/or return field name is entered.');
			}
			
			for ($x = 0; $x < count($field); $x++) {
				$result = $em->getRepository('DocovaBundle:FormTextValues')->getFieldValuesByCriteria($criteria, $field[$x], $library, $folder, $document);
				if (!empty($result))
				{
					foreach ($result as $value) {
						$returned_values[$value['Document']][$field[$x]] = array('Field_Value' => $value['Field_Value'], 'Field_Type' => $value['Field_Type']);
					}
				}
			}
			$node = $result_xml->createElement('Result', 'OK');
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$node->appendChild($attrib);
			$root->appendChild($node);
			if (!empty($returned_values))
			{
				$node = $result_xml->createElement('Result');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Ret1';
				$node->appendChild($attrib);
				if (count($field) == 1) {
					foreach ($returned_values as $key => $values)
					{
						if ($values[$field[0]]['Field_Type'] == 1) {
							$value = date_create_from_format('d/m/Y', $values[$field[0]]['Field_Value']);
							$value = $value->format(str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat()));
						}
						else {
							$value = $values[$field[0]]['Field_Value'];
						}
						$newnode = $result_xml->createElement($field[0]);
						$cdata = $result_xml->createCDATASection($value);
						$newnode->appendChild($cdata);
						$node->appendChild($newnode);
					}
					$root->appendChild($node);
				}
				else {
					foreach ($returned_values as $values) 
					{
						$child = $result_xml->createElement('Fields');
						foreach ($values as $key => $v)
						{
							if ($v['Field_Type'] == 1) {
								$value = date_create_from_format('d/m/Y', $v['Field_Value']);
								$value = $value->format(str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat()));
							}
							else {
								$value = $v['Field_Value'];
							}
							$newnode = $result_xml->createElement($key);
							$cdata = $result_xml->createCDATASection($value);
							$newnode->appendChild($cdata);
							$child->appendChild($newnode);
						}
						$node->appendChild($child);
					}
					$root->appendChild($node);
				}
			}
			
			return $result_xml;
		}
		catch (\Exception $e) {
			//@TODO: May need to log the occured error(s)
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		//$node = $result_xml->createElement('Result', 'Could not remove comment document(s). Please contact your systems administrator.');
		$node = $result_xml->createElement('Result', $e->getMessage() . $e->getLine());
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Add a URL as linked to a document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceADDURLLINK($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('ParentDocID')->item(0)->nodeValue;
		try {
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Archived' => false, 'Trash' => false));
			if (!empty($document)) 
			{
				$linked_url = new RelatedLinks();
				$linked_url->setLinkUrl($post_xml->getElementsByTagName('LinkURL')->item(0)->nodeValue);
				$linked_url->setDescription($post_xml->getElementsByTagName('LinkDesc')->item(0)->nodeValue);
				$linked_url->setLinkType($post_xml->getElementsByTagName('LinkType')->item(0)->nodeValue == 'URL' ? true : false);
				$linked_url->setCreatedBy($this->user);
				$linked_url->setDateCreated(new \DateTime());
				$linked_url->setDocument($document);
				$em->persist($linked_url);
				$em->flush();
				unset($linked_url);
				
				$log_obj = new Miscellaneous($this->container);
				$log_obj->createDocumentLog($em, 'UPDATE', $document, 'Added URL link \''.$post_xml->getElementsByTagName('LinkDesc')->item(0)->nodeValue.'\' to Document Relationships section.');
				unset($log_obj);

				$node = $result_xml->createElement('Result', 'OK');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$node->appendChild($attrib);
				$root->appendChild($node);
				
				return $result_xml;
			}
			else {
				throw new \Exception('Unpecified source Document.');
			}
		}
		catch (\Exception $e) {	}
		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		//$node = $result_xml->createElement('Result', 'Could not remove comment document(s). Please contact your systems administrator.');
		$node = $result_xml->createElement('Result', $e->getMessage());
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Get created documetn position when pagination is enabled in a document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceGETDOCPOSITION($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$docname = $post_xml->getElementsByTagName('Subject')->item(0)->nodeValue;
		$folder	 = $post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$security_check = new Miscellaneous($this->container);
		
		$documents = $em->getRepository('DocovaBundle:Documents')->getFolderDocuments($folder);
		if (!empty($documents)) 
		{
			$position = 0;
			foreach ($documents as $document)
			{
				if (true === $security_check->canReadDocument($document, true)) 
				{
					$position++;
					if ($document->getDocTitle() == $docname) 
					{
						break;
					}
				}
			}
			
			if ($position) 
			{
				$node = $result_xml->createElement('Result', 'OK');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$node->appendChild($attrib);
				$root->appendChild($node);
				$node = $result_xml->createElement('Result', $position);
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Ret1';
				$node->appendChild($attrib);
				$root->appendChild($node);
				
				return $result_xml;
			}
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'Unable to locate position of the document.');
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}

	/**
	 * Get profile document
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */

	private function serviceGETPROFILEDOC($post) 
	{
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$app = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
		$profilename = $post->getElementsByTagName('ProfileName')->item(0)->nodeValue;
		$profilekey = $post->getElementsByTagName('ProfileKey')->item(0)->nodeValue;
		$profilekey = empty($profilekey) ? null : $profilekey;
		$app = $this->docova->DocovaApplication(['appID' => $app]);
		$appId = $app->appID;
		if (empty($appId)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}

		$profile = $app->getProfileDocument($profilename, $profilekey);
		
		if (!empty($profile)) 
		{
			$node = $result_xml->createElement('Result', 'OK');
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$node->appendChild($attrib);
			$root->appendChild($node);
			$node = $result_xml->createElement('Result');
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'Ret1';
			$node->appendChild($attrib);
			$newnode = $result_xml->createElement('Document');
			$child = $result_xml->createElement('Unid', $profile->getId());
			$newnode->appendChild($child);
			$node->appendChild($newnode);
			$root->appendChild($node);

			return $result_xml;
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'No profile documents were found');
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Get profile document(s)
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function serviceGETPROFILEDOCCOLLECTION($post)
	{
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$profile = $post->getElementsByTagName('ProfileName')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$profiles = $em->getRepository('DocovaBundle:Documents')->getProfileDocuments($profile);
		
		if (!empty($profiles)) 
		{
			$node = $result_xml->createElement('Result', 'OK');
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$node->appendChild($attrib);
			$root->appendChild($node);
			$node = $result_xml->createElement('Result');
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'Ret1';
			$node->appendChild($attrib);
			foreach ($profiles as $profile)
			{
				$newnode = $result_xml->createElement('Document');
				$child = $result_xml->createElement('Unid', $profile->getId());
				$newnode->appendChild($child);
				$node->appendChild($newnode);
			}
			$root->appendChild($node);

			return $result_xml;
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', 'No profile documents were found');
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}
	
	/**
	 * Create new doc comment
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function createCommentDoc($post)
	{
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$form = $post->getElementsByTagName('FormName')->item(0)->nodeValue;
		$app = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$msg = 'Unable to complete insert action.';
		
		if ($form == 'd_DocComment')
		{
			$document = $post->getElementsByTagName('ParentDocKey')->item(0)->nodeValue;
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Trash' => false, 'application' => $app));
			if (!empty($document))
			{
				try {
					$comment = new AppDocComments();
					$comment->setThreadIndex(intval($post->getElementsByTagName('ThreadIndex')->item(0)->nodeValue));
					$comment->setCommentIndex(intval($post->getElementsByTagName('CommentIndex')->item(0)->nodeValue));
					$comment->setCreatedBy($this->user);
					$comment->setDateCreated(new \DateTime());
					$comment->setComment(trim($post->getElementsByTagName('Comment')->item(0)->nodeValue));
					$comment->setAvatar(trim($post->getElementsByTagName('AvatarIcon')->item(0)->nodeValue));
					$comment->setDocument($document);
					$em->persist($comment);
					$em->flush();
					
					$node = $result_xml->createElement('Result', 'OK');
					$attrib = $result_xml->createAttribute('ID');
					$attrib->value = 'Status';
					$node->appendChild($attrib);
					$root->appendChild($node);
					$node = $result_xml->createElement('Result', $comment->getId());
					$attrib = $result_xml->createAttribute('ID');
					$attrib->value = 'Ret1';
					$node->appendChild($attrib);
					$root->appendChild($node);
					
					return $result_xml;
				}
				catch (\Exception $e) {
					$msg = $e->getMessage();
				}
			}
		}

		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib	= $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', $msg);
		$node->appendChild($attrib);
		$root->appendChild($node);
		
		return $result_xml;
	}

	/**
	 * Set profile field
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
 	private function serviceSETPROFILEFIELDS($post)
 	{
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$profile = $post->getElementsByTagName('ProfileName')->item(0)->nodeValue;
		$app = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
		$pkey = $post->getElementsByTagName('ProfileKey')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$fields = $post->getElementsByTagName('Fields')->item(0)->childNodes;
		$count = 0;
		$msg = '';
		$application =  $this->docova->DocovaApplication(['appID' => $app]);
		
		$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formName' => $profile, 'application' => $app, 'trash' => false));
	  	if (empty($form)) 
		{
			$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formAlias' => $profile, 'application' => $app, 'trash' => false));
			
		}
	
	  	try {
		   if (!empty($fields) && count($fields))
		   {
		   		$field_values = array();
			    foreach ($fields as $f)
			    {
			    	$field_name = $f->nodeName;
			    	$field_val = $f->nodeValue;
			    	$field_name = strtolower($field_name);
					$field_values[$field_name] = $field_val;
		    	}
	
		    	if ($application->setProfileFields($profile, $field_values, $pkey, $form))
			      	$count++;
		    
				if ($count) {
					$node = $result_xml->createElement('Result', 'OK');
					$attrib = $result_xml->createAttribute('ID');
					$attrib->value = 'Status';
					$node->appendChild($attrib);
					$root->appendChild($node);
					$node = $result_xml->createElement('Result', $count);
					$attrib = $result_xml->createAttribute('ID');
					$attrib->value = 'Ret1';
					$node->appendChild($attrib);
					$root->appendChild($node);
	
					return $result_xml;
		    	} else {
		     		throw new \Exception('Oops, none of profile fields were updated.');
		    	}
		   } else {
		    	throw new \Exception('No profile field is provided.');
		   }
		} catch (\Exception $e) {
		   $msg = $e->getMessage();
		}
	
		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
	
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', $msg);
		$node->appendChild($attrib);
		$root->appendChild($node);
		  
		return $result_xml;
	}
	
	/**
	 * Evaluate a twig script
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function serviceEVALUATEFORMULA($post)
	{
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		
		$formulanodes = $post->getElementsByTagName('Formula');
		$docid = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
		$appid = $post->getElementsByTagName('AppID')->item(0)->nodeValue;		
		$msg = '';
		$outputvalues = array();
		
		try {
			$em = $this->getDoctrine()->getManager();
			$document = null;
			if (!empty($docid))
			{
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $docid, 'Trash' => false));
			}
			
			if(empty($document)){
			    $appobj = $this->docova->DocovaApplication([], $appid);
			    $document = $this->docova->DocovaDocument($appobj);
			}
			
			foreach($formulanodes as $formulanode){
				$output = '';				
				$script = $formulanode->nodeValue;
				if (!empty($script))
				{
					$template = $this->get('twig')->createTemplate('{{ f_SetUser(user) }}{{ f_SetApplication(application) }}{{ f_SetDocument(document) }}{% docovascript "output:json" %} '.$script.'{% enddocovascript %}');
					$output = $template->render(array(
							'document' => $document,
							'application' => $appid,
							'user' => $this->user
					));
						
					$output = json_decode($output);
				}
				array_push($outputvalues, $output);				
			}
			
			$node = $result_xml->createElement('Result', 'OK');
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$node->appendChild($attrib);
			$root->appendChild($node);
			
			$retcount = 0;
			foreach($outputvalues as $outputvalue){					
				$retcount ++;
				$node = $result_xml->createElement('Result');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Ret'.(string)$retcount;
				$node->appendChild($attrib);				
				$elemnode = $result_xml->createElement('Element');			
				$attrib = $result_xml->createAttribute('dt');
				$dt = "text";
				$tempval = $outputvalue;
				switch(gettype($tempval)){
				    case "string":
				        break;
				    case "boolean":
				    case "integer":
				    case "double":
				        $dt = "number";
				        $tempval = (string)$tempval;
				        break;  
				    case "object":
				        if(is_a($tempval, 'DateTime')){
				            $dt = "date";
				            $tempval = $tempval->format('Y-m-d H:i:s');
				        }elseif(is_a($tempval, "stdClass") && property_exists($tempval, "date")){
				            $dt = "date";
				            $tempval = $tempval->date;
				        }else{
				           $tempval = print_r($tempval);     
				        }
				        break;
				    case "null":
				        $tempval = '';
				        break;
				    default:
				        $tempval = (string)$tempval;
				        break;
				}
				$attrib->value = $dt;
				$elemnode->appendChild($attrib);
				$cdata = $result_xml->createCDATASection($tempval);
				$elemnode->appendChild($cdata);
				$node->appendChild($elemnode);
				$root->appendChild($node);
			}

			return $result_xml;
		}
		catch (\Exception $e) {
			$msg = $e->getMessage();
		}
	
		$node = $result_xml->createElement('Result', 'FAILED');
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'Status';
		$node->appendChild($attrib);
		$root->appendChild($node);
	
		$attrib = $result_xml->createAttribute('ID');
		$attrib->value = 'ErrMsg';
		$node = $result_xml->createElement('Result', $msg);
		$node->appendChild($attrib);
		$root->appendChild($node);
		  
		return $result_xml;
	}
 
	
	/**
	 * Render Subform pages according to document types and document values
	 * 
	 * @param string $form_name
	 * @param \Doctrine\Common\Collections\ArrayCollection $element_objects
	 * @param boolean $can_edit
	 * @param \Docova\DocovaBundle\Entity\DocumentTypes $doctype_obj
	 * @param \Docova\DocovaBundle\Entity\Folders $folder_obj
	 * @param \DomDocument $xml_properties
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $read_mode
	 * @return string
	 */
	private function renderSubform($form_name, $element_objects, $can_edit, $doctype_obj, $folder_obj, $is_mobile = false, $xml_properties = null, $document = null, $read_mode = false)
	{
		$output = array();
		$properties = array('folder' => $folder_obj);

		for ($x = 0; $x < count($element_objects); $x++) {
//			$fields_property = $this->getDoctrine()->getRepository('DocovaBundle:DocTypeFieldsProperty')->getDocTypeAllProperties($doctype_obj, $element_objects[$x]);
			preg_match('/^mcsf_dliuploader\d$/i', $element_objects[$x]->getFieldName(), $matches);
			
			if ($element_objects[$x]->getFieldType() === 2)
			{
				$options = array();
				if (!$element_objects[$x]->getSelectQuery())
				{
					if ($element_objects[$x]->getOptions()->count() > 0) {
						foreach ($element_objects[$x]->getOptions() as $opt)
						{
							$options[] = array ('Opt_Value' => $opt->getOptValue(), 'Display_Option' => $opt->getDisplayOption());
						}
					}
				}

				$output[$element_objects[$x]->getFieldName()]['Options'] = $options;
			}

			if (!empty($document)) {
				$delimiter = $element_objects[$x]->getMultiSeparator() ? explode(' ', $element_objects[$x]->getMultiSeparator()) : array(',');
				$delimiter = $delimiter[0];
				if (empty($matches[0]) || $element_objects[$x]->getFieldType() != 5) {
					if ($element_objects[$x]->getFieldType() === 1) {
						$field_value = $this->getDoctrine()->getRepository('DocovaBundle:FormDateTimeValues')->findBy(array('Document'=>$document->getId(), 'Field'=>$element_objects[$x], 'trash'=>false));
						if (!empty($field_value))
						{
							$tmp_value = '';
							foreach ($field_value as $value) {
								$tmp_value .= $value->getFieldValue()->format(str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat())) . $delimiter;
							}
							$tmp_value = !empty($tmp_value) ? substr_replace($tmp_value, '', -(strlen($delimiter))) : '';
							$output[$element_objects[$x]->getFieldName()]['Value'] = $tmp_value;
						}
					}
					elseif ($element_objects[$x]->getFieldType() == 3) {
						$tmp_value = '';
						$field_value = $this->getDoctrine()->getRepository('DocovaBundle:FormNameValues')->findBy(array('Document'=>$document->getId(), 'Field'=>$element_objects[$x], 'trash'=>false));
						if (!empty($field_value))
						{
							foreach ($field_value as $value) {
								if ($this->global_settings->getUserDisplayDefault())
									$tmp_value .= $value->getFieldValue()->getUserProfile()->getDisplayName();
								else 
									$tmp_value .= $value->getFieldValue()->getUserNameDnAbbreviated();
								
								$tmp_value .= $delimiter;
							}
						}
						$field_value = $this->getDoctrine()->getRepository('DocovaBundle:FormGroupValues')->findBy(array('Document'=>$document->getId(), 'Field'=>$element_objects[$x], 'trash'=>false));
						if (!empty($field_value))
						{
							foreach ($field_value as $value) {
								$tmp_value .= $value->getFieldValue()->getDisplayName() . $delimiter;
							}
						}
						$tmp_value = !empty($tmp_value) ? substr_replace($tmp_value, '', -(strlen($delimiter))) : '';
						$output[$element_objects[$x]->getFieldName()]['Value'] = $tmp_value;
					}
					elseif ($element_objects[$x]->getFieldType() == 4) {
						$tmp_value = '';
						$field_value = $this->getDoctrine()->getRepository('DocovaBundle:FormNumericValues')->findBy(array('Document'=>$document->getId(), 'Field'=>$element_objects[$x], 'Trash'=>false));
						if (!empty($field_value))
						{
							foreach ($field_value as $value) {
								$tmp_value .= strval($value->getFieldValue()) . $delimiter;
							}
							$tmp_value = !empty($tmp_value) ? substr_replace($tmp_value, '', -(strlen($delimiter))) : '';
						}
						$output[$element_objects[$x]->getFieldName()]['Value'] = $tmp_value;
					}
					else {
						$tmp_value = '';
						$field_value = $this->getDoctrine()->getRepository('DocovaBundle:FormTextValues')->findBy(array('Document'=>$document->getId(), 'Field'=>$element_objects[$x], 'Trash'=>false));
						if (!empty($field_value))
						{
							foreach ($field_value as $value) {
								$tmp_value .= $value->getFieldValue() . $delimiter;
							}
							$tmp_value = !empty($tmp_value) ? substr_replace($tmp_value, '', -(strlen($delimiter))) : '';
						}
						$output[$element_objects[$x]->getFieldName()]['Value'] = $tmp_value;
					}
				}
				else {
					$field_value = $this->getDoctrine()->getRepository('DocovaBundle:AttachmentsDetails')->findBy(array('Document'=>$document->getId(), 'Field'=>$element_objects[$x]->getId()));
					$output[$element_objects[$x]->getFieldName()]['Value'] = (!empty($field_value)) ? $field_value : '';
				}
			}
			
			$output[$element_objects[$x]->getFieldName()]['Field'] = $element_objects[$x];
			if(empty($output[$element_objects[$x]->getFieldName()]['Value'])){
				$output[$element_objects[$x]->getFieldName()]['Value'] = '';								
			}
//			$output[$element_objects[$x]->getFieldName()]['Property'] = $fields_property;
		}
		if (!empty($xml_properties))
		{
			if (!empty($xml_properties->getElementsByTagName('Interface')->item(0)->childNodes)) 
			{
				$interface_properties = $xml_properties->getElementsByTagName('Interface')->item(0)->childNodes;
				foreach ($interface_properties as $node)
				{
					$properties[$node->nodeName] = $node->nodeValue;
				}
			}
			if (!empty($xml_properties->getElementsByTagName('MaxFiles')->length)) {
				$properties['MaxFiles'] = $xml_properties->getElementsByTagName('MaxFiles')->item(0)->nodeValue;
			}
			$properties['is_read'] = $read_mode;
			$properties['document'] = $document;
			$properties['can_edit'] = $can_edit;
		}

		$read_form_name = ($read_mode === true && true === file_exists('../src/Docova/DocovaBundle/Resources/views/Subform/'.$form_name.'-read.html.twig')) ? '-read' : '';

		$is_mobile = ($is_mobile === true) ? '_m' : '';
		return $this->renderView("DocovaBundle:Subform:".$form_name.$is_mobile.$read_form_name.".html.twig", array(
				'data' => $output,
				'properties' => $properties,
				'user' => $this->user,
				'settings' => $this->global_settings
		));
	}
	
	/**
	 * Move uploaded files to specific folder with created names
	 * 
	 * @param \Symfony\Component\HttpFoundation\FileBag $upload_file_obj
	 * @param array $file_names
	 * @param array $file_dates
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\DesignElements $field
	 * @param string $action
	 * @param array $rename_list
	 */
	private function moveUploadedFiles($upload_file_obj, $file_names, $file_dates, $document, $field = null, $action = 'INSERT', $rename_list = array())
	{

		if (empty($upload_file_obj)) {
			return false;
		}
		
		$error = $added = $edited = array();
		$uploaded = 0;
		$files_list = $upload_file_obj->get('Uploader_DLI_Tools');
		$em = $this->getDoctrine()->getManager();
		
		foreach ($files_list as $file) {
			$file_name	= html_entity_decode(basename($file->getClientOriginalName()), ENT_COMPAT, 'UTF-8');
			$found = false;
			if(!empty($file_names)){
			    foreach ($file_names as $key=>$filepath){
			        if (mb_substr($filepath, 0-mb_strlen($file_name)-1) === ("\\".$file_name)){
			            $found = $key;
			            break;
			        }else if ($filepath === $file_name){
			            $found = $key;
			            break;
			        }
			    }
			}
			if ((empty($file_names) && empty($file_dates)) || false !== $found || (!empty($rename_list) && false !== $this->findInArray($rename_list, $file_name))) {
				if ($action === 'CHECKIN' && empty($field)) 
				{
					$temp = $this->getDoctrine()->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document'=>$document->getId(), 'Checked_Out'=>true, 'File_Name'=>$file_name));
					if (!empty($temp)) {
						$res = $file->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), md5($file_name));
						$uploaded++;
					}
				}
				else {
					if (!is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId())) {
						mkdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR);
					}
	
					//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), '0777');
					$res = $file->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), md5($file_name));
					//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), '0666');
					if (!empty($res)) {
						if ($action === 'INSERT') {
							$temp = new AttachmentsDetails();
							$temp->setDocument($document);
							$temp->setField($field);
							$temp->setFileName($file_name);
							$temp->setFileDate(!empty($file_dates) ? $file_dates[$found] : new \DateTime());
							$mimetype = $res->getMimeType();
							$temp->setFileMimeType($mimetype ? $mimetype : 'application/octet-stream');
							$temp->setFileSize($file->getClientSize());
							$temp->setAuthor($this->user);
							
							$em->persist($temp);
							$em->flush();
							$uploaded++;
							$added[] = $file_name;
						}
						elseif ($action === 'EDIT') {
							if (!empty($rename_list) && (false !== $oldName = $this->findInArray($rename_list, $file_name))) 
							{
								$temp = $this->getDoctrine()->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document'=>$document->getId(), 'Field'=>$field, 'File_Name'=>$oldName));
								if (!empty($temp))
								{
									unlink($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($oldName));
									$temp->setFileName($file_name);
								}
							}
							else {
								$temp = $this->getDoctrine()->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document'=>$document->getId(), 'Field'=>$field, 'File_Name'=>$file_name));
							}
							if (!empty($temp)) {
								$temp->setFileDate(new \DateTime());
								$temp->setFileSize($file->getClientSize());
								
								$em->flush();
								$uploaded++;
								$edited[] = $file_name;
							}
						}
					}
					else {
						$error[] = 'Could not upload file "'.$file_name.'"';
					}
				}
				unset($temp);
			}
		}
		
		if ($uploaded === 0) {
//			$error[] = 'No file was uploaded.';
		}
		elseif (!empty($added) || !empty($edited)) {
			$log_obj = new Miscellaneous($this->container);
			if (!empty($added)) {
				$log_obj->createDocumentLog($em, 'UPDATE', $document, 'Added file(s): '.implode(',', $added));
			}
			if (!empty($edited)) {
				$log_obj->createDocumentLog($em, 'UPDATE', $document, 'Edited file(s): '.implode(',', $edited));
			}
		}
		unset($em);
		
		if (!empty($error)) {
			return $error;
		}
		return true;
	}
	
	/**
	 * Rename files server side
	 *
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\DesignElements $field
	 * @param array $rename_list
	 */
	private function renameFiles($document, $field = null, $rename_list = array())
	{
	    
	    if (empty($rename_list)) {
	        return false;
	    }
	    
	    $em = $this->getDoctrine()->getManager();
	    
	    $log_obj = new Miscellaneous($this->container);
	    
	    foreach ($rename_list as $file) {
	        $oldName = $file['original'];
	        $newName = $file['new'];
            $temp = $this->getDoctrine()->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document'=>$document->getId(), 'Field'=>$field, 'File_Name'=>$oldName));
            if (!empty($temp))
	        {
	            if(file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($oldName))){
	                if(rename($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($oldName), $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($newName))){
	                    $temp->setFileName($newName);
	                    $em->flush();
	                    $log_obj->createDocumentLog($em, 'UPDATE', $document, 'Renamed file: ['.$oldName.'] to ['.$newName.']');	                    
	                }
	            }
	           
	        }
            unset($temp);
	    }
	    
	    unset($log_obj);
	    unset($em);
	    
	    return true;
	}
	
	private function findInArray($array, $filename)
	{
		foreach ($array as $element) {
			if ($element['new'] == $filename) 
			{
				return $element['original'];
			}
		}
		return false;
	}

	


	private function generatePathHeaderEx($filename, $file_path)
	{
		
		$headers = array(
				'Content-Description' => 'File Transfer',
				'Content-Type' =>"application/octet-stream",
				'Content-Disposition' => 'attachment; filename="'.$filename.'"',
				'Content-Transfer-Encoding' => 'binary',
				'Pragma' => 'no-cache',
				'Content-Length' => filesize($file_path)
			);

		return array(
				'file_path' => $file_path,
				'headers' => $headers
				);
	}
	
	/**
	 * Generate file path and required header options for response.
	 * If file does not exist returns false
	 * 
	 * @param object $record_obj
	 * @return array
	 */
	private function generatePathHeader($record_obj, $is_template = false)
	{
		$this->initialize();
		$rout = ($is_template === false) ? ($record_obj instanceof \Docova\DocovaBundle\Entity\AttachmentsDetails ? $record_obj->getDocument()->getId() : $record_obj->getDiscussion()->getParentDocument()->getId()) : $is_template;
		$file_name = md5($record_obj->getFileName());

		if (!file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$rout.DIRECTORY_SEPARATOR.$file_name)) {
			return false;
		}
		
		$file_path = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$rout.DIRECTORY_SEPARATOR.$file_name;
		if ($is_template !== false) {
			$headers = array(
				'Content-Type' => $record_obj->getFileMimeType(),
				'Content-Disposition' => 'inline; filename="'.$record_obj->getFileName().'"',
			);
		}
		else {
			$isDownload = $this->get('request_stack')->getCurrentRequest()->query->get('download') == 'true' ? 'attachment' : 'inline';
			$headers = array(
				'Content-Description' => 'File Transfer',
				'Content-Type' => $record_obj->getFileMimeType(),
				'Content-Disposition' => $isDownload . '; filename="'.$record_obj->getFileName().'"',
				'Content-Transfer-Encoding' => 'binary',
				'Pragma' => 'no-cache',
				'Content-Length' => $record_obj->getFileSize()
			);
		}
		
		return array(
				'file_path' => $file_path,
				'headers' => $headers
				);
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
	 * Check if a folder exists as subfolder in a folder with children
	 * 
	 * @param array $subfolders
	 * @param object $needle
	 * @return boolean
	 */
	private function subfolderExists($em, $subfolders, $needle)
	{
		if (!empty($subfolders) && is_array($subfolders)) {
			foreach ($subfolders as $folder) {
				
				if ($folder->getChildren()->count() < 1) {
					if ($folder == $needle) {
						return true;
					}
				}
				else {

					if ($folder == $needle) {
						return true;
					}

					$res = $this->subfolderExists($em, $folder->getChildren(), $needle);
					if (true === $res) {
						return true;
					}
				}
			}
		}
		
		return false;
	}

	
	/**
	 * Paste (duplicate) the copied documents to the target folder
	 * 
	 * @param object $parent_folder
	 * @param array $documents
	 * @param boolean $return_obj
	 * @param boolean $without_log
	 * @return void|array
	 */
	private function pasteFolderDocuments($parent_folder, $documents, $return_obj = false, $without_log = false, $is_revision = false, $file_exception = [])
	{
		if (is_array($documents)) {
			$em = $this->getDoctrine()->getManager();
			$active_user = $this->user;
			$output = array();
			
			if (!$active_user instanceof UserAccounts) {
				$active_user = $this->findUserAndCreate($active_user->getUserNameDnAbbreviated(), false);
			}
			
			$miscfunctions = new MiscFunctions();

			foreach ($documents as $doc) {
				$entity = new Documents();
				$entity->setId($miscfunctions->generateGuid());
				$entity->setAuthor($active_user);
				$entity->setCreator($active_user);
				$entity->setModifier($active_user);
				$entity->setDateCreated(new \DateTime());
				$entity->setDateModified(new \DateTime());
				$entity->setDescription($doc->getDescription());
				$entity->setDocVersion($doc->getDocVersion());
				$entity->setRevision($doc->getRevision());
				if (!$doc->getApplication()) {
					if (!$doc->getFolder()) { return false; }
					$entity->setDocStatus(($doc->getDocType()->getEnableLifecycle()) ? $doc->getDocType()->getInitialStatus() : $doc->getDocType()->getFinalStatus());
					$entity->setStatusNo(($doc->getDocType()->getEnableLifecycle()) ? 0 : 1);
					$entity->setDocTitle(($is_revision === false && $doc->getFolder()->getId() === $parent_folder->getId()) ? 'Copy of ' . $doc->getDocTitle() : $doc->getDocTitle());
					$entity->setKeywords($doc->getKeywords());
					$entity->setFolder($parent_folder);
					$entity->setDocType($doc->getDocType());
				}
				else {
					$fr_property = $doc->getAppForm()->getFormProperties();
					if (empty($fr_property)) { return false; }
					$entity->setDocStatus(($fr_property->getEnableLifecycle()) ? $fr_property->getInitialStatus() : $fr_property->getFinalStatus());
					$entity->setStatusNo(($fr_property->getEnableLifecycle()) ? 0 : 1);
					$entity->setApplication($doc->getApplication());
					$entity->setAppForm($doc->getAppForm());
					
				}
				$entity->setOwner($active_user);
				$entity->setIndexed(false);

				$em->persist($entity);
				$em->flush();
				
				$readers = $authors = [];
				
				if ($doc->getAttachments()->count() > 0) {
					$this->copyAttachments($em, $doc->getAttachments(), $entity, $file_exception);
				}
				
				if ($doc->getTextValues()->count() > 0) 
				{
					foreach ($doc->getTextValues() as $subform_value) {
						if (!$subform_value->getTrash()) {
							$sub_value_entity = new FormTextValues();
							$sub_value_entity->setDocument($entity);
							$sub_value_entity->setField($subform_value->getField());
							$sub_value_entity->setSummaryValue(substr($subform_value->getFieldValue(), 0, 450));
							$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							$sub_value_entity->setOrder($subform_value->getOrder());
							
							$em->persist($sub_value_entity);
							$em->flush();
							
							$sub_value_entity = null;
						}
					}
				}
				
				if ($doc->getNumericValues()->count() > 0) 
				{
					foreach ($doc->getNumericValues() as $subform_value) {
						if (!$subform_value->getTrash()) {
							$sub_value_entity = new FormNumericValues();
							$sub_value_entity->setDocument($entity);
							$sub_value_entity->setField($subform_value->getField());
							$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							$sub_value_entity->setOrder($subform_value->getOrder());

							$em->persist($sub_value_entity);
							$em->flush();
							
							$sub_value_entity = null;
						}
					}
				}
				
				if ($doc->getDateValues()->count() > 0) 
				{
					foreach ($doc->getDateValues() as $subform_value) {
						if (!$subform_value->getTrash()) {
							$sub_value_entity = new FormDateTimeValues();
							$sub_value_entity->setDocument($entity);
							$sub_value_entity->setField($subform_value->getField());
							$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							$sub_value_entity->setOrder($subform_value->getOrder());

							$em->persist($sub_value_entity);
							$em->flush();
							
							$sub_value_entity = null;
						}
					}
				}
				
				if ($doc->getNameValues()->count() > 0) 
				{
					foreach ($doc->getNameValues() as $subform_value) {
						if (!$subform_value->getTrash()) {
							$sub_value_entity = new FormNameValues();
							$sub_value_entity->setDocument($entity);
							$sub_value_entity->setField($subform_value->getField());
							$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							$sub_value_entity->setOrder($subform_value->getOrder());

							$em->persist($sub_value_entity);
							$em->flush();
							
							if ($doc->getApplication()) {
								if ($subform_value->getField()->getNameFieldType() == 2) {
									$readers[] = ['Individual' => $subform_value->getFieldValue()]; 
								}
								elseif ($subform_value->getField()->getNameFieldType() == 3) {
									$authors[] = ['Individual' => $subform_value->getFieldValue()];
								}
							}
							
							$sub_value_entity = null;
						}
					}
				}
				
				if ($doc->getGroupValues()->count() > 0) 
				{
					foreach ($doc->getGroupValues() as $subform_value) {
						if (!$subform_value->getTrash()) {
							$sub_value_entity = new FormGroupValues();
							$sub_value_entity->setDocument($entity);
							$sub_value_entity->setField($subform_value->getField());
							$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							$sub_value_entity->setOrder($subform_value->getOrder());

							$em->persist($sub_value_entity);
							$em->flush();

							if ($doc->getApplication()) {
								if ($subform_value->getField()->getNameFieldType() == 2) {
									$readers[] = ['Group' => $subform_value->getFieldValue()];
								}
								elseif ($subform_value->getField()->getNameFieldType() == 3) {
									$authors[] = ['Group' => $subform_value->getFieldValue()];
								}
							}

							$sub_value_entity = null;
						}
					}
				}
				
				if ($without_log === false) {
					$log_obj = new Miscellaneous($this->container);
					$log_obj->createDocumentLog($em, 'CREATE', $entity);
					$log_obj = null;
				}

				if (!$doc->getApplication())
				{
					$customACL_obj = new CustomACL($this->container);
					$customACL_obj->insertObjectAce($entity, $active_user, 'owner', false);
					$doc_editors = $customACL_obj->getObjectACEUsers($doc, 'edit');
					if ($doc_editors->count() > 0) {
						for ($x = 0; $x < $doc_editors->count(); $x++) 
						{
							if (false !== $user = $this->findUserAndCreate($doc_editors[$x]->getUsername(), false, true)) {
								$customACL_obj->insertObjectAce($entity, $user, 'edit', false);
							}
						}
					}
					$doc_editors = null;
					$grp_editors = $customACL_obj->getObjectACEGroups($doc, 'edit');
					if ($grp_editors->count() > 0) {
						foreach ($grp_editors as $group)
						{
							$customACL_obj->insertObjectAce($entity, $group->getRole(), 'edit');
						}
					}
					$grp_editors = null;
					
					$doc_readers = $customACL_obj->getObjectACEUsers($doc, 'view');
					$grp_readers = $customACL_obj->getObjectACEGroups($doc, 'view');
					if ($doc_readers->count() > 0) {
						for ($x = 0; $x < $doc_readers->count(); $x++) {
							
							if (false != $user = $this->findUserAndCreate($doc_readers[$x]->getUsername(), false, true)) {
								$customACL_obj->insertObjectAce($entity, $user, 'view', false);
							}
						}
					}
					if ($grp_readers->count()) 
					{
						foreach ($grp_readers as $group) {
							$customACL_obj->insertObjectAce($entity, $group->getRole(), 'view');
						}
					}
					
					if (!$doc_readers->count() && !$grp_readers->count()) {
						$customACL_obj->insertObjectAce($entity, 'ROLE_USER', 'view');
					}
					$doc_readers = $grp_readers = $customACL_obj = null;
				}
				else {
					$docova_acl = $this->docova->DocovaAcl($doc->getApplication());
					$docova_acl instanceof \Docova\DocovaBundle\ObjectModel\DocovaAcl;
					if (empty($readers) || !count($readers)) {
						$docova_acl->addDocAuthor($doc, 'ROLE_USER', true);
						$docova_acl->addDocReader($doc, 'ROLE_USER', true);
					}
					else {
						foreach ($readers as $user) {
							if (array_key_exists('Individual', $user)) {
								$docova_acl->addDocReader($doc, $user['Individual']);
							}
							else {
								$docova_acl->addDocReader($doc, $user['Group'], true);
							}
						}
					}
					
					if (!empty($authors) || count($authors))
					{
						foreach ($authors as $user) {
							if (array_key_exists('Individual', $user)) {
								$docova_acl->addDocAuthor($doc, $user['Individual']);
							}
							else {
								$docova_acl->addDocAuthor($doc, $user['Group'], true);
							}
						}
					}
					
					$docova_acl->removeDocAuthor($doc, $this->user);
					$docova_acl->addDocAuthor($doc, $this->user);
					$docova_acl = $user = null;
				}
				
				if ($return_obj === true)
				{
					$output[] = $entity;
				}
				
				$entity = null;
			}
			
			
			if ($return_obj === true && !empty($output))
			{
				return $output;
			}
		}
	}
	
	
	/**
	 * Paste (duplicate) the copied document
	 *
	 * @param DocovaDocument $doc
	 * @param boolean $return_obj
	 * @return void|DocovaDocument
	 */
	private function copyDocument($doc, $return_obj = false)
	{
		
			$em = $this->getDoctrine()->getManager();
			$active_user = $this->user;
			$customACL_obj = new CustomACL($this->container);
			$output = null;
				
			if (!$active_user instanceof UserAccounts) {
				$active_user = $this->findUserAndCreate($active_user->getUserNameDnAbbreviated(), false);
			}
				
			$miscfunctions = new MiscFunctions();
	
			$entity = new Documents();
			$entity->setId($miscfunctions->generateGuid());
			$entity->setApplication($doc->getApplication());
			$entity->setAppForm($doc->getAppForm());
			$entity->setAuthor($active_user);
			$entity->setCreator($active_user);
			$entity->setModifier($active_user);
			$entity->setDateCreated(new \DateTime());
			$entity->setDateModified(new \DateTime());	
			$entity->setOwner($active_user);
			$entity->setIndexed(false);
			$entity->setDocStatus($doc->getDocStatus());
			$entity->setStatusNo($doc->getStatusNo());
				
	
			$em->persist($entity);
			$em->flush();

			
			if ($doc->getAttachments()->count() > 0) {
				$this->copyAttachments($em, $doc->getAttachments(), $entity);
			}
	
			if ($doc->getTextValues()->count() > 0)
			{
				foreach ($doc->getTextValues() as $subform_value) {
					if (!$subform_value->getTrash()) {
						$sub_value_entity = new FormTextValues();
						$sub_value_entity->setDocument($entity);
						$sub_value_entity->setField($subform_value->getField());
						$sub_value_entity->setSummaryValue(substr($subform_value->getFieldValue(), 0, 450));
						$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							
						$em->persist($sub_value_entity);
						$em->flush();
							
						$sub_value_entity = null;
					}
				}
			}
	
			if ($doc->getNumericValues()->count() > 0)
			{
				foreach ($doc->getNumericValues() as $subform_value) {
					if (!$subform_value->getTrash()) {
						$sub_value_entity = new FormNumericValues();
						$sub_value_entity->setDocument($entity);
						$sub_value_entity->setField($subform_value->getField());
						$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							
						$em->persist($sub_value_entity);
						$em->flush();
							
						$sub_value_entity = null;
					}
				}
			}
	
			if ($doc->getDateValues()->count() > 0)
			{
				foreach ($doc->getDateValues() as $subform_value) {
					if (!$subform_value->getTrash()) {
						$sub_value_entity = new FormDateTimeValues();
						$sub_value_entity->setDocument($entity);
						$sub_value_entity->setField($subform_value->getField());
						$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							
						$em->persist($sub_value_entity);
						$em->flush();
							
						$sub_value_entity = null;
					}
				}
			}
	
			if ($doc->getNameValues()->count() > 0)
			{
				foreach ($doc->getNameValues() as $subform_value) {
					if (!$subform_value->getTrash()) {
						$sub_value_entity = new FormNameValues();
						$sub_value_entity->setDocument($entity);
						$sub_value_entity->setField($subform_value->getField());
						$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							
						$em->persist($sub_value_entity);
						$em->flush();
							
						$sub_value_entity = null;
					}
				}
			}
	
			if ($doc->getGroupValues()->count() > 0)
			{
				foreach ($doc->getGroupValues() as $subform_value) {
					if (!$subform_value->getTrash()) {
						$sub_value_entity = new FormGroupValues();
						$sub_value_entity->setDocument($entity);
						$sub_value_entity->setField($subform_value->getField());
						$sub_value_entity->setFieldValue($subform_value->getFieldValue());
							
						$em->persist($sub_value_entity);
						$em->flush();
							
						$sub_value_entity = null;
					}
				}
			}
	
			$log_obj = new Miscellaneous($this->container);
			$log_obj->createDocumentLog($em, 'CREATE', $entity);
			unset($log_obj);
			
	
			$customACL_obj->insertObjectAce($entity, $active_user, 'owner', false);
			$doc_editors = $customACL_obj->getObjectACEUsers($doc, 'edit');
			if ($doc_editors->count() > 0) {
				for ($x = 0; $x < $doc_editors->count(); $x++)
				{
					if (false !== $user = $this->findUserAndCreate($doc_editors[$x]->getUsername(), false, true)) {
						$customACL_obj->insertObjectAce($entity, $user, 'edit', false);
					}
				}
			}
			$doc_editors = null;
			$grp_editors = $customACL_obj->getObjectACEGroups($doc, 'edit');
			if ($grp_editors->count() > 0) {
				foreach ($grp_editors as $group)
				{
					$customACL_obj->insertObjectAce($entity, $group->getRole(), 'edit');
				}
			}
			$grp_editors = null;
	
			$doc_readers = $customACL_obj->getObjectACEUsers($doc, 'view');
			$grp_readers = $customACL_obj->getObjectACEGroups($doc, 'view');
			if ($doc_readers->count() > 0) {
				for ($x = 0; $x < $doc_readers->count(); $x++) {
					if (false != $user = $this->findUserAndCreate($doc_readers[$x]->getUsername(), false, true)) {
						$customACL_obj->insertObjectAce($entity, $user, 'view', false);
					}
				}
			}
			if ($grp_readers->count())
			{
				foreach ($grp_readers as $group) {
					$customACL_obj->insertObjectAce($entity, $group->getRole(), 'view');
				}
			}
	
			if (!$doc_readers->count() && !$grp_readers->count()) {
				$customACL_obj->insertObjectAce($entity, 'ROLE_USER', 'view');
			}
			$doc_readers = $grp_readers = null;
			
			
			if ($return_obj === true)
			{
				$output = $entity;
			}
	
			unset($entity);			
			unset($customACL_obj);
				
			if ($return_obj === true && !empty($output))
			{
				return $output;
			}

	}	
	

	/**
	 * Copy uploaded documents to the new destincation
	 * 
	 * @param object $em
	 * @param \Doctrine\Common\Collections\ArrayCollection $attachments
	 * @param object $target_document
	 * @param array $exceptions
	 */
	private function copyAttachments($em, $attachments, $target_document, $exceptions = [])
	{
		if ($attachments->count() > 0) {
			foreach ($attachments as $file) 
			{
				if (!in_array($file->getFileName(), $exceptions) && !file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$target_document->getId().DIRECTORY_SEPARATOR.md5($file->getFileName()))) 
				{
					if (!is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$target_document->getId().DIRECTORY_SEPARATOR)) {
						mkdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$target_document->getId().DIRECTORY_SEPARATOR);
					}

					//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$entity->getId(), '0777');
					copy($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$file->getDocument()->getId().DIRECTORY_SEPARATOR.md5($file->getFileName()), $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$target_document->getId().DIRECTORY_SEPARATOR.md5($file->getFileName()));
					//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$entity->getId(), '0666');
					
					$entity = new AttachmentsDetails();
					$entity->setDocument($target_document);
					$entity->setField($file->getField());
					$entity->setFileDate($file->getFileDate());
					$entity->setFileMimeType($file->getFileMimeType());
					$entity->setFileName($file->getFileName());
					$entity->setFileSize($file->getFileSize());
					$entity->setAuthor($this->user);

					$em->persist($entity);
					$em->flush();
					unset($entity);
				}
			}
		}
	}

	/**
	 * Create empty Document Workflow Step objects in DB for the document
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \DOMDocument $workflow_xml
	 * @param boolean $return
	 */

	/*  I THINK THIS IS REDUNDANT as teh function has been moved to the workflowprocess extension (SJ)
	private function createDocumentWorkflow($document, $workflow_xml, $return = false)
	{
		if (!empty($document))
		{
			$em = $this->getDoctrine()->getManager();
			foreach ($workflow_xml->getElementsByTagName('IsNew') as $index => $node)
			{
				if ($node->nodeValue == 1) 
				{
					$step = $em->getRepository('DocovaBundle:WorkflowSteps')->findOneBy(array('id' => $workflow_xml->getElementsByTagName('wfItemKey')->item($index)->nodeValue));
					if (!empty($step)) 
					{
						$assignees = $workflow_xml->getElementsByTagName('wfDispReviewerApproverList')->item($index)->nodeValue;
						$assignee_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $assignees);
						$assignees = $groups = array();
						for ($x = 0; $x < count($assignee_list); $x++)
						{
							if (trim($assignee_list[$x]))
							{
								if ($assignee_list[$x] == '[Author]') 
								{
									$assignees['user'][] = $this->user;
								}
								elseif (false !== $user = $this->findUserAndCreate($assignee_list[$x])) 
								{
									$assignees['user'][] = $user;
								}
								else {
									$members = $this->fetchGroupMembers($assignee_list[$x], true);
									if (!empty($members) && $members->count() > 0) 
									{
										$groups[] = $assignee_list[$x];
										foreach ($members as $user) {
											$assignees['group'][] = $user;
										}
									}
								}
							}
							else {
								$assignees['user'][] = 'Please Select';
							}
						}
						if ((!empty($assignees['user']) && count($assignees['user'])) || (!empty($assignees['group']) && count($assignees['group']))) 
						{
							$doc_step = new DocumentWorkflowSteps();
							$doc_step->setDocument($document);
							$doc_step->setStatus('Pending');
							$doc_step->setWorkflowName($step->getWorkflow()->getWorkflowName());
							$doc_step->setRuntimeJSON($step->getRuntimeJSON());
							$doc_step->setAuthorCustomization($step->getWorkflow()->getAuthorCustomization());
							$doc_step->setBypassRleasing($step->getWorkflow()->getBypassRleasing());
							$doc_step->setStepName($step->getStepName());
							$doc_step->setStepType($step->getStepType());
							$doc_step->setDocStatus($step->getDocStatus());
							$doc_step->setPosition($workflow_xml->getElementsByTagName('wfOrder')->item($index)->nodeValue);
							$doc_step->setDistribution($step->getDistribution());
							$doc_step->setParticipants($step->getParticipants());
							$doc_step->setCompleteOn($step->getCompleteBy());
							$doc_step->setOptionalComments($step->getOptionalComments());
							$doc_step->setApproverEdit($step->getApproverEdit());
							$doc_step->setHideReading($step->getHideReading());
							$doc_step->setHideEditing($step->getHideEditing());
							$doc_step->setHideCustom($step->getHideCustom());
							$doc_step->setCustomReviewButtonLabel($step->getCustomReviewButtonLabel());
							$doc_step->setCustomApproveButtonLabel($step->getCustomApproveButtonLabel());
							$doc_step->setCustomDeclineButtonLabel($step->getCustomDeclineButtonLabel());
							$doc_step->setCustomReleaseButtonLabel($step->getCustomReleaseButtonLabel());
							$doc_step->setOtherParticipantGroups($step->getOtherParticipantGroups());
							$doc_step->setAssigneeGroup($groups);
							if ($workflow_xml->getElementsByTagName('wfOrder')->item($index)->nodeValue == 0) {
								$doc_step->setDateStarted(new \DateTime());
							}
							if ($step->getOtherParticipant()->count() > 0)
							{
								foreach ($step->getOtherParticipant() as $participant) {
									$doc_step->addOtherParticipant($participant);
								}
							}
							unset($participant);
							$em->persist($doc_step);
							if (!empty($assignees['user'])) 
							{
								foreach ($assignees['user'] as $user) {
									if ($user !== 'Please Select')
									{
										$assignee = new WorkflowAssignee();
										$assignee->setAssignee($user);
										$assignee->setDocWorkflowStep($doc_step);
										$assignee->setGroupMember(false);
										$em->persist($assignee);
									}
								}
							}
							else {
								foreach ($assignees['group'] as $user) {
									$assignee = new WorkflowAssignee();
									$assignee->setAssignee($user);
									$assignee->setDocWorkflowStep($doc_step);
									$assignee->setGroupMember(true);
									$em->persist($assignee);
								}
							}
							if ($step->getActions()->count() > 0)
							{
								foreach ($step->getActions() as $action) {
									$doc_step_action = new DocWorkflowStepActions();
									$doc_step_action->setActionType($action->getActionType());
									$doc_step_action->setDeclineAction($action->getDeclineAction());
									$doc_step_action->setDueDays($action->getDueDays());
									$doc_step_action->setMessage($action->getMessage());
									$doc_step_action->setSendMessage($action->getSendMessage());
									$doc_step_action->setToOptions($action->getToOptions());
									$doc_step_action->setSenderGroups($action->getSenderGroups());
									$doc_step_action->setStep($doc_step);
									if ($action->getBackTrackStep()) {
										$doc_step_action->setBackTrackStep($action->getBackTrackStep()->getStepName());
									}
									if ($action->getSendTo()->count() > 0) {
										foreach ($action->getSendTo() as $sendTo) {
											$doc_step_action->addSendTo($sendTo);
										}
									}
									$em->persist($doc_step_action);
								}
							}
							$em->flush();
							$doc_step_action = $action = $sendTo = null;
							if (!empty($return)) {
								return $doc_step;
							}
							$doc_step = null;
						}
					}
					unset($step);
				}
			}
		}
	}*/
	
	/**
	 * Generate XML/RSS for user watch list
	 * 
	 * @param string $format
	 * @return \DOMDocument
	 */
	private function getPersonalWatchlist($format)
	{
		$xml_result = new \DOMDocument();
		if ($format === 'RSS')
		{
			$this->generateRSSHead($xml_result, 'Watchlist for '. $this->user->getUserNameDnAbbreviated());
		}
		
		return $xml_result;
	}
	
	/**
	 * Get the XML list of current user's favorite list
	 * 
	 * @return \DOMDocument
	 */
	private function grabFavoriteList($format)
	{
		$xml_result = new \DOMDocument();
		$em = $this->getDoctrine()->getManager();
		$watch_list = $fwatch_list = array();
		$watch_list = $em->getRepository('DocovaBundle:DocumentsWatchlist')->findBy(array('Watchlist_Type' => 1, 'Owner' => $this->user->getId()));
		$fwatch_list = $em->getRepository('DocovaBundle:FoldersWatchlist')->findBy(array('Watchlist_Type' => 1, 'Owner' => $this->user->getId()));
		
		if (!empty($watch_list) || !empty($fwatch_list))
		{
			if ($format === 'RSS') 
			{
				$this->generateRSSHead($xml_result, 'Favorites');
			}
			else {
				$xml_result->appendChild($xml_result->createElement('Documents'));
			}
			$this->mergeFavoritesXML($watch_list, $xml_result, $format);
			$this->mergeFavoritesXML($fwatch_list, $xml_result, $format);
			unset($watch_list);
		}
		else {
			$xml_result->appendChild($xml_result->createElement('Watchlists'));
		}
		return $xml_result;
	}
	
	/**
	 * Merge favorite folders and documents in XML
	 * 
	 * @param array $watch_list
	 * @param \DomDocument $xml_result
	 * @param string $format
	 */
	private function mergeFavoritesXML($watch_list, &$xml_result, $format)
	{
		if (!empty($watch_list) && count($watch_list) > 0) {
			if ($format === 'RSS') 
			{
				foreach ($watch_list as $list)
				{
					$isDocument = $list instanceof \Docova\DocovaBundle\Entity\FoldersWatchlist ? false : true;
					if (($isDocument === true && $list->getDocument()->getFolder()->getLibrary()->getTrash() === false) || (!$isDocument && $list->getFolders()->getLibrary()->getTrash() === false)) 
					{
						$item = $xml_result->createElement('item');
						$newnode = $xml_result->createElement('title');
						$cdata = $xml_result->createCDATASection($isDocument === true ? $list->getDocument()->getDocTitle() : $list->getFolders()->getFolderName());
						$newnode->appendChild($cdata);
						$item->appendChild($newnode);
						$newnode = $xml_result->createElement('description');
						$cdata = $xml_result->createCDATASection($isDocument === true ? 'Document in library '.$list->getDocument()->getFolder()->getLibrary()->getLibraryTitle() : 'Folder in library '.$list->getFolders()->getLibrary()->getLibraryTitle());
						$newnode->appendChild($cdata);
						$item->appendChild($newnode);
						$goto = $isDocument === true ? $list->getDocument()->getFolder()->getLibrary()->getId().','.$list->getDocument()->getFolder()->getId().','.$list->getDocument()->getId() : $list->getFolders()->getLibrary()->getId().','.$list->getFolders()->getId();
						$newnode = $xml_result->createElement('link', $this->generateUrl('docova_homeframe') . '?goto='.$goto);
						$item->appendChild($newnode);
						$newnode = $xml_result->createElement('guid', $goto);
						$attr = $xml_result->createAttribute('isPermaLink');
						$attr->value = 'false';
						$newnode->appendChild($attr);
						$item->appendChild($newnode);
						$newnode = $xml_result->createElement('pubDate', $isDocument === true ? $list->getDocument()->getDateCreated()->format('r') : $list->getFolders()->getDateCreated()->format('r'));
						$item->appendChild($newnode);
						$newnode = $xml_result->createElement('category');
						$cdata = $xml_result->createCDATASection('Favorites');
						$newnode->appendChild($cdata);
						$item->appendChild($newnode);
	
						$channel = $xml_result->getElementsByTagName('channel')->item(0);
						$channel->appendChild($item);
					}
				}
			}
			else 
			{
				foreach ($watch_list as $list)
				{
					$isDocument = $list instanceof \Docova\DocovaBundle\Entity\FoldersWatchlist ? false : true;
					if (($isDocument === true && $list->getDocument()->getFolder()->getLibrary()->getTrash() === false) || (!$isDocument && $list->getFolders()->getLibrary()->getTrash() === false)) 
					{
						$document = $xml_result->createElement('Document');
						$document->appendChild($xml_result->createElement('LibraryKey', $isDocument === true ? $list->getDocument()->getFolder()->getLibrary()->getId() : $list->getFolders()->getLibrary()->getId()));
						$cdata	= $xml_result->createCDATASection($isDocument === true ? $list->getDocument()->getFolder()->getLibrary()->getLibraryTitle() : $list->getFolders()->getLibrary()->getLibraryTitle());
						$node	= $xml_result->createElement('LibraryName');
						$node->appendChild($cdata);
						$document->appendChild($node);
						$document->appendChild($xml_result->createElement('FolderID', $isDocument === true ? $list->getDocument()->getFolder()->getId() : $list->getFolders()->getId()));
						$document->appendChild($xml_result->createElement('ParentDocID', $isDocument === true ? $list->getDocument()->getId() : $list->getFolders()->getId()));
						$cdata	= $xml_result->createCDATASection($isDocument === true ? $list->getDocument()->getFolder()->getFolderName() : $list->getFolders()->getFolderName());
						$node	= $xml_result->createElement('FolderName');
						$node->appendChild($cdata);
						$document->appendChild($node);
						$cdata	= $xml_result->createCDATASection($isDocument === true ? $list->getDocument()->getDocTitle() : $list->getFolders()->getFolderName());
						$node	= $xml_result->createElement('Title');
						$node->appendChild($cdata);
						$document->appendChild($node);
						$cdata	= $xml_result->createCDATASection($isDocument === true ? $list->getDocument()->getDescription() : $list->getFolders()->getDescription());
						$node	= $xml_result->createElement('Description');
						$node->appendChild($cdata);
						$document->appendChild($node);
						$document->appendChild($xml_result->createElement('Unid', $isDocument === true ? $list->getDocument()->getId() : $list->getFolders()->getId()));
						$document->appendChild($xml_result->createElement('typeico', $isDocument === true ? '&#157;' : '&#204;'));
						$document->appendChild($xml_result->createElement('rectype', $isDocument === true ? 'doc' : 'fld'));
						$node = $xml_result->createElement('LastModifiedDate');
						$date = $isDocument === true ? ($list->getDocument()->getDateModified() ? $list->getDocument()->getDateModified() : $list->getDocument()->getDateCreated()) : ($list->getFolders()->getDateUpdated() ? $list->getFolders()->getDateUpdated() : $list->getFolders()->getDateCreated());
						$cdata = $xml_result->createCDATASection($date->format('m/d/Y'));
						$node->appendChild($cdata);
						$year = $xml_result->createAttribute('Y');
						$year->value = $date->format('Y');
						$month = $xml_result->createAttribute('M');
						$month->value = $date->format('m');
						$day = $xml_result->createAttribute('D');
						$day->value = $date->format('d');
						$weekday = $xml_result->createAttribute('W');
						$weekday->value = $date->format('w');
						$hours = $xml_result->createAttribute('H');
						$hours->value = $date->format('H');
						$minutes = $xml_result->createAttribute('MN');
						$minutes->value = $date->format('i');
						$seconds = $xml_result->createAttribute('S');
						$seconds->value = $date->format('s');
						$val = $xml_result->createAttribute('val');
						$val->value = strtotime($date->format('Y-m-d H:i:s'));
						$orgin_date = $xml_result->createAttribute('origDate');
						$orgin_date->value = $date->format('m/d/Y');
						$node->appendChild($year);
						$node->appendChild($month);
						$node->appendChild($day);
						$node->appendChild($weekday);
						$node->appendChild($hours);
						$node->appendChild($minutes);
						$node->appendChild($seconds);
						$node->appendChild($val);
						$node->appendChild($orgin_date);
						$document->appendChild($node);
						$document->appendChild($xml_result->createElement('WatchDate', $list->getDateModified() ? $list->getDateModified()->format('m/d/Y h:i:s A') : ''));
					
						$root = $xml_result->getElementsByTagName('Documents')->item(0);
						$root->appendChild($document);
					}
				}
			}
		}
	}
	
	/**
	 * Get XML for recent edited documents by current user
	 * 
	 * @param string $format
	 * @return \DOMDocument
	 */
	private function getRecentEditedList($format)
	{
		$xml_result = new \DOMDocument("1.0", 'UTF-8');
		$em = $this->getDoctrine()->getManager();
		$documents = $em->getRepository('DocovaBundle:Documents')->findBy(array('Modifier' => $this->user->getId(), 'Archived' => false), array('Date_Modified' => 'DESC'), 10);
		$security_check = new Miscellaneous($this->container);
		
		if ($format === 'RSS') 
		{
			$this->generateRSSHead($xml_result, 'Recently edited');
			$node = $xml_result->getElementsByTagName('channel')->item(0);
			if (!empty($documents) && count($documents) > 0) 
			{
				foreach ($documents as $doc)
				{
					$library = $doc->getFolder()->getLibrary();
					if ($library->getTrash() === false && ($security_check->canReadDocument($doc, true) === true || $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('MASTER', $library))) 
					{
						$item = $xml_result->createElement('item');
						$newnode = $xml_result->createElement('title');
						$cdata = $xml_result->createCDATASection($doc->getDocTitle());
						$newnode->appendChild($cdata);
						$item->appendChild($newnode);
						$newnode = $xml_result->createElement('description');
						$cdata = $xml_result->createCDATASection('Document in library '.$library->getLibraryTitle());
						$newnode->appendChild($cdata);
						$item->appendChild($newnode);
						$newnode = $xml_result->createElement('link', $this->generateUrl('docova_homeframe').'?goto='.$library->getId().','.$doc->getFolder()->getId().','.$doc->getId());
						$item->appendChild($newnode);
						$newnode = $xml_result->createElement('guid', $library->getId().','.$doc->getFolder()->getId().','.$doc->getId());
						$attr = $xml_result->createAttribute('isPermaLink');
						$attr->value = 'false';
						$newnode->appendChild($attr);
						$item->appendChild($newnode);
						$newnode = $xml_result->createElement('pubDate', $doc->getDateModified() ? $doc->getDateModified()->format('r') : $doc->getDateCreated()->format('r'));
						$item->appendChild($newnode);
						$newnode = $xml_result->createElement('category');
						$cdata = $xml_result->createCDATASection('Recently edited');
						$newnode->appendChild($cdata);
						$item->appendChild($newnode);
						$node->appendChild($item);
					}
				}
			}
		}
		else {
			$root = $xml_result->appendChild($xml_result->createElement('documents'));
			foreach ($documents as $doc)
			{
				$is_app_form = false;
				if (!$doc->getFolder()) {
					$is_app_form = true;
					$library = $doc->getApplication();
				}
				else {
					$library = $doc->getFolder()->getLibrary();
				}
				$can_read = false;
				if ($is_app_form === true) {
					$docova = new Docova($this->container);
					$docovaAcl = $docova->DocovaAcl($library);
					$docova = null;
					$can_read = $library->getTrash() === false && $docovaAcl->isDocReader($doc) ? true : false;
				}
				else {
					$can_read = $library->getTrash() === false && ($security_check->canReadDocument($doc, true) === true || $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->container->get('security.authorization_checker')->isGranted('MASTER', $library)) ? true : false;
				}
				
				if ($can_read === true) {
					$doc_node = $xml_result->createElement('document');
					$newnode = $xml_result->createElement('docid', $doc->getId().'~rel');
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('librarykey', $library->getId());
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('libraryname');
					$cdata = $xml_result->createCDATASection($library->getLibraryTitle());
					$newnode->appendChild($cdata);
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('folderid', $is_app_form === false ? $doc->getFolder()->getId() : '');
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('foldername');
					$cdata = $xml_result->createCDATASection($is_app_form === false ? $doc->getFolder()->getFolderName() : '');
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('folderpath');
					$cdata = $xml_result->createCDATASection($is_app_form === false ? $doc->getFolder()->getFolderPath() : '');
					$newnode->appendChild($cdata);
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('parentdocid', $doc->getId());
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('title');
					$cdata = $xml_result->createCDATASection($doc->getDocTitle());
					$newnode->appendChild($cdata);
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('description');
					$cdata = $xml_result->createCDATASection($doc->getDescription());
					$newnode->appendChild($cdata);
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('unid', $doc->getId());
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('isappform', $is_app_form === false ? null : 1);
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('typeicon', '&#157;');
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('rectype', $is_app_form === false ? null : 'doc');
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('lastmodifieddate');
					$cdata = $xml_result->createCDATASection($doc->getDateModified()->format('m/d/Y'));
					$newnode->appendChild($cdata);
					$year = $xml_result->createAttribute('Y');
					$year->value = $doc->getDateModified()->format('Y');
					$month = $xml_result->createAttribute('M');
					$month->value = $doc->getDateModified()->format('m');
					$day = $xml_result->createAttribute('D');
					$day->value = $doc->getDateModified()->format('d');
					$weekday = $xml_result->createAttribute('W');
					$weekday->value = $doc->getDateModified()->format('w');
					$hours = $xml_result->createAttribute('H');
					$hours->value = $doc->getDateModified()->format('H');
					$minutes = $xml_result->createAttribute('MN');
					$minutes->value = $doc->getDateModified()->format('i');
					$seconds = $xml_result->createAttribute('S');
					$seconds->value = $doc->getDateModified()->format('s');
					$val = $xml_result->createAttribute('val');
					$val->value = strtotime($doc->getDateModified()->format('Y-m-d H:i:s'));
					$newnode->appendChild($year);
					$newnode->appendChild($month);
					$newnode->appendChild($day);
					$newnode->appendChild($weekday);
					$newnode->appendChild($hours);
					$newnode->appendChild($minutes);
					$newnode->appendChild($seconds);
					$newnode->appendChild($val);
					$doc_node->appendChild($newnode);
					$newnode = $xml_result->createElement('selected');
					$doc_node->appendChild($newnode);
			
					$root->appendChild($doc_node);
					unset($doc_node, $newnode, $cdata);
				}
			}
		}
		unset($documents, $doc);
		
		return $xml_result;
	}
	
	/**
	 * Generate XML for favorites in widgets
	 * 
	 * @return \DOMDocument
	 */
	private function grabFavoriteListWidget()
	{
		$xml_result = new \DOMDocument();
		$root = $xml_result->appendChild($xml_result->createElement('documents'));
		$em = $this->getDoctrine()->getManager();
		$favorites = $em->getRepository('DocovaBundle:DocumentsWatchlist')->findBy(array('Watchlist_Type' => 1, 'Owner' => $this->user->getId()));
		$folder_favorites = $em->getRepository('DocovaBundle:FoldersWatchlist')->findBy(array('Watchlist_Type' => 1, 'Owner' => $this->user->getId()));
		if (!empty($folder_favorites) && count($folder_favorites) > 0) 
		{
			foreach ($folder_favorites as $ff) {
				$favorites[] = $ff;
			}
		}
		unset($folder_favorites, $ff);
		if (!empty($favorites) && count($favorites) > 0) 
		{
			foreach ($favorites as $uf) {
				if ($uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist) {
					if ($uf->getDocument()->getFolder()->getLibrary()->getTrash()) {
						continue;
					}
				}
				else {
					if ($uf->getFolders()->getLibrary()->getTrash()) {
						continue;
					}
				}
				$node = $xml_result->createElement('document');
				$newnode = $xml_result->createElement('docid', $uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? $uf->getDocument()->getId() : $uf->getFolders()->getId());
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('librarykey', $uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? $uf->getDocument()->getFolder()->getLibrary()->getId() : $uf->getFolders()->getLibrary()->getId());
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('libraryname');
				$cdata = $xml_result->createCDATASection($uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? $uf->getDocument()->getFolder()->getLibrary()->getLibraryTitle() : $uf->getFolders()->getLibrary()->getLibraryTitle());
				$newnode->appendChild($cdata);
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('folderid', $uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? $uf->getDocument()->getFolder()->getId() : $uf->getFolders()->getId());
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('foldername');
				$cdata = $xml_result->createCDATASection($uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? $uf->getDocument()->getFolder()->getFolderName() : $uf->getFolders()->getFolderName());
				$newnode->appendChild($cdata);
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('folderpath');
				$cdata = $xml_result->createCDATASection($uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? $uf->getDocument()->getFolder()->getFolderName() : $uf->getFolders()->getFolderPath());
				$newnode->appendChild($cdata);
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('parentdocid', $uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? $uf->getDocument()->getId() : $uf->getFolders()->getId());
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('title');
				$cdata = $xml_result->createCDATASection($uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? $uf->getDocument()->getDocTitle() : $uf->getFolders()->getFolderName());
				$newnode->appendChild($cdata);
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('description');
				$cdata = $xml_result->createCDATASection($uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? $uf->getDocument()->getDescription() : $uf->getFolders()->getDescription());
				$newnode->appendChild($cdata);
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('typeicon', $uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? '&#157;' : '&#204;');
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('rectype', $uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? 'doc' : 'fld');
				$node->appendChild($newnode);
				$date = $uf instanceof \Docova\DocovaBundle\Entity\DocumentsWatchlist ? ($uf->getDocument()->getDateModified() ? $uf->getDocument()->getDateModified() : $uf->getDocument()->getDateCreated()) : ($uf->getFolders()->getDateUpdated() ? $uf->getFolders()->getDateUpdated() : $uf->getFolders()->getDateCreated());
				$newnode = $xml_result->createElement('lastmodifieddate');
				$cdata = $xml_result->createCDATASection($date->format('m/d/Y'));
				$newnode->appendChild($cdata);
				$year = $xml_result->createAttribute('Y');
				$year->value = $date->format('Y');
				$month = $xml_result->createAttribute('M');
				$month->value = $date->format('m');
				$day = $xml_result->createAttribute('D');
				$day->value = $date->format('d');
				$weekday = $xml_result->createAttribute('W');
				$weekday->value = $date->format('w');
				$hours = $xml_result->createAttribute('H');
				$hours->value = $date->format('H');
				$minutes = $xml_result->createAttribute('MN');
				$minutes->value = $date->format('i');
				$seconds = $xml_result->createAttribute('S');
				$seconds->value = $date->format('s');
				$val = $xml_result->createAttribute('val');
				$val->value = strtotime($date->format('Y-m-d H:i:s'));
				$orgin_date = $xml_result->createAttribute('origDate');
				$orgin_date->value = $date->format('m/d/Y');
				$newnode->appendChild($year);
				$newnode->appendChild($month);
				$newnode->appendChild($day);
				$newnode->appendChild($weekday);
				$newnode->appendChild($hours);
				$newnode->appendChild($minutes);
				$newnode->appendChild($seconds);
				$newnode->appendChild($val);
				$newnode->appendChild($orgin_date);
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('watchdate', $uf->getDateModified() ? $uf->getDateModified()->format('m/d/Y h:i:s A') : '');
				$node->appendChild($newnode);
				$newnode = $xml_result->createElement('selected');
				$node->appendChild($newnode);
				$root->appendChild($node);
			}
		}
		unset($favorites, $uf);
		
		return $xml_result;
	}
	
	/**
	 * Generate XML/RSS for pending workflow items
	 * 
	 * @param string $format
	 * @return \DOMDocument
	 */
	private function getPendingWorkflow($format)
	{
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$em = $this->getDoctrine()->getManager();
		$wf_items = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getPendingWorkflowItems($this->user->getId());
		if ($format == 'RSS')
		{
			$this->generateRSSHead($xml_result, 'Pending workflow');
			$node = $xml_result->getElementsByTagName('channel')->item(0);
			if (!empty($wf_items) && count($wf_items) > 0)
			{
				foreach ($wf_items as $wf) {
					$due_days = 0;
					if ($wf->getActions()->count() > 0) {
						foreach ($wf->getActions() as $action) {
							if ($action->getActionType() == 6) {
								$due_days = $action->getDueDays();
								break;
							}
						}
					}
					$item = $xml_result->createElement('item');
					$newnode = $xml_result->createElement('title');
					$due_days = (!empty($due_days)) ? $wf->getDateStarted()->modify("+$due_days day")->format('m/d/Y') : '';
					$cdata = $xml_result->createCDATASection($wf->getStepType(true).' for: '.$wf->getDocument()->getDocTitle().', due '.$due_days);
					$newnode->appendChild($cdata);
					$item->appendChild($newnode);
					$newnode = $xml_result->createElement('description');
					$cdata = $xml_result->createCDATASection('Initialized by: '.$wf->getDocument()->getCreator()->getUserNameDnAbbreviated().' on '.$wf->getDocument()->getDateCreated()->format('m/d/Y').', due '.$due_days);
					$newnode->appendChild($cdata);
					$item->appendChild($newnode);
					$newnode = $xml_result->createElement('link', $this->generateUrl('docova_homeframe').'?goto='.$wf->getDocument()->getFolder()->getLibrary()->getId().','.$wf->getDocument()->getFolder()->getId().','.$wf->getDocument()->getId());
					$item->appendChild($newnode);
					$newnode = $xml_result->createElement('guid', $wf->getDocument()->getFolder()->getLibrary()->getId().','.$wf->getDocument()->getFolder()->getId().','.$wf->getDocument()->getId());
					$attr = $xml_result->createAttribute('isPermaLink');
					$attr->value = 'false';
					$newnode->appendChild($attr);
					$item->appendChild($newnode);
					$newnode = $xml_result->createElement('pubDate');
					$cdata = $xml_result->createCDATASection($wf->getDateStarted()->format('m/d/Y'));
					$newnode->appendChild($cdata);
					$year = $xml_result->createAttribute('Y');
					$year->value = $wf->getDateStarted()->format('Y');
					$month = $xml_result->createAttribute('M');
					$month->value = $wf->getDateStarted()->format('m');
					$day = $xml_result->createAttribute('D');
					$day->value = $wf->getDateStarted()->format('d');
					$weekday = $xml_result->createAttribute('W');
					$weekday->value = $wf->getDateStarted()->format('w');
					$hours = $xml_result->createAttribute('H');
					$hours->value = $wf->getDateStarted()->format('H');
					$minutes = $xml_result->createAttribute('MN');
					$minutes->value = $wf->getDateStarted()->format('i');
					$seconds = $xml_result->createAttribute('S');
					$seconds->value = $wf->getDateStarted()->format('s');
					$val = $xml_result->createAttribute('val');
					$val->value = strtotime($wf->getDateStarted()->format('Y-m-d H:i:s'));
					$orgin_date = $xml_result->createAttribute('origDate');
					$orgin_date->value = $wf->getDateStarted()->format('m/d/Y');
					$newnode->appendChild($year);
					$newnode->appendChild($month);
					$newnode->appendChild($day);
					$newnode->appendChild($weekday);
					$newnode->appendChild($hours);
					$newnode->appendChild($minutes);
					$newnode->appendChild($seconds);
					$newnode->appendChild($val);
					$newnode->appendChild($orgin_date);
					$item->appendChild($newnode);
					$newnode = $xml_result->createElement('category');
					$cdata = $xml_result->createCDATASection('Pending workflow');
					$newnode->appendChild($cdata);
					$item->appendChild($newnode);
					$node->appendChild($item);
				}	
			}
			return $xml_result;
		}
	}
	
	/**
	 * Generate XML/RSS for user documents
	 * 
	 * @param string $format
	 * @return \DOMDocument
	 */
	private function getMyDocuments($format)
	{
		$xml_result = new \DOMDocument();
		if ($format === 'RSS') 
		{
			$this->generateRSSHead($xml_result, 'My documents');
		}
		
		return $xml_result;
	}
	
	/**
	 * Generate XML/RSS for My To Do List
	 * 
	 * @param string $format
	 * @return \DOMDocument
	 */
	private function getMyToDoList($format)
	{
		$xml_result = new \DOMDocument();
		if ($format === 'RSS') 
		{
			$this->generateRSSHead($xml_result, 'My to do list items');
		}
		
		return $xml_result;
	}
	
	/**
	 * Generate the RSS header section
	 * 
	 * @param \DOMDocument $xml
	 * @param string $title
	 */
	private function generateRSSHead(&$xml, $title)
	{
		$root = $xml->appendChild($xml->createElement('rss'));
		$attr = $xml->createAttribute('version');
		$attr->value = '2.0';
		$root->appendChild($attr);
		$node = $xml->createElement('channel');
		$newnode = $xml->createElement('title');
		$cdata = $xml->createCDATASection($title);
		$newnode->appendChild($cdata);
		$node->appendChild($newnode);
		$newnode = $xml->createElement('link', $this->generateUrl('docova_homeframe') . '?goto=fav');
		$node->appendChild($newnode);
		$newnode = $xml->createElement('description');
		$cdata = $xml->createCDATASection($title);
		$newnode->appendChild($cdata);
		$node->appendChild($newnode);
		$newnode = $xml->createElement('language', 'en-us');
		$node->appendChild($newnode);
		$newnode = $xml->createElement('lastBuildDate', date('r'));
		$node->appendChild($newnode);
		$newnode = $xml->createElement('copyright');
		$cdata = $xml->createCDATASection('DLI Tools Inc');
		$newnode->appendChild($cdata);
		$node->appendChild($newnode);
		$newnode = $xml->createElement('ttl', '15');
		$node->appendChild($newnode);
		$root->appendChild($node);
	}
	
	/**
	 * Get CIAO file XML log for the current user
	 * 
	 * @return string|boolean
	 */
	private function getCIAPLogsByUser()
	{
		try {
			$em = $this->getDoctrine()->getManager();
			$ciao_logs = $em->getRepository('DocovaBundle:AttachmentsDetails')->getUserCheckedOutFiles($this->user->getId());
			if (!empty($ciao_logs)) 
			{
				$xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $xml->appendChild($xml->createElement('documents'));
				foreach ($ciao_logs as $log) 
				{
					if ($log->getDocument()->getFolder()->getLibrary()->getTrash() === false) 
					{
						$node = $xml->createElement('document');
						$cdata = $xml->createCDATASection($log->getDocument()->getDocTitle());
						$newnode = $xml->createElement('Subject');
						$newnode->appendChild($cdata);
						$node->appendChild($newnode);
						$newnode = $xml->createElement('librarykey', $log->getDocument()->getFolder()->getLibrary()->getId());
						$node->appendChild($newnode);
						$cdata = $xml->createCDATASection($log->getDocument()->getFolder()->getLibrary()->getLibraryTitle());
						$newnode = $xml->createElement('libraryname');
						$newnode->appendChild($cdata);
						$node->appendChild($newnode);
						$newnode = $xml->createElement('folderid', $log->getDocument()->getFolder()->getId());
						$node->appendChild($newnode);
						$newnode = $xml->createElement('parentdockey', $log->getDocument()->getId());
						$node->appendChild($newnode);
						$newnode = $xml->createElement('parentdocid', $log->getDocument()->getId());
						$node->appendChild($newnode);
						$newnode = $xml->createElement('Unid', $log->getId());
						$node->appendChild($newnode);
						$newnode = $xml->createElement('docid', $log->getId());
						$node->appendChild($newnode);
						$cdata = $xml->createCDATASection($log->getCheckedOutBy()->getUserNameDnAbbreviated());
						$newnode = $xml->createElement('editor');
						$newnode->appendChild($cdata);
						$node->appendChild($newnode);
						$newnode = $xml->createElement('date', $log->getDateCheckedOut()->format('m/d/Y h:i:s A'));
						$node->appendChild($newnode);
						$cdata = $xml->createCDATASection($log->getCheckedOutPath());
						$newnode = $xml->createElement('path');
						$newnode->appendChild($cdata);
						$node->appendChild($newnode);
						$cdata = $xml->createCDATASection($log->getFileName());
						$newnode = $xml->createElement('filename');
						$newnode->appendChild($cdata);
						$node->appendChild($newnode);
						$root->appendChild($node);
					}
				}

				return $xml->saveXML();
			}

			//return '';
		}
		catch (\Exception $e) {
			return false;
		}
		return false;
	}
	
	/**
	 * Generate XML for pending workflow items
	 * 
	 * @param string $lbr_fld
	 * @return string|boolean
	 */
	private function getWorkflowItemsSummary($lbr_fld = '')
	{
		$library = $folder = '';
		$xml_result = false;
		if (!empty($lbr_fld)) {
			$filter = explode('~', $lbr_fld);
			$library = $filter[0];
			$folder = !empty($filter[1]) ? $filter[1] : $folder;
		}
		try {	
			$em = $this->getDoctrine()->getManager();
			$wf_items = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getWorkflowsSummary($library, $folder);
			if (!empty($wf_items) && count($wf_items) > 0) 
			{
				$xml_result = '<documents>';
				foreach ($wf_items as $item) {
					$due_days = 0;
					if ($item->getActions()->count() > 0) {
						foreach ($item->getActions() as $action) {
							if ($action->getActionType() == 6) {
								$due_days = $action->getDueDays();
								break;
							}
						}
					}
					//@TODO: names list should be completed, for now just the assignee is added
					$xml_result .= '<document>';
					$xml_result .= "<wfItemKey>{$item->getId()}</wfItemKey>";
					$xml_result .= "<docid>wfs{$item->getId()}</docid>";
					$xml_result .= '<rectype>doc</rectype>';
					$xml_result .= "<librarykey>{$item->getDocument()->getFolder()->getLibrary()->getId()}</librarykey>";
					$xml_result .= "<folderid>{$item->getDocument()->getFolder()->getId()}</folderid>";
					$xml_result .= "<parentdocid>{$item->getDocument()->getId()}</parentdocid>";
					$xml_result .= '<duedate>'.((!empty($due_days)) ? $item->getDateStarted()->modify("+$due_days day")->format('m/d/Y') : '').'</duedate>';
					$xml_result .= "<foldername><![CDATA[{$item->getDocument()->getFolder()->getFolderName()}]]></foldername>";
					$xml_result .= "<subject><![CDATA[{$item->getDocument()->getDocTitle()}]]></subject>";
					$xml_result .= "<wforder>{$item->getPosition()}</wforder>";
					$xml_result .= "<wftitle><![CDATA[{$item->getStepName()}]]></wftitle>";
					$xml_result .= "<wfAction>{$item->getStepType(true)}</wfAction>";
					$xml_result .= '<wfType>'.($item->getDistribution() == 2 ? 'Parallel' : 'Serial').'</wfType>';
					$xml_result .= '<wfOptionalComments>'.($item->getOptionalComments() ? '1' : '').'</wfOptionalComments>';
					$xml_result .= "<wfStatus><![CDATA[{$item->getStatus()}]]></wfStatus>";
					$xml_result .= "<wfCompleteAny>0</wfCompleteAny>";
					$xml_result .= "<wfCompleteCount>0</wfCompleteCount>";
					$xml_result .= "<ContainsGroups>No</ContainsGroups>";
					$assignee = '';
					foreach ($item->getAssignee() as $a) {
						if (!$a->getGroupMember()) {
							$assignee .= $a->getAssignee()->getUserNameDnAbbreviated(). ',';
						}
					}
					if (count($item->getAssigneeGroup()) > 0) 
					{
						$assignee .= implode(',', $item->getAssigneeGroup()) . ',';
					}
					$assignee = substr_replace($assignee, '', -1);
					$xml_result .= "<wfDispReviewerApproverList><![CDATA[$assignee]]></wfDispReviewerApproverList>";
					$xml_result .= "<wfReviewerApproverList><![CDATA[$assignee]]></wfReviewerApproverList>";
					$xml_result .= "<wfReviewApprovalComplete><![CDATA[]]></wfReviewApprovalComplete>";
					$xml_result .= "<wfReviewerApproverSelect><![CDATA[]]></wfReviewerApproverSelect>";
					$xml_result .= "<wfDocStatus><![CDATA[{$item->getDocument()->getDocStatus()}]]></wfDocStatus>";
					$xml_result .= "<wfIsCurrentItem>1</wfIsCurrentItem>";
					$xml_result .= "<wfCompleteNotifyList><![CDATA[]]></wfCompleteNotifyList>";
					$xml_result .= "<wfActivateNotifyList><![CDATA[]]></wfActivateNotifyList>";
					$xml_result .= "<Modified>0</Modified><Selected>0</Selected><IsNew>0</IsNew><IsDeleted>0</IsDeleted><blank></blank>";
					$xml_result .= '</document>';
				}
				$xml_result .= '</documents>';
				return $xml_result;
			}
			return false;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Get user activities incomplete XML
	 * 
	 * @return boolean|string
	 */
	private function getIncompleteUserActivities()
	{
		$xml = false;
		$em = $this->getDoctrine()->getManager();
		try {
			$activites = $em->getRepository('DocovaBundle:DocumentActivities')->getIncompleteUserActivities($this->user->getId());
			if (!empty($activites)) 
			{
				$xml = '<documents>';
				foreach ($activites as $act) 
				{
					if ($act->getDocument()->getFolder()->getLibrary()->getTrash() === false) 
					{
						$xml .= "<document><docid>{$act->getId()}</docid>";
						$xml .= "<librarykey>{$act->getDocument()->getFolder()->getLibrary()->getId()}</librarykey>";
						$xml .= "<folderid>{$act->getDocument()->getFolder()->getId()}</folderid>";
						$xml .= "<parentdocid>{$act->getDocument()->getId()}</parentdocid>";
						$xml .= "<ActivityType><![CDATA[{$act->getActivityAction()}]]></ActivityType>";
						$xml .= "<ActivityStatus><![CDATA[{$act->getActivityAction()} Pending]]></ActivityStatus>";
						$xml .= "<Subject><![CDATA[{$act->getSubject()}]]></Subject>";
						$xml .= "<Body><![CDATA[{$act->getMessage()}]]></Body>";
						$xml .= "<Resp><![CDATA[{$act->getResponse()}]]></Resp>";
						$xml .= "<CreatedBy><![CDATA[{$act->getCreatedBy()->getUserNameDnAbbreviated()}]]></CreatedBy>";
						$xml .= "<Recipient><![CDATA[{$this->user->getUserNameDnAbbreviated()}]]></Recipient>";
						$date = $act->getStatusDate();
						$year = $date->format('Y');
						$month = $date->format('m');
						$day = $date->format('d');
						$weekday = $date->format('w');
						$hours = $date->format('H');
						$minutes = $date->format('i');
						$seconds = $date->format('s');
						$val = strtotime($date->format('Y-m-d H:i:s'));
						$orgin_date = $date->format('m/d/Y');
						$xml .= "<StatusDate Y='$year' M='$month' D='$day' W='$weekday' H='$hours' MN='$minutes' S='$seconds' val='$val' origDate='$orgin_date'><![CDATA[{$date->format('m/d/Y')}]]></StatusDate>";
						$xml .= "<Unid>{$act->getId()}</Unid></document>";
					}
				}
				$xml .= '</documents>';
			}
		}
		catch (\Exception $e) {
			//@TODO: log the error message
			$xml = false;
		}

		return $xml;
	}
	
	/**
	 * Creates document "comments/related emails" XML
	 * 
	 * @param string $document
	 * @param string $suffix
	 * @return string|boolean
	 */
	private function getDocumentsByParent($document, $suffix)
	{
		$xml_result = false;
		$em = $this->getDoctrine()->getManager();
		if ($suffix == 'M') 
		{
			try {
				$emails = $em->getRepository('DocovaBundle:RelatedEmails')->findBy(array('document' => $document));
				if (!empty($emails) && count($emails) > 0) 
				{
					$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
					$xml_result = '<documents>';
					foreach ($emails as $mail) 
					{
						$date = $mail->getDateSent();
						$xml_result .= "<document><customfield1><![CDATA[]]></customfield1>";
						$xml_result .=  "<customfield2><![CDATA[]]></customfield2>";
						$xml_result .= "<docid>{$mail->getId()}</docid>";
						$xml_result .= "<rectype>memo</rectype>";
						$xml_result .= "<parentdocid></parentdocid>";
						$xml_result .= "<relateddata></relateddata>";
						$xml_result .= "<librarykey></librarykey>";
						$xml_result .= "<libraryname><![CDATA[]]></libraryname>";
						$xml_result .= "<folderid></folderid>";
						$xml_result .= "<foldername><![CDATA[]]></foldername>";
						$xml_result .= "<doctypekey></doctypekey>";
						$xml_result .= "<title><![CDATA[]]></title>";
						$xml_result .= "<status><![CDATA[]]></status>";
						$xml_result .= "<author><![CDATA[]]></author>";
						$xml_result .= '<datecreated val="0" Y="" M="" D="" H="" MN="" S="" W=""><![CDATA[]]></datecreated>';
						$xml_result .= "<version>.</version>";
						$xml_result .= "<description><![CDATA[]]></description>";
						$xml_result .= '<typeicon>&#204;</typeicon><lastmodifieddate val="0" Y="" M="" D="" H="" MN="" S="" W=""></lastmodifieddate>';
						$xml_result .= "<active></active><selected>0</selected>";
						$xml_result .= "<lvInfo></lvInfo><lvparentdockey></lvparentdockey>";
						$xml_result .= "<lvparentdocid></lvparentdocid><archivedparentdockey></archivedparentdockey><docisdeleted></docisdeleted>";
						$xml_result .= "<from><![CDATA[{$mail->getFromWho()}]]></from>";
						$xml_result .= "<dockey></dockey><subject><![CDATA[{$mail->getSubject()}]]></subject>";
						$xml_result .= "<createddate val='".strtotime($date->format('Y-m-d H:i:s'))."' Y='{$date->format('Y')}' M='{$date->format('m')}' D='{$date->format('d')}' H='{$date->format('h')}' MN='{$date->format('i')}' S='{$date->format('s')}' W='{$date->format('w')}'>{$date->format($defaultDateFormat)}</createddate>";
						$xml_result .= "<createdby><![CDATA[{$mail->getDocument()->getCreator()->getUserNameDnAbbreviated()}]]></createdby>";
						$xml_result .= "<parentdockey>{$mail->getDocument()->getId()}</parentdockey>";
						$xml_result .= "<attachcount>1</attachcount></document>";
					}
					$xml_result .= '</documents>';
					
					return $xml_result;
				}
			}
			catch (\Exception $e) {
				return false;
			}
		}
		elseif ($suffix == 'L')
		{
			try {
				$links = $em->getRepository('DocovaBundle:RelatedLinks')->findBy(array('document' => $document));
				if (!empty($links) && count($links) > 0)
				{
					$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
					$xml_result = '<documents>';
					foreach ($links as $url)
					{
						$xml_result .= "<document><customfield1><![CDATA[]]></customfield1>";
						$xml_result .=  "<customfield2><![CDATA[]]></customfield2>";
						$xml_result .= "<docid>{$url->getId()}</docid>";
						$xml_result .= "<rectype>URL</rectype>";
						$xml_result .= "<parentdocid></parentdocid>";
						$xml_result .= "<relateddata></relateddata>";
						$xml_result .= "<librarykey></librarykey>";
						$xml_result .= "<libraryname><![CDATA[]]></libraryname>";
						$xml_result .= "<folderid></folderid>";
						$xml_result .= "<foldername><![CDATA[]]></foldername>";
						$xml_result .= "<doctypekey></doctypekey>";
						$xml_result .= "<title><![CDATA[{$url->getDescription()}]]></title>";
						$xml_result .= "<status><![CDATA[]]></status>";
						$xml_result .= "<author><![CDATA[{$url->getCreatedBy()->getUserNameDnAbbreviated()}]]></author>";
						$xml_result .= "<datecreated val='0' Y='' M='' D='' H='' MN='' S='' W=''><![CDATA[{$url->getDateCreated()->format($defaultDateFormat)}]]></datecreated>";
						$xml_result .= "<version>.</version>";
						$xml_result .= "<description><![CDATA[{$url->getLinkUrl()}]]></description>";
						$xml_result .= '<typeicon>&#204;</typeicon><lastmodifieddate val="0" Y="" M="" D="" H="" MN="" S="" W=""></lastmodifieddate>';
						$xml_result .= "<active></active><selected>0</selected>";
						$xml_result .= "<lvInfo></lvInfo><lvparentdockey></lvparentdockey>";
						$xml_result .= "<lvparentdocid></lvparentdocid><archivedparentdockey></archivedparentdockey><docisdeleted></docisdeleted>";
						$xml_result .= "<from><![CDATA[]]></from>";
						$xml_result .= "<dockey></dockey><subject><![CDATA[]]></subject>";
						$xml_result .= "<createddate></createddate>";
						$xml_result .= "<createdby><![CDATA[]]></createdby>";
						$xml_result .= "<parentdockey>{$url->getDocument()->getId()}</parentdockey>";
						$xml_result .= "<comment><![CDATA[]]></comment>";
						$xml_result .= '<commentype>Comment</commentype>';
						$xml_result .= "<commentdate><![CDATA[]]></commentdate>";
						$xml_result .= "<attachcount>0</attachcount></document>";
					}
					$xml_result .= '</documents>';
						
					return $xml_result;
				}
			}
			catch (\Exception $e) {
				return false;
			}
		}
		elseif ($suffix == 'C')
		{
			try {
				$comments = $em->getRepository('DocovaBundle:DocumentComments')->getDocumentLogComments($document);
				if (!empty($comments) && count($comments) > 0)
				{
					$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
					$xml_result = '<documents>';
					foreach ($comments as $comm)
					{
						$date = $comm->getDateCreated();
						$xml_result .= "<document><customfield1><![CDATA[]]></customfield1>";
						$xml_result .=  "<customfield2><![CDATA[]]></customfield2>";
						$xml_result .= "<docid>{$comm->getId()}</docid>";
						$xml_result .= "<rectype>comment</rectype>";
						$xml_result .= "<parentdocid>{$comm->getDocument()->getId()}</parentdocid>";
						$xml_result .= "<relateddata></relateddata>";
						$xml_result .= "<librarykey></librarykey>";
						$xml_result .= "<libraryname><![CDATA[]]></libraryname>";
						$xml_result .= "<folderid></folderid>";
						$xml_result .= "<foldername><![CDATA[]]></foldername>";
						$xml_result .= "<doctypekey></doctypekey>";
						$xml_result .= "<title><![CDATA[]]></title>";
						$xml_result .= "<status><![CDATA[]]></status>";
						$xml_result .= "<author><![CDATA[]]></author>";
						$xml_result .= '<datecreated val="0" Y="" M="" D="" H="" MN="" S="" W=""><![CDATA[]]></datecreated>';
						$xml_result .= "<version>.</version>";
						$xml_result .= "<description><![CDATA[]]></description>";
						$xml_result .= '<typeicon>&#204;</typeicon><lastmodifieddate val="0" Y="" M="" D="" H="" MN="" S="" W=""></lastmodifieddate>';
						$xml_result .= "<active></active><selected>0</selected>";
						$xml_result .= "<lvInfo></lvInfo><lvparentdockey></lvparentdockey>";
						$xml_result .= "<lvparentdocid></lvparentdocid><archivedparentdockey></archivedparentdockey><docisdeleted></docisdeleted>";
						$xml_result .= "<from><![CDATA[]]></from>";
						$xml_result .= "<dockey></dockey><subject><![CDATA[]]></subject>";
						$xml_result .= "<createddate></createddate>";
						$xml_result .= "<createdby><![CDATA[{$comm->getCreatedBy()->getUserNameDnAbbreviated()}]]></createdby>";
						$xml_result .= "<parentdockey>{$comm->getDocument()->getId()}</parentdockey>";
						$xml_result .= "<comment><![CDATA[{$comm->getComment()}]]></comment>";
						$xml_result .= '<commentype>'.($comm->getCommentType() == 2 ? 'Life Cycle' : 'Comment').'</commentype>';
						$xml_result .= "<commentdate val='".strtotime($date->format('Y-m-d H:i:s'))."' Y='{$date->format('Y')}' M='{$date->format('m')}' D='{$date->format('d')}' H='{$date->format('h')}' MN='{$date->format('i')}' S='{$date->format('s')}' W='{$date->format('w')}'><![CDATA[{$date->format($defaultDateFormat)}]]></commentdate>";
						$xml_result .= "<attachcount>0</attachcount></document>";
					}
					$xml_result .= '</documents>';
			
					return $xml_result;
				}
			}
			catch (\Exception $e) {
				return false;
			}
		}
	}
	
	private function getPendingReviewItems()
	{
		$xml_result = false;
		$em = $this->getDoctrine()->getManager();
		try {
			$xml_result = '<documents>';
			$review_items = $em->getRepository('DocovaBundle:ReviewItems')->getUserPendingReviewItems($this->user->getId());
			if (!empty($review_items)) 
			{
				$today = new \DateTime();
				$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
				foreach ($review_items as $item) {
					$diff = (int)($today->diff($item->getStartDate())->format('%r%a'));
					$marker = '';
					if ($diff <= 10) {
						$marker = 'red';
					}
					elseif ($diff <= 30) {
						$marker = 'orange';
					}
					$marker = !empty($marker) ? "<span style='color:$marker'>=</span>" : '';
					$xml_result .= '<document><wfaction><![CDATA[Review]]></wfaction>';
					$xml_result .= '<docid>'.$item->getId().'</docid>';
					$xml_result .= '<rectype>doc</rectype>';
					$xml_result .= '<parentdocid>'.$item->getDocument()->getId().'</parentdocid>';
					$xml_result .= '<librarykey>'.$item->getDocument()->getFolder()->getLibrary()->getId().'</librarykey>';
					$xml_result .= '<folderid>'.$item->getDocument()->getFolder()->getId().'</folderid>';
					$xml_result .= '<subject><![CDATA['.$item->getDocument()->getDocTitle().']]></subject>';
					$xml_result .= '<wfstartdate val="'.$item->getStartDate()->getTimeStamp().'" Y="'.$item->getStartDate()->format('Y').'" M="'.$item->getStartDate()->format('m').'" D="'.$item->getStartDate()->format('d').'"';
					$xml_result .= ' W="'.$item->getStartDate()->format('W').'" H="'.$item->getStartDate()->format('h').'" MN="'.$item->getStartDate()->format('i').'" S="'.$item->getStartDate()->format('s').'">';
					$xml_result .= '<![CDATA['.$item->getStartDate()->format($defaultDateFormat).']]></wfstartdate>';
					$xml_result .= '<wfduedate>'.$item->getDueDate()->format($defaultDateFormat).'</wfduedate>';
					$xml_result .= "<typeicon>$marker</typeicon></document>";
				}
			}
			$xml_result .= '</documents>';
			return $xml_result;
		}
		catch(\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Fetch current user delegates
	 * 
	 * @return boolean|string
	 */
	private function getDelegatesByOwner()
	{
		$xml_result = false;
		try {
			$em = $this->getDoctrine()->getManager();
			$delegates = $em->getRepository('DocovaBundle:UserDelegates')->findBy(array('owner' => $this->user->getId()));
			if (!empty($delegates)) {
				$xml_result = '<documents>';
				foreach ($delegates as $d) {
					$xml_result .= '<document><docid>' . $d->getId() . '</docid>';
					if ($d->getApplicableLibraries()->count()) {
						$xml_result .= '<Library>';
						foreach ($d->getApplicableLibraries() as $library) {
							$xml_result .= $library->getId().',';
						}
						$xml_result = substr_replace($xml_result, '</Library>', -1);
					}
					else {
						$xml_result .= '<Library></Library>';
					}
					
					$xml_result .= '<Workflow><![CDATA['.$d->getApplicableWorkflows().']]></Workflow>';
					$xml_result .= '<StartDate><![CDATA['.$d->getStartDate()->format('m/d/Y').']]></StartDate>';
					$xml_result .= '<EndDate><![CDATA['.$d->getEndDate()->format('m/d/Y').']]></EndDate>';
					$xml_result .= '<Delegate><![CDATA['.$d->getDelegatedUser()->getUserNameDnAbbreviated().']]></Delegate>';
					$xml_result .= '<Notifications><![CDATA['.($d->getNotifications() ? 'Yes' : '').']]></Notifications>';
					$xml_result .= '<ReviewPolicies><![CDATA['.($d->getReviewPolicies() ? 'Yes' : '').']]></ReviewPolicies>';
					$xml_result .= '<Selected>0</Selected>';
					$xml_result .= '</document>';
				}
				$xml_result .= '</documents>';
			}
			return $xml_result;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Delete all folder (and subfolders) permanently
	 * 
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @return boolean
	 */
	private function deleteFolderPermanently($folder)
	{
		try {
			$em = $this->getDoctrine()->getManager();
			if ($folder->getChildren()->count() > 0)
			{
				foreach ($folder->getChildren() as $subfolder) {
					if (false === $this->deleteFolderPermanently($subfolder)) {
						throw new \Exception('Unable to delete subfolder(s)');
					}
				}
			}
			
			if (true === $this->transferLogs($folder, $em)) {
				if ($folder->getBookmarks()->count() > 0)
				{
					foreach ($folder->getBookmarks() as $value) {
						$em->remove($value);
					}
				}
				if ($folder->getLogs()->count() > 0)
				{
					foreach ($folder->getLogs() as $value) {
						$em->remove($value);
					}
				}
				if ($folder->getFavorites()->count() > 0)
				{
					foreach ($folder->getFavorites() as $value) {
						$em->remove($value);
					}
				}
				if ($folder->getSynchUsers()->count() > 0)
				{
					foreach ($folder->getSynchUsers() as $value) {
						$em->remove($value);
					}
				}
				unset($value);
			
				$folder->clearApplicableDocTypes();
				$perspectives = $em->getRepository('DocovaBundle:SystemPerspectives')->findBy(array('Built_In_Folder' => $folder->getId()));
				if (!empty($perspectives) && count($perspectives)) {
					foreach ($perspectives as $p) {
						$p->setBuiltInFolder(null);
					}
				}
				if ($folder->getDocuments()->count() > 0)
				{
					foreach ($folder->getDocuments() as $document) {
						$this->deleteDocumentPermanently($document);
					}
				}
				$em->remove($folder);
				$em->flush();
				return true;
			}
		}
		catch (\Exception $e) {
			$log = $this->get('logger');
			$log->error($e->getMessage());
			unset($log);
			return false;
		}
		return false;
	}
	
	/**
	 * Delete a document and all stub documents
	 *  
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @return boolean
	 */
	private function deleteDocumentPermanently($document)
	{
		try {
			$em = $this->getDoctrine()->getManager();
			if (true === $this->transferLogs($document, $em) ) 
			{
				if (!$document->getParentDocument()) 
				{
					$new_parent = $em->getRepository('DocovaBundle:Documents')->isParentDocument($document->getId());
					if (!empty($new_parent)) 
					{
						$new_parent->setParentDocument(null);
						$em->flush();
						$em->getRepository('DocovaBundle:Documents')->updateParentDocument($new_parent->getId(), $document->getId());
					}
				}
				if ($document->getAttachments()->count() > 0) {
					$this->removeAttachedFiles($document->getId());
					foreach ($document->getAttachments() as $attachment) {
						$em->remove($attachment);
					}
					unset($attachment);
				}
				if ($document->getTextValues()->count() > 0) 
				{
					foreach ($document->getTextValues() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getDateValues()->count() > 0) 
				{
					foreach ($document->getDateValues() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getNumericValues()->count() > 0) 
				{
					foreach ($document->getNumericValues() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getNameValues()->count() > 0) 
				{
					foreach ($document->getNameValues() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getGroupValues()->count() > 0)
				{
					foreach ($document->getGroupValues() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getBookmarks()->count() > 0) 
				{
					foreach ($document->getBookmarks() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getDocSteps()->count() > 0)
				{
					foreach ($document->getDocSteps() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getComments()->count() > 0)
				{
					foreach ($document->getComments() as $value) {
						$em->remove($value);
					}
				}

				if ($document->getAppDocComments()->count() > 0)
				{
					foreach ($document->getAppDocComments() as $value) {
						$em->remove($value);
					}
				}

				if ($document->getActivities()->count() > 0)
				{
					foreach ($document->getActivities() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getLogs()->count() > 0)
				{
					foreach ($document->getLogs() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getFavorites()->count() > 0)
				{
					foreach ($document->getFavorites() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getDiscussion()->count() > 0) 
				{
					foreach ($document->getDiscussion() as $value) {
						$em->remove($value);
					}
				}
				if ($document->getReviewItems()->count() > 0) 
				{
					foreach ($document->getReviewItems() as $value) {
						$em->remove($value);
					}
				}
				$document->clearReviewers();
				unset($value);
	
				$customACL = new CustomACL($this->container);
				$customACL->removeAllMasks($document);
				$customACL = null;
			
				$related_docs = $em->getRepository('DocovaBundle:RelatedDocuments')->findLinkedDocuments($document->getId());
				foreach ($related_docs as $rd)
				{
					if ($rd->getParentDoc()->getId() === $document->getId()) {
						$em->remove($rd);
					}
					elseif ($rd->getRelatedDoc()->getId() === $document->getId())
					{
						$rd->setRelatedDoc(null);
					}
				}
				$em->remove($document);
				$em->flush();
				return true;
			}
		}
		catch (\Exception $e) {
			$log = $this->get('logger');
			$log->error($e->getMessage() . ' On line: ' . $e->getLine());
			unset($log);
			return false;
		}
		return false;
	}
	
	/**
	 * Remove all attachments of particular document
	 * 
	 * @param integer $id
	 */
	private function removeAttachedFiles($id)
	{
		if (is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$id))
		{
			foreach (glob($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$id);
		}
	}
	
	/**
	 * Transfer all logs for the deleted document to the trashed log table
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents|\Docova\DocovaBundle\Entity\Folders $document
	 * @param \Doctrine\ORM\EntityManager $em
	 * @return boolean
	 */
	private function transferLogs($document, $em)
	{
		if ($document->getLogs()->count() > 0) 
		{
			try {
				foreach ($document->getLogs() as $log) {
					$trashed_log = new TrashedLogs();
					if ($document instanceof \Docova\DocovaBundle\Entity\Documents) {
						$trashed_log->setLogType(true);
						$trashed_log->setOwnerTitle($document->getDocTitle() ? $document->getDocTitle() : 'Document ID: '.$document->getId());
						if ($document->getFolder())
						{
							$trashed_log->setParentFolder($document->getFolder()->getFolderName());
							$trashed_log->setParentLibrary($document->getFolder()->getLibrary());
						}
						else {
							$trashed_log->setParentLibrary($document->getApplication());
						}
					}
					elseif ($document instanceof \Docova\DocovaBundle\Entity\Folders) {
						$trashed_log->setLogType(false);
						$trashed_log->setOwnerTitle($document->getFolderName());
						$trashed_log->setParentFolder($document->getParentfolder() ? $document->getParentfolder()->getFolderName() : 'ROOT');
						$trashed_log->setParentLibrary($document->getLibrary());
					}
					$trashed_log->setDateCreated(new \DateTime());
					$trashed_log->setLogDetails($log->getLogDetails());
					
					$em->persist($trashed_log);
					$em->flush();
				}
				$trashed_log = new TrashedLogs();
				if ($document instanceof \Docova\DocovaBundle\Entity\Documents) 
				{
					$trashed_log->setLogType(true);
					$trashed_log->setOwnerTitle($document->getDocTitle() ? $document->getDocTitle() : 'Document ID: '.$document->getId());
					if ($document->getFolder())
					{
						$trashed_log->setParentFolder($document->getFolder()->getFolderName());
						$trashed_log->setParentLibrary($document->getFolder()->getLibrary());
						$trashed_log->setLogDetails('Deleted document from library.');
					}
					else {
						$trashed_log->setParentLibrary($document->getApplication());
						$trashed_log->setLogDetails('Deleted document from application.');
					}
				}
				else {
					$trashed_log->setLogType(false);
					$trashed_log->setOwnerTitle($document->getFolderName());
					$trashed_log->setParentFolder($document->getParentfolder() ? $document->getParentfolder()->getFolderName() : 'ROOT');
					$trashed_log->setParentLibrary($document->getLibrary());
					$trashed_log->setLogDetails('Deleted folder from library.');
				}
				$trashed_log->setDateCreated(new \DateTime());
				$em->persist($trashed_log);
				$em->flush();
				return true;
			}
			catch (\Exception $e) {
				$log = $this->get('logger');
				$log->error($e->getMessage());
				$log = null;
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Fetch group members
	 * 
	 * @param string $grouname
	 * @param boolean $return_members
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $findme
	 * @return \Doctrine\Common\Collections\ArrayCollection|boolean
	 */
	private function fetchGroupMembers($grouname, $return_members = false, $findme = null)
	{
		$em = $this->getDoctrine()->getManager();
		$type = false === strpos($grouname, '/DOCOVA') ? true : false;
		$name = str_replace('/DOCOVA', '', $grouname);
		$role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $name, 'groupType' => $type));
		if (!empty($role)) {
			if (empty($return_members) && empty($findme)) 
			{
				return $role;
			}
			if ($return_members === true) 
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
	 * Search for a user inside a collection object
	 * 
	 * @param \Doctrine\Common\Collections\ArrayCollection $collection
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 * @param boolean $isCompletedBy [optional(false)]
	 * @return boolean
	 */
	private function searchUserInCollection($collection, $user, $return_group = false, $isCompletedBy = false)
	{
		foreach ($collection as $record) {
			if ($isCompletedBy === false) 
			{
				if ($record->getAssignee()->getId() === $user->getId()) {
					return ($return_group === true ? array('id' => $user->getId(), 'gType' => $record->getGroupMember(), 'record' => $record->getId()) : true);
				}
			}
			else {
				if ($record->getCompletedBy()->getId() === $user->getId()) {
					return ($return_group === true ? array('id' => $user->getId(), 'gType' => $record->getGroupMember(), 'record' => $record->getId()) : true);
				}
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
	 * Validate string as XML
	 * 
	 * @param string $content
	 * @return boolean
	 */
	private function isValidXML($content)
	{
		if (empty($content))
			return false;
		libxml_use_internal_errors(true);
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->loadXML($content);
		$error = libxml_get_errors();
		libxml_use_internal_errors(false);
		return empty($error) ? true :false;
	}

	/** 
	 * Gets the xml node value or blank if not here
	 * @param string $post
	 * @param string nodetoget
	 * @return string
	 */
	private function getNodeValue($post, $nodename){
		$t = $post->getElementsByTagName($nodename);
		if ( $t->length > 0)
			return $t->item(0)->nodeValue;
		else
			return "";
	}
	
	
	/**
	 * Generates a valid datetime object from a datetime string or returns null
	 *
	 * @param string $date_string
	 * @return NULL|\DateTime
	 */
	private function getValidDateTimeValue($date_string)
	{
		if ($date_string instanceof \DateTime) {
			return $date_string;
		}
		$format = $this->global_settings->getDefaultDateFormat();
		$format = str_replace(array('YYYY', 'MM', 'DD'), array('Y', 'm', 'd'), $format);
		$parsed = date_parse($date_string);
		$is_period = (false === stripos($date_string, ' am') && false === stripos($date_string, ' pm')) ? false : true;
		$time = (false !== $parsed['hour'] && false !== $parsed['minute']) ? ' H:i:s' : '';
		$time .= !empty($time) && $is_period === true ? ' A' : '';
		$value = \DateTime::createFromFormat($format.$time, $date_string);
		$format = $parsed = $time = $is_period = null;
	
		return empty($value) ? null : $value;
	}	
	
}
