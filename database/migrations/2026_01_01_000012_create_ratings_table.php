<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // "نظام تقييم المستخدمين بعضهم البعض بعد التعامل، لبناء الثقة داخل المنصة".
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rated_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rater_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ad_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('score')->comment('1-5');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['rater_user_id', 'rated_user_id', 'ad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
