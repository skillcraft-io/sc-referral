<?php

namespace Skillcraft\Referral\Tables\Member;

use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Skillcraft\Referral\Models\Referral;
use Skillcraft\Referral\Services\ReferralService;
use Skillcraft\Referral\Tables\HeaderActions\ChangeReferralLevelHeaderAction;
use Skillcraft\Referral\Tables\Traits\ForMember;

class MemberReferralTable extends TableAbstract
{
    use ForMember;

    public function setup(): void
    {
        $this
            ->model(Referral::class)
            ->addColumns([
                Column::make('referral_id'),
                CreatedAtColumn::make(),
            ])
            ->queryUsing(function (EloquentBuilder $query) {
                return $query
                    ->select([
                        'id',
                        'referral_type',
                        'referral_id',
                        'sponsor_type',
                        'sponsor_id',
                        'created_at',
                    ])->whereIn('id', auth('member')->user()->getSubLevelReferrals(request()->get('level', 1))->pluck('id')->toArray());
            });

        if((new ReferralService)->getReferralLevels() > 1){
            $this->addHeaderAction(ChangeReferralLevelHeaderAction::make());
        }
    }
}
