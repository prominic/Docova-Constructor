<?php

namespace Docova\DocovaBundle\Extensions;

use Docova\DocovaBundle\Entity\UserAccounts;
use Doctrine\ORM\EntityManager;
use function GuzzleHttp\json_decode;

/**
 * Contains two servers communication classes plus common import/export functions
 * @author javad_rahimi
 */
class CommunicationServices extends TransactionManager
{
	private $_ch;
	private $_cookie;
	
	/**
	 * @var string
	 * Destination server path/URL
	 */
	protected $destination;

	/**
	 * @var integer
	 * Destination server port (if applicable)
	 */
	protected $port;

	/**
	 * @var UserAccounts
	 * Current active user object
	 */
	protected $user;
	
	public function __construct(EntityManager $em, UserAccounts $user)
	{
		$this->user = $user;
		parent::__construct($em);
	}
	
	/**
	 * Set destination server properties
	 * 
	 * @param array $options
	 */
	public function setOptions($options)
	{
		if (!empty($options['path']))
		{
			if (substr($options['path'], -7) === '/Docova') {
				$options['path'] = $options['path'].'/DeployServices';
			}
			elseif (substr($options['path'], -8) === '/Docova/') {
				$options['path'] = $options['path'].'DeployServices';
			}
			elseif (substr($options['path'], -10) === '/HomeFrame') {
				$options['path'] = str_replace('/HomeFrame', '/DeployServices', $options['path']);
			}
			$this->destination = $options['path'];
		}
		if (!empty($options['port']))
		{
			$this->port = $options['port'];
		}
	}
	
	/**
	 * Submit a (zip) file to the server
	 * 
	 * @param string $filename
	 * @return boolean|string
	 */
	public function postFile($filename)
	{
		if (!empty($filename))
		{
			$xml = '<Request><Action>UPLOADZIP</Action>';
			$xml .= '<AppFile><![CDATA['. basename($filename) .']]></AppFile>';
			$xml .= '<Username><![CDATA['. $this->user->getUsername() .']]></Username>';
			$xml .= '<DnAbbreviated><![CDATA['. $this->user->getUserNameDnAbbreviated() .']]></DnAbbreviated>';
			$xml .= '</Request>';
			$xml = rawurlencode($xml);

			$data = [
				'file' => realpath($filename),
				'xmlRequest' => $xml
			];
			
			$res = $this->communicate(false, $data);
			$res = json_decode($res, true);
			if ($res['status'] == 'OK') {
				return true;
			}
			else {
				return $res['errmsg'];
			}
		}
		
		return false;
	}
	
	/**
	 * Send request to start extracting uploaded file in the server
	 * 
	 * @param string $filename
	 * @return boolean|string
	 */
	public function extractAndPublish($filename)
	{
		if (!empty($filename))
		{
			$xml = '<Request><Action>EXTRACTZIP</Action>';
			$xml .= '<AppFile><![CDATA['. basename($filename) .']]></AppFile>';
			$xml .= '<Username><![CDATA['. $this->user->getUsername() .']]></Username>';
			$xml .= '<DnAbbreviated><![CDATA['. $this->user->getUserNameDnAbbreviated() .']]></DnAbbreviated>';
			$xml .= '</Request>';
			$xml = rawurlencode($xml);
			
			$res = $this->communicate(false, $xml, ['Content-Type: text/xml']);
			$res = json_decode($res, true);
			
			if ($res['status'] == 'OK') {
				return true;
			}
			else {
				return $res['errmsg'];
			}
		}
		
		return false;
	}
	
	/**
	 * Get app update status (progress percentage)
	 * 
	 * @param string $filename
	 * @return mixed|string
	 */
	public function updateStatus($filename)
	{
		$xml = '<Request><Action>GETUPDATESTATUS</Action>';
		$xml .= '<AppFile><![CDATA['. basename($filename) .']]></AppFile>';
		$xml .= '<Username><![CDATA['. $this->user->getUsername() .']]></Username>';
		$xml .= '<DnAbbreviated><![CDATA['. $this->user->getUserNameDnAbbreviated() .']]></DnAbbreviated>';
		$xml .= '</Request>';
		$xml = rawurlencode($xml);
		
		$res = $this->communicate(false, $xml, ['Content-Type: text/xml']);
		$res = json_decode($res, true);
		
		if ($res['status'] == 'OK' && $res['percentage']) {
			return $res['percentage'];
		}
		elseif (!empty($res['errmsg'])) {
			return $res['errmsg'];
		}
		else {
			return 'Oops! App extraction on the server failed.';
		}
	}
	
