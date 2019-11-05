<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Docova\DocovaBundle\Index\Indexer;


/**
 * @author Chris Fales
 * Create document index
 *
 */
class CreateIndexCommand extends ContainerAwareCommand 
{
	private $global_settings;
	private $DEBUG=true;
	protected function configure()
	{
		$this
			->setName('docova:createindex')
			->setDescription('Create FT Index');	
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			
			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion('Do you want to create/recreate the DOCOVA full text index?'.PHP_EOL.'WARNING: any existing index data will be lost! (y/n):', false);
			if (!$helper->ask($input, $output, $question)) {
				echo "Creation of index cancelled.".PHP_EOL;		
				return;
			}		
			
			$em = $this->getContainer()->get('doctrine')->getManager();
			$conn = $this->getContainer()->get('doctrine')->getConnection();
			$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
			$this->global_settings = $this->global_settings[0];
			
			echo "Recreate Index command - execute - Start".PHP_EOL;
			echo "Initializing indexer ".PHP_EOL;
			$indexer = new Indexer($conn, $this->getContainer()->getParameter('document_root'));
			echo "Initialized indexer ".PHP_EOL;
			
			$indexOpened = false;
			try {
				$indexOpened=$indexer->openIndex();
			} 
			catch (\Exception $e) {
			}
			
			if($indexOpened){
				echo "Deleting existing index.".PHP_EOL;				
				$indexer->deleteIndex();
				echo "Existing index deleted.".PHP_EOL;				
			}
			
			if(! $indexer->createIndex()){
				echo("Error: unable to create index for " . $indexer->indexName . PHP_EOL);
			}			
		
		}
		catch (\PDOException $e) {
			echo "Recreate Index command - Exception: ".$e->getMessage()." Code: ".$e->getCode()." on line ". $e->getLine() . PHP_EOL;
		}
		 catch (\Exception $e) { 
			echo "Recreate Index command - Exception: ".$e->getMessage()." on line ". $e->getLine() . PHP_EOL;
		}
					
		echo "Recreate Index command - execute - End".PHP_EOL;
		
	}


}