<?php

namespace Dingo\Api\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UnknownVersionException extends HttpException
{
    /**
     * UnknownVersionException constructor.
     * @param null $message
     * @param Exception|null $previous
     * @param int|null $code
     */
    public function __construct($message = null, Exception $previous = null, ?int $code = 0)
    {
        parent::__construct(400, $message ?: 'The version given was unknown or has no registered routes.', $previous, [], $code);
    }
}
