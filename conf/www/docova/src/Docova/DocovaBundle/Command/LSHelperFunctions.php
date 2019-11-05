<?php
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Get ascii twig script
 * 
 * @param mixed $argument
 * @param string|array $flags
 * @return mixed
 */
function _ascii($argument, $flags = false)
{
	if (empty($argument)) { return is_array($argument) ? array() : ''; }
	if($flags !== false){
		if (is_array($flags)){
			$flags = ($flags[0] === "ALLINRANGE");
		}else{
			$flags = ($flags === true || $flags === "ALLINRANGE" || $flags === "[ALLINRANGE]");
		}
	}
	
	$defaultDiacriticsRemovalMap = array(
		'base' => 'A', 'letters' => '\u0041\u24B6\uFF21\u00C0\u00C1\u00C2\u1EA6\u1EA4\u1EAA\u1EA8\u00C3\u0100\u0102\u1EB0\u1EAE\u1EB4\u1EB2\u0226\u01E0\u00C4\u01DE\u1EA2\u00C5\u01FA\u01CD\u0200\u0202\u1EA0\u1EAC\u1EB6\u1E00\u0104\u023A\u2C6F',
		'base' => 'AA','letters' => '\uA732',
		'base' => 'AE','letters' => '\u00C6\u01FC\u01E2',
		'base' => 'AO','letters' => '\uA734',
		'base' => 'AU','letters' => '\uA736',
		'base' => 'AV','letters' => '\uA738\uA73A',
		'base' => 'AY','letters' => '\uA73C',
		'base' => 'B', 'letters' => '\u0042\u24B7\uFF22\u1E02\u1E04\u1E06\u0243\u0182\u0181',
		'base' => 'C', 'letters' => '\u0043\u24B8\uFF23\u0106\u0108\u010A\u010C\u00C7\u1E08\u0187\u023B\uA73E',
		'base' => 'D', 'letters' => '\u0044\u24B9\uFF24\u1E0A\u010E\u1E0C\u1E10\u1E12\u1E0E\u0110\u018B\u018A\u0189\uA779',
		'base' => 'DZ','letters' => '\u01F1\u01C4',
		'base' => 'Dz','letters' => '\u01F2\u01C5',
		'base' => 'E', 'letters' => '\u0045\u24BA\uFF25\u00C8\u00C9\u00CA\u1EC0\u1EBE\u1EC4\u1EC2\u1EBC\u0112\u1E14\u1E16\u0114\u0116\u00CB\u1EBA\u011A\u0204\u0206\u1EB8\u1EC6\u0228\u1E1C\u0118\u1E18\u1E1A\u0190\u018E',
		'base' => 'F', 'letters' => '\u0046\u24BB\uFF26\u1E1E\u0191\uA77B',
		'base' => 'G', 'letters' => '\u0047\u24BC\uFF27\u01F4\u011C\u1E20\u011E\u0120\u01E6\u0122\u01E4\u0193\uA7A0\uA77D\uA77E',
		'base' => 'H', 'letters' => '\u0048\u24BD\uFF28\u0124\u1E22\u1E26\u021E\u1E24\u1E28\u1E2A\u0126\u2C67\u2C75\uA78D',
		'base' => 'I', 'letters' => '\u0049\u24BE\uFF29\u00CC\u00CD\u00CE\u0128\u012A\u012C\u0130\u00CF\u1E2E\u1EC8\u01CF\u0208\u020A\u1ECA\u012E\u1E2C\u0197',
		'base' => 'J', 'letters' => '\u004A\u24BF\uFF2A\u0134\u0248',
		'base' => 'K', 'letters' => '\u004B\u24C0\uFF2B\u1E30\u01E8\u1E32\u0136\u1E34\u0198\u2C69\uA740\uA742\uA744\uA7A2',
		'base' => 'L', 'letters' => '\u004C\u24C1\uFF2C\u013F\u0139\u013D\u1E36\u1E38\u013B\u1E3C\u1E3A\u0141\u023D\u2C62\u2C60\uA748\uA746\uA780',
		'base' => 'LJ','letters' => '\u01C7',
		'base' => 'Lj','letters' => '\u01C8',
		'base' => 'M', 'letters' => '\u004D\u24C2\uFF2D\u1E3E\u1E40\u1E42\u2C6E\u019C',
		'base' => 'N', 'letters' => '\u004E\u24C3\uFF2E\u01F8\u0143\u00D1\u1E44\u0147\u1E46\u0145\u1E4A\u1E48\u0220\u019D\uA790\uA7A4',
		'base' => 'NJ','letters' => '\u01CA',
		'base' => 'Nj','letters' => '\u01CB',
		'base' => 'O', 'letters' => '\u004F\u24C4\uFF2F\u00D2\u00D3\u00D4\u1ED2\u1ED0\u1ED6\u1ED4\u00D5\u1E4C\u022C\u1E4E\u014C\u1E50\u1E52\u014E\u022E\u0230\u00D6\u022A\u1ECE\u0150\u01D1\u020C\u020E\u01A0\u1EDC\u1EDA\u1EE0\u1EDE\u1EE2\u1ECC\u1ED8\u01EA\u01EC\u00D8\u01FE\u0186\u019F\uA74A\uA74C',
		'base' => 'OI','letters' => '\u01A2',
		'base' => 'OO','letters' => '\uA74E',
		'base' => 'OU','letters' => '\u0222',
		'base' => 'OE','letters' => '\u008C\u0152',
		'base' => 'oe','letters' => '\u009C\u0153',
		'base' => 'P', 'letters' => '\u0050\u24C5\uFF30\u1E54\u1E56\u01A4\u2C63\uA750\uA752\uA754',
		'base' => 'Q', 'letters' => '\u0051\u24C6\uFF31\uA756\uA758\u024A',
		'base' => 'R', 'letters' => '\u0052\u24C7\uFF32\u0154\u1E58\u0158\u0210\u0212\u1E5A\u1E5C\u0156\u1E5E\u024C\u2C64\uA75A\uA7A6\uA782',
		'base' => 'S', 'letters' => '\u0053\u24C8\uFF33\u1E9E\u015A\u1E64\u015C\u1E60\u0160\u1E66\u1E62\u1E68\u0218\u015E\u2C7E\uA7A8\uA784',
		'base' => 'T', 'letters' => '\u0054\u24C9\uFF34\u1E6A\u0164\u1E6C\u021A\u0162\u1E70\u1E6E\u0166\u01AC\u01AE\u023E\uA786',
		'base' => 'TZ','letters' => '\uA728',
		'base' => 'U', 'letters' => '\u0055\u24CA\uFF35\u00D9\u00DA\u00DB\u0168\u1E78\u016A\u1E7A\u016C\u00DC\u01DB\u01D7\u01D5\u01D9\u1EE6\u016E\u0170\u01D3\u0214\u0216\u01AF\u1EEA\u1EE8\u1EEE\u1EEC\u1EF0\u1EE4\u1E72\u0172\u1E76\u1E74\u0244',
		'base' => 'V', 'letters' => '\u0056\u24CB\uFF36\u1E7C\u1E7E\u01B2\uA75E\u0245',
		'base' => 'VY','letters' => '\uA760',
		'base' => 'W', 'letters' => '\u0057\u24CC\uFF37\u1E80\u1E82\u0174\u1E86\u1E84\u1E88\u2C72',
		'base' => 'X', 'letters' => '\u0058\u24CD\uFF38\u1E8A\u1E8C',
		'base' => 'Y', 'letters' => '\u0059\u24CE\uFF39\u1EF2\u00DD\u0176\u1EF8\u0232\u1E8E\u0178\u1EF6\u1EF4\u01B3\u024E\u1EFE',
		'base' => 'Z', 'letters' => '\u005A\u24CF\uFF3A\u0179\u1E90\u017B\u017D\u1E92\u1E94\u01B5\u0224\u2C7F\u2C6B\uA762',
		'base' => 'a', 'letters' => '\u0061\u24D0\uFF41\u1E9A\u00E0\u00E1\u00E2\u1EA7\u1EA5\u1EAB\u1EA9\u00E3\u0101\u0103\u1EB1\u1EAF\u1EB5\u1EB3\u0227\u01E1\u00E4\u01DF\u1EA3\u00E5\u01FB\u01CE\u0201\u0203\u1EA1\u1EAD\u1EB7\u1E01\u0105\u2C65\u0250',
		'base' => 'aa','letters' => '\uA733',
		'base' => 'ae','letters' => '\u00E6\u01FD\u01E3',
		'base' => 'ao','letters' => '\uA735',
		'base' => 'au','letters' => '\uA737',
		'base' => 'av','letters' => '\uA739\uA73B',
		'base' => 'ay','letters' => '\uA73D',
		'base' => 'b', 'letters' => '\u0062\u24D1\uFF42\u1E03\u1E05\u1E07\u0180\u0183\u0253',
		'base' => 'c', 'letters' => '\u0063\u24D2\uFF43\u0107\u0109\u010B\u010D\u00E7\u1E09\u0188\u023C\uA73F\u2184',
		'base' => 'd', 'letters' => '\u0064\u24D3\uFF44\u1E0B\u010F\u1E0D\u1E11\u1E13\u1E0F\u0111\u018C\u0256\u0257\uA77A',
		'base' => 'dz','letters' => '\u01F3\u01C6',
		'base' => 'e', 'letters' => '\u0065\u24D4\uFF45\u00E8\u00E9\u00EA\u1EC1\u1EBF\u1EC5\u1EC3\u1EBD\u0113\u1E15\u1E17\u0115\u0117\u00EB\u1EBB\u011B\u0205\u0207\u1EB9\u1EC7\u0229\u1E1D\u0119\u1E19\u1E1B\u0247\u025B\u01DD',
		'base' => 'f', 'letters' => '\u0066\u24D5\uFF46\u1E1F\u0192\uA77C',
		'base' => 'g', 'letters' => '\u0067\u24D6\uFF47\u01F5\u011D\u1E21\u011F\u0121\u01E7\u0123\u01E5\u0260\uA7A1\u1D79\uA77F',
		'base' => 'h', 'letters' => '\u0068\u24D7\uFF48\u0125\u1E23\u1E27\u021F\u1E25\u1E29\u1E2B\u1E96\u0127\u2C68\u2C76\u0265',
		'base' => 'hv','letters' => '\u0195',
		'base' => 'i', 'letters' => '\u0069\u24D8\uFF49\u00EC\u00ED\u00EE\u0129\u012B\u012D\u00EF\u1E2F\u1EC9\u01D0\u0209\u020B\u1ECB\u012F\u1E2D\u0268\u0131',
		'base' => 'j', 'letters' => '\u006A\u24D9\uFF4A\u0135\u01F0\u0249',
		'base' => 'k', 'letters' => '\u006B\u24DA\uFF4B\u1E31\u01E9\u1E33\u0137\u1E35\u0199\u2C6A\uA741\uA743\uA745\uA7A3',
		'base' => 'l', 'letters' => '\u006C\u24DB\uFF4C\u0140\u013A\u013E\u1E37\u1E39\u013C\u1E3D\u1E3B\u017F\u0142\u019A\u026B\u2C61\uA749\uA781\uA747',
		'base' => 'lj','letters' => '\u01C9',
		'base' => 'm', 'letters' => '\u006D\u24DC\uFF4D\u1E3F\u1E41\u1E43\u0271\u026F',
		'base' => 'n', 'letters' => '\u006E\u24DD\uFF4E\u01F9\u0144\u00F1\u1E45\u0148\u1E47\u0146\u1E4B\u1E49\u019E\u0272\u0149\uA791\uA7A5',
		'base' => 'nj','letters' => '\u01CC',
		'base' => 'o', 'letters' => '\u006F\u24DE\uFF4F\u00F2\u00F3\u00F4\u1ED3\u1ED1\u1ED7\u1ED5\u00F5\u1E4D\u022D\u1E4F\u014D\u1E51\u1E53\u014F\u022F\u0231\u00F6\u022B\u1ECF\u0151\u01D2\u020D\u020F\u01A1\u1EDD\u1EDB\u1EE1\u1EDF\u1EE3\u1ECD\u1ED9\u01EB\u01ED\u00F8\u01FF\u0254\uA74B\uA74D\u0275',
		'base' => 'oi','letters' => '\u01A3',
		'base' => 'ou','letters' => '\u0223',
		'base' => 'oo','letters' => '\uA74F',
		'base' => 'p','letters' => '\u0070\u24DF\uFF50\u1E55\u1E57\u01A5\u1D7D\uA751\uA753\uA755',
		'base' => 'q','letters' => '\u0071\u24E0\uFF51\u024B\uA757\uA759',
		'base' => 'r','letters' => '\u0072\u24E1\uFF52\u0155\u1E59\u0159\u0211\u0213\u1E5B\u1E5D\u0157\u1E5F\u024D\u027D\uA75B\uA7A7\uA783',
		'base' => 's','letters' => '\u0073\u24E2\uFF53\u00DF\u015B\u1E65\u015D\u1E61\u0161\u1E67\u1E63\u1E69\u0219\u015F\u023F\uA7A9\uA785\u1E9B',
		'base' => 't','letters' => '\u0074\u24E3\uFF54\u1E6B\u1E97\u0165\u1E6D\u021B\u0163\u1E71\u1E6F\u0167\u01AD\u0288\u2C66\uA787',
		'base' => 'tz','letters' => '\uA729',
		'base' => 'u','letters' => '\u0075\u24E4\uFF55\u00F9\u00FA\u00FB\u0169\u1E79\u016B\u1E7B\u016D\u00FC\u01DC\u01D8\u01D6\u01DA\u1EE7\u016F\u0171\u01D4\u0215\u0217\u01B0\u1EEB\u1EE9\u1EEF\u1EED\u1EF1\u1EE5\u1E73\u0173\u1E77\u1E75\u0289',
		'base' => 'v','letters' => '\u0076\u24E5\uFF56\u1E7D\u1E7F\u028B\uA75F\u028C',
		'base' => 'vy','letters' => '\uA761',
		'base' => 'w','letters' => '\u0077\u24E6\uFF57\u1E81\u1E83\u0175\u1E87\u1E85\u1E98\u1E89\u2C73',
		'base' => 'x','letters' => '\u0078\u24E7\uFF58\u1E8B\u1E8D',
		'base' => 'y','letters' => '\u0079\u24E8\uFF59\u1EF3\u00FD\u0177\u1EF9\u0233\u1E8F\u00FF\u1EF7\u1E99\u1EF5\u01B4\u024F\u1EFF',
		'base' => 'z','letters' => '\u007A\u24E9\uFF5A\u017A\u1E91\u017C\u017E\u1E93\u1E95\u01B6\u0225\u0240\u2C6C\uA763'
	);
	
	$diacriticsMap = array();
	for ($i=0; $i < count($defaultDiacriticsRemovalMap); $i++){
		$letters = $defaultDiacriticsRemovalMap[i]['letters'];
		for ($j=0; $j < count($letters); $j++){
        	$diacriticsMap[$letters[$j]] = $defaultDiacriticsRemovalMap[$i]['base'];
    	}
	}
	
	$templist = array();
	if (is_array($argument)) {
		$templist = $argument;
	}else{
		$templist[] = $argument;
	}

	for($i=0; $i < count($templist); $i++) {
		$tempstring = $templist[$i];
		$tempstring = preg_replace_callback('/[^\u0000-\u007E]/g', function($a) use ($diacriticsMap) {
			return $diacriticsMap[$a] || $a;
		}, $templist[$i]);
	
		$newtempstring = '';
		for($c=0; $c < count($tempstring); $c++){
			$charcode = ord($tempstring[$c]);
			if($charcode < 32 || $charcode > 127) {
				$newtempstring += "?";
			}else{
				$newtempstring += ord($tempstring[$c]);
			}
		}
		if($flags){
			if(false !== strpos($newtempstring, '?')){
				$newtempstring = '';
			}
		}
	
		$templist[$i] = $newtempstring;
	}
	return (is_array($argument) ? $templist : $templist[0]);
}

