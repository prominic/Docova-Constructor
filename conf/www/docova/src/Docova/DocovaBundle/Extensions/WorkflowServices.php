<?php

namespace Docova\DocovaBundle\Extensions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Docova\DocovaBundle\Entity\Workflow;
use Doctrine\ORM\EntityManager;
use Docova\DocovaBundle\Entity\WorkflowSteps;
use Docova\DocovaBundle\Entity\WorkflowStepActions;
//use Docova\DocovaBundle\Controller\UtilityHelperTeitr;
use Docova\DocovaBundle\Security\User\adLDAP;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserProfile;

/**
 * External workflow class to handle workflow creation process
 * including workflow steps, can be used in app builder or admin
 * @author javad_rahimi
 *        
 */
class WorkflowServices
{
	protected $_em;
	protected $_params;
	
	public function __construct(EntityManager $em)
	{
		$this->_em = $em;
	}
	
	public function setParams($params)
	{
		$this->_params = $params;
	}
	
	/**
	 * Generate workflow
	 * 
	 * @param array $details
	 * @return \Docova\DocovaBundle\Entity\Workflow
	 */
	public function generateWorkflow($details = array())
	{
		if (empty($details['WorkflowName']))
		{
			throw new NotFoundHttpException('Workflow Name is required to be filled out.');
		}
			
		$enable_releasing = ($details['EnableImmediateRelease'] == 1) ? true : false;
		$default_workflow = ($details['DefaultWorkflow'] == 1) ? true : false;
		
		if (!empty($details['Workflow']) && $details['Workflow'] instanceof \Docova\DocovaBundle\Entity\Workflow) {
			$workflow = $details['Workflow'];
		}
		else {
			$workflow = new Workflow();
		}
		$workflow->setWorkflowName(trim($details['WorkflowName']));
		$workflow->setDescription(!empty($details['WorkflowDescription']) ? $details['WorkflowDescription'] : null);
		$workflow->setAuthorCustomization($details['CustomizeAction']);
		$workflow->setBypassRleasing($enable_releasing);
		$workflow->setUseDefaultDoc($default_workflow);
		$workflow->setDateModified(new \DateTime());

		if (!empty($details['Workflow']) && $details['Workflow'] instanceof \Docova\DocovaBundle\Entity\Workflow) 
		{
			$this->_em->flush();
			return $workflow;
		}

		if (!empty($details['Application'])) 
		{
			$app = $this->_em->getReference('DocovaBundle:Libraries', $details['Application']);
			$workflow->setApplication($app);
		}
		
		$this->_em->persist($workflow);
		$this->_em->flush();
		
		return $workflow;
	}
	
