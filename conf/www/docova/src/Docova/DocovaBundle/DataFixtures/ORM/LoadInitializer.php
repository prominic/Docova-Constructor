<?php
namespace Docova\DocovaBundle\DataFixtures\ORM;

use Docova\DocovaBundle\Entity\UserRoles;
use Docova\DocovaBundle\Entity\Widgets;
use Docova\DocovaBundle\Entity\SystemPerspectives;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Docova\DocovaBundle\Entity\GlobalSettings;
use Docova\DocovaBundle\Entity\UserAccounts;
use Docova\DocovaBundle\Entity\UserProfile;
use Docova\DocovaBundle\Entity\FacetMaps;
use Docova\DocovaBundle\Entity\DocumentTypes;
use Docova\DocovaBundle\Entity\DocTypeSubforms;
use Docova\DocovaBundle\Entity\Subforms;
use Docova\DocovaBundle\Entity\DesignElements;
use Docova\DocovaBundle\Entity\Workflow;
use Docova\DocovaBundle\Entity\WorkflowSteps;
use Docova\DocovaBundle\Entity\WorkflowStepActions;
use Docova\DocovaBundle\Entity\SystemMessages;
use Docova\DocovaBundle\Entity\FileTemplates;
use Docova\DocovaBundle\Entity\Activities;
use Docova\DocovaBundle\Entity\SubformActionButtons;
use Docova\DocovaBundle\Entity\UserScriptFunction;
use Docova\DocovaBundle\Entity\ViewColumns;
use Docova\DocovaBundle\Entity\Libraries;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Entity\Folders;
use Docova\DocovaBundle\Entity\FoldersLog;
use Docova\DocovaBundle\Entity\CustomSearchFields;

class LoadInitializer implements FixtureInterface, ContainerAwareInterface
{
	private $container;

	public function setContainer(ContainerInterface $container = null)
	{
		$this->container = $container;
	}

