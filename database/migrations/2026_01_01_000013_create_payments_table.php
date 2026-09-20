<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tracks a Sham Cash checkout for a featured-ad package. The webhook
    // (PaymentController@webhook) flips status to 'paid' and activates the package.
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_package_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('provider')->default('shamcash');
            $table->string('provider_reference')->nullable()->index();
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending')->index();
            $table->json('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
