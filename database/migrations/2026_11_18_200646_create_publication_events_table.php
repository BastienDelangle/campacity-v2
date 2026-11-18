<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('organizer')->nullable();
            $table->text('program')->nullable();
            $table->string('registration_url')->nullable();
            $table->timestamps();

            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_events');
    }
};