/**
 * Returns the arcsine, in radians, of a number between -1 and 1, inclusive.
 * 
 * @param mixed $argument
 * @return float[]
 */
function _ASin($argument)
{
	if (is_array($argument)) {
		if (empty($argument)) return array();
		foreach ($argument as $index => $value) {
			$value = floatval($value);
			$argument[$index] = asin($value) ? asin($value) : '';
		}
	}
	else {
		if (trim($argument) === '') return '';
		$argument = asin($argument) ? asin($argument) : '';
	}
	return $argument;
}

/**
 * Returns the arctangent, in radians, of a number.
 *
 * @param mixed $argument
 * @return float[]
 */
function _ATan($argument)
{
	if (is_array($argument)) {
		if (empty($argument)) return array();
		foreach ($argument as $index => $value) {
			$value = floatval($value);
			$argument[$index] = atan($value) ? atan($value) : '';
		}
	}
	else {
		if (trim($argument) === '') { return ''; }
		$argument = atan($argument) ? atan($argument) : '';
	}
	return $argument;
}

/**
 * Returns the polar coordinate angle, in radians, of a point in the Cartesian plane.
 * 
 * @param mixed $argument1
 * @param mixed $argument2
 * @return float[]
 */
function _ATan2($argument1, $argument2)
{
	if (is_array($argument1) && is_array($argument2)) {
		if (count($argument1) != count($argument2)) {
			return array();
		}
			
		$result = array();
		for ($x = 0; $x < count($argument1); $x++)
		{
			$result[] = atan2($argument2[$x], $argument1[$x]);
		}
		return $result;
	}
	elseif (is_array($argument1) && !is_array($argument2)) {
		$result = array();
		if (empty($argument1) || trim($argument2) === '') return $result;
		for ($x = 0; $x < count($argument1); $x++)
		{
			$result[] = atan2($argument2, $argument1[$x]);
		}
		return $result;
	}
	elseif (!is_array($argument1) && is_array($argument2)) {
		$result = array();
		if (trim($argument1) === '' || empty($argument2)) return $result;
		for ($x = 0; $x < count($argument2); $x++)
		{
			$result[] = atan2($argument2[$x], $argument1);
		}
		return $result;
	}
	else {
		if (trim($argument1) === '' || trim($argument2) === '') return '';
		return atan2($argument2, $argument1);
	}
}

