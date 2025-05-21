<?php declare(strict_types=1);

namespace InertiaBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class InertiaExtension extends Extension
{
    public function getAlias(): string
    {
        return 'inertia';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->buildParameters($container, $config);

        $this->loadConfiguration($container);

        $this->registerTwigPaths($container);
    }

    private function loadConfiguration(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
    }

    private function buildParameters(ContainerBuilder $container, array $config): void
    {
        foreach ($config as $name => $value) {
            if (!is_array($value)) {
                $container->setParameter(
                    vsprintf('%s.%s', [$this->getAlias(), $name]),
                    $value
                );
                continue;
            }

            foreach ($value as $subName => $subValue) {
                $container->setParameter(
                    vsprintf('%s.%s.%s', [$this->getAlias(), $name, $subName]),
                    $subValue
                );
            }
        }
    }

    private function registerTwigPaths(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('twig')) {
            return;
        }

        $container->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__ . '/../Resources/views' => 'Inertia',
            ]
        ]);
    }

}