	public function load(ObjectManager $manager)
	{
		/****************************************** Create default roles (User/Admin) ******************************************/
		$exists = $manager->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_USER'));
		if (empty($exists))
		{
			$role = new UserRoles();
			$role->setRole('ROLE_USER');
			$role->setDisplayName('User');
			$manager->persist($role);
			$manager->flush();
			unset($role);
		}
			
		$exists = $manager->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_ADMIN'));
		if (empty($exists))
		{
			$role = new UserRoles();
			$role->setRole('ROLE_ADMIN');
			$role->setDisplayName('Administrator');
			$manager->persist($role);
			$manager->flush();
			unset($role);
		}

		/********************************************* Create default admin user ***********************************************/
		$init_file = $this->container->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR.'init_client_instance.xml';
		if (file_exists($init_file)) {
			$xml = new \DOMDocument();
			if ($xml->load($init_file)) {
				$user_account = new UserAccounts();
				$user_account->setUsername($xml->getElementsByTagName('clientadminuser')->item(0)->nodeValue);
				$user_account->setUserMail($xml->getElementsByTagName('clientadminemail')->item(0)->nodeValue);
				$user_account->setPassword(md5(md5(md5($xml->getElementsByTagName('clientadminpass')->item(0)->nodeValue))));
				$user_account->setUserNameDnAbbreviated($xml->getElementsByTagName('clientadmindisplayname')->item(0)->nodeValue.'/DOCOVA');
				$role = $manager->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_ADMIN'));
				$user_account->addRoles($role);
				$manager->persist($user_account);
				$manager->flush();
					
				$user_profile = new UserProfile();
				$user_profile->setAccountType(true);
				$user_profile->setFirstName($xml->getElementsByTagName('clientadminfirstname')->item(0)->nodeValue);
				$user_profile->setLastName($xml->getElementsByTagName('clientadminlastname')->item(0)->nodeValue);
				$user_profile->setDisplayName($xml->getElementsByTagName('clientadmindisplayname')->item(0)->nodeValue);
				$user_profile->setUser($user_account);
				$user_profile->setMailServerURL('mails.sample.com');
				$user_profile->setCanCreateApp(true);
				$user_profile->setNotifyUser(true);
				$manager->persist($user_profile);
				$manager->flush();
				unset($user_account, $user_profile, $role);
			}
		}

		$user = $manager->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => 'DOCOVA SE', 'userNameDnAbbreviated' => 'DOCOVA SE/DOCOVA'));
		if (empty($user)) 
		{
			$user_account = new UserAccounts();
			$user_account->setUsername('DOCOVA SE');
			$user_account->setUserMail('dli_tools@dlitools.com');
			if (file_exists($init_file)) {
				$user_account->setPassword(md5(md5(md5($xml->getElementsByTagName('docovasepass')->item(0)->nodeValue))));
			}
			else {
				$user_account->setPassword(md5(md5(md5('password12'))));
			}
			$user_account->setUserNameDnAbbreviated('DOCOVA SE/DOCOVA');
			$role = $manager->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_ADMIN'));
			$user_account->addRoles($role);
			$manager->persist($user_account);
			$manager->flush();
			
			$user_profile = new UserProfile();
			$user_profile->setAccountType(true);
			$user_profile->setFirstName('DOCOVA');
			$user_profile->setLastName('SE');
			$user_profile->setDisplayName('DOCOVA SE\DOCOVA');
			$user_profile->setUser($user_account);
			$user_profile->setMailServerURL('mails.sample.com');
			$manager->persist($user_profile);
			$manager->flush();
			$user = $user_account;
			unset($user_account, $user_profile, $role);
		}
		
		if (empty($user)) 
		{
			return false;
		}
		
		/******************************************** Add custom php functions (hide when action buttons) **********************************************/
		$exists = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showStartWorkflow'));
		if (empty($exists)) 
		{
			$function = '$doc_steps = $document_context->getDocSteps();
if ($doc_steps->count() > 0) {

   foreach ($doc_steps as $step)
   {
       if (($step->getIsCurrentStep() && $step->getStatus() === \'Pending\' || $step->getStatus() === \'Paused\') && $step->getStepType(true) === \'Start\')
       {
           foreach ($step->getAssignee() as $record) {
               if ($record->getAssignee()->getId() == $user->getId()) {
                   return true;
               }
           }
       }
   }
   return false;
}
return false;';
			$custom_script = new UserScriptFunction();
			$custom_script->setFunctionName('showStartWorkflow');
			$custom_script->setFunctionArguments('$document_context, $user');
			$custom_script->setReturnType('boolean');
			$custom_script->setFunctionScript($function);
			$manager->persist($custom_script);
			$manager->flush();
			
			unset($custom_script, $function);
		}

		$exists = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showCompleteReview'));
		if (empty($exists))
		{
			$function = '$doc_steps = $document_context->getDocSteps();
if ($doc_steps->count() > 0) {
   foreach($doc_steps as $step) {
       if ($step->getStatus() === \'Pending\' || $step->getStatus() === \'Paused\') {
           if ($step->getStepType(true) === \'Review\') {
               $matched = false;
               foreach ($step->getAssignee() as $record) {
                   if ($record->getAssignee()->getId() === $user->getId()) {
                       $matched = true;
                       break;
                   }
               }
               
               if ($matched === true) 
               {
                   if ($step->getHideReading() && false !== strpos($_SERVER[\'REQUEST_URI\'], \'/ReadDocument/\'))
                   {
                       return false;
                   }
                   if ($step->getHideEditing() && false !== strpos($_SERVER[\'REQUEST_URI\'], \'/EditDocument/\'))
                   {
                       return false;
                   }
                   return true;
               }
           }
           break;
       }
   }
}
return false;';
			$custom_script = new UserScriptFunction();
			$custom_script->setFunctionName('showCompleteReview');
			$custom_script->setFunctionArguments('$document_context, $user');
			$custom_script->setReturnType('boolean');
			$custom_script->setFunctionScript($function);
			$manager->persist($custom_script);
			$manager->flush();
			
			unset($custom_script, $function);
		}

		$exists = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showRleaseDocument'));
		if (empty($exists))
		{
			$function = '$doc_steps = $document_context->getDocSteps();
if ($doc_steps->count() > 0) {
   foreach ($doc_steps as $step) {
       if ($step->isCurrentStep() && ($step->getStatus() === \'Pending\' || $step->getStatus() === \'Paused\')) {
           if ($step->getStepType(true) === \'End\') {
               $matched = false;
               foreach ($step->getAssignee() as $record) {
                   if ($record->getAssignee()->getId() === $user->getId()) {
                       $matched = true;
                       break;
                   }
               }
                
               if ($matched === true)
               {
                   if ($step->getHideReading() && false !== strpos($_SERVER[\'REQUEST_URI\'], \'/ReadDocument/\'))
                   {
                       return false;
                   }
                   if ($step->getHideEditing() && false !== strpos($_SERVER[\'REQUEST_URI\'], \'/EditDocument/\'))
                   {
                       return false;
                   }
                   return true;
               }
           }
           break;
       }
   }
}
return false;';
			$custom_script = new UserScriptFunction();
			$custom_script->setFunctionName('showRleaseDocument');
			$custom_script->setFunctionArguments('$document_context, $user');
			$custom_script->setReturnType('boolean');
			$custom_script->setFunctionScript($function);
			$manager->persist($custom_script);
			$manager->flush();
			
			unset($custom_script, $function);
		}

		$exists = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showApprove'));
		if (empty($exists))
		{
			$function = '$doc_steps = $document_context->getDocSteps();
if ($doc_steps->count () > 0) {
   foreach ($doc_steps as $step) {
       if ($step->getIscurrentStep()) {
           if ($step->getStepType(true) === \'Approve\') {
               $matched = false;
               foreach ($step->getAssignee() as $record) {
                   if ($record->getAssignee()->getId() === $user->getId()) {
                       $matched = true;
                       break;
                   }
               }
               if ($matched === true) 
               {
                   if ($step->getHideReading() && false !== strpos($_SERVER[\'REQUEST_URI\'], \'/ReadDocument/\'))
                   {
                       return false;
                   }
                   if ($step->getHideEditing() && false !== strpos($_SERVER[\'REQUEST_URI\'], \'/EditDocument/\'))
                   {
                       return false;
                   }
                   return true;
               }
           }
           break;
       }
   }
}
return false;';
			$custom_script = new UserScriptFunction();
			$custom_script->setFunctionName('showApprove');
			$custom_script->setFunctionArguments('$document_context, $user');
			$custom_script->setReturnType('boolean');
			$custom_script->setFunctionScript($function);
			$manager->persist($custom_script);
			$manager->flush();
			
			unset($custom_script, $function);
		}

		$exists = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showDecline'));
		if (empty($exists))
		{
			$function = '$doc_steps = $document_context->getDocSteps();
if ($doc_steps->count() > 0) {
   foreach ($doc_steps as $step) {
       if ($step->getIscurrentStep()) {
           if ($step->getStepType(true) === \'Approve\') {
               $matched = false;
               foreach ($step->getAssignee() as $record) {
                   if ($record->getAssignee()->getId() === $user->getId()) {
                       $matched = true;
                       break;
                   }
               }
               if ($matched === true) 
               {
                   if ($step->getHideReading() && false !== strpos($_SERVER[\'REQUEST_URI\'], \'/ReadDocument/\'))
                   {
                       return false;
                   }
                   if ($step->getHideEditing() && false !== strpos($_SERVER[\'REQUEST_URI\'], \'/EditDocument/\'))
                   {
                       return false;
                   }
                   return true;
               }
           }
           break;
       }
   }
}
return false;';
			$custom_script = new UserScriptFunction();
			$custom_script->setFunctionName('showDecline');
			$custom_script->setFunctionArguments('$document_context, $user');
			$custom_script->setReturnType('boolean');
			$custom_script->setFunctionScript($function);
			$manager->persist($custom_script);
			$manager->flush();
			
			unset($custom_script, $function);
		}

		$exists = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showWorkflow'));
		if (empty($exists))
		{
			$function = '$doc_steps = $document_context->getDocSteps();
if ($doc_steps->count() > 0)
{
   foreach ($doc_steps as $step)
   {
       if ($step->getStatus() !== \'Completed\' && $step->getStatus() !== \'Approved\')
       {
           return true;
       }
   }
}
return false;';
			$custom_script = new UserScriptFunction();
			$custom_script->setFunctionName('showWorkflow');
			$custom_script->setFunctionArguments('$document_context, $user');
			$custom_script->setReturnType('boolean');
			$custom_script->setFunctionScript($function);
			$manager->persist($custom_script);
			$manager->flush();
			
			unset($custom_script, $function);
		}

		/********************************************* Add meta data for TextEditor subform ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-TextEditor'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-TextEditor');
			$subform->setFormName('Plain Text Editor');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
		
			$fields = new DesignElements();
			$fields->setFieldName('editor_body');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
			$manager->flush();
		
			unset($subform, $fields);
		}

		/********************************************* Add meta data for DocovaEditor subform ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-DocovaEditor'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-DocovaEditor');
			$subform->setFormName('Docova Text Editor');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
		
			$fields = new DesignElements();
			$fields->setFieldName('body');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
			$manager->flush();
		
			unset($subform, $fields);
		}

		/********************************************* Add meta data for Attachments subform ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Attachments'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-Attachments');
			$subform->setFormName('Attachments');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
				
			$fields = new DesignElements();
			$fields->setFieldName('mcsf_dliuploader1');
			$fields->setFieldType(5);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
			$manager->flush();
				
			unset($subform, $fields);
		}

		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-AttachmentsIntl'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-AttachmentsIntl');
			$subform->setFormName('Attachments Intl');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
		
			$fields = new DesignElements();
			$fields->setFieldName('mcsf_dliuploader1');
			$fields->setFieldType(5);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
			$manager->flush();
		
			unset($subform, $fields);
		}
		
		/********************************************* Add meta data for RelatedDocuments subform ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RelatedDocuments'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-RelatedDocuments');
			$subform->setFormName('Related Documents');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
			$manager->flush();
				
			unset($subform);
		}
		
		/********************************************* Add meta data for Workflow subform ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
		if (empty($exists)) 
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-Workflow');
			$subform->setFormName('Workflows');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
			$manager->flush();
			
			unset($subform);
		}
		
		/******************************************* Add meta data for RichTextEditor Subform  ******************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RTEditor'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-RTEditor');
			$subform->setFormName('Rich Text Editor');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
		
			$fields = new DesignElements();
			$fields->setFieldName('body');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
			$manager->flush();
							
			unset($subform, $fields);
		}

		/********************************************* Add meta data for TextContentReadOnly subform ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-TextContentReadOnly'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-TextContentReadOnly');
			$subform->setFormName('Text Content Read-only');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
		
			$fields = new DesignElements();
			$fields->setFieldName('terbody');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
			$manager->flush();
		
			unset($subform, $fields);
		}

		/******************************************* Add meta data for MailMemo Subform **********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-HeaderMemo'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-HeaderMemo');
			$subform->setFormName('Mail Memo');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
		
			$fields = new DesignElements();
			$fields->setFieldName('mail_date');
			$fields->setFieldType(1);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
		
			$fields = new DesignElements();
			$fields->setFieldName('mail_from');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
		
			$fields = new DesignElements();
			$fields->setFieldName('mail_to');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
					
			$fields = new DesignElements();
			$fields->setFieldName('mail_cc');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
		
			$fields = new DesignElements();
			$fields->setFieldName('mail_bcc');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
		
			$fields = new DesignElements();
			$fields->setFieldName('mail_subject');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
			$manager->flush();
				
			unset($subform, $fields);
		}

		/********************************************* Add meta data for AdvancedComments subform ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-AdvancedComments'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-AdvancedComments');
			$subform->setFormName('Advanced Comments');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
			$manager->flush();
		
			unset($subform);
		}
		
		/*********************************************** Add meta data for Web bookmark subform *************************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-WebBookmark'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-WebBookmark');
			$subform->setFormName('Web bookmark');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
			$manager->flush();

			$fields = new DesignElements();
			$fields->setFieldName('bookmarkurl');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);
			
			$fields = new DesignElements();
			$fields->setFieldName('showpreview');
			$fields->setFieldType(0);
			$fields->setModifiedBy($user);
			$fields->setDateModified(new \DateTime());
			$fields->setSubform($subform);
			$manager->persist($fields);

			$manager->flush();
			unset($subform);
		}
		/********************************************* Add meta data for RelatedEmails subform ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RelatedEmails'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-RelatedEmails');
			$subform->setFormName('Related Emails');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
			$manager->flush();
		
			unset($subform);
		}

		/********************************************* Add meta data for RelatedLinks subform ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RelatedLinks'));
		if (empty($exists))
		{
			$subform = new Subforms();
			$subform->setFormFileName('sfDocSection-RelatedLinks');
			$subform->setFormName('Related Links');
			$subform->setCreator($user);
			$subform->setModifiedBy($user);
			$subform->setDateCreated(new \DateTime());
			$manager->persist($subform);
			$manager->flush();
		
			unset($subform);
		}
		
		/******************************************* Add Action Buttons *******************************************/
		$exists = $manager->getRepository('DocovaBundle:SubformActionButtons')->findOneBy(array('Action_Name' => 'Start_Workflow'));
		if (empty($exists))
		{
			$function	= $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showStartWorkflow'));
			$subform	= $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
			$action_button = new SubformActionButtons();
			$action_button->setActionName('Start_Workflow');
			$action_button->setButtonLabel('Start Workflow');
			$action_button->setPrimaryIcon('far fa-check-circle');
			$action_button->setClickScript('if(!CanModifyDocument()){return false;}
StartWorkflow();
return false;');
			$action_button->setVisibleOn($function);
			$action_button->setSubform($subform);
			$manager->persist($action_button);
			$manager->flush();
				
			unset($action_button, $function, $subform);
		}
		
		$exists = $manager->getRepository('DocovaBundle:SubformActionButtons')->findOneBy(array('Action_Name' => 'Complete_Review'));
		if (empty($exists))
		{
			$function = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showCompleteReview'));
			$subform	= $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
			$action_button = new SubformActionButtons();
			$action_button->setActionName('Complete_Review');
			$action_button->setButtonLabel('Complete Review');
			$action_button->setPrimaryIcon('far fa-check-circle');
			$action_button->setClickScript('if(!CanModifyDocument()){return false;}
CompleteWorkflowStep();
return false;');
			$action_button->setVisibleOn($function);
			$action_button->setSubform($subform);
			$manager->persist($action_button);
			$manager->flush();
		
			unset($action_button, $function, $subform);
		}
		
		$exists = $manager->getRepository('DocovaBundle:SubformActionButtons')->findOneBy(array('Action_Name' => 'Release_Document'));
		if (empty($exists))
		{
			$function = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showRleaseDocument'));
			$subform	= $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
			$action_button = new SubformActionButtons();
			$action_button->setActionName('Release_Document');
			$action_button->setButtonLabel('Release Document');
			$action_button->setPrimaryIcon('far fa-check-circle');
			$action_button->setClickScript('if(!CanModifyDocument()){return false;}
if (countApprovalsRemaining() > 1){
        alert(\'The document will not be released yet, as additional approvals are still pending for this workflow step.\');
        ApproveWorkflowStep();
}else{
        FinishWorkflow();
}
return false;');
			$action_button->setVisibleOn($function);
			$action_button->setSubform($subform);
			$manager->persist($action_button);
			$manager->flush();
		
			unset($action_button, $function, $subform);
		}
		
		$exists = $manager->getRepository('DocovaBundle:SubformActionButtons')->findOneBy(array('Action_Name' => 'Approve'));
		if (empty($exists))
		{
			$function = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showApprove'));
			$subform	= $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
			$action_button = new SubformActionButtons();
			$action_button->setActionName('Approve');
			$action_button->setButtonLabel('Approve');
			$action_button->setPrimaryIcon('far fa-check-circle');
			$action_button->setClickScript('ApproveWorkflowStep(); return false;');
			$action_button->setVisibleOn($function);
			$action_button->setSubform($subform);
			$manager->persist($action_button);
			$manager->flush();
		
			unset($action_button, $function, $subform);
		}
		
		$exists = $manager->getRepository('DocovaBundle:SubformActionButtons')->findOneBy(array('Action_Name' => 'Decline'));
		if (empty($exists))
		{
			$function = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showDecline'));
			$subform	= $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
			$action_button = new SubformActionButtons();
			$action_button->setActionName('Decline');
			$action_button->setButtonLabel('Decline');
			$action_button->setPrimaryIcon('far fa-times-circle');
			$action_button->setClickScript('DenyWorkflowStep(); return false;');
			$action_button->setVisibleOn($function);
			$action_button->setSubform($subform);
			$manager->persist($action_button);
			$manager->flush();
		
			unset($action_button, $function, $subform);
		}
		
		$exists = $manager->getRepository('DocovaBundle:SubformActionButtons')->findOneBy(array('Action_Name' => 'Workflow'));
		if (empty($exists))
		{
			$function = $manager->getRepository('DocovaBundle:UserScriptFunction')->findOneBy(array('Function_Name' => 'showWorkflow'));
			$subform	= $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Workflow'));
			$action_button = new SubformActionButtons();
			$action_button->setActionName('Workflow');
			$action_button->setButtonLabel('Workflow');
			$action_button->setPrimaryIcon('far fa-project-diagram');
			$action_button->setSecondaryIcon('fas fa-caret-down');
			$action_button->setClickScript('CreateWorkflowSubmenu(this); return false;');
			$action_button->setVisibleOn($function);
			$action_button->setSubform($subform);
			$manager->persist($action_button);
			$manager->flush();
		
			unset($action_button, $function, $subform);
		}
		
		$exists = $manager->getRepository('DocovaBundle:SubformActionButtons')->findOneBy(array('Action_Name' => 'Linked_Files'));
		if (empty($exists))
		{
			$subform	= $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RelatedDocuments'));
			$action_button = new SubformActionButtons();
			$action_button->setActionName('Linked_Files');
			$action_button->setButtonLabel('Linked Files');
			$action_button->setClickScript('CreateLinkedOfficeFilesSubmenu(document.getElementById(\'C\' + this.id))');
			$action_button->setSubform($subform);
			$manager->persist($action_button);
			$manager->flush();
		
			unset($action_button, $subform);
		}

		/******************************************** Create File Document type ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => 'File Document'));
		if (empty($exists)) 
		{
			$document_type = new DocumentTypes();
			$document_type->setStatus(true);
			$document_type->setDocName('File Document');
			$document_type->setDescription('Stores file attachments');
			$document_type->setPaperColor('FFFFFF');
			$document_type->setHideSubject(false);
			$document_type->setSubjectLabel('Subject/Title');
			$document_type->setMoreSectionLabel('More');
			$document_type->setSectionLabel('Content');
			$document_type->setBackgroundColor('DFDFDF');
			$document_type->setAdditionalCss('padding:4px;width:100%;');
			$document_type->setEnableMailAcquire(false);
			$document_type->setForwardSave(2);
			$document_type->setContentSections(array('ATT','N','N','N','N','N','N','N'));
			$document_type->setEnableLifecycle(true);
			$document_type->setInitialStatus('Draft');
			$document_type->setFinalStatus('Released');
			$document_type->setSupersededStatus('Inactive');
			$document_type->setDiscardedStatus('Discarded');
			$document_type->setArchivedStatus('Archived');
			$document_type->setDeletedStatus('Deleted');
			$document_type->setEnableVersions(true);
			$document_type->setCustomReviewButtonLabel('Mark Reviewed');
			$document_type->setCursorFocusField('Subject');
			$document_type->setValidateChoice(1);
			$document_type->setValidateFieldNames('Subject');
			$document_type->setValidateFieldTypes('text');
			$document_type->setValidateFieldLabels('Document Title/Subject');
			$document_type->setUpdateFullTextIndex(false);
			$document_type->setDateCreated(new \DateTime());
			$document_type->setCreator($user);
			$manager->persist($document_type);

			$subform = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Attachments'));
			if (!empty($subform))
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties><MaxFiles>0</MaxFiles><TemplateType>None</TemplateType><TemplateKey></TemplateKey><TemplateName></TemplateName><TemplateFileName></TemplateFileName><TemplateVersion></TemplateVersion><TemplateAutoAttachment>1</TemplateAutoAttachment><Interface><AttachmentReadOnly></AttachmentReadOnly><AttachmentLabel>Attachments</AttachmentLabel><AttachmentsHeight>75</AttachmentsHeight><GenerateThumbnails>1</GenerateThumbnails><ThumbnailWidth>100</ThumbnailWidth><ThumbnailHeight>100</ThumbnailHeight><AllowedFileExtensions></AllowedFileExtensions><EnableFileViewLogging></EnableFileViewLogging><EnableFileDownloadLogging></EnableFileDownloadLogging><EnableLocalScan></EnableLocalScan><CustomScanJS></CustomScanJS><EnableFileCIAO></EnableFileCIAO><ListType>I</ListType><HideAttachButtons></HideAttachButtons><HideOnReading></HideOnReading><HideOnEditing></HideOnEditing><HideOnCustom></HideOnCustom><CustomAttachmentsHideWhen></CustomAttachmentsHideWhen><HasMultiAttachmentSection></HasMultiAttachmentSection></Interface></Properties>';
		
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($document_type);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setPropertiesXML($property_xml);
				$doctype_subform->setSubformOrder(1);
				$manager->persist($doctype_subform);
			}
			$manager->flush();
			unset($subform, $doctype_subform, $property_xml, $document_type);
		}
		
		/***************************************** Add Standard Document type ******************************************/
		$exists = $manager->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => 'Standard Document'));
		if (empty($exists)) 
		{
			$document_type = new DocumentTypes();
			$document_type->setStatus(true);
			$document_type->setDocName('Standard Document');
			$document_type->setDescription('Used to store miscellaneous documents');
			$document_type->setPaperColor('FFFFFF');
			$document_type->setHideSubject(false);
			$document_type->setSubjectLabel('Subject/Title');
			$document_type->setMoreSectionLabel('More');
			$document_type->setSectionLabel('Content');
			$document_type->setBackgroundColor('DFDFDF');
			$document_type->setAdditionalCss('padding:4px;width:100%;');
			$document_type->setEnableDiscussion(true);
			$document_type->setForwardSave(2);
			$document_type->setContentSections(array('TXT','ATT','N','N','N','N','N','N'));
			$document_type->setCursorFocusField('Subject');
			$document_type->setValidateChoice(1);
			$document_type->setValidateFieldNames('Subject');
			$document_type->setValidateFieldTypes('text');
			$document_type->setValidateFieldLabels('Document Title/Subject');
			$document_type->setDateCreated(new \DateTime());
			$document_type->setCreator($user);
			$manager->persist($document_type);
				
			$subform = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-TextEditor'));
			if (!empty($subform)) 
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties><Interface><EditorReadOnly></EditorReadOnly><EditorLabel>Text Content</EditorLabel><EditorHeightValue>150</EditorHeightValue><HideOnReading></HideOnReading><HideOnEditing></HideOnEditing><HideOnCustom></HideOnCustom><CustomTextAreaHideWhen></CustomTextAreaHideWhen></Interface></Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($document_type);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setPropertiesXML($property_xml);
				$doctype_subform->setSubformOrder(1);
				$manager->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}

			$subform = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Attachments'));
			if (!empty($subform)) 
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties><MaxFiles>1</MaxFiles><TemplateType>None</TemplateType><TemplateKey></TemplateKey><TemplateName></TemplateName><TemplateFileName></TemplateFileName><TemplateVersion></TemplateVersion><TemplateAutoAttachment>1</TemplateAutoAttachment><Interface><AttachmentReadOnly></AttachmentReadOnly><AttachmentLabel>Attachments</AttachmentLabel><AttachmentsHeight>100</AttachmentsHeight><GenerateThumbnails></GenerateThumbnails><ThumbnailWidth></ThumbnailWidth><ThumbnailHeight></ThumbnailHeight><AllowedFileExtensions></AllowedFileExtensions><EnableFileViewLogging></EnableFileViewLogging><EnableFileDownloadLogging></EnableFileDownloadLogging><EnableLocalScan>1</EnableLocalScan><CustomScanJS></CustomScanJS><EnableFileCIAO>1</EnableFileCIAO><ListType>I</ListType><HideAttachButtons></HideAttachButtons><HideOnReading></HideOnReading><HideOnEditing></HideOnEditing><HideOnCustom></HideOnCustom><CustomAttachmentsHideWhen></CustomAttachmentsHideWhen><HasMultiAttachmentSection></HasMultiAttachmentSection></Interface></Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($document_type);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setPropertiesXML($property_xml);
				$doctype_subform->setSubformOrder(2);
				$manager->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
			$manager->flush();
		}

		/******************************************** Create Mail Memo type ***********************************************/
		$exists = $manager->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => 'Mail Memo'));
		if (empty($exists))
		{
			$document_type = new DocumentTypes();
			$document_type->setStatus(true);
			$document_type->setDocName('Mail Memo');
			$document_type->setDescription('Stores the mail memo contents');
			$document_type->setKeyType(3);
			$document_type->setPaperColor('FFFFFF');
			$document_type->setHideSubject(true);
			$document_type->setSubjectLabel('Subject/Title');
			$document_type->setMoreSectionLabel('More');
			$document_type->setBackgroundColor('DFDFDF');
			$document_type->setAdditionalCss('padding:4px;width:100%;');
			$document_type->setEnableMailAcquire(false);
			$document_type->setForwardSave(2);
			$document_type->setContentSections(array('MEMO','RTXT','ATT','N','N','N','N','N'));
			$document_type->setCustomReviewButtonLabel('Mark Reviewed');
			$document_type->setCursorFocusField('Subject');
			$document_type->setValidateChoice(0);
			$document_type->setDateCreated(new \DateTime());
			$document_type->setCreator($user);
			$manager->persist($document_type);

			$subform = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-HeaderMemo'));
			if (!empty($subform))
			{
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($document_type);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setSubformOrder(1);
				$manager->persist($doctype_subform);
				unset($subform, $doctype_subform);
			}

			$subform = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-RTEditor'));
			if (!empty($subform)) 
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties><Interface><EditorReadOnly>1</EditorReadOnly><EditorLabel>Message Content</EditorLabel><EditorHeightValue>500</EditorHeightValue><HideOnReading></HideOnReading><HideOnEditing>1</HideOnEditing><HideOnCustom></HideOnCustom><CustomTextAreaHideWhen></CustomTextAreaHideWhen></Interface></Properties>';
				
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($document_type);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setPropertiesXML($property_xml);
				$doctype_subform->setSubformOrder(2);
				$manager->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}
			

			$subform = $manager->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => 'sfDocSection-Attachments'));
			if (!empty($subform))
			{
				$property_xml = '<?xml version="1.0" encoding="UTF-8" ?><Properties><MaxFiles>0</MaxFiles><TemplateType>None</TemplateType><TemplateKey></TemplateKey><TemplateName></TemplateName><TemplateFileName></TemplateFileName><TemplateVersion></TemplateVersion><TemplateAutoAttachment>1</TemplateAutoAttachment><Interface><AttachmentReadOnly></AttachmentReadOnly><AttachmentLabel>Attachments</AttachmentLabel><AttachmentsHeight>100</AttachmentsHeight><GenerateThumbnails></GenerateThumbnails><ThumbnailWidth></ThumbnailWidth><ThumbnailHeight></ThumbnailHeight><AllowedFileExtensions></AllowedFileExtensions><EnableFileViewLogging></EnableFileViewLogging><EnableFileDownloadLogging></EnableFileDownloadLogging><EnableLocalScan></EnableLocalScan><CustomScanJS></CustomScanJS><EnableFileCIAO></EnableFileCIAO><ListType>I</ListType><HideAttachButtons></HideAttachButtons><HideOnReading></HideOnReading><HideOnEditing></HideOnEditing><HideOnCustom></HideOnCustom><CustomAttachmentsHideWhen></CustomAttachmentsHideWhen><HasMultiAttachmentSection></HasMultiAttachmentSection></Interface></Properties>';
			
				$doctype_subform = new DocTypeSubforms();
				$doctype_subform->setDocType($document_type);
				$doctype_subform->setSubform($subform);
				$doctype_subform->setPropertiesXML($property_xml);
				$doctype_subform->setSubformOrder(3);
				$manager->persist($doctype_subform);
				unset($subform, $doctype_subform, $property_xml);
			}			
			
			
			$manager->flush();
		}

		/***************************************** Create Default System Messages *******************************************/
		$exists = $manager->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_ACTIVATE'));
		if (empty($exists)) 
		{
			$message = <<<CONTENT
The document workflow step [wfTitle] for document [Subject] is now pending.

Please follow the link below to open the [applicationname] application and review the document.

[varLink]
CONTENT;
			$system_message = new SystemMessages();
			$system_message->setMessageName('DEFAULT_ACTIVATE');
			$system_message->setSubjectLine('The document workflow step [wfTitle] for document [Subject] is now pending.');
			$system_message->setSystemic(true);
			$system_message->setLinkType(false);
			$system_message->setMessageContent($message);
			$manager->persist($system_message);
			$manager->flush();
			
			unset($system_message, $message);
		}

		$exists = $manager->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_COMPLETE'));
		if (empty($exists))
		{
			$message = <<<CONTENT
The document workflow step [wfTitle] for document [Subject] has been completed.

Please follow the link below to open the [applicationname] application and review the document.

[varLink]
CONTENT;
			$system_message = new SystemMessages();
			$system_message->setMessageName('DEFAULT_COMPLETE');
			$system_message->setSubjectLine('The document workflow step [wfTitle] for document [Subject] has been completed.');
			$system_message->setSystemic(true);
			$system_message->setLinkType(false);
			$system_message->setMessageContent($message);
			$manager->persist($system_message);
			$manager->flush();
			
			unset($system_message, $message);
		}

		$exists = $manager->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_DECLINE'));
		if (empty($exists))
		{
			$message = <<<CONTENT
The approval step [wfTitle] for document [Subject] has been declined by [webuser] for the following reason(s):
[usercomment].

Please follow the link below to open the [applicationname] application and review the document.

[varLink]
CONTENT;
			$system_message = new SystemMessages();
			$system_message->setMessageName('DEFAULT_DECLINE');
			$system_message->setSubjectLine('The approval step [wfTitle] for document [Subject] has been declined.');
			$system_message->setSystemic(true);
			$system_message->setLinkType(false);
			$system_message->setMessageContent($message);
			$manager->persist($system_message);
			$manager->flush();
			
			unset($system_message, $message);
		}
		
		$exists = $manager->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_PAUSE'));
		if (empty($exists))
		{
			$message = <<<CONTENT
The workflow for document [Subject] has been paused by [webuser] for the following reason(s):
[usercomment].

Please follow the link below to open the [applicationname] application and review the document.

[varLink]
CONTENT;
			$system_message = new SystemMessages();
			$system_message->setMessageName('DEFAULT_PAUSE');
			$system_message->setSubjectLine('The workflow for document [Subject] has been paused.');
			$system_message->setSystemic(true);
			$system_message->setLinkType(false);
			$system_message->setMessageContent($message);
			$manager->persist($system_message);
			$manager->flush();
			
			unset($system_message, $message);
		}
		
		$exists = $manager->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_CANCEL'));
		if (empty($exists))
		{
			$message = <<<CONTENT
The workflow for document [Subject] has been cancelled by [webuser] for the following reason(s):
[usercomment].

Please follow the link below to open the [applicationname] application and review the document.

[varLink]
CONTENT;
			$system_message = new SystemMessages();
			$system_message->setMessageName('DEFAULT_CANCEL');
			$system_message->setSubjectLine('The workflow for document [Subject] has been cancelled.');
			$system_message->setSystemic(true);
			$system_message->setLinkType(false);
			$system_message->setMessageContent($message);
			$manager->persist($system_message);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_DELAY'));
		if (empty($exists))
		{
			$message = <<<CONTENT
The following documents are past due for review or approval:

[doclinks]
CONTENT;
			$system_message = new SystemMessages();
			$system_message->setMessageName('DEFAULT_DELAY');
			$system_message->setSubjectLine('[applicationname] - past due document review/approval notification.');
			$system_message->setSystemic(true);
			$system_message->setLinkType(false);
			$system_message->setMessageContent($message);
			$manager->persist($system_message);
			$manager->flush();
		}

		$exists = $manager->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_DELAYESCL'));
		if (empty($exists))
		{
			$message = <<<CONTENT
The following documents are past due for review or approval:
		
[doclinks]
CONTENT;
			$system_message = new SystemMessages();
			$system_message->setMessageName('DEFAULT_DELAYESCL');
			$system_message->setSubjectLine('[applicationname] - past due document review/approval notification.');
			$system_message->setSystemic(true);
			$system_message->setLinkType(false);
			$system_message->setMessageContent($message);
			$manager->persist($system_message);
			$manager->flush();
		}

		$exists = $manager->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_REVIEWACTIVATE'));
		if (empty($exists))
		{
			$message = <<<CONTENT
The following document requires review:

Due: [dueDate], Document: [doclink]
CONTENT;
			$system_message = new SystemMessages();
			$system_message->setMessageName('DEFAULT_REVIEWACTIVATE');
			$system_message->setSubjectLine('[applicationname] - document review notification.');
			$system_message->setSystemic(true);
			$system_message->setLinkType(false);
			$system_message->setMessageContent($message);
			$manager->persist($system_message);
			$manager->flush();
				
			unset($system_message, $message);
		}

		/************************************************ Add Built-In View Columns ***************************************************/
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'apflag', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Allow Preview');
			$view_column->setFieldName('AllowPreviewFlag');
			$view_column->setXMLName('apflag');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}

		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'CF35', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Attachment Dates (Original)');
			$view_column->setFieldName('{% set att_dates = f_ArrayConcat(f_AttachmentModifiedTimes()) %}
{% set output = \'\' %}
{% if att_dates|length %}
{% for item in att_dates %}
{% set output = output ~ item.format(\'m/d/Y\') ~ \'; \' %}
{% endfor %}
{% set output = output[:(output|length - 2)] %}
{% endif %}
{{ output }}');
			$view_column->setXMLName('CF35');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}

		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F33', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Attachment Lengths');
			$view_column->setFieldName('{% set att_length = f_ArrayConcat(f_AttachmentLengths()) %}
{% set output = \'\' %}
{% if att_length|length %}
{% for item in att_length %}
{% if f_Round(item/1024) <= 1024 %}
{% set output = output ~ f_Round(item/1024) ~ \'KB; \' %}
{% else %}
{% set output = output ~ f_Round((item/1024)/1024) ~ \'MB; \' %}
{% endif %}
{% endfor %}
{% set output = output[:(output|length - 2)] %}
{% endif %}
{{ output }}');
			$view_column->setXMLName('F33');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}

		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F17', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Attachments');
			$view_column->setFieldName('{{ f_AttachmentNames()|join(\';\') }}');
			$view_column->setXMLName('F17');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F1', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Author');
			$view_column->setFieldName('Author');
			$view_column->setXMLName('F1');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'bmk', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Bookmark');
			$view_column->setFieldName('Bookmarks');
			$view_column->setXMLName('bmk');
			$view_column->setColumnType(true);
			$view_column->setDataType(4);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F19', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Category');
			$view_column->setFieldName('Document_Category');
			$view_column->setXMLName('F19');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F13', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Checked Out By');
			$view_column->setFieldName('CheckedOutEditor');
			$view_column->setXMLName('F13');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F14', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Checked Out Date');
			$view_column->setFieldName('CheckedOutDate');
			$view_column->setXMLName('F14');
			$view_column->setColumnType(true);
			$view_column->setDataType(2);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F2', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Created By');
			$view_column->setFieldName('CreatedBy');
			$view_column->setXMLName('F2');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F3', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Created Date');
			$view_column->setFieldName('Date_Created');
			$view_column->setXMLName('F3');
			$view_column->setColumnType(true);
			$view_column->setDataType(2);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F4', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Date');
			$view_column->setFieldName('Date_Created');
			$view_column->setXMLName('F4');
			$view_column->setColumnType(true);
			$view_column->setDataType(2);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F32', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Deleted By');
			$view_column->setFieldName('{{ f_Name(\'[CN]\', f_FetchUser(document[\'Deleted_By\'])) }}');
			$view_column->setXMLName('F32');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F31', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Deleted Date');
			$view_column->setFieldName('Date_Deleted');
			$view_column->setXMLName('F31');
			$view_column->setColumnType(true);
			$view_column->setDataType(2);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F5', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Description');
			$view_column->setFieldName('Description');
			$view_column->setXMLName('F5');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'delflag', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Disable Delete');
			$view_column->setFieldName('Disable_Delete_In_Workflow');
			$view_column->setXMLName('delflag');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F26', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Folder Name');
			$view_column->setFieldName('{{ f_FolderName(document[\'Folder_Id\']) }}');
			$view_column->setXMLName('F26');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F34', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Folder Path');
			$view_column->setFieldName('{{ f_FolderPath(document[\'Folder_Id\']) }}');
			$view_column->setXMLName('F34');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F6', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Icon');
			$view_column->setFieldName('Doc_Icon');
			$view_column->setXMLName('F6');
			$view_column->setColumnType(true);
			$view_column->setDataType(5);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'wfstarted', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Is Workflow Started');
			$view_column->setFieldName('{{ f_IsWorkflowStarted() ? \'Yes\' : \'\' }}');
			$view_column->setXMLName('wfstarted');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F30', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Item Type');
			$view_column->setFieldName('<? $value = key_exists(\'Doc_Version\', $documents[$x]) ? "&#157;" : "&#204;"; ?>');
			$view_column->setXMLName('F30');
			$view_column->setColumnType(true);
			$view_column->setDataType(4);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'libname', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Library Name');
			$view_column->setFieldName('{{ library ? library.getLibraryTitle : documents[\'Library_Title\'] }}');
			$view_column->setXMLName('libname');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F28', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Locked By');
			$view_column->setFieldName('{{ document[\'Locked\'] ? document[\'ModifiedBy\'] : \'\' }}');
			$view_column->setXMLName('F28');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F29', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Locked On');
			$view_column->setFieldName('<? $value = !empty($documents[$x][\'Locked\']) ? $documents[$x][\'Date_Modified\'] : null; ?>');
			$view_column->setXMLName('F29');
			$view_column->setColumnType(true);
			$view_column->setDataType(2);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F11', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Modified By');
			$view_column->setFieldName('ModifiedBy');
			$view_column->setXMLName('F11');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F12', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Modified Date');
			$view_column->setFieldName('Date_Modified');
			$view_column->setXMLName('F12');
			$view_column->setColumnType(true);
			$view_column->setDataType(2);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F15', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Released By');
			$view_column->setFieldName('{{ f_Name(\'[CN]\', f_FetchUser(document[\'Released_By\'])) }}');
			$view_column->setXMLName('F15');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F16', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Released Date');
			$view_column->setFieldName('Released_Date');
			$view_column->setXMLName('F16');
			$view_column->setColumnType(true);
			$view_column->setDataType(2);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'recflag', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('RME Enabled');
			$view_column->setFieldName('RME');
			$view_column->setXMLName('recflag');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'ftscore', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Search Score');
			$view_column->setFieldName('searchScore');
			$view_column->setXMLName('ftscore');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F7', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Status');
			$view_column->setFieldName('Doc_Status');
			$view_column->setXMLName('F7');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}

		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'statno', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Status No');
			$view_column->setFieldName('Status_No');
			$view_column->setXMLName('statno');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(50);
			$manager->persist($view_column);
			$manager->flush();
		}

		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'versflag', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Strict Versioning');
			$view_column->setFieldName('Strict_Versioning');
			$view_column->setXMLName('versflag');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F8', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Title');
			$view_column->setFieldName('Doc_Title');
			$view_column->setXMLName('F8');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F9', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Type');
			$view_column->setFieldName('Doc_Name');
			$view_column->setXMLName('F9');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F10', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Version');
			$view_column->setFieldName('<{{ document[\'Doc_Version\'] ? document[\'Doc_Version\'] ~ \'.\' ~ document[\'Revision\'] : \'\' }}');
			$view_column->setXMLName('F10');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}
		
		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'verflag', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Versioning Enabled');
			$view_column->setFieldName('Enable_Versions');
			$view_column->setXMLName('verflag');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(100);
			$manager->persist($view_column);
			$manager->flush();
		}

		$exists = $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'flags', 'Column_Type' => true));
		if (empty($exists))
		{
			$view_column = new ViewColumns();
			$view_column->setTitle('Flags');
			$view_column->setFieldName('{{ f_GetFlag(document[\'Doc_Type_Id\']) }}');
			$view_column->setXMLName('flags');
			$view_column->setColumnType(true);
			$view_column->setDataType(1);
			$view_column->setWidth(1);
			$manager->persist($view_column);
			$manager->flush();
		}

		/*************************************** Add Default Facet Map *****************************************/
		$exists = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		if (empty($exists))
		{
			$column_author	= $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F1', 'Column_Type' => true));
			$column_type	= $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F9', 'Column_Type' => true));
			$column_status	= $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F7', 'Column_Type' => true));
		
			$facet_map = new FacetMaps();
			$facet_map->setFacetMapName('Default Facet Map');
			$facet_map->setDescription('Default Facet Map for docova');
			$facet_map->setDefaultFacet('facetmap');
			$facet_map->setFacetMapFields('F1:1,F9:2,F7:3');
			$facet_map->addFields($column_author);
			$facet_map->addFields($column_type);
			$facet_map->addFields($column_status);
			$manager->persist($facet_map);
			$manager->flush();
			unset($facet_map, $column_author, $column_status, $column_type);
		}
		
		$exists = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Search Facet Map'));
		if (empty($exists))
		{
			$column_libname	= $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'libname', 'Column_Type' => true));
			$column_fpath	= $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F34', 'Column_Type' => true));
			$column_title	= $manager->getRepository('DocovaBundle:ViewColumns')->findOneBy(array('XML_Name' => 'F8', 'Column_Type' => true));
		
			$facet_map = new FacetMaps();
			$facet_map->setFacetMapName('Default Search Facet Map');
			$facet_map->setDescription('Default Search Facet Map');
			$facet_map->setDefaultFacet('search');
			$facet_map->setFacetMapFields('libname:1,F34:2,F8:3');
			$facet_map->addFields($column_libname);
			$facet_map->addFields($column_fpath);
			$facet_map->addFields($column_title);
			$manager->persist($facet_map);
			$manager->flush();
			unset($facet_map, $column_libname, $column_fpath, $column_title);
		}

		/*************************************** Create Default Workflow/Steps *****************************************/
		$exists = $manager->getRepository('DocovaBundle:Workflow')->findOneBy(array('Workflow_Name' => 'AdHoc Workflow'));
		if (empty($exists))
		{
			$workflow = new Workflow();
			$workflow->setWorkflowName('AdHoc Workflow');
			$workflow->setDescription('Originator selects the necessary steps');
			$workflow->setUseDefaultDoc(true);
			$manager->persist($workflow);
			
			$workflow_step = new WorkflowSteps();
			$workflow_step->setStepName('Submit draft');
			$workflow_step->setStepType(1);
			$workflow_step->setPosition(0);
			$workflow_step->setWorkflow($workflow);
			$manager->persist($workflow_step);
			
			$workflow_step = new WorkflowSteps();
			$workflow_step->setStepName('Review document');
			$workflow_step->setStepType(2);
			$workflow_step->setPosition(1);
			$workflow_step->setDocStatus('In Review');
			$workflow_step->setDistribution(1);
			$workflow_step->setParticipants(3);
			$workflow_step->setWorkflow($workflow);
			$manager->persist($workflow_step);
			$review_step = $workflow_step;
			
			$workflow_step = new WorkflowSteps();
			$workflow_step->setStepName('Approve document');
			$workflow_step->setStepType(3);
			$workflow_step->setPosition(2);
			$workflow_step->setDocStatus('Awaiting Approval');
			$workflow_step->setDistribution(1);
			$workflow_step->setParticipants(3);
			$workflow_step->setWorkflow($workflow);
			$manager->persist($workflow_step);
			
			$workflow_step = new WorkflowSteps();
			$workflow_step->setStepName('Publish');
			$workflow_step->setStepType(4);
			$workflow_step->setPosition(3);
			$workflow_step->setDocStatus('Ready to Publish');
			$workflow_step->setDistribution(1);
			$workflow_step->setParticipants(3);
			$workflow_step->setWorkflow($workflow);
			$manager->persist($workflow_step);
			
			$manager->flush();
			$manager->refresh($review_step);
			
			$message = $manager->getRepository('DocovaBundle:SystemMessages')->findOneBy(array('Message_Name' => 'DEFAULT_ACTIVATE'));
			$step_action = new WorkflowStepActions();
			$step_action->setSendMessage(true);
			$step_action->setToOptions(0);
			$step_action->setActionType(1);
			$step_action->setMessage($message);
			$step_action->setStep($review_step);
			$manager->persist($step_action);
			$manager->flush();
			
			unset($workflow, $workflow_step, $review_step, $step_action);
		}

		// ---------- create default file templates -----------
		//1. Excel: Sample-Budget-Template.xls
		$exists = $manager->getRepository('DocovaBundle:FileTemplates')->findOneBy(array('Template_Name' => 'Sample-Budget'));
		if(empty($exists))
		{
			$file_template = new FileTemplates();
			$file_template->setTemplateName("Sample-Budget");
			$file_template->setTemplateType("Excel");
			$file_template->setTemplateVersion("1");
			$file_template->setFileName("Sample-Budget-Template.xls");
			$file_template->setFileMimeType("application/vnd.ms-excel");
			$file_template->setFileSize(78);
			$file_template->setDateCreated(new \DateTime()); // back space to ignore symfony date time
			$manager->persist($file_template);
			$manager->flush();

			unset($file_template);
		}
		
		// 2. Word: InsuranceLetter.doc
		$exists = $manager->getRepository('DocovaBundle:FileTemplates')->findOneBy(array('Template_Name' => 'Blank Letterhead template'));
		if(empty($exists))
		{
			$file_template = new FileTemplates();
			$file_template->setTemplateName("Blank Letterhead template");
			$file_template->setTemplateType("Word");
			$file_template->setTemplateVersion("1");
			$file_template->setFileName("InsuranceLetter.doc");
			$file_template->setFileMimeType("application/msword");
			$file_template->setFileSize(78);
			$file_template->setDateCreated(new \DateTime()); // back space to ignore symfony date time
			$manager->persist($file_template);
			$manager->flush();

			unset($file_template);
		}
		
		
		// 3. Word: InsuranceLetter.doc
		$exists = $manager->getRepository('DocovaBundle:FileTemplates')->findOneBy(array('Template_Name' => 'Sample-RFP'));
		if(empty($exists))
		{
			$file_template = new FileTemplates();
			$file_template->setTemplateName("Sample-RFP");
			$file_template->setTemplateType("Word");
			$file_template->setTemplateVersion("1");
			$file_template->setFileName("Sample-RFP-Template.doc");
			$file_template->setFileMimeType("application/msword");
			$file_template->setFileSize(60);
			$file_template->setDateCreated(new \DateTime()); // back space to ignore symfony date time
			$manager->persist($file_template);
			$manager->flush();

			unset($file_template);
		}
		
		
		// 4. Word: InsuranceLetter.doc
		$exists = $manager->getRepository('DocovaBundle:FileTemplates')->findOneBy(array('Template_Name' => 'Sample - Contract'));
		if(empty($exists))
		{
			$file_template = new FileTemplates();
			$file_template->setTemplateName("Sample - Contract");
			$file_template->setTemplateType("Word");
			$file_template->setTemplateVersion("1");
			$file_template->setFileName("Sample-Sales-Contract.doc");
			$file_template->setFileMimeType("application/msword");
			$file_template->setFileSize(62);
			$file_template->setDateCreated(new \DateTime()); // back space to ignore symfony date time
			$manager->persist($file_template);
			$manager->flush();

			unset($file_template);
		}
		
		// 5. Word: InsuranceLetter.doc
		$exists = $manager->getRepository('DocovaBundle:FileTemplates')->findOneBy(array('Template_Name' => 'Sample - Proposal'));
		if(empty($exists))
		{
			$file_template = new FileTemplates();
			$file_template->setTemplateName("Sample - Proposal");
			$file_template->setTemplateType("Word");
			$file_template->setTemplateVersion("1");
			$file_template->setFileName("Sample Proposal Template.doc");
			$file_template->setFileMimeType("application/msword");
			$file_template->setFileSize(62);
			$file_template->setDateCreated(new \DateTime()); // back space to ignore symfony date time
			$manager->persist($file_template);
			$manager->flush();

			unset($file_template);
		}
		
		//--------------- create default activities ---------------------
		//1. Acknowledgement
		$exists = $manager->getRepository('DocovaBundle:Activities')->findOneBy(array('activityAction' => 'Acknowledgement'));
		if(empty($exists))
		{
			$activity = new Activities();
			$activity->setActivityAction("Acknowledgement");
			$activity->setActivitySubject('Subject');
			$activity->setActivityObligation(1);
			$activity->setActivitySendMessage(false);
			$activity->setActivityInstruction("Please acknowledge that you have read and understand this document.");
			$manager->persist($activity); // create entity record
			$manager->flush();

			unset($activity);
		}
		
		//2. Adhoc
		$exists = $manager->getRepository('DocovaBundle:Activities')->findOneBy(array('activityAction' => 'Adhoc'));
		if(empty($exists))
		{
			$activity = new Activities();
			$activity->setActivityAction("Adhoc");
			$activity->setActivityObligation(0);
			$activity->setActivitySendMessage(false);
			$manager->persist($activity); // create entity record
			$manager->flush();

			unset($activity);
		}
		
		//3. Collaboration
		$exists = $manager->getRepository('DocovaBundle:Activities')->findOneBy(array('activityAction' => 'Collaboration'));
		if(empty($exists))
		{
			$activity = new Activities();
			$activity->setActivityAction("Collaboration");
			$activity->setActivityObligation(1);
			$activity->setActivitySendMessage(false);
			$activity->setActivityInstruction("This document is ready, please make any changes before I start the workflow.");
			$manager->persist($activity); // create entity record
			$manager->flush();

			unset($activity);
		}
		
		//4. Adhoc
		$exists = $manager->getRepository('DocovaBundle:Activities')->findOneBy(array('activityAction' => 'FYI'));
		if(empty($exists))
		{
			$activity = new Activities();
			$activity->setActivityAction("FYI");
			$activity->setActivityObligation(1);
			$activity->setActivitySendMessage(false);
			$activity->setActivityInstruction("Please read this document.  It is simply for your information, no acknowledgement is needed.");
			$manager->persist($activity); // create entity record
			$manager->flush();

			unset($activity);
		}
		
		//5. Question
		$exists = $manager->getRepository('DocovaBundle:Activities')->findOneBy(array('activityAction' => 'Question'));
		if(empty($exists))
		{
			$activity = new Activities();
			$activity->setActivityAction("Question");
			$activity->setActivityObligation(0);
			$activity->setActivitySendMessage(true);
			$activity->setActivityInstruction(" have a question about this document.");
			$manager->persist($activity); // create entity record
			$manager->flush();

			unset($activity);
		}

		/***************************************** System Default perspective ****************************************/
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'System Default', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('System Default');
			$perspective->setDescription('Basic view columns, no formatting.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setDefaultFor('Folder');
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('Folder,Global Search,Recycle Bin');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings><viewproperties><showSelectionMargin>1</showSelectionMargin><allowCustomization>1</allowCustomization><extendLastColumn/><isSummary/><categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle></viewproperties><columns><column><isCategorized/><hasCustomSort/><totalType>0</totalType><isFrozen/><isFreezeControl/><title/><xmlNodeName>bmk</xmlNodeName><dataType>html</dataType><sortOrder>none</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>20</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT>6pt</fontSizeT><fontFamilyT/><colorT/><fontWeightT/><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH>6pt</fontSizeH><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Title</title><xmlNodeName>F8</xmlNodeName><dataType>text</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>180</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Ver.</title><xmlNodeName>F10</xmlNodeName><dataType>text</dataType><sortOrder>none</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>50</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Status</title><xmlNodeName>F7</xmlNodeName><dataType>text</dataType><sortOrder>none</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>80</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Type</title><xmlNodeName>F9</xmlNodeName><dataType>text</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>150</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Author</title><xmlNodeName>F1</xmlNodeName><dataType>text</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>160</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Date</title><xmlNodeName>F4</xmlNodeName><dataType>date</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>100</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column></columns></viewsettings>');
		
			$manager->persist($perspective, $facet_map);
			$manager->flush();
			unset($perspective);
		}

		/***************************************** Global Search perspective ****************************************/
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'Global search', 'Is_System' => true));
		if (empty($exists)) 
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
			
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('Global search');
			$perspective->setDescription('Used in multi library searches');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setDefaultFor('Global Search');
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('Global Search');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings><viewproperties><isSummary/><showSelectionMargin/><allowCustomization>1</allowCustomization><extendLastColumn/><categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle></viewproperties><columns><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Apps/Libraries</title><xmlNodeName>libname</xmlNodeName><dataType>text</dataType><sortOrder>none</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>120</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Folder path</title><xmlNodeName>F34</xmlNodeName><dataType>text</dataType><sortOrder>none</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>140</width><align/><fontSize/><fontFamily/><color>#0033cc</color><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Title</title><xmlNodeName>F8</xmlNodeName><dataType>text</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>174</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Type</title><xmlNodeName>F9</xmlNodeName><dataType>text</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>100</width><align/><fontSize/><fontFamily/><color>#003366</color><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Author</title><xmlNodeName>F1</xmlNodeName><dataType>text</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>169</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Date</title><xmlNodeName>F4</xmlNodeName><dataType>date</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>90</width><align/><fontSize/><fontFamily/><color>#006666</color><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column></columns></viewsettings>');
		
			$manager->persist($perspective, $facet_map);
			$manager->flush();
			unset($perspective);
		}

		/***************************************** Recycle Bin perspective ****************************************/
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'Recycle bin default', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('Recycle bin default');
			$perspective->setDescription('Default perspective for recycle bin.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setDefaultFor('Recycle Bin');
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('Recycle Bin');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings><viewproperties><showSelectionMargin>1</showSelectionMargin><allowCustomization>1</allowCustomization><extendLastColumn/><isSummary/><categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle></viewproperties><columns><column><isCategorized/><hasCustomSort/><totalType>0</totalType><isFrozen/><isFreezeControl/><title/><xmlNodeName>F30</xmlNodeName><dataType>html</dataType><sortOrder>none</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>20</width><align/><fontSize>12pt</fontSize><fontFamily>Webdings</fontFamily><color>#ff9900</color><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Title</title><xmlNodeName>F8</xmlNodeName><dataType>text</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>170</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Type</title><xmlNodeName>F9</xmlNodeName><dataType>text</dataType><sortOrder>none</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>140</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>Deleted by</title><xmlNodeName>F32</xmlNodeName><dataType>names</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>170</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column><column><isCategorized/><hasCustomSort>1</hasCustomSort><totalType>0</totalType><isFrozen/><isFreezeControl/><title>on</title><xmlNodeName>F31</xmlNodeName><dataType>date</dataType><sortOrder>ascending</sortOrder><customSortOrder>none</customSortOrder><numberFormat>###.##;-###.##</numberFormat><numberPrefix/><numberSuffix/><dateFormat/><width>170</width><align/><fontSize/><fontFamily/><color/><fontWeight/><fontStyle/><textDecoration/><backgroundColor/><alignT/><fontSizeT/><fontFamilyT/><colorT>#0000ff</colorT><fontWeightT>bold</fontWeightT><fontStyleT/><textDecorationT/><backgroundColorT/><alignH/><fontSizeH/><fontFamilyH/><colorH/><fontWeightH/><fontStyleH/><textDecorationH/><backgroundColorH/></column></columns></viewsettings>');
		
			$manager->persist($perspective, $facet_map);
			$manager->flush();
			unset($perspective);
		}

		// ********************************* Librariess Perspective ***********************************/		
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminLibraries', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminLibraries');
			$perspective->setDescription('Perspective used by administration library view.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Title</title>
                 <xmlNodeName>title</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title></title>
                 <xmlNodeName>status</xmlNodeName>
                 <dataType>html</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>40</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Path</title>
                 <xmlNodeName>path</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>350</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Server</title>
                 <xmlNodeName>server</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
</columns>
</viewsettings>');
		
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		// ********************************* Application Perspective ***********************************/
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminApplications', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminApplications');
			$perspective->setDescription('Perspective used by administration application view.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Title</title>
                 <xmlNodeName>title</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title></title>
                 <xmlNodeName>status</xmlNodeName>
                 <dataType>html</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>40</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Path</title>
                 <xmlNodeName>path</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>350</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Server</title>
                 <xmlNodeName>server</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
</columns>
</viewsettings>');
		
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		// ********************************* Document Types Perspective ***********************************/
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminDocTypes', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminDocTypes');
			$perspective->setDescription('Perspective used by Administration view.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Document Type</title>
                 <xmlNodeName>doctype</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>180</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
           <column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Status</title>
                 <xmlNodeName>disable-icon</xmlNodeName>
                 <dataType>html</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>50</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT/>
                 <fontWeightT/>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Description</title>
                 <xmlNodeName>description</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>400</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
</columns>
</viewsettings>');
		
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}
		
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminWorkflow', 'Is_System' => true));
		if (empty($exists)) 
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
			
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminWorkflow');
			$perspective->setDescription('perspective for administration workflow list');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
	      <viewproperties>
	            <showSelectionMargin>1</showSelectionMargin>
	            <allowCustomization></allowCustomization>
	            <extendLastColumn></extendLastColumn>
	            <isSummary/>
	            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
	    </viewproperties>
	      <columns>
	            <column>
	                 <isCategorized/>
	                 <hasCustomSort></hasCustomSort>
	                 <totalType>0</totalType>
	                 <isFrozen/>
	                 <isFreezeControl/>
	                 <title></title>
	                 <xmlNodeName>sortorder</xmlNodeName>
	                 <dataType>text</dataType>
	                 <sortOrder>ascending</sortOrder>
	                 <customSortOrder>none</customSortOrder>
	                 <numberFormat>###.##;-###.##</numberFormat>
	                 <numberPrefix/><numberSuffix/>
	                 <dateFormat/>
	                 <width>0</width>
	                 <align/>
	                 <fontSize/>
	                 <fontFamily/>
	                 <color/>
	                 <fontWeight/>
	                 <fontStyle/>
	                 <textDecoration/>
	                 <backgroundColor/>
	                 <alignT/>
	                 <fontSizeT/>
	                 <fontFamilyT/>
	                 <colorT>#000000</colorT>
	                 <fontWeightT>bold</fontWeightT>
	                 <fontStyleT/>
	                 <textDecorationT/>
	                 <backgroundColorT/>
	                 <alignH/>
	                 <fontSizeH/>
	                 <fontFamilyH/>
	                 <colorH/>
	                 <fontWeightH/>
	                 <fontStyleH/>
	                 <textDecorationH/>
	                 <backgroundColorH/>
	            </column>
		<column>
	                 <isCategorized/>
	                 <hasCustomSort></hasCustomSort>
	                 <totalType>0</totalType>
	                 <isFrozen/>
	                 <isFreezeControl/>
	                 <title></title>
	                 <xmlNodeName>default-icon</xmlNodeName>
	                 <dataType>html</dataType>
	                 <sortOrder></sortOrder>
	                 <customSortOrder>none</customSortOrder>
	                 <numberFormat>###.##;-###.##</numberFormat>
	                 <numberPrefix/><numberSuffix/>
	                 <dateFormat/>
	                 <width>40</width>
	                 <align/>
	                 <fontSize/>
	                 <fontFamily/>
	                 <color/>
	                 <fontWeight/>
	                 <fontStyle/>
	                 <textDecoration/>
	                 <backgroundColor/>
	                 <alignT/>
	                 <fontSizeT/>
	                 <fontFamilyT/>
	                 <colorT/>
	                 <fontWeightT/>
	                 <fontStyleT/>
	                 <textDecorationT/>
	                 <backgroundColorT/>
	                 <alignH/>
	                 <fontSizeH/>
	                 <fontFamilyH/>
	                 <colorH/>
	                 <fontWeightH/>
	                 <fontStyleH/>
	                 <textDecorationH/>
	                 <backgroundColorH/>
	            </column>
	         <column>
	                 <isCategorized></isCategorized>
	                 <hasCustomSort></hasCustomSort>
	                 <totalType>0</totalType>
	                 <isFrozen/>
	                 <isFreezeControl/>
	                 <title>Workflow Name</title>
	                 <xmlNodeName>workflowname</xmlNodeName>
	                 <dataType>text</dataType>
	                 <sortOrder></sortOrder>
	                 <customSortOrder>none</customSortOrder>
	                 <numberFormat>###.##;-###.##</numberFormat>
	                 <numberPrefix/><numberSuffix/>
	                 <dateFormat/>
	                 <width>180</width>
	                 <align/>
	                 <fontSize/>
	                 <fontFamily/>
	                 <color/>
	                 <fontWeight/>
	                 <fontStyle/>
	                 <textDecoration/>
	                 <backgroundColor/>
	                 <alignT/>
	                 <fontSizeT/>
	                 <fontFamilyT/>
	                 <colorT>#0000ff</colorT>
	                 <fontWeightT>bold</fontWeightT>
	                 <fontStyleT/>
	                 <textDecorationT/>
	                 <backgroundColorT/>
	                 <alignH/>
	                 <fontSizeH/>
	                 <fontFamilyH/>
	                 <colorH/>
	                 <fontWeightH/>
	                 <fontStyleH/>
	                 <textDecorationH/>
	                 <backgroundColorH/>
	            </column>
	            <column>
	                 <isCategorized/>
	                 <hasCustomSort></hasCustomSort>
	                 <totalType>0</totalType>
	                 <isFrozen/>
	                 <isFreezeControl/>
	                 <title>Title</title>
	                 <xmlNodeName>title</xmlNodeName>
	                 <dataType>text</dataType>
	                 <sortOrder></sortOrder>
	                 <customSortOrder>none</customSortOrder>
	                 <numberFormat>###.##;-###.##</numberFormat>
	                 <numberPrefix/><numberSuffix/>
	                 <dateFormat/>
	                 <width>180</width>
	                 <align/>
	                 <fontSize/>
	                 <fontFamily/>
	                 <color/>
	                 <fontWeight/>
	                 <fontStyle/>
	                 <textDecoration/>
	                 <backgroundColor/>
	                 <alignT/>
	                 <fontSizeT/>
	                 <fontFamilyT/>
	                 <colorT>#0000ff</colorT>
	                 <fontWeightT>bold</fontWeightT>
	                 <fontStyleT/>
	                 <textDecorationT/>
	                 <backgroundColorT/>
	                 <alignH/>
	                 <fontSizeH/>
	                 <fontFamilyH/>
	                 <colorH/>
	                 <fontWeightH/>
	                 <fontStyleH/>
	                 <textDecorationH/>
	                 <backgroundColorH/>
	            </column>
	            <column>
	                 <isCategorized/>
	                 <hasCustomSort></hasCustomSort>
	                 <totalType>0</totalType>
	                 <isFrozen/>
	                 <isFreezeControl/>
	                 <title>Type</title>
	                 <xmlNodeName>type</xmlNodeName>
	                 <dataType>text</dataType>
	                 <sortOrder></sortOrder>
	                 <customSortOrder>none</customSortOrder>
	                 <numberFormat>###.##;-###.##</numberFormat>
	                 <numberPrefix/><numberSuffix/>
	                 <dateFormat/>
	                 <width>180</width>
	                 <align/>
	                 <fontSize/>
	                 <fontFamily/>
	                 <color/>
	                 <fontWeight/>
	                 <fontStyle/>
	                 <textDecoration/>
	                 <backgroundColor/>
	                 <alignT/>
	                 <fontSizeT/>
	                 <fontFamilyT/>
	                 <colorT>#0000ff</colorT>
	                 <fontWeightT>bold</fontWeightT>
	                 <fontStyleT/>
	                 <textDecorationT/>
	                 <backgroundColorT/>
	                 <alignH/>
	                 <fontSizeH/>
	                 <fontFamilyH/>
	                 <colorH/>
	                 <fontWeightH/>
	                 <fontStyleH/>
	                 <textDecorationH/>
	                 <backgroundColorH/>
	            </column>
	            <column>
	                 <isCategorized/>
	                 <hasCustomSort></hasCustomSort>
	                 <totalType>0</totalType>
	                 <isFrozen/>
	                 <isFreezeControl/>
	                 <title>Distribution</title>
	                 <xmlNodeName>distribution</xmlNodeName>
	                 <dataType>text</dataType>
	                 <sortOrder></sortOrder>
	                 <customSortOrder>none</customSortOrder>
	                 <numberFormat>###.##;-###.##</numberFormat>
	                 <numberPrefix/><numberSuffix/>
	                 <dateFormat/>
	                 <width>180</width>
	                 <align/>
	                 <fontSize/>
	                 <fontFamily/>
	                 <color/>
	                 <fontWeight/>
	                 <fontStyle/>
	                 <textDecoration/>
	                 <backgroundColor/>
	                 <alignT/>
	                 <fontSizeT/>
	                 <fontFamilyT/>
	                 <colorT>#0000ff</colorT>
	                 <fontWeightT>bold</fontWeightT>
	                 <fontStyleT/>
	                 <textDecorationT/>
	                 <backgroundColorT/>
	                 <alignH/>
	                 <fontSizeH/>
	                 <fontFamilyH/>
	                 <colorH/>
	                 <fontWeightH/>
	                 <fontStyleH/>
	                 <textDecorationH/>
	                 <backgroundColorH/>
	            </column>
	            <column>
	                 <isCategorized/>
	                 <hasCustomSort></hasCustomSort>
	                 <totalType>0</totalType>
	                 <isFrozen/>
	                 <isFreezeControl/>
	                 <title>Participants</title>
	                 <xmlNodeName>participants</xmlNodeName>
	                 <dataType>text</dataType>
	                 <sortOrder></sortOrder>
	                 <customSortOrder>none</customSortOrder>
	                 <numberFormat>###.##;-###.##</numberFormat>
	                 <numberPrefix/><numberSuffix/>
	                 <dateFormat/>
	                 <width>180</width>
	                 <align/>
	                 <fontSize/>
	                 <fontFamily/>
	                 <color/>
	                 <fontWeight/>
	                 <fontStyle/>
	                 <textDecoration/>
	                 <backgroundColor/>
	                 <alignT/>
	                 <fontSizeT/>
	                 <fontFamilyT/>
	                 <colorT>#0000ff</colorT>
	                 <fontWeightT>bold</fontWeightT>
	                 <fontStyleT/>
	                 <textDecorationT/>
	                 <backgroundColorT/>
	                 <alignH/>
	                 <fontSizeH/>
	                 <fontFamilyH/>
	                 <colorH/>
	                 <fontWeightH/>
	                 <fontStyleH/>
	                 <textDecorationH/>
	                 <backgroundColorH/>
	            </column>
	</columns>
	</viewsettings>');
			
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminReviewPolicies', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
				
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminReviewPolicies');
			$perspective->setDescription('perspective for Admin Review Policies');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized>1</isCategorized>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Policy Status</title>
                 <xmlNodeName>policyStatus</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>150</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color>#0000ff</color>
                 <fontWeight>bold</fontWeight>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                  <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized></isCategorized>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Library</title>
                 <xmlNodeName>libraries</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Priority</title>
                 <xmlNodeName>policyPriority</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>65</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Policy Name</title>
                 <xmlNodeName>policyName</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Document Types</title>
                 <xmlNodeName>documenttypes</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Description</title>
                 <xmlNodeName>description</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>none</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                <color>#008000</color>
                  <fontWeight>bold</fontWeight>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
