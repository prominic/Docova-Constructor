<?php 
namespace Docova\DocovaBundle\Extensions;

use Symfony\Component\HttpFoundation\Response;
//use Docova\DocovaBundle\Logging;
//use Docova\DocovaBundle\Logging\FileLogger;
use Docova\DocovaBundle\Controller\Miscellaneous;
/*
 * Simple class to generate Perspective XML from a list of fields and array of data
 */
class ExternalDataSource{
	private $name=null;
	private $fields=null;
	private $data=null;
	private $hasHeaders=false;
	private $sourceDate=null;
	private $docovaDocumentsOnly=false;

	public function __construct($name,$fields,$data,$sourceDate,$dataHasHeaders=false,$docovaDocumentsOnly=false){
		if (empty($name) || empty($fields) || !\is_array($fields) ){
			throw new \Exception("ExternalDataSource:: Invalid Parameters Received");
		}
				
		$this->name = $name;
		$this->fields = $fields;
		$this->data = $data;
		$this->sourceDate = $sourceDate;
		$this->hasHeaders = $dataHasHeaders;
		$this->docovaDocumentsOnly=$docovaDocumentsOnly;		
	}
	public function getDataRowCount(){
		if (empty($this->data))
			return 0;
		else
			return count($this->data);
	}
	public function getXmlResponse($folder_id=null,$fromSearch=false){		
		$data_xml='<?xml version="1.0" encoding="UTF-8" ?>'.PHP_EOL;
		$data_xml.='<documents>'.PHP_EOL;
		$rowId=0;
		if (empty($this->data))
			return;

		$ddOnly = $this->getDocovaDocumentsOnly();
		if ($ddOnly){		
			//determine the field index of the key docova field values		
			$idFieldIndex = $this->getFieldIndex('id');
			$docIdFieldIndex = $this->getFieldIndex('doc_id');
			$folderIdFieldIndex = $this->getFieldIndex('folder_id');
			if (empty($idFieldIndex) && empty($docIdFieldIndex)){
				throw new \Exception("DOCOVA document queries must provide the document UNID as column id or doc_id");
			}
		}
			
		foreach($this->data as $index => $row){
			$rowId++;
			if ($this->hasHeaders && $rowId==1)
				continue;
			
			//Start the XML document
			if ($ddOnly){
				//Pass the DOCOVA document and folder id to the row generation method
				//NOTE:  
				//1.) a document id field must be available as id or doc_id
				//2.) the folder id field must be available as folder_id to be used
				//3.) the current user must have access to the source document and folder								
				$documentId = !empty($row[$idFieldIndex]) ? $row[$idFieldIndex] : null;
				$documentId = empty($documentId) && !empty($row[$docIdFieldIndex]) ? $row[$docIdFieldIndex] : $documentId;				
				if (empty($row[$folderIdFieldIndex]))
					$data_xml .= $this->getRowXml($this->name,$folder_id,$documentId,$row);
				else
					$data_xml .= $this->getRowXml($this->name,$row[$folderIdFieldIndex],$documentId,$row);
			}
			else 
				$data_xml .= $this->getRowXml($this->name,$folder_id,$index,$row);
		}
		
		if ($fromSearch){
			$data_xml .= '<srchCount>'.$this->getDataRowCount().'</srchCount>';
			$data_xml .= '<moreResults>No</moreResults>';
			$data_xml .= '<status>OK</status>';
		}
		
		$data_xml.='</documents>';
		
		$response = new Response($data_xml);
		$response->headers->set('Content-Type', 'text/xml');
		$data_xml = null;
			
		return $response;
		
	}
	public function getDefaultPerspective(){
	
		$perspective = "<viewsettings><viewproperties><showSelectionMargin>1</showSelectionMargin><allowCustomization>1</allowCustomization><extendLastColumn/><isSummary/><isThumbnails/><categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle></viewproperties><columns>";
		$defaultColumn="<column><isCategorized/><hasCustomSort/><totalType>0</totalType><isFrozen/><isFreezeControl/><title/><xmlNodeName>bmk</xmlNodeName><dataType>html</dataType><sortOrder>none</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat>MM/DD/YYYY</dateFormat><width>20</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT>6pt</fontSizeT><fontFamilyT/><colorT/><fontWeightT/><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH>6pt</fontSizeH><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column>";
		$perspective.= $defaultColumn;
		
		if (!empty($this->fields)){
			foreach($this->fields as $field){
				$perspective.=$this->getPerspectiveColumn($field);
			}
		}		
		
		$perspective.="</columns></viewsettings>";
		
		return $perspective;
	}
	private function getPerspectiveColumn($elementName,$elementType="text",$elementLabel=null){
		$name = ExternalDataSource::getValidElementName($elementName);
		$elementLabel = $elementLabel==null ? $elementName : $elementLabel;
		
		$column = "<column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>2</totalType><isFrozen/><isFreezeControl/>".PHP_EOL;
		$column.="<title>".$elementLabel."</title>".PHP_EOL;
		$column.="<xmlNodeName>".$name."</xmlNodeName>".PHP_EOL;
		$column.="<dataType>".$elementType."</dataType>".PHP_EOL;
		$column.="<sortOrder>none</sortOrder><customSortOrder>none</customSortOrder>".PHP_EOL;
		$column.="<numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/>".PHP_EOL;
		$column.="<dateFormat>MM/DD/YYYY</dateFormat>".PHP_EOL;
		$column.="<width>150</width>".PHP_EOL;
		$column.="<align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/>".PHP_EOL;
		$column.="<alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/>".PHP_EOL;
		$column.="<backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/>".PHP_EOL;
		$column.="</column>";
		return $column;
	}
	public function getRowXml($name,$folderId,$rowId,$row){
		//Start the XML document
		$data_xml = '<document>';
		$data_xml .= ExternalDataSource::getXmlDataElement("dockey",$rowId);
		$data_xml .= ExternalDataSource::getXmlDataElement("docid",$rowId);
		$data_xml .= ExternalDataSource::getXmlDataElement("rectype","doc");
		$data_xml .= ExternalDataSource::getXmlDataElement("libnsf",$this->name);
		$data_xml .= ExternalDataSource::getXmlDataElement("libid",$this->name);
		if (empty($folderId))
			$data_xml .= ExternalDataSource::getXmlDataElement("folderid",$this->name);
		else
			$data_xml .= ExternalDataSource::getXmlDataElement("folderid",$folderId);
		
		$data_xml .= ExternalDataSource::getXmlDataElement("typekey",$this->name);
			
		//Treat the first column as the subject and the file name as the type
		$data_xml .= ExternalDataSource::getXmlDateElement("F4",$this->sourceDate); //Date
		
		//Check for Doc_Title and Subject fields, if not found treat the first column as the subject
		$indexDocTitle=ExternalDataSource::getFieldIndexFromFields($this->fields,"Doc_Title");
		$indexSubject=ExternalDataSource::getFieldIndexFromFields($this->fields,"Subject");		
		if (!empty($indexDocTitle) && !empty($row[$indexDocTitle])){
			$data_xml .= ExternalDataSource::getXmlDataElement("F8",$row[$indexDocTitle]); //Subject
		}
		else if (!empty($indexSubject) && !empty($row[$indexSubject])){
			$data_xml .= ExternalDataSource::getXmlDataElement("F8",$row[$indexSubject]); //Subject			
		}
		else if (!empty($row[0])){
			$data_xml .= ExternalDataSource::getXmlDataElement("F8",$row[0]); //Subject
		}
		else{
			$data_xml .= '<F8></F8>'; //Subject
		}
		$data_xml .= ExternalDataSource::getXmlDataElement("F9",$this->name); //Type
			
			
		//Add the XML data fields
		$columnId=0;
		foreach($this->fields as $field){
			if (!empty($field) && $field!="" & !empty($row[$columnId]) )
				$data_xml .= ExternalDataSource::getXmlDataElement($field,$row[$columnId]);
			else
				$data_xml .= ExternalDataSource::getXmlDataElement($field,"");
			$columnId++;
		}
			
		//End the XML document
		$data_xml .= '<statno />';
		$data_xml .= '<wfstarted />';
		$data_xml .= '<delflag />';
		$data_xml .= '</document>'.PHP_EOL;
		return $data_xml;
	}
	public function getFieldName($index){
		return ExternalDataSource::getFieldNameFromFields($this->fields,$index);
	}
	public function getFieldIndex($name){
		return ExternalDataSource::getFieldIndexFromFields($this->fields,$name);
	}
	public static function getFieldNameFromFields($fields,$index){
		$columnId=0;
		foreach($fields as $field){
			if ($index==$columnId)
				return $field;
			$columnId++;
		}
		return 'Unknown';
	}
	public static function getFieldIndexFromFields($fields,$name){
		$columnId=0;
		foreach($fields as $field){
			if (\strtolower($name)==\strtolower($field))
				return $columnId;
			$columnId++;
		}
		return -1;
	}
	
