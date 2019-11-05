<?php

namespace Docova\DocovaBundle\IMAP;


//use Docova\DocovaBundle\Logging\FileLogger;
use Docova\DocovaBundle\Entity\AttachmentsDetails;
use Docova\DocovaBundle\Entity\FormTextValues;
use Docova\DocovaBundle\Entity\FormDateTimeValues;
use Docova\DocovaBundle\Entity\Documents;
use Docova\DocovaBundle\Controller\Miscellaneous;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Extensions\MiscFunctions;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;


/*
 * Helper class to assist with importing an IMAP message into DOCOVA SE 
 * ***********************  IMPORTANT  **************************************
 * If editing this file in eclipse be sure to set character encoding to UTF-8 
 * so that foreign character codes contained here are not improperly saved
 * ***********************  IMPORTANT  **************************************
 */

class IMAPMsgImporter {
	private $logger=null;
	private $mailbox=null;
	private $msg_no=null;
	private $document=null;
	private $msgHeader=null;
	private $msgId=null;
	private $file_path=null;
	private $web_path=null;
	private $container=null;
	private $entityManager=null;
	private $user = null;
	private $doc_type=null;
	private $miscellaneous=null;
	private $customAcl=null;
	
	public $DEBUG = false;
	
	public function __construct($container,/*FileLogger or null*/ $logger,$em,$mailbox,$msg_no){
		$this->container = $container;
		$this->mailbox = $mailbox;
		$this->msg_no = $msg_no;		
		$this->logger = $logger;
		$this->entityManager = $em;
		$this->setMsgHeader();
	}
	
	private function setMsgHeader(){
		$this->msgHeader = imap_headerinfo($this->mailbox, $this->msg_no);
		if (empty($this->msgHeader)){
			if ($this->logger!=null && $this->DEBUG){
			    $this->logger->log("Email message # ".$this->msg_no.", not found!", $this->logger::WARNING );
			}
			//Just ignore this exception, the message must have moved, or must be in use
			return false;
		}
		
		$this->msgId = empty($this->msgHeader->message_id) ? "Unknown" : $this->msgHeader->message_id;
		if ($this->logger!=null){
				$this->logger->log("Email message header # ".$this->msg_no." Header id: ".$this->msgId);
		}
	}
	public function setFilePath($filePath){
		$this->file_path = $filePath;
	}
	public function setWebPath($webPath){
		$this->web_path = $webPath;
	}
	public function getFilePath($filePath){
		return $this->file_path;
	}
	public function getWebPath($webPath){
		return $this->web_path;
	}
	public static function computeWebPath($container){
		$instance = ($container->hasParameter('docova_instance') && $container->getParameter('docova_instance') ? $container->getParameter('docova_instance') : 'Docova');
		return '/'.$instance."/_storage";
	}
	public static function computeFilePath($container){
		$instance = ($container->hasParameter('docova_instance') && $container->getParameter('docova_instance') ? $container->getParameter('docova_instance') : 'Docova');
		return $container->getParameter('document_root') ? $container->getParameter('document_root').DIRECTORY_SEPARATOR.$instance.DIRECTORY_SEPARATOR.'_storage' :  $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.$instance.DIRECTORY_SEPARATOR.'_storage';
	}
	public function getEntityManager() {
		return $this->entityManager;
	}
	public function setEntityManager($entityManager) {
		$this->entityManager = $entityManager;
		return $this;
	}
	
	public function createMemoDocument($folder,$dtMailDate=null,$subject=null){
			$em = $this->getEntityManager();
		
			$entity = new Documents();
			
			$miscfunctions = new MiscFunctions();
				
			$entity->setId($miscfunctions->generateGuid());
			
			$user = $this->getUser();
			$entity->setAuthor($user);
			$entity->setCreator($user);
			$entity->setModifier($user);
			
			if (!empty($dtMailDate)){
				$entity->setDateCreated($dtMailDate);
			}
			else{
				$entity->setDateCreated(new \DateTime());	
			}
			$entity->setDateModified(new \DateTime());
			if ($subject==null){
				$subject = $this->getSubject($this->getMsgHeader(),$entity);
			}
			$entity->setDocTitle($subject);
			if ($this->doc_type==null){
				$this->doc_type = self::getMemoType($this->getEntityManager());
			}
			$entity->setDocType($this->doc_type);
			$entity->setDocStatus('Released');
			$entity->setStatusNo(1);
			$entity->setFolder($folder);
			$entity->setOwner($user);
			$this->document = $entity;
			return $entity;
	}
	
	public function importMessage($document=null){
		$em = $this->getEntityManager();
		
		if ($document==null){
			$document = $this->document;
		}
		if ($this->document==null){
			throw new \Exception("No memo available to save.  Call createMemo first!");
		}
		
		try {
			$em->persist($document);
			$em->flush();
		}
		catch (\Doctrine\DBAL\DBALException $de) {
			throw new \Exception("DBAL ERROR: ".$de->getMessage());
		}
		catch (\PDOException $pe) {
			throw new \Exception( "PDO ERROR: ".$pe->getMessage());
		}
		catch (\Exception $e) {
			if (\strpos($e->getMessage(),"The EntityManger is closed.")===false)
				throw new \Exception($e->getMessage());
				else{
					if ($this->DEBUG)
					    if ($this->logger!=null) $this->logger->log("Fatal Error - The EntityManager is closed." , $this->logger::NOTICE );
		
						//echo "Fatal Error - The EntityManager is closed.".PHP_EOL;
						throw new \Exception("The EntityManager is closed");
				}
		}
		
		$subject = $document->getDocTitle();
		$folder = $document->getFolder();
		
		if ($this->logger!=null)  $this->logger->log("Document [".$subject."] saved to ".$folder->getFolderName()." as ".$document->getId() , $this->logger::NOTICE );
		
			
		if ($this->DEBUG){				
			if ($this->logger!=null)  $this->logger->log("Processing mail content for UID: ".$this->getMsgNo());
		}
		
		try{
			$content = $this->getMailContent($this->getMailbox(), $this->getMsgNo(), $document, $this->getMsgId());
		
					
			/*
			 if ($this->logger!=null)  $this->logger->log(PHP_EOL." Inline Images Types: ".\json_encode($content["inlineImages"]));
			 if ($this->logger!=null)  $this->logger->log(PHP_EOL." Content Plain: ".$content["plain"]);
			 if ($this->logger!=null)  $this->logger->log(PHP_EOL." Content HTML: ".$content["html"]);
			 */
		
			if (!empty($content['plain'])){
				$content['plain']=nl2br($content['plain']);
			}
					
			//if ($this->logger!=null)  $this->logger->log("Text: [".\json_encode($content['plain'])."]");
			//if ($this->logger!=null)  $this->logger->log("HTML: ".\json_encode($content['html'])."]");
		}
		catch (\Exception $ee){
				if ($this->DEBUG)
				    if ($this->logger!=null)  $this->logger->log("Error loading content ".$ee->getMessage() , $this->logger::ERROR );
		
					throw new \Exception("Error loading content ".$ee->getMessage());
		}
			
		if ($this->logger!=null)  $this->logger->log("Processed mail content");
			
		//Populate the subform fields with their related field data
		$this->importSubformData($document,$this->getMsgHeader(),$content);
			
		$security_check = $this->getMiscellaneous();
		$security_check->createDocumentLog($em, 'CREATE', $document);
		$customAcl = $this->getCustomACL();
		$customAcl->insertObjectAce($document, $this->user, 'owner', false);
		$customAcl->insertObjectAce($document, 'ROLE_USER', 'view');
	}
	
	private function getMailContent($mailbox, $msg_no, $document,$header_id)
	{
		if ($this->file_path==null){
			$this->setFilePath(self::computeFilePath($this->container));
		}
		if ($this->web_path==null){
			$this->setWebPath(self::computeWebPath($this->container));
		}
		
		try{
			$output = array('plain' => '', 'html' => '', 'inlineImages' => array());
			try {
				$mail_structure = imap_fetchstructure($mailbox, $msg_no);
			}
			catch (\Exception $e) {
				echo "ERROR: ".$e->getMessage();
			}
	
			if (empty($mail_structure))
				if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() Emty mail structure");
	
				if (empty($mail_structure) || empty($mail_structure->parts))
				{
					if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() - Single message part found");
					$res = $this->getParts($mailbox, $msg_no, $mail_structure, 0, $document,$header_id);
	
					if (!empty($res)){
						if (!empty($output['plain']))
							$output['plain'] .= $res['plain'];
							else
								$output['plain'] = $res['plain'];
									
								if (!empty($output['html']))
									$output['html'] .= ''. $res['html'];
									else
										$output['html'] = $res['html'];
											
										$output["inlineImages"]=\array_merge($output["inlineImages"],$res["inlineImages"]);
											
					}
					else{
						if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() No body found to process");
					}
				}
				else {
					//if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() - Multi-part message found");
					foreach ($mail_structure->parts as $p_no => $part) {
						$res = $this->getParts($mailbox, $msg_no, $part, $p_no+1, $document,$header_id);
	
	
						if (!empty($res)){
							//if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() - Part: ".$p_no." Value: ".\json_encode($res));
								
							if (!empty($output['plain']))
								$output['plain'] .= $res['plain'];
								else
									$output['plain'] = $res['plain'];
										
									if (!empty($output['html']))
										$output['html'] .= $res['html'];
										else
											$output['html'] = $res['html'];
												
											$output["inlineImages"]=\array_merge($output["inlineImages"],$res["inlineImages"]);
	
												
											//if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() - Res: ".\json_encode($res));
											//if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() - Output: ".\json_encode($output));
						}
						else{
							if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() No body found to process");
						}
					}
				}
	
				if (!empty($output['html']))
				{
					//if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() Found HTML");
					$matches=null;
					preg_match_all('/src="cid:(.*)"/Uims', $output['html'], $matches);
					if (empty($matches[1]))
					{
						preg_match_all('/src=cid:(.*)\s/Uim', $output['html'], $matches);
					}
	
					if (!empty($matches[1]))
					{
						foreach($matches[1] as $match) {
								
								
							$embeddedPath = $this->file_path.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR;
							$embeddedPathWeb = $this->web_path."/".'Embedded'."/".$document->getId()."/";
							
							$attachmentPath = $this->web_path."/".$document->getId()."/";
								
							//check to see if the path reflects a file extension
							if (empty($output["inlineImages"][$match])){
								$this->logger->log("No image file name found for content id: ".$match);
								continue;
							}
								
							$imageFileName = $output["inlineImages"][$match];
								
							$fileExtension = pathinfo($attachmentPath, PATHINFO_EXTENSION);
							$hasExtension = !empty($fileExtension) && $fileExtension!="";
								
							if (!empty($output["inlineImages"][$match]) && $hasExtension==false){
								if ($this->logger!=null) $this->logger->log("Resolving cid: "+$match." to attachment: ".$imageFileName);
	
								$attachmentPath .= md5($imageFileName);
								$embeddedPathWeb .= $imageFileName;
								$embeddedPath .= $imageFileName;
							}
							else
								$this->logger->log("Inline image has an extension [".$fileExtension."]");
							
								if (file_exists($embeddedPath)) {
									$search = array("src=\"cid:$match\"","src=3D\"cid:$match\"", "src=cid:$match");
									$replace = "src=\"$embeddedPathWeb\"";
									$output['html'] = str_replace($search, $replace, $output['html']);
									if ($this->logger!=null && $this->DEBUG) $this->logger->log("Replacing: ".\json_encode($search)." With: ".$replace);
								}
	
						}
					}
				}
	
				//	if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() Result:");
				//if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() ".\json_encode($output));
				return $output;
		}
		catch (\Exception $e) {
			$isDisconnected=!(\strpos($e->getMessage(), "connection is lost or broken" )===false);
				
			if ($isDisconnected) {
				if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() - IMAP connection lost or broken, removing ".$document->getId());
				$this->entityManager->remove($document);
				$this->entityManager->flush();
				if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() - Document removed");
			}
			if ($this->logger!=null && $this->DEBUG) $this->logger->log("getMailContent() - ERROR: ".$e->getMessage());
			throw $e;
		}
	}
	
