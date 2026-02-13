<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAddress extends Model
{
    use HasFactory;

    protected $table = 'user_addresses';

    protected $fillable = [
        'user_id',
        'label',
        'street',
        'exterior_number',
        'interior_number',
        'neighborhood',
        'references',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
