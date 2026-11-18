<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique des notifications envoyées (sans leur contenu).
     * dedupe_key garantit qu'une notification planifiée n'est envoyée qu'une fois.
     */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('notifiable');
            $table->string('notification');
            $table->string('channel', 30)->nullable();
            $table->nullableMorphs('subject');
            $table->string('dedupe_key')->nullable()->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['notification', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
