$(() => {
    BDashboard.loadWidget($('#widget_latest_referrals').find('.widget-content'), route('referral.widget.referral-list'))
})
