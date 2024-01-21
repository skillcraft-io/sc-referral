<?php

namespace Skillcraft\Referral\Http\Controllers\Member;

use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Skillcraft\Referral\Tables\Member\MemberReferralTable;

class MemberReferralController extends BaseController
{
    public function index(MemberReferralTable $table)
    {
        PageTitle::setTitle(trans('plugins/sc-referral::referral.name'));

        return $table->renderTable();
    }
}
