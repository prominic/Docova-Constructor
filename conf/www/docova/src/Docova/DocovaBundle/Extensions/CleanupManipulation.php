<?php

namespace Docova\DocovaBundle\Extensions;

use Doctrine\ORM\EntityManager;
//use Docova\DocovaBundle\Extensions\TransactionManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Class to manipulate application clean up process
 * @author javad_rahimi
 */
class CleanupManipulation extends TransactionManager
{
	private $_app;
	private $_app_path;
	private $_att_path;
	
	/**
	 * {@inheritdoc}
	 *
	 * @see \Docova\DocovaBundle\Extensions\TransactionManager::__construct()
	 */
	public function __construct(EntityManager $manager, Router $router, $app, $file_path, $app_path)
	{
		if ($app instanceof \Docova\DocovaBundle\ObjectModel\DocovaApplication) {
			$this->_app = $app->appID;
		}
		elseif ($app instanceof \Docova\DocovaBundle\Entity\Libraries) {
			$this->_app = $app->getId();
		}
		else {
			$this->_app = $app;
		}
		
		$this->_att_path = $file_path;
		$this->_app_path = $app_path;
		
		parent::__construct ( $manager );
	}
	
	/**
	 * Clean up all app document logs
	 * 
	 * @return boolean
	 */
	protected function cleanDocumentLogs()
	{
		$query = 'DELETE L FROM tb_documents_log AS L JOIN tb_folders_documents AS D ON L.Document_Id = D.id WHERE D.App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up all app document logs
	 * 
	 * @return boolean
	 */
	protected function cleanupDocumentComments()
	{
		$query = 'DELETE C FROM tb_document_comments AS C JOIN tb_folders_documents AS D ON C.Document_Id = D.id WHERE D.App_Id = ?';
		$this->execQuery($query, [$this->_app]);
		$query = 'DELETE C FROM tb_app_document_comments AS C JOIN tb_folders_documents AS D ON C.Document_Id = D.id WHERE D.App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up all app document field values (numeric, date time, names/group, string)
	 * 
	 * @return boolean
	 */
	protected function cleanupDocumentFieldValues()
	{
		$query = 'DELETE V FROM tb_form_datetime_values AS V JOIN tb_folders_documents AS D ON V.Doc_Id = D.id WHERE D.App_Id = ?';
		$d_ret = $this->execQuery($query, [$this->_app]);
		$query = 'DELETE V FROM tb_form_group_values AS V JOIN tb_folders_documents AS D ON V.Doc_Id = D.id WHERE D.App_Id = ?';
		$g_ret = $this->execQuery($query, [$this->_app]);
		$query = 'DELETE V FROM tb_form_name_values AS V JOIN tb_folders_documents AS D ON V.Doc_Id = D.id WHERE D.App_Id = ?';
		$n_ret = $this->execQuery($query, [$this->_app]);
		$query = 'DELETE V FROM tb_form_numeric_values AS V JOIN tb_folders_documents AS D ON V.Doc_Id = D.id WHERE D.App_Id = ?';
		$nu_ret = $this->execQuery($query, [$this->_app]);
		$query = 'DELETE V FROM tb_form_text_values AS V JOIN tb_folders_documents AS D ON V.Doc_Id = D.id WHERE D.App_Id = ?';
		$t_ret = $this->execQuery($query, [$this->_app]);
		
		return ($d_ret && $g_ret && $n_ret && $nu_ret && $t_ret);
	}
	
	/**
	 * Clean up all app related emails and their possible attachments
	 * 
	 * @return boolean
	 */
	protected function cleanupRelatedEmails()
	{
		$query = 'SELECT E.id FROM tb_related_emails AS E JOIN tb_folders_documents AS D ON E.Document_Id = D.id WHERE D.App_Id = ?';
		$result = $this->fetchAll($query, [$this->_app]);
		if (!empty($result) && !empty($result[0]))
		{
			foreach ($result as $email) {
				$file = $this->_att_path.DIRECTORY_SEPARATOR.'mails'.DIRECTORY_SEPARATOR.$email['id'];
				if (!file_exists($file)) {
					@unlink($file);
				}
			}
			$query = 'DELETE E FROM tb_related_emails AS E JOIN tb_folders_documents AS D ON E.Document_Id = D.id WHERE D.App_Id = ?';
			return $this->execQuery($query, [$this->_app]);
		}
		return true;
	}
	
	/**
	 * Clean up all app related links
	 * 
	 * @return boolean
	 */
	protected function cleanupRelatedLinks()
	{
		$query = 'DELETE L FROM tb_related_links AS L JOIN tb_folders_documents AS D ON L.Document_Id = D.id WHERE D.App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}

	/**
	 * Clean up all app related documents (response documents)
	 * 
	 * @return boolean
	 */
	protected function cleanupRelatedDocuments()
	{
		$query = 'DELETE R FROM tb_related_documents AS R JOIN tb_folders_documents AS D ON R.Parent_Document_Id = D.id WHERE D.App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}

	/**
	 * Clean up all app documents' activities
	 * 
	 * @return boolean
	 */
	protected function cleanupDocumentActivities()
	{
		$query = 'DELETE A FROM tb_document_activities AS A JOIN tb_folders_documents AS D ON A.Document_Id = D.id WHERE D.App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up all app document workflow steps and their dependencies
	 * (reviewers, approvers, step actions, participants and etc)
	 * 
	 * @return boolean
	 */
	protected function cleanupDocumentWorkflow()
	{
		$query = 'SELECT DISTINCT(W.id) FROM tb_document_workflow_steps AS W JOIN tb_folders_documents AS D ON W.Document_Id = D.id WHERE D.App_Id = ?';
		$result = $this->fetchAll($query, [$this->_app]);
		if (!empty($result) && !empty($result[0]))
		{
			$params = implode(',', array_fill(0, count($result), '?'));
			$values = [];
			foreach ($result as $step) {
				$values[] = $step['id'];
			}
			//delete steps' reviewers and approvers who are pending
			$query = "DELETE FROM tb_document_workflow_assignee WHERE Workflow_Step_Id IN ($params)";
			$this->execQuery($query, $values);
			
			//delete stpes' reviewers and approvers who have completed step
			$query = "DELETE FROM tb_document_workflow_completedby WHERE Workflow_Step_Id IN ($params)";
			$this->execQuery($query, $values);
			
			//delete steps' other participants
			$query = "DELETE FROM tb_docworkflowsteps_users WHERE Step_Id IN ($params)";
			$this->execQuery($query, $values);
			
			//first delete users from list of "send to" in each step action
			$query = "DELETE S FROM tb_docstep_action_senders AS S JOIN tb_docworkflow_step_actions AS A ON S.Message_Id = A.id WHERE A.Step_Id IN ($params)";
			$this->execQuery($query, $values);
			//then delete all setps' actions
			$query = "DELETE FROM tb_docworkflow_step_actions WHERE Step_Id IN ($params)";
			$this->execQuery($query, $values);

			//finally delete all document workflow steps
			$query = 'DELETE W FROM tb_document_workflow_steps AS W JOIN tb_folders_documents AS D ON W.Document_Id = D.id WHERE D.App_Id = ?';
			return $this->execQuery($query, [$this->_app]);
		}
		return true;
	}
	
	/**
	 * Clean up all app documents' ACL entries
	 * 
	 * @return boolean
	 */
	protected function cleanupDocumentAcl()
	{
		$query = 'DELETE E FROM acl_entries AS E JOIN acl_object_identities AS O ON O.id = E.object_identity_id ';
		$query .= 'JOIN tb_folders_documents AS D ON D.id = O.object_identifier WHERE D.App_Id = ?';
		$this->execQuery($query, [$this->_app]);
		
		$query = 'DELETE A from acl_object_identity_ancestors AS A JOIN acl_object_identities AS O ON O.id = A.object_identity_id ';
		$query .= 'JOIN tb_folders_documents AS D ON D.id = O.object_identifier WHERE D.App_Id = ?';
		$this->execQuery($query, [$this->_app]);
		
		$query = 'DELETE O FROM acl_object_identities AS O JOIN tb_folders_documents AS D ON O.object_identifier = D.id WHERE D.App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up all app documents' attachments (meta data from DB, actual file from system)
	 * 
	 * @return boolean
	 */
	protected function cleanupDocumentAttachments()
	{
		$query = 'SELECT DISTINCT(A.Doc_Id) FROM tb_attachments_details AS A JOIN tb_folders_documents AS D ON A.Doc_Id = D.id WHERE D.App_Id = ?';
		$result = $this->fetchAll($query, [$this->_app]);
		if (!empty($result) && !empty($result[0]))
		{
			foreach ($result as $attach)
			{
				$path = $this->_att_path.DIRECTORY_SEPARATOR.$attach['Doc_Id'];
				if (file_exists($path))
				{
					foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
						if (!is_dir($file)) {
							@unlink($file);
						}
					}
					@rmdir($path);
					clearstatcache();
				}
			}
			$query = 'DELETE A FROM tb_attachments_details AS A JOIN tb_folders_documents AS D ON A.Doc_Id = D.id WHERE D.App_Id = ?';
			return $this->execQuery($query, [$this->_app]);
		}
		return true;
	}
	
	/**
	 * Clean up all app profile documents' fields
	 * 
	 * @return boolean
	 */
	protected function cleanupAppProfileDocFields()
	{
		$query = 'DELETE E FROM tb_design_elements AS E JOIN tb_folders_documents AS D ON E.Profile_Document_Id = D.id WHERE D.App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Negate relationship between app documents when versioning is enabled
	 * 
	 * @return boolean
	 */
	protected function negateParentDocuments()
	{
		$query = 'UPDATE tb_folders_documents SET Parent_Document = NULL WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up all app documents and it's dependencies
	 * 
	 * @return boolean
	 */
	public function cleanupDocuments()
	{
		try {
			$this->cleanDocumentLogs();
			$this->cleanupDocumentComments();
			$this->cleanupDocumentFieldValues();
			$this->cleanupRelatedEmails();
			$this->cleanupRelatedLinks();
			$this->cleanupRelatedDocuments();
			$this->cleanupDocumentActivities();
			$this->cleanupDocumentWorkflow();
			$this->cleanupDocumentAcl();
			$this->cleanupDocumentAttachments();
			$this->cleanupAppProfileDocFields();
			$this->negateParentDocuments();
			$query = 'DELETE FROM tb_folders_documents WHERE App_Id = ?';
			if (false === $this->execQuery($query, [$this->_app])) {
				return 'Failed to remove documents of application with ID '.$this->_app;
			}
			return $this->execQuery($query, [$this->_app]);
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	
	/**
	 * (FOR FUTURE)
	 * Clean up all saved searches on the app
	 * 
	 * @return boolean
	 */
	protected function cleanupAppSavedSearches()
	{
		$query = 'DELETE FROM tb_saved_searches_libraries WHERE Library_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * clean up all app ACL & ACE & roles
	 */
	protected function cleanupAppAcl()
	{
		$query = 'DELETE FROM tb_app_acl WHERE App_Id = ?';
		$this->execQuery($query, [$this->_app]);
		
		//remove all app roles
		$query = 'DELETE FROM tb_user_roles WHERE Application_Id = ?';
		$this->execQuery($query, [$this->_app]);
		
		//remove app ace records
		$query = 'DELETE E FROM acl_entries AS E JOIN acl_object_identities AS O ON O.id = E.object_identity_id WHERE O.object_identifier = ?';
		$this->execQuery($query, [$this->_app]);
		$query = 'DELETE A from acl_object_identity_ancestors AS A JOIN acl_object_identities AS O ON O.id = A.object_identity_id WHERE O.object_identifier = ?';
		$this->execQuery($query, [$this->_app]);
		$query = 'DELETE FROM acl_object_identities WHERE object_identifier = ?';
		$this->execQuery($query, [$this->_app]);
		
		//remove migrated app ace details
		$query = 'DELETE FROM tb_migrated_app_ace WHERE application_id = ?';
		$this->execQuery($query, [$this->_app]);
	}

	/**
	 * Clean up all app agents
	 * 
	 * @return boolean
	 */
	protected function cleanupAppAgents()
	{
		$appdir = 'A'.str_replace('-', '', $this->_app);
		$path = $this->_app_path . '../../../Agents'.DIRECTORY_SEPARATOR . $appdir;
		if (file_exists($path))
		{
			foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($path);
			clearstatcache();
		}
		$query = 'DELETE FROM tb_app_agents WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}

	/**
	 * Clean up all app CSSs
	 * 
	 * @return boolean
	 */
	protected function cleanupAppCss()
	{
		$path = $this->_app_path . '../../public/css/custom' . DIRECTORY_SEPARATOR . $this->_app;
		if (file_exists($path))
		{
			foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($path);
			clearstatcache();
		}
		$query = 'DELETE FROM tb_app_css WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}

	/**
	 * Clean up all app files/resources
	 * 
	 * @return boolean
	 */
	protected function cleanupAppFiles()
	{
		$path = $this->_app_path . '../../public/images' . DIRECTORY_SEPARATOR . $this->_app;
		if (file_exists($path))
		{
			foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($path);
			clearstatcache();
		}
		$query = 'DELETE FROM tb_app_files WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up all app forms
	 * 
	 * @return boolean
	 */
	protected function cleanupAppForms()
	{
		//remove dependencies first (form attachments properties, form properties)
		$query = 'DELETE AP FROM tb_app_form_att_properties AS AP JOIN tb_app_forms AS F ON AP.App_Form_Id = F.id WHERE F.App_Id = ?';
		$this->execQuery($query, [$this->_app]);
		$query = 'DELETE P FROM tb_app_form_properties AS P JOIN tb_app_forms AS F ON P.App_Form_Id = F.id WHERE F.App_Id = ?';
		$this->execQuery($query, [$this->_app]);
		
		//remove connected form workflows
		$query = 'DELETE W FROM tb_app_form_workflows AS W JOIN tb_app_forms AS F ON W.Form_Id = F.id WHERE F.App_Id = ?';
		$this->execQuery($query, [$this->_app]);
		
		//remove design elements
		$query = 'DELETE E FROM tb_design_elements AS E JOIN tb_app_forms AS F ON E.Form_Id = F.id WHERE F.App_Id = ?';
		$this->execQuery($query, [$this->_app]);
		
		//remove the generate twig files and template files
		$path = $this->_app_path . $this->_app. DIRECTORY_SEPARATOR;
		if (file_exists($path))
		{
			foreach (glob($path.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			clearstatcache();
			
			$template_path = $path . 'templates'.DIRECTORY_SEPARATOR.'FORM';
			if (file_exists($template_path))
			{
				foreach (glob($template_path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
					if (!is_dir($file)) {
						@unlink($file);
					}
				}
				@rmdir($template_path);
				clearstatcache();
			}
		}
		
		//remove the app forms meta data
		$query = 'DELETE FROM tb_app_forms WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up all app JavaScripts
	 * 
	 * @return boolean
	 */
	protected function cleanupAppJavaScripts()
	{
		$path = $this->_app_path . '../../public/js/custom' . DIRECTORY_SEPARATOR . $this->_app;
		if (file_exists($path))
		{
			foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($path);
			clearstatcache();
		}
		$query = 'DELETE FROM tb_app_javascripts WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up all app layouts
	 * 
	 * @return boolean
	 */
	protected function cleanupAppLayouts()
	{
		$path = $this->_app_path. $this->_app . DIRECTORY_SEPARATOR . 'layouts';
		if (file_exists($path))
		{
			foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($path);
			clearstatcache();
		}
		$query = 'DELETE FROM tb_app_layouts WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}

	/**
	 * Clean up all app menus
	 * 
	 * @return boolean
	 */
	protected function cleanupAppMenus()
	{
		$path = $this->_app_path. $this->_app . DIRECTORY_SEPARATOR . 'outline';
		if (file_exists($path))
		{
			foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($path);
			clearstatcache();
		}
		$query = 'DELETE FROM tb_app_outlines WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}

	/**
	 * Clean up all app pages
	 * 
	 * @return boolean
	 */
	protected function cleanupAppPages()
	{
		$path = $this->_app_path . $this->_app . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'PAGE';
		if (file_exists($path))
		{
			foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($path);
			clearstatcache();
		}
		$query = 'DELETE FROM tb_app_pages WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}

	/**
	 * Clean up all app PHP Scripts
	 * 
	 * @return boolean
	 */
	protected function cleanupAppPhpScripts()
	{
		$path = $this->_app_path . '../../../Agents'.DIRECTORY_SEPARATOR . $this->_app . '/ScriptLibraries';
		if (file_exists($path))
		{
			foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($path);
			clearstatcache();
		}
		$query = 'DELETE FROM tb_app_phpscripts WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}

	/**
	 * Clean up all app views
	 * 
	 * @return boolean
	 */
	protected function cleanupAppViews()
	{
		$query = 'SELECT id FROM tb_app_views WHERE App_Id = ?';
		$result = $this->fetchAll($query, [$this->_app]);
		if (!empty($result) && !empty($result[0]))
		{
			$driver = $this->getDriver();
			//drop the app view tables first
			for ($x = 0; $x < count($result); $x++)
			{
				$view_name = str_replace('-', '', $result[$x]['id']);
				if ($driver == 'pdo_mysql') {
					$query = "DROP TABLE IF EXISTS view_$view_name ";
				}
				else {
					$query = "IF OBJECT_ID('view_$view_name', 'U') IS NOT NULL DROP TABLE view_$view_name";
				}
				$this->execQuery($query);
			}
			
			//remove generate view twig files (for tooblar)
			$path = $this->_app_path. $this->_app . DIRECTORY_SEPARATOR . 'templates'. DIRECTORY_SEPARATOR . 'View';
			if (file_exists($path))
			{
				foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
					if (!is_dir($file)) {
						@unlink($file);
					}
				}
				@rmdir($path);
				clearstatcache();
			}

			//now delete the view meta data
			$query = 'DELETE FROM tb_app_views WHERE App_Id = ?';
			return $this->execQuery($query, [$this->_app]);
		}
		return true;
	}
	
	/**
	 * Clean up all app subforms
	 * 
	 * @return boolean
	 */
	protected function cleanupAppSubforms()
	{
		//remove subforms design elements
		$query = 'DELETE E FROM tb_design_elements AS E JOIN tb_subforms AS S ON E.Subform_Id = S.id WHERE S.App_Id = ?';
		$this->execQuery($query, [$this->_app]);
		
		$path = $this->_app_path . $this->_app . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'SUBFORM';
		if (file_exists($path))
		{
			foreach (glob($path.DIRECTORY_SEPARATOR.'*', GLOB_MARK) as $file) {
				if (!is_dir($file)) {
					@unlink($file);
				}
			}
			@rmdir($path);
			clearstatcache();
		}
		
		//remove the app forms meta data
		$query = 'DELETE FROM tb_subforms WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean-up all app workflows
	 * 
	 * @return boolean
	 */
	protected function cleanupAppWorkflows()
	{
		$query = 'SELECT DISTINCT(W.id) FROM tb_workflow AS W WHERE W.App_Id = ?';
		$result = $this->fetchAll($query, [$this->_app]);
		if (!empty($result) && !empty($result[0]))
		{
			$params = implode(',', array_fill(0, count($result), '?'));
			$values = [];
			foreach ($result as $step) {
				$values[] = $step['id'];
			}
			
			//delete all other participants
			$query = "DELETE FROM tb_workflowsteps_users WHERE Step_Id IN (SELECT id FROM tb_workflow_steps WHERE Workflow_id IN($params))";
			$this->execQuery($query, $values);
			
			//clean-up action send to list
			$query = "DELETE FROM tb_step_action_senders WHERE Message_Id IN (SELECT id FROM tb_workflow_step_actions WHERE Step_Id IN (SELECT id FROM tb_workflow_steps WHERE Workflow_id IN($params)))";
			$this->execQuery($query, $values);
			
			//delete all step actions
			$query = "DELETE FROM tb_workflow_step_actions WHERE Step_Id IN (SELECT id FROM tb_workflow_steps WHERE Workflow_id IN($params))";
			$this->execQuery($query, $values);

			//delete all workflow steps
			$query = "DELETE FROM tb_workflow_steps WHERE Workflow_id IN($params)";
			$this->execQuery($query, $values);
			
			//finally delete all app workflows
			$query = "DELETE FROM tb_workflow WHERE id IN($params)";
			return $this->execQuery($query, $values);
		}
		return true;
	}
	
	/**
	 * Clean up all apps opened/subscribed in users' app builder
	 * 
	 * @return boolean
	 */
	protected function cleanupUserAppsInBuilder()
	{
		$query = 'DELETE FROM tb_user_appbuilder_apps WHERE App_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up the app from app groups section
	 * 
	 * @return boolean
	 */
	protected function cleanupFromAppGroup()
	{
		$query = 'DELETE FROM tb_app_groups_content WHERE Application = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up the app in delegated user apps list
	 * 
	 * @return boolean
	 */
	protected function cleanupDelegatedApps()
	{
		$query = 'DELETE FROM tb_delegate_libraries WHERE libraries_id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up the app from recently used apps list
	 * 
	 * @return boolean
	 */
	protected function cleanupRecentlyUsedApps()
	{
		$query = 'DELETE FROM tb_user_recent_apps WHERE Application_Id = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up all app trash logs
	 * 
	 * @return boolean
	 */
	protected function cleanupAppTrashLogs()
	{
		$query = 'DELETE FROM tb_trashed_logs WHERE Parent_Library = ?';
		return $this->execQuery($query, [$this->_app]);
	}
	
	/**
	 * Clean up the application and all its depencies
	 * 
	 * @return boolean
	 */
	public function cleanupApplication()
	{
		try {
			//first remove all dependecies
			$this->cleanupAppSavedSearches(); // <<-- Optional (it's planned for future)
			$this->cleanupAppAcl();
			$this->cleanupAppAgents();
			$this->cleanupAppCss();
			$this->cleanupAppFiles();
			$this->cleanupAppForms();
			$this->cleanupAppJavaScripts();
			$this->cleanupAppLayouts();
			$this->cleanupAppMenus();
			$this->cleanupAppPages();
			$this->cleanupAppPhpScripts();
			$this->cleanupAppViews();
			$this->cleanupAppSubforms();
			$this->cleanupAppWorkflows();
			$this->cleanupUserAppsInBuilder();
			$this->cleanupFromAppGroup();
			$this->cleanupDelegatedApps();
			$this->cleanupRecentlyUsedApps();
			$this->cleanupAppTrashLogs();
			
			//remove any left over directories for the app
			$path = $this->_app_path . $this->_app . DIRECTORY_SEPARATOR;
			$this->removeDirectories($path);
			$path = $this->_app_path . '../../public/js/custom/' . $this->_app . DIRECTORY_SEPARATOR;
			$this->removeDirectories($path);
			$path = $this->_app_path . '../../public/css/custom/' . $this->_app . DIRECTORY_SEPARATOR;
			$this->removeDirectories($path);
			$path = $this->_app_path . '../../public/images/custom/' . $this->_app . DIRECTORY_SEPARATOR;
			$this->removeDirectories($path);

			//now delete the app itself
			$query = 'DELETE FROM tb_libraries WHERE id = ? AND Trash = 1 AND Is_App = 1';
			if (false === $this->execQuery($query, [$this->_app])) {
				return 'Failed to remove application ID '.$this->_app;
			}
			return true;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	
	/**
	 * Bind the values to a query and execute it.
	 * 
	 * @param string $query
	 * @param array $params
	 * @return boolean
	 */
	private function execQuery($query, $params = [])
	{
		$stmt = $this->prepare($query);
		$len = count($params);
		if (!empty($params) && $len)
		{
			for ($x = 1; $x <= $len; $x++)
			{
				$stmt->bindValue($x, $params[$x - 1]);
			}
		}
		
		$result = $stmt->execute();
		return $result;
	}
	
	/**
	 * Remove a directory and its sub-directories including their contents
	 * 
	 * @param string $directory
	 */
	private function removeDirectories($directory)
	{
		foreach (glob($directory.'*', GLOB_MARK) as $file) {
			if (!is_dir($file)) {
				@unlink($file);
			}
			else {
				$this->removeDirectories($file.DIRECTORY_SEPARATOR);
			}
		}
		@rmdir($directory);
		clearstatcache();
	}
}