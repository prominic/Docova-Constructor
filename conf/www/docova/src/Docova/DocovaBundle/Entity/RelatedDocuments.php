<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RelatedDocuments
 *
 * @ORM\Table(name="tb_related_documents")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\RelatedDocumentsRepository")
 */
class RelatedDocuments
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Trash", type="boolean")
     */
    protected $Trash = false;
    
    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="childDocuments")
     * @ORM\JoinColumn(name="Parent_Document_Id", referencedColumnName="id", nullable=false)
     */
    protected $Parent_Doc;

	/**
	 * @ORM\ManyToOne(targetEntity="Documents")
	 * @ORM\JoinColumn(name="Related_Document_Id", referencedColumnName="id", nullable=true)
	 */
    protected $Related_Doc;

    
    public function __construct()
    {
    	$this->Trash = false;
    }

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
     * Set Trash
     *
     * @param boolean $trash
     * @return RelatedDocuments
     */
    public function setTrash($trash)
    {
        $this->Trash = $trash;
    
        return $this;
    }

    /**
     * Get Trash
     *
     * @return boolean 
     */
    public function getTrash()
    {
        return $this->Trash;
    }

    /**
     * Set Parent_Doc
     * 
     * @param \Docova\DocovaBundle\Entity\Documents $document
     */
    public function setParentDoc(\Docova\DocovaBundle\Entity\Documents $document)
    {
    	$this->Parent_Doc = $document;
    }

    /**
     * Get Parent_Doc
     * 
     * @return \Docova\DocovaBundle\Entity\Documents
     */
    public function getParentDoc()
    {
    	return $this->Parent_Doc;
    }

    /**
     * Set Related_Doc
     * 
     * @param \Docova\DocovaBundle\Entity\Documents|null $document
     */
    public function setRelatedDoc(\Docova\DocovaBundle\Entity\Documents $document = null)
    {
    	$this->Related_Doc = $document;
    }

    /**
     * Get Related_Doc
     * 
     * @return \Docova\DocovaBundle\Entity\Documents
     */
    public function getRelatedDoc()
    {
    	return $this->Related_Doc;
    }
}
