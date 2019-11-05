<?php

namespace Docova\DocovaBundle\ObjectModel;

use Doctrine\Common\Collections\ArrayCollection;
use Docova\DocovaBundle\Extensions\ViewManipulation;

/**
 * Class for interaction with collections of objects
 * Primarily used for collections of documents
 * @author javad_rahimi
 */
class DocovaCollection extends ArrayCollection
{
	private $_em;
	private $_docova;
	public $parent = null;
	public $count = null;
	public $isSorted = false;
	public $query = '';
	
	public function __construct($parent = null, $elements = array(), Docova $docova_obj = null)
	{
		if (!empty($docova_obj)) {
			$this->_docova = $docova_obj;
			$this->_em = $docova_obj->getManager();
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_em = $docova_obj;
				$this->_em = $docova->getManager();
			}
			else {
				throw new \Exception('Oops! DocovaCollection construction failed. Entity Manager not available.');
			}
		}
		
		if (!empty($parent)) 
		{
			$this->parent = $parent;
		}
		
		if (!empty($elements) && is_array($elements)) {
			parent::__construct($elements);
			$this->count = parent::count();
		}
	}
	
	/**
	 * Adds a new entry to the collection
	 * 
	 * @param object $entry
	 */
	public function addEntry($entry)
	{
		if (empty($entry) || !is_object($entry)) {
			return;
		}
		
		$this->add($entry);
		$this->count = $this->count();
	}
	
	/**
	 * Returns true if the collection contains a particular element or set of elements
	 * 
	 * @param mixed $needle
	 * @return boolean
	 */
	public function contains($needle)
	{
		if (is_string($needle)) {
			$elements = $this->toArray();
			foreach ($elements as $value) {
				if ($needle == $value->getId()) {
					return true;
				}
			}
		}
		elseif (is_array($needle))
		{
			$found = false;
			$elements = $this->toArray();
			foreach ($needle as $seek) {
				if (is_string($seek)) {
					foreach ($elements as $value) {
						if ($seek == $value->getId()) {
							$found = true;
							break;
						}
					}
				}
				else {
					$found = parent::contains($seek);
				}
				if ($found === false)
					return false;
			}
			return $found;
		}
		else {
			return parent::contains($needle);
		}
		return false;
	}
	
	/**
	 * Removes an entry from the collection
	 * 
	 * @param object $entry
	 */
	public function deleteEntry($entry)
	{
		$this->removeElement($entry);
		$this->count = $this->count();
	}
	
	/**
	 * Alias for deleteEntry
	 * 
	 * @param object $entryItem
	 */
	public function deleteDocument($entryItem)
	{
		$this->deleteEntry($entryItem);
	}
	
	/**
	 * Returns an entry from the collection
	 * 
	 * @param object $entry
	 * @return NULL|object
	 */
	public function getEntry($entry)
	{
		if (!$this->count()) {
			return null;
		}
		
		$elements = $this->toArray();
		foreach ($elements as $value) {
			if ($value->getId() == $entry->getId()) {
				return $value;
			}
		}
		return null;
	}
	
	/**
	 * Returns an object for first entry in the collection
	 * 
	 * @return NULL|mixed
	 */
	public function getFirstEntry()
	{
	    if (!$this->count()){
			return null;
	    }
		
		return $this->first();
	}
	
	public function getFirstDocument()
	{
		return $this->getFirstEntry();
	}
	
	/**
	 * Returns an object for previous entry in the collection
	 * 
	 * @param object $entry
	 * @return object|NULL
	 */
	public function getPrevEntry($entry)
	{
	    if (!$this->count()){
			return null;
	    }
		
		$index = $this->indexOf($entry);
		if ($index == 0 || $index === false){
			return null;
		}
		return $this->get($index - 1);
	}
	
	public function getPrevDocument($entry)
	{
		return $this->getPrevEntry($entry);
	}
	
	/**
	 * Returns an object for next entry in the collection
	 * 
	 * @param object $entry
	 * @return object|NULL
	 */
	public function getNextEntry($entry)
	{
	    if (!$this->count()){
			return null;
	    }
		
		$index = $this->indexOf($entry);
		if ($index === false || $index >= ($this->count() - 1)){
			return null;
		}
		return $this->get($index + 1);
	}
	
	public function getNextDocument($entry)
	{
		return $this->getNextEntry($entry);
	}
	
	/**
	 * Returns an object based on position in the collection
	 * 
	 * @param integer $position
	 * @return mixed|NULL
	 */
	public function getNthEntry($position)
	{
		$position--;
		if ($position < 0 || ($position > ($this->count() - 1)) || !$this->count()){
			return null;
		}
		
		return $this->get($position);
	}
	
	public function getNthDocument($position)
	{
		return $this->getNthEntry($position);
	}
	
	/**
	 * Returns an object for last entry in the collection
	 * 
	 * @return mixed|null
	 */
	public function getLastEntry()
	{
	    if (!$this->count()){
			return null;
	    }
		
		return $this->last();
	}
	
	public function getLastDocument()
	{
		return $this->getLastEntry();
	}
	
	/**
	 * Removes entries in the collection that do not match a provided collection or list of elements
	 * 
	 * @param mixed $elements
	 */
	public function intersect($elements)
	{
	    if (!$this->count()){
			return null;
	    }
		
		$elements = is_array($elements) ? $elements : array($elements);
		$collection = $this->toArray();
		foreach ($collection as $value) {
			$found = false;
			foreach ($elements as $elm)
			{
				if (is_object($elm)) {
					if ($elm->getId() == $value->getId()) {
						$found = true;
						break;
					}
				}
				elseif (is_string($elm))
				{
					if ($elm == $value->getId()) {
						$found = true;
						break;
					}
				}
			}
			if ($found === false) 
			{
				$this->removeElement($value);
			}
		}
		$this->count = $this->count();
	}
	
	/**
	 * Adds to collection any documents not already in the collection 
	 * that are contained in a second collection.
	 * 
	 * @param object[] $elements
	 */
	public function merge($elements)
	{
		$elements = !is_array($elements) ? array($elements) : $elements;
		if (!empty($elements) && is_array($elements)) 
		{
			foreach ($elements as $item)
			{
				if (is_object($item) && !$this->getEntry($item)) {
					$this->addEntry($item);
				}
			}
			$this->count = $this->count();
		}
	}
	
	/**
	 * Adds all the documents in the collection to the specified view
	 * which is emulated as folder.
	 * 
	 * @param string $foldername
	 * @return boolean
	 */
	public function putAllInFolder($foldername)
	{
		try {
			if (!$this->count() || empty($foldername) || !($this->parent instanceof DocovaView))
				throw new \Exception('Failed due to empty collection or empty foldername.');
			
			$view = $this->_em->getRepository('DocovaBundle:AppViews')->findOneBy(array('viewName' => $foldername, 'emulateFolder' => true));
			if (!empty($view))
			{
				$xml = new \DOMDocument();
				$xml->loadXML($view->getViewPerspective());
				$view_handler = new ViewManipulation($this->_docova, $this->parent->parentApp->appID);
				$elements = $this->toArray();
				$added = false;
				$session = $this->_docova->DocovaSession();
				$user = $session->getCurrentUser();
				if (empty($user))
					throw new \Exception('Current user is not initiated');
				$session = null;
				$dateformat = $this->_settings->getDefaultDateFormat();
				$display_names = $this->_settings->getUserDisplayDefault();
				
				foreach ($elements as $document)
				{
					$view_handler->beginTransaction();
					try {
						$view_id = str_replace('-', '', $view->getId());
						$exists = $view_handler->viewContains($view_id, $document->getId());
						$values_array = $this->_em->getRepository('DocovaBundle:Documents')->getDocFieldValues($this->_document->getId(), $dateformat, $display_names, true);
						if ($exists === false) {
							if (!$view_handler->addOrUpdateViewEntries2($document->getId(), $view_id, $xml, $values_array)) {
								throw new \Exception('Insertion failed for update');
							}
						}
						else {
							if (!$view_handler->addOrUpdateViewEntries2($document->getId(), $view_id, $xml, $values_array, false)) {
								throw new \Exception('Update failed.');
							}
						}
						$view_handler->commitTransaction();
						$added = true;
					}
					catch (\Exception $e) {
						var_dump($e->getMessage() . ' in line '. $e->getLine() . ' of file: '. $e->getFile());
						$view_handler->rollbackTransaction();
					}
				}
				return $added;
			}
			return false;
		}
		catch(\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Deletes all documents in the collection (should it remove documents from DB, too?)
	 * 
	 * @return NULL
	 */
	public function removeAll()
	{
	    if (!$this->count()){
			return null;
	    }
		
		if ($this->parent instanceof DocovaView) {
			$this->removeAllViewDocs();
			parent::clear();
		}
	}
	
	/**
	 * Removes all documents in the collection from the specified view
	 * which is emulated as folder.
	 * 
	 * @param string $foldername
	 * @return boolean
	 */
	public function removeAllFromFolder($foldername)
	{
	    if (!$this->count() || empty($foldername)){
			return false;
	    }

		return $this->removeViewEntries($foldername, true);
	}

	/**
	 * Updates all documents in collection with a field value
	 *
	 * @param string $fieldname
	 * @param mixed $fieldvalue
	 */
	public function stampAll($fieldname, $fieldvalue)
	{
	    if (!$this->count() || empty($fieldname)){
			return false;
	    }
	
		$count = 0;
		$collection = $this->toArray();
		foreach ($collection as $item)
		{
			if ($item instanceof DocovaDocument)
			{
				$type = 0;
				if ($fieldvalue instanceof \DateTime){
					$type = 1;
				}elseif ($fieldvalue instanceof \Docova\DocovaBundle\Entity\UserAccounts){
					$type = 3;
				}elseif (!is_nan($fieldvalue)){
					$type = 4;
				}
				$item->setField($fieldname, $fieldvalue, $type);
				$item->isModified = true;
				if ($item->save()){
				    $count++;
				}
			}
		}
	
		if (!empty($count)){
			return true;
		}
	
		return false;
	}
	
	/**
	 * Updates all documents in collection with multiple field values
	 * @note: if copyAllItems didn't work I need to run getField('*') and in a loop I update document field values
	 *
	 * @param DocovaDocument $sourcedoc
	 * @return boolean
	 */
	public function stampAllMulti(DocovaDocument $sourcedoc)
	{
	    if (!$this->count() || empty($sourcedoc)){
			return false;
	    }
	
		$count = 0;
		$collection = $this->toArray();
		foreach ($collection as $item)
		{
			if ($item instanceof DocovaDocument)
			{
				if ($sourcedoc->copyAllItems($item)) {
					if ($item->save())
						$count++;
				}
			}
		}
	
		if (!empty($count)){
			return true;
		}
	
		return false;
	}
	
	/**
	 * Removes entries in the collection that match a provided collection or list of elements
	 * 
	 * @param object[] $elements
	 */
	public function subtract($elements)
	{
		$elements = is_array($elements) ? $elements : array($elements);
		if (!$this->count() || empty($elements)){
			return;
		}
		
		foreach ($elements as $item)
		{
			if (is_string($item)) {
				$collection = $this->toArray();
				foreach ($collection as $value) {
					if ($value->getId() == $item) {
						$this->removeElement($value);
						break;
					}
				}
			}
			elseif (is_object($item)) {
				$key = $this->indexOf($item);
				if (false !== $key) {
					$this->remove($key);
				}
			}
		}
		$this->count = $this->count();
	}
	
	/**
	 * Delete the collection documents form view and database
	 */
	private function removeAllViewDocs()
	{
		$this->removeViewEntries();
		$collection = $this->toArray();
		foreach ($collection as $item) {
			try {
				$this->_em->beginTransaction();
				//if this is confirmed, document logs need to be deleted.
				$this->_em->getRepository('DocovaBundle:AttachmentsDetails')->deleteDocAttachments($item->getId());
				$this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->deleteAllDocWorkflows($item->getId());
				$this->_em->getRepository('DocovaBundle:FormDateTimeValues')->deleteAllValues($item->getId());
				$this->_em->getRepository('DocovaBundle:FormNumericValues')->deleteAllValues($item->getId());
				$this->_em->getRepository('DocovaBundle:FormNameValues')->deleteAllValues($item->getId());
				$this->_em->getRepository('DocovaBundle:FormGroupValues')->deleteAllValues($item->getId());
				$this->_em->getRepository('DocovaBundle:FormTextValues')->deleteAllValues($item->getId());
				$document = $this->_em->getReference('DocovaBundle:Documents', $item->getId());
				$this->_em->remove($document);
				$this->_em->flush();
				$this->_em->commit();
			}
			catch (\Exception $e) {
				$this->_em->rollback();
			}
		}
	}
	
	/**
	 * Delete the collection documents from view table(s)
	 * 
	 * @param string $viewname
	 * @param boolean $isFolder
	 */
	private function removeViewEntries($viewname = null, $isFolder = false)
	{
		$removed = 0;
		if (empty($viewname)) {
			$views = $this->_em->getRepository('DocovaBundle:AppViews')->getAllViewIds($this->parent->parentApp->appID);
		}
		else {
			$views = $this->_em->getRepository('DocovaBundle:AppViews')->findOneBy(array('viewName' => $viewname, 'emulateFolder' => $isFolder));
			$views = !empty($views) ? array($views->getId()) : array();
		}

		if (!empty($views))
		{
			$conn = $this->_em->getConnection();
			foreach ($views as $viewid)
			{
				$view = str_replace('-', '', $viewid);
				$this->_em->beginTransaction();
				try {
					$len = $this->count();
					$query = "DELETE FROM view_$view WHERE Document_Id IN (";
					for ($x = 0; $x < $len; $x++) {
						$query .= '?, ';
					}
					$query = substr_replace($query, ') AND ', -2);
					$query .= 'Application_Id = ? ';
					$collection = $this->toArray();
					$stmt = $conn->prepare($query);
					for ($x = 1; $x <= $len; $x++) {
						$stmt->bindValue($x, $collection[$x - 1]->getId());
					}
					$stmt->bindValue($x, $this->parent->parentApp->appID);
					$stmt->execute();
					$this->_em->commit();
					$removed++;
				}
				catch (\Exception $e) {
					//Log the message and rollback
					$this->_em->rollback();
				}
			}
		}
		
		return $removed > 0 ? true : false;
	}
}