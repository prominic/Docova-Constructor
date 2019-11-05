<?php

namespace Docova\DocovaBundle\Extensions;


/**
 * Class for a mixture of commonly used functions
 *
 */
class MiscFunctions
{
	private $em;
	
	public function __construct() {

	}
	
	
	/**
	 * Generate a guid/uuid string
	 *
	 * @return string
	 */
	function generateGuid($case = null)
	{
		$result = '';
		
		if (function_exists('com_create_guid')) {
			$result = com_create_guid();
		}
		else {
			mt_srand((double)microtime()*10000);
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12);
			$result = $uuid;
		}
		
		if(!empty($case) && gettype($case) == "string"){
			if(strtoupper($case) == "UPPERCASE"){
				$result = strtoupper($result);
			}elseif(strtoupper($case) == "LOWERCASE"){
				$result = strtolower($result);
			}
		}
		
		return $result;
	}	


	/**
	 * Check to see if a value is a guid/uuid string
	 * 
	 * $param $guidvalue string
	 * @return bool
	 */
	function isGuid($guidvalue)
	{
		$result = false;
		
		$UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
		$result = (preg_match($UUIDv4, $guidvalue) == 1);
		
		return $result;
	}		
	
}