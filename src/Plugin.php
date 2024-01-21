<?php

namespace Skillcraft\Referral;

use Illuminate\Support\Facades\Schema;
use Botble\PluginManagement\Abstracts\PluginOperationAbstract;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Schema::dropIfExists('sc_referrals');
        Schema::dropIfExists('sc_referral_trackings');
        Schema::dropIfExists('sc_referral_aliases');
    }
}
