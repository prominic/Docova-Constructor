<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CustomSearchFields
 *
 * @ORM\Table(name="tb_custom_search_fields")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\CustomSearchFieldsRepository")
 */
class CustomSearchFields
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
	 * @ORM\Column(name="Field_Description", type="string", length=255,  nullable=true)
	 */
	protected $fieldDescription;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="Field_Name", type="string", length=255, nullable=true)
	 */
	protected $fieldName;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="Data_Type", type="string", length=15,  options={"default":"text"})
	 */
	protected $dataType='text';
	
	
	/**
	 * @var ArrayCollection
	 *
	 * @ORM\ManyToMany(targetEntity="DocumentTypes")
	 * @ORM\JoinTable(name="tb_custom_search_fields_document_types",
	 *      joinColumns={@ORM\JoinColumn(name="Custom_Search_Field_Id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="Doc_Type_Id", referencedColumnName="id")})
	 */
	protected $documentTypes;
	
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="Text_Entry_Type", type="string", length=25, options={"default":"E"})
	 */
	protected $textEntryType = 'E';
	
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="Select_Values", type="string", length=255, nullable=true)
	 */
	protected $selectValues;
	
	/**
	 * @var string
	 * 
	 * @ORM\Column(name="Is_Custom", type="string", length=1024, nullable=true)
	 */
	protected $customField;
	
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
	 * Add documentTypes
	 *
	 * @param \Docova\DocovaBundle\Entity\DocumentTypes $doctype
	 */
	public function addDocumentTypes(\Docova\DocovaBundle\Entity\DocumentTypes $doctype)
	{
		$this->documentTypes[] = $doctype;
	}
	
	/**
	 * Get documentTypes
	 *
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getDocumentTypes()
	{
		return $this->documentTypes;
	}
	
	/**
	 * Remove documentTypes
	 *
	 * @param \Docova\DocovaBundle\Entity\DocumentTypes $doctype
	 */
	public function removeDocumentTypes(\Docova\DocovaBundle\Entity\DocumentTypes $doctype)
	{
		$this->documentTypes->removeElement($doctype);
	}
	
	/**
	 * Remove all documentTypes
	 */
	public function removeAllDocumentTypes()
	{
		$this->documentTypes->clear();
	}
	
	/**
	 *
	 * @return string
	 */
	public function getFieldDescription() {
		return $this->fieldDescription;
	}
	
	/**
	 *
	 * @param string $fieldDescription        	
	 */
	public function setFieldDescription( $fieldDescription) {
		$this->fieldDescription = $fieldDescription;
		return $this;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getFieldName() {
		return $this->fieldName;
	}
	
	/**
	 *
	 * @param string $field_Name        	
	 */
	public function setFieldName( $field_Name) {
		$this->fieldName = $field_Name;
		return $this;
	}
	

	/**
	 *
	 * @return string
	 */
	public function getTextEntryType() {
		return $this->textEntryType;
	}
	
	/**
	 *
	 * @param string $textEntryType        	
	 */
	public function setTextEntryType( $textEntryType) {
		$this->textEntryType = $textEntryType;
		return $this;
	}
	
	/**
	 * Get selectValues
	 * 
	 * @return string
	 */
	public function getSelectValues() 
	{
		return $this->selectValues;
	}
	
	/**
	 * Set selectValues
	 * 
	 * @param string $selectValues
	 * @return CustomSearchFields        	
	 */
	public function setSelectValues($selectValues) 
	{
		$this->selectValues = $selectValues;
		
		return $this;
	}
	
	/**
	 * Get dataType
	 * @return string
	 */
	public function getDataType() 
	{
		return $this->dataType;
	}
	
	/**
	 * Set dataType
	 * 
	 * @param string $dataType
	 * @return CustomSearchFields
	 */
	public function setDataType($dataType) 
	{
		$this->dataType = $dataType;
		
		return $this;
	}

	/**
	 * Set customField
	 * 
	 * @param string $customField
	 * @return CustomSearchFields
	 */
	public function setCustomField($customField = null) 
	{
		$this->customField = $customField;
		
		return $this;
	}

	/**
	 * Get customField
	 * 
	 * @return string
	 */
	public function getCustomField() 
	{
		return $this->customField;
	}
}