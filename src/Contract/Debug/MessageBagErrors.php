<?php

namespace Dingo\Api\Contract\Debug;

use Illuminate\Support\MessageBag;

interface MessageBagErrors
{
    /**
     * Get the errors message bag.
     *
     * @return MessageBag
     */
    public function getErrors() : MessageBag;

    /**
     * Determine if message bag has any errors.
     *
     * @return bool
     */
    public function hasErrors() : bool;
}
