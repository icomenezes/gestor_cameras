<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN plan ENUM('trial','monthly','quarterly','annual') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("DELETE FROM subscriptions WHERE plan = 'trial'");
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN plan ENUM('monthly','quarterly','annual') NOT NULL");
    }
};
