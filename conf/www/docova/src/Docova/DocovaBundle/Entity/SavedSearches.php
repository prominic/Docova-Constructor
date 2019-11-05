<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * SavedSearches
 *
 * @ORM\Table(name="tb_saved_searched")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\SavedSearchesRepository")
 */
class SavedSearches
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
     * @ORM\Column(name="Search_Name", type="string", length=255)
     */
    protected $searchName;

    /**
     * @var string
     * 
     * @ORM\Column(name="Search_Query", type="text", nullable=true)
     */
    protected $searchQuery;

    /**
     * @var string
     *
     * @ORM\Column(name="Search_Criteria", type="text")
     */
    protected $searchCriteria;

    /**
     * @var boolean
     * 
     * @ORM\Column(name="Include_Unsubscribed", type="boolean")
     */
    protected $includeUnsbscribed = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Global_Search", type="boolean")
     */
    protected $globalSearch;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="User_Id", referencedColumnName="id", nullable=false)
     */
    protected $userSaved;

    /**
     * @var ArrayCollection
     * 
     * @ORM\ManyToMany(targetEntity="Libraries", inversedBy="savedSearches")
     * @ORM\JoinTable(name="tb_saved_searches_libraries",
     *      joinColumns={@ORM\JoinColumn(name="Search_Id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Library_Id", referencedColumnName="id")})
     */
    protected $libraries;


    public function __construct()
    {
    	$this->libraries = new ArrayCollection();
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
     * Set searchName
     *
     * @param string $searchName
     * @return SavedSearches
     */
    public function setSearchName($searchName)
    {
        $this->searchName = $searchName;
    
        return $this;
    }

    /**
     * Get searchName
     *
     * @return string 
     */
    public function getSearchName()
    {
        return $this->searchName;
    }

    /**
     * Set searchCriteria
     *
     * @param string $searchCriteria
     * @return SavedSearches
     */
    public function setSearchCriteria($searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
    
        return $this;
    }

    /**
     * Get searchCriteria
     *
     * @return string 
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * Set globalSearch
     *
     * @param boolean $globalSearch
     * @return SavedSearches
     */
    public function setGlobalSearch($globalSearch)
    {
        $this->globalSearch = $globalSearch;
    
        return $this;
    }

    /**
     * Get globalSearch
     *
     * @return boolean 
     */
    public function getGlobalSearch()
    {
        return $this->globalSearch;
    }

    /**
     * Set userSaved
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $userSaved
     * @return SavedSearches
     */
    public function setUserSaved(\Docova\DocovaBundle\Entity\UserAccounts $userSaved)
    {
        $this->userSaved = $userSaved;
    
        return $this;
    }

    /**
     * Get userSaved
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getUserSaved()
    {
        return $this->userSaved;
    }

    /**
     * Add libraries
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $libraries
     * @return SavedSearches
     */
    public function addLibrarie(\Docova\DocovaBundle\Entity\Libraries $libraries)
    {
        $this->libraries[] = $libraries;
    
        return $this;
    }

    /**
     * Remove libraries
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $libraries
     */
    public function removeLibrarie(\Docova\DocovaBundle\Entity\Libraries $libraries)
    {
        $this->libraries->removeElement($libraries);
    }

    /**
     * Get libraries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLibraries()
    {
        return $this->libraries;
    }

    /**
     * Set searchQuery
     *
     * @param string $searchQuery
     * @return SavedSearches
     */
    public function setSearchQuery($searchQuery)
    {
        $this->searchQuery = $searchQuery;
    
        return $this;
    }

    /**
     * Get searchQuery
     *
     * @return string 
     */
    public function getSearchQuery()
    {
        return $this->searchQuery;
    }

    /**
     * Set includeUnsbscribed
     *
     * @param boolean $includeUnsbscribed
     * @return SavedSearches
     */
    public function setIncludeUnsbscribed($includeUnsbscribed)
    {
        $this->includeUnsbscribed = $includeUnsbscribed;
    
        return $this;
    }

    /**
     * Get includeUnsbscribed
     *
     * @return boolean 
     */
    public function getIncludeUnsbscribed()
    {
        return $this->includeUnsbscribed;
    }
}