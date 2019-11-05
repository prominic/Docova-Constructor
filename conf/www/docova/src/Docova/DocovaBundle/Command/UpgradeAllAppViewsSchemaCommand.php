<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Major command to call view schema upgrade command for all apps' views
 * @author javad_rahimi
 */
class UpgradeAllAppViewsSchemaCommand extends ContainerAwareCommand
{
	public function configure()
	{
		$this->setName('docova:apps:upgradeviewschema')
			->setDescription('Upgrade all views\' schema in all applications');
	}
	
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$applications = $em->getRepository('DocovaBundle:Libraries')->getAllAppIds();
		if (!empty($applications[0]))
		{
			foreach ($applications as $appid)
			{
				$output->writeln("\n-------------------\n\rSchema upgrade for all views in app with ID '".$appid['id']."' is started.\n\r-------------------");
				$argument = array(
					'command' => 'docova:upgradeviewsschema',
					'application' => $appid['id']
				);
					
				$indexView = new ArrayInput($argument);
				$command = $this->getApplication()->find('docova:upgradeviewsschema');
				$command->run($indexView, $output);
				$output->write("-------------------\n\rSchema upgrade for all views in app has ended.\n\r-------------------");
			}
		}
		else {
			$output->writeln('No applicatoin was found to upgrade!');
		}
	}
}