<?php

/*
 * This file is a variant of the core AssetsInstallCommand
 * includes as part of the part of the Symfony package and
 * original developed by Fabien Potencier <fabien@symfony.com>
 *
 * This file has been modified by DLI.tools to allow targeting 
 * a particular set of assets rather than all bundles.
 *
 * 
 */

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
//use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Command that places application web assets into a given directory.
 *
 * @author DLI.tools <support@dlitools.com>
 */
class AppAssetsInstallCommand extends ContainerAwareCommand
{
    const METHOD_COPY = 'copy';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('docova:appassetsinstall')
            ->setDefinition(array(
           		new InputArgument('appid', InputArgument::REQUIRED, 'The application id to update'),            		
                new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'public')            		
            ))
            ->setDescription('Installs application web assets under a public directory')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command installs application assets for the specified application into a given
directory (e.g. the <comment>public</comment> directory).

  <info>php %command.full_name% appid</info>
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	$appid = $input->getArgument('appid');
    	if(empty($appid) || $appid == ""){
    		throw new \InvalidArgumentException('No application id specified.');
    	}
    	
        $targetArg = rtrim($input->getArgument('target'), '/');

        if (!is_dir($targetArg)) {
            $targetArg = $this->getContainer()->getParameter('kernel.project_dir').'/'.$targetArg;

            if (!is_dir($targetArg)) {
                // deprecated, logic to be removed in 4.0
                // this allows the commands to work out of the box with web/ and public/
                if (is_dir(dirname($targetArg).'/web')) {
                    $targetArg = dirname($targetArg).'/web';
                } else {
                    throw new \InvalidArgumentException(sprintf('The target directory "%s" does not exist.', $input->getArgument('target')));
                }
            }
        }

        $this->filesystem = $this->getContainer()->get('filesystem');

        // Create the bundles directory otherwise symlink will fail.
        $bundlesDir = $targetArg.'/bundles/';
        if(!is_dir($bundlesDir)){
	        $this->filesystem->mkdir($bundlesDir, 0777);
        }
        
        $io = new SymfonyStyle($input, $output);
        $io->newLine();
        $io->text('Installing assets as <info>hard copies</info>.');
        $io->newLine();

        $rows = array();
        $exitCode = 0;
      
        $assetSubDirs = array(
        		"js".'/custom/'.$appid.'/', 
        		"css".'/custom/'.$appid.'/',         		
        		"images".'/'.$appid.'/'        		
        );
        $dirsToRemove = array();
        
        /** @var BundleInterface $bundle */
        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
        	$originDir = $bundle->getPath().'/Resources/public';
            if (!is_dir($originDir)) {
                continue;
            }
 
            
            $assetDir = preg_replace('/bundle$/', '', strtolower($bundle->getName()));
            
            foreach ($assetSubDirs as $subdir){
            	$sourceDir = $originDir.'/'.$subdir;
            	$targetDir = $bundlesDir.$assetDir.'/'.$subdir;

            	if(is_dir($sourceDir)){
            		if(!is_dir($targetDir)){
            			$this->filesystem->mkdir($targetDir, 0777);
            		}
            		           		
	         		$message = sprintf("%s", $assetDir.'/'.$subdir);

	            	try {
   		         		$this->filesystem->remove($targetDir);
            	
           				$method = $this->hardCopy($sourceDir, $targetDir);
            	
       		 			$rows[] = array(sprintf('<fg=green;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" /* HEAVY CHECK MARK (U+2714) */), $message, $method);
            		} catch (\Exception $e) {
            			$exitCode = 1;
            			$rows[] = array(sprintf('<fg=red;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98" /* HEAVY BALLOT X (U+2718) */), $message, $e->getMessage());
            		}            	
            	}else{
            		if(is_dir($targetDir)){
            			$dirsToRemove[] = $targetDir;            			 
            		}
            	}
   
            }
            
        }

        // remove assets that no longer exist
        $this->filesystem->remove($dirsToRemove);

        $io->table(array('', 'Asset', 'Method / Error'), $rows);

        if (0 !== $exitCode) {
            $io->error('Some errors occurred while installing assets.');
        } else {
            $io->note('Application assets were installed via copy. If you make changes to these assets you have to run this command again.');
            $io->success('All application assets were successfully installed.');
        }

        return $exitCode;
    }


    /**
     * Copies origin to target.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function hardCopy($originDir, $targetDir)
    {
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));

        return self::METHOD_COPY;
    }
}
