<?php

namespace Dingo\Api\Contract\Http\RateLimit;

use Illuminate\Container\Container;

interface Throttle
{
    /**
     * Attempt to match the throttle against a given condition.
     *
     * @param Container $container
     *
     * @return bool
     */
    public function match(Container $container) : bool;

    /**
     * Get the time in minutes that the throttles request limit will expire.
     *
     * @return int
     */
    public function getExpires() : int;

    /**
     * Get the throttles request limit.
     *
     * @return int
     */
    public function getLimit() : int;
}
