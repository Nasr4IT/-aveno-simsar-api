<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Core listing entity. status drives the admin "review before publish" workflow
    // (proposal §2 لوحة تحكم الإدارة / نظام مراجعة المنشورات).
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('governorate_id')->constrained();
            $table->foreignId('city_id')->constrained();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'expired', 'sold'])
                ->default('pending')
                ->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->boolean('is_featured')->default(false)->index();
            $table->foreignId('ad_package_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('featured_until')->nullable();

            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index(['governorate_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
