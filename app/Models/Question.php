<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question'
    ];
    public function option()
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }
}
