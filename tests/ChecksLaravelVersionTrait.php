<?php

namespace Dingo\Api\Tests;

use Dingo\Api\Tests\Stubs\ApplicationStub;
use Illuminate\Container\Container;

trait ChecksLaravelVersionTrait
{
    public $installed_file_path = __DIR__.'/../vendor/composer/installed.json';
    public $current_release = '7.0';

    /**
     * @return string
     */
    private function getFrameworkVersion() : string
    {
        $contents = file_get_contents($this->installed_file_path);
        $parsed_data = json_decode($contents, true);
        $just_laravel = array_filter($parsed_data, static function ($val) {
            $name = $val['name'] ?? null;

            return ('laravel/framework' === $name || 'laravel/lumen-framework' === $name);
        });

        $versions = array_map(static function ($val) {
            return $val['version'];
        }, array_values($just_laravel));

        return (string)($versions[0] ?? '');
    }

    /**
     * @return Container
     */
    private function getApplicationStub() : Container
    {
        $version = $this->getFrameworkVersion();

        if ('dev-master' === $version) {
            $version = $this->current_release;
        }

        // Remove the 'v' in for example 'v5.8.3'
        $version = str_replace('v', '', $version);

        return new ApplicationStub($version);
    }
}
