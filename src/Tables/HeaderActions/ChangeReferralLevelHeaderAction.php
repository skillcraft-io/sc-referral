<?php

namespace Skillcraft\Referral\Tables\HeaderActions;

use Botble\Table\HeaderActions\HeaderAction;

class ChangeReferralLevelHeaderAction extends HeaderAction
{
    public static function make(string $name = 'referral_level'): static
    {
        return parent::make($name)
            ->icon('ti ti-user-down')
            ->label(trans('Change Level'))
            ->color('primary');
    }

    public function toArray(): array
    {
        return [
            'className' => sprintf('dropdown-toggle'),
            'text' => view('plugins/sc-referral::member.table.actions.header-action', ['action' => $this])->render(),
        ];
    }

    public function getLevelUrl(string $route, array $parameters = [], bool $absolute = true): string
    {
        return route($route, $parameters, $absolute);
    }
}
