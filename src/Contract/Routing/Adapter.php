<?php

namespace Dingo\Api\Contract\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

interface Adapter
{
    /**
     * Dispatch a request.
     *
     * @param Request $request
     * @param string  $version
     *
     * @return Response
     */
    public function dispatch(Request $request, string $version) : Response;

    /**
     * Get the URI, methods, and action from the route.
     *
     * @param Route $route
     * @param Request $request
     *
     * @return array
     */
    public function getRouteProperties(Route $route, Request $request) : array;

    /**
     * Add a route to the appropriate route collection.
     *
     * @param array  $methods
     * @param array  $versions
     * @param string $uri
     * @param mixed  $action
     *
     * @return void
     */
    public function addRoute(array $methods, array $versions, string $uri, $action) : void;

    /**
     * Get all routes or only for a specific version.
     *
     * @param string|null $version
     *
     * @return array
     */
    public function getRoutes(string $version = null) : array;

    /**
     * Get a normalized iterable set of routes. Top level key must be a version with each
     * version containing iterable routes that can be consumed by the adapter.
     *
     * @param string|null $version
     *
     * @return iterable
     */
    public function getIterableRoutes(string $version = null) : iterable;

    /**
     * Set the routes on the adapter.
     *
     * @param array $routes
     *
     * @return void
     */
    public function setRoutes(array $routes) : void;

    /**
     * Prepare a route for serialization.
     *
     * @param Route $route
     *
     * @return void
     */
    public function prepareRouteForSerialization(Route $route) : void;

    /**
     * @param Route $route
     * @return array
     */
    public function gatherRouteMiddlewares(Route $route) : array;
}
