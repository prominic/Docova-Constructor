<?php
namespace Docova\DocovaBundle\Security\User;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

class CustomACL
{
	protected $container;
	
	public function __construct($container)
	{
		$this->container = $container;
	}


	/**
	 * Remove all ACEs of an Object related to the user or role
	 *        remove ACEs which contain the mask if $mask is not null
	 *
	 * @param object $object
	 * @param mixed $user_object
	 * @param array $mask
	 * @return boolean
	 */
	public function removeUserACE($object, $user_object, $mask = null, $role_base = false)
	{
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}
		
		if ($role_base === true) {
			if (!$user_object instanceof RoleSecurityIdentity) {
				$user_object = new RoleSecurityIdentity($user_object);
			}
		}
		else {
			if (!$user_object instanceof UserSecurityIdentity) {
				$user_object = UserSecurityIdentity::fromAccount($user_object);
			}
		}
		
		if (!empty($mask) && !is_array($mask)) {
			$mask = array($mask);
		}

		$acl_provider = $this->container->get('security.acl.provider');
		$deleted = false;

		try {
			$this->resetAceOrder($object);
			$acl = $acl_provider->findAcl($object);
			$aces = $acl->getObjectAces();
			
			if (!empty($aces) && is_array($aces)) 
			{
				$len = max(array_keys($aces));
				for ($index = $len; $index >= 0; $index--) 
				{
					if (!empty($aces[$index])) 
					{			
						$ace_security_identity = $aces[$index]->getSecurityIdentity();
						if ($ace_security_identity->equals($user_object)) 
						{
							if (!empty($mask)) {
								for ($x = 0; $x < count($mask); $x++) {
									if ($aces[$index]->getMask() === $this->convertToCode($mask[$x])) {
										$acl->deleteObjectAce($index);
										//$aces = $acl->getObjectAces();
										//$index = -1;
										//reset($aces);
										$deleted = true;
									}
								}
							}
							else {
								$acl->deleteObjectAce($index);
								$deleted = true;
							}
						}
					}
				}
			}
			
			if ($deleted === true) {
				$acl_provider->updateAcl($acl);
			}
			return $deleted;
		}
		catch (\Exception $e) {
			//var_dump($e->getMessage());
			//@TODO: Create a log for not found ACL then return false
			return false;
		}
	}
	

	/**
	 * Remove all ACEs of the Object which are equal to the Mask
	 * 
	 * @param object $object
	 * @param string $mask
	 * @return void|boolean
	 */
	public function removeMaskACEs($object, $mask)
	{
		if (empty($object) || empty($mask)) {
			return false;
		}
		
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}

		$acl_provider = $this->container->get('security.acl.provider');
		$deleted = false;

		try {
			$this->resetAceOrder($object);
			$acl = $acl_provider->findAcl($object);
			$aces = $acl->getObjectAces();

			if (!empty($aces) && is_array($aces)) 
			{
				$len = max(array_keys($aces));
				for ($x = $len; $x >= 0; $x--) {
	
					if (!empty($aces[$x])) {
	
						if ($aces[$x]->getMask() === $this->convertToCode($mask)) {
							$acl->deleteObjectAce($x);
							//$aces = $acl->getObjectAces();
							//$index = -1;
							//reset($aces);
							$deleted = true;
						}
					}
				}
			}
			
			if ($deleted === true) {
				$acl_provider->updateAcl($acl);
			}
			
			return $deleted;
		}
		catch (\Exception $e) {
			//@TODO: Create a log for not found ACL then return false
			return false;
		}
	}
	
	/**
	 * Remove all generated ACE for the object
	 * 
	 * @param object $object
	 * @return boolean
	 */
	public function removeAllMasks($object)
	{
		if (empty($object)) {
			return false;
		}
		
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}
		
		$acl_provider = $this->container->get('security.acl.provider');
		$deleted = false;
		
		try {
			$this->resetAceOrder($object);
			$acl = $acl_provider->findAcl($object);
			$aces = $acl->getObjectAces();
		
			if (!empty($aces) && is_array($aces)) 
			{
				foreach ($aces as $index => $aces) {
					$acl->deleteObjectAce($index);
					//$aces = $acl->getObjectAces();
					//$index = -1;
					//reset($aces);
					$deleted = true;
				}
			}
				
			if ($deleted === true) {
				$acl_provider->updateAcl($acl);
			}
				
			return $deleted;
		}
		catch (\Exception $e) {
			return false;
		}
	}


	/**
	 * Insert ACE for an Object for a user or a role
	 *  
	 * @param mixed $object
	 * @param mixed $security_identity
	 * @param array $masks
	 * @param boolean $role_base
	 * @return boolean
	 */
	public function insertObjectAce($object, $security_identity, $masks, $role_base = true)
	{
		if (empty($object) || empty($security_identity) || empty($masks)) {
			return false;
		}

		if (!($object instanceof ObjectIdentity)) {
			$object = ObjectIdentity::fromDomainObject($object);
		}
		
		if ($role_base === true) {
			if (!$security_identity instanceof RoleSecurityIdentity) {
				$security_identity = new RoleSecurityIdentity($security_identity);
			}
		}
		else {
			if (!$security_identity instanceof UserSecurityIdentity) {
				$security_identity = UserSecurityIdentity::fromAccount($security_identity);
			}
		}

		if (!is_array($masks)) {
			$masks = array($masks);
		}
		
		$acl_provider = $this->container->get('security.acl.provider');
		try {
			$acl = $acl_provider->findAcl($object);
			$this->resetAceOrder($object);
		}
		catch (\Exception $e) {
			$acl = $acl_provider->createAcl($object);
		}

		if (empty($acl)) {
			return false;
		}
		
		for ($x = 0; $x < count($masks); $x++) {
			$mask_code = $this->convertToCode($masks[$x]);
			$acl->insertObjectAce($security_identity, $mask_code);
			$acl_provider->updateAcl($acl);
		}
		
		//$acl_provider->updateAcl($acl);
		return true;
	}
	

	/**
	 * Update an object ACE of the user to new MASK
	 * 
	 * @param mixed $object
	 * @param mixed $security_identity
	 * @param string $from_mask
	 * @param string $to_mask
	 * @return boolean
	 */
	public function updateUserACE($object, $security_identity, $from_mask, $to_mask)
	{
		if (empty($object) || empty($security_identity) || empty($from_mask) || empty($to_mask)) {
			return false;
		}
		
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}

		if (!$security_identity instanceof UserSecurityIdentity) {
			$security_identity = UserSecurityIdentity::fromAccount($security_identity);
		}

		$from_mask = $this->convertToCode($from_mask);
		if (empty($from_mask)) {
			return false;
		}

		$to_mask = $this->convertToCode($to_mask);
		if (empty($to_mask)) {
			return false;
		}
		
		$acl_provider = $this->container->get('security.acl.provider');
		try {
			$acl = $acl_provider->findAcl($object);
			
			foreach ($acl->getObjectAces() as $index => $ace) {
			
				$ace_security_identity = $ace->getSecurityIdentity();
			
				if ($ace_security_identity instanceof UserSecurityIdentity) {
					if ($ace->getMask() === $from_mask) {
						$acl->updateObjectAce($index, $to_mask);
					}
				}
			}

			$acl_provider->updateAcl($acl);
			return true;
		}
		catch (\Exception $e) {
			return false;
		}
	}
	

	/**
	 * Returns array of all UserSecurities granted to the object if $mask is empty.
	 *        Returns UserSecurities granted to the object according to specific Mask if $mask is not empty
	 *
	 * @param object $object
	 * @param string $mask
	 * @return ArrayCollection
	 */
	public function getObjectACEUsers($object, $mask = null)
	{
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}
		
		$acl_provider = $this->container->get('security.acl.provider');
		$output = new ArrayCollection();

		try {
			$acl = $acl_provider->findAcl($object);
			
			foreach ($acl->getObjectAces() as $ace) {

				$ace_security_identity = $ace->getSecurityIdentity();
				
				if ($ace_security_identity instanceof UserSecurityIdentity) {
					if (!empty($mask)) {
						if ($ace->getMask() === $this->convertToCode($mask)) {
							$output[] = $ace_security_identity;
						}
					}
					else {
						$output[] = $ace_security_identity;
					}
				}
			}
		}
		catch(\Exception $e) {
			unset($output);
			$output = new ArrayCollection();
			return $output;
		}

		return $output;
	}
	
	/**
	 * Return collection of groups(roles) granted all masks or particular mask to the object
	 * 
	 * @param object $object
	 * @param string $mask
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getObjectACEGroups($object, $mask = null)
	{
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}
		
		$acl_provider = $this->container->get('security.acl.provider');
		$output = new ArrayCollection();
		
		try {
			$user_role = new RoleSecurityIdentity('ROLE_USER');
			$acl = $acl_provider->findAcl($object);
			foreach ($acl->getObjectAces() as $ace) 
			{
				$ace_security_identity = $ace->getSecurityIdentity();
				
				if ($ace_security_identity instanceof RoleSecurityIdentity && !$ace->getSecurityIdentity()->equals($user_role)) {
					if (!empty($mask)) {
						if ($ace->getMask() === $this->convertToCode($mask)) {
							$output[] = $ace_security_identity;
						}
					}
					else {
						$output[] = $ace_security_identity;
					}
				}
			}
		}
		catch(\Exception $e) {
			unset($output);
			$output = new ArrayCollection();
			return $output;
		}

		return $output;
	}

	/**
	 * Check if the user has access to the Object in ACL according to the mask
	 * 
	 * @param object $object
	 * @param object $user_security_id
	 * @param string $mask
	 * @return boolean
	 */
	public function isUserGranted($object, $user_security_id, $mask)
	{
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}
		
		if(!$user_security_id instanceof UserSecurityIdentity) {
			$user_security_id = UserSecurityIdentity::fromAccount($user_security_id);
		}
		
		$acl_provider = $this->container->get('security.acl.provider');
		$acl = $acl_provider->findAcl($object);
		$aces = $acl->getObjectAces();
		
		if (!empty($aces) && is_array($aces))
		{
			$len = max(array_keys($aces));
			for ($x = $len; $x >= 0; $x--) 
			{
				if (!empty($aces[$x])) 
				{
					if ($aces[$x]->getSecurityIdentity()->equals($user_security_id)) {
						if ($aces[$x]->getMask() === $this->convertToCode($mask)) {
							return true;
						}
					}
				}
			}
		}
		
		return false;
	}
	
	
	/**
	 * Check if the role has access to the Object in ACL according to the mask
	 * 
	 * @param object $object
	 * @param object|string $role_security_id
	 * @param string $mask
	 * @return boolean
	 */
	public function isRoleGranted($object, $role_security_id, $mask)
	{
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}
		
		if(!$role_security_id instanceof RoleSecurityIdentity) {
			$role_security_id = new RoleSecurityIdentity($role_security_id);
		}
		
		$acl_provider = $this->container->get('security.acl.provider');
		$acl = $acl_provider->findAcl($object);
		
		foreach ($acl->getObjectAces() as $ace) {
			if ($ace->getSecurityIdentity()->equals($role_security_id)) {
				if ($ace->getMask() === $this->convertToCode($mask)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Get all masks set for a user
	 * 
	 * @param object $object
	 * @param \Docova\DocovaBundle\Entity\UserAccounts|UserSecurityIdentity $user
	 * @return string[]
	 */
	public function getUserMasks($object, $user)
	{
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}
		
		if(!$user instanceof UserSecurityIdentity) {
			$user = UserSecurityIdentity::fromAccount($user);
		}
		
		$output = array();
		$acl_provider = $this->container->get('security.acl.provider');
		try {
			$acl = $acl_provider->findAcl($object);
			$aces = $acl->getObjectAces();
		}
		catch(\Exception $e) {
			return $output;
		}
		
		if (!empty($aces) && is_array($aces))
		{
			$len = max(array_keys($aces));
			for ($x = $len; $x >= 0; $x--) 
			{
				if (!empty($aces[$x])) 
				{
					if ($aces[$x]->getSecurityIdentity()->equals($user)) {
						$output[] = $this->convertToMask($aces[$x]->getMask());
					}
				}
			}
		}
		
		return $output;
	}
	
	/**
	 * Get all masks set for a group
	 * 
	 * @param object $object
	 * @param RoleSecurityIdentity|string $group
	 * @return string[]
	 */
	public function getGroupMasks($object, $group)
	{
		if (!$object instanceof ObjectIdentity) {
			$object = ObjectIdentity::fromDomainObject($object);
		}
		
		if (!$group instanceof RoleSecurityIdentity) {
			$group = new RoleSecurityIdentity($group);
		}
		
		$output = array();
		$acl_provider = $this->container->get('security.acl.provider');
		try {
			$acl = $acl_provider->findAcl($object);
			$aces = $acl->getObjectAces();
		}
		catch(\Exception $e) {
			return $output;
		}
		
		if (!empty($aces) && is_array($aces))
		{
			$len = max(array_keys($aces));
			for ($x = $len; $x >= 0; $x--) 
			{
				if (!empty($aces[$x])) 
				{
					if ($aces[$x]->getSecurityIdentity()->equals($group)) {
						$output[] = $this->convertToMask($aces[$x]->getMask());
					}
				}
			}
		}
		
		return $output;
	}
	
	/**
	 * Copy all ACL entries from source object to target object
	 * 
	 * @param object $source
	 * @param object $target
	 * @param array $exceptions (optional, excepted masks we don't want to apply)
	 * @param array $masks (optional, masks we want to apply)
	 * @return void
	 */
	public function copyObjectAceEntries($source, $target, $exceptions = [], $masks = [])
	{
		if (!$source instanceof ObjectIdentity) {
			$source = ObjectIdentity::fromDomainObject($source);
		}
		
		if (!$target instanceof ObjectIdentity) {
			$target = ObjectIdentity::fromDomainObject($target);
		}
		
		if (!empty($masks)) {
			$masks = array_map('strtolower', $masks);
		}

		if (!empty($exceptions)) {
			$exceptions = array_map('strtolower', $exceptions);
			if (!empty($masks)) {
				$masks = array_diff($masks, $exceptions);
			}
		}
		
		$acl_provider = $this->container->get('security.acl.provider');
		$source_acl = $acl_provider->findAcl($source);
		$aces = $source_acl->getObjectAces();
		
		if (!empty($aces) && is_array($aces))
		{
			$len = max(array_keys($aces));
			for ($x = $len; $x >= 0; $x--)
			{
				if (!empty($aces[$x]))
				{
					$security_identity = $aces[$x]->getSecurityIdentity();
					$is_role = $security_identity instanceof RoleSecurityIdentity ? true : false;
					$source_mask = $this->convertToMask($aces[$x]->getMask());
					if ($is_role === true && !$this->isRoleGranted($target, $security_identity, $source_mask))
					{
						if (!empty($masks) && in_array($source_mask, $masks))
						{
							$this->insertObjectAce($target, $security_identity, $source_mask);
						}
						elseif (empty($masks) && !empty($exceptions) && !in_array($source_mask, $exceptions))
						{
							$this->insertObjectAce($target, $security_identity, $source_mask);
						}
						elseif (empty($masks) && empty($exceptions))
						{
							$this->insertObjectAce($target, $security_identity, $source_mask);
						}
					}
					elseif ($is_role !== true && !$this->isUserGranted($target, $security_identity, $source_mask))
					{
						if (!empty($masks) && in_array($source_mask, $masks))
						{
							$this->insertObjectAce($target, $security_identity, $source_mask, false);
						}
						elseif (empty($masks) && !empty($exceptions) && !in_array($source_mask, $exceptions))
						{
							$this->insertObjectAce($target, $security_identity, $source_mask, false);
						}
						elseif (empty($masks) && empty($exceptions))
						{
							$this->insertObjectAce($target, $security_identity, $source_mask, false);
						}
					}
				}
			}
		}
	}


	/**
	 * Returns the code of the mask
	 * 
	 *   returns false if mask is invalid
	 * 
	 * @param string $mask
	 * @return integer
	 */
	private function convertToCode($mask)
	{
		$mask_code = 0;
		
		switch (strtolower($mask)) {
			case 'create':
				$mask_code = MaskBuilder::MASK_CREATE;
				break;
			case 'delete':
				$mask_code = MaskBuilder::MASK_DELETE;
				break;
			case 'edit':
				$mask_code = MaskBuilder::MASK_EDIT;
				break;
			case 'master':
				$mask_code = MaskBuilder::MASK_MASTER;
				break;
			case 'operator':
				$mask_code = MaskBuilder::MASK_OPERATOR;
				break;
			case 'owner':
				$mask_code = MaskBuilder::MASK_OWNER;
				break;
			case 'undelete':
				$mask_code = MaskBuilder::MASK_UNDELETE;
				break;
			case 'view':
				$mask_code = MaskBuilder::MASK_VIEW;
				break;
			default:
				return false;
		}
		return $mask_code;
	}
	
	/**
	 * Convert a mask code to mask string
	 * 
	 * @param integer $code
	 * @return string
	 */
	private function convertToMask($code)
	{
		$mask = '';
		switch ($code)
		{
			case MaskBuilder::MASK_CREATE:
				$mask = 'create';
				break;
			case MaskBuilder::MASK_DELETE:
				$mask = 'delete';
				break;
			case MaskBuilder::MASK_EDIT:
				$mask = 'edit';
				break;
			case MaskBuilder::MASK_MASTER:
				$mask = 'master';
				break;
			case MaskBuilder::MASK_OPERATOR:
				$mask = 'operator';
				break;
			case MaskBuilder::MASK_OWNER:
				$mask = 'owner';
				break;
			case MaskBuilder::MASK_UNDELETE:
				$mask = 'undelete';
				break;
			case MaskBuilder::MASK_VIEW:
				$mask = 'view';
				break;
		}
		return $mask;
	}
	
	/**
	 * Reset the ace_order for the selected object identity
	 * 
	 * @param object $object
	 */
	private function resetAceOrder($object)
	{
		$conn = $this->container->get('doctrine')->getConnection();
		$driver = $conn->getDriver()->getName();
		$conn->beginTransaction();
		try {
			if ($driver == 'pdo_mysql') 
			{
				$conn->query('SET @ordering = -1;');
				$query = "UPDATE `acl_entries` SET `ace_order` = (@ordering := @ordering + 1) WHERE `object_identity_id` = (SELECT `id` FROM `acl_object_identities` WHERE `object_identifier` = ?) ORDER BY `ace_order` ASC";
				$stmt = $conn->prepare($query);
				$stmt->bindValue(1, $object->getIdentifier());
				$stmt->execute();
			}
			elseif ($driver == 'pdo_sqlsrv')
			{
				$query = "UPDATE [ocl] SET [ace_order]=[Row] FROM (SELECT ROW_NUMBER() OVER(ORDER BY [ace_order] ASC) - 1 AS [Row], [ace_order] FROM [acl_entries] WHERE [object_identity_id] = (SELECT [id] FROM [acl_object_identities] WHERE [object_identifier] = ?)) AS [ocl]";
				$stmt = $conn->prepare($query);
				$stmt->bindValue(1, $object->getIdentifier());
				$stmt->execute();
			}
			$conn->commit();
		}
		catch (\Exception $e) {
			//var_dump($e->getMessage());
			$conn->rollback();
		}
	}
}