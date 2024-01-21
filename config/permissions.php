<?php

return [
    [
        'name' => 'Referrals',
        'flag' => 'referral.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'referral.destroy',
        'parent_flag' => 'referral.index',
    ],
];
