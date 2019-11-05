<?php
namespace Docova\DocovaBundle\Security\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Doctrine\ORM\EntityManager;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler {

	protected $_session;
	protected $_em;
	
	public function __construct( HttpUtils $httpUtils, array $options, EntityManager $em, $session ) 
	{
		$this->_em = $em;
		$this->_session = $session;
		parent::__construct( $httpUtils, $options );
	}

	public function onAuthenticationSuccess( Request $request, TokenInterface $token ) 
	{
		$user_profile = $this->_em->getRepository('DocovaBundle:UserAccounts')->find($token->getUser()->getId());
		$user_profile = $user_profile->getUserProfile();
		$user_profile->setLastModifiedDate(new \DateTime());
		$this->_em->flush();
		
		$this->_session->set('user_locale', $user_profile->getLanguage());
		$r = (array_key_exists("REMOTE_HOST",$_SERVER) ? $_SERVER["REMOTE_HOST"] : (array_key_exists("REMOTE_ADDR", $_SERVER) ? gethostbyaddr($_SERVER["REMOTE_ADDR"]) : ""));
		$this->_session->set('IP_Address',$r);
		$this->_session->set('currentUser', base64_encode($token->getUser()->getId()));
		$response = parent::onAuthenticationSuccess( $request, $token );
		return $response;
	}
}