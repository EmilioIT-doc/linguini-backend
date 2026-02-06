<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')
                ->constrained('carts')
                ->cascadeOnDelete(); // si borras carrito (hard delete), se borran items

            $table->foreignId('product_id')
                ->constrained('products'); // ✅ sin restrict (SQL Server), default = NO ACTION

            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->string('notes', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
