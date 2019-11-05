<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Yaml\Yaml;


/**
 * Command to generate a new translation yml file based on
 * an existing source file
 * @author chris_fales
 */
class CreateTranslationFileCommand extends ContainerAwareCommand 
{
    const TRANSLATION_URL = "https://translate.googleapis.com/translate_a/single?client=gtx&sl={{sourcelang}}&tl={{targetlang}}&dt=t&q={{sourcetext}}";
    
	protected function configure()
	{
		$this->setName('docova:createtranslationfile')
			->setDescription('Generate a new translation yml file based on an existing source file')
			->addArgument('sourcelang', InputArgument::REQUIRED, 'Language File to Use as Source (eg. en)')
			->addArgument('targetlang', InputArgument::REQUIRED, 'Target Language File (eg. fr)');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
	   
	    $sourcelang = $input->getArgument('sourcelang');
	    $targetlang = $input->getArgument('targetlang');
	    
	    	    
	    $translationDir = $this->getContainer()->getParameter('kernel.project_dir')."/src/Docova/DocovaBundle/Resources/translations";
	    $sourcefilename = "messages.".$sourcelang.".yml";
	    $targetfilename = "messages.".$targetlang.".yml";
	    
	    $baseurl = self::TRANSLATION_URL;
	    $baseurl = str_replace("{{sourcelang}}", "en", $baseurl);  //--key is always english
	    $baseurl = str_replace("{{targetlang}}", $targetlang, $baseurl);
	    
        $errors = 0;
        $translated = 0;
        $linetotal = 0;
        
        if (is_dir($translationDir)) {
	            $sourceFile = $translationDir."/".$sourcefilename;
	            $targetFile = $translationDir."/".$targetfilename;
	            if(!is_file($sourceFile)){
	                $output->writeln('Source translation file [".$sourcefilename."] was not found in the translations resource folder.');
	            }else if(is_file($targetFile)){
	                $output->writeln('Target translation file [".$targetfilename."] already exists in the translations resource folder.');
	            }else{
	                $textdata = file_get_contents($sourceFile);
	                if($textdata !== false){	                    
	                    $curl = curl_init();
	                   	                    
    	                $yaml = Yaml::parse($textdata);
    	                
    	                $linetotal = count($yaml);
    	                $progressBar = new ProgressBar($output, $linetotal);
    	                $progressBar->setRedrawFrequency(1);
    	                $progressBar->setOverwrite(true);
    	                $progressBar->start();
    	                
    	                foreach($yaml as $key => $value){    	                        	                    
    	                    $sourcetext = urlencode($key);
    	                    $targeturl = $baseurl;
    	                    $targeturl = str_replace("{{sourcetext}}", $sourcetext, $targeturl);
    	                        	                    
    	                    // Set some options - we are passing in a useragent too here
    	                    curl_setopt_array($curl, array(
    	                        CURLOPT_URL => $targeturl,
    	                        CURLOPT_RETURNTRANSFER => 1
    	                    ));
    	                    
    	                    // Send the request & save response to $resp
    	                    $resp = curl_exec($curl);

    	                    if (curl_error($curl)) {
    	                        $errors ++;
    	                    }else{       	                    
          	                    $jsondata = json_decode($resp, true);
          	                    if(is_array($jsondata)){
          	                        
          	                    }
          	                    $yaml[$key] = $jsondata[0][0][0];
    
        	                    $translated ++;
    	                    }
    	                    $progressBar->advance();
    	                }
    	                $progressBar->finish();
    	                curl_close($curl);
    	                
    	                if($translated > 0){
    	                    file_put_contents($targetFile,  Yaml::dump($yaml));
    	                }
    	                
	                }
	            }
        }
	    
        if($translated > 0){
            $msg = "Translation file created.";
            if($errors > 0){
                $msg .= " ".(string) $errors." were encountered during translation.";
            }
        }else{
            $msg = "Translation failed";
        }
        $output->writeln(PHP_EOL.$msg);
        
	}
}