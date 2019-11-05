<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TempEditAttachment
 *
 * @ORM\Table(name="tb_temp_edit_attachment")
 * @ORM\Entity
 */
class TempEditAttachment
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
     * @var string
     *
     * @ORM\Column(name="File_Name", type="string", length=255)
     */
    protected $fileName;

    /**
     * @var string
     *
     * @ORM\Column(name="File_Path", type="string", length=255)
     */
    protected $filePath;

    /**
     * @var string
     *
     * @ORM\Column(name="Document", type="string", length=255)
     */
    protected $document;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Track_Edit", type="boolean")
     */
    protected $trackEdit;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Track_User", referencedColumnName="id", nullable=false)
     */
    protected $trackUser;


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
     * Set fileName
     *
     * @param string $fileName
     * @return TempEditAttachment
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    
        return $this;
    }

    /**
     * Get fileName
     *
     * @return string 
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set filePath
     *
     * @param string $filePath
     * @return TempEditAttachment
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    
        return $this;
    }

    /**
     * Get filePath
     *
     * @return string 
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Set document
     *
     * @param string $document
     * @return TempEditAttachment
     */
    public function setDocument($document)
    {
        $this->document = $document;
    
        return $this;
    }

    /**
     * Get document
     *
     * @return string 
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Set trackEdit
     *
     * @param boolean $trackEdit
     * @return TempEditAttachment
     */
    public function setTrackEdit($trackEdit)
    {
        $this->trackEdit = $trackEdit;
    
        return $this;
    }

    /**
     * Get trackEdit
     *
     * @return boolean 
     */
    public function getTrackEdit()
    {
        return $this->trackEdit;
    }

    /**
     * Set trackUser
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $trackUser
     * @return TempEditAttachment
     */
    public function setTrackUser(\Docova\DocovaBundle\Entity\UserAccounts $trackUser)
    {
        $this->trackUser = $trackUser;
    
        return $this;
    }

    /**
     * Get trackUser
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getTrackUser()
    {
        return $this->trackUser;
    }
}