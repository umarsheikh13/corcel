<?php

namespace Ronin\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Call the method using call_user_func_array
     * @param  string $method    The method name
     * @param  array  $arguments The method arguments
     * @return mixed             The method
     */
    public function callMethod($method, $arguments)
    {
        return call_user_func_array([$this, $method], $arguments);
    }
}
