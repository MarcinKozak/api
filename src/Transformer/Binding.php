<?php

namespace Dingo\Api\Transformer;

use Closure;
use League\Fractal\TransformerAbstract;

class Binding
{

    /**
     * Binding transformer.
     *
     * @var TransformerAbstract
     */
    protected $transformer;

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
     * @param TransformerAbstract $transformer
     * @param array $parameters
     * @param Closure|null $callback
     */
    public function __construct(TransformerAbstract $transformer, array $parameters = [], Closure $callback = null)
    {
        $this->transformer = $transformer;
        $this->parameters = $parameters;
        $this->callback = $callback;
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
     * @return TransformerAbstract
     */
    public function getTransformer() : TransformerAbstract {
        return $this->transformer;
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
