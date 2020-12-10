<?php

namespace Dingo\Api\Tests\Routing;

use Dingo\Api\Http;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use Dingo\Api\Tests\Stubs\BasicThrottleStub;
use Dingo\Api\Tests\Stubs\RoutingAdapterStub;
use Dingo\Api\Tests\Stubs\RoutingControllerStub;
use Dingo\Api\Tests\Stubs\ThrottleStub;
use Illuminate\Container\Container;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RouterTest extends Adapter\BaseAdapterTest
{
    public function getAdapterInstance() : RoutingAdapterStub
    {
        return $this->container->make(RoutingAdapterStub::class);
    }

    public function getContainerInstance() : Container
    {
        return new Container;
    }

    public function testRouteOptionsMergeCorrectly() : void
    {
        $this->router->version('v1', ['scopes' => 'foo|bar'], function () {
            $this->router->get('foo', ['scopes' => ['baz'], function () {
                self::assertSame(
                    ['foo', 'bar', 'baz'],
                    $this->router->getCurrentRoute()->getScopes(),
                    'Router did not merge string based group scopes with route based array scopes.'
                );
            }]);

            $this->router->get('baz', function () {
                self::assertSame(
                    ['foo', 'bar'],
                    $this->router->getCurrentRoute()->getScopes(),
                    'Router did not merge string based group scopes with route.'
                );
            });
        });

        $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->router->dispatch($request);

        $request = $this->createRequest('baz', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->router->dispatch($request);

        $this->router->version('v2', ['providers' => 'foo', 'throttle' => new ThrottleStub(['limit' => 10, 'expires' => 15]), 'namespace' => '\Dingo\Api\Tests'], function () {
            $this->router->get('foo', 'Stubs\RoutingControllerStub@index');
        });

        $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        $this->router->dispatch($request);

        $route = $this->router->getCurrentRoute();

        self::assertSame(['baz', 'bing'], $route->scopes());
        self::assertSame(['foo', 'red', 'black'], $route->getAuthenticationProviders());
        self::assertSame(10, $route->getRateLimit());
        self::assertSame(20, $route->getRateLimitExpiration());
        self::assertInstanceOf(BasicThrottleStub::class, $route->getThrottle());
    }

    public function testGroupAsPrefixesRouteAs(): void
    {
        $this->router->version('v1', ['as' => 'api'], function ($api) {
            $api->get('users', ['as' => 'users', function () {
                return 'foo';
            }]);
        });

        $routes = $this->router->getRoutes('v1');

        self::assertInstanceOf(Route::class, $routes->getByName('api.users'));
    }

    public function testNoGroupVersionThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A version is required for an API group definition.');

        $this->router->group([], function () {
            //
        });
    }

    public function testMatchRoutes(): void
    {
        $this->router->version('v1', function ($api) {
            $api->match(['get', 'post'], 'foo', function () {
                return 'bar';
            });
        });

        $this->router->setConditionalRequest(false);

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('bar', $response->getContent());

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'POST', ['accept' => 'application/vnd.api.v1+json'])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('bar', $response->getContent());
    }

    public function testAnyRoutes(): void
    {
        $this->router->version('v1', function ($api) {
            $api->any('foo', function () {
                return 'bar';
            });
        });

        $this->router->setConditionalRequest(false);

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('bar', $response->getContent());

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'POST', ['accept' => 'application/vnd.api.v1+json'])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('bar', $response->getContent());

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'PATCH', ['accept' => 'application/vnd.api.v1+json'])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('bar', $response->getContent());

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'DELETE', ['accept' => 'application/vnd.api.v1+json'])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('bar', $response->getContent());
    }

    public function testRouterPreparesNotModifiedResponse(): void
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->router->setConditionalRequest(false);

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('bar', $response->getContent());

        $this->router->setConditionalRequest(true);

        $response = $this->router->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('"'.sha1('bar').'"', $response->getETag());
        self::assertSame('bar', $response->getContent());

        $request = $this->createRequest('foo', 'GET', [
            'if-none-match' => '"'.sha1('bar').'"',
            'accept' => 'application/vnd.api.v1+json',
        ]);

        $response = $this->router->dispatch($request);

        self::assertSame(304, $response->getStatusCode());
        self::assertSame('"'.sha1('bar').'"', $response->getETag());
        self::assertEmpty($response->getContent());

        $request = $this->createRequest('foo', 'GET', [
            'if-none-match' => '123456789',
            'accept' => 'application/vnd.api.v1+json',
        ]);

        $response = $this->router->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('"'.sha1('bar').'"', $response->getETag());
        self::assertSame('bar', $response->getContent());
    }

    public function testRouterHandlesExistingEtag(): void
    {
        $this->router->version('v1', ['conditional_request' => true], function () {
            $this->router->get('foo', function () {
                $response = new Http\Response('bar');
                $response->setEtag('custom-etag');

                return $response;
            });
        });

        $response = $this->router->dispatch(
            $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('"custom-etag"', $response->getETag());
        self::assertSame('bar', $response->getContent());
    }

    public function testRouterHandlesCustomEtag(): void
    {
        $this->router->version('v1', ['conditional_request' => true], function () {
            $this->router->get('foo', function () {
                $response = new Http\Response('bar');
                $response->setEtag('custom-etag');

                return $response;
            });
        });

        $response = $this->router->dispatch(
            $this->createRequest('foo', 'GET', [
                'if-none-match' => '"custom-etag"',
                'accept' => 'application/vnd.api.v1+json',
            ])
        );

        self::assertSame(304, $response->getStatusCode());
        self::assertSame('"custom-etag"', $response->getETag());
        self::assertEmpty($response->getContent());
    }

    public function testExceptionsAreHandledByExceptionHandler(): void
    {
        $exception = new HttpException(400);

        $this->router->version('v1', function () use ($exception) {
            $this->router->get('foo', function () use ($exception) {
                throw $exception;
            });
        });

        $this->exception->shouldReceive('report')->once()->with($exception);
        $this->exception->shouldReceive('handle')->once()->with($exception)->andReturn(new Http\Response('exception'));

        $request = $this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v1+json']);

        self::assertSame('exception', $this->router->dispatch($request)->getContent(), 'Router did not delegate exception handling.');
    }

    public function testNoAcceptHeaderUsesDefaultVersion(): void
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        self::assertSame('foo', $this->router->dispatch($this->createRequest('foo', 'GET'))->getContent(), 'Router does not default to default version.');
    }

    public function testRoutesAddedToCorrectVersions(): void
    {
        $this->router->version('v1', ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $this->router->version('v2', ['domain' => 'foo.bar'], function () {
            $this->router->get('bar', function () {
                return 'baz';
            });
        });

        $this->createRequest('/', 'GET');

        self::assertCount(1, $this->router->getRoutes()['v1'], 'Routes were not added to the correct versions.');
    }

    public function testUnsuccessfulResponseThrowsHttpException(): void
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return new Http\Response('Failed!', 400);
            });
        });

        $request = $this->createRequest('foo', 'GET');

        $this->exception->shouldReceive('handle')->with(m::type(HttpException::class))->andReturn(new Http\Response('Failed!'));

        self::assertSame('Failed!', $this->router->dispatch($request)->getContent(), 'Router did not throw and handle a HttpException.');
    }

    public function testGroupNamespacesAreConcatenated(): void
    {
        $this->router->version('v1', ['namespace' => '\Dingo\Api'], function () {
            $this->router->group(['namespace' => 'Tests\Stubs'], function () {
                $this->router->get('foo', 'RoutingControllerStub@getIndex');
            });
        });

        $request = $this->createRequest('foo', 'GET');

        self::assertSame('foo', $this->router->dispatch($request)->getContent(), 'Router did not concatenate controller namespace correctly.');
    }

    public function testCurrentRouteName(): void
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', ['as' => 'foo', function () {
                return 'foo';
            }]);
        });

        $request = $this->createRequest('foo', 'GET');

        $this->router->dispatch($request);

        self::assertFalse($this->router->currentRouteNamed('bar'));
        self::assertTrue($this->router->currentRouteNamed('foo'));
        self::assertTrue($this->router->is('*'));
        self::assertFalse($this->router->is('b*'));
        self::assertTrue($this->router->is('b*', 'f*'));
    }

    public function testCurrentRouteAction(): void
    {
        $this->router->version('v1', ['namespace' => '\Dingo\Api\Tests\Stubs'], function () {
            $this->router->get('foo', 'RoutingControllerStub@getIndex');
        });

        $request = $this->createRequest('foo', 'GET');

        $this->router->dispatch($request);

        self::assertFalse($this->router->currentRouteUses('foo'));
        self::assertTrue($this->router->currentRouteUses(RoutingControllerStub::class.'@getIndex'));
        self::assertFalse($this->router->uses('foo*'));
        self::assertTrue($this->router->uses('*'));
        self::assertTrue($this->router->uses(RoutingControllerStub::class.'@*'));
    }

    public function testRoutePatternsAreAppliedCorrectly(): void
    {
        $adapter = $this->adapter;
        $adapter->pattern('bar', '[0-9]+');

        $this->router = new Router($adapter, $this->exception, $this->container, null, null);
        $this->router->version('v1', function ($api) {
            $api->any('foo/{bar}', function () {
                return 'bar';
            });
        });

        $this->router->setConditionalRequest(false);

        $this->exception->shouldReceive('report')->once()->with(HttpException::class);
        $this->exception->shouldReceive('handle')->with(m::type(HttpException::class))->andReturn(new Http\Response('Not Found!', 404));

        $response = $this->router->dispatch(
            $request = $this->createRequest('foo/abc', 'GET', ['accept' => 'application/vnd.api.v1+json'])
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Not Found!', $response->getContent());
    }
}
