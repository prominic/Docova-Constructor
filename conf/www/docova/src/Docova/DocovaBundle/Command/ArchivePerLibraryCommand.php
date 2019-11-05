<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Archive documents per library
 * @author javad rahimi
 *
 */
class ArchivePerLibraryCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this->setName('docova:archiveperlibrary')
			->setDescription('Daily archiving task per library.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$libraries = $em->getRepository('DocovaBundle:Libraries')->createQueryBuilder('L')
			->select('L.id')
			->where('L.Trash = false')
			->getQuery()
			->getArrayResult();

		if (!empty($libraries[0]))
		{
			foreach ($libraries as $libid)
			{
				$argument = array(
					'command' => 'docova:dailyarchive',
					'libid' => $libid['id']
				);

				$dailyArchiveCommand = new ArrayInput($argument);
				$command = $this->getApplication()->find('docova:dailyarchive');

				$command->run($dailyArchiveCommand, $output);
			}
		}
	}
}