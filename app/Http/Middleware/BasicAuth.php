<?php
namespace App\Http\Middleware;
use Closure;
use App\Logging\AppLogger;

class BasicAuth
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

        $credentials = array('email' => $request->getUser(), 'password'=> $request->getPassword());

        if (!auth()->attempt($credentials)) {
            $logger = new AppLogger();
            $logger->logRequest('error', 'Internal API access FAIL. Invalid Credentials. Please try again');
            header('HTTP/1.1 401 Authorization is Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            exit;
        }
        return $next($request);
    }
}