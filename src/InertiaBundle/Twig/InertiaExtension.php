<?php declare(strict_types=1);

namespace InertiaBundle\Twig;

use Twig\Markup;
use Twig\TwigFunction;
use InertiaBundle\Service\Inertia;
use InertiaBundle\Support\SsrGateway;
use Twig\Extension\AbstractExtension;

class InertiaExtension extends AbstractExtension
{

    public function __construct(
        private Inertia    $inertia,
        private SsrGateway $gateway
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('inertia', [$this, 'inertiaResolver'], ['needs_context' => true]),
            new TwigFunction('inertia_head', [$this, 'inertiaHeadResolver'], ['needs_context' => true])
        ];
    }

    public function inertiaResolver(array $context): Markup
    {
        if (!$page = $context['page'] ?? null) {
            throw new \RuntimeException('Missing inertia page variable.');
        }

        if (!$this->inertia->isSsr() || !($response = $this->gateway->dispatch($page))) {
            return new Markup(
                sprintf('<div id="app" data-page="%s"></div>', htmlspecialchars(json_encode($page))),
                'UTF-8'
            );
        }

        return new Markup($response->body, 'UTF-8');
    }

    public function inertiaHeadResolver(array $context): Markup
    {
        if (!$page = $context['page'] ?? null) {
            throw new \RuntimeException('Missing inertia page variable.');
        }

        if (!$this->inertia->isSsr() || !($response = $this->gateway->dispatch($page))) {
            return new Markup('', 'UTF-8');
        }

        return new Markup($response->head, 'UTF-8');
    }
}

