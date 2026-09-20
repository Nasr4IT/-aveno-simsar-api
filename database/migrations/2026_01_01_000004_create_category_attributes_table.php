<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Per-category dynamic specs (proposal example: مستعملة/جديدة، مصدومة، بنزين/ديزل/هجين/كهربائي...).
    // "type" drives how the Flutter app renders the field and how AdController validates it.
    public function up(): void
    {
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('key')->comment('machine name, e.g. fuel_type');
            $table->string('label_ar');
            $table->string('label_en')->nullable();
            $table->enum('type', ['text', 'number', 'boolean', 'select', 'multiselect'])->default('text');
            $table->json('options')->nullable()->comment('choice list for select/multiselect, e.g. ["بنزين","ديزل","هجين","كهربائي"]');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(true)->comment('exposed on the search/filter screen');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attributes');
    }
};
