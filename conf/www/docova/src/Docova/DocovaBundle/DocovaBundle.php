<?php
namespace Docova\DocovaBundle;

use Docova\DocovaBundle\Security\Factory\LdapFactory;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DocovaBundle extends Bundle
{
	public function build(ContainerBuilder $container)
	{
		parent::build($container);

		$extension = $container->getExtension('security');
		$extension->addSecurityListenerFactory(new LdapFactory());
	}
}
