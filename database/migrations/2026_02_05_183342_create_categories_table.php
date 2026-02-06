<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // slug para urls bonitas
            $table->string('slug', 150)->unique();

            $table->timestamps();

            // opcional: indexes útiles
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
