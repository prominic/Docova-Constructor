<?php
namespace Docova\DocovaBundle\Extensions;

//use Docova\DocovaBundle\Extensions\CSVDataSource;
//use Docova\DocovaBundle\Extensions\ExternalConnections;
//use Docova\DocovaBundle\Extensions\SQLDataSource;
//use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
//use Docova\DocovaBundle\Logging\FileLogger;
//use Docova\DocovaBundle\Controller\Miscellaneous;

class ExternalViews {
	public static function getDataView($controller,$dataViewId){
		$em = ExternalViews::getEntityManager($controller);
		$dataView = $em->getRepository("DocovaBundle:DataViews")->find($dataViewId);
		return $dataView;		
	}
	public static function getDataSource($controller,$dataViewId){		
		$dataView = ExternalViews::getDataView($controller,$dataViewId);
		$dataSource = !empty($dataView) ? $dataView->getDataSource(ExternalViews::getEntityManager($controller)) : null;
		return $dataSource;
	}
	public static function readDocument($controller, $rootPath, $doc_id, $folder_id, $dataView, $dataSource ){
		//Do not process DOCOVA documents
		if (!empty($dataSource)){
			$docovaDocumentsOnly = $dataSource->getDocovaDocumentsOnly();
			if (!empty($docovaDocumentsOnly) && $docovaDocumentsOnly){
				return null;
			}
		}
		
		$documentData = ExternalViews::getFirstDocument(ExternalViews::getDocumentData($controller, $rootPath, $doc_id, $dataView, $dataSource));
		
		//Look for a custom twig
		if (\file_exists(\realpath(__DIR__.'/../Resources/views/Form/').$dataView->getName().'-read.html.twig')){	
			return $controller->render('DocovaBundle:Form:'.$dataView->getName().'-read.html.twig', array(
					'viewTitle' => $dataView->getName(),
					'data' => $documentData			
			));
		} 
		else {		
		//no custom twig found, render using the default twig
			return $controller->render('DocovaBundle:Form:Default-External-read.html.twig', array(
					'viewTitle' => $dataView->getName(),
					'data' => $documentData			
			));
		}
					
	}
	public static function getRowData($fields,$request){
		$row=array();
		foreach($fields as $field){
			$field_value = $request->get($field);
			if (empty($field_value))
				$field_value="";
			$row[$field]=$field_value;
		}
		return $row;
	}
	public static function saveDocument($controller, $rootPath, $request, $doc_id, $folder_id, $dataViewId ){
		$logPath = $rootPath ? $rootPath.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR : $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR;
		//$log = new FileLogger($logPath."documentLog.log");
		//if (!empty($log)) $log->log("SaveDocument for Document ID: ".$doc_id. " in view ".$dataViewId,FileLogger::NOTICE); 
		
		$em = self::getEntityManager($controller);
		$dataView = $em->getRepository("DocovaBundle:DataViews")->find($dataViewId);
		$dataSource = !empty($dataView) ? $dataView->getDataSource($em) : null;
		
		//Do not process DOCOVA documents
		if (!empty($dataSource)){
			$docovaDocumentsOnly = $dataSource->getDocovaDocumentOnly();
			if (!empty($docovaDocumentsOnly) && $docovaDocumentsOnly){
				return null;
			}
		}
		
		if (!empty($log)) $log->log("Data Source: ".\json_encode($dataSource),$log::NOTICE);
		
		
		if ($dataSource->getType()=="CSV"){
			$source = self::getCSVSource($controller, $rootPath, $folder_id, 0, 1000000, null, $dataSource, $dataView);
				
			if (!empty($log)) $log->log("CSV Source: ".\json_encode($source),$log::NOTICE);			
			$source->updateCSVLine($doc_id, self::getRowData($source->fields,$request));					
			if (!empty($log)) $log->log("CSV Source Updated: ".\json_encode($source),$log::NOTICE);
						
			$source->saveCSVFile();
		}
		else if ($dataSource->getType()=="SQL"){
			$source = self::getSQLSource($controller, $rootPath, $folder_id, $doc_id, 1, null, $dataSource, $dataView);
			
			if (!empty($log)) $log->log("SQL Source: ".\json_encode($source),$log::NOTICE);
			$source->updateSQLRow($doc_id, self::getRowData($source->fields,$request));
			if (!empty($log)) $log->log("SQL Source Updated: ".\json_encode($source),$log::NOTICE);
		}
		$source=null;
	}
	public static function editDocument($controller, $rootPath, $doc_id, $folder_id, $dataViewId ){		
		$em = ExternalViews::getEntityManager($controller);
		$dataView = $em->getRepository("DocovaBundle:DataViews")->find($dataViewId);
		$dataSource = !empty($dataView) ? $dataView->getDataSource($em) : null;
	
		//Do not process DOCOVA documents
        if (!empty($dataSource)){
        	$docovaDocumentsOnly = $dataSource->getDocovaDocumentsOnly();
        	if (!empty($docovaDocumentsOnly) && $docovaDocumentsOnly){
        		return null;
        	}
        }
		
		$documentData = ExternalViews::getFirstDocument(ExternalViews::getDocumentData($controller, $rootPath, $doc_id, $dataView, $dataSource));
				
		if (\file_exists(\realpath(__DIR__.'/../Resources/views/Form/').$dataView->getName().'-edit.html.twig')){	
			return $controller->render('DocovaBundle:Form:'.$dataView->getName().'-edit.html.twig', array(
					'viewTitle' => $dataView->getName(),
					'doc_id' => $doc_id,
					'folderId' => $folder_id,
					'dataViewId' => $dataView->getId(),
					'data' => $documentData			
			));
		} 
		else {		
			//no custom twig found, render using the default twig
			return $controller->render('DocovaBundle:Form:Default-External-edit.html.twig', array(
					'viewTitle' => $dataView->getName(),
					'doc_id' => $doc_id,
					'folderId' => $folder_id,
					'dataViewId' => $dataView->getId(),
					'data' => $documentData			
			));
		}
	}
	
