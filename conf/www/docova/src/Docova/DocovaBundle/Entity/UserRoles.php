<?php

namespace Docova\DocovaBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Role\Role;

/**
 * UserRoles
 *
 * @ORM\Table(name="tb_user_roles")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\UserRolesRepository")
 */
class UserRoles extends Role
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Group_Name", type="string", length=255, nullable=true)
     */
    protected $Group_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Role_Name", type="string", length=255, unique=true)
     */
    protected $Role;

    /**
     * @var string
     * 
     * @ORM\Column(name="Display_Name", type="string", length=255)
     */
    protected $displayName;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Group_Type", type="boolean", nullable=true)
     */
    protected $groupType;

    /**
     * @var string
     * 
     * @ORM\Column(name="Nested_Groups", type="text", nullable=true)
     */
    protected $Nested_Groups;

    /**
     * @var string
     * 
     * @ORM\Column(name="AD_Key", type="string", length=100, nullable=true)
     */
    protected $adKey;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="Application_Id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $application;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="UserAccounts", mappedBy="User_Roles", cascade={"persist"})
     * @ORM\JoinTable(name="tb_useraccounts_userroles")
     */
    protected $Role_Users;
    
    
    public function __construct()
    {
    	$this->Role_Users = new ArrayCollection();
    }


    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Group_Name
     *
     * @param string $groupName
     * @return UserRoles
     */
    public function setGroupName($groupName)
    {
        $this->Group_Name = $groupName;
    
        return $this;
    }

    /**
     * Get Group_Name
     *
     * @return string 
     */
    public function getGroupName()
    {
        return $this->Group_Name;
    }

    /**
     * Set Role
     *
     * @param string $roleName
     * @return UserRoles
     */
    public function setRole($roleName)
    {
        $this->Role = $roleName;
    
        return $this;
    }

    /**
     * Get Role
     *
     * @return string 
     */
    public function getRole()
    {
        return $this->Role;
    }

    /**
     * Set displayName
     * 
     * @param string $displayName
     * @return UserRoles
     */
    public function setDisplayName($displayName)
    {
    	$this->displayName = $displayName;
    	
    	return $this;
    }

    /**
     * Get displayName
     * 
     * @return string
     */
    public function getDisplayName()
    {
    	return $this->displayName;
    }

    /**
     * Add Role_Users
     * 
     * @param: \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function addRoleUsers(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$user->addRoles($this);
    	$this->Role_Users[] = $user;
    }
    
    /**
     * Get Role_Users
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getRoleUsers()
    {
    	return $this->Role_Users;
    }
    
    /**
     * Remove Role_Users
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function removeRoleUsers(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$user->removeUserRoles($this);
    	$this->Role_Users->removeElement($user);
    }

    /**
     * Set groupType
     * 
     * @param boolean $groupType
     * @return UserRoles
     */
    public function setGroupType($groupType)
    {
    	$this->groupType = $groupType;
    	
    	return $this;
    }

    /**
     * Get groupType
     * 
     * @return boolean
     */
    public function getGroupType()
    {
    	return $this->groupType;
    }
    
    /**
     * Set Nested_Groups
     * 
     * @param array|string $nestedGroups
     * @return UserRoles
     */
    public function setNestedGroups($nestedGroups = null)
    {
    	if (!empty($nestedGroups)) 
    	{
    		if (is_array($nestedGroups)) 
    		{
    			$this->Nested_Groups = implode(',', $nestedGroups);
    		}
    		else {
    			$this->Nested_Groups = $nestedGroups;
    		}
    	}
    	else {
    		$this->Nested_Groups = null;
    	}
    	
    	return $this;
    }
    
    /**
     * Get Nested_Groups
     * 
     * @return array|null
     */
    public function getNestedGroups()
    {
    	if (!empty($this->Nested_Groups)) 
    	{
	    	return explode(',', $this->Nested_Groups);
    	}
    	return null;
    }
    
    /**
     * Set adKey
     * 
     * @param string $adKey
     * @return UserRoles
     */
    public function setAdKey($adKey = null)
    {
    	$this->adKey = $adKey;
    	
    	return $this;
    }
    
    /**
     * Get adKey
     * 
     * @return string
     */
    public function getAdKey()
    {
    	return $this->adKey;
    }
   
    /**
     * Set application
     * 
     * @param \Docova\DocovaBundle\Entity\Libraries $app
     * @return UserRoles
     */
    public function setApplication(\Docova\DocovaBundle\Entity\Libraries $app = null)
    {
    	$this->application = $app;
    	
    	return $this;
    }
    
    /**
     * Get application
     * 
     * @return \Docova\DocovaBundle\Entity\Libraries
     */
    public function getApplication()
    {
    	return $this->application;
    }
}
