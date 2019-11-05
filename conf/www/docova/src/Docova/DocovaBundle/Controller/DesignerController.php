<?php

namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Docova\DocovaBundle\Entity\Subforms;
use Docova\DocovaBundle\Entity\ListFieldOptions;
use Docova\DocovaBundle\Entity\AppForms;
use Docova\DocovaBundle\Entity\AppPages;
use Docova\DocovaBundle\Entity\AppOutlines;
use Docova\DocovaBundle\Entity\AppJavaScripts;
use Docova\DocovaBundle\Entity\AppAgents;
use Docova\DocovaBundle\Entity\AppLayout;
use Docova\DocovaBundle\Entity\AppCss;
use Docova\DocovaBundle\Entity\AppFiles;
use Docova\DocovaBundle\Entity\AppPhpScripts;
use Docova\DocovaBundle\Entity\AppFormProperties;
use Docova\DocovaBundle\Entity\DesignElements;
use Docova\DocovaBundle\Entity\AppFormAttProperties;
use Docova\DocovaBundle\Extensions\DocovaDominoSession;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Docova\DocovaBundle\Entity\Widgets;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Docova\DocovaBundle\Entity\AppViews;
use Docova\DocovaBundle\ObjectModel\Docova;

class DesignerController extends Controller 
{
	const TEXT = 0;
	const DATE_TYPE = 1;
	const OPTIONS = 3;
	protected $user;
	protected $appPath;
	protected $assetPath;
//	protected $global_settings;
	
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();		
		$this->appPath = $this->get('kernel')->getRootDir() . '/../src/Docova/DocovaBundle/Resources/views/DesignElements/';
		$this->assetPath = $this->get('kernel')->getRootDir() . '/../web/bundles/docova/';
		
//		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->find(1);
	}
	
	public function designServicesAction(Request $request)
	{
		
		$post = new \DOMDocument();
		$post->loadXML(str_replace('&amp;nbsp;', ' ', urldecode($request->getContent())));
		$action = $post->getElementsByTagName('Action')->item(0)->nodeValue;
		$results = false;
		if (method_exists($this, "dservice$action")) 
		{
			$this->initialize();
			$results = call_user_func(array($this, "dservice$action"), $post, $request);
		}
		
		if (empty($results) || !$results instanceof \DOMDocument)
		{
			$status = $results;
			$results = new \DOMDocument('1.0', 'UTF-8');
			$root = $results->appendChild($results->createElement('Results'));
			$newnode = $results->createElement('Result', 'FAILED');
			$attrib = $results->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			if (is_string($status))
			{
				$newnode = $results->createElement('Result', $status);
			}
			else {
				$newnode = $results->createElement('Result', 'Operation could not be completed due to a system error. Check the logs for more details.');
			}
			$attrib = $results->createAttribute('ID');
			$attrib->value = 'ErrMsg';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			opcache_reset();
		}
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($results->saveXML());
		return $response;
	}
	
	public function openDesignerAction()
	{
		return $this->render('DocovaBundle:Designer:DesignerContainer.html.twig');
	}
	
	public function designerLeftNavAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Designer:wDesignerMenu.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function designerInitAction()
	{
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$subforms = $em->getRepository('DocovaBundle:Subforms')->findBy(array('Is_Custom' => true,'Trash' => false, 'application' => null),array('Form_Name'=>'asc'));
		$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$global_settings = $global_settings[0];
		$defaultDateFormat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
		unset($global_settings);
		return $this->render('DocovaBundle:Designer:wDesignerInit.html.twig', array(
			'user' => $this->user,
			'date_format' => $defaultDateFormat,
			'subforms_list' => $subforms
		));
	}
	
	public function subformDesignerAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Designer:wDesignerSubform.html.twig', array(
			'user' => $this->user
		));
	}
	
	public function editSubformAction(Request $request)
	{
		$subform_id = $request->query->get('subformid');
		if (empty($subform_id))
		{
			throw $this->createNotFoundException('Subform ID is missed.');
		}

		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$subform = $em->getRepository('DocovaBundle:Subforms')->find($subform_id);
		if (empty($subform)) 
		{
			throw $this->createNotFoundException('Subform not found, source ID = '. $subform_id);
		}
		
		$subform_html = $subform_js = '';
		if (file_exists($this->get('kernel')->getRootDir().'/../web/upload/templates/'.$subform->getFormFileName().'.htm')) {
			$subform_html = file_get_contents($this->get('kernel')->getRootDir().'/../web/upload/templates/'.$subform->getFormFileName().'.htm');
		}
		if (file_exists($this->get('kernel')->getRootDir().'/../web/upload/js/'.$subform->getFormFileName().'.js')) {
			$subform_js = file_get_contents($this->get('kernel')->getRootDir().'/../web/upload/js/'.$subform->getFormFileName().'.js');
		}
		
		return $this->render('DocovaBundle:Designer:wDesignerSubform.html.twig', array(
			'user' => $this->user,
			'subform_html' => $subform_html,
			'subform_js' => $subform_js,
			'subform' => $subform
		));
	}
	
	public function openAceEditorAction($mode)
	{
		return $this->render('DocovaBundle:Designer:aceEditor.html.twig');
	}
	
	public function saveDesignedSubformAction(Request $request)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'json/application');

		$output = array();
		if ($request->isMethod('POST')) 
		{
			$req = $request->request;
			$form_name	= urldecode($req->get('form_name'));
			$form_alias	= urldecode($req->get('form_alias'));
			$add_fileds = explode(',', urldecode($req->get('extra_fields')));
			$addfield_types = explode(',', urldecode($req->get('field_types')));
			$form_html	= urldecode($req->get('subform_html'));
			$form_js	= urldecode($req->get('subform_js'));
			if (empty($form_name) || empty($form_html)) {
				$output['error'][] = '"Form Name" and/or "Subform Designed HTML" are missed';
			}
			else {
				$direction = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'Subform'.DIRECTORY_SEPARATOR;
				$twig_edit_filename = 'sfCustomSection-'.$form_name.'.html.twig';
				$twig_read_filename = 'sfCustomSection-'.$form_name.'-read.html.twig';
				if (!file_exists($direction.$twig_edit_filename) && !file_exists($direction.$twig_read_filename)) 
				{
					$filters = array("/\n\r/", "/\n/", '/&nbsp;/', '/\s*ui-resizable[\w|\-]*\s*/', '/\s*ui-droppable[\w|\-]*\s*/', '/\s*ui-state[\w|\-]*\s*/', '/type=submit/', '/\s*aria-disabled=[\w|"]*\s*/');
					$form_html = preg_replace('/\s+/', ' ', $form_html);
					$pure_html = preg_replace($filters, ' ', $form_html);
					$pure_html = preg_replace(array('/class=" "/', '/class=""/', '/class= /', '/style= /'), '', $pure_html);
					$pure_html = preg_replace('/style=>/', '>', $pure_html);
					$pure_html = preg_replace('/\s+/', ' ', $pure_html);
						
					$result = $this->generateEditReadTemplates($pure_html);
/*
 * FORNOW WE DON'T NEED TO SAVE LABELS AS SEPARATE ELEMENT IN DB; IN FUTURE IF LOCALIZATION IS IMPLEMENTED THIS MIGHT HELP THEN.					
					foreach ($dom_html->getElementsByTagName('label') as $item)
					{
						if ($item->hasAttribute('id'))
						{
							$index = $item->getAttribute('id');
							$elements[$index]['id'] = $index;
							$elements[$index]['type'] = 'label';
							$elements[$index]['value'] = $item->nodeValue;
							if ($item->hasAttribute('name')) {
								$elements[$index]['name'] = $item->getAttribute('name');
							}
							else {
								$elements[$index]['name'] = $index;
							}
							$item->nodeValue = "{{ data['{$elements[$index]['name']}']['Value'] }}";
						}
					}
*/					
					$this->initialize();
					$subform = new Subforms();
					$subform->setCreator($this->user);
					$subform->setModifiedBy($this->user);
					$subform->setDateCreated(new \DateTime());
					$subform->setFormFileName("sfCustomSection-$form_name");
					$subform->setFormName($form_alias);
					$subform->setIsCustom(true);
					$em = $this->getDoctrine()->getManager();
					$em->persist($subform);
					
					foreach ($result['elements'] as $value) 
					{
						$fname = strtolower($value['name']);
						$element = new DesignElements();
						$element->setModifiedBy($this->user);
						$element->setDateModified(new \DateTime());
						$element->setFieldName($fname);
						if (in_array($value['type'], array('text', 'textarea'))) {
							$element->setFieldType('text');
						}
						else {
							$element->setFieldType($value['type']);
						}
						$element->setSubform($subform);
						$em->persist($element);
						
						if (in_array($value['type'], array('checkbox', 'radio', 'select'))) 
						{
							foreach ($value['options'] as $option) {
								$field_options = new ListFieldOptions();
								$field_options->setDisplayOption($option['label']);
								$field_options->setOptValue($option['value']);
								$field_options->setOptionOrder($option['order']);
								$field_options->setField($element);
								$em->persist($field_options);
							}
						}
					}
					
					if (!empty($add_fileds) && count($add_fileds) > 0) 
					{
						for ($x = 0; $x < count($add_fileds); $x++) {
							if (!empty($add_fileds[$x])) 
							{
								$element = new DesignElements();
								$element->setModifiedBy($this->user);
								$element->setDateModified(new \DateTime());
								$element->setFieldName($add_fileds[$x]);
								$element->setFieldType($addfield_types[$x]);
								$element->setIsExtra(true);
								$element->setSubform($subform);
								$em->persist($element);
							}
						}
					}
					$em->flush();

					file_put_contents($direction.$twig_edit_filename, html_entity_decode($result['edit_html']));
					file_put_contents($direction.$twig_read_filename, html_entity_decode($result['read_html']));
//					file_put_contents($this->get('kernel')->getRootDir().'/../web/upload/templates/sfCustomSection-'.$form_name.'.htm', $dom_html->saveHTML());
					file_put_contents($this->get('kernel')->getRootDir().'/../web/upload/templates/sfCustomSection-'.$form_name.'.htm', $result['template']);
					if (trim($form_js)) {
						file_put_contents($this->get('kernel')->getRootDir().'/../web/upload/js/sfCustomSection-'.$form_name.'.js', $form_js);
					}
					$output[] = true;
				}
				else {
					$output['error'][] = 'Form name exists, please change the form name.';
				}
			}
		}

		$response->setContent(json_encode($output));
		return $response;
	}
	
	public function saveEditedSubformAction(Request $request, $subform)
	{
		$em = $this->getDoctrine()->getManager();
		$subform = $em->getRepository('DocovaBundle:Subforms')->find($subform);
		$output = array();
		if (!empty($subform)) 
		{
			if ($request->isMethod('POST'))
			{
				$req = $request->request;
				$form_name	= urldecode($req->get('form_name'));
				$form_alias	= urldecode($req->get('form_alias'));
				$add_fileds = explode(',', urldecode($req->get('extra_fields')));
				$addfield_types = explode(',', urldecode($req->get('field_types')));
				$form_html	= urldecode($req->get('subform_html'));
				$form_js	= urldecode($req->get('subform_js'));
				
				if (empty($form_name) || empty($form_html)) {
					$output['error'][] = '"Form Name" and/or "Subform Designed HTML" are missed';
				}
				else {
					$this->initialize();
					$direction = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'Subform'.DIRECTORY_SEPARATOR;
					$twig_edit_filename = 'sfCustomSection-'.$form_name.'.html.twig';
					$twig_read_filename = 'sfCustomSection-'.$form_name.'-read.html.twig';
					$file_name = $subform->getFormFileName();
					$filters = array("/\n\r/", "/\n/", '/&nbsp;/', '/\s*ui-resizable[\w|\-]*\s*/', '/\s*ui-droppable[\w|\-]*\s*/', '/\s*ui-state[\w|\-]*\s*/', '/type=submit/', '/\s*aria-disabled=[\w|"]*\s*/');
					$form_html = preg_replace('/\s+/', ' ', $form_html);
					$pure_html = preg_replace($filters, ' ', $form_html);
					$pure_html = preg_replace(array('/class=" "/', '/class=""/', '/class= /', '/style= /'), '', $pure_html);
					$pure_html = preg_replace('/style=>/', '>', $pure_html);
					$pure_html = preg_replace('/\s+/', ' ', $pure_html);
					$result = $this->generateEditReadTemplates($pure_html);
				
					$fieldlist = array();
					foreach($result['elements'] as $key => $value){
						$fieldlist[] = strtolower($key);
					}
					
					foreach ($subform->getSubformFields() as $fields) 
					{
						if (!in_array(strtolower($fields->getFieldName()), $fieldlist) && !array_key_exists($fields->getFieldName(), $add_fileds)) {
							$fields->setTrash(true);
						}
						else {
							$fields->setTrash(false);
						}
					}
					try {
						$subform->setModifiedBy($this->user);
						$subform->setDateModified(new \DateTime());
						$subform->setFormFileName("sfCustomSection-$form_name");
						$subform->setFormName($form_alias);
						$em->flush();
						
						foreach ($result['elements'] as $key => $value) {
							$exists = false;
							foreach ($subform->getSubformFields() as $fields) 
							{
								if (strtolower($key) == strtolower($fields->getFieldName())) {
									if (in_array($value['type'], array('text', 'textarea'))) {
										$fields->setFieldType('text');
									}
									else {
										$fields->setFieldType($value['type']);
									}
									
									if ($fields->getOptions()->count() > 0) {
										foreach ($fields->getOptions() as $option) {
											$em->remove($option);
										}
									}

									if (in_array($value['type'], array('checkbox', 'radio', 'select')))
									{
										foreach ($value['options'] as $option) {
											$field_options = new ListFieldOptions();
											$field_options->setDisplayOption($option['label']);
											$field_options->setOptValue($option['value']);
											$field_options->setOptionOrder($option['order']);
											$field_options->setField($fields);
											$em->persist($field_options);
										}
									}
									$em->flush();
									$exists = true;
									break;
								}
							}
							
							if ($exists === false) 
							{
								$element = new DesignElements();
								$element->setModifiedBy($this->user);
								$element->setDateModified(new \DateTime());
								$element->setFieldName($value['name']);
								if (in_array($value['type'], array('text', 'textarea'))) {
									$element->setFieldType('text');
								}
								else {
									$element->setFieldType($value['type']);
								}
								$element->setSubform($subform);
								$em->persist($element);
								
								if (in_array($value['type'], array('checkbox', 'radio', 'select')))
								{
									foreach ($value['options'] as $option) {
										$field_options = new ListFieldOptions();
										$field_options->setDisplayOption($option['label']);
										$field_options->setOptValue($option['value']);
										$field_options->setOptionOrder($option['order']);
										$field_options->setField($element);
										$em->persist($field_options);
									}
								}
							}
							
							$em->flush();
						}

						if (!empty($add_fileds) && count($add_fileds) > 0)
						{
							for ($x = 0; $x < count($add_fileds); $x++) {
								$exists = false;
								foreach ($subform->getSubformFields() as $fields)
								{
									if ($add_fileds[$x] == $fields->getFieldName()) {
										$fields->setTrash(false);
										$fields->setFieldType($addfield_types[$x]);
										$fields->setIsExtra(true);
										$exists = true;
										break;
									}
								}
								if ($exists === false && !empty($add_fileds[$x])) {
									$element = new DesignElements();
									$element->setModifiedBy($this->user);
									$element->setDateModified(new \DateTime());
									$element->setFieldName($add_fileds[$x]);
									$element->setFieldType($addfield_types[$x]);
									$element->setIsExtra(true);
									$element->setSubform($subform);
									$em->persist($element);
								}
								$em->flush();
							}
						}

						@unlink($direction.$file_name.'.html.twig');
						@unlink($direction.$file_name.'-read.html.twig');
						@unlink($this->get('kernel')->getRootDir().'/../web/upload/templates/'.$file_name.'.htm');
						@unlink($this->get('kernel')->getRootDir().'/../web/upload/js/'.$file_name.'.js');
						
						file_put_contents($direction.$twig_edit_filename, html_entity_decode($result['edit_html']));
						file_put_contents($direction.$twig_read_filename, html_entity_decode($result['read_html']));
						//file_put_contents($this->get('kernel')->getRootDir().'/../web/upload/templates/sfCustomSection-'.$form_name.'.htm', $dom_html->saveHTML());
						file_put_contents($this->get('kernel')->getRootDir().'/../web/upload/templates/sfCustomSection-'.$form_name.'.htm', $result['template']);
						if (trim($form_js)) {
							file_put_contents($this->get('kernel')->getRootDir().'/../web/upload/js/sfCustomSection-'.$form_name.'.js', $form_js);
						}
						$output[] = true;
					}
					catch (\Exception $e) {
						$output['error'][] = 'Could not save the modified subform; please try again or contact Administrator. ' . $e->getMessage();
					}
				}
			}
			else {
				$output['error'][] = 'Unspecified Subform source.';
			}
		}

		$response = new Response();
		$response->headers->set('Content-Type', 'json/application');
		$response->setContent(json_encode($output));
		return $response;
	}
	
	public function deleteSubformAction(Request $request)
	{
		$output = array();
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$subform = $request->request->get('sid');
		$em = $this->getDoctrine()->getManager();
		if (!empty($subform)) 
		{
			$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('id' => $subform, 'Is_Custom' => true));
		}
		
		if (empty($subform)) 
		{
			$output = array('Status' => 'FAILED', 'ErrMsg' => 'Unspecified subform source!');
			$response->setContent(json_encode($output));
			return $response;
		}
		
		try {
			$direction = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'Subform'.DIRECTORY_SEPARATOR;
			$file_name = $subform->getFormFileName();
			$data = $em->getRepository('DocovaBundle:FormTextValues')->getSubformFieldValues($subform->getId());
			if (!empty($data)) 
			{
				foreach ($data as $value) {
					$em->remove($value);
				}
			}
			unset($data, $value);

			if ($subform->getDocType()->count() > 0) 
			{
				foreach ($subform->getDocType() as $sfdoctype) {
					$em->remove($sfdoctype);
				}
			}
			unset($sfdoctype);
			
			if ($subform->getActionButtons()->count() > 0) 
			{
				foreach ($subform->getActionButtons() as $btn) {
					if ($btn->getVisibleOn()) 
					{
						$em->remove($btn->getVisibleOn());
					}
					$em->remove($btn);
				}
			}
			unset($btn);
			
			$search_fields = $em->getRepository('DocovaBundle:CustomSearchFields')->getSearchFieldsInSubform($subform->getId());
			if (!empty($search_fields)) 
			{
				foreach ($search_fields as $sfield) {
					$sfield->removeAllDocumentTypes();
					$em->remove($sfield);
				}
			}
			unset($search_fields, $sfield);
			
			if ($subform->getSubformFields()->count() > 0) 
			{
				foreach ($subform->getSubformFields() as $field) {
					$em->remove($field);
				}
			}
			unset($field);
			$em->remove($subform);
			$em->flush();
			
			@unlink($direction.$file_name.'.html.twig');
			@unlink($direction.$file_name.'-read.html.twig');
			@unlink($this->get('kernel')->getRootDir().'/../web/upload/templates/'.$file_name.'.htm');
			@unlink($this->get('kernel')->getRootDir().'/../web/upload/js/'.$file_name.'.js');
						
			$output = array('Status' => true);
		}
		catch (\Exception $e) {
			$output = array(
				'Status' => 'FAILED',
				'ErrMsg' => $e->getMessage()
			);
		}
		return $response->setContent(json_encode($output));
	}
	
	/**
	 * Parse HTML and generate TWIG template base on the input HTML source
	 *
	 * @param string $pure_html
	 * @return array
	 */
	private function generateEditReadTemplates($pure_html)
	{
		$elements = array();
		$dom_html = new \DOMDocument();
		$dom_edit = new \DOMDocument();
		$dom_read = new \DOMDocument();
		$dom_html->loadHTML($pure_html);
		$dom_edit->loadHTML($pure_html);
		$dom_read->loadHTML($pure_html);
		$first_index = '';
		$div_elements = array();
		foreach ($dom_html->getElementsByTagName('div') as $div) {
			if (!$div->hasChildNodes()) {
				$div_elements[] = $div;
			}
		}
		if (!empty($div_elements)) {
			foreach ($div_elements as $div) {
				$div->parentNode->removeChild($div);
			}
			$div_elements = array();
		}
	
		foreach ($dom_edit->getElementsByTagName('div') as $div) {
			if (!$div->hasChildNodes()) {
				$div_elements[] = $div;
			}
		}
		if (!empty($div_elements)) {
			foreach ($div_elements as $div) {
				$div->parentNode->removeChild($div);
			}
			$div_elements = array();
		}
	
		foreach ($dom_read->getElementsByTagName('div') as $div) {
			if (!$div->hasChildNodes()) {
				$div_elements[] = $div;
			}
		}
		if (!empty($div_elements)) {
			foreach ($div_elements as $div) {
				$div->parentNode->removeChild($div);
			}
			$div_elements = array();
		}
	
	
		foreach ($dom_html->getElementsByTagName('span') as $item)
		{
			if ($item->getAttribute('contenteditable'))
			{
				$found = false;
				foreach ($dom_edit->getElementsByTagName('span') as $edit_item) {
					if ($edit_item->getNodePath() == $item->getNodePath()) {
						$found = true;
						break;
					}
				}
	
				if ($found === true)
				{
					if ($edit_item->getAttribute('class') == 'htmlcode')
					{
						$html_content = '<span>' . rawurldecode($item->nodeValue) . '</span>';
						$tmpDom = new \DOMDocument();
						$tmpDom->loadHTML($html_content);
						$new_node = $dom_edit->importNode($tmpDom->getElementsByTagName('span')->item(0), true);
						$edit_item->parentNode->replaceChild($new_node, $edit_item);
						unset($tmpDom, $new_node, $html_content);
					}
					elseif ($edit_item->getAttribute('class') == 'twigcode') {
						$twig_codes = rawurldecode(($item->nodeValue));
						$domtext = $dom_edit->createCDATASection($twig_codes);
						$edit_item->parentNode->replaceChild($domtext, $edit_item);
					}
				}
			}
		}
	
		foreach ($dom_html->getElementsByTagName('span') as $item)
		{
			if ($item->getAttribute('contenteditable'))
			{
				$found = false;
				foreach ($dom_read->getElementsByTagName('span') as $read_item) {
					if ($read_item->getNodePath() == $item->getNodePath()) {
						$found = true;
						break;
					}
				}
	
				if ($found === true)
				{
					if ($read_item->getAttribute('class') == 'htmlcode')
					{
						$html_content = '<span>' . rawurldecode($item->nodeValue) . '</span>';
						$tmpDom = new \DOMDocument();
						$tmpDom->loadHTML($html_content);
						$new_node = $dom_read->importNode($tmpDom->getElementsByTagName('span')->item(0), true);
						$read_item->parentNode->replaceChild($new_node, $read_item);
						unset($tmpDom, $new_node, $html_content);
					}
					elseif ($read_item->getAttribute('class') == 'twigcode') {
						$twig_codes = rawurldecode(($item->nodeValue));
						$domtext = $dom_read->createCDATASection($twig_codes);
						$read_item->parentNode->replaceChild($domtext, $read_item);
					}
				}
			}
		}
	
		//echo "DOM_EDIT IS \n".$dom_edit->saveHTML();
		//echo "DONE HERE";
	
		foreach ($dom_html->getElementsByTagName('input') as $item)
		{
			if ($item->hasAttribute('name') && !array_key_exists($item->getAttribute('name'), $elements))
			{
				$index = $item->getAttribute('name');
				$first_index = empty($first_index) ? $index : $first_index;
				$elements[$index]['name'] = strtolower($index);
				if ($item->hasAttribute('type')) {
					$elements[$index]['type'] = $item->getAttribute('type');
				}
				else {
					$elements[$index]['type'] = 'text';
				}
					
				$found = false;
				foreach ($dom_edit->getElementsByTagName('input') as $edit_item) {
					if ($edit_item->getNodePath() == $item->getNodePath()) {
						$found = true;
						break;
					}
				}
	
				if ($found === true) {
					$edit_item->setAttribute('name', $index);
					switch ($elements[$index]['type']) {
						case 'text':
							if ($edit_item->hasAttribute('target')) {
								$elements[$index]['type'] = 'names';
								if ($edit_item->hasAttribute('class') && false !== strpos($edit_item->getAttribute('class'), 'singleNamePicker'))
								{
									$edit_item->setAttribute('value', "{{ properties['document'] ? data['{$elements[$index]['name']}']['Value'] : '' }}");
								}
								else {
									$edit_item->removeAttribute('name');
									$edit_item->removeAttribute('id');
									$new_node = $dom_edit->createElement('input');
									$attr = $dom_edit->createAttribute('name');
									$attr->value = $index;
									$new_node->appendChild($attr);
									$attr = $dom_edit->createAttribute('type');
									$attr->value = 'hidden';
									$new_node->appendChild($attr);
									$attr = $dom_edit->createAttribute('id');
									$attr->value = $index;
									$new_node->appendChild($attr);
									$attr = $dom_edit->createAttribute('value');
									$attr->value = "{{ properties['document'] ? data['{$elements[$index]['name']}']['Value'] : '' }}";
									$new_node->appendChild($attr);
									$edit_item->parentNode->appendChild($new_node);
									$new_node = new \DOMText("{% if properties['document'] and data['{$elements[$index]['name']}']['Value'] %}<p class=\"slContainer\">{{ data['{$elements[$index]['name']}']['Value'] }}</p>{% endif %}");
									$edit_item->parentNode->appendChild($new_node);
									unset($new_node,$attr);
								}
							}
							else {
								if ($edit_item->hasAttribute('class') && false !== strpos($edit_item->getAttribute('class'), 'docovaDatepicker')) {
									$elements[$index]['value'] = '';
									$elements[$index]['type'] = 'date';
								}
								else {
									$elements[$index]['value'] = $edit_item->getAttribute('value');
								}
								$edit_item->setAttribute('value', "{{ properties['document'] ? data['{$elements[$index]['name']}']['Value'] : '".$edit_item->getAttribute('value')."' }}");
							}
							break;
						case 'checkbox':
						case 'radio':
							$default_value = '';
							$container_table = $item->parentNode->parentNode->parentNode->parentNode;
							$cells	= $container_table->getElementsByTagName('tr')->item(0)->getElementsByTagName('td')->length;
	
							foreach ($container_table->getElementsByTagName('input') as $option) {
								$elements[$index]['options'][] = array('value' => $option->getAttribute('value'), 'label' => $option->nextSibling->nodeValue, 'order' => $option->getAttribute('order'));
								if ($option->getAttribute('checked')) {
									$default_value .= "'{$option->getAttribute('value')}',";
								}
							}
							$default_value = !empty($default_value) ? substr_replace($default_value, '', -1) : '';
	
							$edit_item->nextSibling->nodeValue = '{{ option[\'Display_Option\'] }}';
							$node_string = $dom_edit->saveHTML($edit_item->parentNode);
							$node_string = preg_replace('/checked/i', '', $node_string);
							$node_string = preg_replace('/value="[^"]+"/i', 'value="{{ option[\'Opt_Value\'] }}"', $node_string);
							$node_string = preg_replace('/\<input /i', "<input {% if option['Opt_Value'] in selectedValues %}checked{% endif %} ", $node_string);
	
							$container_html = <<<HTM
{% set selectedValues = properties['document'] ? data['{$elements[$index]['name']}']['Value']|split(';') : [{$default_value}] %}
{% for option in data['{$elements[$index]['name']}']['Options'] %}
	{% if loop.index0 % {$cells} == 0  %}<tr>{% endif %}
		<td>{$node_string}</td>
	{% if loop.index % {$cells} == 0 or loop.index == data['{$elements[$index]['name']}']['Options']|length %}</tr>{% endif %}
{% endfor %}
{% if selectedValues|length > 0 %}
{% set extra = 0 %}
{% for item in selectedValues %}
	{% set exist = false %}
	{% for option in data['{$elements[$index]['name']}']['Options'] %}
		{% if item|trim == option['Opt_Value']|trim %}
			{% set exist = true %}
		{% endif %}
	{% endfor %}
	{% if exist == false and item %}
		{% if extra % {$cells} == 0  %}<tr>{% endif %}
		<td><label><input name="{$elements[$index]['name']}" checked type="{$elements[$index]['type']}" value="{{ item }}">{{ item }}</label></td>
		{% set extra = extra + 1 %}
		{% if extra % {$cells} == 0  %}</tr>{% endif %}
	{% endif %}
{% endfor %}
{% endif %}
HTM;
							$domtext = $dom_edit->createCDATASection($container_html);
							$edit_item->parentNode->parentNode->parentNode->parentNode->parentNode->replaceChild($domtext, $edit_item->parentNode->parentNode->parentNode->parentNode);
							break;
					}
				}
			}
		}
	
		foreach ($dom_html->getElementsByTagName('textarea') as $item)
		{
			if ($item->hasAttribute('name') && !array_key_exists($item->getAttribute('name'), $elements))
			{
				$index = $item->getAttribute('name');
				$first_index = empty($first_index) ? $index : $first_index;
				$elements[$index]['name'] = strtolower($index);
				$elements[$index]['type'] = 'textarea';
					
				$found = false;
				foreach ($dom_edit->getElementsByTagName('textarea') as $edit_item) {
					if ($edit_item->getNodePath() == $item->getNodePath()) {
						$found = true;
						break;
					}
				}
	
				if ($found === true) {
					$edit_item->setAttribute('name', $index);
					$elements[$index]['value'] = $edit_item->getAttribute('value');
					$cdata = $dom_edit->createCDATASection("{{ data['{$elements[$index]['name']}']['Value'] }}");
					$edit_item->appendChild($cdata);
				}
			}
		}
			
		foreach ($dom_html->getElementsByTagName('select') as $item)
		{
			if ($item->hasAttribute('name') && !array_key_exists($item->getAttribute('name'), $elements))
			{
				$index = $item->getAttribute('name');
				$first_index = empty($first_index) ? $index : $first_index;
				$elements[$index]['name'] = strtolower($index);
				$elements[$index]['type'] = 'select';
					
				$found = false;
	
				foreach ($dom_edit->getElementsByTagName('select') as $edit_item) {
					if ($edit_item->getNodePath() == $item->getNodePath()) {
						$found = true;
						break;
					}
				}
	
				if ($found === true) {
					$default_value = '';
					$edit_item->setAttribute('name', $index);
					foreach ($edit_item->getElementsByTagName('option') as $option) {
						$elements[$index]['options'][] = array('value' => $option->getAttribute('value'), 'label' => $option->nodeValue, 'order' => $option->getAttribute('order'));
						if ($option->getAttribute('selected')) {
							$default_value = $option->getAttribute('value');
						}
					}
	
					$node_string = '{% set exists = false %}{% for option in data[\''.$elements[$index]['name'].'\'][\'Options\'] %}';
					$node_string .= '<option value="{{ option[\'Opt_Value\'] }}" {% if properties[\'document\'] and data[\''.$elements[$index]['name'].'\'][\'Value\'] == option[\'Opt_Value\'] %}selected{% set exists = true %}{% elseif not properties[\'document\'] and option[\'Opt_Value\'] == \''.$default_value.'\' %}selected{% set exists = true %}{% endif %}>';
					$node_string .= '{{ option[\'Display_Option\'] }}</option>{% endfor %}';
					$node_string .= '{% if exists == false and data[\''.$elements[$index]['name'].'\'][\'Value\'] %}<option value="{{ data[\''.$elements[$index]['name'].'\'][\'Value\'] }}" selected="selected">{{ data[\''.$elements[$index]['name'].'\'][\'Value\'] }}</option>{% endif %}';
					$node_string = $dom_edit->createCDATASection($node_string);
					libxml_use_internal_errors(true);
					$clone = $edit_item->cloneNode(false);
					$clone->appendChild($node_string);
					$edit_item->parentNode->replaceChild($clone, $edit_item);
					libxml_use_internal_errors(false);
				}
			}
		}
			
		$read_elements = array();
		foreach ($dom_html->getElementsByTagName('input') as $item)
		{
			if ($item->hasAttribute('name') && !array_key_exists($item->getAttribute('name'), $read_elements))
			{
				$index = $item->getAttribute('name');
				$read_elements[] = $index;
				$found = false;
				foreach ($dom_read->getElementsByTagName('input') as $read_item) {
					if ($read_item->getNodePath() == $item->getNodePath()) {
						$found = true;
						break;
					}
				}
					
				if ($found === true)
				{
					switch ($item->getAttribute('type')) {
						case 'checkbox':
	
							$parent_item = $read_item->parentNode;
	
							$container_html = <<<HTM
{% set found = false %}<tr><td>
{% set selectedValues = data['{$elements[$index]['name']}']['Value']|split(';') %}
{% for option in data['{$elements[$index]['name']}']['Options'] %}
	 {% if option['Opt_Value'] in selectedValues %}
	 <span name="{$index}">{{ option['Display_Option'] }}</span><br />
	 {% set found = true %}
	 {% endif %}
{% endfor %}
{% if found == false and data['{$elements[$index]['name']}']['Value'] %}
	{{ data['{$elements[$index]['name']}']['Value']|replace({';':'<br />'})|raw }}
{% else %}
	{% for item in selectedValues %}
		{% set exist = false %}
		{% for option in data['{$elements[$index]['name']}']['Options'] %}
			{% if item|trim == option['Opt_Value']|trim %}
				{% set exist = true %}
			{% endif %}
		{% endfor %}
		{% if exist == false and item %}
			<span name="{$index}">{{ item }}</span><br />
		{% endif %}
	{% endfor %}
{% endif %}
</td></tr>
HTM;
							$domtext = $dom_read->createCDATASection($container_html);
							$parent_item->parentNode->parentNode->parentNode->parentNode->replaceChild($domtext, $parent_item->parentNode->parentNode->parentNode);
							break;
						case 'radio':
							$container_html = <<<HTM
<tr><td>
{% set found = false %}
{% for option in data['{$elements[$index]['name']}']['Options'] if option['Opt_Value'] == data['{$elements[$index]['name']}']['Value'] %}
	<span id="{$index}"> {{ option['Display_Option'] }}</span><br />
	{% set found = true %}
{% endfor %}
{% if found == false and data['{$elements[$index]['name']}']['Value'] %}<span id="{$index}">{{ data['{$elements[$index]['name']}']['Value'] }}</span>{% endif %}
</td></tr>
HTM;
							$domtext = $dom_read->createCDATASection($container_html);
							$read_item->parentNode->parentNode->parentNode->parentNode->parentNode->replaceChild($domtext, $read_item->parentNode->parentNode->parentNode->parentNode);
							break;
						case 'text':
						default:
							$new_node = $dom_read->createElement('span');
							$new_node->setAttribute('id', "{$index}");
							$cdata = $dom_read->createCDATASection("{{ data['{$elements[$index]['name']}']['Value'] }}");
							$new_node->appendChild($cdata);
							if (!empty($read_item->nextSibling) && !empty($read_item->nextSibling->nodeName) && strtolower($read_item->nextSibling->nodeName) == 'button') {
								$read_item->parentNode->removeChild($read_item->nextSibling);
							}
							$read_item->parentNode->replaceChild($new_node, $read_item);
							break;
					}
				}
			}
		}
	
		foreach ($dom_html->getElementsByTagName('textarea') as $item)
		{
			if ($item->hasAttribute('name') && !array_key_exists($item->getAttribute('name'), $read_elements))
			{
				$index = $item->getAttribute('name');
				$read_elements[] = $index;
				$found = false;
				foreach ($dom_read->getElementsByTagName('textarea') as $read_item) {
					if ($read_item->getNodePath() == $item->getNodePath() && $read_item->getAttribute('name') == $item->getAttribute('name')) {
						$found = true;
						break;
					}
				}
	
				if ($found === true) {
					$new_node = $dom_read->createElement('span');
					$new_node->setAttribute('id', $index);
					$cdata = $dom_read->createCDATASection("{{ data['{$elements[$index]['name']}']['Value']|nl2br }}");
					$new_node->appendChild($cdata);
					$read_item->parentNode->replaceChild($new_node, $read_item);
				}
			}
		}
	
		foreach ($dom_html->getElementsByTagName('select') as $item)
		{
			if ($item->hasAttribute('name') && !array_key_exists($item->getAttribute('name'), $read_elements))
			{
				$index = $item->getAttribute('name');
				$read_elements[] = $index;
				$found = false;
				foreach ($dom_read->getElementsByTagName('select') as $read_item) {
					if ($read_item->getNodePath() == $item->getNodePath()) {
						$found = true;
						break;
					}
				}
	
				if ($found === true)
				{
					$node_string = '{% set found = false %}';
					$node_string .= "{% for option in data['{$elements[$index]['name']}']['Options'] %}";
					$node_string .= "{% if data['{$elements[$index]['name']}']['Value'] == option['Opt_Value'] %}";
					$node_string .= "<span id=\"{$index}\"> {{ option['Display_Option'] }}{% set found = true %}</span>";
					$node_string .= '{% endif %}{% endfor %}';
					$node_string .= "{% if found == false and data['{$elements[$index]['name']}']['Value'] %}";
					$node_string .= "<span id=\"{$index}\">{{ data['{$elements[$index]['name']}']['Value'] }}</span>";
					$node_string .= '{% endif %}';
					$node_string = $dom_read->createCDATASection($node_string);
					$read_item->parentNode->replaceChild($node_string, $read_item);
				}
			}
		}
	
		$tbl_node = $dom_edit->getElementsByTagName('table')->item(0);
		$html_file_content = <<<HTML
<style type="text/css">
.verdana { font-family: Verdana }
.arial { font-family: Arial }
.sans-serif { font-family: sans-serif }
.italic { font-style: italic; }
.bold { font-weight: bold; }
.sz_9 { font-size: 9px; }
.sz_10 { font-size: 10px; }
.sz_11 { font-size: 11px; }
.sz_12 { font-size: 12px; }
.sz_13 { font-size: 13px; }
.sz_14 { font-size: 14px; }
.sz_15 { font-size: 15px; }
.sz_16 { font-size: 16px; }
.sz_17 { font-size: 17px; }
.sz_18 { font-size: 18px; }
.sz_19 { font-size: 19px; }
.subform_container table {
	display: block;
	table-layout: fixed;
}
</style>
HTML;
		if (!empty($elements[$first_index]['name'])) {
			$html_file_content .= '<input type="hidden" name="subforms[]" value="{{ data[\''.$elements[$first_index]['name'].'\'][\'Field\'].getSubform.getId }}" />';
		}
		$html_file_content .= <<<HTML
<div class="subform_container">
{$dom_edit->saveHTML($tbl_node)}
</div>
HTML;
	
	$tblrd_node = $dom_read->getElementsByTagName('table')->item(0);
	$htmlrd_file_content = <<<HTML
<style type="text/css">
.verdana { font-family: Verdana }
.arial { font-family: Arial }
.sans-serif { font-family: sans-serif }
.italic { font-style: italic; }
.bold { font-weight: bold; }
.sz_9 { font-size: 9px; }
.sz_10 { font-size: 10px; }
.sz_11 { font-size: 11px; }
.sz_12 { font-size: 12px; }
.sz_13 { font-size: 13px; }
.sz_14 { font-size: 14px; }
.sz_15 { font-size: 15px; }
.sz_16 { font-size: 16px; }
.sz_17 { font-size: 17px; }
.sz_18 { font-size: 18px; }
.sz_19 { font-size: 19px; }
.subform_container table {
	display: block;
	table-layout: fixed;
}
</style>
<div class="subform_container">
{$dom_read->saveHTML($tblrd_node)}
</div>
HTML;
	
	return array(
			'elements' => $elements,
			'edit_html' => $html_file_content,
			'read_html' => $htmlrd_file_content,
			'template' => $dom_html->saveHTML($dom_html->getElementsByTagName('table')->item(0))
	);
	}
	
	/**
	 * Save design element service
	 * @param \DOMDocument $post
	 * @return \DOMDocument|string
	 */
	private function dserviceSAVEDESIGNELEMENTHTML($post)
	{
		$output = false;
		$type = $post->getElementsByTagName('Type')->item(0)->nodeValue;
		switch ($type)
		{
			case 'Form':
				$oldname = '';
				$form = $this->saveFormDesignElements($post, $oldname);
				if ($form instanceof \Docova\DocovaBundle\Entity\AppForms) {
					$output = $this->saveTemplate($post, $form, 'Form', $oldname);
				}
				else {
					$output = $form;
				}
				break;
			case 'Page':
				$page = $this->savePageDesignElements($post);
				if ($page instanceof \Docova\DocovaBundle\Entity\AppPages) {
					$output = $this->saveTemplate($post, $page, 'Page');
				}
				else {
					$output = $page;
				}
				break;
			case 'Subform':
				$subform = $this->saveSubformDesignElements($post);
				if ($subform instanceof \Docova\DocovaBundle\Entity\Subforms) {
					$output = $this->saveTemplate($post, $subform, 'Subform');
				}
				else {
					$output = $subform;
				}
				break;
			case 'Outline':
				$outline = $this->saveOutlineDesignElements($post);
				if ($outline instanceof \Docova\DocovaBundle\Entity\AppOutlines) {
					$output = $this->saveTemplate($post, $outline, 'Outline');
				}
				else {
					$output = $outline;
				}
				break;
			case 'Widget':
				$widget = $this->saveWidgetDesignElement($post);
				if ($widget && $widget instanceof \Docova\DocovaBundle\Entity\Widgets) {
					$output = $this->saveTemplate($post, $widget, 'Widget');
				}
				else {
					$output = $widget;
				}
				break;
			case 'JS':
				$js = $this->saveJsDesignElement($post);
				if ($js) {
					$output = $this->saveScripts($post, $js, 'JS');
				}
				else {
					$output = $js;
				}
				break;
			case 'Agent':
				$agent = $this->saveAgentDesignElement($post);
				if ($agent) {
					$output = $this->saveScripts($post, $agent, 'Agent');
				}
				else {
					$output = $agent;
				}
				break;
			case 'CSS':
				$css = $this->saveCssDesignElement($post);
				if ($css) {
					$output = $this->saveScripts($post, $css, 'Css');
				}
				else {
					$output = $css;
				}
				break;
			case 'ScriptLibrary':
				$script = $this->savePhpScriptDesignElement($post);
				if ($script) {
					$output = $this->saveScripts($post, $script, 'Php');
				}
				else {
					$output = $script;
				}
				break;
			default:
				$output = 'Invalid type entry!';
				break;
		}
		return $output;
	}
	
	/**
	 * Save app file(s)
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceSAVEIMAGERESOURCE($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$fileId = $post->getElementsByTagName('FileUNID')->item(0)->nodeValue;
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appId, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application source ID.');
			}
			if (empty($post->getElementsByTagName('imgName')->item(0)->nodeValue) || empty($post->getElementsByTagName('imgContent')->item(0)->nodeValue)) {
				throw new \Exception('No File/Image has been uploaded, or File/Image name is missed.');
			}
			if (!empty($fileId)) {
				$file = $em->getRepository('DocovaBundle:AppFiles')->findOneBy(array('id' => $fileId, 'application' => $appId));
				if (empty($file)) {
					throw new \Exception('Unspecified File/Images source ID.');
				}
				$file_name = $file->getFileName();
				$file_name = str_replace(array('/', '\\'), '-', $file_name);
				$file_name = str_replace(' ', '', $file_name);
				if (file_exists($this->appPath . '../../public/images' . DIRECTORY_SEPARATOR . $appId . DIRECTORY_SEPARATOR . $file_name)) {
					unlink($this->appPath . '../../public/images' . DIRECTORY_SEPARATOR . $appId . DIRECTORY_SEPARATOR . $file_name);
				}
			}
			else {
				$file = new AppFiles();
			}

			if (!pathinfo(trim($post->getElementsByTagName('imgName')->item(0)->nodeValue), PATHINFO_EXTENSION)) {
				$extension = $post->getElementsByTagName('imgExtension')->item(0)->nodeValue;
				$file->setFileName(trim($post->getElementsByTagName('imgName')->item(0)->nodeValue).'.'.$extension);
			}
			else {
				$file->setFileName(trim($post->getElementsByTagName('imgName')->item(0)->nodeValue));
			}
			if (!empty($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue))
				$file->setPDU($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue == 'PDU' ? true : false);
			else
				$file->setPDU(false);

			$file->setDateModified(new \DateTime());
			$file->setModifiedBy($this->user);
			
			if (empty($fileId)) {
				$file->setDateCreated(new \DateTime());
				$file->setCreatedBy($this->user);
				$file->setApplication($app);
				$em->persist($file);
			}
			$em->flush();
			
			if (!is_dir($this->appPath . '../../public/images' . DIRECTORY_SEPARATOR . $app->getId())) {
				mkdir($this->appPath . '../../public/images' . DIRECTORY_SEPARATOR . $app->getId(), 0755, true);
			}
			$content = str_replace(' ', '+', $post->getElementsByTagName('imgContent')->item(0)->nodeValue);
			$file_name = $post->getElementsByTagName('imgName')->item(0)->nodeValue;
			$file_name = str_replace(array('/', '\\'), '-', $file_name);
			$file_name = str_replace(' ', '', $file_name);
			if (!pathinfo($file_name, PATHINFO_EXTENSION)) {
				$extension = $post->getElementsByTagName('imgExtension')->item(0)->nodeValue;
				$file_name = $file_name.'.'.$extension;
			}
			file_put_contents($this->appPath.'../../public/images'.DIRECTORY_SEPARATOR.$app->getId().DIRECTORY_SEPARATOR.$file_name, base64_decode($content));

			//-- copy the files to assets directory
			if (!is_dir($this->assetPath.'/images/'.$appId)) {
				mkdir($this->assetPath.'/images/'.$appId, 0775, true);
			}
			file_put_contents($this->assetPath.'/images/'.$appId.'/'.$file_name, base64_decode($content));
							
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $file->getId());
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		
		return $output;
	}
	
	private function dserviceSAVEWORKFLOWDESIGN($post, $request)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$workflow = $post->getElementsByTagName('WorkflowDocKey')->item(0)->nodeValue;
			$app = $request->query->get('AppID');
			$workflow = $em->getRepository('DocovaBundle:Workflow')->findOneBy(array('id' => $workflow, 'application' => $app));
			if (empty($workflow)) {
				throw new \Exception('Unspecified application workflow source ID.');
			}


			//$start_html = $post->getElementsByTagName('StartStepHTML')->item(0)->nodeValue;
			$wf_html = $post->getElementsByTagName('WorkflowHTML')->item(0)->nodeValue;
			

			if (!is_dir($this->appPath . $app . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'WORKFLOWS')) {
				mkdir($this->appPath . $app . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'WORKFLOWS', 0775, true);
			}
			
			file_put_contents($this->appPath . $app . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'WORKFLOWS' . DIRECTORY_SEPARATOR . $workflow->getId() . '.html.twig', "$wf_html");

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $workflow->getId());
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		
		return $output;
	}
	
	
	/**
	 * Create app form documnet type service
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceSAVEDOCTYPE($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$em = $this->getDoctrine()->getManager();
			$form = trim($post->getElementsByTagName('FormUnid')->item(0)->nodeValue);
			$app = trim($post->getElementsByTagName('AppID')->item(0)->nodeValue);
			$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' => $form, 'application' => $app, 'trash' => false));
			if (empty($form)) {
				throw new \Exception('Unspecified form/application source ID.');
			}

			
			$initial = $superseded = $discarded = $custom_hide_buttons = $att_options = $startBtn_label = $workflow_style = null;
			$att_prop = array();
			$versioning = $live_draft = $restrict_draft = $enable_workflow = $strict_versioning = $retract = $update_bookmark = $cant_delete = false;
			$life_cycle = !empty($post->getElementsByTagName('enablelifecycle')->item(0)->nodeValue) && $post->getElementsByTagName('enablelifecycle')->item(0)->nodeValue == '1' ? true : false;
			$final = !empty($post->getElementsByTagName('finalstatus')->item(0)->nodeValue) ? trim($post->getElementsByTagName('finalstatus')->item(0)->nodeValue) : 'Released';
			$archived = !empty($post->getElementsByTagName('archivedstatus')->item(0)->nodeValue) ? trim($post->getElementsByTagName('archivedstatus')->item(0)->nodeValue) : 'Archived';
			$deleted = !empty($post->getElementsByTagName('deletedstatus')->item(0)->nodeValue) ? trim($post->getElementsByTagName('deletedstatus')->item(0)->nodeValue) : 'Deleted';
			$releaseBtnLabel = !empty($post->getElementsByTagName('wfcustomreleasebuttonlabel')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustomreleasebuttonlabel')->item(0)->nodeValue) : null;
			$hide_buttons = !empty($post->getElementsByTagName('wfHideButtons')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfHideButtons')->item(0)->nodeValue) : null;
			$reviewBtn_label = !empty($post->getElementsByTagName('wfcustomreviewbuttonlabel')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustomreviewbuttonlabel')->item(0)->nodeValue) : null;
			$textarea_section = !empty($post->getElementsByTagName('HasRTEditor')->item(0)->nodeValue) ? (trim($post->getElementsByTagName('HasRTEditor')->item(0)->nodeValue) == 2 ? 2 : 1) : null;
			$att_section = !empty($post->getElementsByTagName('hasattachment')->item(0)->nodeValue) || $post->getElementsByTagName('attachmentsreadonly')->length > 0 ? true : false;
			$wqoagent = !empty($post->getElementsByTagName('WqoAgent')->item(0)->nodeValue) ? trim($post->getElementsByTagName('WqoAgent')->item(0)->nodeValue) : '';
			$wqsagent = !empty($post->getElementsByTagName('WqsAgent')->item(0)->nodeValue) ? trim($post->getElementsByTagName('WqsAgent')->item(0)->nodeValue) : '';
			$email_section = false;
			if ($post->getElementsByTagName('sectiontype')->length) {
				foreach ($post->getElementsByTagName('sectiontype') as $item) {
					if ($item->nodeValue == 'relatedemails') {
						$email_section = true;
						break;
					}
				}
			}
			
			if ($form->getFormWorkflows()->count()) 
			{
				$form_workflows = $form->getFormWorkflows(); 
				foreach ($form_workflows as $fwf) {
					$form->removeFormWorkflow($fwf);
				}
				$em->flush();
			}
			
			$isNew = true;
			if ($form->getFormProperties()) {
				$isNew = false;
				$docType = $form->getFormProperties();
			}
			else {
				$docType = new AppFormProperties();
			}
			$docType->setEnableLifeCycle($life_cycle);
			$docType->setArchivedStatus($archived);
			$docType->setFinalStatus($final);
			$docType->setDeletedStatus($deleted);
			$docType->setAttachmentSection($att_section);
			$docType->setEmailSection($email_section);
			$docType->setCustomReleaseButtonLabel($releaseBtnLabel);
			$docType->setHideButtons($hide_buttons);
			$docType->setCustomReviewButtonLabel($reviewBtn_label);
			if (in_array('C', explode(';', $hide_buttons))) {
				$custom_hide_buttons = !empty($post->getElementsByTagName('wfcustombuttonshidewhen')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustombuttonshidewhen')->item(0)->nodeValue) : null; 
			}
			$docType->setCustomHideButtonsWhen($custom_hide_buttons);
			if ($att_section === true) {
				if ($form->getAttachmentProp()) {
					$att_prop = $form->getAttachmentProp();
				}
				else {
					$att_prop = new AppFormAttProperties();
				}
				$att_prop->setReadOnly(!empty($post->getElementsByTagName('attachmentsreadonly')->item(0)->nodeValue) ? true : false);
				$att_prop->setMaxFiles(!empty($post->getElementsByTagName('maxfiles')->item(0)->nodeValue) ? intval($post->getElementsByTagName('maxfiles')->item(0)->nodeValue) : 0);
				$att_prop->setAllowedExtensions(!empty($post->getElementsByTagName('allowedfileextensions')->item(0)->nodeValue) ? trim($post->getElementsByTagName('allowedfileextensions')->item(0)->nodeValue) : null);
				$att_prop->setFileViewLogging(!empty($post->getElementsByTagName('enablefileviewlogging')->item(0)->nodeValue) ? true : false);
				$att_prop->setFileDownloadLogging(!empty($post->getElementsByTagName('enablefiledownloadlogging')->item(0)->nodeValue) ? true : false);
				$att_prop->setLocalScan(!empty($post->getElementsByTagName('enablelocalscan')->item(0)->nodeValue) ? true : false);
				$att_prop->setFileCiao(!empty($post->getElementsByTagName('enablefileciao')->item(0)->nodeValue) ? true : false);
				$att_prop->setHideAttachment(!empty($post->getElementsByTagName('hidewhen')->item(0)->nodeValue) ? trim($post->getElementsByTagName('hidewhen')->item(0)->nodeValue) : null);
				if (!empty($post->getElementsByTagName('templatetype')->length) && $post->getElementsByTagName('templatetype')->item(0)->nodeValue != 'None')
				{
					$att_prop->setTemplateType(trim($post->getElementsByTagName('templatetype')->item(0)->nodeValue));
					$att_prop->setTemplateList(!empty($post->getElementsByTagName('templatelist')->item(0)->nodeValue) ? $post->getElementsByTagName('templatelist')->item(0)->nodeValue : null);
					$att_prop->setTemplateAutoAttach(!empty($post->getElementsByTagName('templateautoattach')->item(0)->nodeValue) ? true : false);
				}
				else {
					$att_prop->setTemplateType(null);
					$att_prop->setTemplateList(null);
					$att_prop->setTemplateAutoAttach(false);
				}

				if (!$form->getAttachmentProp()) {
					$att_prop->setAppForm($form);
					$em->persist($att_prop);
				}
			}
			elseif ($form->getAttachmentProp()) {
				$att_prop = $form->getAttachmentProp();
				$em->remove($att_prop);
			}
			
			$startBtn_label = $startBtn_js = $completeBtn_js = $approveBtn_js = $denyBtn_js = $releaseBtnBefore_js = $releaseBtnAfter_js = null;
			if ($life_cycle === true)
			{
				$initial = !empty($post->getElementsByTagName('initialstatus')->item(0)->nodeValue) ? trim($post->getElementsByTagName('initialstatus')->item(0)->nodeValue) : null;
				$superseded = !empty($post->getElementsByTagName('supersededstatus')->item(0)->nodeValue) ? trim($post->getElementsByTagName('supersededstatus')->item(0)->nodeValue) : null;
				$discarded = !empty($post->getElementsByTagName('discardedstatus')->item(0)->nodeValue) ? trim($post->getElementsByTagName('discardedstatus')->item(0)->nodeValue) : null;
				$versioning = !empty($post->getElementsByTagName('enableversions')->item(0)->nodeValue) && $post->getElementsByTagName('enableversions')->item(0)->nodeValue === '1' ? true : false;
				$live_draft = !empty($post->getElementsByTagName('restrictlivedrafts')->item(0)->nodeValue) && $post->getElementsByTagName('restrictlivedrafts')->item(0)->nodeValue === '1' ? true : false;
				$enable_workflow = !empty($post->getElementsByTagName('showheaders')->item(0)->nodeValue) && $post->getElementsByTagName('showheaders')->item(0)->nodeValue === 'WFL' ? true : false;
				if ($versioning === true) 
				{
					$strict_versioning = !empty($post->getElementsByTagName('strictversioning')->item(0)->nodeValue) && $post->getElementsByTagName('strictversioning')->item(0)->nodeValue === '1' ? true : false;
					$retract = !empty($post->getElementsByTagName('allowretract')->item(0)->nodeValue) && $post->getElementsByTagName('allowretract')->item(0)->nodeValue === '1' ? true : false;
					$restrict_draft = !empty($post->getElementsByTagName('restrictdrafts')->item(0)->nodeValue) && $post->getElementsByTagName('restrictdrafts')->item(0)->nodeValue === '1' ? true : false;
					$update_bookmark = !empty($post->getElementsByTagName('updatebookmarks')->item(0)->nodeValue) && $post->getElementsByTagName('updatebookmarks')->item(0)->nodeValue === '1' ? true : false;
					$att_options = !empty($post->getElementsByTagName('attachmentoptions')->item(0)->nodeValue) ? trim($post->getElementsByTagName('attachmentoptions')->item(0)->nodeValue) : null;
					
				}
				if ($enable_workflow === true) 
				{
					$workflow_style = !empty($post->getElementsByTagName('workflowstyle')->item(0)->nodeValue) ? $post->getElementsByTagName('workflowstyle')->item(0)->nodeValue : 'B';
					$cant_delete = !empty($post->getElementsByTagName('disabledeleteinworkflow')->item(0)->nodeValue) && $post->getElementsByTagName('disabledeleteinworkflow')->item(0)->nodeValue === '1' ? true : false;
					$startBtn_label = !empty($post->getElementsByTagName('wfcustomstartbuttonlabel')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustomstartbuttonlabel')->item(0)->nodeValue) : null;
					$startBtn_js = !empty($post->getElementsByTagName('wfcustomjswfstart')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustomjswfstart')->item(0)->nodeValue) : null;
					$completeBtn_js = !empty($post->getElementsByTagName('wfcustomjswfcomplete')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustomjswfcomplete')->item(0)->nodeValue) : null;
					$approveBtn_js = !empty($post->getElementsByTagName('wfcustomjswfapprove')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustomjswfapprove')->item(0)->nodeValue) : null;
					$denyBtn_js = !empty($post->getElementsByTagName('wfcustomjswfdeny')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustomjswfdeny')->item(0)->nodeValue) : null;
					$releaseBtnBefore_js = !empty($post->getElementsByTagName('wfcustomjsbeforerelease')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustomjsbeforerelease')->item(0)->nodeValue) : null;
					$releaseBtnAfter_js = !empty($post->getElementsByTagName('wfcustomjsafterrelease')->item(0)->nodeValue) ? trim($post->getElementsByTagName('wfcustomjsafterrelease')->item(0)->nodeValue) : null;
					if (!empty($post->getElementsByTagName('workflowlist')->item(0)->nodeValue)) {
						$wf_list = explode(';', $post->getElementsByTagName('workflowlist')->item(0)->nodeValue);
						foreach ($wf_list as $id) {
							$workflow = $em->getReference('DocovaBundle:Workflow', $id);
							$form->addFormWorkflow($workflow);
						}
					}
					else {
						$workflows = $em->getRepository('DocovaBundle:Workflow')->findBy(array('Use_Default_Doc' => true, 'application' => $app));
						if (!empty($workflows)) {
							foreach ($workflows as $workflow) {
								$form->addFormWorkflow($workflow);
							}
						}
						else {
							$enable_workflow = $cant_delete = false;
							$startBtn_label = $startBtn_js = $completeBtn_js = $approveBtn_js = $denyBtn_js = $releaseBtnBefore_js = $releaseBtnAfter_js = null;
						}
					}
				}
			}
			$docType->setInitialStatus($initial);
			$docType->setSupersededStatus($superseded);
			$docType->setDiscardedStatus($discarded);
			$docType->setEnableVersions($versioning);
			$docType->setRestrictLiveDrafts($live_draft);
			$docType->setEnableWorkflow($enable_workflow);
			$docType->setWorkflowStyle($workflow_style);
			$docType->setStrictVersioning($strict_versioning);
			$docType->setAllowRetract($retract);
			$docType->setRestrictDrafts($restrict_draft);
			$docType->setUpdateBookmarks($update_bookmark);
			$docType->setAttachmentOptions($att_options);
			$docType->setSpecialEditorSectoin($textarea_section);
			$docType->setDisableDeleteWorkflow($cant_delete);
			$docType->setCustomStartButtonLabel($startBtn_label);
			$docType->setCustomJSWFStart($startBtn_js);
			$docType->setCustomJSWFComplete($completeBtn_js);
			$docType->setCustomJSWFApprove($approveBtn_js);
			$docType->setCustomJSWFDeny($denyBtn_js);
			$docType->setCustomJSBeforeRelease($releaseBtnBefore_js);
			$docType->setCustomJSAfterRelease($releaseBtnAfter_js);
			$docType->setCustomWqoAgent($wqoagent);
			$docType->setCustomWqsAgent($wqsagent);
			$docType->setDateModified(new \DateTime());
			if ($isNew === true) {
				$docType->setAppForm($form);
				$em->persist($docType);
			}

			$datatablesjson = $post->getElementsByTagName('DataTables')->item(0)->nodeValue;		
			if ( !empty($datatablesjson) ){
				$dtjson =  json_decode(urldecode($datatablesjson) );
				$this->handleDataTables($em, $docType, $dtjson, $post);
			}

			$em->flush();

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $docType->getId());
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	

	/**
	 * Returns image as base 64 string
	 *
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceGETIMAGEDATA($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		$result = "";
		try {

			$path = $post->getElementsByTagName('path')->item(0)->nodeValue;
			$name = $post->getElementsByTagName('elemname')->item(0)->nodeValue;
			
			$docova_conn = unserialize($_SESSION['docova_session_obj']);
			
			$result = $docova_conn->getImageData($name, $path);

			if ( ! empty($result)) {
				$resenc = base64_encode(trim($result));
				
				$root = $output->appendChild($output->createElement('Results'));
				$newnode = $output->createElement('Result', 'OK');
				$att = $output->createAttribute('ID');
				$att->value = 'Status';
				$newnode->appendChild($att);
				$root->appendChild($newnode);
				$newnode = $output->createElement('Result', $resenc);
				$att = $output->createAttribute('ID');
				$att->value = 'Ret1';
				$newnode->appendChild($att);
				$root->appendChild($newnode);

				return $output;
			}else{
				
			}
		}
		catch (\Exception $e) {
			$result = $e->getMessage();
		}

	}

	
	/**
	 * Returns extra information about a design element
	 *
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceGETDESIGNELEMENTINFO($xmldata)
	{    
	    try {
	        
	        $docovahomeurl = $this->container->getParameter('domino_services_url');
	        $username = $this->container->getParameter('domino_username');
	        $password = $this->container->getParameter('domino_password');
	        
	        $docova_conn = new DocovaDominoSession($username, $password, $docovahomeurl);
	        $_SESSION['docova_session_obj'] = serialize($docova_conn);

	        $output = $docova_conn->proxyRequest($xmldata->saveXML());
	        	        
	        return $output;
	    }
	    catch (\Exception $e) {
	        $output = $e->getMessage();
	    }   
	}
	
	
	
	/**
	 * Returns a list of response forms
	 *
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceGETRESPONSEFORMS($xmldata)
	{
	    
	    try {	        
	        $docovahomeurl = $this->container->getParameter('domino_services_url');
	        $username = $this->container->getParameter('domino_username');
	        $password = $this->container->getParameter('domino_password');
	        
	        $docova_conn = new DocovaDominoSession($username, $password, $docovahomeurl);
	        $_SESSION['docova_session_obj'] = serialize($docova_conn);

	        $output = $docova_conn->proxyRequest($xmldata->saveXML());
	        
	        return $output;
	    }
	    catch (\Exception $e) {
	        $output = $e->getMessage();
	    }    
	    
	}
	
	
	/**
	 * Returns extra information about a database
	 *
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceGETDATABASEPROPERTIES($xmldata)
	{
	    try {
	        
	        $docovahomeurl = $this->container->getParameter('domino_services_url');
	        $username = $this->container->getParameter('domino_username');
	        $password = $this->container->getParameter('domino_password');
	        
	        $docova_conn = new DocovaDominoSession($username, $password, $docovahomeurl);
	        $_SESSION['docova_session_obj'] = serialize($docova_conn);
	        
	        $output = $docova_conn->proxyRequest($xmldata->saveXML());
	        
	        return $output;
	    }
	    catch (\Exception $e) {
	        $output = $e->getMessage();
	    }
	}
	
	/**
	 * Returns a list of design element names based on type
	 *
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceGETDESIGNELEMENTS($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		
		try {
			
			$elemtype = $post->getElementsByTagName('elemtype')->item(0)->nodeValue;
			$path = $post->getElementsByTagName('path')->item(0)->nodeValue;
			$docovahomeurl = $this->container->getParameter('domino_services_url');
			$username = $this->container->getParameter('domino_username');
			$password = $this->container->getParameter('domino_password');
			$tempnode = $post->getElementsByTagName('direct');
			$direct = (!empty($tempnode) && $tempnode->length > 0 ? ($tempnode->item(0)->nodeValue == "1") : false);

			$docova_conn = new DocovaDominoSession($username, $password, $docovahomeurl);
			$_SESSION['docova_session_obj'] = serialize($docova_conn);

			$result = $docova_conn->getElementList($elemtype, $path, $direct);

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$cdata = $output->createCDATASection($result);
			$newnode = $output->createElement('Result');
			$newnode->appendChild($cdata);
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);

			return $output;
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}

		
		
	}



	private function dserviceSAVELAYOUTHTML($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');

		$em = $this->getDoctrine()->getManager();
		try {

			$app_id = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$layoutName = $post->getElementsByTagName('LayoutName')->item(0)->nodeValue;
			$layoutAlias = ($post->getElementsByTagName('LayoutAlias')->length > 0 ? $post->getElementsByTagName('LayoutAlias')->item(0)->nodeValue : "");
			$frameCode = $post->getElementsByTagName('LayoutHTML')->item(0)->nodeValue;
			$def = $post->getElementsByTagName('isDefault')->item(0)->nodeValue;
			if ( $def == "1")
				$isdefault = true;
			else
				$isdefault = false;

			$layout = new AppLayout();
			$layout->setLayoutId($layoutName);
			$layout->setLayoutAlias($layoutAlias);
			$layout->setLayoutDefault($isdefault ? true : false);
			$layout->setProhibitDesignUpdate(false);
			$layout->setDateModified(new \DateTime());
			$layout->setModifiedBy($this->user);
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app_id, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				$em->reset();
				throw $this->createNotFoundException('Unspecified applicatoin source ID.');
			}
			$layout->setDateCreated(new \DateTime());
			$layout->setCreatedBy($this->user);
			$layout->setApplication($app);
			$em->persist($layout);
			$em->flush();


			if (!is_dir($this->appPath.$app_id.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR)) {
				mkdir($this->appPath.$app_id.DIRECTORY_SEPARATOR.'layouts', 0775, true);
			}
			
			$layoutName = str_replace(array('/', '\\'), '-', $layoutName);
			$layoutName = str_replace(' ', '', $layoutName);
			file_put_contents($this->appPath.$app_id.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$layoutName.'.html.twig', $frameCode);
			

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $layout->getId());
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}

	/**
	 * Returns specified design element html from Domino DOCOVA instance
	 *
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceGETELEMENTHTML($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		$result = "";
		try {

			$elemtype = $post->getElementsByTagName('elemtype')->item(0)->nodeValue;
			$path = $post->getElementsByTagName('path')->item(0)->nodeValue;
			
			$elemname = $post->getElementsByTagName('elemname')->item(0)->nodeValue;

			
			$docova_conn = unserialize($_SESSION['docova_session_obj']);
			
			$tmpresult = $docova_conn->getElementHtml($elemtype, $path, $elemname);

			if (!empty($tmpresult))
				$output->loadXML($tmpresult);

			return $output;
		}
		catch (\Exception $e) {
			$result = $e->getMessage();
			return $result;
		}
	}


	/**
	 * Returns specified design element dxl from Domino db
	 *
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceGETDXL($post)
	{
	    $output = new \DOMDocument('1.0', 'UTF-8');
	    $result = "";
	    try {
	        
	        $elemtype = $post->getElementsByTagName('elemtype')->item(0)->nodeValue;
	        $path = $post->getElementsByTagName('path')->item(0)->nodeValue;
	        
	        $elemname = $post->getElementsByTagName('elemname')->item(0)->nodeValue;
	        
	        $tempnode = $post->getElementsByTagName('direct');
	        $direct = (!empty($tempnode) && $tempnode->length > 0 ? ($tempnode->item(0)->nodeValue == "1") : false);
	        
	        $docova_conn = unserialize($_SESSION['docova_session_obj']);
	        
	        $tmpresult = $docova_conn->getElementDXL($elemtype, $path, $elemname);
	        
	        if (!empty($tmpresult))
	            $output->loadXML($tmpresult);
	            
	            return $output;
	    }
	    catch (\Exception $e) {
	        $result = $e->getMessage();
	        return $result;
	    }
	}
	
	private function handleDataTables($em, $doctype, $dtjson, $post)
	{
		
		$appid = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
		$docova = new Docova($this->container);
		$global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$global_settings = $global_settings[0];
		$view_handler = new ViewManipulation($docova, $appid, $global_settings);
		$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appid, 'isApp' => true, 'Trash' => false));
		if (empty($application))
			throw new \Exception('Invalid application to import!');

	
		foreach ( $dtjson as $datatable)
		{
			$dtid = $datatable->id;
			$dt_type= $datatable->dttype;

			$dtform = $dt_type == "local" ? "local" : $datatable->dtform;
			$persxml = "<columns><column><xmlNodeName>Created</xmlNodeName><dataType>date</dataType><sortOrder>ascending</sortOrder></column>";
			$view = $em->getRepository('DocovaBundle:AppViews')->findOneBy(array('application' => $appid, "viewType" => "datatable", "viewName" => $dtid, "datatableformproperties" => $doctype->getId()));

			
			$dttype = "";
			foreach  ( $datatable->fields as $field){
				$fieldid = $field->id;
				$fieldtype = $field->type;

				if ( $fieldtype == "time" || $fieldtype == "datetime" || $fieldtype == "date"){
					$dttype = $fieldtype;
					$fieldtype = "date";
				}

				$persxml .= "<column><xmlNodeName>". $fieldid ."</xmlNodeName><dataType>".$fieldtype."</dataType><responseFormula /><sortOrder>none</sortOrder><dttype>".$dttype."</dttype></column>";
			}
			$persxml .= "</columns>";

			if  ( empty ($view)){
				//create a new view to store the data
				$view = new AppViews();
				$view->setViewName($dtid);
				$view->setViewAlias($dtform); 
				$view->setViewType("datatable");
				$view->setViewQuery("datatable");
				$view->setAutoCollapse(false);
				$view->setViewFtSearch(false);
				$view->setPDU( true );
				$view->setModifiedBy($this->user);
				$view->setDateModified(new \DateTime());
				$view->setCreatedBy($this->user);
				$view->setDateCreated(new \DateTime());
				$view->setApplication($application);
				$view->setViewPerspective($persxml)	;
				$view->setDataTableFormProperties($doctype);
				$em->persist($view);
					
			}else{
				$view->setViewAlias($dtform); 
				$view->setViewPerspective($persxml)	;
				$em->persist($view);
			}

			if (  $dt_type == "local")
			{
				//local type ..nothing to do
				return true;
			}

			$view_id = str_replace('-', '', $view->getId());

			
			$existing = null;
			$exists = $view_handler->viewExists($view_id);
			if ( $exists )
			{
				$existing = $view_handler->getColumnValues($view_id, array("Document_Id", "Parent_Doc") , array ("App_Id" => $appid), null, true);
				$view_handler->deleteView($view_id);
			}
			$perspective_xml = new \DOMDocument();
			$perspective_xml->loadXML(urldecode($persxml));
			$view_handler->createViewTable($view, $perspective_xml);
			
			if ( !empty($existing))
			{
				foreach ( $existing as $existingvals)
				{
					$dockey = $existingvals["Document_Id"];
					$parentdoc = $existingvals["Parent_Doc"];
					$values_array = $em->getRepository('DocovaBundle:Documents')->getDocFieldValues($dockey, $global_settings->getDefaultDateFormat(), $global_settings->getUserDisplayDefault(), true);

					$values_array = $values_array[0];

					if (! $view_handler->indexDataTableDocument($dockey, $values_array, $appid, $view_id, $parentdoc, $persxml)){
							throw new \Exception('Insertion failed!');
					}
				}
			}


		}

		//$doctype->addDataTableViews($view);
		
	}
	

	/**
	 * Create form design elements and twig files
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceCREATEDESIGNELEMENT($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$form = $post->getElementsByTagName('FormUNID')->item(0)->nodeValue;
			if (empty($form)) {
				throw new \Exception('Form ID is missed!');
			}

			$elmType = strtolower($post->getElementsByTagName('DesignElementType')->item(0)->nodeValue);
			if ($elmType == 'form' || $elmType == 'subform')
			{
				$elements = json_decode($post->getElementsByTagName('Elements')->item(0)->nodeValue, true);
				$elmType = $elmType == 'form' ? $elmType : ucfirst($elmType);
				if (true === $msg = $this->upgradeDbDesignElements($form, $elements, $elmType)) 
				{
				    $elmType = strtolower($elmType);
				    										
					$computed_values = $post->getElementsByTagName('computedvalues')->item(0)->nodeValue;
					$default_values = $post->getElementsByTagName('defaultvalues')->item(0)->nodeValue;					
										
					//-- generate standard browser version
					$read_html = $post->getElementsByTagName('readbody')->item(0)->nodeValue;
					$edit_body = $post->getElementsByTagName('editbody')->item(0)->nodeValue;
					if(!empty($read_html) || !empty($edit_body)){
    					$action_bar_read = $post->getElementsByTagName($elmType.'actionbarread')->item(0)->nodeValue;    					
    					$action_bar_read = str_replace(['&lt;','&gt;'], ['<','>'], $action_bar_read);
	   		      		$action_bar_edit = $post->getElementsByTagName($elmType.'actionbaredit')->item(0)->nodeValue;
	   		      		$action_bar_edit = str_replace(['&lt;','&gt;'], ['<','>'], $action_bar_edit);
		  			    if (!$this->generateTwigs($form, $read_html, $edit_body, $action_bar_read, $action_bar_edit, $elmType, $computed_values, $default_values)) {
						      throw new \Exception('Could not generate the twig files!');
					    }
					}
					
				    //-- generate mobile version
					$read_html_m = $post->getElementsByTagName('readbody_m')->item(0)->nodeValue;
					$edit_body_m = $post->getElementsByTagName('editbody_m')->item(0)->nodeValue;
					if(!empty($read_html_m) || !empty($edit_body_m)){
    					$action_bar_read_m = $post->getElementsByTagName($elmType.'actionbarread_m')->item(0)->nodeValue;
    					$action_bar_read_m = str_replace(['&lt;','&gt;'], ['<','>'], $action_bar_read_m);
    					$action_bar_edit_m = $post->getElementsByTagName($elmType.'actionbaredit_m')->item(0)->nodeValue;	
    					$action_bar_edit_m = str_replace(['&lt;','&gt;'], ['<','>'], $action_bar_edit_m);
    					if (!$this->generateTwigs($form, $read_html_m, $edit_body_m, $action_bar_read_m, $action_bar_edit_m, $elmType, $computed_values, $default_values, true)) {
	       				    throw new \Exception('Could not generate the mobile twig files!');
			 		  }
					}
				}
				else {
					throw new \Exception($msg);
				}
			}else if ( $elmType == "page"){
			    //-- generate standard browser version
				$read_html = $post->getElementsByTagName('readbody')->item(0)->nodeValue;
				$edit_body = $post->getElementsByTagName('editbody')->item(0)->nodeValue;
				$action_bar = $post->getElementsByTagName('formactionbaredit')->item(0)->nodeValue;
				$action_bar = str_replace(['&lt;','&gt;'], ['<','>'], $action_bar);
				if (!$this->generateTwigs($form, $read_html, $edit_body, "",$action_bar, $elmType)) {
					throw new \Exception('Could not generate the twig files!');
				}
				
				//-- generate mobile version
				$read_html_m = $post->getElementsByTagName('readbody_m')->item(0)->nodeValue;
				$edit_body_m = $post->getElementsByTagName('editbody_m')->item(0)->nodeValue;
				if(!empty($read_html_m) || !empty($edit_body_m)){
				    $action_bar_m = $post->getElementsByTagName('formactionbaredit_m')->item(0)->nodeValue;
				    if (!$this->generateTwigs($form, $read_html_m, $edit_body_m, "", $action_bar_m, $elmType, "", "", true)) {
				        throw new \Exception('Could not generate the mobile twig files!');
				    }
				}
			}else if ( $elmType == "outline"){
				$outlinetwig = $post->getElementsByTagName('twigoutline')->item(0)->nodeValue;
				//-- special case for outlines since twigoutline element is CDATA wrapped so some elements will not be auto unescaped
				$outlinetwig = str_replace(['&lt;','&gt;'], ['<','>'], $outlinetwig);
				
				if (!$this->generateTwigs($form, $outlinetwig, "", "","", $elmType)) {
					throw new \Exception('Could not generate the twig files!');
				}
			}
			else if ($elmType == 'widget') {
				$twig_content = $post->getElementsByTagName('widgethtml')->item(0)->nodeValue;
				if (!$this->generateTwigs($form, $twig_content, '', '', '', 'widget')) {
					throw new \Exception('Could not generate widget twig file!');
				}
			}

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', 'SUCCESS');
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Import an app data from domino
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceIMPORTAPPDATA($post)
	{
		$response = null;
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$migrator_path = $this->container->getParameter('datamigrator_path');
			$em = $this->getDoctrine()->getManager();
			$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $post->getElementsByTagName('AppId')->item(0)->nodeValue, 'isApp' => true, 'Trash' => false));
			if (empty($application))
				throw new \Exception('Invalid application to import!');
					
			$xml = new \DOMDocument('1.0', 'UTF-8');
			$xml->load(realpath($migrator_path . 'DOCOVA_SE_Migrator.exe.config'));
			$se_host = trim($post->getElementsByTagName('DocovaHost')->item(0)->nodeValue);
			$se_user = trim($post->getElementsByTagName('DocovaUser')->item(0)->nodeValue);
			$se_password = trim($post->getElementsByTagName('DocovaPass')->item(0)->nodeValue);
			$se_webroot = trim($post->getElementsByTagName('DocovaWebPath')->item(0)->nodeValue);
			$se_attachment = trim($post->getElementsByTagName('AttPath')->item(0)->nodeValue);
			$domino_path = trim($post->getElementsByTagName('DominoPath')->item(0)->nodeValue);
			$domino_user = trim($post->getElementsByTagName('DominoUser')->item(0)->nodeValue);
			$domino_password = trim($post->getElementsByTagName('DominoPass')->item(0)->nodeValue);
			$source_app = trim($post->getElementsByTagName('Library')->item(0)->nodeValue);
			$docova_dws = trim($post->getElementsByTagName('WebServiceUrl')->item(0)->nodeValue);
			$xpath = new \DOMXPath($xml);
			if (!empty($se_host))
				$xpath->query('//setting[@name="TEITRHost"]/value')->item(0)->nodeValue = $se_host;
			if (!empty($se_user))
				$xpath->query('//setting[@name="TEITRUser"]/value')->item(0)->nodeValue = $se_user;
			if (!empty($se_password))
				$xpath->query('//setting[@name="TEITRPassword"]/value')->item(0)->nodeValue = $se_password;
			if (!empty($se_webroot))
				$xpath->query('//setting[@name="TEITRWebRoot"]/value')->item(0)->nodeValue = $se_webroot;
			if (!empty($se_attachment))
				$xpath->query('//setting[@name="TEITRAttachmentPath"]/value')->item(0)->nodeValue = $se_attachment;
			if (!empty($domino_path))
				$xpath->query('//setting[@name="DOCOVAHome"]/value')->item(0)->nodeValue = $domino_path;
			if (!empty($domino_user))
				$xpath->query('//setting[@name="DOCOVAUser"]/value')->item(0)->nodeValue = $domino_user;
			if (!empty($domino_password))
				$xpath->query('//setting[@name="DOCOVAPassword"]/value')->item(0)->nodeValue = $domino_password;
			if (!empty($docova_dws))
				$xpath->query('//setting[@name="DOCOVATEITRMigrator_DWS_DOCOVAWebServices"]/value')->item(0)->nodeValue = $docova_dws;
			
			file_put_contents(realpath($migrator_path . 'DOCOVA_SE_Migrator.exe.config'), $xml->saveXML());
			
			$job_id = md5(strval(microtime()));

			chdir($this->container->getParameter('datamigrator_path'));
			if (substr(php_uname(), 0, 7) == "Windows"){ 
				$res = popen("start /B migrator.bat \"".trim($source_app).'" '.$application->getId().' '.$job_id, 'r');
				if($res !== false) {
					pclose($res);
					$res = false;
				}
			}
			else { 
				$res = exec('migrator.bat "'.trim($source_app).'" '.$application->getId().' '.$job_id, $response);   
			}
			//$res = system('migrator.bat "'.trim($source_app).'" '.$application->getId(), $response);
			if (!empty($res))
				throw new \Exception('Data migration failed on windows. Contact admin or check the logs');
			elseif (!empty($response))
				throw new \Exception(trim($response));

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', 'SUCCESS');
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $job_id);
			$att = $output->createAttribute('ID');
			$att->value = 'Ret2';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Install the assets
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceINSTALLASSET($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$appid = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			
			$application = new Application($this->get('kernel'));
			$application->setAutoExit(false);
			
			$input = new ArrayInput(array(
					'command' => 'docova:appassetsinstall',
					'appid' => $appid
			));
			
			$retcode = $application->run($input, new NullOutput());
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', ($retcode == 0 ? 'SUCCESS' : 'FAILURE'));
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Check existence of a design element in the app
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceCHECKEXISTENCE($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$appid = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$type = trim($post->getElementsByTagName('ElementType')->item(0)->nodeValue);
			$filename = trim($post->getElementsByTagName('ElementName')->item(0)->nodeValue);
			if (empty($filename)) {
				$filename = trim($post->getElementsByTagName('ElementAlias')->item(0)->nodeValue);
			}
			$filename = str_replace(array('/', '\\'), '-', $filename);
			$filename = str_replace(' ', '', $filename);
			$exist = false;
			
			switch ($type)
			{
				case 'agent':
					$filename = str_replace('-', '', $filename);
					$exist = file_exists($this->appPath . $appid . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'AGENTS' . DIRECTORY_SEPARATOR . $filename . '.html.twig');
					break;
				case 'scriptlibrary':
					$appdir = 'A'.str_replace('-', '', $appid);
					$exist = file_exists($this->appPath . '../../../Agents'.DIRECTORY_SEPARATOR . $appdir . "/ScriptLibraries". DIRECTORY_SEPARATOR . $filename . '.php');
					break;
				case 'css':
					$exist = file_exists($this->assetPath.'/css/custom/'.$appid.'/'.$filename.'.css');
					break;
				case 'js':
				case 'jslibrary':
				case 'javascript':
					$exist = file_exists($this->assetPath.'/js/custom/'.$appid.'/'.$filename.'.js');
					break;
				case 'form':
				case 'page':
				case 'subform':
					$exist = file_exists($this->appPath. $appid .DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR. strtoupper($type) .DIRECTORY_SEPARATOR. $filename .'.html.twig');
					break;
				case 'outline':
					$exist = file_exists($this->appPath. $appid .DIRECTORY_SEPARATOR.'outline'.DIRECTORY_SEPARATOR. $filename .'.html.twig');
					break;
				case 'workflow':
					$filename = trim($post->getElementsByTagName('ElementAlias')->item(0)->nodeValue);
					$filename = str_replace(array('/', '\\'), '-', $filename);
					$exist = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Workflow')->workflowExists($appid, $filename);
					break;
				case 'widget':
					$exist = file_exists($this->appPath .'widgets'. DIRECTORY_SEPARATOR. $filename . '.html.twig');
					break;
			}
			
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', ($exist === false ? 'NODUPLICATE' : 'DUPLICATED'));
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Check access control for editing a widget
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceCANEDITWIDGET($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$access = false;
			$widget = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$widget = $em->getRepository('DocovaBundle:Widgets')->find($widget);
			if (!empty($widget)) {
				if ($this->user->getUserProfile()->getCanCreateApp() || $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
				{
					$access = true;
				}
			}

			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', ($access === false ? 'NOACCESS' : 'ACCESSIBLE'));
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Toggle widget status (active/inactive)
	 * 
	 * @param \DOMDocument $post
	 * @return \DOMDocument
	 */
	private function dserviceTOGGLEWIDGETSTATUS($post)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		try {
			$widget = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$widget = $em->getRepository('DocovaBundle:Widgets')->find($widget);
			if (empty($widget)) {
				throw new \Exception('Unspecified widget source ID.');
			}
			
			$activated = false;
			if ($widget->getInactive()) {
				$widget->setInactive(false);
				$activated = true;
			}
			else {
				$widget->setInactive(true);
			}
			
			$em->flush();
		
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', ($activated === false ? 'DEACTIVATED' : 'ACTIVATED'));
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Save form design elements
	 * @param \DOMDocument $post
	 * @return \Docova\DocovaBundle\Entity\AppForms|string
	 */
	private function saveFormDesignElements($post, &$oldname = null)
	{
		try {
			$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appId, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application source ID.');
			}
			$form_id = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($form_id)) 
			{
				$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' => $form_id, 'application' => $appId, 'trash' => false));
				if (empty($form)) 
				{
					throw new \Exception('Unspecifed application form source ID.');
				}
				$oldname = $form->getFormName();
				if (strtolower($oldname) == strtolower(trim($post->getElementsByTagName('Name')->item(0)->nodeValue))) {
					$oldname = null;
				}
			}
			else {
				$name = trim($post->getElementsByTagName('Name')->item(0)->nodeValue);
				$alias = trim($post->getElementsByTagName('Alias')->item(0)->nodeValue);
				$form = $em->getRepository('DocovaBundle:AppForms')->findTrashedForm($appId, $name, $alias);
				if (empty($form))
					$form = new AppForms();
				else 
					$form->setTrash(false);
			}
			$form->setFormName(trim($post->getElementsByTagName('Name')->item(0)->nodeValue));
			$form->setFormAlias(trim($post->getElementsByTagName('Alias')->item(0)->nodeValue) ? trim($post->getElementsByTagName('Alias')->item(0)->nodeValue) : null);
			if ( !empty($post->getElementsByTagName('ProhibitDesignUpdate')->length))
				$form->setPDU(trim($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue) == 'PDU' ? true : false);
			else
				$form->setPDU(true);

			if ( !empty($post->getElementsByTagName('AddCSS')->length))
				$form->setCSSFilename(trim($post->getElementsByTagName('AddCSS')->item(0)->nodeValue) ? trim($post->getElementsByTagName('AddCSS')->item(0)->nodeValue) : null);

			$form->setJSFilename(trim($post->getElementsByTagName('AddJS')->item(0)->nodeValue) ? trim($post->getElementsByTagName('AddJS')->item(0)->nodeValue) : null);
			$form->setDateModified(new \DateTime());
			$form->setModifiedBy($this->user);
			
			if (empty($form_id)) {
				$form->setDateCreated(new \DateTime());
				$form->setCreatedBy($this->user);
				$form->setApplication($app);
				$em->persist($form);
			}
			$em->flush();
			
			return $form;
		}
		catch (\Exception $e) {
			return  $e->getMessage();
		}
	}
	
	/**
	 * Save page design elements
	 * @param \DOMDocument $post
	 * @return \Docova\DocovaBundle\Entity\AppPages|string
	 */
	private function savePageDesignElements($post)
	{
		try {
			$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appId, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application souce ID.');
			}
			$page_id = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($page_id))
			{
				$page = $em->getRepository('DocovaBundle:AppPages')->findOneBy(array('id' => $page_id, 'application' => $appId));
				if (empty($page))
				{
					throw new \Exception('Unspecifed application form source ID.');
				}
			}
			else {
				$page = new AppPages();
			}
			
			$page->setPageName($post->getElementsByTagName('Name')->item(0)->nodeValue);
			$page->setPageAlias(trim($post->getElementsByTagName('Alias')->item(0)->nodeValue) ? trim($post->getElementsByTagName('Alias')->item(0)->nodeValue) : null);
			if ( !empty($post->getElementsByTagName('PageBackgroundColor')->item(0)->nodeValue) )
				$bgColor = trim($post->getElementsByTagName('PageBackgroundColor')->item(0)->nodeValue);
			$page->setBackgroundColor(!empty($bgColor) ? $bgColor : null);
			$page->setPDU(trim($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue) == 'PDU' ? true : false);
			$page->setDateModified(new \DateTime());
			$page->setModifiedBy($this->user);
			
			if (empty($page_id)) {
				$page->setDateCreated(new \DateTime());
				$page->setCreatedBy($this->user);
				$page->setApplication($app);
				$em->persist($page);
			}
			$em->flush();
			$bgColor = $page_id = null;

			return $page;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	
	/**
	 * Save subform design elements
	 * @param \DOMDocument $post
	 * @return \Docova\DocovaBundle\Entity\Subforms|string
	 */
	private function saveSubformDesignElements($post)
	{
		try {
			$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appId, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application souce ID.');
			}
			$subform_id = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($subform_id))
			{
				$subform = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('id' => $subform_id, 'application' => $appId, 'Is_Custom' => true));
				if (empty($subform))
				{
					throw new \Exception('Unspecifed application subform source ID.');
				}
			}
			else {
				$subform = new Subforms();
			}
				
			$subform->setFormFileName($post->getElementsByTagName('Name')->item(0)->nodeValue);
			$subform->setFormName(trim($post->getElementsByTagName('Alias')->item(0)->nodeValue) ? trim($post->getElementsByTagName('Alias')->item(0)->nodeValue) :$post->getElementsByTagName('Name')->item(0)->nodeValue);
			$subform->setPDU(trim($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue) == 'PDU' ? true : false);
			$subform->setDateModified(new \DateTime());
			$subform->setModifiedBy($this->user);
				
			if (empty($subform_id)) {
				$subform->setDateCreated(new \DateTime());
				$subform->setCreator($this->user);
				$subform->setApplication($app);
				$subform->setIsCustom(true);
				$em->persist($subform);
			}
			$em->flush();
		
			return $subform;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	
	/**
	 * Save outline design elements
	 * @param \DOMDocument $post
	 * @return \Docova\DocovaBundle\Entity\AppOutlines|string
	 */
	private function saveOutlineDesignElements($post)
	{
		try {
			$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appId, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application souce ID.');
			}
			$outline_id = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($outline_id))
			{
				$outline = $em->getRepository('DocovaBundle:AppOutlines')->findOneBy(array('id' => $outline_id, 'application' => $appId));
				if (empty($outline))
				{
					throw new \Exception('Unspecifed application form source ID.');
				}
			}
			else {
				$outline = new AppOutlines();
			}
				
			$outline->setOutlineName($post->getElementsByTagName('Name')->item(0)->nodeValue);
			$outline->setOutlineAlias(trim($post->getElementsByTagName('Alias')->item(0)->nodeValue) ? trim($post->getElementsByTagName('Alias')->item(0)->nodeValue) : null);
			$outline->setOutlineType(!empty($post->getElementsByTagName('OutlineType')->item(0)->nodeValue) ? trim($post->getElementsByTagName('OutlineType')->item(0)->nodeValue) : 'B');
			$outline->setPDU(trim($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue) == 'PDU' ? true : false);
			$outline->setDateModified(new \DateTime());
			$outline->setModifiedBy($this->user);
				
			if (empty($outline_id)) {
				$outline->setDateCreated(new \DateTime());
				$outline->setCreatedBy($this->user);
				$outline->setApplication($app);
				$em->persist($outline);
			}
			$em->flush();
		
			return $outline;
		}
		catch (\Exception $e) {
			return $e->getMessage() . ' on line '.$e->getLine().' in '.$e->getFile();
		}
	}
	
	/**
	 * Save JavaScript design elements
	 * 
	 * @param \DOMDocument $post
	 * @return \Docova\DocovaBundle\Entity\AppPhpScripts|string
	 */
	private function savePhpScriptDesignElement($post) 
	{
		try {
			$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appId, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application souce ID.');
			}
			$php_id = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($php_id))
			{
				$php = $em->getRepository('DocovaBundle:AppPhpScripts')->findOneBy(array('id' => $php_id, 'application' => $appId));
				if (empty($php))
				{
					throw new \Exception('Unspecifed application form source ID.');
				}
			}
			else {
				$php = new AppPhpScripts();
			}
		
			$php->setPhpName($post->getElementsByTagName('Name')->item(0)->nodeValue);
			$php->setPDU(trim($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue) == 'PDU' ? true : false);
			$php->setDateModified(new \DateTime());
			$php->setModifiedBy($this->user);
		
			if (empty($php_id)) {
				$php->setDateCreated(new \DateTime());
				$php->setCreatedBy($this->user);
				$php->setApplication($app);
				$em->persist($php);
			}
			$em->flush();
		
			return $php;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}


	/**
	 * Save JavaScript design elements
	 * 
	 * @param \DOMDocument $post
	 * @return \Docova\DocovaBundle\Entity\AppJavaScripts|string
	 */
	private function saveJsDesignElement($post) 
	{
		try {
			$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appId, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application souce ID.');
			}
			$js_id = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($js_id))
			{
				$js = $em->getRepository('DocovaBundle:AppJavaScripts')->findOneBy(array('id' => $js_id, 'application' => $appId));
				if (empty($js))
				{
					throw new \Exception('Unspecifed application form source ID.');
				}
			}
			else {
				$js = new AppJavaScripts();
			}
		
			$js->setJsName($post->getElementsByTagName('Name')->item(0)->nodeValue);
			$js->setPDU(trim($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue) == 'PDU' ? true : false);
			$js->setDateModified(new \DateTime());
			$js->setModifiedBy($this->user);
		
			if (empty($js_id)) {
				$js->setDateCreated(new \DateTime());
				$js->setCreatedBy($this->user);
				$js->setApplication($app);
				$em->persist($js);
			}
			$em->flush();
		
			return $js;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Save agent design elements
	 *
	 * @param \DOMDocument $post
	 * @return \Docova\DocovaBundle\Entity\AppAgents|string
	 */
	private function saveAgentDesignElement($post)
	{
		try {
			$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appId, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application souce ID.');
			}
			$agent_id = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($agent_id))
			{
				$agent = $em->getRepository('DocovaBundle:AppAgents')->findOneBy(array('id' => $agent_id, 'application' => $appId));
				if (empty($agent))
				{
					throw new \Exception('Unspecifed application form source ID.');
				}
			}
			else {
				$agent = new AppAgents();
			}
	
			$agent->setAgentName($post->getElementsByTagName('Name')->item(0)->nodeValue);
			$agent->setAgentAlias(trim($post->getElementsByTagName('Alias')->item(0)->nodeValue) ? $post->getElementsByTagName('Alias')->item(0)->nodeValue : null);
			$agent->setAgentType($post->getElementsByTagName('SubType')->item(0)->nodeValue == 'PHP' ? 1 : 0);
			$agent->setPDU(trim($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue) == 'PDU' ? true : false);
			$agent->setDateModified(new \DateTime());
			$agent->setModifiedBy($this->user);
			$schedule = $post->getElementsByTagName('agentschedule')->item(0)->nodeValue;
			$agent->setAgentSchedule(!empty($schedule) ? strval($schedule) : '0');
			$agent->setRuntimeSecurityLevel(intval($post->getElementsByTagName('runtimesecuritylevel')->item(0)->nodeValue));
			if (!empty($schedule))
			{
				if ($schedule == '1') {
					$agent->setIntervalHours(intval($post->getElementsByTagName('intervalhours')->item(0)->nodeValue));
					$agent->setIntervalMinutes(intval($post->getElementsByTagName('intervalminutes')->item(0)->nodeValue));
				}
				elseif ($schedule == 'D' || $schedule == 'W' || $schedule == 'M')
				{
					if ($schedule == 'W')
						$agent->setStartWeekDay($post->getElementsByTagName('startweekday')->item(0)->nodeValue);
					elseif ($schedule == 'M')
						$agent->setStartDayOfMonth(intval($post->getElementsByTagName('startdayofmonth')->item(0)->nodeValue));
					
					$agent->setStartHour(intval($post->getElementsByTagName('starthour')->item(0)->nodeValue));
					$agent->setStartMinutes(intval($post->getElementsByTagName('startminutes')->item(0)->nodeValue));
					$agent->setStartHourAmPm(intval($post->getElementsByTagName('starthourampm')->item(0)->nodeValue));
				}
				$agent->setRunAgentAs('A');
			}
			else {
				$agent->setRunAgentAs($post->getElementsByTagName('runas')->item(0)->nodeValue);
			}
	
			if (empty($agent_id)) {
				$agent->setDateCreated(new \DateTime());
				$agent->setCreatedBy($this->user);
				$agent->setApplication($app);
				$em->persist($agent);
			}
			$em->flush();
	
			return $agent;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Save Css design elements
	 *
	 * @param \DOMDocument $post
	 * @return \Docova\DocovaBundle\Entity\AppCss|string
	 */
	private function saveCssDesignElement($post)
	{
		try {
			$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $appId, 'Trash' => false, 'isApp' => true));
			if (empty($app)) {
				throw new \Exception('Unspecified application souce ID.');
			}
			$css_id = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($css_id))
			{
				$css = $em->getRepository('DocovaBundle:AppCss')->findOneBy(array('id' => $css_id, 'application' => $appId));
				if (empty($css))
				{
					throw new \Exception('Unspecifed application form source ID.');
				}
			}
			else {
				$css = new AppCss();
			}
	
			$css->setCssName($post->getElementsByTagName('Name')->item(0)->nodeValue);
			$css->setPDU(trim($post->getElementsByTagName('ProhibitDesignUpdate')->item(0)->nodeValue) == 'PDU' ? true : false);
			$css->setDateModified(new \DateTime());
			$css->setModifiedBy($this->user);
	
			if (empty($css_id)) {
				$css->setDateCreated(new \DateTime());
				$css->setCreatedBy($this->user);
				$css->setApplication($app);
				$em->persist($css);
			}
			$em->flush();
	
			return $css;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	
	/**
	 * Save widget elements
	 * 
	 * @param \DOMDocument $post
	 * @return \Docova\DocovaBundle\Entity\Widgets
	 */
	private function saveWidgetDesignElement($post)
	{
		try {
			$em = $this->getDoctrine()->getManager();
			$widget_id = $post->getElementsByTagName('Unid')->item(0)->nodeValue;
			if (!empty($widget_id))
			{
				$widget = $em->getRepository('DocovaBundle:Widgets')->findOneBy(array('id' => $widget_id, 'isCustom' => true));
				if (empty($widget))
				{
					throw new \Exception('Unspecifed widget source ID.');
				}
			}
			else {
				$widget = new Widgets();
			}
			$name = $post->getElementsByTagName('Name')->item(0)->nodeValue;
			$filename = str_replace(array('/', '\\'), '-', $name);
			$filename = str_replace(' ', '', $filename);

			$widget->setWidgetName($post->getElementsByTagName('Name')->item(0)->nodeValue);
			$widget->setSubformName('sfCustom-'.$filename);
			$widget->setSubformAlias($filename);
			$widget->setDescription($post->getElementsByTagName('WidgetDesc')->item(0)->nodeValue);
			$widget->setIsCustom(true);
			$widget->setDateModified(new \DateTime());
			$widget->setModifiedBy($this->user);
			if (empty($widget_id)) {
				$em->persist($widget);
			}
			$em->flush();
			
			return $widget;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Save design templates base on type
	 * @param \DOMDocument $post
	 * @param object $record
	 * @param string $type
	 * @param string $oldname
	 * @return \DOMDocument
	 */
	private function saveTemplate($post, $record, $type, $oldname = null)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
		$name = trim($post->getElementsByTagName('Name')->item(0)->nodeValue);
		$name = str_replace(array('/', '\\'), '-', $name);
		$name = str_replace(' ', '', $name);
		$html = $post->getElementsByTagName('HTML')->item(0)->nodeValue;
		$html_m = null;
		if (!empty($oldname))
		{
			$oldname = str_replace(array('/', '\\'), '-', $oldname);
			$oldname = str_replace(' ', '', $oldname);
		}
		
		if($post->getElementsByTagName('HTML_m')->length > 0){
       			$html_m = $post->getElementsByTagName('HTML_m')->item(0)->nodeValue;
		}
		
		if ($type == 'Outline') {
			if (!is_dir($this->appPath . $appId . DIRECTORY_SEPARATOR . 'outline')) {
				mkdir($this->appPath . $appId . DIRECTORY_SEPARATOR . 'outline', 0775, true);
			}			
		}
		elseif ($type == 'Widget') {
			if (!is_dir($this->appPath. 'Widgets')) {
				mkdir($this->appPath. 'Widgets', 0775, true);
			}
		}
		elseif (!is_dir($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . strtoupper($type))) {
			mkdir($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . strtoupper($type), 0775, true);
		}
		
		if ($type == 'Outline') {
			$html = $post->getElementsByTagName('Code')->item(0)->nodeValue;
	
			file_put_contents($this->appPath . $appId . DIRECTORY_SEPARATOR . 'outline' . DIRECTORY_SEPARATOR . $name . '_m.html.twig', $html);
			
			$html = "<script type='text/javascript'>\n var outlineJson=" . $html . ";</script>";
			file_put_contents($this->appPath . $appId . DIRECTORY_SEPARATOR . 'outline' . DIRECTORY_SEPARATOR . $name . '.html.twig', $html);

			$css = $post->getElementsByTagName('CSS')->item(0)->nodeValue;
			if (!empty($css)){
				//save any custom css
				if (!is_dir($this->appPath . '../../public/css/custom' . DIRECTORY_SEPARATOR . $appId. DIRECTORY_SEPARATOR. 'outlines')) {
							mkdir($this->appPath . '../../public/css/custom' . DIRECTORY_SEPARATOR . $appId.DIRECTORY_SEPARATOR.'outlines', 0775, true);
				}
				file_put_contents($this->appPath . '../../public/css/custom' . DIRECTORY_SEPARATOR . $appId . DIRECTORY_SEPARATOR.'outlines'. DIRECTORY_SEPARATOR. $name . '.css', $css);

				//-- copy the files to assets directory
				if (!is_dir($this->assetPath.'/css/custom/'.$appId. DIRECTORY_SEPARATOR.'outlines')) {
					mkdir($this->assetPath.'/css/custom/'.$appId. DIRECTORY_SEPARATOR.'outlines', 0775, true);
				}
				file_put_contents($this->assetPath.'/css/custom/'.$appId. DIRECTORY_SEPARATOR.'outlines'. DIRECTORY_SEPARATOR.$name.'.css', $css);					
			}
		}
		elseif ($type == 'Widget') {
			file_put_contents($this->appPath. 'Widgets'. DIRECTORY_SEPARATOR. "sfCustom-$name".'.html.twig', $html);
		}
		else {
			if (!empty($oldname)) {
				@unlink($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . strtoupper($type) . DIRECTORY_SEPARATOR . $oldname . '.html.twig');
				@unlink($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . strtoupper($type) . DIRECTORY_SEPARATOR . $oldname . '_m.html.twig');
				@unlink($this->appPath. $appId .DIRECTORY_SEPARATOR. $oldname .'_computed.html.twig');
				@unlink($this->appPath. $appId .DIRECTORY_SEPARATOR. $oldname .'_default.html.twig');
				@unlink($this->appPath. $appId .DIRECTORY_SEPARATOR. $oldname .'.html.twig');
				@unlink($this->appPath. $appId .DIRECTORY_SEPARATOR. $oldname .'_read.html.twig');
				@unlink($this->appPath. $appId .DIRECTORY_SEPARATOR. $oldname .'_m.html.twig');
				@unlink($this->appPath. $appId .DIRECTORY_SEPARATOR. $oldname .'_m_read.html.twig');
			}
			
			file_put_contents($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . strtoupper($type) . DIRECTORY_SEPARATOR . $name . '.html.twig', $html);
			if(!empty($html_m)){
			    file_put_contents($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . strtoupper($type) . DIRECTORY_SEPARATOR . $name . '_m.html.twig', $html_m);
			}
		}

		if (!empty($post->getElementsByTagName('CSS')->item(0)->nodeValue)) {
			$css = $post->getElementsByTagName('CSS')->item(0)->nodeValue;
			
			$cssArr = explode("!!----!!", $css);
			if ( count($cssArr) == 2)
			{
				$jsonCode = $cssArr[0];
				$cssCode = $cssArr[1];

				if (!is_dir($this->appPath . '../../public/css/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type))) {
					mkdir($this->appPath . '../../public/css/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type), 0775, true);
				}
				if (!empty($oldname)) {
					@unlink($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . strtoupper($type) . DIRECTORY_SEPARATOR . $oldname . '_style.json');
					@unlink($this->appPath . '../../public/css/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type).DIRECTORY_SEPARATOR . $oldname . '.css');
					@unlink($this->assetPath.'/css/custom/'.$appId.'/'.strtoupper($type).'/'.$oldname.'.css');
				}
				file_put_contents($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . strtoupper($type) . DIRECTORY_SEPARATOR . $name . '_style.json', $jsonCode);

				if (!is_dir($this->appPath . '../../public/css/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type))) {
					mkdir($this->appPath . '../../public/css/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type), 0775, true);
				}
				file_put_contents($this->appPath . '../../public/css/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type).DIRECTORY_SEPARATOR . $name . '.css', $cssCode);

				//-- copy the files to assets directory
				if (!is_dir($this->assetPath.'/css/custom/'.$appId.'/'.strtoupper($type))) {
					mkdir($this->assetPath.'/css/custom/'.$appId.'/'.strtoupper($type), 0775, true);
				}
				file_put_contents($this->assetPath.'/css/custom/'.$appId.'/'.strtoupper($type).'/'.$name.'.css', $cssCode);		
			}	
		}
		
		if (!empty($post->getElementsByTagName('Code')->item(0)->nodeValue)) {
			$js = urldecode(base64_decode($post->getElementsByTagName('Code')->item(0)->nodeValue));
			$org_js = null;
			if (!empty($post->getElementsByTagName('HeadCode')->item(0)->nodeValue))
				$org_js = urldecode(base64_decode($post->getElementsByTagName('HeadCode')->item(0)->nodeValue));
			if (!is_dir($this->appPath . '../../public/js/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type))) {
				mkdir($this->appPath . '../../public/js/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type), 0775, true);
			}
			if (!empty($oldname))
			{
				@unlink($this->appPath . '../../public/js/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type).DIRECTORY_SEPARATOR . $oldname . '.js');
				@unlink($this->appPath . '../../public/js/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type).DIRECTORY_SEPARATOR . 'TEMPLATE_'.$oldname . '.js');
				@unlink($this->assetPath.'/js/custom/'.$appId.'/'.strtoupper($type).'/'.$oldname.'.js');
			}
			file_put_contents($this->appPath . '../../public/js/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type).DIRECTORY_SEPARATOR . $name . '.js', $js);
			if (!empty($org_js)) {
				file_put_contents($this->appPath . '../../public/js/custom'.DIRECTORY_SEPARATOR.$appId.DIRECTORY_SEPARATOR.strtoupper($type).DIRECTORY_SEPARATOR . 'TEMPLATE_'.$name . '.js', $org_js);
			}

			//-- copy the files to assets directory
			if (!is_dir($this->assetPath.'/js/custom/'.$appId.'/'.strtoupper($type))) {
				mkdir($this->assetPath.'/js/custom/'.$appId.'/'.strtoupper($type), 0775, true);
			}
			file_put_contents($this->assetPath.'/js/custom/'.$appId.'/'.strtoupper($type).'/'.$name.'.js', $js);
		}
			
		$root = $output->appendChild($output->createElement('Results'));
		$newnode = $output->createElement('Result', 'OK');
		$att = $output->createAttribute('ID');
		$att->value = 'Status';
		$newnode->appendChild($att);
		$root->appendChild($newnode);
		$newnode = $output->createElement('Result', $record->getId());
		$att = $output->createAttribute('ID');
		$att->value = 'Ret1';
		$newnode->appendChild($att);
		$root->appendChild($newnode);
		
		return $output;
	}
	
	/**
	 * Generate a system file for the user code base on type and return XML response on success
	 * 
	 * @param \DOMDocument $post
	 * @param object $record
	 * @param string $type
	 * @return \DOMDocument
	 */
	private function saveScripts($post, $record, $type)
	{
		$output = new \DOMDocument('1.0', 'UTF-8');
		$appId = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
		$name = trim($post->getElementsByTagName('Name')->item(0)->nodeValue);
		$name = str_replace(array('/', '\\'), '-', $name);
		$name = str_replace(' ', '', $name);
		$script = $type != 'Css' ? trim($post->getElementsByTagName('Code')->item(0)->nodeValue) : trim($post->getElementsByTagName('CSS')->item(0)->nodeValue);
		
		//if its an agent then script can be empty...i.e.empty body
		if (!empty($script) || $type == "Agent")
		{
			$script = urldecode(base64_decode($script));
			switch ($type)
			{
				case 'JS':
					if (!is_dir($this->appPath . '../../public/js/custom' . DIRECTORY_SEPARATOR . $appId)) {
						mkdir($this->appPath . '../../public/js/custom' . DIRECTORY_SEPARATOR . $appId, 0775, true);
					}

					file_put_contents($this->appPath . '../../public/js/custom' . DIRECTORY_SEPARATOR . $appId . DIRECTORY_SEPARATOR . $name . '.js', $script);
					
					//-- copy the files to assets directory
					if (!is_dir($this->assetPath.'/js/custom/'.$appId)) {
						mkdir($this->assetPath.'/js/custom/'.$appId, 0775, true);
					}
					file_put_contents($this->assetPath.'/js/custom/'.$appId.'/'.$name.'.js', $script);

					break;
				case 'Agent':
					$appdir = 'A'.str_replace('-', '', $appId);
					$script = $this->generateAgentForCode($record, $post, $appdir);
					if (!is_dir($this->appPath . '../../../Agents'.DIRECTORY_SEPARATOR . $appdir)) {
						mkdir($this->appPath . '../../../Agents'.DIRECTORY_SEPARATOR . $appdir, 0775, true);
					}
					if (!is_dir($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'AGENTS')) {
						mkdir($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'AGENTS', 0775, true);
					}
					$name = str_replace('-', '', $name);
					$user_code = urldecode(base64_decode($post->getElementsByTagName('usercode')->item(0)->nodeValue));
					file_put_contents($this->appPath . $appId . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'AGENTS' . DIRECTORY_SEPARATOR . $name . '.html.twig', $user_code);
					file_put_contents($this->appPath . '../../../Agents'.DIRECTORY_SEPARATOR . $appdir . DIRECTORY_SEPARATOR . $name . '.php', $script);
					break;
				case 'Php':
					$appdir = 'A'.str_replace('-', '', $appId);
					if (!is_dir($this->appPath . '../../../Agents'.DIRECTORY_SEPARATOR . $appdir."/ScriptLibraries")) {
						mkdir($this->appPath . '../../../Agents'.DIRECTORY_SEPARATOR . $appdir."/ScriptLibraries", 0775, true);
					}


					file_put_contents($this->appPath . '../../../Agents'.DIRECTORY_SEPARATOR . $appdir . "/ScriptLibraries". DIRECTORY_SEPARATOR . $name . '.php', $script);
					break;
				case 'Css':
					if (!is_dir($this->appPath . '../../public/css/custom' . DIRECTORY_SEPARATOR . $appId)) {
						mkdir($this->appPath . '../../public/css/custom' . DIRECTORY_SEPARATOR . $appId, 0775, true);
					}
					file_put_contents($this->appPath . '../../public/css/custom' . DIRECTORY_SEPARATOR . $appId . DIRECTORY_SEPARATOR . $name . '.css', $script);

					//-- copy the files to assets directory
					if (!is_dir($this->assetPath.'/css/custom/'.$appId)) {
						mkdir($this->assetPath.'/css/custom/'.$appId, 0775, true);
					}
					file_put_contents($this->assetPath.'/css/custom/'.$appId.'/'.$name.'.css', $script);					
					
					break;
			}
		}
		
		$root = $output->appendChild($output->createElement('Results'));
		$newnode = $output->createElement('Result', 'OK');
		$att = $output->createAttribute('ID');
		$att->value = 'Status';
		$newnode->appendChild($att);
		$root->appendChild($newnode);
		$newnode = $output->createElement('Result', $record->getId());
		$att = $output->createAttribute('ID');
		$att->value = 'Ret1';
		$newnode->appendChild($att);
		$root->appendChild($newnode);
		$name = $script = $appId = null;
		
		return $output;
	}
	
	/**
	 * Generate proper agent code for provided user code
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppAgents $agent
	 * @param \DOMDocument $xml
	 * @return string
	 */
	private function generateAgentForCode($agent, $xml, $appdir)
	{
		$init = urldecode(base64_decode(trim($xml->getElementsByTagName('InitCode')->item(0)->nodeValue)));
		$body = urldecode(base64_decode(trim($xml->getElementsByTagName('Code')->item(0)->nodeValue)));
		$requires = urldecode(base64_decode(trim($xml->getElementsByTagName('HeadCode')->item(0)->nodeValue)));
		$terminate = urldecode(base64_decode(trim($xml->getElementsByTagName('EndCode')->item(0)->nodeValue)));
		$body = str_replace(array('<?php', '<?', '?>'), '', $body);
		$init = str_replace(array('<?php', '<?', '?>'), '', $init);
		$requires = str_replace(array('<?php', '<?', '?>'), '', $requires);
		$terminate = str_replace(array('<?php', '<?', '?>'), '', $terminate);
		$class_name = str_replace(array('/', '\\', '-'), '', $agent->getAgentName());
		$class_name = str_replace(' ', '', $class_name);
		$agent_code = <<<AGENT
<?php
namespace Docova\DocovaBundle\Agents\\$appdir;

use Docova\DocovaBundle\ObjectModel\DocovaApplication;
use Docova\DocovaBundle\ObjectModel\DocovaAttachment;
use Docova\DocovaBundle\ObjectModel\DocovaCollection;
use Docova\DocovaBundle\ObjectModel\DocovaDateTime;
use Docova\DocovaBundle\ObjectModel\DocovaDocument;
use Docova\DocovaBundle\ObjectModel\DocovaField;
use Docova\DocovaBundle\ObjectModel\DocovaRichTextItem;
use Docova\DocovaBundle\ObjectModel\DocovaRichTextNavigator;
use Docova\DocovaBundle\ObjectModel\DocovaRichTextParagraphStyle;
use Docova\DocovaBundle\ObjectModel\DocovaRichTextRange;
use Docova\DocovaBundle\ObjectModel\DocovaRichTextStyle;
use Docova\DocovaBundle\ObjectModel\DocovaSession;
use Docova\DocovaBundle\ObjectModel\DocovaView;
use Docova\DocovaBundle\ObjectModel\DocovaViewNavigator;
use Docova\DocovaBundle\ObjectModel\DocovaName;
use Docova\DocovaBundle\ObjectModel\DocovaAgent;

$requires

\$docova = null;

class {$class_name}
{
	private \$ParameterDocID = null;
    private \$DocumentContext= null;

	public function __construct(\$docova_obj)
	{
		global \$docova; 
		\$docova = \$docova_obj;
	}

	function initialize(\$document_idorobj = null)
	{
	    if(gettype(\$document_idorobj) == "string"){
	        \$this->ParameterDocID = \$document_idorobj;	
	        \$this->DocumentContext = \$this->_em->getRepository('DocovaBundle:Documents')->find(\$document_idorobj);
	    }else if(\$document_idorobj instanceof \\Docova\DocovaBundle\\ObjectModel\\DocovaDocument){
	        \$this->ParameterDocID = \$document_idorobj->getId();
	        \$this->DocumentContext = \$document_idorobj;
	    }elseif (\$document_idorobj instanceof \\Docova\\DocovaBundle\\Entity\\Documents){
	        \$this->ParameterDocID = \$document_idorobj->getId();
	        \$this->DocumentContext = \$document_idorobj;
	    }
        global \$docova;
        \$docova->_DocumentContext = \$this->DocumentContext;
		
		$init
	}

	$body
	
	public function __destruct()
	{
		$terminate
	}
}
AGENT;
		return $agent_code;
	}
	
	/**
	 * Generate toolbar for action buttons
	 * 
	 * @param \DOMDocument $html
	 * @return string
	 */
	private function generateToolbar($html)
	{
		$output = array('read' => '', 'edit' => '');
		foreach ($html->getElementsByTagName('button') as $btn)
		{
			if (false === strpos($btn->getAttribute('hidewhen'), 'R')) {
				$output['read'] .= '<a onclick="'.$btn->getAttribute('fonclick').'" ';
				$output['read'] .= 'primary="'.trim(str_replace(array('ui-button-icon-primary', 'ui-button-text', 'ui-icon '), '', $btn->getElementsByTagName('span')->item(0)->getAttribute('class'))).'" ';
				$output['read'] .= 'secondary="' . ($btn->getElementsByTagName('span')->length > 2 ? trim(str_replace(array('ui-button-icon-secondary', 'ui-icon '), '', $btn->getElementsByTagName('span')->item(2)->getAttribute('class'))) : '').'" ';
				$output['read'] .= 'id="'.($btn->getAttribute('id') ? $btn->getAttribute('id') : '').'">';
				$output['read'] .= $btn->getAttribute('btntext') == '1' && $btn->getAttribute('btnlabel') ? $btn->getAttribute('btnlabel') : '';
				$output['read'] .= "</a>\n\r\t";
			}
			if (false === strpos($btn->getAttribute('hidewhen'), 'E')) {
				$output['edit'] .= '<a onclick="'.$btn->getAttribute('fonclick').'" ';
				$output['edit'] .= 'primary="'.trim(str_replace(array('ui-button-icon-primary', 'ui-button-text', 'ui-icon '), '', $btn->getElementsByTagName('span')->item(0)->getAttribute('class'))).'" ';
				$output['edit'] .= 'secondary="' . ($btn->getElementsByTagName('span')->length > 2 ? trim(str_replace(array('ui-button-icon-secondary', 'ui-icon '), '', $btn->getElementsByTagName('span')->item(2)->getAttribute('class'))) : '').'" ';
				$output['edit'] .= 'id="'.($btn->getAttribute('id') ? $btn->getAttribute('id') : '').'">';
				$output['edit'] .= $btn->getAttribute('btntext') == '1' && $btn->getAttribute('btnlabel') ? $btn->getAttribute('btnlabel') : '';
				$output['edit'] .= "</a>\n\r\t";
			}
		}
		return $output;
	}
	
	/**
	 * Generate/Edit deisgn elements in DB
	 * 
	 * @param string $form
	 * @param array $elements
	 * @return boolean|string
	 */
	private function upgradeDbDesignElements($form, $elements, $type)
	{
		try {
			$em = $this->getDoctrine()->getManager();
			$curr_elements = $em->getRepository('DocovaBundle:DesignElements')->findBy(array($type => $form));
			if (!empty($curr_elements)) 
			{
				foreach ($curr_elements as $elem) {
					$found = false;
					foreach ($elements as $index => $e) {
						if (strtolower($elem->getFieldName()) == strtolower($e['name'])) {
							$found = $e;
							break;
						}
					}
					if ($found === false) {
						$elem->setTrash("1");
					}
					else {
						$found['separator'] = str_ireplace(array('comma', 'semicolon', 'newline' ,'blankline', 'space'), array(",", ";", "\r\n", "\r\n\n", "\s"), $found['separator']);
						$found['separator'] = str_replace(array('\r', '\n'), array("\r", "\n"), $found['separator']);
						$elem->setFieldType($found['type']);
						$elem->setNameFieldType(null);
					    if ( $found['type'] == "readers"){
							$elem->setNameFieldType(2);
						}else if ( $found['type'] == "authors"){
							$elem->setNameFieldType(3);
						}
						$elem->setModifiedBy($this->user);
						$elem->setDateModified(new \DateTime());
						
						$elem->setMultiSeparator(!empty($found['separator']) ? $found['separator'] : null);
						$elem->setTrash(false);
						array_splice($elements, $index, 1);
					}
				}
				$em->flush();
				$curr_elements = $elem = $e = $found = null;
			}
	
			if (!empty($elements)) 
			{
				if ($type == 'form')
					$form = $em->getReference('DocovaBundle:AppForms', $form);
				else 
					$form = $em->getReference('DocovaBundle:Subforms', $form);
				foreach ($elements as $e) {
					$e['separator'] = str_ireplace(array('comma', 'semicolon', 'newline' ,'blankline', 'space'), array(",", ";", "\r\n", "\r\n\n", "\s"), $e['separator']);
					$e['separator'] = str_replace(array('\r', '\n'), array("\r", "\n"), $e['separator']);
					$elem = new DesignElements();
					$elem->setFieldName($e['name']);
					$elem->setFieldType($e['type']);
					$elem->setNameFieldType(null);
					if ( $e['type'] == "readers"){
					    $elem->setNameFieldType(2);
					}else if ( $e['type'] == "authors"){
					    $elem->setNameFieldType(3);
					}
					$elem->setModifiedBy($this->user);
					$elem->setDateModified(new \DateTime());
					$elem->setMultiSeparator(!empty($e['separator']) ? $e['separator'] : null);
					if ($type == 'form')
						$elem->setForm($form);
					else 
						$elem->setSubform($form);
					$em->persist($elem);
				}
				$em->flush();
			}
			
			return true;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	
	/**
	 * Generate twigs for read and edit mode
	 * 
	 * @param string $form
	 * @param string $read
	 * @param string $edit
	 * @param string $actionsread
	 * @param string $actionsedit
	 * @param string $type
	 * @param string $computed
	 * @param string $default
	 * @param boolean $ismobile
	 * @return boolean
	 */
	private function generateTwigs($form, $read, $edit, $actionsread = '', $actionsedit = '', $type = 'form', $computed = null, $default = null, $ismobile = false)
	{
		$em = $this->getDoctrine()->getManager();
		if ($type == 'form'){
			$form = $em->getRepository('DocovaBundle:AppForms')->findOneBy(array('id' => $form, 'trash' => false));
		}elseif ( strtolower ($type) == "subform"){
			$form = $em->getRepository('DocovaBundle:Subforms')->findOneBy(array('id' => $form, 'Trash' => false));
		}elseif ($type == "page"){
			$form = $em->getRepository('DocovaBundle:AppPages')->find($form);
		}elseif ($type == "outline"){
			$form = $em->getRepository('DocovaBundle:AppOutlines')->find($form);
		}elseif ($type == 'widget'){
			$form = $em->getRepository('DocovaBundle:Widgets')->findOneBy(['id' => $form, 'isCustom' => true]);
		}
		if (empty($form)) {
			throw new \Exception('Unspecifed Form/Subform/Page/Widget source ID.');
		}
		
		if ($type !== 'widget')
		{
			$app = $form->getApplication()->getId();
			if ( $type == "page"){
				$form =  $form->getPageName();
			}elseif ( $type == "outline"){
				$form = $form->getOutlineName();
			}else{
				$form = ($type == 'form'  ) ? $form->getFormName() : $form->getFormFileName();
			}
			if (is_dir($this->appPath.$app.DIRECTORY_SEPARATOR)) 
			{
				$form = str_replace(array('/', '\\'), '-', $form);
				$form = str_replace(' ', '', $form);
				if ($type == 'form')
				{
	
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.$form.'_computed.html.twig', $computed);
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.$form.'_default.html.twig', $default);
					if($ismobile){
						$extends = "{% extends 'DocovaBundle:Mobile:mReadAppForm.html.twig' %}\n";
					}else{
						$extends = "{% extends 'DocovaBundle:Applications:wReadForm.html.twig' %}\n";
					}
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.$form.($ismobile ? '_m' : '').'_read'.'.html.twig', "$extends{% block actionbar %}". (!empty($actionsread) ? $actionsread : '')."{% endblock %}{% block contentsection %}$read{% endblock %}");
				
					if($ismobile){
						$extends = "{% extends 'DocovaBundle:Mobile:mEditAppForm.html.twig' %}\n";
					}else{
						$extends = "{% extends 'DocovaBundle:Applications:wOpenForm.html.twig' %}\n";
					} 
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.$form.($ismobile ? '_m' : '').'.html.twig', "$extends{% block actionbar %}". (!empty($actionsedit) ? $actionsedit : '')."{% endblock %}{% block contentsection %}$edit{% endblock %}");
					return true;
				}
				else if ( $type == 'subform') {
					if (!is_dir($this->appPath.$app.DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR)) 
					{
						@mkdir($this->appPath.$app.DIRECTORY_SEPARATOR.'subforms');
					}
	
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$form.'_computed.html.twig', $computed);
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$form.'_default.html.twig', $default);
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$form.($ismobile ? '_m' : '').'_read.html.twig', "{% block subactionbar %}<div class='subformactionbar'>". (!empty($actionsread) ? $actionsread : '')."</div>{% endblock %}$read");
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.'subforms'.DIRECTORY_SEPARATOR.$form.($ismobile ? '_m' : '').'.html.twig', "{% block subactionbar %}<div class='subformactionbar'>". (!empty($actionsedit) ? $actionsedit : '')."</div>{% endblock %}$edit");
					return true;
				}else if ( $type == "page") {
					if (!is_dir($this->appPath.$app.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR)) 
					{
						@mkdir($this->appPath.$app.DIRECTORY_SEPARATOR.'pages');
					}
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$form.($ismobile ? '_m' : '').'.html.twig', (!empty($actionsedit) ? "<div id='FormHeader' style='padding-top:4px;' ><table border=0 cellspacing=0 cellpadding=0 width=100%><tr><td id='tdActionBar'>".$actionsedit."</tr></table></div>" : '')."<div id='divFormContentSection' ><div id='divDocPage'>".$edit."</div></div>");
					return true;
				}else if ( $type == "outline"){
					if (!is_dir($this->appPath.$app.DIRECTORY_SEPARATOR.'outline'.DIRECTORY_SEPARATOR)) 
					{
						@mkdir($this->appPath.$app.DIRECTORY_SEPARATOR.'outline');
					}
					$html = "<script type='text/javascript'>\n var outlineJson=" . $read . ";</script>";
					file_put_contents($this->appPath.$app.DIRECTORY_SEPARATOR.'outline'.DIRECTORY_SEPARATOR.$form.'_twig.html.twig', $html );
					return true;
				}
			}
			else {
				throw new \Exception('Application form path could not be found!');
			}
		}
		else {
			if (is_dir($this->appPath.'Widgets'.DIRECTORY_SEPARATOR)) {
				$read = html_entity_decode($read);
				$widget_path = $this->appPath.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Subform'.DIRECTORY_SEPARATOR;
				$form = $form->getSubformName();
				$content = <<<TWIG
<div class="portlet-header">
	<span class="arial twelve bold">{{ widget.getWidgetName }}</span>
	<button class="btn-RemoveWidget"></button>
	<button class="btn-AddWidget"></button>
</div>
<div class="portlet-content">
	{$read}
</div>
TWIG;
				file_put_contents($widget_path . $form . '.html.twig', $content);
				return true;
			}
		}

		return false;
	}
	
	/**
	 * THIS WAS OLD METHOD TO GENERATE FORM LEVEL TABLES
	 * BASE ON CHRIS AND JEFF IDEA THERE WILL BE NO LONGER FORM LEVEL
	 * TABLES BUT WE CREATE VIEW TABLES AT TIME OF SAVE/UPDATE A VIEW
	 * Generate view tables for all design elements of a form
	 * 
	 * @param object|string $form
	 * @param array $added
	 * @param array $updated
	 * @param array $removed
	 */
/*
	private function generateFormView($form, $added, $updated, $removed)
	{
		$form = is_object($form) ? $form->getId() : $form;
		$form = str_replace('-', '', $form);
		$conn = $this->getDoctrine()->getConnection();
		$conn->beginTransaction();
		try {
			$driver = $conn->getDriver()->getName();
			if ($driver == 'pdo_mysql')
			{
				$exists = $conn->fetchArray('SHOW TABLES LIKE ?', array("view_$form"));
				$exists = !empty($exists[0]) ? true : false;
			}
			elseif ($driver == 'pdo_sqlsrv')
			{
				$exists = $conn->executeQuery("IF OBJECT_ID('view_".$form."', 'U') IS NOT NULL BEGIN SELECT 1 END ELSE BEGIN SELECT 0 END");
				$exists = !empty($exists) ? true : false;
			}
			
			if ($exists === false) 
			{
				$query = 'CREATE TABLE view_'. $form .' (';
				$query .= 'Document_Id VARCHAR(40) NOT NULL, ';
				$query .= 'App_Id VARCHAR(40) NOT NULL, ';
				foreach ($added as $field => $t)
				{
					$type = $this->getDataType($driver, $t);
					$query .= "$field $type, ";
				}
				$query = substr_replace($query, ')', -2);
				if ($driver == 'pdo_mysql') {
					$query .= ' ENGINE INNODB';
				}
				
				$conn->executeQuery($query);
				
				$query = 'CREATE INDEX idx_' . $form . ' ON view_'. $form . ' (Document_Id)';
				$conn->executeQuery($query);

				$conn->commit();
			}
			else {
				if (count($added)) {
					$query = "ALTER TABLE view_$form ";
					foreach ($added as $field => $t) {
						$type = $this->getDataType($driver, $t);
						$query .= "ADD COLUMN $field $type,";
					}
					$query = substr_replace($query, ';', -1);
					$conn->executeQuery($query);
				}
				if (count($removed)) {
					$query = "ALTER TABLE view_$form ";
					$query .= $driver == 'pdo_mysql' ? 'DROP ' : 'DROP COLUMN ';
					foreach ($removed as $field) {
						$query .= "$field,";
					}
					$query = substr_replace($query, ';', -1);
					$conn->executeQuery($query);
				}
				if (count($updated)) {
					$query = "ALTER TABLE view_$form ";
					foreach ($updated as $field => $t) {
						$type = $this->getDataType($driver, $t);
						if ($driver == 'pdo_mysql') {
							$query .= "MODIFY COLUMN $field $type,";
						}
						else {
							$query .= "ALTER COLUMN $field $type,";
						}
					}
					$query = substr_replace($query, ';', -1);
					$conn->executeQuery($query);
				}
				$conn->commit();
			}
		}
		catch (\Exception $e) {
			$conn->rollback();
			throw $this->createNotFoundException($e->getMessage());
		}
	}
	
	private function getDataType($driver, $value)
	{
		switch ($value)
		{
    		case 'date':
    			$type = 'DATETIME NULL'; 
    			break;
    		case 'number':
    			$type = 'FLOAT NULL';
    			break;
    		default:
    			$type = 'NVARCHAR(1024) NULL';
    			break;
		}
		return $type;
	}
*/
}