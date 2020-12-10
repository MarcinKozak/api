<?php

namespace Dingo\Api\Exception;

use Exception;
use Illuminate\Support\MessageBag;
use Dingo\Api\Contract\Debug\MessageBagErrors;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResourceException extends HttpException implements MessageBagErrors
{
    /**
     * MessageBag errors.
     *
     * @var MessageBag
     */
    protected $errors;

    /**
     * ResourceException constructor.
     * @param null $message
     * @param null $errors
     * @param Exception|null $previous
     * @param array $headers
     * @param int|null $code
     */
    public function __construct($message = null, $errors = null, Exception $previous = null, $headers = [], ?int $code = 0)
    {
        if (is_null($errors)) {
            $this->errors = new MessageBag;
        } else {
            $this->errors = is_array($errors) ? new MessageBag($errors) : $errors;
        }

        parent::__construct(422, $message, $previous, $headers, $code);
    }

    /**
     * Get the errors message bag.
     *
     * @return MessageBag
     */
    public function getErrors() : MessageBag
    {
        return $this->errors;
    }

    /**
     * Determine if message bag has any errors.
     *
     * @return bool
     */
    public function hasErrors() : bool
    {
        return ! $this->errors->isEmpty();
    }
}
