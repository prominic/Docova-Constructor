<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentComments
 *
 * @ORM\Table(name="tb_document_comments")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\DocumentCommentsRepository")
 */
class DocumentComments
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
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $Date_Created;

    /**
     * @var string
     *
     * @ORM\Column(name="Comment", type="text")
     */
    protected $Comment;

    /**
     * @var integer
     * 
     * @ORM\Column(name="Comment_Type", type="smallint", nullable=true)
     */
    protected $Comment_Type;

    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy="Comments")
     * @ORM\JoinColumn(name="Document_Id", referencedColumnName="id", nullable=false)
     */
    protected $Document;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Created_By", referencedColumnName="id", nullable=false)
     */
    protected $Created_By;


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
     * Set Date_Created
     *
     * @param \DateTime $dateCreated
     * @return DocumentComments
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

    /**
     * Set Comment
     *
     * @param string $comment
     * @return DocumentComments
     */
    public function setComment($comment)
    {
        $this->Comment = $comment;
    
        return $this;
    }

    /**
     * Get Comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->Comment;
    }

    /**
     * Set Comment_Type
     * 
     * @param boolean $Comment_Type
     * @return DocumentComments
     */
    public function setCommentType($Comment_Type) 
    {
    	$this->Comment_Type = $Comment_Type;
    	
    	return $this;
    }

	/**
	 * Get Comment_Type
	 * 
	 * @return boolean
	 */
	public function getCommentType() 
	{
		return $this->Comment_Type;
	}

    /**
     * Set Document
     * 
     * @param \Docova\DocovaBundle\Entity\Documents $document
     */
    public function setDocument(\Docova\DocovaBundle\Entity\Documents $document)
    {
    	$this->Document = $document;
    }

    /**
     * Get Document
     * 
     * @return \Docova\DocovaBundle\Entity\Documents
     */
    public function getDocument()
    {
    	return $this->Document;
    }

    /**
     * Set Created_By
     * 
     * @param \Docova\DocovaBundle\Entity\UserAccounts $user
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $user)
    {
    	$this->Created_By = $user;
    }

    /**
     * Get Created_By
     * 
     * @return \Docova\DocovaBundle\Entity\UserAccounts
     */
    public function getCreatedBy()
    {
    	return $this->Created_By;
    }
}
