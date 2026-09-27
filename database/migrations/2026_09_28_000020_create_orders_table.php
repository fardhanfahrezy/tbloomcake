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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 30)->unique();
            $table->string('customer_name');
            $table->string('customer_whatsapp', 20);
            $table->string('customer_email')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('unpaid');
            $table->date('pickup_date');
            $table->time('pickup_time');
            $table->string('delivery_type', 10)->default('pickup');
            $table->text('delivery_address')->nullable();
            $table->string('delivery_recipient_phone', 20)->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index('status', 'orders_status_idx');
            $table->index('pickup_date', 'orders_pickup_date_idx');
            $table->index('customer_whatsapp', 'orders_customer_whatsapp_idx');
            $table->index(['status', 'pickup_date'], 'orders_status_pickup_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
