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
        Schema::create('pricing_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('trial_days');
            $table->unsignedSmallInteger('first_charge_day');
            $table->unsignedSmallInteger('reminder_day');
            $table->unsignedSmallInteger('pre_debit_notice_hours_before');
            $table->unsignedSmallInteger('past_due_grace_days');
            $table->timestamps();
        });

        // Money is integer minor units (paise, cents) plus an ISO currency code.
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->string('market');
            $table->string('interval');
            $table->char('currency', 3);
            $table->unsignedInteger('amount_minor');
            $table->timestamps();
            $table->unique(['market', 'interval']);
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->char('base', 3);
            $table->char('currency', 3);
            $table->decimal('rate', 18, 8);
            $table->date('as_of');
            $table->timestamps();
            $table->unique(['base', 'currency', 'as_of']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('prices');
        Schema::dropIfExists('pricing_settings');
    }
};
