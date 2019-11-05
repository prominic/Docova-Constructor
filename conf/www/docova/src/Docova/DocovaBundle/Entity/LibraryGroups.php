<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LibraryGroups
 *
 * @ORM\Table(name="tb_library_groups")
 * @ORM\Entity
 */
class LibraryGroups
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
     * @ORM\Column(name="Group_Title", type="string", length=255)
     */
    protected $groupTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="Group_Description", type="string", length=255)
     */
    protected $groupDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="Group_Icon", type="string", length=100)
     */
    protected $groupIcon;

    /**
     * @var string
     *
     * @ORM\Column(name="Group_Icon_Color", type="string", length=50)
     */
    protected $groupIconColor;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id")
     */
    protected $createdBy;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Libraries")
     * @ORM\JoinTable(name="tb_librarygroup_libraries",
     *      joinColumns={@ORM\JoinColumn(name="Group_Id", referencedColumnName="id", nullable=false)},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Library_Id", referencedColumnName="id", nullable=false)})
     */
    protected $libraries;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->libraries = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set groupTitle
     *
     * @param string $groupTitle
     * @return LibraryGroups
     */
    public function setGroupTitle($groupTitle)
    {
        $this->groupTitle = $groupTitle;

        return $this;
    }

    /**
     * Get groupTitle
     *
     * @return string 
     */
    public function getGroupTitle()
    {
        return $this->groupTitle;
    }

    /**
     * Set groupDescription
     *
     * @param string $groupDescription
     * @return LibraryGroups
     */
    public function setGroupDescription($groupDescription)
    {
        $this->groupDescription = $groupDescription;

        return $this;
    }

    /**
     * Get groupDescription
     *
     * @return string 
     */
    public function getGroupDescription()
    {
        return $this->groupDescription;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return LibraryGroups
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set groupIcon
     *
     * @param string $groupIcon
     * @return LibraryGroups
     */
    public function setGroupIcon($groupIcon)
    {
        $this->groupIcon = $groupIcon;

        return $this;
    }

    /**
     * Get groupIcon
     *
     * @return string 
     */
    public function getGroupIcon()
    {
        return $this->groupIcon;
    }

    /**
     * Set groupIconColor
     *
     * @param string $groupIconColor
     * @return LibraryGroups
     */
    public function setGroupIconColor($groupIconColor)
    {
        $this->groupIconColor = $groupIconColor;

        return $this;
    }

    /**
     * Get groupIconColor
     *
     * @return string 
     */
    public function getGroupIconColor()
    {
        return $this->groupIconColor;
    }

    /**
     * Set createdBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $createdBy
     * @return LibraryGroups
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Add libraries
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $libraries
     * @return LibraryGroups
     */
    public function addLibrary(\Docova\DocovaBundle\Entity\Libraries $libraries)
    {
        $this->libraries[] = $libraries;

        return $this;
    }

    /**
     * Remove libraries
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $libraries
     */
    public function removeLibrary(\Docova\DocovaBundle\Entity\Libraries $libraries)
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
}
