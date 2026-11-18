<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visibility_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('visibility_product_id')->constrained()->restrictOnDelete();
            // Cible : une publication (mise en avant, boost) ou une vitrine partenaire.
            $table->foreignId('publication_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_profile_id')->nullable()->constrained()->nullOnDelete();
            // Instantanés au moment de l'achat (le produit peut évoluer ensuite).
            $table->string('product_name');
            $table->string('kind', 30);
            $table->unsignedInteger('amount_cents');
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedSmallInteger('ranking_weight')->default(0);
            $table->string('status', 20)->default('pending_payment');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['publication_id', 'status', 'ends_at']);
            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visibility_purchases');
    }
};
