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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->string('size_label', 50);
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('max_daily_quota')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'size_label'], 'product_variants_product_size_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
