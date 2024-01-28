<?php

namespace Skillcraft\Referral\Hooks;

use Botble\Base\Facades\Assets;
use Botble\Dashboard\Supports\DashboardWidgetInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardWidgets
{
    public static function registerLatestReferralWidget(array $widgets, Collection $widgetSettings): array
    {
        if (!Auth::guard()->user()->hasPermission('referral.index')) {
            return $widgets;
        }

        Assets::addScriptsDirectly(['/vendor/core/plugins/sc-referral/js/referrals.js']);

        return (new DashboardWidgetInstance())
            ->setPermission('referral.index')
            ->setKey('widget_latest_referrals')
            ->setTitle(trans('plugins/sc-referral::referral.widget_latest_referrals'))
            ->setIcon('ti ti-sitemap')
            ->setColor('blue')
            ->setRoute(route('referral.widget.referral-list'))
            ->setBodyClass('')
            ->setColumn('col-md-6 col-sm-6')
            ->init($widgets, $widgetSettings);
    }
}
