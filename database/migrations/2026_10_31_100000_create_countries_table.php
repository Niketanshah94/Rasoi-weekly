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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique();
            $table->string('name');
            $table->string('dial_code');
            $table->string('mobile_regex');
            $table->unsignedTinyInteger('mobile_length');
            $table->string('mobile_placeholder');
            $table->string('mobile_error');
            $table->char('currency', 3);
            $table->string('billing_provider');
            $table->jsonb('web_payment_methods');
            $table->string('cards_label');
            $table->string('units');
            $table->string('shopping_list_name');
            $table->string('spices_note');
            $table->string('tax_note');
            $table->string('invoice_name');
            $table->string('privacy_law');
            // Which local_names entry ingredients use here; "default" means name_en.
            $table->string('ingredient_names');
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
