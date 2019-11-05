<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RelatedEmails
 *
 * @ORM\Table(name="tb_related_emails")
 * @ORM\Entity
 */
class RelatedEmails
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
     * @ORM\Column(name="From_Who", type="string", length=2048)
     */
    protected $fromWho;

    /**
     * @var string
     *
     * @ORM\Column(name="To_Who", type="string", length=2048)
     */
    protected $toWho;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="CC_Who", type="string", length=2048, nullable=true)
     */
    protected $cc;
    
    /**
     * @var string
     *
     * @ORM\Column(name="BCC_Who", type="string", length=2048, nullable=true)
     */
    protected $bcc;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_Sent", type="datetime")
     */
    protected $dateSent;

    /**
     * @var string
     *
     * @ORM\Column(name="Subject", type="string", length=2048)
     */
    protected $subject;

    /**
     * @ORM\ManyToOne(targetEntity="Documents")
     * @ORM\JoinColumn(name="Document_Id", referencedColumnName="id", nullable=false)
     */
    protected $document;


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
     * Set fromWho
     *
     * @param string $fromWho
     * @return RelatedEmails
     */
    public function setFromWho($fromWho)
    {
        $this->fromWho = $fromWho;
    
        return $this;
    }

    /**
     * Get fromWho
     *
     * @return string 
     */
    public function getFromWho()
    {
        return $this->fromWho;
    }

    /**
     * Set toWho
     *
     * @param string $toWho
     * @return RelatedEmails
     */
    public function setToWho($toWho)
    {
    	$this->toWho = $toWho;
    
    	return $this;
    }
    
    /**
     * Get toWho
     *
     * @return string
     */
    public function getToWho()
    {
    	return $this->toWho;
    }

    /**
     * Set dateSent
     *
     * @param \DateTime $dateSent
     * @return RelatedEmails
     */
    public function setDateSent($dateSent)
    {
        $this->dateSent = $dateSent;
    
        return $this;
    }

    /**
     * Get dateSent
     *
     * @return \DateTime 
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return RelatedEmails
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    
        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set document
     *
     * @param \Docova\DocovaBundle\Entity\Documents $document
     * @return RelatedEmails
     */
    public function setDocument(\Docova\DocovaBundle\Entity\Documents $document)
    {
        $this->document = $document;
    
        return $this;
    }

    /**
     * Get document
     *
     * @return string 
     */
    public function getDocument()
    {
        return $this->document;
    }

	/**
	 * Get cc
	 * 
	 * @return string
	 */
	public function getCc() 
	{
		return $this->cc;
	}

	/**
	 * Set cc
	 * 
	 * @param string $cc
	 * @return RelatedEmails
	 */
	public function setCc($cc) 
	{
		$this->cc = $cc;
		return $this;
	}

	/**
	 * Get bcc
	 * 
	 * @return string
	 */
	public function getBcc() 
	{
		return $this->bcc;
	}

	/**
	 * Set bcc
	 * 
	 * @param string $bcc
	 * @return RelatedEmails
	 */
	public function setBcc($bcc) 
	{
		$this->bcc = $bcc;
		return $this;
	}
}
