<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//use Docova\DocovaBundle\Security\User\CustomACL;
//use Docova\DocovaBundle\Entity\EventLogs;
//use Docova\DocovaBundle\Entity\TrashedLogs;
//use Docova\DocovaBundle\Extensions\BufferedOutput;
//use Symfony\Component\Console\Input\StringInput;
use Docova\DocovaBundle\Index\Indexer;


/**
 * @author Chris Fales
 * Index document and attachment content
 *        
 */
class IndexDocumentsCommand extends ContainerAwareCommand 
{
	private $global_settings;
	private $batchsize=1000;
	private $offset=0;
	private $libraries=null;
	private $DEBUG=false;
	protected function configure()
	{
		$this
			->setName('docova:indexdocuments')
			->setDescription('FT Index Task')
   			->addArgument(
           		'batchsize',
               	InputArgument::OPTIONAL,
                'How many documents should be processed?'
            )
         	->addArgument(
               	'offset',
            	InputArgument::OPTIONAL,
               	'Where should we start processing from?'
			)
			->addArgument(
				'libraries',
				InputArgument::OPTIONAL,
				'The name of the target library list to index?'
			);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			 			
			$inputBatchSize = $input->getArgument('batchsize');
			//$this->batchsize = $inputBatchSize;
			if($inputBatchSize){
				$this->batchsize = intval($inputBatchSize);
			}

			$inputOffset = $input->getArgument('offset');
			if($inputOffset){
				$this->offset = intval($inputOffset);
			}
			
			$inputLibraries = $input->getArgument('libraries');
						
			$em = $this->getContainer()->get('doctrine')->getManager();
			$conn = $this->getContainer()->get('doctrine')->getConnection();
			$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
			$this->global_settings = $this->global_settings[0];
			
			echo "Index command - execute - Start".PHP_EOL;

			//obtain library list and process				
			$librariesList = $this->getLibraries($em);

			
			//Determine which libraries to process (all or specified)
			if (empty($inputLibraries)){			
				echo 'Processing all active libraries'.PHP_EOL;			

				if (empty($librariesList) || !is_array($librariesList)){
					echo 'No active libraries found to process'.PHP_EOL;
					return;
				}
					
				if ($this->DEBUG) echo ''.count($librariesList).' active libraries found to process'.PHP_EOL;
			}
			else{
				//Process specified libraries				
				echo 'Processing specified active libraries'.PHP_EOL;
				$inputLibsArray = split(';', $inputLibraries);
				
				$librariesList = array_filter($librariesList, function($libobj){
					return (in_array($libobj['id'], $inputLibsArray) || in_array($libobj['Library_Title'], $inputLibsArray));
				});
								
				echo ''.count($librariesList).' custom libraries found to process'.PHP_EOL;
			}
			
			//Process each library in list
			foreach ($librariesList as $library){
				try{				
					if ($this->DEBUG) echo "Index command - Indexing Library: ".$library['Library_Title']." ".$library['id'].PHP_EOL;					
					$this->indexDocuments($conn,$em,$library,$this->getDocumentsQuery($em,$library) );					 
					if ($this->DEBUG) echo "Index command - Indexed Library: ".$library['Library_Title']." ".$library['id'].PHP_EOL;
				}
				catch (\Exception $le){
					echo "Index command - Exception: ".$le->getMessage()." on line ". $le->getLine() . PHP_EOL;
				}
			}			
		}
		catch (\PDOException $e) {
			echo "Index command - Exception: ".$e->getMessage()." Code: ".$e->getCode()." on line ". $e->getLine() . PHP_EOL;
		}
		 catch (\Exception $e) { 
			echo "Index command - Exception: ".$e->getMessage()." on line ". $e->getLine() . PHP_EOL;
		}
					
