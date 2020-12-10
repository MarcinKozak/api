<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\RateLimit\Handler;
use Dingo\Api\Exception\RateLimitExceededException;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;

class RateLimit
{
    /**
     * Router instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * Rate limit handler instance.
     *
     * @var Handler
     */
    protected $handler;

    /**
     * Create a new rate limit middleware instance.
     *
     * @param Router $router
     * @param Handler $handler
     *
     * @return void
     */
    public function __construct(Router $router, Handler $handler)
    {
        $this->router = $router;
        $this->handler = $handler;
    }

    /**
     * Perform rate limiting before a request is executed.
     *
     * @param IlluminateRequest $request
     * @param Closure $next
     *
     * @return Response|mixed
     */
    public function handle(IlluminateRequest $request, Closure $next)
    {
        if ($request instanceof InternalRequest) {
            return $next($request);
        }

        $route = $this->router->getCurrentRoute();

        if($route instanceof Route) {
            if ($route->hasThrottle()) {
                $this->handler->setThrottle($route->getThrottle());
            }

            $this->handler->rateLimitRequest($request, $route->getRateLimit(), $route->getRateLimitExpiration());

            if ($this->handler->exceededRateLimit()) {
                throw new RateLimitExceededException('You have exceeded your rate limit.', null, $this->getHeaders());
            }
        }

        if ($route->hasThrottle()) {
            $this->handler->setThrottle($route->getThrottle());
        }

        $this->handler->rateLimitRequest($request, $route->getRateLimit(), $route->getRateLimitExpiration());

        if ($this->handler->exceededRateLimit()) {
            throw new RateLimitExceededException('You have exceeded your rate limit.', null, $this->getHeaders());
        }

        $response = $next($request);

        if ($this->handler->requestWasRateLimited()) {
            return $this->responseWithHeaders($response);
        }

        return $response;
    }

    /**
     * Send the response with the rate limit headers.
     *
     * @param Response $response
     * @return Response
     */
    protected function responseWithHeaders(Response $response) : Response
    {
        foreach ($this->getHeaders() as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * Get the headers for the response.
     *
     * @return array
     */
    protected function getHeaders() : array
    {
        return [
            'X-RateLimit-Limit' => $this->handler->getThrottleLimit(),
            'X-RateLimit-Remaining' => $this->handler->getRemainingLimit(),
            'X-RateLimit-Reset' => $this->handler->getRateLimitReset(),
        ];
    }
}
