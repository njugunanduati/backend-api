<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            $token = $user->currentAccessToken();
            // check token expiry
            if ($user->tokenExpired($token)) {
                // Token has expired, revoke it and return an error response
                $token->delete(); // Revoke the token
                return response()->json(['message' => 'Token has expired'], 401);
            }
        }

        return $next($request);
    }
}
