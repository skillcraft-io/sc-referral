<?php

return [
    'name' => 'Referrals',
    'description' => 'Review System Referrals',
    'widget_latest_referrals' => 'Latest Referrals',
    'no_new_referral_title' => 'No new referrals yet',
    'no_new_referral_now' => 'Your members have not made any new referrals yet.',
    'tables' => [
        'referral' => 'Referral',
        'sponsor' => 'Sponsor',
    ],
    'settings' => [
        'title' => 'Referral Settings',
        'description' => 'Adjust referral plugin system settings',
        'expire_days' => 'Tracking Expiry Days',
        'alias_length' => 'Default Alias Length',
        'ref_levels' => 'Referral Levels',
        'enable_member_default' => 'Enable Member Plugin (must be installed and active)',
        'query_param' => 'Referral Query Parameter'

    ]
];
