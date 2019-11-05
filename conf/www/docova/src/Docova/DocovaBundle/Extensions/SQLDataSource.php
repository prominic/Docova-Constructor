<?php 
namespace Docova\DocovaBundle\Extensions;

//use Docova\DocovaBundle\Extensions\ExternalDataSource;
use Symfony\Component\HttpFoundation\Response;

/*
 * Read data from SQL data source
 */
class SQLDataSource extends ExternalDataSource {
	private $pdo=null;	
	private $csvFilePath = null;
	private $em=null;
	private $controller=null;
	private $driverName="unknown";
	
	public function __construct($controller,$name,$pdo,$query,$orderBy,$offset=0,$limit=10000,$search=null,$docovaDocumentsOnly=false){	
		
		if (! $pdo instanceof \PDO  && !$pdo instanceof \Doctrine\DBAL\Connection)
			throw new \Exception("Invalid PDO Provided. Received: ".\gettype($pdo));		
		$this->pdo = $pdo;
		$this->controller=$controller;
		$this->em=$this->getEntityManager($controller);
		if ($this->pdo instanceof \Doctrine\DBAL\Connection){
			$this->driverName = $this->pdo->getDriver()->getName();
		}
		else{
			$this->driverName = $controller->get('doctrine')->getConnection()->getDriver()->getName();
			//$this->driverName = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
		}
		$this->readQueryData($query,$orderBy,$offset,($limit==0 ? 1000000 : $limit),$search);
		
		parent::__construct($name,$this->fields, $this->data, new \DateTime(), false, $docovaDocumentsOnly);
		
		
	}
	public static function getEntityManager($controller){
		return  $controller->get('doctrine')->getManager();
	}
	
	public function getRowCountResponse($table,$query=null){
		$count = $this->getRowCount($table,$query);
		$result = '<?xml version="1.0" encoding="UTF-8"?>
			<Results><Result ID="Status">OK</Result><Result ID="Ret1">'.$count.'</Result></Results>';
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($result);
		return $response;
	}
	public function getRowCount($table,$query=null){
		if ($query==null)
			$query = "SELECT Count(*) FROM ".$table;		
		$statement = $this->pdo->query($query);
		$results = array_values($statement->fetchAll());
		return $results[0][0];
	}	
	private function readQueryData($query,$orderBy,$offset=0,$limit=10000,$search=null){		
		$hasOrderBy = !(\strpos(\strtoupper($query),"ORDER BY")===false);
		if ($hasOrderBy){
			$query = \substr($query,0,\strpos(\strtoupper($query),"ORDER BY"));
		}		
		$statement=null;
		try {
			$hasCount = !(\strpos(\strtoupper($query),"COUNT(")===false);
			if (!$hasCount){
				$queryOptions="";
				if (!empty($orderBy) && $orderBy!="")
					$queryOptions.= " ORDER BY ".$orderBy;
			
				if ($this->getDriverName()=="sqlsrv" || $this->getDriverName()=="pdo_sqlsrv")				
					$queryOptions.=" OFFSET ".$offset." ROWS FETCH NEXT ".$limit." ROWS ONLY";
				else
					$queryOptions.=" LIMIT ".$limit." OFFSET ".$offset." ";

				//echo "Running query: ".$query.$queryOptions;
				$statement = $this->pdo->query($query.$queryOptions);
			}
			else{ 
				//echo "Running query: ".$query." ORDER BY ".$orderBy.PHP_EOL;
				$statement = $this->pdo->query($query." ORDER BY ".$orderBy);
			}
		} 
		catch (\PDOException $pe) {
			echo "ERROR: ".$pe->getMessage();
		}		
		
		$this->data = array();
		//Use the statement to compute the field names
		$columnCount = $statement->columnCount();
		$this->fields = array();
		for($iColumn=0;$iColumn<$columnCount;$iColumn++){
			$columnMeta = $statement->getColumnMeta($iColumn);
			$this->fields[] = $columnMeta["name"];			
		}
		
		$rowPosition=0;
		$rowsMatched=0;
		
		
		
		
		while ( $dataRow=$statement->fetch() ){		
			if ($this->pdo instanceof \Doctrine\DBAL\Connection)	
				$dataRow = array_values($dataRow);
			$rowPosition++;
			$processRow=true;
		//	echo "Row: ".$rowPosition.PHP_EOL;
		 //   echo \json_encode($dataRow).PHP_EOL;
		 
			$ddOnly = $this->getDocovaDocumentsOnly();
			if ($ddOnly){
				//1.) Read the DOCOVA id
				//2). Confirm access to the document
				$idFieldIndex = $this->getFieldIndex('id');
				$docIdFieldIndex = $this->getFieldIndex('doc_id');
				$documentId = !empty($dataRow[$idFieldIndex]) ? $dataRow[$idFieldIndex] : null;
				$documentId = empty($documentId) && !empty($dataRow[$docIdFieldIndex]) ? $dataRow[$docIdFieldIndex] : $documentId;
				$accessCheck = ExternalDataSource::verifySourceAccess($this->controller, $documentId, "Document");
				if ($accessCheck==false)
					$processRow=false;
			}
			
			if ($search!=null){
				//Search Limit of 500 results
				if ($rowsMatched>=500)
					break;
				$processRow=false;
				foreach($dataRow as $dataField){					
				//	$matches = \preg_match("/.*".\strtolower($search)."*/", \strtolower($dataField));
					$matches=!(\strpos(\strtolower($dataField),\strtolower($search))===false);					
					if ($matches==1){						
						$processRow=true;	
						$rowsMatched++;				
						break;
					}					
				}				
			}			
			
			if ($processRow)
				$this->data[($rowPosition+$offset)-1]=$dataRow;			
		}		
		parent::setData($this->data);
	}
	public function getDriverName() {
		return $this->driverName;
	}
	
	
}
?>