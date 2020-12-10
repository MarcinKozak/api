<?php

namespace Dingo\Api\Transformer\Adapter;

use Dingo\Api\Http\Request;
use Dingo\Api\Transformer\Binding;
use Illuminate\Pagination\AbstractPaginator;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\TransformerAbstract;
use Dingo\Api\Contract\Transformer\Adapter;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Support\Collection as IlluminateCollection;
use League\Fractal\Resource\Collection as FractalCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\Paginator as IlluminatePaginator;

class Fractal implements Adapter
{
    /**
     * Fractal manager instance.
     *
     * @var FractalManager
     */
    protected $fractal;

    /**
     * The include query string key.
     *
     * @var string
     */
    protected $includeKey;

    /**
     * The include separator.
     *
     * @var string
     */
    protected $includeSeparator;

    /**
     * Indicates if eager loading is enabled.
     *
     * @var bool
     */
    protected $eagerLoading = true;

    /**
     * Fractal constructor.
     * @param FractalManager $fractal
     * @param string $includeKey
     * @param string $includeSeparator
     * @param bool $eagerLoading
     */
    public function __construct(FractalManager $fractal, $includeKey = 'include', $includeSeparator = ',', $eagerLoading = true)
    {
        $this->fractal = $fractal;
        $this->includeKey = $includeKey;
        $this->includeSeparator = $includeSeparator;
        $this->eagerLoading = $eagerLoading;
    }

    /**
     * Transform a response with a transformer.
     *
     * @param mixed $response
     * @param object $transformer
     * @param Binding $binding
     * @param Request $request
     * @return array
     */
    public function transform($response, object $transformer, Binding $binding, Request $request) : array
    {
        $this->parseFractalIncludes($request);

        $resource = $this->createResource($response, $transformer, $parameters = $binding->getParameters());

        // If the response is a paginator then we'll create a new paginator
        // adapter for Laravel and set the paginator instance on our
        // collection resource.
        if ($response instanceof IlluminatePaginator && $resource instanceof Collection) {
            $paginator = $this->createPaginatorAdapter($response);

            $resource->setPaginator($paginator);
        }

        if ($this->shouldEagerLoad($response)) {
            $eagerLoads = $this->mergeEagerLoads($transformer, $this->fractal->getRequestedIncludes());

            if ($transformer instanceof TransformerAbstract) {
                // Only eager load the items in available includes
                $eagerLoads = array_intersect($eagerLoads, $transformer->getAvailableIncludes());
            }

            $response->load($eagerLoads);
        }

        foreach ($binding->getMeta() as $key => $value) {
            $resource->setMetaValue($key, $value);
        }

        $binding->fireCallback($resource, $this->fractal);

        $identifier = $parameters['identifier'] ?? null;

        return $this->fractal->createData($resource, $identifier)->toArray();
    }

    /**
     * Eager loading is only performed when the response is or contains an
     * Eloquent collection and eager loading is enabled.
     *
     * @param mixed $response
     *
     * @return bool
     */
    protected function shouldEagerLoad($response) : bool
    {
        if ($response instanceof AbstractPaginator) {
            $response = $response->getCollection();
        }

        return $response instanceof EloquentCollection && $this->eagerLoading;
    }

    /**
     * Create the Fractal paginator adapter.
     *
     * @param \Illuminate\Contracts\Pagination\Paginator $paginator
     *
     * @return \League\Fractal\Pagination\IlluminatePaginatorAdapter
     */
    protected function createPaginatorAdapter(IlluminatePaginator $paginator)
    {
        return new IlluminatePaginatorAdapter($paginator);
    }

    /**
     * Create a Fractal resource instance.
     *
     * @param $response
     * @param object $transformer
     * @param array $parameters
     * @return ResourceInterface
     */
    protected function createResource($response, object $transformer, array $parameters) : ResourceInterface
    {
        $key = $parameters['key'] ?? null;

        if ($response instanceof IlluminatePaginator || $response instanceof IlluminateCollection) {
            return new FractalCollection($response, $transformer, $key);
        }

        return new FractalItem($response, $transformer, $key);
    }

    /**
     * Parse the includes.
     *
     * @param Request $request
     *
     * @return void
     */
    public function parseFractalIncludes(Request $request) : void
    {
        $includes = $request->input($this->includeKey);

        if (! is_array($includes)) {
            $includes = array_map('trim', array_filter(explode($this->includeSeparator, $includes)));
        }

        $this->fractal->parseIncludes($includes);
    }

    /**
     * Get the underlying Fractal instance.
     *
     * @return FractalManager
     */
    public function getFractal() : FractalManager
    {
        return $this->fractal;
    }

    /**
     * Get includes as their array keys for eager loading.
     *
     * @param TransformerAbstract $transformer
     * @param array $requestedIncludes
     * @return array
     */
    protected function mergeEagerLoads(TransformerAbstract $transformer, array $requestedIncludes) : array
    {
        $includes   = array_merge($requestedIncludes, $transformer->getDefaultIncludes());
        $eagerLoads = [];

        foreach ($includes as $key => $value) {
            $eagerLoads[] = is_string($key) ? $key : $value;
        }

        if (property_exists($transformer, 'lazyLoadedIncludes')) {
            $eagerLoads = array_diff($eagerLoads, $transformer->lazyLoadedIncludes);
        }

        return $eagerLoads;
    }

    /**
     * Disable eager loading.
     *
     * @return $this
     */
    public function disableEagerLoading() : self
    {
        $this->eagerLoading = false;

        return $this;
    }

    /**
     * Enable eager loading.
     *
     * @return $this
     */
    public function enableEagerLoading() : self
    {
        $this->eagerLoading = true;

        return $this;
    }
}
