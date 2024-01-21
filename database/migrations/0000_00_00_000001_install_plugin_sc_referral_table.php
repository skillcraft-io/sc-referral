<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sc_referrals')) {
            Schema::create('sc_referrals', function (Blueprint $table) {
                $table->id();
                $table->morphs('sponsor');
                $table->morphs('referral');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('sc_referral_aliases')) {
            Schema::create('sc_referral_aliases', function (Blueprint $table) {
                $table->id();
                $table->morphs('user');
                $table->string('alias', 50)->unique()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sc_referral_trackings')) {
            Schema::create('sc_referral_trackings', function (Blueprint $table) {
                $table->id();
                $table->morphs('sponsor');
                $table->ipAddress('ip_address')->index();
                $table->string('referrer')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sc_referrals');
        Schema::dropIfExists('sc_referral_trackings');
        Schema::dropIfExists('sc_referral_aliases');
    }
};
