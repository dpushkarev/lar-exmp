<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{

    /**
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$params
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$params)
    {
        $origin = $request->header('Origin');

        $response = $next($request);

        if ($origin) {
            $response
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Headers', 'Content-Type, x-Requested-With, Authorization')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Methods', $params[0] ?? 'GET, POST, PUT');
        }

        return $response;
    }
}
