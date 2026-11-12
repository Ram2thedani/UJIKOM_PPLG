<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_id',
    'examiner_id',
    'final_score',
    'status',
    'quick_tags',
    'examiner_note',
    'duration_seconds',
    'revision_count',
    'submitted_at',
])]
class Assessment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'final_score' => 'float',
            'quick_tags' => 'array',
            'duration_seconds' => 'integer',
            'revision_count' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(AssessmentDetail::class);
    }
}
