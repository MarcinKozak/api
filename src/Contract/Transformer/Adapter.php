<?php

namespace Dingo\Api\Contract\Transformer;

use Dingo\Api\Http\Request;
use Dingo\Api\Transformer\Binding;

interface Adapter
{
    /**
     * Transform a response with a transformer.
     *
     * @param mixed $response
     * @param Binding $binding
     * @param Request $request
     *
     * @return array
     */
    public function transform($response, Binding $binding, Request $request) : array;
}
