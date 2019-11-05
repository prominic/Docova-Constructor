<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use PhpOffice\PhpWord\PhpWord;
use Mpdf\Mpdf;
use Docova\DocovaBundle\ObjectModel\Docova;
require_once(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'LSHelperFunctions.php');

$docova = null;

/**
 * @author Chris Fales
 * Export Data Command
 *
 */
class ExportDataCommand extends ContainerAwareCommand
{
	private $global_settings;
	private $appid="";
	private $appdata = null;
	private $em=null;
	private $conn=null;
	private $qhelper=null;
	private $_attpath="";
	private $input=null;
	private $output=null;
	private $DEBUG=false;

	protected function configure()
	{
		$this
		->setName('docova:exportdatacommand')
		->setDescription('Data export utility command');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
	
		$this->input = $input;
		$this->output = $output;

		$this->qhelper = $this->getHelper('question');
		
		$this->em = $this->getContainer()->get('doctrine')->getManager();
		
		$this->conn = $this->em->getConnection();
		
		$path = $this->getContainer()->getParameter('document_root') ? realpath($this->getContainer()->getParameter('document_root')) : getcwd().DIRECTORY_SEPARATOR.'..';
		$this->_attpath = $path.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
		
		try{
			$this->global_settings = $this->em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		}
		catch (\PDOException $pe){
			$output->writeln("ExportDataCommand->execute() - Error PDO: ".$pe->getMessage());
			throw new \Exception("ExportDataCommand->excute() - Error PDO: ".$pe->getMessage().PHP_EOL);
		}		
		$this->global_settings = $this->global_settings[0];
		
		
		$question = new ChoiceQuestion('What type of data export do you want to perform? ', 
		    array('Export File Attachments to File System',
		        'Export Rich Text to Word',
		        'Export Rich Text to PDF',
		        'Export Field Data to PDF',
		        'Export Field Data to Text',
		        'Export Document Data to CSV',
		        'Quit') 
		,'');
		$action = $this->qhelper->ask($this->input, $this->output, $question);
		
		switch ($action){
		    case "Export File Attachments to File System":
		        if($this->getApp()){
		            $this->exportFileAttachmentsToFileSystem();
		        }
		        break;
			case "Export Rich Text to Word":	
				if($this->getApp()){
					$this->exportRichTextToWord();
				}
				break;
			case "Export Rich Text to PDF":
			    if($this->getApp()){
			        $this->exportRichTextToPDF();
			    }
			    break;
			case "Export Field Data to PDF":
			    if($this->getApp()){
			        $this->exportFieldDataToPDF();
			    }
			    break;
			case "Export Field Data to Text":
			    if($this->getApp()){
			        $this->exportFieldDataToText();
			    }
			    break;
			case "Export Document Data to CSV":
			    if($this->getApp()){
			        $this->exportDocumentDataToCSV();
			    }
			    break;
			case "Quit":
				echo("Quit selected. Exiting.".PHP_EOL);
				return;
				break;
		}

	}
	
	protected function getApp(){
		$question = new Question('Enter the application/library id that you wish to export: ', '');
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
		$this->appdata = $qbLib->getQuery()->getArrayResult();
		if(empty($this->appdata)){
			echo("No application found. Exiting.".PHP_EOL);
			return false;
		}		
		
		return true;
	}
	
	
	
	protected function exportRichTextToWord(){		
		$exportcount = 0; 
		
		$question = new Question('What form do you want to export data from? ', '');
		$formname = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);
		