/**
 * Returns the cosine of an angle.
 * 
 * @param mixed $argument
 * @return float[]
 */
function _Cos($argument)
{
	if (is_array($argument)) {
		if (empty($argument)) { return array();	}
		foreach ($argument as $index => $value) {
			$value = floatval($value);
			$argument[$index] = acos($value) ? acos($value) : '';
		}
	}
	else {
		if (trim($argument) === '') { return ''; }
		$argument = acos($argument) ? acos($argument) : '';
	}
	return $argument;
}

/**
 * Converts a numeric value or string value to a date/time value.
 * 
 * @param string|number $expr
 * @return \DateTime
 */
function _CDat($expr)
{
	if (empty($expr))
		return \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', 0));
		
	if (is_numeric($expr)) {
		$expr = intval($expr);
		try {
			$date = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', $expr));
			return $date;
		}
		catch (\Exception $e) {
			throw new \Exception("Date conversion failed in \"{$e->getFile()}\" on line {$e->getLine()}.");
		}
	}
	elseif (is_string($expr)) {
		try {
			$date = new \DateTime($expr);
			return $date;
		}
		catch (\Exception $e) {
			throw new \Exception("Date conversion failed in \"{$e->getFile()}\" on line {$e->getLine()}.");
		}
	}
	else {
		throw new \Exception('Invalid datatype. Numeric or String values are acceptable.');
	}
}

