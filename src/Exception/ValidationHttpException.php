<?php

namespace Dingo\Api\Exception;

use Exception;

class ValidationHttpException extends ResourceException
{
    /**
     * ValidationHttpException constructor.
     * @param null $errors
     * @param Exception|null $previous
     * @param array $headers
     * @param int|null $code
     */
    public function __construct($errors = null, Exception $previous = null, $headers = [], ?int $code = 0)
    {
        parent::__construct(null, $errors, $previous, $headers, $code);
    }
}
