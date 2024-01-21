<?php

namespace Skillcraft\Referral\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralMiddleware
{
    public function handle(Request $request, Closure $next, string $guard = 'account')
    {
        if (!Auth::guard($guard)->check()) {
            do_action(ACTION_HOOK_REFERRAL_MIDDLEWARE_RUN, $request);
        }
        
        return $next($request);
    }
}
