<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to generate imported app views if not exist
 * @author javad_rahimi
 */
class CleanDataCommand extends ContainerAwareCommand 
{
	protected function configure()
	{
		$this->setName('docova:cleandata')
			->setDescription('Generate imported app views table if not exist')
			->addArgument('form', InputArgument::REQUIRED, 'application ID/Name');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$count = 0;
		$fails = array();
		$formid = $input->getArgument('form');
		$em = $this->getContainer()->get('doctrine')->getManager();
		
		$customFields = $em->getRepository('DocovaBundle:DesignElements')->findBy(array( 'trash' => true));
		if (!empty($customFields)) 
		{
			foreach($customFields as $field) {
				 $textdata =  $em->getRepository('DocovaBundle:FormTextValues')->findBy(array( 'Field' => $field));
				 foreach($textdata as $data) {
				 	$em->remove($data);
				 }

				 $textdata =  $em->getRepository('DocovaBundle:FormNumericValues')->findBy(array( 'Field' => $field));
				 foreach($textdata as $data) {
				 	$em->remove($data);
				 }

				 $textdata =  $em->getRepository('DocovaBundle:FormDateTimeValues')->findBy(array( 'Field' => $field));
				 foreach($textdata as $data) {
				 	$em->remove($data);
				 }

				 $textdata =  $em->getRepository('DocovaBundle:FormNumericValues')->findBy(array( 'Field' => $field));
				 foreach($textdata as $data) {
				 	$em->remove($data);
				 }

				 $textdata =  $em->getRepository('DocovaBundle:FormGroupValues')->findBy(array( 'Field' => $field));
				 foreach($textdata as $data) {
				 	$em->remove($data);
				 }


				 $em->remove($field);
				 $count ++;
			}
			
		}
		$em->flush();
		$output->writeln('Done.  removed '.$count.' fields!');

		
	}
}