<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Docova\DocovaBundle\Entity\UserWorkspace;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Entity\Libraries;
use Docova\DocovaBundle\Entity\LibraryGroups;
use Docova\DocovaBundle\Extensions\CopyDesignServices;
use Docova\DocovaBundle\Entity\AppAcl;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
//use Docova\DocovaBundle\Entity\UserRoles;
use Docova\DocovaBundle\Extensions\CopyLibraryServices;
//use Docova\DocovaBundle\Entity\UserLibrariesGroups;
use Docova\DocovaBundle\Entity\UserRecentApps;
use Docova\DocovaBundle\Entity\UserAppGroups;
use Docova\DocovaBundle\Entity\AppGroupsContent;
use Docova\DocovaBundle\Entity\UserPanels;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Workspace controller
 * @author Javad Rahimi
 */
class WorkspaceController extends Controller
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
		
	public function openWsFrameAction()
	{
		return $this->render('DocovaBundle:Workspace:wWorkspaceFrame.html.twig');
	}
	
	public function workspaceOptionsAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Workspace:wWorkspaceOptions.html.twig', array('user' => $this->user));
	}
	
	public function createDefaultWorkspace(){
	    $this->initialize();
	    
	    $html = <<<HTML
<div id="UserName">{$this->user->getUserNameDnAbbreviated()}</div>
<div id="DefaultOpenApp"></div>
<div id="PinnedTabs"></div>
<div id="WorkspaceHTML"><div class="panel col-10 active" pid="1" panelname="First Panel">
<div class="app-wrapper">
<div class="app-container" pid="1" aid="1">
	<div id="Dashboard" class="app draggable" apptype="SA" title="DOCOVA User Dashboard" apptitle="Dashboard" appdesc="DOCOVA User Dashboard" appicon="fa-chart-area" appiconcolor="#5C87C6" dockey="">
	<div class="app-icon-box">
		<div class="app-icon-wrapper"><i class="app-icon far fa-chart-area" style="color:#5C87C6;"></i></div>
	</div>
	<div class="app-title ui-widget">Dashboard</div>
	</div>
</div>
<div class="app-container" pid="1" pid="2">
	<div id="Libraries" class="app draggable" apptype="SA" title="DOCOVA Library Repository" apptitle="Libraries" appdesc="DOCOVA Library Repository" appicon="fa-university" appiconcolor="#5C87C6" dockey="">
	<div class="app-icon-box">
		<div class="app-icon-wrapper"><i class="app-icon far fa-university" style="color:#5C87C6;"></i></div>
	</div>
	<div class="app-title ui-widget">Libraries</div>
	</div>
</div>
<div class="app-container" pid="1" pid="3">
	<div id="Search" class="app draggable" apptype="SA" title="DOCOVA Search Libraries" apptitle="Search" appdesc="DOCOVA Search Libraries" appicon="fa-search" appiconcolor="#5C87C6" dockey="">
	<div class="app-icon-box">
		<div class="app-icon-wrapper"><i class="app-icon far fa-search" style="color:#5C87C6;"></i></div>
	</div>
	<div class="app-title ui-widget">Search</div>
	</div>
</div>
<div class="app-container droppable" pid="1" pid="4"></div>
<div class="app-container droppable" pid="1" pid="5"></div>
<div class="app-container droppable" pid="1" pid="6"></div>
<div class="app-container droppable" pid="1" pid="7"></div>
<div class="app-container droppable" pid="1" pid="8"></div>
<div class="app-container droppable" pid="1" pid="9"></div>
<div class="app-container droppable" pid="1" pid="10"></div>
<div class="app-container droppable" pid="1" pid="11"></div>
<div class="app-container droppable" pid="1" pid="12"></div>
<div class="app-container droppable" pid="1" pid="13"></div>
<div class="app-container droppable" pid="1" pid="14"></div>
<div class="app-container droppable" pid="1" pid="15"></div>
<div class="app-container droppable" pid="1" pid="16"></div>
<div class="app-container droppable" pid="1" pid="17"></div>
<div class="app-container droppable" pid="1" pid="18"></div>
</div></div></div>
HTML;
	    
	    $em = $this->getDoctrine()->getManager();
	    $workspace = new UserWorkspace();
	    $workspace->setUser($this->user);
	    $workspace->setWorkspaceHTML($html);
	    $em->persist($workspace);
	    $em->flush();
	    
        return $workspace;
	}
	
	public function showWorkspaceAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$workspace = $em->getRepository('DocovaBundle:UserWorkspace')->findOneBy(array('user' => $this->user->getId()));
		if (empty($workspace))
		{
            $workspace = $this->createDefaultWorkspace();
            if (empty($workspace)) {
                throw $this->createNotFoundNotification('Unable to create default workspace.');
            }
		}

		return $this->render('DocovaBundle:Workspace:wWorkspace.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'workspace' => $workspace
		));
	}
	
	public function loadWorkspaceAction($wkey)
	{
        $workspace = '';
        
		$em = $this->getDoctrine()->getManager();
		if ($wkey !== 'BlankPanel') {
			$workspace = $em->getRepository('DocovaBundle:UserWorkspace')->find($wkey);
			if (empty($workspace)) {
			    if (empty($workspace)) {
			        $workspace = $this->createDefaultWorkspace();
			        if (empty($workspace)) {
			            throw $this->createNotFoundException('Unable to create workspace record.');
			        }
			    }
			}
		}else{
		    $workspace = <<<HTML
<div id="WorkspaceHTML"><div class="panel col-10" pid=9999 panelname="New Panel">
<div class="app-wrapper">
<div class="app-container droppable" pid=0 aid=1></div>
<div class="app-container droppable" pid=0 aid=2></div>
<div class="app-container droppable" pid=0 aid=3></div>
<div class="app-container droppable" pid=0 aid=4></div>
<div class="app-container droppable" pid=0 aid=5></div>
<div class="app-container droppable" pid=0 aid=6></div>
<div class="app-container droppable" pid=0 aid=7></div>
<div class="app-container droppable" pid=0 aid=8></div>
<div class="app-container droppable" pid=0 aid=9></div>
<div class="app-container droppable" pid=0 aid=10></div>
<div class="app-container droppable" pid=0 aid=11></div>
<div class="app-container droppable" pid=0 aid=12></div>
<div class="app-container droppable" pid=0 aid=13></div>
<div class="app-container droppable" pid=0 aid=14></div>
<div class="app-container droppable" pid=0 aid=15></div>
<div class="app-container droppable" pid=0 aid=16></div>
<div class="app-container droppable" pid=0 aid=17></div>
<div class="app-container droppable" pid=0 aid=18></div>
</div></div></div>
HTML;
		}
		
		$response = new Response();
		$response->setContent(\is_string($workspace) ? $workspace : $workspace->getWorkspaceHTML());
		return $response;
	}
	

	public function openPanelPropertiesAction()
	{
		return $this->render('DocovaBundle:Workspace:dlgWSPanelProperties.html.twig');
	}
	
	public function openDlgAddAppAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Workspace:dlgAddApp.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function openCreateAppAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Workspace:dlgCreateApp.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function openCreateAppGroupAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$apps_list = $em->getRepository('DocovaBundle:Libraries')->findBy(['Trash' => false], ['Library_Title' => 'ASC']);
		$lib_groups = $em->getRepository('DocovaBundle:LibraryGroups')->findAll();
		return $this->render('DocovaBundle:Workspace:dlgCreateAppGroup.html.twig', array(
			'user' => $this->user,
			'allapps' => $apps_list,
			'libgroups' => $lib_groups
		));
	}
	
	public function manageAppGroupsAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$apps_list = $em->getRepository('DocovaBundle:Libraries')->findBy(['Trash' => false], ['Library_Title' => 'ASC']);
		$lib_groups = $em->getRepository('DocovaBundle:LibraryGroups')->findAll();
		$user_appgroups = $em->getRepository('DocovaBundle:UserAppGroups')->getUserAppGroups($this->user->getId());
		return $this->render('DocovaBundle:Workspace:dlgCreateAppGroup.html.twig', array(
			'user' => $this->user,
			'allapps' => $apps_list,
			'libgroups' => $lib_groups,
			'user_appgroups' => $user_appgroups,
			'is_edit' => true
		));
	}
	
	public function openAppPropertiesAction()
	{
	    $this->initialize();
	    return $this->render('DocovaBundle:Workspace:dlgAppProperties.html.twig', array(
	        'user' => $this->user
	    ));
	}
	
	public function openAboutWorkspaceDlgAction()
	{
		return $this->render('DocovaBundle:Workspace:dlgAboutWorkspace.html.twig');
	}
	
	public function openCopyAppDlgAction()
	{
	    $this->initialize();
	    return $this->render('DocovaBundle:Workspace:dlgCopyApp.html.twig', array('user' => $this->user));
	}
	
	public function appDataXmlAction(Request $request)
	{
		$response = new Response();
		$type = $request->query->get('apptype');
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$this->initialize();
		switch ($type) {
			case 'applibs':
				$em = $this->getDoctrine()->getManager();
				$libraries = $em->getRepository('DocovaBundle:Libraries')->findBy(array('Trash' => false, 'isApp' => false), array('Library_Title' => 'ASC'));
				$securityContext = $this->get('security.authorization_checker');
				$xml .= '<Apps>';
				$access = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? 7 : 3;
				foreach ($libraries as $library) {
					if ($securityContext->isGranted('VIEW', $library) === true) 
					{
						if ($access < 7) {
							$roles = $this->user->getRoles();
							foreach ($roles as $r) {
								if ($r == 'ROLE_LIBADMIN'.$library->getId()) {
									$access = 6;
									break;
								}
							}
						}
						$xml .= "<App><Title><![CDATA[{$library->getLibraryTitle()}]]></Title><Server>{$request->getHttpHost()}</Server>";
						$xml .= '<NsfName>'.substr($this->generateUrl('docova_homepage'), 0, -1).'</NsfName>';
						$xml .= "<Description><![CDATA[{$library->getDescription()}]]></Description><IsApp>0</IsApp>";
						$xml .= "<AppIcon>{$library->getAppIcon()}</AppIcon><AppIconColor>{$library->getAppIconColor()}</AppIconColor>";
						$xml .= "<DocKey>{$library->getId()}</DocKey><Unid>{$library->getId()}</Unid>";
						$xml .= "<AccessLevel>{$access}</AccessLevel>";
						$xml .= "<Selected>0</Selected><Modified>0</Modified><DocAuthors><![CDATA[Administrators]]></DocAuthors>";
						$xml .= '</App>';
					}
				}
				$xml .= '</Apps>';
				break;
			case 'librarygroups':
				$em = $this->getDoctrine()->getManager();
				$libgroups = $em->getRepository('DocovaBundle:LibraryGroups')->findAll();
				$xml .= '<Apps>';
				$access = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? 7 : 3;
				foreach ($libgroups as $group) {
					if ($access < 7 && $group->getCreatedBy()->getId() == $this->user->getId()) {
						$access = 6;
					}
					$xml .= "<App><Title><![CDATA[{$group->getGroupTitle()}]]></Title>";
					$xml .= "<Description><![CDATA[{$group->getGroupDescription()}]]></Description><IsApp>0</IsApp>";
					$xml .= "<AppIcon>{$group->getGroupIcon()}</AppIcon><AppIconColor>{$group->getGroupIconColor()}</AppIconColor>";
					$xml .= "<DocKey>{$group->getId()}</DocKey><Unid>{$group->getId()}</Unid><Selected>0</Selected><Modified>0</Modified>";
					$xml .= "<AccessLevel>{$access}</AccessLevel>";
					$xml .= "<DocAuthors></DocAuthors></App>";
				}
				$xml .= '</Apps>';
				break;
			case 'appsys':
				$xml .= '<Apps>';
				$this->initialize();
				$em = $this->getDoctrine()->getManager();
				$libraries = $em->getRepository('DocovaBundle:Libraries')->createQueryBuilder('L')
					->select('L.id')
					->where('L.Trash = false')
					->andWhere('L.isApp = false')
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
				$securityContext = $this->get('security.authorization_checker');
				if ($isLibraryAdmin === true || $securityContext->isGranted('ROLE_ADMIN')) {
					$xml .= '<App><Title><![CDATA[Admin]]></Title><Server></Server><NsfName></NsfName><Description><![CDATA[DOCOVA Administration]]></Description><AppIcon>far fa-street-view</AppIcon><AppIconColor>#5C87C6</AppIconColor><DocKey></DocKey><Unid>Admin</Unid><Selected>0</Selected><Modified>0</Modified><DocAuthors></DocAuthors></App>';
					$xml .= '<App><Title><![CDATA[AppBuilder]]></Title><Server></Server><NsfName></NsfName><Description><![CDATA[DOCOVA Application Builder]]></Description><AppIcon>far fa-cog</AppIcon><AppIconColor>#5C87C6</AppIconColor><DocKey></DocKey><Unid>AppBuilder</Unid><Selected>0</Selected><Modified>0</Modified><DocAuthors></DocAuthors></App>';
				}
				$xml .= '<App><Title><![CDATA[Dashboard]]></Title><Server></Server><NsfName></NsfName><Description><![CDATA[DOCOVA User Dashboard --]]></Description><AppIcon>far fa-chart-area</AppIcon><AppIconColor>#5C87C6</AppIconColor><DocKey></DocKey><Unid>Dashboard</Unid><Selected>0</Selected><Modified>0</Modified><DocAuthors></DocAuthors></App>';
				if ($isLibraryAdmin === true || $securityContext->isGranted('ROLE_ADMIN')) {
					$xml .= '<App><Title><![CDATA[Designer]]></Title><Server></Server><NsfName></NsfName><Description><![CDATA[DOCOVA Form Designer]]></Description><AppIcon>far fa-cogs</AppIcon><AppIconColor>#5C87C6</AppIconColor><DocKey></DocKey><Unid>Designer</Unid><Selected>0</Selected><Modified>0</Modified><DocAuthors></DocAuthors></App>';
				}
				$securityContext = $libraries = $roles = $libid = $isLibraryAdmin = null;
				$xml .= '<App><Title><![CDATA[Libraries]]></Title><Server></Server><NsfName></NsfName><Description><![CDATA[DOCOVA Library Repository]]></Description><AppIcon>fas fa-university</AppIcon><AppIconColor>#5C87C6</AppIconColor><DocKey></DocKey><Unid>Libraries</Unid><Selected>0</Selected><Modified>0</Modified><DocAuthors></DocAuthors></App><App><Title><![CDATA[Search]]></Title><Server></Server><NsfName></NsfName><Description><![CDATA[DOCOVA Federated Search]]></Description><AppIcon>fa-search</AppIcon><AppIconColor>#5C87C6</AppIconColor><DocKey></DocKey><Unid>Search</Unid><Selected>0</Selected><Modified>0</Modified><DocAuthors></DocAuthors></App></Apps>';
				break;
			case 'templates':
				$em = $this->getDoctrine()->getManager();
				$applications = $em->getRepository('DocovaBundle:Libraries')->findBy(array('Trash' => false, 'isApp' => true, 'Is_Template' => true), array('Library_Title' => 'ASC'));
				$xml .= '<Apps>';
				foreach ($applications as $app) {
					$xml .= "<App><Title><![CDATA[{$app->getLibraryTitle()}]]></Title><Server>{$request->getHttpHost()}</Server>";
					$xml .= '<NsfName>'.substr($this->generateUrl('docova_homepage'), 0, -1).'</NsfName>';
					$xml .= "<Description><![CDATA[{$app->getDescription()}]]></Description><IsApp>1</IsApp>";
					$xml .= "<AppIcon>{$app->getAppIcon()}</AppIcon><AppIconColor>{$app->getAppIconColor()}</AppIconColor>";
					$xml .= "<DocKey>{$app->getId()}</DocKey><Unid>{$app->getId()}</Unid>";
					$xml .= "<Selected>0</Selected><Modified>0</Modified><DocAuthors><![CDATA[Administrators]]></DocAuthors>";
					$xml .= '</App>';
				}
				$xml .= '</Apps>';
				break;
			default:
				$em = $this->getDoctrine()->getManager();
				$applications = $em->getRepository('DocovaBundle:Libraries')->findBy(array('Trash' => false, 'isApp' => true), array('Library_Title' => 'ASC'));
				$xml .= '<Apps>';
				foreach ($applications as $app) {
					$xml .= "<App><Title><![CDATA[{$app->getLibraryTitle()}]]></Title><Server>{$request->getHttpHost()}</Server>";
					$xml .= '<NsfName>'.substr($this->generateUrl('docova_homepage'), 0, -1).'</NsfName>';
					$xml .= "<Description><![CDATA[{$app->getDescription()}]]></Description><IsApp>1</IsApp>";
					$xml .= "<AppIcon>{$app->getAppIcon()}</AppIcon><AppIconColor>{$app->getAppIconColor()}</AppIconColor>";
					$xml .= "<DocKey>{$app->getId()}</DocKey><Unid>{$app->getId()}</Unid>";
					$xml .= "<Selected>0</Selected><Modified>0</Modified><DocAuthors><![CDATA[Administrators]]></DocAuthors>";
					$xml .= '</App>';
				}
				$xml .= '</Apps>';
				break;
		}
		$response->setContent($xml);
		return $response;
	}
	
	public function openApplicationAction(Request $request, $appid)
	{
		$output = array();
		$em = $this->getDoctrine()->getManager();
		$type = $request->query->get('apptype');
		if ($type == 'LG') {
			$group = $em->getRepository('DocovaBundle:LibraryGroups')->find($appid);
			if (!empty($group)) 
			{
				$output['Status'] = 'OK';
				$output['Properties'] = array(
						'title' => $group->getGroupTitle(),
						'description' => $group->getGroupDescription(),
						'appicon' => $group->getGroupIcon(),
						'appiconcolor' => $group->getGroupIconColor(),
						'appwebpath' => substr($this->generateUrl('docova_homepage'), 0, -1),
						'isapp' => false
				);
			}
			else {
				throw $this->createNotFoundException('Unspecified applicatoin source.');
			}
		}
		else {
			$library = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appid, 'Trash' => false));
			if (!empty($library))
			{
				$output['Status'] = 'OK';
				$output['Properties'] = array(
					'title' => $library->getLibraryTitle(),
					'description' => $library->getDescription(),
					'appicon' => $library->getAppIcon(),
					'appiconcolor' => $library->getAppIconColor(),
					'appwebpath' => substr($this->generateUrl('docova_homepage'), 0, -1),
					'isapp' => $library->getIsApp()
				);
			}
			else {
				throw $this->createNotFoundException('Unspecified applicatoin source.');
			}
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'applicatoin/json');
		$response->setContent(\json_encode($output));
		return $response;
	}
	
	public function appLoaderAction(Request $request, $appid)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appid, 'isApp' => true, 'Trash' => false));
		if (empty($app)) {
			throw $this->createNotFoundException('Unspecified source application ID.');
		}
		
		$layoutid = $request->query->get('Layout');
		if (empty($layoutid)) {
			$layout = $em->getRepository('DocovaBundle:AppLayout')->findOneBy(array('application' => $appid, 'layoutDefault' => true));
		}
		else {
			$layout = $em->getRepository('DocovaBundle:AppLayout')->findOneBy(['application' => $appid, 'id' => $layoutid]);
		}
		//opcache_reset();
		return $this->render('DocovaBundle:Workspace:appLoader.html.twig', array(
			'application' => $app,
			'settings' => $this->global_settings,
			'user' => $this->user,
			'layout' => $layout
		));
	}

	public function emptyContentAction()
	{
		return $this->render('DocovaBundle:Workspace:emptyContent.html.twig');
	}
	
	public function workspaceServicesAction(Request $request)
	{
		$request = $request->getContent();
		$xml = new \DOMDocument();
		$xml->loadXML($request);
		$action = $xml->getElementsByTagName('Action')->item(0)->nodeValue;
		$this->initialize();
		if (!empty($action) && method_exists($this, "workspace$action")) 
		{
			$results = call_user_func(array($this, "workspace$action"), $xml);
		}
		
		if (empty($results) || !$results instanceof \DOMDocument) 
		{
			$status = empty($results) ? null : $results;
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
				$newnode = $results->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact system administrator for help.');
			}
			$attrib = $results->createAttribute('ID');
			$attrib->value = 'ErrMsg';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
		}

		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($results->saveXML());
		return $response;
	}
	
	public function openDlgLibraryGroupsAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Workspace:dlgManageLibraryGroup.html.twig', array('user' => $this->user));
	}
	
	public function getLibraryGroupAction(Request $request)
	{
		$libgroup = $request->query->get('libgroupid');
		$xml = '<?xml version="1.0" encoding="UTF-8"?><Libraries>';
		if (!empty($libgroup)) 
		{
			$em = $this->getDoctrine()->getManager();
			$libgroup = $em->getRepository('DocovaBundle:LibraryGroups')->find($libgroup);
			if (!empty($libgroup)) 
			{
				$libraries = $libgroup->getLibraries();
				if ($libraries->count())
				{
					foreach ($libraries as $lib) {
						$xml .= "<Library><Title>{$lib->getLibraryTitle()}</Title>";
						$xml .= "<Description><![CDATA[{$lib->getDescription()}]]></Description>";
						$xml .= "<DocKey>{$lib->getId()}</DocKey>";
						$xml .= "<Unid>{$lib->getId()}</Unid>";
						$xml .= '<Selected>0</Selected></Library>';
					}
				}
			}
		}
		$xml .= '</Libraries>';
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml);
		return $response;
	}
	
	public function getTemplateDataAction()
	{
		$xml = '<?xml version="1.0" encoding="UTF-8"?><Apps><App><Title>-Custom-</Title><Server></Server><NsfName></NsfName><Description>New empty application</Description><AppIcon>far fa-cogs</AppIcon><AppIconColor>#5C87C6</AppIconColor><DocKey></DocKey><Unid></Unid><NotesNsfName></NotesNsfName><IsApp>1</IsApp></App>';
		$xml .= '<App><Title>-Library-</Title><Server></Server><NsfName></NsfName><Description>New DOCOVA Library</Description><AppIcon>fas fa-university</AppIcon><AppIconColor>#ff6600</AppIconColor><DocKey></DocKey><Unid></Unid><NotesNsfName></NotesNsfName><IsApp>0</IsApp></App>';
		$xml .= '<App><Title>-Library Group-</Title><Server></Server><NsfName></NsfName><Description>New DOCOVA Library Group</Description><AppIcon>far fa-object-group</AppIcon><AppIconColor>#ff6600</AppIconColor><DocKey></DocKey><Unid></Unid><NotesNsfName></NotesNsfName><IsApp>0</IsApp></App>';
		$templates = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Libraries')->findBy(['Trash' => false, 'Status' => true, 'Is_Template' => true, 'isApp' => true], ['Library_Title' => 'ASC']);
		if (!empty($templates))
		{
			foreach ($templates as $temp) {
				$xml .= "<App><Title>{$temp->getLibraryTitle()}</Title><Server></Server><NsfName></NsfName>";
				$xml .= "<Description>{$temp->getDescription()}</Description><AppIcon>{$temp->getAppIcon()}</AppIcon><AppIconColor>{$temp->getAppIconColor()}</AppIconColor>";
				$xml .= "<DocKey>{$temp->getId()}</DocKey><Unid>{$temp->getId()}</Unid><NotesNsfName></NotesNsfName>";
				$xml .= '<IsApp>'.($temp->getIsApp() ? 1 : 0).'</IsApp></App>';
			}
		}
		else {
			//$xml .= '<h2>No documents found</h2>';
		}
		$xml .= '</Apps>';

		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml);
		return $response;
	}
	
	public function openDocovaWorkspaceAction(Request $request)
	{
		$sList = '';
		$this->initialize();
		$params = $this->computeWorkspaceParams();
		$em = $this->getDoctrine()->getManager();
		$workspace = $em->getRepository('DocovaBundle:UserWorkspace')->findOneBy(array('user' => $this->user->getId()));
		if (empty($workspace))
		{
            $workspace = $this->createDefaultWorkspace();
		}
		$panel = $em->getRepository('DocovaBundle:UserPanels')->findOneBy(['assignedUser' => $this->user->getId(), 'trash' => false, 'isWorkspace' => true]);
		
		if (empty($panel))
		{
			$panel  = new UserPanels();
			$panel->setAssignedUser($this->user);
			$panel->setCreator($this->user);
			$panel->setDescription('User default workspace panel.');
			$panel->setIsWorkspacet(true);
			$panel->setLayoutOrder(1);
			$panel->setTitle('Workspace Panel');
			$em->persist($panel);
			$em->flush();
		}
		
		return $this->render('DocovaBundle:Workspace:wDocovaWorkspace.html.twig', [
			'user' => $this->user,
			'appgroups' => $params['appgroups'],
			'applications' => $params['applications'],
			'isLibAdmin' => $params['isLibAdmin'],
			'recentapps' => $params['recentapps'],
			'templates' => $params['templates'],
			'panel' => $panel,
			'shareList' => $sList
		]);
	}
	
	public function searchAppAction(Request $request)
	{
		$is_load_more = false;
		$search_for = urldecode($request->request->get('searchtxt'));
		if (!empty($search_for))
		{
			$offset = $request->request->get('offset');
			$em = $this->getDoctrine()->getManager();
			if ($search_for !== '*')
			{
				$apps = $em->getRepository('DocovaBundle:Libraries')->searchApp($search_for, $offset);
				if (!empty($apps))
				{
					foreach ($apps as $index => $app) {
					    $access = $this->getAppAccessLevel($app);
					    if($access == 0){
					        unset($apps[$index]);
					    }else{
					    	$apps[$index] = ['app' => $app, 'access' => $access, 'isApp' => $app->getIsApp(), 'apptype' => ($app->getIsApp() ? 'A' : 'L')];
					    }		
					}
				}
			}
			else {
				$is_load_more = true;
				$apps = $em->getRepository('DocovaBundle:Libraries')->getAllAppLibGroups($offset);
				if (!empty($apps))
				{
					$this->initialize();
					$access = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? 7 : 3;
					$appcount = count($apps);
					for ($x = 0; $x < $appcount; $x++) {
						//if it's library group
						if (!$apps[$x]['isapp'] && !empty($apps[$x]['creator'])) {
							if ($access < 7)
							{
								$access = 3;
								$access = $apps[$x]['creator'] == $this->user->getId() ? 6 : $access;
							}
							$apps[$x] = ['app' => $apps[$x], 'access' => $access, 'isApp' => false, 'apptype' => 'LG'];
						}
						//if it's a library
						elseif (!$apps[$x]['isapp'] && is_null($apps[$x]['creator'])) {
							$app = $em->getReference('DocovaBundle:Libraries', $apps[$x]['id']);
							$app->setIsApp(false);
							$access = $this->getAppAccessLevel($app);
							if($access == 0){
							    unset($apps[$x]);
							}else{
							    $apps[$x] = ['app' => $apps[$x], 'access' => $access, 'isApp' => false, 'apptype' => 'L'];
							}
						}
						//if it's an app
						else {
							$app = $em->getReference('DocovaBundle:Libraries', $apps[$x]['id']);
							$app->setIsApp(true);
							$access = $this->getAppAccessLevel($app);
							if($access == 0){
							    unset($apps[$x]); 
							}else{
							    $apps[$x] = ['app' => $apps[$x], 'access' => $access, 'isApp' => true, 'apptype' => 'A'];
							}
						}
					}
				}
			}
		}
		return $this->render('DocovaBundle:Workspace:workspaceSearchAppResult.html.twig', [
			'applications' => $apps,
			'isloadmore' => $is_load_more
		]);
	}
	
	
	public function getAppsAction(Request $request)
	{  
        $em = $this->getDoctrine()->getManager();
        $server = $request->getHttpHost();
        $homeurl = substr($this->generateUrl('docova_homepage'), 0, -1);
        
        $xml_obj = new \DOMDocument("1.0", "UTF-8");
        $root = $xml_obj->appendChild($xml_obj->createElement('Libraries'));
        
	    $apps = $em->getRepository('DocovaBundle:Libraries')->getAllAppLibGroups(0,0);
	    if (!empty($apps))
	    {
            $this->initialize();
	        $access = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? 7 : 3;
	        for ($x = 0; $x < count($apps); $x++) {
	           //if it's library group
	           if (!$apps[$x]['isapp'] && !empty($apps[$x]['creator'])) 
	           {
                    if ($access < 7)
	                {
	                     $access = 3;
	                     $access = $apps[$x]['creator'] == $this->user->getId() ? 6 : $access;
	                }
	                $apps[$x] = ['app' => $apps[$x], 'access' => $access, 'community' => '', 'realm' => '', 'ldaf' => '', 'appicon' => $app->getAppIcon(), 'appiconcolor' => $app->getAppIconColor(), 'launchtype' => '', 'launchid' => '', 'type' => 'LG'];
	           }
	           //if it's a library
	           elseif (!$apps[$x]['isapp'] && is_null($apps[$x]['creator'])) 
	           {
	                $app = $em->getReference('DocovaBundle:Libraries', $apps[$x]['id']);
	                $app->setIsApp(false);
	                $apps[$x] = ['app' => $apps[$x], 'access' => $this->getAppAccessLevel($app), 'community' => $app->getCommunity(), 'realm' => $app->getRealm(), 'ldaf' => $app->getLoadDocsAsFolders(), 'appicon' => $app->getAppIcon(), 'appiconcolor' => $app->getAppIconColor(),  'launchtype' => '', 'launchid' => '', 'type' => 'L'];
	           }
	           //if it's an app
	           else 
	           {
	                 $app = $em->getReference('DocovaBundle:Libraries', $apps[$x]['id']);
	                 $app->setIsApp(true);
	                 $mlt = $app->getMobileLaunchType();
	                 $mlid = $app->getMobileLaunchId();
	                 $apps[$x] = ['app' => $apps[$x], 'access' => $this->getAppAccessLevel($app), 'community' => '', 'realm' => '', 'ldaf' => '', 'appicon' => $app->getAppIcon(), 'appiconcolor' => $app->getAppIconColor(),  'launchtype' => ($mlt ? strtolower($mlt) : ''), 'launchid' => ($mlid ? $mlid : ''), 'type' => 'A'];
	           }
	        }
	    }

	    for ($x = 0; $x < count($apps); $x++) {
	        if( $apps[$x]['access'] > 0){
	           	            
	            $lib_child = $root->appendChild($xml_obj->createElement('Library'));
	            
	            $CData = $xml_obj->createCDATASection($server);
	            $child = $xml_obj->createElement('Server');
	            $child->appendChild($CData);
	            $lib_child->appendChild($child);
	            $lib_child->appendChild($xml_obj->createElement('NsfName', $homeurl));
	            
	            $CData = $xml_obj->createCDATASection($apps[$x]['app']['title']);
	            $child = $xml_obj->createElement('Title');
	            $child->appendChild($CData);
	            $lib_child->appendChild($child);
	            
	            $CData = $xml_obj->createCDATASection($apps[$x]['app']['dscp']);
	            $child = $xml_obj->createElement('Description');
	            $child->appendChild($CData);
	            $lib_child->appendChild($child);
	            
	            $lib_child->appendChild($xml_obj->createElement('DocKey', $apps[$x]['app']['id']));
	            $lib_child->appendChild($xml_obj->createElement('Unid', $apps[$x]['app']['id']));
	            
	            $CData = $xml_obj->createCDATASection($apps[$x]['community']);
	            $child = $xml_obj->createElement('Community');
	            $child->appendChild($CData);
	            $lib_child->appendChild($child);
	            
	            $CData = $xml_obj->createCDATASection($apps[$x]['realm']);
	            $child = $xml_obj->createElement('Realm');
	            $child->appendChild($CData);
	            $lib_child->appendChild($child);
	            
	            $lib_child->appendChild($xml_obj->createElement('LoadDocsAsFolders', (int)$apps[$x]['ldaf']));
	            
	            $lib_child->appendChild($xml_obj->createElement('Type', $apps[$x]['type']));
	            
	            $lib_child->appendChild($xml_obj->createElement('LaunchType', $apps[$x]['launchtype'])); 
	            $lib_child->appendChild($xml_obj->createElement('LaunchID', $apps[$x]['launchid'])); 
	            
	            $lib_child->appendChild($xml_obj->createElement('Selected', 0));
	            $lib_child->appendChild($xml_obj->createElement('Modified', 0));
	            
	            $lib_child->appendChild($xml_obj->createElement('AppIcon', $apps[$x]['appicon']));  
	            $lib_child->appendChild($xml_obj->createElement('AppIconColor', $apps[$x]['appiconcolor']));  
	        }
	    }
	    
	    $response = new Response($xml_obj->saveXML());
	    $response->headers->set('Content-Type', 'text/xml');
	    return $response;
	}
	
	
	public function openChangeLayoutAction()
	{
		return $this->render('DocovaBundle:Workspace:dlgChangeLayout.html.twig');
	}
	
	/**
	 * Workspace save service
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument|string
	 */
	private function workspaceSAVEWORKSPACE($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$workspace = $em->getRepository('DocovaBundle:UserWorkspace')->findOneBy(array('user' => $this->user->getId()));
			if (empty($workspace)) {
			   $workspace = $this->createDefaultWorkspace();
			   if (empty($workspace)) {
			       throw $this->createNotFoundException('Unable to create workspace record.');
			   }
			}
			$html = '<div id="WorkspaceHTML">' . $post->getElementsByTagName('WorkspaceHTML')->item(0)->nodeValue . '</div>';
			$workspace->setWorkspaceHTML($html);
			$em->flush();
			
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $workspace->getDefaultOpenApp());
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		
		return $output;
	}
	
	/**
	 * Pin tab(s) workspace service
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument|string
	 */
	private function workspacePINTABS($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$workspace = $em->getRepository('DocovaBundle:UserWorkspace')->findOneBy(array('user' => $this->user->getId()));
			if (empty($workspace)) {
			    $workspace = $this->createDefaultWorkspace();
			    if (empty($workspace)) {
			        throw $this->createNotFoundException('Unable to create workspace record.');
			    }
			}
			
			$pinned_tab = $post->getElementsByTagName('TabParams')->item(0)->nodeValue;
			$default_tab = $post->getElementsByTagName('TabSelected')->item(0)->nodeValue;
			$workspace->setPinnedTabs($pinned_tab);
			$workspace->setDefaultOpenApp($default_tab);
			$em->flush();

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $workspace->getDefaultOpenApp());
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Check current user access to the app
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument|string
	 */
	private function workspaceCHECKUSERAPPACCESS($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$app = $post->getElementsByTagName('AppDocKey')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$docova = new Docova($this->container);
			$app = $docova->DocovaApplication(['appID' => $app]);
			$appId = $app->appID;
			if (empty($app) || empty($appId)) {
				throw new \Exception('Unspecified application source ID');
			}
			$acl = $app->getAcl();
			$access = 0;
			if ($acl->isManager()) {
				$access = 6;
			}
			elseif ($acl->isDesigner()) {
				$access = 5;
			}
			elseif ($acl->isEditor()) {
				$access = 4;
			}
			elseif ($acl->isAuthor()) {
				$access = 3;
			}
			elseif ($acl->isReader()) {
				$access = 2;
			}

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $access);
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Delete an application or library service
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument|string
	 */
	private function workspaceDELETEAPP($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$app = $post->getElementsByTagName('appUnid')->item(0)->nodeValue;
			$app = $em->getRepository('DocovaBundle:Libraries')->find($app);
			if (!empty($app)) {
				$acl = new Miscellaneous($this->container);
				$access = $acl->getDBAccessLevel($app);
				if ($access['dbaccess'] > 5) {
					$appUsers = $em->getRepository('DocovaBundle:UserAccounts')->createQueryBuilder('U')
						->join('U.userAppbuilderApps', 'A')
						->where('A.id = :app')
						->setParameter('app', $app->getId())
						->getQuery()
						->getResult();
					if (!empty($appUsers)) 
					{
						foreach ($appUsers as $user) {
							$user->removeUserAppbuilderApps($app);
						}
						$user = $appUsers = null;
					}
					
					$recentUsersApp = $em->getRepository('DocovaBundle:UserRecentApps')->findBy(['app' => $app->getId()]);
					if (!empty($recentUsersApp))
					{
						foreach ($recentUsersApp as $ua)
						{
							$em->remove($ua);
						}
					}
					
					$app->setTrash(true);
					$em->flush();
					
					$root = $output->appendChild($output->createElement('Results'));
					$newnode = $output->createElement('Result', 'OK');
					$attr = $output->createAttribute('ID');
					$attr->value = 'Status';
					$newnode->appendChild($attr);
					$root->appendChild($newnode);
					$newnode = $output->createElement('Result', 'DELETED');
					$attr = $output->createAttribute('ID');
					$attr->value = 'Ret1';
					$newnode->appendChild($attr);
					$root->appendChild($newnode);
				}
				else {
					throw new AccessDeniedException();
				}
			}
			else {
				throw new \Exception('Unspecified application/library source ID.');
			}
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Update app properties service
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceUPDATEAPPDOC($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$app = $post->getElementsByTagName('AppUnid')->item(0)->nodeValue;
			$type = $post->getElementsByTagName('AppType')->item(0)->nodeValue;
			if ($type == 'LG')
				$app = $em->getRepository('DocovaBundle:LibraryGroups')->find($app);
			else 
				$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app));
			if (empty($app)) {
				throw new \Exception('Unspecified source application ID.');
			}
			
			$access = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? 7 : 3;
			if ($type == 'LG' && $access < 7)
			{
				$access = $app->getCreatedBy()->getId() == $this->user->getId() ? 6 : $access;
			}
			elseif ($access < 7 && $app->getIsApp()) {
				$access = $this->getAppAccessLevel($app);
			}
			elseif ($access < 7) {
				$roles = $this->user->getRoles();
				foreach ($roles as $r) {
					if ($r == 'ROLE_LIBADMIN' . $app->getId()) {
						$access = 6;
						break;
					}
				}
			}

			if ($access < 5)
			{
				$status = 'NOACCESS';
			}
			else {
				$title = $post->getElementsByTagName('Title')->item(0)->nodeValue;
				$descriptoin = $post->getElementsByTagName('Description')->item(0)->nodeValue;
				$appIcon = $post->getElementsByTagName('AppIcon')->item(0)->nodeValue;
				$iconColor = $post->getElementsByTagName('AppIconColor')->item(0)->nodeValue;
				
				if ($type == 'LG')
				{
					$app->setGroupTitle($title);
					$app->setGroupDescription($descriptoin);
					$app->setGroupIcon($appIcon);
					$app->setGroupIconColor($iconColor);
				}
				else {
					$app->setLibraryTitle($title);
					$app->setDescription($descriptoin);
					$app->setAppIcon($appIcon);
					$app->setAppIconColor($iconColor);
				}
				$em->flush();
				$status = 'UPDATED';
			}
			
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $status);
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Create an application service
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceCREATEAPP($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$title = $post->getElementsByTagName('Title')->item(0)->nodeValue;
			$description = $post->getElementsByTagName('Description')->item(0)->nodeValue;
			$appIcon = $post->getElementsByTagName('AppIcon')->item(0)->nodeValue;
			$iconColor = $post->getElementsByTagName('AppIconColor')->item(0)->nodeValue;
			if (!empty($post->getElementsByTagName('AppType')->length)) {
				$appType = $post->getElementsByTagName('AppType')->item(0)->nodeValue;
			}
			elseif (!empty($post->getElementsByTagName('IsApp')->item(0)->nodeValue)) {
				$appType = 'A';
			}
			if (empty($title)) {
				throw new \Exception('Application title cannot be empty');
			}
			
			if (!$this->user->getUserProfile()->getCanCreateApp() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
				throw new \Exception('You have insufficient access to create new app/library.');
			}
			
			$em = $this->getDoctrine()->getManager();
			if ($appType === 'LG') {
				$is_new = true;
				$app = new LibraryGroups();
				$app->setGroupTitle($title);
				$app->setGroupDescription($description);
				$app->setGroupIcon($appIcon);
				$app->setGroupIconColor($iconColor);
				$app->setCreatedBy($this->user);
				$app->setDateCreated(new \DateTime());
			}
			else {
				$is_new = false;
				$source = $post->getElementsByTagName('AppTemplatePath')->item(0)->nodeValue;
				if (!empty($source)) {
					$source = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $source, 'isApp' => ($appType === 'A' ? true : false), 'Trash' => false));
					if (empty($source))
					{
						throw new \Exception('Source App/Library cannot be found.');
					}
					
					$options = [
						'title' => $title,
						'desc' => $description,
						'icon' => $appType == 'A' ? $appIcon : null,
						'iconcolor' => $appType == 'A' ? $iconColor : null,
						'host' => $source->getHostName(),
						'inherit' => ($post->getElementsByTagName('AppInherit')->item(0)->nodeValue == 'true') ? true : false,
						'is_app' => $appType == 'A' ? true : false,
						'copy_option' => $post->getElementsByTagName('AppCopyOption')->item(0)->nodeValue,
						'source_template' => $appType == 'A' ? $source->getId() : null
					];
						
					$app = $this->makeTemplateCopy($source, $options);
					if ($options['is_app'] === true)
					{
						$recent_app = new UserRecentApps();
						$recent_app->setUser($this->user);
						$recent_app->setLastOpenDate(new \DateTime());
						$recent_app->setApp($app);
						$em->persist($recent_app);
						$em->flush();
						$recent_app = null;
					}
				}
				else {
					$app = new Libraries();
					$app->setLibraryTitle($title);
					$app->setDescription($description);
					$app->setHostName($this->get('request_stack')->getCurrentRequest()->getHost());
					$app->setAppIconColor($iconColor);
					$app->setDateCreated(new \DateTime());
					if ($appType === 'L') {
						$app->setIsApp(false);
						$app->setPublicAccessEnabled(false);
						$app->setLoadDocsAsFolders(false);
						$app->setRecycleRetention(30);
						$app->setArchiveRetention(730);
						$app->setDocLogRetention(2192);
						$app->setEventLogRetention(90);
						$app->setMemberAssignment(false);
					}
					else {
						$app->setAppIcon($appIcon);
						$app->setIsApp(true);
					}
					$is_new = true;
				}
			}
			
			if ($is_new === true) {
				$em->persist($app);
				$em->flush();
			}
			
			if ($is_new === true && $appType == 'A') {
				$recent_app = new UserRecentApps();
				$recent_app->setUser($this->user);
				$recent_app->setLastOpenDate(new \DateTime());
				$recent_app->setApp($app);
				$em->persist($recent_app);
				$em->flush();
				$recent_app = null;
			}

			if ($appType !== 'LG') 
			{
				$customACL = new CustomACL($this->container);
				$customACL->insertObjectAce($app, 'ROLE_ADMIN', 'owner');
				$customACL->insertObjectAce($app, $this->user, 'owner', false);
				if ($appType == 'L') {
					if (empty($source)) {
						$customACL->insertObjectAce($app, 'ROLE_USER', array('view', 'create'));// I'm not sure if "create" level is required for an app.
					}
					else {
						$customACL->copyObjectAceEntries($source, $app, ['delete']);
					}
					$customACL = null;
				}
				else {
					if (empty($source)) {
						$customACL->insertObjectAce($app, 'ROLE_USER', 'edit');
					}
					else {
						$customACL->copyObjectAceEntries($source, $app);
					}
					$customACL = null;
					
					$acl_property = new AppAcl();
					$acl_property->setApplication($app);
					$acl_property->setCreateDocument(true);
					$acl_property->setDeleteDocument(true);
					$acl_property->setUserObject($this->user);
					$em->persist($acl_property);
					$em->flush();
					
					$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_USER'));
					$acl_property = new AppAcl();
					$acl_property->setApplication($app);
					$acl_property->setCreateDocument(true);
					$acl_property->setDeleteDocument(true);
					$acl_property->setGroupObject($group);
					$em->persist($acl_property);
					$em->flush();
					$em = $acl_property = $group = null;
				}
			}

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $app->getId());
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Copy applicatoin service
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceCOPYAPP($post)
	{
		
		set_time_limit(0);
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$src_app = $post->getElementsByTagName('SrcAppUnid')->item(0)->nodeValue;
			$title = $post->getElementsByTagName('Title')->item(0)->nodeValue;
			$description = $post->getElementsByTagName('Description')->item(0)->nodeValue;
			$app_icon = $post->getElementsByTagName('AppIcon')->item(0)->nodeValue;
			$icon_color = $post->getElementsByTagName('AppIconColor')->item(0)->nodeValue;
			$copy_type = $post->getElementsByTagName('AppCopyOption')->item(0)->nodeValue;
			$inherit = $post->getElementsByTagName('AppInherit')->item(0)->nodeValue == 'true' ? true : false;
			
			$em = $this->getDoctrine()->getManager();
			$src_app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $src_app));
			if (empty($src_app)) {
				throw new \Exception('Unspecified source application ID.');
			}
			
			$options = [
				'title' => $title,
				'desc' => $description,
				'icon' => $app_icon,
				'iconcolor' => $icon_color,
				'host' => $src_app->getHostName(),
				'inherit' => $inherit,
				'is_app' => true,
				'copy_option' => $copy_type,
				'source_template' => $inherit === true ? $src_app->getId() : null
			];
			
			$app = $this->makeTemplateCopy($src_app, $options);
			$customACL = new CustomACL($this->container);
			$customACL->insertObjectAce($app, 'ROLE_ADMIN', 'owner');
			$customACL->insertObjectAce($app, $this->user, 'owner', false);
			$customACL->copyObjectAceEntries($src_app, $app);
			$customACL = null;

			$tempapp = new Application($this->get('kernel'));
			$tempapp->setAutoExit(false);
				
			$input = new ArrayInput(array(
					'command' => 'docova:appassetsinstall',
					'appid' => $app->getId()
			));
				
			$tempapp->run($input, new NullOutput());
			
			
			if ($copy_type === 'DD') {
				//@TODO: copy documents and design from the source (following is just copying library's document not an app)
				$rootFolders = $em->getRepository('DocovaBundle:Folders')->findBy(array('Library' => $src_app->getId(), 'Del' => false, 'parentfolder' => null));
				if (!empty($rootFolders))
				{
					$pasted_folders = array();
					$UPLOAD_FILE_PATH = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root').DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage' : $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
					$conn = $this->getDoctrine()->getConnection();
					foreach ($rootFolders as $folder)
					{
						$children = $em->getRepository('DocovaBundle:Folders')->getDescendants($folder->getPosition(), $src_app->getId(), $folder->getId(), true);
						$conn->beginTransaction();
						try {
							foreach ($children as $f)
							{
								if ($f['id'] == $folder->getId()) {
									$new_folder = $this->get('docova.libcontroller')->createCopy($conn, $folder->getId(), $src_app->getId(), null, false, $this->user, $UPLOAD_FILE_PATH);
									$pasted_folders[] = array(
											'source_id' => $folder->getId(),
											'new_id' => $new_folder
									);
								}
								elseif (false != $parent_folder = $this->searchInPastedFolders($pasted_folders, $f['Parent']))
								{
									$new_folder = $this->get('docova.libcontroller')->createCopy($conn, $f['id'], $src_app->getId(), $parent_folder, false, $this->user, $UPLOAD_FILE_PATH);
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
			elseif ($copy_type === 'DO') {
				//@TODO: copy just the design from the source
			}

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $app->getId());
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Make a copy of an app or library
	 * @param mixed $source
	 * @param mixed $options
	 * @return \Docova\DocovaBundle\Entity\Libraries
	 */
	private function makeTemplateCopy($source, $options)
	{
		$em = $this->getDoctrine()->getManager();
		$docova = new Docova($this->container);
		if ($options['is_app'] === true) {
			$copy_handler = new CopyDesignServices($docova, $source, $this->user, $this->get('kernel')->getRootDir());
			$app = $copy_handler->copyApplication($options);
			$copy_handler->copyForms();
			$copy_handler->copyViews($this->get('router'));
			$copy_handler->copyLayouts();
			$copy_handler->copyPages();
			$copy_handler->copySubforms();
			$copy_handler->copyOutlines();
			$copy_handler->copyJavaScripts();
			$copy_handler->copyAgents();
			$copy_handler->copyScriptLibraries();
			$copy_handler->copyImages();
			$copy_handler->copyCsses();
			$copy_handler->installAssets($this->get('kernel'));
		}
		else {
			$copy_handler = new CopyLibraryServices($em, $source, $this->user);
			$app = $copy_handler->copyApplication($options);
			$copy_handler->copyDocTypes();
			if (!empty($options['copy_option']) && $options['copy_option'] == 'DD')
			{
				//@todo: should be implemented?!
				$copy_handler->copyFolders();
			}
		}
		
		return $app;
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
	 * Add to applist in app-builder service
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceADDTOAPPLIST($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$app = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified source application.');
			}
			
			$docova = new Docova($this->container);
			$docovaAcl = $docova->DocovaAcl($app);
			$docova = null;
			if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
				throw new \Exception('Access denied.');
			}
			
			$exist = false;
			if ($this->user->getUserAppbuilderApps()->count()) {
				$user_apps = $this->user->getUserAppbuilderApps();
				foreach ($user_apps as $ua) {
					if ($ua->getId() == $app->getId()) {
						$exist = true;
						break;
					}
				}
			}

			if ($exist === false) {
				$this->user->addUserAppbuilderApps($app);
				$em->flush();
			}

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $app->getId());
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Remove application from user applicatoin list service
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceREMOVEFROMAPPLIST($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$app = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application source ID.');
			}
			
			$this->user->removeUserAppbuilderApps($app);
			$em->flush();

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Add library to a library group
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceADDLIBRARYGROUPLIBRARY($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$libgroup = $post->getElementsByTagName('LibraryGroupId')->item(0)->nodeValue;
			$libgroup = $em->getRepository('DocovaBundle:LibraryGroups')->find($libgroup);
			if (empty($libgroup)) {
				throw new \Exception('Unspecifed library group source ID.');
			}
			
			$selected_libs = explode(',', $post->getElementsByTagName('LibsSelected')->item(0)->nodeValue);
			if (empty($selected_libs)) {
				throw new \Exception('No library is selected.');
			}
			foreach ($selected_libs as $lib)
			{
				$lib = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $lib, 'Trash' => false, 'isApp' => false));
				if (!empty($lib)) {
					$libgroup->addLibrary($lib);
				}
			}
			$em->flush();
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Remove libraries from a library group
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceREMOVELIBRARYGROUPLIBRARY($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$libgroup = $post->getElementsByTagName('LibraryGroupId')->item(0)->nodeValue;
			$libgroup = $em->getRepository('DocovaBundle:LibraryGroups')->find($libgroup);
			if (empty($libgroup)) {
				throw new \Exception('Unspecifed library group source ID.');
			}

			$selected_libs = explode(',', $post->getElementsByTagName('LibsSelected')->item(0)->nodeValue);
			if (empty($selected_libs)) {
				throw new \Exception('No library is selected.');
			}
			foreach ($selected_libs as $lib)
			{
				$lib = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $lib, 'Trash' => false, 'isApp' => false));
				if (!empty($lib)) {
					$libgroup->removeLibrary($lib);
				}
			}
			$em->flush();
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Delete library group and depenecies
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument|string
	 */
	private function workspaceDELETELG($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$libgroup = $post->getElementsByTagName('appUnid')->item(0)->nodeValue;
			$libgroup = $em->getRepository('DocovaBundle:LibraryGroups')->find($libgroup);
			if (empty($libgroup)) {
				throw new \Exception('Unspecifed library group source ID.');
			}
			
			$libraries = $libgroup->getLibraries();
			if (!empty($libraries) && $libraries->count())
			{
				foreach ($libraries as $lib) {
					$libgroup->removeLibrary($lib);
				}
			}
			$em->remove($libgroup);
			$em->flush();

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', 'DELETED');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Update an app design
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceUPDATEAPPDESIGN($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$appid = $post->getElementsByTagName('AppDocKey')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(['id' => $appid, 'Trash' => false, 'isApp' => true]);
			if (empty($app)) {
				throw new \Exception('Application was not found!');
			}
			if (!$app->getInheritDesignFrom()) {
				throw new \Exception('Sorry, update design from Master has not implemented through this process. Talk to IT Administrator for details.');
			}
			
			$source = $em->getRepository('DocovaBundle:Libraries')->find($app->getInheritDesignFrom());
			if (empty($source)) {
				throw new \Exception('Source templace app cannot be found!');
			}
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Update latest opened apps by user
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceUPDATELATESTOPENAPP($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$unid = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$type = $post->getElementsByTagName('AppType')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			if ($type == 'LG') {
				$recent_app = $em->getRepository('DocovaBundle:UserRecentApps')->findOneBy(['user' => $this->user->getId(), 'libgroup' => $unid]);
			}
			else {
				$recent_app = $em->getRepository('DocovaBundle:UserRecentApps')->findOneBy(['user' => $this->user->getId(), 'app' => $unid]);
			}
			if (empty($recent_app))
			{
				if ($type == 'LG') {
					$app = $em->getRepository('DocovaBundle:LibraryGroups')->find($unid);
				}
				else {
					$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(['id' => $unid, 'Trash' => false]);
				}
				if (!empty($app))
				{
					$recent_app = new UserRecentApps();
					$recent_app->setUser($this->user);
					$recent_app->setLastOpenDate(new \DateTime());
					if ($type == 'LG') {
						$recent_app->setLibgroup($app);
					}
					else {
						$recent_app->setApp($app);
					}
					$em->persist($recent_app);
					$em->flush();
				}
			}
			else {
				$recent_app->setLastOpenDate(new \DateTime());
				$em->flush();
			}

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Create app-group for current user
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceCREATEAPPGROUP($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$group_name = $post->getElementsByTagName('AppGroupName')->item(0)->nodeValue;
			if (empty($group_name)) {
				throw new \Exception('Group name cannot be empty.');
			}

			$em = $this->getDoctrine()->getManager();

			$app_group = new UserAppGroups();
			$app_group->setCreatedBy($this->user);
			$app_group->setDateCreated(new \DateTime());
			$app_group->setGroupName($group_name);
			$em->persist($app_group);
			if (!empty($post->getElementsByTagName('Unid')->length))
			{
				$index = 0;
				foreach ($post->getElementsByTagName('Unid') as $item) {
					$app_list = new AppGroupsContent();
					$app_list->setAppGroup($app_group);
					if ($post->getElementsByTagName('AppType')->item($index)->nodeValue == 'LG') {
						$libgroup = $em->getReference('DocovaBundle:LibraryGroups', $item->nodeValue);
						$app_list->setLibraryGroup($libgroup);
					}
					else {
						$application = $em->getReference('DocovaBundle:Libraries', $item->nodeValue);
						$app_list->setApplication($application);
					}
					$em->persist($app_list);
					$index++;
				}
			}
			$em->flush();
			
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Get the app-group info
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceGETAPPGROUPINFO($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$app_group = $post->getElementsByTagName('AppGroupUnid')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			if (empty($app_group)) {
				throw new \Exception('No app-group is selected!');
			}
			$result = $em->getRepository('DocovaBundle:AppGroupsContent')->getAppGroupList($app_group);
			$apps_list = [];
			if (!empty($result))
			{
				foreach ($result as $app) {
					if (!empty($app['AppId']))
						$apps_list[] = $app['AppId'];
					elseif (!empty($app['LibGroupId']))
						$apps_list[] = $app['LibGroupId'];
				}
			}

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result');
			$cdata = $output->createCDATASection(implode(';', $apps_list));
			$newnode->appendChild($cdata);
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Update app-group details
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceUPDATEAPPGROUP($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$app_group = $post->getElementsByTagName('AppGroupUnid')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app_group = $em->getRepository('DocovaBundle:UserAppGroups')->find($app_group);
			if (empty($app_group)) {
				throw new \Exception('App-Group not found.');
			}
			$group_name = $post->getElementsByTagName('AppGroupName')->item(0)->nodeValue;
			if (empty($group_name)) {
				throw new \Exception('Group name cannot be empty.');
			}
			
			$app_group->setDateModified(new \DateTime());
			$app_group->setGroupName($group_name);
			foreach ($app_group->getAppsList() as $app) {
				$em->remove($app);
			}
			$em->flush();
			if (!empty($post->getElementsByTagName('Unid')->length))
			{
				$index = 0;
				foreach ($post->getElementsByTagName('Unid') as $item) {
					$app_list = new AppGroupsContent();
					$app_list->setAppGroup($app_group);
					if ($post->getElementsByTagName('AppType')->item($index)->nodeValue == 'LG') {
						$libgroup = $em->getReference('DocovaBundle:LibraryGroups', $item->nodeValue);
						$app_list->setLibraryGroup($libgroup);
					}
					else {
						$application = $em->getReference('DocovaBundle:Libraries', $item->nodeValue);
						$app_list->setApplication($application);
					}
					$em->persist($app_list);
					$index++;
				}
				$em->flush();
			}

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Delete an app-group
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceDELETEAPPGROUP($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$app_group = $post->getElementsByTagName('AppGroupUnid')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app_group = $em->getRepository('DocovaBundle:UserAppGroups')->find($app_group);
			if (empty($app_group)) {
				throw new \Exception('App-Group not found.');
			}
			
			$apps_list = $app_group->getAppsList();
			if ($apps_list && $apps_list->count())
			{
				foreach ($apps_list as $app) {
					$em->remove($app);
				}
			}
			
			$em->remove($app_group);
			$em->flush();

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Refresh workspace left navigation section
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function workspaceREFRESHNAV($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			
			$params = $this->computeWorkspaceParams();
			$response = $this->renderView('DocovaBundle:Workspace:workspaceLeftNavSection.html.twig', [
				'appgroups' => $params['appgroups'],
				'applications' => $params['applications'],
				'isLibAdmin' => $params['isLibAdmin'],
				'recentapps' => $params['recentapps'],
				'templates' => $params['templates']
			]);

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$attr = $output->createAttribute('ID');
			$attr->value = 'Status';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result');
			$cdata = $output->createCDATASection(rawurlencode($response));
			$newnode->appendChild($cdata);
			$attr = $output->createAttribute('ID');
			$attr->value = 'Ret1';
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Compute the left navigator section variables for twig
	 * 
	 * @return array
	 */
	private function computeWorkspaceParams()
	{
		$isLibraryAdmin = false;
		$em = $this->getDoctrine()->getManager();
		
		$app_groups = $em->getRepository('DocovaBundle:UserAppGroups')->getUserAppGroups($this->user->getId());		
		$access = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? 7 : 3;		
		if (!empty($app_groups))
		{
			for ($x = 0; $x < count($app_groups); $x++)
			{
				$app_list = $em->getRepository('DocovaBundle:AppGroupsContent')->getAppGroupList($app_groups[$x]['id']);
				if (!empty($app_list))
				{
					for ($c = 0; $c < count($app_list); $c++)
					{
						if (!empty($app_list[$c]['AppId']))
						{
							$app = $em->getReference('DocovaBundle:Libraries', $app_list[$c]['AppId']);
							$app->setIsApp($app_list[$c]['isApp']);
							$app_list[$c]['access'] = $this->getAppAccessLevel($app);
							$app = null;
						}
						elseif (!empty($app_list[$c]['LibGroupId']))
						{
							if ($access < 7)
							{
								$access = $app_list[$c]['createdBy'] == $this->user->getId() ? 6 : $access;
							}
							$app_list[$c]['access'] = $access;
						}
					}
					$app_groups[$x]['appslist'] = $app_list;
				}
				else {
					$app_groups[$x]['appslist'] = [];
				}
			}
		}
		
		$templates = $em->getRepository('DocovaBundle:Libraries')->findBy(array('Trash' => false, 'isApp' => true, 'Is_Template' => true), array('Library_Title' => 'ASC'));
		if (!empty($templates[0]))
		{
			foreach ($templates as $index => $temp) {
			    $access = $this->getAppAccessLevel($temp);
			    if($access == 0){
			        unset($templates[$index]);   
			    }else{
			        $templates[$index] = ['app' => $temp, 'access' => $access, 'isApp' => true, 'apptype' => 'T'];
			    }
			}
			$temp = $index = null;
		}
		
		$recent_apps = $em->getRepository('DocovaBundle:UserRecentApps')->getLatestOpenedApps($this->user->getId(), $this->user->getUserProfile()->getRecentUsedAppCount());
		if (!empty($recent_apps[0]))
		{
			foreach ($recent_apps as $index => $app)
			{
				if ($app instanceof \Docova\DocovaBundle\Entity\LibraryGroups) {
					if ($access < 7)
					{
						$access = 3;
						$access = $app->getCreatedBy()->getId() == $this->user->getId() ? 6 : $access;
					}
					$recent_apps[$index] = ['app' => $app, 'access' => $access, 'isApp' => false, 'apptype' => 'LG'];
				}
				else {
				    $access = $this->getAppAccessLevel($app);
				    if($access == 0){
				        unset($recent_apps[$index]); 
				    }else{
				        $recent_apps[$index] = ['app' => $app, 'access' => $access, 'isApp' => $app->getIsApp(), 'apptype' => ($app->getIsApp() ? 'A' : 'L')];
				    }
				}
			}
			$app = $index = null;
		}
		
		$roles = $this->user->getRoles();
		$libraries = $em->getRepository('DocovaBundle:Libraries')->createQueryBuilder('L')
			->select('L.id')
			->where('L.Trash = false')
			->andWhere('L.isApp = false')
			->getQuery()
			->getArrayResult();
		
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
		
		$apps = $em->getRepository('DocovaBundle:Libraries')->getAllAppLibGroups(0);
		if (!empty($apps))
		{
		    $appcount = count($apps);
			for ($x = 0; $x < $appcount; $x++) {
				//if it's library group
				if (!$apps[$x]['isapp'] && !empty($apps[$x]['creator'])) {
					if ($access < 7)
					{
						$access = 3;
						$access = $apps[$x]['creator'] == $this->user->getId() ? 6 : $access;
					}
					$apps[$x] = ['app' => $apps[$x], 'access' => $access, 'isApp' => false, 'apptype' => 'LG'];
				}
				//if it's a library
				elseif (!$apps[$x]['isapp'] && is_null($apps[$x]['creator'])) {
					$app = $em->getReference('DocovaBundle:Libraries', $apps[$x]['id']);
					$app->setIsApp(false);
					$access = $this->getAppAccessLevel($app);
					if($access == 0){
					    unset($apps[$x]);
					}else{
					    $apps[$x] = ['app' => $apps[$x], 'access' => $access, 'isApp' => false, 'apptype' => 'L' ];
					}
				}
				//if it's an app
				else {
					$app = $em->getReference('DocovaBundle:Libraries', $apps[$x]['id']);
					$app->setIsApp(true);
					
					$access = $this->getAppAccessLevel($app);
					if($access == 0){
					    unset($apps[$x]);
					}else{
					    $apps[$x] = ['app' => $apps[$x], 'access' => $access, 'isApp' => true, 'apptype' => 'A'];
					}
				}
			}
		}
		
		return [
			'appgroups' => $app_groups,
			'applications' => $apps,
			'isLibAdmin' => $isLibraryAdmin,
			'recentapps' => $recent_apps,
			'templates' => $templates
		];
	}
	
	/**
	 * Fetch access level for the application or template
	 * 
	 * @param \Docova\DocovaBundle\Entity\Libraries $app
	 * @return number
	 */
	private function getAppAccessLevel($app)
	{
		if ($app->getIsApp())
		{
			$docova = new Docova($this->container);
			$acl = $docova->DocovaAcl($app);
			$docova = null;
			$access = 0;
			if ($acl->isManager()) {
				$access = 6;
			}
			elseif ($acl->isDesigner()) {
				$access = 5;
			}
			elseif ($acl->isEditor()) {
				$access = 4;
			}
			elseif ($acl->isAuthor()) {
				$access = 3;
			}
			elseif ($acl->isReader()) {
				$access = 2;
			}
		}
		else {
			$acl = new Miscellaneous($this->container);
			$access = $acl->getDBAccessLevel($app);
			$access = $access['dbaccess'];
		}
		
		return $access;
	}
}