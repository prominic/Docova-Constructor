<?php

namespace Docova\DocovaBundle\Index;

use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;


/**
 * Initializing Database Indexing
 * @author javad rahimi, chris fales
 *        
 */
class Indexer
{
	protected $connection;
	public $indexName;
	private $esclient;
	protected $root;
	protected $UPLOAD_FILE_PATH;
	private $maxFileBytes;
	private $maxTotalFileBytes;
	private $ignoreAttachmentTypes;
	private $DEBUG=false;
	
	public function __construct(Connection $connection, $root)
	{	
		if ($this->DEBUG) echo('Root: '.$root.PHP_EOL);

		$this->indexName = "docova";
		$this->connection = $connection;
//		$this->esclient = ClientBuilder::create()->build();
		$this->esclient = ClientBuilder::create()->setConnectionParams(['client' => [
						'curl' => [
								CURLOPT_PROXY => ''
						]
				]
		])->build();
		$this->root = !empty($root) ? $root : getcwd().DIRECTORY_SEPARATOR.'..';
		$this->UPLOAD_FILE_PATH = $this->root.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
		$this->maxFileBytes = 1024 * 1024 * 6; //6MB cap on indexing any one file
		$this->maxTotalFileBytes = 1024 * 1024 * 12; //12MB cap for all indexed file content
		$this->ignoreAttachmentTypes = "bmp,gif,img,jpg,jpeg,png,tif,rar,tar,zip,com,dll,exe,sys,mov,wmv,mp3,mp4,mpg,wav,nsf,ntf";

	}
	
	/**
	 * Get array of document comments
	 * 
	 * @param string $docid
	 * @return array 
	 */
	public function fetchComments($docid)
	{
		$output = array();
		$query = 'SELECT C.Comment FROM tb_document_comments AS C WHERE C.Document_Id = ?';
		$result = $this->connection->fetchAll($query, array($docid));
		if (!empty($result['0'])) 
		{
			foreach ($result as $comment) {
				$tempcomment = $comment['Comment'];
				if($tempcomment != null){
					$output[] = ['Comment' => $tempcomment];
				}
			}
		}
		unset($result);
		
		return $output;
	}

	/**
	 * Get array of a document input text values
	 * 
	 * @param string $docid
	 * @return array 
	 */
	public function fetchInputTextValues($docid)
	{
		$output = array();
		try{		
			//-- get text field values
			$query = 'SELECT V.Field_Id, V.Value_Order, V.Field_Value FROM tb_form_text_values AS V WHERE V.Doc_Id = ? ORDER BY V.Field_Id, V.Value_Order';
			$result = $this->connection->fetchAll($query, array($docid));
			if (!empty($result) && !empty($result['0'])) 
			{
				foreach ($result as $value) {
					$output[] = $value['Field_Value'];
				}
			}
			unset($result);
		}
		catch (\PDOException $pe){
			echo "fetchInputTextValues() - PDO Exception: ".$pe->getMessage()." Code: ".$pe->getCode()." Trace: ".$pe->getTraceAsString().PHP_EOL;
			echo "fetchInputTextValues() - PDO Query: ".$query.PHP_EOL;
			throw $pe;
		}
		catch (\Exception $e){
			echo "fetchInputTextValues() - Exception: ".$e->getMessage()." Line: ".$e->getLine()." Trace: ".$e->getTraceAsString().PHP_EOL;
			throw $e;
		}
		unset($result);

		return $output;
	}

	/**
	 * Get array of a document input date values
	 * 
	 * @param string $docid
	 * @return array 
	 */
	public function fetchInputDateValues($docid)
	{
		$output = array();
		try{		
			//-- get date field values
			$query = 'SELECT V.Field_Value FROM tb_form_datetime_values AS V WHERE V.Doc_Id = ?';
			$result = $this->connection->fetchAll($query, array($docid));
			if (!empty($result) && !empty($result['0'])) 
			{		
				foreach ($result as $value) {
					$output[] = str_replace('-', '/', $value['Field_Value']);
				}
			}
			unset($result);
		}
		catch (\PDOException $pe){
			echo "fetchInputDateValues() - PDO Exception: ".$pe->getMessage()." Code: ".$pe->getCode()." Trace: ".$pe->getTraceAsString().PHP_EOL;
			echo "fetchInputDateValues() - PDO Query: ".$query.PHP_EOL;
			throw $pe;
		}
		catch (\Exception $e){
			echo "fetchInputDateValues() - Exception: ".$e->getMessage()." Line: ".$e->getLine()." Trace: ".$e->getTraceAsString().PHP_EOL;
			throw $e;
		}
		unset($result);

		return $output;
	}	