</columns>
</viewsettings>');
				
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminFileTemplates', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminFileTemplates');
			$perspective->setDescription('Web Admin for File Templates.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized>1</isCategorized>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Type</title>
                 <xmlNodeName>type</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color>#0000ff</color>
                 <fontWeight>bold</fontWeight>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                  <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
               <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Template Name</title>
                 <xmlNodeName>templatename</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>400</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
       </columns>
</viewsettings>');
		
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminViewColumns', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminViewColumns');
			$perspective->setDescription('Web Admin perpsective for View  Column Settings.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized>1</isCategorized>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Column Type</title>
                 <xmlNodeName>columntype</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>120</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color>#0000ff</color>
                 <fontWeight>bold</fontWeight>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                  <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Column Title</title>
                 <xmlNodeName>columntitle</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>datatype</title>
                 <xmlNodeName>datatype</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>80</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>xmlNode</title>
                 <xmlNodeName>xmlNode</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>80</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
<column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title></title>
                 <xmlNodeName>disable-icon</xmlNodeName>
                 <dataType>html</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>40</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT/>
                 <fontWeightT/>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Data Query</title>
                 <xmlNodeName>dataquery</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>none</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>500</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
    </columns>
</viewsettings>');
		
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminUserProfiles', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
				
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminUserProfiles ');
			$perspective->setDescription('Administration user profiles perspective.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>User</title>
                 <xmlNodeName>user</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>250</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Last Access</title>
                 <xmlNodeName>lastaccess</xmlNodeName>
                 <dataType>date</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>400</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
</columns>
</viewsettings>');
				
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminGroups', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminGroups ');
			$perspective->setDescription('Administration groups perspective.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
	<viewproperties>
		<showSelectionMargin>1</showSelectionMargin>
		<allowCustomization></allowCustomization>
		<extendLastColumn></extendLastColumn>
		<isSummary/>
		<categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
	</viewproperties>
	<columns>
		<column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Group Name</title>
                 <xmlNodeName>displayName</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>250</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Type</title>
                 <xmlNodeName>groupType</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>300</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
		</column>
	</columns>
</viewsettings>');
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}
		//---------------------------------------wAdminArchivePolicies----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminArchivePolicies', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminArchivePolicies');
			$perspective->setDescription('perspective for Admin Archive Policies');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized>1</isCategorized>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Policy Status</title>
                 <xmlNodeName>policyStatus</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>180</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized>1</isCategorized>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Library</title>
                 <xmlNodeName>libraries</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Priority</title>
                 <xmlNodeName>policyPriority</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>65</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Policy Name</title>
                 <xmlNodeName>policyName</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Document Types</title>
                 <xmlNodeName>documenttypes</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Description</title>
                 <xmlNodeName>description</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>none</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
</columns>
</viewsettings>');
		
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}
		
		/***************************************** wAdminSystemMessages ************************************************/
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminSystemMessages', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminSystemMessages');
			$perspective->setDescription('Web admin perspective for system message');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
   <columns>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Message Name</title>
                 <xmlNodeName>messageName</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>250</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Subject</title>
                 <xmlNodeName>subjectLine</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>300</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color>#0000ff</color>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Body</title>
                 <xmlNodeName>messageContent</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>400</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
                </columns>
