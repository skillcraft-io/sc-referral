<?php

namespace Skillcraft\Referral\Tests\Unit;

use Botble\Base\Events\CreatedContentEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Skillcraft\Core\Supports\Tests\CoreTestHelper;
use Skillcraft\Referral\Tests\ReferralTestCase;
use Skillcraft\Referral\Models\ReferralTracking;
use Skillcraft\Referral\Services\ReferralService;

class ReferralServiceTest extends ReferralTestCase
{
    use WithFaker, RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadReferralSupport();
    }

    public function testAddMissingAlias()
    {
        (new ReferralService)->addMissingAlias($this->user);

        $this->assertNotNull((new ReferralService)->getAlias($this->user));
    }

    public function testReferralHasAlias(): void
    {
        $alias = (new ReferralService)->getAlias($this->user);

        $this->assertNotNull($alias);
    }

    public function testProcessSponsorTracking(): void
    {
        Event::fake();

        $this->get((new ReferralService)->getReferralLink($this->user));

        $retVal = (new ReferralTracking())->query()
            ->where('expires_at', '>=', now())
            ->exists();

        $this->assertTrue($retVal, 'No ReferralTracking record found.');

        Event::hasDispatched(CreatedContentEvent::class);
    }

    public function testGetReferralLink(): void
    {
        $alias = (new ReferralService)->getAlias($this->user);

        $link = (new ReferralService)->getReferralLink($this->user);

        $truth_link = route('public.index', [
            setting('sc_referral_query_param', config('plugins.sc-referral.general.query_param')) => $alias->alias,
        ]);

        $this->assertEquals($truth_link, $link);
    }

    public function testUpdateAlias(): void
    {
        $new = fake()->username();

        $new_alias = (new ReferralService)->updateAlias($this->user, $new);

        $this->assertEquals($new_alias->alias, $new);
    }

    public function testGetSponsor(): void
    {
        $sponsor = (new CoreTestHelper)->addUser();

        (new ReferralService)->updateSponsor($this->user, (new ReferralService)->getAlias($sponsor)->id);

        $this->assertInstanceOf($this->user::class, (new ReferralService)->getSponsor($this->user));

        $this->assertEquals((new ReferralService)->getSponsor($this->user)->id, $sponsor->id);
    }

    public function testUpdateSponsor(): void
    {
        $sponsor = (new CoreTestHelper)->addUser();

        (new ReferralService)->updateSponsor($this->user, (new ReferralService)->getAlias($sponsor)->id);

        $this->assertInstanceOf($this->user::class, (new ReferralService)->getSponsor($this->user));

        $this->assertEquals((new ReferralService)->getSponsor($this->user)->id, $sponsor->id);

        $sponsor2 = (new CoreTestHelper)->addUser();

        (new ReferralService)->updateSponsor($this->user, (new ReferralService)->getAlias($sponsor2)->id);

        $this->assertEquals((new ReferralService)->getSponsor($this->user)->id, $sponsor2->id);
    }

    public function testGetReferrals(): void
    {

        $referrals = [];

        for ($i = 0; $i < 5; $i++) {
            $referrals[] = (new CoreTestHelper)->addUser();
            (new ReferralService)->updateSponsor($referrals[$i], (new ReferralService)->getAlias($this->user)->id);
        }

        $this->assertCount(5, (new ReferralService)->getReferrals($this->user));
    }

    public function testSubLevelReferrals(): void
    {
        $max_levels = 4;

        $referrals = [];

        $level = 1;
        for ($i = 0; $i < 5; $i++) {

            $referrals[] = (new CoreTestHelper)->addUser();

            $this->addAnotherReferralLevel($referrals[$i], $this->user);

            if($max_levels > 1) {
                if($level == 1) {
                    $level++;
                    $referrals_two[] = (new CoreTestHelper)->addUser();
                    (new ReferralService)->updateSponsor($referrals_two[$i], (new ReferralService)->getAlias($referrals[$i])->id);
                }

                if($level == 2) {
                    $level++;
                    $referrals_three[] = (new CoreTestHelper)->addUser();
                    (new ReferralService)->updateSponsor($referrals_three[$i], (new ReferralService)->getAlias($referrals_two[$i])->id);
                }

                if($level == 3) {
                    $level++;
                    $referrals_four[] = (new CoreTestHelper)->addUser();
                    (new ReferralService)->updateSponsor($referrals_four[$i], (new ReferralService)->getAlias($referrals_three[$i])->id);
                }
            }
        }

        $this->assertCount(5, (new ReferralService)->getReferrals($this->user));

        if($max_levels > 1) {
            if($level == 1) {
                $downline = (new ReferralService)->getSubLevelReferrals($this->user, 2);
                $this->assertCount(25, $downline);

                $this->assertNotContains(
                    (new ReferralService)->getReferrals($this->user),
                    $downline,
                    'Level two referrals contain level one referrals'
                );
            }

            if($level == 2) {
                $downline = (new ReferralService)->getSubLevelReferrals($this->user, 3);

                $this->assertCount(125, $downline);

                $this->assertNotContains(
                    (new ReferralService)->getReferrals($this->user),
                    $downline,
                    'Level three referrals contain level two referrals'
                );

            }

            if($level == 3) {
                $downline = (new ReferralService)->getSubLevelReferrals($this->user, 4);

                $this->assertCount(625, $downline);

                $this->assertNotContains(
                    (new ReferralService)->getReferrals($this->user),
                    $downline,
                    'Level four referrals contain level three referrals'
                );

            }
        }
    }

    private function addAnotherReferralLevel($referral, $sponsor): void
    {
        (new ReferralService)->updateSponsor($referral, (new ReferralService)->getAlias($sponsor)->id);
    }
}
