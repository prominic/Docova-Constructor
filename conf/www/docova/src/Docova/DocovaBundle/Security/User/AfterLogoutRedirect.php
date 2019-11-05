<?php

namespace Docova\DocovaBundle\Security\User;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class to handle redirection after successful logout
 * @author javad_rahimi
 */
class AfterLogoutRedirect implements LogoutSuccessHandlerInterface
{
	private $_router;
	
	public function __construct(Router $router)
	{
		$this->_router = $router;
	}
	
	public function onLogoutSuccess(Request $request)
	{
		$logout_url = $this->_router->generate('docova_logout_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);
		$response = new RedirectResponse($logout_url);
		return $response;
	}
}