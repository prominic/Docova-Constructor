<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SubformActionButtons
 *
 * @ORM\Table(name="tb_subform_action_buttons")
 * @ORM\Entity
 */
class SubformActionButtons
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
     * @ORM\Column(name="Action_Name", type="string", length=100)
     */
    protected $Action_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Button_Label", type="string", length=150)
     */
    protected $Button_Label;

    /**
     * @var string
     *
     * @ORM\Column(name="Click_Script", type="text", nullable=true)
     */
    protected $Click_Script;

    /**
     * @var string
     * 
     * @ORM\Column(name="Primary_Icon", type="string", length=100, nullable=true)
     */
    protected $primaryIcon;

    /**
     * @var string
     * 
     * @ORM\Column(name="Secondary_Icon", type="string", length=100, nullable=true)
     */
    protected $secondaryIcon;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserScriptFunction")
     * @ORM\JoinColumn(name="Function_Id", referencedColumnName="id", nullable=true)
     * 
     */
    protected $Visible_On;
    
    /**
     * @ORM\ManyToOne(targetEntity="Subforms", inversedBy="Action_Buttons")
     * @ORM\JoinColumn(name="Subform_Id", referencedColumnName="id", nullable=true)
     */
    protected $Subform;


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
     * Set Action_Name
     *
     * @param string $actionName
     * @return SubformActionButtons
     */
    public function setActionName($actionName)
    {
        $this->Action_Name = $actionName;
    
        return $this;
    }

    /**
     * Get Action_Name
     *
     * @return string 
     */
    public function getActionName()
    {
        return $this->Action_Name;
    }

    /**
     * Set Button_Label
     *
     * @param string $buttonLabel
     * @return SubformActionButtons
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->Button_Label = $buttonLabel;
    
        return $this;
    }

    /**
     * Get Button_Label
     *
     * @return string 
     */
    public function getButtonLabel()
    {
        return $this->Button_Label;
    }

    /**
     * Set Click_Script
     *
     * @param string $clickScript
     * @return SubformActionButtons
     */
    public function setClickScript($clickScript)
    {
        $this->Click_Script = $clickScript;
    
        return $this;
    }

    /**
     * Get Click_Script
     *
     * @return string 
     */
    public function getClickScript()
    {
        return $this->Click_Script;
    }

    /**
     * Set Visible_On
     * 
     * @param \Docova\DocovaBundle\Entity\UserScriptFunction $condition
     */
    public function setVisibleOn(\Docova\DocovaBundle\Entity\UserScriptFunction $function)
    {
    	$this->Visible_On = $function;
    }

    /**
     * Get Visible_On
     * 
     * @return \Docova\DocovaBundle\Entity\UserScriptFunction
     */
    public function getVisibleOn()
    {
    	return $this->Visible_On;
    }
    
    /**
     * Set Subform
     * 
     * @param \Docova\DocovaBundle\Entity\Subforms $subform
     */
    public function setSubform(\Docova\DocovaBundle\Entity\Subforms $subform)
    {
    	$this->Subform = $subform;
    }
    
    /**
     * Get Subform
     * 
     * @return \Docova\DocovaBundle\Entity\Subforms
     */
    public function getSubform()
    {
    	return $this->Subform;
    }

    /**
     * Set primaryIcon
     *
     * @param string $primaryIcon
     * @return SubformActionButtons
     */
    public function setPrimaryIcon($primaryIcon)
    {
        $this->primaryIcon = $primaryIcon;
    
        return $this;
    }

    /**
     * Get primaryIcon
     *
     * @return string 
     */
    public function getPrimaryIcon()
    {
        return $this->primaryIcon;
    }

    /**
     * Set secondaryIcon
     *
     * @param string $secondaryIcon
     * @return SubformActionButtons
     */
    public function setSecondaryIcon($secondaryIcon)
    {
        $this->secondaryIcon = $secondaryIcon;
    
        return $this;
    }

    /**
     * Get secondaryIcon
     *
     * @return string 
     */
    public function getSecondaryIcon()
    {
        return $this->secondaryIcon;
    }
}