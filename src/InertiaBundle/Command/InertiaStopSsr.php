<?php declare(strict_types=1);

namespace InertiaBundle\Command;

use Pimcore\Console\AbstractCommand;
use Psr\Container\ContainerInterface;
use InertiaBundle\Support\BundleHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InertiaStopSsr extends AbstractCommand
{

    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    protected function configure(): void
    {
        $this->setName('inertia:ssr:stop')
            ->addOption('bundle', 'b', InputOption::VALUE_REQUIRED, 'The path to the SSR bundle. If not specified, an attempt is made to determine the bundle dynamically.')
            ->addOption('runtime', 'r', InputOption::VALUE_REQUIRED, 'The runtime used to start the SSR server (`node` or `pm2` or `screen`). If not specified, an attempt is made to determine the runtime dynamically.')
            ->setDescription('Stops the Inertia SSR server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $name = sprintf('inertia:ssr:%s', $_ENV['APP_NAME']);
        $bundle = $input->getOption('bundle') ?? BundleHelper::detectBundle();
        $runtime = $input->getOption('runtime');


        if (!$bundle || !file_exists($bundle)) {
            throw new \InvalidArgumentException(
                'The bundle could not be found, please check if it is available and enter a file path if necessary.'
            );
        }

        if (!$runtime) {
            $runtime = BundleHelper::detectRuntime($bundle, $name);
        }

        if (!in_array($runtime, ['node', 'pm2', 'screen'], true)) {
            throw new \InvalidArgumentException(
                'The specified runtime is not supported. Please use `node` or `pm2` or `screen`'
            );
        }

        $code = BundleHelper::stopSsrProcess($runtime, $bundle, $name);

        if ($code === self::SUCCESS) {
            $this->output->writeln('Inertia SSR server stopped.');
            return self::SUCCESS;
        }

        $this->output->writeln('Unable to stop the Inertia SSR server.');
        return self::FAILURE;
    }


    private function stopViaRequest(): int
    {
        if (!$this->container->hasParameter('inertia.ssr.url')) {
            return self::INVALID;
        }

        $url = str_replace(
            '/render',
            '/render',
            sprintf('%s/shutdown', $this->container->getParameter('inertia.ssr.url'))
        );

        $ch = curl_init($url);
        curl_exec($ch);

        if (curl_error($ch) !== 'Empty reply from server') {
            $this->output->writeln('Unable to connect to Inertia SSR server.');

            return self::FAILURE;
        }

        $this->output->writeln('Inertia SSR server stopped.');

        curl_close($ch);

        return self::SUCCESS;
    }
}
