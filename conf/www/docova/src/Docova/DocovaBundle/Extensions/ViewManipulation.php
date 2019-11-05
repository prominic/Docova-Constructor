<?php

namespace Docova\DocovaBundle\Extensions;

use Docova\DocovaBundle\Twig\DocovaExtension;
use Docova\DocovaBundle\ObjectModel\ObjectModel;
use Docova\DocovaBundle\Twig\DocovaTwigExtension;
use Docova\DocovaBundle\Twig\DocovaConcatExtension;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Class for all manual/native maniuplation on app views
 * @author javad_rahimi
 * 
 */
class ViewManipulation extends TransactionManager
{
	private $_app;
	private $_docova;
	private $_twigEnv = null;
	private $_dateformat;
	private $_shortname = false;
	
	public function __construct(Docova $docova_obj = null, $application = null, $global_settings = null)
	{
		if (!empty($docova_obj)) {
			$this->_docova = $docova_obj;
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_docova = $docova;
			}
			else {
				throw new \Exception('View Manipulation contstruction failed, Docova service not available.');
			}
		}
		
		if ($application instanceof \Docova\DocovaBundle\ObjectModel\DocovaApplication) {
			$this->_app = $application->appID;
		}
		elseif ($application instanceof \Docova\DocovaBundle\Entity\Libraries) {
			$this->_app = $application->getId();
		}
		else {
			$this->_app = $application;
		}
		
		if (empty($global_settings)) {
			$global_settings = $this->_docova->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
			$global_settings = $global_settings[0];
		}
		$this->_dateformat = str_replace(array('MM', 'DD', 'YYYY'), array('m', 'd', 'Y'), $global_settings->getDefaultDateFormat());
		$this->_shortname = $global_settings->getUserDisplayDefault();
		$global_settings = null;
		
