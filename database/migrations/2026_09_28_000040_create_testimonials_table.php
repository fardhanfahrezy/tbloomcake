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
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->text('content');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();

            $table->index('is_published', 'testimonials_is_published_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
