<?php declare(strict_types=1);

namespace InertiaBundle\EventSubscriber;

use ReflectionClass;
use InertiaBundle\Service\Inertia;
use InertiaBundle\Support\InertiaResponse;
use InertiaBundle\Service\TranslationService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InertiaAttributeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Inertia $inertia,
        protected TranslationService $translationService
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', -128],
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $parameters = $event->getControllerResult();

        if (!is_array($parameters ?? [])) {
            return;
        }

        [$controller, $methodName] = $event->controllerArgumentsEvent?->getController() ?? [null, null];

        if (!$controller || !$methodName) {
            return;
        }

        $reflection = new ReflectionClass($controller);
        $classAttribute = $reflection->getAttributes(InertiaResponse::class)[0] ?? null;
        $methodAttribute = $reflection->getMethod($methodName)->getAttributes(InertiaResponse::class)[0] ?? null;

        if (!$classAttribute && !$methodAttribute) {
            return;
        }

        $classInertiaResponse = $classAttribute?->newInstance() ?: new InertiaResponse();
        $methodInertiaResponse = $methodAttribute?->newInstance() ?: new InertiaResponse();

        $component = $this->resolveComponent($classInertiaResponse, $methodInertiaResponse, $controller, $methodName);
        $viewData = array_merge($classInertiaResponse->viewData, $methodInertiaResponse->viewData);
        $context = array_merge($classInertiaResponse->context, $methodInertiaResponse->context);
        $props = array_merge(
            ['translations' => $this->translationService->getAllTranslations()],
            $classInertiaResponse->props,
            $methodInertiaResponse->props,
            $parameters
        );
        $url = $methodInertiaResponse->url ?? $classInertiaResponse->url;

        $event->setResponse(
            $this->inertia->render($component, $props, $viewData, $context, $url)
        );
    }

    protected function resolveComponent(
        InertiaResponse $classResponse,
        InertiaResponse $methodResponse,
        $controller,
        string $methodName
    ): string
    {

        if ($methodResponse->component) {
            return vsprintf('%s%s%s', [
                $classResponse->component ?? '',
                $classResponse->component ? '/' : '',
                $methodResponse->component
            ]);
        }

        if ($classResponse->component) {
            return vsprintf('%s/%s', [
                $classResponse->component,
                lcfirst($methodName)
            ]);
        }

        return $this->guessInertiaComponentName($controller, $methodName);
    }

    protected function guessInertiaComponentName(mixed $controller, string $methodName): string
    {
        $className = (new ReflectionClass($controller))->getShortName();
        $className = str_replace('Controller', '', $className);
        return lcfirst($className) . '/' . lcfirst($methodName);
    }
}
