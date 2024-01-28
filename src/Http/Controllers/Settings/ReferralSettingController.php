<?php

namespace Skillcraft\Referral\Http\Controllers\Settings;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Setting\Http\Controllers\SettingController;
use Skillcraft\Referral\Forms\Settings\ReferralSettingForm;
use Skillcraft\Referral\Http\Requests\Settings\ReferralSettingRequest;

class ReferralSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle(trans('plugins/sc-referral::referral.settings.title'));

        return ReferralSettingForm::create()->renderForm();
    }

    public function update(ReferralSettingRequest $request): BaseHttpResponse
    {
        return $this->performUpdate($request->validated());
    }
}
