<?php

namespace Docova\DocovaBundle\ObjectModel;

use Docova\DocovaBundle\Entity\Documents;
use Docova\DocovaBundle\Entity\DesignElements;
use Docova\DocovaBundle\Entity\FormDateTimeValues;
use Docova\DocovaBundle\Entity\FormTextValues;
use Docova\DocovaBundle\Entity\FormNumericValues;
use Docova\DocovaBundle\Entity\FormNameValues;
use Docova\DocovaBundle\Entity\FormGroupValues;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\AppForms;
use Docova\DocovaBundle\Entity\UserProfile;
use Docova\DocovaBundle\Extensions\MiscFunctions;


/**
 * Class for interaction with DOCOVA applications/libraries
 * @author javad_rahimi
 *        
 */
class DocovaApplication 
{
	private $_em;
	private $_router;
	private $_docova;
	private $_application;
	private $_props = array();
	private $appID;
	private $appType;
	private $appName;
	private $filePath = '';
	private $appIcon;
	private $appIconColor;
	private $appDescription;
	private $isNativeDb = false;
	private $currentUserAccessLevel = '';
	private $libraryList = array();
	private $server = 'localhost';
	private $isApp;
	private $unprocessedDocuments;
	private $newDocUNID = "";
	private $profilefieldsarr = [];
	private $global_settings;
	private $attachPath = '';

	public function __construct($options = array(), $applicationEntity = null, Docova $docova_obj = null)
	{
		if (!empty($docova_obj)) {
			$this->_em = $docova_obj->getManager();
			$this->_router = $docova_obj->getRouter();
			$this->_docova = $docova_obj;
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_em = $docova->getManager();
				$this->_router = $docova->getRouter();
				$this->_docova = $docova;
			}
			else {
				throw new \Exception('Oops! DocovaApplicatoin construction failed. Entity Manager not available.');
			}
		}

		$global_settings = $this->_em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $global_settings[0];


