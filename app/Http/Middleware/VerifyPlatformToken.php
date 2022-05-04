<?php

namespace App\Http\Middleware;

use App\Models\FrontendDomain;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\InteractsWithTime;

class VerifyPlatformToken
{
    use InteractsWithTime;

    /**
     * The hosts that should be excluded from token verification.
     *
     * @var array
     */
    protected $except = [
//        '127.0.0.1'
    ];

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new middleware instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        if (
            $this->runningUnitTests() ||
            $this->inExceptArray($request) ||
            $this->checkPlatform($request)
        ) {
            return $next($request);
        }

        throw new TokenMismatchException('Platform token is incorrect.');
    }

    /**
     * Determine if the application is running unit tests.
     *
     * @return bool
     */
    protected function runningUnitTests()
    {
        return $this->app->runningInConsole() && $this->app->runningUnitTests();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($request->getHost() == $except) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $request
     * @return bool
     * @throws TokenMismatchException
     */
    protected function checkPlatform($request)
    {
        $token = $this->getTokenFromRequest($request);

        return is_string($token) &&
            $this->checkToken($token, $request->getHost());
    }

    /**
     * @param $token
     * @param $host
     * @return bool
     * @throws TokenMismatchException
     */
    protected function checkToken($token, $host): bool
    {
        $platform = FrontendDomain::where('domain', $host)
            ->where('token', $token)
            ->first();

        if (is_null($platform)) {
            throw new TokenMismatchException('Platform token is incorrect.');
        };

        Container::getInstance()->bind('platform', function () use ($platform) {
            return $platform;
        });

        return (bool)$platform;
    }

    /**
     * Get the CSRF token from the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function getTokenFromRequest($request)
    {
        $token = $request->header('X-PLATFORM-TOKEN', null);

        return $token ?? null;
    }

}
