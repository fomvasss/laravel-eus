<?php
/**
 * Created by PhpStorm.
 * User: fomvasss
 * Date: 12.12.18
 * Time: 22:13
 */

namespace Fomvasss\LaravelEloquentUniqueString;

use Fomvasss\LaravelMetaTags\EUSGenerator;
use Illuminate\Support\Facades\Facade as LFacade;

class Facade extends LFacade
{
    public static function getFacadeAccessor()
    {
        return EUSGenerator::class;
    }
}
