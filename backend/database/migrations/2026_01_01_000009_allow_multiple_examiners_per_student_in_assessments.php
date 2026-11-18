<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('assessments'))->pluck('name')->all();

        Schema::table('assessments', function (Blueprint $table) use ($indexes) {
            // Tambahkan composite unique (student_id, examiner_id) terlebih dahulu
            // agar foreign key student_id tetap memiliki backing index di MySQL InnoDB
            if (! in_array('assessments_student_examiner_unique', $indexes, true)) {
                $table->unique(['student_id', 'examiner_id'], 'assessments_student_examiner_unique');
            }

            // Hapus unique tunggal pada student_id jika masih ada
            if (in_array('assessments_student_id_unique', $indexes, true)) {
                $table->dropUnique('assessments_student_id_unique');
            }
        });
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('assessments'))->pluck('name')->all();

        Schema::table('assessments', function (Blueprint $table) use ($indexes) {
            if (! in_array('assessments_student_id_unique', $indexes, true)) {
                $table->unique('student_id', 'assessments_student_id_unique');
            }

            if (in_array('assessments_student_examiner_unique', $indexes, true)) {
                $table->dropUnique('assessments_student_examiner_unique');
            }
        });
    }
};
