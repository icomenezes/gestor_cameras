<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camera_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', ['motion', 'tampering', 'offline', 'online']);
            $table->string('snapshot_url')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('notified_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['camera_id', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_events');
    }
};
