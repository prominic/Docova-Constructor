<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppJavaScripts
 *
 * @ORM\Table(name="tb_app_javascripts")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AppJavaScriptsRepository")
 */
class AppJavaScripts
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
     * @ORM\Column(name="JS_Name", type="string", length=255)
     */
    protected $jSName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="PDU", type="boolean")
     */
    protected $pDU = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id")
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
     * @ORM\ManyToOne(targetEntity="Libraries", inversedBy="jScripts")
     * @ORM\JoinColumn(name="App_Id", referencedColumnName="id")
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
     * Set jSName
     *
     * @param string $jSName
     * @return AppJavaScripts
     */
    public function setJSName($jSName)
    {
        $this->jSName = $jSName;

        return $this;
    }

    /**
     * Get jSName
     *
     * @return string 
     */
    public function getJSName()
    {
        return $this->jSName;
    }

    /**
     * Set pDU
     *
     * @param boolean $pDU
     * @return AppJavaScripts
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
     * @return AppJavaScripts
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
     * @return AppJavaScripts
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
     * @return AppJavaScripts
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
     * @return AppJavaScripts
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
     * @return AppJavaScripts
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
