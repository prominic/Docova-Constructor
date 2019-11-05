<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * AppFormsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AppFormsRepository extends EntityRepository
{
	/**
	 * Get all application forms data array/xml for view
	 * 
	 * @param string $app_id
	 * @param string $format
	 * @return array
	 */
	public function getViewData($app_id, $format = 'array')
	{
		$output = array('@timestamp' => time(), '@toplevelentries' => '0');
		$query = $this->createQueryBuilder('F')
			->join('F.application', 'A')
			->where('F.application = :app')
			->andWhere('F.trash = false')
			->andWhere('A.Trash = false')
			->andWhere('A.isApp = true')
			->addOrderBy('F.formName', 'ASC')
			->addOrderBy('F.formAlias', 'ASC')
			->setParameter('app', $app_id)
			->getQuery();
		
		$result = $query->getResult();
		
		if (!empty($result))
		{
			if ($format === 'array')
			{
				$output['@toplevelentries'] = count($result);
				$output['viewentry'] = array(); 
				foreach ($result as $form) {
					$output['viewentry'][] = array(
						'@unid' => $form->getId(),
						'entrydata' => array(
							array('text' => array($form->getFormName())),
							array('text' => array($form->getFormAlias())),
							array('datetime' => array('@dst'=>true, 0 => $form->getDateModified()->format('Ymd his'))),
							array('text' => array($form->getModifiedBy()->getUserNameDnAbbreviated())),
							array('text' => array($form->getPDU() ? 'Yes' : 'No'))
						)
					);
				}
			}
			else {
				$xml = '<viewentries timestamp="'.time().'" toplevelentries="'.count($result).'">';
				foreach ($result as $form) {
					$xml .= "<viewentry unid='{$form->getId()}'>";
					$xml .= "<entrydata columnnumber='0'><text><![CDATA[{$form->getFormName()}]]></text></entrydata>";
					$xml .= "<entrydata columnnumber='1'><text><![CDATA[{$form->getFormAlias()}]]></text></entrydata>";
					$xml .= "<entrydata columnnumber='2'><datetime dst='true'>{$form->getDateModified()->format('Ymd his')}</datetime></entrydata>";
					$xml .= "<entrydata columnnumber='3'><text>{$form->getModifiedBy()->getUserNameDnAbbreviated()}</text></entrydata>";
					$xml .= '<entrydata columnnumber="4"><text>'.($form->getPDU() ? 'Yes' : 'No').'</text></entrydata>';
					$xml .= '<entrydata columnnumber="5"><text><![CDATA['.$form->getFormName().'|'.$form->getFormAlias().']]></text></entrydata>';
					$xml .= '</viewentry>';
				}
				$xml .= '</viewentries>';
				$output = $xml;
			}
			$result = $form = null;
		}
		elseif ($format !== 'array') {
			$output = '<viewentries timestamp="'.time().'" toplevelentries="0"></viewentries>';
		}

		return $output;
	}
	
	/**
	 * Return all application form names
	 * 
	 * @param string $application
	 * @return string[]|NULL
	 */
	public function getAppFormNames($application)
	{
		$query = $this->createQueryBuilder('F')
			->select(array('F.formName', 'F.formAlias'))
			->where('F.application = :app')
			->andWhere('F.trash = false')
			->addOrderBy('F.formName', 'ASC')
			->addOrderBy('F.formAlias', 'ASC')
			->setParameter('app', $application)
			->getQuery();
		
		$result = $query->getArrayResult();
		if (!empty($result))
		{
			$output = array();
			foreach ($result as $form) {
				$output[] = !empty($form['formName']) ? $form['formName'] : $form['formAlias'];
			}
			return $output;
		}
		return null;
	}
	
	/**
	 * Delete app workflows which are eadded into app forms
	 * 
	 * @param string $workflow
	 * @return boolean
	 */
	public function deleteAppFormWorkflows($workflow)
	{
		$query = 'DELETE FROM tb_app_form_workflows WHERE Workflow_Id = ?';
		$conn = $this->_em->getConnection();
		$stmt = $conn->prepare($query);
		$stmt->bindValue(1, $workflow);
		return $stmt->execute();
	}
	
	/**
	 * Find view by view name or alias
	 * 
	 * @param string $name
	 * @param string $appid
	 * @return \Docova\DocovaBundle\Entity\AppForms|null
	 */
	public function findByNameAlias($name, $appid)
	{
		$query = $this->createQueryBuilder('F')
			->where('F.application = :app')
			->andWhere('F.trash = false')
			->andWhere('F.formName = :fname OR F.formAlias = :fname')
			->setParameter('app', $appid)
			->setParameter('fname', $name)
			->getQuery();
		
		try {
			$result = $query->getSingleResult();
			return $result;
		}
		catch (\Exception $e) {
//			var_dump($e->getMessage());
			return null;
		}
	}
	
	/**
	 * Find trashed form by name or alias
	 * 
	 * @param string $appid
	 * @param string $name
	 * @param string $alias
	 * @return \Docova\DocovaBundle\Entity\AppForms|null
	 */
	public function findTrashedForm($appid, $name, $alias)
	{
		$query = $this->createQueryBuilder('F')
			->where('F.application = :app')
			->andWhere('F.trash = true')
			->setParameter('app', $appid);
		
		if (!empty($alias)) {
			$query->andWhere('F.formName = :fname OR F.formAlias = :falias')
				->setParameter('fname', $name)
				->setParameter('falias', $alias);
		}
		else {
			$query->andWhere('F.formName = :fname')
				->setParameter('fname', $name);
		}
		
		try {
			$result = $query->getQuery()->getSingleResult();
			return $result;
		}
		catch (\Exception $e) {
			return null;
		}
	}
}
