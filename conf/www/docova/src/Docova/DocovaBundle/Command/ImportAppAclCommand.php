<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Entity\AppAcl;
use Docova\DocovaBundle\Security\User\CustomACL;
use Docova\DocovaBundle\Entity\UserRoles;
use Docova\DocovaBundle\ObjectModel\Docova;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

$docova = null;

/**
 * Command to import/synch application acl on app level and document level
 * base on pre-imported data
 * @author javad_rahimi
 */
class ImportAppAclCommand extends ContainerAwareCommand 
{
	private $_em;
	private $_app;
	
	public function configure()
	{
		$this->setName('docova:importappacl')
			->setDescription('Import/synch application ACL')
			->addArgument('app', InputArgument::REQUIRED, 'Application ID');
	}
	
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$app = $input->getArgument('app');
		$this->_em = $this->getContainer()->get('doctrine')->getManager();
		$this->_app = $this->_em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' =>true));
		if (empty($this->_app)){
			throw new \Exception('Oops! Unspecified application source.');
		}
		
		GLOBAL $docova;
		$docova = new Docova($this->getContainer());
			
		$imported_apps = $this->_em->getRepository('DocovaBundle:MigratedAppACE')->findBy(array('application' => $app));
		if (!empty($imported_apps))
		{
//			$output->writeln('Start importing All app ACLs for "'.$this->_app->getLibraryTitle().'".');
			foreach ($imported_apps as $ace)
			{
				try {
					$acl = $this->generateAppAcl($ace);
					if (false === $acl) {
						throw new \Exception('App ACL generation failed.');
					}
					if ($ace->getRoles()) {
						$this->_em->refresh($acl);
						$this->generateAppRoles($ace, $acl);
					}
					$this->generateDocovaAcl($ace, $acl);
//					$output->writeln('Importing app ACL for "'.$ace->getName().'" is complete.');
					$ace->setMigrated(true);
					$this->_em->flush();
				}
				catch (\Exception $e) {
					$output->writeln('Acl import failed for "'. $ace->getName() . '". [Error:'.$e->getMessage().']');
				}
			}

//			$output->writeln('Start importing app documents ACL.');
			$documents = $this->_em->getRepository('DocovaBundle:Documents')->getAllAppDocuments($app);
			$count = 0;
			if (!empty($documents))
			{
				foreach ($documents as $doc) {
					$argument = array(
						'command' => 'docova:importdocumentacl',
						'docid' => $doc
					);
					
					$importOutput = new BufferedOutput();
					$importDocAcl = new ArrayInput($argument);
					$command = $this->getApplication()->find('docova:importdocumentacl');
					$ret_code = $command->run($importDocAcl, $importOutput);
					if ($ret_code == 0) {
						$result = json_decode(trim($importOutput->fetch()));
						if ($result->Status == 'OK') {
							$count++;
						}
						else {
							$output->writeln('Error on creating ACL records for document with ID "'.$doc.'", with the following message:');
							$output->writeln($result->ErrMsg);
						}
					}
				}
				$output->writeln('Status: OK, Return:'.$count);
			}
		}
		else {
			$output->writeln('Status: OK, Return:No imported application ACL record were found.');
		}
		
