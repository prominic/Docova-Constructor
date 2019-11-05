<?php

namespace Docova\DocovaBundle\Extensions;

use Doctrine\ORM\EntityManager;

/**
 * Superclass to manage major transactions in sub-classes 
 * @author javad_rahimi
 */
class TransactionManager
{
	protected $_em;
	private $_conn;
	
	public function __construct(EntityManager $manager)
	{
		$this->_em = $manager;
		$this->_conn = $manager->getConnection();
	}

	/**
	 * Get connection
	 *
	 * @return \Doctrine\DBAL\Connection
	 */
	public function getConnection()
	{
		return $this->_conn;
	}
	
	/**
	 * Starts a transaction by suspending auto-commit mode.
	 * Initiates a transaction.
	 */
	public function beginTransaction()
	{
		$this->_conn->beginTransaction();
	}
	
	/**
	 * Commits the current transaction. 
	 */
	public function commitTransaction()
	{
		$this->_conn->commit();
	}
	
	/**
	 * Cancels any database changes done during the current transaction.
	 */
	public function rollbackTransaction()
	{
		$this->_conn->rollBack();
	}
	
	/**
	 * Get driver name
	 * 
	 * @return string
	 */
	protected function getDriver()
	{
		return $this->_conn->getDriver()->getName();
	}
	
	/**
	 * Executes an, optionally parametrized, SQL query.
	 * 
	 * @param string $query
	 * @param array $params
	 * @param array $types
	 * @return \Doctrine\DBAL\Driver\Statement
	 */
	protected function executeQuery($query, $params = array(), $types = array())
	{
		return $this->_conn->executeQuery($query, $params, $types);
	}
	
	/**
	 * Prepares and executes an SQL query and returns the first row of the result as a numerically indexed array.
	 * 
	 * @param string $query
	 * @param array $params
	 * @param array $types
	 * @return array
	 */
	protected function fetchArray($query, $params = array(), $types = array())
	{
		return $this->_conn->fetchArray($query, $params, $types);
	}
	
	/**
	 * Prepares and executes an SQL query and returns the result as an associative array.
	 * 
	 * @param string $query
	 * @param array $params
	 * @param array $types
	 * @return array
	 */
	protected function fetchAll($query, $params = array(), $types = array())
	{
		return $this->_conn->fetchAll($query, $params, $types);
	}
	
	/**
	 * Prepares a statement for execution and returns a Statement object.
	 * 
	 * @param string $query
	 * @return \Doctrine\DBAL\Driver\Statement
	 */
	protected function prepare($query)
	{
		return $this->_conn->prepare($query);
	}
	
	/**
	 * Prepares and executes an SQL query and returns a single column value as an array.
	 *
	 * @param string $query
	 * @param array $params
	 * @param array $types
	 * @param int $colnum
	 * @return array
	 */
	protected function fetchColumn($query, $params = array(), $types = array(), $colnum=0)
	{
	    $result = array();
	    
	    $result = $this->executeQuery($query, $params, $types)->fetchAll(\PDO::FETCH_COLUMN, $colnum);
	    	    
	    return $result;
	}
}