<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->nullable()->constrained('dining_tables')->nullOnDelete();
            $table->enum('type', ['dine_in', 'takeaway'])->default('takeaway');
            $table->string('note', 255)->nullable();
            $table->foreignId('server_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'waiting', 'preparing', 'ready', 'served', 'cancelled'])->default('pending');
            $table->unsignedInteger('total')->default(0);
            $table->boolean('paid')->default(false);
            $table->index(['status', 'created_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
