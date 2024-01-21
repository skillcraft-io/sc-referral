@if ($referrals->isNotEmpty())
    <div class="table-responsive">
        <x-core::table>
            <x-core::table.header>
                <x-core::table.header.cell>
                    {{ trans('plugins/sc-referral::referral.tables.sponsor') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell>
                    {{ trans('plugins/sc-referral::referral.tables.referral') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell>
                {{ trans('core/base::tables.created_at') }}
                </x-core::table.header.cell>
            </x-core::table.header>

            <x-core::table.body>
                
                @foreach ($referrals as $item)
                    <x-core::table.body.row>
                        <x-core::table.body.cell>
                            {!! $item->sponsor->name ?? $item->sponsor_id; !!}
                        </x-core::table.body.cell>
                        <x-core::table.body.cell>
                                {!! $item->referral->name ?? $item->referral_id; !!}
                        </x-core::table.body.cell>
                        <x-core::table.body.cell>
                            {{ BaseHelper::formatDate($item->created_at) }}
                        </x-core::table.body.cell>
                    </x-core::table.body.row>
                @endforeach
            </x-core::table.body>
        </x-core::table>
    </div>
@else
    <x-core::empty-state
        :title="trans('plugins/sc-referral::referral.no_new_referral_title')"
        :subtitle="trans('plugins/sc-referral::referral.no_new_referral_now')"
    />
@endif
