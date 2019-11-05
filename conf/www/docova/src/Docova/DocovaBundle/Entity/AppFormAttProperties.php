<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppFormAttProperties
 *
 * @ORM\Table(name="tb_app_form_att_properties")
 * @ORM\Entity
 */
class AppFormAttProperties
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
     * @var boolean
     *
     * @ORM\Column(name="Read_Only", type="boolean")
     */
    protected $readOnly = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Max_Files", type="smallint", options={"default":0})
     */
    protected $maxFiles = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="Allowed_Extensions", type="string", length=255, nullable=true)
     */
    protected $allowedExtensions;

    /**
     * @var boolean
     *
     * @ORM\Column(name="File_View_Logging", type="boolean")
     */
    protected $fileViewLogging = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="File_Download_Logging", type="boolean")
     */
    protected $fileDownloadLogging = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Local_Scan", type="boolean")
     */
    protected $localScan = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="File_Ciao", type="boolean")
     */
    protected $fileCiao = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Template_Type", type="string", length=7, nullable=true)
     */
    protected $templateType;

    /**
     * @var string
     *
     * @ORM\Column(name="Template_List", type="string", length=1024, nullable=true)
     */
    protected $templateList;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Template_Auto_Attach", type="boolean")
     */
    protected $templateAutoAttach = false;

    /**
     * @var string
     * 
     * @ORM\Column(name="Hide_Attachment", type="string", length=5, nullable=true)
     */
    protected $hideAttachment;

    /**
     * @ORM\OneToOne(targetEntity="AppForms", inversedBy="attachmentProp")
     * @ORM\JoinColumn(name="App_Form_Id", referencedColumnName="id")
     */
    protected $appForm;


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
     * Set readOnly
     *
     * @param boolean $readOnly
     * @return AppFormAttProperties
     */
    public function setReadOnly($readOnly)
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    /**
     * Get readOnly
     *
     * @return boolean 
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * Set maxFiles
     *
     * @param integer $maxFiles
     * @return AppFormAttProperties
     */
    public function setMaxFiles($maxFiles)
    {
        $this->maxFiles = $maxFiles;

        return $this;
    }

    /**
     * Get maxFiles
     *
     * @return integer 
     */
    public function getMaxFiles()
    {
        return $this->maxFiles;
    }

    /**
     * Set allowedExtensions
     *
     * @param string $allowedExtensions
     * @return AppFormAttProperties
     */
    public function setAllowedExtensions($allowedExtensions)
    {
        $this->allowedExtensions = $allowedExtensions;

        return $this;
    }

    /**
     * Get allowedExtensions
     *
     * @return string 
     */
    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    /**
     * Set fileViewLogging
     *
     * @param boolean $fileViewLogging
     * @return AppFormAttProperties
     */
    public function setFileViewLogging($fileViewLogging)
    {
        $this->fileViewLogging = $fileViewLogging;

        return $this;
    }

    /**
     * Get fileViewLogging
     *
     * @return boolean 
     */
    public function getFileViewLogging()
    {
        return $this->fileViewLogging;
    }

    /**
     * Set fileDownloadLogging
     *
     * @param boolean $fileDownloadLogging
     * @return AppFormAttProperties
     */
    public function setFileDownloadLogging($fileDownloadLogging)
    {
        $this->fileDownloadLogging = $fileDownloadLogging;

        return $this;
    }

    /**
     * Get fileDownloadLogging
     *
     * @return boolean 
     */
    public function getFileDownloadLogging()
    {
        return $this->fileDownloadLogging;
    }

    /**
     * Set localScan
     *
     * @param boolean $localScan
     * @return AppFormAttProperties
     */
    public function setLocalScan($localScan)
    {
        $this->localScan = $localScan;

        return $this;
    }

    /**
     * Get localScan
     *
     * @return boolean 
     */
    public function getLocalScan()
    {
        return $this->localScan;
    }

    /**
     * Set fileCiao
     *
     * @param boolean $fileCiao
     * @return AppFormAttProperties
     */
    public function setFileCiao($fileCiao)
    {
        $this->fileCiao = $fileCiao;

        return $this;
    }

    /**
     * Get fileCiao
     *
     * @return boolean 
     */
    public function getFileCiao()
    {
        return $this->fileCiao;
    }

    /**
     * Set templateType
     *
     * @param string $templateType
     * @return AppFormAttProperties
     */
    public function setTemplateType($templateType = null)
    {
        $this->templateType = $templateType;

        return $this;
    }

    /**
     * Get templateType
     *
     * @return string 
     */
    public function getTemplateType()
    {
        return $this->templateType;
    }

    /**
     * Set templateList
     *
     * @param string $templateList
     * @return AppFormAttProperties
     */
    public function setTemplateList($templateList = null)
    {
        $this->templateList = $templateList;

        return $this;
    }

    /**
     * Get templateList
     *
     * @return string 
     */
    public function getTemplateList()
    {
        return $this->templateList;
    }

    /**
     * Set templateAutoAttach
     *
     * @param boolean $templateAutoAttach
     * @return AppFormAttProperties
     */
    public function setTemplateAutoAttach($templateAutoAttach)
    {
        $this->templateAutoAttach = $templateAutoAttach;

        return $this;
    }

    /**
     * Get templateAutoAttach
     *
     * @return boolean 
     */
    public function getTemplateAutoAttach()
    {
        return $this->templateAutoAttach;
    }

    /**
     * Set appForm
     *
     * @param \Docova\DocovaBundle\Entity\AppForms $appForm
     * @return AppFormAttProperties
     */
    public function setAppForm(\Docova\DocovaBundle\Entity\AppForms $appForm = null)
    {
        $this->appForm = $appForm;

        return $this;
    }

    /**
     * Get appForm
     *
     * @return \Docova\DocovaBundle\Entity\AppForms 
     */
    public function getAppForm()
    {
        return $this->appForm;
    }

    /**
     * Set hideAttachment
     *
     * @param string $hideAttachment
     * @return AppFormAttProperties
     */
    public function setHideAttachment($hideAttachment)
    {
        $this->hideAttachment = $hideAttachment;

        return $this;
    }

    /**
     * Get hideAttachment
     *
     * @return string 
     */
    public function getHideAttachment()
    {
        return $this->hideAttachment;
    }
}
