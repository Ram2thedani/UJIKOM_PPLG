<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['page_number', 'item_order', 'title', 'indicators', 'weight'])]
class RubricItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'page_number' => 'integer',
            'item_order' => 'integer',
            'indicators' => 'array',
            'weight' => 'float',
        ];
    }

    public function assessmentDetails(): HasMany
    {
        return $this->hasMany(AssessmentDetail::class);
    }
}
