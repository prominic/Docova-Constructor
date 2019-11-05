<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AttachmentsDetails
 *
 * @ORM\Table(name="tb_attachments_details")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AttachmentsDetailsRepository")
 */
class AttachmentsDetails
{
	protected $file_path;

	/**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="Attachments")
     * @ORM\JoinColumn(name="Doc_Id", referencedColumnName="id", nullable=false)
     */
    protected $Document;
    
    /**
     * @ORM\ManyToOne(targetEntity="DesignElements")
     * @ORM\JoinColumn(name="Field_Id", referencedColumnName="id", nullable=false)
     */
    protected $Field;
    
    /**
     * @var string
     *
     * @ORM\Column(name="File_Name", type="string", length=255)
     */
    protected $File_Name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="File_Date", type="datetime")
     */
    protected $File_Date;

    /**
     * @var integer
     *
     * @ORM\Column(name="File_Size", type="integer", nullable=true)
     */
    protected $File_Size;

    /**
     * @var string
     *
     * @ORM\Column(name="File_Mime_Type", type="string", length=255)
     */
    protected $File_Mime_Type;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Checked_Out", type="boolean", options={"default": false})
     */
    protected $Checked_Out = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Checked_Out_Path", type="string", length=255, nullable=true)
     */
    protected $Checked_Out_Path;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Checked_Out", type="datetime", nullable=true)
     */
    protected $Date_Checked_Out;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Checked_Out_By", referencedColumnName="id", nullable=true)
     */
    protected $Checked_Out_By;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Author_Id", referencedColumnName="id", nullable=false)
     */
    protected $Author;


    public function __construct()
    {
		$this->file_path = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
    }

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
    public function setDocument($document)
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
     * Set Field
     *
     * @param \Docova\DocovaBundle\Entity\DesignElements $field
     */
    public function setField(\Docova\DocovaBundle\Entity\DesignElements $field)
    {
     $this->Field = $field;
    }
    
    /**
     * Get Field
     *
     * @return \Docova\DocovaBundle\Entity\DesignElements
     */
    public function getField()
    {
     return $this->Field;
    }

    /**
     * Set File_Name
     *
     * @param string $fileName
     * @return AttachmentsDetails
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
     * Set File_Date
     *
     * @param \DateTime $fileDate
     * @return AttachmentsDetails
     */
    public function setFileDate($fileDate)
    {
        $this->File_Date = $fileDate;
    
        return $this;
    }

    /**
     * Get File_Date
     *
     * @return \DateTime 
     */
    public function getFileDate()
    {
        return $this->File_Date;
    }

    /**
     * Set File_Size
     *
     * @param integer $fileSize
     * @return AttachmentsDetails
     */
    public function setFileSize($fileSize)
    {
        $this->File_Size = $fileSize;
    
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
     * @param string $fileMimeType
     * @return AttachmentsDetails
     */
    public function setFileMimeType($fileMimeType)
    {
        $this->File_Mime_Type = $fileMimeType;
    
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
     * Set Author
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     * @return AttachmentsDetails
     */
    public function setAuthor(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
     $this->Author = $user;
     
     return $this;
    }

    /**
     * Get Author
     * 
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getAuthor()
    {
     return $this->Author;
    }
    
    /**
     * Get content mime type
     * 
     * @return string
     */
    public function getContentType()
    {
    	return $this->File_Mime_Type;
/*
    	$pathInfo = pathinfo($this->getFileName());
    	$symfonyFile = new File($this->getPath() . '/' . $pathInfo['filename'] . $this->getRevision() . '.' . $pathInfo['extension']);
    	return $symfonyFile->getMimeType();
*/
    }
    
    /**
     * Get file name
     * 
     * @return string 
     */
    public function getName()
    {
    	return $this->File_Name;
    }
    
    /**
     * Get file content encoded base64
     * 
     * @return string
     */
    public function getContent()
    {
		$this->file_path = realpath('../').DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
    	if ($this->getDocument() && file_exists($this->file_path . DIRECTORY_SEPARATOR . $this->getDocument()->getId() . DIRECTORY_SEPARATOR . md5($this->File_Name))) {
	    	return file_get_contents($this->file_path . DIRECTORY_SEPARATOR . $this->getDocument()->getId() . DIRECTORY_SEPARATOR . md5($this->File_Name), NULL, NULL, 0, 500000);
    	}
    	else {
    		return false;
    	}
    }
    
    public function getEncodedFile()
    {
    	$output = $this->getContent();
    	if (!empty($output)) 
    	{
    		return base64_encode($output);
    	}
    	return false;
    }

    /**
     * Set Checked_Out
     *
     * @param boolean $checkedOut
     * @return AttachmentsDetails
     */
    public function setCheckedOut($checkedOut)
    {
        $this->Checked_Out = $checkedOut;
    
        return $this;
    }

    /**
     * Get Checked_Out
     *
     * @return boolean 
     */
    public function getCheckedOut()
    {
        return $this->Checked_Out;
    }

    /**
     * Set Checked_Out_Path
     *
     * @param string $checkedOutPath
     * @return AttachmentsDetails
     */
    public function setCheckedOutPath($checkedOutPath = null)
    {
        $this->Checked_Out_Path = $checkedOutPath;
    
        return $this;
    }

    /**
     * Get Checked_Out_Path
     *
     * @return string 
     */
    public function getCheckedOutPath()
    {
        return $this->Checked_Out_Path;
    }

    /**
     * Set Date_Checked_Out
     *
     * @param \DateTime $dateCheckedOut
     * @return AttachmentsDetails
     */
    public function setDateCheckedOut($dateCheckedOut = null)
    {
        $this->Date_Checked_Out = $dateCheckedOut;
    
        return $this;
    }

    /**
     * Get Date_Checked_Out
     *
     * @return \DateTime 
     */
    public function getDateCheckedOut()
    {
        return $this->Date_Checked_Out;
    }

    /**
     * Set Checked_Out_By
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $checkedOutBy
     * @return AttachmentsDetails
     */
    public function setCheckedOutBy(\Docova\DocovaBundle\Entity\UserAccounts $checkedOutBy = null)
    {
        $this->Checked_Out_By = $checkedOutBy;
    
        return $this;
    }

    /**
     * Get Checked_Out_By
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getCheckedOutBy()
    {
        return $this->Checked_Out_By;
    }
}