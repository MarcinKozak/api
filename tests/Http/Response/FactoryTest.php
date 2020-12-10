<?php

namespace Dingo\Api\Tests\Http\Response;

use Closure;
use Dingo\Api\Http\Response\Factory;
use Dingo\Api\Tests\BaseTestCase;
use Dingo\Api\Tests\Stubs\UserStub;
use Dingo\Api\Transformer\Factory as TransformerFactory;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FactoryTest extends BaseTestCase
{
    /**
     * @var TransformerFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    protected $transformer;
    /**
     * @var Factory
     */
    protected $factory;

    public function setUp(): void
    {
        $this->transformer = Mockery::mock(TransformerFactory::class);
        $this->factory = new Factory($this->transformer);
    }

    public function testMakingACreatedResponse()
    {
        $response = $this->factory->created();
        $responseWithLocation = $this->factory->created('test');

        self::assertSame($response->getStatusCode(), 201);
        self::assertFalse($response->headers->has('Location'));

        self::assertSame($responseWithLocation->getStatusCode(), 201);
        self::assertTrue($responseWithLocation->headers->has('Location'));
        self::assertSame($responseWithLocation->headers->get('Location'), 'test');
    }

    public function testMakingAnAcceptedResponse()
    {
        $response = $this->factory->accepted();
        $responseWithLocation = $this->factory->accepted('testHeader');
        $responseWithContent = $this->factory->accepted(null, 'testContent');
        $responseWithBoth = $this->factory->accepted('testHeader', 'testContent');

        self::assertSame($response->getStatusCode(), 202);
        self::assertFalse($response->headers->has('Location'));
        self::assertSame('', $response->getContent());

        self::assertSame($responseWithLocation->getStatusCode(), 202);
        self::assertTrue($responseWithLocation->headers->has('Location'));
        self::assertSame($responseWithLocation->headers->get('Location'), 'testHeader');
        self::assertSame('', $responseWithLocation->getContent());

        self::assertSame($responseWithContent->getStatusCode(), 202);
        self::assertFalse($responseWithContent->headers->has('Location'));
        self::assertSame('testContent', $responseWithContent->getContent());

        self::assertSame($responseWithBoth->getStatusCode(), 202);
        self::assertTrue($responseWithBoth->headers->has('Location'));
        self::assertSame($responseWithBoth->headers->get('Location'), 'testHeader');
        self::assertSame('testContent', $responseWithBoth->getContent());
    }

    public function testMakingANoContentResponse()
    {
        $response = $this->factory->noContent();
        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', $response->getContent());
    }

    public function testMakingCollectionRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], null);

        self::assertInstanceOf(Collection::class, $this->factory->collection(new Collection([new UserStub('Jason')]), 'test')->getOriginalContent());
        self::assertInstanceOf(Collection::class, $this->factory->withCollection(new Collection([new UserStub('Jason')]), 'test')->getOriginalContent());
    }

    public function testMakingCollectionResponseWithThreeParameters()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], Mockery::on(function ($param) {
            return $param instanceof Closure;
        }));

        self::assertInstanceOf(Collection::class, $this->factory->collection(new Collection([new UserStub('Jason')]), 'test', function ($resource, $fractal) {
            self::assertInstanceOf(\League\Fractal\Resource\Collection::class, $resource);
            self::assertInstanceOf(Manager::class, $fractal);
        })->getOriginalContent());
        self::assertInstanceOf(Collection::class, $this->factory->withCollection(new Collection([new UserStub('Jason')]), 'test', function ($resource, $fractal) {
            self::assertInstanceOf(\League\Fractal\Resource\Collection::class, $resource);
            self::assertInstanceOf(Manager::class, $fractal);
        })->getOriginalContent());
    }

    public function testMakingItemsRegistersClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], null);

        self::assertInstanceOf(UserStub::class, $this->factory->item(new UserStub('Jason'), 'test')->getOriginalContent());
        self::assertInstanceOf(UserStub::class, $this->factory->withItem(new UserStub('Jason'), 'test')->getOriginalContent());
    }

    public function testMakingItemResponseWithThreeParameters()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], Mockery::on(function ($param) {
            return $param instanceof Closure;
        }));

        self::assertInstanceOf(UserStub::class, $this->factory->item(new UserStub('Jason'), 'test', function ($resource, $fractal) {
            self::assertInstanceOf(Item::class, $resource);
            self::assertInstanceOf(Manager::class, $fractal);
        })->getOriginalContent());
        self::assertInstanceOf(UserStub::class, $this->factory->withItem(new UserStub('Jason'), 'test', function ($resource, $fractal) {
            self::assertInstanceOf(Item::class, $resource);
            self::assertInstanceOf(Manager::class, $fractal);
        })->getOriginalContent());
    }

    public function testMakingPaginatorRegistersUnderlyingClassWithTransformer()
    {
        $this->transformer->shouldReceive('register')->twice()->with(UserStub::class, 'test', [], null);

        self::assertInstanceOf(Paginator::class, $this->factory->paginator(new Paginator([new UserStub('Jason')], 1), 'test')->getOriginalContent());
        self::assertInstanceOf(Paginator::class, $this->factory->withPaginator(new Paginator([new UserStub('Jason')], 1), 'test')->getOriginalContent());
    }

    public function testNotFoundThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorNotFound();
    }

    public function testBadRequestThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorBadRequest();
    }

    public function testForbiddenThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorForbidden();
    }

    public function testInternalThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorInternal();
    }

    public function testUnauthorizedThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorUnauthorized();
    }

    public function testMethodNotAllowedThrowsHttpException()
    {
        $this->expectException(HttpException::class);

        $this->factory->errorMethodNotAllowed();
    }

    public function testMakingArrayResponse()
    {
        $response = $this->factory->array(['foo' => 'bar']);
        self::assertSame('{"foo":"bar"}', $response->getContent());
    }

    public function testPrefixingWithCallsMethodsCorrectly()
    {
        $response = $this->factory->withArray(['foo' => 'bar']);
        self::assertSame('{"foo":"bar"}', $response->getContent());
    }
}
