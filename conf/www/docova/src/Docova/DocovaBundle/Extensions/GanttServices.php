<?php

namespace Docova\DocovaBundle\Extensions;

use Docova\DocovaBundle\Entity\Documents;
use Docova\DocovaBundle\Entity\FormTextValues;
use Docova\DocovaBundle\Entity\FormDateTimeValues;
use Docova\DocovaBundle\Entity\FormNumericValues;
use Docova\DocovaBundle\Entity\FormNameValues;
use Docova\DocovaBundle\Controller\UtilityHelperTeitr;
use Docova\DocovaBundle\Entity\FormGroupValues;
use Docova\DocovaBundle\Entity\DesignElements;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Class for all Gantt back-end services including fetch records
 * and save Gantt records
 * @author javad_rahimi
 */
class GanttServices extends MiscFunctions
{
	private $view_name;
	private $application;
	private $user;
	private $global_settings;
	private $_document;
	private $_container;
	
	public function __construct($view_name, $app, $user, $settings)
	{
		$this->view_name = $view_name;
		$this->application = $app;
		$this->user = $user;
		$this->global_settings = $settings;
	}

	/**
	 * Generate Gantt records array in proper format
	 * 
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param \Docova\DocovaBundle\Extensions\ViewManipulation $view_handler
	 * @param \Docova\DocovaBundle\Entity\AppViews $view
	 * @param string $category
	 * @return array
	 */
	public function fetchGanttRecords($em, $view_handler, $view, $category = null)
	{
		$output = array(
			'tasks' => array(),
			'resources' => array(),
			'options' => array()
		);

		if (!empty($view))
		{
			$view_id = str_replace('-', '', $view->getId());
			if ($view_handler->viewExists($view_id))
			{
				$xml = new \DOMDocument();
				$xml->loadXML($view->getViewPerspective());
				$query = "SELECT * FROM view_$view_id WHERE App_Id= ? ";
				$params = array($this->application);
				if (!empty($category))
				{
					$x = 0;
					$cat_column = '';
					foreach ($xml->getElementsByTagName('xmlNodeName') as $item)
					{
						$title = $xml->getElementsByTagName('title')->item($x)->nodeValue;
						if ($title == 'Category') {
							$cat_column = $item->nodeValue;
							break;
						}
						$x++;
					}
					$query .= " AND $cat_column = ? ";
					$params[] = $category;
				}
				$result = $view_handler->selectQuery($query, $params);
				if (!empty($result))
				{
					for ($x = 0; $x < count($result); $x++)
					{
						$records = $this->getTaskRecordFormatted($em, $result[$x], $xml, $view->getGanttResourceType());
						if (!empty($records['assigs']) && count($records['assigs'])) {
							foreach ($records['assigs'] as $assignee) {
								if (false === $this->findInArray($output['resources'], 'name', $assignee['resourceId'])) {
									$output['resources'][] = [
										'id' => $assignee['resourceId'],
										'name' => $assignee['resourceId']
									];
								}
							}
						}
						$output['tasks'][] = $records;
					}
				}
			}
			
			$resources = [];
			if ($view->getGanttResourceType() == 'M')
			{
				$resources = preg_split("/(\r\n|\n|\r|\;)/", $view->getGanttResourceOptions());
			}
			elseif ($view->getGanttResourceType() == 'F' && trim($view->getGanttResourceFormula()))
			{
				$output['options'] = array('addresslookup' => false);
				$doc_values = $em->getRepository('DocovaBundle:Documents')->getDocFieldValues(array(), $this->global_settings->getDefaultDateFormat(), $this->global_settings->getUserDisplayDefault(), true, $this->application);
				$twig = $this->_container->get('twig');
				$template = $twig->createTemplate('{{ f_SetApplication("'.$this->application.'") }}{{ f_SetViewScripting() }}{% docovascript "output:string" %} '.$view->getGanttTranslatedFormula().'{% enddocovascript %} ');
				$tmp_output = $template->render(array(
					'docvalues' => $doc_values
				));
				if (trim($tmp_output))
				{
					$resources = preg_split("/(\r\n|\n|\r|\;)/", $tmp_output);
				}
				$tmp_output = null;
			}
			elseif ($view->getGanttResourceType() == 'A')
			{
				$output['options'] = array('addresslookup' => true);
			}
			if (!empty($resources))
			{
				foreach ($resources as $rs)
				{
					$output['resources'][] = array(
						'id' => $rs,
						'name' => $rs
					);
				}
			}
		}
		return $output;
	}

