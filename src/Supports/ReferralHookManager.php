<?php

namespace Skillcraft\Referral\Supports;

use Botble\Dashboard\Events\RenderingDashboardWidgets;
use Botble\Support\Http\Requests\Request as BaseRequest;
use Botble\Table\CollectionDataTable;
use Botble\Table\EloquentDataTable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Botble\Base\Facades\MacroableModels;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\ValidationException;
use Skillcraft\Membership\Supports\MembershipModuleHookManager;
use Skillcraft\Referral\Actions\AddMetaBoxToHookAction;
use Skillcraft\Referral\Actions\AddToHookFormRulesAction;
use Skillcraft\Referral\Actions\AddToHookTableAction;
use Skillcraft\Referral\Hooks\DashboardWidgets;
use Skillcraft\Referral\Services\ReferralService;
use Skillcraft\Core\Abstracts\HookRegistrarAbstract;

class ReferralHookManager extends HookRegistrarAbstract
{
    /**
     * Get the screen name for the referral module.
     *
     * @return string The screen name for the referral module.
     */
    public static function getScreenName(): string
    {
        return REFERRAL_MODULE_SCREEN_NAME;
    }

    /**
     * Get the name of the screen forms
     *
     * @return string The name of the screen forms
     */
    public static function getScreenFormsName(): string
    {
        return REFERRAL_MODULE_FORM_SCREEN_NAME;
    }

    /**
     * Get Module Name
     *
     * This method returns the name of the module as a string.
     *
     * @return string The name of the module.
     */
    public static function getModuleName(): string
    {
        return 'sc-referral';
    }

    /**
     * Get the supported key.
     *
     * @return string The supported key.
     */
    public static function getSupportedKey():string
    {
        return 'supported';
    }

    /**
     * Get the key for supported forms.
     *
     * @return string The key for supported forms.
     */
    public static function getSupportedFormsKey():string
    {
        return 'supported_forms';
    }

    /**
     * Get the supported forms configuration path.
     *
     * @return string The configuration path for the supported forms.
     */
    public static function getSupportedFormsConfigPath():string
    {
        return static::getConfigPath().'.'.static::getSupportedFormsKey();
    }

    /**
     * Get the supported form hooks.
     *
     * This method returns an array of supported form hooks by applying the filters
     * defined in the `getScreenFormsName` method and retrieving the configuration
     * from the `getSupportedFormsConfigPath`.
     *
     * @return mixed The supported form hooks.
     */
    public static function getSupportedFormHooks(): mixed
    {
        return apply_filters(
            static::getScreenFormsName(),
            config(static::getSupportedFormsConfigPath(), [])
        );
    }

    /**
     * Register Form Hooks
     *
     * @param array|string $model The model or models to be registered
     * @param string $name The name of the form hook
     * @return void
     */
    public static function registerFormHooks(array|string $model, string $name): void
    {
        config([
            static::getSupportedFormsConfigPath() => array_merge(
                static::getSupportedFormHooks(),
                [$model => $name]
            ),
        ]);
    }

    /**
     * Check if a form is supported
     *
     * @param Request|string $form The form to check, either as a Request object or a string class name
     *
     * @return bool True if the form is supported, false otherwise
     */
    public static function isSupportedForm(Request|string $form): bool
    {
        if (is_object($form)) {
            $form = get_class($form);
        }

        return array_key_exists($form, static::getSupportedFormHooks());
    }

    /**
     * Add Macro Hooks to supported models
     *
     * @return void
     */
    public static function addMacroHooks():void
    {
        foreach (self::getSupportedHooks() as $model => $name) {
            MacroableModels::addMacro($model, 'getAlias', function () {
                /**
                 * @var Model $this
                 * return Model
                 */
                return (new ReferralService)->getAlias($this);
            });

            MacroableModels::addMacro($model, 'updateAlias', function (string $alias) {
                /**
                 * @var Model $this
                 * @param string $alias
                 * return Model
                 */
                return (new ReferralService)->updateAlias($this, $alias);
            });

            MacroableModels::addMacro($model, 'updateSponsor', function (string|int $sponsor_id) {
                /**
                 * @var Model $this
                 * @param int $sponsor_id
                 * return Model
                 */
                return (new ReferralService)->updateSponsor($this, $sponsor_id);
            });

            MacroableModels::addMacro($model, 'unHookSponsor', function () {
                /**
                 * @var Model $this
                 * return void
                 */
                (new ReferralService)->unHookSponsor($this);
            });

            MacroableModels::addMacro($model, 'unHookReferral', function (Model $referral) {
                /**
                 * @var Model $this
                 * @param Model $referral
                 * return void
                 */
                (new ReferralService)->unHookSponsor($this, $referral);
            });

            MacroableModels::addMacro($model, 'unHookAllReferrals', function () {
                /**
                 * @var Model $this
                 * return void
                 */
                (new ReferralService)->unHookAllReferrals($this);
            });

            MacroableModels::addMacro($model, 'addMissingAlias', function () {
                /**
                 * @var Model $this
                 * return void
                 */
                (new ReferralService)->addMissingAlias($this);
            });

            MacroableModels::addMacro($model, 'getSponsor', function () {
                /**
                 * @var Model $this
                 * return ?Model
                 */
                return (new ReferralService)->getSponsor($this);
            });

            MacroableModels::addMacro($model, 'getReferrals', function () {
                /**
                 * @var Model $this
                 * return Collection
                 */
                return (new ReferralService)->getReferrals($this);
            });

            MacroableModels::addMacro($model, 'getReferralLink', function () {
                /**
                 * @var Model $this
                 * return string
                 */
                return (new ReferralService)->getReferralLink($this);
            });

            MacroableModels::addMacro($model, 'getSubLevelReferrals', function (int $level = 1) {
                /**
                 * @var Model $this
                 * return Collection
                 */
                return (new ReferralService)->getSubLevelReferrals($this, $level);
            });
        }
    }