</viewsettings>');
		
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		//---------------------------------------wAdminSubscribedLibraries----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminSubscribedLibraries', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminSubscribedLibraries');
			$perspective->setDescription('Web Admin for Subscribed Libraries');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized>1</isCategorized>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>User</title>
                 <xmlNodeName>username</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color>#0000ff</color>
                 <fontWeight>bold</fontWeight>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                  <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title></title>
                 <xmlNodeName>status</xmlNodeName>
                 <dataType>html</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>40</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
               <column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Library Name</title>
                 <xmlNodeName>libraryname</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort></hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Library Key</title>
                 <xmlNodeName>librarykey</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder></sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>400</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>

    </columns>
</viewsettings>');
		
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}
		
		//---------------------------------------wAdminHTMLResource----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminHTMLResource', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminHTMLResource');
			$perspective->setDescription('Web admin perspective for HTML Resource');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
   <columns>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Name</title>
                 <xmlNodeName>resourceName</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>250</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Files</title>
                 <xmlNodeName>fileName</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>300</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color>#0000ff</color>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
         </columns>
</viewsettings>');	
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}
		
		
		//---------------------------------------wAdminCustomSearchFields----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminCustomSearchFields', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminCustomSearchFields');
			$perspective->setDescription('Web admin perspective for custom search fields.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
   <columns>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Field Description</title>
                 <xmlNodeName>fieldDescription</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Field Name</title>
                 <xmlNodeName>fieldName</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>180</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Data Type</title>
                 <xmlNodeName>dataType</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>100</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
                </columns>
</viewsettings>');
		
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}			
		//---------------------------------------wAdminActivities----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminActivities', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminActivities');
			$perspective->setDescription('Web Admin perspective for Activities');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
    <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
    <columns>
            <column>
                 <isCategorized></isCategorized>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Activity Action</title>
                 <xmlNodeName>activityaction</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                  <colorT/>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Activity Instruction</title>
                 <xmlNodeName>activityinstruction</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>400</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
       </columns>
