<?php declare(strict_types=1);

namespace InertiaBundle\Controller\Admin;

use Pimcore\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

#[Route('/inertia/asset_cache', name: 'inertia_asset_cache_')]
class AssetCacheController extends Controller
{

    #[Route('/invalidate/{name}', name: 'invalidate', methods: ['GET'])]
    public function invalidateAction(Request $request, string $name): JsonResponse
    {
        try {

            $cache = new TagAwareAdapter(
                new FilesystemAdapter('inertia_assets')
            );

            $cache->invalidateTags(['thumbnail_config_' . $name]);

            return new JsonResponse([
                'success' => true
            ]);

        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return new JsonResponse([
                'error' => true,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

}
