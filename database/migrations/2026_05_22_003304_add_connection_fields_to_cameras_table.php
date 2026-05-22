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
        Schema::table('cameras', function (Blueprint $table) {
            $table->string('ip', 100)->nullable()->after('stream_url');
            $table->unsignedSmallInteger('port')->default(554)->after('ip');
            $table->string('cam_username', 100)->default('admin')->after('port');
            $table->string('cam_password', 200)->nullable()->after('cam_username');
            $table->unsignedTinyInteger('channel')->default(1)->after('cam_password');
            $table->unsignedTinyInteger('subtype')->default(0)->after('channel');
            $table->boolean('is_recording')->default(false)->after('subtype');
        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn(['ip', 'port', 'cam_username', 'cam_password', 'channel', 'subtype', 'is_recording']);
        });
    }
};
