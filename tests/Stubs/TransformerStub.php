<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Contract\Transformer\Adapter;
use Dingo\Api\Http\Request;
use Dingo\Api\Transformer\Binding;
use Illuminate\Support\Collection;

class TransformerStub implements Adapter
{
    /**
     * @param mixed $response
     * @param object $transformer
     * @param Binding $binding
     * @param Request $request
     * @return array
     */
    public function transform($response, object $transformer, Binding $binding, Request $request) : array
    {
        if ($response instanceof Collection) {
            return $response->transform(function ($response) use ($transformer) {
                return $transformer->transform($response);
            })->toArray();
        }

        return $transformer->transform($response);
    }
}
