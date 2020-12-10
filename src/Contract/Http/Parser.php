<?php

namespace Dingo\Api\Contract\Http;

use Illuminate\Http\Request as IlluminateRequest;

interface Parser
{
    /**
     * Parse an incoming request.
     *
     * @param IlluminateRequest $request
     *
     * @return array
     */
    public function parse(IlluminateRequest $request) : array;
}
