<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('image');
            $table->enum('room_type', ['living_room', 'bedroom', 'kitchen', 'bathroom', 'office']);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['room_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_ideas');
    }
};
