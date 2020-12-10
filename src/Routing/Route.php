<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Contract\Http\RateLimit\Throttle;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Dingo\Api\Contract\Routing\Adapter;
use Illuminate\Routing\Route as BaseRoute;

class Route extends BaseRoute
{
    /**
     * Routing adapter instance.
     *
     * @var Adapter
     */
    protected $adapter;

    /**
     * Array of versions this route will respond to.
     *
     * @var array
     */
    protected $versions;

    /**
     * Array of scopes for OAuth 2.0 authentication.
     *
     * @var array
     */
    protected $scopes;

    /**
     * Array of authentication providers.
     *
     * @var array
     */
    protected $authenticationProviders;

    /**
     * The rate limit for this route.
     *
     * @var int
     */
    protected $rateLimit;

    /**
     * The expiration time for any rate limit set on this rate.
     *
     * @var int
     */
    protected $rateExpiration;

    /**
     * The throttle used by the route, takes precedence over rate limits.
     *
     * @return string|Throttle
     */
    protected $throttle;

    /**
     * Controller class name.
     *
     * @var string
     */
    protected $controllerClass;

    /**
     * Controller method name.
     *
     * @var string
     */
    protected $controllerMethod;

    /**
     * Indicates if the request is conditional.
     *
     * @var bool
     */
    protected $conditionalRequest = true;

    /**
     * Middleware applied to route.
     *
     * @var array
     */
    protected $middleware;

    /**
     * Create a new route instance.
     *
     * @param Adapter $adapter
     * @param Container $container
     * @param Request $request
     * @param BaseRoute $route
     */
    public function __construct(Adapter $adapter, Container $container, Request $request, BaseRoute $route)
    {
        $this->adapter = $adapter;
        $this->container = $container;

        $this->setupRouteProperties($request, $route);

        parent::__construct($this->methods, $this->uri, $this->action);
    }

