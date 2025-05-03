<?php declare(strict_types=1);

namespace InertiaBundle\Support;

class LazyProp
{
    /**
     * Properties cannot be typed callable, therefore we need to set the prop manually
     * @var array|callable|string
     */
    private mixed $callback;

    public function __construct(array|callable|string $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(): mixed
    {
        return call_user_func($this->callback);
    }
}
