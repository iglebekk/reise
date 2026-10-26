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
        Schema::create('trip_inspiration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projection_id')
                ->constrained('trip_inspiration_projections')
                ->cascadeOnDelete();
            $table->enum('canonical_name', [
                'Passport',
                'Phone',
                'Phone charger',
                'Power bank',
                'Toothbrush',
                'Toothpaste',
                'Sunscreen',
                'Water bottle',
                'Rain jacket',
                'Walking shoes',
                'Towel',
                'Hat',
            ]);
            $table->timestamps();

            $table->unique(['projection_id', 'canonical_name']);
            $table->index('canonical_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_inspiration_items');
    }
};
