<?php declare(strict_types=1);

namespace InertiaBundle\Support;

class SsrResponse
{
    public function __construct(public string $head, public string $body)
    {}
}
