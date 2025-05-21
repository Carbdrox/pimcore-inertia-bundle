<?php declare(strict_types=1);

namespace InertiaBundle\Controller;

use Pimcore\Model\Asset;
use Pimcore\Controller\Controller;
use InertiaBundle\Service\ThumbnailGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Route('/api/inertia/assets', name: 'inertia_assets_')]
class AssetApiController extends Controller
{

    protected ?ContainerInterface $serviceContainer = null;

    protected ?ThumbnailGenerator $thumbnailGenerator = null;


    public function setServiceContainer(ContainerInterface $serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    public function setThumbnailGenerator(ThumbnailGenerator $thumbnailGenerator)
    {
        $this->thumbnailGenerator = $thumbnailGenerator;
    }


    #[Route('/thumbnail/{assetId}', name: 'thumbnail', methods: ['GET'])]
    public function thumbnailAction(Request $request, int $assetId): JsonResponse
    {
        try {

            if (!$this->thumbnailGenerator) {
                throw new \LogicException('ThumbnailGenerator not set!');
            }

            $thumbnailName = $request->query->get('config', 'content');
            $image = Asset\Image::getById($assetId);

            if (!$image) {
                throw new \Exception('Asset not found', 404);
            }

            return $this->thumbnailGenerator->generateInertiaThumbnail(
                $image,
                $thumbnailName,
                "asset_thumbnail_{$assetId}_{$thumbnailName}"
            );
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

            return new JsonResponse([
                'error' => true,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

}
