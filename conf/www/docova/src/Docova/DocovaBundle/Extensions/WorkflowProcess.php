<?php

namespace Docova\DocovaBundle\Extensions;

use Docova\DocovaBundle\Controller\MailController;
use Docova\DocovaBundle\Entity\DocumentComments;
use Docova\DocovaBundle\Controller\Miscellaneous;
use Docova\DocovaBundle\Controller\UtilityHelperTeitr;
use Docova\DocovaBundle\Entity\DocumentWorkflowSteps;
use Docova\DocovaBundle\Entity\WorkflowAssignee;
use Docova\DocovaBundle\Entity\DocWorkflowStepActions;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Docova\DocovaBundle\Entity\WorkflowCompletedBy;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Document workflow process including workflow services
 * either for applications or libraries
 * @author javad_rahimi
 */
class WorkflowProcess 
{
	protected $_docova;
	protected $_em;
	protected $_container;
	protected $_user;
	protected $_gs;
	
	public function __construct(Docova $docova_obj = null)
	{
		if (!empty($docova_obj)) {
			$this->_docova = $docova_obj;
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_docova = $docova;
			}
			else {
				throw new \Exception('Workflow Process contstruction failed, Docova service not available.');
			}
		}
		$this->_em = $this->_docova->getManager();
		$this->_container = $this->_docova->getContainer();
	}
	
	/**
	 * Set current user
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 */
	public function setUser($user)
	{
		$this->_user = $user;
	}
	
	/**
	 * Set _gs
	 * 
	 * @param \Docova\DocovaBundle\Entity\GlobalSettings $gs
	 */
	public function setGs($gs)
	{
		$this->_gs = $gs;
	}
	
	/**
	 * Release document (if versioning is enabled creates a new version; discard previous drafts)
	 *
	 * @param \DOMDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $is_workflow
	 * @return boolean
	 */
	public function releaseDocument($post_xml, $document, $is_workflow = false)
	{
		try {
			$docsteps = $document->getDocSteps();
			$doctype = $document->getDocType() ? $document->getDocType() : $document->getAppForm()->getFormProperties();
			if (!empty($docsteps) && $docsteps->count())
			{
				foreach ($docsteps as $step)
				{
					if ($is_workflow === true && $step->getStepType() == 4) {
						$this->sendWfNotificationIfRequired($step, 2);
					}
					if ($step->getActions() && $step->getActions()->count() > 0) {
						foreach ($step->getActions() as $action) {
							$this->_em->remove($action);
						}
					}
					if ($step->getAssignee()->count())
					{
						foreach ($step->getAssignee() as $assignee) {
							$this->_em->remove($assignee);
						}
					}
					if ($step->getCompletedBy()->count())
					{
						foreach ($step->getCompletedBy() as $completor) {
							$this->_em->remove($completor);
						}
					}
					$this->_em->remove($step);
					$this->_em->flush();
				}
			}
			$document->setDocStatus($doctype->getFinalStatus());
			$document->setStatusNo(1);
			$document->setReleasedBy($this->_user);
			$document->setReleasedDate(new \DateTime());
			$document->setDateModified(new \DateTime());
			$document->setModifier($this->_user);
			if (!empty($post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue))
			{
				$comment = new DocumentComments();
				$comment->setComment($post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue);
				$comment->setCreatedBy($this->_user);
				$comment->setDateCreated(new \DateTime());
				$comment->setDocument($document);
				$this->_em->persist($comment);
				$comment = null;
			}
			$this->_em->flush();
	
			if ($doctype->getEnableVersions() && !empty($post_xml->getElementsByTagName('Version')->item(0)->nodeValue))
			{
				$new_version = explode('.', $post_xml->getElementsByTagName('Version')->item(0)->nodeValue);
				$revision = $new_version[2];
				$new_version = (!empty($new_version[0]) ? $new_version[0] : '0').'.'.(!empty($new_version[1]) ? $new_version[1] : '0');
				$document->setDocVersion($new_version);
				$document->setRevision($revision);
				$this->_em->flush();
				$this->_em->refresh($document);
				
				$parentdoc = $document->getParentDocument();
				$dockey = (empty($parentdoc) ? $document->getId() : $parentdoc->getId());
				unset($parentdoc);
				$all_versions = $this->_em->getRepository('DocovaBundle:Documents')->getAllDocVersionsFromParent($dockey);
				if (!empty($all_versions) && count($all_versions) > 0)
				{
					$log_obj = new Miscellaneous($this->_container);
					$status_list = array($doctype->getFinalStatus(), $doctype->getDiscardedStatus(), $doctype->getSupersededStatus(), $doctype->getDeletedStatus(), $doctype->getArchivedStatus());
					for ($x = count($all_versions) - 1; $all_versions[$x]->getId() != $document->getId() && $x >= 0; $x--)
					{
						if ($all_versions[$x]->getDocStatus() == $doctype->getInitialStatus() || !in_array($all_versions[$x]->getDocStatus(), $status_list)) {
							$all_versions[$x]->setDocStatus($doctype->getDiscardedStatus());
							$all_versions[$x]->setStatusNo(5);
							if ($all_versions[$x]->getDocSteps() && $all_versions[$x]->getDocSteps()->count() > 0)
							{
								foreach ($all_versions[$x]->getDocSteps() as $step) {
									$this->_em->remove($step);
								}
							}
							$this->_em->flush();
							$this->reindexDocumnetInView($all_versions[$x]);
							$log_obj->createDocumentLog($this->_em, 'UPDATE', $all_versions[$x], 'Discarded by version '.$post_xml->getElementsByTagName('Version')->item(0)->nodeValue);
						}
					}
	
					for ($x = 0; $x < count($all_versions); $x++)
					{
						if ($all_versions[$x]->getId() != $document->getId())
						{
							if ($all_versions[$x]->getDocStatus() == $doctype->getFinalStatus()) {
								$all_versions[$x]->setDocStatus($doctype->getSupersededStatus());
								$all_versions[$x]->setStatusNo(2);
								$this->_em->flush();
								$this->reindexDocumnetInView($all_versions[$x]);
								$log_obj->createDocumentLog($this->_em, 'UPDATE', $all_versions[$x], 'Superseded by version '.$post_xml->getElementsByTagName('Version')->item(0)->nodeValue);
							}
						}
					}
				}
				$log_obj = $post_xml = null;
			}
			$this->reindexDocumnetInView($document);
			return true;
		}
		catch (\Exception $e) {
			var_dump($e->getMessage());
			return false;
		}
	}
	
	/**
	 * Send workflow step notification message if is required
	 * 
	 * @param \Docova\DocovaBundle\Entity\DocumentWorkflowSteps $step
	 * @param integer $type
	 * @param string $comment
	 * @param array $recipients
	 * @return boolean
	 */
	public function sendWfNotificationIfRequired($step, $type, $comment = '', $new_recipients = [])
	{
		if ($step->getActions()->count())
		{
			$step_actions = $step->getActions();
			foreach ($step_actions as $action)
			{
				$recipients = !empty($new_recipients) ? $new_recipients : array();
				if ($action->getActionType() == $type && $action->getSendMessage() && ($action->getToOptions() > 0 || $action->getSendTo()->count() > 0 || count($action->getSenderGroups()) > 0)) {
					if ($action->getToOptions() > 1 && empty($new_recipients)) {
						$recipients[] = $step->getDocument()->getAuthor()->getUserMail();
					}
					if (empty($new_recipients) && ($action->getToOptions() == 1 || $action->getToOptions() == 3)) {
						foreach ($step->getAssignee() as $user) {
							$recipients[] = $user->getAssignee()->getUserMail();
						}
					}
					if (empty($new_recipients) && $action->getSendTo()->count() > 0)
					{
						foreach ($action->getSendTo() as $send_to) {
							$recipients[] = $send_to->getUserMail();
						}
					}
					if (empty($new_recipients) && count($action->getSenderGroups()) > 0)
					{
						foreach ($action->getSenderGroups() as $group) {
							$members = $this->fetchGroupMembers($group, true);
							if (!empty($members) && $members->count() > 0)
							{
								foreach ($members as $send_to) {
									$recipients[] = $send_to->getUserMail();
								}
							}
						}
					}
	
					$recipients = array_unique($recipients);
						
					if (!empty($recipients) && count($recipients) > 0)
					{
						$sysmessage = $action->getMessage();
						$message = array(
								'subject' => MailController::parseTokens($this->_container, $sysmessage->getSubjectLine(), $sysmessage->getLinkType(), $step->getDocument(), array('usercomment' => $comment), $step),
								'content' => MailController::parseTokens($this->_container, $sysmessage->getMessageContent(), $sysmessage->getLinkType(), $step->getDocument(), array('usercomment' => $comment), $step),
								'senders' => $sysmessage->getSenders(),
								'groups' => $sysmessage->getSenderGroups()
						);
						if (true === MailController::parseMessageAndSend($message, $this->_container, $recipients))
						{
							return true;
						}
					}
				}
			}
		}
		else {
			$logger = $this->_container->get('logger');
			$logger->error('No action is set for the step.');
			$logger = null;
		}
		return false;
	}

	/**
	 * Mark the review assigned to current user as reviewed
	 *
	 * @param \DOMDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	public function wfServiceREVIEW($post_xml, $document, $return_xml = false)
	{
		try {
			if (empty($this->_user) || empty($post_xml) || !($post_xml instanceof \DOMDocument) || empty($document)) {
				throw new \Exception('Inproper arguments are passed');
			}
			$comment = trim($post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue);
			if (!empty($comment)) {
				$review_item = $this->_em->getRepository('DocovaBundle:ReviewItems')->getPendingReviewItem($document->getId(), $this->_user->getId());
				if (!empty($review_item))
				{
					$comment_obj = new DocumentComments();
					$comment_obj->setCreatedBy($this->_user);
					$comment_obj->setDateCreated(new \DateTime());
					$comment_obj->setDocument($document);
					$comment_obj->setComment($comment);
					$this->_em->persist($comment_obj);
					$comment_obj = null;
	
					$review_item->addCompletedReviewers($this->_user);
					$review_item->removePendingReviewers($this->_user);
					$this->_em->flush();
					$this->_em->refresh($review_item);
					$log_obj = new Miscellaneous($this->_container);
					$log_obj->createDocumentLog($this->_em, 'UPDATE', $document, 'Completed document review action.');

					if ($review_item->getPendingReviewers()->count() == 0)
					{
						$this->_em->remove($review_item);
						$document->setHasPendingReview(false);
						$document->setLastReviewDate(new \DateTime());
						$this->_em->flush();
	
						$log_obj->createDocumentLog($this->_em, 'UPDATE', $document, 'Closed document review.');
					}
					$log_obj = null;
				}
			}
			else {
				throw new \Exception('Review comment is missed');
			}
				
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'OK');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return true;
			}
		}
		catch (\Exception $e) {
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return false;
			}
		}
	}

	/**
	 * Create empty Document Workflow Step objects in DB for the document
	 *
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \DOMDocument $workflow_xml
	 * @param boolean $return
	 */
	public function createDocumentWorkflow($document, $workflow_xml, $return = false)
	{
		if (!empty($document) && !empty($this->_user) && !empty($workflow_xml) && $workflow_xml instanceof \DOMDocument)
		{
			foreach ($workflow_xml->getElementsByTagName('IsNew') as $index => $node)
			{
				if ($node->nodeValue == 1)
				{
					$step = $this->_em->getRepository('DocovaBundle:WorkflowSteps')->findOneBy(array('id' => $workflow_xml->getElementsByTagName('wfItemKey')->item($index)->nodeValue));
					if (!empty($step))
					{
						$assignees = $workflow_xml->getElementsByTagName('wfDispReviewerApproverList')->item($index)->nodeValue;
						$assignee_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $assignees);
						$assignees = $groups = array();
						$placeholder = "";
						for ($x = 0; $x < count($assignee_list); $x++)
						{
							$userortoken = trim($assignee_list[$x]);
							if ($userortoken)
							{
								if ($userortoken == '[Author]')
								{
									$assignees['user'][] = $this->_user;
								}
								else if ( $userortoken == '[Formula]'){
									
									$assignees['user'][] = 'Please Select';
									$placeholder .=  $placeholder == "" ? $userortoken : ",".$userortoken;
								}
								elseif (false !== $user = $this->findUserAndCreate($userortoken))
								{
									$assignees['user'][] = $user;
								}
								else {
									$members = $this->fetchGroupMembers($userortoken, true);
									if (!empty($members) && $members->count() > 0)
									{
										$groups[] = $userortoken;
										foreach ($members as $user) {
											$assignees['group'][] = $user;
										}
									}

									if ( empty($assignees))
									{
										$assignees['user'][] = 'Please Select';
										$placeholder .=  $placeholder == "" ? $userortoken : ",".$userortoken;
									}
								}
							}
							else {
								$assignees['user'][] = 'Please Select';
							}
						}
						if ((!empty($assignees['user']) && count($assignees['user'])) || (!empty($assignees['group']) && count($assignees['group'])))
						{
							$doc_step = new DocumentWorkflowSteps();
							$doc_step->setDocument($document);
							$doc_step->setStatus('Pending');
							$doc_step->setWorkflowName($step->getWorkflow()->getWorkflowName());
							$doc_step->setAuthorCustomization($step->getWorkflow()->getAuthorCustomization());
							$doc_step->setBypassRleasing($step->getWorkflow()->getBypassRleasing());
							$doc_step->setRuntimeJSON($step->getRuntimeJSON());
							$tempval = $workflow_xml->getElementsByTagName('wfTitle')->item($index)->nodeValue;
							$doc_step->setStepName(!empty($tempval) ? $tempval : $step->getStepName());
							
							$tempnum = $step->getStepType();
							//dont allow changing of start or end step type
							if($tempnum !== 1 && $tempnum !== 4){
    								$tempval = $workflow_xml->getElementsByTagName('wfAction')->item($index)->nodeValue;
       								if ($tempval == "Review"){
    							     		$tempnum = 2;
    								}else if ($tempval == "Approve"){
    							    		$tempnum = 3;
    								}
							}
							$doc_step->setStepType($tempnum);
							
							$tempval = $workflow_xml->getElementsByTagName('wfDocStatus')->item($index)->nodeValue;
							$doc_step->setDocStatus(!empty($tempval) ? $tempval : $step->getDocStatus());
							
							$doc_step->setPosition($index);
							
							$tempval = $workflow_xml->getElementsByTagName('wfType')->item($index)->nodeValue;
							$tempnum = 2;
							if(empty($tempval)){
							    $tempnum = $step->getDistribution();
							}else if($tempval == "Serial"){
							    $tempnum = 1;
							}else if ($tempval == "Parallel"){
							    $tempnum = 2;
							}
							$doc_step->setDistribution($tempnum);
							
							$doc_step->setParticipants($step->getParticipants());
							$doc_step->setCompleteOn($step->getCompleteBy());
							$doc_step->setOptionalComments($step->getOptionalComments());
							$doc_step->setApproverEdit($step->getApproverEdit());
							$doc_step->setHideReading($step->getHideReading());
							$doc_step->setHideEditing($step->getHideEditing());
							$doc_step->setHideCustom($step->getHideCustom());
							$doc_step->setCustomReviewButtonLabel($step->getCustomReviewButtonLabel());
							$doc_step->setCustomApproveButtonLabel($step->getCustomApproveButtonLabel());
							$doc_step->setCustomDeclineButtonLabel($step->getCustomDeclineButtonLabel());
							$doc_step->setCustomReleaseButtonLabel($step->getCustomReleaseButtonLabel());
							$doc_step->setOtherParticipantGroups($step->getOtherParticipantGroups());

							if ( !empty ( $placeholder)){
								$doc_step->setParticipantTokens( $placeholder);
							}else{
								$doc_step->setParticipantTokens( null);
							}


							$formula = $workflow_xml->getElementsByTagName('wfParticipantFormula')->item($index) ? $workflow_xml->getElementsByTagName('wfParticipantFormula')->item($index)->nodeValue : '';


							if ( !empty ( $formula )){
								$doc_step->setParticipantFormula( $formula);
							}

							$doc_step->setAssigneeGroup($groups);
							if ($workflow_xml->getElementsByTagName('wfOrder')->item($index)->nodeValue == 0) {
								$doc_step->setDateStarted(new \DateTime());
								$doc_step->setIsCurrentStep(true);
							}
							if ($step->getOtherParticipant()->count() > 0)
							{
								foreach ($step->getOtherParticipant() as $participant) {
									$doc_step->addOtherParticipant($participant);
								}
							}
							$participant = null;
							$this->_em->persist($doc_step);
							if (!empty($assignees['user']))
							{
								foreach ($assignees['user'] as $user) {
									if ($user !== 'Please Select')
									{
										$assignee = new WorkflowAssignee();
										$assignee->setAssignee($user);
										$assignee->setDocWorkflowStep($doc_step);
										$assignee->setGroupMember(false);
										$this->_em->persist($assignee);
									}
								}
							}
							else {
								foreach ($assignees['group'] as $user) {
									$assignee = new WorkflowAssignee();
									$assignee->setAssignee($user);
									$assignee->setDocWorkflowStep($doc_step);
									$assignee->setGroupMember(true);
									$this->_em->persist($assignee);
								}
							}
							if ($step->getActions()->count() > 0)
							{
								foreach ($step->getActions() as $action) {
									$doc_step_action = new DocWorkflowStepActions();
									$doc_step_action->setActionType($action->getActionType());
									$doc_step_action->setDeclineAction($action->getDeclineAction());
									$doc_step_action->setDueDays($action->getDueDays());
									$doc_step_action->setMessage($action->getMessage());
									$doc_step_action->setSendMessage($action->getSendMessage());
									$doc_step_action->setToOptions($action->getToOptions());
									$doc_step_action->setSenderGroups($action->getSenderGroups());
									$doc_step_action->setStep($doc_step);
									if ($action->getBackTrackStep()) {
										$doc_step_action->setBackTrackStep($action->getBackTrackStep()->getStepName());
									}
									if ($action->getSendTo()->count() > 0) {
										foreach ($action->getSendTo() as $sendTo) {
											$doc_step_action->addSendTo($sendTo);
										}
									}
									$this->_em->persist($doc_step_action);
								}
							}
							$this->_em->flush();
							$doc_step_action = $action = $sendTo = null;
							if (!empty($return)) {
								return $doc_step;
							}
							$doc_step = null;
						}
					}
					$step = null;
				}
			}
		}
	}

	/**
	 * Workflow process handler
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $xml_response
	 * @return string|null
	 */
	public function workflowItemProcess($post_xml, $document, $xml_response = false)
	{
		$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
		if (!empty($this->_user) && !empty($post_xml) && !empty($document) && method_exists($this, 'wfService'.$action))
		{
			if ($action === 'UPDATE') {
				$output = call_user_func(array($this, 'wfServiceUPDATE'), $post_xml, $document, $xml_response, true);;
			}
			else {
				$output = call_user_func(array($this, 'wfService'.$action), $post_xml, $document, $xml_response);
			}
		}
		else {
			if ($xml_response === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				$output = $result_xml->saveXML();
				unset($result_xml);
			}
			else {
				$output = true;
			}
		}
		return $output;
	}


	/**
	 * Update workflow step(s) of a document
	 *
	 * @param \DOMDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceUPDATE($post_xml, $document, $return_xml = false, $force_reindex = false)
	{
		$modified = false;
		foreach ($post_xml->getElementsByTagName('Modified') as $index => $step)
		{
			if ($post_xml->getElementsByTagName('IsNew')->item($index)->nodeValue == 1) {
				try {
					$newXML = new \DOMDocument();
					$node = $post_xml->getElementsByTagName('Document')->item($index);
					$root = $newXML->importNode($node, true);
					$newXML->appendChild($root);
					$docstep = $this->createDocumentWorkflow($document, $newXML, true);
					if (!empty($docstep)) {
						$query = $this->_em->createQuery(
							"UPDATE DocovaBundle:DocumentWorkflowSteps DWS SET DWS.Position = DWS.Position + 1
							 WHERE DWS.Document = :document AND DWS.Position >= :order AND DWS.id != :newstep"
							)->setParameter('document', $document->getId())
							->setParameter('order', $docstep->getPosition())
							->setParameter('newstep', $docstep->getId());

						$query->execute();
						$modified = true;
						
						if ($force_reindex === true) {
							$this->reindexDocumnetInView($document);
						}
					}
				}
				catch(\Exception $e) {
					var_dump($e->getMessage());
				}
			}
			elseif (trim($step->nodeValue) == 1)
			{
				$step = $this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findOneBy(array('Document' => $document->getId(), 'id' => $post_xml->getElementsByTagName('Unid')->item($index + 1)->nodeValue));
				if (!empty($step))
				{
					$assignees = $groups = array();
					$assignee_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $post_xml->getElementsByTagName('wfDispReviewerApproverList')->item($index)->nodeValue);
					$added = [];
					for ($x = 0; $x < count($assignee_list); $x++)
					{
						$wfparticipant = trim($assignee_list[$x]);
						if (trim($wfparticipant))
						{
							if (false !== $user = $this->findUserAndCreate($wfparticipant))
							{
								if ( !in_array($user->getId(), $added))
								{	
									array_push($added, $user->getId());
									$assignees['user'][] = $user;
								}
							}
							else {
								$members = $this->fetchGroupMembers($wfparticipant, true);
								if (!empty($members) && $members->count() > 0)
								{
									$groups[] = $wfparticipant;
									foreach ($members as $user) 
									{
										if ( !in_array($user->getId(), $added)) {
											array_push($added, $user->getId());
											$assignees['group'][] = $user;
										}
									}
								}
							}
						}
						else {
							$assignees['user'][] = 'Please Select';
						}
					}
					$assignee_list = $user = null;
					if ((!empty($assignees['user']) && count($assignees['user'])) || (!empty($assignees['group']) && count($assignees['group'])))
					{
						$activated = false;
						$prev_assignee = $new_assignee = [];
						if ($post_xml->getElementsByTagName('wfIsCurrentItem')->item($index)->nodeValue == 1)
						{
							foreach ($step->getActions() as $action) {
								if ($action->getActionType() === 1 && $action->getSendMessage()) {
									$activated = true;
									break;
								}
							}
						}
						
						foreach ($step->getAssignee() as $assignee) {
							if ($activated === true) {
								$prev_assignee[] = $assignee->getAssignee()->getId();
							}
							$this->_em->remove($assignee);
						}
						$this->_em->flush();
						$assignee = null;
						if (!empty($assignees['user']))
						{
							foreach ($assignees['user'] as $user) {
								if ($user !== 'Please Select')
								{
									if ($activated === true && !in_array($user->getId(), $prev_assignee)) {
										$new_assignee[] = $user->getUserMail();
									}
									$assignee = new WorkflowAssignee();
									$assignee->setAssignee($user);
									$assignee->setDocWorkflowStep($step);
									$assignee->setGroupMember(false);
									$this->_em->persist($assignee);
									$modified = true;
								}
							}
						}
						if (!empty($assignees['group'])) {
							foreach ($assignees['group'] as $user) {
								if ($activated === true && !in_array($user->getId(), $prev_assignee)) {
									$new_assignee[] = $user->getUserMail();
								}
								$assignee = new WorkflowAssignee();
								$assignee->setAssignee($user);
								$assignee->setDocWorkflowStep($step);
								$assignee->setGroupMember(true);
								$this->_em->persist($assignee);
								$modified = true;
							}
						}
						if ($modified === true)
						{
							$step->setAssigneeGroup($groups);
						}
						$this->_em->flush();
						if ($activated === true && !empty($new_assignee))
						{
							$this->sendWfNotificationIfRequired($step, 1, '', $new_assignee);
						}
					}
				}
			}
		}
	
		if ($return_xml === true)
		{
			$result_xml = new \DOMDocument('1.0', 'UTF-8');
			$root = $result_xml->appendChild($result_xml->createElement('Results'));
			$child = $result_xml->createElement('Result', ($modified === true) ? 'OK' : 'FAILED');
			$att = $result_xml->createAttribute('ID');
			$att->value = 'Status';
			$child->appendChild($att);
			$root->appendChild($child);
	
			return $result_xml->saveXML();
		}
		else {
			return $modified;
		}
	}
	
	/**
	 * Complete workflow process
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceCOMPLETE($post_xml, $document, $return_xml = false)
	{
		$this->wfServiceUPDATE($post_xml, $document);
		return $this->wfServiceCommonFunction($post_xml, $document, $return_xml);
	}
	
	/**
	 * Approve workflow process
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceAPPROVE($post_xml, $document, $return_xml = false)
	{
		$nextnode = $post_xml->getElementsByTagName('NextStep')->item(0)->nodeValue;
		$doc_step =$this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findBy(array('Document' => $document->getId(), 'IsCurrentStep' =>true));

		if ( $nextnode == $doc_step[0]->getPosition())
		{
			$retval =  $this->wfServiceCommonFunction($post_xml, $document, $return_xml);
			$this->wfServiceUPDATE($post_xml, $document);
			return $retval;
			

		}else{
			$this->wfServiceUPDATE($post_xml, $document);
			return $this->wfServiceCommonFunction($post_xml, $document, $return_xml);

		}
		

	}
	
	/**
	 * Deny workflow process
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceDENY($post_xml, $document, $return_xml = false)
	{
		$this->wfServiceUPDATE($post_xml, $document);
		return $this->wfServiceCommonFunction($post_xml, $document, $return_xml, 'DENY');
	}
	
	/**
	 * Pause workflow process
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServicePAUSE($post_xml, $document, $return_xml = false)
	{
		return $this->wfServiceCommonFunction($post_xml, $document, $return_xml, 'PAUSE');
	}

	private function clearStep($document, $processed_step)
	{
		try {
			$completers = $this->_em->getRepository('DocovaBundle:WorkflowCompletedBy')->createQueryBuilder('C')
				->join('C.DocWorkflowStep', 'WS')
				->where('WS.Document = :docid')
				->andWhere('WS.Position = :order')
				->setParameter('docid', $document->getId())
				->setParameter('order', $processed_step->getPosition())
				->getQuery()
				->getResult();
			if (!empty($completers))
			{
				foreach ($completers as $cmp) {
					$exists = $this->_em->getRepository('DocovaBundle:WorkflowAssignee')->findOneBy(array('DocWorkflowStep' => $cmp->getDocWorkflowStep()->getId(), 'assignee' => $cmp->getCompletedBy()->getId()));
					if (empty($exists))
					{
						if ( empty  ( $cmp->getDocWorkflowStep()->getParticipantFormula()) ) {
							$assignee = new WorkflowAssignee();
							$assignee->setAssignee($cmp->getCompletedBy());
							$assignee->setDocWorkflowStep($cmp->getDocWorkflowStep());
							$assignee->setGroupMember($cmp->getGroupMember());
							$this->_em->persist($assignee);
						}
					}
				}
				$this->_em->flush();
			}
			$completers = $assignee = $exists = null;
			$this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->resetStep($document->getId(), $processed_step->getPosition(), true);
		}catch (\Exception $e) {
			$logger = $this->_container->get('logger');
			$logger->error('Clear Step failed; ERROR on line '.$e->getLine() . ' in '.$e->getFile() . ' with message: '. $e->getMessage());
			$logger = null;
			return false;
		}
		return true;
	}
	
	/**
	 * Common function to Complete, Approve, Deny or Pause a workflow process
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @param string $action
	 * @return string|boolean
	 */
	private function wfServiceCommonFunction($post_xml, $document, $return_xml = false, $action = null)
	{
		$index = 0;
		try {

			$nextnode = $post_xml->getElementsByTagName('NextStep')->item(0)->nodeValue;

			$islibs = ! is_null($document->getFolder() ) ? true : false;

			$next_step = $this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findOneBy(array('Document' => $document,  'Position' => $nextnode) );
			if ( empty($next_step) && $action !== "PAUSE"){
				throw new NotFoundHttpException('Could not find next step in workflow.');
			}

			$doc_step =$this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findBy(array('Document' => $document->getId(), 'IsCurrentStep' =>true));
			if ( empty($doc_step)){
				throw new NotFoundHttpException('Could not find current step in workflow.');
			}
			$doc_step = $doc_step[0];
			//$wfAction = $post_xml->getElementsByTagName('wfAction')->item($index)->nodeValue;
			//$wf_title = $post_xml->getElementsByTagName('wfTitle')->item($index)->nodeValue;
				
			$wfAction = $doc_step->getStepType(true);
			$wf_title = $doc_step->getStepName();
			

			$accessed = false;
			$security_context = $this->_container->get('security.authorization_checker');
			if ($action === 'PAUSE') {
				if ($security_context->isGranted('ROLE_ADMIN') || $security_context->isGranted('MASTER', $document->getFolder()->getLibrary()) || $document->getCreator()->getUserNameDnAbbreviated() == $this->_user->getUserNameDnAbbreviated()) {
					$accessed = true;
				}
			}
			else {
				$this->_em->refresh($doc_step);
				$assignee = $this->searchUserInCollection($doc_step->getAssignee(), $this->_user, true);
				if (!empty($assignee['id']))
				{
					$accessed = true;
				}
			}
			if ($accessed === false) {
				throw new AccessDeniedException();
			}
			$comment = (!empty($post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue)) ? $post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue : '';
	
			$log_obj = new Miscellaneous($this->_container);

			if ($action === 'PAUSE')
			{
				$doc_step->setStatus('Paused');
				$this->_em->flush();
				$this->sendWfNotificationIfRequired($doc_step, 4, $comment);
	
				$log_obj->createDocumentLog($this->_em, 'WORKFLOW', $document, $wf_title.' - paused workflow. Reason: '.$comment);
			}else 
			{
				$step_assignee = $this->_em->getReference('DocovaBundle:WorkflowAssignee', $assignee['record']);
				$this->_em->remove($step_assignee);
				$completedlist = $this->_em->getRepository('DocovaBundle:WorkflowCompletedBy')->findBy(array('DocWorkflowStep' => $doc_step->getId()) );

				$alreadycompleted = false;
				foreach ( $completedlist as $completedentry){
					if ( $completedentry->getCompletedBy()->getId() == $this->_user->getId())
						$alreadycompleted = true;
				}

				if ( !$alreadycompleted)
				{

					$completer = new WorkflowCompletedBy();
					$completer->setCompletedBy($this->_user);
					$completer->setDocWorkflowStep($doc_step);
					$completer->setGroupMember(!empty($assignee['gType']) ? true : false);
					$this->_em->persist($completer);
					$this->_em->flush();
				}
				$step_assignee = $completer = null;
				
	
				$completed = false;
				//$this->_em->refresh($doc_step);
				if ($doc_step->getCompleteOn() === 0 && $doc_step->getAssignee()->count() < 1  )
				{
					if ( empty($doc_step->getParticipantTokens()) || $doc_step->getParticipantTokens() == "[Formula]" )
					{
						$completed = true;
					}
				}
				elseif ($doc_step->getCompleteOn() > 1 && $doc_step->getCompletedBy()->count() == ($doc_step->getCompleteOn() - 2) ) {
					if ( empty($doc_step->getParticipantTokens()) || $doc_step->getParticipantTokens() == "[Formula]")
					{
						$completed = true;
					}
				}
				elseif ( ($doc_step->getCompleteOn() === 1 || $doc_step->getCompleteOn() === null )   ) {
					if ( empty($doc_step->getParticipantTokens()) || $doc_step->getParticipantTokens() == "[Formula]")
					{
						$completed = true;
					}
				}
				$docreleased = false;

				if ($completed === true)
				{
					if ($wfAction === 'Approve' && $action != "DENY") {
						$doc_step->setStatus('Approved');
					}else if ( $wfAction === 'Approve' && $action == "DENY")
					{
						$doc_step->setStatus('Denied');

					}else {
						$doc_step->setStatus('Completed');
					}
					$doc_step->setIsCurrentStep(false);
					$doc_step->setDateCompleted(new \DateTime());

					if ( $action === 'DENY'){
						$this->sendWfNotificationIfRequired($doc_step, 3, $comment);
					}else{

						$this->sendWfNotificationIfRequired($doc_step, 2, $comment);
					}

					$document->setStatusNo(0);
					$document->setDateModified(new \DateTime());
					$document->setModifier($this->_user);

					if (!empty($next_step))
					{
						$this->clearStep($document, $next_step);

						$oldorderno = $doc_step->getPosition();
						$neworderno = $next_step->getPosition();
						$next_step->setIsCurrentStep(true);

						if ( false /*$neworderno <= $oldorderno*/)
						{
							if (false === $this->backtrackWorkflow($document, $next_step, $comment)) 
							{
								throw new \Exception('Workflow backtrack process failed, details are logged.');
							}
						}else{


							$next_step->setDateStarted(new \DateTime());
							$next_step->setIsCurrentStep ( true );

							$this->sendWfNotificationIfRequired($next_step, 1, $comment);
							$doctype = $document->getDocType() ? $document->getDocType() : $document->getAppForm()->getFormProperties();

							$stat = $next_step->getStepType() === 1 ? $doctype->getInitialStatus()  : $next_step->getDocStatus();
							$document->setDocStatus($stat);
							
							//if  there is no version control and our next step is the end step...then just "release" the workflow without requiring the user to "release" the document.
							if ( $doctype && ! $doctype->getEnableVersions()  && $next_step->getStepType() == 4 )
							{
								if (true === $this->releaseDocument($post_xml, $document, true))
								{
									
									$docreleased = true;
									
								}
								else {
									throw new \Exception('Could not release document.');
								}
							}
						}
					}
				}
	
				$this->_em->flush();

				if ($completed === true) {
					if ( $action === 'DENY'){
						$log_obj->createDocumentLog($this->_em, 'WORKFLOW', $document, $wf_title.' - denied approval. Reason: '.$comment); 
					}else{
						$log_obj->createDocumentLog($this->_em, 'WORKFLOW', $document, $wf_title.' - Completed workflow step.'.(!empty($comment) ? ' Comments: '.$comment : ''));
					}
					
					$this->reindexDocumnetInView($document);
				}
				else {
					$log_obj->createDocumentLog($this->_em, 'WORKFLOW', $document, $wf_title.' - Completed workflow task.'.(!empty($comment) ? ' Comments: '.$comment : ''));
				}

				if ( $docreleased)
				{
					$log_obj->createDocumentLog($this->_em, 'WORKFLOW', $document, 'Completed document workflow.');
				}
			}
			$log_obj = $document = $comment = null;
	
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'OK');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
	
				return $result_xml->saveXML();
			}
			else {
				return true;
			}
		}
		catch (\Exception $e) {
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED: '.$e->getMessage().' on line '.$e->getLine(). ' of '. $e->getFile());
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return false;
			}
		}
	}
	
	/**
	 * Workflow request info service
	 *
	 * @param \DOMDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return boolean|string
	 */
	private function wfServiceINFO($post_xml, $document, $return_xml = false)
	{
		try {
		    $recipients = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $post_xml->getElementsByTagName('SendTo')->item(0)->nodeValue);
			foreach ($recipients as $index => $username)
			{
				if (false !== $user = $this->findUserAndCreate($username, false))
				{
					$recipients[$index] = (is_object($user)) ? $user->getUserMail() : $user['mail'];
				}
			}
			$comment	= nl2br($post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue);
			if ($document->getDocType()) {
				$link = ($this->_container->get('router')->generate('docova_readdocument', array('doc_id' => $document->getId()), true) . '?OpenDocument&ParentUNID=' . $document->getFolder()->getId());
				$title = $document->getDocTitle();
			} else {
				$link = $this->_container->get('router')->generate('docova_homeframe', array(), true).'?goto='.$document->getApplication()->getId().',appdoc,'.$document->getId();
				$title = 'Document in '.$document->getApplication()->getLibraryTitle().' application';
			}
			$body = <<<BODY
<font size="2" face="verdana,arial">{$comment}
<br>Follow the link below to open the document <a href="{$link}" alt="">{$title}</a>
</font>
BODY;
			$message = array(
					'senders' => array($this->_user),
					'subject' => $post_xml->getElementsByTagName('Subject')->item(0)->nodeValue,
					'content' => $body
			);
			if (true === MailController::parseMessageAndSend($message, $this->_container, $recipients))
			{
				if ($return_xml === true)
				{
					$result_xml = new \DOMDocument('1.0', 'UTF-8');
					$root = $result_xml->appendChild($result_xml->createElement('Results'));
					$child = $result_xml->createElement('Result', 'OK');
					$att = $result_xml->createAttribute('ID');
					$att->value = 'Status';
					$child->appendChild($att);
					$root->appendChild($child);
						
					return $result_xml->saveXML();
				}
				else {
					return true;
				}
			}
		}
		catch (\Exception $e) {
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return false;
			}
		}
	}
	
	/**
	 * Delete workflow step service
	 *
	 * @param \DOMDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param string $return_xml
	 * @return string|boolean
	 */
	private function wfServiceDELETE($post_xml, $document, $return_xml = false)
	{
		try {
			$removed = $found = false;
			foreach ($post_xml->getElementsByTagName('Selected') as $index => $item) {
				if ($item->nodeValue == 1) {
					$found = true;
					break;
				}
			}
			unset($item);
			if ($found === true)
			{
				$doc_step = $this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->getOneBy(array('Document' => $document->getId(), 'Step_Name' => $post_xml->getElementsByTagName('wfTitle')->item($index)->nodeValue));
				if (!empty($doc_step))
				{
					if ($doc_step->getActions()->count())
					{
						foreach ($doc_step->getActions() as $action) {
							$this->_em->remove($action);
						}
						$this->_em->flush();
					}
					$this->_em->remove($doc_step);
					$this->_em->flush();
					$removed = true;
				}
				unset($doc_step, $found);
			}
			if ($removed === true)
			{
				$log_obj = new Miscellaneous($this->_container);
				$log_obj->createDocumentLog($this->_em, 'WORKFLOW', $document, 'Deleted workflow step Review document ');
				unset($log_obj);
				if ($return_xml === true)
				{
					$result_xml = new \DOMDocument('1.0', 'UTF-8');
					$root = $result_xml->appendChild($result_xml->createElement('Results'));
					$child = $result_xml->createElement('Result', 'OK');
					$att = $result_xml->createAttribute('ID');
					$att->value = 'Status';
					$child->appendChild($att);
					$root->appendChild($child);
	
					return $result_xml->saveXML();
				}
				else {
					return true;
				}
			}
			else {
				throw new \Exception('Out of range step or undefined step was selected.');
			}
		}
		catch (\Exception $e) {
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return false;
			}
		}
	}
	
	/**
	 * Cancel a workflow process
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return boolean|string
	 */
	private function wfServiceCANCEL($post_xml, $document, $return_xml = false)
	{
		try {
			if (empty($post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue)) {
				throw new NotFoundHttpException('Cancellation comment is missed.');
			}
			$comment = trim($post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue);
				
			$index = 0;
			foreach ($post_xml->getElementsByTagName('wfIsCurrentItem') as $item)
			{
				if ($item->nodeValue === '1')
				{
					break;
				}
				$index++;
			}
				
			if (false === $this->cancelWorkflow($document, $post_xml->getElementsByTagName('Unid')->item($index + 1)->nodeValue, $comment))
			{
				throw new \Exception('Workflow cancelation is failed, details are logged.');
			}
			$log_obj = new Miscellaneous($this->_container);
			$log_obj->createDocumentLog($this->_em, 'WORKFLOW', $document, 'Cancelled document workflow. Reason: '.$comment);
			$log_obj = $document = $comment = null;
	
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'OK');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return true;
			}
		}
		catch (\Exception $e) {
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED'.$e->getMessage().' on line '.$e->getLine().' of '. $e->getFile());
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return false;
			}
		}
	}
	
	/**
	 * Release document (create new version if versioning is enabled)
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceFINISH($post_xml, $document, $return_xml = false)
	{
		try {
			$index = 0;
			foreach ($post_xml->getElementsByTagName('wfIsCurrentItem') as $item)
			{
				if ($item->nodeValue === '1')
				{
					break;
				}
				$index++;
			}
				
			if ($post_xml->getElementsByTagName('wfAction')->item($index)->nodeValue === 'End')
			{
				if (true === $this->releaseDocument($post_xml, $document, true))
				{
					$log_obj = new Miscellaneous($this->_container);
					$log_obj->createDocumentLog($this->_em, 'WORKFLOW', $document, 'Completed document workflow.');
					$log_obj = $post_xml = $document = null;
					if ($return_xml === true)
					{
						$result_xml = new \DOMDocument('1.0', 'UTF-8');
						$root = $result_xml->appendChild($result_xml->createElement('Results'));
						$child = $result_xml->createElement('Result', 'OK');
						$att = $result_xml->createAttribute('ID');
						$att->value = 'Status';
						$child->appendChild($att);
						$root->appendChild($child);
	
						return $result_xml->saveXML();
					}
					else {
						return true;
					}
				}
				else {
					throw new \Exception('Could not release document.');
				}
			}
			else {
				throw new \Exception('Releasing the Document with workflow is not completed.');
			}
		}
		catch (\Exception $e)
		{
			var_dump($e->getMessage());
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
				$child = $result_xml->createElement('Result', $e->getMessage());
				$att = $result_xml->createAttribute('ID');
				$att->value = 'ErrMsg';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return false;
			}
		}
	}
	
	/**
	 * Backtrack a workflow process
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceBACKTRACK($post_xml, $document, $return_xml = false)
	{
		try {
			$index = 0;
			foreach ($post_xml->getElementsByTagName('wfIsCurrentItem') as $item)
			{
				if ($item->nodeValue === '1')
				{
					break;
				}
				$index++;
			}
			$processed_step = $post_xml->getElementsByTagName('ProcessedStep')->item(0)->nodeValue;
			if (empty($processed_step) && $processed_step !== '0') {
				throw new NotFoundHttpException('Backtrack step is missed.');
			}
			
			

			$next_step = $this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->findOneBy(array('Document' => $document,  'Position' => $processed_step) );

	
			if (false === $this->backtrackWorkflow($document, $next_step)) {
				throw new \Exception('Backtrack process failed, all details are logged.');
			}
				
			$wf_title = $post_xml->getElementsByTagName('wfTitle')->item($index)->nodeValue;

			$log_obj = new Miscellaneous($this->_container);
			$log_obj->createDocumentLog($this->_em, 'WORKFLOW', $document, 'Restarted document workflow from step '.$wf_title);
			unset($log_obj);
				
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'OK');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return true;
			}
		}
		catch (\Exception $e) {
			//var_dump($e->getMessage());
	
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return false;
			}
		}
	}
	
	/**
	 * Add Comment
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceADDCOMMENT($post_xml, $document, $return_xml = false)
	{
		if (!empty($post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue))
		{
			$comment = new DocumentComments();
			$comment->setDocument($document);
			$comment->setCreatedBy($this->_user);
			$comment->setDateCreated(new \DateTime());
			$comment->setComment($post_xml->getElementsByTagName('UserComment')->item(0)->nodeValue);
			$this->_em->persist($comment);
			$this->_em->flush();
			$post_xml = $document = null;
				
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'OK');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return true;
			}
		}
	
		if ($return_xml === true)
		{
			$result_xml = new \DOMDocument('1.0', 'UTF-8');
			$root = $result_xml->appendChild($result_xml->createElement('Results'));
			$child = $result_xml->createElement('Result', 'FAILED');
			$att = $result_xml->createAttribute('ID');
			$att->value = 'Status';
			$child->appendChild($att);
			$root->appendChild($child);
	
			return $result_xml->saveXML();
		}
		else {
			return false;
		}
	}
	
	/**
	 * Retrackt a released document
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceRETRACTVERSION($post_xml, $document, $return_xml = false)
	{
		try {
			$doctype = $document->getDocType() ? $document->getDocType() : $document->getAppForm()->getFormProperties();
			if ($doctype->getEnableVersions() && $post_xml->getElementsByTagName('CurAction')->item(0)->nodeValue === 'R')
			{
				$document->setDocStatus($doctype->getInitialStatus());
				$document->setStatusNo(0);
				$document->setReleasedBy();
				$document->setReleasedDate();
			}
			elseif ($doctype->getEnableVersions() && $post_xml->getElementsByTagName('CurAction')->item(0)->nodeValue === 'O')
			{
				$document->setDocStatus($doctype->getSupersededStatus());
				$document->setStatusNo(2);
			}
			elseif ($doctype->getEnableLifecycle()) {
				$document->setDocStatus($doctype->getInitialStatus());
				$document->setStatusNo(0);
				$document->setReleasedBy();
				$document->setReleasedDate();
			}
			$document->setModifier($this->_user);
			$document->setDateModified(new \DateTime());
	
			$log_obj = new Miscellaneous($this->_container);
			if ($doctype->getEnableVersions() && $post_xml->getElementsByTagName('PrevAction')->item(0)->nodeValue === 'C' && $document->getParentDocument())
			{
				$previous_version = $this->_em->getRepository('DocovaBundle:Documents')->getPreviousVersion($document);
				if (!empty($previous_version) && $previous_version->getTrash() === false && $previous_version->getDocStatus() == $doctype->getSupersededStatus())
				{
					$previous_version->setDocStatus($doctype->getFinalStatus());
					$previous_version->setStatusNo(1);
					$this->_em->flush();
					$this->reindexDocumnetInView($previous_version);
					$log_obj->createDocumentLog($this->_em, 'UPDATE', $previous_version, 'Set document as current release.');
				}
			}
			$this->_em->flush();
			$this->reindexDocumnetInView($document);
			$log_obj->createDocumentLog($this->_em, 'UPDATE', $document, 'Retracted release.');
			$log_obj = $document = null;
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'OK');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return false;
			}
		}
		catch (\Exception $e) {
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
				$child = $result_xml->createElement('Ret1', $e->getMessage());
				$att = $result_xml->createAttribute('ID');
				$att->value = 'ErrMsg';
				$child->appendChild($att);
				$root->appendChild($child);
	
				return $result_xml->saveXML();
			}
			else {
				return true;
			}
		}
	}
	
	/**
	 * Release a document version
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceRELEASEVERSION($post_xml, $document, $return_xml = false)
	{
		if (true === $this->releaseDocument($post_xml, $document))
		{
			$log_obj = new Miscellaneous($this->_container);
			$log_obj->createDocumentLog($this->_em, 'UPDATE', $document, 'Released document as version '.$post_xml->getElementsByTagName('Version')->item(0)->nodeValue);
			$log_obj = $document = $post_xml = null;
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'OK');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
	
				return $result_xml->saveXML();
			}
			else {
				return true;
			}
		}
	
		if ($return_xml === true)
		{
			$result_xml = new \DOMDocument('1.0', 'UTF-8');
			$root = $result_xml->appendChild($result_xml->createElement('Results'));
			$child = $result_xml->createElement('Result', 'FAILED');
			$att = $result_xml->createAttribute('ID');
			$att->value = 'Status';
			$child->appendChild($att);
			$root->appendChild($child);
	
			return $result_xml->saveXML();
		}
		else {
			return false;
		}
	}

	/**
	 * Activate a discarded document version
	 *
	 * @param \DomDocument $post_xml
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param boolean $return_xml
	 * @return string|boolean
	 */
	private function wfServiceACTIVATEVERSION($post_xml, $document, $return_xml = false)
	{
		try {
			$doctype = $document->getDocType() ? $document->getDocType() : $document->getAppForm()->getFormProperties();
			$document->setDocStatus($doctype->getInitialStatus());
			$document->setStatusNo(0);
			$document->setDateModified(new \DateTime());
			$document->setModifier($this->_user);
			$document->setReleasedBy();
			$document->setReleasedDate();
			$this->_em->flush();
			
			$this->reindexDocumnetInView($document);
			$log_obj = new Miscellaneous($this->_container);
			$log_obj->createDocumentLog($this->_em, 'UPDATE', $document, 'Reactivated discarded draft.');
			$log_obj = $document = $post_xml = null;
				
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'OK');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return true;
			}
		}
		catch (\Exception $e) {
			if ($return_xml === true)
			{
				$result_xml = new \DOMDocument('1.0', 'UTF-8');
				$root = $result_xml->appendChild($result_xml->createElement('Results'));
				$child = $result_xml->createElement('Result', 'FAILED');
				$att = $result_xml->createAttribute('ID');
				$att->value = 'Status';
				$child->appendChild($att);
				$root->appendChild($child);
					
				return $result_xml->saveXML();
			}
			else {
				return false;
			}
		}
	}

	/**
	 * Cancel a workflow and update the document status
	 *
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param string $next_step
	 * @param string $comment
	 * @return boolean
	 */
	private function cancelWorkflow($document, $next_step, $comment)
	{
		try {
			$doc_steps = $document->getDocSteps();
			foreach ($doc_steps as $step)
			{
				if (!empty($next_step) && $next_step == $step->getId()) {
					$this->sendWfNotificationIfRequired($step, 5, $comment);
				}
				if ($step->getActions() && $step->getActions()->count() > 0)
				{
					foreach ($step->getActions() as $action) {
						$this->_em->remove($action);
					}
					$this->_em->flush();
				}
	
				if ($step->getAssignee()->count())
				{
					foreach ($step->getAssignee() as $assignee) {
						$this->_em->remove($assignee);
					}
					$this->_em->flush();
				}
				if ($step->getCompletedBy()->count())
				{
					foreach ($step->getCompletedBy() as $completor) {
						$this->_em->remove($completor);
					}
				}
				$this->_em->remove($step);
			}
			$this->_em->flush();
			
			$doctype = $document->getDoctype() ? $document->getDocType() : $document->getAppForm()->getFormProperties();
			$document->setDocStatus($doctype->getInitialStatus());
			$document->setStatusNo(0);
			$document->setDateModified(new \DateTime());
			$document->setModifier($this->_user);
			$this->_em->flush();
			$this->reindexDocumnetInView($document);
			$doc_steps = $step = null;
				
			return true;
		}
		catch(\Exception $e) {
			$logger = $this->_container->get('logger');
			$logger->error('Cancelation stopped due to: ERROR on Line: ' . $e->getLine() . ' in ' . $e->getFile() . ' with message: ' . $e->getMessage());
			$logger = null;
		}
		return false;
	}

	/**
	 * Backtrack a workflow step to another step
	 *
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param integer $processed_step
	 * @param string $new_status
	 * @return boolean
	 */
	private function backtrackWorkflow($document, $processed_step, $comment = null)
	{
		try {
			$completers = $this->_em->getRepository('DocovaBundle:WorkflowCompletedBy')->createQueryBuilder('C')
				->join('C.DocWorkflowStep', 'WS')
				->where('WS.Document = :docid')
				->andWhere('WS.Position >= :order')
				->setParameter('docid', $document->getId())
				->setParameter('order', $processed_step->getPosition())
				->getQuery()
				->getResult();
			if (!empty($completers))
			{
				foreach ($completers as $cmp) {
					$exists = $this->_em->getRepository('DocovaBundle:WorkflowAssignee')->findOneBy(array('DocWorkflowStep' => $cmp->getDocWorkflowStep()->getId(), 'assignee' => $cmp->getCompletedBy()->getId()));
					if (empty($exists))
					{
						if ( empty  ( $cmp->getDocWorkflowStep()->getParticipantFormula()) ) {
							$assignee = new WorkflowAssignee();
							$assignee->setAssignee($cmp->getCompletedBy());
							$assignee->setDocWorkflowStep($cmp->getDocWorkflowStep());
							$assignee->setGroupMember($cmp->getGroupMember());
							$this->_em->persist($assignee);
						}
					}
				}
				$this->_em->flush();
			}
			$completers = $assignee = $exists = null;
			$this->_em->getRepository('DocovaBundle:DocumentWorkflowSteps')->resetStep($document->getId(), $processed_step->getPosition());


			$first_step = $processed_step;
			$new_status = $processed_step->getDocStatus();
			$processed_step->setIsCurrentStep(true);
			$this->sendWfNotificationIfRequired($first_step, 1, $comment);
			if ($document->getAppForm()) {
				$new_status = !empty($new_status) ? $new_status : $document->getAppForm()->getFormProperties()->getInitialStatus();
			}
			else {
				$new_status = (!empty($new_status)) ? $new_status : $document->getDocType()->getInitialStatus();
			}
			$document->setDocStatus($new_status);
			$status_list = array(1 => 'released', 2 => 'inactive', 5 =>'discarded', 6 => 'archived', 7 => 'deleted');
			$key = array_search(strtolower($new_status), $status_list);
			$document->setStatusNo($key !== false ? $key : 0);
			$document->setDateModified(new \DateTime());
			$document->setModifier($this->_user);
			$this->_em->flush();
			$this->reindexDocumnetInView($document);
			$first_step = null;
			return true;
		}
		catch (\Exception $e) {
			$logger = $this->_container->get('logger');
			$logger->error('Backtrack failed; ERROR on line '.$e->getLine() . ' in '.$e->getFile() . ' with message: '. $e->getMessage());
			$logger = null;
		}
		return false;
	}
	
	/**
	 * Re-index the document in all app views
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @return boolean|void
	 */
	private function reindexDocumnetInView($document)
	{
		if (!$document->getApplication()) {
			return false;
		}
		
		$appid = $document->getApplication()->getId();
		$views = $this->_em->getRepository('DocovaBundle:AppViews')->findBy(array('application' => $appid));
		$view_handler = new ViewManipulation($this->_docova, $appid, $this->_gs);
		if (!empty($views))
		{
			$dateformat = $this->_gs->getDefaultDateFormat();
			$display_names = $this->_gs->getUserDisplayDefault();
			$repository = $this->_em->getRepository('DocovaBundle:Documents');
			$twig = $this->_container->get('twig');
			$doc_values = $repository->getDocFieldValues($document->getId(), $dateformat, $display_names, true);
			foreach ($views as $v)
			{
				try {
					//re-index the document in all views
					$view_handler->indexDocument2($document->getId(), $doc_values, $appid, $v->getId(), $v->getViewPerspective(), $twig, false, $v->getConvertedQuery());
				}
				catch (\Exception $e) {
				}
			}
		}
	}
	
	
	/**
	 * Fetch group members
	 *
	 * @param string $grouname
	 * @param boolean $return_members
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $findme
	 * @return \Doctrine\Common\Collections\ArrayCollection|boolean
	 */
	private function fetchGroupMembers($grouname, $return_members = false, $findme = null)
	{
		$type = false === strpos($grouname, '/DOCOVA') ? true : false;
		$name = str_replace('/DOCOVA', '', $grouname);
		$role = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $name, 'groupType' => $type));
		if (!empty($role)) {
			if (empty($return_members) && empty($findme))
			{
				return $role;
			}
			if ($return_members === true)
			{
				return $role->getRoleUsers();
			}
			else {
				if ($role->getRoleUsers()->count() > 0) {
					foreach ($role->getRoleUsers() as $user) {
						if ($user->getUserNameDnAbbreviated() === $findme->getUserNameDnAbbreviated())
						{
							return true;
						}
					}
				}
				return false;
			}
		}
	
		return false;
	}

	/**
	 * Search for a user inside a collection object
	 *
	 * @param \Doctrine\Common\Collections\ArrayCollection $collection
	 * @param \Docova\DocovaBundle\Entity\UserAccounts $user
	 * @param boolean $isCompletedBy [optional(false)]
	 * @return boolean
	 */
	private function searchUserInCollection($collection, $user, $return_group = false, $isCompletedBy = false)
	{
		foreach ($collection as $record) {
			if ($isCompletedBy === false)
			{
				if ($record->getAssignee()->getId() === $user->getId()) {
					return ($return_group === true ? array('id' => $user->getId(), 'gType' => $record->getGroupMember(), 'record' => $record->getId()) : true);
				}
			}
			else {
				if ($record->getCompletedBy()->getId() === $user->getId()) {
					return ($return_group === true ? array('id' => $user->getId(), 'gType' => $record->getGroupMember(), 'record' => $record->getId()) : true);
				}
			}
		}
		return false;
	}

	/**
	 * Find a user info in Database, if does not exist a user object can be created according to its info through LDAP server
	 *  
	 * @param string $username
	 * @param boolean $create
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUserAndCreate($username, $create = true, $search_userid = false, $search_ad = true)
	{
		$utilHelper= new UtilityHelperTeitr($this->_gs, $this->_container);
		return $utilHelper->findUserAndCreate($username, $create, $this->_em, $search_userid, $search_ad);
	}
}