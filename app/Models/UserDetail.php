<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fair_meta',
        'user_id',
        'phone',
        'company_name',
        'placement',
        'status',
        'scanner_data',
    ];
}