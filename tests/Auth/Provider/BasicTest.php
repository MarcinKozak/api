<?php

namespace Dingo\Api\Tests\Auth\Provider;

use Dingo\Api\Auth\Provider\Basic;
use Dingo\Api\Routing\Route;
use Dingo\Api\Tests\BaseTestCase;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Auth\AuthManager;

class BasicTest extends BaseTestCase
{
    protected $auth;
    protected $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = m::mock(AuthManager::class);
        $this->provider = new Basic($this->auth);
    }

    public function testInvalidBasicCredentialsThrowsException(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic 12345']);

        $this->auth->shouldReceive('guard->onceBasic')->once()->with('email')->andReturn(new Response('', 401));

        $this->provider->authenticate($request, m::mock(Route::class));
    }

    public function testValidCredentialsReturnsUser(): void
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic 12345']);

        $this->auth->shouldReceive('guard->onceBasic')->once()->with('email')->andReturn(null);
        $this->auth->shouldReceive('guard->user')->once()->andReturn(new GenericUser(['id' => 1]));

        $user = $this->provider->authenticate($request, m::mock(Route::class));

        self::assertSame(1, $user->getAuthIdentifier());
    }
}
