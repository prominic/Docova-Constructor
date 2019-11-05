<?php

namespace Docova\DocovaBundle\ObjectModel;

/**
 * Back end class and methods for navigating DOCOVA view contents
 * @author javad_rahimi
 */
class DocovaViewNavigator 
{
	private $_collection = null;
	private $_category = null;
	public $count;
	public $parentView;
	
	public function __construct(DocovaView $parentView, $params = array())
	{
		if (empty($parentView) || !($parentView instanceof DocovaView))
			throw new \Exception('Oops! Construction of DocovaViewNavigator failed, unrecognized entries!');
		
		$this->parentView = $parentView;
		if (!empty($params) && !empty($params['category']))
		{
			$root_path = !empty($params['root_path']) ? $params['root_path'] : '';
			$this->_category = $params['category'];
			$this->_collection = $parentView->getAllDocumentsByKey($params['category'], true, $root_path);
		}
		else {
			$root_path = !empty($params) && !empty($params['root_path']) ? $params['root_path'] : '';
			$this->_collection = $parentView->getAllDocuments($root_path);
		}
		$this->count = $this->_collection->count();
	}
	
	public function getCount()
	{
		if (empty($this->_collection))
			return 0;
		
		return $this->_collection->count();
	}
	
	/**
	 * Returns a Docova Document object for first entry in the navigator collection
	 * 
	 * @return NULL|DocovaDocument
	 */
	public function getFirstDocument()
	{
		return !empty($this->_collection) && $this->_collection->count ? $this->_collection->getFirstDocument() : null;
	}
	
	/**
	 * Returns a Docova Document object for previous entry in the navigator collection
	 * 
	 * @param DocovaDocument $docovadoc
	 * @return NULL|DocovaDocument
	 */
	public function getPrev($docovadoc)
	{
		return $this->getPrevDocument($docovadoc);
	}
	
	/**
	 * Returns a Docova Document object for previous entry in the navigator collection
	 * 
	 * @param DocovaDocument $docovadoc
	 * @return NULL|DocovaDocument
	 */
	public function getPrevDocument($docovadoc)
	{
		return !empty($this->_collection) && $this->_collection->count ? $this->_collection->getPrevEntry($docovadoc) : null;
	}
	
	/**
	 * Returns a Docova Document object for next entry in the navigator collection
	 * 
	 * @param DocovaDocument $docovadoc
	 * @return NULL|DocovaDocument
	 */
	public function getNext($docovadoc)
	{
		return $this->getNextDocument($docovadoc);
	}
	
	/**
	 * Returns a Docova Document object for next entry in the navigator collection
	 * 
	 * @param DocovaDocument $docovadoc
	 * @return NULL|DocovaDocument
	 */
	public function getNextDocument($docovadoc)
	{
		return !empty($this->_collection) && $this->_collection->count ? $this->_collection->getNextEntry($docovadoc) : null;
	}
	
	/**
	 * Returns a Docova Document object for last entry in the navigator collection
	 * 
	 * @return NULL|DocovaDocument
	 */
	public function getLastDocument()
	{
		return !empty($this->_collection) && $this->_collection->count ? $this->_collection->getLastEntry() : null;
	}
}