<?php

namespace App\Http\Middleware;

use Auth;
// use Illuminate\Support\Facades\Auth;
use Hash;
use Closure;
use DateTime;
use App\Models\User;
use ThrottleRequests;
use League\OAuth2\Server;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;



class AuthAnyUser
{
    use ApiResponses;

    protected $server;
    protected $tokens;
    //protected $user;

    public function __construct(ResourceServer $server, TokenRepository $tokens)
    {

        $this->server = $server;
        $this->tokens = $tokens;
        //$this->user = $user;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        // try {
        // First, we will convert the Symfony request to a PSR-7 implementation which will
        // be compatible with the base OAuth2 library. The Symfony bridge can perform a
        // conversion for us to a Zend Diactoros implementation of the PSR-7 request.
        // $psr = (new DiactorosFactory)->createRequest($request);

        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->errorResponse('The resource owner or authorization server denied the request.', 401);
            }
        } catch (TokenExpiredException $e) {

            return $this->errorResponse('Your token expired. Please Log in again.', 401);
        } catch (TokenInvalidException $e) {

            return $this->errorResponse('Your token is invalid. Please try again.', 401);
        } catch (JWTException $e) {

            return $this->errorResponse('Token not Found.', 401);
        }

        // the token is valid allow request
        return $next($request);
    }
}
