<?php

namespace Dingo\Api\Http\Response\Format;

use ErrorException;

class Jsonp extends Json
{
    /**
     * Name of JSONP callback parameter.
     *
     * @var string
     */
    protected $callbackName = 'callback';

    /**
     * Create a new JSONP response formatter instance.
     *
     * @param string $callbackName
     */
    public function __construct(string $callbackName = 'callback')
    {
        $this->callbackName = $callbackName;
    }

    /**
     * Determine if a callback is valid.
     *
     * @return bool
     */
    protected function hasValidCallback() : bool
    {
        return $this->request->query->has($this->callbackName);
    }

    /**
     * Get the callback from the query string.
     *
     * @return string
     */
    protected function getCallback() : string
    {
        return (string) $this->request->query->get($this->callbackName);
    }

    /**
     * Get the response content type.
     *
     * @return string
     */
    public function getContentType() : string
    {
        if ($this->hasValidCallback()) {
            return 'application/javascript';
        }

        return parent::getContentType();
    }

    /**
     * Encode the content to its JSONP representation.
     *
     * @param mixed $content
     *
     * @return string
     * @throws ErrorException
     */
    protected function encode($content) : string
    {
        $jsonString = parent::encode($content);

        if ($this->hasValidCallback()) {
            return sprintf('%s(%s);', $this->getCallback(), $jsonString);
        }

        return $jsonString;
    }
}
