<?php

namespace Dingo\Api\Contract\Auth;

use Dingo\Api\Routing\Route;
use Illuminate\Http\Request;

interface Provider
{
    /**
     * Authenticate the request and return the authenticated user instance.
     *
     * @param Request $request
     * @param Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route);
}
