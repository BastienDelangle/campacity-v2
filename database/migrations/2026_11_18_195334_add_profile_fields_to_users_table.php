<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->boolean('show_email')->default(false)->after('website');
            $table->boolean('show_phone')->default(false)->after('show_email');
            $table->boolean('show_website')->default(true)->after('show_phone');
            $table->timestamp('anonymized_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'website', 'show_email', 'show_phone', 'show_website', 'anonymized_at']);
        });
    }
};
