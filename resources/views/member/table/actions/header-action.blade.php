@php
use Skillcraft\Referral\Services\ReferralService;

$max_levels = (new ReferralService)->getReferralLevels();
@endphp

@if($max_levels > 1)
<span  data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="changeReferralLevel">
    @if($icon = $action->getIcon())
        <x-core::icon :name="$icon"/>
    @endif
</span>
<ul class="dropdown-menu" aria-labelledby="changeReferralLevel">
    <span class="dropdown-header">{{trans('Change Referral Level')}}</span>
        @php
            $counter = 1;
        @endphp
        @while($counter <= $max_levels)
            <li>
                <a class="dropdown-item referral-level" href="{{ $action->getLevelUrl('public.member.referrals.index', ['level' => $counter]) }}">
                    {!! trans('Referral Level :level', [
                        'level' => $counter
                    ]) !!}
                </a>
            </li>
            @php
                $counter++;
            @endphp
        @endwhile
    </ul>
@endif


