<?php

namespace Docova\DocovaBundle\ObjectModel;


/**
 * Back-end class to handle ACL entries
 * @author javad_rahimi
 */
class DocovaAclEntry 
{
	private $_em;
	private $acl_entry;
	private $remove = false;

	/**
	 * Can create documents
	 * @var boolean
	 */
	public $canCreateDocuments = false;

	/**
	 * Can delete documents
	 * @var boolean
	 */
	public $canDeleteDocuments = false;

	/**
	 * Is current entry group
	 * @var boolean
	 */
	public $isGroup = false;

	/**
	 * Is current entry person
	 * @var string
	 */
	public $isPerson = false;

	/**
	 * Current entry level
	 * @var integer
	 */
	public $level = 0;

	/**
	 * The name of entry
	 * @var string
	 */
	public $name;

	/**
	 * Is new entry
	 * @var boolean
	 */
	public $new = false;

	/**
	 * Arrays of roles of entry
	 * @var array
	 */
	public $roles = array();

	/**
	 * User type of entry
	 * @var integer
	 */
	public $userType = 1;
	
	public function __construct(DocovaAcl $acl, $name, $level = null)
	{
		global $docova;
		if (!empty($docova) && $docova instanceof Docova) {
			$this->_em = $docova->getManager();
		}
		else {
			throw new \Exception('Oops! DocovaAclEntry construction failed. Entity Manager not available.');
		}
		
		$user = null;
		if (!empty($name))
		{
			$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $name, 'Trash' => false));
			if (empty($user))
				$user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $name, 'Trash' => false));
		}
		
		if (!empty($user))
		{
			$acl_entry = $this->_em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $acl->getAppId(), 'userObject' => $user->getId()));
			if (!empty($acl_entry))
			{
				$this->new = false;
				$this->canCreateDocuments = $acl_entry->getCanCreateDocument();
				$this->canDeleteDocuments = $acl_entry->getCanDeleteDocument();
				if ($acl_entry->getGroupObject())
				{
					$this->userType = 2;
					$this->isGroup = true;
					$this->isPerson = false;
					$this->name = $acl_entry->getGroupObject()->getDisplayName();
				}
				else {
					$this->userType = 1;
					$this->isGroup = false;
					$this->isPerson = true;
					$this->name = $acl_entry->getUserObject()->getUserNameDnAbbrivated();
				}
				if ($acl->isManager())
					$this->level = 6;
				elseif ($acl->isDesigner())
					$this->level = 5;
				elseif ($acl->isEditor())
					$this->level = 4;
				elseif ($acl->isAuthor())
					$this->level = 3;
				elseif ($acl->isReader())
					$this->level = 2;
				else 
					$this->level = 0;
				
				$roles = $this->_em->getRepository('DocovaBundle:UserRoles')->getAppRoles($acl->getAppId(), $name);
				if (!empty($roles))
				{
					foreach ($roles as $r)
					{
						$this->roles[] = $r['displayName'];
					}
				}
			}
			else 
				throw new \Exception('Oops! DocovaAclEntry failed on construction due to unspecified entry.');
		}
		else {
			$this->new = true;
			$this->name = $name;
			$this->level = $level;
		}
	}
	
	/**
	 * Removes an entry from an access control list.
	 */
	public function remove()
	{
		$this->remove = true;
	}
}