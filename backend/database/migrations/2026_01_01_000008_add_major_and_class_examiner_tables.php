<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('major', 20)->default('PPLG')->after('name');
        });

        Schema::table('rubric_items', function (Blueprint $table) {
            $table->string('major', 20)->default('PPLG')->after('id');
            $table->string('page_title')->nullable()->after('page_number');
        });

        Schema::create('class_examiner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_examiner');

        Schema::table('rubric_items', function (Blueprint $table) {
            $table->dropColumn(['major', 'page_title']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('major');
        });
    }
};
