<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppPages
 *
 * @ORM\Table(name="tb_app_pages")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AppPagesRepository")
 */
class AppPages
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Page_Name", type="string", length=255)
     */
    protected $pageName;

    /**
     * @var string
     *
     * @ORM\Column(name="Page_Alias", type="string", length=255, nullable=true)
     */
    protected $pageAlias;

    /**
     * @var string
     *
     * @ORM\Column(name="Background_Color", type="string", length=10, nullable=true)
     */
    protected $backgroundColor;

    /**
     * @var boolean
     *
     * @ORM\Column(name="PDU", type="boolean", options={"comment"="Prohibit Design Update"})
     */
    protected $pDU;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime", nullable=false)
     */
    protected $dateCreated;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Modified", type="datetime", nullable=true)
     */
    protected $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id", nullable=true)
     */
    protected $modifiedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries", inversedBy="pages")
     * @ORM\JoinColumn(name="App_Id", referencedColumnName="id", nullable=false)
     */
    protected $application;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set pageName
     *
     * @param string $pageName
     * @return AppPages
     */
    public function setPageName($pageName)
    {
        $this->pageName = $pageName;

        return $this;
    }

    /**
     * Get pageName
     *
     * @return string 
     */
    public function getPageName()
    {
        return $this->pageName;
    }

    /**
     * Set pageAlias
     *
     * @param string $pageAlias
     * @return AppPages
     */
    public function setPageAlias($pageAlias = null)
    {
        $this->pageAlias = $pageAlias;

        return $this;
    }

    /**
     * Get pageAlias
     *
     * @return string 
     */
    public function getPageAlias()
    {
        return $this->pageAlias;
    }

    /**
     * Set backgroundColor
     *
     * @param string $backgroundColor
     * @return AppPages
     */
    public function setBackgroundColor($backgroundColor = null)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * Get backgroundColor
     *
     * @return string 
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Set pDU
     *
     * @param boolean $pDU
     * @return AppPages
     */
    public function setPDU($pDU)
    {
        $this->pDU = $pDU;

        return $this;
    }

    /**
     * Get pDU
     *
     * @return boolean 
     */
    public function getPDU()
    {
        return $this->pDU;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return AppPages
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
     * @return AppPages
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
     * @param \Docova\DocovaBundle\Entity\UserAccounts $createdBy
     * @return AppPages
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $modifiedBy
     * @return AppPages
     */
    public function setModifiedBy(\Docova\DocovaBundle\Entity\UserAccounts $modifiedBy = null)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set application
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $application
     * @return AppPages
     */
    public function setApplication(\Docova\DocovaBundle\Entity\Libraries $application)
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
}
