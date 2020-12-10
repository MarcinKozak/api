<?php

namespace Dingo\Api\Transformer;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use League\Fractal\TransformerAbstract;
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
        assert($this->isValid($resolver), 'Transform resolver is not valid.');

        $this->resolver = $resolver;
    }

    /**
     * @param $resolver
     * @return bool
     */
    private function isValid($resolver) : bool {
        return is_string($resolver) || is_callable($resolver) || $resolver instanceof TransformerAbstract;
    }

    /**
     * @param Container $container
     * @return TransformerAbstract
     */
    public function create(Container $container) : TransformerAbstract {
        $transform = $this->resolver;

        if (is_string($this->resolver)) {
            try {
                $transform = $container->make($this->resolver);
            }
            catch(BindingResolutionException $e) {
                throw new RuntimeException('Unable to resolve transformer binding.');
            }
        }

        else if (is_callable($this->resolver)) {
            $transform = call_user_func($this->resolver, $container);
        }

        if($transform instanceof TransformerAbstract) {
            return $transform;
        }

        throw new RuntimeException('Cannot resolve transformer class due invalid type.');
    }

}
