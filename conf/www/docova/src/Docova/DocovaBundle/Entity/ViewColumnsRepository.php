<?php
namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ViewColumnsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ViewColumnsRepository extends EntityRepository
{
	/**
	 * List of valid columns
	 * 
	 * @param integer $library
	 * @param array $nodes
	 * @return mixed
	 */
	public function getValidColumns($library = null, $nodes = array(), $return_object = false)
	{
		$query = $this->createQueryBuilder('VC')
			->leftJoin('VC.Applicable_Libraries', 'AL')
			->where('VC.Column_Status = true');
		if (!empty($library)) 
		{
			$query->andWhere('VC.Column_Type = true OR (AL.id = :lib_id OR AL.id IS NULL)')
				->setParameter('lib_id', $library);
		}
		
		if (!empty($nodes)) 
		{
			if (count($nodes) > 1) 
			{
				$query->andWhere($query->expr()->in('VC.XML_Name', $nodes));
			}
			else {
				$query->andWhere('VC.XML_Name = :node');
				$query->setParameter('node', $nodes[0]);
			}
		}
		
		$query->addOrderBy('VC.Title', 'ASC');
		if ($return_object === false) {
			$result = $query->getQuery()->getArrayResult();
		}
		else {
			$result = $query->getQuery()->getResult();
		}

		if (!empty($result[0]))
		{
			return $result;
		}
		
		return array();
	}
	
	/**
	 * Get max of existing custom view columns
	 */
	public function getLastXMLNode()
	{
		$query = $this->createQueryBuilder('VC')
			->select('COUNT(VC.id) + 1')
			->where('VC.Column_Type = false');
		$query->andWhere($query->expr()->like('VC.XML_Name', $query->expr()->literal('CF%')));
		
		$result = $query->getQuery()->getSingleScalarResult();

		return $result;
	}
	
	/**
	 * Get all columns in specific range
	 * 
	 * @param array $nodes
	 * @return mixed|array 
	 */
	public function getColumnsIn($nodes)
	{
		$query = $this->createQueryBuilder('VC');
		if (count($nodes) > 1) {
			$query->where($query->expr()->in('VC.XML_Name', $nodes));
		}
		else {
			$query->where('VC.XML_Name = :node')
				->setParameter('node', $nodes[0]);
		}

		$result = $query->andWhere('VC.Column_Status = true')
					->getQuery()
					->getResult();
		
		return $result;
	}
	
	/**
	 * Return all columns XML Data as string or DOM Document
	 * 
	 * @param string $icon_path
	 * @param array $criteria
	 * @param array $orderBy
	 * @param boolean $return_obj
	 * @return string|\DOMDocument>
	 */
	public function getDataXML($icon_path, $criteria = array(), $sortBy = array(), $return_obj = false)
	{
		if (empty($criteria) && empty($sortBy)) {
			$view_columns = $this->createQueryBuilder('V')
				->addOrderBy('V.Column_Type', 'DESC')
				->getQuery()
				->getResult();
		}
		else {
			$orderBy = array();
			if (!empty($sortBy)) 
			{
				foreach ($sortBy as $key => $value) {
					if ($this->_class->hasField($key)) {
						$orderBy[$key] = strtoupper($value) == 'DESC' ? 'DESC' : 'ASC';
					}
					else {
						if ($key == 'columntype' && !empty($value)) {
							$orderBy['Column_Type'] = strtoupper($value) == 'DESC' ? 'ASC' : 'DESC';
						}
						elseif ($key == 'columntitle' && !empty($value)) {
							$orderBy['Title'] = strtoupper($value) == 'DESC' ? 'DESC' : 'ASC';
						}
						elseif ($key == 'datatype' && !empty($value)) {
							$orderBy['Data_Type'] = strtoupper($value) == 'DESC' ? 'DESC' : 'ASC';
						}
						elseif ($key == 'xmlNode' && !empty($value)) {
							$orderBy['XML_Name'] = strtoupper($value) == 'DESC' ? 'DESC' : 'ASC';
						}
						elseif ($key == 'dataquery' && !empty($value)) {
							$orderBy['Field_Name'] = strtoupper($value) == 'DESC' ? 'DESC' : 'ASC';;
						}
					}
				}
			}
			$view_columns = $this->findBy($criteria, $orderBy);
		}
		
		$data_xml = ($return_obj === false) ? array() : new \DOMDocument('1.0', 'UTF-8');
		foreach ($view_columns as $doc)
		{
			$columnDataType="";
			switch ($doc->getDataType())
			{
				case 1:
					$columnDataType="text";
					break;
				case 2:
					$columnDataType="date";
					break;
				case 3:
					$columnDataType="names";
					break;
				case 4:
					$columnDataType="html";
					break;
				default:
					$columnDataType="unknown";
					break;
			}

			if ($return_obj === false)
			{
				$data_xml[] = array(
					'dockey' => $doc->getId(),
					'columntype' => $doc->getColumnType() ? 'Built In' : 'Custom',
					'columntitle' => $doc->getTitle(),
					'datatype' => $columnDataType,
					'xmlNode' => $doc->getXMLName(),
					'disable-icon' => ($doc->getColumnStatus() == false ? 'vwicn081.gif' : 'vwicn082.gif'),
					'dataquery' => $doc->getFieldName()
				);
/*
				$data_xml .= '<document>';
				$data_xml .= '<dockey>'.$doc->getId().'</dockey>';
				$data_xml .= '<docid>'.$doc->getId().'</docid>';
				$data_xml .= '<rectype>doc</rectype>';
				$data_xml .= ($doc->getColumnType()) ? '<columntype><![CDATA[Built In]]></columntype>' : '<columntype><![CDATA[Custom]]></columntype>'; 
				$data_xml .= '<columntitle><![CDATA['.$doc->getTitle().']]></columntitle>';
				$data_xml .= '<datatype><![CDATA['.$columnDataType.']]></datatype>';						
				$data_xml .= '<xmlNode><![CDATA['.$doc->getXMLName().']]></xmlNode>';
				$img = ($doc->getColumnStatus() == false) ? '/vwicn081.gif' : '/vwicn082.gif';
				$data_xml .= "<disable-icon><img width='13' height='11' alt='Enabled' border='0' src=\"$icon_path$img\"/></disable-icon>";
				$data_xml .= '<dataquery><![CDATA['.$doc->getTitle().']]></dataquery>';
				$data_xml .= '<statno />';
				$data_xml .= '<wfstarted />';
				$data_xml .= '<delflag />';				
				$data_xml .= '</document>';
*/
			}
			else 
			{
				$root = $data_xml->appendChild($data_xml->createElement('document'));
				$root->appendChild($data_xml->createElement('dockey', $doc->getId()));
				$root->appendChild($data_xml->createElement('docid', $doc->getId()));
				$root->appendChild($data_xml->createElement('rectype', 'doc'));
				$cdata = $data_xml->createCDATASection($doc->getColumnType() ? 'Built In' : 'Custom');
				$newnode = $data_xml->createElement('columntype');
				$newnode->appendChild($cdata);
				$root->appendChild($newnode);
				$cdata = $data_xml->createCDATASection($doc->getTitle());
				$newnode = $data_xml->createElement('columntitle');
				$newnode->appendChild($cdata);
				$root->appendChild($newnode);
				$cdata = $data_xml->createCDATASection($columnDataType);
				$newnode = $data_xml->createElement('datatype');
				$newnode->appendChild($cdata);
				$root->appendChild($newnode);
				$cdata = $data_xml->createCDATASection($doc->getXMLName());
				$newnode = $data_xml->createElement('xmlNode');
				$newnode->appendChild($cdata);
				$root->appendChild($newnode);
				$img = ($doc->getColumnStatus() == false) ? '/vwicn082.gif' : '/vwicn081.gif';
				$cdata = $data_xml->createCDATASection("<img width='13' height='11' alt='Enabled' border='0' src=\"$icon_path.$img\"/>");
				$newnode = $data_xml->createElement('disable-icon');
				$newnode->appendChild($cdata);
				$root->appendChild($newnode);
				$cdata = $data_xml->createCDATASection($doc->getTitle());
				$newnode = $data_xml->createElement('dataquery');
				$newnode->appendChild($cdata);
				$root->appendChild($newnode);
			}
		}
		
		return $data_xml;
	}
	
	/*
	 * Deletes documents from web admin view
	* @param: xml data with id to delete
	* @return: xml Data response
	*/
	public function deleteSelectedDocuments($post_xml){
	
		$deleted=0;
	
		foreach ($post_xml->getElementsByTagName('Unid') as $doc_id)
		{
			$document = $this->find($doc_id->nodeValue);
			if (!empty($document))
			{
				$this->_em->remove($document);
				$deleted++;
				$this->_em->flush(); // commit each delete
			}
	
		}
		// setup return xml
		$response_xml = new \DOMDocument("1.0", "UTF-8");
		$root = $response_xml->appendChild($response_xml->createElement('Results'));
		if ($deleted !== 0)
		{
			$child = $response_xml->createElement('Result', 'OK');
			$attrib = $response_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$child->appendChild($attrib);
			$root->appendChild($child);
	
			$child = $response_xml->createElement('Result', $deleted);
			$attrib = $response_xml->createAttribute('ID');
			$attrib->value = 'Ret1';
			$child->appendChild($attrib);
			$root->appendChild($child);
		}
		else {
			$child = $response_xml->createElement('Result', 'FAILED');
			$attrib = $response_xml->createAttribute('ID');
			$attrib->value = 'Status';
			$child->appendChild($attrib);
			$root->appendChild($child);
	
			$child = $response_xml->createElement('Result', 'Could not delete one or more of the selected documents.');
			$attrib = $response_xml->createAttribute('ID');
			$attrib->value = 'ErrMsg';
			$child->appendChild($attrib);
			$root->appendChild($child);
		}
		return $response_xml->saveXML();
	}
}