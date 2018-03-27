<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bruteforce extends Model
{
    protected $table = 'bruteforce';

    protected $fillable = [
        'user_id','attempts','ip_address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
