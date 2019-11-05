<?php

namespace Docova\DocovaBundle\ObjectModel;

/**
 * Class and methods for interaction with DOCOVA field
 * Typically accessed from DocovaDocument.getFirstItem() or new DocovaField(document, fieldname, fieldvalue).
 * @author javad_rahimi
 */
class DocovaField 
{
	private $_em;
	public $remove = false;
	public $modified = false;
	public $isAuthor = false;
	public $isNames = false;
	public $isReader = false;
	public $isSummary = false;
	public $isProtected = false;
	public $isEncrypted = false;
	public $isSigned = false;
	public $name = null;
	public $parent = null;
	public $text;
	public $type = null;
	public $value = null;
	private $_entity = null;
	
	public function __construct($document, $fieldname, $fieldvalue = null, $special_type = null, $fieldType = null, Docova $docova_obj = null)
	{
		if (!empty($docova_obj)) {
			$this->_em = $docova_obj->getManager();
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_em = $docova->getManager();
			}
			else {
				throw new \Exception('Oops! DocovaField construction failed. Entity Manager not available.');
			}
		}

		if (!empty($document) && $document instanceof DocovaDocument) 
		{
			$this->parent = $document;
			$this->name = $fieldname;
			if (!empty($special_type))
			{
				if ($special_type == 'NAMES')
					$this->isNames = true;
				elseif ($special_type == 'READERS')
					$this->isReader = true;
				elseif ($special_type == 'AUTHORS')
					$this->isAuthor = true;
			}
			
			if ($fieldvalue !== null) {
				$this->value = $fieldvalue;
				$this->modified = true;
			}
			if ($fieldType !== null && is_numeric($fieldType))
				$this->type = $fieldType;

			$this->_entity = $this->getFieldEntity();

			if ( !empty($this->_entity))
			{
				$this->type = $this->_entity->getFieldType();
			}
			
			if (!empty($this->value))
			{
				$tmp_array = is_array($this->value) ? $this->value : array($this->value);
				for ($x = 0; $x < count($tmp_array); $x++)
				{
					$type = $this->getFieldType($tmp_array[$x]);
					switch ($type)
					{
						case 1:
							$tmp_array[$x] = $tmp_array[$x]->format('d/m/Y h:i:s A');
							break;
						case 3:
							$tmp_array[$x] = $tmp_array[$x]->getUserNameDnAbbreviated();
							break;
						case 4:
							$tmp_array[$x] = strval($tmp_array[$x]);
							break;
						default:
							if ($tmp_array[$x] instanceof \Docova\DocovaBundle\Entity\AppForms) {
								$tmp_array[$x] = $tmp_array[$x]->getFormName() ? $tmp_array[$x]->getFormName() : $tmp_array[$x]->getFormAlias();
							}
							break;
					}
				}
				$this->text = implode(',', $tmp_array);
				$tmp_array = null;
			}
		}
	}

	public function setFieldEntity( $entityobj){
		$this->_entity = $entityobj;
	}

	public function getFieldEntity(){

		$fieldentity = $this->_entity;


		if ( empty($fieldentity)){
			if ($this->parent->isProfile === false){
			    $formid = null;

			    $formentity = $this->parent->getFormEntity();
			    if(!empty($formentity)){
			        $formid = $formentity->getId();
			    }
			    if(!empty($formid)){
    				$fieldentity = $this->_em->getRepository('DocovaBundle:DesignElements')->getField($this->name, $formid, $this->parent->parentApp->appID);
			    }
			}else{ 
				$fieldentity = $this->_em->getRepository('DocovaBundle:DesignElements')->findOneBy(array('fieldName' => $this->name, 'profileDocument' => $this->parent->unid));
			}
		}
		return $fieldentity;

	}

	public function embedObject ($type, $notused, $path)
	{
		if ( $type != "EMBED_ATTACHMENT")
			throw new \Exception('Only type supported by embedObject is EMBED_ATTACHMENT');

		$this->value = $path;
		$this->parent->isModified = true;
	}

	
	/**
	 * Append a string or an array of strings to an existing text value
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function appendToTextList($value)
	{
		if (empty($value) || empty($this->parent) || empty($this->name))
			return false;
		
		$tmpValue = $this->parent->getField($this->name);
		if ($tmpValue instanceof \DateTime)
			$tmpValue = $tmpValue->format('m/d/Y H:i:s');
		elseif ($tmpValue instanceof \Docova\DocovaBundle\Entity\UserAccounts)
			$tmpValue = $tmpValue->getUserNameDnAbbreviated();
		elseif (is_numeric($tmpValue))
			$tmpValue = strval($tmpValue);
		
		$value = is_array($value) ? implode('', $value) : $value;
		$this->parent->setField($this->name, ($tmpValue . $value));
		return true;
	}

	/**
	 * Returns the value of the field as an array
	 * @return string
	 *
	 */

	public function getValues()
	{
		$tempval = null;
		if ( ! empty($this->value)) {
			if(! is_array($this->value))
				$tempval = array(_fieldvalue);
			else
				$tempval = $this->value;

		}else if(! empty($this->parent) &&  ! empty($this->name) ) {
			$tempval = $this->parent->getField($this->name);
		}
		return $tempval;
	}
	
	/**
	 * Checks if a value is contained as one of the values in an array of values
	 * 
	 * @param mixed $searchValue
	 * @return boolean
	 */
	public function contains($searchValue)
	{
		if (empty($searchValue) || empty($this->parent) || empty($this->name))
			return false;
		
		$value = $this->parent->getField($this->name);
		return ($value == $searchValue);
	}
	
	public function copyItemToDocument($targetDoc, $newname)
	{
		if (empty($targetDoc) || !($targetDoc instanceof DocovaDocument))
			return null;
		
		if ($this->parent && $this->name)
		{
			$targetDoc->addItemToDoc($this);
			$new_item = $targetDoc->fieldBuffer[$this->name];
			return $new_item;
		}
		return null;
	}
	
	/**
	 * Deletes a field value from the document
	 */
	public function remove()
	{
		if (!empty($this->parent) && !empty($this->name)) 
		{
			$this->parent->deleteField($this->name);
		}
	}

	/**
	 * Returns a DateTime object representing the value of the item. 
	 * @return string
	 *
	 */

	public function getDateTimeValue()
	{
		$tempval = null;
		if ( ! empty($this->value)) {
			$tempval = $this->value;

		}else if(! empty($this->parent) &&  ! empty($this->name) ) {
			$tempval = $this->parent->getField($this->name);
		}

		if ($tempval instanceof \DateTime)
			return $tempval;
		else
			return null;
	}
	
	/**
	 * Get value(s) data type
	 * 
	 * @param mixed $itemvalue
	 * @return number
	 */
	private function getFieldType($itemvalue)
	{
		$itemvalue = is_array($itemvalue) ? $itemvalue : array($itemvalue);
		
		$dataType = 0;
		foreach ($itemvalue as $value)
		{
			if ($value instanceof \DateTime)
				$dataType = 1;
			elseif ($value instanceof \Docova\DocovaBundle\Entity\UserAccounts)
				$dataType = 3;
			elseif (is_numeric($value))
				$dataType = 4;
		}
		
		return $dataType;
	}


	public function toString($dateformat = 'm/d/Y H:i:s')
	{
		$mvsep = ", "; //typically would use chr(31) but it is not valid in xml so use this string instead
		$attribs = "";
		$value = "";
		switch ($this->type)
		{
			case 0:
			case 2:
				//text /rich text
				
				if ( is_array($this->value)){
					foreach ( $this->value as $itemval){
						if ( $value != ""){
							$value .= $mvsep;
						}						
						$value .= $itemval;
					}
				}else{
					$value = $this->value;
				}
				break;
			case 1:
				
				if ( is_array($this->value)){
					foreach ( $this->value as $itemval){
						if ( $value != ""){
							$value .= $mvsep;
						}
						$value .= (is_string($itemval) ? $itemval : $itemval->format($dateformat));
					}
				}else{
					$value = is_string($this->value) ? $this->value : $this->value->format($dateformat);
				}
				break;
			case 3:
				
				if ( is_array($this->value))
				{
					foreach ( $this->value as $itemval){
						$tmpval = is_string($itemval) ? $itemval : $itemval->getUsername();
						
						if ( $value != ""){
							$value .= $mvsep;
						}
						
						$value .= $tmpval;
					}
				}else{
					if ( is_string($this->value))
						$value = $this->value;
					else
						$value = $this->value->getUsername();
				}
				break;
			case 4:
				
				if ( is_array($this->value)){
					foreach ( $this->value as $itemval){
						if ( $value != ""){
							$value .= $mvsep;
						}
						$value .= $itemval;
					}
				}else{
					$value = $this->value;
				}
				break;
		}

		
		
		
		return $value;

	}

	public function toXML($dateformat = 'm/d/Y H:i:s')
	{
		$mvsep = "_~*^_"; //typically would use chr(31) but it is not valid in xml so use this string instead
		$attribs = "";
		$value = "";
		switch ($this->type)
		{
			case 0:
			case 2:
				//text /rich text
				$attribs .= ' dt="text"';
				if ( is_array($this->value)){
					foreach ( $this->value as $itemval){
						if ( $value != ""){
							$value .= $mvsep;
						}						
						$value .= $itemval;
					}
				}else{
					$value = $this->value;
				}
				break;
			case 1:
				$attribs .= ' dt="date"';

				if ( is_array($this->value)){
					foreach ( $this->value as $itemval){
						if ( $value != ""){
							$value .= $mvsep;
						}
						$value .= (is_string($itemval) ? $itemval : $itemval->format($dateformat));
					}
				}else{
					$value = is_string($this->value) ? $this->value : $this->value->format($dateformat);
				}
				break;
			case 3:
				$attribs .= ' dt="names"';
				if ( is_array($this->value))
				{
					foreach ( $this->value as $itemval){
						$tmpval = is_string($itemval) ? $itemval : $itemval->getUsername();
						
						if ( $value != ""){
							$value .= $mvsep;
						}
						
						$value .= $tmpval;
					}
				}else{
					if ( is_string($this->value))
						$value = $this->value;
					else
						$value = $this->value->getUsername();
				}
				break;
			case 4:
				$attribs .= ' dt="number"';
				if ( is_array($this->value)){
					foreach ( $this->value as $itemval){
						if ( $value != ""){
							$value .= $mvsep;
						}
						$value .= $itemval;
					}
				}else{
					$value = $this->value;
				}
				break;
		}

		if ( is_array($this->value) ){
			$attribs .=  ' multi="1" sep="'.$mvsep.'" ';
		}
		
		$cleanfieldname = str_replace("$", "__S__", strtolower($this->name));
		
		$fieldData = "<" . $cleanfieldname . $attribs . "><![CDATA[" . $value . "]]></" . $cleanfieldname . ">";
		return $fieldData;
	}
}