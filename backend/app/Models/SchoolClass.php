<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('classes')]
#[Fillable(['name', 'academic_year'])]
class SchoolClass extends Model
{
    use HasFactory;

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}
