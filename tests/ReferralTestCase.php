<?php

namespace Skillcraft\Referral\Tests;

use Skillcraft\Core\Supports\Tests\CoreTestHelper;
use Skillcraft\Referral\Supports\ReferralHookManager;
use Tests\TestCase;

class ReferralTestCase extends TestCase
{
    public $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('cms:plugin:activate sc-core', []);

        $this->artisan('cms:plugin:activate sc-referral', []);

        $this->user = (new CoreTestHelper)->addUser();
    }

    public function loadReferralSupport(): void
    {
        ReferralHookManager::registerHooks($this->user::class, 'user');
        ReferralHookManager::addMacroHooks();
    }
}
