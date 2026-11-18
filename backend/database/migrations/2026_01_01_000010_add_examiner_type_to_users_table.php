<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'examiner_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('examiner_type', 20)->nullable()->default('internal')->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'examiner_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('examiner_type');
            });
        }
    }
};
