<?php

namespace Skillcraft\Referral\Tests\Unit;

use Illuminate\Auth\Events\Registered;
use Skillcraft\Core\Supports\Tests\CoreTestHelper;
use Skillcraft\Referral\Listeners\RegisteredEventListener;
use Skillcraft\Referral\Models\ReferralAlias;
use Skillcraft\Referral\Tests\ReferralTestCase;

class RegisteredEventListenerTest extends ReferralTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadReferralSupport();
    }

    public function testSupportedListenerEvent()
    {
        $user = (new CoreTestHelper)->addUser();

        $event = new Registered($user);

        $listener = new RegisteredEventListener();

        // Handle the event
        $listener->handle($event);

        $this->assertTrue((new ReferralAlias())->HasUser($user)->exists());
    }
}
