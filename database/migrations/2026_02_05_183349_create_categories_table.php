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

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->noActionOnDelete(); 

            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->decimal('price', 10, 2);

            $table->string('image_url', 255)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // opcional: indexes útiles
            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
