<?php

namespace Skillcraft\Referral\Listeners;

use Botble\Base\Facades\BaseHelper;
use Exception;
use Illuminate\Auth\Events\Registered;
use Skillcraft\Referral\Supports\ReferralHookManager;

class RegisteredEventListener
{
    public function handle(Registered $event): void
    {
        try {

           if(ReferralHookManager::isSupported($event->user)){
               $event->user->addMissingAlias();
           }
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }
}
