<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline', 160)->nullable();
            $table->text('presentation')->nullable();
            $table->string('activity')->nullable();
            $table->string('cover_path')->nullable();
            // Période de visibilité de la vitrine.
            $table->timestamp('visible_from')->nullable();
            $table->timestamp('visible_until')->nullable();
            $table->boolean('show_on_home')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['visible_from', 'visible_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_profiles');
    }
};
