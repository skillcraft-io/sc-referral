<?php

namespace Skillcraft\Referral\Actions;

use Botble\Table\CollectionDataTable;
use Botble\Table\Columns\Column;
use Botble\Table\EloquentDataTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Skillcraft\Referral\Supports\ReferralHookManager;

class AddToHookTableAction
{
    public function addAliasHeaderToTable(array $headings, Model|string|null $model):array
    {
        if ($model instanceof Model && ReferralHookManager::isSupported($model)) {
            if ($this->isPermissioned()) {
                $heading = [
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
                $headings = array_merge($headings, $heading);
            }
        }

        return $headings;
    }

    public function addAliasColumnToTable(
        EloquentDataTable|CollectionDataTable $data,
        Model|string|null                     $model
    ): EloquentDataTable|CollectionDataTable
    {
        if ($model instanceof Model && ReferralHookManager::isSupported($model)) {
            if ($this->isPermissioned()) {

                $data->addColumn('alias', function ($item) {
                    return $item->getAlias()->alias;
                }, 0);

                $data->addColumn('sponsor', function ($item) {
                    return $item->getSponsor()?->alias ?: '-- / --';
                }, true);
            }
        }

        return $data;
    }

    private function isPermissioned():bool
    {
        return is_in_admin() && Auth::guard()->check() && !Auth::guard()->user()->hasAnyPermission($this->getRoutes());
    }

    protected function getRoutes(): array
    {
        $currentRoute = implode('.', explode('.', Route::currentRouteName(), -1));

        return apply_filters('referral-action-filter', [
            'create' => $currentRoute . '.create',
            'edit' => $currentRoute . '.edit',
        ]);
    }
}
