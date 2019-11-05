<?php
namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SystemPerspectivesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SystemPerspectivesRepository extends EntityRepository
{
	/**
	 * Get list of valid perspectives in a library
	 * 
	 * @param string $for_filter
	 * @param \Docova\DocovaBundle\Entity\Folders $folder
	 * @return array
	 */
	public function getValidPerspectivesFor($for_filter, $folder = null)
	{
		$query = $this->createQueryBuilder('SP');
		$query->where($query->expr()->like('SP.Visibility', $query->expr()->literal("%$for_filter%")));
		if (!empty($folder)) 
		{
			$query->leftJoin('DocovaBundle:Folders', 'F', 'WITH', 'F.Default_Perspective = SP.id');
			$query->distinct('SP.id');
			$query->andWhere("SP.Library IS NULL OR (SP.Library = :lib_id AND (SP.Available_For_Folders = true OR F.id = :folder))");
			$query->orWhere('SP.Built_In_Folder = :folder');
			$query->setParameter('folder', $folder->getId());
			$query->setParameter('lib_id', $folder->getLibrary()->getId());
		}
		$query->orderBy('SP.Perspective_Name', 'ASC');

		$result = $query->getQuery()->getResult();
		
		return $result;
	}

	/**
	 * Get all perspectives defined for folder view
	 * 
	 * @param string $library
	 * @return array
	 */
	public function getFoldersPerspectives($library)
	{
		$queryB = $this->createQueryBuilder('SP');
		$query = $queryB
				->where($queryB->expr()->like('SP.Visibility', $queryB->expr()->literal("%Folder%")))
				->andWhere('SP.Library IS NULL OR SP.Library = :library')
				->setParameter('library', $library)
				->orderBy('SP.Perspective_Name', 'ASC')
				->getQuery();
		
		$result = $query->getResult();
		
		return $result;
	}

	/**
	 * Check if perspective name exists in system default perpectives or custom perspectives
	 * 
	 * @param string $perspective_name
	 * @param integer $library_id
	 * @return boolean
	 */
	public function perspetiveExists($perspective_name, $library_id)
	{
		$query = $this->createQueryBuilder('SP')
				->where('SP.Perspective_Name = :p_name')
				->andWhere('SP.Library IS NULL OR SP.Library = :lib_id')
				->setParameters(array('p_name' => $perspective_name, 'lib_id' => $library_id))
				->getQuery();
		try {
			$result = $query->getOneOrNullResult();
			if (!empty($result))
			{
				return true;
			}
			return false;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}
}