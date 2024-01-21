<?php

namespace Skillcraft\Referral\Models;

use Botble\Base\Casts\SafeContent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Skillcraft\Core\Models\CoreModel;

/**
 * @method static \Botble\Base\Models\BaseQueryBuilder<static> query()
 */
class ReferralTracking extends CoreModel
{
    use SoftDeletes;
    
    protected $table = 'sc_referral_trackings';

    protected $fillable = [
        'sponsor_type',
        'sponsor_id',
        'ip_address',
        'referrer',
        'expires_at',
    ];

    protected $casts = [
        'referrer' => SafeContent::class,
    ];

    protected $dates = [
        'expires_at',
    ];

    public function sponsor():MorphTo
    {
        return $this->morphTo();
    }
}
