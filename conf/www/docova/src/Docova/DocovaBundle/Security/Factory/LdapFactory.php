<?php
namespace Docova\DocovaBundle\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class LdapFactory implements SecurityFactoryInterface
{
	public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
	{
		// authentication provider
		$authProviderId = $this->createAuthProvider($container, $id, $config, $userProviderId);

		// authentication listener
		$listenerId = $this->createListener($container, $id, $config, $userProviderId);

		return array($authProviderId, $listenerId, $defaultEntryPointId);
	}

	public function getPosition()
	{
		return 'pre_auth';
	}

	public function getKey()
	{
		return 'docova';
	}

	public function addConfiguration(NodeDefinition $node)
	{
		// Without Configuration
	}

	protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
	{
		$providerId = 'security.authentication.provider.docova.' . $id;

		$container
			->setDefinition($providerId, new DefinitionDecorator('docova.security.authentication.provider'))
			->replaceArgument(1, $id) // Provider Key
			->replaceArgument(2, new Reference($userProviderId));

		return $providerId;
	}

	protected function createListener(ContainerBuilder $container, $id, $config, $userProvider)
	{
		$listenerId = 'security.authentication.listener.form';

		$listener   = new DefinitionDecorator($listenerId);
		$listener->replaceArgument(4, $id);
		$listener->replaceArgument(5, $config);

		$listenerId .= '.' . $id;
		$container
			->setDefinition($listenerId, $listener)
		;

		return $listenerId;
	}
}