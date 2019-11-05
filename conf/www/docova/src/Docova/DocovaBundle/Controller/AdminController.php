<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Docova\DocovaBundle\Entity\GlobalSettings;
//use Docova\DocovaBundle\Entity\WorkflowSteps;
//use Docova\DocovaBundle\Entity\Workflow;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Entity\Libraries;
use Docova\DocovaBundle\Entity\WorkflowStepActions;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserProfile;
use Docova\DocovaBundle\Security\User\adLDAP;
use Docova\DocovaBundle\Entity\SystemMessages;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Docova\DocovaBundle\Entity\FileTemplates;
use Docova\DocovaBundle\Entity\ViewColumns;
use Docova\DocovaBundle\Entity\DocumentTypes;
use Docova\DocovaBundle\Entity\ReviewPolicies;
use Docova\DocovaBundle\Entity\DocTypeSubforms;
use Docova\DocovaBundle\Entity\ArchivePolicies;
use Docova\DocovaBundle\Entity\HtmlResources;
use Docova\DocovaBundle\Entity\CustomSearchFields;
use Docova\DocovaBundle\Entity\Activities;
use Docova\DocovaBundle\Entity\UserRoles;
use Symfony\Component\HttpFoundation\File\File;
//use Doctrine\Common\Collections\ArrayCollection;
use Docova\DocovaBundle\Extensions\SessionHelpers;
use Docova\DocovaBundle\Entity\FileResources;
use Docova\DocovaBundle\Entity\DataSources;
use Docova\DocovaBundle\Entity\DataViews;

class AdminController extends Controller
{
	protected $user;
	protected $global_settings;
	protected $UPLOAD_FILE_PATH;

	private function initialize($requested_user = null)
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();

		if (!is_null($requested_user)) {
			if ($this->user->getId() != $requested_user) {
				throw new AccessDeniedException();
			}
		}
		elseif (false == $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
		{
			$libraries = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Libraries')->createQueryBuilder('L')
				->select('L.id')
				->where('L.Trash = false')
				->getQuery()
				->getArrayResult();
			$isLibraryAdmin = false;
			if (!empty($libraries)) {
				$roles = $this->user->getRoles();
				foreach ($libraries as $libid) {
					foreach ($roles as $r) {
						if ($r == 'ROLE_LIBADMIN' . $libid['id']) {
							$isLibraryAdmin = true;
							break;
						}
					}
					if ($isLibraryAdmin === true)
					{
						break;
					}
				}
			}
			if ($isLibraryAdmin !== true) {
				throw new AccessDeniedException();
			}
		}

		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];

