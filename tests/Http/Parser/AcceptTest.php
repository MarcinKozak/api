<?php

namespace Dingo\Api\Tests\Http\Parser;

use Dingo\Api\Http\Parser\Accept;
use Dingo\Api\Http\Request;
use Dingo\Api\Tests\BaseTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AcceptTest extends BaseTestCase
{
    public function testParsingInvalidAcceptReturnsDefaults()
    {
        $parser = new Accept('vnd', 'api', 'v1', 'json');

        $accept = $parser->parse($this->createRequest('foo', 'GET', ['accept' => 'application/vnd.foo.v2+xml']));

        self::assertSame('api', $accept['subtype']);
        self::assertSame('v1', $accept['version']);
        self::assertSame('json', $accept['format']);
    }

    public function testStrictlyParsingInvalidAcceptHeaderThrowsException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Accept header could not be properly parsed because of a strict matching process.');

        $parser = new Accept('vnd', 'api', 'v1', 'json');

        $parser->parse($this->createRequest('foo', 'GET', ['accept' => 'application/vnd.foo.v2+xml']), true);
    }

    public function testParsingValidAcceptReturnsHeaderValues()
    {
        $parser = new Accept('vnd', 'api', 'v1', 'json');

        $accept = $parser->parse($this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.v2+xml']));

        self::assertSame('api', $accept['subtype']);
        self::assertSame('v2', $accept['version']);
        self::assertSame('xml', $accept['format']);
    }

    public function testApiVersionWithoutVSuffix()
    {
        $parser = new Accept('vnd', 'api', '1.0', 'json');

        $accept = $parser->parse($this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.1.0+xml']));

        self::assertSame('api', $accept['subtype']);
        self::assertSame('1.0', $accept['version']);
        self::assertSame('xml', $accept['format']);
    }

    public function testApiVersionWithHyphen()
    {
        $parser = new Accept('vnd', 'api', '1.0-beta', 'json');

        $accept = $parser->parse($this->createRequest('foo', 'GET', ['accept' => 'application/vnd.api.1.0-beta+xml']));

        self::assertSame('api', $accept['subtype']);
        self::assertSame('1.0-beta', $accept['version']);
        self::assertSame('xml', $accept['format']);
    }

    protected function createRequest($uri, $method, array $headers = [])
    {
        $request = Request::create($uri, $method);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $request;
    }
}