	private static function getValidElementName($elementName){
		return   \str_replace(array("(",")"," ","-"), "", \preg_replace("/^![A-Za-z0-9_:\\.]/","",$elementName));
	}
	public static function getXmlDateElement($elementName,$elementValue){
		$name = ExternalDataSource::getValidElementName($elementName);
		
		if (! $elementValue instanceof \DateTime){
			return  "<".$name." val=''/>";
		}
		
		$date = $elementValue;		
		$val = !empty($date) ? $date->getTimestamp(): '';		
		$y = !empty($date) ? $date->format('Y') : '';
		$m = !empty($date) ? $date->format('m') : '';
		$d = !empty($date) ? $date->format('d') : '';
		$w = !empty($date) ? $date->format('w') : '';
		$h = !empty($date) ? $date->format('H') : '';
		$mn = !empty($date) ? $date->format('i') : '';
		$s = !empty($date) ? $date->format('s') : '';
		$date = !empty($date) ? $date->format("m/d/Y h:i:s A") : '';
		return  "<".$name." val='$val' Y='$y' M='$m' D='$d' W='$w' H='$h' MN='$mn' S='$s'><![CDATA[".$date."]]></".$name.">";
		
	}
	public static function getXmlDataElement($elementName,$elementValue){	
		//echo $elementName." is ".\gettype($elementValue).PHP_EOL;	
		if ($elementValue instanceof \DateTime){
			return ExternalDataSource::getXmlDateElement($elementName,$elementValue);
		}
			
		$name = ExternalDataSource::getValidElementName($elementName);
		return '<'.$name.'><![CDATA['. $elementValue.']]></'.$name.'>';
	}
	public function getFields() {
		return $this->fields;
	}
	public function getDataNameValues() {
		if (empty($this->data) || empty($this->fields))
			return null;
		
		$nameValues = array();		
		$index=0;
		foreach($this->data as $row){
			$dataRow=array();
			foreach($this->fields as $field){
				if (!empty($row[$index]))
					$dataRow[$field]=$row[$index];
				$index++;
			}
			$nameValues[]=$dataRow;
		}
		return $nameValues;
	}
	public function getData() {
		return $this->data;
	}
	public function setData($data) {
		$this->data = $data;
		return $this;
	}
	public function getDocovaDocumentsOnly() {
		return $this->docovaDocumentsOnly;
	}
	public function setDocovaDocumentsOnly($docovaDocumentsOnly) {
		$this->docovaDocumentsOnly = $docovaDocumentsOnly;
		return $this;
	}
	