		parent::__construct($this->_docova->getManager());
	}
	
	/**
	 * Check if view table exists
	 * 
	 * @param string $view
	 * @return boolean
	 */
	public function viewExists($view)
	{
		$exists = false;
		$driver = $this->getDriver();
		if ($driver == 'pdo_mysql')
		{
			$exists = $this->fetchArray('SHOW TABLES LIKE ?', array("view_$view"));
			$exists = !empty($exists[0]) ? true : false;
		}
		elseif ($driver == 'pdo_sqlsrv' || $driver =='sqlsrv')
		{
			$exists = $this->fetchArray("IF OBJECT_ID('view_".$view."', 'U') IS NOT NULL BEGIN SELECT 1 END ELSE BEGIN SELECT 0 END");
			$exists = !empty($exists[0]) ? true : false;
		}
		return $exists;
	}
	
	/**
	 * Create view table
	 * 
	 * @param \Docova\DocovaBundle\Entity\AppViews $view
	 * @param \DOMDocument $xml
	 */
	public function createViewTable($view, $xml)
	{
		$driver = $this->getDriver();
		$response_columns = $sort_columns = [];
		$view_id = str_replace('-', '', $view->getId());
		$query = 'CREATE TABLE view_'. $view_id .' (';
		$query .= 'Document_Id varchar(36) NOT NULL, ';
		$query .= 'App_Id varchar(36) NOT NULL, ';
		$query .= 'Parent_Doc varchar(36) NULL, ';
		foreach ($xml->getElementsByTagName('xmlNodeName') as $index => $field)
		{
			$type = 'NVARCHAR(1024) NULL';
			if ($xml->getElementsByTagName('dataType')->item($index)->nodeValue == 'date' || $xml->getElementsByTagName('dataType')->item($index)->nodeValue == 'datetime') {
				if ($driver == 'pdo_sqlsrv' || $driver =='sqlsrv')
					$type = 'datetime2(7) NULL';
				else
					$type = 'DATETIME NULL';
			}
			elseif ($xml->getElementsByTagName('dataType')->item($index)->nodeValue == 'number') {
				$type = 'FLOAT NULL';
			}

			if (!empty($xml->getElementsByTagName('sortOrder')->item($index)->nodeValue) && $xml->getElementsByTagName('sortOrder')->item($index)->nodeValue != 'none') {
				$sort_columns[] = $field->nodeValue;
			}

			$query .= "{$field->nodeValue} $type, ";
			if ($view->getRespHierarchy() && !empty($xml->getElementsByTagName('responseFormula')->item($index)->nodeValue))
			{
				$query .= "RESP_{$field->nodeValue} NVARCHAR(1024) NULL, ";
				$response_columns[] = $field->nodeValue;
			}
		}
		$query = substr_replace($query, ')', -2);
		if ($driver == 'pdo_mysql') {
			$query .= ' ENGINE INNODB COLLATE utf8_unicode_ci';
		}
		
		$this->executeQuery($query);
		
		$query = 'CREATE INDEX idx_' . $view_id . ' ON view_'. $view_id . ' (Document_Id) ';
		$this->executeQuery($query);
		
		if (!empty($response_columns))
		{
			$query = $driver == 'pdo_mysql' ? 'CREATE UNIQUE INDEX idxMain_'.$view_id.' ON view_'.$view_id.'(' : 'CREATE CLUSTERED INDEX idxMain_'.$view_id.' ON view_'.$view_id.'(';
			$query .= 'App_Id,Document_Id,';
			foreach ($response_columns as $column) {
				if ($driver == 'pdo_mysql') {
					//max allowed key length in mysql 5.6 is 767, in mysql 5.7> is 3072
					$query .= "RESP_$column(255),";
				}
				else {
					$query .= "RESP_$column,";
				}
			}
			$query = substr_replace($query, ')', -1);
			$this->executeQuery($query);
		}
		
		if (!empty($sort_columns)) {
			foreach ($sort_columns as $column) {
				$query = 'CREATE INDEX idx_sorted_'.$column.' ON view_'.$view_id.' ('.$column.')';
				$this->executeQuery($query);
			}
		}
	}
	
	/**
	 * Get all current view columns in array
	 * 
	 * @param string $view
	 * @return array
	 */
	public function fetchViewColumns($view)
	{
		$query = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?';
		$columns = $this->fetchAll($query, array('view_'.$view));
		$len = count($columns);
		for ($x = 0; $x < $len; $x++) {
			if ($columns[$x]['COLUMN_NAME'] != 'Document_Id' && $columns[$x]['COLUMN_NAME'] != 'App_Id' && $columns[$x]['COLUMN_NAME'] != 'Parent_Doc' && false === strpos($columns[$x]['COLUMN_NAME'], 'RESP_'))
				$columns[$x] = $columns[$x]['COLUMN_NAME'];
			else
				unset($columns[$x]);
		}
		$columns = array_values($columns);
		return $columns;
	}
	
	/**
	 * @deprecated (never used)
	 * Drop main index of view for altered columns
	 * 
	 * @param string $view
	 */
	public function resetMainIndex($view)
	{
		$driver = $this->getDriver();
		if ($driver == 'pdo_mysql') {
			$query = "SHOW INDEX FROM view_$view WHERE KEY_NAME = ?";
			$res = $this->fetchArray($query, array("idxMain_$view"));
			if (!empty($res[0])) {
				$query = "ALTER TABLE view_$view DROP INDEX idxMain_$view;";
				$this->executeQuery($query);
			}
			$res = null;
		}
		elseif ($driver == 'pdo_sqlsrv')
		{
			$query = "BEGIN IF EXISTS (SELECT * FROM sys.indexes WHERE name=? AND object_id = OBJECT_ID('[view_$view]')) BEGIN DROP INDEX idxMain_$view ON [view_$view] END END";
			$this->executeQuery($query, array("idxMain_$view"));
		}
	}
	
	/**
	 * @deprecated (never used)
	 * Create unique index for responses columns
	 * 
	 * @param string $view
	 * @param array $columns
	 */
	public function createResponsesUniqueIndex($view, $columns)
	{
		$driver = $this->getDriver();
		$query = $driver == 'pdo_mysql' ? 'CREATE UNIQUE INDEX idxMain_'.$view.' ON view_'.$view.'(' : 'CREATE CLUSTERED INDEX idxMain_'.$view.' ON view_'.$view.'(';
		foreach ($columns as $column) {
			if ($driver == 'pdo_mysql') {
				//max allowed key length in mysql 5.6 is 767, in mysql 5.7> is 3072
				$query .= "RESP_$column(512),";
			}
			else {
				$query .= "RESP_$column,";
			}
			$query .= "{$column}_id,";
		}
		$query = substr_replace($query, ')', -1);
		$this->executeQuery($query);
	}
	
	/**
	 * @deprecated (never used)
	 * Add new columns to view table
	 * 
	 * @param string $view
	 * @param array $columns
	 * @param array $response_columns
	 */
	public function addViewColumns($view, $columns, $response_columns = array())
	{
		$query = "ALTER TABLE view_$view ";
		foreach ($columns as $field) {
			$type = 'NVARCHAR(1024) NULL';
			if ($field['type'] == 'date' || $field['type'] == 'datetime') {
				$driver = $this->getDriver();
				if ($driver == 'pdo_sqlsrv' || $driver =='sqlsrv')
					$type = 'datetime2(7) NULL';
				else
					$type = 'DATETIME NULL';
			}
			elseif ($field['type'] == 'number') {
				$type = 'FLOAT NULL';
			}
			$query .= "ADD {$field['field']} $type,";
			if (!empty($response_columns) && in_array($field['field'], $response_columns)) {
				$query .= "ADD {$field['field']}_id VARCHAR(36),";
			}
		}
		$query = substr_replace($query, ';', -1);
		$this->executeQuery($query);
	}
	
	/**
	 * @deprecated (never used)
	 * Modify view table columns
	 * 
	 * @param string $view
	 * @param array $columns
	 */
	public function alterViewColumns($view, $columns)
	{
		$driver = $this->getDriver();

		//lets truncate the view first..its will be re-indexed later

		$this->truncateView($view);
		if ($driver == 'pdo_mysql') {
			$query = "ALTER TABLE view_$view ";
		}
		else {
			$query = '';
		}
		foreach ($columns as $field) {
			$type = 'NVARCHAR(1024) NULL';
			if ($field['type'] == 'date') {
				if ($driver == 'pdo_sqlsrv' || $driver =='sqlsrv')
					$type = 'datetime2(7) NULL';
				else
					$type = 'DATETIME NULL';
			}
			elseif ($field['type'] == 'number') {
				$type = 'FLOAT NULL';
			}
		
			if ($driver == 'pdo_mysql') {
				$query .= "MODIFY COLUMN {$field['field']} $type,";
			}
			else {
				$query .= "ALTER TABLE view_$view ALTER COLUMN {$field['field']} $type;";
			}
		}
		$query = substr_replace($query, ';', -1);
		$this->executeQuery($query);
	}
	
	/**
	 * @deprecated (never used)
	 * Update view documents
	 * 
	 * @param string $view
	 * @param array $records
	 */
	public function updateViewEntries($view, $records)
	{
		foreach ($records as $docid => $values)
		{
			$query = "UPDATE view_$view SET ";
			foreach ($values as $field => $value) {
				if (!empty($value)) {
					if ($docid !== 'ALL') {
						$query .= "$field = ?, ";
					}
					else {
						$query .= "$field = '$value', ";
					}
				}
				else {
					$query .= "$field = NULL, ";
				}
			}
			if ($docid === 'ALL') {
				$query = substr_replace($query, ' ', -2);
			}
			else {
				$query = substr_replace($query, ' WHERE Document_Id = ?', -2);
			}
			$stmt = $this->prepare($query);
			if ($docid !== 'ALL') {
				$x = 1;
				foreach ($values as $value) {
					if (!empty($value))
					{
						try {
							$tmp = new \DateTime($value);
						}
						catch (\Exception $e) {
							$tmp = trim($value);
						}
						$stmt->bindValue($x, $tmp, (!empty($tmp) && $tmp instanceof \DateTime ? 'datetime' : null));
						$x++;
					}
				}
				$stmt->bindValue($x, $docid);
			}
			$stmt->execute();
			if (!$stmt->rowCount() && $docid !== 'ALL') {
				$query = "SELECT COUNT(*) FROM view_$view WHERE Document_Id = ? AND App_Id = ?";
				$result = $this->fetchArray($query, array($docid, $this->_app));
				if (empty($result[0]))
				{
					$query = "INSERT INTO view_$view (Document_Id, App_Id,";
					foreach ($values as $field => $value) {
						$query .= "$field,";
					}
					$query = substr_replace($query, ') VALUES (?,?,', -1);
					foreach ($values as $value) {
						if (!empty($value)) {
							$query .= '?,';
						}
						else {
							$query .= 'NULL,';
						}
					}
					$query = substr_replace($query, ')', -1);
					$stmt = $this->prepare($query);
					$stmt->bindValue(1, $docid);
					$stmt->bindValue(2, $this->_app);
					$x = 3;
					foreach ($values as $field => $value) {
						if (!empty($value))
						{
							$tmp = trim($value);
							try {
								$tmp = new \DateTime($value);
							}
							catch (\Exception $e) {
								$tmp = !empty($value) ? trim($value) : null;
							}
							$stmt->bindValue($x, $tmp, (!empty($tmp) && $tmp instanceof \DateTime ? 'datetime' : null));
							$x++;
						}
						$tmp = null;
					}
					$stmt->execute();
				}
				$value = null;
			}
		}
	}

	/**
	 * Append view entries to view table
	 * 
	 * @param string $docid
	 * @param string $view
	 * @param \DOMDocument $xml
	 * @param array $values_array
	 * @param boolean $isAdd
	 * @return boolean
	 */
	public function addOrUpdateViewEntries2($docid, $view, $xml, $values_array, $isAdd = true, $isUpdate = false)
	{
		$returnresult = false;
		
		$columns = $values = array();
		if ($isAdd !== true) {
			$this->deleteDocument($view, $docid, !$isUpdate);
		}
//		$has_response = false;
		$result = $this->prepareQuery2($xml, $values_array);
		$columns = $result['columns'];
		$values = $result['values'];
		if (empty($columns)) return false;
		$query = "INSERT INTO view_$view (App_Id, Document_Id, Parent_Doc,";
		
		for ($x = 0; $x < count($columns); $x++) {
			$query .= " {$columns[$x]['field']},";
// 			if (0 === strpos($columns[$x]['field'], 'RESP_')) {
// 				$has_response = true;
// 			}
		}

		$query = substr_replace($query, ') VALUES (?, ?, ?, ', -1);
		for ($x = 0; $x < count($columns); $x++) {
			$query .= ' ?, ';
		}
		$query = substr_replace($query, ')', -2);
		
		$dimension = [];
		foreach ($values as $value) {
			if (is_array($value['value'])) {
				$dimension[] = count($value['value']);
			}
		}
		$dimension = array_product($dimension);
		$query_values = [];
		if (empty($dimension) || $dimension === 1) {
			$query_values[] = $values;
		}
		else {
			$array_index = 1;
			for ($x = 0; $x < count($values); $x++)
			{
				if (!is_array($values[$x]['value'])) {
					for ($c = 0; $c < $dimension; $c++) {
						$query_values[$c][$x] = $values[$x]['value'];
					}
				}
				else {
					$col_max = floor($dimension / (count($values[$x]['value']) * $array_index));
					for ($c = 0; $c < $dimension; $c++) {
						$index = floor($c / $col_max) % count($values[$x]['value']);
						$query_values[$c][$x] = ['type' => $values[$x]['type'], 'value' => $values[$x]['value'][$index]];
					}
					$array_index++;
				}
			}
		}
		
		for ($c = 0; $c < count($query_values); $c++)
		{
			$x = 4;
			$this->beginTransaction();
			try {
				$stmt = $this->prepare($query);
				$stmt->bindValue(1, $this->_app);
				$stmt->bindValue(2, $docid);
				$stmt->bindValue(3, $values_array[0]['__parentdoc']);
				foreach ($query_values[$c] as $value) {
					if (!is_array($value) || !array_key_exists('value', $value)) {
						if (empty($value) || $value === 'KEEP THE OLD VALUE AS IS') {
							$stmt->bindValue($x, null);
						}
						else {
							if ( $value instanceof \DateTime){
								$value = $value->format("Y-m-d H:i:s");
							}
							$value = is_array($value) ? implode(", ", $value) : $value;
							if ( strlen($value) > 1024){
								$value = substr($value, 0, 1024);
							}
							
							$stmt->bindValue($x, $value);
						}
					}
					elseif ($value['value'] === 'KEEP THE OLD VALUE AS IS') {
						$stmt->bindValue($x, null);
					}
					else {
						if ( $value['value'] instanceof \DateTime){
							$value['value'] = $value['value']->format("Y-m-d H:i:s");
							
						}
						if ( strlen($value['value']) > 1024){
							$value['value'] = substr($value['value'], 0, 1024);
						}
						$stmt->bindValue($x, htmlspecialchars_decode($value['value']));
						//$stmt->bindValue($x, $value['value'], (($value['type'] == 'date' || $value['type'] == 'datetime') && !empty($value['value']) ? 'datetime' : null));
					}
					$x++;
				}
				$stmt->execute();
				$this->commitTransaction();
				$returnresult = true;
			}
			catch (\Exception $e) {
				$this->rollbackTransaction();
				var_dump($e->getMessage().' on line '. $e->getLine().' of '.$e->getFile());
			}
		}
		return $returnresult;
	}

	public function addOrUpdateDataTableEntries ( $appid, $docid, $view, $values_array, $parentdoc, $xml)
	{
		$query = "INSERT INTO view_$view (App_Id, Document_Id, Parent_Doc,";
		
		$columns = $this->fetchViewColumns ($view);

		
		foreach ( $columns as $fieldname ){
			$query .= " {$fieldname},";
		}
		
		$query = substr_replace($query, ') VALUES (?, ?, ?,', -1);
		for ($x = 0; $x < count($columns); $x++) {
			$query .= ' ?, ';
		}
		$query = substr_replace($query, ')', -2);

		$x = 5;
		$this->beginTransaction();

		$values_array = array_change_key_case($values_array, CASE_LOWER);

		try {
			$stmt = $this->prepare($query);
			$stmt->bindValue(1, $this->_app);
			$stmt->bindValue(2, $docid);
			$stmt->bindValue(3, $parentdoc);
			$crtd = array_key_exists("created", $values_array) ? $values_array["created"] : date("Y-m-d H:i:s");
			$stmt->bindValue(4, $crtd);
			for ($i = 1; $i < count($columns); $i++)
			{
				$value = "";
				$field_name = strtolower($columns[$i]);
				$datatype =  trim($xml->getElementsByTagName('dataType')->item($i)->nodeValue);
				if ( isset($values_array[$field_name])) {
					$value = $values_array[$field_name];
				}

				if (is_array($value))
				{
					$value = $this->un_serialize($value);
				}
				else {
					$value = false !== @unserialize($value) ? unserialize($value) : $value;
				}

				if (is_array($value) && $datatype == 'number') {
					$value = array_map('floatval', $value);
				}
				elseif (!is_array($value) && $datatype == 'number') {
					$value = floatval($value);
				}

				if ( $datatype == "date")
				{
					$dtvalue = $this->getValidDateTimeValue($value);
					if ( !empty($dtvalue)){
						$value = $dtvalue->format("Y-m-d H:i:s");
					}else{
						$value = null;
					}
					
				}

				if ( is_array($value))
				{
					$value = implode(",", $value);
				}
				$toret = htmlspecialchars_decode($value);
				$stmt->bindValue($x, htmlspecialchars_decode($value));
				$x++;
			}

			$stmt->execute();
			$this->commitTransaction();

		}catch (\Exception $e) {
			$this->rollbackTransaction();
			var_dump($e->getMessage().' on line '. $e->getLine().' of '.$e->getFile());
		}
		return true;
	}

	/**
	 * @deprecated (never used)
	 * Delete view columns form view table
	 * 
	 * @param string $view
	 * @param array $columns
	 * @param array $response_columns
	 */
	public function deleteViewColumns($view, $columns, $response_columns = array())
	{
		$driver = $this->getDriver();
		$query = "ALTER TABLE view_$view ";
		$query .= $driver == 'pdo_mysql' ? 'DROP ' : 'DROP COLUMN ';
		foreach ($columns as $field) {
			$query .= "$field,";
			if (!empty($response_columns) && in_array($field, $response_columns)) {
				$query .= "{$field}_id,";
			}
		}
		$query = substr_replace($query, ';', -1);
		$this->executeQuery($query);
	}
	
	/**
	 * Get all documents' records in view
	 * 
	 * @param string $view
	 * @return array|integer
	 */
	public function getAllViewDocuments($view, $sortBy = array(), $start = null, $count=null, $ret_count = false)
	{
		$query = "SELECT ".($ret_count === false ? '*' : 'COUNT(Document_Id) AS EXPR_CNTVAL')." FROM view_$view WHERE App_Id = ?";
		//$query .= ' AND (Parent_Doc IS NULL OR Parent_Doc IN (SELECT CDoc.Document_Id FROM view_'.$view.' AS CDoc WHERE CDoc.App_Id = ?))';

		if (!empty($sortBy) && $ret_count === false) 
		{
			$query .= ' ORDER BY Parent_Doc ASC, ';
			foreach ($sortBy as $column => $type) 
			{
				$query .= $column .' '. strtoupper($type).', '; 
			}
			$query = substr_replace($query, '', -2);
		}
		
		
		if ($ret_count === false && !is_null($start) && ! is_null($count) && $count != 0){
			$driver = $this->getDriver();
			if ($driver == 'pdo_mysql')
			{
				$query .= " LIMIT ".($start-1).", ".$count;
			}
			elseif ($driver == 'pdo_sqlsrv' or $driver =='sqlsrv')
			{
				$query .=  " OFFSET ".($start-1)." ROWS FETCH NEXT ".$count." ROWS ONLY";
			}	
		}

		$result = $this->fetchAll($query, array($this->_app));
		if (!empty($result[0])) {
			if ($ret_count === false)
				return $result;
			else 
				return $result[0]['EXPR_CNTVAL'];
		}
		return array();
	}
	
	/**
	 * Does view contain the document
	 * 
	 * @param string $view
	 * @param string $document
	 * @return boolean
	 */
	public function viewContains($view, $document)
	{
		$query = "SELECT COUNT(*) FROM view_$view WHERE App_Id = ? AND Document_Id = ?";
		$result = $this->fetchArray($query, array($this->_app, $document));
		if (!empty($result[0])) {
			return true;
		}
		return false;
	}
	
	/**
	 * Get documents by columns matched with values
	 * 
	 * @param string $view
	 * @param array $columns
	 * @param array $values
	 * @param boolean $exact_match
	 * @param integer $count
	 * @param integer $start
	 * @return array
	 */
	public function getDocumentsByColumnsKey($view, $columns, $values, $exact_match, $count = null, $start = null, $sort_by = null)
	{
		try {
			$query = "SELECT * FROM view_$view WHERE ";
			foreach ($columns as $c) 
			{
				if ($exact_match === true)
					$query .= "$c = ? AND ";
				else 
					$query .= "$c LIKE %?% AND ";
			}
			$query .= 'App_Id = ?';

			if ( !empty($sort_by))
			{
				$query .= " ORDER BY ";
				foreach ($sort_by as $sortcolumn => $sorttype)
				{
					$query .= $sortcolumn." ".$sorttype.", ";
				}
				$query = substr_replace($query, '', -2);
			
			}


			if ( !is_null($start) && ! is_null($count) && $count != 0){
				$driver = $this->getDriver();
				if ($driver == 'pdo_mysql')
				{
					$query .= " LIMIT ".($start-1).", ".$count;
				}
				elseif ($driver == 'pdo_sqlsrv' or $driver =='sqlsrv')
				{
					$query .=  " OFFSET ".($start-1)." ROWS FETCH NEXT ".$count." ROWS ONLY";
				}	
			}
			
			$params = array();

			for ($x = 0; $x < count($values); $x++)
			{
				//$stmt->bindValue($x+1, trim($values[$x]));
				array_push($params, trim($values[$x]));

			}
			array_push($params, trim($this->_app));
			//$stmt->bindValue($x+1, trim($this->_app));
			$result = $this->fetchAll($query, $params);
			
			if (is_null($count) || $count == 0)
				return $result;
			else 
				return array_slice($result, 0, $count);
		}
		catch (\Exception $e) {
			return array($e->getMessage());
		}
		return array();
	}
	
	
	/**
	 * Get documents by matching document id values
	 *
	 * @param string $view
	 * @param array $docids
	 * @return array
	 */
	public function getDocumentsByID($view, $docids)
	{

		if(!empty($docids)){
			try {
				$query = "SELECT * FROM view_$view WHERE Document_Id IN (?) AND App_Id = ?";										
				$values = [$docids,trim($this->_app)];
				$types = [\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_STR];
								
				$result = $this->fetchAll($query, $values, $types);				
					
				return $result;
			}
			catch (\Exception $e) {
				return array($e->getMessage());
			}
		}
		
		return array();
	}	
	
	
	/**
	 * Returns the values of a view column
	 * 
	 * @param string $view
	 * @param string $column
	 * @param array $where
	 * @param array $orderby
	 * @return array
	 */
	public function getColumnValues($view, $column, $where = array(), $orderby = array(), $returnraw = false)
	{
		$output = $params = array();
		$columnorig = $column;
		if ( is_array($column))
			$column = implode(",", $column);


		$query = "SELECT $column FROM view_$view ";
		if (!empty($where))
		{
			$query .= 'WHERE ';
			foreach ($where as $field => $value)
			{
				$query .= "$field = ?  AND ";
				$params[] = $value;
			}
			$query = substr_replace($query, '', -4);
		}
		
		if (!empty($orderby))
		{
			$query .= ' ORDER BY ';
			foreach ($orderby as $field => $value)
			{
				$query .= $field . ' ' . strtoupper($value).',';
			}
			$query = substr_replace($query, '', -1);
		}
		
		$result = $this->fetchAll($query, $params);
		if ( $returnraw){
			return $result;
		}
		if (!empty($result)) 
		{
			foreach ($result as $value)
			{

				if ( is_array($columnorig))
				{
					foreach ( $columnorig as $origcol){
						if ( isset($value[$origcol]) || is_null($value[$origcol]))
							$output[] = $value[$origcol];
						else
							$output = $value;
						}
				}else{
					if ( isset($value[$columnorig]) || is_null($value[$columnorig]))
						$output[] = $value[$column];
					else
						$output = $value;
				}
			}
		}
		return $output;
	}
	
	/**
	 * Drop view table
	 * 
	 * @param string $view
	 * @return boolean
	 */
	public function deleteView($view)
	{
		$driver = $this->getDriver();
		if ($driver == 'pdo_sqlsrv' or $driver =='sqlsrv') {
			$query = "IF OBJECT_ID('view_$view', 'U') IS NOT NULL ";
			$query .= "DROP TABLE view_$view";
		}
		else {
			$query = 'DROP TABLE IF EXISTS view_'.$view;
		}
		$stmt = $this->prepare($query);
		return $stmt->execute();
	}
	
	/**
	 * Truncate view table
	 * 
	 * @param string $view
	 * @return boolean
	 */
	public function truncateView($view)
	{
		$query = "TRUNCATE TABLE view_$view";
		$stmt = $this->prepare($query);
		return $stmt->execute();
	}
	
	/**
	 * Delete a document from a view
	 * 
	 * @param string $view
	 * @param string $document
	 * @return boolean
	 */
	public function deleteDocument($view, $document, $removechildren=true)
	{
		if($removechildren){
	        //delete parent doc and all first level child documents of the document
	        $query = 'DELETE FROM view_'.$view.' WHERE App_Id = ? AND (Document_Id = ? OR Parent_Doc = ?)';
		//TODO - should remove all sub children recursively
	    }else{
	        //delete regular document with the provided ID	        
	        $query = 'DELETE FROM view_'.$view.' WHERE App_Id = ? AND Document_Id = ?';
	    }
	    $stmt = $this->prepare($query);
		$stmt->bindValue(1, $this->_app);
		$stmt->bindValue(2, $document);
		if($removechildren){
		    $stmt->bindValue(3, $document);
		}
		$result = $stmt->execute();
			
		return $result;
	}
	
	/**
	 * Remove all child/response entries for the specified document from the view
	 * 
	 * @param string $view
	 * @param string $document
	 * @return boolean
	 */
	public function dispatchChildren($view, $document)
	{
	    $result = false;
	    
	    $idlist = [$document];
	    	    
	    do{
    	    $query = 'SELECT Document_Id FROM view_'.$view.' WHERE App_Id = :appid AND Parent_Doc IN (:parentids)';
    	    $values = ['appid' => trim($this->_app), 'parentids' => $idlist];
    	    $types = ['appid' => \PDO::PARAM_STR, 'parentids' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY];   
    	    
    	    $childids = $this->fetchColumn($query, $values, $types, 0);		
    	
    	    $childrenfound = false;
    	    if(!empty($childids) && is_array($childids) && count($childids) > 0){
    	        $childrenfound = true;
    	        $idlist = $childids;
    	            	        
    	        $query = 'DELETE FROM view_'.$view.' WHERE App_Id = :appid AND Document_Id IN (:childids)';
    	        $values = ['appid' => trim($this->_app), 'childids' => $childids];
    	        $types = ['appid' => \PDO::PARAM_STR, 'childids' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY];
    	        try{
        	       $this->executeQuery($query, $values, $types);
        	       $result = true;
    	        }catch (\Exception $e) {
    	            $result = false;
    	        }    	       
    	    }    		
	    }while($childrenfound);
		
		return $result;
	}
	
	/**
	 * Execute selection query
	 * 
	 * @param string $query
	 * @param array $params
	 * @param array $types
	 * @return array
	 */
	public function selectQuery($query, $params = array(), $types = array())
	{
		if (!empty($query))
		{
			$result = $this->fetchAll($query, $params, $types);
			if (!empty($result))
				return $result;
		}
		return array();
	}
	
	/**
	 * Executes an insertion query
	 * 
	 * @param string $view
	 * @param string $query
	 * @param string $app
	 * @param string $document
	 * @param array $params
	 * @return boolean
	 */
	public function viewInsertionQuery($view, $query, $app, $document, $params = array())
	{
		if (true === $this->viewExists($view))
		{
			$stmt = $this->prepare($query);
			$stmt->bindValue('appId', $app);
			$stmt->bindValue('docId', $document);
			if (!empty($params))
			{
				foreach ($params as $key => $value)
				{
					if (!empty($value['value'])) {
						if ($value['type'] == 1) {
							$stmt->bindValue($key, $value['value'], 'datetime');
						}
						else {
							$stmt->bindValue($key, $value['value']);
						}
					}
					else {
						$stmt->bindValue($key, null);
					}
				}
			}
			
			return $stmt->execute();
		}
		return false;
	}
	
	public function un_serialize($value)
	{
		foreach ($value as $key => $v) {
			if (!is_array($v)) {
				if (false !== @unserialize($v)) {
					$tmp = unserialize($v);
					$value[$key] = is_object($tmp) ? $tmp : $tmp[0];
				}
			}
			else {
				foreach ($v as $index => $rvalue) {
					if (false !== @unserialize($rvalue)) {
						$tmp = unserialize($rvalue);
						$value[$key][$index] = is_object($tmp) ? $tmp : $tmp[0];
					}
				}
			}
		}
		return $value;
	}

	/**
	 * Prepare a SQL query
	 * 
	 * @param \DOMDocument $xml
	 * @param array $values_array
	 * @return array
	 */
	private function prepareQuery2($xml, $values_array)
	{
		$output = array('columns' => array(), 'values' => array());
		if (!empty($xml->getElementsByTagName('columnsscript')->length)) {
			$script = $xml->getElementsByTagName('columnsscript')->item(0)->nodeValue;
		}

		$values_array[0] = array_change_key_case($values_array[0], CASE_LOWER);
		$twig_script = '{{ f_SetViewScripting() }}'.$script;
		$twig_env = $this->generateTwigEnv($twig_script);
		$json = $twig_env->render('docovaindex.html', array(
			//'application' => $this->_app,
			'docvalues' => $values_array[0],
			//'document' => $document,
			//'user' => $user
		));
		//echo $json."\n";

		$columns_values = json_decode(htmlspecialchars_decode($json), true);
		$columns = $xml->getElementsByTagName('xmlNodeName');
		for ($x = 0; $x < $columns->length; $x++)
		{
			$show_multi = trim($xml->getElementsByTagName('showMultiAsSeparate')->item($x)->nodeValue) == '1' ? true : false;
			$datatype = trim($xml->getElementsByTagName('dataType')->item($x)->nodeValue);
			if (array_key_exists($columns->item($x)->nodeValue, $columns_values)) {
				$value = $columns_values[$columns->item($x)->nodeValue];
				if (is_array($value))
				{
					$value = $this->un_serialize($value);
				}
				else {
					$value = false !== @unserialize($value) ? unserialize($value) : $value;
				}

				if (is_array($value) && $datatype == 'number') {
					$value = array_map('floatval', $value);
				}
				elseif (!is_array($value) && $datatype == 'number') {
					$value = floatval($value);
				}
				
				if (is_array($value) && $show_multi === false)
				{
					$len = count($columns_values[$columns->item($x)->nodeValue]);
					if ($len > 1 && ($datatype == 'date' || $datatype == 'datetime' || $datatype == 'number')) {
						$value = null;
					}
					else {
						if ($len === 1) {
							$value = $value[0];
						}
						else {
							$value = implode(', ', array_filter($value));
						}
					}
					$value = empty($value) ? null : $value;
				}
				elseif (is_array($value) && $show_multi !== false && count($value) == 1) {
					$value = $value[0];
					$value = empty($value) ?  null : $value;
				}
				if(($datatype == 'date' || $datatype == 'datetime') && empty($value)){
					$value = '1000-01-01 00:00:00';
				}
				
				$output['columns'][] = array('field' => $columns->item($x)->nodeValue);
				$output['values'][] = array('type' => $datatype, 'value' => $value);
				
				//fetch response column and its value
				if (!empty($xml->getElementsByTagName('responseFormula')->item($x)->nodeValue) && array_key_exists('RESP_'.$columns->item($x)->nodeValue, $columns_values))
				{
					$value = $columns_values['RESP_'.$columns->item($x)->nodeValue];
					if (is_array($value)) {
						foreach ($value as $key => $V) {
							if (is_object($V)) {
								$value[$key] = $V->format($this->_dateformat);
							}
						}
						$value = implode(';', $value);
					}
					$output['columns'][] = array('field' => 'RESP_'.$columns->item($x)->nodeValue);
					$output['values'][] = array('type' => 'text', 'value' => $value);
				}
			}
			else {
				$output['columns'][] = array('field' => $columns->item($x)->nodeValue);
				$output['values'][] = array('value' => 'KEEP THE OLD VALUE AS IS');
			}
		}

		return $output;
	}

	/**
	 * @deprecated (not used in new method)
	 * Get document field value
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents|Docova\DocovaBundle\ObjectModel\DocovaDocument $document
	 * @param string $field
	 * @param string $type
	 * @return string|NULL
	 */
	private function getDocFieldValue($document, $field, $type)
	{
		$query = 'SELECT ';
		if ($type == 'date' || $type == 'datetime') 
			$query .= 'Field_Value AS vl FROM tb_form_datetime_values ';
		elseif ($type == 'numeric')
			$query .= 'Field_Value AS vl FROM tb_form_numeric_values ';
		else
			$query .= 'Summary_Value AS vl FROM tb_form_text_values ';
		
		$query .= 'AS V JOIN tb_design_elements as E ON V.Field_Id = E.id ';
		$query .= 'WHERE E.Field_Name = ? AND V.Doc_Id = ? ';
		$result = $this->fetchAll($query, array($field, $document->getId()));
		
		if (!empty($result[0]) && count($result) == 1) {
			return $result[0]['vl'];
		}
		return null;
	}

	/**
	 * @deprecated (not used)
	 * Parse string for plus operand to convert it to concat or add function
	 *
	 * @param string $string
	 * @return string
	 */
	private function lexerParser($string)
	{
		if (false === strpos($string, '(')) {
			$string = str_replace('+', ',', $string);
			return '$docova->concatOrAdd(array(' . $string . '))';
		}
		else {
			$plus = null;
			$parenthesis = $this->getParenthesisPos($string);
			preg_match_all('/\+/', $string, $plus, PREG_OFFSET_CAPTURE);
			if (!empty($plus[0]))
			{
				$plus = $plus[0];
				$found = array();
				foreach ($plus as $pos) {
					foreach ($parenthesis as $par) {
						if ($pos[1] > $par[0] && $pos[1] < $par[1]) {
							$found[] = $par;
							break;
						}
					}
				}
				if (!empty($found))
				{
					$found = array_map("unserialize", array_unique(array_map("serialize", $found)));
					foreach ($found as $pos) {
						$string = substr($string, 0, $pos[0]) . '$docova->concatOrAdd(' . substr($string, $pos[0]+1, $pos[1] - $pos[0]) . ')' . substr($string, $pos[1]+1);
					}
				}
				else {
					$string = '$docova->concatOrAdd(array(' . str_replace('+', ',', $string) . '))';
				}
			}
			return $string;
		}
	}
	
	/**
	 * @deprecated (not used)
	 * Generates an array of positions of open and close parenthesis
	 *
	 * @param string $string
	 * @param integer $openPos
	 * @param array $output
	 * @return number[]
	 */
	private function getParenthesisPos($string, $openPos = null, $output = array())
	{
		$x = empty($openPos) ? 0 : ($openPos + 1);
		$len = strlen($string);
		while ($x < $len)
		{
			if ($string[$x] == ')') {
				$output[] = array($openPos, $x);
				return $output;
			}
			elseif ($string[$x] == '(') {
				$output = $this->getParenthesisPos($string, $x, $output);
				$tmp = end($output);
				$x = $tmp[1];
			}
			$x++;
		}
		return $output;
	}
	
	/**
	 * @deprecated
	 * Generates a valid datetime object from a datetime string or returns null
	 * 
	 * @param string $date_string
	 * @return NULL|\DateTime
	 */
	private function getValidDateTimeValue($date_string)
	{
	    //-- check if a date time object was passed if so leave it unchanged
		if ($date_string instanceof \DateTime) {
			return $date_string;
		}
		
		if (empty($date_string)){
		    return null;
		}
		
		$hasdate = false;
		$hastime = false;
		$hasseconds = false;
		
		//-- generate php date format
		$format = $this->_dateformat ;
		$format = str_replace(array('YYYY', 'MM', 'DD'), array('Y', 'm', 'd'), $format);
	
		//-- parse the string into a date component array
		$parsed = date_parse($date_string);
		
		//-- check for time portion
		$is_period = (false === stripos(strtolower($date_string), ' am') && false === stripos(strtolower($date_string), ' pm')) ? false : true;
		$time = (false !== $parsed['hour'] && false !== $parsed['minute']) ? ($is_period ? 'h:i' : 'H:i') : '';
		$time .= (!empty($time) && substr_count($date_string, ":") > 1 ? ':s' : '');
		$time .= (!empty($time) && $is_period ? ' A' : '');

		
		if(!empty($format) && false !== $parsed['year'] && false !== $parsed['month'] && false !== $parsed['day']){
            $hasdate = true;
		}		
		if(!empty($time)){
    		$hastime = true;
		}
		
		$combinedformat = "!";
		if($hasdate){
		    $combinedformat .= $format;
		}
		if($hasdate && $hastime){
		    $combinedformat .= " ";
		}
		if($hastime){
		    $combinedformat .= $time;
		}
		
		//-- generate a date time object
		$value = \DateTime::createFromFormat($combinedformat, $date_string);
		
		if(!empty($value) && !$hasdate){
		    $value->setDate(1000,01,01);		    
		}elseif(!empty($value) && !$hastime){
		    $value->setTime(0, 0, 0);
		}

		$format = $parsed = $time = $is_period = $combinedformat = null;

		return empty($value) ? null : $value;
	}
	
	/**
	 * Initialize and pre-load custom twig extensions, tokens, nodes and etc
	 * 
	 * @return \Twig_Environment
	 */
	private function generateTwigEnv($template)
	{
		$loader = new \Twig_Loader_Array(array(
			'docovaindex.html' => $template,
		));
		$twig_env = new \Twig_Environment($loader, array('auto_reload' => true));
		$docova = new ObjectModel($this->_docova);
		//$twig_env->setLoader(new \Twig_Loader_String);
		$twig_env->addExtension(new \Twig_Extension_StringLoader());
		$twig_env->addExtension(new DocovaExtension($docova, true));
		$twig_env->addExtension(new DocovaConcatExtension());
		$docova = null;
		$twig_env->addExtension(new DocovaTwigExtension());
		return $twig_env;
	}


	public function indexDataTableDocument($docid, $values_array, $appid, $view_id, $parentdocid, $perspective)
	{
		$isdocinview = false;
		$isdocinview = $this->viewContains($view_id, $docid);
		$xml = new \DOMDocument();
		$xml->loadXML($perspective);
		if ( $isdocinview)
		{
			$existing = $this->getColumnValues($view_id, array("Created") , array ("Document_Id" => $docid), null, true);
			$values_array["Created"] = $existing[0]["Created"];
			$this->deleteDocument($view_id, $docid);
		}
		try {
			if (!$this->addOrUpdateDataTableEntries( $appid, $docid, $view_id, $values_array, $parentdocid, $xml) ) {
				throw new \Exception('Insertion failed!');
			}
			return 1;
		}
		catch (\Exception $e) {
			var_dump($e->getMessage().' on line '. $e->getLine().' of '.$e->getFile());
			return 0;
		}
	}

	/**
	 * @param string $docid
	 * @param array $values_array
	 * @param string $appid
	 * @param string $viewid
	 * @param string $viewpers
	 * @param \Twig_Environment $twig
	 * @param boolean $isTruncated
	 * @param string $viewconvq
	 * @return number
	 */
	public function indexDocument2($docid, $values_array, $appid, $viewid, $viewpers, $twig =null, $isTruncated = false, $viewconvq = null)
	{
		$isdocinview = false;

		if ($isTruncated === false)
			$shoulddocbeinview = $this->isDocMatchView2($appid, $values_array, $viewconvq,  $twig);
		else 
			$shoulddocbeinview = true;
		
		$view_id = str_replace('-', '', $viewid);
		if ($isTruncated === false)
			$isdocinview = $this->viewContains($view_id, $docid);

		$xml = new \DOMDocument();
		$xml->loadXML($viewpers);
		if ( $shoulddocbeinview && !$isdocinview ){
			//add this doc to this view
			try {
				if (!$this->addOrUpdateViewEntries2($docid, $view_id, $xml, $values_array, true, false)) {
					throw new \Exception('Insertion failed!');
				}
				return 1;
			}
			catch (\Exception $e) {
				var_dump($e->getMessage().' on line '. $e->getLine().' of '.$e->getFile());
				return 0;
			}
		}else if ( !$shoulddocbeinview && $isdocinview){
			$this->beginTransaction();
			try {
				//remove from this view
				$this->deleteDocument($view_id, $docid, true);
				$this->commitTransaction();
				return 2;
			}catch (\Exception $e) {
				$this->rollbackTransaction();
			}
		}else if ( $shoulddocbeinview && $isdocinview ) {
			//update the doc in this view
			try {
				if (! $this->addOrUpdateViewEntries2($docid, $view_id, $xml, $values_array, false, true)) {
					throw new \Exception('Update failed.');
				}
				return 3;
			}
			catch (\Exception $e) {
				echo "\n{$e->getMessage()} on line {$e->getLine()} in {$e->getFile()}\n";
				return 0;
			}
		}
		return 4;
	}

	/**
	 * Check if the document matches the view selection query
	 * 
	 * @param string $appid
	 * @param array $values_array
	 * @param string $query
	 * @param \Twig_Environment $twig
	 * @return boolean
	 */
	public function isDocMatchView2($appid, $values_array, $query, $twig = null)
	{
		$result = false;
		if (empty($appid) || empty($values_array) || empty($query)  ) return $result;
		if (false === strpos($query, '{') && false === strpos($query, '%') && !in_array(strtolower($query), array('1', 'true'))) return $result;

		//$values_array = $this->_em->getRepository('DocovaBundle:Documents')->getDocFieldValues($document->getId(), $this->_dateformat, $this->_shortname, true);
		$values_array[0] = array_change_key_case( $values_array[0], CASE_LOWER);

		try {
			if (is_null($twig))
			{
				$template = '{{ f_SetViewScripting() }}{% docovascript "output:string" %} '.$query.'{% enddocovascript %} ';
				$twig = $this->generateTwigEnv($template);
				$output = $twig->render('docovaindex.html', array(
					//'document' => $document,
					'docvalues' => $values_array[0],
					//'application' => $appid,
					//'user' => $user
				));
			}
			else {
				$template = $twig->createTemplate('{{f_SetViewScripting() }}{% docovascript "output:string" %} '.$query.'{% enddocovascript %} ');
				$output = $template->render(array(
					//'document' => $document,
					'docvalues' => $values_array[0],
					//'application' => $appid,
					//'user' => $user
				));
			}
			$output = trim($output);
			$template = $values_array = null;
			if (!empty($output))
			{
				$result = true;
			}
		}
		catch (\Exception $e) {
			// ** CAUSE TO 500 ERROR IN PROD MODE
			// $logger = $this->get('monolog.logger.docova');
			// $err = !empty($e->getMessage()) ? $e->getMessage() : $e->getTraceAsString();
			// $logger->error('On File: ' . $e->getFile() . ' Line: '. $e->getLine(). ' - ' . $err);
			return false;
		}
		return $result;
	}
}