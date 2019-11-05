<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Command to import/synch an application view data base on pre imported data in DB
 * @author javad_rahimi
 */
class ImportViewDataCommand extends ContainerAwareCommand 
{
	private $_app;
	private $_view;
	private $_output;
	private $_debug=false;
	private $_global_settings;
	
	protected function configure()
	{
		$this->setName('docova:importviewdata')
			->setDescription('Import/synch an application view data')
			->addArgument('app', InputArgument::REQUIRED, 'Application ID')
			->addArgument('view', InputArgument::REQUIRED, 'App view ID');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$app = $input->getArgument('app');
		$view = $input->getArgument('view');
		$em = $this->getContainer()->get('doctrine')->getManager();
		$this->_output = $output;
		$this->_app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		//$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => 'DOCOVA SE'));
		$appid = $this->_app->getId();

		if ( $view == "all"){
			$viewcoll = $em->getRepository('DocovaBundle:AppViews')->findBy(array( 'application' => $app));
		}else{

			$viewcoll = array($em->getRepository('DocovaBundle:AppViews')->findOneBy(array('id' => $view, 'application' => $app)));
		}

		if (empty($this->_app))
			throw new \Exception('Unspecified application source.');
		
		$gs = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->_global_settings = $gs[0];
		$gs = null;

		$repository = $em->getRepository('DocovaBundle:Documents');
		$docova = new Docova($this->getContainer());
		$view_handler = new ViewManipulation($docova, $this->_app, $this->_global_settings);
		$docova = null;

		foreach ( $viewcoll as $this->_view){
			if (empty($this->_view))
				throw new \Exception('Unspecified view source.');
			
			if ($this->_debug){
				$output->writeln("    Memory usage before indexing view with id ".$this->_view->getId().": " . (memory_get_usage() / 1024) . " KB");
			}
			$view = str_replace('-', '', $this->_view->getId());
			$addedcount = 0;
//			$removedcount = 0;
//			$updatedcount = 0;
			$errorcount = 0;
			try {
				if (!$view_handler->viewExists($view)) {
					throw new \Exception('Back-end view was not generated, contact admin for details.');
				}		
				$view_handler->truncateView($view);

				if ($this->_debug){
					$mem_pre = memory_get_usage();
				}
				$values_array = $repository->getDocFieldValues(array(), $this->_global_settings->getDefaultDateFormat(), $this->_global_settings->getUserDisplayDefault(), true, $appid);
				foreach ($values_array as $docid => $docval) {
					if (!$view_handler->isDocMatchView2($appid, array(0 => $docval), $this->_view->getConvertedQuery())) {
						$values_array[$docid] = null;
						unset($values_array[$docid]);
					}
				}

				$values_array = array_filter($values_array);
				$documents = array_keys($values_array);
				foreach ($documents as $doc)
				{
					$retval = $view_handler->indexDocument2($doc, array(0 => $values_array[$doc]), $appid, $this->_view->getId(), $this->_view->getViewPerspective(), null, true);
					if ($this->_debug){
						$mem_post = memory_get_usage();
						$output->writeln("        Memory change: " . round(($mem_post - $mem_pre)/1024, 0) . " KB");
					}
					if ( $retval == 0 )
						$errorcount++;
					elseif ( $retval == 1 )
						$addedcount++;
/* NO REMOVE/UPDATE OCCURE SINCE WE TRUNCATE FIRST */
//						elseif ( $retval == 2)
//							$removedcount++;
//						elseif ( $retval == 3 )
//							$updatedcount++;
				}
				
				$output->writeln('View : '.$this->_view->getViewName().' Status: OK, Added: '.$addedcount./*' Updated: '.$updatedcount.' Removed: '.$removedcount.*/' Errors: '.$errorcount);
			}
			catch (\Exception $e) {
				$logger = $this->getContainer()->get('logger');
				$logger->error($e->getMessage()."\n");
				$logger = null;
				$output->writeln('Status: FAILED,  ErrMsg: '.$e->getMessage().' on line '.$e->getLine().' of '.$e->getFile());
			}
			
			if ($this->_debug){
				$output->writeln("    Memory usage after indexing view with id ".$this->_view->getId().": " . (memory_get_usage() / 1024) . " KB");
			}
							
		}
	}
	
	/**
	 * Render view selection script
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 * @return boolean
	 */
	private function renderSelectionScript($document, $user)
	{
		$query = $this->_view->getConvertedQuery();
		if (empty($query))
			return false;

		if (in_array(strtolower(trim($query)), array('1', 'true')))
			return true;
		
		if (false === strpos($query, '{') && false === strpos($query, '%'))
			return false;

		try {
			$query = '{{ f_SetUser(user) }}{{ f_SetApplication(application) }}{{ f_SetDocument(document) }}{% docovascript "output:string" %}'.$query.'{% enddocovascript %}';
			$template = $this->getContainer()->get('twig')->createTemplate($query);
			$output = $template->render(array(
				'document' => $document,
				'application' => $this->_app->getId(),
				'user' => $user
			));
			
			$this->_output->writeln('Query '.$query.' output '.$output);
			$output = trim($output);
			$template = $document = null;
			if (!empty($output))
			{
				return true;
			}
		}
		catch (\Exception $e) {
			$logger = $this->getContainer()->get('logger');
			$logger->error($e->getMessage()."\n");
			$logger = null;
			echo "{$e->getMessage()}\n";
			return false;
		}
		return false;
	}
}