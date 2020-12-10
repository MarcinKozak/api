<?php

namespace Dingo\Api\Tests\Routing\Adapter;

use Dingo\Api\Contract\Routing\Adapter;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Http;
use Dingo\Api\Routing\Adapter\Laravel;
use Dingo\Api\Routing\Adapter\Lumen;
use Dingo\Api\Routing\Router;
use Dingo\Api\Tests\BaseTestCase;
use Dingo\Api\Tests\Stubs\MiddlewareStub;
use Illuminate\Container\Container;
use Mockery as m;

abstract class BaseAdapterTest extends BaseTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Laravel|Lumen
     */
    protected $adapter;
    /**
     * @var Handler
     */
    protected $exception;
    /**
     * @var Router
     */
    protected $router;

    public function setUp(): void
    {
        $this->container = $this->getContainerInstance();
        $this->container[\Illuminate\Container\Container::class] = $this->container;
        $this->container['api.auth'] = new MiddlewareStub;
        $this->container['api.limiting'] = new MiddlewareStub;
        $this->container['api.controllers'] = new MiddlewareStub;
        $this->container['request'] = new Http\Request;

        Http\Request::setAcceptParser(new Http\Parser\Accept('vnd', 'api', 'v1', 'json'));

        $this->adapter = $this->getAdapterInstance();
        $this->exception = m::mock(Handler::class);
        $this->router = new Router($this->adapter, $this->exception, $this->container, null, null);
        app()->instance(\Illuminate\Routing\Router::class, $this->adapter);

        Http\Response::setFormatters(['json' => new Http\Response\Format\Json]);
    }

    /**
     * @return Container
     */
    abstract public function getContainerInstance() : Container;

    /**
     * @return Adapter
     */
    abstract public function getAdapterInstance() : Adapter;

    /**
     * @param string $uri
     * @param string $method
     * @param array $headers
     * @return Http\Request
     */
    protected function createRequest(string $uri, string $method, array $headers = []) : Http\Request
    {
        $request = Http\Request::create($uri, $method);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $this->container['request'] = $request;
    }

    public function testBasicRouteVersions() : void
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
            $this->router->post('foo', function () {
                return 'posted';
            });
            $this->router->patch('foo', function () {
                return 'patched';
            });
            $this->router->delete('foo', function () {
                return 'deleted';
            });
            $this->router->put('foo', function () {
                return 'put';
            });
            $this->router->options('foo', function () {
                return 'options';
            });
        });

        $this->router->group(['version' => 'v2'], function () {
            $this->router->get('foo', ['version' => 'v3', function () {
                return 'bar';
            }]);
        });

        $this->createRequest('/', 'GET');

        self::assertArrayHasKey('v1', $this->router->getRoutes(), 'No routes were registered for version 1.');
        self::assertArrayHasKey('v2', $this->router->getRoutes(), 'No routes were registered for version 2.');
        self::assertArrayHasKey('v3', $this->router->getRoutes(), 'No routes were registered for version 3.');

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        self::assertSame('foo', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo/', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        self::assertSame('foo', $this->router->dispatch($request)->getContent(), 'Could not dispatch request with trailing slash.');

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        self::assertSame('bar', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v3+json']);
        self::assertSame('bar', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'POST', ['accept' => 'application/vnd.api.v1+json']);
        self::assertSame('posted', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'PATCH', ['accept' => 'application/vnd.api.v1+json']);
        self::assertSame('patched', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'DELETE', ['accept' => 'application/vnd.api.v1+json']);
        self::assertSame('deleted', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'PUT', ['accept' => 'application/vnd.api.v1+json']);
        self::assertSame('put', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'options', ['accept' => 'application/vnd.api.v1+json']);
        self::assertSame('options', $this->router->dispatch($request)->getContent());
    }

    public function testAdapterDispatchesRequestsThroughRouter() : void
    {
        $this->container['request'] = Http\Request::create('/foo', 'GET');

        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $response = $this->router->dispatch($this->container['request']);

        self::assertSame('foo', $response->getContent());
    }

    public function testRoutesWithPrefix() : void
    {
        $this->router->version('v1', ['prefix' => 'foo/bar'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version('v2', ['prefix' => 'foo/bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = $this->createRequest('/foo/bar/foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        self::assertSame('bar', $this->router->dispatch($request)->getContent(), 'Router could not dispatch prefixed routes.');
    }

    public function testRoutesWithDomains() : void
    {
        $this->router->version('v1', ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version('v2', ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = $this->createRequest('http://foo.bar/foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        self::assertSame('bar', $this->router->dispatch($request)->getContent(), 'Router could not dispatch domain routes.');
    }

    public function testPointReleaseVersions() : void
    {
        $this->router->version('v1.1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version('v2.0.1', function () {
            $this->router->get('bar', function () {
                return 'bar';
            });
        });

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v1.1+json']);
        self::assertSame('foo', $this->router->dispatch($request)->getContent(), 'Router does not support point release versions.');

        $request = $this->createRequest('/bar', 'GET', ['accept' => 'application/vnd.api.v2.0.1+json']);
        self::assertSame('bar', $this->router->dispatch($request)->getContent(), 'Router does not support point release versions.');
    }

    public function testRoutingResources() : void
    {
        $this->router->version('v1', ['namespace' => '\Dingo\Api\Tests\Stubs'], function () {
            $this->router->resources([
                'bar' => ['RoutingControllerStub', ['only' => ['index']]],
            ]);
        });

        $request = $this->createRequest('/bar', 'GET', ['accept' => 'application/vnd.api.v1+json']);

        self::assertSame('foo', $this->router->dispatch($request)->getContent(), 'Router did not register controller correctly.');
    }

    public function testIterableRoutes() : void
    {
        $this->router->version('v1', ['namespace' => '\Dingo\Api\Tests\Stubs'], function () {
            $this->router->post('/', ['uses' => 'RoutingControllerStub@index']);
            $this->router->post('/find', ['uses' => 'RoutingControllerOtherStub@show']);
        });

        $routes = $this->adapter->getIterableRoutes();
        self::assertTrue(array_key_exists('v1', (array) $routes));
        self::assertCount(2, $routes['v1']);
    }
}
