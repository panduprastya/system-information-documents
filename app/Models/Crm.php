<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crm extends Model
{
    protected $table = 'crms';

    protected $fillable = [
        'id_user',
        'name',
    ];
}
