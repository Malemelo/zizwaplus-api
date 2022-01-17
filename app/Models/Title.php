<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Title extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'sub_title','thumbnail'
    ];

    public function movies()
    {
        return $this->hasMany(Movie::class,'title_id');
    }
}
