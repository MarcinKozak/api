<?php

namespace Dingo\Api\Transformer;

use Closure;
use Illuminate\Pagination\AbstractPaginator;
use RuntimeException;
use Dingo\Api\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Dingo\Api\Contract\Transformer\Adapter;
use Illuminate\Http\Request as IlluminateRequest;

class Factory {

    /**
     * Illuminate container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Array of registered transformer bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Transformation layer adapter being used to transform responses.
     *
     * @var Adapter
     */
    protected $adapter;

    /**
     * Factory constructor.
     * @param Container $container
     * @param Adapter $adapter
     */
    public function __construct(Container $container, Adapter $adapter) {
        $this->container = $container;
        $this->adapter = $adapter;
    }

    /**
     * Register a transformer binding resolver for a class.
     *
     * @param string $class
     * @param Resolver $resolver
     * @param array $parameters
     * @param Closure|null $after
     * @return Binding
     */
    public function register(string $class, Resolver $resolver, array $parameters = [], Closure $after = null): Binding {
        return $this->bindings[$class] = $this->createBinding($resolver, $parameters, $after);
    }

    /**
     * Transform a response.
     *
     * @param string|object $response
     *
     * @return array
     */
    public function transform($response) : array {
        $binding = $this->getBinding($response);

        return $this->adapter->transform($response, $binding->resolveTransformer(), $binding, $this->getRequest());
    }

    /**
     * Determine if a response is transformable.
     *
     * @param mixed $response
     *
     * @return bool
     */
    public function transformableResponse($response): bool {
        return $this->transformableType($response) && $this->hasBinding($response);
    }

    /**
     * Determine if a value is of a transformable type.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function transformableType($value): bool {
        return is_object($value) || is_string($value);
    }

    /**
     * Get a registered transformer binding.
     *
     * @param string|object $class
     *
     * @return Binding
     * @throws RuntimeException
     *
     */
    public function getBinding($class): Binding {
        if ($this->isFulfilledCollection($class)) {
            /* @var $class Collection|AbstractPaginator */
            return $this->getBindingFromCollection($class);
        }

        $class = is_object($class) ? get_class($class) : $class;

        if (!$this->hasBinding($class)) {
            throw new RuntimeException('Unable to find bound transformer for "' . $class . '" class.');
        }

        return $this->bindings[$class];
    }

    /**
     * @param Resolver $resolver
     * @param array $parameters
     * @param Closure|null $callback
     * @return Binding
     */
    protected function createBinding(Resolver $resolver, array $parameters = [], Closure $callback = null): Binding {
        return new Binding($this->container, $resolver, $parameters, $callback);
    }

    /**
     * Get a registered transformer binding from a collection of items.
     *
     * @param Collection|AbstractPaginator $collection
     *
     * @return Binding
     */
    protected function getBindingFromCollection($collection) : Binding {
        return $this->getBinding($collection->first());
    }

    /**
     * Determine if a class has a transformer binding.
     *
     * @param string|object $class
     *
     * @return bool
     */
    protected function hasBinding($class) : bool {
        if ($this->isFulfilledCollection($class)) {
            /* @var $class Collection|AbstractPaginator */
            $class = $class->first();
        }

        $class = is_object($class) ? get_class($class) : $class;

        return isset($this->bindings[$class]);
    }

    /**
     * Determine if the instance is a collection.
     *
     * @param object|string $instance
     *
     * @return bool
     */
    protected function isFulfilledCollection($instance): bool {
        if ($instance instanceof Collection || $instance instanceof AbstractPaginator) {
            return !$instance->isEmpty();
        }

        return false;
    }

    /**
     * Get the array of registered transformer bindings.
     *
     * @return array
     */
    public function getTransformerBindings() : array {
        return $this->bindings;
    }

    /**
     * Get the transformation layer adapter.
     *
     * @return Adapter
     */
    public function getAdapter() : Adapter {
        return $this->adapter;
    }

    /**
     * Get the request from the container.
     *
     * @return Request
     */
    public function getRequest() : Request {
        $request = $this->container['request'];

        if ($request instanceof IlluminateRequest && !$request instanceof Request) {
            $request = Request::createFromIlluminate($request);
        }

        return $request;
    }

}
