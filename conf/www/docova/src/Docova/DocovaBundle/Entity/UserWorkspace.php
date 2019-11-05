<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserWorkspace
 *
 * @ORM\Table(name="tb_user_workspace")
 * @ORM\Entity
 */
class UserWorkspace
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
     * @ORM\Column(name="Default_Open_App", type="string", length=255, nullable=true)
     */
    protected $defaultOpenApp;

    /**
     * @var string
     *
     * @ORM\Column(name="Pinned_Tabs", type="string", length=1024, nullable=true)
     */
    protected $pinnedTabs;

    /**
     * @var string
     *
     * @ORM\Column(name="Workspace_HTML", type="text")
     */
    protected $workspaceHTML;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="User_Id", referencedColumnName="id", nullable=false)
     */
    protected $user;


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
     * Set defaultOpenApp
     *
     * @param string $defaultOpenApp
     * @return UserWorkspace
     */
    public function setDefaultOpenApp($defaultOpenApp)
    {
        $this->defaultOpenApp = $defaultOpenApp;

        return $this;
    }

    /**
     * Get defaultOpenApp
     *
     * @return string 
     */
    public function getDefaultOpenApp()
    {
        return $this->defaultOpenApp;
    }

    /**
     * Set pinnedTabs
     *
     * @param string $pinnedTabs
     * @return UserWorkspace
     */
    public function setPinnedTabs($pinnedTabs)
    {
        $this->pinnedTabs = $pinnedTabs;

        return $this;
    }

    /**
     * Get pinnedTabs
     *
     * @return string 
     */
    public function getPinnedTabs()
    {
        return $this->pinnedTabs;
    }

    /**
     * Set workspaceHTML
     *
     * @param string $workspaceHTML
     * @return UserWorkspace
     */
    public function setWorkspaceHTML($workspaceHTML)
    {
        $this->workspaceHTML = $workspaceHTML;

        return $this;
    }

    /**
     * Get workspaceHTML
     *
     * @return string 
     */
    public function getWorkspaceHTML()
    {
        return $this->workspaceHTML;
    }

    /**
     * Set user
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     * @return UserWorkspace
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
}
