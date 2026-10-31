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
        // Meals marked as cooked, per plan, diet mode and week, like swaps and ticks.
        Schema::create('cooked_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_template_id')->constrained()->cascadeOnDelete();
            $table->string('diet_mode');
            $table->date('week_start');
            $table->string('weekday', 3);
            $table->string('meal');
            $table->timestamps();
            $table->unique(['household_id', 'plan_template_id', 'diet_mode', 'week_start', 'weekday', 'meal'], 'cooked_meals_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooked_meals');
    }
};
