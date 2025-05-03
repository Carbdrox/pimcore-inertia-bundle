<?php declare(strict_types=1);

namespace InertiaBundle\Service;

use Closure;
use Twig\Environment;
use Twig\Error\RuntimeError;
use InertiaBundle\Support\LazyProp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class Inertia
{
    protected ?string $rootView = null;

    protected array $props = [];

    protected array $viewData = [];

    protected array $contextData = [];

    protected bool $ssr = false;

    protected string $ssrUrl = '';

    protected ?string $version = null;

    public function __construct(
        private     ContainerInterface      $container,
        protected   Environment             $engine,
        protected   RequestStack            $requestStack,
        protected   ?SerializerInterface    $serializer = null
    )
    {
        if (!$container->hasParameter('inertia.root_view')) {
            return;
        }

        $this->setRootView($container->getParameter('inertia.root_view'));

        if (!$container->hasParameter('inertia.ssr.enabled') || !$container->hasParameter('inertia.ssr.url')) {
            return;
        }

        $this->setSsr(!!$container->getParameter('inertia.ssr.enabled'));
        $this->setSsrUrl($container->getParameter('inertia.ssr.url'));
    }

    public function isSsr(): bool
    {
        return $this->ssr;
    }

    public function setSsr(bool $state): void
    {
        $this->ssr = $state;
    }

    public function getSsrUrl(): string
    {
        return $this->ssrUrl;
    }

    public function setSsrUrl(string $url): void
    {
        $this->ssrUrl = $url;
    }

    public function getShare(?string $key = null): mixed
    {
        return null === $key ? $this->props : ($this->props[$key] ?? null);
    }

    public function share(string $key, mixed $value = null): void
    {
        $this->props[$key] = $value;
    }

    public function getViewData(?string $key = null): mixed
    {
        return null === $key ? $this->viewData : ($this->viewData[$key] ?? null);
    }

    public function viewData(string $key, mixed $value = null): void
    {
        $this->viewData[$key] = $value;
    }

    public function getContext(?string $key = null): mixed
    {
        return null === $key ? $this->contextData : ($this->contextData[$key] ?? null);
    }

    public function context(string $key, mixed $value = null): void
    {
        $this->contextData[$key] = $value;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getRootView(): ?string
    {
        return $this->rootView;
    }

    public function setRootView(string $rootView): void
    {
        $this->rootView = $rootView;
    }


    public function render(
        string  $component,
        array   $props = [],
        array   $viewData = [],
        array   $context = [],
        ?string $url = null
    ): Response
    {

        if ($this->rootView === null) {
            throw new RuntimeError('The root view is not set.');
        }

        $request = $this->requestStack->getCurrentRequest();
        $context = array_merge($this->contextData, $context);
        $viewData = array_merge($this->viewData, $viewData);
        $props = array_merge($this->props, $props);
        $url = $url ?? $request->getRequestUri();

        if ($url === '') {
            $url = null;
        }

        $only = array_filter(
            explode(',', $request->headers->get('X-Inertia-Partial-Data') ?? '')
        );

        $props = ($only && $request->headers->get('X-Inertia-Partial-Component') === $component)
            ? array_intersect_key($props, array_flip($only))
            : array_filter($props, fn($prop) => !($prop instanceof LazyProp));

        array_walk_recursive($props, function (&$prop) {
            if ($prop instanceof LazyProp) {
                $prop = call_user_func($prop);
            } elseif ($prop instanceof Closure) {
                $prop = $prop();
            }
        });

        $version = $this->version;
        $page = $this->serialize(compact('component', 'props', 'url', 'version'), $context);

        if ($request->headers->get('X-Inertia')) {
            return new JsonResponse($page, Response::HTTP_OK, [
                'Vary' => 'Accept',
                'X-Inertia' => true,
            ]);
        }

        $response = new Response();
        $response->setContent(
            $this->engine->render($this->rootView, compact('page', 'viewData'))
        );

        return $response;
    }

    public function location(string|RedirectResponse $url): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($url instanceof RedirectResponse) {
            $url = $url->getTargetUrl();
        }

        if ($request->headers->has('X-Inertia')) {
            return new Response('', Response::HTTP_CONFLICT, [
                'X-Inertia-Location' => $url,
            ]);
        }

        return new RedirectResponse($url);
    }

    //@see https://inertiajs.com/partial-reloads#lazy-data-evaluation
    public function lazy(callable|array|string $callback): LazyProp
    {
        if (is_string($callback)) {
            $callback = explode('::', $callback, 2);
        }

        if (is_array($callback)) {
            [$name, $action] = array_pad($callback, 2, null);

            if (($useContainer = is_string($name) && $this->container->has($name)) && !is_null($action)) {
                return new LazyProp([$this->container->get($name), $action]);
            }

            if ($useContainer && is_null($action)) {
                return new LazyProp($this->container->get($name));
            }
        }

        return new LazyProp($callback);
    }

    private function serialize(array $page, array $context = []): array
    {
        if (!!$this->serializer) {
            $json = $this
                ->serializer
                ->serialize(
                    $page,
                    'json',
                    array_merge([
                        'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
                        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn() => null,
                        AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true,
                        AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true
                    ], $context)
                );
        } else {
            $json = json_encode($page);
        }

        return (array)json_decode($json, false);
    }
}
