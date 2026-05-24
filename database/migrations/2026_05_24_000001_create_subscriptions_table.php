<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('plan', ['monthly', 'quarterly', 'annual'])->default('monthly');
            $table->enum('status', ['active', 'suspended', 'expired', 'cancelled'])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
