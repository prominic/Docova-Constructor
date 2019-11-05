<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
//use Docova\DocovaBundle\Entity\GlobalSettings;
//use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\RelatedDocuments;
use Docova\DocovaBundle\Entity\UserProfile;
use Docova\DocovaBundle\Security\User\adLDAP;


/**
 * @author Chris Fales
 * Adjust Data Command
 *
 */
class AdjustDataCommand extends ContainerAwareCommand
{
	private $global_settings;
	private $appid="";
	private $em=null;
	private $conn=null;
	private $qhelper=null;
	private $input=null;
	private $output=null;
	private $DEBUG=false;

	protected function configure()
	{
		$this
		->setName('docova:adjustdatacommand')
		->setDescription('Data manipulation utility command');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
	
		$this->input = $input;
		$this->output = $output;

		$this->qhelper = $this->getHelper('question');
		
		$this->em = $this->getContainer()->get('doctrine')->getManager();
		
		$this->conn = $this->em->getConnection();
		
		try{
			$this->global_settings = $this->em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		}
		catch (\PDOException $pe){
			$output->writeln("AdjustDataCommand->execute() - Error PDO: ".$pe->getMessage());
			throw new \Exception("AdjustDataCommand->execute() - Error PDO: ".$pe->getMessage().PHP_EOL);
		}		
		$this->global_settings = $this->global_settings[0];
		
		
		$question = new ChoiceQuestion('What type of data adjustment do you want to perform? ', 
		    array('Split Field Values',
		        'Rename Users', 
		        'Refresh User Data from LDAP', 
		        'Check User Accounts', 
		        'Update Fields from XML',
		        'Apply Regex to Field',
		        'Link Responses',
		        'Quit') 
		,'');
		$action = $this->qhelper->ask($this->input, $this->output, $question);
		
		switch ($action){
			case "Split Field Values":	
				if($this->getApp()){
					$this->splitFieldValues();
				}
				break;
			case "Rename Users":
				$this->renameUsers();
				break;
			case "Refresh User Data from LDAP":
				$this->refreshUsers();
				break;				
			case "Check User Accounts":
				$this->checkUsers();
				break;
			case "Update Fields from XML":
			    $this->updateFromXml();
			    break;
			case "Apply Regex to Field":
			    if($this->getApp()){
    			    $this->applyRegexToField();
			    }
			    break;
			case "Link Responses":
			    if($this->getApp()){
    			    $this->linkResponses();
			    }
			    break;
			case "Quit":
				echo("Quit selected. Exiting.".PHP_EOL);
				return;
				break;
		}

	}
	
	protected function getApp(){
		$question = new Question('Enter the application/library id that you wish to modify: ', '');
		$this->appid = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);
		
		if(empty($this->appid)){
			echo "Application ID required. Exiting.".PHP_EOL;
			return false;
		}
				
		$qbLib = $this->em->getRepository("DocovaBundle:Libraries")->createQueryBuilder("l");
		$qbLib->select("l.id,l.Library_Title,l.isApp")
		->where("l.Trash='false'")
		->andWhere("l.id=?1")
		->orderBy("l.Library_Title","ASC")
		->setParameter(1, $this->appid);
		$appdata = $qbLib->getQuery()->getArrayResult();
		if(empty($appdata)){
			echo("No application found. Exiting.".PHP_EOL);
			return false;
		}		
		
