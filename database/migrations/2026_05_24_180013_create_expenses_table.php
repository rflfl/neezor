<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->boolean('is_recurring')->default(false);
            $table->string('description')->nullable();
            $table->date('due_date');
            $table->timestamps();

            $table->index(['tenant_id', 'due_date']);
            $table->index(['tenant_id', 'is_recurring']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};