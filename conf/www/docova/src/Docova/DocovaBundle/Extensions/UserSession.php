<?php
namespace Docova\DocovaBundle\Extensions;

class UserSession 
{
	private $environment = null;
	private $created = null;
	private $modified = null;
	private $userId = null;
	private $userName = null;
	private $ipAddress = null;
	private $user = null;

	public function __construct($user, $created, $modified, $ipAddress, $environment) 
	{
		if (empty ( $user )) {
			throw new \Exception ( "UserSession must be provided a valid user account!" );
		}
			
		// Initialize the session timestamps
		$this->environment = $environment;
		$this->created = $created;
		$this->modified = $modified;
		$this->ipAddress = $ipAddress;
		
		// Initialize the user properties
		$this->userId = $user->getId ();
		$this->userName = $user->getUserNameDnAbbreviated ();
		unset ( $user );
	}

	public function getCreated() 
	{
		return $this->created;
	}

	public function setCreated($created) 
	{
		$this->created = $created;
		return $this;
	}

	public function getModified() 
	{
		return $this->modified;
	}

	public function setModified($modified)
	{
		$this->modified = $modified;
		return $this;
	}

	public function getUserId()
	{
		return $this->userId;
	}

	public function setUserId($userId)
	{
		$this->userId = $userId;
		return $this;
	}

	public function getUserName()
	{
		return $this->userName;
	}

	public function setUserName($userName)
	{
		$this->userName = $userName;
		return $this;
	}

	public function getEnvironment()
	{
		return $this->environment;
	}

	public function setEnvironment($environment)
	{
		$this->environment = $environment;
		return $this;
	}

	public function getIpAddress()
	{
		return $this->ipAddress;
	}

	public function setIpAddress($ipAddress)
	{
		$this->ipAddress = $ipAddress;
		return $this;
	}
}
