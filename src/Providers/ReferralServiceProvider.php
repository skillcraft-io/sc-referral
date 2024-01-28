<?php

namespace Skillcraft\Referral\Providers;

use Botble\Member\Http\Requests\MemberCreateRequest;
use Botble\Member\Models\Member;
use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Supports\ServiceProvider;
use Illuminate\Routing\Events\RouteMatched;
use Botble\Base\Facades\PanelSectionManager;
use Botble\Base\PanelSections\PanelSectionItem;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Member\Http\Requests\MemberEditRequest;
use Skillcraft\Core\PanelSections\CorePanelSection;
use Skillcraft\Referral\Services\ReferralService;
use Skillcraft\Referral\Supports\ReferralHookManager;
use Skillcraft\Referral\Http\Middleware\ReferralMiddleware;

class ReferralServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;
    public function register(): void
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

        $this->app->register(EventServiceProvider::class);

        $this
            ->setNamespace('plugins/sc-referral')
            ->loadHelpers()
            ->loadAndPublishConfigurations(['permissions', 'general'])
            ->loadMigrations()
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->loadRoutes();

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

                    PanelSectionItem::make('referral_settings')
                        ->setTitle(trans('plugins/sc-referral::referral.settings.title'))
                        ->withIcon('ti ti-settings-cog')
                        ->withDescription(trans('plugins/sc-referral::referral.settings.description'))
                        ->withPriority(-9980)
                        ->withRoute('referral.settings'),
                ]
            );
        });

        if (is_plugin_active('member')) {
           if ((new ReferralService())->isMemberPluginEnabled()) {
                ReferralHookManager::registerHooks(Member::class, 'member');
                ReferralHookManager::registerFormHooks(MemberCreateRequest::class, 'member_create');
                ReferralHookManager::registerFormHooks(MemberEditRequest::class, 'member_edit');
            }
       }

        $this->app->booted(function () {
            (new ReferralHookManager())->load();

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