	/**
	 * Save and create Gantt record in the view
	 *
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param \Docova\DocovaBundle\Extensions\ViewManipulation $handler
	 * @param \Docova\DocovaBundle\Entity\AppViews $view
	 * @param \DOMDocument $xml
	 * @param string $category
	 * @return boolean|string
	 */
	public function saveGanttRecord($em, $handler, $view, $xml, $category = null)
	{
		$form = $view->getGanttDefaultForm();
		if (empty($form))
			return 'No default form is set for Gantt view!';
		
		$form = $em->getRepository('DocovaBundle:AppForms')->findByNameAlias($form, $this->application);
		if (empty($form))
			return 'Could not find Gantt default form.';
		
		try {
			$view_id = str_replace('-', '', $view->getId());
			$doc_id = $xml->getElementsByTagName('id')->item(0)->nodeValue;
			if (false !== strpos($doc_id, 'tmp_')) {
				$docova = new Docova($this->_container);
				$application = $docova->DocovaApplication(['appID' => $this->application]);
				$document = $docova->DocovaDocument($application);
				
				$document->setField('form', $form->getFormName() ? $form->getFormName() : $form->getFormAlias());
				$document->save(true);
				
				$application = $docova = null;
				$document = $document->getEntity();
			}
			else {
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(['id' => $doc_id, 'application' => $this->application]);
				if (empty($document)) {
					throw new \Exception('Document Not Found!');
				}
				$document->setModifier($this->user);
				$document->setDateModified(new \DateTime());
				$text_values = $document->getTextValues();
				$numeric_values = $document->getNumericValues();
				$date_values = $document->getDateValues();
				$name_values = $document->getNameValues();
				if (!empty($text_values) && $text_values->count()) {
					foreach ($text_values as $value) {
						$em->remove($value);
					}
				}
				if (!empty($numeric_values) && $numeric_values->count()) {
					foreach ($numeric_values as $value) {
						$em->remove($value);
					}
				}
				if (!empty($date_values) && $date_values->count()) {
					foreach ($date_values as $value) {
						$em->remove($value);
					}
				}
				if (!empty($name_values) && $name_values->count()) {
					foreach ($name_values as $value) {
						$em->remove($value);
					}
				}
				$em->flush();
				$handler->deleteDocument($view_id, $doc_id);
			}

			$elements = $em->getRepository('DocovaBundle:DesignElements')->getFormFields($form->getId(), $this->application);
			$perspective = new \DOMDocument();
			$perspective->loadXML($view->getViewPerspective());
			$field_names = $this->getViewColumns($perspective, 'columnFormula');
			$columns = $this->getViewColumns($perspective);
			$values = array();
			foreach ($field_names as $key => $f)
			{
				$field = $this->findMatchField($elements, $f);
				if ($field !== false)
				{
					if (!empty($xml->getElementsByTagName($key)->item(0)->nodeValue))
					{
						$value = $xml->getElementsByTagName($key)->item(0)->nodeValue;
						if ($field instanceof \Docova\DocovaBundle\Entity\DesignElements) {
							if ($field->getFieldType() == 1)
							{
								$value = str_replace('T', ' ', $value);
								$value = new \DateTime($value);
							}
							elseif ($field->getFieldType() == 4 && (strtolower($value) == 'true' || strtolower($value) == 'false')) {
								$value = strtolower($value) == 'true' ? 1 : 0;
							}
							$this->saveFieldValue($em, $document, $field, $value);
							$values[$key] = array('type' => $field->getFieldType(), 'value' => $value);
						}
						else {
							//if it's a docuemnt core field first update the feild value in DB then append it to values array
							if ($field != 3 && $field != 1) {
								$setField = "set$f";
								$document->$setField($value);
								$em->flush();
							}
							elseif ($field == 1) {
								$value = str_replace('T', ' ', $value);
								$value = new \DateTime($value);
							}
							$values[$key] = array('type' => $field, 'value' => $value);
						}
					}
					else {
						$values[$key] = array('type' => ($field instanceof \Docova\DocovaBundle\Entity\DesignElements ? $field->getFieldType() : $field), 'value' => null);
					}
				}
			}

			if (!empty($category))
			{
				$c = 0;
				$field = false;
				foreach ($perspective->getElementsByTagName('columnFormula') as $item)
				{
					if ($perspective->getElementsByTagName('title')->item($c)->nodeValue == 'Category') {
						$field = $item->nodeValue;
						$field = $this->findMatchField($elements, $field);
						break;
					}
					$c++;
				}
				
				if ($field !== false) {
					$this->saveFieldValue($em, $document, $field, $category);
					$values['category'] = array('type' => $field->getFieldType(), 'value' => $category);
				}
				else {
					$values['category'] = array('type' => 0, 'value' => $category);
				}
			}
			else {
				$values['category'] = null;
			}
			
			if (!empty($xml->getElementsByTagName('parent')->item(0)->nodeValue))
			{
				$field = $this->findElementOrCreate($em, $form, 'gantt_parent');
				$this->saveFieldValue($em, $document, $field, $xml->getElementsByTagName('parent')->item(0)->nodeValue);
			}
			if (!empty($xml->getElementsByTagName('depends')->item(0)->nodeValue))
			{
				$field = $this->findElementOrCreate($em, $form, 'gantt_depends');
				$this->saveFieldValue($em, $document, $field, $xml->getElementsByTagName('depends')->item(0)->nodeValue);
			}
			if (!empty($xml->getElementsByTagName('resourceId')->item(0)->nodeValue))
			{
				if ($view->getGanttResourceType() == 'A') {
					$field = $this->findElementOrCreate($em, $form, 'gantt_assigs', 3);
				}
				else {
					$field = $this->findElementOrCreate($em, $form, 'gantt_assigs', 0);
				}
				$tmp_values = [];
				foreach ($xml->getElementsByTagName('resourceId') as $username) {
					if (!empty($username->nodeValue)) {
						$tmp_values[] = $username->nodeValue;
					}
				}
				if (!empty($tmp_values))
				{
					$this->saveFieldValue($em, $document, $field, implode(';', $tmp_values));
					$efforts = array_fill(0, count($tmp_values), 0);
					$field = $this->findElementOrCreate($em, $form, 'gantt_efforts', 4);
					for ($x = 0; $x < count($efforts); $x++) {
						if (!empty($xml->getElementsByTagName('effort')->item($x)->nodeValue)) {
							$efforts[$x] = $xml->getElementsByTagName('effort')->item($x)->nodeValue;
						}
						else {
							$efforts[$x] = 0;
						}
					}
					$this->saveFieldValue($em, $document, $field, implode(';', $efforts));
				}
			}
/*
 * Javad >> NOT NEEDED IF WE CALL THE indexDocument2 to index the document in all views
 * 
			$query = "INSERT INTO view_$view_id (App_Id, Document_Id,";
			foreach ($columns as $c) {
				$query .= "$c,";
			}
			$query = substr_replace($query, ') VALUES (:appId, :docId,', -1);
			foreach ($columns as $key => $c) {
				$query .= " :$key,";
			}
			$query = substr_replace($query, ')', -1);
			$res = $handler->viewInsertionQuery($view_id, $query, $this->application, $document->getId(), $values);
*/
//			if ($res === true) {
				$views = $em->getRepository('DocovaBundle:AppViews')->findBy(array('application' => $this->application));
				if (!empty($views))
				{
					$dateformat = $this->global_settings->getDefaultDateFormat();
					$display_names = $this->global_settings->getUserDisplayDefault();
					$repository = $em->getRepository('DocovaBundle:Documents');
					$twig = $this->_container->get('twig');
					$doc_values = $repository->getDocFieldValues($document->getId(), $dateformat, $display_names, true);
					foreach ($views as $v)
					{
						try {
							$handler->indexDocument2($document->getId(), $doc_values, $this->application, $v->getId(), $v->getViewPerspective(), $twig, false, $v->getConvertedQuery());
						}
						catch (\Exception $e) {
						}
					}
//				}

				$this->_document = $document->getId();
				return true;
			}
			return 'Insertion to Gantt view failed!';
		}
		catch (\Exception $e) {
			return $e->getMessage().' line '. $e->getLine().' of '. $e->getFile();
		}
	}
	
