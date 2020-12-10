<?php

namespace Dingo\Api\Auth\Provider;

use Dingo\Api\Routing\Route;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Basic extends Authorization
{
    /**
     * Illuminate authentication manager.
     *
     * @var AuthManager
     */
    protected $auth;

    /**
     * Basic auth identifier.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Basic constructor.
     * @param AuthManager $auth
     * @param string $identifier
     */
    public function __construct(AuthManager $auth, string $identifier = 'email')
    {
        $this->auth = $auth;
        $this->identifier = $identifier;
    }

    /**
     * Authenticate request with Basic.
     *
     * @param Request $request
     * @param Route $route
     *
     * @return Authenticatable|null
     */
    public function authenticate(Request $request, Route $route) : ?Authenticatable
    {
        $this->validateAuthorizationHeader($request);

        $guard = $this->auth->guard();

        if (($response = $guard->onceBasic($this->identifier)) && $response->getStatusCode() === 401) {
            throw new UnauthorizedHttpException('Basic', 'Invalid authentication credentials.');
        }

        return $guard->user();
    }

    /**
     * Get the providers authorization method.
     *
     * @return string
     */
    public function getAuthorizationMethod() : string
    {
        return 'basic';
    }
}
