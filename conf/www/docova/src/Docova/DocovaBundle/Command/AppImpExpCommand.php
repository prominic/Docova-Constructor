<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
//use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

//use Symfony\Component\HttpKernel\Bundle\BundleInterface;


/**
 * @author Chris Fales
 * Import/Export application
 *        
 */
class AppImpExpCommand extends ContainerAwareCommand 
{
	private $global_settings;
	private $appid="";
	private $newtitle="";
	private $filepath="";
	private $temppath="";
	private $import=false;
	private $user=null;
	private $sourceFileSubDirs=[];
	private $changeids=false;
	private $overwrite=false;
	private $remap=[];
	private $filesystem=null;
	private $DEBUG=false;
	private $appdata=[];
	
	protected function configure()
	{
		$this
			->setName('docova:appimpexpcommand')
			->setDescription('Application import export command')
			->addOption(
				'appid',
				null,
				InputOption::VALUE_REQUIRED,
				'The id of the target application.'
			)
			->addOption(
				'import',
				null,
				InputOption::VALUE_NONE,
				'Include to import an app.'
			)
			->addOption(
				'export',
				null,
				InputOption::VALUE_NONE,
				'Include to export an app.'
			)
			->addOption(
				'file',
				null,
				InputOption::VALUE_REQUIRED,
				'File name to retrieve/store import/export files.'
			)
			->addOption(
				'owner',
				null,
				InputOption::VALUE_OPTIONAL,
				'Name or id of user that design elements should be re-assigned to.'
			)
			->addOption(
				'newid',
				null,
				InputOption::VALUE_NONE,
				'Include to assign a new app id on import.'
			)
			->addOption(
				'overwrite',
				null,
				InputOption::VALUE_NONE,
				'Include to overwrite an existing app with same id on import.'
			);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{		
			$tempfile = $input->getOption('file');
			if(!empty($tempfile)){
				$this->filepath = $tempfile;
			}
			if(empty($this->filepath)){
				echo "Error: import/export file path not specified. Exiting.".PHP_EOL;
				return;
			}			
				
			if($input->getOption('import')){
				$this->import=true;
			}
			
			if($input->getOption('overwrite')){
				$this->overwrite=true;
			}
				
			
			if($input->getOption('export')){
				$this->import=false;
			}
			
			if($input->getOption('owner')){
				$this->user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $input->getOption('owner')));
			}			
						
			$em = $this->getContainer()->get('doctrine')->getManager();
			
			$this->filesystem = $this->getContainer()->get('filesystem');			
			
			$conn = $em->getConnection();
			$this->global_settings = $em->getRepository('DocovaBundle:GlobalSettings')->findAll();
			$this->global_settings = $this->global_settings[0];
			
			if($this->import){			
				if(!is_file($this->filepath)){
					echo "Error: import file not found. Exiting.".PHP_EOL;
					return;
				}
				
				$er = error_reporting();
				error_reporting(0);
				$data = file_get_contents("zip://".$this->filepath."#"."manifest.json");
				error_reporting($er);
				if(empty($data)){
					echo "Error: unable to locate manifest file in application zip. Exiting.".PHP_EOL;
					return;
				}
				$jsondata = json_decode($data, true);
				if(empty($jsondata['id'])){
					echo "Error: unable to locate application id in manifest file. Exiting.".PHP_EOL;
					return;						
				}
				$this->appdata = $jsondata;
				$this->appid = $jsondata['id'];
				
				if($input->getOption('newid')){
					$this->changeids = true;
					$this->remap[$this->appid] = $this->generateGuid();
				}				
			}else{
				$tempid = $input->getOption('appid');
				if(!empty($tempid)){
					$this->appid = $tempid;
				}		
				if(empty($this->appid)){
					echo "Error: no application id specified. Exiting.".PHP_EOL;
					return;
				}				
			}
				
			$this->sourceFileSubDirs = array(
				'Resources/public/js/custom/'.$this->appid.'/',
				'Resources/public/css/custom/'.$this->appid.'/',
				'Resources/public/images/'.$this->appid.'/',
				'Resources/views/DesignElements/'.$this->appid.'/',
				'Agents/A'.str_replace("-", "", $this->appid).'/'
			);			
			
