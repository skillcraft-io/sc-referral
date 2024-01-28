<?php

namespace Skillcraft\Referral\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Botble\Base\Facades\MetaBox;
use Botble\Table\Columns\Column;
use Botble\Base\Facades\BaseHelper;
use Botble\Table\EloquentDataTable;
use Illuminate\Support\Facades\Auth;
use Botble\Table\CollectionDataTable;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Skillcraft\Referral\Models\Referral;
use Botble\Base\Events\CreatedContentEvent;
use Illuminate\Database\Eloquent\Collection;
use Skillcraft\Referral\Models\ReferralAlias;
use Illuminate\Validation\ValidationException;
use Skillcraft\Referral\Models\ReferralTracking;
use Skillcraft\Referral\Supports\ReferralHookManager;
use Botble\Support\Http\Requests\Request as BaseRequest;
use Skillcraft\Referral\Supports\Membership\ReferralLimitModule;
use Skillcraft\Membership\Exceptions\MembershipValidationException;

class ReferralService
{
    /**
     * Retrieves the referral link for the given user.
     *
     * @param Model $user The user for whom the referral link is retrieved
     * @return string
     */
    public function getReferralLink(Model $user): string
    {
        return route('public.index', [
            $this->getQueryParam() => $user->getAlias()->alias,
        ]);
    }


    /**
     * Get the alias for the user model.
     *
     * @param Model $user The user model
     * @return Model
     */
    public function getAlias(Model $user): Model
    {
        //account for create screen
        if (!$user->exists) {
            return $user;
        }

        return (new ReferralAlias())->HasUser($user)->firstOr(function () use ($user) {
            return $this->createAlias($user);
        });
    }


    /**
     * Updates the alias of a user.
     *
     * @param Model $user The user model
     * @param string $alias The new alias
     * @return Model The updated user model
     */
    public function updateAlias(Model $user, string $alias): Model
    {
        $record = $this->getAlias($user);

        if ($record->alias === $alias) {
            return $record;
        }

        $record->alias = $alias;

        $record->save();

        return $record;
    }

    /**
     * Updates the sponsor of a user.
     *
     * @param Model $user The user model
     * @param string|int $alias_id The alias id of the sponsor
     * @return Model|null The updated user model
     */
    public function updateSponsor(Model $user, string|int $alias_id): ?Model
    {
        if ($alias_id === "-") {
            return $this->unHookSponsor($user);
        }


        $sponsor_alias = (new ReferralAlias())

            ->hasAliasId($alias_id)
            ->first();

        return $this->createSponsor($user, $sponsor_alias);
    }


    /**
     * Get the sponsor for the given referral.
     *
     * @param Model $referral The referral model
     * @return Model|null
     */
    public function getSponsor(Model $referral): ?Model
    {
        $sponsor_rec = (new Referral())->IsReferral($referral)->first();

        if ($sponsor_rec) {
            return $sponsor_rec->sponsor;
        }

        return null;
    }

    /**
     * Get referrals for a specific sponsor.
     *
     * @param Model $sponsor The sponsor model
     * @return Collection
     */
    public function getReferrals(Model $sponsor): Collection
    {
        return (new Referral())->IsSponsor($sponsor)->get();
    }

    /**
     * Add a missing alias for the user model.
     *
     * @param Model $user The user model
     * @return void
     */
    public function addMissingAlias(Model $user): void
    {
        if (!(new ReferralAlias())->HasUser($user)->exists()) {
            $alias = $this->createAlias($user);
            event(new CreatedContentEvent(REFERRAL_MODULE_SCREEN_NAME, request(), $alias));
        }
    }

    /**
     * Process the sponsor check for the referral model.
     *
     * @param Request $request The request object
     * @param Model $referral The referral model
     * @return void
     *
     * @throws Exception
     */
    public function processSponsorCheck(Request $request, Model $referral): void
    {
        $tracking_record = $this->getTrackingRecord($request);

        if ($tracking_record === null) {
            return;
        }

        $sponsor = $this->createSponsor(
            $referral,
            $this->getAlias($tracking_record->sponsor)
        );

        if ($sponsor !== null) {
            $tracking_record->delete();
            event(new CreatedContentEvent(REFERRAL_MODULE_SCREEN_NAME, $request, $sponsor));
        }
    }

    /**
     * Process sponsor tracking.
     *
     * @param Request $request The current request object
     * @return void
     */
    public function processSponsorTracking(Request $request): void
    {
        if (!is_in_admin()) {
            $hasTracking = (new ReferralTracking())

                ->hasIpAddress($request->getClientIp())
                ->hasNotExpired()
                ->exists();

            if (!$hasTracking) {
                $alias_record = (new ReferralAlias())

                    ->hasAlias($request->query($this->getQueryParam()))
                    ->first();

                if (!empty($alias_record)) {
                    $tracker = (new ReferralTracking())->create([
                        'sponsor_type' => $alias_record->user_type,
                        'sponsor_id' => $alias_record->user_id,
                        'ip_address' => $request->getClientIp(),
                        'referer' => $request->header('referer'),
                        'expires_at' => now()->addDays($this->getExpiryDays()),
                    ]);

                    event(new CreatedContentEvent(REFERRAL_MODULE_SCREEN_NAME, $request, $tracker));
                }
            }
        }
    }

