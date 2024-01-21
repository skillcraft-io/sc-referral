<?php

use Botble\Base\Facades\BaseHelper;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Skillcraft\Referral\Http\Controllers', 'middleware' => ['web', 'core']], function () {

    Route::group(['prefix' => BaseHelper::getAdminPrefix(), 'middleware' => 'auth'], function () {

        Route::group(['prefix' => 'referrals', 'as' => 'referral.'], function () {
            Route::resource('', 'ReferralController')->parameters(['' => 'referral'])->only(['index', 'destroy']);

            Route::get('widgets/referral-list', [
                'as' => 'widget.referral-list',
                'uses' => 'ReferralController@getWidgetLatestReferrals',
                'permission' => 'referral.index',
            ]);
        });
    });
});