</viewsettings>');
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}
		
		//---------------------------------------wAdminCheckedOutFiles----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminCheckedOutFiles', 'Is_System' => true));
		if (empty($exists)) 
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
						
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminCheckedOutFiles');
			$perspective->setDescription('Web Admin perspective for checked out files');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
      <columns>
            <column>
                 <isCategorized>1</isCategorized>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>User</title>
                 <xmlNodeName>username</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color>#0000ff</color>
                 <fontWeight>bold</fontWeight>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                  <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Document</title>
                 <xmlNodeName>subject</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Folder</title>
                 <xmlNodeName>foldername</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Library</title>
                 <xmlNodeName>libraryname</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
    </columns>
</viewsettings>');

			$manager->persist($perspective);
			$manager->flush();
			unset($perspective, $facet_map);
		}
		
			//---------------------------------------wAdminDeletedUsers----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminDeletedUsers', 'Is_System' => true));
		if (empty($exists)) 
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
						
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminDeletedUsers');
			$perspective->setDescription('Web Admin perspective for deleted users');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
     <viewproperties>
           <showSelectionMargin>1</showSelectionMargin>
           <allowCustomization></allowCustomization>
           <extendLastColumn></extendLastColumn>
           <isSummary/>
           <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
   </viewproperties>
     <columns>
           <column>
                <isCategorized/>
                <hasCustomSort>1</hasCustomSort>
                <totalType>0</totalType>
                <isFrozen/>
                <isFreezeControl/>
                <title>User</title>
                <xmlNodeName>username</xmlNodeName>
                <dataType>text</dataType>
                <sortOrder>ascending</sortOrder>
                <customSortOrder>none</customSortOrder>
                <numberFormat>###.##;-###.##</numberFormat>
                <numberPrefix/><numberSuffix/>
                <dateFormat/>
                <width>250</width>
                <align/>
                <fontSize/>
                <fontFamily/>
                <color/>
                <fontWeight/>
                <fontStyle/>
                <textDecoration/>
                <backgroundColor/>
                <alignT/>
                <fontSizeT/>
                <fontFamilyT/>
                <colorT>#0000ff</colorT>
                <fontWeightT>bold</fontWeightT>
                <fontStyleT/>
                <textDecorationT/>
                <backgroundColorT/>
                <alignH/>
                <fontSizeH/>
                <fontFamilyH/>
                <colorH/>
                <fontWeightH/>
                <fontStyleH/>
                <textDecorationH/>
                <backgroundColorH/>
           </column>
           <column>
                <isCategorized/>
                <hasCustomSort>1</hasCustomSort>
                <totalType>0</totalType>
                <isFrozen/>
                <isFreezeControl/>
                <title>Last Access</title>
                <xmlNodeName>lastaccess</xmlNodeName>
                <dataType>date</dataType>
                <sortOrder></sortOrder>
                <customSortOrder>none</customSortOrder>
                <numberFormat>###.##;-###.##</numberFormat>
                <numberPrefix/><numberSuffix/>
                <dateFormat/>
                <width>400</width>
                <align/>
                <fontSize/>
                <fontFamily/>
                <color/>
                <fontWeight/>
                <fontStyle/>
                <textDecoration/>
                <backgroundColor/>
                <alignT/>
                <fontSizeT/>
                <fontFamilyT/>
                <colorT>#0000ff</colorT>
                <fontWeightT>bold</fontWeightT>
                <fontStyleT/>
                <textDecorationT/>
                <backgroundColorT/>
                <alignH/>
                <fontSizeH/>
                <fontFamilyH/>
                <colorH/>
                <fontWeightH/>
                <fontStyleH/>
                <textDecorationH/>
                <backgroundColorH/>
           </column>
           <column>
                <isCategorized/>
                <hasCustomSort></hasCustomSort>
                <totalType>0</totalType>
                <isFrozen/>
                <isFreezeControl/>
                <title>Reinstate</title>
                <xmlNodeName>reinstate</xmlNodeName>
                <dataType>html</dataType>
                <sortOrder></sortOrder>
                <customSortOrder>none</customSortOrder>
                <numberFormat>###.##;-###.##</numberFormat>
                <numberPrefix/><numberSuffix/>
                <dateFormat/>
                <width>70</width>
                <align>center</align>
                <fontSize/>
                <fontFamily/>
                <color/>
                <fontWeight/>
                <fontStyle/>
                <textDecoration/>
                <backgroundColor/>
                <alignT/>
                <fontSizeT/>
                <fontFamilyT/>
                <colorT>#0000ff</colorT>
                <fontWeightT>bold</fontWeightT>
                <fontStyleT/>
                <textDecorationT/>
                <backgroundColorT/>
                <alignH/>
                <fontSizeH/>
                <fontFamilyH/>
                <colorH/>
                <fontWeightH/>
                <fontStyleH/>
                <textDecorationH/>
                <backgroundColorH/>
           </column>
	</columns>
