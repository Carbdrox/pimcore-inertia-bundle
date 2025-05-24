<?php

namespace InertiaBundle\EventSubscriber;

use Pimcore\Event\BundleManagerEvents;
use Pimcore\Event\BundleManager\PathsEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class InertiaAdminListener implements EventSubscriberInterface
{

    public function __construct(
        private ContainerInterface $container
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BundleManagerEvents::JS_PATHS => 'addJsPaths',
        ];
    }

    public function addJsPaths(PathsEvent $event)
    {

        if (!$this->container->hasParameter('inertia.admin.split_view') ||
            !$this->container->getParameter('inertia.admin.split_view')) {
            return;
        }

        $event->addPaths([
            '/bundles/inertia/js/admin/editPreviewTab.js',
            '/bundles/inertia/js/admin/inertia.js',
        ]);
    }
}