/**
 * Returns the current system date as a date/time value.
 * 
 * @return \DateTime
 */
function _Date()
{
	$date = new \DateTime();
	$date->setTime(0, 0, 0);
	return $date;
}

/**
 * Returns a date value for a given set of year, month, and day numbers.
 * 
 * @param integer $year
 * @param integer $month
 * @param integer $day
 * @throws \OutOfBoundsException|\Exception
 * @return \DateTime
 */
function _DateNumber($year, $month, $day)
{
	$year = empty($year) ?  1970 : intval($year);
	if ($year < 10) {
		$year = intval('200'.$year);
	}
	elseif ($year < 50) {
		$year = intval('20'.$year);
	}
	elseif ($year < 1000) {
		$year = 1970;
	}
	
	$month = intval($month);
	if (empty($month) || abs($month) > 12)
		throw new \OutOfBoundsException('Out of boundary month value.');
	
	if ($month < 0) {
		$month = 12 + $month;
		$year--;
	}
	
	$day = intval($day);
	if (empty($day) || abs($day) > 31)
		throw new \OutOfBoundsException('Out of boundary day value.');
	
	if ($day < 0) {
		$day = 31 + $day;
		$month--;
		if ($month <= 0) {
			$month = 12 + $month;
			$year--;
		}
	}
	
	try {
		$date = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', mktime(0, 0, 0, $month, $day, $year)));
		return $date;
	}
	catch (\Exception $e) {
		throw new \Exception("Date conversion failed in \"{$e->getFile()}\" on line {$e->getLine()}");
	}
}

/**
 * Formats a number, a date/time, or a string according to a supplied format.
 * 
 * @param mixed $expr
 * @param string $format
 */
function _Format($expr, $format = '')
{
    if (empty($expr)){
		return $expr;
    }
    
	$result = $expr;
	$thousand_sep = $decimal_sep = $date_format = '';
	$isNumeric = $isDate = false;
	
	if ($expr instanceof \DateTime) {
      $tmpVal = $expr;
	  $isDate = true;
	}else{
	    try{
	       $tmpVal = new \DateTime($expr);	       
           $isDate = true;
	    }catch(\Exception $e){	        
	    }
	}
	
	if($isDate){
	    $date_format = _GetDateFormat();
	    $time_format = _GetTimeFormat();
	    $timezone = _GetUserTimeZone();
	    if(!empty($timezone)){
	        $tmpVal->setTimeZone(new \DateTimeZone($timezone));
	    }
	}else{    
	    $tmpVal = $expr;
	    try{
    	    	if (is_numeric($tmpVal)) {
	           if(gettype($tmpVal) == "string"){
	               $tmpVal = floatval($tmpVal);
	           }
	           $isNumeric = true;

	           $defnumfmt = _GetNumberFormat();	           
    	       	   $number = substr($defnumfmt, 0, strlen($defnumfmt)-2);
    	       	   $thousand_sep = false !== strpos($number, '.') ? '.' : (false !== strpos($number, ',') ? ',' : (false !== strpos($number, ' ') ? ' ' :  ''));
    	       	   $number = substr($defnumfmt, strlen($defnumfmt)-2);
    	       	   $decimal_sep = false !== strpos($number, '.') ? '.' : (false !== strpos($number, ',') ? ',' : (false !== strpos($number, ' ') ? ' ' : ''));
    	     	}
	    }catch(\Exception $e){	        
	    }
	}
	

	switch ($format) {
		case 'General Number':
		    if ($isNumeric === true){
				$result = strval($tmpVal);
		    }
			break;
		case 'Currency':
			if ($isNumeric === true)
			{
				$result = number_format($tmpVal, 2, $decimal_sep, $thousand_sep);
			}
			break;
		case 'Fixed':
		    if ($isNumeric === true){
				$result = number_format($tmpVal, 2, $decimal_sep);
		    }
			break;
		case 'Standard':
			if ($isNumeric === true)
			{
				$result = number_format($tmpVal, 2, $decimal_sep, $thousand_sep);
			}
			break;
		case 'Percent':
			if ($isNumeric === true)
			{
				$result = strval(100 * $tmpVal);
				if (substr(strval(round((($tmpVal * 100) % 1), 2)), 2) != '00') {
					$result .= $decimal_sep !== '' ? $decimal_sep : '.';
					$result .= substr(strval(round((($tmpVal * 100) % 1), 2)), 2);
				}
				$result .= '%';
			}
			break;
		case 'Scientific':
			if ($isNumeric === true) {
				$result = exp($tmpVal);
			}
			break;
		case 'Yes/No':
			if ($isNumeric === true)
			{
			    if ($tmpVal == 0){
					$result = 'No';
			    }else{
					$result = 'Yes';
			    }
			}
			break;
		case 'True/False':
			if ($isNumeric === true)
			{
			    if ($tmpVal == 0){
					return 'False';
			    }else{
					return 'True';
			    }
			}
			break;
		case 'On/Off':
			if ($isNumeric === true)
			{
			    if ($tmpVal == 0){
					return 'Off';
			    }else{
					return 'On';
			    }
			}
			break;
		case 'General Date':
			if ($isDate === true)
			{
				$result = _FormatDate($tmpVal, $date_format);
			}
			break;
		case 'Long Date':
			if ($isDate === true)
			{
				$result = $tmpVal->format('F j, Y');
			}
			break;
		case 'Medium Date':
			if ($isDate === true)
			{
				$result = $tmpVal->format('d-M-y');
			}
			break;
		case 'Short Date':
			if ($isDate === true)
			{			    
				$result = _FormatDate($tmpVal, $date_format);
			}
			break;
		case 'Long Time':
			if ($isDate === true)
			{
				$result = $tmpVal->format('H:i:s');
			}
			break;
		case 'Medium Time':
			if ($isDate === true)
			{
				$result = $tmpVal->format('h:i A');
			}
			break;
		case 'Short Time':
			if ($isDate === true)
			{
				$result = $tmpVal->format('H:i');
			}
			break;
		default:
			if ($isDate === true)
			{
			    $result = _FormatDate($tmpVal, trim($date_format + " " + $time_format));
			}
			elseif ($isNumeric === true)
			{
				$result = strval($tmpVal);
			}
			else {
				$result = $tmpVal;
			}
			break;
	}
	return $result;
}

