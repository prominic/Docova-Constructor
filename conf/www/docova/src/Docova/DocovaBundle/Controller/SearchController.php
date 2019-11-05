<?php

namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
//use Docova\DocovaBundle\Entity\UserAccounts;
//use Docova\DocovaBundle\Extensions\ExternalViews;
use Symfony\Component\HttpFoundation\JsonResponse;
use Docova\DocovaBundle\ObjectModel\Docova;
use Docova\DocovaBundle\Extensions\ExternalViews;
use Docova\DocovaBundle\Security\User\CustomACL;
class SearchController extends Controller 
{
	protected $user;
	protected $global_settings;
	protected $max_results = 100;
	
	protected static $filtered_doctypes = array();
	protected static $folders = array();
	protected $IS_LEGACY_VERSION=false;
	public function getEntityManager()
	{
		return $this->getDoctrine()->getManager();
	}
	public function getDoctrineConnection()
	{
		return $this->getDoctrine()->getConnection();
	}
	public function folderSearchAction(Request $request)
	{
		
		
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');

		$query = null;
		$folder_id = null;
		$view_scope = null;
		$versions = null;
		$max_results = "500";
		$nodes = 'bmk,F8,F10,F7,F9,F1,F4';
		$post_content = $request->getContent();
		if (!empty($post_content)) 
		{
			$post_xml = new \DOMDocument('1.0', 'UTF-8');
			$post_xml->loadXML($post_content);
			if (!empty($post_xml->getElementsByTagName('query')->item(0)->nodeValue)) 
			{
				$query = $post_xml->getElementsByTagName('query')->item(0)->nodeValue;
				$query = $this->parseQuery($query);
				
				$folder_id = (!empty($post_xml->getElementsByTagName('FolderID') && !empty($post_xml->getElementsByTagName('FolderID')->item(0)) && !empty($post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue)) ? $post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue : null);
				if(empty($folder_id)){
				    $folder_id = (!empty($post_xml->getElementsByTagName('folderid') && !empty($post_xml->getElementsByTagName('folderid')->item(0)) && !empty($post_xml->getElementsByTagName('folderid')->item(0)->nodeValue)) ? $post_xml->getElementsByTagName('FolderID')->item(0)->nodeValue : null);				    
				}
				$view_scope = (!empty($post_xml->getElementsByTagName('viewscope')) && !empty($post_xml->getElementsByTagName('viewscope')->item(0)) ? $post_xml->getElementsByTagName('viewscope')->item(0)->nodeValue : '');
				$versions = (!empty($post_xml->getElementsByTagName('versions')) && !empty($post_xml->getElementsByTagName('versions')->item(0)) ? $post_xml->getElementsByTagName('versions')->item(0)->nodeValue : '');
				if(!empty($post_xml->getElementsByTagName('maxresults')) && !empty($post_xml->getElementsByTagName('maxresults')->item(0))){
				    $max_results = $post_xml->getElementsByTagName('maxresults')->item(0)->nodeValue;
				}
				
				if(!empty($post_xml->getElementsByTagName('nodes')) && !empty($post_xml->getElementsByTagName('nodes')->item(0)->length) && !empty($post_xml->getElementsByTagName('nodes')->item(0)->nodeValue)){
				    $nodes = trim($post_xml->getElementsByTagName('nodes')->item(0)->nodeValue);
				}else if(!empty(trim($request->query->get('nodes')))){
				    $nodes = $request->query->get('nodes');
				}
				////Special custom External View handling			
// 				$searchQuery = $post_xml->getElementsByTagName('query')->item(0)->nodeValue;
// 				$extProcessed = ExternalViews::getData($this, $this->container->getParameter("document_root"),$folder_id, 0, 0,$searchQuery);
			
// 				if ($extProcessed!=null)
// 					return $extProcessed;
			}			
		}else{
		    $query = $request->query->get('query');
		    if(empty($query)){
		        $query = $request->query->get('search');
		    }		    
		    $query = $this->parseQuery($query);
					
		    $folder_id = $request->query->get('FolderId');
		    if(empty($folder_id)){
    		    $folder_id = $request->query->get('folderid');
		    }
		    if(!empty($request->query->get('maxresults'))){
		        $max_results = $request->query->get('maxresults');
		    }
		    if(!empty(trim($request->query->get('nodes')))){
		        $nodes = $request->query->get('nodes');
		    }
		}
		if(isset($folder_id) && isset($query)){
			$em = $this->getDoctrine()->getManager();
			$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
			$this->global_settings = $this->global_settings[0];
			$this->max_results = $max_results;
    			self::$folders = array($folder_id);
			$folder_obj = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' =>  $folder_id, 'Del' => false, 'Inactive' => 0));
			$sub_filter = [
				'folder' => $folder_obj,
    				'include_subfolders' => $view_scope == 'ST' ? true : false,
    				'pending_release' => $versions === 'NEW' ? true : false,
    				'current_version' => $versions === 'REL' ? true : false
			];
    			$queryresults = $this->fetchDocumentsFromQueries($query, $sub_filter);
    			$documents = $this->mergeResultsByOpernads($queryresults);
			$showDisplayName = $this->global_settings->getUserDisplayDefault() ? true : false;
			$documents = !empty($documents) ? $em->getRepository('DocovaBundle:Documents')->getDocumentsObject($documents, $sub_filter, $showDisplayName) : array();
    			$queryresults = $showDisplayName = null;
    			if($nodes == "summary"){
    		    		$xml_doc = $this->buildDocsSummaryXML($documents, $folder_obj);
    		    		$response->setContent($xml_doc->saveXML());
    			}else{
				$nodes = explode(',', $nodes);
				$response->setContent($this->buildDocsXML($documents, $nodes));
			}
		}else{
		
			$xml_doc = new \DOMDocument("1.0", "UTF-8");
			$root = $xml_doc->appendChild($xml_doc->createElement('documents'));
			$newnode = $xml_doc->createElement('srchCount', 0);
			$root->appendChild($newnode);
			$newnode = $xml_doc->createElement('status', 'OK');
			$root->appendChild($newnode);
			$response->setContent($xml_doc->saveXML());
		}
		return $response;
	}
	
	
	public function viewSearchAction(Request $request)
	{
		$response = null;
		
		$post_content = rawurldecode($request->getContent());
		
		if (!empty($post_content))
		{
			$post_xml = new \DOMDocument('1.0', 'UTF-8');
			$post_xml->loadXML($post_content);
			if (!empty($post_xml->getElementsByTagName('Query')->item(0)->nodeValue))
			{
				$em = $this->getDoctrine()->getManager();
				
				$this->max_results = '500';
				$query = $post_xml->getElementsByTagName('Query')->item(0)->nodeValue;
				try{
					$query = $this->parseQuery($query);
				}
				catch (\Exception $e)
				{	
				}
	
				$app_id = $post_xml->getElementsByTagName('AppID')->item(0)->nodeValue;
				$view_id = $post_xml->getElementsByTagName('ViewID')->item(0)->nodeValue;
				$isMobile = !empty($post_xml->getElementsByTagName('IsMobile')->item(0)->nodeValue) ? true : false;
				if ($isMobile === true) {
					$viewentity = $em->getRepository('DocovaBundle:AppViews')->findByNameAlias($view_id, $app_id);
					if (empty($viewentity)) {
						throw $this->createNotFoundException('Could not open view.');
					}
					$view_id = $viewentity->getId();
				}
				
				$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
				$this->global_settings = $this->global_settings[0];
				$sub_filter = ['view' => $view_id, 'libraries' => $app_id];
/* 				
				//External views
				/////////////////////////////
				$viewentity =  $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('id' => $view_id, 'application' => $app_id));
				if (empty($viewentity)) {
					throw $this->createNotFoundException('Could not open view.');
				}
				
				//First check to see if there is an associated external data source
				//If there is then load and return the external data instead
				if (! empty($viewentity->getDataView())){
					$dataView = $em->getRepository('DocovaBundle:DataViews')->findOneBy(array('Name' => $viewentity->getDataView()));
					$extData = null;
					if (!empty($dataView) ) {
						$extData= ExternalViews::getData($this, $this->container->getParameter("document_root"), $dataView, 0, 0, $post_xml->getElementsByTagName('Query')->item(0)->nodeValue,true);
						if (!empty($extData)){
							return $extData;
						}
				
					}
				}
				/////////////////////////////
 */					
				$query = $this->fetchDocumentsFromQueries($query, $sub_filter);
				$documents = $this->mergeResultsByOpernads($query);

				if ($isMobile === true) {
					$response = new Response();
					$response->headers->set('content-type', 'text/xml');
					$response->setContent($this->buildViewXML($documents, $viewentity, $app_id));
				}
				else {
					$response = $this->buildViewJSON($documents, $view_id, $app_id);
				}
				return $response;
			}
		}
		
		$response = JsonResponse::fromJsonString("{}");
		$response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);		
		return $response;
	}	
	
	public function fullSearchAction(Request $request)
	{
/*
		$qstring = new QueryString();
		$qstring->setQuery('*version*');
		$bool_q = new \Elastica\Query\Bool();
		$filter = new Match();
		$filter->setFieldQuery('folder.Library.id', 'fdffb8b1-71af-11e4-9116-b888e3e826fd');
		$bool_q->addMust($qstring);
		$bool_q->addMust($filter);
		$filter = new Term();
		$filter->setTerm('Bookmarks.Target_Folder.id', '2');
		$bool_q->addShould($filter);
		$filter = new Terms('DocType.id', array(1,2));
		$bool_q->addMust($filter);
		//$filtered_query = new Filtered($qstring, $bool_q);
		$finder = $this->container->get('fos_elastica.finder.website.documents');
		$res = $finder->find($bool_q);
		var_dump($res); exit();
*/		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$post_content = $request->getContent();
		
		if (!empty($post_content))
		{
			$em = $this->getDoctrine()->getManager();
			$post_xml = new \DOMDocument('1.0', 'UTF-8');
			$post_xml->loadXML($post_content);
			$libraries = $post_xml->getElementsByTagName('libraries')->item(0)->nodeValue;
			$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
			if ($action === 'DOMAINSEARCH') 
			{
				$security = $this->container->get('security.authorization_checker');
				$libraries = $em->getRepository('DocovaBundle:Libraries')->getLibrarieIds();
				foreach ($libraries as $index => $lib) {
					$library = $em->getReference('DocovaBundle:Libraries', $lib);
					if (!$security->isGranted('VIEW', $library)) 
					{
						unset($libraries[$index]);
					}
				}
				$libraries = array_values($libraries);
			}
			$query_string = $post_xml->getElementsByTagName('query')->item(0)->nodeValue;
			$this->max_results = !empty($post_xml->getElementsByTagName('maxresults')->item(0)->nodeValue) ? $post_xml->getElementsByTagName('maxresults')->item(0)->nodeValue : 100;
			if (!empty($libraries) && $query_string) 
			{
				$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
				$this->global_settings = $this->global_settings[0];
				$libraries = is_array($libraries) ? implode(',', $libraries) : $libraries;
				$query = $this->parseQuery($query_string);
				$searchArchive = $post_xml->getElementsByTagName('searcharchive')->item(0)->nodeValue == 'true' ? true : false;
				$sub_filter = [
					'libraries' => $libraries,
					'search_archive' => $searchArchive
				];
				$query = $this->fetchDocumentsFromQueries($query, $sub_filter);
				$documents = $this->mergeResultsByOpernads($query);
				$showDisplayName = $this->global_settings->getUserDisplayDefault() ? true : false;
				$lib_docs = !empty($documents) ? $em->getRepository('DocovaBundle:Documents')->getDocumentsObject($documents, array(), $showDisplayName, $searchArchive) : array();
				if (!empty($libraries)) {
					$app_docs = !empty($documents) ? $em->getRepository('DocovaBundle:Documents')->getDocumentsObject($documents, array(), $showDisplayName, $searchArchive, true) : array();
					$lib_docs = array_merge($lib_docs, $app_docs);
				}
				$documents = $lib_docs;
				if ($action !== 'DOMAINSEARCH')
				{
					$libraries = explode(',', $libraries);
					for ($x = 0; $x < count($documents); $x++) {
						if (false === in_array($documents[$x]['Library_Id'], $libraries))
						{
							unset($documents[$x]);
						}
					}
					$documents = array_values($documents);
				}
				$query = $showDisplayName = null;
				
				$nodes = trim($request->query->get('nodes')) ? $request->query->get('nodes') : 'libname,F34,F8,F9,F1,F4';
				$nodes = explode(',', $nodes);
				$response->setContent($this->buildDocsXML($documents, $nodes, ($action === 'DOMAINSEARCH' ? true : false)));
				return $response;
			}
		}

		$xml_doc = new \DOMDocument("1.0", "UTF-8");
		$root = $xml_doc->appendChild($xml_doc->createElement('documents'));
		$newnode = $xml_doc->createElement('srchCount', 0);
		$root->appendChild($newnode);
		$newnode = $xml_doc->createElement('status', 'OK');
		$root->appendChild($newnode);
		$response->setContent($xml_doc->saveXML());

		return $response;
	}
	
	public function showSaveSearchDlgAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgSaveSearch.html.twig', array('user' => $this->user));
	}
	
	public function lookUpSavedSearchesAction(Request $request)
	{
		$response = new Response();
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $xml->appendChild($xml->createElement('viewentries'));
		$library = trim($request->query->get('RestrictToCategory'));
		if (!empty($library)) 
		{
			$this->initialize();
			$em = $this->getDoctrine()->getManager();
			if ($library == 'Global')
			{
				$savedSearches = $em->getRepository('DocovaBundle:SavedSearches')->getUserSavedSearchesInLibraries($this->user);				
			}
			else {
				$savedSearches = $em->getRepository('DocovaBundle:SavedSearches')->getUserSavedSearchesInLibraries($this->user, array($library));
			}
			if (!empty($savedSearches)) 
			{
				$x = 1;
				foreach ($savedSearches as $search)
				{
					$entry = $xml->createElement('viewentry');
					$attr = $xml->createAttribute('position');
					$attr->value = $x;
					$entry->appendChild($attr);
					$attr = $xml->createAttribute('unid');
					$attr->value = $search->getId();
					$entry->appendChild($attr);
					$newnode = $xml->createElement('entrydata');
					$attr = $xml->createAttribute('columnnumber');
					$attr->value = 0;
					$newnode->appendChild($attr);
					$attr = $xml->createAttribute('name');
					$attr->value = 'SearchName';
					$newnode->appendChild($attr);
					$textnode = $xml->createElement('text', $search->getSearchName());
					$newnode->appendChild($textnode);
					$entry->appendChild($newnode);
					$newnode = $xml->createElement('entrydata');
					$attr = $xml->createAttribute('columnnumber');
					$attr->value = 1;
					$newnode->appendChild($attr);
					$attr = $xml->createAttribute('name');
					$attr->value = '$6';
					$newnode->appendChild($attr);
					$textnode = $xml->createElement('text', '<option value="'.$search->getId().'">'.$search->getSearchName().'</option>');
					$newnode->appendChild($textnode);
					$entry->appendChild($newnode);
					$newnode = $xml->createElement('entrydata');
					$attr = $xml->createAttribute('columnnumber');
					$attr->value = 2;
					$newnode->appendChild($attr);
					$attr = $xml->createAttribute('name');
					$attr->value = '$8';
					$newnode->appendChild($attr);
					$textnode = $xml->createElement('text', $search->getSearchName().'|'.$search->getId());
					$newnode->appendChild($textnode);
					$entry->appendChild($newnode);
					$root->appendChild($entry);
					$x++;
				}
			}
		}
		
		return $response->setContent($xml->saveXML());
	}

	/**
	 * Generate proper ESE search query in json format
	 * 
	 * @param string $query
	 * @param array $libraries
	 * @param array $folders
	 * @param array $documentTypes
	 * @param number $size
	 * @return string
	 */
	public function createFTQuery($query,$libraries,$folders,$views,$documentTypes,$size=100){
	//	$fieldsName = "fields";
		$fieldsName = "_source";
		$ftQuery = '{"size":'.$size.',"fields":[],'.PHP_EOL;
	
		if (empty($query) || $query=="")
			throw new \Exception("A query must be provided!");

		$ftQuery .= '	"query":{'.PHP_EOL;
		$ftQuery .= '		"bool":{'.PHP_EOL;
		$ftQuery .= '			"must":['.PHP_EOL;

		//add the main query
		$criteria = '				{"query_string":{"query":"'.\str_replace("\"", "\\\"", $query).'"} }';
	
		//now add each of the term filters applicable to the search criteria
		$this->addTermQuery($criteria, "Library.id", $libraries);
		$this->addTermQuery($criteria, "Folder.id", $folders);
		$this->addTermQuery($criteria, "Views.id", $views);
		$this->addTermQuery($criteria, "Doc_Type.id", $documentTypes);
	
		//Add the criteria to the query
		$ftQuery.=$criteria;
	
		$ftQuery .=PHP_EOL.'			]'.PHP_EOL; //end must array
	
		//Add an optional filter to match on the bookmark target folder id when a folder filter is applied
		$folderFilter=(empty($folders) || !\is_array($folders) ? false : true);
		if ($folderFilter==true){
			$ftQuery .= ','.PHP_EOL;
			$ftQuery .= '			"should" : ['.PHP_EOL;
			$this->addTermQuery($ftQuery, "Bookmark.Target_Folder", $folders, false);
			$ftQuery .=PHP_EOL.'			], '; //end should array
			$ftQuery .=PHP_EOL.'			"minimum_should_match" : 0'.PHP_EOL;
		}
	
		$ftQuery .='		}'.PHP_EOL;    //end bool operator
		$ftQuery .='	}'.PHP_EOL; // end the query element
		$ftQuery .='}'.PHP_EOL; // end the query request object

		return $ftQuery;
	
	}
	
	private function addTermQuery(&$terms,$field,$values, $delim = true)
	{
		if (!empty($values) && is_array($values) && !empty($field) && $field!="")
		{
			$terms .= $delim === true ? ','.PHP_EOL : PHP_EOL;
			$terms .= '				{"bool":{'.PHP_EOL;
			$terms .= '					"should":['.PHP_EOL;
			foreach ($values as $v)
				$terms .= '{"match" : {"'.$field.'":"'.$v.'"}},';
			$terms = substr_replace($terms, ']'.PHP_EOL, -1);
			$terms .= '					}'.PHP_EOL;
			$terms .= '				}'.PHP_EOL;
		}
		else {
			$terms .= PHP_EOL;
		}
		return "";
	}
	
	/**
	 * Parse a query string and build an array of queries splitted by the operand
	 * 
	 * @param string $query
	 * @return array
	 */
	private function parseQuery($query)
	{
		$query = str_replace(array('*"', '"*', '?"', '"?'), '"', $query);
		if (false !== strpos($query, '} OR {') || false !== strpos($query, '} AND {')) {
			$query = preg_split('/(\} OR \{|\} AND \{)+/is', trim($query), null, PREG_SPLIT_DELIM_CAPTURE);
			foreach ($query as $index => $item)
			{
				$item = trim(trim($item, '{'));
				$item = trim(trim($item, '}'));
				if (false === strpos(trim($item), ' ') && trim($item) != 'AND' && trim($item) != 'OR') {
					$item = '*' . trim($item) . '*';
				}
				$query[$index] = trim($item);
			}
		}
		else {
			$advanced = strpos($query, '{') === 0 ? true : false;
			$query = trim(trim($query, '{'));
			$query = trim(trim($query, '}'));
			if ($advanced === true && false === strpos(trim($query), ' ')) {
				$query = "*$query*";
			}
			$query = array(trim($query));
		}
		
		foreach ($query as $criteria)
		{
			$matches = null;
			$criteria = trim($criteria);
			if (preg_match_all('/\[(.*?)\]/', $criteria, $matches))
			{
				if ($matches[1][0] == 'DocumentTypeKey')
				{
					$value = trim($criteria, $matches[0][0]);
					$value = trim(strstr($value, '"'));
					$value = trim($value, '"');
					self::$filtered_doctypes[] = $value;
				}
			}
		}
		
		foreach ($query as $index => $criteria)
		{
			if (false === strpos($criteria, '"')) {
				$criteria = str_replace(' ', '" "', $criteria);
				$criteria = (strlen($criteria) - 1) == strpos($criteria, '*') ? '"'.substr_replace($criteria, '', -1).'"*' : '"'.$criteria.'"'; 
				$query[$index] = str_replace('-', '\-', $criteria);
				if (false !== strpos($query[$index], '?') || false !== strpos($query[$index], '*')) {
					$criteria = explode(' ', $query[$index]);
					foreach ($criteria as $key => $word) {
						if (false !== strpos($word, '?')) {
							$word = str_replace('"', '', $word);
							$criteria[$key] = $word;
						}
						if (false !== strpos($word, '*')) {
							$word = str_replace('"', '', $word);
							$criteria[$key] = $word;
						}
					}
					unset($key, $word);
					$criteria = implode(' ', $criteria);
					$query[$index] = $criteria;
				}
				
				$query[$index] = str_ireplace(array('"AND"', '"OR"'), array('AND', 'OR'), $query[$index]);
			}
			else {
				if (preg_match_all('/"([^"]+)"/', $criteria, $matches)) {
					$temp = str_replace($matches[0], '%tmp%', $criteria);
					$temp = str_replace('-', '\-', $temp);
					foreach ($matches[0] as $original) {
						$temp = preg_replace('/%tmp%/', $original, $temp, 1);
					}
					$query[$index] = $temp;
					unset($temp, $matches, $original);
				}
				else {
					$criteria = str_replace(' ', '" "', $criteria);
					$criteria = (strlen($criteria) - 1) == strpos($criteria, '*') ? '"'.substr_replace($criteria, '', -1).'"*' : '"'.$criteria.'"'; 
					$query[$index] = str_replace('-', '\-', $criteria);
					if (false !== strpos($query[$index], '?') || false !== strpos($query[$index], '*')) {
						$criteria = explode(' ', $query[$index]);
						foreach ($criteria as $key => $word) {
							if (false !== strpos($word, '?')) {
								$word = str_replace('"', '', $word);
								$criteria[$key] = $word;
							}
							if (false !== strpos($word, '*')) {
								$word = str_replace('"', '', $word);
								$criteria[$key] = $word;
							}
						}
						unset($key, $word);
						$criteria = implode(' ', $criteria);
						$query[$index] = $criteria;
					}
					$query[$index] = str_ireplace(array('"AND"', '"OR"'), array('AND', 'OR'), $query[$index]);
				}
			}
			
			$temp = explode(' ', $query[$index]);
			for ($x = 0; $x < count($temp); $x++) {
				if (false !== strpos($temp[$x], '"(') || false !== strpos($temp[$x], ')"')) {
					$temp[$x] = str_replace('"', '', $temp[$x]);
				}
			}
			$query[$index] = implode(' ', $temp);
		}
		return $query;
	}
	
	/**
	 * Fetch document results base on each query
	 * 
	 * @param array $query
	 * @param array $sub_filter
	 * @return array
	 */
	private function fetchDocumentsFromQueries($query, $sub_filter = array())
	{
		if (!empty($query) && is_array($query)) 
		{
			$em = $this->getDoctrine()->getManager();
			foreach ($query as $index => $criteria)
			{
				$matches = null;
				$criteria = trim($criteria);
				if (preg_match_all('/\[(.*?)\]/', $criteria, $matches))
				{
					if ($matches[1][0] != 'DocumentTypeKey')
					{
						$matches[1][0] = false === strpos($matches[1][0], '[') && false === strpos($matches[1][0], ']') ? $matches[1][0] : str_replace(array('[', ']'), '', $matches[1][0]).'[]';
						$search_field = $em->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => $matches[1][0]));
						$operand = trim(trim($criteria, $matches[0][0]));
						$operand = strtoupper(trim(strstr($operand, '"', true)));
						$value = trim($criteria, $matches[0][0]);
						$value = trim(strstr($value, '"'));
						$value = trim($value, '"');
						$value = explode("_~*^_", $value);
						$value = (is_array($value) && count($value) == 1 ? $value[0] : $value);
						if (!empty($search_field))
						{
							if ($search_field->getCustomField())
							{
								$format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
								if ($search_field->getDataType()=="number" && ($value=="0" || $value=="0.0")){
									$value="0.00";
								}

								$dataType = $search_field->getDataType();
								if ($dataType=="date"){
								    $search_result = $em->getRepository('DocovaBundle:FormDateTimeValues')->customSearch($search_field->getCustomField(), $operand, $value, $sub_filter, self::$filtered_doctypes, $format, $search_field->getDataType());
								}
								else if ($dataType=="number"){
								    $search_result = $em->getRepository('DocovaBundle:FormNumericValues')->customSearch($search_field->getCustomField(), $operand, $value, $sub_filter, self::$filtered_doctypes, $format, $search_field->getDataType());
								}
								else if ($dataType=="names"){
								    $search_result = $em->getRepository('DocovaBundle:FormNameValues')->customSearch($search_field->getCustomField(), $operand, $value, $sub_filter, self::$filtered_doctypes, $format, $search_field->getDataType());
								}
								else{
								    $search_result = $em->getRepository('DocovaBundle:FormTextValues')->customSearch($search_field->getCustomField(), $operand, $value, $sub_filter, self::$filtered_doctypes, $format, $search_field->getDataType());
								}
								$query[$index] = $search_result;
							}
							else {
								$matches[1][0] = str_replace(array('[', ']'), '', $matches[1][0]);
								$format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
								$search_result = $em->getRepository('DocovaBundle:Documents')->customSearch($matches[1][0], $operand, $value, $sub_filter, self::$filtered_doctypes, $format);
								$query[$index] = $search_result;
							}
						}
					}
					else {
						unset($query[$index]);
						if (!empty($query[$index + 1]) && ($query[$index + 1] == 'OR' || $query[$index + 1] == 'AND' || $query[$index + 1] == '"OR"' || $query[$index + 1] == '"AND"')) {
							unset($query[$index + 1]);
						}
					}
				}
				elseif ($criteria !== 'AND' && $criteria !== 'OR' && $criteria !== '"AND"' && $criteria !== '"OR"')
				{
					$criteria = $criteria === '*ALLDOCS*' ? '*' : $criteria;
					if ($this->global_settings->getEnableElastica() === true)
					{
						$esQuery = ['size' => (($this->max_results * 4) < 1000 ? 1000 : ($this->max_results * 4)), 'fields' => array()];//, 'min_score' => 0.25
						
						if ($criteria !== '*')
						{
							$esQuery['query'] = array(
								'bool' => array(
									'must' => array(array('query_string' => array('query' => $criteria)))
								)
							);
						}
						else {
							$esQuery['query'] = array('bool' => array('must' => array()));
						}

						$multi_match_query = array();
						
						if (!empty($sub_filter['folder'])) 
						{
							if (empty($sub_filter['include_subfolders']))
							{
								$multi_match_query['Folders'] = array(urlencode($sub_filter['folder']->getId()));
							}
							else {
								$multi_match_query['Folders'] = array(urlencode($sub_filter['folder']->getId()));
								$children = $em->getRepository('DocovaBundle:Folders')->getDescendants($sub_filter['folder']->getPosition(), $sub_filter['folder']->getLibrary()->getId(), null, true);
								if (!empty($children) && !empty($children[0]))
								{
									foreach ($children as $subfolder) {
										$multi_match_query['Folders'][] = $subfolder['id'];
									}
								}
							}
						}
						elseif(!empty($sub_filter['view']))
						{
							$multi_match_query['Views'] = !is_array($sub_filter['view']) ? explode(',', $sub_filter['view']) : $sub_filter['view'];								
						}
						elseif (!empty($sub_filter['libraries']))
						{
							$multi_match_query['Libraries'] = !is_array($sub_filter['libraries']) ? explode(',', $sub_filter['libraries']) : $sub_filter['libraries'];
						}
											
						$ftQuery = $this->createFTQuery($criteria, 
						                     (!empty($multi_match_query['Libraries']) ? $multi_match_query['Libraries'] : null),
						                     (!empty($multi_match_query['Folders']) ? $multi_match_query['Folders'] : null),
											 (!empty($multi_match_query['Views']) ? $multi_match_query['Views'] : null),								
						                     (!empty(self::$filtered_doctypes) ? self::$filtered_doctypes : null),
						                     (($this->max_results * 4) < 1000 ? 1000 : ($this->max_results * 4)) 
						                   );	
																								
						
						$ch = curl_init('http://localhost:9200/_search');
						
						//ELASTIC SEARCH > v2.4 needs to use '_source' not 'fields'
						if ($this->IS_LEGACY_VERSION==false){
							$ftQuery = str_replace('fields','_source',$ftQuery);
						}
						
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
						curl_setopt($ch, CURLOPT_POSTFIELDS, $ftQuery);
						curl_setopt($ch, CURLOPT_PROXY, '');
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							'Content-Type: application/json',
							'Content-Length: ' . strlen($ftQuery)
						));
						
						$documents = array();
						$result = curl_exec($ch);
						$result = json_decode($result);
						if (!empty($result->hits->total)) {
							foreach ($result->hits->hits as $document) {
								if (!empty($document->_id)) {
									$documents[] = $document->_id;
								}
							}
							$documents = array_unique($documents);
						}
	
						$query[$index] = $documents;
					}
					else {
						$sub_filter['custom_fields'] = array();//<< JAVAD: this was just for WesCEF should be removed or be fetched from global repositories like parameters.yml or GS
						$max = ($this->max_results * 4) < 1000 ? 1000 : ($this->max_results * 4);
						$documents = $em->getRepository('DocovaBundle:Documents')->fullSearchOn($criteria, $sub_filter, self::$filtered_doctypes, $max);
						if (!empty($documents[0])) {
							$query[$index] = $documents;
						}
						else {
							$query[$index] = array();
						}
					}
					
					
