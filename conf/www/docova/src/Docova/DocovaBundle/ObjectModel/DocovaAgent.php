<?php

namespace Docova\DocovaBundle\ObjectModel;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Back-end class to manipulate attachments
 * @author javad_rahimi
 */
class DocovaAgent 
{
	private $_name;
	private $_parentApp;
	
	public function __construct(DocovaApplication $parent, $name)
	{
		if (!empty($name)) 
		{
			$this->_name = $name;
		}
		
		$this->_parentApp = $parent;
	}
	
	public function __set($name, $value)
	{
		if ($name === 'name') {
			$this->_name = $value;
		}
	}
	
	public function __get($name)
	{
		if ($name === 'name') {
			return $this->_name;
		}
	}
	
	/**
	 * Runs the agent
	 * 
	 * @param string $docidorobj
	 * @return string|boolean
	 */
	public function run($docidorobj = null)
	{
		if (true === $this->agentExists())
		{
			$app = $this->getApplication();
			if (false !== $app) 
			{
				$inputs = array(
					'command' => 'docova:agentrunner',
					'agent_name' => $this->_name,
					'app' => $this->_parentApp->appID,
					'-io' => true
				);
				if (!empty($docidorobj))
				{
					$inputs['docid'] = $docidorobj;
				}
				
				$inputs = new ArrayInput($inputs);
				$output = new BufferedOutput();
				$app->run($inputs, $output);
				
				$result = $output->fetch();

				return $result;
			}
		}
		return false;
	}
	
	/**
	 * Runs the agent on the server
	 * 
	 * @param string $docidorobj
	 * @return string|boolean
	 */
	public function runOnServer($docidorobj = null)
	{
		return $this->run($docidorobj);
	}
	
	/**
	 * Check if agent is defined and exists
	 * 
	 * @return boolean
	 */
	private function agentExists()
	{
		if (!empty($this->_name) && !is_null($this->_parentApp->_docova)) 
		{
			$em = $this->_parentApp->_docova->getManager();
			$agent = $em->getRepository('DocovaBundle:AppAgents')->findOneBy(array('application' => $this->_parentApp->appID, 'agentName' => $this->_name));
			if (!empty($agent)) 
			{
				$path = realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Agents'.DIRECTORY_SEPARATOR;
				$app_path = str_replace('-', '', $this->_parentApp->appID);
				if (is_dir($path.'A'.$app_path)) 
				{
					$name = str_replace(array('/', '\\'), '-', $this->_name);
					$name = str_replace(' ', '', $name);
					if (file_exists($path.'A'.$app_path.DIRECTORY_SEPARATOR.$name.'.php')) {
						return true;
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * Get application
	 * 
	 * @return \Symfony\Component\Console\Application|boolean
	 */
	private function getApplication()
	{
		if (!is_null($this->_parentApp->_docova))
		{
			$kernel = $this->_parentApp->_docova->getContainer()->get('kernel');
			$application = new Application($kernel);
			$application->setAutoExit(false);
			return $application;
		}
		
		return false;
	}
}