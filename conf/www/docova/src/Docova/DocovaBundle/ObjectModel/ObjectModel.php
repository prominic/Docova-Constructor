<?php

namespace Docova\DocovaBundle\ObjectModel;

use Docova\DocovaBundle\Entity\FormTextValues;
use Docova\DocovaBundle\Entity\FormDateTimeValues;
use Docova\DocovaBundle\Entity\FormNumericValues;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Docova\DocovaBundle\Entity\FormNameValues;
use Docova\DocovaBundle\Entity\FormGroupValues;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserProfile;

/**
 * @author javad rahimi
 * Object model for user php scripts        
 */
class ObjectModel 
{
	

	private $_user;
	private $_router;
	private $_docova;
	private $_container;
	private $_em;
	private $_currentDocovaDocument =null;
	private $_currentDocovaApp = null;
	
	public function __construct(Docova $docova = null)
	{
		if (!empty($docova))
		{
			$this->_docova = $docova;
			$this->_em = $docova->getManager();
			$this->_router = $docova->getRouter();
			$this->_container = $docova->getContainer();
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_docova = $docova;
				$this->_em = $docova->getManager();
				$this->_router = $docova->getRouter();
				$this->_container = $docova->getContainer();
			}
		}
	}
	
	/**
	 * Set generic application object
	 * 
	 * @param string|\Docova\DocovaBundle\Entity\Libraries $application
	 */
	public function setApplication($application)
	{
		if ($application instanceof \Docova\DocovaBundle\Entity\Libraries) {
			$this->_currentDocovaApp =  $this->_docova->DocovaApplication(null, $application);
		}
		elseif ($application instanceof \Docova\DocovaBundle\ObjectModel\DocovaApplication) {
		    $this->_currentDocovaApp =  $application;
		}
		else {
			$applicationEntity = $this->_em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $application, 'Trash' => false));
			if (!empty($applicationEntity)) 
			{
				$this->_currentDocovaApp =  $this->_docova->DocovaApplication(null, $applicationEntity);
			}
		}
	}

	/**
	 * Set document object
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents|string $document
	 * @param \Docova\DocovaBundle\Entity\Libraries $library (used for documents in libraries & folders)
	 */
	public function setDocument($document, $library = null)
	{
		if (is_object($document))
		{
			if($document instanceof \Docova\DocovaBundle\ObjectModel\DocovaDocument){
				$this->_currentDocovaDocument = $document;
				return;
			}elseif ($document instanceof \Docova\DocovaBundle\Entity\Documents){
				$documentEntity = $document;
			}else{ 
				$documentEntity = $this->_em->getRepository('DocovaBundle:Documents')->find($document->getId());
			}
			
			if (empty($library)){
				$this->_currentDocovaDocument = $this->_docova->DocovaDocument($this->getCurrentApplication(), $document->getId(), $documentEntity );
			}else {
				$library = $this->_docova->DocovaApplication([], $library);
				$this->_currentDocovaDocument = $this->_docova->DocovaDocument($library, $document->getId(), '', $documentEntity);
			}
		}
		else {
			$document = $this->_em->getRepository('DocovaBundle:Documents')->find($document);
			if (!empty($document)) {
				$documentEntity = $document;
			}
			
			if (empty($library)) {
				$this->_currentDocovaDocument = $this->_docova->DocovaDocument($this->getCurrentApplication(), $document->getId(), '', $documentEntity);
			}
			else {
				$library = $this->_docova->DocovaApplication([], $library);
				$this->_currentDocovaDocument = $this->_docova->DocovaDocument($library, $document->getId(), '', $documentEntity);
			}
		}
		
	}

	/**
	 * Gets document object
	 * 
	 * @return DocovaDocument $document
	 */
	public function getDocument(){
		return $this->_currentDocovaDocument;
	}
	
	/**
	 * Get document attachments properties
	 * 
	 * @param number $output
	 * @return number|array
	 */
	public function getAttachments($output = 0)
	{
		if (empty($this->_currentDocovaDocument))
		{
			if ($output === 0) return 0;
			else return null;
		}
		
		$attachments = $this->_currentDocovaDocument->getAttachments();
		if ($output === 0) {
			return count($attachments);
		}
		elseif ($output === 1) {
			return $attachments;
		}
	}
	
	/**
	 * Set user object
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 */
	public function setUser($user)
	{
		$this->_user = $user;
	}
	
	/**
	 * Get user object
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserAccounts
	 */
	public function getUser()
	{
		return $this->_user;
	}
	
	/**
	 * Get DocovaSession object
	 * 
	 * @return DocovaSession
	 */
	public function getDocovaSessionObject()
	{
		$session = $this->_docova->DocovaSession();
		return $session;
	}
	
	/**
	 * Get global settings
	 * 
	 * @return \Docova\DocovaBundle\Entity\GlobalSettings|NULL
	 */
	public function getGlobalSettings()
	{
		$global_settings = $this->_em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		if (!empty($global_settings))
			return $global_settings[0];
		
		return null;
	}
	
	/**
	 * Is document new
	 * 
	 * @return boolean
	 */
	public function isNew()
	{
		if ( empty ($this->_currentDocovaDocument))
			return true;
		return empty($this->_currentDocovaDocument->getEntity());
	}
	
	/**
	 * Check if document workflow is started
	 * 
	 * @return boolean
	 */
	public function isWorkflowStarted()
	{
		if (!empty($this->_currentDocovaDocument))
		{
			return $this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->isWorkflowStarted($this->_currentDocovaDocument->getEntity()->getId());
		}
		return false;
	}
	
	/**
	 * Get first step pending assignee name
	 * 
	 * @return string
	 */
	public function getFirstPendingFor()
	{
		$assignee = '';
		if (!empty($this->_currentDocovaDocument))
		{
			$step = $this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getFirstPendingStep($this->_currentDocovaDocument->getEntity()->getId());
			if (!empty($step[0]))
			{
				$assignee = $step[0]->getAssignee()->getUserNameDnAbbreviated();
			}
		}
		
		return $assignee;
	}
	
	/**
	 * Get read/edit mode
	 * 
	 * @return string
	 */
	public function getCurrentMode()
	{
		if ($this->isNew())
			return 'edit';
		
		$read_url = $this->_router->generate('docova_readappdocument', array('docid' => $this->_currentDocovaDocument->id));
		if (false !== stripos($read_url, $_SERVER['PATH_INFO']))
			return 'read';
		elseif (false !== strpos($_SERVER['PATH_INFO'], '/wViewForm/'))
			return 'edit';
	}

	/**
	 * Fetch form name/alias
	 * 
	 * @return string
	 */
	public function getFormName(){
		if ( is_null($this->_currentDocovaDocument->getEntity()->getAppForm()))
			return "";
		$alias = $this->_currentDocovaDocument->getEntity()->getAppForm()->getFormAlias();
		if ( empty($alias))
			return $this->_currentDocovaDocument->getEntity()->getAppForm()->getFormName();
		else
			return $alias;
	}
	
	/**
	 * Fetch application form properties object
	 * 
	 * @param string $form
	 * @return \Docova\DocovaBundle\Entity\AppFormProperties|NULL
	 */
	public function getFormProperties($form)
	{
		if ($this->_currentDocovaDocument && $this->_currentDocovaDocument->getEntity()->getAppForm())
		{
			return $this->_currentDocovaDocument->getEntity()->getAppForm()->getFormProperties();
		}
		else {
			$form = $this->_em->getRepository('DocovaBundle:AppForms')->findByNameAlias($form, $this->getCurrentApplication()->appID);
			if (!empty($form)) {
				return $form->getFormProperties();
			}
		}
		return null;
	}
	
	/**
	 * Get a field/column value in the document
	 * 
	 * @param string $fieldName
	 * @return string
	 */
	public function getFieldValue($fieldName)
	{
		if (!empty($this->_currentDocovaDocument->getEntity()))
		{
			$myretval =  $this->_currentDocovaDocument->getField($fieldName);
			return $myretval;

			$value = $this->_em->getRepository('DocovaBundle:FormTextValues')->getFieldValue($this->_currentDocovaDocument->getEntity()->getId(), $fieldName);
			if (empty($value)) {
				$value = $this->_em->getRepository('DocovaBundle:FormDateTimeValues')->getFieldValue($this->_currentDocovaDocument->getEntity()->getId(), $fieldName);
				if (empty($value)) {
					$value = $this->_em->getRepository('DocovaBundle:FormNumericValues')->getFieldValue($this->_currentDocovaDocument->getEntity()->getId(), $fieldName);
					$value = empty($value) ? array() : $value;
					$groups = $this->_em->getRepository('DocovaBundle:FormGroupValues')->getFieldValue($this->_currentDocovaDocument->getEntity()->getId(), $fieldName);
					if (!empty($groups))
					{
						foreach ($groups as $g)
							$value[] = $g;
					}
					$groups = $g = null;
					if (empty($value)) {
						$value = $this->_em->getRepository('DocovaBundle:FormNumericValues')->getFieldValue($this->_currentDocovaDocument->getEntity()->getId(), $fieldName);
					}
				}
			}
			if (!empty($value)) {
				return $value;
			}
		}
		return '';
	}
	
	/**
	 * Set form field value
	 * 
	 * @param string $field_name
	 * @param string $value
	 * @return boolean
	 */
	public function setFieldValue($field_name, $value)
	{
		if (!empty($this->_currentDocovaDocument->getEntity()) && !empty($field_name) && !empty($value));
		{
			$form = $this->_currentDocovaDocument->getEntity()->getAppForm();
			if (!empty($form))
			{
				$found = null;
				$elements = $form->getElements();
				foreach ($elements as $elem) {
					if ($elem->getFieldName() == $field_name) {
						$found = $elem;
						break;
					}
				}
				if ($found !== null) 
				{
					switch ($found->getFieldType())
					{
						case 0:
						case 2:
							$element = new FormTextValues();
							$element->setSummaryValue(substr($value, 0, 450));
							break;
						case 1:
							$element = new FormDateTimeValues();
							try {
								$value = new \DateTime($value);
							} catch (\Exception $e) {
								return false;
							}
							break;
						case 3:
							$element = new FormNameValues();
							try {
								$identity = $this->findUser($value);
								if (empty($identity))
									$identity = $this->findGroup($value);
								if (empty($identity))
									$identity = $this->createInactiveUser($value);
								if (!empty($identity))
								{
									if ($identity instanceof \Docova\DocovaBundle\Entity\UserAccounts)
										$element = new FormNameValues();
									elseif ($identity instanceof \Docova\DocovaBundle\Entity\UserRoles)
										$element = new FormGroupValues();
									else 
										return false;
									$value = $identity;
								}
								else 
									return false;
							} catch (\Exception $e) {
								return false;
							}
							break;
						case 4:
							$element = new FormNumericValues();
							$value = floatval($value);
							break;
					}
					$element->setField($found);
					$element->setFieldValue($value);
					$element->setDocument($this->_currentDocovaDocument->getEntity());
					$this->_em->persist($element);
					$this->_em->flush();
					return true;
				}
			}
			else {
			
			}
		}
		return false;
	}
	
	/**
	 * Get array of field values in application
	 * 
	 * @param string $fieldname
	 * @param string $application
	 * @return array
	 */
	public function getAllFieldValues($fieldname, $application)
	{
		$output = array();
		$result = $this->_em->getRepository('DocovaBundle:DesignElements')->getAppField($fieldname, $application);
		if (!empty($result)) 
		{
			foreach ($result as $field) {
				$entity = 'DocovaBundle:FormTextValues';
				if ($field['fieldType'] == 1) {
					$entity = 'DocovaBundle:FormDateTimeValues';
				}
				elseif ($field['fieldType'] == 3) {
					$entity = 'DocovaBundle:FormNameValues';
				}
				elseif ($field['fieldType'] == 4) {
					$entity = 'DocovaBundle:FormNumericValues';
				}
				elseif ($field['fieldType'] == 5) {
					$entity = 'DocovaBundle:AttachmentsDetails';
				}
				
				$values = $this->_em->getRepository($entity)->getAppFieldValues($field['id']);
				if ($field['fieldType'] == 3) {
					$values = empty($values) ? array() : $values;
					$groups = $this->_em->getRepository('DocovaBundle:FormGroupValues')->getAppFieldValues($field['id']);
					if (!empty($groups))
					{
						foreach ($groups as $g) {
							$values[] = $g;
						}
					}
					$groups = $g = null;
				}
				if (!empty($values)) 
				{
					foreach ($values as $v) {
						$output[] = $v;
					}
				}
			}
		}
		return $output;
	}
	
	/**
	 * Get current document unique ID
	 * 
	 * @return string
	 */
	public function getDocumentUniqueId()
	{
		return $this->_currentDocovaDocument->getEntity()->getId();
	}
	
	/**
	 * Check if field exists in a form
	 * 
	 * @param string $field_name
	 * @return boolean
	 */
	public function fieldExists($field_name)
	{
		if (!empty($field_name) && !empty($this->_currentDocovaDocument->getEntity())) 
		{
			$form = $this->_currentDocovaDocument->getEntity()->getAppForm();
			if (!empty($form))
			{
				$elements = $form->getElements();
				foreach ($elements as $elem) {
					if ($elem->getFieldName() == $field_name) {
						return true;
					}
				}
			}
			else {
				
			}
		}
		return false;
	}
	
	/**
	 * Instantiate a new DocovaApplication object
	 * 
	 * @param array $options
	 * @return \Docova\DocovaBundle\ObjectModel\DocovaApplication
	 */
	public function getCurrentApplication(){
		return $this->_currentDocovaApp;
	}
	
	/**
	 * Get field value from profile document record
	 * 
	 * @param string $profile
	 * @param string $field
	 * @param string $key
	 * @return string|number|\DateTime|NULL
	 */
	public function getProfileField($profile, $field, $key = null)
	{
		$application = $this->_docova->DocovaApplication();
		$value = $application->getProfileFields($profile, $field, $key);
		if (!empty($value[$field]))
			return $value[$field];

		return null;
	}
	
	/**
	 * Set field value record for profile document
	 * 
	 * @param string $profile_name
	 * @param string $field
	 * @param mixed $value
	 * @param mixed $key
	 * @return boolean
	 */
	public function setProfileField($profile_name, $field, $value, $key = null)
	{
		$applicatoin = $this->_docova->DocovaApplication();
		return $applicatoin->setProfileField($profile_name, $field, $value, $key);
	}
	
	/**
	 * Add or concatenate array of values according to their type
	 * 
	 * @param array $values_array
	 * @return string|array
	 */
	public function concatOrAdd($values_array)
	{
		if (empty($values_array)) {
			return '';
		}
		
		$output = '';
		$max = 0;
		foreach ($values_array as $value)
		{
			if (is_array($value)) {
				$output = array();
				$max = count($value) > $max ? count($value) : $max;
			}
		}
		
		if (is_array($output)) {
			$output = array_fill(0, $max, array('docid' => null, 'fvalue' => null));
			foreach ($values_array as $value)
			{
				if (is_array($value)) {
					$this->findAndConcat($value, $output);
				}
				else {
					for ($x = 0; $x < $max; $x++) {
						if (is_string($value)) {
							$output[$x]['fvalue'] .= $value;
						}
						else {
							$output[$x]['fvalue'] += $value;
						}
					}
				}
			}
		}
		else {
			$isnumeric = true;
			for($i=0; $i<count($values_array); $i++) {
				if(is_string($values_array[$i])) {
					$isnumeric = false;
				}
			}
			
			if($isnumeric){
				$output = 0;
			}else{
				$output = '';
			}
			for($i=0; $i<count($values_array); ++$i){
				if($isnumeric){
					$output += $values_array[$i];
				}else{
					$output .= $values_array[$i];
				}
			}
		}
		return $output;
	}
	
	/**
	 * Returns the values of a view column
	 * 
	 * @param string $view
	 * @param integer $column
	 * @return array
	 */
	public function dbColumn($view, $column)
	{
		$view = $this->_em->getRepository('DocovaBundle:AppViews')->findByNameAlias($view, $this->_currentDocovaApp->getEntity()->getId());
		$column--;
		if (!empty($view))
		{
			$view_handler = new ViewManipulation($this->_docova, $this->_currentDocovaApp->getEntity());
			$view_name = str_replace('-', '', $view->getId());
			if (true === $view_handler->viewExists($view_name)) 
			{
				$view_columns = $view_handler->fetchViewColumns($view_name);
				if (count($view_columns) < $column)
					return array();

				$sorted_column = array();
				$perspective = new \DOMDocument();
				$perspective->loadXML($view->getViewPerspective());
				for ($x = 0; $x < $perspective->getElementsByTagName('xmlNodeName')->length; $x++)
				{
					if ($perspective->getElementsByTagName('sortOrder')->item($x)->nodeValue != 'none')
					{
						$sorted_column[$perspective->getElementsByTagName('xmlNodeName')->item($x)->nodeValue] = strtolower($perspective->getElementsByTagName('sortOrder')->item($x)->nodeValue) == 'descending' ? 'DESC' : 'ASC';
					}
				}
						
				$column_values = $view_handler->getColumnValues($view_name, $view_columns[$column], array(), $sorted_column);
				if (!empty($column_values)) 
				{
					//check if the $column is categorized.if so we remove duplicates 
					$issorted = $perspective->getElementsByTagName('isCategorized')->item($column)->nodeValue;
					
					if ( $issorted )
						return array_unique($column_values);
					else
						return $column_values;
				}
			}
		}
		return array();
	}
	
	/**
	 * Returns view column or field values that correspond to matched keys in a sorted view column.
	 * 
	 * @param string $view
	 * @param string|string[] $key
	 * @param integer|string $columnorfield
	 * @param string $keywords
	 * @return string
	 */
	public function dbLookup($view, $key, $columnorfield, $keywords = null, $delimiter = ';')
	{	    
		$output = '';		
		$keyarray = (is_array($key) ? $key : [$key]);
		$keywords = !empty($keywords) ? strtoupper($keywords) : null;
		
		if (empty($key)) {
		    return (strpos($keywords, '[FAILSILENT]') !== false ? $output : 'Invalid KEY entry for DbLookup');
		}
		
		
		$view = $this->_em->getRepository('DocovaBundle:AppViews')->findByNameAlias($view, $this->_currentDocovaApp->getEntity()->getId());
	

		if (!empty($view))
		{
			$view_hanlder = new ViewManipulation($this->_docova, $this->_currentDocovaApp->getEntity());
			$vid = $view->getId();
			$vid = str_replace('-', '', $vid);


			//todo :- need to fix this later...if we are given an array of keys then we should do a where firstSortedColumn = key
			if (true === $view_hanlder->viewExists($vid)) 
			{
				$sorted_column = null;
				$ordered_columns = array();
				$perspective = new \DOMDocument();
				$perspective->loadXML($view->getViewPerspective());
				for ($x = 0; $x < $perspective->getElementsByTagName('xmlNodeName')->length; $x++)
				{
					if ( $perspective->getElementsByTagName('sortOrder')->item($x)->nodeValue != 'none')
					{
						$sorted_column[] = $perspective->getElementsByTagName('xmlNodeName')->item($x)->nodeValue;
					}
					
					$ordered_columns[$perspective->getElementsByTagName('xmlNodeName')->item($x)->nodeValue] = strtolower($perspective->getElementsByTagName('sortOrder')->item($x)->nodeValue) == 'descending' ? 'DESC' : 'ASC';
				}
				if (empty($sorted_column))
				{
					return (strpos($keywords, '[FAILSILENT]') !== false ? $output : 'No sorted column found for DbLookup');
				}


				array_splice($sorted_column, count($keyarray) );

    			if (!is_numeric($columnorfield))
    			{    
					$values = $view_hanlder->getColumnValues($vid, 'Document_Id',  array_combine($sorted_column , $keyarray), $ordered_columns);
					if (!empty($values))
					{
						if(strpos($keywords, '[RETURNDOCUMENTUNIQUEID]') !== false){
							$output = $values[0];
						}else{
							$refDocument = $this->_em->getReference('DocovaBundle:Documents', $values[0]);
							$tdoc = $this->_docova->DocovaDocument($this->getCurrentApplication(), $refDocument->getId(), '', $refDocument);
							$output = $this->fetchStringValue($tdoc->getField($columnorfield));
							//$output = $this->fetchStringValue($this->getFieldValue($columnorfield));
						}
					}
					return $output;
				}else {
					if ($columnorfield > $perspective->getElementsByTagName('xmlNodeName')->length)
						return $output;
					$columnorfield = $perspective->getElementsByTagName('xmlNodeName')->item($columnorfield-1)->nodeValue;
				}
    			if (is_numeric($columnorfield))
     				return $output;

				if(strpos($keywords, '[RETURNDOCUMENTUNIQUEID]') !== false){
					$values = $view_hanlder->getColumnValues($vid, 'Document_Id',  array_combine($sorted_column , $keyarray), $ordered_columns);					
				}else{



					$values = $view_hanlder->getColumnValues($vid, $columnorfield, array_combine($sorted_column , $keyarray), $ordered_columns);
				}
    			if (count($values) === 1)
    				$output = $this->fetchStringValue($values[0]);
    			else {
    				$len = count($values);
    				for ($x = 0; $x < $len; $x++) {
    					$values[$x] = $this->fetchStringValue($values[$x]);
    				}
    				$output = implode($delimiter, $values);
    			}
   			}
  		}
  		return html_entity_decode($output, ENT_QUOTES | ENT_XML1, "UTF-8");
 	}
 	
 	/**
 	 * Calculate function on column values in a view
 	 * 
 	 * @param string $view
 	 * @param string $function
 	 * @param string|integer $columnorfield
 	 * @param array|object $criteria
 	 * @return number
 	 */
 	public function dbCalculate($view, $function, $columnorfield, $criteria = [])
 	{
 		$output = 0;
 		$view = $this->_em->getRepository('DocovaBundle:AppViews')->findByNameAlias($view, $this->_currentDocovaApp->getEntity()->getId());
 		if (!empty($view))
 		{
 			$view_hanlder = new ViewManipulation($this->_docova, $this->_currentDocovaApp->getEntity());
 			$vid = $view->getId();
 			$vid = str_replace('-', '', $vid);
 		
 			if (true === $view_hanlder->viewExists($vid))
 			{
 				$perspective = new \DOMDocument();
 				$perspective->loadXML($view->getViewPerspective());
   				for ($x = 0; $x < $perspective->getElementsByTagName('xmlNodeName')->length; $x++)
   				{
   					if (!is_numeric($columnorfield))
   					{
	   					if (strtolower($perspective->getElementsByTagName('title')->item($x)->nodeValue) == strtolower($columnorfield))
	   					{
	   						$columnorfield = $perspective->getElementsByTagName('xmlNodeName')->item($x)->nodeValue;
	   						break;
	   					}
   					}
   					else {
   						if ($x+1 == $columnorfield) {
   							$columnorfield = $perspective->getElementsByTagName('xmlNodeName')->item($x)->nodeValue;
   							break;
   						}
   					}
   				}
   				
   				$where = [];
   				if (!empty($criteria))
   				{
   					if (is_object($criteria)) {
   						$criteria = get_object_vars($criteria);
   					}
   					foreach ($criteria as $field => $value)
   					{
   						for ($x = 0; $x < $perspective->getElementsByTagName('xmlNodeName')->length; $x++)
   						{
   							if (strtolower($field) == strtolower($perspective->getElementsByTagName('title')->item($x)->nodeValue))
   							{
   								$where[$perspective->getElementsByTagName('xmlNodeName')->item($x)->nodeValue] = $value;
   								break;
   							}
   						}
   					}
   				}
   				
   				$values = $view_hanlder->getColumnValues($vid, $columnorfield, $where);
   				if (empty($values)) {
   					return $output;
   				}
   				
   				switch (strtolower($function)) {
   					case 'sum':
   					case 'total':
   						$output = array_sum($values);
   						break;
   					case 'avg':
   					case 'average':
   						$output = round(array_sum($values)/count($values));
   						break;
   					case 'count':
   						$output = count($values);
   						break;
   					case 'max':
   						$output = max($values);
   						break;
   					case 'min':
   						$output = min($values);
   						break;
   					default:
   						$output = 0;
   				}
 			}
 		}
 		return $output;
 	}
 
	 /**
	  * Send mail base on current document
	  * 
	  * @param string[] $sendTo
	  * @param string[] $copyTo
	  * @param string $blindCopyTo
	  * @param string $subject
	  * @param string[] $remark
	  * @param string $bodyFields
	  * @param boolean $include_links
	  * @return boolean
	  */
	 public function sendMailTo($sendTo, $copyTo, $blindCopyTo, $subject, $remark = '', $bodyFields = '', $include_links = false)
	 {
	 	$result = false;
	 	
		if (!empty($this->_currentDocovaDocument))
	 	{
	  		$msg = '';
			$remark = !empty($remark) ? "\r\n".(is_array($remark) ? implode("\r\n", $remark) : $remark) : '';
			$msg .= $remark;
	
			if (!empty($bodyFields)) {
				$bodyFields = str_replace('"', '', $bodyFields);
				$bodyFields = explode(':', $bodyFields);
				for ($x = 0; $x < count($bodyFields); $x++)
				{
					$value = $this->_currentDocovaDocument->getField($bodyFields[$x]);
					$msg .= "\r\n" . $value;
				}
			}
			
			if ($include_links === true)
			{
				$url = $this->_currentDocovaDocument->getURL(['fullurl'=>true, 'mode'=>'window']);
				$msg .= "\r\n\r\n";
				$msg .= "<a target=\"_blank\" href=\"$url\" >Linked Document</a>";
				$msg .= "\r\n";
			}
			
			
			$send_to = (empty($sendTo) ? [] : (is_array($sendTo) ? $sendTo : [$sendTo]));
			$temp_send_to = [];
			foreach ($send_to as $index => $user)
			{
				//-- check if a valid email address on its own
				if (false !== ($tempemail = filter_var(trim($user), FILTER_VALIDATE_EMAIL))) {
					array_push($temp_send_to, $tempemail);
					//-- check if it is a group with members
				}else if (false !== ($groupmembers = $this->fetchGroupMembers($user))){
					foreach($groupmembers as $tempuser){
						array_push($temp_send_to, $tempuser->getUserMail());
					}
					//-- try and look up the user from internal list as well as ldap, but don't create if not found
				}else if (false !== ($tempuser = $this->findUserAndCreate($user))) {
					//-- check if an object was returned
					if(is_object($tempuser)){
						array_push($temp_send_to, $tempuser->getUserMail());
						//-- check if an ldap data array was returned
					}else if(is_array($tempuser) && array_key_exists("mail", $tempuser)){
						array_push($temp_send_to, $tempuser["mail"]);
					}
				}
			}
			$send_to = $temp_send_to;
			
			$copy_to = (empty($copyTo) ? [] : (is_array($copyTo) ? $copyTo : [$copyTo]));
			$temp_copy_to = [];
			foreach ($copy_to as $index => $user)
			{
				//-- check if a valid email address on its own
				if (false !== ($tempemail = filter_var(trim($user), FILTER_VALIDATE_EMAIL))) {
					array_push($temp_copy_to, $tempemail);
					//-- check if it is a group with members
				}else if (false !== ($groupmembers = $this->fetchGroupMembers($user))){
					foreach($groupmembers as $tempuser){
						array_push($temp_copy_to, $tempuser->getUserMail());
					}
					//-- try and look up the user from internal list as well as ldap, but don't create if not found
				}else if (false !== ($tempuser = $this->findUserAndCreate($user))) {
					//-- check if an object was returned
					if(is_object($tempuser)){
						array_push($temp_copy_to, $tempuser->getUserMail());
						//-- check if an ldap data array was returned
					}else if(is_array($tempuser) && array_key_exists("mail", $tempuser)){
						array_push($temp_copy_to, $tempuser["mail"]);
					}
				}
			}
			$copy_to = $temp_copy_to;
			
			$bcc = (empty($blindCopyTo) ? [] : (is_array($blindCopyTo) ? $blindCopyTo : [$blindCopyTo]));
			$temp_bcc = [];
			foreach ($bcc as $index => $user)
			{
				//-- check if a valid email address on its own
				if (false !== ($tempemail = filter_var(trim($user), FILTER_VALIDATE_EMAIL))) {
					array_push($temp_bcc, $tempemail);
					//-- check if it is a group with members
				}else if (false !== ($groupmembers = $this->fetchGroupMembers($user))){
					foreach($groupmembers as $tempuser){
						array_push($temp_bcc, $tempuser->getUserMail());
					}
					//-- try and look up the user from internal list as well as ldap, but don't create if not found
				}else if (false !== ($tempuser = $this->findUserAndCreate($user))) {
					//-- check if an object was returned
					if(is_object($tempuser)){
						array_push($temp_bcc, $tempuser->getUserMail());
						//-- check if an ldap data array was returned
					}else if(is_array($tempuser) && array_key_exists("mail", $tempuser)){
						array_push($temp_bcc, $tempuser["mail"]);
					}
				}
			}
			$bcc = $temp_bcc;			
			
			if (false == (empty($send_to) && empty($copy_to) && empty($bcc)))
			{				
				try {
					$failur = null;

					$user = $this->getUser();
					$fromname =  ($user->getUserProfile() ?  $user->getUserProfile()->getDisplayName() : $user->getUserNameDnAbbreviated());
					
					$message = new \Swift_Message();
					$message->setSubject($subject)
						->setTo($send_to)
						->setCc($copy_to)
						->setBcc($bcc)
						->setFrom(array($user->getUserMail() => $fromname))
						->setBody(nl2br($msg), 'text/html');					
					
					$mailer = $this->_container->get('mailer');
					$tempres = $mailer->send($message, $failur);
					unset($user);
				}
				catch (\Exception $e) {
					$failur = $e->getMessage();
				}
					
				if (empty($failur)) {
					$result = true;
				}
							
			}
			
				
		}
		
		return $result;
	}
	
	/**
	 * Is current document a response document
	 * 
	 * @return boolean
	 */
	public function isResponse()
	{
		if (!empty($this->_currentDocovaDocument))
		{
			$res = $this->_em->getRepository('DocovaBundle:RelatedDocuments')->isResponse($this->_currentDocovaDocument->id);
			if (!empty($res))
				return true;
		}
		return false;
	}
	
	/**
	 * Get a document type flag value
	 * 
	 * @param string $doctype
	 * @return number|NULL
	 */
	public function getDocTypeFlag($doctype)
	{
		$doctype = $this->_em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('id' => $doctype, 'Trash' => false));
		if (!empty($doctype)) {
			return $doctype->getFlags();
		}
		return null;
	}

	/**
	 * Get ACL roles
	 *
	 * @param  mixed $userobjorstring
	 * @return array
	 */
	public function getAclRoles($userobjorstring = null)
	{
		$results = array();
	
		if (empty($userobjorstring)){
			$user = $this->security_token->getToken()->getUser();
		}else {
			if(gettype($userobjorstring) == 'string'){
				$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $userobjorstring, 'Trash' => false));
				if (empty($user)){
				    $user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $userobjorstring, 'Trash' => false));
				}
			}else{
				$user = $userobjorstring;
			}
		}
		if (empty($user)){
			return $results;
		}
	
		$appobj = $this->getCurrentApplication();
	
		$aclmatchfound = FALSE;
		$acl_property = $this->_em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $appobj->appID, 'userObject' => $user->getId()));
		if (!empty($acl_property)){
			//-- user found so retrieve roles assigned specifically to that user
			$aclmatchfound = TRUE;
			$temp_roles = $this->_em->getRepository('DocovaBundle:UserRoles')->getAppUserGroups($appobj->appID, $user->getId(), false);
			if(!empty($temp_roles) && count($temp_roles) > 0){
				foreach ($temp_roles as $temp_role){
					$results[] = $temp_role["displayName"];
				}
			}
		}else{
			//-- look at groups if user is not listed explicitly
			$groups = $user->getUserRoles();
			if (!empty($groups) && $groups->count() > 0)
			{
				foreach ($groups as $g)
				{
				    $acl_property = $this->_em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $appobj->appID, 'groupObject' => $g->getId()));
					if (!empty($acl_property)){
						$gname = $g->getGroupName();
						$gname = (empty($gname) ? $g->getDisplayName() : $gname);
						//-- do not retrieve roles for default entry before we check every other group
						if($gname != "User"){
							$aclmatchfound = TRUE;
							$temp_roles = $this->_em->getRepository('DocovaBundle:UserRoles')->getAppRoles($appobj->appID, $gname, false);
							if(!empty($temp_roles) && count($temp_roles) > 0){
								foreach ($temp_roles as $temp_role){
									$results[] = $temp_role["displayName"];
								}
							}
						}
					}
				}
			}
		}
	
		if(!$aclmatchfound){
			//--retrieve roles for default access level
		    $temp_roles = $this->_em->getRepository('DocovaBundle:UserRoles')->getAppRoles($appobj->appID, "User", false);
			if(!empty($temp_roles) && count($temp_roles) > 0){
				foreach ($temp_roles as $temp_role){
					$results[] = $temp_role["displayName"];
				}
			}
		}
			
		return array_unique($results);
	}
	
	/**
	 * Get folder info
	 * 
	 * @param string $folderid
	 * @param string $request_type
	 * @return string
	 */
	public function getFolderInfo($folderid, $request_type)
	{
		$folder = $this->_em->getRepository('DocovaBundle:Folders')->find($folderid);
		if (empty($folder))
			return '';
		
		switch ($request_type)
		{
			case 'N':
				return $folder->getFolderName();
			case 'P':
				return $folder->getFolderPath();
			default:
				return '';
		}
	}
	
	/**
	 * Get user DN by user id or username
	 * 
	 * @param string $user
	 * @return string
	 */
	public function fetchUserDn($user)
	{
		$user_obj = $this->_em->getRepository('DocovaBundle:UserAccounts')->find($user);
		if (empty($user_obj)) {
			$user_obj = $this->findUser($user);
		}
		
		if (empty($user_obj) || $user_obj->getTrash()) {
			return '';
		}
		
		return $user_obj->getUserNameDn();
	}
	
	public function getDocovaObject(){
	    return $this->_docova;
	}
	
	/**
	 * Concat or add array of values
	 * 
	 * @param array $array
	 * @param array $output
	 */
	private function findAndConcat($array, &$output)
	{
		$found = false;
		$len = count($output);
		$count = count($array);
		for ($i = 0; $i < $count; $i++)
		{
			for ($x = 0; $x < $len; $x++) {
				if ($output[$x]['docid'] == $array[$i]['docid']) {
					$found = $x;
					break;
				}
			}
			if ($found !== false) 
			{
				if (is_string($array[$i]['fvalue'])) {
					$output[$found]['fvalue'] .= $array[$i]['fvalue'];
				}
				else {
					$output[$found]['fvalue'] += $array[$i]['fvalue'];
				}
			}
			else {
				for ($x = 0; $x < $len; $x++) {
					if (empty($output[$x]['docid'])) {
						$output[$x]['docid'] = $array[$i]['docid'];
						$output[$x]['fvalue'] = $array[$i]['fvalue'];
						break;
					}
				}
			}
		}
	}
	
	/**
	 * Find user object base on username or abbreviated name
	 * 
	 * @param $username
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUser($username)
	{
		$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $username));
		if (empty($user))
		{
			$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $username));
		}
		if (!empty($user)) {
			return $user;
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
		$group = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $groupname));
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
	 * @return UserAccounts
	 */
	private function createInactiveUser($userName)
	{
	    $details = explode(' ', $userName);
	    $inactive_user = new UserAccounts();
	    $inactive_user->setTrash(true);
	    $inactive_user->setUserMail(implode('', $details).'@inactive.user');
	    $inactive_user->setUsername($userName);
	    $docova_certifier_name = $this->global_settings->getDocovaBaseDn() ? $this->global_settings->getDocovaBaseDn() : "/DOCOVA";
	    if(substr($docova_certifier_name, 0, 1) == "/" || substr($docova_certifier_name, 0, 1) == "\\"){
	        $docova_certifier_name = substr($docova_certifier_name, 1);
	    }
	    $unameAbbr = trim($userName).'/'.$docova_certifier_name;
	    $unameCanon = 'CN='.trim($userName).",O=".$docova_certifier_name;
	    $inactive_user->setUserNameDnAbbreviated($unameAbbr);
	    $inactive_user->setUserNameDn($unameCanon);
	    $this->_em->persist($inactive_user);
	    
	    $inactive_profile = new UserProfile();
	    $inactive_profile->setAccountType(true);
	    $inactive_profile->setFirstName($details[0]);
	    $inactive_profile->setLastName(!empty($details[1]) ? $details[1] : $details[0]);
	    $inactive_profile->setDisplayName($userName);
	    $inactive_profile->setUserMailSystem('O');
	    $inactive_profile->setMailServerURL('inactive.user');
	    $inactive_profile->setUser($inactive_user);
	    $this->_em->persist($inactive_profile);
	    $this->_em->flush();
	    
	    return $inactive_user;
	}
	
	
	/**
	 * Fetch group members
	 *
	 * @param string $groupname
	 * @return \Doctrine\Common\Collections\ArrayCollection|boolean
	 */
	private function fetchGroupMembers($groupname)
	{
		$docovaonly = (false === strpos($groupname, '/DOCOVA') ? false : true);
		$name = str_replace('/DOCOVA', '', $groupname);
		$role = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $name, 'groupType' => false));
		if (!empty($role)) {
			return $role->getRoleUsers();
		}
		if(!$docovaonly){
		    $role = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $name, 'groupType' => true));
			if (!empty($role)) {
				return $role->getRoleUsers();
			}
		}
	
		return false;
	}
	
	/**
	 * Find a user info in Database
	 *
	 * @param string $username
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUserAndCreate($username)
	{
		$tmpAbbName=$this->getUserAbbreviatedName($username);
		$user_obj = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated'=>$tmpAbbName));
		$nameparts = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $tmpAbbName);		
		if(empty($user_obj) && !(is_array($nameparts) && count($nameparts)>1)){
		    $result = $this->_em->getRepository("DocovaBundle:UserAccounts")->createQueryBuilder('u')
				->where('u.userNameDnAbbreviated LIKE :dnpattern')
				->setParameter('dnpattern', $this->escapeValue($username, "SQL_LIKE_SETPARAM").'/%')
				->getQuery()
				->getResult();
			
			if(!empty($result)){
				//-- check if only a single result found
				if(count($result) == 1){
					$user_obj = $result[0];
				}else{
					//-- stop if we got more than one result since we don't know which one to return
					return false;
				}
			}
		}
		
		return $user_obj;
	}	
	
	/**
	 * Fetch a valid string value from different objects
	 * 
	 * @param object $value
	 * @return string
	 */
	private function fetchStringValue($value)
	{
		if (!is_object($value))
			return $value;
		
		if ($value instanceof \DateTime)
		{
			$tmp_date = new \DateTime($value->format('m/d/Y'));
			$tmp_date->setTime(0,0,0);
			if ($tmp_date == $value) {
				$value = $value->format('m/d/Y');
			}
			else {
				$value = $value->format('m/d/Y H:i:s');
			}
		}
		elseif ($value instanceof \Docova\DocovaBundle\Entity\UserAccounts) {
			$value = $value->getUserNameDnAbbreviated();
		}
		elseif ($value instanceof \Docova\DocovaBundle\Entity\UserRoles) {
			$value = $value->getDisplayName();
		}
		return $value;
	}
	
	/**
	 * @param: string $userDnName e.g CN=DV Punia,O=DLI
	 * @return: string abbreviated name e.g DV Punia/DLI
	 */
	private function getUserAbbreviatedName($userDnName)
	{
	    try {
	        $strAbbDnName="";
	        $arrAbbName = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $userDnName);
	        if(count($arrAbbName) < 2){
	            $arrAbbName = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $userDnName);
	        }
	        
	        //create abbreviated name
	        foreach ($arrAbbName as $value){
	            if(trim($value) != ""){
	                $namepart = explode("=", $value);
	                $strAbbDnName .= (count($namepart) > 1 ? trim($namepart[1]) : trim($namepart[0]))."/";
	            }
	        }
	        //remove last "/"
	        $strAbbDnName=rtrim($strAbbDnName,"/");
	        
	    } catch (\Exception $e) {
	        //$this->log->error("Bad name found in Ldap (user_dn): ". $user_dn[0]);
	        var_dump("UserProvider::getUserAbbreviatedName() exception".$e->getMessage());
	    }
	    
	    
	    return $strAbbDnName;
	}
	
	
	/**
	 * escapeValue
	 * @param: $valuetoescape string
	 * @param: $escapetype string - "SQL" or "SQL_LIKE" or "SQL_LIKE_SETPARAM"
	 * @return: string
	 */
	private function escapeValue($valuetoescape, $escapetype = ""){
	    $output = $valuetoescape;
	    
	    if($escapetype == "SQL"){
	        $output = str_replace("\\", "\\\\", $output); //-- escape a single back slash into two
	    }else if($escapetype == "SQL_LIKE"){
	        $output = str_replace("\\", "\\\\\\\\", $output); //-- escape a single back slash into four
	    }else if($escapetype == "SQL_LIKE_SETPARAM"){
	        $output = str_replace("\\", "\\\\", $output); //-- escape a single back slash into four
	    }
	    
	    return $output;
	}
}