		return true;
	}
	
	protected function checkUsers(){
		$driver = $this->conn->getDriver()->getName();
		
		//-- get a list of user accounts
		$users = $this->em->getRepository("DocovaBundle:UserAccounts")->findAll();
		
		echo(PHP_EOL."=============  Checking Users - Start =============".PHP_EOL);
		
		$profilescreated = 0;	
		$nameformatschanged = 0;
		$assigneddnnames = 0;
		$aclidentitiescreated = 0;
		$userrolesadded = 0;
		
		$default_role = $this->em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role'=>'ROLE_USER'));
		
		foreach($users as $user){
		    //-- check user name formats
		    if($user->getUserNameDnAbbreviated() !== $this->getUserAbbreviatedName($user->getUserNameDn())){
		        if(trim($user->getUserNameDn()) == ""){
		            $user->setUserNameDn($this->getUserDnName($user->getUserNameDnAbbreviated()));
		            $this->em->flush();
		            $assigneddnnames ++;
		        }else{
    		        echo("name mismatch: ".$user->getUserNameDnAbbreviated()." != ".$this->getUserAbbreviatedName($user->getUserNameDn()).PHP_EOL);
		        }
		    }
		    //-- reformat DN name
		    if(false === strrpos($user->getUserNameDn(), "CN=")){
		        $user->setUserNameDn($this->getUserDnName($user->getUserNameDn()));
		        $this->em->flush();
		        $nameformatschanged ++;
		    }

			
			//-- check user profile records					
			$profile = $user->getUserProfile();
			if(empty($profile)){
				$nparts = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $user->getUserNameDnAbbreviated());
				if(count($nparts) < 2){
				    $nparts = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $user->getUserNameDnAbbreviated());
				}
				$nparts = explode("=", $nparts[0]);
				$cn = (count($nparts) > 1 ? $nparts[1] : $nparts[0]);
				$nparts = explode(" ", $cn);
			
				$profile = new UserProfile();
				$profile->setUser($user);
				$profile->setFirstName((count($nparts) > 1 ? $nparts[0] : ''));
				$profile->setLastName($nparts[count($nparts)-1]);
				$profile->setDisplayName($cn);
				$profile->setAccountType(false);
				$profile->setMailServerURL('UNKNOWN');
				$profile->setUserMailSystem('O');
				$profile->setLanguage('en');
				
				$this->em->persist($profile);
				$this->em->flush();
				$profilescreated ++;		
			}
			
			//-- following checks for active users only
			if(!$user->getTrash()){
				//--check acl security identities
				$shortname = $user->getUsername();
				$rd = 'Docova\DocovaBundle\Entity\UserAccounts-'.$shortname;
				
				$security_id = $this->conn->fetchArray("SELECT id FROM acl_security_identities WHERE identifier = ? AND username = ?;", array($rd, true));
				$security_id = $security_id[0];
				if (empty($security_id))
				{
					$stmt = $this->conn->prepare("INSERT INTO acl_security_identities (identifier, username) VALUES (?, ?);");
					$stmt->bindValue(1, $rd);
					$stmt->bindValue(2, true);
					$stmt->execute();
					$aclidentitiescreated ++;
				}
				
				
				//-- check user roles
				$user_roles = $user->getRoles();
				if(empty($user_roles)){
					$user->addRoles($default_role);
					$this->em->flush();
					$userrolesadded ++;					
				}
				
			}		
			
		}
		
		$updatesmade = false;
		if(!empty($assigneddnnames)){
		    echo (PHP_EOL."  Updated ".$assigneddnnames." distinguished names missing from user profile records.".PHP_EOL);
		    $updatesmade = true;
		}
		if(!empty($nameformatschanged)){
		    echo (PHP_EOL."  Updated ".$nameformatschanged." distinguished name formats.".PHP_EOL);
		    $updatesmade = true;
		}
		if(!empty($profilescreated)){
			echo (PHP_EOL."  Created ".$profilescreated." missing user profile records.".PHP_EOL);
			$updatesmade = true;
		}
		if(!empty($aclidentitiescreated)){
			echo (PHP_EOL."  Created ".$aclidentitiescreated." missing user acl security identity records.".PHP_EOL);
			$updatesmade = true;				
		}
		if(!empty($userrolesadded)){
			echo (PHP_EOL."  Set default user role for ".$userrolesadded." user account(s).".PHP_EOL);
			$updatesmade = true;
		}		
		if(!$updatesmade){
			echo (PHP_EOL."  No user records were updated.".PHP_EOL);
		}
		
		echo(PHP_EOL."=============  Checking Users - Finished =============".PHP_EOL);
	}
	
	protected function renameUsers(){
		$question = new Question('Enter the path to the rename csv file: ', '');
		$filename = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);
		
		if(empty($filename)){
			echo "Filename required. Exiting.".PHP_EOL;
			return false;
		}
		
		if(!file_exists($filename)){
			echo "Filename [". $filename ."] not found. Exiting.".PHP_EOL;
			return false;				
		}
		
		$question = new ConfirmationQuestion('Merge renamed users if duplicate? ', true);
		$mergedups = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);
				
		$question = new ConfirmationQuestion('Look up email addresses from LDAP? ', true);
		$lookupemail = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);
		
		$question = new ConfirmationQuestion('Update matching text values? ', false);
		$updatetextvalues = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);

		$question = new ConfirmationQuestion('Update migration ACL records? ', false);
		$updateacl = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);
		
		
		$updatecount = 0;
		
		$logfile = fopen("adjustdata.log", "w");
		fwrite($logfile, "OLD_ACCOUNT_NAME,NEW_ACCOUNT_NAME,NEW_DN_NAME,NEW_DN_NAME_AB,NEW_EMAIL,NOTES".PHP_EOL);
		
		echo(PHP_EOL."=============  Renaming Users - Start =============".PHP_EOL);				
		
		$row = 1;
		if (($handle = fopen($filename, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				if($row == 1 && ($data[0] == "DOMINO_NAME" || $data[0] == "NAME")){
					continue;
				}
				$row++;
				
				$renamed = false;
				$merged = false;
				
				$logdata = "";
				$comment = "";	
				
				$num = count($data);
				$dname = $data[0];
				$abname = $this->getUserAbbreviatedName($dname);
				$cname = explode("/",$abname)[0];
				
				//-- look for user based on distinguished name and abbreviated name
				$qbUsers = $this->em->getRepository("DocovaBundle:UserAccounts")->createQueryBuilder("ua");
				$qbUsers->select("ua");
				$qbUsers->where("ua.userNameDn=?1 OR ua.userNameDnAbbreviated=?2");
				$qbUsers->setParameter(1, $dname);
				$qbUsers->setParameter(2, $abname);
				
				$qbQuery = $qbUsers->getQuery();
				if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}				
				$userdata = $qbQuery->getResult();
				
				//-- look for user based on common name
				if(empty($userdata)){
					$qbUsers = $this->em->getRepository("DocovaBundle:UserAccounts")->createQueryBuilder("ua");
					$qbUsers->select("ua");
					$qbUsers->where("ua.userNameDn=?1 OR ua.userNameDnAbbreviated=?2");
					$qbUsers->setParameter(1, $cname);
					$qbUsers->setParameter(2, $cname);
					
					$qbQuery = $qbUsers->getQuery();
					if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}
					$userdata = $qbQuery->getResult();
				}
				
				//-- look for user based on short name
				if(empty($userdata)){						
					$qbUsers = $this->em->getRepository("DocovaBundle:UserAccounts")->createQueryBuilder("ua");
					$qbUsers->select("ua");
					$qbUsers->where("ua.username=?1");
					$qbUsers->setParameter(1, $cname);
						
					$qbQuery = $qbUsers->getQuery();
					if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}					
					$userdata = $qbQuery->getResult();
				}		
				
				if(!empty($userdata)){
					$shortname = $userdata[0]->getUsername();
					$oldabname = $userdata[0]->getUserNameDn();
					$newshortname = $data[1];
					$newdnname = $data[2];
					if($newdnname == ""){
					    $newdnname = $newshortname;
					}
					$newabname = $this->getUserAbbreviatedName($newdnname);
					$newmail = "";
					
					//check if this name already exists
					$qbUsers = $this->em->getRepository("DocovaBundle:UserAccounts")->createQueryBuilder("ua");
					$qbUsers->select("ua");
					$qbUsers->where("ua.username=?1");
					$qbUsers->setParameter(1, $newshortname);
					$qbQuery = $qbUsers->getQuery();
					if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}					
					$dupuserdata = $qbQuery->getResult();
					if(!empty($dupuserdata) && count($dupuserdata)>0){
					    //We need to merge users instead of rename
					    echo("Duplicate username found for [".$newshortname."]".PHP_EOL);
					    if($mergedups == true){	
					        $newshortname = $dupuserdata[0]->getUsername();
					        $newdnname = $dupuserdata[0]->getUserNameDn();
					        $newabname = $dupuserdata[0]->getUserNameDnAbbreviated();
					        $newmail = $dupuserdata[0]->getUserMail();
					        
					        if($this->mergeUserAccounts($userdata[0], $dupuserdata[0])){
					            $comment .= "Merged with existing username.";				
					            $merged = true;
					        }else{
					            $comment .= "Duplicate username found. Unable to merge names.";					            
					        }
					    }else{
					        $comment .= "Duplicate username found.";				        
					    }
					}else{			
    				    $userdata[0]->setUsername($newshortname);
				        $userdata[0]->setUserNameDn($newdnname);
					    $userdata[0]->setUserNameDnAbbreviated($newabname);
					
    					if($lookupemail == true){
    						$ldapdata = $this->getLDAPData($newshortname);
    						if(!empty($ldapdata)){
    							$newmail = $ldapdata["mail"];
    							if(!empty($newmail)){
    								$userdata[0]->setUserMail($newmail);
    							}
    						}
    					}

					    $this->em->flush();					    					
    					$renamed = true;
					}
					
					$logdata .= "\"".$shortname."\",";
					$logdata .= "\"".$newshortname."\",";
					$logdata .= "\"".$newdnname."\",";
					$logdata .= "\"".$newabname."\",";
					$logdata .= "\"".$newmail."\"";
					
					if($renamed){
    					if($shortname !== $newshortname){
						
    						$query = "SELECT id FROM acl_security_identities WHERE identifier=? AND username=1";
    						$stmt = $this->conn->prepare($query);
    						$stmt->bindValue(1, "Docova\DocovaBundle\Entity\UserAccounts-".$shortname);
    						$result = $stmt->execute();
    						if($result == true){
    							$result = $stmt->fetchAll();
    							if(count($result) == 1){
    								//--need to also update security identity
    								$query = "UPDATE acl_security_identities SET identifier = ? WHERE id=?;";
    								$stmt = $this->conn->prepare($query);
    								$stmt->bindValue(1, "Docova\DocovaBundle\Entity\UserAccounts-".$newshortname);
    								$stmt->bindValue(2, $result[0]["id"]);
    								$result = $stmt->execute();
    								if($result !== true){
    									$comment .= "Error updating security identity.";
    								}else{
    									$comment .= "Updated security identity.";									
    								}
    								
    							}							
    						}
    												
    					}
					}
					
					if($renamed || $merged){
    					if($updatetextvalues && !empty($oldabname)){
    						//--need to update any matching text values
    						$query = "UPDATE tb_form_text_values SET Summary_Value = ?, Field_Value = ? WHERE Summary_Value=?;";
    						$stmt = $this->conn->prepare($query);
    						$stmt->bindValue(1, $newabname);
    						$stmt->bindValue(2, $newabname);
    						$stmt->bindValue(3, $oldabname);
    						$result = $stmt->execute();
    						if($result !== true){
    							$comment .= "Error changing matching field text values.";
    						}else{
    							$comment .= "Modified matching field text values.";
    						}
    					}
					
    					if($updateacl){
    						//--need to update any matching migrated acl values
    						$query = "UPDATE tb_migrated_app_ace SET name = ? WHERE name=?;";
    						$stmt = $this->conn->prepare($query);
    						$stmt->bindValue(1, $newdnname);
    						$stmt->bindValue(2, $dname);
    						$result = $stmt->execute();
    						if($result !== true){
    							$comment .= "Error changing migrated acl entries.";
    						}else{
    							$comment .= "Modified migrated acl entries.";
    						}
    					}	
					}
					
								
					$logdata .= ",\"".$comment."\"";
					fwrite($logfile, $logdata.PHP_EOL);
					if($renamed || $merged){
    					$updatecount ++;
					}
				}
			}
			fclose($handle);
		}
		fclose($logfile);	
		if(!empty($updatecount)){
			echo (PHP_EOL."  Updated ".$updatecount." user records.  See adjustdata.log for details.".PHP_EOL);
		}else{
			echo (PHP_EOL."  No user records were updated.".PHP_EOL);			
		}
		echo(PHP_EOL."=============  Renaming Users - Finished =============".PHP_EOL);
	}
	
	
	protected function refreshUsers(){	
		$updatecount = 0;		
		
		//-- get a list of user accounts
		$users = $this->em->getRepository("DocovaBundle:UserAccounts")->findAll();
	
		$logfile = fopen("adjustdata.log", "w");
		fwrite($logfile, "ACCOUNT_NAME,NEW_EMAIL".PHP_EOL);		
		
		echo(PHP_EOL."=============  Refreshing Users - Start =============".PHP_EOL);
	
		foreach($users as $user){			
			//-- following checks for active users only
			if(!$user->getTrash()){
				$ldapdata = $this->getLDAPData($user->getUsername());
				if(!empty($ldapdata)){
					$newmail = $ldapdata["mail"];
					if(!empty($newmail)){
						if($newmail !== $user->getUserMail()){
							$user->setUserMail($newmail);
							$updatecount ++;
						
							fwrite($logfile, $user->getUsername().",".$newmail.PHP_EOL);
						
							$this->em->flush();
						}
					}
				}				
			}
				
		}
		fclose($logfile);

		if(!empty($updatecount)){
			echo (PHP_EOL."  Updated ".$updatecount." user records.  See adjustdata.log for details.".PHP_EOL);
		}else{
			echo (PHP_EOL."  No user records were updated.".PHP_EOL);
		}	
		echo(PHP_EOL."=============  Refreshing Users - Finished =============".PHP_EOL);
	}	
	
	protected function linkResponses(){
	    $updatecount = 0; 
	    	    
	    $question = new Question('What field do you want to use as the response document parent look up key (default=$ref)?', '$ref');
	    $responsefieldname = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);

	    $question = new Question('What field on the parent record do you want to use to match the response key (default=dockey)?', 'dockey');
	    $parentfieldname = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    if(empty($responsefieldname) || empty($parentfieldname)){
	        echo("Missing required field name parameters. Exiting.".PHP_EOL);
	        return $updatecount;
	    }
	    
		$responsefieldname = strtolower($responsefieldname);
		$parentfieldname = strtolower($parentfieldname);
		
		$fieldvalprefix = '';
		if($parentfieldname == "dockey" && $responsefieldname == '$ref'){
			$fieldvalprefix = 'DK';
        }		
	    
	    $qbFieldVals = $this->em->getRepository("DocovaBundle:FormTextValues")->createQueryBuilder("tval");
	    $qbFieldVals->leftJoin("tval.Field", "fld");
	    $qbFieldVals->leftJoin("fld.form", "frm");
	    $qbFieldVals->leftJoin("fld.Subform", "sbfrm");
	    $qbFieldVals->leftJoin("frm.application", "app1");
	    $qbFieldVals->leftJoin("sbfrm.application", "app2");
	    $qbFieldVals->select("IDENTITY(tval.Document, 'id') AS Doc_Id, tval.fieldValue AS Field_Value");
	    $qbFieldVals->where("fld.fieldName=?1");
	    $qbFieldVals->andWhere("fld.fieldType=0");
	    $qbFieldVals->andWhere("app1.id=?2 OR app2.id=?2");
	    $qbFieldVals->addOrderBy("Doc_Id", "ASC");
	    $qbFieldVals->addOrderBy("Field_Value", "ASC");
	    $qbFieldVals->setParameter(1, $responsefieldname);
	    $qbFieldVals->setParameter(2, $this->appid);
	    $qbFieldVals->distinct();
	    
	    $qbQuery = $qbFieldVals->getQuery();
	    if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}
	    
	    $responsefielddata = $qbQuery->getArrayResult();
	    
	    if(empty($responsefielddata)){
	        echo("No response records found in the specified application. Exiting.".PHP_EOL);
	        return $updatecount;
	    }
	    
	    echo(PHP_EOL."=============  Linking Response Documents - Start =============".PHP_EOL);
		echo("found ".count($responsefielddata)." potential response documents.".PHP_EOL);
	    
	    $docrepository = $this->em->getRepository("DocovaBundle:Documents");
	    $relateddoc_repository = $this->em->getRepository('DocovaBundle:RelatedDocuments');
	    
	    foreach($responsefielddata as $fieldinfo){
	        if($this->DEBUG){var_dump($fieldinfo);}	        
	        
	        $qbFieldVals = $this->em->getRepository("DocovaBundle:FormTextValues")->createQueryBuilder("tval");
	        $qbFieldVals->leftJoin("tval.Field", "fld");
	        $qbFieldVals->leftJoin("fld.form", "frm");
	        $qbFieldVals->leftJoin("fld.Subform", "sbfrm");
	        $qbFieldVals->leftJoin("frm.application", "app1");
	        $qbFieldVals->leftJoin("sbfrm.application", "app2");
	        $qbFieldVals->select("IDENTITY(tval.Document, 'id') AS Doc_Id");
	        $qbFieldVals->where("fld.fieldName=?1");
	        $qbFieldVals->andWhere("fld.fieldType=0");
	        $qbFieldVals->andWhere("app1.id=?2 OR app2.id=?2");
	        $qbFieldVals->andWhere("tval.fieldValue=?3");	
	        $qbFieldVals->addOrderBy("Doc_Id", "ASC");
	        $qbFieldVals->setParameter(1, $parentfieldname);
	        $qbFieldVals->setParameter(2, $this->appid);
	        $qbFieldVals->setParameter(3, $fieldvalprefix.trim($fieldinfo["Field_Value"]));
	        $qbFieldVals->distinct();
	        
	        $qbQuery = $qbFieldVals->getQuery();
	        if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}
	        
	        $parentdocdata = $qbQuery->getArrayResult();
	        
	        if(!empty($parentdocdata) && !empty($parentdocdata[0])){
	            $parentdoc = $docrepository->findOneBy(array('id' => $parentdocdata[0]["Doc_Id"]));
	            if(!empty($parentdoc)){
    	           $responsedoc = 	$docrepository->findOneBy(array('id' => $fieldinfo["Doc_Id"]));
	               if(!empty($responsedoc)){	            
	                    $related_doc = $relateddoc_repository->findOneBy(array('Parent_Doc' => $parentdoc->getId(), 'Related_Doc' => $responsedoc->getId()));
	                    if (empty($related_doc))
	                    {
	                         $related_doc = new RelatedDocuments();
	                    }
	                    $related_doc->setParentDoc($parentdoc);
	                    $related_doc->setRelatedDoc($responsedoc);	                    
	                    $this->em->persist($related_doc);
	                    $this->em->flush();
	                   
	                    $updatecount++;
	               }
	            }

  	        }
  	        $this->em->clear();
	    }
	    
	    echo($updatecount." response documents linked to parents performed.".PHP_EOL);
	    echo(PHP_EOL."=============  Linking Response Documents - Finished =============".PHP_EOL);
	    
	    return $updatecount;
	    
	}
	
	protected function splitFieldValues(){		
		$updatecount = 0; 
		
		/*
		$question = new ChoiceQuestion('What type of object/entity do you want to update? ', array('FormTextValues') ,'');
		$entitytype = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);
		*/
		
		$question = new Question('What field name do you want to modify? ', '');
		$fieldname = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);
		
		$question = new ChoiceQuestion('Split values on string delimiter or character code? ', array('String Delimiter', 'Character Code') ,'String Delimiter');
		$delimtype = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);		
		
		switch ($delimtype){
			case "String Delimiter":
				$question = new Question('What character or string should be used as a delimiter to split the values? (if using spaces enclose in quotes): ', '');
				$delimiter = $this->qhelper->ask($this->input, $this->output, $question);
				echo(PHP_EOL);

				if(strlen($delimiter)>2 && strpos($delimiter, " ") !== false ){
					if(substr($delimiter,0, 1)=='"' && substr($delimiter,-1)=='"'){
						$delimiter = substr($delimiter, 1, strlen($delimiter)-2);
					}
				}
				if($delimiter == ""){
					echo("No delimiter specified. Exiting.".PHP_EOL);
					return $updatecount;
				}
				
				break;
			case "Character Code":
				$question = new Question('Enter the ASCII character code number to use as a delimiter: ', '');
				$charcode = $this->qhelper->ask($this->input, $this->output, $question);
				echo(PHP_EOL);
				$delimiter = chr(intval($charcode));
			
				break;
		}
		
		$qbFieldVals = $this->em->getRepository("DocovaBundle:FormTextValues")->createQueryBuilder("tval");
		$qbFieldVals->leftJoin("tval.Field", "fld");		
		$qbFieldVals->leftJoin("fld.form", "frm");
		$qbFieldVals->leftJoin("fld.Subform", "sbfrm");
		$qbFieldVals->leftJoin("frm.application", "app1");
		$qbFieldVals->leftJoin("sbfrm.application", "app2");	
		//$qbFieldVals->select("fld.fieldName, IDENTITY(tval.Document, 'id') AS Doc_Id,IDENTITY(tval.Field, 'id') AS Field_Id, tval.order AS Value_Order, tval.id, tval.fieldValue");
		$qbFieldVals->select("IDENTITY(tval.Document, 'id') AS Doc_Id,IDENTITY(tval.Field, 'id') AS Field_Id");		
		$qbFieldVals->where("fld.fieldName=?1");
		$qbFieldVals->andWhere("fld.fieldType=0");
		$qbFieldVals->andWhere("app1.id=?2 OR app2.id=?2");
		$qbFieldVals->andWhere("tval.fieldValue LIKE ?3");	
		$qbFieldVals->addOrderBy("Doc_Id", "ASC");
		$qbFieldVals->addOrderBy("Field_Id", "ASC");
		//$qbFieldVals->addOrderBy("Value_Order", "ASC");
		$qbFieldVals->setParameter(1, $fieldname);		
		$qbFieldVals->setParameter(2, $this->appid);
		$qbFieldVals->setParameter(3, '%'.$delimiter.'%');	
		$qbFieldVals->distinct();
		
		$qbQuery = $qbFieldVals->getQuery();
		if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}
		
		$fielddata = $qbQuery->getArrayResult();
		
		if(empty($fielddata)){
			echo("No data found to update for the specified field in the specified application. Exiting.".PHP_EOL);
			return $updatecount;
		}
	
		echo(PHP_EOL."=============  Splitting Field Values - Start =============".PHP_EOL);
		
		foreach($fielddata as $fieldinfo){
			if($this->DEBUG){var_dump($fieldinfo);}
			$updatecount ++;
			
			$fvalobjects = $this->em->getRepository("DocovaBundle:FormTextValues")->findBy(array('Document' => $fieldinfo["Doc_Id"], 'Field' => $fieldinfo["Field_Id"]), array('order' => 'ASC'));
			$curorder = 0;
			foreach($fvalobjects as $curobj){							
				if($this->DEBUG){var_dump($fieldinfo);	}
				
				$valparts = explode($delimiter, $curobj->getFieldValue());
				
				for($x=0; $x < count($valparts); $x++){
					$val = $valparts[$x];
					if($this->DEBUG){echo($val.PHP_EOL);}
					$tempobj = ($x == 0 ? $curobj : clone $curobj);
					$tempobj->setFieldValue($val);
					$tempobj->setSummaryValue($val);
					$tempobj->setOrder($curorder);
					$this->em->persist($tempobj);
						
					$this->em->flush();
					$curorder ++;
				}				
			}
			$this->em->clear();
		}

		echo($updatecount." field updates performed.".PHP_EOL);
		echo(PHP_EOL."=============  Splitting Field Values - Finished =============".PHP_EOL);
		
		return $updatecount;
	}
	
	protected function applyRegexToField(){
	    $updatecount = 0;
	    
	    $question = new Question('What is the name of the form that you wish to modify data for? ', '');
	    $formname = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $question = new Question('What field name do you want to modify? ', '');
	    $fieldname = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    
	    $question = new ChoiceQuestion('What type of regular expression operation do you want to use? ', array('Replace', 'Match') ,'Replace');
	    $regextype = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);	
	    
	    $question = new Question('Enter a Regex pattern to use as a search query on the field value:', '');
	    $searchpattern = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);

	    if($regextype == 'Replace'){
	        $question = new Question('Enter a Regex pattern to use as a replacement pattern:', '');
	        $replacepattern = $this->qhelper->ask($this->input, $this->output, $question);
	        echo(PHP_EOL);	        
	    }
	    
	    $qbFields = $this->em->getRepository("DocovaBundle:DesignElements")->createQueryBuilder("fld");
	    $qbFields->leftJoin("fld.form", "frm");
	    $qbFields->leftJoin("fld.Subform", "sbfrm");
	    $qbFields->leftJoin("frm.application", "app1");
	    $qbFields->leftJoin("sbfrm.application", "app2");
	    $qbFields->select("fld.id AS Field_Id");
	    $qbFields->where("fld.fieldName=?1");
	    $qbFields->andWhere("fld.fieldType=0");
	    $qbFields->andWhere("app1.id=?2 OR app2.id=?2");
	    $qbFields->setParameter(1, $fieldname);
	    $qbFields->setParameter(2, $this->appid);
	    $qbFields->distinct();	    
	    $qbQuery = $qbFields->getQuery();
	    $fieldids = $qbQuery->getArrayResult();
	    if(is_array($fieldids)){
	        $fieldids = array_column($fieldids, 'Field_Id');
	    }
	    
	    $qbForms = $this->em->getRepository("DocovaBundle:AppForms")->createQueryBuilder("frm");
	    $qbForms->select("frm.id AS Form_Id");
	    $qbForms->where("frm.formName=?1 OR frm.formAlias=?1");
	    $qbForms->andWhere("frm.application=?2");
	    $qbForms->setParameter(1, $formname);
	    $qbForms->setParameter(2, $this->appid);
	    $qbForms->distinct();	    
	    $qbQuery = $qbForms->getQuery();
	    $formids = $qbQuery->getArrayResult();
	    if(is_array($formids)){
	        $formids = array_column($formids, 'Form_Id');
	    }
	    
		    
	    $qbFieldVals = $this->em->getRepository("DocovaBundle:FormTextValues")->createQueryBuilder("tval");
	    $qbFieldVals->leftJoin("tval.Document", "doc");
	    $qbFieldVals->select("IDENTITY(tval.Document, 'id') AS Doc_Id,IDENTITY(tval.Field, 'id') AS Field_Id");
	    $qbFieldVals->where($qbFieldVals->expr()->in('tval.Field', $fieldids));	
	    $qbFieldVals->andWhere($qbFieldVals->expr()->in('doc.appForm', $formids));	
	    $qbFieldVals->addOrderBy("Doc_Id", "ASC");
	    $qbFieldVals->addOrderBy("Field_Id", "ASC");
	    $qbFieldVals->distinct();	    
	    $qbQuery = $qbFieldVals->getQuery();
	    if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}	    
	    $fielddata = $qbQuery->getArrayResult();
	    
	    
	    if(empty($fielddata)){
	        echo("No data found to update for the specified field in the specified application. Exiting.".PHP_EOL);
	        return $updatecount;
	    }
	    
	    $logfile = fopen("adjustdata.log", "w");
	    fwrite($logfile, ">>>RECORDSTART DOC_ID,FIELD_ID,FIELDVALUE_ID >>OLDVALUE >>NEWVALUE >>>RECORDEND".PHP_EOL);	    
	    
	    echo(PHP_EOL."=============  Apply Rejex to Field Values - Start =============".PHP_EOL);
	    
	    foreach($fielddata as $fieldinfo){
	        if($this->DEBUG){var_dump($fieldinfo);}
	        
	        $fvalobjects = $this->em->getRepository("DocovaBundle:FormTextValues")->findBy(array('Document' => $fieldinfo["Doc_Id"], 'Field' => $fieldinfo["Field_Id"]), array('order' => 'ASC'));
	        $curorder = 0;
	        foreach($fvalobjects as $curobj){
	            if($this->DEBUG){var_dump($fieldinfo);	}
	            
	            $changed = false;
	            $fieldval = $curobj->getFieldValue();
	            
	            if($regextype == "Replace"){
	               $tempval = preg_replace($searchpattern, $replacepattern, $fieldval);    
	               if(!is_null($tempval) && $fieldval !== $tempval){
	                   $changed = true;
	               }	               
	            }else if($regextype == "Match"){
	                $matches = null;
	                if(preg_match($searchpattern, $fieldval, $matches) === 1){
	                    $tempval = $matches[1];
	                    $changed = true;
	                }	                
	            }
                
	            if($changed){
	                $updatecount ++;
	                
	                fwrite($logfile, '>>>RECORDSTART'.PHP_EOL);
	                fwrite($logfile, $fieldinfo["Doc_Id"].','.$fieldinfo["Field_Id"].','.$curobj->getId().PHP_EOL);
	                fwrite($logfile, '>>OLDVALUE'.PHP_EOL);
	                fwrite($logfile, $fieldval.PHP_EOL);	                
	                fwrite($logfile, '>>NEWVALUE'.PHP_EOL);
	                fwrite($logfile, $tempval.PHP_EOL);	                
	                fwrite($logfile, '>>>RECORDEND'.PHP_EOL);
	                	            
                    $curobj->setFieldValue($tempval);
                    $curobj->setSummaryValue($tempval);
	                
	                $this->em->flush();
	            }
	        }
	        $this->em->clear();
	    }
	    fclose($logfile);	
	    echo($updatecount." field updates performed.".PHP_EOL);
	    echo(PHP_EOL."=============  Apply Regex to Field Values - Finished =============".PHP_EOL);
	    
	    return $updatecount;
	}
	

	protected function updateFromXml(){
	    $updatecount = 0;
	    $createcount = 0;
	    $rowcount = 0;
	    
	    $question = new Question('Enter the path and file name of the xml file containing update data. ', '');
	    $xmlfile = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $question = new ChoiceQuestion('What type of update do you wish to perform? ', array('Update Only', 'Create Only', 'Create and Update') ,'Update Only');
	    $updatetype = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $question = new Question('What field(s) do you want to key on (separate multiple fields with a comma)? ', '');
	    $keyfieldnames = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    $keyfieldnames = explode(",",strtolower($keyfieldnames));

	    $excludefromupdate = "";
	    if($updatetype != "Create Only"){
    	    $question = new Question('What field(s) should be excluded from update (eg. id) (separate multiple fields with a comma)? ', 'id');
	        $excludefromupdate = $this->qhelper->ask($this->input, $this->output, $question);
	        echo(PHP_EOL);
	    }
	    $excludefromupdate = explode(",",strtolower($excludefromupdate));
	    
	    
	    if (file_exists($xmlfile)) {
	        $xmlobj = simplexml_load_file($xmlfile);
	        
	        $logfile = fopen("adjustdata.log", "w");
	        
	        echo(PHP_EOL."=============  Updating/Creating Records - Start =============".PHP_EOL);
	        
	        
	        foreach($xmlobj->children() as $rowobj){
	            $rowcount ++;
	            fwrite($logfile, "Record # ".$rowcount.PHP_EOL);
	            
	            $tablename = $rowobj->getName();	
	            
	            //-- update existing records
	            $updated = false;
	            if($updatetype == "Update Only" || $updatetype == "Create and Update"){
	                $wherenames = [];
	                $wherevals = [];
	                $fieldvals = [];
	                $query = "UPDATE ".$tablename." SET "; 
	                foreach($rowobj->children() as $fieldobj){	                    
	                    $fieldname = $fieldobj->getName();
	                    
	                    if(in_array(strtolower($fieldname), $keyfieldnames)){
	                        array_push($wherenames, $fieldname);
	                        array_push($wherevals, (string)$fieldobj);
	                    }else if(!in_array(strtolower($fieldname), $excludefromupdate)){
    	                    if(count($fieldvals) > 0){
    	                        $query .= ",";
    	                    }
	                    
	                       $query .= $fieldname." = ?";
	                       array_push($fieldvals, (string)$fieldobj);
	                    }
	                }
	                $query .= " WHERE ";
	                for($i=0; $i<count($wherenames); $i++){
	                    if($i > 0){
	                        $query .= ",";
	                    }
	                    
	                    $query .= $wherenames[$i]." = ?";
	                }
	                $query .= ";";

	                $stmt = $this->conn->prepare($query);
	                
	                $index = 0;
	                for($i=0; $i<count($fieldvals); $i++){
	                    $index ++;
	                    $stmt->bindValue($index, $fieldvals[$i]);
	                }
	                for($i=0; $i<count($wherevals); $i++){
	                    $index ++;
	                    $stmt->bindValue($index, $wherevals[$i]);
	                }
                    	                	                
                    $result = $stmt->execute();
	                if($result !== true){	                    
	                    $comment = "Error updating record.";
	                    $comment .= PHP_EOL.(string)$stmt->errorInfo();
	                }else if ($stmt->rowCount() == 0){
	                    $comment = "No matching record found.";                  
	                }else{
	                    $comment = "Record updated.";
	                    $updatecount ++;
	                    $updated = true;
	                }
	                fwrite($logfile, $comment.PHP_EOL);
	                fwrite($logfile, "Query:".PHP_EOL);
	                fwrite($logfile, $query.PHP_EOL);
	                fwrite($logfile, "Update Params:".PHP_EOL);
	                fwrite($logfile, var_export($fieldvals, true).PHP_EOL);
	                fwrite($logfile, "Where Params:".PHP_EOL);
	                fwrite($logfile, var_export($wherevals, true).PHP_EOL);               
	            }
	            
	            //-- check to see if record already exists
	            $exists = true;
	            if($updatetype == "Create Only" || $updatetype == "Create and Update"){
	                $wherenames = [];
	                $wherevals = [];
	                $query = "SELECT * FROM ".$tablename;
	                foreach($rowobj->children() as $fieldobj){
	                    $fieldname = $fieldobj->getName();
	                    
	                    if(in_array(strtolower($fieldname), $keyfieldnames)){
	                        array_push($wherenames, $fieldname);
	                        array_push($wherevals, (string)$fieldobj);
	                    }
	                }
	                $query .= " WHERE ";
	                for($i=0; $i<count($wherenames); $i++){
	                    if($i > 0){
	                        $query .= ",";
	                    }
	                    
	                    $query .= $wherenames[$i]." = ?";
	                }
	                $query .= ";";
	                
	                $stmt = $this->conn->prepare($query);
	                
	                $index = 0;
	                for($i=0; $i<count($wherevals); $i++){
	                    $index ++;
	                    $stmt->bindValue($index, $wherevals[$i]);
	                }
	                
	                $result = $stmt->execute();
	                if($result !== true){
	                    $comment = "Error searching for existing record.";
	                    $comment .= PHP_EOL.(string)$stmt->errorInfo();
	                }else if ($stmt->rowCount() == 0){
	                    $comment = "No matching record found.";
	                    $exists = false;
	                }else{
	                    $comment = "Record found.";
	                }
	                fwrite($logfile, "Checking for existing record:".PHP_EOL);
	                fwrite($logfile, $comment.PHP_EOL);
	            }
	        
	            //-- create new records
	            if($updated == false && $exists == false && ($updatetype == "Create Only" || $updatetype == "Create and Update")){
	                $fieldvals = [];
	                $query = "INSERT INTO ".$tablename." (";
	                foreach($rowobj->children() as $fieldobj){
	                    $fieldname = $fieldobj->getName();	                    
	                    if(count($fieldvals) > 0){
	                        $query .= ",";
	                    }
	                        
	                    $query .= $fieldname;
	                    array_push($fieldvals, (string)$fieldobj);                    
	                }
	                $query .= ") VALUES (";
	                for($i=0; $i<count($fieldvals); $i++){
	                    if($i > 0){
	                        $query .= ",";
	                    }
	                    
	                    $query .= "?";
	                }
	                $query .= ");";
	                
	                $stmt = $this->conn->prepare($query);
	                
	                $index = 0;
	                for($i=0; $i<count($fieldvals); $i++){
	                    $index ++;
	                    $stmt->bindValue($index, $fieldvals[$i]);
	                }
	                
	                $result = $stmt->execute();
	                if($result !== true){
	                    $comment = "Error inserting record.";
	                    $comment .= PHP_EOL.(string)$stmt->errorInfo();
	                }else if ($stmt->rowCount() == 0){
	                    $comment = "Record was not inserted.";
	                }else{
	                    $comment = "Record inserted.";
	                    $createcount ++;
	                }
	                fwrite($logfile, $comment.PHP_EOL);
	                fwrite($logfile, "Query:".PHP_EOL);
	                fwrite($logfile, $query.PHP_EOL);
	                fwrite($logfile, "Insert Params:".PHP_EOL);
	                fwrite($logfile, var_export($fieldvals, true).PHP_EOL);
	            }	
	            
	            fwrite($logfile,PHP_EOL);
	        }
	     
	        
	        if(!empty($updatecount) || !empty($createcount)){
	            fwrite($logfile, PHP_EOL."**** Updated ".$updatecount." records. Created ".$createcount." records.");
	            echo (PHP_EOL."  Updated ".$updatecount." records. Created ".$createcount." records.  See adjustdata.log for details.".PHP_EOL);
	        }else{
	            fwrite($logfile, PHP_EOL."**** No records were created/updated.");	            
	            echo (PHP_EOL."  No records were created/updated.".PHP_EOL);
	        }
	        echo(PHP_EOL."=============  Updating/Creating Records - Finished =============".PHP_EOL);
	        
	        fclose($logfile);
	        
	    } else {
	        echo("Failed to open xml file [".$xmlfile."]\.".PHP_EOL);
	    }
	    
	    return ($updatecount + $createcount);
	}
	
	/**
	 * @param: $userAbName string - abbreviated name eg. Jim Smith/Acme
	 * @return string - distinguished name eg. CN=Jim Smith,O=Acme
	 */
	protected function getUserDnName($userAbName){
	    $tempname = $userAbName;
	    
	    if (false === stripos($tempname, 'CN=')) {
	            $tmp = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $tempname);
	            if(count($tmp)<2){
	                $tmp = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $tempname);	                
	            }
	            $outputstr = "";
	            for($i=0; $i<count($tmp); $i++){
	                if($i==0){
	                    $outputstr = "CN=".trim($tmp[$i]);
	                }elseif($i==(count($tmp)-1)){
	                    $outputstr .= ",O=".trim($tmp[$i]);
	                }else{
	                    $outputstr .= ",OU=".trim($tmp[$i]);
	                }	                
	            }
	            $tempname = $outputstr;
	    }
	    return $tempname;
	}
	
	/**
	 * @param: dn name e.g CN=DV Punia,O=DLI
	 * @return: abbreviated name e.g DV Punia/DLI
	 */
	protected function getUserAbbreviatedName($userDnName)
	{
		try {
			$strAbbDnName="";		
			$arrAbbName = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $userDnName);
			if(count($arrAbbName) < 2){
			    $arrAbbName = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $userDnName);
			}
			
			//create abbreviated name
			foreach ($arrAbbName as $value){
				if(trim($value) != ""){
					$namepart = explode("=", $value);
					$strAbbDnName .= (count($namepart) > 1 ? trim($namepart[1]) : trim($namepart[0]))."/";
				}
			}
			//remove last "/"
			$strAbbDnName=rtrim($strAbbDnName,"/");
	
		} catch (\Exception $e) {
		}
	
	
		return $strAbbDnName;
	}	
	
	/**
	 * @param: string $userame 
	 * @return: array
	 */
	protected function getLDAPData($username)
	{
		$user_data = null;
		
		if ($this->global_settings->getLDAPAuthentication())
		{
			$ldap_obj = new adLDAP(array(
					'domain_controllers' => $this->global_settings->getLDAPDirectory(),
					'domain_port' => $this->global_settings->getLDAPPort(),
					'base_dn' => $this->global_settings->getLdapBaseDn(),
					'ad_username'=>$this->getContainer()->getParameter('ldap_username'),
					'ad_password'=>$this->getContainer()->getParameter('ldap_password')
			));
							
			$arrUserName = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $username);
			$searchTxt=$arrUserName[0];
			$searchTxt = str_replace('\\', '', $searchTxt);		
			
			$filter = "( &(objectclass=person)(|(samaccountname=".$searchTxt.")(uid=".$searchTxt.")(userPrincipalName=".$searchTxt.")(cn=".$searchTxt.") ) )";
				
			$info = $ldap_obj->search_user($filter);
			if (!empty($info['count']))
			{
				$ldap_dn_name = $info[0]['dn'];
				$user_data = array(
						'mail' => (!empty($info[0]['mail']['0']) ? $info[0]['mail']['0'] : ''),
						'username_dn_abbreviated' => $this->getUserAbbreviatedName($ldap_dn_name),
						'username_dn' => $ldap_dn_name,
						'display_name' => (!empty($info[0]['displayname'][0]) ? $info[0]['displayname'][0] : $info[0]['cn'][0]),
						'uid' => (!empty($info[0]['uid'][0]) ? $info[0]['uid'][0] : $info[0]['samaccountname'][0])
				);
			}
			$ldap_obj = null;
		}
		
		return $user_data;
	}
	
	/**
	 * @param: $fromuser object
	 * @param: $touser object
	 * @return: boolean
	 */
	protected function mergeUserAccounts($fromuser, $touser){
	   $result = false;
	   	       
	   if(!empty($fromuser) && !empty($touser)){
	       $fromid = $fromuser->getId();
	       $toid = $touser->getId();
	       
	       if($fromid !== $toid){	           
	           //--need to update any links to this user
	           
	           $objectarray = [
    	           'AppAcl' => ['userObject'],
    	           'AppAgents' => ['createdBy','modifiedBy'],
    	           'AppCss' => ['createdBy','modifiedBy'],
    	           'AppDocComments' => ['createdBy'],
    	           'AppFiles' => ['createdBy','modifiedBy'],
    	           'AppFormProperties' => ['modifiedBy'],
    	           'AppForms' => ['createdBy','modifiedBy'],
    	           'AppJavaScripts' => ['createdBy','modifiedBy'],
    	           'AppLayout' => ['createdBy','modifiedBy'],
    	           'AppOutlines' => ['createdBy','modifiedBy'],
    	           'AppPages' => ['createdBy','modifiedBy'],
    	           'AppPhpScripts' => ['createdBy','modifiedBy'],
    	           'AppViews' => ['createdBy','modifiedBy'],
    	           'AttachmentsDetails' => ['Checked_Out_By','Author'],
    	           'Bookmarks' => ['Created_By'],
    	           'DesignElements' => ['modifiedBy'],
    	           'DiscussionTopic' => ['createdBy'],
    	           'DocumentActivities' => ['createdBy','assignee'],
    	           'DocumentComments' => ['Created_By'],
    	           'Documents' => ['Owner', 'Author', 'Creator', 'Modifier', 'Lock_Editor', 'Released_By', 'Deleted_By'],
    	           'DocumentsLog' => ['Log_Author'],
    	           'DocumentsWatchlist' => ['Owner'],
    	           'DocumentTypes' => ['Creator','Modifier'],
    	           'Folders' => ['Creator', 'Updator', 'Deleted_By'],
    	           'FoldersLog' => ['Log_Author'],
    	           'FoldersWatchlist' => ['Owner'],
    	           'FormNameValues' => ['fieldValue'],
    	           'LibraryGroups' => ['createdBy'],
    	           'PublicAccessResources' => ['Author'],
    	           'RelatedLinks' => ['createdBy'],
    	           'SavedSearches' => ['userSaved'],
    	           'Subforms' => ['Creator','Modified_By'],
    	           'SystemPerspectives' => ['Creator','Modifier'],
    	           'TempEditAttachment' => ['trackUser'],
    	           'UserAppGroups' => ['createdBy'],
    	           'UserDelegates' => ['owner'],
    	           'UserLibrariesGroups' => ['user'],
    	           'UserPanels' => ['creator','assignedUser'],
    	           'UserProfile' => ['Manager'],
    	           'UserRecentApps' => ['user'],
    	           'UserWorkspace' => ['user'],
    	           'WorkflowAssignee' => ['assignee'],
    	           'WorkflowCompletedBy' => ['completedBy']
	           ];
	           
	           //--loop through the entities
	           foreach($objectarray as $key => $value){
	               $rep = $this->em->getRepository('DocovaBundle:'.$key);
	               
	               //--loop through the entity properties tied to user accounts
	               foreach($value as $propname){
	                   $res = $qb = $rep->createQueryBuilder('O')
	                   ->update()
	                   ->set('O.'.$propname, ':newidval')
	                   ->where('O.'.$propname.' = :oldidval')
	                   ->setParameter('newidval' , $toid)
	                   ->setParameter('oldidval' , $fromid)
	                   ->getQuery()
	                   ->execute();
	                   
	               }
	           }	
	           
	           
	           
	          //--remove the merged user account
	          $userprofile = $fromuser->getUserProfile();
	          if(!empty($userprofile)){
	              $this->em->remove($userprofile);
	              $this->em->flush();
	          }
	          $this->em->remove($fromuser);
	          $this->em->flush();
	           
	           $result = true;
	           
	           //--update security identities
	           $fromshortname = $fromuser->getUsername();
	           $toshortname = $touser->getUsername();
	           
	           $query = "SELECT id FROM acl_security_identities WHERE identifier=? AND username=1";
	           $stmt = $this->conn->prepare($query);
	           $stmt->bindValue(1, "Docova\DocovaBundle\Entity\UserAccounts-".$fromshortname);
	           $tempresult = $stmt->execute();
	           if($tempresult == true){
	               $sifromobjects = $stmt->fetchAll();
	               if(count($sifromobjects) == 1){
	                   $sifromid = $sifromobjects[0]["id"];
	                   
	                   $query = "SELECT id FROM acl_security_identities WHERE identifier=? AND username=1";
	                   $stmt = $this->conn->prepare($query);
	                   $stmt->bindValue(1, "Docova\DocovaBundle\Entity\UserAccounts-".$toshortname);
	                   $tempresult = $stmt->execute();
	                   if($tempresult == true){
	                       $sitoobjects = $stmt->fetchAll();
	                       if(count($sitoobjects) == 1){
	                           $sitoid = $sitoobjects[0]["id"];
	                           
	                           //--need to update security identity
	                           $query = "UPDATE acl_entries SET security_identity_id = ? WHERE security_identity_id=?;";
	                           $stmt = $this->conn->prepare($query);
	                           $stmt->bindValue(1, $sitoid);
	                           $stmt->bindValue(2, $sifromid);
	                           $tempresult = $stmt->execute();
	                           
	                           $query = "DELETE FROM acl_security_identities WHERE id=?";
	                           $stmt = $this->conn->prepare($query);
	                           $stmt->bindValue(1, $sifromid);
	                           $tempresult = $stmt->execute();
	                       }
	                   }	                   	                   
	               }
	           }
	       }
	   }
	   	   
	   return $result;
	}
	
}