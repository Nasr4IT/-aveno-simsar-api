<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Stores the actual value an ad has for each dynamic category_attribute
    // (EAV pattern — keeps categories/specs fully data-driven, no schema change per category).
    public function up(): void
    {
        Schema::create('ad_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_attribute_id')->constrained()->cascadeOnDelete();
            $table->text('value');
            $table->timestamps();

            $table->unique(['ad_id', 'category_attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_attribute_values');
    }
};
