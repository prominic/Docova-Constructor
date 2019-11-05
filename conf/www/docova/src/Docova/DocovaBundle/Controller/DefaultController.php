<?php

namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
//use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserPanels;
use Docova\DocovaBundle\Entity\PanelWidgets;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Extensions\ExternalViews;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultController extends Controller
{
	protected $user;
	protected $global_settings;
	protected $app_path;
	
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
		
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->app_path = $this->get('kernel')->getRootDir() . '/../src/Docova/DocovaBundle/Resources/views/DesignElements/';
		$this->global_settings = $this->global_settings[0];
	}
	
	public function indexAction(Request $request)
	{
		$this->initialize();
		$session = $request->getSession();
		if (!$session->has('currentUser') || trim($session->get('currentUser')) == '') {
			$session->set('currentUser', base64_encode($this->user->getId()));
		}
		if ($this->container->get('session')->has('_security.docova.target_path')) 
		{
			try {
				$url = $this->container->get('session')->get('_security.docova.target_path');
				$params = $this->get('router')->match(strstr(strstr($url, '/Docova/'), '?', true));
				if (true === in_array($params['_route'], array('docova_homeframe', 'docova_mainpage', 'docova_readdocument', 'docova_readappdocument', 'docova_editdocument', 'docova_openform', 'docova_opendocfile'))) 
				{
					$this->container->get('session')->remove('_security.docova.target_path');
					return $this->redirect($url);
				}
			}
			catch (\Exception $e) {
				
			}
		}

		return $this->render('DocovaBundle:Default:index.html.twig', array(
				//'tab_name' => 'home',
				'user' => $this->user,
				'settings' => $this->global_settings
		));
	}
	
	public function docovaLicenseInstaructionsAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$gb = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$gb = $gb[0];
		$em = null;
		$request->getSession()->invalidate();
		return $this->render('DocovaBundle:Default:DocovaLicenseInstaructions.html.twig', array(
			'settings' => $gb
		));
	}
	
	public function openMainPageAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:HomeFrame.html.twig', array(
			'settings' => $this->global_settings,
			'user' => $this->user
		));
	}
	
	public function homepageAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$html_resource=$em->getRepository('DocovaBundle:HtmlResources')->findOneBy(array('resourceName' => 'HomePage'));

		return $this->render('DocovaBundle:Default:homepage.html.twig', array(
			'user' => $this->user,
			'html_resource' => $html_resource,
			'settings' => $this->global_settings
		));
	}

	public function homeTopAction(){

		$this->initialize();
		$incompleteEdits = '';
		$em = $this->getDoctrine()->getManager();
		$workspace = $em->getRepository('DocovaBundle:UserWorkspace')->findOneBy(array('user' => $this->user->getId()));
		$workspace = empty($workspace) ? null : $workspace;
		$incompleted_items = $em->getRepository('DocovaBundle:TempEditAttachment')->findBy(array('trackUser' => $this->user->getId()));
		if (!empty($incompleted_items)) 
		{
			foreach ($incompleted_items as $item) {
				$incompleteEdits .= ($item->getDocument() . '; ');
			}
			$incompleteEdits = substr_replace($incompleteEdits, '', -2);
		}
		unset($incompleted_items, $item);
		$libraries = $em->getRepository('DocovaBundle:Libraries')->createQueryBuilder('L')
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
		$customLogo = null;
		$logoobjs = $em->getRepository('DocovaBundle:HtmlResources')->findBy(array('resourceName' => 'ApplicationLogo'));
		if(!empty($logoobjs)){
		    $customLogo = $logoobjs[0]->getHtmlCode();
		    if(empty($customLogo)){
		        $logofname = $logoobjs[0]->getFileName();
		        if(!empty($logofname)){
    	            $customLogo = '<img src="./HTMLResource/'.$logofname.'" alt="" title="powered by DOCOVA" style="max-height:100%; max-width:100%;" >';
		        }
		    }
		}
		unset($logoobjs);
		
		return $this->render('DocovaBundle:Default:HomeTop.html.twig',  array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'incompleteEdits' => $incompleteEdits,
		    'customLogo' => $customLogo,
			'workspace' => $workspace,
			'isLibraryAdmin' => $isLibraryAdmin
		)); 
	}
	
	public function dashboardAction()
	{
		return $this->render('DocovaBundle:Default:wDocovaDashboard.html.twig');
	}
	
	public function libraryPropertiesAction(Request $request)
	{
		$this->initialize();
		$lib_id	= $request->query->get('ParentUNID');
		$lib_obj =  $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $lib_id, 'Trash' => false));
		if (empty($lib_obj) && $lib_id != -1) {
			throw $this->createNotFoundException('No Library found for ID = '. $lib_id);
		}
		
		return $this->render('DocovaBundle:Default:dlgLibraryProperties.html.twig',  array(
			'user' => $this->user,
			'library' => $lib_obj
		));
	}
	
	public function dashboardServicesAction(Request $request)
	{
		$this->initialize();
		$response = new Response();
		$request_xml = new \DOMDocument();
		$request_xml->loadXML($request->getContent());
		$action = $request_xml->getElementsByTagName('Action')->item(0)->nodeValue;
		$results = false;
		if (!empty($action) && method_exists($this, "dashboard$action")) 
		{
			$results = call_user_func(array($this, "dashboard$action"), $request_xml);
		}
		
		if ($results === false || !$results instanceof \DOMDocument) 
		{
			$status = $results;
			$results = new \DOMDocument('1.0', 'UTF-8');
			$root = $results->appendChild($results->createElement('Results'));
			$newnode = $results->createElement('Result', 'FAILED');
			$attrib = $results->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			if (is_string($status)) 
			{
				$newnode = $results->createElement('Result', $status);
			}
			else {
				$newnode = $results->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
			}
			$attrib = $results->createAttribute('ID');
			$attrib->value = 'ErrMsg';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
		}

		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($results->saveXML());
		return $response;
	}
	
	public function userPanelTabsAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$userpanels = $em->getRepository('DocovaBundle:UserPanels')->getUserAvailablePanels($this->user->getId());
		if (empty($userpanels)) 
		{
			$userpanels = new UserPanels();
			$userpanels->setTitle($this->user->getUserProfile()->getDisplayName());
			$userpanels->setLayoutName('L4-1');
			$userpanels->setDescription('Default Dashboard Panel.  Please feel free to change this panel.');
			$userpanels->setLayoutOrder(1);
			$userpanels->setLayoutDefault(true);
			$userpanels->setCreator($this->user);
			$userpanels->setAssignedUser($this->user);
			$em->persist($userpanels);
			$em->flush();
			
			$userpanels = array($userpanels);
		}
		return $this->render('DocovaBundle:Default:userPanelTabs.html.twig', array(
			'user' => $this->user,
			'userpanels' => $userpanels
		));
	}

	public function myDocovaAction(Request $request)
	{
		$this->initialize();
		$panel_id = $request->query->get('panelkey');
		$em = $this->getDoctrine()->getManager();
		$sList = '';
		if (!empty($panel_id)) {
			$panel = $em->getRepository('DocovaBundle:UserPanels')->findOneBy(array('id' => $panel_id, 'assignedUser' => $this->user->getId(), 'trash' => false, 'isWorkspace' => false));
		}
		else {
			$panel = $em->getRepository('DocovaBundle:UserPanels')->findBy(array('assignedUser' => $this->user->getId(), 'trash' => false, 'isWorkspace' => false), array('layoutOrder' => 'ASC'), 1);
			$panel = !empty($panel) ? $panel[0] : $panel;
		}

		if (!empty($panel))
		{
			$parent = $panel->getParentPanel();
			$shareList = $em->getRepository('DocovaBundle:UserPanels')->getSharedList($parent ? $parent->getId() : $panel->getId());
			if (!empty($shareList)) {
				foreach ($shareList as $user) {
					$sList .= $user->getAssignedUser()->getUserNameDnAbbreviated().';';
				}
				$sList = substr_replace($sList, '', -1);
			}
			$userPanels = $em->getRepository('DocovaBundle:UserPanels')->getUserAvailablePanels($this->user->getId());
		}

		return $this->render('DocovaBundle:Default:MyDocova.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings,
				'panel' => $panel,
				'shareList' => $sList,
				'userPanels' => $userPanels
			));
	}
	
	public function getSubscribedLibrariesFoldersAction()
	{
		$this->initialize();
		$response = new Response();
		$em = $this->getDoctrine()->getManager();
		$content = '';
		$libraries = $em->getRepository('DocovaBundle:Libraries')->getUserLibraries($this->user, true, array('Library_Title' => 'ASC'));
		if (!empty($libraries))
		{
			$isAdmin = $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
			foreach ($libraries as $library)
			{
				$roots = $em->getRepository('DocovaBundle:Folders')->getSubfolders($library['id'], $this->user, null, $isAdmin);
				if (!empty($roots))
				{
					foreach ($roots as $folder)
					{
						$content .= $library['Library_Title'].'~'.$folder['Folder_Name'].'; ';
					}
				}
			}
		}
/*
 * OLD CODE
 *
		$folders = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Folders')->findBy(array('Del' => false, 'parentfolder' => NULL), array('Folder_Name' => 'ASC'));
		$content = '';
		if (!empty($folders) && count($folders) > 0) 
		{
			$securityContext = $this->get('security.authorization_checker');
			foreach ($folders as $folder) 
			{
				if ($folder->getLibrary()->getTrash() !== true && $securityContext->isGranted('VIEW', $folder) === true && $securityContext->isGranted('VIEW', $folder->getLibrary()) === true && $securityContext->isGranted('DELETE', $folder->getLibrary()) === true) {
					$content .= $folder->getLibrary()->getLibraryTitle().'~'.$folder->getFolderName().'; ';
				}
			}
			$content = !empty($content) ? substr_replace($content, '', -2) : $content;
		}
*/
		
		$response->setContent($content);
		return $response;
	}
	
	public function showDlgSharePanelAction()
	{
		return $this->render('DocovaBundle:Default:_dlgSharePanel.html.twig');
	}
	
	public function showDlgCreatePanelAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:_dlgCreatePanel.html.twig', array('user' => $this->user));
	}
	
	public function showDlgChangePanelAction()
	{
		return $this->render('DocovaBundle:Default:_dlgChangePanel.html.twig');
	}
	
	public function showDlgAddPanelAction()
	{
		$em = $this->getDoctrine()->getManager();
		$panelsList = $em->getRepository('DocovaBundle:UserPanels')->findBy(array('panelList' => true, 'shareType' => 'Shared', 'isWorkspace' => false, 'parentPanel' => NULL));
		return $this->render('DocovaBundle:Default:_dlgAddPanel.html.twig', array('panelsList' => $panelsList));
	}
	
	public function showDlgSelectWidgetAction()
	{
		$widgets = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Widgets')->findBy(['inactive' => false], ['widgetName' => 'ASC']);
		return $this->render('DocovaBundle:Default:_dlgSelectWidget.html.twig', array(
			'widgets' => $widgets
		));
	}
	
	public function showDlgRemoveSharedPanelAction()
	{
		return $this->render('DocovaBundle:Default:_dlgRemoveDeleteSharedPane.html.twig');
	}
	
	public function getManagedPanelsAction()
	{
		$this->initialize();
		$response = new Response();
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$response->headers->set('Content-Type', 'text/xml');

		$root = $result_xml->appendChild($result_xml->createElement('viewentries'));

		$em = $this->getDoctrine()->getManager();
		$list = $em->getRepository('DocovaBundle:UserPanels')->findBy(array('shareType' => 'Shared', 'isWorkspace' => false, 'parentPanel' => NULL, 'assignedUser' => NULL, 'trash' => true, 'creator' => $this->user));
		if (!empty($list) && count($list) > 0) 
		{
			foreach ($list as $p) {
				$node = $result_xml->createElement('viewentry');
				$attrib = $result_xml->createAttribute('position');
				$attrib->value = $p->getLayoutOrder();
				$node->appendChild($attrib);
				$attrib = $result_xml->createAttribute('unid');
				$attrib->value = $p->getId();
				$node->appendChild($attrib);
				$newnode = $result_xml->createElement('entrydata');
				$attrib = $result_xml->createAttribute('columnnumber');
				$attrib->value = 0;
				$newnode->appendChild($attrib);
				$textnode = $result_xml->createElement('text');
				$textvalue = $result_xml->createTextNode($p->getCreator()->getUserNameDnAbbreviated());
				$textnode->appendChild($textvalue);
				$newnode->appendChild($textnode);
				$node->appendChild($newnode);
				$newnode = $result_xml->createElement('entrydata');
				$attrib = $result_xml->createAttribute('columnnumber');
				$attrib->value = 1;
				$newnode->appendChild($attrib);
				$textnode = $result_xml->createElement('text');
				$textvalue = $result_xml->createTextNode('<option value="'.$p->getId().'">'.$p->getTitle().'</option>');
				$textnode->appendChild($textvalue);
				$newnode->appendChild($textnode);
				$node->appendChild($newnode);
				$root->appendChild($node);
			}
		}
		
		$response->setContent($result_xml->saveXML());
		return $response;
	}
	
	public function getPublicPanelsAction()
	{
		$response = new Response();
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$response->headers->set('Content-Type', 'text/xml');
		
		$root = $result_xml->appendChild($result_xml->createElement('viewentries'));
		
		$em = $this->getDoctrine()->getManager();
		$list = $em->getRepository('DocovaBundle:UserPanels')->findBy(array('panelList' => true, 'shareType' => 'Shared', 'isWorkspace' => false, 'parentPanel' => NULL));
		if (!empty($list) && count($list) > 0)
		{
			foreach ($list as $p) {
				$node = $result_xml->createElement('viewentry');
				$attrib = $result_xml->createAttribute('position');
				$attrib->value = $p->getLayoutOrder();
				$node->appendChild($attrib);
				$attrib = $result_xml->createAttribute('unid');
				$attrib->value = $p->getId();
				$node->appendChild($attrib);
				$newnode = $result_xml->createElement('entrydata');
				$attrib = $result_xml->createAttribute('columnnumber');
				$attrib->value = 0;
				$newnode->appendChild($attrib);
				$textnode = $result_xml->createElement('text');
				$textvalue = $result_xml->createTextNode($p->getCreator()->getUserNameDnAbbreviated());
				$textnode->appendChild($textvalue);
				$newnode->appendChild($textnode);
				$node->appendChild($newnode);
				$newnode = $result_xml->createElement('entrydata');
				$attrib = $result_xml->createAttribute('columnnumber');
				$attrib->value = 1;
				$newnode->appendChild($attrib);
				$textnode = $result_xml->createElement('text');
				$textvalue = $result_xml->createTextNode('<option value="'.$p->getId().'">'.$p->getTitle().'</option>');
				$textnode->appendChild($textvalue);
				$newnode->appendChild($textnode);
				$node->appendChild($newnode);
				$root->appendChild($node);
			}
		}
		
		$response->setContent($result_xml->saveXML());
		return $response;
	}
	
	public function getPanelsListByIdAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$response = new Response();
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_result->appendChild($xml_result->createElement('viewentries'));
		$panel = $request->query->get('StartKey');
		$panel = $em->find('DocovaBundle:UserPanels', $panel);
		if (!empty($panel)) 
		{
			$node = $xml_result->createElement('viewentry');
			$attr = $xml_result->createAttribute('unid');
			$attr->value = $panel->getId();
			$node->appendChild($attr);

			$newnode = $xml_result->createElement('entrydata');
			$attr = $xml_result->createAttribute('columnnumber');
			$attr->value = 0;
			$newnode->appendChild($attr);
			$text_node = $xml_result->createElement('text');
			$text_val = $xml_result->createTextNode($panel->getId());
			$text_node->appendChild($text_val);
			$newnode->appendChild($text_node);
			$node->appendChild($newnode);
			$newnode = $xml_result->createElement('entrydata');
			$attr = $xml_result->createAttribute('columnnumber');
			$attr->value = 1;
			$newnode->appendChild($attr); 
			$text_node = $xml_result->createElement('text');
			$text_val = $xml_result->createTextNode($panel->getTitle());
			$text_node->appendChild($text_val);
			$newnode->appendChild($text_node);
			$node->appendChild($newnode);
			$newnode = $xml_result->createElement('entrydata');
			$attr = $xml_result->createAttribute('columnnumber');
			$attr->value = 2;
			$newnode->appendChild($attr); 
			$text_node = $xml_result->createElement('text');
			$text_val = $xml_result->createTextNode($panel->getDescription());
			$text_node->appendChild($text_val);
			$newnode->appendChild($text_node);
			$node->appendChild($newnode);
			$newnode = $xml_result->createElement('entrydata');
			$attr = $xml_result->createAttribute('columnnumber');
			$attr->value = 3;
			$newnode->appendChild($attr); 
			$text_node = $xml_result->createElement('text');
			$text_val = $xml_result->createTextNode($panel->getCreator()->getUserNameDnAbbreviated());
			$text_node->appendChild($text_val);
			$newnode->appendChild($text_node);
			$node->appendChild($newnode);
				
			$root->appendChild($node);
		}
		
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml_result->saveXML());
		return $response;
	}
	
	public function getWidgetByKeyAction(Request $request)
	{
		$response = new Response();
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_result->appendChild($xml_result->createElement('viewentries'));
		$widget_id = $request->query->get('StartKey');
		if (!empty($widget_id)) 
		{
			$widget = $this->getDoctrine()->getManager()->find('DocovaBundle:Widgets', $widget_id);
			if (!empty($widget)) 
			{
				$entry = $xml_result->createElement('viewentry');
				$attrib = $xml_result->createAttribute('unid');
				$attrib->value = $widget->getId();
				$entry->appendChild($attrib);
				$newnode = $xml_result->createElement('entrydata');
				$attrib = $xml_result->createAttribute('columnnumber');
				$attrib->value = 0;
				$newnode->appendChild($attrib);
				$textvalue = $xml_result->createTextNode($widget->getId());
				$textnode = $xml_result->createElement('text');
				$textnode->appendChild($textvalue);
				$newnode->appendChild($textnode);
				$entry->appendChild($newnode);
				$newnode = $xml_result->createElement('entrydata');
				$attrib = $xml_result->createAttribute('name');
				$attrib->value = 'WidgetDescription';
				$newnode->appendChild($attrib);
				$attrib = $xml_result->createAttribute('columnnumber');
				$attrib->value = 1;
				$newnode->appendChild($attrib);
				$textvalue = $xml_result->createTextNode($widget->getDescription());
				$textnode = $xml_result->createElement('text');
				$textnode->appendChild($textvalue);
				$newnode->appendChild($textnode);
				$entry->appendChild($newnode);
				$root->appendChild($entry);
			}
		}
		
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml_result->saveXML());
		return $response;
	}
	
	public function getNamesListAction()
	{
		$em = $this->getDoctrine()->getManager();
		$users_list = $em->getRepository('DocovaBundle:UserAccounts')->findBy(array('Trash' => false));
		return $this->render('DocovaBundle:Default:_dlgSelectNamesMulti.html.twig', array(
			'usersList' => $users_list
		));
	}
	
	public function blankFolderSelectedAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:BlankFolderSelected.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function redirectToAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:wRedirect.html.twig', array(
			'user' => $this->user
		));
	}

	public function librariesFrameAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:wLibrariesFrame.html.twig', array('settings' => $this->global_settings));
	}
	
	public function showTabbedTableAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:wTabbedTable.html.twig', array(
			'user' => $this->user
		));
	}

	public function blankContentAction(Request $request)
	{
		$close_frame = $request->query->get('closeframe');
		$close_frame = ($close_frame == 'true') ? 'true' : 'false'; 

		return $this->render('DocovaBundle:Default:BlankContent.html.twig', array(
			'close_frame' => $close_frame
		));
	}

	public function leftNavAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:HomeLeftNav.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings
		));
	}
	
	public function loadFolderControlJSAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:loaderFolderControl.js.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings
			));
	}
	
	public function getFolderControlRelatedDocAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:loaderFolderControlRelatedDoc.js.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings
		));
	}

	public function loadDLExtensionsJSAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:loaderDLExtensions.js.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings
			));
	}
	
	public function loginConfirmationAction()
	{
		$this->initialize();
		$response = new Response();

		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_result->appendChild($xml_result->createElement('login'));
		$newnode = $xml_result->createElement('status', 'OK');
		$root->appendChild($newnode);
		$newnode = $xml_result->createElement('UserName');
		$cdata = $xml_result->createCDATASection($this->user->getUserNameDnAbbreviated());
		$newnode->appendChild($cdata);
		$root->appendChild($newnode);
		
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml_result->saveXML());
		return $response;
	}

	public function libraryWelcomeAction(Request $request)
	{
		$this->initialize();
		$library = $request->query->get('lib');
		if (!empty($library))
		{
			$library = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $library, 'Status' => true, 'Trash' => false));
			if (!empty($library)) {
				$securityContext = $this->get('security.authorization_checker');
				if ($securityContext->isGranted('VIEW', $library) === false && $securityContext->isGranted('DELETE', $library) === false) {
					unset($library);
				}
				unset($securityContext);
			}
		}
		
		if (empty($library)) {
			throw $this->createNotFoundException('Unspecified source library.');
		}
		$library = null;
		return $this->render('DocovaBundle:Default:LibraryWelcome.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function showColorPickerAction()
	{
		return $this->render('DocovaBundle:Default:colorPicker.html.twig');
	}
	
	public function getUploaderScriptAction(Request $request)
	{
		$upid		= $request->query->get('id');
		$settings	= $request->query->get('settings');
		$height		= $request->query->get('height');
		$width		= $request->query->get('width');
		
		$settings = (!empty($settings)) ? $settings : 'UploaderSettings';
		$upid 	= (!empty($upid)) ? $upid : 'DLIUploader1';
		$height	= (!empty($height)) ? $height : '100px';
		$width	= (!empty($width)) ? $width : '280px';
		
		return $this->render('DocovaBundle:Default:loaderUploaderDocSection.js.twig', array(
				'upid' => $upid,
				'settings' => $settings,
				'height' => $height,
				'width' => $width
			));
	}
	
	public function getUploaderIntlScriptAction(Request $request)
	{
		$upid		= $request->query->get('id');
		$settings	= $request->query->get('settings');
		$height		= $request->query->get('height');
		$width		= $request->query->get('width');
		
		$settings = (!empty($settings)) ? $settings : 'UploaderSettings';
		$upid 	= (!empty($upid)) ? $upid : 'DLIUploader1';
		$height	= (!empty($height)) ? $height : '100px';
		$width	= (!empty($width)) ? $width : '280px';
		
		return $this->render('DocovaBundle:Default:loaderUploaderIntlDocSection.js.twig', array(
				'upid' => $upid,
				'settings' => $settings,
				'height' => $height,
				'width' => $width
		));
	}
	
	public function getXMLViewDataAction(Request $request)
	{
		$this->initialize();
		$view = $request->query->get('view');
		$em = $this->getDoctrine()->getManager();
		$xml_res = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_res->appendChild($xml_res->createElement('Documents'));
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		
		if ($view == 'xmlWorkflowStepsByProcessId')
		{
			$workflow = $em->find('DocovaBundle:Workflow', $request->query->get('lkey'));
			if (!$workflow)
			{
				$this->createNotFoundException('Unspecified source workflow.');
			}
			$xml_res = $em->getRepository('DocovaBundle:Workflow')->getWorkflowStepsXML($workflow);
		}
		elseif ($view == 'xmlWorkflowItemsByDocument')
		{
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $request->query->get('lkey'), 'Trash' => false, 'Archived' => false));
			if (empty($document)) {
				throw $this->createNotFoundException('Unspecified source document.');
			}
			$changeable = ($this->user->getUserNameDnAbbreviated() == $document->getCreator()->getUserNameDnAbbreviated() || $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) ? true : false;
			
			$xml_res = $em->getRepository('DocovaBundle:Documents')->getWorkflowStepsXML($document, $this->user, $this->global_settings, $changeable);
		}
		elseif ($view === 'workflowTasks.xml') 
		{
			$xml_res = new \DOMDocument('1.0', 'UTF-8');
			$root = $xml_res->appendChild($xml_res->createElement('documents'));
			$username = strstr(trim($request->query->get('lkey')), 'wfItem', true);
			$user_obj = $this->findUserAndCreate($username, false);
			if (empty($user_obj)) {
				$this->createNotFoundException('No user with username "'.$username.'" exists!');
			}
			
			$em = $this->getDoctrine()->getManager();
			$assigned_workflows = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getWorkflowTasks($user_obj->getId());
			if (!empty($assigned_workflows) && count($assigned_workflows) > 0)
			{
				$step_types = array(1 => 'Start', 2 => 'Review', 3 => 'Approve', 4 => 'End');
				foreach ($assigned_workflows as $document_workflow)
				{
					$doc_steps = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findBy(array('Document' => $document_workflow['document'], 'Status' => 'Denied'));
					if ($document_workflow['LTrash'] === false && (empty($doc_steps) || count($doc_steps) < 1)) 
					{
						$element = $root->appendChild($xml_res->createElement('document'));
						$element->appendChild($xml_res->createElement('docid', $document_workflow[0]['id']));
						$element->appendChild($xml_res->createElement('rectype', 'doc'));
						$wfAction = $document_workflow[0]['Step_Type'] == 4 ? 'Approve' : $step_types[$document_workflow[0]['Step_Type']];
						$element->appendChild($xml_res->createElement('wfaction', $wfAction));
						$element->appendChild($xml_res->createElement('isappform', $document_workflow['IsAppForm'] === true ? 1 : null));
						$element->appendChild($xml_res->createElement('parentdocid', $document_workflow['document']));
						$element->appendChild($xml_res->createElement('librarykey', $document_workflow['library']));
						$newnode = $xml_res->createElement('libraryname');
						$CData = $xml_res->createCDATASection($document_workflow['Library_Title']);
						$newnode->appendChild($CData);
						$element->appendChild($newnode);
						$element->appendChild($xml_res->createElement('folderid', $document_workflow['folder']));
						$CData = $xml_res->createCDATASection($document_workflow['Folder_Name']);
						$newnode = $xml_res->createElement('foldername');
						$newnode->appendChild($CData);
						$element->appendChild($newnode);
						$CData = $xml_res->createCDATASection($document_workflow['Doc_Title']);
						$newnode = $xml_res->createElement('title');
						$newnode->appendChild($CData);
						$element->appendChild($newnode);
						$CData = $xml_res->createCDATASection(''); //$document_workflow->getDocument()->getOwner()->getUserNameDnAbbreviated()
						$newnode = $xml_res->createElement('owner');
						$newnode->appendChild($CData);
						$element->appendChild($newnode);
						$assignees = '';
						foreach ($document_workflow[0]['assignee'] as $a) {
							if (!$a['groupMember']) {
								$temp_assignee = $em->getRepository('DocovaBundle:WorkflowAssignee')->find($a['id']);
								$assignees .= $temp_assignee->getAssignee()->getUserNameDnAbbreviated() . ',';
								$temp_assignee = null;
							}
						}
						if (!empty($document_workflow[0]['Assignee_Group']))
						{
							$assignees .= $document_workflow[0]['Assignee_Group'] . ',';
						}
						$assignees = substr_replace($assignees, '', -1);
						$CData = $xml_res->createCDATASection($assignees);
						$newnode = $xml_res->createElement('wforiginator');
						$newnode->appendChild($CData);
						$element->appendChild($newnode);
						$element->appendChild($xml_res->createElement('dockey', $document_workflow[0]['id']));
						$element->appendChild($xml_res->createElement('parentdockey',  $document_workflow['document']));
						$element->appendChild($xml_res->createElement('wfstartdate', $document_workflow[0]['Date_Started']->format('m/d/Y')));
						$due_days = 0;
						$stepActions = $em->getRepository('DocovaBundle:DocWorkflowStepActions')->findBy(array('Step' => $document_workflow[0]['id']));
						if (!empty($stepActions)) {
							foreach ($stepActions as $action) {
								if ($action->getActionType() == 6) {
									$due_days = $action->getDueDays();
									break;
								}
							}
						}
						$date = (!empty($due_days)) ? $document_workflow[0]['Date_Started']->modify("+$due_days day") : null;
						$newnode = $xml_res->createElement('wfduedate');
						$CData = $xml_res->createCDATASection(!empty($date) ? $date->format('m/d/Y') : '');
						$newnode->appendChild($CData);
						$year = $xml_res->createAttribute('Y');
						$year->value = !empty($date) ? $date->format('Y') : '';
						$month = $xml_res->createAttribute('M');
						$month->value = !empty($date) ? $date->format('m') : '';
						$day = $xml_res->createAttribute('D');
						$day->value = !empty($date) ? $date->format('d') : '';
						$weekday = $xml_res->createAttribute('W');
						$weekday->value = !empty($date) ? $date->format('w') : '';
						$hours = $xml_res->createAttribute('H');
						$hours->value = !empty($date) ? $date->format('H') : '';
						$minutes = $xml_res->createAttribute('MN');
						$minutes->value = !empty($date) ? $date->format('i') : '';
						$seconds = $xml_res->createAttribute('S');
						$seconds->value = !empty($date) ? $date->format('s') : '';
						$val = $xml_res->createAttribute('val');
						$val->value = !empty($date) ? strtotime($date->format('Y-m-d H:i:s')) : '';
						$orgin_date = $xml_res->createAttribute('origDate');
						$orgin_date->value = !empty($date) ? $date->format('m/d/Y') : '';
						$newnode->appendChild($year);
						$newnode->appendChild($month);
						$newnode->appendChild($day);
						$newnode->appendChild($weekday);
						$newnode->appendChild($hours);
						$newnode->appendChild($minutes);
						$newnode->appendChild($seconds);
						$newnode->appendChild($val);
						$newnode->appendChild($orgin_date);
						$element->appendChild($newnode);
						$element->appendChild($xml_res->createElement('wfcompleteddate', (($document_workflow[0]['Date_Completed']) ? $document_workflow[0]['Date_Completed']->format('m/d/Y') : '')));
						$CData = $xml_res->createCDATASection($document_workflow['Doc_Title']);
						$newnode = $xml_res->createElement('subject');
						$newnode->appendChild($CData);
						$element->appendChild($newnode);
						$CData = $xml_res->createCDATASection($document_workflow[0]['Step_Name']);
						$newnode = $xml_res->createElement('wftitle');
						$newnode->appendChild($CData);
						$element->appendChild($newnode);
					}
				}
			}
		}
		elseif ($view === 'xmlWorkflowItemsInReview.xml') 
		{
			$username = trim($request->query->get('lkey'));
			if (!empty($username) && false !== $user_obj = $this->findUserAndCreate($username)) 
			{
				$em = $this->getDoctrine()->getManager();
				$documents = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getMyReviweItems($user_obj->getId());
				if (!empty($documents) && count($documents) > 0) 
				{
					foreach ($documents as $docstep) {
						if ($docstep->getDocument()->getFolder()->getLibrary()->getTrash() === false) 
						{
							$element = $xml_res->createElement('Document');
							$newnode = $xml_res->createElement('Owner');
							$CData = $xml_res->createCDATASection($docstep->getDocument()->getOwner()->getUserNameDnAbbreviated());
							$newnode->appendChild($CData);
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('wfOriginator');
							$CData = $xml_res->createCDATASection($docstep->getDocument()->getCreator()->getUserNameDnAbbreviated());
							$newnode->appendChild($CData);
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('FolderID', $docstep->getDocument()->getFolder()->getId());
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('FolderName', $docstep->getDocument()->getFolder()->getFolderName());
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('LibraryKey', $docstep->getDocument()->getFolder()->getLibrary()->getId());
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('LibraryName', $docstep->getDocument()->getFolder()->getLibrary()->getLibraryTitle());
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('DocKey', $docstep->getDocument()->getId());
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('ParentDocKey', $docstep->getDocument()->getId());
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('ParentDocID', $docstep->getDocument()->getId());
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('wfStartDate', $docstep->getDateStarted() ? $docstep->getDateStarted()->format('m/d/Y') : '');
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('wfDueDate');
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('wfCompletedDate', $docstep->getDateCompleted() ? $docstep->getDateCompleted()->format('m/d/Y') : '');
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('Subject');
							$CData = $xml_res->createCDATASection($docstep->getDocument()->getDocTitle());
							$newnode->appendChild($CData);
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('Title');
							$CData = $xml_res->createCDATASection($docstep->getStepName() . ' for: ' . $docstep->getDocument()->getDocTitle());
							$newnode->appendChild($CData);
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('Unid', $docstep->getDocument()->getId());
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('Selected', 0);
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('IsNew', 0);
							$element->appendChild($newnode);
							$newnode = $xml_res->createElement('IsDeleted', 0);
							$element->appendChild($newnode);
							$root->appendChild($element);
						}
					}
				}
			}
		}
		elseif ($view === 'luCIAOLogsByParentId')
		{
			$xml_res = new \DOMDocument('1.0', 'UTF-8');
			$root = $xml_res->appendChild($xml_res->createElement('cofiles'));
			$document = $request->query->get('lkey');
			if (!empty($document)) 
			{
				$em = $this->getDoctrine()->getManager();
				$ciao_logs = $em->getRepository('DocovaBundle:AttachmentsDetails')->getUserCheckedOutFiles(null, $document);
				if (!empty($ciao_logs)) 
				{
					$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
					foreach ($ciao_logs as $log) {
						$node = $xml_res->createElement('file');
						$CData = $xml_res->createCDATASection($log->getCheckedOutBy()->getUserNameDnAbbreviated());
						$newnode = $xml_res->createElement('editor');
						$newnode->appendChild($CData);
						$node->appendChild($newnode);
						$newnode = $xml_res->createElement('date', $log->getDateCheckedOut()->format($defaultDateFormat . ' h:i:s A'));
						$node->appendChild($newnode);
						$CData = $xml_res->createCDATASection($log->getCheckedOutPath());
						$newnode = $xml_res->createElement('path');
						$newnode->appendChild($CData);
						$node->appendChild($newnode);
						$CData = $xml_res->createCDATASection($log->getFileName());
						$newnode = $xml_res->createElement('filename');
						$newnode->appendChild($CData);
						$node->appendChild($newnode);
						$CData = $xml_res->createCDATASection(strtolower($log->getFileName()));
						$newnode = $xml_res->createElement('fnamelc');
						$newnode->appendChild($CData);
						$node->appendChild($newnode);
						$root->appendChild($node);
					}
				}
			}
		}

		$response->setContent($xml_res->saveXML());
		return $response;
	}
	
	public function getRelatedDocLinksAction(Request $request)
	{
		$response = new Response();
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_result->appendChild($xml_result->createElement('documents'));
		$response->headers->set('Content-Type', 'text/xml');
		$doc_id = $request->query->get('RestricttoCategory');
		
		if (!empty($doc_id))
		{
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id, 'Archived' => false));
			if (empty($document)) {
				throw $this->createNotFoundException('Unspecified source document ID = '. $doc_id);
			}
			
			$related_docs = $em->getRepository('DocovaBundle:RelatedDocuments')->findBy(array('Parent_Doc' => $document, 'Trash' => false));
			if (!empty($related_docs) && count($related_docs) > 0) {
				$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
				$global_settings = $global_settings[0];
				$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
				unset($global_settings);
				foreach ($related_docs as $doc)
				{
					$child_node = $root->appendChild($xml_result->createElement('document'));
					$child_node->appendChild($xml_result->createElement('relateddata'));
					$child_node->appendChild($xml_result->createElement('librarykey', $doc->getRelatedDoc()->getFolder()->getLibrary()->getId()));
					$CData = $xml_result->createCDATASection($doc->getRelatedDoc()->getFolder()->getLibrary()->getLibraryTitle());
					$child = $xml_result->createElement('libraryname');
					$child->appendChild($CData);
					$child_node->appendChild($child);
					$child_node->appendChild($xml_result->createElement('folderid', $doc->getRelatedDoc()->getFolder()->getId()));
					$CData = $xml_result->createCDATASection($doc->getRelatedDoc()->getFolder()->getFolderName());
					$child = $xml_result->createElement('foldername');
					$child->appendChild($CData);
					$child_node->appendChild($child);
					$child_node->appendChild($xml_result->createElement('parentdocid', $doc->getRelatedDoc()->getId()));
					$child_node->appendChild($xml_result->createElement('doctypekey', $doc->getRelatedDoc()->getDocType()->getId()));
					$CData = $xml_result->createCDATASection($doc->getRelatedDoc()->getDocTitle());
					$child = $xml_result->createElement('title');
					$child->appendChild($CData);
					$child_node->appendChild($child);
					$CData = $xml_result->createCDATASection($doc->getRelatedDoc()->getDocStatus());
					$child = $xml_result->createElement('status');
					$child->appendChild($CData);
					$child_node->appendChild($child);
					$CData = $xml_result->createCDATASection($doc->getRelatedDoc()->getModifier()->getUserProfile()->getDisplayName());
					$child = $xml_result->createElement('author');
					$child->appendChild($CData);
					$child_node->appendChild($child);
					$date = $doc->getRelatedDoc()->getDateCreated();
					$CData = $xml_result->createCDATASection($doc->getRelatedDoc()->getDateCreated()->format($defaultDateFormat));
					$child = $xml_result->createElement('datecreated');
					$child->appendChild($CData);
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
					$child->appendChild($year);
					$child->appendChild($month);
					$child->appendChild($day);
					$child->appendChild($weekday);
					$child->appendChild($hours);
					$child->appendChild($minutes);
					$child->appendChild($seconds);
					$child->appendChild($val);
					$child_node->appendChild($child);
					$CData = $xml_result->createCDATASection($doc->getRelatedDoc()->getDescription());
					$child = $xml_result->createElement('description');
					$child->appendChild($CData);
					$child_node->appendChild($child);
					$child_node->appendChild($xml_result->createElement('Unid', $doc->getRelatedDoc()->getId()));
					$child_node->appendChild($xml_result->createElement('docid', $doc->getRelatedDoc()->getId()));
					$child_node->appendChild($xml_result->createElement('typeicon', '&#157;'));
					$child_node->appendChild($xml_result->createElement('rectype', 'doc'));
					$child_node->appendChild($xml_result->createElement('lastmodifieddate', ($doc->getRelatedDoc()->getDateModified()) ? $doc->getRelatedDoc()->getDateModified()->format($defaultDateFormat.' H:i:s') : ''));
					$child_node->appendChild($xml_result->createElement('datecreated', $doc->getRelatedDoc()->getDateCreated()->format($defaultDateFormat.' H:i:s')));
					$version = $doc->getRelatedDoc()->getDocVersion() ? ($doc->getRelatedDoc()->getDocVersion() . '.' . $doc->getRelatedDoc()->getRevision()) : '';
					$child_node->appendChild($xml_result->createElement('version', $version));
					$child_node->appendChild($xml_result->createElement('active', 1));
					$child_node->appendChild($xml_result->createElement('selected', 0));
					$child_node->appendChild($xml_result->createElement('lvinfo'));
					$child_node->appendChild($xml_result->createElement('lvparentdockey'));
					$child_node->appendChild($xml_result->createElement('lvparentdocid'));
					$child_node->appendChild($xml_result->createElement('archivedparentdockey', $doc->getRelatedDoc()->getId()));
					$child_node->appendChild($xml_result->createElement('docisdeleted', $doc->getRelatedDoc()->getTrash()));
				}
			}
		}
		
		return $response->setContent($xml_result->saveXML());
	}
	
	public function getActivityDataAction(Request $request)
	{
		$found = false;
		$response = new Response();
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_result->appendChild($xml_result->createElement('documents'));

		$category = $request->query->get('RestrictToCategory');
		if (!empty($category)) 
		{
			$category = explode('~', $category);
			$em = $this->getDoctrine()->getManager();
			$assignee = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('id' => $category[0], 'Trash' => false));
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $category[1], 'Trash' => false, 'Archived' => false));
			if (!empty($assignee) && !empty($document)) 
			{
				unset($assignee, $document);
				$activities = $em->getRepository('DocovaBundle:DocumentActivities')->getDocActivitiesAssignedTo($category[0], $category[1]);
				if (!empty($activities)) 
				{
					$found = true;
					foreach ($activities as $activity)
					{
						$entry = $xml_result->createElement('Document');
						$newnode = $xml_result->createElement('Subject');
						$cdata = $xml_result->createCDATASection($activity->getSubject());
						$newnode->appendChild($cdata);
						$entry->appendChild($newnode);
						$newnode = $xml_result->createElement('Unid', $activity->getId());
						$entry->appendChild($newnode);
						$obligation = '0';
						if ($activity->getRequestResponse()) {
							$obligation = '2';
						}
						elseif ($activity->getAckRequired()) {
							$obligation = '1';
						}
						$newnode = $xml_result->createElement('Obligation');
						$cdata = $xml_result->createCDATASection($obligation);
						$newnode->appendChild($cdata);
						$entry->appendChild($newnode);
						$root->appendChild($entry);
					}
				}
			}
		}
		
		if ($found === false) 
		{
			$newnode = $xml_result->createElement('h2', 'No documents found');
			$root->appendChild($newnode);
		}
		
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml_result->saveXML());
		unset($xml_result);
		
		return $response;
	}
	
	public function getActivityDocDataAction(Request $request)
	{
		$found = false;
		$response = new Response();
		$xml_result = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml_result->appendChild($xml_result->createElement('Documents'));
		
		$category = $request->query->get('RestrictToCategory');
		if (!empty($category))
		{
			$em = $this->getDoctrine()->getManager();
			$activities = $em->getRepository('DocovaBundle:DocumentActivities')->getDocumentActivities($category);
			if (!empty($activities))
			{
				$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
				$global_settings = $global_settings[0];
				$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
				foreach ($activities as $activity)
				{
					$entry = $xml_result->createElement('Document');
					$newnode = $xml_result->createElement('ActivityType');
					$cdata = $xml_result->createCDATASection($activity->getActivityAction());
					$newnode->appendChild($cdata);
					$entry->appendChild($newnode);
					$newnode = $xml_result->createElement('ActivityStatus');
					if ($activity->getIsComplete()) {
						$activityStatus = 'Complete';
					}
					elseif ($activity->getRequestResponse()) {
						$activityStatus = 'Response Pending';
					}
					elseif ($activity->getAckRequired()) {
						$activityStatus = 'Acknowledgement Pending';
					}
					else {
						$activityStatus = 'Not Read';
					}
					$cdata = $xml_result->createCDATASection($activityStatus);
					$newnode->appendChild($cdata);
					$entry->appendChild($newnode);
					$newnode = $xml_result->createElement('CreatedBy');
					$cdata = $xml_result->createCDATASection($global_settings->getUserDisplayDefault() ? $activity->getCreatedBy()->getUserProfile()->getDisplayName() : $activity->getCreatedBy()->getUserNameDnAbbreviated());
					$newnode->appendChild($cdata);
					$entry->appendChild($newnode);
					$newnode = $xml_result->createElement('Recipient');
					$cdata = $xml_result->createCDATASection($global_settings->getUserDisplayDefault() ? $activity->getAssignee()->getUserProfile()->getDisplayName() : $activity->getAssignee()->getUserNameDnAbbreviated());
					$newnode->appendChild($cdata);
					$entry->appendChild($newnode);
					$newnode = $xml_result->createElement('Subject');
					$cdata = $xml_result->createCDATASection($activity->getSubject());
					$newnode->appendChild($cdata);
					$entry->appendChild($newnode);
					$newnode = $xml_result->createElement('Body');
					$cdata = $xml_result->createCDATASection($activity->getMessage());
					$newnode->appendChild($cdata);
					$entry->appendChild($newnode);
					$newnode = $xml_result->createElement('Resp');
					$cdata = $xml_result->createCDATASection($activity->getResponse());
					$newnode->appendChild($cdata);
					$entry->appendChild($newnode);
					$newnode = $xml_result->createElement('StatusDate', $activity->getStatusDate()->format($defaultDateFormat));
					$entry->appendChild($newnode);
					$newnode = $xml_result->createElement('Unid', $activity->getId());
					$entry->appendChild($newnode);
					$root->appendChild($entry);
				}
			}
			else {
				$newnode = $xml_result->createElement('h2', 'No documents found');
				$root->appendChild($newnode);
			}
		}
		else 	
		{
			$newnode = $xml_result->createElement('h2', 'No documents found');
			$root->appendChild($newnode);
		}
		
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml_result->saveXML());
		unset($xml_result);
		
		return $response;
	}
	
	public function getWorkflowTasksAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:WorkflowTasks.html.twig', array(
				'user' => $this->user
				));
	}
	
	public function getTasksAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:Tasks.html.twig', array(
				'user' => $this->user
				));
	}
	
	public function getWatchListsAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:WatchLists.html.twig', array(
				'user' => $this->user
				));
	}
	
	public function searchAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$libraries = array();
		$available_libs = $em->getRepository('DocovaBundle:Libraries')->getUserLibraries($this->user, true, array('Community' => 'ASC', 'Realm' => 'ASC', 'Library_Title' => 'ASC'));
		//$available_libs = $em->getRepository('DocovaBundle:Libraries')->findBy(array('Trash' => false), array('Community' => 'ASC', 'Realm' => 'ASC', 'Library_Title' => 'ASC'));
		foreach ($available_libs as $library) {
//			if ($securityContext->isGranted('VIEW', $library) === true && $customACL_obj->isUserGranted($library, $this->user, 'delete') === true) {
			if ($library['Community']) {
				if (!array_key_exists('community', $libraries))
					$libraries['community'] = array();
				if (!array_key_exists($library['Community'], $libraries['community']))
					$libraries['community'][$library['Community']] = array();
				if ($library['Realm']) {
					if (!array_key_exists('realm', $libraries['community'][$library['Community']]))
						$libraries['community'][$library['Community']]['realm'] = array();
					if (!array_key_exists($library['Realm'], $libraries['community'][$library['Community']]['realm']))
						$libraries['community'][$library['Community']]['realm'][$library['Realm']] = array();
					
					$libraries['community'][$library['Community']]['realm'][$library['Realm']][] = $library;
				}
				else {
					$libraries['community'][$library['Community']][] = $library;
				}
			}
			else {
				$libraries[] = $library;
			}
//			}			
		}
		$apps = $em->getRepository('DocovaBundle:Libraries')->getAllAvailableApps($this->container); //$this->user
		if (!empty($apps))
		{
			foreach ($apps as $app) {
				$libraries[] = $app;
			}
		}
		$userSavedSearches = $em->getRepository('DocovaBundle:SavedSearches')->getUserSavedSearchesInLibraries($this->user->getId());
		$default_perspective = $em->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Default_For' => 'Global Search', 'Trash' => false));
		$search_perspectives = $em->getRepository('DocovaBundle:SystemPerspectives')->getValidPerspectivesFor('Global Search');
		return $this->render('DocovaBundle:Default:wSearch.html.twig', array(
			'user' => $this->user,
			'global_settings' => $this->global_settings,
			'savedSearches' => $userSavedSearches,
			'default_perspective' => $default_perspective,
			'search_perspectives' => $search_perspectives,
			'libraries' => $libraries
		));
	}
	
	public function tabbedTableSearchAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:wTabbedTableSearch.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings
				));
	}
	
	public function searchFrameAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:wSearchFrame.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings
				));
	}
	
	public function openHomeFrameAction(Request $request)
	{
		$goto = $request->query->get('goto');
		if (empty($goto))
		{
			return $this->redirect($this->generateUrl('docova_homepage'));
		}

		$this->initialize();
		return $this->render('DocovaBundle:Default:wHomeFrame.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings
		));
	}
	
	public function openSecondFrameAction(Request $request)
	{
		$goto = $request->query->get('goto');
		if (empty($goto))
		{
			return $this->redirect($this->generateUrl('docova_homepage'));
		}
		$params = explode(',', $goto);
		$em = $this->getDoctrine()->getManager();
		if (empty($params[2]))
		{
			if (empty($params[1]))
			{
				throw $this->createNotFoundException('Document ID and Folder ID are missed.');
			}
		
			$document = '';
			$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $params[1], 'Del' => false, 'Inactive' => 0));
			if (empty($folder))
			{
				throw $this->createNotFoundException('Unspecified folder source ID = '. $params[1]);
			}
		}
		else
		{
			//*********External View Extensions*********
			$dataView = $em->getRepository("DocovaBundle:DataViews")->getDataView($params[1]);
			$dataSource = !empty($dataView) ? $dataView->getDataSource($em) : null;
		
			if (!empty($dataSource)){
				$response = ExternalViews::readDocument($this, $this->container->getParameter("document_root"), $params[2], $params[1], $dataView, $dataSource);
				if ($response!=null)
					return $response;
			}
			//*******************************************
				
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $params[2], 'Archived' => false));
			if (empty($document))
			{
				throw $this->createNotFoundException('Unspecified document source ID = '. $params[2]);
			}
			$folder = $document->getFolder();
		}
		$this->initialize();

		$user = $em->getReference('DocovaBundle:UserAccounts', $this->user->getId());
		$library = $em->getReference('DocovaBundle:Libraries', $params[0]);
		$em = null;
		$customAcl = new CustomACL($this->container);
		if (false === $customAcl->isUserGranted($library, $user, 'delete'))
		{
			$security_check = $this->get('security.authorization_checker');
			if (false !== $security_check->isGranted('VIEW', $library)) {
				$customAcl->insertObjectAce($library, $user, 'delete', false);
			}
		}
		$user = $customAcl = $library = null;
		return $this->render('DocovaBundle:Default:wHomeFrame2.html.twig', array(
			'user' => $this->user,
			'folder' => $folder,
			'document' => $document,
			'settings' => $this->global_settings
		));
	}
	
	public function getDLESystemConfigurationAction()
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$this->initialize();
		$dle_config = '<?xml version="1.0" encoding="UTF-8"?>