	private static function canAccessDocument($controller,$document,$aclCheck=null){
		if ($aclCheck==null)
			$aclCheck = new Miscellaneous($controller->container);
		return $aclCheck->canReadDocument($document);
	}
	
	private function canViewFolder($controller,$folder,$aclCheck=null){
		return (true === $controller->container->get('security.authorization_checker')->isGranted('VIEW', $folder) || true === $controller->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
	}
	private static function canAccessFolder($controller,$folder,$aclCheck=null){
		if ($aclCheck==null)			
			$aclCheck = new Miscellaneous($controller);
		return ($aclCheck->isFolderVisible($folder) && ExternalViews::canViewFolder($controller,$folder));
	}
	
	public static function verifySourceAccess($controller,$sourceId,$sourceType,$aclCheck=null){
		$em = ExternalViews::getEntityManager($controller);
		//Verify that the author has access to the source folder or document being shared
		if ($sourceType=="Document"){
			$dr = $em->getRepository('DocovaBundle:Documents');
			$document=$dr->find($sourceId);
			if (empty($document)){
				//return new Response("Error:  The source document that you are attempting to share does not exist.");
				return false;
			}
			if (ExternalDataSource::canViewFolder($controller,$document->getFolder()->getId(),$aclCheck)==false){
				return false;
			}
			if (ExternalDataSource::canAccessDocument($controller,$document,$aclCheck)==false){
				//return new Response("Authorization Error:  You are not authorized to access the source document that you are attempting to share.");
				return false;
			}
		}
		else if ($sourceType=="Folder"){
			$fr = $em->getRepository('DocovaBundle:Folders');
			$folder=$fr->find($sourceId);
			if (empty($folder)){
				//return new Response("Error:  The source folder that you are attempting to share does not exist.");
				return false;
			}
			if (ExternalDataSource::canAccessFolder($controller,$folder,$aclCheck)==false){
				//return new Response("Authorization Error:  You are not authorized to access the source folder that you are attempting to share.");
				return false;
			}
		}
		return true;
	}
	public static function getEntityManager($controller){
		return  $controller->get('doctrine')->getManager();
	}
}

?>