<?php

namespace Docova\DocovaBundle\Extensions;

use Docova\DocovaBundle\Entity\Libraries;

/**
 * Traits to handle common copy app/library services
 * @author javad_rahimi
 */
trait CopyServices
{
	/**
	 * Duplicates an application
	 *
	 * @param array $options
	 * @return \Docova\DocovaBundle\Entity\Libraries
	 */
	public function copyApplication($options = array())
	{
		$app = new Libraries();
		$app->setLibraryTitle(!empty($options['title']) ? $options['title'] : $this->_source->getLibraryTitle());
		$app->setDescription(!empty($options['desc']) ? $options['desc'] : $this->_source->getDescription());
		$app->setAppIcon(!empty($options['icon']) ? $options['icon'] : $this->_source->getAppIcon());
		$app->setAppIconColor(!empty($options['icon_color']) ? $options['icon_color'] : $this->_source->getAppIconColor());
		$app->setHostName(!empty($options['host']) ? $options['host'] : $this->_source->getHostName());
		$app->setDateCreated(new \DateTime());
		$app->setIsApp(!empty($options['is_app']) ? true : false);
		$app->setAppInherit(!empty($options['inherit']) ? $options['inherit'] : $this->_source->getAppInherit());
		$app->setSourceTemplate(!empty($options['source_template']) ? $options['source_template'] : null);
		$this->_em->persist($app);
		$this->_em->flush();
	
		$this->_target = $app;
		if (!empty($options['is_app']))
		{
			$this->setTargetPath($app->getId());
			$this->copyApplicationACL();
		}
	
		return $app;
	}
	
	public function setTargetApp( $target){
		$this->_target = $target;
	}
}