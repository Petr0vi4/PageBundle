<?php

namespace Creonit\PageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigGlobalsPass implements CompilerPassInterface {

    public function process(ContainerBuilder $container)
    {

        $twig = $container->getDefinition('twig');
        $twig->addMethodCall('addGlobal', ['creonit_page', new Reference('creonit_page')]);

    }
} 