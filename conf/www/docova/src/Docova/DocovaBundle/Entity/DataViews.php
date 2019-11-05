<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DataViews
 *
 * @ORM\Table(name="tb_data_views")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DataViewsRepository")
 */
class DataViews
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
     * @ORM\Column(name="Folder_Id", type="string", length=36, nullable=true)
     */
    protected $Folder_Id;
    /**
     * @var string
     *
     * @ORM\Column(name="Folder_Link", type="string", length=255, nullable=false)
     */
    protected $Folder_Link;

    /**
     * @var string
     *
     * @ORM\Column(name="Data_Source_Name", type="string", length=100, nullable=false)
     */
    protected $Data_Source_Name;
    
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $Date_Created;

    /**
     * @var string
     *
     * @ORM\Column(name="SQL_Filter", type="text", nullable=true)
     */
    protected $SQL_Filter;

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
     * Set Name
     *
     * @param string $Name
     * @return DataViews
     */
    public function setName($Name)
    {
        $this->Name = $Name;
    
        return $this;
    }

    /**
     * Get File_Name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * Set Folder_Link
     *
     * @param string $Name
     * @return DataViews
     */
    public function setFolderLink($link)
    {
    	$this->Folder_Link = $link;
    
    	return $this;
    }
    
    /**
     * Get Folder_Link
     *
     * @return string
     */
    public function getFolderLink()
    {
    	return $this->Folder_Link;
    }
    /**
     * Set Folder_Id
     *
     * @param string $id
     * @return DataViews
     */
    public function setFolderId($id)
    {
    	$this->Folder_Id = $id;
    
    	return $this;
    }
    
    /**
     * Get Folder_Id
     *
     * @return string
     */
    public function getFolderId()
    {
    	return $this->Folder_Id;
    }
    /**
     * Set Data Source Name
     *
     * @param string $DataSourceName
     * @return DataViews
     */
    public function setDataSourceName($Name)
    {
    	$this->Data_Source_Name = $Name;
    
    	return $this;
    }
    
    /**
     * Get File_Name
     *
     * @return string
     */
    public function getDataSourceName()
    {
    	return $this->Data_Source_Name;
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

    /**
     * Get Date_Created
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->Date_Created;
    }
	
    public function getDataSource($em)
    {
    	return $em->getRepository("DocovaBundle:DataSources")->getDataSource($this->getDataSourceName());
    }
	public function getSQLFilter() {
		if (empty($this->SQL_Filter))
			return "";
		return $this->SQL_Filter;
	}
	public function setSQLFilter($SQL_Filter) {
		$this->SQL_Filter = $SQL_Filter;
		return $this;
	}
	
	
}
