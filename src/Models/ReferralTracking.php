<?php

namespace Skillcraft\Referral\Models;

use Botble\Base\Casts\SafeContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Skillcraft\Core\Models\CoreModel;
use Skillcraft\Core\Traits\FillableValidation;

/**
 * Class ReferralTracking
 *
 * @package Skillcraft\Referral
 * @property string $sponsor_type The type of associated model that initiated the referral.
 * @property int $sponsor_id The model's id that initiated the referral.
 * @property string $ip_address The IP address from where the referral request originated.
 * @property string $referrer The referrer url.
 * @property Carbon $expires_at The expiration date of the current row.
 * @method static Builder|ReferralTracking hasIpAddress(string $ip_address)
 * @method static Builder|ReferralTracking hasNotExpired()
 */
class ReferralTracking extends CoreModel
{
    use SoftDeletes;
    use FillableValidation;

    protected $table = 'sc_referral_trackings';

    protected $fillable = [
        'sponsor_type' => 'required|string',
        'sponsor_id' => 'required|numeric|min:1',
        'ip_address' => 'required|ip',
        'referrer' => 'nullable|string',
        'expires_at' => 'nullable|date_format:Y-m-d H:i:s'
    ];

    protected $casts = [
        'referrer' => SafeContent::class,
    ];

    protected array $dates = [
        'expires_at',
    ];

    /**
     * Get the sponsor model associated with the sponsor able model.
     *
     * @return MorphTo
     */
    public function sponsor():MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope the query to records that have the given IP address.
     *
     * @param Builder $query The query builder instance.
     * @param string $ip_address The IP address to filter records by.
     *
     * @return void
     */
    public function scopeHasIpAddress(Builder $query, string $ip_address):void
    {
        $query->where('ip_address', $ip_address);
    }

    /**
     * Scope to filter records that have not expired.
     *
     * @param Builder $query The query builder instance.
     * @return void
     */
    public function scopeHasNotExpired(Builder $query):void
    {
        $query->where('expires_at','>=', now());
    }
}
