<?php

namespace Dingo\Api\Facade;

use Dingo\Api\Auth\Auth;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Http\Response\Factory;
use Dingo\Api\Routing\Router;
use Dingo\Api\Transformer\Factory as TransformerFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Facade;

class API extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() : string
    {
        return 'api.dispatcher';
    }

    /**
     * Bind an exception handler.
     *
     * @param callable $callback
     *
     * @return void
     */
    public static function error(callable $callback)
    {
        return static::$app['api.exception']->register($callback);
    }

    /**
     * @return TransformerFactory
     */
    public static function transformer() : TransformerFactory
    {
        return static::$app['api.transformer'];
    }

    /**
     * Register a class transformer.
     *
     * @param string          $class
     * @param string|\Closure $transformer
     *
     * @return \Dingo\Api\Transformer\Binding
     */
    public static function transform(string $class, $transformer)
    {
        return static::transformer()->register($class, $transformer);
    }

    /**
     * Get the authenticator.
     *
     * @return Auth
     */
    public static function auth() : Auth
    {
        return static::$app['api.auth'];
    }

    /**
     * Get the authenticated user.
     *
     * @return ?Authenticatable
     */
    public static function user() : ?Authenticatable
    {
        return static::auth()->user();
    }

    /**
     * Determine if a request is internal.
     *
     * @return bool
     */
    public static function internal() : bool
    {
        return static::router()->getCurrentRequest() instanceof InternalRequest;
    }

    /**
     * Get the response factory to begin building a response.
     *
     * @return Factory
     */
    public static function response() : Factory
    {
        return static::$app['api.http.response'];
    }

    /**
     * Get the API router instance.
     *
     * @return Router
     */
    public static function router() : Router
    {
        return static::$app['api.router'];
    }

    /**
     * Get the API route of the given name, and optionally specify the API version.
     *
     * @param string $routeName
     * @param string $apiVersion
     *
     * @return string
     */
    public static function route(string $routeName, string $apiVersion = 'v1') : string
    {
        return static::$app['api.url']->version($apiVersion)->route($routeName);
    }
}
