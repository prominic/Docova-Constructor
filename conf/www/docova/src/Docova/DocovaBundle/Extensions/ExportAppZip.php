<?php

namespace Docova\DocovaBundle\Extensions;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Log\Logger;

/**
 * Class to generate zip file for an application
 * Usually used prior to pushing to another server
 * @author javad_rahimi
 */
class ExportAppZip extends CommunicationServices
{
	private $_app;
	private $_user;
	private $_logger;
	private $_tmppath;
	private $_docova_path;
	private $_source_paths = [];
	
	public function __construct(EntityManager $em, $application, $user, $bundle_path)
	{
		$this->_app = $application;
		$this->_user = $user;
		$this->_docova_path = $bundle_path;
		$this->sourceFileSubDirs = array(
			"Resources/public/js/custom/$application/",
			"Resources/public/css/custom/$application/",
			"Resources/public/images/$application/",
			"Resources/views/DesignElements/$application/",
			'Agents/A'.str_replace("-", "", $application).'/'
		);			
		$this->_tmppath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$application;
		
		if (!is_dir($this->_tmppath))
		{
			$file_system = new Filesystem();
			$file_system->mkdir($this->_tmppath, 0777);
			if (!is_dir($this->_tmppath)){
				throw new \Exception('Oops! Unable to create temporary working directory! Process Terminated.');
			}
		}
		parent::__construct($em, $user);
	}
	
	public function setLogger(Logger $log)
	{
		$this->_logger = $log;
	}
	
	/**
	 * Collect application meta data and creates json files
	 * 
	 * @return boolean
	 */
	public function collectAppMetaData()
	{
		try {
			//-- export database records
			$this->exportData('SELECT * FROM tb_libraries AS L WHERE L.id = :appid', 'docova_exportapp_1_library.json');
			$this->exportData('SELECT * FROM tb_user_roles AS A WHERE A.Application_Id = :appid', 'docova_exportapp_2_roles.json');
			$this->exportData('SELECT * FROM tb_app_agents AS A WHERE A.App_Id = :appid', 'docova_exportapp_3_agents.json');
			$this->exportData('SELECT * FROM tb_app_css AS A WHERE A.App_Id = :appid', 'docova_exportapp_4_css.json');
			$this->exportData('SELECT * FROM tb_app_forms AS A WHERE A.App_Id = :appid', 'docova_exportapp_5_forms.json');
			$this->exportData('SELECT * FROM tb_app_form_properties AS P WHERE P.App_Form_Id IN (SELECT id FROM tb_app_forms AS A WHERE A.App_Id = :appid)', 'docova_exportapp_6_form_prop.json');
			$this->exportData('SELECT * FROM tb_app_form_att_properties AS P WHERE P.App_Form_Id IN (SELECT id FROM tb_app_forms AS A WHERE A.App_Id = :appid)', 'docova_exportapp_7_form_att_prop.json');
			$this->exportData('SELECT * FROM tb_app_javascripts AS A WHERE A.App_Id = :appid', 'docova_exportapp_9_js.json');
			$this->exportData('SELECT * FROM tb_app_layouts AS A WHERE A.App_Id = :appid', 'docova_exportapp_10_layouts.json');
			$this->exportData('SELECT * FROM tb_app_outlines AS A WHERE A.App_Id = :appid', 'docova_exportapp_11_outlines.json');
			$this->exportData('SELECT * FROM tb_app_pages AS A WHERE A.App_Id = :appid', 'docova_exportapp_12_pages.json');
			$this->exportData('SELECT * FROM tb_app_phpscripts AS A WHERE A.App_Id = :appid', 'docova_exportapp_13_php.json');
			$this->exportData('SELECT * FROM tb_app_views AS A WHERE A.App_Id = :appid', 'docova_exportapp_14_views.json');
			$this->exportData('SELECT * FROM tb_subforms AS A WHERE A.App_Id = :appid', 'docova_exportapp_15_subforms.json');
			$this->exportData('SELECT * FROM tb_subform_action_buttons AS P WHERE P.Subform_Id IN (SELECT id FROM tb_subforms AS A WHERE A.App_Id = :appid)', 'docova_exportapp_16_subform_actions.json');
			$this->exportData('SELECT * FROM tb_design_elements AS D WHERE (D.Form_Id IN (SELECT id FROM tb_app_forms AS A WHERE A.App_Id = :appid) OR D.Subform_Id IN (SELECT id FROM tb_subforms AS S WHERE S.App_Id = :appid) )', 'docova_exportapp_18_designelements.json');
			$this->exportData('SELECT * FROM tb_app_files AS A WHERE A.App_Id = :appid', 'docova_exportapp_19_files.json');
			return true;
		}
		catch (\Exception $e) {
			if ($this->_logger) {
				$this->_logger->error('Collecting App Meta Data Field. '.$e->getMessage(). ' on line '.$e->getLine().' of '.$e->getFile());
				return false;
			}
			else {
				return 'Collecting App Meta Data Field. '.$e->getMessage(). ' on line '.$e->getLine().' of '.$e->getFile();
			}
		}
	}
	