/**
 * Formats a DateTime object to the supplied format
 * 
 * @param \DateTime $date
 * @param string $format
 * @return string
 */
function _FormatDate($date, $format)
{
    if (empty($date) || !($date instanceof \DateTime)){
		return '';
    }
       
	
	$final_format = $format;
	$isampm = ((false !== strpos(strtolower($final_format), "am/pm")) || (false !== strpos(strtolower($final_format), "a/p")) || (false !== strpos(strtolower($final_format), "ampm")));
	
	//y single
	$final_format = preg_replace('/(?<!y)y(?!y)/', 'z', $final_format);
	   
    //hh
    $final_format = str_replace('hh', ($isampm ? 'h' : 'H'), $final_format);
    //h
    $final_format = str_replace('h', ($isampm ? 'g' : 'G' ), $final_format);
    //nn
   	$final_format = str_replace('nn', 'i', $final_format);
	//n
    $final_format = str_replace('n', 'i', $final_format);
    //ss
    $final_format = str_replace('ss', 's', $final_format);
    //ttttt
	$final_format = str_replace('ttttt', 'h:i:s', $final_format);
	//AMPM
	$final_format = str_replace(array('AM/PM', 'A/P', 'AMPM'), 'A', $final_format);
	//ampm
	$final_format = str_replace(array('am/pm', 'a/p', 'ampm'), 'a', $final_format);
	//yyyy
	$final_format = str_replace('yyyy', 'Y', $final_format);
	//yy
	$final_format = str_replace('yy', 'y', $final_format);
	//d single
	$final_format = preg_replace('/(?<!d)d(?!d)/', 'j', $final_format);
	//dddddd
	$final_format = str_replace('dddddd', 'F d, Y', $final_format);
	//ddddd
	$final_format = str_replace('ddddd', 'm/d/y', $final_format);
	//dddd
	$final_format = str_replace('dddd', 'l', $final_format);
	//dd
	$final_format = str_replace('dd', 'd', $final_format);
    //mmmm
	$final_format = str_replace('mmmm', 'F', $final_format);
	//mmm
	$final_format = str_replace('mmm', 'M', $final_format);
	//mm
	$final_format = str_replace('mm', 'm', $final_format);
	//ww
	$final_format = str_replace('ww', 'W', $final_format);	
	//c
	$final_format = str_replace('c', 'd/m/y h:i:s', $final_format);
	
	
	$output = $date->format($final_format);
	if (false !== strpos($output, 'q')) {
		$quarter = '';
		$q = intval($date->format('z'));
		if ($q <= 90)
			$quarter = '1';
			elseif ($q <= 181)
			$quarter = '2';
			elseif ($q <= 273)
			$quarter = '3';
			else
				$quarter = '4';
				$output = str_replace('q', $quarter, $output);
	}

	return $output;
}

/**
 * Returns the position of the character that begins the first occurrence of one string within another string.
 * 
 * @param string|number $param
 * @param string $string
 * @param string $last_string
 * @param number $comptype
 * @return number
 */
function _instr($param, $string, $last_string = null, $comptype = 0)
{
	$result = $offset = 0;
	$source = $needle = '';
	if (is_numeric($param))
	{
		$offset = $param;
		$source = $string;
		$needle = $last_string;
		if (!is_numeric($comptype))
			$comptype = 0;
	}
	else {
		$source = $param;
		$needle = $string;
		if (is_numeric($last_string))
			$comptype = $last_string;
	}
	
	if ($comptype === 1)
	{
		if (empty($offset))
			$result = stripos($source, $needle);
		else 
			$result = stripos($source, $needle, $offset);
	}
	elseif ($comptype === 4) {
		$source = _ascii($source);
		$needle = _ascii($needle);
		if (empty($offset))
			$result = strpos($source, $needle);
		else 
			$result = strpos($source, $needle, $offset);
	}
	elseif ($comptype === 5) {
		$source = _ascii($source);
		$needle = _ascii($needle);
		if (empty($offset))
			$result = stripos($source, $needle);
		else
			$result = stripos($source, $needle, $offset);
	}
	else {
		if (empty($offset))
			$result = strpos($source, $needle);
		else
			$result = strpos($source, $needle, $offset);
	}
	return $result === false ? 0 : $result + 1;
}



/**
 * Returns the upper bound for one dimension of an array.
 * 
 * @param Array $array
 * @return boolean|mixed
 */
function _UBound($array)
{
	$result = false;
	if (is_array($array))
	{
		$keys = array_keys($array);
		if (count(array_filter($keys, 'is_string')) == 0)
		{
			$result = end($keys);
		}
	}
	return $result;
}

/**
 * Change all string(s) characters to upercase
 * 
 * @param string[] $argument
 * @return string|mixed
 */
function _UpperCase($argument)
{
	if (!is_array($argument) && !is_string($argument)) return '';
	if (empty($argument)) return $argument;
	if (is_array($argument))
	{
		for ($x = 0; $x < count($argument); $x++) {
			$argument[$x] = strtoupper($argument[$x]);
		}
	}
	else {
		$argument = strtoupper($argument);
	}
	return $argument;
}

function _ArrayGetIndex($haystack, $needle, $compmethod){

	if (!is_array($haystack) || empty($haystack) || (empty($needle) && $needle != '0'))
			return null;
		
	if ($compmethod == 1 || $compmethod == 5)
		$result = array_search(strtolower($needle), array_map('strtolower', $haystack));
	else 
		$result = array_search($needle, $haystack);
	
	return $result !== false ? $result : null;
}


function _ArrayRemoveItem($sourcearray, $element){
	
	if (!is_array($sourcearray) || empty($sourcearray) || (empty($element) && $element != 0)){
		return null;
	}

	$result = $sourcearray;
	
	$pos = null;
	if(gettype($element) == "string"){
		$pos = array_search($element, $sourcearray);	
	}else if(is_numeric($element)){
		$pos = intval($element);
	}
	
	
	
	if(!is_null($pos)){
		if($pos < sizeof($result)){
			array_splice($result, $pos, 1);
		}
	}
	
		
	return $result;
}