<Configuration>
<SystemKey>'.$this->global_settings->getSystemKey().'</SystemKey>
<Version>4.0.0 SE</Version>
<Title>'.$this->global_settings->getApplicationTitle().'</Title>
<UserName><![CDATA['.$this->user->getUserNameDnAbbreviated().']]></UserName>
<DLELicenseCode>'.$this->global_settings->getFolderLicenseKey().'</DLELicenseCode>
<UseLazyLoad>'.($this->user->getUserProfile()->getLoadLibraryFolders() ? 'false' : 'true').'</UseLazyLoad>
<DLEVersion></DLEVersion>
<DLFeeds>
  <Feed>
    <Key>1</Key>
    <Name><![CDATA[Pending Workflow]]></Name>
    <Display><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=3&format=RSS]]></Display>
    <Alerts><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=3&format=DLE]]></Alerts>
  </Feed>
  <Feed>
    <Key>2</Key>
    <Name><![CDATA[To Do]]></Name>
    <Display><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=5&format=RSS]]></Display>
    <Alerts><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=5&format=DLE]]></Alerts>
  </Feed>
  <Feed>
    <Key>3</Key>
    <Name><![CDATA[Personal Watches]]></Name>
    <Display><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=0&format=RSS]]></Display>
    <Alerts><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=0&format=DLE]]></Alerts>
  </Feed>
  <Feed>
    <Key>4</Key>
    <Name><![CDATA[Other Watch Lists]]></Name>
    <Display><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=S&format=RSS]]></Display>
    <Alerts><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=S&format=DLE]]></Alerts>
  </Feed>
  <Feed>
    <Key>5</Key>
    <Name><![CDATA[Favorites]]></Name>
    <Display><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=1&format=RSS]]></Display>
    <Alerts><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=1&format=DLE]]></Alerts>
  </Feed>
  <Feed>
    <Key>6</Key>
    <Name><![CDATA[Recently Edited]]></Name>
    <Display><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=2&format=RSS]]></Display>
    <Alerts><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=2&format=DLE]]></Alerts>
  </Feed>
  <Feed>
    <Key>7</Key>
    <Name><![CDATA[My Documents]]></Name>
    <Display><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=4&format=RSS]]></Display>
    <Alerts><![CDATA['.$this->generateUrl('docova_userwatchlists', array(), true).'?ReadForm&listid=4&format=DLE]]></Alerts>
  </Feed>
