<?php

/**
 * Class to handle making curl connections to Docova for the import process
 * @author sandeep Jyoti
 *        
 */

namespace Docova\DocovaBundle\Extensions;
class DocovaDominoSession
{
	private $username = null;
	private $password = null;
	private $dominourl = null;
	private $tmp = null;
	public function __construct($username, $password, $url) 
	{

		// Initialize the session variables
		$this->username = $username;
		$this->password = $password;
		$this->url = $url;
		$this->set_sessions();
		
	}


	public function getUsername() 
	{
		return $this->username;
	}

	public function setUsername($username) 
	{
		$this->username = $username;
		return $this;
	}

	public function getElementHtml ($elemtype, $apppath, $elemname ){
		if (empty($_SESSION['docova_cookie']) || empty($_SESSION['docova_url']))
		{
			$this->set_sessions();
		}
		else {
			//$this->validate_session();
		}

		try {
			$tmpurl = $_SESSION['docova_url']."/DesignImportServices?openAgent";

			$ch2 = curl_init();

			$xml = "<Request>";
			$xml .= "<Action>GETELEMENTHTML</Action>";
			$xml .= "<apppath>".$apppath."</apppath>";
			$xml .= "<elemtype>".$elemtype."</elemtype>";
			$xml .= "<elemname><![CDATA[".$elemname."]]></elemname>";
			$xml .= "</Request>";
			$xml = rawurlencode($xml);

			curl_setopt($ch2, CURLOPT_URL, $tmpurl);
			if (!empty($_SESSION['is_http'])) {
				curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
			}
			curl_setopt($ch2, CURLOPT_POST, true);
			curl_setopt($ch2, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch2, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch2, CURLOPT_ENCODING, '');
			curl_setopt($ch2, CURLOPT_HEADER, false);
			curl_setopt($ch2, CURLOPT_COOKIE, 'DomAuthSessId='.$_SESSION['docova_cookie']);

			curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
			'Cache-Control: max-age=0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
			'Accept-Encoding: gzip,deflate,sdch',
			'Content-Type: application/xml',
			'Accept-Language: en-US,en;q=0.8',
			'Referer: http'.$_SESSION['is_http'].'://'.$tmpurl,
			));
			curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch2, CURLOPT_FRESH_CONNECT, true);
				
			//curl_setopt($ch2, CURLOPT_PROXY, '192.168.0.16:8888');
			//execute post
			$result = curl_exec($ch2);
			curl_close($ch2);


			$ret = "";
			if (!empty($result)) {

				//remove all the <nl> tags...these are put in to represent new lines in the source code
				$result = str_replace("%3Cnl%3E", "\n", $result);

				$xml_obj =  new \DOMDocument('1.0', 'UTF-8');
				$loaded = $xml_obj->loadXML($result, LIBXML_NOERROR+LIBXML_ERR_FATAL+LIBXML_ERR_NONE);
					
				if (!empty($loaded)) 
				{
					if (!empty($xml_obj->getElementsByTagName('Results')->length))
					{
						foreach ($xml_obj->getElementsByTagName('Result') as $item)
						{
							if ($item->nodeValue == "OK") 
							{
								$ret= $result;
								break;
							}
						}
					}	
				}
				
			}
			return $ret;

		}
		catch (\Exception $e) {
			throw new \Exception('Failed to get Elements: <br />' .$e->getMessage());
			return null;
		}


	}
	
	
	public function getElementDXL ($elemtype, $apppath, $elemname ){
	    if (empty($_SESSION['docova_cookie']) || empty($_SESSION['docova_url']))
	    {
	        $this->set_sessions();
	    }
	    else {
	        //$this->validate_session();
	    }
	    
	    try {
	        $tmpurl = $_SESSION['docova_url']."/DesignServices?openAgent";
	        
	        $ch2 = curl_init();
	        
	        $xml = "<Request>";
	        $xml .= "<Action>GETDXL</Action>";
	        $xml .= "<server></server>";
	        $xml .= "<path>".$apppath."</path>";
	        $xml .= "<elemtype>".$elemtype."</elemtype>";
	        $xml .= "<elemname><![CDATA[".$elemname."]]></elemname>";
	        $xml .= "</Request>";
	        $xml = rawurlencode($xml);
	        
	        curl_setopt($ch2, CURLOPT_URL, $tmpurl);
	        if (!empty($_SESSION['is_http'])) {
	            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
	        }
	        curl_setopt($ch2, CURLOPT_POST, true);
	        curl_setopt($ch2, CURLOPT_POSTFIELDS, $xml);
	        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch2, CURLOPT_COOKIESESSION, true);
	        curl_setopt($ch2, CURLOPT_ENCODING, '');
	        curl_setopt($ch2, CURLOPT_HEADER, false);
	        curl_setopt($ch2, CURLOPT_COOKIE, 'DomAuthSessId='.$_SESSION['docova_cookie']);
	        
	        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
	            'Cache-Control: max-age=0',
	            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	            'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
	            'Accept-Encoding: gzip,deflate,sdch',
	            'Content-Type: application/xml',
	            'Accept-Language: en-US,en;q=0.8',
	            'Referer: http'.$_SESSION['is_http'].'://'.$tmpurl,
	        ));
	        curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, false);
	        curl_setopt($ch2, CURLOPT_FRESH_CONNECT, true);
	        
	        //curl_setopt($ch2, CURLOPT_PROXY, '192.168.0.16:8888');
	        //execute post
	        $result = curl_exec($ch2);
	        curl_close($ch2);
	        
	        
	        $ret = "";
	        if (!empty($result)) {
	            
	            //remove all the <nl> tags...these are put in to represent new lines in the source code
	            $result = str_replace("%3Cnl%3E", "\n", $result);
	            
	            $xml_obj =  new \DOMDocument('1.0', 'UTF-8');
	            $loaded = $xml_obj->loadXML($result, LIBXML_NOERROR+LIBXML_ERR_FATAL+LIBXML_ERR_NONE);
	            
	            if (!empty($loaded))
	            {
	                if (!empty($xml_obj->getElementsByTagName('Results')->length))
	                {
	                    foreach ($xml_obj->getElementsByTagName('Result') as $item)
	                    {
	                        if ($item->nodeValue == "OK")
	                        {
	                            $ret= $result;
	                            break;
	                        }
	                    }
	                }
	            }
	            
	        }
	        return $ret;
	        
	    }
	    catch (\Exception $e) {
	        throw new \Exception('Failed to get Elements: <br />' .$e->getMessage());
	        return null;
	    }
	    
	    
	}


	public function getImageData($imagename, $apppath)
	{
		if (empty($_SESSION['docova_cookie']) || empty($_SESSION['docova_url']))
		{
			$this->set_sessions();
		}
		else {
			//$this->validate_session();
		}
		try {
			$tmarr = parse_url($_SESSION['docova_url']);

			$tmpurl = $tmarr["scheme"]."://".$tmarr["host"].(isset($tmarr['port']) ? ':' . $tmarr['port'] : '')."/".$apppath."/".$imagename."?openimageresource";
			$ch2 = curl_init();


			curl_setopt($ch2, CURLOPT_URL, $tmpurl);
			if (!empty($_SESSION['is_http'])) {
				curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
			}
			curl_setopt($ch2, CURLOPT_POST, false);
			//curl_setopt($ch2, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch2, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch2, CURLOPT_ENCODING, '');
			curl_setopt($ch2, CURLOPT_HEADER, false);
			curl_setopt($ch2, CURLOPT_COOKIE, 'DomAuthSessId='.$_SESSION['docova_cookie']);

			curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
			'Cache-Control: max-age=0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
			'Accept-Encoding: gzip,deflate,sdch',
			'Content-Type: application/xml',
			'Accept-Language: en-US,en;q=0.8',
			'Referer: http'.$_SESSION['is_http'].'://'.$tmpurl,
			));
			curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch2, CURLOPT_FRESH_CONNECT, true);
				
			//curl_setopt($ch2, CURLOPT_PROXY, '10.0.0.169:8888');
			//execute post
			$result = curl_exec($ch2);
			curl_close($ch2);
			
			if (!empty($result)) {
				$check = substr($result, 0, 15) ;
				if ( $check  != '<!doctype html>')
					return $result;
			}
			return "";

		}
		catch (\Exception $e) {
			throw new \Exception('Failed to get Elements: <br />' .$e->getMessage());
			return null;
		}

	}
	
	
	public function proxyRequest($xmlrequest)
	{	    
	    if (empty($_SESSION['docova_cookie']) || empty($_SESSION['docova_url']))
	    {
	        $this->set_sessions();
	    }
	    else {
	        //$this->validate_session();
	    }

	    try {
	        $tmpurl = $_SESSION['docova_url']."/DesignServices";
	        
	        $ch2 = curl_init();
	        
	        curl_setopt($ch2, CURLOPT_URL, $tmpurl);
	        if (!empty($_SESSION['is_http'])) {
	            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
	        }
	        curl_setopt($ch2, CURLOPT_POST, true);
	        curl_setopt($ch2, CURLOPT_POSTFIELDS, $xmlrequest);
	        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch2, CURLOPT_COOKIESESSION, true);
	        curl_setopt($ch2, CURLOPT_ENCODING, '');
	        curl_setopt($ch2, CURLOPT_HEADER, false);
	        curl_setopt($ch2, CURLOPT_COOKIE, 'DomAuthSessId='.$_SESSION['docova_cookie']);
	        
	        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
	            'Cache-Control: max-age=0',
	            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	            'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
	            'Accept-Encoding: gzip,deflate,sdch',
	            'Content-Type: application/xml',
	            'Accept-Language: en-US,en;q=0.8',
	            'Referer: http'.$_SESSION['is_http'].'://'.$tmpurl,
	        ));
	        curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, false);
	        curl_setopt($ch2, CURLOPT_FRESH_CONNECT, true);
	        
	        //curl_setopt($ch2, CURLOPT_PROXY, '10.0.0.169:8888');
	        //execute post
	        $result = curl_exec($ch2);
	        curl_close($ch2);
	        
	        if (!empty($result)) {
	            $xml_obj =  new \DOMDocument('1.0', 'UTF-8');
	            $loaded = $xml_obj->loadXML($result, LIBXML_NOERROR+LIBXML_ERR_FATAL+LIBXML_ERR_NONE);
	            
	            if (!empty($loaded)) {
	                if (!empty($xml_obj->getElementsByTagName('Results')->length))
	                {
                        return $xml_obj;
	                }
	            }
	            
	        }	        
	    }
	    catch (\Exception $e) {
	        throw new \Exception('Failed to get data: <br />' .$e->getMessage());
	    }
	    
	    return null;
	}
	

	public function getElementList($elemtype, $apppath, $direct)
	{
		if (empty($_SESSION['docova_cookie']) || empty($_SESSION['docova_url']))
		{
			$this->set_sessions();
		}
		else {
			//$this->validate_session();
		}
		$elemlist = "";
		try {
			$tmpurl = $_SESSION['docova_url']."/".($direct ? "DesignServices" : "DesignImportServices")."?openAgent";

			$ch2 = curl_init();

			$xml = "<Request>";
			$xml = $xml."<Action>".($direct ? "GETDESIGNELEMENTS" : "GETELEMENTLIST")."</Action>";
			if($direct){
			    $xml = $xml."<server></server>";
			    $xml = $xml."<path>".$apppath."</path>";
			}else{
    				$xml = $xml."<apppath>".$apppath."</apppath>";
			}
			$xml = $xml."<elemtype>".$elemtype."</elemtype>";
			$xml = $xml."</Request>";	
				

			curl_setopt($ch2, CURLOPT_URL, $tmpurl);
			if (!empty($_SESSION['is_http'])) {
				curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
			}
			curl_setopt($ch2, CURLOPT_POST, true);
			curl_setopt($ch2, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch2, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch2, CURLOPT_ENCODING, '');
			curl_setopt($ch2, CURLOPT_HEADER, false);
			curl_setopt($ch2, CURLOPT_COOKIE, 'DomAuthSessId='.$_SESSION['docova_cookie']);

			curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
			'Cache-Control: max-age=0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
			'Accept-Encoding: gzip,deflate,sdch',
			'Content-Type: application/xml',
			'Accept-Language: en-US,en;q=0.8',
			'Referer: http'.$_SESSION['is_http'].'://'.$tmpurl,
			));
			curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch2, CURLOPT_FRESH_CONNECT, true);
				
			//curl_setopt($ch2, CURLOPT_PROXY, '10.0.0.169:8888');
			//execute post
			$result = curl_exec($ch2);
			curl_close($ch2);
			
			if (!empty($result)) {
				$xml_obj =  new \DOMDocument('1.0', 'UTF-8');
				$loaded = $xml_obj->loadXML($result, LIBXML_NOERROR+LIBXML_ERR_FATAL+LIBXML_ERR_NONE);
					
				if (!empty($loaded)) {
					if (!empty($xml_obj->getElementsByTagName('Results')->length))
					{
						foreach ($xml_obj->getElementsByTagName('Result') as $item)
						{
							if ($item->nodeValue == "OK") 
							{
								$elemlist = trim($item->nextSibling->nodeValue);
								break;
							}
						}
					}
				}
				
			}
			return $elemlist;

		}
		catch (\Exception $e) {
			throw new \Exception('Failed to get Elements: <br />' .$e->getMessage());
			return null;
		}

		
	}
	
	private function set_sessions()
	{
		
		$_SESSION['d_username'] = $this->username;;

		$refere_url = $this->url;
		$_SESSION['is_http'] = (strpos($refere_url, 'https') !== false) ? 's' : '';
		$matches = array();
		
		preg_match('@^(?:http'.$_SESSION['is_http'].'://)?([^/]+)@i', $this->url, $matches);
		if (empty($matches[0])) {
			throw new \Exception('Incorrect DOCOVA Login URL.');
		}
		
		$redirect_to = str_replace($matches[0], '', $this->url);
		$ch = curl_init();
			
		$cred_data = array('username' => $this->username, 'password' => $this->password, 'redirectto' => $redirect_to);
			
		$turl = $matches[0].'/names.nsf?Login';
		curl_setopt($ch, CURLOPT_URL, $turl);
		if (!empty($_SESSION['is_http'])) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($cred_data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Cache-Control: max-age=0',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
		'Accept-Encoding: gzip,deflate,sdch',
		'Accept-Language: en-US,en;q=0.8',
		'Referer: http'.$_SESSION['is_http'].'://'.$this->url,
		));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
			
		//curl_setopt($ch, CURLOPT_PROXY, '10.0.0.169:8888');
		//execute post
		$result = curl_exec($ch);
		curl_close($ch);
			
		if (!empty($result))
		{
			preg_match('/DomAuthSessId=/', $result, $matches);
			if (!empty($matches[0]))
			{
				$header_cookie = strstr($result, 'DomAuthSessId=');
				$header_cookie = strstr($header_cookie, ';', true);
				$header_cookie = explode("=", $header_cookie)[1];
				$_SESSION['docova_cookie'] = $header_cookie;
				$_SESSION['docova_url'] = $this->url;
				//$_SESSION['docova_base_url'] = $matches[0];
			}
		}
		else {
			throw new \Exception('Could not log into the DOCOVA.');
		}
	}

	private function validate_session()
	{
		$ch = curl_init();
		
		curl_setopt($ch,CURLOPT_URL, 'http'.$_SESSION['is_http'].'://'.$_SESSION['docova_url'].'/GetUserLibraries?ReadForm');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIE, 'DomAuthSessId='.$_SESSION["docova_cookie"]);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Cache-Control: max-age=0',
		'Cookie: DomAuthSessId='.$_SESSION['docova_cookie'],
		));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
			
		$result = curl_exec($ch);
		curl_close($ch);
		if ($result !== false) {
			if (strpos($result, 'DOCTYPE') !== false || strpos($result, '<html') !== false || strpos($result, '<body') !== false) {
				$this->set_sessions();
			}
		}
	}
	

}