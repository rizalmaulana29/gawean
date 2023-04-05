<?php

namespace App\Http\Middleware;

use Closure;
use Exception;

class SurveyAuth
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
        if (!$request->header('Authorization')) {
            return response()->json(
                ['status' => false, 'message' => 'Unauthorized Access!'],
                401
            );
        }

        if ($request->header('Authorization') != 'HelloWorldN3v3rD13sDud3s') {
            return response()->json(
                ['status' => false, 'message' => 'Unauthorized Access'],
                401
            );
        }

        $response =  $next($request);
        return $response;
    }
}
