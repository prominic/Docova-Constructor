<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * UserRecentApps
 *
 * @ORM\Table(name="tb_user_recent_apps", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="Unique_Indexes", columns={"User_Id", "Application_Id", "LibGroup_Id"})})
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\UserRecentAppsRepository")
 * @UniqueEntity(fields={"user", "app", "libgroup"})
 */
class UserRecentApps
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Last_Open_Date", type="datetime")
     */
    protected $lastOpenDate;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="User_Id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Libraries")
     * @ORM\JoinColumn(name="Application_Id", referencedColumnName="id", nullable=true)
     */
    protected $app;

    /**
     * @ORM\ManyToOne(targetEntity="LibraryGroups")
     * @ORM\JoinColumn(name="LibGroup_Id", referencedColumnName="id", nullable=true)
     */
    protected $libgroup;


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
     * Set lastOpenDate
     *
     * @param \DateTime $lastOpenDate
     *
     * @return UserRecentApps
     */
    public function setLastOpenDate($lastOpenDate)
    {
        $this->lastOpenDate = $lastOpenDate;

        return $this;
    }

    /**
     * Get lastOpenDate
     *
     * @return \DateTime
     */
    public function getLastOpenDate()
    {
        return $this->lastOpenDate;
    }

    /**
     * Set user
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     *
     * @return UserRecentApps
     */
    public function setUser(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set app
     *
     * @param \Docova\DocovaBundle\Entity\Libraries $app
     *
     * @return UserRecentApps
     */
    public function setApp(\Docova\DocovaBundle\Entity\Libraries $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get app
     *
     * @return \Docova\DocovaBundle\Entity\Libraries
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Set libgroup
     *
     * @param \Docova\DocovaBundle\Entity\LibraryGroups $libgroup
     *
     * @return UserRecentApps
     */
    public function setLibgroup(\Docova\DocovaBundle\Entity\LibraryGroups $libgroup)
    {
        $this->libgroup = $libgroup;

        return $this;
    }

    /**
     * Get libgroup
     *
     * @return \Docova\DocovaBundle\Entity\LibraryGroups
     */
    public function getLibgroup()
    {
        return $this->libgroup;
    }
}