/**
 * Char twig script (Ascii to character)
 * 
 * @param string[] $argument
 * @return string[]
 */
function _Char($argument)
{
	if (empty($argument)) {
		return is_array($argument) ? array() : '';
	}
	
	if (is_array($argument)) {
		foreach ($argument as $index => $value) {
			$argument[$index] = !empty($value) ? chr($value) : '';
		}
	}
	else {
		$argument = chr($argument);
	}
	return $argument;
}

function _Join($inarray, $delm)
{
	return implode ( $delm , $inarray );
}

/**
 * Removes duplicate elements from an Array
 * 
 * @param array $source_array
 * @param integer $compmethod
 * @return NULL|array
 */
function _ArrayUnique($source_array, $compmethod = 0)
{
	if (!is_array($source_array) || empty($source_array))
		return null;
	
	if ($compmethod == 1 || $compmethod == 5)
	{
		$lowered_source = array_map('strtolower', $source_array);
		$result = array_intersect_key($source_array, array_unique($lowered_source));
	}
	else 
		$result = array_values(array_unique($source_array));
	
	return $result;
}


/** 
* get the default date format
* @param string $formatstyle
* @return string
**/
function _GetDateFormat($formatstyle=null)
{
    if(isset($formatstyle) && strtolower($formatstyle) == "php" ){
        $date_format = 'Y-m-d';
    }else{
        $date_format = 'yyyy-mm-dd';
    }

    
	global $docova;
	if(!empty($docova) && ($docova instanceof Docova)){
	    $gs = $docova->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
	    $gs = $gs[0];
	    $date_format = $gs->getDefaultDateFormat();
	    if(isset($formatstyle) && strtolower($formatstyle)=="php"){
	        $date_format = str_replace(array('YYYY', 'MM', 'DD'), array('Y', 'm', 'd'), $date_format);
	    }else{
	        $date_format = str_replace(array('YYYY', 'MM', 'DD'), array('yyyy', 'mm', 'dd'), $date_format);
	    }
	}

	
    return $date_format;
}

/**
 * get the default time format
 * @param string $formatstyle
 * @return string
 **/
function _GetTimeFormat($formatstyle=null)
{
    if(isset($formatstyle) && strtolower($formatstyle) == "php" ){
        $time_format = 'g:m:s A';
    }else{
        $time_format = 'h:nn:ss AM/PM';
    }
    
    return $time_format;
}


/**
 * get the users time zone
 **/
function _GetUserTimeZone()
{
    $timezone = '';
    
    global $docova;
    if(!empty($docova) && ($docova instanceof Docova)){
        $session = $docova->DocovaSession();
    }
    
    if (!empty($session))
    {
        $user = $session->getCurrentUser();
        $tz = $user->getUserProfile()->getTimeZone();
        if(!empty($tz)){
            $timezone = $tz;
        }
    }
    $session = null;
    
    return $timezone;
}

/**
 * get default number format
 **/
function _GetNumberFormat(){
    $defnumfmt = '1234.5';
    
    global $docova;
    if(!empty($docova) && ($docova instanceof Docova)){
        $session = $docova->DocovaSession();
    }
    if (!empty($session))
    {
		$user = $session->getCurrentUser();
		if (!empty($user)) {
			$frm = $user->getUserProfile()->getNumberFormat();
			if(!empty($frm) && $frm != "None"){
				$defnumfmt = $frm;
			}
		}
    }
    $session = null;  
    
    return $defnumfmt;
}

/**
 * Convert value(s) to string
 * 
 * @param mixed $argument
 * @param string $formatstring
 * @return string[]
 */
