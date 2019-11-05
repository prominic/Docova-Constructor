<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Entity\EventLogs;
use Docova\DocovaBundle\Controller\Miscellaneous;

/**
 * @author javad rahimi
 *        
 */
class DailyArchiveCommand extends ContainerAwareCommand 
{
	private $global_settings;
	private $libraryId;
	
	protected function configure()
	{
		$this
			->setName('docova:dailyarchive')
			->addArgument('libid', InputArgument::REQUIRED, 'Library ID to run daily archiving on it.')
			->setDescription('Daily Archiving Tasks');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->libraryId = $input->getArgument('libid');
		$em = $this->getContainer()->get('doctrine')->getManager();
		$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
//		if ($this->global_settings->getRunningServer() == $this->getContainer()->get('router')->getContext()->getHost())
//		{
			$time_to_run = $this->global_settings->getArchiveTime() ? $this->global_settings->getArchiveTime()->format('h:i:s A') : '04:00:00 AM';
			if (true === $this->isTimeToRun($time_to_run))
			{
				$this->processArchivePolicies();
				$this->ProcessCustomArchive();
			}
//		}
	}
	
	/**
	 * Process for archiving document base on created policies in admin 
	 */
	private function processArchivePolicies()
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$archive_policies = $em->getRepository('DocovaBundle:ArchivePolicies')->findBy(array('policyStatus' => true), array('policyPriority' => 'ASC'));

