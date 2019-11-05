<?php

if (count($argv) != 4){
	echo "Parameter Missing: switchormdatatype.php <filepathorpattern> <fromtype> <totype>";
	exit;
} 


$filepattern = $argv[1];
$from = $argv[2];
$to = $argv[3];

$includepattern = '/(.*@ORM\\\\Column\(name=".*",.* type=")(';
$includepattern .= $from;
$includepattern .= ')(".*\).*)/';
	
$excludepattern = '/.*@ORM\\\\Column\(name=".*",.* length=.*\).*/';

$filelist = glob($filepattern, GLOB_NOESCAPE);
foreach ($filelist as $filename) { 
    $changemade = false;
	$result = '';
	$lines = file($filename);

	foreach($lines as $line) {
		if (preg_match($includepattern, $line) && ! preg_match($excludepattern, $line)){
			$result .= preg_replace($includepattern, '$1'.$to.'$3', $line);
			$changemade = true;
		} else {
			$result .= $line;
		}
	}
	if($changemade){
		copy($filename, $filename . ".bak");
		if(file_put_contents($filename, $result) !== false){
			unlink($filename . ".bak");  
		}
	}
}

