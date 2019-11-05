<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * FormNumericValuesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FormNumericValuesRepository extends EntityRepository
{
    /**
     * Custom search in form element values base on the entered operand
     *
     * @param integer $fields
     * @param string $operand
     * @param string $value
     * @param array $filters
     * @param array $doctypes
     * @return array
     */
    public function customSearch($fields, $operand, $value, $filters, $doctypes = array(),$defaultFormat="m/d/Y",$type="text")
    {
        if (empty($fields) || empty($operand) || empty($value)) { return array(); }
        
        if (!empty($filters['folder']))
        {
            $folders = array($filters['folder']->getId());
            if (!empty($filters['include_subfolders']) && $filters['include_subfolders'] === true)
            {
                $subfolders = $this->_em->getRepository('DocovaBundle:Folders')->getDescendants($filters['folder']->getPosition(), $filters['folder']->getLibrary()->getId(), null, true);
                foreach ($subfolders as $f)
                {
                    $folders[] = $f['id'];
                }
                unset($subfolders, $f);
            }
        }
        elseif (!empty($filters['libraries']))
        {
            $libraries = explode(',', $filters['libraries']);
        }
        else {
            return array();
        }
        
        $query = $this->_em->createQueryBuilder()
	        ->select('DISTINCT(D.id) AS id')
	        ->from('Docova\DocovaBundle\Entity\Documents', 'D')
	        ->leftJoin('D.Bookmarks', 'B')
	        ->where('D.Trash = false AND D.Archived = false');
        
        $subQuery = $this->createQueryBuilder('EV')
	        ->select('IDENTITY(EV.Document)');
        $fields = explode(';', $fields);
        if (count($fields) > 1) {
            $subQuery->where($subQuery->expr()->in('EV.Field', $fields));
        }
        else {
            $subQuery->where('EV.Field = :field');
            $query->setParameter('field', $fields[0]);
        }
        
        if (\is_numeric($value)){
                    $value =  \str_replace(",", ".",$value);
                    if ($value=="0"  || $value=="0.0")
                        $value="0.00";
                        $subQuery->andWhere(" EV.fieldValue ".$operand." ".$value);
        }   
                
        $query->andWhere($query->expr()->in('D.id', $subQuery->getDQL()));
        
        if (!empty($folders))
        {
            $query->join('D.folder', 'DF');
            if (count($folders) > 1)
            {
                $query->andWhere($query->expr()->in('D.folder', $folders). ' OR '.$query->expr()->in('B.Target_Folder', $folders));
            }
            else {
                $query->andWhere('D.folder = :folder OR B.Target_Folder = :folder');
                $query->setParameter('folder', $folders[0]);
            }
            $query->andWhere('DF.Del = false');
            $query->andWhere('DF.Inactive = false');
        }
        elseif (!empty($libraries))
        {
            $query->join('D.folder', 'DF')
	            ->join('DF.Library', 'L')
	            ->andWhere('L.Trash = false')
	            ->andWhere('DF.Del = false')
	            ->andWhere('DF.Inactive = false');
            if (count($libraries) > 1)
            {
                $query->andWhere($query->expr()->in('DF.Library', $libraries));
            }
            else {
                $query->andWhere('DF.Library = :lib_id');
                $query->setParameter('lib_id', $libraries[0]);
            }
        }
        
        if (!empty($doctypes) && count($doctypes) > 0)
        {
            $query->join('D.DocType', 'DT');
            if (count($doctypes) > 1) {
                $query->andWhere($query->expr()->in('DT.id', $doctypes));
            }
            else {
                $query->andWhere('DT.id = :doctype');
                $query->setParameter('doctype', $doctypes[0]);
            }
        }
        
        $output = array();
        
        /*
         echo "<br/>Value: ".$value;
         echo "<br/>Query:";
         echo "<br/>".$query->getDQL();
         */
        
        $result = $query->getQuery()->getArrayResult();
        $len = count($result);
        if (!empty($result) && !empty($result[0])) {
            for ($x = 0; $x < $len; $x++) {
                $output[] = $result[$x]['id'];
            }
        }
        return $output;
    }
    
	/**
	 * @param integer $document
	 * @param string $field_name
	 * @return string
	 */
	public function getFieldValue($document, $field_name)
	{
		$query = $this->createQueryBuilder('EV')
			->select('EV.fieldValue')
			->join('EV.Field', 'F')
			->where('EV.Document = :document')
			->andWhere('F.fieldName = :field OR F.fieldName = :fieldArr')
			->setParameter('document', $document)
			->setParameter('field', $field_name)
			->setParameter('fieldArr', $field_name.'[]')
			->getQuery();
		
		$result = $query->getArrayResult();
		if (!empty($result[0])) 
		{
			return !empty($result[0]['fieldValue']) ? $result[0]['fieldValue'] : '';
		}
		return '';
	}

	/**
	 * Get particular fields value in a document
	 *
	 * @param integer $document
	 * @param array $fields
	 * @param boolean $return_obj
	 * @return mixed
	 */
	public function getDocumentFieldsValue($document, $fields, $return_obj = false)
	{
		$query = $this->createQueryBuilder('EV')
			->join('EV.Field', 'F')
			->where('EV.Document = :document')
			->addOrderBy('EV.order', 'ASC')
			->setParameter('document', $document);
	
		if ($return_obj === false)
		{
			$query->select(array('EV.fieldValue', 'F.fieldName', 'F.fieldType', 'F.multiSeparator', 'F.id'));
		}
	
		if (count($fields > 1))
		{
			$query->andWhere($query->expr()->in('F.fieldName', $fields));
		}
		else {
			$query->andWhere('F.fieldName = :field')
				->setParameter('field', $fields[0]);
		}
	
		$result = $query->getQuery()->getResult();
		return !empty($result) ? $result : array();
	}

	/**
	 * Get application field values
	 * 
	 * @param string $field
	 * @return array
	 */
	public function getAppFieldValues($field)
	{
		$query = $this->createQueryBuilder('V')
			->select('V.fieldValue AS fvalue, IDENTITY(V.Document) AS docid')
			->where('V.Field = :field')
			->setParameter('field', $field)
			->addOrderBy('V.order', 'ASC')
			->getQuery();
		
		$result = $query->getArrayResult();
		return $result;
	}
	
	/**
	 * Delete all document field values
	 * 
	 * @param string $docid
	 */
	public function deleteAllValues($docid)
	{
		$query = $this->createQueryBuilder('V')
			->delete()
			->where('V.Document = :doc')
			->setParameter('doc', $docid)
			->getQuery();
		
		$query->execute();
	}
	
	/**
	 * Delete specified field values
	 * 
	 * @param string $field
	 */
	public function deleteFieldRecords($field)
	{
		$query = $this->createQueryBuilder('V')
			->delete()
			->where('V.Field = :field')
			->setParameter('field', $field)
			->getQuery();
		
		$query->execute();
	}
}
