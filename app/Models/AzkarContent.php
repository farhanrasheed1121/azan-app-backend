<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AzkarContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'azkar_id',
        'content'
    ];
}
