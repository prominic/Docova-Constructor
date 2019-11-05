<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FormTextValues
 *
 * @ORM\Table(name="tb_form_text_values", indexes={@ORM\Index(name="text_values_indexed", columns={"Summary_Value"})})
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\FormTextValuesRepository")
 */
class FormTextValues
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="Text_Values")
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
     * @ORM\Column(name="Summary_Value", type="string", length=450)
     */
    protected $summaryValue;

    /**
     * @var string
     *
     * @ORM\Column(name="Field_Value", type="text")
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
     * @ORM\Column(name="Trash", type="boolean", nullable=false)
     */
    protected $Trash = false;
    

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
     * Set fieldValue
     *
     * @param string $fieldValue
     * @return FormTextValues
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;
    
        if (is_null($this->fieldValue)){
        	$this->fieldValue="";
        	$this->setSummaryValue("");
        }
        else{
        	$summaryLen = strlen($fieldValue);
        	$summary = $summaryLen>450 ? substr($this->fieldValue,0,450) : $this->fieldValue;
        	$this->setSummaryValue($summary);
        }
                
        
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
     * Set Trash
     * 
     * @param boolean $trash
     * @return FormTextValues
     */
    public function setTrash($trash)
    {
    	$this->Trash = $trash;
    	
    	return $this;
    }
    
    /**
     * Get Trash
     * 
     * @return boolean
     */
    public function getTrash()
    {
    	return $this->Trash;
    }

    /**
     * Set summaryValue
     *
     * @param string $summaryValue
     * @return FormTextValues
     */
    public function setSummaryValue($summaryValue)
    {
        $this->summaryValue = $summaryValue;

        return $this;
    }

    /**
     * Get summaryValue
     *
     * @return string 
     */
    public function getSummaryValue()
    {
        return $this->summaryValue;
    }

    /**
     * Set order
     *
     * @param integer $order
     * @return FormTextValues
     */
    public function setOrder($order = null)
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
}
