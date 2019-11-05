<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileTemplates
 *
 * @ORM\Table(name="tb_file_templates")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\FileTemplatesRepository")
 */
class FileTemplates
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
     * @ORM\Column(name="Template_Name", type="string", length=100)
     */
    protected $Template_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Template_Type", type="string", length=20)
     */
    protected $Template_Type;

    /**
     * @var string
     *
     * @ORM\Column(name="File_Name", type="string", length=255, nullable=true)
     */
    protected $File_Name;

    /**
     * @var integer
     *
     * @ORM\Column(name="File_Size", type="bigint", nullable=true)
     */
    protected $File_Size;

    /**
     * @var string
     * 
     * @ORM\Column(name="File_Mime_Type", type="string", length=255, nullable=true)
     */
    protected $File_Mime_Type;

    /**
     * @var string
     *
     * @ORM\Column(name="Template_Version", type="string", length=20, nullable=true)
     */
    protected $Template_Version;

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
     * Set Template_Name
     *
     * @param string $templateName
     * @return FileTemplates
     */
    public function setTemplateName($templateName)
    {
        $this->Template_Name = $templateName;
    
        return $this;
    }

    /**
     * Get Template_Name
     *
     * @return string 
     */
    public function getTemplateName()
    {
        return $this->Template_Name;
    }

    /**
     * Set Template_Type
     *
     * @param string $templateType
     * @return FileTemplates
     */
    public function setTemplateType($templateType)
    {
        $this->Template_Type = $templateType;
    
        return $this;
    }

    /**
     * Get Template_Type
     *
     * @return string 
     */
    public function getTemplateType()
    {
        return $this->Template_Type;
    }

    /**
     * Set File_Name
     *
     * @param string $fileName
     * @return FileTemplates
     */
    public function setFileName($fileName)
    {
        $this->File_Name = $fileName;
    
        return $this;
    }

    /**
     * Get File_Name
     *
     * @return string 
     */
    public function getFileName()
    {
        return $this->File_Name;
    }

    /**
     * Set File_Size
     *
     * @param integer $FileSize
     * @return FileTemplates
     */
    public function setFileSize($FileSize)
    {
        $this->File_Size = $FileSize;
    
        return $this;
    }

    /**
     * Get File_Size
     *
     * @return integer 
     */
    public function getFileSize()
    {
        return $this->File_Size;
    }

    /**
     * Set File_Mime_Type
     * 
     * @param string $mime_type
     * @return FileTemplates
     */
    public function setFileMimeType($mime_type)
    {
    	$this->File_Mime_Type = $mime_type;
    	
    	return $this;
    }

    /**
     * Get File_Mime_Type
     * 
     * @return string
     */
    public function getFileMimeType()
    {
    	return $this->File_Mime_Type;
    }

    /**
     * Set Template_Version
     *
     * @param string $templateVersion
     * @return FileTemplates
     */
    public function setTemplateVersion($templateVersion)
    {
        $this->Template_Version = $templateVersion;
    
        return $this;
    }

    /**
     * Get Template_Version
     *
     * @return string 
     */
    public function getTemplateVersion()
    {
        return $this->Template_Version;
    }

    /**
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return FileTemplates
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
