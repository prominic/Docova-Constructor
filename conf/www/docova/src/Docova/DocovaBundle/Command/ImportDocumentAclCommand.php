<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Security\User\CustomACL;

/**
 * Command to import application documents ACL base on imported data
 * @author javad_rahimi
 */
class ImportDocumentAclCommand extends ContainerAwareCommand 
{
	private $_em;
	private $_acl;
	private $document;
	
	public function configure()
	{
		$this
			->setName('docova:importdocumentacl')
			->setDescription('Docova import document ACLs in an app.')
			->addArgument('docid', InputArgument::REQUIRED, 'Document ID');
	}
	
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$docid = $input->getArgument('docid');
//		$output->setVerbosity(1);
		$this->_em = $this->getContainer()->get('doctrine')->getManager();
		$this->document = $this->_em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $docid, 'Trash' => false, 'Archived' => false));
		if (empty($this->document)) {
			$output->writeln('Failed to find document with ID '. $docid);
		}
		
		try {
			$docReaders = $this->fetchDocReaders();
			$docAuthors = $this->fetchDocAuthors();
			$this->_acl = new CustomACL($this->getContainer());
			try {
				if (!$this->_acl->isRoleGranted($this->document, 'ROLE_USER', 'view')) {
					$this->_acl->insertObjectAce($this->document, 'ROLE_USER', 'view');
				}
			}
			catch (\Exception $e) {
				$this->_acl->insertObjectAce($this->document, 'ROLE_USER', 'view');
			}
			
			if (!empty($docReaders['persons'])) {
				$this->addDocReaderAcl($docReaders['persons'], true);
			}
			if (!empty($docReaders['groups'])) {
				$this->addDocReaderAcl($docReaders['groups'], false);
			}
			if (empty($docReaders['persons']) && empty($docReaders['groups'])) {
				$this->addDocReaderAcl(array(array('Role' => 'ROLE_USER')), false);
				//$this->addDocAuthorAcl(array(array('Role' =>'ROLE_USER')), false);
			}
			
			if (!empty($docAuthors['persons'])) {
				$this->addDocAuthorAcl($docAuthors['persons'], true);
			}
			if (!empty($docAuthors['groups'])) {
				$this->addDocAuthorAcl($docAuthors['groups'], false);
			}
			$result = array('Status' => 'OK');
		}
		catch (\Exception $e) {
			$result = array('Status' => 'FAILED', 'ErrMsg' => $e->getMessage().' on line '.$e->getLine());
		}
		
		$output->writeln(json_encode($result));
	}
	
	/**
	 * Fetch all document readers
	 * 
	 * @return array|boolean
	 */
	private function fetchDocReaders() 
	{
		$docReaders = array();
		$readers = $this->_em->getRepository('DocovaBundle:FormNameValues')->getDocNames($this->document->getId(), 'reader');
		if (!empty($readers[0]))
		{
			$docReaders['persons'] = $readers;
		}
		$readers = $this->_em->getRepository('DocovaBundle:FormGroupValues')->getDocNames($this->document->getId(), 'reader');
		if (!empty($readers[0]))
		{
			$docReaders['groups'] = $readers;
		}
		
		if (!empty($docReaders['persons']) || !empty($docReaders['groups']))
		{
			return $docReaders;
		}
		
		return false;
	}
	
	/**
	 * Fetch all document authors
	 * 
	 * @return array|boolean
	 */
	private function fetchDocAuthors() 
	{
		$docAuthors = array();
		$authors = $this->_em->getRepository('DocovaBundle:FormNameValues')->getDocNames($this->document->getId(), 'author');
		if (!empty($authors[0]))
		{
			$docAuthors['persons'] = $authors;
		}
		$authors = $this->_em->getRepository('DocovaBundle:FormGroupValues')->getDocNames($this->document->getId(), 'author');
		if (!empty($authors[0]))
		{
			$docAuthors['groups'] = $authors;
		}
		
		if (!empty($docAuthors['persons']) || !empty($docAuthors['groups']))
		{
			return $docAuthors;
		}
		
		return false;
	}
	
	/**
	 * Add DocReaders ACL for the document
	 * 
	 * @param array $ace_records
	 * @param boolean $is_user
	 */
	private function addDocReaderAcl($ace_records, $is_user)
	{
		$masks = array();
		if ($this->_acl->isRoleGranted($this->document, 'ROLE_USER', 'view'))
		{
			$masks[] = 'view';
		}
		if ($this->_acl->isRoleGranted($this->document, 'ROLE_USER', 'edit'))
		{
			$masks[] = 'edit';
		}
		if (!empty($masks))
		{
			$this->_acl->removeUserACE($this->document, 'ROLE_USER', $masks, true);
		}
		
		foreach ($ace_records as $ace)
		{
			if ($is_user === true) {
				$ace = $this->_em->getReference('DocovaBundle:UserAccounts', $ace);
			}
			if ($is_user === true && $ace instanceof \Docova\DocovaBundle\Entity\UserAccounts && !$this->_acl->isUserGranted($this->document, $ace, 'view')) {
				$this->_acl->insertObjectAce($this->document, $ace, 'view', false);
			}
			elseif ($is_user === false && !empty($ace['Role']) && !$this->_acl->isRoleGranted($this->document, $ace['Role'], 'view')) {
				$this->_acl->insertObjectAce($this->document, $ace['Role'], 'view');
			}
		}
	}
	
	/**
	 * Add DocAuthors ACL for the document
	 * 
	 * @param array $ace_records
	 * @param boolean $is_user
	 */
	private function addDocAuthorAcl($ace_records, $is_user)
	{
		foreach ($ace_records as $ace)
		{
			if ($is_user === true) {
				$ace = $this->_em->getReference('DocovaBundle:UserAccounts', $ace);
			}
			if ($is_user === true && $ace instanceof \Docova\DocovaBundle\Entity\UserAccounts && !$this->_acl->isUserGranted($this->document, $ace, 'edit')) {
				$this->_acl->insertObjectAce($this->document, $ace, 'edit', false);
			}
			elseif ($is_user === false && !empty($ace['Role']) && !$this->_acl->isRoleGranted($this->document, $ace['Role'], 'edit')) {
				$this->_acl->insertObjectAce($this->document, $ace['Role'], 'edit');
			}
		}
	}
}