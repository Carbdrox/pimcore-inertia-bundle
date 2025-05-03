<?php declare(strict_types=1);

namespace InertiaBundle\Command;

use Pimcore\Console\AbstractCommand;
use InertiaBundle\Support\BundleHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InertiaStartSsr extends AbstractCommand
{

    protected function configure(): void
    {
        $this->setName('inertia:ssr:start')
            ->addOption('bundle', 'b', InputOption::VALUE_REQUIRED, 'The path to the SSR bundle. If not specified, an attempt is made to determine the bundle dynamically.')
            ->addOption('runtime', 'r', InputOption::VALUE_REQUIRED, 'The runtime to use (`node` or `pm2` or `screen`). If not specified, `node` is used.')
            ->setDescription('Starts/Restarts the Inertia SSR server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = sprintf('inertia:ssr:%s', $_ENV['APP_NAME']);
        $bundle = $input->getOption('bundle') ?? BundleHelper::detectBundle();
        $runtime = $input->getOption('runtime') ?? 'node';

        if (!$bundle || !file_exists($bundle)) {
            throw new \InvalidArgumentException(
                'The bundle could not be found, please check if it is available and enter a file path if necessary.'
            );
        }

        if (!in_array($runtime, ['node', 'pm2', 'screen'], true)) {
            throw new \InvalidArgumentException(
                'The specified runtime is not supported. Please use `node` or `pm2` or `screen`'
            );
        }

        BundleHelper::stopSsrProcess($runtime, $bundle, $name);
        $code = BundleHelper::startSsrProcess($runtime, $bundle, $name);

        if ($code === self::SUCCESS) {
            $this->output->writeln(vsprintf('Inertia SSR server startet with `%s` and name `%s`', [
                $runtime,
                $bundle
            ]));
            return self::SUCCESS;
        }

        if ($runtime === 'node') {
            $this->output->writeln('Inertia SSR server stopped.');
            return self::SUCCESS;
        }

        $this->output->writeln(vsprintf('Inertia SSR server could not be startet with `%s` and name `%s`', [
            $runtime,
            $name
        ]));

        return self::FAILURE;
    }
}
