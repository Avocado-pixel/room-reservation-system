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
        Schema::table('users', function (Blueprint $table) {
            // Add phone_country column after phone
            $table->string('phone_country', 2)->default('ES')->after('phone');
            
            // Modify phone column to support E.164 format (up to 15 digits + country code)
            $table->string('phone', 25)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_country');
            $table->string('phone', 15)->change();
        });
    }
};
