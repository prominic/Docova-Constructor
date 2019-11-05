<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ListFieldOptions
 *
 * @ORM\Table(name="tb_list_field_options")
 * @ORM\Entity
 */
class ListFieldOptions
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
     * @ORM\Column(name="Opt_Value", type="string", length=255)
     */
    protected $Opt_Value;

    /**
     * @var string
     *
     * @ORM\Column(name="Display_Option", type="string", length=255)
     */
    protected $Display_Option;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Option_Order", type="smallint")
     */
    protected $Option_Order;
    
    /**
     * @ORM\ManyToOne(targetEntity="DesignElements", inversedBy="Options")
     * @ORM\JoinColumn(name="Field_Id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
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
     * Set Opt_Value
     *
     * @param string $optValue
     * @return ListFieldOptions
     */
    public function setOptValue($optValue)
    {
        $this->Opt_Value = $optValue;
    
        return $this;
    }

    /**
     * Get Opt_Value
     *
     * @return string 
     */
    public function getOptValue()
    {
        return $this->Opt_Value;
    }

    /**
     * Set Display_Option
     *
     * @param string $displayOption
     * @return ListFieldOptions
     */
    public function setDisplayOption($displayOption)
    {
        $this->Display_Option = $displayOption;
    
        return $this;
    }

    /**
     * Get Display_Option
     *
     * @return string 
     */
    public function getDisplayOption()
    {
        return $this->Display_Option;
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
     * @return \Docova\DocovaBundle\Entity\DesignElements
     */
    public function getField()
    {
    	return $this->Field;
    }

    /**
     * Set Option_Order
     *
     * @param integer $optionOrder
     * @return ListFieldOptions
     */
    public function setOptionOrder($optionOrder)
    {
        $this->Option_Order = $optionOrder;
    
        return $this;
    }

    /**
     * Get Option_Order
     *
     * @return integer 
     */
    public function getOptionOrder()
    {
        return $this->Option_Order;
    }
}