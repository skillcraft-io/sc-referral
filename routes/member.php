<?php

use Botble\Theme\Facades\Theme;
use Botble\Member\Models\Member;
use Illuminate\Support\Facades\Route;
use Skillcraft\Referral\Supports\ReferralHookManager;

if (defined('THEME_MODULE_SCREEN_NAME') && is_plugin_active('member') && ReferralHookManager::isSupported(Member::class)) {
    Theme::registerRoutes(function () {
        Route::group([
            'namespace' => 'Skillcraft\Referral\Http\Controllers\Member',
            'middleware' => ['web', 'core', 'member'],
            'as' => 'public.member.',
        ], function () {
            Route::group([
                'prefix' => 'account/referrals',
                'as' => 'referrals.',
            ], function () {
                Route::resource('', 'MemberReferralController')->parameters(['' => 'referral'])->only(['index']);
            });
        });
    });
}