    /**
     * Setup the route properties.
     *
     * @param Request $request
     * @param BaseRoute $route
     *
     * @return void
     */
    protected function setupRouteProperties(Request $request, BaseRoute $route) : void
    {
        [$this->uri, $this->methods, $this->action] = $this->adapter->getRouteProperties($route, $request);

        $this->versions = $this->pullAction('version');
        $this->conditionalRequest = $this->pullAction('conditionalRequest', true);
        $this->middleware = (array) $this->pullAction('middleware', []);
        $this->throttle = $this->pullAction('throttle');
        $this->scopes = $this->pullAction('scopes', []);
        $this->authenticationProviders = $this->pullAction('providers', []);
        $this->rateLimit = $this->pullAction('limit', 0);
        $this->rateExpiration = $this->pullAction('expires', 0);

        // Now that the default route properties have been set we'll go ahead and merge
        // any controller properties to fully configure the route.
        $this->mergeControllerProperties();

        // If we have a string based throttle then we'll new up an instance of the
        // throttle through the container.
        if (is_string($this->throttle)) {
            $this->throttle = $this->container->make($this->throttle);
        }
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    private function pullAction(string $key, $default = null) {
        return Arr::pull($this->action, $key, $default);
    }

    /**
     * Merge the controller properties onto the route properties.
     */
    protected function mergeControllerProperties() : void
    {
        if (isset($this->action['uses']) && is_string($this->action['uses']) && Str::contains($this->action['uses'],
                '@')) {
            $this->action['controller'] = $this->action['uses'];

            $this->makeControllerInstance();
        }

        if (! $this->controllerUsesHelpersTrait()) {
            return;
        }

        if($controller = $this->getControllerInstance()) {
            $controllerMiddleware = [];

            if (method_exists($controller, 'getMiddleware')) {
                $controllerMiddleware = $controller->getMiddleware();
            } elseif (method_exists($controller, 'getMiddlewareForMethod')) {
                $controllerMiddleware = $controller->getMiddlewareForMethod($this->controllerMethod);
            }

            $this->middleware = array_merge($this->middleware, $controllerMiddleware);
        }

        if ($property = $this->findControllerPropertyOptions('throttles')) {
            $this->throttle = $property['class'];
        }

        if ($property = $this->findControllerPropertyOptions('scopes')) {
            $this->scopes = array_merge($this->scopes, $property['scopes']);
        }

        if ($property = $this->findControllerPropertyOptions('authenticationProviders')) {
            $this->authenticationProviders = array_merge($this->authenticationProviders, $property['providers']);
        }

        if ($property = $this->findControllerPropertyOptions('rateLimit')) {
            $this->rateLimit = $property['limit'];
            $this->rateExpiration = $property['expires'];
        }
    }

    /**
     * Find the controller options and whether or not it will apply to this routes controller method.
     *
     * @param string $name
     *
     * @return array
     */
    protected function findControllerPropertyOptions(string $name) : array
    {
        $properties = [];

        if($controller = $this->getControllerInstance()) {
            foreach ($controller->{'get'.ucfirst($name)}() as $property) {
                if (isset($property['options']) && ! $this->optionsApplyToControllerMethod($property['options'])) {
                    continue;
                }

                unset($property['options']);

                $properties = array_merge_recursive($properties, $property);
            }
        }



        return $properties;
    }

    /**
     * Determine if a controller method is in an array of options.
     *
     * @param array $options
     *
     * @return bool
     */
    protected function optionsApplyToControllerMethod(array $options) : bool
    {
        if (empty($options)) {
            return true;
        }

        if (isset($options['only']) && in_array($this->controllerMethod, $this->explodeOnPipes($options['only']), true)) {
            return true;
        }

        if (isset($options['except'])) {
            return !in_array($this->controllerMethod, $this->explodeOnPipes($options['except']), true);
        }

        if (in_array($this->controllerMethod, $this->explodeOnPipes($options), true)) {
            return true;
        }

        return false;
    }

    /**
     * Explode a value on a pipe delimiter.
     *
     * @param string|array $value
     *
     * @return array
     */
    protected function explodeOnPipes($value) : array
    {
        return is_string($value) ? explode('|', $value) : $value;
    }

    /**
     * Determine if the controller instance uses the helpers trait.
     *
     * @return bool
     */
    protected function controllerUsesHelpersTrait() : bool
    {
        if (! $controller = $this->getControllerInstance()) {
            return false;
        }

        $traits = [];

        do {
            if($uses = class_uses($controller, false)) {
                $traits[] = $uses;
            }
        } while ($controller = get_parent_class($controller));

        $traits = array_merge(...$traits);

        return isset($traits[Helpers::class]);
    }

    /**
     * Get the routes controller instance.
     *
     * @return null|\Illuminate\Routing\Controller|\Laravel\Lumen\Routing\Controller
     */
    public function getControllerInstance()
    {
        return $this->controller;
    }

    /**
     * Make a new controller instance through the container.
     *
     * @return \Illuminate\Routing\Controller|\Laravel\Lumen\Routing\Controller
     */
    protected function makeControllerInstance()
    {
        [$this->controllerClass, $this->controllerMethod] = explode('@', $this->action['uses']);

        $this->container->instance($this->controllerClass,
            $this->controller = $this->container->make($this->controllerClass));

        return $this->controller;
    }

    /**
     * Determine if the route is protected.
     *
     * @return bool
     */
    public function isProtected() : bool
    {
        $authRegistered = isset($this->middleware['api.auth']);

        if ($authRegistered || in_array('api.auth', $this->middleware, true)) {
            if ($this->controller && $authRegistered) {
                return $this->optionsApplyToControllerMethod($this->middleware['api.auth']);
            }

            return true;
        }

        return false;
    }

    /**
     * Determine if the route has a throttle.
     *
     * @return bool
     */
    public function hasThrottle() : array
    {
        return ! is_null($this->throttle);
    }

    /**
     * Get the route throttle.
     *
     * @return string|\Dingo\Api\Http\RateLimit\Throttle\Throttle
     */
    public function throttle()
    {
        return $this->throttle;
    }

    /**
     * Get the route throttle.
     *
     * @return string|\Dingo\Api\Http\RateLimit\Throttle\Throttle
     */
    public function getThrottle()
    {
        return $this->throttle;
    }

    /**
     * Get the route scopes.
     *
     * @return array
     */
    public function scopes() : array
    {
        return $this->scopes;
    }

    /**
     * Get the route scopes.
     *
     * @return array
     */
    public function getScopes() : array
    {
        return $this->scopes;
    }

    /**
     * Check if route requires all scopes or any scope to be valid.
     *
     * @return bool
     */
    public function scopeStrict() : bool
    {
        return Arr::get($this->action, 'scopeStrict', false);
    }

    /**
     * Get the route authentication providers.
     *
     * @return array
     */
    public function authenticationProviders() : array
    {
        return $this->authenticationProviders;
    }

    /**
     * Get the route authentication providers.
     *
     * @return array
     */
    public function getAuthenticationProviders() : array
    {
        return $this->authenticationProviders;
    }

    /**
     * Get the rate limit for this route.
     *
     * @return int
     */
    public function rateLimit() : int
    {
        return $this->rateLimit;
    }

    /**
     * Get the rate limit for this route.
     *
     * @return int
     */
    public function getRateLimit() : int
    {
        return $this->rateLimit;
    }

    /**
     * Get the rate limit expiration time for this route.
     *
     * @return int
     */
    public function rateLimitExpiration() : int
    {
        return $this->rateExpiration;
    }

    /**
     * Get the rate limit expiration time for this route.
     *
     * @return int
     */
    public function getRateLimitExpiration() : int
    {
        return $this->rateExpiration;
    }

    /**
     * Get the name of the route.
     *
     * @return string|null
     */
    public function getName() : ?string
    {
        return Arr::get($this->action, 'as', null);
    }

    /**
     * Determine if the request is conditional.
     *
     * @return bool
     */
    public function requestIsConditional() : bool
    {
        return $this->conditionalRequest === true;
    }

    /**
     * Get the versions for the route.
     *
     * @return array
     */
    public function getVersions() : array
    {
        return $this->versions;
    }

    /**
     * Get the versions for the route.
     *
     * @return array
     */
    public function versions() : array
    {
        return $this->getVersions();
    }

    /**
     * Get the URI associated with the route.
     *
     * @return string
     */
    public function getPath() : string
    {
        return $this->uri();
    }

    /**
     * Get the HTTP verbs the route responds to.
     *
     * @return array
     */
    public function getMethods() : array
    {
        return $this->methods();
    }

    /**
     * Determine if the route only responds to HTTP requests.
     *
     * @return bool
     */
    public function httpOnly() : bool
    {
        return in_array('http', $this->action, true)
            || (array_key_exists('http', $this->action) && $this->action['http']);
    }

    /**
     * Determine if the route only responds to HTTPS requests.
     *
     * @return bool
     */
    public function httpsOnly() : bool
    {
        return $this->secure();
    }

    /**
     * Determine if the route only responds to HTTPS requests.
     *
     * @return bool
     */
    public function secure() : bool
    {
        return in_array('https', $this->action, true)
            || (array_key_exists('https', $this->action) && $this->action['https']);
    }

    /**
     * Return the middlewares for this route.
     *
     * @return array
     */
    public function getMiddleware() : array
    {
        return $this->middleware;
    }
}
