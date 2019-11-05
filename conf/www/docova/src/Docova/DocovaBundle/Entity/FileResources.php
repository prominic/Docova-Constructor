<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileResources
 *
 * @ORM\Table(name="tb_file_resources")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\FileResourcesRepository")
 */
class FileResources
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
     * @ORM\Column(name="Name", type="string", length=100)
     */
    protected $Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Type", type="string", length=20)
     */
    protected $Type;

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
     * @ORM\Column(name="Version", type="string", length=20, nullable=true)
     */
    protected $Version;

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
     * Set File_Name
     *
     * @param string $fileName
     * @return FileResources
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
     * @return FileResources
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
     * @return FileResources
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
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return FileResources
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
	
	public function getName() {
		return $this->Name;
	}
	public function setName($Name) {
		$this->Name = $Name;
		return $this;
	}
	public function getType() {
		return $this->Type;
	}
	public function setType($Type) {
		$this->Type = $Type;
		return $this;
	}
	
	public function getVersion() {
		return $this->Version;
	}
	public function setVersion($Version) {
		$this->Version = $Version;
		return $this;
	}	
	
}
