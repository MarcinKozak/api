<?php

namespace Dingo\Api\Tests\Transformer;

use Dingo\Api\Tests\BaseTestCase;
use Dingo\Api\Tests\Stubs\TransformerStub;
use Dingo\Api\Tests\Stubs\UserStub;
use Dingo\Api\Tests\Stubs\UserTransformerStub;
use Dingo\Api\Transformer\Factory;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mockery;

class FactoryTest extends BaseTestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    public function setUp(): void
    {
        $container = new Container;
        $container['request'] = Mockery::mock(\Dingo\Api\Http\Request::class);

        $this->factory = new Factory($container, new TransformerStub);
    }

    public function testResponseIsTransformable()
    {
        self::assertFalse($this->factory->transformableResponse(new UserStub('Jason'), new UserTransformerStub));

        $this->factory->register(UserStub::class, new UserTransformerStub);

        self::assertTrue($this->factory->transformableResponse(new UserStub('Jason'), new UserTransformerStub));
    }

    public function testRegisterParameterOrder()
    {
        // Third parameter is parameters and fourth is callback.
        $binding = $this->factory->register(UserStub::class, new UserTransformerStub,
            ['foo' => 'bar'], function ($foo) {
                self::assertSame('foo', $foo);
            });

        $binding->fireCallback('foo');
        self::assertSame(['foo' => 'bar'], $binding->getParameters());

        // Third parameter is parameters and fourth is null.
        $binding = $this->factory->register(UserStub::class, new UserTransformerStub,
            ['foo' => 'bar']);

        self::assertSame(['foo' => 'bar'], $binding->getParameters());

        // Third parameter is an empty array and fourth is callback.
        $binding = $this->factory->register(UserStub::class, new UserTransformerStub, [],
            function ($foo) {
                self::assertSame('foo', $foo);
            });

        $binding->fireCallback('foo');
    }

    public function testResponseIsTransformableType()
    {
        self::assertFalse($this->factory->transformableType(['foo' => 'bar']));
        self::assertTrue($this->factory->transformableType('Foo'));
        self::assertTrue($this->factory->transformableType((object) ['foo' => 'bar']));
    }

    public function testTransformingResponse()
    {
        $this->factory->register(UserStub::class, new UserTransformerStub);

        $response = $this->factory->transform(new UserStub('Jason'));

        self::assertSame(['name' => 'Jason'], $response);
    }

    public function testTransformingCollectionResponse()
    {
        $this->factory->register(UserStub::class, new UserTransformerStub);

        $response = $this->factory->transform(new Collection([new UserStub('Jason'), new UserStub('Bob')]));

        self::assertSame([['name' => 'Jason'], ['name' => 'Bob']], $response);
    }

    public function testTransforingWithIlluminateRequest()
    {
        $container = new Container;
        $container['request'] = new Request();

        $factory = new Factory($container, new TransformerStub);

        $factory->register(UserStub::class, new UserTransformerStub);

        $response = $factory->transform(new UserStub('Jason'));

        self::assertSame(['name' => 'Jason'], $response);
    }

    public function testTransformingWithNoTransformerThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to find bound transformer for "Dingo\Api\Tests\Stubs\UserStub" class');

        $this->factory->transform(new UserStub('Jason'));
    }
}
