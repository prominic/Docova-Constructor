<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RelatedLinks
 *
 * @ORM\Table(name="tb_related_links")
 * @ORM\Entity
 */
class RelatedLinks
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Link_Type", type="boolean")
     */
    protected $linkType;

    /**
     * @var string
     *
     * @ORM\Column(name="Link_Url", type="string", length=255)
     */
    protected $linkUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="Documents")
     * @ORM\JoinColumn(name="Document_Id", referencedColumnName="id", nullable=false)
     */
    protected $document;


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
     * Set linkType
     *
     * @param boolean $linkType
     * @return RelatedLinks
     */
    public function setLinkType($linkType)
    {
        $this->linkType = $linkType;
    
        return $this;
    }

    /**
     * Get linkType
     *
     * @return boolean 
     */
    public function getLinkType()
    {
        return $this->linkType;
    }

    /**
     * Set linkUrl
     *
     * @param string $linkUrl
     * @return RelatedLinks
     */
    public function setLinkUrl($linkUrl)
    {
        $this->linkUrl = $linkUrl;
    
        return $this;
    }

    /**
     * Get linkUrl
     *
     * @return string 
     */
    public function getLinkUrl()
    {
        return $this->linkUrl;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return RelatedLinks
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set document
     *
     * @param \Docova\DocovaBundle\Entity\Documents $document
     * @return RelatedLinks
     */
    public function setDocument(\Docova\DocovaBundle\Entity\Documents $document)
    {
        $this->document = $document;
    
        return $this;
    }

    /**
     * Get document
     *
     * @return \Docova\DocovaBundle\Entity\Documents 
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return RelatedLinks
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
     * Set createdBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $createdBy
     * @return RelatedLinks
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
}