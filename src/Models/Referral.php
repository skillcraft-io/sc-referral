<?php

namespace Skillcraft\Referral\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Skillcraft\Core\Models\CoreModel;

/**
 * @method static \Botble\Base\Models\BaseQueryBuilder<static> query()
 */
class Referral extends CoreModel
{
    use SoftDeletes;

    protected $table = 'sc_referrals';

    protected $fillable = [
        'referral_type',
        'referral_id',
        'sponsor_type',
        'sponsor_id',
    ];

    public function sponsor(): MorphTo
    {
        return $this->morphTo();
    }

    public function referral(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeIsSponsor(Builder $query, Model $sponsor): void
    {
        $query->where(function ($query) use ($sponsor) {
            $query->where('sponsor_id', $sponsor->getKey())
                ->where('sponsor_type', $sponsor->getMorphClass());
        });
    }

    public function scopeIsReferral(Builder $query, Model $referral): void
    {
        $query->where(function ($query) use ($referral) {
            $query->where('referral_id', $referral->getKey())
                ->where('referral_type', $referral->getMorphClass());
        });
    }
}
