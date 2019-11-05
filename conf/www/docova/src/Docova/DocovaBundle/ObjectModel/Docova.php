<?php

namespace Docova\DocovaBundle\ObjectModel;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class as container of all API classes to be accessible on any spots
 * @author javad_rahimi
 */
class Docova
{
	private $_container;
	private $_router;
	private $_em;
	
	public function __construct(ContainerInterface $container)
	{
		$this->_container = $container;
		$this->_router = $container->get('router');
		$this->_em = $container->get('doctrine')->getManager();
	}
	
	public function __call($api, $arguments)
	{
		$fname = ucfirst($api);
		if (method_exists($this, 'get' . $fname)) {
			$method = 'get' . $fname;
			return call_user_func_array([$this, $method], $arguments);
			//return $this->$method($arguments);
		}
		else {
			throw new \OutOfBoundsException('Undefined API method "'.$api.'" via __get');
		}
	}
	
	/**
	 * Get container
	 * 
	 * @return \Symfony\Component\DependencyInjection\ContainerInterface
	 */
	public function getContainer()
	{
		return $this->_container;
	}
	
	/**
	 * Get entity manager
	 * 
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getManager()
	{
		return $this->_em;
	}
	
	/**
	 * Get router
	 * 
	 * @return \Symfony\Bundle\FrameworkBundle\Routing\Router 
	 */
	public function getRouter()
	{
		return $this->_router;
	}
	
	/**
	 * get DocovaApplication object
	 * 
	 * @param array $options
	 * @param object $app_entity
	 * @return DocovaApplication
	 */
	private function getDocovaApplication($options = [], $app_entity = null)
	{
		$application = new DocovaApplication($options, $app_entity, $this);
		return $application;
	}
	
	/**
	 * get DocovaAcl object
	 * 
	 * @param \Docova\DocovaBundle\Entity\Libraries $application
	 * @return DocovaAcl
	 */
	private function getDocovaAcl(\Docova\DocovaBundle\Entity\Libraries $application)
	{
		$acl = new DocovaAcl($application, $this);
		return $acl;
	}

	/**
	 * get DocovaAgent object
	 * 
	 * @param DocovaApplication $application
	 * @param string $name
	 * @return DocovaAgent
	 */
	private function getDocovaAgent(DocovaApplication $application, $name = '')
	{
		$agent = new DocovaAgent($application, $name);
		return $agent;
	}
	
	/**
	 * get DocovaAttachment object
	 * 
	 * @param DocovaDocument $parentDoc
	 * @param mixed $attachment
	 * @param string $root_path
	 * @return DocovaAttachment
	 */
	private function getDocovaAttachment(DocovaDocument $parentDoc, $attachment, $root_path = '')
	{
		$attachment = new DocovaAttachment($parentDoc, $attachment, $root_path, $this);
		return $attachment;
	}
	
	/**
	 * get DocovaCollection object
	 * 
	 * @param mixed $parent
	 * @param array $elements
	 * @return DocovaCollection
	 */
	private function getDocovaCollection($parent, $elements = [])
	{
		$collection = new DocovaCollection($parent, $elements, $this);
		return $collection;
	}
	
	/**
	 * get DocovaDateTime object
	 * 
	 * @param mixed $datetime
	 * @param string $default_format [optional]
	 * @return DocovaDateTime
	 */
	private function getDocovaDateTime($datetime, $default_format = '')
	{
		$datetime = new DocovaDateTime($datetime, $default_format);
		return $datetime;
	}
	
	/**
	 * get DocovaDocument object
	 * 
	 * @param mixed $parentObj
	 * @param string $unid [optional]
	 * @param string $root [optional]
	 * @param object $doc_entity [optional]
	 * @return DocovaDocument
	 */
	private function getDocovaDocument($parentObj, $unid = null, $root = '', $doc_entity = null)
	{
		$document = new DocovaDocument($parentObj, $unid, $root, $doc_entity, $this);
		return $document;
	}
	