	private function getParts($mailbox, $msg_no, $structure, $part_no, $document,$header_id)
	{
		$output = array('plain' => '', 'html' => '', 'attachments' => false, 'inlineImages' => array());
		if (!empty($part_no) ){
			if ($this->logger!=null) $this->logger->log("getParts() - Fetching body part #: ".$part_no);
			$content =imap_fetchbody($mailbox, $msg_no, $part_no);
			if ($content==null && empty($structure->parts)){
				//try as plain text
				if ($this->logger!=null) $this->logger->log("getParts() - Fetching body as plain text for message #: ".$msg_no);
				$content =imap_body($mailbox, $msg_no);
			}
		}
		else{
			if ($this->logger!=null) $this->logger->log("getParts() - Fetching body for message #: ".$msg_no);
			$content =imap_body($mailbox, $msg_no);
		}
	
		if (empty($content)) {
			if ($this->logger!=null) $this->logger->log("ERROR: getParts() - No body content available from IMAP");
			if ($this->isDisconnected()){
				throw new \Exception("ERROR: getParts() - Aborting, IMAP connection is lost or broken");
			}
			return null;
		}
	
		//	if ($this->DEBUG) if ($this->logger!=null) $this->logger->log("getParts() - Encoding: ".$structure->encoding);
	
		if ($structure->encoding == 0){
			//7-bit
		}
		else if ($structure->encoding == 1){
			//8-bit
		}
		else if ($structure->encoding == 4) {
			if ($this->DEBUG && $this->logger!=null) $this->logger->log("Converting encoding to 4 (quoted printable) to UTF-8");	
			if ($this->DEBUG && $this->logger!=null) $this->logger->log("Printed decode");
			$content = imap_utf8(quoted_printable_decode($content));
		}
		elseif ($structure->encoding == 3) {
			$content = base64_decode($content);
		}
	
	
		if (!empty($structure->dparameters) )
		{
			$up_field = $this->getEntityManager()->getRepository('DocovaBundle:DesignElements')->findOneBy(array('fieldName' => 'MCSF_DLIUploader1', 'trash' => false));
			foreach ($structure->dparameters as $p)
			{
				//if ($this->logger!=null) $this->logger->log("Parameters a): ".\json_encode($p));
				if ((strtoupper($p->attribute)=='NAME' || strtoupper($p->attribute)=='FILENAME') && !empty($p->value)) {
					$output['attachments'] = true;
					//	$attName = $this->getDecodedValue($p->value,false,"AttachmentCheck");
					//	$attInfo = new \SplFileInfo($attName);
					//	$isEML = !empty($attInfo) && \strtolower($attInfo->getExtension())=="eml";
	
					//Do not upload as an attachment if this an inline image
						
					if ($structure->type != 5) {
						if ($this->logger!=null) $this->logger->log("getParts() - Uploading attachment: ".imap_utf8($p->value));
						$this->uploadAttachedFile($p->value, $content, $document, $up_field);
						$content='';
					}
					else if (empty($structure->id)) {// || ($isEML && !empty($p->value))) {
						if ($this->logger!=null) $this->logger->log("getParts() - Uploading attachment: ".imap_utf8($p->value));
						//this is not an inline image so add it as an attachment
						$this->uploadAttachedFile($p->value, $content, $document, $up_field);
						$content='';
					}
					else{
						//	if ($this->logger!=null) $this->logger->log("getParts() - Inline content detected: ".$structure->id);
					}
				}
	
			}
		}
		elseif (!empty($structure->parameters))
		{
			$up_field = $this->getEntityManager()->getRepository('DocovaBundle:DesignElements')->findOneBy(array('fieldName' => 'MCSF_DLIUploader1', 'trash' => false));
	
			foreach ($structure->parameters as $p)
			{
				//if ($this->logger!=null) $this->logger->log("Structure Type: ".$structure->type. " Parameters b): ".\json_encode($p));
				if ($structure->type==2){
					//mark part as having attachments to avoid adding the part to the body
					$output['attachments'] = true;
					//email message, import as eml
					if ($this->logger!=null) $this->logger->log("Processing EML content");
					$messageFile="Message_".mt_rand().".eml";
					if ($this->logger!=null) $this->logger->log("EML File: ".$messageFile);
					$this->uploadAttachedFile($messageFile, $content, $document, $up_field);
					$content='';
				}
				else if (!empty($p) && strtoupper($p->attribute)=='NAME' || strtoupper($p->attribute)=='FILENAME') {
					$output['attachments'] = true;
						
					if ($this->logger!=null) $this->logger->log("getParts() - Uploading inline attachment content: ".\json_encode($p->value));
					//Do not upload as an attachment if this an inline image
					if ($structure->type != 5) {
						$this->uploadAttachedFile($p->value, $content, $document, $up_field);
						$content='';
					}
					else  {
						//this is not an inline image so add it as an attachment
						$this->uploadAttachedFile($p->value, $content, $document, $up_field);
						$content='';
					}
	
				}
				else if (!empty($content) && !empty($p) && !empty($p->attribute) && strtoupper($p->attribute)=='CHARSET'){

					$charset = $p->value;
					if (!empty($charset))
						if ($this->logger!=null) $this->logger->log("Detected charset ".\json_encode($charset)." for Body");
							
						$mbCharset = $this->getCharsetName($charset);

						if (!empty($charset) && $charset!="UTF-8" && $charset!="utf-8" && \in_array($mbCharset,mb_list_encodings())   ){

							$charset = strtoupper($charset);
							if ($this->logger!=null) $this->logger->log("Detected charset ".\json_encode($mbCharset)." converting to UTF-8 for Body");
							$contentBefore = $content;
							try{
								$content = mb_convert_encoding($content, "UTF-8", $mbCharset);
								if ($this->isValid($content)==false){
									if ($this->logger!=null) $this->logger->log("mb Conversion is not valid UTF-8, attempting iconv");
									try {
										$content = iconv($mbCharset,"UTF-8//TRANSLIT",$contentBefore);
									} catch (\Exception $e) {
										if ($this->logger!=null) $this->logger->log( "getParts() iconv error - ".$e->getMessage());
									}
										
									if (empty($content) && !empty($contentBefore) && $mbCharset=="GB2312") {
										if ($this->logger!=null) $this->logger->log( "getParts() Conversion from GB2312 not successful, try GBK");
										$content = iconv("GBK","UTF-8//TRANSLIT",$contentBefore);
									}
									if (empty($content) && !empty($contentBefore) && ($mbCharset=="US-ASCII" || $mbCharset=="ASCII" )) {
										if ($this->logger!=null) $this->logger->log( "getParts() Conversion from ".$mbCharset. " not successful, try ISO");
										$content = iconv("ISO-8859-1","UTF-8//TRANSLIT",$contentBefore);
									}
									if (empty($content) && !empty($contentBefore) && $mbCharset=="BIG5") {
										if ($this->logger!=null) $this->logger->log( "getParts() Conversion from BIG5 not successful, try alternate conversion");
										$content = $this->big52utf8($contentBefore);
									}
								}								
							}
							catch (\Exception $e){
								$this->markDocumentTruncated($document);
								if ($this->logger!=null) $this->logger->log("getParts() - "+$e->getMessage()." Trace: ".$e->getTraceAsString());
							}
								
							if (empty($content) && !empty($contentBefore)){
								if ($this->logger!=null) $this->logger->log("Conversion failed, returning to before content");
								$this->markDocumentTruncated($document);
								$content = $contentBefore;
							}
	
						}						
						else if (!empty($charset) && $mbCharset=="UTF-8" ){
							if ($this->logger!=null) $this->logger->log( "UTF-8 detected, leave as-is\n");
						}
						else if (!empty($charset) && $mbCharset!="UNKNOWN"){
							$contentBefore=$content;
							if ($this->logger!=null) $this->logger->log( "Unknown or default charset: ".\json_encode($charset)." attempting iconv\n");
	
	
							try {
								$convText = iconv($mbCharset,"UTF-8//TRANSLIT",$content);
							} catch (\Exception $e) {
								if ($this->logger!=null) $this->logger->log( "getParts() iconv error - ".$e->getMessage());
	
							}						
	
							if (empty($convText) && !empty($content) && $mbCharset=="GB2312") {
								$convText = iconv("GBK","UTF-8//TRANSLIT",$content);
							}
							if (empty($convText) && !empty($content) && $mbCharset=="BIG5") {
								if ($this->logger!=null) $this->logger->log( "getParts() Conversion from BIG5 not successful, try GBK");							
								$convText = $this->big52utf8($content);
							}
							if (empty($convText) && !empty($content) && ($mbCharset=="US-ASCII" || $mbCharset=="ASCII" )) {
								if ($this->logger!=null) $this->logger->log( "getParts() Conversion from ".$mbCharset." not successful, try ISO");
								$convText = iconv("ISO-8859-1","UTF-8//TRANSLIT",$content);
							}
							if (empty($convText) ){
								$this->markDocumentTruncated($document);
								if ($this->logger!=null) $this->logger->log( "iconv failed - conversion error");
							}
							else{
								$content=$convText;
							}
	
							if ($this->isValid($content)==false){
								if ($this->logger!=null) $this->logger->log("WARNING: Converted body content is invalid");
								$this->markDocumentTruncated($document);
								$content = $contentBefore;
							}
						}
							
				}
	
			}
		}
	
		//if (!empty($structure) && empty($structure->id))
		//	if ($this->logger!=null) $this->logger->log("Structure Type: ".$structure->type);
		//elseif (!empty($structure) )
		//	if ($this->logger!=null) $this->logger->log("Structure Type: ".$structure->type. " Structure ID: ".\json_encode($structure->id) );
			
		if (!empty($structure) && empty($output['attachments']) && $structure->type == 0 && !empty($content))
		{
			//if ($this->logger!=null) $this->logger->log("Encoding is ".$structure->encoding);
			//	if ($this->logger!=null) $this->logger->log("Attachments ".\json_encode($output['attachments']));
			//if ($this->logger!=null) $this->logger->log("Adding text content:\n".$content);
			//	if ($this->logger!=null) $this->logger->log("Structure:\n".\json_encode($structure));
			if (strtolower($structure->subtype)=='plain')
				$output['plain'] .= trim($content);// ."\n\n";
				else
					$output['html'] .= $content;//."<br><br>";
		}
		elseif ( !empty($structure) && ($structure->type == 5 && !empty($structure->id)) && !empty($content))
		{
			if ($this->logger!=null) $this->logger->log("Adding inline image");
			//NOTE: Image extensions should really be computed based on the image types
			//Inline images do not require the proper file name extensions and therefore may not be present
			//If the extension is not properly identified the inline image will not render in a browser
				
			//if ($this->DEBUG) if ($this->logger!=null) $this->logger->log("getParts() Attachment processessing for Structure: ".\json_encode($structure));
			//if ($this->DEBUG) if ($this->logger!=null) $this->logger->log("Content: ".\json_encode($content));
				
			//if ($this->logger!=null) $this->logger->log("Structure:\n".\json_encode($structure,JSON_PRETTY_PRINT));
			if ($this->logger!=null) $this->logger->log("Sub-type:\n".$structure->subtype);
				
				
			$imageFileName = '';
			//look for a file name in the attributes
			if (!empty($structure->dparameters)){
				foreach ($structure->dparameters as $p)
				{
					//if ($this->logger!=null) $this->logger->log("Parameters a): ".\json_encode($p));
					if ((strtoupper($p->attribute)=='NAME' || strtoupper($p->attribute)=='FILENAME') && !empty($p->value)) {
						$imageFileName = $p->value;
						break;
					}
				}
			}
				
			if ($this->logger!=null) $this->logger->log("Image File:\n".$imageFileName);
				
			$ext = "";
	
			//compute the inline image id
			$img_id = str_replace(array('<', '>'), '', $structure->id);
				
			if ($this->logger!=null) $this->logger->log("Image ID:\n".$img_id);
				
			if (\strtolower($structure->subtype)=="gif")
				$ext = ".gif";
				else if (\strtolower($structure->subtype)=="png")
					$ext = ".png";
					else if (\strtolower($structure->subtype)=="bmp")
						$ext = ".bmp";
						else if (\strtolower($structure->subtype)=="tiff" || \strtolower($structure->subtype=="tif"))
							$ext = ".tif";
							else if (\strtolower($structure->subtype)=="x-icon")
								$ext = ".ico";
								else if (\strtolower($structure->subtype)=="jpg" || \strtolower($structure->subtype)=="jpeg" || \strtolower($structure->subtype=="jpe"))
									$ext = ".jpg";
									else
										$ext = "";
										if ($this->logger!=null) $this->logger->log("File extension:\n".$ext);
											
											
										//Remember the types for each inline image id.  Some inline objects will not have file extensions
										//These extensions will need to be properly computed in order to render the image in the browser
											
										//if ($this->logger!=null) $this->logger->log("Subtype: ".\strtolower($structure->subtype)." Ext: ".$ext. " File Ext: ".pathinfo($structure->id,PATHINFO_EXTENSION));
											
										//check to see if the path reflects a file extension
											
										if ($imageFileName!=""){
											$fileExtension = pathinfo($imageFileName, PATHINFO_EXTENSION);
										}
										else{
											$fileExtension = pathinfo($img_id, PATHINFO_EXTENSION);
										}
											
										$hasGT=!(\strpos($fileExtension,'>')===false);
										$hasExtension = !empty($fileExtension) && $fileExtension!="" && \strlen($fileExtension)<=4 && $hasGT==false;
											
										if (! $hasExtension){
											if ($imageFileName!=""){
												$output["inlineImages"][$img_id]=$imageFileName.$ext;
												$img_id=$imageFileName.$ext;
											}
											else{
												$output["inlineImages"][$img_id]=$img_id . $ext;
												$img_id .= $ext;
											}
	
	
										}
										else if ($imageFileName!=""){
											$output["inlineImages"][$img_id]=$imageFileName;
											$img_id=$imageFileName;
										}
										$output['attachments'] = true;
										$this->uploadAttachedFile($img_id, $content, $document);
										$content='';
											
		}
		elseif ( !empty($structure) && $structure->type == 2 && !empty($content))
		{
			//email message, import as eml
			$output['attachments'] = true;
			if ($this->logger!=null) $this->logger->log("Processing EML content");
			$messageFile="Message_".\mt_rand().".eml";
			if ($this->logger!=null) $this->logger->log("EML File: ".$messageFile);
			$this->uploadAttachedFile($messageFile, $content, $document, $up_field);
			$content='';
		}
		else{
			//if ($this->logger!=null) $this->logger->log("getParts() - No conditions met");
		}
		if (!empty($structure) && !empty($structure->parts) && $structure->type != 2)
		{
			$part_no = (empty($part_no)) ? '' : $part_no.'.';
				
			foreach ($structure->parts as $no => $substructure)
			{
				//	if ($this->DEBUG) if ($this->logger!=null) $this->logger->log("getMailContent() Processing substucture for part # ".$part_no.($no+1)." Attachments: ".\json_encode($output['attachments']));
				//	if ($this->DEBUG) if ($this->logger!=null) $this->logger->log("getMailContent() Structure: ".json_encode($structure,JSON_PRETTY_PRINT));
				$res = $this->getParts($mailbox, $msg_no, $substructure, $part_no.($no+1), $document,$header_id);
				if (!empty($res)){
					if (!empty($output['plain']))
						$output['plain'] .= $res['plain'];
						else
							$output['plain'] = $res['plain'];
								
							if (!empty($output['html']))
								$output['html'] .= $res['html'];
								else
									$output['html'] = $res['html'];
										
									$output["inlineImages"]=\array_merge($output["inlineImages"],$res["inlineImages"]);
				}
				else{
					if ($this->DEBUG) if ($this->logger!=null) $this->logger->log("getMailContent() No body found to process");
				}
			}
		}
	
		if (empty($output['html'])){
			$output['html'] = "";
		}
		if (empty($output['plain'])){
			$output['plain'] = "";
		}
	
		return $output;
	}
	/**2
	 * Extract each part and sub part(s) of a mail
	 *
	 * @param Resource $mailbox
	 * @param integer $msg_no
	 * @param object $structure
	 * @param integer|string $part_no
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @return array
	 */
	private function processAttachments($mailbox, $msg_no, $structure, $part_no,$fhAtt,$id,$skipAttachments=false)
	{
	
		try{
			if ($this->logger!=null) $this->logger->log("Processing attachments for msg: ".$id);
	
			$output = array('plain' => '', 'html' => '', 'attachments' => false, 'inlineImages' => array());
			//	if ($this->logger!=null) $this->logger->log("Fetching body for id: ".$id);
			$content = (!empty($part_no)) ? imap_fetchbody($mailbox, $msg_no, $part_no) : imap_body($mailbox, $msg_no);
			//	if ($this->logger!=null) $this->logger->log("Decoding id: ".$id);
			if (empty($content)) {
				return null;
			}
	
			//	if ($this->DEBUG) if ($this->logger!=null) $this->logger->log("getParts() - Encoding: ".$structure->encoding);
	
			if ($structure->encoding == 0){
				//7-bit
			}
			else if ($structure->encoding == 1){
				//8-bit
			}
			else if ($structure->encoding == 4) {
				//	if ($this->logger!=null) $this->logger->log("Converting encoding to 4 (quoted printable) to UTF-8");	
				//	if ($this->logger!=null) $this->logger->log("Printed decode");
				$content = imap_utf8(quoted_printable_decode($content));
			}
			elseif ($structure->encoding == 3) {
				$content = base64_decode($content);
			}
	
			//	if ($this->logger!=null) $this->logger->log("Decoded id: ".$id);
	
			if ( !empty($structure->dparameters) )
			{
	
				foreach ($structure->dparameters as $p)
				{
					//if ($this->logger!=null) $this->logger->log("Parameters a): ".\json_encode($p));
					if (strtoupper($p->attribute)=='NAME' || strtoupper($p->attribute)=='FILENAME') {
						$output['attachments'] = true;
	
						//Do not upload as an attachment if this an inline image
						if ($structure->type != 5) {
							if (!empty($fhAtt) && !empty($p->value) && \strpos($p->value, '=?')!==false)
								fwrite($fhAtt,'"'.$id.'"'.
										",".$this->getAttachmentName($p->value).",".$p->value.PHP_EOL);
	
						}
						else if (empty($structure->id)) {
							//this is not an inline image so add it as an attachment
							if (!empty($fhAtt) && !empty($p->value) && \strpos($p->value, '=?')!==false)
								fwrite($fhAtt,'"'.$id.'"'.
										",".$this->getAttachmentName($p->value).",".$p->value.PHP_EOL);
	
						}
					}
				}
			}
			elseif ( !empty($structure->parameters))
			{
				foreach ($structure->parameters as $p)
				{
					//if ($this->logger!=null) $this->logger->log("Structure Type: ".$structure->type. " Parameters b): ".\json_encode($p));
	
					if (!empty($p) && strtoupper($p->attribute)=='NAME' || strtoupper($p->attribute)=='FILENAME') {
						$output['attachments'] = true;
	
						//Do not upload as an attachment if this an inline image
						if ($structure->type != 5) {
							if (!empty($fhAtt) && !empty($p->value) && \strpos($p->value, '=?')!==false)
								fwrite($fhAtt,'"'.$id.'"'.
										",".$this->getAttachmentName($p->value).",".$p->value.PHP_EOL);
						}
						else  {
							//this is not an inline image so add it as an attachment
							if (!empty($fhAtt) && !empty($p->value) && \strpos($p->value, '=?')!==false)
								fwrite($fhAtt,'"'.$id.'"'.
										",".$this->getAttachmentName($p->value).",".$p->value.PHP_EOL);
						}
	
					}
					else if (!empty($content) && !empty($p) && !empty($p->attribute) && strtoupper($p->attribute)=='CHARSET'){
						//$isUTF8 = $this->isUTF8($content);
						$charset = $p->value;
						if (!empty($charset))
							if ($this->logger!=null) $this->logger->log("Detected charset ".\json_encode($charset)." for Body");
								
							$mbCharset = $this->getCharsetName($charset);
							//				$mbCharset = str_replace("Windows-1258", "Windows-1252",$mbCharset);
							if (!empty($charset) && $charset!="UTF-8" && $charset!="utf-8" && \in_array($mbCharset,mb_list_encodings()) ){
								$charset = strtoupper($charset);
								if ($this->logger!=null) $this->logger->log("Detected charset ".\json_encode($charset)." converting to UTF-8 for Body");
								$contentBefore = $content;
								try{
									$content = mb_convert_encoding($content, "UTF-8", $charset);
									if ($this->isValid($content)==false){
										if ($this->logger!=null) $this->logger->log("mb Conversion is not valid UTF-8, attempting iconv");
										$content = iconv($charset,"UTF-8//TRANSLIT",$contentBefore);
									}
									else{
										if ($this->logger!=null) $this->logger->log("Converted body content is valid");
									}
								}
								catch (\Exception $e){
									if ($this->logger!=null) $this->logger->log("getParts() - "+$e->getMessage()." Trace: ".$e->getTraceAsString());
								}
	
								if (empty($content) && !empty($contentBefore)){
									if ($this->logger!=null) $this->logger->log("Conversion failed, returning to before content");
									$content = $contentBefore;
								}
							}
							else if (!empty($charset) && !\in_array($mbCharset,mb_list_encodings()) ){
								if ($this->logger!=null) $this->logger->log( "Unknown or default charset: ".\json_encode($charset)." attempting iconv\n");
	
								$convText = iconv($charset,"UTF-8//TRANSLIT",$content);
								if (empty($convText)){
									if ($this->logger!=null) $this->logger->log( "iconv failed");
								}
								else{
									if ($this->logger!=null) $this->logger->log( "iconv succeeded");
									$content=$convText;
										
								}
	
								if ($this->isValid($content)==false){
									if ($this->logger!=null) $this->logger->log("WARNING: Converted body content is valid");
								}
								else{
									if ($this->logger!=null) $this->logger->log("Converted body content is valid");
								}
	
							}
						}
				}
			}
	
			//if ($this->logger!=null) $this->logger->log("Parameters processed");
	
			if (empty($structure) || empty($structure->id))
				if ($this->logger!=null) $this->logger->log("Structure Type: ".$structure->type);
				else
					if ($this->logger!=null) $this->logger->log("Structure Type: ".$structure->type. " Structure ID: ".\json_encode($structure->id) );
						
					if (empty($output['attachments']) && $structure->type == 0 && !empty($content))
					{
						if (strtolower($structure->subtype)=='plain')
							$output['plain'] .= trim($content);// ."\n\n";
							else
								$output['html'] .= $content;//."<br><br>";
					}
					elseif (empty($output['attachments']) && $structure->type == 2 && !empty($content))
					{
						$output['plain'] .= trim($content);//."\n\n";
					}
					//elseif (empty($output['attachments']) && ($structure->type == 5 && !empty($structure->id)) && !empty($content))
					elseif ( ($structure->type == 5 && !empty($structure->id)) && !empty($content))
					{
							
						$ext = "";
	
						//compute the inline image id
						$img_id = str_replace(array('<', '>'), '', $structure->id);
	
						if (\strtolower($structure->subtype)=="gif")
							$ext = ".gif";
							else if (\strtolower($structure->subtype)=="png")
								$ext = ".png";
								else if (\strtolower($structure->subtype)=="bmp")
									$ext = ".bmp";
									else if (\strtolower($structure->subtype)=="tiff" || \strtolower($structure->subtype=="tif"))
										$ext = ".tif";
										else if (\strtolower($structure->subtype)=="x-icon")
											$ext = ".ico";
											else if (\strtolower($structure->subtype)=="jpg" || \strtolower($structure->subtype=="jpeg") || \strtolower($structure->subtype=="jpe"))
												$ext = ".jpg";
												else
													$ext = "";
														
													//Remember the types for each inline image id.  Some inline objects will not have file extensions
													//These extensions will need to be properly computed in order to render the image in the browser
														
													if ($this->logger!=null) $this->logger->log("Subtype: ".\strtolower($structure->subtype)." Ext: ".$ext. " File Ext: ".pathinfo($structure->id,PATHINFO_EXTENSION));
														
													//check to see if the path reflects a file extension
													$fileExtension = pathinfo($img_id, PATHINFO_EXTENSION);
													$hasGT=!(\strpos($fileExtension,'>')===false);
													$hasExtension = !empty($fileExtension) && $fileExtension!="" && \strlen($fileExtension)<=4 && $hasGT==false;
														
													if (! $hasExtension){
														$output["inlineImages"][$img_id]=$img_id . $ext;
														$img_id .= $ext;
													}
													if (!empty($fhAtt) && !empty($img_id) && \strpos($img_id, '=?')!==false)
														fwrite($fhAtt,'"'.$id.'"'.
																",".$this->getAttachmentName($img_id).",".$img_id.",".$img_id . $ext.PHP_EOL);
															
					}
					//	if ($this->logger!=null) $this->logger->log("Structure processed");
					if (!empty($structure->parts))
					{
						$part_no = (empty($part_no)) ? '' : $part_no.'.';
						foreach ($structure->parts as $no => $substructure)
						{
							$res = $this->processAttachments($mailbox, $msg_no, $substructure, $part_no.($no+1), $fhAtt,$id);
							if (!empty($res)){
								if (!empty($output['plain']))
									$output['plain'] .= $res['plain'];
									else
										$output['plain'] = $res['plain'];
	
										if (!empty($output['html']))
											$output['html'] .= $res['html'];
											else
												$output['html'] = $res['html'];
	
												$output["inlineImages"]=\array_merge($output["inlineImages"],$res["inlineImages"]);
							}
							else{
								if ($this->DEBUG) if ($this->logger!=null) $this->logger->log("getMailContent() No body found to process");
							}
						}
					}
	
					if (empty($output['html'])){
						$output['html'] = "";
					}
					if (empty($output['plain'])){
						$output['plain'] = "";
					}
	
					//if ($this->logger!=null) $this->logger->log("Processed attachments for msg: ".$id);
		}
		catch (\Exception $e){
			if ($this->logger!=null) $this->logger->log("processAttachments() - ".$e->getTraceAsString() );
		}
		return $output;
	}
	
