<?php

namespace Docova\DocovaBundle\Command;

use Docova\DocovaBundle\Extensions\CleanupManipulation;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Daily App clean up task/cron job.
 * Cleans the all trashed apps with all its contents including forms,
 * design elements, attachments, design twigs, documents and etc
 * @author javad_rahimi
 */
class AppCleanUpCommand extends ContainerAwareCommand
{
	private $_em;
	private $_path;
	private $_settings;
	private $_apps = array();
	private $debug = false;
	
	public function configure()
	{
		$this->setName('docova:appcleanup')
			->setDescription('Task to clean up all or specific trashed apps')
			->addOption('scheduled', null, InputOption::VALUE_OPTIONAL, 'Run base on scheduled time', 'true')
			->addOption('debug', null, InputOption::VALUE_REQUIRED, 'Debugging enabled?', 'false')
			->addOption('appid', null, InputOption::VALUE_REQUIRED, 'Application ID');
	}
	
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$appid = $input->getOption('appid');
		$scheduled = $input->getOption('scheduled');
		$scheduled = $scheduled == 'false' ? false : true;
		$this->debug = $input->getOption('debug') == 'true' ? true : false;
		$this->_em = $this->getContainer()->get('doctrine')->getManager();
		//compute the attachments path
		$path = $this->getContainer()->getParameter('document_root') ? realpath($this->getContainer()->getParameter('document_root')) : getcwd().DIRECTORY_SEPARATOR.'..'; 
		$this->_path = $path.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';

		//is it scheduled command
		if ($scheduled === true)
		{
			$this->_settings = $this->_em->getRepository('DocovaBundle:GlobalSettings')->findAll();
			$this->_settings = $this->_settings[0];
			$time_to_run = $this->_settings->getCleanupTime() ? $this->_settings->getCleanupTime()->format('h:i:s A') : '03:00:00 AM';
			//check if it's time to run
			if (true !== $this->isTimeToRun($time_to_run))
			{
				$output->writeln('No task to run.');
				return null;
			}
		}
		
		//check if it's single app or all
		if (empty($appid))
		{
			$apps = $this->_em->getRepository('DocovaBundle:Libraries')->findBy(['isApp' => true, 'Trash' => true]);
			if (empty($apps) || empty($apps[0])) {
				$output->writeln('No trashed app is found.');
				return null;
			}
			$this->_apps = $apps;
		}
		else {
			$app = $this->_em->getRepository('DocovaBundle:Libraries')->findOneBy(['id' => $appid, 'isApp' => true, 'Trash' => true]);
			if (empty($app)) {
				$output->writeln('Application with ID "'.$appid.'" cannot be found.');
				return null;
			}
			$this->_apps = [$app];
		}
		
		$count = 0;
		$router = $this->getContainer()->get('router');
		$app_path = $this->getContainer()->get('kernel')->getRootDir() . '/../src/Docova/DocovaBundle/Resources/views/DesignElements/';
		
		foreach ($this->_apps as $app)
		{
			$handler = new CleanupManipulation($this->_em, $router, $app->getId(), $this->_path, $app_path);
			$res = $handler->cleanupDocuments();
			if (true !== $res) {
				if ($this->debug === true) { $output->writeln($res); }
				continue;
			}
			$res = $handler->cleanupApplication();
			if (true !== $res) {
				if ($this->debug === true) { $output->writeln($res); }
				continue;
			}

			$handler = null;
			$count++;
		}
		
		$total = count($this->_apps);
		$output->writeln("Application clean up has been completed for $count application out of $total.");
	}

	/**
	 * Check if run time is within 60 minutes range
	 *
	 * @param string $run_time
	 * @return boolean
	 */
	private function isTimeToRun($run_time)
	{
		$diff = ceil((time() - strtotime($run_time))/60);
		if ($diff == 0 || ($diff > 0 && $diff < 60))
		{
			return true;
		}

		return false;
	}
}