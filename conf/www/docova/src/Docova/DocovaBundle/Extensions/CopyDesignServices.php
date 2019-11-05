<?php

namespace Docova\DocovaBundle\Extensions;

use Doctrine\ORM\EntityManager;
use Docova\DocovaBundle\Entity\Libraries;
//use Docova\DocovaBundle\Extensions\MiscFunctions;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Docova\DocovaBundle\ObjectModel\Docova;
/**
 * Class to controll all app design services when an app is copied
 * @author javad_rahimi
 */
class CopyDesignServices extends CopyFilesServices
{
	private $_em;
	private $_source;
	private $_target;
	private $_user;
	private $_docova;
	
	use CopyServices;
	
	public function __construct(Docova $docova_obj = null, Libraries $source_app, $user, $rootpath)
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
				throw new \Exception('View Manipulation contstruction failed, Docova service not available.');
			}
		}
		$this->_em = $this->_docova->getManager();
		$this->_source = $source_app;
		$this->_user = $user;
		parent::__construct($rootpath, $source_app->getId());
	}
	
	/**
	 * Copies the ACL from one application to another
	 *
	 */
	public function copyApplicationACL()
	{
		
		$appRoles = $this->_em->getRepository('DocovaBundle:UserRoles')->findBy(array('application' => $this->_source->getId()));
		
		if (!empty($appRoles) && count($appRoles) > 0)
		{
			$miscfunctions = new MiscFunctions();
				
			foreach ($appRoles as $roleentry)
			{
				try {
					$target_roleentry = clone $roleentry;
					
					$target_roleentry->setRole('ROLE_APP'.$miscfunctions->generateGuid("UPPERCASE"));
					$target_roleentry->setApplication($this->_target);
					$this->_em->persist($target_roleentry);
					$this->_em->flush();
				}catch (\Exception $e) {
					//@todo: log the error and role entry name which is not copied
					echo ("Error in copy app acl ".$e->getMessage() );
				}
			}
		}		
		
		
		$appAcl = $this->_em->getRepository('DocovaBundle:AppAcl')->findBy(array('application' => $this->_source->getId()));

		if (!empty($appAcl) && count($appAcl) > 0)
		{
			foreach ($appAcl as $aclentry)
			{
				try {
					$target_aclentry = clone $aclentry;
					$target_aclentry->setApplication($this->_target);
					$this->_em->persist($target_aclentry);
					$this->_em->flush();
				}catch (\Exception $e) {
					//@todo: log the error and acl entry name which is not copied
					echo ("Error in copy app acl 2 ".$e->getMessage() );
				}
			}
		}
		
	}


	/**
	 * Get new app element name
	 * 
	 * @param string $type
	 * @param string $origname
	 * @return string
	 */
	public function getNewElementName($type, $origname)
	{

		if ( $type == "form"){
			
			$formintargetapp = $this->_em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formName' => $origname, 'trash' => false, 'application' => $this->_target));
			while ( ! empty($formintargetapp)){
				$origname = " Copy of ".$origname;
				$formintargetapp = $this->_em->getRepository('DocovaBundle:AppForms')->findOneBy(array('formName' => $origname, 'trash' => false, 'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "view"){
			$elem = $this->_em->getRepository('DocovaBundle:AppViews')->findOneBy(array('viewName' => $origname, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:AppViews')->findOneBy(array('viewName' => $origname,  'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "layout"){
			$elem = $this->_em->getRepository('DocovaBundle:AppLayout')->findOneBy(array('layoutId' => $origname, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:AppLayout')->findOneBy(array('layoutId' => $origname,  'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "page"){
			$elem = $this->_em->getRepository('DocovaBundle:AppPages')->findOneBy(array('pageName' => $origname, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:AppPages')->findOneBy(array('pageName' => $origname,  'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "subform"){
			$elem = $this->_em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => $origname, 'Trash' => false, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:Subforms')->findOneBy(array('Form_File_Name' => $origname, 'Trash' => false, 'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "outline"){
			$elem = $this->_em->getRepository('DocovaBundle:AppOutlines')->findOneBy(array('outlineName' => $origname, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:AppOutlines')->findOneBy(array('outlineName' => $origname, 'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "js"){
			$elem = $this->_em->getRepository('DocovaBundle:AppJavaScripts')->findOneBy(array('jSName' => $origname, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:AppJavaScripts')->findOneBy(array('jSName' => $origname, 'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "agent"){
			$elem = $this->_em->getRepository('DocovaBundle:AppAgents')->findOneBy(array('agentName' => $origname, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:AppAgents')->findOneBy(array('agentName' => $origname, 'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "sl"){
			$elem = $this->_em->getRepository('DocovaBundle:AppPhpScripts')->findOneBy(array('PhpName' => $origname, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:AppPhpScripts')->findOneBy(array('PhpName' => $origname, 'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "image"){
			$elem = $this->_em->getRepository('DocovaBundle:AppFiles')->findOneBy(array('fileName' => $origname, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:AppFiles')->findOneBy(array('fileName' => $origname, 'application' => $this->_target));
			}
			return $origname;
		}else if ( $type == "css"){
			$elem = $this->_em->getRepository('DocovaBundle:AppCss')->findOneBy(array('cssName' => $origname, 'application' => $this->_target));
			while ( ! empty($elem)){
				$origname = " Copy of ".$origname;
				$elem = $this->_em->getRepository('DocovaBundle:AppCss')->findOneBy(array('cssName' => $origname, 'application' => $this->_target));
			}
			return $origname;
		}
		return $origname;
	}

	
	/**
	 * Copy an app form
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppForms $f
	 */
	public function copyForm($f)
	{
		try {
			$target_form = clone $f;
			$target_form->setApplication($this->_target);
			
			if ( ! $f->getTrash() ){
				$newformname = $this->getNewElementName("form", $f->getFormName());
				$target_form->setFormName($newformname);
			}else{
				$newformname = $f->getFormName();
			}

			$target_form->setCreatedBy($this->_user);
			$target_form->setDateCreated(new \DateTime());
			$target_form->setDateModified(new \DateTime());
			$target_form->setModifiedBy($this->_user);
			$this->_em->persist($target_form);

			$src_properties = $f->getFormProperties();
			if(!empty($src_properties)){
				$properties = clone $src_properties;
				$properties->setAppForm($target_form);
				$properties->setModifiedBy($this->_user);
				$properties->setDateModified(new \DateTime());
				$this->_em->persist($properties);
			}
			
			$src_attprop = $f->getAttachmentProp();
			if(!empty($src_attprop)){
				$att_prop = clone $src_attprop;
				$att_prop->setAppForm($target_form);
				$this->_em->persist($att_prop);
			}
			
			$this->copyFormElements($f, $target_form);
			$this->copyFormWorkflows($f, $target_form);
		
			$this->copyFiles('FORMS', $f->getFormName(),  $newformname);
			
		}
		catch (\Exception $e) {
			//@todo: log the error and form name which is not copied
			echo "exception ".$e->getMessage();
		}
	}
	
	/**
	 * Copies all forms and form elements of source app to target app
	 */
	public function copyForms()
	{
		$forms = $this->_source->getForms();
		if ($forms->count())
		{
			foreach ($forms as $f)
			{
				$this->copyForm($f);
			}
		}
	}

	/**
	 * Copy an app view
	 * 
	 * @param \Docova\DocovaBundle\ObjectModel\Docova $docova_obj
	 * @param \Docova\DocovaBundle\Entity\AppViews $v
	 * @throws \Exception
	 */
	public function copyView($v){
		try {
			$target_view = clone $v;

			$newviewname = $this->getNewElementName("view", $v->getViewName());
			$target_view->setViewName($newviewname);
			$target_view->setApplication($this->_target);
			$target_view->setCreatedBy($this->_user);
			$target_view->setModifiedBy($this->_user);
			$target_view->setDateCreated(new \DateTime());
			$target_view->setDateModified(new \DateTime());
			$this->_em->persist($target_view);
			$this->_em->flush();
			
			$vhandler = new ViewManipulation($this->_docova);
			$vhandler->beginTransaction();
			try {
				$xml = new \DOMDocument();
				$xml->loadXML($target_view->getViewPerspective());
				$vhandler->createViewTable($target_view, $xml);
				$vhandler->commitTransaction();
			}
			catch (\Exception $e) {
				$vhandler->rollbackTransaction();
				$this->_em->remove($target_view);
				$this->_em->flush();
				throw new \Exception($e->getMessage());
			}
			
			$this->copyFiles('TOOLBAR', $v->getViewName(), $newviewname);
			$vhandler = null;
			return $target_view->getId();
		}
		catch (\Exception $e) {
			//@note: log the error and view name which is not copied
		}
	}
	
	/**
	 * Copy all app views to target app
	 * 
	 * @param object $router
	 */
	public function copyViews($router)
	{
		$views = $this->_source->getViews();
		if ($views->count() > 0)
		{
			foreach ($views as $v)
			{
				$this->copyView( $v);
			}
		}
	}

	/**
	 * Copy an app layout
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppLayout $l
	 */
	public function copyLayout($l)
	{
		try {
			$target_layout = clone $l;

			$newlayoutname = $this->getNewElementName("layout", $l->getLayoutId());
			$target_layout->setLayoutId($newlayoutname);
		

			$target_layout->setApplication($this->_target);
			$target_layout->setCreatedBy($this->_user);
			$target_layout->setModifiedBy($this->_user);
			$target_layout->setDateCreated(new \DateTime());
			$target_layout->setDateModified(new \DateTime());
			$this->_em->persist($target_layout);
			$this->_em->flush();
			
			$this->copyFiles('LAYOUTS', $l->getLayoutId(), $newlayoutname);
			$target_layout = $l = null;
		}
		catch (\Exception $e) {
			//@TODO: log the error and layout name which is not copied
		}
	}
	
	/**
	 * Copy all application layouts
	 */
	public function copyLayouts()
	{
		$layouts = $this->_source->getLayouts();
		if ($layouts->count() > 0)
		{
			foreach ($layouts as $l)
			{
				$this->copyLayout($l);
			}
		}
	}

	/**
	 * Copy an app page
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppPages $p
	 */
	public function copyPage($p)
	{
		try {
			$target_page = clone $p;
			$newname = $this->getNewElementName("page", $p->getPageName());
			$target_page->setPageName($newname);
		
			$target_page->setApplication($this->_target);
			$target_page->setCreatedBy($this->_user);
			$target_page->setModifiedBy($this->_user);
			$target_page->setDateCreated(new \DateTime());
			$target_page->setDateModified(new \DateTime());
			$this->_em->persist($target_page);
			$this->_em->flush();
			
			$this->copyFiles('PAGES', $p->getPageName(), $newname);
			$target_page = $p = null;
		}
		catch (\Exception $e) {
			//@todo: log the error and page name which is not copied
		}
	}

	/**
	 * Copy all app pages to target
	 */
	public function copyPages()
	{
		$pages = $this->_source->getPages();
		if ($pages->count() > 0)
		{
			foreach ($pages as $p)
			{
				$this->copyPage($p);
			}
		}
	}

	/**
	 * Copy an app subform
	 * 
	 * @param \Docova\DocovaBundle\Entity\Subforms $s
	 */
	public function copySubform($s)
	{
		try {
			$target_subform = clone $s;

			if ( ! $s->getTrash() ){
				$newformname = $this->getNewElementName("subform", $s->getFormFileName());
				$target_subform->setFormFileName($newformname);
			}else{
				$newformname = $s->getFormFileName();
			}
			$target_subform->setApplication($this->_target);
			$target_subform->setCreator($this->_user);
			$target_subform->setModifiedBy($this->_user);
			$target_subform->setDateCreated(new \DateTime());
			$target_subform->setDateModified(new \DateTime());
			$this->_em->persist($target_subform);
				
			$this->copyFormElements($s, $target_subform, true);
			$this->_em->flush();
				
			$this->copyFiles('SUBFORMS', $s->getFormFileName(), $newformname);
			$target_subform = $s = null;
		}
		catch (\Exception $e) {
			//@note: log the error and subform name which is faild to copy
		}	
	}

	/**
	 * Copy all app subforms to the target
	 */
	public function copySubforms()
	{
		$subforms = $this->_source->getSubforms();
		if ($subforms->count() > 0)
		{
			foreach ($subforms as $s)
			{
				$this->copySubform($s);
			}
		}
	}

	/**
	 * Copy an app outline
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppOutlines $o
	 */
	public function copyOutline($o){
		try {
			$target_outline = clone $o;
			$newname = $this->getNewElementName("outline", $o->getOutlineName());
			$target_outline->setOutlineName($newname);

			$target_outline->setApplication($this->_target);
			$target_outline->setCreatedBy($this->_user);
			$target_outline->setModifiedBy($this->_user);
			$target_outline->setDateCreated(new \DateTime());
			$target_outline->setDateModified(new \DateTime());
			$this->_em->persist($target_outline);
			$this->_em->flush();
			
			$this->copyFiles('OUTLINE', $o->getOutlineName(), $newname);
			$target_outline = $o = null;
		}
		catch (\Exception $e) {
			//@TODO: log the error and outline name which is failed
		}
	}
	
	/**
	 * Copy all application outlines to target
	 */
	public function copyOutlines()
	{
		$outlines = $this->_source->getOutlines();
		if ($outlines->count() > 0)
		{
			foreach ($outlines as $o)
			{
				$this->copyOutline($o);
			}
		}
	}

	/**
	 * Copy an app JavaScript
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppJavaScripts $js
	 */
	public function copyJavasScript($js){
		try {
			$target_js = clone $js;
			$newname = $this->getNewElementName("js", $js->getJSName());
			$target_js->setJSName($newname);

			$target_js->setApplication($this->_target);
			$target_js->setCreatedBy($this->_user);
			$target_js->setModifiedBy($this->_user);
			$target_js->setDateCreated(new \DateTime());
			$target_js->setDateModified(new \DateTime());
			$this->_em->persist($target_js);
			$this->_em->flush();
			
			$this->copyFiles('JAVASCRIPT', $js->getJSName(), $newname);
			$target_js = $js = null;
		}
		catch (\Exception $e) {
			//@TODO: log the error and failed javascript file name
		}
	}
	
	/**
	 * Copy all application javascripts to target
	 */
	public function copyJavaScripts()
	{
		$jscirpts = $this->_source->getjScripts();
		if ($jscirpts->count() > 0)
		{
			foreach ($jscirpts as $js)
			{
				$this->copyJavasScript($js);
			}
		}
	}

	/**
	 * Copy an app agent
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppAgents $a
	 */
	public function copyAgent($a){
		try {
			$target_agent = clone $a;
			$newname = $this->getNewElementName("agent", $a->getAgentName());
			$target_agent->setAgentName($newname);
			$target_agent->setApplication($this->_target);
			$target_agent->setCreatedBy($this->_user);
			$target_agent->setModifiedBy($this->_user);
			$target_agent->setDateCreated(new \DateTime());
			$target_agent->setDateModified(new \DateTime());
			$this->_em->persist($target_agent);
			$this->_em->flush();
			
			$this->copyFiles('AGENTS', $a->getAgentName(), $newname);
			$target_agent = $a = null;
		}
		catch (\Exception $e) {
			//@todo: log the error and failed agent name
		}
	}
	
	/**
	 * Copy all application agents to target
	 */
	public function copyAgents()
	{
		$agents = $this->_source->getAgents();
		if ($agents->count() > 0)
		{
			foreach ($agents as $a)
			{
				$this->copyAgent($a);
			}
		}
	}

	/**
	 * Copy an app Script Library
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppPhpScripts $s
	 */
	public function copyScriptLibrary($s)
	{
		try {
			$target_script = clone $s;
			$newname = $this->getNewElementName("sl", $s->getPhpName());
			$target_script->setPhpName($newname);
			$target_script->setApplication($this->_target);
			$target_script->setCreatedBy($this->_user);
			$target_script->setModifiedBy($this->_user);
			$target_script->setDateCreated(new \DateTime());
			$target_script->setDateModified(new \DateTime());
			$this->_em->persist($target_script);
			$this->_em->flush();
			
			$this->copyFiles('SCRIPTLIBRARIES', $s->getPhpName(), $newname);
			$target_script = $s = null;
		}
		catch (\Exception $e) {
			//@todo: log the error and failed php script name
		}
	}

	/**
	 * Copy all php script libraries to target
	 */
	public function copyScriptLibraries()
	{
		$scripts = $this->_source->getPhpScripts();
		if ($scripts->count() > 0)
		{
			foreach ($scripts as $s)
			{
				$this->copyScriptLibrary($s);
			}
		}
	}

	/**
	 * Copy an app image/file
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppFiles $f
	 */
	public function copyImage($f)
	{
		try {
			$target_file = clone $f;
			$newname = $this->getNewElementName("image", $f->getFileName());
			$target_file->setFileName($newname);
			$target_file->setApplication($this->_target);
			$target_file->setCreatedBy($this->_user);
			$target_file->setModifiedBy($this->_user);
			$target_file->setDateCreated(new \DateTime());
			$target_file->setDateModified(new \DateTime());
			$this->_em->persist($target_file);
			$this->_em->flush();
			
			$this->copyFiles('IMAGEFILES', $f->getFileName(), $newname);
			$target_file = $f = null;
		}
		catch (\Exception $e) {
			//@note: log the error and failed image/file name
		}
	}
	
	/**
	 * Copy all application images to target 
	 */
	public function copyImages()
	{
		$files = $this->_source->getFiles();
		if ($files->count() > 0)
		{
			foreach ($files as $f)
			{
				$this->copyImage($f);
			}
		}
	}

	/**
	 * Copy an app CSS
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppCss $c
	 */
	public function copyCss($c){
		try {
			$target_css = clone $c;
			$newname = $this->getNewElementName("css", $c->getCssName());
			$target_css->setCssName($newname);
			$target_css->setApplication($this->_target);
			$target_css->setCreatedBy($this->_user);
			$target_css->setModifiedBy($this->_user);
			$target_css->setDateCreated(new \DateTime());
			$target_css->setDateModified(new \DateTime());
			$this->_em->persist($target_css);
			$this->_em->flush();
			
			$this->copyFiles('CSS', $c->getCssName(), $newname);
			$target_css = $c = null;
		}
		catch (\Exception $e) {
			//@note: log the error and failed css file name
		}
	}
	
	/**
	 * Copy all application css files to target
	 */
	public function copyCsses()
	{
		$csses = $this->_source->getCsses();
		if($csses->count() > 0)
		{
			foreach ($csses as $c)
			{
				$this->copyCss($c);
			}
		}
	}
	
	/**
	 * Move the assets for the target app to web directory
	 * 
	 * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
	 */
	public function installAssets($kernel)
	{
		$command = new Application($kernel);
		$command->setAutoExit(false);
			
		$inputs = new ArrayInput(array(
			'command' => 'docova:appassetsinstall',
			'appid' => $this->_target->getId()
		));
			
		$command->run($inputs);
	}

	/**
	 * Copy all form elements
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppForms|\Docova\DocovaBundle\Entity\Subforms $src_form
	 * @param \Docova\DocovaBundle\Entity\AppForms $target_form
	 */
	protected function copyFormElements($src_form, $target_form, $is_subform = false)
	{
		if ($is_subform === true)
			$elements = $src_form->getSubformFields();
		else
			$elements = $src_form->getElements();
		if ($elements->count() > 0)
		{
			foreach ($elements as $e)
			{
				$new_elem = clone $e;
				$new_elem->setDateModified(new \DateTime());
				if ($is_subform === true) {
					$new_elem->setSubform($target_form);
				}
				else {
					$new_elem->setForm($target_form);
				}
				$new_elem->setModifiedBy($this->_user);
				$this->_em->persist($new_elem);
				$this->_em->flush();
			}
		}
	}
	
	/**
	 * Copy all form workflows
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppForms $src_form
	 * @param \Docova\DocovaBundle\Entity\AppForms $target_form
	 */
	protected function copyFormWorkflows($src_form, $target_form)
	{
		$workflows = $src_form->getFormWorkflows();
		if ($workflows->count() > 0)
		{
			foreach ($workflows as $wf)
			{
				//Clone workflow
				$new_wf = clone $wf;
				$new_wf->setApplication($this->_target);
				$new_wf->setDateModified(new \DateTime());
				$this->_em->persist($new_wf);
				
				$steps = $wf->getSteps();
				if (!empty($steps) && $steps->count() > 0)
				{
					foreach ($steps as $s)
					{
						//Clone each workflow step
						$wf_step = clone $s;
						$wf_step->setWorkflow($new_wf);
						$participants = $s->getOtherParticipant();
						if (!empty($participants))
						{
							foreach ($participants as $p) {
								$wf->addOtherParticipant($p);
							}
						}
						$this->_em->persist($wf_step);

						$actions = $s->getActions();
						if ($actions->count() > 0)
						{
							foreach ($actions as $a)
							{
								//Clone each workflow step action
								$sp_action = clone $a;
								$sp_action->setStep($wf_step);
								$sendto = $a->getSendTo();
								if (!empty($sendto) && $sendto->count() > 0)
								{
									foreach ($sendto as $st) {
										$sp_action->addSendTo($st);
									}
								}
								$this->_em->persist($sp_action);
							}
						}
					}
				}
				$this->_em->flush();
			}
		}
	}
}