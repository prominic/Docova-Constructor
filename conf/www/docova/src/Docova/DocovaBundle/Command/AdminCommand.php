<?php
namespace Docova\DocovaBundle\Command;

//require 'C:\inetpub\wwwroot\docova_ecm\app\autoload.php';

/*
 * Basic Administration Commands
 */
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Extensions\SessionHelpers;
//use Docova\DocovaBundle\Extensions\UserSession;
class AdminCommand extends ContainerAwareCommand
{	
	protected $command=null;
	protected $global_settings;
	protected $em;
	public function showUsers(){
		SessionHelpers::showUserSessions($this->getEntityManager());
	}
	public function showUsersXml(){
		echo SessionHelpers::getUserSessions($this->getEntityManager(),false,"xml");
	}
	private function getEntityManager(){
		if ((empty($this->em)))
			return  $this->getContainer()->get('doctrine')->getManager();
		else
			return $this->em;
	}
	public function setEntityManager($em){
		$this->em=$em;
	}
	
	
	
	protected function configure()
	{
		$this
			->setName('docova:admin')
			->setDescription('DOCOVA SE Admin Console v1.0')
			->addArgument('secommand', InputArgument::OPTIONAL, 'Enter command please');
	}
	protected function execute(InputInterface $input, OutputInterface $output)
	{	
		$output->writeln("********* DOCOVA SE Admin Console v1.0 *********".PHP_EOL);
		$command=$input->getArgument('secommand');
		if (!empty($command)){			
			$this->command = $command;
			$output->writeln("Running command: ".$this->command.PHP_EOL);
		}			
		else{
			throw new \Exception("A command must be provided!");			
		}
		//echo "Getting manager".PHP_EOL;
		$em = $this->getEntityManager();
		//echo "Getting settings".PHP_EOL;
		try{
			$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		}
		catch (\PDOException $pe){
			$output->writeln("AdminCommand->excute() - Error PDO: ".$pe->getMessage());
			throw new \Exception("AdminCommand->excute() - Error PDO: ".$pe->getMessage().PHP_EOL);
		}
	
		$this->global_settings = $this->global_settings[0];
		if (!empty($this->global_settings)){
			$output->writeln("Host: ".$this->global_settings->getRunningServer());			
		}		
		$output->writeln("User Sessions");
		$output->writeln("--------------------");
		
		switch ($this->command){
			case "show users":
			case "SHOW USERS":
			case "sh u":
			case "sh users":
				$this->showUsers();
				break;
			case "show users xml":
				$this->showUsersXml();
				break;
			default:
				throw new \Exception("Unknown command provided!");
		}
		
		
	}
}
?>