	/**
	 * Get array of a document input number values
	 * 
	 * @param string $docid
	 * @return array 
	 */
	public function fetchInputNumberValues($docid)
	{
		$output = array();
		try{		
			//-- get number field values
			$query = 'SELECT V.Field_Value FROM tb_form_numeric_values AS V WHERE V.Doc_Id = ?';
			$result = $this->connection->fetchAll($query, array($docid));
			if (!empty($result) && !empty($result['0'])) 
			{
				foreach ($result as $value) {
					$output[] = $value['Field_Value'];
				}
			}
			unset($result);
		}
		catch (\PDOException $pe){
			echo "fetchInputNumberValues() - PDO Exception: ".$pe->getMessage()." Code: ".$pe->getCode()." Trace: ".$pe->getTraceAsString().PHP_EOL;
			echo "fetchInputNumberValues() - PDO Query: ".$query.PHP_EOL;
			throw $pe;
		}
		catch (\Exception $e){
			echo "fetchInputNumberValues() - Exception: ".$e->getMessage()." Line: ".$e->getLine()." Trace: ".$e->getTraceAsString().PHP_EOL;
			throw $e;
		}
		unset($result);

		return $output;
	}		
	
	
	/**
	 * Get array of a document name values
	 * 
	 * @param string $docid
	 * @return array 
	 */
	public function fetchInputNameValues($docid)
	{
		$output = array();
		try{		
			//-- get name field values
			$query = 'SELECT V.Field_Value, U.user_name_dn_abbreviated FROM tb_form_name_values AS V LEFT JOIN tb_user_accounts AS U ON V.Field_Value = U.id WHERE V.Doc_Id = ?';			
			$result = $this->connection->fetchAll($query, array($docid));
			if (!empty($result) && !empty($result['0'])) 
			{
				foreach ($result as $value) {										
					$output[] = array(
						'id' => $value['Field_Value'],
						'name' => $value['user_name_dn_abbreviated']
					);
				}
			}
			unset($result);
		}
		catch (\PDOException $pe){
			echo "fetchInputNameValues() - PDO Exception: ".$pe->getMessage()." Code: ".$pe->getCode()." Trace: ".$pe->getTraceAsString().PHP_EOL;
			echo "fetchInputNameValues() - PDO Query: ".$query.PHP_EOL;
			throw $pe;
		}
		catch (\Exception $e){
			echo "fetchInputNameValues() - Exception: ".$e->getMessage()." Line: ".$e->getLine()." Trace: ".$e->getTraceAsString().PHP_EOL;
			throw $e;
		}
		unset($result);

		return $output;
	}			