	public static function getData($controller,$rootPath,$folder_id,$start,$count,$searchQuery) {
		//Check to see if there this folder is supplied by an external view
		$em = ExternalViews::getEntityManager($controller);		 
		$dataView = $em->getRepository("DocovaBundle:DataViews")->getDataView($folder_id);
		$dataSource = !empty($dataView) ? $dataView->getDataSource($em) : null;

		if (empty($dataView))
			return null;
		
		//Proces SQL data extensions
		$data = ExternalViews::getSQLData($controller,$rootPath, $folder_id, $start, $count, $searchQuery,$dataSource,$dataView);
		if ($data!=null) return $data;
		
		//Proces CSV data extensions		
		$data = ExternalViews::getCSVData($controller,$rootPath,$folder_id, $start, $count, $searchQuery,$dataSource,$dataView);
		if ($data!=null) return $data;
		
		return null;
	}
	
	public static function getCount($controller,$rootPath,$folder_id){		
		//Data View Mappings
		$em = ExternalViews::getEntityManager($controller);
		$dataView = $em->getRepository("DocovaBundle:DataViews")->getDataView($folder_id);
		$dataSource = !empty($dataView) ? $dataView->getDataSource($em) : null;
		if (!empty($dataView) && !empty($dataSource)){
			$dataType = $dataSource->getType();				
			if ($dataType=="SQL"){
				$connectionName = $dataSource->getConnectionName();
				$connection = $connectionName=="DOCOVA SE" ? $controller->getDoctrine()->getConnection() : ExternalConnections::getConnection($connectionName);
				$sqlType = $dataSource->getSQLType();
				if ($sqlType=="Text")
						$query = $dataSource->getSQL();
				else if ($sqlType=="File Resource" &&
					(empty($query) || $query=="" ))
						$query = $dataSource->getFileResourceContent($em,$rootPath);
				
				if (empty($query) || $query=="" )
					throw new NotFoundHttpException("A query must be provided");
				
				//check to see if this is a DOCOVA documents source
				$docovaDocumentsOnly = $dataSource->getDocovaDocumentsOnly();
				if (empty($docovaDocumentsOnly) || $docovaDocumentsOnly==false){
					$docovaDocumentsOnly=false;
				}
								
				$sqlSource = new SQLDataSource(
						$controller,
						$dataView->getName(), 
						$connection, 
						$query,
						$dataSource->getSQLOrderBy(),
						0,
						1,
						$docovaDocumentsOnly);
				
				return $sqlSource->getRowCountResponse($dataSource->getSQLTableName());
			}
			else if ($dataType=="CSV"){
				$csvPath = $dataSource->getFileResourcePath($em,$rootPath);
				if (empty($csvPath))
					throw new NotFoundHttpException("File resouce ".$dataSource->getFileResourceName()." was not found!");		
				return CSVDataSource::getRowCountResponse($csvPath);
			}			
		}
		
		//Special handling
		switch ($folder_id){
			/*
			case "361CD4FA-DA4B-41BC-BD83-4B87F3BBDBFC":
				$file_resource = $em->getRepository('DocovaBundle:FileResources');
				$csvPath = $file_resource->getFileResourcePath($rootPath,"Fruit");
				if (empty($csvPath))
					throw new \Exception("File resouce Fruit was not found!");
				$file_resource=null;
				return CSVDataSource::getRowCountResponse($csvPath);
			*/
			default:
				return null;
		}
	}
	private static function getDocumentContent($dataView,$data){
		$html= "<h3 style='border:0;margin:5px;font-family: Verdana; font-size: 10pt; font-weight: bold'>".$dataView->getName()."</h3>";
		$html.="<hr style='margin-top:5:px;color:rgb(92,134,197);height:1px'/>";
		
		$html.="<table border=0 style='font-family: Verdana; font-size: 8pt;'>";
		
		if (!empty($data)){
			foreach($data as $field => $field_value){
				$html.= "<tr>";
					$html.= "<td><b>".$field.":&nbsp;&nbsp;&nbsp;</b></td>";
					$html.= "<td>".$field_value."</td>";
				$html.= "</tr>";					
			}
		}			
		
		$html.= "</table>";
	    return $html;
	}
	private static function getFirstDocument($documentData){
		foreach($documentData as $data){
			if (!empty($data)){
				return $data;				
			}
		}
	}
	private static function getDocumentData($controller, $rootPath, $doc_id, $dataView,$dataSource){
		$em = ExternalViews::getEntityManager($controller);
		if (empty($dataView) && empty($dataSource)){
			throw new NotFoundHttpException("Data View or Data Source could not be located!");
		}
	
		switch ($dataSource->getType()){
			case "CSV":
				if ($dataSource->getName()=="IIS Logs" || $dataSource->getName()=="IIS Log"){
					$iisPath="C:/inetpub/logs/LogFiles/W3SVC1/";
					$files = scandir($iisPath, SCANDIR_SORT_DESCENDING);
					$csvFilePath = $iisPath.$files[0];
					if (empty($csvFilePath))
						throw new NotFoundHttpException("No log files found to process in path");
				}
				else
					$csvFilePath = $dataSource->getFileResourcePath($em,$rootPath);
				
				//CSV Options
				$csvDelimiter = $dataSource->getCSVDelimiter();
				$csvOffsetLines = $dataSource->getCSVOffsetLines();
				$csvOffsetChars = $dataSource->getCSVOffsetChars();
				
				$csvSource = new CSVDataSource($controller,$csvFilePath,intval($doc_id)-1,1,null,
						$csvDelimiter,$csvOffsetLines,$csvOffsetChars);
				return $csvSource->getDataNameValues();
				break;
			case "SQL":
				$connectionName = $dataSource->getConnectionName();
				$connection = $connectionName=="DOCOVA SE" ? $controller->getDoctrine()->getConnection() : ExternalConnections::getConnection($connectionName);
				$sqlType = $dataSource->getSQLType();
				if ($sqlType=="Text")
					$query = $dataSource->getSQL();
				if (empty($query) || $query=="")
					$query = $dataSource->getFileResourceContent($em,$rootPath);
				

				//check to see if this is a DOCOVA documents source
				$docovaDocumentsOnly = $dataSource->getDocovaDocumentsOnly();
				if (empty($docovaDocumentsOnly) || $docovaDocumentsOnly==false){
					$docovaDocumentsOnly=false;
				}
					
				
				$sqlSource = new SQLDataSource(
						$controller,
						$dataView->getName(),
						$connection,
						$query,
						$dataSource->getSQLOrderBy(),
						$doc_id,
						1,
						$docovaDocumentsOnly);
				
				return $sqlSource->getDataNameValues();
				break;
		}
	}
		
