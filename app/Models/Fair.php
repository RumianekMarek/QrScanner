<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fair extends Model
{   
    protected $table = 'fair_details';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fair_meta',
        'domain',
        'fair_name',
        'fair_start',
        'fair_end',
        'qr_details',
    ];
}
