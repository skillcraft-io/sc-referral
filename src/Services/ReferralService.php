<?php

namespace Skillcraft\Referral\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Botble\Chart\Supports\Base;
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
use Illuminate\Support\Facades\Validator;
use Botble\Base\Events\CreatedContentEvent;
use Illuminate\Database\Eloquent\Collection;
use Skillcraft\Referral\Models\ReferralAlias;
use Illuminate\Validation\ValidationException;
use Skillcraft\Referral\Models\ReferralTracking;
use Skillcraft\Referral\Supports\ReferralHookManager;
use Botble\Support\Http\Requests\Request as BaseRequest;
use Skillcraft\Referral\Http\Requests\ReferralAliasRequest;
use Skillcraft\Referral\Supports\Membership\ReferralLimitModule;
use Skillcraft\Membership\Exceptions\MembershipValidationException;

class ReferralService
{
    public function getReferralLink(Model $user): string
    {
        return route('public.index', [
            $this->getQueryParam() => $user->getAlias()->alias,
        ]);
    }

    /**
     * @param Model $user
     * @return Model
     */
    public function getAlias(Model $user): Model
    {
        //account for create screen
        if (!$user->exists) {
            return $user;
        }

        return (new ReferralAlias())->query()->HasUser($user)->firstOr(function () use ($user) {
            return $this->createAlias($user);
        });
    }

    /**
     * @param Model $user
     * @param string $alias
     * @return Model
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
     * @param Model $user
     * @param string $alias
     * @return ?Model
     */
    public function updateSponsor(Model $user, string|int $alias_id): ?Model
    {
        if ($alias_id === "-") {
            return $this->unHookSponsor($user);
        }

        $sponsor_alias = (new ReferralAlias())
            ->query()
            ->where('id', $alias_id)
            ->first();

        return $this->createSponsor($user, $sponsor_alias);
    }


    /**
     * @param Model $referral
     * @return ?Model
     */
    public function getSponsor(Model $referral): ?Model
    {
        $sponsor_rec = (new Referral())->query()->IsReferral($referral)->first();

        if ($sponsor_rec) {
            return $sponsor_rec->sponsor;
        }

        return null;
    }

    public function getReferrals(Model $sponsor): Collection
    {
        return (new ReferralAlias())->query()->IsSponsor($sponsor)->get();
    }

    public function addMissingAlias(Model $user): void
    {
        if ((new ReferralAlias())->query()->HasUser($user)->exists()) {
            $alias = $this->createAlias($user);
            event(new CreatedContentEvent(REFERRAL_MODULE_SCREEN_NAME, request(), $alias));
        }
    }

    public function processSponsorCheck(Request $request, Model $referral): void
    {
        $tracking_record = (new ReferralTracking())->query()->where('ip_address', $request->getClientIp())->first();

        if (!empty($tracking_record)) {
            $sponsor = $this->createSponsor(
                $referral,
                (new ReferralAlias())
                    ->query()
                    ->where('sponsor_type', $tracking_record->sponsor_type)
                    ->where('sponsor_id', $tracking_record->sponsor_id)
                    ->first()
            );

            if (!empty($sponsor)) {
                $tracking_record->delete();

                event(new CreatedContentEvent(REFERRAL_MODULE_SCREEN_NAME, $request, $sponsor));
            }
        }
    }

    public function processSponsorTracking(Request $request): void
    {
        if (!is_in_admin()) {
            $hasTracking = (new ReferralTracking())
                ->query()
                ->where('ip_address', $request->getClientIp())
                ->where('expires_at', '>=', now())
                ->exists();

            if (!$hasTracking) {
                $alias_record = (new ReferralAlias())
                    ->query()
                    ->where('alias', $request->query($this->getQueryParam()))
                    ->first();

                if (!empty($alias_record)) {
                    $tracker = (new ReferralTracking())->query()->create([
                        'sponsor_type' => $alias_record->user_type,
                        'sponsor_id'   => $alias_record->user_id,
                        'ip_address'   => $request->getClientIp(),
                        'referer'     => $request->header('referer'),
                        'expires_at'   => now()->addDays($this->getExpiryDays()),
                    ]);

                    event(new CreatedContentEvent(REFERRAL_MODULE_SCREEN_NAME, $request, $tracker));
                }
            }
        }
    }

    public function processDelete(Model $model): void
    {
        (new Referral())
            ->query()
            ->where(function ($query) use ($model) {
                $query->IsSponsor($model)
                    ->orWhere(function ($query) use ($model) {
                        $query->IsReferral($model);
                    });
            })
            ->delete();

        (new ReferralAlias())
            ->query()
            ->HasUser($model)
            ->delete();
    }

    public function addAliasFormMetabox(mixed $priority, mixed $data, string $input = 'referral_alias_wrap'): void
    {
        if (!empty($data) && is_object($data) && ReferralHookManager::isSupported($data)) {
            MetaBox::addMetaBox(
                $input,
                trans('Alias'),
                function () use ($input, $data) {
                    $metadata = ($data->exists) ? $data->getAlias() : null;

                    if ($metadata && !empty($metadata)) {
                        $metadata = $metadata->alias;
                    }

                    return view(
                        'plugins/sc-referral::add-textbox-meta-box',
                        compact('metadata', 'input')
                    );
                },
                get_class($data),
                'top',
                'default'
            );
        }
    }

