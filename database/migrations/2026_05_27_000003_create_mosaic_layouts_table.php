<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mosaic_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('grid')->default('2x2'); // 2x2, 3x3, 1+5
            $table->json('camera_ids')->nullable();  // ordem das câmeras nos slots
            $table->unsignedSmallInteger('rotation_seconds')->nullable(); // null = sem rotação
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mosaic_layouts');
    }
};
