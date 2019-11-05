<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FieldSummary
 *
 * @ORM\Table(name="tb_field_summary",indexes={
 *   @ORM\Index(name="idxFieldSummary_Library", columns={"library_id"}),
 *   @ORM\Index(name="idxFieldSummary_Folder", columns={"folder_id"}),
 *   @ORM\Index(name="idxFieldSummary_DocumentType", columns={"doc_type_id"}),
 *   @ORM\Index(name="idxFieldSummary_Document", columns={"doc_id"}),
 *   @ORM\Index(name="idxFieldSummary_Field", columns={"Field_Id"}),
 *   @ORM\Index(name="idxFieldSummary_Modified", columns={"Date_Modified"}),
 *   @ORM\Index(name="idxFieldSummary_FieldSummary", columns={"Field_Summary"}),
 *   @ORM\Index(name="idxFieldSummary_FieldValue", columns={"Field_Id", "Field_Summary"})
 * })
 * @ORM\Entity
 */
class FieldSummary
{
	/**
	 * @ORM\Column(name="id", type="guid")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="UUID")
	 */
	private $id;

	/**
	 * @var string
	 * 
	 * @ORM\Column(name="library_id", type="guid")
	 */
	private $library;

	/**
	 * @var string
	 * 
	 * @ORM\Column(name="folder_id", type="guid")
	 */
	private $folder;

	/**
	 * @var string
	 * 
	 * @ORM\Column(name="doc_id", type="guid")
	 */
	private $document;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="doc_type_id", type="guid")
	 */
	private $doctype;

	/**
	 * @var string
	 * 
	 * @ORM\Column(name="Field_Id", type="guid")
	 */
	private $field;

	/**
	 * @var \DateTime
	 * 
	 * @ORM\Column(name="Date_Modified", type="datetime", nullable=true)
	 */
	private $modifyDate;

	/**
	 * @var string
	 * 
	 * @ORM\Column(name="Field_Summary", type="string", length=255, nullable=true)
	 */
	private $summary;
	
	/**
	 * Set library
	 * 
	 * @param string $library
	 * @return FieldSummary
	 */
	public function setLibrary($library)
	{
		$this->library = $library;
		
		return $this;
	}
	
	/**
	 * Get library
	 * 
	 * @return string
	 */
	public function getLibrary()
	{
		return $this->library;
	}
	
	/**
	 * Set folder
	 * 
	 * @param string $folder
	 * @return FieldSummary
	 */
	public function setFolder($folder)
	{
		$this->folder = $folder;
		
		return $this;
	}
	
	/**
	 * Get folder
	 * 
	 * @return string
	 */
	public function getFolder()
	{
		return $this->folder;
	}
	
	/**
	 * Set document
	 * 
	 * @param string $document
	 * @return FieldSummary
	 */
	public function setDocument($document)
	{
		$this->document = $document;
		
		return $this;
	}
	
	/**
	 * Get document
	 * 
	 * @return string
	 */
	public function getDocument()
	{
		return $this->document;
	}
	
	/**
	 * Set doctype
	 * 
	 * @param string $doctype
	 * @return FieldSummary
	 */
	public function setDoctype($doctype)
	{
		$this->doctype = $doctype;
		
		return $this;
	}
	
	/**
	 * Get doctype
	 * 
	 * @return string
	 */
	public function getDoctype()
	{
		return $this->doctype;
	}
	
	/**
	 * Set field
	 * 
	 * @param string $field
	 * @return FieldSummary
	 */
	public function setField($field)
	{
		$this->field = $field;
		
		return $this;
	}
	
	/**
	 * Get field
	 * 
	 * @return string
	 */
	public function getField()
	{
		return $this->field;
	}
	
	/**
	 * Set modifyDate
	 * 
	 * @param \DateTime $modifyDate
	 * @return FieldSummary
	 */
	public function setModifyDate($modifyDate)
	{
		$this->modifyDate = $modifyDate;
		
		return $this;
	}
	
	/**
	 * Get modifyDate
	 * 
	 * @return \DateTime
	 */
	public function getModifyDate()
	{
		return $this->modifyDate;
	}
	
	/**
	 * Set summary
	 * 
	 * @param string $summary
	 * @return FieldSummary
	 */
	public function setSummary($summary)
	{
		$this->summary = $summary;
		
		return $this;
	}
	
	/**
	 * Get summary
	 * 
	 * @return string
	 */
	public function getSummary()
	{
		return $this->summary;
	}
}