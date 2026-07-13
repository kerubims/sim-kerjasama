<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_parties', function (Blueprint $table) {
            $table->string('stamp_path')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('document_parties', function (Blueprint $table) {
            $table->dropColumn('stamp_path');
        });
    }
};
