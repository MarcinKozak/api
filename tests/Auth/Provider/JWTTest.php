<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Dingo\Api\Auth\Provider\JWT;
use Dingo\Api\Routing\Route;
use Dingo\Api\Tests\BaseTestCase;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

class JWTTest extends BaseTestCase
{
    protected $auth;
    protected $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = m::mock(JWTAuth::class);
        $this->provider = new JWT($this->auth);
    }

    public function testValidatingAuthorizationHeaderFailsAndThrowsException(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request = Request::create('foo', 'GET');
        $this->provider->authenticate($request, m::mock(Route::class));
    }

    public function testAuthenticatingFailsAndThrowsException(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andThrow(new JWTException('foo'));

        $this->provider->authenticate($request, m::mock(Route::class));
    }

    public function testAuthenticatingSucceedsAndReturnsUserObject(): void
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn(new GenericUser(['id' => 1]));

        $user = $this->provider->authenticate($request, m::mock(Route::class));

        self::assertSame(1, $user->getAuthIdentifier());
    }

    public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserObject(): void
    {
        $request = Request::create('foo', 'GET', ['token' => 'foo']);

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn(new GenericUser(['id' => 1]));

        $user = $this->provider->authenticate($request, m::mock(Route::class));

        self::assertSame(1, $user->getAuthIdentifier());
    }
}
