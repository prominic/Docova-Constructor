<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DesignElements
 *
 * @ORM\Table(name="tb_design_elements")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DesignElementsRepository")
 */
class DesignElements
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Field_Name", type="string", length=255)
     */
    protected $fieldName;

    /**
     * @var integer
     *
     * @ORM\Column(name="Field_Type", type="smallint")
     */
    protected $fieldType;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Name_Type", type="smallint", nullable=true)
     */
    protected $nameFieldType;

    /**
     * @var string
     * 
     * @ORM\Column(name="Multi_Value_Separator", type="string", length=15, nullable=true)
     */
    protected $multiSeparator;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="Date_Modified", type="datetime")
     */
    protected $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Modified_By", referencedColumnName="id", nullable=false)
     */
    protected $modifiedBy;

    /**
     * @var string
     *
     * @ORM\Column(name="Select_Query", type="text", nullable=true)
     */
    protected $selectQuery;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Is_Extra", type="boolean", nullable=false, options={"default":false})
     */
    protected $isExtra = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean", options={"default":false})
     */
    protected $trash = false;

    /**
     * @ORM\ManyToOne(targetEntity="AppForms", inversedBy="elements")
     * @ORM\JoinColumn(name="Form_Id", referencedColumnName="id", nullable=true)
     */
    protected $form;

    /**
     * @ORM\ManyToOne(targetEntity="Subforms", inversedBy="SubformFields")
     * @ORM\JoinColumn(name="Subform_Id", referencedColumnName="id", nullable=true)
     */
    protected $Subform;
    
    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="profileFields")
     * @ORM\JoinColumn(name="Profile_Document_Id", referencedColumnName="id", nullable=true)
     */
    protected $profileDocument;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ListFieldOptions", mappedBy="Field")
     * @ORM\OrderBy({"Option_Order" = "ASC"})
     */
    protected $Options;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Options = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set fieldName
     *
     * @param string $fieldName
     * @return DesignElements
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = strtolower($fieldName);

        return $this;
    }

    /**
     * Get fieldName
     *
     * @return string 
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set fieldType
     * 
     * 0 => text
     * 1 => date/time
     * 2 => list/options
     * 3 => names/authors/readers
     * 4 => number
     * 5 => attachment
     *
     * @param integer $fieldType
     * @return DesignElements
     */
    public function setFieldType($fieldType)
    {
    	if (!is_numeric($fieldType))
    	{
	    	switch ($fieldType)
	    	{
	    		case 'text':
	    			$fieldType = 0;
	    			break;
	    		case 'date':
	    		case 'datetime':
	    			$fieldType = 1;
	    			break;
	    		case 'radio':
	    		case 'checkbox':
	    		case 'select':
	    			$fieldType = 2;
	    			break;
	    		case 'name':
	    		case 'names':
	    		case 'author':
	    		case 'reader':
				case 'authors':
				case 'readers':
	    			$fieldType = 3;
	    			break;
	    		case 'number':
	    			$fieldType = 4;
	    			break;
	    		case 'attachment':
	    			$fieldType = 5;
	    			break;
	    		default:
	    			$fieldType = 0;
	    			break;
	    	}
    	}
        $this->fieldType = $fieldType;

        return $this;
    }

    /**
     * Get fieldType
     *
     * @return integer 
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * Set nameFieldType
     * 
     * @param number $type
     * @return DesignElements
     */
    public function setNameFieldType($type = null)
    {
    	$this->nameFieldType = $type;
    	
    	return $this;
    }
    
    /**
     * Get nameFieldType
     * 
     * 2 => reader
     * 3 => author
     * 
     * @return number
     */
    public function getNameFieldType()
    {
    	return $this->nameFieldType;
    }

    /**
     * Set trash
     *
     * @param boolean $trash
     * @return DesignElements
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
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return DesignElements
     */
    public function setDateModified($dateModified)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified
     *
     * @return \DateTime 
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Set modifiedBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $modifiedBy
     * @return DesignElements
     */
    public function setModifiedBy(\Docova\DocovaBundle\Entity\UserAccounts $modifiedBy = null)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set form
     *
     * @param \Docova\DocovaBundle\Entity\AppForms $form
     * @return DesignElements
     */
    public function setForm(\Docova\DocovaBundle\Entity\AppForms $form = null)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Get form
     *
     * @return \Docova\DocovaBundle\Entity\AppForms 
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Set selectQuery
     *
     * @param string $selectQuery
     * @return DesignElements
     */
    public function setSelectQuery($selectQuery)
    {
        $this->selectQuery = $selectQuery;

        return $this;
    }

    /**
     * Get selectQuery
     *
     * @return string 
     */
    public function getSelectQuery()
    {
        return $this->selectQuery;
    }

    /**
     * Set isExtra
     *
     * @param boolean $isExtra
     * @return DesignElements
     */
    public function setIsExtra($isExtra)
    {
        $this->isExtra = $isExtra;

        return $this;
    }

    /**
     * Get isExtra
     *
     * @return boolean 
     */
    public function getIsExtra()
    {
        return $this->isExtra;
    }

    /**
     * Set Subform
     *
     * @param \Docova\DocovaBundle\Entity\Subforms $subform
     * @return DesignElements
     */
    public function setSubform(\Docova\DocovaBundle\Entity\Subforms $subform = null)
    {
        $this->Subform = $subform;

        return $this;
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
     * Set profileDocument
     * 
     * @param \Docova\DocovaBundle\Entity\Documents|null $profileDoc
     * @return DesignElements
     */
    public function setProfileDocument(\Docova\DocovaBundle\Entity\Documents $profileDoc = null)
    {
    	$this->profileDocument = $profileDoc;
    	
    	return $this;
    }
    
    /**
     * Get profileDocument
     * 
     * @return \Docova\DocovaBundle\Entity\Documents
     */
    public function getProfileDocument()
    {
    	return $this->profileDocument;
    }

    /**
     * Add Options
     *
     * @param \Docova\DocovaBundle\Entity\ListFieldOptions $options
     * @return DesignElements
     */
    public function addOption(\Docova\DocovaBundle\Entity\ListFieldOptions $options)
    {
        $this->Options[] = $options;

        return $this;
    }

    /**
     * Remove Options
     *
     * @param \Docova\DocovaBundle\Entity\ListFieldOptions $options
     */
    public function removeOption(\Docova\DocovaBundle\Entity\ListFieldOptions $options)
    {
        $this->Options->removeElement($options);
    }

    /**
     * Get Options
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOptions()
    {
        return $this->Options;
    }

    /**
     * Set multiSeparator
     *
     * @param string $multiSeparator
     * @return DesignElements
     */
    public function setMultiSeparator($multiSeparator = null)
    {
        $this->multiSeparator = $multiSeparator;

        return $this;
    }

    /**
     * Get multiSeparator
     *
     * @return string 
     */
    public function getMultiSeparator()
    {
        return $this->multiSeparator;
    }
}
