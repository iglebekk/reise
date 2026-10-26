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
        Schema::create('packing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('participant_id')->nullable();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign(['participant_id', 'trip_id'])
                ->references(['id', 'trip_id'])
                ->on('trip_participants')
                ->cascadeOnDelete();

            $table->index(['trip_id', 'participant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_items');
    }
};
