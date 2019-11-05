<?php

namespace Docova\DocovaBundle\Extensions;

/**
 * Class to handle copy application different design system files
 * @author javad_rahimi
 */
define('_SL', '/ScriptLibraries');
define('_LAYOUTS', '/layouts');
define('_OUTLINE', '/outline');
define('_PAGES', '/pages');
define('_SUBFORMS', '/subforms');
define('_TOOLBAR', '/toolbar');
define('_TEMPLATE', '/templates');
define('_TAGENTS', _TEMPLATE.'/AGENTS');
define('_TFORMS', _TEMPLATE.'/FORM');
define('_TPAGES', _TEMPLATE.'/PAGE');
define('_TSUBFORMS', _TEMPLATE.'/SUBFORM');
define('_TTOOLBAR', _TEMPLATE.'/View');
define('_JFORMS', '/FORM');
define('_CSSFORMS', '/FORM');
define('_JOUTLINE', '/OUTLINE');
define('_COUTLINE', '/outlines');
define('_JPAGES', '/PAGE');
define('_JSUBFORMS', '/SUBFORM');

class CopyFilesServices 
{
	private $src_app;
	private $trg_app;
	private $base_paths;
	private $source_paths;
	private $target_paths;
	
	public function __construct($rootpath, $source)
	{		
		$this->base_paths = array();
		$this->base_paths["designelements"] = $rootpath . '/../src/Docova/DocovaBundle/Resources/views/DesignElements/';
		$this->base_paths["js"] = $rootpath . '/../src/Docova/DocovaBundle/Resources/public/js/custom/';
		$this->base_paths["css"] = $rootpath . '/../src/Docova/DocovaBundle/Resources/public/css/custom/';
		$this->base_paths["images"] = $rootpath . '/../src/Docova/DocovaBundle/Resources/public/images/';
		$this->base_paths["agents"] = $rootpath . '/../src/Docova/DocovaBundle/Agents/A';
		
		
		$this->src_app = $source;
		$this->source_paths = $this->base_paths;
		foreach ($this->source_paths as $key=>&$value) {
			if($key == "agents"){
				$value = $value . str_replace('-', '', $source);	
			}else{
				$value = $value . $source;
			}
		}
		unset($value);
	}
	
	/**
	 * Set and generate target folder path
	 *
	 * @param string $target
	 */
	public function setTargetPath($target)
	{
		$this->trg_app = $target;
	
		$this->target_paths = $this->base_paths;
		foreach ($this->target_paths as $key=>&$value) {
			if($key == "agents"){
				$value = $value . str_replace('-', '', $target);
			}else{
				$value = $value . $target;
			}			
		}
		unset($value);
	}	
	
	public function copyFiles($action, $param, $newname = "")
	{
		if (empty($this->target_paths))
			return false;
					
		$action = strtoupper($action);
		if (method_exists($this, 'copy'.$action))
		{
			$this->createPath($action);
			$results = call_user_func(array($this, "copy$action"), $param, $newname);
			return $results;
		}
		return false;
	}
	
	
	/**
	 * Generate folders for design element path
	 * 
	 * @param string $action
	 */
	private function createPath($action)
	{
		
		if (!is_dir($this->target_paths["designelements"])) {
			@mkdir($this->target_paths["designelements"], 0755, true);
		}		
		
		if(defined("_$action") && constant("_$action") != ""){
			if (!is_dir($this->target_paths["designelements"] . constant("_$action"))) {
				@mkdir($this->target_paths["designelements"] . constant("_$action"), 0755, true);
			}			
		}
		
		if(defined("_T$action") && constant("_T$action") != ""){
			if (!is_dir($this->target_paths["designelements"] . constant("_TEMPLATE"))) {
				@mkdir($this->target_paths["designelements"] . constant("_TEMPLATE"), 0755, true);
			}
			
			if (!is_dir($this->target_paths["designelements"] . constant("_T$action"))) {
				@mkdir($this->target_paths["designelements"] . constant("_T$action"), 0755, true);
			}	
		}		
		
		if (!is_dir($this->target_paths["js"])) {
			@mkdir($this->target_paths["js"], 0755, true);
		}
		
		if(defined("_J$action") && constant("_J$action") != ""){	
			if (!is_dir($this->target_paths["js"] . constant("_J$action"))) {
				@mkdir($this->target_paths["js"] . constant("_J$action"), 0755, true);
			}
		}

		if(defined("_C$action") && constant("_C$action") != ""){	

			if (!is_dir($this->target_paths["css"])) {
				@mkdir($this->target_paths["css"], 0755, true);
			}

			
			if (!is_dir($this->target_paths["css"] . constant("_C$action"))) {
				@mkdir($this->target_paths["css"] . constant("_C$action"), 0755, true);
			}
		}
		
			
		if ($action == 'AGENTS' || $action == 'SCRIPTLIBRARIES') {		
			if (!is_dir($this->target_paths["agents"])) {
				@mkdir($this->target_paths["agents"], 0755, true);
			}
		}
		
		if ($action == 'SCRIPTLIBRARIES') {
			if (!is_dir($this->target_paths["agents"] . _SL)) {
				@mkdir($this->target_paths["agents"]. _SL, 0755, true);
			}
		}		
	
		
		if ($action == 'IMAGEFILES') {
			if (!is_dir($this->target_paths["images"])) {
				@mkdir($this->target_paths["images"], 0755, true);
			}
		}		
		
		if ($action == 'CSS') {
			if (!is_dir($this->target_paths["css"])) {
				@mkdir($this->target_paths["css"], 0755, true);
			}
		}		
		
	}
	
