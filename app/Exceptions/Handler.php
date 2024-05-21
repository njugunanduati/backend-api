<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Integration;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            Integration::captureUnhandledException($e);
        });
    }
    /**
    * Report or log an exception for CloudWatch.
    *
    * @param  Throwable  $e
    * @return void
    *
    * @throws Throwable
    */
   public function report(Throwable $e)
   {
       $exceptionExcluse = [
           RouteNotFoundException::class,
           NotFoundHttpException::class,
           AuthorizationException::class,
           ValidationException::class,
       ];

    //    if (!in_array(get_class($e), $exceptionExcluse)) {
           $this->logPrettyError($e);
    //    }

       parent::report($e);
   }


   private function logPrettyError(Throwable $e)
   {
       $request = request();

       $log = [
           'access' => [
               'request' => $request->all(),
               'method' => $request->method(),
               'path' => $request->path(),
           ],
           'error' => [
               'class' => get_class($e),
               'code' => $e->getCode(),
               'message' => $e->getMessage(),
               'file' => $e->getFile(),
               'line' => $e->getLine(),
           ],
       ];
        /**
         * Get logger
         */
        if (!function_exists('getLogger')) {
            function getLogger()
            {
                return Log::channel(env('LOG_CHANNEL', 'daily'));
            }
        }

        /**
         * Log info
         */
        if (!function_exists('logInfo')) {
            function logInfo($info)
            {
                getLogger()->info($info);
            }
        }

        /**
         * Log error
         */
        if (!function_exists('logError')) {
            function logError($e)
            {
                $logger = getLogger();

                if ($e) {
                    $logger->error($e->getMessage() . ' on line ' . $e->getLine() . ' of file ' . $e->getFile());
                } else {
                    $logger->error($e);
                }
            }
        }
        getLogger()->error(json_encode($log));
   }

}