		unset($docova);
	}
	
	/**
	 * Generate AppAcl records
	 * 
	 * @param \Docova\DocovaBundle\Entity\MigratedAppACE $appAce
	 * @return \Docova\DocovaBundle\Entity\AppAcl|boolean
	 */
	private function generateAppAcl($appAce)
	{
		$user = $group = null;
		if ($appAce->getType() == 'Person') {
			if ($appAce->getEntityId()) {
				$user = $this->_em->getReference('DocovaBundle:UserAccounts', $appAce->getEntityId());
			}
			else {
				$user = $this->findUser($appAce->getName());
			}
			if (empty($user)){
				return false;
			}
			
			$appAcl = $this->_em->getRepository('DocovaBundle:AppAcl')->findBy(array('application' => $this->_app->getId(), 'userObject' => $user->getId()));
		}
		else {
			if ($appAce->getEntityId()) {
				$group = $this->_em->getReference('DocovaBundle:UserRoles', $appAce->getEntityId());
			}
			else {
				$group_name = $appAce->getName() == '-Default-' ? 'ROLE_USER' : $appAce->getName();
				$group = $this->findGroup($group_name);
			}
			if (empty($group)){
				return false;
			}
			
			$appAcl = $this->_em->getRepository('DocovaBundle:AppAcl')->findBy(array('application' => $this->_app->getId(), 'groupObject' => $group->getId()));
		}
		
		if (!empty($appAcl[0])){
			return $appAcl[0];
		}
		
		$appAcl = new AppAcl();
		$appAcl->setApplication($this->_app);
		$appAcl->setCreateDocument($appAce->getCanCreate());
		$appAcl->setDeleteDocument($appAce->getCanDelete());
		if ($appAce->getType() == 'Person')
		{
			$appAcl->setUserObject($user);
		}
		else {
			$appAcl->setGroupObject($group);
		}
		
		if (strtolower($appAce->getAccess()) == 'no access' || $appAce->getAccessLevel() == 0) {
			$appAcl->setNoAccess(true);
		}
		
		$this->_em->persist($appAcl);
		$this->_em->flush();
		return $appAcl;
	}
	
	/**
	 * Generate application roles
	 * 
	 * @param \Docova\DocovaBundle\Entity\MigratedAppACE $appAce
	 * @param \Docova\DocovaBundle\Entity\AppAcl $appAcl
	 */
	private function generateAppRoles($appAce, $appAcl)
	{
		$roles = explode(',', $appAce->getRoles());
		foreach ($roles as $r)
		{
			if ($r != '[User]' && $r != '[Administrator]')
			{
				$r = str_replace(array('[', ']'), '', $r);
				$r = trim($r);
				$role = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('displayName' => $r, 'application' => $this->_app->getId()));
				if (empty($role))
				{
					$role = new UserRoles();
					$role->setApplication($this->_app);
					$role->setDisplayName($r);
					$role->setRole('ROLE_APP'.strtoupper($this->generateGuid()));
					$this->_em->persist($role);
					$this->_em->flush();
				}
				
				if ($appAce->getType() == 'Person')
				{
					$role->removeRoleUsers($appAcl->getUserObject());
					$this->_em->flush();
					$role->addRoleUsers($appAcl->getUserObject());
					$this->_em->flush();
				}
			}
		}
	}
	
	/**
	 * Generate back-end ACL entries in ACL tables
	 * 
	 * @param \Docova\DocovaBundle\Entity\MigratedAppACE $appAce
	 * @param \Docova\DocovaBundle\Entity\AppAcl $appAcl
	 */
	private function generateDocovaAcl($appAce, $appAcl)
	{
		$customAcl = new CustomACL($this->getContainer());
		try {
			if (!$customAcl->isRoleGranted($this->_app, 'ROLE_ADMIN', 'owner')) {
				$customAcl->insertObjectAce($this->_app, 'ROLE_ADMIN', 'OWNER');
			}
		}
		catch (\Exception $e) {
			$customAcl->insertObjectAce($this->_app, 'ROLE_ADMIN', 'OWNER');
		}
		
		$mask = $this->getAccessLevel($appAce->getAccess());
		if ($appAce->getType() == 'Person')
		{
			$user = $appAcl->getUserObject();
			if ($mask !== false && !$customAcl->isUserGranted($this->_app, $user, $mask)) {
				$customAcl->insertObjectAce($this->_app, $user, $mask, false);
			}
			elseif ($mask === false) {
				$customAcl->removeUserACE($this->_app, $user);
			}
		}
		else {
			$group = $appAcl->getGroupObject();
			if ($mask !== false && !$customAcl->isRoleGranted($this->_app, $group->getRole(), $mask))
			{
				$customAcl->insertObjectAce($this->_app, $group->getRole(), $mask);
			}
			elseif ($mask === false) {
				$customAcl->removeUserACE($this->_app, $group->getRole(), null, true);
			}
		}
	}
	
	/**
	 * Get valid ACL level name
	 * 
	 * @param string $level
	 * @return string|boolean
	 */
	private function getAccessLevel($level)
	{
		$level = strtolower($level);
		switch ($level)
		{
			case 'manager':
				return 'OWNER';
				break;
			case 'designer':
				return 'MASTER';
				break;
			case 'editor':
				return 'OPERATOR';
				break;
			case 'author':
				return 'EDIT';
				break;
			case 'reader':
				return 'VIEW';
				breka;
			default:
				return false;
		}
	}
	
	/**
	 * Find user account in DB
	 * 
	 * @param string $username
	 * @return \Docova\DocovaBundle\Entity\UserAccounts|boolean
	 */
	private function findUser($username)
	{
		GLOBAL $docova;
		
	    $docova_user_name = $docova->DocovaName($username, $docova);
	    $abname = $docova_user_name->Abbreviated;
	    $cnname = $docova_user_name->Canonical;
	    
		$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $abname, 'Trash' => false));
		if (empty($user))
		{
			$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDn' => $cnname, 'Trash' => false));
			if (empty($user))
			{
				$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $abname, 'Trash' => false));
				if(empty($user))
				{
					$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $abname, 'Trash' => true));
					if (empty($user))
					{
						$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDn' => $cnname, 'Trash' => true));
						if (empty($user))
						{
							$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $abname, 'Trash' => true));
						}
					}					
				}
			}
		}
		
		if (!empty($user)){
			return $user;
		}
		
		return false;
	}
	
	/**
	 * Find group/role in DB
	 * 
	 * @param string $groupname
	 * @return \Docova\DocovaBundle\Entity\UserRoles|boolean
	 */
	private function findGroup($groupname)
	{
		$group = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Role' => $groupname));
		if (empty($group)) 
		{
			$group = $this->_em->getRepository('DocovaBundle:UserRoles')->findOneBy(array('Group_Name' => $groupname));
		}
		
		if (!empty($group) && $group->getGroupType() === null) 
			return $group;
		
		return false;
	}
	
	/**
	 * Generate a guid/uuid string
	 * 
	 * @return string
	 */
	private function generateGuid()
	{
		if (function_exists('com_create_guid')) {
			return com_create_guid();
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
			return $uuid;			
		}
	}
}