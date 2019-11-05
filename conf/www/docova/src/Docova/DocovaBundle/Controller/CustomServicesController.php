<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Custom Services class with one action is used to load some php scripts
 * which are saved in php files outside the bundle. Each file should produce
 * and return the proper response which is needed (can be a rendred twig
 * template or XML, json and etc responses)
 * @author javad rahimi
 *        
 */
class CustomServicesController extends Controller 
{
	protected $filepath;
	protected $user;
	protected $docova;
	protected $global_settings;
	
	/**
	 * Set the script file path
	 */
	private function setFilePath()
	{
		$this->filepath = $_SERVER['DOCUMENT_ROOT'];
		$this->filepath .= DIRECTORY_SEPARATOR . $this->get('assets.packages')->getUrl('upload/scripts/');
	}
	
	/**
	 * Initializing properties to get current user and global settings objects
	 */
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
	
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
		
		$this->docova = new Docova($this->container);
		global $docova;
		$docova = $this->docova;
	}
	
	/**
	 * Action to load a php script file
	 * 
	 * @param string $scriptfilename
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function loadScriptAction($scriptfilename)
	{
		$response = new Response();
		$this->setFilePath();
		if (true == file_exists($this->filepath . $scriptfilename . '.php' )) 
		{
			$this->initialize();
			include_once ($this->filepath . $scriptfilename . '.php');
		}
		else {
			$response->headers->set('Content-Type', 'text/xml');
			$xml = new \DOMDocument('1.0', 'UTF-8');
			$root = $xml->appendChild($xml->createElement('Report'));
			$attr = $xml->createAttribute('No');
			$attr->value = '404';
			$newnode = $xml->createElement('Error', 'Page Not Found');
			$newnode->appendChild($attr);
			$root->appendChild($newnode);
			$newnode = $xml->createElement('Message');
			$cdata = $xml->createCDATASection('Oops! The page you requested could not be found.');
			$newnode->appendChild($cdata);
			$root->appendChild($newnode);
			
			$response->setContent($xml->saveXML());
		}
		
		return $response;
	}
}