		$this->UPLOAD_FILE_PATH = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root').DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage' : $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
	}
	
	public function newSystemMessagesAction(Request $request)
	{
		$this->initialize();
		$message = '';
		
		if ($request->isMethod('POST'))
		{
			$em = $this->getDoctrine()->getManager();
			$message_name = $request->request->get('MessageName');
			$link_type = $request->request->get('LinkOption');
			$link_type = (empty($link_type) || $link_type == 'w') ? false : true;
			$senders = $request->request->get('Principal');
			$subject = $request->request->get('Subject');
			$content = $request->request->get('Body');
			
			if (!empty($senders))
			{
				$groups = array();
				$senders = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $senders);
				foreach ($senders as $key => $value)
				{
					if (false !== $user = $this->findUserAndCreate($value))
					{
						$senders[$key] = $user;
					}
					else {
						$groups[] = $value;
					}
				}
			}
			
			if (empty($message_name) || empty($subject) || empty($content))
			{
				throw $this->createNotFoundException('Data form is incomplete; check required information.');
			}
			
			try {
				$system_message = new SystemMessages();
				$system_message->setLinkType($link_type);
				$system_message->setMessageName($message_name);
				$system_message->setSubjectLine($subject);
				$system_message->setSystemic(true);
				$system_message->setMessageContent($content);
	
				if (!empty($senders))
				{
					foreach ($senders as $user)
					{
						$system_message->addSenders($user);
					}
				}
				
				if (!empty($groups)) 
				{
					$system_message->setSenderGroups($groups);
				}
	
				$em->persist($system_message);
				$em->flush();
				
				$message = '<span style="color:green">Successfully submitted.</span>';
			}
			catch (\Exception $e)
			{
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
		}

		return $this->render('DocovaBundle:Default:SystemMessagesAdmin.html.twig', array(
				'user' => $this->user,
				'message' => $message
			));
	}

	public function showAdminContentAction(Request $request)
	{
		$this->initialize();
		$view_name = $request->query->get('viewname');
		$em = $this->getDoctrine()->getManager();
		if ($view_name == 'wAdminWorkflow')
		{
			$workflows = $em->getRepository('DocovaBundle:Workflow')->findBy(array('Trash' => false));
			
			return $this->render('DocovaBundle:Default:wAdminContent.html.twig', array(
					'user' => $this->user,
					'workflows' => $workflows
				));
		}
	}
	
	public function workflowStepAction(Request $request, $step)
	{
		$this->initialize();
		$message = '';
		$em = $this->getDoctrine()->getManager();
		$step = $em->getRepository('DocovaBundle:WorkflowSteps')->findOneBy(array('id' => $step, 'Trash' => false));
		if (empty($step))
		{
			throw $this->createNotFoundException('Unspecified source workflow step.');
		}
		
		$system_messages = $em->getRepository('DocovaBundle:SystemMessages')->findBy(array('Systemic' => false, 'Trash' => false), array('Message_Name' => 'ASC'));
		$step_actions = array();
		if ($step->getActions()->count() > 0)
		{
			foreach ($step->getActions() as $action)
			{
				$step_actions[$action->getActionType(true)] = $action;
			}
		}
		
		if ($request->isMethod('POST'))
		{
			if ($request->request->get('wfEnableActivateMsg'))
			{
				$message_code	= $request->request->get('wfActivateMsg');
				$to_notify_who	= $request->request->get('wfActivateNotifyParticipants');
				$to_notify_list	= $request->request->get('wfActivateNotifyList');
				
				if ($message_code === 'ACTIVATE')
				{
					$message_code = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => true, 'Trash' => false, 'Message_Name' => 'DEFAULT_'.$message_code));
					if (empty($message_code))
					{
						throw $this->createNotFoundException('Unspecified default activate system message.');
					}
				}
				else {
					$message_code = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => false, 'Trash'=> false, 'id' => $message_code));
					if (empty($message_code))
					{
						throw $this->createNotFoundException('Unspecified source activate system message.');
					}
				}
				
				$to_options = 0;
				if (is_array($to_notify_who))
				{
					if (in_array('P', $to_notify_who) === true) {
						$to_options= 1;
					}
					
					if (in_array('A', $to_notify_who) === true) {
						$to_options = (!empty($to_options)) ? 3 : 2;
					}
				}
				
				try {
					$step_action = new WorkflowStepActions();
					$step_action->setActionType(1);
					$step_action->setSendMessage(true);
					$step_action->setToOptions($to_options);
					$step_action->setMessage($message_code);
					$step_action->setStep($step);
					
					$em->persist($step_action);
					$groups = array();
								
					if (!empty($to_notify_list))
					{
					    $to_notify_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $to_notify_list);
						foreach ($to_notify_list as $index => $user)
						{
							if (false !== $user = $this->findUserAndCreate($user))
							{
								$step_action->addSendTo($user);
							}
							elseif (!empty($to_notify_list[$index])) {
								$groups[] = $to_notify_list[$index];
							}
						}
					}
					
					$step_action->setSenderGroups($groups);
					$em->flush();
					$message = '<span style="color:green">Successfully saved actions.</span>';
				}
				catch (\Exception $e)
				{
					$message = 'Could not save step action because of the following issue(s):<br>'. $e->getMessage();
				}
			}
		}
		
		return $this->render('DocovaBundle:Default:WorkflowDefinitionItem.html.twig', array(
				'user' => $this->user,
				'message' => $message,
				'system_messages' => $system_messages,
				'step_actions' => $step_actions,
				'step' => $step
			));
	}
	
	public function workflowReadAction(Request $request)
	{
		$step = $request->query->get('step_id');
		if (empty($step)) 
		{
			throw $this->createNotFoundException('Workflow Item ID is missed.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$step = $em->getRepository('DocovaBundle:WorkflowSteps')->findOneBy(array('id' => $step, 'Trash' => false));
		if (empty($step))
		{
			throw $this->createNotFoundException('Unspecified workflow item source.');
		}

		$system_messages = $em->getRepository('DocovaBundle:SystemMessages')->findBy(array('Systemic' => false, 'Trash' => false), array('Message_Name' => 'ASC'));
		$step_actions = array();
		if ($step->getActions()->count() > 0)
		{
			foreach ($step->getActions() as $action)
			{
				$step_actions[$action->getActionType(true)] = $action;
			}
		}
		
		$response = new Response();
		$response->setContent($this->renderView('DocovaBundle:Admin:WorkflowItemRead.html.twig', array(
				'step' => $step,
				'step_actions' => $step_actions,
				'system_messages' => $system_messages
			)));
		
		return $response;
	}

/***************************************************** DV STUFFS ********************************************************/
	public function adminAction(){
		$this->initialize();
		return $this->render('DocovaBundle:Admin:admin.html.twig',  array(
				'user' => $this->user
		));
	}
	
	public function showTopTabsAction() {
		$this->initialize();
		return $this->render('DocovaBundle:Admin:adminTopTabs.html.twig', array(
			'user' => $this->user
		));
	}
	
	/*
	 * To delete admin documents
	 */
	public function adminViewServicesAction(Request $request)
	{
//		try{
			$this->initialize();
			// get entity manager
			$em = $this->getDoctrine()->getManager();
			// get xml post request
			$post_req = $request->getContent();
			$post_xml = new \DOMDocument();
			$post_xml->loadXML($post_req);
			
			$view_name = trim($request->query->get('vw'));
			if (in_array($view_name, array('wAdminFileResources', 'wAdminDataSources', 'wAdminDataViews', 'wAdminCheckedOutFiles', 'wAdminGroups', 'wAdminDeletedUsers', 'wAdminUserProfiles'))) {
				$securityContext = $this->container->get('security.authorization_checker');
				if (!$securityContext->isGranted('ROLE_ADMIN')) {
					throw new AccessDeniedException();
				}
			}
			
			$response=new Response();
			$response->headers->set('Content-Type', 'text/xml');
			
			switch ($view_name) {
				case 'wAdminLibraries':
					$response->setContent($em->getRepository('DocovaBundle:Libraries')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminDocTypes':
					$response->setContent($em->getRepository('DocovaBundle:DocumentTypes')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminWorkflow':
					$response->setContent($em->getRepository('DocovaBundle:Workflow')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminReviewPolicies':
					$response->setContent($em->getRepository('DocovaBundle:ReviewPolicies')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminArchivePolicies':
					$response->setContent($em->getRepository('DocovaBundle:ArchivePolicies')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminFileTemplates':
					$response->setContent($em->getRepository('DocovaBundle:FileTemplates')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminFileResources':
					$response->setContent($em->getRepository('DocovaBundle:FileResources')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminDataSources':
					$response->setContent($em->getRepository('DocovaBundle:DataSources')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminDataViews':
					$response->setContent($em->getRepository('DocovaBundle:DataViews')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminViewColumns':
					$response->setContent($em->getRepository('DocovaBundle:ViewColumns')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminCustomSearchFields':
					$response->setContent($em->getRepository('DocovaBundle:CustomSearchFields')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminSystemMessages':
					$response->setContent($em->getRepository('DocovaBundle:SystemMessages')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminUserProfiles':
					$response->setContent($em->getRepository('DocovaBundle:UserAccounts')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminHTMLResource':
					$response->setContent($em->getRepository('DocovaBundle:HtmlResources')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminActivities':
					$response->setContent($em->getRepository('DocovaBundle:Activities')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminCheckedOutFiles':
					$response->setContent($em->getRepository('DocovaBundle:AttachmentsDetails')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminGroups':
					$response->setContent($em->getRepository('DocovaBundle:UserRoles')->deleteSelectedDocuments($post_xml));
					break;
				case 'wAdminDeletedUsers':
					$allowed = true;
					if ($this->global_settings->getNumberOfUsers() > 0)
					{
						$user_count = $em->getRepository('DocovaBundle:UserAccounts')->createQueryBuilder('U')
							->select('COUNT(U.id)')
							->where('U.Trash = false')
							->getQuery()
							->getSingleScalarResult();
					
						if ($this->global_settings->getNumberOfUsers() <= $user_count)
						{
							$allowed = false;
						}
					}
					if ($allowed === true) {
						$response->setContent($em->getRepository('DocovaBundle:UserAccounts')->reinstateUser($post_xml));
					}
					else {
						$response->setContent('<Results><Result ID="Status">FAILED</Result><Result ID="ErrMsg">Your DOCOVA license provides access for '.$this->global_settings->getNumberOfUsers().' users. This limit has been reached.</Result></Results>');
					}
					break;
			}
//		}catch(\Exception $e){
//			$message = 'Could not delete documents <br />'. $e->getMessage();
//		}
		return $response;
	}
	
	/*
	 * Handles web admin left navigation clicks
	* e.g /../OpenFolderView.xml?OpenPage&vw=wAdminFileTemplates
	*/
	public function openFolderViewAction(Request $request){
		$responseXml=new Response();
		$responseXml->headers->set("content-type", "application/json");
	
		$view_name = (trim($request->query->get('vw'))) ? $request->query->get('vw') : 'wAdminLibraries';
		if (in_array($view_name, array('wAdminFileResources', 'wAdminApplications', 'wAdminDataSources', 'wAdminDataViews', 'wAdminCheckedOutFiles', 'wAdminGroups', 'wAdminDeletedUsers', 'wAdminUserProfiles'))) {
			$securityContext = $this->container->get('security.authorization_checker');
			if (!$securityContext->isGranted('ROLE_ADMIN')) {
				throw new AccessDeniedException();
			}
		}
		
		$em = $this->getDoctrine()->getManager();
		$icon_path = $this->get('assets.packages')->getUrl('bundles/docova/images/icons/');
		$sorting = null;
		if (trim($request->query->get('sort')) && trim($request->query->get('type'))) 
		{
			$sort = explode('~', $request->query->get('sort'));
			$type = explode('~', $request->query->get('type'));
			for ($x = 0; $x < count($sort); $x++) {
				$sorting[$sort[$x]] = $type[$x];
			}
			unset($sort, $type);
		}
		switch ($view_name) {
			case 'wAdminLibraries':
			case 'wAdminApplications':
				$orderBy = array();
				if (!empty($sorting)) 
				{
					foreach ($sorting as $key => $value) {
						if ($key == 'title') {
							$orderBy['Library_Title'] = 'ASC';
						}
					}
				}
				else {
					$orderBy['Library_Title'] = 'ASC';
				}
				$documents = $em->getRepository('DocovaBundle:Libraries')->findBy(array('Trash' => false, 'isApp' => ($view_name == 'wAdminLibraries' ? false : true)), $orderBy);
				break;
			case 'wAdminDocTypes':
				$data = $em->getRepository('DocovaBundle:DocumentTypes')->getDataXML($icon_path, null, $sorting); 
				break;
			case 'wAdminWorkflow':
				$sorting['Workflow_Name'] = 'ASC';
				$data = $em->getRepository('DocovaBundle:Workflow')->getDataXML($icon_path, array('Trash' => false, 'application' => null), $sorting);
				break;
			case 'wAdminReviewPolicies':
				$data = $em->getRepository('DocovaBundle:ReviewPolicies')->getDataXML(array(), $sorting);
				break;
			case 'wAdminArchivePolicies':
				$data = $em->getRepository('DocovaBundle:ArchivePolicies')->getDataXML(array(), $sorting);
				break;				
			case 'wAdminFileTemplates':
				$data = $em->getRepository('DocovaBundle:FileTemplates')->getDataXML(array(), $sorting);
				break;
			case 'wAdminFileResources':
				$data = $em->getRepository('DocovaBundle:FileResources')->getDataXML(array(), $sorting);
				break;
			case 'wAdminDataSources':
				$data = $em->getRepository('DocovaBundle:DataSources')->getDataXML(array(), $sorting);
				break;
			case 'wAdminDataViews':
				$data = $em->getRepository('DocovaBundle:DataViews')->getDataXML(array(), $sorting);
				break;
			case 'wAdminViewColumns':
				$data = $em->getRepository('DocovaBundle:ViewColumns')->getDataXML($icon_path, array(), $sorting);
				break;
			case 'wAdminCustomSearchFields':
				$data = $em->getRepository('DocovaBundle:CustomSearchFields')->getDataXML(array(), $sorting);
				break;
			case 'wAdminSystemMessages':
				$sorting = empty($sorting) ? array('Message_Name' => 'ASC') : $sorting;
				$data = $em->getRepository('DocovaBundle:SystemMessages')->getDataXML(array('Trash' => false, 'Systemic' => false), $sorting);
				break;
			case 'wAdminUserProfiles':
				$data = $em->getRepository('DocovaBundle:UserAccounts')->getDataXML(array(), $sorting);
				break;
			case 'wAdminUserSessions':
				$data = SessionHelpers::getUserSessions($em,false,"array");
				break;
			case 'wAdminGroups':
				$data = $em->getRepository('DocovaBundle:UserRoles')->getDataXML(array(), $sorting);
				break;
			case 'wAdminHTMLResource':
				$data = $em->getRepository('DocovaBundle:HtmlResources')->getDataXML(array(), $sorting);
				break;
			case 'wAdminActivities':
				$data = $em->getRepository('DocovaBundle:Activities')->getDataXML(array(), $sorting);
				break;
			case 'wAdminCheckedOutFiles':
				$data = $em->getRepository('DocovaBundle:AttachmentsDetails')->getDataXML(array(), $sorting);
				break;
			case 'wAdminDeletedUsers':
				$data = $em->getRepository('DocovaBundle:UserAccounts')->getDeletedUsers($icon_path, array(), $sorting);
				break;
			//----------------------------------- Library ----------------------------------------
			case 'wAdminSubscribedLibraries':
				$data = array();
				$conn = $em->getConnection();
				$query = 'SELECT S.identifier, L.id, L.Library_Title FROM acl_security_identities AS S JOIN acl_entries AS E ON E.security_identity_id = S.id JOIN acl_object_identities AS O ON E.object_identity_id = O.id INNER JOIN tb_libraries AS L ON O.object_identifier = L.id ';
				$query .= 'WHERE E.class_id = 1 AND E.mask = 8 AND S.username = 1 AND L.Trash = 0 ORDER BY L.id';
				$result = $conn->fetchAll($query);
				if (!empty($result[0]))
				{
					$this->initialize();
					foreach ($result as $libDoc)
					{
						$username = str_replace('Docova\DocovaBundle\Entity\UserAccounts-', '', $libDoc['identifier']);
						$query = "SELECT U.id, U.user_name_dn_abbreviated, P.Display_Name FROM tb_user_accounts AS U JOIN tb_user_profile AS P ON U.id = P.User_Id WHERE U.User_Account_Name = ?";
						$user = $conn->fetchAll($query, array($username));
						if (!empty($user[0]))
						{
							$data[] = array(
								'dockey' => $user[0]['id'].",".$libDoc['id'],
								'username' => $this->global_settings->getUserDisplayDefault() ? $user[0]['Display_Name'] : $user[0]['user_name_dn_abbreviated'],
								'libraryname' => $libDoc['Library_Title'],
								'librarykey' => $libDoc['id'],
								'status' => 'vwicn083.gif'
							);
						}
					}
					$libDoc = $user = $username = null;
				}
				$result = null;
				break;
		}
		if (!empty($documents))
		{
			$data = array();
			foreach ($documents as $doc)
			{
				$data[] = array(
					'dockey' => $doc->getId(),
					'title' => $doc->getLibraryTitle(),
					'path' => $this->generateUrl('docova_homepage'),
					'status' => ($doc->getStatus() ? 'vwicn083.gif' : 'vwicn080.gif'),
					'server' => $request->getHost()
				);
			}
		}
		elseif (empty($data)) {
			$data = array();
		}
		$data = json_encode($data);
	
		$responseXml->setContent($data);
		return $responseXml;
	
	}
	
	public function wAdminProfileAction(Request $request)
	{
		$securityContext = $this->container->get('security.authorization_checker');
		if (!$securityContext->isGranted('ROLE_ADMIN')) {
			throw new AccessDeniedException();
		}
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$global_settings = (!empty($global_settings[0])) ?  $global_settings[0] : '';
		$is_new = $success = false;
		
//		$administrators = $em->getRepository('DocovaBundle:UserAccounts')->findByRole('ROLE_ADMIN');
		
		if ($request->isMethod('POST'))
		{
			$req 	 	 = $request->request;
			$title 		 = (trim($req->get('ApplicationTitle'))) ? $req->get('ApplicationTitle') : 'DOCOVA Enterprise Content Manager';
			$retention	 = (trim($req->get('EventCleanupDelay'))) ? $req->get('EventCleanupDelay') : 90;
			$redirect_to = ($req->get('LogoutRedirect') && trim($req->get('RedirectTo'))) ? $req->get('RedirectTo') : null;
			$file_types	 = (trim($req->get('LaunchLocally'))) ? $req->get('LaunchLocally') : 'doc,docx,docm,xls,xlsx,xlsm,ppt,pptx,pptm,csv,tif';
			$ld_exclude	 = (trim($req->get('entryLocalDeleteExcludes'))) ? $req->get('entryLocalDeleteExcludes') : null;
			$ssl_enabled = trim($req->get('SSL')) == '1' ? true : false;
			$http_port   = intval(trim($req->get('HTTPPort')));
			$http_port   = (empty($http_port) || ($ssl_enabled && $http_port == 443) || (!$ssl_enabled && $http_port == 80)) ? null : $http_port ;
			$server_parts = explode(":", (trim($req->get('HPWebServer')) ? $req->get('HPWebServer') : $_SERVER['SERVER_NAME']));
			$run_server  = $server_parts[0];
			$ldap_path	 = (trim($req->get('LDAP_Authentication')) == 1 && trim($req->get('LDAP_Directory'))) ? $req->get('LDAP_Directory') : null;
			$ldap_base_dn= trim($req->get('ldap_base_dn'));
			$docova_base_dn= trim($req->get('docova_base_dn'));
			
			$license = trim($req->get('LicenseCode'));
			$segments = explode('-', $license);
			if (empty($license) || strlen($license) != 29 || count($segments) != 5) 
			{
				throw $this->createNotFoundException('Invalid License Code has been entered!');
			}
			
			$nou = ltrim($this->decodeSegment($segments[2]), '0');
			$nou = empty($nou) ? 0 : $nou;
			$exp = is_numeric($segments[3]) ? null : $this->decodeSegment($segments[3]);
			if (!is_numeric($nou)) {
				throw $this->createNotFoundException('Invalid License Code has been entered!');
			}
			
			if (!empty($exp)) 
			{
				try {
					if (strlen(substr($exp, 3)) > 2) {
						$exp = \DateTime::createFromFormat('z Y', substr($exp, 0, 3).' '.substr($exp, 3));
					}
					else {
						$exp = \DateTime::createFromFormat('z y', substr($exp, 0, 3).' '.substr($exp, 3));
					}
				}
				catch (\Exception $e) {
					throw $this->createNotFoundException('Invalid License Code has been entered!');
				}
			}

			$appFeatures = '';
			$features = $this->decodeSegment($segments[4]);
			if (!is_numeric($features)) {
				throw $this->createNotFoundException('Invalid License Code has been entered!');
			}
			if (!empty($features))
			{
				$array = array(1 => 'App Importer for AppBuilder', 2 => 'Public File Access', 4 => 'DOCOVA Enterprise Integrator (DEI)', 8 => 'DOCOVA Web Services (DWS)');
				foreach ($array as $key => $value) {
					if (array_key_exists(($key & $features), $array)) {
						$appFeatures .= $value . ', ';
					}
				}
			}
			$appFeatures = !empty($appFeatures) ? substr_replace($appFeatures, '', -2) : null;

			if (empty($global_settings))
			{
				$is_new = true;
				$global_settings = new GlobalSettings();
			}
			
			$global_settings->setApplicationTitle($title);
			$global_settings->setStartupOption($req->get('StartupOption') == 'W' ? false : true);
			$global_settings->setUserDisplayDefault($req->get('DisplayDefault') == 1 ? true : false);
			$global_settings->setDefaultDateFormat(in_array($req->get('defaultDateFormat'), array('MM/DD/YYYY','DD/MM/YYYY','YYYY/MM/DD','YYYY/DD/MM')) ? $req->get('defaultDateFormat') : 'MM/DD/YYYY');
			$global_settings->setChromeOptions($req->get('ChromeOptions'));
			$global_settings->setErrorLogLevel($req->get('ErrorLogLevel'));
			$global_settings->setErrorLogEmail($req->get('ErrorLogEmail'));
			$global_settings->setUserProfileCreation($req->get('UserProfileCreation') ? true : false);
			$global_settings->setPromptToClose($req->get('PromptToCloseWindow') ? true : false);
			$global_settings->setDefaultFolderPaneWidth(intval(trim($req->get('DefaultFolderPaneWidth'))) ? intval(trim($req->get('DefaultFolderPaneWidth'))) : null);
			$global_settings->setLogRetention($retention);
			$global_settings->setRedirectTo($redirect_to);
			$global_settings->setCompanyName(trim($req->get('CompanyName')) ? trim($req->get('CompanyName')) : null);
			$global_settings->setLicenseCode($license);
			$global_settings->setNumberOfUsers((int)$nou);
			$global_settings->setExpiryDate($exp);
			$global_settings->setLicensedFeatures($appFeatures);
			$global_settings->setNotesMailServer(trim($req->get('notesMailServer')) ? $req->get('notesMailServer') : null);
			$global_settings->setCentralLocking($req->get('EnableCentralizedLocking') ? true : false);
			$global_settings->setRecordManagement($req->get('RMEEnable') ? true : false);
			$global_settings->setDuplicateEmailCheck($req->get('DuplicateEmailCheck') == 1 ? true : false);
			$global_settings->setEnableElastica($req->get('EnableElasticSearch') ? true : false);
			$global_settings->setEnableDomainSearch($req->get('EnableDomainSearch') ? true : false);
			$global_settings->setLaunchLocally($file_types);
			$global_settings->setLocalDelete($req->get('LocalDelete') ? true : false);
			$global_settings->setLocalDeleteExclude($ld_exclude);
			$global_settings->setSslEnabled($ssl_enabled);
			$global_settings->setHttpPort($http_port);
			$global_settings->setRunningServer($run_server);
			$global_settings->setCleanupTime(new \DateTime($req->get('CleanupTimeHour').' '.$req->get('CleanupTimeAMPM')));
			$global_settings->setArchiveTime(new \DateTime($req->get('ArchiveTimeHour').' '.$req->get('ArchiveTimeAMPM')));
			$global_settings->setNotificationTime(new \DateTime($req->get('NotificationTimeHour').' '.$req->get('NotificationTimeAMPM')));
			$global_settings->setLimitFolderPathLength($req->get('LimitFolderPathLength') ? true : false);
			$global_settings->setFolderPathLength($req->get('LimitFolderPathLength') && intval($req->get('FolderPathLength')) ? intval($req->get('FolderPathLength')) : null);
			$global_settings->setLDAPAuthentication(($req->get('LDAP_Authentication') == 1 && $req->get('LDAP_Directory')) ? true : false);
			$global_settings->setSsoAuthentication(trim($req->get('SSO_Authentication')) == 1 ? true : false);
			//-------- DOCOVA Deployment Settings ----------
			$servers = $req->get('DestServerPath');
			$ports = $req->get('DestServerPort');
			if (!empty($servers) && count($servers)) {
				$len = count($servers);
				for ($x = 0; $x < $len; $x++) {
					$servers[$x] = $servers[$x].'::'.$ports[$x];
				}
			}
			else {
				$servers = null;
			}
			
			$global_settings->setDeployServerPath($servers);
			//-------- LDAP settings --------------------
			$global_settings->setLDAPDirectory($ldap_path);
			$global_settings->setLDAPPort(trim($req->get('LDAP_Port')) ? $req->get('LDAP_Port') : 389);
			$global_settings->setLdapBaseDn($ldap_base_dn);
			$global_settings->setLdapDirectoryName(trim($req->get('ldapName')) ? trim($req->get('ldapName')) : 'LDAP/AD');
			//----------- DOCOVA base DN --------
			$global_settings->setDocovaBaseDn($docova_base_dn);
			
			//db authentication settings
			$global_settings->setDBAuthentication($req->get('DOCOVA_Authentication') ? true : false);
			
			if ($is_new === true) {
				$em->persist($global_settings);
			}
			else {
				if ($global_settings->getDefaultPrincipal()->count() > 0)
				{
					foreach ($global_settings->getDefaultPrincipal() as $dp)
					{
						$global_settings->removeDefaultPrincipal($dp);
					}
					$em->flush();
				}
			}

			$groups = array();
			if (trim($req->get('Principal'))) 
			{
			    $principals = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $req->get('Principal'));
				foreach ($principals as $dp) {
					if (false !== $user = $this->findUserAndCreate($dp))
					{
						$global_settings->addDefaultPrincipal($user);
					}
					else {
						$groups[] = $dp;
					}
				}
			}
			$global_settings->setPrincipalGroups($groups);
			
			$em->flush();
			
			return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$global_settings->getId().'&loadaction=reloadlastopenedview&closeframe=true');
		}
		
		return $this->render('DocovaBundle:Admin:adminGlobalSettings.html.twig', array(
				'user' => $this->user,
				'global_settings' => $global_settings,
				'administrators_list' => null,
				'submit_successfully' => $success
			));
	}
	
	public function libraryAction(Request $request)
	{
		$this->initialize();
		
		$em = $this->getDoctrine()->getManager();
		$doctypes = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false), array('Doc_Name' => 'ASC'));
		$libTemplates = $em->getRepository('DocovaBundle:Libraries')->findByInArray(array('L.id', 'L.Library_Title'), array('Is_Template' => true));
		if (empty($libTemplates[0])) {
			$libTemplates = array();
		}
		
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
			$title = $req->get('Title');
			$recycle = ($req->get('TrashCleanupDelay')) ? $req->get('TrashCleanupDelay') : 30;
			$archive = ($req->get('ArchiveCleanupDelay')) ? $req->get('ArchiveCleanupDelay') : 730;
			$log_delay = ($req->get('LogCleanupDelay')) ? $req->get('LogCleanupDelay') : 2192;
			$event_delay = ($req->get('EventCleanupDelay')) ? $req->get('EventCleanupDelay') : 90;
			$member_assignment = trim($req->get('MemberAssignment') == 'A') ? true : false;
			$is_template = trim($req->get('IsTemplate')) == 1 ? true : false; 
			$display_docs_as_folder = trim($req->get('LoadDocsAsFolders')) == 1 ? true : false;
			
			if (empty($title))
			{
				throw $this->createNotFoundException('Library Title is missed.');
			}
			
			$library = new Libraries();
			$library->setLibraryTitle($title);
			$library->setRecycleRetention($recycle);
			$library->setArchiveRetention($archive);
			$library->setDocLogRetention($log_delay);
			$library->setEventLogRetention($event_delay);
			$library->setHostName($request->getHost());
			$library->setDateCreated(new \DateTime());
			$library->setMemberAssignment($member_assignment);
			$library->setIsTemplate($is_template);
			$library->setIsApp(false);
			$library->setLoadDocsAsFolders($display_docs_as_folder);
			if ($req->get('ChangeStatus') == '0') {
				$library->setStatus(false);
			}
			if ($req->get('RequirePDFCreator') == '1') {
				$library->setPDFCreatorRequired(true);
			}
			if (trim($req->get('Description'))) {
				$library->setDescription(trim($req->get('Description')));
			}
			if (trim($req->get('Community'))) {
				$library->setCommunity(trim($req->get('Community')));
			}
			if (trim($req->get('Realm'))) {
				$library->setRealm(trim($req->get('Realm')));
			}
			if (trim($req->get('EnablePublicAccess')=='1')) {
				$library->setPublicAccessEnabled(true);
				if (trim($req->get('Public_Access_Expiration'))) {
					$library->setPublicAccessExpiration(trim($req->get('Public_Access_Expiration')));
				}		
			}
			else
				$library->setPublicAccessEnabled(false);

			if (trim($req->get('SourceTemplate'))) {
				$source_template = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $req->get('SourceTemplate'), 'Trash' => false));
				$source_template = !empty($source_template) ? trim($req->get('SourceTemplate')) : null;
				$library->setSourceTemplate($source_template);
				$source_template = null;
			}
			else {
				$library->setSourceTemplate(null);
			}
			
			if (trim($req->get('LibraryDomainOrgUnit'))) {
				$library->setLibraryDomainOrgUnit(trim($req->get('LibraryDomainOrgUnit')));
			}
			if (trim($req->get('UploaderAttachmentsField'))) {
				$library->setCompareFileSource(trim($req->get('UploaderAttachmentsField')));
			}
			if (trim($req->get('Body'))) {
				$library->setComments(trim($req->get('Body')));
			}
			
			$em->persist($library);
			$em->flush();
			
			if ($req->get('DocumentTypeOption') == 'S')
			{
				if ($req->get('DocumentType') && is_array($req->get('DocumentType')) && count($req->get('DocumentType')) > 0) {
					foreach ($req->get('DocumentType') as $doctype_id)
					{
						$dt = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $doctype_id, 'Trash' => false));
						if (!empty($dt))
						{
							$library->addApplicableDocType($dt);
						}
					}

					$em->flush();
					$em->refresh($library);
				}
			}

			$customACL = new CustomACL($this->container);
			$customACL->insertObjectAce($library, 'ROLE_ADMIN', 'owner');
			
			if (trim($req->get('LibraryAdmins')))
			{
				$master_role = new UserRoles();
				$master_role->setDisplayName($library->getLibraryTitle() . ' Administrators');
				$master_role->setRole('ROLE_LIBADMIN' . $library->getId());
				$em->persist($master_role);
				$customACL->insertObjectAce($library, $master_role->getRole(), 'master');
				
				$users_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($req->get('LibraryAdmins')));
				foreach ($users_list as $username)
				{
					if (false !== $user = $this->findUserAndCreate($username)) 
					{
						$master_role->addRoleUsers($user);
					}
					elseif (false != $group = $this->retrieveGroupByRole($username))
					{
						$customACL->insertObjectAce($library, $group->getRole(), 'master');
					}
				}

				$em->flush();
			}

			if (trim($req->get('CanCreateRootFolders')))
			{
			    $users_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($req->get('CanCreateRootFolders')));
				$customACL->insertObjectAce($library, 'ROLE_ADMIN', 'create');
				foreach ($users_list as $username)
				{
					if (false !== $user = $this->findUserAndCreate($username) && is_object($user)) 
					{
						$customACL->insertObjectAce($library, $user, 'create', false);
					}
					else {
						$type = false === strpos($username, '/DOCOVA') ? true : false;
						$groupname = str_replace('/DOCOVA', '', $username);
						$role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $groupname, 'groupType' => $type));
						if (!empty($role)) 
						{
							$customACL->insertObjectAce($library, $role->getRole(), 'create');
						}
					}
				}
			}
			else {
				$customACL->insertObjectAce($library, 'ROLE_USER', 'create');
			}
			
			if (trim($req->get('Subscribers')) || (trim($req->get('LibraryAdmins')) && $member_assignment === true)) 
			{
			    $users_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($req->get('Subscribers')));
			    $users_list = array_unique(array_merge($users_list, preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($req->get('LibraryAdmins')))));
				$customACL->insertObjectAce($library, 'ROLE_ADMIN', 'view');
				foreach ($users_list as $username)
				{
					if (false !== $user = $this->findUserAndCreate($username))
					{
						$customACL->insertObjectAce($library, $user, 'view', false);
					}
					else {
						$type = false === strpos($username, '/DOCOVA') ? true : false;
						$groupname = str_replace('/DOCOVA', '', $username);
						$role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $groupname, 'groupType' => $type));
						if (!empty($role)) 
						{
							$customACL->insertObjectAce($library, $role->getRole(), 'view');
						}
					}
				}
			}
			else {
				$customACL->insertObjectAce($library, 'ROLE_USER', 'view');
			}

			if ($library->getSourceTemplate())
			{
				$this->copyTemplateLibraryContent($library);
			}

			return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$library->getId().'&loadaction=reloadview&closeframe=true');
		}

		return $this->render('DocovaBundle:Admin:editwAdminLibrariesDocument.html.twig', array(
				'user' => $this->user,
				'doctypes' => $doctypes,
				'settings' => $this->global_settings,
				'lib_templates' => $libTemplates
			));
	}
	
	public function workflowDefinitionAction(Request $request)
	{
		$this->initialize();
		
		if (true === $request->isMethod('POST')) 
		{
			$req	= $request->request;
			$params = array(
				'CustomizeAction' => $req->get('CustomizeAction') == 1 ? true : false,
				'EnableImmediateRelease' => $req->get('EnableImmediateRelease') == 1 ? true : false,
				'DefaultWorkflow' => $req->get('DefaultWorkflow') == 1 ? true : false,
				'WorkflowName' => trim($req->get('WorkflowName')),
				'settings' => $this->global_settings,
				'WorkflowDescription' => trim($req->get('WorkflowDescription'))
			);
			
			$workflow = $this->get('docova.workflowservices');
			$workflow = $workflow->generateWorkflow($params);

			return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$workflow->getId().'&loadaction=reloadview&closeframe=true');
		}
		
		return $this->render('DocovaBundle:Admin:editwAdminWorkflowDocument.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings
			));
	}
	
	public function workflowDefinitionItemAction(Request $request)
	{
		$this->initialize();
		$workflow = $request->query->get('ParentUNID');
		$em = $this->getDoctrine()->getManager();
		$workflow = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('id' => $workflow, 'Trash' => false));
		if (empty($workflow)) 
		{
			throw $this->createNotFoundException('Unspecified source workflow.');
		}
		$system_messages = $em->getRepository('DocovaBundle:SystemMessages')->findBy(array('Systemic' => false, 'Trash' => false), array('Message_Name' => 'ASC'));
		
		if ($request->isMethod('POST'))
		{
			$req = $request->request;
			$dist = $req->get('wfType') == 'Serial'? 1 : 2;

			$tokenlist = "";
			
			if ( $dist == 1){
				if ($req->get('wfEnableAuthorParticipant') && $req->get('wfEnableAuthorParticipant') == "1"){
					$tokenlist = "[Author]";
				}else if ( !empty( $req->get('tmpwfReviewerApproverListSingle') )){
					$tokenlist = empty($tokenlist) ? $req->get('tmpwfReviewerApproverListSingle') : ",".$req->get('tmpwfReviewerApproverListSingle');
				}
			}else{
				if ($req->get('wfEnableAuthorParticipant') && $req->get('wfEnableAuthorParticipant') == "1"){
					$tokenlist = "[Author]";
				}
				if ( !empty( $req->get('tmpwfReviewerApproverListMulti') )){
					$tokenlist .= empty($tokenlist) ? $req->get('tmpwfReviewerApproverListMulti') : ",".$req->get('tmpwfReviewerApproverListMulti');
				}

				if ( !empty( $req->get('tmpwfReviewerApproverListSingle') )){
					$tokenlist .= empty($tokenlist) ? $req->get('tmpwfReviewerApproverListSingle') : ",".$req->get('tmpwfReviewerApproverListSingle');
				}
			}
			
			$details = array(
				'wfTitle' => trim($req->get('wfTitle')),
				'wfAction' => trim($req->get('wfAction')),
				'wfParticipantTokens' => $tokenlist,
				'wfReviewerApproverSelect' => $req->get('wfReviewerApproverSelect'),
				'wfType' => $req->get('wfType'),
				'wfCompleteAny' => $req->get('wfCompleteAny'),
				'wfCompleteCount' => $req->get('wfCompleteCount'),
				'wfOptionalComments' =>$req->get('wfOptionalComments'),
				'wfApproverEdit' => $req->get('wfApproverEdit'),
				'wfCustomReviewButtonLabel' => $req->get('wfCustomReviewButtonLabel'),
				'wfCustomApproveButtonLabel' => $req->get('wfCustomApproveButtonLabel'),
				'wfCustomDeclineButtonLabel' => $req->get('wfCustomDeclineButtonLabel'),
				'wfCustomReleaseButtonLabel' => $req->get('wfCustomReleaseButtonLabel'),
				'wfHideButtons' => $req->get('wfHideButtons'),
				'wfOrder' => $req->get('wfOrder'),
				'wfDocStatus' => $req->get('wfDocStatus'),
				'tmpwfReviewerApproverListMulti' => trim($req->get('tmpwfReviewerApproverListMulti')),
				'tmpwfReviewerApproverListSingle' => trim($req->get('tmpwfReviewerApproverListSingle')),
				'wfEnableActivateMsg' => $req->get('wfEnableActivateMsg'),
				'wfActivateMsg' => $req->get('wfActivateMsg'),
				'wfActivateNotifyList' => $req->get('wfActivateNotifyList'),
				'wfActivateNotifyParticipants' => $req->get('wfActivateNotifyParticipants'),
				'wfEnableCompleteMsg' => $req->get('wfEnableCompleteMsg'),
				'wfCompleteMsg' => $req->get('wfCompleteMsg'),
				'wfCompleteNotifyList' => $req->get('wfCompleteNotifyList'),
				'wfCompleteNotifyParticipants' => $req->get('wfCompleteNotifyParticipants'),
				'wfEnableDeclineMsg' => $req->get('wfEnableDeclineMsg'),
				'wfDeclineMsg' => $req->get('wfDeclineMsg'),
				'wfDeclineNotifyList' => $req->get('wfDeclineNotifyList'),
				'wfDeclineNotifyParticipants' => $req->get('wfDeclineNotifyParticipants'),
				'wfDeclineAction' => $req->get('wfDeclineAction'),
				'wfDeclineBacktrack' => $req->get('wfDeclineBacktrack'),
				'wfEnablePauseMsg' => $req->get('wfEnablePauseMsg'),
				'wfPauseMsg' => $req->get('wfPauseMsg'),
				'wfPauseNotifyList' => $req->get('wfPauseNotifyList'),
				'wfPauseNotifyParticipants' => $req->get('wfPauseNotifyParticipants'),
				'wfEnableCancelMsg' => $req->get('wfEnableCancelMsg'),
				'wfCancelMsg' => $req->get('wfCancelMsg'),
				'wfCancelNotifyList' => $req->get('wfCancelNotifyList'),
				'wfCancelNotifyParticipants' => $req->get('wfCancelNotifyParticipants'),
				'wfEnableDelayMsg' => $req->get('wfEnableDelayMsg'),
				'wfDelayMsg' => $req->get('wfDelayMsg'),
				'wfDelayNotifyList' => $req->get('wfDelayNotifyList'),
				'wfDelayNotifyParticipants' => $req->get('wfDelayNotifyParticipants'),
				'wfDelayCompleteThreshold' => trim($req->get('wfDelayCompleteThreshold')),
				'wfEnableDelayEsclMsg' => $req->get('wfEnableDelayEsclMsg'),
				'wfDelayEsclMsg' => $req->get('wfDelayEsclMsg'),
				'wfDelayEsclNotifyList' => $req->get('wfDelayEsclNotifyList'),
				'wfDelayEsclNotifyParticipants' => $req->get('wfDelayEsclNotifyParticipants'),
				'wfDelayEsclThreshold' => trim($req->get('wfDelayEsclThreshold')),
				'Workflow' => $workflow
			);
			
			$step = $this->get('docova.workflowservices');
			$step->setParams(array(
				'ldap_username' => $this->container->getParameter('ldap_username'),
				'ldap_password' => $this->container->getParameter('ldap_password')
			));
			$workflow_step = $step->generateWorkflowStep($details);
			$step = null;
			
			return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$workflow_step->getId().'&loadaction=reloadview&closeframe=true');
		}
		
		return $this->render('DocovaBundle:Admin:editwAdminWorkflowDocument.html.twig', array(
			'user' => $this->user,
			'form_type' => 'item',
			'settings' => $this->global_settings,
			'workflow' => $workflow,
			'system_messages' => $system_messages
		));
	}
	/*
	 * This create new Archive Policy
	 */
	public function createArchivePolicyAction(Request $request)
	{
		$this->initialize();
	
		$em = $this->getDoctrine()->getManager();
		$libraries	= $em->getRepository('DocovaBundle:Libraries')->findBy(array('Status' => true, 'Trash' => false));
		$doctypes	= $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true, 'Trash' => false), array('Doc_Name' => 'ASC'));
		
		
		if ($request->isMethod('POST'))
		{
			
			$req = $request->request;
			
			try {
					$archive_policy = new ArchivePolicies();
					$archive_policy instanceof \Docova\DocovaBundle\Entity\ArchivePolicies;
					
					// ---------- get form fields from post --------------------------
					//Policy Name
					$archive_policy->setPolicyName($req->get('PolicyName'));
					//Description
					$archive_policy->setDescription($req->get('Description')); 
					//Status
					$archive_policy->setPolicyStatus($req->get('PolicyStatus') ? true : false);
					//Priority
					$archive_policy->setPolicyPriority($req->get('PolicyPriority') ? $req->get('PolicyPriority') : 2) ; 
					
					//Applicable Libraries
					if ($req->get('LibraryOption') == 'S' && $req->get('Library'))
					{
						$libraries_selected = (is_array($req->get('Library'))) ? $req->get('Library') : array($req->get('Library'));
						foreach ($libraries_selected as $library)
						{
							$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('Status' => true, 'Trash' => false, 'id' => $library));
							if (!empty($library)) {
								$archive_policy->addLibraries($library);
							}
						}
						unset($libraries_selected, $library);
					}
					
					// Applicable Document Types
					if ($req->get('DocumentTypeOption') == 'S' && $req->get('DocumentType'))
					{
						//var_dump("inside doc types");
						$doctypes_selected = (is_array($req->get('DocumentType'))) ? $req->get('DocumentType') : array($req->get('DocumentType'));
						//var_dump($doctypes_selected);
						
						foreach ($doctypes_selected as $dt)
						{
							$document_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Status' => true, 'id' => $dt, 'Trash' => false));
							//var_dump("after:");
							//var_dump($document_type);
							if (!empty($document_type)) {
								$archive_policy->addDocumentTypes($document_type);
							}
						}
						unset($doctypes_selected, $document_type);
					}
					
					//------------------ Selction criteria -----------------------------------------------
					//Documents that were	
					$archive_policy->setEnableDateArchive($req->get('EnableDateArchive') ? true : false);
					//ArchiveDateSelect ( more then )
					$archive_policy->setArchiveDateSelect($req->get('ArchiveDateSelect') ? $req->get('ArchiveDateSelect') : "LastModifiedDate") ;
					//ArchiveDelay ( day(s) ago)
					$archive_policy->setArchiveDelay($req->get('ArchiveDelay'));
					//ArchiveCustomFormula ( and satisfy the following custom formula )
					$archive_policy->setArchiveCustomFormula($req->get('ArchiveCustomFormula'));
					
					//----------------- Exemptions ----------------------------------------------------------
					//ArchiveSkipWorkflow: Do not archive documents with active workflow 
					$archive_policy->setArchiveSkipWorkflow($req->get('ArchiveSkipWorkflow') ? true : false);
					//ArchiveSkipDrafts :Do not archive active draft documents
					$archive_policy->setArchiveSkipDrafts($req->get('ArchiveSkipDrafts') ? true : false);
					//ArchiveSkipVersions: Keep latest
					$archive_policy->setArchiveSkipVersions($req->get('ArchiveSkipVersions') ? true : false);
					//VersionCount: releases in the version stream (discarded drafts are not counted)
					$archive_policy->setVersionCount($req->get('VersionCount'));
			
					$em->persist($archive_policy); // create entity record
					$em->flush(); // commit entity record
				
					//. points to contenate
					return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$archive_policy->getId().'&loadaction=reloadview&closeframe=true');
			}
			catch (\Exception $e)
			{
				//var_dump($e->getMessage());
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
			
			
		
		}
		
		return $this->render('DocovaBundle:Admin:editwAdminArchivePoliciesDocument.html.twig', array(
				'user' => $this->user,
				'libraries' => $libraries,
				'doctypes' => $doctypes,
				'view_name' => 'wAdminArchivePolicies',
				'view_title' => 'Archive Policy',
				'settings' => $this->global_settings,
				'mode' => "edit"
		));
	}

	public function createReviewPolicyAction(Request $request)
	{
		$this->initialize();
		
		$em = $this->getDoctrine()->getManager();
				
		$libraries	= $em->getRepository('DocovaBundle:Libraries')->findBy(array('Status' => true, 'Trash' => false));
		$doctypes	= $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true, 'Trash' => false), array('Doc_Name' => 'ASC'));
		$system_messages = $em->getRepository('DocovaBundle:SystemMessages')->findBy(array('Systemic' => false));
		
		
		if ($request->isMethod('POST')) 
		{
			$req = $request->request;
			$review_policy = $this->saveReviewPolicyData($em, $doctypes, $libraries, $req);
			return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$review_policy->getId().'&loadaction=reloadview&closeframe=true');
		}

		return $this->render('DocovaBundle:Admin:editwAdminReviewPoliciesDocument.html.twig', array(
				'user' => $this->user,
				'libraries' => $libraries,
				'doctypes' => $doctypes,
				'system_messages' => $system_messages,
				'view_name' => 'wAdminReviewPolicies',
				'view_title' => 'Review Policy',
			));
	}

	public function systemMessageAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
			if (!trim($req->get('MessageName')) || !trim($req->get('Subject')) || !trim($req->get('Body')))
			{
				throw $this->createNotFoundException('Required information are missed.');
			}
			
			$link_type = $req->get('LinkOption');
			$link_type = (empty($link_type) || $link_type == 'w') ? false : true;
			$senders = $req->get('Principal');
			
			if (!empty($senders))
			{
				$groups = array();
				$senders = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $senders);
				foreach ($senders as $key => $value)
				{
					if (false !== $user = $this->findUserAndCreate($value))
					{
						$senders[$key] = $user;
					}
					else {
						$groups[] = $value;
					}
				}
			}
			
			try {
				$system_message = new SystemMessages();
				$system_message->setLinkType($link_type);
				$system_message->setMessageName($req->get('MessageName'));
				$system_message->setSubjectLine($req->get('Subject'));
				$system_message->setSystemic(false);
				$system_message->setMessageContent($req->get('Body'));
	
				if (!empty($senders))
				{
					foreach ($senders as $user)
					{
						$system_message->addSenders($user);
					}
				}
				
				if (!empty($groups)) 
				{
					$system_message->setSenderGroups($groups);
				}
	
				$em->persist($system_message);
				$em->flush();
				
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$system_message->getId().'&loadaction=reloadview&closeframe=true');
			}
			catch (\Exception $e)
			{
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
		}
		
		return $this->render('DocovaBundle:Admin:editwAdminSystemMessagesDocument.html.twig', array(
				'user' => $this->user
			));
	}

	public function createUserProfileAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();

		$roles	= $em->getRepository('DocovaBundle:UserRoles')->findBy(array('Group_Name' => null));
		$columns= $em->getRepository('DocovaBundle:ViewColumns')->findBy(array(), array('Title' => 'ASC'));

		if ($request->isMethod('POST')) 
		{
			$req = $request->request;

			if (!trim($req->get('FirstName')) || !trim($req->get('LastName')) || !trim($req->get('Email')) || !trim($req->get('UserMailSystem'))) 
			{
				throw $this->createNotFoundException('One or more required form inputs are missed.');
			}

			if (false == filter_var($req->get('Email', FILTER_VALIDATE_EMAIL))) 
			{
				throw $this->createNotFoundException('"'.$req->get('Email').'" is an invalid email address.');
			}
			
			if ($req->get('AccountType') != 1) 
			{
				$new_user = $this->findUserAndCreate($req->get('FirstName').' '.$req->get('LastName'));
				if (empty($new_user)) {
					$new_user = $this->findUserAndCreate($req->get('Email'));
				}
				if (empty($new_user)) 
				{
					throw $this->createNotFoundException('Could not create Internal user profile for "'.$req->get('FirstName').' '.$req->get('LastName').'"; User cannot be found in LDAP directory');
				}
			}
			else {
				if ($this->global_settings->getNumberOfUsers() > 0)
				{
					$user_count = $em->getRepository('DocovaBundle:UserAccounts')->createQueryBuilder('U')
						->select('COUNT(U.id)')
						->where('U.Trash = false')
						->getQuery()
						->getSingleScalarResult();
						
					if ($this->global_settings->getNumberOfUsers() <= $user_count)
					{
						return $this->redirect($this->generateUrl('docova_licenseguide') . '?issue=users');
					}
				}
				
				//---------- create user account -------------------
				$new_user = new UserAccounts();
				$new_user->setUsername($req->get('idUserName'));
				$new_user->setUserMail($req->get('Email'));

				//---------------------------- set docova dn name ---------------------------------
				$global_setting_docova_certifier = $this->global_settings->getDocovaBaseDn();
				$docova_certifier_name = $this->global_settings->getDocovaBaseDn() ? $this->global_settings->getDocovaBaseDn() : "/DOCOVA";
				if(substr($docova_certifier_name, 0, 1) == "/" || substr($docova_certifier_name, 0, 1) == "\\"){
				    $docova_certifier_name = substr($docova_certifier_name, 1);
				}
				$docova_dn_name = "CN=".trim($req->get('FirstName')." ".$req->get('LastName')).","."O=".trim($docova_certifier_name);				
				$new_user->setUserNameDn($docova_dn_name);
				
				$user_abbreviated_name=trim($req->get('FirstName').' '.$req->get('LastName'))."/".trim($docova_certifier_name);				
				$new_user->setUserNameDnAbbreviated($user_abbreviated_name);
				
				if ($this->global_settings->getDBAuthentication()) 
				{
					$user_pass = $req->get('NewPassword');
					if (empty($user_pass)) {
						throw $this->createNotFoundException('External user\'s password cannot be empty.');
					}
					$new_user->setPassword(md5(md5(md5($user_pass))));
				}
			}
			
			$user_role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $req->get('UserRole')));
			if (!empty($user_role)) 
			{
				if ($currnet_roles = $new_user->getUserRoles()) 
				{
					foreach ($currnet_roles as $rl) {
						$new_user->removeUserRoles($rl);
					}
					unset($currnet_roles);
				}
				
				$new_user->addRoles($user_role);
				unset($user_role);
			}
			else {
				unset($new_user);
				throw $this->createNotFoundException('Invalid role source is entered.');
			}

			$user_type = ($req->get('AccountType') == 1) ? true : false; // TRUE = External; FALSE = Internal;
			if ($user_type === true) 
			{
				$em->persist($new_user);
			}
			$em->flush();

			if ($user_type === true) {
				$user_profile = new UserProfile();
				$user_profile->setUser($new_user);
				$user_profile->setFirstName($req->get('FirstName'));
				$user_profile->setLastName($req->get('LastName'));
				$user_profile->setDisplayName($req->get('DisplayName'));
				$user_profile->setAccountType($this->global_settings->getDBAuthentication() ? true : false);
			}
			else {
				$em->refresh($new_user);
				$user_profile = $new_user->getUserProfile();
			}
			$user_profile->setLanguage(trim($req->get('UserLocale')) ? trim($req->get('UserLocale')) : 'en');
			$user_profile->setTimeZone(trim($req->get('UserTimeZone')) ? trim($req->get('UserTimeZone')) : '');
			$user_profile->setLoadLibraryFolders($req->get('LoadLibraryFolders'));
			$user_profile->setUserMailSystem($req->get('UserMailSystem'));
			if ($req->get('UserMailSystem') == 'O') {
				$user_profile->setEnableDebugMode($req->get('enableDebugMode') ? true : false);
				$user_profile->setOutlookMsgsToShow($req->get('OutlookMsgsToShow') ? $req->get('OutlookMsgsToShow') : null);
				$user_profile->setExcludeOutlookFolders($req->get('ExcludeOutlookPersonalFolders') ? $req->get('ExcludeOutlookPersonalFolders') : null);
				$user_profile->setExcludeOutlookInboxLevel($req->get('ExcludeOutlookInboxLevelFolders') ? $req->get('ExcludeOutlookInboxLevelFolders') : null);
			}
			switch ($req->get('UserMailSystem')) {
				case 'N':
					$user_profile->setMailServerURL($this->global_settings->getNotesMailServer() ? $this->global_settings->getNotesMailServer() : 'prod02.dlitools.com');
					break;
				case 'G':
					$user_profile->setMailServerURL('imap.gmail.com:993/imap/ssl');
					break;
				case 'Y':
					$user_profile->setMailServerURL('imap.mail.yahoo.com:993/imap/ssl');
					break;
				case 'H':
					$user_profile->setMailServerURL('imap-mail.outlook.com:993/imap/ssl');
					break;
				case 'X':
					$isSSL=$req->get('mailServerPort')=='993' ? true : false;
					if ($isSSL)
						$user_profile->setMailServerURL($req->get('mailServerUrl').':'.$req->get('mailServerPort').'/imap/ssl');
					else
						$user_profile->setMailServerURL($req->get('mailServerUrl').':'.$req->get('mailServerPort').'/imap/notls');
					break;
				default:
					$user_profile->setMailServerURL('N/A');
					break;
			}
			$user_profile->setNotifyUser($req->get('notifyUser') ? true : false);
			if (!$this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
				$user_profile->setCanCreateApp(false);
			}
			else {
				$user_profile->setCanCreateApp($req->get('CanCreateApps') == 'Yes' ? true: false);
			}
			$user_profile->setRecentEditCount($req->get('RecentEditCount') ? $req->get('RecentEditCount') : 10);
			$user_profile->setRecentUsedAppCount($req->get('RecentUsedAppCount') ? $req->get('RecentUsedAppCount') : 5);
			$user_profile->setNumberFormat($req->get('NumberFormat') ? $req->get('NumberFormat') : null);
			$user_profile->setDefaultShowOption($req->get('DefaultShowOption') ? $req->get('DefaultShowOption') : null);
			$user_profile->setHideLogout($req->get('HideLogout') == 1 ? true : false);
			$user_profile->setRedirectToMobile($req->get('RedirectToMobile') == 1 ? true : false);
			$user_profile->setTitle($req->get('Title') ? $req->get('Title') : null);
			$user_profile->setDepartment($req->get('department') ?$req->get('department') : null);
			if (trim($req->get('myboss')))
			{
				if (false !== $Manager = $this->findUserAndCreate($req->get('myboss'))) 
				{
					$user_profile->setManager($Manager);
				}
			}
			$user_profile->setOfficeNo($req->get('officeNo') ? $req->get('officeNo') : null);
			$user_profile->setMobileNo($req->get('mobileNo') ? $req->get('mobileNo') : null);
			$user_profile->setExpertise($req->get('expertise') ? $req->get('expertise') : null);
			$user_profile->setTheme($req->get('UserTheme') ? $req->get('UserTheme') : 'redmond');
			$user_profile->setWorkspaceTheme($req->get('WorkspaceTheme') == 'T' ? 'T' : 'S');
			if ($req->get('fltrField1')) {
				$column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $req->get('fltrField1')));
				if (!empty($column)) {
					$user_profile->setFltrField1($column);
					$user_profile->setFltrFieldVal1($req->get('fltrFieldVal1') ? $req->get('fltrFieldVal1') : null);
				}
			}
			if ($req->get('fltrField2')) {
				$column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $req->get('fltrField2')));
				if (!empty($column)) {
					$user_profile->setFltrField2($column);
					$user_profile->setFltrFieldVal2($req->get('fltrFieldVal2') ? $req->get('fltrFieldVal2') : null);
				}
			}
			if ($req->get('fltrField3')) {
				$column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $req->get('fltrField3')));
				if (!empty($column)) {
					$user_profile->setFltrField3($column);
					$user_profile->setFltrFieldVal3($req->get('fltrFieldVal3') ? $req->get('fltrFieldVal3') : null);
				}
			}
			if ($req->get('fltrField4')) {
				$column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $req->get('fltrField4')));
				if (!empty($column)) {
					$user_profile->setFltrField4($column);
					$user_profile->setFltrFieldVal4($req->get('fltrFieldVal4') ? $req->get('fltrFieldVal4') : null);
				}
			}
			
			if ($user_type === true) 
			{
				$em->persist($user_profile);
			}
			$em->flush();
			
			if ($new_user->getId() == $this->user->getId()) 
			{
				$session = $this->get('session');
				$session->set('user_locale', $user_profile->getLanguage());
				unset($session);
			}
			
			if ($req->get('notifyUser')) {
				//MailController::parseMessageAndSend();
				if ($user_type === true && $this->global_settings->getDBAuthentication()) 
				{
					$sysmessage = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'NewRegistrationNotifyMessage', 'Systemic' => false));
					$message = array(
						'subject' => MailController::parseTokens($this->container, $sysmessage->getSubjectLine(), $sysmessage->getLinkType(), null, array('DocovaUsername' => $new_user->getUserNameDnAbbreviated(), 'DocovaPassword' => $user_pass)),
						'content' => MailController::parseTokens($this->container, $sysmessage->getMessageContent(), $sysmessage->getLinkType()),
						'senders' => $sysmessage->getSenders(),
						'groups' => $sysmessage->getSenderGroups()
					);
					MailController::parseMessageAndSend($message, $this->container, array($req->get('Email')));
				}
				else {
					//@TODO: I have to create a mail message for internal users, too.
					//$message = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'NewRegistrationInternalMessage', 'Systemic' => false));
					$message['subject'] = 'Docova User Registration';
					$message['content'] = <<<MESSAGE
Please click on the below link to get started!

Click here to launch DOCOVA: <a href="{$this->generateUrl('docova_homepage', array(), true)}" alt="{$this->global_settings->getApplicationTitle()}">{$this->global_settings->getApplicationTitle()}</a>
You can use your local username/email and password to login.
If you are having problems accessing DOCOVA, please contact your system administrator.

Docova Web Admin 
MESSAGE;
					MailController::parseMessageAndSend($message, $this->container, array($req->get('Email')));
				}
			}
			
			if (!empty($request->files) && $file = $request->files->get('Uploader_DLI_Tools')) 
			{
				if (false !== stripos($file->getMimeType(), 'image/')) 
				{
					$res = $file->move($this->get('kernel')->getRootDir().'/../web/upload/userprofiles/', md5($new_user->getUserNameDnAbbreviated()));
					if (empty($res)) {
						//@TODO: log the profile picture is not uploaded
					}
				}
			}
			
			return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$new_user->getId().'&loadaction=reloadview&closeframe=true'); 
		}

		return $this->render('DocovaBundle:Admin:editwAdminUserProfilesDocument.html.twig', array(
			'user' => $this->user,
			'view_name' => 'wAdminUserProfiles',
			'view_title' => 'User Profile',
			'settings' => $this->global_settings,
			'roles' => $roles,
			'columns' => $columns,
			'doc_mode' => "Create",
			'fieldsourcedata' => ['timezones' => $this->getTimeZoneList()]
		));
	}
	
	/*
	 * check user profile id
	 */
	public function userProfileCheckAction(Request $request)
	{
		
		$userName = $request->query->get('userName'); // eg JSON
		$userEmail = $request->query->get('userEmail');
		
		//var_dump($userName);
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$user_name_profile = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $userName));
		$user_email_profile =$em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('User_Mail' => $userEmail));
		
		$userExists="false";
		//var_dump($user_name_profile);
		if ($user_name_profile){
			$userExists="true";
		}
		
		$useremailExist="false";
		//var_dump("------------------user profile-----------");
		//var_dump($user_email_profile);
		if ($user_email_profile){
			$useremailExist="true";
		}
		
		$ret_xml = '<Results>';
		$ret_xml .=     '<user_name>'.$userExists.'</user_name>';
		$ret_xml .=     '<user_email>'.$useremailExist.'</user_email>';
		$ret_xml .= '</Results>';
		
		$response = new Response();
		$response->headers->set("content-type", "application/xml");
		$response->setContent($ret_xml);
		return $response;
		
	}
	
	public function createGroupAction(Request $request)
	{
		$this->initialize();
		if ($request->getMethod() == 'POST') 
		{
			$req = $request->request;
			$new_group = new UserRoles();
			$result = $this->saveUserRole($req, $new_group, true);
			if (!empty($result)) 
			{
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$new_group->getId().'&loadaction=reloadview&closeframe=true');
			}
		}
		return $this->render('DocovaBundle:Admin:editwAdminGroupsDocument.html.twig', array(
			'user' => $this->user,
			'view_name' => 'wAdminGroups',
			'view_title' => 'Groups',
			'settings' => $this->global_settings,
			'mode' => "edit"
		));
	}
	
	public function groupServicesAction(Request $request)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$em = $this->getDoctrine()->getManager();
		$output = array();
		try {
			$action = $request->request->get('Action');
			switch ($action) 
			{
				case 'REMOVE':
					$group = $request->request->get('Unid');
					$group = $em->getRepository('DocovaBundle:UserRoles')->find($group);
					if (empty($group)) {
						throw new \Exception('Unspecified group source.');
					}
					$users = $request->request->get('Documents');
					$users = is_array($users) ? $users : array($users);
					foreach ($users as $uid) {
						$user = $em->getRepository('DocovaBundle:UserAccounts')->find($uid);
						if (!empty($user)) 
						{
							$group->removeRoleUsers($user);
						}
						elseif ($group->getNestedGroups() && in_array($uid, $group->getNestedGroups())) {
							$type = false === strpos($uid, '/DOCOVA') ? true : false;
							$groupname = str_replace('/DOCOVA', '', $uid);
							$ng = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $groupname, 'groupType' => $type));
							if (!empty($ng) && $ng->getRoleUsers()->count() > 0) 
							{
								foreach ($ng->getRoleUsers() as $user) 
								{
									$group->removeRoleUsers($user);
								}
							}
							$nested_groups = $group->getNestedGroups();
							unset($nested_groups[array_search($uid, $nested_groups)]);
							$group->setNestedGroups($nested_groups);
						}
					}
					$em->flush();
					$output = array('Status' => 'OK');
					break;
				case 'NEW':
					$group = $request->request->get('Unid');
					$group = $em->getRepository('DocovaBundle:UserRoles')->find($group);
					if (empty($group)) {
						throw new \Exception('Unspecified group source.');
					}
					$users = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $request->request->get('usersname')); 
					if (!empty($users)) 
					{
						$adds = array();
						foreach ($users as $username) {
							if (false !== $user = $this->findUserAndCreate($username, true)) {
								$group->addRoleUsers($user);
								$adds[] = array('uid' => $user->getId(), 'name' => $username);
							}
							else {
								$type = false === strpos($username, '/DOCOVA') ? true : false;
								$groupname = str_replace('/DOCOVA', '', $username);
								$inner_group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $groupname, 'groupType' => $type));
								if (!empty($inner_group) && $inner_group->getRoleUsers()->count() > 0)
								{
									foreach ($inner_group->getRoleUsers() as $user) {
										if (!$group->getRoleUsers()->contains($user)) {
											$group->addRoleUsers($user);
										}
									}
									$nested_groups = $group->getNestedGroups();
									if (!empty($nested_groups) && !in_array($username, $nested_groups)) {
										$nested_groups[] = $username;
										$group->setNestedGroups($nested_groups);
										$adds[] = array('uid' => $inner_group->getId(), 'name' => $username);
									}
									elseif (empty($nested_groups)) {
										$nested_groups = array($username);
										$group->setNestedGroups($nested_groups);
										$adds[] = array('uid' => $inner_group->getId(), 'name' => $username);
									}
								}
							}
						}
						if (count($adds) > 0) 
						{
							$em->flush();
							$output = array('Status' => 'OK', 'RetNames' => $adds);
						}
						else {
							$output = array('Status' => 'FAILED', 'ErrMsg' => 'No matching user found to be added.');
						}
					}
					else {
						$output = array('Status' => 'FAILED', 'ErrMsg' => 'No user is selected.');
					}
					break;
				case 'IMPORTGROUP':
					$this->initialize();
					$cn = trim($request->request->get('CommonName'));
					$ldap_obj = new adLDAP(array(
						'domain_controllers' => $this->global_settings->getLDAPDirectory(),
						'domain_port' => $this->global_settings->getLDAPPort(),
						'base_dn' => $this->global_settings->getLdapBaseDn(),
						'ad_username' => $this->container->getParameter('ldap_username'),
						'ad_password' => $this->container->getParameter('ldap_password')
					));
					
					$ad_unique_key = ($this->container->hasParameter('ldap_groupkey') ? $this->container->getParameter('ldap_groupkey') : '');
					$fields = ['displayname', 'cn'];
					if (!empty($ad_unique_key))
						$fields[] = $ad_unique_key;

					$groupInfo = $ldap_obj->group_info($cn, $fields);
					if (!empty($groupInfo['count']))
					{
						$guid_key = null;
						if (array_key_exists($ad_unique_key, $groupInfo[0]) && !empty($groupInfo[0][$ad_unique_key])) {
							if (is_array($groupInfo[0][$ad_unique_key])) {
								$groupInfo[0][$ad_unique_key][0] = $ldap_obj->decodeGuid($groupInfo[0][$ad_unique_key][0]);
								$guid_key = $groupInfo[0][$ad_unique_key][0];
							}
							else {
								$groupInfo[0][$ad_unique_key] = $ldap_obj->decodeGuid($groupInfo[0][$ad_unique_key]);
								$guid_key = $groupInfo[0][$ad_unique_key];
							}
						}
						
						$exists = $em->getRepository('DocovaBundle:UserRoles')->groupExists($groupInfo, $guid_key);
						$gname = !empty($groupInfo[0]['displayname'][0]) ? $groupInfo[0]['displayname'][0] : $groupInfo[0]['cn'][0];
						if (empty($exists))
						{
							$group = new UserRoles();
							$group->setGroupName($gname);
							$group->setDisplayName($gname);
							$group->setGroupType(true);
							$group->setRole('ROLE_'.strtoupper(str_replace(' ', '', $gname)));
							$group->setAdKey($guid_key);
							$em->persist($group);
							$em->flush();
							$members = $ldap_obj->group_members($cn);
							if (!empty($members) && count($members) > 0)
							{
								foreach ($members as $username) {
									if (strpos($username, '=') === false && (false !== $user = $this->findUserAndCreate($username, true, true)))
									{
										if (!$user->getTrash()) {
											$user->addRoles($group);
											//$group->addRoleUsers($user);
										}
									}
									unset($user);
								}
								$em->flush();
							}
							$output = array('Status' => 'OK', 'RetId' => $group->getId());
						}
						elseif (!empty($guid_key)) {
							$modified = false;
							if ($exists->getAdKey() != $guid_key) {
								$exists->setAdKey($guid_key);
								$modified = true;
							}
							if ($exists->getGroupName() != $gname || $exists->getDisplayName() != $gname) {
								$exists->setGroupName($gname);
								$exists->setDisplayName($gname);
								$modified = true;
							}
							if ($modified === true) {
								$em->flush();
							}
							//@note: should we remove existing members and re-import members?
							$output = array('Status' => 'OK', 'RetId' => $exists->getId());
						}
						else {
							$output = array('Status' => 'FAILED', 'ErrMsg' => 'The selected group was imported previously.');
						}
					}
					break;
				case 'SYNCHRONIZE':
					$this->initialize();
					$output = array('Status' => 'FAILED', 'result' => array());
					$group = $request->request->get('Unid');
					$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('id' => $group, 'groupType' => true));
					if (!empty($group)) 
					{
						$members = $group->getRoleUsers();
						if ($members->count()) 
						{
							foreach ($members as $user) {
								$group->removeRoleUsers($user);
							}
							$em->flush();
						}
						unset($members);

						$ldap_obj = new adLDAP(array(
							'domain_controllers' => $this->global_settings->getLDAPDirectory(),
							'domain_port' => $this->global_settings->getLDAPPort(),
							'base_dn' => $this->global_settings->getLdapBaseDn(),
							'ad_username' => $this->container->getParameter('ldap_username'),
							'ad_password' => $this->container->getParameter('ldap_password')
						));
						
						$dn = str_replace(array(', ', ','), '/', ldap_dn2ufn($this->global_settings->getLdapBaseDn()));
						$members = $ldap_obj->group_members($group->getGroupName().'/'.$dn);
						if (!empty($members) && count($members) > 0) 
						{
							foreach ($members as $username) {
								if (strpos($username, '=') === false && (false !== $user = $this->findUserAndCreate($username, true, true)))
								{
									if (!$user->getTrash()) {
										$user->addRoles($group);
										//$group->addRoleUsers($user);
									}
								}
								unset($user);
							}
							
							$subgroups = $ldap_obj->groups_in_group($group->getGroupName() . '/' . $dn);
							if (!empty($subgroups)) 
							{
								$nested_groups = array();
								foreach ($subgroups as $g) {
									$d = substr(stristr($g, 'CN='), 3);
									$d = strstr($d, ',', true);
									$d = substr_replace($d, '', -1);
									$nested_groups[] = $d;
								}
								$group->setNestedGroups($nested_groups);
							}
							$em->flush();
						}
						unset($members, $user);

						$em->refresh($group);
						$members = $group->getRoleUsers();
						if ($members->count()) 
						{
							$output['Status'] = 'OK';
							foreach ($members as $user) {
								$output['result'][] = array(
									'uid' => $user->getId(),
									'displayName' => $user->getUserProfile()->getDisplayName(),
									'abbreviatedName' => $user->getUserNameDnAbbreviated()
								);
							}
						}
						else {
							$output['Status'] = 'OK';
						}

						if ($group->getNestedGroups())
						{
							foreach ($group->getNestedGroups() as $g) {
								$output['result'][] = array(
									'uid' => $g,
									'displayName' => $g,
									'abbreviatedName' => ''
								);
							}
						}
					}
					break;
				default:
					$output = array('Status' => 'FAILED', 'ErrMsg' => 'Undefined action.');
			}
		}
		catch (\Exception $e) {
			$logger = $this->get('logger');
			$logger->error('On File: ' . $e->getFile() . ' Line: '. $e->getLine(). ' - ' . $e->getMessage());
			$output = array('Status' => 'FAILED', 'ErrMsg' => 'Contact Administrator for details.');
		}
		$response->setContent(json_encode($output));
		return $response;
	}
	
	public function popupImportGroupsAction()
	{
		$this->initialize();
		$ldap_obj = new adLDAP(array(
			'domain_controllers' => $this->global_settings->getLDAPDirectory(),
			'domain_port' => $this->global_settings->getLDAPPort(),
			'base_dn' => $this->global_settings->getLdapBaseDn(),
			'ad_username' => $this->container->getParameter('ldap_username'),
			'ad_password' => $this->container->getParameter('ldap_password')
		));
		
		$groups = $ldap_obj->all_groups();
		$output= array();
		if (count($groups) > 0) 
		{
			foreach ($groups as $displayname => $cn) {
				$output[] = array('DisplayName' => $displayname, 'distinguishedname' => $cn);
			}
		}

		return $this->render('DocovaBundle:Admin:dlgImportGroups.html.twig', array(
			'user' => $this->user,
			'groups' => $output
		));
	}
	
	public function lookupGroupsAction(Request $request)
	{
		$this->initialize();
		$ldap_obj = new adLDAP(array(
			'domain_controllers' => $this->global_settings->getLDAPDirectory(),
			'domain_port' => $this->global_settings->getLDAPPort(),
			'base_dn' => $this->global_settings->getLdapBaseDn(),
			'ad_username' => $this->container->getParameter('ldap_username'),
			'ad_password' => $this->container->getParameter('ldap_password')
		));
		
		if (trim($request->request->get('searchfor'))) {
			$search = '*' . urldecode(trim($request->request->get('searchfor'))) . '*';
			$groups = $ldap_obj->search_groups(null, false, $search);
		}
		else {
			$groups = $ldap_obj->all_groups();
		}
		$output= array('count' => 0);
		if (count($groups) > 0)
		{
			$output['count'] = count($groups);
			foreach ($groups as $displayname => $cn) {
				$output['results'][] = array('DisplayName' => $displayname, 'distinguishedname' => $cn);
			}
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'json/application');
		$response->setContent(json_encode($output));
		return $response;
	}
	
	/**
	 * Creates new Document Type
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function documentTypeAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$workflow_Types	= $em->getRepository('DocovaBundle:Workflow')->findBy(array('Trash' => false), array('application' => 'ASC'));
		$templates		= $em->getRepository('DocovaBundle:FileTemplates')->findAll();
		$doctypes		= $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false), array('Doc_Name' => 'ASC'));
		$review_policy	= $em->getRepository('DocovaBundle:ReviewPolicies')->findAll();
		$custom_subfoms = $em->getRepository('DocovaBundle:Subforms')->findBy(['Is_Custom' => true, 'Trash' => false], ['Form_Name' => 'ASC']);
	
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
	
			try {
				$doc_type = $this->saveDocumentType($em, $req, $this->user);

				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$doc_type->getId().'&loadaction=reloadview&closeframe=true');
			}
			catch (\Exception $e)
			{
				var_export($e->getMessage());
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
		}
		// create params for twig template
		$view_name="wAdminDocTypes";
		$params = array(
				'user' => $this->user,
				'view_name'=>$view_name ,
				'view_title'=> "Document Type",
				'workflow_Types' =>$workflow_Types,
				'templates' => $templates,
				'doctypes' => $doctypes,
				'settings' => $this->global_settings,
				'review_policies' => $review_policy,
				'custom_subforms' => $custom_subfoms,
				'mode' => "edit"
		);
		return $this->render('DocovaBundle:Admin:edit'.$view_name.'Document.html.twig', $params);
	}	

	/*
	 *  Creates new HTML Resource
	 */
	public function createHtmlResourceAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
			
			
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
	
			try {
			
				$html_resource = new HtmlResources();
				$html_resource->setResourceName($req->get('ResourceName'));
				$html_resource->setHtmlCode($req->get('HtmlCode'));
				//---------------------------get file attachment----------------------------------
				$fileAttachments=$request->files;
				$file=$fileAttachments->get('fileTemplateAttachment');

				// check if file is uploaded
				if($file){
					if (!is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'HtmlResources'))
					{
						@mkdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'HtmlResources');
					}
					
					$res = $file->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'HtmlResources', md5(basename($file->getClientOriginalName())));
					
					$html_resource->setFileName($file->getClientOriginalName());
					$html_resource->setFileMimeType($res->getMimeType());
					$html_resource->setFileSize($file->getClientSize());
				}

				$em->persist($html_resource); // create entity record
				$em->flush(); // commit entity record
	
				//. points to contenate
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$html_resource->getId().'&loadaction=reloadview&closeframe=true');

			}
			catch (\Exception $e)
			{
				//var_dump( $e->getMessage());
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
		}
		return $this->render('DocovaBundle:Admin:editwAdminHTMLResourceDocument.html.twig', array(
				'user' => $this->user,
				'view_name' => 'wAdminHTMLResource',
				'settings' => $this->global_settings,
				'view_title' => 'HTML Resource',
				'mode' => "edit"
		));
	}	

	/*
	 * Creates Cusomt Search Field for web admin
	 */
	public function createCustomSearchFieldAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
			
		//check if its a post request	
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
	
			try {
				$custom_search = new CustomSearchFields();
				$custom_search->setFieldDescription($req->get('FieldDescription'));
				$fname = strtolower($req->get('FieldName'));
				$custom_search->setFieldName($req->get('FieldName'));
				$custom_search->setDataType($req->get('DataType'));
				$custom_search->setTextEntryType($req->get('TextEntryType'));
				$custom_search->setSelectValues($req->get('SelectValues'));
				$customFields = $em->getRepository('DocovaBundle:DesignElements')->findBy(array('fieldName' => $fname, 'trash' => false));
				$cFields = null;
				if (!empty($customFields)) 
				{
					foreach($customFields as $field) {
						$cFields .= $field->getId() . ';';
					}
					$cFields = substr_replace($cFields, '', -1);
				}
				$custom_search->setCustomField($cFields);
				unset($customFields);

				// Related Document Types
				if ($req->get('DocumentTypeOption') == 'S' && $req->get('DocumentType'))
				{
					$doctypes_selected = (is_array($req->get('DocumentType'))) ? $req->get('DocumentType') : array($req->get('DocumentType'));
					foreach ($doctypes_selected as $dt)
					{
						$document_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Status' => true, 'id' => $dt, 'Trash' => false)); //Status => true meaning its enabled.
						if (!empty($document_type)) {
							$custom_search->addDocumentTypes($document_type);
						}
					}
					unset($doctypes_selected, $document_type);
				}

				$em->persist($custom_search); // create entity record
				$em->flush(); // commit entity record
	
				//. points to contenate
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$custom_search->getId().'&loadaction=reloadview&closeframe=true');
	
			}
			catch (\Exception $e)
			{
				//var_dump( $e->getMessage());
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
		}
		
		$docTypes = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true, 'Trash' => false), array('Doc_Name' => 'ASC'));
		return $this->render('DocovaBundle:Admin:editwAdminCustomSearchFieldsDocument.html.twig', array(
				'user' => $this->user,
				'view_name' => 'wAdminCustomSearchFields',
				'view_title' => 'Custom Search Field',
				'settings' => $this->global_settings,
				'docTypes' => $docTypes,
				'mode' => "edit"
		));
	}
	
	/*
	 * Creates Activity for web admin
	 */
	
	public function createActivityAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
			
			
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
	
			try {
					
					$activity = new Activities();
					$activity->setActivityAction($req->get("ActivityAction"));
					$activity->setActivityObligation($req->get('ActivityObligation'));
					$activity->setActivitySendMessage($req->get('ActivitySendMessage') == 1 ? true : false);
					$activity->setActivitySubject($req->get('ActivitySubject') ? $req->get('ActivitySubject') : null);
					$activity->setActivityInstruction($req->get("ActivityInstruction") ? $req->get("ActivityInstruction") : null);
					$em->persist($activity); // create entity record
					$em->flush(); // commit entity record
		
					//. points to contenate
					return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$activity->getId().'&loadaction=reloadview&closeframe=true');
	
			}
			catch (\Exception $e)
			{
				//var_dump( $e->getMessage());
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
		}
		return $this->render('DocovaBundle:Admin:editwAdminActivitiesDocument.html.twig', array(
				'user' => $this->user,
				'view_name' => 'wAdminActivities',
				'view_title' => 'Activity',
				'settings' => $this->global_settings,
				'mode' => "edit"
		));
	}
	
	// To Add file Template
	public function fileTemplateAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		
			
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;

			try {
				$file_template = new FileTemplates();
				$file_template->setTemplateName($req->get('TemplateName'));
				$file_template->setTemplateType($req->get('TemplateType'));
				// get file attachment
				//-------------------------------------------------------------------
				$fileAttachments=$request->files;
				$file=$fileAttachments->get('fileTemplateAttachment');
				
				//var_dump($file);
				if ($file){
					//var_dump("inside file");
					
					//exit();
					if (!is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Templates'))
					{
						@mkdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Templates');
					}
					
					$res = $file->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Templates', md5(basename($file->getClientOriginalName())));
					$file_template->setFileName($file->getClientOriginalName());
					$file_template->setFileMimeType($res->getMimeType());
					$file_template->setFileSize($file->getClientSize());
									
				}
				$file_template->setDateCreated(new \DateTime()); // back space to ignore symfony date time
				$file_template->setTemplateVersion($req->get('TemplateVersion'));

				$em->persist($file_template); // create entity record
				$em->flush(); // commit entity record
	
				//. points to contenate
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$file_template->getId().'&loadaction=reloadview&closeframe=true');
			}
			catch (\Exception $e)
			{
				//var_dump( $e->getMessage());
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
		}
		return $this->render('DocovaBundle:Admin:editwAdminFileTemplatesDocument.html.twig', array(
				'user' => $this->user,
				'view_name' => 'wAdminFileTemplates',
				'view_title' => 'File Template',
				'settings' => $this->global_settings,
				'mode' => "edit"
		));
	}
	
	public function getFileTemplatesByTypeAction(Request $request)
	{
		$type = $request->query->get('RestrictToCategory');
		if (empty($type)) 
		{
			throw $this->createNotFoundException('Category type is missed.');
		}
		
		$results = new \DOMDocument('1.0', 'UTF-8');
		$root = $results->appendChild($results->createElement('viewentries'));
		$em = $this->getDoctrine()->getManager();
		if ($type === 'All') {
			$templates = $em->getRepository('DocovaBundle:FileTemplates')->findBy(array(), array('Template_Name' => 'ASC'));
		}
		else {
			$templates = $em->getRepository('DocovaBundle:FileTemplates')->findBy(array('Template_Type' => $type), array('Template_Name' => 'ASC'));
		}
		
		if (!empty($templates)) 
		{
			$count = 0;
			foreach ($templates as $ft) {
				$ft instanceof \Docova\DocovaBundle\Entity\FileTemplates;
				$node = $results->createElement('viewentry');
				$newnode = $results->createElement('entrydata');
				$textnode = $results->createElement('text', $ft->getTemplateName());
				$newnode->appendChild($textnode);
				$attrib = $results->createAttribute('name');
				$attrib->value = 'TemplateName';
				$newnode->appendChild($attrib);
				$attrib = $results->createAttribute('columnnumber');
				$attrib->value = 0;
				$newnode->appendChild($attrib);
				$node->appendChild($newnode);
				$newnode = $results->createElement('entrydata');
				$textnode = $results->createElement('text', $ft->getId());
				$newnode->appendChild($textnode);
				$attrib = $results->createAttribute('name');
				$attrib->value = 'DocKey';
				$newnode->appendChild($attrib);
				$attrib = $results->createAttribute('columnnumber');
				$attrib->value = 1;
				$newnode->appendChild($attrib);
				$node->appendChild($newnode);
				$newnode = $results->createElement('entrydata');
				$textnode = $results->createElement('text', $count);
				$newnode->appendChild($textnode);
				$attrib = $results->createAttribute('name');
				$attrib->value = '$10';
				$newnode->appendChild($attrib);
				$attrib = $results->createAttribute('columnnumber');
				$attrib->value = 2;
				$newnode->appendChild($attrib);
				$node->appendChild($newnode);
				$count++;
				$root->appendChild($node);
			}
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($results->saveXML());
		return $response;
	}

	/**
	 * open file template
	 * @param: file path
	 * @return: file output stream
	 */
	public function openAdminFileTemplateAction($file_name)
	{
		if (empty($file_name)) 
		{
			throw $this->createNotFoundException('Unspecified source File Template.');
		}
		
		$file_info = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:FileTemplates')->findOneBy(array('File_Name'=>$file_name));
		if (empty($file_info))
		{
			throw $this->createNotFoundException('Unspecified source File Template with ID = '. $file_id);
		}
		
		$file_path_header = $this->generatePathHeader($file_info, true,"Templates");
		
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
		//return  new Response ( $file_info->getFileMimeType(), 200 );
	}

	/**
	  * find mime type of a file
	  * @param : file name
	  * @return: string representing the mime type
	  */
	  
	public function GetMimeTypeEx($fname) {

		ob_start();
		system("file -i -b {$fname}");
		$output = ob_get_clean();
		$output = explode("; ",$output);
		if ( is_array($output) ) {
			$output = $output[0];
		}
		return $output;
	}
	
	/**
	 * Generate file path and required header options for response.
	 * If file does not exist returns false
	 * 
	 * @param object $record_obj
	 * @return array
	 */
	private function generatePathHeader($record_obj, $is_template = false, $type)
	{
		$this->initialize();
		$file_name		= $record_obj->getFileName();
		
		if (!file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.md5($file_name))) {
			return false;
		}
		
		$file_path = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.md5($file_name);
		
		$mtype = $this->GetMimeTypeEx($file_path );
		$mtype = !empty($mtype) ? $mtype : $record_obj->getFileMimeType();
		
		if ($is_template === true) {
			$headers = array(
				//'Content-Type' => $record_obj->getFileMimeType(),
				'Content-Type' => $mtype,
				'Content-Disposition' => 'inline; filename="'.$record_obj->getFileName().'"',
			);
		}
		
		return array(
				'file_path' => $file_path,
				'headers' => $headers
				);
	}
	
	
	

	
	// To create/Add ViewColumn
	public function viewColumnAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();

		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
			try {
				$view_column = new ViewColumns();
				//$view_column->get($req->get('TemplateName'));
				//--------- get view column form fields ----------------------
				// Column Type
				$view_column->setColumnType( $req->get('ColumnType')=="1" ? true:false );
				//Column Status
				$view_column->setColumnStatus( $req->get('ColumnStatus')=="1" ? false:true) ;
				//Default Column Title
				$view_column->setTitle( $req->get("ColumnHeading") );
				//field or formula
				$view_column->setFieldName( $req->get("DataQuery") );
				//DataType
				$view_column->setDataType( $req->get("DataType") );
				//Default Width
				$view_column->setWidth( $req->get("ColumnWidth") );
				// XML Node Name
				$view_column->setXMLName( $req->get("XmlNode") );
				
				// Related Document Types
				if( $req->get('RelatedDocType')=="S"){
					$docTypeOptions=$req->get('DocTypeKey');
					foreach ($docTypeOptions as $value){
						$temp = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $value, 'Trash' => false));
						$view_column->addRelatedDocTypes($temp);
					}
				}
				// applicpable libraries
				if( $req->get('ApplicableLibraries')=="S"){
					$libOptions=$req->get('Libraries');
					foreach ($libOptions as $value){
						$temp=$em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $value, 'Trash' => false));
						$view_column->addApplicableLibraries($temp);
					}
				}
				
	
				$em->persist($view_column); // create entity record
				$em->flush(); // commit entity record
	
				//. points to contenate
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$view_column->getId().'&loadaction=reloadview&closeframe=true');
			}
			catch (\Exception $e)
			{
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
		}
		
		$docTypes = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true, 'Trash' => false), array('Doc_Name' => 'ASC'));
		$applicableLibs=$em->getRepository('DocovaBundle:Libraries')->findBy(array('Status' => true, 'Trash' => false));
		$valid_xml_node = 'CF'.$em->getRepository('DocovaBundle:ViewColumns')->getLastXMLNode();
		
		$params = array(
				'user' => $this->user,
				'view_name' => 'wAdminViewColumns',
				'view_title' => 'View Column',
				'relatedDoctypes'=>$docTypes,
				'applicableLibraries'=>$applicableLibs,
				'settings' => $this->global_settings,
				'valid_xml_node' => $valid_xml_node,
				'mode' => 'edit'
		);
		
		return $this->render('DocovaBundle:Admin:editwAdminViewColumnsDocument.html.twig', $params);
	}
	
	public function readDocumentAction(Request $request, $view_name, $doc_id)
	{
		if (!empty($view_name) && !empty($doc_id))
		{
			if ($view_name == 'wAdminUserProfiles' && $request->query->get('uType') == 'user') {
				$this->initialize($doc_id);
			}
			else {
				if (in_array($view_name, array('wAdminFileResources', 'wAdminDataSources', 'wAdminDataViews', 'wAdminCheckedOutFiles', 'wAdminGroups', 'wAdminDeletedUsers', 'wAdminUserProfiles'))) {
					$securityContext = $this->container->get('security.authorization_checker');
					if (!$securityContext->isGranted('ROLE_ADMIN')) {
						throw new AccessDeniedException();
					}
				}
				$this->initialize();
			}
			$em = $this->getDoctrine()->getManager();
			$params = array();
			
			switch ($view_name)
			{
				case 'wAdminLibraries':
					$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $doc_id, 'Trash' => false));
					if (empty($library))
					{
						throw $this->createNotFoundException('Unspecified source library ID = '.$doc_id);
					}
					$source_template = '';
					if ($library->getSourceTemplate()) {
						$source_template = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library->getSourceTemplate(), 'Trash' => false));
						$source_template = !empty($source_template) ? $source_template->getLibraryTitle() : '';
					}
					$customACL = new CustomACL($this->container);
					$securityContext = $this->container->get('security.authorization_checker');
					$canEdit = $securityContext->isGranted('MASTER', $library) ? true : false;
					$folder_creators = $customACL->getObjectACEUsers($library, 'create');
					if ($folder_creators->count() > 0) 
					{
						foreach ($folder_creators as $index => $value)
						{
							if (false !== $member = $this->findUserAndCreate($value->getUsername(), false, true)) {
								$folder_creators[$index] = $member;
							}
							else {
								unset($folder_creators[$index]);
							}
						}
						unset($member, $value, $index);
					}
					$group_creators = $customACL->getObjectACEGroups($library, 'create');
					if (!empty($group_creators) && $group_creators->count() > 0) 
					{
						foreach ($group_creators as $index => $value) {
							$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $value->getRole()));
							if (!empty($group)) 
							{
								$group_creators[$index] = $group;
							}
							else {
								unset($group_creators[$index]);
							}
						}
					}
					$restrictions = $customACL->getObjectACEUsers($library, 'view');
					if ($restrictions->count() > 0) 
					{
						foreach ($restrictions as $index => $value)
						{
							if (false !== $member = $this->findUserAndCreate($value->getUsername(), false, true)) {
								$restrictions[$index] = $member;
							}
							else {
								unset($restrictions[$index]);
							}
						}
						unset($member, $value, $index);
					}
					$group_restrictions = $customACL->getObjectACEGroups($library, 'view');
					if (!empty($group_restrictions) && $group_restrictions->count() > 0)
					{
						foreach ($group_restrictions as $index => $value) {
							if ($value->getRole() === 'ROLE_LIBADMIN'.$library->getId() || $value->getRole() === 'ROLE_ADMIN') {
								unset($group_restrictions[$index]);
								continue;
							}
							$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $value->getRole()));
							if (!empty($group))
							{
								$group_restrictions[$index] = $group;
							}
							else {
								unset($group_restrictions[$index]);
							}
						}
					}
					$libadmins = $em->getRepository('DocovaBundle:UserAccounts')->findByRole('ROLE_LIBADMIN'.$library->getId());
					$libadming = $customACL->getObjectACEGroups($library, 'master');
					if (!empty($libadming) && $libadming->count() > 0) 
					{
						foreach ($libadming as $index => $value) {
							if ($value->getRole() === 'ROLE_LIBADMIN'.$library->getId() || $value->getRole() === 'ROLE_ADMIN') {
								unset($libadming[$index]);
								continue;
							}
							$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $value->getRole()));
							if ( !empty($group))
							{
								$libadming[$index] = $group;
							}
							else {
								unset($libadming[$index]);
							}
						}
					}
					
					$params = array(
							'user' => $this->user,
							'library' => $library,
							'folder_creators' => $folder_creators,
							'group_creators' => $group_creators,
							'restrictions' => $restrictions,
							'settings' => $this->global_settings,
							'group_restrictions' => $group_restrictions,
							'libadmin_users' => $libadmins,
							'libadmin_groups' => $libadming,
							'sourceTemplate' => $source_template,
							'can_edit' => $canEdit
						);
				break;
				case 'wAdminSystemMessages':
					$message = $em->find('DocovaBundle:SystemMessages', $doc_id);
					if (empty($message))
					{
						throw $this->createNotFoundException('Unspecified source message ID = '. $doc_id);
					}
					
					$params = array(
							'user' => $this->user,
							'message' => $message,
							'settings' => $this->global_settings,
						);
				break;
				case 'wAdminWorkflow':
					if ($request->query->get('wfitem') && $request->query->get('wfitem') == 'true') 
					{
						$workflow_item = $em->getRepository('DocovaBundle:WorkflowSteps')->findOneBy(array('id' => $doc_id, 'Trash' => false));
						if (empty($workflow_item)) 
						{
							throw $this->createNotFoundException('Unspecified source workflow item ID = '. $doc_id);
						}

						$step_actions = array();
						if ($workflow_item->getActions()->count() > 0)
						{
							foreach ($workflow_item->getActions() as $action)
							{
								$step_actions[$action->getActionType(true)] = $action;
							}
						}

						$params = array(
								'user' => $this->user,
								'workflow_item' => $workflow_item,
								'step_actions' => $step_actions,
								'settings' => $this->global_settings,
							);
					}
					else {
						$workflow = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('id' => $doc_id, 'Trash' => false));
						if (empty($workflow)) 
						{
							throw $this->createNotFoundException('Unspecified source workflow ID = '. $doc_id);
						}
					
						$params = array(
								'user' => $this->user,
								'workflow' => $workflow,
								'settings' => $this->global_settings,
							);
					}
					break;
				case 'wAdminReviewPolicies':
					$review_policy = $em->find('DocovaBundle:ReviewPolicies', $doc_id);
					if (empty($review_policy)) 
					{
						throw $this->createNotFoundException('Unspecifed review policy source ID = '.$doc_id);
					}
					
					$params = array(
						'user' => $this->user,
						'document' => $review_policy,
						'settings' => $this->global_settings,
					);
					break;
				case 'wAdminFileTemplates':
					$file_template = $em->find('DocovaBundle:FileTemplates', $doc_id);
					if (empty($file_template))
					{
						throw $this->createNotFoundException('Unspecified source message ID = '. $doc_id);
					}
						
					$params = array(
							'user' => $this->user,
							'document' => $file_template,
							'view_name' =>$view_name,
							'view_title' => "File Template",
							'settings' => $this->global_settings,
							'mode' => 'read'					
					);
					break;
				case 'wAdminFileResources':
					$file_resource = $em->find('DocovaBundle:FileResources', $doc_id);
					if (empty($file_resource))
					{
						throw $this->createNotFoundException('Unspecified source message ID = '. $doc_id);
					}
				
					$params = array(
							'user' => $this->user,
							'document' => $file_resource,
							'view_name' =>$view_name,
							'settings' => $this->global_settings,
							'view_title' => "File Resource",
							'mode' => 'read'
					);
					break;
				case 'wAdminDataSources':
						$data_source = $em->find('DocovaBundle:DataSources', $doc_id);
						if (empty($data_source))
						{
							throw $this->createNotFoundException('Unspecified source message ID = '. $doc_id);
						}
					
						$params = array(
								'user' => $this->user,
								'document' => $data_source,
								'view_name' =>$view_name,
								'settings' => $this->global_settings,
								'view_title' => "Data Source",
								'mode' => 'read'
						);
						break;
				case 'wAdminDataViews':
					$data_view = $em->find('DocovaBundle:DataViews', $doc_id);
					if (empty($data_view))
					{
						throw $this->createNotFoundException('Unspecified source message ID = '. $doc_id);
					}
						
					$params = array(
							'user' => $this->user,
							'document' => $data_view,
							'view_name' =>$view_name,
							'settings' => $this->global_settings,
							'view_title' => "Data View",
							'mode' => 'read'
					);
					break;
				case 'wAdminViewColumns':
					// get view column by id
					$view_column = $em->find('DocovaBundle:ViewColumns', $doc_id);
					if (empty($view_column))
					{
						throw $this->createNotFoundException('[wAdminViewColumns]Unspecified source message ID = '. $doc_id);
					}
				
					$params = array(
							'user' => $this->user,
							'view_name'=> $view_name,
							'view_title'=> "View Column",
							'settings' => $this->global_settings,
							'document' => $view_column,
							'mode' => 'read'
					);
					break;	
				//----------- User Profile ---------
				case 'wAdminUserProfiles':
				case 'wAdminUserSessions':
					$view_name ='wAdminUserProfiles'; 
					$user_profile = $em->find('DocovaBundle:UserAccounts', $doc_id);
					if (empty($user_profile))
					{
						throw $this->createNotFoundException('[ReadDocument::wAdminUserProfiles] Unspecified source ID = '. $doc_id);
					}

					if (file_exists($this->get('kernel')->getRootDir().'/../web/upload/userprofiles/' . md5($user_profile->getUserNameDnAbbreviated())))
					{
						$attachment = array(
								'filename' => md5($user_profile->getUserNameDnAbbreviated())
						);
					}

					$params = array(
							'user' => $this->user,
							'settings' => $this->global_settings,
							'document' => $user_profile,
							'view_name'=> $view_name,
							'attachment' => !empty($attachment) ? $attachment : null,
							'view_title'=> "User Profile"
					);
					break;

				case 'wAdminGroups':
					$group = $em->find('DocovaBundle:UserRoles', $doc_id);
					if (empty($group))
					{
						throw $this->createNotFoundException('[ReadDocument::wAdminGroups] Unspecified source ID = '. $doc_id);
					}

					$params = array(
							'user' => $this->user,
							'document' => $group,
							'settings' => $this->global_settings,
							'view_name'=> $view_name,
							'view_title'=> "Groups"
					);
					break;
						
				//----------- Document Types ---------
				case 'wAdminDocTypes':
					$document_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $doc_id, 'Trash' => false));
					$custom_subfoms = $em->getRepository('DocovaBundle:Subforms')->findBy(['Is_Custom' => true, 'Trash' => false], ['Form_Name' => 'ASC']);
					if (empty($document_type))
					{
						throw $this->createNotFoundException('[ReadDocument::wAdminDocTypes] Unspecified source ID = '. $doc_id);
					}
					$review_policy	= $em->getRepository('DocovaBundle:ReviewPolicies')->findAll();
					$doctypes		= $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false), array('Doc_Name' => 'ASC'));
					
					$params = array(
							'user' => $this->user,
							'document' => $document_type,
							'view_name'=> $view_name,
							'view_title'=> "Document Type",
							'doctypes' => $doctypes,
							'settings' => $this->global_settings,
							'custom_subforms' => $custom_subfoms,
							'review_policies' => $review_policy,
							'mode' => "read"
					);
					break;
					
				case 'wAdminArchivePolicies':
					//var_dump("DV inside here");
					$archive_policy = $em->find('DocovaBundle:ArchivePolicies', $doc_id);
					if (empty($archive_policy))
					{
						//var_dump("DV inside here  emtpy");
						throw $this->createNotFoundException('Unspecifed archive policy source ID = '.$doc_id);
					}
					//var_dump("DV inside not empty");
					$params = array(
							'user' => $this->user,
							'document' => $archive_policy,
							'view_name'=> $view_name,
							'settings' => $this->global_settings,
							'view_title'=> "Document Type",
							'mode' => "read"
					);
					break;
					
					case 'wAdminSubscribedLibraries':
						$unid = explode(',', $doc_id);
						$document = [
							'id' => $doc_id,
							'username' => null,
							'library' => null,
							'community' => null,
							'realm' => null
						];
						$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(['id' => $unid[1], 'isApp' => false, 'Trash' => false]);
						$user = $em->getRepository('DocovaBundle:UserAccounts')->find($unid[0]);
						if (!empty($library) && !empty($user)) {
							$document['username'] = $user->getUserNameDnAbbreviated();
							$document['library'] = $library->getLibraryTitle();
							$document['community'] = $library->getCommunity();
							$document['realm'] = $library->getRealm();
						}
						
						$params = [
							'user' => $this->user,
							'document' => $document,
							'view_name' => $view_name,
							'settings' => $this->global_settings,
							'mode' => 'read'
						];
						break;
						
					case 'wAdminHTMLResource':
						$html_resource = $em->find('DocovaBundle:HtmlResources', $doc_id);
						if (empty($html_resource))
						{
							throw $this->createNotFoundException('Unspecifed archive policy source ID = '.$doc_id);
						}
						$params = array(
								'user' => $this->user,
								'document' => $html_resource,
								'view_name'=> $view_name,
								'settings' => $this->global_settings,
								'view_title'=> "HTML Resource",
								'mode' => "read"
						);
						break;
						
					case 'wAdminCustomSearchFields':
						$custom_search_field = $em->find('DocovaBundle:CustomSearchFields', $doc_id);
						if (empty($custom_search_field))
						{
							throw $this->createNotFoundException('Unspecifed custom search field doc ID = '.$doc_id);
						}
						$params = array(
								'user' => $this->user,
								'document' => $custom_search_field,
								'view_name'=> $view_name,
								'settings' => $this->global_settings,
								'view_title'=> "Custom Search Field",
								'mode' => "read"
						);
						break;	

					case 'wAdminActivities':
						$activity = $em->find('DocovaBundle:Activities', $doc_id);
						if (empty($activity))
						{
							throw $this->createNotFoundException('Unspecifed activity ID = '.$doc_id);
						}
						$params = array(
								'user' => $this->user,
								'document' => $activity,
								'view_name'=> $view_name,
								'settings' => $this->global_settings,
								'view_title'=> "Activity",
								'mode' => "read"
						);
						break;
					case 'wAdminCheckedOutFiles':
						$ciao_log = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('id' => $doc_id, 'Checked_Out' => true));
						if (empty($ciao_log)) 
						{
							throw $this->createNotFoundException('Unspecified CIAO file ID = '. $doc_id);
						}
						$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
						$params = array(
							'user' => $this->user,
							'document' => $ciao_log,
							'view_name' => $view_name,
							'view_title' => 'File check-out log',
							'settings' => $this->global_settings,
							'date_format' => $defaultDateFormat,
							'mode' => 'read'
						);
						break;
				default:
					throw $this->createNotFoundException('Matched routing cannot be found for '.$view_name);
			}
			return $this->render('DocovaBundle:Admin:read'.$view_name.'Document.html.twig', $params);
		}
	}
	
	public function editDocumentAction(Request $request, $view_name, $doc_id)
	{
		if (!empty($view_name) && !empty($doc_id))
		{
			if ($view_name == 'wAdminUserProfiles' && $request->query->get('uType') == 'user') {
				$this->initialize($doc_id);
			}
			else {
				if (in_array($view_name, array('wAdminFileResources', 'wAdminDataSources', 'wAdminDataViews', 'wAdminCheckedOutFiles', 'wAdminGroups', 'wAdminDeletedUsers', 'wAdminUserProfiles'))) {
					$securityContext = $this->container->get('security.authorization_checker');
					if (!$securityContext->isGranted('ROLE_ADMIN')) {
						throw new AccessDeniedException();
					}
				}
				$this->initialize();
			}
			$em = $this->getDoctrine()->getManager();
			$params = array();
				
			switch ($view_name)
			{
				case 'wAdminLibraries':
					$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $doc_id, 'Trash' => false));
					if (empty($library))
					{
						throw $this->createNotFoundException('Unspecified source library ID = '.$doc_id);
					}
					$securityContext = $this->container->get('security.authorization_checker');
					if (!$securityContext->isGranted('MASTER', $library)) {
						throw new AccessDeniedException();
					}

					$customACL = new CustomACL($this->container);
					
					if ($request->isMethod('POST'))
					{
						$req = $request->request;
						$title = $req->get('Title');
						$recycle = ($req->get('TrashCleanupDelay')) ? $req->get('TrashCleanupDelay') : 30;
						$archive = ($req->get('ArchiveCleanupDelay')) ? $req->get('ArchiveCleanupDelay') : 730;
						$log_delay = ($req->get('LogCleanupDelay')) ? $req->get('LogCleanupDelay') : 2192;
						$event_delay = ($req->get('EventCleanupDelay')) ? $req->get('EventCleanupDelay') : 90;
						$display_docs_as_folder = trim($req->get('LoadDocsAsFolders')) == 1 ? true : false;
						
						if (empty($title))
						{
							throw $this->createNotFoundException('Library Title is missed.');
						}
						
						$library->setLibraryTitle($title);
						$library->setRecycleRetention($recycle);
						$library->setArchiveRetention($archive);
						$library->setDocLogRetention($log_delay);
						$library->setEventLogRetention($event_delay);
						$library->setLoadDocsAsFolders($display_docs_as_folder);
						$library->setDateUpdated(new \DateTime());
						if ($req->get('ChangeStatus') == '0') {
							$library->setStatus(false);
						}
						else {
							$library->setStatus(true);
						}
						if ($req->get('RequirePDFCreator') == '1') {
							$library->setPDFCreatorRequired(true);
						}
						else {
							$library->setPDFCreatorRequired(false);
						}
						if (trim($req->get('Description'))) {
							$library->setDescription(trim($req->get('Description')));
						}
						else {
							$library->setDescription(null);
						}
						if (trim($req->get('Community'))) {
							$library->setCommunity(trim($req->get('Community')));
						}
						else {
							$library->setCommunity(null);
						}
						if (trim($req->get('Realm'))) {
							$library->setRealm(trim($req->get('Realm')));
						}
						else {
							$library->setRealm(null);
						}
						
						if (trim($req->get('EnablePublicAccess')=='1')) {
							$library->setPublicAccessEnabled(true);
							if (trim($req->get('Public_Access_Expiration'))) {
								$library->setPublicAccessExpiration(trim($req->get('Public_Access_Expiration')));
							}
						}
						else
							$library->setPublicAccessEnabled(false);
						
						$library->setMemberAssignment($req->get('MemberAssignment') == 'A' ? true : false);
						$library->setIsTemplate($req->get('IsTemplate') == 1 ? true : false);
						$library->setLibraryDomainOrgUnit(trim($req->get('LibraryDomainOrgUnit')) ? trim($req->get('LibraryDomainOrgUnit')) : null);
						if (trim($req->get('SourceTemplate'))) {
							$source_template = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $req->get('SourceTemplate'), 'Trash' => false));
							$source_template = !empty($source_template) ? trim($req->get('SourceTemplate')) : null;
							$library->setSourceTemplate($source_template);
							$source_template = null;
						}
						else {
							$library->setSourceTemplate(null);
						}
						if (trim($req->get('UploaderAttachmentsField'))) {
							$library->setCompareFileSource(trim($req->get('UploaderAttachmentsField')));
						}
						else {
							$library->setCompareFileSource(null);
						}
						if (trim($req->get('Body'))) {
							$library->setComments(trim($req->get('Body')));
						}
						else {
							$library->setComments(null);
						}
						
						$em->flush();
						if ($library->getApplicableDocType()->count() > 0)
						{
							foreach ($library->getApplicableDocType() as $dt)
							{
								$library->removeApplicableDocType($dt);
							}
							$em->flush();
							$em->refresh($library);
							unset($dt);
						}

						if (!$customACL->isRoleGranted($library, 'ROLE_ADMIN', 'owner')) 
						{
							$customACL->insertObjectAce($library, 'ROLE_ADMIN', 'owner');
						}

						$customACL->removeMaskACEs($library, 'master');
						$master_role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_LIBADMIN' . $library->getId()));
						if (!empty($master_role)) 
						{
							foreach ($master_role->getRoleUsers() as $user) 
							{
								$master_role->removeRoleUsers($user);
							}
							$em->flush();
						}
						if (trim($req->get('LibraryAdmins')))
						{
							if (empty($master_role)) {
								$master_role = new UserRoles();
								$master_role->setDisplayName($library->getLibraryTitle() . ' Administrators');
								$master_role->setRole('ROLE_LIBADMIN' . $library->getId());
								$em->persist($master_role);
								$em->flush();
							}
							$customACL->insertObjectAce($library, $master_role->getRole(), 'master');
						
							$users_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($req->get('LibraryAdmins')));
							foreach ($users_list as $username)
							{
								if (false !== $user = $this->findUserAndCreate($username))
								{
									$master_role->addRoleUsers($user);
								}
								elseif (false != $group = $this->retrieveGroupByRole($username))
								{
									$customACL->insertObjectAce($library, $group->getRole(), 'master');
								}
							}
						
							$em->flush();
						}
						elseif (!empty($master_role) && $customACL->isRoleGranted($library, $master_role->getRole(), 'master')) {
							$customACL->removeUserACE($library, $master_role->getRole(), 'master', true);
						}

						if ($req->get('DocumentTypeOption') == 'S')
						{
							if ($req->get('DocumentType') && is_array($req->get('DocumentType')) && count($req->get('DocumentType')) > 0) {
								foreach ($req->get('DocumentType') as $doctype_id)
								{
									$dt = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $doctype_id, 'Trash' => false));
									if (!empty($dt))
									{
										$library->addApplicableDocType($dt);
									}
								}
						
								$em->flush();
								$em->refresh($library);
							}
						}
						
						$customACL->removeMaskACEs($library, 'create');
			
						if (trim($req->get('CanCreateRootFolders')))
						{
						    $users_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($req->get('CanCreateRootFolders')));
							$customACL->insertObjectAce($library, 'ROLE_ADMIN', 'create');
							foreach ($users_list as $username)
							{
								if (false !== $user = $this->findUserAndCreate($username) && is_object($user)) 
								{
									$customACL->insertObjectAce($library, $user, 'create', false);
								}
								else {
									$type = false === strpos($username, '/DOCOVA') ? true : false;
									$groupname = str_replace('/DOCOVA', '', $username);
									$role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $groupname, 'groupType' => $type));
									if (!empty($role)) 
									{
										$customACL->insertObjectAce($library, $role->getRole(), 'create');
									}
								}
							}
						}
						else {
							$customACL->insertObjectAce($library, 'ROLE_USER', 'create');
						}
						
						if ($req->get('MemberAssignment') != 'A')
						{
							$customACL->removeMaskACEs($library, 'view');
							if (trim($req->get('Subscribers')) || trim($req->get('LibraryAdmins')))
							{
							    $users_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($req->get('Subscribers')));
							    $users_list = array_unique(array_merge($users_list, preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($req->get('LibraryAdmins')))));
								$customACL->insertObjectAce($library, 'ROLE_ADMIN', 'view');
								foreach ($users_list as $username)
								{
									if (false !== $user = $this->findUserAndCreate($username))
									{
										$customACL->insertObjectAce($library, $user, 'view', false);
									}
									else {
										$type = false === strpos($username, '/DOCOVA') ? true : false;
										$groupname = str_replace('/DOCOVA', '', $username);
										$role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $groupname, 'groupType' => $type));
										if (!empty($role))
										{
											$customACL->insertObjectAce($library, $role->getRole(), 'view');
										}
									}
								}
							}
							else {
								$customACL->insertObjectAce($library, 'ROLE_USER', 'view');
							}
						}
						elseif (!$customACL->isRoleGranted($library, 'ROLE_ADMIN', 'view')) {
							$customACL->insertObjectAce($library, 'ROLE_USER', 'view');
						}
						
						return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$library->getId().'&loadaction=reloadview&closeframe=true');
					}

					$doctypes = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false), array('Doc_Name' => 'ASC'));
					$folder_creators = $customACL->getObjectACEUsers($library, 'create');
					if ($folder_creators->count() > 0) 
					{
						foreach ($folder_creators as $index => $value)
						{
							if (false !== $member = $this->findUserAndCreate($value->getUsername(), false, true)) {
								$folder_creators[$index] = $member;
							}
							else {
								unset($folder_creators[$index]);
							}
						}
						unset($member, $value, $index);
					}
					$group_creators = $customACL->getObjectACEGroups($library, 'create');
					if (!empty($group_creators) && $group_creators->count() > 0) 
					{
						foreach ($group_creators as $index => $value) {
							$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $value->getRole()));
							if (!empty($group)) 
							{
								$group_creators[$index] = $group;
							}
							else {
								unset($group_creators[$index]);
							}
						}
					}
					$restrictions = $customACL->getObjectACEUsers($library, 'view');
					if ($restrictions->count() > 0) 
					{
						foreach ($restrictions as $index => $value)
						{
							if (false !== $member = $this->findUserAndCreate($value->getUsername(), false, true)) {
								$restrictions[$index] = $member;
							}
							else {
								unset($restrictions[$index]);
							}
						}
						unset($member, $value, $index);
					}
					$group_restrictions = $customACL->getObjectACEGroups($library, 'view');
					if (!empty($group_restrictions) && $group_restrictions->count() > 0)
					{
						foreach ($group_restrictions as $index => $value) {
							if ($value->getRole() === 'ROLE_LIBADMIN'.$library->getId() || $value->getRole() === 'ROLE_ADMIN') {
								unset($group_restrictions[$index]);
								continue;
							}
							$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $value->getRole()));
							if (!empty($group))
							{
								$group_restrictions[$index] = $group;
							}
							else {
								unset($group_restrictions[$index]);
							}
						}
					}
					$libadmins = $em->getRepository('DocovaBundle:UserAccounts')->findByRole('ROLE_LIBADMIN'.$library->getId());
					$libadming = $customACL->getObjectACEGroups($library, 'master');
					if (!empty($libadming) && $libadming->count() > 0)
					{
						foreach ($libadming as $index => $value) {
							if ($value->getRole() === 'ROLE_LIBADMIN'.$library->getId() || $value->getRole() === 'ROLE_ADMIN') {
								unset($libadming[$index]);
								continue;
							}
							$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $value->getRole()));
							if ( !empty($group))
							{
								$libadming[$index] = $group;
							}
							else {
								unset($libadming[$index]);
							}
						}
					}
					$libTemplates = $em->getRepository('DocovaBundle:Libraries')->findByInArray(array('L.id', 'L.Library_Title'), array('Is_Template' => true));
					if (empty($libTemplates[0])) {
						$libTemplates = array();
					}
						
					$params = array(
							'user' => $this->user,
							'library' => $library,
							'doctypes' => $doctypes,
							'folder_creators' => $folder_creators,
							'group_creators' => $group_creators,
							'restrictions' => $restrictions,
							'group_restrictions' => $group_restrictions,
							'libadmin_users' => $libadmins,
							'settings' => $this->global_settings,
							'libadmin_groups' => $libadming,
							'lib_templates' => $libTemplates
					);
				break;
				case 'wAdminWorkflow':
					if ($request->query->get('wfitem') && $request->query->get('wfitem') == 'true')
					{
						$workflow_item = $em->getRepository('DocovaBundle:WorkflowSteps')->findOneBy(array('id' => $doc_id, 'Trash' => false));
						if (empty($workflow_item)) 
						{
							throw $this->createNotFoundException('Unspecified source workflow item ID = '. $doc_id);
						}
						
						$system_messages = $em->getRepository('DocovaBundle:SystemMessages')->findBy(array('Systemic' => false, 'Trash' => false), array('Message_Name' => 'ASC'));
						$step_actions = array();
						if ($workflow_item->getActions()->count() > 0)
						{
							foreach ($workflow_item->getActions() as $action)
							{
								$step_actions[$action->getActionType(true)] = $action;
							}
						}
						
						if ($request->isMethod('POST'))
						{
							$req = $request->request;

							$dist = $req->get('wfType') == 'Serial'? 1 : 2;

							$tokenlist = "";
							
							if ( $dist == 1){
								if ($req->get('wfEnableAuthorParticipant') && $req->get('wfEnableAuthorParticipant') == "1"){
									$tokenlist = "[Author]";
								}else if ( !empty( $req->get('tmpwfReviewerApproverListSingle') )){
									$tokenlist = empty($tokenlist) ? $req->get('tmpwfReviewerApproverListSingle') : ",".$req->get('tmpwfReviewerApproverListSingle');
								}
							}else{
								if ($req->get('wfEnableAuthorParticipant') && $req->get('wfEnableAuthorParticipant') == "1"){
									$tokenlist = "[Author]";
								}

								if ( !empty( $req->get('tmpwfReviewerApproverListMulti') )){
									$tokenlist .= empty($tokenlist) ? $req->get('tmpwfReviewerApproverListMulti') : ",".$req->get('tmpwfReviewerApproverListMulti');
								}

								if ( !empty( $req->get('tmpwfReviewerApproverListSingle') )){
									$tokenlist .= empty($tokenlist) ? $req->get('tmpwfReviewerApproverListSingle') : ",".$req->get('tmpwfReviewerApproverListSingle');
								}
							}


							$details = array(
								'wfTitle' => trim($req->get('wfTitle')),
								'wfAction' => trim($req->get('wfAction')),
								'wfParticipantTokens' => $tokenlist,
								'wfEnableAuthorParticipant' => $req->get('wfEnableAuthorParticipant'),
								'wfReviewerApproverSelect' => $req->get('wfReviewerApproverSelect'),
								'wfType' => $req->get('wfType'),
								'wfCompleteAny' => $req->get('wfCompleteAny'),
								'wfCompleteCount' => $req->get('wfCompleteCount'),
								'wfOptionalComments' =>$req->get('wfOptionalComments'),
								'wfApproverEdit' => $req->get('wfApproverEdit'),
								'wfCustomReviewButtonLabel' => $req->get('wfCustomReviewButtonLabel'),
								'wfCustomApproveButtonLabel' => $req->get('wfCustomApproveButtonLabel'),
								'wfCustomDeclineButtonLabel' => $req->get('wfCustomDeclineButtonLabel'),
								'wfCustomReleaseButtonLabel' => $req->get('wfCustomReleaseButtonLabel'),
								'wfHideButtons' => $req->get('wfHideButtons'),
								'wfOrder' => $req->get('wfOrder'),
								'wfDocStatus' => $req->get('wfDocStatus'),
								'tmpwfReviewerApproverListMulti' => trim($req->get('tmpwfReviewerApproverListMulti')),
								'tmpwfReviewerApproverListSingle' => trim($req->get('tmpwfReviewerApproverListSingle')),
								'wfEnableActivateMsg' => $req->get('wfEnableActivateMsg'),
								'wfActivateMsg' => $req->get('wfActivateMsg'),
								'wfActivateNotifyList' => $req->get('wfActivateNotifyList'),
								'wfActivateNotifyParticipants' => $req->get('wfActivateNotifyParticipants'),
								'wfEnableCompleteMsg' => $req->get('wfEnableCompleteMsg'),
								'wfCompleteMsg' => $req->get('wfCompleteMsg'),
								'wfCompleteNotifyList' => $req->get('wfCompleteNotifyList'),
								'wfCompleteNotifyParticipants' => $req->get('wfCompleteNotifyParticipants'),
								'wfEnableDeclineMsg' => $req->get('wfEnableDeclineMsg'),
								'wfDeclineMsg' => $req->get('wfDeclineMsg'),
								'wfDeclineNotifyList' => $req->get('wfDeclineNotifyList'),
								'wfDeclineNotifyParticipants' => $req->get('wfDeclineNotifyParticipants'),
								'wfDeclineAction' => $req->get('wfDeclineAction'),
								'wfDeclineBacktrack' => $req->get('wfDeclineBacktrack'),
								'wfEnablePauseMsg' => $req->get('wfEnablePauseMsg'),
								'wfPauseMsg' => $req->get('wfPauseMsg'),
								'wfPauseNotifyList' => $req->get('wfPauseNotifyList'),
								'wfPauseNotifyParticipants' => $req->get('wfPauseNotifyParticipants'),
								'wfEnableCancelMsg' => $req->get('wfEnableCancelMsg'),
								'wfCancelMsg' => $req->get('wfCancelMsg'),
								'wfCancelNotifyList' => $req->get('wfCancelNotifyList'),
								'wfCancelNotifyParticipants' => $req->get('wfCancelNotifyParticipants'),
								'wfEnableDelayMsg' => $req->get('wfEnableDelayMsg'),
								'wfDelayMsg' => $req->get('wfDelayMsg'),
								'wfDelayNotifyList' => $req->get('wfDelayNotifyList'),
								'wfDelayNotifyParticipants' => $req->get('wfDelayNotifyParticipants'),
								'wfDelayCompleteThreshold' => trim($req->get('wfDelayCompleteThreshold')),
								'wfEnableDelayEsclMsg' => $req->get('wfEnableDelayEsclMsg'),
								'wfDelayEsclMsg' => $req->get('wfDelayEsclMsg'),
								'wfDelayEsclNotifyList' => $req->get('wfDelayEsclNotifyList'),
								'wfDelayEsclNotifyParticipants' => $req->get('wfDelayEsclNotifyParticipants'),
								'wfDelayEsclThreshold' => trim($req->get('wfDelayEsclThreshold')),
								'WorkflowStep' => $workflow_item
							);
								
							$step = $this->get('docova.workflowservices');
							$step->setParams(array(
								'ldap_username' => $this->container->getParameter('ldap_username'),
								'ldap_password' => $this->container->getParameter('ldap_password')
							));
							$step->generateWorkflowStep($details);
							$step = null;
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$workflow_item->getId().'&loadaction=reloadview&closeframe=true');
						}
						
						$params = array(
							'user' => $this->user,
							'form_type' => 'item',
							'settings' => $this->global_settings,
							'workflow_item' => $workflow_item,
							'system_messages' => $system_messages,
							'step_actions' => $step_actions
						);
					}
					else {
						$workflow = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('id' => $doc_id, 'Trash' => false));
						if (empty($workflow))
						{
							throw $this->createNotFoundException('Unspecified source workflow ID = '. $doc_id);
						}
						
						if ($request->isMethod('POST'))
						{
							$req = $request->request;
							$params = array(
								'CustomizeAction' => $req->get('CustomizeAction') == 1 ? true : false,
								'EnableImmediateRelease' => $req->get('EnableImmediateRelease') == 1 ? true : false,
								'DefaultWorkflow' => $req->get('DefaultWorkflow') == 1 ? true : false,
								'WorkflowName' => trim($req->get('WorkflowName')),
								'settings' => $this->global_settings,
								'WorkflowDescription' => trim($req->get('WorkflowDescription')),
								'Workflow' => $workflow
							);
								
							$wf_service = $this->get('docova.workflowservices');
							$wf_service->generateWorkflow($params);
							$wf_service = null;
							
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$workflow->getId().'&loadaction=reloadview&closeframe=true');
						}
						
						$params = array(
							'user' => $this->user,
							'settings' => $this->global_settings,
							'workflow' => $workflow
						);
					}
				break;
				case 'wAdminSystemMessages':
					$message = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('id' => $doc_id, 'Trash' => false));
					if (empty($message))
					{
						throw $this->createNotFoundException('Unspecified source system message ID = '. $doc_id);
					}
					
					if ($request->isMethod('POST'))
					{
						$req = $request->request;
						if (!trim($req->get('MessageName')) || !trim($req->get('Subject')) || !trim($req->get('Body')))
						{
							throw $this->createNotFoundException('Required information are missed.');
						}
						
						$link_type = $req->get('LinkOption');
						$link_type = (empty($link_type) || $link_type == 'w') ? false : true;
						$senders = $req->get('Principal');
						
						$groups = array();
						if (!empty($senders))
						{
						    $senders = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $senders);
							foreach ($senders as $key => $value)
							{
								if (false !== $user = $this->findUserAndCreate($value))
								{
									$senders[$key] = $user;
								}
								else {
									$groups[] = $value;
								}
							}
						}
						
						try {
							if ($message->getSenders()->count() > 0) 
							{
								foreach ($message->getSenders() as $user) 
								{
									$message->removeSenders($user);
								}
								$em->flush();
								$em->refresh($message);
							}
							
							$message->setLinkType($link_type);
							$message->setMessageName($req->get('MessageName'));
							$message->setSubjectLine($req->get('Subject'));
							$message->setMessageContent($req->get('Body'));
							$message->setSenderGroups($groups);
							
							$em->flush();
				
							if (!empty($senders))
							{
								foreach ($senders as $user)
								{
									$message->addSenders($user);
								}
							}
				
							$em->flush();
							
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$message->getId().'&loadaction=reloadview&closeframe=true');
						}
						catch (\Exception $e)
						{
							throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
						}
					}
					
					$params = array(
						'user' => $this->user,
						'settings' => $this->global_settings,
						'message' => $message
					);
				break;
				//--------------------- VIEW COLUMN-----------------------------
				case 'wAdminViewColumns':
					//---------------------------------------------------------
					//get required objects
					$view_column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('id' => $doc_id)); // contains view column 
					$applicableLibs	= $em->getRepository('DocovaBundle:Libraries')->findBy(array('Status' => true, 'Trash' => false));
					$docTypes	= $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true, 'Trash' => false), array('Doc_Name' => 'ASC'));
					
					// when posting form data
					if ($request->isMethod('POST'))
					{
						$req = $request->request;
						$this->saveViewColumnsData($em,$view_column,$docTypes, $applicableLibs, $doc_id, $req);
						return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$view_column->getId().'&loadaction=reloadview&closeframe=true');
					}
					// set array for twig template
					//var_dump(view_name);
					$params = array(
							'user' => $this->user,
							'view_name' => $view_name,
							'view_title' => 'View Column',
							'document' => $view_column,
							'relatedDoctypes'=>$docTypes,
							'settings' => $this->global_settings,
							'applicableLibraries'=>$applicableLibs,					
							'mode' => 'edit'
					);

					break;
				//--------------------------wAdminFileTemplates---------------------------
				case 'wAdminFileTemplates':
					//---------------------------------------------------------
					//get required objects
					$file_template=$em->getRepository('DocovaBundle:FileTemplates')->findOneBy(array('id' => $doc_id)); 
						
					// when posting form data
					if ($request->isMethod('POST'))
					{
						$req = $request->request;
						$this->saveFileTemplateData($em,$file_template, $doc_id, $req);
						return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$file_template->getId().'&loadaction=reloadview&closeframe=true');
					}
					// set array for twig template
					// $file_template->getTemplateName()
					$params = array(
							'user' => $this->user,
							'view_name' => $view_name,
							'view_title' =>"File Template",
							'settings' => $this->global_settings,
							'document' => $file_template,
							'mode' => 'edit'		
					);
				
					break;
				//--------------------------wAdminFileResources---------------------------
				case 'wAdminFileResources':
					//---------------------------------------------------------
					//get required objects
					$file_resource=$em->getRepository('DocovaBundle:FileResources')->findOneBy(array('id' => $doc_id));
				
					// when posting form data
					if ($request->isMethod('POST'))
					{
						$req = $request->request;
						$this->saveFileResourceData($em,$file_resource, $doc_id, $req);
						return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$file_resource->getId().'&loadaction=reloadview&closeframe=true');
					}
					// set array for twig template
					// $file_template->getTemplateName()
					$params = array(
							'user' => $this->user,
							'view_name' => $view_name,
							'view_title' =>"File Resource",
							'document' => $file_resource,
							'settings' => $this->global_settings,
							'mode' => 'edit'
					);
				
					break;
				case 'wAdminDataSources':
					//---------------------------------------------------------
					//get required objects
					$data_source=$em->getRepository('DocovaBundle:DataSources')->findOneBy(array('id' => $doc_id));
				
					// when posting form data
					if ($request->isMethod('POST'))
					{
						$req = $request->request;
						$this->saveDataSourceData($em,$data_source, $doc_id, $req);
						return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$data_source->getId().'&loadaction=reloadview&closeframe=true');
					}
					// set array for twig template
					// $file_template->getTemplateName()
					$params = array(
							'user' => $this->user,
							'view_name' => $view_name,
							'view_title' =>"Data Source",
							'document' => $data_source,
							'settings' => $this->global_settings,
							'dataSources' => $this->getDoctrine()->getManager()->getRepository("DocovaBundle:DataSources"),
							'mode' => 'edit'
					);
				
					break;
				case 'wAdminDataViews':
					//---------------------------------------------------------
					//get required objects
					$data_view=$em->getRepository('DocovaBundle:DataViews')->findOneBy(array('id' => $doc_id));
				
					// when posting form data
					if ($request->isMethod('POST'))
					{
						$req = $request->request;
						$this->saveDataViewData($em,$data_view, $doc_id, $req);
						return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$data_view->getId().'&loadaction=reloadview&closeframe=true');
					}
					// set array for twig template
					// $file_template->getTemplateName()
					$params = array(
							'user' => $this->user,
							'view_name' => $view_name,
							'view_title' =>"Data View",
							'settings' => $this->global_settings,
							'document' => $data_view,
							'dataViews' => $this->getDoctrine()->getManager()->getRepository("DocovaBundle:DataViews"),
							'mode' => 'edit'
					);
				
					break;
				case 'wAdminGroups':
					$group = $em->getRepository('DocovaBundle:UserRoles')->find($doc_id);
					if ($request->isMethod('POST')) 
					{
						$req = $request->request;
						$return = $this->saveUserRole($req, $group);
						if (!empty($return)) 
						{
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$group->getId().'&loadaction=reloadview&closeframe=true');
						}
					}
					
					$params = array(
						'user' => $this->user,
						'view_name' => $view_name,
						'view_title' => 'Groups',
						'settings' => $this->global_settings,
						'document' => $group,
						'mode' => 'edit'
					);
					break;

				//--------------------------wAdminFileTemplates---------------------------
				case 'wAdminUserProfiles':
					//---------------------------------------------------------
					//get required objects
					$user_profile = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('id' => $doc_id));
					if ($user_profile->getUsername() == 'DOCOVA SE' && $this->user->getId() != $user_profile->getId()) {
						throw new AccessDeniedException();
					}
					$roles	= $em->getRepository('DocovaBundle:UserRoles')->findBy(array('Group_Name' => null));
					$columns= $em->getRepository('DocovaBundle:ViewColumns')->findBy(array(), array('Title' => 'ASC'));
					if (file_exists($this->get('kernel')->getRootDir().'/../web/upload/userprofiles/' . md5($user_profile->getUserNameDnAbbreviated())))
					{
						$file = new File($this->get('kernel')->getRootDir().'/../web/upload/userprofiles/'.md5($user_profile->getUserNameDnAbbreviated()));
						$attachment = array(
							'filename' => $file->getFilename(),
							'filesize' => $file->getSize(),
							'filedate' => date('m/d/Y h:i:s A', $file->getMTime())
						);
					}
					// when posting form data
					if ($request->isMethod('POST'))
					{
						$this->saveUserProfile($em, $request, $user_profile, $doc_id);
						if ($request->query->get('uType') == 'user') {
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&uType=user');
						}
						else {
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$user_profile->getId().'&loadaction=reloadview&closeframe=true');
						}
					}
					//**** note below params are required in edit mode twig template *******
					$params = array(
							'user' => $this->user,
							'document' => $user_profile,
							'view_name' => $view_name,
							'view_title' => 'User Profile',
							'settings' => $this->global_settings,
							'roles' => $roles,
							'columns' => $columns,
							'doc_mode' => "Edit",
							'attachment' => !empty($attachment) ? $attachment : null,
    			        			'fieldsourcedata' => ['timezones' => $this->getTimeZoneList()]
						);
					break;
						
				case 'wAdminDocTypes':
					//get required objects
					$document_type	= $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $doc_id, 'Trash' => false));
					$workflow_Types	= $em->getRepository('DocovaBundle:Workflow')->findBy(array('Trash' => false, 'application' => null));
					$doctypes 		= $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false), array('Doc_Name' => 'ASC'));
					$templates		= $em->getRepository('DocovaBundle:FileTemplates')->findAll();
					$resources		= $em->getRepository('DocovaBundle:FileResources')->findAll();
					$review_policy	= $em->getRepository('DocovaBundle:ReviewPolicies')->findAll();
					$custom_subfoms = $em->getRepository('DocovaBundle:Subforms')->findBy(['Is_Custom' => true, 'Trash' => false], ['Form_Name' => 'ASC']);
						
					// when posting form data
					if ($request->isMethod('POST'))
					{
						$req = $request->request;
						$this->saveDocumentType($em, $req, $this->user, $document_type);
						return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$document_type->getId().'&loadaction=reloadview&closeframe=true');
					}
					//**** note below params are required in edit mode twig template *******
					$params = array(
							'user' => $this->user,
							'document' => $document_type,
							'view_name'=> $view_name,
							'view_title'=> "Document Type",
							'workflow_Types'=>$workflow_Types,
							'doctypes' => $doctypes,
							'templates' => $templates,
							'resources' => $resources,
							'settings' => $this->global_settings,
							'review_policies' => $review_policy,
							'custom_subforms' => $custom_subfoms,
							'mode' => "edit"
					);
				
					break;
					
					case 'wAdminArchivePolicies':
						//---------------------------------------------------------
						$archive_policy = $em->getRepository('DocovaBundle:ArchivePolicies')->findOneBy(array('id' => $doc_id)); 
						$libraries	= $em->getRepository('DocovaBundle:Libraries')->findBy(array('Status' => true, 'Trash' => false));
						$doctypes	= $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true, 'Trash' => false), array('Doc_Name' => 'ASC'));

							
						// when posting form data
						if ($request->isMethod('POST'))
						{
							$req = $request->request;
							$this->saveArchivePolicyData($em,$archive_policy,$doctypes, $libraries, $doc_id, $req);
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$archive_policy->getId().'&loadaction=reloadview&closeframe=true');
						}
						// set array for twig template
						$params = array(
									'user' => $this->user,
									'document' => $archive_policy,
									'libraries' => $libraries,
									'doctypes' => $doctypes,
									'view_name' => $view_name,
									'settings' => $this->global_settings,
									'view_title' => 'Archive Policy',
									'mode' => "edit"			
						);
						break;
					case 'wAdminReviewPolicies':
						$review_policy = $em->getRepository('DocovaBundle:ReviewPolicies')->find($doc_id);
						$libraries	= $em->getRepository('DocovaBundle:Libraries')->findBy(array('Status' => true, 'Trash' => false));
						$doctypes	= $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true, 'Trash' => false), array('Doc_Name' => 'ASC'));
						$system_messages = $em->getRepository('DocovaBundle:SystemMessages')->findBy(array('Systemic' => false, 'Trash' => false), array('Message_Name' => 'ASC'));
						
						if ($request->isMethod('POST')) 
						{
							$req = $request->request;
							$this->saveReviewPolicyData($em, $doctypes, $libraries, $req, $review_policy);
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$review_policy->getId().'&loadaction=reloadview&closeframe=true');
						}

						$params = array(
							'user' => $this->user,
							'document' => $review_policy,
							'libraries' => $libraries,
							'doctypes' => $doctypes,
							'system_messages' => $system_messages,
							'settings' => $this->global_settings,
							'view_name' => $view_name,
							'view_title' => 'Review Policy',
							'mode' => 'edit'
						);
						break;
					case 'wAdminHTMLResource':
						//---------------------------------------------------------
						//get required objects
						$html_resource=$em->getRepository('DocovaBundle:HtmlResources')->findOneBy(array('id' => $doc_id));
					
						// when posting form data
						if ($request->isMethod('POST'))
						{
							$req = $request->request;
							$this->saveHtmlResourceData($em,$html_resource, $doc_id, $req);
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$html_resource->getId().'&loadaction=reloadview&closeframe=true');
						}

						$params = array(
								'user' => $this->user,
								'view_name' => $view_name,
								'view_title' =>"HTML Resource",
								'settings' => $this->global_settings,
								'document' => $html_resource,
								'mode' => 'edit'
						);
					
						break;
					case 'wAdminCustomSearchFields':
						//---------------------------------------------------------
						$custom_search_field = $em->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('id' => $doc_id));
						$docTypes	= $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Status' => true), array('Doc_Name' => 'ASC'));
						// check if its a post request
						if ($request->isMethod('POST'))
						{
							$req = $request->request;
							$this->saveCustomSearchFieldData($em,$custom_search_field,$docTypes, $doc_id, $req);
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$custom_search_field->getId().'&loadaction=reloadview&closeframe=true');
						}
						// set array for twig template
						$params = array(
								'user' => $this->user,
								'document' => $custom_search_field,
								'docTypes' => $docTypes,
								'settings' => $this->global_settings,
								'view_name' => $view_name,
								'view_title' => 'Custom Search Field',
								'mode' => "edit"
						);
						break;	

					case 'wAdminActivities':
						//---------------------------------------------------------
						$activity = $em->getRepository('DocovaBundle:Activities')->findOneBy(array('id' => $doc_id));
						//var_dump($activity);
						// check if its a post request
						if ($request->isMethod('POST'))
						{
							$req = $request->request;
							$this->saveActivityData($em,$activity, $doc_id, $req);
							return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$activity->getId().'&loadaction=reloadview&closeframe=true');
						}
						// set array for twig template
						$params = array(
								'user' => $this->user,
								'document' => $activity,
								'settings' => $this->global_settings,
								'view_name' => $view_name,
								'view_title' => 'Activity',
								'mode' => "edit"
						);
						break;
			//---------------------------------------------------------
				default:
					throw $this->createNotFoundException('Routing cannot be found for '.$view_name);
			}
			return $this->render('DocovaBundle:Admin:edit'.$view_name.'Document.html.twig', $params);
		}
	}
	
	/*
	 * Update activity data
	 */
	private function saveActivityData($em,$activity, $doc_id,$request)
	{
		// check if there is any data
		if (empty($activity))
		{
			throw $this->createNotFoundException('Unspecified source custom search field ID = '. $doc_id);
		}
		try {
			$activity->setActivityAction($request->get("ActivityAction"));
			$activity->setActivityObligation($request->get('ActivityObligation'));
			$activity->setActivitySendMessage($request->get('ActivitySendMessage') == 1 ? true : false);
			$activity->setActivitySubject($request->get('ActivitySubject') ? $request->get('ActivitySubject') : null);
			$activity->setActivityInstruction($request->get("ActivityInstruction") ? $request->get("ActivityInstruction") : null);
			$em->flush();
		}
		catch (\Exception $e)
		{
			throw $this->createNotFoundException('Could not save activity data because of the following error <br />'. $e->getMessage());
			return false;
		}
	}
	
	//--------------- save archive policy data -------------------
	private function saveCustomSearchFieldData($em,$custom_search_field,$doctypes, $doc_id,$request)
	{
		// check if there is any data
		if (empty($custom_search_field))
		{
			throw $this->createNotFoundException('Unspecified source custom search field ID = '. $doc_id);
		}
		try {
				$custom_search_field->setFieldDescription($request->get('FieldDescription'));
				$fname = strtolower($request->get('FieldName'));
				$custom_search_field->setFieldName($request->get('FieldName'));
				$custom_search_field->setDataType($request->get('DataType'));
				$custom_search_field->setTextEntryType($request->get('TextEntryType'));
				$custom_search_field->setSelectValues($request->get('SelectValues'));
				$customFields = $em->getRepository('DocovaBundle:DesignElements')->findBy(array('fieldName' => $fname, 'trash' => false));
				if (!empty($customFields))
				{
					$cFields = null;
					foreach ($customFields as $field) {
						$cFields .= $field->getId() . ';';
					}
					$cFields = substr_replace($cFields, '', -1);
					$custom_search_field->setCustomField($cFields);
				}
				else {
					$custom_search_field->setCustomField();
				}
				unset($customFields);

			//--------------------------------Related Document Types ---------------------------------------
			// delete applicable document types if they exist
			if ($custom_search_field->getDocumentTypes()->count() > 0)
			{
				foreach ($custom_search_field->getDocumentTypes() as $dt)
				{
					$custom_search_field->removeDocumentTypes($dt);
				}
				$em->flush();
				$em->refresh($custom_search_field);
				unset($dt);
			}
	
			// check if new doc types need to be added
			if ($request->get('DocumentTypeOption') == 'S' && $request->get('DocumentType')){
				$doctypes_selected = (is_array($request->get('DocumentType'))) ? $request->get('DocumentType') : array($request->get('DocumentType'));
				foreach ($doctypes_selected as $docKey)
				{
					$document_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Status' => true, 'id' => $docKey, 'Trash' => false));
					if (!empty($document_type)) {
						$custom_search_field->addDocumentTypes($document_type);
					}
				}
				unset($doctypes_selected, $document_type);
			}
			//----------------------------------------------------------------------------------------------------
				
			// update record
			$em->flush();
		}
		catch (\Exception $e)
		{
			throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
			return false;
		}
	}
	
	
	//--------------- to save file template data -------------------
	private function saveHtmlResourceData($em,$html_resource, $doc_id, $request)
	{
		// check if there is any data
		if (empty($html_resource))
		{
			throw $this->createNotFoundException('Unspecified source File Template ID = '. $doc_id);
		}
		try {
	
				$html_resource->setResourceName($request->get('ResourceName'));
				$html_resource->setHtmlCode($request->get('HtmlCode'));
				
				$fileAttachments=$this->get('request_stack')->getCurrentRequest()->files;
				$file=$fileAttachments->get('fileTemplateAttachment');
				// check if update is required for file
				if($file){
						
					// first delete the file
					$filePath=$this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'HtmlResources'.DIRECTORY_SEPARATOR.md5($html_resource->getFileName());
					//var_dump($filePath);
					@unlink($filePath);
					if (!is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'HtmlResources')) {
						@mkdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'HtmlResources');
					}
					$res = $file->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'HtmlResources', md5(basename($file->getClientOriginalName())));
					//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), '0777');
					//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), '0666');
					$html_resource->setFileName($file->getClientOriginalName());
					$html_resource->setFileMimeType($res->getMimeType());
					$html_resource->setFileSize($file->getClientSize());
				}
	
					//-------------------------------------------------------------------
	

	
					$em->persist($html_resource); // create entity record
					$em->flush(); // commit entity record
		}
		catch (\Exception $e)
		{
			throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
			return false;
		}
	}
	
	//--------------- save archive policy data -------------------
	private function saveArchivePolicyData($em,$archive_policy,$doctypes,$applicableLibs, $doc_id,$request)
	{
		// check if there is any data
		if (empty($archive_policy))
		{
			throw $this->createNotFoundException('Unspecified source view column ID ID = '. $doc_id);
		}
		try {
			//--------- get view column form fields ----------------------
			############ to delete (for content assist )#################
			//$archive_policy instanceof \Docova\DocovaBundle\Entity\ArchivePolicies;
			######################## to delete #################
			// ---------- get form fields from post --------------------------
			//Policy Name
			$archive_policy->setPolicyName($request->get('PolicyName'));
			//Description
			$archive_policy->setDescription($request->get('Description'));
			//Status
			$archive_policy->setPolicyStatus($request->get('PolicyStatus') ? true : false);
			//Priority
			$archive_policy->setPolicyPriority($request->get('PolicyPriority') ? $request->get('PolicyPriority') : 2) ;
			//-------------------------------Applicable Libraries-------------------------------
			// remove all applicable libraries if they exist
			if ($archive_policy->getLibraries()->count() > 0)
			{
				foreach ($archive_policy->getLibraries() as $al) {
					$archive_policy->removeLibraries($al);
				}
				// commit records for delete
				$em->flush();
				$em->refresh($archive_policy);
				unset($al);
			}
			
			// add applicable libraries with selected option
			if ($request->get('LibraryOption') == 'S' && $request->get('Library')){
				// add new entries
				$libraries_selected = (is_array($request->get('Library'))) ? $request->get('Library') : array($request->get('Library'));
				foreach ($libraries_selected as $library)
				{
					$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('Status' => true, 'Trash' => false, 'id' => $library));
					if (!empty($library)) {
						$archive_policy->addLibraries($library);
					}
				}
				unset($libraries_selected, $library);
				
			}
			//--------------------------------Related Document Types ---------------------------------------
			// delete applicable document types if they exist
			if ($archive_policy->getDocumentTypes()->count() > 0)
			{
				foreach ($archive_policy->getDocumentTypes() as $dt)
				{
					$archive_policy->removeDocumentTypes($dt);
				}
				$em->flush();
				$em->refresh($archive_policy);
				unset($dt);
			}

			// check if new doc types need to be added
			if ($request->get('DocumentTypeOption') == 'S' && $request->get('DocumentType')){
					$doctypes_selected = (is_array($request->get('DocumentType'))) ? $request->get('DocumentType') : array($request->get('DocumentType'));
					foreach ($doctypes_selected as $docKey)
					{
						$document_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Status' => true, 'id' => $docKey, 'Trash' => false));
						if (!empty($document_type)) {
							$archive_policy->addDocumentTypes($document_type);
						}
					}
					unset($doctypes_selected, $document_type);
			}
			
			//------------------ Selction criteria -----------------------------------------------
			//Documents that were
			$archive_policy->setEnableDateArchive($request->get('EnableDateArchive') ? true : false);
			//ArchiveDateSelect ( more then )
			$archive_policy->setArchiveDateSelect($request->get('ArchiveDateSelect') ? $request->get('ArchiveDateSelect') : "LastModifiedDate") ;
			//ArchiveDelay ( day(s) ago)
			$archive_policy->setArchiveDelay($request->get('ArchiveDelay'));
			//ArchiveCustomFormula ( and satisfy the following custom formula )
			$archive_policy->setArchiveCustomFormula($request->get('ArchiveCustomFormula'));
				
			//----------------- Exemptions ----------------------------------------------------------
			//ArchiveSkipWorkflow: Do not archive documents with active workflow
			$archive_policy->setArchiveSkipWorkflow($request->get('ArchiveSkipWorkflow') ? true : false);
			//ArchiveSkipDrafts :Do not archive active draft documents
			$archive_policy->setArchiveSkipDrafts($request->get('ArchiveSkipDrafts') ? true : false);
			//ArchiveSkipVersions: Keep latest
			$archive_policy->setArchiveSkipVersions($request->get('ArchiveSkipVersions') ? true : false);
			//VersionCount: releases in the version stream (discarded drafts are not counted)
			$archive_policy->setVersionCount($request->get('VersionCount'));
			
			
			// update record
			$em->flush();
		}
		catch (\Exception $e)
		{
			throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
			return false;
		}
	}
	
	/**
	 * Create/Edit a review policy
	 * 
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param array of \Docova\DocovaBundle\Entity\DocumentTypes $doctypes
	 * @param array of \Docova\DocovaBundle\Entity\Libraries $libraries
	 * @param Request $request
	 * @param null|\Docova\DocovaBundle\Entity\ReviewPolicies $review_policy [optional()]
	 * @return \Docova\DocovaBundle\Entity\ReviewPolicies
	 */
	private function saveReviewPolicyData($em, $doctypes, $libraries, $request, $review_policy =  null)
	{
		if (!trim($request->get('PolicyName')) || (!trim($request->get('AuthorReview')) && !trim($request->get('Reviewers'))))
		{
			throw $this->createNotFoundException('Policy Name and/or Action - Reviewer are/is missed.');
		}
			
		if (trim($request->get('wfEnableActivateMsg')) && !trim($request->get('wfActivateNotifyParticipants')) && !trim($request->get('wfActivateNotifyField')) && !trim($request->get('wfActivateNotifyList')))
		{
			throw $this->createNotFoundException('"Send" selected but no recipients defined');
		}
		
		if (trim($request->get('wfEnableDelayMsg')) && !trim($request->get('wfDelayNotifyParticipants')) && !trim($request->get('wfDelayNotifyList')))
		{
			throw $this->createNotFoundException('"Send" action selected without any recipients');
		}
		
		if (trim($request->get('wfEnableDelayEsclMsg')) && !trim($request->get('wfDelayEsclNotifyParticipants')) && !trim($request->get('wfDelayEsclNotifyList')))
		{
			throw $this->createNotFoundException('"Send" action selected without any recipients');
		}

		$is_new = false;
		if (empty($review_policy)) 
		{
			$review_policy = new ReviewPolicies();
			$is_new = true;
		}

		$review_policy->setPolicyName($request->get('PolicyName'));
		$review_policy->setDescription($request->get('Description') ? $request->get('Description') : null);
		$review_policy->setPolicyStatus($request->get('PolicyStatus') ? true : false);
		$review_policy->setPolicyPriority($request->get('PolicyPriority') ? $request->get('PolicyPriority') : 2);
		$doc_status = ($request->get('DocumentStatusDraft')) ? 1 : 0;
		$doc_status = ($request->get('DocumentStatusRelease')) ? ($doc_status+2) : $doc_status; 
		$review_policy->setDocumentStatus($doc_status);
		$review_policy->setReviewPeriod($request->get('ReviewPeriod') ? $request->get('ReviewPeriod') : 1);
		$review_policy->setReviewPeriodOption($request->get('ReviewPeriodOption'));
		$review_policy->setCustomReviewPeriodOption(($request->get('ReviewPeriodOption') == 'C' && trim($request->get('CustomReviewPeriodOption'))) ? $request->get('CustomReviewPeriodOption') : null);
		$review_policy->setCustomReviewPeriod(trim($request->get('CustomReviewPeriod')) ? $request->get('CustomReviewPeriod') : null);
		$review_policy->setReviewDateSelect($request->get('ReviewDateSelect'));
		$review_policy->setCustomReviewDateSelect(($request->get('CustomReviewDateSelect') && $request->get('ReviewDateSelect') == 'custom') ? $request->get('CustomReviewDateSelect') : null);
		$review_policy->setReviewStartDayOption($request->get('ReviewStartDayOption') ? true : false);
		$review_policy->setReviewStartMonth($request->get('ReviewStartDayOption') && (int)$request->get('ReviewStartMonth') ? $request->get('ReviewStartMonth') : null);
		$review_policy->setReviewStartDay($request->get('ReviewStartDayOption') && (int)$request->get('ReviewStartDay') ? $request->get('ReviewStartDay'): null);
		$review_policy->setAuthorReview($request->get('AuthorReview') ? true : false);
		$review_policy->setAdditionalEditorReview($request->get('AdditionalEditorReview') ? true : false);
		$review_policy->setReviewersField(trim($request->get('wfReviewersField')) ? $request->get('wfReviewersField') : null);
		$review_policy->setActivateNotifyParticipants($request->get('wfActivateNotifyParticipants') ? true : false);
		$review_policy->setActivateNotifyField(trim($request->get('wfActivateNotifyField')) ? $request->get('wfActivateNotifyField') : null);
		$review_policy->setReviewCustomFormula(trim($request->get('ReviewCustomFormula')) ? trim($request->get('ReviewCustomFormula')) : null);
		if ($request->get('wfEnableActivateMsg'))
		{
			$activate_message = ($request->get('wfActivateMsg') == 'D') ? $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => true, 'Message_Name' => 'DEFAULT_REVIEWACTIVATE')) : $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => false, 'id' => $request->get('wfActivateMsg')));
			if (empty($activate_message))
			{
				$activate_message = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => true, 'Message_Name' => 'DEFAULT_REVIEWACTIVATE'));
			}

			$review_policy->setEnableActivateMsg($activate_message);
			unset($activate_message);
		}
		else {
			$review_policy->setEnableActivateMsg(null);
		}
		$review_policy->setReviewAdvance(trim($request->get('ReviewAdvance')) ? $request->get('ReviewAdvance') : 1);
		$review_policy->setReviewGracePeriod(trim($request->get('ReviewGracePeriod')) ? $request->get('ReviewGracePeriod') : 30);
		$review_policy->setDelayCompleteThreshold(trim($request->get('wfDelayCompleteThreshold')) ? $request->get('wfDelayCompleteThreshold') : 3);
		$review_policy->setDelayNotifyParticipants($request->get('wfDelayNotifyParticipants') ? true : false);
		if ($request->get('wfEnableDelayMsg'))
		{
			$delay_message = ($request->get('wfDelayMsg') == 'D') ? $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => true, 'Message_Name' => 'DEFAULT_DELAY')) : $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => false, 'id' => $request->get('wfDelayMsg')));
			if (empty($delay_message))
			{
				$delay_message = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => true, 'Message_Name' => 'DEFAULT_DELAY'));
			}
			$review_policy->setEnableDelayMsg($delay_message);
			unset($delay_message);
		}
		else {
			$review_policy->setEnableDelayMsg(null);
		}
		$review_policy->setDelayEsclThreshold(trim($request->get('wfDelayEsclThreshold')) ? $request->get('wfDelayEsclThreshold') : 3);
		$review_policy->setDelayEsclNotifyParticipant($request->get('wfDelayEsclNotifyParticipants') ? true : false);
		if ($request->get('wfEnableDelayEsclMsg')) {
			$delay_escl_msg = ($request->get('wfDelayEsclMsg') == 'D') ? $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => true, 'Message_Name' => 'DEFAULT_DELAYESCL')) : $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => false, 'id' => $request->get('wfDelayEsclMsg')));
			if (empty($delay_escl_msg))
			{
				$delay_escl_msg = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => true, 'Message_Name' => 'DEFAULT_DELAYESCL'));
			}
			$review_policy->setEnableDelayEsclMsg($delay_escl_msg);
			unset($delay_escl_msg);
		}

		if ($is_new === true) 
		{
			$em->persist($review_policy);
		}
		else {
			if ($review_policy->getLibraries()->count() > 0)
			{
				$review_policy->clearAllLibraries();
			}
			
			if ($review_policy->getDocumentTypes()->count() > 0) 
			{
				$review_policy->clearDocumentTypes();
			}
			
			if ($review_policy->getReviewers()->count() > 0) 
			{
				$review_policy->clearReviewers();
			}
			
			if ($review_policy->getActivateNotifyList()->count() > 0) 
			{
				$review_policy->clearActivateNotifyList();
			}
			
			if ($review_policy->getDelayNotifyList()->count() > 0) 
			{
				$review_policy->clearDelayNotifyList();
			}
			
			if ($review_policy->getDelayEsclNotifyList()->count() > 0) 
			{
				$review_policy->clearDelayEsclNotifyList();
			}
			
			$em->flush();
		}
			
		if ($request->get('LibraryOption') == 'S' && $request->get('Library'))
		{
			$libraries_selected = (is_array($request->get('Library'))) ? $request->get('Library') : array($request->get('Library'));
			foreach ($libraries_selected as $library)
			{
				$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('Status' => true, 'Trash' => false, 'id' => $library));
				if (!empty($library)) {
					$review_policy->addLibraries($library);
				}
			}
			unset($libraries_selected, $library);
		}
		
		if ($request->get('DocumentTypeOption') == 'S' && $request->get('DocumentType'))
		{
			$doctypes_selected = (is_array($request->get('DocumentType'))) ? $request->get('DocumentType') : array($request->get('DocumentType'));
			foreach ($doctypes_selected as $dt)
			{
				$document_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Status' => true, 'id' => $dt, 'Trash' => false));
				if (!empty($document_type)) {
					$review_policy->addDocumentTypes($document_type);
				}
			}
			unset($doctypes_selected, $document_type);
		}
		
		$reviewer_groups = array();
		if (trim($request->get('Reviewers'))) {
		    $reviewers = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $request->get('Reviewers'));
			for ($x = 0; $x < count($reviewers); $x++)
			{
				if (false !== $user = $this->findUserAndCreate($reviewers[$x])) {
					$review_policy->addReviewers($user);
				}
				else {
					$reviewer_groups[] = $reviewers[$x];
				}
			}
			unset($reviewers, $user);
		}
		$review_policy->setReviewerGroups($reviewer_groups);

		$activate_groups = array();
		if ($request->get('wfEnableActivateMsg') && trim($request->get('wfActivateNotifyList'))) 
		{
		    $activate_notify_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $request->get('wfActivateNotifyList'));
			for ($x = 0; $x < count($activate_notify_list); $x++)
			{
				if (false !== $user = $this->findUserAndCreate($activate_notify_list[$x])) {
					$review_policy->addActivateNotifyList($user);
				}
				else {
					$activate_groups[] = $activate_notify_list[$x];
				}
			}
			unset($activate_notify_list, $user);
		}
		$review_policy->setActivateNotifyGroups($activate_groups);

		$delay_groups = array();
		if ($request->get('wfEnableDelayMsg') && trim($request->get('wfDelayNotifyList')))
		{
		    $delay_notify_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $request->get('wfDelayNotifyList'));
			for ($x = 0; $x < count($delay_notify_list); $x++)
			{
				if (false !== $user = $this->findUserAndCreate($activate_notify_list[$x])) {
					$review_policy->addDelayNotifyList($user);
				}
				else {
					$delay_groups[] = $delay_notify_list[$x];
				}
			}
			unset($delay_notify_list, $user);
		}
		$review_policy->setDelayNotifyGroups($delay_groups);

		$delayEscl_groups = array();
		if ($request->get('wfEnableDelayEsclMsg') && trim($request->get('wfDelayEsclNotifyList')))
		{
		    $delay_escl_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $request->get('wfDelayEsclNotifyList'));
			for ($x = 0; $x < count($delay_escl_list); $x++)
			{
				if (false !== $user = $this->findUserAndCreate($delay_escl_list[$x])) {
					$review_policy->addDelayEsclNotifyList($user);
				}
				else {
					$delayEscl_groups[] = $delay_escl_list[$x];
				}
			}
			unset($delay_escl_list, $user);
		}
		$review_policy->setDelayEsclNotifyGroups($delayEscl_groups);
		
		$em->flush();
		
		return $review_policy;
	}
	
	//--------------- to save file template data -------------------
	private function saveFileTemplateData($em,$file_template, $doc_id, $request)
	{
		// check if there is any data
		if (empty($file_template))
		{
			throw $this->createNotFoundException('Unspecified source File Template ID = '. $doc_id);
		}
		try {
			$file_template->setTemplateName($request->get('TemplateName'));
			$file_template->setTemplateType($request->get('TemplateType'));

			// get file attachment
			$fileAttachments=$this->get('request_stack')->getCurrentRequest()->files;
			$file=$fileAttachments->get('fileTemplateAttachment');
			// check if update is required for file
			if($file)
			{
				// first delete the file
				 $filePath=$this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.md5($file_template->getFileName());
				 @unlink($filePath);
				
				if (!is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Templates')) {
					@mkdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Templates');
				}
				$res = $file->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Templates', md5(basename($file->getClientOriginalName())));
				$file_template->setFileName($file->getClientOriginalName());
				$file_template->setFileMimeType($res->getMimeType());
				$file_template->setFileSize($file->getClientSize());
			}
				
			$file_template->setDateCreated(new \DateTime());
			$file_template->setTemplateVersion($request->get('TemplateVersion'));

			$em->persist($file_template); // create entity record
			$em->flush(); // commit entity record
		}
		catch (\Exception $e)
		{
			throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
			return false;
		}
	}

	
	/**
	 * Save modifications of a user profile
	 * 
	 * @param \Doctrine\Common\Persistence\ObjectManager $em
	 * @param Request $request
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user_account
	 * @param integer $doc_id
	 */
	private function saveUserProfile($em, $request, $user_account, $doc_id)
	{
		if (empty($user_account)) 
		{
			throw $this->createNotFoundException('Unspecifed source user ID = '.$doc_id);
		}
		
		try {
			if (!trim($request->request->get('FirstName')) || !trim($request->request->get('LastName')) || !trim($request->request->get('Email')) || !trim($request->request->get('UserMailSystem')))
			{
				throw $this->createNotFoundException('One or more required form inputs are missed.');
			}
			
			if (false == filter_var($request->request->get('Email', FILTER_VALIDATE_EMAIL)))
			{
				throw $this->createNotFoundException('"'.$request->request->get('Email').'" is an invalid email address.');
			}

			if ($request->query->get('uType') != 'user' && $request->request->get('UserRole')) {
				$user_role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $request->request->get('UserRole')));
				if (!empty($user_role))
				{
					if ($user_account->getUserRoles()->count() > 0)
					{
						foreach ($user_account->getUserRoles() as $role) {
							if ($role->getRole() == 'ROLE_USER' || $role->getRole() == 'ROLE_ADMIN') {
								$user_account->removeUserRoles($role);
								$em->flush();
							}
						}
						unset($role);
						$user_account->addRoles($user_role);
					}
					else {
						$user_account->addRoles($user_role);
					}
					unset($user_role);
				}
				else {
					unset($user_account);
					throw $this->createNotFoundException('Invalid role source is entered.');
				}
			}

			if ($this->user->getId() == $user_account->getId() && $user_account->getUserMail() != $request->request->get('Email')) 
			{
				$session = $this->get('session');
				$session->remove('epass');
				unset($session);
			}
			$user_account->setUserMail($request->request->get('Email'));
			$user_type = ($request->query->get('uType') == 'user') ? $user_account->getUserProfile()->getAccountType() : (($request->request->get('AccountType') == 1) ? true : false); // TRUE = External; FALSE = Internal;
			if ($user_type === true && $this->global_settings->getDBAuthentication())
			{
				$user_pass = $request->request->get('NewPassword');
				if (!empty($user_pass)) {
					$user_account->setPassword(md5(md5(md5($user_pass))));
				}
			}
			elseif ($user_type == false && $user_account->getUserProfile()->getAccountType() == true) {
				$user_account->setPassword(null);
			}
			
			$em->flush();
				
			$user_profile = $user_account->getUserProfile();
			$user_profile->setFirstName($request->request->get('FirstName'));
			$user_profile->setLastName($request->request->get('LastName'));
			$user_profile->setLoadLibraryFolders($request->request->get('LoadLibraryFolders'));
			$user_profile->setUserMailSystem($request->request->get('UserMailSystem'));
			$user_profile->setLanguage(trim($request->request->get('UserLocale')) ? trim($request->request->get('UserLocale')) : 'en');
			$user_profile->setTimeZone(trim($request->request->get('UserTimeZone')) ? trim($request->request->get('UserTimeZone')) : '');
			if ($request->request->get('UserMailSystem') == 'O') {
				$user_profile->setEnableDebugMode($request->request->get('enableDebugMode') ? true : false);
				$user_profile->setOutlookMsgsToShow($request->request->get('OutlookMsgsToShow') ? $request->request->get('OutlookMsgsToShow') : null);
				$user_profile->setExcludeOutlookFolders($request->request->get('ExcludeOutlookPersonalFolders') ? $request->request->get('ExcludeOutlookPersonalFolders') : null);
				$user_profile->setExcludeOutlookInboxLevel($request->request->get('ExcludeOutlookInboxLevelFolders') ? $request->request->get('ExcludeOutlookInboxLevelFolders') : null);
			}
			else {
				$user_profile->setEnableDebugMode(false);
				$user_profile->setOutlookMsgsToShow(null);
				$user_profile->setExcludeOutlookFolders(null);
				$user_profile->setExcludeOutlookInboxLevel(null);
			}
			switch ($request->request->get('UserMailSystem')) {
				case 'N':
					$user_profile->setMailServerURL($this->global_settings->getNotesMailServer() ? $this->global_settings->getNotesMailServer() : 'prod02.dlitools.com');
					break;
				case 'G':
					$user_profile->setMailServerURL('imap.gmail.com:993/imap/ssl');
					break;
				case 'Y':
					$user_profile->setMailServerURL('imap.mail.yahoo.com:993/imap/ssl');
					break;
				case 'H':
					$user_profile->setMailServerURL('imap-mail.outlook.com:993/imap/ssl');
					break;
				case 'X':
					$isSSL=$request->request->get('mailServerPort')=='993' ? true : false;
					if ($isSSL)
						$user_profile->setMailServerURL($request->request->get('mailServerUrl').':'.$request->request->get('mailServerPort').'/imap/ssl');
					else
						$user_profile->setMailServerURL($request->request->get('mailServerUrl').':'.$request->request->get('mailServerPort').'/imap/notls');
					break;
				default:
					$user_profile->setMailServerURL('N/A');
					break;
			}
			$user_profile->setDisplayName($request->request->get('DisplayName'));
			$user_profile->setAccountType(($user_type === true && $this->global_settings->getDBAuthentication()) ? true : false);
			$user_profile->setNotifyUser($request->request->get('notifyUser') ? true : false);
			if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
				$user_profile->setCanCreateApp($request->request->get('CanCreateApps') == 'Yes' ? true: false);
			$user_profile->setRecentEditCount($request->request->get('RecentEditCount') ? $request->get('RecentEditCount') : 10);
			$user_profile->setRecentUsedAppCount($request->request->get('RecentUsedAppCount') ? $request->get('RecentUsedAppCount') : 5);
			$user_profile->setNumberFormat($request->request->get('NumberFormat') ? $request->get('NumberFormat') : null);
			$user_profile->setDefaultShowOption($request->request->get('DefaultShowOption') ? $request->get('DefaultShowOption') : null);
			$user_profile->setHideLogout($request->request->get('HideLogout') == 1 ? true : false);
			$user_profile->setRedirectToMobile($request->request->get('RedirectToMobile') == 1 ? true : false);
			$user_profile->setTitle($request->request->get('Title') ? $request->request->get('Title') : null);
			$user_profile->setDepartment($request->request->get('department') ?$request->request->get('department') : null);
			if (trim($request->request->get('myboss')))
			{
				if (false !== $Manager = $this->findUserAndCreate($request->request->get('myboss')))
				{
					$user_profile->setManager($Manager);
				}
			}
			else {
				$user_profile->setManager(null);
			}
			$user_profile->setOfficeNo($request->request->get('officeNo') ? $request->request->get('officeNo') : null);
			$user_profile->setMobileNo($request->request->get('mobileNo') ? $request->request->get('mobileNo') : null);
			$user_profile->setExpertise($request->request->get('expertise') ? $request->request->get('expertise') : null);
			$user_profile->setTheme($request->request->get('UserTheme') ? $request->request->get('UserTheme') : 'redmond');
			$user_profile->setWorkspaceTheme($request->request->get('WorkspaceTheme') == 'T' ? 'T' : 'S');
			if ($request->request->get('fltrField1')) {
				$column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $request->request->get('fltrField1')));
				if (!empty($column)) {
					$user_profile->setFltrField1($column);
					$user_profile->setFltrFieldVal1($request->request->get('fltrFieldVal1') ? $request->request->get('fltrFieldVal1') : null);
				}
			}
			else {
				$user_profile->setFltrField1(null);
				$user_profile->setFltrFieldVal1(null);
			}
			if ($request->request->get('fltrField2')) {
				$column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $request->request->get('fltrField2')));
				if (!empty($column)) {
					$user_profile->setFltrField2($column);
					$user_profile->setFltrFieldVal2($request->request->get('fltrFieldVal2') ? $request->request->get('fltrFieldVal2') : null);
				}
			}
			else {
				$user_profile->setFltrField2(null);
				$user_profile->setFltrFieldVal2(null);
			}
			if ($request->request->get('fltrField3')) {
				$column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $request->request->get('fltrField3')));
				if (!empty($column)) {
					$user_profile->setFltrField3($column);
					$user_profile->setFltrFieldVal3($request->request->get('fltrFieldVal3') ? $request->request->get('fltrFieldVal3') : null);
				}
			}
			else {
				$user_profile->setFltrField3(null);
				$user_profile->setFltrFieldVal3(null);
			}
			if ($request->request->get('fltrField4')) {
				$column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => $request->request->get('fltrField4')));
				if (!empty($column)) {
					$user_profile->setFltrField4($column);
					$user_profile->setFltrFieldVal4($request->request->get('fltrFieldVal4') ? $request->request->get('fltrFieldVal4') : null);
				}
			}
			else {
				$user_profile->setFltrField4(null);
				$user_profile->setFltrFieldVal4(null);
			}

			$em->flush();
			$em->refresh($user_account);
			if ($user_account->getId() == $this->user->getId()) 
			{
				$session = $this->get('session');
				$session->set('user_locale', $user_profile->getLanguage());
				unset($session);
			}

			if ($request->request->get('notifyUser')) {
				if ($user_type === true && $this->global_settings->getDBAuthentication())
				{
					$sysmessage = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'NewRegistrationNotifyMessage', 'Systemic' => false));
					$message = array(
						'subject' => MailController::parseTokens($this->container, $sysmessage->getSubjectLine(), $sysmessage->getLinkType(), null, array('DocovaUsername' => $user_account->getUserNameDnAbbreviated(), 'DocovaPassword' => $user_pass)),
						'content' => MailController::parseTokens($this->container, $sysmessage->getMessageContent(), $sysmessage->getLinkType()),
						'senders' => $sysmessage->getSenders(),
						'groups' => $sysmessage->getSenderGroups()
					);
					MailController::parseMessageAndSend($message, $this->container, array($request->request->get('Email')));
				}
				else {
					$message['subject'] = 'Docova User Registration';
					$message['content'] = <<<MESSAGE
Please click on the below link to get started!
					
Click here to launch DOCOVA: <a href="{$this->generateUrl('docova_homepage', array(), true)}" alt="{$this->global_settings->getApplicationTitle()}">{$this->global_settings->getApplicationTitle()}</a>
You can use your local username/email and password to login.
If you are having problems accessing DOCOVA, please contact your system administrator.
					
Docova Web Admin
MESSAGE;
					MailController::parseMessageAndSend($message, $this->container, array($request->request->get('Email')));
				}
			}

			if (!empty($request->files) && $file = $request->files->get('Uploader_DLI_Tools'))
			{
				if (false !== stripos($file->getMimeType(), 'image/'))
				{
					$res = $file->move($this->get('kernel')->getRootDir().'/../web/upload/userprofiles/', md5($user_account->getUserNameDnAbbreviated()));
					if (empty($res)) {
						//@TODO: log the profile picture is not uploaded
					}
				}
			}

		}
		catch (\Exception $e) {
			throw $this->createNotFoundException('Could not save user profile; check the following error(s)<br />'.$e->getMessage());
		}
	}
	
	//--------------- save view column data -------------------
	private function saveViewColumnsData($em,$view_column,$doctypes,$applicableLibs, $doc_id,$request)
	{
		// check if there is any data
		if (empty($view_column))
		{
			throw $this->createNotFoundException('Unspecified source view column ID ID = '. $doc_id);
		}
		try {
			//--------- get view column form fields ----------------------
			############ to delete (for content assist )#################
			$view_column instanceof \Docova\DocovaBundle\Entity\ViewColumns;
			######################## to delete #################
				
			//--------- get view column form fields ----------------------
			// Column Type
			$view_column->setColumnType( $request->get('ColumnType')=="1" ? true:false );
			//Column Status
			$view_column->setColumnStatus( $request->get('ColumnStatus')=="1" ? false:true) ;
			//Default Column Title
			$view_column->setTitle( $request->get("ColumnHeading") );
			//field or formula
			$view_column->setFieldName( $request->get("DataQuery") );
			//DataType
			$view_column->setDataType( $request->get("DataType") );
			//Default Width
			$view_column->setWidth( $request->get("ColumnWidth") );
			// XML Node Name
			$view_column->setXMLName( $request->get("XmlNode") );

			// delete if any entries exist
			if ($view_column->getRelatedDocTypes()->count() > 0)
			{
				foreach ($view_column->getRelatedDocTypes() as $rdt) {
					$view_column->removeRelatedDocType($rdt);
				}
				// commit records for delete
				$em->flush();
			}

			// delete existing entries
			if ($view_column->getApplicableLibraries()->count() > 0)
			{
				foreach ($view_column->getApplicableLibraries() as $al) {
					$view_column->removeApplicableLibrary($al);
			
				}
				// commit records for delete
				$em->flush();
			}

			// Related Document Types
			if( $request->get('RelatedDocType')=="S"){
				// add the entries
				$docTypeOptions=$request->get('DocTypeKey');
				foreach ($docTypeOptions as $value){
					$temp=$em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $value, 'Trash' => false));
					$view_column->addRelatedDocTypes($temp);
				}
			}
			// applicpable libraries
			if( $request->get('ApplicableLibraries')=="S"){
				// add new entries
				$libOptions=$request->get('Libraries');
				foreach ($libOptions as $value){
					$temp=$em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $value, 'Trash' => false));
					$view_column->addApplicableLibraries($temp);
				}
			}
			// update record
			$em->flush();
		}
		catch (\Exception $e)
		{
			throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
			return false;
		}
	}
	
	//--------------- TO DELETE -------------------
	private function editViewColumnsData($doc_id)
	{
		// get entity manager
		$em = $this->getDoctrine()->getManager();
		//get document by key
		$view_column = $em->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('id' => $doc_id));
		
		$doctypes = $em->getRepository('DocovaBundle:DocumentTypes')->findBy(array('Trash' => false));
		$applicableLibs=$em->getRepository('DocovaBundle:Libraries')->findBy(array('Trash' => false));
		
		// check if there is any data
		if (empty($view_column))
		{
			throw $this->createNotFoundException('Unspecified source view column ID ID = '. $doc_id);
		}
		// check if form was posted
		if ($this->get('request_stack')->getCurrentRequest()->isMethod('POST'))
		{
			//get the request
			$request = $this->get('request_stack')->getCurrentRequest()->request;
			// validate form fields
		
			try {
					//--------- get view column form fields ----------------------
					// to check the method ############ to delete #################
					$view_column instanceof \Docova\DocovaBundle\Entity\ViewColumns; 
					
					//--------- get view column form fields ----------------------
					// Column Type
					$view_column->setColumnType( $request->get('ColumnType')=="1" ? true:false );
					//Column Status
					$view_column->setColumnStatus( $request->get('ColumnStatus')=="1" ? false:true) ;
					//Default Column Title
					$view_column->setTitle( $request->get("ColumnHeading") );
					//field or formula
					$view_column->setFieldName( $request->get("DataQuery") );
					//DataType
					$view_column->setDataType( $request->get("DataType") );
					//Default Width
					$view_column->setWidth( $request->get("ColumnWidth") );
					// XML Node Name
					$view_column->setXMLName( $request->get("XmlNode") );
					
					// Related Document Types
					if( $request->get('RelatedDocType')=="S"){
						
						// delete if any entries exist
						if ($view_column->getRelatedDocTypes()->count() > 0)
						{
							foreach ($view_column->getRelatedDocTypes() as $rdt) {
								$view_column->removeRelatedDocType($rdt);
							}
							// commit records for delete
							$em->flush();
						}
						
						// add the entries
						$docTypeOptions=$request->get('DocTypeKey');
						foreach ($docTypeOptions as $value){
							$temp=$em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $value, 'Trash' => false));
							$view_column->addRelatedDocTypes($temp);
						}
					}
					// applicpable libraries
					if( $request->get('ApplicableLibraries')=="S"){
						// delete existing entries
						if ($view_column->getApplicableLibraries()->count() > 0)
						{
							foreach ($view_column->getApplicableLibraries() as $al) {
								$view_column->removeApplicableLibrary($al);

							}
							// commit records for delete
							$em->flush(); 
						}
						
						// add new entries
						$libOptions=$request->get('Libraries');
						foreach ($libOptions as $value){
							$temp=$em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $value, 'Trash' => false));
							$view_column->addApplicableLibraries($temp);
						}
					}
				// update record
				$em->flush();
		
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$view_column->getId().'&loadaction=reloadview&closeframe=true');
			}
			catch (\Exception $e)
			{
				throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
				return false;
			}
		} // end of if ($this->get('request_stack')->isMethod('POST'))

		$params = array(
				'user' => $this->user,
				'view_column' => $view_column,
				'relatedDoctypes'=>$doctypes,
				'applicableLibraries'=>$applicableLibs
					
		);
		
		return  $params;
	}
	
	/**
	 * Create or edit a document type
	 * 
	 * @param \Doctrine\Common\Persistence\ObjectManager $em
	 * @param Request $request
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $current_user
	 * @param \Docova\DocovaBundle\Entity\DocumentTypes $document_type
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	private function saveDocumentType($em, $request, $current_user, $document_type = null)
	{
		if (empty($document_type)) 
		{
			$doctype = new DocumentTypes();
		}
		else {
			$doctype = $document_type;
		
			if ($document_type->getDocTypeWorkflow()->count() > 0) 
			{
				$workflows = $document_type->getDocTypeWorkflow();
				foreach ($workflows as $wf)
				{
					$doctype->removeDocTypeWorkflow($wf);
				}
				unset($workflows, $wf);
			}
			
			if ($document_type->getDocTypeSubform()->count() > 0) 
			{
				$subforms = $document_type->getDocTypeSubform();
				foreach ($subforms as $sf)
				{
					$doctype->removeDocTypeSubform($sf);
					$em->remove($sf);
				}
				unset($subforms, $sf);
			}
		}

		// 1. Setting general tab values 
		$doctype->setStatus($request->get('disabled') ? false : true);
		$doctype->setKeyType($request->get('KeyType'));
		$doctype->setDocName($request->get('DocumentType'));
		$doctype->setDescription($request->get('DocumentTypeDescription') ? $request->get('DocumentTypeDescription') : null);
		$doctype->setDocIcon($request->get('DocIcon'));
		// 2. Setting content settings tab values
		// 		2-1. Body tab  
		$doctype->setPaperColor(trim($request->get('PaperColor')) ? str_replace('#', '', $request->get('PaperColor')) : null);
		$doctype->setDisableActivities($request->get('DisableActivities') ? true : false);
		// 		2-2. Header tab  
		$doctype->setHideSubject($request->get('HideSubject') ? true : false);
		$doctype->setSubjectLabel($request->get('CustomSubjectLabelText') ? $request->get('CustomSubjectLabelText') : 'Subject/Title');
		$doctype->setCustomSubjectFormula($request->get('CustomSubjectDefaultFormula') ? $request->get('CustomSubjectDefaultFormula') : null);
		$doctype->setTranslateSubjectTo($request->get('CustomSubjectTranslationFormula') ? $request->get('CustomSubjectTranslationFormula') : null);
		$doctype->setMoreSectionLabel($request->get('MoreSectionLabel') ? $request->get('MoreSectionLabel') : 'More');
		$doctype->setHideOnReading($request->get('HideHeaderRead') ? true : false);
		$doctype->setHideOnEditing($request->get('HideHeaderEdit') ? true : false);
		$doctype->setHideOnCustom($request->get('HideHeaderCustom') ? true : false);
		$doctype->setCustomHeaderHideWhen($request->get('HideHeaderCustom') && $request->get('CustomHeaderHideWhen') ? $request->get('CustomHeaderHideWhen') : null);
		// 		2-3. Header ACL tab  
		$doctype->setCustomChangeDocAuthor($request->get('CustomChangeDocAuthor') ? $request->get('CustomChangeDocAuthor') : null);
		$doctype->setCustomHideDescription($request->get('CustomHideDescription') ? $request->get('CustomHideDescription') : null);
		$doctype->setCustomHideKeywords($request->get('CustomHideKeywords') ? $request->get('CustomHideKeywords') : null);
		$doctype->setCustomHideReviewCycle($request->get('CustomHideReviewCycle') ? $request->get('CustomHideReviewCycle') : null);
		$doctype->setCustomHideArchiving($request->get('CustomHideArchiving') ? $request->get('CustomHideArchiving') : null);
		$doctype->setCustomChangeOwner($request->get('CustomChangeOwner') ? $request->get('CustomChangeOwner') : null);
		$doctype->setCustomChangeAddEditors($request->get('CustomChangeAddEditors') ? $request->get('CustomChangeAddEditors') : null);
		$doctype->setCustomChangeReadAccess($request->get('CustomChangeReadAccess') ? $request->get('CustomChangeReadAccess') : null);
		// 		2-4. Content tab  
		$doctype->setTopBanner($request->get('CustomTopBanner') ? $request->get('CustomTopBanner') : null);
		$doctype->setBottomBanner($request->get('CustomBottomBanner') ? $request->get('CustomBottomBanner') : null);
		$doctype->setSectionStyle($request->get('ContentAppearance') == 0 ? false : true);
		$doctype->setSectionLabel($request->get('lblContent') ? $request->get('lblContent') : (($request->get('HideSubject')) ? null : 'Content'));
		$doctype->setBackgroundColor($request->get('ContentBackgroundColorSelect') ? str_replace('#', '', $request->get('ContentBackgroundColorSelect')) : 'DFDFDF');
		$doctype->setAdditionalCss($request->get('ContentStyleSelect') ? $request->get('ContentStyleSelect') : null);
		// 2. Setting content settings common part  
		$doctype->setEnableForDle($request->get('EnableForDLE') ? true : false);
		$doctype->setHideDocsFromDoe($request->get('HideDocsFromDOE') ? true : false);
		$doctype->setHideDocsFromMobile($request->get('HideDocsFromMobile') ? true : false);
		$doctype->setEnableMailAcquire($request->get('EnableMailAcquire') ? true : false);
		$doctype->setEnableDiscussion($request->get('EnableDiscussion') ? true : false);
		$doctype->setPromptForMetadata($request->get('PromptForMetadata') ? true : false);
		$doctype->setAllowForwarding($request->get('EnableForwarding') ? true : false);
		$doctype->setForwardSave($request->get('EnableForwarding') ? $request->get('ForwardSave') : null);
		$sections = array();
		for ($x = 1; $x < 9; $x++) {
			$sections[] = ($request->get('ContentSection'.$x)) ? $request->get('ContentSection'.$x) : 'N';
		}
		$doctype->setContentSections($sections);
		
		// 3. Setting Workflow/Lifecycle settings
		$doctype->setEnableLifecycle($request->get('EnableLifecycle') ? true : false);
		if ($request->get('EnableLifecycle')) 
		{
			$doctype->setInitialStatus(trim($request->get('InitialStatus')) ? $request->get('InitialStatus') : 'Draft');
			$doctype->setFinalStatus(trim($request->get('FinalStatus')) ? $request->get('FinalStatus') : 'Released');
			$doctype->setSupersededStatus(trim($request->get('SupersededStatus')) ? $request->get('SupersededStatus') : 'Inactive');
			$doctype->setDiscardedStatus(trim($request->get('DiscardedStatus')) ? $request->get('DiscardedStatus') : 'Discarded');
			$doctype->setArchivedStatus(trim($request->get('ArchivedStatus')) ? $request->get('ArchivedStatus') : 'Archived');
			$doctype->setDeletedStatus(trim($request->get('DeletedStatus')) ? $request->get('DeletedStatus') : 'Deleted');
		}
		else {
			$doctype->setInitialStatus(null);
			$doctype->setSupersededStatus(null);
			$doctype->setDiscardedStatus(null);
			$doctype->setFinalStatus(trim($request->get('FinalStatus')) ? $request->get('FinalStatus') : 'Released');
			$doctype->setArchivedStatus(trim($request->get('ArchivedStatus')) ? $request->get('ArchivedStatus') : 'Archived');
			$doctype->setDeletedStatus(trim($request->get('DeletedStatus')) ? $request->get('DeletedStatus') : 'Deleted');
		}

		$doctype->setEnableVersions($request->get('EnableLifecycle') && $request->get('EnableVersions') ? true : false);
		$doctype->setRestrictLiveDrafts($request->get('EnableLifecycle') && $request->get('EnableVersions') && $request->get('RestrictLiveDrafts') ? true : false);
		$doctype->setEnableWorkflow($request->get('EnableLifecycle') && $request->get('ShowHeaders') ? true : false);
		$doctype->setStrictVersioning($request->get('EnableLifecycle') && $request->get('EnableVersions') && $request->get('StrictVersioning') ? true : false);
		$doctype->setAllowRetract($request->get('EnableLifecycle') && $request->get('EnableVersions') && $request->get('StrictVersioning') && $request->get('AllowRetract') ? true : false);
		$doctype->setRestrictDrafts($request->get('EnableLifecycle') && $request->get('EnableVersions') && $request->get('StrictVersioning') && $request->get('RestrictDrafts') ? true : false);
		$doctype->setUpdateBookmarks($request->get('EnableLifecycle') && $request->get('EnableVersions') && $request->get('UpdateBookmarks') ? true : false);
		$doctype->setHideWorkflow($request->get('EnableLifecycle') && $request->get('ShowHeaders') && $request->get('HideWorkflow') ? true : false);
		$doctype->setDisableDeleteInWorkflow($request->get('EnableLifecycle') && $request->get('ShowHeaders') && $request->get('DisableDeleteInWorkflow') ? true : false);
		$doctype->setCustomStartButtonLabel($request->get('EnableLifecycle') && $request->get('ShowHeaders') && trim($request->get('wfCustomStartButtonLabel')) ? $request->get('wfCustomStartButtonLabel') : null);
		$doctype->setCustomJSWFStart($request->get('EnableLifecycle') && $request->get('ShowHeaders') && trim($request->get('wfCustomJSWFStart')) ? trim($request->get('wfCustomJSWFStart')) : null);
		$doctype->setCustomJSWFComplete($request->get('EnableLifecycle') && $request->get('ShowHeaders') && trim($request->get('wfCustomJSWFComplete')) ? trim($request->get('wfCustomJSWFComplete')) : null);
		$doctype->setCustomJSWFApprove($request->get('EnableLifecycle') && $request->get('ShowHeaders') && trim($request->get('wfCustomJSWFApprove')) ? trim($request->get('wfCustomJSWFApprove')) : null);
		$doctype->setCustomJSWFDeny($request->get('EnableLifecycle') && $request->get('ShowHeaders') && trim($request->get('wfCustomJSWFDeny')) ? trim($request->get('wfCustomJSWFDeny')) : null);
		$doctype->setFilterAttachmentScript(trim($request->get('filterAttachments')) ? trim($request->get('filterAttachments')) : null);

		if ($request->get('EnableLifecycle') && $request->get('ShowHeaders'))
		{
			if ($request->get('WorkflowList')) {
				$wf_list = (is_array($request->get('WorkflowList')) ? $request->get('WorkflowList') : array($request->get('WorkflowList')));
				foreach ($wf_list as $id) {
					$workflow = $em->getRepository('DocovaBundle:Workflow')->find($id);
					if (!empty($workflow))
					{
						$doctype->addDocTypeWorkflow($workflow);
					}
				}
			}
			else {
				$workflows = $em->getRepository('DocovaBundle:Workflow')->findBy(array('Use_Default_Doc' => true));
				if (!empty($workflows)) {
					foreach ($workflows as $workflow) {
						$doctype->addDocTypeWorkflow($workflow);
					}
				}
			}
			
		}
		
		$doctype->setCustomReleaseButtonLabel(trim($request->get('wfCustomReleaseButtonLabel')) ? $request->get('wfCustomReleaseButtonLabel') : null);
		$buttons = $request->get('wfHideButtons') ? (is_array($request->get('wfHideButtons')) ? $request->get('wfHideButtons') : array($request->get('wfHideButtons'))) : array();
		$hide_buttons = implode(',', $buttons);
		$doctype->setHideButtons(trim($hide_buttons) ? $hide_buttons : null);
		$doctype->setCustomButtonsHideWhen(in_array('C', $buttons) && trim($request->get('wfCustomButtonsHideWhen')) ? $request->get('wfCustomButtonsHideWhen') : null);
		$doctype->setCustomReviewButtonLabel(trim($request->get('wfCustomReviewButtonLabel')) ? $request->get('wfCustomReviewButtonLabel') : null);
		
		// 4. Setting Advance settings tab
		$doctype->setCustomHtmlHead(trim($request->get('CustomHTMLHead')) ? $request->get('CustomHTMLHead') : null);
		$doctype->setCustomWqoAgent(trim($request->get('CustomWQOAgent')) ? $request->get('CustomWQOAgent') : null);
		$doctype->setCustomEditButtonLabel(trim($request->get('CustomEditButtonLabel')) ? $request->get('CustomEditButtonLabel') : null);
		$doctype->setEditDocumentCustomJS(trim($request->get('CustomEditJS')) ? $request->get('CustomEditJS') : null);
		$doctype->setCustomEditButtonHideWhen(trim($request->get('CustomEditButtonHideWhen')) ? $request->get('CustomEditButtonHideWhen') : null);
		$doctype->setCursorFocusField(trim($request->get('CursorFocusField')) ? $request->get('CursorFocusField') : null);
		$doctype->setOnLoadCustomJS(trim($request->get('CustomOnLoadJS')) ? $request->get('CustomOnLoadJS') : null);
		$doctype->setOnUnLoadCustomJS(trim($request->get('CustomOnUnLoadJS')) ? $request->get('CustomOnUnLoadJS') : null);
		$doctype->setCustomRestrictPrinting(trim($request->get('CustomRestrictPrinting')) ? $request->get('CustomRestrictPrinting') : null);
		$doctype->setPrintCustomJS(trim($request->get('CustomPrintJS')) ? $request->get('CustomPrintJS') : null);
		$doctype->setCustomSaveButtonLabel(trim($request->get('CustomSaveButtonLabel')) ? $request->get('CustomSaveButtonLabel') : null);
		$doctype->setSaveCloseCustomJS(trim($request->get('CustomSaveJS')) ? trim($request->get('CustomSaveJS')) : null);
		$doctype->setSaveButtonHideWhen(trim($request->get('CustomSaveButtonHideWhen')) ? $request->get('CustomSaveButtonHideWhen') : null);
		$doctype->setBeforeReleaseCustomJS(trim($request->get('CustomOnBeforeReleaseJS')) ? $request->get('CustomOnBeforeReleaseJS') : null);
		$doctype->setAfterReleaseCustomJS(trim($request->get('CustomOnAfterReleaseJS')) ? $request->get('CustomOnAfterReleaseJS') : null);
		$doctype->setValidateChoice((int)$request->get('ValidateChoice') ? (int)$request->get('ValidateChoice') : 0);
		if ($request->get('ValidateChoice') == 1 && trim($request->get('ValidateFieldNames'))) 
		{
			$fieldnames = str_replace(array("\r\n", "\r"), ";", trim($request->get('ValidateFieldNames')));
			$fieldtypes = str_replace(array("\r\n", "\r"), ";", trim($request->get('ValidateFieldTypes')));
			$fieldlabels = str_replace(array("\r\n", "\r"), ";", trim($request->get('ValidateFieldLabels')));
			$doctype->setValidateFieldNames($fieldnames);
			$doctype->setValidateFieldTypes($fieldtypes);
			$doctype->setValidateFieldLabels($fieldlabels);
			unset($fieldlabels, $fieldnames, $fieldtypes);
		}
		else {
			$doctype->setValidateFieldNames(null);
			$doctype->setValidateFieldTypes(null);
			$doctype->setValidateFieldLabels(null);
		}
		$doctype->setCustomValidateJs($request->get('ValidateChoice') == 2 && trim($request->get('CustomValidateJS')) ? $request->get('CustomValidateJS') : null);
		$doctype->setCustomWqsAgent(trim($request->get('CustomWQSAgent')) ? $request->get('CustomWQSAgent') : null);
		$doctype->setUpdateFullTextIndex($request->get('UpdateFtIndex') ? true : false);
		if (empty($document_type)) {
			$doctype->setDateCreated(new \DateTime());
			$doctype->setCreator($current_user);
			$em->persist($doctype);
		}
		else {
			$doctype->setDateModified(new \DateTime());
			$doctype->setModifier($current_user);
		}
		
		// 2. Setting content settings > saving subform sections
		if (in_array('CS1', $sections)) 
		{
			if (trim($request->get('cbCustomSubform1')) && (trim($request->get('cbCustomSubform1')) == trim($request->get('CustomSubform1')) || $request->get('cbCustomSubform1') == 'NIL')) 
			{
				$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => $request->get('CustomSubform1')));
				if (!empty($subform)) {
					$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
					$property_xml .= '<CS1>'.$subform->getFormFileName().'</CS1>';
					$property_xml .= '<DisplayInMore>'.($request->get('HideSubform1') ? '1' : '').'</DisplayInMore>';
					$property_xml .= '<SubFormTabName>'.($request->get('HideSubform1') && trim($request->get('SubForm1TabName')) ? trim($request->get('SubForm1TabName')) : '').'</SubFormTabName>';
					$property_xml .= '</Properties>';
					
					$doctype_subform = new DocTypeSubforms();
					$doctype_subform->setDocType($doctype);
					$doctype_subform->setSubform($subform);
					$doctype_subform->setSubformOrder(array_search('CS1', $sections) + 1);
					$doctype_subform->setPropertiesXML($property_xml);
					$em->persist($doctype_subform);
					unset($doctype_subform, $property_xml, $subform);
				}
			}
		}

		if (in_array('CS2', $sections))
		{
			if (trim($request->get('cbCustomSubform2')) && (trim($request->get('cbCustomSubform2')) == trim($request->get('CustomSubform2')) || $request->get('cbCustomSubform2') == 'NIL'))
			{
				$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => $request->get('CustomSubform2')));
				if (!empty($subform)) {
					$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
					$property_xml .= '<CS2>'.$subform->getFormFileName().'</CS2>';
					$property_xml .= '<DisplayInMore>'.($request->get('HideSubform2') ? '1' : '').'</DisplayInMore>';
					$property_xml .= '<SubFormTabName>'.($request->get('HideSubform2') && trim($request->get('SubForm2TabName')) ? trim($request->get('SubForm2TabName')) : '').'</SubFormTabName>';
					$property_xml .= '</Properties>';
						
					$doctype_subform = new DocTypeSubforms();
					$doctype_subform->setDocType($doctype);
					$doctype_subform->setSubform($subform);
					$doctype_subform->setSubformOrder(array_search('CS2', $sections) + 1);
					$doctype_subform->setPropertiesXML($property_xml);
					$em->persist($doctype_subform);
					unset($doctype_subform, $property_xml, $subform);
				}
			}
		}

		if (in_array('CS3', $sections))
		{
			if (trim($request->get('cbCustomSubform3')) && (trim($request->get('cbCustomSubform3')) == trim($request->get('CustomSubform3')) || $request->get('cbCustomSubform3') == 'NIL'))
			{
				$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => $request->get('CustomSubform3')));
				if (!empty($subform)) {
					$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
					$property_xml .= '<CS3>'.$subform->getFormFileName().'</CS3>';
					$property_xml .= '<DisplayInMore>'.($request->get('HideSubform3') ? '1' : '').'</DisplayInMore>';
					$property_xml .= '<SubFormTabName>'.($request->get('HideSubform3') && trim($request->get('SubForm3TabName')) ? trim($request->get('SubForm3TabName')) : '').'</SubFormTabName>';
					$property_xml .= '</Properties>';
		
					$doctype_subform = new DocTypeSubforms();
					$doctype_subform->setDocType($doctype);
					$doctype_subform->setSubform($subform);
					$doctype_subform->setSubformOrder(array_search('CS3', $sections) + 1);
					$doctype_subform->setPropertiesXML($property_xml);
					$em->persist($doctype_subform);
					unset($doctype_subform, $property_xml, $subform);
				}
			}
		}

		if (in_array('MEMO', $sections))
		{
			$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-HeaderMemo'));
			if (!empty($subform))
			{
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($doctype);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setSubformOrder(array_search('MEMO', $sections) + 1);
				$em->persist($doctype_subform);
				unset($subform, $doctype_subform);
			}
			$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-TextContentReadOnly'));
			if (!empty($subform))
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
				$property_xml .= '<Interface>';
				$property_xml .= '<EditorLabel>Text Content</EditorLabel>';
				$property_xml .= '<EditorHeightValue>150</EditorHeightValue>';
				$property_xml .= '<HideOnReading></HideOnReading>';
				$property_xml .= '<HideOnEditing>1</HideOnEditing>';
				$property_xml .= '</Interface>';
				$property_xml .= '</Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($doctype);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setPropertiesXML($property_xml);
				$doctype_subform->setSubformOrder(array_search('MEMO', $sections) + 1);
				$em->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
		}
		
		if (in_array('TXT', $sections) || in_array('RTXT', $sections) || in_array('DRTXT', $sections)) 
		{
			if (in_array('TXT', $sections)) {
				$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-TextEditor'));
			}
			elseif (in_array('RTXT', $sections)) {
				$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RTEditor'));
			}
			elseif (in_array('DRTXT', $sections)) {
				$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-DocovaEditor'));
			}
			
			if (!empty($subform)) 
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
				$property_xml .= '<Interface>';
				if (!in_array('DRTXT', $sections)) {
					$property_xml .= '<EditorReadOnly>'.($request->get('EditorReadOnly') ? '1' : '').'</EditorReadOnly>';
				}
				$property_xml .= '<EditorLabel>'.(trim($request->get('lblEditor')) ? trim($request->get('lblEditor')) : 'Text Content').'</EditorLabel>';
				$property_xml .= '<EditorHeightValue>'.((int)$request->get('EditorHeightValue') ? (int)$request->get('EditorHeightValue') : '150').'</EditorHeightValue>';
				$HideTextAreaOn = ($request->get('HideTextArea')) ? (is_array($request->get('HideTextArea')) ? $request->get('HideTextArea') : array($request->get('HideTextArea'))) : array();
				$property_xml .= '<HideOnReading>'.(in_array('R', $HideTextAreaOn) ? '1' : '').'</HideOnReading>';
				$property_xml .= '<HideOnEditing>'.(in_array('E', $HideTextAreaOn) ? '1' : '').'</HideOnEditing>';
				$property_xml .= '<HideOnCustom>'.(in_array('C', $HideTextAreaOn) ? '1' : '').'</HideOnCustom>';
				$property_xml .= '<CustomTextAreaHideWhen>'.(in_array('C', $HideTextAreaOn) && trim($request->get('CustomTextAreaHideWhen')) ? trim($request->get('CustomTextAreaHideWhen')) : '').'</CustomTextAreaHideWhen>';
				$property_xml .= '</Interface>';
				$property_xml .= '</Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($doctype);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setPropertiesXML($property_xml);
				$doctype_subform->setSubformOrder(in_array('TXT', $sections) ? (array_search('TXT', $sections) + 1) : (in_array('RTXT', $sections) ? (array_search('RTXT', $sections) + 1) : (array_search('DRTXT', $sections) + 1)));
				$em->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
		}
		
		if (in_array('ATT', $sections) || in_array('ATTI', $sections)) 
		{
			if (in_array('ATT', $sections))
			{
				$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Attachments'));
			}
			else {
				$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-AttachmentsIntl'));
			}
			if (!empty($subform)) 
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
				$property_xml .= '<MaxFiles>'.((int)$request->get('MaxFiles') ? (int)$request->get('MaxFiles') : '0').'</MaxFiles>';
				$property_xml .= '<TemplateType>'.($request->get('TemplateType') ? $request->get('TemplateType') : 'None').'</TemplateType>';
				$file_templates = ($request->get('TemplateList')) ? (is_array($request->get('TemplateList')) ? $request->get('TemplateList') : array($request->get('TemplateList'))) : array();
				$temp_keys = $temp_names = $temp_filenames = $temp_versions = '';
				foreach ($file_templates as $id)
				{
					$template = $em->find('DocovaBundle:FileTemplates', $id);
					if (!empty($template)) {
						$temp_keys .= $template->getId().',';
						$temp_names .= $template->getTemplateName().',';
						$temp_filenames .= $template->getFileName().',';
						$temp_versions .= ($template->getTemplateVersion() ? $template->getTemplateVersion() : '0').',';
					}
				}
				if (!empty($temp_keys)) 
				{
					$temp_keys = substr_replace($temp_keys, '', -1);
					$temp_names = substr_replace($temp_names, '', -1);
					$temp_filenames = substr_replace($temp_filenames, '', -1);
					$temp_versions = substr_replace($temp_versions, '', -1);
				}
				$property_xml .= "<TemplateKey>$temp_keys</TemplateKey>";
				$property_xml .= "<TemplateName>$temp_names</TemplateName>";
				$property_xml .= "<TemplateFileName>$temp_filenames</TemplateFileName>";
				$property_xml .= "<TemplateVersion>$temp_versions</TemplateVersion>";
				$property_xml .= '<TemplateAutoAttachment>'.($request->get('TemplateAutoAttach') ? '1' : '').'</TemplateAutoAttachment>';
				$property_xml .= '<Interface>';
					$property_xml .= '<AttachmentReadOnly>'.($request->get('AttachmentsReadOnly') ? '1' : '').'</AttachmentReadOnly>';
					$property_xml .= '<AttachmentLabel>'.(trim($request->get('lblAttachments')) ? trim($request->get('lblAttachments')) : 'Attachments').'</AttachmentLabel>';
					$property_xml .= '<AttachmentsHeight>'.((int)$request->get('AttachmentsHeightValue') ? (int)$request->get('AttachmentsHeightValue') : '100').'</AttachmentsHeight>';
					$property_xml .= '<GenerateThumbnails>'.($request->get('GenerateThumbnails') == 1 ? '1' : '').'</GenerateThumbnails>';
					$property_xml .= '<ThumbnailWidth>'.((int)$request->get('ThumbnailWidth') ? (int)$request->get('ThumbnailWidth') : '').'</ThumbnailWidth>';
					$property_xml .= '<ThumbnailHeight>'.((int)$request->get('ThumbnailHeight') ? (int)$request->get('ThumbnailHeight') : '').'</ThumbnailHeight>';
					$property_xml .= '<AllowedFileExtensions>'.(trim($request->get('AllowedFileExtensions')) ? trim($request->get('AllowedFileExtensions')) : '').'</AllowedFileExtensions>';
					$property_xml .= '<EnableFileViewLogging>'.($request->get('EnableFileViewLogging') ? '1' : '').'</EnableFileViewLogging>';
					$property_xml .= '<EnableFileDownloadLogging>'.($request->get('EnableFileDownloadLogging') ? '1' : '').'</EnableFileDownloadLogging>';
					$property_xml .= '<EnableLocalScan>'.($request->get('EnableLocalScan') ? '1' : '').'</EnableLocalScan>';
					$property_xml .= '<CustomScanJS>'.(trim($request->get('CustomScanJS')) ? trim($request->get('CustomScanJS')) : '').'</CustomScanJS>';
					$property_xml .= '<EnableFileCIAO>'.($request->get('EnableFileCIAO') ? '1' : '').'</EnableFileCIAO>';
					$property_xml .= '<ListType>'.($request->get('ListType') ? $request->get('ListType') : 'I').'</ListType>';
					$property_xml .= '<HideAttachButtons>'.($request->get('HideAttachButtons') ? '1' : '').'</HideAttachButtons>';
					$HideAttachmentsOn = ($request->get('HideAttachments')) ? (is_array($request->get('HideAttachments')) ? $request->get('HideAttachments') : array($request->get('HideAttachments'))) : array();
					$property_xml .= '<HideOnReading>'.(in_array('R', $HideAttachmentsOn) ? '1' : '').'</HideOnReading>';
					$property_xml .= '<HideOnEditing>'.(in_array('E', $HideAttachmentsOn) ? '1' : '').'</HideOnEditing>';
					$property_xml .= '<HideOnCustom>'.(in_array('C', $HideAttachmentsOn) ? '1' : '').'</HideOnCustom>';
					$property_xml .= '<CustomAttachmentsHideWhen>'.(in_array('C', $HideAttachmentsOn) && trim($request->get('CustomAttachmentsHideWhen')) ? trim($request->get('CustomAttachmentsHideWhen')) : '').'</CustomAttachmentsHideWhen>';
					$property_xml .= '<HasMultiAttachmentSection>'.($request->get('HasMultiAttachmentSections') ? '1' : '').'</HasMultiAttachmentSection>';
				$property_xml .= '</Interface>';
				$property_xml .= '</Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($doctype);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setSubformOrder(array_search('ATT', $sections) + 1);
				$doctype_subform->setPropertiesXML($property_xml);
				$em->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
		}
		
		if (in_array('WEB', $sections)) 
		{
			$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-WebBookmark'));
			if (!empty($subform)) 
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
				$property_xml .= '<Interface>';
					$property_xml .= '<BookmarkLabel>'.(trim($request->get('lblBookmark')) ? trim($request->get('lblBookmark')) : 'Web page bookmark').'</BookmarkLabel>';
					$property_xml .= '<PreviewHeightValue>'.((int)$request->get('PreviewHeightValue') ? (int)$request->get('PreviewHeightValue') : '100').'</PreviewHeightValue>';
					$HideBookmarkOn = ($request->get('HideWebBookmark')) ? (is_array($request->get('HideWebBookmark')) ? $request->get('HideWebBookmark') : array($request->get('HideWebBookmark'))) : array();
					$property_xml .= '<HideOnReading>'.(in_array('R', $HideBookmarkOn) ? '1' : '').'</HideOnReading>';
					$property_xml .= '<HideOnEditing>'.(in_array('E', $HideBookmarkOn) ? '1' : '').'</HideOnEditing>';
					$property_xml .= '<HideOnCustom>'.(in_array('C', $HideBookmarkOn) ? '1' : '').'</HideOnCustom>';
					$property_xml .= '<CustomWebBookmarkHideWhen>'.(in_array('C', $HideBookmarkOn) && trim($request->get('CustomWebBookmarkHideWhen')) ? trim($request->get('CustomWebBookmarkHideWhen')) : '').'</CustomWebBookmarkHideWhen>';
				$property_xml .= '</Interface>';
				$property_xml .= '</Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($doctype);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setSubformOrder(array_search('WEB', $sections) + 1);
				$doctype_subform->setPropertiesXML($property_xml);
				$em->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
		}
		
		if (in_array('RDOC', $sections)) 
		{
			$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RelatedDocuments'));
			if (!empty($subform)) 
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
				$property_xml .= '<RelatedDocOpenMode>'.((int)$request->get('RelatedDocOpenMode') ? (int)$request->get('RelatedDocOpenMode') : '1').'</RelatedDocOpenMode>';
				$user_select_types = $latest_types = $linked_types = $doc_type_keys = '';
				$tmp_doctypes = ($request->get('OMUserSelectDocTypeKey')) ? (is_array($request->get('OMUserSelectDocTypeKey')) ? $request->get('OMUserSelectDocTypeKey') : array($request->get('OMUserSelectDocTypeKey'))) : array();
				foreach ($tmp_doctypes as $id)
				{
					$type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $id, 'Trash' => false));
					if (!empty($type)) {
						$user_select_types .= "$id;";
					}
				}
				$user_select_types = (!empty($user_select_types)) ? substr_replace($user_select_types, '', -1) : '';
				$tmp_doctypes = ($request->get('OMLatestDocTypeKey')) ? (is_array($request->get('OMLatestDocTypeKey')) ? $request->get('OMLatestDocTypeKey') : array($request->get('OMLatestDocTypeKey'))) : array();
				foreach ($tmp_doctypes as $id)
				{
					$type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $id, 'Trash' => false));
					if (!empty($type)) {
						$latest_types .= "$id;";
					}
				}
				$latest_types = (!empty($latest_types)) ? substr_replace($latest_types, '', -1) : '';
				$tmp_doctypes = ($request->get('OMUserSelectDocTypeKey')) ? (is_array($request->get('OMUserSelectDocTypeKey')) ? $request->get('OMUserSelectDocTypeKey') : array($request->get('OMUserSelectDocTypeKey'))) : array();
				foreach ($tmp_doctypes as $id)
				{
					$type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $id, 'Trash' => false));
					if (!empty($type)) {
						$linked_types .= "$id;";
					}
				}
				$linked_types = (!empty($linked_types)) ? substr_replace($linked_types, '', -1) : '';
				unset($type, $tmp_doctypes, $id);
				$property_xml .= "<OMUserSelectDocTypeKey>$user_select_types</OMUserSelectDocTypeKey>";
				$property_xml .= "<OMLatestDocTypeKey>$latest_types</OMLatestDocTypeKey>";
				$property_xml .= "<OMLinkedDocTypeKey>$linked_types</OMLinkedDocTypeKey>";
				$property_xml .= '<EnableXLink>'.($request->get('EnableXLink') ? '1' : '').'</EnableXLink>';
				$property_xml .= '<LaunchLinkedFiles>'.($request->get('LaunchLinkedFiles') ? '1' : '').'</LaunchLinkedFiles>';
				$property_xml .= '<RelatedDocType>'.($request->get('RelatedDocType') ? $request->get('RelatedDocType') : 'N').'</RelatedDocType>';
				if ($request->get('RelatedDocType') == 'S' && $request->get('DocTypeKey')) 
				{
					$tmp_doctypes = (is_array($request->get('DocTypeKey')) ? $request->get('DocTypeKey') : array($request->get('DocTypeKey')));
					foreach ($tmp_doctypes as $id)
					{
						$type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $id, 'Trash' => false));
						if (!empty($type)) {
							$doc_type_keys .= "$id,";
						}
					}
				}
				$property_xml .= "<DocTypeKey>$doc_type_keys</DocTypeKey>";
				unset($type, $tmp_doctypes, $id, $doc_type_keys);
				$property_xml .= '<Interface>';
					$property_xml .= '<EnableLinkedFiles>'.($request->get('EnableLinkedFiles') ? '1' : '').'</EnableLinkedFiles>';
					$property_xml .= '<DisplayInMore>'.($request->get('HideRelatedDocs') ? '1' : '').'</DisplayInMore>';
					$HideRelDocOn = ($request->get('HideRelDocuments')) ? (is_array($request->get('HideRelDocuments')) ? $request->get('HideRelDocuments') : array($request->get('HideRelDocuments'))) : array();
					$property_xml .= '<HideOnReading>'.(in_array('R', $HideRelDocOn) ? '1' : '').'</HideOnReading>';
					$property_xml .= '<HideOnEditing>'.(in_array('E', $HideRelDocOn) ? '1' : '').'</HideOnEditing>';
					$property_xml .= '<HideOnCustom>'.(in_array('C', $HideRelDocOn) ? '1' : '').'</HideOnCustom>';
					$property_xml .= '<CustomRelDocumentsHideWhen>'.(in_array('C', $HideRelDocOn) && trim($request->get('CustomRelDocumentsHideWhen')) ? trim($request->get('CustomRelDocumentsHideWhen')) : '').'</CustomRelDocumentsHideWhen>';
				$property_xml .= '</Interface>';
				$property_xml .= '</Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($doctype);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setSubformOrder(array_search('RDOC', $sections) + 1);
				$doctype_subform->setPropertiesXML($property_xml);
				$em->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
		}
		
		if (in_array('REMAIL', $sections)) 
		{
			$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RelatedEmails'));
			if (!empty($subform)) 
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
				$property_xml .= '<IncludeEmailLinks>'.($request->get('IncludeEmailLinks') ? '1' : '').'</IncludeEmailLinks>';
				$property_xml .= '<ForwardSaveAs>'.($request->get('ForwardSaveAs') ? '1' : '').'</ForwardSaveAs>';
				$property_xml .= '<Interface>';
				$HideRelEmLiOn = ($request->get('HideRelEmails')) ? (is_array($request->get('HideRelEmails')) ? $request->get('HideRelEmails') : array($request->get('HideRelEmails'))) : array();
				$property_xml .= '<HideOnReading>'.(in_array('R', $HideRelEmLiOn) ? '1' : '').'</HideOnReading>';
				$property_xml .= '<HideOnEditing>'.(in_array('E', $HideRelEmLiOn) ? '1' : '').'</HideOnEditing>';
				$property_xml .= '<HideOnCustom>'.(in_array('C', $HideRelEmLiOn) ? '1' : '').'</HideOnCustom>';
				$property_xml .= '<CustomRelEmailsHideWhen>'.(in_array('C', $HideRelEmLiOn) && trim($request->get('CustomRelEmailsHideWhen')) ? trim($request->get('CustomRelEmailsHideWhen')) : '').'</CustomRelEmailsHideWhen>';
				$property_xml .= '<CustomRelLinksHideWhen>'.(in_array('C', $HideRelEmLiOn) && trim($request->get('CustomRelLinksHideWhen')) ? trim($request->get('CustomRelLinksHideWhen')) : '').'</CustomRelLinksHideWhen>';
				$property_xml .= '</Interface>';
				$property_xml .= '</Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($doctype);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setSubformOrder(array_search('REMAIL', $sections) + 1);
				$doctype_subform->setPropertiesXML($property_xml);
				$em->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
		}

		if (in_array('RLINK', $sections))
		{
			$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RelatedLinks'));
			if (!empty($subform))
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
				$property_xml .= '<IncludeRelLinks>'.($request->get('IncludeRelLinks') ? '1' : '').'</IncludeRelLinks>';
				$property_xml .= '<Interface>';
				$HideRelEmLiOn = ($request->get('HideRelLinks')) ? (is_array($request->get('HideRelLinks')) ? $request->get('HideRelLinks') : array($request->get('HideRelLinks'))) : array();
				$property_xml .= '<HideOnReading>'.(in_array('R', $HideRelEmLiOn) ? '1' : '').'</HideOnReading>';
				$property_xml .= '<HideOnEditing>'.(in_array('E', $HideRelEmLiOn) ? '1' : '').'</HideOnEditing>';
				$property_xml .= '<HideOnCustom>'.(in_array('C', $HideRelEmLiOn) ? '1' : '').'</HideOnCustom>';
				$property_xml .= '<CustomRelLinksHideWhen>'.(in_array('C', $HideRelEmLiOn) && trim($request->get('CustomRelLinksHideWhen')) ? trim($request->get('CustomRelLinksHideWhen')) : '').'</CustomRelLinksHideWhen>';
				$property_xml .= '</Interface>';
				$property_xml .= '</Properties>';
		
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($doctype);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setSubformOrder(array_search('RLINK', $sections) + 1);
				$doctype_subform->setPropertiesXML($property_xml);
				$em->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
		}

		if (in_array('COMM', $sections)) 
		{
			$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-AdvancedComments'));
			if (!empty($subform)) 
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties>';
				$property_xml .= '<LinkComments>'.($request->get('LinkComments') ? '1' : '').'</LinkComments>';
				$property_xml .= '<Interface>';
					$property_xml .= '<NotifyParticipants>'.($request->get('NotifyParticipants') ? '1' : '').'</NotifyParticipants>';
					$property_xml .= '<NotifyAuthor>'.($request->get('NotifyAuthor') ? '1' : '').'</NotifyAuthor>';
					$property_xml .= '<NotificationTrigger>'.((int)$request->get('NotificationTrigger') ? (int)$request->get('NotificationTrigger') : '').'</NotificationTrigger>';
					$property_xml .= '<CommentsLabel>'.(trim($request->get('lblComments')) ? trim($request->get('lblComments')) : 'Comments').'</CommentsLabel>';
					$HideCommentsOn = ($request->get('HideComments')) ? (is_array($request->get('HideComments')) ? $request->get('HideComments') : array($request->get('HideComments'))) : array();
					$property_xml .= '<HideOnReading>'.(in_array('R', $HideCommentsOn) ? '1' : '').'</HideOnReading>';
					$property_xml .= '<HideOnEditing>'.(in_array('E', $HideCommentsOn) ? '1' : '').'</HideOnEditing>';
					$property_xml .= '<HideOnCustom>'.(in_array('C', $HideCommentsOn) ? '1' : '').'</HideOnCustom>';
					$property_xml .= '<CustomCommentsHideWhen>'.(in_array('C', $HideCommentsOn) && trim($request->get('CustomCommentsHideWhen')) ? trim($request->get('CustomCommentsHideWhen')) : '').'</CustomCommentsHideWhen>';
				$property_xml .= '</Interface>';
				$property_xml .= '</Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($doctype);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setSubformOrder(array_search('COMM', $sections) + 1);
				$doctype_subform->setPropertiesXML($property_xml);
				$em->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
		}
		
		$em->flush();
		return $doctype;
	}
	
	public function getUserSubscriptionAction(Request $request)
	{
		$library_id = $request->query->get('RestrictToCategory');
		if (empty($library_id))
		{
			throw $this->createNotFoundException('Library ID is missed.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library_id, 'Trash' => false));
		if (empty($library))
		{
			throw $this->createNotFoundException('Unspecified source Library ID = '.$library_id);
		}
		
		$response = new Response();
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$customACL = new CustomACL($this->container);
		
		$root = $result_xml->appendChild($result_xml->createElement('documents'));
		$subscribers = $customACL->getObjectACEUsers($library, 'delete');
		if ($subscribers->count() > 0)
		{
			foreach ($subscribers as $item)
			{
				if ($user_obj = $this->findUserAndCreate($item->getUsername(), false, true)) {
					$child = $result_xml->createElement('Document');
					$newnode = $result_xml->createElement('LibKey', $library->getId());
					$child->appendChild($newnode);
					
					$newnode = $result_xml->createElement('SubscriberName', $user_obj->getUserNameDnAbbreviated());
					$child->appendChild($newnode);
					
					$root->appendChild($child);
				}
			}
		}
		else {
			$newnode = $result_xml->createElement('h2', 'No documents found');
			$root->appendChild($newnode);
		}
		unset($customACL);
		
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($result_xml->saveXML());
		return $response;
	}
	
	public function adminLeftNavAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Admin:adminLeftNav.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings
			));
	}
	
	public function wAdminContentAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$view_name	= (trim($request->query->get('viewname'))) ? $request->query->get('viewname') : 'wAdminLibraries';
		$view_title	= (trim($request->query->get('viewtitle'))) ? $request->query->get('viewtitle') : 'Libraries';

		$perspective = $em->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => $view_name, 'Trash' => false, 'Is_System' => true));
		if (empty($perspective))
		{
			throw $this->createNotFoundException('Unsepecified system perspective for '.$view_name);
		}
		
		$tbl_perspective = array();
		$perspective_xml = new \DOMDocument();
		$perspective_xml->loadXML($perspective->getXmlStructure());
		foreach ($perspective_xml->getElementsByTagName('column') as $column) 
		{
			$tbl_perspective[] = array(
				'title' => $column->getElementsByTagName('title')->item(0)->nodeValue,
				'dataSet' => $column->getElementsByTagName('xmlNodeName')->item(0)->nodeValue,
				'dataType' => $column->getElementsByTagName('dataType')->item(0)->nodeValue,
				'hasCustomSort' =>  $column->getElementsByTagName('hasCustomSort')->item(0)->nodeValue,
				'sortOrder' => $column->getElementsByTagName('customSortOrder')->item(0)->nodeValue,
				'width' => $column->getElementsByTagName('width')->item(0)->nodeValue,
				'align' => $column->getElementsByTagName('align')->item(0)->nodeValue,
				'fontSize' => $column->getElementsByTagName('fontSize')->item(0)->nodeValue,
				'fontFamily' => $column->getElementsByTagName('fontFamily')->item(0)->nodeValue,
				'fontWeight' => $column->getElementsByTagName('fontWeight')->item(0)->nodeValue,
				'color' => $column->getElementsByTagName('color')->item(0)->nodeValue,
				'backgroundColor' => $column->getElementsByTagName('backgroundColor')->item(0)->nodeValue,
				'isCategorized' => $column->getElementsByTagName('isCategorized')->item(0)->nodeValue
			);
		}
		unset($perspective_xml, $perspective);
		
		return $this->render('DocovaBundle:Admin:wAdminContent.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings,
				'table_perspective' => $tbl_perspective,
				'view_name' => $view_name,
				'view_title' => $view_title
		));
	}
	
	public function wAdminBlankContentAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Admin:wAdminBlankContent.html.twig', array(
				'user' => $this->user
		));
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
		//var_dump("AdminController::findUserAndCreate");
		//var_dump("user name => ".$username);
		return $utilHelper->findUserAndCreate($username, $create, $em, $search_userid, $search_ad);
	}

	/**
	 * Handle saving group and members
	 * 
	 * @param Request $request
	 * @param \Docova\DocovaBundle\Entity\UserRoles $group
	 * @param boolean $isNew [optional(false)]
	 * @return boolean
	 */
	private function saveUserRole($request, $group, $isNew = false)
	{
		if (!trim($request->get('GroupName'))) 
		{
			return false;
		}

		$em = $this->getDoctrine()->getManager();
		$group->setGroupName($request->get('GroupName'));
		$group->setDisplayName(trim($request->get('DisplaName')) ? $request->get('DisplaName') : $request->get('GroupName'));
		if ($isNew === true) 
		{
			$group->setGroupType(trim($request->get('GroupType')) ? true : false);
			if (trim($request->get('AddedUser'))) 
			{
			    $users = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', trim($request->get('AddedUser')));
				$len = count($users);
				for ($x = 0; $x < $len; $x++) {
					if (false !== $user = $this->findUserAndCreate($users[$x], false)) {
						$group->addRoleUsers($user);
					}
					else {
						$groupname = str_replace('/DOCOVA', '', $users[$x]);
						$inner_group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $groupname, 'groupType' => false));
						if (!empty($inner_group) && $inner_group->getRoleUsers()->count() > 0) 
						{
							foreach ($inner_group->getRoleUsers() as $user) {
								$group->addRoleUsers($user);
							}
							$group->setNestedGroups(array($users[$x]));
						}
					}
				}
			}

			$group->setRole('ROLE_'.strtoupper(trim($request->get('GroupName'))));
			$em->persist($group);
		}
		
		$em->flush();
		return true;
	}
	

	// To Add file Resource
	public function fileResourceAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
	
			
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
	
			try {
				$file_resource = new FileResources();
				$file_resource->setName($req->get('Name'));
				$file_resource->setType($req->get('Type'));
				// get file attachment
				//-------------------------------------------------------------------
				$fileAttachments=$request->files;
				$file=$fileAttachments->get('fileAttachment');
	
				//var_dump($file);
				if ($file){
					//var_dump("inside file");
	
					//exit();
					if (!is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Resources'))
					{
						@mkdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Resources');
					}
	
					$res = $file->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Resources', md5(basename($file->getClientOriginalName())));
					$file_resource->setFileName($file->getClientOriginalName());
					$file_resource->setFileMimeType($res->getMimeType());
					$file_resource->setFileSize($file->getClientSize());
	
				}
				$file_resource->setDateCreated(new \DateTime()); // back space to ignore symfony date time
				$file_resource->setVersion($req->get('Version'));
	
				$em->persist($file_resource); // create entity record
				$em->flush(); // commit entity record
	
				//. points to contenate
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$file_resource->getId().'&loadaction=reloadview&closeframe=true');
			}
			catch (\Exception $e)
			{
				//var_dump( $e->getMessage());
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
			}
		}
		return $this->render('DocovaBundle:Admin:editwAdminFileResourcesDocument.html.twig', array(
				'user' => $this->user,
				'view_name' => 'wAdminFileResources',
				'settings' => $this->global_settings,
				'view_title' => 'File Resource',
				'mode' => "edit"
		));
	}
	// To Add a Data Source Action
	public function dataSourceAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
	
			
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
	
			try {
				$data_source = new DataSources();
				$data_source->setName($req->get('Name'));
				$data_source->setConnectionName($request->get('Connection_Name'));
	
				$data_source->setType($req->get('Type'));
					
				$csvResource = $req->get('File_Resource_Name_CSV');
				$sqlResource = $req->get('File_Resource_Name_SQL');
				if (!empty($csvResource) && $csvResource!=''){
					$data_source->setFileResourceName($csvResource);
				}
				else if(!empty($sqlResource) && $sqlResource!=''){
					$data_source->setFileResourceName($sqlResource);
				}
				$data_source->setSubjectColumn($req->get('Subject_Column'));
	
	
				//CSV Fields
				$data_source->setCSVDelimiter($req->get('CSV_Delimiter'));
				$data_source->setCSVOffsetLines($req->get('CSV_Offset_Lines'));
				$data_source->setCSVOffsetChars($req->get('CSV_Offset_Chars'));
	
				//SQL Fields
				$data_source->setSQLType($req->get('SQL_Type'));
				$data_source->setSQL($req->get('SQL'));
				$data_source->setSQLTableName($req->get('SQL_Table_Name'));
				$data_source->setSQLOrderBy($req->get('SQL_Order_By'));
				$data_source->setSQLKeyName($req->get('SQL_Key_Name'));
				
				//Docova Documents Only Check
				$docovaDocumentsOnly = $req->get('Docova_Documents_Only');
				if (!empty($docovaDocumentsOnly) && $docovaDocumentsOnly=="1"){
					$data_source->setDocovaDocumentsOnly(true);
				}
				else{
					$data_source->setDocovaDocumentsOnly(false);
				}
	
				$data_source->setDateCreated(new \DateTime()); // back space to ignore symfony date time
					
				$em->persist($data_source); // create entity record
				$em->flush(); // commit entity record
	
				//. points to contenate
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$data_source->getId().'&loadaction=reloadview&closeframe=true');
			}
			catch (\Exception $e)
			{
				//var_dump( $e->getMessage());
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
				echo $message;
			}
		}
		return $this->render('DocovaBundle:Admin:editwAdminDataSourcesDocument.html.twig', array(
				'user' => $this->user,
				'view_name' => 'wAdminDataSources',
				'view_title' => 'Data Source',
				'settings' => $this->global_settings,
				'dataSources' => $this->getDoctrine()->getManager()->getRepository("DocovaBundle:DataSources"),
				'mode' => "edit"
		));
	}

	//to test the script parser
	public function scriptParserAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		return $this->render('DocovaBundle:Admin:scriptParser.html.twig', array(
			'user' => $this->user,
		));
	}


	// To Add a Data View Action
	public function dataViewAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
	
			
		if (true === $request->isMethod('POST'))
		{
			$req = $request->request;
	
			try {
				$data_view = new DataViews();
				$data_view->setName($req->get('Name'));
				$data_view->setFolderLink($req->get('Folder_Link'));
				$linkLen = \strlen($req->get('Folder_Link'));
				$data_view->setFolderId(\substr($req->get('Folder_Link'),$linkLen-36));
				$data_view->setDataSourceName($req->get('Data_Source_Name'));
				$data_view->setDateCreated(new \DateTime()); // back space to ignore symfony date time
				$data_view->setSQLFilter($req->get('SQL_Filter'));
				$em->persist($data_view); // create entity record
				$em->flush(); // commit entity record
	
				//. points to contenate
				return $this->redirect($this->generateUrl('docova_blankcontent').'?OpenPage&docid='.$data_view->getId().'&loadaction=reloadview&closeframe=true');
			}
			catch (\Exception $e)
			{
				//var_dump( $e->getMessage());
				$message = 'Could not save data because of the following error <br />'. $e->getMessage();
				echo $message;
			}
		}
		return $this->render('DocovaBundle:Admin:editwAdminDataViewsDocument.html.twig', array(
				'user' => $this->user,
				'view_name' => 'wAdminDataViews',
				'view_title' => 'Data View',
				'settings' => $this->global_settings,
				'dataViews' => $this->getDoctrine()->getRepository("DocovaBundle:DataViews"),
				'mode' => "edit"
		));
	}
	public function getFileResourcesByTypeAction(Request $request)
	{
		$type = $request->query->get('RestrictToCategory');
		if (empty($type))
		{
			throw $this->createNotFoundException('Category type is missed.');
		}
	
		$results = new \DOMDocument('1.0', 'UTF-8');
		$root = $results->appendChild($results->createElement('viewentries'));
		$em = $this->getDoctrine()->getManager();
		if ($type === 'All') {
			$templates = $em->getRepository('DocovaBundle:FileResources')->findBy(array(), array('Name' => 'ASC'));
		}
		else {
			$templates = $em->getRepository('DocovaBundle:FileResources')->findBy(array('Type' => $type), array('Name' => 'ASC'));
		}
	
		if (!empty($templates))
		{
			$count = 0;
			foreach ($templates as $ft) {
				$ft instanceof \Docova\DocovaBundle\Entity\FileResources;
				$node = $results->createElement('viewentry');
				$newnode = $results->createElement('entrydata');
				$textnode = $results->createElement('text', $ft->getName());
				$newnode->appendChild($textnode);
				$attrib = $results->createAttribute('name');
				$attrib->value = 'Name';
				$newnode->appendChild($attrib);
				$attrib = $results->createAttribute('columnnumber');
				$attrib->value = 0;
				$newnode->appendChild($attrib);
				$node->appendChild($newnode);
				$newnode = $results->createElement('entrydata');
				$textnode = $results->createElement('text', $ft->getId());
				$newnode->appendChild($textnode);
				$attrib = $results->createAttribute('name');
				$attrib->value = 'DocKey';
				$newnode->appendChild($attrib);
				$attrib = $results->createAttribute('columnnumber');
				$attrib->value = 1;
				$newnode->appendChild($attrib);
				$node->appendChild($newnode);
				$newnode = $results->createElement('entrydata');
				$textnode = $results->createElement('text', $count);
				$newnode->appendChild($textnode);
				$attrib = $results->createAttribute('name');
				$attrib->value = '$10';
				$newnode->appendChild($attrib);
				$attrib = $results->createAttribute('columnnumber');
				$attrib->value = 2;
				$newnode->appendChild($attrib);
				$node->appendChild($newnode);
				$count++;
				$root->appendChild($node);
			}
		}
	
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($results->saveXML());
		return $response;
	}
	/**
	 * open file resource
	 * @param: file path
	 * @return: file output stream
	 */
	public function openAdminFileResourceAction($file_name)
	{
		if (empty($file_name))
		{
			throw $this->createNotFoundException('Unspecified source File Resource.');
		}
	
		$file_info = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:FileResources')->findOneBy(array('File_Name'=>$file_name));
		if (empty($file_info))
		{
			throw $this->createNotFoundException('Unspecified source File Resource with ID = '. $file_name);
		}
	
		$file_path_header = $this->generatePathHeader($file_info, true,"Resources");
	
		if (empty($file_path_header)) {
			throw $this->createNotFoundException('Could not download or find the selected file.');
		}
	
		$response = new Response(file_get_contents($file_path_header['file_path']), 200);
		$response->headers->add($file_path_header['headers']);
		return $response;
		//return  new Response ( $file_info->getFileMimeType(), 200 );
	}
	
	public function openCreateMemberDlgAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Admin:dlgCreateMember.html.twig', array(
			'user' => $this->user
		));
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

	//--------------- to save file resource data -------------------
	private function saveFileResourceData($em,$file_resource, $doc_id, $request)
	{
		// check if there is any data
		if (empty($file_resource))
		{
			throw $this->createNotFoundException('Unspecified source File resource ID = '. $doc_id);
		}
		try {
	
			############ to delete (for content assist )#################
			$file_resource instanceof \Docova\DocovaBundle\Entity\FileResources;
			######################## to delete #################
			$file_resource->setName($request->get('Name'));
			$file_resource->setType($request->get('Type'));
			// get file attachment
			//-------------------------------------------------------------------
			$fileAttachments=$this->get('request_stack')->getCurrentRequest()->files;
			$file=$fileAttachments->get('fileAttachment');
			// check if update is required for file
			if($file){
					
				// first delete the file
				$filePath=$this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.md5($file_resource->getFileName());
				@unlink($filePath);
					
				//var_dump($file);
				//exit();
				if (!is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Resources')) {
					@mkdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Resources');
				}
				$res = $file->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Resources', md5(basename($file->getClientOriginalName())));
				//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), '0777');
				//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), '0666');
				$file_resource->setFileName($file->getClientOriginalName());
				$file_resource->setFileMimeType($res->getMimeType());
				$file_resource->setFileSize($file->getClientSize());
			}
	
			//-------------------------------------------------------------------
	
	
			$file_resource->setDateCreated(new \DateTime()); // back space to ignore symfony date time
			$file_resource->setVersion($request->get('Version'));
	
			$em->persist($file_resource); // create entity record
			$em->flush(); // commit entity record
		}
		catch (\Exception $e)
		{
			throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
			return false;
		}
	}
	//--------------- to save data source data -------------------
	private function saveDataSourceData($em,$data_source, $doc_id, $request)
	{
		// check if there is any data
		if (empty($data_source))
		{
			throw $this->createNotFoundException('Unspecified source Data Source ID = '. $doc_id);
		}
		try {
	
			############ to delete (for content assist )#################
			$data_source instanceof \Docova\DocovaBundle\Entity\DataSources;
			######################## to delete #################
			$data_source->setName($request->get('Name'));
			$data_source->setConnectionName($request->get('Connection_Name'));
			$data_source->setType($request->get('Type'));
			$csvResource = $request->get('File_Resource_Name_CSV');
			$sqlResource = $request->get('File_Resource_Name_SQL');
			if (!empty($csvResource) && $csvResource!=''){
				$data_source->setFileResourceName($csvResource);
			}
			if(!empty($sqlResource) && $sqlResource!=''){
				$data_source->setFileResourceName($sqlResource);
			}
			$data_source->setSubjectColumn($request->get('Subject_Column'));
				
				
			//CSV Fields
			$data_source->setCSVDelimiter($request->get('CSV_Delimiter'));
			$data_source->setCSVOffsetLines($request->get('CSV_Offset_Lines'));
			$data_source->setCSVOffsetChars($request->get('CSV_Offset_Chars'));
				
			//SQL Fields
			$data_source->setSQLType($request->get('SQL_Type'));
			$data_source->setSQL($request->get('SQL'));
			$data_source->setSQLTableName($request->get('SQL_Table_Name'));
			$data_source->setSQLOrderBy($request->get('SQL_Order_By'));
			$data_source->setSQLKeyName($request->get('SQL_Key_Name'));
	
			//Docova Documents Only Check
			$docovaDocumentsOnly = $request->get('Docova_Documents_Only');
			if (!empty($docovaDocumentsOnly) && $docovaDocumentsOnly=="1"){
				$data_source->setDocovaDocumentsOnly(true);
			}
			else{
				$data_source->setDocovaDocumentsOnly(false);
			}
			
			$data_source->setDateCreated(new \DateTime()); // back space to ignore symfony date time
				
			$em->persist($data_source); // create entity record
			$em->flush(); // commit entity record
		}
		catch (\Exception $e)
		{
			throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
			return false;
		}
	}
	//--------------- to save data view data -------------------
	private function saveDataViewData($em,$data_view, $doc_id, $request)
	{
		// check if there is any data
		if (empty($data_view))
		{
			throw $this->createNotFoundException('Unspecified source Data View ID = '. $doc_id);
		}
		try {
	
			############ to delete (for content assist )#################
			$data_view instanceof \Docova\DocovaBundle\Entity\DataViews;
			######################## to delete #################
			$data_view->setName($request->get('Name'));
			$data_view->setFolderLink($request->get('Folder_Link'));
			$linkLen = \strlen($request->get('Folder_Link'));
			$data_view->setFolderId(\substr($request->get('Folder_Link'),$linkLen-36));
			$data_view->setDataSourceName($request->get('Data_Source_Name'));
			$data_view->setDateCreated(new \DateTime()); // back space to ignore symfony date time
			$data_view->setSQLFilter($request->get('SQL_Filter'));
			$em->persist($data_view); // create entity record
			$em->flush(); // commit entity record
		}
		catch (\Exception $e)
		{
			throw $this->createNotFoundException('Could not save data because of the following error <br />'. $e->getMessage());
			return false;
		}
	}
	
	/**
	 * Parse and decode string
	 * 
	 * @param string $string
	 * @return boolean|string
	 */
	private function decodeSegment($string)
	{
		$output = '';
		$keys = array("0" => "G", "1" => "T", "2" => "S", "3" => "H", "4" => "K", "5" => "J", "6" => "Q", "7" => "W", "8" => "Z", "9" => "Y", "10" => "B", "11" => "R", "12" => "N", "13" => "D", "14" => "C", "." => "M", "-" => "0", "A" => "9", "B" => "8", "C" => "7", "D" => "6", "E" => "5", "F" => "4", "G" => "3", "H" => "2", "I" => "1");
		$strArray = str_split($string);
		for ($x = 0; $x < count($strArray); $x++)
		{
			if (false !== ($val = array_search($strArray[$x], $keys)))
			{
				if (is_numeric($val)) {
					$output .= ((int)$val - $x - 1);
				}
				else {
					$output .= $val;
				}
			}
			else {
				return false;
			}
		}
		return $output;
	}
	
	/**
	 * Create/Copy content of the template library into the new library (folders and documents)
	 * 
	 * @param \Docova\DocovaBundle\Entity\Libraries $library
	 */
	private function copyTemplateLibraryContent($library)
	{
		$em = $this->getDoctrine()->getManager();
		$rootFolders = $em->getRepository('DocovaBundle:Folders')->findBy(array('Library' => $library->getSourceTemplate(), 'Del' => false, 'parentfolder' => null));
		if (!empty($rootFolders)) 
		{
			$pasted_folders = array();
			$conn = $this->getDoctrine()->getConnection();
			foreach ($rootFolders as $folder)
			{
				$children = $em->getRepository('DocovaBundle:Folders')->getDescendants($folder->getPosition(), $library->getSourceTemplate(), $folder->getId(), true);
				$conn->beginTransaction();
				try {
					foreach ($children as $f)
					{
						if ($f['id'] == $folder->getId()) {
							$new_folder = $this->get('docova.libcontroller')->createCopy($conn, $folder->getId(), $library->getId(), null, false, $this->user, $this->UPLOAD_FILE_PATH);
							$pasted_folders[] = array(
								'source_id' => $folder->getId(),
								'new_id' => $new_folder
							);
						}
						elseif (false != $parent_folder = $this->searchInPastedFolders($pasted_folders, $f['Parent']))
						{
							$new_folder = $this->get('docova.libcontroller')->createCopy($conn, $f['id'], $library->getId(), $parent_folder, false, $this->user, $this->UPLOAD_FILE_PATH);
							$pasted_folders[] = array(
								'source_id' => $f['id'],
								'new_id' => $new_folder
							);
						}
					}
					$conn->commit();
				}
				catch (\Exception $e) {
					$conn->rollback();
				}
			}
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
	 * Get list of time zones
	 * 
	 * @return string[]
	 */
	function getTimeZoneList()
	{
	    $timezonelist = [];
	    
	    $timezoneIds = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);	    
	    foreach ($timezoneIds as $id) {
	        $timezonelist[] = $id;
	    }
	    
	    return $timezonelist;
	}
}
