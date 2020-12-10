<?php

namespace Dingo\Api\Tests\Http\Validation;

use Dingo\Api\Http\Validation\Prefix;
use Dingo\Api\Tests\BaseTestCase;
use Illuminate\Http\Request;

class PrefixTest extends BaseTestCase
{
    public function testValidationFailsWithInvalidOrNullPrefix()
    {
        $validator = new Prefix('foo');
        self::assertFalse($validator->validate(Request::create('bar', 'GET')), 'Validation passed when it should have failed with an invalid prefix.');

        $validator = new Prefix(null);
        self::assertFalse($validator->validate(Request::create('foo', 'GET')), 'Validation passed when it should have failed with a null prefix.');
    }

    public function testValidationPasses()
    {
        $validator = new Prefix('foo');
        self::assertTrue($validator->validate(Request::create('foo', 'GET')), 'Validation failed when it should have passed with a valid prefix.');
        self::assertTrue($validator->validate(Request::create('foo/bar', 'GET')), 'Validation failed when it should have passed with a valid prefix.');
    }

    public function testValidationPassesWithHyphenatedPrefix()
    {
        $validator = new Prefix('web-api');
        self::assertTrue($validator->validate(Request::create('web-api', 'GET')), 'Validation failed when it should have passed with a valid prefix.');
        self::assertTrue($validator->validate(Request::create('web-api/bar', 'GET')), 'Validation failed when it should have passed with a valid prefix.');
    }
}
