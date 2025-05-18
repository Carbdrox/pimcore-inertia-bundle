<?php

namespace InertiaBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallAssetsCommand extends Command
{
    protected static $defaultName = 'inertia:assets:install';

    public function __construct(
        private KernelInterface $kernel
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Installs the assets for the InertiaBundle')
            ->setHelp('This command installs only the assets for the InertiaBundle')
            ->addOption('symlink', 's', InputOption::VALUE_NONE, 'If specified, assets will be installed via symlink.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bundle = $this->kernel->getBundle('InertiaBundle');
        $originDir = $bundle->getPath() . '/Resources/public';

        if (!is_dir($originDir)) {
            $output->writeln(sprintf('No assets found in bundle: <comment>%s</comment>', $bundle->getName()));
            return Command::SUCCESS;
        }

        $targetDir = $this->kernel->getProjectDir() . '/public/bundles/' . preg_replace('/bundle$/', '', strtolower($bundle->getName()));

        $filesystem = new Filesystem();
        $output->writeln(sprintf('Installing assets for <comment>%s</comment> into <comment>%s</comment>', $bundle->getName(), $targetDir));

        $filesystem->remove($targetDir);

        if ($input->getOption('symlink')) {
            if ($filesystem->exists($targetDir)) {
                $filesystem->remove($targetDir);
            }
            $filesystem->symlink($originDir, $targetDir);
        } else {
            $filesystem->mirror($originDir, $targetDir);
        }

        $output->writeln('Assets installed successfully.');

        return Command::SUCCESS;
    }
}
