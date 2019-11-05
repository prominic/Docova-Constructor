<?php

namespace Docova\DocovaBundle\ObjectModel;

/**
 * Represents a means of navigation in a rich text item.
 * @author javad_rahimi
 */
class DocovaRichTextNavigator 
{
	private $domElements;
	private $currentElement = null;
	private $positionAtEnd = false;
	private $currentIndex = 0;
	private $startElement = null;
	private $endElement = null;

	public function __construct(\DOMDocument $itemDomObject, \DOMElement $startElem = null, \DOMElement $endElem = null)
	{
		if (empty($itemDomObject))
			throw new \Exception('Oops! Undefined itemDomArray, construction of the class failed.');
		
		$this->domElements = $itemDomObject;
		if (!empty($startElem)) {
			$this->startElement = $startElem;
		}
		if (!empty($endElem)) {
			$this->endElement = $endElem;
		}
	}
	
	/**
	 * Moves the current position to the first element of a specified type in a rich text item.
	 * 
	 * @param integer|string $elementType
	 * @return boolean
	 */
	public function findFirstElement($elementType)
	{
		if (empty($elementType))
			return false;

		return $this->selectDOMElement($elementType, 1);
	}
	
	/**
	 * Moves the current position to the next element of a specified type in a rich text item after the current position.
	 * 
	 * @param integer|string $elementType
	 * @param integer $occurence
	 * @return boolean
	 */
	public function findNextElement($elementType, $occurence)
	{
		if (empty($elementType) && empty($this->currentElement))
			return false;
		elseif (empty($elementType) && !empty($this->currentElement)) {
			switch (strtolower($this->currentElement->nodeName))
			{
				case 'table':
					$elementType = 'table';
					break;
				case 'p':
					$elementType = 'p';
					break;
				case 'a':
					$elementType = 'a';
					break;
				case 'td':
					$elementType = 'td';
					break;
				default:
					return false;
			}
		}
		
		$occurence = empty($occurence) ? 1 : $occurence;
		return $this->selectDOMElement($elementType, $occurence + 1, $this->currentElement);
	}
	
	/**
	 * Moves the current position to the element at a specified position within elements of the same type.
	 * 
	 * @param integer|string $elementType
	 * @param integer $occurence
	 * @return boolean
	 */
	public function findNthElement($elementType, $occurence)
	{
		if (empty($elementType))
			return false;
		
		$occurence = empty($occurence) ? 1 : $occurence;
		return $this->selectDOMElement($elementType, $occurence);
	}
	
	/**
	 * Moves the current position to the last element of a specified type in a rich text item.
	 * 
	 * @param integer|string $elementType
	 * @return boolean
	 */
	public function findLastElement($elementType)
	{
		if (empty($elementType))
			return false;
		
		return $this->selectDOMElement($elementType, -1);
	}
	
	/**
	 * Returns the element at the current position.
	 * 
	 * @return \DOMDocument|\DOMNode
	 */
	public function getElement()
	{
		return $this->currentElement;
	}
	
	/**
	 * Sets the current position at the end of a specified element in a rich text item
	 * 
	 * @param mixed $element
	 */
	public function setPositionAtEnd($element)
	{
		$this->positionAtEnd = true;
	}
	
	public function setStartElement(\DOMElement $element)
	{
		$this->startElement = $element;
	}
	
	public function setEndElements(\DOMElement $element)
	{
		$this->endElement = $element;
	}
	
	public function setCurrentElement($element)
	{
		$this->currentElement = $element;
	}
	
	/**
	 * Find the element in DOM
	 * 
	 * @param string|integer $elementType
	 * @param integer $index
	 * @return boolean
	 */
	private function selectDOMElement($elementType, $index, $startat = null)
	{
		if (empty($this->domElements))
			return false;
		
		$index = empty($index) ? 0 : ($index < 0 ? -1 : $index-1);
		if (is_numeric($elementType))
		{
			switch ($elementType)
			{
				case 1:
					$element = 'table';
					break;
				case 4:
					$element = 'p';
					break;
				case 5:
					$element = 'a';
					break;
				case 7:
					$element = 'td';
					break;
			}
		}
		else {
			switch ($elementType)
			{
				case 'table':
					$element = 'table';
					break;
				case 'textparagraph':
					$element = 'p';
					break;
				case 'doclink':
					$element = 'a';
					break;
				case 'tablecell':
					$element = 'td';
					break;
			}
		}
		
		if (empty($this->startElement) && empty($this->endElement) && empty($startat))
		{
			$elements = $this->domElements->getElementsByTagName($element);
			if (!empty($elements->length))
			{
				if ($index >= $elements->length || $index == -1) {
					$found = $elements->item($elements->length - 1);
					if (!empty($found)) {
						$this->currentElement = $found;
						return true;
					}
				}
				else {
					$found = $elements->item($index);
					if (!empty($found))
					{
						$this->currentElement = $found;
						return true;
					}
				}
			}
		}
		else {
			$element_range = array();
			if (!empty($startat))
			{
				$this->parseHelper($this->domElements, $element, $startat, $element_range);
			}
			else {
				$this->parseHelper($this->domElements, $element, $this->startElement, $element_range);
			}
			if (!empty($element_range))
			{
				$len = count($element_range);
				if ($index >= $len - 1 || $index == -1) {
					$this->currentElement = $element_range[$len - 1];
					return true;
				}
				else {
					$this->currentElement = $element_range[$index];
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Recursive function to parse nodes and their children to find the searching element matches
	 * and put them in the output array
	 *  
	 * @param \DOMNode $domNode
	 * @param string $find
	 * @param \DOMElement $startat
	 * @param array $foundElems
	 * @param boolean $started
	 * @param boolean $ended
	 */
	private function parseHelper(\DOMNode $domNode, $find, $startat, &$foundElems, $started = false, $ended = false)
	{
		foreach ($domNode->childNodes as $node)
		{
			if ($node->getNodePath() == $startat->getNodePath()) {
				$started = true;
			}
			elseif (!empty($this->endElement) && $node->getNodePath() == $this->endElement->getNodePath()) {
				$ended = true;
			}
			
			if ($started === true && $ended === false && strtolower($node->nodeName) == strtolower($find)){
				$foundElems[] = $node;
			}
			
			if ($ended === true) {
				return;
			}
			
			if ($node->hasChildNodes())
			{
				$this->parseHelper($node, $find, $foundElems, $started, $ended);
			}
		}
		return;
	}
}