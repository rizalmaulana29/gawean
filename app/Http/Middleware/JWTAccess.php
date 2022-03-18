<?php

namespace App\Http\Middleware;

use App\Helpers\JWT;
use Closure;
use Exception;

class JWTAccess
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
        if (!$request->header('access-token')) {
            return response()->json(['status' => 'denied'], 400);
        }

        try {
            $credentials = JWT::Decode($request->header('access-token'));
        } catch(Exception $e) {
            return response()->json(['status' => 'denied', 'message' => 'Unauthorized Access'], 401);
        }

        if (!$credentials->sub) {
            return response()->json(['status' => 'denied', 'message' => 'Unauthorized Access'], 401);
        }

        // make it accessible on controller
        $request->auth = $credentials->sub;

        $response =  $next($request);

        // give next token with new expiry time.
        $response->header('next', JWT::Sign($credentials->sub !== '9999999999' ? $credentials->sub : '99999999'));

        return $response;
    }
}
