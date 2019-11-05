<?php
namespace Docova\DocovaBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
//use Docova\DocovaBundle\Entity\UserAccounts;
//use Docova\DocovaBundle\Entity\UserProfile;

class MobileController extends Controller
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
	
	// docova lite/ mobile browser starter page
	public function mobileAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Mobile:mobile.html.twig', array(
		    'user' => $this->user,
		    'settings' => $this->global_settings		    
		));
	}
	
	// starter page for mobile apps ( iOS & Android)
	public function mobileAppAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Mobile:mobileApp.html.twig', array(
		    'user' => $this->user,
		    'settings' => $this->global_settings
		));
	}
	
	// MobileDataServices
	
	public function mobileDataServicesAction(Request $request)
	{
		$this->initialize();
		$view = $request->query->get('view');
		$em = $this->getDoctrine()->getManager();
		
		$responseXml=new Response();
		$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
		
		//create root document
		$xml_data ='<?xml version="1.0" encoding="UTF-8"?>'; 
		$xml_data .="<Documents>";
	
		// get workflow pending documents
		$pending_workflow_docs = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findStepsBy(array('A.assignee' => $this->user->getId(), 'DWS.Status' => 'Pending', 'DWS.Date_Started!' => ''), array('DWS.Position' => 'ASC'));
		foreach ($pending_workflow_docs as $curWorflowDoc)
		{
			$curWorflowDoc instanceof \Docova\DocovaBundle\Entity\DocumentWorkflowSteps;
			
			$title=$curWorflowDoc->getDocument()->getDocTitle();
			$docKey=$curWorflowDoc->getDocument()->getId();
			$libName=$curWorflowDoc->getDocument()->getFolder()->getLibrary()->getLibraryTitle();
			$wfStartDate=$curWorflowDoc->getDateStarted()->format($defaultDateFormat);
			$due_days = 0;
			if ($curWorflowDoc->getActions()->count() > 0) {
				foreach ($curWorflowDoc->getActions() as $action) {
					if ($action->getActionType() == 6) {
						$due_days = $action->getDueDays();
						break;
					}
				}
			}
			$wfDueDate = (!empty($due_days)) ? $curWorflowDoc->getDateStarted()->modify("+$due_days day")->format($defaultDateFormat) : '';
				
			$wfOriginator=$curWorflowDoc->getDocument()->getCreator()->getUserNameDnAbbreviated();
			$folderName=$curWorflowDoc->getDocument()->getFolder()->getFolderName();;
			$libKey=$curWorflowDoc->getDocument()->getFolder()->getLibrary()->getId();
			
			$xml_data .= '<Document>';
				$xml_data .= '<Title><![CDATA['.$title.']]></Title>';
				$xml_data .= '<DocKey>'.$docKey.'</DocKey>';
				$xml_data .= '<LibraryPath>N/A</LibraryPath>';
				$xml_data .= '<LibraryName><![CDATA['.$libName.']]></LibraryName>';
				$xml_data .= '<wfStartDate>'.$wfStartDate.'</wfStartDate>';
				$xml_data .= '<wfDueDate>'.$wfDueDate.'</wfDueDate>';
				$xml_data .= '<wfOriginator><![CDATA['.$wfOriginator.']]></wfOriginator>';
				$xml_data .= '<FolderName><![CDATA['.$folderName.']]></FolderName>';
				$xml_data .= '<LibraryKey>'.$libKey.'</LibraryKey>';
			$xml_data .= '</Document>';
		}
		//create Docoument
		//foreach document create doc
		$xml_data .= '</Documents>';
		$responseXml->headers->set("content-type", "text/xml");
		$responseXml->setContent($xml_data);
		return $responseXml;
	}
	
	public function mobileWorkflowScreenRedirectAction(){
		//get device parameter to pass to twig e.g iOS and Android
		return $this->render('DocovaBundle:Mobile:mobileWorkflowScreenRedirect.html.twig');
	
	}

	/**
	* For android login/ios login
	*/
	public function loginXmlAction()
	{
		$this->initialize();
		$xml_data ='<?xml version="1.0" encoding="UTF-8"?>';
		$xml_data .= '<login>';
		$xml_data .= 	'<status>OK</status>';
		$xml_data .= 	'<UserName><![CDATA['.$this->user->getUsername().']]></UserName>';
		$xml_data .= '</login>';
	
		$responseXml=new Response();
		$responseXml->headers->set("content-type", "text/xml");
		$responseXml->setContent($xml_data);
		return $responseXml;
	}
}