<?php

namespace Dingo\Api\Http\Response\Format;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

abstract class Format
{
    /**
     * Illuminate request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * Illuminate response instance.
     *
     * @var Response
     */
    protected $response;

    /*
     * Array of formats' options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request) : self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param Response $response
     * @return $this
     */
    public function setResponse(Response $response) : self
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options) : self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Format an Eloquent model.
     *
     * @param Model $model
     *
     * @return string
     */
    abstract public function formatEloquentModel(Model $model) : string;

    /**
     * Format an Eloquent collection.
     *
     * @param  Collection $collection
     *
     * @return string
     */
    abstract public function formatEloquentCollection(Collection $collection) : string;

    /**
     * Format an array or instance implementing Arrayable.
     *
     * @param array|Arrayable $content
     *
     * @return string
     */
    abstract public function formatArray($content) : string;

    /**
     * Get the response content type.
     *
     * @return string
     */
    abstract public function getContentType() : string ;
}
