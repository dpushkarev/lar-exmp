<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     *  Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param Throwable $exception
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $exception)
    {
        $route = $request->route();
        if ($route) {
            if (Str::startsWith($request->route()->getPrefix(), 'api')) {
                return $this->getJsonResponse($exception);
            }
        }

        return parent::render($request, $exception);
    }

    /**
     * @param Throwable $exception
     * @return \Illuminate\Http\JsonResponse
     */
    private function getJsonResponse(Throwable $exception)
    {
        if ($exception instanceof ApiException) {
            return response()->json($exception->getResponseArray(), 422);
        }

        if ($exception instanceof ThrottleRequestsException) {
            return response()->json(['message' => $exception->getMessage()], 429)->withHeaders($exception->getHeaders());
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        if ($exception instanceof NotFoundHttpException) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        if (config('app.debug')) {
            return response()->json([
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            ], 500);
        }

        return response()->json([
            'message' => 'Internal Server Error'
        ], 500);
    }
}
