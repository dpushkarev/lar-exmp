<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;

class ApiLocale
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale = $request->get('apilang', $request->get('user_language_get_change'));
        if (!$locale || !Config::get('resources.locale.' . $locale)) {
            $locale = Config::get('app.locale');
        }
        App::setLocale($locale);

        return $next($request);
    }
}
