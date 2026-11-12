<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_id', 'rubric_item_id', 'raw_score', 'weighted_score'])]
class AssessmentDetail extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'raw_score' => 'integer',
            'weighted_score' => 'float',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function rubricItem(): BelongsTo
    {
        return $this->belongsTo(RubricItem::class);
    }
}
