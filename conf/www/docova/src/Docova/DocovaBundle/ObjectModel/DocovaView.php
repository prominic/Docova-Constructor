<?php

namespace Docova\DocovaBundle\ObjectModel;

use Docova\DocovaBundle\Extensions\ViewManipulation;

/**
 * Back-end class for interaction with DOCOVA views
 * @author javad_rahimi
 *        
 */
class DocovaView 
{
	private $_em;
	private $_router;
	private $_docova;
	private $_view = null;
	private $_doccollection = null;
	public $columns = array();
	public $viewid = null;
	public $viewName = null;
	public $viewAlias = null;
	public $parentApp = null;
	public $isFolder = false;
	public $isPrivate = false;
	public $isCategorized = false;
	
	public function __construct(DocovaApplication $app, $viewname, $viewEntity=null, Docova $docova_obj = null)
	{
		if (!empty($docova_obj))
		{
			$this->_docova = $docova_obj;
			$this->_em = $docova_obj->getManager();
			$this->_router = $docova_obj->getRouter();
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_docova = $docova;
				$this->_em = $docova->getManager();
				$this->_router = $docova->getRouter();
			}
			else {
				throw new \Exception('Oops! DocovaView construction failed. Entity Manager\Router not available.');
			}
		}

		if ( ! is_null($viewEntity)){
			$this->_view  = $viewEntity;
		}else{
			$this->_view = $this->_em->getRepository('DocovaBundle:AppViews')->findByNameAlias($viewname, $app->appID);
		}
		