	public function generateWorkflowStep($details = array(), $runjson = '')
	{
		if (empty($details['wfTitle']) || empty($details['wfAction']))
		{
			throw new NotFoundHttpException('Step Name and Step Type are required.');
		}
		
		if (!empty($details['WorkflowStep']) && $details['WorkflowStep'] instanceof \Docova\DocovaBundle\Entity\WorkflowSteps) {
			$workflow_step = $details['WorkflowStep'];
		}
		else {
			$workflow_step = new WorkflowSteps();
		}

		$participant_tokens = "";
		if ( !empty($details['wfParticipantTokens']))
			$participant_tokens = $details['wfParticipantTokens'];
		else if ($details['wfAction'] != "Start" && $details['wfAction'] != "Stop" && $details['wfAction'] != "Cancel") {
			throw new NotFoundHttpException('Please Select participant(s).');
		}

		$participants = 0;
		$participants += (strpos($participant_tokens, '[Author]') !== false) ? 1 : 0;
		$participants += ($details['wfReviewerApproverSelect'] == 1) ?  2 : 0;
		$complete_by = null;
		$optional_comments = false;

		

		
		switch ($details['wfAction'])
		{
			case 'Start':
				$wf_action = 1;
				$participants = $distribution = $complete_by = null;
				$optional_comments = $approver_edit = false;
				if ( !empty( $runjson )){
					$workflow_step->setRuntimeJSON($runjson);
				}
				$workflow_step->setCustomReviewButtonLabel(null);
				$workflow_step->setCustomApproveButtonLabel(null);
				$workflow_step->setCustomDeclineButtonLabel(null);
				$workflow_step->setCustomReleaseButtonLabel(null);
				break;
			case 'Stop':
				$wf_action = 5;
				$participants = $distribution = $complete_by = null;
				$optional_comments = $approver_edit = false;
				$workflow_step->setCustomReviewButtonLabel(null);
				$workflow_step->setCustomApproveButtonLabel(null);
				$workflow_step->setCustomDeclineButtonLabel(null);
				$workflow_step->setCustomReleaseButtonLabel(null);
				break;
			case 'Review':
				$distribution = ($details['wfType'] == 'Serial') ? 1 : 2;
				$approver_edit = false;
				if ($distribution == 2) {
					if ($details['wfCompleteAny'] != 2) {
						$complete_by = $details['wfCompleteAny'];
					}
					else {
						$complete_by = $details['wfCompleteAny'] + $details['wfCompleteCount'];
					}
				}
				$optional_comments = ($details['wfOptionalComments'] == 1) ? true : false;
				$workflow_step->setCustomReviewButtonLabel(!empty($details['wfCustomReviewButtonLabel']) ? $details['wfCustomReviewButtonLabel'] : null);
				$workflow_step->setCustomApproveButtonLabel(null);
				$workflow_step->setCustomDeclineButtonLabel(null);
				$workflow_step->setCustomReleaseButtonLabel(null);
				$wf_action = 2;
				break;
			case 'Approve':
				$distribution = ($details['wfType'] == 'Serial') ? 1 : 2;
				if ($distribution == 2) {
					if ($details['wfCompleteAny'] != 2) {
						$complete_by = $details['wfCompleteAny'];
					}
					else {
						$complete_by = $details['wfCompleteAny'] + $details['wfCompleteCount'];
					}
				}
				$optional_comments = ($details['wfOptionalComments'] == 1) ? true : false;
				$approver_edit = ($details['wfApproverEdit'] == 'Yes') ? true : false;
				$workflow_step->setCustomApproveButtonLabel(!empty($details['wfCustomApproveButtonLabel']) ? $details['wfCustomApproveButtonLabel'] : null);
				$workflow_step->setCustomDeclineButtonLabel(!empty($details['wfCustomDeclineButtonLabel']) ? $details['wfCustomDeclineButtonLabel'] : null);
				$workflow_step->setCustomReviewButtonLabel(null);
				$workflow_step->setCustomReleaseButtonLabel(null);
				$wf_action = 3;
				break;
			case 'End':
				$distribution = ($details['wfType'] == 'Serial') ? 1 : 2;
				$approver_edit = false;
				if ($distribution == 2) {
					if ($details['wfCompleteAny'] != 2) {
						$complete_by = $details['wfCompleteAny'];
					}
					else {
						$complete_by = $details['wfCompleteAny'] + $details['wfCompleteCount'];
					}
				}
				$workflow_step->setCustomDeclineButtonLabel(!empty($details['wfCustomDeclineButtonLabel']) ? $details['wfCustomDeclineButtonLabel'] : null);
				$workflow_step->setCustomReleaseButtonLabel(!empty($details['wfCustomReleaseButtonLabel']) ? $details['wfCustomReleaseButtonLabel'] : null);
				$workflow_step->setCustomReviewButtonLabel(null);
				$workflow_step->setCustomApproveButtonLabel(null);
				$wf_action = 4;
				break;
			default:
				throw new NotFoundHttpException('Unrecognized step action type');
		}
			
		if ($details['wfHideButtons'] && is_array($details['wfHideButtons']))
		{
			if (in_array('R', $details['wfHideButtons'])) {
				$workflow_step->setHideReading(true);
			}
			else {
				$workflow_step->setHideReading(false);
			}
			if (in_array('E', $details['wfHideButtons'])) {
				$workflow_step->setHideEditing(true);
			}
			else {
				$workflow_step->setHideEditing(false);
			}
			if (in_array('C', $details['wfHideButtons'])) {
				$workflow_step->setHideCustom(true);
			}
			else {
				$workflow_step->setHideCustom(false);
			}
		}
		else {
			$workflow_step->setHideReading(false);
			$workflow_step->setHideEditing(false);
			$workflow_step->setHideCustom(false);
		}

		$parray = [];
		if ( !empty($participant_tokens)){
			$workflow_step->setParticipantTokens($participant_tokens);
			$tmparr = explode(',', $participant_tokens);
			foreach ( $tmparr as $wftoken)
			{
				$wftoken = trim($wftoken);
				if ( $wftoken != "[Author]" && $wftoken != "[Formula]")
				{
					array_push($parray, $wftoken);
				}
			}
		}else{
			$workflow_step->setParticipantTokens(null);
		}


		if ( !empty($details['wfParticipantFormula'])){
			
			$workflow_step->setParticipantFormula($details['wfParticipantFormula']);
		}else{

			$workflow_step->setParticipantFormula(null);
		}

			
		$workflow_step->setStepName($details['wfTitle']);
		$workflow_step->setStepType($wf_action);
		$workflow_step->setPosition($details['wfOrder']);
		$workflow_step->setParticipants($participants);
		$workflow_step->setDistribution($distribution);
		$workflow_step->setCompleteBy($complete_by);
		$workflow_step->setOptionalComments($optional_comments);
		$workflow_step->setApproverEdit($approver_edit);
		if (!empty($details['wfDocStatus'])) {
			$workflow_step->setDocStatus($details['wfDocStatus']);
		}
		else {
			$workflow_step->setDocStatus(null);
		}

		if ($workflow_step->getOtherParticipant()->count() > 0)
		{
			foreach ($workflow_step->getOtherParticipant() as $user) 
			{
				$workflow_step->removeOtherParticipant($user);
			}
		}

		if (!empty($details['WorkflowStep']) && $details['WorkflowStep'] instanceof \Docova\DocovaBundle\Entity\WorkflowSteps) {
			$this->_em->flush();
			$this->_em->refresh($workflow_step);
		}
		else {
			$workflow_step->setWorkflow($details['Workflow']);
			$this->_em->persist($workflow_step);
		}

		if ($details['wfAction'] != 'Start')
		{
			$other_participants = $parray;
		
			$participantGroups = array();
			if (!empty($other_participants) && is_array($other_participants))
			{
				foreach ($other_participants as $op)
				{
					if (false !== $user = $this->findUserAndCreate($op))
					{
						$workflow_step->addOtherParticipant($user);
					}
					else {
						$participantGroups[] = $op;
					}
				}
			}
		
			$workflow_step->setOtherParticipantGroups($participantGroups);
		}
		$this->_em->flush();
		if (!empty($details['WorkflowStep']) && $details['WorkflowStep'] instanceof \Docova\DocovaBundle\Entity\WorkflowSteps) {
			$this->_em->refresh($workflow_step);

			if ($workflow_step->getActions()->count() > 0)
			{
				foreach ($workflow_step->getActions() as $action)
				{
					$this->_em->remove($action);
					$this->_em->flush();
				}
			}
		}

		if (!empty($details['wfEnableActivateMsg']) && !empty($details['wfActivateMsg']))
		{
			$message_code	= $details['wfActivateMsg'];
			$notify_list	= $details['wfActivateNotifyList'];
			$options = 0;
		
			if (!is_array($details['wfActivateNotifyParticipants']))
			{
				$notify_participants = array($details['wfActivateNotifyParticipants']);
			}
			else {
				$notify_participants = $details['wfActivateNotifyParticipants'];
			}
		
			if (in_array('P', $notify_participants) === true) {
				$options= 1;
			}
			if (in_array('A', $notify_participants) === true) {
				$options = (!empty($options)) ? 3 : 2;
			}
		
			$this->setWorkflowStepActions($workflow_step, $message_code, 1, $options, $notify_list);
		}
		if (!empty($details['wfEnableCompleteMsg']) && !empty($details['wfCompleteMsg']))
		{
			$message_code	= $details['wfCompleteMsg'];
			$notify_list	= $details['wfCompleteNotifyList'];
			$options = 0;
		
			if (!is_array($details['wfCompleteNotifyParticipants']))
			{
				$notify_participants = array($details['wfCompleteNotifyParticipants']);
			}
			else {
				$notify_participants = $details['wfCompleteNotifyParticipants'];
			}
		
			if (in_array('P', $notify_participants) === true) {
				$options= 1;
			}
				
			if (in_array('A', $notify_participants) === true) {
				$options = (!empty($options)) ? 3 : 2;
			}
		
			$this->setWorkflowStepActions($workflow_step, $message_code, 2, $options, $notify_list);
		}
		if (!empty($details['wfEnableDeclineMsg']) && !empty($details['wfDeclineMsg']))
		{
			$message_code	= $details['wfDeclineMsg'];
			$notify_list	= $details['wfDeclineNotifyList'];
			$options = 0;
			if (!is_array($details['wfDeclineNotifyParticipants']))
			{
				$notify_participants = array($details['wfDeclineNotifyParticipants']);
			}
			else {
				$notify_participants = $details['wfDeclineNotifyParticipants'];
			}
			if (in_array('P', $notify_participants) === true) {
				$options= 1;
			}
				
			if (in_array('A', $notify_participants) === true) {
				$options = (!empty($options)) ? 3 : 2;
			}
		
			if (is_numeric($details['wfDeclineBacktrack'])) {
				$backtrack_step = $this->_em->getRepository('DocovaBundle:WorkflowSteps')->findOneBy(['Position' => $details['wfDeclineBacktrack'], 'Workflow' => $workflow_step->getWorkflow()->getId()]);
				if (!empty($backtrack_step)) {
					$details['wfDeclineBacktrack'] = $backtrack_step->getId();
				}
			}
			$this->setWorkflowStepActions($workflow_step, $message_code, 3, $options, $notify_list, null, $details['wfDeclineAction'], $details['wfDeclineBacktrack']);
		}
		elseif ($details['wfDeclineAction'] > 1) {
			if (is_numeric($details['wfDeclineBacktrack'])) {
				$backtrack_step = $this->_em->getRepository('DocovaBundle:WorkflowSteps')->findOneBy(['Position' => $details['wfDeclineBacktrack'], 'Workflow' => $workflow_step->getWorkflow()->getId()]);
				if (!empty($backtrack_step)) {
					$details['wfDeclineBacktrack'] = $backtrack_step->getId();
				}
			}
			$this->setWorkflowStepActions($workflow_step, null, 3, null, null, null, $details['wfDeclineAction'], $details['wfDeclineBacktrack']);
		}
		if (!empty($details['wfEnablePauseMsg']) && !empty($details['wfPauseMsg']))
		{
			$message_code	= $details['wfPauseMsg'];
			$notify_list	= $details['wfPauseNotifyList'];
			$options = 0;
			if (!is_array($details['wfPauseNotifyParticipants']))
			{
				$notify_participants = array($details['wfPauseNotifyParticipants']);
			}
			else {
				$notify_participants = $details['wfPauseNotifyParticipants'];
			}
			if (in_array('P', $notify_participants) === true) {
				$options= 1;
			}
				
			if (in_array('A', $notify_participants) === true) {
				$options = (!empty($options)) ? 3 : 2;
			}
		
			$this->setWorkflowStepActions($workflow_step, $message_code, 4, $options, $notify_list);
		}
		if (!empty($details['wfEnableCancelMsg']) && !empty($details['wfCancelMsg']))
		{
			$message_code	= $details['wfCancelMsg'];
			$notify_list	= $details['wfCancelNotifyList'];
			$options = 0;
			if (!is_array($details['wfCancelNotifyParticipants']))
			{
				$notify_participants = array($details['wfCancelNotifyParticipants']);
			}
			else {
				$notify_participants = $details['wfCancelNotifyParticipants'];
			}
			if (in_array('P', $notify_participants) === true) {
				$options= 1;
			}
				
			if (in_array('A', $notify_participants) === true) {
				$options = (!empty($options)) ? 3 : 2;
			}
		
			$this->setWorkflowStepActions($workflow_step, $message_code, 5, $options, $notify_list);
		}
		if ($details['wfEnableDelayMsg'] && $workflow_step->getStepType() > 1 && !empty($details['wfDelayMsg']))
		{
			$message_code	= $details['wfDelayMsg'];
			$notify_list	= $details['wfDelayNotifyList'];
			$options = 0;
			if (!is_array($details['wfDelayNotifyParticipants']))
			{
				$notify_participants = array($details['wfDelayNotifyParticipants']);
			}
			else {
				$notify_participants = $details['wfDelayNotifyParticipants'];
			}
			if (in_array('P', $notify_participants) === true) {
				$options= 1;
			}
				
			if (in_array('A', $notify_participants) === true) {
				$options = (!empty($options)) ? 3 : 2;
			}
		
			$due_days = !empty($details['wfDelayCompleteThreshold']) ? $details['wfDelayCompleteThreshold'] : 3;
		
			$this->setWorkflowStepActions($workflow_step, $message_code, 6, $options, $notify_list, $due_days);
		}
		if ($details['wfEnableDelayEsclMsg'] && $workflow_step->getStepType() > 1 && !empty($details['wfDelayEsclMsg']))
		{
			$message_code	= $details['wfDelayEsclMsg'];
			$notify_list	= $details['wfDelayEsclNotifyList'];
			$options = 0;
			if (!is_array($details['wfDelayEsclNotifyParticipants']))
			{
				$notify_participants = array($details['wfDelayEsclNotifyParticipants']);
			}
			else {
				$notify_participants = $details['wfDelayEsclNotifyParticipants'];
			}
			if (in_array('P', $notify_participants) === true) {
				$options= 1;
			}
				
			if (in_array('A', $notify_participants) === true) {
				$options = (!empty($options)) ? 3 : 2;
			}
		
			$due_days = !empty($details['wfDelayEsclThreshold']) ? $details['wfDelayEsclThreshold'] : 3;
		
			$this->setWorkflowStepActions($workflow_step, $message_code, 7, $options, $notify_list, $details['wfDelayEsclThreshold']);
		}
		
		return $workflow_step;
	}
	
