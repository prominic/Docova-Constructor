<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DataSources
 *
 * @ORM\Table(name="tb_data_sources")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DataSourcesRepository")
 */
class DataSources
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
     * @ORM\Column(name="Name", type="string", length=100)
     */
    protected $Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Connection_Name", type="string", length=100, nullable=true)
     */
    protected $Connection_Name;
       
    
    /**
     * @var string
     *
     * @ORM\Column(name="Type", type="string", length=20)
     */
    protected $Type;
    
    /**
     * @var string
     *
     * @ORM\Column(name="File_Resource_Name", type="string", length=100, nullable=true)
     */
    protected $File_Resource_Name;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Subject_Column", type="string", length=1)
     */
    protected $Subject_Column;
    
    
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $Date_Created;
    
    
    //Define CSV Fields
    /**
     * @var string
     *
     * @ORM\Column(name="CSV_Delimiter", type="string", length=1, nullable=true)
     */
    protected $CSV_Delimiter;
    
    /**
     * @var string
     *
     * @ORM\Column(name="CSV_Offset_Lines", type="string", length=1, nullable=true)
     */
    protected $CSV_Offset_Lines;
    
    /**
     * @var string
     *
     * @ORM\Column(name="CSV_Offset_Chars", type="string", length=2, nullable=true)
     */
    protected $CSV_Offset_Chars;
        
    //Define SQL Fields
    /**
     * @var string
     *
     * @ORM\Column(name="SQL_Type", type="string", length=100, nullable=true)
     */
    protected $SQL_Type;
    
    /**
     * @var string
     *
     * @ORM\Column(name="SQL_String", type="text", nullable=true)
     */
    protected $SQL;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Docova_Documents_Only", type="boolean", nullable=true)
     */
    protected $Docova_Documents_Only;
    
    /**
     * @var string
     *
     * @ORM\Column(name="SQL_Table_Name", type="string", length=100, nullable=true)
     */
    protected $SQL_Table_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="SQL_Order_By", type="string", length=100, nullable=true)
     */
    protected $SQL_Order_By;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="SQL_Key_Name", type="string", length=100, nullable=true)
     */
    protected $SQL_Key_Name;
    
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
     * Get Date_Created
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->Date_Created;
    }
    /**
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return FileResources
     */
    public function setDateCreated($dateCreated)
    {
    	$this->Date_Created = $dateCreated;
    
    	return $this;
    }
	public function getName() {
		return $this->Name;
	}
	public function setName($Name) {
		$this->Name = $Name;
		return $this;
	}
	public function getConnectionName() {
		return $this->Connection_Name;
	}
	public function setConnectionName($Connection) {
		$this->Connection_Name = $Connection;
		return $this;
	}
	public function getType() {
		return $this->Type;
	}
	public function setType($Type) {
		$this->Type = $Type;
		return $this;
	}
	public function getFileResourceName() {
		return $this->File_Resource_Name;
	}
	public function setFileResourceName($Name) {
		$this->File_Resource_Name = $Name;
		return $this;
	}
	public function getSubjectColumn() {
		return $this->Subject_Column;
	}
	public function setSubjectColumn($Column) {
		$this->Subject_Column = $Column;
		return $this;
	}
	public function getCSVDelimiter() {
		return $this->CSV_Delimiter;
	}
	public function setCSVDelimiter($delim) {
		$this->CSV_Delimiter = $delim;
		return $this;
	}
	public function getCSVOffsetLines() {
		return $this->CSV_Offset_Lines;
		
	}
	public function setCSVOffsetLines($offset) {
		$this->CSV_Offset_Lines = $offset;
		return $this;
	}
	public function getCSVOffsetChars() {
		return $this->CSV_Offset_Chars;
	
	}
	public function setCSVOffsetChars($offset) {
		$this->CSV_Offset_Chars = $offset;
		return $this;
	}
	public function getSQLTableName() {
		return $this->SQL_Table_Name;
	}
	public function setSQLTableName($tableName) {
		$this->SQL_Table_Name = $tableName;
		return $this;
	}
	public function getSQLType() {
		return $this->SQL_Type;
	}
	public function setSQLType($type) {
		$this->SQL_Type = $type;
		return $this;
	}
	public function getSQL() {
		return $this->SQL;
	}
	public function setSQL($sql) {
		$this->SQL = $sql;
		return $this;
	}
	public function getSQLOrderBy() {
		return $this->SQL_Order_By;
	}
	public function setSQLOrderBy($orderBy) {
		$this->SQL_Order_By = $orderBy;
		return $this;
	}
	public function getSQLKeyName() {
		return $this->SQL_Key_Name;
	}
	public function setSQLKeyName($keyName) {
		$this->SQL_Key_Name = $keyName;
		return $this;
	}
	public function getFileResource($em)
	{
		return $em->getRepository("DocovaBundle:FileResources")->getFileResource($this->getFileResourceName());
	}
	public function getFileResourcePath($em,$root)
	{
		return $em->getRepository("DocovaBundle:FileResources")->getFileResourcePath($root,$this->getFileResourceName());
	}
	public function getFileResourceContent($em,$root)
	{
		return $em->getRepository("DocovaBundle:FileResources")->getFileResourceContent($root,$this->getFileResourceName());
	}
	public function getDocovaDocumentsOnly() {
		return $this->Docova_Documents_Only;
	}
	public function setDocovaDocumentsOnly($Docova_Documents_Only) {
		$this->Docova_Documents_Only = $Docova_Documents_Only;
		return $this;
	}
}
