<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PanelWidgets
 *
 * @ORM\Table(name="tb_userpanels_widgets")
 * @ORM\Entity
 */
class PanelWidgets
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
	 * @ORM\ManyToOne(targetEntity="UserPanels", inversedBy="panelWidgets")
	 * @ORM\JoinColumn(name="User_Panel_Id", referencedColumnName="id", onDelete="CASCADE")
	 */
	protected $panel;

	/**
	 * @ORM\ManyToOne(targetEntity="Widgets", inversedBy="panelWidgets")
	 * @ORM\JoinColumn(name="Widget_Id", referencedColumnName="id", onDelete="CASCADE")
	 */
	protected $widget;

	/**
     * @var integer
     *
     * @ORM\Column(name="Box_No", type="smallint")
     */
    protected $boxNo;


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
	 * Set panel
	 * 
	 * @param \Docova\DocovaBundle\Entity\UserPanels $panel        	
	 */
	public function setPanel(\Docova\DocovaBundle\Entity\UserPanels $panel) 
	{
		$this->panel = $panel;
	}

	/**
	 * Get panel
	 * 
	 * @return \Docova\DocovaBundle\Entity\UserPanels
	 */
	public function getPanel() 
	{
		return $this->panel;
	}

	/**
	 * Set widget
	 * 
	 * @param \Docova\DocovaBundle\Entity\Widgets $widget
	 */
	public function setWidget(\Docova\DocovaBundle\Entity\Widgets $widget) 
	{
		$this->widget = $widget;
	}

	/**
	 * Get widget
	 * 
	 * @return \Docova\DocovaBundle\Entity\Widgets
	 */
	public function getWidget() 
	{
		return $this->widget;
	}

    /**
     * Set boxNo
     *
     * @param integer $boxNo
     * @return PanelWidgets
     */
    public function setBoxNo($boxNo)
    {
        $this->boxNo = $boxNo;
    
        return $this;
    }

    /**
     * Get boxNo
     *
     * @return integer 
     */
    public function getBoxNo()
    {
        return $this->boxNo;
    }
}