			//--importing application
			if($this->import){
				$this->importApp($em, $conn);				
			//-- exporting application
			}else{				
				$serializer = new Serializer([new JsonSerializableNormalizer()], [new JsonEncoder()]);								
				$this->exportApp($em, $conn, $serializer);				
			}
		
	}
	
	private function exportApp($em, $conn, $serializer)
	{
		
		$appobj = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $this->appid, 'isApp' => 1));
		if(empty($appobj)){
			echo "Error: unable to find application with specified id. Exiting.".PHP_EOL;
			return false;			
		}
		
		$this->appdata['id']=$appobj->getId();
		$this->appdata['title']=$appobj->getLibraryTitle();
		$this->appdata['description']=$appobj->getDescription();
		$this->appdata['icon']=$appobj->getAppIcon();
		$this->appdata['iconcolor']=$appobj->getAppIconColor();		
		
		
		$this->temppath = sys_get_temp_dir().'/'.$this->appid.'/';
		if(!is_dir($this->temppath)){
			$this->filesystem->mkdir($this->temppath, 0777);
			if(!is_dir($this->temppath)){
				echo "Error: unable to create temporary working directory. Exiting.".PHP_EOL;
				return false;
			}
		}
		
		echo "Export App - Start".PHP_EOL;		
		
		
		//-- export database records
		$this->exportData($conn, $serializer, "SELECT * FROM tb_libraries AS L WHERE L.id = ?", "docova_exportapp_1_library.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_user_roles AS A WHERE A.Application_Id=?", "docova_exportapp_2_roles.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_agents AS A WHERE A.App_Id=?", "docova_exportapp_3_agents.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_css AS A WHERE A.App_Id=?", "docova_exportapp_4_css.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_forms AS A WHERE A.App_Id=?", "docova_exportapp_5_forms.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_form_properties AS P WHERE P.App_Form_Id IN (SELECT id FROM tb_app_forms AS A WHERE A.App_Id=?)", "docova_exportapp_6_form_prop.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_form_att_properties AS P WHERE P.App_Form_Id IN (SELECT id FROM tb_app_forms AS A WHERE A.App_Id=?)", "docova_exportapp_7_form_att_prop.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_javascripts AS A WHERE A.App_Id=?", "docova_exportapp_9_js.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_layouts AS A WHERE A.App_Id=?", "docova_exportapp_10_layouts.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_outlines AS A WHERE A.App_Id=?", "docova_exportapp_11_outlines.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_pages AS A WHERE A.App_Id=?", "docova_exportapp_12_pages.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_phpscripts AS A WHERE A.App_Id=?", "docova_exportapp_13_php.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_views AS A WHERE A.App_Id=?", "docova_exportapp_14_views.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_subforms AS A WHERE A.App_Id=?", "docova_exportapp_15_subforms.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_subform_action_buttons AS P WHERE P.Subform_Id IN (SELECT id FROM tb_subforms AS A WHERE A.App_Id=?)", "docova_exportapp_16_subform_actions.json");
		//$this->exportData($conn, $serializer, "SELECT * FROM tb_folders_documents AS D WHERE D.App_Id=? AND Profile_Name IS NOT NULL", "docova_exportapp_17_profiles.json");		
		$this->exportData($conn, $serializer, "SELECT * FROM tb_design_elements AS D WHERE (D.Form_Id IN (SELECT id FROM tb_app_forms AS A WHERE A.App_Id=?)OR D.Subform_Id IN (SELECT id FROM tb_subforms AS S WHERE S.App_Id=?) )", "docova_exportapp_18_designelements.json");
		$this->exportData($conn, $serializer, "SELECT * FROM tb_app_files AS A WHERE A.App_Id=?", "docova_exportapp_19_files.json");

		//-- copy source files
		$originDir = $this->getContainer()->get('kernel')->locateResource('@DocovaBundle');
		if (is_dir($originDir)) {
			
			foreach ($this->sourceFileSubDirs as $subdir){
				$sourceDir = $originDir.'/'.$subdir;
				$targetDir = $this->temppath.'/'.$subdir;
			
				if(is_dir($sourceDir)){
					if(!is_dir($targetDir)){
						$this->filesystem->mkdir($targetDir, 0777);
					}
			
					$this->filesystem->mirror($sourceDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($sourceDir));
				}				
			}				
		}
	
		//-- zip up directory contents		
		$this->zipDir($this->temppath, $this->filepath);			
		if(!is_file($this->filepath)){			
			echo "   Error: unable to export application to file [".$this->filepath."]".PHP_EOL;			
		}else{
			//-- write a manifest file
			file_put_contents($this->temppath.'manifest.json', $serializer->encode($this->appdata, 'json'));
			$result = $this->zipAddFile($this->filepath, $this->temppath.'manifest.json', 'manifest.json');
			echo "   Exported application to file [".$this->filepath."]".PHP_EOL;				
		}

		//-- remove the temporary files dir
		$this->filesystem->remove($this->temppath);		
				
		echo "Export App - End".PHP_EOL;
		return true;
	}

	private function importApp($em, $conn)
	{
		$this->temppath = sys_get_temp_dir();
		
		if(!file_exists($this->filepath)){
			echo "Error: import file not found. Exiting.".PHP_EOL;
			return;
		}
		
		if(empty($this->user)){
			$this->user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => 'DOCOVA SE'));
		}
		if(empty($this->user)){
			echo "Error: no default owner for imported application data was specified/found. Exiting.".PHP_EOL;
			return;
		}		
		
		if(!$this->changeids && !$this->overwrite){
			$appobj = $em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $this->appid));
			if(!empty($appobj)){
				echo "Error: existing application found with specified id. Exiting.".PHP_EOL;
				return false;
			}
		}
		
		echo "Import App - Start".PHP_EOL;		
		
		$this->unZipFile($this->filepath, $this->temppath);

		if(!(substr($this->temppath, -1) == "/" || substr($this->temppath, -1) == "\\")){
			$this->temppath .= "/";
		}
		$this->temppath .= $this->appid."/";

		
		//-- copy database records
		$this->importData($conn, "docova_exportapp_1_library.json", "tb_libraries");
		$this->importData($conn, "docova_exportapp_2_roles.json", "tb_user_roles");
		$this->importData($conn, "docova_exportapp_3_agents.json", "tb_app_agents");
		$this->importData($conn, "docova_exportapp_4_css.json", "tb_app_css");
		$this->importData($conn, "docova_exportapp_5_forms.json", "tb_app_forms");
		$this->importData($conn, "docova_exportapp_6_form_prop.json", "tb_app_form_properties");
		$this->importData($conn, "docova_exportapp_7_form_att_prop.json", "tb_app_form_att_properties");
		$this->importData($conn, "docova_exportapp_9_js.json", "tb_app_javascripts");
		$this->importData($conn, "docova_exportapp_10_layouts.json", "tb_app_layouts");
		$this->importData($conn, "docova_exportapp_11_outlines.json", "tb_app_outlines");
		$this->importData($conn, "docova_exportapp_12_pages.json", "tb_app_pages");
		$this->importData($conn, "docova_exportapp_13_php.json", "tb_app_phpscripts");
		$this->importData($conn, "docova_exportapp_14_views.json", "tb_app_views");
		$this->importData($conn, "docova_exportapp_15_subforms.json", "tb_subforms");
		$this->importData($conn, "docova_exportapp_16_subform_actions.json", "tb_subform_action_buttons");
		//$this->importData($conn, "docova_exportapp_17_profiles.json", "tb_folders_documents");		
		$this->importData($conn, "docova_exportapp_18_designelements.json", "tb_design_elements");
		$this->importData($conn, "docova_exportapp_19_files.json", "tb_app_files");		

		//-- determine if new id assigned
		$targetid = $this->appid;
		if($this->changeids && $this->remap[$this->appid]){
			$targetid = $this->remap[$this->appid];
		}
		
		//-- copy source files
		$originDir = $this->getContainer()->get('kernel')->locateResource('@DocovaBundle');
		if (is_dir($originDir)) {
				
			foreach ($this->sourceFileSubDirs as $subdir){
				$targetDir = $originDir.'/'.$subdir;
				if($this->changeids){
					if(!empty($this->remap[$this->appid])){
						$targetDir = str_replace('/'.$this->appid.'/', '/'.$targetid.'/', $targetDir);
						$targetDir = str_replace('/A'.str_replace("-", "", $this->appid).'/', '/A'.str_replace("-", "", $targetid).'/', $targetDir);						
					}
				}
				$sourceDir = $this->temppath.'/'.$subdir;
					
				if(is_dir($sourceDir)){
					if(!is_dir($targetDir)){
						$this->filesystem->mkdir($targetDir, 0777);
					}
						
					// We use a custom iterator to ignore VCS files
					$this->filesystem->mirror($sourceDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($sourceDir));
				}				
			}	
		}
		
		
		//-- install assets for the new application
		$tempapp = $this->getApplication()->find('docova:appassetsinstall');		
		$input = new ArrayInput(array(
				'command' => 'docova:appassetsinstall',
				'appid' => $targetid
		));		
		$retcode = $tempapp->run($input, new NullOutput());
		
		
		//-- create views for the new application
		$tempapp = $this->getApplication()->find('docova:generateappviews');
		$input = new ArrayInput(array(
				'command' => 'docova:generateappviews',
				'application' => $targetid
		));
		$retcode = $tempapp->run($input, new NullOutput());
		
		
		echo "   Imported new application '".$this->newtitle."' with ID ".$targetid.PHP_EOL;		
		
		//-- remove the temporary files dir
		$this->filesystem->remove($this->temppath);
		
		echo "Import App - End".PHP_EOL;
	}

	private function exportData($conn, $serializer, $query, $filename)
	{
		$result = false;
		
		if($this->DEBUG) echo "Exporting data to ".$filename.".".PHP_EOL;
		$fullfilename = $this->temppath.$filename;
		
		$stmt = $conn->prepare($query);
		for($i=1; $i<=substr_count($query, '?'); $i++){
			$stmt->bindValue($i, $this->appid);				
		}
		$stmt->execute();
		$data = $stmt->fetchAll();
		//$data=$serializer->normalize($data);
		$data=$serializer->encode($data, 'json');
		file_put_contents($fullfilename, $data);		
		
		$result = true;
		
		return $result;
	}

	
	private function importData($conn, $filename, $tablename)
	{
		$result = false;
		
		if($this->DEBUG) echo "Importing data from ".$filename." to ".$tablename.".".PHP_EOL;

		$fullfilename = $this->temppath.$filename;

		if(is_file($fullfilename)){
			$data=file_get_contents($fullfilename);
			$jsonobj=json_decode($data, true);
			if(is_array($jsonobj)){
				for($i=0; $i<count($jsonobj); $i++){
					$rowdata = $jsonobj[$i];
					$rowdata = $this->adjustData($conn, $rowdata, $tablename);
					$this->insertData($conn, $tablename, $rowdata);
				}
			}		
			$result = true;
		}

		return $result;
	}
	
	
	private function adjustData($conn, $recorddata, $tablename){
		foreach($recorddata as $key => $value){
			if($key == "Created_By" || $key == "Modified_By" || $key == "Doc_Owner" || $key == "Doc_Author" || $key == "Released_By" || $key == "Deleted_By" || $key == "Lock_Editor"){
				//-- update the created by and modified by fields to the current user
				$recorddata[$key] = $this->user->getId();
			}else if($key == "id"){
				if($this->changeids){
					//--change any record ids to unique values
					if(empty($this->remap[$recorddata[$key]])){
						$this->remap[$recorddata[$key]] = $this->generateGuid();
					}
					$recorddata[$key] = $this->remap[$recorddata[$key]];					
				}
			}else if($key == "Role_Name"){
				if($this->changeids){
					//-- change any existing role name to a unique value
					if(empty($this->remap[$recorddata[$key]])){
						$this->remap[$recorddata[$key]] = 'ROLE_APP'.strtoupper($this->generateGuid());
					}
					$recorddata[$key] = $this->remap[$recorddata[$key]];
				}
			}else if($key == "App_Id" || $key == "Application_Id" || $key == "Form_Id" || $key == "App_Form_Id" || $key == "Subform_Id" || $key == "Function_Id" || $key == "Profile_Document_Id"){
				if($this->changeids && !empty($this->remap[$recorddata[$key]])){
					//-- update any matching id values
					$recorddata[$key] = $this->remap[$recorddata[$key]];
				}
			}else if($key == "Library_Title" && $tablename == "tb_libraries"){
				$apptitle = $recorddata[$key];
				$isunique = false;
				$count = 1;

				if(!$this->overwrite){
					//check that app title is unique				
					while($isunique == false && $count < 100){
						$apptitle = $recorddata[$key].($count == 1 ? "" : " (".$count.")");
						$query = "SELECT Library_Title FROM tb_libraries AS L WHERE L.Library_Title=? AND L.Trash=?";
						$stmt = $conn->prepare($query);
						$stmt->bindValue(1, $apptitle);
						$stmt->bindValue(2, false);					
						$stmt->execute();
						$data = $stmt->fetchAll();
						if(empty($data)){
							$isunique = true;	
						}			
						$count ++;
					}
				}
				$recorddata[$key] = $apptitle;
				$this->newtitle = $apptitle;

			}
		}
		return $recorddata;
	}
	
	
	private function insertData($conn, $tablename, $recorddata){
		$result = false;

		$fieldlist = "";
		$valuelist = "";		
		foreach($recorddata as $key => $value){
			if(!empty($fieldlist)){
				$fieldlist .= ",";
				$valuelist .= ",";
			}
			$fieldlist .= $key;
			$valuelist .= "?";			
		}
		$query = "INSERT INTO ".$tablename." (".$fieldlist.") VALUES (".$valuelist.");";
		$stmt = $conn->prepare($query);
		$i=0;
		foreach($recorddata as $key => $value){
			$i++;
			$stmt->bindValue($i, $value);
		}		
		if($this->DEBUG) echo $query.PHP_EOL;
		try{
			$result = $stmt->execute();
		}catch(\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e){
			echo "Unable to insert records to table [".$tablename."] as they already exist.".PHP_EOL;				
		}catch(\Exception $e){
			echo "Error inserting records into table [".$tablename."]:".PHP_EOL;
			echo $e->getMessage().PHP_EOL;
		}
		
		return $result;
	}
	
	
	/**
	 * Generate a guid/uuid string
	 *
	 * @return string
	 */
	private function generateGuid()
	{
		if (function_exists('com_create_guid')) {
			return strtolower(com_create_guid());
		}
		else {
			mt_srand((double)microtime()*10000);
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12);
			return strtolower($uuid);
		}
	}
	
	
	/**
	 * Add files and sub-directories in a folder to zip file.
	 * @param string $folder
	 * @param \ZipArchive $zipFile
	 * @param int $exclusiveLength Number of text to be exclusived from the file path.
	 */
	private function folderToZip($folder, &$zipFile, $exclusiveLength) {
		$handle = opendir($folder);
		while (false !== $f = readdir($handle)) {
			if ($f != '.' && $f != '..') {
				$filePath = "$folder/$f";
				// Remove prefix from file path before add to zip.
				$localPath = substr($filePath, $exclusiveLength);
				if (is_file($filePath)) {
					$zipFile->addFile($filePath, $localPath);
				} elseif (is_dir($filePath)) {
					// Add sub-directory.
					$zipFile->addEmptyDir($localPath);
					$this->folderToZip($filePath, $zipFile, $exclusiveLength);
				}
			}
		}
		closedir($handle);
	}
	
	/**
	 * Zip a folder (include itself).
	 * Usage:
	 *   zipDir('/path/to/sourceDir', '/path/to/out.zip');
	 *
	 * @param string $sourcePath Path of directory to be zip.
	 * @param string $outZipPath Path of output zip file.
	 */
	private function zipDir($sourcePath, $outZipPath)
	{
		while(substr($sourcePath, -1) == "/" || substr($sourcePath, -1) == "\\"){
			$sourcePath = substr($sourcePath, 0, -1);	
		}
		$pathInfo = pathInfo($sourcePath);
		$parentPath = $pathInfo['dirname'];
		$dirName = $pathInfo['basename'];

		$z = new \ZipArchive();
		if ($z->open($outZipPath, \ZipArchive::CREATE)!==TRUE) {
			return;
		}		
		$z->addEmptyDir($dirName);
		$this->folderToZip($sourcePath, $z, strlen("$parentPath/"));
		$z->close();
	}
	
	/**
	 * unZip a file to a folder
	 * Usage:
	 *   unZipFile('/path/to/in.zip', '/path/to/OutputFolder');
	 *
	 * @param string $inpZipPath Path of output zip file.
	 * @param string $outPath Path of directory to unzip to. 
	 */
	private function unZipFile($inpZipPath, $outPath)
	{
		if(!file_exists($inpZipPath) || !is_dir($outPath)){
			return false;
		}
			
		$z = new \ZipArchive();
		if ($z->open($inpZipPath)!==TRUE) {
			return false;
		}
		$z->extractTo($outPath);
		$z->close();
		return true;
	}	

	/**
	 * zipAddFile adds a file to an existing zip
	 * Usage:
	 *   zipAddFile('/path/to/in.zip', '/path/to/fileToAdd');
	 *
	 * @param string $inpZipPath Path of existing zip file.
	 * @param string $filePath Path of file to add to zip.
	 * @param string $localName File name of file within the zip
	 */	
	private function zipAddFile($inpZipPath, $filePath, $localName)
	{
		if(!file_exists($inpZipPath) || !file_exists($filePath)){
			return false;
		}
			
		$z = new \ZipArchive();
		if ($z->open($inpZipPath)!==TRUE) {
			return false;
		}
		$z->addFile($filePath, $localName);
		$z->close();
		return true;		
	}
		
	
}