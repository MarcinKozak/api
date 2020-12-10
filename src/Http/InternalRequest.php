<?php

namespace Dingo\Api\Http;

class InternalRequest extends Request
{
    /**
     * InternalRequest constructor.
     * @param array $query
     * @param array $request
     * @param array $attributes
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param null $content
     */
    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        // Pass parameters inside internal request into Laravel's JSON ParameterBag,
        // so that they can be accessed using $request->input()
        if (isset($this->request) && $this->isJson()) {
            $this->setJson($this->request);
        }
    }
}
