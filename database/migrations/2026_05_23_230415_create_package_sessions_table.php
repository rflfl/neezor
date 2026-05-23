<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->integer('sessions_remaining');
            $table->dateTime('used_at')->nullable();
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'package_id']);
            $table->index('expires_at');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_sessions');
    }
};