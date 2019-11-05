<?php

namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Docova\DocovaBundle\Entity\TempEditAttachment;

/**
 * @author javad rahimi
 */
class DocumentServicesController extends Controller 
{
	protected $user;
	protected $global_settings;

	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
	
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
	}
	
	public function documentServicesExtAction(Request $request)
	{
		$post_req = $request->getContent();
		if (empty($post_req)) {
			throw $this->createNotFoundException('Nothing was submitted.');
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$post_xml = new \DOMDocument();
		$post_xml->loadXML($post_req);
		$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
		if (empty($action)) {
			throw $this->createNotFoundException('No ACTION was found in POST request');
		}
		
		$this->initialize();
		
		if (method_exists($this, 'service'.$action))
		{
			$result_xml = call_user_func(array($this, 'service'.$action), $post_xml);
		}
		
		return $response->setContent($result_xml->saveXML());
	}
	
	public function openProgressBarAction()
	{
		return $this->render('DocovaBundle:Default:dlgSubmitProgress.html.twig');
	}
	
	/**
	 * Remove logs on edit for a document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceLOGFILEEDITEDDELETE($post_xml)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$em = $this->getDoctrine()->getManager();
		$root = $xml->appendChild($xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('DocKey')->item(0)->nodeValue;
		try {
			$tempLogs = $em->getRepository('DocovaBundle:TempEditAttachment')->findBy(array('document' => $document, 'trackUser' => $this->user->getId()));
			if (!empty($tempLogs)) 
			{
				foreach ($tempLogs as $log) {
					$em->remove($log);
				}
				$em->flush();
			}
			
			$node = $xml->createElement('Result', 'OK');
			$attr = $xml->createAttribute('ID');
			$attr->value = 'Status';
			$node->appendChild($attr);
			$root->appendChild($node);
		}
		catch (\Exception $e) {
			$this->generateErrorLog($xml, $root, $e);
		}
		
		return $xml;
	}
	
	/**
	 * Log a document file edited
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceLOGFILEEDITED($post_xml)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$em = $this->getDoctrine()->getManager();
		$root = $xml->appendChild($xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('DocKey')->item(0)->nodeValue;
		try {
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $document, 'Trash' => false, 'Archived' => false));
			$filename = $post_xml->getElementsByTagName('FileName')->item(0)->nodeValue;
			if (empty($document))
			{
				throw new \Exception('Unspecified Document source.');
			}
			
			$tempEditAttachment = new TempEditAttachment();
			$tempEditAttachment->setDocument($document->getId());
			$tempEditAttachment->setFileName($filename);
			$tempEditAttachment->setFilePath($post_xml->getElementsByTagName('Path')->item(0)->nodeValue);
			$tempEditAttachment->setTrackEdit(true);
			$tempEditAttachment->setTrackUser($this->user);
			$em->persist($tempEditAttachment);
			$em->flush();

			$node = $xml->createElement('Result', 'OK');
			$attr = $xml->createAttribute('ID');
			$attr->value = 'Status';
			$node->appendChild($attr);
			$root->appendChild($node);
		}
		catch (\Exception $e) {
			$this->generateErrorLog($xml, $root, $e);
		}
		
		return $xml;
	}
	
	/**
	 * Create log for viewed file in a document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceLOGVIEWED($post_xml)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$em = $this->getDoctrine()->getManager();
		$root = $xml->appendChild($xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$filename = $post_xml->getElementsByTagName('FileName')->item(0)->nodeValue;
		try {
			$attachment = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document' => $document, 'File_Name' => $filename));
			if (empty($document)) 
			{
				throw new \Exception('Unspecified Document source.');
			}
			
			$docLog = new Miscellaneous($this->container);
			$docLog->createDocumentLog($em, 'UPDATE', $attachment->getDocument(), 'Viewed the following file(s): ' . $filename);
			unset($docLog);

			$node = $xml->createElement('Result', 'OK');
			$attr = $xml->createAttribute('ID');
			$attr->value = 'Status';
			$node->appendChild($attr);
			$root->appendChild($node);
		} 
		catch (\Exception $e) {
			$this->generateErrorLog($xml, $root, $e);
		}
		
		return $xml;
	}
	
	/**
	 * Create log for downloaded file in a document
	 * 
	 * @param \DOMDocument $post_xml
	 * @return \DOMDocument
	 */
	private function serviceLOGDOWNLOADED($post_xml)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$em = $this->getDoctrine()->getManager();
		$root = $xml->appendChild($xml->createElement('Results'));
		$document = $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue;
		$filename = $post_xml->getElementsByTagName('FileName')->item(0)->nodeValue;
		try {
			$attachment = $em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('Document' => $document, 'File_Name' => $filename));
			if (empty($document))
			{
				throw new \Exception('Unspecified Document source.');
			}
				
			$docLog = new Miscellaneous($this->container);
			$docLog->createDocumentLog($em, 'UPDATE', $attachment->getDocument(), 'Downloaded the following file(s): ' . $filename);
			unset($docLog);
		
			$node = $xml->createElement('Result', 'OK');
			$attr = $xml->createAttribute('ID');
			$attr->value = 'Status';
			$node->appendChild($attr);
			$root->appendChild($node);
		}
		catch (\Exception $e) {
			$this->generateErrorLog($xml, $root, $e);
		}
		
		return $xml;
	}
	
	/**
	 * Logs the issue and generates XML error log
	 * 
	 * @param \DOMDocument $xml
	 * @param \Exception $e
	 */
	private function generateErrorLog(&$xml, $root, $e)
	{
		$logger = $this->get('logger');
		$logger->error('On File: ' . $e->getFile() . ' Line: '. $e->getLine(). ' - ' . $e->getMessage() . "\n");
		unset($logger);
			
		$attr = $xml->createAttribute('ID');
		$attr->value = 'Status';
		$node = $xml->createElement('Result', 'FAILED');
		$node->appendChild($attr);
		$root->appendChild($node);
		
		$attr = $xml->createAttribute('ID');
		$attr->value = 'ErrMsg';
		$node = $xml->createElement('Result', 'Operation could not be completed due to a system error. Details of the error have been logged. Please contact your system administrator for help with this issue.');
		$node->appendChild($attr);
		$root->appendChild($node);
	}
}