<?php

namespace Dingo\Api\Contract\Http;

use Dingo\Api\Http\Request as ApiRequest;
use Illuminate\Http\Request as IlluminateRequest;

interface Request
{
    /**
     * Create a new Dingo request instance from an Illuminate request instance.
     *
     * @param IlluminateRequest $old
     *
     * @return ApiRequest
     */
    public static function createFromIlluminate(IlluminateRequest $old) : ApiRequest;
}
