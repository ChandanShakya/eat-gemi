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
        Schema::create('visited_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('place_id')->index(); // Google Places ID
            $table->string('name');
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->text('menu_image_url')->nullable();
            $table->json('menu_table')->nullable(); // Structured menu data
            $table->timestamp('visited_at');
            $table->timestamps();

            // Composite index for user and place
            $table->unique(['user_id', 'place_id']);

            // Spatial index for location-based queries
            $table->index(['lat', 'lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visited_places');
    }
};
