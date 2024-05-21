<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Traits\ApiResponses;


class AdminMiddleware
{
    use ApiResponses;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $id = $request->user()->id;
        $user = User::find($id);
        if ($user) {
            if ($user->hasRole('Administrator')) //If user does //not have this permission
            {

            return $next($request);

            }else{
                return $this->errorResponse('Insufficient Permissions. You need administrator permission to access this resource.', 401);
            }
        }
    }
}
