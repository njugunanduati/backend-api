<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Traits\ApiResponses;


class FreelancerMiddleware
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
            if ($user->hasRole('Freelancer')) //If user does //not have this permission
            {

            return $next($request);

            }else{
                return $this->errorResponse('Insufficient Permissions. You need freelancer permission to access this resource.', 401);
            }
        }
    }
}
