<?php

namespace Dingo\Api\Auth\Provider;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Dingo\Api\Contract\Auth\Provider as AuthProvider;

abstract class Authorization implements AuthProvider
{
    /**
     * Array of provider specific options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Validate the requests authorization header for the provider.
     *
     * @param Request $request
     *
     * @throws BadRequestHttpException
     *
     * @return void
     */
    public function validateAuthorizationHeader(Request $request) : void
    {
        if (Str::startsWith(strtolower($request->headers->get('authorization')), $this->getAuthorizationMethod())) {
            return;
        }

        throw new BadRequestHttpException;
    }

    /**
     * Get the providers authorization method.
     *
     * @return string
     */
    abstract public function getAuthorizationMethod() : string;
}
