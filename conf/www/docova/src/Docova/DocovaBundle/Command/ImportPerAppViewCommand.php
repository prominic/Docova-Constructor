<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Run the docova:importviewdata command per views in the app
 * to improve the performance and avoid huge memory usage.
 * @author javad_rahimi
 */
class ImportPerAppViewCommand extends ContainerAwareCommand 
{
	protected function configure()
	{
		$this->setName('docova:importperappview')
			->setDescription('Index each views in the app by calling docova:importviewdata command.')
			->addArgument('appid', InputArgument::REQUIRED, 'Application ID');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$appid = $input->getArgument('appid');
		if (empty($appid)) {
			$output->writeln('Invalid Application ID.');
			return false;
		}
		
		$em = $this->getContainer()->get('doctrine')->getManager();
		$views = $em->getRepository('DocovaBundle:AppViews')->getAllViewIds($appid);
		$em = null;
	
		if (!empty($views[0]))
		{
			foreach ($views as $id)
			{
				$argument = array(
					'command' => 'docova:importviewdata',
					'app' => $appid,
					'view' => $id
				);
	
				$importViewDataCommand = new ArrayInput($argument);
				$command = $this->getApplication()->find('docova:importviewdata');
	
				$command->run($importViewDataCommand, $output);
			}
		}
		else {
			$output->writeln('No view found in the application.');
		}
	}
}