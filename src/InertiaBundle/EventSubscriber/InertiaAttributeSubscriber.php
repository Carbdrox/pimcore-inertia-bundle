<?php declare(strict_types=1);

namespace InertiaBundle\EventSubscriber;

use ReflectionClass;
use InertiaBundle\Service\Inertia;
use InertiaBundle\Support\InertiaResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InertiaAttributeSubscriber implements EventSubscriberInterface
{

    public function __construct(protected Inertia $inertia)
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

        if (!($attribute = $event->controllerArgumentsEvent?->getAttributes(InertiaResponse::class)[0] ?? null)) {
            return;
        }

        if (!$attribute->component) {
            [$controller, $methodName] = $event->controllerArgumentsEvent->getController();

            if (!$controller || !$methodName) {
                return;
            }

            $attribute->component = $this->guessInertiaComponentName($controller, $methodName);
            $parameters = array_merge($attribute->props, $parameters);
        }

        $event->setResponse(
            $this
                ->inertia
                ->render($attribute->component, $parameters, $attribute->viewData, $attribute->context, $attribute->url)
        );
    }

    protected function guessInertiaComponentName(mixed $controller, string $methodName): string
    {
        $className = (new ReflectionClass($controller))->getShortName();
        $className = str_replace('Controller', '', $className);
        return lcfirst($className) . '/' . lcfirst($methodName);
    }
}
