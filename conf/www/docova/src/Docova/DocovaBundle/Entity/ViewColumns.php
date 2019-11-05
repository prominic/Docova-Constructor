<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ViewColumns
 *
 * @ORM\Table(name="tb_view_columns")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\ViewColumnsRepository")
 */
class ViewColumns
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
     * @ORM\Column(name="Title", type="string", length=255, nullable=true)
     */
    protected $Title;

    /**
     * @var string
     *
     * @ORM\Column(name="Field_Name", type="text", nullable=true)
     */
    protected $Field_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="XML_Name", type="string", length=50, nullable=true)
     */
    protected $XML_Name;

    /**
     * @var integer
     *
     * @ORM\Column(name="Data_Type", type="smallint", nullable=true)
     */
    protected $Data_Type;
    
    /**
     * @var integer
     * 
     * @ORM\Column(name="Width", type="smallint", nullable=true)
     */
    protected $Width;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Column_Type", type="boolean")
     */
    protected $Column_Type;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Column_Status", type="boolean")
     */
    protected $Column_Status=true;
    
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="DocumentTypes")
     * @ORM\JoinTable(name="tb_column_related_doctypes",
     *      joinColumns={@ORM\JoinColumn(name="Column_Id", referencedColumnName="id", nullable=false, onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="DocType_Id", referencedColumnName="id", nullable=false,onDelete="CASCADE")})
     */
    protected $Related_DocTypes;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="Libraries")
     * @ORM\JoinTable(name="tb_column_applicable_libraries",
     *      joinColumns={@ORM\JoinColumn(name="Column_Id", referencedColumnName="id", nullable=false,onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Library_Id", referencedColumnName="id", nullable=false,onDelete="CASCADE")})
     */
    protected $Applicable_Libraries;


    public function __construct()
    {
    	$this->Related_DocTypes = new ArrayCollection();
    	$this->Applicable_Libraries = new ArrayCollection();
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
     * Set Title
     *
     * @param string $title
     * @return ViewColumns
     */
    public function setTitle($title = null)
    {
        $this->Title = $title;
    
        return $this;
    }

    /**
     * Get Title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->Title;
    }

    /**
     * Set Field_Name
     *
     * @param string $fieldName
     * @return ViewColumns
     */
    public function setFieldName($fieldName = null)
    {
        $this->Field_Name = $fieldName;
    
        return $this;
    }

    /**
     * Get Field_Name
     *
     * @return string 
     */
    public function getFieldName()
    {
        return $this->Field_Name;
    }

    /**
     * Set XML_Name
     *
     * @param string $xMLName
     * @return ViewColumns
     */
    public function setXMLName($xMLName = null)
    {
        $this->XML_Name = $xMLName;
    
        return $this;
    }

    /**
     * Get XML_Name
     *
     * @return string 
     */
    public function getXMLName()
    {
        return $this->XML_Name;
    }

    /**
     * Set Data_Type
     *
     * @param integer $dataType
     * @return ViewColumns
     */
    public function setDataType($dataType = null)
    {
        $this->Data_Type = $dataType;
    
        return $this;
    }

    /**
     * Get Data_Type
     *
     * @return integer 
     */
    public function getDataType()
    {
        return $this->Data_Type;
    }

    /**
     * Set Width
     * 
     * @param integer $width
     * @return ViewColumns
     */
    public function setWidth($width = null)
    {
    	$this->Width = $width;
    	
    	return $this;
    }

    /**
     * Get Width
     * 
     * @return integer
     */
    public function getWidth()
    {
    	return $this->Width;
    }

    /**
     * Set Built_In (Column_Type)
     *
     * @param boolean $column_type
     * @return ViewColumns
     */
    public function setColumnType($column_type = null)
    {
        $this->Column_Type = $column_type;
    
        return $this;
    }

    /**
     * Get Built_In (ColumnType)
     *
     * @return boolean 
     */
    public function getColumnType()
    {
        return $this->Column_Type;
    }


    /**
     * Add Related_DocTypes
     * 
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $doc_type
     */
    public function addRelatedDocTypes(\Docova\DocovaBundle\Entity\DocumentTypes $doc_type)
    {
    	$this->Related_DocTypes[] = $doc_type;
    }
    
    /**
     * Remove Related_DocTypes
     *
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $doc_type
     */
    public function removeRelatedDocType(\Docova\DocovaBundle\Entity\DocumentTypes $doc_type)
    {
    	$this->Related_DocTypes->removeElement($doc_type);
    }

    /**
     * Get Related_DocTypes
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getRelatedDocTypes()
    {
    	return $this->Related_DocTypes;
    }
    
    /**
     * Add Applicable_Library
     * 
     * @param \Docova\DocovaBundle\Entity\Libraries $library
     */
    public function addApplicableLibraries(\Docova\DocovaBundle\Entity\Libraries $library)
    {
    	$this->Applicable_Libraries[] = $library;
    }
    
    /**
     * Remove Applicable_Libraries
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $library
     */
    public function removeApplicableLibrary(\Docova\DocovaBundle\Entity\Libraries $library)
    {
    	$this->Applicable_Libraries->removeElement($library);
    }

    /**
     * Get Applicable_Libraries
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getApplicableLibraries()
    {
    	return $this->Applicable_Libraries;
    }

	/**
	 * Get Column_Status
	 * 
	 * @return boolean
	 */
	public function getColumnStatus() 
	{
		return $this->Column_Status;
	}
	
	/**
	 * Set Column_Status
	 * 
	 * @param boolean $Column_Status
	 * @return ViewColumns
	 */
	public function setColumnStatus($Column_Status) 
	{
		$this->Column_Status = $Column_Status;
		
		return $this;
	}
}
