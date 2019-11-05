<?php

namespace Docova\DocovaBundle\ObjectModel;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Docova\DocovaBundle\Entity\FormDateTimeValues;
use Docova\DocovaBundle\Entity\FormNumericValues;
use Docova\DocovaBundle\Entity\FormTextValues;
use Docova\DocovaBundle\Entity\TrashedLogs;
use Docova\DocovaBundle\Entity\Documents;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Docova\DocovaBundle\Extensions\MiscFunctions;
use Docova\DocovaBundle\Entity\DocumentsLog;
use Docova\DocovaBundle\Entity\FormNameValues;
use Docova\DocovaBundle\Entity\FormGroupValues;
use Docova\DocovaBundle\Entity\DesignElements;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserProfile;
use Docova\DocovaBundle\Entity\AttachmentsDetails;
use Docova\DocovaBundle\Entity\RelatedDocuments;

/**
 * Class for interaction with DOCOVA documents
 * Typically accessed from DocovaApplication.getDocument() or various DocovaView methods.
 * @author javad_rahimi
 *        
 */
class DocovaDocument 
{
	private $_em;
	private $_router;
	private $_settings;
	private $_document = null;
	private $_form = null;
	private $_docroot;
	private $_docova;
	private $unid;
	private $attachmentBuffer = array();
	private $allfieldsretrieved = false;
	public $id;
	public $children = array();
	public $isModified = false;
	public $isNewDocument = false;
	public $isProfile = false;
	public $isResponse = false;
	public $doComputeWithForm = false;
	public $isValid = false;
	public $items = array();
	public $fieldBuffer = array();
	public $parentApp = null;
	public $parentDoc = null;
	public $parentView = null;
	public $parentFolder = null;
	public $profileKey = null;
	public $columnValues = null;
	public $responses = array();
	public $data_table_info = array();
	