    /**
     * Add Filter Hooks
     *
     * @return void
     * @throws ValidationException
     */
    protected static function addFilterHooks():void
    {
        add_filter('core_request_rules', function (array $rules, BaseRequest $request) {
            return (new AddToHookFormRulesAction)->addRulesToSupportedForms($rules, $request);
        }, 100, 2);

        add_filter('core_request_attributes', function (array $rules, BaseRequest $request) {
            return (new AddToHookFormRulesAction())->aliasRuleAttributes($rules, $request);
        }, 100, 2);

        add_filter(BASE_FILTER_GET_LIST_DATA, function (EloquentDataTable|CollectionDataTable $data, Model|string|null $model) {
            return (new AddToHookTableAction)->addAliasColumnToTable($data, $model);
        }, 247, 2);

        add_filter(BASE_FILTER_TABLE_HEADINGS, function (array $headings, Model|string|null $model) {
            return (new AddToHookTableAction())->addAliasHeaderToTable($headings, $model);
        }, 1134, 2);

        app()['events']->listen(RenderingDashboardWidgets::class, function () {
            add_filter(DASHBOARD_FILTER_ADMIN_LIST, [DashboardWidgets::class, 'registerLatestReferralWidget'], 21, 2);
        });

        add_filter(THEME_FRONT_FOOTER, function (string|null $html) {
            if(is_plugin_active('member')
                && (new ReferralService())->isMemberPluginEnabled()
                && (new ReferralService())->getReferralLevels() > 1
            ) {
                $html = $html.view('plugins/sc-referral::member.table.actions.header-action-script')->render();
            }
            return $html;
        }, 3000, 1);
    }


    /**
     * Add Action Hooks
     *
     * @return void
     * @throws ValidationException
     */
    protected static function addActionHooks():void
    {
        add_action(ACTION_HOOK_REFERRAL_MIDDLEWARE_RUN, function (\Illuminate\Http\Request $request) {
            (new ReferralService)->processSponsorTracking($request);
        }, 1, 1);

        add_action(
            BASE_ACTION_META_BOXES,
            function ($priority, $data) {
                (new AddMetaBoxToHookAction)->addAliasFormMetabox($priority, $data);
                (new AddMetaBoxToHookAction)->addSponsorFormMetabox($priority, $data);
            },
            900,
            2
        );

        add_action(
            BASE_ACTION_AFTER_CREATE_CONTENT,
            function ($screen, $request, $data) {
                (new AddMetaBoxToHookAction())->saveMetaData($screen, $request, $data);
            },
            1,
            3
        );

        add_action(
            BASE_ACTION_AFTER_UPDATE_CONTENT,
            function ($screen, $request, $data) {
                (new AddMetaBoxToHookAction)->saveMetaData($screen, $request, $data);
            },
            1,
            3
        );

        add_action(
            BASE_ACTION_AFTER_DELETE_CONTENT,
            function ($screen, $request, $data) {
                (new AddMetaBoxToHookAction)->saveMetaData($screen, $request, $data);
            },
            1,
            3
        );
    }

    /**
     * Register Referral With Other Plugins
     *
     * @return void
     * @throws Exception
     */
    protected static function addToModuleRegistrar():void
    {
        if (defined('SKILLCRAFT_MEMBERSHIP_MODULE_SCREEN_NAME')) {
            MembershipModuleHookManager::registerModuleHooks(ReferralLimitModule::class);
        }
    }
}
