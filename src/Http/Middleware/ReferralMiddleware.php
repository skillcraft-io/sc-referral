<?php

namespace Skillcraft\Referral\Http\Middleware;

use Botble\Base\Facades\BaseHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ReferralMiddleware
{
     public function handle(Request $request, Closure $next, string ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        $isLoggedIn = false;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $isLoggedIn = true;
            }
        }

        if (! $isLoggedIn) {
            try {
                do_action(ACTION_HOOK_REFERRAL_MIDDLEWARE_RUN, $request);
            } catch (Throwable $exception) {
                BaseHelper::logError($exception);
            }
        }

        return $next($request);
    }

}
