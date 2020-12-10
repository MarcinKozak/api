<?php

namespace Dingo\Api\Http\RateLimit\Throttle;

use Illuminate\Container\Container;

class Route extends Throttle
{
    /**
     * Route specific throttles always match.
     *
     * @param Container $container
     *
     * @return bool
     */
    public function match(Container $container) : bool
    {
        return true;
    }
}
