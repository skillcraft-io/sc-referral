<?php

namespace Skillcraft\Referral\Models;

use Botble\Base\Casts\SafeContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Skillcraft\Core\Models\CoreModel;

/**
 * @method static \Botble\Base\Models\BaseQueryBuilder<static> query()
 */
class ReferralAlias extends CoreModel
{
    protected $table = 'sc_referral_aliases';

    protected $fillable = [
        'user_id',
        'user_type',
        'alias',
    ];

    protected $casts = [
        'alias' => SafeContent::class,
    ];

    public function scopeHasUser(Builder $query, Model $user): void
    {
        $query->where(function ($query) use ($user) {
            $query->where('user_id', $user->getKey())
                ->where('user_type', $user->getMorphClass());
        });
    }

    public function scopeIsNotUser(Builder $query, Model $user): void
    {
        $query->where(function ($query) use ($user) {
            $query->where('user_id', '!=', $user->getKey())
                ->where('user_type', $user->getMorphClass());
        });
    }
}
