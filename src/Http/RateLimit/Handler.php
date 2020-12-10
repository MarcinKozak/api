<?php

namespace Dingo\Api\Http\RateLimit;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Dingo\Api\Http\RateLimit\Throttle\Route;
use Dingo\Api\Contract\Http\RateLimit\Throttle;
use Dingo\Api\Contract\Http\RateLimit\HasRateLimiter;

class Handler
{
    /**
     * Container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Cache instance.
     *
     * @var CacheRepository
     */
    protected $cache;

    /**
     * Registered throttles.
     *
     * @var Collection
     */
    protected $throttles;

    /**
     * Throttle used for rate limiting.
     *
     * @var Throttle
     */
    protected $throttle;

    /**
     * Request instance being throttled.
     *
     * @var Request
     */
    protected $request;

    /**
     * The key prefix used when throttling route specific requests.
     *
     * @var string
     */
    protected $keyPrefix;

    /**
     * A callback used to define the limiter.
     *
     * @var callable
     */
    protected $limiter;

    /**
     * Create a new rate limit handler instance.
     *
     * @param Container $container
     * @param CacheRepository $cache
     * @param array $throttles
     */
    public function __construct(Container $container, CacheRepository $cache, array $throttles)
    {
        $this->cache = $cache;
        $this->container = $container;
        $this->throttles = new Collection($throttles);
    }

    /**
     * @param Request $request
     * @param int $limit
     * @param int $expires
     */
    public function rateLimitRequest(Request $request, int $limit = 0, int $expires = 0) : void
    {
        $this->request = $request;

        // If the throttle instance is already set then we'll just carry on as
        // per usual.
        if ($this->throttle instanceof Throttle) {

            // If the developer specified a certain amount of requests or expiration
        // time on a specific route then we'll always use the route specific
        // throttle with the given values.
        } elseif ($limit > 0 || $expires > 0) {
            $this->throttle = new Route(['limit' => $limit, 'expires' => $expires]);
            $this->keyPrefix = sha1($request->path());

        // Otherwise we'll use the throttle that gives the consumer the largest
        // amount of requests. If no matching throttle is found then rate
        // limiting will not be imposed for the request.
        } else {
            $this->throttle = $this->getMatchingThrottles()->sort(function ($a, $b) {
                return $a->getLimit() < $b->getLimit();
            })->first();
        }

        if (is_null($this->throttle)) {
            return;
        }

        if ($this->throttle instanceof HasRateLimiter) {
            $this->setRateLimiter([$this->throttle, 'getRateLimiter']);
        }

        $this->prepareCacheStore();

        $this->cache('requests', 0, $this->throttle->getExpires());
        $this->cache('expires', $this->throttle->getExpires(), $this->throttle->getExpires());
        $this->cache('reset', time() + ($this->throttle->getExpires() * 60), $this->throttle->getExpires());
        $this->increment('requests');
    }

    /**
     * Prepare the cache store.
     *
     * @return void
     */
    protected function prepareCacheStore() : void
    {
        if ($this->retrieve('expires') !== $this->throttle->getExpires()) {
            $this->forget('requests');
            $this->forget('expires');
            $this->forget('reset');
        }
    }

    /**
     * Determine if the rate limit has been exceeded.
     *
     * @return bool
     */
    public function exceededRateLimit() : bool
    {
        return $this->requestWasRateLimited() ? $this->retrieve('requests') > $this->throttle->getLimit() : false;
    }

    /**
     * Get matching throttles after executing the condition of each throttle.
     *
     * @return Collection
     */
    protected function getMatchingThrottles() : Collection
    {
        return $this->throttles->filter(function ($throttle) {
            return $throttle->match($this->container);
        });
    }

    /**
     * Namespace a cache key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function key(string $key) : string
    {
        return sprintf('dingo.api.%s.%s', $key, $this->getRateLimiter());
    }

    /**
     * Cache a value under a given key for a certain amount of minutes.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     *
     * @return void
     */
    protected function cache(string $key, $value, int $minutes) : void
    {
        $this->cache->add($this->key($key), $value, Carbon::now()->addMinutes($minutes));
    }

    /**
     * Retrieve a value from the cache store.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function retrieve(string $key)
    {
        return $this->cache->get($this->key($key));
    }

    /**
     * Increment a key in the cache.
     *
     * @param string $key
     *
     * @return void
     */
    protected function increment(string $key) : void
    {
        $this->cache->increment($this->key($key));
    }

    /**
     * Forget a key in the cache.
     *
     * @param string $key
     *
     * @return void
     */
    protected function forget(string $key) : void
    {
        $this->cache->forget($this->key($key));
    }

    /**
     * Determine if the request was rate limited.
     *
     * @return bool
     */
    public function requestWasRateLimited() : bool
    {
        return ! is_null($this->throttle);
    }

    /**
     * Get the rate limiter.
     *
     * @return string
     */
    public function getRateLimiter() : string
    {
        $closure = $this->limiter ?: static function ($container, $request) {
            return $request->getClientIp();
        };

        return $closure($this->container, $this->request);
    }

    /**
     * Set the rate limiter.
     *
     * @param callable $limiter
     *
     * @return void
     */
    public function setRateLimiter(callable $limiter) : void
    {
        $this->limiter = $limiter;
    }

    /**
     * Set the throttle to use for rate limiting.
     *
     * @param string|Throttle $throttle
     *
     * @return void
     */
    public function setThrottle($throttle) : void
    {
        if (is_string($throttle)) {
            $throttle = $this->container->make($throttle);
        }

        $this->throttle = $throttle;
    }

    /**
     * Get the throttle used to rate limit the request.
     *
     * @return Throttle
     */
    public function getThrottle() : Throttle
    {
        return $this->throttle;
    }

    /**
     * Get the limit of the throttle used.
     *
     * @return int
     */
    public function getThrottleLimit() : int
    {
        return $this->throttle->getLimit();
    }

    /**
     * Get the remaining limit before the consumer is rate limited.
     *
     * @return int
     */
    public function getRemainingLimit() : int
    {
        $remaining = $this->throttle->getLimit() - $this->retrieve('requests');

        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Get the timestamp for when the current rate limiting will expire.
     *
     * @return int
     */
    public function getRateLimitReset() : int
    {
        return $this->retrieve('reset');
    }

    /**
     * Extend the rate limiter by adding a new throttle.
     *
     * @param callable|Throttle $throttle
     *
     * @return void
     */
    public function extend($throttle) : void
    {
        if (is_callable($throttle)) {
            $throttle = $throttle($this->container);
        }

        $this->throttles->push($throttle);
    }
}
