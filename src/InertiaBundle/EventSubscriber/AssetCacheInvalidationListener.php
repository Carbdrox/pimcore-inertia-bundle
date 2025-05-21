<?php

namespace InertiaBundle\EventSubscriber;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\AssetEvent;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetCacheInvalidationListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvents::POST_UPDATE => 'onAssetChange',
            AssetEvents::POST_DELETE => 'onAssetChange'
        ];
    }

    public function onAssetChange(AssetEvent $event): void
    {
        $assetId = $event->getAsset()->getId();

        $cache = new TagAwareAdapter(
            new FilesystemAdapter('inertia_assets')
        );

        $cache->invalidateTags(['asset_' . $assetId]);
    }
}
