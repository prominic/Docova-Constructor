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
use Docova\DocovaBundle\Validation\Validator;


/**
 * @author Chris Fales, Javad Rahimi, and Jeff Primeau
 * Based loosely on indexdocuments command
 * 
 * Processed each document one at a time to indentify any of the following issues
 * 
 * 1.  Identify problems querying the document
 * 2.  Identify problems querying the documents field values and other related elements
 */
class ValidateDocumentsCommand extends ContainerAwareCommand 
{
	private $global_settings;
	private $batchsize=100;
	private $offset=0;
	private $libraries=null;
	private $DEBUG=true;
	private $exceptions=0;
	private $folders=true;
	protected function configure()
	{
		$this
		->setName('docova:validatedocuments')
		->setDescription('Document Validation Task');

		$this->addArgument(
				'libraries',
				InputArgument::OPTIONAL,
				'The name of the target library list to index?'
		);

		$this->addArgument(
				'folders',
				InputArgument::OPTIONAL,
				'Queries document by folder bool'
		);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{

		try {
			
			
			$fh = fopen('validation_exceptions_'.date('j-m-y h-i-s').'.log','w');
						
			$inputLibraries = $input->getArgument('libraries');
			
			$folders = $input->getArgument('folders');
			if (!empty($folders))
				$this->folders = $folders;
						
			$em = $this->getContainer()->get('doctrine')->getManager();
			$conn = $this->getContainer()->get('doctrine')->getConnection();
			$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
			$this->global_settings = $this->global_settings[0];
			
			//echo "Validate command - execute - Start".PHP_EOL;
			
			//Determine which libraries to process (all or specified)
			if (empty($inputLibraries)){			
			//	echo 'Processing all active libraries'.PHP_EOL;
				
				//obtain library list and process				
				$librariesList = $this->getLibraries($em);

				if (empty($librariesList) || !is_array($librariesList)){
					echo 'No active libraries found to process'.PHP_EOL;
					return;
				}
					
			//	if ($this->DEBUG) echo ''.count($librariesList).' active libraries found to process'.PHP_EOL;
			}
			else{
				//Process specified libraries				
			//	echo 'Processing specified active libraries'.PHP_EOL;
				$librariesList = split(';', $inputLibraries);
				//echo ''.count($librariesList).' custom libraries found to process'.PHP_EOL;
			}
			
			//Process each library in list
			foreach ($librariesList as $library){
				try{
					//PDO returns each record as an array, convert to library name
					if (is_array($library))
						$library=$library['Library_Title'];
					
				//	if ($this->DEBUG) echo "Validate command - Validating Library: ".$library.PHP_EOL;	
					if ($this->folders==false){
						//Validate documents
						$docCount = $this->getDocumentsCount($em, $library);
						echo $docCount. " document(s) found in Library: ".$library;
						
					  	for($i=0;$i<=$docCount;$i++){
					  		echo PHP_EOL."Validating single Document ".$i." of ".$docCount;
					  		$docsQuery = $this->getDocumentsQuery($em,$library,1,$i);
					  		$result = $this->validateDocuments($fh,$conn,$em,$library,$docsQuery );				  		
					  	}
					}
					else{
						//Validate folders						
						$folders = $this->getFolders($em, $library);
						$folderCount=count($folders);
						echo $folderCount. " folders(s) found in Library: ".$library;

						for($i=0;$i<$folderCount;$i++){
							try{
								$folder = $folders[$i];
								$folderName = $folder->getFolderName();								
								echo PHP_EOL."Validating folder ".$i." of ".$folderCount." in Library: ".$library." Folder: ".$folderName."  ".PHP_EOL;
								
								$folderDocCount = $this->getDocumentsCount($em, $library,$folder);
								$folderMsg=PHP_EOL.$folderDocCount. " document(s) found in Library: ".$library." Folder: ".$folderName;
								
								$result = $this->getFolderDocuments($em,$library,$folder,intval(100000),0);
								//echo PHP_EOL.var_dump($result);
																
								echo PHP_EOL."Validated folder ".$i." of ".$folderCount." in Library: ".$library." Folder: ".$folderName."  ".PHP_EOL;
								
								echo $folderMsg;
								$VALIDATE_DOCUMENTS=false;
								if ($folderName=='Daniel')
									$VALIDATE_DOCUMENTS=true;
									
								if ($VALIDATE_DOCUMENTS){
									for($c=0;$c<=$folderDocCount;$c++){
										echo PHP_EOL."Validating Library: ".$library." Folder: ".$folderName.", Document ".$c." of ".$folderDocCount;
										$result = $this->validateDocuments($fh,$conn,$em,$library,$this->getDocumentsQuery($em,$library,1,$c) );
									}						
								}
												
							}
							catch(\Exception $pe){
								
								$this->exceptions++;
								fwrite($fh,'Library: '.$library.' Folder '.$folderName.",".$folder->getId().PHP_EOL);
								fwrite($fh,'Exception: '.$pe->getMessage().PHP_EOL);
								echo PHP_EOL."COULD NOT Validate folder documents ".$folder->getFolderName()."  ".$i." of ".$folderCount." in Library: ".$library;
								
								if ($VALIDATE_DOCUMENTS){
									try{
										$folderMsg=PHP_EOL.$folderDocCount. " document(s) found in Library: ".$library." Folder: ".$folderName;
										fwrite($fh,$folderMsg.PHP_EOL);
										for($x=0;$x<$folderDocCount;$x++){										
											echo PHP_EOL."Validating Library: ".$library." Folder: ".$folderName.", Document ".$x." of ".$folderDocCount.PHP_EOL;																						
											try{	
												
												$folderDocs = $this->getDocumentsQueryByFolder($em, $library, $folder,1, (intval($x)+1));												
												$result = $this->validateDocuments($fh,$conn,$em,$library,$folderDocs );												
												echo PHP_EOL."Validated ".$x;
												
												echo PHP_EOL."Validating field values for document ".$x;
												$this->validateFolderDocuments($fh, $conn, $em, $library, $result, $x);
												echo PHP_EOL."Validated field values for document ".$x;
											}
											catch(\PDOException $pe){												
												echo PHP_EOL."PDO Exception: ".$pe->getMessage()." Document ".$x." of ".$folderDocCount ;
											}
											catch(\Exception $pe){			
												echo PHP_EOL."Exception: ".$pe->getMessage()." Document ".$x." of ".$folderDocCount;
											}
											
										}
									}
									catch(\Exception $e){
										echo PHP_EOL."Could not validate documents - Exception: ".$e->getMessage();
									}
								}
							}						
							
						} //end folder loop
						
					} //end folder processing check
										 
				//	if ($this->DEBUG) echo "Validate command - Validated Library: ".$library.PHP_EOL;
				}
				catch (\Exception $le){
					echo PHP_EOL."Validate command - Exception: ".$le->getMessage()." on line ". $le->getLine() . PHP_EOL;
				}
			}	
			
		}
		catch (\PDOException $e) {
			echo PHP_EOL."Validate command - Exception: ".$e->getMessage()." Code: ".$e->getCode()." on line ". $e->getLine() . PHP_EOL;
		}
		 catch (\Exception $e) { 
			echo PHP_EOL."Validate command - Exception: ".$e->getMessage()." on line ". $e->getLine() . PHP_EOL;
		}
		
		echo PHP_EOL.$this->exceptions." Exceptions Detected";
		fclose($fh);				
		//echo "Validate command - execute - End".PHP_EOL;
		
	}
	private function getLibraries($em){
		$qbLib = $em->getRepository("DocovaBundle:Libraries")->createQueryBuilder("l");
		$qbLib->select("l.Library_Title")
		->where("l.Trash='false'")
		->orderBy("l.Library_Title","ASC");
		return $qbLib->getQuery()->getArrayResult();
	}
	private function getDocumentsIdQuery($em,$library){
		$qb=$em->getRepository("DocovaBundle:Documents")->createQueryBuilder("d");
		if($this->batchsize > 0){
			$qb->setMaxResults($this->batchsize);
		}
			
		if($this->offset > 0){
			$qb->setFirstResult($this->offset);
		}
			
		$qb->select("d.id")
		->leftJoin('d.folder', 'f')
		->leftJoin('f.Library', 'l')
		->where("d.Trash=?1 AND d.Indexed=?2 AND l.Trash=?3 AND l.Library_Title=?4")
		->setParameter(1, false)
		->setParameter(2, false)
		->setParameter(3, false)
		->setParameter(4, $library)
		->orderBy("d.Index_Date","ASC");
	
		return $qb;
	}
	private function getDocumentsQuery($em,$library,$size,$offset){				
		$qb=$em->getRepository("DocovaBundle:Documents")->createQueryBuilder("d");		
		$qb->setMaxResults($size);
		$qb->setFirstResult($offset);
		
		$qb->select("d")
		->leftJoin('d.folder', 'f')
		->leftJoin('f.Library', 'l')
		->where("d.Trash=?1 AND l.Trash=?3 AND l.Library_Title=?4")
		->setParameter(1, false)		
		->setParameter(3, false)
		->setParameter(4, $library)
		->orderBy("d.Index_Date","ASC");
		
		return $qb;
	}
	private function getDocumentsQueryByFolder($em,$library,$folder,$size=1,$offset=0){
		echo "getDocumentsQueryByFolder Size: ".$size+" Offset: ".$offset;
		$qbFolderDocs=$em->getRepository("DocovaBundle:Documents")->createQueryBuilder("d");
		try{
			
			$qbFolderDocs->setMaxResults($size);
			$qbFolderDocs->setFirstResult($offset);
		
			$qbFolderDocs->select("d")
			->leftJoin('d.folder', "f")
			->leftJoin('f.Library', 'l')
			->where("d.Trash=?1 AND l.Trash=?3 AND l.Library_Title=?4 AND f.id=?5")
			->setParameter(1, false)
			->setParameter(3, false)
			->setParameter(4, $library)	
			->setParameter(5, $folder->getId());
			return $qbFolderDocs;	
		}
		catch (\PDOException $pe){
			echo "getDocumentsQueryByFolder() ".$pe.getMessage();
		}
		catch (\Exception $e){
			echo "getDocumentsQueryByFolder() ".$e.getMessage();
		}
		return $null;
	}
	private function getFolderDocuments($em,$library,$folder){
		$isAdmin = true;
		$showDisplayName = false;
		$user = $this->getUser($em,"DOCOVA SE");		
//		echo PHP_EOL."Email: "+$user->getUserMail();
		//echo PHP_EOL."Name: "+$user->getUsername();
		$documents = $em->getRepository('DocovaBundle:Documents')->getAllFolderDocuments($folder, 1, 100000, $user, true);
		return $documents;
//		$documents = $this->getAllFolderDocuments($em,$folder, $size,$offset, $user,$isAdmin, true, true, true, $showDisplayName);
	}
	
	private function getDocumentsCount($em,$library,$folder=null){
		$docQb=$em->getRepository("DocovaBundle:Documents")->createQueryBuilder("d");		
		$docQb->select("d.id")
		->leftJoin('d.folder', 'f')
		->leftJoin('f.Library', 'l');
		
		if (!empty($folder))
			$docQb->where("d.Trash=?1 AND l.Trash=?3 AND l.Library_Title=?4 AND f.id=?5");
		else
			$docQb->where("d.Trash=?1 AND l.Trash=?3 AND l.Library_Title=?4");
		
		$docQb->setParameter(1, false);
		$docQb->setParameter(3, false);	
		$docQb->setParameter(4, $library);
		if (!empty($folder))
			$docQb->setParameter(5, $folder->getId());
		$docQb->orderBy("d.Index_Date","ASC");
	
		return count($docQb->getQuery()->getResult());
	}
	private function getFolders($em,$library){
		$folderQb=$em->getRepository("DocovaBundle:Folders")->createQueryBuilder("f");
		$folderQb->select("f")
		->leftJoin('f.Library', 'l')
		->where("l.Trash=?1 AND l.Library_Title=?2")
		->setParameter(1, false)		
		->setParameter(2, $library)
		->orderBy("f.Folder_Name","ASC");	
		return $folderQb->getQuery()->getResult();
	}
	private function getUser($em,$name){
		$qb=$em->getRepository("DocovaBundle:UserAccounts")->createQueryBuilder("u");
		$qb->select("u")		
		->where("u.username=?1")
		->setParameter(1, $name);
		$results = $qb->getQuery()->getResult();
		
		return $results[0];
	}
	/**	
	 * @param indexer
	 * @param qb
	
	 */
	 private function validateDocuments($fh,$conn,$em,$library,$qb) {
		 if ($this->DEBUG){
		// 	echo "Validate command Running Query".PHP_EOL;
		// 	echo "Query: ".$qb->getQuery()->getSQL().PHP_EOL;
		 }
		 
		 try{
		 	$documents = $qb->getQuery()->getResult();
		 }
		 catch (\PDOException $qe){
		 	echo "Validate command Query PDO Exception ".$qe->getMessage()." Code: ".$qe->getCode()." on line ". $qe->getLine() . PHP_EOL;
		 	return;	 	
		 }
		 catch (\Exception $qe){
		 	echo "Validate command Query Exception ".$qe->getMessage()." on line ". $qe->getLine() . PHP_EOL;
		  	return;
		 }
		//if ($this->DEBUG) echo "Validate command Query Complete".PHP_EOL;		 
		if (!empty($documents) && count($documents) > 0){
		//	if ($this->DEBUG) echo "Initializing validator ".PHP_EOL;
			$indexer = new Validator($em, $conn, $this->getContainer()->getParameter('document_root'));
		//	if ($this->DEBUG) echo "Initialized validator ".PHP_EOL;						
		//	if ($this->DEBUG) echo "Processing query results".PHP_EOL;
			$count = 0;
			try{					
				foreach ($documents as $docobj){  
					$result = null;
					try{	
						$thisLibrary = $docobj->getFolder()->getLibrary()->getLibraryTitle();
						if (!empty($librariesList)){
							//skip processing the document if not a matching libraring
							if (in_array($thisLibrary, $librariesList)===false){
								echo 'Skipping '.$docobj->getId().' in library '.$thisLibrary.PHP_EOL;
								continue;	
							}		
						}
						
					//	if ($this->DEBUG) echo "About to validate document: ".$docobj->getId().PHP_EOL;
						$result = $indexer->validateDocument($docobj);
					//	if ($this->DEBUG) echo "Done validating document: ".$docobj->getId().PHP_EOL;
						
						if($result){
							$count++;
							//echo PHP_EOL."Validated ".$count." documents";
						}
						else{														
							$count++;
							$this->exceptions++;
							fwrite($fh,"Document: ".$docobj->getId().PHP_EOL);
							echo PHP_EOL."COULD NOT Validate document ".$docobj->getId()."!".PHP_EOL;
						}
					}
					catch(\Exception $iie){
						echo "Validate Document - validateDocument: " .$docobj->getId() . " Exception: ".$iie->getMessage()." on line ". $iie->getLine() . PHP_EOL;
					}
				}
			}
			catch (\Exception $ie){
				echo "Validate Document - Document: " .$docobj->getId() . " Exception: ".$ie->getMessage()." on line ". $ie->getLine() . PHP_EOL;
			}	
		}}
		/**
		 * @param indexer
		 * @param qb
		
		 */
		private function validateFolderDocuments($fh,$conn,$em,$library,$documents,$x) {
			if ($this->DEBUG){
				// 	echo "Validate command Running Query".PHP_EOL;
				// 	echo "Query: ".$qb->getQuery()->getSQL().PHP_EOL;
			}
			
			//if ($this->DEBUG) echo "Validate command Query Complete".PHP_EOL;
			if (!empty($documents) && count($documents) > 0){
				//	if ($this->DEBUG) echo "Initializing validator ".PHP_EOL;
				$indexer = new Validator($conn, $this->getContainer()->getParameter('document_root'));
				//	if ($this->DEBUG) echo "Initialized validator ".PHP_EOL;
				//	if ($this->DEBUG) echo "Processing query results".PHP_EOL;
				$count = 0;
				try{
					foreach ($documents as $docobj){
						$result = null;
						try{
							
							$result = $this->getFolderDocuments($em,$library,$folder,intval(1),$x);
							
							//	if ($this->DEBUG) echo "About to validate document: ".$docobj->getId().PHP_EOL;
							if (!empty($result[0]))
								$result = $indexer->validateDocument($result[0]);
							//	if ($this->DEBUG) echo "Done validating document: ".$docobj->getId().PHP_EOL;
		
							if($result){
								$count++;
								//echo PHP_EOL."Validated ".$count." documents";
							}
							else{
								$count++;
								$this->exceptions++;
								fwrite($fh,"Document: ".$docobj->getId().PHP_EOL);
								echo PHP_EOL."COULD NOT Validate document ".$docobj->getId()."!".PHP_EOL;
							}
						}
						catch(\Exception $iie){
							echo "Validate Document - validateDocument: " .$docobj->getId() . " Exception: ".$iie->getMessage()." on line ". $iie->getLine() . PHP_EOL;
						}
					}
				}
				catch (\Exception $ie){
					echo "Validate Document - Document: " .$docobj->getId() . " Exception: ".$ie->getMessage()." on line ". $ie->getLine() . PHP_EOL;
				}
			}}
		private function validateFolders($fh,$conn,$em,$library,$qb) {
			if ($this->DEBUG){
				// 	echo "Validate command Running Query".PHP_EOL;
				// 	echo "Query: ".$qb->getQuery()->getSQL().PHP_EOL;
			}
				
			try{
				$folders = $qb->getQuery()->getResult();
			}
			catch (\PDOException $qe){
				echo "Validate command Query PDO Exception ".$qe->getMessage()." Code: ".$qe->getCode()." on line ". $qe->getLine() . PHP_EOL;
				return;
			}
			catch (\Exception $qe){
				echo "Validate command Query Exception ".$qe->getMessage()." on line ". $qe->getLine() . PHP_EOL;
				return;
			}
			//if ($this->DEBUG) echo "Validate command Query Complete".PHP_EOL;
			if (!empty($folders) && count($folders) > 0){
				//	if ($this->DEBUG) echo "Initializing validator ".PHP_EOL;
				
				//	if ($this->DEBUG) echo "Initialized validator ".PHP_EOL;
				//	if ($this->DEBUG) echo "Processing query results".PHP_EOL;
				$count = 0;
				try{
					foreach ($folders as $folderobj){
						$result = null;
						try{
							$thisLibrary = $folderObj->getLibrary()->getLibraryTitle();
							if (!empty($librariesList)){
								//skip processing the Folder if not a matching library
								if (in_array($thisLibrary, $librariesList)===false){
									echo 'Skipping '.$folderobj->getId().' in library '.$thisLibrary.PHP_EOL;
									continue;
								}
							}
		
							//	if ($this->DEBUG) echo "About to validate Folder: ".$docobj->getId().PHP_EOL;							
							//	if ($this->DEBUG) echo "Done validating Folder: ".$docobj->getId().PHP_EOL;
		
							if($result){
								$count++;
								//echo PHP_EOL."Validated ".$count." Folders";
							}
							else{
								$count++;
								$this->exceptions++;
								fwrite($fh,$folderobj->getId().PHP_EOL);
								echo PHP_EOL."COULD NOT Validate folder ".$folderobj->getId()."!".PHP_EOL;
							}
						}
						catch(\Exception $iie){
							echo "Validate Folder - validateFolder: " .$folderobj->getId() . " Exception: ".$iie->getMessage()." on line ". $iie->getLine() . PHP_EOL;
						}
					}
				}
				catch (\Exception $ie){
					echo "Validate Folder - Folder: " .$folderobj->getId() . " Exception: ".$ie->getMessage()." on line ". $ie->getLine() . PHP_EOL;
				}
			}}

}