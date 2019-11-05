<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Run the auto group synch command per exisitng groups
 * @author javad rahimi
 *        
 */
class SynchPerGroupCommand extends ContainerAwareCommand 
{
	protected function configure()
	{
		$this->setName('docova:synchpergroup')
			->setDescription('Auto synch group memebers per exisging group.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->getContainer()->get('Doctrine')->getManager();
		$groups = $em->getRepository('DocovaBundle:UserRoles')->createQueryBuilder('G')
			->select('G.id')
			->where('G.Group_Type = true')
			->getQuery()
			->getArrayResult();
		
		$em = null;
		
		if (!empty($groups[0]))
		{
			foreach ($groups as $g)
			{
				$argument = array(
						'command' => 'docova:syncgroups',
						'groupid' => $g['id']
				);
		
				$syncGroupsCommand = new ArrayInput($argument);
				$command = $this->getApplication()->find('docova:syncgroups');
		
				$command->run($syncGroupsCommand, $output);
			}
		}
		
	}
}