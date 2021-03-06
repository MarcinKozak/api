<?php

namespace Dingo\Api\Tests\Auth;

use Dingo\Api\Auth\Auth;
use Dingo\Api\Contract\Auth\Provider;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use Dingo\Api\Tests\BaseTestCase;
use Illuminate\Auth\GenericUser;
use Illuminate\Container\Container;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthTest extends BaseTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Router|m\LegacyMockInterface|m\MockInterface
     */
    protected $router;
    /**
     * @var Auth
     */
    protected $auth;

    public function setUp(): void
    {
        parent::setUp();

        $this->container = new Container;
        $this->router = m::mock(Router::class);
        $this->auth = new Auth($this->router, $this->container, []);
    }

    public function testExceptionThrownWhenAuthorizationHeaderNotSet(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route = m::mock(Route::class));
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn($request = Request::create('foo', 'GET'));

        $provider = m::mock(Provider::class);
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new BadRequestHttpException);

        $this->auth->extend('provider', $provider);

        $this->auth->authenticate();
    }

    public function testExceptionThrownWhenProviderFailsToAuthenticate(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route = m::mock(Route::class));
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn($request = Request::create('foo', 'GET'));

        $provider = m::mock(Provider::class);
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andThrow(new UnauthorizedHttpException('foo'));

        $this->auth->extend('provider', $provider);

        $this->auth->authenticate();
    }

    public function testAuthenticationIsSuccessfulAndUserIsSet(): void
    {
        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route = m::mock(Route::class));
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn($request = Request::create('foo', 'GET'));

        $provider = m::mock(Provider::class);
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(new GenericUser(['id' => 1]));

        $this->auth->extend('provider', $provider);

        $user = $this->auth->authenticate();

        self::assertSame(1, $user->id);
    }

    public function testProvidersAreFilteredWhenSpecificProviderIsRequested(): void
    {
        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route = m::mock(Route::class));
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn($request = Request::create('foo', 'GET'));

        $provider = m::mock(Provider::class);
        $provider->shouldReceive('authenticate')->once()->with($request, $route)->andReturn(new GenericUser(['id' => 1]));

        $this->auth->extend('one', m::mock(Provider::class));
        $this->auth->extend('two', $provider);

        $this->auth->authenticate(['two']);
        self::assertSame($provider, $this->auth->getProviderUsed());
    }

    public function testGettingUserWhenNotAuthenticatedAttemptsToAuthenticateAndReturnsNull(): void
    {
        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn(m::mock(Route::class));
        $this->router->shouldReceive('getCurrentRequest')->once()->andReturn(Request::create('foo', 'GET'));

        $this->auth->extend('provider', m::mock(Provider::class));

        self::assertNull($this->auth->user());
    }

    public function testGettingUserWhenAlreadyAuthenticatedReturnsUser(): void
    {
        $user = new GenericUser(['id' => 1]);
        $this->auth->setUser($user);

        self::assertSame(1, $this->auth->user()->id);
        self::assertTrue($this->auth->check());
    }
}
