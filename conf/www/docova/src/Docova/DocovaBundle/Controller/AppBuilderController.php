<?php

namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Docova\DocovaBundle\Entity\AppLayout;
use Docova\DocovaBundle\Entity\AppViews;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Docova\DocovaBundle\ObjectModel\Docova;

class AppBuilderController extends Controller 
{
	private $_docova;
	protected $user;
	protected $global_settings;
	protected $app_path;
	protected $assetPath;

	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
	
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];

		$this->app_path = $this->get('kernel')->getRootDir() . '/../src/Docova/DocovaBundle/Resources/views/DesignElements/';
		$this->assetPath = $this->get('kernel')->getRootDir() . '/../web/bundles/docova/';
		
		$this->_docova = new Docova($this->container);
	}

	public function appBuilderFrameAction()
	{
		return $this->render('DocovaBundle:AppBuilder:wAppBuilderFrame.html.twig');
	}

	public function appBuilderLeftNavAction()
	{
		$this->initialize();
		$user_apps = '';
		if ($this->user->getUserAppbuilderApps()->count())
		{
			foreach ($this->user->getUserAppbuilderApps() as $app) {
				$user_apps .= "<Library><Title><![CDATA[{$app->getLibraryTitle()}]]></Title>";
				$user_apps .= "<Description><![CDATA[{$app->getDescription()}]]></Description>";
				$user_apps .= "<DocKey>{$app->getId()}</DocKey>";
				$user_apps .= "<AppIcon><![CDATA[{$app->getAppIcon()}]]></AppIcon>";
				$user_apps .= "<LayoutKey><![CDATA[]]></LayoutKey>";
				$user_apps .= '<NsfName>'. $this->generateUrl('docova_homepage') .'</NsfName>';
				$user_apps .= "<Unid>{$app->getId()}</Unid>";
				$user_apps .= '<Selected></Selected><Modified></Modified></Library>';
			}
		}
		return $this->render('DocovaBundle:AppBuilder:wAppBuilderLeftNav.html.twig', array(
				'user' => $this->user,
				'userapps' => $user_apps
		));
	}

	public function tabbedTableAppBuilderAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:tabbedTableAppBuilder.html.twig', array('user' => $this->user));
	}

	public function appBuilderHomePageAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:appBuilderHomePage.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings
		));
	}

	public function openImportAppDlgAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:dlgImportApp.html.twig', array(
			'user' => $this->user
		));
	}

	public function openImportDesignElementDXLDlgAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:dlgImportDesignElementDXL.html.twig', array(
			'user' => $this->user
		));
	}

	public function openAddExistingAppDlgAction()
	{
		return $this->render('DocovaBundle:AppBuilder:dlgAddExistingApp.html.twig');
	}

	public function openIneritAppDlgAction()
	{
		return $this->render('DocovaBundle:AppBuilder:dlgIneritApp.html.twig');
	}

	public function applicationDocKeyAction(Request $request, $appid)
	{
		$em = $this->getDoctrine()->getManager();
		$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appid, 'Trash' => false, 'isApp' => true));
		if (empty($app)) {
			throw $this->createNotFoundException('Unsepecified application source ID.');
		}
		$this->initialize();
		$docovaAcl = $this->_docova->DocovaAcl($app);
		if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
			throw new AccessDeniedException();
		}
		$docovaAcl = null;
	
		if ($request->isMethod('POST'))
		{
			$response = new Response();
			try {
				$req = $request->request;
				$title		 = $req->get('Title');
				$app_icon	 = $req->get('AppIcon');
				$desc		 = ($req->get('Description') ? $req->get('Description') : null);
				$inherit	 = ($req->get('InheritDesignFrom') ? $req->get('InheritDesignFrom') : null);
				$isInherit	 = ($req->get('InheritDesignFrom') ? true : false);
				$isTemplate  = ($req->get('IsAppTemplate') == 1 ? true : false);
				$mLaunchType = ($req->get('MobileLaunchType') ? $req->get('MobileLaunchType') : null);
				$mLaunchId   = (($req->get('MobileLaunchId') && $mLaunchType) ? $req->get('MobileLaunchId') : null);
				
				$app->setLibraryTitle($title);
				$app->setAppIcon($app_icon);
				$app->setDescription($desc);
				$app->setInheritDesignFrom($inherit);
				$app->setAppInherit($isInherit);
				$app->setIsTemplate($isTemplate);
				$app->setMobileLaunchType($mLaunchType);
				$app->setMobileLaunchId($mLaunchId);
				$em->flush();
				
				if ($request->query->get('source') == 'admin') {
					$content = <<<HTML
<!DOCTYPE HTML><html><head></head><body text="#000000">
<script type="text/javascript">
var top_frame = window.parent.frames["fraAdminContentTop"];
var viewFrame = parent.frames['fraAdminFixedTab'];
if (top_frame) {
	var index = top_frame.tabs.tabs("option", "active");
	top_frame.removeTabByIndex(index);
	viewFrame.ViewLoadData();
}
</script>
</body></html>
HTML;
					$response->setContent($content);
				}
				else {
					$response->setContent('<!DOCTYPE HTML><html><head></head><body text="#000000"><script>window.parent.fraTabbedTable.objTabBar.CloseTab(\'appBuilderMainView\', true, false)</script></body></html>');
				}
				return $response;
			}
			catch (\Exception $e) {
				$response->setContent('Oops! ERROR: ' . $e->getMessage() .' on line ' . $e->getLine() . ' of ' . $e->getFile());
				return $response;
			}
		}
	
		return $this->render('DocovaBundle:AppBuilder:wAppProperties.html.twig', array(
				'settings' => $this->global_settings,
				'user' => $this->user,
				'application' => $app
		));
	}
	
	public function openAclAddRoleDlgAction()
	{
		return $this->render('DocovaBundle:AppBuilder:dlgACLAddRole.html.twig');
	}
	
	public function openAclRemoveRoleDlgAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:dlgACLRemoveRole.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function openAclAddEntryDlgAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:dlgACLAddEntry.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function notesViewAction(Request $request, $viewid)
	{
		$em = $this->getDoctrine()->getManager();
		$app = $request->query->get('AppID');
		$mode = $request->query->get('mode');
		$element = $request->query->get('DesignElement');
		$this->initialize();
		if ($app != 'GENERAL')
		{
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($app) && $mode != 'build') {
				throw $this->createNotFoundException('Unspecified application source ID.');
			}
		
			$docovaAcl = $this->_docova->DocovaAcl($app);
			if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
				throw new AccessDeniedException();
			}
			$docovaAcl = null;
		}
		
		if (!empty($viewid)) 
		{
			$view = $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('id' => $viewid, 'application' => $app->getId()));
			if (empty($view)) {
				throw $this->createNotFoundException('Unspecified view source ID.');
			}
		}
		
		if ($mode == 'build') {
			if ($request->isMethod('POST')) 
			{
				$old_perspective = $old_select = null;
				$xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $xml->appendChild($xml->createElement('Results'));
				try {
					$req = $request->request;
					if (!trim($req->get('ViewName'))) {
						throw new \Exception('"View Name" is required to be filled.');
					}
					
					$perspective_xml = new \DOMDocument();
					$viewpers = $req->get('ViewPerspectiveTxt');
					$perspective_xml->loadXML(urldecode($viewpers));
					if (empty($view)) {
						$view = new AppViews();
					}
					else {
						$old_perspective = new \DOMDocument();
						$old_perspective->loadXML($view->getViewPerspective());
						$old_select = $view->getConvertedQuery();
					}
					$view->setViewName($req->get('ViewName'));
					$view->setViewAlias(trim($req->get('ViewAlias')) ? trim($req->get('ViewAlias')) : null);
					$view->setMaxDocCount(trim($req->get('MaxDocCount')) ? trim($req->get('MaxDocCount')) : null);
					$view->setRespHierarchy(trim($req->get('RespHierarchy')) ? true : false);
					$view->setRespColspan($req->get('RespColspan')? trim($req->get('RespColspan'))  : 0);
					$view->setSelectionType(trim($req->get('ViewSelectionType')) == 'S' ? 'S' : 'F');
					$view->setViewQuery($req->get('EmulateFolder') != 1 && trim($req->get('ViewSelectionFormula')) ? trim($req->get('ViewSelectionFormula')) : '');
					$view->setConvertedQuery($req->get('EmulateFolder') != 1 ? trim($req->get('TranslatedSelectionFormula')) : null);
					$view->setViewJavaScript(trim($req->get('ViewJavascriptTxt')) ? base64_decode(trim($req->get('ViewJavascriptTxt'))) : null);
					$view->setViewPerspective($perspective_xml->saveXML());
					$view->setViewType(trim($req->get('ViewType')) ? trim($req->get('ViewType')) : 'Standard');
					$view->setEmulateFolder($req->get('EmulateFolder') == 1 ? true : false);
					$view->setPrivateOnFirstUse($req->get('EmulateFolder') == 1 && $req->get('PrivateOnFirstUse') == 1 ? true : false);
					$view->setEnablePaging($req->get('UseContentPaging') ==  1 ? true : false);
					$view->setOpenInEdit($req->get('OpenDocInEditMode') == 1 ? true :false);
					$view->setOpenInDialog($req->get('OpenInDialog') == 1 ? true : false);
					$view->setShowSelection($req->get('ShowSelection') == 1 ? true : false);
					$view->setAutoCollapse($req->get('AutoCollapse') == 1 ? true : false);
					$view->setViewFtSearch($req->get('ViewSearch') == 1 ? true : false);
					if ($req->get('ViewType') == 'Calendar')
					{
						$view->setDayClick(trim($req->get('DayClick')) ? trim($req->get('DayClick')) : null);
						$view->setDayDblClick(trim($req->get('DayDblClick')) ? trim($req->get('DayDblClick')) : null);
						$view->setEventClick(trim($req->get('EventClick')) ? trim($req->get('EventClick')) : null);
						$view->setEventColor(trim($req->get('EventColor')) ? trim($req->get('EventColor')) : null);
						$view->setEventDblClick(trim($req->get('EventDblClick')) ? trim($req->get('EventDblClick')) : null);
						$view->setEventTextColor(trim($req->get('EventTextColor')) ? trim($req->get('EventTextColor')) : null);
						$view->setFirstDay($req->get('FirstDay') == 1 ? true : false);
						$view->setStyle($req->get('Style') == 'true' ? true : false);
						$view->setWeekends($req->get('Weekends') == 'true' ? true : false);
						$view->setGanttDefaultForm(null);
						$view->setGanttResourceType(null);
						$view->setGanttResourceOptions(null);
						$view->setGanttResourceFormula(null);
						$view->setGanttTranslatedFormula(null);
					}
					elseif ($req->get('ViewType') == 'Gantt') {
						$view->setDayClick(null);
						$view->setDayDblClick(null);
						$view->setEventClick(null);
						$view->setEventColor(null);
						$view->setEventDblClick(null);
						$view->setEventTextColor(null);
						$view->setFirstDay(true);
						$view->setStyle(false);
						$view->setWeekends(false);
						$view->setGanttDefaultForm(trim($req->get('GanttDefaultForm')) ? trim($req->get('GanttDefaultForm')) : null);
						$view->setGanttResourceType(trim($req->get('GanttResourceType')) ? trim($req->get('GanttResourceType')) : 'A');
						if ($req->get('GanttResourceType') == 'F' && trim($req->get('GanttResourceFormula')) != '') {
							$view->setGanttResourceFormula(trim($req->get('GanttResourceFormula')));
							$view->setGanttTranslatedFormula(trim($req->get('TranslatedGanttResourceFormula')));;
							$view->setGanttResourceOptions(null);
						}
						elseif ($req->get('GanttResourceType') == 'M' && trim($req->get('GanttResourceOptions')) != '')  {
							$view->setGanttResourceOptions(trim($req->get('GanttResourceOptions')));
							$view->setGanttResourceFormula(null);
							$view->setGanttTranslatedFormula(null);
						}
						else {
							$view->setGanttResourceOptions(null);
							$view->setGanttResourceFormula(null);
							$view->setGanttTranslatedFormula(null);
						}
					}
					else {
						$view->setDayClick(null);
						$view->setDayDblClick(null);
						$view->setEventClick(null);
						$view->setEventColor(null);
						$view->setEventDblClick(null);
						$view->setEventTextColor(null);
						$view->setFirstDay(true);
						$view->setStyle(false);
						$view->setWeekends(false);
						$view->setGanttDefaultForm(null);
						$view->setGanttResourceType(null);
						$view->setGanttResourceOptions(null);
						$view->setGanttResourceFormula(null);
					}
					$view->setPDU($req->get('ProhibitDesignUpdate') == 'PDU' ? true : false);
					$view->setModifiedBy($this->user);
					$view->setDateModified(new \DateTime());
					
					if (empty($viewid)) 
					{
						$view->setCreatedBy($this->user);
						$view->setDateCreated(new \DateTime());
						$view->setApplication($app);
						$em->persist($view);
					}
					$em->flush();
					
					if (true !== $this->generateCustomView($view, $perspective_xml, $app, ($old_select == $view->getConvertedQuery()), $old_perspective)) {
						$em->remove($view);
						$em->flush();
					}

					$view_name = str_replace([' ', ':'], '', $view->getViewName());
					
					if (trim($req->get('ViewToolbarTxt'))) 
					{
						if (!is_dir($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'templates')) {
							mkdir($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'templates', 0755, true);
						}

						if (!is_dir($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'View')) {
							mkdir($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'View', 0755, true);
						}
						
						file_put_contents($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR.$view_name.'.html.twig', $req->get('ViewToolbarTxt'));
					}else{
					    if(file_exists($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR.$view_name.'.html.twig')){
					        unlink($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR.$view_name.'.html.twig');
					    }
					}

					$viewcss = $req->get('viewCSSTxt');

					if (trim($req->get('viewCSSTxt'))) 
					{
						$viewcss = urldecode(trim($req->get('viewCSSTxt')));
						if (!is_dir($this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app->getId().DIRECTORY_SEPARATOR."views")) {
							mkdir($this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app->getId().DIRECTORY_SEPARATOR."views", 0775, true);
						}
						file_put_contents($this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app->getId() .DIRECTORY_SEPARATOR."views". DIRECTORY_SEPARATOR . $view_name . '.css', $viewcss);

						//-- copy the files to assets directory
						if (!is_dir($this->assetPath.'/css/custom/'.$app->getId().'/views')) {
							mkdir($this->assetPath.'/css/custom/'.$app->getId().'/views', 0775, true);
						}
						file_put_contents($this->assetPath.'/css/custom/'.$app->getId().'/views/'. $view_name.'.css', $viewcss);					
						
					}

					$toolbarcontent = $req->get('ViewToolbarTWIG');
					if (trim($toolbarcontent)) 
					{	
					    if (!is_dir($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar')) {
					        mkdir($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar', 0755, true);
					    }
					    $toolbarcontent = str_replace(array("&lt;", "&gt;"),array("<", ">"),$toolbarcontent);
						file_put_contents($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar'.DIRECTORY_SEPARATOR.$view_name.'.html.twig', $toolbarcontent);
					}else{
					    if(file_exists($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar'.DIRECTORY_SEPARATOR.$view_name.'.html.twig')){
					        unlink($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar'.DIRECTORY_SEPARATOR.$view_name.'.html.twig');
					    }
					}
					
					$toolbarcontent = $req->get('ViewToolbarTWIG_m');
					if (trim($toolbarcontent))
					{
					    if (!is_dir($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar')) {
					        mkdir($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar', 0755, true);
					    }
					    $toolbarcontent = str_replace(array("&lt;", "&gt;"),array("<", ">"),$toolbarcontent);
					    file_put_contents($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar'.DIRECTORY_SEPARATOR.$view_name.'_m.html.twig', $toolbarcontent);
					}else{
					    if(file_exists($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar'.DIRECTORY_SEPARATOR.$view_name.'_m.html.twig')){
					        unlink($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'toolbar'.DIRECTORY_SEPARATOR.$view_name.'_m.html.twig');
					    }
					}
					
					$newnode = $xml->createElement('Result', 'SUCCESS');
					$att = $xml->createAttribute('ID');
					$att->value = 'Status';
					$newnode->appendChild($att);
					$root->appendChild($newnode);
					$newnode = $xml->createElement('Result', $view->getId());
					$att = $xml->createAttribute('ID');
					$att->value = 'Ret1';
					$newnode->appendChild($att);
					$root->appendChild($newnode);
				}
				catch (\Exception $e) {
					$newnode = $xml->createElement('Result', 'FAILED');
					$att = $xml->createAttribute('ID');
					$att->value = 'Status';
					$newnode->appendChild($att);
					$root->appendChild($newnode);
					$newnode = $xml->createElement('Error');
					$cdata = $xml->createCDATASection($e->getMessage());
					$newnode->appendChild($cdata);
					$root->appendChild($newnode);
				}
				
				if (function_exists('opcache_reset')) {
					opcache_reset();
				}
				$response = new Response();
				$response->headers->set('Content-Type', 'text/xml');
				$response->setContent($xml->saveXML());
				return $response;
			}
			
			$app_forms = $em->getRepository('DocovaBundle:AppForms')->getAppFormNames($app->getId());
			$toolbar = '';
			$viewcss = '';
			if (!empty($viewid) && !empty($view))
			{
				$view_name = str_replace([' ', ':'], '', $view->getViewName());
				$filepath = $this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR.$view_name.'.html.twig';
				if (file_exists($filepath)) 
				{
					$toolbar = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR.$view_name.'.html.twig');
				}

				//viewcss
				$filepath = $this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app->getId().DIRECTORY_SEPARATOR."Views".DIRECTORY_SEPARATOR.$view_name.'.css';
				if (file_exists($filepath)) 
				{
					$viewcss = file_get_contents($filepath);
				}
			}
			return $this->render('DocovaBundle:AppBuilder:sfViewBuilder.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings,
				'application' => $app,
				'toolbar' => $toolbar,
				'forms' => $app_forms,
				'viewcss' => $viewcss,
				'appView' => !empty($viewid) && !empty($view) ? $view : null
			));
		}
		else {
			return $this->render('DocovaBundle:AppBuilder:sfViewContent.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings,
				'application' => $app,
				'view_name' => "App$element"
			));
		}
	}
	
	public function widgetsViewAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:sfViewWidgets.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'view_name' => 'Widgets'
		));
	}
	
	public function getAppViewEntriesAction($viewname, $appid)
	{
		$output = array('@timestamp' => time(), '@toplevelentries' => '0');
		$em = $this->getDoctrine()->getManager();
		if (!empty($appid)){
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appid, 'Trash' => false, 'isApp' => true));
		}
		if (!empty($app))
		{
			$this->initialize();
			$docovaAcl = $this->_docova->DocovaAcl($app);
			if ($docovaAcl->isManager() || $docovaAcl->isDesigner())
			{
				$docovaAcl = null;
				switch ($viewname)
				{
					case 'AppForms':
						$output = $em->getRepository('DocovaBundle:AppForms')->getViewData($appid);
						break;
					case 'AppViews':
						$output = $em->getRepository('DocovaBundle:AppViews')->getViewData($appid);
						break;
					case 'AppLayouts':
						$output = $em->getRepository('DocovaBundle:AppLayout')->getViewData($appid);
						break;
					case 'AppPages':
						$output = $em->getRepository('DocovaBundle:AppPages')->getViewData($appid);
						break;
					case 'AppSubforms':
						$output = $em->getRepository('DocovaBundle:Subforms')->getViewData($appid);
						break;
					case 'AppMenus':
						$output = $em->getRepository('DocovaBundle:AppOutlines')->getViewData($appid);
						break;
					case 'AppJS':
						$output = $em->getRepository('DocovaBundle:AppJavaScripts')->getViewData($appid);
						break;
					case 'AppAgents':
						$output = $em->getRepository('DocovaBundle:AppAgents')->getViewData($appid);
						break;
					case 'AppCSS':
						$output = $em->getRepository('DocovaBundle:AppCss')->getViewData($appid);
						break;
					case 'AppImages':
						$output = $em->getRepository('DocovaBundle:AppFiles')->getViewData($appid);
						break;
					case 'AppWorkflow':
						$output = $em->getRepository('DocovaBundle:Workflow')->getViewData($appid);
						break;
					case 'AppScriptLibraries':
						$output = $em->getRepository('DocovaBundle:AppPhpScripts')->getViewData($appid);
						break;
					default:
						$output['Error'] = 'Unrecognized view name!';
						break;
				}
			}
		}
		elseif ($appid === '0' && ($viewname === 'systemwidgets' || $viewname === 'customwidgets'))
		{
			if ($viewname === 'customwidgets') {
				$output = $em->getRepository('DocovaBundle:Widgets')->getViewData(true);
			}
			elseif ($viewname === 'systemwidgets') {
				$output = $em->getRepository('DocovaBundle:Widgets')->getViewData();
			}
		}

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($output));
		return $response;
	}
	
	public function AppFormBuilderAction(Request $request)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$form = $request->query->get('FormUNID');
		$em = $this->getDoctrine()->getManager();
		$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
		if (empty($app)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}
		$docovaAcl = $this->_docova->DocovaAcl($app);
		if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
			throw new AccessDeniedException();
		}
		$docovaAcl = null;

		$appforms = $em->getRepository('DocovaBundle:AppForms')->getAppFormNames($app_id);
		$appWorkflows = $em->getRepository('DocovaBundle:Workflow')->findBy(array('application' => $app_id));
		$appCssFiles = $em->getRepository('DocovaBundle:AppCss')->findBy(array('application' => $app_id));
		$appJsFiles = $em->getRepository('DocovaBundle:AppJavaScripts')->findBy(array('application' => $app_id));
		if (!empty($form))
		{
			$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' => $form, 'application' => $app_id, 'trash' => false));
		}
		return $this->render('DocovaBundle:AppBuilder:AppFormBuilder.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'appId' => $app_id,
			'appForm' => $form,
			'formNames' => $appforms,
			'appWorkflows' => $appWorkflows,
			'appCssFiles' => $appCssFiles,
			'appJsFiles' => $appJsFiles,
		    'gmap_key' => ($this->container->hasParameter('google_api_key') ? $this->container->getParameter('google_api_key') : ''),
		    'weather_key' => ($this->container->hasParameter('weather_api_key') ? $this->container->getParameter('weather_api_key') : '')
		));
	}
	
	public function applicationLookupsAction(Request $request, $viewname)
	{
		$start = null;
		$app = $request->query->get('RestrictToCategory');
		if (empty($app)) {
			$app = $request->query->get('AppID');
			$start = $request->query->get('StartKey');
		}
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		try {
			$this->initialize();
			$em = $this->getDoctrine()->getManager();
			$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
			if (empty($application))
				throw new \Exception('Unspecified application source ID.');

			$docovaAcl = $this->_docova->DocovaAcl($application);
			if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
				throw new \Exception('Access Denied.');
			}
			$docovaAcl = null;

			switch ($viewname)
			{
				case 'Forms':
					$xml .= $em->getRepository('DocovaBundle:AppForms')->getViewData($app, 'xml');
					break;
				case 'Views':
					$xml .= $em->getRepository('DocovaBundle:AppViews')->getViewData($app, 'xml', $start);
					break;
				case 'Pages':
					$xml .= $em->getRepository('DocovaBundle:AppPages')->getViewData($app, 'xml');
					break;
				case 'Subforms':
					$xml .= $em->getRepository('DocovaBundle:Subforms')->getViewData($app, 'xml');
					break;
				case 'Outlines':
					$xml .= $em->getRepository('DocovaBundle:AppOutlines')->getViewData($app, 'xml');
					break;
				case 'Agents':
					$xml .= $em->getRepository('DocovaBundle:AppAgents')->getViewData($app, 'xml');
					break;
				case 'Files':
					$xml .= $em->getRepository('DocovaBundle:AppFiles')->getViewData($app, 'xml');
					break;
				case 'Layouts':
					$xml .= $em->getRepository('DocovaBundle:AppLayout')->getViewData($app, 'xml');
					break;
				default:
					$xml .= '<Result><h2>Incorrect view name</h2></Result>';
					break;
			}
			
			if (empty($app)) {
				throw new \Exception('Unspecified application source ID.');
			}
		}
		catch (\Exception $e) {
			$xml .= '<Result><h2>'. $e->getMessage() .'</h2></Result>';
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($xml);
		return $response;
	}
	
	public function AppLayoutAction(Request $request)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$layout_id = $request->query->get('FormUNID');
		$em = $this->getDoctrine()->getManager();
		$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
		if (empty($app)) {
			throw $this->createNotFoundException('Unspecified applicatoin source ID.');
		}

		$docovaAcl = $this->_docova->DocovaAcl($app);
		if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
			throw new AccessDeniedException();
		}
		$docovaAcl = null;
		
		if ($request->isMethod('POST')) 
		{
			$req = $request->request;
			$layout_id = $req->get('layoutUnid');
			$layoutName = $req->get('layoutID');
			$layoutAlias = $req->get('DEAlias');
			$frameCode = trim($req->get('frameCode'));
			if (!empty($layout_id)) {
				$layout = $em->getRepository('DocovaBundle:AppLayout')->find($layout_id);
				if (empty($layout)) {
					throw $this->createNotFoundException('Unspecified layout source ID.');
				}
			}
			else {
				$layout = new AppLayout();
			}
			
			if ($req->get('layoutIsDefault')) {
				$em->getRepository('DocovaBundle:AppLayout')->resetDefaultLayout($app_id);
				if (!empty($layout_id)) {
					$em->refresh($layout);
				}
			}
			
			$layout->setLayoutId($layoutName);
			$layout->setLayoutAlias($layoutAlias);
			$layout->setLayoutDefault($req->get('layoutIsDefault') ? true : false);
			$layout->setProhibitDesignUpdate($req->get('ProhibitDesignUpdate') == 'PDU' ? true : false);
			$layout->setDateModified(new \DateTime());
			$layout->setModifiedBy($this->user);
			if (empty($layout_id)) 
			{
				$layout->setDateCreated(new \DateTime());
				$layout->setCreatedBy($this->user);
				$layout->setApplication($app);
				$em->persist($layout);
			}
			$em->flush();
			
			if (!is_dir($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR)) {
				mkdir($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'layouts', 0775, true);
			}
			
			$layoutName = str_replace(array('/', '\\'), '', $layoutName);
			$layoutName = str_replace(' ', '', $layoutName);
			file_put_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$layoutName.'.html.twig', $frameCode);
			
			if (function_exists('opcache_reset')) {
				opcache_reset();
			}
			return $this->redirect($this->generateUrl('docova_blankcontent') . '?OpenPage&docid='.$layout->getId());
		}else{
			if ( !empty( $layout_id)){
				$layout = $em->getRepository('DocovaBundle:AppLayout')->find($layout_id);
				if (empty($layout)) {
						throw $this->createNotFoundException('Unspecified layout source ID.');
				}
				$layout_name = str_replace(array('/','\\'), '-', $layout->getLayoutId());
				$layout_name = str_replace(' ', '', $layout_name);
				if (!empty($layout) && file_exists($this->app_path.DIRECTORY_SEPARATOR.$layout->getApplication()->getId().DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$layout_name.'.html.twig'))
				{
					$html = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$layout->getApplication()->getId().DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$layout_name.'.html.twig');
					$htmlpath = 'DocovaBundle:DesignElements:'.$layout->getApplication()->getId().'/layouts/'.$layout_name.'.html.twig';
				}
			}
		}
		
		$oldcode = false;
		if ( !empty($html)){
			if(strpos(trim($html), '<frameset') === 0) {
    			$oldcode = true;
			}
		}
		
		return $this->render('DocovaBundle:AppBuilder:AppLayout.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'appId' => $app_id,
			'oldcode' => $oldcode,
			'layouthtml' => !empty($htmlpath) ? $htmlpath : '',
			'layout' => !empty($layout) ? $layout : ''
		));
	}
	
	public function appLayoutContainerAction($layout)
	{
		$this->initialize();
		if (!empty($layout))
		{
			$em = $this->getDoctrine()->getManager();
			$layout = $em->getRepository('DocovaBundle:AppLayout')->find($layout);
			$layout_name = str_replace(array('/','\\'), '-', $layout->getLayoutId());
			$layout_name = str_replace(' ', '', $layout_name);
			if (!empty($layout) && file_exists($this->app_path.DIRECTORY_SEPARATOR.$layout->getApplication()->getId().DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$layout_name.'.html.twig'))
			{
				$html = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$layout->getApplication()->getId().DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$layout_name.'.html.twig');
			}
		}
		return $this->render('DocovaBundle:AppBuilder:appLayoutContainer.html.twig', array(
			'settings' => $this->global_settings,
			'layouthtml' => !empty($html) ? $html : ''
		));
	}

	public function loadLayoutAction(Request $request, $layoutname)
	{
		$em = $this->getDoctrine()->getManager();
		$app = $request->query->get('AppID');
		$layout = $em->getRepository('DocovaBundle:AppLayout')->findLayout($layoutname, $app);
		
		return $this->render('DocovaBundle:AppBuilder:wLayoutLoader.html.twig', array(
			'appid' => $app,
			'layout' => $layout
		));
	}

	public function noContentLayoutAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:noContent.html.twig', array('user' => $this->user));
	}
	
	public function openDlgInsertLayoutAction()
	{
		return $this->render('DocovaBundle:AppBuilder:dlgInsertLayout.html.twig');
	}
	
	public function appPageBuilderAction(Request $request)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$page = $request->query->get('FormUNID');
		$em = $this->getDoctrine()->getManager();
		if (!empty($page))
		{
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (!empty($app))
			{
				$docovaAcl = $this->_docova->DocovaAcl($app);
				if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
					throw new AccessDeniedException();
				}
				$docovaAcl = null;
			}
			$page = $em->getRepository('DocovaBundle:AppPages')->findOneBy(array('id' => $page, 'application' => $app_id));
		}
		$appforms = $em->getRepository('DocovaBundle:AppForms')->getAppFormNames($app_id);
		
		return $this->render('DocovaBundle:AppBuilder:AppPageBuilder.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'appId' => $app_id,
			'appPage' => $page,
			'formNames' => $appforms,
			'gmap_key' => ($this->container->hasParameter('google_api_key') ? $this->container->getParameter('google_api_key') : ''),
			'weather_key' => ($this->container->hasParameter('weather_api_key') ? $this->container->getParameter('weather_api_key') : '')
		));
	}
	
	public function appSubformBuilderAction(Request $request)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$subform = $request->query->get('FormUNID');
		$em = $this->getDoctrine()->getManager();
		if (!empty($subform))
		{
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (!empty($app))
			{
				$docovaAcl = $this->_docova->DocovaAcl($app);
				if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
					throw new AccessDeniedException();
				}
				$docovaAcl = null;
			}
			$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('id' => $subform, 'application' => $app_id, 'Is_Custom' => true));
		}
		$appforms = $em->getRepository('DocovaBundle:AppForms')->getAppFormNames($app_id);
		return $this->render('DocovaBundle:AppBuilder:AppSubformBuilder.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'appId' => $app_id,
			'formNames' => $appforms,
			'appSubform' => $subform
		));
	}
	
	public function outlineBuilderAction(Request $request)
	{
		$content = '';
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$outline = $request->query->get('oID');
		$custcss = false;
		$outlinecss = "";
		if (!empty($outline))
		{
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (!empty($app))
			{
				$docovaAcl = $this->_docova->DocovaAcl($app);
				if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
					throw new AccessDeniedException();
				}
				$docovaAcl = null;
			}
			$outline = $em->getRepository('DocovaBundle:AppOutlines')->findOneBy(array('id' => $outline, 'application' => $app_id));
			if (!empty($outline))
			{
				$name = str_replace(array('/', '\\'), '-', $outline->getOutlineName());
				$name = str_replace(' ', '', $name);
				$content .= file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'outline'.DIRECTORY_SEPARATOR.$name.'.html.twig');


				$filepath = $this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app_id.DIRECTORY_SEPARATOR."outlines".DIRECTORY_SEPARATOR.$name.'.css';
				if (file_exists($filepath)) 
				{
					$custcss = true;
					$outlinecss = file_get_contents($filepath);
				}
			}
		}
		return $this->render('DocovaBundle:AppBuilder:OutlineBuilder.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'custcss' => $custcss,
			'appId' => $app_id,
			'outlinecss' => $outlinecss,
			'appOutline' => $outline,
			'outlineContent' => $content
		));
	}
	
	public function openDlgIconPickerAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:dlgIconPicker.html.twig', array('user' => $this->user));
	}
	
	public function appJSBuilderAction(Request $request)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$js = $request->query->get('FormUNID');
		if (!empty($js))
		{
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (!empty($app))
			{
				$docovaAcl = $this->_docova->DocovaAcl($app);
				if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
					throw new AccessDeniedException();
				}
				$docovaAcl = null;
			}
			$js = $em->getRepository('DocovaBundle:AppJavaScripts')->findOneBy(array('id' => $js, 'application' => $app_id));
		}
		return $this->render('DocovaBundle:AppBuilder:AppJSBuilder.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'appId' => $app_id,
			'appJS' => $js
		));
	}
	
	public function appAgentBuilderAction(Request $request)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$agent = $request->query->get('FormUNID');
		if (!empty($agent)) 
		{
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (!empty($app))
			{
				$docovaAcl = $this->_docova->DocovaAcl($app);
				if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
					throw new AccessDeniedException();
				}
				$docovaAcl = null;
			}
			$agent = $em->getRepository('DocovaBundle:AppAgents')->findOneBy(array('id' => $agent, 'application' => $app_id));
		}
		return $this->render('DocovaBundle:AppBuilder:AppAgentBuilder.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'document' => $agent,
			'isAgent' => true
		));
	}
	
	public function appScriptLibraryBuilderAction(Request $request)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$scriptdoc = $request->query->get('FormUNID');
		if (!empty($scriptdoc)) 
		{
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (!empty($app))
			{
				$docovaAcl = $this->_docova->DocovaAcl($app);
				if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
					throw new AccessDeniedException();
				}
				$docovaAcl = null;
			}
			$scriptdoc = $em->getRepository('DocovaBundle:AppPhpScripts')->findOneBy(array('id' => $scriptdoc, 'application' => $app_id));
		}
		return $this->render('DocovaBundle:AppBuilder:AppScriptLibraryBuilder.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'document' => $scriptdoc,
			'isAgent' => false
		));
	}
	
	public function appFileAction(Request $request)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$file = $request->query->get('FileUNID');
		$file_content = '';
		if (!empty($file)) 
		{
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (!empty($app))
			{
				$docovaAcl = $this->_docova->DocovaAcl($app);
				if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
					throw new AccessDeniedException();
				}
				$docovaAcl = null;
			}
			$file = $em->getRepository('DocovaBundle:AppFiles')->findOneBy(array('id' => $file, 'application' => $app_id));

			$filename = str_replace(array('/', '\\'), '-', $file->getFileName());
			$filename = str_replace(' ', '', $filename);

			if (!empty($file) && file_exists($this->app_path.'../../public/images'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.$filename)) 
			{
				$file_content = file_get_contents($this->app_path.'../../public/images'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.$filename);
				$file_content = base64_encode($file_content);
			}
			
			if ($request->isMethod('POST'))
			{
				$response = new Response();
				$response->setContent('url;' . $this->generateUrl('docova_blankcontent') . '?OpenPage&docid=' . $file->getId());
				return $response;
			}
		}
		return $this->render('DocovaBundle:AppBuilder:AppFile.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'appFile' => $file,
			'fileContent' => $file_content,
			'fileSize' => $file_content ? strlen(base64_decode($file_content)) : ''
		));
	}
	
	public function appCSSBuilderAction(Request $request)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$css = $request->query->get('FormUNID');
		if (!empty($css)) 
		{
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (!empty($app))
			{
				$docovaAcl = $this->_docova->DocovaAcl($app);
				if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
					throw new AccessDeniedException();
				}
				$docovaAcl = null;
			}
			$css = $em->getRepository('DocovaBundle:AppCss')->findOneBy(array('id' => $css, 'application' => $app_id));
		}
		return $this->render('DocovaBundle:AppBuilder:AppCSSBuilder.html.twig', array(
			'user' => $this->user,
			'appCss' => $css
		));
	}
	
	public function appWorkflowBuilderAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$workflow = $request->query->get('wfID');
		$app_id = $request->query->get('AppID');
		$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
		if (!empty($app))
		{
			$docovaAcl = $this->_docova->DocovaAcl($app);
			if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
				throw new AccessDeniedException();
			}
			$docovaAcl = null;
		}
		if (!empty($workflow)) 
		{
			$workflow = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('id' => $workflow, 'application' => $app_id));
			if (empty($workflow)) {
				$workflow = null;
			}
		}
		$messages = $em->getRepository('DocovaBundle:SystemMessages')->findBy(array('Systemic' => false, 'Trash' => false), array('Message_Name' => 'ASC'));
		return $this->render('DocovaBundle:AppBuilder:AppWorkflowBuilder.html.twig', array(
			'user' => $this->user,
			'messages' => $messages,
			'workflow' => $workflow ? $workflow->getId() : null
		));
	}
	
	public function appWidgetBuilderAction(Request $request, $widgetid)
	{
		$widget = null;
		$this->initialize();
		if (!$this->user->getUserProfile()->getCanCreateApp() && !$this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
		{
			throw $this->createAccessDeniedException('No Access');
		}

		if (!empty($widgetid))
		{
			$em = $this->getDoctrine()->getManager();
			$widget = $em->getRepository('DocovaBundle:Widgets')->find($widgetid);
			if (empty($widget)) {
				throw $this->createNotFoundException('Oops! Widget was not found.');
			}
		}
		
		return $this->render('DocovaBundle:AppBuilder:WidgetBuilder.html.twig', [
			'user' => $this->user,
			'widget' => $widget,
		    'gmap_key' => ($this->container->hasParameter('google_api_key') ? $this->container->getParameter('google_api_key') : ''),
		    'weather_key' => ($this->container->hasParameter('weather_api_key') ? $this->container->getParameter('weather_api_key') : '')
		]);
	}
	
	public function blankOutlineAction()
	{
		$response = new Response();
		$response->setContent('<div id="divOutlineMsg" style="width: 100%; height:100%;font: normal 11px verdana, arial, sans-serif; color: #0000ff;text-align:center;display:;"></div>');
		return $response;
	}
	
	public function readContentAction(Request $request, $document)
	{
		$type = $request->query->get('DesignElement');
		$app_id = $request->query->get('AppID');
		$em = $this->getDoctrine()->getManager();
		$response = new Response();
		$this->initialize();
		try {
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (!empty($app))
			{
				$docovaAcl = $this->_docova->DocovaAcl($app);
				if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
					throw new \Exception('Access denied.');
				}
				$docovaAcl = null;
			}
			
			switch ($type)
			{
				case 'Form':
					$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' => $document, 'application' => $app_id, 'trash' => false));
					if (empty($form)) {
						throw new \Exception('Unspecified form source ID.');
					}
					$name = str_replace(array('/', '\\'), '-', $form->getFormName());
					$name = str_replace(' ', '', $name);
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'FORM'.DIRECTORY_SEPARATOR.$name.'.html.twig')) {
						$content = "<div id='AppID'>$app_id</div><div id='DEName'>{$form->getFormName()}</div><div id='DEType'>Form</div>";
						$content .= "<div id='DESubType'></div><div id='DEAlias'>{$form->getFormAlias()}</div><div id='DELastModifiedBy'>{$form->getModifiedBy()->getUserNameDnAbbreviated()}</div>";
						$content .= "<div id='DEKey'></div><div id='DocTypeID'></div><div id='GenerateMobile'>0</div><div id='ProhibitDesignUpdate'>".($form->getPDU() ? 'PDU' : '')."</div><div id='DEProperties'></div>";
						$content .= '<div id="DEHTML">';
						$content .= file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'FORM'.DIRECTORY_SEPARATOR.$name.'.html.twig');
						$content .= '</div>';
						$content .= '<div id="DEHTML_m">';
						if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'FORM'.DIRECTORY_SEPARATOR.$name.'_m.html.twig')) {
			 			  $content .= file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'FORM'.DIRECTORY_SEPARATOR.$name.'_m.html.twig');
						}
						$content .= '</div>';
						$content .= '<div id="DECSS">';
						if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'FORM'.DIRECTORY_SEPARATOR.$name.'_style.json')) {
							$cssjson = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'FORM'.DIRECTORY_SEPARATOR.$name.'_style.json');
							

							$content .= base64_encode($cssjson);
						}

						$content .= '</div>';
						$content .= '<div id="DECode">';
						if (file_exists(($this->app_path.DIRECTORY_SEPARATOR.'../../public/js/custom'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'FORM' . DIRECTORY_SEPARATOR . 'TEMPLATE_'.$name . '.js'))) {
							$js = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.'../../public/js/custom'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'FORM' . DIRECTORY_SEPARATOR . 'TEMPLATE_'.$name . '.js');
							$js = base64_encode($js);
							$content .= $js;
						}
						$content .= '</div><div id="DEAddCSS"></div><div id="DEAddJS"></div>';
						$response->setContent($content);
						$content = null;
					}
					break;
				case 'Page':
					$page = $em->getRepository('DocovaBundle:AppPages')->findOneBy(array('id' => $document, 'application' => $app_id));
					if (empty($page)) {
						throw new \Exception('Unspecified page source ID.');
					}
					$name = str_replace(array('/', '\\'), '-', $page->getPageName());
					$name = str_replace(' ', '', $name);
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'PAGE'.DIRECTORY_SEPARATOR.$name.'.html.twig')) {
						$content = "<div id='AppID'>$app_id</div><div id='DEName'>{$page->getPageName()}</div><div id='DEType'>Page</div>";
						$content .= "<div id='DESubType'></div><div id='DEAlias'>{$page->getPageAlias()}</div><div id='DELastModifiedBy'>{$page->getModifiedBy()->getUserNameDnAbbreviated()}</div>";
						$content .= "<div id='DEKey'></div><div id='DocTypeID'></div><div id='GenerateMobile'>0</div><div id='ProhibitDesignUpdate'>".($page->getPDU() ? 'PDU' : '')."</div>";
						$content .= '<div id="DEProperties"><PageBackgroundColor>'. ($page->getBackgroundColor() ? $page->getBackgroundColor() : "#ffffff") .'</PageBackgroundColor></div>';
						$content .= '<div id="DEHTML">';
						$content .= file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'PAGE'.DIRECTORY_SEPARATOR.$name.'.html.twig');
						$content .= '</div>';
						$content .= '<div id="DEHTML_m">';
						if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'PAGE'.DIRECTORY_SEPARATOR.$name.'_m.html.twig')) {
						    $content .= file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'PAGE'.DIRECTORY_SEPARATOR.$name.'_m.html.twig');						    
						}
						$content .= '</div>';
						$content .= '<div id="DECSS">';
						if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'PAGE'.DIRECTORY_SEPARATOR.$name.'_style.json')) {
							$cssjson = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'PAGE'.DIRECTORY_SEPARATOR.$name.'_style.json');
							

							$content .= base64_encode($cssjson);
						}
						$content .= '</div>';
						$content .= '<div id="DECode">';
						if (file_exists(($this->app_path.DIRECTORY_SEPARATOR.'../../public/js/custom'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'PAGE' . DIRECTORY_SEPARATOR . 'TEMPLATE_'.$name . '.js'))) {
							$js = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.'../../public/js/custom'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'PAGE' . DIRECTORY_SEPARATOR . 'TEMPLATE_'.$name . '.js');
							$js = base64_encode($js);
							$content .= $js;
						}
						$content .= '</div><div id="DEAddCSS"></div><div id="DEAddJS"></div>';
						$response->setContent($content);
						$content = null;
					}
					break;
				case 'Subform':
					$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('id' => $document, 'application' => $app_id, 'Is_Custom' => true));
					if (empty($subform)) {
						throw new \Exception('Unspecified page source ID.');
					}
					$name = str_replace(array('/', '\\'), '-', $subform->getFormFileName());
					$name = str_replace(' ', '', $name);
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'SUBFORM'.DIRECTORY_SEPARATOR.$name.'.html.twig')) {
						$content = "<div id='AppID'>$app_id</div><div id='DEName'>{$subform->getFormFileName()}</div><div id='DEType'>Subform</div>";
						$content .= "<div id='DESubType'></div><div id='DEAlias'>{$subform->getFormName()}</div><div id='DELastModifiedBy'>{$subform->getModifiedBy()->getUserNameDnAbbreviated()}</div>";
						$content .= "<div id='DEKey'></div><div id='DocTypeID'></div><div id='GenerateMobile'>0</div><div id='ProhibitDesignUpdate'>".($subform->getPDU() ? 'PDU' : '')."</div><div id='DEProperties'></div>";
						$content .= '<div id="DEHTML">';
						$content .= file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'SUBFORM'.DIRECTORY_SEPARATOR.$name.'.html.twig');
						$content .= '</div>';
						$content .= '<div id="DEHTML_m">';
						if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'SUBFORM'.DIRECTORY_SEPARATOR.$name.'_m.html.twig')) {
    							$content .= file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'SUBFORM'.DIRECTORY_SEPARATOR.$name.'_m.html.twig');
						}
						$content .= '</div>';						
						$content .= '<div id="DECSS">';
						if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'SUBFORM'.DIRECTORY_SEPARATOR.$name.'_style.json')) {
						    $cssjson = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'SUBFORM'.DIRECTORY_SEPARATOR.$name.'_style.json');
						    
						    
						    $content .= base64_encode($cssjson);
						}
						$content .= '</div>';
						$content .= '<div id="DECode">';
						if (file_exists(($this->app_path.DIRECTORY_SEPARATOR.'../../public/js/custom'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'SUBFORM' . DIRECTORY_SEPARATOR . 'TEMPLATE_'.$name . '.js'))) {
						    $js = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.'../../public/js/custom'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'SUBFORM' . DIRECTORY_SEPARATOR . 'TEMPLATE_'.$name . '.js');
						    $js = base64_encode($js);
						    $content .= $js;
						}
						$content .= '</div><div id="DEAddCSS"></div><div id="DEAddJS"></div>';
						$response->setContent($content);
						$content = null;
					}
					break;
				case 'Outline':
					$outline = $em->getRepository('DocovaBundle:AppOutlines')->findOneBy(array('id' => $document, 'application' => $app_id));
					if (empty($outline)) {
						throw new \Exception('Unspecified outline source ID.');
					}
					$name = str_replace(array('/', '\\'), '-', $outline->getOutlineName());
					$name = str_replace(' ', '', $name);
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'outline'.DIRECTORY_SEPARATOR.$name.'.html.twig')) {
						$content = "<div><div id='AppID'>$app_id</div><div id='DEName'>{$outline->getOutlineName()}</div><div id='DEType'>Outline</div>";
						$content .= "<div id='DEAlias'>{$outline->getOutlineAlias()}</div><div id='DELastModifiedBy'>{$outline->getModifiedBy()->getUserNameDnAbbreviated()}</div>";
						$content .= "<div id='DEKey'></div><div id='ProhibitDesignUpdate'>".($outline->getPDU() ? 'PDU' : '')."</div>";
						$content .= '<div id="divOutline">';
						$content .= file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'outline'.DIRECTORY_SEPARATOR.$name.'.html.twig');
						$content .= '</div></div>';
						$response->setContent($content);
						$content = null;
					}
					break;
				case 'ScriptLibrary':
					$agent = $em->getRepository('DocovaBundle:AppPhpScripts')->findOneBy(array('id' => $document, 'application' => $app_id));
					if (empty($agent)) {
						throw new \Exception('Unspecified PHP Script Library source ID.');
					}
					$appdir = 'A'.str_replace('-', '', $app_id);
					$name = str_replace(array('/', '\\'), '-', $agent->getPhpName());
					$name = str_replace(' ', '', $name);
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.'../../../Agents/'.DIRECTORY_SEPARATOR.$appdir."/ScriptLibraries".DIRECTORY_SEPARATOR.$name.'.php')) {
						$content = "<div id='AppID'>$app_id</div><div id='DEName'>{$agent->getPhpName()}</div><div id='DEType'>ScriptLibrary</div>";
						$content .= "<div id='DESubType'>PHP</div><div id='DEAlias'></div><div id='DELastModifiedBy'>{$agent->getModifiedBy()->getUserNameDnAbbreviated()}</div>";
						$content .= "<div id='DEKey'></div><div id='DocTypeID'></div><div id='GenerateMobile'>0</div><div id='ProhibitDesignUpdate'>".($agent->getPDU() ? 'PDU' : '')."</div><div id='DEProperties'></div>";
						$content .= '<div id="DEHTML"></div><div id="DECSS"></div><div id="DECode">';
						$agent = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.'../../../Agents'.DIRECTORY_SEPARATOR. $appdir . "/ScriptLibraries". DIRECTORY_SEPARATOR . $name . '.php');
						$agent = base64_encode(rawurlencode($agent));
						$content .= $agent;
						$content .= '</div><div id="DEAddCSS"></div><div id="DEAddJS"></div>';
						$response->setContent($content);
						$content = null;
					}
					break;
				case 'JS':
					$js = $em->getRepository('DocovaBundle:AppJavaScripts')->findOneBy(array('id' => $document, 'application' => $app_id));
					if (empty($js)) {
						throw new \Exception('Unspecified JavaScript source ID.');
					}
					$name = str_replace(array('/', '\\'), '-', $js->getJsName());
					$name = str_replace(' ', '', $name);
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.'../../public/js/custom'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.$name.'.js')) {
						$content = "<div id='AppID'>$app_id</div><div id='DEName'>{$js->getJsName()}</div><div id='DEType'>JS</div>";
						$content .= "<div id='DESubType'></div><div id='DEAlias'></div><div id='DELastModifiedBy'>{$js->getModifiedBy()->getUserNameDnAbbreviated()}</div>";
						$content .= "<div id='DEKey'></div><div id='DocTypeID'></div><div id='GenerateMobile'>0</div><div id='ProhibitDesignUpdate'>".($js->getPDU() ? 'PDU' : '')."</div><div id='DEProperties'></div>";
						$content .= '<div id="DEHTML"></div><div id="DECSS"></div><div id="DECode">';
						$js = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.'../../public/js/custom' . DIRECTORY_SEPARATOR . $app_id . DIRECTORY_SEPARATOR . $name . '.js');
						$js = base64_encode(rawurlencode($js));
						$content .= $js;
						$content .= '</div><div id="DEAddCSS"></div><div id="DEAddJS"></div>';
						$response->setContent($content);
						$content = null;
					}
					break;
				case 'Agent':
					$agent = $em->getRepository('DocovaBundle:AppAgents')->findOneBy(array('id' => $document, 'application' => $app_id));
					if (empty($agent)) {
						throw new \Exception('Unspecified JavaScript source ID.');
					}
					$name = str_replace(array('/', '\\', '-'), '', $agent->getAgentName());
					$name = str_replace(' ', '', $name);
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'AGENTS'.DIRECTORY_SEPARATOR.$name.'.html.twig')) {
						$content = "<div id='AppID'>$app_id</div><div id='DEName'>{$agent->getAgentName()}</div><div id='DEType'>Agent</div>";
						$content .= "<div id='DESubType'>".($agent->getAgentType()?'PHP':'Script')."</div><div id='DEAlias'>{$agent->getAgentAlias()}</div><div id='DELastModifiedBy'>{$agent->getModifiedBy()->getUserNameDnAbbreviated()}</div>";
						$content .= "<div id='DEProperties'><div id='agentschedule'>{$agent->getAgentSchedule()}</div><div id='startdayofmonth'>{$agent->getStartDayOfMonth()}</div><div id='startweekday'>{$agent->getStartWeekDay()}</div>";
						$content .= "<div id='starthour'>{$agent->getStartHour()}</div><div id='startminutes'>{$agent->getStartMinutes()}</div><div id='starthourampm'>{$agent->getStartHourAmPm()}</div>";
						$content .= "<div id='runas'>{$agent->getRunAgentAs()}</div><div id='runtimesecuritylevel'>{$agent->getRuntimeSecurityLevel()}</div><div id='intervalhours'>{$agent->getIntervalHours()}</div>";
						$content .= "<div id='intervalminutes'>{$agent->getIntervalMinutes()}</div></div>";
						$content .= "<div id='DEKey'></div><div id='DocTypeID'></div><div id='GenerateMobile'>0</div><div id='ProhibitDesignUpdate'>".($agent->getPDU() ? 'PDU' : '')."</div>";
						$content .= '<div id="DEHTML"></div><div id="DECSS"></div><div id="DECode">';
						$agent = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'AGENTS'.DIRECTORY_SEPARATOR.$name.'.html.twig');
						$agent = base64_encode(rawurlencode($agent));
						$content .= $agent;
						$content .= '</div><div id="DEAddCSS"></div><div id="DEAddJS"></div>';
						$response->setContent($content);
						$content = null;
					}
					break;
				case 'CSS':
					$css = $em->getRepository('DocovaBundle:AppCss')->findOneBy(array('id' => $document, 'application' => $app_id));
					if (empty($css)) {
						throw new \Exception('Unspecified CSS source ID.');
					}
					$name = str_replace(array('/', '\\'), '-', $css->getCssName());
					$name = str_replace(' ', '', $name);
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.'../../public/css/custom'.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.$name.'.css')) {
						$content = "<div id='AppID'>$app_id</div><div id='DEName'>{$css->getCssName()}</div><div id='DEType'>CSS</div>";
						$content .= "<div id='DESubType'></div><div id='DEAlias'></div><div id='DELastModifiedBy'>{$css->getModifiedBy()->getUserNameDnAbbreviated()}</div>";
						$content .= "<div id='DEKey'></div><div id='DocTypeID'></div><div id='GenerateMobile'>0</div><div id='ProhibitDesignUpdate'>".($css->getPDU() ? 'PDU' : '')."</div><div id='DEProperties'></div>";
						$content .= '<div id="DEHTML"></div><div id="DECode"></div><div id="DECSS">';
						$css = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.'../../public/css/custom' . DIRECTORY_SEPARATOR . $app_id . DIRECTORY_SEPARATOR . $name . '.css');
						$css = base64_encode($css);
						$content .= $css;
						$content .= '</div><div id="DEAddCSS"></div><div id="DEAddJS"></div>';
						$response->setContent($content);
						$content = null;
					}
					break;
				case 'Workflow':
					$workflow = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('id' => $document, 'application' => $app_id));
					if (empty($workflow)) {
						throw new \Exception('Unspecified Workflow source ID.');
					}
					$content = "<div><div id='WorkflowName'>{$workflow->getWorkflowName()}</div><div id='WorkflowDescription'>{$workflow->getDescription()}</div>";
					$content .= "<div id='WorkflowKey'>{$workflow->getId()}</div><div id='WorkflowDocKey'>{$workflow->getId()}</div>";
					$content .= '<div id="CustomizeAction">'.($workflow->getAuthorCustomization() ? 'Allow document author to customize this process' : '').'</div>';
					$content .= '<div id="EnableImmediateRelease">'.($workflow->getBypassRleasing() ? 'Allow to release bypassing workflow' : '').'</div>';
					$content .= '<div id="DefaultWorkflow">'.($workflow->getUseDefaultDoc() ? 'Use as default document workflow if none specified' : '').'</div>';
					$html = array();
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'WORKFLOWS'.DIRECTORY_SEPARATOR.$workflow->getId().'.html.twig')) 
					{
						$html = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'WORKFLOWS'.DIRECTORY_SEPARATOR.$workflow->getId().'.html.twig');
						$html = explode('@@@@@', $html);
					}
					//$content .= '<div id="divStartStep">'.(!empty($html[0]) ? $html[0] : '').'</div>';
					$content .= '<ul class="WorkflowItems">'.(!empty($html[0]) ? $html[0] : '').'</ul>';
					//$content .= '<div id="divEndStep">'.(!empty($html[2]) ? $html[2] : '').'</div></div>';
					$response->setContent($content);
					break;
				case 'Widget':
					$widget = $em->getRepository('DocovaBundle:Widgets')->findOneBy(['id' => $document, 'isCustom' => true]);
					if (empty($widget)) {
						throw new \Exception('Widget not found!');
					}
					$name = $widget->getSubformName();
					if (file_exists($this->app_path.DIRECTORY_SEPARATOR.'Widgets'.DIRECTORY_SEPARATOR.$name.'.html.twig')) {
						$content = "<div id='DEName'>{$widget->getWidgetName()}</div>";
						$content .= "<div id='DEDescription'>{$widget->getDescription()}</div><div id='DELastModifiedBy'>{$widget->getModifiedBy()->getUserNameDnAbbreviated()}</div>";
						$content .= '<div id="DEHTML">';
						$content .= file_get_contents($this->app_path.DIRECTORY_SEPARATOR.'Widgets'.DIRECTORY_SEPARATOR.$name.'.html.twig');
						$content .= '</div>';
						$response->setContent($content);
						$content = null;
					}
					break;
				default:
					throw new \Exception('Unrecognised Design Element type.');
			}
		}
		catch (\Exception $e) {
			$response->setContent("<h2><b style='color:red'>ERROR:</b> {$e->getMessage()}</h2>");
		}
		
		return $response;
	}
	
	public function previewFormAction(Request $request, $formid)
	{
		$this->initialize();
		$app_id = $request->query->get('AppID');
		$type = strtoupper($request->query->get('DesignElement'));
		$em = $this->getDoctrine()->getManager();
		$viewname = '';
		$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
		if (!empty($app))
		{
			$docovaAcl = $this->_docova->DocovaAcl($app);
			if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
				throw new AccessDeniedException();
			}
			$docovaAcl = null;
		}
		if ($type == 'FORM') {
			$document = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' => $formid, 'application' => $app_id, 'trash' => false));
			if (!empty($document)) {
				$name = $document->getFormName();
			}
		}
		elseif ($type == 'PAGE') {
			$document = $em->getRepository('DocovaBundle:AppPages')->findOneBy(array('id' => $formid, 'application' => $app_id));
			if (!empty($document)) {
				$name = $document->getPageName();
			}
		}
		$name = str_replace(array('/', '\\'), '-', $name);
		$name = str_replace(' ', '', $name);
		if (!empty($document) && file_exists($this->app_path.DIRECTORY_SEPARATOR.$app_id.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$name.'.html.twig')) 
		{
			if ($type === 'PAGE') {
				$viewname = "$app_id/templates/$type/$name.html.twig";
			}
			else {
				$viewname = "$app_id/$name.html.twig";
			}
		}
		
		return $this->render('DocovaBundle:AppBuilder:DefaultPreview.html.twig', array(
			'viewname' => $viewname,
			'user' => $this->user,
			'form' => $type === 'FORM' ? $document : null
		));
	}
	
	public function appWorkflowServicesAction(Request $request)
	{
		$this->initialize();
		$app = $request->query->get('AppID');
		$em = $this->getDoctrine()->getManager();
		$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		if (empty($app)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}

		$docovaAcl = $this->_docova->DocovaAcl($app);
		if (!($docovaAcl->isManager() || $docovaAcl->isDesigner())) {
			throw new AccessDeniedException();
		}
		$docovaAcl = null;
		
		$post = $request->getContent();
		$xml = new \DOMDocument();
		$xml->loadXML(html_entity_decode($post));
		$action = $xml->getElementsByTagName('Action')->item(0)->nodeValue;
		$results = false;
		if (method_exists($this, "wfServices$action"))
		{
			$results = call_user_func(array($this, "wfServices$action"), $xml, $app);
		}

		if (empty($results) || !$results instanceof \DOMDocument)
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
				$newnode = $results->createElement('Result', 'Operation could not be completed due to a system error. Check the logs for more details.');
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
	
	public function openAddAppDlgAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:AppBuilder:dlgAddApp.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings
		));
	}
	
	public function openImportDataDlgAction()
	{
		$this->initialize();
		$params = $list = array();
		$migrator_path = $this->container->getParameter('datamigrator_path');
		if (file_exists(realpath($migrator_path . 'DOCOVA_SE_Migrator.exe.config')))
		{
			chdir($migrator_path);
			$res = system('getapplist.bat');
			if (empty($res)) {
				if (file_exists(realpath($migrator_path . 'application_list.txt')))
				{
					$list = file_get_contents(realpath($migrator_path . 'application_list.txt'));
					$list = !empty($list) ? explode(PHP_EOL, $list) : array();
				}
			}
			$xml = new \DOMDocument();
			$xml->load(realpath($migrator_path . 'DOCOVA_SE_Migrator.exe.config'));
			$xpath = new \DOMXPath($xml);
			$params['se_host'] = trim($xpath->query('//setting[@name="TEITRHost"]')->item(0)->nodeValue);
			$params['se_database'] = $this->container->getParameter('database_name');
			$params['se_user'] = trim($xpath->query('//setting[@name="TEITRUser"]')->item(0)->nodeValue);
			$params['se_password'] = trim($xpath->query('//setting[@name="TEITRPassword"]')->item(0)->nodeValue);
			$params['se_webroot'] = trim($xpath->query('//setting[@name="TEITRWebRoot"]')->item(0)->nodeValue);
			$params['se_attachment'] = trim($xpath->query('//setting[@name="TEITRAttachmentPath"]')->item(0)->nodeValue);
			$params['domino_path'] = trim($xpath->query('//setting[@name="DOCOVAHome"]')->item(0)->nodeValue);
			$params['domino_user'] = trim($xpath->query('//setting[@name="DOCOVAUser"]')->item(0)->nodeValue);
			$params['domino_password'] = trim($xpath->query('//setting[@name="DOCOVAPassword"]')->item(0)->nodeValue);
			$params['docova_dws'] = trim($xpath->query('//setting[@name="DOCOVATEITRMigrator_DWS_DOCOVAWebServices"]')->item(0)->nodeValue);
		}
		return $this->render('DocovaBundle:AppBuilder:dlgImportAppData.html.twig', array(
			'user' => $this->user,
			'parameters' => $params,
			'applist' => $list
		));
	}
	
	public function openImportProgressBarAction(Request $request)
	{
		$this->initialize();
		$appid = $request->query->get('AppId');
		$job_id = $request->query->get('job');
		$em = $this->getDoctrine()->getManager();
		$views = $em->getRepository('DocovaBundle:AppViews')->getAllViewIds($appid);
		return $this->render('DocovaBundle:AppBuilder:dlgImportProgressBar.html.twig', array(
			'user' => $this->user,
			'views' => implode("','", $views),
			'jobid' => $job_id
		));
	}
	
	public function getImportDataStatusAction(Request $request)
	{
		$job_id = $request->query->get('job');
		$job_id = !empty($job_id) ? "autorun_$job_id" : 'autorun';
		$output = array(
			'total' => 0,
			'count' => 0
		);
		$status_log = $this->container->getParameter('datamigrator_path') . $job_id.'.txt';
		$complete_log = $this->container->getParameter('datamigrator_path') . $job_id.'.complete';
		if (file_exists($status_log) && !file_exists($complete_log))
		{
			$content = explode(',', trim(file_get_contents($status_log)));
			$output['total'] = !empty($content[1]) && is_numeric($content[1]) ? intval($content[1]) : 1;
			$output['count'] = !empty($content[0]) && is_numeric($content[0]) ? intval($content[0]) : 1;
		}
		elseif (file_exists($status_log) && file_exists($complete_log)) {
			$content = explode(',', trim(file_get_contents($status_log)));
			if (!empty($content[0])) {
				$output['total'] = intval($content[0]);
				$output['count'] = intval($content[1]);
			}
			else {
				$output['total'] = 0;
				$output['count'] = intval($content[1]);
			}
			@unlink($complete_log);
			@unlink($status_log);
		}
		elseif (!file_exists($status_log) && file_exists($complete_log)) {
			$output['total'] = 0;
			$output['count'] = 0;
			@unlink($complete_log);
		}
		else {
			$output['total'] = 100;
			$output['count'] = 1;
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'Application/json');
		$response->setContent(json_encode($output));
		return $response;
	}
	
	public function importViewsDataAction(Request $request, $appid)
	{
		$output = array();
		$em = $this->getDoctrine()->getManager();
		try {
			$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appid, 'isApp' => true, 'Trash' => false));
			if (empty($application))
				throw new \Exception('Unspecified application source ID.');
			$view_id = $request->request->get('viewid');
			$kernel = new Application($this->get('kernel'));
			$kernel->setAutoExit(false);
			
			$inputs = new ArrayInput(array(
				'command' => 'docova:importviewdata',
				'app' => $application->getId(),
				'view' => $view_id
			));
			
			$buffer = new BufferedOutput();
			$kernel->run($inputs, $buffer);
			if (false !== stripos($buffer->fetch(), 'Status: OK, Added:'))
			{
				$output = array('Status' => 'OK');
			}
			else {
				$output = array(
					'Status' => 'FAILED',
					'ErrMsg' => trim($buffer->fetch())
				);
			}
		}
		catch (\Exception $e) {
			$output = array(
				'status' => 'FAILED',
				'msg' => $e->getMessage()
			);
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($output));
		return $response;
	}
	
	public function importAppAclAction($appid)
	{
		$output = array();
		try {
			$application = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appid, 'isApp' => true, 'Trash' => false));
			if (empty($application))
				throw new \Exception('Unspecified application source ID.');
			
			$kernel = new Application($this->get('kernel'));
			$kernel->setAutoExit(false);
				
			$inputs = new ArrayInput(array(
				'command' => 'docova:importappacl',
				'app' => $application->getId()
			));
				
			$buffer = new BufferedOutput();
			$kernel->run($inputs, $buffer);
			if (false !== stripos($buffer->fetch(), 'Status: OK, Return:'))
			{
				$output = array('Status' => 'OK');
			}
			else {
				$output = array(
					'Status' => 'FAILED',
					'ErrMsg' => trim($buffer->fetch())
				);
			}
		}
		catch (\Exception $e) {
			$output = array(
				'status' => 'FAILED',
				'msg' => $e->getMessage()
			);
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($output));
		return $response;
	}
	
	public function buildDocLinksAction($appid)
	{
		$output = array();
		try {
			$application = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appid, 'isApp' => true, 'Trash' => false));
			if (empty($application))
				throw new \Exception('Unspecified application source ID.');
					
			$kernel = new Application($this->get('kernel'));
			$kernel->setAutoExit(false);
		
			$inputs = new ArrayInput(array(
				'command' => 'docova:upgradedoclinks',
				'application' => $application->getId()
			));
		
			$buffer = new BufferedOutput();
			$kernel->run($inputs, $buffer);
			if (false !== stripos($buffer->fetch(), 'Updated application doclinks.  Resolved:'))
			{
				$output = array('Status' => 'OK');
			}
			else {
				$output = array(
					'Status' => 'FAILED',
					'ErrMsg' => trim($buffer->fetch())
				);
			}
		}
		catch (\Exception $e) {
			$output = array(
				'status' => 'FAILED',
				'msg' => $e->getMessage()
			);
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($output));
		return $response;
	}
	
	/**
	 * Generate workflow service
	 * 
	 * @param \DOMDocument $post
	 * @param \Docova\DocovaBundle\Entity\Libraries $app
	 * @return \DOMDocument
	 */
	private function wfServicesSAVEWORKFLOWDEFINITION($post, $app)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$details = array(
				'WorkflowName' => trim($post->getElementsByTagName('WorkflowName')->item(0)->nodeValue),
				'WorkflowDescription' => trim($post->getElementsByTagName('WorkflowDescription')->item(0)->nodeValue),
				'CustomizeAction' => $post->getElementsByTagName('WorkflowCustomizeAction')->item(0)->nodeValue === '1' ? true : false,
				'EnableImmediateRelease' => $post->getElementsByTagName('WorkflowEnableImmediateRelease')->item(0)->nodeValue === '1' ? true : false,
				'DefaultWorkflow' => $post->getElementsByTagName('WorkflowDefaultWorkflow')->item(0)->nodeValue === '1' ? true : false,
				'Application' => $app->getId()
			);
			if (!empty($post->getElementsByTagName('WorkflowDocKey')->item(0)->nodeValue)) 
			{
				$wf = trim($post->getElementsByTagName('WorkflowDocKey')->item(0)->nodeValue);
				$wf = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Workflow')->findOneBy(array('id' => $wf, 'application' => $app->getId()));
				if (empty($wf)) {
					throw new \Exception('Unspecified workflow source ID');
				}
				$details['Workflow'] = $wf;
				$wf = null;
			}

			$workflow = $this->get('docova.workflowservices');
			$workflow = $workflow->generateWorkflow($details);
			
			$root = $output->appendChild($output->createElement('Results'));
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode = $output->createElement('Result', 'OK');
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode = $output->createElement('Result', $workflow->getId());
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Generate a workflow step/item service
	 * 
	 * @param \DOMDocument $post
	 * @param \Docova\DocovaBundle\Entity\Libraries $app
	 * @return \DOMDocument
	 */
	private function wfServicesSAVESTEP($post, $app)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$workflow = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('id' => $post->getElementsByTagName('WorkflowDocKey')->item(0)->nodeValue, 'application' => $app->getId()));
			if (empty($workflow)) 
			{
				throw new \Exception('Unspecified application workflow source ID.');
			}

			$action = trim($post->getElementsByTagName('wfStepAction')->item(0)->nodeValue);

			$runjson = '';
			if ( $action == "Start")
			{

				$runjson = trim($post->getElementsByTagName('wfrunjson')->item(0)->nodeValue);
			}

			
			$details = array(
				'wfTitle' => trim($post->getElementsByTagName('wfStepTitle')->item(0)->nodeValue),
				'wfAction' => trim($post->getElementsByTagName('wfStepAction')->item(0)->nodeValue),
				'wfReviewerApproverSelect' => $post->getElementsByTagName('wfReviewerApproverSelect')->item(0)->nodeValue,
				'wfType' => !empty($post->getElementsByTagName('wfType')->item(0)->nodeValue) ? $post->getElementsByTagName('wfType')->item(0)->nodeValue : null,
				'wfCompleteAny' => !is_null($post->getElementsByTagName('wfCompleteAny')->item(0)) ? $post->getElementsByTagName('wfCompleteAny')->item(0)->nodeValue : null,
				'wfParticipantTokens' => !empty($post->getElementsByTagName('wfParticipantTokens')->item(0)->nodeValue) ? $post->getElementsByTagName('wfParticipantTokens')->item(0)->nodeValue : null,
				'wfParticipantFormula' => !empty($post->getElementsByTagName('wfParticipantFormula')->item(0)->nodeValue) ? $post->getElementsByTagName('wfParticipantFormula')->item(0)->nodeValue : null,
				'wfCompleteCount' => !empty($post->getElementsByTagName('wfCompleteCount')->length) ? $post->getElementsByTagName('wfCompleteCount')->item(0)->nodeValue : null,
				'wfOptionalComments' => !empty($post->getElementsByTagName('wfOptionalComments')->item(0)->nodeValue) ? $post->getElementsByTagName('wfOptionalComments')->item(0)->nodeValue : null,
				'wfApproverEdit' => !empty($post->getElementsByTagName('wfApproverEdit')->item(0)->nodeValue) ? $post->getElementsByTagName('wfApproverEdit')->item(0)->nodeValue : null,
				'wfCustomReviewButtonLabel' => !empty($post->getElementsByTagName('wfCustomReviewButtonLabel')->item(0)->nodeValue) ? $post->getElementsByTagName('wfCustomReviewButtonLabel')->item(0)->nodeValue : null,
				'wfCustomApproveButtonLabel' => !empty($post->getElementsByTagName('wfCustomApproveButtonLabel')->item(0)->nodeValue) ? $post->getElementsByTagName('wfCustomApproveButtonLabel')->item(0)->nodeValue : null,
				'wfCustomDeclineButtonLabel' => !empty($post->getElementsByTagName('wfCustomDeclineButtonLabel')->item(0)->nodeValue) ? $post->getElementsByTagName('wfCustomDeclineButtonLabel')->item(0)->nodeValue : null,
				'wfCustomReleaseButtonLabel' => !empty($post->getElementsByTagName('wfCustomReleaseButtonLabel')->item(0)->nodeValue) ? $post->getElementsByTagName('wfCustomReleaseButtonLabel')->item(0)->nodeValue : null,
				'wfHideButtons' => !empty($post->getElementsByTagName('wfHideButtons')->item(0)->nodeValue) ? $post->getElementsByTagName('wfHideButtons')->item(0)->nodeValue : null,
				'wfOrder' => $post->getElementsByTagName('wfStepNo')->item(0)->nodeValue,
				'wfDocStatus' => !empty($post->getElementsByTagName('wfDocStatus')->item(0)->nodeValue) ? $post->getElementsByTagName('wfDocStatus')->item(0)->nodeValue : null,
				'tmpwfReviewerApproverListMulti' => !empty($post->getElementsByTagName('tmpwfReviewerApproverListMulti')->item(0)->nodeValue) ? trim($post->getElementsByTagName('tmpwfReviewerApproverListMulti')->item(0)->nodeValue) : null,
				'tmpwfReviewerApproverListSingle' => !empty($post->getElementsByTagName('tmpwfReviewerApproverListSingle')->item(0)->nodeValue) ? trim($post->getElementsByTagName('tmpwfReviewerApproverListSingle')->item(0)->nodeValue) : null,
				'wfEnableActivateMsg' => !empty($post->getElementsByTagName('wfEnableActivateMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfEnableActivateMsg')->item(0)->nodeValue : null,
				'wfActivateMsg' => !empty($post->getElementsByTagName('wfActivateMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfActivateMsg')->item(0)->nodeValue : null,
				'wfActivateNotifyList' => !empty($post->getElementsByTagName('wfActivateNotifyList')->item(0)->nodeValue) ? $post->getElementsByTagName('wfActivateNotifyList')->item(0)->nodeValue : null,
				'wfActivateNotifyParticipants' => !empty($post->getElementsByTagName('wfActivateNotifyParticipants')->item(0)->nodeValue) ? explode(',', $post->getElementsByTagName('wfActivateNotifyParticipants')->item(0)->nodeValue) : null,
				'wfEnableCompleteMsg' => !empty($post->getElementsByTagName('wfEnableCompleteMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfEnableCompleteMsg')->item(0)->nodeValue : null,
				'wfCompleteMsg' => $post->getElementsByTagName('wfCompleteMsg')->item(0)->nodeValue,
				'wfCompleteNotifyList' => $post->getElementsByTagName('wfCompleteNotifyList')->item(0)->nodeValue,
				'wfCompleteNotifyParticipants' => $post->getElementsByTagName('wfCompleteNotifyParticipants')->item(0)->nodeValue ? explode(',', $post->getElementsByTagName('wfCompleteNotifyParticipants')->item(0)->nodeValue) : null,
				'wfEnableDeclineMsg' => !empty($post->getElementsByTagName('wfEnableDeclineMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfEnableDeclineMsg')->item(0)->nodeValue : null,
				'wfDeclineMsg' => !empty($post->getElementsByTagName('wfDeclineMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfDeclineMsg')->item(0)->nodeValue : null,
				'wfDeclineNotifyList' => !empty($post->getElementsByTagName('wfDeclineNotifyList')->item(0)->nodeValue) ? $post->getElementsByTagName('wfDeclineNotifyList')->item(0)->nodeValue : null,
				'wfDeclineNotifyParticipants' => !empty($post->getElementsByTagName('wfDeclineNotifyParticipants')->item(0)->nodeValue) ? explode(',', $post->getElementsByTagName('wfDeclineNotifyParticipants')->item(0)->nodeValue) : null,
				'wfDeclineAction' => !empty($post->getElementsByTagName('wfDeclineAction')->item(0)->nodeValue) ? $post->getElementsByTagName('wfDeclineAction')->item(0)->nodeValue : null,
				'wfDeclineBacktrack' => !empty($post->getElementsByTagName('wfDeclineBacktrack')->length) ? $post->getElementsByTagName('wfDeclineBacktrack')->item(0)->nodeValue : null,
				'wfEnablePauseMsg' => !empty($post->getElementsByTagName('wfEnablePauseMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfEnablePauseMsg')->item(0)->nodeValue : null,
				'wfPauseMsg' => !empty($post->getElementsByTagName('wfPauseMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfPauseMsg')->item(0)->nodeValue : null,
				'wfPauseNotifyList' => !empty($post->getElementsByTagName('wfPauseNotifyList')->item(0)->nodeValue) ? $post->getElementsByTagName('wfPauseNotifyList')->item(0)->nodeValue : null,
				'wfPauseNotifyParticipants' => !empty($post->getElementsByTagName('wfPauseNotifyParticipants')->item(0)->nodeValue) ? explode(',', $post->getElementsByTagName('wfPauseNotifyParticipants')->item(0)->nodeValue) : null,
				'wfEnableCancelMsg' => !empty($post->getElementsByTagName('wfEnableCancelMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfEnableCancelMsg')->item(0)->nodeValue : null,
				'wfCancelMsg' => !empty($post->getElementsByTagName('wfCancelMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfCancelMsg')->item(0)->nodeValue : null,
				'wfCancelNotifyList' => !empty($post->getElementsByTagName('wfCancelNotifyList')->item(0)->nodeValue) ? $post->getElementsByTagName('wfCancelNotifyList')->item(0)->nodeValue : null,
				'wfCancelNotifyParticipants' => !empty($post->getElementsByTagName('wfCancelNotifyParticipants')->item(0)->nodeValue) ? explode(',', $post->getElementsByTagName('wfCancelNotifyParticipants')->item(0)->nodeValue) : null,
				'wfEnableDelayMsg' => !empty($post->getElementsByTagName('wfEnableDelayMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfEnableDelayMsg')->item(0)->nodeValue : null,
				'wfDelayMsg' => !empty($post->getElementsByTagName('wfDelayMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfDelayMsg')->item(0)->nodeValue : null,
				'wfDelayNotifyList' => !empty($post->getElementsByTagName('wfDelayNotifyList')->item(0)->nodeValue) ? $post->getElementsByTagName('wfDelayNotifyList')->item(0)->nodeValue : null,
				'wfDelayNotifyParticipants' => !empty($post->getElementsByTagName('wfDelayNotifyParticipants')->item(0)->nodeValue) ? explode(',', $post->getElementsByTagName('wfDelayNotifyParticipants')->item(0)->nodeValue) : null,
				'wfDelayCompleteThreshold' => !empty($post->getElementsByTagName('wfDelayCompleteThreshold')->length) ? trim($post->getElementsByTagName('wfDelayCompleteThreshold')->item(0)->nodeValue) : null,
				'wfEnableDelayEsclMsg' => !empty($post->getElementsByTagName('wfEnableDelayEsclMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfEnableDelayEsclMsg')->item(0)->nodeValue : null,
				'wfDelayEsclMsg' => !empty($post->getElementsByTagName('wfDelayEsclMsg')->item(0)->nodeValue) ? $post->getElementsByTagName('wfDelayEsclMsg')->item(0)->nodeValue : null,
				'wfDelayEsclNotifyList' => !empty($post->getElementsByTagName('wfDelayEsclNotifyList')->item(0)->nodeValue) ? $post->getElementsByTagName('wfDelayEsclNotifyList')->item(0)->nodeValue : null,
				'wfDelayEsclNotifyParticipants' => !empty($post->getElementsByTagName('wfDelayEsclNotifyParticipants')->item(0)->nodeValue) ? explode(',', $post->getElementsByTagName('wfDelayEsclNotifyParticipants')->item(0)->nodeValue) : null,
				'wfDelayEsclThreshold' => !empty($post->getElementsByTagName('wfDelayEsclThreshold')->length) ? trim($post->getElementsByTagName('wfDelayEsclThreshold')->item(0)->nodeValue) : null,
				'Workflow' => $workflow
			);
			
			if (!empty($post->getElementsByTagName('wfStepKey')->item(0)->nodeValue)) 
			{
				$wf_step = trim($post->getElementsByTagName('wfStepKey')->item(0)->nodeValue);
				$wf_step = $em->getRepository('DocovaBundle:WorkflowSteps')->find($wf_step);
				if (empty($wf_step)) 
				{
					throw new \Exception('Unspecifed workflow item source ID.');
				}
				$details['WorkflowStep'] = $wf_step;
				$wf_step = null;
			}
				
			$step = $this->get('docova.workflowservices');
			$step->setParams(array(
				'ldap_username' => $this->container->getParameter('ldap_username'),
				'ldap_password' => $this->container->getParameter('ldap_password')
			));
			$step = $step->generateWorkflowStep($details, $runjson);
			
			$root = $output->appendChild($output->createElement('Results'));
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode = $output->createElement('Result', 'OK');
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode = $output->createElement('Result', $step->getId());
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		
		return $output;
	}
	
	/**
	 * Generate custom view tables in DB
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppViews $view
	 * @param \DOMDocument $xml
	 * @param \Docova\DocovaBundle\Entity\Libraries $application
	 * @param \DOMDocument $perspective
	 */
	private function generateCustomView($view, $xml, $application, $not_changed = false, $perspective = null)
	{
		$view_id = is_object($view) ? $view->getId() : $view;
		$view_id = str_replace('-', '', $view_id);
		$em = $this->getDoctrine()->getManager();
		$view_handler = new ViewManipulation($this->_docova, $application, $this->global_settings);
		$oldentries = [];
		try {
			$exists = $view_handler->viewExists($view_id);
			if ($exists === false)
			{
				$view_handler->createViewTable($view, $xml);
			}
			else {
				if ($not_changed === true) {
					$columns = array();
					foreach ($xml->getElementsByTagName('xmlNodeName') as $index => $item) {
						$columns[$item->nodeValue] = array(
							'new' => $xml->getElementsByTagName('columnFormula')->item($index)->nodeValue,
							'old' => null,
							'ntype' => $xml->getElementsByTagName('dataType')->item($index)->nodeValue,
							'otype' => null,
							'respnew' => $xml->getElementsByTagName('showResponses')->item($index)->nodeValue,
							'respold' => null,
							'rspscriptnew' => $xml->getElementsByTagName('responseFormula')->item($index)->nodeValue,
							'rspscriptold' => null,
							'separatemultinew' => $xml->getElementsByTagName('showMultiAsSeparate')->item($index)->nodeValue == 1 ? true : false,
							'separatemultiold' => null
						);
					}
					$item = $index = null;
					if (!empty($perspective)) {
						foreach ($perspective->getElementsByTagName('xmlNodeName') as $index => $item) {
						    $showresp = (!empty($perspective->getElementsByTagName('showResponses')) && $perspective->getElementsByTagName('showResponses')->length > $index ? $perspective->getElementsByTagName('showResponses')->item($index)->nodeValue : '');
						    $respform = (!empty($perspective->getElementsByTagName('responseFormula')) && $perspective->getElementsByTagName('responseFormula')->length > $index ? $perspective->getElementsByTagName('responseFormula')->item($index)->nodeValue : '');
						    
							if (array_key_exists($item->nodeValue, $columns)) {
								$columns[$item->nodeValue]['old'] = $perspective->getElementsByTagName('columnFormula')->item($index)->nodeValue;
								$columns[$item->nodeValue]['otype'] = $perspective->getElementsByTagName('dataType')->item($index)->nodeValue;
								$columns[$item->nodeValue]['respold'] = $showresp;
								$columns[$item->nodeValue]['rspscriptold'] = $respform;
								$columns[$item->nodeValue]['separatemultiold'] = $perspective->getElementsByTagName('showMultiAsSeparate')->item($index)->nodeValue == 1 ? true : false;
							}
							else {
								$columns[$item->nodeValue] = array(
									'new' => null,
									'old' => $perspective->getElementsByTagName('columnFormula')->item($index)->nodeValue,
									'ntype' => null,
									'otype' => $perspective->getElementsByTagName('dataType')->item($index)->nodeValue,
									'respnew' => null,
									'respold' => $showresp,
									'rspscriptnew' => null,
									'rspscriptold' => $respform,
									'separatemultinew' => null,
									'separatemultiold' => $perspective->getElementsByTagName('showMultiAsSeparate')->item($index)->nodeValue == 1 ? true : false
								);
							}
						}
					}
					$match = true;
					foreach ($columns as $c) {
						if (trim($c['new']) != trim($c['old'])) {
							$match = false;
							break;
						}
						if (trim($c['ntype']) != trim($c['otype'])) {
							$match = false;
							break;
						}
						if (trim($c['respnew']) != trim($c['respold'])) {
							$match = false;
							break;
						}
						if (trim($c['rspscriptnew']) != trim($c['rspscriptold'])) {
							$match = false;
							break;
						}
						if ($c['separatemultinew'] !== $c['separatemultiold']) {
							$match = false;
							break;
						}
					}
					if ($match === true) {
						return true;
					}
					$entries = $view_handler->getColumnValues($view_id, 'Document_Id');
				}
				if(!empty($entries)){
	  		        	$oldentries = $entries;
	   			}else{
	         		$oldentries = $view_handler->getColumnValues($view_id, 'Document_Id');
	            }
				$view_handler->deleteView($view_id);
				$view_handler->createViewTable($view, $xml);
			}

			$dateformat = $this->global_settings->getDefaultDateFormat();
			$display_names = $this->global_settings->getUserDisplayDefault();
			$repository = $em->getRepository('DocovaBundle:Documents');
			$twig = $this->container->get('twig');
			$viewid = $view->getId();
			$viewconvq =  $view->getConvertedQuery();
			$viewpers = $view->getViewPerspective();
			$appid = $application->getId();
			if (!empty($entries))
				$values_array = $repository->getDocFieldValues($entries, $dateformat, $display_names, true);
			else {
				$values_array = $repository->getDocFieldValues(array(), $dateformat, $display_names, true, $appid);
				foreach ($values_array as $docid => $docval) {
					if (!$view_handler->isDocMatchView2($appid, array(0 => $docval), $viewconvq, $twig)) {
						$values_array[$docid] = null;
						unset($values_array[$docid]);
					}
				}
				$values_array = array_filter($values_array);
				$entries = array_keys($values_array);
			}
			
			if(!empty($oldentries) && count($oldentries) > 0){
				$oldentries = array_unique($oldentries);
			}
			foreach ($entries as $doc)
			{
				$indexresult = $view_handler->indexDocument2($doc, array(0 => $values_array[$doc]), $appid, $viewid, $viewpers, $twig, true);
				$pos = array_search($doc, $oldentries);
				if(($indexresult == 1 || $indexresult == 2) && $pos === false){
					array_push($oldentries, $doc);
				}else if($pos !== false){
					unset($oldentries[$pos]);
				}
			}
			
			if(!empty($oldentries) && count($oldentries) > 0)
			{
				$paramchunks = array_chunk($oldentries, 2000);
				
				foreach($paramchunks as $chunk){
					$repository->createQueryBuilder('D')
						->update()
						->set('D.Indexed', ':indexedval')
						->set('D.Index_Date', ':indexeddate')
						->where('D.id IN (:idlist)')
						->setParameter('indexedval', false)
						->setParameter('indexeddate', new \DateTime())
						->setParameter('idlist', $chunk)
						->getQuery()
						->execute();
				}
			}
			
			unset($oldentries);
			return true;
		}
		catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}
		return false;
	}

	/**
	 * Convert a selectoin formula string to valid php query string
	 * 
	 * @param string $string
	 * @return string|NULL
	 */
	private function convertQuery($string)
	{
		if (empty($string)) return null;
		$string = 0 === stripos($string, 'SELECT') ? trim(substr($string, 7)) : $string;
		$string = " $string";
		$string = str_ireplace(array(' FORM', '@'), array(' $form->getFormName()', '$twig->f_'), $string);
		$string = str_replace('&', '&&', $string);
		$string = str_replace('|', '||', $string);
		$lastPos = 0;
		$positions = array();
		while (($lastPos = strpos($string, '=', $lastPos))!== false) {
			$positions[] = $lastPos;
			$lastPos = $lastPos + strlen('=');
		}
		if (!empty($positions)) 
		{
			sort($positions);
			$count = 0;
			foreach ($positions as $p) {
				if (!in_array($string{($p+$count-1)}, array('!', '<', '>', '=', '-', '+', '*'))) {
					$string = substr_replace($string, '=', $p, 0);
					$count++;
				}
			}
		}
		return $string;
	}
	
}