	/**
	 * Delete document from Gantt view
	 * 
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param string $document
	 * @param \Docova\DocovaBundle\Extensions\ViewManipulation $handle
	 */
	public function deleteGanttRecord($em, $document, $handle)
	{
		$removed = 0;
		try {
			$app_views = $em->getRepository('DocovaBundle:AppViews')->getAllViewIds($this->application);
			if (!empty($app_views))
			{
				foreach ($app_views as $view_id)
				{
					$view_id = str_replace('-', '', $view_id);
					$res = $handle->deleteDocument($view_id, $document);
					if ($res === true) {
						$removed++;
					}
				}
			}
			if ($removed > 0) {
				return true;
			}
			return 'Failed to delete document from the view';
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	
	/**
	 * Set container
	 * 
	 * @param \Doctrine\ORM\EntityManager $container
	 */
	public function setContainer($container)
	{
		$this->_container = $container;
	}
	
	/**
	 * Get inserted document ID
	 * 
	 * @return string
	 */
	public function getInsertDocId()
	{
		return $this->_document;
	}
	
	/**
	 * Fetch view columns/nodes from view perspective
	 * 
	 * @param \DOMDocument $xml
	 * @param string $column_name
	 * @return array
	 */
	private function getViewColumns($xml, $column_name = null)
	{
		$column_name = empty($column_name) ? 'xmlNodeName' : $column_name;
		$columns = array(
			'category' => $column_name === 'xmlNodeName' ? 'CF0' : null,
			'code' => null,
			'name' => null,
			'startIsMilestone' => null,
			'start' => null,
			'duration' => null,
			'endIsMilestone' => null,
			'end' => null,
			'progress' => null,
			'status' => null,
			'desc' => null,
			'row' => null
		);
		
		$c = 0;
		foreach ($xml->getElementsByTagName($column_name) as $item)
		{
			switch ($xml->getElementsByTagName('title')->item($c)->nodeValue)
			{
				case 'Code/Short Name':
					$columns['code'] = $item->nodeValue;
					break;
				case 'Name':
					$columns['name'] = $item->nodeValue;
					break;
				case 'Milestone (Start)':
					$columns['startIsMilestone'] = $item->nodeValue;
					break;
				case 'Start Date':
					$columns['start'] = $item->nodeValue;
					break;
				case 'Duration':
					$columns['duration'] = $item->nodeValue;
					break;
				case 'Milestone (End)':
					$columns['endIsMilestone'] = $item->nodeValue;
					break;
				case 'End Date':
					$columns['end'] = $item->nodeValue;
					break;
				case 'Progress':
					$columns['progress'] = $item->nodeValue;
					break;
				case 'Status':
					$columns['status'] = $item->nodeValue;
					break;
				case 'Description':
					$columns['desc'] = $item->nodeValue;
					break;
				case 'Row #':
					$columns['row'] = $item->nodeValue;
					break;
			}
			$c++;
		}
		
		return $columns;
	}

	/**
	 * Generate formatted task array for a Gantt entry
	 *
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param array $row
	 * @param \DOMDocument $xml
	 * @param string $resource_type
	 * @return array
	 */
	private function getTaskRecordFormatted($em, $row, $xml, $resource_type)
	{
		$columns = $this->getViewColumns($xml);
		$start_date = !empty($row[$columns['start']]) ? new \DateTime($row[$columns['start']]) : null;
		$end_date = !empty($row[$columns['end']]) ? new \DateTime($row[$columns['end']]) : null;
		$parent = $em->getRepository('DocovaBundle:FormTextValues')->getDocumentFieldsValue($row['Document_Id'], ['gantt_parent']);
		$parent = !empty($parent) && !empty($parent[0]['fieldValue']) ? $parent[0]['fieldValue'] : ''; 
		$depends = $em->getRepository('DocovaBundle:FormTextValues')->getDocumentFieldsValue($row['Document_Id'], ['gantt_depends']);
		$depends = !empty($depends) && !empty($depends[0]['fieldValue']) ? $depends[0]['fieldValue'] : '';
		$assigs = [];
		if ($resource_type == 'A') {
			$assigs_names = $em->getRepository('DocovaBundle:FormNameValues')->getDocumentFieldsValue($row['Document_Id'], ['gantt_assigs']);
			$assigs_groups = $em->getRepository('DocovaBundle:FormGroupValues')->getDocumentFieldsValue($row['Document_Id'], ['gantt_assigs']);
		}
		else {
			$assigs_names = $em->getRepository('DocovaBundle:FormTextValues')->getDocumentFieldsValue($row['Document_Id'], ['gantt_assigs']);
		}
		$merged = [];
		if (!empty($assigs_names)) {
			for ($x = 0; $x < count($assigs_names); $x++) {
				$merged[] = $assigs_names[$x];
			}
		}
		if (!empty($assigs_groups)) {
			for ($x = 0; $x < count($assigs_names); $x++) {
				$merged[] = $assigs_groups[$x];
			}
		}
		$assigs_efforts = $em->getRepository('DocovaBundle:FormNumericValues')->getDocumentFieldsValue($row['Document_Id'], ['gantt_efforts']);
		if (!empty($merged)) {
			for ($x = 0; $x < count($merged); $x++) {
				$assigs[] = [
					'id' => 'tmp_' . ($x+1),
					'resourceId' => $merged[$x]['fieldValue'],
					'effort' => !empty($assigs_efforts[$x]['fieldValue']) ? $assigs_efforts[$x]['fieldValue'] : 0
				];
			}
		}
		$assigs_efforts = $assigs_groups = $assigs_names = $merged = null;
		
		$array = array(
			'assigs' => $assigs, 
			'id' => $row['Document_Id'],
			'code' => $row[$columns['code']] ? $row[$columns['code']] : '',
			'name' => $row[$columns['name']] ? $row[$columns['name']] : '',
			'startIsMilestone' => intval($row[$columns['startIsMilestone']]) ? 'True' : '',
			'start' => !empty($start_date) ? $start_date->format('Y-m-d\TH:i:s') : '0000-00-00T00:00:00',
			'duration' => $row[$columns['duration']] ? $row[$columns['duration']] : 1,
			'endIsMilestone' => intval($row[$columns['endIsMilestone']]) ? 'True' : '',
			'end' => !empty($end_date) ? $end_date->format('Y-m-d\TH:i:s') : '0000-00-00T00:00:00',
			'progress' => $row[$columns['progress']] ? $row[$columns['progress']] : 0,
			'status' => strtoupper($row[$columns['status']]),
			'description' => $row[$columns['desc']] ? $row[$columns['desc']] : '',
			'parent' => $parent,
			'depends' => $depends
		);
	
		return $array;
	}
	
	/**
	 * Find or create design element by name
	 * 
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param object $form
	 * @param string $field_name
	 * @param integer $type
	 * @return \Docova\DocovaBundle\Entity\DesignElements
	 */
	private function findElementOrCreate($em, $form, $field_name, $type = 0)
	{
		$field = $em->getRepository('DocovaBundle:DesignElements')->findFieldInForm($field_name, $form->getId(), $this->application, $type);
		if (empty($field))
		{
			$field = new DesignElements();
			$field->setFieldName($field_name);
			$field->setFieldType($type);
			$field->setForm($form);
			$field->setModifiedBy($this->user);
			$field->setDateModified(new \DateTime());
			if ($type == 3 || $type == 4) {
				$field->setMultiSeparator(';');
			}
			$em->persist($field);
			$em->flush();
		}
		return $field;
	}
	
	/**
	 * Find match field in fields array
	 * 
	 * @param \Docova\DocovaBundle\Entity\DesignElements[] $fields
	 * @param string $needle
	 * @return boolean|\Docova\DocovaBundle\Entity\DesignElements
	 */
	private function findMatchField($fields, $needle)
	{
		if (empty($fields))
			return false;
		
		$found = false;
		foreach ($fields as $f)
		{
			if (strtolower($f->getFieldName()) == strtolower($needle))
			{
				$found = $f;
				break;
			}
		}
		
		if ($found === false)
		{
			$doc_columns = array('Author' => 3, 'Creator' => 3, 'DateArchived' => 1, 'DateCreated' => 1, 'DateDeleted' => 1, 'DateModified' => 1, 'DeletedBy' => 3, 'Description' => 0, 'DocStatus' => 0, 'DocTitle' => 0, 'DocVersion' => 0, 'Keywords' => 0, 'LockEditor' => 3, 'Locked' => 0, 'Modifier' => 3, 'Owner' =>3, 'ReleasedBy' => 3, 'ReleasedDate' => 1, 'Revision' => 4, 'StatusNo' => 4, 'Form' => 0);
			if (array_key_exists($needle, $doc_columns)) {
				return $doc_columns[$needle];
			}
		}
		
		return $found;
	}
	
	/**
	 * Save field value meta data
	 * 
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\DesignElements $field
	 * @param string $value
	 */
	private function saveFieldValue($em, $document, $field, $value)
	{
		$separators = array();
		if ($field->getMultiSeparator()) {
			$separators = implode('|', explode(' ', $field->getMultiSeparator()));
		}
		$values = array();
		switch ($field->getFieldType())
		{
			case 0:
			case 2:
				if (!empty($separators)) {
					$values = preg_split('/('.$separators.')/', $value);
					$summary = array();
					for ($x = 0; $x < count($values); $x++) {
						$summary[] = substr($values[$x], 0, 450);
					}
				}
				else {
					$values = array($value);
					$summary = array(substr($values[0], 0, 450));
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
				if (!empty($separators)) {
					$values = preg_split('/('.$separators.')/', $value);
					for ($x = 0; $x < count($values); $x++) {
						$values[$x] = $this->getValidDateTimeValue($values[$x]);
					}
				}
				else {
					if ($value instanceof \DateTime) {
						$values = [$value];
					}
					else {
						$values = [$this->getValidDateTimeValue($value)];
					}
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
				if (!empty($separators)) {
					$values = preg_split('/('.$separators.')/', $value);
					for ($x = 0; $x < count($values); $x++) {
						$identity = $this->findUser($em, $values[$x]);
						if (empty($identity))
						{
							$identity = $this->findGroup($em, $values[$x]);
						}
						$values[$x] = $identity;
					}
				}
				else {
					$identity = $this->findUser($em, $value);
					if (empty($identity))
					{
						$identity = $this->findGroup($em, $value);
					}
					$values = array($identity);
				}
		
				if (!empty($values))
				{
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
							}
						}
					}
				}
				break;
			case 4:
				if (!empty($separators))
				{
					$values = preg_split('/('.$separators.')/', $value);
					for ($x = 0; $x < count($values); $x++) {
						$values[$x] = floatval($values[$x]);
					}
				}
				else {
					$values = array(floatval($value));
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
				break;
			default:
				if (!empty($separators)) {
					$values = preg_split('/('.$separators.')/', $value);
					$summary = array();
					for ($x = 0; $x < count($values); $x++) {
						$summary[] = substr($values[$x], 0, 450);
					}
				}
				else {
					$values = array($value);
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
	}
	
	/**
	 * Generates a valid datetime object from a datetime string or returns null
	 * 
	 * @param string $date_string
	 * @return NULL|\DateTime
	 */
	private function getValidDateTimeValue($date_string)
	{
		$format = $this->global_settings->getDefaultDateFormat();
		$format = str_replace(array('YYYY', 'MM', 'DD'), array('Y', 'm', 'd'), $format);
		$parsed = date_parse($date_string);
		$is_period = (false === stripos($date_string, ' am') && false === stripos($date_string, ' pm')) ? false : true;
		$time = (false !== $parsed['hour'] && false !== $parsed['minute']) ? ' H:i:s' : '';
		$time .= !empty($time) && $is_period === true ? ' A' : '';
		$value = \DateTime::createFromFormat($format.$time, $date_string);
		$format = $parsed = $time = $is_period = null;

		return empty($value) ? null : $value;
	}
	
	/**
	 * Find a match in associative array/collection
	 * 
	 * @param array $array
	 * @param string|integer $key
	 * @param mixed $needle
	 * @return boolean
	 */
	private function findInArray($array, $key, $needle)
	{
		if (empty($array) || empty($key)) {
			return false;
		}
		
		foreach ($array as $value) {
			if ($value[$key] === $needle) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Find user object base on username or abbreviated name
	 * 
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param string $username
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUser($em, $username)
	{
		$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $username));
		if (empty($user))
		{
			$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $username));
		}
		if (empty($user)) {
			$helper = new UtilityHelperTeitr($this->global_settings, $this->_container);
			$user = $helper->findUserAndCreate($username, true, $em);
		}
		if (!empty($user) && $user instanceof \Docova\DocovaBundle\Entity\UserAccounts)
			return $user;
		
		return false;
	}
	
	/**
	 * Find group object base on role/group name
	 * 
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param string $groupname
	 * @return \Docova\DocovaBundle\Entity\UserRoles|boolean
	 */
	private function findGroup($em, $groupname)
	{
		$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $groupname));
		if (!empty($group))
		{
			return $group;
		}
		return false;
	}
}