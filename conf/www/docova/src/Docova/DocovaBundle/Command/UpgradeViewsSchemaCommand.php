<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Command to upgrade applicatoin views and re-index them
 * @author javad_rahimi
 */
class UpgradeViewsSchemaCommand extends ContainerAwareCommand
{
	private $_em;
	private $_target;
	private $_pdu;
	
	public function configure()
	{
		$this->setName('docova:upgradeviewsschema')
			->setDescription('Command to upgrade all or specific view(s) of an application')
			->addArgument('application', InputArgument::REQUIRED, 'Applicatoin ID')
			->addArgument('viewid', InputArgument::OPTIONAL, 'Application view ID')
			->addOption('pdu', null, InputOption::VALUE_OPTIONAL, 'Set to true to just upgrade none-prohibited app views.');
	}
	
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$appid = $input->getArgument('application');
		$view = $input->getArgument('viewid');
		$this->_pdu = $input->getOption('pdu') == 'true' ? true : false;
		$view_ids = [];
		
		if (empty($appid))
		{
			$output->writeln('Application ID is missed!');
			return false;
		}
		$em = $this->getContainer()->get('doctrine')->getManager();
		$this->_target = $em->getRepository('DocovaBundle:Libraries')->findOneBy(['id' => $appid, 'Trash' => false, 'isApp' => true]);
		if (empty($this->_target)) {
			$output->writeln('Applicatoin with "'.$appid.'" ID could not be found.');
			return false;
		}
		
		$docova = new Docova($this->getContainer());
		$view_handler = new ViewManipulation($docova, $this->_target);
		$docova = null;
		if (empty($view))
		{
			$view_ids = $em->getRepository('DocovaBundle:AppViews')->getAllViewIds($appid, $this->_pdu);
			if (!empty($view_ids)) {
				foreach ($view_ids as $vid) {
					$id = str_replace('-', '', $vid);
					$view_handler->deleteView($id);
				}
			}
		}
		else {
			$view = $this->_em->getRepository('DocovaBundle:AppViews')->findOneBy(['id' => $view, 'pDU' => false]);
			if (!empty($view))
			{
				$id = str_replace('-', '', $vid);
				$view_handler->deleteView($id);
				$view_ids = [$view];
			}
			else {
				$output->writeln('Either selected view cannot be found or it is prohibited from upgrade.');
				return false;
			}
		}
		
		if (!empty($view_ids))
		{
			foreach ($view_ids as $vid)
			{
				$view = $em->getRepository('DocovaBundle:AppViews')->find($vid);
				$xml = new \DOMDocument();
				$xml->loadXML($view->getViewPerspective());
				$view_handler->createViewTable($view, $xml);

				$argument = array(
					'command' => 'docova:importviewdata',
					'app' => $this->_target->getId(),
					'view' => $vid
				);
			
				$indexView = new ArrayInput($argument);
				$command = $this->getApplication()->find('docova:importviewdata');
				$command->run($indexView, $output);
			}
		}
		else {
			$output->writeln('Nothing to upgrade and index.');
		}
	}
}