</DLFeeds>
</Configuration>';
		
		$response->setContent($dle_config);
		return $response;
	}
	
	public function getDLECreateInstanceAction()
	{
		$this->initialize();
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$homeUrl = $this->generateUrl('docova_homepage', array(), UrlGeneratorInterface::ABSOLUTE_URL);
		$homeUrl = substr_replace($homeUrl, '', -1);
		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<Configuration>
<DocLogicInstance>
<Action>Add</Action>
<SystemKey>{$this->global_settings->getSystemKey()}</SystemKey>
<Version>5.5.0 SE</Version>
<SystemName>{$this->global_settings->getApplicationTitle()}</SystemName>
<LoginUrl>{$this->generateUrl('docova_mobile_login_check', array(), UrlGeneratorInterface::ABSOLUTE_URL)}</LoginUrl>
<HomeUrl>{$homeUrl}</HomeUrl>
<UserName><![CDATA[{$this->user->getUsername()}]]></UserName>
<isSymfony>1</isSymfony>
<LicenseCode>{$this->global_settings->getFolderLicenseKey()}</LicenseCode>
</DocLogicInstance>
</Configuration>
XML;
		$response->setContent($xml);
		return $response;
	}
	
	public function openDlgAboutDocovaAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgAboutDocova.html.twig', array(
		    'user' => $this->user,
		    'settings' => $this->global_settings
		));
	}
	
	

	/**
	 * Find a user info in Database, if does not exist a user object can be created according to its info through LDAP server
	 *
	 * @param string $username
	 * @param boolean $create
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUserAndCreate($username, $create = true)
	{
		if (empty($this->user)) {
			$this->initialize();
		}
		$em = $this->getDoctrine()->getManager();
		$utilHelper= new UtilityHelperTeitr($this->global_settings,$this->container);
		return $utilHelper->findUserAndCreate($username, $create,$em);
	}
	
	/**
	 * Create a new panel for current user
	 * 
	 * @param \DOMDocument $xml_request
	 * @return \DOMDocument|boolean
	 */
	private function dashboardCREATEPANEL($xml_request)
	{
		try {
			$em = $this->getDoctrine()->getManager();
			$panel = new UserPanels();
			$panel->setCreator($this->user);
			$panel->setAssignedUser($this->user);
			$panel->setTitle(trim($xml_request->getElementsByTagName('PanelName')->item(0)->nodeValue));
			$panel->setDescription(trim($xml_request->getElementsByTagName('PanelDesc')->item(0)->nodeValue));
			$panel->setLayoutName(trim($xml_request->getElementsByTagName('PanelLayout')->item(0)->nodeValue));
			$last_order = $em->getRepository('DocovaBundle:UserPanels')->getUserLastLayoutOrder($this->user->getId());
			$panel->setLayoutOrder($last_order + 1);
			unset($last_order);
			$em->persist($panel);
			$em->flush();
			
			$result = new \DOMDocument('1.0', 'UTF-8');
			$root = $result->appendChild($result->createElement('Results'));
			$newnode = $result->createElement('Result', 'OK');
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			$newnode = $result->createElement('Result', $panel->getId());
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Ret1';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			
			return $result;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Change the widget reference
	 * 
	 * @param \DOMDocument $xml_request
	 * @return \DOMDocument|boolean
	 */
	private function dashboardCHANGEWIDGET($xml_request)
	{
		$panel = trim($xml_request->getElementsByTagName('PanelKey')->item(0)->nodeValue);
		$box_no = trim($xml_request->getElementsByTagName('BoxNumber')->item(0)->nodeValue);
		$widget_id = trim($xml_request->getElementsByTagName('WidgetID')->item(0)->nodeValue);
		try {
			$em = $this->getDoctrine()->getManager();
			$exists = $em->getRepository('DocovaBundle:UserPanels')->widgetExists($panel, $widget_id);
			if ($exists === true) {
				$result = new \DOMDocument('1.0', 'UTF-8');
				$root = $result->appendChild($result->createElement('Results'));
				$newnode = $result->createElement('Result', 'NOTOK');
				$attrib = $result->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
				return $result;
			}
			$box_widget = $em->getRepository('DocovaBundle:PanelWidgets')->findOneBy(array('panel' => $panel, 'boxNo' => $box_no));
			$widget = $em->getRepository('DocovaBundle:Widgets')->find($widget_id);
			if (empty($widget)) {
				throw new \Exception('Unspecified source widget with ID = '.$widget_id);
			}

			if (!empty($box_widget)) 
			{
				$box_widget->setWidget($widget);
				$panel = $box_widget->getPanel();
			}
			else {
				$panel = $em->getRepository('DocovaBundle:UserPanels')->find($panel);
				$box_widget = new PanelWidgets();
				$box_widget->setBoxNo($box_no);
				$box_widget->setPanel($panel);
				$box_widget->setWidget($widget);
				$em->persist($box_widget);
			}
					
			if ($panel->getShareType() == 'Shared' && !$panel->getParentPanel()) {
				$subpanels = $em->getRepository('DocovaBundle:UserPanels')->getSharedList($panel->getId());
				if (!empty($subpanels) && count($subpanels) > 0) 
				{
					foreach ($subpanels as $sp) {
						if ($sp->getPanelWidgets()->count() > 0) {
							$found = false;
							foreach ($sp->getPanelWidgets() as $pw) {
								if ($pw->getBoxNo() == $box_no) {
									$pw->setWidget($widget);
									$found = true;
									break;
								}
							}
							
							if ($found === false) {
								$box_widget = new PanelWidgets();
								$box_widget->setBoxNo($box_no);
								$box_widget->setPanel($sp);
								$box_widget->setWidget($widget);
								$em->persist($box_widget);
							}
						}
					}
				}
			}

			unset($panel, $box_no, $widget, $widget_id, $subpanels, $sp, $pw, $box_widget);
			$em->flush();

			$result = new \DOMDocument('1.0', 'UTF-8');
			$root = $result->appendChild($result->createElement('Results'));
			$newnode = $result->createElement('Result', 'OK');
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
				
			return $result;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Change the selected panel properties
	 * 
	 * @param \DOMDocument $xml_request
	 * @return \DOMDocument|boolean
	 */
	private function dashboardCHANGEPANEL($xml_request)
	{
		$em = $this->getDoctrine()->getManager();
		$panel = trim($xml_request->getElementsByTagName('PanelKey')->item(0)->nodeValue);
		try {
			$panel = $em->getRepository('DocovaBundle:UserPanels')->find($panel);
			if (empty($panel)) {
				throw new \Exception('Unspecified source User Panel.');
			}
			
			if (!$panel->getIsWorkspace())
			{
				$panel->setTitle(trim($xml_request->getElementsByTagName('PanelName')->item(0)->nodeValue));
				$panel->setDescription(trim($xml_request->getElementsByTagName('PanelDesc')->item(0)->nodeValue));
				$panel->setAssignedUser($this->user);
			}
			$panel->setLayoutName(trim($xml_request->getElementsByTagName('PanelLayout')->item(0)->nodeValue));
			$em->flush();

			if (!$panel->getParentPanel() && $panel->getShareType() == 'Shared') 
			{
				$children = $em->getRepository('DocovaBundle:UserPanels')->findBy(array('parentPanel' => $panel->getId()));
				if (!empty($children) && count($children) > 0) {
					foreach ($children as $subpanel) {
						$panel->setTitle(trim($xml_request->getElementsByTagName('PanelName')->item(0)->nodeValue));
						$panel->setDescription(trim($xml_request->getElementsByTagName('PanelDesc')->item(0)->nodeValue));
						$panel->setLayoutName(trim($xml_request->getElementsByTagName('PanelLayout')->item(0)->nodeValue));
					}
					$em->flush();
				}
			}

			$result = new \DOMDocument('1.0', 'UTF-8');
			$root = $result->appendChild($result->createElement('Results'));
			$newnode = $result->createElement('Result', 'OK');
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			$newnode = $result->createElement('Result', $panel->getId());
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Ret1';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
				
			return $result;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Remove the selected widget
	 * 
	 * @param \DOMDocument $xml_request
	 * @return \DOMDocument|boolean
	 */
	private function dashboardREMOVEWIDGET($xml_request)
	{
		$em = $this->getDoctrine()->getManager();
		$panel	= trim($xml_request->getElementsByTagName('PanelKey')->item(0)->nodeValue);
		$box_no	= trim($xml_request->getElementsByTagName('BoxNumber')->item(0)->nodeValue);
		try {
			$user_panel_widget = $em->getRepository('DocovaBundle:PanelWidgets')->findOneBy(array('panel' => $panel, 'boxNo' => $box_no));
			if (empty($user_panel_widget)) 
			{
				throw new \Exception('Selected box in the panel could not be found.');
			}
			
			$em->remove($user_panel_widget);
			$em->flush();
			
			$result = new \DOMDocument('1.0', 'UTF-8');
			$root = $result->appendChild($result->createElement('Results'));
			$newnode = $result->createElement('Result', 'OK');
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
				
			return $result;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Share a panel with all or some specific ones
	 * 
	 * @param \DOMDocument $xml_request
	 * @return \DOMDocument|boolean
	 */
	private function dashboardSHAREPANEL($xml_request)
	{
		$em = $this->getDoctrine()->getManager();
		$panel = trim($xml_request->getElementsByTagName('PanelKey')->item(0)->nodeValue);
		try {
			$panel = $em->getRepository('DocovaBundle:UserPanels')->findOneBy(array('id' => $panel, 'isWorkspace' => false, 'parentPanel' => NULL));
			if (empty($panel)) {
				throw new \Exception('Unspecified source User Panel.');
			}
			
			$share_type = trim($xml_request->getElementsByTagName('ShareType')->item(0)->nodeValue);
			$share_list = explode(';', trim($xml_request->getElementsByTagName('ShareList')->item(0)->nodeValue));
			$remove_list = explode(';', trim($xml_request->getElementsByTagName('RemoveList')->item(0)->nodeValue));
			$panel->setShareType($share_type == 'Shared' ? 'Shared' : 'Private');
			$panel->setPanelList(trim($xml_request->getElementsByTagName('InPanelList')->item(0)->nodeValue) == 'Yes' ? true : false);
			if (trim($xml_request->getElementsByTagName('ShareType')->item(0)->nodeValue) != 'Shared') {
				$panel->setPanelTab(true);
			}
			$em->flush();
			$em->refresh($panel);
			
			if ($share_type == 'Shared' && !empty($share_list) && count($share_list) > 0) 
			{
				foreach ($share_list as $username) {
					if (false !== $user = $this->findUserAndCreate($username, false)) {
						if ($user_panel = $em->getRepository('DocovaBundle:UserPanels')->findOneBy(array('parentPanel' => $panel->getId(), 'assignedUser' => $user->getId(), 'isWorkspace' => false))) {
							$user_panel->setTrash(false);
							$user_panel->setTitle($panel->getTitle());
							$last_order = $em->getRepository('DocovaBundle:UserPanels')->getUserLastLayoutOrder($user);
							$user_panel->setLayoutOrder($last_order + 1);
							unset($last_order);
						}
						else {
							$user_panel = new UserPanels();
							$user_panel->setCreator($panel->getCreator());
							$user_panel->setAssignedUser($user);
							$user_panel->setTitle($panel->getTitle());
							$user_panel->setDescription($panel->getDescription());
							$user_panel->setLayoutName($panel->getLayoutName());
							$last_order = $em->getRepository('DocovaBundle:UserPanels')->getUserLastLayoutOrder($user);
							$user_panel->setLayoutOrder($last_order + 1);
							unset($last_order);
							$user_panel->setParentPanel($panel);
							$em->persist($user_panel);
															
							if ($panel->getPanelWidgets()->count() > 0) 
							{
								foreach ($panel->getPanelWidgets() as $pw) {
									$panel_widget = new PanelWidgets();
									$panel_widget->setBoxNo($pw->getBoxNo());
									$panel_widget->setPanel($user_panel);
									$panel_widget->setWidget($pw->getWidget());
									$em->persist($panel_widget);
									unset($panel_widget);
								}
							}
							$user_panel = $user = null;
						}
					}
				}
				$em->flush();
			}
			
			if ($share_type != 'Shared' && trim($xml_request->getElementsByTagName('origShareType')->item(0)->nodeValue) == 'Shared') {
				$children = $em->getRepository('DocovaBundle:UserPanels')->findBy(array('parentPanel' => $panel->getId()));
				if (!empty($children) && count($children) > 0) 
				{
					foreach ($children as $subpanel) {
						$em->remove($subpanel);
					}
					$em->flush();
				}
			}
			
			if (!empty($remove_list) && count($remove_list) > 0) 
			{
				$children = $em->getRepository('DocovaBundle:UserPanels')->getUsersChildPanel($remove_list, $panel->getId());
				if (!empty($children) && count($children) > 0) 
				{
					foreach ($children as $subpanel) {
						$em->remove($subpanel);
					}
					$em->flush();
				}
			}

			$result = new \DOMDocument('1.0', 'UTF-8');
			$root = $result->appendChild($result->createElement('Results'));
			$newnode = $result->createElement('Result', 'OK');
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			$newnode = $result->createElement('Result', $panel->getId());
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Ret1';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
				
			return $result;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Share a panel with all or some specific ones
	 * 
	 * @param \DOMDocument $xml_request
	 * @return \DOMDocument|boolean
	 */
	private function dashboardREORDERPANELS($xml_request)
	{
		$new_order = trim($xml_request->getElementsByTagName('TabOrderList')->item(0)->nodeValue);
		$new_order = explode(';', $new_order);
		try {
			if (!empty($new_order) && count($new_order) > 0) {
				$em = $this->getDoctrine()->getManager();
				foreach ($new_order as $index => $pid) 
				{
					$user_panel = $em->getRepository('DocovaBundle:UserPanels')->findOneBy(array('id' => $pid, 'assignedUser' => $this->user ,'isWorkspace' => false));
					if (!empty($user_panel)) {
						$user_panel->setLayoutOrder($index + 1);
					}
				}
				$em->flush();

				$result = new \DOMDocument('1.0', 'UTF-8');
				$root = $result->appendChild($result->createElement('Results'));
				$newnode = $result->createElement('Result', 'OK');
				$attrib = $result->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
				
				return $result;
			}
			else {
				throw new \Exception('Panels order is missed.');
			}
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Add a panel from add list
	 * 
	 * @param \DOMDocument $xml_request
	 * @return \DOMDocument|boolean
	 */
	private function dashboardADDPANEL($xml_request)
	{
		$panel = trim($xml_request->getElementsByTagName('PanelID')->item(0)->nodeValue);
		$em = $this->getDoctrine()->getManager();
		try {
			$panel = $em->getRepository('DocovaBundle:UserPanels')->findOneBy(array('id' => $panel, 'isWorkspace' => false, 'parentPanel' => NULL));
			if (empty($panel)) {
				throw new \Exception('Unspecified source user panel.');
			}

			if ($user_panel = $em->getRepository('DocovaBundle:UserPanels')->findRemovedPanel($panel->getId(), $this->user->getId()))
			{
				$user_panel->setTrash(false);
				$last_order = $em->getRepository('DocovaBundle:UserPanels')->getUserLastLayoutOrder($this->user);
				$user_panel->setLayoutOrder($last_order + 1);
				unset($last_order);
				if (!$user_panel->getAssignedUser()) {
					$user_panel->setAssignedUser($this->user);
				}
				$em->flush();
			}
			else {
				$user_panel = new UserPanels();
				$user_panel->setCreator($panel->getCreator());
				$user_panel->setAssignedUser($this->user);
				$user_panel->setTitle($panel->getTitle());
				$user_panel->setDescription($panel->getDescription());
				$user_panel->setLayoutName($panel->getLayoutName());
				$user_panel->setPanelList($panel->getPanelList());
	//			$user_panel->setShareType($panel->getShareType());
				$last_order = $em->getRepository('DocovaBundle:UserPanels')->getUserLastLayoutOrder($this->user);
				$user_panel->setLayoutOrder($last_order + 1);
				unset($last_order);
				$user_panel->setParentPanel($panel);
				$em->persist($user_panel);
				$em->flush();
				
				if ($panel->getPanelWidgets()->count() > 0) 
				{
					foreach ($panel->getPanelWidgets() as $pw) {
						$panel_widget = new PanelWidgets();
						$panel_widget->setBoxNo($pw->getBoxNo());
						$panel_widget->setPanel($user_panel);
						$panel_widget->setWidget($pw->getWidget());
						$em->persist($panel_widget);
					}
					$em->flush();
				}
				unset($user_panel, $user, $panel_widget, $pw);
			}

			$result = new \DOMDocument('1.0', 'UTF-8');
			$root = $result->appendChild($result->createElement('Results'));
			$newnode = $result->createElement('Result', 'OK');
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			
			return $result;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Take out (remove) the panel from current user
	 * 
	 * @param \DOMDocument $xml_request
	 * @return \DOMDocument|boolean
	 */
	public function dashboardREMOVEPANEL($xml_request)
	{
		$panel = $xml_request->getElementsByTagName('PanelID')->item(0)->nodeValue;
		$delete_option = $xml_request->getElementsByTagName('DeleteOption')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		try {
			$panel = $em->getRepository('DocovaBundle:UserPanels')->findOneBy(array('id' => $panel, 'assignedUser' => $this->user, 'isWorkspace' => false));
			if (empty($panel)) {
				throw new \Exception('Unspecified source panel.');
			}
			
			if ($delete_option === 'Remove') 
			{
				$panel->setLayoutOrder(0);
				$panel->setAssignedUser(null);
				$panel->setTrash(true);
			}
			elseif ($delete_option === 'Delete') {
				if ($panel->getParentPanel()) {
					$panel->setLayoutOrder(0);
					$panel->setTrash(true);
				}
				else {
					$children = $em->getRepository('DocovaBundle:UserPanels')->findBy(array('parentPanel' => $panel->getId(), 'isWorkspace' => false));
					if (!empty($children) && count($children) > 0) 
					{
						foreach ($children as $subpanel) {
							$em->remove($subpanel);
						}
						$em->flush();
					}
					$em->remove($panel);
				}
			}
			$em->flush();

			$result = new \DOMDocument('1.0', 'UTF-8');
			$root = $result->appendChild($result->createElement('Results'));
			$newnode = $result->createElement('Result', 'OK');
			$attrib = $result->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			
			return $result;
		}
		catch (\Exception $e) {
			return false;
		}
	}
}
