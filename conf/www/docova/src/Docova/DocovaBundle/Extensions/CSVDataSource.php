<?php 
namespace Docova\DocovaBundle\Extensions;

//use Docova\DocovaBundle\Extensions\ExternalDataSource;
use Symfony\Component\HttpFoundation\Response;
/*
 * Simple class to read a csv data file into memory
 */
class CSVDataSource extends ExternalDataSource {
	private $delimiter = ",";
	private $csvFilePath = null;	
	private $offset = null;
	private $offsetChars = null;
	private $controller=null;
	
	public function __construct($controller,$csvFilePath,$start,$count,$search=null,$csvFieldDelimiter=",",$offset=0,$offsetChars=0,$docovaDocumentsOnly=false){		
		$this->csvFilePath = $csvFilePath;
		$this->delimiter=$csvFieldDelimiter;
		if (!\file_exists($csvFilePath))
			throw new \Exception("CSV file: ".$csvFilePath." can not be located!");
		$this->controller=$controller;
		$this->em=$this->getEntityManager($controller);
		$this->offset = $offset;
		$this->offsetChars = $offsetChars;
		
		$reportName=basename($this->csvFilePath,'.csv');
		$this->readCSVFile($start,$count,$search,$offset,$offsetChars);
		
		$sourceDate = new \DateTime();				
		$sourceDate->setTimestamp(filemtime($csvFilePath));
		
		parent::__construct($reportName, $this->fields, $this->data, $sourceDate, false,$docovaDocumentsOnly);
		
		
	}
	public static function getRowCountResponse($csvFilePath,$hasHeaderRow=true){		
		$count = CSVDataSource::getRowCount($csvFilePath,$hasHeaderRow);
		$result = '<?xml version="1.0" encoding="UTF-8"?>
		<Results><Result ID="Status">OK</Result><Result ID="Ret1">'.$count.'</Result></Results>';
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($result);
		return $response;
	}
	public static function readNLines(&$handle,$lines){
		$linecount = 0;
		
		$lineLimit = 32*1024;
		
		while(!feof($handle) && $linecount<$lines){
			$line = fgets($handle, $lineLimit);
			$linecount = $linecount + substr_count($line, PHP_EOL);
		}		
	}
	public static function getLine(&$handle,$lines){
		$linecount = 0;
	
		$lineLimit = 32*1024;
	
		if(!feof($handle) && $linecount<$lines){
			return fgets($handle, $lineLimit);			
		}
	}
	public static function deleteLine(&$handle,$lineToDelete,$offset,$offsetChars){
		if (! \is_numeric($lineToDelete) || $lineToDelete<1)
			return;
		 
		//Read up to the line to delete		
		self::readCSVFile(0, $lineToDelete-1, null, $offset, $offsetChars);
		
		//Now read beyond the line to delete without resetting the data
		self::readCSVFile($lineToDelete+1, 1000000, null, $offset, $offsetChars, false);
	}
	public static function readChars(&$handle,$length){
		if ($length<1)
			return null;
		else
			return fgets($handle, $length);		
	}
	public static function getRowCount($csvFilePath,$hasHeaderRow=true){		
		$linecount = 0;
		
		$handle = fopen($csvFilePath, "r");
		if (empty($handle) || feof($handle)){
			throw new \Exception("No data available in csv file!");
		}
		
		$lineLimit = 32*1024;
		while(!feof($handle)){			
			$line = fgets($handle, $lineLimit);
			$linecount = intval($linecount) + substr_count($line, PHP_EOL);
		}		
		fclose($handle);
		return $linecount>0 ? $linecount : 0;
	}
	
	public function updateCSVLine($line,$values){
		if ( empty($values) || !\is_array($values) || empty($this->data[$line])) {
			return;
		}
		$this->data[$line]=$values;
	}
	
	public function saveCSVFile(){
		$fp=fopen($this->csvFilePath,'w');
		
		//write the field headers
		fputcsv($fp,$this->fields,$this->delimiter);

		//write each data row
		foreach($this->data as $row){			
			//write the data row
			fputcsv($fp,$row,$this->delimiter);
		}
		
		fclose($fp);
	}
	
	private function readCSVFile($start,$count,$search=null,$offset=0,$offsetChars=0,$reset=true){
		//echo "Reading file: ".$this->csvFilePath." Start: ".$start." Count: ".$count.PHP_EOL;
		if ($reset)
			$this->data=array();
		$fp=fopen($this->csvFilePath,'r');
		
		$this->readNLines($fp, $offset);
		$this->readChars($fp, $offsetChars);
		
		//The first line must define the field names		
		$this->fields = fgetcsv($fp,null,$this->delimiter);
		if (!empty($this->fields) && !empty($this->fields[0]) && $this->fields[0]=='#Fields:'){
			
			$this->fields = \array_shift($this->fields);
		}
		//echo "Fields: ".json_encode($this->fields).PHP_EOL;
		$rowPosition=1;
		while ($start!=1 && $rowPosition<$start){
			//echo "Skipping line at ".$rowPosition.PHP_EOL;
			//Move to the start position
			fgetcsv($fp,null,$this->delimiter);
			$rowPosition++;
		}
		
		//The remaining lines define the field data
		
		//Read the data until the stop position if provided
		$rowsMatched=0;
		$start = $start==1 ? 0 : $start;		
		$start = $start==0 ? 0 : $start-1;		
		while ( ($dataRow=fgetcsv($fp,null,$this->delimiter)) && ($count==0 || ($rowPosition-$start)<=$count) ){
			$rowPosition++;
			$processRow=false;
			if (!empty($dataRow) && !empty($dataRow[0]) && $dataRow[0][0]=="#"){
				//break out and ignore the line, its been commented
				break;
			}
			if ($search!=null){
				//Search Limit of 500 results
				if ($rowsMatched>=500)
					break;
				$processRow=false;
				foreach($dataRow as $dataField){
					//$matches = \preg_match("/.*".\strtolower($search)."*/", \strtolower($dataField));
					//$matches = \preg_match("/".\strtolower($search)."*/", \strtolower($dataField));
					//field contains search key check (case insensitive)
					if (empty($search) || empty($dataField))
						$matches = false;
					else 
						$matches=!(\strpos(\strtolower($dataField),\strtolower($search))===false);
					
					if ($matches){
						$processRow=true;
						$rowsMatched++;
						break;
					}
				}				
				if ($matches==false)
					$processRow=false;
			}			
			else{
				$processRow=true;
			}	
			if ($processRow)
				$this->data[$rowPosition]=$dataRow;
		}
	//	echo "Read ".count($this->data). " records.".PHP_EOL;
		fclose($fp);
	}
	
}
?>