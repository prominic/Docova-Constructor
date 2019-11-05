<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FormNameValues
 *
 * @ORM\Table(name="tb_form_name_values")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\FormNameValuesRepository")
 */
class FormNameValues
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
     * @ORM\ManyToOne(targetEntity="UserAccounts")
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
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="Name_Values")
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
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set order
     *
     * @param integer $order
     * @return FormNameValues
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
     * Set fieldValue
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $fieldValue
     * @return FormNameValues
     */
    public function setFieldValue(\Docova\DocovaBundle\Entity\UserAccounts $fieldValue)
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }

    /**
     * Get fieldValue
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Set Document
     *
     * @param \Docova\DocovaBundle\Entity\Documents $document
     * @return FormNameValues
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
     * @return FormNameValues
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
    
    /**
     * Set trash
     * 
     * @param boolean $trash
     * @return FormNameValues
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
}
