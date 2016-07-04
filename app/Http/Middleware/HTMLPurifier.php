<?php

namespace App\Http\Middleware;

use Closure;

class HTMLPurifier
{
    /**
     * Handle an incoming request.
     * HTML Purifier filters through the input data and remove white spaces
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        foreach($request->all() as $key=>$val){
            $request[$key] = clean(trim($val));
        }
        return $next($request);
    }
}
