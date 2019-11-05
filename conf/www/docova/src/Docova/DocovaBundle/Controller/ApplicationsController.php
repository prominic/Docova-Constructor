<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Docova\DocovaBundle\Entity\Documents;
use Docova\DocovaBundle\Entity\FormTextValues;
use Docova\DocovaBundle\Entity\FormDateTimeValues;
use Docova\DocovaBundle\Entity\FormNumericValues;
use Docova\DocovaBundle\Entity\AttachmentsDetails;
use Docova\DocovaBundle\Entity\WorkflowCompletedBy;
//use Docova\DocovaBundle\Entity\DocumentComments;
//use Docova\DocovaBundle\ObjectModel\DocovaName;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Docova\DocovaBundle\Extensions\GanttServices;
use Docova\DocovaBundle\Extensions\MiscFunctions;
use Docova\DocovaBundle\Entity\FormNameValues;
use Docova\DocovaBundle\Entity\RelatedDocuments;
use Docova\DocovaBundle\Entity\FormGroupValues;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserProfile;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Docova\DocovaBundle\ObjectModel\Docova;
use Docova\DocovaBundle\Entity\AppViews;
use Docova\DocovaBundle\Entity\AppFormProperties;

class ApplicationsController extends Controller 
{
	protected $user;
	protected $global_settings;
	protected $app_path;
	protected $root_path;
	protected $UPLOAD_FILE_PATH;
	
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
	
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];

		$this->app_path = $this->get('kernel')->getRootDir() . '/../src/Docova/DocovaBundle/Resources/views/DesignElements/';
		$this->root_path = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root') : $_SERVER['DOCUMENT_ROOT']; 
		$this->UPLOAD_FILE_PATH = $this->root_path.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
	}
	
	
	public function appServicesAction(Request $request)
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

	public function popupPickListAction(Request $request)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$dbname =  $request->query->get('dbpath');
		if (preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i', $dbname)) {
			try{
				$app =  $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $dbname));			
            }catch(\Doctrine\ORM\NoResultException $e){
            }catch(\Doctrine\ORM\NonUniqueResultException $e){
            }
		}else{
			$startpos = strrpos($dbname, '/');
			if($startpos !== false){
			  $dbname = substr($dbname, $startpos + 1);		  
			}			
			$app =  $em->getRepository('DocovaBundle:Libraries')->getByName($dbname);			
		}
			
		if (empty($app)) {
			throw $this->createNotFoundException('Unspecified source Library with NAME = '.$dbname);
		}
	
		return $this->render('DocovaBundle:Admin:dlgPickList.html.twig', array(
			'user' => $this->user,
			'application' => $app,
			'settings' => $this->global_settings
		));
	}
	
	public function viewOutlineAction(Request $request, $outline)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$app = $request->query->get('AppID');
		$custcss = false;
		$outlineobj = $em->getRepository('DocovaBundle:AppOutlines')->findByNameAlias($outline, $app);

		if ( empty($outlineobj)){
			throw $this->createNotFoundException('Unspecified outline with NAME = '.$outline);
		}

		//custom css
		//viewcss
		$outline_name = str_replace(array('/', '\\'), '-',$outlineobj->getOutlineName() );
		$outline_name = str_replace(' ', '',$outline_name);
		$filepath = $this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app.DIRECTORY_SEPARATOR."outlines".DIRECTORY_SEPARATOR.$outline_name.'.css';
		if (file_exists($filepath)) 
		{
			$custcss = true;
		}
		
		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
		return $this->render('DocovaBundle:Applications:sfOutlineLoader.html.twig', array(
			'user' => $this->user,
			'outline' => $outlineobj,
			'custcss' => $custcss,
			'appId' => $app
		));
	}
	
	public function viewAppPageAction(Request $request, $page)
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$app = $request->query->get('AppID');
		$page = $em->getRepository('DocovaBundle:AppPages')->findPage($page, $app);
		$filename = str_replace(array('\\', '/'), '-', $page->getPageName());
		$filename = str_replace(' ', '', $filename);
		$is_mobile = $request->query->get('Mobile');
		$mobile_app_device=$request->query->get('device'); // this is coming from mobile app
		if ($is_mobile == 'true' ||  in_array($mobile_app_device, array("android","iOS") ) ) {
		    $is_mobile=true;
		}else{
		    $is_mobile=false;
		}
		$custcss = false;

		$basepath = $this->container->getParameter('kernel.project_dir');
		
		$scriptpath = $basepath.'/web/bundles/docova/js/custom/'.$app.'/PAGE/'.$filename.'.js';

		
		$js_exists = file_exists($scriptpath);
		$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		$docova = new Docova($this->container);
		$docovaAcl = $docova->DocovaAcl($application);
		$docova = null;

		//check form custom css

		if ( !empty( $page))
		{
			$name = str_replace(array('/', '\\'), '-', $page->getPageName());
			$name = str_replace(' ', '', $name);
			

			$filepath = $this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app.DIRECTORY_SEPARATOR."PAGE".DIRECTORY_SEPARATOR.$name.'.css';
			if (file_exists($filepath)) 
			{
				$custcss = true;
				
			}
		}

		return $this->render('DocovaBundle:Applications:sfPageLoader.html.twig', array(
			'user' => $this->user,
			'appId' => $app,
			'page' => $page,
			'custcss' => $custcss,
			'fileexist' => $js_exists,
			'acl' => $docovaAcl,
		   	'ismobile' => $is_mobile
		));
	}
	
	public function viewFrameAction()
	{
		return $this->render('DocovaBundle:Applications:wViewFrame.html.twig');
	}
	
	public function tabbedTableViewAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Applications:tabbedTableView.html.twig', array('user' => $this->user));
	}
	
	public function appViewsAllAction(Request $request,$viewname)
	{
		$this->initialize();
		$app = $request->query->get('AppID');
		$app = urldecode($app);
		$em = $this->getDoctrine()->getManager();
		$appView = $em->getRepository('DocovaBundle:AppViews')->findByNameAlias($viewname, $app);
		if (empty($appView)) {
			throw $this->createNotFoundException('Unspecified app view source ID or app source ID.');
		}
		
		$filter_params = '';
		$params = $request->query->keys();
		if (!empty($params) && count($params)) {
			foreach ($params as $key) {
				if (strpos($key, 'embflt_') === 0) {
					$filter_params = $key.'='.$request->query->get($key).'&';
				}
			}
			$filter_params = substr_replace($filter_params, '', -1);
		}
		
		$toolbar = '';
		$name = str_replace(' ', '', $appView->getViewName());
		
		if (file_exists($this->app_path.DIRECTORY_SEPARATOR.$app.DIRECTORY_SEPARATOR.'toolbar'.DIRECTORY_SEPARATOR.$name.'.html.twig')) 
		{
			$toolbar = file_get_contents($this->app_path.DIRECTORY_SEPARATOR.$app.DIRECTORY_SEPARATOR.'toolbar'.DIRECTORY_SEPARATOR.$name.'.html.twig');
		}
		$view_type = $request->query->get('viewType');
		$template_name = 'wAppViewsAll';

		if ($view_type == 'Calendar' && $view_type == $appView->getViewType())
		{
			$template_name = 'sfViewCalendarContents';
		}
		elseif ($view_type == 'Gantt' && $view_type == $appView->getViewType())
		{
			$template_name = 'sfViewGanttContents';
		}

		return $this->render("DocovaBundle:Applications:$template_name.html.twig", array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'view' => $appView,
			'filters' => $filter_params,
			'toolbar' => $toolbar
		));
	}
	
	public function appLoadViewAction(Request $request, $viewname, $appid)
	{
		//$output = array('@timestamp' => time(), '@toplevelentries' => '0');
		$output = '{"@timestamp":'.time().',"@toplevelentries":"';
		$em = $this->getDoctrine()->getManager();
		$start =  $request->query->get('start');
		$count =  $request->query->get('count');

		$start = $start != "" ? intval($start) : null;
		$count = $count != "" ? intval($count) : null;
		$restrictToCategory =  $request->query->get('startKey');
		$childrenOnlyKey = $request->query->get('childrenOnlyKey');
		$filters = [];
		
		if (empty($restrictToCategory) && empty($childrenOnlyKey)) {
			$params = $request->query->keys();
			foreach ($params as $key) {
				if (0 === strpos($key, 'embflt_')) {
					$filters[str_replace('embflt_', '', $key)] = $request->query->get($key);
				}
			}
		}

		$docova = new Docova($this->container);
		$application = $docova->DocovaApplication(['appID' => $appid]);
		if (empty($application)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}
		$viewentity =  $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('id' => $viewname, 'application' => $appid));
		if (empty($viewentity)) {
			throw $this->createNotFoundException('Could not open view.');
		}
		$perspective = new \DOMDocument();
		if ($viewentity->getViewPerspective()){
			$perspective->loadXML($viewentity->getViewPerspective());
		}
		$datatypenodes = $perspective->getElementsByTagName('dataType');
		$namenodes = $perspective->getElementsByTagName('xmlNodeName');
		$showresponsehierarchy = $viewentity->getRespHierarchy();
		
		$view = $docova->DocovaView($application, "", $viewentity);
		if (!empty($start) && !empty($count)) {
			$total = $view->getAllDocumentsByKeyEX($start, $count, '', true, '', true);
		}
		
		$page_full = false;
		$documents = [];
		$docovaAcl = $docova->DocovaAcl($application->getEntity());
		while ($page_full === false) {
			$result = $view->getAllDocumentsByKeyEX($start, $count, $restrictToCategory, true, '', false, $childrenOnlyKey, $filters);
			if (empty($result)) {
				break;
			}
			foreach ($result as $document) {
				$doc = $em->getReference('DocovaBundle:Documents', $document['Document_Id']);
				if (is_null($start)) {
					$start = 1;
				}
				$start++;
				if ( !$docovaAcl->isDocReader($doc)) {
					continue;
				}
				
				$documents[] = $document;
				if (count($documents) == $count) {
					break;
				}
			}
			
			if (empty($start) || empty($count) || count($documents) == $count || $start >= $total) {
				$page_full = true;
			}
		}

		if (!empty($documents)) 
		{
			$len = count($documents);
			$output .= isset($total) ? (empty($total) ? 0 : $total) : $len;
			$output .= '","@offset":'.$start;
			$output .= ',"viewentry":[';
			$added = false;
			
			foreach ($documents as $document) {
				$doc = $em->getReference('DocovaBundle:Documents', $document['Document_Id']);
				
				if ($showresponsehierarchy && empty($document['Parent_Doc']) || !$showresponsehierarchy || !empty($childrenOnlyKey)) 
				{
					$added = true;
					$child_count = $this->getChildrenCount($document['Document_Id'], $documents);
					$output .= '{"@unid":"'.$document['Document_Id'].'",';
					//$output .= '"@position":"'.$this->calculatePosition($document, $result).'",';
					if (!empty($child_count)) {
						$output .= '"@children":"'.$child_count.'",';
					}
					$output .= '"entrydata":[';
					
					
					foreach ($namenodes as $index => $node) {
						if (array_key_exists($node->nodeValue, $document))
						{
							$value = $document[$node->nodeValue];
							$type = trim($datatypenodes->item($index)->nodeValue);
							$value = $this->cleanColumnValue($value, $type, false);
							
							if ($type == 'date') {
							    $type = 'datetime';
							}							
						
							$column = intval(str_replace('CF', '', $node->nodeValue));
							$output .= '{"@columnnumber":'.$column.',"'.$type.'":["'.$value.'"]},';
						}
					}
					$output = substr_replace($output, ']},', -1);

					if ($showresponsehierarchy){
						if (!empty($child_count)) {
							$output .= $this->generateChildNodes($document, $documents, $namenodes, $datatypenodes);
						}
					}
				}
			}
			
			if ($added === true)
				$output = substr_replace($output, ']', -1);
			else 
				$output = '{"@timestamp":'.time().',"@toplevelentries":"0"';
		}
		else {
			$output .= '0"';
		}
		$output .= '}';
		
		$output = str_replace(array("\r\n", "\n"), "\\n", $output);
		$output = trim(preg_replace('/\s+/', ' ', $output));
		$response = JsonResponse::fromJsonString($output);
		$response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);
	
		return $response;
	}
	
	public function appLoadViewMobileAction(Request $request, $viewname, $appid)
	{
	    $result_xml = new \DOMDocument("1.0", "UTF-8");
	    $root = $result_xml->appendChild($result_xml->createElement('documents'));
	    
	    $em = $this->getDoctrine()->getManager();
	    $start =  $request->query->get('start');
	    $count =  $request->query->get('count');
	    
	    $start = $start != "" ? intval($start) : null;
	    $count = $count != "" ? intval($count) : null;
	    $restrictToCategory =  $request->query->get('startKey');
	    
	    $docova = new Docova($this->container);
	    $application = $docova->DocovaApplication(['appID' => $appid]);
	    if (empty($application)) {
	        throw $this->createNotFoundException('Unspecified application source ID.');
	    }
		$miscfunctions = new MiscFunctions();
		if($miscfunctions->isGuid($viewname)){
			$viewentity =  $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('id' => $viewname, 'application' => $appid));			
		}		
	    if (empty($viewentity)) {
	        $viewentity =  $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('viewName' => $viewname, 'application' => $appid));
	        if (empty($viewentity)) {
	            $viewentity =  $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('viewAlias' => $viewname, 'application' => $appid));
	            if (empty($viewentity)) {
	                throw $this->createNotFoundException('Could not open view.');
	            }
	        }
	    }
	    $perspective = new \DOMDocument();
	    if ($viewentity->getViewPerspective())
	        $perspective->loadXML($viewentity->getViewPerspective());
	        $view = $docova->DocovaView($application, "", $viewentity);
	        $result = $view->getAllDocumentsByKeyEX($start, $count, $restrictToCategory);
	        if (!empty($result))
	        {
	            $docovaAcl = $docova->DocovaAcl($application->getEntity());
	            foreach ($result as $document) {
	                $doc = $em->getReference('DocovaBundle:Documents', $document['Document_Id']);
	                if ( !$docovaAcl->isDocReader($doc)) {
	                    continue;
	                }
	                
	                if (empty($document['Parent_Doc']))
	                {
	                    $docnode = $result_xml->createElement('document');
	                    
	                    $docnode->appendChild($result_xml->createElement('dockey', $document['Document_Id']));
	                    
	                    $docnode->appendChild($result_xml->createElement('docid', $document['Document_Id']));
	                    
	                    $docnode->appendChild($result_xml->createElement('rectype', "doc"));
	                    
	                    $docnode->appendChild($result_xml->createElement('typekey', ''));
	                    
	                    $tempdatatypenodes = $perspective->getElementsByTagName('dataType');
	                    $tempismobiletitlenodes = $perspective->getElementsByTagName('isMobileTitle');
	                    $tempismobiledetailnodes = $perspective->getElementsByTagName('isMobileDetail');
	                    
	                    $titledata = "";
	                    $detaildata = "";
	                    
	                    foreach ($perspective->getElementsByTagName('xmlNodeName') as $index => $node) {
	                        $ismobiletitle = trim($tempismobiletitlenodes->item($index)->nodeValue);
	                        $ismobiledetail = trim($tempismobiledetailnodes->item($index)->nodeValue);
	                        
	                        if (($ismobiletitle == "1" | $ismobiledetail == "1") & array_key_exists($node->nodeValue, $document))
	                        {
	                            $value = $document[$node->nodeValue];
	                            $type = trim($tempdatatypenodes->item($index)->nodeValue);
	                            $value = $this->cleanColumnValue($value, $type, true);
	                            
	                            if($value != ""){
	                                if($ismobiletitle == "1"){
        	                            if($titledata != ""){
	                                      $titledata .= " ";
	                                    }
	                                    $titledata .= $value;
	                                }
	                                if($ismobiledetail == "1"){
	                                    if($detaildata != ""){
	                                        $detaildata .= " | ";
	                                    }
	                                    $detaildata .= $value;
	                                }
	                            }
	                            
                            	                            	                            
                            }
	                    }
	                    
	                    if($titledata != ""){
	                        $CData = $result_xml->createCDATASection($titledata);
	                        $child = $result_xml->createElement("CF0");
	                        $child->appendChild($CData);
	                        $docnode->appendChild($child);	                        
	                    }
	                    
	                    if($detaildata != ""){
	                        $CData = $result_xml->createCDATASection($detaildata);
	                        $child = $result_xml->createElement("CF1");
	                        $child->appendChild($CData);
	                        $docnode->appendChild($child);	                        
	                    }
	                    
	                    $root->appendChild($docnode); 
	                }
	            }
	            
	        }
	        
	        
	        $response = new Response($result_xml->saveXML());
	        $response->headers->set('Content-Type', 'text/xml');
	        return $response;
	}
	
	
	public function appLoadToolbarAction(Request $request, $appid, $elementname)
	{
	    if (empty($appid)) {
	        throw $this->createNotFoundException('Unspecified application source ID.');
	    }
	    if (empty($elementname)) {
	        throw $this->createNotFoundException('Unspecified target element name.');
	    }
	    $htmldata = "";
	    
	    $ismobile =  $request->query->get('mobile');
	    if(!empty($appid) && !empty($elementname)){
	        $templatename = 'DocovaBundle:DesignElements:'.$appid.'/toolbar/'.$elementname.(empty($ismobile) ? '': '_m').'.html.twig';
	        if ( $this->get('templating')->exists($templatename) ) {
	            $htmldata = $this->renderView($templatename);
	        }
	    }
	    
	    $response = new Response($htmldata);
	    $response->headers->set('Content-Type', 'text/html');
	    return $response;
	}
	
	public function getChartDataAction(Request $request)
	{

	    $post_req = $request->getContent();
	    if (empty($post_req)) {
	        throw $this->createNotFoundException('No POST data was submitted.');
	    }
	    if (stripos($post_req, '%3C') !== false) {
	        $post_req = urldecode($post_req);
	    }
	    
	    $post_xml = new \DOMDocument();
	    $post_xml->loadXML($post_req);
	    
	    $action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
	    if (empty($action)) {
	        throw $this->createNotFoundException('No Action was found in POST request');
	    }
	    
	    $appid = $post_xml->getElementsByTagName('AppID')->item(0)->nodeValue;
	    if (empty($appid)) {
	        throw $this->createNotFoundException('No AppID was found in POST request');
	    }
	    
	    $sourcetype = $post_xml->getElementsByTagName('SourceType')->item(0)->nodeValue;
	    if (empty($sourcetype)) {
	        throw $this->createNotFoundException('No SourceType was found in POST request');
	    }
	    
	    $sourcename = $post_xml->getElementsByTagName('SourceName')->item(0)->nodeValue;
	    if (empty($sourcename)) {
	        throw $this->createNotFoundException('No SourceName was found in POST request');
	    }
	    
	    $viewcat = $post_xml->getElementsByTagName('Category')->item(0)->nodeValue;
	    
	    $valueitems = $post_xml->getElementsByTagName('ValueItems')->item(0)->nodeValue;
	    if (empty($valueitems)) {
	        throw $this->createNotFoundException('No ValueItems was found in POST request');
	    }
	    $valueitems = explode(",", $valueitems);
	    for($i=0; $i<count($valueitems); $i++){
	        $valueitems[$i] = explode("|", $valueitems[$i]);
	    }
	    
	    $legenditems = $post_xml->getElementsByTagName('LegendItems')->item(0)->nodeValue;
	    $legenditems = explode(",", $legenditems);
	    $haslegend = (count($legenditems) > 0 && $legenditems[0] != "");
	    for($i=0; $i<count($legenditems); $i++){
	        $legenditems[$i] = explode("|", $legenditems[$i]);
	    }
	    
	    $axisitems = $post_xml->getElementsByTagName('AxisItems')->item(0)->nodeValue;
	    $axisitems = explode(",", $axisitems);
	    for($i=0; $i<count($axisitems); $i++){
	        $axisitems[$i] = explode("|", $axisitems[$i]);
	    }
	    
	    $em = $this->getDoctrine()->getManager();
	    	    
	    $docova = new Docova($this->container);
	    $application = $docova->DocovaApplication(['appID' => $appid]);
	    if (empty($application)) {
	        throw $this->createNotFoundException('Unspecified application source ID.');
	    }
	    $viewentity =  $em->getRepository('DocovaBundle:AppViews')->findByNameAlias($sourcename, $appid);
	    if (empty($viewentity)) {
	        throw $this->createNotFoundException('Could not open view.');
	    }
	    
        $view = $docova->DocovaView($application, "", $viewentity);
        $result = $view->getAllDocumentsByKeyEX(null, null, $viewcat);
        
        $legends = array();
        $labels = array();
        $data = array();
        $datacount = array();
        $adjustaverages = array();
        $legendcolors = array();
        $datatypes = array();
        
        //-- use a static array of color values so that colors remain consistent between page loads or refreshes
        $colorlist = array("255,0,41","55,126,184", "102,166,130", "152,78,163", "0,210,213", "255,127,0", "175,141,0", "127,128,205", "179,233,0", "196,46,96", "166,86,40", "247,129,191", "141,211,199", "190,186,218", "251,128,114", "128,177,211", "253,180,98", "188,128,189", "255,237,111", "196,234,255", "207,140,0", "27,158,119", "217,95,2", "231,41,138", "230,171,2", "166,118,29", "0,151,255", "0,208,103", "115,115,115", "150,150,150", "244,54,0", "75,169,59", "87,121,187", "151,238,63", "191,57,71", "159,91,0", "244,135,88", "140,174,214", "242,185,79", "239,242,110", "228,56,114", "217,177,0", "157,122,0", "105,140,255");
        $colorindex = 0;
        
        $totalcount = 0;  
	    if (!empty($result))
	    {
	           $docovaAcl = $docova->DocovaAcl($application->getEntity());
	            
	           $perspective = new \DOMDocument();
	           if ($viewentity->getViewPerspective()){
	                $perspective->loadXML($viewentity->getViewPerspective());
	           }
	            
	            //-- loop through each document in view
	            foreach ($result as $document) {
	                $doc = $em->getReference('DocovaBundle:Documents', $document['Document_Id']);
	                if ( !$docovaAcl->isDocReader($doc)) {
	                    continue;
	                }
	                
	                if (!empty($document['Parent_Doc'])){
	                    continue;
	                }
	                    
                    $totalcount ++;
                    
                    //-- loop through axis labels
                    $axislabel = "";
                    for($a=0; $a<count($axisitems); $a++){
                        $labelfieldkey = strtoupper($axisitems[$a][0]);
                        if (array_key_exists($labelfieldkey, $document))
                        {
                            if(!array_key_exists($labelfieldkey, $datatypes)){
                                $colindex = intval(str_replace('CF', '', $labelfieldkey));
                                $type = trim($perspective->getElementsByTagName('dataType')->item($colindex)->nodeValue);
                                $datatypes[$labelfieldkey] = $type;
                            }
                            $label = $document[$labelfieldkey];
                            $label = $this->cleanColumnValue($label, $datatypes[$labelfieldkey], true); 
                            if(is_null($label)){$label = "";}
                            if($label != ""){
                                if($axislabel != ""){
                                    $axislabel .= " - ";
                                }
                                $axislabel .= $label;
                            }
                        }
                    }
                    
                    if (!in_array($axislabel, $labels))
                    {
                        $labels[] = $axislabel;
                    }
                    if(!array_key_exists($axislabel, $data)){
                        $data[$axislabel] = array();
                        $datacount[$axislabel] = array();
                    }
                    
                    
                    //-- loop through legend items
                    $legendlabel = "";
                    for($l=0; $l<count($legenditems); $l++){
                        $legendfieldkey = strtoupper($legenditems[$l][0]);
                        if (array_key_exists($legendfieldkey, $document))
                        {
                            if(!array_key_exists($legendfieldkey, $datatypes)){
                                $colindex = intval(str_replace('CF', '', $legendfieldkey));
                                $type = trim($perspective->getElementsByTagName('dataType')->item($colindex)->nodeValue);
                                $datatypes[$legendfieldkey] = $type;
                            }
                            $legend = $document[$legendfieldkey];
                            $legend = $this->cleanColumnValue($legend, $datatypes[$legendfieldkey], true);   
                            if(is_null($legend)){$legend = "";}
                            if($legend !== null && $legend != ""){
                                if($legendlabel != ""){
                                    $legendlabel .= " - ";
                                }
                                $legendlabel .= $legend;
                            }
                        }            
                    }
                    
                    //-- loop through value items
                    for($i=0; $i<count($valueitems); $i++){
                        $valop = $valueitems[$i][2];
                        $fieldkey = strtoupper($valueitems[$i][0]);
                        
                        $templegendlabel = $legendlabel;
                        if(count($valueitems)>1 || $templegendlabel == ""){
                            if($templegendlabel != ""){
                                $templegendlabel .= " - ";
                            }
                            $templegendlabel .= ucfirst($valop)." of ".$valueitems[$i][1]; 
                        }
                        if (!in_array($templegendlabel, $legends))
                        {
                            $legends[] = $templegendlabel;
                            $legendcolors[$templegendlabel] = $colorlist[$colorindex];
                            $colorindex = ($colorindex >= count($colorlist) ? 0 : $colorindex + 1);
                            if($valop == "average"){
                                $adjustaverages[] = $templegendlabel;
                            }
                        }
                                                                        
                        $tempval = 0;
                        if($valop == "count"){
                            $tempval = 1;
                        }else{
                            if (array_key_exists($fieldkey, $document))
                            {
                                $value = $document[$fieldkey];
                                if(!empty($value) && is_numeric($value)){
                                    $tempval = floatval($value);
                                }
                            }
                        }
                        
                        if($valop == "sum" || $valop == "average" || $valop == "count"){
                            if(!array_key_exists($templegendlabel, $data[$axislabel])){
                                $data[$axislabel][$templegendlabel] = $tempval;
                            }else{
                                $data[$axislabel][$templegendlabel] = $data[$axislabel][$templegendlabel] + $tempval;
                            }
                        }else if($valop == "min"){
                            if(!array_key_exists($templegendlabel, $data[$axislabel])){
                                $data[$axislabel][$templegendlabel] = $tempval;
                            }else if($data[$axislabel][$templegendlabel] > $tempval){
                                $data[$axislabel][$templegendlabel] = $tempval;
                            }
                        }else if($valop == "max"){
                            if(!array_key_exists($templegendlabel, $data[$axislabel])){
                                $data[$axislabel][$templegendlabel] = $tempval;
                            }else if($data[$axislabel][$templegendlabel] < $tempval){
                                $data[$axislabel][$templegendlabel] = $tempval;
                            }
                        }
                        
                        //--keep a count so that we can use it later for averaging if need be
                        if(!array_key_exists($templegendlabel, $datacount[$axislabel])){
                            $datacount[$axislabel][$templegendlabel] = 1;
                        }else{
                            $datacount[$axislabel][$templegendlabel] = $datacount[$axislabel][$templegendlabel] + 1;
                        }
                    }
                    
                }
	    }
	    
	    
	    sort($labels);
	    
	    $output = '{';
	    $output .=   '"data": {';
	    $output .=      '"labels": [';
	    for($l=0; $l<count($labels); $l++){
	        $output .=        ($l > 0 ? ',' : '');
	        $output .=        '"'.$labels[$l].'"';
	    }
	    $output .=      '],';
	    $output .=      '"datasets": [';	
	    for($d=0; $d<count($legends); $d++){
	       $bgcolors = "";
	       $bcolors = "";
           $output .=        ($d > 0 ? ',' : '');
	       $output .=        '{';
	       $output .=          '"label": "'.$legends[$d].'",';
	       $output .=          '"data": [';
	       for($v=0; $v<count($labels); $v++){
	             $output .=        ($v > 0 ? ',' : '');
	             if(array_key_exists($legends[$d], $data[$labels[$v]])){
	                 $tempval = $data[$labels[$v]][$legends[$d]];
	                 if(in_array($legends[$d], $adjustaverages)){
	                     $tempcount = $datacount[$labels[$v]][$legends[$d]];
                         $tempval = round($tempval / $tempcount, 2);
	                 }
    	             $output .=        (string)$tempval;
	             }else{
	                 $output .=        '0';
	             }
	             $bgcolors .= ($v > 0 ? ',' : '').'"rgba('.($haslegend ? $legendcolors[$legends[$d]] : $colorlist[$v]).',0.2)"';	             
	             $bcolors .= ($v > 0 ? ',' : '').'"rgba('.($haslegend ? $legendcolors[$legends[$d]] : $colorlist[$v]).',1)"';
	       }
	       $output .=          '],';
	       $output .=          '"backgroundColor": [';
	       $output .=              $bgcolors; 
	       $output .=          '],';
	       $output .=          '"borderColor": [';
	       $output .=              $bcolors;
	       $output .=          '],';
	       $output .=          '"borderWidth": 1';
	       $output .=        '}';
	    }
        $output .=      ']';
	    $output .=   '}';
	    $output .= '}';  
	        
	    $response = new Response();
	    $response->headers->set('Content-Type', 'application/json');
	    $response->setContent($output);
	    return $response;
	}
	
	
	
	private function cleanColumnValue($columnvalue, $type, $outputstring=false){
        $value = $columnvalue;
        
        if ($type == 'datetime' || $type == 'date') {
            if (!empty($value)) {
                $value = new \DateTime($value);
                $value = $value->format(($type == 'date' ? ($outputstring ? 'Y-m-d' : 'Ymd') : ($outputstring ? 'Y-m-d H:i:s' : 'Ymd His')));
                if(($outputstring && substr($value, 0, 10) == '1000-01-01') || substr($value, 0, 8) == '10000101'){
                    if($type == 'datetime'){
                        if(($outputstring && substr($value, 0, 19) == '1000-01-01 00:00:00') || substr($value, 0, 15) == '10000101 000000'){
                            $value = ($outputstring ? "" : null);
                        }
                    }else{
                        $value = ($outputstring ? "" : null);
                    }
                }
            }else{
                $value = ($outputstring ? "" : null);
            }
        }
        else {
            $value = str_replace('\\', '\\\\', $value);
            $value = str_replace('"', '\\"', $value);
        }
        $value = html_entity_decode($value, ENT_QUOTES | ENT_XML1, "UTF-8");
        
        if ($type == 'html'){
            $value = str_replace('&', '&amp;', $value);            
        }
        
        return $value;
	}
	
	
	private function serviceGETDOCPOSITION($post_xml)
	{
		$result_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$docname = $post_xml->getElementsByTagName('Subject')->item(0)->nodeValue;
		$view	 = $post_xml->getElementsByTagName('ViewID')->item(0)->nodeValue;
		$em = $this->getDoctrine()->getManager();
		$security_check = new Miscellaneous($this->container);
	
		$documents = $em->getRepository('DocovaBundle:Documents')->getFolderDocuments($view);
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
	
	
	public function serviceGETVIEWCOLUMNS($post_xml)
	{
	    $result_xml = new \DOMDocument("1.0", "UTF-8");
	    $root = $result_xml->appendChild($result_xml->createElement('Results'));
	    
	    $datafound = false;
	    
	    $docova = new Docova($this->container);
	    $appid = $post_xml->getElementsByTagName('AppID')->item(0)->nodeValue;
	    $application = $docova->DocovaApplication(['appID' => $appid]);
	    if (!empty($application)) {
    	    $viewname = $post_xml->getElementsByTagName('ViewName')->item(0)->nodeValue;	    
    	    $em = $this->getDoctrine()->getManager();
    	    $viewentity =  $em->getRepository('DocovaBundle:AppViews')->findByNameAlias($viewname, $appid);
    	    if (!empty($viewentity)) {
        	   $perspective = new \DOMDocument();
	           if ($viewentity->getViewPerspective()){
	               $perspective->loadXML($viewentity->getViewPerspective());
	        
	               $node = $result_xml->createElement('Result', 'OK');
        	       $attrib = $result_xml->createAttribute('ID');
	               $attrib->value = 'Status';
	               $node->appendChild($attrib);
	               $root->appendChild($node);
	               
	               $resnode = $result_xml->createElement('Result');
	               $attrib = $result_xml->createAttribute('ID');
	               $attrib->value = 'Ret1';
	               $resnode->appendChild($attrib);
	               
	               foreach ($perspective->getElementsByTagName('column') as $index => $sourcenode) {
	                   $columnnode =  $result_xml->createElement('Column');
	                   
	                   $columnnode->appendChild($result_xml->createElement("Alignment", $sourcenode->getElementsByTagName("align")->item(0)->nodeValue));	                   
	                   $columnnode->appendChild($result_xml->createElement("DateFmt", $sourcenode->getElementsByTagName("dateFormat")->item(0)->nodeValue));	                   
	                   $columnnode->appendChild($result_xml->createElement("FontColor", $sourcenode->getElementsByTagName("color")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("FontFace", $sourcenode->getElementsByTagName("fontFamily")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("FontPointSize", $sourcenode->getElementsByTagName("fontSize")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("FontStyle", $sourcenode->getElementsByTagName("fontStyle")->item(0)->nodeValue));
	                   
	                   $tempnode = $result_xml->createElement("Formula");
	                   $tempnode->appendChild($result_xml->createCDATASection($sourcenode->getElementsByTagName("columnFormula")->item(0)->nodeValue));
	                   $columnnode->appendChild($tempnode);	                   
	                   
	                   $columnnode->appendChild($result_xml->createElement("HeaderAlignment", $sourcenode->getElementsByTagName("alignH")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("HeaderFontColor", $sourcenode->getElementsByTagName("colorH")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("HeaderFontFace", $sourcenode->getElementsByTagName("fontFamilyH")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("HeaderFontPointSize", $sourcenode->getElementsByTagName("fontSizeH")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("HeaderFontStyle", $sourcenode->getElementsByTagName("fontStyleH")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("IsAccentSensitiveSort", "1"));
	                   $columnnode->appendChild($result_xml->createElement("IsCaseSensitiveSort", "1"));
	                   $columnnode->appendChild($result_xml->createElement("IsCategory", $sourcenode->getElementsByTagName("isCategorized")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("IsField", "0"));
	                   $columnnode->appendChild($result_xml->createElement("IsFontBold", $sourcenode->getElementsByTagName("fontWeight")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("IsFontItalic", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsFontStrikethrough", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsFontUnderline", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsFormula", "1"));
	                   $columnnode->appendChild($result_xml->createElement("IsHeaderFontBold", $sourcenode->getElementsByTagName("fontWeightH")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("IsHeaderFontItalic", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsHeaderFontStrikethrough", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsHeaderFontUnderline", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsHidden", $sourcenode->getElementsByTagName("isHidden")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("IsHideDetail", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsIcon", ($sourcenode->getElementsByTagName("dataType")->item(0)->nodeValue == "icon" ? "1" : "0")));
	                   $columnnode->appendChild($result_xml->createElement("IsNumberAttribParens", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsNumberAttribPercent", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsNumberAttribPunctuated", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsResize", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsResortAscending", ($sourcenode->getElementsByTagName("customSortOrder")->item(0)->nodeValue == "ascending" ? "1" : "" )));
	                   $columnnode->appendChild($result_xml->createElement("IsResortDescending", ($sourcenode->getElementsByTagName("customSortOrder")->item(0)->nodeValue == "descending" ? "1" : "" )));
	                   $columnnode->appendChild($result_xml->createElement("IsResortToView", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsResponse", $sourcenode->getElementsByTagName("showResponses")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("IsSecondaryResort", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsSecondaryResortDescending", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsShowTwistie", ""));
	                   $columnnode->appendChild($result_xml->createElement("IsSortDescending", ($sourcenode->getElementsByTagName("sortOrder")->item(0)->nodeValue == "descending" ? "1" : "" )));
	                   $columnnode->appendChild($result_xml->createElement("IsSorted", ($sourcenode->getElementsByTagName("sortOrder")->item(0)->nodeValue == "none" ? "" : "1" )));	    
	                   
	                   $tempnode = $result_xml->createElement("ItemName");
	                   $tempnode->appendChild($result_xml->createCDATASection($sourcenode->getElementsByTagName("xmlNodeName")->item(0)->nodeValue));
	                   $columnnode->appendChild($tempnode);	  

	                   $columnnode->appendChild($result_xml->createElement("ListSep", ""));
	                   $columnnode->appendChild($result_xml->createElement("NumberAttrib", ""));
	                   $columnnode->appendChild($result_xml->createElement("NumberDigits", ""));
	                   $columnnode->appendChild($result_xml->createElement("NumberFormat", $sourcenode->getElementsByTagName("numberFormat")->item(0)->nodeValue));
	                   $columnnode->appendChild($result_xml->createElement("Position", (string)$index));       
	                   
	                   $tempnode = $result_xml->createElement("ResortToViewName");
	                   $tempnode->appendChild($result_xml->createCDATASection(""));
	                   $columnnode->appendChild($tempnode);	  
	                   
	                   $columnnode->appendChild($result_xml->createElement("SecondaryResortColumnIndex", ""));
	                   $columnnode->appendChild($result_xml->createElement("TimeDateFmt", ""));
	                   $columnnode->appendChild($result_xml->createElement("TimeFmt", ""));
	                   $columnnode->appendChild($result_xml->createElement("TimeZoneFmt", ""));	   
	                   
	                   $tempnode = $result_xml->createElement("Title");
	                   $tempnode->appendChild($result_xml->createCDATASection($sourcenode->getElementsByTagName("title")->item(0)->nodeValue));
	                   $columnnode->appendChild($tempnode);
	                   
	                   $columnnode->appendChild($result_xml->createElement("Width", $sourcenode->getElementsByTagName("width")->item(0)->nodeValue));
	                   
	                   
	                   $resnode->appendChild($columnnode);
	               }
	               
	               $root->appendChild($resnode);
	               
	               $datafound = true;
	           }
    	    }
	    }
	    
	    if(!$datafound){
	        $node = $result_xml->createElement('Result', 'FAILED');
	        $attrib	= $result_xml->createAttribute('ID');
	        $attrib->value = 'Status';
	        $node->appendChild($attrib);
	        $root->appendChild($node);
	        
	        $attrib = $result_xml->createAttribute('ID');
	        $attrib->value = 'ErrMsg';
	        $node = $result_xml->createElement('Result', 'Unable to retrieve view column information.');
	        $node->appendChild($attrib);
	        $root->appendChild($node);
        }
        
        return $result_xml;
	}
	
	
	/**
	 * Fetch amount of children of the document
	 * 
	 * @param string $docid
	 * @param array $records
	 * @return number
	 */
	private function getChildrenCount($docid, $records)
	{
		$count = 0;
		foreach ($records as $document)
		{
			if ($document['Parent_Doc'] == $docid) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Calculate the position of document amont its other siblings and ancestors
	 *
	 * @param array $document
	 * @param array $records
	 * @param \DOMNodes $namenodes
	 * @param \DOMNodes $datatypenodes
	 * @return string
	 */
	private function generateChildNodes($document, $records, $namenodes, $datatypenodes)
	{
		$output = '';
		foreach ($records as $row)
		{
			if ($row['Parent_Doc'] == $document['Document_Id'])
			{
				$child_count = $this->getChildrenCount($row['Document_Id'], $records);
				$output .= '{"@unid":"'.$row['Document_Id'].'",';
				$output .= '"@response":"true",';
				if (!empty($child_count)) {
					$output .= '"@children":"'.$child_count.'",';
				}
				$output .= '"entrydata":[';
				$column_number = 0;
								
				foreach ($namenodes as $index => $node) {
//					if (!empty($perspective->getElementsByTagName('responseFormula')->item($index)->nodeValue)) {
						if (array_key_exists('RESP_'.$node->nodeValue, $row) || array_key_exists($node->nodeValue, $row))
						{
							if (array_key_exists('RESP_'.$node->nodeValue, $row)) {
								$value = $row['RESP_'.$node->nodeValue];
							}
							else {
								$value = $row[$node->nodeValue];
							}
							
							if ($value instanceof \DateTime) {
								$value = $value->format('Y-m-d H:i:s');
							}
							$value = $this->cleanColumnValue($value, 'text', false);
							
// 							$type = trim($datatypenodes->item($index)->nodeValue);
// 							if ($type == 'date') {
// 							    $type = 'datetime';
// 							}						
							
							$output .= '{"@columnnumber":'.$column_number.', "@name":"RESP_'.$node->nodeValue.'", "text":["'.$value.'"]},';
							$column_number++;
						}
//					}
				}
				$output = substr_replace($output, ']},', -1);
				if (!empty($child_count))
				{
					$output .= $this->generateChildNodes($row, $records, $namenodes, $datatypenodes);
				}
			}
		}
		return $output;
	}


	

	private function indexDataTableDocs($datatables_data, $application, $parentdocid, $docova,  $view_handler, $em, $properties)
	{
		$docovaapp = $docova->DocovaApplication(null, $application);
		$appid = $application->getId();
		foreach ( $datatables_data as $dtdata)
		{
			
			$dtid = $dtdata['name'];
				
			$view = $em->getRepository('DocovaBundle:AppViews')->getDataTableInfo($dtid, $properties->getId());
			$view = $view[0];
			
			if ( empty($view))
				 throw $this->createNotFoundException('Could not find datatable view');

			
			//clear datatables view
			//$view_handler->deleteDocument($view_id, $parentdocid, true);
			$frmid = $view["viewAlias"];
			if ( $frmid == "local"){
				continue;
			}
			$vid = $view["id"];
			$view_id = str_replace('-', '', $vid);
			
			
			$frm = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' => $frmid));
			foreach ( $dtdata["items"] as $itemarr)
			{

				if ( $itemarr->status == ""){
					continue;
				}
			
				$values_array = Array();
				//$values_array = Array("__form" => "test");
				//$values_array = array_merge($values_array, Array("__parentdoc" => $parentdocid) );
				$dockey = $itemarr->docid;

				if ( $itemarr->status == "removed")
				{
					//remove this record
					$todel = $dockey;
					$docovadoc = $docova->DocovaDocument($docovaapp, $dockey, '', null);
					$docovadoc->deleteDocument();
					
				}else
				{
					
					if ( empty($dockey))
					{
						$docovadoc = $docova->DocovaDocument($docovaapp, null, '', null);
						$docovadoc->setField("form", $frm->getFormName());
					}else{

						$exists = $em->getRepository('DocovaBundle:Documents')->docExists($dockey, $appid);
						if ( ! $exists )
						{
							$view_handler->deleteDocument($view_id, $dockey);
							$dockey = "";
						}
						$docovadoc = $docova->DocovaDocument($docovaapp, $dockey, '', null);
						if  ( ! $exists ){
							$docovadoc->setField("form", $frm->getFormName());
						}
					}

					foreach ( $itemarr->fields as $dtfields)
					{
						$tarr = Array($dtfields->id => $dtfields->value);
						$values_array = array_merge($values_array, $tarr );
						$docovadoc->setField($dtfields->id , $dtfields->value);	
					}

					if ( empty($dockey))
					{
						
						$dockey = $docovadoc->getId();
					}
					$docovadoc->addToDataTable($parentdocid, $dtid, $view, $values_array);

					$docovadoc->save(true);	
				}
			}
			
		}

				
	}

	
	
	private function preProcessPostValues($request_lower, &$datatables_data)
	{
		//preprocess..all multivalue to array
		$datetimefields = [];
		$numfields = [];
		$datatables = [];
		$datatablevalues = [];

		if ( isset($request_lower['__datatablenames'])){
			$datatables =  explode(';', $request_lower['__datatablenames']);
			unset($request_lower['__datatablenames']);
		}

		$datatablevalues = array_map(function($value) { return $value."_values"; }, $datatables);

		if ( isset($request_lower['__dtmfields'])){
			$datetimefields =  explode(chr(31), $request_lower['__dtmfields']);
			unset($request_lower['__dtmfields']);
		}

		if ( isset($request_lower['__numfields'])){
			$numfields =  explode(chr(31), $request_lower['__numfields']);
			unset($request_lower['__numfields']);
		}

		

		foreach ( $request_lower as $reqkey => $reqval)
		{
			if ( in_array ($reqkey, $datatablevalues))
			{
				$rawjson = $request_lower[$reqkey];
				$rawjson = rawurldecode($rawjson);
				$dtjson =json_decode($rawjson); 	
				$data_island_name = substr($reqkey, 0, strlen($reqkey) - strlen("_values"));
				$tmpArr = Array ( "name" => $data_island_name, "items" => $dtjson);
				array_push($datatables_data, $tmpArr);
			}
			
			if ( ! is_array($reqval)){
				if (  strpos($reqval, chr(31)) !== false){
					$request_lower[$reqkey] = explode(chr(31), $reqval);
				}

				//convert date time values
				if ( in_array($reqkey, $datetimefields)  ){
					if ( is_array($request_lower[$reqkey] ) ){
						$datearr = $request_lower[$reqkey];
						foreach ($datearr as $dtmkey => $dtmval ){
								
								$datearr [$dtmkey] =$this->getValidDateTimeValue(trim($dtmval));
								
						}
						$request_lower[$reqkey] = $datearr;
					}else{
						$request_lower[$reqkey] = $this->getValidDateTimeValue(trim($request_lower[$reqkey]));
					}
				}else if ( in_array($reqkey, $numfields))
				{
					if ( !empty($reqval) ) {
						if ( is_array($request_lower[$reqkey] ) ){
							$numarr = $request_lower[$reqkey];
							foreach ($numarr as $numkey => $numval ){
									//adding zero will conver to the approprite number format..int, float
									$numarr [$numkey] = trim($numval) + 0;
									
							}
							$request_lower[$reqkey] = $numarr;
						}else{
							$request_lower[$reqkey] = trim($request_lower[$reqkey]) + 0;
						}
					}
				}
			}
		}

		return $request_lower;
	}

	private function computeBeforeSave($form, $request_lower, $appid, $document)
	{
		$form_name = str_replace(array('/', '\\'), '-', $form->getFormName());
		$form_name = str_replace(' ', '', $form_name);

		$compvals = [];
		$txtjsoncomputed= $this->renderView("DocovaBundle:DesignElements:$appid/{$form_name}_computed.html.twig", array(
			'docvalues' => $request_lower,
			'user' => $this->user,
			'application' => $appid,
			'object' => $document
		));
		$retval = html_entity_decode ($txtjsoncomputed);
		$xxval = json_decode($retval, true);
		if(is_null($xxval)){
		    $errormsg = json_last_error();
		}

		if ( count($xxval) > 0){
			$compvals = array_merge(...$xxval);
			$compvalsunserl = $this->un_serialize($compvals);
			$compvals = $compvalsunserl;
		}

		foreach ( $compvals as $compkey => $compval){
			if ( isset ( $request_lower[$compkey]) || $compkey == "__title")
				$request_lower[$compkey] = $compvals[$compkey];
		}
		return $request_lower;
	}

	private function saveDocument($request, $application, $form, $document, $parentDoc, $docovaAcl,  $properties, $docid)
	{
    	$response = new Response();
    	    
    	$newdoc = empty($docid);
    	    
		$em = $this->getDoctrine()->getManager();
		$appid = $application->getId();
		$docova = new Docova($this->container);
		
		$datatables_data = [];
    	    
		
		$access_level = 1;
		if ($docovaAcl->isSuperAdmin()) {
			$access_level = 7;
		}
		elseif ($docovaAcl->isManager() || $docovaAcl->isDesigner() || $docovaAcl->isEditor()) {
			$access_level = 6;
		}
		elseif ($docovaAcl->isAuthor()) {
			$access_level = 3;
		}
		
		if($newdoc && ($access_level < 3 || !$docovaAcl->canCreateDocument())){
			$response->setContent("error;You are not authorized to perform that operation.");			    
			return $response;
//			    throw new AccessDeniedException();
		}else if(!$newdoc && ($access_level < 3 || ($access_level == 3 && !$docovaAcl->isDocAuthor($document)))){
		    $response->setContent("error;You are not authorized to perform that operation.");
		    return $response;
//		    throw new AccessDeniedException();
		}
				
		$renamearray = [];

		$document->setAuthor($this->user);
		$document->setModifier($this->user);
		$document->setDateModified(new \DateTime());
		$document->setIndexed(false);
		if ($newdoc)
		{
			$document->setDocStatus(($properties->getEnableLifecycle()) ? $properties->getInitialStatus() : $properties->getFinalStatus());
			$document->setStatusNo(($properties->getEnableLifecycle()) ? 0 : 1);
			if ($properties->getEnableLifecycle() && $properties->getEnableVersions()) {
				$document->setDocVersion('0.0');
				$document->setRevision(0);
			}
		}
		$document->setLocked(false);
		$document->setLockEditor(null);
		
		if ($newdoc) {
			$em->persist($document);
		}
		$em->flush();
		
		if (!empty($parentDoc))
		{
			$parentDoc = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $parentDoc, 'Archived' => false, 'Trash' => false));
			if (!empty($parentDoc)) 
			{
				$relation_exists = $em->getRepository('DocovaBundle:RelatedDocuments')->findOneBy(array('Parent_Doc' => $parentDoc->getId(), 'Related_Doc' => $document->getId()));
				if (empty($relation_exists))
				{
					$related_doc = new RelatedDocuments();
					$related_doc->setParentDoc($parentDoc);
					$related_doc->setRelatedDoc($document);

					$em->persist($related_doc);
					$em->flush();
				}
			}
		}

		$docovaAcl->removeAllDocAuthors($document);
		$docovaAcl->removeAllDocReaders($document);
		$docovaAcl->addDocAuthor($document, 'ROLE_USER', true);
		$docovaAcl->addDocReader($document, 'ROLE_USER', true);
//			$docovaAcl->addDocAuthor($document, $this->user);
		$log_obj = new Miscellaneous($this->container);
		if ($newdoc)
			$log_obj->createDocumentLog($em, 'CREATE', $document);
		else 
			$log_obj->createDocumentLog($em, 'UPDATE', $document);

		$params = $request->request->keys();
		$req = $request->request;
		$request_lower = array_change_key_case($req->all(), CASE_LOWER);

		$request_lower = $this->preProcessPostValues($request_lower, $datatables_data);
		
		$request_lower = $this->computeBeforeSave($form, $request_lower, $appid, $document);
		
		//-- set computed title for use in widgets and global search
		if(!empty($request_lower['__title'])){
		    $document->setDocTitle($request_lower['__title']);
		    unset($request_lower['__title']); //--remove so it doesnt get saved with other fields
		    $em->flush();
		}
		
		
		//-- web query save agent
		$wqsagent = $properties->getCustomWqsAgent();
		if(!empty($wqsagent)){
		    $appobj = $docova->DocovaApplication(['appID' => $appid], $application);
		    $inmemorydoc = $docova->DocovaDocument($appobj, (empty($docid) ? $document->getId() : $docid), '', $document);
		    $ignorefields = ["__attachments"];
		    foreach ($request_lower as $key => $value){			        
		        if(!in_array($key, $ignorefields)){
		        	$tempfield = $inmemorydoc->replaceItemValue($key, $value);
       		        	$tempfield->modified = false;
		        }
		    }
		    $inmemorydoc->isModified = false;
		    $docova_agent = $docova->DocovaAgent($appobj, $wqsagent);
		    $appobj = null;
		    $docova_agent->run($inmemorydoc);
			    
		    if($inmemorydoc->isModified){
		        $fields = $inmemorydoc->fieldBuffer;
		        $fieldkeys = array_keys($fields);
		        for($i=0; $i<count($fieldkeys); $i++){
		            $docfield = $fields[$fieldkeys[$i]];
		            if ($docfield->modified) {
		                 if($docfield->remove){
		                     if(array_key_exists(strtolower($docfield->name), $request_lower) ){
		                         unset($request_lower[strtolower($docfield->name)]);
		                     }
		                 }else{
		                     $request_lower[strtolower($docfield->name)] = $docfield->value;
		                 }
		            }
		        }
		    }
		    $inmemorydoc = null;
		}
			
		$form_fields = $em->getRepository('DocovaBundle:DesignElements')->getFormElementsBy($params, $form->getId(), $appid);

		foreach ($params as $key) {
			$origkey = $key;
			$key = strtolower($key);
			if (array_key_exists($key, $form_fields)) {
				$field = $em->getReference('DocovaBundle:DesignElements', $form_fields[$key]['id']);
			}
			if (empty($field)) {
				if ($origkey === 'tmpDeletedFiles' && trim($req->get($origkey))) {
//						$file_names = preg_split('/; /', $req->get($key));
					$file_names = preg_split('/\*/', $req->get($origkey));
					$removes = array();
					for ($i = 0; $i < count($file_names); $i++) {
						if (!empty($file_names[$i])) {

							$file_record = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document' => $document->getId(), 'File_Name' => $file_names[$i]));

							if (!empty($file_record) && (!$file_record->getCheckedOut() || $file_record->getCheckedOutBy()->getUserNameDnAbbreviated() === $this->user->getUserNameDnAbbreviated())) {
								$em->remove($file_record);
								$em->flush();
								
								if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($file_names[$i]))) {
									@unlink($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($file_names[$i]));
									$removes[] = $file_names[$i];
								}
							}
						}
					}
					if (!empty($removes)) {
						$log_obj->createDocumentLog($em, 'UPDATE', $document, 'Deleted file(s): '.implode(',', $removes));
						$removes = null;
					}
				}else if ($origkey === 'tmpRenamedFiles' && trim($req->get($origkey)) && !empty( trim($req->get($origkey))) ) {
					$file_names =  preg_split('/\;/', $req->get("tmpRenamedFiles"));
					for ($i = 0; $i < count($file_names); $i++) {
						if (!empty($file_names[$i])) {
							$valarry = preg_split('/\,/', $file_names[$i]);
							$origname = trim(substr($valarry[0], 2));
							$newname = trim(substr($valarry[1], 2));
							$renamearray[$newname] = $origname;
						}
					}
				}
				continue;
			}
/*
* NOT USED
			$separators = array();
			if ($form_fields[$key]['multiSeparator']) {
				$separators = implode('|', explode(' ', $form_fields[$key]['multiSeparator']));
			}
*/
			$values = array();

			//we are expecting a post value here that would have concatenated multivalues into a string using the seperator.
			//if we are getting an array here, its because the computed fields code has done so....implode it into an array that 
			//the rest of the code expects

			//if ( is_array($request_lower[$key]))
			//	$request_lower[$key] = implode(explode(' ', $field->getMultiSeparator())[0], $request_lower[$key]);

			switch ($form_fields[$key]['fieldType'])
			{
				case 0:
				case 2:
					if (!$newdoc) {
						$form_values = $em->getRepository('DocovaBundle:FormTextValues')->findBy(array('Document' => $docid, 'Field' => $form_fields[$key]['allids']));
						if (!empty($form_values)) {
							foreach ($form_values as $record) {
								$em->remove($record);
							}
							$em->flush();
						}
					}

					$values = $request_lower[$key];
					if (!is_array($values)) 
					{
						$summary = substr($values, 0, 450);
						if (false !== strpos($values, '|')) {
							$values = explode('|', $values);
							$summary = $values[1];
							$values = $values[0];
						}
						$values = array($values);
						$summary = array($summary);
					} else {
						$summary = array();
						for ($x = 0; $x < count($values); $x++) {
							$summary[] = substr($values[$x], 0, 450);
						}
					}
						
						
					
					if (!empty($values)) {
						$len = count($values);
						for ($x = 0; $x < $len; $x++) {
							if (!empty($values[$x]))
							{
								$form_value = new FormTextValues();
								$form_value->setSummaryValue($summary[$x]);
								$form_value->setDocument($document);
								$form_value->setField($field);
								$form_value->setFieldValue($values[$x]);
								$form_value->setOrder($len > 1 ? $x : null);
								$form_value->setTrash(false);
								$em->persist($form_value);
								$em->flush();
							}
						}
					}
					break;
				case 1:
					if (!$newdoc) {
						$form_values = $em->getRepository('DocovaBundle:FormDateTimeValues')->findBy(array('Document' => $docid, 'Field' => $form_fields[$key]['allids']));
						if (!empty($form_values)) {
							foreach ($form_values as $record) {
								$em->remove($record);
							}
							$em->flush();
						}
					}
					$values = $request_lower[$key];
					if (is_array($values)) {
						for ($x = 0; $x < count($values); $x++) {
							$values[$x] = $this->getValidDateTimeValue($values[$x]);
						}
					}
					else {
						$values = array($this->getValidDateTimeValue($request_lower[$key]));
					}
					
					if (!empty($values))
					{
						$len = count($values);
						for ($x = 0; $x < $len; $x++) {
							if (!empty($values[$x])) {
								$form_value = new FormDateTimeValues();
								$form_value->setDocument($document);
								$form_value->setField($field);
								$form_value->setFieldValue($values[$x]);
								$form_value->setOrder($len > 1 ? $x : null);
								$form_value->setTrash(false);
								$em->persist($form_value);
								$em->flush();
							}
						}
					}
					break;
				case 3:
					if (!$newdoc) {
						$form_values = $em->getRepository('DocovaBundle:FormNameValues')->findBy(array('Document' => $docid, 'Field' => $form_fields[$key]['allids']));
						if (!empty($form_values)) {
							foreach ($form_values as $record) {
								$em->remove($record);
							}
							$em->flush();
						}
						$form_values = $em->getRepository('DocovaBundle:FormGroupValues')->findBy(array('Document' => $docid, 'Field' => $form_fields[$key]['allids']));
						if (!empty($form_values)) {
							foreach ($form_values as $record) {
								$em->remove($record);
							}
							$em->flush();
						}
					}
					$values = $request_lower[$key];
				
					if (is_array($values)) {
						for ($x = 0; $x < count($values); $x++) {
							$usernamestr = trim($values[$x]);
							if ( $usernamestr != "" )
							{
								$identity = $this->findUser($usernamestr);
								if (empty($identity))
								{
									//we are given name in the full format i.e name/Docova..we need to get the proper
									//format to seach for the group name
									$groupname = $docova->DocovaName($usernamestr);
									$groupnamecn = $groupname->__get("Common");
									$identity = $this->findGroup($groupnamecn);
								}
								if (empty($identity))
								{
									$identity = $this->createInactiveUser($usernamestr);
								}
								$values[$x] = $identity;
							}
						}
					}
					else {
						if ( $request_lower[$key] && $request_lower[$key] != "" )
						{
							$usernamestr = trim($request_lower[$key]);
							if($usernamestr != "")
							{
							$identity = $this->findUser($usernamestr);
							if (empty($identity))
								{
								//we are given name in the full format i.e name/Docova..we need to get the proper
								//format to seach for the group name
									$groupname = $docova->DocovaName($usernamestr);
								$groupnamecn = $groupname->__get("Common");
								$identity = $this->findGroup($groupnamecn);
								}
								if (empty($identity))
								{
									$identity = $this->createInactiveUser($usernamestr);
								}
								$values = array($identity);
							}
							else{
								$values = null;
							}
						}
					}
					
					if (!empty($values))
					{
						$restricted_reader = false;
						$len = count($values);
						for ($x = 0; $x < $len; $x++) {
							if (!empty($values[$x])) {
								if ($values[$x] instanceof \Docova\DocovaBundle\Entity\UserAccounts)
								{
									$form_value = new FormNameValues();
									$form_value->setDocument($document);
									$form_value->setField($field);
									$form_value->setFieldValue($values[$x]);
									$form_value->setOrder($len > 1 ? $x : null);
									$form_value->setTrash(false);
									$em->persist($form_value);
									$em->flush();
									if ($form_fields[$key]['nameFieldType'] == 2)
									{
										$docovaAcl->addDocReader($document, $values[$x]);
										$restricted_reader = true;
									}
									elseif ($form_fields[$key]['nameFieldType'] == 3) {
										$docovaAcl->addDocAuthor($document, $values[$x]);
									}
								}
								elseif ($values[$x] instanceof \Docova\DocovaBundle\Entity\UserRoles)
								{
									$form_value = new FormGroupValues();
									$form_value->setDocument($document);
									$form_value->setField($field);
									$form_value->setFieldValue($values[$x]);
									$form_value->setOrder($len > 1 ? $x : null);
									$form_value->setTrash(false);
									$em->persist($form_value);
									$em->flush();
									if ($form_fields[$key]['nameFieldType'] == 2)
									{
										$docovaAcl->addDocReader($document, $values[$x]->getRole(), true);
										$restricted_reader = true;
									}
									elseif ($form_fields[$key]['nameFieldType'] == 3) {
										$docovaAcl->addDocAuthor($document, $values[$x]->getRole(), true);
									}
								}
							}
						}
						if ($restricted_reader === true)
						{
							$docovaAcl->removeDocReader($document, 'ROLE_USER', true);
							$docovaAcl->removeDocAuthor($document, 'ROLE_USER', true);
						}
					}
					break;
				case 4:
					if (!$newdoc) {
						$form_values = $em->getRepository('DocovaBundle:FormNumericValues')->findBy(array('Document' => $docid, 'Field' => $form_fields[$key]['allids']));
						if (!empty($form_values)) {
							foreach ($form_values as $record) {
								$em->remove($record);
							}
							$em->flush();
						}
					}
					$values = $request_lower[$key];
					if (is_array($values)) {
						for ($x = 0; $x < count($values); $x++) {
							$values[$x] = floatval($values[$x]);
						}
					}
					else {
						$values = array(floatval($values));
					}
									
					if (!empty($values))
					{
						$len = count($values);
						for ($x = 0; $x < $len; $x++) {
							if (!empty($values[$x])) {
								$form_value = new FormNumericValues();
								$form_value->setDocument($document);
								$form_value->setField($field);
								$form_value->setFieldValue($values[$x]);
								$form_value->setOrder($len > 1 ? $x : null);
								$form_value->setTrash(false);
								$em->persist($form_value);
								$em->flush();
							}
						}
					}
					break;
				case 5:
					if ($request->files->count()) {
						$file_date_list =  trim($req->get('OFileDates'));
						$file_dates = [];
						if ( !empty($file_date_list)){
							$file_dates = explode(';', $file_date_list);
							if (!empty($file_dates)) 
							{
								foreach ($file_dates as $index => $date) {
									try {
										$d = new \DateTime($date);
										$file_dates[$index] = $d;
										$d = null;
									}
									catch (\Exception $e) {
										$file_dates[$index] = new \DateTime();
									}
								}
							}
						}
						$filelist = explode('*', $request_lower[$key]);
						$this->uploadDocAttachment($request->files, $document, $field, $file_dates, $filelist, $renamearray);
						continue;
					}
					break;
				default:
					if (!$newdoc) {
						$form_values = $em->getRepository('DocovaBundle:FormTextValues')->findOneBy(array('Document' => $docid, 'Field' => $form_fields[$key]['allids']));
						if (!empty($form_values)) {
							foreach ($form_values as $record) {
								$em->remove($record);
							}
							$em->flush();
						}
					}
					$values = $request_lower[$key];
					if (is_array($values)) {
						$summary = array();
						for ($x = 0; $x < count($values); $x++) {
							$summary[] = substr($values[$x], 0, 450);
						}
					}
					else {
						$values = array($values);
						$summary = array(substr($values[0], 0, 450));
					}
					
					if (!empty($values))
					{
						$len = count($values);
						for ($x = 0; $x < $len; $x++) {
							if (!empty($values[$x]))
							{
								$form_value = new FormTextValues();
								$form_value->setSummaryValue($summary[$x]);
								$form_value->setDocument($document);
								$form_value->setField($field);
								$form_value->setFieldValue($values[$x]);
								$form_value->setOrder($len > 1 ? $x : null);
								$form_value->setTrash(false);
								$em->persist($form_value);
								$em->flush();
							}
						}
					}
					break;
			}
			$field = $form_value = null;
		}


		//handle renamed files
		if ($req->get('tmpRenamedFiles'))
		{
			$file_names =  preg_split('/\;/', $req->get("tmpRenamedFiles"));
			for ($i = 0; $i < count($file_names); $i++) {
				if (!empty($file_names[$i])) {
					$valarry = preg_split('/\,/', trim($file_names[$i]));
					$origname = trim(substr($valarry[0], 2));
					$newname = trim(substr($valarry[1], 2));

					
					$file_record = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document' => $document->getId(), 'File_Name' => $origname));

					if (!empty($file_record) && (!$file_record->getCheckedOut() || $file_record->getCheckedOutBy()->getUserNameDnAbbreviated() === $this->user->getUserNameDnAbbreviated())) {
						$file_record->setFileName($newname);

						$em->flush();
						
						if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($origname))) {
							@rename($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($origname), $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($newname));
						}
					}
				}
			}
		}
		//handle tmpbackendchangesXML
		if ($req->get('tmpBackendChangesXML')){
			$changes_xml = new \DOMDocument('1.0', 'UTF-8');
			$changes_xml->loadXML($req->get('tmpBackendChangesXML'));
			$fields = $changes_xml->getElementsByTagName('Fields')->item(0)->childNodes;
			$docovaapp = $docova->DocovaApplication(null, $application);
			$docovadoc = $docova->DocovaDocument($docovaapp, $document->getId(), '', $document);
			$docovadoc->syncFieldsFromXML($fields);
			$docovadoc->save(false);
			$docovaapp = $docovadoc = null;
		}
		
		if ($req->get('tmpRequestDataXml'))
		{
			$request_data = new \DOMDocument('1.0', 'UTF-8');
			$request_data->loadXML($req->get('tmpRequestDataXml'));
			$wfProcess = $this->get('docova.workflowprocess');
			$wfProcess->setUser($this->user);
			$wfProcess->setGs($this->global_settings);
			if ($request_data->getElementsByTagName('Action')->item(0)->nodeValue == 'RELEASEVERSION')
			{
				if (true === $wfProcess->releaseDocument($request_data, $document))
				{
					$version = $request_data->getElementsByTagName('Version')->item(0)->nodeValue;
					$log_obj->createDocumentLog($em, 'UPDATE', $document, 'Released document as version '.(!empty($version) ? $version : '0.0.0'));
				}
				else {
					//@TODO: log the possible error(s)
				}
			}
			elseif (!$newdoc && $request_data->getElementsByTagName('Action')->item(0)->nodeValue == 'REVIEW')
			{
				$wfProcess->wfServiceREVIEW($request_data, $document);
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
			if (empty($wfProcess))
			{
				$wfProcess = $this->get('docova.workflowprocess');
				$wfProcess->setUser($this->user);
				$wfProcess->setGs($this->global_settings);
			}
			
			$request_workflow = new \DOMDocument('1.0', 'UTF-8');
			$request_workflow->loadXML($req->get('tmpWorkflowDataXml'));
			$docsteps = $document->getDocSteps();
			if (empty($docsteps) || $docsteps->count() == 0)
			{
				if (!$newdoc && $req->get('wfEnableImmediateRelease') == 1 && $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue === 'FINISH')
				{
					$wfProcess->releaseDocument($request_workflow, $document, true);
				}
				else {
					if (!$newdoc || $req->get('isWorkflowStarted') == 1 || $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue !== 'FINISH') {
						$wfProcess->createDocumentWorkflow($document, $request_workflow);
					}
				}

				if ($req->get('isWorkflowStarted') == 1)
				{
					$action = $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue;
					$nextnode = $request_workflow->getElementsByTagName('NextStep')->item(0)->nodeValue;
					


					$step = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findBy(array('Document' => $document->getId(), 'IsCurrentStep' =>true));
					
					if (!empty($step[0]))
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
							$em->refresh($document);
							
							$log_obj->createDocumentLog($em, 'WORKFLOW', $document, 'Start - Completed workflow step.');
							$nextstep =  $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findBy(array('Document' => $document->getId() ,'Position' => $nextnode));	
							
							if ( !empty($nextstep[0]))
							{
								$next_step = $nextstep[0];
								if ($next_step->getStatus() === 'Pending' && !$next_step->getDateStarted() && !$next_step->getDateCompleted())
								{
									$next_step->setIsCurrentStep(true);
									$next_step->setDateStarted(new \DateTime());
									$document->setDocStatus($next_step->getDocStatus());
									$em->flush();
									$em->refresh($next_step);
									$wfProcess->sendWfNotificationIfRequired($next_step, 1);
									
								}
							}
							
						}
						elseif ($newdoc && $step[0]->getDocStatus())
						{
							$document->setDocStatus($step[0]->getDocStatus());
							$em->flush();
						}
						elseif (!$newdoc)
						{
							$step = $step[0];
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
							
								if (!$document->getAppForm() && !empty($request_workflow->getElementsByTagName('UserComment')->item(0)->nodeValue))
								{
/*
* @TODO: apply different logic to check if advanced
* if advanced comments are enabled in applications
* 
									$comment_txt = trim($request_workflow->getElementsByTagName('UserComment')->item(0)->nodeValue);
									$comment_subform = $em->getRepository('DocovaBundle:DocumentTypes')->containsSubform($document->getDocType()->getId(), 'Advanced Comments');
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
											$comment->setDocument($document);
											$em->persist($comment);
											$em->flush();
										}
										$temp_xml = $comment_subform = null;
									}
*/
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
									$step->setIsCurrentStep(false);
									$step->setStatus('Completed');
									$em->flush();
//										$security_check->createDocumentLog($em, 'WORKFLOW', $document, $step->getStepName().' - Completed workflow step.'.(!empty($comment_txt) ? ' Comments: '.$comment_txt : ''));
									$wfProcess->sendWfNotificationIfRequired($step, 2);
								}
								else {
//										$security_check->createDocumentLog($em, 'WORKFLOW', $document, $step->getStepName().' - Completed workflow task.'.(!empty($comment_txt) ? ' Comments: '.$comment_txt : ''));
								}
							}
							
								
							if (!empty($completed))
							{
								$em->refresh($document);
								$nextstep =  $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findBy(array('Document' => $document->getId() ,'Position' => $nextnode));	




								$next_step = $nextstep[0];
								$next_step->setDateStarted(new \DateTime());
								$next_step->IsCurrentStep(true);
								if ($next_step->getDocStatus())
								{
									$document->setDocStatus($next_step->getDocStatus());
									$document->setStatusNo(0);
								}

								$em->flush();
								$em->refresh($next_step);
								$wfProcess->sendWfNotificationIfRequired($next_step, 1);
										
							}
							$step = null;
						}
					}
				}
				elseif ($newdoc) {
					$action = $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue;
					if ($action === 'FINISH' && $workflow_obj->getBypassRleasing()) {
						if (true == $wfProcess->releaseDocument($request_workflow, $document, true)) {
							$log_obj->createDocumentLog($em, 'WORKFLOW', $document, 'Completed document workflow.');
						}
					}
				}
			}
			elseif ($req->get('isWorkflowStarted') == 1 || $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue === 'UPDATE')
			{
				$res = $wfProcess->workflowItemProcess($request_workflow, $document);
				if (empty($res))
				{
					throw $this->createNotFoundException('Could not update workflow step action.');
				}
			}
			elseif ($req->get('wfEnableImmediateRelease') == 1 && $request_workflow->getElementsByTagName('Action')->item(0)->nodeValue === 'FINISH') {
				// do we need to stop the process if release failed?!
				$wfProcess->releaseDocument($request_workflow, $document, true);
			}
		}

		$view_handler = new ViewManipulation($docova, $appid, $this->global_settings);
		$views = $em->getRepository('DocovaBundle:AppViews')->getAllAppViews($appid);

		//index data table rows coming from the post request
		$this->indexDataTableDocs($datatables_data, $application, $document->getId(), $docova, $view_handler, $em, $properties);

		if (!empty($views)) 
		{
			$dateformat = $this->global_settings->getDefaultDateFormat();
			$display_names = $this->global_settings->getUserDisplayDefault();
			$repository = $em->getRepository('DocovaBundle:Documents');
			$twig = $this->container->get('twig');
			$doc_values = $repository->getDocFieldValues($document->getId(), $dateformat, $display_names, true);
			
			foreach ($views as $v)
			{
				try {
					$view_handler->indexDocument2($document->getId(), $doc_values, $appid, $v->getId(), $v->getViewPerspective(), $twig, false, $v->getConvertedQuery());
				}
				catch (\Exception $e) {
				}
			}
		}
		//now see if we need to index this doc into any data tables.
		$dtviews =  $em->getRepository('DocovaBundle:AppViews')->getDataTableInfoByForm($appid, $form->getId());

		if ( !empty($dtviews))
		{
			if ( empty($doc_values)){
				$doc_values = $repository->getDocFieldValues($document->getId(), $dateformat, $display_names, true);	
			}
			foreach ( $dtviews as $dtview)
			{
				$viewid =  str_replace('-', '', $dtview["id"]);
				$existing = $view_handler->getColumnValues($viewid, array("Parent_Doc") , array ("App_Id" => $appid, "Document_Id" => $document->getId()), null, true);
				if ( !empty($existing)) 
				{
					$existing = $existing[0];
					if ( !empty( $existing))
					{
						$pers = $dtview["viewPerspective"];
						$view_handler->indexDataTableDocument($document->getId(), $doc_values[0], $appid, $viewid, $existing["Parent_Doc"], $pers ); 	
					}
				}
			}
		}

		
		$mode = $request->query->get('mode') ? $request->query->get('mode'): "";
		$tmpmode = isset($request_lower["tmpmode"]) ? $request_lower["tmpmode"] : "";
		$issave = isset($request_lower["issave"]) ? $request_lower["issave"] : "";
		$ismobile =  $request->query->get('Mobile') ? !empty( $request->query->get('Mobile')) : false;

		if ($tmpmode == 'nodoe') 
		{
			$resp = "";
			if ( $issave == "1" )
			{
				$resp = 'url;' . $this->generateUrl('docova_openform', array("formname"=>$form->getFormName(), "docid"=>$document->getId()), true) . '?EditDocument&AppID='. $appid. ($ismobile ? "&Mobile=true&ParentUNID=" . $appid : "");
				$response->setContent($resp);
			}else{
				$resp = 'url;'.$this->generateUrl('docova_blankcontent') . '?OpenPage&docid=' . $document->getId() . ($ismobile ? "&Mobile=true" : '&fid=appViewMain');
				$response->setContent($resp);
			}
		}elseif ($mode == "dialog" and $issave != "1")
		{
			$resp = "url;".$this->generateUrl('docova_blankcontent'). "?OpenPage&docid=" . $document->getId() . "&fldid=appViewMain&mode=dialog&dialogid=" . $request->query->get('dialogid');
			$response->setContent ($resp);
		}else{
			if ( $issave == "1") 
			{
				//'save only - not save and close
				if ( $mode == "dialog") {
					$resp = "url;" . $this->generateUrl('docova_openform') . '/' . $form->getFormName(). '/'.$document->getId().'?EditDocument&AppID='. $appid . "&mode=dialog&dialogid=" . $request->query->get('dialogid') . ";" .  $document->getId();
					$response->setContent($resp);
				}else{
					$resp = 'url;' . $this->generateUrl('docova_openform', array("formname"=>$form->getFormName(), "docid"=>$document->getId()), true) . '?EditDocument&AppID='. $appid . ($ismobile ? "&Mobile=true&ParentUNID=" . $appid : "");
					$response->setContent($resp);
				}
				return $response;
			}
	
			if ( $mode == "window"){
				$resp = 'url;'.$this->generateUrl('docova_blankcontent') . '?OpenPage&docid=' . $document->getId().'&mode=window';
				$response->setContent($resp);
			}else{
				$resp = 'url;'.$this->generateUrl('docova_blankcontent') . '?OpenPage&docid=' . $document->getId() . ($ismobile ? "&Mobile=true" : '&fid=appViewMain');
				$response->setContent($resp);
			}
		}
		
		return $response;
		
	}

	/**
	 * Attach workflow section to the TWIG form
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppFormProperties $properties
	 * @param \Docova\DocovaBundle\Entity\AppForms $form
	 * @param string $docid
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\Libraries $application
	 * @return array
	 */
	private function attachWorkflow($properties, $form, $docid, $document, $application )
	{
		$current_step = null;
		$docstep = false;
		$output = [
			'xml' => '',
			'wfstep' => null,
			'docstep' => null,
			'nodesxml' => null,
			'workflow_arr' => [],
			'workflow_options' => ['isCreated' => false, 'isCompleted' => false, 'createInDraft' => true],
			'action_buttons' => [],
			'available_versions' => []
		];

		$output['available_versions'] = $this->calculateAvailableVersions($docid, $document, $properties);
		
		if (!empty($docid) && !$document->getDocSteps()->count() && $document->getDocStatus() == $properties->getFinalStatus()) {
			$output['workflow_options']['isCreated'] = true;
			$output['workflow_options']['isCompleted'] = true;
			return $output;
		}
		$docova = $this->get('docova.objectmodel');
		$docova->setUser($this->user);
		if ($properties != null && $properties->getEnableWorkflow() && $form->getFormWorkflows()->count())
		{
			if (!empty($docid) && $document->getDocSteps()->count())
			{
				$docova->setApplication($application);
				$docova->setDocument($document);
				$workflow_steps = $document->getDocSteps();
				$changeable = ($this->user->getUserNameDnAbbreviated() == $document->getCreator()->getUserNameDnAbbreviated() || $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) ? true : false;
				$wf_nodes_xmlObj = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Documents')->getWorkflowStepsXML($document, $this->user, $this->global_settings, $changeable);
				$output['nodesxml'] = $wf_nodes_xmlObj->saveXML();

				

				$output['workflow_arr'] = array(
					'Workflow_Name' => $workflow_steps[0]->getWorkflowName(),
					'Author_Customization' => $workflow_steps[0]->getAuthorCustomization(),
					'Bypass_Rleasing' => $workflow_steps[0]->getBypassRleasing()
				);
				$docstep = array(
					'document_id' => $docid,
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
					$docstep['AllowUpdate'] = ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/) ? true : '';
					$docstep['AllowPause'] = ($current_step->getStatus() !== 'Paused' && ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/)) ? true : '';
					$docstep['AllowCustomize'] = ($output['workflow_arr']['Author_Customization'] == true && $current_step->getStepType() !== 4 &&  ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/)) ? true : '';
					$docstep['AllowBacktrack'] = ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/) ? true : '';
					$docstep['AllowCancel'] = ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/) ? true : '';
				}
		
				if (!empty($is_denied) && $is_denied === true)
				{
					$docstep['isApprover'] = '';
					$docstep['isReviewer'] = '';
					$docstep['isPublisher'] = '';
				}
		
				$output['workflow_options']['isCreated'] = true;
				$output['workflow_options']['isCompleted'] = false;
				$output['workflow_options']['createInDraft'] = true;
			}
			else
			{

				if (!empty($docid))
				{
					$docova->setApplication($application);
					$docova->setDocument($document);
				}
				$wf = $form->getFormWorkflows();

				$output['xml'] = $wf->count() > 1 ? '<Documents>' : '';
				foreach ($wf as $item) 
				{

					if ( $wf->count() == 1)
					{
						$workflow_detail = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Workflow')->getWorkflowStepsXML($item, $this->user);
						$output['nodesxml'] = $workflow_detail->saveXML();
					}



					$output['xml'] .= '<Document><wfID>'.$item->getId().'</wfID><wfName><![CDATA['.$item->getWorkflowName().']]></wfName>';
					$output['xml'] .= '<wfDescription><![CDATA['.$item->getDescription().']]></wfDescription>';
					$output['xml'] .= ($item->getBypassRleasing() == true) ? '<EnableImmediateRelease>1</EnableImmediateRelease>' : '<EnableImmediateRelease/>';
					$output['xml'] .= ($item->getAuthorCustomization() == true) ? '<wfCustomizeAction>1</wfCustomizeAction>' : '<wfCustomizeAction/>';
					$output['xml'] .= '</Document>';
						
					if ($wf->count() == 1) 
					{
						

						$output['workflow_arr'] = array(
							'id' => $item->getId(),
							'Author_Customization' => $item->getAuthorCustomization(),
							'Bypass_Rleasing' => $item->getBypassRleasing(),
							'Workflow_Name' => $item->getWorkflowName()
						);
						$steps = $item->getSteps();
						if ($steps->count() > 0)
						{
							$output['wfstep'] = $steps->first();
						}
						else {
							$this->createNotFoundException('No Workflow steps were found.');
						}
					}
				}
				$output['xml'] .= $wf->count() > 1 ? '</Documents>' : '';



			}
		
			$complete_review = $approve = $decline = $release = false;
			if (!empty($current_step) && trim($current_step->getCustomReviewButtonLabel()))
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
			if (!empty($current_step) && trim($current_step->getCustomApproveButtonLabel()))
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
			if (!empty($current_step) && trim($current_step->getCustomDeclineButtonLabel()))
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
			if (!empty($current_step) && trim($current_step->getCustomReleaseButtonLabel()))
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
		
			$workflow_detail = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
			if (!empty($workflow_detail)) {
				$wf_buttons = $workflow_detail->getActionButtons();
				if ($wf_buttons->count() > 0)
				{
					foreach ($wf_buttons as $button)
					{
						if (!empty($button_labels[$button->getActionName()]))
						{
							$output['action_buttons'][$button->getActionName()]['Label'] = $button_labels[$button->getActionName()];
						}
						elseif ($button->getActionName() === 'Start_Workflow' && trim($properties->getCustomStartButtonLabel()))
						{
							$value = trim($properties->getCustomStartButtonLabel());
							if (preg_match('~^[<?].*[?>]$~', $value))
							{
								$value = str_replace(array('<?', '<? ', ' ?>', '?>'), '', $value);
								$function = create_function('$docova', $value);
								$output['action_buttons'][$button->getActionName()]['Label'] = $function($docova);
							}
							else {
								$output['action_buttons'][$button->getActionName()]['Label'] = $value;
							}
						}
						else {
							$output['action_buttons'][$button->getActionName()]['Label']	= $button->getButtonLabel();
						}
						$output['action_buttons'][$button->getActionName()]['Script']	= $button->getClickScript();
						$output['action_buttons'][$button->getActionName()]['Primary'] = $button->getPrimaryIcon();
						$output['action_buttons'][$button->getActionName()]['Secondary'] = $button->getSecondaryIcon();
						if (!empty($docid) && $document->getDocStatus() === $properties->getInitialStatus() && ($button->getActionName() === 'Start_Workflow' || $button->getActionName() === 'Workflow')) {
							$output['action_buttons'][$button->getActionName()]['Visible'] = true;
						}
						elseif (!empty($document) && $button->getVisibleOn())
						{
							$function = create_function($button->getVisibleOn()->getFunctionArguments(), $button->getVisibleOn()->getFunctionScript());
							$res = $function($document, $this->user);
							$output['action_buttons'][$button->getActionName()]['Visible'] = $res;
						}
						elseif (empty($document) && $button->getActionName() !== 'Start_Workflow' && $button->getActionName() !== 'Workflow') {
							$output['action_buttons'][$button->getActionName()]['Visible'] = false;
						}
						else
						{
							$output['action_buttons'][$button->getActionName()]['Visible'] = true;
						}
					}
				}
			}
			$workflow_detail = null;
		}
		$output['docstep'] = $docstep;

		return $output;
	}

	public function un_serialize($inarr)
	{
		foreach ($inarr as $key => $value) {
			if ( is_array($value)){
				foreach( $value as $skey => $val){
					if (false !== @unserialize($val))
						$value[$skey] = unserialize($val);
				}
				$inarr[$key]= $value;
			}else{

				if (false !== @unserialize($value)){
					$unserialized_value = unserialize($value);
					$inarr[$key] = $unserialized_value;
				}
			}
		}
		return $inarr;
	}
	
	public function appOpenFormAction(Request $request, $formname, $docid)
	{
		$id = null;
		unset($id);
		$isprofile = false;
		$app = $request->query->get('AppID');
		$mode = $request->query->get('mode');
		$parentDoc = $request->query->get('isresponse') == 'true' ? $request->query->get('ParentDocID') : null;
		$profileName = "";
		$profileKey = null;
		$access_level = 1;
		$custcss = false;
		$em = $this->getDoctrine()->getManager();

		if ( $request->query->has('profilename') ){
			$profileName =  $request->query->get('profilename');
			$profileKey =  $request->query->get('profilekey');
			$isprofile = true;
			//try and find the profile
			if ( empty($profileName) ){
				throw $this->createNotFoundException('Unspecified profile name!.');
			}
			
			$profile_doc_id = $em->getRepository('DocovaBundle:Documents')->getProfileId($profileName, $app, $profileKey);
			if ( !empty ($profile_doc_id)){
				
				$docid = $profile_doc_id;
			}
		}
		$document = null;
		$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		if (empty($application))
			throw $this->createNotFoundException('Unspecified application source ID');
		
		//we we are not given a form name then get it from the docid specified
		if ( (is_null($formname) || $formname == "0" ) && !empty($docid))
		{
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $docid, 'application' => $app, 'Trash' => false));
			$form = $document->getAppForm();
		}
		if ( empty($form)){
			$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formName' => $formname, 'application' => $app, 'trash' => false));
		}
		$values_array = array();
		if (empty($form))
		{
			$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formAlias' => $formname, 'application' => $app, 'trash' => false));
			if (empty($form)) {
				throw $this->createNotFoundException('Unspecified Form/Application source ID.');
			}
		}
		$this->initialize();
		$is_mobile = $request->query->get('Mobile');
		$mobile_app_device=$request->query->get('device'); // this is coming from mobile app
		if ($is_mobile == 'true' ||  in_array($mobile_app_device, array("android","iOS") ) ) {
		    $is_mobile=true;
		}else{
		    $is_mobile=false;
		}
		
		$docova = new Docova($this->container);
		$docovaAcl = $docova->DocovaAcl($application);
		$docova = null;
		if (!empty($docid)) 
		{
			//if the mode=dialog, then we can search for document just by the unid, ignoring the form.
			//this is because the ws.dialogbox api creates a tmpDIALOGBOX document with the values from the source document
		    if ( empty($document) && $mode != "dialog"){
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $docid, 'appForm' => $form->getId(), 'Trash' => false));
		    }elseif(empty($document)){
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $docid,  'Trash' => false));
		    }

			if (empty($document)) {
				throw $this->createNotFoundException('Unspecified Document source ID.');
			}
			
			$can_edit = false;
			if ($docovaAcl->isSuperAdmin()) {
				$access_level = 7;
				$can_edit = true;
			}
			elseif ($docovaAcl->isManager() || $docovaAcl->isDesigner() || $docovaAcl->isEditor()) {
				$access_level = 6;
				$can_edit = true;
			}
			else {
				$can_edit = $docovaAcl->isDocAuthor($document);
				$access_level = $can_edit === true ? 3 : 2;
			}
			if ($can_edit === false)
			{
				throw new AccessDeniedException();
			}
			
			$values_array = $em->getRepository('DocovaBundle:Documents')->getDocFieldValues($document->getId(), $this->global_settings->getDefaultDateFormat(), $this->global_settings->getUserDisplayDefault(), true);
			
		}
		else {
		    if ($docovaAcl->isSuperAdmin()) {
		        $access_level = 7;
		    }else if($docovaAcl->isManager() || $docovaAcl->isDesigner() || $docovaAcl->isEditor()){
		        $access_level = 6;
		    }
			if ($access_level === 1) {
				$access_level = $docovaAcl->canCreateDocument() ? 3 : 2;
			}
			$miscfunctions = new MiscFunctions();						
			$id = $miscfunctions->generateGuid();
		}
		
		$properties = $form->getFormProperties();
		if ($request->isMethod('POST'))
		{
			if (empty($docid)) 
			{
				$document = new Documents();
				$document->setId(trim($request->request->get('unid')) ? $request->request->get('unid') : $id);
				$document->setOwner($this->user);
				$document->setCreator($this->user);
				$document->setDateCreated(new \DateTime());
				$document->setAppForm($form);
				$document->setApplication($application);
				if ( $isprofile){
					$document->setProfileName($profileName);
					if ( !empty($profileKey))
						$document->setProfileKey($profileKey);

				}
			}
			return $this->saveDocument($request,  $application, $form, $document, $parentDoc, $docovaAcl,  $properties, $docid);

		}

		$parentdocunid = $request->query->get('isresponse') == 'true' ? $request->query->get('ParentDocID') : ($request->query->get('ParentUNID') ? $request->query->get('ParentUNID'): null);
		$values_array_parent = null;
		if ( !empty($parentdocunid)) {
			$parentdoc = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $parentdocunid, 'application' => $app));
			if (!empty($parentdoc)){
				$values_array_parent = $em->getRepository('DocovaBundle:Documents')->getDocFieldValues($parentdoc->getId(), $this->global_settings->getDefaultDateFormat(), $this->global_settings->getUserDisplayDefault());
				$values_array_parent[0] = array_change_key_case($values_array_parent[0], CASE_LOWER);
			}
		}
		
		//attach workflow to the form if form has one
		$options = $this->attachWorkflow($properties, $form, $docid, $document, $application );		

		$form_name = str_replace(array('/', '\\'), '-', $form->getFormName());
		$form_name = str_replace(' ', '', $form_name);

		$computedvalues = [];

		if (!empty($docid) ) {
			$values_array[0] = array_change_key_case($values_array[0], CASE_LOWER);
			$computedvalues = $values_array[0];
		}else{

			$defvals = [];
			if (empty($docid))
			{
				$core_field_values = [
					['__form' => $form_name,
					'__version' => $properties->getEnableLifecycle() && $properties->getEnableVersions() ? '0.0.0' : '',
					'__status' => $properties->getEnableLifeCycle() && $properties->getEnableWorkflow() ? $properties->getInitialStatus() : '',
					'__statusno' => $properties->getEnableLifeCycle() ? 0 : 1]
				];
			}
		
			$txtjsondef = $this->renderView("DocovaBundle:DesignElements:$app/{$form_name}_default.html.twig", array(
				'object' => empty($docid) ? null : $document,
				'user' => $this->user,
				'docvalues' =>  !empty($parentdocunid) ? $values_array_parent[0] : $core_field_values,
				'newunid' => !empty($docid) ? "" : $id
			));
			
			$retval = html_entity_decode($txtjsondef);

			$xxval = json_decode($retval, true);
			if ( count($xxval) > 0){
				$defvals = array_merge(...$xxval);
				$defvals = $this->un_serialize($defvals);
			}
			$computedvalues = $defvals;
			

		}

		$wqoagent = $properties->getCustomWqoAgent();
		if(!empty($wqoagent)){
		    $docova = new Docova($this->container);
		    $appobj = $docova->DocovaApplication(['appID' => $app], $application);
		    if(!empty($document)){
		        $inmemorydoc = $docova->DocovaDocument($appobj, $docid, '', $document);
		    }else{
    		    $inmemorydoc = $appobj->createDocument(['formname'=>$form->getFormName(), 'unid'=>$id]);
		    }
		    $ignorefields = ["__attachments"];
		    foreach ($computedvalues as $key => $value){
		        if(!in_array($key, $ignorefields)){
    		        $tempfield = $inmemorydoc->replaceItemValue($key, $value);
    		        $tempfield->modified = false;
		        }
		    }
		    $inmemorydoc->isModified = false;
		    $docova_agent = $docova->DocovaAgent($appobj, $wqoagent);
		    $docova = null;
		    $appobj = null;
		    $docova_agent->run($inmemorydoc);
		    if($inmemorydoc->isModified){
    		    foreach ($inmemorydoc->fieldBuffer as $field)
    		    {
    		        if ($field->modified) {
    		            if($field->remove){
    		                if(array_key_exists(strtolower($field->name), $computedvalues) ){
        		                unset($computedvalues[strtolower($field->name)]);
    		                }
    		            }else{
        		            $computedvalues[strtolower($field->name)] = $field->value;
    		            }
    		        }
    		    }
		    }
		    $inmemorydoc = null;
		}
		//check form custom css

		if ( !empty( $form))
		{
			$name = str_replace(array('/', '\\'), '-', $form->getFormName());
			$name = str_replace(' ', '', $name);
			$filepath = $this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app.DIRECTORY_SEPARATOR."FORM".DIRECTORY_SEPARATOR.$name.'.css';
			if (file_exists($filepath)) 
			{
				$custcss = true;
			}
			$this->loadDataTablesData($em, $app, $properties, $application, $computedvalues, $docid);
		}
		$mobileExtension = $is_mobile === true ? '_m' : '';
		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
		$renderedView = $this->renderView("DocovaBundle:DesignElements:$app/{$form_name}".$mobileExtension.".html.twig", array(
			'unid' => !empty($docid) ? $docid : $id,
			'user' => $this->user,
			'useraccess' => $access_level,
			'applicationname' => $application->getLibraryTitle(),
			'settings' => $this->global_settings,
			'form' => $form,
			'custcss' => $custcss,
			'mode' => !empty($docid) ? 'edit' : 'new',
			'isprofile' => $isprofile,
			'isrefresh' => false,
			'docvalues' => $computedvalues,
			'object' => empty($docid) ? null : $document,
			'document' => empty($docid) ? null : $values_array,
			'parentdocument' => $values_array_parent,
			'available_versions' => $options['available_versions'],
			'workflow_options' => $options['workflow_options'],
			'workflows' => $options['workflow_arr'],
			'workflow_buttons' => $options['action_buttons'],
			'wf_nodes_xml' => $options['nodesxml'],
			'wf_xml' => $options['xml'],
			'current_step' => $options['wfstep'],
			'docstep' => $options['docstep']
		));
		
		$response = new Response();
		$response->setContent($renderedView);
		return $response;
	}

	public function loadDataTablesData($em, $app, $properties, $application, &$computedvalues, $docid)
	{
	//get data tables json
		//$dtviews = $properties->getDataTableViews();
		$dtviews = $em->getRepository('DocovaBundle:AppViews')->getFormDataTableViews($app, $properties->getId());
		$docova = new Docova($this->container);
		foreach ( $dtviews as $dtview)
		{
			if ( $dtview["viewAlias"] == "local"){
				continue;
			}

			$dtname = $dtview["viewName"];
			$viewid = $dtview["id"];
			$viewpers = $dtview["viewPerspective"];
			$xmlpers = new \DOMDocument();
			$xmlpers->loadXML($viewpers);
			$viewid = str_replace('-', '', $viewid);

			
			$view_handler = new ViewManipulation($docova, $application, $this->global_settings);
			$rest = $view_handler->getDocumentsByColumnsKey($viewid, Array("Parent_doc"), Array($docid),true, null, null, Array("Created"=>"ASC"));
			$jsonArray = [];
			for ( $p = 0; $p < count($rest); $p++)
			{
				$cur = $rest[$p];
				$tmparr["docid"] = $cur["Document_Id"];
				$tmparr["fields"] = Array();
				$index = 0;
				foreach ( array_keys($cur) as $arrkey)
				{
					$curval = $cur[$arrkey];
					if ( $index >= 3 )
					{
						$datatype =  trim($xmlpers->getElementsByTagName('dataType')->item($index-3)->nodeValue);
						if ( $datatype == "date"){
							$dttype = $datatype =  trim($xmlpers->getElementsByTagName('dttype')->item($index-3)->nodeValue);
							if ( !empty ($dttype)){
								$dtfmt = $this->global_settings->getDefaultDateFormat();
								$dtfmt = str_replace(array('YYYY', 'MM', 'DD'), array('Y', 'm', 'd'), $dtfmt);
								$fmt = $dtfmt;
								$dtobj = new \DateTime($curval);
								if ( $dttype == "time"){
									$fmt = "h:i A";
								}else if ( $dttype == "datetime"){
									$fmt = $dtfmt." h:i A";
								}
								$curval = $dtobj->format($fmt);
							}
						}
					}
					$newarr = array("id" => $arrkey, "value" => $curval );
					$tmparr['fields'][] = $newarr;
					$index++;
					//array_push($tmparr["fields"], );
				}
				array_push($jsonArray, $tmparr);
			}
			$myJSON = rawurlencode(json_encode($jsonArray));
			
			$computedvalues[$dtname ."_values"] = $myJSON;
		}	
	}
	
	public function readDocumentAction(Request $request, $docid)
	{
		$custcss = false;
		$app = $request->query->get('AppID');
		if(empty($app)){
    			$app = $request->query->get('ParentUNID');
		}
		$em = $this->getDoctrine()->getManager();
		$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		if (empty($application)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}
		$object = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $docid, 'application' => $app));
		if (empty($object)) {
			throw $this->createNotFoundException('Unspecified source document ID.');
		}
		
		$this->initialize();
		$is_mobile = $request->query->get('Mobile');
		$mobile_app_device=$request->query->get('device'); // this is coming from mobile app
		if ($is_mobile == 'true' ||  in_array($mobile_app_device, array("android","iOS") ) ) {
		    $is_mobile=true;
		}else{
		    $is_mobile=false;
		}
		
		$access_level = 1;
		$wf_nodes_xml = '';
		$docova = new Docova($this->container);
		$docovaAcl = $docova->DocovaAcl($application);
		$docova = null;
		if ($docovaAcl->isSuperAdmin()) {
			$access_level = 7;
		}
		elseif ($docovaAcl->isManager() || $docovaAcl->isDesigner() || $docovaAcl->isEditor()) {
			$access_level = 6;
		}
		elseif ($docovaAcl->isAuthor($object)) {
			$access_level = 3;
		}
		elseif ($docovaAcl->isDocReader($object)) {
			$access_level = 2;
		}
		else { 
			throw new AccessDeniedException();
		}
		
		$formname = $request->query->get('form');
		//we we are not given a form name then get it from the docid specified
		if ( (is_null($formname) || $formname == "0" )){
		    $form = $object->getAppForm();
		}else{
		    $form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formName' => $formname, 'application' => $app, 'trash' => false));
		    if (empty($form))
	            {
	            	$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formAlias' => $formname, 'application' => $app, 'trash' => false));
	            	if (empty($form)) {
		                throw $this->createNotFoundException('Unspecified Form/Application source ID.');
	            	}
	            }
		}
		
		$docvalues = $em->getRepository('DocovaBundle:Documents')->getDocFieldValues($object->getId(), $this->global_settings->getDefaultDateFormat(), $this->global_settings->getUserDisplayDefault(), true);
		$properties = $form->getFormProperties();
		$att_properties = $form->getAttachmentProp();
		$workflow = $action_buttons = $att_templates = array();
		$workflow_options = array(
			'isCreated' => false,
			'isCompleted' => false,
			'createInDraft' => false
		);
		if (!empty($att_properties) && $att_properties->getTemplateType() != 'None' && $att_properties->getTemplateList())
		{
			$templates = explode(';', $att_properties->getTemplateList());
			$templates = $em->getRepository('DocovaBundle:FileTemplates')->getTemplatesByIds($templates);
			if (!empty($templates)) 
			{
				$att_templates = array('names' => '', 'filenames' => '', 'versions' => '');
				foreach ($templates as $temp) {
					$att_templates['names'] += $temp->getTemplateName() . ',';
					$att_templates['filenames'] += $temp->getFileName() . ',';
					$att_templates['versions'] += $temp->getTemplateVersion() .',';
				}
				$att_templates['names'] = substr_replace($att_templates['names'], '', -1);
				$att_templates['filenames'] = substr_replace($att_templates['filenames'], '', -1);
				$att_templates['versions'] = substr_replace($att_templates['versions'], '', -1);
			}
		}
		if ($properties->getEnableWorkflow()) 
		{
			$docova = $this->get('docova.objectmodel');
			$docova->setUser($this->user);
			$docova->setApplication($application );
			$docova->setDocument($object);
			$workflow_steps = $object->getDocSteps();
			if ($workflow_steps->count() > 0)
			{

				$wf_nodes_xmlObj = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Documents')->getWorkflowStepsXML($object, $this->user, $this->global_settings, false);
				$wf_nodes_xml = $wf_nodes_xmlObj->saveXML();

				$is_workflow_completed = false;
				$workflow = array(
					'Workflow_Name' => $workflow_steps[0]->getWorkflowName(),
					'Author_Customization' => $workflow_steps[0]->getAuthorCustomization(),
					'Bypass_Rleasing' => $workflow_steps[0]->getBypassRleasing()
				);
				foreach ($workflow_steps as $step)
				{

					if ($step->getStepType() === 4 && $step->getDateCompleted()) {
						$is_workflow_completed = true;
						break;
					}
				}
				
				if ($is_workflow_completed === false) 
				{
					$is_denied = false;
					$docstep = array(
						'document_id' => $docid,
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
					foreach ($workflow_steps as  $step)
					{
						if ($step->getStepType() === 1 && $step->getStatus() === 'Completed')
						{
							$docstep['isStarted'] = true;
						}
						if ($step->getStepType() === 1)
						{
							if ($step->getAssignee()->count() && true === $this->searchUserInCollection($step->getAssignee(), $this->user))
								$docstep['isOriginator'] = true;
							elseif ($step->getCompletedBy()->count() && true === $this->searchUserInCollection($step->getCompletedBy(), $this->user, false, true))
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
						if ($step->getIsCurrentStep())
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
						$docstep['AllowUpdate'] = ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/) ? true : '';
						$docstep['AllowPause'] = ($current_step->getStatus() !== 'Paused' && ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/)) ? true : '';
						$docstep['AllowCustomize'] = ($workflow['Author_Customization'] == true && $current_step->getStepType() !== 4 &&  ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/)) ? true : '';
						$docstep['AllowBacktrack'] = ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/) ? true : '';
						$docstep['AllowCancel'] = ($docstep['isOriginator'] === true /*|| $security_check->canEditDocument($document) === true*/) ? true : '';
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

					$workflow_detail = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
					if (!empty($workflow_detail)) {
						$wf_buttons = $workflow_detail->getActionButtons();
						if ($wf_buttons->count() > 0/* && $bookmarked === false*/)
						{
							foreach ($wf_buttons as $button)
							{
								if (!empty($button_labels[$button->getActionName()]))
								{
									$action_buttons[$button->getActionName()]['Label']	= $button_labels[$button->getActionName()];
								}
								elseif ($button->getActionName() === 'Start_Workflow' && trim($properties->getCustomStartButtonLabel()))
								{
									$value = trim($properties->getCustomStartButtonLabel());
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
									$res = $function($object, $this->user);
									$action_buttons[$button->getActionName()]['Visible'] = $res;
								}
								else
								{
									$action_buttons[$button->getActionName()]['Visible'] = true;
								}
							}
						}
					}
					$workflow_detail = null;
					
					$workflow_options['isCreated'] = true;
					$workflow_options['isCompleted'] = $is_workflow_completed;
					$workflow_options['createInDraft'] = true;
				}
			}
		}
		
		$form_name = str_replace(array('/', '\\'), '-', $form->getFormName());
		$form_name = str_replace(' ', '', $form_name);
		$docvalues[0] = array_change_key_case($docvalues[0], CASE_LOWER);
		$docvalues = $docvalues[0];

		$wqoagent = $properties->getCustomWqoAgent();
		if(!empty($wqoagent)){
		    $docova = new Docova($this->container);
		    $appobj = $docova->DocovaApplication(['appID' => $app], $application);
	            $inmemorydoc = $docova->DocovaDocument($appobj, $docid, '', $object);
	            $ignorefields = ["__attachments"];
		    foreach ($docvalues as $key => $value){
		        if(!in_array($key, $ignorefields)){
    		        $tempfield = $inmemorydoc->replaceItemValue($key, $value);
	       	        $tempfield->modified = false;
		        }
		    }
		    $inmemorydoc->isModified = false;
		    $docova_agent = $docova->DocovaAgent($appobj, $wqoagent);
		    $docova = null;
		    $appobj = null;
		    $docova_agent->run($inmemorydoc);
		    if($inmemorydoc->isModified){
    		    foreach ($inmemorydoc->fieldBuffer as $field)
	       	    {
    		        if ($field->modified) {
    		            if($field->remove){
    		                if(array_key_exists(strtolower($field->name), $docvalues) ){
    		                    unset($docvalues[strtolower($field->name)]);
    		                }
    		            }else{
    		                $docvalues[strtolower($field->name)] = $field->value;
    		            }
    		        }
	       	    }
		    }
		    $inmemorydoc = null;
		}
		
		//check form custom css

		if ( !empty( $form))
		{
			$name = str_replace(array('/', '\\'), '-', $form->getFormName());
			$name = str_replace(' ', '', $name);
			

			$filepath = $this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app.DIRECTORY_SEPARATOR."FORM".DIRECTORY_SEPARATOR.$name.'.css';
			if (file_exists($filepath)) 
			{
				$custcss = true;
				
			}
			$this->loadDataTablesData($em, $application->getId(), $properties, $application, $document, $object->getId());
		}

		
		$mobileExtension = $is_mobile === true ? '_m' : '';
		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
		return $this->render("DocovaBundle:DesignElements:$app/{$form_name}".$mobileExtension."_read.html.twig", array(
			'user' => $this->user,
			'useraccess' => $access_level,
			'settings' => $this->global_settings,
			'applicationname' => $application->getLibraryTitle(),
			'form' => $form,
			'custcss' => $custcss,
			'object' => $object,
			'docvalues' => $docvalues,
			'docvalues_gotall' => TRUE, 
			'workflow_options' => $workflow_options,
			'workflows' => $workflow,
			'workflow_buttons' => $action_buttons,
			'wf_xml' => '',
			'wf_nodes_xml' => $wf_nodes_xml ? $wf_nodes_xml : '',
			'current_step' => !empty($current_step) ? $current_step : null,
			'docstep' => !empty($docstep) ? $docstep : null,
			'available_versions' => $this->calculateAvailableVersions($docid, $object, $form->getFormProperties()),
			'att_templates' => $att_templates
		));
	}
	
	public function appOpenProfileAction(Request $request, $profile)
	{
	
		///should never come here..old code
		$em = $this->getDoctrine()->getManager();
		$app = $request->query->get('AppID');
		$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		$profileKey = $request->query->get('profilekey');
		$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formName' => $profile, 'application' => $app, 'trash' => false));
		if (empty($form)) 
		{
			$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formAlias' => $profile, 'application' => $app, 'trash' => false));
			if (empty($form)) {
				throw $this->createNotFoundException('Unspecified Form/Application source ID.');
			}
		}
		
		$this->initialize();
		$values_array = array();
		$profile_doc = $em->getRepository('DocovaBundle:Documents')->getProfile($profile, $app, $profileKey);
		if (!empty($profile_doc[0]))
		{
			$profile_doc = $profile_doc[0];
			$values_array = $em->getRepository('DocovaBundle:Documents')->getDocFieldValues($profile_doc->getId(), $this->global_settings->getDefaultDateFormat(), $this->global_settings->getUserDisplayDefault(), true);
			$values_array[0]['__form'] = $profile;
		}

		if ( empty($profile_doc)){
			$miscfunctions = new MiscFunctions();
			$tmpunid = $miscfunctions->generateGuid(); 
		}
		
		if ($request->isMethod('POST'))
		{
			$result = false;
			$docova = new Docova($this->container);
			$docova_app = $docova->DocovaApplication(['appID' => $app]);
			if ($docova_app)
			{
				$params = $request->request->keys();
				$req = $request->request;
				$request_lower = array_change_key_case($req->all(), CASE_LOWER);
				$request_lower = $this->preProcessPostValues($request_lower);

				//run the computed values
				$request_lower = $this->computeBeforeSave($form, $request_lower, $app, $profile_doc);
			

				if ( empty($profile_doc))
					$docova_app->newDocUNID = $tmpunid;

				$core_fields = array('__dtmfields','__numfields', 'dleOk','dleCancel','dleFailed','tmpDleDataXml','tmpDleStatusXml','tmpBackendChangesXML','tmpRequestDataXml','tmpLinkDataXml','tmpEmailAuditDataXml','DocKey','DocumentNumber','tmpAddedFiles','tmpDeletedFiles','tmpEditedFiles','LoadedInDLE','OFileNames','OFileDates','ActivityRecipients','tmpVersion','UserHomeServer','isSave','tmpAddedFilesUP','tmpEditedFilesUP','Author', 'Creator', 'DateArchived', 'DateCreated', 'DateDeleted', 'DateModified', 'DeletedBy', 'Description', 'DocStatus', 'DocTitle', 'DocVersion', 'Keywords', 'LockEditor', 'Locked', 'Modifier', 'Owner', 'ReleasedBy', 'ReleasedDate', 'Revision', 'StatusNo', 'Form');
				$field_values = array();
				foreach ($params as $field)
				{
					if (!in_array($field, $core_fields))
					{
						$field = strtolower($field);
						$field_values[$field] = $request_lower[$field];
					}
				}
				if (!empty($field_values)) {
					$result = $docova_app->setProfileFields($profile, $field_values, $profileKey, $form);
				}
			}
			if ($result === true) {
				$response = new Response();
				$profile = $docova_app->getProfileDocument($profile, $profileKey);
				$response->setContent('url;'.$this->generateUrl('docova_blankcontent') . '?OpenPage&docid=' . $profile->getId() .'&fid=appViewMain');
				return $response;
			}
		}
		
		$form_name = str_replace(array('/', '\\'), '-', $form->getFormName());
		$form_name = str_replace(' ', '', $form_name);
		
		if (!empty($profile_doc) ) {
			$values_array[0] = array_change_key_case($values_array[0], CASE_LOWER);
			$computedvalues = $values_array[0];
			//var_dump($computedvalues);
		}else{

			$defvals = [];
		
			$txtjsondef = $this->renderView("DocovaBundle:DesignElements:$app/{$form_name}_default.html.twig", array(
				'object' =>  empty($profile_doc) ? null : $profile_doc,
				'user' => $this->user,
				'newunid' => empty($profile_doc) ? $tmpunid : $profile_doc->getId(),
			));
			
			$retval = html_entity_decode ($txtjsondef);

			$xxval = json_decode($retval, true);
			if ( count($xxval) > 0){
				$defvals = array_merge(...$xxval);
				$defvals = $this->un_serialize($defvals);
			}
			$computedvalues = $defvals;
			
		}

		$properties = $form->getFormProperties();
		$wqoagent = $properties->getCustomWqoAgent();
		if(!empty($wqoagent)){
		    $docova = new Docova($this->container);
		    $appobj = $docova->DocovaApplication(['appID' => $app], $application);
		    if(!empty($profile_doc)){
		        $inmemorydoc = $docova->DocovaDocument($appobj, $profile_doc->getId(), '', $profile_doc);
		    }else{
		        $inmemorydoc = $appobj->createDocument(['formname'=>$form->getFormName(), 'unid'=>$tmpunid]);
		    }
		    $ignorefields = ["__attachments"];
		    foreach ($computedvalues as $key => $value){		        
		        if(!in_array($key, $ignorefields)){
    		        $tempfield = $inmemorydoc->replaceItemValue($key, $value);
	       	        $tempfield->modified = false;
		        }
		    }
		    $inmemorydoc->isModified = false;
		    $docova_agent = $docova->DocovaAgent($appobj, $wqoagent);
		    $docova = null;
		    $appobj = null;
		    $docova_agent->run($inmemorydoc);
		    if($inmemorydoc->isModified){
		        foreach ($inmemorydoc->fieldBuffer as $field)
		        {
		            if ($field->modified) {
		                if($field->remove){
		                    if(array_key_exists(strtolower($field->name), $computedvalues) ){
		                        unset($computedvalues[strtolower($field->name)]);
		                    }
		                }else{
		                    $computedvalues[strtolower($field->name)] = $field->value;
		                }
		            }
		        }
		    }
		    $inmemorydoc = null;
		}

		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
		return $this->render("DocovaBundle:DesignElements:$app/$form_name.html.twig", array(
			'unid' => empty($profile_doc) ? $tmpunid : $profile_doc->getId(),
			'user' => $this->user,
			'settings' => $this->global_settings,
			'form' => $form,
			'applicationname' => $application->getLibraryTitle(),
			'mode' => empty($profile_doc) ? "new" : "edit",
			'object' => $profile_doc,
			'isprofile' => true,
			'isrefresh' => false,
			'docvalues' => $computedvalues,
			'available_versions' => array(),
			'workflow_options' => array('isCreated' => false,'isCompleted' => false,'createInDraft' => false),
			'workflows' => false,
			'workflow_buttons' => array(),
			'wf_xml' => '',
			'current_step' => false,
			'docstep' => false
		));
	}
	
	public function submitRefreshAction(Request $request, $formname, $docid)
	{
		$formname = urldecode($formname);
		$app = $request->query->get('AppID');
		$mode = $request->query->get('mode');
		$access_level = 1;
		$em = $this->getDoctrine()->getManager();
		$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		$isprofile = $request->query->get('isProfile') == "1" ? true : false;
		$document = null;
		$form = $em->getRepository('DocovaBundle:AppForms')->findByNameAlias($formname, $app);
		$id = "";
		$custcss = false;
		//$isprofile = false;
		if (empty($form))
		{
			$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formAlias' => $formname, 'application' => $app, 'trash' => false));
			if (empty($form)) {
				throw $this->createNotFoundException('Unspecified Form/Application source ID.');
			}
		}
		$this->initialize();
		$docova = new Docova($this->container);
		$docovaAcl = $docova->DocovaAcl($application);
		$docova = null;

		if (!empty($docid)) 
		{
			if ( $mode == "dialog")
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $docid,  'Trash' => false));
			else
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $docid, 'appForm' => $form->getId(), 'Trash' => false));
			
			if (empty($document))
			{
				$profileKey = $request->query->get('profilekey');
				$document = $em->getRepository('DocovaBundle:Documents')->getProfile($formname, $app, $profileKey);
				if ( empty ( $document)){
					$document = $em->getRepository('DocovaBundle:Documents')->getProfile($form->getFormAlias(), $app, $profileKey);
				}

				if ( ! empty ( $document)) {
					$isprofile = true;
					$document = $document[0];
				}
				else
					throw $this->createNotFoundException('Unspecified Document source ID.');
			}


			$can_edit = false;
			if ($docovaAcl->isSuperAdmin()) {
				$access_level = 7;
				$can_edit = true;
			}
			elseif ($docovaAcl->isEditor()) {
				$access_level = 6;
				$can_edit = true;
			}
			else {
				$can_edit = $docovaAcl->isDocAuthor($document);
				$access_level = $can_edit === true ? 3 : 2;
			}
			if ($can_edit === false)
			{
				throw new AccessDeniedException();
			}


		}else{
			$access_level = $docovaAcl->isEditor() ? 6 : $access_level;
			if ($access_level === 1) {
				$access_level = $docovaAcl->canCreateDocument() ? 3 : 2;
			}
		}
		
		$properties = $form->getFormProperties();
		if (empty($docid)) 
		{
			//$application = $em->getReference('DocovaBundle:Libraries', $app);
			$document = new Documents();
			$document->setOwner($this->user);
			$document->setCreator($this->user);
			$document->setDateCreated(new \DateTime());
			$document->setAppForm($form);
			$document->setApplication($application);
		}
		$document->setAuthor($this->user);
		$document->setModifier($this->user);
		$document->setDateModified(new \DateTime());
		$document->setDocStatus(($properties->getEnableLifecycle()) ? $properties->getInitialStatus() : $properties->getFinalStatus());
		$document->setStatusNo(($properties->getEnableLifecycle()) ? 0 : 1);
		if ($properties->getEnableLifecycle() && $properties->getEnableVersions()) {
			$document->setDocVersion('0.0');
			$document->setRevision(0);
		}
		
		if (empty($docid))
		{
			$unid = $request->request->get("unid");
			if ( empty ($unid)){
				$miscfunctions = new MiscFunctions();				
			   	 $unid = $miscfunctions->generateGuid(); 			     
			}
			$id = $unid;
			$document->setId($unid);
			$em->persist($document);
		}
