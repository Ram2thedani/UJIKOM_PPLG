<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->onDelete('cascade');
            $table->foreignId('examiner_id')->constrained('users')->onDelete('cascade');
            $table->decimal('final_score', 5, 2)->default(0);
            $table->enum('status', ['pending', 'kompeten', 'remedial'])->default('pending');
            $table->json('quick_tags')->nullable();
            $table->text('examiner_note')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('revision_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
