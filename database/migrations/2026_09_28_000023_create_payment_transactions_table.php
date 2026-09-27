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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('gateway', 30);
            $table->string('gateway_reference', 100)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->json('raw_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['gateway', 'gateway_reference'],
                'payment_transactions_gateway_ref_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