//		$application = null;

		$params = $request->request->keys();
		$req = $request->request;
		$request_lower = array_change_key_case($req->all(), CASE_LOWER);
		
		//attach workflow to the form if workflow is enabled
		$options = $this->attachWorkflow($properties, $form, $docid, $document, $application );

		$form_name = str_replace(array('/', '\\'), '-', $form->getFormName());
		$form_name = str_replace(' ', '', $form_name);
		//preprocess..all multivalue to array
		
		$request_lower = $this->preProcessPostValues($request_lower);
		$compvals = [];
		$txtjsoncomputed= $this->renderView("DocovaBundle:DesignElements:$app/{$form_name}_computed.html.twig", array(
			'docvalues' => $request_lower,
			'user' => $this->user,
			'object' => $document,
		));
		$retval = html_entity_decode ($txtjsoncomputed);
		$xxval = json_decode($retval, true);

		if (count($xxval) > 0){
			$compvals = array_merge(...$xxval);
			$compvals = $this->un_serialize($compvals);
		}

		foreach ($compvals as $compkey => $compval){
			if (isset ( $request_lower[$compkey]))
				$request_lower[$compkey] = $compval;
		}

		//check form custom css

		if ( !empty( $form))
		{
			$name = str_replace(array('/', '\\'), '-', $form->getFormName());
			$name = str_replace(' ', '', $name);
			

			$filepath = $this->app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR .$app.DIRECTORY_SEPARATOR."FORM".DIRECTORY_SEPARATOR.$name.'.css';
			if (file_exists($filepath)) 
			{
				$custcss = true;
				
			}
		}

		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
		$html = $this->renderView("DocovaBundle:DesignElements:$app/$form_name.html.twig", array(
			'unid' => !empty($docid) ? $docid : $id,
			'user' => $this->user,
			'applicationname' => $application->getLibraryTitle(),
			'settings' => $this->global_settings,
			'form' => $form,
			'mode' =>  empty($docid) ? 'edit' : 'edit',  //for refresh we always set mode to edit as we want to use the values supplied by the post
			'object' => $document,
			'docvalues' => $request_lower,
			'isprofile' => $isprofile,
			'useraccess' => $access_level,
			'isrefresh' => true,
			'custcss' => $custcss,
			'isrefreshnewdoc' => !empty($docid) ? false : true,
			'available_versions' => $options['available_versions'],
			'workflow_options' => $options['workflow_options'],
			'workflows' => $options['workflow_arr'],
			'workflow_buttons' => $options['action_buttons'],
			'wf_xml' => $options['xml'],
			'current_step' => $options['wfstep'],
			'docstep' => $options['docstep']
		));
		
		$info = $this->renderView('DocovaBundle:Applications:partialDocInfo.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'form' => $form,
			'useraccess' => $access_level,
			'applicationname' => $application->getLibraryTitle(),
			'isprofile' => $isprofile,
			'custcss' => $custcss,
			'isrefresh' => true,
			'object' => $document,
			'available_versions' => $options['available_versions'],
			'workflow_options' => $options['workflow_options']
		));
		
		$output = array(
			'body' => $html,
			'info' => $info
		);

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($output));
		return $response;
	}
	
	public function runAppAgentAction(Request $request, $agent)
	{
		$app = $request->query->get('AppID');
		$docid = $request->query->get('parentUNID');
		$docid = !empty($docid) ? $docid : null;
		$docova = new Docova($this->container);
		$app = $docova->DocovaApplication(['appID' => $app]);
		$appId = $app->appID;
		if (empty($appId)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}
		$docova_agent = $docova->DocovaAgent($app, $agent);
		$docova = null;
		$output = $docova_agent->run($docid);
		$output = (is_bool($output) && $output == "false" ? "0" : $output);
		
		$ct = "";
		if(strtolower(mb_substr($output, 0, 13)) == "content-type:"){
		    $eolp = mb_strpos($output, PHP_EOL);
		    if($eolp !== false){
		        $tmpstr = mb_substr($output, 13, $eolp-13);
		        $ct = $tmpstr;
		        $output = mb_substr($output, $eolp+mb_strlen(PHP_EOL));    
		    }		        
		}
		
		$cd = "";
		if(strtolower(mb_substr($output, 0, 20)) == "content-disposition:"){
		    $eolp = mb_strpos($output, PHP_EOL);
		    if($eolp !== false){
		        $tmpstr = mb_substr($output, 20, $eolp-20);
		        $cd = $tmpstr;
		        $output = mb_substr($output, $eolp+mb_strlen(PHP_EOL));
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
		if($cd !== ""){
		    $response->headers->set('Content-Disposition', $cd);
		}
		$response->setContent($output);
		return $response;
	}	

	public function loadJScriptAction($appid, $scriptname)
	{

		$basepath = $this->container->getParameter('kernel.project_dir');
		
		$scriptpath = $basepath.'/web/bundles/docova/js/custom/'.$appid.'/'.$scriptname.'.js';
		
		$response = new Response();
		if ( file_exists($scriptpath))
			$jsfile = file_get_contents($scriptpath);
		else
			$jsfile = "";


		$response->headers->set('Content-Type', 'text/javascript');
		$response->setContent($jsfile);
		return $response;
	}


	public function plugInServicesAction(Request $request)
	{
		$records = array();
		$query = $request->query;
		$app = $query->get('AppID');
		$action = $query->get('Action');
		$post_xml = null;
		if (empty($action) && $request->isMethod('POST'))
		{
			$post_xml = new \DOMDocument();
			$post = urldecode($request->getContent());
			$post_xml->loadXML($post);
			$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
		}

		$response = new Response();
		
		if (!empty($app) && !empty($action))
		{
			if ($action === 'CALENDAR') {
				$view_name = $query->get('ViewName');
				$category = $query->get('category');
				$start = new \DateTime($query->get('start'));
				$end = new \DateTime($query->get('end'));
				$start->setTime('00', '00', '00');
				$end->setTime('00', '00', '00');
				$records = $this->fetchCalendarRecords($view_name, $app, $start, $end, $category);
			}
			elseif ($action === 'GANTT') {
				$this->initialize();
				$em = $this->getDoctrine()->getManager();
				$view_name = $query->get('ViewName');
				
				$view = $em->getRepository('DocovaBundle:AppViews')->findByNameAlias($view_name, $app);
				if (!empty($view))
				{
					$docova = new Docova($this->container);
					$view_handler = new ViewManipulation($docova, $app, $this->global_settings);
					$docova = null;
					$gantt_services = new GanttServices($view_name, $app, $this->user, $this->global_settings);
					$gantt_services->setContainer($this->container);
					$category = $query->get('category');
					$records = $gantt_services->fetchGanttRecords($em, $view_handler, $view, $category);
				}
			}
			elseif ($action === 'GANTTSAVE' && !empty($post_xml)) {
				$this->initialize();
				$em = $this->getDoctrine()->getManager();
				$response->headers->set('Content-Type', 'text/xml');
				$view_name = $post_xml->getElementsByTagName('ViewName')->item(0)->nodeValue;
				$view = $em->getRepository('DocovaBundle:AppViews')->findByNameAlias($view_name, $app);
				if (empty($view))
				{
					$content = '<?xml version="1.0" encoding="UTF-8" ?><Results><Result ID="Status">FAILED</Result><Result ID="ErrMsg"><!CDATA[Unspecified view name]]></Result>';
					$response->setContent($content);
					return $response;
				}
				$docova = new Docova($this->container);
				$view_handler = new ViewManipulation($docova, $app, $this->global_settings);
				$gantt_services = new GanttServices($view_name, $app, $this->user, $this->global_settings);
				$gantt_services->setContainer($this->container);
				$category = $post_xml->getElementsByTagName('Category')->item(0)->nodeValue;
				$category = empty($category) ? null : $category;
				foreach ($post_xml->getElementsByTagName('tasks') as $node) {
					$res = $gantt_services->saveGanttRecord($em, $view_handler, $view, $node, $category);
				}
				if ($post_xml->getElementsByTagName('deletedTaskIds')->length)
				{
					$application = $docova->DocovaApplication(['appID' => $app]);
					foreach ($post_xml->getElementsByTagName('deletedTaskIds') as $delDoc) {
						$doc_obj = $docova->DocovaDocument($application, $delDoc->nodeValue);
						if ($doc_obj) {
							$doc_obj->deleteDocument();
							$gantt_services->deleteGanttRecord($em, $delDoc->nodeValue, $view_handler);
						}
					}
				}
				$docova = null;
				$content = '<?xml version="1.0" encoding="UTF-8" ?><Results>';
				if (!empty($res) && $res === true)
				{
					$document = $em->getReference('DocovaBundle:Documents', $gantt_services->getInsertDocId());
					$log_obj = new Miscellaneous($this->container);
					$log_obj->createDocumentLog($em, 'CREATE', $document);
					$content .= '<Result ID="Status">OK</Result>';
				}
				else {
					$content .= '<Result ID="Status">FAILED</Result>';
					$content .= '<Result ID="ErrMsg"><![CDATA['.(!empty($res) ? $res : '').']]></Result>';
				}
				$content .= '</Results>';
				$response->setContent($content);
				return $response;
			}
		}
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($records));
		return $response;
	}
	
	public function embeddedImageLoadAction(Request $request, $docid, $imagename=null)
	{
		$this->initialize();
		$file_path = null;
		if(isset($imagename) && !empty($imagename)){
		    $image = $imagename;
		}else{
		    $image = $request->query->get('image');
		}

		$ext = substr($image, strrpos($image, '.')+1);
		if(in_array(strtolower($ext), array('gif', 'png', 'jpeg', 'bmp', 'jpg'))){
			$mime = strtolower($ext);
		}else{
			$mime = 'jpeg';
		}
		$response = new StreamedResponse();
		$headers = array(
			'Content-Type' => "image/$mime",
			'Content-Disposition' => 'inline; filename="'.$image.'"',
		);
		
		if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$docid.DIRECTORY_SEPARATOR.$image))
		{
			$file_path = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$docid.DIRECTORY_SEPARATOR.$image;
		}
		else {
			$image = null;
		}
		
		if (empty($image)) 
		{
		    $file_path = $this->root_path.DIRECTORY_SEPARATOR.$this->container->get('assets.packages')->getUrl('bundles/docova/images/not-image.png');
			$headers['Content-Type'] = 'image/png';
			$headers['Content-Disposition'] = 'inline; filename="no-image.png"';
		}

		$response->setCallback(function() use($file_path) {
			$handle = fopen($file_path, 'r');
			while (!feof($handle)) {
				$buffer = fread($handle, 1024);
				echo $buffer;
				flush();
			}
			fclose($handle);
		});

		$response->headers->add($headers);
		$response->setStatusCode(200);
		return $response;
	}
	
	
	public function getMenuDataAction(Request $request)
	{
	    
	    $appid = $request->query->get('LibraryId');
	    $menuname = $request->query->get('MenuId');
	    $jsondata = "{}";
	    
	    if(!empty($appid) && !empty($menuname)){
	        $templatename = 'DocovaBundle:DesignElements:'.$appid.'/outline/'.$menuname.'_m.html.twig';
	        if ( $this->get('templating')->exists($templatename) ) {
    	        $jsondata = $this->renderView($templatename);	   
	        }
	    }

	    $response = new Response($jsondata);
	    $response->headers->set('Content-Type', 'application/json');
	    return $response;
	    
	}
	
	
	/**
	 * Generates a valid datetime object from a datetime string or returns null
	 * 
	 * @param string $date_string
	 * @return NULL|\DateTime
	 */
	private function getValidDateTimeValue($date_string)
	{
	    //-- check if a date time object was passed if so leave it unchanged
		if ($date_string instanceof \DateTime) {
			return $date_string;
		}
		
		if (empty($date_string)){
		    return null;
		}
		
		$hasdate = false;
		$hastime = false;
		$hasseconds = false;
		
		//-- generate php date format
		$format = $this->global_settings->getDefaultDateFormat();
		$format = str_replace(array('YYYY', 'MM', 'DD'), array('Y', 'm', 'd'), $format);
	
		//-- parse the string into a date component array
		$parsed = date_parse($date_string);
		
		//-- check for time portion
		$is_period = (false === stripos(strtolower($date_string), ' am') && false === stripos(strtolower($date_string), ' pm')) ? false : true;
		$time = (false !== $parsed['hour'] && false !== $parsed['minute']) ? ($is_period ? 'h:i' : 'H:i') : '';
		$time .= (!empty($time) && substr_count($date_string, ":") > 1 ? ':s' : '');
		$time .= (!empty($time) && $is_period ? ' A' : '');

		
		if(!empty($format) && false !== $parsed['year'] && false !== $parsed['month'] && false !== $parsed['day']){
            $hasdate = true;
		}		
		if(!empty($time)){
    		$hastime = true;
		}
		
		$combinedformat = "!";
		if($hasdate){
		    $combinedformat .= $format;
		}
		if($hasdate && $hastime){
		    $combinedformat .= " ";
		}
		if($hastime){
		    $combinedformat .= $time;
		}
		
		//-- generate a date time object
		$value = \DateTime::createFromFormat($combinedformat, $date_string);
		
		if(!empty($value) && !$hasdate){
		    $value->setDate(1000,01,01);		    
		}elseif(!empty($value) && !$hastime){
		    $value->setTime(0, 0, 0);
		}

		$format = $parsed = $time = $is_period = $combinedformat = null;

		return empty($value) ? null : $value;
	}
	
	
	/**
	 * Find user object base on provided username or userDnNamAbbreviated
	 * 
	 * @param string $user
	 * @param boolean $findinactive
	 * @param boolean $findldap
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUser($user, $findinactive = false, $findldap = false)
	{
		$em = $this->getDoctrine()->getManager();
		$helper = new UtilityHelperTeitr($this->global_settings, $this->container);


		//check if flat name..if so check in the username field else check abbreviated column
		if (false === stripos($user, '/')){
			$user_object = $helper->findUserAndCreate($user, false, $em, true, $findldap, $findinactive);
			if (empty($user_object))
			{
				$user_object = $helper->findUserAndCreate($user, false, $em, false, $findldap, $findinactive);
			}
		 	//$usernameabbr .= "/Docova";
		 }else{
		 	//now look for it in the  Abbreviated field
		 	$docova = new Docova($this->container);
		 	$usernameabbr = $docova->DocovaName($user);
		 	$docova = null;
			$usernameabbr = $usernameabbr->__get("Abbreviated");
		 	$user_object = $helper->findUserAndCreate($usernameabbr, false, $em, false, $findldap, $findinactive);
		 }

	
		if ($user_object instanceof \Docova\DocovaBundle\Entity\UserAccounts) {
			return $user_object;
		}
		if(!empty($user_object) && $findldap === true){
			return $user_object;
		}
		return false;
	}
	
	/**
	 * Find group object base on role/group name
	 * 
	 * @param string $groupname
	 * @return \Docova\DocovaBundle\Entity\UserRoles|boolean
	 */
	private function findGroup($groupname)
	{
		$em = $this->getDoctrine()->getManager();
		$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $groupname));
		if (!empty($group))
		{
			return $group;
		}
		return false;
	}
	
	/**
	 * Create an inactive user profile for migrated name values as string
	 * 
	 * @param string $userName
	 * @return \Docova\DocovaBundle\Entity\UserAccounts
	 */
	private function createInactiveUser($userName)
	{

		$userentity = $this->findUser($userName, true, true);
		if ($userentity instanceof \Docova\DocovaBundle\Entity\UserAccounts) {
			//-- if existing user found return it
			return $userentity;
		}
		
		$em = $this->getDoctrine()->getManager();
		$objName = null;
		$unameAbbr = '';
		$unameCanon = '';
		$unameCommon = '';
		$unameFirst = '';
		$unameLast = '';
		$unameDisplay = '';
		$ldapaccount = false;
		
		$docova = new Docova($this->container);
		$inactive_user = new UserAccounts();
		
		//-- if ldap data was returned
		if(!empty($userentity) && !empty($userentity['username_dn_abbreviated'])){
			$objName = $docova->DocovaName((!empty($userentity['username_dn_abbreviated']) ? $userentity['username_dn_abbreviated'] : $userName));
			$unameAbbr = $objName->__get("Abbreviated");
			$unameCanon = (!empty($userentity['username_dn']) ? $userentity['username_dn'] : $objName->__get("Canonical"));
			$unameCommon = $objName->__get("Common");
			$unameFirst =  $objName->__get("Given");
			$unameLast =  $objName->__get("Surname");
			$unameDisplay = (!empty($userentity['display_name']) ? $userentity['display_name'] : $unameCommon);
			
			$inactive_user->setUsername((!empty($userentity['uid']) ? $userentity['uid'] : $unameCommon));
			$inactive_user->setUserNameDnAbbreviated($unameAbbr);
			$inactive_user->setUserNameDn($unameCanon);
			$inactive_user->setUserMail((!empty($userentity['mail']) ? $userentity['mail'] : ''));
			
			$ldapaccount = true;
		//-- otherwise use the user name string
		}else{			
			$objName = $docova->DocovaName($userName);			
			$unameAbbr = $objName->__get("Abbreviated");
			$unameCanon = $objName->__get("Canonical");
			$unameCommon = $objName->__get("Common");
			$unameFirst =  $objName->__get("Given");
			$unameLast =  $objName->__get("Surname");
			$unameDisplay = $unameCommon;			

			if (false === stripos($unameAbbr, '/') ){
			    $docova_certifier_name = $this->global_settings->getDocovaBaseDn() ? $this->global_settings->getDocovaBaseDn() : "/DOCOVA";
			    if(substr($docova_certifier_name, 0, 1) == "/" || substr($docova_certifier_name, 0, 1) == "\\"){
			        $docova_certifier_name = substr($docova_certifier_name, 1);
			    }
			    $unameCanon = 'CN='.trim($unameAbbr).",O=".$docova_certifier_name;
			    $unameAbbr = $unameAbbr.'/'.$docova_certifier_name;
			}
			
			$inactive_user->setUsername($unameCommon);
			$inactive_user->setUserNameDnAbbreviated($unameAbbr);
			$inactive_user->setUserNameDn($unameCanon);
			$inactive_user->setUserMail('');				
		}
		$docova = $objName = null;
		
		$inactive_user->setTrash(true);
		$em->persist($inactive_user);
		
		$inactive_profile = new UserProfile();
		$inactive_profile->setAccountType(!$ldapaccount);
		$inactive_profile->setFirstName($unameFirst);
		$inactive_profile->setLastName($unameLast);
		$inactive_profile->setDisplayName($unameDisplay);
		$inactive_profile->setUserMailSystem('O');
		$inactive_profile->setMailServerURL('inactive.user');
		$inactive_profile->setUser($inactive_user);
		$em->persist($inactive_profile);
		$em->flush();
		
		return $inactive_user;
	}
	
	/**
	 * Upload document attachments
	 * 
	 * @param \Symfony\Component\HttpFoundation\FileBag $files
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\DesignElements $field
	 * @param \DateTime[]
	 */
	private function uploadDocAttachment($files, $document, $field, $dates, $filelist, $renamearray = null)
	{
		$em = $this->getDoctrine()->getManager();
		$attachments = $document->getAttachments();
		$attached_files = $files->get('Uploader_DLI_Tools');
		$index = 0;
		foreach ($attached_files as $att)
		{
			$file_name	= html_entity_decode($att->getClientOriginalName(), ENT_COMPAT, 'UTF-8');

			if ( !empty($renamearray) ){
				if ( array_key_exists($file_name, $renamearray)){
					$file_name = $renamearray[$file_name];
				}
			}

			if ( in_array($file_name, $filelist) ){
				if (!is_dir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId())) {
					mkdir($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR);
				}

				$res = $att->move($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$document->getId(), md5($file_name));
				if (!empty($res)) {
					$exists = false;
					if (!empty($attachments)) {
						foreach ($attachments as $key => $value) {
							if ($value->getFileName() == $file_name) {
								$exists = $value;
								unset($attachments[$key]);
								break;
							}
						}
					}
					
					if (!empty($dates[$index])) {
						$file_date = $dates[$index];
					}
					else {
						$file_date = new \DateTime();
					}
					if ($exists === false) {
						$temp = new AttachmentsDetails();
						$temp->setDocument($document);
						$temp->setField($field);
						$temp->setFileName($file_name);
					}
					else {
						$temp = $exists;
					}
					$temp->setFileDate($file_date);
					$mimetype = $res->getMimeType();
					$temp->setFileMimeType($mimetype ? $mimetype : 'application/octet-stream');
					$temp->setFileSize($att->getClientSize());
					$temp->setAuthor($this->user);
					if ($exists === false) {
						$em->persist($temp);
					}
					$em->flush();
				}
			}
			$index++;
		}
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
	 * Parse string for plus operand to convert it to concat or add function
	 * 
	 * @param string $string
	 * @return string
	 */
	private function lexerParser($string)
	{
		if (false === strpos($string, '(')) {
			$string = str_replace('+', ',', $string);
			return '$docova->concatOrAdd(array(' . $string . '))';
		}
		else {
			$plus = null;
			$parenthesis = $this->getParenthesisPos($string);
			preg_match_all('/\+/', $string, $plus, PREG_OFFSET_CAPTURE);
			if (!empty($plus[0])) 
			{
				$plus = $plus[0];
				$found = array();
				foreach ($plus as $pos) {
					foreach ($parenthesis as $par) {
						if ($pos[1] > $par[0] && $pos[1] < $par[1]) {
							$found[] = $par;
							break;
						}
					}
				}
				if (!empty($found)) 
				{
					$found = array_map("unserialize", array_unique(array_map("serialize", $found)));
					foreach ($found as $pos) {
						$string = substr($string, 0, $pos[0]) . '$docova->concatOrAdd(' . substr($string, $pos[0]+1, $pos[1] - $pos[0]) . ')' . substr($string, $pos[1]+1);
					}
				}
				else {
					$string = '$docova->concatOrAdd(array(' . str_replace('+', ',', $string) . '))';
				}
			}
			return $string;
		}
	}
	
	/**
	 * Generates an array of positions of open and close parenthesis
	 * 
	 * @param string $string
	 * @param integer $openPos
	 * @param array $output
	 * @return number[]
	 */
	private function getParenthesisPos($string, $openPos = null, $output = array())
	{
		$x = empty($openPos) ? 0 : ($openPos + 1);
		$len = strlen($string);
		while ($x < $len)
		{
			if ($string[$x] == ')') {
				$output[] = array($openPos, $x);
				return $output;
			}
			elseif ($string[$x] == '(') {
				$output = $this->getParenthesisPos($string, $x, $output);
				$tmp = end($output);
				$x = $tmp[1];
			}
			$x++;
		}
		return $output;
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
	 * Fetch calendar view records in the specified date range
	 * 
	 * @param string $view
	 * @param string $app
	 * @param \DateTime $start
	 * @param \DateTime $end
	 * @return array
	 */
	private function fetchCalendarRecords($view, $app, $start, $end, $category = null)
	{
		$output = array();
		$em = $this->getDoctrine()->getManager();
		$view = $em->getRepository('DocovaBundle:AppViews')->findByNameAlias($view, $app);
		if (!empty($view))
		{
			$xml = new \DOMDocument();
			$xml->loadXML($view->getViewPerspective());
			$x = 0;
			$start_column = $end_column = $icon = $title_column = $cat_column = null;
			foreach ($xml->getElementsByTagName('xmlNodeName') as $item)
			{
				$title = $xml->getElementsByTagName('title')->item($x)->nodeValue;
				$type = $xml->getElementsByTagName('dataType')->item($x)->nodeValue;
				if ($title == 'Category')
					$cat_column = $item->nodeValue;
				if ($title == 'Start Date' && ($type == 'datetime' || $type == 'date'))
					$start_column = $item->nodeValue;
				if ($title == 'End Date' && ($type == 'datetime' || $type == 'date'))
					$end_column = $item->nodeValue;
				if ($title === 'Icon')
					$icon = $item->nodeValue;
				if ($title == 'Description')
					$title_column = $item->nodeValue;
				$x++;
			}
			$docova = new Docova($this->container);
			$view_handler = new ViewManipulation($docova, $app);
			$docova = null;
			$view_id = str_replace('-', '', $view->getId());
			$params = array(null, 'datetime', 'datetime');
			if ($view_handler->viewExists($view_id) && !empty($start_column) && !empty($end_column))
			{
				$query = "SELECT * FROM view_$view_id WHERE App_Id = ? AND $start_column >= ? AND $end_column <= ?";
				if (!empty($category) && !empty($cat_column))
				{
					$query .= " AND $cat_column = ? ";
					$params[] = $category;
				}
				$result = $view_handler->selectQuery($query, array($app, $start, $end), $params);
				if (!empty($result))
				{
					foreach ($result as $record)
					{
						$output[] = array(
							'id' => $record['Document_Id'],
							'start' => $record[$start_column],
							'end' => $record[$end_column],
							'icon' => empty($record[$icon]) ? '' : $record[$icon],
							'title' => empty($record[$title_column]) ? '' : $record[$title_column]
						);
					}
				}
			}
		}
		return $output;
	}
	
	/**
	 * Calculate document available versions if versioning is enabled
	 * 
	 * @param string $docid
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\AppFormProperties $properties
	 */
	private function calculateAvailableVersions($docid, $document, $properties)
	{
		$versions = [];
		
		if (!empty($docid))
		{
			if ($properties->getEnableLifecycle() && $properties->getEnableVersions())
			{
			    $parentdoc = $document->getParentDocument();
			    $dockey = (empty($parentdoc) ? $docid : $parentdoc->getId());
			    unset($parentdoc);
			    
				$all_versions = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Documents')->getAllDocVersionsFromParent($dockey);
				if (!empty($all_versions))
				{
					if (count($all_versions) == 1 || $all_versions[count($all_versions) - 1]->getId() == $docid)
					{
						$pfv = $document->getDocVersion().'.'.$document->getRevision();
					}
					else {
						foreach ($all_versions as $index => $doc)
						{
							if ($doc->getId() == $docid) {
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
							
						!empty($v1) ? array_push($versions, $v1) : '';
						!empty($v2) ? array_push($versions, $v2) : '';
						!empty($v3) ? array_push($versions, $v3) : '';
						$versions = array_unique($versions);
					}
				}
				unset($all_versions);
			}
		}

		return $versions;
	}
}
