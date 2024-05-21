<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
            ? Limit::perMinute(500)->by($request->user()->id)
            : Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('api/v1/questions/get_by_module', function (Request $request) {
            return Limit::perMinute(500)->response(function () {
                $time = 120;
                $response_data = [
                    "message" => "Too Many request at this time. Please try again after ".$time." seconds",
                ];
                return response($response_data, 429);
            });
        });

        RateLimiter::for('api/v1/assessments/', function (Request $request) {
            return Limit::perMinute(500)->response(function () {
                $time = 120;
                $response_data = [
                    "message" => "Too Many request at this time. Please try again after ".$time." seconds",
                ];
                return response($response_data, 429);
            });
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->response(function () {
                $time = 120;
                $response_data = [
                    "message" => "Too Many request at this time. Please try again after ".$time." seconds",
                ];
                return response($response_data, 429);
            });
        });

        RateLimiter::for('gapi', function (Request $request) {
            return Limit::perMinute(500)->response(function () {
                $time = 120;
                $response_data = [
                    "message" => "Too Many request at this time. Please try again after ".$time." seconds",
                ];
                return response($response_data, 429);
            });
        });

    }
}
