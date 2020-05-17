<?php

namespace Creonit\PageBundle;

use Creonit\PageBundle\DependencyInjection\Compiler\TwigGlobalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CreonitPageBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TwigGlobalsPass);
    }
}
