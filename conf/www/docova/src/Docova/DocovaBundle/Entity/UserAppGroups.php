<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * UserAppGroups
 *
 * @ORM\Table(name="tb_user_app_groups")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\UserAppGroupsRepository")
 */
class UserAppGroups
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
     * @ORM\Column(name="Group_Name", type="string", length=255)
     */
    protected $groupName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Modified", type="datetime", nullable=true)
     */
    protected $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $createdBy;

    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="AppGroupsContent", mappedBy="appGroup")
     */
    protected $appsList;


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
     * Set groupName
     *
     * @param string $groupName
     *
     * @return UserAppGroups
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * Get groupName
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return UserAppGroups
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     *
     * @return UserAppGroups
     */
    public function setDateModified($dateModified)
    {
    	$this->dateModified = $dateModified;
    
    	return $this;
    }
    
    /**
     * Get dateModified
     *
     * @return \DateTime
     */
    public function getDateModified()
    {
    	return $this->dateModified;
    }

    /**
     * Set createdBy
     *
     * @param UserAccounts $createdBy
     *
     * @return UserAppGroups
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return UserAccounts
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Get appsList
     * 
     * @return ArrayCollection
     */
    public function getAppsList()
    {
    	return $this->appsList;
    }

    /**
     * Add appsList
     * 
     * @param AppGroupsContent $appGroupContent
     * @return UserAppGroups
     */
    public function addAppsList(\Docova\DocovaBundle\Entity\AppGroupsContent $appGroupContent)
    {
    	$this->appsList->add($appGroupContent);
    	
    	return $this;
    }

    /**
     * Remove appsList
     * 
     * @param AppGroupsContent $appGroupContent
     */
    public function removeAppsList(\Docova\DocovaBundle\Entity\AppGroupsContent $appGroupContent)
    {
    	$this->appsList->removeElement($appGroupContent);
    }
}

