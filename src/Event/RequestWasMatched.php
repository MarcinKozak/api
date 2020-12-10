<?php

namespace Dingo\Api\Event;

use Dingo\Api\Http\Request;
use Illuminate\Contracts\Container\Container;

class RequestWasMatched
{
    /**
     * Request instance.
     *
     * @var Request
     */
    public $request;

    /**
     * Application instance.
     *
     * @var Container
     */
    public $app;

    /**
     * RequestWasMatched constructor.
     * @param Request $request
     * @param Container $app
     */
    public function __construct(Request $request, Container $app)
    {
        $this->request = $request;
        $this->app = $app;
    }
}
