<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtnPaymentIntent extends Model
{
    use HasFactory;

    public $fillable = [
        'user_id','reference_id','receipt_number','plan_name','plan_amount','plan_currency'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
