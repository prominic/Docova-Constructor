<?php

namespace Docova\DocovaBundle\Controller;

//use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\Documents;
use Docova\DocovaBundle\Entity\AttachmentsDetails;
use Docova\DocovaBundle\Entity\FormTextValues;
use Docova\DocovaBundle\Entity\RelatedEmails;
use Docova\DocovaBundle\Extensions\MiscFunctions;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\IMAP\IMAPMsgImporter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Docova\DocovaBundle\ObjectModel\Docova;

class MailController extends Controller
{
	protected $user;
	protected $file_path;
	protected $global_settings; 
	
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();

		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];

		$this->file_path = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root').DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage' :  $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
	}
	
	public function openMailAcquireMessagesAction()
	{
		$folders = array();
		$this->initialize();
		$session_obj = $this->get('session');
		if ($session_obj->get('epass')) 
		{
			$folders = $this->getMailFolders();
		}

		return $this->render('DocovaBundle:Default:dlgMailAcquireMessages.html.twig', array(
				'user' => $this->user,
				'folders' => $folders
			));
	}
	
	public function openMailAcquireAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Default:dlgMailAcquire.html.twig', array(
				'user' => $this->user
			));
	}
	
	public function encryptPassAction(Request $request)
	{
		try {
			$log="";
			$this->initialize();
			$pass = urldecode(trim($request->request->get('pass')));
			$domain = $this->user->getUserProfile()->getMailServerURL();
			if (empty($domain) || strtolower($domain) == 'inactive.user' || strtolower($domain) == 'n/a' || strtolower($domain)  == 'mail server should be set manually.') {
				throw new \Exception('Unspecified mail server URL!');
			}
			$mailbox = imap_open('{'.$domain.'/novalidate-cert/readonly}', $this->user->getUserMail(), $pass);
			$response_xml = new \DOMDocument('1.0', 'UTF-8');
			$root = $response_xml->appendChild($response_xml->createElement('Results'));
			$attrib = $response_xml->createAttribute('ID');
			$attrib->value = 'Ret1';
		
			if (!empty($mailbox)) 
			{
				$session_obj = $this->get('session');
				if (empty($session_obj)){
					$log.="Empty session<br/>";
				}				
				$session_obj->set('epass', base64_encode($pass));
				unset($session_obj);
				$newnode = $response_xml->createElement('Result', 'OK');
				@imap_close($mailbox);
				$log.="Closed mailbox session<br/>";
			}
			else {
				$log.="Imap Open Error<br/>";
				$newnode = $response_xml->createElement('Result', 'FAILED');
			}
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			
			$response = new Response();
			$response->headers->set('Content-Type', 'text/xml');
			$xml = $response_xml->saveXML();
			if (empty($xml)){
				$response->setContent("<error>Failed</error>");
			}
			else{
				$response->setContent($xml);
			}
			
			return $response;
		}
		catch (\Exception $e){
			$response = new Response();
			$response->headers->set('Content-Type', 'text/html');
			$response->setContent("<html><title>Error</title><body><h3>Log: ".$log."<br/>Mail Error: "
					.$e->getMessage()." Line: ".$e->getLine()
					." Trace: ".$e->getTraceAsString()
					."<h3></body></html>");
			
			return $response;
		}
	}
	
	public function openOutlookAcquireMessagesAction(Request $request)
	{
		$folder_id = $request->query->get('ParentUNID');
		if (empty($folder_id))
		{
			throw $this->createNotFoundException('Folder ID is missed.');
		}
		
		$em = $this->getDoctrine()->getManager();
		$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id, 'Del' => false, 'Inactive' => 0));
		if (empty($folder))
		{
			throw $this->createNotFoundException('Unspecified source folder ID = '.$folder_id);
		}

		$this->initialize();

		if ($request->isMethod('POST') === true)
		{
			$security_check = new Miscellaneous($this->container);
			if (false === $security_check->canCreateDocument($folder)) 
			{
				throw new AccessDeniedException();
			}

			$post_request = $request->request;
			if (!trim($post_request->get('SendTo')) || !trim($post_request->get('Sender')) || !trim($post_request->get('Subject')) || !trim($post_request->get('body')) ||!trim($post_request->get('PostedDate')))
			{
				throw $this->createNotFoundException('Mail required meta data is missed.');
			}
			
			$doc_type = (!trim($post_request->get('DocumentTypeKey'))) ? 'Mail Memo' : trim($post_request->get('DocumentTypeKey'));
			$doc_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => $doc_type, 'Status' => true, 'Trash' => false));
			if (empty($doc_type))
			{
				throw $this->createNotFoundException('Unspecified Mail Memo document type source.');
			}

			$result_xml = new \DOMDocument('1.0', 'UTF-8');
			$root = $result_xml->appendChild($result_xml->createElement('Results'));

			$entity = new Documents();	
			$miscfunctions = new MiscFunctions();	
			$entity->setId($miscfunctions->generateGuid());
			$entity->setAuthor($this->user);
			$entity->setCreator($this->user);
			$entity->setModifier($this->user);
			$entity->setDateCreated(new \DateTime());
			$entity->setDateModified(new \DateTime());
			$entity->setDocTitle($post_request->get('Subject'));
			$entity->setDocType($doc_type);
			$entity->setDocStatus('Released');
			$entity->setStatusNo(1);
			$entity->setFolder($folder);
			$entity->setOwner($this->user);
				
			$em->persist($entity);
			$em->flush();
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
								$temp->setFieldValue(date('d-m-Y', strtotime($post_request->get('PostedDate'))));
								break;
							case 'mail_from':
								$temp->setFieldValue(trim($post_request->get('Sender')));
								break;
							case 'mail_to':
								$temp->setFieldValue(trim($post_request->get('SendTo')));
								break;
							case 'mail_cc':
								if (trim($post_request->get('CopyTo'))) {
									$temp->setFieldValue($post_request->get('CopyTo'));
								}
								else {
									unset($temp);
									continue;
								}
								break;
							case 'mail_subject':
								$temp->setFieldValue($post_request->get('Subject'));
								break;
							case 'terbody':
								$temp->setFieldValue($post_request->get('body'));
								break;
							default:
								unset($temp);
								continue;
						}
			
						if (!empty($temp)) {
							$em->persist($temp);
							$em->flush();
						}
							
						unset($temp);
					}
				}
				
				$em->refresh($entity);
				
				if (trim($post_request->get('msgAttachmentsSize')) && $request->files->count() > 0 && count($request->files->get('Uploader_DLI_Tools')) > 0)
				{
			
					$field = $em->getRepository('DocovaBundle:DesignElements')->findOneBy(array('fieldName' => 'mcsf_dliuploader1'));
					$files = explode(';', trim($post_request->get('msgAttachmentsSize')));
					$file_names = $file_dates = array();
					foreach ($files as $value)
					{
						$file_params = explode('*', $value);
						$file_names[] = $file_params[0];
						$file_dates[] = new \DateTime(date('d-m-Y', strtotime($post_request->get('PostedDate'))));
						unset($file_params);
					}

					$res = $this->moveUploadedFiles($request->files, $file_names, $file_dates, $entity, $field);
					if ($res !== true)
					{
						$res = (is_array($res)) ? $res : array('No file is uploaded.');
						$values = $entity->getTextValues();
						if (!empty($values) && count($values)) {
							foreach ($values as $value) {
								$em->remove($value);
								$em->flush();
							}
						}
						$values = $entity->getNumericValues();
						if (!empty($values) && count($values)) {
							foreach ($values as $value) {
								$em->remove($value);
								$em->flush();
							}
						}
						$values = $entity->getNameValues();
						if (!empty($values) && count($values)) {
							foreach ($values as $value) {
								$em->remove($value);
								$em->flush();
							}
						}
						$values = $entity->getGroupValues();
						if (!empty($values) && count($values)) {
							foreach ($values as $value) {
								$em->remove($value);
								$em->flush();
							}
						}
						$values = $entity->getDateValues();
						if (!empty($values) && count($values)) {
							foreach ($values as $value) {
								$em->remove($value);
								$em->flush();
							}
						}
						$values = $entity->getAttachments();
						if (!empty($values) && count($values)) {
							foreach ($values as $value) {
								$em->remove($value);
								$em->flush();
							}
						}
						
						$em->remove($entity);
						$em->flush();
						
						throw $this->createNotFoundException('Email attached file could not be uploaded and document could not be saved, because of the following error(s):<br/>'. implode('<br />', $res));
					}
				}
			
				$security_check->createDocumentLog($em, 'CREATE', $entity);
				$customAcl = new CustomACL($this->container);
				$customAcl->insertObjectAce($entity, $this->user, 'owner', false);
				$customAcl->insertObjectAce($entity, 'ROLE_USER', 'view');

				$newnode = $result_xml->createElement('Result', 'OK');
				$attrib	 = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
				
				$newnode = $result_xml->createElement('Unid', $entity->getId());
				$root->appendChild($newnode);
				$newnode = $result_xml->createElement('Url', $this->generateUrl('docova_homepage', array(), true));
				$root->appendChild($newnode);
			}
			
			$response = new Response();
			$response->headers->set('Content-Type', 'text/xml');
			$response->setContent($result_xml->saveXML());
			
			return $response;
		}

		return $this->render('DocovaBundle:Default:dlgOutlookMailAcquireMessages.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings,
			'folder' => $folder
		));
	}
	
	public function mailServicesAction(Request $request)
	{
		$this->initialize();
		$response	= new Response();
		$post_xml	= new \DOMDocument('1.0', 'UTF-8');
		$result_xml	= new \DOMDocument('1.0', 'UTF-8');

		$contentXML = $request->getContent();
		if (empty($contentXML)){
			$post_xml->loadXML("<request><Action>READMAILVIEW</Action></request>");
		}
		else
			$post_xml->loadXML($request->getContent());
		

		if (empty($post_xml->getElementsByTagName('Action')->item(0)->nodeValue))
		{
			throw $this->createNotFoundException('No action is defined.');
		}
		
		if (empty($post_xml->getElementsByTagName('mailserverurl')->item(0)->nodeValue) || empty($post_xml->getElementsByTagName('mailserver')->item(0)->nodeValue))
		{
			//throw $this->createNotFoundException('Mail Server is not set.');
		}
		
		$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
		//$mail_server = $post_xml->getElementsByTagName('mailserverurl')->item(0)->nodeValue;
		$session_obj = $this->get('session');
		if ($action === 'GETMAILFOLDERINFO')
		{
			$type = $request->query->get('type');
			$folders = $this->getMailFolders();
			if ($type != 'xml') {
				$response->headers->set('Content-Type', 'application/json');
				$response->setContent(json_encode($folders));
				return $response;
			}
			else {
				$xml = '<?xml version="1.0" encoding="UTF-8" ?><Results><Result ID="Status">OK</Result>';
				if (!empty($folders)) 
				{
					$xml .= '<Result ID="Ret1"><viewentries>';
					foreach ($folders as $f => $value) {
						$xml .= '<viewentry><entrydata columnnumber="0"><text>'.$f.'</text></entrydata>';
						$xml .= '<entrydata columnnumber="1"></entrydata>';
						$xml .= '<entrydata columnnumber="2"><text>0</text></entrydata></viewentry>';
						if (is_array($value)) {
							$xml .= $this->generateSubfolderNodes($value, $f);
						}
					}
					$xml .= '</viewentries></Result>';
				}
				$xml .= '</Results>';
				$response->headers->set('Content-Type', 'text/xml');
				$response->setContent($xml);
				return $response;
			}
		}
		elseif ($action === 'READMAILVIEW') {
			$output = array();
			$domain = $this->user->getUserProfile()->getMailServerURL();

			$xview = $post_xml->getElementsByTagName('viewname');
			if (empty($xview) || empty($xview->item(0)->nodeValue) ){
				$view="Inbox";
			}			
			else{
				$view = trim($xview->item(0)->nodeValue);
			}

			$count=20;			
			if (!empty($post_xml->getElementsByTagName('count')->item(0)->nodeValue)){
				$count = trim($post_xml->getElementsByTagName('count')->item(0)->nodeValue);
			}			
			$count = (int)$count ? $count : 20;			
			$start=1;
			if (!empty($post_xml->getElementsByTagName('start')->item(0)->nodeValue)){
				$start = trim($post_xml->getElementsByTagName('start')->item(0)->nodeValue);
			}
			$start = (int)$start ? $start : 1;
			$mailbox = imap_open('{'.$domain.'/novalidate-cert/readonly}'.$view, $this->user->getUserMail(), base64_decode($session_obj->get('epass')));
			$total = imap_num_msg($mailbox);
			$output['count'] = $total;
			$start = ($total - $start) + 1;
			$count = ($start - $count <= 0) ? 0 : ($start - $count);
			for ($x = $start; $x > $count; $x--) 
			{
				$msg_header = imap_headerinfo($mailbox, $x);
				if (!empty($msg_header)) 
				{
					$from = '';
					
					if (!empty($msg_header->from)){
						if (is_array($msg_header->from)) 
						{
							foreach ($msg_header->from as $fromObj) {
								$from .= (!empty($fromObj->personal)) ? $fromObj->personal. ',' : $fromObj->mailbox.',';
							}
							$from = substr_replace($from, '', -1);
						}
					}
					
					if (!empty($msg_header->subject))
						$msgSubject = imap_utf8($msg_header->subject);
					else
						$msgSubject = "-";
					
					if (!empty($msg_header->Size))
						$msgSize = $msg_header->Size;
				    else
					    $msgSize = 0;
				    
					try{    
						if (!empty($msg_header->udate))
							$msgDate = date('d-m-Y', $msg_header->udate);
						else {
							$msgDate = new \DateTime();
							$msgDate = $msgDate->format("d-m-Y");
						}
					}
					catch (\Exception $e){
						$msgDate = new \DateTime();
						$msgDate = $msgDate->format("d-m-Y");
					}
					
					
					$output['messages'][] = array(
						'msgno' => $x,
						'Subject' => $msgSubject,
						'From' => imap_utf8($from),
						'Date' => $msgDate,
						'Size' => $msgSize
					);
				}
			}
			
			$type = $request->query->get('type');
			if ($type != 'xml') {
				$response->headers->set('Content-Type', 'application/json');
				$response->setContent(json_encode($output));
				return $response;
			}
			else {
				$xml = '<?xml version="1.0" encoding="UTF-8" ?><Results><Result ID="Status">OK</Result>';
				if (!empty($output['messages'])) 
				{
					$xml .= '<Result ID="Ret1"><toplevelentries>'.$output['count'].'</toplevelentries><viewentries>';
					foreach ($output['messages'] as $msg) {
						$xml .= "<viewentry><id>{$msg['msgno']}</id><selected/>";
						$xml .= "<who>{$msg['From']}</who>";
						$xml .= "<date>{$msg['Date']}</date><time>00:00 AM</time>";
						$xml .= "<size>{$msg['Size']}</size>";
						$xml .= "<subject>{$msg['Subject']}</subject><icon>&#160;</icon>";
						$xml .= '<attachments><filecount>0</filecount></attachments><hasfiles>0</hasfiles></viewentry>';
					}
					$xml .= '</viewentries></Result>';
				}
				$xml .= '</Results>';
				
				$response->headers->set('Content-Type', 'text/xml');
				$response->setContent($xml);
				return $response;
			}
		}
		elseif ($action === 'CREATETEMPMESSAGE')
		{
			$session_obj = $this->get('session');
			if (!trim($session_obj->get('epass')))
			{
				throw $this->createNotFoundException('User not logged in his/her mail server.');
			}
			
			$msg_no = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (empty($msg_no))
			{
				throw $this->createNotFoundException('No email is selected to acquire.');
			}

			$response->headers->set('Content-Type', 'application/json');
			try {
				$view = $post_xml->getElementsByTagName('viewname')->item(0)->nodeValue;
				$domain = $this->user->getUserProfile()->getMailServerURL();
				$mailbox = imap_open('{'.$domain.'/novalidate-cert}'.$view, $this->user->getUserMail(), base64_decode($session_obj->get('epass')));
				$email = imap_headerinfo($mailbox, $msg_no);
				if (!empty($email) && !empty($email->Subject))
					$subject = imap_utf8($email->Subject);
				else
					$subject="-";
				
				$content = $this->getMailContent($mailbox, $msg_no);
				$output = [
					'docid' => $content['tempid'],
					'subject' => $subject,
					'body' => empty($content['html']) ? utf8_encode($content['plain']) : $content['html'],
					'attachments' => $content['attachments']
				];
				
				$response->setContent(json_encode(array('Result' => 'OK', 'Ret1' => $output)));
			}
			catch (\Exception $e) {
				$response->setContent(json_encode(array('Result' => 'FAILED', 'errmsg' => 'Selected message could not be acquired. Error Message: '.$e->getMessage())));
			}

			return $response;
		}
		elseif ($action === 'DELETETEMPMESSAGE') {
			$unid = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($unid))
			{
				$files = glob($this->file_path.DIRECTORY_SEPARATOR.'mails'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.$unid.'_*', GLOB_MARK);
				foreach ($files as $filename) {
					@unlink($filename);
				}
			}
			$xml = '<?xml version="1.0" encoding="UTF-8" ?><Results><Result ID="Status">OK</Result></Results>';
			$response->headers->set('Content-Type', 'text/xml');
			$response->setContent($xml);
			return $response;
		}
		elseif ($action === 'IMPORTMESSAGES')
		{
			$session_obj = $this->get('session');
			if (!trim($session_obj->get('epass'))) 
			{
				throw $this->createNotFoundException('User not logged in his/her mail server.');
			}

			$msg_nos = $post_xml->getElementsByTagName('Unids')->item(0)->nodeValue;
			$msg_nos = explode(',', $msg_nos);
			if (empty($msg_nos)) 
			{
				throw $this->createNotFoundException('No email is selected to import.');
			}
			
			if (empty($post_xml->getElementsByTagName('FolderUnid')->item(0)->nodeValue))
			{
				throw $this->createNotFoundException('Folder ID is missed.');
			}
	
			$em = $this->getDoctrine()->getManager();
			$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $post_xml->getElementsByTagName('FolderUnid')->item(0)->nodeValue, 'Del' => false, 'Inactive' => 0));
			if (empty($folder))
			{
				throw $this->createNotFoundException('Unspecified source folder ID.');
			}

			$doc_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => 'Mail Memo', 'Status' => true, 'Trash' => false));
			if (empty($doc_type))
			{
				throw $this->createNotFoundException('Unspecified Document Type source for Mail Memo');
			}

			$security_check = new Miscellaneous($this->container);
			if (false === $security_check->canCreateDocument($folder)) 
			{
				throw new AccessDeniedException();
			}
			
			$count = 0;
			$view = $post_xml->getElementsByTagName('viewname')->item(0)->nodeValue;
			$domain = $this->user->getUserProfile()->getMailServerURL();
			$mailbox = imap_open('{'.$domain.'/novalidate-cert}'.$view, $this->user->getUserMail(), base64_decode($session_obj->get('epass')));

			/*
			 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			 * Revised shared IMAP import procedure
			 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			 */
			//initialize memo creation parameters
			$customAcl = new CustomACL($this->container);
				
			foreach ($msg_nos as $num)
			{
				//use the IMAP msg importer to process the message
				$importer = new IMAPMsgImporter($this->container, null, $em, $mailbox, $num);
				//reuse existing variables for efficiency
				$importer->setMiscellaneous($security_check);
				$importer->setCustomAcl($customAcl);
				$importer->setMemoType($doc_type);
				$importer->setUser($this->user);
			
				//initalize the memo document
				$memo = $importer->createMemoDocument($folder);
				//import and save into the new memo document
				$importer->importMessage($memo);
			
				$count++;
			
			}
			/*
			 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			 * Revised shared IMAP import procedure complete
			 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			 */			
			
			$response->headers->set('Content-Type', 'application/json');
			if ($count > 0) 
			{
				$response->setContent(json_encode(array('Result' => 'OK', 'Count' => $count)));
			}
			else {
				$response->setContent(json_encode(array('Result' => 'FAILED', 'errmsg' => 'None of selected messages are imported.')));
			}
			return $response;
		}
		elseif ($action === 'SAVEMESSAGES') 
		{
			$session_obj = $this->get('session');
			if (!trim($session_obj->get('epass'))) 
			{
				throw $this->createNotFoundException('User not logged in his/her mail server.');
			}
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $post_xml->getElementsByTagName('DocumentUnid')->item(0)->nodeValue, 'Archived' => false));
			$view = $post_xml->getElementsByTagName('viewname')->item(0)->nodeValue;
			$imported = 0;
			$error = '';
			if (!empty($document)) 
			{
				try {
					$domain = $this->user->getUserProfile()->getMailServerURL();
					$mailbox = imap_open('{'.$domain.'/novalidate-cert}'.$view, $this->user->getUserMail(), base64_decode($session_obj->get('epass')));
					if ($mailbox && !empty($post_xml->getElementsByTagName('Unids')->item(0)->nodeValue)) {
						$msg_nos = explode(',', $post_xml->getElementsByTagName('Unids')->item(0)->nodeValue);
						foreach ($msg_nos as $msg_no)
						{
							$email = imap_headerinfo($mailbox, $msg_no);
							//$email = imap_fetch_overview ($mailbox, $msg_no);
							$related_email = new RelatedEmails();
							$related_email->setFromWho($email->fromaddress);
							$related_email->setToWho($email->toaddress);
							$related_email->setDateSent(new \DateTime($email->date));
							$related_email->setSubject(imap_utf8($email->Subject));
							$related_email->setDocument($document);
							$em->persist($related_email);
							$em->flush();
							
							$filename = $this->file_path.DIRECTORY_SEPARATOR.'mails'.DIRECTORY_SEPARATOR.$related_email->getId();
							imap_savebody($mailbox, $filename, $msg_no, '', FT_INTERNAL);
							$imported++;
						}
					}
				}
				catch (\Exception $e) {
					$error = $e->getMessage();
				}
			}
			$response->headers->set('Content-Type', 'application/json');
			if ($imported > 0)
			{
				$log_obj = new Miscellaneous($this->container);
				$log_obj->createDocumentLog($em, 'UPDATE', $document, 'Added email message(s) to Related Correspondence.');
				unset($log_obj);
				$response->setContent(json_encode(array('Result' => 'OK', 'Count' => $imported)));
			}
			else {
				$response->setContent(json_encode(array('Result' => 'FAILED', 'errmsg' => $error)));
			}
			return $response;
		}
		
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($result_xml->saveXML());
		return $response;
	}
	
	public function sendLinkMessageAction(Request $request)
	{
		$folder_id = $request->query->get('ParentUNID');
		$doc_id = $request->query->get('DocUNID');
		if (empty($folder_id) && empty($doc_id))
		{
			throw $this->createNotFoundException('Folder/Document ID is missed.');
		}
		
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$access_check = new Miscellaneous($this->container);
		
		//Public access features require the access level for folders		
		if (!empty($folder_id)) {
			$parent = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $folder_id, 'Del' => false, 'Inactive' => 0));
			$access_levels = $access_check->getAccessLevel($parent);
		}
		else {
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id, 'Trash' => false, 'Archived' => false));
			$parent = !empty($document) ? $document->getFolder() : null;
			if (empty($parent) && $document->getApplication())
				$parent = $document->getApplication();
		}
		if (empty($parent))
		{
			throw $this->createNotFoundException('Unspecified source folder/document ID.');
		}
		
		$options =array(
			'user' => $this->user,
			'folder' => !empty($doc_id) ? $document : $parent
		);

		//security info for public access features
		if (!empty($access_levels))
			$options['user_access'] = $access_levels;
		
		return $this->render('DocovaBundle:Default:dlgSendLinkMessage.html.twig', 
			$options
		);
	}

	private function sendPublicAccessMessage($folder_name,$folder_path,$post_xml,$send_to,$subject,$body,$result_xml,$root){
		$content_body = <<<BODY
<font size="2" face="verdana,arial">{$body}</font><br><br>
BODY;
		if (!empty($post_xml->getElementsByTagName('PublicAccessLink')->length))
		{
			$content_body .= <<<BODY
<br>
<table style="background-color:white;border: 1px solid white;" border="0" cellspacing="1" cellpadding="2" width="600">
BODY;
			$content_body .= <<<BODY
	<tr>
		<td style="background-color:white;padding:2px; border: 1px solid #dfdfdf;"><font face="verdana,arial" size="1">
			<a href="{$post_xml->getElementsByTagName('PublicAccessLink')->item(0)->nodeValue}">DOCOVA Public Access Link</a></font>
		</td>
	</tr>
BODY;
			
			$content_body .= <<<BODY
</table>
BODY;
		}
		
		try {
		    $fromname =  ($this->user->getUserProfile() ?  $this->user->getUserProfile()->getDisplayName() : $this->user->getUserNameDnAbbreviated());
			$message = new \Swift_Message();
			$message->setSubject($subject)
			->setTo($send_to)
			//					->setSender(array($this->container->getParameter('sender_address') => 'DOCOVA Administrator'))
			->setFrom(array($this->user->getUserMail() => $fromname))
			->setBody($content_body, 'text/html');
		
			$this->get('mailer')->send($message, $failur);
		}
		catch (\Exception $e) {
			$failur = $e->getMessage();
		}
			
		if (empty($failur)) {
			$newnode = $result_xml->createElement('Result', 'OK');
			$attrib	 = $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
		}
		else {
			$newnode = $result_xml->createElement('Result', 'FAILED');
			$attrib = $result_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
		}
	} 
	
	public function openForwardDocumentDialogAction(Request $request)
	{
		$this->initialize();
		$document = $request->query->get('SourceDocUNID');
		$em = $this->getDoctrine()->getManager();
		$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Trash' => false, 'Archived' => false));
		if (empty($document)) 
		{
			$document = null;
		}
		return $this->render('DocovaBundle:Default:dlgForwardDocument.html.twig', array(
			'user' => $this->user,
			'document' => $document
		));
	}
	
	public function messagingServicesAction(Request $request)
	{
		$post_xml = $request->getContent();
		if (empty($post_xml))
		{
			throw $this->createNotFoundException('No data is submitted.');
		}
		
		$result_xml = new \DOMDocument('1.0', 'UTF-8');
		$root = $result_xml->appendChild($result_xml->createElement('Results'));
		$post_xml = new \DOMDocument('1.0', 'UTF-8');
		$post_xml->loadXML(rawurldecode($request->getContent()));
		
		if (empty($post_xml->getElementsByTagName('Action')->item(0)->nodeValue)) 
		{
			throw $this->createNotFoundException('Action is missed.');
		}
		$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;

		if (empty($post_xml->getElementsByTagName('SendTo')->item(0)->nodeValue) && empty($post_xml->getElementsByTagName('AutoNotify')->item(0)->nodeValue))
		{
			throw $this->createNotFoundException('No one is picked as "Send To".');
		}
		$auto_notify = !empty($post_xml->getElementsByTagName('AutoNotify')->item(0)->nodeValue) ? intval($post_xml->getElementsByTagName('AutoNotify')->item(0)->nodeValue) : null;
		$send_to = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $post_xml->getElementsByTagName('SendTo')->item(0)->nodeValue);
		

		$copy_to = [];
		if (!empty($post_xml->getElementsByTagName('CopyTo')->item(0)->nodeValue))
		{
		    $copy_to = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $post_xml->getElementsByTagName('CopyTo')->item(0)->nodeValue);
		}
		
		$bcc = [];
		if (!empty($post_xml->getElementsByTagName('BCC')->item(0)->nodeValue))
		{
		    $bcc = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $post_xml->getElementsByTagName('BCC')->item(0)->nodeValue);
		}
		
		$subject = !empty($post_xml->getElementsByTagName('Subject')->item(0)->nodeValue) ? $post_xml->getElementsByTagName('Subject')->item(0)->nodeValue : (!empty($auto_notify) ? 'New Comment Posted' : null);
		if (empty($subject))
		{
			throw $this->createNotFoundException('Subject is missed.');
		}

		if (!empty($post_xml->getElementsByTagName('Body')->item(0)->nodeValue))
		{
			$body = nl2br($post_xml->getElementsByTagName('Body')->item(0)->nodeValue, false);
		}
		elseif (empty($auto_notify)) {
			throw $this->createNotFoundException('Email content body is missed.');
		}
		else {
			$body = 'Comments: '.nl2br(!empty($post_xml->getElementsByTagName('Comment')->item(0)->nodeValue) ? $post_xml->getElementsByTagName('Comment')->item(0)->nodeValue : '');
		}
				
		$folder_path = '';
		$folder_name = '';
		if ($action !== 'FORWARDDOCUMENT' && $action !== 'MAILSEND')
		{
			if ((!empty($post_xml->getElementsByTagName('FolderPath')->item(0)->nodeValue) && !empty($post_xml->getElementsByTagName('FolderName')->item(0)->nodeValue)))
			{
				$folder_path = $post_xml->getElementsByTagName('FolderPath')->item(0)->nodeValue;
				$folder_name = $post_xml->getElementsByTagName('FolderName')->item(0)->nodeValue;
			}
		}
		
		$this->initialize();
		
		$temp_send_to = [];
		foreach ($send_to as $index => $user)
		{
			//-- check if a valid email address on its own
			if (false !== ($tempemail = filter_var(trim($user), FILTER_VALIDATE_EMAIL))) {
				array_push($temp_send_to, $tempemail);
			//-- check if it is a group with members
			}else if (false !== ($groupmembers = $this->fetchGroupMembers($user))){
				foreach($groupmembers as $tempuser){
					array_push($temp_send_to, $tempuser->getUserMail());
				}
			//-- try and look up the user from internal list as well as ldap, but don't create if not found
			}else if (false !== ($tempuser = $this->findUserAndCreate($user, false))) {
				//-- check if an object was returned
				if(is_object($tempuser)){
					array_push($temp_send_to, $tempuser->getUserMail());
				//-- check if an ldap data array was returned
				}else if(is_array($tempuser) && array_key_exists("mail", $tempuser)){
					array_push($temp_send_to, $tempuser["mail"]);
				}
			}
		}
		$send_to = $temp_send_to;

		$temp_copy_to = [];		
		foreach ($copy_to as $index => $user)
		{
			//-- check if a valid email address on its own					
			if (false !== ($tempemail = filter_var(trim($user), FILTER_VALIDATE_EMAIL))) {
				array_push($temp_copy_to, $tempemail);			
			//-- check if it is a group with members
			}else if (false !== ($groupmembers = $this->fetchGroupMembers($user))){
				foreach($groupmembers as $tempuser){
					array_push($temp_copy_to, $tempuser->getUserMail());
				}
			//-- try and look up the user from internal list as well as ldap, but don't create if not found
			}else if (false !== ($tempuser = $this->findUserAndCreate($user, false))) {
				//-- check if an object was returned
				if(is_object($tempuser)){
					array_push($temp_copy_to, $tempuser->getUserMail());
				//-- check if an ldap data array was returned
				}else if(is_array($tempuser) && array_key_exists("mail", $tempuser)){
					array_push($temp_copy_to, $tempuser["mail"]);
				}
			}
		}
		$copy_to = $temp_copy_to;		
		
		$temp_bcc = [];
		foreach ($bcc as $index => $user)
		{
			//-- check if a valid email address on its own
			if (false !== ($tempemail = filter_var(trim($user), FILTER_VALIDATE_EMAIL))) {
				array_push($temp_bcc, $tempemail);
			//-- check if it is a group with members
			}else if (false !== ($groupmembers = $this->fetchGroupMembers($user))){
				foreach($groupmembers as $tempuser){
					array_push($temp_bcc, $tempuser->getUserMail());
				}				
			//-- try and look up the user from internal list as well as ldap, but don't create if not found
			}else if (false !== ($tempuser = $this->findUserAndCreate($user, false))) {
				//-- check if an object was returned
				if(is_object($tempuser)){
					array_push($temp_bcc, $tempuser->getUserMail());
					//-- check if an ldap data array was returned
				}else if(is_array($tempuser) && array_key_exists("mail", $tempuser)){
					array_push($temp_bcc, $tempuser["mail"]);
				}
			}
		}
		$bcc = $temp_bcc;
		
		if (empty($send_to) && !empty($auto_notify))
		{
			$em = $this->getDoctrine()->getManager();
			foreach ($post_xml->getElementsByTagName('Unid') as $item)
			{
				$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $item->nodeValue, 'Archived' => false));
				if (!empty($document)) {
					if ($auto_notify === 1) {
						$send_to[] = $document->getAuthor()->getUserMail();
					}
					elseif ($auto_notify === 2) {
						$active_step = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getFirstPendingStep($item->nodeValue);
						if (!empty($active_step)) {
							$active_step = $active_step[0];
							foreach ($active_step->getAssignee() as $assignee) {
								$send_to[] = $assignee->getAssignee()->getUserMail();
							}
						}
					}
					elseif ($auto_notify === 3) {
						$send_to[] = $document->getAuthor()->getUserMail();
						$active_step = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getFirstPendingStep($item->nodeValue);
						if (!empty($active_step)) {
							$active_step = $active_step[0];
							foreach ($active_step->getAssignee() as $assignee) {
								$send_to[] = $assignee->getAssignee()->getUserMail();
							}
						}
					}
				}
			}
		}
		
		if (empty($send_to) && empty($copy_to) && empty($bcc))
		{
			throw $this->createNotFoundException('No valid mail recipients found.');
		}
		
		$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());

		if ($action === 'SENDLINKMSG')
		{
				$content_body = <<<BODY
<font size="2" face="verdana,arial">{$body}</font><br>
BODY;

			if(!empty($folder_path)){
				$content_body .= <<<BODY
<br><font size="1" face="verdana,arial"><b>Related folder and/or document(s)</b>
<br><br>Folder:
<table style="background-color:white;border: 1px solid white;" border="0" cellspacing="1" cellpadding="2" width="600">
	<tr>
		<td style="background-color:white;padding:2px; border: 1px solid #dfdfdf;"><font face="verdana,arial" size="1">
			<a href="{$folder_path}">{$folder_name}</a></font>
		</td>
	</tr>
</table>
BODY;

				if (!empty($post_xml->getElementsByTagName('Unid')->length))
				{
					$content_body .= <<<BODY
<br>Document(s)
<table style="background-color:white;border: 1px solid white;" border="0" cellspacing="1" cellpadding="2" width="600">
BODY;
					$em = $this->getDoctrine()->getManager();
					foreach ($post_xml->getElementsByTagName('Unid') as $item)
					{
						$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $item->nodeValue, 'Archived' => false));
						if (!empty($document))
						{
							$content_body .= <<<BODY
	<tr>
		<td style="background-color:white;padding:2px; border: 1px solid #dfdfdf;"><font face="verdana,arial" size="1">
			<a href="{$folder_path}%2C{$item->nodeValue}">{$document->getDocTitle()}</a></font>
		</td>
	</tr>
BODY;
						}
						unset($document);
					}
					unset($em);
					$content_body .= <<<BODY
</table>
BODY;
				}
			}
			
			if (!empty($post_xml->getElementsByTagName('Link')->length))
			{
				foreach($post_xml->getElementsByTagName('Link') as $linknode) {
					$linkval = $linknode->textContent;
					if(!empty($linkval)) {
						$content_body .= <<<BODY
<br><a href="{$linkval}">Linked Document</a><br>
BODY;
					}
				}
			}			
		
			try {
			    $fromname =  ($this->user->getUserProfile() ?  $this->user->getUserProfile()->getDisplayName() : $this->user->getUserNameDnAbbreviated());
				$message = new \Swift_Message();
				$message->setSubject($subject)
					->setTo($send_to)
					->setFrom(array($this->user->getUserMail() => $fromname))
					->setBody($content_body, 'text/html');
				
				$this->get('mailer')->send($message, $failur);
			}
			catch (\Exception $e) {
				$failur = $e->getMessage();
			}
			
			if (empty($failur)) {
				$newnode = $result_xml->createElement('Result', 'OK');
				$attrib	 = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
			}
			else {
				$newnode = $result_xml->createElement('Result', 'FAILED');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
			}
		}
		elseif ($action === 'SENDPUBLICACCESSMSG')
		{
			$this->sendPublicAccessMessage($folder_name, $folder_path, $post_xml, $send_to, $subject, $body, $result_xml, $root);
		}
		elseif ($action === 'SENDATTACHMENTMSG')
		{
			$content_body = <<<BODY
{$body}

Check the attachment file(s)

BODY;
			try {
			    $fromname =  ($this->user->getUserProfile() ?  $this->user->getUserProfile()->getDisplayName() : $this->user->getUserNameDnAbbreviated());
				$message = new \Swift_Message();
				$message->setSubject($subject)
					->setTo($send_to)
					->setFrom(array($this->user->getUserMail() => $fromname))
					->setBody($content_body);

				if (!empty($post_xml->getElementsByTagName('Unid')->length))
				{
					$em = $this->getDoctrine()->getManager();
					foreach ($post_xml->getElementsByTagName('Unid') as $item)
					{
						$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $item->nodeValue, 'Archived' => false));
						if (!empty($document))
						{
							if ($document->getAttachments()->count() > 0)
							{
								foreach ($document->getAttachments() as $attachment)
								{
									$valid = true;
									try {
										$filepath = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root') : $_SERVER['DOCUMENT_ROOT'];
										$filepath .= DIRECTORY_SEPARATOR . $this->get('assets.packages')->getUrl('upload/scripts/');
										if ($document->getDocType()->getFilterAttachmentScript() && file_exists($filepath . $document->getDocType()->getFilterAttachmentScript() . '.php'))
										{
											$className = $document->getDocType()->getFilterAttachmentScript();
											include_once $filepath . $className . '.php';
											if (class_exists($className)) {
												$filterAttachments = new $className($em);
												if (true !== $filterAttachments->isValidAttachment($attachment)) {
													$valid = false;
												}
												$filterAttachments = null;
											}
											$className = null;
										}
										$filepath = null;
									}
									catch (\Exception $e) {
										$logger = $this->container->get('logger');
										$logger->error('filterAttachment.ERROR: Uncaught PHP exception '.$e->getFile(). ': "' .$e->getMessage(). '" on line ' . $e->getLine() . "\n\r{" . $e->getTraceAsString() . "}\n");
										$logger = null;
									}
		
									if ($valid === true)
									{
										$file_path = $this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($attachment->getFileName());
										$content_type = ($attachment->getFileMimeType()) ? $attachment->getFileMimeType() : 'application/octet-stream';
										$message->attach(\Swift_Attachment::fromPath($file_path, $content_type)->setFilename($attachment->getFileName()));
									}
								}
							}
						}
					}
				}
	
				$this->get('mailer')->send($message, $failur);
			}
			catch (\Exception $e) {
				$failur = $e->getMessage();
			}
				
			if (empty($failur)) {
				$newnode = $result_xml->createElement('Result', 'OK');
				$attrib	 = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
			}
			else {
				$newnode = $result_xml->createElement('Result', 'FAILED');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
			}
		}
		elseif ($action === 'FORWARDDOCUMENT')
		{
			$em = $this->getDoctrine()->getManager();
			$document = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Trash' => false, 'Archived' => false));
			if (empty($document)) 
			{
				throw $this->createNotFoundException('Unable to locate document with entered UNID');
			}
			$today = date('m/d/Y h:i:s A');
			$body = urldecode($body);
			$originalBody = urldecode($post_xml->getElementsByTagName('OriginalBody')->item(0)->nodeValue);
			if ($document->getDocType()->getDocName() == 'Mail Memo') 
			{
				$mail_field_values = $em->getRepository('DocovaBundle:DesignElements')->getDocumentFieldsValue($document->getId(), array('mail_date', 'mail_from', 'mail_to', 'mail_cc', 'mail_subject'));
				foreach ($mail_field_values as $fv) {
					if ($fv['Field_Name'] == 'mail_date') {
						$post_date = !empty($fv['mail_date']) ? $fv['mail_date'] : date('m/d/Y h:i:s A');
					}
					if ($fv['Field_Name'] == 'mail_from') {
						$from = $fv['mail_from'];
					}
					if ($fv['Field_Name'] == 'mail_to') {
						$to = $fv['mail_to'];
					}
					if ($fv['Field_Name'] == 'mail_cc') {
						$cc = $fv['mail_cc'];
					}
					if ($fv['Field_Name'] == 'mail_subject') {
						$msubject = $fv['mail_subject'];
					}
				}
				$hBody = <<<HBODY
<table>
	<tr><td><font face="Arial" color="666666" size="2">Date: </font></td><td><font face="Arial" color="black" size="2">{$post_date}</font></td></tr>
	<tr><td><font face="Arial" color="666666" size="2">From: </font></td><td><font face="Arial" color="black" size="2">{$from}</font></td></tr>
	<tr><td><font face="Arial" color="666666" size="2">To: </font></td><td><font face="Arial" color="black" size="2">{$to}</font></td></tr>
	<tr><td><font face="Arial" color="666666" size="2">Cc: </font></td><td><font face="Arial" color="black" size="2">{$cc}</font></td></tr>
	<tr><td><font face="Arial" color="666666" size="2">Subject: </font></td><td><font face="Arial" color="black" size="2">{$msubject}</font></td></tr>
</tabl><br>
HBODY;
			}
			else {
				$hBody = <<<HBODY
<table>
	<tr><td><font face="Arial" color="666666" size="2">Date: </font></td><td><font face="Arial" color="black" size="2">{$document->getDateCreated()->format($defaultDateFormat . ' h:i:s A')}</font></td></tr>
	<tr><td><font face="Arial" color="666666" size="2">Author: </font></td><td><font face="Arial" color="black" size="2">{$document->getCreator()->getUserNameDnAbbreviated()}</font></td></tr>
	<tr><td><font face="Arial" color="666666" size="2">Subject: </font></td><td><font face="Arial" color="black" size="2">{$document->getDocTitle()}</font></td></tr>
</tabl><br>
HBODY;
			}

			$content_body = <<<BODY
{$body}{$hBody}<font face="Arial" color="990099" size="2">----- Forwarded by {$this->user->getUserNameDnAbbreviated()} on {$today}-----</b></font><br>
{$originalBody}
BODY;
			try {
			    $fromname =  ($this->user->getUserProfile() ?  $this->user->getUserProfile()->getDisplayName() : $this->user->getUserNameDnAbbreviated());
				$message = new \Swift_Message();
				$message->setSubject($subject)
						->setTo($send_to)
						->setFrom(array($this->user->getUserMail() => $fromname))
						->setBody(nl2br($content_body), 'text/html', 'UTF-8');
			
				if (!empty($post_xml->getElementsByTagName('IncludeAttachments')->item(0)->nodeValue))
				{
					if ($document->getAttachments()->count() > 0)
					{
						foreach ($document->getAttachments() as $attachment)
						{
							$valid = true;
							try {
								$filepath = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root') : $_SERVER['DOCUMENT_ROOT'];
								$filepath .= DIRECTORY_SEPARATOR . $this->get('assets.packages')->getUrl('upload/scripts/');
								if ($document->getDocType()->getFilterAttachmentScript() && file_exists($filepath . $document->getDocType()->getFilterAttachmentScript() . '.php'))
								{
									$className = $document->getDocType()->getFilterAttachmentScript();
									include_once $filepath . $className . '.php';
									if (class_exists($className)) {
										$filterAttachments = new $className($em);
										if (true !== $filterAttachments->isValidAttachment($attachment)) {
											$valid = false;
										}
										$filterAttachments = null;
									}
									$className = null;
								}
								$filepath = null;
							}
							catch (\Exception $e) {
								$logger = $this->container->get('logger');
								$logger->error('filterAttachment.ERROR: Uncaught PHP exception '.$e->getFile(). ': "' .$e->getMessage(). '" on line ' . $e->getLine() . "\n\r{" . $e->getTraceAsString() . "}\n");
								$logger = null;
							}

							if ($valid === true)
							{
								$file_path = $this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($attachment->getFileName());
								$content_type = ($attachment->getFileMimeType()) ? $attachment->getFileMimeType() : 'application/octet-stream';
								$message->attach(\Swift_Attachment::fromPath($file_path, $content_type)->setFilename($attachment->getFileName()));
							}
						}
					}
				}
			
				$this->get('mailer')->send($message, $failur);
				if (empty($failur) && !empty($post_xml->getElementsByTagName('SaveCopy')->item(0)->nodeValue)) 
				{
					$doc_type = $em->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => 'Mail Memo', 'Status' => true, 'Trash' => false));
					if (empty($doc_type))
					{
						throw $this->createNotFoundException('Unspecified Document Type source for Mail Memo');
					}
		
					$security_check = new Miscellaneous($this->container);
					$folder = $document->getFolder();
					if (false === $security_check->canCreateDocument($folder))
					{
						throw new AccessDeniedException();
					}

					$subforms = $doc_type->getDocTypeSubform();
					if ($subforms->count() > 0)
					{
						$entity = new Documents();
						$miscfunctions = new MiscFunctions();
						$entity->setId($miscfunctions->generateGuid());
						$entity->setAuthor($this->user);
						$entity->setCreator($this->user);
						$entity->setModifier($this->user);
						$entity->setDateCreated(new \DateTime());
						$entity->setDateModified(new \DateTime());
						$entity->setDocTitle($post_xml->getElementsByTagName('Subject')->item(0)->nodeValue);
						$entity->setDocType($doc_type);
						$entity->setDocStatus('Released');
						$entity->setStatusNo(1);
						$entity->setFolder($folder);
						$entity->setOwner($this->user);
							
						$em->persist($entity);
						$em->flush();
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
										$temp->setFieldValue(date('d-m-Y h:i:s A'));
										break;
									case 'mail_from':
										$temp->setFieldValue($this->user->getUserMail());
										break;
									case 'mail_to':
										$temp->setFieldValue($send_to);
										break;
									case 'mail_cc':
									case 'mail_bcc':
										unset($temp);
										continue;
									case 'mail_subject':
										$temp->setFieldValue($subject);
										break;
									case 'terbody':
										$temp->setFieldValue($content_body);
										break;
								}
					
								if (!empty($temp)) {
									$em->persist($temp);
									$em->flush();
								}
								unset($temp);
							}
						}
					
						$security_check->createDocumentLog($em, 'CREATE', $entity);
						$customAcl = new CustomACL($this->container);
						$customAcl->insertObjectAce($entity, $this->user, 'owner', false);
						$customAcl->insertObjectAce($entity, 'ROLE_USER', 'view');
						if (!empty($post_xml->getElementsByTagName('IncludeAttachments')->item(0)->nodeValue) && $document->getAttachments()->count() > 0)
						{
							$field = $em->getRepository('DocovaBundle:DesignElements')->findOneBy(array('Field_Name' => 'mcsf_dliuploader1'));
							foreach ($document->getAttachments() as $attachment) {
								$file_path = $this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5($attachment->getFileName());
								if (!is_dir($this->file_path.DIRECTORY_SEPARATOR.$entity->getId().DIRECTORY_SEPARATOR)) {
									mkdir($this->file_path.DIRECTORY_SEPARATOR.$entity->getId().DIRECTORY_SEPARATOR);
								}
								
								//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$entity->getId(), '0777');
								copy($this->file_path.DIRECTORY_SEPARATOR.$document->getId().DIRECTORY_SEPARATOR.md5(basename($attachment->getFileName())), $this->file_path.DIRECTORY_SEPARATOR.$entity->getId().DIRECTORY_SEPARATOR.md5(basename($attachment->getFileName())));
								//@chmod($this::UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$entity->getId(), '0666');
									
								$att = new AttachmentsDetails();
								$att->setDocument($entity);
								$att->setField($field);
								$att->setFileDate($attachment->getFileDate());
								$att->setFileMimeType($attachment->getFileMimeType());
								$att->setFileName($attachment->getFileName());
								$att->setFileSize($attachment->getFileSize());
								$att->setAuthor($this->user);
								
								$em->persist($att);
								$em->flush();
								unset($att);
							}
						}
					}
				}
				
				$properties = $em->getRepository('DocovaBundle:DocumentTypes')->containsSubform($document->getDocType()->getId(), 'Related Emails');
				if (!empty($properties['Properties_XML']) && false !== strpos($properties['Properties_XML'], '<ForwardSaveAs>1</ForwardSaveAs>')) {
					$related_email = new RelatedEmails();
					$related_email->setFromWho($this->user->getUserMail());
					$related_email->setToWho(implode(',', array_values($send_to)));
					$related_email->setDateSent(new \DateTime());
					$related_email->setSubject(imap_utf8($subject));
					$related_email->setDocument($document);
					$em->persist($related_email);
					$em->flush();
					if (!is_dir($this->file_path.DIRECTORY_SEPARATOR.'mails')) {
						mkdir($this->file_path.DIRECTORY_SEPARATOR.'mails');
					}
					$filename = $this->file_path.DIRECTORY_SEPARATOR.'mails'.DIRECTORY_SEPARATOR.$related_email->getId();
					$related_email = null;
					file_put_contents($filename, $content_body);
				}
			}
			catch (\Exception $e) {
				$failur = $e->getMessage();
			}
						
			if (empty($failur)) {
				$newnode = $result_xml->createElement('Result', 'OK');
				$attrib	 = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
			}
			else {
				$newnode = $result_xml->createElement('Result', 'FAILED');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
			}
		}
		elseif ($action === 'MAILSEND'){
			$content_body = <<<BODY
{$body}<br><br>
BODY;
			if (!empty($post_xml->getElementsByTagName('Link')->length))
			{
				foreach($post_xml->getElementsByTagName('Link') as $linknode) {
					$linkval = $linknode->textContent;
					if(!empty($linkval)) {
						$content_body .= <<<BODY
<a href="{$linkval}">Linked Document</a><br>
BODY;
					}
				}
			}
				
			try {
			    $fromname =  ($this->user->getUserProfile() ?  $this->user->getUserProfile()->getDisplayName() : $this->user->getUserNameDnAbbreviated());
				$message = new \Swift_Message();
				$message->setSubject($subject)
				->setTo($send_to)
				->setCc($copy_to)
				->setBcc($bcc)
				->setFrom(array($this->user->getUserMail() => $fromname))
				->setBody($content_body, 'text/html');
					
				$this->get('mailer')->send($message, $failur);
			}
			catch (\Exception $e) {
				$failur = $e->getMessage();
			}
		
			if (empty($failur)) {
				$newnode = $result_xml->createElement('Result', 'OK');
				$attrib	 = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
			}
			else {
				$newnode = $result_xml->createElement('Result', 'FAILED');
				$attrib = $result_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$newnode->appendChild($attrib);
				$root->appendChild($newnode);
			}
		}		
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($result_xml->saveXML());
		
		return $response;
	}

	/**
	 * Parse a message content and replace the tokens, finally send the message to the "TO"
	 * 
	 * @param array $message
	 * @param \Doctrine\ORM\EntityManager $container
	 * @param array $mail_to
	 * @return boolean|void
	 */
	static public function parseMessageAndSend($message, $container, $mail_to)
	{
		$em = $container->get('doctrine')->getManager();
		$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$global_settings = $global_settings[0];
		$principals = array();
		if ($global_settings->getDefaultPrincipal() && $global_settings->getDefaultPrincipal()->count())
		{
			foreach ($global_settings->getDefaultPrincipal() as $pr) {
				$principals[$pr->getUserMail()] = $pr->getUserNameDnAbbreviated();
			}
		}
		unset($em);
		$fromList = array();
		$senders = (!empty($message['senders'])) ? $message['senders'] : array();
		if (!empty($message['groups']) && count($message['groups']) > 0)
		{
			foreach ($message['groups'] as $g) {
				$type = false === strpos($g, '/DOCOVA') ? true : false;
				$groupname = str_replace('/DOCOVA', '', $g);
				$group = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Display_Name' => $groupname, 'Group_Type' => $type));
				if (!empty($group) && $group->getRoleUsers()->count() > 0)
				{
					foreach ($group->getRoleUsers() as $user) {
						$senders[] = $user;
					}
				}
			}
			unset($em, $group, $groupname, $g, $user);
		}

		if (!empty($senders))
		{
			foreach ($senders as $index => $from)
			{
				if ($from instanceof \Docova\DocovaBundle\Entity\UserAccounts && trim($from->getUserMail())) {
					$fromList[$from->getUserMail()] = $from->getUserNameDnAbbreviated();
				}
				elseif (!($from instanceof \Docova\DocovaBundle\Entity\UserAccounts) && !empty($from)) {
					$fromList[$index] = $from;
				}
			}
		}
		
		try {
			$mail_message = new \Swift_Message();
			$mail_message->setSubject($message['subject']);
			//$mail_message->setSender($container->getParameter('sender_address'));
			$mail_message->setBody(nl2br($message['content']), 'text/html', 'UTF-8');
			if (count($fromList) > 0) {
				$mail_message->setFrom($fromList);
			}
			elseif (!empty($principals)) {
				$mail_message->setFrom($principals);
			}
			else {
				$mail_message->setFrom(array($container->getParameter('sender_address') => 'DOCOVA Administrator'));
			}
			for ($x = 0; $x < count($mail_to); $x++)
			{
				if (!empty($mail_to[$x])) {
					$mail_message->addTo($mail_to[$x]);
				}
			}
				
			$mailer = $container->get('mailer');
			$mailer->send($mail_message, $failur);
			
			$spool = $mailer->getTransport()->getSpool();
			$transport = $container->get('swiftmailer.transport.real');
			$spool->flushQueue($transport);
		}
		catch (\Exception $e) {
			$failur = 'ERROR: Sending mail with subject "'. $message['subject'].'" failed with the message "'. $e->getMessage().'" on line '. $e->getLine() . ' in file '. $e->getFile() . ' STACK = '. $e->getTraceAsString();
		}
		
		if (empty($failur)) {
			return true;
		}
		else {
			$failur = is_array($failur) ? implode(' , ', $failur) : $failur;
			$logger = $container->get('logger');
			$logger->error($failur);
			$logger = $failur = null;
		}
		return false;
	}
	
	/**
	 * Parse each token and replace with proper values
	 * 
	 * @param object $container
	 * @param string $content
	 * @param \Docova\DocovaBundle\Entity\Documents|array $documents
	 * @param array $extra_inputs
	 * @param object $step
	 * @return string|NULL
	 */
	static public function parseTokens($container, $content, $linkType = true, $documents = null, $extra_inputs = array(), $step = null)
	{
		if (!empty($content)) 
		{
			if (!empty($documents)) 
			{
				if (!is_array($documents)) {
					$documents = array($documents);
				}
				$document = $documents[0];
			}

			if (!empty($document) && preg_match_all("/<\?(.*?)\?>/", $content, $matches)) 
			{
				for ($x = 0; $x < count($matches[1]); $x++) {
					$value = '';
					$script = html_entity_decode(trim($matches[1][$x]));
					eval($script);
					$content = str_replace('<?'.$matches[1][$x].'?>', $value, $content);
				}
			}
			$matches = null;
			$app = $document->getAppForm() && $document->getApplication() ? $document->getApplication() : false;

			if (preg_match_all("/\[([^\]]*)\]/", $content, $matches))
			{
				for ($x = 0; $x < count($matches[1]); $x++) {
					switch ($matches[1][$x])
					{
						case 'wfTitle':
							$content = str_replace('[wfTitle]', $step->getStepName(), $content);
							break;
						case 'LibraryName':
							$content = str_replace('[LibraryName]', ($app !== false ? $app->getLibraryTitle() : $document->getFolder()->getLibrary()->getLibraryTitle()), $content);
							break;
						case 'FolderName':
							$content = str_replace('[FolderName]', ($app === false ? $document->getFolder()->getFolderName() : ''), $content);
							break;
						case 'FolderPath':
							$content = str_replace('[FolderPath]', ($app === false ? $document->getFolder()->getFolderPath() : ''), $content);
							break;
						case 'applicationname':
							if ($app === false)
							{
								$em = $container->get('doctrine')->getManager();
								$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
								$global_settings = $global_settings[0];
								$content = str_replace('[applicationname]', $global_settings->getApplicationTitle(), $content);
								$em = $global_settings = null;
							}
							else {
								$content = str_replace('[applicationname]', $app->getLibraryTitle(), $content);
							}
							break;
						case 'dueDate':
							if ($app === false)
							{
								$em = $container->get('doctrine')->getManager();
								$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
								$global_settings = $global_settings[0];
								$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
								unset($em, $global_settings);
								try{
									if (!$document->getNextReviewDate() || !is_object($document->getNextReviewDate()))
										$nextReview = "Unknown";
									else
										$nextReview = $document->getNextReviewDate()->format($defaultDateFormat);
									$due = ((!empty($extra_inputs['dueDate']) && is_object($extra_inputs['dueDate']) ? $extra_inputs['dueDate']->format($defaultDateFormat) : "Unknown"));
									$content = str_replace('[dueDate]', (!empty($extra_inputs['dueDate']) ? $due : $nextReview), $content);
								}
								catch (\Exception $e){
									
									$content = str_replace('[dueDate]','Unknown', $content);
								}
							}
							else {
								$content = str_replace('[dueDate]','Unknown', $content);
							}
							break;
						case 'varLink':
						case 'docLink':
						case 'doclinks':
							if (!empty($documents)) {
								$link = '';
								foreach ($documents as $doc) {
									if ($app === false)
									{
										if ($linkType === true) {
											$link .= '<a href="'.$container->get('router')->generate('docova_readdocument', array('doc_id' => $doc->getId()), UrlGeneratorInterface::ABSOLUTE_URL).'?ParentUNID='.$doc->getFolder()->getId().'&mode=window" alt="'.$doc->getDocTitle().'">'.$doc->getDocTitle().'</a><br />';
										}
										else {
											$link .= '<a href="'.$container->get('router')->generate('docova_homeframe', array(), UrlGeneratorInterface::ABSOLUTE_URL).'?goto='.$doc->getFolder()->getLibrary()->getId().','.$doc->getFolder()->getId().','.$doc->getId().'" alt="'.$doc->getDocTitle().'">'.$doc->getDocTitle().'</a><br />';
										}
									}
									else {
										$docova = new Docova($container);
										$appobj = $docova->DocovaApplication(['appID' => $app->getId()], $app);
										$apidocobj = $docova->DocovaDocument($appobj, $doc->getId(), '', $doc);
										$docurl = $apidocobj->getURL(['fullurl'=>true, 'mode' => ($linkType === true ? 'window' : '')]);
										$link .= '<a href="'.$docurl.'" alt="Document in '.$app->getLibraryTitle().' application">Document in '. $app->getLibraryTitle() .' application</a><br />';
										$docova = $appobj = $apidocobj = null;
									}
								}
								$content = str_replace(array('[varLink]', '[docLink]', '[doclinks]'), $link, $content);
							}
							break;
						case 'Subject':
							$content = !empty($document) ? str_replace('[Subject]', ($app === false ? $document->getDocTitle() : ''), $content) : $content;
							break;
						case 'usercomment':
							$comment = key_exists('usercomment', $extra_inputs) ? $extra_inputs['usercomment'] : '';
							$content = str_replace('[usercomment]', "<b>$comment</b>", $content);
							break;
						case 'DocovaLink':
							$em = $container->get('doctrine')->getManager();
							$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
							$global_settings = $global_settings[0];
							$link = '<a href="'.$container->get('router')->generate('docova_homepage', array(), UrlGeneratorInterface::ABSOLUTE_URL).'" alt="'.$global_settings->getApplicationTitle().'">'.$global_settings->getApplicationTitle().'</a>';
							$content = str_replace('[DocovaLink]', $link, $content);
							unset($em, $global_settings, $link);
							break;
						case 'DocovaUsername':
							$username = key_exists('DocovaUsername', $extra_inputs) ? $extra_inputs['DocovaUsername'] : '';
							$content = str_replace('[DocovaUsername]', $username, $content);
							break;
						case 'DocovaPassword':
							$username = key_exists('DocovaPassword', $extra_inputs) ? $extra_inputs['DocovaPassword'] : '';
							$content = str_replace('[DocovaPassword]', $username, $content);
							break;
						default:
							if (!empty($document) && false !== strpos($matches[1][$x], 'PreviousComments'))
							{
								$em = $container->get('doctrine')->getManager();
								$comments_obj = $em->getRepository('DocovaBundle:DocumentComments')->getDocumentComments($document->getId());
								$wf_comments = $em->getRepository('DocovaBundle:DocumentsLog')->getDocWorkflowComments($document->getId());
								if (!empty($wf_comments))
									$comments_obj = array_merge($comments_obj, $wf_comments);
								$comments = '';
								if (!empty($comments_obj) || !empty($extra_inputs['usercomment'])) 
								{
									$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
									$global_settings = $global_settings[0];
									$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
									if (false !== strpos($matches[1][$x], 'html'))
									{
										$comments = '<table style="width:100%; table-layout:fixed;" cellspacing="0" cellpadding="5" border="0"><tr style="background:#909090;color:#FFF;">';
										$comments .= '<th>Created By</th><th>Date</th><th>Comments</th></tr>';
										$c = 0;
										if (!empty($comments_obj))
										{
											foreach ($comments_obj as $cm) {
												$isComment = $cm instanceof \Docova\DocovaBundle\Entity\DocumentComments ? true : false;
												$comments .= '<tr style="background:#'.($c % 2 == 0 ? 'EBF4FA' : 'FFFFFF').'"><td>';
												$comments .= ($isComment === true ? $cm->getCreatedBy()->getUserProfile()->getDisplayName() : $cm->getLogAuthor()->getUserProfile()->getDisplayName()).'</td>';
												$comments .= '<td>'. ($isComment === true ? $cm->getDateCreated()->format($defaultDateFormat) : $cm->getLogDate()->format($defaultDateFormat)) .'</td>';
												$comments .= '<td>'. ($isComment === true ? nl2br($cm->getComment()) : nl2br(ltrim(strstr($cm->getLogDetails(), 'Comments:'), 'Comments:'))) .'</td>';
												$comments .= '</tr>';
												$c++;
											}
										}
										if (!empty($extra_inputs['usercomment'])) {
											$comments .= '<tr><td>'. $document->getModifier()->getUserProfile()->getDisplayName().'</td>';
											$comments .= '<td>'. date($defaultDateFormat).'</td>';
											$comments .= '<td>'. $extra_inputs['usercomment'] .'</td></tr>';
										}
										$comments .= '</table>';
										$content = str_replace('['.$matches[1][$x].']', $comments, $content);
									}
									else {
										$comments = '';
										if (!empty($comments_obj))
										{
											foreach ($comments_obj as $cm) {
												if ($cm instanceof \Docova\DocovaBundle\Entity\DocumentComments)
												{
													$comments .= $cm->getCreatedBy()->getUserProfile()->getDisplayName().' &ensp;&ensp;';
													$comments .= $cm->getDateCreated()->format($defaultDateFormat) .' &ensp;&ensp;';
													$comments .= $cm->getComment() .' <br>';
												}
												else {
													$comments .= $cm->getLogAuthor()->getUserProfile()->getDisplayName().' &ensp;&ensp;';
													$comments .= $cm->getLogDate()->format($defaultDateFormat) .' &ensp;&ensp;';
													$comments .= ltrim(strstr($cm->getLogDetails(), 'Comments:'), 'Comments:') .' <br>';
												}
											}
										}
										if (!empty($extra_inputs['usercomment'])) {
											$comments .= $document->getModifier()->getUserProfile()->getDisplayName().' &ensp;&ensp;';
											$comments .= date($defaultDateFormat).' &ensp;&ensp;';
											$comments .= $extra_inputs['usercomment'].'<br>';
										}
										$content = str_replace('['.$matches[1][$x].']', $comments, $content);
									}
								}
							}
							if (!empty($document))
							{
								if (method_exists($document, 'get'.$matches[1][$x])) {
									$replace = call_user_func(array($document, 'get'.$matches[1][$x]));
									$content = str_replace('['.$matches[1][$x].']', $replace, $content);
								}
								else {
									$docova = $container->get('docova.objectmodel');
									if ($app !== false)
										$docova->setDocument($document);
									else 
										$docova->setDocument($document, $document->getFolder()->getLibrary());
									$replace = $docova->getFieldValue($matches[1][$x]);
									$content = str_replace('['.$matches[1][$x].']', $replace, $content);
								}
							}
							break;
					}
				}
			}
			
			return $content;
		}
		return null;
	}
	
	public function getDocumentBodyAction($document)
	{
		$body = '';
		$response = new Response();
		$em = $this->getDoctrine()->getManager();
		$result = $em->getRepository('DocovaBundle:FormTextValues')->getDocumentFieldsValue($document, array('editor_body', 'body', 'terbody'));
		if (!empty($result)) 
		{
			foreach ($result as $content) {
				$body .= $content['fieldValue'];
			}
		}
		$response->setContent(html_entity_decode(nl2br($body)));
		return $response; 
	}
	
	/**
	 * Generate XML nodes for subfolders
	 * 
	 * @param array $folders
	 * @param string $parent
	 * @return string
	 */
	private function generateSubfolderNodes($folders, $parent)
	{
		$xml = '';
		foreach ($folders as $key => $value) {
			$folderPath = $parent.'\\'.$key;
			$xml .= '<viewentry><entrydata columnnumber="0"><text>'.$folderPath.'</text></entrydata>';
			$xml .= '<entrydata columnnumber="1"></entrydata>';
			$xml .= '<entrydata columnnumber="2"><text>0</text></entrydata></viewentry>';
			if (is_array($value)) {
				$xml .= $this->generateSubfolderNodes($value, $folderPath);
			}
		}
		return $xml;
	}


	
	
	/**
	 * Fetch group members
	 *
	 * @param string $groupname
	 * @return \Doctrine\Common\Collections\ArrayCollection|boolean
	 */
	private function fetchGroupMembers($groupname)
	{
		$em = $this->getDoctrine()->getManager();
		$docovaonly = (false === strpos($groupname, '/DOCOVA') ? false : true);
		$name = str_replace('/DOCOVA', '', $groupname);
		$role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $name, 'groupType' => false));
		if (!empty($role)) {
			return $role->getRoleUsers();
		}
		if(!$docovaonly){
			$role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $name, 'groupType' => true));
			if (!empty($role)) {
				return $role->getRoleUsers();
			}				
		}
		
		return false;
	}	
	
	
	/**
	 * Find a user info in Database, if does not exist a user object can be created according to its info through LDAP server
	 *  
	 * @param string $username
	 * @param boolean $create
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUserAndCreate($username, $create = true)
	{
		if (empty($this->user)) {
			$this->initialize();
		}
		$em = $this->getDoctrine()->getManager();
		$utilHelper= new UtilityHelperTeitr($this->global_settings,$this->container);
		return $utilHelper->findUserAndCreate($username, $create,$em);
	}

	/**
	 * Move uploaded files to specific folder with created names
	 * 
	 * @param \Symfony\Component\HttpFoundation\FileBag $upload_file_obj
	 * @param array $file_names
	 * @param array $file_dates
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\DesignElements $field
	 */
	private function moveUploadedFiles($upload_file_obj, $file_names, $file_dates, $document, $field)
	{

		if (empty($upload_file_obj)) {
			return false;
		}
		
		$error = $added = $edited = array();
		$uploaded = 0;
		$files_list = $upload_file_obj->get('Uploader_DLI_Tools');
		$em = $this->getDoctrine()->getManager();
		
		foreach ($files_list as $file) {
			$file_name	= html_entity_decode($file->getClientOriginalName());
			if (false !== ($found = array_search($file_name, $file_names))) {
				if (!is_dir($this->file_path.DIRECTORY_SEPARATOR.$document->getId())) {
					mkdir($this->file_path.DIRECTORY_SEPARATOR.$document->getId(), 0666);
				}

				@chmod($this->file_path.DIRECTORY_SEPARATOR.$document->getId(), '0777');
				$res = $file->move($this->file_path.DIRECTORY_SEPARATOR.$document->getId(), md5(basename($file_name)));
				@chmod($this->file_path.DIRECTORY_SEPARATOR.$document->getId(), '0666');
				if (!empty($res)) {
					$temp = new AttachmentsDetails();
					$temp->setDocument($document);
					$temp->setField($field);
					$temp->setFileName(basename($file_name));
					$temp->setFileDate($file_dates[$found]);
					$temp->setFileMimeType($res->getMimeType());
					$temp->setFileSize($file->getClientSize());
					$temp->setAuthor($this->user);
					
					$em->persist($temp);
					$em->flush();
					$uploaded++;
					$added[] = $file_name;
				}
				else {
					$error[] = 'Could not upload file "'.$file_name.'"';
				}
				unset($temp);
			}
		}
		
		if ($uploaded === 0) {
			$error[] = 'No file was uploaded.';
		}
		else {
			$log_obj = new Miscellaneous($this->container);
			if (!empty($added)) {
				$log_obj->createDocumentLog($em, 'UPDATE', $document, 'Added file(s): '.implode(',', $added));
			}
			if (!empty($edited)) {
				$log_obj->createDocumentLog($em, 'UPDATE', $document, 'Edited file(s): '.implode(',', $edited));
			}
		}
		unset($em);
		
		if (!empty($error)) {
			return $error;
		}
		return true;
	}
	
	/**
	 * Get mail-box folders
	 * 
	 * @return array
	 */
	private function getMailFolders()
	{
		$folders = array();
		$session_obj = $this->get('session');
		$domain = $this->user->getUserProfile()->getMailServerURL();
		$mailbox = imap_open('{'.$domain.'/novalidate-cert}', $this->user->getUserMail(), base64_decode($session_obj->get('epass')));
		$list = imap_list($mailbox, '{'.$domain.'}', '*');
		if (!empty($list))
		{
			foreach ($list as $key => $value)
			{
				if (strtolower($value) == '{'.$domain.'}inbox')
				{
					unset($list[$key]);
					continue;
				}
				$list[$key] = str_replace('{'.$domain.'}', '', $value);
			}
				
			if (!empty($list))
			{
				foreach ($list as $value)
				{
					$tmp = &$folders;
					$xpath = explode('\\', $value);
					$key = array_pop($xpath);
					foreach ($xpath as $index) {
						if (!array_key_exists($index, $tmp) || !is_array($tmp[$index]))
						{
							$tmp[$index] = array();
						}
						$tmp = &$tmp[$index];
					}
					$tmp[$key] = '';
				}
			}
		}
		imap_close($mailbox);

		return $folders;
	}
}
