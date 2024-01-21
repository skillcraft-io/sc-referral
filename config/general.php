<?php

return [
    'supported' => [],
    'supported_forms' => [],
    'expire_days' => env('SKILLCRAFT_REFERRAL_EXPIRE_DAYS', 30),
    'query_param' => env('SKILLCRAFT_REFERRAL_QUERY_PARAM', 'ref'),
    'alias_length' => env('SKILLCRAFT_REFERRAL_DEFAULT_ALIAS_LENGTH', 8),
    'enable_member_default' => env('SKILLCRAFT_REFERRAL_ENABLE_MEMBER_DEFAULT', true),
];
