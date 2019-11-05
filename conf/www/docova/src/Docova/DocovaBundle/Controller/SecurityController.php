<?php
namespace Docova\DocovaBundle\Controller;

use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Docova\DocovaBundle\Extensions\Oauthorization;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityController extends Controller
{
	public function loginAction(Request $request)
	{
		$error = '';
		$homepage = false;
        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(Security::AUTHENTICATION_ERROR);
        } else {
            if ($request->getSession()->get(Security::AUTHENTICATION_ERROR)) {
            	$error = 'Invalid credential is entered.';
            }
        }
        
        $global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
        $global_settings = $global_settings[0];
        if (!empty($global_settings)) {
        	$homepage = $global_settings->getStartupOption();
        }
      
		return $this->render('DocovaBundle:Default:login.html.twig', array(
				'messages' => $error,
				'username' => $request->getSession()->get(Security::LAST_USERNAME),
				'homepage' => $homepage,
				'hidden_id' => mt_rand(),
				'sso_enabled' => $global_settings->getSsoAuthentication()
		));
	}
	
	public function loggedOutAction()
	{
		return $this->render('DocovaBundle:Default:loggedOut.html.twig');
	}
	
	public function mobileAuthorizeAction(Request $request)
	{
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		if (!$user instanceof UserInterface) 
		{
			throw new AccessDeniedException();
		}

		return $this->render('DocovaBundle:Default:login.html.twig', array(
				'messages' => '',
				'username' => $request->getSession()->get(Security::LAST_USERNAME),
				'homepage' => '',
				'hidden_id' => mt_rand()
		));
	}
	
	public function oauthLoginAction(Request $request)
	{
        $global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
        $global_settings = $global_settings[0];
        if (!$global_settings->getSsoAuthentication() || $request->query->get('l') == 'int') {
        	return $this->redirectToRoute('docova_login');
        }
        
        $uri = str_replace('http://', 'https://', $this->generateUrl('docova_homepage', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $uri = rtrim($uri, '/');
        
		$params = [
			'clientId' => $this->container->getParameter('client_id'),
			'clientSecret' => $this->container->getParameter('client_secret'),
			'redirectUri' => $uri
		];
		
		$oauth = new Oauthorization($params, $request);
		$output = $oauth->initiate();
		
		if (!empty($output)) {
			return $this->redirect($output);
		}
		
		$response = new Response();
		return $response;
	}
}