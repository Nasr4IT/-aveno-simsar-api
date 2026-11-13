<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The "Publish ad" options from the app design: negotiable price, a free-text
// address, and how buyers may contact the seller. Defaults keep existing ads
// behaving as before (chat allowed; calls allowed but only once the seller's
// phone is actually shown — see AdResource 'contact_phone').
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->boolean('is_negotiable')->default(false)->after('currency');
            $table->string('address', 255)->nullable()->after('city_id');
            $table->boolean('allow_chat')->default(true)->after('longitude');
            $table->boolean('allow_calls')->default(true)->after('allow_chat');
            $table->boolean('hide_phone')->default(false)->after('allow_calls');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['is_negotiable', 'address', 'allow_chat', 'allow_calls', 'hide_phone']);
        });
    }
};
