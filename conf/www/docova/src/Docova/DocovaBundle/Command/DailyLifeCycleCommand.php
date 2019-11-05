<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Controller\MailController;
use Docova\DocovaBundle\Entity\EventLogs;
use Docova\DocovaBundle\Entity\ReviewItems;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Controller\UtilityHelperTeitr;
use Docova\DocovaBundle\Controller\Miscellaneous;

/**
 * @author javad rahimi
 * Daily Life Cycle command generated to check the notification cycle through App Settings
 * And send the the proper notification to receipients, base on Workflow Step Actions
 * 
 */
class DailyLifeCycleCommand extends ContainerAwareCommand 
{
	private $global_settings;
	private $docova;
	
	protected function configure()
	{
		$this
			->setName('docova:dailylifecycle')
			->setDescription('Daily Life Cycle')
			->addArgument('deactive', InputArgument::OPTIONAL, 'Enter deactive agent name, please');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$count = 0;
		$em = $this->getContainer()->get('doctrine')->getManager();
		$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
//		if ($this->global_settings->getRunningServer() == $this->getContainer()->get('router')->getContext()->getHost()) 
//		{
			$time_to_run = $this->global_settings->getNotificationTime() ? $this->global_settings->getNotificationTime()->format('h:i:s A') : '06:00:00 AM';
			if (true === $this->isTimeToRun($time_to_run)) 
			{
				$wf_docs = $em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getStartedPendingWfSteps();
				if (!empty($wf_docs) && count($wf_docs) > 0) 
				{
					foreach ($wf_docs as $step) {
						if ($step->getActions()->count() > 0) {
							$due_days = 0;
							foreach ($step->getActions() as $action) {
								if ($action->getActionType() == 6) {
									$due_days = $action->getDueDays();
									$temp = clone $step->getDateStarted();
									$temp->modify("+{$action->getDueDays()} days");
									$due_date = $temp->format('m/d/Y');
									if (strtotime(date('m/d/Y')) - strtotime($due_date) > 0) 
									{
										if (true == $this->sendNotificationTo($action, $step, $temp)) {
											$count++;
											$output->writeln('Notification mail has been sent to selected participants of document "'. $step->getDocument()->getDocTitle().'"');
										}
									}
									$temp = null;
									break;
								}
							}

							if (!empty($due_days)) 
							{
								foreach ($step->getActions() as $action) {
									if ($action->getActionType() == 7) {
										$temp = clone $step->getDateStarted();
										$temp->modify('+' . ($action->getDueDays() + $due_days) .' days');
										$due_date = $temp->format('m/d/Y');
										if (strtotime(date('m/d/Y')) - strtotime($due_date) > 0) 
										{
											if (true == $this->sendNotificationTo($action, $step, $temp)) {
												$count++;
												$output->writeln('Notification (escalation) mail has been sent to selected participants of document "'. $step->getDocument()->getDocTitle().'"');
											}
										}
										$temp = null;
										$found = true;
									}
								}
							}
						}
					}
				}

				$event_log = new EventLogs();
				$event_log->setAgentName('Daily Life Cycle');
				$event_log->setEventDate(new \DateTime());
				$event_log->setServer($this->getContainer()->get('router')->getContext()->getHost());
				$event_log->setDetails("Lifecycle Tasks - has been run housekeeping. ($count doc step notification(s) sent)");
				$em->persist($event_log);
				$em->flush();

				$this->processReviewPolicies();
				$this->ProcessCustomReviews();
			}
//		}
	}
	
