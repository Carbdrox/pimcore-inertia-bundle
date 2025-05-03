<?php declare(strict_types=1);

namespace InertiaBundle\Controller;

use InertiaBundle\Service\Inertia;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractInertiaController extends AbstractController
{
    protected Inertia $inertia;
    protected ParameterBagInterface $params;

    public function __construct(Inertia $inertia, ParameterBagInterface $params)
    {
        $this->inertia = $inertia;
        $this->params = $params;
    }

    protected function share(string $key, $value): void
    {
        $this->inertia->share($key, $value);
    }

    protected function getShared(?string $key = null): mixed
    {
        return $this->inertia->getShared($key);
    }

    protected function getViewData(?string $key = null): mixed
    {
        return $this->inertia->getViewData($key);
    }

    protected function setViewData(string $key, mixed $value = null): void
    {
        $this->inertia->setViewData($key, $value);
    }

    protected function getContext(?string $key = null): mixed
    {
        return $this->inertia->getContext($key);
    }

    protected function setContext(string $key, mixed $value = null): void
    {
        $this->inertia->setContext($key, $value);
    }

    protected function renderInertia(
        string  $component,
        array   $props = [],
        array   $viewData = [],
        array   $context = [],
        ?string $url = null): Response
    {
        return $this->inertia->render($component, $props, $viewData, $context, $url);
    }
}


