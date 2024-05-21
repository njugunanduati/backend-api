<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Traits\ApiResponses;

class SuperMiddleware
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
        // $user->getPermissionsViaRoles();
        if ($user) {
            if ($user->hasRole('Super Administrator')) //If user does //not have this permission
            {

            return $next($request);

            }else{
                return $this->errorResponse('Insufficient Permissions. You need super administrator permission to access this resource.', 401);
            }
        }


    }
}
