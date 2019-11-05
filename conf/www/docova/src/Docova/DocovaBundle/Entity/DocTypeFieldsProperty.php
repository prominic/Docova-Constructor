<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocTypeFieldsProperty
 *
 * @ORM\Table(name="tb_doctype_fields_property")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DocTypeFieldsPropertyRepository")
 */
class DocTypeFieldsProperty
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="DocumentTypes")
     * @ORM\JoinColumn(name="Doc_Type_Id", referencedColumnName="id", nullable=false)
     */
    protected $DocumentType;

    /**
     * @ORM\ManyToOne(targetEntity="DesignElements")
     * @ORM\JoinColumn(name="Field_Id", referencedColumnName="id", nullable=false)
     */
    protected $Field;

    /**
     * @var string
     *
     * @ORM\Column(name="Field_Label", type="string", length=255)
     */
    protected $Field_Label;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Read_Only", type="boolean")
     */
    protected $Read_Only = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Default_Value", type="string", length=255, nullable=true)
     */
    protected $Default_Value;

    /**
     * @var string
     *
     * @ORM\Column(name="Text_Color", type="string", length=6, nullable=false)
     */
    protected $Text_Color = '000000';


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
     * Set DocumentType
     *
     * @param \Docova\DocovaBundle\Entity\DocumentTypes $docType
     */
    public function setDocumentType(\Docova\DocovaBundle\Entity\DocumentTypes $docType)
    {
        $this->DocumentType = $docType;
    }

    /**
     * Get DocumentType
     *
     * @return \Docova\DocovaBundle\Entity\DocumentTypes 
     */
    public function getDocumentType()
    {
        return $this->DocumentType;
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
     * Set Field_Label
     *
     * @param string $fieldLabel
     * @return DocTypeFieldsProperty
     */
    public function setFieldLabel($fieldLabel)
    {
        $this->Field_Label = $fieldLabel;
    
        return $this;
    }

    /**
     * Get Field_Label
     *
     * @return string 
     */
    public function getFieldLabel()
    {
        return $this->Field_Label;
    }

    /**
     * Set Read_Only
     *
     * @param boolean $readOnly
     * @return DocTypeFieldsProperty
     */
    public function setReadOnly($readOnly)
    {
        $this->Read_Only = $readOnly;
    
        return $this;
    }

    /**
     * Get Read_Only
     *
     * @return boolean 
     */
    public function getReadOnly()
    {
        return $this->Read_Only;
    }

    /**
     * Set Default_Value
     *
     * @param string $defaultValue
     * @return DocTypeFieldsProperty
     */
    public function setDefaultValue($defaultValue)
    {
        $this->Default_Value = $defaultValue;
    
        return $this;
    }

    /**
     * Get Default_Value
     *
     * @return string 
     */
    public function getDefaultValue()
    {
        return $this->Default_Value;
    }

    /**
     * Set Text_Color
     *
     * @param string $textColor
     * @return DocTypeFieldsProperty
     */
    public function setTextColor($textColor)
    {
        $this->Text_Color = $textColor;
    
        return $this;
    }

    /**
     * Get Text_Color
     *
     * @return string 
     */
    public function getTextColor()
    {
        return $this->Text_Color;
    }
}
