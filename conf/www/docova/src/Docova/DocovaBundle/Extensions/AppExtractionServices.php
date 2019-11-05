<?php

namespace Docova\DocovaBundle\Extensions;

use Docova\DocovaBundle\Extensions\TransactionManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Intl\Data\Bundle\Reader\JsonBundleReader;
use Symfony\Component\Finder\Finder;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Entity\AppAcl;

/**
 * Class to handle extraction of imported app zip and upgrade the design for the app
 * @author javad_rahimi
 */
class AppExtractionServices extends TransactionManager
{
	private $_container;
	private $_zipfile;
	private $_tmpath;
	private $_user;
	private $_app;
	private $_total;
	private $_isnew;
	
	public function __construct($container, EntityManager $manager, $zipfile, $user)
	{
		if (!file_exists($zipfile))
		{
			throw new \Exception('Oops! Zip file not found.');
		}
		
		if (empty($user))
		{
			throw new \Exception('Oops! Modifier is unknown.');
		}

		$this->_container = $container;
		$this->_zipfile = $zipfile;
		$this->_user = $user;
		$this->_tmpath = sys_get_temp_dir();
		$this->_app = str_replace(['Repository/','.zip'], '', strstr($zipfile, 'Repository/'));
		
		parent::__construct($manager);
	}
	
	/**
	 * Extract the zip file to temp folder
	 * 
	 * @throws \Exception
	 * @return boolean
	 */
	public function extractApp()
	{
		$zip = new \ZipArchive();
		if (true !== $res = $zip->open($this->_zipfile))
		{
			throw new \Exception($this->fetchErrMsg($res));
		}
		if (true !== $zip->extractTo($this->_tmpath)) {
			throw new \Exception('App extraction failed.');
		}
		$zip->close();
		return true;
	}
	
	/**
	 * Publish/upgrade an application
	 * 
	 * @return array
	 */
	public function publishApp()
	{
		$json_obj = new JsonBundleReader();
		$app_json = $json_obj->read($this->_tmpath.DIRECTORY_SEPARATOR.$this->_app, 'docova_exportapp_1_library');

		$length = 1;
		$handle = opendir($this->_tmpath.DIRECTORY_SEPARATOR.$this->_app);
		while (false !== $name = readdir($handle))
		{
			if ($name != '.' && $name != '..' && $name != 'docova_exportapp_1_library.json')
			{
				$filePath = $this->_tmpath.DIRECTORY_SEPARATOR.$this->_app.DIRECTORY_SEPARATOR.$name;
				if (is_file($filePath)) {
					$name = str_replace('.json', '', $name);
					$json_arr = $json_obj->read($this->_tmpath.DIRECTORY_SEPARATOR.$this->_app, $name);
					$length += count($json_arr);
				}
				elseif (is_dir($filePath)) {
					$files = $this->getFileCounts($filePath);
					$length += count($files);
				}
			}
		}
		$this->_total = $length;
		
		if (!$this->recordExists('tb_libraries', $this->_app))
		{
			$this->_isnew = true;
			$app_json[0]['Host_Name'] = '???';
			$app_json[0]['Date_Created'] = new \DateTime();
			
			$status = $this->insertData('tb_libraries', $app_json[0]);
			$this->createAppAcl();
		}
		else {
			$this->_isnew = false;
			unset($app_json[0]['id'], $app_json[0]['Status'], $app_json[0]['Date_Created'], $app_json[0]['Host_Name']);
			$app_json[0]['Date_Updated'] = new \DateTime();
			
			$status = $this->updateData('tb_libraries', $app_json[0], 'id = ?', [$this->_app]);
		}
		
		return ['Status' => $status, 'Total' => $length];
	}
	
