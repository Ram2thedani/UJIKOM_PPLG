<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('classes')]
#[Fillable(['name', 'major', 'academic_year'])]
class SchoolClass extends Model
{
    use HasFactory;

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function examiners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_examiner', 'class_id', 'user_id')
            ->withTimestamps();
    }
}
