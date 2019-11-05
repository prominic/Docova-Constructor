<?php
namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocTypeSubforms
 * 
 * @ORM\Table(name="tb_doc_type_subforms")
 * @ORM\Entity
 */
class DocTypeSubforms
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;
	
	/**
	 * @ORM\ManyToOne(targetEntity="Subforms", inversedBy="DocType")
     * @ORM\JoinColumn(name="Subform_Id", referencedColumnName="id", nullable=false)
	 */
	protected $Subform;

	/**
	 * @ORM\ManyToOne(targetEntity="DocumentTypes", inversedBy="DocTypeSubform")
	 * @ORM\JoinColumn(name="Doc_Type_Id", referencedColumnName="id", nullable=false)
	 */
	protected $DocType;

	/**
	 * @var integer
	 * 
	 * @ORM\Column(name="Subform_Order", type="smallint")
	 */
	protected $Subform_Order;

	/**
	 * @var string
	 * 
	 * @ORM\Column(name="Properties_XML", type="text", nullable=true)
	 */
	protected $Properties_XML;


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
	 * Set DocType
	 * 
	 * @param \Docova\DocovaBundle\Entity\DocumentTypes $doc_type
	 */
	public function setDocType(\Docova\DocovaBundle\Entity\DocumentTypes $doc_type)
	{
		$this->DocType = $doc_type;
	}

	/**
	 * Get DocType
	 * 
	 * @return \Docova\DocovaBundle\Entity\DocumentTypes
	 */
	public function getDocType()
	{
		return $this->DocType;
	}
	
	/**
	 * Set Subform_Order
	 * 
	 * @param integer $subformOrder
	 * @return DocTypeSubforms
	 */
	public function setSubformOrder($subformOrder)
	{
		$this->Subform_Order = $subformOrder;
		
		return $this;
	}
	
	/**
	 * Get Subform_Order
	 * 
	 * @return integer
	 */
	public function getSubformOrder()
	{
		return $this->Subform_Order;
	}

	/**
	 * Set Properties_XML
	 * 
	 * @param string|\DomDocument $properties
	 * @return DocTypeSubforms
	 */
	public function setPropertiesXML($properties)
	{
		if ($properties instanceof \DOMDocument)
		{
			$properties = $properties->saveXML();
		}
		
		$this->Properties_XML = $properties;
		
		return $this;
	}

	/**
	 * Get Properties_XML
	 * 
	 * @param boolean $return_array
	 * @return string|array
	 */
	public function getPropertiesXML($return_array = false)
	{
		if ($return_array === false) 
		{
			return $this->Properties_XML;
		}
		elseif (!empty($this->Properties_XML)) 
		{
			$dom_obj = new \DOMDocument('1.0', 'UTF-8');
			$dom_obj->loadXML($this->Properties_XML);
			$output = array();
			$root = $dom_obj->getElementsByTagName('Properties')->item(0);
			$output = $this->convertToArray($root);
			
			return $output;
		}

		return $this->Properties_XML;
	}
	
	/**
	 * Conver XML to array
	 * 
	 * @param \DOMNode $node
	 * @return array  
	 */
	private function convertToArray(\DOMNode $node)
	{
		$output = array();
		$children = $node->childNodes;
		foreach ($children as $item)
		{
			if ($item->hasChildNodes()) {
				if ($item->childNodes->length == 1 && $item->childNodes->item(0)->nodeType != XML_ELEMENT_NODE) {
					$output[$item->nodeName] = $item->childNodes->item(0)->nodeValue;
				}
				else {
					$output[$item->nodeName] = $this->convertToArray($item);
				}
			}
			else {
				$output[$item->nodeName] = $item->nodeValue;
			}
		}
		
		return $output;
	}
}