	/**
	 * Append action for specific workflow item
	 * 
	 * @param \Docova\DocovaBundle\Entity\WorkflowSteps $workflow_step
	 * @param integer|string $message_code
	 * @param integer $action_type
	 * @param integer $options
	 * @param string $notify_list
	 * @param integer $threshold [optional()]
	 * @param string $decline_action [optional()]
	 * @param integer $backtrack [optional()]
	 * @return boolean
	 */
	private function setWorkflowStepActions($workflow_step, $message_code, $action_type, $options, $notify_list, $threshold = null, $decline_action = null, $backtrack = null)
	{
		if ($message_code !== null)
		{
			if (false === strpos($message_code, '-') && !preg_match('/[0-9]+/', $message_code))
			{
				$message_code = $this->_em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => true, 'Trash' => false, 'Message_Name' => 'DEFAULT_'.$message_code));
			}
			else {
				$message_code = $this->_em->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Systemic' => false, 'Trash'=> false, 'id' => $message_code));
			}
	
			if (empty($message_code))
			{
				throw new NotFoundHttpException('Unspecified default activate system message.');
			}
		}
			
		try {
			
			$step_action = new WorkflowStepActions();
			$step_action->setActionType($action_type);
			$step_action->setStep($workflow_step);
			if ($message_code !== null) 
			{
				$step_action->setSendMessage(true);
				$step_action->setToOptions($options);
				$step_action->setMessage($message_code);
			}
			
			if (!empty($threshold))
			{
				$step_action->setDueDays($threshold);
			}
			
			if (!empty($decline_action)) 
			{
				$step_action->setDeclineAction($decline_action);
				if ($decline_action == 2 && !empty($backtrack)) {
					$step = $this->_em->getRepository('DocovaBundle:WorkflowSteps')->findOneBy(array('id' => $backtrack, 'Trash' => false));
					if (!empty($step)) {
						$step_action->setBackTrackStep($step);
					}
				}
			}
		
			$this->_em->persist($step_action);
				
			if (!empty($notify_list))
			{
				$groups = array();
				$notify_list = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $notify_list);
				foreach ($notify_list as $index => $user)
				{
					if (false !== $user = $this->findUserAndCreate($user))
					{
						$step_action->addSendTo($user);
					}
					else {
						$groups[] = $notify_list[$index];
					}
				}
			}
			
			if (!empty($groups)) 
			{
				$step_action->setSenderGroups($groups);
			}
		
			$this->_em->flush();
			
			return true;
		}
		catch (\Exception $e)
		{
			var_dump($e->getMessage());
			return false;
		}
	}

	/**
	 * Find a user info in Database, if does not exist a user object can be created according to its info through LDAP server
	 *  
	 * @param string $username
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUserAndCreate($username)
	{
		$global_settings = $this->_em->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$global_settings = $global_settings[0];
		$tmpAbbName=$this->getUserAbbreviatedName($username);
		$user_obj = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated'=>$tmpAbbName));

		if (empty($user_obj) && $global_settings->getLDAPAuthentication())
		{
			$ldap_obj = new adLDAP(array(
				'domain_controllers' => $global_settings->getLDAPDirectory(), 
				'domain_port' => $global_settings->getLDAPPort(), 
				'base_dn' => $global_settings->getLdapBaseDn(), 
				'ad_username'=>$this->_params['ldap_username'], 
				'ad_password'=>$this->_params['ldap_password']
			));
			
			$arrUserName = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $tmpAbbName);
			$searchTxt=$arrUserName[0];			
			$searchTxt = str_replace('\\', '', $searchTxt);
			
			$filter = "( &(objectclass=person)(|(samaccountname=".$searchTxt.")(uid=".$searchTxt.")(userPrincipalName=".$searchTxt.")(cn=".$searchTxt.") ) )";
			
			$info = $ldap_obj->search_user($filter);
			if (empty($user_obj) && !empty($info['count']))
			{
				$create = true;
				if ($global_settings->getNumberOfUsers() > 0)
				{
					$user_count = $this->_em->getRepository('DocovaBundle:UserAccounts')->createQueryBuilder('U')
						->select('COUNT(U.id)')
						->where('U.Trash = false')
						->getQuery()
						->getSingleScalarResult();
				
					if ($global_settings->getNumberOfUsers() <= $user_count)
					{
						$create = false;
					}
				}
				
				$userUnid = '';
				if(!empty($this->_params['ldap_adkey'])	&& !empty($info[0][strtolower($this->_params['ldap_adkey'])][0])){
				    $userUnid = $info[0][strtolower($this->_params['ldap_adkey'])][0];
				}elseif(!empty($info[0]['samaccountname'][0])){
				    $userUnid = $info[0]['samaccountname'][0];
				}elseif(!empty($info[0]['uid'][0])){
				    $userUnid = $info[0]['uid'][0];
				}elseif(!empty($info[0]['userprincipalname'][0])){
				    $userUnid = $info[0]['userprincipalname'][0];
				}elseif(!empty($info[0]['mail'][0])){
				    $userUnid = $info[0]['mail'][0];  //TODO - review if this should be the case.  Mail might not be unique attribute
				}
				
				$mail = '';
				if(!empty($info[0]['mail'][0])){
				    $mail = $info[0]['mail'][0];
				}elseif(!empty($info[0]['userprincipalname'][0])){
				    $mail = $info[0]['userprincipalname'][0];
				}else{
				    $mail = $userUnid.'@unknown.unknown';
				}

				$ldap_dn_name = $info[0]['dn'];
				$tmpAbbName = $this->getUserAbbreviatedName($ldap_dn_name);
				
				
				if ($create === true) 
				{
					$user_obj = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username'=>$userUnid, 'userNameDnAbbreviated' => $tmpAbbName)); // duplicate checking
					if (!empty($user_obj)) { return $user_obj; }
					$user_obj = null;
					
					$user_obj = new UserAccounts();
					$user_obj->setUserMail($mail);
					$user_obj->setUsername($userUnid);
		
					//------------- add ldap dn and abbreviated name to user account     ----------
					$user_obj->setUserNameDn($ldap_dn_name); // raw dn format $users_list[$x]['dn']
					$user_obj->setUserNameDnAbbreviated($tmpAbbName); // abbreviate format
					//-----------------------------------------------------------------------------
		
					$default_role = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role'=>'ROLE_USER'));
					$user_obj->addRoles($default_role);
					$this->_em->persist($user_obj);
		
					$user_profile = new UserProfile();
					$user_profile->setFirstName((!empty($info[0]['givenname'][0])) ? $info[0]['givenname'][0] : $info[0]['cn'][0]);
					$user_profile->setLastName((!empty($info[0]['sn'][0])) ? $info[0]['sn'][0] : $info[0]['cn'][0]);
					$user_profile->setAccountType(false);
					$user_profile->setDisplayName((!empty($info[0]['displayname'][0])) ? $info[0]['displayname'][0] : $info[0]['cn'][0]);
					$user_profile->setUser($user_obj);
					if (!empty($mail))
					{
						if (false !== stripos($mail, '@gmail')) {
							$mailServerUrl = 'imap.gmail.com:993/imap/ssl';
						}
						elseif (false !== stripos($mail, '@yahoo') || stripos($mail, '@ymail')) {
							$mailServerUrl = 'imap.mail.yahoo.com:993/imap/ssl';
						}
						elseif (false !== stripos($mail, '@hotmail') || stripos($mail, '@live')) {
							$mailServerUrl = 'imap-mail.outlook.com:993/imap/ssl';
						}
						else {
							$mailServerUrl = $global_settings->getNotesMailServer() ? $global_settings->getNotesMailServer() : 'MAIL SERVER SHOULD BE SET MANUALLY.';
						}
					}
					else {
						$mailServerUrl = $global_settings->getNotesMailServer() ? $global_settings->getNotesMailServer() : 'MAIL SERVER SHOULD BE SET MANUALLY.';
					}
					$user_profile->setMailServerURL($mailServerUrl);
					$this->_em->persist($user_profile);
					$this->_em->flush();

				}
				else {
					$user_obj = array(
					    'mail' => $mail,
					    'username_dn_abbreviated' => $tmpAbbName,
					    'username_dn' => $ldap_dn_name,
					    'display_name' => (!empty($info[0]['displayname'][0]) ? $info[0]['displayname'][0] : $info[0]['cn'][0]),
					    'uid' => $userUnid
					);
				}
				$ldap_obj = $user_profile = null;
			}
		}
	
		if (!empty($user_obj)) {
			return $user_obj;
		}
	
		return false;
	}

	/**
	 * @param: string $userDnName e.g CN=DV Punia,O=DLI
	 * @return: string abbreviated name e.g DV Punia/DLI
	 */
	private function getUserAbbreviatedName($userDnName)
	{
	    try {
	        $strAbbDnName="";
	        $arrAbbName = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $userDnName);
	        if(count($arrAbbName) < 2){
	            $arrAbbName = preg_split('~\\\\.(*SKIP)(*FAIL)|\/~s', $userDnName);
	        }
	        
	        //create abbreviated name
	        foreach ($arrAbbName as $value){
	            if(trim($value) != ""){
	                $namepart = explode("=", $value);
	                $strAbbDnName .= (count($namepart) > 1 ? trim($namepart[1]) : trim($namepart[0]))."/";
	            }
	        }
	        //remove last "/"
	        $strAbbDnName=rtrim($strAbbDnName,"/");
	        
	    } catch (\Exception $e) {
	        //$this->log->error("Bad name found in Ldap (user_dn): ". $user_dn[0]);
	        var_dump("WorkflowServices::getUserAbbreviatedName() exception".$e->getMessage());
	    }
	    
	    
	    return $strAbbDnName;
	}
	
	
}