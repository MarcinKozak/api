<?php

namespace Dingo\Api\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RateLimitExceededException extends HttpException
{
    /**
     * RateLimitExceededException constructor.
     * @param null $message
     * @param Exception|null $previous
     * @param array $headers
     * @param int|null $code
     */
    public function __construct($message = null, Exception $previous = null, $headers = [], ?int $code = 0)
    {
        if (array_key_exists('X-RateLimit-Reset', $headers)) {
            $headers['Retry-After'] = $headers['X-RateLimit-Reset'] - time();
        }

        parent::__construct(429, $message ?: 'You have exceeded your rate limit.', $previous, $headers, $code);
    }
}
