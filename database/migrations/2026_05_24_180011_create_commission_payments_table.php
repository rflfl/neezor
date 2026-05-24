<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_run_id')->constrained('commission_runs')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamp('paid_at');
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['commission_run_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_payments');
    }
};