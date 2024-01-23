<?php

namespace Skillcraft\Referral\Providers;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Botble\Base\Facades\Assets;
use Illuminate\Support\Collection;
use Botble\Table\EloquentDataTable;
use Illuminate\Support\Facades\Auth;
use Botble\Table\CollectionDataTable;
use Illuminate\Database\Eloquent\Model;
use Botble\Base\Supports\ServiceProvider;
use Skillcraft\Referral\Services\ReferralService;
use Skillcraft\Referral\Supports\ReferralHookManager;
use Botble\Dashboard\Events\RenderingDashboardWidgets;
use Botble\Dashboard\Supports\DashboardWidgetInstance;
use Botble\Support\Http\Requests\Request as BaseRequest;
use Skillcraft\Membership\Supports\MembershipModuleHookManager;
use Skillcraft\Referral\Supports\Membership\ReferralLimitModule;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        (new ReferralHookManager())->load();

        add_filter('core_request_rules', function (array $rules, BaseRequest $request) {

            $id = 0;
            if (sizeOf($request->route()->parameters())) {
                $name = $request->route()->parameterNames()[0];
                $id = $request->route()->parameter($name)->getKey();
            }
            return (new ReferralService)->addRulesToSupportedForms($rules, $request, $id);
        }, 100, 2);

        add_filter('core_request_attributes', function (array $rules, BaseRequest $request) {
            return (new ReferralService)->aliasRuleAttributes($rules, $request);
        }, 100, 2);

        add_filter(BASE_FILTER_GET_LIST_DATA, function (EloquentDataTable|CollectionDataTable $data, Model|string|null $model) {
            return (new ReferralService)->addAliasColumnToTable($data, $model);
        }, 247, 2);

        add_filter(BASE_FILTER_TABLE_HEADINGS, function (array $headings, Model|string|null $model) {
            return (new ReferralService)->addAliasHeaderToTable($headings, $model);
        }, 1134, 2);

        add_action(ACTION_HOOK_REFERRAL_MIDDLEWARE_RUN, function (Request $request) {
            (new ReferralService)->processSponsorTracking($request);
        }, 1, 1);

        add_action(
            BASE_ACTION_META_BOXES,
            function ($priority, $data) {
                (new ReferralService)->addAliasFormMetabox($priority, $data);
                (new ReferralService)->addSponsorFormMetabox($priority, $data);
            },
            900,
            2
        );

        add_action(
            BASE_ACTION_AFTER_CREATE_CONTENT,
            function ($screen, $request, $data) {
                (new ReferralService)->saveMetaData($screen, $request, $data);
            },
            1,
            3
        );

        add_action(
            BASE_ACTION_AFTER_UPDATE_CONTENT,
            function ($screen, $request, $data) {
                (new ReferralService)->saveMetaData($screen, $request, $data);
            },
            1,
            3
        );

        add_action(
            BASE_ACTION_AFTER_DELETE_CONTENT,
            function ($screen, $request, $data) {
                (new ReferralService)->saveMetaData($screen, $request, $data);
            },
            1,
            3
        );

        $this->app['events']->listen(RenderingDashboardWidgets::class, function () {
            add_filter(DASHBOARD_FILTER_ADMIN_LIST, [$this, 'registerDashboardWidgets'], 21, 2);
        });

        if (defined('SKILLCRAFT_MEMBERSHIP_MODULE_SCREEN_NAME')) {
            MembershipModuleHookManager::registerModuleHooks(ReferralLimitModule::class);
        }
    }

    public function registerDashboardWidgets(array $widgets, Collection $widgetSettings): array
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
