<?php

namespace Skillcraft\Referral\Supports;

use Botble\Base\Facades\MacroableModels;
use Botble\Support\Http\Requests\Request;
use Illuminate\Database\Eloquent\Model;
use Skillcraft\Core\Abstracts\HookRegistrarAbstract;
use Skillcraft\Referral\Services\ReferralService;

class ReferralHookManager extends HookRegistrarAbstract
{
    public static function getScreenName(): string
    {
        return REFERRAL_MODULE_SCREEN_NAME;
    }

    public static function getScreenFormsName(): string
    {
        return REFERRAL_MODULE_FORM_SCREEN_NAME;
    }

    public static function getModuleName(): string
    {
        return 'sc-referral';
    }

    public static function getSupportedKey(): string
    {
        return 'supported';
    }

    public static function getSupportedFormsKey(): string
    {
        return 'supported_forms';
    }

    public static function getSupportedFormsConfigPath(): string
    {
        return static::getConfigPath() . '.' . static::getSupportedFormsKey();
    }

    public static function getSupportedFormHooks(): mixed
    {
        return apply_filters(
            static::getScreenFormsName(),
            config(static::getSupportedFormsConfigPath(), [])
        );
    }

    public static function registerFormHooks(array|string $model, string $name): void
    {
        config([
            static::getSupportedFormsConfigPath() => array_merge(
                static::getSupportedFormHooks(),
                [$model => $name]
            ),
        ]);
    }

    public static function isSupportedForm(Request|string $form): bool
    {
        if (is_object($form)) {
            $form = get_class($form);
        }

        return array_key_exists($form, static::getSupportedFormHooks());
    }

    public static function addMacroHooks(): void
    {
        foreach (self::getSupportedHooks() as $model => $name) {
            MacroableModels::addMacro($model, 'getAlias', function () {
                /**
                 * @var Model $this
                 * return Model
                 */
                return (new ReferralService())->getAlias($this);
            });

            MacroableModels::addMacro($model, 'updateAlias', function (string $alias) {
                /**
                 * @var Model $this
                 * @param string $alias
                 * return Model
                 */
                return (new ReferralService())->updateAlias($this, $alias);
            });

            MacroableModels::addMacro($model, 'updateSponsor', function (string|int $sponsor_id) {
                /**
                 * @var Model $this
                 * @param int $sponsor_id
                 * return Model
                 */
                return (new ReferralService())->updateSponsor($this, $sponsor_id);
            });

            MacroableModels::addMacro($model, 'unHookSponsor', function () {
                /**
                 * @var Model $this
                 * return Model
                 */
                return (new ReferralService())->unHookSponsor($this);
            });

            MacroableModels::addMacro($model, 'unHookReferral', function (Model $referral) {
                /**
                 * @var Model $this
                 * @param Model $referral
                 * return Model
                 */
                return (new ReferralService())->unHookSponsor($this, $referral);
            });

            MacroableModels::addMacro($model, 'unHookAllReferrals', function () {
                /**
                 * @var Model $this
                 * return Model
                 */
                return (new ReferralService())->unHookAllReferrals($this);
            });

            MacroableModels::addMacro($model, 'addMissingAlias', function () {
                /**
                 * @var Model $this
                 * return void
                 */
                (new ReferralService())->addMissingAlias($this);
            });

            MacroableModels::addMacro($model, 'getSponsor', function () {
                /**
                 * @var Model $this
                 * return ?Model
                 */
                return (new ReferralService())->getSponsor($this);
            });

            MacroableModels::addMacro($model, 'getReferrals', function () {
                /**
                 * @var Model $this
                 * return Collection
                 */
                return (new ReferralService())->getReferrals($this);
            });

            MacroableModels::addMacro($model, 'getReferralLink', function () {
                /**
                 * @var Model $this
                 * return string
                 */
                return (new ReferralService())->getReferralLink($this);
            });
        }
    }
}