function _Text($argument, $formatstring = null)
{
    if(!isset($argument)){return;}
    
    $defdatefmt = null;
    $deftimefmt = null;
    $defnumfmt = null;
    $timezone = null;
    
    $isarray = false;
    $tempresult = array();
    
    $isarray = is_array($argument);
    $templist = ($isarray ? $argument : [$argument]);
    for($i=0; $i<count($templist); $i++){
        $tempval = $templist[$i];
        if(gettype($tempval) == 'string'){
            //--nothing to do already a string
        }else if(gettype($tempval) == 'object' && is_a($tempval, 'DateTime')){
            $defdatefmt = (empty($defdatefmt) ? _GetDateFormat() : $defdatefmt);
            $deftimefmt = (empty($deftimefmt) ? _GetTimeFormat() : $deftimefmt);
            
            $timezone = (empty($timezone) ? _GetUserTimeZone() : $timezone);
            if(!empty($timezone)){
                $tempval->setTimeZone(new \DateTimeZone($timezone));
            }

            //-- found a date so check to see if we have any formatting options
            $datefmt = $defdatefmt;
            $timefmt = $deftimefmt;
            
            $datesep = (strpos($datefmt, "-") > -1 ? "-" : (strpos($datefmt, "/") > -1 ? "/" : (strpos($datefmt, ".") > -1 ? "." : "/")));
            
            if(isset($formatstring) && $formatstring != ""){
                if(strpos($formatstring, "D0")!== false){
                    //-- leave date format as is
                }else if(strpos($formatstring,"D1")!== false){
                    //-- remove year if same as current year
                    if($tempval->format("Y") == date("Y")){
                        $datefmt = str_replace($datesep."yyyy", '', $datefmt);
                        $datefmt = str_replace("yyyy".$datesep, '', $datefmt);
                        $datefmt = str_replace($datesep.'yy', '', $datefmt);
                        $datefmt = str_replace('yy'.$datesep, '', $datefmt);
                    }
                }else if(strpos($formatstring, "D2")!== false){
                    //-- remove year
                    $datefmt = str_replace($datesep.'yyyy', '', $datefmt);
                    $datefmt = str_replace('yyyy'.$datesep, '', $datefmt);
                    $datefmt = str_replace($datesep.'yy', '', $datefmt);
                    $datefmt = str_replace('yy'.$datesep, '', $datefmt);
                }else if(strpos($formatstring, "D3")!== false){
                    //-- remove day
                    $datefmt = str_replace($datesep.'dd', '', $datefmt);
                    $datefmt = str_replace('dd'.$datesep, '', $datefmt);
                    $datefmt = str_replace($datesep.'d', '', $datefmt);
                    $datefmt = str_replace('d'.$datesep, '', $datefmt);
                }
                
                if(strpos($formatstring, "S0")!== false){
                    //-- remove time
                    $timefmt = '';
                }else if(strpos($formatstring, "S1")!== false){
                    //-- remove date
                    $datefmt = '';
                }else if(strpos($formatstring, "S2")!== false){
                    //--leave time format as is
                }
                
                if($timefmt != ''){
                    if(strpos($formatstring, "T0")!== false){
                        //-- leave time format as is
                    }else if(strpos($formatstring, "T1")!== false){
                        //-- remove seconds
                        $timefmt = str_replace(':ss', '', $timefmt);
                        $timefmt = str_replace(':s', '', $timefmt);
                    }
                }
            }
            $datetimefmt = trim($datefmt . " " . $timefmt);
            $tempval = _FormatDate($tempval, $datetimefmt);
        }else if(is_numeric($tempval)){
            //-- found a number so lets see if we have any formatting options
            $formatstring = (isset($formatstring) ? $formatstring : "");
            
            //Retrieve user defined numeric formatting criteria
            $defnumfmt = (empty($defnumfmt) ? _GetNumberFormat() : $defnumfmt);
            $number = substr($defnumfmt, 0, strlen($defnumfmt)-2);
            $thousand_sep = false !== strpos($number, '.') ? '.' : (false !== strpos($number, ',') ? ',' : (false !== strpos($number, ' ') ? ' ' : ''));
            $number = substr($defnumfmt, strlen($defnumfmt)-2);
            $decimal_sep = false !== strpos($number, '.') ? '.' : (false !== strpos($number, ',') ? ',' : (false !== strpos($number, ' ') ? ' ' : ''));
            
            $bracketneg = (strpos($formatstring, '()')!== false);
            $isfixed = (strpos($formatstring, 'F')!== false);
                
            $matches = array();
            if(preg_match('/\d+/', $formatstring, $matches)){
                 $sigdigits = $matches[0];
            }else{
                 $sigdigits = 2;
            }
                
            if(strpos($formatstring, ',')=== false){
                $thousand_sep = ''; 
            }else if($thousand_sep == ""){
                $thousand_sep = ",";  
            }
            
            if(strpos($formatstring, 'S')!== false){
                $newtempval = exp($bracketneg ? abs($tempval) : $tempval);
                if($bracketneg && $tempval < 0){
                    $newtempval = "(" . $newtempval . ")";
                }
                $tempval = $newtempval;
                
            }else if(strpos($formatstring, '%')!== false){
                $newtempval = strval(intval(($bracketneg ? abs($tempval) : $tempval) * 100));
                if($sigdigits > 0){
                    if(substr(number_format(fmod(abs($tempval) * 100, 1), $sigdigits, $decimal_sep, ""), 2) !== str_repeat("0", $sigdigits)){
                        $newtempval .= $decimal_sep;
                        $newtempval .= substr(number_format(fmod(abs($tempval) * 100, 1), $sigdigits, $decimal_sep, ""), 2);
                    }
                }
                $newtempval .= "%";
                if($bracketneg && $tempval < 0){
                    $newtempval = "(" . $newtempval . ")";
                }
                $tempval = $newtempval;
            }else if(strpos($formatstring, 'C')!== false){
                $newtempval = "$".number_format(intval(($bracketneg ? abs($tempval) : $tempval)), 0, "", $thousand_sep);
                if($sigdigits > 0){
                    $newtempval .= substr($decimal_sep . number_format(fmod(abs($tempval), 1), $sigdigits), 2);
                }
                if($bracketneg && $tempval < 0){
                    $newtempval = "(" . $newtempval . ")";
                }
                $tempval = $newtempval;
            }else{
                $newtempval = number_format(intval(($bracketneg ? abs($tempval) : $tempval)), 0, "", $thousand_sep);
                if($sigdigits > 0){
                    if(substr(number_format(fmod(abs($tempval), 1), $sigdigits, $decimal_sep, ""), 2) !== str_repeat("0", $sigdigits)){
                        $newtempval .= $decimal_sep;
                        $newtempval .= substr(number_format(fmod(abs($tempval), 1), $sigdigits, $decimal_sep, ""), 2);
                    }
                }
                if($bracketneg && $tempval < 0){
                    $newtempval = "(" . $newtempval . ")";
                }
                $tempval = $newtempval;
            }           
        }else{
            $tempval = strval($tempval);
        }
        $tempresult[] = $tempval;
    }
    
    return ($isarray ? $tempresult : $tempresult[0]);	
}

/**
 * Removes leading and trailing spaces from a string and returns the resulting string.
 * 
 * @param string[] $argument
 * @return string[]
 */
function _Trim($argument)
{
	if (!isset($argument) || is_null($argument)) return '';
	$result = array();
	if (is_array($argument)) {
		for ($x = 0; $x < count($argument); $x++) {
		    if ( isset($argument[$x])){
			    $tempval = trim(strval($argument[$x]));
			    if($tempval != ''){
    				array_push($result, $tempval);
			    }
		    }
		}
	}
	else {
	    $tempval = trim(strval($argument));
	    if($tempval != ''){
	        array_push($result, $tempval);
	    }
	}
	if(count($result) == 0){
	    $result = '';
	}else if(count($result) == 1){
	    $result = $result[0]; 
	}
	
	return $result;
}

/**
 * Concatenates all members of a text list and returns a text string.
 * 
 * @param array $list
 * @param string $glue
 * @return string
 */
function _Implode($list, $glue = '')
{
	$output = '';
	if (empty($list)) return $output;
	$glue = empty($glue) ? ' ' : $glue;
	if (is_array($list)) {
		$output = implode($glue, $list);
	}
	elseif (is_string($list)) {
		$output = $list;
	}
	return $output;
}

/**
 * Tests the value of an expression to determine whether it is a date/time value.
 * 
 * @param mixed $argument
 * @return boolean
 */
function _IsDate($argument)
{
	$result = false;
	if (empty($argument))
		return $result;
	
	if ($argument instanceof \DateTime)
		$result = true;
	else {
		try {
			$argument = new \DateTime($argument);
			$result = true;
		}
		catch (\Exception $e) {
			$result = false;
		}
	}
	return $result;
}

/**
 * Returns the nearest integer value that is less than or equal to a number
 * 
 * @param mixed $argument
 * @return number[]|NULL
 */
