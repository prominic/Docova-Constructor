<?php

namespace Docova\DocovaBundle\ObjectModel;

use Symfony\Component\HttpFoundation\Session\Session;
//use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Helper class to assist with translations from Lotus Notes
 * @author javad_rahimi
 */
class DocovaSession extends Session 
{
	private $_em;
	private $_router;
	private $_docova;
	
	public function __construct(Docova $docova = null)
	{
		if (!empty($docova)) {
			$this->_docova = $docova;
			$this->_em = $docova->getManager();
			$this->_router = $docova->getRouter();
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_docova = $docova;
				$this->_em = $docova->getManager();
				$this->_router = $docova->getRouter();
			}
			else {
				throw new \Exception('Oops! DocovaSessoin construction failed. Entity Manager\Router not available.');
			}
		}
		parent::__construct();
	}
	
	/**
	 * Property get
	 * 
	 * @param string $name
	 * @return mixed|\OutOfBoundsException
	 */
	public function __get($name)
	{
		$mname = ucfirst($name);
		if (method_exists($this, 'get' . $mname)) {
			$method = 'get' . $mname;
			return $this->$method();
		}
		else {
			throw new \OutOfBoundsException('Undefined property "'.$name.'" via __get');
		}
	}
	
	/**
	 * Get commonUserName
	 * 
	 * @return string
	 */
	public function getCommonUserName()
	{
		$user = $this->getCurrentUser();
		if (empty($user))
			return '';
		
		return $user->getUserNameDn();
	}
	
	/**
	 * Get current database
	 * 
	 * @return NULL|DocovaApplication
	 */
	public function getCurrentDatabase()
	{
		$appentity = $this->getCurrentAppEntity(false);
		if (empty($appentity))
			return null;
		
		$app = $this->_docova->DocovaApplication(null, $appentity);
		return $app;
	}
	
	/**
	 * Get documentContext
	 *
	 * @return DocovaDocument object
	 */
	public function getDocumentContext()
	{
	    $result = null;
	    if($this->_docova && $this->_docova->_DocumentContext){
	        $result = $this->_docova->_DocumentContext;
	    }
        return $result;
	}
	/**
	 * Get effectiveUserName
	 * 
	 * @return string
	 */
	public function getEffectiveUserName()
	{
		$user = $this->getCurrentUser();
		if (empty($user))
			return '';
		
		return $user->getUsernameDn();
	}
	
	/**
	 * Is on server
	 * 
	 * @return boolean
	 */
	public function getIsOnServer()
	{
		return true;
	}
	
	/**
	 * Get server name
	 * 
	 * @return string
	 */
	public function getServerName()
	{
		return $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	}
	
	/**
	 * Get username
	 * 
	 * @return string
	 */
	public function getUserName()
	{
		$user = $this->getCurrentUser();
		if (empty($user))
			return '';
		
		return $user->getUsernameDn();
	}
	
	public function getUserNameList()
	{
		//@todo: need to investigate
	}
	
	/**
	 * Get username object
	 * 
	 * @return NULL|DocovaName
	 */
	public function getUserNameObject()
	{
		$user = $this->getCurrentUser();
		if (empty($user)){
			return null;
		}
		
		return $this->_docova->DocovaName(($user->getUserNameDn() ? $user->getUserNameDn() : $user->getUserNameDnAbbreviated()));
	}
	
	/**
	 * Get user roles
	 * 
	 *  @return array
	 */
	public function getUserRoles()
	{
		$user = $this->getCurrentUser();
		if (empty($user))
			return array();
		
		return $user->getRoles();
	}
	
	/**
	 * Get current user object/ID
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|string|NULL
	 */
	public function getCurrentUser($object = true)
	{
		$user = $this->get('currentUser');
		if (!empty($user)) {
			if ($object === false)
				return base64_decode($user);
			else {
				$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('id' => base64_decode($user), 'Trash' => false));
				if (!empty($user))
					return $user;
			}
		}
		
		return null;
	}
	
	/**
	 * Get current application object/ID
	 * 
	 * @return mixed
	 */
	public function getCurrentAppEntity($object = true)
	{
		$application = $this->get('currentApp');
		if (!empty($application)){
			if ($object === false)
				return base64_decode($application);
			else {
				$application = $this->_em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $application, 'Trash' => false));
				if (!empty($application))
					return $application;
			}
		}
		
		return null;
	}
	
	/**
	 * Create a new DocovaName object
	 * 
	 * @param string $namestring
	 * @return DocovaName
	 */
	public function createName($namestring)
	{
		$result = null;
		if (empty($namestring))
			return $result;
		
		$result = $this->_docova->DocovaName($namestring);
		return $result;
	}
	
	/**
	 * Gets a DOCOVA Application object
	 * 
	 * @param string $server
	 * @param string $database
	 * @param boolean $create
	 * @return NULL|DocovaApplication
	 */
	public function getDatabase($server = null, $database, $create = false)
	{
		if (empty($database))
			return null;
		
		$application = 	$this->_docova->DocovaApplication(['appID' => $database]);
		if(empty($application) || empty($application->appID)){
		    $application = $this->_docova->DocovaApplication(['appName' => $database]);		    
		    if(empty($application) || empty($application->appID)){
		        $application = null;
		    }
		}
			
		return $application;
	}
	
	/**
	 * Gets an environment variable
	 * 
	 * @param string $varname
	 * @return string|mixed
	 */
	public function getEnvironmentVar($varname)
	{
		if (empty($varname) || !$this->has($varname))
			return '';
		
		return unserialize($this->get($varname));
	}
	
	/**
	 * Sets an environment variable
	 * 
	 * @param string $varname
	 * @param mixed $value
	 */
	public function setEnvironmentVar($varname, $value)
	{
		if (empty($varname) || empty($value))
			return;
		
		if (is_object($value))
			$this->set($varname, serialize($value));
		elseif (is_array($value))
			$this->set($varname, serialize($value));
		else 
			$this->set($varname, $value);
	}
	
	public function createRichTextStyle()
	{
		//@todo: if back-end, return new instance of DocovaRichTextStyle
	}
	
	public function createRichTextParagraphStyle()
	{
		//@todo: if back-end, return new instance of DocovaRichTextParagraphStyle
	}
}