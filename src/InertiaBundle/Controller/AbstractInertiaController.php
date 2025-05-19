<?php declare(strict_types=1);

namespace InertiaBundle\Controller;

use ReflectionClass;
use RuntimeException;
use InertiaBundle\Service\Inertia;
use InertiaBundle\Support\InertiaAreabrick;
use InertiaBundle\Service\AreabrickRenderer;
use Symfony\Component\HttpFoundation\Response;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as ServiceContainerInterface;

abstract class AbstractInertiaController extends AbstractController
{

    protected array $bricks = [];

    public function __construct(
        protected Inertia $inertia,
        protected ParameterBagInterface $params,
        protected DocumentResolver $documentResolver,
        protected EditmodeResolver $editmodeResolver,
        protected AreabrickRenderer $areabrickRenderer,
        protected ServiceContainerInterface $serviceContainer,
    )
    {
    }

    public function __get(string $name)
    {
        if ('document' === $name) {
            return $this->documentResolver->getDocument();
        }

        if ('editmode' === $name) {
            return $this->editmodeResolver->isEditmode();
        }

        throw new RuntimeException(sprintf('Trying to read undefined property "%s"', $name));
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
        ?string $url = null
    ): Response
    {
        return $this->inertia->render($component, $props, $viewData, $context, $url);
    }

    protected function getAreablockData(null|string|array $identifier = null): array {
        return match (true) {
            is_string($identifier) => [$this->areabrickRenderer->getAreablockData($identifier)],
            is_array($identifier) => array_map(fn ($name) => $this->areabrickRenderer->getAreablockData($name), array_keys($identifier)),
            default => []
        };
    }

    protected function processInertiaAttribute(string $methodName): ?Response
    {
        $reflection = new ReflectionClass($this);
        $method = $reflection->getMethod($methodName);
        $attributes = $method->getAttributes(InertiaAreabrick::class);

        if (empty($attributes)) {
            return null;
        }

        $attribute = $attributes[0]->newInstance();

        if (!($attribute->identifier ?? null)) {
            return null;
        }

        if ($this->editmode) {
            $template = $attribute->editTemplate ??
                ($this->serviceContainer->getParameter('inertia.admin.edit_mode_template') ?? '@Inertia/edit_mode.html.twig');
            return $this->render(
                $template,
                [
                    'document' => $this->document,
                    'blocks' => is_string($attribute->identifier) ? [$attribute->identifier => []] : $attribute->identifier,
                ]
            );
        }

        $this->bricks = $this->getAreablockData($attribute->identifier);
        return null;
    }

    public function __call($method, $arguments): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException(
                sprintf('Call to undefined method AbstractInertiaController::%s()', $method)
            );
        }

        $result = $this->processInertiaAttribute($method);

        if ($result instanceof Response) {
            return $result;
        }

        return call_user_func_array(array($this, $method), $arguments);
    }

}