	/**
	 * get DocovaField object
	 * 
	 * @param DocovaDocument $document
	 * @param string $fieldname
	 * @param mixed $fieldvalue
	 * @param string $special_type
	 * @param string $fieldType
	 * @return DocovaField
	 */
	private function getDocovaField(DocovaDocument $document, $fieldname, $fieldvalue = null, $special_type = null, $fieldType = null)
	{
		$field = new DocovaField($document, $fieldname, $fieldvalue, $special_type, $fieldType, $this);
		return $field;
	}
	
	/**
	 * get DocovaName object
	 * 
	 * @param string $inputname
	 * @return \Docova\DocovaBundle\ObjectModel\DocovaName
	 */
	private function getDocovaName($inputname)
	{
		$name = new DocovaName($inputname, $this);
		return $name;
	}
	
	/**
	 * get DocovaRichTextItem object
	 * 
	 * @param DocovaDocument $parentDoc
	 * @param string $fieldname
	 * @param DocovaField $field
	 * @return DocovaRichTextItem
	 */
	private function getDocovaRichTextItem(DocovaDocument $parentDoc, $fieldname, DocovaField $field = null)
	{
		$rich_text_item = new DocovaRichTextItem($parentDoc, $fieldname, $field, $this);
		return $rich_text_item;
	}
	
	/**
	 * get DocovaRichTextNavigator object
	 * 
	 * @param \DOMDocument $domObject
	 * @param \DOMElement $startElem
	 * @param \DOMElement $endElem
	 * @return DocovaRichTextNavigator
	 */
	private function getDocovaRichTextNavigator(\DOMDocument $domObject, \DOMElement $startElem = null, \DOMElement $endElem = null)
	{
		$rich_text_navigator = new DocovaRichTextNavigator($domObject, $startElem, $endElem);
		return $rich_text_navigator;
	}
	
	/**
	 * get DocovaRichTextParagraphStyle object
	 * 
	 * @return DocovaRichTextParagraphStyle
	 */
	private function getDocovaRichTextParagraphStyle()
	{
		$rich_text_pstyle = new DocovaRichTextParagraphStyle();
		return $rich_text_pstyle;
	}
	
	/**
	 * get DocovaRichTextRange object
	 * 
	 * @param \DOMDocument $element
	 * @return DocovaRichTextRange
	 */
	private function getDocovaRichTextRange(\DOMDocument $element)
	{
		$rich_text_range = new DocovaRichTextRange($element);
		return $rich_text_range;
	}
	
	/**
	 * get DocovaRichTextStyle object
	 * 
	 * @return DocovaRichTextStyle
	 */
	private function getDocovaRichTextStyle()
	{
		$rich_text_style = new DocovaRichTextStyle();
		return $rich_text_style;
	}
	
	/**
	 * get DocovaSession object
	 * 
	 * @return DocovaSession
	 */
	private function getDocovaSession()
	{
		$session = new DocovaSession($this);
		return $session;
	}
	
	/**
	 * get DocovaView object
	 * 
	 * @param DocovaApplication $application
	 * @param string $viewname
	 * @param mixed $view_entity
	 * @return DocovaView
	 */
	private function getDocovaView(DocovaApplication $application, $viewname, $view_entity = null)
	{
		$view = new DocovaView($application, $viewname, $view_entity, $this);
		return $view;
	}
	
	/**
	 * get DocovaViewNavigator object
	 * 
	 * @param DocovaView $parentView
	 * @param array $params
	 * @return DocovaViewNavigator
	 */
	private function getDocovaViewNavigator(DocovaView $parentView, $params = [])
	{
		$view_nav = new DocovaViewNavigator($parentView, $params);
		return $view_nav;
	}
	
	/**
	 * get ObjectModel object
	 * 
	 * @return ObjectModel
	 */
	private function getObjectModel()
	{
		$model = new ObjectModel($this);
		return $model;
	}
}