</viewsettings>');

			$manager->persist($perspective);
			$manager->flush();
			unset($perspective, $facet_map);
		}
		
			//---------------------------------------wAdminUsersSession----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminUserSessions', 'Is_System' => true));
		if (empty($exists)) 
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
						
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminUserSessions');
			$perspective->setDescription('Administration user sessions perspective.');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
	<viewproperties>
		<showSelectionMargin>1</showSelectionMargin>
		<allowCustomization></allowCustomization>
		<extendLastColumn></extendLastColumn>
		<isSummary/>
		<categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
	</viewproperties>
	<columns>
		<column>
			<isCategorized/>
			<hasCustomSort>1</hasCustomSort>
			<totalType>0</totalType>
			<isFrozen/>
			<isFreezeControl/>
			<title>User</title>
			<xmlNodeName>user</xmlNodeName>
			<dataType>text</dataType>
			<sortOrder>ascending</sortOrder>
			<customSortOrder>none</customSortOrder>
			<numberFormat>###.##;-###.##</numberFormat>
			<numberPrefix/><numberSuffix/>
			<dateFormat/>
			<width>250</width>
			<align/>
			<fontSize/>
			<fontFamily/>
			<color/>
			<fontWeight/>
			<fontStyle/>
			<textDecoration/>
			<backgroundColor/>
			<alignT/>
			<fontSizeT/>
			<fontFamilyT/>
			<colorT>#0000ff</colorT>
			<fontWeightT>bold</fontWeightT>
			<fontStyleT/>
			<textDecorationT/>
			<backgroundColorT/>
			<alignH/>
			<fontSizeH/>
			<fontFamilyH/>
			<colorH/>
			<fontWeightH/>
			<fontStyleH/>
			<textDecorationH/>
			<backgroundColorH/>
		</column>
		<column>
			<isCategorized/>
			<hasCustomSort>1</hasCustomSort>
			<totalType>0</totalType>
			<isFrozen/>
			<isFreezeControl/>
			<title>Environment</title>
			<xmlNodeName>env</xmlNodeName>
			<dataType>text</dataType>
			<sortOrder></sortOrder>
			<customSortOrder>none</customSortOrder>
			<numberFormat>###.##;-###.##</numberFormat>
			<numberPrefix/><numberSuffix/>
			<dateFormat/>
			<width>100</width>
			<align/>
			<fontSize/>
			<fontFamily/>
			<color/>
			<fontWeight/>
			<fontStyle/>
			<textDecoration/>
			<backgroundColor/>
			<alignT/>
			<fontSizeT/>
			<fontFamilyT/>
			<colorT>#0000ff</colorT>
			<fontWeightT>bold</fontWeightT>
			<fontStyleT/>
			<textDecorationT/>
			<backgroundColorT/>
			<alignH/>
			<fontSizeH/>
			<fontFamilyH/>
			<colorH/>
			<fontWeightH/>
			<fontStyleH/>
			<textDecorationH/>
			<backgroundColorH/>
		</column>
		<column>
			<isCategorized/>
			<hasCustomSort>1</hasCustomSort>
			<totalType>0</totalType>
			<isFrozen/>
			<isFreezeControl/>
			<title>Created</title>
			<xmlNodeName>created</xmlNodeName>
			<dataType>date</dataType>
			<sortOrder></sortOrder>
			<customSortOrder>none</customSortOrder>
			<numberFormat>###.##;-###.##</numberFormat>
			<numberPrefix/><numberSuffix/>
			<dateFormat/>
			<width>125</width>
			<align/>
			<fontSize/>
			<fontFamily/>
			<color/>
			<fontWeight/>
			<fontStyle/>
			<textDecoration/>
			<backgroundColor/>
			<alignT/>
			<fontSizeT/>
			<fontFamilyT/>
			<colorT>#0000ff</colorT>
			<fontWeightT>bold</fontWeightT>
			<fontStyleT/>
			<textDecorationT/>
			<backgroundColorT/>
			<alignH/>
			<fontSizeH/>
			<fontFamilyH/>
			<colorH/>
			<fontWeightH/>
			<fontStyleH/>
			<textDecorationH/>
			<backgroundColorH/>
		</column>
		<column>
			<isCategorized/>
			<hasCustomSort>1</hasCustomSort>
			<totalType>0</totalType>
			<isFrozen/>
			<isFreezeControl/>
			<title>Last Access</title>
			<xmlNodeName>lastaccess</xmlNodeName>
			<dataType>date</dataType>
			<sortOrder></sortOrder>
			<customSortOrder>none</customSortOrder>
			<numberFormat>###.##;-###.##</numberFormat>
			<numberPrefix/><numberSuffix/>
			<dateFormat/>
			<width>125</width>
			<align/>
			<fontSize/>
			<fontFamily/>
			<color/>
			<fontWeight/>
			<fontStyle/>
			<textDecoration/>
			<backgroundColor/>
			<alignT/>
			<fontSizeT/>
			<fontFamilyT/>
			<colorT>#0000ff</colorT>
			<fontWeightT>bold</fontWeightT>
			<fontStyleT/>
			<textDecorationT/>
			<backgroundColorT/>
			<alignH/>
			<fontSizeH/>
			<fontFamilyH/>
			<colorH/>
			<fontWeightH/>
			<fontStyleH/>
			<textDecorationH/>
			<backgroundColorH/>
		</column>
		<column>
			<isCategorized/>
			<hasCustomSort>1</hasCustomSort>
			<totalType>0</totalType>
			<isFrozen/>
			<isFreezeControl/>
			<title>IP Address</title>
			<xmlNodeName>ipaddress</xmlNodeName>
			<dataType>text</dataType>
			<sortOrder></sortOrder>
			<customSortOrder>none</customSortOrder>
			<numberFormat>###.##;-###.##</numberFormat>
			<numberPrefix/><numberSuffix/>
			<dateFormat/>
			<width>125</width>
			<align/>
			<fontSize/>
			<fontFamily/>
			<color/>
			<fontWeight/>
			<fontStyle/>
			<textDecoration/>
			<backgroundColor/>
			<alignT/>
			<fontSizeT/>
			<fontFamilyT/>
			<colorT>#0000ff</colorT>
			<fontWeightT>bold</fontWeightT>
			<fontStyleT/>
			<textDecorationT/>
			<backgroundColorT/>
			<alignH/>
			<fontSizeH/>
			<fontFamilyH/>
			<colorH/>
			<fontWeightH/>
			<fontStyleH/>
			<textDecorationH/>
			<backgroundColorH/>
		</column>
	</columns>
