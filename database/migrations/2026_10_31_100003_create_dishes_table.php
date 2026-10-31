<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name_en');
            $table->string('name_hi')->nullable();
            $table->string('status')->default('draft')->index();
            $table->jsonb('meals');
            $table->string('mood');
            $table->foreignId('cuisine_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('prep_min');
            $table->unsignedSmallInteger('cook_min');
            $table->unsignedTinyInteger('effort');
            $table->unsignedTinyInteger('spice');
            // Nutrition per serving.
            $table->unsignedSmallInteger('kcal');
            $table->unsignedSmallInteger('protein_g');
            $table->unsignedSmallInteger('carbs_g');
            $table->unsignedSmallInteger('fat_g');
            $table->unsignedSmallInteger('fibre_g');
            $table->jsonb('steps_en');
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });

        Schema::create('dish_country', function (Blueprint $table) {
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->primary(['dish_id', 'country_id']);
        });

        // The ingredient's own unit applies; quantities are per serving.
        Schema::create('dish_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('qty_per_serving', 8, 2);
            $table->unsignedSmallInteger('position');
            $table->unique(['dish_id', 'ingredient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dish_ingredients');
        Schema::dropIfExists('dish_country');
        Schema::dropIfExists('dishes');
    }
};
