<?php

namespace Skillcraft\Referral\Actions;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\MetaBox;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Skillcraft\Referral\Models\ReferralAlias;
use Skillcraft\Referral\Supports\ReferralHookManager;

class AddMetaBoxToHookAction
{
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
}