    /**
     * Process the delete operation when a supported model is deleted
     *
     * @param Model $model The model to be deleted
     * @return void
     */
    public function processDelete(Model $model): void
    {
        (new Referral())

            ->isSponsorOrReferral($model)
            ->delete();

        (new ReferralAlias())

            ->HasUser($model)
            ->delete();
    }

    /**
     * Unhook all referrals associated with the given sponsor model.
     *
     * @param Model $sponsor The sponsor model
     * @return void
     */
    public function unHookAllReferrals(Model $sponsor): void
    {
        (new Referral())

            ->isSponsor($sponsor)
            ->delete();
    }

    /**
     * Unhook the sponsor from the referral model.
     *
     * @param Model $sponsor The sponsor model
     * @return void
     */
    public function unHookSponsor(Model $sponsor): void
    {
        (new Referral())

            ->IsReferral($sponsor)
            ->delete();
    }

    /**
     * Remove the referral relationship between a sponsor and a referral.
     *
     * @param Model $sponsor The sponsor model.
     * @param Model $referral The referral model.
     * @return void
     */
    public function unHookReferral(Model $sponsor, Model $referral): void
    {
        (new Referral())
            ->IsSponsor($sponsor)
            ->IsReferral($referral)
            ->delete();
    }

    /**
     * Get the X level of referrals
     *
     * @param Model $sponsor The sponsor model
     * @param int $level The level of referrals
     * @return \Illuminate\Support\Collection
     */
    public function getSubLevelReferrals(Model $sponsor, int $level = 1): \Illuminate\Support\Collection
    {
        $referrals = $this->getReferrals($sponsor);

        if ($level > 1) {
                for ($i = 2; $i <= $level; $i++) {
                    $referrals = $referrals->flatMap(function ($referral) {
                        return $this->getReferrals($referral->sponsor);
                    });
                }
            }

        return $referrals->map(function ($referral) {
            return $referral;
        });
    }

    /**
     * Get the tracking record for the given request.
     *
     * @param Request $request The HTTP request object
     * @return Model|null The tracking record, or null if not found
     */
    private function getTrackingRecord(Request $request): ?Model
    {
        return (new ReferralTracking())

            ->HasIpAddress($request->getClientIp())
            ->first();
    }

    /**
     * Get a unique alias.
     *
     * @return string
     */
    private function getUniqueAlias(): string
    {
        $alias = Str::random($this->getAliasLength());

        if ((new ReferralAlias())->HasAlias($alias)->exists()) {
            return $this->getUniqueAlias();
        }

        return $alias;
    }

    /**
     * Create a sponsor for a user model.
     *
     * @param Model $user The user model.
     * @param Model|ReferralAlias $alias The alias model.
     * @return Referral|Model|null The created sponsor or null.
     * @throws Exception
     */
    private function createSponsor(Model $user, Model|ReferralAlias $alias): null|Referral|Model
    {
        if ($this->isMembershipValidated($user, $alias->user()->getReferrals()->count())) {
            $sponsor = (new Referral())->updateOrCreate([
                'referral_type' => get_class($user),
                'referral_id' => $user->id,
            ], [
                'sponsor_type' => $alias->user_type,
                'sponsor_id' => $alias->user_id,
            ]);

            event(new CreatedContentEvent(REFERRAL_MODULE_SCREEN_NAME, request(), $sponsor));

            return $sponsor;
        }

        return null;
    }

    /**
     * Create an alias for the user model.
     *
     * @param Model $user The user model
     * @return Model The created or updated alias model
     */
    private function createAlias(Model $user): Model
    {
        return (new ReferralAlias())->updateOrCreate([
            'user_type' => get_class($user),
            'user_id' => $user->id,
        ], [
            'alias' => $this->getUniqueAlias(),
        ]);
    }

    /**
     * Get the value of the query parameter used for referral.
     *
     * @return string The query parameter value.
     */
    public function getQueryParam(): string
    {
        return setting('sc_referral_query_param', config('plugins.sc-referral.general.query_param'));
    }

    /**
     * Get the expiry days for the referral alias.
     *
     * @return string The expiry days for the referral alias
     */
    public function getExpiryDays(): string
    {
        return setting('sc_referral_expire_days', config('plugins.sc-referral.general.expire_days'));
    }

    /**
     * Get the length of the alias for referral.
     *
     * @return string The length of the alias for referral
     */
    public function getAliasLength(): string
    {
        return setting('sc_referral_alias_length', config('plugins.sc-referral.general.alias_length'));
    }

    public function getReferralLevels(): string
    {
        return setting('sc_referral_ref_levels', config('plugins.sc-referral.general.ref_levels'));
    }

    public function isMemberPluginEnabled(): string
    {
        return setting('sc_referral_enable_member_default', config('plugins.sc-referral.general.enable_member_default'));
    }

    /**
     * Check if the user's membership is validated.
     *
     * @param Model $user The user model
     * @param int|string $value The value to be validated
     * @return bool Returns true if the membership is validated, false otherwise
     */
    private function isMembershipValidated(Model $user, int|string $value): bool
    {
        try {
            if (defined('ACTION_HOOK_MEMBERSHIP_MODULE_VALIDATION')) {
                do_action(ACTION_HOOK_MEMBERSHIP_MODULE_VALIDATION, $user, ReferralLimitModule::class, $value);
            }
            return true;
        } catch (MembershipValidationException $exception) {
            return false;
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
        return true;
    }
}
