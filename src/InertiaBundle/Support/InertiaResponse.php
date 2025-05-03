<?php declare(strict_types=1);

namespace InertiaBundle\Support;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class InertiaResponse
{
    public function __construct(
        public ?string $component = null,
        public array   $props = [],
        public array   $viewData = [],
        public array   $context = [],
        public ?string $url = null
    )
    {
    }
}