		$question = new Question('What rich text field do you want to export data from? ', '');
		$fieldname = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);
		
		$outputdir = sys_get_temp_dir();
		$question = new Question('What directory do you want to export the data to? ', $outputdir);
		$outputdir = $this->qhelper->ask($this->input, $this->output, $question);
		echo(PHP_EOL);		
		
		if(!file_exists($outputdir)){
			echo("No export directory [".$outputdir."] found. Exiting.".PHP_EOL);
			return $exportcount;	
		}
		
		if(substr($outputdir, -1) !== DIRECTORY_SEPARATOR){
			$outputdir .= DIRECTORY_SEPARATOR;
		}		
		$outputdir .= strtolower($this->appid);		
		if(!file_exists($outputdir)){
			mkdir($outputdir);
		}	
		if(!file_exists($outputdir)){
			echo("Unable to create export sub directory [".$outputdir."]. Exiting.".PHP_EOL);
			return $exportcount;	
		}
		$outputdir .= DIRECTORY_SEPARATOR;		
		
		if($this->DEBUG){echo("Output Directory=".$outputdir.PHP_EOL);}
		
		$qbFieldVals = $this->em->getRepository("DocovaBundle:FormTextValues")->createQueryBuilder("tval");
		$qbFieldVals->leftJoin("tval.Field", "fld");		
		$qbFieldVals->leftJoin("tval.Document", "d");
		$qbFieldVals->leftJoin("fld.form", "frm");
		$qbFieldVals->leftJoin("fld.Subform", "sbfrm");
		$qbFieldVals->leftJoin("frm.application", "app1");
		$qbFieldVals->leftJoin("sbfrm.application", "app2");	
		$qbFieldVals->select("IDENTITY(tval.Document, 'id') AS Doc_Id,IDENTITY(tval.Field, 'id') AS Field_Id");		
		$qbFieldVals->where("fld.fieldName=?1");
		$qbFieldVals->andWhere("fld.fieldType=0");
		$qbFieldVals->andWhere("app1.id=?2 OR app2.id=?2");
		$qbFieldVals->andWhere("d.Trash=false");
