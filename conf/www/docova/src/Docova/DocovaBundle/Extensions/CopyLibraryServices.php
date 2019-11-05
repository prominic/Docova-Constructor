<?php

namespace Docova\DocovaBundle\Extensions;

use Doctrine\ORM\EntityManager;
use Docova\DocovaBundle\Entity\Libraries;
use Docova\DocovaBundle\Entity\UserAccounts;

/**
 * Class to handle copy library services
 * @author javad_rahimi
 */
class CopyLibraryServices
{
	private $_em;
	private $_source;
	private $_target;
	private $_user;
	
	use CopyServices;
	
	public function __construct(EntityManager $em, Libraries $source, UserAccounts $user)
	{
		$this->_em = $em;
		$this->_source = $source;
		$this->_user = $user;
	}
	
	public function copyDocTypes()
	{
		if ($this->_source->getApplicableDocType()->count())
		{
			foreach ($this->_source->getApplicableDocType() as $doctype)
			{
				$this->_target->addApplicableDocType($doctype);
			}
			$this->_em->flush();
		}
	}
	
	public function copyFolders()
	{
		//$folders = $this->_em->getRepository('DocovaBundle:Folders')->
		//@note: implementation of this process is like a full migration project (recursive function, start from root, create folders and documents, go to subfolders and repeat)
		// it also requires attachments migration
	}
}