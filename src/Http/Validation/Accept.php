<?php

namespace Dingo\Api\Http\Validation;

use Exception;
use Illuminate\Http\Request;
use Dingo\Api\Contract\Http\Validator;
use Dingo\Api\Http\Parser\Accept as AcceptParser;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Accept implements Validator
{
    /**
     * Accept parser instance.
     *
     * @var AcceptParser
     */
    protected $accept;

    /**
     * Indicates if the accept matching is strict.
     *
     * @var bool
     */
    protected $strict;

    /**
     * Create a new accept validator instance.
     *
     * @param AcceptParser $accept
     * @param bool $strict
     *
     * @return void
     */
    public function __construct(AcceptParser $accept, bool $strict = false)
    {
        $this->accept = $accept;
        $this->strict = $strict;
    }

    /**
     * Validate the accept header on the request. If this fails it will throw
     * an HTTP exception that will be caught by the middleware. This
     * validator should always be run last and must not return
     * a success boolean.
     *
     * @param Request $request
     *
     * @throws Exception|BadRequestHttpException
     *
     * @return bool|void
     */
    public function validate(Request $request) : bool
    {
        try {
            $this->accept->parse($request, $this->strict);
        } catch (BadRequestHttpException $exception) {
            if ($request->getMethod() === 'OPTIONS') {
                return true;
            }

            throw $exception;
        }
    }
}
