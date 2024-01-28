<?php

namespace Skillcraft\Referral\Http\Requests\Settings;

use Botble\Base\Rules\OnOffRule;
use Botble\Support\Http\Requests\Request;

class ReferralSettingRequest extends Request
{
    public function rules(): array
    {
        return [
            'sc_referral_expire_days' => ['required', 'numeric', 'min:1'],
            'sc_referral_alias_length' => ['required', 'numeric', 'min:1', 'max:50'],
            'sc_referral_ref_levels' => ['required', 'numeric', 'min:1'],
            'sc_referral_enable_member_default' => new OnOffRule(),
            'sc_referral_query_param' => ['required', 'string']
        ];
    }
}
