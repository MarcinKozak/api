<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Contract\Routing\Adapter;
use Dingo\Api\Http\Response;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Route;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request as BaseRequest;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class RoutingAdapterStub implements Adapter
{
    protected $routes = [];

    protected $patterns = [];

    /**
     * @param Request $request
     * @param string $version
     * @return BaseResponse
     */
    public function dispatch(Request $request, string $version) : BaseResponse
    {
        $routes = $this->routes[$version];

        $route = $this->findRoute($request, $routes);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return (new Pipeline(new Container))
            ->send($request)
            ->through([])
            ->then(function ($request) use ($route) {
                return $this->prepareResponse($request, $route->run());
            });
    }

    protected function prepareResponse(BaseRequest $request, $response) : BaseResponse
    {
        if ($response instanceof IlluminateResponse) {
            $response = Response::makeFromExisting($response);
        } elseif ($response instanceof JsonResponse) {
            $response = Response::makeFromJson($response);
        } else {
            $response = new Response($response);
        }

        return $response->prepare($request);
    }

    protected function findRoute(Request $request, RouteCollection $routeCollection) : Route
    {
        return $routeCollection->match($request);
    }

    public function getRouteProperties($route, Request $request) : array
    {
        return [$route->uri(), (array) $request->getMethod(), $route->getAction()];
    }

    public function addRoute(array $methods, array $versions, string $uri, $action) : void
    {
        $this->createRouteCollections($versions);

        $route = new IlluminateRoute($methods, $uri, $action);
        $this->addWhereClausesToRoute($route);

        foreach ($versions as $version) {
            $this->routes[$version]->add($route);
        }
    }

    public function getRoutes(string $version = null) : array
    {
        if (! is_null($version)) {
            return $this->routes[$version];
        }

        return $this->routes;
    }

    public function getIterableRoutes($version = null) : array
    {
        return $this->getRoutes($version);
    }

    public function setRoutes(array $routes) : void
    {
        //
    }

    public function prepareRouteForSerialization($route) : void
    {
        //
    }

    public function pattern($key, $pattern): void {
        $this->patterns[$key] = $pattern;
    }

    public function getPatterns(): array {
        return $this->patterns;
    }

    protected function createRouteCollections(array $versions): void {
        foreach ($versions as $version) {
            if (! isset($this->routes[$version])) {
                $this->routes[$version] = new RouteCollection;
            }
        }
    }

    protected function addWhereClausesToRoute(Route $route) : Route
    {
        $where = $route->getAction()['where'] ?? [];

        $route->where(array_merge($this->patterns, $where));

        return $route;
    }

    public function gatherRouteMiddlewares(Route $route): array {
        return [];
    }
}
