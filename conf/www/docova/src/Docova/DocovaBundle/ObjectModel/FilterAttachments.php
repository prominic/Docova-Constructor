<?php
namespace Docova\DocovaBundle\ObjectModel;

/**
 * This is an interface which must be implemented by all filtering attachments classes.
 * @author javad rahimi
 *        
 */
interface FilterAttachments 
{
	/**
	 * Returns true if the selected attachment passes the filtering process, otherwise false
	 * 
	 * @param \Docova\DocovaBundle\Entity\AttachmentsDetails $file
	 * @return boolean
	 */
	public function isValidAttachment(\Docova\DocovaBundle\Entity\AttachmentsDetails $file);
}