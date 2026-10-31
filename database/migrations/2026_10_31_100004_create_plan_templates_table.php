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
        Schema::create('plan_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description');
            // Null: dishes come from the household's own country. Set: from this country.
            $table->foreignId('dish_country_id')->nullable()->constrained('countries')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });

        Schema::create('plan_template_country', function (Blueprint $table) {
            $table->foreignId('plan_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->primary(['plan_template_id', 'country_id']);
        });

        // Restricting dish deletes keeps a dish used in a plan from being deleted (spec §9).
        Schema::create('plan_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_template_id')->constrained()->cascadeOnDelete();
            $table->string('meal');
            $table->string('weekday', 3);
            $table->foreignId('dish_id')->constrained()->restrictOnDelete();
            $table->unique(['plan_template_id', 'meal', 'weekday']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_template_items');
        Schema::dropIfExists('plan_template_country');
        Schema::dropIfExists('plan_templates');
    }
};
