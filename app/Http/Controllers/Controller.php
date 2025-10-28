<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @method $this middleware(string|array $middleware, array $options = [])
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
