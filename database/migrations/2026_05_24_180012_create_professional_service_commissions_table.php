<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_service_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->decimal('commission_rate', 5, 4);
            $table->timestamps();

            $table->unique(['professional_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_service_commissions');
    }
};