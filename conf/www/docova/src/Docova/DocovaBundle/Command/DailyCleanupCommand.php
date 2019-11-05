<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
//use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Entity\EventLogs;
use Docova\DocovaBundle\Entity\TrashedLogs;

/**
 * @author javad rahimi
 * Cleans all Document, Folder and Event Logs in DB if the created log date 
 * passes the deadline defined for Recycle Retention or Log Retention
 *        
 */
class DailyCleanupCommand extends ContainerAwareCommand 
{
	private $ATTACHMENT_PATH;
	private $global_settings;
	private static $trashed_count = 0;
	
	protected function configure()
	{
		$this
		->setName('docova:dailycleanup')
		->setDescription('Daily Cleanup Tasks');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
//		if ($this->global_settings->getRunningServer() == $this->getContainer()->get('router')->getContext()->getHost())
//		{
			$time_to_run = $this->global_settings->getCleanupTime() ? $this->global_settings->getCleanupTime()->format('h:i:s A') : '03:00:00 AM';
			if (true === $this->isTimeToRun($time_to_run))
			{
				$this->cleanExpiredPublicAccessResources();
				$this->cleanupTrash();
				$this->cleanExpiredLogs();
				$this->cleanExpiredEvents();				
			}
//		}
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
	
	/**
	 * Clear all trashed folders and/or documents
	 */
	private function cleanupTrash()
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$deleted_documents = $em->getRepository('DocovaBundle:Documents')->findBy(array('Trash' => true));
		foreach ($deleted_documents as $document) 
		{
			if ($document->getFolder())
			{
				$recycle_retention = $document->getFolder()->getLibrary()->getRecycleRetention() ? $document->getFolder()->getLibrary()->getRecycleRetention() : 30;
				if ($document->getDateDeleted()->modify("+$recycle_retention day")->format('U') <= date('U'))
				{
					$this->removeDocument($document, $em);
				}
			}
		}

		$deleted_folders = $em->getRepository('DocovaBundle:Folders')->findBy(array('Del' => true));
		foreach ($deleted_folders as $folder) 
		{
			$recycle_retention = $folder->getLibrary()->getRecycleRetention() ? $folder->getLibrary()->getRecycleRetention() : 30;
			if ($folder->getDateDeleted()->modify("+$recycle_retention day")->format('U') <= date('U'))
			{
				$this->removeFolder($folder, $em);
			}
		}
		
		if ($this::$trashed_count > 0) 
		{
			$event_log = new EventLogs();
			$event_log->setAgentName('Daily Cleanup Tasks');
			$event_log->setEventDate(new \DateTime());
			$event_log->setServer($this->getContainer()->get('router')->getContext()->getHost());
			$event_log->setDetails("permanently deleted {$this::$trashed_count} document(s)/folder(s)");
			$em->persist($event_log);
			$em->flush();
		}
		
		$this::$trashed_count = 0;
	}
	
	/**
	 * Delete all expired logs for documents / folders 
	 */
	private function cleanExpiredLogs()
	{
		$count = 0;
		$em = $this->getContainer()->get('doctrine')->getManager();
		$logs = $em->getRepository('DocovaBundle:TrashedLogs')->getExpiredLogs();
		if (!empty($logs) && count($logs) > 0)
		{
			foreach ($logs as $log) {
				$em->remove($log);
				$count++;
			}
			$em->flush();
		
			$event_log = new EventLogs();
			$event_log->setAgentName('Daily Cleanup Tasks');
			$event_log->setEventDate(new \DateTime());
			$event_log->setServer($this->getContainer()->get('router')->getContext()->getHost());
			$event_log->setDetails("permanently deleted $count document/folder log(s)");
			$em->persist($event_log);
			$em->flush();
		}
		$logs = $log = null;
	}
	
	/**
	 * Delete all expired public access profiles
	 */
	private function cleanExpiredPublicAccessResources()
	{
		$count = 0;
		$em = $this->getContainer()->get('doctrine')->getManager();
		$paps = $em->getRepository('DocovaBundle:PublicAccessResources')->getExpired();
		if (!empty($paps) && count($paps) > 0)
		{
			foreach ($paps as $pap) {
				$em->remove($pap);
				$count++;
			}
			$em->flush();
	
			$event_log = new EventLogs();
			$event_log->setAgentName('Daily Cleanup Tasks');
			$event_log->setEventDate(new \DateTime());
			$event_log->setServer($this->getContainer()->get('router')->getContext()->getHost());
			$event_log->setDetails("permanently deleted $count public access resource(s)");
			$em->persist($event_log);
			$em->flush();
		}
		$paps = $pap = null;
	}
	