	/**
	 * Create/Update app elements meta data and transfer related files
	 * 
	 * @param string $kernel_path
	 */
	public function publishAppElements($kernel_path)
	{
		$completed = 1;
		$json_obj = new JsonBundleReader();
		$json_files = [
			['docova_exportapp_2_roles.json', 'tb_user_roles'],
			['docova_exportapp_3_agents.json', 'tb_app_agents'],
			['docova_exportapp_4_css.json', 'tb_app_css'],
			['docova_exportapp_5_forms.json', 'tb_app_forms'],
			['docova_exportapp_6_form_prop.json', 'tb_app_form_properties'],
			['docova_exportapp_7_form_att_prop.json', 'tb_app_form_att_properties'],
			['docova_exportapp_9_js.json', 'tb_app_javascripts'],
			['docova_exportapp_10_layouts.json', 'tb_app_layouts'],
			['docova_exportapp_11_outlines.json', 'tb_app_outlines'],
			['docova_exportapp_12_pages.json', 'tb_app_pages'],
			['docova_exportapp_13_php.json', 'tb_app_phpscripts'],
			['docova_exportapp_14_views.json', 'tb_app_views'],
			['docova_exportapp_15_subforms.json', 'tb_subforms'],
			['docova_exportapp_16_subform_actions.json', 'tb_subform_action_buttons'],
			//['docova_exportapp_17_profiles.json', 'tb_folders_documents'],		
			['docova_exportapp_18_designelements.json', 'tb_design_elements'],
			['docova_exportapp_19_files.json', 'tb_app_files']
		];
		
		for ($x = 0; $x < 16; $x++ )
		{
			if (file_exists($this->_tmpath.DIRECTORY_SEPARATOR.$this->_app.DIRECTORY_SEPARATOR.$json_files[$x][0]))
			{
				$table_name = $json_files[$x][1];
				$file_name = str_replace('.json', '', $json_files[$x][0]);
				$elements = $json_obj->read($this->_tmpath.DIRECTORY_SEPARATOR.$this->_app, $file_name);
				foreach ($elements as $record)
				{
					$percent = 0;
					if ($this->_isnew === true) {
						$this->adjustFieldValues($record, $json_files[$x][0]);
						if ($this->insertData($table_name, $record)) {
							$completed++;
							$percent = round($completed * 100 / $this->_total);
						}
					}
					else {
						if (!$this->recordExists($table_name, $record['id'])) {
							$this->adjustFieldValues($record, $json_files[$x][0]);
							if ($this->insertData($table_name, $record)) {
								$completed++;
							}
						}
						else {
							$this->adjustFieldValues($record, $json_files[$x][0], true);
							if ($this->updateData($table_name, $record, 'id = ?', [$record['id']])) {
								$completed++;
							}
						}
						$percent = round($completed * 100 / $this->_total);
					}
					
					if ($percent && file_exists($kernel_path.'/../../../log/extract_'. $this->_app .'.log')) {
						file_put_contents($kernel_path.'/../../../log/extract_'. $this->_app .'.log', 'Status:OK;Published:'.$percent);
					}
				}
			}
		}

		$this->transferFiles($kernel_path, $completed);
		$this->cleanup();
	}
	
	/**
	 * Generate and execute INSERT query
	 * 
	 * @param string $table
	 * @param array $data
	 * @throws \Exception
	 * @return boolean
	 */
	private function insertData($table, $data)
	{
		if (empty($table) || empty($data)) {
			throw new \Exception('Invalid query entries.');
		}

		$query = 'INSERT INTO '.$table.' (';
		foreach ($data as $field => $value) {
			$query .= "$field, ";
		}
		$len = count($data);
		$query = substr_replace($query, ') VALUES (', -2);
		$query = $query.str_repeat('?, ', $len);
		$query = substr_replace($query, ')', -2);
		
		$x = 1;
		$stmt = $this->prepare($query);
		foreach ($data as $value) {
			if ($value instanceof \DateTime) {
				$value = $value->format('Y-m-d h:i:s');
			}
			if (is_null($value) || $value == 'null' || $value == '') {
				$value = null;
			}
			
			$stmt->bindValue($x, $value);
			$x++;
		}
		
		$res = $stmt->execute();
		if (false === $res) {
			throw new \Exception($stmt->errorCode());
		}
		elseif (!$stmt->rowCount()) {
			throw new \Exception('No application was added to DB.');
		}
		return true;
	}
	
