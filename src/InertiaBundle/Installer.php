<?php declare(strict_types=1);
namespace InertiaBundle;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller implements InstallerInterface
{

    private string $configDir = PIMCORE_PROJECT_ROOT . '/config/routes';

    private Filesystem $filesystem;

    public function __construct(BundleInterface $bundle)
    {
        $this->filesystem = new Filesystem();

        parent::__construct($bundle);
    }

    public function install(): void
    {
        parent::install();

        $this->filesystem = new Filesystem();

        $this->installRoutingConfig();
    }

    private function installRoutingConfig(): void
    {
        if (!$this->filesystem->exists($this->configDir)) {
            $this->filesystem->mkdir($this->configDir);
        }

        $routingConfig = [
            'inertia_bundle' => [
                'resource' => '@InertiaBundle/Resources/config/routes.yaml'
            ]
        ];

        $configFile = $this->configDir . '/inertia_bundle.yaml';

        if (!$this->filesystem->exists($configFile)) {
            file_put_contents($configFile, Yaml::dump($routingConfig, 4));
        }
    }

    public function uninstall(): void
    {
        $configFile = $this->configDir . '/inertia_bundle.yaml';

        if ($this->filesystem->exists($configFile)) {
            $this->filesystem->remove($configFile);
        }

        parent::uninstall();
    }

    public function isInstalled(): bool
    {
        return $this->filesystem->exists($this->configDir . '/inertia_bundle.yaml');
    }

    public function canBeInstalled(): bool
    {
        return !$this->filesystem->exists($this->configDir . '/inertia_bundle.yaml');
    }

    public function canBeUninstalled(): bool
    {
        return $this->isInstalled();
    }
}
