<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use Dingo\Api\Routing\Router;
use Dingo\Api\Auth\Auth as Authentication;
use Illuminate\Http\Request as IlluminateRequest;

class Auth
{
    /**
     * Router instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * Authenticator instance.
     *
     * @var Authentication
     */
    protected $auth;

    /**
     * Create a new auth middleware instance.
     *
     * @param Router $router
     * @param Authentication $auth
     *
     * @return void
     */
    public function __construct(Router $router, Authentication $auth)
    {
        $this->router = $router;
        $this->auth = $auth;
    }

    /**
     * Perform authentication before a request is executed.
     *
     * @param IlluminateRequest $request
     * @param Closure                 $next
     *
     * @return mixed
     */
    public function handle(IlluminateRequest $request, Closure $next)
    {
        $route = $this->router->getCurrentRoute();

        if ($route && ! $this->auth->check(false)) {
            $this->auth->authenticate($route->getAuthenticationProviders());
        }

        return $next($request);
    }
}
