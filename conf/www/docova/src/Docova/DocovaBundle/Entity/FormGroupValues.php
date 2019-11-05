<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FormGroupValues
 *
 * @ORM\Table(name="tb_form_group_values")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\FormGroupValuesRepository")
 */
class FormGroupValues
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
     * @ORM\ManyToOne(targetEntity="UserRoles")
     * @ORM\JoinColumn(name="Field_Value", referencedColumnName="id", nullable=false)
     */
    protected $fieldValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="Value_Order", type="smallint", nullable=true)
     */
    protected $order;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean", options={"default":false})
     */
    protected $trash = false;

    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="Group_Values")
     * @ORM\JoinColumn(name="Doc_Id", referencedColumnName="id", nullable=false)
     */
    protected $Document;
    
    /**
     * @ORM\ManyToOne(targetEntity="DesignElements")
     * @ORM\JoinColumn(name="Field_Id", referencedColumnName="id", nullable=false)
     */
    protected $Field;
	

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
     * Set fieldValue
     *
     * @param string $fieldValue
     * @return FormGroupValues
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }

    /**
     * Get fieldValue
     *
     * @return string 
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Set order
     *
     * @param integer $order
     * @return FormGroupValues
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return integer 
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set trash
     *
     * @param boolean $trash
     * @return FormGroupValues
     */
    public function setTrash($trash)
    {
        $this->trash = $trash;

        return $this;
    }

    /**
     * Get trash
     *
     * @return boolean 
     */
    public function getTrash()
    {
        return $this->trash;
    }

    /**
     * Set Document
     *
     * @param \Docova\DocovaBundle\Entity\Documents $document
     * @return FormGroupValues
     */
    public function setDocument(\Docova\DocovaBundle\Entity\Documents $document)
    {
        $this->Document = $document;

        return $this;
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
     * @return FormGroupValues
     */
    public function setField(\Docova\DocovaBundle\Entity\DesignElements $field)
    {
        $this->Field = $field;

        return $this;
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
}
