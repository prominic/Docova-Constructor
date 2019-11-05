<?php
namespace Docova\DocovaBundle\Security\User;

use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;
//use Docova\DocovaBundle\Security\User\adLDAP;

class LdapProvider extends UserAuthenticationProvider
{
	private $user_provider;
	private $parameters;
	
	public function __construct(UserCheckerInterface $userChecker, $providerKey, UserProviderInterface $userProvider, $params = array())
	{
		parent::__construct($userChecker, $providerKey);

		$this->user_provider = $userProvider;
		$this->parameters = $params;
		
	}

    /**
     * {@inheritdoc}
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        try {
            $user = $this->user_provider->loadUserByUsername($username);

            if (!$user instanceof UserInterface) {
                throw new AuthenticationServiceException('The user provider must return a UserInterface object.');
            }
            return $user;
        } catch (\Symfony\Component\Security\Core\Exception\UsernameNotFoundException $notFound) {
            throw $notFound;
        } catch (\Exception $repositoryProblem) {
            throw new AuthenticationServiceException($repositoryProblem->getMessage(), 0, $repositoryProblem);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
     	
    	$currentUser = $token->getUser();
    	
    	if(empty($_SESSION['directory'])){
    		if ($this->user_provider instanceof \Symfony\Component\Security\Core\User\ChainUserProvider)
    		{
    			$providers = $this->user_provider->getProviders();
    			if(!empty($providers)){
    				foreach($providers as $up){
    					if($up instanceof \Docova\DocovaBundle\Security\User\UserProvider){
    						$up->initLdapSettings();
    					}
    				}    				
    			}
    		}else if($this->user_provider instanceof \Docova\DocovaBundle\Security\User\UserProvider){
    			$this->user_provider->initLdapSettings();    					
    		}
    	}
    	
        if ($currentUser instanceof UserInterface) 
        {
	        if ($this->user_provider instanceof \Docova\DocovaBundle\Security\User\UserProvider) 
	        {
	        	$ldap_obj = new adLDAP(array(
	        		'domain_controllers' => $_SESSION['directory'], 
	        		'domain_port' => $_SESSION['port'], 
	        		'base_dn' => $_SESSION['base_dn'],
					'ad_username'=>$this->parameters['ldap_username'],
					'ad_password'=>$this->parameters['ldap_password']
	        	));
	        	if (!$ldap_obj->authenticate('CN='.$currentUser->getUsername(), $currentUser->getPassword()) && !$ldap_obj->authenticate($currentUser->getUsername(), $currentUser->getPassword())) 
	        	{
	        		if (!empty($this->parameters['ldap_domain'])) 
	        		{
	        			if (!$ldap_obj->authenticate($this->parameters['ldap_domain'].'\\'.$currentUser->getUsername(), $currentUser->getPassword())) {
	        				throw new BadCredentialsException('The credentials were changed from another session.');
	        			}
	        		}
	        		else {
		        		throw new BadCredentialsException('The credentials were changed from another session.');
	        		}
		        }
	        }
	        else {
	        	if ($currentUser->getUsername() !== $user->getUsername()) {
		        	throw new BadCredentialsException('The credentials were changed from another session.');
	        	}
	        }
	        
        } else {
       		if (!$presentedPassword = $token->getCredentials()) {
       			throw new BadCredentialsException('The presented password cannot be empty.');
       		}
       		
	        if ($user->getPassword() != md5(md5(md5($presentedPassword)))) 
	        {
	       		$ldap_obj = new adLDAP(array(
	       			'domain_controllers' => $_SESSION['directory'], 
	       			'domain_port' => $_SESSION['port'], 
	       			'base_dn' => $_SESSION['base_dn'],
					'ad_username'=>$this->parameters['ldap_username'],
					'ad_password'=>$this->parameters['ldap_password']
	       		));
	       		
		        if (!$ldap_obj->authenticate('CN='.$token->getUsername(), $presentedPassword) && !$ldap_obj->authenticate($token->getUsername(), $presentedPassword)) 
		        {
		        	if (!empty($this->parameters['ldap_domain'])) 
	        		{
	        			if (!$ldap_obj->authenticate($this->parameters['ldap_domain'].'\\'.$token->getUsername(), $presentedPassword)) {
	        				throw new BadCredentialsException('The credentials were changed from another session.');
	        			}
	        		}
	        		else {
		        		throw new BadCredentialsException('The presented password is invalid.');
	        		}
		        }
	        }
        }
    }
}