		if ( !is_null($applicationEntity)){
			if(gettype($applicationEntity) === "string"){
				$this->_application = $this->_em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $applicationEntity, 'Trash' => false));
				if (empty($this->_application))
					$this->_application = $this->_em->getRepository('DocovaBundle:LibraryGroups')->find($applicationEntity);				
			}else{
				$this->_application  = $applicationEntity;				
			}
			$this->appID = $this->_application->getId();
			$this->appName = $this->_application->getLibraryTitle();
			$this->appIcon = $this->_application->getAppIcon();
			$this->appIconColor = $this->_application->getAppIconColor();
			$this->appDescription = $this->_application->getDescription();
			$this->libraryList = array();
			$this->isApp = $this->_application->getIsApp();
			$this->appType = $this->_application->getIsApp() ? 'A' : 'L';
		}else if (!empty($options) && is_array($options))
		{
			if (key_exists('appID', $options) && !empty($options['appID'])) {
				$this->_application = $this->_em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $options['appID'], 'Trash' => false));
				if (empty($this->_application))
					$this->_application = $this->_em->getRepository('DocovaBundle:LibraryGroups')->find($options['appID']);
			}
			elseif (key_exists('appName', $options) && !empty($options['appName'])) {
				$this->_application = $this->_em->getRepository('DocovaBundle:Libraries')->findOneBy(array('Library_Title' => $options['appName'], 'Trash' => false));
				if (empty($this->_application))
					$this->_application = $this->_em->getRepository('DocovaBundle:LibraryGroups')->findOneBy(array('groupTitle' => $options['appName']));
			}
			
			if (!empty($this->_application) && $this->_application instanceof \Docova\DocovaBundle\Entity\Libraries) {
				$this->appID = $this->_application->getId();
				$this->appName = $this->_application->getLibraryTitle();
				$this->appIcon = $this->_application->getAppIcon();
				$this->appIconColor = $this->_application->getAppIconColor();
				$this->appDescription = $this->_application->getDescription();
				$this->libraryList = array();
				$this->isApp = $this->_application->getIsApp();
				$this->appType = $this->_application->getIsApp() ? 'A' : 'L';
			}
			elseif (!empty($this->_application) && $this->_application instanceof \Docova\DocovaBundle\Entity\LibraryGroups) {
				$this->appID = $this->_application->getId();
				$this->appType = 'LG';
				$this->appName = $this->_application->getGroupTitle();
				$this->appIcon = $this->_application->getGroupIcon();
				$this->appIconColor = $this->_application->getGroupIconColor();
				$this->appDescription = $this->_application->getGroupDescription();
				$this->libraryList = $this->_application->getLibraries();
				$this->isApp = false;
			}
			else {
				$this->_application = $this->appID = $this->appName = $this->isApp = null;
				$this->appIcon = $this->appIconColor = $this->appType = null;
			}
		}
		else {
			$session = $this->_docova->DocovaSession();
			$current_app = $session->getCurrentAppEntity();
			$session = null;
			if (!empty($current_app)) {
				$this->_application = $current_app;
				$this->appID = $this->_application->getId();
				$this->appName = $this->_application->getLibraryTitle();
				$this->appIcon = $this->_application->getAppIcon();
				$this->appIconColor = $this->_application->getAppIconColor();
				$this->appDescription = $this->_application->getDescription();
				$this->libraryList = array();
				$this->isApp = $this->_application->getIsApp();
				$this->appType = $this->_application->getIsApp() ? 'A' : 'L';
			}
			else {
				$this->_application = $this->appID = $this->appName = $this->isApp = null;
				$this->appIcon = $this->appIconColor = $this->appType = null;
			}
		}
		$this->unprocessedDocuments = $this->_docova->DocovaCollection($this);
	}
	
	/**
	 * Property set
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @throws \OutOfBoundsException
	 */
	public function __set($name, $value)
	{
		if (!property_exists($this, $name)) {
			throw new \OutOfBoundsException('Undefiend property "'.$name.'" via __set');
		}
		
		$this->$name = $value;
	}
	
	/**
	 * Property get
	 * 
	 * @param string $name
	 * @throws mixed|\OutOfBoundsException
	 */
	public function __get($name)
	{
		if (!property_exists($this, $name)) {
			throw new \OutOfBoundsException('Undefiend property "'.$name.'" via __get');
		}
		
		return $this->$name;
	}

	public function getEntity(){
		return $this->_application;
	}

	public function setAttachmentsBasePath($inpath){
		$this->attachPath = $inpath;
	}

	public function getAttachmentsBasePath(){
		return $this->attachPath;
	}
	
	/**
	 * Create a new back end docovaAgent object
	 * 
	 * @param string $name
	 * @return DocovaAgent
	 */
	public function getAgent($name)
	{
		return $this->_docova->DocovaAgent($this, $name);
	}
	
	/**
	 * Get all current app documents
	 * 
	 * @return DocovaCollection
	 */
	public function getAllDocuments()
	{
		$collection = $this->_docova->DocovaCollection($this);
		$documents = $this->_em->getRepository('DocovaBundle:Documents')->getAllAppDocuments($this->appID);
		if (!empty($documents)) 
		{
			foreach ($documents as $doc)
			{
				$document = $this->_docova->DocovaDocument($this, $doc);
				$collection->addEntry($document);
			}
		}
		
		return $collection;
	}
	
	/**
	 * Create a new back end document object
	 * 
	 * @param array $options
	 * @param string $root_path
	 * @return NULL|DocovaDocument
	 */
	public function createDocument($options = null, $root_path = '')
	{
		
		$document = $this->_docova->DocovaDocument($this);
		if ($this->isApp === true)
		{
			if (!empty($options) && !empty($options['formname']))
			{
				$document->setField('Form', $options['formname']);
			}
			if (!empty($options) && !empty($options['unid']))
			{
				$document->unid = $options['unid'];
			}
		}
		elseif ($this->_application instanceof \Docova\DocovaBundle\Entity\Libraries && !empty($options) && !empty($options['doctypeid']) && !empty($options['folderid']))
		{
			$document->setField('DocumentTypeKey', $options['doctypeid']);
			$document->setField('FolderID', $options['folderid']);
			//$document = $this->createAppFolderDocument(null, $options['doctypeid'], $options['folderid']);;
		}
		
		return $document;
	}
	
	/**
	 * Return a back end docova document object
	 * 
	 * @param string $docid
	 * @param string $root_path
	 * @return NULL|DocovaDocument
	 */
	public function getDocument($docid, $root_path = '')
	{
		if (empty($docid) || empty($this->appID)) {
			return null;
		}
		
		$document = $this->_docova->DocovaDocument($this, $docid, $root_path);
		if (!empty($document->getEntity()))
			return $document;
		
		return null;
	}

	/**
	 * Return the title of the application
	 * 
	 * @param string $docid
	 * @param string $root_path
	 * @return NULL|DocovaDocument
	 */
	public function getTitle()
	{
		return $this->appName;
	}
	
	/**
	 * Return a back end docova view object
	 * 
	 * @param string $viewname
	 * @return \Docova\DocovaBundle\ObjectModel\DocovaView|null
	 */
	public function getView($viewname)
	{
		if (empty($this->appID) || empty($viewname)) {
			return null;
		}
		
		$view = $this->_docova->DocovaView($this, $viewname);
		if (!empty($view->viewid))
			return $view;
	
		return null;
	}
	
	/**
	 * Get DocovaCollection of profile documents in the app
	 * 
	 * @param string $profilename
	 * @return DocovaCollection|NULL
	 */
	public function getProfileDocCollection($profilename)
	{
		if (empty($profilename) || empty($this->_application))
			return null;
		
		$profiles = $this->_em->getRepository('DocovaBundle:Documents')->getProfile($profilename, $this->appID);
		if (!empty($profiles))
		{
			$collection = $this->_docova->DocovaCollection();
			foreach ($profiles as $pd)
			{
				$pDoc = $this->_docova->DocovaDocument($this, $pd->getId());
				if (!empty($pDoc->id))
					$collection->addEntry($pDoc);
			}
			return $collection;
		}
		return null;
	}
	
	/**
	 * Get profile document that matches the profile name
	 * 
	 * @param string $profile
	 * @param string $key
	 * @return DocovaDocument
	 */
	public function getProfileDocument($profilename, $key, $profform = null )
	{
		if (empty($profilename) || empty($this->_application))
			return null;
		
		$unique_key = $key;
		if ($key instanceof \DateTime)
			$unique_key = $key->format('m-d-Y H:i:s');
		elseif ($key instanceof \Docova\DocovaBundle\Entity\UserAccounts)
			$unique_key = $key->getUserNameDnAbbreviated();
					
		$profile = $this->_em->getRepository('DocovaBundle:Documents')->getProfile($profilename, $this->appID, $unique_key);
		if (!empty($profile[0]) && count($profile) == 1)
		{
			$profile = $this->_docova->DocovaDocument($this, $profile[0]->getId());
			
		}else{
			//if not found then need to create one
			$profile = $this->createProfileDocument($profilename, $key, $profform);
			$profile = $this->_docova->DocovaDocument($this, $profile->getId());
		}
		return $profile;
	}


	public function getSubformFileName ($nameoralias)
	{
		$subform = $this->_em->getRepository('DocovaBundle:Subforms')->findSubformByNameAlias($nameoralias, $this->appID);
		if (!empty($subform)) {
			$filename = $subform->getFormFileName();
			$filename = str_replace(array('/', '\\'), '-', $filename);
			$filename = str_replace(' ', '', $filename);
			return $filename;
		}
	}

	/**
	 * Get server of the current application
	 *
	 * 
	 * @return string
	 */

	public function getServer()
	{
		return '';
	}


	public function getFilePath()
	{

		return $this->_router->generate('docova_homepage');
	}



	public function getProfileField($profile, $field, $key = null)
	{
		$value = $this->getProfileFields($profile, $field, $key);
		if (!empty($value[$field])){
			return  $value[$field];
		}
		return null;
	}

	/**
	 * Get field value from profile document record
	 *
	 * @param string $profile
	 * @param string $fields
	 * @param string $key
	 * @return array|null
	 */
	public function getProfileFields($profile, $fields, $key = null)
	{
		$value = null;
		if (empty($this->_application)) return $value;
		if (empty($profile) || empty($fields)) return $value;
		if ($key instanceof \DateTime)
			$key = $key->format('m-d-Y H:i:s');
		elseif ($key instanceof \Docova\DocovaBundle\Entity\UserAccounts)
			$key->getUserNameDnAbbreviated();
		
		$fields = is_array($fields) ? $fields : explode(',', $fields);

		$output = array();
		if ( !isset($this->profilefieldsarr[$profile])){
			$profile_doc = $this->_em->getRepository('DocovaBundle:Documents')->getProfile($profile, $this->appID,$key);
			if (!empty($profile_doc[0]))
				$profile_doc = $profile_doc[0];
			else
				return null;

		
			$profvalues = $this->_em->getRepository('DocovaBundle:Documents')->getDocFieldValues($profile_doc->getId(), $this->global_settings->getDefaultDateFormat(), $this->global_settings->getUserDisplayDefault(), true);
				
			$this->profilefieldsarr[$profile] =  $profvalues[0];
			
		}


		foreach ($fields as $field)
		{
			if ( isset($this->profilefieldsarr[$profile][strtolower($field)]) ){
				$output[$field] = $this->profilefieldsarr[$profile][strtolower($field)];
			}
		}

		return $output;

		

	

		$fields = $this->_em->getRepository('DocovaBundle:DesignElements')->getProfileFields($profile, $fields, $this->appID, $key);
		if (!empty($fields)) {
			$output = array();
			foreach ($fields as $field)
			{
				$value = '';
				switch ($field['fieldType'])
				{
					case 0:
					case 2:
						$tmp_value = $this->_em->getRepository('DocovaBundle:FormTextValues')->findBy(array('Document' => $profile_doc->getId(), 'Field' => $field['id']), array('order' => 'ASC'));
						if (!empty($tmp_value))
						{
							foreach ($tmp_value as $v) {
								$value .= $v->getFieldValue() . ';';
							}
						}
						break;
					case 1:
						$value = $this->_em->getRepository('DocovaBundle:FormDateTimeValues')->findBy(array('Document' => $profile_doc->getId(), 'Field' => $field['id']), array('order' => 'ASC'));
						if (!empty($tmp_value))
						{
							foreach ($tmp_value as $v) {
								$value .= $v->getFieldValue()->format('m/d/Y H:i:s') . ';';
							}
						}
						break;
					case 3:
						$tmp_value = $this->_em->getRepository('DocovaBundle:FormNameValues')->findBy(array('Document' => $profile_doc->getId(), 'Field' => $field['id']), array('order' => 'ASC'));
						if (!empty($tmp_value))
						{
							foreach ($tmp_value as $v) {
								$value .= $v->getFieldValue()->getUserNameDnAbbreviated() . ';';
							}
						}
						$tmp_value = $this->_em->getRepository('DocovaBundle:FormGroupValues')->findBy(array('Document' =>$profile_doc->getId(), 'Field' => $field['id']), array('order' => 'ASC'));
						if (!empty($tmp_value))
						{
							foreach ($tmp_value as $v) {
								$value .= $v->getFieldValue()->getDisplayName() . ';';
							}
						}
						break;
					case 4:
						$value = $this->_em->getRepository('DocovaBundle:FormNumericValues')->findBy(array('Document' => $profile_doc->getId(), 'Field' => $field['id']), array('order' => 'ASC'));
						if (!empty($tmp_value))
						{
							foreach ($tmp_value as $v) {
								$value .= $v->getFieldValue() . ';';
							}
						}
						break;
				}
				
				if (!empty($value))
					$value = substr_replace($value, '', -1);
				$value = explode(';', $value);
				$value = count($value) > 1 ? $value : $value[0];
				$output[$field['fieldName']] = $value;
			}
			return $value;
		}
		return null;
	}
	
	/**
	 * Sets one or more fields on a back end profile document
	 * 
	 * @param string $profile_name
	 * @param array $fieldvalues
	 * @param string $key
	 * @param \Docova\DocovaBundle\Entity\AppForms $parentForm
	 * @return boolean
	 */
	public function setProfileFields($profile_name, $fieldvalues, $key = null, $parentForm = null)
	{
		if (empty($profile_name) || empty($fieldvalues)) return false;
		if (empty($this->_application)) { return false; }
		$profile = $this->getProfileDocument($profile_name, $key, $parentForm);


		//TODO - change this code to set the field value on the profile document as you would on an normal docovadocument
		// i.e  $profile->replaceItemValue ( )

		if (empty($profile)) return false;
		$unique_key = $key;
		if ($key instanceof \DateTime)
			$unique_key = $key->format('m-d-Y H:i:s');
		elseif ($key instanceof \Docova\DocovaBundle\Entity\UserAccounts)
			$unique_key = $key->getUserNameDnAbbreviated();
		
		$success = true;
		foreach ($fieldvalues as $field => $value)
		{
			if ($value === null  || (is_array($value) && empty($value)) || (!is_array($value) and trim($value) === ''  ))
			{
				try {
					$profileField = $this->_em->getRepository('DocovaBundle:DesignElements')->getProfileFields($profile_name, $field, $this->appID, $unique_key);
					if (!empty($profileField))
					{
						switch ($profileField['fieldType'])
						{
							case 0:
							case 2:
								$values = $this->_em->getRepository('DocovaBundle:FormTextValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profileField['id']));
								break;
							case 1:
								$values = $this->_em->getRepository('DocovaBundle:FormDateTimeValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profileField['id']));
								break;
							case 3:
								$values = $this->_em->getRepository('DocovaBundle:FormNameValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profileField['id']));
								$groups = $this->_em->getRepository('DocovaBundle:FormGroupValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profileField['id']));
								if (!empty($groups))
								{
									foreach ($groups as $g) {
										$values[] = $g;
									}
								}
								$groups = null;
								break;
							case 4:
								$values = $this->_em->getRepository('DocovaBundle:FormNumericValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profileField['id']));
								break;
						}
						if (!empty($values))
						{
							foreach ($values as $v) {
								$this->_em->remove($v);
							}
							$this->_em->flush();
						}
					}
					else {
						if (!$this->setProfileField($profile, $field, null, $parentForm)) {
							$success = false;
							break;
						}
					}
				}
				catch (\Exception $e) {
					$success = false;
					break;
				}
			}
			else {
				if (!$this->setProfileField($profile, $field, $value, $parentForm))
				{
					$success = false;
					break;
				}
			}
		}
		return $success;
	}
	
	/**
	 * Get ACL class for this application
	 * 
	 * @return \Docova\DocovaBundle\ObjectModel\DocovaAcl
	 */
	public function getAcl()
	{
		$appAcl = $this->_docova->DocovaAcl($this->_application);
		return $appAcl;
	}
	
	/**
	 * Create profile document
	 * 
	 * @param string $profile_name
	 * @param mixed $key
	 * @return \Docova\DocovaBundle\Entity\Documents
	 */
	private function createProfileDocument($profile_name, $key, $profile_form)
	{
		if (empty($key))
			$key = null;
		elseif ($key instanceof \DateTime)
			$key = $key->format('m-d-Y H:i:s');
		elseif ($key instanceof \Docova\DocovaBundle\Entity\UserAccounts)
			$key->getUserNameDnAbbreviated();
		
		$profile = new Documents();
		$profile->setProfileName($profile_name);
		$profile->setProfileKey($key);
		$profile->setApplication($this->_application);
		$profile->setStatusNo(0);
		$session = $this->_docova->DocovaSession();
		$user = $session->getCurrentUser();

		//look for form..if not create a trashed form

		if ( !empty($profile_form)){
			$form = $profile_form;
		}else{

			$form = $this->_em->getRepository('DocovaBundle:AppForms')->findByNameAlias($profile_name, $this->appID);
			if ( empty ($form)){
				$form = new AppForms();
				$form->setFormName($profile_name);
				
				$form->setPDU(true);
				$form->setDateModified(new \DateTime());
				$form->setModifiedBy($user);
				$form->setTrash(true);
				$form->setDateCreated(new \DateTime());
				$form->setCreatedBy($user);
				$form->setApplication($this->_application);
				$this->_em->persist($form);
				$this->_em->flush();

				
			}
		}

		$profile->setAppForm($form);

		if ( empty($this->newDocUNID)){
			$miscfunctions = new MiscFunctions();
		   	 $tmpunid = $miscfunctions->generateGuid(); 
		}else{
			$tmpunid = $this->newDocUNID;
		}
		$profile->setId($tmpunid);

		$profile->setDateCreated(new \DateTime());
		$profile->setDateModified(new \DateTime());
		
		$profile->setOwner($user);
		$profile->setAuthor($user);
		$profile->setCreator($user);
		$profile->setModifier($user);
			
		$this->_em->persist($profile);
		$this->_em->flush();
		return $profile;
	}


    /* guesses the type of field based on the value being passed in */

	private function guessFieldType($value)
	{
		$type = 0;
		if (is_null($value)) {
			$type = 0;
		}
		elseif ($value instanceof \DateTime || (is_array($value) && $value[0] instanceof \DateTime)) {
			$type = 1;
		}
		elseif ($value instanceof \Docova\DocovaBundle\Entity\UserAccounts) {
			$type = 3;
		}
		elseif (is_numeric($value) || (is_array($value) && is_numeric($value[0]))) {
			$type = 4;
		}
		return $type;
	}
	
	/**
	 * Set field value record for profile document
	 *
	 * @param DocovaDocument $profile
	 * @param string $field
	 * @param mixed $value
	 * @param \Docova\DocovaBundle\Entity\AppForms $parentForm
	 * @return boolean
	 */
	private function setProfileField($profile, $field, $value, $parentForm)
	{
		if (empty($profile) || !$profile->getId() || empty($field)) return false;
		if (empty($this->_application)) { return false; }


		$frm = $profile->getEntity()->getAppForm();

		$profile_field = $this->_em->getRepository('DocovaBundle:DesignElements')->getFormElementsBy([$field], $frm->getId(), $this->appID);
		
		//$profileField = $this->_em->getRepository('DocovaBundle:DesignElements')->getProfileFields($profile->getEntity()->getProfileName(), $field, $this->appID, $profile->getEntity()->getProfileKey());
		if (empty($profile_field))
		{
			$pDoc = $profile->getEntity();
			$session = $this->_docova->DocovaSession();
			$user = $session->getCurrentUser();
			
			if (!empty($parentForm)) {
				$parent_field = $this->_em->getRepository('DocovaBundle:DesignElements')->getField($field, $parentForm->getId(), $this->appID);
				if (empty($parent_field)){
					$profile_field = new DesignElements();
					$type = $this->guessFieldType($value);
				}
				else 
				{
					$profile_field = clone $parent_field;
					$profile_field->setForm(null);
					$profile_field->setSubform(null);
					$type = $parent_field->getFieldType();
				}
			}
			else {
				$profile_field = new DesignElements();
				$type = $this->guessFieldType($value);
			}
			
			$profile_field->setFieldName($field);
			$profile_field->setFieldType($type);
			$profile_field->setModifiedBy($user);
			$profile_field->setDateModified(new \DateTime());
			$profile_field->setForm($frm);
			//$profile_field->setProfileDocument($pDoc);
			if (is_array($value)) {
				$profile_field->setMultiSeparator(';');
			}
			$this->_em->persist($profile_field);
			$this->_em->flush();
		}
		else {
			$field_values = null;
			switch ($profile_field[$field]['fieldType']) {
				case 1:
					$field_values = $this->_em->getRepository('DocovaBundle:FormDateTimeValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profile_field[$field]['id']));
					break;
				case 3:
					$field_values = $this->_em->getRepository('DocovaBundle:FormNameValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profile_field[$field]['id']));
					$values = $this->_em->getRepository('DocovaBundle:FormGroupValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profile_field[$field]['id']));
					if (!empty($values))
					{
						foreach ($values as $g) {
							$field_values[] = $g;
						}
					}
					$values = null;
					break;
				case 4:
					$field_values = $this->_em->getRepository('DocovaBundle:FormNumericValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profile_field[$field]['id']));
					break;
				default:
					$field_values = $this->_em->getRepository('DocovaBundle:FormTextValues')->findBy(array('Document' => $profile->getId(), 'Field' => $profile_field[$field]['id']));
					break;
			}
			if (!empty($field_values)) {
				foreach ($field_values as $v) {
					$this->_em->remove($v);
				}
				$this->_em->flush();
			}
			$field_values = null;
		}
		
		if (!is_null($value))
		{
			if (!is_array($value))
			{
				if ((is_object($profile_field) && $profile_field->getMultiSeparator()) || (!is_object($profile_field) && $profile_field[$field]['multiSeparator']))
				{
					$pattern = implode('|', explode(' ', is_object($profile_field) ? $profile_field->getMultiSeparator() : $profile_field[$field]['multiSeparator']));
					$value = preg_split('/('.$pattern.')/', $value);
				}
				else 
					$value = array($value);
			}

			$c = 0;
			foreach ($value as $v)
			{
				switch (is_object($profile_field) ? $profile_field->getFieldType() : $profile_field[$field]['fieldType']) {
					case 1:
						$field_value = new FormDateTimeValues();
						$v = $v instanceof \DateTime ? $v : new \DateTime($v);
						break;
					case 3:
						if (!is_object($v))
						{
							$tmp = $this->findUser($v);
							if (empty($tmp)) {
								$tmp = $this->findGroup($v);
							}
							if (empty($tmp))
							{
								$tmp = $this->createInactiveUser($v);
							}
							$v = $tmp;
						}
						if ($v instanceof \Docova\DocovaBundle\Entity\UserAccounts)
							$field_value = new FormNameValues();
						elseif ($v instanceof \Docova\DocovaBundle\Entity\UserRoles)
							$field_value = new FormGroupValues();
						break;
					case 4:
						$field_value = new FormNumericValues();
						$v = floatval($v);
						break;
					default:
						$field_value = new FormTextValues();
						$v = is_string($v) ? $v : strval($v);
						$v = trim($v);
						$field_value->setSummaryValue(substr($v, 0, 450));
						break;
				}
				if (empty($field_value))
					continue;
				if (!is_object($profile_field)) {
					$profile_field = $this->_em->getReference('DocovaBundle:DesignElements', $profile_field[$field]['id']);
				}
				$pDoc = $profile->getEntity();
				$field_value->setDocument($pDoc);
				$field_value->setField($profile_field);
				$field_value->setFieldValue($v);
				if (count($value) > 1) {
					$field_value->setOrder($c);
					$c++;
				}
				$this->_em->persist($field_value);
			}
			$this->_em->flush();
		}
		return true;
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
}