		if (!empty($archive_policies) && count($archive_policies) > 0)
		{
			foreach ($archive_policies as $ap) {

				if ($ap->getLibraries()->count() > 0) {
					$found = false;
					foreach ($ap->getLibraries() as $library) {
						if ($library->getId() == $this->libraryId) 
						{
							$found = true;
							break;
						}
					}
				}
				else { $found = true; }
				
				if ($found !== true) { return false; }

				$filters = $doctypes = array();
				
				$filters['SkipWorkflow'] = ($ap->getArchiveSkipWorkflow() === true) ? true : false;
				$filters['SkipDrafts'] = $ap->getArchiveSkipDrafts() === true ? true : false;
				$filters['SkipReleased'] = $ap->getArchiveSkipVersions() === true ? true : false;
				if (true === $ap->getEnableDateArchive()) 
				{
					$filters['ArchiveDateField'] = $ap->getArchiveDateSelect();
					$filters['ArchiveDelay'] = $ap->getArchiveDelay();
				} 

				if ($ap->getDocumentTypes()->count() > 0) {
					foreach ($ap->getDocumentTypes() as $dtype) {
						$doctypes[] = $dtype->getId();
					}
				}

				$custom_query = trim($ap->getArchiveCustomFormula()) ? trim($ap->getArchiveCustomFormula()) : '';
				
				$documents = $em->getRepository('DocovaBundle:Documents')->getDocumentsForArchiving($this->libraryId, $filters, $doctypes, $custom_query);
				$arc_count = 0; 
				foreach ($documents as $document)
				{
					if (!empty($document) && true === $this->isArchivePolicyApplicable($document, $ap, $em)) {
						if (true === $this->archiveDocument($document, $em))
						{
							$arc_count++;
							$log_obj = new Miscellaneous($this->getContainer());
							$doc = $em->getReference('DocovaBundle:Documents', $document['id']);
							$log_obj->createDocumentLog($em, 'ARCHIVE', $doc, 'Archived document by policy: '.$ap->getPolicyName());
							$log_obj = $doc = null;
						}
					}
				}
				
				if ($arc_count > 0) 
				{
					$event_log = new EventLogs();
					$event_log->setAgentName('Daily Archive Tasks');
					$event_log->setEventDate(new \DateTime());
					$event_log->setServer($this->getContainer()->get('router')->getContext()->getHost());
					$event_log->setDetails("archived $arc_count document(s) as per {$ap->getPolicyName()} policy for Library ID '{$this->libraryId}'.");
					$em->persist($event_log);
					$em->flush();
					$event_log = null;
				}
			}
		}
	}
	
	/**
	 * Process for archiving document base on custom document settings
	 */
	private function ProcessCustomArchive()
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$documents = $em->getRepository('DocovaBundle:Documents')->createQueryBuilder('D')
			->join('D.folder', 'F')
			->select(array('D.id', 'D.Status_No', 'D.Doc_Status', 'D.Custom_Archive_Date'))
			->where("D.Archive_Type = 'C'")
			->andWhere('D.Archived = false')
			->andWhere('D.Trash = false')
			->andWhere('F.Library = :libid')
			->setParameter('libid', $this->libraryId)
			->getQuery()
			->getArrayResult();
		if (!empty($documents[0]) && count($documents) > 0) 
		{
			$arc_count = 0;
			foreach ($documents as $document)
			{
				if ($document['Custom_Archive_Date']) 
				{
					$customADate = new \DateTime();
					$customADate->setTimestamp(strtotime($document['Custom_Archive_Date']));
					$today = new \DateTime();
					if ((int)$customADate->diff($today)->format('%r%a') > 0) 
					{
						if (true === $this->archiveDocument($document, $em)) {
							$arc_count++;
							$log_obj = new Miscellaneous($this->getContainer());
							$doc = $em->getReference('DocovaBundle:Documents', $document['id']);
							$log_obj->createDocumentLog($em, 'ARCHIVE', $doc, 'Archived document.');
							$log_obj = $doc = null;
						}
					}
				}
			}
					
			if ($arc_count > 0) 
			{
				$event_log = new EventLogs();
				$event_log->setAgentName('Daily Archive Tasks');
				$event_log->setEventDate(new \DateTime());
				$event_log->setServer($this->getContainer()->get('router')->getContext()->getHost());
				$event_log->setDetails("archived $arc_count document(s) as per custom archive date settings in Library ID '{$this->libraryId}'.");
				$em->persist($event_log);
				$em->flush();
			}
		}
	}
	
	/**
	 * Check if the policy is applicable for the document
	 * 
	 * @param array $document
	 * @param \Docova\DocovaBundle\Entity\ArchivePolicies $archive_policy
	 * @param \Doctrine\ORM\EntityManager $em
	 * @return boolean
	 */
	private function isArchivePolicyApplicable($document, $archive_policy, $em)
	{
		if (true === $archive_policy->getArchiveSkipVersions() && true == $document['Enable_Versions'] && $document['Status_No'] != 5) 
		{
		    $dockey = (!empty($document['Parent_Document']) ? $document['Parent_Document'] : $document['id']);
			//-- archive policy says keep x number of releases and this document type has versioning so check the version number
			$doc_versions = $em->getRepository('DocovaBundle:Documents')->getAllDocVersionsFromParent($dockey);
			if (count($doc_versions) <= $archive_policy->getVersionCount()) {
				$doc_versions = null;
				return false;
			}
			
			$vcount = 0;
			foreach ($doc_versions as $doc) {
				if ($doc->getStatusNo() == 1 || $doc->getStatusNo() == 2) {
					$vcount++;
					
					if ($vcount <= $archive_policy->getVersionCount() && $doc->getId() === $document['id']) {
						$doc_versions = $doc = null;
						return false;
					}
					elseif ($vcount > $archive_policy->getVersionCount()) {
						$doc_versions = $doc = null;
						return true;
					}
				}
			}
		}
		elseif (true === $archive_policy->getArchiveSkipVersions() && !$document['Enable_Versions'] && $document['Status_No'] == 1){
			//-- review policy says keep x number of releases and this document type doesn't have versioning enabled so keep any release
			return false;
		}
		return true;
	}
	
	/**
	 * Archive the document
	 * 
	 * @param array $document
	 * @param \Doctrine\ORM\EntityManager $em
	 * @return boolean
	 */
	private function archiveDocument($document, $em)
	{
		$em->getConnection()->beginTransaction();
		try {
			$query = $em->createQueryBuilder()
				->update('Docova\DocovaBundle\Entity\Documents', 'D')
				->set('D.Archived', ':archived')
				->set('D.Date_Archived', ':adate')
				->set('D.Status_No_Archived', ':astatusno')
				->set('D.Previous_Status', ':pstatus')
				->set('D.Status_No', ':statusno')
				->set('D.Doc_Status', ':dstatus')
				->where('D.id = :docid')
				->setParameters(array(
					'archived' => true,
					'adate' => new \DateTime(),
					'astatusno' => $document['Status_No'],
					'pstatus' => $document['Doc_Status'],
					'statusno' => 6,
					'dstatus' => 'Archived',
					'docid' => $document['id']
				))
				->getQuery();
			
			$query->execute();
			
			$query = $em->createQueryBuilder()
				->delete('Docova\DocovaBundle\Entity\Bookmarks', 'B')
				->where('B.Document = :docid')
				->setParameter('docid', $document['id'])
				->getQuery();
			
			$query->execute();
			$em->getConnection()->commit();
			$document = $query = $em = null;
			return true;
		}
		catch (\Exception $e) {
			$em->getConnection()->rollback();
			echo $e->getMessage();
			$em = null;
			return false;
		}
	}

	/**
	 * Check if run time is within 60 minutes range
	 *
	 * @param string $run_time
	 * @return boolean
	 */
	private function isTimeToRun($run_time)
	{
		$diff = ceil((time() - strtotime($run_time))/60);
		if ($diff == 0 || ($diff > 0 && $diff < 60))
		{
			return true;
		}
	
		return false;
	}
}