function _Int($argument)
{
	if (empty($argument))
		return null;
	
	$inputs = is_array($argument) ? $argument : array($argument);
	for ($x = 0; $x < count($inputs); $x++)
	{
		$inputs[$x] = intval($inputs[$x]);
	}

	return is_array($argument) ? $inputs : $inputs[0];
}

/**
 * Tests a string to determine whether it is a list tag for a given list.
 * 
 * @param array $list
 * @param string $expr
 * @return boolean
 */
function _IsElement($list, $expr = null)
{
	if (empty($list))
		return false;
	
	if (empty($expr))
		return true;
		
	return array_key_exists($expr, $list);
}

/**
 * Tests the value of an expression to determine whether it is EMPTY
 * 
 * @param mixed $argument
 */
function _IsEmpty($argument)
{
	return empty($argument);
}

/**
 * Checks the value of an expression to determine whether it is NULL
 * 
 * @param mixed $value
 * @return boolean
 */
function _IsNull($value)
{
	return $value === null;
}

/**
 * Checks the value of an expression to determine whether it is numeric, or can be converted to a numeric value.
 * 
 * @param mixed $argument
 * @return boolean
 */
function _IsNumeric($argument)
{
	if (is_null($argument) || is_array($argument) || is_object($argument))
		return false;
	
	if (is_bool($argument) || empty($argument) || !is_nan($argument) || is_numeric($argument))
		return true;
}

/**
 * Returns the lower bound for one dimension of an array.
 * 
 * @param array $array
 * @return boolean|number
 */
function _LBound($array)
{
	$result = false;
	if (is_array($array))
	{
		$keys = array_keys($array);
		if (count(array_filter($keys, 'is_string')) == 0)
		{
			$result = $keys[0];
		}
	}
	return $result;
}

/**
 * Returns the lowercase representation of a string.
 * 
 * @param string[] $argument
 * @return string[]
 */
function _LCase($argument)
{
	if (!is_array($argument) && !is_string($argument)) return '';
	if (empty($argument)) return $argument;
	if (is_array($argument))
	{
		for ($x = 0; $x < count($argument); $x++) {
			$argument[$x] = strtolower($argument[$x]);
		}
	}
	else {
		$argument = strtolower($argument);
	}
	return $argument;
}

/**
 * Returns leftmost found characters of the string
 *
 * @param string[] $string_search
 * @param string|number $selection
 * @param integer $compmethod
 * @return string[]
 */
function _Left($string_search, $selection, $compmethod = 0)
{
	if (empty($string_search)) return '';
	$string_search = is_array($string_search) ? $string_search : array($string_search);
	$output = array();
	foreach ($string_search as $str) {
		if (is_numeric($selection)) {
			$selection = intval($selection);
			if ($selection > -1)
				$output[] = substr($str, 0, $selection);
				else
					$output[] = '';
		}
		else {
			if ($compmethod == 1 || $compmethod == 5) {
				$value = stristr($str, $selection, true);
				$output[] = $value !== false ? $value : '';
			}
			else {
				$value = strstr($str, $selection, true);
				$output[] = $value !== false ? $value : '';
			}
		}
	}

	return is_array($string_search) ? $output : $output[0];
}

/**
 * Extracts a specified number of the rightmost characters in a string.
 * 
 * @param string[] $search_string
 * @param string|number $selection
 * @param number $compmethod
 * @return string[]
 */
function _Right($search_string, $selection, $compmethod = 0)
{
	if (empty($search_string)) return '';
	$search_string = is_array($search_string) ? $search_string : array($search_string);
	$output = array();
	foreach ($search_string as $str) {
		if (is_numeric($selection)) {
			$selection = intval($selection);
			if ($selection > -1)
				$output[] = substr($str, -($selection));
				else
					$output[] = '';
		}
		else {
			if ($compmethod == 1 || $compmethod == 5) {
				$pos = strripos($str, $selection);
			}
			else {
				$pos = strrpos($str, $selection);
			}
			$output[] = ($pos !== false ? substr($str, $pos + strlen($selection)) : '');
		}
	}
	
	return is_array($search_string) ? $output : $output[0];
}

/**
 * Searches a string from left to right and returns the leftmost characters of the string.
 * 
 * @param string $expression
 * @param string $pattern
 * @param number $compmethod
 * @param number $occurence
 * @return string
 */
function _StrLeft($expression, $pattern, $compmethod = 0, $occurence = 1)
{
	if (empty($expression) || empty($pattern)) return '';
	
	$offset = 0;
	if ($compmethod == 1 || $compmethod == 5) {
		if (false === stripos($expression, $pattern))
			return '';

		for ($x = 0; $x < $occurence; $x++)
		{
			$pos = stripos($expression, $pattern, $offset);
			if ($pos !== false)
				$offset = $pos + strlen($pattern);
			else 
				break;
		}
		$pos = $offset;
	}
	else {
		if (false === strpos($expression, $pattern))
			return '';

		for ($x = 0; $x < $occurence; $x++)
		{
			$pos = strpos($expression, $pattern, $offset);
			if ($pos !== false)
				$offset = $pos + strlen($pattern);
			else 
				break;
		}
		$pos = $offset;
	}
	
	return substr($expression, 0, $pos);
}

/**
 * Searches a string from left to right and returns the rightmost characters of the string.
 *
 * @param string $expression
 * @param string $pattern
 * @param number $compmethod
 * @param number $occurence
 * @return string
 */
function _StrRight($expression, $pattern, $compmethod = 0, $occurence = 1)
{
	if (empty($expression) || empty($pattern)) return '';

	$offset = 0;
	if ($compmethod == 1 || $compmethod == 5) {
		if (false === stripos($expression, $pattern))
			return '';

			for ($x = 0; $x < $occurence; $x++)
			{
				$pos = stripos($expression, $pattern, $offset);
				if ($pos !== false)
					$offset = $pos + strlen($pattern);
					else
						break;
			}
			$pos = $offset;
	}
	else {
		if (false === strpos($expression, $pattern))
			return '';

			for ($x = 0; $x < $occurence; $x++)
			{
				$pos = strpos($expression, $pattern, $offset);
				if ($pos !== false)
					$offset = $pos + strlen($pattern);
					else
						break;
			}
			$pos = $offset;
	}

	return substr($expression, $pos);
}

/**
 * @param string $string_search
 * @param string $delm
 * @return array
 */
function _Split($string_search, $delm)
{
	return explode($delm, $string_search);
}
?>