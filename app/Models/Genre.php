<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function series()
    {
        return $this->hasMany(Series::class, 'genre_id');
    }

    public function movies()
    {
        return $this->hasMany(Movie::class,'genre_id');
    }
}
