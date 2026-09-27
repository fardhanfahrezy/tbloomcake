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
        Schema::create('product_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->date('date');
            $table->integer('used_quota')->default(0);
            $table->timestamps();

            $table->unique(
                ['product_variant_id', 'date'],
                'availabilities_variant_date_unique'
            );
            $table->index('date', 'availabilities_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_availabilities');
    }
};