	/**
	 * Copy all form template and twig files to target
	 * 
	 * @param string $filename
	 */
	private function copyFORMS($filename, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $filename);
		$filename = str_replace(' ', '', $filename);

		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
		
		@copy($this->source_paths["designelements"].'/'.$filename.'.html.twig', $this->target_paths["designelements"].'/'.$targetname.'.html.twig');
		$this->updateAppID($this->target_paths["designelements"].'/'.$targetname.'.html.twig');


		@copy($this->source_paths["designelements"].'/'.$filename.'_computed.html.twig', $this->target_paths["designelements"].'/'.$targetname.'_computed.html.twig');		
		@copy($this->source_paths["designelements"].'/'.$filename.'_default.html.twig', $this->target_paths["designelements"].'/'.$targetname.'_default.html.twig');
		@copy($this->source_paths["designelements"].'/'.$filename.'_read.html.twig', $this->target_paths["designelements"].'/'.$targetname.'_read.html.twig');
		$this->updateAppID($this->target_paths["designelements"].'/'.$targetname.'_read.html.twig');

		@copy($this->source_paths["designelements"].'/'.$filename.'_m.html.twig', $this->target_paths["designelements"].'/'.$targetname.'_m.html.twig');
		$this->updateAppID($this->target_paths["designelements"].'/'.$targetname.'_m.html.twig');
		@copy($this->source_paths["designelements"].'/'.$filename.'_m_read.html.twig', $this->target_paths["designelements"].'/'.$targetname.'_m_read.html.twig');
		$this->updateAppID($this->target_paths["designelements"].'/'.$targetname.'_m_read.html.twig');
		
		@copy($this->source_paths["designelements"]._TFORMS.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._TFORMS.'/'.$targetname.'.html.twig');	
		$this->updateAppID($this->target_paths["designelements"]._TFORMS.'/'.$targetname.'.html.twig');


