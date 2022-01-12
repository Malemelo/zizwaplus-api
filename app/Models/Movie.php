<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'genre_id','title','sub_title','thumbnail','trailer','video','popular','published'
    ];

    public function genre()
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }
}
