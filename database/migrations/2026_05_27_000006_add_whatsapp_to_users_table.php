<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp', 20)->nullable()->after('email');
        });

        if (!Schema::hasColumn('tenant_settings', 'whatsapp_enabled')) {
            Schema::table('tenant_settings', function (Blueprint $table) {
                $table->boolean('whatsapp_enabled')->default(false)->after('support_whatsapp');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whatsapp');
        });

        if (Schema::hasColumn('tenant_settings', 'whatsapp_enabled')) {
            Schema::table('tenant_settings', function (Blueprint $table) {
                $table->dropColumn('whatsapp_enabled');
            });
        }
    }
};
