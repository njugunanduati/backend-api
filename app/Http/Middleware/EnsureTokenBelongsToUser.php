<?php

namespace App\Http\Middleware;

use Closure;
use Cache;
use App\Models\User;
use App\Models\PersonalAccessToken;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class EnsureTokenBelongsToUser
{
    use ApiResponses;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $personal_access_token = PersonalAccessToken::findToken($request->bearerToken());
        $abilities = $personal_access_token->abilities[0];
        $abilities = explode(':', $abilities);
        if (intval($abilities['1'], 10) === 10){
            return $this->errorResponse('You cannot perform the request', 400);
        }
        return $next($request);
    }
}