	/**
	 * Get array of a document group values
	 * 
	 * @param string $docid
	 * @return array 
	 */
	public function fetchInputGroupValues($docid)
	{
		$output = array();
		try{		
			//-- get name field values
			$query = 'SELECT V.Field_Value, G.Group_Name, G.Display_Name FROM tb_form_group_values AS V LEFT JOIN tb_user_roles AS G ON V.Field_Value = G.id WHERE V.Doc_Id = ?';						
			$result = $this->connection->fetchAll($query, array($docid));
			if (!empty($result) && !empty($result['0'])) 
			{
				foreach ($result as $value) {
					$gname = $value['Display_Name'];
					if(empty($gname)){
						$gname = $value['Group_Name'];
					}
					$output[] = array(
						'id' => $value['Field_Value'],
						'name' => $gname
					);
				}
			}
			unset($result);
		}
		catch (\PDOException $pe){
			echo "fetchInputGroupValues() - PDO Exception: ".$pe->getMessage()." Code: ".$pe->getCode()." Trace: ".$pe->getTraceAsString().PHP_EOL;
			echo "fetchInputGroupValues() - PDO Query: ".$query.PHP_EOL;
			throw $pe;
		}
		catch (\Exception $e){
			echo "fetchInputGroupValues() - Exception: ".$e->getMessage()." Line: ".$e->getLine()." Trace: ".$e->getTraceAsString().PHP_EOL;
			throw $e;
		}
		unset($result);

		return $output;
	}			
	
	
	/**
	 * Get array of folders ID which the document is bookmarked in
	 * 
	 * @param string $docid
	 * @return array 
	 */
	public function fetchBookmarks($docid)
	{
		$output = array();
		$query = 'SELECT B.Target_Folder FROM tb_bookmarks AS B WHERE B.Document_Id = ?';
		$result = $this->connection->fetchAll($query, array($docid));
		if (!empty($result['0'])) 
		{
			foreach ($result as $bookmark) {
				$output[] = ['Target_Folder' => $bookmark['Target_Folder']];
			}
		}
		unset($result);

		return $output;
	}
	
	
	/**
	 * Get array of view ID which the document appears in
	 *
	 * @param string $appid
	 * @param string $docid
	 * @return array
	 */
	public function fetchViews($appid, $docid)
	{
		$output = array();
		$query = 'SELECT B.Target_Folder FROM tb_bookmarks AS B WHERE B.Document_Id = ?';
		$result = $this->connection->fetchAll($query, array($docid));
		if (!empty($result['0']))
		{
			foreach ($result as $bookmark) {
				$output[] = ['Target_Folder' => $bookmark['Target_Folder']];
			}
		}
		unset($result);
	
		return $output;
	}	
	

	/**
	 * Get array of document discussions
	 * 
	 * @param string $docid
	 * @return array  
	 */
	public function fetchDiscussionAttachments($docid)
	{
		$output = array();
		$query = 'SELECT D.id, D.Subject, D.Body FROM tb_discussion_topic AS D WHERE D.Parent_Document = ?';
		$result = $this->connection->fetchAll($query, array($docid));
		if (!empty($result['0'])) 
		{
			foreach ($result as $discussion) {
				$query = 'SELECT DA.File_Name FROM tb_discussion_attachments AS DA WHERE Discussion_Id = ?';
				$attachments = $this->connection->fetchAll($query, array($discussion['id']));
				if (!empty($attachments[0])) {
					foreach ($attachments as $attachment) {
						$output[] = $this->fetchAttachmentsContent(array($attachment['File_Name']), $discussion['id']);
					}

				}
				
			}
		}
		unset($result);
		unset($attachments);

		return $output;
	}

	/**
	 * Get array of document discussion attachments
	 * 
	 * @param string $docid
	 * @return array  
	 */
	public function fetchDiscussions($docid)
	{
		$output = array();
		$query = 'SELECT D.id, D.Subject, D.Body FROM tb_discussion_topic AS D WHERE D.Parent_Document = ?';
		$result = $this->connection->fetchAll($query, array($docid));
		if (!empty($result['0'])) 
		{
			foreach ($result as $discussion) {
				$query = 'SELECT DA.File_Name FROM tb_discussion_attachments AS DA WHERE Discussion_Id = ?';
				$attachments = $this->connection->fetchAll($query, array($discussion['id']));
				$attlist = [];
				if (!empty($attachments[0])) {
					foreach ($attachments as $attachment) {
						$attlist[] = $attachment['File_Name'];
					}

				}
				$output[] = array(
					'Subject' => $discussion['Subject'],
					'Body' => $discussion['Body'],
					'AttachmentNames' => $attlist
				);
			}
		}
		unset($result);
		unset($attachments);

		return $output;
	}
	

	/**
	 * Get array of encoded attachment contents in a document
	 * 
	 * @param string $docid
	 * @return array 
	 */
	public function fetchAttachmentNames($docid)
	{
		$output = array();
		$query = 'SELECT File_Name FROM tb_attachments_details WHERE Doc_Id = ?';
		$result = $this->connection->fetchAll($query, array($docid));
		if (!empty($result[0])) 
		{
			foreach ($result as $value) {
				$output[] = $value['File_Name'];
			}
		}
		unset($result);

		return $output;
	}