	/**
	 * Kill the cookies and close connection
	 * 
	 * @return string
	 */
	public function closeConnection()
	{
		$xml = '<Request><Action>CLOSECONNECTION</Action>';
		$xml .= '<Username><![CDATA['. $this->user->getUsername() .']]></Username>';
		$xml .= '<DnAbbreviated><![CDATA['. $this->user->getUserNameDnAbbreviated() .']]></DnAbbreviated>';
		$xml .= '</Request>';
		$xml = rawurlencode($xml);

		try {
			$res = $this->communicate(false, $xml, ['Content-Type: text/xml']);
			$res = json_decode($res, true);
		}
		catch (\Exception $e) {
			return ['status' => 'FAILED'];
		}

		return $res['status'];
	}

	/**
	 * Check if current user has access to push to the server, fetch the session cookie on success
	 * 
	 * @throws \Exception
	 * @return boolean|string
	 */
	public function checkUserAccess($application)
	{
		try {
			$xml = '<Request><Action>CHECKUSERACCESS</Action>';
			$xml .= '<AppID>'. $application .'</AppID>';
			$xml .= '<Username><![CDATA['. $this->user->getUsername() .']]></Username>';
			$xml .= '<DnAbbreviated><![CDATA['. $this->user->getUserNameDnAbbreviated() .']]></DnAbbreviated>';
			$xml .= '</Request>';
			$xml = rawurlencode($xml);
			$res = $this->communicate(true, $xml, ['Content-Type: text/xml']);
			
			if ($res !== true) {
				throw new \Exception('You have insufficient right to push this app to the selected server.');
			}
			return true;
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	
	private function communicate($authenticate = false, $data = null, $headers = [])
	{
		if (empty($this->destination)) {
			throw new \Exception('Destination server path is not configured.');
		}
		
		$this->_ch = curl_init();
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
		
		$url = $this->destination;
		if (!empty($this->port))
		{
			/* ALTERNATIVE **
			$parsed_url = parse_url($this->destination);
			$url = str_replace($parsed_url, $parsed_url.':'.$this->port, $this->destination);
			*/
			curl_setopt($this->_ch, CURLOPT_PORT, $this->port);
		}

		curl_setopt($this->_ch, CURLOPT_URL, $url);
		if (!empty($headers))
		{
			curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $headers);
		}
		
		if (!empty($data))
		{
			if (is_array($data) && array_key_exists('file', $data)) {
				$type = mime_content_type($data['file']);
				$file = new \CURLFile($data['file'], $type);
				$data['file'] = $file;
			}
			
			curl_setopt($this->_ch, CURLOPT_POST, 1);
			curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $data);
		}
		
		if ($authenticate === true)
		{
			curl_setopt($this->_ch, CURLOPT_COOKIEFILE, '');
			curl_setopt($this->_ch, CURLOPT_COOKIELIST, 'ALL');
			
			curl_exec($this->_ch);
			$cookies = curl_getinfo($this->_ch, CURLINFO_COOKIELIST);
			if (empty($cookies[0]) || false === stripos($cookies[0], 'ValidId')) {
				return false;
			}
			
			$cookies = strstr($cookies[0], 'ValidId');
			$cookies = substr(trim(str_replace('ValidId', '', $cookies)), 0, 32);
			$this->_cookie = $cookies;

			curl_close($this->_ch);
			return true;
		}
		
		if (!empty($this->_cookie)) {
			curl_setopt($this->_ch, CURLOPT_COOKIE, $this->_cookie);
			$output = curl_exec($this->_ch);
			
			return $output;
		}
		
		return false;
	}
}