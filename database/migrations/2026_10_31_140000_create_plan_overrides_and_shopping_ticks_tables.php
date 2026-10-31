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
        // A swap or "add to this meal" replaces the resolved dish for that plan, diet mode, day and meal (spec §4).
        Schema::create('plan_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_template_id')->constrained()->cascadeOnDelete();
            $table->string('diet_mode');
            $table->string('weekday', 3);
            $table->string('meal');
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['household_id', 'plan_template_id', 'diet_mode', 'weekday', 'meal'], 'plan_overrides_slot_unique');
        });

        // Things the household already has, ticked per plan and week (spec §5).
        Schema::create('shopping_ticks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_template_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['household_id', 'plan_template_id', 'week_start', 'ingredient_id'], 'shopping_ticks_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_ticks');
        Schema::dropIfExists('plan_overrides');
    }
};
