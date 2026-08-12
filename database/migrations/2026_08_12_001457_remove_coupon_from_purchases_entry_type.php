<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE purchases MODIFY entry_type ENUM('scan', 'lump_sum') NOT NULL DEFAULT 'scan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE purchases MODIFY entry_type ENUM('scan', 'coupon', 'lump_sum') NOT NULL DEFAULT 'scan'");
    }
};
