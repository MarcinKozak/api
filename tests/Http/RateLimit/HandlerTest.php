<?php

namespace Dingo\Api\Tests\Http\RateLimit;

use Dingo\Api\Http\RateLimit\Handler;
use Dingo\Api\Http\RateLimit\Throttle\Route;
use Dingo\Api\Http\Request;
use Dingo\Api\Tests\BaseTestCase;
use Dingo\Api\Tests\Stubs\ThrottleStub;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;

class HandlerTest extends BaseTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var CacheManager
     */
    protected $cache;
    /**
     * @var Handler
     */
    protected $limiter;

    public function setUp(): void
    {
        $this->container = new Container;
        $this->container['config'] = ['cache.default' => 'array', 'cache.stores.array' => ['driver' => 'array']];

        $this->cache = new CacheManager($this->container);
        $this->limiter = new Handler($this->container, $this->cache, []);

        $this->limiter->setRateLimiter(function ($container, $request) {
            return $request->getClientIp();
        });
    }

    public function testSettingSpecificLimitsOnRouteUsesRouteSpecificThrottle()
    {
        $this->limiter->rateLimitRequest(Request::create('test', 'GET'), 100, 100);

        $throttle = $this->limiter->getThrottle();

        self::assertInstanceOf(Route::class, $throttle);
        self::assertSame(100, $throttle->getLimit());
        self::assertSame(100, $throttle->getExpires());
    }

    public function testThrottleWithHighestAmountOfRequestsIsUsedWhenMoreThanOneMatchingThrottle()
    {
        $this->limiter->extend($first = new ThrottleStub(['limit' => 100, 'expires' => 200]));
        $this->limiter->extend($second = new ThrottleStub(['limit' => 99, 'expires' => 400]));

        $this->limiter->rateLimitRequest(Request::create('test', 'GET'));

        self::assertSame($first, $this->limiter->getThrottle());
    }

    public function testExceedingOfRateLimit()
    {
        $request = Request::create('test', 'GET');

        $this->limiter->rateLimitRequest($request);
        self::assertFalse($this->limiter->exceededRateLimit());

        $this->limiter->extend(new ThrottleStub(['limit' => 1, 'expires' => 200]));
        $this->limiter->rateLimitRequest($request);
        self::assertFalse($this->limiter->exceededRateLimit());

        $this->limiter->rateLimitRequest($request);
        self::assertTrue($this->limiter->exceededRateLimit());
    }

    public function testGettingTheRemainingLimit()
    {
        $this->limiter->extend(new ThrottleStub(['limit' => 10, 'expires' => 200]));
        $this->limiter->rateLimitRequest(Request::create('test', 'GET'));
        self::assertSame(9, $this->limiter->getRemainingLimit());
    }
}
