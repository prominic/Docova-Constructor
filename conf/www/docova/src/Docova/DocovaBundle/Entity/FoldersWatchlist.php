<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * FoldersWatchlist
 *
 * @ORM\Table(name="tb_folders_watchlist", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="unique_idxes_folders", columns={"Owner", "Folder_Id"})})
 * @ORM\Entity
 * @UniqueEntity(fields={"Owner", "Foldres"})
 */
class FoldersWatchlist
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
     * @ORM\Column(name="Watchlist_Name", type="string", length=255)
     */
    protected $Watchlist_Name;

    /**
     * @var integer
     *
     * @ORM\Column(name="Watchlist_Type", type="smallint")
     */
    protected $Watchlist_Type;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=255)
     */
    protected $Description;

    /**
     * @var integer
     *
     * @ORM\Column(name="Availability", type="smallint")
     */
    protected $Availability;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Modified", type="datetime", nullable=true)
     */
    protected $Date_Modified;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Owner", referencedColumnName="id", nullable=false)
     */
    protected $Owner;

    /**
     * @ORM\ManyToOne(targetEntity="Folders", inversedBy="Favorites")
     * @ORM\JoinColumn(name="Folder_Id", referencedColumnName="id", nullable=false)
     */
    protected $Folders = null;


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
     * Set Watchlist_Name
     *
     * @param string $watchlistName
     * @return FoldersWatchlist
     */
    public function setWatchlistName($watchlistName)
    {
        $this->Watchlist_Name = $watchlistName;
    
        return $this;
    }

    /**
     * Get Watchlist_Name
     *
     * @return string 
     */
    public function getWatchlistName()
    {
        return $this->Watchlist_Name;
    }

    /**
     * Set Watchlist_Type
     *
     * @param integer $watchlistType
     * @return FoldersWatchlist
     */
    public function setWatchlistType($watchlistType)
    {
        $this->Watchlist_Type = $watchlistType;
    
        return $this;
    }

    /**
     * Get Watchlist_Type
     *
     * @return integer 
     */
    public function getWatchlistType()
    {
        return $this->Watchlist_Type;
    }

    /**
     * Set Description
     *
     * @param string $description
     * @return FoldersWatchlist
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
     * Set Availability
     *
     * @param integer $availability
     * @return FoldersWatchlist
     */
    public function setAvailability($availability)
    {
        $this->Availability = $availability;
    
        return $this;
    }

    /**
     * Get Availability
     *
     * @return integer 
     */
    public function getAvailability()
    {
        return $this->Availability;
    }

    /**
     * Set Date_Modified
     *
     * @param \DateTime $dateModified
     * @return FoldersWatchlist
     */
    public function setDateModified($dateModified)
    {
        $this->Date_Modified = $dateModified;
    
        return $this;
    }

    /**
     * Get Date_Modified
     *
     * @return \DateTime 
     */
    public function getDateModified()
    {
        return $this->Date_Modified;
    }

    /**
     * Set Owner
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     * @return FoldersWatchlist
     */
    public function setOwner(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->Owner = $user;
    	 
    	return $this;
    }
    
    /**
     * Get Owner
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getOwner()
    {
    	return $this->Owner;
    }

    /**
     * Set Folders
     * 
     * @param \Docova\DocovaBundle\Entity\Folders $folder
     * @return FoldersWatchlist
     */
    public function setFolders(\Docova\DocovaBundle\Entity\Folders $folder = null)
    {
    	$this->Folders = $folder;
    	
    	return $this;
    }

    /**
     * Get Folders
     * 
     * @return \Docova\DocovaBundle\Entity\Folders|null
     */
    public function getFolders()
    {
    	return $this->Folders;
    }
}
