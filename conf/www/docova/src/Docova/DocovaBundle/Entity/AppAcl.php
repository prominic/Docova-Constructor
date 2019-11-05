<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppAcl
 *
 * @ORM\Table(name="tb_app_acl")
 * @ORM\Entity
 */
class AppAcl
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Create_Document", type="boolean")
     */
    protected $createDocument;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Delete_Document", type="boolean")
     */
    protected $deleteDocument;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="No_Access", type="boolean", options={"default": false})
     */
    protected $noAccess = false;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="App_Id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $application;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="User_Id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $userObject;

    /**
     * @ORM\ManyToOne(targetEntity="UserRoles")
     * @ORM\JoinColumn(name="Group_Id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $groupObject;


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
     * Set createDocument
     *
     * @param boolean $createDocument
     * @return AppAcl
     */
    public function setCreateDocument($createDocument)
    {
        $this->createDocument = $createDocument;

        return $this;
    }

    /**
     * Get createDocument
     *
     * @return boolean 
     */
    public function getCreateDocument()
    {
        return $this->createDocument;
    }

    /**
     * Set deleteDocument
     *
     * @param boolean $deleteDocument
     * @return AppAcl
     */
    public function setDeleteDocument($deleteDocument)
    {
        $this->deleteDocument = $deleteDocument;

        return $this;
    }

    /**
     * Get deleteDocument
     *
     * @return boolean 
     */
    public function getDeleteDocument()
    {
        return $this->deleteDocument;
    }

    /**
     * Set noAccess
     *
     * @param boolean $noAccess
     * @return AppAcl
     */
    public function setNoAccess($noAccess)
    {
    	$this->noAccess = $noAccess;
    
    	return $this;
    }

    /**
     * Get noAccess
     *
     * @return boolean
     */
    public function getNoAccess()
    {
    	return $this->noAccess;
    }

    /**
     * Set application
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $application
     * @return AppAcl
     */
    public function setApplication(\Docova\DocovaBundle\Entity\Libraries $application = null)
    {
        $this->application = $application;

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

    /**
     * Set userObject
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $userObject
     * @return AppAcl
     */
    public function setUserObject(\Docova\DocovaBundle\Entity\UserAccounts $userObject = null)
    {
        $this->userObject = $userObject;

        return $this;
    }

    /**
     * Get userObject
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getUserObject()
    {
        return $this->userObject;
    }

    /**
     * Set groupObject
     *
     * @param \Docova\DocovaBundle\Entity\UserRoles $groupObject
     * @return AppAcl
     */
    public function setGroupObject(\Docova\DocovaBundle\Entity\UserRoles $groupObject = null)
    {
        $this->groupObject = $groupObject;

        return $this;
    }

    /**
     * Get groupObject
     *
     * @return \Docova\DocovaBundle\Entity\UserRoles 
     */
    public function getGroupObject()
    {
        return $this->groupObject;
    }
}
