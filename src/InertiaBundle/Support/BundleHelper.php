<?php declare(strict_types=1);

namespace InertiaBundle\Support;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Console\Command\Command;

class BundleHelper
{

    public static function detectBundle(): ?string
    {
        $paths = [
            Path::join(PIMCORE_PROJECT_ROOT, '/build/ssr/ssr.mjs'),
            Path::join(PIMCORE_PROJECT_ROOT, '/build/ssr/ssr.js'),
            Path::join(PIMCORE_PROJECT_ROOT, '/build/ssr.mjs'),
            Path::join(PIMCORE_PROJECT_ROOT, '/build/ssr.js'),
            Path::join(PIMCORE_PROJECT_ROOT, '/public/build/assets/js/ssr.mjs'),
            Path::join(PIMCORE_PROJECT_ROOT, '/public/build/assets/js/ssr.js'),
            Path::join(PIMCORE_PROJECT_ROOT, '/public/assets/js/ssr.mjs'),
            Path::join(PIMCORE_PROJECT_ROOT, '/public/assets/js/ssr.js'),
        ];

        return current(array_filter($paths, fn ($path) => file_exists($path))) ?: null;
    }

    public static function detectRuntime(string $bundle, string $name): ?string
    {

        $output = null;
        $code = null;
        exec(sprintf('pm2 status | grep %s | grep online', $name), $output, $code);
        if ($code === 0 && $output && !!count($output)) {
            return 'pm2';
        }

        $output = null;
        $code = null;
        exec(sprintf('screen -ls | grep %s',  $name), $output, $code);
        if ($code === 0 && $output && !!count($output)) {
            return 'screen';
        }

        $output = null;
        $code = null;
        exec(sprintf('ps axf | grep "node %s" | grep -v grep | grep -v sh', $bundle), $output, $code);
        if ($code === 0 && $output && !!count($output)) {
            return 'node';
        }

        return null;
    }

    public static function stopSsrProcess(string $runtime, string $bundle, string $name): int
    {

        $output = null;
        $code = null;

        if ('node' === $runtime) {
            exec(sprintf('ps axf | grep "node %s" | grep -v grep | grep -v sh', $bundle), $output, $code);

            if ($code > 0 || !$output || !count($output)) {
                return Command::FAILURE;
            }

            $process = explode(' ', trim($output[0]));

            if (count($process) <= 0) {
                return Command::FAILURE;
            }

            $output = null;
            exec(sprintf('kill %s', $process[0]), $output, $code);

            return Command::SUCCESS;
        }

        if ('pm2' === $runtime) {
            exec(sprintf('pm2 status | grep %s', $name), $output, $code);

            if ($code > 0 || !$output || !count($output)) {
                return Command::FAILURE;
            }

            $output = null;
            exec(sprintf('pm2 stop %s', $name), $output, $code);

            return Command::SUCCESS;
        }

        if ('screen' === $runtime) {
            exec(sprintf('screen -ls | grep %s',  $name), $output, $code);

            if ($code > 0 || !$output || !count($output)) {
                return Command::FAILURE;
            }

            $output = null;
            exec(sprintf('screen -X -S "%s" quit', $name), $output, $code);

            return Command::SUCCESS;
        }

        return Command::INVALID;
    }


    public static function startSsrProcess(string $runtime, string $bundle, string $name): int
    {

        $output = null;
        $code = null;

        if ('node' === $runtime) {
            system(sprintf('node %s', $bundle), $code);
            return $code;
        }

        if ('pm2' === $runtime) {
            exec(vsprintf('pm2 start %s --name=%s', [$bundle, $name]), $output, $code);

            return $code > 0 || !$output || !count($output) ? Command::FAILURE : Command::SUCCESS;
        }

        if ('screen' === $runtime) {
            exec(vsprintf('screen -AdmS %s node %s', [$name, $bundle]), $output, $code);

            return $code > 0 ? Command::FAILURE : Command::SUCCESS;
        }

        return Command::INVALID;
    }

}
