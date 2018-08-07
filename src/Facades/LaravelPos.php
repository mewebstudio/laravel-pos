<?php

namespace Mews\LaravelPos\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class LaravelPos
 * @package Mews\LaravelPos\Facades
 */
class LaravelPos extends Facade {

    protected static function getFacadeAccessor() { return 'laravelpos'; }

}
