<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexe les deux dates des opérations, que le grand livre filtre et trie.
     */
    public function up(): void
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->index('delivery_at');
            $table->index('created_at');
        });
    }

    /**
     * Retire les index des dates des opérations.
     */
    public function down(): void
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropIndex(['delivery_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