		if (!empty($this->_view))
		{
			$this->viewid = $this->_view->getId();
			$this->viewName = $this->_view->getViewName();
			$this->viewAlias = $this->_view->getViewAlias();
			$this->parentApp = $app;
			$this->isFolder = $this->_view->getEmulateFolder();
			$this->isPrivate = false; //@TODO: needs to be implemented
			$xml = $this->_view->getViewPerspective();
			$dom = new \DOMDocument();
			$dom->loadXML($xml);
			$columns = $dom->getElementsByTagName('column');
			if ($columns->length)
			{
				for ($x = 0; $x < $columns->length; $x++)
				{
					$column_arr = array();
					$column_arr['Alignment'] = empty($dom->getElementsByTagName('align')->item($x)->nodeValue) ? 'left' : $dom->getElementsByTagName('align')->item($x)->nodeValue;
					$column_arr['DateFmt'] = empty($dom->getElementsByTagName('dateFormat')->item($x)->nodeValue) || $dom->getElementsByTagName('dateFormat')->item($x)->nodeValue == 'undefined' ? '' : $dom->getElementsByTagName('dateFormat')->item($x)->nodeValue;
					$column_arr['FontColor'] = empty($dom->getElementsByTagName('color')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('color')->item($x)->nodeValue;
					$column_arr['FontFace'] = empty($dom->getElementsByTagName('fontFamily')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('fontFamily')->item($x)->nodeValue;
					$column_arr['FontPointSize'] = empty($dom->getElementsByTagName('fontSize')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('fontSize')->item($x)->nodeValue;
					$column_arr['FontStyle'] = empty($dom->getElementsByTagName('fontStyle')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('FontStyle')->item($x)->nodeValue;
					$column_arr['Formula'] = empty($dom->getElementsByTagName('columnFormula')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('columnFormula')->item($x)->nodeValue;
					$column_arr['TranslatedFormula'] = empty($dom->getElementsByTagName('translatedFormula')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('translatedFormula')->item($x)->nodeValue;
					$column_arr['HeaderAlignment'] = empty($dom->getElementsByTagName('alignH')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('alignH')->item($x)->nodeValue;
					$column_arr['HeaderFontColor'] = empty($dom->getElementsByTagName('colorH')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('colorH')->item($x)->nodeValue;
					$column_arr['HeaderFontFace'] = empty($dom->getElementsByTagName('fontFamilyH')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('fontFamilyH')->item($x)->nodeValue;
					$column_arr['HeaderFontPointSize'] = empty($dom->getElementsByTagName('fontSizeH')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('fontSizeH')->item($x)->nodeValue;
					$column_arr['HeaderFontStyle'] = empty($dom->getElementsByTagName('fontStyleH')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('fontStyleH')->item($x)->nodeValue;
					$column_arr['IsAccentSensitiveSort'] = false;
					$column_arr['IsCaseSensitiveSort'] = false;
					$column_arr['IsCategory'] = empty($dom->getElementsByTagName('isCategorized')->item($x)->nodeValue) ? false : true;
					if ( $column_arr['IsCategory'])
						$this->isCategorized = true;
					$column_arr['IsField'] = empty($dom->getElementsByTagName('columnFormula')->item($x)->nodeValue) || 1 == preg_match('/(\$|@|-|%|{|}|\>|\<)/', $dom->getElementsByTagName('columnFormula')->item($x)->nodeValue) ? false : true;
					$column_arr['IsFontBold'] = empty($dom->getElementsByTagName('fontWeight')->item($x)->nodeValue) || $dom->getElementsByTagName('fontWeight')->item($x)->nodeValue == 'null' ? false : true;
					$column_arr['IsFontItalic'] = empty($dom->getElementsByTagName('fontStyle')->item($x)->nodeValue) || false === stripos($dom->getElementsByTagName('fontStyle')->item($x)->nodeValue, 'italic') ? false : true;
					$column_arr['IsFontStrikethrough'] = false;
					$column_arr['IsFontUnderline'] = empty($dom->getElementsByTagName('textDecoration')->item($x)->nodeValue) || false === stripos($dom->getElementsByTagName('textDecoration')->item($x)->nodeValue, 'underline') ? false : true;
					$column_arr['IsFormula'] = empty($dom->getElementsByTagName('columnFormula')->item($x)->nodeValue) || !preg_match('/(\$|@|-|%|{|}|\>|\<)/', $dom->getElementsByTagName('columnFormula')->item($x)->nodeValue) ? false : true;
					$column_arr['IsHeaderFontBold'] = empty($dom->getElementsByTagName('fontWeightH')->item($x)->nodeValue) || $dom->getElementsByTagName('fontWeightH')->item($x)->nodeValue == 'null' ? false : true;
					$column_arr['IsHeaderFontItalic'] = empty($dom->getElementsByTagName('fontStyleH')->item($x)->nodeValue) || false === stripos($dom->getElementsByTagName('fontStyleH')->item($x)->nodeValue, 'italic') ? false : true;
					$column_arr['IsHeaderFontStrikethrough'] = false;
					$column_arr['IsHeaderFontUnderline'] = empty($dom->getElementsByTagName('textDecorationH')->item($x)->nodeValue) || false === stripos($dom->getElementsByTagName('textDecorationH')->item($x)->nodeValue, 'underline') ? false : true;
					$column_arr['IsHidden'] = empty($dom->getElementsByTagName('isHidden')->item($x)->nodeValue) || $dom->getElementsByTagName('isHidden')->item($x)->nodeValue == 'null' ? false : true;
					$column_arr['IsHideDetail'] = false;
					$column_arr['IsIcon'] = false;
					$column_arr['IsNumberAttribParens'] = false;
					$column_arr['IsNumberAttribPercent'] = false;
					$column_arr['IsNumberAttribPunctuated'] = false;
					$column_arr['IsResize'] = true;
					$column_arr['IsResortAscending'] = false;
					$column_arr['IsResortDescending'] = false;
					$column_arr['IsResortToView'] = false;
					$column_arr['IsResponse'] = empty($dom->getElementsByTagName('responseFormula')->item($x)->nodeValue) ? false : true;
					$column_arr['IsSecondaryResort'] = false;
					$column_arr['IsSecondaryResortDescending'] = false;
					$column_arr['IsShowTwistie'] = false;
					$column_arr['IsSortDescending'] = empty($dom->getElementsByTagName('sortOrder')->item($x)->nodeValue) || false === stripos($dom->getElementsByTagName('sortOrder')->item($x)->nodeValue, 'desc') ? false : true;
					$column_arr['IsSorted'] = empty($dom->getElementsByTagName('sortOrder')->item($x)->nodeValue) || $dom->getElementsByTagName('sortOrder')->item($x)->nodeValue == 'none' ? false : true;
					$column_arr['ItemName'] = empty($dom->getElementsByTagName('xmlNodeName')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('xmlNodeName')->item($x)->nodeValue;
					$column_arr['ListSep'] = 2;
					$column_arr['NumberAttrib'] = 0;
					$column_arr['NumberDigits'] = 0;
					$column_arr['NumberFormat'] = empty($dom->getElementsByTagName('numberFormat')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('numberFormat')->item($x)->nodeValue;
					$column_arr['Parent'] = $this;
					$column_arr['Position'] = $x + 1;
					$column_arr['ResortToViewName'] = $this->viewName;
					$column_arr['SecondaryResortColumnIndex'] = null;
					$column_arr['TimeDateFmt'] = empty($dom->getElementsByTagName('dateFormat')->item($x)->nodeValue) || $dom->getElementsByTagName('dateFormat')->item($x)->nodeValue == 'undefined' ? '' : $dom->getElementsByTagName('dateFormat')->item($x)->nodeValue;
					$column_arr['TimeFmt'] = empty($dom->getElementsByTagName('dateFormat')->item($x)->nodeValue) || $dom->getElementsByTagName('dateFormat')->item($x)->nodeValue == 'undefined' ? '' : $dom->getElementsByTagName('dateFormat')->item($x)->nodeValue;
					$column_arr['TimeZoneFmt'] = empty($dom->getElementsByTagName('dateFormat')->item($x)->nodeValue) || $dom->getElementsByTagName('dateFormat')->item($x)->nodeValue == 'undefined' ? '' : $dom->getElementsByTagName('dateFormat')->item($x)->nodeValue;
					$column_arr['Title'] = empty($dom->getElementsByTagName('title')->item($x)->nodeValue) ? '' : $dom->getElementsByTagName('title')->item($x)->nodeValue;
					$column_arr['Width'] = empty($dom->getElementsByTagName('width')->item($x)->nodeValue) ? 4 : $dom->getElementsByTagName('width')->item($x)->nodeValue;
					$this->columns[] = $column_arr;
					$column_arr = null;
				}
			}
		}
		else {
			throw new \Exception('Oops! Class construction failed due ot unspecifed view source.');
		}
	}
	
	/**
	 * Property get
	 * 
	 * @param string $name
	 * @throws \OutOfBoundsException
	 * @return mixed
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'Aliases':
				return $this->viewAlias;
				break;
			case 'AllEntries':
				return $this->getAllDocuments();
				break;
			case 'Name':
				return $this->viewName;
				break;
			case 'ColumnCount':
				return count($this->columns);
				break;
			default:
				throw new \OutOfBoundsException('Undefined property "'.$name.'" via __get');
		}
	}

	public function getRouter()
	{
		return $this->_router;
	}
	
	/**
	 * Returns a view navigator object for the current view
	 * 
	 * @return DocovaViewNavigator
	 */
	public function createViewNav()
	{
		$nav = new DocovaViewNavigator($this);
		return $nav;
	}
	
	/**
	 * Returns a view navigator object for a given category in the current view
	 * 
	 * @param string $category
	 * @return NULL|DocovaViewNavigator
	 */
	public function createViewNavFromCategory($category)
	{
		if (empty($category))
			return null;
		
		$nav = new DocovaViewNavigator($this, array('category' => $category));
		return $nav;
	}

	/**
	 * Returns an collection of Docova Document objects from the view
	 * 
	 * @return DocovaCollection
	 */
	public function getAllDocuments($root_path = '')
	{
	    if(!is_null($this->_doccollection)){
	        return $this->_doccollection;
	    }
	    
		$collection = $this->_docova->DocovaCollection($this);
		if (empty($this->viewid)){
		    $this->_doccollection = $collection;
			return $collection;
		}

		$sort_columns = array();
		if (!empty($this->columns))
		{
			foreach ($this->columns as $column)
			{
				if (!empty($column['IsSorted']))
				{
					$sort_columns[$column['ItemName']] = $column['IsSortDescending'] === false ? 'ASC' : 'DESC';
				}
			}
		}
		$view_hanlder = new ViewManipulation($this->_docova, $this->parentApp->appID);
		$viewname = str_replace('-', '', $this->viewid);
		$documents = $view_hanlder->getAllViewDocuments($viewname, $sort_columns);
		if (empty($documents))
			return $collection;
		
		foreach ($documents as $doc)
		{
			$entry = $this->_docova->DocovaDocument($this, $doc['Document_Id'], $root_path);
			$collection->addEntry($entry);
		}
		
		$this->_doccollection = $collection;
		return $collection;
	}
	
	/**
	 * Returns a collection of Docova Document objects based on a matching key
	 * 
	 * @param array|string $key
	 * @param boolean $exact_match
	 * @return DocovaCollection
	 */
	public function getAllDocumentsByKey($key, $exact_match = true, $root_path = '')
	{
		$key = !is_array($key) ? array($key) : $key;
		$collection = $this->_docova->DocovaCollection($this);
		if (empty($this->viewid) || empty($key)){
			return $collection;
		}
		
		$sort_columns = $response_column = [];
		if (!empty($this->columns))
		{
			foreach ($this->columns as $column)
			{
				if (!empty($column['IsSorted']))
				{
					$sort_columns[] = $column['ItemName'];
				}
				
				if ($column['IsResponse'] === true) {
					$response_column[] = $column['ItemName'];
				}
			}
		}
		
		if (empty($sort_columns)){
			return $collection;
		}
		
		if (count($key) < count($sort_columns)) {
			$sort_columns = array_slice($sort_columns, 0, count($key));
		}
		
		$view_id = str_replace('-', '', $this->viewid);
		$view_handler = new ViewManipulation($this->_docova, $this->parentApp->appID);
		$documents = $view_handler->getDocumentsByColumnsKey($view_id, $sort_columns, $key, $exact_match);
		if (empty($documents)){
		    $this->_doccollection = $collection;
			return $collection;
		}
		
		foreach ($documents as $doc)
		{
			$entry = $this->_docova->DocovaDocument($this, $doc['Document_Id'], $root_path);
			if (!empty($response_column)) {
				$is_response = false;
				foreach ($response_column as $column) {
					if (!is_null($doc['RESP_'.$column])) {
						$is_response = $doc['RESP_'.$column];
						break;
					}
				}
				if ($is_response !== false) {
					$parendDoc = $this->_docova->DocovaDocument($this, $is_response, $root_path);
					$entry->makeResponse($parendDoc);
				}
			}
			$collection->addEntry($entry);
		}
		return $collection;
	}

	/* this function will be used by the views to handle paging/restrictToCategory etc */
	public function getAllDocumentsByKeyEX($start = null, $count =null, $key = "", $exact_match = true, $root_path = '', $ret_count = false, $restricttochildrenkey = '', $filters = [])
	{
		
		$documents = array();
		if (empty($this->viewid) ){
			return $documents;
		}

		$view_id = str_replace('-', '', $this->viewid);
		$view_handler = new ViewManipulation($this->_docova, $this->parentApp->appID);

		$sort_columns = $filter_columns = [];
		
		//no restrict to category
		if (empty($key)){
			if (!empty($this->columns))
			{
				foreach ($this->columns as $column)
				{
					if (!empty($column['IsSorted']))
					{
						$sort_columns[$column['ItemName']] = $column['IsSortDescending'] === false ? 'ASC' : 'DESC';
					}
					
					if (!empty($filters)) {
						foreach ($filters as $key => $value) {
							if (strtolower($key) == strtolower($column['Title'])) {
								$filter_columns[$column['ItemName']] = $value;
							}
						}
					}
				}
			}

			if (empty($restricttochildrenkey) && empty($filter_columns)) {
				$documents = $view_handler->getAllViewDocuments($view_id, $sort_columns, $start, $count, $ret_count);
			}
			elseif (empty($restricttochildrenkey) && !empty($filter_columns)) {
				$documents = $view_handler->getDocumentsByColumnsKey($view_id, array_keys($filter_columns), array_values($filter_columns), true, $count, $start, $sort_columns);
			}
			elseif (!empty($restricttochildrenkey) && empty($filter_columns)){
				$restrict_key = array('Parent_Doc');
				$key = array($restricttochildrenkey);
				$documents = $view_handler->getDocumentsByColumnsKey($view_id, $restrict_key, $key, $exact_match, $count, $start, $sort_columns);
			}

			
		}else{
			if (!empty($this->columns))
			{
				foreach ($this->columns as $column)
				{
					if (!empty($column['IsSorted']))
					{
						$sort_columns[] = $column['ItemName'];
					}
				}
			}
			$key = !is_array($key) ? array($key) : $key;
			if (count($key) < count($sort_columns)) {
				$sort_columns = array_slice($sort_columns, 0, count($key));
			}
			$documents = $view_handler->getDocumentsByColumnsKey($view_id, $sort_columns, $key, $exact_match, $count, $start);
		}
		return $documents;
	}

	
	/**
	 * Returns an array of view data based on document ids
	 *
	 * @param array $docids
	 * @param string $root_path
	 * @return Array of Arrays
	 */
	public function getViewEntriesByID($docids, $root_path = '')
	{
		$documents = array();
		if (empty($this->viewid) ){
			return $documents;
		}

		$view_id = str_replace('-', '', $this->viewid);
		$view_handler = new ViewManipulation($this->_docova, $this->parentApp->appID);
		
	
		$documents = $view_handler->getDocumentsByID($view_id, $docids);
			
		return $documents;
	}	

	
	/**
	 * Returns a Docova Document object based on a document id
	 * 
	 * @param string $docid
	 * @return NULL|DocovaDocument
	 */
	public function getDocument($docid, $root_path)
	{
		if (empty($docid) || empty($this->viewid))
			return null;
		
		$view_id = str_replace('-', '', $this->viewid);
		$view_handler = new ViewManipulation($this->_docova);
		$document = $view_handler->getDocumentsByColumnsKey($view_id, array('Document_Id'), array($docid), true);
		if (empty($document))
			return null;
		
		$document = $this->_docova->DocovaDocument($this, $docid, $root_path);
		return $document;
	}
	
	/**
	 * Returns a DocovaDocument object based on a matching key
	 * 
	 * @param array $key
	 * @param boolean $exact_match
	 * @return NULL|DocovaDocument
	 */
	public function getDocumentByKey($key, $exact_match = true, $root_path = '')
	{
		if (empty($key) || empty($this->viewid))
			return null;
		
		$key = is_array($key) ? $key : array($key);
		$sort_columns = array();
		if (!empty($this->columns))
		{
			foreach ($this->columns as $column)
			{
				if (!empty($column['IsSorted']))
				{
					$sort_columns[] = $column['ItemName'];
				}
			}
		}

		if (empty($sort_columns))
			return null;
		
		if (count($key) < count($sort_columns)) {
			$sort_columns = array_slice($sort_columns, 0, count($key));
		}
		
		$view_id = str_replace('-', '', $this->viewid);
		$view_handler = new ViewManipulation($this->_docova, $this->parentApp->appID);
		$document = $view_handler->getDocumentsByColumnsKey($view_id, $sort_columns, $key, $exact_match, 1);
		if (empty($document))
			return null;
		
		$document = $this->_docova->DocovaDocument($this, $document[0]['Document_Id'], $root_path);
		return $document;
	}
	
	/**
	 * Returns a Docova Document object for first entry in the view
	 * 
	 * @return NULL|DocovaDocument
	 */
	public function getFirstDocument()
	{
	    if (empty($this->viewid)){
			return null;
	    }
		
	    if(is_null($this->_doccollection)){
	        $documents = $this->getAllDocuments();
	    }else{
	        $documents = $this->_doccollection;
	    }

	    if (empty($documents->count)){
			return null;
	    }
		
		return $documents->getFirstEntry();
	}
	
	/**
	 * Returns a Docova Document object for previous entry in the view
	 * 
	 * @param DocovaDocument $docovadoc
	 * @return NULL|DocovaDocument
	 */
	public function getPrevDocument($docovadoc)
	{
	    if (empty($docovadoc) || empty($this->viewid)){
			return null;
	    }
		
	    if(is_null($this->_doccollection)){
	        $documents = $this->getAllDocuments();
	    }else{
	        $documents = $this->_doccollection;
	    }
	    
	    if (empty($documents)){
			return null;
	    }
		
		return $documents->getPrevEntry($docovadoc);
	}
	
	/**
	 * Returns a Docova Document object for next entry in the view
	 * 
	 * @param DocovaDocument $docovadoc
	 * @return NULL|DocovaDocument
	 */
	public function getNextDocument($docovadoc)
	{
	    if (empty($docovadoc) || empty($this->viewid)){
			return null;
	    }
		
	    if(is_null($this->_doccollection)){
	        $documents = $this->getAllDocuments();
	    }else{
	        $documents = $this->_doccollection;
	    }
	    
	    if (empty($documents)){
			return null;
	    }
		
		return $documents->getNextEntry($docovadoc);
	}
	
	/**
	 * Returns a Docova Document object based on position in the view
	 * 
	 * @param integer $position
	 * @return NULL|DocovaDocument
	 */
	public function getNthDocument($position)
	{
	    if (empty($position) || empty($this->viewid)){
			return null;
	    }
		
	    if(is_null($this->_doccollection)){
	        $documents = $this->getAllDocuments();
	    }else{
	        $documents = $this->_doccollection;
	    }
	    
		return $documents->getNthEntry($position);
	}
	
	/**
	 * Returns a Docova Document object for last entry in the view
	 * 
	 * @return NULL|DocovaDocument
	 */
	public function getLastDocument()
	{
	    if (empty($this->viewid)){
			return null;
	    }
		
	    if(is_null($this->_doccollection)){
	        $documents = $this->getAllDocuments();
	    }else{
	        $documents = $this->_doccollection;
	    }
	    
	    if (empty($documents)){
			return null;
	    }
		
		return $documents->getLastEntry();
	}
	
	
	/**
	 * Reloads the view contents 
	 *
	 */
	public function refresh(){
	    $this->_doccollection = null;
	    $this->getAllDocuments();
	}
}