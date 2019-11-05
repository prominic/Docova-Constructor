<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Docova\DocovaBundle\Extensions\ExportAppZip;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Docova\DocovaBundle\Extensions\CommunicationServices;

class DeploymentController extends Controller
{
	private $user;
	private $settings;
	
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
		
		$this->settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->settings = $this->settings[0];
	}
	
	public function pushAppToServerAction(Request $request)
	{
		$post = new \DOMDocument();
		$output = new \DOMDocument();
		$post->loadXML(urldecode($request->getContent()));
		$app = $post->getElementsByTagName('AppID')->item(0)->nodeValue;
		try {
			$this->initialize();
			$em = $this->getDoctrine()->getManager();
			$app = $em->getRepository('DocovaBundle:Libraries')->findOneBy(['id' => $app, 'Trash' => false, 'isApp' => true]);
			if (empty($app)) {
				throw new \Exception('Application not found.');
			}
				
			$server = $post->getElementsByTagName('ServerPath')->item(0)->nodeValue;
			$port = !empty($post->getElementsByTagName('ServerPort')->item(0)->nodeValue) ? $post->getElementsByTagName('ServerPort')->item(0)->nodeValue : null;
				
			$export = new ExportAppZip($em, $app->getId(), $this->user, $this->container->get('kernel')->locateResource('@DocovaBundle'));
			$export->setOptions([
				'path' => $server,
				'port' => $port
			]);
			$res = $export->checkUserAccess();
			if ($res !== true) {
				throw new \Exception($res);
			}
			$res = $export->collectAppMetaData();
			if ($res !== true) {
				throw new \Exception($res);
			}
				
			$res = $export->collectAppFiles();
			if ($res !== true) {
				throw new \Exception($res);
			}
				
			$file = $export->createZipFile();
			if (!is_array($file)) {
				throw new \Exception($file);
			}
			
			$res = $export->postFile($file['ZipName']);
			if ($res !== true) {
				throw new \Exception('Failed to upload application. Message: '.$res);
			}

			$export->cleanup($file['ZipName']);
			$res = $export->extractAndPublish($file['ZipName']);
			if ($res !== true) {
				throw new \Exception($res);
			}
			
			$status = $export->updateStatus($file['ZipName']);
			if (!is_numeric($status)) {
				throw new \Exception($status);
			}
			
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', 'SUCCESS:'.$status);
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		catch (\Exception $e) {
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'FAILED');
			$attrib = $output->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $e->getMessage());
			$attrib = $output->createAttribute('ID');
			$attrib->value = 'ErrMsg';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
		}

		$export->closeConnection();
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($output->saveXML());
		return $response;
	}
	
	public function deployServicesAction(Request $request)
	{
		$content = $request->getContent();
		if (empty($content)) {
			$content = $request->request->get('xmlRequest');
		}
		$post = new \DOMDocument();
		$post->loadXML(rawurldecode($content));
		$action = $post->getElementsByTagName('Action')->item(0)->nodeValue;
		$results = [];
		if (method_exists($this, "deploy$action"))
		{
			$results = call_user_func(array($this, "deploy$action"), $post, $request);
		}
		else {
			$results['status'] = 'FAILED';
			$results['errmsg'] = 'Invalid action!';
		}
		
		$response = new JsonResponse();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($results));
		return $response;
	}
	
	public function getServerUpdateStatusAction(Request $request)
	{
		$post = new \DOMDocument();
		$post->loadXML(urldecode($request->getContent()));
		$appid = $post->getElementsByTagName('AppFile')->item(0)->nodeValue;
		$server = $post->getElementsByTagName('ServerPath')->item(0)->nodeValue;
		$port = !empty($post->getElementsByTagName('ServerPort')->item(0)->nodeValue) ? $post->getElementsByTagName('ServerPort')->item(0)->nodeValue : null;
		$post = null;
		$output = new \DOMDocument();
		$this->initialize();
		$em = $this->getDoctrine()->getManager();
		$communicate = new CommunicationServices($em, $this->user);
		$communicate->setOptions([
			'path' => $server,
			'port' => $port
		]);
		$res = $communicate->checkUserAccess($appid);
		if ($res !== true) {
			throw new \Exception($res);
		}
		$res = $communicate->updateStatus($appid);
		if (is_numeric($res)) {
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'OK');
			$att = $output->createAttribute('ID');
			$att->value = 'Status';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $res);
			$att = $output->createAttribute('ID');
			$att->value = 'Ret1';
			$newnode->appendChild($att);
			$root->appendChild($newnode);
		}
		else {
			$root = $output->appendChild($output->createElement('Results'));
			$newnode = $output->createElement('Result', 'FAILED');
			$attrib = $output->createAttribute('ID');
			$attrib->value = 'Status';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
			$newnode = $output->createElement('Result', $res);
			$attrib = $output->createAttribute('ID');
			$attrib->value = 'ErrMsg';
			$newnode->appendChild($attrib);
			$root->appendChild($newnode);
		}
	
		$response = new Response();
		$response->headers->set('Content-Type', 'text/xml');
		$response->setContent($output->saveXML());
		return $response;
	}
	
	/**
	 * Check if passed user has sufficient rights to push to the server. Generates temp cookies on success
	 * 
	 * @param \DOMDocument $post
	 * @param Request $request
	 * @return array
	 */
	private function deployCHECKUSERACCESS($post, $request)
	{
		$output = [];
		try {
			$username = $post->getElementsByTagName('Username')->item(0)->nodeValue;
			$dn_abbreviated = $post->getElementsByTagName('DnAbbreviated')->item(0)->nodeValue;
			//$app = $post->getElementsByTagName('AppID')->item(0)->nodeValue;

			$user = $this->securityCheck($username, $dn_abbreviated);
			if ($user !== false && $user instanceof \Docova\DocovaBundle\Entity\UserAccounts)
			{
				$output['status'] = 'OK';
				//@note: maybe we need to change setcookie to session_set_cookie_params
				setcookie('ValidId', md5($user->getId()), time()+7200, $_SERVER["REQUEST_URI"], $request->getHost(), false !== stripos($request->getHttpHost(), 'https'), true);
			}
			else {
				throw new \Exception('You do not have sufficient right to push the app.');
			}
		}
		catch (\Exception $e) {
			$output['status'] = 'FAILED';
			$output['errmsg'] = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * @param \DOMDocument $post
	 * @param Request $request
	 * @return array
	 */
	private function deployUPLOADZIP($post, $request)
	{
		$output = [];
		$repository_path = $this->get('kernel')->getRootDir() . '/../web/upload/Repository/';
		try {
			$username = $post->getElementsByTagName('Username')->item(0)->nodeValue;
			$dn_abbreviated = $post->getElementsByTagName('DnAbbreviated')->item(0)->nodeValue;
			$user = $this->securityCheck($username, $dn_abbreviated);
			if ($user === false || !($user instanceof \Docova\DocovaBundle\Entity\UserAccounts)) {
				throw new \Exception('You do not have suffiecient right to push the app.');
			}

			if ($request->files->count()) {
				$app = $post->getElementsByTagName('AppFile')->item(0)->nodeValue;
				if (empty($app)) {
					throw new \Exception('Invalid application entry.');
				}
				$file_obj = $request->files->get('file');
				$file_name	= html_entity_decode($file_obj->getClientOriginalName(), ENT_COMPAT, 'UTF-8');
				if ($file_name == $app) {
					if (!is_dir($repository_path)) {
						mkdir($repository_path);
					}
					
					$res = $file_obj->move($repository_path, $file_name);
					if (!empty($res)) {
						$output['status'] = 'OK'; 
					}
					else {
						throw new \Exception('Failed to upload app file(s).');
					}
				}
				else {
					throw new \Exception('Invalid app file was uploaded! >> '.$file_name);
				}
			}
			else {
				throw new \Exception('No app file was uploaded!');
			}
		}
		catch (\Exception $e) {
			$output['status'] = 'FAILED';
			$output['errmsg'] = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Start extracting zip file and updating design
	 * 
	 * @param \DOMDocument $post
	 * @param Request $request
	 * @return array
	 */
	private function deployEXTRACTZIP($post, $request)
	{
		$output = [];
		$response = null;
		try {
			$username = $post->getElementsByTagName('Username')->item(0)->nodeValue;
			$dn_abbreviated = $post->getElementsByTagName('DnAbbreviated')->item(0)->nodeValue;
			$appid = $post->getElementsByTagName('AppFile')->item(0)->nodeValue;
			$user = $this->securityCheck($username, $dn_abbreviated);
			if ($user === false || !($user instanceof \Docova\DocovaBundle\Entity\UserAccounts)) {
				throw new \Exception('You do not have suffiecient right to push the app.');
			}
			
			if (file_exists($this->container->get('kernel')->getRootDir().'/../zipextraction.bat'))
			{
				file_put_contents($this->container->get('kernel')->getRootDir().'/../extract.log', '');
				chdir($this->container->get('kernel')->getRootDir().'/../');
				if (substr(php_uname(), 0, 7) == 'Windows'){
					$res = popen('start /b zipextraction.bat "'.$appid.'" "'.$user->getId().'"', 'r');
					if($res !== false) {
						pclose($res);
						$res = false;
					}
				}
				else {
					$res = exec('zipextraction.bat', $response);   
				}
			}
			else {
				$res = 'File does not exist.';
			}

			if (!empty($res))
				throw new \Exception('App extraction on the server failed.');
			elseif (!empty($response))
				throw new \Exception(trim($response));
		
			$content = file_get_contents($this->container->get('kernel')->getRootDir().'/../extract.log');
			if (empty($content) || false !== stripos($content, 'Status:OK')) {
				$appid = false !== stripos($appid, '.zip') ? str_replace('.zip', '', $appid) : $appid;
				file_put_contents($this->container->get('kernel')->getRootDir().'/../log/extract_'. $appid .'.log', 'Status:OK;Published:1');
				$output['status'] = 'OK';
			}
			else {
				throw new \Exception($content);
			}
		}
		catch (\Exception $e) {
			$output['status'] = 'FAILED';
			$output['errmsg'] = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Kill the cookie and close connection
	 * 
	 * @param \DOMDocument $post
	 * @param Request $request
	 * @throws \Exception
	 * @return string[]|NULL[]
	 */
	private function deployCLOSECONNECTION($post, $request)
	{
		$output = [];
		try {
			$username = $post->getElementsByTagName('Username')->item(0)->nodeValue;
			$dn_abbreviated = $post->getElementsByTagName('DnAbbreviated')->item(0)->nodeValue;
			$user = $this->securityCheck($username, $dn_abbreviated);
			if ($user !== false && $user instanceof \Docova\DocovaBundle\Entity\UserAccounts)
			{
				unset($_COOKIE['ValidId']);
				setcookie('ValidId', '', time()-3600, $_SERVER["REQUEST_URI"], $request->getHost(), false !== stripos($request->getHttpHost(), 'https'), true);
				$output['status'] = 'OK';
			}
			else {
				throw new \Exception('You do not have sufficient right to complete this request.');
			}
		}
		catch (\Exception $e) {
			$output['status'] = 'FAILED';
			$output['errmsg'] = $e->getMessage();
		}
		return $output;
	}
	
	/**
	 * Get app update status on the server
	 * 
	 * @param \DOMDocument $post
	 * @param Request $request
	 * @return array
	 */
	private function deployGETUPDATESTATUS($post, $request)
	{
		$output = [];
		try {
			$username = $post->getElementsByTagName('Username')->item(0)->nodeValue;
			$dn_abbreviated = $post->getElementsByTagName('DnAbbreviated')->item(0)->nodeValue;
			$appid = $post->getElementsByTagName('AppFile')->item(0)->nodeValue;
			$user = $this->securityCheck($username, $dn_abbreviated);
			if ($user === false || !($user instanceof \Docova\DocovaBundle\Entity\UserAccounts)) {
				throw new \Exception('You do not have suffiecient right for this request.');
			}

			$appid = false !== stripos($appid, '.zip') ? str_replace('.zip', '', $appid) : $appid;
			if (file_exists($this->container->get('kernel')->getRootDir().'/../log/extract_'. $appid .'.log'))
			{
				$content = file_get_contents($this->container->get('kernel')->getRootDir().'/../log/extract_'. $appid .'.log');
				if (empty($content) || false === stripos($content, ';Published:'))
				{
					throw new \Exception('Pushing app to the server failed! Check logs or contact Admin for more details. [Empty Content]');
				}
				
				$value = intval(str_replace(';Published:', '', strstr($content, ';Published:')));
				if (empty($value)) {
					throw new \Exception('Pushing app to the server failed! Check logs or contact Admin for more details.[No Value]');
				}
				
				$output['status'] = 'OK';
				$output['percentage'] = $value;
			}
 		}
 		catch (\Exception $e) {
 			$output['status'] = 'FAILED';
 			$output['errmsg'] = $e->getMessage();
 		}
		
		return $output;
	}
	
	/**
	 * Check user security
	 * 
	 * @param string $username
	 * @param string $dn_abbreviated
	 * @throws \Exception
	 * @return boolean|\Docova\DocovaBundle\Entity\UserAccounts
	 */
	private function securityCheck($username, $dn_abbreviated)
	{
		$user = $this->findUser($dn_abbreviated, 'userNameDnAbbreviated');
		if (false === $user) {
			$user = $this->findUser($username, 'username');
		}
			
		if (empty($user)) {
			throw new \Exception('Inavlid user.');
		}
		
		if ($user->getUserProfile()->getCanCreateApp())
		{
			return $user;
		}
		else {
			$token = new UsernamePasswordToken($user, 'none', 'none', $user->getRoles());
			$isAdmin = $this->container->get('security.access.decision_manager')->decide($token, array('ROLE_ADMIN'));
			$token = null;
			if ($isAdmin) {
				return $user;
			}
		}
		return false;
	}
	
	/**
	 * Find user by DN abbreviated or Username
	 * 
	 * @param string $value
	 * @param string $field
	 * @return boolean|\Docova\DocovaBundle\Entity\UserAccounts
	 */
	private function findUser($value, $field)
	{
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy([$field => $value, 'Trash' => 0]);
		if (empty($user)) {
			$user = false;
		}
		return $user;
	}
}