	/**
	 * Clear all events match the date created plush retention days 
	 */
	private function cleanExpiredEvents()
	{
		$count = 0;
		$em = $this->getContainer()->get('doctrine')->getManager();
		$logs = $em->getRepository('DocovaBundle:EventLogs')->findAll();
		if (!empty($logs) && count($logs) > 0) 
		{
			foreach ($logs as $log) {
				if (strtotime($log->getEventDate()->modify("+{$this->global_settings->getLogRetention()} day")->format('m/d/Y')) <= strtotime(date('m/d/Y'))) {
					$em->remove($log);
					$count++;
				}
			}
			$em->flush();
		
			if ($count > 0) {
				$event_log = new EventLogs();
				$event_log->setAgentName('Daily Cleanup Tasks');
				$event_log->setEventDate(new \DateTime());
				$event_log->setServer($this->getContainer()->get('router')->getContext()->getHost());
				$event_log->setDetails("permanently deleted $count expired event log(s).");
				$em->persist($event_log);
				$em->flush();
			}
		}
	}
	
	/**
	 * Remove a document and all stub documents (attachments, bookmarks, favorites, ...)
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Doctrine\ORM\EntityManager $em
	 */
	private function removeDocument($document, $em)
	{
		if (true === $this->transferLogs($document, $em) ) {
			if (!$document->getParentDocument())
			{
				$new_parent = $em->getRepository('DocovaBundle:Documents')->isParentDocument($document->getId());
				if (!empty($new_parent))
				{
					$new_parent->setParentDocument(null);
					$em->flush();
					$em->getRepository('DocovaBundle:Documents')->updateParentDocument($new_parent->getId(), $document->getId());
				}
			}
			if ($document->getAttachments()->count() > 0) {
				$this->removeAttachedFiles($document->getId());
				foreach ($document->getAttachments() as $attachment) {
					$em->remove($attachment);
				}
				$attachment = null;
			}
			if ($document->getDateValues()->count() > 0)
			{
				foreach ($document->getDateValues() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getNumericValues()->count() > 0)
			{
				foreach ($document->getNumericValues() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getNameValues()->count() > 0)
			{
				foreach ($document->getNameValues() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getGroupValues()->count() > 0)
			{
				foreach ($document->getGroupValues() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getTextValues()->count() > 0) 
			{
				foreach ($document->getTextValues() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getBookmarks()->count() > 0) 
			{
				foreach ($document->getBookmarks() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getDocSteps()->count() > 0)
			{
				foreach ($document->getDocSteps() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getComments()->count() > 0)
			{
				foreach ($document->getComments() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getActivities()->count() > 0)
			{
				foreach ($document->getActivities() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getLogs()->count() > 0)
			{
				foreach ($document->getLogs() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getFavorites()->count() > 0)
			{
				foreach ($document->getFavorites() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getDiscussion()->count() > 0) 
			{
				foreach ($document->getDiscussion() as $value) {
					$em->remove($value);
				}
			}
			if ($document->getReviewItems()->count() > 0) 
			{
				foreach ($document->getReviewItems() as $value) {
					$em->remove($value);
				}
			}
			$document->clearReviewers();
			$value = null;

			$customACL = new CustomACL($this->getContainer());
			$customACL->removeAllMasks($document);
			$customACL = null;
		
			$related_docs = $em->getRepository('DocovaBundle:RelatedDocuments')->findLinkedDocuments($document->getId());
			foreach ($related_docs as $rd)
			{
				if ($rd->getParentDoc()->getId() === $document->getId()) {
					$em->remove($rd);
				}
				elseif ($rd->getRelatedDoc()->getId() === $document->getId())
				{
					$rd->setRelatedDoc(null);
				}
			}
			$em->remove($document);
			$em->flush();
			
			$this::$trashed_count++;
		}
	}
	
	/**
	 * Remove a folder and all sub folders and documents
	 * 
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @param \Doctrine\ORM\EntityManager $em
	 */
	private function removeFolder($folder, $em)
	{
		if ($folder->getChildren()->count() > 0) 
		{
			foreach ($folder->getChildren() as $subfolder) {
				$this->removeFolder($subfolder, $em);
			}
		}
		
		if (true === $this->transferLogs($folder, $em)) {
			if ($folder->getBookmarks()->count() > 0) 
			{
				foreach ($folder->getBookmarks() as $value) {
					$em->remove($value);
				}
			}
			if ($folder->getLogs()->count() > 0)
			{
				foreach ($folder->getLogs() as $value) {
					$em->remove($value);
				}
			}
			if ($folder->getFavorites()->count() > 0)
			{
				foreach ($folder->getFavorites() as $value) {
					$em->remove($value);
				}
			}
			if ($folder->getSynchUsers()->count() > 0)
			{
				foreach ($folder->getSynchUsers() as $value) {
					$em->remove($value);
				}
			}
			$value = null;

			$folder->clearApplicableDocTypes();
			if ($folder->getDocuments()->count() > 0) 
			{
				foreach ($folder->getDocuments() as $document) {
					$this->removeDocument($document, $em);
				}
			}
			$em->remove($folder);
			$em->flush();
			
			$this::$trashed_count++;
		}
	}
	
	/**
	 * Remove generated folder for a document and all its contents
	 * 
	 * @param integer $doc_id
	 */
	private function removeAttachedFiles($doc_id)
	{
		$this->ATTACHMENT_PATH = getcwd().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
		if (is_dir($this->ATTACHMENT_PATH.DIRECTORY_SEPARATOR.$doc_id)) 
		{
			foreach (glob($this->ATTACHMENT_PATH.DIRECTORY_SEPARATOR.$doc_id.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($this->ATTACHMENT_PATH.DIRECTORY_SEPARATOR.$doc_id);
		}
	}
	
	/**
	 * Transfer all logs for the deleted document to the trashed log table
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Doctrine\ORM\EntityManager $em
	 * @return boolean
	 */
	private function transferLogs($document, $em)
	{
		if ($document->getLogs()->count() > 0) 
		{
			try {
				foreach ($document->getLogs() as $log) {
					$trashed_log = new TrashedLogs();
					if ($document instanceof \Docova\DocovaBundle\Entity\Documents) {
						$trashed_log->setLogType(true);
						$trashed_log->setOwnerTitle($document->getDocTitle());
						$trashed_log->setParentFolder($document->getFolder()->getFolderName());
						$trashed_log->setParentLibrary($document->getFolder()->getLibrary());
					}
					elseif ($document instanceof \Docova\DocovaBundle\Entity\Folders) {
						$trashed_log->setLogType(false);
						$trashed_log->setOwnerTitle($document->getFolderName());
						$trashed_log->setParentFolder($document->getParentfolder() ? $document->getParentfolder()->getFolderName() : 'ROOT');
						$trashed_log->setParentLibrary($document->getLibrary());
					}
					$trashed_log->setDateCreated(new \DateTime());
					$trashed_log->setLogDetails($log->getLogDetails());
					
					$em->persist($trashed_log);
					$em->flush();
				}
				$trashed_log = new TrashedLogs();
				if ($document instanceof \Docova\DocovaBundle\Entity\Documents) 
				{
					$trashed_log->setLogType(true);
					$trashed_log->setOwnerTitle($document->getDocTitle());
					$trashed_log->setParentFolder($document->getFolder()->getFolderName());
					$trashed_log->setParentLibrary($document->getFolder()->getLibrary());
					$trashed_log->setLogDetails('Deleted document from library.');
				}
				else {
					$trashed_log->setLogType(false);
					$trashed_log->setOwnerTitle($document->getFolderName());
					$trashed_log->setParentFolder($document->getParentfolder() ? $document->getParentfolder()->getFolderName() : 'ROOT');
					$trashed_log->setParentLibrary($document->getLibrary());
					$trashed_log->setLogDetails('Deleted folder from library.');
				}
				$trashed_log->setDateCreated(new \DateTime());
				$em->persist($trashed_log);
				$em->flush();
				$trashed_log = null;
				return true;
			}
			catch (\Exception $e) {
				$trashed_log = null;
				//May need to log the error
				return false;
			}
		}
		
		return true;
	}
}