	public function __construct($parentObj, $unid = null, $document_root = '', $document_entity = null, Docova $docova_obj = null)
	{
		if (!empty($docova_obj)) {
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
				throw new \Exception('Oops! DocovaDocument construction failed. Entity Manager/Router not available.');
			}
		}
		$this->_docroot = $document_root;
		$gs = $this->_em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->_settings = $gs[0];
		$gs = null;
		
		
		if (empty($parentObj))
			throw new \Exception('Oops! Construction failed due to missed Parent Object.');
		else {
			if ($parentObj instanceof DocovaApplication)
				$this->parentApp = $parentObj;
			elseif ($parentObj instanceof DocovaView) {
				$this->parentApp = $parentObj->parentApp;
				$this->parentView = $parentObj;
			}
			elseif ($parentObj instanceof DocovaDocument) {
				$this->parentApp = $parentObj->parentApp;
				$this->parentView = $parentObj->parentView;
				$this->parentDoc = $parentObj;
			}
			
			if (empty($unid)) {
				$this->isNewDocument = true;
				$this->isModified = true;
				$miscfunctions = new MiscFunctions();
				$this->unid = $miscfunctions->generateGuid();
				$this->id = $this->unid;
			}
			else {
				if ($this->parentApp->isApp === true){
					if ( !is_null($document_entity)){
						$this->_document = $document_entity;
					}else{
						$this->_document = $this->_em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $unid, 'application' => $this->parentApp->appID));
					}
				}elseif ($this->parentApp->appType == 'L'){
					$this->_document = $this->_em->getRepository('DocovaBundle:Documents')->getFolderDocument($unid);
				}
				if (empty($this->_document)) 
				{
					//throw new \Exception('Contructoin Failed - No document source is found!');
					return;
				}
			}
			
			if (!empty($this->_document))
			{
				$this->id = $this->_document->getId();
				$this->unid = $this->_document->getId();
				$this->isModified = $this->isNewDocument = false;
				$this->isProfile = trim($this->_document->getProfileName()) != '' ? true : false;
				$this->_form =$this->_document->getAppForm();
				if (!empty($this->parentView)) 
				{
					$view_handler = new ViewManipulation($this->_docova, $this->parentApp->appID);
					$view_id = str_replace('-', '', $this->parentView->viewid);
					if ($view_handler->viewExists($view_id))
					{
						$columns = $view_handler->fetchViewColumns($view_id);
						$values = $view_handler->getColumnValues($view_id, $columns, array('Document_Id' => $this->unid));
						if (!empty($values))
						{
							$this->columnValues = $values;
						}
					}
				}
			}
		}
	}
	
	public function __set($name, $value)
	{
		if (!property_exists($this, $name)) {
			throw new \OutOfBoundsException('Unspecified property "'.$name.'" via __set');
		}
		
		$this->$name = $value;
	}
	
	public function __get($name)
	{
		if (!property_exists($this, $name)) {
			throw new \OutOfBoundsException('Unspecified property "'.$name.'" via __get');
		}
		
		return $this->$name;
	}

	public function getEntity()
	{
		return $this->_document;
	}
	
	public function getFormEntity()
	{
	    return $this->_form;
	}

	public function getDocStatus()
	{
		return $this->_document->getDocStatus();
	}

	/**
	 * Create Date
	 *
	 **/

	public function getCreated()
	{
		return $this->_document->getDateCreated();
	}
	
	/**
	 * Get id
	 * 
	 * @return NULL|string
	 */
	public function getId()
	{
		return $this->unid;
	}
	
	public function appendItemValue($fieldname, $value = null)
	{
		//@TODO: logic should be applied
	}
	
	/**
	 * Copies items from the document to another target document
	 * 
	 * @param DocovaDocument $destination
	 * @return boolean
	 */
	public function copyAllItems($destination)
	{
		$result = false;
		$sourceFields = array();
		if (empty($destination) || !($destination instanceof DocovaDocument)) 
		{
			return $result;
		}
		
		if ((!$this->isNewDocument && empty($this->id)) || (!$destination->isNewDocument && empty($destination->id)))
			return $result;
		
		if ($this->isModified && !empty($this->fieldBuffer)) 
		{
			foreach ($this->fieldBuffer as $field)
			{
				if ($field->modified) {
					$destination->setField($field, $field->value, $field->type);
					$result = true;
				}
			}
		}
		
		if (!$this->isNewDocument) 
		{
			$sourceFields = $this->getFields('*');
		}
		
		if (!empty($sourceFields)) 
		{
			foreach ($sourceFields as $field => $value)
			{
				if (array_key_exists($field, $this->fieldBuffer))
				{
					$destination->setField($field, $value);
					//@todo:attachments should be copied separately
					$result = true;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Copies an item from another document
	 * 
	 * @param DocovaField $item
	 * @param string $newname
	 * @return NULL
	 */
	public function copyItem($item, $newname)
	{
		$result = null;
		if (empty($item) || !($item instanceof DocovaField))
			return $result;
		
		if (!$this->isNewDocument && empty($this->unid))
			return $result;
		
		if ($item->parent && $item->parent instanceof DocovaDocument)
			$result = $item->copyItemToDocument($this, $newname);
		
		return $result;
	}
	
	/**
	 * Create new rich text item
	 * 
	 * @param string $fieldname
	 * @return DocovaRichTextItem
	 */
	public function createRichTextItem($fieldname)
	{
		$richText = $this->_docova->DocovaRichTextItem($this, $fieldname);
		$richText->modified = true;
		$this->fieldBuffer[strtolower($fieldname)] = $richText;
		$this->isModified = true;		
		
		return $richText;
	}
	
	//@NOTE: do I need to have same setParentDocument as Domino version?!
	
	/**
	 * Makes one document a response to another document.
	 * 
	 * @param DocovaDocument $objDocovaDocument
	 * @return NULL|boolean
	 */
	public function makeResponse($objDocovaDocument)
	{
		if (empty($objDocovaDocument) || !($objDocovaDocument instanceof DocovaDocument)) 
		{
			return null;
		}
		
		$this->parentDoc = $objDocovaDocument;
		$this->isResponse = true;
		$this->isModified = true;
		return true;
	}
	
	/**
	 * Computes the document with the associated form
	 * NOT SURE HOW THIS WILL BE IMPLEMENTED IN SE
	 * 
	 * @param void $doDataType
	 * @param void $raiseError
	 */
	public function computeWithForm($doDataType = null, $raiseError = null)
	{
		$this->doComputeWithForm = true;
	}
	
	/**
	 * Deletes a docova attachment in the document.
	 * 
	 * @param string $attachment
	 * @return boolean
	 */
	public function deleteAttachment($attachment)
	{
		if (empty($attachment))
			return false;
		
		if (!empty($this->attachmentBuffer))
		{
			if ($this->isNewDocument) {
				unset($this->attachmentBuffer[$attachment]);
				$this->isModified = true;
			}
			elseif (array_key_exists($attachment, $this->attachmentBuffer)) 
			{
				$this->attachmentBuffer[$attachment] = null;
				$this->isModified = true;
			}
		}
/*		
		$attachment = $this->_em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('File_Name' => $attachment, 'Document' => $this->unid));
		if (!empty($attachment)) 
		{
			if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$this->unid.DIRECTORY_SEPARATOR.md5($attachment->getFileName()))) {
				@unlink($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$this->unid.DIRECTORY_SEPARATOR.md5($attachment->getFileName()));
			}
			$this->_em->remove($attachment);
			$this->_em->flush();
			return true;
		}
		return false;
*/
	}
	
	/**
	 * Deletes a back end docova document. If a library app flags for trash, otherwise permanently deletes.
	 * 
	 * @return boolean
	 */
	public function deleteDocument()
	{
		$result = false;
		
		if (empty($this->unid))
			return $result;
		
		$session = $this->_docova->DocovaSession();
		$user = $session->getCurrentUser();
		if (empty($user)){
			return $result;
		}		
		
		$session = null;
		

		if ($this->isProfile == false)
		{
			if ($this->_document->getDocTitle() && $this->_document->getFolder())
			{
				$this->_document->setTrash(true);
				$this->_document->setDateDeleted(new \DateTime());
				$this->_document->setDeletedBy($user);
				$bmk = $this->_document->getBookmarks();
				if ($bmk->count()) {
					foreach ($bmk as $b) {
						$this->_em->remove($b);
					}
				}
				$this->_em->flush();
				$result = true;
			}
			else {
			    $acl = $this->parentApp->getAcl();
			    $docid = $this->_document->getId();
			    if(!$acl->isDocAuthor($this)){
			        return false;
			    }
			    if(!$acl->canDeleteDocument()){
			        return false;
			    }
				$result = $this->deleteDocumentPermanently($this->_document);
				$view_handler = new ViewManipulation($this->_docova, $this->parentApp->appID, $this->_settings);
				if($result)
				{
				    $views = $this->_em->getRepository('DocovaBundle:AppViews')->getAllAppViews($this->parentApp->appID);
				    if (!empty($views))
				    {
				        foreach ($views as $view) {
				            try {
				                $view_id = str_replace('-', '', $view->getId());
				                $view_handler->deleteDocument($view_id, $this->id);
				            }
				            catch (\Exception $e) {
				            }
				        }
				    }
				    $docform = $this->_document->getAppForm();
					//remove from data tables
					if ( !empty ($docform))
					{
						$dtviews =  $this->_em->getRepository('DocovaBundle:AppViews')->getDataTableInfoByForm($this->parentApp->appID, $docform->getId());

						if ( !empty($dtviews))
						{
							foreach ( $dtviews as $dtview)
							{
								$viewid =  str_replace('-', '', $dtview["id"]);
								try {
									$view_handler->beginTransaction();
									$view_handler->deleteDocument($viewid, $docid, false);
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
		}
		else {
			$result = $this->deleteProfileDocument();
		}
		
		if ($result === true) 
		{
			$this->_docroot = $this->_document = $this->id = $this->unid = $this->parentApp = $this->parentDoc = $this->parentFolder = $this->parentView = null;
			$this->attachmentBuffer = $this->children = $this->fieldBuffer = $this->items = $this->responses = array();
			$this->isModified = $this->isNewDocument = $this->isProfile = $this->isResponse = $this->isValid = false;
		}
		
		return $result;
	}
	
	/**
	 * Deletes a field value on a document
	 * 
	 * @param string $fieldname
	 * @return boolean
	 */
	public function deleteField($fieldname)
	{
		if (empty($fieldname))
			return false;
		
		if (!$this->isNewDocument && empty($this->unid))
			return false;
		
		$dField = $this->_docova->DocovaField($this, $fieldname);
		$dField->remove = true;
		$this->fieldBuffer[$fieldname] = $dField;
		$this->isModified = true;
		return true;
	}

	

	public function saveAttachmentToDocument ($field, $filepath)
	{
		if ( empty($field) || empty($filepath))
			return false;

		$basePath = $this->parentApp->getAttachmentsBasePath();
		if ( empty($basePath))
			throw new \Exception('Attachemnts base path not set in Application Object');

		$path_parts = pathinfo($filepath);
		
		$file_name = $path_parts['basename'];

		$fullpath = $basePath.DIRECTORY_SEPARATOR.$this->unid.DIRECTORY_SEPARATOR.md5($file_name);

		@copy($filepath, $fullpath);

		//now write the metadata
		$fieldentity = $field->getFieldEntity();

		if ( empty($fieldentity))
			throw new \Exception('Unable to find entity object for field '.$field->name);

		$temp = $this->_em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('File_Name' => $file_name, 'Document' => $this->unid));

		if ( empty ( $temp ))
			$temp = new AttachmentsDetails();

		$temp->setDocument($this->_document);
		$temp->setField($fieldentity);
		$temp->setFileName($file_name);
		$temp->setFileDate(new \DateTime());

		//use finfo to determine mine type...in windows need to include fileinfo.dll in php.ini
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mtype =  finfo_file($finfo, $filepath);
		finfo_close($finfo);
		$temp->setFileMimeType($mtype ? $mtype : 'application/octet-stream');
		$temp->setFileSize(filesize($fullpath));
		$session = $this->_docova->DocovaSession();
		$user = $session->getCurrentUser();
		$temp->setAuthor($user);
		
		$this->_em->persist($temp);

	}
	
	/**
	 * Find and return a docova attachment object
	 * 
	 * @param string $filename
	 * @return NULL|DocovaAttachment
	 */
	public function getAttachment($filename)
	{
		if (empty($filename) || empty($this->unid))
			return null;
		
		$attachment = $this->_em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('File_Name' => $filename, 'Document' => $this->unid));
		if (!empty($attachment))
		{
			$attachment = $this->_docova->DocovaAttachment($this, $attachment, $this->_docroot);
			return $attachment;
		}
		return null;
	}
	
	/**
	 * Returns an array of docova attachment objects for the document
	 * 
	 * @param string $pattern
	 * @return DocovaAttachment[]|array
	 */
	public function getAttachments($pattern = null)
	{
		if (empty($this->unid))
			return array();
		
		$output = array();
		if (!empty($pattern)) 
		{
			$pattern = str_replace('*', '%', $pattern);
			$attachments = $this->_em->getRepository('DocovaBundle:AttachmentsDetails')->getFilteredAttachments($this->unid, $pattern);
			if (!empty($attachments))
			{
				for ($x = 0; $x < count($attachments); $x++) {
					$output[$x] = $this->_docova->DocovaAttachment($this, $attachments[$x], $this->_docroot);
				}
			}
		}
		else {
			$attachments = $this->_document->getAttachments();
			if (!empty($attachments)) 
			{
				for ($x = 0; $x < count($attachments); $x++) {
					$output[$x] = $this->_docova->DocovaAttachment($this, $attachments[$x], $this->_docroot);
				}
			}
		}
		return $output;
	}
	
	//@note: maybe private getColumnValues function needs to be implemented here same as Domino
	//@note: maybe private getFieldFromDoc function needs to be implemented here same as Domino
	
	/**
	 * Returns the value of a field on a document
	 * 
	 * @param string $fieldname
	 * @return mixed
	 */
	public function getField($fieldname)
	{
	    if (empty($fieldname)){
			return null;
	    }

		$fieldname = strtolower($fieldname);
		
		if (!empty($this->fieldBuffer) && array_key_exists($fieldname, $this->fieldBuffer)) 
		{
			return $this->fieldBuffer[$fieldname]->value;
		}

		if($this->allfieldsretrieved){
		    return null;  //no need to check back end as full list of fields already retrieved
		}

		$fieldname = array($fieldname);
		try {
			$result = $this->getFields($fieldname);
			if (!empty($result[$fieldname[0]]))
				return $result[$fieldname[0]];
			return null;
		}
		catch (\Exception $e) {
			//@NOTE: log the error and return null
			return null;
		}
	}
	
	/**
	 * Returns the value of a field on a document
	 *
	 * @param string $fieldname
	 * @return mixed
	 */
	public function getItemValue($fieldname)
	{
	    return $this->getField($fieldname);
	}

	/**
	 * Returns an array of field names
	 * 
	 * @return string[]|null
	 */
	public function getFieldNames()
	{
		if (empty($this->unid))
			return null;
		
		$field_names = $this->_em->getRepository('DocovaBundle:DesignElements')->getDocFieldNames($this->unid);
		if (!empty($field_names)) 
		{
			return $field_names;
		}
		return null;
	}
	
	/**
	 * Returns the value of one or more fields from a document
	 * 
	 * @param string[] $fields
	 * @return array
	 */
	public function getFields($fields, $returnfieldarray = false)
	{
		$output = array();
		if (empty($fields) || (!$this->isNewDocument && empty($this->unid))){
			return $output;
		}
		$fieldarray = array();

		if ($fields === '*' || $fields[0] === '*'){
		  $this->allfieldsretrieved = true;
		}else{
		    $fields = is_array($fields) ? $fields : array($fields);
		    //convert all field names to lowercase
		    for ($x = 0; $x < count($fields); $x++) {
		        $fields[$x] = strtolower($fields[$x]);
		    }
		}

		
			if ($this->isProfile === false)
			{
				$core_fields = array('Author', 'Creator', 'DateArchived', 'DateCreated', 'DateDeleted', 'DateModified', 'DeletedBy', 'Description', 'DocStatus', 'DocTitle', 'DocVersion', 'Keywords', 'LockEditor', 'Locked', 'Modifier', 'Owner', 'ReleasedBy', 'ReleasedDate', 'Revision', 'StatusNo', 'Form');
				for ($x = 0; $x < count($core_fields); $x++) {
			    if ($this->allfieldsretrieved || in_array(strtolower($core_fields[$x]), $fields)){
					if (method_exists($this->_document, 'get'.$core_fields[$x]))
					{
						$value = call_user_func(array($this->_document, 'get'.$core_fields[$x]));
						$type = 0;
						if ($value instanceof \DateTime)
							$type = 1;
						elseif ($value instanceof \Docova\DocovaBundle\Entity\UserAccounts)
							$type = 3;
						elseif (is_numeric($value))
							$type = 4;
						
						$dField = $this->_docova->DocovaField($this, $core_fields[$x], $value, null, $type);
    						$this->fieldBuffer[strtolower($core_fields[$x])] = $dField;
    						$this->fieldBuffer[strtolower($core_fields[$x])]->modified = false;
    						$output[strtolower($core_fields[$x])] = $value;
    						$fieldarray[strtolower($core_fields[$x])] = $dField;
					}
					elseif ($core_fields[$x] == 'Form' && $this->_document->getAppForm()) {
						$formname = $this->_document->getAppForm()->getFormName();
						$formalias =  $this->_document->getAppForm()->getFormAlias();
						$value = !empty($formalias) ? $formalias : $formname;
						$dField = $this->_docova->DocovaField($this, 'Form', $value, null, 0);
						$this->fieldBuffer['form'] = $dField;
						$this->fieldBuffer['form']->modified = false;
						$output['Form'] = $value;
						$fieldarray['Form'] = $dField;
					}
				}
			}
	
				$core_fields = null;
				//get all field values in the document
				$field_values = $this->_em->getRepository('DocovaBundle:Documents')->getDocovaFieldValues($this->unid);
				if (!empty($field_values)) {
					foreach ($field_values as $field => $value)
					{
				    	if ($this->allfieldsretrieved || in_array(strtolower($field), $fields)){
							$dField = $this->_docova->DocovaField($this, $field, $value['value'], null, $value['type']);
    						$this->fieldBuffer[strtolower($field)] = $dField;
    						$this->fieldBuffer[strtolower($field)]->modified = false;
    						$output[strtolower($field)] = $value['value'];//will contain single value or array of value(s) when multi separator is set
    						$fieldarray[strtolower($field)] = $dField;
					   }
					}
				}
			}
			else {
				$core_fields = array('Author', 'Creator', 'DateArchived', 'DateCreated', 'DateDeleted', 'DateModified', 'DeletedBy', 'Modifier', 'Owner', 'ProfileName', 'ProfileKey');
				for ($x = 0; $x < count($core_fields); $x++) {
			    	if ($this->allfieldsretrieved || in_array(strtolower($core_fields[$x]), $fields)){
						if (method_exists($this->_document, 'get'.$core_fields[$x]))
						{
							$value = call_user_func(array($this->_document, 'get'.$core_fields[$x]));
							$type = 0;
	    						if ($value instanceof \DateTime){
								$type = 1;
	    						}elseif ($value instanceof \Docova\DocovaBundle\Entity\UserAccounts){
								$type = 3;
	    						}elseif (is_numeric($value)){
								$type = 4;
	    						}
							
							$dField = $this->_docova->DocovaField($this, $core_fields[$x], $value, null, $type);
	    						$this->fieldBuffer[strtolower($core_fields[$x])] = $dField;
	    						$this->fieldBuffer[strtolower($core_fields[$x])]->modified = false;
	    						$output[strtolower($core_fields[$x])] = $value;
	    						$fieldarray[strtolower($core_fields[$x])] = $dField;
						}
					}
				}
				$core_fields = null;
				//get all profile field values in the profile document
				$field_values = $this->_em->getRepository('DocovaBundle:Documents')->getDocovaFieldValues($this->unid, true);
				if (!empty($field_values)) {
					foreach ($field_values as $field => $value)
					{
				    	if ($this->allfieldsretrieved || in_array(strtolower($field), $fields)){
							$dField = $this->_docova->DocovaField($this, $field, $value['value'], null, $value['type']);
    						$this->fieldBuffer[strtolower($field)] = $dField;
    						$this->fieldBuffer[strtolower($field)]->modified = false;
    						$output[strtolower($field)] = $value['value'];//will contain single value or array of value(s) when multi separator is set
    						$fieldarray[strtolower($field)] = $dField;
					}
				}
			}
		}
		

			

		if ( $returnfieldarray){
				return $fieldarray;
		}else{
				return $output;
		}
	}
	
	/**
	 * Returns DocovaField object for specified field in the document
	 * 
	 * @param string $fieldname
	 * @return NULL|\Docova\DocovaBundle\ObjectModel\DocovaField
	 */
	public function getFirstItem($fieldname)
	{
		if (empty($fieldname))
			return null;
		
		if (!empty($this->fieldBuffer) && array_key_exists($fieldname, $this->fieldBuffer))
		{
			return $this->fieldBuffer[$fieldname];
		}
		
		if ($this->hasItem($fieldname)) 
		{
			$field = $this->fieldBuffer[$fieldname];
			return $field;
		}
		return null;
	}
	
	/**
	 * Returns the url to the document
	 * 
	 * @param array $option
	 * @return string
	 */
	public function getURL($option = array())
	{
		if (empty($this->unid) || $this->isProfile)
			return null;
		
		$editmode = (array_key_exists('editmode', $option) && $option['editmode'] === true);
		$urlstyle = (array_key_exists('fullurl', $option) && $option['fullurl'] === true ? (UrlGeneratorInterface::ABSOLUTE_URL) : null);
		$mode = (array_key_exists('mode', $option) ? $option['mode'] : '');
		$routename = ($editmode ? 'docova_openform' : 'docova_readappdocument');
		
		$params = array();
		if($editmode){
			$params['editDocument'] = "";
		}else{
			$params['openDocument'] = "";
		}
		$params['docid'] = $this->unid;
		$params['ParentUNID'] = $this->parentApp->appID;
		if(!empty($mode)){
			$params['mode'] = $mode;
		}
		
	
		$context = $this->_router->getContext();
		$context->setHost($this->_settings->getRunningServer());
		$context->setScheme($this->_settings->getSslEnabled() ? 'https' : 'http'); 
		$tempport = $this->_settings->getHttpPort();
		if(!empty($tempport)){
    		if($this->_settings->getSslEnabled()){
	   	       $context->setHttpsPort($tempport);
		    }else{
		        $context->setHttpPort($tempport);
	   	    }
		}	
		$pathparts = explode(DIRECTORY_SEPARATOR, $this->_docova->getContainer()->get('kernel')->getRootDir());
		$dir = $pathparts[count($pathparts)-2];
		$context->setBaseUrl(DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'app.php');
		
		$url = $this->_router->generate($routename, $params, $urlstyle);

		return $url;
	}
	
	/**
	 * Returns true if the document contains the specified field
	 * 
	 * @param string $fieldname
	 * @return boolean
	 */
	public function hasItem($fieldname)
	{
		if (empty($fieldname))
			return false;
		
		if (!empty($this->fieldBuffer) && array_key_exists($fieldname, $this->fieldBuffer)) 
		{
			return true;
		}
		
		if (empty($this->unid))
			return false;

		if ( empty($this->_document))
			return false;


		
		
		$field = $this->_em->getRepository('DocovaBundle:DesignElements')->getDocumentField($fieldname, $this->unid, $this->parentApp->appID,  $this->_document->getAppForm()->getId());
		if (!empty($field)) {
			$dField = $this->_docova->DocovaField($this, $fieldname, $field['value'], null, $field['field']->getFieldType());
			$dField->setFieldEntity( $field['field']);
			$this->fieldBuffer[$fieldname] = $dField;
		}
		return !empty($field);
	}

	public function addToDataTable ($parentdockey, $datatablename, $view = null, $val_array = null)
	{
		$dtdetails = array();
		$dtdetails["parentdockey"] = $parentdockey;
		$dtdetails["datatablename"] = $datatablename;
		$dtdetails["view"] = $view;
		$dtdetails["valarray"] = $val_array;

		$this->data_table_info[] = $dtdetails;
	}

	private function _getDataTableView($dtnametofind, $parentdocid)
	{
		$pDoc =  $this->_em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $parentdocid, 'application' => $this->parentApp->appID));
		$frm = $pDoc->getAppForm();

		if ( empty ( $frm))
			return "";

		$properties = $frm->getFormProperties();
		$dtdetails = $this->_em->getRepository('DocovaBundle:AppViews')->getDataTableInfo($dtnametofind, $properties->getId());
		return $dtdetails[0];
	}

	private function indexDataTableDocs()
	{
		foreach ( $this->data_table_info as $dt)
		{

			$viewid = "";
			$viewpers  = "";
			if (empty($dt["parentdockey"]))
				continue;

			if ( empty ($dt["view"])){
				$view = $this->_getDataTableView( $dt["datatablename"],$dt["parentdockey"]);
			}else{
				$view = $dt["view"];
			}

			if ( isset($view["id"])){
				$viewid = $view["id"];
				$viewid = str_replace('-', '', $viewid);
				$viewpers = $view["viewPerspective"];
			}

			if ( empty($viewid) || empty($dt["parentdockey"]) || empty($viewpers))
				continue;

			if ( empty ( $dt["valarray"])){
				$valarray = $this->getFields("*");
			}else{
				$valarray = $dt["valarray"];
			}

			$view_handler = new ViewManipulation($this->_docova, $this->parentApp->appID);
			if (!$view_handler->indexDataTableDocument(  $this->unid,  $valarray, $this->parentApp->appID, $viewid, $dt["parentdockey"], $viewpers) ) 
			{
				throw new \Exception('Insertion failed!');
			}

		}
	}
	
	/**
	 * Replaces value of field on document and return DocovaField object
	 *
	 * @param string $fieldname
	 * @param string $fieldvalue
	 * @param integer $type
	 * @return DocovaField|null
	 */
	public function replaceItemValue($fieldname, $fieldvalue, $type=null)
	{
	    if (empty($fieldname)){
			return null;
	    }
	    if (!$this->isNewDocument && empty($this->unid)){
	        return null;
	    }	    
	    if(!isset($type)){
		$type = 0;
    		
    		$tempval = (is_array($fieldvalue) ? (count($fieldvalue) > 0 ? $fieldvalue[0] : null) : $fieldvalue);
    		if ($tempval instanceof \DateTime){
			$type = 1;
    		}else if($tempval instanceof \Docova\DocovaBundle\Entity\UserAccounts){
    		    $type = 3;
    		}else if(is_bool($tempval) || is_float($tempval) || is_int($tempval)){
				$type = 4;
		}
	    }
	    $fieldname = strtolower($fieldname);
	    if($fieldname == "form" && !empty($this->parentApp)){
	        $this->_form = $this->_em->getRepository('DocovaBundle:AppForms')->findByNameAlias($fieldvalue, $this->parentApp->appID);
	    }
	    $dField = $this->_docova->DocovaField($this, $fieldname, $fieldvalue, null, $type);
	    $dField->modified = true;
	    $this->fieldBuffer[$fieldname] = $dField;
	    $this->isModified = true;
		return $dField;
	}
	
	/**
	 * Saves changes to a docova document to the back end record or creates a new record
	 * 
	 * @return boolean
	 */
	public function save($rebuildindex = true)
	{
		if (!$this->isModified && !$this->isNewDocument) {
			return false;
		}
		if (!$this->isNewDocument && empty($this->unid)) {
			return false;
		}
		$session = $this->_docova->DocovaSession();
		$user = $session->getCurrentUser();
		if (empty($user)){
			return false;
		}
		$newformname = "";
		$session = $formname = $doctype = $folderid = null;
		if (array_key_exists('form', $this->fieldBuffer) && !empty($this->fieldBuffer['form']->value)) {
			$newformname = $this->fieldBuffer['form']->value;
			unset($this->fieldBuffer['form']);
		}
		if (array_key_exists('documenttypekey', $this->fieldBuffer) && !empty($this->fieldBuffer['documenttypekey']->value)) {
			$doctype = $this->fieldBuffer['documenttypekey']->value;
			unset($this->fieldBuffer['documenttypekey']);
		}
		if (array_key_exists('folderid', $this->fieldBuffer) && !empty($this->fieldBuffer['folderid']->value)) {
			$folderid = $this->fieldBuffer['folderid']->value;
			unset($this->fieldBuffer['folderid']);
		}
		if ($this->isNewDocument)
		{
			if ($this->isProfile === false)
			{
				if (($this->parentApp->isApp && empty($newformname) && empty($formname)) || (($this->parentApp->appType == 'L' || $this->parentApp->appType == 'LG') && (empty($doctype) || empty($folderid)))){
					return false;
				}
			}
			else {
				if (array_key_exists('profilename', $this->fieldBuffer)){
					return false;
				}
			}
			
			if ( empty($this->_document))
				$this->_document = new Documents();

			if ( empty($formname))
				$formname = $newformname;
		}

		if ($this->isProfile === false )
		{
			if ($this->_document->getAppForm()) {
			    $acl = $this->parentApp->getAcl();
			    if(!$this->isNewDocument && !$acl->isDocAuthor($this)){
			        return false;
			    }else if($this->isNewDocument && !$acl->canCreateDocument()){
			        return false;
			    }
			    
				$formname = $this->_document->getAppForm()->getFormName();
			}
			elseif ($this->_document->getDocType()) {
				$doctype = $this->_document->getDocType()->getId();
				if (empty($folderid))
					$folderid = $this->_document->getFolder()->getId();
			}
		}
		try {
			$this->saveDocumentMetaData($formname, $newformname, $doctype, $folderid, $user);
			if ($this->isNewDocument)
			{
				$this->_document->setId($this->unid);
				$this->_em->persist($this->_document);
			}
			$this->_em->flush();
			$this->createAclEntries($user);
			if ($this->isResponse === true && !empty($this->parentDoc))
			{
				$this->createResponseHierarchy();
			}
			$this->saveFieldValues($user);
			if ($this->isProfile === false)
			{
				$this->generateLogs($user);
				if ( $rebuildindex){
					$this->updateViewEntries($user);
				}
			}
			$this->unid = $this->_document->getId();
			$this->isNewDocument = $this->isModified = false;
			$this->fieldBuffer = array();
			$this->_form =$this->_document->getAppForm();
			if ( count($this->data_table_info) > 0 )
			{
				$this->indexDataTableDocs();
			}
			return true;
		}
		catch (\Exception $e) {
			echo $e->getMessage().' on line '.$e->getLine().' of '.$e->getFile();
			// maybe the error should be logged first
			return false;
		}
	}
	
	
	/**
	 * Sends an email for the current document
	 * @param boolean $includelink - whether to send a link to the document
	 * @param string array $recipients - string - array of strings (optional) - who to send email to
	 * @return boolean
	 **/
	public function send($includelink, $recipients) {
		$result = false;
		
		$subject = $this->getField("Subject");
		$sendTo = $this->getField("SendTo");
		$copyTo = $this->getField("CopyTo");
		$blindCopyTo = $this->getField("BlindCopyTo");
		$bodyFields = "Body";
		$remark = "";

		$targetdoc = $this;
	
		if(!empty($recipients)){
			$sendTo = $recipients;
		}
		if(empty($subject)){
			$subject = "- No Subject -";
		}
		if(empty($sendTo)){
			$sendTo = "";
		}
		if(empty($copyTo)){
			$copyTo = "";
		}
		if(empty($blindCopyTo)){
			$blindCopyTo = "";
		}
	
		$objectmodel = new \Docova\DocovaBundle\ObjectModel\ObjectModel($this->_docova);	
		$objectmodel->setDocument($this);
		$session = $this->_docova->DocovaSession();
		$objectmodel->setUser($session->getCurrentUser());
		$result = $objectmodel->sendMailTo($sendTo,  $copyTo,  $blindCopyTo,  $subject,  $remark,  $bodyFields, $includelink);		
		unset($session, $objectmodel);
		
		return $result;
	} 
	
	
	/**
	 * Sets the value of a field on a document
	 * 
	 * @param string $fieldname
	 * @param mixed $value
	 * @param integer $type
	 * @return boolean
	 */
	public function setField($fieldname, $value, $type=null)
	{
		$tempfield = $this->replaceItemValue($fieldname, $value, $type);
		if(empty($tempfield)){
			return false;

		
		
		}else{
    		return true;
		}

	}
	
	/**
	 * Add DocovaField item to current document buffer
	 * 
	 * @param DocovaField $objDocField
	 */
	public function addItemToDoc($objDocField)
	{
		$this->fieldBuffer[$objDocField->name] = $objDocField;
		$this->isModified = true;
	}
	
	/**
	 * Get profile document name
	 * 
	 * @return string
	 */
	public function getProfileName()
	{
		if ($this->isProfile) {
			return $this->_document->getProfileName();
		}
		return '';
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
			if (true === $this->transferLogs($document) )
			{
				if ($document->getAttachments()->count() > 0) {
					$this->removeAttachedFiles($document->getId());
					foreach ($document->getAttachments() as $attachment) {
						$att = $this->_docova->DocovaAttachment($this, $attachment, $this->_docroot);
						$att->deleteAttachment();
//						$this->_em->remove($attachment);
					}
					$attachment = $att = null;
				}
				if ($document->getTextValues()->count() > 0)
				{
					foreach ($document->getTextValues() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getDateValues()->count() > 0)
				{
					foreach ($document->getDateValues() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getNumericValues()->count() > 0)
				{
					foreach ($document->getNumericValues() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getChildDocuments()->count() > 0) {
					foreach ($document->getChildDocuments() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getBookmarks()->count() > 0)
				{
					foreach ($document->getBookmarks() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getDocSteps()->count() > 0)
				{
					foreach ($document->getDocSteps() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getComments()->count() > 0)
				{
					foreach ($document->getComments() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getActivities()->count() > 0)
				{
					foreach ($document->getActivities() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getLogs()->count() > 0)
				{
					foreach ($document->getLogs() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getFavorites()->count() > 0)
				{
					foreach ($document->getFavorites() as $value) {
						$this->_em->remove($value);
					}
				}
/* application documents do not have discussion docs and review items for now, this part might be used in future
				if ($document->getDiscussion()->count() > 0)
				{
					foreach ($document->getDiscussion() as $value) {
						$this->_em->remove($value);
					}
				}
				if ($document->getReviewItems()->count() > 0)
				{
					foreach ($document->getReviewItems() as $value) {
						$this->_em->remove($value);
					}
				}
				$document->clearReviewers();
*/
				$value = null;
				$related_docs = $this->_em->getRepository('DocovaBundle:RelatedDocuments')->findLinkedDocuments($document->getId());
				foreach ($related_docs as $rd)
				{
					if ($rd->getParentDoc()->getId() === $document->getId()) {
						$this->_em->remove($rd);
					}
					elseif ($rd->getRelatedDoc()->getId() === $document->getId())
					{
						$rd->setRelatedDoc(null);
					}
				}
				$this->_em->remove($document);
				$this->_em->flush();

				$acl = $this->parentApp->getAcl();
				$acl->removeAllDocAuthors($document);
				$acl->removeAllDocReaders($document);
				
				return true;
			}
		}
		catch (\Exception $e) {
			var_dump($e->getMessage() . ' On line: ' . $e->getLine());
			return false;
		}
		return false;
	}
	
	/**
	 * Transfer all logs for the deleted document to the trashed log table
	 *
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @return boolean
	 */
	private function transferLogs($document)
	{
		if ($document->getLogs()->count() > 0)
		{
			try {
				foreach ($document->getLogs() as $log) {
					$trashed_log = new TrashedLogs();
					$trashed_log->setLogType(true);
					$trashed_log->setOwnerTitle('Document ID: '.$document->getId());
					$trashed_log->setParentLibrary($document->getApplication());
					$trashed_log->setDateCreated(new \DateTime());
					$trashed_log->setLogDetails($log->getLogDetails());
						
					$this->_em->persist($trashed_log);
					$this->_em->flush();
				}
				$trashed_log = new TrashedLogs();
				$trashed_log->setLogType(true);
				$trashed_log->setOwnerTitle('Document ID: '.$document->getId());
				$trashed_log->setParentLibrary($document->getApplication());
				$trashed_log->setLogDetails('Deleted document from application.');
				$trashed_log->setDateCreated(new \DateTime());
				$this->_em->persist($trashed_log);
				$this->_em->flush();
				return true;
			}
			catch (\Exception $e) {
				return false;
			}
		}
		return true;
	}

	
	/**
	 * Create an empty app/folder document in db
	 * 
	 * @param string $form
	 * @param string $doctype
	 * @param string $folder
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 * @return \Docova\DocovaBundle\Entity\Documents|NULL
	 */
	private function saveDocumentMetaData($form = null, $newform = null, $doctype = null, $folder = null, $user)
	{
		$formchanged = false;
		if (!empty($form))
		{

			$form = $this->_em->getRepository('DocovaBundle:AppForms')->findByNameAlias( $form,  $this->parentApp->appID);
			if (!empty($form)){
				
				if ( !empty( $newform)){
					$formname = $form->getFormName();
					$formalias = $form->getFormAlias();
					if ( $formname != $newform && $formalias != $newform){
						$form = $this->_em->getRepository('DocovaBundle:AppForms')->findByNameAlias( $newform,  $this->parentApp->appID);
						if ( !empty($form))
							$formchanged = true;
					}

				}


			}else{
				if ( !empty( $newform)){
					$form = $this->_em->getRepository('DocovaBundle:AppForms')->findByNameAlias( $newform,  $this->parentApp->appID);
					if ( !empty($form))
						$formchanged = true;
				}
			}
			$properties = $form->getFormProperties();


		}
		elseif (!empty($doctype) && !empty($folder))
		{
			$properties = $this->_em->getRepository('DocovaBundle:DocumentTypes')->find($doctype);
			$folder = $this->_em->getReference('DocovaBundle:Folders', $folder);
		}

		if (!empty($form) || (!empty($properties) && !empty($folder)) || $this->isProfile !== false)
		{
			if ($this->isNewDocument)
			{
				$this->_document->setOwner($user);
				$this->_document->setCreator($user);
				$this->_document->setAuthor($user);
				$this->_document->setDateCreated(new \DateTime());
				if ($this->isProfile === false) {
					if (!array_key_exists('DocStatus', $this->fieldBuffer))
						$this->_document->setDocStatus(($properties->getEnableLifecycle()) ? $properties->getInitialStatus() : $properties->getFinalStatus());
					if (!array_key_exists('StatusNo', $this->fieldBuffer))
						$this->_document->setStatusNo(($properties->getEnableLifecycle()) ? 0 : 1);
					if (!array_key_exists('DocVersion', $this->fieldBuffer))
					{
						if ($properties->getEnableLifecycle() && $properties->getEnableVersions()) {
							$this->_document->setDocVersion('0.0');
							$this->_document->setRevision(0);
						}
					}
				}

				if (!empty($form))
				{
					$app = $this->_em->getReference('DocovaBundle:Libraries', $this->parentApp->appID);
					$this->_document->setAppForm($form);
					$this->_document->setApplication($app);
				}
				elseif ($this->isProfile !== false) {
					$this->_document->setApplication($app);
				}
				else {
					$this->_document->setDocType($properties);
					$this->_document->setFolder($folder);
				}
			}

			$this->_document->setModifier($user);
			$this->_document->setDateModified(new \DateTime());

			if ( $formchanged)
				$this->_document->setAppForm($form);
			
			if (!empty($this->fieldBuffer) && !$this->parentApp->isApp)
			{
				foreach ($this->fieldBuffer as $fieldname => $field)
				{
					if (method_exists($this->_document, 'set'.$fieldname))
					{
						$value = null;
						if ($field->type === 1) {
							if ($field->value instanceof \DateTime)
								$value = $field->value;
							else {
								try {
									$value = new \DateTime($field->value);
								}
								catch (\Exception $e) {
									$value = null;
								}
							}
						}
						elseif ($field->type === 3)
						{
							if ($field->value instanceof \Docova\DocovaBundle\Entity\UserAccounts)
								$value = $field->value;
							else {
								$value = $this->fetchUserProfile($field->value);
							}
						}
						else 
							$value = $field->value;
						
						try {
							call_user_func(array($this->_document, 'set'.$fieldname), $value);
						}
						catch (\Exception $e) {
							//should I log if the value was not set?!
						}
						
						unset($this->fieldBuffer[$fieldname]);
					}
				}
			}
		}
	}
	
	/**
	 * Generate ACL entries for the document
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 */
	private function createAclEntries($user)
	{
		$acl = $this->parentApp->getAcl();
		if (!empty($acl))
		{
			if (!$this->isNewDocument) {
				$acl->removeAllDocAuthors($this->_document);
				$acl->removeAllDocReaders($this->_document);
			}
			$acl->addDocAuthor($this->_document, 'ROLE_USER', true);
			$acl->addDocReader($this->_document, 'ROLE_USER', true);
			$acl->addDocAuthor($this->_document, $user);
			$restrict_readers = false;
		
			foreach ($this->fieldBuffer as $field)
			{
				if ($field->isReader === true) {
					if ($field->value instanceof \Docova\DocovaBundle\Entity\UserAccounts) {
						$acl->addDocReader($this->_document, $field->value);
						$restrict_readers = true;
					}
					elseif ($field->value instanceof \Docova\DocovaBundle\Entity\UserRoles) {
						$acl->addDocReader($this->_document, $field->value, true);
						$restrict_readers = true;
					}
				}
				elseif ($field->isAuthor === true) {
					if ($field->value instanceof \Docova\DocovaBundle\Entity\UserAccounts) {
						$acl->addDocAuthor($this->_document, $field->value);
					}
					else {
						$acl->addDocAuthor($this->_document, $field->value, true);
					}
				}
			}
			if ($restrict_readers === true)
			{
				$acl->removeDocReader($this->_document, 'ROLE_USER', true);
				$acl->removeDocAuthor($this->_document, 'ROLE_USER', true);
			}
		}
	}

	private function _createNewFieldDesignElementsEntity($field, $user)
	{
		$field_obj = new DesignElements();
		$field_obj->setFieldName($field->name);
		$field_obj->setFieldType($field->type);
		$field_obj->setModifiedBy($user);
		$field_obj->setDateModified(new \DateTime());
		if ($this->isProfile) {
			//$field_obj->setProfileDocument($this->_document);
		}
		//TODO ..i think dodova Field needs to keep the multivalue
		//$elem->setMultiSeparator(!empty($e['separator']) ? $e['separator'] : null);
		$form = $this->_document->getAppForm();
		$field_obj->setForm($form);
		$this->_em->persist($field_obj);
		$this->_em->flush();

		return $field_obj;
	}


	private function _clearExistingFieldData($fieldname)
	{
		//removing field value if "remove" is set to true or value is empty
		$field_value = $this->_em->getRepository('DocovaBundle:FormTextValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
		if (!empty($field_value)) {
			foreach ($field_value as $v)
				$this->_em->remove($v);
		}
		$field_value = $this->_em->getRepository('DocovaBundle:FormDateTimeValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
		if (!empty($field_value)) {
			foreach ($field_value as $v)
				$this->_em->remove($v);
		}
		$field_value = $this->_em->getRepository('DocovaBundle:FormNameValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
		if (!empty($field_value)) {
			foreach ($field_value as $v)
				$this->_em->remove($v);
		}
		$field_value = $this->_em->getRepository('DocovaBundle:FormGroupValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
		if (!empty($field_value)) {
			foreach ($field_value as $v)
				$this->_em->remove($v);
		}
		$field_value = $this->_em->getRepository('DocovaBundle:FormNumericValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
		if (!empty($field_value))
		{
			foreach ($field_value as $v)
				$this->_em->remove($v);
			$this->_em->flush();
		}
	}


	private function _formatValue ($field, $fieldval)
	{
		$value = $fieldval;
		switch ($field->type)
		{
			case 1:
				if ( empty ($value)){
					$value = null;
				}else{
					if($fieldval instanceof \DateTime){
						$value = $fieldval;
					}else if (is_string($fieldval)){
						try{
							$value = new \DateTime(substr($fieldval, 0, 33));
						}catch(\Exception $e){
							$value = null;                      
						}
					}
				}
				break;
			case 3:

				if ( empty($value)){
					$value = null;

				}else
				{
					if (!is_object($fieldval))
					{
						$value = $this->findUser($fieldval);
						if (empty($value))
						{
							$value = $this->findGroup($fieldval);
						}
						if (empty($value))
						{
							$value = $this->createInactiveUser($fieldval);
						}
					}
					else {
						$value = $fieldval;
					}
				}
				
				break;
			case 4:
				if ( strlen($value) == 0)
				{
					$value = null;
				}else{
					$value = floatval($fieldval);
				}
				
				break;
			default:
				$value = $fieldval;
				
				break;
		}

		return $value;

	}

	private function _createNewField( $field, $fieldval, $user, $order = null){

		$value = $this->_formatValue($field, $fieldval);
		if ( $value == null ) return;

		switch ($field->type)
		{
			case 1:
				$field_value_entity = new FormDateTimeValues();
				
				break;
			case 3:
				
				if ($value instanceof \Docova\DocovaBundle\Entity\UserAccounts)
					$field_value_entity = new FormNameValues();
				elseif ($value instanceof \Docova\DocovaBundle\Entity\UserRoles)
					$field_value_entity = new FormGroupValues();
				break;
			case 4:
				$field_value_entity = new FormNumericValues();
				
				break;
			default:
				
				$field_value_entity = new FormTextValues();
				$field_value_entity->setSummaryValue(substr($value, 0, 450));
				break;
		}

		$field_obj_entity = $field->getFieldEntity();
		if ( empty($field_obj_entity)){
			$field_obj_entity = $this->_createNewFieldDesignElementsEntity($field, $user);
		}

		if (!empty($field_obj_entity) && !empty($field_value_entity))
		{
			$field_value_entity->setDocument($this->_document);
			$field_value_entity->setField($field_obj_entity);
			$field_value_entity->setFieldValue($value);
			$field_value_entity->setOrder($order);
			$this->_em->persist($field_value_entity);
			$this->_em->flush();
			
		}

	}

	private function _getExistingValuesEntity($fieldname, $field)
	{
		$cValues = $this->_em->getRepository('DocovaBundle:FormTextValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
		if (empty($cValues))
			$cValues = $this->_em->getRepository('DocovaBundle:FormNumericValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
		if (empty($cValues))
			$cValues = $this->_em->getRepository('DocovaBundle:FormDateTimeValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
		if (empty($cValues))
		{
			$cValues = $this->_em->getRepository('DocovaBundle:FormNameValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
			if (empty($cValues))
				$cValues = array();
			$groups = $this->_em->getRepository('DocovaBundle:FormGroupValues')->getDocumentFieldsValue($this->_document->getId(), array($fieldname), true);
			if (!empty($groups)) 
			{
				foreach ($groups as $g) {
					$cValues[] = $g;
				}
			}
			$groups = $g = null;
		}
		if (!empty($cValues) && is_array($cValues))
			$currentValue = $cValues[0];

		$currentType = 0;
		if ($currentValue instanceof \Docova\DocovaBundle\Entity\FormDateTimeValues) {
			$currentType = 1;
		}
		elseif ($currentValue instanceof \Docova\DocovaBundle\Entity\FormNumericValues) {
			$currentType = 4;
		}
		elseif ($currentValue instanceof \Docova\DocovaBundle\Entity\UserAccounts || $currentValue instanceof \Docova\DocovaBundle\Entity\UserRoles) {
			$currentType = 3;
		}

		if ($currentType != $field->type) {
			foreach ($cValues as $v)
				$this->_em->remove($v);
			$this->_em->flush();
			$currentValue = $v = null;
		}

		return $currentValue;
	}

	/**
	 * Create back response hierarchy records if not already exist
	 */
	private function createResponseHierarchy()
	{
		if (!empty($this->parentDoc->id))
		{
			$relation_exists = $this->_em->getRepository('DocovaBundle:RelatedDocuments')->findOneBy(array('Related_Doc' => $this->_document->getId()));
			if (empty($relation_exists))
			{
				$related_doc = new RelatedDocuments();
				$related_doc->setParentDoc($this->parentDoc->getEntity());
				$related_doc->setRelatedDoc($this->_document);
		
				$this->_em->persist($related_doc);
			}
			else {
				$relation_exists->setParentDoc($this->parentDoc->getEntity());
			}
			$this->_em->flush();
		}
	}
	
	/**
	 * Save all buffered custom field values
	 */
	private function saveFieldValues($user)
	{
		if (!empty($this->fieldBuffer))
		{
			foreach ($this->fieldBuffer as $fieldname => $field)
			{
				//if the field wasn't modified, then just ignore it
				if (! $field->modified)
					continue;

				$this->_clearExistingFieldData($fieldname);

				$fldarray = is_array($field->value) ? $field->value : array($field->value);
				$order = (count($fldarray) > 1 ? 0 : null);
				foreach( $fldarray as $fieldval)
				{
					if ( $field->type == 5 ){
						$this->saveAttachmentToDocument($field, $fieldval);
					}else{
						$this->_createNewField( $field, $fieldval, $user, $order);
						if($order !== null){ $order ++;}
					}
				}

		
				$this->_em->flush();
			}
		}
	}
	
	/**
	 * Update all app views base on updated/new field values
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 * @return void
	 */
	private function updateViewEntries($user)
	{
		$views = $this->_em->getRepository('DocovaBundle:AppViews')->getAllAppViews ($this->parentApp->appID);
		if (!empty($views)) 
		{
			$dateformat = $this->_settings->getDefaultDateFormat();
			$display_names = $this->_settings->getUserDisplayDefault();
			$repository = $this->_em->getRepository('DocovaBundle:Documents');
			$doc_values = $repository->getDocFieldValues($this->_document->getId(), $dateformat, $display_names, true);
			$view_handler = new ViewManipulation($this->_docova, $this->parentApp->appID);
			foreach ($views as $v)
			{
				try {
					$view_handler->indexDocument2($this->_document->getId(), $doc_values, $this->parentApp->appID, $v->getId(), $v->getViewPerspective(), null, false, $v->getConvertedQuery());
				}
				catch (\Exception $e) {
				}
			}
		}
	}
	
	/**
	 * Generate logs for the updated/created document
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 */
	private function generateLogs($user)
	{
		if (empty($this->_document))
			return;
		
		$log_details = 'Updated document through API.';
		if ($this->isNewDocument)
			$log_details = 'Created document through API.';
		
		$log = new DocumentsLog();
		$log->setDocument($this->_document);
		$log->setLogAction($this->isNewDocument ? 2 : 1);
		$log->setLogAuthor($user);
		$log->setLogDate(new \DateTime());
		$log->setLogStatus(false);
		$log->setLogDetails($log_details);
		$this->_em->persist($log);
		$this->_em->flush();
	}

	/**
	 * Fetch user profile from DB
	 * 
	 * @param string $username
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|null
	 */
	private function fetchUserProfile($username)
	{
		$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('User_Name_Dn_Abbreviated' => $username, 'Trash' => false));
		if (empty($user))
			$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $username, 'Trash' => false));
		
		if (!empty($user))
			return $user;
		
		return null;
	}
	
	/**
	 * Delete user profile document and its fields and values
	 * 
	 * @return boolean
	 */
	private function deleteProfileDocument()
	{
		try {
			$fields = $this->_document->getProfileFields();
			if (!empty($fields) && $fields->count())
			{
				foreach ($fields as $f)
				{
					$values = null;
					switch ($f->getType())
					{
						case 1:
							$values = $this->_em->getRepository('DocovaBundle:FormDateTimeValues')->findBy(array('Field' => $f->getId()));
							break;
						case 3:
							$values = $this->_em->getRepository('DocovaBundle:FormNameValues')->findBy(array('Field' => $f->getId()));
							$values = empty($values) ? array() : $values;
							$groups = $this->_em->getRepository('DocovaBundle:FormGroupValues')->findBy(array('Field' => $f->getId()));
							if (!empty($groups))
							{
								foreach ($groups as $g)
									$values[] = $g;
							}
							$groups = $g = null;
							break;
						case 4:
							$values = $this->_em->getRepository('DocovaBundle:FormNumericValues')->findBy(array('Field' => $f->getId()));
							break;
						default:
							$values = $this->_em->getRepository('DocovaBundle:FormTextValues')->findBy(array('Field' => $f->getId()));
							break;
					}
					if (!empty($values)){
						foreach ($values as $v) {
							$this->_em->remove($v);
						}
					}
					$this->_em->remove($f);
					$this->_em->flush();
				}
			}
			$this->_em->remove($this->_document);
			$this->_em->flush();
			return true;
		}
		catch (\Exception $e){
			return false;
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
	    $docova_certifier_name = $this->_settings->getDocovaBaseDn() ? $this->_settings->getDocovaBaseDn() : "/DOCOVA";
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
	 * returns the value converted into the appropriate format..i.e. multivalue etc.
	 * 
	 * @param \DOMDocument $fieldNode
	 * @return \DOMDocument
	 */

	private function getConvertedValue($item)
	{
		$ignoreblanks = $item->getAttribute('ignoreblanks') == "1" ? true : false;
		$multi = $item->getAttribute('multi') == "1" ? true : false;
		$notrim = $item->getAttribute('notrim') == "1" ? true : false;
		$elDataType = $item->getAttribute('dt') == "" ? "text" :  $item->getAttribute('dt');
		$sep = $item->getAttribute('sep');
		$elText = $item->nodeValue;
		if(!$notrim){
		  $elText = trim($elText);
		}
		$isMultivalue = false;
		$cValues = Array();

		if ( is_null($sep) || $sep == "")
			$sep = ",";

		if ( $multi){
			$elValues = explode($sep, $elText);
			if(!$notrim){
				$elValues = array_map('trim',$elValues);				
			}
			$isMultivalue = true;
		}else{
			$elValues = $elText;
		}

		if ( $elText != "" ){
			
			if ($elDataType == "number"){
				if ($isMultivalue){
					$cValues = Array();
					for ( $x = 0; $x < count($elValues); $x++){
						$cValues[$x] = floatval($elValues[$x]);
					}
				}else{
					
					$cValues = floatval($elValues);
				}
			}elseif ( $elDataType == "date") {
				if ( $isMultivalue){
					$cValues = Array();
					for ( $x = 0; $x < count($elValues); $x++){
						$cValues[$x] =  new \DateTime($elValues[$x]);
					}		
				}else{
					
					$cValues = new \DateTime($elValues);
				}
			}else{
				$cValues = $elValues;					
			}
		}
		return $cValues;
	}

	/**
	 * syncs the field values sent through XML.  This xml currently comes from the frontend docovaapi
	 * 
	 * @param mixed $fields
	 * @return boolean
	 */

	public function syncFieldsFromXML($fields)
	{
		foreach ($fields as $item) 
		{
			if($item->nodeType !== 3){
				$fldname = strtolower($item->tagName);
				$fldtype = $item->getAttribute('dt');
				$fldspecialtype = empty($item->getAttribute('specialType')) ? null :$item->getAttribute('specialType') ;
				$fldval =$this->getConvertedValue($item);


				$type = 0;
				if ($fldtype == "date")
					$type = 1;
				elseif ($fldtype == "number")
					$type = 4;
				elseif ( $fldtype == "names")
					$type = 3;

				$dField = $this->_docova->DocovaField($this, $fldname, $fldval, $fldspecialtype, $type);
				$this->fieldBuffer[$fldname] = $dField;
				$this->isModified = true;
		

				//$this->setField($fldname, $fldval, $type);
			}
		}
	}
}