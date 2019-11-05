<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DiscussionAttachments
 *
 * @ORM\Table(name="tb_discussion_attachments")
 * @ORM\Entity
 */
class DiscussionAttachments
{
	protected $file_path;
	
	/**
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
     * @var \DateTime
     *
     * @ORM\Column(name="File_Date", type="datetime", nullable=true)
     */
    protected $fileDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="File_Size", type="integer")
     */
    protected $fileSize;

    /**
     * @var string
     *
     * @ORM\Column(name="File_Mime_Type", type="string", length=255)
     */
    protected $fileMimeType;

    /**
     * @ORM\ManyToOne(targetEntity="DiscussionTopic", inversedBy="attachments")
     * @ORM\JoinColumn(name="Discussion_Id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $discussion;


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
     * Set fileName
     *
     * @param string $fileName
     * @return DiscussionAttachments
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
     * Set fileDate
     *
     * @param \DateTime $fileDate
     * @return DiscussionAttachments
     */
    public function setFileDate($fileDate)
    {
        $this->fileDate = $fileDate;
    
        return $this;
    }

    /**
     * Get fileDate
     *
     * @return \DateTime 
     */
    public function getFileDate()
    {
        return $this->fileDate;
    }

    /**
     * Set fileSize
     *
     * @param integer $fileSize
     * @return DiscussionAttachments
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
    
        return $this;
    }

    /**
     * Get fileSize
     *
     * @return integer 
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * Set fileMimeType
     *
     * @param string $fileMimeType
     * @return DiscussionAttachments
     */
    public function setFileMimeType($fileMimeType)
    {
        $this->fileMimeType = $fileMimeType;
    
        return $this;
    }

    /**
     * Get fileMimeType
     *
     * @return string 
     */
    public function getFileMimeType()
    {
        return $this->fileMimeType;
    }

    /**
     * Set discussion
     * 
     * @param \Docova\DocovaBundle\Entity\DiscussionTopic $discussion
     */
    public function setDiscussion(\Docova\DocovaBundle\Entity\DiscussionTopic $discussion) 
    {
    	$this->discussion = $discussion;
    }

	/**
	 * Get discussion
	 * 
	 * @return \Docova\DocovaBundle\Entity\DiscussionTopic
	 */
	public function getDiscussion() 
	{
		return $this->discussion;
	}

	/**
	 * Get content mime type
	 *
	 * @return string
	 */
	public function getContentType()
	{
		return $this->File_Mime_Type;
	}
	
	/**
	 * Get file name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->fileName;
	}
	
	/**
	 * Get file content encoded base64
	 *
	 * @return string
	 */
	public function getContent()
	{
		$this->file_path = realpath('../').DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
		if ($this->getDiscussion() && file_exists($this->file_path . DIRECTORY_SEPARATOR . $this->getDiscussion()->getParentDocument()->getId() . DIRECTORY_SEPARATOR . md5($this->fileName))) {
			return file_get_contents($this->file_path . DIRECTORY_SEPARATOR . $this->getDiscussion()->getParentDocument()->getId() . DIRECTORY_SEPARATOR . md5($this->fileName), 'r');
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
}
