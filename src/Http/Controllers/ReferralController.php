<?php

namespace Skillcraft\Referral\Http\Controllers;

use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Exception;
use Illuminate\Http\Request;
use Skillcraft\Referral\Models\Referral;
use Skillcraft\Referral\Tables\ReferralTable;

class ReferralController extends BaseController
{
    public function index(ReferralTable $table)
    {
        PageTitle::setTitle(trans('plugins/sc-referral::referral.name'));

        return $table->renderTable();
    }

    public function destroy(Referral $referral, Request $request, BaseHttpResponse $response)
    {
        try {
            $referral->delete();

            event(new DeletedContentEvent(REFERRAL_MODULE_SCREEN_NAME, $request, $referral));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function getWidgetLatestReferrals(Request $request): BaseHttpResponse
    {
        $limit = $request->integer('paginate', 10);
        $limit = $limit > 0 ? $limit : 10;

        $referrals = (new Referral())->query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $this
            ->httpResponse()
            ->setData(view('plugins/sc-referral::widgets.referral-list', compact('referrals', 'limit'))->render());
    }
}
