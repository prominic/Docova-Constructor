<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppOutlines
 *
 * @ORM\Table(name="tb_app_outlines")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AppOutlinesRepository")
 */
class AppOutlines
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
     * @ORM\Column(name="Outline_Name", type="string", length=255)
     */
    protected $outlineName;

    /**
     * @var string
     *
     * @ORM\Column(name="Outline_Alias", type="string", length=255, nullable=true)
     */
    protected $outlineAlias;

    /**
     * @var string
     * 
     * @ORM\Column(name="Outline_Type", type="string", length=2, nullable=true)
     */
    protected $outlineType;

    /**
     * @var boolean
     *
     * @ORM\Column(name="PDU", type="boolean", options={"comment"="Prohibit Design Update"})
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
     * @ORM\ManyToOne(targetEntity="Libraries", inversedBy="outlines")
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
     * Set outlineName
     *
     * @param string $outlineName
     * @return AppOutlines
     */
    public function setOutlineName($outlineName)
    {
        $this->outlineName = $outlineName;

        return $this;
    }

    /**
     * Get outlineName
     *
     * @return string 
     */
    public function getOutlineName()
    {
        return $this->outlineName;
    }

    /**
     * Set outlineAlias
     *
     * @param string $outlineAlias
     * @return AppOutlines
     */
    public function setOutlineAlias($outlineAlias = null)
    {
        $this->outlineAlias = $outlineAlias;

        return $this;
    }

    /**
     * Get outlineAlias
     *
     * @return string 
     */
    public function getOutlineAlias()
    {
        return $this->outlineAlias;
    }

    /**
     * Set outlineType
     * 
     * @param string $outlineType
     * @return AppOutlines
     */
    public function setOutlineType($outlineType)
    {
    	$this->outlineType = $outlineType;
    	return $this;
    }

    /**
     * Get outlineType
     * 
     * @return string
     */
    public function getOutlineType()
    {
    	return $this->outlineType;
    }

    /**
     * Set pDU
     *
     * @param boolean $pDU
     * @return AppOutlines
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
     * @return AppOutlines
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
     * @return AppOutlines
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
     * @return AppOutlines
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $createdBy = null)
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
     * @return AppOutlines
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
     * @return AppOutlines
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
