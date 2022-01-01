<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','stripe_id', 'subtotal', 'tax', 'total','plan_type','start_date','end_date','payment_method'
    ];

    public function user()
    {
        $this->belongsTo(User::class, 'user_id');
    }
}
