<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('upc', 32)->nullable()->unique();
            $table->string('product_name');
            $table->decimal('price', 8, 2);
            $table->dateTime('last_confirmed');
        });

        // Self-referential: the product this one directly replaced (e.g. a
        // shrunk successor pack), not a shared "family" grouping — a
        // simultaneous different-size product is a distinct, unrelated
        // product, not a variant of this one.
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('replaces_product_id')->nullable()->constrained('products')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
