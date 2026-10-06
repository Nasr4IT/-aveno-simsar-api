<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sponsored banners for the home-screen carousel — a business pays the
// admin directly (e.g. via Sham Cash, outside the ad-posting flow) and the
// admin uploads a banner image for that window. Deliberately separate from
// `ads`: this isn't a listing, has no owner/approval workflow, and is
// managed entirely by admins.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->string('title')->nullable();
            // https://... or tel:... — whatever tapping the banner should open.
            $table->string('link_url')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('sort_order')->default(0);
            // Separate from the starts_at/ends_at window so an admin can
            // pause a banner early without losing/recalculating its dates.
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
