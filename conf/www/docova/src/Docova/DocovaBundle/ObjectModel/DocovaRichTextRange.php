<?php

namespace Docova\DocovaBundle\ObjectModel;

/**
 * Represents a range of elements in a rich text item.
 * @author javad_rahimi
 */
class DocovaRichTextRange 
{
	private $_navigator;
	private $_domElements;
	
	public function __construct(\DOMDocument $domElements)
	{
		if (empty($domElements))
		{
			throw new \Exception('Oops! Class construction failed due to empty inputs.');
		}
		
		$this->_domElements = $domElements;
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
		if ($name == 'Navigator') {
			return $this->_navigator;
		}
		elseif ($name == 'TextParagraph') {
			if (!empty($this->_navigator))
				return $this->_navigator->getElement()->saveHTML();
			else 
				return '';
		}
		else {
			throw new \OutOfBoundsException('Undefiend property "'.$name.'" via __get');
		}
	}
	
	/**
	 * Defines the beginning of a range.
	 * 
	 * @param mixed $element
	 */
	public function setBegin($element)
	{
		if ($element instanceof DocovaRichTextNavigator)
		{
			if (empty($this->_navigator))
			{
				$this->_navigator = new DocovaRichTextNavigator($this->_domElements, $element->getElement());
			}
			else {
				$this->_navigator->setStartElement($element->getElement());
			}
			
			$this->_navigator->setCurrentElement($element->getElement());
		}
	}
	
	/**
	 * Defines the end of a range.
	 * 
	 * @param mixed $element
	 */
	public function setEnd($element)
	{
		if ($element instanceof DocovaRichTextNavigator)
		{
			if (empty($this->_navigator))
			{
				$this->_navigator = new DocovaRichTextNavigator($this->_domElements, null, $element->getElement());
			}
			else {
				$this->_navigator->setEndElements($element->getElement());
			}
			
			$this->_navigator->setPositionAtEnd($element->getElement());
		}
	}
}