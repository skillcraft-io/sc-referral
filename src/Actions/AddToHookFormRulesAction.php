<?php

namespace Skillcraft\Referral\Actions;

use Botble\Support\Http\Requests\Request as BaseRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Skillcraft\Referral\Models\ReferralAlias;
use Skillcraft\Referral\Supports\ReferralHookManager;

class AddToHookFormRulesAction
{
    public function aliasRules(int $id): array
    {
        return [
            'referral_alias_wrap' => [
                Rule::unique((new ReferralAlias())->getTable(), 'alias')->ignore($id),
                'required',
                'string',
                'max:50'
            ]
        ];
    }

    public function aliasRuleAttributes(): array
    {
        return [
            'referral_alias_wrap' => 'alias',
        ];
    }

    public function addRulesToSupportedForms(array $rules, BaseRequest $formRequest):array
    {

        $id = $this->resolveRouteResourceKey($formRequest);

        if (ReferralHookManager::isSupportedForm($formRequest)) {
            $rules = array_merge($rules, $this->aliasRules($id));
        }

        return $rules;
    }

    /**
     * @param BaseRequest $formRequest
     * @return int
     */
    protected function resolveRouteResourceKey(BaseRequest $formRequest): int
    {
        $id = 0;
        if (sizeOf($formRequest->route()->parameters())) {
            $name = $formRequest->route()->parameterNames()[0];
            $params = $formRequest->route()->parameters();

            $value = Arr::get($params, $name);
            if ($value instanceof Model) {
                $id = $value->getKey();
            } else {
                $id = (int)$value;
            }
        }

        return $id;
    }
}
