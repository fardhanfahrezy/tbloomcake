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
        Schema::create('custom_cake_requests', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_whatsapp', 20);
            $table->date('pickup_date');
            $table->string('size_label', 50)->nullable();
            $table->string('flavor', 100)->nullable();
            $table->string('filling', 100)->nullable();
            $table->string('color', 100)->nullable();
            $table->text('cake_text')->nullable();
            $table->boolean('wants_fondant')->default(false);
            $table->text('fondant_detail')->nullable();
            $table->boolean('wants_print')->default(false);
            $table->text('additional_notes')->nullable();
            $table->string('reference_image_path')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index('status', 'custom_cake_requests_status_idx');
            $table->index('pickup_date', 'custom_cake_requests_pickup_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_cake_requests');
    }
};
