<?php

namespace Dingo\Api\Transformer;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class Resolver {

    /**
     * @var object|string|callable
     */
    private $resolver;

    /**
     * Resolver constructor.
     * @param $resolver
     */
    public function __construct($resolver) {
        assert($this->isValid($resolver), 'Resolver is not valid.');

        $this->resolver = $resolver;
    }

    /**
     * @param $resolver
     * @return bool
     */
    private function isValid($resolver) : bool {
        return is_string($resolver) || is_object($resolver) || is_callable($resolver);
    }

    /**
     * @param Container $container
     * @return object
     */
    public function create(Container $container) : object {
        if (is_string($this->resolver)) {
            try {
                return $container->make($this->resolver);
            }
            catch(BindingResolutionException $e) {
                throw new RuntimeException('Unable to resolve transformer binding.');
            }
        }

        if (is_callable($this->resolver)) {
            return call_user_func($this->resolver, $container);
        }

        return $this->resolver;
    }

}
