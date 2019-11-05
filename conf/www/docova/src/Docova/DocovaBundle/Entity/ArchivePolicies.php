<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ArchivePolicies
 *
 * @ORM\Table(name="tb_archive_policies")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\ArchivePoliciesRepository")
 */
class ArchivePolicies
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
	 * @ORM\Column(name="Policy_Name", type="string", length=255)
	 */
	protected $policyName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="Description", type="string", length=255, nullable=true)
	 */
	protected $description;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="Policy_Status", type="boolean", options={"default":true})
	 */
	protected $policyStatus = true;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="Policy_Priority", type="smallint", options={"default":2})
	 */
	protected $policyPriority = 2;

	/**
	 * @var ArrayCollection
	 *
	 * @ORM\ManyToMany(targetEntity="Libraries")
	 * @ORM\JoinTable(name="tb_archive_policies_libraries",
	 *      joinColumns={@ORM\JoinColumn(name="Archive_Policy_Id", referencedColumnName="id", onDelete="CASCADE")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="Library_Id", referencedColumnName="id", onDelete="CASCADE")})
	 */
	protected $libraries;

	/**
	 * @var ArrayCollection
	 *
	 * @ORM\ManyToMany(targetEntity="DocumentTypes")
	 * @ORM\JoinTable(name="tb_archive_policies_document_types",
	 *      joinColumns={@ORM\JoinColumn(name="Archive_Policy_Id", referencedColumnName="id", onDelete="CASCADE")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="Doc_Type_Id", referencedColumnName="id", onDelete="CASCADE")})
	 */
	protected $documentTypes;
	
	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="Enable_Date_Archive", type="boolean", options={"default":false})
	 */
	protected $enableDateArchive = true;
	
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="Archive_Date_Select", type="string", length=25, options={"default":"CreatedDate"})
	 */
	protected $archiveDateSelect = 'CreatedDate';
	

    /**
     * @var integer
     *
     * @ORM\Column(name="Archive_Delay", type="smallint", options={"default":365})
     */
    protected $archiveDelay = 365;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="Archive_Custom_Formula", type="text", nullable=true)
     */
    protected $archiveCustomFormula;

	
    /**
     * @var boolean
     *
     * @ORM\Column(name="Archive_Skip_Workflow", type="boolean", options={"default":false})
     */
    protected $archiveSkipWorkflow = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Archive_Skip_Drafts", type="boolean", options={"default":false})
     */
    protected $archive_Skip_Drafts = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="Archive_Skip_Versions", type="boolean", options={"default":false})
     */
    protected $archiveSkipVersions = false;
    

    /**
     * @var integer
     *
     * @ORM\Column(name="Version_Count", type="smallint", options={"default":5})
     */
    protected $versionCount = 5;
	
	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}
	
	
	/**
	 *
	 * @return string
	 */
	public function getPolicyName() {
		return $this->policyName;
	}
	
	/**
	 *
	 * @param
	 *        	$policyName
	 */
	public function setPolicyName($policyName) {
		$this->policyName = $policyName;
		return $this;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}
	
	/**
	 *
	 * @param
	 *        	$description
	 */
	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function getPolicyStatus() {
		return $this->policyStatus;
	}
	
	/**
	 *
	 * @param
	 *        	$policyStatus
	 */
	public function setPolicyStatus($policyStatus) {
		$this->policyStatus = $policyStatus;
		return $this;
	}
	
	/**
	 *
	 * @return integer
	 */
	public function getPolicyPriority() {
		return $this->policyPriority;
	}
	
	/**
	 *
	 * @param
	 *        	$policyPriority
	 */
	public function setPolicyPriority($policyPriority) {
		$this->policyPriority = $policyPriority;
		return $this;
	}
	
	/**
	 * Add libraries
	 *
	 * @param \Docova\DocovaBundle\Entity\Libraries $library
	 */
	public function addLibraries(\Docova\DocovaBundle\Entity\Libraries $library)
	{
		$this->libraries[] = $library;
	}
	
	/**
	 * Get libraries
	 *
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getLibraries()
	{
		return $this->libraries;
	}
	
	/**
	 * Remove libraries
	 *
	 * @param \Docova\DocovaBundle\Entity\Libraries $library
	 */
	public function removeLibraries(\Docova\DocovaBundle\Entity\Libraries $library)
	{
		$this->libraries->removeElement($library);
	}
	
	/**
	 * Add documentTypes
	 *
	 * @param \Docova\DocovaBundle\Entity\DocumentTypes $doctype
	 */
	public function addDocumentTypes(\Docova\DocovaBundle\Entity\DocumentTypes $doctype)
	{
		$this->documentTypes[] = $doctype;
	}
	
	/**
	 * Get documentTypes
	 *
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getDocumentTypes()
	{
		return $this->documentTypes;
	}
	
	/**
	 * Remove documentTypes
	 *
	 * @param \Docova\DocovaBundle\Entity\DocumentTypes $doctype
	 */
	public function removeDocumentTypes(\Docova\DocovaBundle\Entity\DocumentTypes $doctype)
	{
		$this->documentTypes->removeElement($doctype);
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function getEnableDateArchive() {
		return $this->enableDateArchive;
	}
	
	/**
	 *
	 * @param
	 *        	$enableDateArchive
	 */
	public function setEnableDateArchive($enableDateArchive) {
		$this->enableDateArchive = $enableDateArchive;
		return $this;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getArchiveDateSelect() {
		return $this->archiveDateSelect;
	}
	
	/**
	 *
	 * @param
	 *        	$archiveDateSelect
	 */
	public function setArchiveDateSelect($archiveDateSelect) {
		$this->archiveDateSelect = $archiveDateSelect;
		return $this;
	}
	
	/**
	 *
	 * @return integer
	 */
	public function getArchiveDelay() {
		return $this->archiveDelay;
	}
	
	/**
	 *
	 * @param
	 *        	$archiveDelay
	 */
	public function setArchiveDelay($archiveDelay) {
		$this->archiveDelay = $archiveDelay;
		return $this;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getArchiveCustomFormula() {
		return $this->archiveCustomFormula;
	}
	
	/**
	 *
	 * @param
	 *        	$archiveCustomFormula
	 */
	public function setArchiveCustomFormula($archiveCustomFormula) {
		$this->archiveCustomFormula = $archiveCustomFormula;
		return $this;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function getArchiveSkipWorkflow() {
		return $this->archiveSkipWorkflow;
	}
	
	/**
	 *
	 * @param
	 *        	$archiveSkipWorkflow
	 */
	public function setArchiveSkipWorkflow($archiveSkipWorkflow) {
		$this->archiveSkipWorkflow = $archiveSkipWorkflow;
		return $this;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function getArchiveSkipDrafts() {
		return $this->archive_Skip_Drafts;
	}
	
	/**
	 *
	 * @param
	 *        	$archive_Skip_Drafts
	 */
	public function setArchiveSkipDrafts($archive_Skip_Drafts) {
		$this->archive_Skip_Drafts = $archive_Skip_Drafts;
		return $this;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function getArchiveSkipVersions() {
		return $this->archiveSkipVersions;
	}
	
	/**
	 *
	 * @param
	 *        	$archiveSkipVersions
	 */
	public function setArchiveSkipVersions($archiveSkipVersions) {
		$this->archiveSkipVersions = $archiveSkipVersions;
		return $this;
	}
	
	/**
	 *
	 * @return integer
	 */
	public function getVersionCount() {
		return $this->versionCount;
	}
	
	/**
	 *
	 * @param
	 *        	$versionCount
	 */
	public function setVersionCount($versionCount) {
		$this->versionCount = $versionCount;
		return $this;
	}
	
    
}