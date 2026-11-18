<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visibility_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // featured (mise en avant), boost, premium_partner
            $table->string('kind', 30)->index();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedSmallInteger('duration_days');
            // Poids dans le tri des résultats (plus élevé = plus haut).
            $table->unsignedSmallInteger('ranking_weight')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visibility_products');
    }
};
