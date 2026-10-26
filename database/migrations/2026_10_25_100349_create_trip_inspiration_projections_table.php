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
        Schema::table('trips', function (Blueprint $table) {
            $table->unique(['id', 'created_by_user_id'], 'trips_id_creator_unique');
        });

        Schema::create('trip_inspiration_projections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id')->unique();
            $table->unsignedBigInteger('owner_id');
            $table->enum('trip_type', ['family', 'business', 'adventure']);
            $table->enum('transport', ['car', 'train', 'plane']);
            $table->enum('accommodation', ['hotel', 'cabin', 'camping']);
            $table->enum('duration_group', ['day', 'weekend', 'week', 'extended']);
            $table->timestamps();

            $table->index(
                ['trip_type', 'transport', 'accommodation', 'duration_group'],
                'trip_inspiration_metadata_idx',
            );
            $table->index('owner_id');

            $table->foreign(['trip_id', 'owner_id'])
                ->references(['id', 'created_by_user_id'])
                ->on('trips')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_inspiration_projections');

        Schema::table('trips', function (Blueprint $table) {
            $table->dropUnique('trips_id_creator_unique');
        });
    }
};