	private static function getEntityManager($controller){
		return  $controller->get('doctrine')->getManager();
	}
	
	private static function getSQLSource($controller,$rootPath,$folder_id,$start,$count,$searchQuery,$dataSource,$dataView) {
		//Adjust the offsets and limits
		$start = ($searchQuery!=null ? 1 : $start);
		$start = ($start==0 ? 1 : $start);
		$count = ($count==10000 ? 1000000 : $count);
		$count = ($searchQuery!=null ? 1000000 : $count);
		
		
		//Data View Hooks
		$em = ExternalViews::getEntityManager($controller);
		if (!empty($dataView) && !empty($dataSource)){
			$connectionName = $dataSource->getConnectionName();
			$connection = $connectionName=="DOCOVA SE" ? $controller->getDoctrine()->getConnection() : ExternalConnections::getConnection($connectionName);
			$dataType = $dataSource->getType();
	
			if ($dataType=="SQL"){
				$sqlType = $dataSource->getSQLType();
				if ($sqlType=="Text")
					$query = $dataSource->getSQL();
				else if ($sqlType=="File Resource" &&
						(empty($query) || $query=="" ))
							$query = $dataSource->getFileResourceContent($em,$rootPath);
							
				if (empty($query) || $query=="" )
					throw new NotFoundHttpException("A query must be provided");
				
				$docovaDocumentsOnly = $dataSource->getDocovaDocumentsOnly();
				if (empty($docovaDocumentsOnly) || $docovaDocumentsOnly==false){
					$docovaDocumentsOnly=false;
				}				
			
				//If the data view has a filter then append it as an additonal condition
			    $whereFilter=$dataView->getSQLFilter();
			    if (!empty($whereFilter) && $whereFilter!=""){
			    	//if a where statement does not exist in the query then add one			    	
			    	if (\strpos(\strtolower($query),"where")===false)
			    		$query.=" WHERE ".$whereFilter;
			    	else
			    	//add an additional AND WHERE condition
			    		$query.=" AND ".$whereFilter;
			    }
			    
				$sqlSource = new SQLDataSource(
						$controller,						
						$dataView->getName(),
						$connection,
						$query,
						$dataSource->getSQLOrderBy(),
						($start-1),
						$count,$searchQuery,$docovaDocumentsOnly);

					
				return $sqlSource;
			}
		}
	
		//Hard Coded Hooks
		switch ($folder_id){
			//Special External Data Folder Hooks
	
			/*
				//Game
				case "3EE64EC3-AFC5-49E6-A18E-558AC25F82CA":
				$query = "SELECT * FROM dbo.Game";
				$sqlSource = new SQLDataSource("Game", ExternalConnections::getVideoGamesDbConnection(), $query,"SystemName",($start-1),$count,$searchQuery);
				return $sqlSource; //->getXmlResponse($folder_id,$isSearch);
				*/
			default:
				return null;
					
		}
	}
	
