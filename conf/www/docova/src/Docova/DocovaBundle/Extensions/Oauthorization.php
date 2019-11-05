<?php
namespace Docova\DocovaBundle\Extensions;

use TheNetworg\OAuth2\Client\Provider\Azure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class to handle Oauth2 connections
 * @author javad_rahimi
 */
class Oauthorization extends Azure
{
	private $oauth_code;
	private $oauth_state;
	private $request;
	
	public function __construct($options = array(), Request $request = null, $collaborators = array())
	{
		$this->oauth_code = !empty($request) ? $request->query->get('code') : $_GET['code'];
		$this->oauth_state = !empty($request) ? $request->query->get('state') : $_GET['state'];
		$this->request = $request;
		parent::__construct($options, $collaborators);
	}
	
	public function initiate()
	{
		$session = new Session();
		$session->clear();
		if (empty($this->oauth_code))
		{
			$auth_url = $this->getAuthorizationUrl();
			$session->set('oauth2state', $this->getState());
			$session->save();
			return $auth_url;
		}
		elseif (empty($this->oauth_state) || ($session->has('oauth2state') && $this->oauth_state !== $session->get('oauth2state')))
		{
			if ($session->has('oauth2state')) {
				$session->remove('oauth2state');
			}
			throw new \InvalidArgumentException();
		}
		return null;
	}
	
	/**
	 * Get OAuth authorization code
	 * 
	 * @param string $code
	 * @throws \InvalidArgumentException
	 * @return \League\OAuth2\Client\Token\AccessToken
	 */
	public function getAuthorizationCode($code = null)
	{
		if (empty($this->oauth_code) && !empty($code)) {
			$this->oauth_code = $code;
		}
		
		if (!empty($this->oauth_code))
		{
			$oauth_token = $this->getAccessToken('authorization_code',[
				'code' => $this->oauth_code
			]);
			
			return $oauth_token;
		}

		throw new \InvalidArgumentException();
	}
	
	/**
	 * Get current user details/info
	 * 
	 * @param \League\OAuth2\Client\Token\AccessToken $token
	 * @throws \InvalidArgumentException
	 * @return NULL|mixed
	 */
	public function getUserInfo($token)
	{
		try {
			$curr_user = $this->get('me', $token);
			return $curr_user;
		}
		catch (\Exception $e) {
			throw new \InvalidArgumentException();
		}
	}
	
	/**
	 * Logout and redirect to destination url
	 * 
	 * @param string $url
	 */
	public function logOut($url)
	{
		$logout_url = $this->getLogoutUrl($url);
		header('Location: '.$logout_url);
	}
}