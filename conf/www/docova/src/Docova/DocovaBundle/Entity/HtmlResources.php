<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HtmlResources
 *
 * @ORM\Table(name="tb_html_resources")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\HtmlResourcesRepository")
 */
class HtmlResources
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;


    /**
     * @var string (drop down)
     *
     * @ORM\Column(name="Resource_Name", type="string", length=20)
     */
    protected $resourceName;
    
    /**
     * @var string
     *
     * @ORM\Column(name="HTML_code", type="text", nullable=true)
     */
    protected $htmlCode; 
      
    /**
     * @var string
     *
     * @ORM\Column(name="File_Name", type="string", length=255,  nullable=true)
     */
    protected $fileName;

    
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
     * @ORM\Column(name="ResourcePath", type="string", length=255, nullable=true)
     */
    protected $resourcePath;


    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }
	public function getResourceName() {
		return $this->resourceName;
	}
	public function setResourceName( $resourceName) {
		$this->resourceName = $resourceName;
		return $this;
	}
	public function getHtmlCode() {
		return $this->htmlCode;
	}
	public function setHtmlCode( $htmlCode) {
		$this->htmlCode = $htmlCode;
		return $this;
	}
	public function getFileName() {
		return $this->fileName;
	}
	public function setFileName( $fileName) {
		$this->fileName = $fileName;
		return $this;
	}
	public function getFileSize() {
		return $this->File_Size;
	}
	public function setFileSize($File_Size) {
		$this->File_Size = $File_Size;
		return $this;
	}
	public function getFileMimeType() {
		return $this->File_Mime_Type;
	}
	public function setFileMimeType( $File_Mime_Type) {
		$this->File_Mime_Type = $File_Mime_Type;
		return $this;
	}
	public function getResourcePath() {
		return $this->resourcePath;
	}
	public function setResourcePath( $resourcePath) {
		$this->resourcePath = $resourcePath;
		return $this;
	}
	
    
	

 
}
