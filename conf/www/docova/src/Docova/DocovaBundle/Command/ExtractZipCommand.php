<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Docova\DocovaBundle\Extensions\AppExtractionServices;

/**
 * Core command to extract app zip file and upgrade the design
 * @author javad_rahimi
 */
class ExtractZipCommand extends ContainerAwareCommand
{
	public function configure()
	{
		$this->setName('docova:extractzip')
			->setDescription('Extract app zip file and upgrade design')
			->addArgument('zipfile', InputArgument::REQUIRED, 'App zip file name')
			->addArgument('userid', InputArgument::REQUIRED, 'Modifier/Creator user ID');
	}
	
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$user_id = $input->getArgument('userid');
		$zipfile = $input->getArgument('zipfile');
		if (empty($user_id) || empty($zipfile))
		{
			$output->writeln('ERROR: Application ID and/or modifier ID is missed.');
			return;
		}
		$zipfile = $this->getContainer()->get('kernel')->getRootDir() . '/../web/upload/Repository/'.$zipfile;
		$em = $this->getContainer()->get('doctrine')->getManager();
		try {
			$extract = new AppExtractionServices($this->getContainer(), $em, $zipfile, $user_id);
			$extract->extractApp();
			$res = $extract->publishApp();
			$extract->publishAppElements($this->getContainer()->get('kernel')->locateResource('@DocovaBundle'));
			if ($res['Status'] === true) {
				$output->writeln('Status:OK;TotalLength:'.$res['Total']);
			}
			else {
				$output->writeln('Status:FAILED');
			}
		}
		catch (\Exception $e) {
			$output->writeln('ERROR: '. $e->getMessage());
		}
	}
}