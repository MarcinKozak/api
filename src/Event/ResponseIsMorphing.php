<?php

namespace Dingo\Api\Event;

use Dingo\Api\Http\Response;

class ResponseIsMorphing
{
    /**
     * Response instance.
     *
     * @var Response
     */
    public $response;

    /**
     * Response content.
     *
     * @var string
     */
    public $content;

    /**
     * ResponseIsMorphing constructor.
     * @param Response $response
     * @param string $content
     */
    public function __construct(Response $response, string &$content)
    {
        $this->response = $response;
        $this->content = &$content;
    }
}
