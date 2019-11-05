<?php

namespace Docova\DocovaBundle\Validation;

use Doctrine\DBAL\Connection;
//use Elasticsearch\ClientBuilder;


/**
 * Initializing Database validating
 * @author javad rahimi, chris fales. jeff primeau
 *        
 */
class Validator
{
	protected $connection;
	protected $em;
	public $indexName;
	private $esclient;
	protected $root;
	protected $UPLOAD_FILE_PATH;
	private $maxFileBytes;
	private $maxTotalFileBytes;
	private $ignoreAttachmentTypes;
	
	public function __construct($em,Connection $connection, $root)
	{	
//echo 'Root: '.$root;

		$this->indexName = "docova";
		$this->connection = $connection;
		$this->em=$em;
			
		$this->root = !empty($root) ? $root : getcwd().DIRECTORY_SEPARATOR.'..';
		$this->UPLOAD_FILE_PATH = $this->root.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
		$this->maxFileBytes = 1024 * 1024 * 6; //6MB cap on validating any one file
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
	
	public function queryFieldValues($doc){		
		$output = array();
		try{				
			$elementsQb=$this->em->getRepository("DocovaBundle:FormElementValues")->createQueryBuilder("V");
			$elementsQb->select("V")			
			->where("V.Document=?1")
			->setParameter(1, $doc);
			
			$result = $elementsQb->getQuery()->getResult();
			
			if (!empty($result) && !empty($result['0']))
			{
				foreach ($result as $value) {
					$output[] = $value->getFieldValue();
				}
			}
			
			if (empty($output))				
				echo PHP_EOL."No results found for field values query for document ".$doc->getId();
			
			return $output;
		}
		catch (\PDOException $pe){
			echo PHP_EOL."queryFieldValues() - PDO Exception: ".$pe->getMessage()." Code: ".$pe->getCode()." Trace: ".$pe->getTraceAsString();			
			throw $pe;
		}
		catch (\Exception $e){
			echo PHP_EOL."queryFieldValues() - Exception: ".$e->getMessage()." Line: ".$e->getLine()." Trace: ".$e->getTraceAsString();
			throw $e;
		}
		return $output;
	}
	/**
	 * Get array of a document input values
	 * 
	 * @param string $docid
	 * @return array 
	 */
	public function fetchInputValues($docid)
	{
		$output = array();
		try{		
			$query = 'SELECT V.Field_Value FROM tb_subform_element_values AS V WHERE V.Doc_Id = ?';
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
			echo "fetchInputValues() - PDO Exception: ".$pe->getMessage()." Code: ".$pe->getCode()." Trace: ".$pe->getTraceAsString();
			echo "fetchInputValues() - PDO Query: ".$query;
			throw $pe;
		}
		catch (\Exception $e){
			echo "fetchInputValues() - Exception: ".$e->getMessage()." Line: ".$e->getLine()." Trace: ".$e->getTraceAsString();
			throw $e;
		}
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
		$exceptionlist = explode(",", strtolower($this->ignoreAttachmentTypes));
		$contents = array();
		if (!empty($fileNames) && is_array($fileNames)) 
		{
			foreach ($fileNames as $name) {
				$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
				if($ext == "" || !in_array($ext, $exceptionlist)){
					$fname = md5($name);
					if (file_exists($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$docid.DIRECTORY_SEPARATOR.$fname)) 
					{
						$content = $this->getEncodedFile($fname, $docid);
						if (!empty($content))
						{
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
		$content = file_get_contents($this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$docid.DIRECTORY_SEPARATOR.$filename, false, NULL, -1, $this->maxFileBytes);
		if ($content) 
		{
			return base64_encode($content);
		}
		else {
			return false;
		}
	}	



	function validateDocument($docobj){
		try{
		    $DEBUG=true;
		    
			$indexedok = false;
	
			$docid = $docobj->getId();
	
			try{
				$fieldValues = $this->queryFieldValues($docobj);
				echo PHP_EOL."Found ".count($fieldValues)." field value(s) for document ".$docid;
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating field values check 1: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating field values check 1 - Document: '.$docid.' Error: '.$e;
				return false;
			}
			$fileNames = $this->fetchAttachmentNames($docobj->getId());
	
		//	if ($DEBUG) echo "validateDocument - Building validation Parameters".PHP_EOL;
			$indexParams = null;
			try{
				//separate out the elements to better detect individual method failure
			$indexParams['index'] = $this->indexName;
			$indexParams['type']="document";
			$indexParams['id']=$docobj->getId();
			$bodyElements=array();
			try{
				$bodyElements['Attachments'] = ''; //$this->fetchAttachmentsContent($fileNames, $docid);
				$bodyElements['AttachmentNames'] = $fileNames;
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating atachments details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating atachments details - Document: '.$docid.' Error: '.$e;	
				return false;			
			}
			try{
				$bodyElements['Author'] = array(
						$docobj->getAuthor()->getUserName(), 
						$docobj->getAuthor()->getUserNameDn(), 
						$docobj->getAuthor()->getUserNameDnAbbreviated()
					);
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating author details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating author details - Document: '.$docid.' Error: '.$e;
				return false;
			}
			try{
				$bodyElements['Bookmarks'] = $this->fetchBookmarks($docid);
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating bookmark details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating bookmark details - Document: '.$docid.' Error: '.$e;
				return false;
			}
			try{
				$bodyElements['Comments'] = $this->fetchComments($docid);
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating comment details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating comment details - Document: '.$docid.' Error: '.$e;
				return false;
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
				echo "validateDocument()  PDO Error validating creator/creation details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating Creator/Creation details - Document: '.$docid.' Error: '.$e;
			}	
			try{	
				$bodyElements['Description'] = $docobj->getDescription();
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating description details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating description details - Document: '.$docid.' Error: '.$e;
			}
			try{
				$bodyElements['Discussion'] = $this->fetchDiscussions($docid);
				$bodyElements['Discussion_Attachments'] = $this->fetchDiscussionAttachments($docid);
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating discussion details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating discussion and discussion atachment details - Document: '.$docid.' Error: '.$e;
			}
			
			try{
				$bodyElements['Doc_Status'] = $docobj->getDocStatus();
				$bodyElements['Doc_Title'] = $docobj->getDocTitle();
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating subject and status: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating document subject and status - Document: '.$docid.' Error: '.$e;
			}
			
			try{
				$bodyElements['Doc_Type'] = array(
					'id' => $docobj->getDocType()->getId(),
					'Doc_Name' => $docobj->getDocType()->getDocName()
				);
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating type details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating document types - Document: '.$docid.' Error: '.$e;
			}
			
			try{
				$bodyElements['Field_Values'] = $this->fetchInputValues($docid);
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating field values: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating field values details - Document: '.$docid.' Error: '.$e;
			}
			try{
				$bodyElements['Folder'] = array('id'=> $docobj->getFolder()->getId());
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error folder details - Document: '.$docid.' Error: '.$e;
			}
			try{
				$bodyElements['Keywords'] = $docobj->getKeywords();
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating keywords: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating keyword details - Document: '.$docid.' Error: '.$e;
			}
			try{
				$bodyElements['Library'] = array('id' => $docobj->getFolder()->getLibrary()->getId());
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating library details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating library details - Document: '.$docid.' Error: '.$e;
			}
			try{
				$bodyElements['Owner'] = array(
					$docobj->getAuthor()->getUserName(),
					$docobj->getAuthor()->getUserNameDn(),
					$docobj->getAuthor()->getUserNameDnAbbreviated()
				);
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating owner details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating owner details - Document: '.$docid.' Error: '.$e;
			}
			try{
				$bodyElements['Version']= $docobj->getDocVersion() . "." . $docobj->getRevision();
			}
			catch (\PDOException $e){
				echo "validateDocument()  PDO Error validating version details: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $e){
				echo 'validateDocument() - Error validating version details - Document: '.$docid.' Error: '.$e;
			}
			
			$indexParams['body'] = $bodyElements;
			unset($fileNames);		
			}
			catch (\PDOException $e){
				echo "validateDocument() PDO Error building validation params: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
				return false;
			}
			catch (\Exception $sie){
				echo "validateDocument() Error building validation params: " . $sie->getMessage(). ' Trace: '.$sie->getTraceAsString().PHP_EOL;			
			}
		//	echo "validateDocument - Built validation Parameters".PHP_EOL;
		
			return true;
		}
		catch (\PDOException $e){
			echo "validateDocument() PDO Error: " . $e->getMessage().'Code: '.$e->getCode(). ' Trace: '.$e->getTraceAsString().PHP_EOL;
			return false;
		}
	}
	
	

}