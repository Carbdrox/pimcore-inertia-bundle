<?php declare(strict_types=1);

namespace InertiaBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use InertiaBundle\DependencyInjection\InertiaExtension;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class InertiaBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    protected function getComposerPackageName(): string
    {
        return 'carbdrox/pimcore-inertia-bundle';
    }

    public function getInstaller(): ?InstallerInterface
    {
        if (!$this->container || !$installer = $this->container->get(Installer::class)) {
            error_log('Error while Installing Inertia Bundle: "Installer not found!"');
            return null;
        }

        return $installer instanceof InstallerInterface ? $installer : null;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new InertiaExtension();
    }
}