</viewsettings>');

			$manager->persist($perspective);
			$manager->flush();
			unset($perspective, $facet_map);
		}

		//---------------------------------------wAdminDataSources----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminDataSources', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminDataSources');
			$perspective->setDescription('Web admin perspective for data sources');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
      <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
		<columns>
			<column>
                 <isCategorized>1</isCategorized>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Type</title>
                 <xmlNodeName>type</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>75</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color>#0000ff</color>
                 <fontWeight>bold</fontWeight>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                  <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
            <column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Name</title>
                 <xmlNodeName>name</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>250</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
</columns>
</viewsettings>');
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		//---------------------------------------wAdminDataViews----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminDataViews', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminDataViews');
			$perspective->setDescription('Web admin perspective for data views');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
	<viewproperties>
		<showSelectionMargin>1</showSelectionMargin>
		<allowCustomization></allowCustomization>
		<extendLastColumn></extendLastColumn>
		<isSummary/>
		<categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
		<columns>
			<column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Name</title>
                 <xmlNodeName>name</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>250</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
		</columns>
	</viewsettings>');
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		//---------------------------------------wAdminFileResources----------------------------------------------
		$exists = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'wAdminFileResources', 'Is_System' => true));
		if (empty($exists))
		{
			$facet_map = $manager->getRepository('DocovaBundle:FacetMaps')->findOneBy(array('Facet_Map_Name' => 'Default Facet Map'));
		
			$perspective = new SystemPerspectives();
			$perspective->setPerspectiveName('wAdminFileResources');
			$perspective->setDescription('Web admin perspective for file resources');
			$perspective->setCreator($user);
			$perspective->setDateCreated(new \DateTime());
			$perspective->setFacetMap($facet_map);
			$perspective->setVisibility('view');
			$perspective->setIsSystem(true);
			$perspective->setXmlStructure('<viewsettings>
    <viewproperties>
            <showSelectionMargin>1</showSelectionMargin>
            <allowCustomization></allowCustomization>
            <extendLastColumn></extendLastColumn>
            <isSummary/>
            <categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle>
    </viewproperties>
		<columns>
			<column>
                 <isCategorized>1</isCategorized>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Type</title>
                 <xmlNodeName>type</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>200</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color>#0000ff</color>
                 <fontWeight>bold</fontWeight>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                  <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
			<column>
                 <isCategorized/>
                 <hasCustomSort>1</hasCustomSort>
                 <totalType>0</totalType>
                 <isFrozen/>
                 <isFreezeControl/>
                 <title>Name</title>
                 <xmlNodeName>name</xmlNodeName>
                 <dataType>text</dataType>
                 <sortOrder>ascending</sortOrder>
                 <customSortOrder>none</customSortOrder>
                 <numberFormat>###.##;-###.##</numberFormat>
                 <numberPrefix/><numberSuffix/>
                 <dateFormat/>
                 <width>400</width>
                 <align/>
                 <fontSize/>
                 <fontFamily/>
                 <color/>
                 <fontWeight/>
                 <fontStyle/>
                 <textDecoration/>
                 <backgroundColor/>
                 <alignT/>
                 <fontSizeT/>
                 <fontFamilyT/>
                 <colorT>#0000ff</colorT>
                 <fontWeightT>bold</fontWeightT>
                 <fontStyleT/>
                 <textDecorationT/>
                 <backgroundColorT/>
                 <alignH/>
                 <fontSizeH/>
                 <fontFamilyH/>
                 <colorH/>
                 <fontWeightH/>
                 <fontStyleH/>
                 <textDecorationH/>
                 <backgroundColorH/>
            </column>
       </columns>
</viewsettings>');
			$manager->persist($perspective);
			$manager->flush();
			unset($perspective);
		}

		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'File_Name', 'customField' => null));
		if (empty($exists)) 
		{
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('File_Name');
			$custom_search_field->setFieldDescription('Attachment names');
			$custom_search_field->setDataType('text');
			$custom_search_field->setTextEntryType('E');
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field);
		}

		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'Date_Created', 'customField' => null));
		if (empty($exists))
		{
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('Date_Created');
			$custom_search_field->setFieldDescription('Created date');
			$custom_search_field->setDataType('date');
			$custom_search_field->setTextEntryType('E');
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field);
		}

		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'Description', 'customField' => null));
		if (empty($exists))
		{
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('Description');
			$custom_search_field->setFieldDescription('Description');
			$custom_search_field->setDataType('text');
			$custom_search_field->setTextEntryType('E');
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field);
		}

		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'Author', 'customField' => null));
		if (empty($exists))
		{
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('Author');
			$custom_search_field->setFieldDescription('Document author');
			$custom_search_field->setDataType('names');
			$custom_search_field->setTextEntryType('E');
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field);
		}

		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'Owner', 'customField' => null));
		if (empty($exists))
		{
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('Owner');
			$custom_search_field->setFieldDescription('Document owner');
			$custom_search_field->setDataType('names');
			$custom_search_field->setTextEntryType('E');
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field);
		}

		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'Keywords', 'customField' => null));
		if (empty($exists))
		{
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('Keywords');
			$custom_search_field->setFieldDescription('Keywords');
			$custom_search_field->setDataType('text');
			$custom_search_field->setTextEntryType('E');
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field);
		}

		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'Modifier', 'customField' => null));
		if (empty($exists))
		{
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('Modifier');
			$custom_search_field->setFieldDescription('Last modified by');
			$custom_search_field->setDataType('names');
			$custom_search_field->setTextEntryType('E');
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field);
		}

		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'Date_Modified', 'customField' => null));
		if (empty($exists))
		{
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('Date_Modified');
			$custom_search_field->setFieldDescription('Last modified date');
			$custom_search_field->setDataType('date');
			$custom_search_field->setTextEntryType('E');
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field);
		}

		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'Doc_Title', 'customField' => null));
		if (empty($exists))
		{
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('Doc_Title');
			$custom_search_field->setFieldDescription('Sub');
			$custom_search_field->setDataType('text');
			$custom_search_field->setTextEntryType('E');
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field);
		}

		$field = $manager->getRepository('DocovaBundle:DesignElements')->findOneBy(array('fieldName' => 'mail_cc', 'fieldType' => 0));
		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'mail_cc', 'customField' => $field->getId()));
		if (empty($exists))
		{
			$doctype = $manager->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => 'Mail Memo'));
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('mail_cc');
			$custom_search_field->setFieldDescription('CC');
			$custom_search_field->setDataType('names');
			$custom_search_field->setTextEntryType('E');
			$custom_search_field->setCustomField($field->getId());
			$custom_search_field->addDocumentTypes($doctype);
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field, $field, $doctype);
		}

		$field = $manager->getRepository('DocovaBundle:DesignElements')->findOneBy(array('fieldName' => 'mail_from', 'fieldType' => 0));
		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'mail_from', 'customField' => $field->getId()));
		if (empty($exists))
		{
			$doctype = $manager->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => 'Mail Memo'));
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('mail_from');
			$custom_search_field->setFieldDescription('From');
			$custom_search_field->setDataType('names');
			$custom_search_field->setTextEntryType('E');
			$custom_search_field->setCustomField($field->getId());
			$custom_search_field->addDocumentTypes($doctype);
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field, $field, $doctype);
		}

		$field = $manager->getRepository('DocovaBundle:DesignElements')->findOneBy(array('fieldName' => 'mail_to', 'fieldType' => 0));
		$exists = $manager->getRepository('DocovaBundle:CustomSearchFields')->findOneBy(array('fieldName' => 'mail_to', 'customField' => $field->getId()));
		if (empty($exists))
		{
			$doctype = $manager->getRepository('DocovaBundle:DocumentTypes')->findOneBy(array('Doc_Name' => 'Mail Memo'));
			$custom_search_field = new CustomSearchFields();
			$custom_search_field->setFieldName('mail_to');
			$custom_search_field->setFieldDescription('To');
			$custom_search_field->setDataType('names');
			$custom_search_field->setTextEntryType('E');
			$custom_search_field->setCustomField($field->getId());
			$custom_search_field->addDocumentTypes($doctype);
			$manager->persist($custom_search_field);
			$manager->flush();
			unset($custom_search_field, $field, $doctype);
		}

		//----------------------------------------------------------------------------------------------------------------------------
			
		$exists = $manager->getRepository('DocovaBundle:Widgets')->findOneBy(array('widgetName' => 'My Checked-Out Files'));
		if (empty($exists)) 
		{
			$widget = new Widgets();
			$widget->setWidgetName('My Checked-Out Files');
			$widget->setSubformName('sfMyDocova-CheckedOut');
			$widget->setSubformAlias('sfCheckedOut');
			$widget->setDescription('Shows User\'s currently checked-out files.');
			$manager->persist($widget);
			$manager->flush();
			unset($widget);
		}
			
		$exists = $manager->getRepository('DocovaBundle:Widgets')->findOneBy(array('widgetName' => 'My Recently Edited Documents'));
		if (empty($exists)) 
		{
			$widget = new Widgets();
			$widget->setWidgetName('My Recently Edited Documents');
			$widget->setSubformName('sfMyDocova-RecentlyEdited');
			$widget->setSubformAlias('sfRecentlyEdited');
			$widget->setDescription('Documents that have been recently edited by the User.');
			$manager->persist($widget);
			$manager->flush();
			unset($widget);
		}
			
		$exists = $manager->getRepository('DocovaBundle:Widgets')->findOneBy(array('widgetName' => 'My Review Items'));
		if (empty($exists)) 
		{
			$widget = new Widgets();
			$widget->setWidgetName('My Review Items');
			$widget->setSubformName('sfMyDocova-MyReviewItems');
			$widget->setSubformAlias('MyReviewItems');
			$widget->setDescription('Documents that are awaiting the User\'s review.');
			$manager->persist($widget);
			$manager->flush();
			unset($widget);
		}
			
		$exists = $manager->getRepository('DocovaBundle:Widgets')->findOneBy(array('widgetName' => 'DOCOVA My Pending Activities'));
		if (empty($exists)) 
		{
			$widget = new Widgets();
			$widget->setWidgetName('DOCOVA My Pending Activities');
			$widget->setSubformName('sfMyDocova-UserActivities');
			$widget->setSubformAlias('MyUserActivities');
			$widget->setDescription('Widget for showing a User\'s outstanding activities and activities that they\'ve created that are outstanding.');
			$manager->persist($widget);
			$manager->flush();
			unset($widget);
		}
			
		$exists = $manager->getRepository('DocovaBundle:Widgets')->findOneBy(array('widgetName' => 'My Pending Workflow Items'));
		if (empty($exists)) 
		{
			$widget = new Widgets();
			$widget->setWidgetName('My Pending Workflow Items');
			$widget->setSubformName('sfMyDocova-PendingWorkflowItems');
			$widget->setSubformAlias('sfPendingWorkflow');
			$widget->setDescription('All of the current User\'s outstanding workflow items.');
			$manager->persist($widget);
			$manager->flush();
			unset($widget);
		}

		$exists = $manager->getRepository('DocovaBundle:Widgets')->findOneBy(array('widgetName' => 'DOCOVA My Favorites'));
		if (empty($exists))
		{
			$widget = new Widgets();
			$widget->setWidgetName('DOCOVA My Favorites');
			$widget->setSubformName('sfMyDocova-UserFavorites');
			$widget->setSubformAlias('sfUserFavorites');
			$widget->setDescription('Listing of your favorite documents and folders.');
			$manager->persist($widget);
			$manager->flush();
			unset($widget);
		}

		$exists = $manager->getRepository('DocovaBundle:Widgets')->findOneBy(array('widgetName' => 'DOCOVA Workflow Summary'));
		if (empty($exists))
		{
			$widget = new Widgets();
			$widget->setWidgetName('DOCOVA Workflow Summary');
			$widget->setSubformName('sfMyDocova-WorkflowSummary');
			$widget->setSubformAlias('sfWorkflowSummary');
			$widget->setDescription('Allows the user to select a Library and a Folder, for which all ACTIVE workflow items are displayed. Can be used to monitor workflow activity/bottlenecks.');
			$manager->persist($widget);
			$manager->flush();
			unset($widget);
		}

		//**************************************** GLOBAL SETTINGS ****************************************
		$exists = $manager->getRepository('DocovaBundle:GlobalSettings')->findAll();
		if (empty($exists[0])) 
		{
			$global_settings = new GlobalSettings();
			$global_settings->setSystemKey(strtoupper(md5(rand(1000, 9999999))));
			$global_settings->setErrorLogLevel(2);
			$global_settings->setErrorLogEmail(2);
			$global_settings->setLogRetention(90);
			$global_settings->setApplicationTitle('DOCOVA');
			if (file_exists($init_file)) {
				$global_settings->setLicenseCode($xml->getElementsByTagName('clientlicensecode')->item(0)->nodeValue);
				$global_settings->setCompanyName($xml->getElementsByTagName('clientcompanyname')->item(0)->nodeValue);
				$global_settings->setNumberOfUsers($xml->getElementsByTagName('clientnumberofusers')->item(0)->nodeValue ? $xml->getElementsByTagName('clientnumberofusers')->item(0)->nodeValue : 10);
				$global_settings->setRunningServer($xml->getElementsByTagName('clientdomain')->item(0)->nodeValue);
			}
			else {
				$global_settings->setLicenseCode('36852-80897-TSHKJ-00000-56KZQ');
				$global_settings->setCompanyName('DLI.tools');
				$global_settings->setNumberOfUsers(0);
				$global_settings->setRunningServer('teitr.dlitools.com');
			}
			$global_settings->setCentralLocking(false);
			$global_settings->setRecordManagement(false);
			$global_settings->setDuplicateEmailCheck(false);
			$global_settings->setLocalDelete(false);
			$global_settings->setCleanupTime(new \DateTime('12:00:00 AM'));
			$global_settings->setArchiveTime(new \DateTime('02:00:00 PM'));
			$global_settings->setNotificationTime(new \DateTime('08:00:00 AM'));
			$global_settings->setDBAuthentication(true);
			$global_settings->setDocovaBaseDn('/DOCOVA');
			$global_settings->setUserDisplayDefault(false);
			$manager->persist($global_settings);
			$manager->flush();
			unset($global_settings);
		}
		
		//****************************************************** LIBRARIES & FOLDERS ***********************************************************
		$exists = $manager->getRepository('DocovaBundle:Libraries')->findOneBy(array('Library_Title' => 'Documents', 'Status' => true));
		if (empty($exists)) 
		{
			$library = new Libraries();
			$library->setLibraryTitle('Documents');
			$library->setHostName('DOCOVA');
			$library->setRecycleRetention(30);
			$library->setArchiveRetention(730);
			$library->setDocLogRetention(2192);
			$library->setEventLogRetention(90);
			$library->setDescription('Documents');
			$library->setIsApp(false);
			$library->setDateCreated(new \DateTime());
			$manager->persist($library);
			$manager->flush();

			$custom_acl = new CustomACL($this->container);
			$custom_acl->insertObjectAce($library, 'ROLE_ADMIN', 'owner');
			$custom_acl->insertObjectAce($library, 'ROLE_USER', array('create', 'view'));
			
			$exists = $manager->getRepository('DocovaBundle:Folders')->findOneBy(array('Folder_Name' => 'New Folder', 'Library' => $library->getId()));
			if (empty($exists)) 
			{
				$perspective = $manager->getRepository('DocovaBundle:SystemPerspectives')->findOneBy(array('Perspective_Name' => 'System Default', 'Is_System' => true));
				$folder = new Folders();
				$folder->setFolderName('New Folder');
				$folder->setPosition($manager);
				$folder->setDefaultDocType(-1);
				$folder->setCreator($user);
				$folder->setDateCreated(new \DateTime());
				$folder->setLibrary($library);
				$folder->setDefaultPerspective($perspective);
				$manager->persist($folder);
				$manager->flush();
				
				$custom_acl->insertObjectAce($folder, 'ROLE_USER', ['view', 'create', 'delete']);
				$custom_acl->insertObjectAce($folder, $user, ['owner'], false);
				
				$log_obj = new FoldersLog();
				$log_obj->setFolder($folder);
				$log_obj->setLogAuthor($user);
				$log_obj->setLogDate(new \DateTime());
				$log_obj->setLogStatus(true);
				$log_obj->setLogAction(2);
				$log_obj->setLogDetails('Created new folder.');
				
				$manager->persist($log_obj);
				$manager->flush();
			}
			
			unset($library, $custom_acl, $perspective, $folder, $log_obj);
		}

		$manager->flush();
			
		if (file_exists($init_file)) {
			@unlink($init_file);
		}
	}
}