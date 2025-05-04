<?php declare(strict_types=1);

namespace InertiaBundle\Support;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class InertiaAreabrick
{
    public function __construct(
        public string|array $identifier,
        public ?string $editTemplate = null,
    )
    {
    }
}
