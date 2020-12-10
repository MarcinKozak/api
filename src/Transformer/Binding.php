<?php

namespace Dingo\Api\Transformer;

use Closure;
use Illuminate\Container\Container;
use League\Fractal\TransformerAbstract;

class Binding
{
    /**
     * Illuminate container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Binding resolver.
     *
     * @var Resolver
     */
    protected $resolver;

    /**
     * Array of parameters.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Callback fired during transformation.
     *
     * @var Closure|null
     */
    protected $callback;

    /**
     * Array of meta data.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Binding constructor.
     * @param Container $container
     * @param Resolver $resolver
     * @param array $parameters
     * @param Closure|null $callback
     */
    public function __construct(Container $container, Resolver $resolver, array $parameters = [], Closure $callback = null)
    {
        $this->container = $container;
        $this->resolver = $resolver;
        $this->parameters = $parameters;
        $this->callback = $callback;
    }

    /**
     * Resolve a transformer binding instance.
     *
     * @return TransformerAbstract
     */
    public function resolveTransformer() : TransformerAbstract
    {
        return $this->resolver->create($this->container);
    }

    /**
     * Fire the binding callback.
     *
     * @param ...$parameters
     *
     * @return void
     */
    public function fireCallback(...$parameters) : void
    {
        if (is_callable($this->callback)) {
            call_user_func_array($this->callback, $parameters);
        }
    }

    /**
     * Get the binding parameters.
     *
     * @return array
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /**
     * Set the meta data for the binding.
     *
     * @param array $meta
     * @return void
     */
    public function setMeta(array $meta) : void
    {
        $this->meta = $meta;
    }

    /**
     * Add a meta data key/value pair.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function addMeta(string $key, $value) : void
    {
        $this->meta[$key] = $value;
    }

    /**
     * Get the binding meta data.
     *
     * @return array
     */
    public function getMeta() : array
    {
        return $this->meta;
    }
}