	/**
	 *  Review policies process
	 */
	private function processReviewPolicies()
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$review_policies = $em->getRepository('DocovaBundle:ReviewPolicies')->findBy(array('policyStatus' => true), array('policyPriority' => 'DESC'));
		if (!empty($review_policies) && count($review_policies) > 0)
		{
			$this->docova = $this->getContainer()->get('docova.objectmodel');
			foreach ($review_policies as $rp) {
				$doc_status = $libraries = $doctypes = array();
				if ($rp->getDocumentStatus() > 1) {
					$doc_status[] = 1;
				}
				if ($rp->getDocumentStatus() == 1 || $rp->getDocumentStatus() == 3) {
					$doc_status[] = 0;
				}
				
				if ($rp->getLibraries()->count() > 0) {
					foreach ($rp->getLibraries() as $library) {
						$libraries[] = $library->getId();
					}
				}
				
				if ($rp->getDocumentTypes()->count() > 0) {
					foreach ($rp->getDocumentTypes() as $dtype) {
						$doctypes[] = $dtype->getId();
					}
				}
				$custom_query = trim($rp->getReviewCustomFormula()) ? trim($rp->getReviewCustomFormula()) : '';
				$documents = $em->getRepository('DocovaBundle:Documents')->getDocumentsForReview($doc_status, $libraries, $doctypes, $custom_query);
				if (!empty($documents) && count($documents) > 0) 
				{
					$rw = 0;
					foreach ($documents as $document)
					{
						if (true === $this->isTimeForReview($em, $document, $rp))
						{
							$em->refresh($document);
							$this->processReviewActions($document, $rp);
							$document->setHasPendingReview(true);
							$em->flush();

							$doc_log = new Miscellaneous($this->getContainer());
							$doc_log->createDocumentLog($em, 'UPDATE', $document, "Document scheduled for review on {$document->getNextReviewDate()->format('m/d/Y')} per policy {$rp->getPolicyName()}.");
							$doc_log = null;
							$rw++;
						}
					}
					
					if (!empty($rw)) 
					{
						$event_log = new EventLogs();
						$event_log->setAgentName('Daily Life Cycle');
						$event_log->setEventDate(new \DateTime());
						$event_log->setServer($this->getContainer()->get('router')->getContext()->getHost());
						$event_log->setDetails("DailyLifecycleTasks processed $rw document(s) pending review as per {$rp->getPolicyName()} policy.");
						$em->persist($event_log);
						$em->flush();
						$event_log = null;
					}
				}
			}
		}
	}
	
	/**
	 * Document review process per custom review settings 
	 */
	private function ProcessCustomReviews()
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$documents = $em->getRepository('DocovaBundle:Documents')->findBy(array('Review_Type' => 'C', 'Has_Pending_Review' => false, 'Trash' => false, 'Status_No' => 1));
		if (!empty($documents) && count($documents) > 0) 
		{
			$rw = 0;
			foreach ($documents as $document)
			{
				if ($this->isTimeForReview($em, $document)) 
				{
					$em->refresh($document);
					$this->processReviewActions($document);
					$document->setHasPendingReview(true);
					$em->flush();

					$doc_log = new Miscellaneous($this->getContainer());
					$doc_log->createDocumentLog($em, 'UPDATE', $document, "Document scheduled for review on {$document->getNextReviewDate()->format('m/d/Y')} per custom review settings.");
					$doc_log = null;
					$rw++;
				}
			}
					
			if (!empty($rw)) 
			{
				$event_log = new EventLogs();
				$event_log->setAgentName('Daily Life Cycle');
				$event_log->setEventDate(new \DateTime());
				$event_log->setServer($this->getContainer()->get('router')->getContext()->getHost());
				$event_log->setDetails("DailyLifecycleTasks processed $rw document(s) pending review as per custom review settings.");
				$em->persist($event_log);
				$em->flush();
				$event_log = null;
			}
		}
	}
	
	/**
	 * Check if it's time to review the policy
	 * 
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\ReviewPolicies $review_policy [optional()]
	 * @return boolean
	 */
	private function isTimeForReview($em, $document, $review_policy = null)
	{
		if (!empty($review_policy)) 
		{
			$this->docova->setDocument($document);
			$increment_type = $review_policy->getReviewPeriodOption();
			$date_increment = $review_policy->getReviewPeriod();
			
			if ($document->getLastReviewDate())
			{
				$start_date = $document->getLastReviewDate();
			}
			else {
				if ($review_policy->getReviewDateSelect() == 'custom') {
					if (!method_exists($document, 'get'.$review_policy->getCustomReviewDateSelect())) {
						$start_date = $this->docova->getFieldValue($review_policy->getCustomReviewDateSelect());
					}
					else {
						$start_date = call_user_func(array($document, 'get'.$review_policy->getCustomReviewDateSelect()));
					}
				}
				else {
					if (method_exists($document, 'get'.$review_policy->getReviewDateSelect())) {
						$start_date = call_user_func(array($document, 'get'.$review_policy->getReviewDateSelect()));
					}
				}
			}
			
			$advance_period	 = $review_policy->getReviewAdvance();
			$grace_period	 = $review_policy->getReviewGracePeriod();
			$reference_month = $review_policy->getReviewStartMonth();
			$reference_day	 = $review_policy->getReviewStartDay();
		}
		else {
			$increment_type = $document->getReviewPeriodOption();
			$date_increment = $document->getReviewPeriod();
			$advance_period = 2;
			$grace_period	= 3;
			$reference_month = $document->getReviewStartMonth();
			$reference_day	 = $document->getReviewStartDay();

			if (method_exists($document, 'get'.$document->getReviewDateSelect())) {
				$start_date = call_user_func(array($document, 'get'.$document->getReviewDateSelect()));
			}
		}

		switch ($increment_type) {
			case 'D':
				$date_increment = "$date_increment days";
				break;
			case 'W':
				$date_increment = ($date_increment * 7).' days';
				break;
			case 'M':
				$date_increment = "$date_increment month";
				break;
			case 'Y':
				$date_increment = "$date_increment year";
				break;
			default:
				return false;
		}
		
		if (empty($start_date)) 
		{
			$start_date = $document->getLastReviewDate();
		}
		if (!($start_date instanceof \DateTime)) {
			$format = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $this->global_settings->getDefaultDateFormat());
			$start_date = date_create_from_format($format, $start_date);
		}
		
		if (empty($start_date) || !($start_date instanceof \DateTime)) 
		{
			return false;
		}
			
		$today = new \DateTime();
		if (!$document->getLastReviewDate() && $document->getReleasedDate() && (int)($document->getReleasedDate()->diff($today)->format('%r%a')) <= $grace_period)
		{
			$today = $start_date = null;
			return false;
		}
		
		if (!empty($advance_period)) 
		{
			$start_date->modify("-$advance_period days");
		}
		
		$temp = clone $start_date;
		$temp->modify("+$date_increment");
		if ((int)$today->diff($temp)->format('%r%a') < 0) 
		{
			if (!empty($advance_period)) 
			{
				$start_date->modify("+$advance_period days");
			}
			$start_date->modify("+$date_increment");
			$document->setNextReviewDate($start_date);
			$em->flush();
			$temp = $start_date = $today = null;
			return true;
		}
		$temp = null;

		return false;
	}
	
	/**
	 * Generate a Review Item base on the policy for the document
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\ReviewPolicies $review_policy [optional()]
	 */
	private function processReviewActions($document, $review_policy = null)
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$review_item = new ReviewItems();
		$review_item->setTitle('Review');
		$review_item->setStatus('Pending');
		$review_item->setStartDate($document->getNextReviewDate());
		$review_item->setDocument($document);
		if (!empty($review_policy)) {
			$temp = clone $document->getNextReviewDate();
			$temp->modify("+{$review_policy->getDelayCompleteThreshold()} days");
			$review_item->setDueDate($temp);
			$review_item->setReviewPolicy($review_policy);
			$temp = null;
		}
		else {
			$temp = clone $document->getNextReviewDate();
			$temp->modify("+3 day");
			$review_item->setDueDate($temp);
			$temp = null;
		}
		$em->persist($review_item);
		$em->flush();

		$context = $this->getContainer()->get('router')->getContext();
		$context->setHost($this->global_settings->getRunningServer());
		$context->setScheme($this->global_settings->getSslEnabled() ? 'https' : 'http');
		$tempport = $this->global_settings->getHttpPort();
		if(!empty($tempport)){
		    if($this->global_settings->getSslEnabled()){
		        $context->setHttpsPort($tempport);
		    }else{
		        $context->setHttpPort($tempport);
		    }
		}
		$pathparts = explode(DIRECTORY_SEPARATOR, $this->getContainer()->get('kernel')->getRootDir());
		$dir = $pathparts[count($pathparts)-2];
		$context->setBaseUrl(DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'app.php');
		
		if (!empty($review_policy))
		{
			$approvers = array();
			if ($review_policy->getAuthorReview() === true) {
				$approvers[] = $document->getAuthor();
			}
			
			if ($review_policy->getAdditionalEditorReview() === true) {
				$custom_acl = new CustomACL($this->getContainer());
				$authors = $custom_acl->getObjectACEUsers($document, 'edit');
				foreach ($authors as $user) {
					if (false !== $userObj = $this->findUser($user)) {
						$approvers[] = $userObj;
					}
				}
				$custom_acl = $authors = $user = $userObj = null;
			}
			
			if ($review_policy->getReviewersField() && method_exists($document, 'get'.$review_policy->getReviewersField())) 
			{
				if (false !== $userObj = $this->findUser(call_user_func(array($document, 'get'.$review_policy->getReviewersField())))) {
					$approvers[] = $userObj;
				}
				$userObj = null;
			}
			
			if ($review_policy->getReviewers()->count() > 0) 
			{
				foreach ($review_policy->getReviewers() as $rw) {
					$approvers[] = $rw;
				}
				$rw = null;
			}
			
			if (!empty($approvers) && count($approvers) > 0) 
			{
				$approvers = array_unique($approvers, SORT_REGULAR);
				foreach ($approvers as $userObj) {
					$review_item->addPendingReviewers($userObj);
				}
				$em->flush();
			}
			
			if ($review_policy->getEnableActivateMsg()) 
			{
				$recipients = array();
				if ($review_policy->getActivateNotifyParticipants() === true && !empty($approvers)) 
				{
					foreach ($approvers as $userObj) 
					{
						$recipients[] = $userObj->getUserMail();
					}
					$userObj = null;
				}
	
				if ($review_policy->getActivateNotifyField() && method_exists($document, 'get'.$review_policy->getActivateNotifyField()))
				{
					if (false !== $userObj = $this->findUser(call_user_func(array($document, 'get'.$review_policy->getActivateNotifyField())))) {
						$recipients[] = $userObj->getUserMail();
					}
					$userObj = null;
				}
				
				if ($review_policy->getActivateNotifyList()->count() > 0) 
				{
					foreach ($review_policy->getActivateNotifyList() as $ntfl) {
						$recipients[] = $ntfl->getUserMail();
					}
				}
			
				if (!empty($recipients) && count($recipients) > 0) 
				{
					$recipients = array_unique($recipients);
					sort($recipients);
					$sysmessage = $review_policy->getEnableActivateMsg();
					$message = array(
						'subject' => MailController::parseTokens($this->getContainer(), $sysmessage->getSubjectLine(), $sysmessage->getLinkType(), $document),
						'content' => MailController::parseTokens($this->getContainer(), $sysmessage->getMessageContent(), $sysmessage->getLinkType(), $document),
						'senders' => $sysmessage->getSenders(),
						'groups' => $sysmessage->getSenderGroups()
					);
					if (true === MailController::parseMessageAndSend($message, $this->getContainer(), $recipients))
					{
						$recipients = $sysmessage = $message = null;
						return true;
					}
				}
			}
		}
		else {
			$recipients = $approvers = array();
			if ($document->getAuthorReview()) {
				$approvers[] = $document->getAuthor();
				$recipients[] = $document->getAuthor()->getUserMail();
			}
			
			if ($document->getReviewers()->count() > 0) 
			{
				foreach ($document->getReviewers() as $reviewer) {
					$recipients[] = $reviewer->getUserMail();
					$approvers[] = $reviewer;
				}
			}

			if (!empty($approvers) && count($approvers) > 0)
			{
				$approvers = array_unique($approvers, SORT_REGULAR);
				foreach ($approvers as $userObj) {
					$review_item->addPendingReviewers($userObj);
				}
				$em->flush();
			}

			if (!empty($recipients) && count($recipients) > 0) 
			{
				$recipients = array_unique($recipients);
				sort($recipients);
				$sysmessage = $em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_REVIEWACTIVATE', 'Systemic' => true));
				$message = array(
					'subject' => MailController::parseTokens($this->getContainer(), $sysmessage->getSubjectLine(), $sysmessage->getLinkType(), $document),
					'content' => MailController::parseTokens($this->getContainer(), $sysmessage->getMessageContent(), $sysmessage->getLinkType(), $document),
					'senders' => $sysmessage->getSenders(),
					'groups' => $sysmessage->getSenderGroups()
				);
				if (true === MailController::parseMessageAndSend($message, $this->getContainer(), $recipients))
				{
					$recipients = $sysmessage = $message = null;
					return true;
				}
			}
		}
	}
	
	/**
	 * Check if run time is within 60 minutes base on current time
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
	 * Send notification email to participants of the action
	 *  
	 * @param \Docova\DocovaBundle\Entity\WorkflowStepActions $step_action
	 * @param \Docova\DocovaBundle\Entity\DocumentWorkflowSteps $step
	 * @param \DateTime $dueDate
	 * @return boolean
	 */
	private function sendNotificationTo($step_action, $step, $dueDate)
	{
		$recipients = array();
		if ($step_action->getToOptions() > 0) {
			if ($step_action->getToOptions() > 1) {
				//$recipients[$step->getDocument()->getAuthor()->getUserMail()] = $step->getDocument()->getAuthor()->getUserNameDnAbbreviated();
				$recipients[] = $step->getDocument()->getAuthor()->getUserMail();
			}
			if ($step_action->getToOptions() == 1 || $step_action->getToOptions() == 3) {
				//$recipients[$step->getDocument()->getAuthor()->getUserMail()] = $step->getDocument()->getAuthor()->getUserNameDnAbbreviated();
				foreach ($step->getAssignee() as $a) {
					$recipients[] = $a->getAssignee()->getUserMail();
				}
			}
			if ($step_action->getSendTo()->count() > 0) 
			{
				foreach ($step_action->getSendTo() as $send_to) {
					//$recipients[$send_to->getUserMail()] = $send_to->getUserNameDnAbbreviated();
					$recipients[] = $send_to->getUserMail();
				}
			}
			if (count($step_action->getSenderGroups()) > 0) 
			{
				foreach ($step_action->getSenderGroups() as $group) {
					$members = $this->fetchGroupMembers($group);
					if (!empty($members) && $members->count() > 0) 
					{
						foreach ($members as $send_to) {
							//$recipients[$send_to->getUserMail()] = $send_to->getUserNameDnAbbreviated();
							$recipients[] = $send_to->getUserMail();
						}
					}
				}
			}
			
			$recipients = array_unique($recipients);

			if (!empty($recipients) && count($recipients) > 0) 
			{
				$context = $this->getContainer()->get('router')->getContext();
				$context->setHost($this->global_settings->getRunningServer());
				$context->setScheme($this->global_settings->getSslEnabled() ? 'https' : 'http');
				$tempport = $this->global_settings->getHttpPort();
				if(!empty($tempport)){
				    if($this->global_settings->getSslEnabled()){
				        $context->setHttpsPort($tempport);
				    }else{
				        $context->setHttpPort($tempport);
				    }
				}
				$pathparts = explode(DIRECTORY_SEPARATOR, $this->getContainer()->get('kernel')->getRootDir());
				$dir = $pathparts[count($pathparts)-2];
				$context->setBaseUrl(DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'app.php');
				
				$sysmessage = $step_action->getMessage();
				$message = array(
					'subject' => MailController::parseTokens($this->getContainer(), $sysmessage->getSubjectLine(), $sysmessage->getLinkType(), $step->getDocument(), array('dueDate' => $dueDate)),
					'content' => MailController::parseTokens($this->getContainer(), $sysmessage->getMessageContent(), $sysmessage->getLinkType(), $step->getDocument(), array('dueDate' => $dueDate)),
					'senders' => $sysmessage->getSenders(),
					'groups' => $sysmessage->getSenderGroups()
				);
				
				if (true === MailController::parseMessageAndSend($message, $this->getContainer(), $recipients))
				{
					$recipients = $context = $sysmessage = $message = null;
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Find a user info in Database
	 *
	 * @param string $username
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUser($username, $search_userid = false)
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$utilHelper= new UtilityHelperTeitr($this->global_settings, $this->getContainer());
		return $utilHelper->findUserAndCreate($username, false, $em, $search_userid);
	}
	
	/**
	 * Fetch group members
	 * 
	 * @param string $grouname
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $findme
	 * @return Array|boolean
	 */
	private function fetchGroupMembers($grouname, $findme = null)
	{
		$em = $this->getContainer()->get('doctrine')->getManager();
		$type = false !== strpos($grouname, '/DOCOVA') ? true : false;
		$name = str_replace('/DOCOVA', '', $grouname);
		$role = $em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Display_Name' => $name, 'Group_Type' => $type));
		if (!empty($role)) {
			if (empty($findme)) 
			{
				return $role->getRoleUsers();
			}
			else {
				if ($role->getRoleUsers()->count() > 0) {
					foreach ($role->getRoleUsers() as $user) {
						if ($user->getUserNameDnAbbreviated() === $findme->getUserNameDnAbbreviated()) 
						{
							$em = $role = $user = null;
							return true;
						}
					}
				}
				return false;
			}
		}
		
		return false;
	}
}