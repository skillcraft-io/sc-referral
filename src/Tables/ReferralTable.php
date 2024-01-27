<?php

namespace Skillcraft\Referral\Tables;

use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Skillcraft\Referral\Models\Referral;

class ReferralTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Referral::class)
            ->addActions([
                DeleteAction::make()
                    ->route('referral.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('sponsor_id', function (Referral $item) {
                return $item->sponsor->name ?? $item->sponsor_id;
            })
            ->editColumn('referral_id', function (Referral $item) {
                return $item->referral->name ?? $item->referral_id;
            });

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this
            ->getModel()
            ->query()
            ->select([
                'id',
                'referral_type',
                'referral_id',
                'sponsor_type',
                'sponsor_id',
                'created_at',
            ]);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            Column::make('referral_id'),
            Column::make('sponsor_id'),
            CreatedAtColumn::make(),
        ];
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('referral.destroy'),
        ];
    }

    public function getFilters(): array
    {
        return [];
    }
}
