<?php

namespace Dingo\Api\Contract\Http\RateLimit;

use Dingo\Api\Http\Request;
use Illuminate\Container\Container;

interface HasRateLimiter
{
    /**
     * Get rate limiter callable.
     *
     * @param Container $app
     * @param Request $request
     *
     * @return callable
     */
    public function getRateLimiter(Container $app, Request $request) : callable;
}