	private static function getSQLData($controller,$rootPath,$folder_id,$start,$count,$searchQuery,$dataSource,$dataView) {
		$isSearch = ($searchQuery==null ? false : true);
		$sqlSource = self::getSQLSource($controller, $rootPath, $folder_id, $start, $count, $searchQuery, $dataSource, $dataView);
		
		if (!empty($sqlSource))	
			return $sqlSource->getXmlResponse($folder_id,$isSearch);
	}
	
	private static function getCSVSource($controller,$rootPath,$folder_id,$start,$count,$searchQuery,$dataSource,$dataView){
		
		//Adjust the offsets and limits
		$em = ExternalViews::getEntityManager($controller);
		$start = $searchQuery!=null ? 1 : $start;
		$count = $count==10000 ? 0 : $count;
		
		//Data view hooks
		if (!empty($dataView) && !empty($dataSource)){
			
			$dataType = $dataSource->getType();			
			if ($dataType=="CSV"){
				
				if ($dataSource->getName()=="IIS Logs" || $dataSource->getName()=="IIS Log"){
					$iisPath="C:/inetpub/logs/LogFiles/W3SVC1/";
					$files = scandir($iisPath, SCANDIR_SORT_DESCENDING);
					$csvFilePath = $iisPath.$files[0];
					if (empty($csvFilePath))
						throw new NotFoundHttpException("No log files found to process in path");
				}
				else
					$csvFilePath = $dataSource->getFileResourcePath($em,$rootPath);
				
				if (empty($csvFilePath)){
					throw new \Exception("Unable to determine CSV data file location in "+$rootPath);
				}
				
				//CSV Options
				$csvDelimiter = $dataSource->getCSVDelimiter();
				$csvOffsetLines = $dataSource->getCSVOffsetLines();
				$csvOffsetChars = $dataSource->getCSVOffsetChars();

				$docovaDocumentsOnly = $dataSource->getDocovaDocumentsOnly();
				if (empty($docovaDocumentsOnly) || $docovaDocumentsOnly==false){
					$docovaDocumentsOnly=false;
				}
				
				$csvSource = new CSVDataSource($controller,$csvFilePath,$start,$count,$searchQuery,
						$csvDelimiter,$csvOffsetLines,$csvOffsetChars,$docovaDocumentsOnly);
				
				return $csvSource;
			}
		}

		//Hard Coded hooks
		switch ($folder_id){
			//Special External Data Folder Hooks
				
			//IIS Logs
			case "D7187305-B06D-4860-8513-41F208BDB2A6":
				$iisPath="C:/inetpub/logs/LogFiles/W3SVC1/";
				$files = scandir($iisPath, SCANDIR_SORT_DESCENDING);
				$logFilePath = $files[0];
				if (empty($logFilePath))
					throw new NotFoundHttpException("No log files found to process in path: ".$iisPath);
					
				//Skip software,date, and version lines
				//Space is the delimiter
				$csvSource = new CSVDataSource($controller,$iisPath.$logFilePath,$start,$count,$searchQuery," ",3,10);
				return $csvSource;
			default:
				return null;
		}
	}
	private static function getCSVData($controller,$rootPath,$folder_id,$start,$count,$searchQuery,$dataSource,$dataView){
		$csvSource = self::getCSVSource($controller, $rootPath, $folder_id, $start, $count, $searchQuery, $dataSource, $dataView);
		$isSearch = $searchQuery==null ? false : true;		
		if ($csvSource==null)
			return null;
		else 
			return $csvSource->getXmlResponse($folder_id,$isSearch);
	}
	private static function getSchemaQuery($controller,$rootPath) {
		$em = ExternalViews::getEntityManager($controller);
		return $em->getRepository("DocovaBundle:FileResources")->getFileResourceContent($rootPath, "SE Schema");				
	}
	private static function getLibraryStatisticsQuery($controller,$rootPath){
		$em = ExternalViews::getEntityManager($controller);
		return $em->getRepository("DocovaBundle:FileResources")->getFileResourceContent($rootPath, "SE Library Statistics");	
	}
}
?>