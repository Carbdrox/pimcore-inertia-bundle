<?php declare(strict_types=1);

namespace InertiaBundle\EventSubscriber;

use InertiaBundle\Service\Inertia;
use InertiaBundle\Support\InertiaAttribute;
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

        if (!($attribute = $event->controllerArgumentsEvent?->getAttributes()[InertiaAttribute::class][0] ?? null)) {
            return;
        }

        $event->setResponse(
            $this
                ->inertia
                ->render($attribute->component, $parameters, $attribute->viewData, $attribute->context, $attribute->url)
        );
    }


}