    public function addSponsorFormMetabox(mixed $priority, mixed $data, string $input = 'referral_sponsor_wrap'): void
    {
        if (!empty($data) && is_object($data) && ReferralHookManager::isSupported($data)) {
            MetaBox::addMetaBox(
                $input,
                trans('Sponsor'),
                function () use ($input, $data) {

                    $query = (new ReferralAlias())->query();

                    if ($data->exists) {
                        $query = $query->IsNotUser($data);
                    }

                    $query = $query->pluck('alias', 'user_id')->toArray();

                    $options = ['-' => '-- No Sponsor --'] + $query;

                    $metadata = ($data->exists) ? $data->getSponsor() : null;

                    if ($metadata) {
                        $metadata = $metadata->id;
                    }

                    return view(
                        'plugins/sc-referral::add-select-meta-box',
                        compact('options', 'metadata', 'input')
                    );
                },
                get_class($data),
                'top',
                'default'
            );
        }
    }

    public static function saveMetaData(string $screen, Request $request, Model $object): void
    {
        if (ReferralHookManager::isSupported($object)) {
            try {
                if ($request->has('referral_alias_wrap') && !empty($request->input('referral_alias_wrap'))) {
                    $object->updateAlias($request->input('referral_alias_wrap'));
                }

                if ($request->has('referral_sponsor_wrap') && !empty($request->input('referral_sponsor_wrap'))) {
                    $object->updateSponsor($request->input('referral_sponsor_wrap'));
                }
            } catch (ValidationException $e) {
                throw ValidationException::withMessages($e->errors());
            } catch (Exception $exception) {
                BaseHelper::logError($exception);
            }
        }
    }

    public function addAliasColumnToTable(
        EloquentDataTable|CollectionDataTable $data,
        Model|string|null $model
    ): EloquentDataTable|CollectionDataTable {
        if ($model instanceof Model && ReferralHookManager::isSupported($model)) {
            $route = $this->getRoutes();

            if (is_in_admin() && Auth::guard()->check() && !Auth::guard()->user()->hasAnyPermission($route)) {
                return $data;
            }

            $data->addColumn('alias', function ($item) {
                return $item->getAlias()->alias;
            }, 0);

            $data->addColumn('sponsor', function ($item) {
                return $item->getSponsor()?->alias ?: '-- / --';
            }, true);
        }

        return $data;
    }

    public function addAliasHeaderToTable(array $headings, Model|string|null $model)
    {
        if ($model instanceof Model && ReferralHookManager::isSupported($model)) {
            if (is_in_admin() && Auth::guard()->check() && !Auth::guard()->user()->hasAnyPermission($this->getRoutes())) {
                return $headings;
            }
            $heading =  [
                Column::make('alias')
                    ->title(trans('Alias'))
                    ->addClass('text-center no-sort')
                    ->orderable(false)
                    ->searchable(false)
                    ->titleAttr(trans('Alias')),
                Column::make('sponsor')
                    ->title(trans('Sponsor'))
                    ->addClass('text-center no-sort')
                    ->orderable(false)
                    ->searchable(false)
                    ->titleAttr(trans('Sponsor'))
            ];

            return array_merge($headings, $heading);
        }

        return $headings;
    }

    public function unHookAllReferrals(Model $sponsor): void
    {
        (new Referral())
            ->query()
            ->IsSponsor($sponsor)
            ->delete();
    }

    public function unHookSponsor(Model $sponsor): void
    {
        (new Referral())
            ->query()
            ->IsReferral($sponsor)
            ->delete();
    }

    public function unHookReferral(Model $sponsor, Model $referral): void
    {
        (new Referral())->query()
            ->IsSponsor($sponsor)
            ->IsReferral($referral)
            ->delete();
    }

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

    public function addRulesToSupportedForms(array $rules, BaseRequest $formRequest)
    {

        $id = $this->resolveRouteResourceKey($formRequest);

        if (ReferralHookManager::isSupportedForm($formRequest)) {
            $rules = array_merge($rules, $this->aliasRules($id));
        }

        return $rules;
    }

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
                $id = (int) $value;
            }
        }

        return $id;
    }


    protected function getRoutes(): array
    {
        $currentRoute = implode('.', explode('.', Route::currentRouteName(), -1));

        return apply_filters('referal-action-filter', [
            'create' => $currentRoute . '.create',
            'edit' => $currentRoute . '.edit',
        ]);
    }

    private function getUniqueAlias(): string
    {
        $alias = Str::random($this->getAliasLength());

        if ((new ReferralAlias())->query()->where('alias', $alias)->exists()) {
            return $this->getUniqueAlias();
        }

        return $alias;
    }

    private function createSponsor(Model $user, ReferralAlias $alias): ?Referral
    {
        if ($this->isMembershipValidated($user, $alias->getReferrals()->count())) {
            $sponsor = (new Referral())->query()->updateOrCreate([
                'referral_type' => get_class($user),
                'referral_id'   => $user->id,
            ], [
                'sponsor_type'  => $alias->user_type,
                'sponsor_id'    => $alias->user_id,
            ]);

            event(new CreatedContentEvent(REFERRAL_MODULE_SCREEN_NAME, request(), $sponsor));

            return $sponsor;
        }

        return null;
    }

    private function createAlias(Model $user): Model
    {
        return (new ReferralAlias())->query()->updateOrCreate([
            'user_type' => get_class($user),
            'user_id' => $user->id,
        ], [
            'alias' => $this->getUniqueAlias(),
        ]);
    }

    private function getQueryParam(): string
    {
        return setting('sc_referral_query_param', config('plugins.sc-referral.general.query_param'));
    }

    private function getExpiryDays(): string
    {
        return setting('sc_referral_expire_days', config('plugins.sc-referral.general.expire_days'));
    }

    private function getAliasLength(): string
    {
        return setting('sc_referral_alias_length', config('plugins.sc-referral.general.alias_length'));
    }

    /**
     * @throws Exception
     * @return void
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
