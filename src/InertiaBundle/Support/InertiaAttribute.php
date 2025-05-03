<?php declare(strict_types=1);

namespace InertiaBundle\Support;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class InertiaAttribute
{
    public function __construct(
        public string  $component,
        public array   $props = [],
        public array   $viewData = [],
        public array   $context = [],
        public ?string $url = null
    )
    {
    }
}
