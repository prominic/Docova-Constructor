<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpFoundation\Session\Session;
use Docova\DocovaBundle\ObjectModel\Docova;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Back-end command class to auto run custom agents from Agent folder 
 * @author javad_rahimi
 */
class AgentRunnerCommand extends ContainerAwareCommand 
{
	protected function configure()
	{
		$this->setName('docova:agentrunner')
			->addArgument('agent_name', InputArgument::REQUIRED, 'Agent Name to run')
			->addArgument('app', InputArgument::REQUIRED, 'Application ID which agent defined in it.')
			->addArgument('docid', InputArgument::OPTIONAL, 'Optional Document ID passed to agent [Equivalent to ParameterDocID in Domion]')
			->addOption('isoutput', 'io', InputOption::VALUE_OPTIONAL, 'Does agent return output?', false)
			->setDescription('DOCOVA agent runner to run back-end custom agents defined by user.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$agent_name = $input->getArgument('agent_name');
		$app = $input->getArgument('app');
		$docidorobj = $input->getArgument('docid');
		$docidorobj = !empty($docidorobj) ? $docidorobj : null;
		$is_ouput = $input->getOption('isoutput');
		$is_ouput = !empty($is_ouput) ? true : false;
		
		//-- store current app and user in case different than where this agent is running
		$session = new Session();
		$current_app = $session->get('currentApp');
		$current_user = $session->get('currentUser');	
		
		try {
			if (empty($agent_name)) {
				throw new \Exception('Undefined agent name');
			}
			
			$em = $this->getContainer()->get('doctrine')->getManager();
			$agent = $em->getRepository('DocovaBundle:AppAgents')->findOneBy(array('agentName' => $agent_name, 'application' => $app));
			if (empty($agent)) {
				throw new \Exception('Specified agent could not be found in the application');
			}
			
			$class_name = "\Docova\DocovaBundle\Agents\A".str_replace('-', '', $app);
			$agent_name = str_replace(array('/', '\\', '-', ' '), '', $agent->getAgentName());
			$class_name .= "\\".$agent_name;

			$scriptpath = $this->getContainer()->get('kernel')->getRootDir() . '/../src' . str_replace('\\', '/', $class_name) . ".php";
			
			if (! @include_once( $scriptpath )){ 
				throw new \Exception ('Agent does not exist or could not be loaded!');
			}
			
			if (!class_exists($class_name)) {
				throw new \Exception('Agent class not found!');
			}
			
			//--ensure current app is set to agents app
			$session->set('currentApp', base64_encode($app));
			
			//--if no current user defined use agents last modified user or created by user
			if (!$current_user){
			    $tempuserobj = $agent->getModifiedBy();
				if(empty($tempuserobj)){
				    $tempuserobj = $agent->getCreatedBy();
				}
				$userid = $tempuserobj->getId();				
				$session->set('currentUser', base64_encode($userid));
				$usertoken = $this->getContainer()->get('security.token_storage')->getToken();
				
				if(empty($usertoken)){
				    $tempusertoken = new UsernamePasswordToken($tempuserobj, null, 'docova', $tempuserobj->getRoles());
				    $this->getContainer()->get('security.token_storage')->setToken($tempusertoken);
				}
			}			
            
			$docova_obj = new Docova($this->getContainer());
			$agentobj = new $class_name($docova_obj, $this->getContainer());			
			
			ob_start();
			$agentobj->initialize($docidorobj);
			$printed = ob_get_contents();
			ob_end_clean();

			if (!empty($tempusertoken)) {
				$this->getContainer()->get('security.token_storage')->setToken(null);
			}
			$agent->setLastExecution(new \DateTime());
			$em->flush();			
						
			if (!empty($printed) && $is_ouput === true) {
				$output->writeln($printed);
			}
			else {
				//@note: do I have to print true/false as output when is_output is false?!
				$output->write(strval(true));
			}
		}
		catch (\Exception $e) {
			if ($is_ouput === true) {
				$output->writeln('');
				$output->writeln('ERROR: Agent execution failed on line: '. $e->getLine().' in "'.$e->getFile()."\" with the following message:\n".$e->getMessage());
			}
			else {
				//@note: do I have to print true/false as output when is_output is false?!
				$output->write(strval(false));
			}
		}
		
		//-- reset current app and user in case they were overridden
		if (empty($current_app)){
			$session->remove('currentApp');
		}else{
			$session->set('currentApp', $current_app);
		}
		if (empty($current_user)){
			$session->remove('currentUser');
		}else{
			$session->set('currentUser', $current_user);
		}
		if(empty($usertoken)){
		    $this->getContainer()->get('security.token_storage')->setToken($usertoken);
		}
		
		unset($agentobj, $agent, $session);
	}
}