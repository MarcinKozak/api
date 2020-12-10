<?php

namespace Dingo\Api\Auth;

use Dingo\Api\Contract\Auth\Provider;
use Exception;
use Dingo\Api\Routing\Router;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Contracts\Auth\Authenticatable;

class Auth
{
    /**
     * Router instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * Illuminate container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Array of available authentication providers.
     *
     * @var array
     */
    protected $providers;

    /**
     * The provider used for authentication.
     *
     * @var Provider
     */
    protected $providerUsed;

    /**
     * Authenticated user instance.
     *
     * @var Authenticatable
     */
    protected $user;

    /**
     * Create a new auth instance.
     *
     * @param Router $router
     * @param Container $container
     * @param array $providers
     */
    public function __construct(Router $router, Container $container, array $providers)
    {
        $this->router = $router;
        $this->container = $container;
        $this->providers = $providers;
    }

    /**
     * Authenticate the current request.
     *
     * @param array $providers
     *
     * @return Authenticatable|null
     * @throws UnauthorizedHttpException
     *
     */
    public function authenticate(array $providers = []) : ?Authenticatable
    {
        $exceptionStack = [];

        // Spin through each of the registered authentication providers and attempt to
        // authenticate through one of them. This allows a developer to implement
        // and allow a number of different authentication mechanisms.
        foreach ($this->filterProviders($providers) as $provider) {
            try {
                $user = $provider->authenticate($this->router->getCurrentRequest(), $this->router->getCurrentRoute());

                $this->providerUsed = $provider;

                return $this->user = $user;
            } catch (UnauthorizedHttpException $exception) {
                $exceptionStack[] = $exception;
            } catch (BadRequestHttpException $exception) {
                // We won't add this exception to the stack as it's thrown when the provider
                // is unable to authenticate due to the correct authorization header not
                // being set. We will throw an exception for this below.
            }
        }

        $this->throwUnauthorizedException($exceptionStack);

        return null;
    }

    /**
     * Throw the first exception from the exception stack.
     *
     * @param array $exceptionStack
     *
     * @return void
     * @throws UnauthorizedHttpException
     */
    protected function throwUnauthorizedException(array $exceptionStack): void {
        $exception = array_shift($exceptionStack);

        if ($exception === null) {
            $exception = new UnauthorizedHttpException('dingo', 'Failed to authenticate because of bad credentials or an invalid authorization header.');
        }

        throw $exception;
    }

    /**
     * Filter the requested providers from the available providers.
     *
     * @param array $providers
     * @return array
     */
    protected function filterProviders(array $providers) : array
    {
        if (empty($providers)) {
            return $this->providers;
        }

        return array_intersect_key($this->providers, array_flip($providers));
    }

    /**
     * Get the authenticated user.
     *
     * @param bool $authenticate
     *
     * @return Authenticatable|null
     */
    public function getUser($authenticate = true)  : ?Authenticatable
    {
        if ($this->user) {
            return $this->user;
        }

        if (! $authenticate) {
            return null;
        }

        try {
            return $this->user = $this->authenticate();
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * Alias for getUser.
     *
     * @param bool $authenticate
     *
     * @return Authenticatable|null
     */
    public function user($authenticate = true)  : ?Authenticatable
    {
        return $this->getUser($authenticate);
    }

    /**
     * Set the authenticated user.
     *
     * @param Authenticatable $user
     *
     * @return Auth
     */
    public function setUser(Authenticatable $user) : self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Check if a user has authenticated with the API.
     *
     * @param bool $authenticate
     *
     * @return bool
     */
    public function check($authenticate = false) : bool
    {
        return ! is_null($this->user($authenticate));
    }

    /**
     * Get the provider used for authentication.
     *
     * @return Provider
     */
    public function getProviderUsed() : Provider
    {
        return $this->providerUsed;
    }

    /**
     * Extend the authentication layer with a custom provider.
     *
     * @param string          $key
     * @param object|callable $provider
     *
     * @return void
     */
    public function extend(string $key, $provider) : void
    {
        if (is_callable($provider)) {
            $provider = $provider($this->container);
        }

        $this->providers[$key] = $provider;
    }
}
