<?php

return [
    [
        'name' => 'Referrals',
        'flag' => 'referral.index',
    ],
    [
        'name' => 'Referral Settings',
        'flag' => 'referral.settings',
        'parent_flag' => 'referral.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'referral.destroy',
        'parent_flag' => 'referral.index',
    ],
];
