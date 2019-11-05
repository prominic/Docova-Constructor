<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * UserAccounts
 *
 * @ORM\Table(name="tb_user_accounts", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="Unique_User_Accounts", columns={"User_Account_Name", "user_name_dn_abbreviated"})})
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\UserAccountsRepository")
 * @UniqueEntity(fields={"username", "userNameDnAbbreviated"})
 */
class UserAccounts implements AdvancedUserInterface, EquatableInterface, \Serializable
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
     * @ORM\Column(name="User_Account_Name", type="string", length=100)
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(name="User_Account_Pass", type="string", length=32, nullable=true)
     */
    protected $password = NULL;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="User_Salt", type="string", length=32)
     */
    protected $Salt;
    
    /**
     * @var string
     * 
     * @ORM\Column(name="User_Mail", type="string", length=100)
     */
    protected $User_Mail;

    /**
     * @ORM\ManyToMany(targetEntity="UserRoles", inversedBy="Role_Users", cascade={"persist"})
     * @ORM\JoinTable(name="tb_useraccounts_userroles")
     */
    protected $User_Roles;
    
    /**
     * @ORM\OneToOne(targetEntity="UserProfile", mappedBy="User")
     */
    protected $User_Profile;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean", nullable=false)
     */
    protected $Trash = false;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="user_name_dn", type="string", length=256,nullable=true)
     */
    protected $userNameDn;
    
    /**
     * @var string
     *
     * @ORM\Column(name="user_name_dn_abbreviated", type="string", length=255,nullable=true)
     */
    protected $userNameDnAbbreviated;

    /**
     * @ORM\ManyToMany(targetEntity="Libraries")
     * @ORM\JoinTable(name="tb_user_appbuilder_apps",
     * 		joinColumns={@ORM\JoinColumn(name="User_Id", referencedColumnName="id", onDelete="CASCADE")},
     * 		inverseJoinColumns={@ORM\JoinColumn(name="App_Id", referencedColumnName="id", onDelete="CASCADE")})
     */
    protected $userAppbuilderApps;
    
    public function __construct()
    {
    	$this->User_Roles = new ArrayCollection();
    	$this->userAppbuilderApps = new ArrayCollection();
    	$this->Salt = md5(uniqid(null, true));
    	$this->Trash = false;
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
     * Set Username
     *
     * @param string $userAccountName
     * @return UserAccounts
     */
    public function setUsername($userAccountName)
    {
        $this->username = $userAccountName;
    
        return $this;
    }

    /**
     * Get Username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set Password
     *
     * @param string $userAccountPass
     * @return UserAccounts
     */
    public function setPassword($userAccountPass = null)
    {
        $this->password = $userAccountPass;
    
        return $this;
    }

    /**
     * Get Password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * Set Salt
     * 
     * @param string $userSalt
     * @return UserAccounts
     */
    public function setSalt($userSalt)
    {
    	$this->Salt = $userSalt;
    	
    	return $this;
    }
    
    /**
     * Get Salt
     * 
     * @return string
     */
    public function getSalt()
    {
    	return $this->Salt;
    }
    
    /**
     * Set User_Mail
     * 
     * @param string $userMail
     * @return UserAccounts
     */
    public function setUserMail($userMail)
    {
    	$this->User_Mail = $userMail;
    	
    	return $this;
    }
    
    /**
     * Get User_Mail
     * 
     * @return string
     */
    public function getUserMail()
    {
    	return $this->User_Mail;
    }

    /**
     * Add User_Role
     *
     * @param \Docova\DocovaBundle\Entity\UserRoles $role
     */
    public function addRoles(\Docova\DocovaBundle\Entity\UserRoles $role)
    {
        $this->User_Roles[] = $role;
    }
    
    /**
     * Get User_Roles
     * 
     * @return ArrayCollection
     */
    public function getUserRoles()
    {
    	return $this->User_Roles;
    }

    /**
     * Get Roles
     *
     * @return array
     */
    public function getRoles()
    {
    	$roles = array();
    	
    	foreach ($this->User_Roles as $role) {
    		if (is_object($role)) {
	    		$roles[] = $role->getRole();
    		}
    		else {
    			return array();
    		}
    	}
        return $roles;
    }
    
    /**
     * Get Roles display name
     *
     * @return array
     */
    public function getRolesDisplay($appid = null)
    {
        $roles = array();
        
        foreach ($this->User_Roles as $role) {
			if (empty($appid) || ($role->getApplication() && $role->getApplication()->getId() == $appid)) {
				$roles[] = $role->getDisplayName();
			}
        }
        return $roles;
    }

    /**
     * Remove User_Roles
     * 
     * @param \Docova\DocovaBundle\Entity\UserRoles $role
     */
    public function removeUserRoles(\Docova\DocovaBundle\Entity\UserRoles $role)
    {
    	$this->User_Roles->removeElement($role);
    }
    
    /**
     * Check if the user belongs to a role (group)
     * 
     * @param string $rolename
     * @return boolean
     */
    public function hasRole($rolename)
    {
    	foreach ($this->getUserRoles() as $role) 
    	{
    		if ($role->getRole() === $rolename) 
    		{
    			return true;
    		}
    	}
    	return false;
    }

    /**
     * Get User_Profile
     * 
     * @return \Docova\DocovaBundle\Entity\UserProfile
     */
    public function getUserProfile()
    {
    	return $this->User_Profile;
    }

    /**
     * Set Trash
     *
     * @param boolean $trash
     * @return UserAccounts
     */
    public function setTrash($trash)
    {
        $this->Trash = $trash;
    
        return $this;
    }

    /**
     * Get Trash
     *
     * @return boolean 
     */
    public function getTrash()
    {
        return $this->Trash;
    }
    
    public function eraseCredentials() 
    {
    }

    public function isEqualTo(UserInterface $user)
    {
    	if (!$user instanceof UserAccounts) {
    		return false;
    	}

    	if ($this->password !== md5(md5(md5($user->getPassword())))) {
    		return false;
    	}
    
    	if ($this->getSalt() !== $user->getSalt()) {
    		return false;
    	}
    
    	if ($this->username !== $user->getUsername()) {
    		return false;
    	}
    	
    	if ($user->getTrash()) {
    		return false;
    	}
    
    	return true;
    }

    public function serialize()
    {
    	return serialize(array($this->id, $this->username, $this->password, $this->User_Mail, $this->getRoles(), $this->Trash));
    }
    
    public function unserialize($serialized)
    {
    	list($this->id, $this->username, $this->password, $this->User_Mail, $this->User_Roles, $this->Trash) = unserialize($serialized);
    }
	
	public function getUserNameDn() {
	    $tempname = $this->userNameDn;
	    $nameparts = preg_split('~\\\\.(*SKIP)(*FAIL)|,~s', $tempname);
	    $tempname = implode('/', $nameparts);
		return $tempname;
	}
	
	public function setUserNameDn( $userNameDn) {
		$this->userNameDn = $userNameDn;
		return $this;
	}
	
	public function getUserNameDnAbbreviated() {
		return $this->userNameDnAbbreviated;
	}
	
	public function setUserNameDnAbbreviated( $userNameDnAbbreviated) {
		$this->userNameDnAbbreviated = $userNameDnAbbreviated;
		return $this;
	}
	
	/**
	 * Add userAppbuilderApps
	 * 
	 * @param \Docova\DocovaBundle\Entity\Libraries $app
	 * @return UserAccounts
	 */
	public function addUserAppbuilderApps(\Docova\DocovaBundle\Entity\Libraries $app)
	{
		$this->userAppbuilderApps[] = $app;

		return $this;
	}

	/**
	 * Remove userAppbuilderApps
	 * 
	 * @param \Docova\DocovaBundle\Entity\Libraries $app
	 */
	public function removeUserAppbuilderApps(\Docova\DocovaBundle\Entity\Libraries $app)
	{
		$this->userAppbuilderApps->removeElement($app);
	}

	/**
	 * Get userAppbuilderApps
	 * 
	 * @return ArrayCollection
	 */
	public function getUserAppbuilderApps()
	{
		return $this->userAppbuilderApps;
	}

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Security\Core\User\AdvancedUserInterface::isAccountNonExpired()
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Security\Core\User\AdvancedUserInterface::isAccountNonLocked()
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Security\Core\User\AdvancedUserInterface::isCredentialsNonExpired()
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Security\Core\User\AdvancedUserInterface::isEnabled()
     */
    public function isEnabled()
    {
        return !$this->Trash;
    }
}