<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 140);
            $table->string('description', 255);
            $table->unsignedInteger('price');
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('image', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->index(['category_id', 'active']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
