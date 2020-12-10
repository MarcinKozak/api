<?php

namespace Dingo\Api\Contract\Transformer;

use Dingo\Api\Http\Request;
use Dingo\Api\Transformer\Binding;
use League\Fractal\TransformerAbstract;

interface Adapter
{
    /**
     * Transform a response with a transformer.
     *
     * @param mixed $response
     * @param TransformerAbstract $transformer
     * @param Binding $binding
     * @param Request $request
     *
     * @return array
     */
    public function transform($response, TransformerAbstract $transformer, Binding $binding, Request $request) : array;
}
