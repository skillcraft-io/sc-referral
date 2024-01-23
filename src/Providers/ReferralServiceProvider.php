<?php

namespace Skillcraft\Referral\Providers;

use Botble\Member\Models\Member;
use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Supports\ServiceProvider;
use Illuminate\Routing\Events\RouteMatched;
use Botble\Base\Facades\PanelSectionManager;
use Botble\Base\PanelSections\PanelSectionItem;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Member\Http\Requests\SettingRequest;
use Botble\Member\Http\Requests\MemberEditRequest;
use Skillcraft\Core\PanelSections\CorePanelSection;
use Skillcraft\Referral\Supports\ReferralHookManager;
use Skillcraft\Referral\Http\Middleware\ReferralMiddleware;

class ReferralServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;
    public function register()
    {
        $events = $this->app['events'];

        $events->listen(RouteMatched::class, function () {
            $router = $this->app['router'];
            $router->pushMiddlewareToGroup('web', ReferralMiddleware::class);
        });
    }

    public function boot(): void
    {
        if (!is_plugin_active('sc-core')) {
            return;
        }

        $this
            ->setNamespace('plugins/sc-referral')
            ->loadHelpers()
            ->loadAndPublishConfigurations(['permissions', 'general'])
            ->loadMigrations()
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->loadRoutes(['web']);

        PanelSectionManager::default()->beforeRendering(function () {
            PanelSectionManager::registerItem(
                CorePanelSection::class,
                fn () => [
                    PanelSectionItem::make('referrals')
                        ->setTitle(trans('plugins/sc-referral::referral.name'))
                        ->withIcon('ti ti-sitemap')
                        ->withDescription(trans('plugins/sc-referral::referral.description'))
                        ->withPriority(-9980)
                        ->withRoute('referral.index'),
                ]
            );
        });

        if (is_plugin_active('member')) {
            if (config('plugins.sc-referral.general.enable_member_default')) {
                ReferralHookManager::registerHooks(Member::class, 'member');
                ReferralHookManager::registerFormHooks(MemberEditRequest::class, 'member');
            }
        }

        $this->app->booted(function () {
            $this->app->register(HookServiceProvider::class);
            if (is_plugin_active('member')) {
                if (ReferralHookManager::isSupported(Member::class)) {
                    $this->loadRoutes(['member']);

                    DashboardMenu::for('member')->beforeRetrieving(function () {
                        DashboardMenu::make()
                            ->registerItem([
                                'id' => 'cms-member-referrals',
                                'priority' => 5,
                                'name' => 'plugins/sc-referral::referral.name',
                                'url' => fn () => route('public.member.referrals.index'),
                                'icon' => 'ti ti-sitemap',
                            ]);
                    });

                    DashboardMenu::default();
                }
            }
        });
    }
}