	/**
	 * Get attachment content and encode them
	 * 
	 * @param string $fileNames
	 * @param string $docid
	 * @return array
	 */
	protected function fetchAttachmentsContent($fileNames, $docid)
	{
		if ($this->DEBUG) echo("fetchAttachmentsContent - Start".PHP_EOL);
		$exceptionlist = explode(",", strtolower($this->ignoreAttachmentTypes));
		$contents = array();
		if (!empty($fileNames) && is_array($fileNames)) 
		{
			foreach ($fileNames as $name) {
				if ($this->DEBUG) echo("fetchAttachmentsContent - filename:".$name.PHP_EOL);
				$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
				if($ext == "" || !in_array($ext, $exceptionlist)){
					$fname = md5($name);
					if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$docid.DIRECTORY_SEPARATOR.$fname)) 
					{
						if ($this->DEBUG) echo("fetchAttachmentsContent - File Found".PHP_EOL);
						$content = $this->getEncodedFile($fname, $docid);
						if (!empty($content))
						{
							//if ($this->DEBUG) echo("fetchAttachmentsContent - Encoded Content:".PHP_EOL);
							//if ($this->DEBUG) echo($content.PHP_EOL);
							$contents[] = $content;
							unset($content);
						}
					}
				}
			}
		}

		return $contents;
	}

	/**
	 * Encode file content if exists
	 * 
	 * @param string $filename
	 * @param string $docid
	 * @return string|boolean
	 */
	private function getEncodedFile($filename, $docid)
	{
		$content = file_get_contents($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$docid.DIRECTORY_SEPARATOR.$filename, false, NULL, 0, $this->maxFileBytes);
		if ($content) 
		{
			return base64_encode($content);
		}
		else {
			return false;
		}
	}	


	function createIndex(){

		$createIndexParams = [
				'index' => $this->indexName,
				'body' => [
						'settings' => [
								'number_of_shards' => 5,
								'number_of_replicas' => 0,
								'analysis' => [
										'analyzer' => [
												'docova_analyzer' => [
														'type' => "custom",
														'tokenizer' => "whitespace"
												]
										]
								]
						],
						'mappings' => [
								'document' => [
										'properties' => [
												'Doc_Title' => ['type' => "string", 'store' => false],
												'Description' => ['type' => "string", 'store' => false],
												'Date_Created' => [
													'type' => "date",
													'format' => "yyyy/MM/dd HH:mm:ss",
													'store' => false
												],
												'Doc_Status' => ['type' => "string", 'store' => false],
												'Keywords' => ['type' => "string", 'store' => false],
												'Author' => ['type' => "string", 'store' => false],
												'Creator' => ['type' => "string", 'store' => false],
												'Version' => ['type' => "string", 'store' => false],
												'Field_Values' => ['type' => "string", 'store' => false],	
												'Field_Date_Values' => ['type' => "date", 'format' => "yyyy/MM/dd HH:mm:ss", 'store' => false],	
												'Field_Number_Values' => ['type' => "float", 'store' => false],
												'Field_Name_Values' => [
														'type' => "object",
														'properties' => [
															'id' => ['type' => "string", 'store' => true, 'index' => "not_analyzed"],
															'name' => ['type' => "string", 'store' => false, 'index' => "not_analyzed"]
														]
												],
												'Field_Group_Values' => [
														'type' => "object",
														'properties' => [
															'id' => ['type' => "string", 'store' => true, 'index' => "not_analyzed"],
															'name' => ['type' => "string", 'store' => false, 'index' => "not_analyzed"]
														]
												],												
												'Doc_Type' => [
														'type' => "object",
														'properties' => [
																'id' => ['type' => "string", 'store' => true, 'index' => "not_analyzed"],
																'Doc_Name' => ['type' => "string", 'store' => false]
														]
												],
												'Folder' => [
														'type' => "object",
														'properties' => [
																'id' => ['type' => "string", 'store' => true,'index' => "not_analyzed"]
														]
												],
												'Views' => [
														'type' => "object",
														'properties' => [
																'id' => ['type' => "string", 'store' => true,'index' => "not_analyzed"]
														]
												],												
												'Library' => [
														'type' => "object",
														'properties' => [
																'id' => ['type' => "string", 'store' => true,'index' => "not_analyzed"]
														]
												],
												'Comments' => [
														'type' => "object",
														'properties' => [																
																'Comment' => ['type' => "string", 'store' => false]
														]
												],
												'Discussion' => [
														'type' => "object",
														'properties' => [
															'Subject' => ['type' => "string", 'store' => false],
															'Body' => ['type' => "string", 'store' => false],
															'AttachmentNames' => ['type' => "string", 'store' => false]
														]
												],
												'Discussion_Attachments' => [
														'type' => "attachment",
														'fields' => [
																'title' => ['store' => false],
																'content' => ['store' => false]
														]
												],
												'Bookmarks' => [
														'type' => "object",
														'properties' => [
																'Target_Folder' => ['type' => "string", 'store' => true,'index' => "not_analyzed"]
														]
												],
												'Attachments' => [
													'type' => "attachment",
													'fields' => [
															'title' => ['store' => false],
															'content' => ['store' => false]
													]
												],
												'AttachmentNames' => ['type' => "string", 'store' => false]											
										],
										'_source' => ['enabled' => false],
										'_all' => ['enabled' => true]
								]
						]
				]
		];
		
		$tempParams = [
				'index' => $this->indexName,
				'ignore_unavailable' => true
		];
		
		$indexcreated = false;

		$response = null;
		try{
			//--check if index exists
			$response = $this->esclient->indices()->getSettings($tempParams);
			if($response && isset($response[$this->indexName])){
				$indexcreated = true;
			}
		}catch(\Exception $e){
		}

		if($indexcreated === false){
			try{
				//--try and create the index
				$response = $this->esclient->indices()->create($createIndexParams);
				if($response && isset($response["acknowledged"]) && $response["acknowledged"]){
					$indexcreated = true;
				}
			}catch(\Exception $e){
				echo $e->getMessage();
			}
		}

		if($indexcreated === true){
			if ($this->DEBUG) echo("Index " . $this->indexName . " was created successfully.".PHP_EOL);
		}else{
			if ($this->DEBUG) echo("Error: index ". $this->indexName . " could not be created.".PHP_EOL);
		}

		return $indexcreated;
	}


	function openIndex(){

		$indexexists = false;
		
		$tempParams = [
				'index' => $this->indexName,
				'ignore_unavailable' => true
		];
		
		$response = null;
		try{
			//--check if index exists
			$response = $this->esclient->indices()->getSettings($tempParams);
			if($response && isset($response[$this->indexName])){
				$indexexists = true;
			}
		}catch(\Exception $e){
			
		}



		if($indexexists === false){
			if ($this->DEBUG) echo("Error: index ". $this->indexName . " could not be opened.".PHP_EOL);
		}


		return $indexexists;
	}


	function deleteIndex(){
		
		$indexremoved = false;
		
		$tempParams = [
				'index' => $this->indexName
		];
		
		$response = null;
		try{
			//--check if index exists
			$response = $this->esclient->indices()->getSettings($tempParams);
			if($response && isset($response[$this->indexName])){
				$response = $this->esclient->indices()->delete($tempParams);
				if($response && isset($response["acknowledged"]) && $response["acknowledged"]){
					$indexremoved = true;
				}
			}
		}catch(\Exception $e){
		}
		
		if($indexremoved == true){			
			if ($this->DEBUG) echo("Index ". $this->indexName . " has been deleted.".PHP_EOL);
		}else{
			if ($this->DEBUG) echo("Error: index ". $this->indexName . " could not be deleted.".PHP_EOL);
		}
		
		return $indexremoved;	
	}

	function indexDocument($docobj, $otherinfo){
		try{	    
			$indexedok = false;
	
			$docid = $docobj->getId();
	
			$fileNames = $this->fetchAttachmentNames($docobj->getId());
	
			if ($this->DEBUG) echo("indexDocument - Building Index Parameters".PHP_EOL);
			$indexParams = null;
			try{
				//separate out the elements to better detect individual method failure
				$indexParams['index'] = $this->indexName;
				$indexParams['type']="document";
				$indexParams['id']=$docobj->getId();
				$bodyElements=array();
				try{
					$bodyElements['Attachments'] = $this->fetchAttachmentsContent($fileNames, $docid);
					$bodyElements['AttachmentNames'] = $fileNames;
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing atachments details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing atachments details - Document: '.$docid.' Error: '.$e;				
				}
				
				try{
					$auth=$docobj->getAuthor();
					if(!empty($auth)){
						$bodyElements['Author'] = array(
							$auth->getUserName(), 
							$auth->getUserNameDn(), 
							$auth->getUserNameDnAbbreviated()
						);
					}
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing author details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing author details - Document: '.$docid.' Error: '.$e;
				}
				
				try{
					$bodyElements['Bookmarks'] = $this->fetchBookmarks($docid);
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing bookmark details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing bookmark details - Document: '.$docid.' Error: '.$e;
				}
				
				try{
					$bodyElements['Comments'] = $this->fetchComments($docid);
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing comment details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing comment details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}		
			
				try{
					$bodyElements['Creator'] = array(
							$docobj->getCreator()->getUserName(), 
							$docobj->getCreator()->getUserNameDn(), 
							$docobj->getCreator()->getUserNameDnAbbreviated()
						);		
					$bodyElements['Date_Created'] = $docobj->getDateCreated()->format("Y/m/d H:i:s");
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing creator/creation details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing Creator/Creation details - Document: '.$docid.' Error: '.$e;
				}	
			
				try{	
					$bodyElements['Description'] = $docobj->getDescription();
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing description details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing description details - Document: '.$docid.' Error: '.$e;
				}
			
				try{
					$bodyElements['Discussion'] = $this->fetchDiscussions($docid);
					$bodyElements['Discussion_Attachments'] = $this->fetchDiscussionAttachments($docid);
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing discussion details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing discussion and discussion atachment details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}
			
				try{
					$bodyElements['Doc_Status'] = $docobj->getDocStatus();
					$bodyElements['Doc_Title'] = $docobj->getDocTitle();
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing subject and status: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing document subject and status - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}
			
				try{
					$dtype = $docobj->getDocType();
					if(!empty($dtype)){
						$bodyElements['Doc_Type'] = array(
							'id' => $dtype->getId(),
							'Doc_Name' => $dtype->getDocName()
						);
					}
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing type details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing document types - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}
			
				try{
					$bodyElements['Field_Values'] = $this->fetchInputTextValues($docid);
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing text field values: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing text field values details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}
			
				try{
//					$bodyElements['Field_Date_Values'] = $this->fetchInputDateValues($docid);
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing date field values: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing date field values details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}			

				try{
					$bodyElements['Field_Number_Values'] = $this->fetchInputNumberValues($docid);
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing numeric field values: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing numeric field values details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}						

				try{
					$bodyElements['Field_Name_Values'] = $this->fetchInputNameValues($docid);
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing name field values: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing name field values details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}	

				try{
					$bodyElements['Field_Group_Values'] = $this->fetchInputGroupValues($docid);
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing group field values: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing group field values details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}											
			
				try{
					$fobj = $docobj->getFolder();
					if(!empty($fobj)){
						$bodyElements['Folder'] = array('id'=> $fobj->getId());
					}
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error folder details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}
			
				try{
					$bodyElements['Keywords'] = $docobj->getKeywords();
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing keywords: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing keyword details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}
			
				try{
					$fobj = $docobj->getFolder();
					if(!empty($fobj)){
						$bodyElements['Library'] = array('id' => $fobj->getLibrary()->getId());
					}else{
						$appobj = $docobj->getApplication();
						if(!empty($appobj)){
							$bodyElements['Library'] = array('id' => $appobj->getId());
							
							if(!empty($otherinfo) && !empty($otherinfo['views'])){
								$bodyElements['Views'] = array('id' => $otherinfo['views']);
							}
							
						}
					}
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing library details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing library details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}
			
				try{
					$auth = $docobj->getAuthor();
					if(!empty($auth)){
						$bodyElements['Owner'] = array(
							$auth->getUserName(),
							$auth->getUserNameDn(),
							$auth->getUserNameDnAbbreviated()
						);
					}
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing owner details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing owner details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}
			
				try{
					$bodyElements['Version']= $docobj->getDocVersion() . "." . $docobj->getRevision();
				}
				catch (\PDOException $e){
					echo "indexDocument() FT PDO Error indexing version details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
					return false;
				}
				catch (\Exception $e){
					echo 'indexDocument() - Error indexing version details - Document: '.$docid.' Error: '.$e.PHP_EOL;
				}
			
				$indexParams['body'] = $bodyElements;
				unset($fileNames);		
			}
			catch (\PDOException $e){
				echo "indexDocument() FT PDO Error building index params: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $sie){
				echo "indexDocument() Error building index params: " . $sie->getMessage(). ' Trace: '.$sie->getTraceAsString().PHP_EOL;			
			}
			
			if ($this->DEBUG) echo("indexDocument - Built Index Parameters".PHP_EOL);
		//	$indexParamsCopy = array();
		//	$indexParamsCopy = array_merge($indexParams);
		//	$indexParamsCopy['body']['Attachments']=null;
		//	var_dump($indexParamsCopy);
			
			$response = null;
			try{
				if ($this->DEBUG) echo("indexDocument - Indexing Params".PHP_EOL);
				$response = $this->esclient->index($indexParams);
				if($response && isset($response["_shards"]) && $response["_shards"]["successful"] > 0 && $response["_shards"]["failed"] == 0){
					if ($this->DEBUG) echo("indexDocument - Indexed Params".PHP_EOL);
					$indexedok = true;
				}
			}catch(\Exception $e){
				echo "indexDocument() FT Error: " . $e->getMessage(). ' Trace: '.$e->getTraceAsString().PHP_EOL;			
			}
					
			return $indexedok;
		}
		catch (\PDOException $e){
			echo "indexDocument() FT PDO Error: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
			return false;
		}
	}
	
	function indexDocumentORIG($docobj){
	
		$indexedok = false;

		$docid = $docobj->getId();

		$fileNames = $this->fetchAttachmentNames($docobj->getId());
	
		echo "indexDocument - Building Index Parameters".PHP_EOL;
		$indexParams = null;
		try{
			//separate out the elements to better detect individual method failure
			$indexParams = ['index' => $this->indexName,
					'type' => "document",
					'id' => $docobj->getId(),
					'body' => [
							'Attachments' => $this->fetchAttachmentsContent($fileNames, $docid),
							'AttachmentNames' => $fileNames,
							'Author' => [
									$docobj->getAuthor()->getUserName(),
									$docobj->getAuthor()->getUserNameDn(),
									$docobj->getAuthor()->getUserNameDnAbbreviated()
							],
							'Bookmarks' => $this->fetchBookmarks($docid),
							'Comments' => $this->fetchComments($docid),
							'Creator' => [
									$docobj->getCreator()->getUserName(),
									$docobj->getCreator()->getUserNameDn(),
									$docobj->getCreator()->getUserNameDnAbbreviated()
							],
							'Date_Created' => $docobj->getDateCreated()->format("Y/m/d H:i:s"),
							'Description' => $docobj->getDescription(),
							'Discussion' => $this->fetchDiscussions($docid),
							'Discussion_Attachments' => $this->fetchDiscussionAttachments($docid),
							'Doc_Status' => $docobj->getDocStatus(),
							'Doc_Title' => $docobj->getDocTitle(),
							'Doc_Type' => [
									'id' => $docobj->getDocType()->getId(),
									"Doc_Name" => $docobj->getDocType()->getDocName()
							],
							'Field_Values' => $this->fetchInputValues($docid),
							'Folder' => ['id'=> $docobj->getFolder()->getId()],
							'Keywords' => $docobj->getKeywords(),
							'Library' => ['id' => $docobj->getFolder()->getLibrary()->getId()],
							'Owner' => [
									$docobj->getAuthor()->getUserName(),
									$docobj->getAuthor()->getUserNameDn(),
									$docobj->getAuthor()->getUserNameDnAbbreviated()
							],
							'Version' => $docobj->getDocVersion() . "." . $docobj->getRevision()
					]
			];
			unset($fileNames);
		}
		catch (\Exception $sie){
			echo "indexDocument() Error building index params: " . $sie->getMessage(). ' Trace: '.$sie->getTraceAsString().PHP_EOL;
			throw $sie;
		}
		echo "indexDocument - Built Index Parameters".PHP_EOL;
	
		$response = null;
		try{
			echo "indexDocument - Indexing Params".PHP_EOL;
			$response = $this->esclient->index($indexParams);
			if($response && isset($response["_shards"]) && $response["_shards"]["successful"] > 0 && $response["_shards"]["failed"] == 0){
				echo "indexDocument - Indexed Params".PHP_EOL;
				$indexedok = true;
			}
		}catch(\Exception $e){
			echo "indexDocument() FT Error: " . $e->getMessage(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
			throw $e;
		}
				
		
		return $indexedok;
	}
}