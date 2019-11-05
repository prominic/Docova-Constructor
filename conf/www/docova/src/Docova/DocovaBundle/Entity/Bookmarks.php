<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Bookmarks
 *
 * @ORM\Table(name="tb_bookmarks", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="Unique_Indxes", columns={"Document_Id", "Target_Folder"})})
 * @ORM\Entity
 * @UniqueEntity(fields={"Document", "Target_Folder"})
 */
class Bookmarks
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="Bookmarks")
     * @ORM\JoinColumn(name="Document_Id", referencedColumnName="id", nullable=false)
     */
    protected $Document;
    
    /**
     * @ORM\ManyToOne(targetEntity="Folders", inversedBy="Bookmarks")
     * @ORM\JoinColumn(name="Target_Folder", referencedColumnName="id", nullable=false)
     */
    protected $Target_Folder;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $Created_By;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $Date_Created;


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
     * Set Document
     * 
     * @param \Docova\DocovaBundle\Entity\Documents $document
     */
    public function setDocument(\Docova\DocovaBundle\Entity\Documents $document)
    {
    	$this->Document = $document;
    }

    /**
     * Get Document
     * 
     * @return \Docova\DocovaBundle\Entity\Documents
     */
    public function getDocument()
    {
    	return $this->Document;
    }

    /**
     * Set Target_Folder
     * 
     * @param \Docova\DocovaBundle\Entity\Folders $folder
     */
    public function setTargetFolder(\Docova\DocovaBundle\Entity\Folders $folder)
    {
    	$this->Target_Folder = $folder;
    }

    /**
     * Get Target_Folder
     * 
     * @return \Docova\DocovaBundle\Entity\Folders
     */
    public function getTargetFolder()
    {
    	return $this->Target_Folder;
    }

    /**
     * Set Created_By
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->Created_By = $user;
    }

    /**
     * Get Created_By
     * 
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getCreatedBy()
    {
    	return $this->Created_By;
    }

    /**
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return Bookmarks
     */
    public function setDateCreated($dateCreated)
    {
        $this->Date_Created = $dateCreated;
    
        return $this;
    }

    /**
     * Get Date_Created
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->Date_Created;
    }
}