/*					
					$search_q	= new QueryString();
					$search_q->setQuery($criteria);
					$bool_fltr	= new Bool();
					$bool_fltr->addMust($search_q);

					if (!empty($sub_filter['folder'])) 
					{
						$innerBool = new Bool();
						$filter = new Match();
						$filter->setFieldQuery('folder.id', $sub_filter['folder']->getId());
						$innerBool->addShould($filter);
						$filter = new Match();
						$filter->setFieldQuery('Bookmarks.Target_Folder.id', $sub_filter['folder']->getId());
						//$filter->setTerm('Bookmarks.Target_Folder.id', $folder_id);
						$innerBool->addShould($filter);
						$bool_fltr->addMust($innerBool);
						unset($filter);
					}
					elseif (!empty($sub_filter['libraries']))
					{
						$libraries = !is_array($sub_filter['libraries']) ? explode(',', $sub_filter['libraries']) : $sub_filter['libraries'];
						if (count($libraries) == 1)
						{
							$filter = new Match();
							$filter->setFieldQuery('folder.Library.id', $libraries[0]);
							$bool_fltr->addMust($filter);
							unset($filter);
						}
						else {
							$innerBool = new Bool();
							foreach ($libraries as $lib)
							{
								$filter = new Match();
								$filter->setFieldQuery('folder.Library.id', $lib);
								$innerBool->addShould($filter);
								unset($filter);
							}
							$bool_fltr->addMust($innerBool);
						}
						unset($libraries, $lib);
					}

					if (!empty($sub_filter['include_subfolders']) && $sub_filter['include_subfolders'] === true && !empty($sub_filter['folder']))
					{
						$children = $sub_filter['folder']->getChildren();
						if ($children->count() > 0)
						{
							foreach ($children as $subfolder) {
								$filter = new Match();
								$filter->setFieldQuery('folder.id', $subfolder->getId());
								$bool_fltr->addShould($filter);
								$filter = new Match();
								$filter->setFieldQuery('Bookmarks.Target_Folder.id', $subfolder->getId());
								$bool_fltr->addShould($filter);
								if (!in_array($subfolder->getId(), self::$folders))
								{
									self::$folders[] = $subfolder->getId();
								}
								unset($filter);
							}
						}
						unset($children, $subfolder);
					}
			
					if (!empty(self::$filtered_doctypes) && count(self::$filtered_doctypes) > 0)
					{
						$filter = new Match();
						$filter->setField('DocType.id', self::$filtered_doctypes);
						$bool_fltr->addMust($filter);
						unset($filter);
					}
			
					$eQuery = new Query();
					$eQuery->setSize((int)$this->max_results < 250 ? ((int)$this->max_results + 50) : 260);
					$eQuery->setQuery($bool_fltr);
					$finder = $this->container->get('fos_elastica.finder.website.documents');
					$search_result = $finder->find($eQuery);
					unset($search_q, $filter, $bool_fltr);
					$query[$index] = $search_result;
*/
				}
			}
		}
		
		return $query;
	}
	
	/**
	 * Merge the results of search in a array base on AND/OR operands
	 * 
	 * @param array $results
	 * @return array
	 */
	private function mergeResultsByOpernads($results)
	{
		$results = array_values($results);
		$documents = $results[0];
		$x = 1;
		while ($x < count($results) && !empty($results[$x])) {
			if ($results[$x] == 'AND' || $results[$x] == '"AND"') 
			{
				if (!empty($documents) && count($documents) > 0) 
				{
					$documents = array_intersect($documents, $results[$x + 1]);
/*
					foreach ($documents as $index => $doc) 
					{
						$found = false;
						foreach ($results[$x + 1] as $res) {
							if ($doc->getId() == $res->getId()) {
								$found = true;
								break;
							}
						}
						
						if ($found === false) {
							unset($documents[$index]);
						}
					}
*/
				}
			}
			elseif ($results[$x] == 'OR' || $results[$x] == '"OR"') 
			{
				if (!empty($results[$x + 1]) && count($results[$x + 1]) > 0) 
				{
					$documents = array_unique(array_merge($documents, $results[$x + 1]));
/*
					foreach ($results[$x + 1] as $res)
					{
						$found = false;
						if (!empty($documents) && count($documents) > 0) 
						{
							foreach ($documents as $doc) {
								if ($res->getId() == $doc->getId()) {
									$found = true;
									break;
								}
							}
						}
						
						if ($found === false) {
							$documents[] = $res;
						}
					}
*/
				}
			}
			
			$x += 2;
		}
		$documents = array_values($documents);
		return $documents;
	}
	
	/**
	 * Apply ACL to documents list and build the XML for view
	 * 
	 * @param array $documents
	 * @param array $nodes
	 * @param boolean $action
	 * @return \DOMDocument
	 */
	private function buildDocsXML($documents, $nodes = array(), $action = false)
	{
		$count = 0;
		$exceed = false;
		$xml_doc = '<?xml version="1.0" encoding="UTF-8" ?><documents>';
		if (!empty($documents) && !empty($documents[0]))
		{
			$twig = $this->container->get('twig');
			$em = $this->getDoctrine()->getManager();
			$security_check = new Miscellaneous($this->container);
			$xml_fields = $em->getRepository('DocovaBundle:ViewColumns')->getValidColumns(null, $nodes);
			$len = count($documents);
			for ($x = 0; $x < $len; $x++)
			{
				$access = false;
				$document = $em->getReference('DocovaBundle:Documents', $documents[$x]['id']);
				if (!empty($documents[$x]['Is_App'])) {
					$app = $em->getReference('DocovaBundle:Libraries', $documents[$x]['Library_Id']);
					$docova = new Docova($this->container);
					$acl = $docova->DocovaAcl($app);
					$docova = null;
					$access = $acl->isDocReader($document);
				}
				else {
					$access = $security_check->canReadDocument($document, true, $action);
				}
				if ($access === true)
				{
					$document = null; unset($document);
					$xml_doc .= "<document><docKey>DK{$documents[$x]['id']}</docKey><docid>{$documents[$x]['id']}</docid><libnsf><![CDATA[{$documents[$x]['Library_Title']}]]></libnsf><libname><![CDATA[{$documents[$x]['Library_Title']}]]></libname><isapp>".(!empty($documents[$x]['Is_App']) ? 1 : 0)."</isapp><rectype>doc</rectype><libid>{$documents[$x]['Library_Id']}</libid><typekey>{$documents[$x]['id']}</typekey><folderid>{$documents[$x]['Folder_Id']}</folderid>";
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
						elseif ($column['Field_Name'] == 'Bookmarks' && !empty($is_bookmark)) {
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
								'library' => $em->getReference('DocovaBundle:Libraries', $documents[$x]['Library_Id'])
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
								$date = null;
								if (!empty($output_nodes[$c]['value']))
								{
									$date = new \DateTime();
									if (is_string($output_nodes[$c]['value'])) {
										$output_nodes[$c]['value'] = str_replace('/', '-', $output_nodes[$c]['value']);
										$output_nodes[$c]['value'] = strtotime($output_nodes[$c]['value']);
										$date->setTimestamp($output_nodes[$c]['value']);
									}
									elseif ($output_nodes[$c]['value'] instanceof \DateTime) {
										$date = $output_nodes[$c]['value'];
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
					$count++;
					if ($count == ($this->max_results + 1)) {
						$exceed = true;
						break;
					}
				}
			}
			$xml_doc .= '<srchCount>'.($exceed === true ? $count - 1 : $count).'</srchCount>';
			$xml_doc .= '<moreResults>'.($exceed === true ? 'Yes' : 'No').'</moreResults>';
			$xml_doc .= '<status>OK</status>';
		}
		else {
			$xml_doc .= '<srchCount>0</srchCount>';
			$xml_doc .= '<moreResults>No</moreResults>';
			$xml_doc .= '<status>OK</status>';
		}
		$xml_doc .= '</documents>';
		return $xml_doc;
	}
	
	/**
	 * Apply ACL to documents list and build the Summary XML for view
	 *
	 * @param array $documents
	 * @param object $folder_obj
	 * @return \DOMDocument
	 */
	private function buildDocsSummaryXML($documents, $folder_obj)
	{
	    $count = 0;
	    $exceed = false;
	    $xml_result = new \DOMDocument('1.0', 'UTF-8');
	    $root = $xml_result->appendChild($xml_result->createElement('documents'));
	    if (!empty($documents) && !empty($documents[0]) && !empty($folder_obj))
	    {
	        $twig = $this->container->get('twig');
	        $em = $this->getDoctrine()->getManager();
	        $defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());	        
	        $security_check = new Miscellaneous($this->container);
	        $customACL = new CustomACL($this->container);
	        $managerlist = $customACL->getObjectACEUsers($folder_obj, 'owner');
	        $inherited_managers = $customACL->getObjectACEUsers($folder_obj, 'master');
	        if ($inherited_managers->count() > 0)
	        {
	            foreach ($inherited_managers as $im)
	            {
	                $managerlist->add($im);
	            }
	        }
	        unset($inherited_managers, $im);
	        $folderauthors = '';
	        foreach ($managerlist as $m)
	        {
	            if (false !== $user = $this->findUserAndCreate($m, false, true, false))
	            {
	                $folderauthors .= $user->getUserNameDnAbbreviated().';';
	            }
	        }
	        unset($managerlist, $m, $user);
	        $len = count($documents);
	        for ($x = 0; $x < $len; $x++)
	        {
	            $access = false;
	            $document = $em->getReference('DocovaBundle:Documents', $documents[$x]['id']);
	            if (!empty($documents[$x]['Is_App'])) {
	                $app = $em->getReference('DocovaBundle:Libraries', $documents[$x]['Library_Id']);
	                $docova = new Docova($this->container);
	                $acl = $docova->DocovaAcl($app);
	                $docova = null;
	                $access = $acl->isDocReader($document);
	            }
	            else {
	                $access = $security_check->canReadDocument($document, true, false);
	            }
	            if ($access === true)
	            {
	                $authors = $folderauthors . ';' . $document->getAuthor()->getUserNameDnAbbreviated();
	                $attachments = $em->getRepository('DocovaBundle:AttachmentsDetails')->getDocumentAttachmentsArray($documents[$x]['id']);
	                $file_names = $attach_date = $attach_size = '';
	                if (!empty($attachments))
	                {
	                    $alen = count($attachments);
	                    for ($c = 0; $c < $alen; $c++)
	                    {
	                        $file_names 	.= $attachments[$c]['File_Name'].';';
	                        $tempdate = (!empty($attachments[$c]['File_Date']) ? $attachments[$c]['File_Date'] : null);
	                        $attach_date	.= (!empty($tempdate) ? $tempdate->format($defaultDateFormat) : '').';';
	                        $attach_size	.= $attachments[$c]['File_Size'].';';
	                    }
	                    $file_names	= substr_replace($file_names, '', -1);
	                    $attach_date= substr_replace($attach_date, '', -1);
	                    $attach_size= substr_replace($attach_size, '', -1);
	                    unset($attachments, $alen, $c);
	                }
	                $summary = " <div style='margin-bottom:3px;padding-left:20px'><b>".$documents[$x]['Doc_Title']."</b><div style='padding-left:20px;'>Author: ".$document->getAuthor()->getUserNameDnAbbreviated()
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
	                $document = null; unset($document);
	                $count++;
	                if ($count == ($this->max_results + 1)) {
	                    $exceed = true;
	                    break;
	                }
	            }
	        }
//	        $root->appendChild($xml_result->createElement('srchCount', ($exceed === true ? $count - 1 : $count)));
//	        $root->appendChild($xml_result->createElement('moreResults', ($exceed === true ? 'Yes' : 'No')));
//	        $root->appendChild($xml_result->createElement('status', 'OK'));
	    }
	    else {
//	        $root->appendChild($xml_result->createElement('srchCount', 0));
//	        $root->appendChild($xml_result->createElement('moreResults', 'No'));
//	        $root->appendChild($xml_result->createElement('status', 'OK'));
	    }
	    return $xml_result;
	}
	/**
	 * Build XML response for view entries
	 * 
	 * @param array $documents
	 * @param AppViews $viewentity
	 * @param string $appid
	 * @return string
	 */
	private function buildViewXML($documents, $viewentity, $appid)
	{
	    $result_xml = new \DOMDocument("1.0", "UTF-8");
	    $root = $result_xml->appendChild($result_xml->createElement('documents'));
		
		$docova = new Docova($this->container);
		$application =  $docova->DocovaApplication(['appID' => $appid]);
		if (empty($application)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}
		$perspective = new \DOMDocument();
		if ($viewentity->getViewPerspective()){
			$perspective->loadXML($viewentity->getViewPerspective());
		}
		$view = $docova->DocovaView($application, "", $viewentity);
		$result = $view->getViewEntriesByID($documents);
		if (!empty($result) && !empty($result[0]))
		{
		    $em = $this->getDoctrine()->getManager();
			$docovaAcl = $docova->DocovaAcl($application->getEntity());
			foreach ($result as $index => $document) {
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
		
		return $result_xml->saveXML();
	}

	/**
	 * Apply ACL to documents list and build the JSON for view
	 *
	 * @param array $documents
	 * @param string $viewid
	 * @param string $appid
	 * @return JsonResponse
	 */	
	private function buildViewJSON($documents, $viewid, $appid)
	{
		//$output = array('@timestamp' => time(), '@toplevelentries' => '0');
		$output = '{"@timestamp":'.time().',"@toplevelentries":"';
		$em = $this->getDoctrine()->getManager();
		
		$docova = new Docova($this->container);
		$application =  $docova->DocovaApplication(['appID' => $appid]);
		if (empty($application)) {
			throw $this->createNotFoundException('Unspecified application source ID.');
		}
		$viewentity =  $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('id' => $viewid, 'application' => $appid));
		if (empty($viewentity)) {
			throw $this->createNotFoundException('Could not open view.');
		}
		$perspective = new \DOMDocument();
		if ($viewentity->getViewPerspective()){
			$perspective->loadXML($viewentity->getViewPerspective());
		}
		$view = $docova->DocovaView($application, "", $viewentity);
		$result = $view->getViewEntriesByID($documents);
		if (!empty($result))
		{
			$docovaAcl = $docova->DocovaAcl($application->getEntity());
			$len = count($result);
			$output .= $len;
			$output .= '","viewentry":[';
			$added = false;
			foreach ($result as $document) {
				$doc = $em->getReference('DocovaBundle:Documents', $document['Document_Id']);
				if ( !$docovaAcl->isDocReader($doc)) {
					continue;
				}
	
				$added = true;
				$output .= '{"@unid":"'.$document['Document_Id'].'","entrydata":[';
				foreach ($perspective->getElementsByTagName('xmlNodeName') as $index => $node) {
					if (array_key_exists($node->nodeValue, $document))
					{
						$value = $document[$node->nodeValue];
						$type = trim($perspective->getElementsByTagName('dataType')->item($index)->nodeValue);
//						$iscategorized = $perspective->getElementsByTagName('isCategorized')->item($index)->nodeValue;
						
						if ($type == 'datetime' || $type == 'date') {
							if (!empty($value)) {
								$value = new \DateTime($value);
								$value = $value->format(($type == 'date' ? 'Ymd' : 'Ymd His'));
								if(substr($value, 0, 8) == '10000101'){
								    if($type == 'datetime'){
								        if(substr($value, 0, 15) == '10000101 000000'){
								            $value = null;
								        }
								    }else{
								        $value = null;
								    }
								}
							}else{
								$value = null;
							}
							$type = 'datetime';
						}
						else {
				            $value = str_replace('\\', '\\\\', $value);
				            $value = str_replace('"', '\\"', $value);
						}
						$value = html_entity_decode($value, ENT_QUOTES | ENT_XML1, "UTF-8");
						
// JAVAD >> This code was causing the search results look like duplicated documents when the categorized column is marked as "Show multi values in separate entries"
//						if ( $iscategorized == "1" )
//							$value = '';
						$column = intval(str_replace('CF', '', $node->nodeValue));
						$output .= '{"@columnnumber":'.$column.',"'.$type.'":["'.$value.'"]},';
					}
				}
				$output = substr_replace($output, ']},', -1);
			}
					
			if ($added === true){
				$output = substr_replace($output, ']', -1);
			}else{
				$output = '{"@timestamp":'.time().',"@toplevelentries":"0"';
			}
		}
		else {
			$output .= '0"';
		}
		$output .= '}';
	
		$output = str_replace(array("\r\n", "\n"), "\\n", $output);
	
		$response = JsonResponse::fromJsonString($output);
		$response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);

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
	 * Initialize the current user and global_settings 
	 */
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
	
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
	}
}
