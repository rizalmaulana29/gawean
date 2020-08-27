<?php

namespace App\Http\Middleware;

use Closure;

class CorsAllMiddleware
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

        // $http_origin = (isset($_SERVER['HTTP_ORIGIN']))?$_SERVER['HTTP_ORIGIN']:"";

        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'POST, GET',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
        ];

        // if ($http_origin == "https://order.rumahaqiqah.co.id/")
        //     { $headers["Access-Control-Allow-Origin"] = $http_origin;}
        // else
        //     {$headers["Access-Control-Allow-Origin"] = "https://dev.rumahaqiqah.co.id";}

        if ($request->isMethod('OPTIONS')) {
            return response()->json('{"method":"OPTIONS"}', 200, $headers);
        }

        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
