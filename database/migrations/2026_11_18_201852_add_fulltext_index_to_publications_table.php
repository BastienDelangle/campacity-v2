<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->fullText(['title', 'description'], 'publications_search_fulltext');
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropFullText('publications_search_fulltext');
        });
    }
};
