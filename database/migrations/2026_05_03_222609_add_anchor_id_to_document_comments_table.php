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
        Schema::table('document_comments', function (Blueprint $table) {
            $table->string('anchor_id')->nullable()->after('quoted_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_comments', function (Blueprint $table) {
            $table->dropColumn('anchor_id');
        });
    }
};
