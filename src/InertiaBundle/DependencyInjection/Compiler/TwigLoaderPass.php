<?php declare(strict_types=1);

namespace InertiaBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TwigLoaderPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        if (!$container->hasDefinition('twig.loader.filesystem') && !$container->hasAlias('twig.loader.filesystem')) {
            return;
        }

        $definition = $container->findDefinition('twig.loader.filesystem');
        $definition->addMethodCall('addPath', [
            __DIR__ . '/../../Resources/views', 'InertiaBundle'
        ]);
    }
}