	/**
	 * Generate a random file name
	 *
	 * @return string
	 */
	private function generateFileName()
	{
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789_";
		$name = "";
		for($i=0; $i<12; $i++)
			$name.= $chars[rand(0,strlen($chars))];
			return $name;
	}
	
	private function uploadAttachedFile($file_name, $content, $document, $field = false)
	{
		$file_name=$this->getAttachmentName($file_name,$document);
		if (empty($file_name)){
			if ($this->logger!=null) $this->logger->log("uploadedAttachedFile() - No attachment name provided.  Orig: ".\json_encode($file_name));
			return;
		}
		//if ($this->DEBUG){
		//if ($this->logger!=null) $this->logger->log("uploadAttachedFile - Uploading File: ".$file_name." Encoding: ".$encoding);
		//}
		if ($field === false) {
			if (!@is_dir($this->file_path.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$document->getId()))
			{
				@mkdir($this->file_path.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$document->getId(), 0666);
			}
	
			@chmod($this->file_path.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$document->getId(), '0777');
			if ($this->DEBUG){
				if ($this->logger!=null) $this->logger->log("File Path: ".$this->file_path.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.$file_name);
			}
			file_put_contents($this->file_path.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.$file_name, $content);
			@chmod($this->file_path.DIRECTORY_SEPARATOR.'Embedded'.DIRECTORY_SEPARATOR.$document->getId(), '0666');
		}
		else
		{
			if (!@is_dir($this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR))
			{
				@mkdir($this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR, 0666);
			}
	
			@chmod($this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR, '0777');
			file_put_contents($this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($file_name), $content);
			@chmod($this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR, '0666');
	
			$finfo = new \finfo(FILEINFO_MIME_TYPE);
			$mime_type = $finfo->buffer($content);
			$mime_type = (empty($mime_type)) ? 'application/octet-stream' : $mime_type;
	
			$file_size = '';
			$matches=null;
			if (preg_match('/Content-Length: (\d+)/', $content, $matches)) {
				$file_size = (int)$matches[1];
			}
				
			if (empty($file_size))
				$file_size = filesize($this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($file_name));
					
				unset($finfo, $matches);
	
				$em = $this->getEntityManager();
				$temp = new AttachmentsDetails();
				$temp->setDocument($document);
				$temp->setField($field);
				$temp->setFileName(basename($file_name));
				$temp->setFileDate(new \DateTime());
				$temp->setFileMimeType($mime_type);
				if (!empty($file_size)) {
					$temp->setFileSize($file_size);
				}
				$temp->setAuthor($this->user);
	
				$em->persist($temp);
				$em->flush();
					
				unset($temp);
				$temp=null;
				unset($content);
				$content=null;
		}
		if ($this->DEBUG){
			if ($this->logger!=null) $this->logger->log("uploadAttachedFile - ".$file_name);
		}
	}
	private function isDisconnected() {
		$imapErrors = @imap_errors ();
		if (! empty ( $imapErrors )) {
	
			$hasConnection=\strpos(\json_encode ( $imapErrors, JSON_PRETTY_PRINT ),"IMAP connection lost")===false;
			$hasUnbrokenConnection=\strpos(\json_encode ( $imapErrors, JSON_PRETTY_PRINT ),"IMAP connection broken")===false;
			if (!$hasConnection || !$hasUnbrokenConnection){
				if (!$hasConnection)
					if ($this->logger!=null) $this->logger->log ("Connection is lost");
					else
						if ($this->logger!=null) $this->logger->log ("Connection is broken");
	
						return true;
			}
		}
		return false;
	}
	public function getUser() {
		return $this->user;
	}
	public function setUser($user) {
		$this->user = $user;
		return $this;
	}
	private function markDocumentTruncated($document){
		if (empty($document)) return;
		if ($this->logger!=null) $this->logger->log("Marking document as truncted");
		//identify the document as being truncated
		$subject = $document->getDocTitle();
	
		if (empty($subject))
			$subject="";
	
	
			$truncPos = \strpos($subject,"[TRUNCATED] ");
			//if ($this->logger!=null) $this->logger->log("Subject: ".$subject);
			//if ($this->logger!=null) $this->logger->log("Position: ".$trucPos);
			if ($truncPos===0){
				//already marked as truncated
				return;
			}
			else{
				if ($this->logger!=null) $this->logger->log("Setting subject to: "."[TRUNCATED] ".$subject);
				$document->setDocTitle("[TRUNCATED] ".$subject);
			}
	}
	private function getAttachmentName($attachmentName,$document){
		return $this->getDecodedValue($attachmentName, false,"Attachment",$document);
	}
	private  function big52utf8($big5str) {
	
		$blen = strlen($big5str);
		$utf8str = "";
	
		for($i=0; $i<$blen; $i++) {
			$sbit = ord(substr($big5str, $i, 1));
			//echo $sbit;
			//echo "<br>";
			if ($sbit < 129) {
				$utf8str.=substr($big5str,$i,1);
			} elseif ($sbit > 128 && $sbit < 255) {
				$new_word = iconv("BIG5", "UTF-8", substr($big5str,$i,2));
				$utf8str.=($new_word=="")?"?":$new_word;
				$i++;
			}
		}
	
		return $utf8str;
	
	}
	
