<?php

namespace Skillcraft\Referral\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Skillcraft\Core\Models\CoreModel;
use Skillcraft\Core\Traits\FillableValidation;

/**
 * Class Referral
 *
 * @package Skillcraft\Referral
 * @property string $referral_type The type of the referral.
 * @property int $referral_id The id of the referral.
 * @property string $sponsor_type The type of the sponsor.
 * @property int $sponsor_id The id of the sponsor.
 * @method void isSponsor(Builder $query, Model $sponsor)
 * @method void isReferral(Builder $query, Model $referral)
 * @method void isSponsorOrReferral(Builder $query, Model $sponsorReferral)
 */
class Referral extends CoreModel
{
    use SoftDeletes;
    use FillableValidation;

    protected $table = 'sc_referrals';

    protected $fillable = [
        'referral_type' => 'required|string',
        'referral_id' => 'required|numeric|min:1',
        'sponsor_type' => 'required|string',
        'sponsor_id' => 'required|numeric|min:1',
    ];

    /**
     * Get the sponsor of the model.
     *
     * @return MorphTo
     */
    public function sponsor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Retrieve the referred model instance of the referral
     *
     * @return MorphTo
     */
    public function referral(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Add a scope to filter the query by a specific sponsor.
     *
     * @param Builder $query The query builder instance.
     * @param Model $sponsor The sponsor model to filter by.
     *
     * @return void
     */
    public function scopeIsSponsor(Builder $query, Model $sponsor): void
    {
        $query->where(function ($query) use ($sponsor) {
            $query->where('sponsor_id', $sponsor->getKey())
                ->where('sponsor_type', $sponsor->getMorphClass());
        });
    }

    /**
     * Scope a query to only include records that have a specific referral.
     *
     * @param Builder $query The query builder instance.
     * @param Model $referral The referral model instance.
     * @return void
     */
    public function scopeIsReferral(Builder $query, Model $referral): void
    {
        $query->where(function ($query) use ($referral) {
            $query->where('referral_id', $referral->getKey())
                ->where('referral_type', $referral->getMorphClass());
        });
    }

    public function scopeIsSponsorOrReferral(Builder $query, Model $model)
    {
        $query->where(function ($query) use ($model) {
            $query->IsSponsor($model)
                ->orWhere(function ($query) use ($model) {
                    $query->IsReferral($model);
                });
        });
    }
}
