<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Executes all scheduled app agents when current time meets the scheduling time 
 * @author javad_rahimi
 */
class ScheduledAgentsCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this->setName('docova:scheduledagents')
			->addOption('debugmode', '', InputOption::VALUE_OPTIONAL, 'Enable debug mode', false)
			->setDescription('Executes all scheduled app agents when it is time to run.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$debug = $input->getOption('debugmode');
		$debug = !empty($debug) && $debug === 'true' ? true : false;
		$em = $this->getContainer()->get('doctrine')->getManager();
		$app_agents = $em->getRepository('DocovaBundle:AppAgents')->getScheduledAgents();
		if (!empty($app_agents))
		{
			$executed = $total = 0;
			$keys = array_keys($app_agents);
			$curr_app = array_pop($keys);
			if ($debug === true) {
				$output->writeln('---------- Checking scheduled agents for App with ID "'. $curr_app.'" started ----------');
			}
			foreach ($app_agents as $appid => $app_ag)
			{
				if ($appid != $curr_app) {
					if ($debug === true) {
						if (empty($executed)) {
							$output->writeln('No agent found to be executed for application with ID "'.$appid.'"');
						}
						else {
							$output->writeln('"'.$executed.'" agents has been executed for application with ID "'.$appid.'"');
						}
						$output->writeln('---------- Checking scheduled agents end ----------');
						$output->writeln('---------- Checking scheduled agents for App with ID "'. $appid .'" started ----------');
					}
					$curr_app = $appid;
					$executed = 0;
				}
				
				foreach ($app_ag as $agent)
				{
					switch ($agent['agentSchedule']) {
						case '1':
							$execute = $this->checkTimeToRun($agent['lastExecution'], $agent['intervalHours'], $agent['intervalMinutes']);
							break;
						case 'D':
							$execute = $this->checkTimeToRun($agent['lastExecution'], $agent['startHour'], $agent['startMinutes'], $agent['startHourAmPm']);
							break;
						case 'M':
							$execute = $this->checkTimeToRun($agent['lastExecution'], $agent['startHour'], $agent['startMinutes'], $agent['startHourAmPm'], $agent['startDayOfMonth']);
							break;
						case 'W':
							$execute = $this->checkTimeToRun($agent['lastExecution'], $agent['startHour'], $agent['startMinutes'], $agent['startHourAmPm'], null, $agent['startWeekDay']);
							break;
					}
					
					if ($execute === true) {
						$argument = [
							'command' => 'docova:agentrunner',
							'agent_name' => $agent['agentName'],
							'app' => $appid,
							'--isoutput' => $debug
						];
						
						$agentRunnerCommand = new ArrayInput($argument);
						$command = $this->getApplication()->find('docova:agentrunner');
						$command->run($agentRunnerCommand, $output);
						$executed++;
						$total++;
					}
				}
			}
			
			if (empty($total)) {
				$output->writeln('No agent has been executed');
			}
			else {
				$output->writeln('"'.$total.'" amount of agents has been executed in total!');
			}
		}
		else {
			$output->writeln('No scheduled app agent found!');
		}
	}
	
	/**
	 * Check if it's time to run the scheduled agent
	 * 
	 * @param \DateTime|string $last_execution
	 * @param integer $hour
	 * @param integer $minute
	 * @param string $periods
	 * @param integer $month_day
	 * @param string $week_day
	 * @return boolean
	 */
	private function checkTimeToRun($last_execution, $hour, $minute, $periods = null, $month_day = null, $week_day = null)
	{
		if (empty($last_execution))
		{
			return true;
		}
		
		if (!($last_execution instanceof \DateTime))
		{
			$last_execution = new \DateTime($last_execution);
		}
		
		$now = new \DateTime();
		if (is_null($periods))
		{
			$tmp_date = $last_execution;
			$tmp_date->add(new \DateInterval('PT'.$hour.'H'.$minute.'M'));

			if ($tmp_date <= $now) {
				return true;
			}
		}
		elseif (empty($month_day) && empty($week_day))
		{
			$tmp_date = new \DateTime("Now $hour:$minute $periods");
			if (intval($last_execution->diff($now)->format('%r%a')) > 0 && $now >= $tmp_date) {
				return true;
			}
		}
		elseif (!empty($month_day) && empty($week_day))
		{
			$tmp_date = new \DateTime("Now $hour:$minute $periods");
			if (intval($last_execution->format('m')) < intval($now->format('m')) && intval($now->format('d')) >= $month_day && $now >= $tmp_date) {
				return true;
			}
		}
		elseif (!empty($week_day) && empty($month_day))
		{
			$tmp_date = new \DateTime("This week $week_day");
			if (intval($last_execution->diff($now)->format('%r%a')) > 1 && $now->format('l') == $tmp_date->format('l') && $now >= $tmp_date) {
				return true;
			}
		}
		
		return false;
	}
}