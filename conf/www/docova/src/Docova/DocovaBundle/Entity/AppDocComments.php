<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppDocComments
 *
 * @ORM\Table(name="tb_app_document_comments")
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\AppDocCommentsRepository")
 */
class AppDocComments
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
     * @var integer
     *
     * @ORM\Column(name="Thread_Index", type="smallint")
     */
    protected $threadIndex;

    /**
     * @var integer
     *
     * @ORM\Column(name="Comment_Index", type="smallint")
     */
    protected $commentIndex;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Commentor", referencedColumnName="id", nullable=false)
     */
    protected $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @var string
     *
     * @ORM\Column(name="Comment", type="string", length=1024)
     */
    protected $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="Avatar", type="string", length=1024, nullable=true)
     */
    protected $avatar;

    /**
     * @ORM\ManyToOne(targetEntity="Documents", inversedBy ="AppDocComments")
     * @ORM\JoinColumn(name="Document_Id", referencedColumnName="id", nullable=false)
     */
    protected $document;


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
     * Set threadIndex
     *
     * @param integer $threadIndex
     * @return AppDocComments
     */
    public function setThreadIndex($threadIndex)
    {
        $this->threadIndex = $threadIndex;

        return $this;
    }

    /**
     * Get threadIndex
     *
     * @return integer 
     */
    public function getThreadIndex()
    {
        return $this->threadIndex;
    }

    /**
     * Set commentIndex
     *
     * @param integer $commentIndex
     * @return AppDocComments
     */
    public function setCommentIndex($commentIndex)
    {
        $this->commentIndex = $commentIndex;

        return $this;
    }

    /**
     * Get commentIndex
     *
     * @return integer 
     */
    public function getCommentIndex()
    {
        return $this->commentIndex;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return AppDocComments
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
     * Set comment
     *
     * @param string $comment
     * @return AppDocComments
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }


     /**
     * Set Avatar
     *
     * @param string $avatar
     * @return AppDocComments
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get Avatar
     *
     * @return string 
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set createdBy
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $createdBy
     * @return AppDocComments
     */
    public function setCreatedBy(\Docova\DocovaBundle\Entity\UserAccounts $createdBy)
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
     * Set document
     *
     * @param \Docova\DocovaBundle\Entity\Documents $document
     * @return AppDocComments
     */
    public function setDocument(\Docova\DocovaBundle\Entity\Documents $document)
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Get document
     *
     * @return \Docova\DocovaBundle\Entity\Documents 
     */
    public function getDocument()
    {
        return $this->document;
    }
}
