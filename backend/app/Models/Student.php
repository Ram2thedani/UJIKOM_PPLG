<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'class_id', 'nis', 'qr_code_token', 'is_priority', 'priority_note'])]
class Student extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_priority' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class);
    }
}
