<?php declare(strict_types=1);

namespace InertiaBundle\Service;

use Pimcore\Model\Asset\Image;
use Symfony\Contracts\Cache\ItemInterface;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ThumbnailGenerator
{

    private TagAwareAdapter $cache;

    public function __construct(
        protected ContainerInterface $container,
    )
    {
    }

    public function generateInertiaThumbnail(Image $image, string $thumbnailName, ?string $cacheKey = null): JsonResponse
    {
        try {
            if (!$cacheKey) {
                return new JsonResponse($this->buildDataset($image, $thumbnailName));
            }

            $cache = new TagAwareAdapter(
                new FilesystemAdapter('inertia_assets', $this->container->getParameter('inertia.asset.cache_lifetime') ?? 86400)
            );

            return new JsonResponse(
                $cache->get($cacheKey, function (ItemInterface $item) use ($image, $thumbnailName) {

                    $item->tag([
                        'asset_' . $item->getKey(),
                        'asset_thumbnail',
                        'thumbnail_config_' . $thumbnailName
                    ]);

                    return $this->buildDataset($image, $thumbnailName);
                })
            );

        } catch (\Throwable $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return new JsonResponse([
                'error' => true,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    protected function buildDataset(Image $image, string $thumbnailName): array
    {
        $thumbnail = $image->getThumbnail($thumbnailName);
        $thumbnailConfig = clone $thumbnail->getConfig();
        $sources = [];

        if (!$thumbnailConfig instanceof Config) {
            throw new \Exception('Thumbnail config must be an instance of "\Pimcore\Model\Asset\Image\Thumbnail\Config"');
        }

        $isAutoFormat = strtolower($thumbnailConfig->getFormat()) === 'source';

        $mediaConfigs = $thumbnailConfig->getMedias();
        ksort($mediaConfigs, SORT_NUMERIC);
        $mediaConfigs[] = $thumbnailConfig->getItems();



        foreach ($mediaConfigs as $mediaQuery => $config) {
            $thumbnailConfig->setItems($config);
            $source = $this->getSource($image, $thumbnailConfig, $mediaQuery ?? null);

            if (count($source) <= 0) {
                continue;
            }

            $sources[] = $source;

            if ($isAutoFormat) {
                foreach ($thumbnailConfig->getAutoFormatThumbnailConfigs() as $autoFormatConfig) {
                    $autoFormatSource = $this->getSource($image, $autoFormatConfig, $mediaQuery ?? null);

                    if (count($autoFormatSource) <= 0) {
                        continue;
                    }

                    $sources[] = $autoFormatSource;
                }
            }
        }

        return [
            'id' => $image->getId(),
            'filename' => $image->getFilename(),
            'sources' => $sources,
            'image' => [
                'url' => $thumbnail->getPath(),
                'width' => $thumbnail->getWidth(),
                'height' => $thumbnail->getHeight(),
                'config' => $thumbnailConfig
            ]
        ];
    }

    protected function getSource(Image $image, Config $thumbnailConfig, null|int|string $mediaQuery = null): array
    {
        if (!is_string($mediaQuery)) {
            $mediaQuery = null;
        }

        $sourceTagAttributes = [];
        $sourceTagAttributes['srcset'] = $this->getSrcSet($thumbnailConfig, $image, [], $mediaQuery);
        $thumbnail = $image->getThumbnail($thumbnailConfig, true);

        $sourceTagAttributes['media'] = $mediaQuery;

        if ($mediaQuery) {
            $thumbnail->reset();
        }

        if ($thumbnail->getWidth()) {
            $sourceTagAttributes['width'] = $thumbnail->getWidth();
        }

        if ($thumbnail->getHeight()) {
            $sourceTagAttributes['height'] = $thumbnail->getHeight();
        }

        $sourceTagAttributes['type'] = $thumbnail->getMimeType();

        return $sourceTagAttributes;
    }

    protected function getSrcset(Config $thumbConfig, Image $image, array $options, ?string $mediaQuery = null): string
    {
        $srcSetValues = [];
        foreach ([1, 2] as $highRes) {
            $thumbConfigRes = clone $thumbConfig;
            if ($mediaQuery) {
                $thumbConfigRes->selectMedia($mediaQuery);
            }
            $thumbConfigRes->setHighResolution($highRes);
            $thumb = $image->getThumbnail($thumbConfigRes, true);

            $descriptor = $highRes . 'x';
            $srcSetValues[] = str_replace(',', '%2C', $thumb . ' ' . $descriptor);

        }

        return implode(', ', $srcSetValues);
    }

}
