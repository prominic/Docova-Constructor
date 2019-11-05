<?php

namespace Docova\DocovaBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * MigratedAppACE
 *
 * @ORM\Table(name="tb_migrated_app_ace")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\MigratedAppACERepository")
 */
class MigratedAppACE
{
    /**
	 * @ORM\Column(name="id", type="guid")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="UUID")
	 */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="application_id", referencedColumnName="id", nullable=false)
     */
    protected $application;
    
 /**
     * @var string
     * 
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;
        
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;
    
    /**
     * @ORM\Column(name="entity_id", type="string", length=36, nullable=true)
    */
    protected $entity_id;//related account or group
    
    /**
     * @var string
     *
     * @ORM\Column(name="access", type="string", length=255)
     */
    protected $access;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="access_level", type="smallint", length=255)
     */
    protected $access_level;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="can_create", type="boolean", nullable=true )
     */
    protected $can_create;

    /**
     * @var boolean
     *
     * @ORM\Column(name="can_delete", type="boolean", nullable=true )
     */
    protected $can_delete;
    
    /**
     * @var string
     *
     * @ORM\Column(name="roles", type="string", length=3072)
     */
    protected $roles;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Migration_Completed", type="boolean", nullable=true, options={"default":false})
     */
    protected $migrated = false;


	/**
	 * Get id
	 * 
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get application
	 * 
	 * @return \Docova\DocovaBundle\Entity\Libraries
	 */
	public function getApplication() {
		return $this->application;
	}

	/**
	 * Set application
	 * 
	 * @param \Docova\DocovaBundle\Entity\Libraries $application
	 * @return MigratedAppACE
	 */
	public function setApplication($application) {
		$this->application = $application;
		return $this;
	}

	/**
	 * Get name
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set name
	 * 
	 * @param string $name
	 * @return MigratedAppACE
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get entity_id
	 * 
	 * @return string
	 */
	public function getEntityId() {
		return $this->entity_id;
	}

	/**
	 * Set entity_id
	 * 
	 * @param string $entity_id
	 * @return MigratedAppACE
	 */
	public function setEntityId($entity_id) {
		$this->entity_id = $entity_id;
		return $this;
	}

	/**
	 * Get access
	 * 
	 * @return string
	 */
	public function getAccess() {
		return $this->access;
	}

	/**
	 * Set access
	 * 
	 * @param string $Access
	 * @return MigratedAppACE
	 */
	public function setAccess($Access) {
		$this->access = $Access;
		return $this;
	}

	/**
	 * Get access_level
	 * 
	 * @return integer
	 */
	public function getAccessLevel() {
		return $this->access_level;
	}

	/**
	 * Set access_level
	 * 
	 * @param integer $AccessLevel
	 * @return MigratedAppACE
	 */
	public function setAccessLevel($AccessLevel) {
		$this->access_level = $AccessLevel;
		return $this;
	}

	/**
	 * Get can_create
	 * 
	 * @return boolean
	 */
	public function getCanCreate() {
		return $this->can_create;
	}

	/**
	 * Set can_create
	 * 
	 * @param boolean $Can_Create
	 * @return MigratedAppACE
	 */
	public function setCanCreate($Can_Create) {
		$this->can_create = $Can_Create;
		return $this;
	}

	/**
	 * Get can_delete
	 * 
	 * @return boolean
	 */
	public function getCanDelete() {
		return $this->can_delete;
	}

	/**
	 * Set can_delete
	 * 
	 * @param boolean $Can_Delete
	 * @return MigratedAppACE
	 */
	public function setCanDelete($Can_Delete) {
		$this->can_delete = $Can_Delete;
		return $this;
	}

	/**
	 * Get roles
	 * 
	 * @return string
	 */
	public function getRoles() {
		return $this->roles;
	}
	/**
	 * Set roles
	 * 
	 * @param string $roles
	 * @return MigratedAppACE
	 */
	public function setRoles($roles) {
		$this->roles = $roles;
		return $this;
	}

	/**
	 * Get type
	 * 
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Set type
	 * 
	 * @param string $type
	 * @return MigratedAppACE
	 */
	public function setType($type) {
		$this->type = $type;
		return $this;
	}
	
	/**
	 * Set migrated
	 * 
	 * @param boolean $migrated
	 * @return MigratedAppACE
	 */
	public function setMigrated($migrated)
	{
		$this->migrated = $migrated;
		return $this;
	}
	
	/**
	 * Get migrated
	 * 
	 * @return boolean
	 */
	public function getMigrated()
	{
		return $this->migrated;
	}
}