//		$qbFieldVals->andWhere("tval.Document='001b34a6-101b-11e8-8326-544249630d96'"); //TODO remove this
		$qbFieldVals->addOrderBy("Doc_Id", "ASC");
		$qbFieldVals->addOrderBy("Field_Id", "ASC");
		$qbFieldVals->setParameter(1, $fieldname);		
		$qbFieldVals->setParameter(2, $this->appid);
		$qbFieldVals->distinct();
		
		$qbQuery = $qbFieldVals->getQuery();
		if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}
		
		$fielddata = $qbQuery->getArrayResult();
		
		if(empty($fielddata)){
			echo("No data found to export for the specified field in the specified application. Exiting.".PHP_EOL);
			return $exportcount;
		}
	
		echo(PHP_EOL."=============  Exporting Rich Text to Word - Start =============".PHP_EOL);
		
		$embimgpath = $this->_attpath.DIRECTORY_SEPARATOR."Embedded";
		
		foreach($fielddata as $fieldinfo){
			if($this->DEBUG){var_dump($fieldinfo);}
			
			$fvalobjects = $this->em->getRepository("DocovaBundle:FormTextValues")->findBy(array('Document' => $fieldinfo["Doc_Id"], 'Field' => $fieldinfo["Field_Id"]));

			foreach($fvalobjects as $curobj){										
				$rthtmldata = $curobj->getFieldValue();
				
				if(trim($rthtmldata) == "" || trim($rthtmldata) == "<html><body></body></html>" || preg_match('/\(See attached file: .*\)/', $rthtmldata) == 1){
					continue;
				}
				
				// Adjust html data embedded image source paths
				$temphtml = $this->replaceEmbeddedImagesPath($rthtmldata, $embimgpath);
				if($temphtml !== false){
					$rthtmldata = $temphtml;
				}
				
				$outputsubdir = $outputdir . strtolower($fieldinfo["Doc_Id"]);
				if(!file_exists($outputsubdir)){
					mkdir($outputsubdir);
				}	
				if(!file_exists($outputsubdir)){
					echo("Unable to create export sub directory [".$outputsubdir."]. Exiting.".PHP_EOL);
					return $exportcount;	
				}
				$outputsubdir .= DIRECTORY_SEPARATOR;
				$outputfile = $outputsubdir . $fieldname . '.docx';
				
				if($this->DEBUG){echo($outputfile . PHP_EOL);}
				
				
				// New Word Document:
				$phpword_object = new PHPWord();
				$section = $phpword_object->addSection();
				
				$fullhtml = false;
				if(preg_match('/^\s*<!DOCTYPE html\s/mi',$rthtmldata) || preg_match('/^\s*<html>/mi',$rthtmldata)){
				    $fullhtml = true;
				}
				
				try{
				    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $rthtmldata, $fullhtml);
				    
				    // Save File
				    $phpword_object->save($outputfile, 'Word2007');
				    
				    $exportcount ++;
				    
				}catch(\Exception $e){
				    $trace = $e->getTrace();
				    
				    $result = 'Exception: "';
				    $result .= $e->getMessage();
				    $result .= '" @ ';
				    if($trace[0]['class'] != '') {
				        $result .= $trace[0]['class'];
				        $result .= '->';
				    }
				    $result .= $trace[0]['function'];
				    $result .= '();';
				    $result .= PHP_EOL . " on line #".$e->getLine().PHP_EOL;
				    $result .= $fieldinfo["Doc_Id"].PHP_EOL;
				    echo($result);
				}											
			}
		}

		echo($exportcount." rich text fields exported to word in [".$outputdir."].".PHP_EOL);
		echo("=============  Exporting Rich Text to Word - Finished =============".PHP_EOL);
		
		return $exportcount;
	}
	
	
	protected function exportRichTextToPDF(){
	    $exportcount = 0;
	    
	    $question = new Question('What form do you want to export data from? ', '');
	    $formname = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $question = new Question('What rich text field do you want to export data from? ', '');
	    $fieldname = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $outputdir = sys_get_temp_dir();
	    $question = new Question('What directory do you want to export the data to? ', $outputdir);
	    $outputdir = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    if(!file_exists($outputdir)){
	        echo("No export directory [".$outputdir."] found. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    
	    if(substr($outputdir, -1) !== DIRECTORY_SEPARATOR){
	        $outputdir .= DIRECTORY_SEPARATOR;
	    }
	    $outputdir .= strtolower($this->appid);
	    if(!file_exists($outputdir)){
	        mkdir($outputdir);
	    }
	    if(!file_exists($outputdir)){
	        echo("Unable to create export sub directory [".$outputdir."]. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    $outputdir .= DIRECTORY_SEPARATOR;
	    
	    if($this->DEBUG){echo("Output Directory=".$outputdir.PHP_EOL);}
	    
	    $qbFieldVals = $this->em->getRepository("DocovaBundle:FormTextValues")->createQueryBuilder("tval");
	    $qbFieldVals->leftJoin("tval.Field", "fld");
	    $qbFieldVals->leftJoin("tval.Document", "d");
	    $qbFieldVals->leftJoin("fld.form", "frm");
	    $qbFieldVals->leftJoin("fld.Subform", "sbfrm");
	    $qbFieldVals->leftJoin("frm.application", "app1");
	    $qbFieldVals->leftJoin("sbfrm.application", "app2");
	    $qbFieldVals->select("IDENTITY(tval.Document, 'id') AS Doc_Id,IDENTITY(tval.Field, 'id') AS Field_Id");
	    $qbFieldVals->where("fld.fieldName=?1");
	    $qbFieldVals->andWhere("fld.fieldType=0");
	    $qbFieldVals->andWhere("app1.id=?2 OR app2.id=?2");
	    $qbFieldVals->andWhere("d.Trash=false");
	    //$qbFieldVals->andWhere("tval.Document='1D88BD71-AA90-417C-84D2-0079192C2D4B'"); //TODO remove this
	    $qbFieldVals->addOrderBy("Doc_Id", "ASC");
	    $qbFieldVals->addOrderBy("Field_Id", "ASC");
	    $qbFieldVals->setParameter(1, $fieldname);
	    $qbFieldVals->setParameter(2, $this->appid);
	    $qbFieldVals->distinct();
	    
	    $qbQuery = $qbFieldVals->getQuery();
	    if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}
	    
	    $fielddata = $qbQuery->getArrayResult();
	    
	    if(empty($fielddata)){
	        echo("No data found to export for the specified field in the specified application. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    
	    echo(PHP_EOL."=============  Exporting Rich Text to PDF - Start =============".PHP_EOL);
	    
		$embimgpath = $this->_attpath.DIRECTORY_SEPARATOR."Embedded";
		
	    foreach($fielddata as $fieldinfo){
	        if($this->DEBUG){var_dump($fieldinfo);}
	        
	        $fvalobjects = $this->em->getRepository("DocovaBundle:FormTextValues")->findBy(array('Document' => $fieldinfo["Doc_Id"], 'Field' => $fieldinfo["Field_Id"]));
	        
	        foreach($fvalobjects as $curobj){
	            $rthtmldata = $curobj->getFieldValue();
	            
	           	if(trim($rthtmldata) == "" ||  trim($rthtmldata) == "<html><body></body></html>"  || preg_match('/\(See attached file: .*\)/', $rthtmldata) == 1){
					continue;
				}
	            
				// Adjust html data embedded image source paths
				$temphtml = $this->replaceEmbeddedImagesPath($rthtmldata, $embimgpath);
				if($temphtml !== false){
					$rthtmldata = $temphtml;
				}				
				
				
	            $outputsubdir = $outputdir . strtolower($fieldinfo["Doc_Id"]);
	            if(!file_exists($outputsubdir)){
	                mkdir($outputsubdir);
	            }
	            if(!file_exists($outputsubdir)){
	                echo("Unable to create export sub directory [".$outputsubdir."]. Exiting.".PHP_EOL);
	                return $exportcount;
	            }
	            $outputsubdir .= DIRECTORY_SEPARATOR;
	            $outputfile = $outputsubdir . $fieldname . '.pdf';
	            
	            if($this->DEBUG){echo($outputfile . PHP_EOL);}
	            
	            // New PDF Document:
	            $mpdf = new Mpdf([
					'tempDir' => sys_get_temp_dir(),
					'setAutoTopMargin' => 'stretch',
					'setAutoBottomMargin' => 'stretch'
				]);
           
	            $fullhtml = false;
	            if(preg_match('/^\s*<!DOCTYPE html\s/mi',$rthtmldata) || preg_match('/^\s*<html>/mi',$rthtmldata)){
	                $fullhtml = true;
	            }
	            
	            try{
	                // Write some HTML code:
	                $mpdf->WriteHTML($rthtmldata);
	                
	                // Save File
	                $mpdf->Output($outputfile, \Mpdf\Output\Destination::FILE);
	                
	                $exportcount ++;
	                
	            }catch(\Exception $e){
	                $trace = $e->getTrace();
	                
	                $result = 'Exception: "';
	                $result .= $e->getMessage();
	                $result .= '" @ ';
	                if($trace[0]['class'] != '') {
	                    $result .= $trace[0]['class'];
	                    $result .= '->';
	                }
	                $result .= $trace[0]['function'];
	                $result .= '();';
	                $result .= PHP_EOL . " on line #".$e->getLine().PHP_EOL;
	                $result .= $fieldinfo["Doc_Id"].PHP_EOL;
	                echo($result);
	            }
	        }
	    }
	    
	    echo($exportcount." rich text fields exported to PDF in [".$outputdir."].".PHP_EOL);
	    echo("=============  Exporting Rich Text to PDF - Finished =============".PHP_EOL);
	    
	    return $exportcount;
	}
	

	protected function exportFieldDataToPDF(){
	    $exportcount = 0;
	    
	    $question = new Question('What form do you want to export data from? ', '');
	    $formname = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $question = new Question('Enter an HTML fragment to use in outputing data (surround field names in ${ })? ', '');
	    $htmltemplate = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	   
	    $question = new Question('What do you want to name the output pdf file? ', $formname);
	    $filenameprefix = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $outputdir = sys_get_temp_dir();
	    $question = new Question('What directory do you want to export the data to? ', $outputdir);
	    $outputdir = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    if(!file_exists($outputdir)){
	        echo("No export directory [".$outputdir."] found. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    
	    if(substr($outputdir, -1) !== DIRECTORY_SEPARATOR){
	        $outputdir .= DIRECTORY_SEPARATOR;
	    }
	    $outputdir .= strtolower($this->appid);
	    if(!file_exists($outputdir)){
	        mkdir($outputdir);
	    }
	    if(!file_exists($outputdir)){
	        echo("Unable to create export sub directory [".$outputdir."]. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    $outputdir .= DIRECTORY_SEPARATOR;
	    
	    if($this->DEBUG){echo("Output Directory=".$outputdir.PHP_EOL);}
	    
	    GLOBAL $docova;
	    $docova = new Docova($this->getContainer());
	    $docova_app = $docova->DocovaApplication(null, $this->appid, $docova);
	    
	    $doc_coll = $docova_app->getAllDocuments();
	    	    
	    echo(PHP_EOL."=============  Exporting Field Data to PDF - Start =============".PHP_EOL);
	    
	    $placeholders = [];
	    preg_match_all('/\$\{(.*?)}/i', $htmltemplate, $placeholders);
	    
	    if(is_array($placeholders) && count($placeholders) > 1){
	        $placeholders = $placeholders[1];
	    }
	    
	    $doc = $doc_coll->getFirstDocument();
	    
	    while(!empty($doc)){
	        
	        $temphtml = $htmltemplate;
	        
	        if($doc->getField("Form") == $formname){
	            $outputsubdir = $outputdir . strtolower($doc->id);
	            if(!file_exists($outputsubdir)){
	                mkdir($outputsubdir);
	            }
	            if(!file_exists($outputsubdir)){
	                echo("Unable to create export sub directory [".$outputsubdir."]. Exiting.".PHP_EOL);
	                return $exportcount;
	            }
	            $outputsubdir .= DIRECTORY_SEPARATOR;
	            $outputfile = $outputsubdir . $filenameprefix . '.pdf';
	            
	            if($this->DEBUG){echo($outputfile . PHP_EOL);}
	                     
	            // New PDF Document:
	            $mpdf = new Mpdf([
					'tempDir' => sys_get_temp_dir(),
					'setAutoTopMargin' => 'stretch',
					'setAutoBottomMargin' => 'stretch'
				]);
	                        
	            foreach($placeholders as $key => $placeholder){
	                $tempvals = null;
                    $tempvals = $doc->getField($placeholder);
	                if(isset($tempvals) && !is_array($tempvals)){
	                    $tempvals = [$tempvals];
	                }
	                if(isset($tempvals) && is_array($tempvals)){
	                    for($i=0; $i<count($tempvals); $i++){
	                        $tempvals[$i] = _Format($tempvals[$i]);
	                    }
	                }
	                
	                $tempval = (isset($tempvals) ? (is_array($tempvals) ? (count($tempvals) > 1 ? implode(", ", $tempvals) : $tempvals[0]) : $tempvals) : '');
	                $pattern = '${'.$placeholder.'}';
	                $temphtml = str_replace($pattern, $tempval, $temphtml);
	            }
	            
	            try{
	                // Write some HTML code:
	                $mpdf->WriteHTML($temphtml);
	                
	                // Save File
	                $mpdf->Output($outputfile, \Mpdf\Output\Destination::FILE);
	                
	                $exportcount ++;
	                
	            }catch(\Exception $e){
	                $trace = $e->getTrace();
	                
	                $result = 'Exception: "';
	                $result .= $e->getMessage();
	                $result .= '" @ ';
	                if($trace[0]['class'] != '') {
	                    $result .= $trace[0]['class'];
	                    $result .= '->';
	                }
	                $result .= $trace[0]['function'];
	                $result .= '();';
	                $result .= PHP_EOL . " on line #".$e->getLine().PHP_EOL;
	                $result .= $doc->id.PHP_EOL;
	                echo($result);
	            }
	        }
	        
	        $doc = $doc_coll->getNextDocument($doc);
	    }
	    
	    echo($exportcount." documents had field data exported to PDF in [".$outputdir."].".PHP_EOL);
	    echo("=============  Exporting Field Data to PDF - Finished =============".PHP_EOL);
	    
	    return $exportcount;
	}
	

	protected function exportFieldDataToText(){
	    $exportcount = 0;
	    
	    $question = new Question('What form do you want to export data from? ', '');
	    $formname = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $question = new Question('Enter a template to use in outputing data (surround field names in ${ })? ', '');
	    $texttemplate = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $question = new Question('What do you want to name the output text file? ', $formname);
	    $filenameprefix = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $outputdir = sys_get_temp_dir();
	    $question = new Question('What directory do you want to export the data to? ', $outputdir);
	    $outputdir = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    if(!file_exists($outputdir)){
	        echo("No export directory [".$outputdir."] found. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    
	    if(substr($outputdir, -1) !== DIRECTORY_SEPARATOR){
	        $outputdir .= DIRECTORY_SEPARATOR;
	    }
	    $outputdir .= strtolower($this->appid);
	    if(!file_exists($outputdir)){
	        mkdir($outputdir);
	    }
	    if(!file_exists($outputdir)){
	        echo("Unable to create export sub directory [".$outputdir."]. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    $outputdir .= DIRECTORY_SEPARATOR;
	    
	    if($this->DEBUG){echo("Output Directory=".$outputdir.PHP_EOL);}
	    
	    GLOBAL $docova;
	    $docova = new Docova($this->getContainer());
	    $docova_app = $docova->DocovaApplication(null, $this->appid, $docova);
	    
	    $doc_coll = $docova_app->getAllDocuments();
	    
	    echo(PHP_EOL."=============  Exporting Field Data to Text - Start =============".PHP_EOL);
	    
	    $placeholders = [];
	    preg_match_all('/\$\{(.*?)}/i', $texttemplate, $placeholders);
	    
	    if(is_array($placeholders) && count($placeholders) > 1){
	        $placeholders = $placeholders[1];
	    }
	    
	    $doc = $doc_coll->getFirstDocument();
	    
	    while(!empty($doc)){
	        
	        $temptext = $texttemplate;
	        
	        if($doc->getField("Form") == $formname){
	            $outputsubdir = $outputdir . strtolower($doc->id);
	            if(!file_exists($outputsubdir)){
	                mkdir($outputsubdir);
	            }
	            if(!file_exists($outputsubdir)){
	                echo("Unable to create export sub directory [".$outputsubdir."]. Exiting.".PHP_EOL);
	                return $exportcount;
	            }
	            $outputsubdir .= DIRECTORY_SEPARATOR;
	            $outputfile = $outputsubdir . $filenameprefix . '.txt';
	            
	            if($this->DEBUG){echo($outputfile . PHP_EOL);}
	            
	         
	            foreach($placeholders as $key => $placeholder){
	                $tempvals = null;
	                $tempvals = $doc->getField($placeholder);
	                if(isset($tempvals) && !is_array($tempvals)){
	                    $tempvals = [$tempvals];
	                }
	                if(isset($tempvals) && is_array($tempvals)){
	                    for($i=0; $i<count($tempvals); $i++){
	                        $tempvals[$i] = _Format($tempvals[$i]);
	                    }
	                }
	                
	                $tempval = (isset($tempvals) ? (is_array($tempvals) ? (count($tempvals) > 1 ? implode(", ", $tempvals) : $tempvals[0]) : $tempvals) : '');
	                $pattern = '${'.$placeholder.'}';
	                $temptext = str_replace($pattern, $tempval, $temptext);
	            }
	            
	            try{
	                // Write text:
	                if((trim($temptext) != "") && (file_put_contents($outputfile, $temptext) !== false)){
    	                $exportcount ++;
	                }	                
	            }catch(\Exception $e){
	                $trace = $e->getTrace();
	                
	                $result = 'Exception: "';
	                $result .= $e->getMessage();
	                $result .= '" @ ';
	                if($trace[0]['class'] != '') {
	                    $result .= $trace[0]['class'];
	                    $result .= '->';
	                }
	                $result .= $trace[0]['function'];
	                $result .= '();';
	                $result .= PHP_EOL . " on line #".$e->getLine().PHP_EOL;
	                $result .= $doc->id.PHP_EOL;
	                echo($result);
	            }
	        }
	        
	        $doc = $doc_coll->getNextDocument($doc);
	    }
	    
	    echo($exportcount." documents had field data exported to Text in [".$outputdir."].".PHP_EOL);
	    echo("=============  Exporting Field Data to Text - Finished =============".PHP_EOL);
	    
	    return $exportcount;
	}
	
	
	protected function exportDocumentDataToCSV(){
	    $exportcount = 0;
	    $datatrunc = 0;
	    
	    $question = new Question('What form do you want to export data from? ', '');
	    $formname = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    	    
	    $question = new Question('What do you want to name the output CSV file? ', $formname);
	    $filename = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    $outputdir = sys_get_temp_dir();
	    $question = new Question('What directory do you want to export the data to? ', $outputdir);
	    $outputdir = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    
	    
	    $qbFields = $this->em->getRepository("DocovaBundle:DesignElements")->createQueryBuilder("de");
	    $qbFields->leftJoin("de.form", "frm");
	    $qbFields->select("de.fieldName AS FieldName");
	    $qbFields->where("frm.formName=?1 OR frm.formAlias=?1");
	    $qbFields->andWhere("IDENTITY(frm.application)=?2");
	    $qbFields->andWhere("de.trash=false");
	    $qbFields->addOrderBy("FieldName", "ASC");
	    $qbFields->setParameter(1, $formname);
	    $qbFields->setParameter(2, $this->appid);
	    
	    $qbQuery = $qbFields->getQuery();
	    if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}
	    
	    $fields = $qbQuery->getArrayResult();
	    if(empty($fields)){
	        echo("No field definitions found for form [".$formname."]. Exiting.".PHP_EOL);
	        return $exportcount;	        
	    }
	    $fields = array_column($fields, "FieldName");
	    
	    
	    if(!file_exists($outputdir)){
	        echo("No export directory [".$outputdir."] found. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    
	    if(substr($outputdir, -1) !== DIRECTORY_SEPARATOR){
	        $outputdir .= DIRECTORY_SEPARATOR;
	    }
	    $outputdir .= strtolower($this->appid);
	    if(!file_exists($outputdir)){
	        mkdir($outputdir);
	    }
	    if(!file_exists($outputdir)){
	        echo("Unable to create export sub directory [".$outputdir."]. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    $outputdir .= DIRECTORY_SEPARATOR;
	    
	    if($this->DEBUG){echo("Output Directory=".$outputdir.PHP_EOL);}
	    
	    $headings[] = "id";
	    foreach($fields as $key => $fieldinfo){
	        array_push($headings, $fieldinfo);
	    }
	    
	    GLOBAL $docova;
	    $docova = new Docova($this->getContainer());
	    $docova_app = $docova->DocovaApplication(null, $this->appid, $docova);
	    
	    $doc_coll = $docova_app->getAllDocuments();
	    
	    echo(PHP_EOL."=============  Exporting Document Data to CSV - Start =============".PHP_EOL);
    
	    $outputfile = $outputdir . $filename . '.csv';	    
	    $fp = fopen($outputfile, 'w');
	    
	    fputcsv($fp, $headings);
	    
	    $doc = $doc_coll->getFirstDocument();
	    
	    while(!empty($doc)){
	        $linearray = [];
	        
	        if($doc->getField("Form") == $formname){
	            $linearray[] = $doc->id;
	            foreach($fields as $key => $fieldinfo){
	                $tempvals = null;
	                $tempvals = $doc->getField($fieldinfo);
	                if(isset($tempvals) && !is_array($tempvals)){
	                    $tempvals = [$tempvals];
	                }
	                if(isset($tempvals) && is_array($tempvals)){
	                    for($i=0; $i<count($tempvals); $i++){
	                        $tempvals[$i] = _Format($tempvals[$i]);
	                    }
	                }
	                
	                $tempval = (isset($tempvals) ? (is_array($tempvals) ? (count($tempvals) > 1 ? implode("; ", $tempvals) : $tempvals[0]) : $tempvals) : '');
	                $quotecount = substr_count($tempval, '"');
	                if((strlen($tempval) + $quotecount) > 32767){
	                    //truncate the data since it has reached an Excel limit
	                    $tempval = substr($tempval, 0, (32767 - $quotecount));
	                    $datatrunc ++;
	                }
	                array_push($linearray, $tempval);
	            }
	            
	            try{
	                // Write csv file line:
	                fputcsv($fp, $linearray);
                    $exportcount ++;
	            }catch(\Exception $e){
	                $trace = $e->getTrace();
	                
	                $result = 'Exception: "';
	                $result .= $e->getMessage();
	                $result .= '" @ ';
	                if($trace[0]['class'] != '') {
	                    $result .= $trace[0]['class'];
	                    $result .= '->';
	                }
	                $result .= $trace[0]['function'];
	                $result .= '();';
	                $result .= PHP_EOL . " on line #".$e->getLine().PHP_EOL;
	                $result .= $doc->id.PHP_EOL;
	                echo($result);
	            }
	        }
	        
	        $doc = $doc_coll->getNextDocument($doc);
	    }
	    fclose($fp);
	    
	    echo($exportcount." documents had field data exported to CSV in [".$outputdir."].".PHP_EOL);
	    if($datatrunc > 0){
	        echo($datatrunc." data values were truncated due to long string lengths.".PHP_EOL);
	    }
	    echo("=============  Exporting Document Data to CSV - Finished =============".PHP_EOL);
	    
	    return $exportcount;
	}
	
	
	
	protected function exportFileAttachmentsToFileSystem(){
	    $exportcount = 0;
	        
	    $outputdir = sys_get_temp_dir();
	    $question = new Question('What directory do you want to export the data to? ', $outputdir);
	    $outputdir = $this->qhelper->ask($this->input, $this->output, $question);
	    echo(PHP_EOL);
	    
	    if(!file_exists($outputdir)){
	        echo("No export directory [".$outputdir."] found. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    
	    if(substr($outputdir, -1) !== DIRECTORY_SEPARATOR){
	        $outputdir .= DIRECTORY_SEPARATOR;
	    }
	    $outputdir .= strtolower($this->appid);
	    if(!file_exists($outputdir)){
	        mkdir($outputdir);
	    }
	    if(!file_exists($outputdir)){
	        echo("Unable to create export sub directory [".$outputdir."]. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    $outputdir .= DIRECTORY_SEPARATOR;
	    
	    if($this->DEBUG){echo("Output Directory=".$outputdir.PHP_EOL);}
	    
	    $qb=$this->em->getRepository("DocovaBundle:AttachmentsDetails")->createQueryBuilder("ad");
	    
	    $qb->select("IDENTITY(ad.Document, 'id') AS Doc_Id,ad.File_Name,ad.File_Date")
	    ->join("ad.Document", "d")
	    ->where("d.Trash=?1 AND d.application=?2")
	    ->setParameter(1, false)
	    ->setParameter(2, $this->appid)
	    ->orderBy("Doc_Id", "ASC");
	    
	    $qbQuery = $qb->getQuery();
	    if($this->DEBUG){echo(PHP_EOL.$qbQuery->getSql().PHP_EOL);}
	    
	    $attdata = $qbQuery->getArrayResult();
	    
	    if(empty($attdata)){
	        echo("No data found to export for the specified application. Exiting.".PHP_EOL);
	        return $exportcount;
	    }
	    
	    echo(PHP_EOL."=============  Exporting Attachment Files - Start =============".PHP_EOL);
	    
	    foreach($attdata as $attinfo){
	        if($this->DEBUG){var_dump($attinfo);}

	        $sourcefile = $this->_attpath.DIRECTORY_SEPARATOR.$attinfo["Doc_Id"].DIRECTORY_SEPARATOR.md5($attinfo["File_Name"]);
	        if (file_exists($sourcefile)){
	        
    	        $outputsubdir = $outputdir . strtolower($attinfo["Doc_Id"]);
                if(!file_exists($outputsubdir)){
                    mkdir($outputsubdir);
                }
                if(!file_exists($outputsubdir)){
                    echo("Unable to create export sub directory [".$outputsubdir."]. Exiting.".PHP_EOL);
                    return $exportcount;
                }
                
    	        $outputsubdir .= DIRECTORY_SEPARATOR;	        
    	        $outputfile = $outputsubdir . $attinfo["File_Name"];
    	        
    	        if(copy($sourcefile, $outputfile)){
    	            $filedate = $attinfo["File_Date"];
    	            if(gettype($filedate) == "object" && get_class($filedate) == "DateTime"){
    	                touch($outputfile, $filedate->getTimestamp());
    	            }
    	            if($this->DEBUG){echo("Successfully copied file [".$sourcefile."] to [".$outputfile."]" . PHP_EOL);}
    	            $exportcount ++;
    	        }else{
    	            echo("Error copying file [".$sourcefile."] to [".$outputfile."]" . PHP_EOL);
    	            
    	        }
	        }
	            
	    }
	    
	    echo($exportcount." attachment files exported to [".$outputdir."].".PHP_EOL);
	    echo("=============  Exporting Attachments - Finished =============".PHP_EOL);
	    
	    return $exportcount;
	}
	
/**
	 * Find all embedded images and replace src with a valid path
	 * 
	 * @param string $sourcehtml
	 * @param string $sourceimagepath
	 * @return string|boolean
	 */
	private function replaceEmbeddedImagesPath($sourcehtml, $sourceimagepath)
	{
		if (false === stripos($sourcehtml, '<html>')) {
			$sourcehtml = '<html>'.$sourcehtml.'</html>';
		}
		$html = new \DOMDocument();
		$sourcehtml = mb_convert_encoding($sourcehtml, 'HTML-ENTITIES', 'UTF-8');
		
		$loadedok = true;
		libxml_use_internal_errors(true);
		try{
			@$html->loadHTML($sourcehtml, LIBXML_NOWARNING);
		}catch (\Exception $e) {
			$loadedok = false;
		}
		if(count(libxml_get_errors()) > 0){
			$loadedok = false;
		}
		libxml_clear_errors();
		if(!$loadedok){
			return false;
		}

		$matchprefix = '/Docova/embeddedImage/';
		$matchprefix2 = '?image=';

		$sourceadjusted = false;
		foreach ($html->getElementsByTagName('img') as $image)
		{
			$src = $image->getAttribute('src');
			if (false !== stripos($src, $matchprefix))
			{
				if(false !== stripos($src, $matchprefix2)){
					$docid = substr($src, strrpos($src, $matchprefix)+strlen($matchprefix));
					$docid = substr($docid, 0, strpos($docid, $matchprefix2));
					$image_name = substr($src, strrpos($src, $matchprefix2)+strlen($matchprefix2));	
				}else{
					$docid = substr($src, strrpos($src, strlen($matchprefix))+strlen($matchprefix));
					$docid = substr($docid, 0, strpos($docid, '/'));					
					$image_name = substr($src, strrpos($src, '/')+1);					
				}			
				
//				$img_name = htmlspecialchars(htmlspecialchars(substr($src, strrpos($src, '/')+1)));
				$url = $sourceimagepath.DIRECTORY_SEPARATOR.strtolower($docid).DIRECTORY_SEPARATOR.urlencode($image_name);
				if (file_exists($url)){
					$image->setAttribute('src', $url);
					$sourceadjusted = true;
				}
			}
		}
		
		
		return ($sourceadjusted ? $html->saveHTML() : false);
	}	
	
}