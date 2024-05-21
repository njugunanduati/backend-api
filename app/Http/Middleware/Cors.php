<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $allowedOrigins = [env('FRONTEND_URL'), env('STUDENT_URL'), 'http://localhost:5555'];
        $origin = parse_url($request->headers->get('origin'),  PHP_URL_HOST);

        if (in_array($origin, $allowedOrigins, false)) {

            return $next($request)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Headers', '*')
                ->header('Access-Control-Allow-Credentials', ' true');
        }
        // $ref https://maayansavir.medium.com/laravel-check-request-origin-domain-d825fc05dc1c
     
        return $next($request);
    }
}
