<?php

namespace Dingo\Api\Tests\Routing\Adapter;

use Dingo\Api\Contract\Routing\Adapter;
use Dingo\Api\Routing\Adapter\Laravel;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;

class LaravelTest extends BaseAdapterTest
{
    public function getAdapterInstance() : Adapter
    {
        return new Laravel(new Router(new Dispatcher, $this->container));
    }

    public function getContainerInstance() : Container
    {
        return new Container;
    }
}
