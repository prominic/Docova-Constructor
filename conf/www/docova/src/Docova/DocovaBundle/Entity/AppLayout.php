<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppLayout
 *
 * @ORM\Table(name="tb_app_layouts")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AppLayoutRepository")
 */
class AppLayout
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
     * @ORM\Column(name="Layout_Id", type="string", length=255)
     */
    protected $layoutId;

    /**
     * @var string
     * 
     * @ORM\Column(name="Layout_Alias", type="string", length=255, nullable=true)
     */
    protected $layoutAlias;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Layout_Default", type="boolean", options={"default":false})
     */
    protected $layoutDefault = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Prohibit_Design_Update", type="boolean", options={"default":false})
     */
    protected $prohibitDesignUpdate = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime", nullable=true)
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
     * @ORM\Column(name="Date_Modified", type="datetime", nullable=false)
     */
    protected $dateModified;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id", nullable=true)
     */
    protected $modifiedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries", inversedBy="layouts")
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
     * Set layoutId
     * 
     * @param string $layoutId
     * @return AppLayout
     */
    public function setLayoutId($layoutId)
    {
    	$this->layoutId = $layoutId;
    	
    	return $this;
    }

    /**
     * Get layoutId
     * 
     * @return string
     */
    public function getLayoutId()
    {
    	return $this->layoutId;
    }

    /**
     * Set LayoutAlias
     * 
     * @param string $layoutAlias
     * @return AppLayout
     */
    public function setLayoutAlias($layoutAlias = null)
    {
    	$this->layoutAlias = $layoutAlias;
    	
    	return $this;
    }

    /**
     * Get LayoutAlias
     * 
     * @return string
     */
    public function getLayoutAlias()
    {
    	return $this->layoutAlias;
    }

    /**
     * Set layoutDefault
     *
     * @param boolean $layoutDefault
     * @return AppLayout
     */
    public function setLayoutDefault($layoutDefault)
    {
        $this->layoutDefault = $layoutDefault;

        return $this;
    }

    /**
     * Get layoutDefault
     *
     * @return boolean 
     */
    public function getLayoutDefault()
    {
        return $this->layoutDefault;
    }

    /**
     * Set prohibitDesignUpdate
     *
     * @param boolean $prohibitDesignUpdate
     * @return AppLayout
     */
    public function setProhibitDesignUpdate($prohibitDesignUpdate)
    {
        $this->prohibitDesignUpdate = $prohibitDesignUpdate;

        return $this;
    }

    /**
     * Get prohibitDesignUpdate
     *
     * @return boolean 
     */
    public function getProhibitDesignUpdate()
    {
        return $this->prohibitDesignUpdate;
    }

    /**
     * Set application
     * 
     * @param \Docova\DocovaBundle\Entity\Libraries $app
     * @return AppLayout
     */
    public function setApplication(\Docova\DocovaBundle\Entity\Libraries $app)
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

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return AppLayout
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
     * @return AppLayout
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
     * @return AppLayout
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
     * @return AppLayout
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
}
