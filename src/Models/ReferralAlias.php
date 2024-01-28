<?php

namespace Skillcraft\Referral\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseQueryBuilder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Skillcraft\Core\Models\CoreModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Skillcraft\Core\Traits\FillableValidation;

/**
 * Class ReferralAlias
 *
 * @package Skillcraft\Referral
 * @property int $id
 * @property int $user_id
 * @property string $user_type
 * @property string $alias
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Model $user
 * @method static Builder|ReferralAlias hasUser(Model $user)
 * @method static Builder|ReferralAlias isNotUser(Model $user)
 * @method static Builder|ReferralAlias hasAlias(string $alias)
 * @method static Builder|ReferralAlias hasAliasId(int $alias_id)
 */
class ReferralAlias extends CoreModel
{
    use FillableValidation;

    protected $table = 'sc_referral_aliases';

    protected $fillable = [
        'user_id' => 'required|numeric|min:1',
        'user_type' => 'required|string',
        'alias' => 'required|string|max:50|unique:sc_referral_aliases,alias',
    ];

    protected $casts = [
        'alias' => SafeContent::class,
    ];

    /**
     * Get the associated user for this model.
     *
     * @return MorphTo
     */
    public function user():MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope method hasUser
     *
     * @param Builder $query The query builder instance
     * @param Model $user The user model to compare with
     * @return void
     */
    public function scopeHasUser(Builder $query, Model $user):void
    {
        $query->where(function ($query) use ($user) {
            $query->where('user_id', $user->getKey())
                ->where('user_type', $user->getMorphClass());
        });
    }

    /**
     * Scope method isNotUser.
     *
     * @param Builder $query The query builder instance.
     * @param Model $user The user model instance to compare against.
     *
     * @return void
     */
    public function scopeIsNotUser(Builder $query, Model $user):void
    {
        $query->where(function ($query) use ($user) {
            $query->where('user_id', '!=', $user->getKey())
                ->where('user_type', $user->getMorphClass());
        });
    }

    /**
     * Scope method hasAlias.
     *
     * @param Builder $query The query builder object.
     * @param string $alias The alias to filter by.
     * @return void
     */
    public function scopeHasAlias(Builder $query, string $alias):void
    {
        $query->where('alias', $alias);
    }

    /**
     * Scope method hasAliasId.
     *
     * @param Builder $query The query builder instance.
     * @param int $alias_id The alias id to filter by.
     * @return void
     */
    public function scopeHasAliasId(Builder $query, int $alias_id):void
    {
        $query->where('id', $alias_id);
    }

    /**
     * Scope to filter query by any alias IDs.
     *
     * @param Builder $query The query builder instance
     * @param Collection $alias_ids The array of alias IDs to filter by
     *
     * @return void
     */
    public function scopeHasAnyAliasId(Builder $query, Collection $alias_ids):void
    {
        $query->whereIn('id', $alias_ids->toArray());
    }
}
