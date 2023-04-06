<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Azkar extends Model
{
    use HasFactory;
    protected $fillable = [
        'title'
    ];

    public function content()
    {
        return $this->hasMany(AzkarContent::class, 'azkar_id');
    }
}
