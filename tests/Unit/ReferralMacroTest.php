<?php

namespace Skillcraft\Referral\Tests\Unit;

use Mockery;
use Skillcraft\Referral\Tests\ReferralTestCase;
use Botble\ACL\Models\User;
use Botble\Base\Supports\MacroableModels;

class ReferralMacroTest extends ReferralTestCase
{
    protected $mm;
    protected $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->mm = new MacroableModels();

        $this->model = new User();
    }

    public function testMacroRegistration()
    {
        $macros = [
            'getAlias',
            'updateAlias',
            'updateSponsor',
            'unHookSponsor',
            'unHookReferral',
            'unHookAllReferrals',
            'addMissingAlias',
            'getSponsor',
            'getReferrals',
            'getReferralLink'
        ];

        $this->assertEmpty($this->mm->getAllMacros());

        foreach ($macros as $macro) {
            $this->checkExceptionIsThrown($macro);
        }

        foreach ($macros as $macro) {
            $this->mm->addMacro($this->model, $macro, function () {
                return true;
            });
        }

        foreach ($macros as $macro) {
            $this->assertTrue($this->model->{$macro}());
        }
    }

    public function checkExceptionIsThrown(string $macro)
    {
        $this->expectException(\BadMethodCallException::class);
        $this->model->{$macro}();
    }
}
