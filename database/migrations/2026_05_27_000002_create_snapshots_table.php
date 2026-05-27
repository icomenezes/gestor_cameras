<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['camera_id', 'captured_at']);
        });

        // Adiciona intervalo de snapshot à tabela cameras
        Schema::table('cameras', function (Blueprint $table) {
            $table->unsignedSmallInteger('snapshot_interval_minutes')->nullable()
                  ->comment('null = desativado; ex: 5 = captura a cada 5 min');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshots');
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn('snapshot_interval_minutes');
        });
    }
};
