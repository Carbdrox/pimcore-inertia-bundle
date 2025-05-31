<?php declare(strict_types=1);

namespace InertiaBundle\Controller\Admin;

use Pimcore\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/inertia/docs', name: 'inertia_documentation_')]
class DocumentationController extends Controller
{

    #[Route(
        '/{path?}',
        name: 'documentation',
        requirements: ['path' => Requirement::CATCH_ALL],
        methods: ['GET']
    )]
    public function documentationAction(Request $request, ?string $path): BinaryFileResponse | RedirectResponse
    {
        if (!$path) {
            return $this->redirect(
                $this->generateUrl('inertia_documentation_documentation', ['path' => 'index.html'])
            );
        }

        $docsDir = __DIR__ . '/../../../../docs';
        $filePath = $docsDir . '/' . $path;

        $realDocsDir = realpath($docsDir);
        $realFilePath = realpath($filePath);

        if (!$realFilePath || !str_starts_with($realFilePath, $realDocsDir)) {
            throw new NotFoundHttpException('File not found');
        }

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new NotFoundHttpException('File not found');
        }

        $mimeType = $this->getMimeType($filePath);

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type' => $mimeType,
        ]);
    }

    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }
}
