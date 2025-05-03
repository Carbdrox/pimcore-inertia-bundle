<?php declare(strict_types=1);

namespace InertiaBundle\EventSubscriber;

use InertiaBundle\Service\Inertia;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class InertiaSubscriber
{

    protected string $inertiaCsrfTokenName = 'X-Inertia-CSRF-TOKEN';

    public function __construct(
        protected Inertia                   $inertia,
        protected CsrfTokenManagerInterface $csrfTokenManager,
        protected bool                      $debug,
        protected ContainerInterface        $container
    )
    {
    }

    protected function shouldHandleCSRF(RequestEvent|ResponseEvent $event): bool
    {
        if (
            !$this->container->hasParameter('inertia.csrf.enabled') ||
            !$this->container->getParameter('inertia.csrf.enabled')
        ) {
            return false;
        }

        $route = $event->getRequest()->attributes->get('_route');

        if (is_string($route) && str_starts_with($route, '_')) {
            return false;
        }

        return $event->isMainRequest() && !$event->getResponse()?->isRedirect();
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->headers->get('X-Inertia')) {
            return;
        }

        if ($this->container->hasParameter('inertia.csrf.header_name') && $this->shouldHandleCSRF($event)) {
            $csrfToken = $request->headers->get($this->container->getParameter('inertia.csrf.header_name'));

            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->inertiaCsrfTokenName, $csrfToken))) {
                $event->setResponse(new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN));
                return;
            }
        }

        if (
            'GET' === $request->getMethod() &&
            $request->headers->get('X-Inertia-Version') !== $this->inertia->getVersion()
        ) {
            $event->setResponse(
                new Response('', Response::HTTP_CONFLICT, ['X-Inertia-Location' => $request->getUri()])
            );
        }
    }


    public function onKernelResponse(ResponseEvent $event): void
    {
        $requiredParameter = $this->container->hasParameter('inertia.csrf.cookie_name') &&
            $this->container->hasParameter('inertia.csrf.expire') &&
            $this->container->hasParameter('inertia.csrf.path') &&
            $this->container->hasParameter('inertia.csrf.domain') &&
            $this->container->hasParameter('inertia.csrf.secure') &&
            $this->container->hasParameter('inertia.csrf.raw') &&
            $this->container->hasParameter('inertia.csrf.samesite');


        if ($requiredParameter && $this->shouldHandleCSRF($event)) {
            $event
                ->getResponse()
                ->headers->setCookie(
                    new Cookie(
                        $this->container->getParameter('inertia.csrf.cookie_name'),
                        $this->csrfTokenManager->refreshToken($this->inertiaCsrfTokenName)->getValue(),
                        $this->container->getParameter('inertia.csrf.expire'),
                        $this->container->getParameter('inertia.csrf.path'),
                        $this->container->getParameter('inertia.csrf.domain'),
                        $this->container->getParameter('inertia.csrf.secure'),
                        false,
                        $this->container->getParameter('inertia.csrf.raw'),
                        $this->container->getParameter('inertia.csrf.samesite')
                    )
                );
        }

        if (!$event->getRequest()->headers->get('X-Inertia')) {
            return;
        }

        if (
            $event->getResponse()->isRedirect() &&
            Response::HTTP_FOUND === $event->getResponse()->getStatusCode() &&
            in_array($event->getRequest()->getMethod(), ['PUT', 'PATCH', 'DELETE'])
        ) {
            $event->getResponse()->setStatusCode(Response::HTTP_SEE_OTHER);
        }
    }
}
