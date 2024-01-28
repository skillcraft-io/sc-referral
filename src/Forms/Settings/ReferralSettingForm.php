<?php

namespace Skillcraft\Referral\Forms\Settings;

use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Setting\Forms\SettingForm;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Skillcraft\Core\Forms\FieldOptions\NumberFieldOption;
use Skillcraft\Referral\Http\Requests\Settings\ReferralSettingRequest;
use Skillcraft\Referral\Services\ReferralService;

class ReferralSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/sc-referral::referral.settings.title'))
            ->setSectionDescription(trans('plugins/sc-referral::referral.settings.description'))
            ->setValidatorClass(ReferralSettingRequest::class)
            ->add(
                'sc_referral_expire_days',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/sc-referral::referral.settings.expire_days'))
                    ->value((new ReferralService())->getExpiryDays())
                    ->min(1)
                    ->step(1)
                    ->required()
                    ->toArray()
            )
            ->add(
                'sc_referral_alias_length',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/sc-referral::referral.settings.alias_length'))
                    ->value((new ReferralService())->getAliasLength())
                    ->min(1)
                    ->step(1)
                    ->max(50)
                    ->required()
                    ->toArray()
            )
            ->add(
                'sc_referral_ref_levels',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/sc-referral::referral.settings.ref_levels'))
                    ->value((new ReferralService())->getReferralLevels())
                    ->min(1)
                    ->step(1)
                    ->required()
                    ->toArray()
            )
            ->add(
                'sc_referral_enable_member_default',
                OnOffField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/sc-referral::referral.settings.enable_member_default'))
                    ->value((new ReferralService())->isMemberPluginEnabled())
                    ->required()
                    ->toArray()
            )
            ->add(
                'sc_referral_query_param',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/sc-referral::referral.settings.query_param'))
                    ->value((new ReferralService())->getQueryParam())
                    ->required()
                    ->toArray()
            );
    }
}