	/**
	 * Generate ACL entries and App Acl records when it's a new application
	 */
	private function createAppAcl()
	{
		$acl = new CustomACL($this->_container);
		$app = $this->_em->getReference('DocovaBundle:Libraries', $this->_app);
		$user = $this->_em->getReference('DocovaBundle:UserAccounts', $this->_user);
		$acl->insertObjectAce($app, 'ROLE_ADMIN', 'owner');
		$acl->insertObjectAce($app, $user, 'owner', false);
		$acl->insertObjectAce($app, 'ROLE_USER', 'edit');

		$acl_property = new AppAcl();
		$acl_property->setApplication($app);
		$acl_property->setCreateDocument(true);
		$acl_property->setDeleteDocument(true);
		$acl_property->setUserObject($user);
		$this->_em->persist($acl_property);
		$this->_em->flush();
		
		$group = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => 'ROLE_USER'));
		$acl_property = new AppAcl();
		$acl_property->setApplication($app);
		$acl_property->setCreateDocument(true);
		$acl_property->setDeleteDocument(true);
		$acl_property->setGroupObject($group);
		$this->_em->persist($acl_property);
		$this->_em->flush();
	}
	
	/**
	 * Check if record exists in the table
	 * 
	 * @param string $table
	 * @param string $id
	 * @throws \Exception
	 * @return boolean
	 */
	private function recordExists($table, $id)
	{
		if (empty($table) || empty($id)) {
			throw new \Exception('Invalid query entries.');
		}
		
		$query = 'SELECT COUNT(id) FROM '.$table.' WHERE id = ?';
		$result = $this->fetchArray($query, [$id]);
		if (!empty($result[0])) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Generate and execute an UPDATE query
	 * 
	 * @param string $table
	 * @param array $data
	 * @param string $where
	 * @param array $params
	 * @throws \Exception
	 * @return boolean
	 */
	private function updateData($table, $data, $where, $params)
	{
		if (empty($table) || empty($data)) {
			throw new \Exception('Invalid update query entries.');
		}
		
		$query = 'UPDATE '.$table.' SET ';
		foreach ($data as $field => $value)
		{
			if ($field != 'id')
			{
				if (is_null($value) || $value == '' || $value == 'null') {
					$query .= "$field = NULL, ";
				}
				else {
					$query .= "$field = ?, ";
				}
			}
		}
		$query = substr_replace($query, ' WHERE '.$where, -2);
		
		$stmt = $this->prepare($query);
		$x = 1;
		foreach ($data as $field => $value)
		{
			if ($field != 'id' && !is_null($value) && $value != '' && $value != 'null') {
				if ($value instanceof \DateTime) {
					$value = $value->format('Y-m-d h:i:s');
				}
				$stmt->bindValue($x, $value);
				$x++;
			}
		}
		
		$c = 0;
		foreach ($params as $value)
		{
			if ($value instanceof \DateTime) {
				$value = $value->format('Y-m-d h:i:s');
			}
			
			$stmt->bindValue(($c+$x), $value);
			$c++;
		}
		
		$res = $stmt->execute();
		if (false === $res) {
			throw new \Exception($stmt->errorCode());
		}
		return true;
	}
	
	/**
	 * Adjust field values tied to this update (e.g date modified, modified by and etc)
	 * 
	 * @param array $record
	 * @param string $name
	 */
	private function adjustFieldValues(&$record, $name, $is_edit = false)
	{
		switch ($name) {
			case 'docova_exportapp_3_agents.json':
			case 'docova_exportapp_4_css.json':
			case 'docova_exportapp_5_forms.json':
			case 'docova_exportapp_9_js.json':
			case 'docova_exportapp_10_layouts.json':
			case 'docova_exportapp_11_outlines.json':
			case 'docova_exportapp_12_pages.json':
			case 'docova_exportapp_13_php.json':
			case 'docova_exportapp_14_views.json':
			case 'docova_exportapp_15_subforms.json':
			case 'docova_exportapp_19_files.json':
				if ($this->_isnew || $is_edit === false) {
					$record['Date_Created'] = new \DateTime();
					$record['Created_By'] = $this->_user;
				}
				elseif ($is_edit === true) {
					unset($record['Date_Created']);
					unset($record['Created_By']);
				}
				$record['Date_Modified'] = new \DateTime();
				$record['Modified_By'] = $this->_user;
				break;
			case 'docova_exportapp_6_form_prop.json':
			case 'docova_exportapp_18_designelements.json':
				$record['Date_Modified'] = new \DateTime();
				$record['Modified_By'] = $this->_user;
				break;
			default:
				return;
		}
	}
	
	/**
	 * Transfer all app files to destination app path
	 * 
	 * @param string $target_dir
	 * @param number $count
	 * @throws \Exception
	 */
	private function transferFiles($target_dir, $count)
	{
		$file_system = new Filesystem();
		$source_dir = [
			"Resources/public/js/custom/{$this->_app}/",
			"Resources/public/css/custom/{$this->_app}/",
			"Resources/public/images/{$this->_app}/",
			"Resources/views/DesignElements/{$this->_app}/",
			'Agents/A'.str_replace("-", "", $this->_app).'/'
		];
		
		foreach ($source_dir as $sub_dir) {
			$source = $this->_tmpath.DIRECTORY_SEPARATOR.$this->_app.DIRECTORY_SEPARATOR.$sub_dir;
			$target = $target_dir.DIRECTORY_SEPARATOR.$sub_dir;
			
			if (!is_dir($source)) {
				continue;
			}
			
			if (!is_dir($target)) {
				$file_system->mkdir($target, 0777);
			}
			
			$file_system->mirror($source, $target, Finder::create()->ignoreDotFiles(false)->in($source));
			if (false !== strpos($sub_dir, '/public/')) {
				$target = $target_dir.DIRECTORY_SEPARATOR.'../../../web/bundles/docova/'.str_replace('Resources/public/', '', $sub_dir);
				$file_system->mirror($source, $target, Finder::create()->ignoreDotFiles(false)->in($source));
			}
			$count += count($this->getFileCounts($source));
			$percent = round($count * 100 / $this->_total);
			file_put_contents($target_dir.'/../../../log/extract_'. $this->_app .'.log', 'Status:OK;Published:'.$percent);
		}
	}
	
	/**
	 * Clean up all temporary files and folders (once extraction is complete)
	 */
	private function cleanup()
	{
		$file_system = new Filesystem();
		$file_system->remove($this->_tmpath.DIRECTORY_SEPARATOR.$this->_app);
	}
	
	/**
	 * Count none-meta data files in folder and subfolders
	 * 
	 * @param string $folder_path
	 * @param array $files
	 * @return array
	 */
	private function getFileCounts($folder_path, $files = [])
	{
		$handle = opendir($folder_path);
		while (false !== $name = readdir($handle))
		{
			if ($name != '.' && $name != '..')
			{
				$new_path = $folder_path.DIRECTORY_SEPARATOR.$name;
				if (is_file($new_path)) {
					array_push($files, $new_path);
				}
				elseif (is_dir($new_path)) {
					$files = $this->getFileCounts($new_path, $files);
				}
			}
		}
		closedir($handle);
		$files = array_unique($files);
		return $files;
	}
	
	/**
	 * Fetch proper error message base on passed code
	 * 
	 * @param integer $code
	 * @return string
	 */
	private function fetchErrMsg($code)
	{
		switch ($code)
		{
			case \ZipArchive::ER_EXISTS:
				return 'File already exists.';
			case \ZipArchive::ER_INCONS:
				return 'Zip archive inconsistent.';
			case \ZipArchive::ER_INVAL:
				return 'Invalid argument.';
			case \ZipArchive::ER_MEMORY:
				return 'Malloc failure.';
			case \ZipArchive::ER_NOENT:
				return 'No such file.';
			case \ZipArchive::ER_NOZIP:
				return 'Not a zip archive.';
			case \ZipArchive::ER_OPEN:
				return 'Can\'t open file.';
			case \ZipArchive::ER_READ:
				return 'Read error.';
		}
	}
}