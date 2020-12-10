<?php

namespace Dingo\Api\Tests\Exception;

use Dingo\Api\Exception\Handler;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Request as ApiRequest;
use Dingo\Api\Tests\BaseTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Mockery as m;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Contracts\Debug\ExceptionHandler;

class HandlerTest extends BaseTestCase
{
    protected $parentHandler;
    protected $exceptionHandler;

    public function setUp(): void
    {
        $this->parentHandler = m::mock(ExceptionHandler::class);
        $this->exceptionHandler = new Handler($this->parentHandler, [
            'message' => ':message',
            'errors' => ':errors',
            'code' => ':code',
            'status_code' => ':status_code',
            'debug' => ':debug',
        ], false);
    }

    public function testRegisterExceptionHandler(): void
    {
        $this->exceptionHandler->register(static function (HttpException $e) {});

        self::assertArrayHasKey(HttpException::class, $this->exceptionHandler->getHandlers());
    }

    public function testExceptionHandlerHandlesException(): void
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return new Response('foo', 404);
        });

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        self::assertSame('foo', $response->getContent());
        self::assertSame(404, $response->getStatusCode());
        self::assertSame($exception, $response->exception);
    }

    public function testExceptionHandlerHandlesExceptionAndCreatesNewResponse(): void
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return 'foo';
        });

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('foo', $response->getContent());
        self::assertSame(404, $response->getStatusCode());
    }

    public function testExceptionHandlerHandlesExceptionWithRedirectResponse(): void
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return new RedirectResponse('foo');
        });

        $exception = new HttpException(404, 'bar');

        /* @var $response RedirectResponse */
        $response = $this->exceptionHandler->handle($exception);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('foo', $response->getTargetUrl());
        self::assertSame(302, $response->getStatusCode());
    }

    public function testExceptionHandlerHandlesExceptionWithJsonResponse(): void
    {
        $this->exceptionHandler->register(function (HttpException $e) {
            return new JsonResponse(['foo' => 'bar'], 404);
        });

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame('{"foo":"bar"}', $response->getContent());
        self::assertSame(404, $response->getStatusCode());
    }

    public function testExceptionHandlerReturnsGenericWhenNoMatchingHandler(): void
    {
        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('{"message":"bar","status_code":404}', $response->getContent());
        self::assertSame(404, $response->getStatusCode());
    }

    public function testUsingMultidimensionalArrayForGenericResponse(): void
    {
        $this->exceptionHandler->setErrorFormat([
            'error' => [
                'message' => ':message',
                'errors' => ':errors',
                'code' => ':code',
                'status_code' => ':status_code',
                'debug' => ':debug',
            ],
        ]);

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('{"error":{"message":"bar","status_code":404}}', $response->getContent());
        self::assertSame(404, $response->getStatusCode());
    }

    public function testRegularExceptionsAreHandledByGenericHandler(): void
    {
        $exception = new RuntimeException('Uh oh');

        $response = $this->exceptionHandler->handle($exception);

        self::assertSame('{"message":"Uh oh","status_code":500}', $response->getContent());
        self::assertSame(500, $response->getStatusCode());
        self::assertSame($exception, $response->exception);
    }

    public function testResourceExceptionErrorsAreIncludedInResponse(): void
    {
        $exception = new ResourceException('bar', ['foo' => 'bar'], null, [], 10);

        $response = $this->exceptionHandler->handle($exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('{"message":"bar","errors":{"foo":["bar"]},"code":10,"status_code":422}', $response->getContent());
        self::assertSame(422, $response->getStatusCode());
    }

    public function testExceptionTraceIncludedInResponse(): void
    {
        $this->exceptionHandler->setDebug(true);

        $exception = new HttpException(404, 'bar');

        $response = $this->exceptionHandler->handle($exception);

        $object = json_decode($response->getContent(), false);

        self::assertObjectHasAttribute('debug', $object);
    }

    public function testHttpExceptionsWithNoMessageUseStatusCodeMessage(): void
    {
        $exception = new HttpException(404);

        $response = $this->exceptionHandler->handle($exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('{"message":"404 Not Found","status_code":404}', $response->getContent());
        self::assertSame(404, $response->getStatusCode());
    }

    public function testExceptionsHandledByRenderAreReroutedThroughHandler(): void
    {
        $request = ApiRequest::create('foo', 'GET');

        $exception = new HttpException(404);

        $response = $this->exceptionHandler->render($request, $exception);

        self::assertSame('{"message":"404 Not Found","status_code":404}', $response->getContent());
    }

    public function testSettingUserDefinedReplacements(): void
    {
        $this->exceptionHandler->setReplacements([':foo' => 'bar']);
        $this->exceptionHandler->setErrorFormat(['bing' => ':foo']);

        $exception = new HttpException(404);

        $response = $this->exceptionHandler->handle($exception);

        self::assertSame('{"bing":"bar"}', $response->getContent());
    }
}
