<?php

namespace Docova\DocovaBundle\Security\User;

use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
//use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\HttpFoundation\Session\Session;
use Docova\DocovaBundle\Extensions\Oauthorization;
use Docova\DocovaBundle\Entity\UserAccounts;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManager;

/**
 * Class to pre-authenticate user through Oauth2 library (in this case Magium AAD)
 * @author javad_rahimi
 */
class OauthKeyAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationSuccessHandlerInterface
{
	private $_params;
	private $_em;
	
	public function __construct($params, EntityManager $em)
	{
		$this->_params = $params;
		$this->_em = $em;
	}
	
	/**
	 * Check if the token provider matches with current provider
	 * 
	 * {@inheritDoc}
	 * @see \Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface::supportsToken()
	 */
	public function supportsToken(TokenInterface $token, $providerKey)
	{
		return ($token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey);
	}
	
	/**
	 * Authenticate user token through Oauth2 library
	 * 
	 * {@inheritDoc}
	 * @see \Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface::authenticateToken()
	 */
	public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
	{
		/*
		if (!($userProvider instanceof OauthProvider))
		{
			throw new \InvalidArgumentException(sprintf('The user provider must be an instance of OauthProvider (%s was given).', get_class($userProvider)));
		}
		*/
		$username = $token->getCredentials();

		$user = $token->getUser();
		
		if ($user instanceof UserAccounts)
		{
			return new PreAuthenticatedToken($user, $username, $providerKey, $user->getRoles());
		}
		
		if (!$username) {
			throw new CustomUserMessageAuthenticationException(sprintf('Access token for "%s" does not exist.', $username));
        }

		$user = $userProvider->loadUserByUsername($username);
		
		$session = new Session();
		$session->set(Security::LAST_USERNAME, $username);

		return new PreAuthenticatedToken($user, $username, $providerKey, $user->getRoles());
	}
	
	/**
	 * Create a valid Symfony token
	 * 
	 * {@inheritDoc}
	 * @see \Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface::createToken()
	 */
	public function createToken(Request $request, $providerKey)
	{
		// look for an apikey query parameter
		$oauth_key = $request->query->get('code');
		$oauth_state = $request->query->get('state');
		//$oauth_session = $request->query->get('session_state');
		
		if (!$oauth_key || empty($this->_params)) {
			return null;
			// or throw new BadCredentialsException();
		}
		
		$csrf_key = null;
		$session = new Session();
		if ($session->has('oauth2state')) {
			$csrf_key = $session->get('oauth2state');
		}
		
		// Check given state against previously stored one to mitigate CSRF attack
		if ($csrf_key != $oauth_state)
		{
			return null;
		}
		
		$params = [
			'clientId' => $this->_params['client_id'],
			'clientSecret' => $this->_params['client_secret'],
			'redirectUri' => str_replace('http://', 'https://', $request->getUriForPath('/Docova'))
		];
		
		$oauthorization = new Oauthorization($params);
		$token = $oauthorization->getAuthorizationCode($oauth_key);
		$user = $oauthorization->getUserInfo($token);
		
		if (!empty($user))
		{
			$username = !empty($user['userPrincipalName']) ? $user['userPrincipalName'] : $user['mail'];
			return new PreAuthenticatedToken('anon.', $username, $providerKey);
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface::onAuthenticationSuccess()
	 */
	public function onAuthenticationSuccess(Request $request, TokenInterface $token)
	{
		$user_profile = $this->_em->getRepository('DocovaBundle:UserProfile')->createQueryBuilder('P')
			->where('P.User = :uid')
			->setParameter('uid', $token->getUser()->getId())
			->getQuery()
			->getSingleResult();
		
		$user_profile->setLastModifiedDate(new \DateTime());
		$this->_em->flush();
		
		$response = new RedirectResponse($request->getUriForPath('/Docova'));
		return $response;
	}
}