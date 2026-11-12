<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->foreignId('rubric_item_id')->constrained('rubric_items')->onDelete('cascade');
            $table->unsignedTinyInteger('raw_score');
            $table->decimal('weighted_score', 5, 2);
            $table->timestamps();

            $table->unique(['assessment_id', 'rubric_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_details');
    }
};