		if (is_file($this->source_paths["js"]._JFORMS.'/'.$filename.'.js')) {
			@copy($this->source_paths["js"]._JFORMS.'/'.$filename.'.js', $this->target_paths["js"]._JFORMS.'/'.$targetname.'.js');				
		}		
		if (is_file($this->source_paths["css"]._CSSFORMS.'/'.$filename.'.css')) {
			@copy($this->source_paths["css"]._CSSFORMS.'/'.$filename.'.css', $this->target_paths["css"]._CSSFORMS.'/'.$targetname.'.css');
		}
		if (is_file($this->source_paths["js"]._JFORMS.'/TEMPLATE_'.$filename.'.js')) {	
			@copy($this->source_paths["js"]._JFORMS.'/TEMPLATE_'.$filename.'.js', $this->target_paths["js"]._JFORMS.'/TEMPLATE_'.$targetname.'.js');		
		}
	}
	
	/**
	 * Copy toolbar twig file to target
	 * 
	 * @param string $viewname
	 */
	private function copyTOOLBAR($viewname, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $viewname);
		$filename = str_replace(' ', '', $filename);
		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
		
		@copy($this->source_paths["designelements"]._TOOLBAR.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._TOOLBAR.'/'.$targetname.'.html.twig');

		@copy($this->source_paths["designelements"]._TTOOLBAR.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._TTOOLBAR.'/'.$targetname.'.html.twig');		
	}
	
	
	/**
	 * Copy layout twig file to target
	 *
	 * @param string $layoutname
	 */
	private function copyLAYOUTS($layoutname, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $layoutname);
		$filename = str_replace(' ', '', $filename);
		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
	
		@copy($this->source_paths["designelements"]._LAYOUTS.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._LAYOUTS.'/'.$targetname.'.html.twig');		

		//change any appID refrences
		$this->updateAppID($this->target_paths["designelements"]._LAYOUTS.'/'.$targetname.'.html.twig');
	}

	/**
	 * Change any source App ID to target App ID in content
	 * 
	 * @param string $srcfilepath
	 */
	private function updateAppID($srcfilepath, $ignore_dashes = false)
	{
		try{
			//change any appID refrences
			$strtosearch = $this->src_app;
			$strtoreplace =  $this->trg_app;
			if ($ignore_dashes === true) {
				$strtosearch = str_replace('-', '', $strtosearch);
				$strtoreplace = str_replace('-', '', $strtoreplace);
			}
			$content = file_get_contents($srcfilepath);
			$content  = str_ireplace($strtosearch, $strtoreplace , $content);
			file_put_contents($srcfilepath, $content);
		}
		catch (\Exception $e) { }
	}
	
	/**
	 * Copy page twig files to target
	 * 
	 * @param string $pagename
	 */
	private function copyPAGES($pagename, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $pagename);
		$filename = str_replace(' ', '', $filename);

		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
		
		@copy($this->source_paths["designelements"]._PAGES.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._PAGES.'/'.$targetname.'.html.twig');
		//change any appID refrences
		$this->updateAppID($this->target_paths["designelements"]._PAGES.'/'.$targetname.'.html.twig');

		
		@copy($this->source_paths["designelements"]._TPAGES.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._TPAGES.'/'.$targetname.'.html.twig');
		$this->updateAppID($this->target_paths["designelements"]._TPAGES.'/'.$targetname.'.html.twig');

		
		if (is_file($this->source_paths["js"]._JPAGES.'/'.$filename.'.js')) {
			@copy($this->source_paths["js"]._JPAGES.'/'.$filename.'.js', $this->target_paths["js"]._JPAGES.'/'.$targetname.'.js');
		}	
		if (is_file($this->source_paths["js"]._JPAGES.'/TEMPLATE_'.$filename.'.js')) {
			@copy($this->source_paths["js"]._JPAGES.'/TEMPLATE_'.$filename.'.js', $this->target_paths["js"]._JPAGES.'/TEMPLATE_'.$targetname.'.js');
		}		
		
	}
	
	/**
	 * Copy subform template and twig files to target
	 * 
	 * @param string $subform
	 */
	private function copySUBFORMS($subform, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $subform);
		$filename = str_replace(' ', '', $filename);

		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
		
		@copy($this->source_paths["designelements"]._SUBFORMS.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._SUBFORMS.'/'.$targetname.'.html.twig');
		$this->updateAppID($this->target_paths["designelements"]._SUBFORMS.'/'.$targetname.'.html.twig');

		@copy($this->source_paths["designelements"]._SUBFORMS.'/'.$filename.'_computed.html.twig', $this->target_paths["designelements"]._SUBFORMS.'/'.$targetname.'_computed.html.twig');
		@copy($this->source_paths["designelements"]._SUBFORMS.'/'.$filename.'_default.html.twig', $this->target_paths["designelements"]._SUBFORMS.'/'.$targetname.'_default.html.twig');		
		@copy($this->source_paths["designelements"]._SUBFORMS.'/'.$filename.'_read.html.twig', $this->target_paths["designelements"]._SUBFORMS.'/'.$targetname.'_read.html.twig');
		$this->updateAppID($this->target_paths["designelements"]._SUBFORMS.'/'.$targetname.'_read.html.twig');
		
		@copy($this->source_paths["designelements"]._TSUBFORMS.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._TSUBFORMS.'/'.$targetname.'.html.twig');
		$this->updateAppID($this->target_paths["designelements"]._TSUBFORMS.'/'.$targetname.'.html.twig');
		
		
		if (is_file($this->source_paths["js"]._JSUBFORMS.'/'.$filename.'.js')) {
			@copy($this->source_paths["js"]._JSUBFORMS.'/'.$filename.'.js', $this->target_paths["js"]._JSUBFORMS.'/'.$targetname.'.js');
		}	
		if (is_file($this->source_paths["js"]._JSUBFORMS.'/TEMPLATE_'.$filename.'.js')) {
			@copy($this->source_paths["js"]._JSUBFORMS.'/TEMPLATE_'.$filename.'.js', $this->target_paths["js"]._JSUBFORMS.'/TEMPLATE_'.$targetname.'.js');
		}		
	}
	
	
	
	/**
	 * Copy outline twig file to target
	 * 
	 * @param string $outline
	 */
	private function copyOUTLINE($outline, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $outline);
		$filename = str_replace(' ', '', $filename);
		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}

		@copy($this->source_paths["designelements"]._OUTLINE.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._OUTLINE.'/'.$targetname.'.html.twig');
		if (is_file($this->source_paths["designelements"]._OUTLINE.'/'.$filename.'_twig.html.twig')) {
			@copy($this->source_paths["designelements"]._OUTLINE.'/'.$filename.'_twig.html.twig', $this->target_paths["designelements"]._OUTLINE.'/'.$targetname.'_twig.html.twig');
		}
		
		if (is_file($this->source_paths["js"]._JOUTLINE.'/'.$filename.'.js')) {
			@copy($this->source_paths["js"]._JOUTLINE.'/'.$filename.'.js', $this->target_paths["js"]._JOUTLINE.'/'.$targetname.'.js');
		}

		
		if (is_file($this->source_paths["css"]._COUTLINE.'/'.$filename.'.css')) {
			@copy($this->source_paths["css"]._COUTLINE.'/'.$filename.'.css', $this->target_paths["css"]._COUTLINE.'/'.$targetname.'.css');
		}
		
	}
	
	/**
	 * Copy custom javascript file to target
	 * 
	 * @param string $javascript
	 */
	private function copyJAVASCRIPT($javascript, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $javascript);
		$filename = str_replace(' ', '', $filename);
		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
		
		@copy($this->source_paths["js"].'/'.$filename.'.js', $this->target_paths["js"].'/'.$targetname.'.js');
	}
	
	/**
	 * Copy agent twig and php files to target
	 * 
	 * @param string $agent
	 */
	private function copyAGENTS($agent, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $agent);
		$filename = str_replace(array(' ', '-'), '', $filename);
		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(array(' ', '-'), '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
		
		@copy($this->source_paths["designelements"]._TAGENTS.'/'.$filename.'.html.twig', $this->target_paths["designelements"]._TAGENTS.'/'.$targetname.'.html.twig');
		
		@copy($this->source_paths["agents"].'/'.$filename.'.php', $this->target_paths["agents"].'/'.$targetname.'.php');
		$this->updateAppID($this->target_paths['agents'].'/'.$targetname.'.php', true);
	}
	
	/**
	 * Copy php script libraries to target
	 * 
	 * @param string $script
	 */
	private function copySCRIPTLIBRARIES($script, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $script);
		$filename = str_replace(' ', '', $filename);
		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
		
		@copy($this->source_paths["agents"]._SL.'/'.$filename.'.php', $this->target_paths["agents"]._SL.'/'.$targetname.'.php');
	}
	
	/**
	 * Copy image/file to target
	 * 
	 * @param string $file
	 */
	private function copyIMAGEFILES($file, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $file);
		$filename = str_replace(' ', '', $filename);
		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
		
		@copy($this->source_paths["images"].'/'.$filename, $this->target_paths["images"].'/'.$targetname);
	}
	
	/**
	 * Copy css file to target
	 * 
	 * @param string $css
	 */
	private function copyCSS($css, $newname)
	{
		$filename = str_replace(array('/', '\\'), '-', $css);
		$filename = str_replace(' ', '', $filename);
		if ( !empty($newname)){
			$newname = str_replace(array('/', '\\'), '-', $newname);
			$newname = str_replace(' ', '', $newname);
			$targetname = $newname;
		}else{
			$targetname = $filename;
		}
		
		@copy($this->source_paths["css"].'/'.$filename.'.css', $this->target_paths["css"].'/'.$targetname.'.css');
	}
}