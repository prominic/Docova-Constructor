<?php

namespace Docova\DocovaBundle\ObjectModel;

/**
 * Class with properties and methods for manipulating attachments
 * @author javad_rahimi
 *        
 */
class DocovaAttachment 
{
	private $_em;
	private $UPLOAD_FILE_PATH;
	private $parentDoc;
	public $fileDate = null;
	public $fileExt = null;
	public $fileName = null;
	public $filePath = null;
	public $fileSize = null;
	public $fileURL = null;
	
	public function __construct(DocovaDocument $parentDoc, $attachment, $root_path = '', Docova $docova_obj = null)
	{
		if (empty($parentDoc) || empty($attachment))
			throw new \Exception('Oops! Construction of DocovaAttachments failed, unrecognized entries!');
		
		if (!empty($docova_obj)) {
			$this->_em = $docova_obj->getManager();
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_em = $docova->getManager();
			}
			else {
				throw new \Exception('Oops! DocovaAttachment construction failed. Entity Manager not available.');
			}
		}
		
		$this->parentDoc = $parentDoc;
		$root_path = !empty($root_path) ? $root_path : $_SERVER['DOCUMENT_ROOT'];
		$this->UPLOAD_FILE_PATH = $root_path.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
		
		if ($attachment instanceof \Docova\DocovaBundle\Entity\AttachmentsDetails)
		{
//			$this->_attachment = $attachment;
			$this->fileDate = $attachment->getFileDate();
			$this->fileExt = pathinfo($attachment->getFileName(), PATHINFO_EXTENSION);
			$this->fileName = $attachment->getFileName();
			$this->filePath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$this->parentDoc->getId().DIRECTORY_SEPARATOR.md5($this->fileName);
			$this->fileSize = $attachment->getFileSize();
			$this->fileURL = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '').'://'."{$_SERVER['HTTP_HOST']}/".stristr($_SERVER['REQUEST_URI'], '/Docova');
			$this->fileURL .= '/Docova/openDocFile/'.$this->fileName.'?openPage&doc_id='.$this->parentDoc->getId();
		}
		else
		{
			$attachment = $this->_em->getRepository('DocovaBundle:AttachmentsDetails')->findOneBy(array('File_Name' => $attachment, 'Document' => $parentDoc->getId()));
			if (!empty($attachment))
			{
//				$this->_attachment = $attachment;
				$this->fileDate = $attachment->getFileDate();
				$this->fileExt = pathinfo($attachment->getFileName(), PATHINFO_EXTENSION);
				$this->fileName = $attachment->getFileName();
				$this->filePath = $this->UPLOAD_FILE_PATH.DIRECTORY_SEPARATOR.$this->parentDoc->getId().DIRECTORY_SEPARATOR.md5($this->fileName);
				$this->fileSize = $attachment->getFileSize();
				$this->fileURL = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '').'://'."{$_SERVER['HTTP_HOST']}/".stristr($_SERVER['REQUEST_URI'], '/Docova');
				$this->fileURL .= '/Docova/openDocFile/'.$this->fileName.'?openPage&doc_id='.$this->parentDoc->getId();
				$attachment = null;
			}
		}
	}
	
	/**
	 * Deletes the attachment from a back end docova document
	 * 
	 * @return boolean
	 */
	public function deleteAttachment()
	{
		if (empty($this->fileName))
			return false;
		
		if ($this->parentDoc->deleteAttachment($this->fileName)) {
			$this->_attachment = $this->parentDoc = null;
			$this->fileDate = $this->fileExt = $this->fileName = $this->filePath = $this->fileSize = $this->fileURL = null;
			return true;
		}
		return false;
	}
}