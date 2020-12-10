<?php

namespace Dingo\Api\Auth\Provider;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Tymon\JWTAuth\JWTAuth;
use Dingo\Api\Routing\Route;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class JWT extends Authorization
{
    /**
     * The JWTAuth instance.
     *
     * @var JWTAuth
     */
    protected $auth;

    /**
     * Create a new JWT provider instance.
     *
     * @param JWTAuth $auth
     *
     * @return void
     */
    public function __construct(JWTAuth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Authenticate request with a JWT.
     *
     * @param Request $request
     * @param Route $route
     *
     * @return mixed
     * @throws Exception
     */
    public function authenticate(Request $request, Route $route) : Authenticatable
    {
        $token = $this->getToken($request);

        try {
            if (! $user = $this->auth->setToken($token)->authenticate()) {
                throw new UnauthorizedHttpException('JWTAuth', 'Unable to authenticate with invalid token.');
            }
        } catch (JWTException $exception) {
            throw new UnauthorizedHttpException('JWTAuth', $exception->getMessage(), $exception);
        }

        return $user;
    }

    /**
     * Get the JWT from the request.
     *
     * @param Request $request
     *
     * @throws Exception
     *
     * @return string
     */
    protected function getToken(Request $request) : string
    {
        try {
            $this->validateAuthorizationHeader($request);

            $token = $this->parseAuthorizationHeader($request);
        } catch (Exception $exception) {
            if (! $token = $request->query('token', false)) {
                throw $exception;
            }
        }

        return $token;
    }

    /**
     * Parse JWT from the authorization header.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function parseAuthorizationHeader(Request $request) : string
    {
        return trim(str_ireplace($this->getAuthorizationMethod(), '', $request->header('authorization')));
    }

    /**
     * Get the providers authorization method.
     *
     * @return string
     */
    public function getAuthorizationMethod() : string
    {
        return 'bearer';
    }
}