	private function getDecodedValue($value,$utf8_exception,$name="Value",$document,$debug=true){
		$russianChar=false;
		if (!empty($value) ){
			//If the subject starts with =? presume it is encoded and try to decode
			$isEncoded=\strpos(\strtolower($value), "=?")!==false  ||
			(\strpos(\strtolower($value), "''")!==false && \strpos(\strtolower($value), "%")!==false);
			if ($debug) if ($this->logger!=null)  $this->logger->log("getValue() - Name: ".$name." Encoded: ".\json_encode($isEncoded)."\n");
	
			if ($isEncoded){
				//	if ($this->logger!=null)  $this->logger->log("Encoded Value Detected");
	
				$decodedValue = \imap_mime_header_decode($value);
				if (!empty($decodedValue) && $decodedValue!=null){
					//	if ($debug) if ($this->logger!=null)  $this->logger->log("getValue() - Decoding value");
					$valueText = "";
					$valueCount = count($decodedValue);
					for ($i=0; $i<$valueCount; $i++) {
						//	if ($debug) if ($this->logger!=null)  $this->logger->log( "Charset: {$decodedValue[$i]->charset}\n");
						//	if ($debug) if ($this->logger!=null)  $this->logger->log( "Text: {$decodedValue[$i]->text}\n\n");
						if (!empty($decodedValue[$i]) && !empty($decodedValue[$i]->text)){
							if ($debug) if ($this->logger!=null)  $this->logger->log( "Decoded value ".$i." of ".$valueCount."\n");
							if (\strtoupper($decodedValue[$i]->charset)=="KOI8-R"){
								if ($debug) if ($this->logger!=null)  $this->logger->log( "Russian characters detected\n");
								$russianChar=true;
							}
							//check to see if the text is still encoded
							$isStillEncoded=\strpos(\strtolower($decodedValue[$i]->text), "=?")!==false  ||
								
							(\strpos(\strtolower($decodedValue[$i]->text), "''")!==false && \strpos(\strtolower($decodedValue[$i]->text), "%")!==false);
							if ($isStillEncoded==false && $this->isValid($decodedValue[$i]->text)){
								if ($debug) if ($this->logger!=null)  $this->logger->log( "Content is VALID UTF-8 \n");
								$valueText.=$decodedValue[$i]->text;
							}
							else if ($isStillEncoded==false && \strtoupper($decodedValue[$i]->charset)=="DEFAULT" ){
								if ($debug) if ($this->logger!=null)  $this->logger->log( "Default charset\n");
								if ($isStillEncoded==false && $this->isUTFEncoded($decodedValue[$i]->text)){
									if ($debug) if ($this->logger!=null)  $this->logger->log( "Content is already UTF8 encoded \n");
									$valueText.=$decodedValue[$i]->text;
									//if ($debug) if ($this->logger!=null)  $this->logger->log( "Content:".$decodedValue[$i]->text."\n");
								}
								else{
									if ($debug) if ($this->logger!=null)  $this->logger->log( "Content is NOT already UTF8 encoded\n");
									$valueText.=$decodedValue[$i]->text;
									if ($debug) if ($this->logger!=null)  $this->logger->log( "Content:".$decodedValue[$i]->text."\n");
									//$valueText.=mb_convert_encoding($decodedValue[$i]->text,"UTF-8","auto");
								}
	
							}
							else if($isStillEncoded==false && \strtoupper($decodedValue[$i]->charset)=="UTF-8" ) {
								if ($this->isUTFEncoded($decodedValue[$i]->text)){
									if ($debug) if ($this->logger!=null)  $this->logger->log( "Content is UTF8 encoded \n");
									$valueText.=$decodedValue[$i]->text;
									//if ($debug) if ($this->logger!=null)  $this->logger->log( "Content:".$decodedValue[$i]->text."\n");
								}
								else{
									if ($debug) if ($this->logger!=null)  $this->logger->log( "Content is not UTF-8 encoded, encoding as UTF-8 \n");
	
									$valueText.=imap_utf8($decodedValue[$i]->text); //=mb_convert_encoding($decodedValue[$i]->text,"UTF-8","auto");
									//if ($debug) if ($this->logger!=null)  $this->logger->log( "Content:".$decodedValue[$i]->text."\n");
								}
							}
							else if (!empty($decodedValue[$i]->charset) & $russianChar==false ){
	
								$charset=$decodedValue[$i]->charset;
								if ($charset=="default" || $charset=="DEFAULT" ){
									$charset="ISO-8859-1";
								}
								$mbCharset = $this->getCharsetName($charset);
								if ($debug) if ($this->logger!=null)  $this->logger->log( "Detected charset ".\json_encode($mbCharset)."\n");
	
								if ($charset!="UTF-8" && $charset!="utf-8" && \in_array($mbCharset,mb_list_encodings())){
									//	&& $this->isEncodedAs($decodedValue[$i]->text,$mbCharset)){
									$charset = strtoupper($charset);
									if ($debug) if ($this->logger!=null)  $this->logger->log( "Detected charset ".\json_encode($charset)." converting to UTF-8\n");
									$valueConverted=mb_convert_encoding($decodedValue[$i]->text,"UTF-8",$mbCharset);
									if (!empty($valueConverted)){
										//if ($debug) if ($this->logger!=null)  $this->logger->log( "Converted:".$valueConverted."\n");
										$valueText.=$valueConverted;
									}
									else{
										$this->markDocumentTruncated($document);
										if ($debug) if ($this->logger!=null)  $this->logger->log( "Unable to convert:".$decodedValue[$i]->text."\n");
										$valueText.=$decodedValue[$i]->text;
									}
	
								}
								else if ($mbCharset!="UNKONWN") {
									if ($debug) if ($this->logger!=null)  $this->logger->log( "Unknown or default charset: ".\json_encode($charset)." attempting iconv\n");
	
									//convert the text
									try {
										if (!empty($decodedValue[$i]->text))
											$convText = iconv($mbCharset,"UTF-8//TRANSLIT",$decodedValue[$i]->text);
									} catch (\Exception $e) {
										if ($debug) if ($this->logger!=null)  $this->logger->log( "getDecodedValue() - iconv error - ".$e->getMessage());
									}
	
									if (empty($convText) && !empty($decodedValue[$i]->text) && $mbCharset=="BIG5") {
										if ($this->logger!=null)  $this->logger->log( "getDecodedValue() Conversion from BIG5 not successful, try alternate");
										$convText = $this->big52utf8($decodedValue[$i]->text);
									}
									if (empty($convText) && !empty($decodedValue[$i]->text) && $mbCharset=="GB2312") {
										if ($this->logger!=null)  $this->logger->log( "getDecodedValue() Conversion from GB2312 not successful, try GBK");
										$convText = iconv("GBK","UTF-8//TRANSLIT",$decodedValue[$i]->text);
									}
	
									if (empty($convText) && !empty($decodedValue[$i]->text) && ($mbCharset=="US-ASCII" || $mbCharset=="ASCII" )) {
										if ($this->logger!=null)  $this->logger->log( "getDecodedValue() Conversion from ".$mbCharset." not successful, try ISO");
										$convText = iconv("ISO-8859-1","UTF-8//TRANSLIT",$decodedValue[$i]->text);
									}
	
									//we should have converted text
									//if (empty($convText) || $convText!=$origText){
									if (empty($convText)){
										if ($debug) if ($this->logger!=null)  $this->logger->log( "iconv failed - conversion error");
										$this->markDocumentTruncated($document);
										$valueText.=$decodedValue[$i]->text;
										//if ($debug) if ($this->logger!=null)  $this->logger->log( "Content:".$decodedValue[$i]->text."\n");
									}
									else{
										//			if ($debug) if ($this->logger!=null)  $this->logger->log( "iconv succeeded");
										$valueText.=$convText;
										//		if ($debug) if ($this->logger!=null)  $this->logger->log( "ICONV Content:".$convText."\n");
									}
	
	
								}
							}
	
						}
						else{
							//if ($debug) if ($this->logger!=null)  $this->logger->log( "Decoded value ".$i." of ".$valueCount." - Content is empty \n");
						}
					}
					if ($valueText!=""){
						if ($debug) if ($this->logger!=null)  $this->logger->log( "Decoded value ".$i." of ".$valueCount." - Content: ".$valueText."\n");
						$value = $valueText;
					}
					else{
						//	if ($debug) if ($this->logger!=null)  $this->logger->log( "Decoded value for ".$name." is empty \n");
						$isValidUtf8=$this->isValid(imap_utf8($value));
						$utf8Value = imap_utf8($value);
						if (!empty($utf8Value) && $isValidUtf8){
							if ($debug) if ($this->logger!=null)  $this->logger->log( "Using IMAP UTF8 for ".$name."\n");
							$value = imap_utf8($value);
							//if ($debug) if ($this->logger!=null)  $this->logger->log( "Content:".$value."\n");
						}
					}
				}
			}
			else{
				//	if ($debug) if ($this->logger!=null)  $this->logger->log("Decoded Value is empty");
				//if ($debug) if ($this->logger!=null)  $this->logger->log("IMAP UTF-8 Value: ".imap_utf8($value));
				$value=imap_utf8($value);
			}
			
			if (!empty($value) && strpos($value,"’")!==false){
				$value = str_replace("’", "'", $value);
			}
	
			if (!empty($value) && strpos($value,"“")!==false){
				$value = str_replace("“", "\"", $value);
			}
			if (!empty($value) && strpos($value,"”")!==false){
				$value = str_replace("”", "\"", $value);
			}
			if (!empty($value) && strpos($value,"ð")!==false){
				if ($debug) if ($this->logger!=null) $this->logger->log("Fixing s with caron");
				$value = str_replace("ð", html_entity_decode("&scaron;"), $value);
			}
			
			if (!empty($value) && strpos($value,"China")!==false){
  	         	if (!empty($value) && strpos($value,"China Offshore Aug Highlights - Chinese 'Ultra-rich' Hits 89,000")!==false){
  	         		if ($debug) if ($this->logger!=null) $this->logger->log('Custom subject');
  	         		$value = "China Offshore Aug Highlights - Chinese 'Ultra-rich' Hits 89,000, Chinese Wealth Manager Sets Up Jersey Trust Firm 中国离岸8月要闻精选——中国超高净值人士已达89000人，诺亚财富首获泽西岛信托牌照，满足中国投资者";
  	         	}
  	        	         
	  	         if (!empty($value) && strpos($value,"China Offshore October Highlights - BVI Premier Speaks at China Offshore Summit")!==false){
  		         	if ($debug) if ($this->logger!=null) $this->logger->log('Custom subject');
  	    	     	$value = "China Offshore October Highlights - BVI Premier Speaks at China Offshore Summit in Shanghai, Jersey Report Looks At Priorities for China's Wealthy 中国离岸10月要闻精选——英属维尔京群岛总理在上海参加中国离岸金融峰会，泽";
  	        	 }
  	         
  	         	if (!empty($value) && strpos($value,"� ")!==false){
  	         		if ($debug) if ($this->logger!=null) $this->logger->log('Fixing bad chinese "� " ');
  	         		$value = str_replace("� ","",$value);
  	         	}
  	         	if (!empty($value) && strpos($value,"� ")!==false){
  	         		if ($debug) if ($this->logger!=null) $this->logger->log('Fixing bad chinese "� " ');
  	         		$value = str_replace("� ","",$value);
  	         	}  	         	
				if (!empty($value) && strpos($value,"的")!==false){
					if ($debug) if ($this->logger!=null) $this->logger->log('Fixing bad chinese "的" ');
					$value = str_replace("的","",$value);
				}
				if (!empty($value) && strpos($value,json_decode("\xe7\x9a"))!==false){
					if ($debug) if ($this->logger!=null)  $this->logger->log('Fixing bad chinese "\xe7\x9a" ');
					$value = str_replace(json_decode("\xe7\x9a"),"",$value);
				}
					
				if (!empty($value) && strpos($value,json_decode("\u00e6\u00b5"))!==false){
					if ($debug) if ($this->logger!=null)  $this->logger->log('Fixing bad chinese "\u00e6\u00b5" ');
					$value = str_replace(json_decode("\u00e6\u00b5"),"",$value);
				}
					
				if (!empty($value) && strpos($value,json_decode("\u00e8"))!==false){
					if ($debug) if ($this->logger!=null)  $this->logger->log('Fixing bad chinese "\u00e8" ');
					$value = str_replace(json_decode("\u00e8"),"",$value);
				}
			}
	
				
				
			//$isRussian = $this->isRussian($value) || $russianChar;
	
			$valueBefore=$value;
			//if ($debug) if ($this->logger!=null)  $this->logger->log( "Orig Value: ".$value."\n");
			if ($this->isUTF8($value)==false ) { //&& $isRussian==false){
				if ($this->isValid(imap_utf8($value))) {
					//if ($debug) if ($this->logger!=null)  $this->logger->log( "Non-UTF8 Value: ".$value."\n");
					$value = imap_utf8($value);
					if ($debug) if ($this->logger!=null)  $this->logger->log( "IMAP UTF8 Value: ".$value."\n");
				}
				else{				
					if ($this->isValid($value)==false){
						if ($debug) if ($this->logger!=null)  $this->logger->log( "Value HTML Decode 1 - Before: ".\json_encode(utf8_encode($value))."\n");
						$value = utf8_encode(html_entity_decode(mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8'),ENT_HTML401,'UTF-8'));
	
						$value = str_replace("&#2013266357;","",$value);
	
						$matchCount = \preg_match_all("/&#2013[0-9][0-9][0-9][0-9][0-9][0-9];/", $value,$matches);
						if ($matchCount>0){
								
							if (!empty($value) && strpos($value,"China")!==false){
								if ($debug) if ($this->logger!=null)  $this->logger->log( "Bad chinese character");
								$value = str_replace("&#2013266357;", "", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266088;&#2013266096;")!==false){
								$value = str_replace("&#2013266088;&#2013266096;", "ò", $value);
							}
								
							if (!empty($value) && strpos($value,"&#2013266088;&#2013266088;")!==false){
								$value = str_replace("&#2013266088;&#2013266088;", "è", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266088;&#2013266086;")!==false){
								$value= str_replace("&#2013266088;&#2013266086;", "é", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266089;&#2013266086;")!==false){
								$value= str_replace("&#2013266089;&#2013266086;", "|", $value);
							}
							if (!empty($value) && strpos($value,"&#2013265936;&#2013265932;")!==false){
								$value = str_replace("&#2013265936;&#2013265932;", "徐", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266088;&#2013266084;")!==false){
								$value = str_replace("&#2013266088;&#2013266084;", "à", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266081;&#2013266095;")!==false){
								$value = str_replace("&#2013266081;&#2013266095;", "'", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266103;")!==false){
								$value = str_replace("&#2013266103;", "", $value);
							}
							if (!empty($value) && strpos($value,"&#2013265928;")!==false){
								$value = str_replace("&#2013265928;", "è", $value);
							}
							if (!empty($value) && strpos($value,"&#2013265930;")!==false){
								$value = str_replace("&#2013265930;", "ô", $value);
							}
							if (!empty($value) && strpos($value,"&#2013265931;")!==false){
								$value = str_replace("&#2013265931;", "é", $value);
							}
							if (!empty($value) && strpos($value,"&#2013265929;")!==false){
								$value = str_replace("&#2013265929;", "é", $value);
							}						
							if (!empty($value) && strpos($value,"&#2013266093;")!==false){
								$value = str_replace("&#2013266093;", "", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266066;")!==false){
								$value = str_replace("&#2013266066;", "´", $value);
							}						
							if (!empty($value) && strpos($value,"&#2013266538;")!==false){
								$value = str_replace("&#2013266538;", "楠", $value);
							}						
							if (!empty($value) && strpos($value,"&#2013266065;")!==false){
								$value = str_replace("&#2013266065;", "'", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266076;")!==false){
								$value = str_replace("&#2013266076;", "e", $value);
							}
							if (!empty($value) && strpos($value,"&#2013265920;")!==false){
								$value = str_replace("&#2013265920;", "à", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266070;")!==false){
								$value = str_replace("&#2013266070;", "-", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266173;")!==false){
								$value = str_replace("&#2013266173;", "", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266112;")!==false){
								$value = str_replace("&#2013266112;", "À", $value);
							}
							if (!empty($value) && strpos($value,"&#2013265935;")!==false){
								$value = str_replace("&#2013265935;", "ї", $value);
							}
							if (!empty($value) && strpos($value,"&#2013265927;")!==false){
								$value = str_replace("&#2013265927;", "ç", $value);
							}
							if (!empty($value) && strpos($value,"&#2013265924;")!==false){
								$value = str_replace("&#2013265924;", "ô", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266060;")!==false){
								$value = str_replace("&#2013266060;", "ɶ", $value);
							}
							if (!empty($value) && strpos($value,"&#2013266096;")!==false){
								$value = str_replace("&#2013266096;", "°", $value);
							}
							$matchCountAfter = \preg_match_all("/&#2013[0-9][0-9][0-9][0-9][0-9][0-9];/", $value,$matches);
							if ($matchCountAfter>0){
								if ($this->logger!=null)  $this->logger->log("Invalid HTML Entities: ".\json_encode($matches,JSON_PRETTY_PRINT));
								$this->markDocumentTruncated($document);
							}
							if ($debug) if ($this->logger!=null)  $this->logger->log( "Value HTML Decode 1 - After: ".\json_encode(utf8_encode($value))."\n");
						}
					}
	
					if ($debug) if ($this->logger!=null)  $this->logger->log( "UTF8 Value: ".$value."\n");
				}
	
			}
			else{
				//if ($debug) if ($this->logger!=null)  $this->logger->log( "No conversion necessary for Value: ".$value."\n");
			}
			if (empty($value) || $value=="" ){
				if ($debug) if ($this->logger!=null)  $this->logger->log( "No value available, returning to default!\n");
				$value = utf8_encode(utf8_decode($valueBefore));
				if ($debug) if ($this->logger!=null)  $this->logger->log( "Default Value: ".$value."\n");
			}
	
			return $value;
		}
		else {
			if (empty($value)){
				if ($debug) if ($this->logger!=null)  $this->logger->log( "Value is missing.\n");
				return imap_utf8('No Value');
			}
		}
	}
	private function getCharsetName($charset){
		$mbCharset = str_replace("WINDOWS", "Windows",strtoupper($charset));
		$mbCharset = str_replace("7BIT", "7bit",$mbCharset);
		$mbCharset = str_replace("8BIT", "8bit",$mbCharset);
		$mbCharset = str_replace("EUCJP-WIN", "eucJP-win",$mbCharset);
		$mbCharset = str_replace("SJIS-WIN", "SJIS-win",$mbCharset);
		$mbCharset = str_replace("JIS-MS", "JIS-ms",$mbCharset);
		$mbCharset = str_replace("RAW", "raw",$mbCharset);
		$mbCharset = str_replace("BYTE2BE", "byte2be",$mbCharset);
		$mbCharset = str_replace("BYTE2LE", "byte2le",$mbCharset);
		$mbCharset = str_replace("BYTE4BE", "byte4be",$mbCharset);
		$mbCharset = str_replace("BYTE4LE", "byte4le",$mbCharset);
		$mbCharset = str_replace("SJIS-MAC", "SJIS-mac",$mbCharset);
		$mbCharset = str_replace("MOBILE", "Mobile",$mbCharset);
		$mbCharset = str_replace("ARMSCII-8", "ArmSCII-8",$mbCharset);
		if ($mbCharset=="ISO-8859-8-I"){
			$mbCharset="ISO-8859-8";
		}
		return $mbCharset;
	}

	private function isUTFEncoded($string){
		if (\strlen($string)==\strlen(utf8_decode($string))){
			//echo "UTF-8 Encoded Content Detected:\t".$string."\n";
			return true;
		}
		else{
			//echo "UTF-8 Encoded Content NOT Detected:\t".$string."\n";
			return false;
		}
	}
	private function isValid($string)
	{
		return preg_match('//u', $string) === 1;
	}
	
	private function getDecodedBody($value,$utf8_exception,$document){
		if (empty($value)){
			if ($this->logger!=null)  $this->logger->log("WARNING: getDecodedBody() - No body provided");
			return "";
		}
		
		if (!empty($value) && strpos($value,"’")!==false){
			$value = str_replace("’", "'", $value);
		}
		
		if (!empty($value) && strpos($value,"“")!==false){
			$value = str_replace("“", "\"", $value);
		}
		if (!empty($value) && strpos($value,"”")!==false){
			$value = str_replace("”", "\"", $value);
		}
		if (!empty($value) && strpos($value,"š")!==false){
			$value = str_replace("š", "š", $value);
		}
		$value=str_replace("  "," ",$value);
		//$value = html_entity_decode(htmlentities($value));
		//	 //\utf8_encode($value); //htmlentities($value);//
	
		//$value = mb_convert_encoding($value, 'HTML-ENTITIES', 'utf-8'); //\utf8_encode($value);
		$valueBefore=$value;
		if (!empty($value) && $utf8_exception==false && $this->isValid($value)==false){
			if ($this->logger!=null)  $this->logger->log("Body Value is not UTF-8");
				
				
				
			if ($this->isValid(imap_utf8($value))) {
				//$questionMarkCountBefore = \substr_count($value,"?");
				if ($this->logger!=null)  $this->logger->log("Body Value converts to UTF-8 using imap_utf8");
				$newValue = imap_utf8($value);
				//$questionMarkCountAfter = \substr_count($value,"?");
	
				//$this->checkConv($document->getId(), "", $questionMarkCountBefore, $questionMarkCountAfter);
			}
			else{
				//try converting from iso
				if ($this->logger!=null)  $this->logger->log("Body Value does not convert to utf8, try ISO-8859-1");
				$convText = iconv('ISO-8859-1','UTF-8//TRANSLIT',$value);
				if (!empty($convText)){
					$newValue=$convText;
				}else{
					if ($this->logger!=null)  $this->logger->log("Body Value does not convert to utf8, try encoding directly");
						
					$htmlValue = htmlentities($value);
					if (!empty($htmlValue)){
						if ($this->logger!=null)  $this->logger->log("Converting to html entities using htmlentities()");
						$newValue = $htmlValue;
					}
					else{
						if ($this->logger!=null)  $this->logger->log("Converting to html entities using mb_convert_encoding()");
						$newValue = mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8');
					}
						
					$matchCount = \preg_match_all("/&#2013[0-9][0-9][0-9][0-9][0-9][0-9];/", $newValue,$matches);
					if ($matchCount>0){
	
						if (!empty($newValue) && strpos($newValue,"&#2013266088;&#2013266096;")!==false){
							$newValue = str_replace("&#2013266088;&#2013266096;", "ò", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266088;&#2013266088;")!==false){
							$newValue = str_replace("&#2013266088;&#2013266088;", "è", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266088;&#2013266086;")!==false){
							$newValue= str_replace("&#2013266088;&#2013266086;", "é", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266089;&#2013266086;")!==false){
							$newValue= str_replace("&#2013266089;&#2013266086;", "|", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265936;&#2013265932;")!==false){
							$newValue = str_replace("&#2013265936;&#2013265932;", "徐", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266088;&#2013266084;")!==false){
							$newValue = str_replace("&#2013266088;&#2013266084;", "à", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266081;&#2013266095;")!==false){
							$newValue = str_replace("&#2013266081;&#2013266095;", "'", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265928;")!==false){
							$newValue = str_replace("&#2013265928;", "è", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266103;")!==false){
							$newValue = str_replace("&#2013266103;", "", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265930;")!==false){
							$newValue = str_replace("&#2013265930;", "ô", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265929;")!==false){
							$newValue = str_replace("&#2013265929;", "é", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265931;")!==false){
							$newValue = str_replace("&#2013265931;", "é", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266093;")!==false){
							$newValue = str_replace("&#2013266093;", "", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266066;")!==false){
							$newValue = str_replace("&#2013266066;", "´", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266538;")!==false){
							$newValue = str_replace("&#2013266538;", "楠", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266065;")!==false){
							$newValue = str_replace("&#2013266065;", "'", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266076;")!==false){
							$newValue = str_replace("&#2013266076;", "e", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265920;")!==false){
							$newValue = str_replace("&#2013265920;", "à", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266070;")!==false){
							$newValue = str_replace("&#2013266070;", "-", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266173;")!==false){
							$newValue = str_replace("&#2013266173;", "", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266112;")!==false){
							$newValue = str_replace("&#2013266112;", "À", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265935;")!==false){
							$newValue = str_replace("&#2013265935;", "ї", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265927;")!==false){
							$newValue = str_replace("&#2013265927;", "ç", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265924;")!==false){
							$newValue = str_replace("&#2013265924;", "ô", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266060;")!==false){
							$newValue = str_replace("&#2013266060;", "ɶ", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266096;")!==false){
							$newValue = str_replace("&#2013266096;", "°", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266067;")!==false){
							$newValue = str_replace("&#2013266067;", "\"", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266068;")!==false){
							$newValue = str_replace("&#2013266068;", "\"", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265933;")!==false){
							$newValue = str_replace("&#2013265933;", "í", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265933;")!==false){
							$newValue = str_replace("&#2013265933;", "í", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266091;")!==false){
							$newValue = str_replace("&#2013266091;", "<<", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266107;")!==false){
							$newValue = str_replace("&#2013266107;", ">>", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265934;")!==false){
							$newValue = str_replace("&#2013265934;", "î", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013265925;")!==false){
							$newValue = str_replace("&#2013265925;", "Å", $newValue);
						}
							
							
						if (!empty($newValue) && strpos($newValue,"&#2013266087;")!==false){
							$newValue = str_replace("&#2013266087;", "", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266937;")!==false){
							$newValue = str_replace("&#2013266937;", "", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266053;")!==false){
							$newValue = str_replace("&#2013266053;", "…", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266048;")!==false){
							$newValue = str_replace("&#2013266048;", "€", $newValue);
						}
						if (!empty($newValue) && strpos($newValue,"&#2013266080;")!==false){
							$newValue = str_replace("&#2013266080;", "", $newValue);
						}
	
						$matchCountAfter = \preg_match_all("/&#2013[0-9][0-9][0-9][0-9][0-9][0-9];/", $newValue,$matches);
						if ($matchCountAfter>0){
							if ($this->logger!=null)  $this->logger->log("Invalid HTML Entities: ".\json_encode($matches,JSON_PRETTY_PRINT));
							$this->markDocumentTruncated($document);
						}
						if ((empty($newValue) || $newValue==null) && !empty($value)){
							if ($this->logger!=null)  $this->logger->log("WARNING: HTML entity Conversion failed, resetting");
							$newValue = $value;
						}
	
					}
				}
	
	
	
			}
		}
		else if (!empty($value)) {
			if ($this->logger!=null)  $this->logger->log("Using body value as-is");
			$newValue = $value;
		}
		else{
			if ($this->logger!=null)  $this->logger->log("Resetting body to original value");
			$newValue = $valueBefore;
		}
	
		if (empty($value) && !empty($valueBefore)){
			if ($this->logger!=null)  $this->logger->log("Resetting body to original value.");
			$newValue = $valueBefore;
		}
		if ($utf8_exception==false && $this->isValid($newValue)==false){
			if ($this->logger!=null)  $this->logger->log("ERROR: Invalid body forcing conversion");
			$newValue = utf8_encode(utf8_decode($newValue));
		}
		return $newValue;
	}
	
	public function getSubject($email,$document=null){
		if (!empty($email) && !empty($email->Subject)){
			$subject = $email->Subject;
			$value = $this->getDecodedValue($subject,$this->isRussian($subject),"Subject",$document);
			if (\strlen($value)>254)
				$value = \mb_strimwidth($value,0,254);
				return $value;
		}
		else{
			return imap_utf8("No Subject");
		}
	}
	public function isRussian($value){
		if (empty($value))
			return false;
	
			if (!empty($value['html']) ||  !empty($value['plain'])){
				$hasHTML = !empty($value['html']) && $value['html']!='' ? true : false;
				$value =  $hasHTML ? $value['html'] : $value['plain'];
			}
	
			if (\is_array($value)){
				$value='';
			}
	
			if ( preg_match('/[А-Яа-яЁё]/u', $value)) {
				if ($this->logger!=null)  $this->logger->log("Russian found!");
				return true;
			}
			else{
				//if ($this->logger!=null)  $this->logger->log("No russian found!");
				return false;
					
			}	
	}
	private function isUTF8($string) {
		return (utf8_encode(utf8_decode($string)) == $string);
	}
	private function importSubformData($entity,$email,$content){
		if (empty($entity) || empty($email))
			return;
	
			//	if ($this->DEBUG){
			//		if ($this->logger!=null)  $this->logger->log("Processing message fields");
			//	}
	
			$em = $this->getEntityManager();
			$doc_type = $entity->getDocType();
			$subforms = $doc_type->getDocTypeSubform();
			if ($subforms->count() > 0)
			{
				foreach ($subforms as $sub)
				{
					$fields = $sub->getSubform()->getSubformFields();

					foreach ($fields as $field)
					{
							
						$temp = new FormTextValues();
						$temp->setDocument($entity);
						$temp->setField($field);
						$field_name = $field->getFieldName();
						switch ($field_name)
						{
							case 'mail_date':
								$temp = new FormDateTimeValues();
								$temp->setDocument($entity);
								$temp->setField($field);
								$field_name = $field->getFieldName();
								//$temp->setFieldValue(date('d/m/Y H:i:s', $email->udate));
								$msg_date = new \DateTime();
								$msg_date->setTimestamp($email->udate);
								$temp->setFieldValue($msg_date);
								break;
							case 'mail_from':
								if (!empty($email->fromaddress)) {
									$temp->setFieldValue($this->getDecodedValue($email->fromaddress,$this->isRussian($email->fromaddress),"From",$entity ));
								}
								else {
									unset($temp);
									continue;
								}
								break;
							case 'mail_to':
								if (!empty($email->toaddress)) {
									$temp->setFieldValue($this->getDecodedValue($email->toaddress,$this->isRussian($email->toaddress),"To",$entity ));
								}
								else {
									unset($temp);
									continue;
								}
								break;
							case 'mail_cc':
								if (!empty($email->ccaddress)) {
									$temp->setFieldValue($this->getDecodedValue($email->ccaddress,$this->isRussian($email->ccaddress),"Copy To",$entity ));
								}
								else {
									unset($temp);
									continue;
								}
								break;
							case 'mail_bcc':
								if (!empty($email->bccaddress)) {
									$temp->setFieldValue($this->getDecodedValue($email->bccaddress,$this->isRussian($email->bccaddress),"Blind Copy",$entity));
								}
								else {
									unset($temp);
									continue;
								}
								break;
							case 'mail_subject':
								$subject = $this->getSubject($email,$entity);
								$temp->setFieldValue($subject);
								break;
							case 'TERBody':
							case 'terbody':
							case 'body' :
							case 'Body':
							case 'editor_body':								
								if (empty($content)){
									$content['html']='';
								}
								$hasHTML = !empty($content['html']) && $content['html']!='' ? true : false;
								//$value = $hasHTML ? $content['html'] : nl2br($content['plain']);
								$value = $hasHTML ? $content['html'] : $content['plain'];
										
								//	if ($this->logger!=null)  $this->logger->log("Processing body Is UTF-8: ".$this->isUTF8($value));
								//	if ($this->logger!=null)  $this->logger->log($value);
								$origValue = $value;
								$value = $this->getDecodedBody($value,$this->isRussian($value),$entity );
								//	if ($this->logger!=null)  $this->logger->log("Processing decoded body Is UTF-8: ".$this->isUTF8($value));
								//	if ($this->logger!=null)  $this->logger->log($value);
								if (!empty($origValue) && empty($value) ){
									if ($this->logger!=null)  $this->logger->log("Original Body: ".\json_encode($origValue));
									$value="Invalid Body";
								}else if (empty($value) || $value==null){
									$value="-";
								}
								$temp->setFieldValue($value);
								break;
							case 'MCSF_DLIUploader1':
							case 'mcsf_dliuploader1':
								$temp->setFieldValue("");
								break;
							default:
								if ($this->logger!=null)  $this->logger->log("No field mapping found for source target field: ".$field_name);
								//if ($this->logger!=null)  $this->logger->log("Field ".$field_name. "=".$value);
								$temp->setFieldValue("-");
								break;
						}
	
						try {
							$em->getConnection()->beginTransaction();
							if (!empty($temp)) {
								$em->persist($temp);
								$em->flush();
							}
							$em->getConnection()->commit();
						}
						catch (\Exception $e){
							$val = $temp->getFieldValue();
							if (empty($val))
								$val="";
								if ($this->logger!=null)  $this->logger->log("General Error posting field value for field ".$field_name." Value: ".$val." Error: ".$e->getMessage());
								if (!empty($content) && !is_array($content))
									if ($this->logger!=null)  $this->logger->log("Content Encoding: ".\json_encode(\mb_detect_encoding($content)));
									 
									$isOpen = $em->isOpen();
									if ($isOpen)
										$em->getConnection()->rollBack();
					    	else{
					    		$this->resetManager();
					    		$em->getConnection()->rollBack();
					    	}
					    	 
					    	if ($field_name=="TERBody" || $field_name=="terbody" ){
					    		if ($this->logger!=null)  $this->logger->log("General Error posting field value for field ".$field_name.". Error: ".$e->getMessage());
					    	}
					    	else
					    		throw new \Exception("General Error posting field value for field ".$field_name." Error: ".$e->getMessage());
					    		 
						}
							
						unset($temp);
					}
				}
			}
			//if ($this->logger!=null)  $this->logger->log("Processed message fields");
	}
	private static function getConnection($name){
		//Initialize the PDO connection from the YML configuration file
		return self::getDbConnection(self::getConfiguration($name));
	}
	
	//load and parse the specified yml configuration field
	private static function getConfiguration($connectionName){
		//Read the yml connection file
		$configPath =  \str_replace('\\', '/', realpath(__DIR__.'/../../../../app/config'));
		$container = new ContainerBuilder();
		$ymlLoader = new YamlFileLoader($container,new FileLocator($configPath));
		$ymlLoader->load($connectionName.".yml");
		return $container;
	}
	
	private static function getDbConnection($container){
		//Read Connection Parameters
		$host = $container->getParameter("database_host");
		$port = $container->getParameter("database_port");
		$name = $container->getParameter("database_name");
		$user=  $container->getParameter("database_user");
		$pwd =  $container->getParameter("database_password");
		$driver = $container->getParameter("database_driver");
		$pdo = \str_replace('pdo_', '', $driver);
	
		if ( empty($name) || empty($driver) )
			throw new \Exception("Invalid External Database connection information provided!");
	
			//Connect
			try {
				$dsn = null;
				switch ($pdo){
					case "mysql":
						$dsn = $pdo.':host='.$host.(empty($port) ? '' : ';port='.$port).';dbname='.$name;
						break;
					case "sqlsrv":
						$dsn = $pdo.':Server='.$host.(empty($port) ? '' : ','.$port).';Database='.$name;
						break;
					case "pgsql":
						$dsn = $pdo.':host='.$host.(empty($port) ? '' : ';port='.$port).';dbname='.$name;
						break;
					case "odbc":
						$dsn = $pdo.':'.$name;
						break;
					case "sqlite":
						$dsn = $pdo.':'.$name;
						break;
					default:
						$dsn = $pdo.':host='.$host.';Server='.$host.(empty($port) ? '' : ','.$port).';Database='.$name.';dbname='.$name;
						break;
				}
				return new \PDO($dsn,$user,$pwd);
			} catch (\PDOException $e) {
				echo "PDO Exception: ".$e->getMessage();
				throw $e;
			}
	}
	private function resetManager(){
		$manager = $this->getEntityManager();
		if ($manager->isOpen())
			$manager->close();
	
			if (!$manager->isOpen()) {
				$manager = $manager->create(
						$manager->getConnection(),
						$manager->getConfiguration()
						);
					
				if  (!$manager->isOpen()) {
					throw new \Exception("Unable to reopen manager");
				}
				else{
					if ($this->logger!=null) $this->logger->log("Reopened manager");
				}
			}
	
			$this->setEntityManager($manager);
	}
	
	public function setMemoType($doc_type){
		$this->doc_type = $doc_type;
	}
	public static function getMemoType($em){		
		$doc_type = 'Mail Memo';
		$doc_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => $doc_type, 'Status' => true, 'Trash' => false));
		if (empty($doc_type))
		{
			throw new \Exception('Unspecified Mail Memo document type source.');
		}
		return $doc_type;
	}
	public function getMsgHeader() {
		return $this->msgHeader;
	}
	public function getMsgNo() {
		return $this->msg_no;
	}
	public function getMsgId() {
		return $this->msgId;
	}
	public function getMailbox() {
		return $this->mailbox;
	}
	
	
	private function getCustomACL(){
		if (empty($this->customAcl)){
			$this->customAcl=new CustomACL($this->container);
		}
		return $this->customAcl;
	}
	private function getMiscellaneous(){
		if (empty($this->miscellaneous)){
			$this->miscellaneous=new Miscellaneous($this->container);
		}
		return $this->miscellaneous;
	}
	public function setCustomAcl($customAcl){
		$this->customAcl = $customAcl;
	}
	public function setMiscellaneous($misc){
		$this->miscellaneos=$misc;
	}
	
}