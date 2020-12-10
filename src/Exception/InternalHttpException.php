<?php

namespace Dingo\Api\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InternalHttpException extends HttpException
{
    /**
     * The response.
     *
     * @var Response
     */
    protected $response;

    /**
     * InternalHttpException constructor.
     * @param Response $response
     * @param string|null $message
     * @param Exception|null $previous
     * @param array $headers
     * @param int|null $code
     */
    public function __construct(Response $response, string $message = null, Exception $previous = null, array $headers = [], ?int $code = 0)
    {
        $this->response = $response;

        parent::__construct($response->getStatusCode(), $message, $previous, $headers, $code);
    }

    /**
     * Get the response of the internal request.
     *
     * @return Response
     */
    public function getResponse(): Response {
        return $this->response;
    }
}
