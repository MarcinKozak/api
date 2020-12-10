<?php

namespace Dingo\Api\Contract\Debug;

use Illuminate\Http\Response;
use Throwable;

interface ExceptionHandler
{
    /**
     * Handle an exception.
     *
     * @param Throwable $exception
     *
     * @return Response
     */
    public function handle(Throwable $exception) : Response;
}
