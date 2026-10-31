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
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('who_cooks');
            $table->unsignedTinyInteger('people');
            $table->unsignedTinyInteger('nonveg_eaters');
            $table->boolean('veg_eat_eggs');
            $table->jsonb('leave_out');
            $table->foreignId('plan_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cook_name')->nullable();
            // National number without the dial code, checked against the country's pattern.
            $table->string('cook_phone', 20)->nullable();
            $table->string('cook_language', 2);
            $table->timestamps();
        });

        Schema::create('household_user', function (Blueprint $table) {
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();
            $table->primary(['household_id', 'user_id']);
            // One household per person for now; invites come later.
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_user');
        Schema::dropIfExists('households');
    }
};
