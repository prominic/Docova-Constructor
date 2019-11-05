<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FacetMaps
 *
 * @ORM\Table(name="tb_facet_maps")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\FacetMapsRepository")
 */
class FacetMaps
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
     * @ORM\Column(name="Facet_Map_Name", type="string", length=255)
     */
    protected $Facet_Map_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255, nullable=true)
     */
    protected $Description;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="ViewColumns")
     * @ORM\JoinTable(name="tb_facet_map_columns",
     *      joinColumns={@ORM\JoinColumn(name="Facet_Map_Id", referencedColumnName="id", nullable=false)},
     *      inverseJoinColumns={@ORM\JoinColumn(name="View_Column_Id", referencedColumnName="id", nullable=false)})
     */
    protected $Fields;

    /**
     * @var string
     *
     * @ORM\Column(name="Default_Facet", type="string", length=50, nullable=true)
     */
    protected $Default_Facet;

    /**
     * @var string
     *
     * @ORM\Column(name="FacetMap_Fields", type="string", length=255, nullable=true)
     */
    protected $FacetMap_Fields;
    
    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="Library_Id", referencedColumnName="id", nullable=true)
     */
    protected $Library = null;


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
     * Set Facet_Map_Name
     *
     * @param string $facetMapName
     * @return FacetMaps
     */
    public function setFacetMapName($facetMapName)
    {
        $this->Facet_Map_Name = $facetMapName;
    
        return $this;
    }

    /**
     * Get Facet_Map_Name
     *
     * @return string 
     */
    public function getFacetMapName()
    {
        return $this->Facet_Map_Name;
    }

    /**
     * Set Description
     *
     * @param string $description
     * @return FacetMaps
     */
    public function setDescription($description)
    {
        $this->Description = $description;
    
        return $this;
    }

    /**
     * Get Description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * Add Fields
     * 
     * @param \Docova\DocovaBundle\Entity\ViewColumns $column
     */
    public function addFields(\Docova\DocovaBundle\Entity\ViewColumns $column)
    {
    	$this->Fields[] = $column;
    }

    /**
     * Get Fields
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getFields()
    {
    	return $this->Fields;
    }

    /**
     * Remove Fields
     * 
     * @param \Docova\DocovaBundle\Entity\ViewColumns $column
     */
    public function remvoveFields(\Docova\DocovaBundle\Entity\ViewColumns $column)
    {
    	$this->Fields->removeElement($column);
    }

    /**
     * Set Default_Facet
     *
     * @param string $defaultFacet
     * @return FacetMaps
     */
    public function setDefaultFacet($defaultFacet)
    {
        $this->Default_Facet = $defaultFacet;
    
        return $this;
    }

    /**
     * Get Default_Facet
     *
     * @return string 
     */
    public function getDefaultFacet()
    {
        return $this->Default_Facet;
    }

    /**
     * Set FacetMap_Fields
     *
     * @param string $facedmap_field
     * @return FacetMaps
     */
    public function setFacetMapFields($facedmap_field)
    {
        $this->FacetMap_Fields = $facedmap_field;
    
        return $this;
    }

    /**
     * Get FacetMap_Fields
     *
     * @return string 
     */
    public function getFacetMapFields()
    {
        return $this->FacetMap_Fields;
    }

    /**
     * Set Library
     * 
     * @param \Docova\DocovaBundle\Entity\Libraries $library
     */
    public function setLibrary(\Docova\DocovaBundle\Entity\Libraries $library)
    {
    	$this->Library = $library;
    }

    /**
     * Get Library
     * 
     * @return \Docova\DocovaBundle\Entity\Libraries
     */
    public function getLibrary()
    {
    	return $this->Library;
    }
}
