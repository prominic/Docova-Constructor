<?php

namespace Docova\DocovaBundle\ObjectModel;

use Docova\DocovaBundle\Twig\DocovaExtension;

/**
 * Converts between different user name formats
 * Typically accessed as new DocovaName("Jim Smith/Acme")
 * @author javad_rahimi
 */
class DocovaName 
{
	private $_docova;
	private $_username = null;
	
	public function __construct($inputname, Docova $docova_obj = null)
	{
		if (empty($inputname) || !is_string($inputname))
		{
			throw new \Exception('Oops! Construction of DocovaName failed, unrecognized input name.');
		}
		
		if (!empty($docova_obj)) {
			$this->_docova = $docova_obj;
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_docova = $docova;
			}
			else {
				throw new \Exception('Oops! Construction of DocovaName failed, Docova service not available.');
			}
		}

		$this->_username = $inputname;
	}
	
	public function __get($name)
	{
		switch ($name)
		{
			case 'Abbreviated':
				return $this->getNameFormat('[ABBREVIATE]');
				break;
			case 'Addr821':
				return $this->getNameFormat('[ADDRESS821]');
				break;
			case 'ADMD':
				return $this->getNameFormat('[A]');
				break;
			case 'Canonical':
				return $this->getNameFormat('[CANONICALIZE]');
				break;
			case 'Common':
				return $this->getNameFormat('[CN]');
				break;
			case 'Country':
				return $this->getNameFormat('[C]');
				break;
			case 'Generation':
				return $this->getNameFormat('[Q]');
				break;
			case 'Given':
				return $this->getNameFormat('[G]');
				break;
			case 'Initials':
				return $this->getNameFormat('[I]');
				break;
			case 'IsHierarchical':
				return false !== strpos($this->_username, '/') ? true : false;
				break;
			case 'Organization':
				return $this->getNameFormat('[O]');
				break;
			case 'OrgUnit1':
				return $this->getNameFormat('[OU1]');
				break;
			case 'OrgUnit2':
				return $this->getNameFormat('[OU2]');
				break;
			case 'OrgUnit3':
				return $this->getNameFormat('[OU3]');
				break;
			case 'OrgUnit4':
				return $this->getNameFormat('[OU4]');
				break;
			case 'PRMD':
				return $this->getNameFormat('[P]');
				break;
			case 'Surname':
				return $this->getNameFormat('[S]');
				break;
			default :
				throw new \OutOfBoundsException('Undefined property "'.$name.'" via __get');
		}
	}
	
	/**
	 * Private function to call f_Name in scripts library
	 * 
	 * @param string $format
	 * @return string[]
	 */
	private function getNameFormat($format)
	{
		$object_model = $this->_docova->ObjectModel();
		$script = new DocovaExtension($object_model);
		return $script->f_Name($format, $this->_username);
	}
}