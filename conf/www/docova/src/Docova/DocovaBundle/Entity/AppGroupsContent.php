<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * AppGroupsContent
 *
 * @ORM\Table(name="tb_app_groups_content", uniqueConstraints={
 * 		@ORM\UniqueConstraint(name="Unique_User_AppGroups", columns={"App_Group", "Application", "Library_Group"})})
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AppGroupsContentRepository")
 * @UniqueEntity(fields={"appGroup", "application", "libraryGroup"})
 */
class AppGroupsContent
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;
	
	/**
     * @ORM\ManyToOne(targetEntity="UserAppGroups", inversedBy="appsList")
     * @ORM\JoinColumn(name="App_Group", referencedColumnName="id", nullable=false)
     */
    protected $appGroup;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="Application", referencedColumnName="id", nullable=true)
     */
    protected $application;

    /**
     * @ORM\ManyToOne(targetEntity="LibraryGroups")
     * @ORM\JoinColumn(name="Library_Group", referencedColumnName="id", nullable=true)
     */
    protected $libraryGroup;


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
     * Set appGroup
     *
     * @param \stdClass $appGroup
     *
     * @return AppGroupsContent
     */
    public function setAppGroup($appGroup)
    {
        $this->appGroup = $appGroup;

        return $this;
    }

    /**
     * Get appGroup
     *
     * @return \stdClass
     */
    public function getAppGroup()
    {
        return $this->appGroup;
    }

    /**
     * Set application
     *
     * @param \stdClass $application
     *
     * @return AppGroupsContent
     */
    public function setApplication($application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Get application
     *
     * @return \stdClass
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Set libraryGroup
     *
     * @param \stdClass $libraryGroup
     *
     * @return AppGroupsContent
     */
    public function setLibraryGroup($libraryGroup)
    {
        $this->libraryGroup = $libraryGroup;

        return $this;
    }

    /**
     * Get libraryGroup
     *
     * @return \stdClass
     */
    public function getLibraryGroup()
    {
        return $this->libraryGroup;
    }
}