	/**
	 * Collect and copy all app files to tmp folder
	 * 
	 * @return boolean
	 */
	public function collectAppFiles()
	{
		$file_system = new Filesystem();
		try {
			foreach ($this->sourceFileSubDirs as $subdir)
			{
				$sourceDir = $this->_docova_path.DIRECTORY_SEPARATOR.$subdir;
				$targetDir = $this->_tmppath.DIRECTORY_SEPARATOR.$subdir;
					
				if(is_dir($sourceDir)){
					if(!is_dir($targetDir)){
						$file_system->mkdir($targetDir, 0777);
					}
						
					$file_system->mirror($sourceDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($sourceDir));
				}
			}
			$file_system = null;
			return true;
		}
		catch (\Exception $e) {
			if ($this->_logger) {
				$this->_logger->error('Collecting App Files Failed. Msg: '.$e->getMessage().' on line '.$e->getLine().' of '.$e->getFile());
				return false;
			}
			else {
				return 'Collecting App Files Failed. Msg: '.$e->getMessage().' on line '.$e->getLine().' of '.$e->getFile();
			}
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Docova\DocovaBundle\Extensions\CommunicationServices::checkUserAccess()
	 */
	public function checkUserAccess($application = null)
	{
		if (!empty($application)) {
			return parent::checkUserAccess($application);
		}
		return parent::checkUserAccess($this->_app);
	}
	
	/**
	 * Create zip file in temp directory or provided path. Removes all temp data
	 * 
	 * @param string $zip_file (optional)
	 */
	public function createZipFile($zip_file = null)
	{
		$file_system = new Filesystem();
		try {
			if (empty($zip_file))
			{
				$zip_file = $this->_tmppath.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$this->_app.'.zip';
			}
			$this->zipDir($zip_file);
			$file_system->remove($this->_tmppath);
			
			return ['Status' => 'OK', 'ZipName' => $zip_file];
		}
		catch (\Exception $e) {
			if ($this->_logger) {
				$this->_logger->error('Creating Zip File Failed. Msg: '.$e->getMessage().' on line '.$e->getLine().' of '.$e->getFile());
				return false;
			}
			else {
				return 'Creating Zip File Failed. Msg: '.$e->getMessage().' on line '.$e->getLine().' of '.$e->getFile();
			}
		}
	}
	
	/**
	 * Clean up zip files from temp folder (once it's pushed to the server)
	 * 
	 * @param unknown $zip_file
	 */
	public function cleanup($zip_file)
	{
		$file_system = new Filesystem();
		if (file_exists($zip_file))
		{
			$file_system->remove($zip_file);
		}
	}
	
	/**
	 * Execute the query and save the output in a separate json file
	 * 
	 * @param string $query
	 * @param string $filename
	 */
	private function exportData($query, $filename)
	{
		$result = $this->fetchAll($query, ['appid' => $this->_app]);
		if (!empty($result[0]))
		{
			$serialize = new Serializer([new JsonSerializableNormalizer()], [new JsonEncoder()]);
			$data = $serialize->encode($result, 'json');
			file_put_contents($this->_tmppath.DIRECTORY_SEPARATOR.$filename, $data);
		}
	}

	/**
	 * Create a zip file and archive all _tmppath content including the folder
	 * 
	 * @param string $zip_file
	 */
	private function zipDir($zip_file)
	{
		$sourcePath = $this->_tmppath;
		while(substr($sourcePath, -1) == "/" || substr($sourcePath, -1) == "\\")
		{
			$sourcePath = substr($sourcePath, 0, -1);	
		}
		$pathInfo = pathInfo($sourcePath);
		$parentPath = $pathInfo['dirname'];
		$dirName = $pathInfo['basename'];

		$zip = new \ZipArchive();
		if ($zip->open($zip_file, \ZipArchive::CREATE)!==TRUE) {
			return;
		}		
		$zip->addEmptyDir($dirName);
		$this->folderToZip($sourcePath, $zip, strlen("$parentPath/"));
		$zip->close();
	}

	/**
	 * Add files and sub-directories in a folder to the zip file.
	 * 
	 * @param string $folder
	 * @param \ZipArchive $zipFile
	 * @param int $exclusiveLength Number of text to be exclusived from the file path.
	 */
	private function folderToZip($folder, &$zipFile, $exclusiveLength)
	{
		$handle = opendir($folder);
		while (false !== $name = readdir($handle))
		{
			if ($name != '.' && $name != '..')
			{
				$filePath = $folder.DIRECTORY_SEPARATOR.$name;
				// Remove prefix from file path before add to zip.
				$localPath = substr($filePath, $exclusiveLength);
				if (is_file($filePath)) {
					$zipFile->addFile($filePath, $localPath);
				}
				else {
					// Add sub-directory.
					$zipFile->addEmptyDir($localPath);
					$this->folderToZip($filePath, $zipFile, $exclusiveLength);
				}
			}
		}
		closedir($handle);
	}
}