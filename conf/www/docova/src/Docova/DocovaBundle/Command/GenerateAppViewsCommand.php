<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Command to generate imported app views if not exist
 * @author javad_rahimi
 */
class GenerateAppViewsCommand extends ContainerAwareCommand 
{
	protected function configure()
	{
		$this->setName('docova:generateappviews')
			->setDescription('Generate imported app views table if not exist')
			->addArgument('application', InputArgument::REQUIRED, 'application ID/Name');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$count = 0;
		$fails = array();
		$app = $input->getArgument('application');
		$em = $this->getContainer()->get('doctrine')->getManager();
		
		$application = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		if (empty($application)) {
			$application = $em->getRepository('DocovaBundle:Libraries')->getByName($app);
		}

		if (empty($application) || !$application->getIsApp()) {
			$output->writeln('Unspecified application ID or name.');
			return false;
		}
		
		$views = $application->getViews();
		if (!empty($views) && count($views))
		{
			$docova = new Docova($this->getContainer());
			$view_handler = new ViewManipulation($docova, $application);
			$docova = null;
			foreach ($views as $v)
			{
				$view_id = $v->getId();
				$view_id = str_replace('-', '', $view_id);
				$view_handler->beginTransaction();
				try {
					if (!$view_handler->viewExists($view_id))
					{
						$xml = new \DOMDocument();
						$xml->loadXML($v->getViewPerspective());
						$view_handler->createViewTable($v, $xml);
						$view_handler->commitTransaction();
						$count++;
						$xml = null;
					}
				}
				catch (\Exception $e) {
					$view_handler->rollbackTransaction();
					$fails[] = $v->getViewName();
				}
			}
			$view_handler = 0;
			
			if ($count) {
				$output->writeln("'$count' Views are generated for the application.");
			}
			if (!empty($fails)) {
				$output->writeln('Failed to the generate the following views:');
				$output->writeln('"'.implode('",\n"', $fails).'"');
			}
			
			if (empty($count) && empty($fails)) {
				$output->writeln('Generate view service is complete.');
			}
		}
		else {
			$output->writeln('Nothing found to be generated.');
		}
		
	}
}