		echo "Index command - execute - End".PHP_EOL;
		
	}

	private function getLibraries($em)
	{
		$qbLib = $em->getRepository("DocovaBundle:Libraries")->createQueryBuilder("l");
		$qbLib->select("l.id,l.Library_Title,l.isApp")
		->where("l.Trash='false'")
		->orderBy("l.Library_Title","ASC");
		return $qbLib->getQuery()->getArrayResult();
	}

	private function getDocumentsIdQuery($em,$library)
	{
		$qb=$em->getRepository("DocovaBundle:Documents")->createQueryBuilder("d");
		if($this->batchsize > 0){
			$qb->setMaxResults($this->batchsize);
		}
			
		if($this->offset > 0){
			$qb->setFirstResult($this->offset);
		}
		
		if($library['isApp'] == 1){
			$qb->select("d.id")
			->leftJoin('d.application', 'l')
			->where("d.Trash=?1 AND d.Indexed=?2 AND l.Trash=?3 AND l.id=?4")
			->setParameter(1, false)
			->setParameter(2, false)
			->setParameter(3, false)
			->setParameter(4, $library['id'])
			->orderBy("d.Index_Date","ASC");			
		}else{
			$qb->select("d.id")
			->leftJoin('d.folder', 'f')
			->leftJoin('f.Library', 'l')
			->where("d.Trash=?1 AND d.Indexed=?2 AND l.Trash=?3 AND l.id=?4")
			->setParameter(1, false)
			->setParameter(2, false)
			->setParameter(3, false)
			->setParameter(4, $library['id'])
			->orderBy("d.Index_Date","ASC");
		}
	
		return $qb;
	}

	private function getDocumentsQuery($em,$library)
	{		
		$qb=$em->getRepository("DocovaBundle:Documents")->createQueryBuilder("d");
		if($this->batchsize > 0){
			$qb->setMaxResults($this->batchsize);
		}
			
		if($this->offset > 0){
			$qb->setFirstResult($this->offset);
		}
		
		if($library['isApp'] == 1){
			$qb->select("d")
			->leftJoin('d.application', 'l')
			->where("d.Trash=?1 AND d.Indexed!=2 AND d.Indexed=?2 AND l.Trash=?3 AND l.id=?4")
			->setParameter(1, false)
			->setParameter(2, false)
			->setParameter(3, false)
			->setParameter(4, $library['id'])
			->orderBy("d.Index_Date","ASC");			
		}else{
			$qb->select("d")
			->leftJoin('d.folder', 'f')
			->leftJoin('f.Library', 'l')
			->where("d.Trash=?1 AND d.Indexed!=2 AND d.Indexed=?2 AND l.Trash=?3 AND l.id=?4")
			->setParameter(1, false)
			->setParameter(2, false)
			->setParameter(3, false)
			->setParameter(4, $library['id'])
			->orderBy("d.Index_Date","ASC");
		}
		
		return $qb;
	}

	/**	
	 * @param indexer
	 * @param qb
	 */
	 private function indexDocuments($conn,$em,$library,$qb) 
	 {
		if ($this->DEBUG){
			echo "Index command Running Query".PHP_EOL;
			echo "Query: ".$qb->getQuery()->getSQL().PHP_EOL;
		}

		try{
			$documents = $qb->getQuery()->getResult();
		}
		catch (\PDOException $qe){
			echo "Index command Query PDO Exception ".$qe->getMessage()." Code: ".$qe->getCode()." on line ". $qe->getLine() . PHP_EOL;

			if (!empty($this->batchsize) && $this->batchsize==1)
			{
				echo "Batch Size: ".$this->batchsize.PHP_EOL;
		 		//an error occured attempt to process a single document
		 		$qbId = $this->getDocumentsIdQuery($em, $library);
		 		$documents = $qb->getQuery()->getResult();
		 		echo count($documents) + " results";
		 		echo "Problem document id: ".$documents[0][0];
		 	}		
		 	return;	 	
		}
		catch (\Exception $qe){
		 	echo "Index command Query Exception ".$qe->getMessage()." on line ". $qe->getLine() . PHP_EOL;
		  	return;
		}
		
		if ($this->DEBUG) echo "Index command Query Complete".PHP_EOL;
		 
		if (!empty($documents) && count($documents) > 0)
		{			
			if ($this->DEBUG) echo "Initializing indexer ".PHP_EOL;
			$indexer = new Indexer($conn, $this->getContainer()->getParameter('document_root'));
			if ($this->DEBUG) echo "Initialized indexer ".PHP_EOL;
			
			$indexOpened = false;
			try {
				$indexOpened=$indexer->openIndex();
			} 
			catch (\Exception $e) {
				echo("Error: unable to open index for " . $indexer->indexName . " Error: ".$e->getMessage().PHP_EOL);
			}
			if(! $indexOpened){
				if(! $indexer->createIndex()){
					echo("Error: unable to create index for " . $indexer->indexName . PHP_EOL);
				}
			}

			//-- if an app we need to retrieve custom view listing
			$views = null;
			if($library['isApp'] == 1){
				$views = $em->getRepository('DocovaBundle:AppViews')->getAllViewIds($library['id']);
			}			
			
			if ($this->DEBUG) echo "Processing query results".PHP_EOL;
			$count = 0;
			try{					
				foreach ($documents as $docobj){  
					$result = null;
					
					//-- if an app view views get a listing of views this document resides in
					$viewswithdoc = array();
					if(!empty($views)){
						foreach ($views as $v)
						{
							$viewquery = "SELECT COUNT(*) FROM view_" . str_replace('-', '', $v) . " WHERE App_Id = ? AND Document_Id = ?";
							$viewresult = $conn->fetchArray($viewquery, array($library['id'], $docobj->getId()));
							if (!empty($viewresult[0])) {
								array_push($viewswithdoc, $v);
							}							
						}
					}

					
					try{																		
						if ($this->DEBUG) echo "About to index document: ".$docobj->getId().PHP_EOL;
						$result = $indexer->indexDocument($docobj, array('views'=>$viewswithdoc));
						
						if($result){
							if ($this->DEBUG) echo "Done indexing document: ".$docobj->getId().PHP_EOL;
							
							$docobj->setIndexed(true);
							$em->flush();
						
							$count ++;
							echo "Indexed ".$count." documents".PHP_EOL;
						}
						else{
							$docobj->setIndexed(false);
							$em->flush();
							
							$count ++;
							echo "COULD NOT Index document ".$docobj->getId()."!".PHP_EOL;
						}
					}
					catch(\Exception $iie){
						echo "Index Document - indexDocument: " .$docobj->getId() . " Exception: ".$iie->getMessage()." on line ". $iie->getLine() . PHP_EOL;
					}
				}
			}
			catch (\Exception $ie){
				echo "Index Document - Document: " .$docobj->getId() . " Exception: ".$ie->getMessage()." on line ". $ie->getLine() . PHP_EOL;
			}	
		}
	 }
}